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
$GLOBALS["PID_PATH"]="/var/run/roundcube-db.pid";
$GLOBALS["SRV_NAME"]="roundcube-db";
$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/roundcube-db.sock";
$GLOBALS["MYPID_PATH"]="/etc/artica-postfix/pids/roundcubedbstart.pid";
$GLOBALS["DBCACHE_PATH"]="/usr/share/artica-postfix/ressources/logs/web/roundcubedb.size.db";
$GLOBALS["WORK_DIR_TOKEN"]="RoundCubeDedicateMySQLWorkDir";
$GLOBALS["WORK_DIR_DEFAULT"]="/home/roundcube-db";
$GLOBALS["DATABASE_STATS_PID"]="/etc/artica-postfix/pids/roundcube-databasesize.pid";
$GLOBALS["MYSQL_TOKENS"]="RoundCubeTuningParameters";

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.services.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd();die();}
if($argv[1]=="--changemysqldir"){changemysqldir($argv[2]);die();}
if($argv[1]=="--databasesize"){databasesize($GLOBALS["FORCE"]);die();}




function get_memory(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}return 0;
}
function get_swap(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}return 0;
}

function upgrade(){
	$GLOBALS["NOPID"]=true;
	$GLOBALS["OUTPUT"]=true;
	stop();
	start(true);
	$unix=new unix();
	$mysql_upgrade=$unix->find_program("mysql_upgrade");

	echo "Starting......: **************************\n";
	echo "Starting......: [INIT]: Running upgrade $mysql_upgrade....\n";
	echo "Starting......: **************************\n";
	shell_exec("$mysql_upgrade -u root -S {$GLOBALS["MYSQL_SOCKET"]} --verbose");
	stop();
	start(false);
}


function start($nopid=false){
	$unix=new unix();
	
	$SERV_NAME=$GLOBALS["SRV_NAME"];
	$pidfile=$GLOBALS["MYPID_PATH"];
	if(!$nopid){
		$oldpid=$unix->get_pid_from_file($pidfile);
		$sock=new sockets();
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
		
	@file_put_contents($pidfile, getmypid());
		
	if(!is_dir($unix->LOCATE_ROUNDCUBE_WEBFOLDER())){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME RoundCube not installed...\n";}
		
	}
	
	$sock=new sockets();
	$RoundCubeDedicateMySQLServer=$sock->GET_INFO("RoundCubeDedicateMySQLServer");
	if(!is_numeric($RoundCubeDedicateMySQLServer)){$RoundCubeDedicateMySQLServer=0;}
	
	if($RoundCubeDedicateMySQLServer==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME is not Enabled...\n";}
		return;
	}	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME mysqld no such binary\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME is not installed...\n";}
		return;
	}	

	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME writing init.d\n";}
	initd();
	$WORKDIR=$sock->GET_INFO($GLOBALS["WORK_DIR_TOKEN"]);
	if($WORKDIR==null){$WORKDIR=$GLOBALS["WORK_DIR_DEFAULT"];}
	$mysqlserv=new mysql_services();
	$mysqlserv->WORKDIR=$WORKDIR;
	$mysqlserv->MYSQL_PID_FILE=$GLOBALS["PID_PATH"];
	$mysqlserv->MYSQL_SOCKET=$GLOBALS["MYSQL_SOCKET"];;
	$mysqlserv->SERV_NAME=$SERV_NAME;
	$mysqlserv->TokenParams=$GLOBALS["MYSQL_TOKENS"];
	$mysqlserv->InnoDB=false;
	
	
	$TMP=$unix->FILE_TEMP();
	
	$cmdline=$mysqlserv->BuildParams();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME Starting MySQL daemon\n";}
	shell_exec("$nohup $cmdline >/dev/null 2>&1 &");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=SERVICEDB_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME MySQL daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	
	$pid=SERVICEDB_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME MySQL daemon failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME $ligne\n";}
		}
	
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $SERV_NAME $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}


function stop(){
	$unix=new unix();
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $SERV_NAME Already task running PID $oldpid since {$time}mn\n";}
		return;
	}

	$pid=SERVICEDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $SERV_NAME MySQL daemon already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $SERV_NAME Stopping Daemon with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: $SERV_NAME Stopping smoothly...\n";}
	$cmd="$mysqladmin --socket={$GLOBALS["MYSQL_SOCKET"]}  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=SERVICEDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=SERVICEDB_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon kill pid $pid..\n";}
			shell_exec("$kill -9 $pid");
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	$pid=SERVICEDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon Failed...\n";}
}

function SERVICEDB_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file($GLOBALS["PID_PATH"]);
	if($unix->process_exists($pid)){return $pid;}
	$mysqld=$unix->find_program("mysqld");
	$pid=$unix->PIDOF_PATTERN("$mysqld.*?--pid-file={$GLOBALS["PID_PATH"]}");
	return $pid;
	
	
}





function initd(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          roundcube-db";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: RoundCube MySQL database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: RoundCube MySQL database";
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
	@file_put_contents("/etc/init.d/roundcube-db", @implode("\n", $f));
	@chmod("/etc/init.d/roundcube-db",0755);


	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f roundcube-db defaults >/dev/null 2>&1');
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add roundcube-db >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 roundcube-db on >/dev/null 2>&1');
	}
}

function databasesize($force=false){
	
	$sock=new sockets();
	$unix=new unix();
	$arrayfile=$GLOBALS["DBCACHE_PATH"];
	
	
	
	if(!$force){
		$pidfile=$GLOBALS["DATABASE_STATS_PID"];
		
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($time<20){return;}
	}
	
	$WORKDIR=$sock->GET_INFO($GLOBALS["WORK_DIR_TOKEN"]);
	if($WORKDIR==null){$WORKDIR=$GLOBALS["WORK_DIR_DEFAULT"];}
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
	@file_put_contents($arrayfile, serialize($array));
	if($GLOBALS["VERBOSE"]) {echo "Saving $arrayfile...\n";}
	
	@chmod($arrayfile, 0755);
	
}
?>