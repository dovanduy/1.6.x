<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--squidlogs"){squidlogs_status();exit;}
start();

function start(){

	if(system_is_overloaded()){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cache manager, ". __FUNCTION__."() overloaded system\n";}
		return;
	}
	
	$workdir="/usr/share/artica-postfix/ressources/logs/web";
	$status_path="$workdir/admin.index.status.html";
	$notify_path="$workdir/admin.index.notify.html";
	$unix=new unix();
	$users=new usersMenus();
	$sock=new sockets();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timePid=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." cache manager, ". __FUNCTION__."() already running PID:$pid since {$timePid}Mn\n";
		if($timePid<10){RETURN;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($timePid);
		
	}
	@file_put_contents($pidfile,getmypid());


	$php5=$unix->LOCATE_PHP5_BIN();
	$timeMinutefile=$unix->file_time_min($status_path);
	if($GLOBALS["VERBOSE"]){echo "{$timeMinutefile}mn...\n";}
	if(!$GLOBALS["FORCE"]){ 
		if($timeMinutefile<2){
			if($GLOBALS["VERBOSE"]){echo "Aborting....\n";}
			return;
		}
	}
	@unlink($status_path);
	@unlink($notify_path);
	$EXEC_NICE=$unix->EXEC_NICE();
	if($GLOBALS["VERBOSE"]){echo "$EXEC_NICE $php5 /usr/share/artica-postfix/admin.index.php --status-right >$status_path 2>&1\n";}
	shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/admin.index.php --status-right >$status_path 2>&1");
	$unix->chmod_func(0777, $status_path);
	$unix->chmod_func(0777, "$workdir/admin.index.tabs.html");
	$unix->chmod_func(0777, "$workdir/admin.index.memory.html");
	if($GLOBALS["VERBOSE"]){echo __LINE__." /admin.top.menus.php update-white-32-t >$notify_path\n";}
	shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/admin.top.menus.php update-white-32-tr >$notify_path 2>&1");
	$unix->chmod_func(0777, $notify_path);
	if($GLOBALS["VERBOSE"]){echo __LINE__." /$php5 /usr/share/artica-postfix/admin.index.loadvg.php >/dev/null 2>&1\n";}
	shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/admin.index.loadvg.php >/dev/null 2>&1");
	shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/admin.index.status-infos.php >/dev/null 2>&1");
	shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/admin.index.right-image.php >/dev/null 2>&1");
	
	
	
	$AsSquid=false;
	if($users->SQUID_INSTALLED){$AsSquid=true;}
	if($users->WEBSTATS_APPLIANCE){$AsSquid=true;}	
	
	if($GLOBALS["VERBOSE"]){echo "GET_INFO('EnableRemoteStatisticsAppliance')\n";}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if($GLOBALS["VERBOSE"]){echo "EnableRemoteStatisticsAppliance= $EnableRemoteStatisticsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "GET_INFO('EnableWebProxyStatsAppliance')\n";}
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance= $EnableWebProxyStatsAppliance\n";}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){$AsSquid=false;}
	if($EnableWebProxyStatsAppliance==1){$AsSquid=true;}
	
	if($GLOBALS["VERBOSE"]){echo __LINE__." squidlogs_status()\n";}
	squidlogs_status(true);
	if($GLOBALS["VERBOSE"]){echo __LINE__." squidlogs_status() -> DONE\n";}
	if($AsSquid){
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/traffic.statistics.html";
		if($GLOBALS["VERBOSE"]){echo __LINE__." $php5 /usr/share/artica-postfix/squid.traffic.statistics.php squid-status-stats >$cachefile\n";}
		shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/squid.traffic.statistics.php squid-status-stats >$cachefile 2>&1");
		$unix->chmod_func(0777, $cachefile);
		shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/squid.main.quicklinks.php --squid-status >/dev/null 2>&1");
		shell_exec("$EXEC_NICE $php5 /usr/share/artica-postfix/dansguardian2.php --dansguardian-status >/dev/null 2>&1");
	}
	if($GLOBALS["VERBOSE"]){echo __LINE__." ".__FUNCTION__." finish OK\n";}
}

function squidlogs_status($nopid=false){
	$sock=new sockets();
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squidlogs.stats";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$q=new mysql_squid_builder();
	
	if(!$nopid){
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){
			echo "Starting......: ".date("H:i:s")." cache manager, ". __FUNCTION__."() already running PID:$pid\n";
			return;
		}
		@file_put_contents($pidfile,getmypid());
	}
	
	if($GLOBALS["FORCE"]){@unlink($cachefile);}
	
	if(is_file($cachefile)){
		$time=$unix->file_time_min($cachefile);
		if($time<20){
			if($GLOBALS["VERBOSE"]){echo "{$time}mn require 20mn\n";}
			return;}
	}
	
	$df=$unix->find_program("df");
	$sock=new sockets();
	$MYSQL_DATA_DIR=$q->MYSQL_DATA_DIR;
	if(is_dir("/opt/squidsql")){$MYSQL_DATA_DIR="/opt/squidsql";}
	
	if($GLOBALS["VERBOSE"]){echo "MYSQL_DATA_DIR = $MYSQL_DATA_DIR\n";}
	if(!is_link("$MYSQL_DATA_DIR/$q->database")){$realFolder="$MYSQL_DATA_DIR";}else{
		$realFolder=readlink("$MYSQL_DATA_DIR");
	}
	
	$EXEC_NICE=$unix->EXEC_NICE();
	
	$cmdline="$EXEC_NICE$df -h $realFolder 2>&1";
	if($GLOBALS["VERBOSE"]){echo __LINE__." $cmdline\n";}
	exec("$df -h $realFolder 2>&1",$results);
	$foldersize=$unix->DIRSIZE_BYTES($realFolder);
	while (list ($num, $line) = each ($results)){
			if(!preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%#", $line,$re)){
				if($GLOBALS["VERBOSE"]){echo "$line, no match\n";}
				continue;}
			$array["squidlogs"]["DEV"]=$re[1];
			$array["squidlogs"]["SIZE"]=$re[2];
			$array["squidlogs"]["OC"]=$re[3];
			$array["squidlogs"]["DISP"]=$re[4];
			$array["squidlogs"]["POURC"]=$re[5];
			$array["squidlogs"]["REALPATH"]=$realFolder;
			$array["squidlogs"]["PATHSIZE"]=$foldersize;
			$array["squidlogs"]["TIME"]=time();
		}	
	

	
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	$MySQLSyslogWorkDir=$sock->GET_INFO("MySQLSyslogWorkDir");
	if($MySQLSyslogWorkDir==null){$MySQLSyslogWorkDir="/home/syslogsdb";}
	$q=new mysql_storelogs();
	$MYSQL_DATA_DIR=$MySQLSyslogWorkDir;
	
	
	
	
	if(!is_link("$MYSQL_DATA_DIR")){$realFolder="$MYSQL_DATA_DIR";}else{
		$realFolder=readlink("$MYSQL_DATA_DIR");
	}
	
	
	exec("$df -h $realFolder 2>&1",$results);
	if($GLOBALS["VERBOSE"]){echo __LINE__." $cmdline\n";}
	$foldersize=$unix->DIRSIZE_BYTES($realFolder);
	while (list ($num, $line) = each ($results)){
			if(!preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%#", $line,$re)){continue;}
			$array["syslogstore"]["DEV"]=$re[1];
			$array["syslogstore"]["SIZE"]=$re[2];
			$array["syslogstore"]["OC"]=$re[3];
			$array["syslogstore"]["DISP"]=$re[4];
			$array["syslogstore"]["POURC"]=$re[5];
			$array["syslogstore"]["REALPATH"]=$realFolder;
			$array["syslogstore"]["PATHSIZE"]=$foldersize;
			$array["syslogstore"]["TIME"]=time();
		}
	
	
		
	print_r($array);
	@unlink($cachefile);
	
	@file_put_contents($cachefile, serialize($array));
	$unix->chmod_func(0777, $cachefile);
	if($GLOBALS["VERBOSE"]){echo __LINE__." ".__FUNCTION__." finish OK\n";}	
	
}
