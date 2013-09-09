<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#reconfigure-count=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE_COUNT"]=$re[1];}

if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--dump#",implode(" ",$argv),$re)){$GLOBALS["DUMP"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["NO_KILL_MYSQL"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
$GLOBALS["STAMP_MAX_RESTART_TTL"]="/etc/artica-postfix/STAMP_MAX_RESTART_TTL";
$GLOBALS["STAMP_MAX_PING"]="/etc/artica-postfix/SQUID_STAMP_MAX_PING";
$GLOBALS["STAMP_FAILOVER"]="/etc/artica-postfix/SQUID_FAILOVER";
$GLOBALS["STAMP_REBOOT"]="/etc/artica-postfix/SQUID_REBOOT";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');


	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_squid();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_squid();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_squid();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload_squid();die();}
	if($argv[1]=="--squidz"){$GLOBALS["OUTPUT"]=true;squidz();die();}
	if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd_squid();die();}
	if($argv[1]=="--tests-smtp"){$GLOBALS["OUTPUT"]=true;test_smtp_watchdog();die();}
	if($argv[1]=="--swapstate"){$GLOBALS["OUTPUT"]=true;$GLOBALS["SWAPSTATE"]=true;restart_squid();die();}
	if($argv[1]=="--storedirs"){$GLOBALS["OUTPUT"]=true;CheckStoreDirs();die();}
	if($argv[1]=="--memboosters"){$GLOBALS["OUTPUT"]=true;MemBoosters();die();}
	if($argv[1]=="--swap"){$GLOBALS["OUTPUT"]=true;SwapCache();die();}
	if($argv[1]=="--counters"){$GLOBALS["OUTPUT"]=true;counters();die();}
	if($argv[1]=="--peer-status"){$GLOBALS["OUTPUT"]=true;peer_status();die();}
	if($argv[1]=="--dead-parent"){peer_dead($argv[2]);die();}
	if($argv[1]=="--ping"){PING_GATEWAY();die();}
	if($argv[1]=="--ntlmauthenticator"){ntlmauthenticator();die();}
	if($argv[1]=="--ntlmauthenticator-edit"){ntlmauthenticator_edit($argv[2]);die();}
	if($argv[1]=="--logs"){CheckOldCachesLog();die();}
	if($argv[1]=="--DeletedCaches"){DeletedCaches();die();}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "start_watchdog()\n";}
	start_watchdog();
	
function PING_GATEWAY_DEFAULT_PARAMS($MonitConfig){
	if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=1;}
	if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=1;}
	if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
	if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=1;}
	if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=1;}
	if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}	
	return $MonitConfig;
}


