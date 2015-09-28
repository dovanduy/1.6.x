<?php
if(isset($_GET["verbose"])){ini_set_verbosedx();}else{	ini_set('display_errors', 0);ini_set('error_reporting', 0);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	session_save_path('/home/squid/error_page_sessions');
	session_cache_expire(10);
	
	if(isset($_POST["smtp-send-email"])){parseTemplate_smtp_post();exit;}
	if(isset($_POST["unlock-www"])){parseTemplate_unlock_save();exit;}
	if(isset($_POST["unlock-ticket"])){parseTemplate_ticket_save();exit;}
	
	if(isset($_GET["unlock"])){parseTemplate_unlock();exit;}
	if(isset($_GET["ticket"])){parseTemplate_ticket();exit;}
	if(isset($_GET["release-ticket"])){parseTemplate_release_ticket();exit;}
	
	
	
	if(isset($_GET["SquidGuardWebAllowUnblockSinglePass"])){parseTemplate_SinglePassWord();die();}
	if(isset($_GET["smtp-send-js"])){parseTemplate_sendemail_js();exit;}
	if(isset($_REQUEST["send-smtp-notif"])){parseTemplate_sendemail_perform();exit;}
	if(isset($_POST["USERNAME"])){parseTemplate_LocalDB_receive();die();}
	if(isset($_GET["SquidGuardWebUseLocalDatabase"])){parseTemplate_LocalDB();die();}
	parseTemplate();die();}

if(preg_match("#--ouput#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);
$GLOBALS["CMDLINEXEC"]=@implode("\nParams:",$argv);

include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.dansguardian.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdb.microsoft.inc");


if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(count($argv)>0){
	$imploded=implode(" ",$argv);
	
	if(preg_match("#--(output|ouptut)#",$imploded)){
		$GLOBALS["OUTPUT"]=true;
	}
	
	if(preg_match("#--verbose#",$imploded)){
			$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
			$GLOBALS["OUTPUT"]=true;ini_set_verbosed(); 
	}
	
	
	
	if(preg_match("#--reload#",$imploded)){$GLOBALS["RELOAD"]=true;}
	if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--shalla#",$imploded)){$GLOBALS["SHALLA"]=true;}
	if(preg_match("#--restart#",$imploded)){$GLOBALS["RESTART"]=true;}
	if(preg_match("#--catto=(.+?)\s+#",$imploded,$re)){$GLOBALS["CATTO"]=$re[1];}
	if($argv[1]=="--disks"){DisksStatus();exit;}
	if($argv[1]=="--version"){checksVersion();exit;}
	if($argv[1]=="--dump-adrules"){dump_adrules($argv[2]);exit;}
	if($argv[1]=="--dbmem"){ufdbdatabases_in_mem();exit;}
	if($argv[1]=="--notify-start"){ufdguard_start_notify();exit;}
	if($argv[1]=="--artica-db-status"){ufdguard_artica_db_status();exit;}
	
	
	
	
	$argvs=$argv;
	unset($argvs[0]);
	
	if($argv[1]=="--stop"){stop_ufdbguard();exit;}
	if($argv[1]=="--reload"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--reload-ufdb"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--dansguardian"){buildDans();exit;}
	if($argv[1]=="--databases-status"){databases_status();exit;}
	if($argv[1]=="--ufdbguard-status"){print_r(UFDBGUARD_STATUS());exit;}
	if($argv[1]=="--cron-compile"){cron_compile();exit;}
	if($argv[1]=="--compile-category"){UFDBGUARD_COMPILE_CATEGORY($argv[2]);exit;}
	if($argv[1]=="--compile-all-categories"){UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--ufdbguard-recompile-dbs"){echo UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--phraselists"){echo CompileCategoryWords();exit;}
	if($argv[1]=="--fix1"){echo FIX_1_CATEGORY_CHECKED();exit;}
	if($argv[1]=="--bads"){echo remove_bad_files();exit;}
	if($argv[1]=="--reload131"){exit;}
	
	
	
	$GLOBALS["EXECUTEDCMDLINE"]=@implode(" ", $argvs);
	ufdbguard_admin_events("receive ".$GLOBALS["EXECUTEDCMDLINE"],"MAIN",__FILE__,__LINE__,"config");
	if($GLOBALS["VERBOSE"]){echo "Execute ".@implode(" ", $argv)."\n";}
	
	if($argv[1]=="--inject"){echo inject($argv[2],$argv[3]);exit;} // category filepath
	if($argv[1]=="--parse"){echo inject($argv[2],$argv[3],$argv[4]);exit;}
	if($argv[1]=="--conf"){echo build();exit;}
	if($argv[1]=="--ufdb-monit"){echo ufdbguard_watchdog();exit;}
	
	
	if($argv[1]=="--ufdbguard-compile"){echo UFDBGUARD_COMPILE_SINGLE_DB($argv[2]);exit;}	
	if($argv[1]=="--ufdbguard-dbs"){echo UFDBGUARD_COMPILE_DB();exit;}
	if($argv[1]=="--ufdbguard-miss-dbs"){echo ufdbguard_recompile_missing_dbs();exit;}
	
	if($argv[1]=="--ufdbguard-schedule"){ufdbguard_schedule();exit;}
	if($argv[1]=="--ufdbguard-start"){ufdbguard_start();exit;}
	if($argv[1]=="--list-missdbs"){BuildMissingUfdBguardDBS(false,true);exit;}				
	if($argv[1]=="--parsedir"){ParseDirectory($argv[2]);exit;}
	if($argv[1]=="--notify-dnsmasq"){notify_remote_proxys_dnsmasq();exit;}
	if($argv[1]=='--build-ufdb-smoothly'){$GLOBALS["FORCE"]=true;echo build_ufdbguard_smooth();echo "Starting......: ".date("H:i:s")." Starting UfdGuard FINISH DONE\n";exit;}
	if($argv[1]=='--apply-restart'){$GLOBALS["FORCE"]=true;echo build_ufdbguard_restart();;exit;}
	
	
}
	


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$timefile=$unix->PROCCESS_TIME_MIN($pid);
	if($timefile<6){
		writelogs(basename(__FILE__).": Already running PID $pid since {$timefile}mn.. aborting the process",
		basename(__FILE__),__FILE__,__LINE__);
		die();
	}else{
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
}
@file_put_contents($pidfile, getmypid());
if($GLOBALS["VERBOSE"]){echo "New PID ".getmypid()." [1]={$argv[1]}\n";}

if($argv[1]=="--categories"){build_categories();exit;}
if(isset($argv[2])){if($argv[2]=="--reload"){$GLOBALS["RELOAD"]=true;}}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--status"){echo status();exit;}
if($argv[1]=="--compile"){echo compile_databases();exit;}
if($argv[1]=="--db-status"){print_r(databasesStatus());exit;}
if($argv[1]=="--db-status-www"){echo serialize(databasesStatus());exit;}

if($argv[1]=="--compile-single"){echo CompileSingleDB($argv[2]);exit;}
if($argv[1]=="--conf"){echo conf();exit;}



//http://cri.univ-tlse1.fr/documentations/cache/squidguard.html


function build_categories(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT LOWER(pattern) FROM category_porn WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '/tmp/porn.txt' FIELDS OPTIONALLY ENCLOSED BY 'n'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	
}

function build_progress($text,$pourc){
	echo "[{$pourc}%]: $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function build_ufdbguard_restart(){
	$GLOBALS["build_ufdbguard_HUP_EXECUTED"]=true;
	$GLOBALS["FORCE"]=true;
	build_ufdbguard_config();
	build_progress("{apply_restart}: {restarting_service}",70);
	system("/etc/init.d/ufdb restart --force");
	build_progress("{apply_restart}: {reloading_proxy_service}",90);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure 2>&1",$RESULTS);
	squid_admin_mysql(1,"Reloading proxy service (Web filtering)",@implode("\n", $RESULTS),__FILE__,__LINE__);
	sleep(5);
	build_progress("{apply_restart}: {done}",100);
}


function build_ufdbguard_smooth(){
	$users=new usersMenus();
	$unix=new unix();
	if(!$users->APP_UFDBGUARD_INSTALLED){echo "Starting......: ".date("H:i:s")." Webfiltering service is not installed, aborting\n";return;}
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){
		echo "Starting......: ".date("H:i:s")." It use Statistics appliance, aborting\n";
		build_progress("use Statistics appliance, aborting",110);
		return;
	}
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build_ufdbguard_smooth() -> reconfigure UfdbGuardd", basename(__FILE__));}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". date("Y-m-d H:i:s")."\n";
	build_ufdbguard_config();
	build_progress("{reloading_service}",70);
	if(!build_ufdbguard_HUP()){
		build_progress("{reloading_service} {failed}",75);
		ufdbguard_start();
	}
	
	if(!build_ufdbguard_isinconf()){
		build_progress("{reconfiguring_proxy_service}",95);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		
	}
	
	
	build_progress("{done}",100);
}


function build_ufdbguard_isinconf(){

	$squidconf="/etc/squid3/squid.conf";
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableTransparent27")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableTransparent27", 0);}
	$EnableTransparent27=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableTransparent27"));
	if($EnableTransparent27==1){$squidconf="/etc/squid27/squid.conf";}
	
	$f=explode("\n",@file_get_contents($squidconf));
	while (list($num,$val)=each($f)){
		if(preg_match("#ufdbgclient#i", $val)){return true;}
	}

}


function build_ufdbguard_HUP(){
	if(isset($GLOBALS["build_ufdbguard_HUP_EXECUTED"])){return;}
	$GLOBALS["build_ufdbguard_HUP_EXECUTED"]=true;
	$unix=new unix();
	$sock=new sockets();$forceTXT=null;
	$ufdbguardReloadTTL=intval($sock->GET_INFO("ufdbguardReloadTTL"));
	if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
	shell_exec("$rm /home/squid/error_page_cache/*");
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	
	$timeFile="/etc/artica-postfix/pids/UfdbGuardReload.time";
	$TimeReload=$unix->file_time_min($timeFile);
	if(!$GLOBALS["FORCE"]){
		if($TimeReload<$ufdbguardReloadTTL){
			build_progress("{reloading_service} {failed}",110);
			$unix->_syslog("Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn", basename(__FILE__));
			echo "Starting......: ".date("H:i:s")." Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn\n";
			return;
		}
	}else{
		echo "Starting......: ".date("H:i:s")." --- FORCED --- ufdbGuard last reload was {$TimeReload}mn\n";
	}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	$pid=ufdbguard_pid();
	build_progress("{reloading_service} $pid",71);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(strlen($ufdbguardd)<5){WriteToSyslogMail("ufdbguardd no such binary", basename(__FILE__));return;}
	$kill=$unix->find_program("kill");

	
	
	
if($unix->process_exists($pid)){
		$processTTL=intval($unix->PROCCESS_TIME_MIN($pid));
		
		$LastTime=intval($unix->file_time_min($timeFile));
		build_progress("{reloading_service} $pid {$processTTL}Mn",72);
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service TTL {$processTTL}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Last config since {$LastTime}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading Max reload {$ufdbguardReloadTTL}Mn\n";
		
		if(!$GLOBALS["FORCE"]){
			echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading force is disabled\n";
			if($LastTime<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] last reload {$LastTime}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn - Current {$LastTime}mn\n$called\n";
				return;
			}			
			
			
			if($processTTL<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] {$processTTL}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service PID: $pid  Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn\n$called\n";
				return;
			}
		}
		
		
		if($GLOBALS["FORCE"]){ $forceTXT=" with option FORCE enabled";$prefix="[FORCED]:";}
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service PID:$pid {$processTTL}mn\n";
		squid_admin_mysql(1, "{$prefix}Reloading Web Filtering service PID: $pid TTL {$processTTL}Mn","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
		
		build_progress("{reloading_service} HUP $pid",75);
		unix_system_HUP($pid);
		build_progress("{reloading_proxy_service}",76);
		shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
		$squidbin=$unix->LOCATE_SQUID_BIN();
		squid_admin_mysql(1, "{$prefix}Reloading Proxy service",null,__FILE__,__LINE__);
		system("$squidbin -k reconfigure");
		return true;
}
	
	squid_admin_mysql(1, "Warning, Reloading Web Filtering but not running [action=start]","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
	echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service no pid is found, Starting service...\n";
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	build_progress("{starting_service}",76);
	if(!ufdbguard_start()){return;}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering Service restarting ufdb-tail process\n";
	shell_exec("/etc/init.d/ufdb-tail restart");
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
	squid_admin_mysql(1, "{$prefix}Reloading Proxy service",null,__FILE__,__LINE__);
	system("$squidbin -k reconfigure");
	build_progress("{starting_service} {done}",77);
	return true;
}

function ufdbguard_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/tmp/ufdbguardd.pid");
	if($unix->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	$ufdbguardd=$unix->find_program("ufdbguardd");
	return $unix->PIDOF($ufdbguardd);
}

function ufdguard_start_notify(){
	squid_admin_mysql(2, "{starting_web_filtering} engine service by init.d script","",__FILE__,__LINE__);
	$unix=new unix();
	$fuser=$unix->find_program("fuser");
	$port=ufdguard_get_listen_port();
	$results=array();
	echo "Starting......: ".date("H:i:s")." Webfiltering service Listen on port $port\n";
	$cmd="$fuser $port/tcp 2>&1";
	exec("$cmd",$results);
	echo "Starting......: ".date("H:i:s")." Webfiltering service `$cmd` ". count($results) ." lines.\n";
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#$port\/tcp:(.+)#", $ligne,$re)){
			$ff=explode(" ", $re[1]);
			while (list ($index, $ligne2) = each ($ff) ){
				$ligne2=trim($ligne2);
				if(!is_numeric($ligne2)){continue;}
				echo "Starting......: ".date("H:i:s")." Webfiltering service killing PID $ligne2\n";
				$unix->KILL_PROCESS($ligne2,9);
			}
		}
	}
}


function ufdguard_get_listen_port(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#^port\s+([0-9]+)#", $ligne,$re)){return $re[1];}
		
	}
	return 3977;
}




function ufdbguard_start(){
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		build_progress("Already task executed", 110);
		echo "Starting......: ".date("H:i:s")." Webfiltering service Starting service aborted, task pid already running $pid\n";
		writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$master_pid=ufdbguard_pid();

	if(!$unix->process_exists($master_pid)){
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon seems to not running, trying with pidof", basename(__FILE__));}
		$master_pid=$unix->PIDOF($ufdbguardd_path);
		if($unix->process_exists($master_pid)){
			echo "Starting......: ".date("H:i:s")." UfdGuard master is running, updating PID file with $master_pid\n";
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master is running, updating PID file with $master_pid", basename(__FILE__));}
			@file_put_contents($pid_path,$master_pid);	
			build_progress("Already running...",76);
			return true;
		}
	}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	if($EnableUfdbGuard==0){echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service Aborting, service is disabled\n";return;}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	squid_admin_mysql(2, "{starting_web_filtering} engine service","$trace\n{$GLOBALS["CMDLINEXEC"]}");
	ufdbguard_admin_events("Asking to start ufdbguard $trace",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service...\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting UfdGuard master service...", basename(__FILE__));}
	@mkdir("/var/log/ufdbguard",0755,true);
	@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "#");
	@chown("/var/log/ufdbguard/ufdbguardd.log", "squid");
	@chgrp("/var/log/ufdbguard/ufdbguardd.log", "squid");	
	
	
	shell_exec("$nohup /etc/init.d/ufdb start >/dev/null 2>&1 &");
	
	
	for($i=1;$i<5;$i++){
		build_progress("Starting {webfiltering} waiting $i/5",76);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Starting UfdGuard  waiting $i/5\n";}
		sleep(1);
		$pid=ufdbguard_pid();
		if($unix->process_exists($pid)){break;}
	}
	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master init.d ufdb done...\n";
	$master_pid=ufdbguard_pid();
	if(!$unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service failed...\n";
		squid_admin_mysql(0, "{starting_web_filtering} engine service failed","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
		return false;
	}
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master success pid $master_pid...\n";
	squid_admin_mysql(2, "{starting_web_filtering} engine service success","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master ufdbguard_start() function done\n";
	return true;
	
}

function checksVersion(){
	$unix=new unix();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(!is_file($ufdbguardd)){return;}
	$mustcompile=false;
	exec("ufdbguardd -v 2>&1",$results);
	while (list ($a, $line) = each ($results)){
		
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){
			$version=$re[1];
			$version=str_replace(".", "", $version);
			break;
		}
	}
	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard binary version $version\n";
	if($version<130){$mustcompile=true;}
	
	
	if(!$mustcompile){
		$binadate=filemtime($ufdbguardd);
		$fileatime=fileatime($ufdbguardd);
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard version date $binadate (".date("Y-m-d",$binadate).")\n";
		if($binadate<1358240994){
			$mustcompile=true;
		}
	}
	
	if($mustcompile){
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard must be updated !!\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD");
	}
	
}


function build_ufdbguard_config(){
	checksVersion();
	$sock=new sockets();
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$ln=$unix->find_program("ln");
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
	$unix->send_email_events("Order to rebuild ufdbGuard config" , $called, "proxy");
	$sock=new sockets();	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$users=new usersMenus();
	
	@mkdir("/var/tmp",0775,true);
	@mkdir("/etc/ufdbguard",0777,true);
	@mkdir("/etc/squid3",0755,true);
	@mkdir("/var/log/squid",0755,true);
	@mkdir("/var/lib/ufdbartica",0755,true);
	@unlink("/etc/ufdbguard/ufdbGuard.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");	
	remove_bad_files();
	
	build_progress("Building parameters",10);
	
	$ufdb=new compile_ufdbguard();
	$datas=$ufdb->buildConfig();	
	
	if(is_file("/var/log/squid/UfdbguardCache.db")){@unlink("/var/log/squid/UfdbguardCache.db"); }
	
	
	if($EnableWebProxyStatsAppliance==1){
		@file_put_contents("/usr/share/artica-postfix/ressources/databases/ufdbGuard.conf",$datas);
	}

	if($DenyUfdbWriteConf==0){
		build_progress("Saving configuration",60);
		@file_put_contents("/etc/ufdbguard/ufdbGuard.conf",$datas);
		@file_put_contents("/etc/squid3/ufdbGuard.conf",$datas);
		$sock->TOP_NOTIFY("{webfiltering_parameters_was_saved}");
	}
	shell_exec("$chmod 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/ufdbguard");	
	
	shell_exec("chown -R squid:squid /etc/ufdbguard");
	shell_exec("chown -R squid:squid /var/log/squid");
	shell_exec("chown -R squid:squid /etc/squid3");
	shell_exec("chown -R squid:squid /var/lib/ufdbartica");
	build_progress("Saving configuration {done}",65);
	
}


function conf(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	
	@mkdir("/var/tmp",0775,true);
	
	
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){
		@mkdir("/var/log/ufdbguard",0755,true);
		@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");
		shell_exec("chmod 777 /var/log/ufdbguard/ufdbguardd.log");
	}
	
	
	if(is_file("/usr/sbin/ufdbguardd")){
		if(!is_file("/usr/bin/ufdbguardd")){
			$unix=new unix();
			$ln=$unix->find_program("ln");
			shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");
		}
	}
	@mkdir("/etc/ufdbguard",0755,true);
	
	build_ufdbguard_config();
	buildDans();
	ufdbguard_schedule();

	
	if($users->APP_UFDBGUARD_INSTALLED){
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 755 /etc >/dev/null 2>&1");
		shell_exec("$chmod 755 /etc/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/squid >/dev/null 2>&1");
		shell_exec("$chmod -R 755 /var/lib/squidguard >/dev/null 2>&1 &");	
		ufdbguard_admin_events("Asking to reload ufdbguard",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");	
		build_ufdbguard_HUP();
		
	}
	
	
}

function buildDans(){
	if(!is_dir("/var/run/dansguardian")){@mkdir("/var/run/dansguardian",0755,true);}
	$dans=new compile_dansguardian();
	$dans->build();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableWebProxyStatsAppliance==1){
		echo "TO DEV -> Send to stats appliance !\n";
		return;
	}		
}

function remove_bad_files(){
	
	$unix=new unix();
	
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $b) = each ($dirs)){
		$dirname=basename($directory);
		if(is_link("$directory/$dirname")){
			echo "Starting......: ".date("H:i:s")." Webfiltering service removing $dirname/$dirname bad file\n";
			@unlink("$directory/$dirname");
		}
	}
	
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service removing bad files done...\n";
}




