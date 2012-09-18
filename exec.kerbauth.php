<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");
$GLOBALS["CHECKS"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;echo "VERBOSED !!! \n";}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--checks#",implode(" ",$argv))){$GLOBALS["CHECKS"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["EXEC_PID_FILE"]="/etc/artica-postfix/".basename(__FILE__).".pid";
$unix=new unix();

if($argv[1]=="--klist"){ping_klist();die();}
if($argv[1]=='--winbinddpriv'){winbind_priv_perform(true);die();}

if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Executing with `{$argv[1]}` command...", basename(__FILE__));}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--ping"){ping_kdc();die();}
if($argv[1]=="--samba-proxy"){SAMBA_PROXY();die();}
if($argv[1]=='--winbindfix'){winbindfix();die();}
if($argv[1]=='--winbindacls'){winbindd_set_acls_mainpart();die();}
if($argv[1]=='--winbinddmonit'){winbindd_monit();die();}
if($argv[1]=='--join'){JOIN_ACTIVEDIRECTORY();die();}
if($argv[1]=='--samba-ver'){SAMBA_VERSION_DEBUG();die();}



unset($argv[0]);
echo "Unable to understand ".@implode(" ", $argv)."\n";


function build(){
	$unix=new unix();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){
		echo "Starting......: Kerberos, disabled\n";
		if(is_file("/etc/monit/conf.d/winbindd.monitrc")){@unlink("/etc/monit/conf.d/winbindd.monitrc");}
		return;
	}
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeExec=$unix->PROCCESS_TIME_MIN($oldpid);
		writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		return;}
	$time=$unix->file_time_min($timefile);
	if($time<2){
		writelogs("2mn minimal to run this script currently ({$time}Mn)",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	writelogs("Running PID $mypid",__FUNCTION__,__FILE__,__LINE__);
	
	$wbinfo=$unix->find_program("wbinfo");
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file($wbinfo)){
		shell_exec("$php5 /usr/share/artica-postfix exec.apt-get.php --sources-list");
		shell_exec("$nohup /usr/share/artica-postfix/bin/setup-ubuntu --check-samba >/dev/null 2>&1 &");
		$wbinfo=$unix->find_program("wbinfo");
		
	}
	if(!is_file($wbinfo)){
		echo "Starting......: Kerberos, samba is not installed\n";
		die();
	}
	
	if(!checkParams()){echo "Starting......: Kerberos, misconfiguration failed\n";return;}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	if(is_file("/usr/sbin/msktutil")){shell_exec("$chmod 755 /usr/sbin/msktutil");}
	
	$msktutil=$unix->find_program("msktutil");
	$kdb5_util=$unix->find_program("kdb5_util");
	$kadmin_bin=$unix->find_program("kadmin");
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	if(!is_file("$msktutil")){
			echo "Starting......: Kerberos, msktutil no such binary\n";
			if(is_file("/home/artica/mskutils.tar.gz.old")){
				echo "Starting......: Kerberos, msktutil /home/artica/mskutils.tar.gz.old found, install it\n";
				shell_exec("$tar -xf /home/artica/mskutils.tar.gz.old -C /");
			}
	}
	$msktutil=$unix->find_program("msktutil");		
	if(!is_file("$msktutil")){
		shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_MSKTUTIL >/dev/null 2>&1 &");
		return;
	}
	
	
	$uname=posix_uname();
	$mydomain=$uname["domainname"];
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$enctype=null;
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	
	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2003"){
		$t[]="# For Windows 2003:";
		$t[]=" default_tgs_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		
	}
	
	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		$t[]="; for Windows 2008 with AES";
		$t[]=" default_tgs_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		$enctype=" --enctypes 28";
		
	}	
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));	
	echo "Starting......: Kerberos, $hostname\n";
	echo "Starting......: Kerberos, my domain: \"$mydomain\"\n";
	echo "Starting......: Kerberos, my hostname: \"$myFullHostname\"\n";
	echo "Starting......: Kerberos, my netbiosname: \"$myNetBiosName\"\n";
	
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=str_replace("'","",escapeshellarg($kinitpassword));
	$kinitpassword=str_replace('$', '\$', $kinitpassword);

	
	$f[]=" [logging]";
	$f[]=" default = FILE:/var/log/krb5libs.log";
	$f[]=" kdc = FILE:/var/log/krb5kdc.log";
	$f[]=" admin_server = FILE:/var/log/kadmind.log";
	$f[]="";
	$f[]="[libdefaults]";
	$f[]=" default_realm = $domainUp";
	$f[]=" dns_lookup_realm = true";
	$f[]=" dns_lookup_kdc = true";
	$f[]=" ticket_lifetime = 24h";
	$f[]=" forwardable = yes";
	$f[]="";
	@implode("\n", $t);

	$f[]="[realms]";
	$f[]=" $domainUp = {";
	$f[]="  kdc = $hostname";
	$f[]="  admin_server = $hostname";
	$f[]="  default_domain = $domainUp";
	$f[]=" }";
	$f[]="";
	$f[]="[domain_realm]";
	$f[]=" .$domaindow = $domainUp";
	$f[]=" $domaindow = $domainUp";
	$f[]="";
	$f[]="[appdefaults]";
	$f[]=" pam = {";
	$f[]="   debug = false";
	$f[]="   ticket_lifetime = 36000";
	$f[]="   renew_lifetime = 36000";
	$f[]="   forwardable = true";
	$f[]="   krb4_convert = false";
	$f[]="}";
	$f[]="";	
	@file_put_contents("/etc/krb.conf", @implode("\n", $f));
	echo "Starting......: Kerberos, /etc/krb.conf done\n";
	@file_put_contents("/etc/krb5.conf", @implode("\n", $f));
	echo "Starting......: Kerberos, /etc/krb5.conf done\n";	
	unset($f);
	$f[]="lhs=.ns";
	$f[]="rhs=.$mydomain";
	$f[]="classes=IN,HS";
	@file_put_contents("/etc/hesiod.conf", @implode("\n", $f));
	echo "Starting......: Kerberos, /etc/hesiod.conf done\n";


	unset($f);
	$f[]="[libdefaults]";
	$f[]="\t\tdebug = true";
	$f[]="[kdcdefaults]";
	//$f[]="\tv4_mode = nopreauth";	
	$f[]="\tkdc_ports = 88,750";	
	//$f[]="\tkdc_tcp_ports = 88";	
	$f[]="[realms]";	
	$f[]="\t$domainUp = {";	
	$f[]="\t\tdatabase_name = /etc/krb5kdc/principal";
	$f[]="\t\tacl_file = /etc/kadm.acl";	
	$f[]="\t\tdict_file = /usr/share/dict/words";	
	$f[]="\t\tadmin_keytab = FILE:/etc/krb5.keytab";
	$f[]="\t\tkey_stash_file = /etc/krb5kdc/.k5.$domainUp";
	$f[]="\t\tmaster_key_type = des3-hmac-sha1";
	$f[]="\t\tsupported_enctypes = des3-hmac-sha1:normal des-cbc-crc:normal des:normal des:v4 des:norealm des:onlyrealm des:afs3";	
	$f[]="\t\tdefault_principal_flags = +preauth";
	$f[]="\t}";
	$f[]="";
	if(!is_dir("/usr/share/krb5-kdc")){@mkdir("/usr/share/krb5-kdc",644,true);}
	@file_put_contents("/usr/share/krb5-kdc/kdc.conf", @implode("\n", $f));
	@file_put_contents("/etc/kdc.conf", @implode("\n", $f));
	echo "Starting......: Kerberos, /usr/share/krb5-kdc/kdc.conf done\n";
	echo "Starting......: Kerberos, /etc/kdc.conf done Line:".__LINE__."\n";
	
	unset($f);

	$config="*/admin *\n";
	@file_put_contents("/etc/kadm.acl"," ");
	@file_put_contents("/usr/share/krb5-kdc/kadm.acl"," ");
	@file_put_contents("/etc/krb5kdc/kadm5.acl"," ");
	echo "Starting......: Kerberos, /etc/kadm.acl done\n";


	RunKinit($array["WINDOWS_SERVER_ADMIN"],$array["WINDOWS_SERVER_PASS"]);
	
	unset($results);
	if($GLOBALS["VERBOSE"]){$mskutilverb=" --verbose";}
 

	$cmd="$msktutil -c -b \"CN=COMPUTERS\" -s HTTP/$myFullHostname -h $myFullHostname --keytab /etc/krb5.keytab";
	$cmd=$cmd." --computer-name $myNetBiosName --upn HTTP/$myFullHostname --server $hostname$enctype$mskutilverb 2>&1";
	echo "Starting......: msktutil, $cmd Line:".__LINE__."\n";
