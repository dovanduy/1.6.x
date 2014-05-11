<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.kerb.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");
$GLOBALS["CHECKS"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["JUST_PING"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["WRITEPROGRESS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--checks#",implode(" ",$argv))){$GLOBALS["CHECKS"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["EXEC_PID_FILE"]="/etc/artica-postfix/".basename(__FILE__).".pid";

$unix=new unix();
$sock=new sockets();

if(isset($argv[1])){
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	if($DisableWinbindd==1){progress_logs(100,"{join_activedirectory_domain}","You have defined that the Winbindd daemon is disabled, this feature cannot be used");die();}
}

if($argv[1]=='--fstab'){winbindisacl();die();}

if($argv[1]=="--klist"){ping_klist();die();}
if($argv[1]=='--winbinddpriv'){winbind_priv_perform(true);die();}

if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Executing with `{$argv[1]}` command...", basename(__FILE__));}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--ping"){$GLOBALS["JUST_PING"]=true;ping_kdc();die();}
if($argv[1]=="--samba-proxy"){SAMBA_PROXY();die();}
if($argv[1]=='--winbindfix'){winbindfix();die();}


if($argv[1]=='--winbindacls'){winbindd_set_acls_mainpart();die();}
if($argv[1]=='--winbinddmonit'){winbindd_monit();die();}
if($argv[1]=='--join'){JOIN_ACTIVEDIRECTORY();die();}
if($argv[1]=='--samba-ver'){SAMBA_VERSION_DEBUG();die();}
if($argv[1]=='--refresh-ticket'){refresh_ticket();die();}
if($argv[1]=='--disconnect'){disconnect();exit;}
if($argv[1]=='--ntpdate'){sync_time(true);exit;}
if($argv[1]=='--resolv'){PatchResolvConf($argv[2]);exit;}
if($argv[1]=='--build-progress'){$GLOBALS["WRITEPROGRESS"]=true;build_progress();exit;}
if($argv[1]=='--users'){GetUsersNumber();exit;}




unset($argv[0]);
progress_logs(100,"{join_activedirectory_domain}","Unable to understand ".@implode(" ", $argv)."");


function refresh_ticket($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($oldpid));
		writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec>5){
			$kill=$unix->find_program("kill");
			system_admin_events("killing old pid $oldpid (already exists since {$timeExec}Mn)",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$kill -9 $oldpid >/dev/null");
		}else{
			return;
		}
	}
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	if(!is_numeric($EnableKerberosAuthentication)){$EnableKerberosAuthentication=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}			
	if($EnableKerbAuth==0){system_admin_events("EnableKerbAuth is disabled, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	if(!$users->SAMBA_INSTALLED){system_admin_events("Samba is not installed, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$net=$unix->find_program("net");
	$msktutil=$unix->find_program("msktutil");
	$cmdline="$net rpc changetrustpw -d 3 2>&1";
	exec($cmdline,$results);
	if(!is_file($msktutil)){
		$results[]="msktutil no such binary...";
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		system_admin_events("Update kerberos done took:$took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
		
	}
	
	$myNetBiosName=$unix->hostname_simple();
	$msktutil_version=msktutil_version();
	if($msktutil==3){
		$cmd="$msktutil --update --verbose --computer-name $myNetBiosName";
	}
	if($msktutil==4){
		$cmd="$msktutil --auto-update --verbose --computer-name $myNetBiosName";
	}
	$results[]=$cmd;
	exec($cmd." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Update kerberos done took:$took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);	
}


function build_kerberos($progress=0){
	$unix=new unix();
	$sock=new sockets();
	$function=__FUNCTION__;
	if(is_file("/etc/monit/conf.d/winbindd.monitrc")){@unlink("/etc/monit/conf.d/winbindd.monitrc");}
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($oldpid));
		writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec>5){
			$kill=$unix->find_program("kill");
			progress_logs($progress,"{kerberaus_authentication}","killing old pid $oldpid (already exists since {$timeExec}Mn)");
			shell_exec("$kill -9 $oldpid >/dev/null");
		}else{
			return;
		}
	}
	$time=$unix->file_time_min($timefile);
	
	if($time<2){progress_logs($progress,"{kerberaus_authentication}","2mn minimal to run this script currently ({$time}Mn)");}
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	writelogs("Running PID $mypid",__FUNCTION__,__FILE__,__LINE__);
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ipaddr=trim($array["ADNETIPADDR"]);
	
	sync_time($progress);
	krb5conf($progress);
	progress_logs($progress,"{kerberaus_authentication}","run_msktutils() -> run in line ".__LINE__);
	run_msktutils($progress);
	
}

function sync_time($aspid=false){
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$unix=new unix();
	
	$sock=new sockets();	
	$function=__FUNCTION__;
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timeExec=intval($unix->PROCCESS_TIME_MIN($oldpid));
			writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
			if($timeExec>5){
				$kill=$unix->find_program("kill");
				system_admin_events("killing old pid $oldpid (already exists since {$timeExec}Mn)",__FUNCTION__,__FILE__,__LINE__);
				shell_exec("$kill -9 $oldpid >/dev/null");
			}else{
				return;
			}
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ipaddr=trim($array["ADNETIPADDR"]);	
	$ntpdate=$unix->find_program("ntpdate");
	$hwclock=$unix->find_program("hwclock");
	
	
	if(!is_file($ntpdate)){progress_logs(20,"{sync_time}","$function, ntpdate no such binary Line:".__LINE__."");return;}
	progress_logs(20,"{sync_time}","$function, sync the time with the Active Directory $hostname [$ipaddr]...");
	if($ipaddr<>null){$cmd="$ntpdate -u $ipaddr";}else{$cmd="$ntpdate -u $hostname";}
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{sync_time}","$cmd line:".__LINE__."");}
	exec($cmd." 2>&1",$results);
	while (list ($num, $a) = each ($results) ){progress_logs(20,"{sync_time}","$function, $a Line:".__LINE__."");}
	if(is_file($hwclock)){
		progress_logs(20,"{sync_time}","$function, sync the Hardware time with $hwclock");
		shell_exec("$hwclock --systohc");
	}

	$GLOBALS[__FUNCTION__]=true;
}


function krb5conf($progress=0){
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$kdb5_util=$unix->find_program("kdb5_util");
	$kadmin_bin=$unix->find_program("kadmin");	
	$php5=$unix->LOCATE_PHP5_BIN();	
	$function=__FUNCTION__;
	$chmod=$unix->find_program("chmod");
	if(!checkParams()){progress_logs(10,"{kerberaus_authentication}","$function, misconfiguration failed");return;}
	$msktutil=check_msktutil();
	if(!is_file($msktutil)){return;}
	@chmod($msktutil,0755);
	$uname=posix_uname();
	$mydomain=$uname["domainname"];
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$enctype=null;
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	if(!is_numeric($EnableKerberosAuthentication)){$EnableKerberosAuthentication=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}		
	if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
	if($array["WINDOWS_SERVER_TYPE"]==null){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}	
	
	progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type `{$array["WINDOWS_SERVER_TYPE"]}`");
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	
	$workgroup=$array["ADNETBIOSDOMAIN"];

	
	progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory hostname `$hostname`");
	progress_logs($progress,"{kerberaus_authentication}","$function, my domain: \"$mydomain\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my hostname: \"$myFullHostname\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my netbiosname: \"$myNetBiosName\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my workgroup: \"$workgroup\"");		
	
	
	
	
	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2003"){
		progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type Windows 2003, adding default_tgs_enctypes");
		$t[]="# For Windows 2003:";
		$t[]=" default_tgs_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		
	}

	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type Windows 2008, adding default_tgs_enctypes");
		$t[]="; for Windows 2008 with AES";
		$t[]=" default_tgs_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		$enctype=" --enctypes 28";
		
	}
	
	$dns_lookup_realm="no";
	$dns_lookup_kdc="no";
	$default_keytab_name=null;
	$default_realm=$domainUp;
	$realms=$domainUp;
	$default_domain=$domainUp;
	$default_keytab_name="/etc/krb5.keytab";
	
	if($EnableKerberosAuthentication==1){
		$dns_lookup_realm="no";
		$dns_lookup_kdc="no";
		
	}
	//allow_weak_crypto = true ?? -> 
	$hostname_up=strtoupper($hostname);
	$f[]=" [logging]";
	$f[]=" default = FILE:/var/log/krb5libs.log";
	$f[]=" kdc = FILE:/var/log/krb5kdc.log";
	$f[]=" admin_server = FILE:/var/log/kadmind.log";
	$f[]="";
	$f[]="[libdefaults]";
	$f[]=" default_realm = $default_realm";
	$f[]=" dns_lookup_realm = $dns_lookup_realm";
	$f[]=" dns_lookup_kdc = $dns_lookup_kdc";
	$f[]=" allow_weak_crypto = true";
	$f[]=" ticket_lifetime = 24h";
	$f[]=" forwardable = yes";
	if($default_keytab_name<>null){$f[]="default_keytab_name = $default_keytab_name";}
	$f[]="";
	progress_logs($progress,"{kerberaus_authentication}","$function, ". count($t)." lines for default_tgs_enctypes");
	if( count($t)>0){
		$f[]=@implode("\n", $t);
	}
	$f[]="[realms]";
	$f[]=" $realms = {";
	$f[]="  kdc = $hostname";
	$f[]="  admin_server = $hostname";
	if($default_domain<>null){$f[]="  default_domain = $domainUp";}
	$f[]=" }";
	$f[]="";
	$f[]="[domain_realm]";
	$f[]=" .$domaindow = $domainUp";
	$f[]=" $domaindow = $domainUp";
	
	$f[]="";
	if($EnableKerberosAuthentication==0){
		$f[]="[appdefaults]";
		$f[]=" pam = {";
		$f[]="   debug = false";
		$f[]="   ticket_lifetime = 36000";
		$f[]="   renew_lifetime = 36000";
		$f[]="   forwardable = true";
		$f[]="   krb4_convert = false";
		$f[]="}";
		$f[]="";
	}	
	@file_put_contents("/etc/krb.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/krb.conf done");
	@file_put_contents("/etc/krb5.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/krb5.conf done");	
	unset($f);
	$f[]="lhs=.ns";
	$f[]="rhs=.$mydomain";
	$f[]="classes=IN,HS";
	@file_put_contents("/etc/hesiod.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/hesiod.conf done");


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
	progress_logs($progress,"{kerberaus_authentication}","$function, /usr/share/krb5-kdc/kdc.conf done");
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/kdc.conf done Line:".__LINE__."");
	
	unset($f);

	$config="*/admin *\n";
	@file_put_contents("/etc/kadm.acl"," ");
	@file_put_contents("/usr/share/krb5-kdc/kadm.acl"," ");
	@file_put_contents("/etc/krb5kdc/kadm5.acl"," ");
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/kadm.acl done");	
	$progress=$progress+1;
	
	progress_logs($progress,"{kerberaus_authentication}","Running Linit() from line:".__LINE__);
	RunKinit("{$array["WINDOWS_SERVER_ADMIN"]}@$domainUp",$array["WINDOWS_SERVER_PASS"],$progress);
	progress_logs($progress,"{kerberaus_authentication}",__FUNCTION__."() done from line:".__LINE__);
}