function build(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	send_email_events("Order to rebuild filters configuration",@implode("\nParams:",$argv),"proxy");
	$funtion=__FUNCTION__;
	if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__." Loading libraries\n";}
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$squidbin=$unix->find_program("squid3");
	$nohup=$unix->find_program("nohup");
	$unix->SystemCreateUser("squid","squid");
	@mkdir("/var/tmp",0775,true);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableUfdbGuard=$EnableUfdbGuard\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: SQUIDEnable=$SQUIDEnable\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: UseRemoteUfdbguardService=$UseRemoteUfdbguardService\n";}
	

	
	$GLOBALS["SQUIDBIN"]=$squidbin;	
	if($EnableWebProxyStatsAppliance==0){
		$installed=false;
		if($users->SQUIDGUARD_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." SquidGuard is installed\n";}
		if($users->APP_UFDBGUARD_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." Webfiltering service is installed\n";}
		if($users->DANSGUARDIAN_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." Dansguardian is installed\n";}
		if(!$installed){if($GLOBALS["VERBOSE"]){echo "No one installed...\n";
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		return false;}}
		
	}
	
	
	if($EnableUfdbGuard==0){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see EnableUfdbGuard ) in line: ". __LINE__."\n";}return;}	
	if($SQUIDEnable==0){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see SQUIDEnable ) in line: ". __LINE__."\n";}return;}
	if($UseRemoteUfdbguardService==1){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see UseRemoteUfdbguardService ) in line: ". __LINE__."\n";}return;}
	
	if($GLOBALS["VERBOSE"]){echo "FIX_1_CATEGORY_CHECKED()\n";}
	FIX_1_CATEGORY_CHECKED();
	
	if($EnableRemoteStatisticsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance to get configuration file...\n";}
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		ufdbguard_remote();
		return;
	}		

	
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__."Loading compile_dansguardian()\n";}
	$dans=new compile_dansguardian();
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__."Loading compile_dansguardian::->build()\n";}
	$dans->build();
	echo "Starting......: ".date("H:i:s")." Dansguardian compile done...\n";	
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build() -> reconfigure UfdbGuardd", basename(__FILE__));}
	build_ufdbguard_config();
	ufdbguard_schedule();
	
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: ".date("H:i:s")." This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --ufdbguard");
	CheckPermissions();
	ufdbguard_admin_events("Service will be rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
	shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
	
	if(!$GLOBALS["RESTART"]){
		if(is_file("/etc/init.d/ufdb")){
			echo "Starting......: ".date("H:i:s")." Checking watchdog\n";
			ufdbguard_watchdog();
			echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service\n";
			build_ufdbguard_HUP();
		}
	}
	
	if($GLOBALS["RESTART"]){
		if(is_file("/etc/init.d/ufdb")){
			echo "Starting......: ".date("H:i:s")." Restarting\n";
			shell_exec("/etc/init.d/ufdb restart");
		}
	}
	
	if($users->DANSGUARDIAN_INSTALLED){
		echo "Starting......: ".date("H:i:s")." Dansguardian reloading service\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
	}
	

	
}
	

	
function FileMD5($path){
if(strlen(trim($GLOBALS["md5sum"]))==0){
		$unix=new unix();
		$md5sum=$unix->find_program("md5sum");
		$GLOBALS["md5sum"]=$md5sum;
}

if(strlen(trim($GLOBALS["md5sum"]))==0){return md5(@file_get_contents($path));}


exec("{$GLOBALS["md5sum"]} $path 2>&1",$res);
$data=trim(@implode(" ",$res));
if(preg_match("#^(.+?)\s+.+?#",$data,$re)){return trim($re[1]);}
	
}

function ufdbguard_watchdog_remove(){
}
function ufdbguard_watchdog(){
}

function dump_adrules($ruleid){
	
	$ufbd=new compile_ufdbguard();
	$ufbd->build_membersrule($ruleid);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",0);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt","\n");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",0777);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt",0777);
	if($GLOBALS["VERBOSE"]){echo "/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid\n";}
	exec("/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid --output 2>&1", $results);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",1);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt",@implode("\n", $results));
	
}


function CheckPermissions(){
	$unix=new unix();
	$mv=$unix->find_program("mv");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");	
	$ln=$unix->find_program("ln");
	@mkdir("/var/lib/squidguard",644,true);	
	@mkdir("/etc/ufdbguard",644,true);

	$user=GetSquidUser();
	if(!is_file("/squid/log/squid/squidGuard.log")){
		@mkdir("/squid/log/squid",0755,true);
		@file_put_contents("/squid/log/squid/squidGuard.log","#");
		shell_exec("$chown $user /squid/log/squid/squidGuard.log");
	}
	
	if(!is_dir("/var/run/dansguardian")){@mkdir("/var/run/dansguardian",0755,true);}
	if(!is_dir("/etc/dansguardian")){@mkdir("/etc/dansguardian",0755,true);}
	shell_exec("$chown squid:squid /var/run/dansguardian");
	if(is_file("/usr/sbin/ufdbguardd")){if(!is_file("/usr/bin/ufdbguardd")){$unix=new unix();$ln=$unix->find_program("ln");shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");}}
	if(!is_dir("/var/lib/ftpunivtlse1fr")){@mkdir("/var/lib/ftpunivtlse1fr",0755,true);}
	if(!is_dir("/var/lib/squidguard/checked")){@mkdir("/var/lib/squidguard/checked",0755,true);@chown("/var/lib/squidguard/checked","squid");}
	
	if(!is_file("/squid/log/squid/squidGuard.log")){
		@mkdir("/squid/log/squid",0755,true);
		@file_put_contents("/squid/log/squid/squidGuard.log","#");
		shell_exec("$chown $user /squid/log/squid/squidGuard.log");
	}

	
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){
		@mkdir("/var/log/ufdbguard",0755,true);
		@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");
	}
	if(is_file("/usr/sbin/ufdbguardd")){if(!is_file("/usr/bin/ufdbguardd")){shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");}}
	@mkdir("/etc/ufdbguard",0755,true);
	@mkdir("/var/lib/ufdbartica",0755,true);
	shell_exec("$chown $user /var/lib/squidguard");
	shell_exec("$chown $user /var/lib/ftpunivtlse1fr");
	shell_exec("$chown -R $user /var/lib/squidguard");
	shell_exec("$chown -R $user /etc/dansguardian");
	shell_exec("$chown -R $user /var/log/squid");
	shell_exec("$chown -R $user /var/log/squid/");
	shell_exec("$chown -R $user /etc/ufdbguard");
	shell_exec("$chown -R $user /etc/ufdbguard");
	shell_exec("$chown -R $user /var/lib/ftpunivtlse1fr");
	shell_exec("$chmod -R ug+x /var/lib/squidguard/");
	shell_exec("$chown -R $user /var/lib/squidguard");
	shell_exec("$chown -R $user /var/lib/ufdbartica");
	shell_exec("$chown -R $user /var/lib/ufdbartica");		
	shell_exec("$chown -R $user /var/log/squid");
	shell_exec("$chmod -R 755 /var/lib/squidguard");
	shell_exec("$chmod -R 755 /var/lib/ufdbartica");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard");
	@chown("/var/lib/squidguard/checked","squid");
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){@mkdir("/var/log/ufdbguard",0755,true);@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");}
	shell_exec("chmod 755 /var/log/ufdbguard/ufdbguardd.log");	
	@link(dirname(__FILE__)."/ressources/logs/squid-template.log", "/var/log/squid/squid-template.log");
	
}

function UFDBGUARD_COMPILE_SINGLE_DB($path){
	$timeStart=time();
	$OriginalDirename=dirname($path);
	$unix=new unix();
	$path=str_replace(".ufdb","",$path);
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.md5($path).".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){
		events_ufdb_tail("Check \"$path\"... Already process PID \"$pid\" running task has been aborted");
		return;
	}
	
	
	
	$category=null;
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){writelogs("ufdbGenTable no such binary",__FUNCTION__,__FILE__,__LINE__);return;}
	
	events_ufdb_tail("Check \"$path\"...",__LINE__);
	if(preg_match("#\/var\/lib\/squidguard\/(.+?)\/(.+?)/(.+?)$#",$path,$re)){
		$category=$re[2];
		$domain_path="/var/lib/squidguard/{$re[1]}/{$re[2]}/domains";		
	}
	if($category==null){
		if(preg_match("#\/var\/lib\/squidguard\/(.+?)\/domains#",$path,$re)){
			$category=$re[1];
			$domain_path="/var/lib/squidguard/{$re[1]}/domains";		
		}	
	}
	
	if(preg_match("#web-filter-plus\/BL\/(.+?)\/domains#",$path,$re)){
		$category=$re[1];
		$domain_path="/var/lib/squidguard/web-filter-plus/BL/$category/domains";	
	}
	
	if(preg_match("#blacklist-artica\/(.+?)\/(.+?)\/domains#",$path,$re)){
		events_ufdb_tail("find double category \"{$re[1]}-{$re[2]}\"...",__LINE__);
		$category="{$re[1]}-{$re[2]}";
		$domain_path="/var/lib/squidguard/blacklist-artica/{$re[1]}/{$re[2]}/domains";	
	}	

	if(preg_match("#blacklist-artica\/sex\/(.+?)\/domains#",$path,$re)){
		$category=$re[1];
		$domain_path="/var/lib/squidguard/blacklist-artica/sex/$category/domains";	
	}
	
	if($category==null){
		events_ufdb_tail("exec.squidguard.php:: \"$path\" cannot understand...");
	}
	
	events_ufdb_tail("exec.squidguard.php:: Found category \"$category\"",__LINE__);

	if(!is_file($path)){
		events_ufdb_tail("exec.squidguard.php:$category: \"$path\" no such file, build it",__LINE__);
		@file_put_contents($domain_path," ");
	}
	
	$category_compile=substr($category,0,15);
	if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){
					$category_compile=substr($category_compile, strlen($category_compile)-15,15);
				}
			}
		}	
	
	events_ufdb_tail("exec.squidguard.php:: category \"$category\" retranslated to \"$category_compile\"",__LINE__);
	
	
	if(is_file("$domain_path.ufdb")){
		events_ufdb_tail("exec.squidguard.php:: removing \"$domain_path.ufdb\" ...");
		@unlink("$domain_path.ufdb");
	
	}
	if(!is_file($domain_path)){
		events_ufdb_tail("exec.squidguard.php:: $domain_path no such file, create an empty one",__LINE__);
		@mkdir(dirname($domain_path),0755,true);
		@file_put_contents($domain_path,"#");
	}
	
	$urlcmd=null;
	$d=" -d $domain_path";
	if(is_file("$OriginalDirename/urls")){
		$urlssize=@filesize("$OriginalDirename/urls");
		events_ufdb_tail("exec.squidguard.php:: $OriginalDirename/urls $urlssize bytes...",__LINE__);
		if($urlssize>50){
			$urlcmd=" -u $OriginalDirename/urls";
		}
	}

	$NICE=EXEC_NICE();
	$cmd="$NICE$ufdbGenTable -n -D -W -t $category_compile$d$urlcmd 2>&1";
	events_ufdb_tail("exec.squidguard.php:$category:$cmd");
	$time=time();
	exec($cmd,$results);
	exec($cmd,$results);
	while (list ($a, $b) = each ($results)){
		if(strpos($b,"is not added because it was already matched")){continue;}
		if(strpos($b,"has optimised subdomains")){continue;}
		events_ufdb_tail("exec.squidguard.php:$category:$b");
	}
	$tookrecompile=$unix->distanceOfTimeInWords($time,time());
	events_ufdb_tail("exec.squidguard.php:$category_compile: execution $tookrecompile",__LINE__);
	
	events_ufdb_tail("exec.squidguard.php:$category:done..");
	
	$user=GetSquidUser();
	$chown=$unix->find_program("chown");
	if(is_file($chown)){
		events_ufdb_tail("exec.squidguard.php:$category:$chown -R $user $OriginalDirename");
		shell_exec("$chown -R $user $OriginalDirename/*");
		shell_exec("$chown -R $user /var/log/squid/*");
	}
	$sock=new sockets();
	$took=$unix->distanceOfTimeInWords($timeStart,time());
	$sock->TOP_NOTIFY("$OriginalDirename webfiltering database ($category) was recompiled took $took hard compilation took: $tookrecompile","info");
	
}
	

function databasesStatus(){
	$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
	$count=0;
	$f=array();
	while (list ($a, $b) = each ($datas)){
		
		if(preg_match("#domainlist.+?(.+)#",$b,$re)){
			$f[]["domainlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
			
		}
		
		if(preg_match("#expressionlist.+?(.+)#",$b,$re)){
			$f[]["expressionlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		if(preg_match("#urllist.+?(.+)#",$b,$re)){
			$f[]["urllist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		
	}
	

	
	while (list ($a, $b) = each ($f)){

		$domainlist=$b["domainlist"]["path"];
		$expressionlist=$b["expressionlist"]["path"];
		$urllist=$b["urllist"]["path"];
		
		if(is_file($domainlist)){
			$key="domainlist";
			$path=$domainlist;
		}
		
		if(is_file($expressionlist)){
			$key="expressionlist";
			$path=$expressionlist;
		}

		if(is_file($urllist)){
			$key="urllist";
			$path=$urllist;
		}			
		
		$d=explode("\n",@file_get_contents($path));
		$i[$path]["type"]=$key;
		$i[$path]["size"]=@filesize("$domainlist.db");
		$i[$path]["linesn"]=count($d);
		$i[$path]["date"]=filemtime($path);
		
		
		
		
	}
	
	return $i;
	
}

function status(){
	
	
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$conf[]="[APP_SQUIDGUARD]";
	$conf[]="service_name=APP_SQUIDGUARD";
	
	
	if(is_array($array)){
		$conf[]="running=0";
		$conf[]="why={waiting_database_compilation}<br>{databases}:&nbsp;".count($array);
		return implode("\n",$conf);
		
	}
	
	
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	exec("$pidof $users->SQUIDGUARD_BIN_PATH",$res);
	$array=explode(" ",implode(" ",$res));
	while (list ($index, $line) = each ($array)){
		if(preg_match("#([0-9]+)#",$line,$ri)){
			$pid=$ri[1];
			$inistance=$inistance+1;
			$mem=$mem+$unix->MEMORY_OF($pid);
			$ppid=$unix->PPID_OF($pid);
		}
	}
	$conf[]="running=1";
	$conf[]="master_memory=$mem";
	$conf[]="master_pid=$ppid";
	$conf[]="other={processes}:$inistance"; 
	return implode("\n",$conf);
	
}

function CompileSingleDB($db_path){
	$user=GetSquidUser();
	$users=new usersMenus();
	$unix=new unix();
	if(strpos($db_path,".db")>0){$db_path=str_replace(".db","",$db_path);}
	$verb=" -d";
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	exec($users->SQUIDGUARD_BIN_PATH." $verb -C $db_path",$repair);	
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard/*");	
	
	$db_recover=$unix->LOCATE_DB_RECOVER();
	shell_exec("$db_recover -h ".dirname($db_path));
	build();
	KillSquidGuardInstances();	
	send_email_events("squidGuard: $db_path repair","the database $db_path was repair by artica\n",@implode("\n",$repair),"squid");
	
}

function KillSquidGuardInstances(){
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	if(strlen($pidof)>3){
		exec("$pidof $users->SQUIDGUARD_BIN_PATH 2>&1",$results);
		$pids=trim(@implode(" ",$results));
		if(strlen($pids)>3){
			echo "Starting......: ".date("H:i:s")." squidGuard kill $pids PIDs\n";
			shell_exec("/bin/kill $pids");
		}
		
	}	
	
}


function compile_databases(){
	$users=new usersMenus();
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$verb=" -d";
	
	
		$array=$squid->SquidGuardDatabasesStatus(0);

	
	if( count($array)>0){
		while (list ($index, $file) = each ($array)){
			echo "Starting......: ".date("H:i:s")." squidGuard compiling ". count($array)." databases\n";
			$file=str_replace(".db",'',$file);
			$textfile=str_replace("/var/lib/squidguard/","",$file);
			echo "Starting......: ".date("H:i:s")." squidGuard compiling $textfile database ".($index+1) ."/". count($array)."\n";
			if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C $file\n";}
			system($users->SQUIDGUARD_BIN_PATH." -P$verb -C $file");
		}
	}else{
		echo "Starting......: ".date("H:i:s")." squidGuard compiling all databases\n";
		if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C all\n";}
		system($users->SQUIDGUARD_BIN_PATH." -P$verb -C all");
	}

	
		
	$user=GetSquidUser();
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");		
 	system(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.squid.php --build");
	build();
	KillSquidGuardInstances();
	
	
	
 
 
}

function  parseTemplate_extension($uri){
	
	$js_forced["revsci.net"]=true;
	$js_forced["omtrdc.net"]=true;
	
	$array=parse_url($uri);
	$hostname=$array["host"];

	$fam=new squid_familysite();
	$hostname=$fam->GetFamilySites($hostname);
	
	if(count($array)==0){return false;}
	if(!isset($array["path"])){return false;}
	$path_parts = pathinfo($array["path"]);
	$ext=$path_parts['extension'];
	if(preg_match("#(.+?)\?#", $ext,$re)){$ext=$re[1];}
	if($ext=="php"){return false;}
	if($ext=="html"){return false;}
	$basename=$path_parts['basename'];
	$filename=$path_parts['basename'];
	
	if(preg_match("#\/pixel\?#", $uri)){
		parseTemplate_extension_gif();
		return true;
	}
	
	
	
	if(isset($js_forced[$hostname])){$ext="js";}
	
	
	
	if($filename==null){$filename="1x1.$ext";}
	$ctype=null;
    switch ($ext) {
      
      case "gif": parseTemplate_extension_gif($filename);return true;
      case "png": $ctype="image/png"; break;
      case "jpeg": $ctype="image/jpg";break;
      case "jpg": $ctype="image/jpg";;break;
      case "js": $ctype="application/x-javascript";;break;
      case "css": $ctype="text/css";;break;
	}
	
	//aspx
	

	
	if($ext=="js"){
		header("content-type: application/x-javascript");echo "// blocked by url filtering\n";
		return true;
	}
	if($ext=="css"){
		header("content-type: text/css");echo "\n";
		echo "/**\n";
		echo "* blocked by url filtering\n";
		echo "* \n";
		echo "*/\n";
		return true;
	}
	if($ext=="ico"){
		
		$fsize = filesize("ressources/templates/Squid/favicon.ico");
		header("content-type: image/vnd.microsoft.icon");
		header("Content-Length: ".$fsize);
		ob_clean();
		flush();
		readfile( $fsize );
		return true;
	}
	
	

    if($ctype<>null){
    	if(!is_file("img/$filename")){$filename=null;}
    	if($filename==null){$filename="1x1.$ext";}
    	$fsize = filesize("img/$filename"); 
    	header("Content-Type: $ctype");
    	header("Content-Length: ".$fsize);
    	ob_clean();
    	flush();
    	readfile( $fsize );     	
    	return true;
    }

    writelogs("$uri: $ext ($filename) Unkown",__FUNCTION__,__FILE__,__LINE__);
    

		
}
function parseTemplate_extension_gif($filename){
		$fsize = filesize("img/1x1.gif"); 
    	header("Content-Type: image/gif");
    	header("Content-Length: ".$fsize);
    	ob_clean();
    	flush();
    	readfile( "img/1x1.gif" );     	
    	}
    	
function parseTemplateLogs($text=null,$function,$file,$line){
	if(!$GLOBALS["WRITELOGS"]){return;}
	$time=date('m-d H:i:s');

	if($GLOBALS["VERBOSE"]){echo "[$time]:$function:$text in line $line<br>\n";}
	$logFile=dirname(__FILE__)."/ressources/logs/squid-template.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
   
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "[$time]:$function:$text in line $line\n");
	@fclose($f);
} 

function parseTemplateForcejs($uri){
	if(preg_match("#ad\.doubleclick\.net\/adj\/#", $uri)){return true;}
	
}

function parseTemplate_LocalDB_receive(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.ldap.inc");		
	include_once(dirname(__FILE__)."/ressources/class.user.inc");	
	
	$user=new user($_POST["USERNAME"]);
	
	
	
	if($_POST["password"]<>md5($user->password)){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}: {wrong_password}");
		die();
	}
	
	$privs=new privileges($user->uid);
	$privileges_array=$privs->privs;	
	if($privileges_array["AllowDansGuardianBanned"]<>"yes"){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}: {ERROR_NO_PRIVS}");
		return;
	}
	
	
	//AllowDansGuardianBanned

	$Whitehost=$_POST["Whitehost"];
	$CLIENT=$_POST["CLIENT"];
	$MEMBER=$_POST["USERNAME"];
	$md5=md5("$CLIENT$Whitehost$MEMBER");
	$sql="INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) 
	VALUES ('$md5','$CLIENT','$Whitehost','$MEMBER')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL($sql);}
	}
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{success_restart_query_in_few_seconds}");
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");		
	
	
}