exec($cmd,$results);

	while (list ($num, $a) = each ($results) ){echo "Starting......: msktutil, $a Line:".__LINE__."\n";}


	 //kadmin -p Administrateur "addprinc -randkey cifs/bdc.touzeau.com" -w DavidTouzeau180872
		if($GLOBALS["VERBOSE"]){echo "netbin -> $netbin in line ".__LINE__."\n";}
		if(is_file($netbin)){
			try {
				if($GLOBALS["VERBOSE"]){echo "netbin -> SAMBA_PROXY() in line ".__LINE__."\n";}
				SAMBA_PROXY();
			} catch (Exception $e) {echo 'Exception Error: Message: ' .$e->getMessage()."\n";}
		
	
		
	}

	if($GLOBALS["VERBOSE"]){echo "Next in line ".__LINE__."\n";}
	
	if($GLOBALS["VERBOSE"]){"kdb5_util -> $kdb5_util in line ".__LINE__."\n";}
	if(is_file("$kdb5_util")){
		$cmd="$kdb5_util create -r $domainUp -s -P $kinitpassword";
		if($GLOBALS["VERBOSE"]){echo "Starting......:  $cmd Line:".__LINE__."\n";}
		$results=array();
		exec($cmd,$results);
		while (list ($num, $a) = each ($results) ){echo "Starting......: kdb5_util, $a Line:".__LINE__."\n";}
	}
	
	if($GLOBALS["VERBOSE"]){"kadmin_bin -> $kadmin_bin in line ".__LINE__."\n";}
	if(is_file("$kadmin_bin")){}
	
	 //kadmin -p Administrateur "addprinc -randkey cifs/bdc.touzeau.com" -w DavidTouzeau180872
	if($GLOBALS["VERBOSE"]){"netbin -> $netbin in line ".__LINE__."\n";}
	if(is_file("$netbin")){
		if($GLOBALS["VERBOSE"]){"netbin -> JOIN_ACTIVEDIRECTORY() in line ".__LINE__."\n";}
		JOIN_ACTIVEDIRECTORY();
	
	}
	winbind_priv();
	winbindd_monit();
	if(is_file("/etc/init.d/winbind")){shell_exec("/etc/init.d/winbind restart");}



}