function watchdog_config_default($MonitConfig){
	if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!isset($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!isset($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!isset($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if(!isset($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!isset($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	if(!isset($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!isset($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!isset($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!isset($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	
	if(!isset($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	
	
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if(!is_numeric($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!is_numeric($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}

	if(!is_numeric($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
	if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	
	if(!is_numeric($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!is_numeric($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!is_numeric($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	
	
	
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
	if($MonitConfig["MIN_INTERVAL"]<3){$MonitConfig["MIN_INTERVAL"]=3;}
	if($MonitConfig["MaxSwapPourc"]<5){$MonitConfig["MaxSwapPourc"]=5;}
	if($MonitConfig["DNSIssuesMAX"]<1){$MonitConfig["DNSIssuesMAX"]=1;}
	if($MonitConfig["REBOOT_INTERVAL"]<10){$MonitConfig["REBOOT_INTERVAL"]=10;}
	if($MonitConfig["MinTimeFailOverSwitch"]<5){$MonitConfig["MinTimeFailOverSwitch"]=5;}
	
	
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if(!isset($MonitConfig["EnableFailover"])){
		$sock=new sockets();
		$MonitConfig["EnableFailover"]=$sock->GET_INFO("EnableFailover");
		if(!is_numeric($MonitConfig["EnableFailover"])){$MonitConfig["EnableFailover"]=1;}
		
	}
	
	return $MonitConfig;	
}

	
function start_watchdog(){
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtimeNTP="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".ntp.time";
	
	$unix=new unix();
	$oldpid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($oldpid)){
		return;
	}
	
	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	$GLOBALS["EnableFailover"]=$sock->GET_INFO("EnableFailover");
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}	
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	
	if($SQUIDEnable==0){die();}
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig["EnableFailover"]=$EnableFailover;
	$MonitConfig=watchdog_config_default($MonitConfig);
	
	if(!$GLOBALS["VERBOSE"]){if($time<$MonitConfig["MIN_INTERVAL"]){return;}}
	
	$STAMP_MAX_RESTART_TIME=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART"]);
	if($STAMP_MAX_RESTART_TIME>60){@unlink($GLOBALS["STAMP_MAX_RESTART"]);}

	
	//Events("Start: ". basename($pidtime).":{$time}Mn / {$MonitConfig["MIN_INTERVAL"]}Mn STAMP_MAX_RESTART_TIME={$STAMP_MAX_RESTART_TIME}Mn");
	@file_put_contents($pidtime,time());
	
	if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
		shell_exec("$nohup $php ".dirname(__FILE__)."/exec.squid.php --tpl-save >/dev/null 2>&1 &");
		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "Checks_mgrinfos()\n";}
	Checks_mgrinfos($MonitConfig);
	ntlmauthenticator();
	CheckOldCachesLog();
	DeletedCaches();
	
	if($MonitConfig["watchdog"]==0){
		if($GLOBALS["VERBOSE"]){echo "Watchdog is disabled...\n";}
		counters(true);
		return;
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "PING_GATEWAY()\n";}
	PING_GATEWAY();
	if($GLOBALS["VERBOSE"]){echo "Checks_Winbindd()\n";}
	Checks_Winbindd();
	if($GLOBALS["VERBOSE"]){echo "CheckStoreDirs()\n";}
	CheckStoreDirs();
	if($GLOBALS["VERBOSE"]){echo "MemBoosters()\n";}
	MemBoosters();
	if($GLOBALS["VERBOSE"]){echo "SwapCache()\n";}
	SwapCache($MonitConfig);
	if($GLOBALS["VERBOSE"]){echo "counters()\n";}
	counters(true);
	
	if($NtpdateAD==1){
		$pidtimeNTPT=$unix->file_time_min($pidtimeNTP);
		if($pidtimeNTPT>120){
			if($GLOBALS["VERBOSE"]){echo "/usr/share/artica-postfix/exec.kerbauth.php --ntpdate\n";}
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");
			@unlink($pidtimeNTP);
			@file_put_contents($pidtimeNTP, time());
		}
	}
	
	
}

function swap_state(){
	$unix=new unix();
	$caches=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($directory, $type) = each ($caches)){
		if(strtolower($type)=="rock"){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: scanning cache $directory\n";}
		foreach (glob("$directory/swap.*") as $filename) {
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: removing $filename\n";}
			@unlink($filename);	
		}
	}
}

function CheckOldCachesLog(){
	
	@mkdir("/home/squid/cache-logs",0755,true);
	$unix=new unix();
	foreach (glob("/var/log/squid/cache.log.*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Move $filename to /home/squid/cache-logs\n";}
		Events("Move $filename to /home/squid/cache-logs");
		@copy($filename, "/home/squid/cache-logs/".basename($filename));
		@unlink($filename);
	}
	
	foreach (glob("/home/squid/cache-logs/*") as $filename) {
		$ext=$unix->file_ext($filename);
		if(is_numeric($ext)){
			Events("Compress $filename to $filename.gz");
			if($unix->compress($filename, "$filename.gz")){@unlink($filename);}
			continue;
		}
		
		if($ext=="gz"){
			$time=$unix->file_time_min($filename);
			if($GLOBALS["VERBOSE"]){echo "$filename  = {$time}Mn\n";}
			if($time>4320){
				Events("Remove $filename (exceed 3 days on disk...)");
				@unlink($filename);
				continue;
			}
		}
		
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "CheckOldCachesLog:: END\n";}
	
	
	
	
}


function CHEK_SYSTEM_LOAD(){
	$sock=new sockets();
	$unix=new unix();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));	
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($MonitConfig["MaxLoad"]==0){return;}
	if(!function_exists("sys_getloadavg")){return;}
	
	$array_load=sys_getloadavg();
	$internal_load=intval($array_load[0]);
	
	if($internal_load < $MonitConfig["MaxLoad"] ){return;}
		
	$notifymessage="System load $internal_load exceed {$MonitConfig["MaxLoad"]}";
	RESTARTING_SQUID_WHY($MonitConfig, $notifymessage);
	
	if($MonitConfig["MaxLoadFailOver"]==1){
		FailOverDown($notifymessage);
	}
	
	if($MonitConfig["MaxLoadReboot"]==0){return;}
	REBOOTING_SYSTEM();
	
	
}

function REBOOTING_SYSTEM(){
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$unix=new unix();
	
	$timex=$unix->file_time_min($GLOBALS["STAMP_REBOOT"]);
	if($timex < $MonitConfig["REBOOT_INTERVAL"]){
		Events("Cannot reboot, need to wait {$MonitConfig["REBOOT_INTERVAL"]}mn, current is {$timex}mn");
		return;
	}
	
	squid_admin_notifs("Reboot the system.", __FUNCTION__, __FILE__, __LINE__, "proxy");
	squid_admin_mysql(0,"Reboot the system.");
	$reboot=$unix->find_program("reboot");
	@unlink($GLOBALS["STAMP_REBOOT"]);
	@file_put_contents($GLOBALS["STAMP_REBOOT"], time());
	sleep(5);
	shell_exec($reboot);	
	
}

function reload_squid($aspid=false){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: Squid-cache, not installed\n";}return;}
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			system_admin_events("restart_squid::Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$pid=SQUID_PID();
	if(!$unix->process_exists($pid)){start(true);return;}
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
	squid_admin_mysql(2, "Reconfiguring squid-cache","$called");
	exec("$squidbin -k reconfigure",$results);
	
	
	while (list ($num, $ligne) = each ($results) ){
		if($GLOBALS["OUTPUT"]){echo "Restart.......: [INIT][{$GLOBALS["MYPID"]}]: Squid-cache, $ligne\n";}
		
	}
}

function ntlmauthenticator(){
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$unix=new unix();
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/ntlmauthenticator.cache";
	if(!$unix->isSquidNTLM()){
		if($GLOBALS["VERBOSE"]){echo "NOT an NTLM proxy\n";}
		@unlink($cacheFile);
		return;
	}
	$ADDNNEW=false;
	$datas=explode("\n",$unix->squidclient("ntlmauthenticator"));
	$CPU_NUMBER=1;
	$ARRAY=array();
	if($GLOBALS["VERBOSE"]){echo "Watchdog enabled: {$MonitConfig["watchdog"]}\n";}
	
	
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			continue;
		}
		
		if(preg_match("#number active: ([0-9]+) of ([0-9]+)#",$ligne,$re)){
			$Active=intval($re[1]);
			$Max=intval($re[2]);
			$prc=round(($Active/$Max)*100);
			$ARRAY[$CPU_NUMBER]=$prc;
		}
		
	}
	
	if(count($ARRAY)==0){return;}
	if($MonitConfig["watchdog"]==1){
		while (list ($CPU, $PRC) = each ($ARRAY) ){
			if($GLOBALS["VERBOSE"]){echo "CPU.$CPU = $PRC%\n";}
			Events("ntlmauthenticator: CPU.$CPU = $PRC%");
			$LOG[]="Instance on CPU $CPU is $PRC% used.";
			if($PRC>94){
				$NewMax=$Max+1;
				$ADDNNEW=true;
				squid_admin_notifs("Warning NTLM Authenticator on CPU $CPU reach 95%:\nArtica will increase your ntlm authenticator processes to $NewMax instances per CPU and reload the Proxy service\r\nCurrent status:\r\n".@implode("\r\n", $LOG), __FUNCTION__, __FILE__, __LINE__, "proxy");
				squid_admin_mysql(0,"NTLM Authenticator on CPU $CPU reach 95%",
				"Artica will increase your ntlm authenticator processes to $NewMax instances 
				per CPU and reload the Proxy service\r\nCurrent status:\r\n".@implode("\r\n", $LOG));
			}
		}
	}
	
	
	if($ADDNNEW){
		if($NewMax>0){
			$SquidClientParams=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams")));
			$SquidClientParams["auth_param_ntlm_children"]=$NewMax;
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams", base64_encode(serialize($SquidClientParams)));
			if(ntlmauthenticator_edit($NewMax)){
				$squid=$unix->LOCATE_SQUID_BIN();
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
				squid_admin_mysql(2, "NTLM: Reconfiguring squid-cache","$called");
				shell_exec("$squid -k reconfigure");
			}
		}
		
	}
	
	@file_put_contents($cacheFile, serialize($ARRAY));
	@chmod($cacheFile, 0755);
	
	
}

function ntlmauthenticator_edit($newvalue=0){
	if(!is_numeric($newvalue)){$newvalue=20;}
	if($newvalue<20){$newvalue=20;}
	$FOUND=false;
	$ARRAY=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($ARRAY) ){
		if(preg_match("#auth_param ntlm children ([0-9]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "{$ARRAY[$index]}\n";}
			$ARRAY[$index]=str_replace("children {$re[1]}", "children $newvalue", $ARRAY[$index]);
			if($GLOBALS["VERBOSE"]){echo "Change to {$ARRAY[$index]}\n";}
			$FOUND=true;
			break;
		}
		
	}
	
	if($FOUND){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $ARRAY));
		return true;
	}
	
}


function PING_GATEWAY(){
	$sock=new sockets();
	$unix=new unix();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=PING_GATEWAY_DEFAULT_PARAMS($MonitConfig);

	if($MonitConfig["ENABLE_PING_GATEWAY"]==0){return;}
	if(!isset($MonitConfig["PING_GATEWAY"])){$MonitConfig["PING_GATEWAY"]=null;}
	$PING_GATEWAY=$MonitConfig["PING_GATEWAY"];
	
	if($PING_GATEWAY==null){
		$TCP_NICS_STATUS_ARRAY=$unix->NETWORK_ALL_INTERFACES();
		if(isset($TCP_NICS_STATUS_ARRAY["eth0"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth0"]["GATEWAY"];}
		if($PING_GATEWAY==null){if(isset($TCP_NICS_STATUS_ARRAY["eth1"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth1"]["GATEWAY"];}	}
	}
	
	if($PING_GATEWAY==null){Events("No IP address defined in the configuration, aborting test...");return;}
	if(!$unix->isIPAddress($PING_GATEWAY)){Events("\"$PING_GATEWAY\" not a valid ip address");return;}
	
	$STAMP_MAX_PING=intval(trim(@file_get_contents($GLOBALS["STAMP_MAX_PING"])));
	if(!is_numeric($STAMP_MAX_PING)){$STAMP_MAX_PING=1;}
	if($STAMP_MAX_PING<1){$STAMP_MAX_PING=1;}

	if($GLOBALS["VERBOSE"]){echo "PING $PING_GATEWAY STAMP_MAX_PING=$STAMP_MAX_PING\n";}
	
	if($unix->PingHost($PING_GATEWAY,true)){
		if($STAMP_MAX_PING>1){
			@file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);
		}
		if($GLOBALS["VERBOSE"]){echo "PING OK -> FailOverUp()\n";}
		FailOverUp();
		return;
	}	
	
	if($MonitConfig["PING_FAILED_RELOAD_NET"]==1){
		$report=$unix->NETWORK_REPORT();
		shell_exec("/etc/init.d/artica-ifup start");
		if($unix->PingHost($PING_GATEWAY,true)){
			squid_admin_mysql(2,"Relink network success","Relink network success after ping failed on $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica as restarted network and ping is now success.\nHere it is the network report when Ping failed\n$report");
			squid_admin_notifs("Relink network success after ping failed on $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica as restarted network and ping is now success.\nHere it is the network report when Ping failed\n$report", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		
	}
	
	$MAX_PING_GATEWAY=$MonitConfig["MAX_PING_GATEWAY"];
	$STAMP_MAX_PING=$STAMP_MAX_PING+1;
	Events("$PING_GATEWAY not available - $STAMP_MAX_PING time(s) / $MAX_PING_GATEWAY Max");
	@file_put_contents($GLOBALS["STAMP_MAX_PING"], $STAMP_MAX_PING);
	if($STAMP_MAX_PING < $MAX_PING_GATEWAY){return;}
	
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}	
	@file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);
	
	
	if($MonitConfig["PING_FAILED_REPORT"]==1){
		$report=$unix->NETWORK_REPORT();
		squid_admin_mysql(1,"Unable to ping $PING_GATEWAY","$report");
		squid_admin_notifs("Unable to ping $PING_GATEWAY:\n$report", __FUNCTION__, __FILE__, __LINE__, "proxy");
	}

	if($MonitConfig["PING_FAILED_FAILOVER"]==1){FailOverDown("Unable to ping $PING_GATEWAY");}
	if($MonitConfig["PING_FAILED_REBOOT"]==1){REBOOTING_SYSTEM();}


}


function SwapCache($MonitConfig){
	if($MonitConfig["MaxSwapPourc"]==0){return;}
	if($MonitConfig["MaxSwapPourc"]>99){return;}
	$unix=new unix();
	$free=$unix->find_program("free");
	$echo=$unix->find_program("echo");
	$sync=$unix->find_program("sync");
	$swapoff=$unix->find_program("swapoff");
	$swapon=$unix->find_program("swapon");
	exec("$free 2>&1",$results);
	$used=0;
	$total=0;
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$total=$re[1];
			$used=$re[2];
			
		}
		
	}
	if(!is_numeric($total)){return;}
	if($total==0){return;}
	if($used==0){return;}
	if($total==$used){return;}
	$tot1=$used/$total;
	$tot1=$tot1*100;
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total - $tot1\n";}
	
	
	
	$perc=round($tot1);
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total {$perc}%\n";}
	
	Events("Swap: $used/$total {$perc}% Rule {$MonitConfig["MaxSwapPourc"]}%");
	
	if($perc>$MonitConfig["MaxSwapPourc"]){
		Events("Swap exceed rule: {$perc}% flush the swap...");
		shell_exec("$echo \"3\" > /proc/sys/vm/drop_caches");
		sleep(5);
		shell_exec("$sync");
		shell_exec("$echo \"0\" > /proc/sys/vm/drop_caches");
		shell_exec("$swapoff -a");
		shell_exec("$swapon -a");
		$usedTXT=FormatBytes($used);
		Events("Flush the swap done...");
		squid_admin_mysql(1,"System swap exceed rule: {$perc}%","$usedTXT\nArtica have flush the Swap cache.");
		squid_admin_notifs("Swap exceed rule: {$perc}% $usedTXT\nArtica have flush the Swap cache.", __FUNCTION__, __FILE__, __LINE__, "proxy");
	}
	        
}


function squidz($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			system_admin_events("restart_squid::Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	echo date("Y/m/d H:i:s")." Arti| Stopping Squid\n";
	echo date("Y/m/d H:i:s")." Arti| Please wait....\n";
	stop_squid(true);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$su_bin=$unix->find_program("su");
	$t1=time();
	

	
	exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
	echo date("Y/m/d H:i:s")." Arti| Checking caches `$squidbin`....Please wait\n";
	while (list ($index, $val) = each ($results)){
		echo $val."\n";
	}
	
	
	$execnice=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	$chown=$unix->find_program("chown");
	$tail=$unix->find_program("tail");
	
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		echo date("Y/m/d H:i:s")." Arti| Lauching a chown task in background mode on `$CacheDirectory`... this could take a while....\n";
		$unix->chmod_alldirs(0755, $CacheDirectory);
		$cmd="$execnice$nohup $chown -R squid:squid $CacheDirectory >/dev/null 2>&1 &";
		echo date("Y/m/d H:i:s")." Arti| $cmd\n";
		shell_exec($cmd);
		
	}	
	
	echo date("Y/m/d H:i:s")." Arti| Starting squid....Please wait\n";
	start_squid(true);
	sleep(5);
	
	exec("$tail -n 100 /var/log/squid/cache.log 2>&1",$results2);
	while (list ($index, $val) = each ($results2)){echo $val."\n";}
	
	echo date("Y/m/d H:i:s")." Arti| Done...\n";
	echo date("Y/m/d H:i:s")." Arti| Took ". $unix->distanceOfTimeInWords($t1,time())."\n";
}

function CheckStoreDirs($direct=false){
	$unix=new unix();
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	$mustBuild=false;
	$php5=$unix->LOCATE_PHP5_BIN();
	
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory\n";}
		
		if(!is_dir("$CacheDirectory")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory no such directory\n";}
			@mkdir($CacheDirectory,0755,true);
			$mustBuild=true;
			continue;
		}
		if($direct){SendLogs("Found cache $CacheDirectory [$type]");}
		if(strtolower($type)=="rock"){continue;}
		
		if(preg_match("#rock#", $CacheDirectory)){continue;}
		
		if(!is_dir("$CacheDirectory/00")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory/00 no such directory\n";}
			$mustBuild=true;
			continue;
		}
		
		if(!is_dir("$CacheDirectory/01")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory/01 no such directory\n";}
			$mustBuild=true;
			continue;
		}
		
		if(!is_dir("$CacheDirectory/02")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory/02 no such directory\n";}
			$mustBuild=true;
			continue;
		}		
		
	}
	
	if($mustBuild){
		if($direct){
			$su_bin=$unix->find_program("su");
			$squidbin=$unix->LOCATE_SQUID_BIN();
			exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
			while (list ($index, $line) = each ($results) ){SendLogs("$line");}
			return;
		}
		
		
		$cmd="$php5 /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly --force >/dev/null 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec("$cmd");
	}
	
}


function restart_squid($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			system_admin_events("restart_squid::Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		if($GLOBALS["OUTPUT"]){echo "Restart.......: [INIT][{$GLOBALS["MYPID"]}]: Squid-cache, not installed\n";}
		return;
	}
	

	$t1=time();
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		
	if($GLOBALS["OUTPUT"]){echo "Restart.......: [INIT][{$GLOBALS["MYPID"]}]: Restarting Squid-cache...\n";}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["RECONFIGURE"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Reconfiguring Squid-cache...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Restart.......: [INIT][{$GLOBALS["MYPID"]}]: stopping Squid...\n";}
	stop_squid(true);
	
	if($GLOBALS["SWAPSTATE"]){
		$GLOBALS["FORCE"]=true;
		swap_state();
	}
	if($GLOBALS["OUTPUT"]){echo "Restart.......: [INIT][{$GLOBALS["MYPID"]}]: Starting Squid...\n";}
    start_squid(true);
    $took=$unix->distanceOfTimeInWords($t1,time());

    system_admin_events("Squid restarted took: $took", __FUNCTION__, __FILE__, __LINE__, "proxy");
	
}

function DeletedCaches(){
	$unix=new unix();
	$dirs=$unix->dirdir("/home/squid");
	$rm=$unix->find_program("rm");
	while (list ($CacheDirectory, $type) = each ($dirs)){
		if(!preg_match("#-delete-[0-9]+#", $CacheDirectory)){continue;}
		Events("Found an old cache: $CacheDirectory");
		shell_exec("$rm -rf $CacheDirectory");
	}

}

function Checks_Winbindd(){
	$sock=new sockets();
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return;}
	$unix->Winbindd_privileged_SQUID();
	if(winbind_is_run()){
		Events("Winbind OK pid:{$GLOBALS["WINBINDPID"]}...");
		return;}
	system_admin_events("Winbindd not running, start it...", __FUNCTION__, __FILE__, __LINE__, "proxy");
	Events("Start Winbind...");
	$php=$unix->LOCATE_PHP5_BIN();
	exec("$php /usr/share/artica-postfix/exec.winbindd.php --start 2>&1",$results);
	
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_mysql(1,"Winbindd service was started",@implode("\n", $results)."\n$executed");
	squid_admin_notifs("Winbindd service was started:\n".@implode("\n", $results)."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
	
	
	Events(@implode("\n", $results));
	
}

function winbind_is_run(){
	$GLOBALS["WINBINDPID"]=0;
	$pidfile="/var/run/samba/winbindd.pid";
	$unix=new unix();
	$GLOBALS["WINBINDPID"]=$unix->get_pid_from_file($pidfile);
	if(!$unix->process_exists($GLOBALS["WINBINDPID"])){return false;}
	return true;

}

function Events($text){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}	
	
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function ChecksInstances(){
	$unix=new unix();
	$pidof=$unix->find_program("pidof");
	
	
	
}

function MemBoosters(){
	if($GLOBALS["VERBOSE"]){echo "Membooster (Verbose) \n";}
	$unix=new unix();
	$df=$unix->find_program("df");
	$rm=$unix->find_program("rm");
	$CACHE=array();
	exec("$df -h  /var/cache/MemBooster* 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#tmpfs\s+([0-9A-Z]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+.*?MemBooster([0-9]+)#", $ligne,$re)){
			$CACHE[$re[5]]["SIZE"]=$re[1];
			$CACHE[$re[5]]["USED"]=$re[2];
			$CACHE[$re[5]]["POURC"]=$re[4];
			continue;
			
		}	else{
			if($GLOBALS["VERBOSE"]){echo "Unknwon line \"$ligne\"\n";}
		}
	}
	
	if(count($CACHE)==0){
		if($GLOBALS["VERBOSE"]){echo "NO CACHE !\n";}
		return;}
	
	$swapiness=intval(trim(@file_get_contents("/proc/sys/vm/swappiness")));
	if($GLOBALS["VERBOSE"]){echo "SWAPINESS = {$swapiness}%\n";}
	//vm.swappiness
	
	if($swapiness>5){
		squid_admin_mysql(2,"Swapiness set to 5%","The SWAPINESS was {$swapiness}%:\nIt will be modified to 5% for MemBoosters");
		squid_admin_notifs("Swapiness set to 5%\nThe SWAPINESS was {$swapiness}%:\nIt will be modified to 5% for MemBoosters\n", __FUNCTION__, __FILE__, __LINE__, "proxy");
		@file_put_contents("/proc/sys/vm/swappiness", "5");
	}
	
	$SQUIDZ=false;
	while (list ($CacheNum, $ARRAY) = each ($CACHE) ){
		if($ARRAY["POURC"]>93){
			$WARN[]="MemBooster [{$CacheNum}] {$ARRAY["USED"]}/{$ARRAY["SIZE"]} {$ARRAY["POURC"]}%";
			Events("Warning: Membooster $CacheNum reach critical size: {$ARRAY["USED"]}/{$ARRAY["SIZE"]} {$ARRAY["POURC"]}%",true);
			shell_exec("$rm -rf /var/cache/MemBooster{$CacheNum}/* >/dev/null 2>&1");
			$SQUIDZ=true;
		}
	}
	
	if($SQUIDZ){
		squidz(true);
		squid_admin_mysql(1,"MemBoosters exceed size","Cleaned procedure done\nThese MemBoosters status:\n" .@implode("\n", $WARN));
		squid_admin_notifs("MemBoosters exceed size, Cleaned procedure done\nThese MemBoosters status:\n" .@implode("\n", $WARN), __FUNCTION__, __FILE__, __LINE__, "proxy");
	}
	
}

function FailOverParams(){
	if(isset($GLOBALS["FailOverParams"])){return $GLOBALS["FailOverParams"];}
	$sock=new sockets();
	$FailOverArticaParams=unserialize(base64_decode($sock->GET_INFO("FailOverArticaParams")));
	if(!is_numeric($FailOverArticaParams["squid-internal-mgr-info"])){$FailOverArticaParams["squid-internal-mgr-info"]=1;}
	if(!is_numeric($FailOverArticaParams["ExternalPageToCheck"])){$FailOverArticaParams["ExternalPageToCheck"]=1;}
	
	$GLOBALS["FailOverParams"]=$FailOverArticaParams;
	return $GLOBALS["FailOverParams"];
}
function Checks_mgrinfos_31(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	$data=CurlGet("storedir");
	$array=MgrStoreDirToArray($data);
	if(is_array($array)){
		@unlink($StoreDirCache);
		@file_put_contents($StoreDirCache, serialize($array));
		$time=$unix->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
		if($time>15){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos");
		}
	}
		
}

function Checks_mgrinfos($MonitConfig){
	
	$sock=new sockets();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$SystemInfoCache="/etc/squid3/squid_get_system_info.db";
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	if(!is_array($MonitConfig)){
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig=watchdog_config_default($MonitConfig);

	}
	
	$FailOverArticaParams=FailOverParams();
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($time>5){
			$kill=$unix->find_program("kill");
			Events("kill old $oldpid process {$time}mn");
			shell_exec("$kill -9 $oldpid");
		}else{
			system_admin_events("Start_squid:: Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	
	if(is31()){
		if($GLOBALS["VERBOSE"]){echo "Only squid 3.1x... aborting\n";}
		Checks_mgrinfos_31();
		return;
	}
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}

	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
	
	
	
	
	$t0=time();
	$curl=new ccurl("http://$SquidBinIpaddr:$http_port/squid-internal-mgr/info",true);
	$curl->CURLOPT_NOPROXY=$SquidBinIpaddr;
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=$MgrInfosMaxTimeOut;
	$curl->UseDirect=true;
	if(!$curl->get()){
		Events("Unable to retreive informations from $SquidBinIpaddr:$http_port, $curl->error", __FUNCTION__, __FILE__, __LINE__, "proxy");
		if($MonitConfig["watchdog"]==1){
			if($MonitConfig["EnableFailover"]==1){
				if($FailOverArticaParams["squid-internal-mgr-info"]==1){FailOverDown("Unable to retreive informations from $SquidBinIpaddr:$http_port, $curl->error");}
			}
		
		RESTARTING_SQUID_WHY($MonitConfig,"$curl->error: Unable to retreive informations from $SquidBinIpaddr:$http_port");

		}
	}else{
		STAMP_MAX_RESTART_RESET();
		if($MonitConfig["EnableFailover"]==1){FailOverUp();}
		$StoreDirCache="/etc/squid3/squid_storedir_info.db";
		$curl=new ccurl("http://$SquidBinIpaddr:$http_port/squid-internal-mgr/storedir");
		$curl->ArticaProxyServerEnabled=="no";
		$curl->interface="127.0.0.1";
		$curl->Timeout=$MgrInfosMaxTimeOut;
		$curl->UseDirect=true;
		if($curl->get()){	
			$array=MgrStoreDirToArray($curl->data);
			if(is_array($array)){
				@unlink($StoreDirCache);
				@file_put_contents($StoreDirCache, serialize($array));
				$time=$unix->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
				if($time>15){
					shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos");
				}
			}
		}	
		
	}
	
	$array=MgrInfoToArray($curl->data);
	if(count($array)>5){
		@unlink($SystemInfoCache);
		@file_put_contents("$SystemInfoCache", serialize($array));
	}
	
	if($MonitConfig["watchdog"]==1){
		if($GLOBALS["VERBOSE"]){echo " *** *** *** Checks_external_webpage() *** *** *** \n";}
		Checks_external_webpage($MonitConfig);
	}
	
}

function RESTARTING_SQUID_WHY($MonitConfig,$explain){
	
	if(!is_array($MonitConfig)){
		$sock=new sockets();
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig=watchdog_config_default($MonitConfig);
	
	}
	
	Events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}	
	$STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
	if($STAMP_MAX_RESTART >= $MAX_RESTART){
		Events("Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
		return;
	}
	
	$SquidCacheReloadTTL=$MonitConfig["SquidCacheReloadTTL"];
	$unix=new unix();
	$timex=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART_TTL"]);
	if($timex<$SquidCacheReloadTTL){return;}
	@unlink($GLOBALS["STAMP_MAX_RESTART_TTL"]);
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART_TTL"], time());
	
	
	STAMP_MAX_RESTART_SET();
	$STAMP_MAX_RESTART++;
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	
	system_admin_events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
	Events("Restarting squid Max restarts: $STAMP_MAX_RESTART/$MAX_RESTART");
	restart_squid(true);
}


function STAMP_MAX_RESTART_GET(){
	$STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
	if(!is_numeric($STAMP_MAX_RESTART)){$STAMP_MAX_RESTART=0;}
	return $STAMP_MAX_RESTART;
}
function STAMP_MAX_RESTART_SET(){
	$STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
	$STAMP_MAX_RESTART++;
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], $STAMP_MAX_RESTART);
}
function STAMP_MAX_RESTART_RESET(){
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], 0);
}
				

function FailOverDown($why=null){
	$sock=new sockets();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if($FailOverArtica==0){return;}
	if($GLOBALS["EnableFailover"]==0){return;}
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover backup, license error");return;}
	Events("Down failover network interface in order to switch to backup...");
	
	if($why<>null){
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
		system_failover_events("Switch to backup:<br>$why",$sourcefunction,basename(__FILE__),$sourceline);
	}
	
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	
	
	@unlink($GLOBALS["STAMP_FAILOVER"]);
	@file_put_contents($GLOBALS["STAMP_FAILOVER"], time());
	
	shell_exec("/etc/init.d/artica-failover stop");
	
}
function FailOverUp(){
	if($GLOBALS["EnableFailover"]==0){return;}
	if(!is_file($GLOBALS["STAMP_FAILOVER"])){return;}
	$sock=new sockets();
	$unix=new unix();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if($FailOverArtica==0){return;}	
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$GLOBALS[__FUNCTION__]=true;
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover master, license error");return;}
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$TimeEx=$unix->file_time_min($GLOBALS["STAMP_FAILOVER"]);
	if($TimeEx<$MonitConfig["MinTimeFailOverSwitch"]){
		Events("Need to wait {$MonitConfig["MinTimeFailOverSwitch"]}mn before switch to master (current is {$TimeEx}Mn");
		return;
	}
	
	
	
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	system_failover_events("Return to master:<br>Up failover network interface in order to turn back to master",$sourcefunction,basename(__FILE__),$sourceline);
	
	Events("Up failover network interface in order to turn back to master...");
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	@unlink($GLOBALS["STAMP_FAILOVER"]);
	shell_exec("/etc/init.d/artica-failover start");	
}

function MgrStoreDirToArray($datas){
	$results=explode("\n", $datas);
	while (list ($num, $ligne) = each ($results) ){
	if(preg_match("#Store Directory.*?:\s+(.+)#", $ligne,$re)){$StoreDir=trim($re[1]);continue;}
	if(preg_match("#Percent Used:\s+([0-9\.]+)%#", $ligne,$re)){
		$array[$StoreDir]["PERC"]=$re[1];
		continue;
	}
	if(preg_match("#Maximum Size:\s+([0-9\.]+)#", $ligne,$re)){
		$array[$StoreDir]["SIZE"]=$re[1];
		continue;
	}	
	
	if(preg_match("#Shared Memory Cache#", $ligne)){
		$StoreDir="MEM";
		continue;
	}
	
	if(preg_match("#Current entries:\s+([0-9\.]+)\s+([0-9\.]+)%#",$ligne,$re)){
		$array[$StoreDir]["ENTRIES"]=$re[1];
		$array[$StoreDir]["PERC"]=$re[2];
	}
}

return $array;
	
}

function MgrInfoToArray($datas){
	$results=explode("\n", $datas);
	$ARRAY=array();
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^(.*?):$#", trim($ligne),$re)){
			$title=$re[1];
			continue;
		}
	
		if(preg_match("#\s+(.*?):\s+(.+)#", $ligne,$re)){
			$sub=$re[1];
			$values=trim($re[2]);
		}
		if(strpos($ligne, ':')==0){
			if(preg_match("#\s+([0-9]+)\s+(.+)#", $ligne,$re)){
				$sub=trim($re[2]);
				$values=trim($re[1]);
			}
		}
	
		if($title==null){continue;}
		if($sub==null){continue;}
		$ARRAY[$title][$sub]=$values;
	
	
	}	
	
	return $ARRAY;
}


function Checks_external_webpage($MonitConfig){
	$sock=new sockets();
	$unix=new unix();
	if($unix->IsSquidReverse()){if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage() -> IsSquidReverse -> TRUE -> STOP\n";}return;}
	$tcp=new networking();
	
	if(!is_array($MonitConfig)){
		$sock=new sockets();
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	}
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($MonitConfig["TestExternalWebPage"]==0){return;}
	$FailOverArticaParams=FailOverParams();
	
	
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
	while (list ($index, $val) = each ($ALL_IPS_GET_ARRAY)){$IPZ[]=$index;}
	$IPZ_COUNT=count($IPZ);
	if($IPZ_COUNT==1){$choosennet=$IPZ[0];}else{$choosennet=$IPZ[rand(0,$IPZ_COUNT-1)];}
	
	$uri=$MonitConfig["ExternalPageToCheck"];
	if($MonitConfig["ExternalPageListen"]=="127.0.0.1"){$MonitConfig["ExternalPageListen"]=null;}
	if($MonitConfig["ExternalPageListen"]==null){$MonitConfig["ExternalPageListen"]=$choosennet;}
	
	if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage(): choosennet=$choosennet({$MonitConfig["ExternalPageListen"]})\n";}
	
	
	$uri=str_replace("%T", time(), $uri);
	$http_port=squid_get_alternate_port();
	
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}	
	
	if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
		$SquidBinIpaddr=$re[1];
		if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
		$http_port=$re[2];
	}
	
	
	
	$curl=new ccurl($uri,true);
	
	$t0=time();
	
	$curl->ArticaProxyServerEnabled="yes";
	$curl->ArticaProxyServerName=$SquidBinIpaddr;
	$curl->interface="127.0.0.1";
	
	if($MonitConfig["ExternalPageUsername"]<>null){
		$curl->interface=$MonitConfig["ExternalPageListen"];
		$curl->ArticaProxyServerUsername=$MonitConfig["ExternalPageUsername"];
		$curl->ArticaProxyServerUserPassword=$MonitConfig["ExternalPagePassword"];
	}
	
	if($GLOBALS["VERBOSE"]){
		echo "{$uri}:Using SQUID + $curl->interface/{$MonitConfig["ExternalPageListen"]} -> $curl->ArticaProxyServerUsername@$curl->ArticaProxyServerName\n";
	}
	$curl->ArticaProxyServerPort=$http_port;
	$curl->NoHTTP_POST=true;
	$curl->Timeout=$MonitConfig["MgrInfosMaxTimeOut"];
	
	if(!$curl->get()){
		if($GLOBALS["VERBOSE"]){echo $curl->data;}
		RESTARTING_SQUID_WHY($MonitConfig, " Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error`");
		
		if($MonitConfig["EnableFailover"]==1){
			if($FailOverArticaParams["ExternalPageToCheck"]==1){
				FailOverDown("Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error` ($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri max:$MgrInfosMaxTimeOut seconds Proxy:http://$SquidBinIpaddr:$http_port");
			}
		}
		
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "***** SUCCESS *****\n";}
	if($GLOBALS["VERBOSE"]){echo $curl->data;}
	
	$datas=$curl->data;
	$length=strlen($datas);
	$unit="bytes";
	if($length>1024){$length=$length/1024;$unit="Ko";}
	if($length>1024){$length=$length/1024;$unit="Mo";}
	$length=round($length,2);
	if($MonitConfig["EnableFailover"]==1){FailOverUp();}
	STAMP_MAX_RESTART_RESET();
	Events("Success Internet should be available webpage length:{$length}$unit Took:".$unix->distanceOfTimeInWords($t0,time(),true));		
	
}

function SQUID_PID(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}	
	$pid=$unix->get_pid_from_file($unix->LOCATE_SQUID_PID());
	if(!$unix->process_exists($pid)){
		$pid=$unix->PIDOF($squidbin);
	}
	
	return $pid;
	
}

function start_squid($aspid=false){
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$reconfigure=false;
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	$su_bin=$unix->find_program("su");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: Squid-cache, not installed\n";}
		return;
	}
	
	initd_squid();
	
	if($SQUIDEnable==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Squid is disabled...\n";}
		return;
	}
	
	if(is_file("/etc/artica-postfix/squid.lock")){
		$time=$unix->file_time_min("/etc/artica-postfix/squid.lock");
		if($time<60){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Squid is locked (since {$time}Mn...\n";}
			return;
		}
		@unlink("/etc/artica-postfix/squid.lock");		
	}
	
	if(is_dir("/opt/squidsql/data")){
		if(!is_dir("/opt/squidsql/data/squidlogs")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: MySQL database not prepared\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: /opt/squidsql/data/squidlogs no such directory\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Starting MySQL database\n";}
			shell_exec("/etc/init.d/squid-db start");
			shell_exec("/etc/init.d/artica-status start");
			
		}
		if(!is_dir("/opt/squidsql/data/squidlogs")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: MySQL database not prepared\n";}
			return;
		}
	}
	
	$unix->CreateUnixUser("squid","squid");
	
	if(!is_file("/etc/squid3/squid.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Warning /etc/squid3/squid.conf no such file\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Ask to build it and die\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force --withoutloading");
		die();
	}
	
	buil_init_squid_cache_log();
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Already task running PID $oldpid since {$time}mn, Aborting operation (".__LINE__.")\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){system_admin_events("Squid not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");return;}	
	
	@chmod($squidbin,0755);

	if(!is_file("/etc/squid3/malwares.acl")){@file_put_contents("/etc/squid3/malwares.acl", "\n");}
	if(!is_file("/etc/squid3/squid-block.acl")){@file_put_contents("/etc/squid3/squid-block.acl", "\n");}

	$EXPLODED=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	
	
	while (list ($index, $val) = each ($EXPLODED)){
		if(preg_match("#INSERT YOUR OWN RULE#", $val)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: squid must be reconfigured...\n";}
			$reconfigure=true;
		}
	}
	
	if($reconfigure){
		if($GLOBALS["OUTPUT"]){
			system("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading");
		}else{
			exec("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading 2>&1",$GLOBALS["LOGS"]);
		}
		
	}else{
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		$squid=new squidbee();
		$squid->RockStore();
		
	}	
	
	if($NtpdateAD==1){shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");}
	shell_exec("$php /usr/share/artica-postfix/exec.initd-squid.php >/dev/null 2>&1");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1");
	exec("$php /usr/share/artica-postfix/exec.squid.transparent.php",$GLOBALS["LOGS"]);
	@mkdir("/var/log/squid",true,0750);
	@mkdir("/home/squid/cache",true,0750);
	@mkdir("/etc/squid3",true,0750);
	@mkdir("/var/lib/squidguard",true,0750);
	@mkdir("/var/run/squid",true,0750);
	@mkdir("/var/logs",true,0750);
	$unix->chmod_func(0700, "/var/log/squid/*");
	$unix->chmod_func(0755, "/lib/squid3/*");
	$unix->chmod_func(0755, "/var/run/squid/*");
	$unix->chown_func("squid","squid", "/var/log/squid/*");
	$unix->chown_func("squid","squid", "/lib/squid3/*");
	$unix->chown_func("squid","squid", "/etc/squid3/*");
	$unix->chown_func("squid","squid", "/home/squid/cache");
	$unix->chown_func("squid","squid", "/var/run/squid");
	$unix->chown_func("squid","squid", "/var/run/squid/squid.pid");
	$unix->chown_func("squid","squid", "/var/logs");
	$squid_locate_pinger=$unix->squid_locate_pinger();
	if(is_file($squid_locate_pinger)){@chmod($squid_locate_pinger,4755);}
	
	$pid=SQUID_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Proxy service already running pid $pid since {$time}mn\n";}
		system_admin_events("Squid seems to already running pid $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}	
	
	$t1=time();
	SendLogs("Checking caches...");
	CheckStoreDirs(true);
	SendLogs("Checking caches done...");
	SendLogs("Starting squid $squidbin....");
	@copy("/var/log/squid/cache.log", "/var/log/squid/cache.log.".time());
	@chmod($squidbin,0755);
	exec("$squidbin -f /etc/squid3/squid.conf 2>&1",$GLOBALS["LOGS"]);
	
	for($i=0;$i<120;$i++){
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){SendLogs("Starting squid started pid $pid...");break;}
		SendLogs("Starting squid waiting $i/120s");
		sleep(1);
	}
	
	if(!$unix->process_exists($pid)){
		SendLogs("Starting Squid failed to start...");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		
		squid_admin_mysql(0,"Squid failed to start",@implode("\n", $GLOBALS["LOGS"])."\n$executed");
		squid_admin_notifs("Starting Squid failed to start\n".@implode("\n", $GLOBALS["LOGS"])."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		system_admin_events("Starting Squid failed to start\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	
	SendLogs("Starting Squid Tests if it listen all connections....");
	for($i=0;$i<10;$i++){
		if(is_started()){SendLogs("Starting squid listen All connections OK");break;}
		SendLogs("Starting squid listen All connections... waiting $i/10");
		sleep(1);
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time());
	SendLogs("Starting Squid success to start PID $pid...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	$php5=$unix->LOCATE_PHP5_BIN();
	
	system_admin_events("Starting Squid success to start PID $pid took $took\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	SendLogs("Starting Squid finishing by schedule other tasks");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix start squidcache-tail");
	$unix->THREAD_COMMAND_SET("/etc/init.d/auth-tail restart");
	$unix->THREAD_COMMAND_SET("$php5 ".basename(__FILE__)."/exec.proxy.pac.php --write");
	SendLogs("Starting Squid done...");
}




function is_started(){
	
	$f=file("/var/log/squid/cache.log");
	krsort($f);
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#Accepting HTTP Socket connections#i", $val)){
			SendLogs("Detected:$val...");
			return true;}
		
	}
	
	return false;
	
}

function test_smtp_watchdog(){
	squid_admin_notifs("This is an SMTP tests from the configuration file", __FUNCTION__, __FILE__, __LINE__, "proxy");
}


function stop_squid($aspid=false){
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]: Already `task` running PID $oldpid since {$time}mn\n";}
			system_admin_events("stop_squid::Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$STOP_SQUID_TIMEOUT=$MonitConfig["STOP_SQUID_TIMEOUT"];
	$STOP_SQUID_MAXTTL_DAEMON=$MonitConfig["STOP_SQUID_MAXTTL_DAEMON"];
	
	
	if(!is_numeric($STOP_SQUID_TIMEOUT)){$STOP_SQUID_TIMEOUT=120;}
	if(!is_numeric($STOP_SQUID_MAXTTL_DAEMON)){$STOP_SQUID_MAXTTL_DAEMON=5;}
	if($STOP_SQUID_TIMEOUT<5){$STOP_SQUID_TIMEOUT=5;}
	
	
	$squidbin=$unix->find_program("squid");
	$kill=$unix->find_program("kill");
	$pgrep=$unix->find_program("pgrep");
	
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		system_admin_events("Squid not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	
	$t1=time();
	$pid=SQUID_PID();
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid)){
			$timeTTL=$unix->PROCCESS_TIME_MIN($pid);
			if($timeTTL<$STOP_SQUID_MAXTTL_DAEMON){
				if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]: Squid live since {$timeTTL}Mn, this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn\n";}
				Events("Squid live since {$timeTTL}Mn, this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn");
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
				squid_admin_mysql(2, "Reconfiguring Proxy parameters...","Squid live since {$timeTTL}Mn,this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn","$called");
				squid_watchdog_events("Reconfiguring Proxy parameters...");
				shell_exec("$squidbin -k reconfigure >/dev/null 2>&1");
				return;
			}
			
		}
	}
	
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]: Squid Already stopped...\n";}
		system_admin_events("Squid is not running, start it...", __FUNCTION__, __FILE__, __LINE__, "proxy");
		start_squid();
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]:Stopping Squid-Cache service....\n";}
	SendLogs("Stopping Squid-Cache service....");
	shell_exec("$squidbin -f /etc/squid3/squid.conf -k shutdown >/dev/null 2>&1");
	for($i=0;$i<$STOP_SQUID_TIMEOUT;$i++){
		sleep(1);
		$task=null;
		$pid=SQUID_PID();
		if(!$unix->process_exists($pid)){break;}
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		if(preg_match("#\((.+?)\)-#", $cmdline,$re)){$task=$re[1];}
		SendLogs("Stopping Squid-Cache service waiting $i seconds (max $STOP_SQUID_TIMEOUT) for $pid PID $task....");
		shell_exec("$kill $pid >/dev/null 2>&1");
	}
	
	
	
	
	$pid=SQUID_PID();
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");	
	
	if($unix->process_exists($pid)){
		SendLogs("Stopping Squid-Cache service failed, took ".$unix->distanceOfTimeInWords($t1,time()));
		system_admin_events("Squid failed to stop\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");

		SendLogs("Stopping Squid-Cache service failed -> kill all instances:".exec("$pidof $squidbin 2>&1")."...");
		shell_exec("$kill -9 `$pidof $squidbin`");
	}
	
	
	SendLogs("Stopping Squid-Cache seems stopped search ghost processes...");
	$pids=explode(" ", exec("$pidof $squidbin 2>&1"));
	if($GLOBALS["VERBOSE"]){echo "exec($pidof $squidbin 2>&1) = `".exec("$pidof $squidbin 2>&1")."`";}
	
	
	while (list ($num, $pid) = each ($pids) ){
		if(!is_numeric($pid)){continue;}
		if($pid<10){continue;}
		if(!$unix->process_exists($pid)){continue;}
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		if(preg_match("#\((.+?)\)-#", $cmdline,$re)){$task=$re[1];}	
		SendLogs("Stopping Squid-Cache killing ghost task pid $pid `$task`\n");
		shell_exec("$kill -9 $pid");
		
	}
	
	SendLogs("Stopping Squid-Cache seems stopped search ntlm_auth processes...");
	exec("$pgrep -l -f \"ntlm_auth.*?--helper-proto\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+\(ntlm_auth#", $ligne,$re)){SendLogs("Skipping $ligne");continue;}
		$pid=$re[1];
		shell_exec("$kill -9 $pid");
		SendLogs("Stopping ntlm_auth process PID $pid");
	
	}	
	
	SendLogs("Stopping Squid-Cache seems stopped search external_acl_squid processes...");
	exec("$pgrep -l -f \"external_acl_squid.php\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		shell_exec("$kill -9 $pid");
		SendLogs("Stopping external_acl_squid process PID $pid");
	
	}	
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	SendLogs("Stopping Squid-Cache seems stopped search $squidbin processes...");
	exec("$pgrep -l -f \"$squidbin\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		shell_exec("$kill -9 $pid");
		SendLogs("Stopping squid process PID $pid");
	
	}	
	SendLogs("Stopping Squid-Cache seems stopped search $squidbin (sub-daemons) processes...");
	exec("$pgrep -l -f \"\(squid-[0-9]+\)\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		shell_exec("$kill -9 $pid");
		SendLogs("Stopping squid process PID $pid");
	
	}	
	

	if(is_file("/dev/shm/squid-cache_mem.shm")){
		SendLogs("removing /dev/shm/squid-cache_mem.shm");
		@unlink("/dev/shm/squid-cache_mem.shm");
	}
	if(is_file("/dev/shm/squid-squid-page-pool.shm")){
		SendLogs("removing /dev/shm/squid-squid-page-pool.shm");
		@unlink("/dev/shm/squid-squid-page-pool.shm");
	}	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	system_admin_events("Squid success to stop\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	
}


function builduri($cmd){
	
	if(!isset($GLOBALS["builduri"])){
	$sock=new sockets();
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}
	
	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
	$GLOBALS["builduri"]="http://$SquidBinIpaddr:$http_port/squid-internal-mgr";
	}
	
	return $GLOBALS["builduri"]."/$cmd";
}
function CurlGet($cmd){
	
	if(is31()){
		if($GLOBALS["VERBOSE"]){echo "squidclient($cmd)\n";}
		$data=squidclient($cmd);
		if($GLOBALS["VERBOSE"]){echo "squidclient($cmd) -> ".strlen($data)." bytes\n";}
		return $data;
	}
	
	if($GLOBALS["VERBOSE"]){echo "builduri($cmd)\n";}
	$curl=new ccurl(builduri($cmd));
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=5;
	$curl->UseDirect=true;
	if(!$curl->get()){return;}	
	return $curl->data;
	
}

function counters($aspid=false){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.counters.db";
	if(!is_dir(dirname($cacheFile))){@mkdir(dirname($cacheFile),0755,true);}
	
	$unix=new unix();
	if(!$GLOBALS["VERBOSE"]){
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]: Already `task` running PID $oldpid since {$time}mn\n";}
			
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	}
	$timefile=$unix->file_time_min($cacheFile);
	if($GLOBALS["VERBOSE"]){echo basename($cacheFile)." {$timefile}mn\n";}
	
	if(!$GLOBALS["FORCE"]){
		if($timefile<5){if(!$GLOBALS["VERBOSE"]){return;}}
	}
	
	$sock=new sockets();
	
	
	$datas=explode("\n",CurlGet("5min"));
	
	
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		if(!preg_match("#(.+?)=(.+)#", $ligne,$re)){continue;}
		$ARRAY[trim($re[1])]=trim($re[2]);
		
	}

	$datas=explode("\n",CurlGet("active_requests"));
	@file_put_contents("/var/log/squid/monitor.sessions.cache", serialize($datas));
	
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		
		if(!preg_match("#Connection:\s+(.+)#", $ligne,$re)){continue;}
		if(trim($re[1])=="close"){continue;}
		$c++;
	
	}

	
	
	$ARRAY["active_requests"]=$c;
	$ARRAY["SAVETIME"]=time();
	@unlink($cacheFile);
	@file_put_contents($cacheFile, serialize($ARRAY));
	@chmod($cacheFile, 0775);
	peer_status(true);
	
	
}

