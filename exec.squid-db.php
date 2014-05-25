<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["NOPID"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.services.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd();die();}
if($argv[1]=="--changemysqldir"){changemysqldir($argv[2]);die();}
if($argv[1]=="--databasesize"){databasesize($GLOBALS["FORCE"]);die();}
if($argv[1]=="--restorefrom"){RestoreFromBackup($argv[2]);die();}
if($argv[1]=="--keys"){GetStartedValues();die();}
if($argv[1]=="--checks"){checktables();die();}
if($argv[1]=="--statistics"){statistics();die();}
if($argv[1]=="--upgrade"){upgrade();die();}



function get_memory(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		
		if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){
			return $re[1];
		}
	}
	
	return 0;
}
function get_swap(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);

	while (list ($num, $ligne) = each ($results) ){

		if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){
			return $re[1];
		}
	}

	return 0;


}

function upgrade(){
	$GLOBALS["NOPID"]=true;
	$GLOBALS["OUTPUT"]=true;
	stop();
	
	
	
	@unlink("/opt/squidsql/share/mysql/english/errmsg.sys");
	start(true);
	$unix=new unix();
	$mysql_upgrade=$unix->find_program("mysql_upgrade");
	
	echo "Starting......: ".date("H:i:s")." **************************\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Running upgrade $mysql_upgrade....\n";
	echo "Starting......: ".date("H:i:s")." **************************\n";
	shell_exec("$mysql_upgrade -u root -S /var/run/mysqld/squid-db.sock --verbose");
	stop();
	start(false);	
}

function Get_errmsgsys(){
	
	$f[]="/usr/share/mysql/english/errmsg.sys";
	
	while (list ($num, $ligne) = each ($f) ){
		if(is_file($ligne)){return $ligne;}
	}
	
}

function checktables(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$q->CheckTablesICAP();
}


function GetStartedValues(){
	$unix=new unix();
	$mysqld=$unix->find_program("mysqld");
	exec("$mysqld --help --verbose 2>&1",$results);
	
	while (list ($key, $valueN) = each ($results) ){
		if(preg_match("#--([a-z\-\_\=]+)\s+(.+)#", $valueN,$re)){		
			$key=trim($re[1]);
			$value=trim($re[2]);
			$array["--$key"]=true;
		}
			
	}
	
	return $array;
}

function ToCopy($WORKDIR){
	$WORKDIR="/opt/squidsql";
	$f[]="host.frm";
	$f[]="host.MYD";
	$f[]="host.MYI";
	
	
	$f[]="servers.frm";
	$f[]="servers.MYD";
	$f[]="servers.MYI";
	
	
	
	while (list ($key, $filename) = each ($results) ){
		if(!is_file("$WORKDIR/mysql/$filename")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: copy /var/lib/mysql/mysql/$filename $WORKDIR/mysql/$filename\n";}
			@copy("/var/lib/mysql/mysql/$filename $WORKDIR/mysql/$filename");
		}
		
	}
	
	
}

