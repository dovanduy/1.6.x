<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Daemon Monitor";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.monit.inc');

$GLOBALS["MONIT_CLASS"]=new monit_unix();

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--status"){status();die();}
if($argv[1]=="--monitor-wait"){monitor_wait();die();}


function status(){
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}	
	
	$time=$unix->file_time_min($cache_file);
	if($time<2){return;}
	$monit=new monit_unix();
	$array=$monit->all_status();
	if(count($array)<2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} array returned less than 2 items\n";}
		return;}
	@unlink($cache_file);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Saving $cache_file\n";}
	@file_put_contents($cache_file, serialize($array));
	@chmod($cache_file,0755);
}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	build();
	sleep(1);
	start(true);
	
}

function monitor_wait(){
	sleep(5);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	shell_exec("{$GLOBALS["MONIT_CLASS"]->monitor_all_cmdline} >/dev/null 2>&1");
	
}


function start($aspid=false){
	if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){return;}}
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("monit");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableMonit=$sock->GET_INFO("EnableMonit");
	if(!is_numeric($EnableMonit)){$EnableMonit=1;}
	
	

	if($EnableMonit==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	@mkdir("/var/run/monit",0755,true);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} apply permissions\n";}
	$GLOBALS["MONIT_CLASS"]->CheckFolders();
	
	$cmd=$GLOBALS["MONIT_CLASS"]->start_cmdline ." 2>&1";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} configuring service\n";}
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} starting service\n";}
	shell_exec("$nohup $cmd >/dev/null 2>&1 &");
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Force to monitor all daemons..\n";}
		shell_exec("$nohup $php5 --monitor-wait >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		while (list ($index, $line) = each ($results) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $line\n";}
		}
		
	}
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unmonitor all processes\n";}
	exec($GLOBALS["MONIT_CLASS"]->stop_cmdline." 2>&1",$results);
	sleep(1);

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function build(){
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$SystemLoadNotif=$sock->GET_INFO("SystemLoadNotif");
	if(!is_numeric($SystemLoadNotif)){$SystemLoadNotif=0;}
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	
	$ini=new Bs_IniHandler();
	$ini->loadFile('/etc/artica-postfix/smtpnotif.conf');
	
	if(!is_numeric($ini->_params["SMTP"]["EnableNotifs"])){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	if(!is_numeric($ini->_params["SMTP"]["tls_enabled"])){$ini->_params["SMTP"]["tls_enabled"]=0;}
	
	
	$smtp_server=trim($ini->_params["SMTP"]['smtp_server_name']);
	$smtp_server_port=$ini->_params["SMTP"]['smtp_server_port'];
	$smtp_dest=$ini->_params["SMTP"]['smtp_dest'];
	$smtp_sender=$ini->_params["SMTP"]['smtp_sender'];
	$smtp_auth_user=$ini->_params["SMTP"]['smtp_auth_user'];
	$smtp_auth_passwd=$ini->_params["SMTP"]['smtp_auth_passwd'];
	$tls_enabled=$ini->_params["SMTP"]["tls_enabled"];
	
	$recipientsZ=explode("\n","/etc/artica-postfix/settings/Daemons/SmtpNotificationConfigCC");
	
	$recipients=array();
	while (list ($index, $to) = each ($recipientsZ) ){
		if(trim($to)==null){continue;}
		$recipients[]=$to;
		
	}
	
	if($smtp_server==null){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	if($smtp_dest==null){
		if(count($recipients)==0){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	}
	if(!is_numeric($smtp_server_port)){$smtp_server_port=25;}
	
	$EnableNotifs=$ini->_params["SMTP"]["EnableNotifs"];
	$monit_not_on='instance,action';
	
	$f[]='set daemon 60 with start delay 5';
	$f[]='set idfile /var/run/monit/monit.id';
	
	
	$cpunum=$unix->CPU_NUMBER();
	$normal=($cpunum*2)+1;
	$normal2=$cpunum*2;
	$busy=$cpunum*4;
	
	$EnableMONITSmtpNotif=$sock->GET_INFO("EnableMONITSmtpNotif");
	if(!is_numeric($EnableMONITSmtpNotif)){$EnableMONITSmtpNotif=1;}
	
	
	$EnableWatchMemoryUsage=$sock->GET_INFO("EnableWatchMemoryUsage");
	if(!is_numeric($EnableWatchMemoryUsage)){$EnableWatchMemoryUsage=1;}
	
	$EnableWatchCPUsage=$sock->GET_INFO("EnableWatchCPUsage");
	if(!is_numeric($EnableWatchCPUsage)){$EnableWatchCPUsage=1;}
	
	$SystemWatchMemoryUsage=$sock->GET_INFO("SystemWatchMemoryUsage");
	if(!is_numeric($SystemWatchMemoryUsage)){$SystemWatchMemoryUsage=75;}
	
	$EnableWatchCPUsage=$sock->GET_INFO("EnableWatchCPUsage");
	if(!is_numeric($EnableWatchCPUsage)){$EnableWatchCPUsage=1;}
	
	
	$SystemWatchCPUUser=$sock->GET_INFO("SystemWatchCPUUser");
	if(!is_numeric($SystemWatchCPUUser)){$SystemWatchCPUUser=80;}

	$SystemWatchCPUSystem=$sock->GET_INFO("SystemWatchCPUSystem");
	if(!is_numeric($SystemWatchCPUSystem)){$SystemWatchCPUSystem=80;}

	$EnableLoadAvg1mnUser=$sock->GET_INFO("EnableLoadAvg1mnUser");
	if(!is_numeric($EnableLoadAvg1mnUser)){$EnableLoadAvg1mnUser=1;}	
	
	$EnableLoadAvg5mnUser=$sock->GET_INFO("EnableLoadAvg5mnUser");
	if(!is_numeric($EnableLoadAvg5mnUser)){$EnableLoadAvg5mnUser=1;}
	
	$EnableLoadAvg15mnUser=$sock->GET_INFO("EnableLoadAvg15mnUser");
	if(!is_numeric($EnableLoadAvg15mnUser)){$EnableLoadAvg15mnUser=1;}

	
	$Load1mn=$sock->GET_INFO("Load1mn");
	if(!is_numeric($Load1mn)){$Load1mn=$busy;}
	$Load15mn=$sock->GET_INFO("Load15mn");
	if(!is_numeric($Load15mn)){$Load15mn=$normal2;}
	$Load5mn=$sock->GET_INFO("Load5mn");
	if(!is_numeric($Load5mn)){$Load5mn=$normal;}

	$DoNotCheckSystem=0;

	
	
	if($EnableLoadAvg1mnUser==0){
		if($EnableLoadAvg5mnUser==0){
			if($EnableLoadAvg15mnUser==0){
				if($EnableWatchMemoryUsage==0){
					if($SystemLoadNotif==0){
						if($EnableWatchCPUsage==0){$DoNotCheckSystem=1;}
					}
				}
			}
		}
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$rmbin=$unix->find_program("rm");
	$echo=$unix->find_program("echo");
	if($SystemWatchCPUSystem>100){$SystemWatchCPUSystem=99;}
	if($SystemWatchCPUUser>100){$SystemWatchCPUUser=99;}
	if($SystemWatchMemoryUsage>10){$SystemWatchMemoryUsage=99;}
	
	if($SystemWatchCPUSystem<5){$SystemWatchCPUSystem=99;}
	if($SystemWatchCPUUser<5){$SystemWatchCPUUser=99;}
	if($SystemWatchMemoryUsage<5){$SystemWatchMemoryUsage=99;}
	
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$f[]='set logfile syslog facility log_daemon';
	
	$f[]='set statefile /var/run/monit/monit.state';
	$f[]='';
	if($EnableNotifs==1){
		if($EnableMONITSmtpNotif==1){
			$f[]="set mailserver $smtp_server PORT $smtp_server_port";
			if(strlen($smtp_auth_user)>0){ $f[]="\tUSERNAME \"$smtp_auth_user\" PASSWORD \"$smtp_auth_passwd\"";}
			if($tls_enabled==1){$f[]="\tusing TLSV1";}
			$f[]="\tset eventqueue";
			$f[]="\tbasedir /var/monit";
			$f[]="\tslots 100";
	
			$f[]="\tset mail-format {";
			$f[]="\t\tfrom: $smtp_sender";
			$f[]="\t\tsubject: Artica service monitor: \$SERVICE \$EVENT";
			$f[]="\t\tmessage: Artica service monitor  \$ACTION  \$SERVICE at  \$DATE on  \$HOST:  \$DESCRIPTION";
			$f[]="\t}";
			$f[]="set alert $smtp_dest but not on {{$monit_not_on}}";
			if($recipients>0){
				while (list ($index, $to) = each ($recipientsZ) ){
					$f[]="set alert $to but not on {{$monit_not_on}}";
				}
			}
	
		}
	}
	
	$allips=$unix->NETWORK_ALL_INTERFACES(true);
	
	$f[]="set httpd port 2874 and use address 127.0.0.1";
	$f[]="\tallow 127.0.0.1";
	while (list ($tcpi, $to) = each ($allips) ){
		$f[]="\tallow $tcpi";
	}
	
	$top=$unix->find_program("top");
	$hostname=$unix->hostname_g();
	
	if($DoNotCheckSystem==0){
		$f[]="check system ".$unix->hostname_g();
		if($SystemLoadNotif>0){$f[]="\tif loadavg (1min) > $SystemLoadNotif then exec \"$php5 /usr/share/artica-postfix/exec.watchdog.php --loadavg-notif\"";}
		if($EnableLoadAvg1mnUser==1){$f[]="\tif loadavg (1min) > $Load1mn for 5 cycles then alert";}
		if($EnableLoadAvg5mnUser==1){$f[]="\tif loadavg (5min) > $Load5mn for 5 cycles then alert";}
		if($EnableLoadAvg15mnUser==1){$f[]="\tif loadavg (15min) > $Load15mn for 5 cycles then alert";}
		if($EnableWatchMemoryUsage==1){$f[]="\tif memory usage > $SystemWatchMemoryUsage% for 5 cycles then alert";}
		if($EnableWatchCPUsage==1){
			//$f[]="if cpu usage (user) > $SystemWatchCPUUser% for 5 cycles then exec \"/bin/bash -c '$top -b -n 1 >> /var/log/ArticaProc.log;/bin/date >> /var/log/ArticaProc.log'\"";
			//$f[]="if cpu usage (system) > $SystemWatchCPUSystem% for 5 cycles then exec \"/bin/bash -c '$top -b -n 1 >> /var/log/ArticaProc.log;/bin/date >> /var/log/ArticaProc.log'\"";
		}
	
	}
	
	$f[]="check host loopback with address 127.0.0.1";
	$f[]="\tif failed icmp type echo with timeout 1 seconds then exec \"/bin/loopbackfailed.sh\"";
	$f[]="";
	
	$loopbackfailed[]="#!/bin/sh";
	$loopbackfailed[]="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --loopback";
	$loopbackfailed[]="";
	@file_put_contents("/bin/loopbackfailed.sh", @implode("\n", $loopbackfailed));
	@chmod("/bin/loopbackfailed.sh",0755);
	$loopbackfailed=array();

//********************************************************************************************************************	
	$f[]="check file php.log with path /var/log/php.log";
	$f[]="\tif size > 100 MB then";
	$f[]="\t\texec \"/bin/clean-phplog.sh\"";
	$f[]="";
	$f[]="check file usrphp.log with path /usr/share/artica-postfix/ressources/logs/php.log";
	$f[]="      if size > 100 MB then";
	$f[]="\t\texec \"/bin/clean-phplog.sh\"";
	$f[]="";
	$f[]="include /etc/monit/conf.d/*";
	@file_put_contents("/etc/monit/monitrc", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/monitrc done...\n";}
	
	$AA[]="#!/bin/sh";
	$AA[]="$echo \"\" >/var/log/php.log";
	$AA[]="";
	@file_put_contents("/bin/clean-phplog.sh", @implode("\n", $AA));
	@chmod("/bin/clean-phplog.sh",0755);
	$AA=array();
	$monit=new monit();
	$monit->save();
	$INITD_PATH=$unix->SLAPD_INITD_PATH();
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	@unlink("/etc/monit/conf.d/APP_OPENLDAP.monitrc");
//********************************************************************************************************************	
	$f=array();


//********************************************************************************************************************	
	$f=array();
	$f[]="check process APP_FRAMEWORK";
	$f[]="with pidfile /var/run/lighttpd/framework.pid";
	$f[]="start program = \"/etc/init.d/artica-framework start --monit\"";
	$f[]="stop program =  \"/etc/init.d/artica-framework stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/articaframework.monitrc", @implode("\n", $f));
	$f=array();
//********************************************************************************************************************	
	$f=array();
	@unlink("/etc/monit/conf.d/APP_OPENSSH.monitrc");
	
//********************************************************************************************************************	
	$f=array();
	$f[]="check process APP_MYSQL_ARTICA with pidfile /var/run/mysqld/mysqld.pid";
	$f[]="\tstart program = \"/etc/init.d/mysql start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/mysql stop --monit\"";
	$f[]="\tif failed unixsocket /var/run/mysqld/mysqld.sock then restart";
	$f[]="\tif 5 restarts within 5 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring MySQL...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_MYSQLD.monitrc", @implode("\n", $f));	
//********************************************************************************************************************	
	$f=array();
	$f[]="check process APP_ARTICA_STATUS with pidfile /etc/artica-postfix/exec.status.php.pid";
	$f[]="\tstart program = \"/etc/init.d/artica-status start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/artica-status stop --monit\"";
	$f[]="\tif 5 restarts within 5 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Status...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_ARTICASTATUS.monitrc", @implode("\n", $f));	
//********************************************************************************************************************	
	
	$f=array();
	@unlink("/etc/monit/conf.d/squid.monitrc");
	@unlink("/etc/monit/conf.d/APP_SQUIDMAIN.monitrc");
	if(is_file($squidbin)){
			if($SQUIDEnable==1){
				$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
				$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
				if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
				if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
				if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
				if($MonitConfig["watchdog"]==1){
					if($MonitConfig["watchdogMEM"]>500){$AVAILABLE_MEM=$unix->MEM_TOTAL_INSTALLEE();$AVAILABLE_MEM=$AVAILABLE_MEM/1024;$prc=$MonitConfig["watchdogMEM"]/$AVAILABLE_MEM;$prc=round($prc*100);}
					$f=array();
					$f[]="check process APP_SQUID with pidfile /var/run/squid/squid.pid";
					$f[]="\tstart program = \"/etc/init.d/squid start --monit\"";
					$f[]="\tstop program = \"/etc/init.d/squid stop --monit\"";
					if($SquidMgrListenPort>0){$f[]="\tif failed host 127.0.0.1 port $SquidMgrListenPort  then restart";}
					if($MonitConfig["watchdogCPU"]>60){$f[]="\tif cpu usage > {$MonitConfig["watchdogCPU"]}% for 5 cycles then restart";}
					if($prc>10){$f[]="\tif mem usage > {$prc}% for 5 cycles then restart";}
					$f[]="\tif 5 restarts within 5 cycles then timeout";
					$f[]="";
					if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Squid-Cache...\n";}
					@file_put_contents("/etc/monit/conf.d/APP_SQUIDMAIN.monitrc", @implode("\n", $f));
				}
			}
		}
		
// ********************************************************************************************************************	
	$f=array();
	@unlink("/etc/monit/conf.d/APP_SQUIDDB.monitrc");
	if(is_dir("/opt/squidsql/data")){
		if($SQUIDEnable==1){
			$f=array();
			$f[]="check process APP_SQUID_DB with pidfile /var/run/squid-db.pid";
			$f[]="\tstart program = \"/etc/init.d/squid-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/squid-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/mysqld/squid-db.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Squid MySQL DB...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SQUIDDB.monitrc", @implode("\n", $f));
		}		
	}
	
// ********************************************************************************************************************
	$f=array();
	@unlink("/etc/monit/conf.d/APP_DNSMASQ.monitrc");
	if($users->dnsmasq_installed){
			$enabled=$sock->dnsmasq_enabled();
			if($enabled==1){
				$f[]="check process APP_DNSMASQ with pidfile /var/run/dnsmasq.pid";
				$f[]="\tstart program = \"/etc/init.d/dnsmasq start --monit\"";
				$f[]="\tstop program = \"/etc/init.d/dnsmasq stop --monit\"";
				$f[]="\tif 5 restarts within 5 cycles then timeout";
				$f[]="";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DnsMASQ...\n";}
				@file_put_contents("/etc/monit/conf.d/APP_DNSMASQ.monitrc", @implode("\n", $f));
			}
	}
// ********************************************************************************************************************
		
	
	
// ********************************************************************************************************************	
	$f=array();
	@unlink("/etc/monit/conf.d/APP_CICAP.monitrc");
	if($users->C_ICAP_INSTALLED){
		if($SQUIDEnable==1){
			$CicapEnabled=$sock->GET_INFO("CicapEnabled");
			if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
			if($CicapEnabled==1){
				$f[]="check process APP_C_ICAP with pidfile /var/run/c-icap/c-icap.pid";
				$f[]="\tstart program = \"/etc/init.d/artica-postfix start cicap\"";
				$f[]="\tstop program = \"/etc/init.d/artica-postfix stop cicap\"";
				$f[]="\tif 5 restarts within 5 cycles then timeout";
				$f[]="";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring C-ICAP...\n";}
				@file_put_contents("/etc/monit/conf.d/APP_CICAP.monitrc", @implode("\n", $f));
			}
			
		}
	}
// ********************************************************************************************************************	
	@unlink("/etc/monit/conf.d/APP_SYSLOGDB.monitrc");
	if($EnableSyslogDB==1){
		if($MySQLSyslogType==1){
			$f=array();
			$f[]="check process APP_SYSLOG_DB with pidfile /var/run/syslogdb.pid";
			$f[]="\tstart program = \"/etc/init.d/syslog-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/syslog-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/syslogdb.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring syslogd...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SYSLOGDB.monitrc", @implode("\n", $f));			
			
		}
		
	}
//********************************************************************************************************************	
	@unlink("/etc/monit/conf.d/APP_ZARAFASERVER.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAGATEWAY.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAAPACHE.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAWEB.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFASPOOLER.monitrc");	
	@unlink("/etc/monit/conf.d/APP_ZARAFADB.monitrc");

	

	if(is_file($unix->find_program("zarafa-server"))){
		$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
		if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
		$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
		if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
		
		if($ZarafaDedicateMySQLServer==1){
			$f=array();
			$f[]="check process APP_ZARAFA_DB with pidfile /var/run/zarafa-db.pid";
			$f[]="\tstart program = \"/etc/init.d/zarafa-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/zarafa-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/mysqld/zarafa-db.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Database...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_ZARAFADB.monitrc", @implode("\n", $f));
		}		
		
		
		if($ZarafaApacheEnable==1){
			$f=array();
			$f[]="check process APP_ZARAFA_WEB with pidfile /var/run/zarafa-web/httpd.pid";
			$f[]="\tstart program = \"/etc/init.d/zarafa-web start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/zarafa-web stop --monit\"";
			$f[]="\tif failed host 127.0.0.1 port $ZarafaApachePort then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa WebMail...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_ZARAFAWEB.monitrc", @implode("\n", $f));			
		}
		
		$f=array();
		$f[]="check process APP_ZARAFA_SERVER with pidfile /var/run/zarafa-server.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-server start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-server stop --monit\"";
		$f[]="\tif failed unixsocket /var/run/zarafa then restart";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Server...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFASERVER.monitrc", @implode("\n", $f));

		$f=array();
		$f[]="check process APP_ZARAFA_SPOOLER with pidfile /var/run/zarafa-spooler.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-spooler start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-spooler stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Spooler...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFASPOOLER.monitrc", @implode("\n", $f));		
		
		
		
		
		$f=array();
		$f[]="check process APP_ZARAFA_GATEWAY with pidfile /var/run/zarafa-gateway.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-gateway start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-gateway stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Gateway...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFAGATEWAY.monitrc", @implode("\n", $f));		
		
	}

