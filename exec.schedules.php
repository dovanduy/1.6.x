<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["WITHOUT_RESTART"]=false;
$GLOBALS["CMDLINES"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--no-restart#",implode(" ",$argv))){$GLOBALS["WITHOUT_RESTART"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.tasks.inc');
include_once(dirname(__FILE__).'/ressources/class.process.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if($GLOBALS["VERBOSE"]){$GLOBALS["OUTPUT"]=true;$GLOBALS["WITHOUT_RESTART"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--run-schedules"){run_schedules($argv[2]);die();}

if($argv[1]=="--defaults"){Defaults($argv[2]);die();}
if($argv[1]=="--run"){execute_task($argv[2]);die();}
if($argv[1]=="--run-squid"){execute_task_squid($argv[2]);die();}


build_schedules();

function Defaults(){
	$task=new system_tasks();
	$task->CheckDefaultSchedules();
	
}

function build_schedules(){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	$task=new system_tasks();
	$task->CheckDefaultSchedules();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		writelogs("Already executed pid $oldpid",__FILE__,__FUNCTION__,__LINE__);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<1){
			writelogs("To short time to execute the process",__FILE__,__FUNCTION__,__LINE__);
			return;
		}
	}
	
	@file_put_contents($pidTime, time());
	if(!$q->TABLE_EXISTS("system_schedules","artica_backup")){$task->CheckDefaultSchedules();}
	
	if($q->COUNT_ROWS("system_schedules","artica_backup")==0){
		echo "Starting......: artica-postfix watchdog (fcron) system_schedules is empty !!\n";
		die();
	}
	
	
	$sql="SELECT * FROM system_schedules WHERE enabled=1";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){
		echo "Starting......: artica-postfix watchdog (fcron) $q->mysql_error on line ". __LINE__."\n";
		return;
	}	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$chmod=$unix->find_program("chmod");
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!isset($settings["max_nice"])){$settings["max_nice"]=null;}
	if(!isset($settings["max_load_wait"])){$settings["max_load_wait"]=null;}
	if(!isset($settings["max_load_avg5"])){$settings["max_load_avg5"]=null;}
	
	
	
	if(!is_numeric($settings["max_load_avg5"])){$settings["max_load_avg5"]="2.5";}
	if(!is_numeric($settings["max_load_wait"])){$settings["max_load_wait"]="10";}
	if(!is_numeric($settings["max_nice"])){$settings["max_nice"]="19";}	
	$max_load_wait=$settings["max_load_wait"];
	@unlink("/etc/cron.d/artica-cron");
	foreach (glob("/etc/cron.d/*") as $filename) {
		$file=basename($filename);
		if(preg_match("#syssch-[0-9]+#", $filename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix watchdog (fcron) remove $filename\n";}
			@unlink($filename);}
	}
	
	@unlink("/etc/artica-postfix/system.schedules");
	$TRASNCODE["0 * * * *"]="1h";
	$TRASNCODE["0 4,8,12,16,20 * * *"]="4h";
	$TRASNCODE["0 0,4,8,12,16,20 * * *"]="4h";
	$TRASNCODE["0 3,5,7,9,11,13,15,17,19,23 * * *"]="3h";
	$TRASNCODE["0 0,3,5,7,9,11,13,15,17,19,23 * * *"]="3h";
	$TRASNCODE["0 2,4,6,8,10,12,14,16,18,20,22 * * *"]="2h";
	$TRASNCODE["0 0,2,4,6,8,10,12,14,16,18,20,22 * * *"]="2h";
	$TRASNCODE["20,40,59 * * * *"]="20";
	$TRASNCODE["0,20,40 * * * *"]="20";
	$TRASNCODE["0,10,20,30,40,50 * * * *"]="10";
	
	$nice=$unix->EXEC_NICE();
	$me=__FILE__;
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];
		if($TaskType==0){continue;}
		if($ligne["TimeText"]==null){continue;}
		$md5=md5("$TimeText$TaskType");
		if(isset($alreadydone[$md5])){if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix watchdog task {$ligne["ID"]} already set\n";}continue;}
		$alreadydone[$md5]=true;
		
		if(!isset($task->tasks_processes[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix watchdog (fcron) Unable to stat task process of `$TaskType`\n";}
			continue;
		}
		
		if(isset($task->task_disabled[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix`$TaskType` disabled\n";}
			continue;
		}
		
		$script=$task->tasks_processes[$TaskType];
		
		
		
		$f=array();
		if($GLOBALS["OUTPUT"]){echo "Starting......: scheduling $script\n";} 
		$cmdline=trim("$nice $php5 $me --run {$ligne["ID"]}");
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["TimeText"]}  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/syssch-{$ligne["ID"]}", @implode("\n", $f));
		continue;		
		
		
		
		if(isset($TRASNCODE[trim($ligne["TimeText"])])){
			$f[]="@nice({$settings["max_nice"]}),lavg5({$settings["max_load_avg5"]}),until($max_load_wait),mail(false) {$TRASNCODE[trim($ligne["TimeText"])]} $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;
		}
	
		
		$f[]="&nice({$settings["max_nice"]}),lavg5({$settings["max_load_avg5"]}),until($max_load_wait),mail(false) {$ligne["TimeText"]} $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix watchdog (fcron) Saving ".count($f)." schedules\n";}
	@file_put_contents("/etc/artica-postfix/system.schedules",implode("\n",$f));
	if(!$GLOBALS["WITHOUT_RESTART"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: artica-postfix watchdog (fcron) restarting fcron..\n";}
		if($GLOBALS["OUTPUT"]){system("/etc/init.d/artica-postfix restart fcron");}else{
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup /etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
		}	
	}
	
}
function execute_task($ID){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCHEDULE_ID"]=$ID;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ID.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeProcess=$unix->PROCCESS_TIME_MIN($oldpid);
		system_admin_events("$oldpid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		return;
	}
	
	$pidtime=$unix->file_time_min($pidfile);
	if($pidtime<1){
		system_admin_events("last execution was done since {$pidtime}mn" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		return;
	}	

	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];		
	
	writelogs("Task $ID Load:$internal_load cmdline `{$GLOBALS["CMDLINES"]}`",__FUNCTION__,__FILE__,__LINE__);	
	
	if(isMaxInstances()){return;}
	
	if(system_is_overloaded(basename(__FILE__))){
		OverloadedCheckBadProcesses();
		for($i=0;$i<20;$i++){
			if(system_is_overloaded(basename(__FILE__))){
				writelogs("Task $ID -> overloaded {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, wait 1s",__FUNCTION__,__FILE__,__LINE__);
				sleep(1);
			}
			if(!system_is_overloaded(basename(__FILE__))){break;}
		}
	}
	
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("Overloaded system after 20 secondes, aborting task" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --run $ID");
		return;
	}

	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM system_schedules WHERE ID=$ID","artica_backup"));
	$tasks=new system_tasks();
	$TaskType=$ligne["TaskType"];
	if($TaskType==0){return;}	
	if(!isset($tasks->tasks_processes[$TaskType])){system_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return;}
	if(isset($task->task_disabled[$TaskType])){return;}
	$script=$tasks->tasks_processes[$TaskType];
	$nice=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$nice $php5 $WorkingDirectory/$script --schedule-id=$ID >/dev/null";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$nice $WorkingDirectory/bin/{$re[1]} >/dev/null";}
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} will be executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	$t=time();
	shell_exec($cmd);	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Task is executed took $took" , __FUNCTION__, __FILE__, __LINE__, "tasks");
}

function events($text,$function,$line){
	system_admin_events($text , $function, __FILE__, $line, "tasks");
	
}


function run_schedules($ID){
	$GLOBALS["SCHEDULE_ID"]=$ID;
	writelogs("Task $ID",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM system_schedules WHERE ID=$ID","artica_backup"));
	$tasks=new system_tasks();
	$TaskType=$ligne["TaskType"];
	if($TaskType==0){return;}	
	if(!isset($tasks->tasks_processes[$TaskType])){system_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return;}
	$script=$tasks->tasks_processes[$TaskType];
	if(isset($task->task_disabled[$TaskType])){return;}
	
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$nohup $php5 $WorkingDirectory/$script --schedule-id=$ID >/dev/null 2>&1 &";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$nice $WorkingDirectory/bin/{$re[1]} >/dev/null";}	
	
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} is executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	system_admin_events("Task is executed with `$script`" , __FUNCTION__, __FILE__, __LINE__, "tasks");
	shell_exec($cmd);
	
}

