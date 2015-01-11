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
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");

if($GLOBALS["VERBOSE"]){
		$GLOBALS["OUTPUT"]=true;
		$GLOBALS["WITHOUT_RESTART"]=true;
		ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
}


$unix=new unix();
$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>2){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, try to kill them!\n";
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){continue;}
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		$cmdlineMD=md5($cmdline);
		if(isset($ALREDPID[$cmdlineMD])){
			echo "Starting......: ".date("H:i:s")." killing $pid `$cmdline`\n";
			unix_system_kill_force($pid);
			continue;
		}
		$ALREDPID[$cmdlineMD]=true;
	}

}
$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>6){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." dying...\n";
}






if($argv[1]=="--run-schedules"){run_schedules($argv[2]);die();}
if($argv[1]=="--defaults"){Defaults($argv[2]);die();}
if($argv[1]=="--run"){execute_task($argv[2]);die();}
if($argv[1]=="--run-squid"){execute_task_squid($argv[2]);die();}


build_schedules();

function Defaults(){
	$task=new system_tasks();
	if($GLOBALS["VERBOSE"]){echo "CheckDefaultSchedules()\n";}
	$task->CheckDefaultSchedules();
	build_schedules();
	
}

function build_schedules(){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already executed pid $pid",__FILE__,__FUNCTION__,__LINE__);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<1){
			writelogs("To short time to execute the process $pidTime = {$pidTimeINT}Mn < 1",__FILE__,__FUNCTION__,__LINE__);
			return;
		}
	}
	
	@file_put_contents($pidTime, time());
	
	
	
	if(!$q->TABLE_EXISTS("system_schedules","artica_backup")){$task->CheckDefaultSchedules();}
	
	$task=new system_tasks();
	$task->CheckDefaultSchedules();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(file_exists($squidbin)){
		$q=new mysql_squid_builder();
		$q->CheckDefaultSchedules();
	}
	
	
	
	if($q->COUNT_ROWS("system_schedules","artica_backup")==0){
		echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) system_schedules is empty !!\n";
		die();
	}
	
	
	$sql="SELECT * FROM system_schedules WHERE enabled=1";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) $q->mysql_error on line ". __LINE__."\n";
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
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) remove $filename\n";}
			@unlink($filename);}
	}
	@unlink("/etc/artica-postfix/TASKS_CACHE.DB");
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
	build_system_defaults();
	$me=__FILE__;
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];
		if($TaskType==0){continue;}
		if($ligne["TimeText"]==null){continue;}
		$md5=md5("$TimeText$TaskType");
		if(isset($alreadydone[$md5])){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog task {$ligne["ID"]} already set\n";}continue;}
		$alreadydone[$md5]=true;
		
		if(!isset($task->tasks_processes[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) Unable to stat task process of `$TaskType`\n";}
			continue;
		}
		
		if(isset($task->task_disabled[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix`$TaskType` disabled\n";}
			continue;
		}
		
		$script=$task->tasks_processes[$TaskType];
		
		
		
		$f=array();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." scheduling $script /etc/cron.d/syssch-{$ligne["ID"]}\n";} 
		$cmdline=trim("$nice $php5 $me --run {$ligne["ID"]}");
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["TimeText"]}  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/syssch-{$ligne["ID"]}", @implode("\n", $f));
	
	}
	
	shell_exec("/etc/init.d/cron reload");
	
}

