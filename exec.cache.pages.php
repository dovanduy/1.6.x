<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--squidlogs"){squidlogs_status();exit;}
start();

function start(){

	$workdir="/usr/share/artica-postfix/ressources/logs/web";
	$status_path="$workdir/admin.index.status.html";
	$notify_path="$workdir/admin.index.notify.html";
	$unix=new unix();
	$users=new usersMenus();
	$sock=new sockets();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid)){
		echo "Starting......: cache manager, ". __FUNCTION__."() already running PID:$oldpid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());


	$php5=$unix->LOCATE_PHP5_BIN();
	$timeMinutefile=$unix->file_time_min($status_path);
	if($GLOBALS["VERBOSE"]){echo "{$timeMinutefile}mn...\n";} 
	if($timeMinutefile<2){
		if($GLOBALS["VERBOSE"]){echo "Aborting....\n";}
		return;
	}
	@unlink($status_path);
	@unlink($notify_path);
	if($GLOBALS["VERBOSE"]){echo "/admin.index.php --status-right >$status_path\n";}
	shell_exec("$php5 /usr/share/artica-postfix/admin.index.php --status-right >$status_path 2>&1");
	$unix->chmod_func(0777, $status_path);
	$unix->chmod_func(0777, "$workdir/admin.index.tabs.html");
	$unix->chmod_func(0777, "$workdir/admin.index.memory.html");
	shell_exec("$php5 /usr/share/artica-postfix/admin.top.menus.php update-white-32-tr >$notify_path 2>&1");
	$unix->chmod_func(0777, $notify_path);
	
	
	$AsSquid=false;
	if($users->SQUID_INSTALLED){$AsSquid=true;}
	if($users->WEBSTATS_APPLIANCE){$AsSquid=true;}	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){$AsSquid=false;}
	if($EnableWebProxyStatsAppliance==1){$AsSquid=true;}
	
	squidlogs_status(true);
	
	if($AsSquid){
		
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/traffic.statistics.html";
		shell_exec("$php5 /usr/share/artica-postfix/squid.traffic.statistics.php squid-status-stats >$cachefile 2>&1");
		$unix->chmod_func(0777, $cachefile);
	}
}

function squidlogs_status($nopid=false){
	
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squidlogs.stats";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		if($unix->process_exists($oldpid)){
			echo "Starting......: cache manager, ". __FUNCTION__."() already running PID:$oldpid\n";
			return;
		}
		@file_put_contents($pidfile,getmypid());
	}
	
	if($GLOBALS["FORCE"]){@unlink($cachefile);}
	
	if(is_file($cachefile)){
		$time=$unix->file_time_min($cachefile);
		if($time<20){return;}
	}
	
	$df=$unix->find_program("df");
	$sock=new sockets();
	$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
	if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
	
	
	if(count(glob("$MYSQL_DATA_DIR/squidlogs/*"))>0){
		if(!is_link("$MYSQL_DATA_DIR/squidlogs")){$realFolder="$MYSQL_DATA_DIR/squidlogs";}else{
			$realFolder=readlink("$MYSQL_DATA_DIR/squidlogs");
		}
	
	
		exec("$df -h $realFolder 2>&1",$results);
		$foldersize=$unix->DIRSIZE_BYTES($realFolder);
		while (list ($num, $line) = each ($results)){
			if(!preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%#", $line,$re)){continue;}
			$array["squidlogs"]["DEV"]=$re[1];
			$array["squidlogs"]["SIZE"]=$re[2];
			$array["squidlogs"]["OC"]=$re[3];
			$array["squidlogs"]["DISP"]=$re[4];
			$array["squidlogs"]["POURC"]=$re[5];
			$array["squidlogs"]["REALPATH"]=$realFolder;
			$array["squidlogs"]["PATHSIZE"]=$foldersize;
			$array["squidlogs"]["TIME"]=time();
		}	
	

	}
	if(count(glob("$MYSQL_DATA_DIR/syslogstore/*"))>0){
		if(!is_link("$MYSQL_DATA_DIR/syslogstore")){$realFolder="$MYSQL_DATA_DIR/syslogstore";}else{
			$realFolder=readlink("$MYSQL_DATA_DIR/syslogstore");
		}
	
	
		exec("$df -h $realFolder 2>&1",$results);
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
	
	
	}	
	
	@unlink($cachefile);
	@file_put_contents($cachefile, serialize($array));
	$unix->chmod_func(0777, $cachefile);	
	
}
