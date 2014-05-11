<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');


if($argv[1]=="--install"){install();die();}
if($argv[1]=="--uninstall"){uninstall();die();}


function uninstall(){
	$time=time();
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($oldpid)){
		if($unix->PROCCESS_TIME_MIN($oldpid,10)<2){return;}
	}
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	@unlink($cacheFile);

	progress("{uninstalling}","Disable Kaspersky references in Artica",30);
	$sock=new sockets();
	$sock->SET_INFO("kavicapserverEnabled", 0);
	$rm=$unix->find_program("rm");
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	if(is_file("/etc/init.d/kav4proxy")){
		progress("{stopping} {service}","Stopping Kaspersky For Proxy server service",30);
		shell_exec("/etc/init.d/kav4proxy stop");
		progress("{uninstalling}","Disable Kaspersky in servicers",30);
		if(is_file($debianbin)){shell_exec("$debianbin -f kav4proxy remove >/dev/null 2>&1");}
		if(is_file($redhatbin)){shell_exec("$redhatbin --del kav4proxy >/dev/null 2>&1");}
		@unlink("/etc/init.d/kav4proxy");
	}
	progress("{uninstalling}","Cleaning filesystem",30);
	shell_exec("$rm -rf /opt/kaspersky/kav4proxy");
	progress("{uninstalling}","Cleaning filesystem",40);
	shell_exec("$rm -rf /var/opt/kaspersky/kav4proxy");
	progress("{uninstalling}","Cleaning filesystem",50);
	shell_exec("$rm -rf /var/log/kaspersky/kav4proxy");
	progress("{uninstalling}","Cleaning filesystem",60);
	shell_exec("$rm -rf /var/run/kav4proxy");
	shell_exec("$rm -rf /var/db/kav/databases");
	progress("{uninstalling}","Cleaning filesystem",70);
	
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){@unlink("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE");}
	
	progress("{uninstalling}","Learning Artica to the removed software",80);
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose --".time()." >/dev/null 2>&1");
	shell_exec("/etc/init.d/artica-status restart --force >/dev/null 2>&1");
	progress("{uninstalling}","Reconfiguring Squid-cache software",90);
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	progress("{uninstalling} {done}","Done...",100);
	
}

