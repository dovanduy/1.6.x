<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_squid();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_squid();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_squid();die();}
	if($argv[1]=="--squidz"){$GLOBALS["OUTPUT"]=true;squidz();die();}
	if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd_squid();die();}
	if($argv[1]=="--tests-smtp"){$GLOBALS["OUTPUT"]=true;test_smtp_watchdog();die();}
	
	
	start_watchdog();
	

	
function start_watchdog(){
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$time=$unix->file_time_min($pidtime);
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	if(!isset($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!is_numeric($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if($MonitConfig["MIN_INTERVAL"]<3){$MonitConfig["MIN_INTERVAL"]=3;}
	if(!$GLOBALS["VERBOSE"]){
		if($time<$MonitConfig["MIN_INTERVAL"]){
			return;
		}
		
	}
	
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if($MonitConfig["watchdog"]==0){return;}
	
	Checks_mgrinfos();
	Checks_Winbindd();
	
	
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
	
	$GetCachesInsquidConf=GetCachesInsquidConf();
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

	$t1=time();
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Asking to restart squid\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Restarting Squid-cache...\n";}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["RECONFIGURE"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Reconfiguring Squid-cache...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	stop_squid($aspid);
    start_squid($aspid);
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
	shell_exec("$chmod 0750 /var/lib/samba/winbindd_privileged >/dev/null 2>&1");
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


function Checks_mgrinfos(){
	$sock=new sockets();
	$unix=new unix();
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}
	
	if($MonitConfig["watchdog"]==0){
		if($GLOBALS["VERBOSE"]){echo "Watchdog is not Set as SquidWatchdogMonitConfig aborting....\n";}
		return;}	
	
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
	
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
	
	
	$http_port=squid_get_alternate_port();
	
	if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
		$SquidBinIpaddr=$re[1];
		if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
		$http_port=$re[2];
	}	
	
	
	
	
	$t0=time();
	$curl=new ccurl("http://$SquidBinIpaddr:$http_port/squid-internal-mgr/info");
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=$MgrInfosMaxTimeOut;
	if(!$curl->get()){
		$STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
		if(!is_numeric($STAMP_MAX_RESTART)){$STAMP_MAX_RESTART=0;}
		if($STAMP_MAX_RESTART<$MAX_RESTART){
			$STAMP_MAX_RESTART++;
			@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], $STAMP_MAX_RESTART);
			if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
			squid_admin_notifs("Unable to retreive informations from $SquidBinIpaddr:$http_port, restart proxy service\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
			system_admin_events("$curl->error: Unable to retreive informations from $SquidBinIpaddr:$http_port, restart proxy service", __FUNCTION__, __FILE__, __LINE__, "proxy");
			Events("Restarting squid ($STAMP_MAX_RESTART) max time to wait {$MgrInfosMaxTimeOut}s !! $curl->error ".$unix->distanceOfTimeInWords($t0,time(),true));
			restart_squid(true);
			Events("Done Took:".$unix->distanceOfTimeInWords($t0,time(),true));
			return true;
		}else{
			Events("Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
			return true;
		}
	}else{
		@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], 0);
	}
	$datas=$curl->data;
	Events("Done ".strlen($datas)." bytes\nTook:".$unix->distanceOfTimeInWords($t0,time(),true));
	Checks_external_webpage($MonitConfig);
	
}

