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
if($argv[1]=="--restorefrom"){$GLOBALS["OUTPUT"]=true;RestoreFromBackup($argv[2]);die();}
if($argv[1]=="--start-server"){zarafa_server2_start();die();}
if($argv[1]=="--stop-server"){zarafa_server2_stop();die();}
if($argv[1]=="--isrun"){zarafa_db_is_run();die();}
if($argv[1]=="--remove-database"){remove_database();exit;}
if($argv[1]=="--trash"){$GLOBALS["OUTPUT"]=true;remove_database(true);exit;}


function zarafa_db_is_run(){
	 $PID=ZARAFADB_PID();
	 $unix=new unix();
	 if(!$unix->process_exists($PID)){echo "Zarafa Database is not running\n";return;}
	 echo "Zarafa Database is running\n";
	
}

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


function start($nopid=false,$forceInnoDbRecover=false){
	$unix=new unix();
	
	$SERV_NAME="zarafa-db";	
	$pidfile="/etc/artica-postfix/pids/zarafadbstart.pid";
	

	
	
	if(!$nopid){
		$PidRestore="/etc/artica-postfix/pids/zarafaRestore.pid";
		$oldpid=$unix->get_pid_from_file($PidRestore);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restore Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
		
		
		$oldpid=$unix->get_pid_from_file($pidfile);
		$sock=new sockets();
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
		
	@file_put_contents($pidfile, getmypid());
		
	
	$sock=new sockets();
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	if($ZarafaDedicateMySQLServer==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME is not Enabled...\n";}
		return;
	}	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: mysqld no such binary\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME is not installed...\n";}
		return;
	}

	$PID=ZARAFADB_PID();
	$unix=new unix();
	if($unix->process_exists($PID)){
		$time=$unix->PROCCESS_TIME_MIN($PID);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME Already running since {$time}mn\n";}
		return;
	}
		
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: writing init.d\n";}
	initd();
	$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
	
	if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
	$mysql_pid_file="/var/run/zarafa-db.pid";
	$MYSQL_SOCKET="/var/run/mysqld/zarafa-db.sock";
	$mysqlserv=new mysql_services();
	$mysqlserv->WORKDIR=$WORKDIR;
	$mysqlserv->MYSQL_PID_FILE=$mysql_pid_file;
	$mysqlserv->MYSQL_SOCKET=$MYSQL_SOCKET;
	$mysqlserv->SERV_NAME=$SERV_NAME;
	$mysqlserv->TokenParams="ZarafaTuningParameters";
	$mysqlserv->InnoDB=true;
	$mysqlserv->forceInnoDbRecover=$forceInnoDbRecover;
	$mysqlserv->ForceHomeInnoDbDir=true;
	$mysqlserv->AsZarafa=true;
	
	$TMP=$unix->FILE_TEMP();
	
	$cmdline=$mysqlserv->BuildParams();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting MySQL daemon\n";}
	@unlink("/home/zarafa-db/error.log");
	shell_exec("$nohup $cmdline >/dev/null 2>&1 &");
	sleep(2);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Checking potentials errors\n";}
	if(ChecksError()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Error detected starting again\n";}
		@unlink("/home/zarafa-db/error.log");
		shell_exec("$nohup $cmdline >/dev/null 2>&1 &");
	}
	
	
	for($i=0;$i<10;$i++){
		$pid=ZARAFADB_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	
	$pid=ZARAFADB_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		}
	
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}