function check_msktutil(){
	$unix=new unix();
	$msktutil=$unix->find_program("msktutil");
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$function=__FUNCTION__;
	if(is_file($msktutil)){return $msktutil;}
	progress_logs(20,"{join_activedirectory_domain}","Kerberos, msktutil no such binary");
	if(is_file("/home/artica/mskutils.tar.gz.old")){
		progress_logs(20,"{join_activedirectory_domain}","$function, msktutil /home/artica/mskutils.tar.gz.old found, install it");
		shell_exec("$tar -xf /home/artica/mskutils.tar.gz.old -C /");
	}

	$msktutil=$unix->find_program("msktutil");	
	if(is_file($msktutil)){return $msktutil;}	
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_MSKTUTIL >/dev/null 2>&1 &");
	progress_logs(20,"{join_activedirectory_domain}","$function, msktutil no such binary");
	
}

function msktutil_version(){
	$unix=new unix();
	$msktutil=$unix->find_program("msktutil");	
	$t=exec("$msktutil --version 2>&1");
	if(preg_match("#msktutil version\s+([0-9\.]+)#", $t,$re)){
		$tr=explode(".", $re[1]);
		return $tr[1];
	}
}

function resolve_kdc(){
	$unix=new unix();
	$sock=new sockets();
	$function=__FUNCTION__;
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$ipaddr=trim($array["ADNETIPADDR"]);
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	if($ipaddr==null){		
		progress_logs(20,"{join_activedirectory_domain}","$function, KDC $hostname no ip address set, aborting checking function");
		return;
	}

	$newip=gethostbyname($hostname);
	progress_logs(20,"{join_activedirectory_domain}","$function, KDC $hostname [$ipaddr] resolved to: $newip");
	
	
	
}



function run_msktutils(){
	$unix=new unix();
	$sock=new sockets();
	
	if(is_file("/usr/sbin/msktutil")){@chmod("/usr/sbin/msktutil",0755);}
	$msktutil=$unix->find_program("msktutil");
	$function=__FUNCTION__;
	
	
	if(!is_file($msktutil)){
		if(is_file("/home/artica/mskutils.tar.gz.old")){
			progress_logs(20,"{join_activedirectory_domain}","$function, uncompress /home/artica/mskutils.tar.gz.old");
			shell_exec("tar xf /home/artica/mskutils.tar.gz.old -C /");
		}
	}
	
	$msktutil=$unix->find_program("msktutil");
	if(!is_file($msktutil)){	
		progress_logs(20,"{join_activedirectory_domain}","$function, msktutil not installed, you should use it..");
		return;
	}
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$ipaddr=trim($array["ADNETIPADDR"]);
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
	progress_logs(20,"{join_activedirectory_domain}","$function, computers branch `{$array["COMPUTER_BRANCH"]}`");
	progress_logs(20,"{join_activedirectory_domain}","$function, my full hostname `$myFullHostname`");
	progress_logs(20,"{join_activedirectory_domain}","$function, my netbios name `$myNetBiosName`");
	progress_logs(20,"{join_activedirectory_domain}","$function, Active Directory hostname `$hostname` ($ipaddr)");
	$kdestroy=$unix->find_program("kdestroy");
	
	$domain_controller=$hostname;
	if($ipaddr<>null){$domain_controller=$ipaddr;}
	
	$enctypes=null;
	if( $array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		$enctypes=" --enctypes 28";
	}
	$msktutil_version=msktutil_version();
	progress_logs(20,"{join_activedirectory_domain}","$function, msktutil version 0.$msktutil_version");
	
	$f[]="$msktutil -c -b \"{$array["COMPUTER_BRANCH"]}\"";
	$f[]="-s HTTP/$myFullHostname -h $myFullHostname -k /etc/krb5.keytab";
	$f[]="--computer-name $myNetBiosName --upn HTTP/$myFullHostname --server $domain_controller $enctypes";
	$f[]="--verbose";
	if($msktutil_version==4){
		//$f[]="--user-creds-only";
	}
	
	
	$cmdline=@implode(" ", $f);
	progress_logs(20,"{join_activedirectory_domain}","$function,`$cmdline`");
	exec("$cmdline 2>&1",$results);
	while (list ($num, $a) = each ($results) ){if(trim($a)==null){continue;}progress_logs(20,"{join_activedirectory_domain}","$function, $a Line:".__LINE__."");}
	
	if($msktutil_version==4){
		$cmdline="$msktutil --auto-update --verbose --computer-name $myNetBiosName --server $domain_controller";
		exec("$cmdline 2>&1",$results);
		while (list ($num, $a) = each ($results) ){if(trim($a)==null){continue;}progress_logs(20,"{join_activedirectory_domain}","$function, $a Line:".__LINE__."");}
	}
			
	
	
}

