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

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd();die();}
if($argv[1]=="--changemysqldir"){changemysqldir($argv[2]);die();}
if($argv[1]=="--databasesize"){databasesize($GLOBALS["FORCE"]);die();}
if($argv[1]=="--restorefrom"){RestoreFromBackup($argv[2]);die();}
if($argv[1]=="--start-server"){zarafa_server2_start();die();}
if($argv[1]=="--stop-server"){zarafa_server2_stop();die();}


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


function start(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafadbstart.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
		
	
	
	if(!is_file("/opt/zarafa-db/bin/mysqld")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-db is not installed...\n";}
		return;
	}	
	
	$pid=ZARAFADB_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL Database Engine already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: writing init.d\n";}
	initd();
	//innodb_buffer_pool_size
	
	$memory=get_memory();
	$swap=get_swap();
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Server available memory `{$memory}MB`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Server available swap `{$swap}MB`\n";}
	
	$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
	if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
	$zarafa_max_connections=null;
	$zarafa_innodb_buffer_pool_size=null;
	$zarafa_innodb_log_file_size=null;
	$zarafa_innodb_log_buffer_size=null;
	$zarafa_max_allowed_packet=null;
	$zarafa_query_cache_size=null;
	
	if($EnableZarafaTuning==1){
		$ZarafTuningParameters=unserialize(base64_decode($sock->GET_INFO("ZarafaTuningParameters")));
		$zarafa_innodb_buffer_pool_size=$ZarafTuningParameters["zarafa_innodb_buffer_pool_size"];
		$zarafa_query_cache_size=$ZarafTuningParameters["zarafa_query_cache_size"];
		$zarafa_innodb_log_file_size=$ZarafTuningParameters["zarafa_innodb_log_file_size"];
		$zarafa_innodb_log_buffer_size=$ZarafTuningParameters["zarafa_innodb_log_buffer_size"];
		$zarafa_max_allowed_packet=$ZarafTuningParameters["zarafa_max_allowed_packet"];
		$zarafa_max_connections=$ZarafTuningParameters["zarafa_max_connections"];
		$zarafa_connect_timeout=$ZarafTuningParameters["zarafa_connect_timeout"];
		$zarafa_interactive_timeout=$ZarafTuningParameters["zarafa_interactive_timeout"];
	}
	
	
	if(!is_numeric($zarafa_interactive_timeout)){$zarafa_interactive_timeout=57600;}
	if(!is_numeric($zarafa_connect_timeout)){$zarafa_connect_timeout=60;}
	if(!is_numeric($zarafa_max_connections)){$zarafa_max_connections=150;}
	if(!is_numeric($zarafa_innodb_buffer_pool_size)){$zarafa_innodb_buffer_pool_size=round($memory/3);}
	if(!is_numeric($zarafa_innodb_log_file_size)){$zarafa_innodb_log_file_size=round($zarafa_innodb_buffer_pool_size*0.25);}
	if(!is_numeric($zarafa_innodb_log_buffer_size)){$zarafa_innodb_log_buffer_size=32;}
	if(!is_numeric($zarafa_max_allowed_packet)){$zarafa_max_allowed_packet=100;}
	if(!is_numeric($zarafa_query_cache_size)){$zarafa_query_cache_size=8;}
		
	if($zarafa_max_allowed_packet<100){$zarafa_max_allowed_packet=100;}
	
	$KERNEL_ARCH=$unix->KERNEL_ARCH();
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Architecture: $KERNEL_ARCH bits\n";}
	if($unix->KERNEL_ARCH()==32){
		if($zarafa_innodb_buffer_pool_size>3999){$zarafa_innodb_buffer_pool_size=3999;}
		if($zarafa_innodb_buffer_pool_size>$swap){$zarafa_innodb_buffer_pool_size=$swap;}
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Innodb buffer pool size: {$zarafa_innodb_buffer_pool_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Innodb log file size: {$zarafa_innodb_buffer_pool_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Innodb log buffer size: {$zarafa_innodb_log_buffer_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Max allowed packet: {$zarafa_max_allowed_packet}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Max connections: {$zarafa_max_connections} cnxs\n";}
	
		
	$f[]="/opt/zarafa-db/bin/mysqld";
	$f[]="--defaults-file=/opt/zarafa-db/my.cnf ";
	$f[]="--user=root";
	$f[]="--pid-file=/var/run/zarafa-db.pid"; 
	$f[]="--basedir=/opt/zarafa-db"; 
	$f[]="--datadir=/opt/zarafa-db/data"; 
	$f[]="--plugin_dir=/opt/zarafa-db/lib/plugin"; 
	$f[]="--socket=/var/run/mysqld/zarafa-db.sock";
	$f[]="--general_log_file=/opt/zarafa-db/general_log.log";
	$f[]="--max-allowed-packet={$zarafa_max_allowed_packet}M";
	$f[]="--innodb-buffer-pool-size={$zarafa_innodb_buffer_pool_size}M";
	$f[]="--innodb-log-file-size={$zarafa_innodb_log_file_size}M";
	$f[]="--innodb-log-buffer-size={$zarafa_innodb_log_buffer_size}M";
	$f[]="--max-connections={$zarafa_max_connections}";
	$f[]="--connect_timeout={$zarafa_connect_timeout}";
	$f[]="--interactive_timeout={$zarafa_interactive_timeout}";
	$f[]="--innodb-fast-shutdown=0";
	$f[]="--log-warnings=2";
	$f[]="--innodb-file-per-table";
	$f[]="--innodb=FORCE";
	$f[]="--skip-networking";
	
	$TMP=$unix->FILE_TEMP();
	
	$cmdline=@implode(" ", $f);
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting MySQL daemon\n";}
	shell_exec("$nohup $cmdline >$TMP 2>&1 &");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=ZARAFADB_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	
	$pid=ZARAFADB_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $ligne\n";}
		}
	
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}


function stop(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}

	$pid=ZARAFADB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Stopping Daemon with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Stopping smoothly...\n";}
	$cmd="$mysqladmin --socket=/var/run/mysqld/zarafa-db.sock  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=ZARAFADB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=ZARAFADB_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon kill pid $pid..\n";}
			shell_exec("$kill -9 $pid");
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon wait $i/10\n";}
		sleep(1);
	}	
	$pid=ZARAFADB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon Failed...\n";}
}