function build_system_defaults(){
	
	$unix=new unix();
	$sock=new sockets();
	$nice=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$ArticaBackupEnabled=intval($sock->GET_INFO("ArticaBackupEnabled"));
	$users=new usersMenus();
	@unlink("/etc/cron.d/artica-cron-backup");
	@unlink("/etc/cron.d/artica-cron-pflogsumm");
	
	if(is_file('/etc/artica-postfix/artica-backup.conf')){
		if($ArticaBackupEnabled==1){
			$ini=new Bs_IniHandler();
			$ini->loadFile('/etc/artica-postfix/artica-backup.conf');
			if(!isset($ini->_params["backup"]["backup_time"])){$ini->_params["backup"]["backup_time"]="03:00";}
			if(preg_match("#([0-9]+):([0-9]+)#", $ini->_params["backup"]["backup_time"],$re)){
				$backup_hour=intval($re[1]);
				$backup_min=intval($re[2]);
				$f[]="MAILTO=\"\"";
				$f[]="$backup_min $backup_hour * * * root $nice /usr/share/artica-postfix/bin/artica-backup --backup >/dev/null 2>&1";
				$f[]="";
				@file_put_contents("/etc/cron.d/artica-cron-backup", @implode("\n", $f));
				$f=array();
			}
		}
	}
	
	if(is_file('/etc/artica-postfix/settings/Daemons/pflogsumm')){
		$ini=new Bs_IniHandler();
		$ini->loadFile('/etc/artica-postfix/settings/Daemons/pflogsumm');
		$schedule_time=trim($ini->_params['SETTINGS']['schedule']);
		if ($schedule_time<>null){
			$f[]="MAILTO=\"\"";
			$f[]="$schedule_time root $nice $php /usr/share/artica-postfix/exec.postfix.reports.php >/dev/null 2>&1";
			$f[]="";
			@file_put_contents("/etc/cron.d/artica-cron-pflogsumm", @implode("\n", $f));
			$f=array();
		}	
	}
	
	$prefix="/usr/share/artica-postfix";
	$f=array();
	$f[]="MAILTO=\"\"";
	$f[]="@reboot root $nice /sbin/modprobe cifs && echo 0 > /proc/fs/cifs/OplockEnabled >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/cifs-fix", @implode("\n", $f));
	$f=array();

	$f[]="MAILTO=\"\"";
	$f[]="@reboot root $nice $php $prefix/exec.schedules.php >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/schedules", @implode("\n", $f));
	$f=array();	
	
	

	
	$f[]="MAILTO=\"\"";
	$f[]="7,14,21,28,35,42,49,56 0 * * * * root $nice $php $prefix/exec.dnsmasq.php --varrun >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/artica-dnsmasqrun", @implode("\n", $f));
	$f=array();
	
	$f[]="MAILTO=\"\"";
	$f[]="10,34,51 0 * * * * root $nice $php $prefix/exec.watchdog.php --monit >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/artica-dnsmasqrun", @implode("\n", $f));
	$f=array();	
	
	$f[]="MAILTO=\"\"";
	$f[]="0,2,4,6,8,10,12,14,16,18,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,58 * * * * root $nice $php $prefix/exec.parse-orders.php >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/artica-parseorders", @implode("\n", $f));
	$f=array();
	
	if($users->spamassassin_installed){
		$f[]="MAILTO=\"\"";
		$f[]="10 3,6,9,12,15,18,21,23 * * * root $nice $php $prefix/exec.sa-learn-cyrus.php --execute >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-salearn-cyrus", @implode("\n", $f));
		$f=array();
	}
	

	if($users->fetchmail_installed){
		$f[]="MAILTO=\"\"";
		$f[]="0,2,4,6,8,10,12,14,16,18,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,58 * * * * root $nice $php $prefix/exec.fetchmail.sql.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-ftechmailsql", @implode("\n", $f));
		$f=array();
	}
}



