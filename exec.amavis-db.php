<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["WORKDIR"]="/opt/amavisdb";
$GLOBALS["SERV_NAME"]="amavis-db";
$GLOBALS["MYPID"]="/etc/artica-postfix/pids/amavisdbstart.pid";
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
include_once(dirname(__FILE__).'/ressources/class.amavidb.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd();die();}
if($argv[1]=="--changemysqldir"){changemysqldir($argv[2]);die();}
if($argv[1]=="--databasesize"){databasesize($GLOBALS["FORCE"]);die();}
if($argv[1]=="--restorefrom"){RestoreFromBackup($argv[2]);die();}
if($argv[1]=="--keys"){GetStartedValues();die();}


function ToCopy($WORKDIR){
	$f[]="host.frm";
	$f[]="host.MYD";
	$f[]="host.MYI";
	while (list ($key, $filename) = each ($results) ){
		if(!is_file("$WORKDIR/mysql/$filename")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: copy /var/lib/mysql/mysql/$filename $WORKDIR/mysql/$filename\n";}
			@copy("/var/lib/mysql/mysql/$filename $WORKDIR/mysql/$filename");
		}
		
	}
}


function start(){
	$unix=new unix();
	$pidfile=$GLOBALS["MYPID"];
	$WORKDIR=$GLOBALS["WORKDIR"];
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$mysql_pid_file="/var/run/amavis-db.pid";
	$MYSQL_SOCKET="/var/run/mysqld/amavis-db.sock";
	$mysqlserv=new mysql_services();
	$mysqlserv->WORKDIR=$GLOBALS["WORKDIR"];
	$mysqlserv->MYSQL_PID_FILE=$mysql_pid_file;
	$mysqlserv->MYSQL_SOCKET=$MYSQL_SOCKET;
	$mysqlserv->SERV_NAME=$SERV_NAME;
	$mysqlserv->TokenParams="AmavisDBMysqlParams";
	$mysqlserv->InnoDB=true;
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	
	$AmavisPerUser=$sock->GET_INFO("AmavisPerUser");
	if(!is_numeric($AmavisPerUser)){$AmavisPerUser=0;}
	$EnableStopPostfix=$sock->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){$AmavisPerUser=0;}	
	
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$mysql_install_db=$unix->find_program("mysql_install_db");

	
	if($AmavisPerUser==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is disabled...\n";}
		stop();
		die(0);		
		
	}
		
	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	if(!is_file($mysql_install_db)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME mysql_install_db no such binary...\n";}
		return;
	}	
	
	
	$pid=DBPID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL Database Engine already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME writing init.d\n";}
	initd();
	$TMP=$unix->FILE_TEMP();
	$cmdline=$mysqlserv->BuildParams();
	
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}	

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME Starting MySQL daemon ($SERV_NAME)\n";}
	shell_exec("$nohup $cmdline >$TMP 2>&1 &");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=DBPID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=DBPID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME $ligne\n";}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) success\n";}
		$q=new amavisdb();
		$q->checkTables();
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}


function stop(){
	$SERV_NAME="amavis-db";
	$MYSQL_SOCKET="/var/run/mysqld/amavis-db.sock";
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME Already task running PID $pid since {$time}mn\n";}
		return;
	}

	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) smoothly...\n";}
	$cmd="$mysqladmin --socket=$MYSQL_SOCKET  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=DBPID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) wait $i/10\n";}
		sleep(1);
	}	
	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) Failed...\n";}
}

function DBPID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/amavis-db.pid");
	if($unix->process_exists($pid)){return $pid;}
}


function changemysqldir($dir){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/squiddbstart.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	initd();
	$dirCMD=$unix->shellEscapeChars($dir);
	if($dir=="/opt/squidsql/data"){return;}
	@mkdir($dir,0755,true);
	echo "Stopping Squid-db";
	shell_exec("/etc/init.d/squid-db stop");
	$Size=$unix->DIRSIZE_BYTES("/opt/squidsql/data");
	echo "Copy /home/zarafa-db content to next dir size=$Size";
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	shell_exec("$cp -rf /opt/squidsql/data/* $dirCMD/");
	$Size2=$unix->DIRSIZE_BYTES($dir);
	if($Size2<$Size){
		echo "Copy error $Size2 is less than original size ($Size)\n";
	}
	echo "Removing old data\n";
	shell_exec("$rm -rf /opt/squidsql/data");
	echo "Create a new symbolic link...\n";
	shell_exec("$ln -s $dirCMD /opt/squidsql/data");
	echo "Starting MySQL database engine...\n";
	shell_exec("/etc/init.d/squid-db start");
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
	$sock=new sockets();
	$AmavisPerUser=$sock->GET_INFO("AmavisPerUser");
	if(!is_numeric($AmavisPerUser)){$AmavisPerUser=0;}
	if($AmavisPerUser==0){
		echo "Starting......: ".date("H:i:s")." [INIT]:{$GLOBALS["SERV_NAME"]} feature (AmavisPerUser) is disabled\n";
		return;}

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          {$GLOBALS["SERV_NAME"]}";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Amavis MySQL Engine database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Amavis MySQL Engine database";
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
	@file_put_contents("/etc/init.d/amavis-db", @implode("\n", $f));
	@chmod("/etc/init.d/amavis-db",0755);
	echo "Starting......: ".date("H:i:s")." [INIT]:{$GLOBALS["SERV_NAME"]} /etc/init.d/amavis-db done..\n";
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f amavis-db defaults >/dev/null 2>&1');
		
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add amavis-db >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 amavis-db on >/dev/null 2>&1');
	}
}

function databasesize($force=false){
	
	
	$unix=new unix();
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/amavisdb.size.db";
	
	
	
	if(!$force){
		$pidfile="/etc/artica-postfix/pids/amavis-databasesize.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($arrayfile<20){return;}
	}
	
	
	$dir=$GLOBALS["WORKDIR"]."/data";
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
	@file_put_contents($arrayfile, serialize($array));
	if($GLOBALS["VERBOSE"]) {echo "Saving $arrayfile...\n";}
	@chmod($arrayfile, 0755);
	
}
?>