function SendLogs($text){
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: $text\n";}
	$GLOBALS["LOGS"][]=$text;
}
function squid_get_alternate_port(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
		if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
			
		if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
	}

}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function initd_squid(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          squid";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Start squid-cache proxy";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: squid-cache proxy Daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php ". __FILE__." --restart --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php ". __FILE__." --reload --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";	
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	@file_put_contents("/etc/init.d/squid", @implode("\n", $f));
	@chmod("/etc/init.d/squid",0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f squid defaults >/dev/null 2>&1');
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add squid >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 squid on >/dev/null 2>&1');
	}	
	buil_init_squid_tail();
	buil_init_squid_cache_log();
	
}
function buil_init_squid_tail(){
	$unix=new unix();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$chmod=$unix->find_program("chmod");
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/squid-tail";
	$conf[]="#";
	$conf[]="# squid-tail-sock Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          squid-tail";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     2 3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch squid-tail server";
	$conf[]="# Description:       Launch squid-tail server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    /etc/init.d/artica-postfix start squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    /etc/init.d/artica-postfix stop squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="     /etc/init.d/artica-postfix restart squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     /etc/init.d/artica-postfix restart squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";
	@file_put_contents("/etc/init.d/squid-tail",@implode("\n",$conf));
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");

	shell_exec("$chmod +x /etc/init.d/squid-tail >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f squid-tail defaults >/dev/null 2>&1");
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add squid-tail >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 squid-tail on >/dev/null 2>&1");
	}
}



