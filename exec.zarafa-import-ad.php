<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


startx();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/zarafa-import-ad-contatcs.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}



function startx(){

$sock=new sockets();
$ldap=new clladp();
$t=time();
$ImportAdSettings=unserialize($sock->GET_INFO("ZarafaImportADSettings"));

$AdServerName=$ImportAdSettings["ADSERVERNAME"];
$WINDOWS_SERVER_ADMIN=$ImportAdSettings["WINDOWS_SERVER_ADMIN"];
$WINDOWS_SERVER_PASS=$ImportAdSettings["WINDOWS_SERVER_PASS"];
$LDAP_SUFFIX=$ImportAdSettings["LDAP_SUFFIX"];
$ADOU=$ImportAdSettings["ADOU"];
echo "Active Directory Server:........$AdServerName\n";
echo "Active Directory User:..........$WINDOWS_SERVER_ADMIN\n";
echo "Active Directory Suffix.........$LDAP_SUFFIX\n";
echo "To organization.................$ADOU\n";
build_progress("{connecting}",10);

$ldap_connection=ldap_connect($AdServerName, 389);

if(!$ldap_connection){
	build_progress("{connecting} {failed}",110);
	return;
}

ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

build_progress("{authenticate}",20);

$ldapbind=@ldap_bind($ldap_connection, "$WINDOWS_SERVER_ADMIN",$WINDOWS_SERVER_PASS);

if(!$ldapbind){
	$errornumber= ldap_errno($ldap_connection);
	$errorstring= ldap_err2str($errornumber);
	@ldap_close($ldap_connection);
	echo "Error N.$errornumber $errorstring\n";
	build_progress("{authenticate} {failed}",110);
	return;
}

build_progress("{search_users} ($LDAP_SUFFIX)",30);

$filter="(objectClass=user)";
$sr =@ldap_search($ldap_connection,$LDAP_SUFFIX,$filter,array());

if(!$sr){
	$errorstring=ldap_err2str(ldap_errno($ldap_connection));
	echo "Error $errorstring\n";
	build_progress("{search_users} {failed}",110);
	@ldap_close($ldap_connection);
	return;
}

$entries=ldap_get_entries($ldap_connection,$sr);
$FAILED=0;
$SUCC=0;
$Count=$entries["count"];

for($i=0;$i<$Count;$i++){
	
	$prc=($i/$Count)*100;
	$prc=round($prc);
	if($prc<30){$prc=30;}
	if($prc>95){$prc=95;}
	
	$FirstMail=null;
	$MAIN=$entries[$i];
	$mails=array();
	$displayname=$MAIN["displayname"][0];
	$sn=$MAIN["sn"][0];
	if(preg_match("#(MSExch|FederatedEmail)#", $sn)){continue;}
	$C=$MAIN["c"][0];
	$L=$MAIN["l"][0]; // Orgerus
	$st=$MAIN["st"][0]; //Yvelines
	$title=$MAIN["title"][0]; // Ingenieur comme
	$postalcode=$MAIN["postalcode"][0];
	$telephonenumber=$MAIN["telephonenumber"][0];
	$givenname=$MAIN["givenname"][0];
	$streetaddress=$MAIN["streetaddress"];
	$uid=$MAIN["samaccountname"][0];
	$mobile=$MAIN["mobile"][0];
	if(isset($MAIN["userprincipalname"][0])){
		if(preg_match("#.+?@.+?#", $MAIN["userprincipalname"][0])){
			$mails[$MAIN["userprincipalname"][0]]=$MAIN["userprincipalname"][0];
			$FirstMail=$MAIN["userprincipalname"][0];
		}
	}
	
	if(preg_match("#.+?@.+?#", $MAIN["mail"][0])){
		$FirstMail= $MAIN["mail"][0];
	}
	
	$mails[$MAIN["mail"][0]]=$MAIN["mail"][0];
	$proxyaddresses=strtolower($MAIN["proxyaddresses"][0]);
	if(preg_match("#^x500:#",$proxyaddresses)){continue;}
	if(preg_match("#^x400:#",$proxyaddresses)){continue;}
	if(preg_match("#smtp:(.+?)$#",$proxyaddresses,$rz)){
		if(preg_match("#.+?@.+?#", $rz[1])){
			$FirstMail=$rz[1];
			$mails[$FirstMail]=$FirstMail;
		}
	}
	
	if(strpos($FirstMail, "}")>0){continue;}
	
	if($FirstMail==null){
		build_progress("$uid -- SKIP",$prc);
		continue;
	}
	
	
	
	build_progress("$uid",$prc);
	$user=new user($uid);

	$xd=explode("@",$FirstMail);
	$user->domainname=$xd[1];
	$user->mail=$FirstMail;
	$user->DisplayName=$displayname;
	$user->postalCode=$postalcode;
	$user->telephoneNumber=$telephonenumber;
	$user->givenName=$givenname;
	$user->sn=$sn;
	$user->title=$title;
	$user->street=$streetaddress;
	$user->town=$L;
	$user->mobile=$mobile;
	$user->ou=$ADOU;
	$user->AsZarafaContact=true;
	if(!$user->add_user()){
		build_progress("$uid -- FAILED",$prc);
		$FAILED++;
	}
	$SUCC++;
}

echo "Failed: $FAILED\n";

build_progress("{success} {$SUCC} {contacts}",99);
sleep(5);
build_progress("{success} {$SUCC} {contacts}",100);


}


