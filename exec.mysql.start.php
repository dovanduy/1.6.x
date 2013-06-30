<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}

if($argv[1]=="--start"){SERVICE_START();die(0);}
if($argv[1]=="--stop"){SERVICE_STOP();die(0);}
if($argv[1]=="--restart"){SERVICE_RESTART();die(0);}
if($argv[1]=="--recovery"){restart_reco();die();}
if($argv[1]=="--watch"){WATCHDOG_MYSQL();die();}
if($argv[1]=="--engines"){status_all_mysql_engines();die();}
if($argv[1]=="--clean"){clean_events();die();}



function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/mysqld/mysqld.pid");
	if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: /var/run/mysqld/mysqld.pid -> \"$pid\"\n";}
	if(!$unix->process_exists($pid)){
		$mysqlbin=$unix->LOCATE_mysqld_bin();
		$pgrep=$unix->find_program("pgrep");
		$lsof=$unix->find_program("lsof");
		if(is_file($pgrep)){
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1\n";}
			exec("$pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1",$results);
			
			while (list ($num, $line) = each ($results) ){
				if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $line\n";}
				if(preg_match("#pgrep#",$line)){continue;}
			 	if(preg_match("#^([0-9]+)\s+#", $line,$re)){
			 		@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
			 		return $re[1];
			 	}
			}
		}
		$results=array();
		exec("$lsof -Pnl +M -i TCP:3306 2>&1",$results);
		while (list ($num, $line) = each ($results) ){
			if(preg_match("#mysqld\s+([0-9]+).*?TCP.*?:3306#",$line,$re)){
				@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
				return $re[1];
			}
			
		}
	}
	return $pid;
	
}

function restart_reco(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "Starting......: MySQL this script is already executed PID: $oldpid since {$time}Mn\n";
			if($time<10){if(!$GLOBALS["FORCE"]){return;}}
			shell_exec("$kill -9 $oldpid");
		}
		@file_put_contents($pidfile, getmypid());	
	
$MYSQL_DIR=$unix->MYSQL_DATA_DIR();	
$zarafa_server=$unix->find_program("zarafa-server");
$GLOBALS["RECOVERY"]=3;
echo "Stopping MySQL...............: RECOVERY MODE\n";
SERVICE_STOP(true);


if(is_file($zarafa_server)){
	echo "Starting......: Removing frm files.\n";
	shell_exec("/bin/rm -f $MYSQL_DIR/zarafa/*.frm");
}

echo "Starting......: MySQL RECOVERY MODE\n";
SERVICE_START(false,true);
echo "Starting......: Sleeping 10 seconds\n";
sleep(10);
echo "Stopping MySQL...............: RECOVERY MODE\n";
SERVICE_STOP(true);
$GLOBALS["RECOVERY"]=0;
echo "Starting......: MySQL Normal mode\n";
SERVICE_START(false,true);

if(is_file($zarafa_server)){
	echo "Starting......: Restarting Zarafa-server\n";
	shell_exec("/etc/init.d/artica-postfix restart zarafa-server");
}

	
}