function progress_logs($percent,$title,$log,$line=0){
	
	$date=date("H:i:s");
	if(!isset($GLOBALS["LAST_PROGRESS"])){$GLOBALS["LAST_PROGRESS"]=0;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		$function="MAIN";
		$line=0;
		if(isset($trace[1]["file"])){
			$filename=basename($trace[1]["file"]);
		}
		if(isset($trace[1]["function"])){
			$function="{$trace[1]["function"]}()";
		}
		if(isset($trace[1]["line"])){
			
			if($line==0){
				$function=$function." line {$trace[1]["line"]}";
			}
		}
	}
	$log="{$percent}%  $date : $log $function";
	if($GLOBALS["VERBOSE"]){echo "$log\n";}
	if(!$GLOBALS["WRITEPROGRESS"]){return;}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/AdConnnection.status";
	if($percent==-1){@unlink($cachefile);return;}
	
	if($GLOBALS["LAST_PROGRESS"]==$percent){
		$GLOBALS["LOGS"][]=$log;
		return;
		
	}
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/AdConnnection.status"));
	$array["PRC"]=$percent;
	$array["TITLE"]=$title;
	if(count($GLOBALS["LOGS"])>0){
		while (list ($num, $a) = each ($GLOBALS["LOGS"]) ){
			$array["LOGS"][]=$a;
		}
		$GLOBALS["LOGS"]=array();
	}
	
	$array["LOGS"][]=$log;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile, 0755);
	$GLOBALS["LAST_PROGRESS"]=$percent;
	
}


function build_progress(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($oldpid));
		if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","Process $oldpid already exists since {$timeExec}Mn");}
		writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec<5){return;}
		$kill=$unix->find_program("kill");
		progress_logs(20,"{join_activedirectory_domain}","build_progress, killing old pid $oldpid (already exists since {$timeExec}Mn)");
		shell_exec("$kill -9 $oldpid >/dev/null");
	}
		
	progress_logs(1);
	build();
	progress_logs(66,"NssSwitch","Building nsswitch.... on line ".__LINE__);
	exec("/usr/share/artica-postfix/bin/artica-install --nsswitch --verbose  2>&1",$results2);
	while (list ($index, $line) = each ($results2) ){
		progress_logs(67,"NssSwitch",$line);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($unix->LOCATE_SQUID_BIN())){
		progress_logs(68,"Reconfiguring Squid-cache","...");
		$results2=array();
		exec("$php /usr/share/artica-postfix/exec.squid.php --build --force 2>&1",$results2);
		while (list ($index, $line) = each ($results2) ){
			progress_logs(68,"Reconfiguring Squid-cache",$line);
		}
		$results2=array();
		progress_logs(69,"Restarting Squid-cache","...");
		exec("/etc/init.d/squid restart --force 2>&1",$results2);
		while (list ($index, $line) = each ($results2) ){
			progress_logs(69,"Restarting Squid-cache",$line);
		}
		
	}
	
	progress_logs(70,"{please_wait_restarting_artica_status}","...");
	exec("/etc/init.d/artica-status restart --force 2>&1",$results2);
	while (list ($index, $line) = each ($results2) ){
		progress_logs(75,"{please_wait_restarting_artica_status}",$line);
	}
	
	progress_logs(100,"{completed}","...");
}


function build($nopid=false){
	if(isset($GLOBALS["BUILD_EXECUTED"])){
		progress_logs(20,"{continue}","Already executed");
		return;
	}
	$GLOBALS["BUILD_EXECUTED"]=true;
	$unix=new unix();
	$sock=new sockets();
	$function=__FUNCTION__;
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerberosAuthentication==1){
		progress_logs(100,"{finish}","Kerberos, disabled");
		progress_logs(20,"{join_activedirectory_domain}","Kerberos, disabled");
		build_kerberos(20);
		return;}
	if($EnableKerbAuth==0){
		progress_logs(100,"{finish}","Auth Winbindd, disabled");
		progress_logs(20,"{join_activedirectory_domain}","Auth Winbindd, disabled");
		if(is_file("/etc/monit/conf.d/winbindd.monitrc")){@unlink("/etc/monit/conf.d/winbindd.monitrc");}
		return;
	}
	
	if(!$nopid){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timeExec=intval($unix->PROCCESS_TIME_MIN($oldpid));
			if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","Process $oldpid already exists since {$timeExec}Mn");}
			writelogs("Process $oldpid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
			if($timeExec>5){
				$kill=$unix->find_program("kill");
				progress_logs(20,"{join_activedirectory_domain}","killing old pid $oldpid (already exists since {$timeExec}Mn)");
				shell_exec("$kill -9 $oldpid >/dev/null");
			}else{
				return;
			}
		}
		
		
		$time=$unix->file_time_min($timefile);
		if($time<2){
			if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","2mn minimal to run this script currently ({$time}Mn)");}
			writelogs("2mn minimal to run this script currently ({$time}Mn)",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	progress_logs(20,"{join_activedirectory_domain}","Running PID $mypid",__LINE__);
	writelogs("Running PID $mypid",__FUNCTION__,__FILE__,__LINE__);
	
	$wbinfo=$unix->find_program("wbinfo");
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$ntpdate=$unix->find_program("ntpdate");
	

	
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file($wbinfo)){
		shell_exec("$php5 /usr/share/artica-postfix exec.apt-get.php --sources-list");
		shell_exec("$nohup /usr/share/artica-postfix/bin/setup-ubuntu --check-samba >/dev/null 2>&1 &");
		$wbinfo=$unix->find_program("wbinfo");
		
	}
	if(!is_file($wbinfo)){
			progress_logs(20,"{join_activedirectory_domain}","Auth Winbindd, samba is not installed");
			progress_logs(100,"{finish}","Auth Winbindd, samba is not installed");
			return;
	}
	
	if(!checkParams()){
		progress_logs(20,"{join_activedirectory_domain}","Auth Winbindd, misconfiguration failed");
		progress_logs(100,"{finish}","Auth Winbindd, misconfiguration failed");
		return;
	}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$msktutil=check_msktutil();
	$kdb5_util=$unix->find_program("kdb5_util");
	$kadmin_bin=$unix->find_program("kadmin");
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	if(!is_file($msktutil)){return;}
	@mkdir("/var/log/samba",0755,true);
	@mkdir("/var/run/samba",0755,true);
	$uname=posix_uname();
	$mydomain=$uname["domainname"];
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$enctype=null;
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	$ipaddr=trim($array["ADNETIPADDR"]);
	
	$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
	if(!is_numeric($UseADAsNameServer)){$UseADAsNameServer=0;}
	if($UseADAsNameServer==1){
		if(preg_match("#[0-9\.]+#", $ipaddr)){
			progress_logs(8,"{apply_settings}","Patching Resolv.conf");
			PatchResolvConf($ipaddr);
		}
	
	}
	
	
	if($ipaddr<>null){
		$ipaddrZ=explode(".",$ipaddr);
		while (list ($num, $a) = each ($ipaddrZ) ){
			$ipaddrZ[$num]=intval($a);
		}
		$ipaddr=@implode(".", $ipaddrZ);
	}
	
	progress_logs(9,"{apply_settings}","Synchronize time"." in line ".__LINE__);
	sync_time();
	progress_logs(10,"{apply_settings}","Check kerb5..in line ".__LINE__);
	krb5conf(12);
	progress_logs(15,"{apply_settings}","Check msktutils in line ".__LINE__);
	run_msktutils();
	progress_logs(15,"{apply_settings}","netbin -> $netbin in line ".__LINE__);
	if(is_file($netbin)){
		try {
		progress_logs(15,"{apply_settings}","netbin -> SAMBA_PROXY()  in line ".__LINE__);
		SAMBA_PROXY();
		} catch (Exception $e) {
			progress_logs(15,"{failed}","Exception Error: Message: " .$e->getMessage());
		}
	}

	progress_logs(20,"{apply_settings}","Next");
	
	progress_logs(20,"{apply_settings}","kdb5_util -> $kdb5_util");
	if(is_file("$kdb5_util")){
		$cmd="$kdb5_util create -r $domainUp -s -P $kinitpassword";
		progress_logs(20,"{apply_settings}","$cmd");
		$results=array();
		exec($cmd,$results);
		while (list ($num, $a) = each ($results) ){progress_logs(15,"kdb5_util, $a");}
	}
	
	progress_logs(20,"kadmin_bin -> $kadmin_bin");
	progress_logs(20,"netbin -> $netbin ");
	if(is_file("$netbin")){
		progress_logs(20,"{join_activedirectory_domain}","netbin -> JOIN_ACTIVEDIRECTORY() ");
		JOIN_ACTIVEDIRECTORY();
	
	}
	progress_logs(51,"{restarting_winbind}","winbind_priv();");
	winbind_priv(false,52);
	progress_logs(60,"{restarting_winbind}","winbind_priv();");
	winbindd_monit();
	progress_logs(65,"{restarting_winbind}","winbind_priv();");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if(!is_file("/etc/init.d/winbind")){
		shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --winbind");
	}
	progress_logs(65,"{restarting_winbind}","winbind_priv();");
	shell_exec("/etc/init.d/winbind restart --force");



}

function winbindd_version(){
	$unix=new unix();
	$winbindd=$unix->find_program("winbindd");
	if(!is_file($winbindd)){return;}
	exec("$winbindd -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#", @implode("", $results),$re)){
		return $re[1];
	}
}

