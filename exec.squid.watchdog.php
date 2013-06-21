<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
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
	if($argv[1]=="--squidz"){$GLOBALS["OUTPUT"]=true;squidz();die();}
	if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd_squid();die();}
	if($argv[1]=="--tests-smtp"){$GLOBALS["OUTPUT"]=true;test_smtp_watchdog();die();}
	if($argv[1]=="--swapstate"){$GLOBALS["OUTPUT"]=true;$GLOBALS["SWAPSTATE"]=true;restart_squid();die();}
	if($argv[1]=="--storedirs"){$GLOBALS["OUTPUT"]=true;CheckStoreDirs();die();}
	if($argv[1]=="--memboosters"){$GLOBALS["OUTPUT"]=true;MemBoosters();die();}
	if($argv[1]=="--swap"){$GLOBALS["OUTPUT"]=true;SwapCache();die();}
	
	
	
	
	start_watchdog();
	

	
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
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}	
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	
	if($SQUIDEnable==0){die();}
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig["EnableFailover"]=$EnableFailover;
	
	if(!isset($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!is_numeric($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if($MonitConfig["MIN_INTERVAL"]<3){$MonitConfig["MIN_INTERVAL"]=3;}
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	
	if(!$GLOBALS["VERBOSE"]){if($time<$MonitConfig["MIN_INTERVAL"]){return;}}
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	
	
	$STAMP_MAX_RESTART_TIME=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART"]);
	if($STAMP_MAX_RESTART_TIME>60){@unlink($GLOBALS["STAMP_MAX_RESTART"]);}
	
	Events("Start: ". basename($pidtime).":{$time}Mn / {$MonitConfig["MIN_INTERVAL"]}Mn STAMP_MAX_RESTART_TIME={$STAMP_MAX_RESTART_TIME}Mn");
	@file_put_contents($pidtime,time());
	
	Checks_mgrinfos($MonitConfig);
	if($MonitConfig["watchdog"]==0){return;}
	
	Checks_Winbindd();
	CheckStoreDirs();
	MemBoosters();
	SwapCache($MonitConfig);
	
	if($NtpdateAD==1){
		$pidtimeNTPT=$unix->file_time_min($pidtimeNTP);
		if($pidtimeNTPT>120){
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");
			@unlink($pidtimeNTP);
			@file_put_contents($pidtimeNTP, time());
		}
	}
	
	
}

function swap_state(){
	
	
	$unix=new unix();
	$caches=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($num, $directory) = each ($caches)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: scanning cache $directory\n";}
		foreach (glob("$directory/swap.*") as $filename) {
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: removing $filename\n";}
			@unlink($filename);	
		}
		
		
	}
	
	
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
	while (list ($CacheDirectory, $val) = each ($GetCachesInsquidConf)){
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

function CheckStoreDirs(){
	$unix=new unix();
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	$mustBuild=false;
	$php5=$unix->LOCATE_PHP5_BIN();
	
	while (list ($CacheDirectory, $val) = each ($GetCachesInsquidConf)){
		if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory\n";}
		if(!is_dir("$CacheDirectory/00")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory/00 no such directory\n";}
			$mustBuild=true;
		}
		
	}
	
	if($mustBuild){
		$cmd="$php5 /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1";
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
		if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: Squid-cache, not installed\n";}
		return;
	}
	

	$t1=time();
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		
	if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: Restarting Squid-cache...\n";}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["RECONFIGURE"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Reconfiguring Squid-cache...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: stopping Squid...\n";}
	stop_squid(true);
	
	if($GLOBALS["SWAPSTATE"]){
		$GLOBALS["FORCE"]=true;
		swap_state();
	}
	if($GLOBALS["OUTPUT"]){echo "Restart......: [INIT][{$GLOBALS["MYPID"]}]: Starting Squid...\n";}
    start_squid(true);
    $took=$unix->distanceOfTimeInWords($t1,time());

    system_admin_events("Squid restarted took: $took", __FUNCTION__, __FILE__, __LINE__, "proxy");
	
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
		squid_admin_notifs("MemBoosters exceed size, Cleaned procedure done\nThese MemBoosters status:\n" .@implode("\n", $WARN), __FUNCTION__, __FILE__, __LINE__, "proxy");
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
		$EnableFailover=$sock->GET_INFO("EnableFailover");
		if(!is_numeric($EnableFailover)){$EnableFailover=1;}
		$MonitConfig["EnableFailover"]=$EnableFailover;		
		if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
		if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
		if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
		if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		$MonitConfig["EnableFailover"]=$EnableFailover;
	}
	
	
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
			if($MonitConfig["EnableFailover"]==1){FailOverDown();}
			$STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
			if(!is_numeric($STAMP_MAX_RESTART)){$STAMP_MAX_RESTART=0;}
			if($STAMP_MAX_RESTART<$MAX_RESTART){
				$STAMP_MAX_RESTART++;
				@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], $STAMP_MAX_RESTART);
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
				Events("Unable to retreive informations from $SquidBinIpaddr:$http_port, restart proxy service\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
				system_admin_events("$curl->error: Unable to retreive informations from $SquidBinIpaddr:$http_port, restart proxy service", __FUNCTION__, __FILE__, __LINE__, "proxy");
				Events("Restarting squid ($STAMP_MAX_RESTART) max time to wait {$MgrInfosMaxTimeOut}s !! $curl->error ".$unix->distanceOfTimeInWords($t0,time(),true));
				restart_squid(true);
				Events("Done Took:".$unix->distanceOfTimeInWords($t0,time(),true));
				return true;
			}else{
				Events("Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
				return true;
			}
		}
	}else{
		@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], 0);
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
				shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos");
			}
		}	
		
	}
	
	$array=MgrInfoToArray($curl->data);
	if(count($array)>5){
		@unlink($SystemInfoCache);
		@file_put_contents("$SystemInfoCache", serialize($array));
	}
	Events("Done ".strlen($curl->data)." bytes Took:".$unix->distanceOfTimeInWords($t0,time(),true));
	if($MonitConfig["watchdog"]==1){Checks_external_webpage($MonitConfig);}
	
}