function WATCHDOG_MYSQL(){
	$unix=new unix();
	$mysqladmin=$unix->find_program("mysqladmin");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);	
	$kill=$unix->find_program("kill");
	
	if(!$GLOBALS["FORCE"]){
		$LastExec=$unix->file_time_min($pidTime);
		if($LastExec<5){return;}
		
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($time<5){return;}
			shell_exec("$kill -9 $oldpid");
		}
	}
	
	@unlink($pidfile);
	@unlink($pidTime);
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($pidTime, time());
	
	
	$socket="/var/run/mysqld/mysqld.sock";
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if(!$unix->is_socket($socket)){
		system_admin_events("$socket, no such file, restarting MySQL service", __FUNCTION__, __FILE__, __LINE__, "mysql");
		shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
		return;
	}
	
	$MYSQLPid=PID_NUM();
	if(!$unix->process_exists($MYSQLPid)){
		system_admin_events("Error, \"MySQL service is not running\", Starting MySQL service", __FUNCTION__, __FILE__, __LINE__, "mysql");
		shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
		return;
	}
	$RunningScince=$unix->PROCCESS_TIME_MIN($MYSQLPid);
	if($GLOBALS["VERBOSE"]){echo "MySQL PID: $MYSQLPid running since \"{$RunningScince}mn\"\n";}
	
	if(!is_file($mysqladmin)){return;}
	$q=new mysql();
	$cmds[]=$mysqladmin;
	$cmds[]="--user=$q->mysql_admin";
	if($q->mysql_password<>null){
		$password=$unix->shellEscapeChars($q->mysql_password);
		$cmds[]="--password=$password";
	}
	$cmds[]="--socket=$socket";	
	$cmds[]="processlist";
	$cmd=@implode(" ", $cmds)." 2>&1";
	exec("$cmd",$results);
	if($GLOBALS["VERBOSE"]){echo "Receive: ".count($results)." rows\n";}
	while (list ($num, $line) = each ($results) ){
		if(strpos($line, "-----------------------------")>0){continue;}
		if(preg_match("#show processlist#",$line)){ continue;}
		
		if(preg_match("#Can't connect to local MySQL server through socket#", $line)){
			system_admin_events("Error, \"Can't connect to local MySQL server through socket\", restarting MySQL service", __FUNCTION__, __FILE__, __LINE__, "mysql");
			shell_exec("$nohup /etc/init.d/mysql restart >/dev/null 2>&1 &");			
			return;
		}
		
		if(preg_match("#error:\s+'(.*?)'#", $line,$re)){
			system_admin_events("Error, \"{$re[1]}\", restarting MySQL service", __FUNCTION__, __FILE__, __LINE__, "mysql");
			shell_exec("$nohup /etc/init.d/mysql restart >/dev/null 2>&1 &");
			return;
		}
		
		if(preg_match("#\|\s+([0-9]+)\s+\|\s+(.*?)\s+\|\s+(.*?)\s+\|\s+(.*?)\s+\|\s+(Query|Sleep)\s+\|\s+([0-9]+)\s+#",$line,$re)){
			$time=time();
			$seconds=$time-$re[6];
			$dist=$unix->distanceOfTimeInWords($seconds,$time);
			if($GLOBALS["VERBOSE"]){echo "\"Process {$re[1]}/{$re[5]}\" running since {$dist}\n";}
			$countPid[$re[1]]=$re[5];
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "\"$line\"\n";}
		
	}
	
	if($GLOBALS["VERBOSE"]){echo count($countPid)." Threads...\n";}
		
}
function SERVICE_RESTART(){
	SERVICE_STOP(true);
	SERVICE_START(false,true);
	
}


function SERVICE_STOP($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$socket="/var/run/mysqld/mysqld.sock";
	$mysqlbin=$unix->LOCATE_mysqld_bin();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");	
	$mysqladmin=$unix->find_program("mysqladmin");
	$kill=$unix->find_program("kill");
	$pgrep=$unix->find_program("pgrep");
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$kill=$unix->find_program("kill");
	
	if(!$aspid){
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "Stopping MySQL...............: This script is already executed PID: $oldpid since {$time}Mn\n";
			if($time<5){if(!$GLOBALS["FORCE"]){return;}}
			shell_exec("$kill -9 $oldpid");
		}
		
		
		@file_put_contents($pidfile, getmypid());		
	}
	
	$pid=PID_NUM();  
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: PID RETURNED $pid\n";}
	

	
	if(!$unix->process_exists($pid,$mysqlbin)){echo "Stopping MySQL...............: Already stopped\n";return;}
	
	
	$q=new mysql();
	if(is_file($mysqladmin)){
		if(is_file($socket)){
			$cmds[]="nohup";
			$cmds[]=$mysqladmin;
			$cmds[]="--user=$q->mysql_admin";
			if($q->mysql_password<>null){
				$password=$q->mysql_password;
				$password=str_replace('&','\&',$password);
				$password=str_replace('<','\<',$password);
	      		$password=str_replace('$','\$',$password);
	     		$password=str_replace('>','\>',$password);
	       		$password=str_replace('$','\$',$password);
	       		$password=str_replace('!','\!',$password);
	      		$cmds[]="--password=$password";
			}
			$cmds[]="--socket=$socket";
			$cmds[]="shutdown";
			$cmd=@implode(" ", $cmds);
			$cmd=$cmd." >/dev/null 2>&1 &";
			echo "Stopping MySQL...............: Stopping smoothly mysqld pid:$pid\n";
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $cmd\n";}
			for($i=0;$i<10;$i++){
				sleep(1);
				$pid=PID_NUM();  
				if(!$unix->process_exists($pid,$mysqlbin)){break;}
				echo "Stopping MySQL...............: Stopping, please wait $i/10\n";
			}
		}
	}
	
	$pid=PID_NUM();  	
	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		system_admin_events("Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__, "services");
		return;
	}
		
	echo "Stopping MySQL...............: killing smoothly PID $pid\n";
	shell_exec("$kill $pid");
	for($i=0;$i<5;$i++){
		sleep(1);
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid,$mysqlbin)){break;}
	}	
	
	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		system_admin_events("Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__, "services");
		return;
	}
	
	echo "Stopping MySQL...............: Force killing PID $pid\n";
	shell_exec("$kill -9 $pid");
	for($i=0;$i<5;$i++){
		sleep(1);
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid,$mysqlbin)){break;}
	}	

	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		system_admin_events("Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__, "services");
		return;
	}	
	

	echo "Stopping MySQL...............: failed\n";
	
}