function ZARAFADB_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/zarafa-db.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/opt/zarafa-db/bin/mysqld");
	
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	initd();
	$dirCMD=$unix->shellEscapeChars($dir);
	if($dir=="/opt/zarafa-db/data"){return;}
	@mkdir($dir,0755,true);
	echo "Stopping Zarafa-db";
	shell_exec("/etc/init.d/zarafa-db stop");
	$Size=$unix->DIRSIZE_BYTES("/opt/zarafa-db/data");
	echo "Copy /opt/zarafa-db/data content to next dir size=$Size";
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	shell_exec("$cp -rf /opt/zarafa-db/data/* $dirCMD/");
	$Size2=$unix->DIRSIZE_BYTES($dir);
	if($Size2<$Size){
		echo "Copy error $Size2 is less than original size ($Size)\n";
	}
	echo "Removing old data\n";
	shell_exec("$rm -rf /opt/zarafa-db/data");
	echo "Create a new symbolic link...\n";
	shell_exec("$ln -s $dirCMD /opt/zarafa-db/data");
	echo "Starting MySQL database engine...\n";
	shell_exec("/etc/init.d/zarafa-db start");
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}



function initd(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          Zarafa MySQL database";
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
	$f[]="# Provides:          Zarafa-server Second";
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
		if($arrayfile<20){return;}
	}
	
	
	$dir="/opt/zarafa-db/data";
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

