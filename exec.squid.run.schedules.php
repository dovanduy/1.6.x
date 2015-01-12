<?php
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.process.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.automatic-tasks.inc");

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}


$unix=new unix();
if(!is_file($unix->LOCATE_SQUID_BIN())){die();}
$sock=new sockets();
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["KILLBIN"]=$unix->find_program("kill");
$GLOBALS["RMBIN"]=$unix->find_program("rm");
$GLOBALS["SYNCBIN"]=$unix->find_program("sync");
$GLOBALS["ECHOBIN"]=$unix->find_program("echo");
$GLOBALS["NMAPBIN"]=$unix->find_program("nmap");
$GLOBALS["SquidRotateOnlySchedule"]=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
$GLOBALS["SQUID_BIN"]=$unix->LOCATE_SQUID_BIN();



squid_running_schedules();

function squid_running_schedules(){
	
	$TimeFile="/etc/artica-postfix/pids/exec.squid.run.schedules.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.run.schedules.php.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		_statussquid("$pid already executed since {$timepid}Mn");
		if($timepid<5){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<4){
			_statussquid("Current {$time}Mn need 5Mn");
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	
	$BASEDIR="/usr/share/artica-postfix";
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(function_exists("systemMaxOverloaded")){
		if(systemMaxOverloaded()){
			_statussquid("Overloaded system, aborting...");
			return;}
	}
	
	if($SQUIDEnable==0){return;}
	_statussquid("squid_running_schedules");
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.logfile_daemon-parse.php --tables-primaires >/dev/null 2>&1 &");
	
	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php >/dev/null 2>&1 &");
	}
	
	
	$filetimeF="/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/dansguardian2.mainrules.php rules-toolbox-left >/dev/null 2>&1 &");
	}
	
	
	$filetimeF="/usr/share/artica-postfix/ressources/logs/web/squid_mem_status.html";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --mem-status >/dev/null 2>&1 &");
	}
	
	$filetimeF="/usr/share/artica-postfix/ressources/logs/web/SQUID_MGR_INFO.DB";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>15){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --info >/dev/null 2>&1 &");
	}
	
	$filetimeF="/usr/share/artica-postfix/ressources/logs/web/squid_stores_status.html";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>20){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --store-status >/dev/null 2>&1 &");
	}
	
	
	$filetimeF='/etc/artica-postfix/pids/Winbindd_privileged_SQUID.time';
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		$GLOBALS["CLASS_UNIX"]->Winbindd_privileged_SQUID();
		@unlink($filetimeF);
		@file_put_contents($filetimeF, time());
	}
	
	$filetimeF='/etc/artica-postfix/pids/EnableKerbAuth.time';
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==1){
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>5){
			@unlink($filetimeF);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.kerbauth.php --pinglic >/dev/null 2>&1 &");
			@file_put_contents($filetimeF, time());
		}
	}
	
	$filetimeF='/etc/artica-postfix/pids/DisableGoogleSSL.time';
	$DisableGoogleSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableGoogleSSL"));
	if($DisableGoogleSSL==1){
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($GLOBALS["CLASS_UNIX"]->file_time_min($filetime)>4320){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.nosslsearch.google.com.php --run >/dev/null 2>&1 &");
			@unlink($filetimeF);
			@file_put_contents($filetimeF, time());
		}
	}
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.hours.php.RTTZ_WORKSHOURS.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>60){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.hours.php --rtt >/dev/null 2>&1 &");
	}
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.hours.php.tables_hours.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>60){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.hours.php >/dev/null 2>&1 &");
	}	
	
		
	$filetimeF="/etc/artica-postfix/pids/exec.squid.php.Defaultschedules.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.php --defaults-schedules");
	}
	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.central.php.import.statistics.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		@unlink("/etc/artica-postfix/pids/exec.squid.stats.central.php.import.statistics.time");
		@file_put_contents("/etc/artica-postfix/pids/exec.squid.stats.central.php.import.statistics.time", time());
		stats_admin_events(2, "Launching importation tables task", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.central.php --import");
		
	}
	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.not-categorized.php.not_categorized_scan.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.not-categorized.php --recategorize >/dev/null 2>&1 &");
	}
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.totals.php.donnees_interface.pid";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>30){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.totals.php --interface >/dev/null 2>&1 &");
	}
	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.interface-size.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>14){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.interface-size.php >/dev/null 2>&1 &");
	}	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.quota-week.parser.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>1880){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.quota-week.parser.php >/dev/null 2>&1 &");
	}
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.mime.parser.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>19){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.mime.parser.php >/dev/null 2>&1 &");
	}

	$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.mime.proto.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>19){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.mime.proto.php >/dev/null 2>&1 &");
	}
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.current_access_db.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>9){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.hourly.tables.php --current_access >/dev/null 2>&1 &");
	}

	$filetimeF="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>64){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.hourly.tables.php >/dev/null 2>&1 &");
	}	
	
	
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.quotaday.php.start.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>61){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.quotaday.php >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.quotaday.php.quotatemp.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>61){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.quotaday.php --quotatemp >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.squid-searchwords.php.searchwords_hour.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>61){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid-searchwords.php --hour >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/YoutubeByHour.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>61){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.youtube.days.php --youtube-hours >/dev/null 2>&1 &");
	}
	
	
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.notcached-week.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>30){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.notcached-week.php >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.protos.php.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.protos.php >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.squid.php.rotate_logs.pid";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>60){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.php --rotate >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.squid.rotate.php.build.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.rotate.php >/dev/null 2>&1 &");
	}
	$SquidEnforceRules=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidEnforceRules"));
	if($SquidEnforceRules==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squidcache.php >/dev/null 2>&1 &");
	}
	
	$timefile="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>10){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --redirector-array >/dev/null 2>&1 &");
	}
	
	$timefile="/etc/artica-postfix/pids/exec.dansguardian.injector.php.ParseAllUfdbs.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.dansguardian.injector.php --blocked >/dev/null 2>&1 &");
	}
	
	
}