//********************************************************************************************************************
	$EnableClamavDaemon=$sock->GET_INFO("EnableClamavDaemon");
	$EnableClamavDaemonForced=$sock->GET_INFO("EnableClamavDaemonForced");
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
//********************************************************************************************************************
	
	@unlink("/etc/monit/conf.d/APP_CLAMAV.monitrc");
	$MasterBin=$unix->find_program("clamd");
	if(is_file($MasterBin)){
		if($EnableClamavDaemon==1){
			$f=array();
			$f[]="check process APP_CLAMAV";
			$f[]="with pidfile /var/run/clamav/clamd.pid";
			$f[]="start program = \"/etc/init.d/clamav-daemon start --monit\"";
			$f[]="stop program =  \"/etc/init.d/clamav-daemon stop --monit\"";
			$f[]="if 5 restarts within 5 cycles then timeout";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Clamd service...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_CLAMAV.monitrc", @implode("\n", $f));
			$f=array();
		}
	}
	
	
	
//********************************************************************************************************************	
	@unlink("/etc/monit/conf.d/ufdb.monitrc");
	@unlink("/etc/monit/conf.d/ufdbweb.monitrc");
	$ufdbbin=$unix->find_program("ufdbguardd");
	if(is_file($ufdbbin)){
		$EnableUfdbGuard=$sock->EnableUfdbGuard();
		
		$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
		$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
		$SquidGuardApacheSSLPort=$sock->GET_INFO("SquidGuardApacheSSLPort");
		if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
		if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
		if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableUfdbGuard==0){$EnableSquidGuardHTTPService=0;}
		if($EnableWebProxyStatsAppliance==1){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort="9020";}
		if(!is_numeric($SquidGuardApacheSSLPort)){$SquidGuardApacheSSLPort=9025;}
		
		if($SQUIDEnable==1){	
			if($UseRemoteUfdbguardService==0){
			if($EnableUfdbGuard==1){
				$f=array();
				$f[]="check process APP_UFDBGUARD";
				$f[]="with pidfile /var/run/urlfilterdb/ufdbguardd.pid";
				$f[]="start program = \"/etc/init.d/ufdb start --monit\"";
			   	$f[]="stop program =  \"/etc/init.d/ufdb stop --monit\"";
				$f[]="if totalmem > 700 MB for 5 cycles then alert";
			   	$f[]="if cpu > 95% for 5 cycles then alert";
			   	$f[]="if 5 restarts within 5 cycles then timeout";
			   	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Web filtering service...\n";}
			 	@file_put_contents("/etc/monit/conf.d/ufdb.monitrc", @implode("\n", $f));	
				}
			}
			
			
			
			if($EnableSquidGuardHTTPService==1){
				$f=array();
				$f[]="check process APP_SQUIDGUARD_HTTP";
				$f[]="with pidfile /var/run/lighttpd/squidguard-lighttpd.pid";
				$f[]="start program = \"/etc/init.d/squidguard-http start --monit\"";
				$f[]="stop program =  \"/etc/init.d/squidguard-http stop --monit\"";
				$f[]="if failed host 127.0.0.1 port $SquidGuardApachePort then restart";
				$f[]="if 5 restarts within 5 cycles then timeout";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Web filtering HTTP service...\n";}
				@file_put_contents("/etc/monit/conf.d/ufdbweb.monitrc", @implode("\n", $f));
				
			}
			
			
		}
	}
	