function RestoreFromBackup($backuppath){
	
	$unix=new unix();
	$mysql=$unix->find_program("mysql");
	
	if(!is_file($backuppath)){
		echo "Action: `$backuppath` no such file: ABORT!\n";
		return;
	}
	
	echo "Action: Removing Zarafa Database MySQL client `$mysql`....\n";
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa --execute=\"DROP DATABASE zarafa\" 2>&1";
	$results=array();
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){echo "MySQL: $ligne\n";}
	echo "Action: Removing ibdata files...\n";
	@unlink("/opt/zarafa-db/data/ibdata1");
	@unlink("/opt/zarafa-db/data/ib_logfile0");
	@unlink("/opt/zarafa-db/data/ib_logfile1");

	
	echo "Action: Restarting MySQL service...\n";
	$results=array();
	exec("/etc/init.d/zarafa-db restart 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){echo "Service: $ligne\n";}
	
	
	echo "Action: create a freshed Zarafa database\n";
	
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=mysql --execute=\"CREATE DATABASE zarafa\" 2>&1";
	$results=array();
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){echo "MySQL: $ligne\n";}
	
	if(!is_dir("/opt/zarafa-db/data/zarafa")){
		echo "Action: FAILED TO create a freshed Zarafa database: ABORT!!\n";
		return;
	}
	databasesize(true);
	$backuppath1=$unix->shellEscapeChars($backuppath);
	$cmd="$mysql --socket=/var/run/mysqld/zarafa-db.sock --protocol=socket --user=root --batch --debug-info --database=zarafa < $backuppath1 2>&1";
	echo "Action: Restoring From $backuppath1..\n";
	echo "Action: Please wait, it should take time...\nAction: Do not shutdown the computer or restart the MySQL service!\n";
	$results=array();
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){echo "MySQL: $ligne\n";}	
	databasesize(true);
	echo "Action: Restore task done...\n";
	echo "Action: You can close the windows now...\n";
	
	
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
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: `/etc/zarafa/server2.cfg` success...\n";}
}

function zarafa_server2_stop(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/zarafa-server2-stop.pid";
		
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$zarafaserver=$unix->find_program("zarafa-server");
	if(!is_file($zarafaserver)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) is not installed...\n";}
		return;
	}	
	
	$pid=ZARAFA_SERVER2_PID();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) already stopped\n";}
		return;
	}	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) instance running since {$time}mn\n";}
	$kill=$unix->find_program("kill");
	shell_exec("$kill $pid");
	for($i=1;$i<61;$i++){
		sleep(1);
		$pid=ZARAFA_SERVER2_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) waiting $i/60\n";}
		}else{
			break;
		}
		
	}
	
	$pid=ZARAFA_SERVER2_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) failed to stop pid:$pid\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: zarafa-server (2) success\n";}
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
		return;
	}

	@file_put_contents($pidfile, getmypid());


	$zarafaserver=$unix->find_program("zarafa-server");
	if(!is_file($zarafaserver)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) is not installed...\n";}
		return;
	}
	
	if($ZarafaDBEnable2Instance==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) is not enabled...\n";}
		return;		
	}
	
	zarafa_server2_config();

	$pid=ZARAFA_SERVER2_PID();

	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) already running pid $pid since {$time}mn\n";}
		return;
	}

	$f[]=$zarafaserver;
	$f[]="--config=/etc/zarafa/server2.cfg";
	$f[]="--ignore-database-version-conflict";
	$f[]="--ignore-unknown-config-options";


	$cmdline=@implode(" ", $f);
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting zarafa-server (2) daemon\n";}
	shell_exec("$cmdline 2>&1");

	for($i=0;$i<10;$i++){
		$pid=ZARAFA_SERVER2_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) daemon started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) daemon wait $i/10\n";}
		sleep(1);
	}

	$pid=ZARAFA_SERVER2_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: zarafa-server (2) daemon failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $ligne\n";}
		}

	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmdline\n";}}
	
}

