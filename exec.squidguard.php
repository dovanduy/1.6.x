<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	if(isset($_GET["SquidGuardWebAllowUnblockSinglePass"])){parseTemplate_SinglePassWord();die();}
	
	if(isset($_POST["USERNAME"])){parseTemplate_LocalDB_receive();die();}
	if(isset($_POST["password"])){parseTemplate_SinglePassWord_receive();die();}
	if(isset($_GET["parseTemplate-SinglePassWord-popup"])){parseTemplate_SinglePassWord_popup();die();}
	if(isset($_GET["SquidGuardWebUseLocalDatabase"])){parseTemplate_LocalDB();die();}
	parseTemplate();die();}

if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);


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




if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(count($argv)>0){
	$imploded=implode(" ",$argv);
	if(preg_match("#--verbose#",$imploded)){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set_verbosed(); }
	if(preg_match("#--reload#",$imploded)){$GLOBALS["RELOAD"]=true;}
	if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--shalla#",$imploded)){$GLOBALS["SHALLA"]=true;}
	if(preg_match("#--catto=(.+?)\s+#",$imploded,$re)){$GLOBALS["CATTO"]=$re[1];}
	
	
	
	
	$argvs=$argv;
	unset($argvs[0]);
	
	
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
	
	
	$GLOBALS["EXECUTEDCMDLINE"]=@implode(" ", $argvs);
	ufdbguard_admin_events("receive ".$GLOBALS["EXECUTEDCMDLINE"],"MAIN",__FILE__,__LINE__,"config");
	if($GLOBALS["VERBOSE"]){echo "Execute ".@implode(" ", $argv)."\n";}
	
	if($argv[1]=="--inject"){echo inject($argv[2],$argv[3]);exit;}
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
	
	if($argvs[1]=='--build-ufdb-smoothly'){echo build_ufdbguard_smooth();exit;}
	
	
	
}
	


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);die();}
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


function build_ufdbguard_smooth(){
	$users=new usersMenus();
	if(!$users->APP_UFDBGUARD_INSTALLED){echo "Starting......: ufdbGuard is not installed, aborting\n";return;}
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){echo "Starting......: It use Statistics appliance, aborting\n";return;}
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build_ufdbguard_smooth() -> reconfigure UfdbGuardd", basename(__FILE__));}
	build_ufdbguard_config();
	build_ufdbguard_HUP();
}


function build_ufdbguard_HUP(){
	$unix=new unix();
	$sock=new sockets();$forceTXT=null;
	$ufdbguardReloadTTL=$sock->GET_INFO("ufdbguardReloadTTL");
	if(!is_numeric($ufdbguardReloadTTL)){$ufdbguardReloadTTL=10;}
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$TIMEZ=$unix->file_time_min($timeFile);
	if(!$GLOBALS["FORCE"]){
		if($TIMEZ<$ufdbguardReloadTTL){
			ufdbguard_admin_events("Asking to reload ufdbguard but TTL not reached ({$ufdbguardReloadTTL}mn) current {$TIMEZ}mn",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
			echo "Starting......: ufdbGuard reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn PID:$pid\n";
			return;
		}
	}else{
		$forceTXT=" with option FORCE enabled";
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$unix->find_program("squid3");}
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(strlen($ufdbguardd)<5){WriteToSyslogMail("ufdbguardd no such binary", basename(__FILE__));return;}
	$kill=$unix->find_program("kill");
	$pid=$unix->PIDOF($ufdbguardd);
	if($unix->process_exists($pid)){
		echo "Starting......: ufdbGuard reloading service PID:$pid\n";
		WriteToSyslogMail("Asking to reload ufdbguard PID:$pid",basename(__FILE__));
		$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
		ufdbguard_admin_events("Asking to reload ufdbguard$forceTXT - $called - cmdline:{$GLOBALS["EXECUTEDCMDLINE"]}",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		posix_kill(intval($pid),1);
		
		if(is_file($squidbin)){shell_exec("$squidbin -k reconfigure >/dev/null 2>&1");}
		return;
	}
	echo "Starting......: UfdbGuard reloading service no pid is found, Starting service...\n";
	ufdbguard_start();
	
	
	
}

function ufdbguard_start(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Starting......: UfdGuard Starting service aborted, task pid already running $pid\n";
		writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$master_pid=trim(@file_get_contents($pid_path));

	if(!$unix->process_exists($master_pid)){
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon seems to not running, trying with pidof", basename(__FILE__));}
		$master_pid=$unix->PIDOF($ufdbguardd_path);
		if($unix->process_exists($master_pid)){
			echo "Starting......: UfdGuard master is running, updating PID file with $master_pid\n";
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master is running, updating PID file with $master_pid", basename(__FILE__));}
			@file_put_contents($pid_path,$master_pid);	
			return;
		}
	}
	ufdbguard_admin_events("Asking to start ufdbguard",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");	
	echo "Starting......: Starting UfdGuard master service...\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting UfdGuard master service...", basename(__FILE__));}
	@mkdir("/var/log/ufdbguard",0755,true);
	@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "#");
	@chown("/var/log/ufdbguard/ufdbguardd.log", "squid");
	@chgrp("/var/log/ufdbguard/ufdbguardd.log", "squid");	
	
	
	system("/etc/init.d/ufdb start");
	sleep(5);
	
	echo "Starting......: Starting UfdGuard master init.d ufdb done...\n";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$unix->process_exists($master_pid)){
		echo "Starting......: Starting UfdGuard master service failed...\n";
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting UfdGuard master service failed...", basename(__FILE__));}
	}else{
		echo "Starting......: Starting UfdGuard master success pid $master_pid...\n";
	}
	
}