function buil_init_squid_cache_log(){
	$unix=new unix();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$dirname=dirname(__FILE__);
	$chmod=$unix->find_program("chmod");
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/cache-tail";
	$conf[]="#";
	$conf[]="# cache-tail Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          cache-tail";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     2 3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch squid-tail on cache.log server";
	$conf[]="# Description:       Launch squid-tail on cache.log server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    $php5 $dirname/exec.init-tail-cache.php --start \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    $php5 $dirname/exec.init-tail-cache.php --stop \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="	  $php5 $dirname/exec.init-tail-cache.php --stop \$1 \$2";
	$conf[]="     $php5 $dirname/exec.init-tail-cache.php --start \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     $php5 $dirname/exec.init-tail-cache.php --stop \$1 \$2";
	$conf[]="     $php5 $dirname/exec.init-tail-cache.php --stop \$1 \$2";	
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";
	@file_put_contents("/etc/init.d/cache-tail",@implode("\n",$conf));
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");

	shell_exec("$chmod +x /etc/init.d/cache-tail >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f cache-tail defaults >/dev/null 2>&1");
		return;
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add cache-tail >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 cache-tail on >/dev/null 2>&1");
	}
}

function is_peer(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $directory) = each ($f)){
		if(preg_match("#^cache_peer\s+#", $directory)){return true;}
	}
	return false;
	
}