function parseTemplate_LocalDB(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$GLOBALS["JS_NO_CACHE"]=true;
	$proto="http";
	
	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";}}
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";
	$t=time();
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$page=CurrentPageName();
	$tpl=new templates();	
	$ask_password=$tpl->javascript_parse_text("{password}:");
	$url=base64_decode($_GET["url"]);
	$clientaddr=base64_decode($_GET["clientaddr"]);
	$array=parse_url($url);
	$Whitehost=strtolower($array["host"]);	
	$pp=new pagebuilder();
	$head=$pp->jsArtica()."\n\n".$pp->headcss();
	if(preg_match("#^www.(.+)#", $Whitehost,$re)){$Whitehost=$re[1];}
	
	$yahoo=$pp->YahooBody();
	
	$t=time();	
	$html="
	<html>
	<head>
		<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
		<title>$ask_password</title>
		<meta http-equiv=\"X-UA-Compatible\" content=\"IE=EmulateIE7\" />
		<link href='/css/styles_main.css'    rel=\"styleSheet\"  type='text/css' />
		<link href='/css/styles_header.css'  rel=\"styleSheet\"  type='text/css' />
		<link href='/css/styles_middle.css'  rel=\"styleSheet\"  type='text/css' />
		<link href='/css/styles_tables.css'  rel=\"styleSheet\"  type='text/css' />
		<link href=\"/css/styles_rounded.css\" rel=\"stylesheet\"  type=\"text/css\" />		
		$head
	</head>
	<body style='background: url(\"/css/images/pattern.png\") repeat scroll 0pt 0pt rgb(38, 56, 73); padding: 0px;padding-top: 15px; margin: 0px; border: 0px solid black; width: 100%; cursor: default; -moz-user-select: inherit;'>
	$yahoo
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{client}:</td>
		<td style='font-size:16px'><strong>$clientaddr</strong></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{website}:</td>
		<td style='font-size:16px'><strong>$Whitehost</strong></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td style='font-size:16px'>". Field_text("USERNAME-$t",null,"font-size:16px;font-weight:bolder",null,null,null,false,"SendPassCheck(event)")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td style='font-size:16px'>". Field_password("#nolock:PASS-$t",null,"font-size:16px",null,null,null,false,"SendPassCheck(event)")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{submit}","SendPass$t()",18)."</td>
	</tr>
	</table>
	
	<script>
	var X_SendPass= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('div-$t').innerHTML='';
		}

	function SendPassCheck(e){
		if(checkEnter(e)){SendPass$t();}
	}
		
	function SendPass$t(){
		var password=MD5(document.getElementById('PASS-$t').value);
		var XHR = new XHRConnection();
		XHR.appendData('password',password);
		XHR.appendData('USERNAME',document.getElementById('USERNAME-$t').value);
		XHR.appendData('CLIENT','$clientaddr');
		XHR.appendData('Whitehost','$Whitehost');
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',X_SendPass);     		
	}		
	MessagesTophideAllMessages();
	</script>
	</body>
	</html>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function parseTemplate_SinglePassWord(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$proto="http";
	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";}}
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";
	$GLOBALS["JS_NO_CACHE"]=true;
	
	$t=time();
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$page=CurrentPageName();
	$tpl=new templates();	
	$ask_password=$tpl->javascript_parse_text("{password}:");
	$url=base64_decode($_GET["url"]);
	$clientaddr=base64_decode($_GET["clientaddr"]);
	$array=parse_url($url);
	$Whitehost=strtolower($array["host"]);	
	$pp=new pagebuilder();
	$head=$pp->jsArtica()."\n\n".$pp->headcss();
	if(preg_match("#^www.(.+)#", $Whitehost,$re)){$Whitehost=$re[1];}
	
	$yahoo=$pp->YahooBody();
	
	$t=time();	
	$unlock=$tpl->_ENGINE_parse_body("{unlock}");
	$title="$unlock &laquo;$Whitehost&raquo;";
	
	$html="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html lang=\"en\">
  <head>
    <meta charset=\"utf-8\">
    <title>$ask_password</title>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <meta name=\"description\" content=\"\">
    <meta name=\"author\" content=\"\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap.css\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/bootstrap-responsive.css\">

	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/default.js\"></script>    
  	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
  	<script type=\"text/javascript\" language=\"javascript\" src=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
  	<script type='text/javascript' language='javascript' src='{$GLOBALS["JS_HEAD_PREPREND"]}/js/jquery.uilock.min.js'></script>
  	<script type='text/javascript' language='javascript' src='{$GLOBALS["JS_HEAD_PREPREND"]}/js/jquery.blockUI.js'></script>      
   <style type=\"text/css\">
     body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      .form-signin {
        max-width: 300px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type=\"text\"],
      .form-signin input[type=\"password\"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
    </style>    
    <!--[if IE]>
		<link rel=\"stylesheet\" type=\"text/css\" href=\"{$GLOBALS["JS_HEAD_PREPREND"]}/bootstrap/css/ie-only.css\" />
	<![endif]-->    
</head>
<body>
<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"{$GLOBALS["JS_HEAD_PREPREND"]}/ressources/templates/endusers/ajax-loader-eu.gif\">
    

      <div class=\"form-signin\">
       <div id='div-$t'></div>
        <h2 class=\"form-signin-heading\">$title</h2>
        <input type=\"password\" class=\"input-block-level\" placeholder=\"Password\" id=\"PASS-$t\">
        <button class=\"btn btn-large btn-primary\" type=\"button\" id=\"signin\">$unlock</button>
      </div>

    
 
 <script type=\"text/javascript\">
 
 $('#signin').on('click', function (e) {
	 //if(!checkEnter(e)){return;}
		SendPass$t();

});
 
 
 $('.input-block-level').keypress(function (e) {
	
	 if (e.which == 13) {
		 SendPass$t();
	 }

});


	var xSendPass$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('div-$t').innerHTML='';
		}
		
	function SendPass$t(){
		var password=MD5(document.getElementById('PASS-$t').value);
		var XHR = new XHRConnection();
		XHR.appendData('password',password);
		XHR.appendData('CLIENT','$clientaddr');
		XHR.appendData('Whitehost','$Whitehost');
		AnimateDiv('div-$t');
		XHR.sendAndLoad('{$GLOBALS["JS_HEAD_PREPREND"]}/$page', 'POST',xSendPass$t);     		
	}	

 
 </script>

</body>
</html>";
	echo $html;return;

}

function parseTemplate_categoryname($category=null,$license=0,$nosuffix=0){
		
		$CATEGORY_PLUS_TXT=null;
		parseTemplateLogs("parseTemplate_categoryname($category,$license)",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		$SquidGuardApacheShowGroupNameTXT=null;
		
		
		
		if($license==1){
			$SquidGuardApacheShowGroupName=$sock->GET_INFO("SquidGuardApacheShowGroupName");
			if(!is_numeric($SquidGuardApacheShowGroupName)){$SquidGuardApacheShowGroupName=0;}
			if($SquidGuardApacheShowGroupName==1){
				$SquidGuardApacheShowGroupNameTXT=$sock->GET_INFO("SquidGuardApacheShowGroupNameTXT");
				if($SquidGuardApacheShowGroupNameTXT==null){
					$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
					
					if($LicenseInfos["COMPANY"]==null){
						$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
						$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
					}
					$SquidGuardApacheShowGroupNameTXT=$LicenseInfos["COMPANY"];
				
			}
		}
		
	
		$category=strtolower(trim($category));
		
		include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
		
		
		if(preg_match("#^art(.+)#", $category,$re)){
			parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
			$category=CategoryCodeToCatName($category);
			$CATEGORY_PLUS_TXT="Artica Database";
			$users=new usersMenus();
			if($users->WEBSECURIZE){$CATEGORY_PLUS_TXT="Web Securize Database";}
			if($users->LANWANSAT){$CATEGORY_PLUS_TXT="LanWanSAT Database";}
			if($users->BAMSIGHT){$CATEGORY_PLUS_TXT="BamSight Database";}
			
			
		}
		
		if(preg_match("#^tls(.+)#", $category,$re)){
			parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
			$category=CategoryCodeToCatName($category);
			$CATEGORY_PLUS_TXT="Toulouse University Database";			
		}
		parseTemplateLogs("Parsing: `$category` - $CATEGORY_PLUS_TXT nosuffix=$nosuffix",__FUNCTION__,__FILE__,__LINE__);
		if($nosuffix==1){return $category;}
		
		if($SquidGuardApacheShowGroupNameTXT<>null){$CATEGORY_PLUS_TXT=$SquidGuardApacheShowGroupNameTXT;}
		if($CATEGORY_PLUS_TXT<>null){
			return $category." (".$CATEGORY_PLUS_TXT.")";
		}
		return $category;
	}
	
function hostfrom_url($url){
	$URL_ARRAY=parse_url($url);
	if(!isset($URL_ARRAY["host"])){return null;}
	$src_hostname=$URL_ARRAY["host"];
	if(preg_match("#^www.(.+)#", $src_hostname,$re)){$src_hostname=$re[1];}
	if(preg_match("#^(.+?):[0-9]+#", $src_hostname,$re)){$src_hostname=$re[1];}
	return $src_hostname;
}


function CacheManager_default(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
	$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
	return $LicenseInfos["EMAIL"];
}

function CacheManager(){
	$sock=new sockets();
	$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
	if($cache_mgr_user<>null){return $cache_mgr_user;}
	return CacheManager_default();
}


function parseadmin($emailTemplate,$subj){
	
	$CacheManager=CacheManager();
	$subject=rawurlencode("Web Filtering complain [$subj]");
	$emailTemplate=rawurlencode($emailTemplate);
	return "<a href=\"mailto:$CacheManager?subject=$subject&body=$emailTemplate\">$CacheManager</a>";
	
}

function parseTemplate_file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
		
	$data1 = filemtime($path);
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}


function parseTemplate_events($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/artica-webpage-error.log";
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');

	@fwrite($f, "$date:$text  - $line\n");
	@fclose($f);
}

function parseTemplate_string_to_url($url){
	$url=str_replace("%3A", ":", $url);
	$url=str_replace("%2F", "/", $url);
	$url=str_replace("%3D","=",$url);
	$url=str_replace("%3F","?",$url);
	$url=str_replace("%20"," ",$url);
	$url=str_replace("%25",'%',$url);
	$url=str_replace("%40","@",$url);
	return $url;
}

function parseTemplate(){
	
	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	include_once(dirname(__FILE__)."/ressources/class.ufdb.microsoft.inc");
	$CATEGORY_SOURCE=null;
	$proto="http";
	$url=$_GET["url"];
	$cacheid=null;
	$HTTP_X_FORWARDED_FOR=null;
	$HTTP_X_REAL_IP=null;
	if(isset($_GET["category"])){$CATEGORY_SOURCE=$_GET["category"];}
	$AS_SSL=false;
	$DisableSquidGuardHTTPCache=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableSquidGuardHTTPCache"));
	
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": DisableSquidGuardHTTPCache: $DisableSquidGuardHTTPCache</div>\n";}
	

	
	$HTTP_REFERER=null;
	if(isset($_GET["targetgroup"])){
		$TARGET_GROUP_SOURCE=$_GET["targetgroup"];
		if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE=$TARGET_GROUP_SOURCE;}
	}
	$clientgroup=$_GET["clientgroup"];
	$QUERY_STRING=$_SERVER["QUERY_STRING"];
	if(isset($_SERVER["HTTP_REFERER"])){$HTTP_REFERER=$_SERVER["HTTP_REFERER"];}
	$HTTP_REFERER_HOST=hostfrom_url($HTTP_REFERER);
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];}
	
	$URL_HOST=hostfrom_url($url);
	if(isset($_GET["rule-id"])){$ID=$_GET["rule-id"];}
	if(isset($_GET["fatalerror"])){$ID=0;$cacheid="fatalerror";}
	if(isset($_GET["loading-database"])){$ID=0;$cacheid="loading-database";}
	if (isset($_SERVER['HTTPS'])){if (strtolower($_SERVER['HTTPS']) == 'on'){$proto="https";$AS_SSL=true;}}
	$time=date("Ymdh");
	
	if($AS_SSL){
		if(!isset($_GET["SquidGuardIPWeb"])){
			$requested_uri="https://".$_SERVER["SERVER_NAME"]."/".$_SERVER["REQUEST_URI"];
			$arrayURI=parse_url($requested_uri);
			$requested_hostname=$arrayURI["host"];
		}
	}
	
	
	if(preg_match("#&url=(.*?)(&|$)#", $QUERY_STRING,$re)){
		$requested_uri=parseTemplate_string_to_url($re[1]);
		$arrayURI=parse_url($requested_uri);
		$requested_hostname=$arrayURI["host"];
	}
	
	$GLOBALS["BLOCK_KEY_CACHE"]=md5("$HTTP_X_FORWARDED_FOR$HTTP_X_REAL_IP$time$proto$proto$TARGET_GROUP_SOURCE$clientgroup$requested_hostname$HTTP_REFERER_HOST$URL_HOST$ID$cacheid");
	if($GLOBALS["VERBOSE"]){$DisableSquidGuardHTTPCache=1;}
	
	if($DisableSquidGuardHTTPCache==0){
		if(is_file("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}")){
			if(parseTemplate_file_time_min("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}")<10){
				echo @file_get_contents("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}");
				return;
			}
		}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": TARGET_GROUP_SOURCE $TARGET_GROUP_SOURCE / $requested_hostname</div>\n";}
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": CATEGORY_SOURCE $CATEGORY_SOURCE / $requested_hostname</div>\n";}
	
	
	if($TARGET_GROUP_SOURCE=="none"){
		$TARGET_GROUP_SOURCE="{ufdb_none}";
		$EnableSquidGuardSearchCategoryNone=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidGuardSearchCategoryNone"));
		
		if($CATEGORY_SOURCE==null){
			$EnableSquidGuardSearchCategoryNone=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidGuardSearchCategoryNone"));
			if($EnableSquidGuardSearchCategoryNone==1){
				include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
				$catz=new mysql_catz();
				$CATEGORY_SOURCE=$catz->GET_CATEGORIES($requested_hostname);
				if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE="{unknown}";}
			}
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": TARGET_GROUP_SOURCE $TARGET_GROUP_SOURCE / $requested_hostname</div>\n";}
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white;font-size:22px;color:black'>".__LINE__.": CATEGORY_SOURCE $CATEGORY_SOURCE / $requested_hostname</div>\n";}
	
	
	
	

	session_start();
	$HTTP_REFERER=null;
	$template_default_file=dirname(__FILE__)."/ressources/databases/dansguard-template.html";
	
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$users=new usersMenus();
	//$q=new mysql_squid_builder();
	$UfdbGuardRedirectCategories=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
	$SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$SquidGuardWebUseLocalDatabase=$sock->GET_INFO("SquidGuardWebUseLocalDatabase");
	$SquidGuardWebBlankReferer=intval($sock->GET_INFO("SquidGuardWebBlankReferer"));
	
	if(!is_numeric($SquidGuardWebFollowExtensions)){$SquidGuardWebFollowExtensions=1;}
	if(!is_numeric($SquidGuardWebUseLocalDatabase)){$SquidGuardWebUseLocalDatabase=0;}
	
	
	
	
	if($SquidGuardWebBlankReferer==1){
		if($URL_HOST<>$HTTP_REFERER_HOST){
			$data="<html><head></head><body></body></html>";
			header("Content-Length: ".strlen($data));
			header("Content-Type: text/html");
			echo $data;
			die();
		}
	}
	
	$GLOBALS["JS_NO_CACHE"]=true;
	$GLOBALS["JS_HEAD_PREPREND"]="$proto://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}";
	
	if($SquidGuardWebFollowExtensions==1){
		if(parseTemplate_extension($_GET["url"])){return;}
	}
	
	if(parseTemplateForcejs($_GET["url"])){
		parseTemplateLogs("JS detected : For {$_GET["url"]}",__FUNCTION__,__FILE__,__LINE__);
		header("content-type: application/x-javascript");
		echo "// blocked by url filtering\n";
    	return true;
		return;
	}
	
	$defaultjs="alert('Disabled')";
	$ADD_JS_PACK=false;
	
	

	
	if($SquidGuardWebUseLocalDatabase==1){
		$clientaddr=base64_encode($_GET["clientaddr"]);
		$defaultjs="s_PopUp('{$GLOBALS["JS_HEAD_PREPREND"]}/". basename(__FILE__)."?SquidGuardWebUseLocalDatabase=1&url=".base64_encode("{$_GET["url"]}")."&clientaddr=$clientaddr',640,350)";
		$ADD_JS_PACK=true;
	}
	
	if($users->CORP_LICENSE){$LICENSE=1;$FOOTER=null;}
	if(!$users->CORP_LICENSE){$LICENSE=0;}
	parseTemplateLogs("{$_GET["clientaddr"]}: Category=`$CATEGORY_SOURCE` targetgroup=`{$_GET["targetgroup"]}` LICENSE:$LICENSE",__FUNCTION__,__FILE__,__LINE__);
	$CATEGORY_KEY=null;
	$_GET["targetgroup"]=parseTemplate_categoryname($TARGET_GROUP_SOURCE,$LICENSE);
	$_GET["clientgroup"]=parseTemplate_categoryname($_GET["clientgroup"],$LICENSE);
	$_GET["category"]=parseTemplate_categoryname($CATEGORY_SOURCE,$LICENSE);
	$CATEGORY_KEY=parseTemplate_categoryname($CATEGORY_SOURCE,$LICENSE,1);
	if($CATEGORY_KEY==null){
		$CATEGORY_KEY=parseTemplate_categoryname($TARGET_GROUP_SOURCE,$LICENSE,1);
	}
	
	

	$_CATEGORIES_K=$_GET["category"];
	
	
	
	
	$_RULE_K=$_GET["clientgroup"];
	if($_CATEGORIES_K==null){$_CATEGORIES_K=$_GET["targetgroup"];}
	
	
	

	if($_RULE_K==null){$_RULE_K="{web_filtering}";}
	$REASONGIVEN="{web_filtering} $_CATEGORIES_K";
	
	if($_CATEGORIES_K=="restricted_time"){$REASONGIVEN="{restricted_access}";}
	
	parseTemplateLogs("{$REASONGIVEN}: _CATEGORIES_K=`$_CATEGORIES_K` _RULE_K=$_RULE_K` LICENSE:$LICENSE",__FUNCTION__,__FILE__,__LINE__);
	$IpToUid=null;
	//$IpToUid=$q->IpToUid($_GET["clientaddr"]);
	if($IpToUid<>null){$IpToUid="&nbsp;($IpToUid)";}
	
		if($LICENSE==1){
		if($CATEGORY_KEY<>null){
			$RedirectCategory=$UfdbGuardRedirectCategories[$CATEGORY_KEY];
			
			if($RedirectCategory["enable"]==1){
				if($RedirectCategory["blank_page"]==1){
					parseTemplateLogs("[$CATEGORY_KEY]: blank_page : For {$_GET["url"]}",__FUNCTION__,__FILE__,__LINE__);
					header("HTTP/1.1 200 OK");
					die();
					return;
				}
				if(trim($RedirectCategory["template_data"])<>null){
					header('Content-Type: text/html; charset=iso-8859-1');
					$TemplateErrorFinal=$RedirectCategory["template_data"];
					return;
				}
			}
		}
	}
		
	$EnableSquidFilterWhiteListing=$sock->GET_INFO("EnableSquidFilterWhiteListing");


	if($LICENSE==1){
		if(is_numeric($ID)){
			if($ID==0){
				$ligne["groupname"]="Default";
			}else{
				$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
				$q=new mysql_squid_builder();
				$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
				$ruleName=$ligne["groupname"];
			}	
			
			
			
		}else{
			writelogs("ID: not a numeric",__FUNCTION__,__FILE__,__LINE__);
		}
	}

		
	
	if(isset($_GET["fatalerror"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$REASONGIVEN="{webfiltering_issue}";
		$_CATEGORIES_K="{system_Webfiltering_error}";
		$_RULE_K="{service_error}";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];
	}
	
	if(isset($_GET["loading-database"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$REASONGIVEN="{Webfiltering_maintenance}";
		$_CATEGORIES_K="{please_wait_reloading_databases}";
		$_RULE_K="{waiting_service}....";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];		
		
	}
	
	if(!isset($_SESSION["IPRES"][$_GET["clientaddr"]])){$_SESSION["IPRES"][$_GET["clientaddr"]]=gethostbyaddr($_GET["clientaddr"]);}
	if(isset($_GET["source"])){$_GET["clientaddr"]=$_GET["source"];}
	if(isset($_GET["user"])){$_GET["clientname"]=$_GET["user"];}
	if(isset($_GET["virus"])){$_GET["targetgroup"]=$_GET["virus"];$ruleName=null;}
	if($_GET["clientuser"]<>null){$_GET["clientname"]=$_GET["clientuser"];}
	$ruleName=parseTemplate_categoryname($ruleName,$LICENSE);

	$ARRAY["URL"]=$_GET["url"];
	$ARRAY["IPADDR"]=$_GET["clientaddr"];
	$ARRAY["REASONGIVEN"]=$REASONGIVEN;
	$ARRAY["CATEGORY_KEY"]=$CATEGORY_KEY;
	$ARRAY["RULE_ID"]=$ID;
	
	$ARRAY["CATEGORY"]=$_CATEGORIES_K;
	$ARRAY["RULE"]=$_RULE_K;
	if($ruleName<>null){
		$ARRAY["RULE"]=$ruleName;
	}
	$ARRAY["targetgroup"]=$_GET["targetgroup"];
	$ARRAY["IpToUid"]=$IpToUid;
	$ARRAY["clientname"]=$_GET["clientname"];
	$ARRAY["HOST"]=$_SESSION["IPRES"][$_GET["clientaddr"]];
	
	
	$GLOBALS["BLOCK_KEY_CACHE"];
	$Content=parseTemplate_build_main($ARRAY);
	@file_put_contents("/home/squid/error_page_cache/{$GLOBALS["BLOCK_KEY_CACHE"]}", $Content);
	echo $Content;
	
}

function GetSquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: ".date("H:i:s")." squidGuard unable to get squid configuration file\n";
		return "squid:squid";
	}
	
	$array=explode("\n",@file_get_contents($squidconf));
	while (list ($index, $line) = each ($array)){
		if(preg_match("#cache_effective_user\s+(.+)#",$line,$re)){
			$user=trim($re[1]);
		}
		if(preg_match("#cache_effective_group\s+(.+)#",$line,$re)){
			$group=trim($re[1]);
		}
	}
	
	
	if($group==null){$group="squid";}	
	return "$user:$group";
	
	
	
}