function build_ufdbguard_config(){
	$sock=new sockets();
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

	$ufdb=new compile_ufdbguard();
	$datas=$ufdb->buildConfig();	
	
	if($EnableWebProxyStatsAppliance==1){
		@file_put_contents("/usr/share/artica-postfix/ressources/databases/ufdbGuard.conf",$datas);
	}
	@mkdir("/var/tmp",0775,true);
	@mkdir("/etc/ufdbguard",0777,true);
	@mkdir("/etc/squid3",0755,true);
	@unlink("/etc/ufdbguard/ufdbGuard.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");	
	@file_put_contents("/etc/ufdbguard/ufdbGuard.conf",$datas);
	@file_put_contents("/etc/squid3/ufdbGuard.conf",$datas);
	$sock->TOP_NOTIFY("{webfiltering_parameters_was_saved}");
	shell_exec("$chmod 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/ufdbguard");	
	
	shell_exec("chown -R squid:squid /etc/ufdbguard");
	shell_exec("chown -R squid:squid /etc/squid3");
	
	
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




function build(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	send_email_events("Order to rebuild filters configuration",@implode("\nParams:",$argv),"proxy");
	$funtion=__FUNCTION__;
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
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance\n";}
	
	$GLOBALS["SQUIDBIN"]=$squidbin;	
	if($EnableWebProxyStatsAppliance==0){
		$installed=false;
		if($users->SQUIDGUARD_INSTALLED){$installed=true;echo "Starting......: SquidGuard is installed\n";}
		if($users->APP_UFDBGUARD_INSTALLED){$installed=true;echo "Starting......: UfdBguard is installed\n";}
		if($users->DANSGUARDIAN_INSTALLED){$installed=true;echo "Starting......: Dansguardian is installed\n";}
		if(!$installed){if($GLOBALS["VERBOSE"]){echo "No one installed...\n";
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		return false;}}
		
	}
	
	FIX_1_CATEGORY_CHECKED();
	
	if($EnableRemoteStatisticsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance to get configuration file...\n";}
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		ufdbguard_remote();
		return;
	}		

	$dans=new compile_dansguardian();
	$dans->build();
	echo "Starting......: Dansguardian compile done...\n";	
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build() -> reconfigure UfdbGuardd", basename(__FILE__));}
	build_ufdbguard_config();
	ufdbguard_schedule();
	
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}
		
	CheckPermissions();
	ufdbguard_admin_events("Service will be rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
	shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
	
	if(is_file("/etc/init.d/ufdb")){
		echo "Starting......: Checking watchdog\n";
		ufdbguard_watchdog();
		echo "Starting......: ufdbGuard reloading service\n";
		build_ufdbguard_HUP();
	}
	
	if($users->DANSGUARDIAN_INSTALLED){
		echo "Starting......: Dansguardian reloading service\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
	}
	
	if(is_file($GLOBALS["SQUIDBIN"])){
		if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build() -> Reloading Squid service", basename(__FILE__));}
		echo "Starting......: Squid reloading service\n";
		shell_exec("$nohup $php5 ". basename(__FILE__)."/exec.squid.php --reconfigure-squid >/dev/null 2>&1");
	}
	
	send_email_events("SquidGuard/ufdbGuard/Dansguardian/Squid rules was rebuilded","","system");

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
	$unix=new unix();
	$monitbin=$unix->find_program("monit");
	$nohup=$unix->find_program("nohup");	
	$monit_file="/etc/monit/conf.d/ufdb.monitrc";
	$files[]="/usr/sbin/ufdb-monit-start";
	$files[]="/usr/sbin/ufdb-monit-stop";
	$files[]=$monit_file;
	$configured=false;
	while (list ($a, $b) = each ($files)){
		if(is_file($b)){
			@unlink($b);
			$configured=true;
		}
	}	
	
	if($configured){shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --monit-check >/dev/null 2>&1 &");}
}


function ufdbguard_watchdog(){
	$unix=new unix();
	$monitbin=$unix->find_program("monit");
	$nohup=$unix->find_program("nohup");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");		
	if(!is_file($monitbin)){return;}
	if(!is_file("/etc/init.d/ufdb")){ufdbguard_watchdog_remove();return;}
	$sock=new sockets();
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	if($EnableUfdbGuard==0){ufdbguard_watchdog_remove();return;}
	
	
	$monit_file="/etc/monit/conf.d/ufdb.monitrc";
	$files[]="/usr/sbin/ufdb-monit-start";
	$files[]="/usr/sbin/ufdb-monit-stop";
	$files[]=$monit_file;
	$configured=true;
	while (list ($a, $b) = each ($files)){
		if(!is_file($b)){$configured=false;}
	}
	
	if(!$GLOBALS["RELOAD"]){
		if($configured){return;}
	}	
	
		$pidfile="/var/tmp/ufdbguardd.pid";
		echo "Starting......: ufdbguard Monit is enabled check pid `$pidfile`\n";
		$f[]="check process ufdb";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"/usr/sbin/ufdb-monit-start\"";
   		$f[]="stop program =  \"/usr/sbin/ufdb-monit-stop\"";
		$f[]="if totalmem > 700 MB for 5 cycles then alert";
   		$f[]="if cpu > 95% for 5 cycles then alert";
   		$f[]="if 5 restarts within 5 cycles then timeout";
 	    @file_put_contents($monit_file, @implode("\n", $f));	

 	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --ufdbguard-start";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/ufdb-monit-start", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/ufdb-monit-start");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/ufdb stop";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/ufdb-monit-stop", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/ufdb-monit-stop");	    	    
	   shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --monit-check >/dev/null 2>&1 &");
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
	shell_exec("$chown $user /var/lib/squidguard");
	shell_exec("$chown $user /var/lib/ftpunivtlse1fr");
	shell_exec("$chown -R $user /var/lib/squidguard/");
	shell_exec("$chown -R $user /etc/dansguardian");
	shell_exec("$chown -R $user /var/log/squid");
	shell_exec("$chown -R $user /var/log/squid/");
	shell_exec("$chown -R $user /etc/ufdbguard");
	shell_exec("$chown -R $user /etc/ufdbguard/*");
	shell_exec("$chown -R $user /var/lib/ftpunivtlse1fr/");
	shell_exec("$chmod -R ug+x /var/lib/squidguard/");
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chown -R $user /var/lib/ufdbartica");
	shell_exec("$chown -R $user /var/lib/ufdbartica/*");		
	shell_exec("$chown -R $user /var/log/squid/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/ufdbartica/*");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard/*");	
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
	$oldpid=@file_get_contents($pidpath);
	if($unix->process_exists($oldpid)){
		events_ufdb_tail("Check \"$path\"... Already process PID \"$oldpid\" running task has been aborted");
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
		@mkdir(dirname($domain_path),755,true);
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
			echo "Starting......: squidGuard kill $pids PIDs\n";
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
			echo "Starting......: squidGuard compiling ". count($array)." databases\n";
			$file=str_replace(".db",'',$file);
			$textfile=str_replace("/var/lib/squidguard/","",$file);
			echo "Starting......: squidGuard compiling $textfile database ".($index+1) ."/". count($array)."\n";
			if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C $file\n";}
			system($users->SQUIDGUARD_BIN_PATH." -P$verb -C $file");
		}
	}else{
		echo "Starting......: squidGuard compiling all databases\n";
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
	
	$array=parse_url($uri);
	
	
	if(count($array)==0){return false;}
	if(!isset($array["path"])){return false;}
	$path_parts = pathinfo($array["path"]);
	$ext=$path_parts['extension'];
	writelogs("$uri: $ext",__FUNCTION__,__FILE__,__LINE__);
	$filename=$path_parts['basename'];
	$ctype=null;
    switch ($ext) {
      
      case "gif": parseTemplate_extension_gif();return true;break;
      case "png": $ctype="image/png"; $filename="1x1.png";break;
      case "jpeg": $ctype="image/jpg";$filename="1x1.jpg";break;
      case "jpg": $ctype="image/jpg";$filename="1x1.jpeg";break;;
      
    }

    if($ctype<>null){
    	parseTemplateLogs("Fake $filename for $uri",__FUNCTION__,__FILE__,__LINE__);
   		$fsize = filesize("img/$filename"); 
    	header("Content-Type: $ctype");
    	header("Content-Disposition: attachment; filename=\"$filename\";" );
    	header("Content-Transfer-Encoding: binary");
    	header("Content-Length: ".$fsize);
    	ob_clean();
    	flush();
    	readfile( $fsize );     	
    	return true;
    }
    if($ext=="js"){
    	parseTemplateLogs("Fake JS for $uri",__FUNCTION__,__FILE__,__LINE__);
    	header("content-type: application/x-javascript");echo "// blocked by url filtering\n";
    	return true;
    }
	if(preg_match("#\/pixel\?#", $uri)){
		parseTemplate_extension_gif();
		return true;
	}
		
}
function parseTemplate_extension_gif(){
		parseTemplateLogs("Fake GIF",__FUNCTION__,__FILE__,__LINE__);
		$fsize = filesize("img/1x1.gif"); 
    	header("Content-Type: image/gif");
    	header("Content-Disposition: attachment; filename=\"1x1.gif\";" );
    	header("Content-Transfer-Encoding: binary");
    	header("Content-Length: ".$fsize);
    	ob_clean();
    	flush();
    	readfile( $fsize );     	
    	}
    	
function parseTemplateLogs($text=null,$function,$file,$line){
	
	$logFile=dirname(__FILE__)."/ressources/logs/squid-template.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
   	$time=date('m-d H:i:s');
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
	$sql="INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename,uid) VALUES ('$md5','$CLIENT','$Whitehost','$MEMBER')";
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

function parseTemplate_SinglePassWord_receive(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");		
	$sock=new sockets();
	$SquidGuardWebAllowUnblockSinglePassContent=md5(trim($sock->GET_INFO("SquidGuardWebAllowUnblockSinglePassContent")));	
	if($_POST["password"]<>$SquidGuardWebAllowUnblockSinglePassContent){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{failed}: {wrong_password}");
		die();
	}

	$Whitehost=$_POST["Whitehost"];
	$CLIENT=$_POST["CLIENT"];
	$md5=md5("$CLIENT$Whitehost");
	$sql="INSERT IGNORE INTO webfilters_usersasks (zmd5,ipaddr,sitename) VALUES ('$md5','$CLIENT','$Whitehost')";
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
	$GLOBALS["JS_HEAD_PREPREND"]="http://$SquidGuardServerName:$SquidGuardApachePort";
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
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$GLOBALS["JS_NO_CACHE"]=true;
	$GLOBALS["JS_HEAD_PREPREND"]="http://$SquidGuardServerName:$SquidGuardApachePort";
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
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />	
		$head
	</head>
	<body style='background: url(\"/css/images/pattern.png\") repeat scroll 0pt 0pt rgb(38, 56, 73); padding: 0px; margin: 0px; border: 0px solid black; width: 100%; cursor: default; -moz-user-select: inherit;'>
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
		XHR.appendData('CLIENT','$clientaddr');
		XHR.appendData('Whitehost','$Whitehost');
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',X_SendPass);     		
	}		
	
	</script>
	</body>
	</html>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function parseTemplate_categoryname($category){
		$category=strtolower(trim($category));
		parseTemplateLogs("Parsing: `$category`",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#^art(.+)#", $category,$re)){
			parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
			$category=$re[1];
			$CATEGORY_PLUS_TXT=" (Artica Database)";
		}
		
		if(preg_match("#^tls(.+)#", $category,$re)){
			parseTemplateLogs("Parsing: `$category`=`{$re[1]}`",__FUNCTION__,__FILE__,__LINE__);
			$category=$re[1];
			$CATEGORY_PLUS_TXT=" (Toulouse University Database)";			
		}
		
			
			if($category=="lingerie"){$category="sex/lingerie";}
			if($category=="dangerousmaterial"){$category="dangerous_material";}
			if($category=="mixedadult"){$category="mixed_adult";}
			if($category=="listebu"){$category="liste_bu";}
			if($category=="adult"){$category="porn";}
			if($category=="audiovideo"){$category="audio-video";}  
				
		
		return $category.$CATEGORY_PLUS_TXT;
	}


function parseTemplate(){
	session_start();
	include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	$sock=new sockets();
	$UfdbGuardRedirectCategories=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
	$SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
	$SquidGuardWebAllowUnblock=$sock->GET_INFO("SquidGuardWebAllowUnblock");
	$SquidGuardWebAllowUnblockSinglePass=$sock->GET_INFO("SquidGuardWebAllowUnblockSinglePass");
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$SquidGuardWebUseLocalDatabase=$sock->GET_INFO("SquidGuardWebUseLocalDatabase");
	if(!is_numeric($SquidGuardWebAllowUnblock)){$SquidGuardWebAllowUnblock=0;}
	if(!is_numeric($SquidGuardWebFollowExtensions)){$SquidGuardWebFollowExtensions=1;}
	if(!is_numeric($SquidGuardWebUseLocalDatabase)){$SquidGuardWebUseLocalDatabase=0;}
	
	while (list ($num, $ligne) = each ($_GET) ){
		parseTemplateLogs("$num=`$ligne`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	$GLOBALS["JS_NO_CACHE"]=true;
	$GLOBALS["JS_HEAD_PREPREND"]="http://$SquidGuardServerName:$SquidGuardApachePort";
	
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
	
	
	if($SquidGuardWebAllowUnblock==1){
		if($SquidGuardWebAllowUnblockSinglePass==1){
			$clientaddr=base64_encode($_GET["clientaddr"]);
			$defaultjs="s_PopUp('{$GLOBALS["JS_HEAD_PREPREND"]}/". basename(__FILE__)."?SquidGuardWebAllowUnblockSinglePass=1&url=".base64_encode("{$_GET["url"]}")."&clientaddr=$clientaddr',450,250)";
			$ADD_JS_PACK=true;
		}
	}
	
	if($SquidGuardWebUseLocalDatabase==1){
		$clientaddr=base64_encode($_GET["clientaddr"]);
		$defaultjs="s_PopUp('{$GLOBALS["JS_HEAD_PREPREND"]}/". basename(__FILE__)."?SquidGuardWebUseLocalDatabase=1&url=".base64_encode("{$_GET["url"]}")."&clientaddr=$clientaddr',500,250)";
		$ADD_JS_PACK=true;
	}
	
	
	parseTemplateLogs("{$_GET["clientaddr"]}: Category=`{$_GET["category"]}` targetgroup=`{$_GET["targetgroup"]}`",__FUNCTION__,__FILE__,__LINE__);
	$_GET["targetgroup"]=parseTemplate_categoryname($_GET["targetgroup"]);;
	
	if(isset($_GET["category"])){
		$_GET["category"]=parseTemplate_categoryname($_GET["category"]);
		$RedirectCategory=$UfdbGuardRedirectCategories[$_GET["category"]];
		
		if($RedirectCategory["enable"]==1){
			if($RedirectCategory["blank_page"]==1){
				parseTemplateLogs("blank_page : For {$_GET["url"]}",__FUNCTION__,__FILE__,__LINE__);
				header("HTTP/1.0 404 Not Found");
				return;
			}
			if(trim($RedirectCategory["template_data"])<>null){
				header('Content-Type: text/html; charset=iso-8859-1');
				$TemplateErrorFinal=$RedirectCategory["template_data"];
				$TemplateErrorFinal=str_replace("-URL-",$_GET["url"],$TemplateErrorFinal);
				$TemplateErrorFinal=str_replace("-IP-",$_GET["clientaddr"],$TemplateErrorFinal);
				$TemplateErrorFinal=str_replace("-REASONGIVEN-","REASON:".$_GET["targetgroup"],$TemplateErrorFinal);
				$TemplateErrorFinal=str_replace("-CATEGORIES-","<strong>Category:{$_GET["category"]}$CATEGORY_PLUS_TXT:</strong><div>Rule:{$_GET["targetgroup"]}</div>",$TemplateErrorFinal);
				$TemplateErrorFinal=str_replace("-REASONLOGGED-","<strong>Rule:&nbsp;</strong>{$_GET["clientgroup"]}",$TemplateErrorFinal);
				$TemplateErrorFinal=str_replace("-BYPASS-","$defaultjs",$TemplateErrorFinal);				
				return;
			}
		}
	}
		
	$TemplateError=$sock->GET_INFO("DansGuardianHTMLTemplate");
	$EnableSquidFilterWhiteListing=$sock->GET_INFO("EnableSquidFilterWhiteListing");
	if(strlen($TemplateError)<50){$TemplateError=$sock->getFrameWork("cmd.php?dansguardian-get-template=yes");}
	if(preg_match("#<body>(.+?)</body>#is",$TemplateError,$re)){$TemplateError=$re[1];}
	
	
	if(isset($_GET["rule-id"])){$ID=$_GET["rule-id"];}
	parseTemplateLogs("ID: $ID",__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["fatalerror"])){$ID=0;}
	if(isset($_GET["loading-database"])){$ID=0;}
			
	if(is_numeric($ID)){
		if($ID==0){
			$ligne["groupname"]="Default";
			parseTemplateLogs("TemplateError: -> DansGuardianDefaultMainRule",__FUNCTION__,__FILE__,__LINE__);
			$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		}else{
			$sql="SELECT groupname,TemplateError FROM webfilter_rules WHERE ID=$ID";
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		}	
		$TemplateError=trim($ligne["TemplateError"]);
		$ruleName=$ligne["groupname"];
		
	}else{
		writelogs("ID: not a numeric",__FUNCTION__,__FILE__,__LINE__);
	}

	parseTemplateLogs("TemplateError: ".strlen($TemplateError)." bytes",__FUNCTION__,__FILE__,__LINE__);
	if($TemplateError==null){
		$template_default_file=dirname(__FILE__)."/ressources/databases/dansguard-template.html";
		$TemplateError=@file_get_contents($template_default_file);
		parseTemplateLogs("TemplateError: -> `$template_default_file` ".strlen($TemplateError)." bytes",__FUNCTION__,__FILE__,__LINE__);
	}
	$TemplateErrorHeader=@file_get_contents(dirname(__FILE__)."/ressources/databases/dansguard-template-header.html");
	$TemplateErrorFinal="$TemplateErrorHeader$TemplateError\n</body>\n</html>";	
	
	if(isset($_GET["fatalerror"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$_GET["targetgroup"]="System Webfiltering error";
		$_GET["clientgroup"]="Service Error";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];
	}
	
	if(isset($_GET["loading-database"])){
		$_GET["clientaddr"]=$_SERVER["REMOTE_ADDR"];
		$_GET["clientname"]=$_SERVER["REMOTE_HOST"];
		$_GET["targetgroup"]="Please wait, reloading databases";
		$_GET["clientgroup"]="Waiting service....";
		$_GET["url"]=$_SERVER['HTTP_REFERER'];		
		
	}
	
	
	
	
	if(!isset($_SESSION["IPRES"][$_GET["clientaddr"]])){$_SESSION["IPRES"][$_GET["clientaddr"]]=gethostbyaddr($_GET["clientaddr"]);}
	
	//url=http://www.eicar.org/download/eicarcom2.zip&source=192.168.1.212/-&user=-&virus=stream:+Eicar-Test-Signature+FOUND
	
	if(isset($_GET["source"])){$_GET["clientaddr"]=$_GET["source"];}
	if(isset($_GET["user"])){$_GET["clientname"]=$_GET["user"];}
	if(isset($_GET["virus"])){$_GET["targetgroup"]=$_GET["virus"];$ruleName=null;}
	if($_GET["clientuser"]<>null){$_GET["clientname"]=$_GET["clientuser"];}
	$TemplateErrorFinal=str_replace("-USER-",$_GET["clientname"],$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-HOST-",$_SESSION["IPRES"][$_GET["clientaddr"]],$TemplateErrorFinal);
	$_GET["clientgroup"]=parseTemplate_categoryname($_GET["clientgroup"]);
	$ruleName=parseTemplate_categoryname($ruleName);
	
	
	if($ruleName<>null){
		$_GET["clientgroup"]=null;
		$ruleNameText="<strong>$ruleName:</strong>";
	}
	
	
	
	$TemplateErrorFinal=str_replace("-URL-",$_GET["url"],$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-IP-",$_GET["clientaddr"],$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-REASONGIVEN-",$_GET["targetgroup"],$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-CATEGORIES-","$ruleNameText<div style='font-size:12px'>Category:&nbsp;{$_GET["targetgroup"]}$CATEGORY_PLUS_TXT</div>",$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-REASONLOGGED-","<strong>Rule:&nbsp;</strong>{$_GET["clientgroup"]}",$TemplateErrorFinal);
	$TemplateErrorFinal=str_replace("-BYPASS-","javascript:$defaultjs",$TemplateErrorFinal);
	
	
	
	
	if($EnableSquidFilterWhiteListing==1){
		$DansGuardianWhiteListIntro=$sock->GET_INFO("DansGuardianWhiteListIntro");	
		if(strlen($DansGuardianWhiteListIntro)<2){$DansGuardianWhiteListIntro="<strong style=\"font-size:14px\">Unlock this Website</strong><hr><br><i style=\"font-size:14px\">Access to this site is restricted because it is not classified in any category selected by our company policy.<br>If you think that this website is safe and help your work for company objectives, you are free to save this website into categories listed bellow.</i><hr>";}
	}
	
	if($ADD_JS_PACK){
		if(preg_match("#<head>(.*?)</head>#is", $TemplateErrorFinal,$re)){
			include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
			include_once(dirname(__FILE__)."/ressources/class.templates.inc");
			$tpl=new templates();
			$pp=new pagebuilder();
			$head=$re[1]."\n".$pp->jsArtica()."\n";
			$TemplateErrorFinal=str_replace($re[1], $head, $TemplateErrorFinal);
		}
	
	}
	
	

	echo "
$TemplateErrorFinal
	";
	
	
	
}

function GetSquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: squidGuard unable to get squid configuration file\n";
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
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
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
	$array["press"]="press";
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
	$array["press"]="press";
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
	$array["strict_redirector"]="strict_redirector";
	$array["strong_redirector"]="strong_redirector";
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
	$array["press"]="press";
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
	$array["strict_redirector"]="strict_redirector";
	$array["strong_redirector"]="strong_redirector";
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
	$array["ringtones"]="ringtones";
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
	
	if(!is_file($table)){echo "`$table` No such file\n";}
	
	if(is_file($table)){$file=$table;$table=null;}
	
	$q=new mysql_squid_builder();
	if($table==null){
		$table="category_".$q->category_transform_name($category);
		echo "Table is null\n";
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
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	if($uuid==null){echo "No uuid\n";return;}
	echo "open $file\n";
	
	
	$handle = @fopen($file, "r"); 
	if (!$handle) {echo "Failed to open file\n";return;}
	$q=new mysql_squid_builder();
	if($GLOBALS["CATTO"]<>null){$category=$GLOBALS["CATTO"];}
	$countstart=$q->COUNT_ROWS($table);
	$prefix="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid) VALUES ";
	echo "$prefix\n";
	$c=0;
	$CBAD=0;
	$CBADIP=0;
	$CBADNULL=0;
	while (!feof($handle)){
		$c++;
		$www =trim(fgets($handle, 4096));
		if($www==null){$CBADNULL++;continue;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){$CBADIP++;continue;}
		$www=trim(strtolower($www));
		if($www=="thisisarandomentrythatdoesnotexist.com"){$CBAD++;continue;}
		if($www==null){$CBADNULL++;continue;}
		if(preg_match("#(.+?)\s+(.+)#", $www,$re)){$www=$re[1];}
		if(strpos($www, "#")>0){echo "FALSE: $www\n";continue;}
		$md5=md5($www.$category);
		$n[]="('$md5',NOW(),'$category','$www','$uuid')";
		
		
		if(count($n)>6000){
			$sql=$prefix.@implode(",",$n);
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";$n=array();continue;}
			$countend=$q->COUNT_ROWS($table);
			$final=$countend-$countstart;
			echo "$c items, $final new entries added - $CBADNULL bad entries for null value,$CBADIP entries for IP addresses\n";	
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
	echo "$final new entries added\n";
	
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
	
	echo "Starting......: ufdbGuard ". count($Narray)." database(s) must be compiled\n";
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


function databases_status(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	if($GLOBALS["VERBOSE"]){echo "databases_status() line:".__LINE__."\n";}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	@mkdir("/var/lib/squidguard",755,true);
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
	$touch=$unix->find_program("touch");
	@mkdir("/var/lib/squidguard",755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		echo "Starting......: ufdbGuard $table -> $categoryname\n";
		if(!is_file("/var/lib/squidguard/$categoryname/domains")){
			@mkdir("/var/lib/squidguard/$categoryname",755,true);
			$sql="SELECT LOWER(pattern) FROM {$ligne["c"]} WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '$table.temp' FIELDS OPTIONALLY ENCLOSED BY 'n'";
			$q->QUERY_SQL($sql);
			if(!is_file("/var/lib/mysql/squidlogs/$table.temp")){
				echo "Starting......: ufdbGuard /var/lib/mysql/squidlogs/$table.temp no such file\n";
				continue;
			}
			echo "Starting......: ufdbGuard /var/lib/mysql/squidlogs/$table.temp done...\n";
			@copy("/var/lib/mysql/squidlogs/$table.temp", "/var/lib/squidguard/$categoryname/domains");	
			@unlink("/var/lib/mysql/squidlogs/$table.temp");
			echo "Starting......: ufdbGuard UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$categoryname/domains)\n";
			UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$categoryname/domains");					
		}else{
			echo "Starting......: ufdbGuard /var/lib/squidguard/$categoryname/domains OK\n";
			
		}
		
		if(!is_file("/var/lib/squidguard/$categoryname/expressions")){shell_exec("$touch /var/lib/squidguard/$categoryname/expressions");}
		
	}
	build();
	if(is_file("/etc/init.d/ufdb")){
		echo "Starting......: ufdbGuard reloading service\n";
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
		echo "Starting......: ufdbGuard recompile all databases is not scheduled\n";
		return;
	}
	if(!is_numeric($UfdbGuardSchedule["H"])){$UfdbGuardSchedule["H"]=5;}
	if(!is_numeric($UfdbGuardSchedule["M"])){$UfdbGuardSchedule["M"]=0;}
	$f[]="MAILTO=\"\"";
	$f[]="{$UfdbGuardSchedule["H"]} {$UfdbGuardSchedule["M"]} * * * root ".$unix->LOCATE_PHP5_BIN()." ".__FILE__." --ufdbguard-recompile-dbs >/dev/null 2>&1"; 
	$f[]="";
	@file_put_contents($cronfile,@implode("\n",$f) );	
	echo "Starting......: ufdbGuard recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}\n";
	//events_ufdb_tail("ufdbGuard recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}",__LINE__);
}

function UFDBGUARD_COMPILE_CATEGORY($category){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$t=time();
	ufdbguard_admin_events("start $category category compilation",__FUNCTION__,__FILE__,__LINE__,"compile");
	$ufdb=new compile_ufdbguard();
	$ufdb->compile_category($category);
	ufdbguard_admin_events("Compile task $category done",__FUNCTION__,__FILE__,__LINE__,"compile");
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}	
}

function UFDBGUARD_COMPILE_ALL_CATEGORIES(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){
		ufdbguard_admin_events("Compilation task aborted this server is a Web statistics client.",__FUNCTION__,__FILE__,__LINE__,"global-compile");
		return;
	}	
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableRemoteStatisticsAppliance==1){UFDBGUARD_DOWNLOAD_ALL_CATEGORIES();return;}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}		
	ufdbguard_admin_events("start all categories compilation",__FUNCTION__,__FILE__,__LINE__,"global-compile");
	$q=new mysql_squid_builder();
	$t=time();
	$cats=$q->LIST_TABLES_CATEGORIES();
	$ufdb=new compile_ufdbguard();
	while (list ($table, $line) = each ($cats) ){
		$category=$q->tablename_tocat($table);
		if($category==null){ufdbguard_admin_events("Compilation failed for table $table, unable to determine category",__FUNCTION__,__FILE__,__LINE__,"global-compile");continue;}
		$ufdb->compile_category($category);
		
	}
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("Compilation all categories done ($ttook)",__FUNCTION__,__FILE__,__LINE__,"global-compile");
	if($EnableWebProxyStatsAppliance==1){CompressCategories();return;}
	ufdbguard_admin_events("Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
	build_ufdbguard_HUP();	
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
	
	if(is_file("/etc/init.d/ufdb")){
		events_ufdb_tail("/etc/init.d/ufdb reconfig");
		ufdbguard_admin_events("Service was sucessfully rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
		build_ufdbguard_HUP();
	}
	

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
			echo "Starting......: Dansguardian reloading service\n";
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
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";
	$uri="$baseUri/ufdbGuard.conf";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/ufdbGuard.conf")){
		@file_put_contents("/etc/ufdbguard/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
		@file_put_contents("/etc/squid3/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
	}else{
		ufdbguard_admin_events("Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}
	
	
	$uri="$baseUri/ufdbGuard.conf";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/ufdbGuard.conf")){
		@file_put_contents("/etc/ufdbguard/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
		@file_put_contents("/etc/squid3/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
	}else{
		ufdbguard_admin_events("Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
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
		echo "Starting......: Squid reloading service\n";
		shell_exec("$nohup $php5 ". basename(__FILE__)."/exec.squid.php --reconfigure-squid >/dev/null 2>&1");
	}	
	
	$datas=@file_get_contents("/etc/ufdbguard/ufdbGuard.conf");
	send_email_events("SquidGuard/ufdbGuard/Dansguardian rules was rebuilded",basename(__FILE__)."\nFunction:".__FUNCTION__."\nLine:".__LINE__."\n".
	"This is new configuration file of the squidGuard/ufdbGuard:\n-------------------------------------\n$datas","proxy");
	shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.c-icap.php --maint-schedule");	
	
	
}





function events_ufdb_exec($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
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
		$date=@date("h:i:s");
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




?>