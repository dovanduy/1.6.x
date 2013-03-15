<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	echo "VERBOSED\n";
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}

echo "php ".__FILE__." --stop ( stop the zarafa-server)\n";
echo "php ".__FILE__." --start ( start the zarafa-server)\n";

function XZARAFA_SERVER_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("zarafa-server -c /etc/zarafa/server.cfg");

}

function start(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafa-server-starter.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}

	@file_put_contents($pidfile, getmypid());
	$serverbin=$unix->find_program("zarafa-server");


	if(!is_file($serverbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server is not installed...\n";}
		return;
	}
	
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	$oldpid=$unix->get_pid_from_file($SLAPD_PID_FILE);
	if(!$unix->process_exists($oldpid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Failed, OpenLDAP server is not running...\n";}
		return;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: OpenLDAP server is running...\n";}
	}

	$pid=XZARAFA_SERVER_PID();

	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server Engine already running pid $pid since {$time}mn\n";}
		return;
	}


	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting zarafa-server reconfigure...\n";}
	system("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");
	$f[]=$serverbin;
	$f[]="--config=/etc/zarafa/server.cfg";
	$f[]="--ignore-database-version-conflict";
	$f[]="--ignore-unknown-config-options";

	$cmdline=@implode(" ", $f);

	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting zarafa-server daemon\n";}
	shell_exec("$cmdline 2>&1");
	sleep(1);

	for($i=0;$i<5;$i++){
		$pid=XZARAFA_SERVER_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server daemon wait $i/5\n";}
		sleep(1);
	}

	$pid=XZARAFA_SERVER_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server daemon failed to start\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmdline\n";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server daemon success PID $pid\n";}

		}
	}
}

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	$pid=XZARAFA_SERVER_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server stopped...\n";}
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 ".__FILE__." --start");
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server reconfigure...\n";}
	system("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");	
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: zarafa-server reloading PID $pid...\n";}
	$kill=$unix->find_program("kill");
	shell_exec("$kill -HUP $pid");
	
}


function stop(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}

	$pid=XZARAFA_SERVER_PID();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server already stopped...\n";}
		return;
	}

	if(is_file("/tmp/zarafa-upgrade-lock")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server database upgrade is taking place.\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Do not stop this process bacause it may render your database unusable..\n";}
		return;
	}

	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server Daemon with a ttl of {$time}mn\n";}
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server killing smoothly PID $pid...\n";}
	shell_exec("$kill $pid");
	sleep(1);

	for($i=1;$i<60;$i++){
		$pid=XZARAFA_SERVER_PID();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server pid $pid successfully stopped ...\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server wait $i/60\n";}
		sleep(1);
	}


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server daemon success...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server daemon failed...\n";}
}
?>