function PatchResolvConf($ipaddr){
	$function=__FUNCTION__;
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#^nameserver\s+([0-9\.]+)#i", $line,$re)){
			if($re[1]==$ipaddr){
				progress_logs(29,"{join_activedirectory_domain}"," DNS $ipaddr already set");
				return;
			}
		}
		
	}
	progress_logs(29,"{join_activedirectory_domain}"," SET DNS $ipaddr as primary DNS server");
	$newdata="nameserver $ipaddr\n".@implode("\n", $f);
	@file_put_contents("/etc/resolv.conf", $newdata);
	
}



function JOIN_ACTIVEDIRECTORY(){
	$unix=new unix();	
	$user=new settings_inc();
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$function=__FUNCTION__;
	if(!is_file($netbin)){progress_logs(29,"{join_activedirectory_domain}","net, no such binary");return;}
	if(!$user->SAMBA_INSTALLED){progress_logs(29,"{join_activedirectory_domain}"," Samba, no such software");return;}
	
	
	
	
	$winbindd_version=winbindd_version();
	progress_logs(29,"{join_activedirectory_domain}"," Version $winbindd_version");
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $winbindd_version,$re)){
		progress_logs(29,"{join_activedirectory_domain}"," Major:{$re[1]}, minor:{$re[2]}");
		$MAJOR=$re[1];
		$MINOR=$re[2];
	}
	
	if(is_file("/home/artica/packages/samba.tar.gz.old")){
		progress_logs(29,"{join_activedirectory_domain}"," This is a proxy appliance...");
		if($MAJOR>2){
			if($MINOR<6){
				squid_admin_mysql(0, "NTLM: Bad samba version - $winbindd_version -, was updated by the system, return back", $buffer,__FILE__,__LINE__);
				progress_logs(29,"{join_activedirectory_domain}"," Bad samba version, was updated by the system, return back");
				shell_exec("$tar -xvf /home/artica/packages/samba.tar.gz.old -C /");
				$winbindd_version=winbindd_version();
				progress_logs(29,"{join_activedirectory_domain}"," Version is now `$winbindd_version`");
			}
		}
	}
	
	
	$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
	$KDC_SERVER=$NetADSINFOS["KDC server"];
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
	$workgroup=$array["ADNETBIOSDOMAIN"];
	$ipaddr=trim($array["ADNETIPADDR"]);
	$myNetbiosname=$unix->hostname_simple();
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Trying to relink this server with Active Directory $ad_server.$domain_lower server", basename(__FILE__));}
	$A2=array();
	$JOINEDRES=false;
	@unlink("/etc/squid3/NET_ADS_INFOS");
	
	$buffer="$adminname@$domain_lower\nWorkgroup: $workgroup\nAD: $ad_server ($ipaddr)";
	
	squid_admin_mysql(1, "NTLM: Trying to relink this server with Active Directory $ad_server.$domain_lower server", $buffer,__FILE__,__LINE__);
	
	
	if(!is_file("/etc/artica-postfix/PyAuthenNTLM2_launched")){
		shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_PYAUTHENNTLM >/dev/null 2>&1 &");
		@file_put_contents("/etc/artica-postfix/PyAuthenNTLM2_launched", time());
	}
	
	progress_logs(29,"{join_activedirectory_domain}"," Administrator `$adminname`");
	progress_logs(29,"{join_activedirectory_domain}"," Workgroup `$workgroup`");
	progress_logs(29,"{join_activedirectory_domain}"," Active Directory `$ad_server` ($ipaddr)");	
	progress_logs(29,"{join_activedirectory_domain}"," [$adminname 0]: join as netrpc.. (without IP addr and without domain)");	
	
	$cmd="$netbin rpc join -U $adminname%$adminpassword 2>&1";
	exec($cmd,$A2);
	if($GLOBALS["VERBOSE"]){progress_logs(30,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");}
	while (list ($index, $line) = each ($A2) ){
		if(preg_match("#Joined#", $line)){
			progress_logs(31,"{join_activedirectory_domain}"," $function, [$adminname]: join for $workgroup (without IP addr and without domain) success");
			$JOINEDRES=true;
			progress_logs(32,"{join_activedirectory_domain}"," $function, [$adminname]: join for $workgroup with ads method");
			$cmd="$netbin ads join -U $adminname%$adminpassword 2>&1";
			if($GLOBALS["VERBOSE"]){SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$cmd);}
			exec("$cmd",$A3);
			while (list ($index, $line) = each ($A3) ){SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line);}
			$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
			$KDC_SERVER=$NetADSINFOS["KDC server"];
			if($KDC_SERVER==null){
					progress_logs(20,"{join_activedirectory_domain}"," $function, [$adminname]: unable to join the domain $domain_lower (KDC server is null)");
				}
			break;
		}
		if(preg_match("#Unable to find a suitable server for domain#i", $line)){
			progress_logs(33,"{join_activedirectory_domain}"," [$adminname 0]: **** FATAL ****");
			progress_logs(34,"{join_activedirectory_domain}"," [$adminname 0]: $line");
			progress_logs(35,"{join_activedirectory_domain}"," [$adminname 0]: ***************");
			continue;
		}
		
		progress_logs(36,"{join_activedirectory_domain}"," [$adminname 0]: $line");
			
	}
	
	$ipcmdline=null;
	
	if(!$JOINEDRES){
		progress_logs(37,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Try to connect in ads mode with $ad_server.$domain_lower");
		if($ipaddr<>null){
			progress_logs(37,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Try to connect in ads mode with ip address $ipaddr too");
			$ipcmdline=" -I $ipaddr ";
		}
		$cmd="$netbin ads join -S $ad_server.$domain_lower{$ipcmdline} -U $adminname%$adminpassword 2>&1";
		$cmdOutput=$cmd;
		progress_logs(38,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmdOutput");
		$results=array();
		exec("$cmd",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#Joined#", $line)){
				progress_logs(39,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup in ads mode with $ad_server.$domain_lower success");
				$cmd="$netbin rpc join -S $ad_server.$domain_lower{$ipcmdline} -U $adminname%$adminpassword 2>&1";
				$cmdOutput=$cmd;
				progress_logs(40,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmdOutput");
				exec("$cmd",$results2);
				while (list ($index, $line) = each ($results2) ){progress_logs(41,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]:$line");}
				$JOINEDRES=true;
				break;
			}
			
			SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line);
		}
	}	
	
	
	
	

if(!$JOINEDRES){
		progress_logs(42,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Kdc server ads = `$KDC_SERVER`");
		if($KDC_SERVER==null){
			$cmd="$netbin ads join -W $ad_server.$domain_lower -S $ad_server -U $adminname%$adminpassword 2>&1";
			if($GLOBALS["VERBOSE"]){progress_logs(42,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");}
			exec("$cmd",$results);
			while (list ($index, $line) = each ($results) ){SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line);}	
			$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
			$KDC_SERVER=$NetADSINFOS["KDC server"];
		}
		
		if($KDC_SERVER==null){progress_logs(43,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: unable to join the domain $domain_lower (KDC server is null)");}
	
		
	
		
	progress_logs(44,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: setauthuser..");
	$cmd="$netbin setauthuser -U $adminname%$adminpassword 2>&1";	
	if($GLOBALS["VERBOSE"]){progress_logs(44,"{join_activedirectory_domain}"," $function, $cmd");}
	$results=array();
	exec("$cmd",$results);
	while (list ($index, $line) = each ($results) ){progress_logs(44,"{join_activedirectory_domain}","$line");}
		
	if($ipaddr==null){
		$JOINEDRES=false;
		progress_logs(45,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 0]: join for $workgroup (without IP addr)");	
		if($GLOBALS["VERBOSE"]){progress_logs(46,"{join_activedirectory_domain}"," $function, $cmd");}
		$cmd="$netbin join -U $adminname%$adminpassword $workgroup 2>&1";
		exec($cmd,$A1);
		while (list ($index, $line) = each ($A1) ){
			if(preg_match("#Joined#", $line)){
				progress_logs(46,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup (without IP addr) success");
				$JOINEDRES=true;
				break;
			}
			SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line);
		}
		
		if(!$JOINEDRES){
			progress_logs(47,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 0]: join as netrpc.. (without IP addr)");	
			$cmd="$netbin rpc join -U $adminname%$adminpassword $workgroup 2>&1";
			exec($cmd,$A2);
			if($GLOBALS["VERBOSE"]){progress_logs(47,"{join_activedirectory_domain}"," $function, $cmd");}
			while (list ($index, $line) = each ($A2) ){
				if(preg_match("#Joined#", $line)){
					progress_logs(47,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup (without IP addr) success");
					$JOINEDRES=true;
					break;
				}
				progress_logs(47,"{join_activedirectory_domain}",$line);
			}
		}
		
	}
	
	if($ipaddr<>null){
		progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: ads '$netbin ads join -I $ipaddr -U $adminname%**** $workgroup'");
		//$cmd="$netbin ads join -S $ad_server.$domain_lower -I $ipaddr -U $adminname%$adminpassword 2>&1";
		$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}"," Samba, $cmd");}
		exec($cmd,$BIGRES2);	
		while (list ($index, $line) = each ($BIGRES2) ){
			if(preg_match("#Failed to join#i", $line)){
				progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: ads join failed ($line), using pure IP");
				progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: '$netbin ads join -I $ipaddr -U $adminname%*** $workgroup'");
				$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
				if($GLOBALS["VERBOSE"]){progress_logs(48,"{join_activedirectory_domain}"," $function, $cmd line:". __LINE__."");}
				$BIGRESS=array();
				exec($cmd,$BIGRESS);
				
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword 2>&1";
					progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");
					exec($cmd,$BIGRESS);
				}
				
				if(count($BIGRESS)>0){
					while (list ($index, $line) = each ($BIGRESS) ){
						progress_logs(48,"{join_activedirectory_domain}",$line);
						if(preg_match("#(Failed to|Unable to|Error in)#i", $line)){$BIGRESS=array();}
					}
				}
				 
				
				
				
				if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], ".count($BIGRESS)." lines line:".__LINE__."");}
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin rpc join -I $ipaddr -U $adminname%$adminpassword 2>&1";
					if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd Line:".__LINE__."");}
					exec($cmd,$BIGRESS);
				}
				
				if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], ".count($BIGRESS)." lines line:".__LINE__."");}
				if(count($BIGRESS)>0){
					while (list ($index, $line) = each ($BIGRESS) ){
						progress_logs(49,"{join_activedirectory_domain}",$line);
						if(preg_match("#(Failed to|Unable to|Error in)#i", $line)){$BIGRESS=array();}
					}
				}			
				
				
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin rpc join -S $ad_server -n $myNetbiosname -U $adminname%$adminpassword 2>&1";
					progress_logs(50,"{join_activedirectory_domain}"," $cmd Line:".__LINE__."");
					exec($cmd,$BIGRESS);
				}			
				
				reset($BIGRESS);
				while (list ($index, $line) = each ($BIGRESS) ){
					progress_logs(49,"{join_activedirectory_domain}",$line);
				}
				
				break;
			}
			progress_logs(49,"{join_activedirectory_domain}","[$adminname 1] $line");
			
		}
		
		
		/*progress_logs(20,"{join_activedirectory_domain}"," Samba, [$adminname]: join with  IP Adrr:$ipaddr..");	
		$cmd="$netbin join -U $adminname%$adminpassword -I $ipaddr";
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}"," Samba, $cmd");}
		shell_exec($cmd);*/
	
	}
}
	if($KDC_SERVER==null){$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();$KDC_SERVER=$NetADSINFOS["KDC server"];}
	if($KDC_SERVER==null){
		squid_admin_mysql(0, "NTLM: unable to join the domain $domain_lower", $buffer,__FILE__,__LINE__);
		progress_logs(50,"{join_activedirectory_domain}"," Samba, [$adminname]: unable to join the domain $domain_lower");
	}else{
		squid_admin_mysql(2, "NTLM: Success to join the domain $domain_lower", "KDC = $KDC_SERVER",__FILE__,__LINE__);
	}	

	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Kdc server ads : $KDC_SERVER Create keytap...");
	
	unset($results);
	$cmd="$netbin ads keytab create -P -U $adminname%$adminpassword 2>&1";
	if($GLOBALS["VERBOSE"]){progress_logs(50,"{join_activedirectory_domain}"," $function, $cmd");}
	exec("$cmd",$results);
	while (list ($index, $line) = each ($results) ){progress_logs(50,"{join_activedirectory_domain}"," $function, keytab: [$adminname]: $line");}

	$nohup=$unix->find_program("nohup");
	$smbcontrol=$unix->find_program("smbcontrol");
	
	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: restarting Samba");
	$results=array();
	exec("/etc/init.d/artica-postfix start samba 2>&1",$results);
	while (list ($index, $line) = each ($results) ){progress_logs(50,"{join_activedirectory_domain}","$line");}
	
	
	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Reload Artica-status");
	$results=array();
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	if(is_file($smbcontrol)){
		progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Reloading winbindd");
		exec("$smbcontrol winbindd reload-config 2>&1",$results);
		exec("$smbcontrol winbindd online 2>&1",$results);
		while (list ($index, $line) = each ($results) ){progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: $line");}
	}
	$results=array();
	progress_logs(20,"{join_activedirectory_domain}","[$adminname]: checks nsswitch");
	exec("/usr/share/artica-postfix/bin/artica-install --nsswitch 2>&1",$results);
	while (list ($index, $line) = each ($results) ){progress_logs(50,"{join_activedirectory_domain}","[$adminname]: $line");}
	

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
	progress_logs(20,"{join_activedirectory_domain}","Samba Version (Winbind): $SAMBA_VERSION");
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){
		echo"Samba 3.6 OK\n";
	}
}


function SAMBA_PROXY(){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Reconfigure Samba for proxy commpliance", basename(__FILE__));}
	progress_logs(15,"SAMBA_SPECIFIC_PROXY() start... ");
	$IsAppliance=false;
	progress_logs(15,"users=new usersMenus(); ");
	$user=new settings_inc();
	$unix=new unix();
	$sock=new sockets();
	
	
	if(!$user->SAMBA_INSTALLED){progress_logs(16,"{APP_SAMBA}"," Samba, no such software");return;}
	if($user->SQUID_APPLIANCE){$IsAppliance=true;}
	if($user->KASPERSKY_WEB_APPLIANCE){$IsAppliance=true;}
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($user->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	if($EnableWebProxyStatsAppliance==1){$IsAppliance=true;}
	
	if(!$IsAppliance){progress_logs(16,"{APP_SAMBA}"," Samba,This is not a Proxy appliance, i leave untouched smb.conf");return;}
	progress_logs(16,"{APP_SAMBA}"," Samba, it is an appliance...");

	

	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
	if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}	
	
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	
	
	
	$workgroup=$array["ADNETBIOSDOMAIN"];
	$realm=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$ipaddr=trim($array["ADNETIPADDR"]);
	progress_logs(16,"{APP_SAMBA}"," Samba, [$adminname]: Kdc server ads : $ad_server workgroup `$workgroup` ipaddr:$ipaddr");	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$password_server=$hostname;
	//if($ipaddr<>null){$password_server=$ipaddr;}
	if(strpos($password_server, ".")>0){$aa=explode(".", $password_server);$password_server=$aa[0];}
	$SAMBA_VERSION=SAMBA_VERSION();
	$ipaddr=trim($array["ADNETIPADDR"]);
	if($ipaddr<>null){$password_server=$ipaddr;}
	
	$AS36=false;
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){$AS36=true;}
	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#", $SAMBA_VERSION,$re)){
		$MAJOR=intval($re[1]);
		$MINOR=intval($re[2]);
		$REV=intval($re[3]);
		progress_logs(17,"{APP_SAMBA}"," Samba, V$MAJOR $MINOR $REV");
		
	}
	
	
	$f[]="[global]";
	$smbkerb=new samba_kerb();
	$f[]=$smbkerb->buildPart();
	
	
	
	@file_put_contents("/etc/samba/smb.conf", @implode("\n", $f));
	progress_logs(18,"{APP_SAMBA}"," Samba, [$adminname]: SMB.CONF DONE, restarting services");
	$net=$unix->find_program("net");
	shell_exec("$net cache flush");
	shell_exec("$net cache stabilize");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --nsswitch");
	$smbcontrol=$unix->find_program("smbcontrol");
	if(!is_file($smbcontrol)){
		progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Restarting Samba...");
		shell_exec("/etc/init.d/artica-postfix restart samba");
	}else{
		progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Reloading Samba...");
		shell_exec("$smbcontrol smbd reload-config");
	}
	
	progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Restarting Winbind...");
	shell_exec("/etc/init.d/winbind stop");
	shell_exec("/etc/init.d/winbind start");
	
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.ad.import.php --by=". basename(__FILE__)." &");
	
}

function ping_klist($progress=0){
	$sock=new sockets();
	$unix=new unix();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];	
	RunKinit($array["WINDOWS_SERVER_ADMIN"],$array["WINDOWS_SERVER_PASS"],$progress);
	
}