function execute_task_squid($ID){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCHEDULE_ID"]=$ID;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ID.squid.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timeProcess=$unix->PROCCESS_TIME_MIN($oldpid);
		system_admin_events("$oldpid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		return;
	}
	
	$pidtime=$unix->file_time_min($pidfile);
	if($pidtime<1){
		system_admin_events("last execution was done since {$pidtime}mn" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		return;
	}
	
	
	usleep(rand(900, 3000));
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];	
	
	writelogs("Task $ID Load:$internal_load cmdline `{$GLOBALS["CMDLINES"]}`",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["SCHEDULE_ID"]=$ID;
	if(isMaxInstances()){
		ufdbguard_admin_events("Too much instances loaded, aborting task...", __FUNCTION__, __FILE__, __LINE__, "scheduler");
		return;}
	
	
	if(system_is_overloaded(basename(__FILE__))){
		OverloadedCheckBadProcesses();
		for($i=0;$i<20;$i++){
			if(system_is_overloaded(basename(__FILE__))){
				writelogs("Task $ID -> overloaded {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, wait 1s",__FUNCTION__,__FILE__,__LINE__);
				sleep(1);
			}
			if(!system_is_overloaded(basename(__FILE__))){
				ufdbguard_admin_events("Overloaded system, aborting task...", __FUNCTION__, __FILE__, __LINE__, "scheduler");
				break;}
		}
	}
	
	if(system_is_overloaded(basename(__FILE__))){
		ufdbguard_admin_events("Overloaded system after 20 secondes ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), aborting task" , __FUNCTION__, __FILE__, __LINE__, "tasks");
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --run-squid $ID");
		return;
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID"));
	$TaskType=$ligne["TaskType"];
	if($TaskType==0){continue;}	
	if(!isset($q->tasks_processes[$TaskType])){ufdbguard_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return;}
	$script=$q->tasks_processes[$TaskType];
	$nice=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$nice $php5 $WorkingDirectory/$script --schedule-id=$ID >/dev/null";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$nice $WorkingDirectory/bin/{$re[1]} >/dev/null";}
	
	ufdbguard_admin_events("Task {$GLOBALS["SCHEDULE_ID"]} will be executed with `$cmd` ", __FUNCTION__, __FILE__, __LINE__, "scheduler");
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} will be executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	$t=time();
	shell_exec($cmd);	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("Task is executed took $took" , __FUNCTION__, __FILE__, __LINE__, "tasks");
}

