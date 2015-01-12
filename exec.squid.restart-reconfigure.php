<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["CRASHED"]=false;
$GLOBALS["BY_CACHE_LOGS"]=false;
$GLOBALS["BY_STATUS"]=false;
$GLOBALS["BY_CLASS_UNIX"]=false;
$GLOBALS["BY_FRAMEWORK"]=false;
$GLOBALS["BY_OTHER_SCRIPT"]=false;
$GLOBALS["BY_ARTICA_INSTALL"]=false;
$GLOBALS["BY_RESET_CACHES"]=false;
$GLOBALS["OUTPUT"]=false;


startx();


function build_progress($text,$pourc){
	echo "******************** {$pourc}% $text ********************\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function startx(){
	
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		build_progress("Process already running PID $pid",110);
		return;
	}
	
	@file_put_contents($pidFile, getmypid());	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("Reconfiguring Proxy service",10);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{stopping_proxy_service},{please_wait}",50);
	system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --stop --force");
	build_progress("{starting_proxy_service},{please_wait}",95);
	system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --start --force");
	build_progress("{done}",100);
}