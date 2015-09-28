<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="Webfilter Daemon";
$GLOBALS["AFTER-FATAL-ERROR"]=false;
$GLOBALS["BYSCHEDULE"]=false;
$GLOBALS["UPDATER"]=false;
$GLOBALS["HUMAN"]=false;
$GLOBALS["PID_PATH"]="/var/run/urlfilterdb/ufdbguardd.pid";
$GLOBALS["CACHE_IHM"]="/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--from-schedule#",implode(" ",$argv),$re)){$GLOBALS["BYSCHEDULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--fatal-error#",implode(" ",$argv),$re)){$GLOBALS["AFTER-FATAL-ERROR"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--human#",implode(" ",$argv),$re)){$GLOBALS["HUMAN"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--updater#",implode(" ",$argv),$re)){$GLOBALS["UPDATER"]=true;$GLOBALS["FORCE"]=true;}



$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();@unlink($GLOBALS["CACHE_IHM"]);die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();@unlink($GLOBALS["CACHE_IHM"]);die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();@unlink($GLOBALS["CACHE_IHM"]);die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();@unlink($GLOBALS["CACHE_IHM"]);die();}
if($argv[1]=="--rotatelog"){$GLOBALS["OUTPUT"]=true;rotate();die();}
if($argv[1]=="--force-restart-squid"){$GLOBALS["OUTPUT"]=true;force_restart_squid();die();}



function restart() {
	$unix=new unix();
	$FORCED_TEXT=null;
	$NOTIFY=false;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["FORCE"]){
		$FORCED_TEXT=" (forced)";
	
	}
	
	if($GLOBALS["AFTER-FATAL-ERROR"]){
		$FORCED_TEXT=$FORCED_TEXT. " after a fatal error";
		
	}
	
	
	if($GLOBALS["SCHEDULE_ID"]>0){$NOTIFY=true;squid_admin_mysql(1, "Scheduled task executed: Restart Web filtering service$FORCED_TEXT", "This is a schedule task ID:{$GLOBALS["SCHEDULE_ID"]}",__FILE__,__LINE__);}
	if($GLOBALS["WATCHDOG"]){$NOTIFY=true;squid_admin_mysql(1, "Restart Web filtering service$FORCED_TEXT ( by Watchdog )", "nothing",__FILE__,__LINE__);}
	if($GLOBALS["UFDBTAIL"]){$NOTIFY=true;squid_admin_mysql(1, "Restart Web filtering service$FORCED_TEXT ( by Artica Tailer )", "nothing",__FILE__,__LINE__);}
	
	if(!$NOTIFY){
		squid_admin_mysql(2, "Restart Web filtering service$FORCED_TEXT ( by -- )", "nothing",__FILE__,__LINE__);
	}
	
	stop(true);
	sleep(1);
	start(true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	
}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdb.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function force_restart_squid(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already running",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$php5=$unix->LOCATE_PHP5_BIN();
	build_progress("{stopping} {APP_UFDBGUARD}",5);
	$GLOBALS["FORCE"]=true;
	stop(true);
	stop(true);
	build_progress("{reconfiguring} {APP_UFDBGUARD}",40);
	system("$php5 /usr/share/artica-postfix/exec.squidguard.php --conf --force");
	build_progress("{starting} {APP_UFDBGUARD}",50);
	build_progress("{checking_configuration}",50);
	if(!ChecksConfig()){
		build_progress("{reconfiguring} {APP_SQUID}",70);
		system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	start(true,true);
	build_progress("{reconfiguring} {done}",100);
}

function ChecksConfig(){
	$sock=new sockets();
	$unix=new unix();
	$Urgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
	if($Urgency==1){return true;}
	
	$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ufdbClass=new compile_ufdbguard();
	$datas=$ufdbClass->SetDefaultsConfig($ufdbguardConfig);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: tcpsockets={$datas["tcpsockets"]}\n";}
	$php5=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} tcpsockets =1\n";}
	if($ufdbguardConfig["listen_addr"]=="all"){$ufdbguardConfig["listen_addr"]="127.0.0.1";}
		
	$uri="{$ufdbguardConfig["listen_addr"]}:{$ufdbguardConfig["listen_port"]}";
	build_progress("{APP_UFDBGUARD} $uri",10);
	$port=$ufdbguardConfig["listen_port"];
	$listen_addr=$ufdbguardConfig["listen_addr"];
	
	$SQUID_ALL_PORTS=$unix->SQUID_ALL_PORTS();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Squid listens ".count($SQUID_ALL_PORTS)."\n";}
	if(isset($SQUID_ALL_PORTS[$port])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Squid Port conflict !!!\n";}
		$ufdbguardConfig["listen_port"]=3977;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig", base64_decode(serialize($ufdbguardConfig)));
		system("$php5 /usr/share/artica-postfix/exec.squidguard.php --conf --force");
	}
		
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $ligne,$re)){return true;}
		
	}
	build_progress("{APP_UFDBGUARD} $uri",20);
	return false;
	
}