function FailOverDown(){
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover backup, license error");return;}
	Events("Down failover network interface in order to switch to backup...");
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	shell_exec("/etc/init.d/artica-failover stop");
	
}
function FailOverUp(){
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$GLOBALS[__FUNCTION__]=true;
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover master, license error");return;}
	Events("Up failover network interface in order to turn back to master...");
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
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
	if($unix->IsSquidReverse()){return;}
	$tcp=new networking();
	if(!isset($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	if($MonitConfig["TestExternalWebPage"]==0){return;}
	
	
	
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
	while (list ($index, $val) = each ($ALL_IPS_GET_ARRAY)){$IPZ[]=$index;}
	$IPZ_COUNT=count($IPZ);
	if($IPZ_COUNT==1){$choosennet=$IPZ[0];}else{$choosennet=$IPZ[rand(0,$IPZ_COUNT-1)];}
	
	
	
	
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	$uri=$MonitConfig["ExternalPageToCheck"];
	
	
	if($MonitConfig["ExternalPageListen"]=="127.0.0.1"){$MonitConfig["ExternalPageListen"]=null;}
	if($MonitConfig["ExternalPageListen"]==null){$MonitConfig["ExternalPageListen"]=$choosennet;}
	
	if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage(): choosennet=$choosennet({$MonitConfig["ExternalPageListen"]})\n";}
	
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	$uri=str_replace("%T", time(), $uri);
	$http_port=squid_get_alternate_port();
	
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}	
	
	if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
		$SquidBinIpaddr=$re[1];
		if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
		$http_port=$re[2];
	}
	$STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
	
	
	$curl=new ccurl($uri,true);
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
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
	$curl->Timeout=$MgrInfosMaxTimeOut;
	
	if(!$curl->get()){
		if($GLOBALS["VERBOSE"]){echo $curl->data;}
		Events("FATAL: Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error` ($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri max:$MgrInfosMaxTimeOut seconds Proxy:http://$SquidBinIpaddr:$http_port");
		if($MonitConfig["EnableFailover"]==1){FailOverDown();}
		if($STAMP_MAX_RESTART<$MAX_RESTART){
			if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
			squid_admin_notifs("$curl->error: Unable to get $uri, restart proxy service $executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
			Events("FATAL: Unable to download \"$uri\" with error `$curl->error` ($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri max:$MgrInfosMaxTimeOut seconds Proxy:http://$SquidBinIpaddr:$http_port");
			Events("Restarting squid ($STAMP_MAX_RESTART attempt(s)) !! $curl->error ".$unix->distanceOfTimeInWords($t0,time(),true));
			restart_squid(true);
		}else{
			Events("($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri max:$MgrInfosMaxTimeOut seconds Proxy:http://$SquidBinIpaddr:$http_port");
			Events("Could not restart squid, max $MAX_RESTART restarts as been reached");	
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
			return;
		}
	}
	
	
	
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
	$unix->chown_func("squid","squid", "/var/logs");
	$squid_locate_pinger=$unix->squid_locate_pinger();
	if(is_file($squid_locate_pinger)){@chmod($squid_locate_pinger,4755);}
	
	$pid=SQUID_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT][{$GLOBALS["MYPID"]}]: Already running pid $pid since {$time}mn\n";}
		system_admin_events("Squid seems to already running pid $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}	
	
	$t1=time();
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	SendLogs("Starting squid ". count($GetCachesInsquidConf)." caches to check..");
	$MustBuild=false;
	while (list ($CacheDirectory, $val) = each ($GetCachesInsquidConf)){
		SendLogs("Starting squid $CacheDirectory");
		if(trim($CacheDirectory)==null){continue;}
		if(!is_dir($CacheDirectory)){
			echo "Starting......: Squid Check cache \"$CacheDirectory\" no such directory\n";
			@mkdir($CacheDirectory,0755,true);
			$MustBuild=true;
			continue;
		}
		
		if(!is_dir("$CacheDirectory/00")){
			echo "Starting......: Squid Check cache \"$CacheDirectory/00\" no such directory\n";
			$MustBuild=true;
		}
		$unix->chown_func("squid","squid",$CacheDirectory);
		$unix->chown_func("squid","squid","$CacheDirectory/*");
		$unix->chmod_func(0777, "$CacheDirectory/*");
		$unix->chmod_alldirs(0755, $CacheDirectory);
		@chmod($CacheDirectory, 0755);
	}

	if($MustBuild){
		exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
		while (list ($agent, $val) = each ($results) ){SendLogs("$val");}
	}
	
	
	
	
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
		SendLogs("Squid failed to start...");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		squid_admin_notifs("Squid failed to start\n".@implode("\n", $GLOBALS["LOGS"])."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		system_admin_events("Squid failed to start\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	
	SendLogs("Squid Tests if it listen all connections....");
	for($i=0;$i<10;$i++){
		if(is_started()){SendLogs("Starting squid listen All connections OK");break;}
		SendLogs("Starting squid listen All connections... waiting $i/10");
		sleep(1);
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time());
	SendLogs("Squid success to start PID $pid...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	$php5=$unix->LOCATE_PHP5_BIN();
	
	system_admin_events("Squid success to start PID $pid took $took\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix start squidcache-tail");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart auth-logger");
	$unix->THREAD_COMMAND_SET("$php5 ".basename(__FILE__)."/exec.proxy.pac.php --write");
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

?>