function root_squid_version(){
	if(isset($GLOBALS["root_squid_version"])){return $GLOBALS["root_squid_version"];}
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if($squidbin==null){$squidbin=$unix->find_program("squid3");}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
			$GLOBALS["root_squid_version"]= trim($re[1]);
			return $GLOBALS["root_squid_version"];
		}
	}

}

function squidclient($cmd){
	
	if(!isset($GLOBALS["SQUIDCLIENT"])){
	$sock=new sockets();
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	$unix=new unix();
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}
	
	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
		$squidclient=$unix->find_program("squidclient");
		$GLOBALS["SQUIDCLIENT"]="$squidclient -T 5 -h 127.0.0.1 -p $http_port mgr";
			
	}
	
	exec($GLOBALS["SQUIDCLIENT"].":$cmd 2>&1",$results);
	
	
	if($GLOBALS["VERBOSE"]){echo $GLOBALS["SQUIDCLIENT"].":$cmd ". count($results)." lines\n";}
	return @implode("\n", $results);
	
}

function is31(){
	if(isset($GLOBALS["is31"])){return $GLOBALS["is31"];}
	$root_squid_version=root_squid_version();
	if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
	$data=null;
	$GLOBALS["is31"]=false;
	$VER=explode(".",$root_squid_version);
	if($VER[0]<4){
		if($VER[1]<2){
			if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
			$GLOBALS["is31"]=true;return true;}
	}
	return false;
	
}
function peer_status($aspid=false){
	if($GLOBALS["VERBOSE"]){echo "peer_status();\n";}
	$unix=new unix();
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.peers.db";
	if(!is_dir(dirname($cacheFile))){@mkdir(dirname($cacheFile),0755,true);}
	
	if(!$GLOBALS["DUMP"]){
		if(!$GLOBALS["VERBOSE"]){
			$unix=new unix();
			if(!$aspid){
				$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
				$oldpid=$unix->get_pid_from_file($pidfile);
				if($unix->process_exists($oldpid,basename(__FILE__))){
					$time=$unix->PROCCESS_TIME_MIN($oldpid);
					if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT][{$GLOBALS["MYPID"]}]: Already `task` running PID $oldpid since {$time}mn\n";}
						
					return;
				}
				@file_put_contents($pidfile, getmypid());
			}
			
		}
	
	}
	
	$timefile=$unix->file_time_min($cacheFile);
	if($GLOBALS["VERBOSE"]){echo basename($cacheFile)." {$timefile}mn\n";}
	if(!$GLOBALS["DUMP"]){
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["VERBOSE"]){if($timefile<5){return;}}
		}	
	}
	
	
	
	if(!is_peer()){
		if($GLOBALS["DUMP"]){echo "No cache_peer\n";return;}
		if($GLOBALS["VERBOSE"]){echo "No cache_peer...\n";}return;}
	$sock=new sockets();

	$datas=trim(CurlGet("server_list"));
	if($GLOBALS["DUMP"]){echo $datas."\n";return;}
	
	if($datas==null){
		$GLOBALS["RECONFIGURE_COUNT"]=$GLOBALS["RECONFIGURE_COUNT"]+1;
		SendLogs("No results for peer, reloading the server reconfigured {{$GLOBALS["RECONFIGURE_COUNT"]}} times");
		$unix=new unix();
		squid_admin_mysql(2, "Reconfiguring Proxy parameters...","No results for peer, reloading the server reconfigured {{$GLOBALS["RECONFIGURE_COUNT"]}} times");
		shell_exec($unix->LOCATE_SQUID_BIN()." -k reconfigure");
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 ".__FILE__." --peer-status --reconfigure-count={$GLOBALS["RECONFIGURE_COUNT"]}");
		return;
	}
	
	$tr=explode("\n",CurlGet("server_list"));
	
	
	while (list ($num, $val) = each ($tr)){
		if($GLOBALS["VERBOSE"]){echo "Found: \"$val\"\n";}
		if(preg_match("#Parent\s+:(.+)#", $val,$re)){
			$peer=trim($re[1]);
			continue;
		}
		
		
		if(preg_match("#(.+?)\s+:(.*)#", $val,$re)){
			$key=strtoupper(trim($re[1]));
			$array[$peer][$key]=trim($re[2]);
		}
		
	}
	
	if($GLOBALS["VERBOSE"]){
		echo count($array)." peers detected\n";
	}
	
	@unlink($cacheFile);
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0777);
	
	
}