function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$sock=new sockets();
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	$timeFile="/etc/artica-postfix/pids/UfdbGuardReload.time";
	
	$ufdbguardReloadTTL=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardReloadTTL"));
	$UfdbReloadBySchedule=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbReloadBySchedule"));
	if(!is_numeric($UfdbReloadBySchedule)){$UfdbReloadBySchedule=1;}
	
	$TimeReload=$unix->file_time_min($timeFile);
	
	if(!$GLOBALS["FORCE"]){
		
		if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
		
		if($TimeReload<$ufdbguardReloadTTL){
			$unix->_syslog("{$GLOBALS["TITLENAME"]} Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn", basename(__FILE__));
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn\n";}
			return;
		}
		
		if($UfdbReloadBySchedule==1){
			if(!$GLOBALS["BYSCHEDULE"]){
				$unix->_syslog("{$GLOBALS["TITLENAME"]} Aborting reload, only allowed trough a schedule", basename(__FILE__));
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Aborting reload, only allowed trough a schedule\n";}
				return;
			}
		}
				
	}
	
	if($GLOBALS["FORCE"]){
		$pid=PID_NUM();
		$TimeService=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(1, "Force Webfiltering service Running since {$TimeService}Mn to be reloaded", "last reload since {$TimeReload}Mn",__FILE__,__LINE__);
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	$squid_version=$unix->squid_version();
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $squid_version,$re)){
		if(intval($re[2])>4){$re[2]=4;}
		$squid_version="{$re[1]}.{$re[2]}";
	}
	
	$kill=$unix->find_program("kill");
	$php5=$unix->LOCATE_PHP5_BIN();
	$verif_Squid_Version=verif_Squid_Version();
	if($verif_Squid_Version<>$squid_version){
		squid_admin_mysql(1,"$verif_Squid_Version/$squid_version reconfiguring for squid compatibility",__FILE__,__LINE__,"global-compile");
		$unix->_syslog("{$GLOBALS["TITLENAME"]} $verif_Squid_Version/$squid_version reconfiguring for squid compatibility", basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $verif_Squid_Version/$squid_version reconfiguring for squid compatibility...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build --force");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Squid-Cache version $squid_version\n";}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$unix->_syslog("{$GLOBALS["TITLENAME"]} Reloading PID $pid\n",basename(__FILE__));
		$chmod=$unix->find_program("chmod");
		$chown=$unix->find_program("chown");
		
		
		shell_exec("$chmod 0755 /etc/squid3/ufdbGuard.conf");
		shell_exec("$chown squid:squid /etc/squid3");
		shell_exec("$chown squid:squid /etc/squid3/ufdbGuard.conf");
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reloading PID $pid\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --dbmem");
		shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --reload");
		shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
		
	}else{
		start(true);
	}
	
}

function Verif_Squid_Version(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#^squid-version\s+.*?([0-9\.]+)#",$ligne,$re)){
			return $re[1];
		}
		
	}
	
	return "3.3";
	
}