function ParseDirectory($path){
	if(!is_dir($path)){echo "$path No such directory\n";return;}
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "No uuid\n";return;}	
	$handle=opendir($path);
	$q=new mysql_squid_builder();
	$f=false;
	while (false !== ($dir = readdir($handle))) {
		if($dir=="."){continue;}
		if($dir==".."){continue;}	
		if(!is_file("$path/$dir/domains")){echo "$path/$dir/domains no such file\n";continue;}
		$category=sourceCategoryToArticaCategory($dir);
		if($category==null){echo "$path/$dir/domains no such category\n";continue;}
		$table="category_".$q->category_transform_name($category);
		if(!$q->TABLE_EXISTS($table)){echo "$category -> no such table $table\n";continue;}
		inject($category,$table,"$path/$dir/domains");
		
		
	}
	
	
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $www) = each ($tables)){
		$sql="SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$prefix="INSERT IGNORE INTO categorize (zmd5 ,pattern,zDate,uuid,category) VALUES";
		if($ligne["tcount"]>0){
			echo "$table {$ligne["tcount"]} items to export\n";
			$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 and enabled=1");
			while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
				$f[]="('{$ligne2["zmd5"]}','{$ligne2["pattern"]}','{$ligne2["zDate"]}','$uuid','{$ligne2["category"]}')";
				$c++;
				if(count($f)>3000){
					$q->QUERY_SQL($prefix.@implode(",",$f));
					if(!$q->ok){echo $q->mysql_error."\n";return;}
					$f=array();
				}
				
			}
		$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0");
		}
		
	}
	
if(count($f)>0){
	$q->QUERY_SQL($prefix.@implode(",",$f));
	$f=array();	
}	
	
	
	
}