function peer_dead($parent){
	
	$sock=new sockets();
	$detected=false;
	$DisableDeadParents=$sock->GET_INFO("DisableDeadParents");
	if(!is_numeric($DisableDeadParents)){$DisableDeadParents=0;}
	if($DisableDeadParents==0){return;}
	SendLogs("Parent $parent should be removed....");
	$parent_regex=str_replace(".", "\.", $parent);
	$tr=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $val) = each ($tr)){
		if(preg_match("#^cache_peer\s+$parent_regex#", $val)){
			SendLogs("Mark Removing $val...");
			$tr[$num]="#$val";
			$detected=true;
			break;
		}
	}
	if($detected){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $tr));
		$unix=new unix();
		SendLogs("Reconfiguring Squid in order to remove \"$parent\"....");
		squid_admin_mysql(2, "Reconfiguring Squid in order to remove \"$parent\"");
		shell_exec($unix->LOCATE_SQUID_BIN()." -k reconfigure");
		$php5=$unix->LOCATE_PHP5_BIN();
	}
	
	
	$DisableDeadParentsSQL=$sock->GET_INFO("DisableDeadParentsSQL");
	if(!is_numeric($DisableDeadParentsSQL)){$DisableDeadParentsSQL=0;}
	if($DisableDeadParentsSQL==0){return;}
	$q=new mysql();
	SendLogs("Stamp parent $parent to disabled in database..");
	$sql="UPDATE squid_parents SET enabled=0 WHERE servername='$parent'";
	$q->QUERY_SQL($sql,"artica_backup");
}

?>