function isMaxInstances(){
	
	$MaxInstnaces=11;
	$MaxInstancesToDie=16;
	$p=new processes_php();
	$MemoryInstances=$p->MemoryInstances();
	if(!is_numeric($MemoryInstances)){$MemoryInstances=0;}
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} -> $MemoryInstances instances...",__FUNCTION__,__FILE__,__LINE__);
	if($MemoryInstances>$MaxInstancesToDie){
		writelogs("Task {$GLOBALS["SCHEDULE_ID"]} -> too much instances ($MemoryInstances) die ".@implode(",", $GLOBALS["INSTANCES_EXECUTED"]),__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	if($MemoryInstances>$MaxInstnaces){
		for($i=0;$i<10;$i++){
			writelogs("Task {$GLOBALS["SCHEDULE_ID"]} -> too much instances ($MemoryInstances), waiting 10s ".@implode(",", $GLOBALS["INSTANCES_EXECUTED"]),__FUNCTION__,__FILE__,__LINE__);	
			sleep(10);
			$MemoryInstances=$p->MemoryInstances();
			if($MemoryInstances<$MaxInstnaces){break;}
		}
	}
	$MemoryInstances=$p->MemoryInstances();
	if($MemoryInstances>$MaxInstnaces){
		ufdbguard_admin_events("Too much instances ($MemoryInstances Max:$MaxInstnaces) aborting task ".@implode(",", $GLOBALS["INSTANCES_EXECUTED"]) , __FUNCTION__, __FILE__, __LINE__, "tasks");
		$unix->THREAD_COMMAND_SET("$php5 {$GLOBALS["CMDLINES"]}");
		return true;
	}

	return false;
	
}

function OverloadedCheckBadProcesses(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	if(is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date")){
		$pid=$unix->PIDOF("/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date");
		if($pid>0){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time>90){
				$unix->send_email_events("kav4proxy-keepup2date pid: $pid killed", "It was running since {$time}Mn, and reach the maximal 90mn TTL", "proxy");
				shell_exec("$kill -9 $pid");
			}
		}
		
	}
	
	
	
}