function JOIN_ACTIVEDIRECTORY(){
$unix=new unix();	
$user=new settings_inc();
$netbin=$unix->LOCATE_NET_BIN_PATH();
	
if(!is_file($netbin)){echo "Starting......:  net, no such binary\n";return;}
if(!$user->SAMBA_INSTALLED){echo "Starting......:  Samba, no such software\n";return;}
$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
$KDC_SERVER=$NetADSINFOS["KDC server"];
$sock=new sockets();
$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
$adminpassword=$array["WINDOWS_SERVER_PASS"];
$adminpassword=escapeshellarg($adminpassword);
$adminpassword=str_replace("'", "", $adminpassword);
$adminpassword=str_replace('$', '\$', $adminpassword);
$adminname=$array["WINDOWS_SERVER_ADMIN"];
$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
$workgroup=$array["ADNETBIOSDOMAIN"];
$ipaddr=trim($array["ADNETIPADDR"]);
if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Trying to relink this server with Active Directory $ad_server.$domain_lower server", basename(__FILE__));}
echo "Starting......:  Samba, [$adminname]: Kdc server ads : $KDC_SERVER\n";
if($KDC_SERVER==null){
		$cmd="$netbin ads join -W $ad_server.$domain_lower -S $ad_server -U $adminname%$adminpassword 2>&1";
		if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
		exec("$cmd",$results);
		while (list ($index, $line) = each ($results) ){echo "Starting......:  Samba,ads join [$adminname]: $line\n";}	
		$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
		$KDC_SERVER=$NetADSINFOS["KDC server"];
	}
	
	if($KDC_SERVER==null){
		echo "Starting......:  Samba, [$adminname]: unable to join the domain $domain_lower\n";
		
	}
	
	

	
echo "Starting......:  Samba, [$adminname]: setauthuser..\n";
$cmd="$netbin setauthuser -U $adminname%$adminpassword";	
if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
shell_exec($cmd);	

if($ipaddr==null){
	$JOINEDRES=false;
	echo "Starting......:  Samba, [$adminname 0]: join for $workgroup (without IP addr)\n";	
	if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
	$cmd="$netbin join -U $adminname%$adminpassword $workgroup 2>&1";
	exec($cmd,$A1);
	while (list ($index, $line) = each ($A1) ){
		if(preg_match("#Joined#", $line)){
			echo "Starting......:  Samba, [$adminname]: join for $workgroup (without IP addr) success\n";
			$JOINEDRES=true;
			break;
		}
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......:  Samba, $line", basename(__FILE__));}
	}
	
	if(!$JOINEDRES){
		echo "Starting......:  Samba, [$adminname 0]: join as netrpc.. (without IP addr)\n";	
		$cmd="$netbin rpc join -U $adminname%$adminpassword $workgroup 2>&1";
		exec($cmd,$A2);
		if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
		while (list ($index, $line) = each ($A2) ){
			if(preg_match("#Joined#", $line)){
				echo "Starting......:  Samba, [$adminname]: join for $workgroup (without IP addr) success\n";
				$JOINEDRES=true;
				break;
			}
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......:  Samba, $line", basename(__FILE__));}	
		}
	}
	
}

if($ipaddr<>null){
	echo "Starting......:  Samba, [$adminname 1]: ads '$netbin ads join -I $ipaddr -U $adminname%**** $workgroup'\n";
	//$cmd="$netbin ads join -S $ad_server.$domain_lower -I $ipaddr -U $adminname%$adminpassword 2>&1";
	$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
	if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
	exec($cmd,$BIGRES2);	
	while (list ($index, $line) = each ($BIGRES2) ){
		if(preg_match("#Failed to join#i", $line)){
			echo "Starting......:  Samba, [$adminname 1]: ads join failed ($line), using pure IP\n";
			echo "Starting......:  Samba, [$adminname 1]: '$netbin ads join -I $ipaddr -U $adminname%*** $workgroup'\n";
			$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
			if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
			$BIGRESS=array();
			exec($cmd,$BIGRESS);
			
			if(!is_array($BIGRESS)){$BIGRESS=array();}
			if(count($BIGRESS)==0){
				$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword 2>&1";
				if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
				exec($cmd,$BIGRESS);
			}
			
			
			while (list ($index, $line) = each ($BIGRES1) ){
				echo "Starting......:  Samba, [$adminname 2] $line\n";
				if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......:  Samba, $line", basename(__FILE__));}
			}
			
			break;
		}
		echo "Starting......:  Samba,[$adminname 1] $line\n";
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......:  Samba, $line", basename(__FILE__));}
	}
	
	
	/*echo "Starting......:  Samba, [$adminname]: join with  IP Adrr:$ipaddr..\n";	
	$cmd="$netbin join -U $adminname%$adminpassword -I $ipaddr";
	if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
	shell_exec($cmd);*/

}

	if($KDC_SERVER==null){$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();$KDC_SERVER=$NetADSINFOS["KDC server"];}
	if($KDC_SERVER==null){echo "Starting......:  Samba, [$adminname]: unable to join the domain $domain_lower\n";}	

	echo "Starting......:  Samba, [$adminname]: Kdc server ads : $KDC_SERVER\n";
	
	unset($results);
	$cmd="$netbin ads keytab create -P -U $adminname%$adminpassword 2>&1";
	if($GLOBALS["VERBOSE"]){echo "Starting......:  Samba, $cmd\n";}
	exec("$cmd",$results);
	while (list ($index, $line) = each ($results) ){echo "Starting......:  Samba,ads keytab: [$adminname]: $line\n";}		

}