function squid_tasks(){
	if(system_is_overloaded()){squid_tasks_events("Overloaded system, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	
		$time_start = microtime(true);
		squid_tasks_events("Invoke squid_auto_tasks()",__FUNCTION__,__FILE__,__LINE__);
		$t=new squid_auto_tasks();
		$time_end = microtime(true);
		$time_calc = $time_end - $time_start;
		_statussquid("Running squid_tasks {$time_calc}ms");
}


function squid_tasks_events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	$function=__CLASS__."/".$function;
	$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-scheduler-squid.log",false,$function,$line,$filename);
}





function shell_exec2($cmdline){
	$cmdline=str_replace("/usr/share/artica-postfix/ressources/exec.","/usr/share/artica-postfix/exec.",$cmdline);

	if(function_exists("debug_backtrace")){


		$trace=debug_backtrace();
		if(isset($trace[0])){
			$T_FUNCTION=$trace[0]["function"];
			$T_LINE=$trace[0]["line"];
			$T_FILE=basename($trace[0]["file"]);
		}


		if(isset($trace[1])){
			$T_FUNCTION=$trace[1]["function"];
			$T_LINE=$trace[1]["line"];
			$T_FILE=basename($trace[1]["file"]);
		}


	}


	if(!isset($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	if(!is_array($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	$md5=md5($cmdline);
	$time=date("YmdHi");
	if(isset($GLOBALS["shell_exec2"][$time][$md5])){
		if($GLOBALS["VERBOSE"]){echo "ERROR ALREADY EXECUTED $cmdline\n";}
		return;
	}
	if(count($GLOBALS["shell_exec2"])>5){$GLOBALS["shell_exec2"]=array();}
	$GLOBALS["shell_exec2"][$time][$md5]=true;


	if(!preg_match("#\/nohup\s+#",$cmdline)){
		$cmdline="{$GLOBALS["nohup"]} $cmdline";
	}
	if(!preg_match("#\s+>\/.*?2>\&1#",$cmdline)){
		if(!preg_match("#\&$#",$cmdline)){
			$cmdline="$cmdline >/dev/null 2>&1 &";
		}
	}

	if($GLOBALS["VERBOSE"]){echo "******************* EXEC ********************************\n$cmdline\n********************************\n";}
	if(!$GLOBALS["VERBOSE"]){_statussquid("$T_FILE:$T_FUNCTION:$T_LINE:Execute: $cmdline",__FUNCTION__,__LINE__);}
	shell_exec($cmdline);

}
function _statussquid($text=null){
	if(!isset($GLOBALS["MIPIDSQUID"])){$GLOBALS["MIPIDSQUID"]=getmypid();}

	$TIME=date("M d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$logFile="/var/log/artica-scheduler-squid.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$TIME [{$GLOBALS["MIPIDSQUID"]}] $text\n");
	@fclose($f);
}