function execute_task($ID){
	$unix=new unix();
	$tasks=new system_tasks();
	$php5=$unix->LOCATE_PHP5_BIN();
	$pgrep=$unix->find_program("pgrep");
	$GLOBALS["SCHEDULE_ID"]=$ID;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ID.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeProcess=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("$pid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);
		return;
	}
	
	
	
	
	@unlink($pidfile);
	@file_put_contents($lockfile, "#");
	@file_put_contents($pidfile, getmypid());
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];	

	$TASKS_CACHE=unserialize(@file_get_contents("/etc/artica-postfix/TASKS_CACHE.DB"));

	if(isset($TASKS_CACHE[$ID])){
		$TaskType=$TASKS_CACHE[$ID]["TaskType"];
		if(isset($task->task_disabled[$TaskType])){
			writelogs("Task $ID is disabled",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	
	if(!isset($TASKS_CACHE[$ID])){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM system_schedules WHERE ID=$ID","artica_backup"));
		$TaskType=$ligne["TaskType"];
		$TASKS_CACHE[$ID]["TaskType"]=$ligne["TaskType"];
		@file_put_contents("/etc/artica-postfix/TASKS_CACHE.DB", serialize($TASKS_CACHE));
	}	
	if($TaskType==0){return;}
	if(!isset($tasks->tasks_processes[$TaskType])){system_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return;}
	if(isset($task->task_disabled[$TaskType])){return;}
	$script=$tasks->tasks_processes[$TaskType];
	
	
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$php5 $WorkingDirectory/$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$WorkingDirectory/bin/{$re[1]}";}
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} will be executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	$t=time();
	$unix->THREAD_COMMAND_SET($cmd);	
	system_admin_events("Task is scheduled `$cmd`" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);
	
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
	$cmd="$php5 $WorkingDirectory/$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$WorkingDirectory/bin/{$re[1]}";}	
	
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} is scheduled with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);
	
	
}

function execute_task_squid($ID){
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCHEDULE_ID"]=$ID;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ID.squid.pid";
	
	$sock=new sockets();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){return ;}
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];		
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeProcess=$unix->PROCCESS_TIME_MIN($pid);
		ufdbguard_admin_events("$pid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);
		return;
	}
	
	$pidtime=$unix->file_time_min($pidfile);
	if($pidtime<1){
		ufdbguard_admin_events("last execution was done since {$pidtime}mn" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);
		return;
	}
	
	
		
	$TASKS_CACHE=unserialize(@file_get_contents("/etc/artica-postfix/TASKS_SQUID_CACHE.DB"));
	if(isset($TASKS_CACHE[$ID])){
		$TaskType=$TASKS_CACHE[$ID]["TaskType"];
		if(isset($q->tasks_disabled[$TaskType])){
			writelogs("Task $ID is disabled",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}	
	
	
	usleep(rand(900, 3000));
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());


	
	if(!isset($TASKS_CACHE[$ID])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID"));
		$TaskType=$ligne["TaskType"];
		$TASKS_CACHE[$ID]["TaskType"]=$ligne["TaskType"];
		@file_put_contents("/etc/artica-postfix/TASKS_SQUID_CACHE.DB", serialize($TASKS_CACHE));		
	}
	if($TaskType==0){continue;}	
	if(!isset($q->tasks_processes[$TaskType])){ufdbguard_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);return;}
	if(isset($q->tasks_disabled[$TaskType])){ufdbguard_admin_events("Task type `$TaskType` is disabled" , __FUNCTION__, __FILE__, __LINE__, "tasks",$ID);return;}
	$script=$q->tasks_processes[$TaskType];
	
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$php5 $WorkingDirectory/$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$WorkingDirectory/bin/{$re[1]}";}
	
	ufdbguard_admin_events("Task {$GLOBALS["SCHEDULE_ID"]} will be scheduled with `$cmd` ", __FUNCTION__, __FILE__, __LINE__, "scheduler",$ID);
	$unix->THREAD_COMMAND_SET($cmd);
	
}

function isMaxInstances(){
	
	$MaxInstnaces=11;
	$MaxInstancesToDie=16;
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$p=new processes_php();
	$MemoryInstances=$p->MemoryInstances();
	if(!is_numeric($MemoryInstances)){$MemoryInstances=0;}
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} -> $MemoryInstances instances...",__FUNCTION__,__FILE__,__LINE__);
	if($MemoryInstances>$MaxInstancesToDie){
		writelogs("Task {$GLOBALS["SCHEDULE_ID"]} -> too much instances ($MemoryInstances) die ".@implode(",", $GLOBALS["INSTANCES_EXECUTED"]),__FUNCTION__,__FILE__,__LINE__);
		return true;
	}
	
	if($MemoryInstances>$MaxInstnaces){
		ufdbguard_admin_events("Too much instances ($MemoryInstances Max:$MaxInstnaces)" , __FUNCTION__, __FILE__, __LINE__, "tasks");
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
				unix_system_kill_force($pid);
			}
		}
		
	}
	
	
	
}