function SAMBA_VERSION(){
	
	$unix=new unix();
	$winbind=$unix->find_program("winbindd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
	
	
}

function SAMBA_VERSION_DEBUG(){
	$SAMBA_VERSION=SAMBA_VERSION();
	echo "Samba Version (Winbind): $SAMBA_VERSION\n";
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){
		echo "Samba 3.6 OK\n";
	}
}


function SAMBA_PROXY(){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Reconfigure Samba for proxy commpliance", basename(__FILE__));}
	if($GLOBALS["VERBOSE"]){"SAMBA_SPECIFIC_PROXY() start... in line ".__LINE__."\n";}
	$IsAppliance=false;
	if($GLOBALS["VERBOSE"]){"users=new usersMenus(); in line ".__LINE__."\n";}
	$user=new settings_inc();
	
	
	if(!$user->SAMBA_INSTALLED){echo "Starting......:  Samba, no such software\n";return;}
	if($user->SQUID_APPLIANCE){$IsAppliance=true;}
	if($user->KASPERSKY_WEB_APPLIANCE){$IsAppliance=true;}
	if(!$IsAppliance){echo "Starting......:  Samba,This is not a Proxy appliance, i leave untouched smb.conf\n";return;}
	echo "Starting......:  Samba, it is an appliance...\n";

	if($GLOBALS["VERBOSE"]){"sock=new sockets();; in line ".__LINE__."\n";}
	$unix=new unix();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
	if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}	
	
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=escapeshellarg($adminpassword);
	$adminpassword=str_replace("'", "", $adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
	
	
	
	$workgroup=$array["ADNETBIOSDOMAIN"];
	$realm=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$ipaddr=trim($array["ADNETIPADDR"]);
	echo "Starting......:  Samba, [$adminname]: Kdc server ads : $ad_server workgroup `$workgroup` ipaddr:$ipaddr\n";	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$password_server=$hostname;
	//if($ipaddr<>null){$password_server=$ipaddr;}
	if(strpos($password_server, ".")>0){$aa=explode(".", $password_server);$password_server=$aa[0];}
	$SAMBA_VERSION=SAMBA_VERSION();
	
	$AS36=false;
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){$AS36=true;}
	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#", $SAMBA_VERSION,$re)){
		$MAJOR=intval($re[1]);
		$MINOR=intval($re[2]);
		$REV=intval($re[3]);
		echo "Starting......:  Samba, V$MAJOR $MINOR $REV\n";
		
	}
	
	
	$f[]="[global]";
	$f[]="\tworkgroup = $workgroup";
	$f[]="\tkerberos method = system keytab";
	$f[]="\trealm = $realm";
	$f[]="\tsecurity = ads";
	$f[]="\twinbind enum groups = yes";
	$f[]="\twinbind enum users = yes";	
	
	
	$arrayBCK["autorid"]="autorid";
	$arrayBCK["rid"]="rid";
	$arrayBCK["tdb"]="tdb";
	
	  switch ($array["SAMBA_BACKEND"]) {
            case 'autorid':
          		$f[]="\tidmap backend = autorid";
				$f[]="\tidmap gid = 100000-1499999";
				$f[]="\tidmap gid = 100000-1499999";	
            break;
            case 'rid':
				$f[]="\tidmap config * :backend	= rid";
				$f[]="\tidmap config * :read only= yes";
				$f[]="\tidmap config * :range	= 50000001-5999999";
				$f[]="\tidmap config * :base_rid	= 0";
				$f[]="\tidmap gid = 70000 - 99999";
				$f[]="\tidmap uid = 70000 - 99999";	
            break;	
            case 'tdb':
				$f[]="\tidmap config * : range = 20000 - 20000000";
				$f[]="\tidmap config * : read only= yes";				
				$f[]="\tidmap config * : backend = tdb";
            break;	
			case 'ad':
				$f[]="\tidmap config * : range = 20000 - 20000000";
				$f[]="\tidmap config * : read only= yes";				
				$f[]="\tidmap config * : backend = tdb";
        		$f[]="\tidmap config $workgroup : backend  = ad";
        		$f[]="\tidmap config $workgroup : range = 1000-999999	";			
            break;	            




            default:
				$f[]="\tidmap config * : range = 20000 - 20000000";
				$f[]="\tidmap config * : backend = tdb";            	
			break;
            
            
 		}
		
		
		$f[]="\tclient ntlmv2 auth = Yes";
		$f[]="\tclient lanman auth = No";
		$f[]="\twinbind normalize names = Yes";
		$f[]="\twinbind separator = /";
		$f[]="\twinbind use default domain = yes";
		$f[]="\twinbind nested groups = Yes";
		$f[]="\twinbind nss info = rfc2307";
		$f[]="\twinbind reconnect delay = 30";
		$f[]="\twinbind offline logon = true";
		$f[]="\twinbind cache time = 1800";
		$f[]="\twinbind refresh tickets = true";
		$f[]="\tallow trusted domains = Yes";
		$f[]="\tserver signing = auto";
		$f[]="\tclient signing = auto";
		$f[]="\tlm announce = No";
		$f[]="\tntlm auth = No";
		$f[]="\tlanman auth = No";
		$f[]="\tpreferred master = No";	
		
	
	
	$f[]="\tencrypt passwords = yes";
	//$f[]="\tpassword server = *";
	$f[]="\tpassword server = $password_server";	
	//$f[]="\twinbind use default domain = yes";
	$f[]="\tprinting = bsd";
	$f[]="\tload printers = no";
	$f[]="\tsocket options = TCP_NODELAY SO_RCVBUF=8192 SO_SNDBUF=8192";
	//$f[]="\tinterfaces = 127.0.0.0/255.0.0.0";
	//$f[]="\tbind interfaces only = yes";
	$f[]="";
	@file_put_contents("/etc/samba/smb.conf", @implode("\n", $f));
	echo "Starting......:  Samba, [$adminname]: SMB.CONF DONE, restarting services\n";
	shell_exec("/usr/share/artica-postfix/bin/artica-install --nsswitch");
	shell_exec("/etc/init.d/artica-postfix restart samba");	
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.ad.import.php --by=". basename(__FILE__)." &");
	
}