function Checks_external_webpage($MonitConfig){
	$sock=new sockets();
	$unix=new unix();
	
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	$uri=$MonitConfig["ExternalPageToCheck"];
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
	Events("($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri\nmax:$MgrInfosMaxTimeOut seconds\nProxy:http://$SquidBinIpaddr:$http_port");
	
	$curl=new ccurl($uri);
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	$t0=time();
	$curl->ArticaProxyServerEnabled="yes";
	$curl->ArticaProxyServerName=$SquidBinIpaddr;
	$curl->interface="127.0.0.1";
	$curl->ArticaProxyServerPort=$http_port;
	$curl->NoHTTP_POST=true;
	$curl->Timeout=$MgrInfosMaxTimeOut;
	
	
	
	
	
	if(!$curl->get()){
		if($STAMP_MAX_RESTART<$MAX_RESTART){
			if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
			squid_admin_notifs("$curl->error: Unable to get $uri, restart proxy service\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
			system_admin_events("$curl->error: Unable to get $uri, restart proxy service", __FUNCTION__, __FILE__, __LINE__, "proxy");
			Events("Restarting squid ($STAMP_MAX_RESTART attempt(s)) !! $curl->error ".$unix->distanceOfTimeInWords($t0,time(),true));
			restart_squid(true);
		}else{
			Events("Restarting squid stopped, max $MAX_RESTART restart attempts ");
			return;			
		}
	}
	$datas=$curl->data;
	$length=strlen($datas);
	$unit="bytes";
	if($length>1024){$length=$length/1024;$unit="Ko";}
	if($length>1024){$length=$length/1024;$unit="Mo";}
	$length=round($length,2);
	Events("Done $length $unit\nTook:".$unix->distanceOfTimeInWords($t0,time(),true));		
	
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
	$sock=new sockets();
	$reconfigure=false;
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$su_bin=$unix->find_program("su");
	initd_squid();
	
	if($SQUIDEnable==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Squid is disabled...\n";}
		return;
	}
	
	if(is_file("/etc/artica-postfix/squid.lock")){
		$time=$unix->file_time_min("/etc/artica-postfix/squid.lock");
		if($time<60){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Squid is locked (since {$time}Mn...\n";}
			return;
		}
		@unlink("/etc/artica-postfix/squid.lock");		
	}
	
	if(!is_file("/etc/init.d/cache-tail")){
		buil_init_squid_cache_log();
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			system_admin_events("Start_squid:: Already task running PID $oldpid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		system_admin_events("Squid not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}	
	
	if(!is_file("/etc/squid3/malwares.acl")){@file_put_contents("/etc/squid3/malwares.acl", "\n");}
	if(!is_file("/etc/squid3/squid-block.acl")){@file_put_contents("/etc/squid3/squid-block.acl", "\n");}
	
	if(!is_file("/etc/squid3/squid.conf")){
		$reconfigure=true;
		
	}
	
	$EXPLODED=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $val) = each ($EXPLODED)){
		if(preg_match("#INSERT YOUR OWN RULE#", $val)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: squid must be reconfigured...\n";}
			$reconfigure=true;
		}
	}
	
	if($reconfigure){
		if($GLOBALS["OUTPUT"]){
			system("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading");
		}else{
			exec("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading 2>&1",$GLOBALS["LOGS"]);
		}
		
	}	
	
	
	shell_exec("$php /usr/share/artica-postfix/exec.initd-squid.php >/dev/null 2>&1");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1");
	exec("$php /usr/share/artica-postfix/exec.squid.transparent.php",$GLOBALS["LOGS"]);
	@mkdir("/var/log/squid",true,0750);
	@mkdir("/home/squid/cache",true,0750);
	@mkdir("/etc/squid3",true,0750);
	@mkdir("/var/lib/squidguard",true,0750);
	@mkdir("/var/run/squid",true,0750);
	$unix->chmod_func(0700, "/var/log/squid/*");
	$unix->chmod_func(0755, "/var/run/squid/*");
	$unix->chown_func("squid","squid", "/var/log/squid/*");
	$unix->chown_func("squid","squid", "/etc/squid3/*");
	$unix->chown_func("squid","squid", "/home/squid/cache");
	$unix->chown_func("squid","squid", "/var/run/squid");
	$pid=SQUID_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already running pid $pid since {$time}mn\n";}
		system_admin_events("Squid seems to already running pid $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}	
	
	$t1=time();
	$GetCachesInsquidConf=GetCachesInsquidConf();
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
		$unix->chmod_alldirs(0755, $CacheDirectory);
		@chmod($CacheDirectory, 0755);
	}

	if($MustBuild){
		exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
	}
	
	while (list ($agent, $val) = each ($results) ){SendLogs("$val");}	
	
	
	SendLogs("Starting squid $squidbin....");
	@copy("/var/log/squid/cache.log", "/var/log/squid/cache.log.".time());
	exec("$squidbin -f /etc/squid3/squid.conf 2>&1",$GLOBALS["LOGS"]);
	
	for($i=0;$i<120;$i++){
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){
			SendLogs("Starting squid started pid .$pid...");
			break;}
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
	
	for($i=0;$i<120;$i++){
		if(is_started()){
			SendLogs("Starting squid listen All connections OK");
			break;
		}
		sleep(1);
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time());
	SendLogs("Squid success to start PID $pid...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	
	squid_admin_notifs("Squid success to start PID $pid\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
	system_admin_events("Squid success to start PID $pid took $took\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix start squidcache-tail");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart auth-logger");	
}


function GetCachesInsquidConf(){
	$unix=new unix();
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();

	$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
			writelogs("Directory: {$re[2]} type={$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			$array[trim($re[2])]=trim($re[2]);
		}

	}
	if($GLOBALS["VERBOSE"]){print_r($array);}
	return $array;

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
				Events("Squid live since {$timeTTL}Mn, this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn");
				shell_exec("$squidbin -k reconfigure >/dev/null 2>&1");
				return;
			}
			
		}
	}
	
	
	
	if(!$unix->process_exists($pid)){
		system_admin_events("Squid is not running, start it...", __FUNCTION__, __FILE__, __LINE__, "proxy");
		start_squid();
		return;
	}
	
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
	
	
	

	if(is_file("/dev/shm/squid-cache_mem.shm")){
		SendLogs("removing /dev/shm/squid-cache_mem.shm");
		@unlink("/dev/shm/squid-cache_mem.shm");
	}
	if(is_file("/dev/shm/squid-squid-page-pool.shm")){
		SendLogs("removing /dev/shm/squid-squid-page-pool.shm");
		@unlink("/dev/shm/squid-squid-page-pool.shm");
	}	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_notifs("Success to stop Squid\n".@implode("\n", $GLOBALS["LOGS"])."\n$executed", __FUNCTION__, __FILE__, __LINE__, "proxy");
	system_admin_events("Squid success to stop\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	
}

function SendLogs($text){
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $text\n";}
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

function initd_squid(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          Squid 3 cache Proxy";
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
	$conf[]="    /usr/share/artica-postfix/bin/artica-install -watchdog squidcache-tail \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    /usr/share/artica-postfix/bin/artica-install -shutdown squidcache-tail \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="	  /usr/share/artica-postfix/bin/artica-install -shutdown squidcache-tail \$1 \$2";
	$conf[]="     /usr/share/artica-postfix/bin/artica-install -watchdog squidcache-tail \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     /usr/share/artica-postfix/bin/artica-install -shutdown squidcache-tail \$1 \$2";
	$conf[]="     /usr/share/artica-postfix/bin/artica-install -watchdog squidcache-tail \$1 \$2";	
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
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add cache-tail >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 cache-tail on >/dev/null 2>&1");
	}
}