function start($aspid=false,$nochecksquid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("ufdbguardd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ufdbguardd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if($GLOBALS["MONIT"]){@file_put_contents($GLOBALS["PID_PATH"],$pid);}
		return;
	}
	
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	$SquidUFDBUrgency=intval($sock->GET_INFO("SquidUFDBUrgency"));
	if($SquidUFDBUrgency==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} * * * * Your Web filtering is on Emergency mode ! * * * *\n";}
		stop();
		return;		
		
	}

	if($EnableUfdbGuard==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableUfdbGuard)\n";}
		stop();
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	
	$PossibleDirs[]="/var/lib/ftpunivtlse1fr";
	$PossibleDirs[]="/var/lib/ufdbartica";
	$PossibleDirs[]="/var/lib/squidguard";
	
	while (list ($index, $Directory) = each ($PossibleDirs) ){
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} permissions on `$Directory`\n";}
		
		if(is_link($Directory)){$Directory=readlink($Directory);}
		$f=explode("/",$Directory);
		while (list ($index, $subdir) = each ($f) ){
			if($subdir==null){continue;}
			$dir=$dir."/$subdir";
			@chmod($dir,0755);
		}
		
		@chmod("$Directory",0755);
		$unix->chown_func("squid", "squid","$Directory");
	
	}
	
	
	@mkdir(dirname($GLOBALS["PID_PATH"]),0755,true);
	@mkdir("/var/lib/squidguard/security",0755,true);
	$unix->chown_func("squid", "squid",dirname($GLOBALS["PID_PATH"]));
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$php=$unix->LOCATE_PHP5_BIN();
	@chmod($GLOBALS["PID_PATH"],0755);
	

	
	$unix->chown_func("squid", "squid","/var/lib/squidguard/security/cacerts");
	@chmod("/var/lib/squidguard/security/cacerts",0755);
	
	if(!is_file("/etc/squid3/ufdbGuard.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} building settings\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build >/dev/null 2>&1");
		
	}
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["listen_port"])){$datas["listen_port"]=3977;}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	$Threads=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbGuardThreads"));
	if($Threads==0){$Threads=64;}
	if($Threads>140){$Threads=140;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pid path: {$GLOBALS["PID_PATH"]}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Threads:$Threads\n";}
	killbyports();
		
	@unlink($GLOBALS["PID_PATH"]);
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --dbmem >/dev/null");
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --notify-start >/dev/null");
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --ufdbguard >/dev/null");
	system("/etc/init.d/ufdb-tail start");
	
	
	if(!is_file("/etc/squid3/ufdbGuard.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/squid3/ufdbGuard.conf no such file !!\n";}
		system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
	}
	
	@mkdir("/etc/artica-postfix/pids",0755,true);
	shell_exec("$chmod 0755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chown squid:squid /etc/squid3");
	shell_exec("$chown squid:squid /etc/squid3/ufdbGuard.conf");
	@unlink("/etc/artica-postfix/pids/UfdbGuardReload.time");
	@file_put_contents("/etc/artica-postfix/pids/UfdbGuardReload.time", time());
	squid_admin_mysql(1, "Starting the Web filtering Daemon...", "This is a notification in order to inform:\n".basename(__FILE__)." script start the Web filtering Dameon service",__FILE__,__LINE__);
	$cmd="$Masterbin -c /etc/squid3/ufdbGuard.conf -U squid -w $Threads -N >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	system($cmd);
	
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
	@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if(IsPortListen()==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting (no listen port)\n";}
			continue;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Checking configuration...\n";}
		if(!$nochecksquid){build_progress("{checking_configuration}",50);
		if(!ChecksConfig()){
			build_progress("{reconfiguring} {APP_SQUID}",70);
			squid_admin_mysql(1,"Web filtering is not linked with the proxy service [action=reconfigure]", null, __FILE__,__LINE__);
			system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		}}
		@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
		return true;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	


}

function stop($aspid=false){
	if($GLOBALS["MONIT"]){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} runned by Monit, abort\n";}
		return;}
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Artica script already running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		killbyports();
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} notify framework\n";}
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --notify-stop");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		killbyports();
		@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
	killbyports();
	@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
}

function IsPortListen(){
	$sock=new sockets();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if($datas["tcpsockets"]==0){return 1;}
	count($unix->PIDOF_BY_PORT($datas["listen_port"]));
}

function killbyports(){
	$sock=new sockets();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if($datas["tcpsockets"]==0){return 1;}
	$PIDS=$unix->PIDOF_BY_PORT($datas["listen_port"]);
	if(count($PIDS)==0){if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens {$datas["listen_port"]}...\n";}return;}
	while (list ($pid, $b) = each ($PIDS) ){
		if($unix->process_exists($pid)){
			$cmdline=@file_get_contents("/proc/$pid/cmdline");
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens {$datas["listen_port"]} TCP port\n";}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
			unix_system_kill_force($pid);
		}
	}
	
	
}
function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/urlfilterdb/ufdbguardd.pid");
	if($unix->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
		}
	$Masterbin=$unix->find_program("ufdbguardd");
	$pid=$unix->PIDOF_PATTERN("ufdbguardd.*?-c.*?conf");
	if($unix->process_exists($pid)){return $pid;}
}
?>