function sourceCategoryToArticaCategory($category){
	$array["gambling"]="gamble";
	$array["gamble"]="gamble";
	$array["hacking"]="hacking";
	$array["malware"]="malware";
	$array["phishing"]="phishing";
	$array["porn"]="porn";
	$array["sect"]="sect";
	$array["socialnetwork"]="socialnet";
	$array["violence"]="violence";
	$array["adult"]="porn";
	$array["ads"]="publicite";
	$array["warez"]="warez";
	$array["drugs"]="drogue";
	$array["forums"]="forums";
	$array["filehosting"]="filehosting";
	$array["games"]="games";
	$array["astrology"]="astrology";
	$array["publicite"]="publicite";
	$array["radio"]="webradio";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";
	$array["press"]="news";
	$array["audio-video"]="audio-video";
	$array["webmail"]="webmail";
	$array["chat"]="chat";
	$array["social_networks"]="socialnet";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	$array["audio-video"]="audio-video";
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	$array["audio-video"]="audio-video";
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";	
	$array["adv"]="publicite";
	$array["aggressive"]="aggressive";
	$array["automobile"]="automobile/cars";
	$array["chat"]="chat";
	$array["dating"]="dating";
	$array["downloads"]="downloads";
	$array["drugs"]="drugs";
	$array["education"]="recreation/schools";
	$array["finance"]="financial";
	$array["forum"]="forums";
	$array["gamble"]="gamble";
	$array["government"]="governments";
	$array["hacking"]="hacking";
	$array["hospitals"]="hospitals";
	$array["imagehosting"]="imagehosting";
	$array["isp"]="isp";
	$array["jobsearch"]="jobsearch";
	$array["library"]="books";
	$array["models"]="models";
	$array["movies"]="movies";
	$array["music"]="music";
	$array["news"]="news";
	$array["porn"]="porn";
	$array["redirector"]="redirector";
	$array["religion"]="religion";
	$array["remotecontrol"]="remote-control";
	
	$array["searchengines"]="searchengines";
	$array["shopping"]="shopping";
	$array["socialnet"]="socialnet";
	$array["spyware"]="spyware";
	$array["tracker"]="tracker";
	$array["updatesites"]="updatesites";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["weapons"]="weapons";
	$array["webmail"]="webmail";
	$array["webphone"]="webphone";
	$array["webradio"]="webradio";
	$array["webtv"]="webtv";		
	if(!isset($array[$category])){return null;}
	return $array[$category];
	
	
}
// exec.squidguard.php --inject porn /root/blablabl/domains
function inject($category,$table=null,$file=null){
	include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
	$unix=new unix();
	$q=new mysql_squid_builder();
	
	
	
	if(is_file($category)){
		$file=$category;
		$category_name=basename($file);
		echo "$file -> $category_name\n";
		if(preg_match("#(.+?)\.gz$#", $category_name)){
			echo "$category_name -> gunzip\n";
			$new_category_name=str_replace(".gz", "", $category_name);
			$gunzip=$unix->find_program("gunzip");
			$target_file=dirname($file)."/$new_category_name";
			$cmd="/bin/gunzip -d -c \"$file\" >$target_file 2>&1";
			echo "$cmd\n";
			shell_exec($cmd);
			if(!is_file($target_file)){echo "Uncompress failed\n";return;}
			$file=$target_file;
			$table=$new_category_name;
			$category=$q->tablename_tocat($table);
			echo "$new_category_name -> $table\n";
			
			
			
		}else{
			$table=$category_name;
			echo "$new_category_name -> $table\n";
			$category=$q->tablename_tocat($table);
		}
		
		echo "Table: $table\nSource File:$file\nCategory: $category\n";
		
		
	}
	
	
	if(!is_file($file)){
		if(!is_file($table)){echo "`$table` No such file\n";}
		if(is_file($table)){$file=$table;$table=null;}
	}
	
	
	if($table==null){
		$table="category_".$q->category_transform_name($category);
		echo "Table will be $table\n";
	}
	
	if(!$q->TABLE_EXISTS($table)){
		echo "$table does not exists, check if it is an official one\n";
		$dans=new dansguardian_rules();
		if(isset($dans->array_blacksites[$category])){
			$q->CreateCategoryTable($category);
		}
		
	}
	if(!$q->TABLE_EXISTS($table)){	
		echo "`$category` -> no such table \"$table\"\n";return;
	}
	
	
	$sql="SELECT COUNT(*) AS TCOUNT FROM $table";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		if(preg_match("#is marked as crashed and last#", $q->mysql_error)){
			echo "`$table` -> crashed, remove \"$table\"\n";
			$q->QUERY_SQL("DROP TABLE $table");
			$q->QUERY_SQL("flush tables");
			$q=new mysql_squid_builder();
			echo "`$table` -> Create category \"$category\"\n";
			$q->CreateCategoryTable($category);
			$q->CreateCategoryTable($category);
			$q=new mysql_squid_builder();
		}
		
		if(!$q->TABLE_EXISTS($table)){
			echo "`$category` -> no such table \"$table\"\n";
			return;
		}		
	}
		
		
	if($file==null){
		$dir="/var/lib/squidguard";
		if($GLOBALS["SHALLA"]){$dir="/root/shalla/BL";}
		if(!is_file("$dir/$category/domains")){
			echo "$dir/$category/domains no such file";
			return;
			
		}
		$file="$dir/$category/domains";
	}
		
	if(!is_file($file)){echo "$file no such file";return;}
		
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "No uuid\n";return;}
	echo "open $file\n";
	
	
	$handle = @fopen($file, "r"); 
	if (!$handle) {echo "Failed to open file\n";return;}
	$q=new mysql_squid_builder();
	if($GLOBALS["CATTO"]<>null){$category=$GLOBALS["CATTO"];}
	$countstart=$q->COUNT_ROWS($table);
	$prefix="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid) VALUES ";
	echo "$prefix\n";
	
	$catz=new mysql_catz();
	$c=0;
	$CBAD=0;
	$CBADIP=0;
	$CBADNULL=0;
	while (!feof($handle)){
		$c++;
		$www =trim(fgets($handle, 4096));
		if($www==null){$CBADNULL++;continue;}
		$www=str_replace('"', "", $www);
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){$CBADIP++;continue;}
		$www=trim(strtolower($www));
		if($www=="thisisarandomentrythatdoesnotexist.com"){$CBAD++;continue;}
		
		if($www==null){$CBADNULL++;continue;}
		if(preg_match("#(.+?)\s+(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^\.(.*)$#", $www,$re)){$www=$re[1];}
		
		if(strpos($www, "#")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "'")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "{")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "(")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, ")")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "%")>0){echo "FALSE: $www\n";continue;}
		
		$category2=$catz->GET_CATEGORIES($www);
		if($category2<>null){
			if($category2==$category){continue;}
			$md5=md5($category.$www);
			
			if($category=="porn"){
				
				
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
				
				if($category2=="hobby/arts"){
					echo date("H:i:s"). " Remove $www from hobby/arts and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hobby_arts WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				


				if($category2=="finance/realestate"){
					echo date("H:i:s"). " Remove $www from finance/realestate and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_finance_realestate WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
					
				}
				
				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science/computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
					
				}
				
				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
								
				}	

				if($category2=="proxy"){
					echo date("H:i:s"). " Remove $www from proxy and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_proxy WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="searchengines"){
					echo date("H:i:s"). " Remove $www from searchengines and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_searchengines WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="blog"){
					echo date("H:i:s"). " Remove $www from blog and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_blog WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="forums"){
					echo date("H:i:s"). " Remove $www from blog and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_blog WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="recreation/sports"){
					echo date("H:i:s"). " Remove $www from recreation/sports and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_sports WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="hacking"){
					echo date("H:i:s"). " Remove $www from hacking and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hacking WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="malware"){
					echo date("H:i:s"). " Remove $www from malware and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_malware WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="drugs"){
					echo date("H:i:s"). " Remove $www from drugs and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_drugs WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="health"){
					echo date("H:i:s"). " Remove $www from health and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_health WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="news"){
					echo date("H:i:s"). " Remove $www from news and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_news WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="audio-video"){
					echo date("H:i:s"). " Remove $www from audio-video and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_audio_video WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="recreation/schools"){
					echo date("H:i:s"). " Remove $www from recreation/schools and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_schools WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="reaffected"){
					echo date("H:i:s"). " Remove $www from reaffected and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_reaffected WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}						
				if($category2=="warez"){
					echo date("H:i:s"). " Remove $www from warez and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_warez WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="suspicious"){
					echo date("H:i:s"). " Remove $www from suspicious and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_suspicious WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				
				
			}
			
			
			if($category=="gamble"){
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			}
			if($category=="proxy"){
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			
				if($category2=="porn"){
					echo date("H:i:s"). " Remove $www from porn and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_porn WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}				
				
				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science/computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}

				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}				
				
				if($category2=="filehosting"){
					echo date("H:i:s"). " Remove $www from filehosting and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_filehosting WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}	

				if($category2=="hacking"){
					echo date("H:i:s"). " Remove $www from hacking and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hacking WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}	
				if($category2=="governments"){
					echo date("H:i:s"). " Remove $www from governments and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_governments WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			}

			if($category=="spyware"){
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="recreation/sports"){
					echo date("H:i:s"). " Remove $www from recreation/sports and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_sports WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="recreation/schools"){
					echo date("H:i:s"). " Remove $www from recreation/schools and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_schools WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="searchengines"){
					echo date("H:i:s"). " Remove $www from searchengines and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_searchengines WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="audio-video"){
					echo date("H:i:s"). " Remove $www from audio-video and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_audio_video WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="suspicious"){
					$q->QUERY_SQL("DELETE FROM category_suspicious WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="health"){
					echo date("H:i:s"). " Remove $www from health and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_health WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="jobsearch"){
					echo date("H:i:s"). " Remove $www from jobsearch and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_jobsearch WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="hobby/arts"){
					$q->QUERY_SQL("DELETE FROM category_hobby_arts WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science_computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}

				if($category2=="recreation/travel"){
					echo date("H:i:s"). " Remove $www from recreation_travel and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_travel WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="dynamic"){
					echo date("H:i:s"). " Remove $www from dynamic and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_dynamic WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				
				if($category2=="finance/realestate"){
					echo date("H:i:s"). " Remove $www from finance_realestate and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_finance_realestate WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="isp"){
					echo date("H:i:s"). " Remove $www from isp and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_isp WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}if($category2=="housing/accessories"){
					echo date("H:i:s"). " Remove $www from housing/accessories and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_housing_accessories WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				
				
				if($category2=="malware"){continue;}
				if($category2=="phishing"){continue;}
				
			}			
			
			echo date("H:i:s"). " $www $category2 SKIP\n";
			continue;
		}
		
		$md5=md5($www.$category);
		$n[]="('$md5',NOW(),'$category','$www','$uuid')";
		
		
		if(count($n)>6000){
			$sql=$prefix.@implode(",",$n);
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";$n=array();continue;}
			$countend=$q->COUNT_ROWS($table);
			$final=$countend-$countstart;
			echo "".numberFormat($c,0,""," ")." items, ".numberFormat($final,0,""," ")." new entries added - $CBADNULL bad entries for null value,$CBADIP entries for IP addresses\n";	
			$n=array();
			
		}
		
	}
	
	fclose($handle);
	
	if(count($f)>0){
			if($c>0){
				$countend=$q->COUNT_ROWS($table);
				$final=$countend-$countstart;
				echo "$c items, $final new entries added - $CBAD bad entries\n";		
				$sql=$prefix.@implode(",",$n);
				$q->QUERY_SQL($sql,"artica_backup");
				if(!$q->ok){echo $q->mysql_error."\n$sql";continue;}
				$n=array();
			}
		}	
		
	$countend=$q->COUNT_ROWS($table);
	$final=$countend-$countstart;
	echo "".numberFormat($final,0,""," ")." new entries added\n";
	
	@unlink($file);
	
	
}

function UFDBGUARD_COMPILE_DB(){
	$tstart=time();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/UFDBGUARD_COMPILE_DB.pid";
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Process already exists PID: ".@file_get_contents($pidfile)."\n";
		return;
	}
	
	
	@file_put_contents($pidfile,getmypid());
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$datas=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	if(strlen($ufdbGenTable)<5){echo "ufdbGenTable no such file\n";return ;}
	
	$md5db=unserialize(@file_get_contents("/etc/artica-postfix/ufdbGenTableMD5"));
	
	
	$count=0;
	while (list ($a, $b) = each ($datas)){
		if(preg_match('#domainlist\s+"(.+)\/domains#',$b,$re)){
			$f["/var/lib/squidguard/{$re[1]}"]="/var/lib/squidguard/{$re[1]}";
		}
	}
	
	
	
	if(!is_array($datas)){echo "No databases set\n";return ;}
	while (list ($directory, $b) = each ($f)){
		$mustrun=false;
		if(preg_match("#.+?\/([a-zA-Z0-9\-\_]+)$#",$directory,$re)){
			$category=$re[1];
			$category=substr($category,0,15);
			if($GLOBALS["VERBOSE"]){echo "Checking $category\n";}
		}
		
		// ufdbGenTable -n -D -W -t adult -d /var/lib/squidguard/adult/domains -u /var/lib/squidguard/adult/urls     
		if(is_file("$directory/domains")){
			$md5=FileMD5("$directory/domains");
			if($md5<>$md5db["$directory/domains"]){
				$mustrun=true;
				$md5db["$directory/domains"]=$md5;
				$dbb[]="$directory/domains";
			}else{
				if($GLOBALS["VERBOSE"]){echo "$md5 is the same, skip $directory/domains\n";}
			}
			
			
			$d=" -d $directory/domains";
		}else{
			if($GLOBALS["VERBOSE"]){echo "$directory/domains no such file\n";}
		}
		if(is_file("$directory/urls")){
			$md5=FileMD5("$directory/urls");
			if($md5<>$md5db["$directory/urls"]){$mustrun=true;$md5db["$directory/urls"]=$md5;$dbb[]="$directory/urls";}
			$u=" -u $directory/urls";
		}
		
		if(!is_file("$directory/domains.ufdb")){$mustrun=true;$dbb[]="$directory/*";}
		
		if($mustrun){
				$dbcount=$dbcount+1;
				$category_compile=$category;
				if(strlen($category_compile)>15){
				$category_compile=str_replace("recreation_","recre_",$category_compile);
				$category_compile=str_replace("automobile_","auto_",$category_compile);
				$category_compile=str_replace("finance_","fin_",$category_compile);
				if(strlen($category_compile)>15){
					$category_compile=str_replace("_", "", $category_compile);
					if(strlen($category_compile)>15){
						$category_compile=substr($category_compile, strlen($category_compile)-15,15);
					}
				}
			}			
				
				
			$cmd="$ufdbGenTable -n -D -W -t $category_compile$d$u";
			echo $cmd."\n";
			$t=time();
			shell_exec($cmd);
			$took=$unix->distanceOfTimeInWords($t,time(),true);
			ufdbguard_admin_events("Compiled $category_compile in $directory took $took",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
			if(function_exists("system_is_overloaded")){
				if(system_is_overloaded(__FILE__)){
					ufdbguard_admin_events("Overloaded system after $dbcount compilations, oberting task...",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
					return;
				}
			}
		}
		$u=null;$d=null;$md5=null;
	}
	
	@file_put_contents("/etc/artica-postfix/ufdbGenTableMD5",serialize($md5db));
	$user=GetSquidUser();
	$chown=$unix->find_program($chown);
	if(is_file($chown)){
		shell_exec("$chown -R $user /var/lib/squidguard/*");
		shell_exec("$chown -R $user /var/log/squid/*");
	}	
	if($dbcount>0){
		$took=$unix->distanceOfTimeInWords($tstart,time(),true);
		ufdbguard_admin_events("Maintenance on Web Proxy urls Databases: $dbcount database(s) took $took",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
	}
	
	
	
}

function BuildMissingUfdBguardDBS($all=false,$output=false){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$Narray=array();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	while (list ($index, $line) = each ($array) ){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			
			if(!$all){
				if(!is_file($path)){
					if($output){echo "Missing $path\n";} 
					$Narray[$path]=@filesize($datas_path);
				}
			}
			if($all){$Narray[$path]=@filesize($datas_path);}
			
		}
		
	}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". count($Narray)." database(s) must be compiled\n";
	if(!$all){
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",serialize($Narray));
		chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",777);
	}
	return $Narray;
}

function UFDBGUARD_STATUS(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$Narray=array();
	$unix=new unix();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	while (list ($index, $line) = each ($array) ){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			$size=$unix->file_size($path);
			$Narray[$path]=$size;
			
		}
		
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",serialize($Narray));
	chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",777);
	
	return $Narray;
}


function DisksStatus($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	
	if(!$aspid){
		$pid=@file_get_contents("$pidfile");
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		$pidTime=$unix->file_time_min($pidTime);
		if($pidTime<5){return;}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, getmypid());
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){
		$php5=$unix->LOCATE_PHP5_BIN();
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --disks");
		return;}	
	
	
	$q=new mysql_squid_builder();
	
	
	if(!$q->TABLE_EXISTS('webfilters_dbstats')){
			
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_dbstats` (
				  `category` varchar(128) NOT NULL PRIMARY KEY,
				  `articasize` BIGINT UNSIGNED NOT NULL,
				  `unitoulouse` BIGINT UNSIGNED NOT NULL,
				  `persosize` BIGINT UNSIGNED  NOT NULL,
				  KEY `articasize` (`articasize`),KEY `unitoulouse` (`unitoulouse`), KEY `persosize` (`persosize`) )  ENGINE = MYISAM;"; $q->QUERY_SQL($sql);
			
	}	
	
	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){echo "-> /var/lib/ftpunivtlse1fr\n";}
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["UNIV"]=$size;
		
		
		
	}
	$dirs=$unix->dirdir("/var/lib/squidguard");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["PERSO"]=$size;
	}	
	
	$dirs=$unix->dirdir("/var/lib/ufdbartica");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["ARTICA"]=$size;
	}	
	
	while (list ($category, $sizes) = each ($array)){
		if(!isset($sizes["UNIV"])){$sizes["UNIV"]=0;}
		if(!isset($sizes["ARTICA"])){$sizes["ARTICA"]=0;}
		if(!isset($sizes["PERSO"])){$sizes["PERSO"]=0;}
		$f[]="('$category','{$sizes["ARTICA"]}','{$sizes["UNIV"]}','{$sizes["PERSO"]}')";
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_dbstats");
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_dbstats (category,articasize,unitoulouse,persosize) VALUES ".@implode(",", $f));
		
	}
	
}


function databases_status(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	if($GLOBALS["VERBOSE"]){echo "databases_status() line:".__LINE__."\n";}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Checks $categoryname\n";}
		if(is_file("/var/lib/squidguard/$categoryname/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains.ufdb\n";}
			$size=@filesize("/var/lib/squidguard/$categoryname/domains.ufdb");
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains\n";}
			$textsize=@filesize("/var/lib/squidguard/$categoryname/domains");
			
		}
		if(!is_numeric($textsize)){$textsize=0;}
		if(!is_numeric($size)){$size=0;}
		$array[$table]=array("DBSIZE"=>$size,"TXTSIZE"=>$textsize);
	}

	if($GLOBALS["VERBOSE"]){print_r($array);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdbguard_db_status", serialize($array));
	shell_exec("$chmod 777 /usr/share/artica-postfix/ressources/logs/web/ufdbguard_db_status");
	
}

function ufdbguard_recompile_missing_dbs(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$touch=$unix->find_program("touch");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		echo "Starting......: ".date("H:i:s")." Webfiltering service $table -> $categoryname\n";
		if(!is_file("/var/lib/squidguard/$categoryname/domains")){
			@mkdir("/var/lib/squidguard/$categoryname",0755,true);
			$sql="SELECT LOWER(pattern) FROM {$ligne["c"]} WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '$table.temp' FIELDS OPTIONALLY ENCLOSED BY 'n'";
			$q->QUERY_SQL($sql);
			if(!is_file("$MYSQL_DATA_DIR/squidlogs/$table.temp")){
				echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp no such file\n";
				continue;
			}
			echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp done...\n";
			@copy("$MYSQL_DATA_DIR/squidlogs/$table.temp", "/var/lib/squidguard/$categoryname/domains");	
			@unlink("$MYSQL_DATA_DIR/squidlogs/$table.temp");
			echo "Starting......: ".date("H:i:s")." Webfiltering service UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$categoryname/domains)\n";
			UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$categoryname/domains");					
		}else{
			echo "Starting......: ".date("H:i:s")." Webfiltering service /var/lib/squidguard/$categoryname/domains OK\n";
			
		}
		
		if(!is_file("/var/lib/squidguard/$categoryname/expressions")){shell_exec("$touch /var/lib/squidguard/$categoryname/expressions");}
		
	}
	build();
	if(is_file("/etc/init.d/ufdb")){
		echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service\n";
		ufdbguard_admin_events("Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"config");
		build_ufdbguard_HUP();
	}
	
}

function ufdbguard_recompile_dbs(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	@unlink("/var/log/artica-postfix/ufdbguard-compilator.debug");
	build();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /var/lib/squidguard/*");
	ufdbguard_recompile_missing_dbs();	
	
}
function ufdbguard_schedule(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$sock=new sockets();
	$unix=new unix();
	$UfdbGuardSchedule=unserialize(base64_decode($sock->GET_INFO("UfdbGuardSchedule")));
	if(!isset($UfdbGuardSchedule["EnableSchedule"])){$UfdbGuardSchedule["EnableSchedule"]=1;$UfdbGuardSchedule["H"]=5;$UfdbGuardSchedule["M"]=0;}
	$cronfile="/etc/cron.d/artica-ufdb-dbs";	
	if(!is_numeric($UfdbGuardSchedule["EnableSchedule"])){$UfdbGuardSchedule["EnableSchedule"]=1;}
	if($UfdbGuardSchedule["EnableSchedule"]==0){
		@unlink($cronfile);
		echo "Starting......: ".date("H:i:s")." Webfiltering service recompile all databases is not scheduled\n";
		return;
	}
	if(!is_numeric($UfdbGuardSchedule["H"])){$UfdbGuardSchedule["H"]=5;}
	if(!is_numeric($UfdbGuardSchedule["M"])){$UfdbGuardSchedule["M"]=0;}
	$f[]="MAILTO=\"\"";
	$f[]="{$UfdbGuardSchedule["H"]} {$UfdbGuardSchedule["M"]} * * * root ".$unix->LOCATE_PHP5_BIN()." ".__FILE__." --ufdbguard-recompile-dbs >/dev/null 2>&1"; 
	$f[]="";
	@file_put_contents($cronfile,@implode("\n",$f) );	
	echo "Starting......: ".date("H:i:s")." Webfiltering service recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}\n";
	//events_ufdb_tail("ufdbGuard recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}",__LINE__);
}

function UFDBGUARD_COMPILE_CATEGORY_PROGRESS($text,$pourc){
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["OUTPUT"]){echo "{$pourc}% $text\n";sleep(2);}
}

function UFDBGUARD_COMPILE_CATEGORY($category){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}	
	if($EnableRemoteStatisticsAppliance==1){
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} Stat Appliance enabled",110);
		return;
	}
	if($UseRemoteUfdbguardService==1){
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} Use remote service",110);
		return;
	}	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){
		$ufdbguardd=$unix->find_program("ufdbguardd");
		system("$ufdbguardd -v");
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} $category category aborting,task pid $pid running since {$time}Mn",110);
		ufdbguard_admin_events("Compile $category category aborting,task pid $pid running since {$time}Mn",__FUNCTION__,__FILE__,__LINE__,"compile");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$t=time();
	
	echo "Starting......: ".date("H:i:s")." Compiling category $category\n";
	UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{compiling} Compiling category $category",2);
	$ufdb=new compile_ufdbguard();
	$ufdb->compile_category($category);
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: ".date("H:i:s")." This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}	
}

function UFDBGUARD_COMPILE_ALL_CATEGORIES(){
	$sock=new sockets();
	if(system_is_overloaded(basename(__FILE__))){
		squid_admin_mysql(1, "Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting recompiling personal categories", null,__FILE__,__LINE__);
		die();
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}	
	if($EnableRemoteStatisticsAppliance==1){return;}
	if($UseRemoteUfdbguardService==1){return;}		
	
	if($EnableRemoteStatisticsAppliance==1){ return; }	
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableRemoteStatisticsAppliance==1){UFDBGUARD_DOWNLOAD_ALL_CATEGORIES();return;}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}		
	$q=new mysql_squid_builder();
	$t=time();
	$cats=$q->LIST_TABLES_CATEGORIES();
	$ufdb=new compile_ufdbguard();
	while (list ($table, $line) = each ($cats) ){
		if(preg_match("#categoryuris_#",$table)){continue;}
		$category=$q->tablename_tocat($table);
		if($category==null){squid_admin_mysql(1,"Compilation failed for table $table, unable to determine category",null,__FILE__,__LINE__);continue;}
		$ufdb->compile_category($category);
		
	}
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2,"All personal categories are compiled ($ttook)",@implode("\n", $cats),__FILE__,__LINE__,"global-compile");
	if($EnableWebProxyStatsAppliance==1){CompressCategories();return;}
	
	
}

function CompressCategories(){
	
	
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$lighttpdUser=$unix->LIGHTTPD_USER();
	$StorageDir="/usr/share/artica-postfix/ressources/databases";
	
	if(!is_dir("/var/lib/squidguard")){ufdbguard_admin_events("/var/lib/squidguard no such directory",__FUNCTION__,__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	if(is_dir("/var/lib/squidguard")){
		chdir("/var/lib/squidguard");
		if(is_file("$StorageDir/blacklist.tar.gz")){@unlink("$StorageDir/blacklist.tar.gz");}
		writelogs("Compressing /var/lib/squidguard",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$tar -czf $StorageDir/blacklist.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/blacklist.tar.gz");
	}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		chdir("/var/lib/ftpunivtlse1fr");
		writelogs("Compressing /var/lib/ftpunivtlse1fr",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/ftpunivtlse1fr.tar.gz")){@unlink("$StorageDir/ftpunivtlse1fr.tar.gz");}
		shell_exec("$tar -czf $StorageDir/ftpunivtlse1fr.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/ftpunivtlse1fr.tar.gz");
	}
	
	if(is_dir("/etc/dansguardian")){
		chdir("/etc/dansguardian");
		writelogs("Compressing /etc/dansguardian",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/dansguardian.tar.gz")){@unlink("$StorageDir/dansguardian.tar.gz");}
		exec("$tar -czf $StorageDir/dansguardian.tar.gz * 2>&1",$lines);
		while (list ($linum, $line) = each ($lines) ){writelogs($line,__FUNCTION__,__FILE__,__LINE__);}
		if(!is_file("$StorageDir/dansguardian.tar.gz")){writelogs(".$StorageDir/dansguardian.tar.gz no such file",__FUNCTION__,__FILE__,__LINE__);}
		shell_exec("$chmod 770 /usr/share/artica-postfix/ressources/databases/dansguardian.tar.gz");
	}
	
	writelogs("Compressing done, apply permissions for `$lighttpdUser` user",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir");
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir/*");
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("compress all categories done ($ttook)",__FUNCTION__,__FILE__,__LINE__,"global-compile");	
	
	
	
}

function cron_compile(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$isFiltersInstalled=false;
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){return;}
	$users=new usersMenus();
	if($users->APP_UFDBGUARD_INSTALLED){$isFiltersInstalled=true;}
	if($users->DANSGUARDIAN_INSTALLED){$isFiltersInstalled=true;}
	if($EnableWebProxyStatsAppliance==0){if(!$isFiltersInstalled){return;}}

	
			
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$restart=false;
	if($unix->process_exists(@file_get_contents($pidfile))){return;}
	@file_put_contents($pidfile, getmypid());
	
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.alldbs")){
		$WHY="ufdbguard.compile.alldbs exists";
		@unlink("/etc/artica-postfix/ufdbguard.compile.alldbs");
		events_ufdb_exec("CRON:: -> ufdbguard_recompile_dbs()");
		ufdbguard_admin_events("-> ufdbguard_recompile_dbs()",__FUNCTION__,__FILE__,__LINE__,"config");
		UFDBGUARD_COMPILE_ALL_CATEGORIES();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.missing.alldbs")){
		$WHY="ufdbguard.compile.missing.alldbs exists";
		events_ufdb_exec("CRON:: -> ufdbguard_recompile_missing_dbs()");
		@unlink("/etc/artica-postfix/ufdbguard.compile.missing.alldbs");
		ufdbguard_admin_events("-> ufdbguard_recompile_missing_dbs()",__FUNCTION__,__FILE__,__LINE__,"config");
		ufdbguard_recompile_missing_dbs();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.reconfigure.task")){
		$WHY="ufdbguard.reconfigure.task exists";
		events_ufdb_exec("CRON:: -> build()");
		@unlink("/etc/artica-postfix/ufdbguard.reconfigure.task");
		ufdbguard_admin_events("-> build()",__FUNCTION__,__FILE__,__LINE__,"config");
		build();
		return;
	}
	

	foreach (glob("/etc/artica-postfix/ufdbguard.recompile-queue/*") as $filename) {
		$restart=true;
		$db=@file_get_contents($filename);
		@unlink($filename);
		ufdbguard_admin_events("-> UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$db/domains)",__FUNCTION__,__FILE__,__LINE__,"config");
		UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$db/domains");
		
		
	}
	
	if($restart){
		$unix->send_email_events("cron-compile: Ask to reload ufdbguard service", "\n$WHY\nFunction:".__FUNCTION__."\nFile:".__FILE__."\nLine:".__LINE__, "proxy");
		ufdbguard_admin_events("Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		build_ufdbguard_HUP();
	}
	
	
}

function UFDBGUARD_DOWNLOAD_ALL_CATEGORIES(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$sock=new sockets();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("/tmp/blacklist.tar.gz")){ufdbguard_admin_events("Failed to download blacklist.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	shell_exec("$rm -rf /var/lib/squidguard/*");
	exec("$tar -xf /tmp/blacklist.tar.gz -C /var/lib/squidguard/ 2>&1",$results);
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("Extracting blacklist.tar.gz took $ttook `".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__,"global-compile");
	
	$array=$unix->dirdir("/var/lib/squidguard");
	$GLOBALS["NORESTART"]=true;
	while (list ($index, $directoryPath) = each ($array)){
		if(!is_file("$directoryPath/domains.ufdb")){UFDBGUARD_COMPILE_SINGLE_DB("$directoryPath/domains");}
	}
	
	build_ufdbguard_HUP();
	

}

function Dansguardian_remote(){
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();	
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";	
	$uri="$baseUri/dansguardian.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/dansguardian.tar.gz")){
		$cmd="$tar -xf /tmp/dansguardian.tar.gz -C /etc/dansguardian/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
		if($users->DANSGUARDIAN_INSTALLED){
			echo "Starting......: ".date("H:i:s")." Dansguardian reloading service\n";
			shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
		}		
		
	}else{
		ufdbguard_admin_events("Failed to download dansguardian.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}		
}


function ufdbguard_remote(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($timeFile)<5){
		writelogs("too short time to change settings, aborting $called...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	@mkdir("/etc/ufdbguard",null,true);
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";
	
	if($DenyUfdbWriteConf==0){
		$uri="$baseUri/ufdbGuard.conf";
		$curl=new ccurl($uri,true);
		if($curl->GetFile("/tmp/ufdbGuard.conf")){
			@file_put_contents("/etc/ufdbguard/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
			@file_put_contents("/etc/squid3/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
		}else{
			ufdbguard_admin_events("Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
		}
	}

	$uri="$baseUri/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/blacklist.tar.gz")){
		$cmd="$tar -xf /tmp/blacklist.tar.gz -C /var/lib/squidguard/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		ufdbguard_admin_events("Failed to download blacklist.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}	
	
	$uri="$baseUri/ftpunivtlse1fr.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/ftpunivtlse1fr.tar.gz")){
		$cmd="$tar -xf /tmp/ftpunivtlse1fr.tar.gz -C /var/lib/ftpunivtlse1fr/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		ufdbguard_admin_events("Failed to download ftpunivtlse1fr.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}

	Dansguardian_remote();	
	
	CheckPermissions();	
	ufdbguard_schedule();
	
	if($unix->Ufdbguard_remote_srvc_bool()){ufdbguard_admin_events("Using a remote UfdbGuard service, aborting",__FUNCTION__,__FILE__,__LINE__,"config");return;}
	
	
	ufdbguard_admin_events("Service will be rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
	build_ufdbguard_HUP();
	

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(is_file($GLOBALS["SQUIDBIN"])){
		echo "Starting......: ".date("H:i:s")." Squid reloading service\n";
		shell_exec("$nohup $php5 ". basename(__FILE__)."/exec.squid.php --reconfigure-squid >/dev/null 2>&1");
	}	
	
	$datas=@file_get_contents("/etc/ufdbguard/ufdbGuard.conf");
	send_email_events("SquidGuard/ufdbGuard/Dansguardian rules was rebuilded",basename(__FILE__)."\nFunction:".__FUNCTION__."\nLine:".__LINE__."\n".
	"This is new configuration file of the squidGuard/ufdbGuard:\n-------------------------------------\n$datas","proxy");
	shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.c-icap.php --maint-schedule");	
	
	
}





function events_ufdb_exec($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-compilator.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text\n";
		
		@fwrite($f,$text );
		@fclose($f);	
		}


function events_ufdb_tail($text,$line=0){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($line>0){$line=" line:$line";}else{$line=null;}
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text$line\n";
		if($GLOBALS["VERBOSE"]){echo $textnew;}
		@fwrite($f,$textnew );
		@fclose($f);	
		events_ufdb_exec($textnew);
		}

function CompileCategoryWords(){
	$unix=new unix();
	$uuid="8cdd119c-2dc1-452d-b9d0-451c6046464f";
	$f=$unix->DirRecursiveFiles("/etc/dansguardian/lists/phraselists");
	$q=new mysql_squid_builder();
	while (list ($index, $filename) = each ($f) ){
		$basename=basename($filename);
		
		
		if(!preg_match("#weighted#",$basename)){continue;}
		$categoryname=basename(dirname($filename));
		$language="english";
		if($categoryname=="pornography"){$categoryname="porn";}
		if($categoryname=="gambling"){$categoryname="gamble";}
		if($categoryname=="nudism"){$categoryname="mixed_adult";}
		if($categoryname=="illegaldrugs"){$categoryname="drugs";}
		if($categoryname=="translation"){$categoryname="translators";}
		if($categoryname=="warezhacking"){$categoryname="warez";}
		
		
		if(preg_match("#weighted_(.+)#", $basename,$re)){$language=$re[1];}
		$language=str_replace("general_", "",$language);
		echo "$basename -> $categoryname ($language)\n";
		
		$q->CreateCategoryWeightedTable();
		
		$lines=explode("\n",@file_get_contents($filename));
		
		
		$prefix="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,score,uuid,language) VALUES ";
		
		while (list ($linum, $line) = each ($lines) ){
			if(substr($line,0,1)=="#"){continue;}
			if(preg_match("#.+?<([0-9]+)>$#",$line,$re)){
				$line=str_replace("<{$re[1]}>","",$line);
				echo "$categoryname: $line -> score:{$re[1]}\n";
				$score=$re[1];
				$zmd5=md5($line.$score);
				$zDate=date('Y-m-d H:i:s');
				$line=addslashes($line);
				$sqls[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
				$sqlb[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
			}
		}
		
		$q->QUERY_SQL($prefix.@implode(",",$sqls));
		if(!$q->ok){echo $q->mysql_error."\n";}
		$sqls=array();
		
	}
	
	@file_put_contents("/root/weightedPhrases.db", serialize($sqlb));

	
}	

function notify_remote_proxys(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM squidservers";
		$results=$q->QUERY_SQL($sql);
		
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		writelogs($uri,__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="FILTERS";
		
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data");
	}
}

function FIX_1_CATEGORY_CHECKED(){
	@mkdir("/var/lib/squidguard/checked",0755,true);
	if(!is_file("/var/lib/squidguard/checked/domains")){
		@unlink("/var/lib/squidguard/checked/domains.ufdb");
		for($i=0;$i<=10;$i++){
				$f[]=md5(time()."$i.com").".com";
				$t[]=md5(time()."$i.com").".com/index.html";
		}
		
		@file_put_contents("/var/lib/squidguard/checked/domains", @implode("\n", $f));
	}
	
	if(!is_file("/var/lib/squidguard/checked/urls")){@file_put_contents("/var/lib/squidguard/checked/urls", @implode("\n", $t));}
	if(!is_file("/var/lib/squidguard/checked/expressions")){@file_put_contents("/var/lib/squidguard/checked/expressions", "\n");}	
	
	if(!is_file("/var/lib/squidguard/checked/domains.ufdb")){
		$ufd=new compile_ufdbguard();
		$ufd->compile_category("checked");
	}
	
	
	
}

function ufdbdatabases_in_mem(){
	$sock=new sockets();
	$unix=new unix();
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	if($UfdbDatabasesInMemory==0){
		echo "Starting URLfilterDB Database in memory feature is disabled\n";
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM>0){
			echo "Starting URLfilterDB Database unmounting...\n";
			$umount=$unix->find_program("umount");
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
		}
		return;
	}
	
	
	$POSSIBLEDIRS[]="/var/lib/ufdbartica";
	$POSSIBLEDIRS[]="/var/lib/squidguard";
	$POSSIBLEDIRS[]="/var/lib/ftpunivtlse1fr";
	
	$ufdbartica_size=$unix->DIRSIZE_BYTES("/var/lib/ufdbartica");
	$ufdbartica_size=round(($ufdbartica_size/1024)/1000)+5;
	
	$squidguard_size=$unix->DIRSIZE_BYTES("/var/lib/squidguard");
	$squidguard_size=round(($squidguard_size/1024)/1000)+5;
	$ftpunivtlse1fr_size=$unix->DIRSIZE_BYTES("/var/lib/ftpunivtlse1fr");
	$ftpunivtlse1fr_size=round(($ftpunivtlse1fr_size/1024)/1000)+5;
	echo "Starting URLfilterDB ufdbartica DB....: about {$ufdbartica_size}MB\n";
	echo "Starting URLfilterDB squidguard DB....: about {$squidguard_size}MB\n";
	echo "Starting URLfilterDB ftpunivtlse1fr DB: about {$ftpunivtlse1fr_size}MB\n";
	$total=$ufdbartica_size+$squidguard_size+$ftpunivtlse1fr_size+10;
	echo "Starting URLfilterDB require {$total}MB\n";
	$mount=$unix->find_program("mount");
	
	$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
	if($MOUNTED_DIR_MEM==0){
		$system_mem=$unix->TOTAL_MEMORY_MB();
		echo "Starting URLfilterDB system memory {$system_mem}MB\n";
		if($system_mem<$total){
			$require=$total-$system_mem;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
		$system_free=$unix->TOTAL_MEMORY_MB_FREE();
		echo "Starting URLfilterDB system memory available {$system_free}MB\n";
		if($system_free<$total){
			$require=$total-$system_free;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
	}
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$chown=$unix->find_program("chown");
	if($MOUNTED_DIR_MEM>0){
		if($MOUNTED_DIR_MEM<$total){
			echo "Starting URLfilterDB: umounting from memory\n";
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
			$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		}
	}

	if($MOUNTED_DIR_MEM==0){
		if(strlen($idbin)<3){echo "Starting URLfilterDB: tmpfs `id` no such binary\n";return;}
		if(strlen($mount)<3){echo "Starting URLfilterDB: tmpfs `mount` no such binary\n";return;}
		exec("$idbin squid 2>&1",$results);
		if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}
		$uid=$re[1];
		$gid=$re[2];
		echo "Starting URLfilterDB: tmpfs uid/gid =$uid:$gid for {$total}M\n";
		@mkdir("/var/lib/ufdbguard-memory");
		$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$total}M,nr_inodes=10k,mode=0700 tmpfs \"/var/lib/ufdbguard-memory\"";
		shell_exec($cmd);	
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM==0){
			echo "Starting URLfilterDB: tmpfs failed...\n";
			return;
		}
	}
	
	echo "Starting URLfilterDB: mounted as {$MOUNTED_DIR_MEM}MB\n";
	reset($POSSIBLEDIRS);
	while (list ($index, $directory) = each ($POSSIBLEDIRS) ){
		$directoryname=basename($directory);
		@mkdir("/var/lib/ufdbguard-memory/$directoryname",0755,true);
		if(!is_dir("/var/lib/ufdbguard-memory/$directoryname")){
			echo "Starting URLfilterDB: $directoryname permission denied\n";
			return;
		}
		@chown("/var/lib/ufdbguard-memory/$directoryname","squid");
		echo "Starting URLfilterDB: replicating $directoryname\n";
		shell_exec("$cp -rfu $directory/* /var/lib/ufdbguard-memory/$directoryname/");
	}
	
	$ufdbguardConfs[]="/etc/ufdbguard/ufdbGuard.conf";
	$ufdbguardConfs[]="/etc/squid3/ufdbGuard.conf";
	
	echo "Starting URLfilterDB: setup privileges\n";
	shell_exec("$chown -R squid:squid /var/lib/ufdbguard-memory >/dev/null 2>&1");
	
	echo "Starting URLfilterDB: modify configuration files\n";
	while (list ($index, $configfile) = each ($ufdbguardConfs) ){
		$f=explode("\n",@file_get_contents($configfile));
		while (list ($indexLine, $line) = each ($f) ){
			reset($POSSIBLEDIRS);
			while (list ($index, $directory) = each ($POSSIBLEDIRS) ){
				$directoryname=basename($directory);
				$line=str_replace($directory, "/var/lib/ufdbguard-memory/$directoryname", $line);
				$f[$indexLine]=$line;
			}
		}
	
		@file_put_contents($configfile, @implode("\n", $f));
		echo "Starting URLfilterDB: $configfile success...\n";
	}
	
}



function stop_ufdbguard($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=ufdbguard_pid();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=ufdbguard_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	squid_admin_mysql(0, "Stopping Web Filtering engine service","",__FILE__,__LINE__);
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=ufdbguard_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function ufdguard_artica_db_status(){
	$unix=new unix();
	$mainpath="/var/lib/ufdbartica";
	
	
	$mainpath_size=$unix->DIRSIZE_BYTES($mainpath);
	
	$array["SIZE"]=$mainpath_size;
	if(is_file("$mainpath/category_porn/domains.ufdb")){
		$date=filemtime("$mainpath/category_porn/domains.ufdb");
		$array["DATE"]=$date;
	}else{
		$array["DATE"]=0;
	}
	@file_put_contents("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS", serialize($array));
	
}




function parseTemplate_headers($title,$addhead=null,$SquidGuardIPWeb=null){
	$sock=new sockets();
	
	if(!isset($GLOBALS["UfdbGuardHTTP"])){$sock->BuildTemplatesConfig();}
	
	if($SquidGuardIPWeb<>null){
		$SquidGuardIPWeb=str_replace("/".basename(__FILE__), "", $SquidGuardIPWeb);
		
	}
	
	$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"];
	if(isset($_REQUEST["unlock"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["unlock-www"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["smtp-send-email"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"])){$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]=2639;}
	
	
	$SquidHTTPTemplateSmiley=intval($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]);
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"])){
		$SquidHTTPTemplateSmileyEnable=1;
	}else{
		$SquidHTTPTemplateSmileyEnable=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"];
	}
	
	$BackgroundColorBLKBT=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"];
	
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	


	
	
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	$f[]="<head>";
	$f[]=$addhead;
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-1.8.3.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-ui-1.8.22.custom.min.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery.blockUI.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/mouse.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/default.js\"></script>";
	$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/XHRConnection.js\"></script>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function blur(){ }";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"$Background\";";
	$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\{$SquidHTTPTemplateSmiley}\";}";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]=".important{";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }    ";
	$f[]="p {";
	$f[]="        font-size: 12pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }    ";
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }
			
			
.Button2014-lg {
	border-radius: 6px 6px 6px 6px;
	-moz-border-radius: 6px 6px 6px 6px;
	-khtml-border-radius: 6px 6px 6px 6px;
	-webkit-border-radius: 6px 6px 6px 6px;
	font-size: 18px;
	line-height: 1.33;
	padding: 10px 16px;
}
.Button2014-success {
	background-color: $BackgroundColorBLKBT;
	border-color: #000000;
	color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};
}
.Button2014 {
	-moz-user-select: none;
	border: 1px solid transparent;
	border-radius: 4px 4px 4px 4px;
	cursor: pointer;
	display: inline-block;
	font-size: 22px;
	font-weight: normal;
	line-height: 1.42857;
	margin-bottom: 0;
	padding: 6px 22px;
	text-align: center;
	vertical-align: middle;
	white-space: nowrap;
	font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};
}";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	
	
	
	if($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]==1){
		$SquidHTTPTemplateLogoPositionH=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionH"];
		$SquidHTTPTemplateLogoPositionL=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionL"];
		$picturemode=$GLOBALS["UfdbGuardHTTP"]["picturemode"];
		if($picturemode==null){$picturemode="absolute";}
		$widthDiv="100%";
		$heightDiv=null;
		$align=null;
		
			list($width, $height, $type, $attr) = getimagesize(dirname(__FILE__)."/{$GLOBALS["UfdbGuardHTTP"]["picture_path"]}");
			
			$heightDiv="height:{$height}px;";
			$background="background-position:{$SquidHTTPTemplateLogoPositionL}% {$SquidHTTPTemplateLogoPositionH}%;";
		
			if($picturemode=="absolute"){
				$widthDiv="{$width}px";
				$background=null;
			}
		
		if($GLOBALS["UfdbGuardHTTP"]["picturealign"]<>null){
			$align="text-align:{$GLOBALS["UfdbGuardHTTP"]["picturealign"]};";
		}
		$f[]="<div style='position:{$picturemode};{$align}width:{$widthDiv};$heightDiv
		background-image:url(\"$SquidGuardIPWeb/{$GLOBALS["UfdbGuardHTTP"]["picture_path"]}\");
		background-repeat:no-repeat;$background
		left:{$SquidHTTPTemplateLogoPositionL}%;
		top:{$SquidHTTPTemplateLogoPositionH}%;
		'
		>&nbsp;</div>
		";
	
	}	
	
	
	$f[]="<div id=\"wrapper\">";	
	return @implode("\n", $f);
}


function parseTemplate_GET_REMOTE_ADDR(){
	if(isset($_SERVER["REMOTE_ADDR"])){
		$IPADDR=$_SERVER["REMOTE_ADDR"];
		if($GLOBALS["VERBOSE"]){echo "REMOTE_ADDR = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){
		$IPADDR=$_SERVER["HTTP_X_REAL_IP"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_REAL_IP = $IPADDR<br>\n";}
	}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];
		if($GLOBALS["VERBOSE"]){echo "HTTP_X_FORWARDED_FOR = $IPADDR<br>\n";}
	}
	$GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
	if($GLOBALS["VERBOSE"]){echo "HTTP_USER_AGENT = {$GLOBALS["HTTP_USER_AGENT"]}<br>\n";}

	if($GLOBALS["VERBOSE"]){
		while (list ($num, $Linz) = each ($_SERVER) ){
			if(is_array($Linz)){
				while (list ($a, $b) = each ($Linz) ){
					echo "<li style='font-size:10px'>\$_SERVER[\"$num\"][\"$a\"]=\"$b\"</li>\n";
				}
				continue;
			}
			echo "<li style='font-size:10px'>\$_SERVER[\"$num\"]=\"$Linz\"</li>\n";
		}

	}


	$GLOBALS["IPADDR"]=$IPADDR;
	return $IPADDR;
}

function parseTemplate_smtp_button($ARRAY,$SquidGuardIPWeb){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$client_username=$ARRAY["clientname"];
	$ARRAY["SquidGuardIPWeb"]=$SquidGuardIPWeb;
	$serialize_array=base64_encode(serialize($ARRAY));
	$sock=new sockets();
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	
	$client_username=$ARRAY["clientname"];
	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$email=null;
	$t=time();
	if($client_username<>null){
	
		$sock=new sockets();
		$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
		if($EnableKerbAuth==1){
				
			$ad=new external_acl_squid_ldap();
			$array=$ad->ADLdap_userinfos($client_username);
			$email=$array[0]["mail"][0];
				
		}else{
				
			$users=new user($client_username);
			if(count($users->email_addresses)>0){
				$email=$users->email_addresses[0];
			}
				
		}
	}
	
	
	
	return "
	<form method='post' action='$SquidGuardIPWeb' id='post-send-email'>
		<input type='hidden' name='smtp-send-email' value='yes'>
		<input type='hidden' name='email' value='$email'>
		<input type='hidden' name='serialize' value='$serialize_array'>
	</form>
	<a href=\"javascript:blur();\" OnClick=\"javascript:document.forms['post-send-email'].submit();\">{$SquidGuardWebSMTP["smtp_recipient"]}</a>";
	
	
}

function parseTemplate_smtp_post(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_POST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$serialize_array=$_POST["serialize"];
	
	
	
	$_RULE_K=$ARRAY["RULE"];
	$IPADDR=$ARRAY["IPADDR"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$URL=$ARRAY["URL"];
	$HOST=$ARRAY["HOST"];
	
	$members[]=$IPADDR;
	if($HOST<>null){$members[]=$HOST; }
	if(trim($IpToUid)<>null){$members[]=$IpToUid;}
	if(count($members)>0){while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
	$members=array();
	while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}}
	$membersTX=@implode(", ", $members);
	
	
	$email=$_POST["email"];

	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$error=parseTemplate_sendemail_perform($email,$ARRAY);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];

	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);
	
	
	$f[]=parseTemplate_headers($notify_your_administrator,null,$SquidGuardIPWeb);
	$f[]="    <h2>{notify_your_administrator}</h2>";
	if($error<>null){$f[]="    <h2>$error</h2>";}
	$f[]="<div id=\"info\">";
	$f[]="";
	$f[]="<form id='send-email-form' action=\"$SquidGuardIPWeb\" method=\"post\">
		<input type='hidden' name='smtp-send-email' value='yes'>
		<input type='hidden' name='email' value='$email'>
		<input type='hidden' name='serialize' value='$serialize_array'>";
	$f[]="<table width='100%;'>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>
	<p style='margin-top:50px'>&nbsp;</p>";	
	
	
	
	
	
	if($email==null){
		$f[]="<table width='100%;'>";
	
	
	$f[]="
	<tr>
		<td class=\"info_title\">{email}:</td>
		<td class=\"info_content\">".Field_text("email",$_REQUEST["email"],"$fontfamily;width:80%;font-size:35px;padding:5px"
				,null,null,null,false,"CheckTheForm(event)")."</td>
	</tr>
	";
	$f[]=" <tr><td colspan=2 align='right'><p style='margin-top:50px'>&nbsp;</p></td></tr>";
	$f[]=" <tr><td colspan=2 align='right'><hr>". button("{submit}","document.forms['send-email-form'].submit();")."</td></tr>
	</table>";
	}
	$f[]="
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['send-email-form'].submit();
		}
		
	</script>
	";
	
	
	$f[]="</div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
	
	
	
	
}


function parseTemplate_sendemail_js(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.external_acl_squid_ldap.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$your_query_was_sent_to_administrator= $tpl->javascript_parse_text("{your_query_was_sent_to_administrator}",0);
	
	$ARRAY=unserialize(base64_decode($_GET["serialize"]));
	$client_username=$ARRAY["clientname"];
	$SquidGuardIPWeb=$ARRAY["SquidGuardIPWeb"];
	$email=null;
	$t=time();
	if($client_username<>null){
		
		$sock=new sockets();
		$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
		if($EnableKerbAuth==1){
			
			$ad=new external_acl_squid_ldap();
			$array=$ad->ADLdap_userinfos($client_username);
			$email=$array[0]["mail"][0];
			
		}else{
			
			$users=new user($client_username);
			if(count($users->email_addresses)>0){
				$email=$users->email_addresses[0];
			}
			
		}
	}
	
	if($email<>null){
		echo "
		// $client_username
		var xSMTPNotifValues$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
		}
		
		function SMTPNotifValues$t(){
		
		var jqxhr = $.post( '$SquidGuardIPWeb',{'send-smtp-notif':'$email','MAIN_ARRAY':'{$_GET["serialize"]}'}, function(result) {
			alert( '$your_query_was_sent_to_administrator' );
		})
		.done(function(result) {
		alert('$your_query_was_sent_to_administrator' );
		})
		.fail(function() {
		alert( '$your_query_was_sent_to_administrator' );
		})
		.always(function() {
		//alert( 'unknown' );
		});
		
		
		//	var XHR = new XHRConnection();
		//	XHR.setLockOff();
		//	XHR.appendData('send-smtp-notif','$email');
		//	XHR.appendData('MAIN_ARRAY','{$_GET["serialize"]}');
		//	XHR.sendAndLoad('$SquidGuardIPWeb', 'POST',xSMTPNotifValues$t);
		}
		SMTPNotifValues$t();";
		return;
		
	}
	
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{give_your_email_address}");
	echo " //$client_username
	var xSMTPNotifValues$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	
	function SMTPNotifValues$t(){
		var email=prompt('$title');
		if(!email){return;}
		
		var jqxhr = $.post( '$SquidGuardIPWeb',{'send-smtp-notif':email,'MAIN_ARRAY':'{$_GET["serialize"]}'}, function(result) {
			alert( '$your_query_was_sent_to_administrator' );
		})
		.done(function(result) {
			alert('$your_query_was_sent_to_administrator' );
		})
		.fail(function(xhr, textStatus, errorThrown){
			 alert('$your_query_was_sent_to_administrator' +xhr.responseText);
			 		 
		})
		.always(function() {
		 //none
		});
		
		
		//var XHR = new XHRConnection();
		//XHR.appendData('send-smtp-notif',email);
		//XHR.appendData('MAIN_ARRAY','{$_GET["serialize"]}');
		//XHR.sendAndLoad('$SquidGuardIPWeb', 'POST',xSMTPNotifValues$t);
	}
	SMTPNotifValues$t();";
	return;	
	
}

function parseTemplate_sendemail_perform($smtp_sender=null,$ARRAY,$ticket=false,$SquidGuardIPWeb=null){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	
	
	if(!$ticket){
		if($smtp_sender==null){
			$tpl=new templates();
			return $tpl->_ENGINE_parse_body("{give_your_email_address}");
		}
	}
	$main_array=base64_encode(serialize($ARRAY));
	$tpl=new templates();
	$HOST=$ARRAY["HOST"];
	$clientname=$ARRAY["clientname"];
	if($clientname<>null){$clientname="/$clientname";}
	$Subject="Web filter request from an user: {$HOST}$clientname";
	$zDate=date('Y-m-d H:i:s');
	$q=new mysql_squid_builder();
	$URL=$ARRAY["URL"];
	$REASONGIVEN=$ARRAY["REASONGIVEN"];
	
	
	unset($ARRAY["SquidGuardIPWeb"]);
	unset($ARRAY["URL"]);
	unset($ARRAY["REASONGIVEN"]);
	
	
	while (list ($a, $b) = each ($ARRAY) ){
		$body[]="$a\t:$b";
	
	
	}
	
	$text=mysql_escape_string2(@implode($body, "\r\n"));
	$Subject=mysql_escape_string2($Subject);
	$URL=mysql_escape_string2($URL);
	$REASONGIVEN=mysql_escape_string2($REASONGIVEN);
	$md5=md5(serialize($ARRAY)."$Subject $smtp_sender");
	$ticket_val=0;
	if($ticket){$ticket_val=1;}
	
	$tablename="ufdb_smtp";
	if($q->COUNT_ROWS("ufdb_smtp")==0){
		$q->QUERY_SQL("DROP TABLE ufdb_smtp");
	}
	$sql="CREATE TABLE IF NOT EXISTS `ufdb_smtp` (
	`zmd5` varchar(90) NOT NULL,
	`zDate` datetime NOT NULL,
	`Subject` varchar(255) NOT NULL,
	`content` varchar(255) NOT NULL,
	`main_array` TEXT,
	`URL` varchar(255) NOT NULL,
	`REASONGIVEN` varchar(255) NOT NULL,
	`sender` varchar(128) NOT NULL,
	`retrytime` smallint(1) NOT NULL,
	`ticket` smallint(1) NOT NULL,
	`SquidGuardIPWeb` varchar(255),
	PRIMARY KEY (`zmd5`),
	KEY `zDate` (`zDate`),
	KEY `Subject` (`Subject`),
	KEY `sender` (`sender`),
	KEY `ticket` (`ticket`),
	KEY `retrytime` (`retrytime`)
	
	) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;return;}
	
	if(!$q->FIELD_EXISTS("ufdb_smtp", "SquidGuardIPWeb")){
		$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `SquidGuardIPWeb` varchar(255)");
	}
	if(!$q->FIELD_EXISTS("ufdb_smtp", "ticket")){
		$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `ticket` smallint(1) NOT NULL");
	}
	if(!$q->FIELD_EXISTS("ufdb_smtp", "main_array")){
		$q->QUERY_SQL("ALTER TABLE `ufdb_smtp` ADD `main_array` TEXT");
	}		
	
	
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO ufdb_smtp (`zmd5`,`zDate`,`Subject`,`content`,`sender`,`URL`,
			`REASONGIVEN`,`retrytime`,`SquidGuardIPWeb`,`ticket`,`main_array`) VALUES
			('$md5',NOW(),'$Subject','$text','$smtp_sender','$URL','$REASONGIVEN','0','$SquidGuardIPWeb','$ticket_val','$main_array')");
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return $q->mysql_error_html();
		return false;
	}
	
	
	$sock=new sockets();
	$sock->getFrameWork("squidguardweb.php?smtp-notifs=yes");
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body("{your_query_was_sent_to_administrator}");
	
	return true;
	
	
}





