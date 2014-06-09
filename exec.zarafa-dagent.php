<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if($argv[1]=="--stop"){stop();exit;}



function stop(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$kill=$unix->find_program("kill");
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Stopping Zarafa LMTP dagent: This script is already executed PID: $pid since {$time}Mn\n";
		if($time<5){if(!$GLOBALS["FORCE"]){return;}}
		unix_system_kill_force($pid);
	}	
	@file_put_contents($pidfile, getmypid());
	echo "\n";
	$pidfile="/var/run/zarafa-dagent.pid";
	
	echo "Stopping Zarafa LMTP dagent: Pid file: /var/run/zarafa-dagent.pid\n";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	if($unix->process_exists($pid)){
		echo "Stopping Zarafa LMTP dagent: Pid $pid\n";
		unix_system_kill($pid);
		for($i=0;$i<8;$i++){
			sleep(1);
			if($unix->process_exists($pid)){
				echo "Stopping Zarafa LMTP dagent: $pid still running...\n";
			}else{
				break;
			}
		}
		
	}
	
	if($unix->process_exists($pid)){
		echo "Stopping Zarafa LMTP dagent: Force kill Pid $pid\n";
		unix_system_kill_force($pid);
		for($i=0;$i<5;$i++){
		sleep(1);
			if($unix->process_exists($pid)){
				echo "Stopping Zarafa LMTP dagent: $pid still running...\n";
			}else{
				break;
			}
		}
	}

	if($unix->process_exists($pid)){
		echo "Stopping Zarafa LMTP dagent: Failed...\n";
		return;
	}
	
	$pidof=$unix->find_program("pidof");
	$binpath=$unix->find_program("zarafa-dagent");
	$results=exec("pidof /usr/bin/zarafa-dagent 2>&1");
	$tr=explode(" ",$results);
	while (list ($num, $int) = each ($tr)){
		if(!is_numeric($int)){continue;}
		echo "Stopping Zarafa LMTP dagent: Force kill Ghost daemon pid $int\n";
		unix_system_kill_force($int);
		
	}
	
	echo "Stopping Zarafa LMTP dagent: Done...\n";
	
}