function ping_klist(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=escapeshellarg($adminpassword);
	$adminpassword=str_replace("'", "", $adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];	
	RunKinit($array["WINDOWS_SERVER_ADMIN"],$array["WINDOWS_SERVER_PASS"]);
	
}

function RunKinit($username,$password){
$unix=new unix();
$kinit=$unix->find_program("kinit");
$klist=$unix->find_program("klist");
$echo=$unix->find_program("echo");
if(!is_file($kinit)){echo2("Unable to stat kinit");return;}

exec("$klist 2>&1",$res);
$line=@implode("",$res);


if(strpos($line,"No credentials cache found")>0){
	unset($res);
	echo2($line." -> initialize..");
	$password=escapeshellarg($password);
	$password=str_replace("'", "", $password);
	$cmd="$echo \"$password\"|$kinit {$username} 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec("$echo \"$password\"|$kinit {$username} 2>&1",$res);
	while (list ($num, $a) = each ($res) ){	
		if(preg_match("#Password for#",$a,$re)){unset($res[$num]);}
	}	
	$line=@implode("",$res);	
	if(strlen(trim($line))>0){
		echo2($line." -> Failed..");
		return;
	}
	unset($res);
	exec("$klist 2>&1",$res);	
}

while (list ($num, $a) = each ($res) ){	if(preg_match("#Default principal:(.+)#",$a,$re)){echo2(trim($re[1])." -> success");break;}}	
	

	
}

