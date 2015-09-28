<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");


if($argv[1]=='--users'){parseusers();exit;}
if($argv[1]=='--ldapconf'){ldapconf();exit;}
if($argv[1]=="--change-suffix"){ChangeSuffix();exit;}
if($argv[1]=="--proxy"){proxycnx();exit;}


function parseusers(){
	
$ldap=new clladp();	
$hash=GetOus();	
if(is_array($hash)){
	while (list ($num, $ligne) = each ($hash) ){
		echo "
		================================================
				Found organization: $num
		================================================
		";
		SearchUsers($num);
		
	}
}

}


function SearchUsers($org){
	$ldap=new clladp();
	$dn="ou=$org,dc=organizations,$ldap->suffix";
	$filter="(&(objectclass=userAccount)(cn=*))";
	$attrs[]="dn";
	$con=$ldap->ldap_connection;
	$sr=ldap_search($con, $dn, $filter,$attrs);
	if(!$sr){return false;}
	$entries=ldap_get_entries($con, $sr);	
	for($i=0;$i<=$entries["count"];$i++){
		$dnsearch=$entries[$i]["dn"];
		if($dnsearch==null){continue;}
		echo $dnsearch."\n";
  	}
  	
  	
}

function GetOus(){
	$ldap=new clladp();
	return $ldap->hash_get_ou(true);
  	 
}
function ChangeSuffix(){
	$unix=new unix();
	$ldap=new clladp();
	$sock=new sockets();
	$users=new usersMenus();
	$ChangeLDAPSuffixFrom=utf8_encode(base64_decode($sock->GET_INFO("ChangeLDAPSuffixFrom")));
	$ChangeLDAPSuffixTo=utf8_encode(base64_decode($sock->GET_INFO("ChangeLDAPSuffixTo")));
	$filebackup="/home/artica/ldap_backup/ldap.ldif";
	$php5=$unix->LOCATE_PHP5_BIN();
	$slapcat=$unix->find_program("slapcat");
	$slapadd=$unix->find_program("slapadd");
	$rm=$unix->find_program("rm");
	echo "Starting change LDAP suffix from \"$ChangeLDAPSuffixFrom\"\n";
	echo "Starting change LDAP suffix to \"$ChangeLDAPSuffixTo\"\n";
	if(!is_file("$filebackup")){
		echo "Exporting database to /home/artica/ldap_backup...\n";
		@mkdir("/home/artica/ldap_backup",0755);
		$nextscript=dirname(__FILE__)."/exec.ldapchpipe.php";
		$cmd="/usr/sbin/slapcat -b \"$ChangeLDAPSuffixFrom\"|$php5 $nextscript >$filebackup";
		echo $cmd."\n";
		shell_exec($cmd);
		$filesize=$unix->file_size($filebackup);
		echo "$filebackup $filesize Bytes\n";
		if($filesize<100){
			echo "<strong style='color:#d32d2d'>Corrupted backup file, aborting</strong>\n";
			@unlink($filebackup);
			return;
		}
	}else{
		echo "Skipping exporting datas $filebackup exists\n";
	}
	echo "Starting reconfigure ldap parameters....\n";
	@file_put_contents("/etc/artica-postfix/ldap_settings/suffix", $ChangeLDAPSuffixTo);
	shell_exec("/usr/share/artica-postfix/bin/artica-install --slapdconf");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --nsswitch");
	
	if($users->ZARAFA_INSTALLED){shell_exec("$php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --ldap-config");}
	$slpadconf=$unix->SLAPD_CONF_PATH();
	echo "Stopping watchdogs and LDAP server\n";
	shell_exec("/etc/init.d/artica-postfix stop monit");
	shell_exec("/etc/init.d/artica-status reload");
	shell_exec("/etc/init.d/artica-postfix stop ldap");
	echo "Stopping Removing OpenLDAP database file\n";
	shell_exec("$rm -f /var/lib/ldap/*");
	echo "Injecting data with new suffix\n";
	$cmd="$slapadd -v -s -c -l $filebackup -f $slpadconf";   
	echo $cmd."\n";
	shell_exec($cmd);
	echo "Starting LDAP server\n";
	shell_exec("/etc/init.d/artica-postfix start ldap");
	@copy($filebackup, $filebackup.".".time());
	@unlink($filebackup);
	
	
	echo "\n<script>document.location.href='logoff.php';</script>\n";
	
	if($users->SQUID_INSTALLED){shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --reconfigure >/dev/null 2>&1 &");}
	if($users->SAMBA_INSTALLED){shell_exec("$php5 /usr/share/artica-postfix/exec.samba.php --build >/dev/null 2>&1 &");}
	shell_exec("/etc/init.d/artica-postfix start monit &");
	shell_exec("/etc/init.d/artica-status reload &");
}




function proxycnx(){
	$sock=new sockets();
	$database="artica_backup";
	$ldap=new clladp();
	$cffile="/etc/artica-postfix/proxy.slpad.conf";
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}
	if(!is_numeric($EnableOpenLdapProxy)){$EnableOpenLdapProxy=0;}	
	@unlink($cffile);
	if($EnableOpenLdapProxy==0){echo "slapd: [INFO] LDAP Proxy disabled\n";return;}
	echo "slapd: [INFO] LDAP Proxy Enabled\n";
	$q=new mysql();
	$sql="SELECT * FROM openldap_proxy WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,$database);
	$localdb_suffix=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
	
	$f[]="database\tmeta";
	$f[]="suffix\t\"$OpenLdapProxySuffix\"";   
	$f[]="rootdn\t\"cn=$ldap->ldap_admin,$OpenLdapProxySuffix\"";
	$f[]="rootpw\t\"$ldap->ldap_password\"";                                                                        
	$f[]="";

	$f[]="uri           \"ldap://localhost/$OpenLdapProxySuffix\"";
	$f[]="suffixmassage \"$OpenLdapProxySuffix\" \"$localdb_suffix\"";
	$f[]="idassert-bind       bindmethod=simple";
	$f[]="\tbinddn=\"cn=$ldap->ldap_admin,$OpenLdapProxySuffix\"";
	$f[]="\tcredentials=\"$ldap->ldap_password\"";
    $f[]="";                                                                           

	
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$hostname=$ligne["hostname"];
		$port=$ligne["port"];
		$articabranch=$ligne["articabranch"];
		$suffixlink=$ligne["suffixlink"];
		if($suffixlink=="*"){$suffixlink="";}
		if($suffixlink==","){$suffixlink="";}
		if($suffixlink<>null){$suffixlink="$suffixlink,";}
		$suffixmassage="$suffixlink$OpenLdapProxySuffix";
		if($articabranch==1){$suffixmassage="$OpenLdapProxySuffix";}
		echo "slapd: [INFO] Proxy:{$ligne["ID"]} $hostname:$port -> $suffixmassage\n";
		$f[]="uri\t\"ldap://$hostname:$port/$suffixmassage\"";
		$f[]="suffixmassage\t\"$suffixmassage\" \"{$ligne["suffix"]}\"";
		$f[]="idassert-bind	bindmethod=simple";
		$f[]="\tbinddn=\"{$ligne["username"]}\"";
		$f[]="\tcredentials=\"{$ligne["password"]}\"";
		$rwm=rwm($ligne["ID"]);
		if($rwm<>null){$f[]=$rwm;}else{echo "slapd: [INFO] Proxy:{$ligne["ID"]} $hostname:$port no rwm..\n";}
		$f[]="\n";
	}
	
	$f[]="lastmod off";
	$c++;
	@file_put_contents($cffile, @implode("\n", $f));
	if($GLOBALS["VERBOSE"]){echo @implode("\n", $f)."\n";}
	echo "slapd: [INFO] Proxy LDAP $c proxy(s) Done\n";
	
}

function rwm($proxyid){
	$q=new mysql();
	$sql="SELECT * FROM openldap_proxyattrs WHERE proxyid=$proxyid";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$ctn=mysql_num_rows($results);	
	echo "slapd: [INFO] Proxy:{$proxyid} $ctn rwm rule(s)\n";
	if($ctn==0){return null;}
	//$f[]="overlay              rwm";
	while ($ligne = mysql_fetch_assoc($results)) {
		if(isset($al[strtolower($ligne["attribute"])])){continue;}
		//$f[]="rwm-map\t{$ligne["type"]}\t{$ligne["attribute"]}\t{$ligne["match"]}";
		$f[]="map\t{$ligne["type"]}\t{$ligne["attribute"]}\t{$ligne["match"]}";
		$al[strtolower($ligne["attribute"])]=true;
	}
	/*if(count($f)>0){
		$f[]="rwm-map\tattribute\t*";
	}*/
	return @implode("\n", $f);
}