function parseTemplate_debug($text,$line){
	if(!$GLOBALS["VERBOSE"]){return;}
	echo "<p style='color:yellow'>$text ( in line $line )</p>\n";
}


function parseTemplate_build_main($ARRAY){
	$sock=new sockets();
	$page=CurrentPageName();
	if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=null;}
	if($GLOBALS["ARTICA_VERSION"]==null){$GLOBALS["ARTICA_VERSION"]=trim(@file_get_contents(dirname(__FILE__)."/VERSION"));}
	
	$version=$GLOBALS["ARTICA_VERSION"];
	$FOOTER=null;
	$users=new usersMenus();
	$HOST=$ARRAY["HOST"];
	$URL=$ARRAY["URL"];
	$IPADDR=$ARRAY["IPADDR"];
	$REASONGIVEN=$ARRAY["REASONGIVEN"];
	$_CATEGORIES_K=$ARRAY["CATEGORY"];
	$_RULE_K=$ARRAY["RULE"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$SquidGuardIPWeb=base64_decode($_GET["SquidGuardIPWeb"]);
	$client_username=$ARRAY["clientname"];
	$hostname=$sock->GET_INFO("myhostname");

	$ARRAY["Proxy Server"]=$hostname;
	$sock->BuildTemplatesConfig($ARRAY);
	$EnableSquidGuardMicrosoftTPL=intval($sock->GET_INFO("EnableSquidGuardMicrosoftTPL"));
	$SquidHTTPTemplateSmiley=intval($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]);
	
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: {$GLOBALS["UfdbGuardHTTP"]["NoVersion"]}</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":SquidHTTPTemplateSmileyEnable: {$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"]} / {$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]}</li>";}
	if($GLOBALS["VERBOSE"]){echo "</div>";}
	
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"])){
		$SquidHTTPTemplateSmileyEnable=1;
	}else{
		$SquidHTTPTemplateSmileyEnable=$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"];
	}
	
	$BackgroundColorBLKBT=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"];
	
	if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
	
	if($IPADDR==null){
		$IPADDR=parseTemplate_GET_REMOTE_ADDR();
	}
	
	if($HOST==null){
		$HOST=$_SERVER["HTTP_HOST"];
	}
	
	if($URL==null){
		$proto="http";
		if(isset($_SERVER["HTTPS"])){
			if($_SERVER["HTTPS"]=="on"){
				$proto="https";
			}
		}
		$URL="$proto://$HOST{$_SERVER["REQUEST_URI"]}";
		
	}
	
	if($SquidGuardIPWeb==null){
		$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
		$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
		$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
		if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
		if(!preg_match("#\/\/(.+?):$SquidGuardApachePort#", $SquidGuardIPWeb)){
			if($SquidGuardServerName<>null){
				$SquidGuardIPWeb="http://$SquidGuardServerName:$SquidGuardApachePort";
			}
		}
		
	}
	
	if(strpos($SquidGuardIPWeb, $page)==0){
		if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb = $SquidGuardIPWeb require $page</H1>";}
		$SquidGuardIPWeb="$SquidGuardIPWeb/$page";
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "<H1>$SquidGuardIPWeb</H1>";}
	
	$UfdbGuardHTTPUnbblockMaxTime=intval($sock->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
	$UfdbGuardHTTPDisableHostname=intval($sock->GET_INFO("UfdbGuardHTTPDisableHostname"));
	$UfdbGuardHTTPUnbblockText2=$sock->GET_INFO("UfdbGuardHTTPUnbblockText2");
	$UfdbGuardHTTPEnablePostmaster=$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"];
	$UfdbGuardHTTPNoVersion=$GLOBALS["UfdbGuardHTTP"]["NoVersion"];
	$UfdbGuardHTTPAllowUnblock=$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"];
	
	if($UfdbGuardHTTPEnablePostmaster==1){
		$emailTemplate="URL:{$_GET["url"]}\nIP:{$_GET["clientaddr"]}\nREASON:$REASONGIVEN\nCategory:$_CATEGORIES_K\nrule:$_RULE_K";
		$Postmaster=parseadmin($emailTemplate,$URL);
	}
	
	$UfdbGuardHTTPAllowSMTP=intval($sock->GET_INFO("UfdbGuardHTTPAllowSMTP"));
	if($UfdbGuardHTTPAllowSMTP==1){
		$UfdbGuardHTTPEnablePostmaster=1;
		$Postmaster=parseTemplate_smtp_button($ARRAY,$SquidGuardIPWeb);
	}
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$UFDBGUARD_TITLE_1=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"];
	$UFDBGUARD_PARA1=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"];
	$UFDBGUARD_PARA2=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"];
	$UFDBGUARD_TITLE_2=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"];
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TICKET_LINK"];
	$UfdbGuardHTTPDisableHostname=$GLOBALS["UfdbGuardHTTP"]["UfdbGuardHTTPDisableHostname"];
	
	if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>UfdbGuardHTTPDisableHostname: $UfdbGuardHTTPDisableHostname</li>";}
	if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>UfdbGuardHTTPNoVersion: $UfdbGuardHTTPNoVersion</li>";}
	
	if($GLOBALS["VERBOSE"]){echo "</div>";}
	

	
	$f[]=parseTemplate_headers("$UFDBGUARD_TITLE_1 - $_CATEGORIES_K",null,$SquidGuardIPWeb);
	$f2[]=microsoft_ufdb_template("$UFDBGUARD_TITLE_1",null,$SquidGuardIPWeb);
	
	$f2[]="<p style='font-size:25px'>$REASONGIVEN</p>";
	
	
	if($SquidHTTPTemplateSmileyEnable==1){
		$f[]="    <h1 class=bad></h1>";
	}
	if(trim(strtolower($UFDBGUARD_TITLE_1))<>"none"){
		$f[]="    <h2>$UFDBGUARD_TITLE_1</h2>    ";
	}
	$f[]="    <h2>$REASONGIVEN</h2>    ";
	
	if(trim(strtolower($UFDBGUARD_PARA1))<>"none"){
		$f[]="    <p>$UFDBGUARD_PARA1</p>";
		$f2[]="    <p>$UFDBGUARD_PARA1</p>";
	}
	if(trim(strtolower($UFDBGUARD_TITLE_2))<>"none"){
		$f[]="    <h3>$UFDBGUARD_TITLE_2</h3>";
		$f2[]="    <p style='font-size:25px'>$UFDBGUARD_TITLE_2</p>";
	}
	if(trim(strtolower($UFDBGUARD_PARA2))<>"none"){
		$f[]="    <p>$UFDBGUARD_PARA2</p>    ";
		$f2[]="    <p>$UFDBGUARD_PARA2</p>";
	}
	$f[]="    ";
	$f[]="    <div id=\"info\">";
	$f[]="    <table width='100%'>";
	
	if($client_username<>null){
		$members[]=$client_username;
		
	}
	
	
	$members[]=$IPADDR;
	if($HOST<>null){
		$members[]=$HOST;
	}
	
	if(trim($IpToUid)<>null){
		$members[]=$IpToUid;
	}

	if(count($members)>0){
		while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
		$members=array();
		while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}
		
	}
	
	$membersTX=@implode(", ", $members);
	$f2[]="<UL class=\"tasks\" id=\"cantDisplayTasks\">";
	if($UfdbGuardHTTPDisableHostname==0){
		$hostname=$sock->GET_INFO("myhostname");
		if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
		$f[]="        <tr><td class=\"info_title\">{proxy_server}:</td><td class=\"info_content\">$hostname</td></tr>";
		$f2[]="<li><strong>{proxy_server}</strong>: $hostname</li>";
	}	
	
	if($GLOBALS["VERBOSE"]){echo "<span style='font-size:16px'>UfdbGuardHTTPEnablePostmaster:$UfdbGuardHTTPEnablePostmaster</span><br>\n";}
	
	if($UfdbGuardHTTPEnablePostmaster==1){
		$f[]="        <tr><td class=\"info_title\">{administrator}:</td><td class=\"info_content\">$Postmaster</td></tr>";
		$f2[]="<li><strong>{administrator}</strong>: $Postmaster</li>";
	}
	if($UfdbGuardHTTPNoVersion==0){
		$f2[]="<li><strong>{application}</strong>: Version $version</li>";
		$f[]="        <tr><td class=\"info_title\">{application}:</td><td class=\"info_content\">Version $version</td></tr>";
	}
	
	
	if($targetgroup=="restricted_time"){$targetgroup="{restricted_access}";}
	$f2[]="<li><strong>{member}</strong>: $membersTX</li>";
	$f2[]="<li><strong>{policy}</strong>: $_RULE_K, $targetgroup</li>";
	$f2[]="<li><strong>{requested_uri}</strong>: $URL</li>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>";
	
	$NOUNBLOCK=false;
	if(isset($_GET["fatalerror"])){$NOUNBLOCK=true;}
	if(isset($_GET["loading-database"])){$NOUNBLOCK=true;}
	$AllowTicket=0;
	
	$q=new mysql_squid_builder();
	$CountOfufdb_page_rules=$q->COUNT_ROWS("ufdb_page_rules");
	parseTemplate_debug("ufdb_page_rules: $CountOfufdb_page_rules", __LINE__);
	
	
	if($CountOfufdb_page_rules>0){
		include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
		$unlock=new parse_template_ufdb();
		
		if($GLOBALS["VERBOSE"]){echo "<hr style='border-color:#35CA61'>\n";}
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}
		$UfdbGuardHTTPAllowUnblock=$unlock->parseTemplate_unlock_privs($ARRAY,"allow=1",$UfdbGuardHTTPAllowUnblock);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>allow: UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}
		$UfdbGuardHTTPAllowUnblock=$unlock->parseTemplate_unlock_privs($ARRAY,"deny=1",$UfdbGuardHTTPAllowUnblock);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:#35CA61'>Deny: UfdbGuardHTTPAllowUnblock=$UfdbGuardHTTPAllowUnblock</span><br>\n";}
		
		$AllowTicket=$unlock->parseTemplate_unlock_privs($ARRAY,"ticket=1",0);
		if($AllowTicket==1){$UfdbGuardHTTPAllowUnblock=0;}
	}
	
	$f2[]="</ul>";
	
	if($UfdbGuardHTTPAllowUnblock==1){

		if(!$NOUNBLOCK){
			$URL_ENCODED=urlencode($URL);
			$IPADDR_ENCODE=urlencode($IPADDR);
			$page=CurrentPageName();
			$SquidGuardIPWeb_enc=urlencode($SquidGuardIPWeb);
			$unlock_web_site_text="{unlock_web_site}";
			if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}
			
			if(isset($GLOBALS["RULE_MAX_TIME"])){$ARRAY["RULE_MAX_TIME"]=$GLOBALS["RULE_MAX_TIME"];}
			
			$ARRAY_SERIALIZED=urlencode(base64_encode(serialize($ARRAY)));
			$unlock_text="<p>{$GLOBALS["UfdbGuardHTTP"]["UnbblockText1"]}</p>
			<div style='text-align:right;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};padding-top:5px'>
			<a href='$SquidGuardIPWeb?unlock=yes&url=$URL_ENCODED&ipaddr=$IPADDR_ENCODE&SquidGuardIPWeb=$SquidGuardIPWeb_enc&clientname={$ARRAY["clientame"]}&serialize=$ARRAY_SERIALIZED' class=important>
			$unlock_web_site_text</a></div>";
			
			$f[]=$unlock_text;
			$f2[]=$unlock_text;
		}
	}

	if($AllowTicket==1){
		$URL_ENCODED=urlencode($URL);
		$IPADDR_ENCODE=urlencode($IPADDR);
		$page=CurrentPageName();
		$SquidGuardIPWeb_enc=urlencode($SquidGuardIPWeb);
		$ticket_web_site_text="{submit_a_ticket}";
		if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}
		$ARRAY_SERIALIZED=urlencode(base64_encode(serialize($ARRAY)));
		$unlock_text="<p>{$GLOBALS["UfdbGuardHTTP"]["TICKET_TEXT"]}</p>
		<div style='text-align:right;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};padding-top:5px'>
		<a href='$SquidGuardIPWeb?ticket=yes&url=$URL_ENCODED&ipaddr=$IPADDR_ENCODE&SquidGuardIPWeb=$SquidGuardIPWeb_enc&clientname={$ARRAY["clientame"]}&serialize=$ARRAY_SERIALIZED' class=important>
		$ticket_web_site_text</a></div>";
		$f[]=$unlock_text;
		$f2[]=$unlock_text;
	}
	
	$f2[]="$FOOTER</DIV>";
	$f2[]="</DIV>";
	$f2[]="</BODY>";
	$f2[]="</HTML>";
	
	if(!isset($_SESSION["UFDB_PAGE_LANG"])){
		if(!class_exists("articaLang")){include_once(dirname(__FILE__)."/ressources/class.langages.inc");}
		$langAutodetect=new articaLang();
		$_SESSION["UFDB_PAGE_LANG"]=$langAutodetect->get_languages();
	
	}
	

	$tpl=new templates();
	
	$tpl->language=$_SESSION["UFDB_PAGE_LANG"];
	
	if($EnableSquidGuardMicrosoftTPL==1){
		return $tpl->_ENGINE_parse_body(@implode("\n", $f2));
	
	}
	
	
	
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	while (list ($num, $ligne) = each ($ARRAY) ){
		$f[]="    $num = $ligne";
	}

	$f[]=" Language : $tpl->language";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	
	return $tpl->_ENGINE_parse_body(@implode("\n", $f));
}