function echo2($content){
	echo "Starting......: Kerberos,$content\n";
	
}

function ping_kdc(){
	$sock=new sockets();
	$unix=new unix();
	$users=new settings_inc();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){echo "Starting......: [PING]: Kerberos, disabled\n";return;}
	if(!checkParams()){echo "Starting......: [PING]: Kerberos, misconfiguration failed\n";return;}
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$time=$unix->file_time_min($filetime);
	if(!$GLOBALS["FORCE"]){
		if($time<120){
			if(!$GLOBALS["VERBOSE"]){return;}
			echo "$filetime ({$time}Mn)\n";
		}
	}
	$kinit=$unix->find_program("kinit");
	$echo=$unix->find_program("echo");
	$net=$unix->LOCATE_NET_BIN_PATH();
	$wbinfo=$unix->find_program("wbinfo");
	$chmod=$unix->find_program("chmod");

	$domain=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$ad_server=strtolower($array["WINDOWS_SERVER_NETBIOSNAME"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=escapeshellarg($kinitpassword);
	
	$clock_explain="The clock on you system (Linux/UNIX) is too far off from the correct time.\nYour machine needs to be within 5 minutes of the Kerberos servers in order to get any tickets.\nYou will need to run ntp, or a similar service to keep your clock within the five minute window";
	
	
	$cmd="$echo $kinitpassword|$kinit {$array["WINDOWS_SERVER_ADMIN"]}@$domain -V 2>&1";
	echo "$cmd\n";
	exec("$cmd",$kinit_results);
	while (list ($num, $ligne) = each ($kinit_results) ){
		
		if(preg_match("#Clock skew too great while getting initial credentials#", $ligne)){
			if($GLOBALS["VERBOSE"]){echo "Clock skew too great while\n";}
			$array["RESULTS"]=false;
			$array["INFO"]=$ligne;
			$unix->send_email_events("Active Directory connection clock issue", 
			"kinit program claim\n$ligne\n$clock_explain", "system");
		}
		if(preg_match("#Client not found in Kerberos database while getting initial credentials#", $ligne)){
			$array["RESULTS"]=false;
			$array["INFO"]=$ligne;
			$unix->send_email_events("Active Directory authentification issue", "kinit program claim\n$ligne\n", "system");}
		if(preg_match("#Authenticated to Kerberos#", $ligne)){
			$array["RESULTS"]=true;
			$array["INFO"]=$ligne;
			echo "starting......: [PING]: Kerberos, Success\n";
		}
		if($GLOBALS["VERBOSE"]){echo "kinit: $ligne\n";}
	}

	
	//if(is_file($net)){
		/*$kinit_results=array();
		/*exec("$net ads status 2>&1",$kinit_results);
		while (list ($num, $ligne) = each ($kinit_results) ){
			if(preg_match("#No machine account for#", $ligne)){
				$array["RESULTS"]=false;
				$array["INFO"]=$array["INFO"]."<br><i>$ligne</i>";
			}
		}
	}*/
	
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/kinit.array", serialize($array));
	shell_exec("$chmod 777 /usr/share/artica-postfix/ressources/logs/kinit.array");
	if($users->SQUID_INSTALLED){winbind_priv();}
	
}


function winbind_priv($reloadservs=false){
	echo "starting......: winbindd_priv...\n";
	if(!winbind_priv_is_group()){xsyslog("winbindd_priv group did not exists...");return;}
	
	$unix=new unix();
	$gpass=$unix->find_program('gpass');
	if(is_file($gpass)){
		echo "starting......: winbindd_priv group exists, add squid a member of winbindd_priv\n";
		$cmdline="$gpass -a squid winbindd_priv";
		if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
		exec("$cmdline",$kinit_results);
		while (list ($num, $ligne) = each ($kinit_results) ){echo "starting......: winbindd_priv: $ligne\n";}
	}else{
		echo "starting......: winbindd_priv gpass, no such binary\n";
	}
	
	
	
	winbind_priv_perform();
	
	$pid_path=$unix->LOCATE_WINBINDD_PID();
	$pid=$unix->get_pid_from_file($pid_path);
	echo "starting......: winbindd_priv does not exists\n";
	echo "starting......: winbindd_priv checks Samba Winbind Daemon pid: $pid ($pid_path)...\n";
	if(!$unix->process_exists($pid)){
		echo "starting......: winbindd_priv checks Samba Winbind Daemon stopped, start it...\n";
		start_winbind();
		
	}else{
		if($reloadservs){
			echo "starting......: winbindd_priv running.. reload it\n";
			stop_winbind();
			start_winbind();
		}
	}
	
	winbind_priv_perform();
	winbindd_monit();
	
	
}
function winbind_priv_is_group(){
	$f=file("/etc/group");
	while (list ($num, $ligne) = each ($f) ){if(preg_match("#^winbindd_priv#", $ligne)){return true;}}
	return false;
}

function winbind_priv_perform($withpid=false){
	if(isset($GLOBALS[__FUNCTION__."EXECUTED"])){return;}
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return;}
	$unix=new unix();
	// /lib/libnss_winbind.so.2 /lib/libnss_winbind.so
	$possibleDirs[]="/var/run/samba/winbindd_privileged";
	$possibleDirs[]="/var/lib/samba/winbindd_privileged";
	$setfacl=$unix->find_program("setfacl");
	
	if($withpid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			xsyslog("(". __FUNCTION__.") Already executed PID:$oldpid");
			if($GLOBALS["VERBOSE"]){echo "Already executed PID:$oldpid\n";}
		}
		
	}
	
	
	if(strlen($setfacl)>5){winbindd_set_acls_mainpart();}
	
	
	while (list ($num, $Directory) = each ($possibleDirs) ){
			if(is_dir($Directory)){
				if(strlen($setfacl)>5){shell_exec("$setfacl -m u:squid:rwx $Directory");}
				@chmod($Directory,0750);
				chgrp($Directory, "winbindd_priv");
			}
			
			if(file_exists("$Directory/pipe")){
				if(strlen($setfacl)>5){shell_exec("$setfacl -m u:squid:rwx $Directory/pipe");}
				chgrp("$Directory/pipe", "winbindd_priv");
				
			}
			
						
		}
		
	if(!$withpid){	
		$squidbin=$unix->find_program("squid");
		$nohup=$unix->find_program("nohup");
		if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
		if(is_file($squidbin)){
			xsyslog("starting......: Reloading $squidbin");
			shell_exec("$squidbin -k reconfigure >/dev/null 2>&1 &");
		}
	}
	$GLOBALS[__FUNCTION__."EXECUTED"]=true;
}