//********************************************************************************************************************	
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){$EnableArticaFrontEndToNGninx=0;}
	$pid=null;
	
	@unlink("/etc/monit/conf.d/APP_LIGHTTPD.monitrc");
	if($EnableArticaFrontEndToNGninx==0){
		$pid="/var/run/lighttpd/lighttpd.pid";
		if($EnableArticaFrontEndToApache==1){$pid="/var/run/artica-apache/apache.pid";}
		$f=array();
		$f[]="check process APP_ARTICAWEBCONSOLE with pidfile $pid";
		$f[]="\tstart program = \"/etc/init.d/artica-webconsole start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/artica-webconsole stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Web Console...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_LIGHTTPD.monitrc", @implode("\n", $f));
	}
	//********************************************************************************************************************		
	@unlink("/etc/monit/conf.d/APP_NGINX.monitrc");
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){
		if($EnableNginx==1){
			
			$f=array();
			$f[]="check process APP_NGINX with pidfile /var/run/nginx.pid";
			$f[]="\tstart program = \"/etc/init.d/nginx start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/nginx stop --monit\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring NgINX...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_NGINX.monitrc", @implode("\n", $f));			
			
		}
		
	}
	//********************************************************************************************************************	
	
	
	$f=array();
	if(is_file("/etc/init.d/sysklogd")){
		$f[]="check process APP_SYSLOGD with pidfile /var/run/syslogd.pid";
		$f[]="\tstart program = \"/etc/init.d/sysklogd start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/sysklogd stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="\tcheck file syslogd_file with path /var/log/syslog";
		$f[]="\tif timestamp > 10 minutes then restart";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring sysklogd...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_SYSKLOGD.monitrc", @implode("\n", $f));
	}
	