function statistics(){
	$pidfile="/etc/artica-postfix/pids/squiddbstats.pid";
	$unix=new unix();
	$oldpid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}	
	
	
	$Socket="/var/run/mysqld/squid-db.sock";
	$mysqladmin=$unix->find_program("mysqladmin");
	$cmdline="$mysqladmin -S $Socket -u root status 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
	exec($cmdline,$results);
	$date=date("Y-m-d H:i:s");
	if(!preg_match("#Uptime:\s+([0-9]+)\s+Threads:\s+([0-9]+)\s+Questions:\s+([0-9]+)\s+Slow queries:\s+([0-9]+)\s+Opens:\s+([0-9]+)\s+Flush tables:\s+([0-9]+)\s+Open tables:\s+([0-9]+)\s+ Queries per second avg:\s+([0-9]+)#",@implode("", $results),$re)){
		if($GLOBALS["VERBOSE"]){echo @implode("", $results)." no match..\n";}
		return;
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `MySQLStats` (
	`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`uptime` BIGINT UNSIGNED NOT NULL,
	`threads` BIGINT(10) NOT NULL,
	`questions` BIGINT UNSIGNED NOT NULL,
	`squeries` BIGINT(10) NOT NULL,
	`opens` BIGINT UNSIGNED NOT NULL,
	`ftables` BIGINT(20) NOT NULL,
	`open` BIGINT(10) NOT NULL,
	`queriesavg` BIGINT UNSIGNED NOT NULL,
	 UNIQUE KEY `zDate` (`zDate`),
	 KEY `uptime` (`uptime`),
	 KEY `threads` (`threads`),
	 KEY `questions` (`questions`),
	 KEY `squeries` (`squeries`),
	 KEY `opens` (`opens`),
	 KEY `ftables` (`ftables`),
	 KEY `open` (`open`),											
	 KEY `queriesavg` (`queriesavg`)
	)";	
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
	if($GLOBALS["VERBOSE"]){echo $sql."\n$q->mysql_error\n";}
	return;}
	
	
	$sql="INSERT IGNORE INTO MySQLStats (zDate,uptime,threads,questions,squeries,opens,ftables,open,queriesavg)
	VALUES('$date','{$re[1]}','{$re[2]}','{$re[3]}','{$re[4]}','{$re[5]}','{$re[6]}','{$re[7]}','{$re[8]}')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){
			echo $sql."\n$q->mysql_error\n";}
	}
	if($GLOBALS["VERBOSE"]){echo $sql."\nOK\n";}
	
	
	
	
	
	
}
function squid_watchdog_events($text){
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function start($skipGrant=false){
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){return;}}
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/squiddbstart.pid";
	
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}
	$SERV_NAME="squid-db";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lnbin=$unix->find_program("ln");
	if(!$GLOBALS["NOPID"]){
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	$GetStartedValues=GetStartedValues();
	$sock=new sockets();
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}

	
	if($ProxyUseArticaDB==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is disabled...\n";}
		stop();
		return;		
		
	}
		
	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	$pid=SQUIDDB_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME MySQL Database Engine already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME writing init.d\n";}
	initd();
	
	
	$memory=get_memory();
	$swap=get_swap();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME Server available memory `{$memory}MB`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME Server available swap `{$swap}MB`\n";}
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
	$ListenPort=$SquidDBTuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if($ListenPort==0){
		$ListenPort=rand(8900, 45890);
		$SquidDBTuningParameters["ListenPort"]=$ListenPort;
		$sock->SET_INFO("SquidDBTuningParameters", base64_encode(serialize($SquidDBTuningParameters)));
	}
	
	
	
	@mkdir($WORKDIR,0755,true);
	$mysqlserv=new mysql_services();
	$mysqlserv->WORKDIR=$WORKDIR;
	$mysqlserv->MYSQL_PID_FILE="/var/run/squid-db.pid";
	$mysqlserv->MYSQL_SOCKET="/var/run/mysqld/squid-db.sock";
	$mysqlserv->SERV_NAME=$SERV_NAME;
	$mysqlserv->TokenParams="SquidDBTuningParameters";
	$mysqlserv->INSTALL_DATABASE=true;
	$mysqlserv->MYSQL_BIN_DAEMON_PATH=$unix->find_program("mysqld");
	//$mysqlserv->MYSQL_ERRMSG=$GLOBALS["MYSQL_ERRMSG"];
	$mysqlserv->InnoDB=false;
	
	
	$cmdline=$mysqlserv->BuildParams();
	
	
	
	
	

	$CREATEDB=false;
	
	
	if(!is_file("$WORKDIR/my.cnf")){
			@file_put_contents("$WORKDIR/my.cnf", "\n");
	}
	
	if(!is_file("$WORKDIR/bin/my_print_defaults")){
		$my_print_defaults=$unix->find_program("my_print_defaults");
		shell_exec("$lnbin -s $my_print_defaults $WORKDIR/bin/my_print_defaults");
	}
	
	if(!is_file("$WORKDIR/data/mysql/user.MYD")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME Installing defaults databases, Please Wait...\n";}
		install_db($WORKDIR);
		$CREATEDB=true;
	}
	
	$topCopyMysql["host.frm"]=true;
	$topCopyMysql["host.MYD"]=true;
	$topCopyMysql["host.MYI"]=true;
	
	$topCopyMysql["servers.frm"]=true;
	$topCopyMysql["servers.MYD"]=true;
	$topCopyMysql["servers.MYI"]=true;
	
	
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	
	$topCopyMysqlForce["tables_priv.frm"]=true;
	$topCopyMysqlForce["tables_priv.MYD"]=true;
	$topCopyMysqlForce["tables_priv.MYI"]=true;
	$topCopyMysqlForce["columns_priv.frm"]=true;
	$topCopyMysqlForce["columns_priv.MYD"]=true;
	$topCopyMysqlForce["columns_priv.MYI"]=true;
	$topCopyMysqlForce["procs_priv.frm"]=true;
	$topCopyMysqlForce["procs_priv.MYD"]=true;
	$topCopyMysqlForce["procs_priv.MYI"]=true;	
	
	$topCopyMysqlForce["plugin.frm"]=true;
	$topCopyMysqlForce["plugin.MYD"]=true;
	$topCopyMysqlForce["plugin.MYI"]=true;	
	
	$topCopyMysqlForce["user.frm"]=true;
	$topCopyMysqlForce["user.MYD"]=true;
	$topCopyMysqlForce["user.MYI"]=true;

	$topCopyMysqlForce["db.frm"]=true;
	$topCopyMysqlForce["db.MYD"]=true;
	$topCopyMysqlForce["db.MYI"]=true;	
	
	$ToCopyForce=false;
	while (list ($filename, $ligne) = each ($topCopyMysql) ){
		if(!is_file("$WORKDIR/data/mysql/$filename")){
			$ToCopyForce=true;
			if(is_file("$MYSQL_DATA_DIR/mysql/$filename")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Installing $filename\n";}
				@copy("$MYSQL_DATA_DIR/mysql/$filename", "$WORKDIR/data/mysql/$filename");
				$CREATEDB=true;
			}
		}
	}
	
	
		while (list ($filename, $ligne) = each ($topCopyMysqlForce) ){
			if(!is_file("$WORKDIR/data/mysql/$filename")){
				if(is_file("$MYSQL_DATA_DIR/mysql/$filename")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Installing $filename\n";}
					@copy("$MYSQL_DATA_DIR/mysql/$filename", "$WORKDIR/data/mysql/$filename");
				}
			}
		}
	
	


	
	
	
	
	@mkdir("$WORKDIR/share/mysql/english",0755,true);
	$Get_errmsgsys=Get_errmsgsys();
	
	if(!is_file("$WORKDIR/share/mysql/english/errmsg.sys")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Creating errmsg.sys\n";}
		ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		if(is_file($Get_errmsgsys)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: copy $Get_errmsgsys -> $WORKDIR/share/mysql/english/errmsg.sys\n";}
			copy(Get_errmsgsys(), "$WORKDIR/share/mysql/english/errmsg.sys");
		}else{
			file_put_contents("$WORKDIR/share/mysql/english/errmsg.sys", "\n");
		}
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: errmsg.sys OK\n";}
	}
	
	
	
	
	
	
	$TMP=$unix->FILE_TEMP();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting MySQL daemon ($SERV_NAME)\n";}
	
	@unlink("$WORKDIR/error.log");
	
	
	shell_exec("$nohup $cmdline >$TMP 2>&1 &");
	sleep(1);
	for($i=0;$i<5;$i++){
		$pid=SQUIDDB_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon wait $i/5\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=SQUIDDB_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) failed to start\n";}
		if(is_file($TMP)){
			$f=explode("\n",@file_get_contents($TMP));
			while (list ($num, $ligne) = each ($f) ){if(trim($ligne)==null){continue;}if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}}
		}
		
		$f=explode("\n", @file_get_contents("$WORKDIR/error.log"));
		while (list ($num, $ligne) = each ($f) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL Results \"$ligne\"\n";}
			if(preg_match("#Incorrect information in file: './mysql/proxies_priv.frm'#", $ligne)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: remove MySQL tables and install again...\n";}
				shell_exec("/bin/rm -rf $WORKDIR/data/mysql/*");
				shell_exec("$nohup $php5 ".__FILE__." --start --recall >/dev/ null 2>&1 &");
				return;
			}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success\n";}
		if($CREATEDB){$q=new mysql_squid_builder();$q->CheckTables();}
		$q=new mysql_squid_builder();
		$q->MEMORY_TABLES_RESTORE();		
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}


function stop(){
	$SERV_NAME="squid-db";
	$unix=new unix();
	if(!$GLOBALS["NOPID"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) already stopped...\n";}
		return;
	}	
	
	
	$q=new mysql_squid_builder();
	$q->MEMORY_TABLES_DUMP();
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) smoothly...\n";}
	$cmd="$mysqladmin --socket=/var/run/mysqld/squid-db.sock  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=SQUIDDB_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) kill pid $pid..\n";}
			shell_exec("$kill -9 $pid");
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) wait $i/10\n";}
		sleep(1);
	}	
	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) Failed...\n";}
}

function SQUIDDB_PID(){
	$unix=new unix();
	$sock=new sockets();
	$Socket="/var/run/mysqld/squid-db.sock";
	$pid=$unix->get_pid_from_file("/var/run/squid-db.pid");
	if($unix->process_exists($pid)){return $pid;}
	$SquidStatsDatabasePath=$sock->GET_INFO("SquidStatsDatabasePath");
	if($SquidStatsDatabasePath==null){$SquidStatsDatabasePath="/opt/squidsql";}
	$mysqld=$unix->find_program("mysqld");
	
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}
	
	return $unix->PIDOF_PATTERN("$mysqld.*?$Socket");
	
}


function changemysqldir($dir){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/squiddbstart.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	initd();
	$dirCMD=$unix->shellEscapeChars($dir);
	if($dir=="/opt/squidsql/data"){return;}
	@mkdir($dir,0755,true);
	
	echo "Moving......: [INIT]: Calculate disk size\n";
	$Size=$unix->DIRSIZE_BYTES("/opt/squidsql/data");
	echo "Moving......: [INIT]: Stopping Squid-db Size: $Size\n";
	system("/etc/init.d/squid-db stop");
	echo "Moving......: [INIT]: Copy /opt/squidsql/data content to next dir size=$Size\n";
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	shell_exec("$cp -rf /opt/squidsql/data/* $dirCMD/");
	$Size2=$unix->DIRSIZE_BYTES($dir);
	if($Size2<$Size){
		echo "Moving......: [INIT]: Copy error $Size2 is less than original size ($Size)\n";
	}
	echo "Moving......: [INIT]: Removing old data\n";
	shell_exec("$rm -rf /opt/squidsql/data");
	echo "Moving......: [INIT]: Create a new symbolic link...\n";
	shell_exec("$ln -s $dirCMD /opt/squidsql/data");
	echo "Moving......: [INIT]: Starting MySQL database engine...\n";
	system("/etc/init.d/squid-db start");
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}
function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
	
}


function initd(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squid-db";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Squid MySQL Statistics database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Squid MySQL Statistics database";
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
	
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	@file_put_contents("/etc/init.d/squid-db", @implode("\n", $f));
	@chmod("/etc/init.d/squid-db",0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f squid-db defaults >/dev/null 2>&1');
		
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add squid-db >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 squid-db on >/dev/null 2>&1');
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon (squid-db) success...\n";}
}

function databasesize($force=false){
	
	
	$unix=new unix();
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/squiddb.size.db";
	
	
	
	if(!$force){
		$pidfile="/etc/artica-postfix/pids/squid-databasesize.pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($time<20){return;}
	}
	
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}
	if(!is_dir($WORKDIR)){@mkdir($WORKDIR,0755,true);}
	$dir=$WORKDIR;
	
	if(is_link($dir)){$dir=readlink($dir);}
	$unix=new unix();
	$sizbytes=$unix->DIRSIZE_BYTES($dir);
	$dir=$unix->shellEscapeChars($dir);
	$df=$unix->find_program("df");
	$array["DBSIZE"]=$sizbytes/1024;
	exec("$df -B K $dir 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["SIZE"]=$re[1];
			$array["USED"]=$re[2];
			$array["AIVA"]=$re[3];
			$array["POURC"]=$re[4];
			$array["MOUNTED"]=$re[5];
			break;
		}
	}
	$results=array();
	exec("$df -i $dir 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["ISIZE"]=$re[1];
			$array["IUSED"]=$re[2];
			$array["IAIVA"]=$re[3];
			$array["IPOURC"]=$re[4];
			break;
		}
	}	

	if($GLOBALS["VERBOSE"]) {print_r($array);}
	
	@unlink($arrayfile);
	$q=new mysql_squid_builder();
	$TABLES_NUMBER=$q->COUNT_ALL_TABLES();
	$array["TABLES_NUMBER"]=$TABLES_NUMBER;
	@file_put_contents($arrayfile, serialize($array));
	if($GLOBALS["VERBOSE"]) {echo "Saving $arrayfile...\n";}
	@chmod($arrayfile, 0755);
	
}

function install_db($WORKDIR){
	$unix=new unix();
	$mysqld_safe=$unix->find_program("mysqld_safe");
	$mysql_install_db=$unix->find_program("mysql_install_db");
	$cp=$unix->find_program("cp");
	//shell_exec("cp -f /var/lib/mysql/mysql/plugin.* /opt/squidsql/data/mysql/");
	if(is_file("$WORKDIR/data/ibdata1")){
		@unlink("$WORKDIR/data/ibdata1");
		@unlink("$WORKDIR/data/ib_logfile0");
		@unlink("$WORKDIR/data/ib_logfile1");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $WORKDIR/ibdata1 no such file\n";}
	}
	
	if(is_file("/usr/share/mysql/mysql_system_tables.sql")){
		if(is_file($mysqld_safe)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $mysqld_safe with mysql_system_tables.sql\n";}
			$cmd="$mysqld_safe --defaults-file=/opt/squidsql/my.cnf --log-error=/opt/squidsql/error.log --user=root --socket=/var/run/mysqld/squid-db.sock2 --basedir=/opt/squidsql --datadir=/opt/squidsql/data --skip-networking --plugin_dir=/opt/squidsql/lib/plugin --init-file=/usr/share/mysql/mysql_system_tables.sql --verbose";
			shell_exec($cmd);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: `$cmd`\n";}
			
			
			if(is_file("/usr/share/mysql/mysql_system_tables_data.sql")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $mysqld_safe with mysql_system_tables_data.sql\n";}
				shell_exec("$mysqld_safe --defaults-file=/opt/squidsql/my.cnf --log-error=/opt/squidsql/error.log --socket=/var/run/mysqld/squid-db.sock2 --user=root --basedir=/opt/squidsql --datadir=/opt/squidsql/data --skip-networking --plugin_dir=/opt/squidsql/lib/plugin --init-file=/usr/share/mysql/mysql_system_tables_data.sql --verbose");
				
			}
		}
		
	}
	if(!is_file("$WORKDIR/data/mysql/user.MYD")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: checks with `$mysql_install_db`\n";}
		if(is_file("$mysql_install_db")){
			$cmd="$mysql_install_db --basedir=$WORKDIR --plugin_dir=$WORKDIR/lib/plugin --datadir=$WORKDIR/data --skip-name-resolve --user=root --force --no-defaults --log-error=/opt/squidsql/error.log >/dev/null 2>&1";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: `$cmd`\n";}
			shell_exec($cmd);
		}
	}
}
	
?>