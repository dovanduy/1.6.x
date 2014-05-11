<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);

build();



function build(){
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	// /etc/artica-postfix/pids/exec.squid.rotate.php.build.time
	
	$sock=new sockets();
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){echo "Already PID $pid is running\n";die();}
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($timefile);
		if($time<60){echo "Only each 60mn\n";die();}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	
	
	$mysar=$unix->find_program("mysar");
	
	if(!is_file($mysar)){
		$BaseWorkDir="/home/squid/access_logs";
		if (!$handle = opendir($BaseWorkDir)) {@mkdir($BaseWorkDir,0755,true);return;}
		$syslog=new mysql_storelogs();
		
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$filename="$BaseWorkDir/$fileZ";
			if(is_dir($filename)){continue;}
			$syslog->ROTATE_TOMYSQL($filename);
			if(function_exists("system_is_overloaded")){if(system_is_overloaded()){return;}}
		}
	}
	
	$BaseWorkDir="/home/squid/cache-logs";
	if (!$handle = opendir($BaseWorkDir)) {@mkdir($BaseWorkDir,0755,true);return;}
	$syslog=new mysql_storelogs();
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		if(is_dir($filename)){continue;}
		$syslog->ROTATE_TOMYSQL($filename);
		if(function_exists("system_is_overloaded")){if(system_is_overloaded()){return;}}
	}	
	
	
	
	$BaseWorkDir="/home/logrotate/work";
	if ($handle = opendir("/home/logrotate/work")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$BaseWorkDir/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^store\.log-#", $file)){@unlink($path);continue;}
				$timef=$unix->file_time_min($path);
				$syslog->ROTATE_TOMYSQL($path);
				if($timef>5760){if(is_file($path)){@unlink($path);continue;}}
				if(function_exists("system_is_overloaded")){if(system_is_overloaded()){return;}}
			}
		}
	}	
	
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath<>null){
		if($LogRotatePath<>"/home/logrotate"){
			$BaseWorkDir="$LogRotatePath/work";
			if ($handle = opendir($BaseWorkDir)){
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						$path="$BaseWorkDir/$file";
						if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
						if(preg_match("#^store\.log-#", $file)){@unlink($path);continue;}
						$timef=$unix->file_time_min($path);
						$syslog->ROTATE_TOMYSQL($path);
						if($timef>5760){if(is_file($path)){@unlink($path);continue;}}
						if(function_exists("system_is_overloaded")){if(system_is_overloaded()){return;}}
					}
				}
			}
		}
	}	
	
}
?>