function wifidog_build_uri(){
	reset($_REQUEST);
	while (list ($num, $ligne) = each ($_REQUEST) ){
		if($num=="unlock-www"){continue;}
		if($num=="unlock"){continue;}
		
		$URIZ[]="$num=".urlencode($ligne);
		$inputz[]="<input type='hidden' id='$num' name='$num' value='$ligne'>";

	}

	return array(@implode("&", $URIZ),@implode("\n", $inputz));

}
function parseTemplate_unlock_checkcred(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
	include("ressources/settings.inc");
	$sock=new sockets();
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));
	if($UfdbGuardHTTPAllowNoCreds==1){return true;}
	if($_POST["nocreds"]==1){return true;}
	$username=$_POST["username"];
	$password=trim($_POST["password"]);
	
	
	if($sock->SQUID_IS_EXTERNAL_LDAP()){
		$ldap_extern=new ldap_extern();
		if($ldap_extern->checkcredentials($username, $password)){return true;}
		
		
	}
	
	
	
	
	if(trim(strtolower($username))==trim(strtolower($_GLOBAL["ldap_admin"]))){
		if($password==trim($_GLOBAL["ldap_password"])){return true;}
	}
	
	$ldap=new clladp();
	if($ldap->IsKerbAuth()){
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($username,$password)){
			return true;
		}
	}
	
	
	
	$q=new mysql();
	$sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$username' AND `attribute`='Cleartext-Password' LIMIT 0,1";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	if(!$q->ok){writelogs("$username:: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
	if($ligne["id"]>0){
		if($ligne["value"]==$password){return true; }
	}		
	
	
	$u=new user($username);
	if(trim($u->uidNumber)<>null){
		if(trim($password)==trim($u->password)){return true; }
	}	
	
	
	return false;
	
	
	
}