function ChecksError(){
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
	if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
	
	$f=explode("\n",@file_get_contents("$WORKDIR/error.log"));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Analyze ". count($f) ." line(s)\n";}
	
	while (list ($num, $ligne) = each ($f) ){
		if(!preg_match("#Error:#", $ligne)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		if(preg_match("#Error: log file.*?ib_logfile.*?\s+is of different size#",$ligne)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing iblog\n";}
			if(is_file("$WORKDIR/data/ibdata1")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ibdata1 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ibdata1");
			}
			
			if(is_file("$WORKDIR/data/ib_logfile0")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ib_logfile0 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ib_logfile0");
			}
			
			if(is_file("$WORKDIR/data/ib_logfile1")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ib_logfile1 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ib_logfile1");
			}
			
			return true;
		}
		
		if(preg_match("#InnoDB: Error: all log files must be created at the same time#",$ligne)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing iblog\n";}
			if(is_file("$WORKDIR/data/ibdata1")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ibdata1 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ibdata1");
			}
			
			if(is_file("$WORKDIR/data/ib_logfile0")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ib_logfile0 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ib_logfile0");
			}
			
			if(is_file("$WORKDIR/data/ib_logfile1")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing ib_logfile1 files Working directory `$WORKDIR`...\n";}
				@unlink("$WORKDIR/data/ib_logfile1");
			}
			
			return true;
		}
		
	}
	
	return false;
	
}


function stop($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$PidRestore="/etc/artica-postfix/pids/zarafaRestore.pid";
		$oldpid=$unix->get_pid_from_file($PidRestore);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restore Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
	
		
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
	
	}

	$pid=ZARAFADB_PID();
	@file_put_contents($pidfile, getmypid());
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping Daemon with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	$kill=$unix->find_program("kill");
	if($unix->is_socket("/var/run/mysqld/zarafa-db.sock")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping smoothly...\n";}
		$cmd="$mysqladmin --socket=/var/run/mysqld/zarafa-db.sock  --protocol=socket --user=root shutdown >/dev/null 2>&1";
		shell_exec($cmd);
	}else{
		shell_exec("kill $pid >/dev/null 2>&1");
	}

	$pid=ZARAFADB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon success...\n";}
		return;
	}	
	$rm=$unix->find_program("rm");

	for($i=0;$i<15;$i++){
		$pid=ZARAFADB_PID();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon stopped..\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon [STOP] wait PID: $pid $i/10\n";}
		if($unix->is_socket("/var/run/mysqld/zarafa-db.sock")){
			$cmd="$mysqladmin --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root shutdown >/dev/null 2>&1";
		}else{
			$cmd="$kill $pid >/dev/null 2>&1";
		}
		shell_exec($cmd);
		sleep(1);
	}	
	
	$pid=ZARAFADB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon success...\n";}
		shell_exec("$rm -f /var/run/mysqld/zarafa-db.sock >/dev/null 2>&1");
		@unlink("/home/zarafa-db/error.log");
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon Failed...\n";}
}

function ZARAFADB_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-db.pid");
	if($unix->process_exists($pid)){
		if($GLOBALS["VERBOSE"]){echo "*********************************\nPROCESSS RUNNING #1\n\n";}
		return $pid;}
	$mysqld=$unix->find_program("mysqld");
	$pid=$unix->PIDOF_PATTERN("$mysqld.*?--pid-file=/var/run/zarafa-db.pid");
	if($GLOBALS["VERBOSE"]){echo "*********************************\nPROCESSS RUNNING $pid #2\n\n";}
	return $pid;
	
	
}

function XZARAFA_SERVER_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("zarafa-server -c /etc/zarafa/server.cfg");

}

function ZARAFA_SERVER2_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-server2.pid");
	if($unix->process_exists($pid)){return $pid;}
	return null;
	
}

function changemysqldir($dir){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafadbstart.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	initd();
	$dirCMD=$unix->shellEscapeChars($dir);
	if($dir=="/home/zarafa-db"){return;}
	@mkdir($dir,0755,true);
	echo "Stopping Zarafa-db";
	shell_exec("/etc/init.d/zarafa-db stop");
	$Size=$unix->DIRSIZE_BYTES("/home/zarafa-db");
	echo "Copy /home/zarafa-db content to next dir size=$Size";
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	shell_exec("$cp -rf /home/zarafa-db/* $dirCMD/");
	$Size2=$unix->DIRSIZE_BYTES($dir);
	if($Size2<$Size){
		echo "Copy error $Size2 is less than original size ($Size)\n";
	}
	echo "Removing old data\n";
	shell_exec("$rm -rf /home/zarafa-db");
	echo "Create a new symbolic link...\n";
	shell_exec("$ln -s $dirCMD /home/zarafa-db");
	echo "Starting MySQL database engine...\n";
	shell_exec("/etc/init.d/zarafa-db start");
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}