function install(){
	
	$filename="kav4proxy_5.5-88.tar.gz";
	$uri="http://93.88.245.88/download/kaspersky/$filename";
	$time=time();
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($oldpid)){
		if($unix->PROCCESS_TIME_MIN($oldpid,10)<2){return;}
	}
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	@unlink($cacheFile);
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$TMPDIR=$unix->TEMP_DIR()."/$time";
	$FINAL_TARGET_DIR=null;
	$TARGET_PATH="$TMPDIR/$filename";
	progress("{downloading} 5.5.88 version","Downloading $filename",30);
	progress("{downloading} 5.5.88 version","Temporary directory = $TMPDIR",30);
	$curl=new ccurl("$uri");
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	@mkdir($TMPDIR);
	if(!$curl->GetFile($TARGET_PATH)){
		progress("{failed}",$curl->error,100);
		shell_exec("$rm -rf $TMPDIR");
		return;
	}
	
	
	progress("{downloading} 5.5.88 version {success}","success saving $TARGET_PATH ",50);
	progress("{extracting} 5.5.88 version","Extracting $TARGET_PATH ",55);
	$tar=$unix->find_program("tar");
	exec("$tar xvf $TARGET_PATH -C /$TMPDIR/ 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		progress("{extracting} 5.5.88 version",$line,60);
	}
	
	$dir=$unix->dirdir($TMPDIR);
	while (list ($index, $line) = each ($dir) ){
		progress("{search} {directory}",$line,65);
		if(preg_match("#kav4proxy#", $line)){
			progress("{search} {directory}","Found directory $line",65);
			$FINAL_TARGET_DIR=$line;
			break;
		}
	}
	
	if($FINAL_TARGET_DIR==null){
		progress("{extracting} 5.5.88 version {failed}","Unable to find a suitable directory",100);
		shell_exec("$rm -rf $TMPDIR");
		return;
	}
	
	progress("{installing} 5.5.88 version","Copy the content of $FINAL_TARGET_DIR",70);
	exec("$cp -rfdv  $FINAL_TARGET_DIR/* / 2>&1",$resultsA);
	while (list ($index, $line) = each ($resultsA) ){ progress("{installing} 5.5.88 version",$line,70); }
	progress("{installing} 5.5.88 version","Removing the $TMPDIR directory",71);
	shell_exec("$rm -rf $TMPDIR");
	
	if(!is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager")){
		progress("{installing} 5.5.88 version {failed}","install from $FINAL_TARGET_DIR failed ",100);
		return;
	}
	
	$ln=$unix->find_program("ln");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	progress("{installing} 5.5.88 version","linking /etc/init.d/kav4proxy",75);
	shell_exec("ln -s --force /opt/kaspersky/kav4proxy/lib/bin/kav4proxy /etc/init.d/kav4proxy");
	if(is_file($debianbin)){shell_exec("$debianbin -f kav4proxy defaults >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --add kav4proxy >/dev/null 2>&1");}
	
	progress("{installing} 5.5.88 version","executing preinstall",78);
	exec('/usr/share/artica-postfix/bin/install/kavgroup/kav4prox_predoinst.sh 2>&1',$results2);
	
	while (list ($index, $line) = each ($results2) ){
		progress("{installing} 5.5.88 version",$line,78);
	}
	progress("{installing} 5.5.88 version","Creating kluser user",80);
	$unix->CreateUnixUser("kluser","klusers");
	progress("{installing} 5.5.88 version","Apply permissions",85);
	
	@mkdir("/var/log/kaspersky/kav4proxy",0755,true);
	@mkdir("/var/run/kav4proxy",0755,true);
	shell_exec("$chown -R kluser:klusers /var/log/kaspersky/kav4proxy");
	shell_exec("$chown -R kluser:klusers /var/opt/kaspersky/kav4proxy");
	shell_exec("$chown -R kluser:klusers /var/run/kav4proxy");
	shell_exec("$chown -R kluser:klusers /var/opt/kaspersky/kav4proxy");
	shell_exec("$chmod 0755 /var/opt/kaspersky/kav4proxy");
	
	$f[]="EULA_AGREED=yes";
	$f[]="";
	@file_put_contents("/var/opt/kaspersky/kav4proxy/installer.dat",@implode("\n", $f));
	$f=array();
	
	
	$f[]="CONFIGURE_ENTER_KEY_PATH=";
	$f[]="KAVMS_SETUP_LICENSE_DOMAINS=*";
	$f[]="CONFIGURE_KEEPUP2DATE_ASKPROXY=no";
	$f[]="CONFIGURE_RUN_KEEPUP2DATE=no";
	$f[]="CONFIGURE_WEBMIN_ASKCFGPATH=";
	$f[]="KAV4PROXY_SETUP_TYPE=3";
	$f[]="KAV4PROXY_SETUP_LISTENADDRESS=127.0.0.1:1344";
	$f[]="KAV4PROXY_SETUP_CONFPATH=/etc/squid3/squid.conf";
	$f[]="KAV4PROXY_SETUP_BINPATH=".$unix->LOCATE_SQUID_BIN();
	$f[]="KAV4PROXY_CONFIRM_FOUND=Y";
	$f[]="KAVICAP_SETUP_NONICAPCFG=Y";
	@file_put_contents("/opt/kaspersky/kav4proxy/lib/bin/setup/autoanswers.conf",@implode("\n", $f));

	chdir('/opt/kaspersky/kav4proxy/lib/bin/setup');
	exec('./postinstall.pl 2>&1',$results3);
	while (list ($index, $line) = each ($results3) ){
		progress("{installing} 5.5.88 version",$line,90);
	}
	
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose --".time()." >/dev/null 2>&1");
	shell_exec("/etc/init.d/artica-status restart --force >/dev/null 2>&1");
	
	progress("{installed}","Done",100);
	
}

function progress($title,$text,$pourc){
	if(trim($text)==null){return;}
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% ".getmypid()." ".date("Y-m-d H:i:s")."] $text\n";}
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	$data=unserialize(@file_get_contents($cacheFile));
	$data["TITLE"]=$title;
	$data["POURC"]=$pourc;
	$data["LOGS"][]=getmypid()." [".date("Y-m-d H:i:s")."] $text";
	@file_put_contents($cacheFile, serialize($data));
	@chmod($cacheFile,0755);
	
	
}