function stop_winbind(){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Stopping winbindd", basename(__FILE__));}
	system("/etc/init.d/artica-postfix stop winbindd");
}
function start_winbind(){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting winbindd", basename(__FILE__));}
	system("/etc/init.d/artica-postfix start winbindd");
}


function winbindfix(){
	winbind_priv(true);
	winbind_priv_perform();
	
}

function winbindd_set_acls_is_xattr(){
	$f=file("/proc/mounts");
	while (list ($num, $ligne) = each ($f) ){
	if(preg_match("#^(.*)\s+\/\s+(.*?)\s+.*?,acl.*?\s+([0-9]+)#",$ligne,$re)){
		echo "starting......: winbindd_priv main partition is already mounted with extended acls\n";
		return true;
		}
	}
	
	return false;
}

function winbindd_set_acls_mainpart(){
	if(winbindd_set_acls_is_xattr()){return;}
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	$mount=$unix->find_program("mount");
	if(!is_file($setfacl)){
		xsyslog("starting......: winbindd_priv setfacl no such binary");
		return;
	}
	
	
	if(!is_file($mount)){
		xsyslog("starting......: winbindd_priv mount no such binary");
		return;
	}
	
	
	$mustchange=false;
	$f=file("/etc/fstab");
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^(.*)\s+\/\s+(.*?)\s+(.*?)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$options=explode(",",$re[3]);
			while (list ($a, $b) = each ($options) ){
				$b=trim(strtolower($b));
				if($b==null){continue;}
				echo "starting......: winbindd_priv found main partition {$re[1]} with option `$b`\n";
				$MAINOPTIONS[trim($b)]=true;
			}
			if(!isset($MAINOPTIONS["acl"])){$mustchange=true;$options[]="acl";$options[]="user_xattr";}
			if(!$mustchange){
				echo "starting......: winbindd_priv found main partition {$re[1]} ACL user_xattr,acl\n";
			}else{
				echo "starting......: winbindd_priv found main partition {$re[1]} Add ACL user_xattr options was ". @implode(";", $options)."\n";
				$f[$num]="{$re[1]}\t/\t{$re[2]}\t".@implode(",", $options)."\t{$re[4]}\t{$re[5]}";
				reset($f);
				while (list ($c, $d) = each ($f) ){if(trim($d)==null){continue;}$cc[]=$d;}
				if(count($cc)>1){
					@file_put_contents("/etc/fstab", @implode("\n", $cc));
					xsyslog("starting......: winbindd_priv remount system partition...");
					shell_exec("$mount -o remount /");
				}
			}
		}
	}
}