function RunKinit($username,$password,$progress=1){
	$unix=new unix();
	$kinit=$unix->find_program("kinit");
	$klist=$unix->find_program("klist");
	$echo=$unix->find_program("echo");
	$function=__FUNCTION__;
	if(!is_file($kinit)){echo2("Unable to stat kinit");return;}
	resolve_kdc();
	sync_time();
	exec("$klist 2>&1",$res);
	$line=@implode("",$res);


	if(strpos($line,"No credentials cache found")>0){
		unset($res);
		echo2($line." -> initialize..");
		$password=$unix->shellEscapeChars($password);
		$cmd="$echo \"$password\"|$kinit {$username} 2>&1";
		progress_logs($progress,"{kerberaus_authentication}","$cmd");
		progress_logs($progress,"{kerberaus_authentication}","$function, kinit `$username`");
		exec("$echo \"$password\"|$kinit {$username} 2>&1",$res);
		while (list ($num, $a) = each ($res) ){	
			if(preg_match("#Password for#",$a,$re)){unset($res[$num]);}
			progress_logs($progress,"{kerberaus_authentication}","$a");
		}	
		$line=@implode("",$res);	
		if(strlen(trim($line))>0){
			progress_logs($progress,"{kerberaus_authentication}",$line." -> Failed..");
			return;
		}
		unset($res);
		progress_logs($progress,"{kerberaus_authentication}",$klist);
		exec("$klist 2>&1",$res);
	}

while (list ($num, $a) = each ($res) ){	
		progress_logs($progress,"{kerberaus_authentication}","$a");
		if(preg_match("#Default principal:(.+)#",$a,$re)){
				progress_logs($progress,"{kerberaus_authentication}","$a SUCCESS");
				break;
			}
		}	
	

		progress_logs($progress,"{kerberaus_authentication}","DONE LINE: ".__LINE__);	
}