function initd(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-db";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa MySQL database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Zarafa MySQL database";
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
	@file_put_contents("/etc/init.d/zarafa-db", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-db",0755);
	
	$f=array();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-server2";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa-server Second";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Zarafa-server Second";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php ". __FILE__." --start-server --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". __FILE__." --stop-server --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	
	$f[]="    $php ". __FILE__." --stop-server --byinitd --force \$2 \$3";
	$f[]="    $php ". __FILE__." --start-server --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	@file_put_contents("/etc/init.d/zarafa-server2", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-server2",0755);	

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f zarafa-db defaults >/dev/null 2>&1');
		shell_exec('/usr/sbin/update-rc.d -f zarafa-server2 defaults >/dev/null 2>&1');
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add zarafa-server2 >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 zarafa-server2 on >/dev/null 2>&1');
	}
}

function databasesize($force=false){
	
	
	$unix=new unix();
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/zarafadb.size.db";
	
	
	
	if(!$force){
		$pidfile="/etc/artica-postfix/pids/zarafa-databasesize.pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($time<20){
			if($GLOBALS["VERBOSE"]){echo "20Mn minimal current {$time}mn\n";}
			return;
		}
	}
	$sock=new sockets();
	$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
	if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
	$dir="$WORKDIR/data";
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

function build_progress_status($pourc,$text){
	$cachefile="/usr/share/artica-postfix/ressources/logs/zarafatrash.build.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function remove_database($allprocedure=false){
	
	$unix=new unix();

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($allprocedure){
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			build_progress_status(100,"Already task running PID $oldpid");
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	
	$mysql=$unix->find_program("mysql");
	$rm=$unix->find_program("rm");
	$sock=new sockets();
	
	build_progress_status(5,"Removing MySQL Zarafa Database");
	
	WriteToSyslogMail("Action: Removing Zarafa Database MySQL client `$mysql`....",__FILE__);
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa --execute=\"DROP DATABASE zarafa\" 2>&1";
	$results=array();
	exec("$cmd",$results);
	
	$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
	if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
	
	while (list ($num, $ligne) = each ($results) ){echo WriteToSyslogMail("MySQL: (Delete Database) $ligne",__FILE__);}
	
	build_progress_status(10,"Removing Zarafa Database MySQL");
	if(is_dir("$WORKDIR")){shell_exec("$rm -rf $WORKDIR"); }

	
	WriteToSyslogMail("Action: Restarting MySQL service...",__FILE__);
	WriteToSyslogMail("Action: Stopping MySQL service...",__FILE__);
	build_progress_status(15,"Stopping MySQL Zarafa Database");
	stop(true);
	WriteToSyslogMail("Action: Starting MySQL service (InnoDB recovery mode)...",__FILE__);
	build_progress_status(20,"Starting MySQL Zarafa Database (InnoDB recovery mode)");
	start(true,true);
	while (list ($num, $ligne) = each ($results) ){echo "Service: $ligne\n";}
		sleep(5);
		$ZARAFADB_PID=ZARAFADB_PID();
		if(!$unix->process_exists($ZARAFADB_PID)){build_progress_status(110,"{failed}");return;}
	
	if($allprocedure){
		build_progress_status(25,"Restarting MySQL service (normal)");
		
		echo "Action: Restarting MySQL service...\n";
		echo "Action: Stopping MySQL service...\n";
		build_progress_status(30,"Stopping MySQL service (normal)");
		stop(true);
		echo "Action: Starting MySQL service (InnoDB normal mode)...\n";
		build_progress_status(35,"Starting MySQL service (normal)");
		start(true,false);
		while (list ($num, $ligne) = each ($results) ){echo "Service: $ligne\n";}
		sleep(5);
		$ZARAFADB_PID=ZARAFADB_PID();
		if(!$unix->process_exists($ZARAFADB_PID)){build_progress_status(110,"{failed}");return;}
	
	
	}	
	
	
	build_progress_status(40,"Stopping Zarafa Server service");
	WriteToSyslogMail("Action: Stopping Zarafa server...",__FILE__);
	@unlink("/tmp/zarafa-upgrade-lock");
	system("/etc/init.d/zarafa-server stop --kill");
	$pid=XZARAFA_SERVER_PID();
	if($unix->process_exists($pid)){
		$kill=$unix->find_program("kill");
		shell_exec("$kill -9 $pid");
	}
	build_progress_status(45,"Restarting Zarafa Server service");
	WriteToSyslogMail("Action: Restarting Zarafa server...",__FILE__);
	shell_exec("/etc/init.d/zarafa-server restart");
	WriteToSyslogMail("Action: sleeping 5s",__FILE__);
	sleep(5);
	build_progress_status(50,"Restarting Zarafa Server service");
	WriteToSyslogMail("Action: Restarting Zarafa server...",__FILE__);
	system("/etc/init.d/zarafa-server restart");
	build_progress_status(60,"Checking DB size");
	databasesize(true);
	build_progress_status(100,"{finish}");
}

function RestoreFromBackup($backuppath){
	$unix=new unix();
	$PidRestore="/etc/artica-postfix/pids/zarafaRestore.pid";
	$rm=$unix->find_program("rm");
	
	$oldpid=$unix->get_pid_from_file($PidRestore);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restore Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	$oldpid=$unix->PIDOF_PATTERN("exec.zarafa-db.php --restorefrom");
		if($oldpid<>getmypid()){
		if($unix->process_exists($oldpid)){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restore Task Already running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	
	$mysql=$unix->find_program("mysql");
	$pid=$unix->PIDOF_PATTERN("$mysql\s+.*?--socket=/var/run/mysqld/zarafa-db.sock.*?database=zarafa");
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Restore Task Already running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($PidRestore, getmypid());
	$sock=new sockets();
	$SourceDir=dirname($backuppath);
	$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
	if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
	
	if(is_file("$SourceDir/ldap.ldif")){
		RestoreFromBackup_progress("{restore_ldap_database}",10);
		RestoreFromBackup_ldap("$SourceDir/ldap.ldif");
	}
	
	$unix=new unix();
	
	
	if(!is_file($backuppath)){
		echo "Action: `$backuppath` no such file: ABORT!\n";
		RestoreFromBackup_progress("{failed}",100);
		return;
	}
	
	echo "Action: Removing Zarafa Database MySQL client `$mysql`....\n";
	RestoreFromBackup_progress("Removing Zarafa Database",30);
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa --execute=\"DROP DATABASE zarafa\" 2>&1";
	$results=array();
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){echo "MySQL: (Delete Database) $ligne\n";}
	
	RestoreFromBackup_progress("Removing all content",32);
	if(is_dir("$WORKDIR/data/zarafa")){
		shell_exec("$rm -rf $WORKDIR");
	}
	
	
	
	RestoreFromBackup_progress("Restarting MySQL service (recovery)",40);
	echo "Action: Restarting MySQL service...\n";
	echo "Action: Stopping MySQL service...\n";
	stop(true);
	echo "Action: Starting MySQL service (InnoDB recovery mode)...\n";
	start(true,true);
	while (list ($num, $ligne) = each ($results) ){echo "Service: $ligne\n";}
	sleep(5);
	$ZARAFADB_PID=ZARAFADB_PID();
	if(!$unix->process_exists($ZARAFADB_PID)){
		RestoreFromBackup_progress("Failed to restart dedicated MySQL",100);
		return;
	}
	
	
	RestoreFromBackup_progress("Stopping Zarafa server",43);
	@unlink("/tmp/zarafa-upgrade-lock");
	shell_exec("/etc/init.d/zarafa-server stop --force");
	$pid=XZARAFA_SERVER_PID();
	if($unix->process_exists($pid)){
		$kill=$unix->find_program("kill");
		shell_exec("$kill -9 $pid");
	}
	
	RestoreFromBackup_progress("Restarting MySQL service (normal)",45);
	echo "Action: Restarting MySQL service...\n";
	echo "Action: Stopping MySQL service...\n";
	stop(true);
	echo "Action: Starting MySQL service (InnoDB normal mode)...\n";
	start(true,false);
	while (list ($num, $ligne) = each ($results) ){echo "Service: $ligne\n";}
	sleep(2);
	$ZARAFADB_PID=ZARAFADB_PID();
	if(!$unix->process_exists($ZARAFADB_PID)){
		RestoreFromBackup_progress("Failed to restart dedicated MySQL",100);
		return;
	}
	
	
	
	
	if(!$unix->is_socket("/var/run/mysqld/zarafa-db.sock")){
		echo "Action: /var/run/mysqld/zarafa-db.sock waiting socket\n";
		for($i=0;$i<5;$i++){
			if($unix->is_socket("/var/run/mysqld/zarafa-db.sock")){break;}
			echo "Action: Waiting zarafa-db.sock $i/4\n";
			sleep(1);
		}
		
	}
	
	if(!$unix->is_socket("/var/run/mysqld/zarafa-db.sock")){
		echo "Action: /var/run/mysqld/zarafa-db.sock no such socket\n";
		RestoreFromBackup_progress("zarafa-db.sock no such socket",100);
		return;
	}
	
	
	echo "Action: /var/run/mysqld/zarafa-db.sock OK\n";
	echo "Action: create a freshed Zarafa database\n";
	
	
	$ZarafaIndexPath=$sock->GET_INFO("ZarafaIndexPath");
	if($ZarafaIndexPath==null){$ZarafaIndexPath="/var/lib/zarafa/index";}
	RestoreFromBackup_progress("Cleaning/Stopping Zarafa search DBs",50);
	if(is_dir($ZarafaIndexPath)){
		shell_exec("$rm -rf $ZarafaIndexPath");
		shell_exec("/etc/init.d/zarafa-search stop");
	}
	
	
	RestoreFromBackup_progress("Create a freshed Zarafa database",50);
	$results=array();
	
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --execute=\"CREATE DATABASE zarafa\" 2>&1";
	$results=array();
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){echo "MySQL: (Create Database) $ligne\n";}
	RestoreFromBackup_progress("Testing Database...",51);
	if(!is_dir("$WORKDIR/data/zarafa")){
		echo "Action: FAILED TO create a freshed Zarafa database: ABORT!!\n";
		echo "Action: $WORKDIR/data/zarafa no such directory\n";
		RestoreFromBackup_progress("FAILED to create a freshed Zarafa database",100);
		return;
	}
	RestoreFromBackup_progress("Checks Database size",53);
	databasesize(true);
	
	$gunzip=$unix->find_program("gunzip");
	$SourceFileBase=basename($backuppath);
	$file_ext=$unix->file_ext($SourceFileBase);
	$tStart=time();
	$nohup=$unix->find_program("nohup");
	$backuppath1=$unix->shellEscapeChars($backuppath);
	$cmd="$nohup $mysql --show-warnings --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa < $backuppath1 >/root/mysqllog.txt 2>&1 &";
	echo "Action: $SourceFileBase extension $file_ext\n";
	echo "Action: Restoring From $backuppath1\n";
	if($file_ext=="gz"){
		echo "Action: Restoring From $backuppath1 with uncompress..\n";
		$cmd="$nohup $gunzip -c $backuppath1 |$mysql --show-warnings --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa >/root/mysqllog.txt 2>&1 &";
	}
	
	
	$size=@filesize($backuppath);
	$size=FormatBytes($size/1024);
	echo "Action: Please wait, it should take time...\nAction: Do not shutdown the computer or restart the MySQL service!\n";
	$results=array();
	RestoreFromBackup_progress("{restoring_data} $size {please_wait} !",70);
	
	$lastmd5=null;
	$continue=true;
	shell_exec($cmd);
	$ALRDLO=array();
	while ($continue) {
		$fileMD5=@md5_file("/root/mysqllog.txt");
		if($fileMD5<>$lastmd5){
			$LOGS=explode("\n",@file_get_contents("/root/mysqllog.txt"));
			while (list ($num, $ligne) = each ($LOGS) ){
				if(trim($ligne)==null){continue;}
				if(isset($ALRDLO[md5($ligne)])){continue;}
				$ALRDLO[md5($ligne)]=true;
				if(preg_match("#ERROR\s+([0-9]+)\s+\(#",$ligne,$re)){
					echo date("Y-m-d H:i:s")." MySQL: FAILED !!! $ligne\n";
					RestoreFromBackup_progress("{failed} {error} {$re[1]} ",100);
					return;
				}
				echo date("Y-m-d H:i:s")." MySQL: $ligne\n";
			}
			$lastmd5=$fileMD5;
		}
		
		$pid=$unix->PIDOF_PATTERN("$mysql\s+.*?--socket=/var/run/mysqld/zarafa-db.sock.*?database=zarafa");
		echo "Action: PID: $pid\n";
		if(!$unix->process_exists($pid)){
			echo "Action: injection stopped running since ".$unix->distanceOfTimeInWords($tStart,time(),true)."\n";
			$continue=false;
			break;
		}
		echo "Action: PID $pid running since ".$unix->distanceOfTimeInWords($tStart,time(),true).", please wait...\n";
		RestoreFromBackup_progress($unix->distanceOfTimeInWords($tStart,time(),true)." {please_wait} !",71);
		$continue=true;
		sleep(30);
		continue;
	}
	
		
	
	

	
	echo "Action: Done, took: ".$unix->distanceOfTimeInWords($tStart,time(),true)."\n";
	echo "Action: Please wait, Checks Database size\n";
	RestoreFromBackup_progress("Checks Database size",75);
	databasesize(true);
	RestoreFromBackup_progress("{restoring_data} {success}",80);
	echo "Action: restart_services\n";
	RestoreFromBackup_progress("{restart_services}",90);
	$unix->THREAD_COMMAND_SET("/etc/init.d/zarafa-server restart");
	
	echo "Action: Restore task done...\n";
	echo "Action: You can close the windows now...\n";
	RestoreFromBackup_progress("{done}",100);
	die();
	
}

function RestoreFromBackup_progress($text,$prc){
	$file="/usr/share/artica-postfix/ressources/RestoreFromBackup_progress.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	
}

function RestoreFromBackup_ldap($sourcefile){

	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$slapadd=$unix->find_program("slapadd");
	$rm=$unix->find_program("rm");
	$ldap_databases="/var/lib/ldap";
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();

	$TMP=$unix->FILE_TEMP();
	if(!is_file($sourcefile)){
		system_admin_events("{failed} $sourcefile no such file",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	
	RestoreFromBackup_progress("Stopping LDAP",11);
	echo "Action: Stopping LDAP\n";
	shell_exec("/etc/init.d/slapd stop --force");
	echo "Action: Removing $ldap_databases\n";
	RestoreFromBackup_progress("Removing $ldap_databases",15);
	shell_exec("$rm -f  $ldap_databases/* >/dev/null 2>&1");
	echo "Action: Restoring LDAP database....\n";
	RestoreFromBackup_progress("Restoring database",20);
	exec("$slapadd -v -c -l $sourcefile -f $SLAPD_CONF 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		
		if(preg_match("#added#", $ligne)){continue;}
		echo "LDAP: $ligne\n";}
	echo "Action: Starting slapd\n";
	RestoreFromBackup_progress("Starting slapd",25);
	shell_exec("/etc/init.d/slapd start --force");
	
}


function zarafa_server2_config(){
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	
	$f[]="server_bind			= 0.0.0.0";
	$f[]="server_hostname		= $hostname";
	$f[]="server_tcp_enabled	= no";
	$f[]="server_tcp_port		= 236";
	$f[]="server_pipe_enabled	= yes";
	$f[]="server_pipe_name		= /var/run/zarafa2";
	$f[]="server_pipe_priority  = /var/run/zarafa-prio2";
	$f[]="server_name 			= Zarafa2";
	$f[]="database_engine		= mysql";
	$f[]="allow_local_users		= yes";
	$f[]="local_admin_users		= root vmail mail ";
	$f[]="system_email_address	= postmaster@localhost";
	$f[]="run_as_user			= ";
	$f[]="run_as_group			= ";
	$f[]="pid_file				= /var/run/zarafa-server2.pid";
	$f[]="running_path 			= /";
	$f[]="session_timeout		= 300";
	$f[]="license_socket		= /var/run/zarafa-licensed2";
	$f[]="log_method			= file";
	$f[]="audit_log_enabled		= yes";
	$f[]="audit_log_method		= syslog";
	$f[]="audit_log_file		= -";
	$f[]="audit_log_level		= 3";
	$f[]="audit_log_timestamp	= 0";
	$f[]="log_file				= /var/log/zarafa/server2.log";
	$f[]="log_level				= 9";
	$f[]="log_timestamp			= 1";
	$f[]="mysql_socket			= /var/run/mysqld/zarafa-db.sock";
	$f[]="mysql_user			= root";
	$f[]="mysql_database		= zarafa";
	$f[]="attachment_storage	= database";
	$f[]="attachment_path		= /var/lib/zarafa";
	$f[]="attachment_compression= 6";
	$f[]="index_services_enabled= no";
	$f[]="enable_enhanced_ics	= yes";
	$f[]="search_enabled 		= no";
	$f[]="enable_sso_ntlmauth	= no";
	$f[]="server_ssl_enabled	= no";
	$f[]="server_ssl_port		= 237";
	$f[]="sslkeys_path			= /etc/ssl/certs/zarafa";
	$f[]="softdelete_lifetime	= 30";
	$f[]="sync_lifetime			= 730";
	$f[]="sync_log_all_changes 	= yes";
	$f[]="enable_gab 			= yes";
	$f[]="auth_method 			= plugin";
	$f[]="pam_service 			= passwd";
	$f[]="cache_cell_size		= 16777216";
	$f[]="cache_object_size		= 5242880";
	$f[]="cache_indexedobject_size= 16777216";
	$f[]="cache_quota_size		= 1048576";
	$f[]="cache_acl_size		= 1048576";
	$f[]="cache_user_size		= 1048576";
	$f[]="cache_userdetails_size= 1048576";
	$f[]="cache_server_size		= 1048576";
	$f[]="cache_quota_lifetime	= 1";
	$f[]="cache_userdetails_lifetime= 5";
	$f[]="thread_stacksize 		= 512";
	$f[]="quota_warn			= 0";
	$f[]="quota_soft			= 0";
	$f[]="quota_hard			= 0";
	$f[]="companyquota_warn 	= 0";
	$f[]="user_plugin			= ldap";
	$f[]="user_plugin_config	= /etc/zarafa/ldap.openldap.cfg";
	$f[]="# Multi-tenancy configurations";
	$f[]="enable_hosted_zarafa	= yes";
	$f[]="enable_distributed_zarafa = false";
	$f[]="storename_format 		= %f";
	$f[]="loginname_format 		= %u";
	$f[]="client_update_enabled = true";
	$f[]="client_update_path 	= /var/lib/zarafa/client";
	$f[]="hide_everyone 		= no";
	$f[]="plugin_path			= /usr/lib/zarafa";
	$f[]="user_safe_mode 		= no";
	$f[]="disabled_features 	=\n";	
	@file_put_contents("/etc/zarafa/server2.cfg", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: `/etc/zarafa/server2.cfg` success...\n";}
}

function zarafa_server2_stop(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafa-server2-stop.pid";
		
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$zarafaserver=$unix->find_program("zarafa-server");
	if(!is_file($zarafaserver)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) is not installed...\n";}
		return;
	}	
	
	$pid=ZARAFA_SERVER2_PID();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) already stopped\n";}
		return;
	}	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) instance running since {$time}mn\n";}
	$kill=$unix->find_program("kill");
	shell_exec("$kill $pid");
	for($i=1;$i<61;$i++){
		sleep(1);
		$pid=ZARAFA_SERVER2_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) waiting $i/60\n";}
		}else{
			break;
		}
		
	}
	
	$pid=ZARAFA_SERVER2_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) failed to stop pid:$pid\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: zarafa-server (2) success\n";}
}