function winbindd_monit(){
	  if(is_file("/etc/monit/conf.d/winbindd.monitrc")){echo "starting......: winbindd monit: Already set\n";return;}
	   $unix=new unix();
	   $monit=$unix->find_program("monit");
	   if(!is_file($monit)){
	   	xsyslog("starting......: winbindd monit: no such binary");
	   	return;
	   }
	   
	   $nohup=$unix->find_program("nohup");
	   
 	   $fs[]="#!/bin/sh";
	   $fs[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $fs[]="/etc/init.d/artica-postfix start winbindd";
	   $fs[]="exit 0\n";	
	   
	   $fk[]="#!/bin/sh";
	   $fk[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $fk[]="/etc/init.d/artica-postfix stop winbindd";
	   $fk[]="exit 0\n";	   
	
		@file_put_contents("/usr/sbin/winbindd-monit-start", @implode("\n", $fs));
		@file_put_contents("/usr/sbin/winbindd-monit-stop", @implode("\n", $fs));
		@chmod("/usr/sbin/winbindd-monit-start",0777);
		@chmod("/usr/sbin/winbindd-monit-stop",0777);
	
		$fm[]="check process winbindd";
		$fm[]="with pidfile /var/run/samba/winbindd.pid";
		$fm[]="start program = \"/usr/sbin/winbindd-monit-start\"";
		$fm[]="stop program =  \"/usr/sbin/winbindd-monit-stop\"";
		$fm[]="if totalmem > 900 MB for 5 cycles then alert";
		$fm[]="if cpu > 95% for 5 cycles then alert";
		$fm[]="if 5 restarts within 5 cycles then timeout";	
		echo "starting......: winbindd monit: creating winbindd.monitrc\n";
		@file_put_contents("/etc/monit/conf.d/winbindd.monitrc", @implode("\n", $fm));
		echo "starting......: winbindd monit: restarting monit\n";
		shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --monit-check >/dev/null 2>&1 &");
}


function checkParams(){
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if($array["WINDOWS_DNS_SUFFIX"]==null){return false;}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){return false;}
	if($array["WINDOWS_SERVER_TYPE"]==null){return false;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){return false;}
	if($array["WINDOWS_SERVER_PASS"]==null){return false;}
	
	
	
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){return false;}
	return true;
}

function xsyslog($text){
	echo $text."\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}
	
	
}