function echo2($content){
	progress_logs(10,"{kerberaus_authentication}","$content");
	
}

function ping_kdc(){
	
	$sock=new sockets();
	$unix=new unix();
	$users=new settings_inc();
	$chmod=$unix->find_program("chmod");
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$ttime=$unix->PROCCESS_TIME_MIN($oldpid);
		progress_logs(20,"{join_activedirectory_domain}","[PING]: Already executed pid $oldpid since {$ttime}Mn");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($EnableKerbAuth==0){progress_logs(20,"{ping_kdc}","[PING]: Kerberos, disabled");return;}
	if(!checkParams()){progress_logs(20,"{ping_kdc}","[PING]: Kerberos, misconfiguration failed");return;}
	$array["RESULTS"]=false;
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$time=$unix->file_time_min($filetime);
	if(!$GLOBALS["FORCE"]){
		if($time<10){
			if(!$GLOBALS["VERBOSE"]){return;}
			progress_logs(20,"{ping_kdc}","$filetime ({$time}Mn)");
		}
	}
	$kinit=$unix->find_program("kinit");
	$echo=$unix->find_program("echo");
	$net=$unix->LOCATE_NET_BIN_PATH();
	$wbinfo=$unix->find_program("wbinfo");
	$chmod=$unix->find_program("chmod");
	$nohup=$unix->find_program("nohup");
	$domain=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$ad_server=strtolower($array["WINDOWS_SERVER_NETBIOSNAME"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$clock_explain="The clock on you system (Linux/UNIX) is too far off from the correct time.\nYour machine needs to be within 5 minutes of the Kerberos servers in order to get any tickets.\nYou will need to run ntp, or a similar service to keep your clock within the five minute window";
	
	
	$cmd="$echo $kinitpassword|$kinit {$array["WINDOWS_SERVER_ADMIN"]}@$domain -V 2>&1";
	progress_logs(20,"{ping_kdc}","$cmd");
	exec("$cmd",$kinit_results);
	while (list ($num, $ligne) = each ($kinit_results) ){
		
		if(preg_match("#Clock skew too great while getting initial credentials#", $ligne)){
			if($GLOBALS["VERBOSE"]){progress_logs(20,"{ping_kdc}","Clock skew too great while");}
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
			progress_logs(20,"{join_activedirectory_domain}","[PING]: Kerberos, Success");
		}
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{ping_kdc}","kinit: $ligne");}
	}


	$TestJoin=true;
	
	if($array["RESULTS"]==true){
		exec("$net ads testjoin 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			
			if(preg_match("#Unable to find#", $ligne)){
				$array["RESULTS"]=false;
				$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
				$TestJoin=false;
				continue;
			}
			if(preg_match("#is not valid:#", $ligne)){
				$array["RESULTS"]=false;
				$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
				$TestJoin=false;
				continue;
			}
		}
		
		if(preg_match("#OK#", $ligne)){
			$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
			$array["RESULTS"]=true;
		}
		
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/kinit.array", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/kinit.array",0777);
	
	if($GLOBALS["JUST_PING"]){return;}
	
	if(!$TestJoin){
		shell_exec("$nohup $php5 ". __FILE__." --join >/dev/null 2>&1 &");
	}
	
	
	
	
	

	if($users->SQUID_INSTALLED){
		winbind_priv();
		if(!is_dir("/var/lib/samba/smb_krb5")){@mkdir("/var/lib/samba/smb_krb5",0777,true);}
		shell_exec("$chmod 1775 /var/lib/samba/smb_krb5 >/dev/null 2>&1");
		shell_exec("$chmod 1775 /var/lib/samba >/dev/null 2>&1");
	}
	
	
}

function SAMBA_OUTPUT_ERRORS($prefix,$line){
	
	if(preg_match("#NT_STATUS_INVALID_LOGON_HOURS#", $line)){
		progress_logs(20,"{join_activedirectory_domain}","");
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","** ERROR NT_STATUS_INVALID_LOGON_HOURS");
		progress_logs(20,"{join_activedirectory_domain}","** account not allowed to logon during"); 
		progress_logs(20,"{join_activedirectory_domain}","** these logon hours");
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","");
		
	}
	
	if(preg_match("#NT_STATUS_LOGON_FAILURE#", $line)){
		progress_logs(20,"{join_activedirectory_domain}","");
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","** ERROR NT_STATUS_LOGON_FAILURE");
		progress_logs(20,"{join_activedirectory_domain}","** inactive account with bad password specified"); 
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","");
		
	}	
	if(preg_match("#NT_STATUS_ACCOUNT_EXPIRED#", $line)){
		progress_logs(20,"{join_activedirectory_domain}","");
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","** ERROR NT_STATUS_LOGON_FAILURE");
		progress_logs(20,"{join_activedirectory_domain}","** account expired"); 
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","");
		
	}	
	if(preg_match("#NT_STATUS_ACCESS_DENIED#", $line)){
		progress_logs(20,"{join_activedirectory_domain}","");
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","** ERROR NT_STATUS_ACCESS_DENIED");
		progress_logs(20,"{join_activedirectory_domain}","** Wrong administrator credentials (username or password)"); 
		progress_logs(20,"{join_activedirectory_domain}","******************************");
		progress_logs(20,"{join_activedirectory_domain}","");
		
	}	
	
	
	
	progress_logs(20,"{join_activedirectory_domain}","$prefix $line");
}


function winbind_priv($reloadservs=false,$progress=0){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	progress_logs($progress,"WINBINDD {privileges}","winbindd_priv...");
	if(!winbind_priv_is_group()){
		xsyslog("winbindd_priv group did not exists...create it");
		$groupadd=$unix->find_program("groupadd");
		$cmd="$groupadd winbindd_priv >/dev/null 2>&1";
	}
	
	if(!is_dir("/var/lib/samba/smb_krb5")){@mkdir("/var/lib/samba/smb_krb5",0777,true);}
	
	$unix=new unix();
	$gpass=$unix->find_program('gpass');
	$usermod=$unix->find_program("usermod");
	if(is_file($gpass)){
		progress_logs($progress,"{join_activedirectory_domain}","winbindd_priv group exists, add squid a member of winbindd_priv");
		$cmdline="$gpass -a squid winbindd_priv";
		if($GLOBALS["VERBOSE"]){progress_logs($progress,"WINBINDD {privileges}","$cmdline");}
		exec("$cmdline",$kinit_results);
		while (list ($num, $ligne) = each ($kinit_results) ){progress_logs(52,"WINBINDD {privileges}","winbindd_priv: $ligne");}
	}else{
		if(is_file($usermod)){
			$cmdline="$usermod -a -G winbindd_priv squid";
			if($GLOBALS["VERBOSE"]){progress_logs($progress,"WINBINDD {privileges}","$cmdline");}
			exec("$cmdline",$kinit_results);
			while (list ($num, $ligne) = each ($kinit_results) ){progress_logs($progress,"WINBINDD {privileges}","winbindd_priv: $ligne");}
		}

	}
	
	
	
	winbind_priv_perform(false,$progress);
	
	$pid_path=$unix->LOCATE_WINBINDD_PID();
	$pid=$unix->get_pid_from_file($pid_path);
	$res=array();
	progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind Daemon pid: $pid ($pid_path)...");
	if(!$unix->process_exists($pid)){
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind configuring winbind init...");
		exec("$php5 /usr/share/artica-postfix/exec.winbindd.php 2>&1",$res);
		while (list ($num, $ligne) = each ($res) ){progress_logs(54,"WINBINDD {privileges}","winbindd $ligne");}
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind Daemon stopped, start it...");
		start_winbind();
		
	}else{
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind configuring winbind init...");
		shell_exec("$php5 /usr/share/artica-postfix/exec.winbindd.php 2>&1",$res);
		while (list ($num, $ligne) = each ($res) ){progress_logs(55,"WINBINDD {privileges}","winbindd $ligne");}		
		progress_logs($progress,"WINBINDD {privileges}","winbindd already running pid $pid.. reload it");
		$res=array();
		exec("$php5 /usr/share/artica-postfix/exec.winbindd.php --restart 2>&1",$res);
		while (list ($num, $ligne) = each ($res) ){progress_logs(56,"WINBINDD {privileges}","winbindd $ligne");}
		
	}
	
	progress_logs($progress,"WINBINDD {privileges}","winbindd_monit()");
	winbindd_monit();
	
	
}
function winbind_priv_is_group(){
	$f=file("/etc/group");
	while (list ($num, $ligne) = each ($f) ){if(preg_match("#^winbindd_priv#", $ligne)){return true;}}
	return false;
}

function winbind_priv_perform($withpid=false,$progress=0){
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
			progress_logs($progress,"{kerberaus_authentication}","Already executed PID:$oldpid");
		}
		
	}
	
	winbindd_set_acls_is_xattr_var($progress);
	if(strlen($setfacl)>5){winbindd_set_acls_mainpart();}
	
	
	while (list ($num, $Directory) = each ($possibleDirs) ){
			if(is_dir($Directory)){
				if(strlen($setfacl)>5){shell_exec("$setfacl -m u:squid:rwx $Directory");}
			}
			
			if(file_exists("$Directory/pipe")){
				if(strlen($setfacl)>5){shell_exec("$setfacl -m u:squid:rwx $Directory/pipe");}
				chgrp("$Directory/pipe", "winbindd_priv");
				
			}
			
						
		}
		
		$possibleDirsACLS[]="/var/lib/samba/smb_krb5";
		$possibleDirsACLS[]="/var/lib/samba";
		
		while (list ($num, $Directory) = each ($possibleDirsACLS) ){
			if(is_dir($Directory)){
				if(strlen($setfacl)>5){
					shell_exec("$setfacl -R -m u:squid:rwx $Directory >/dev/null 2>&1");
					shell_exec("$setfacl -R -m g:squid:rwx $Directory >/dev/null 2>&1");
				}
			}
			
		}
		
		
	if(!$withpid){	
		$squidbin=$unix->find_program("squid");
		$nohup=$unix->find_program("nohup");
		if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
		if(is_file($squidbin)){
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Reloading $squidbin",basename(__FILE__),false);}
			squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
			progress_logs($progress,"{kerberaus_authentication}","Reconfigure Squid-cache...");
			$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null 2>&1 &";
			shell_exec($cmd);
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
	exec("/etc/init.d/winbind start 2>&1",$res);
	while (list ($num, $ligne) = each ($res) ){
		progress_logs(20,"{join_activedirectory_domain}","winbindd $ligne");
	}
}

function winbindisacl(){
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindisacl() -> winbindd_set_acls_mainpart()");}
	winbindd_set_acls_mainpart();
}


function winbindfix(){
	winbind_priv(true);
	winbind_priv_perform();
	
}

function winbindd_set_acls_is_xattr(){
	$f=file("/proc/mounts");
	while (list ($num, $ligne) = each ($f) ){
	if(preg_match("#^(.*)\s+\/\s+(.*?)\s+.*?,acl.*?\s+([0-9]+)#",$ligne,$re)){
		progress_logs(20,"{join_activedirectory_domain}","winbindd_priv main partition is already mounted with extended acls");
		return true;
		}
	}
	
	return false;
}
function winbindd_set_acls_is_xattr_var($progress=0){
	if(isset($GLOBALS["winbindd_set_acls_is_xattr_var_exectued"])){return;}
	$GLOBALS["winbindd_set_acls_is_xattr_var_exectued"]=true;
	$unix=new unix();
	$mount=$unix->find_program("mount");	
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	$mustchange=false;
	$remountSlash=false;
	if($GLOBALS["VERBOSE"]){progress_logs($progress,"{kerberaus_authentication}","/etc/fstab -> ".count($f)." rows");}
	while (list ($num, $ligne) = each ($f) ){
		$options=array();
		$optionsR=array();
		
		if(preg_match("#^(.*?)\/var\s+(.+)\s+(.+?)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
			progress_logs($progress,"{kerberaus_authentication}","/var partition exists with options `{$re[3]}`...");
			$options=explode(",",$re[3]);
			while (list ($a, $b) = each ($options) ){
				if(is_numeric($b)){continue;}
				if(trim($b)==null){continue;}
				$optionsR[trim($b)]=true;
				
			}
			if(!isset($optionsR["acl"])){
				$mustchange=true;
				$options[]="acl";
				$options[]="user_xattr";
				$re[3]=@implode(",", $options);
				progress_logs($progress,"{kerberaus_authentication}","ACLS found {$re[1]} main partition `/var` was not extended attribute, fix it: `".@implode(",", $options)."`...");
				$f[$num]=trim($re[1])."\t/var\t".$re[2]."\t".$re[3]."\t".$re[4]."\t".$re[5];
			}
		}
		
		
		if(preg_match("#^(.*?)\/\s+(.+)\s+(.+?)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
			progress_logs($progress,"{kerberaus_authentication}","/ partition exists with options `{$re[3]}`...");
			$options=explode(",",$re[3]);
			while (list ($a, $b) = each ($options) ){
				if(is_numeric($b)){continue;}
				if(trim($b)==null){continue;}
				$optionsR[trim($b)]=true;
			
			}
			if(!isset($optionsR["acl"])){
				$mustchange=true;
				$options[]="acl";
				$options[]="user_xattr";
				$re[3]=@implode(",", $options);
				progress_logs($progress,"{kerberaus_authentication}","ACLS found {$re[1]} main partition `/` was not extended attribute, fix it: `".@implode(",", $options)."`...");
				$f[$num]=trim($re[1])."\t/\t".$re[2]."\t".$re[3]."\t".$re[4]."\t".$re[5];
				$remountSlash=true;
			}			
			
		}
	}
	
	if($mustchange){
		@file_put_contents("/etc/fstab", @implode("\n", $f)."\n");
		shell_exec("$mount -o remount /var");
		if($remountSlash){
			shell_exec("$mount -o remount /");
		}
	}
	
	progress_logs($progress,"{kerberaus_authentication}",__FUNCTION__." done line ".__LINE__);
	
	
}



function winbindd_set_acls_mainpart(){
	if(winbindd_set_acls_is_xattr()){
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindd_set_acls_is_xattr() -> winbindd_set_acls_is_xattr_var()");}
		winbindd_set_acls_is_xattr_var();
		return;
	}
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	$mount=$unix->find_program("mount");
	if(!is_file($setfacl)){
		xsyslog("winbindd_priv setfacl no such binary");
		return;
	}
	
	
	if(!is_file($mount)){
		xsyslog("winbindd_priv mount no such binary");
		return;
	}
	
	
	$mustchange=false;
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^(.*?)\s+\/\s+(.*?)\s+(.*?)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$options=explode(",",$re[3]);
			while (list ($a, $b) = each ($options) ){
				$b=trim(strtolower($b));
				if($b==null){continue;}
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} with option `$b`");
				$MAINOPTIONS[trim($b)]=true;
			}
			if(!isset($MAINOPTIONS["acl"])){$mustchange=true;$options[]="acl";$options[]="user_xattr";}
			if(!$mustchange){
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} ACL user_xattr,acl");
			}else{
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} Add ACL user_xattr options was ". @implode(";", $options)."");
				$f[$num]="{$re[1]}\t/\t{$re[2]}\t".@implode(",", $options)."\t{$re[4]}\t{$re[5]}";
				reset($f);
				while (list ($c, $d) = each ($f) ){if(trim($d)==null){continue;}$cc[]=$d;}
				if(count($cc)>1){
					@file_put_contents("/etc/fstab", @implode("\n", $cc)."\n");
					xsyslog("winbindd_priv remount system partition...");
					shell_exec("$mount -o remount /");
				}
			}
		}
	}
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindd_set_acls_is_xattr() -> winbindd_set_acls_is_xattr_var()");}
	winbindd_set_acls_is_xattr_var();
}