function SERVICE_START($nochecks=false,$nopid=false){

	$unix=new unix();
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	
	
	if(!$nopid){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			echo "Starting......: MySQL this script is already executed PID: $oldpid since {$time}Mn\n";
			if($time<5){if(!$GLOBALS["FORCE"]){return;}}
			shell_exec("$kill -9 $oldpid");
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
		
		
	
	if(is_file("/etc/artica-postfix/mysql.stop")){echo "Starting......: MySQL locked, exiting\n";return;}
	
	$PID_NUM=PID_NUM();
	if($unix->process_exists($PID_NUM)){
		$timemin=$unix->PROCCESS_TIME_MIN($PID_NUM);
		echo "Starting......: MySQL already running PID \"$PID_NUM\" since {$timemin}Mn\n";
		return;
	}	
	
	$mysql_install_db=$unix->find_program('mysql_install_db');
	$mysqlbin=$unix->LOCATE_mysqld_bin();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	if(!is_file($mysqlbin)){echo "Starting......: MySQL is not installed, abort\n";return;}

	$EnableMysqlFeatures=$sock->GET_INFO('EnableMysqlFeatures');
	$MysqlBinAllAdresses=$sock->GET_INFO('MysqlBinAllAdresses');
	$MySQLTMPMEMSIZE=$sock->GET_INFO('MySQLTMPMEMSIZE');
	$MysqlTooManyConnections=$sock->GET_INFO("MysqlTooManyConnections");
	$MysqlRemoveidbLogs=$sock->GET_INFO("MysqlRemoveidbLogs");
	$innodb_force_recovery=$sock->GET_INFO("innodb_force_recovery");
	if(!is_numeric($innodb_force_recovery)){$innodb_force_recovery=0;}
	
	if(!is_numeric($MysqlRemoveidbLogs)){$MysqlRemoveidbLogs=0;}
	if(!is_numeric($MysqlBinAllAdresses)){$MysqlBinAllAdresses=0;}
	if(!is_numeric($MySQLTMPMEMSIZE)){$MySQLTMPMEMSIZE=0;}
	if(!is_numeric($MysqlTooManyConnections)){$MysqlTooManyConnections=0;}
	if(!is_numeric($EnableMysqlFeatures)){$EnableMysqlFeatures=1;}
	$MySqlTmpDir=$sock->GET_INFO('MySQLTMPDIR');
	$MySQLLOgErrorPath=$sock->GET_INFO('MySQLLOgErrorPath');
	$datadir=$unix->MYSQL_DATA_DIR();
	$EnableMysqlLog=$sock->GET_INFO("EnableMysqlLog");
	if(!is_numeric($EnableMysqlLog)){$EnableMysqlLog=0;}
	

	if($datadir==null){$datadir='/var/lib/mysql';}
	if($MySqlTmpDir=='/tmp'){$MySqlTmpDir=null;}
	if($MySQLLOgErrorPath==null){$MySQLLOgErrorPath=$datadir.'/mysqld.err';}

	if($MysqlTooManyConnections==1){echo "Starting......: MySQL MysqlTooManyConnections=1, abort\n";return;}
	if(isset($GLOBALS["RECOVERY"])){$innodb_force_recovery=$GLOBALS["RECOVERY"];}

if(strlen($MySqlTmpDir)>3){
        echo "Starting......: MySQL tempdir : $MySqlTmpDir\n";
       shell_exec("$php5 /usr/share/artica-postfix/exec.mysql.build.php --tmpfs");
       $MySqlTmpDir=str_replace("//", "/", $MySqlTmpDir);
       if(!is_dir($MySqlTmpDir)){
          @mkdir($MySqlTmpDir,0755,true);
          $unix->chown_func("mysql","mysql", $MySqlTmpDir);
       }
       $MySqlTmpDirCMD=" --tmpdir=$MySqlTmpDir";
}

if($EnableMysqlFeatures==0){
 	echo "Starting......: MySQL is disabled by \"EnableMysqlFeatures\"...\n";	
 	return;
}

	$pid_file="/var/run/mysqld/mysqld.pid";
	$socket="/var/run/mysqld/mysqld.sock";
	$mysql_user="mysql";
	@mkdir("/var/run/mysqld",0755,true);
	@mkdir("/var/log/mysql",0755,true);
	@mkdir($datadir,0755,true);
	
	
	$bind_address=' --bind-address=127.0.0.1';
	$bind_address2="127.0.0.1";
	  if($MysqlBinAllAdresses==1){
	      $bind_address2='All (0.0.0.0)';
	      $bind_address=' --bind-address=0.0.0.0';
	  }

   echo "Starting......: MySQL Pid path.......:$pid_file\n";
   echo "Starting......: datadir..............:$datadir\n";
   echo "Starting......: Log error............:$MySQLLOgErrorPath\n";
   echo "Starting......: socket...............:$socket\n";
   echo "Starting......: user.................:$mysql_user\n";
   echo "Starting......: LOGS ENABLED.........:$EnableMysqlLog\n";
   echo "Starting......: Daemon...............:$mysqlbin\n";
   echo "Starting......: Bind address.........:$bind_address2\n";
   echo "Starting......: Temp Dir.............:$MySqlTmpDir\n";
   echo "Starting......: innodb_force_recovery:$innodb_force_recovery\n";
   
   
   echo "Starting......: Settings permissions..\n";
   $unix->chown_func($mysql_user,$mysql_user, "/var/run/mysqld");
   $unix->chown_func($mysql_user,$mysql_user, "/var/log/mysql");
   $unix->chown_func($mysql_user,$mysql_user, $datadir);
   $unix->chown_func($mysql_user,$mysql_user, "$datadir/*");

   if($MysqlRemoveidbLogs==1){
        shell_exec('/bin/mv /var/lib/mysql/ib_logfile* /tmp/');
       $sock->SET_INFO('MysqlRemoveidbLogs','0');
   }
   
   
   $logpathstring=" --log-error=$MySQLLOgErrorPath";
   if($EnableMysqlLog==1){$logpathstring=" --log=/var/log/mysql.log --log-slow-queries=/var/log/mysql-slow-queries.log --log-error=$MySQLLOgErrorPath --log-warnings";}
   
   $toTouch[]="/var/log/mysql-slow-queries.log";
   $toTouch[]="/var/log/mysql.error";
   $toTouch[]="/var/log/mysql.log";
   $toTouch[]="/var/log/mysql.warn";
	
   while (list ($num, $filename) = each ($toTouch) ){
   		if(!is_file($filename)){@file_put_contents($filename, "#\n");}
   		$unix->chown_func($mysql_user,$mysql_user, $filename);
   }

	


   echo "Starting......: MySQL Checking : $datadir/mysql/host.frm\n";
   if(!is_file("$datadir/mysql/host.frm")){
	    if(is_file($mysql_install_db)){
	        echo "Starting......: MySQL installing default databases\n";
	        shell_exec("$mysql_install_db --datadir=\"$datadir\"");
	    	}
	}else{
		echo "Starting......: MySQL Checking : $datadir/mysql/host.frm OK\n";
	}


   if(is_file('/var/run/mysqld/mysqld.err')){@unlink('/var/run/mysqld/mysqld.err');}
   if(is_file("/var/run/mysqld/mysqld.pid")){$unix->chown_func($mysql_user,$mysql_user, "/var/run/mysqld/mysqld.pid");}
   
   if(is_file($MySQLLOgErrorPath)){@unlink($MySQLLOgErrorPath);}
	$cmds[]=$mysqlbin;
	$cmds[]="--pid-file=/var/run/mysqld/mysqld.pid";
	$cmds[]=$logpathstring;
	$cmds[]=$MySqlTmpDirCMD;
	$cmds[]="--socket=$socket";
	$cmds[]="--datadir=\"$datadir\"";
	if($innodb_force_recovery>0){
		$cmds[]="--innodb-force-recovery=$innodb_force_recovery";
	}
	$cmds[]=">/dev/null 2>&1 &";
	if(is_file('/usr/sbin/aa-complain')){
        echo "Starting......: Mysql adding mysql in apparamor complain mode...\n";
        shell_exec("/usr/sbin/aa-complain $mysqlbin >/dev/null 2>&1");
	}
	
	$cmd=@implode(" ", $cmds);
	while (list ($num, $ligne) = each ($cmds) ){
		echo "Starting......: MySQL option: $ligne\n";
	}
	
	echo "Starting......: MySQL starting daemon, please wait\n";
	writelogs("Starting MySQL $cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$count=0;
    sleep(2);
    
    
    
   for($i=0;$i<6;$i++){
   		$pid=PID_NUM();
   		if($unix->process_exists($pid,$mysqlbin)){
   			echo "Starting......: MySQL checks daemon running...\n";
   			break;
   		}
   		echo "Starting......: MySQL checks daemon, please wait ($i/6)\n";
   		sleep(1);
   }

   $pid=PID_NUM();
   if(!$unix->process_exists($pid)){
   	echo "Starting......: MySQL failed\n";
   	echo "Starting......: $cmd\n";
   	system_admin_events("Failed to start MySQL server", __FUNCTION__, __FILE__, __LINE__, "services");
   }else{
   		system_admin_events("Success to start MySQL server pid $pid", __FUNCTION__, __FILE__, __LINE__, "services");
   		echo "Starting......: MySQL success pid $pid\n";
   	
   }

   
}

function status_all_mysql_engines(){
	$unix=new unix();
	if(systemMaxOverloaded()){return;}
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/MYSQLDB_STATUS";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($cachefile);
		if($time<60){return;}
	}
	
	
	
	$sock=new sockets();
	$datadir=$unix->MYSQL_DATA_DIR();
	
	$array["APP_MYSQL_ARTICA"]["size"]=$unix->DIRSIZE_BYTES($datadir);
	$array["APP_MYSQL_ARTICA"]["INFO"]=$unix->DIRPART_INFO($datadir);
	
	if(is_dir("/opt/articatech/mysql")){
		$array["APP_ARTICADB"]["size"]=$unix->DIRSIZE_BYTES("/opt/articatech");
		$array["APP_ARTICADB"]["INFO"]=$unix->DIRPART_INFO("/opt/articatech");
		
	}
	
	if(is_dir("/opt/squidsql")){
		$array["APP_SQUID_DB"]["size"]=$unix->DIRSIZE_BYTES("/opt/squidsql");
		$array["APP_SQUID_DB"]["INFO"]=$unix->DIRPART_INFO("/opt/squidsql");
		
	}
	
	$MySQLSyslogWorkDir=$sock->GET_INFO("MySQLSyslogWorkDir");
	if($MySQLSyslogWorkDir==null){$MySQLSyslogWorkDir="/home/syslogsdb";}	
	
	if(is_dir($MySQLSyslogWorkDir)){
		$array["MYSQL_SYSLOG"]["size"]=$unix->DIRSIZE_BYTES($MySQLSyslogWorkDir);
		$array["MYSQL_SYSLOG"]["INFO"]=$unix->DIRPART_INFO($MySQLSyslogWorkDir);		
	}
	if($GLOBALS["VERBOSE"]){print_r($array);}
	@unlink($cachefile);
	@file_put_contents($cachefile, base64_encode(serialize($array)));
	@chmod($cachefile, 0777);
	
}
function clean_events(){
	$q=new mysql();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$TABLES=$q->LIST_TABLES_EVENTS_SYSTEM();
	while (list ($tablename, $line) = each ($TABLES) ){
		echo "DROP $tablename\n";
		$q->QUERY_SQL("DROP TABLE `$tablename`","artica_events");
	}
	$datadir=$unix->MYSQL_DATA_DIR();
	shell_exec("$rm -f $datadir/artica_events/*.BAK");
	if(is_dir("$datadir/syslogstore")){
		$q->DELETE_DATABASE("syslogstore");
		shell_exec("$rm -f $datadir/syslogstore/*.BAK");
	}
	
	$files=$unix->DirFiles("$datadir/artica_events","TaskSq[0-9]+\.MYI");
	while (list ($file, $line) = each ($files) ){
		$file=str_replace(".MYI", "", $file);
		$q->QUERY_SQL("DROP TABLE `$file`","artica_events");
		
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `nmap_events`","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `nmap_events`","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `avgreports`","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `events`","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `dhcpd_logs`","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `update_events`","artica_events");
	shell_exec("$nohup /etc/init.d/mysql restart >/dev/null 2>&1 &");
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.mysql.start.php --engines --verbose 2>&1 &");
	
	
}

?>