function parseTemplate_ticket_save(){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");	
	
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$tpl=new templates();

	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$finalhost=$_POST["finalhost"];
	$IPADDR=$_REQUEST["ipaddr"];
	$user=$_REQUEST["username"];
	$SquidGuardIPWeb=$_REQUEST["SquidGuardIPWeb"];
	$familysite=$q->GetFamilySites($finalhost);
	
	$_RULE_K=$ARRAY["RULE"];
	$IPADDR=$ARRAY["IPADDR"];
	$targetgroup=$ARRAY["targetgroup"];
	$IpToUid=$ARRAY["IpToUid"];
	$URL=$ARRAY["URL"];
	$HOST=$ARRAY["HOST"];
	
	$members[]=$IPADDR;
	if($HOST<>null){$members[]=$HOST; }
	if(trim($IpToUid)<>null){$members[]=$IpToUid;}
	if(count($members)>0){while (list ($num, $ligne) = each ($members) ){$AAAA[$ligne]=true;}
	$members=array();
	while (list ($num, $ligne) = each ($AAAA) ){$members[]=$num;}}
	$membersTX=@implode(", ", $members);
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");
	
	$ticket_web_site_text="{submit_a_ticket}";
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$TICKET_TEXT_SUCCESS=$GLOBALS["UfdbGuardHTTP"]["TICKET_TEXT_SUCCESS"];
	if($TICKET_TEXT_SUCCESS==null){$TICKET_TEXT_SUCCESS="{ufdb_ticket_text_success}";}
	
	if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}
	
	$cssform="  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
  background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  background: rgb(255,255,255); /* Old browsers */
  background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(47%,rgba(246,246,246,1)), color-stop(100%,rgba(237,237,237,1))); /* Chrome,Safari4+ */
  background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Chrome10+,Safari5.1+ */
  background: -o-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* Opera 11.10+ */
  background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* IE10+ */
  background: linear-gradient(to bottom, rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(237,237,237,1) 100%); /* W3C */
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 ); /* IE6-9 */
						
  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);";	
	
	
	$MAX=intval($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]);
	$url=$_REQUEST["url"];
	$md5=md5($finalhost.$IPADDR.$user);
	$q->QUERY_SQL("INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) VALUES ('$md5','$IPADDR','$familysite','$user')");
	$function=__FUNCTION__;
	$file=basename(__FILE__);
	$line=__LINE__;
	$subject="Unlock website ticket $finalhost/$familysite from $user/$IPADDR";
	
	
	
	
	
	
	
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
			(NOW(),'','$subject','$function','$file','$line','1','{$_SERVER["SERVER_NAME"]}')","artica_events");
	if(!$q->ok){
		$redirect=null;
		$MAIN_BODY="<center style='margin:20px;padding:20px;$cssform;color:black;width:80%'>
		<H1>Oups!</H1><hr>".$q->mysql_error_html()."</center>";
	
	}
	$error=parseTemplate_sendemail_perform(null,$ARRAY,true,$SquidGuardIPWeb);
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	
	$notify_your_administrator=$tpl->_ENGINE_parse_body("{notify_your_administrator}");
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);
	
	
	$f[]=parseTemplate_headers($ticket_web_site_text,null,$SquidGuardIPWeb);
	$f[]="    <h2>{notify_your_administrator}</h2>";
	if($error<>null){$f[]="    <h2>$error</h2>";}
	$f[]="<h3>$TICKET_TEXT_SUCCESS</h3>";
	$f[]="<div id=\"info\" style='margin-top:20px'>";
	
	$f[]="<form id='send-email-form' action=\"$SquidGuardIPWeb\" method=\"post\">";
	$f[]="<table width='100%;'>";
	$f[]="        <tr><td class=\"info_title\">{member}:</td><td class=\"info_content\">$membersTX</td></tr>";
	$f[]="        <tr><td class=\"info_title\">{policy}:</td><td class=\"info_content\">$_RULE_K, $targetgroup</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{requested_uri}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">$URL</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	$f[]="    </table>
	<p style='margin-top:50px'>&nbsp;</p>";
	$f[]="
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['send-email-form'].submit();
		}
	
	</script>
	";
	
	
	$f[]="</div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
		
	
	
	
}


function parseTemplate_release_ticket(){

	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));

	parseTemplate_unlock_save(true,$ARRAY,true);
		
}



function parseTemplate_unlock_save($noauth=false,$ARRAYCMD=array(),$noredirect=false){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	$tpl=new templates();
	$cssform="  -moz-border-radius: 5px;
  border-radius: 5px;
  border:1px solid #DDDDDD;
  background:url(\"/img/gr-greybox.gif\") repeat-x scroll 0 0 #FBFBFA;
  background:-moz-linear-gradient(center top , #F1F1F1 0px, #FFFFFF 45px) repeat scroll 0 0 transparent;
  margin:5px;padding:5px;
  -webkit-border-radius: 5px;
  -o-border-radius: 5px;
 -moz-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 -webkit-box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
 box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);";	
	
	if(!$noauth){
		if(!parseTemplate_unlock_checkcred()){
			parseTemplate_unlock("{wrong_password_or_username}");
			die();
		}
	}
	
	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock=new sockets();
	$sock->BuildTemplatesConfig($ARRAY);
	$q=new mysql_squid_builder();
	$finalhost=$_POST["finalhost"];
	$IPADDR=$_REQUEST["ipaddr"];
	$user=$_REQUEST["username"];
	$url=$_REQUEST["url"];
	$SquidGuardIPWeb=$_REQUEST["SquidGuardIPWeb"];
	
	if(count($ARRAYCMD)>3){
		$IPADDR=$ARRAY["IPADDR"];
		$user=$ARRAY["clientname"];
		$url=$ARRAY["URL"];
		$H=parse_url($url);
		$finalhost=$H["host"];
		
	}
	
	
	$MAX=intval($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]);
	
	
	
	if(isset($ARRAY["RULE_MAX_TIME"])){
		if(intval($ARRAY["RULE_MAX_TIME"])>0){
			$MAX=$ARRAY["RULE_MAX_TIME"];
		}
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ufdbunlock` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`uid` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR( 90 ) NULL,
			`www` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			KEY `MAC` (`MAC`),
			KEY `logintime` (`logintime`),
			KEY `finaltime` (`finaltime`),
			KEY `uid` (`uid`),
			KEY `www` (`www`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){parseTemplate_unlock($q->mysql_error);return;}
	
	
	if($MAX==0){$MAX=60;}
	
	
	
	$familysite=$q->GetFamilySites($finalhost);
	include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
	$unlock=new parse_template_ufdb();
	$addTocategory=$unlock->parseTemplate_unlock_privs($ARRAY,"addTocat=1",null);
	
	if(!isset($ARRAY["RULE_MAX_TIME"])){
		if(isset($GLOBALS["RULE_MAX_TIME"])){
			if(intval($GLOBALS["RULE_MAX_TIME"])>0){
				$MAX=$GLOBALS["RULE_MAX_TIME"];
			}
		}
	}
	
	$md5=md5($finalhost.$IPADDR.$user);
	$time=time();
	$EnOfLife = strtotime("+{$MAX} minutes", $time);
	
	$NextLogs=$EnOfLife-$time;
	writelogs("$finalhost $IPADDR $user Alowed for {$MAX} minutes, EndofLife=$EnOfLife in {$NextLogs} seconds",__FUNCTION__,__FILE__,__LINE__);
	
	$q->QUERY_SQL("INSERT IGNORE INTO `ufdbunlock` (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`)
			VALUES('$md5','$time','$EnOfLife','$user','$familysite','$IPADDR')");
	if(!$q->ok){
		writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		parseTemplate_unlock($q->mysql_error);
		return;
	}
	
	
	
	
	if($addTocategory<>null){
		writelogs("Saving $familysite  into $addTocategory",__FUNCTION__,__FILE__,__LINE__);
		$q->ADD_CATEGORYZED_WEBSITE($familysite, $addTocategory);
	}
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) 
			VALUES ('$md5','$IPADDR','$familysite','$user')");
	$function=__FUNCTION__;
	$file=basename(__FILE__);
	$line=__LINE__;
	$subject="Unlocked website $finalhost/$familysite from $user/$IPADDR";
	$redirect="<META http-equiv=\"refresh\" content=\"10; URL=$url?ufdbtime=".time()."\">";
	
	$redirecting_text=$tpl->javascript_parse_text("{redirecting}");
	
	$redirect_text="{please_wait_redirecting_to}<br>$url<br><{for} $MAX {minutes}";
	
	if($noredirect==true){
		$redirect=null;
		$redirect_text="{unlock}<br>$url<br><{for} $MAX {minutes}";
		$redirecting_text=$tpl->javascript_parse_text("{done}");
	}
	
	$MAIN_BODY="<center>
	<div id='maincountdown' style='width:100%'>
	<center style='margin:20px;padding:20px;$cssform;color:black;width:80%' >
		<input type='hidden' id='countdownvalue' value='10'>
		<span id='countdown' style='font-size:70px'></span>
	</center>
	</div>
	<p style='font-size:22px'>
			<center style='margin:50px;$cssform;color:black;width:80%'>
				$redirect_text
				<center style='margin:20px;font-size:70px' id='wait_verybig_mini_red'>
					<img src='img/wait_verybig_mini_red.gif'>
				</center>
			</center>
	</p> 
	</center>
	<script>

	
 
setInterval(function () {
	var countdown = document.getElementById('countdownvalue').value
	countdown=countdown-1;
	if(countdown==0){
		document.getElementById('countdownvalue').value=0;
		document.getElementById('wait_verybig_mini_red').innerHTML='$redirecting_text';
		document.getElementById('maincountdown').innerHTML='';
		
		return;
	}
	document.getElementById('countdownvalue').value=countdown;
	document.getElementById('countdown').innerHTML=countdown
 
}, 1000);
</script>";
	
	
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
			(NOW(),'','$subject','$function','$file','$line','1','{$_SERVER["SERVER_NAME"]}')","artica_events");
	if(!$q->ok){
		$redirect=null;
		$MAIN_BODY="<center style='margin:20px;padding:20px;$cssform;color:black;width:80%'>
		<H1>Oups!</H1><hr>".$q->mysql_error_html()."</center>";
		
	}
	

	
	if($redirect<>null){
		$sock=new sockets();
		if($GLOBALS["VERBOSE"]){echo "<H1 style='color:white'>squid.php?reconfigure-unlock=yes</H1>";}
		$sock->getFrameWork("squid.php?reconfigure-unlock=yes");
	}
	if($noredirect){
		$sock=new sockets();
		if($GLOBALS["VERBOSE"]){echo "<H1 style='color:white'>squid.php?reconfigure-unlock=yes</H1>";}
		$sock->getFrameWork("squid.php?reconfigure-unlock=yes");
	}
	
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}
	
	$f[]=parseTemplate_headers($unlock_web_site_text,$redirect);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$f[]=$MAIN_BODY;
	$f[]=$FOOTER;
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));	
	
}

function parseTemplate_ticket($error=null){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");	
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$SquidGuardIPWeb=null;
	$url=$_REQUEST["url"];
	$IPADDR=$_REQUEST["ipaddr"];
	if(isset($_GET["SquidGuardIPWeb"])){$SquidGuardIPWeb=$_GET["SquidGuardIPWeb"];}
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb=CurrentPageName();}
	if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb=$SquidGuardIPWeb</H1>";}
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));
	
	$q=new mysql_squid_builder();
	$parse_url=parse_url($url);
	$host=$parse_url["host"];
	if(preg_match("#(.+?):[0-9]+#", $host,$re)){$host=$re[1];}
	$FinalHost=$q->GetFamilySites($host);
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];	
	
	$ticket_web_site_text="{submit_a_ticket}";
	$UFDBGUARD_TICKET_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	if($UFDBGUARD_TICKET_LINK<>null){$ticket_web_site_text=$UFDBGUARD_TICKET_LINK;}
	
	$f[]=parseTemplate_headers("$UFDBGUARD_TICKET_LINK",null,$SquidGuardIPWeb);
	$f[]=$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
	<input type='hidden' id='unlock-ticket' name='unlock-ticket' value='yes'>
	<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
	<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
	<input type='hidden' id='SquidGuardIPWeb' name='SquidGuardIPWeb' value='$SquidGuardIPWeb'>
	<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
	<input type='hidden' id='url' name='url' value='$url'>";
	$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
	$f[]="<script>	";
	$f[]="function CheckTheForm(){	";
	$f[]="document.forms['unlockform'].submit();";
	$f[]="}	";
	$f[]="CheckTheForm();";
	$f[]="</script>	";
	$f[]="</body>";
	$f[]="</html>";
	echo @implode("\n", $f);
	
	
}


function parseTemplate_unlock($error=null){
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($_REQUEST["serialize"]));
	$sock->BuildTemplatesConfig($ARRAY);
	$SquidGuardIPWeb=null;
	$url=$_REQUEST["url"];
	$IPADDR=$_REQUEST["ipaddr"];
	if(isset($_GET["SquidGuardIPWeb"])){$SquidGuardIPWeb=$_GET["SquidGuardIPWeb"];}
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb=CurrentPageName();}
	if($GLOBALS["VERBOSE"]){echo "<H1>SquidGuardIPWeb=$SquidGuardIPWeb</H1>";}
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));
	
	$q=new mysql_squid_builder();
	$parse_url=parse_url($url);
	$host=$parse_url["host"];
	if(preg_match("#(.+?):[0-9]+#", $host,$re)){$host=$re[1];}
	$FinalHost=$q->GetFamilySites($host);
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["FOOTER"])){$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;}
	$FOOTER=$GLOBALS["UfdbGuardHTTP"]["FOOTER"];
	$MAX=$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"];
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[240]="4 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[2880]="2 {days}";
	$TEXT_TIME=$Timez[$MAX];
	$UnbblockText2=$GLOBALS["UfdbGuardHTTP"]["UnbblockText2"];
	$page=CurrentPageName();
	$UnbblockText2=str_replace("%WEBSITE%", $url, $UnbblockText2);
	$UnbblockText2=str_replace("%TIME%", $TEXT_TIME, $UnbblockText2);
	$fontfamily="font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$fontfamily=str_replace('"', "", $fontfamily);
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	
	
	$client_username=$ARRAY["clientname"];
	if($client_username<>null){$_REQUEST["clientname"]=$client_username;}
	
	if($q->COUNT_ROWS("ufdb_page_rules")>0){
		include_once(dirname(__FILE__)."/ressources/class.ufdb.parsetemplate.inc");
		$unlock=new parse_template_ufdb();
		$noauth=$unlock->parseTemplate_unlock_privs($ARRAY,$pattern="noauth=1",0,true);
		$UfdbGuardHTTPAllowNoCreds=$noauth;
	}
	
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}
	
	if($noauth==1){
		$f[]=parseTemplate_headers("$unlock_web_site_text",null,$SquidGuardIPWeb);
		$f[]=$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
		<input type='hidden' id='unlock-www' name='unlock-www' value='yes'>
		<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
		<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
		<input type='hidden' id='SquidGuardIPWeb' name='SquidGuardIPWeb' value='$SquidGuardIPWeb'>
		<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
		<input type='hidden' id='url' name='url' value='$url'>";
		$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";		
		$f[]="<input type='hidden' id='nocreds' name='nocreds' value='1'>";
		$f[]="<script>	";	
		$f[]="function CheckTheForm(){	";	
		$f[]="document.forms['unlockform'].submit();";	
		$f[]="}	";	
		$f[]="CheckTheForm();";		
		$f[]="</script>	";			
		$f[]="</body>";
		$f[]="</html>";
		echo @implode("\n", $f);
		return;
	}
	$UFDBGUARD_UNLOCK_LINK=$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"];
	$unlock_web_site_text="{unlock_web_site}";
	if($UFDBGUARD_UNLOCK_LINK<>null){$unlock_web_site_text=$UFDBGUARD_UNLOCK_LINK;}
	
	$f[]=parseTemplate_headers($unlock_web_site_text,null,$SquidGuardIPWeb);
	$f[]="    <h2>$unlock_web_site_text $FinalHost {for} $IPADDR {$_REQUEST["clientname"]}</h2>";
	if($error<>null){
		$f[]="    <h2>$error</h2>";
	}
	$f[]="    <div id=\"info\">";
	$f[]="<p style='margin-bottom:30px'>$UnbblockText2</p>";
	$f[]="<form id='unlockform' action=\"$SquidGuardIPWeb\" method=\"post\">
	<input type='hidden' id='unlock-www' name='unlock-www' value='yes'>
	<input type='hidden' id='finalhost' name='finalhost' value='$FinalHost'>
	<input type='hidden' id='ipaddr' name='ipaddr' value='$IPADDR'>
	<input type='hidden' id='serialize' name='serialize' value='{$_REQUEST["serialize"]}'>
	<input type='hidden' id='url' name='url' value='$url'>";
	$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
	
	if($UfdbGuardHTTPAllowNoCreds==1){
		$f[]="<input type='hidden' id='username' name='username' value='{$_REQUEST["clientname"]}'>";
		$f[]="<input type='hidden' id='password' name='password' value='{$_REQUEST["password"]}'>";
		
	}
	
	
	$f[]="<table width='100%;'>";
	if($UfdbGuardHTTPAllowNoCreds==0){
	$f[]=" <tr>
				<td class=\"info_title\">{username}:</td>
				<td class=\"info_content\">".Field_text("username",$_REQUEST["username"],"$fontfamily;width:80%;font-size:35px;padding:5px")."</td>
			</tr> 
			<tr>
				<td class=\"info_title\">{password}:</td>
				<td class=\"info_content\">".Field_password(
						"nolock:password",$_REQUEST["password"],"$fontfamily;width:80%;font-size:35px;padding:5px",
						null,null,null,false,"CheckTheForm(event)")."</td>
			</tr>";
	}
	$f[]=" <tr><td colspan=2 align='right'><hr>". button("{submit}","document.forms['unlockform'].submit();")."</td></tr>
	</table>
	</form>
	<script>
	function CheckTheForm(e){
		if(!checkEnter(e)){return;}
		document.forms['unlockform'].submit();
		}
			
	</script>		
	";
	
	
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="</html>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}



function ini_set_verbosedx(){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
}
?>