//********************************************************************************************************************	
	$binpath=$unix->DHCPD_BIN_PATH();
	@unlink("/etc/monit/conf.d/APP_DHCPD.monitrc");
	$f=array();
	if(is_file($binpath)){
		$EnableDHCPServer=$sock->GET_INFO("EnableDHCPServer");
		if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
		if($EnableDHCPServer==1){
		$f[]="check process APP_DHCP with pidfile /var/run/dhcpd.pid";
		$f[]="\tstart program = \"/etc/init.d/isc-dhcp-server start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/isc-dhcp-server stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DHCP Service...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_DHCPD.monitrc", @implode("\n", $f));
		}
	}
//********************************************************************************************************************	
$binpath=$unix->find_program("rdpproxy");
@unlink("/etc/monit/conf.d/APP_RDPPROXY.monitrc");
$f=array();
if(is_file($binpath)){
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if($EnableRDPProxy==1){
		$f[]="check process APP_RDPPROXY with pidfile /var/run/redemption/rdpproxy.pid";
		$f[]="\tstart program = \"/etc/init.d/rdpproxy start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/rdpproxy stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring RDP Proxy...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_RDPPROXY.monitrc", @implode("\n", $f));
	}
}
//********************************************************************************************************************
@unlink("/etc/monit/conf.d/APP_DNSMASQ.monitrc");
$f=array();
$binpath=$unix->find_program("dnsmasq");
if(is_file($binpath)){
	$EnableDNSMASQ=$users->EnableDNSMASQ();
	if($EnableDNSMASQ==1){
		$f[]="check process APP_DNSMASQ with pidfile /var/run/dnsmasq.pid";
		$f[]="\tstart program = \"/etc/init.d/dnsmasq start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/dnsmasq stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DNSMasq Service...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_DNSMASQ.monitrc", @implode("\n", $f));
	}
}
//********************************************************************************************************************	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checking syslog\n";}
	if(is_file("/etc/init.d/syslog")){checkDebSyslog();}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} configuration done\n";}
	shell_exec($GLOBALS["MONIT_CLASS"]->monitor_all_cmdline." 2>&1");
	
}
function checkDebSyslog(){
	if(!is_file("/etc/rsyslog.conf")){return;}
	$f=file("/etc/init.d/syslog");
	$RSYSLOGD_PIDFILE=null;
	while (list ($num, $line) = each ($f)){
		if(preg_match("#RSYSLOGD_PIDFILE=(.+)#", $line,$re)){
			$RSYSLOGD_PIDFILE=$re[1];
			break;
		}
	}

	$filesize=filesize("/etc/init.d/syslog");
	if($filesize<50){$RSYSLOGD_PIDFILE="/var/run/rsyslogd.pid";}
	if($RSYSLOGD_PIDFILE==null){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pidfile `cannot check pid...`\n";return;}}

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} rsyslog pidfile `$RSYSLOGD_PIDFILE`\n";}

	$f=file("/etc/rsyslog.conf");
	while (list ($num, $line) = each ($f)){
		if(preg_match("#\*\.\*.*?\s+(.+)#", $line,$re)){
			$syslogpath=$re[1];
			if(substr($syslogpath, 0,1)=='-'){$syslogpath=substr($syslogpath, 1,strlen($syslogpath));}
			break;
		}
		 
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath`\n";}
	if(!is_file($syslogpath)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath` no such file!\n";return;}}

	$f=array();
	$f[]="check process APP_SYSLOGD with pidfile $RSYSLOGD_PIDFILE";
	$f[]="start program = \"/etc/init.d/syslog start --monit\"";
	$f[]="stop program = \"/etc/init.d/syslog stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@chmod("/etc/init.d/syslog",0755);
	@file_put_contents("/etc/monit/conf.d/APP_RSYSLOGD.monitrc", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/conf.d/APP_RSYSLOGD.monitrc done\n";}
}
?>