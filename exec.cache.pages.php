<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

start();

function start(){

$workdir="/usr/share/artica-postfix/ressources/logs/web";
$status_path="/usr/share/artica-postfix/ressources/logs/web/admin.index.status.html";
$notify_path="/usr/share/artica-postfix/ressources/logs/web/admin.index.notify.html";
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
	if($timeMinutefile<2){return;}
	shell_exec("$php5 /usr/share/artica-postfix/admin.index.php --status-right >$status_path 2>&1");
	$unix->chmod_func(0777, $status_path);
	$unix->chmod_func(0777, "$workdir/admin.index.tabs.html");
	$unix->chmod_func(0777, "$workdir/admin.index.memory.html");
	shell_exec("$php5 /usr/share/artica-postfix/admin.top.menus.php update-white-32-tr >$notify_path 2>&1");
	$unix->chmod_func(0777, "$workdir/admin.index.notify.html");
	
	
	$AsSquid=false;
	if($users->SQUID_INSTALLED){$AsSquid=true;}
	if($users->WEBSTATS_APPLIANCE){$AsSquid=true;}	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){$AsSquid=false;}
	if($EnableWebProxyStatsAppliance==1){$AsSquid=true;}
	
	if($AsSquid){
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/traffic.statistics.html";
		shell_exec("$php5 /usr/share/artica-postfix/squid.traffic.statistics.php squid-status-stats >$cachefile 2>&1");
		$unix->chmod_func(0777, $cachefile);
	}
	
	

}