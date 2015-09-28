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


if($argv[1]=="--install-key"){install_key($argv[2]);die();}


function install_key($keyfile){
	
	
	$path="/usr/share/artica-postfix/ressources/conf/upload/$keyfile";
	$license_bin="/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager";
	$time=time();
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		if($unix->PROCCESS_TIME_MIN($pid,10)<2){
			progress("{failed} Already running",110);
			echo "Already runinng PID $pid\n";
			return;}
	}
	
	
	echo "License....: $path\n";
	echo "Binary File: $license_bin\n";
	if(!is_file($path)){
		echo "$path No such file..\n";
		progress("{failed} $keyfile No such file",110);
		die();
	}
	if(!is_file($license_bin)){
		echo "$path No such file..\n";
		progress("{failed} ".basename($license_bin)." No such binary",110);
		@unlink($path);
		die();
	}
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$sock->SET_INFO("kavicapserverEnabled", 1);
	@unlink("/etc/artica-postfix/kav4proxy-licensemanager");
	@unlink("/etc/artica-postfix/kav4proxy-licensemanager-i");
	progress("{removing_old_licenses}",20);
	system("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -d a");
	
	progress("{installing} $keyfile",30);
	
	$cmd="/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -c /etc/opt/kaspersky/kav4proxy.conf -a $path";
	system($cmd);
	@unlink($path);
	
	progress("{analyze_license} $keyfile",50);
	shell_exec("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -i >/etc/artica-postfix/kav4proxy-licensemanager-i 2>&1");
	
	progress("{stopping_service}",60);
	echo "/etc/init.d/kav4proxy stop\n";
	system("/etc/init.d/kav4proxy stop");
	
	progress("{starting_service}",60);
	echo "/etc/init.d/kav4proxy start\n";
	system("$nohup /etc/init.d/kav4proxy start >/dev/null 2>&1 &");
	system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	
	
	progress("{launch_updates}",70);
	$nohup=$unix->find_program("nohup");
	$php=$unix-LOCATE_PHP5_BIN();
	sleep(2);
	progress("{launch_updates}",80);
	shell_exec("$nohup /usr/share/artica-postfix/exec.keepup2date.php --update --force >/dev/null 2>&1 &");
	sleep(3);
	progress("{success}",100);
	
}

function progress($text,$pourc){
	if(trim($text)==null){return;}
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% ".getmypid()." ".date("Y-m-d H:i:s")."] $text\n";}
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/kav4license.install.progress";
	$data=unserialize(@file_get_contents($cacheFile));
	$data["TEXT"]=$text;
	$data["POURC"]=$pourc;
	@file_put_contents($cacheFile, serialize($data));
	@chmod($cacheFile,0755);
	
	
}