function winbindd_monit(){
	  
	  if(is_file("/etc/monit/conf.d/winbindd.monitrc")){progress_logs(20,"{join_activedirectory_domain}","winbindd monit: Already set");return;}
	   $unix=new unix();
	   $monit=$unix->find_program("monit");
	   if(!is_file($monit)){
	   	xsyslog("winbindd monit: no such binary");
	   	return;
	   }
	   
	   $nohup=$unix->find_program("nohup");
	   
 	   $fs[]="#!/bin/sh";
	   $fs[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $fs[]="/etc/init.d/winbind start";
	   $fs[]="exit 0\n";	
	   
	   $fk[]="#!/bin/sh";
	   $fk[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $fk[]="/etc/init.d/winbind stop";
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
		progress_logs(20,"{join_activedirectory_domain}","winbindd monit: creating winbindd.monitrc");
		@file_put_contents("/etc/monit/conf.d/winbindd.monitrc", @implode("\n", $fm));
		progress_logs(20,"{join_activedirectory_domain}","winbindd monit: restarting monit");
		shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --monit-check >/dev/null 2>&1 &");
}
function disconnect(){
	$unix=new unix();	
	$user=new settings_inc();
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	$kdestroy=$unix->find_program("kdestroy");
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
	if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}	
	
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminpassword=str_replace("'", "", $adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];	
	
	$function=__FUNCTION__;
	if(!is_file($netbin)){progress_logs(100,"{join_activedirectory_domain}"," net, no such binary");return;}
	if(!$user->SAMBA_INSTALLED){progress_logs(100,"{join_activedirectory_domain}"," Samba, no such software");return;}	
	exec("$netbin ads keytab flush 2>&1",$results);
	exec("$netbin ads leave -U $adminname%$adminpassword 2>&1",$results);
	exec("$kdestroy 2>&1",$results);
	
	$unix->send_email_events("Active directory disconnected", "An order as been sent to disconnect Active Directory", "activedirectory");
	$sock->SET_INFO("EnableKerbAuth", 0);
	exec("/usr/share/artica-postfix/bin/artica-install --nsswitch 2>&1",$results);
	exec("/etc/init.d/artica-postfix restart samba 2>&1",$results);	
	while (list ($num, $ligne) = each ($results) ){
		progress_logs(90,"{join_activedirectory_domain}","Leave......: $ligne");
	}	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
	progress_logs(100,"{join_activedirectory_domain}","Leave......: $ligne");
}


function checkParams(){
	
	progress_logs(5,"{apply_settings}","Checks settings..");
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if($array["WINDOWS_DNS_SUFFIX"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_DNS_SUFFIX = NULL");
		return false;
	}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_NETBIOSNAME = NULL");
		return false;}
	if($array["WINDOWS_SERVER_TYPE"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_TYPE = NULL");
		return false;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_ADMIN = NULL");return false;}
	if($array["WINDOWS_SERVER_PASS"]==null){progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_PASS = NULL");return false;}
	
	$ADNETIPADDR=$array["ADNETIPADDR"];
	$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
	if(!$UseADAsNameServer){$UseADAsNameServer=0;}
	if($UseADAsNameServer==1){
		$resolve=new resolv_conf();
		progress_logs(20,"{apply_settings}","Auth Winbindd, SET $ADNETIPADDR as 1st DNS in line ". __LINE__);
		$resolve->MainArray["DNS1"]=$ADNETIPADDR;
		progress_logs(20,"{apply_settings}","Auth Winbindd, Building configuration ". __LINE__);
		$REX=$resolve->build();
		progress_logs(20,"{apply_settings}","Auth Winbindd, saving /etc/resolv.conf ". __LINE__);
		@file_put_contents("/etc/resolv.conf",$resolve->build());
		progress_logs(20,"{apply_settings}","Auth Winbindd, saving new configuration ". __LINE__);
		$resolve->save();
	}
	
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	
	progress_logs(20,"{apply_settings}","Trying to resolve host $hostname ....". __LINE__);
	$ip=gethostbyname($hostname);
	progress_logs(20,"{apply_settings}","host $hostname = $ip....". __LINE__);
	
	if($ip==$hostname){
		progress_logs(7,"{apply_settings}","!!!!! $ip gethostbyname($hostname) failed !!!!!");
		return false;
		
	}
	
	progress_logs(7,"{apply_settings}","Checks settings success..");
	return true;
}

function xsyslog($text){
	
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}

}
function GetUsersNumber(){
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$ad=new external_ad_search();
	$users=$ad->NumUsers();
	echo "Users: $users\n";
}