function zarafa_server2_start(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafa-server2-start.pid";
	$sock=new sockets();
	$ZarafaDBEnable2Instance=$sock->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($ZarafaDBEnable2Instance)){$ZarafaDBEnable2Instance=0;}	
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}

	@file_put_contents($pidfile, getmypid());


	$zarafaserver=$unix->find_program("zarafa-server");
	if(!is_file($zarafaserver)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) is not installed...\n";}
		return;
	}
	
	if($ZarafaDBEnable2Instance==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) is not enabled...\n";}
		return;		
	}
	
	zarafa_server2_config();

	$pid=ZARAFA_SERVER2_PID();

	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) already running pid $pid since {$time}mn\n";}
		return;
	}

	$f[]=$zarafaserver;
	$f[]="--config=/etc/zarafa/server2.cfg";
	$f[]="--ignore-database-version-conflict";
	$f[]="--ignore-unknown-config-options";


	$cmdline=@implode(" ", $f);
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting zarafa-server (2) daemon\n";}
	shell_exec("$cmdline 2>&1");

	for($i=0;$i<10;$i++){
		$pid=ZARAFA_SERVER2_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) daemon wait $i/10\n";}
		sleep(1);
	}

	$pid=ZARAFA_SERVER2_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: zarafa-server (2) daemon failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		}

	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	
}

