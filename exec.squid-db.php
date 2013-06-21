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
	
	echo "Starting......: **************************\n";
	echo "Starting......: [INIT]: Running upgrade $mysql_upgrade....\n";
	echo "Starting......: **************************\n";
	shell_exec("$mysql_upgrade -u root -S /var/run/mysqld/squid-db.sock --verbose");
	stop();
	start(false);	
}

function Get_errmsgsys(){
	
	$f[]="/usr/share/mysql/english/errmsg.sys";
	$f[]="/opt/articatech/mysql/share/english/errmsg.sys";
	while (list ($num, $ligne) = each ($f) ){
		if(is_file($ligne)){return $ligne;}
	}
	
}

function checktables(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
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
	while (list ($key, $filename) = each ($results) ){
		if(!is_file("$WORKDIR/mysql/$filename")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: copy /var/lib/mysql/mysql/$filename $WORKDIR/mysql/$filename\n";}
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
	`uptime` BIGINT(100) NOT NULL,
	`threads` BIGINT(10) NOT NULL,
	`questions` BIGINT(100) NOT NULL,
	`squeries` BIGINT(10) NOT NULL,
	`opens` BIGINT(100) NOT NULL,
	`ftables` BIGINT(20) NOT NULL,
	`open` BIGINT(10) NOT NULL,
	`queriesavg` BIGINT(100) NOT NULL,
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
	if($GLOBALS["VERBOSE"]){
		echo $sql."\n$q->mysql_error\n";}
	}
	
	
	$sql="INSERT IGNORE INTO MySQLStats (zDate,uptime,threads,questions,squeries,opens,ftables,open,queriesavg)
	VALUES('$date','{$re[1]}','{$re[2]}','{$re[3]}','{$re[4]}','{$re[5]}','{$re[6]}','{$re[7]}','{$re[8]}')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){
			echo $sql."\n$q->mysql_error\n";}
	}
	if($GLOBALS["VERBOSE"]){
		echo $sql."\nOK\n";
	}
}
function squid_watchdog_events($text){
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function start($skipGrant=false){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/squiddbstart.pid";
	$WORKDIR="/opt/squidsql";
	$SERV_NAME="squid-db";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!$GLOBALS["NOPID"]){
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting Task Already running PID $oldpid since {$time}mn\n";}
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

	
	if($ProxyUseArticaDB==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME is disabled...\n";}
		stop();
		return;		
		
	}
		
	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	$pid=SQUIDDB_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL Database Engine already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: writing init.d\n";}
	initd();
	
	
	$memory=get_memory();
	$swap=get_swap();
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Server available memory `{$memory}MB`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Server available swap `{$swap}MB`\n";}
	
	$max_allowed_packetd=0;
	$bulk_insert_buffer_sized=0;
	$key_buffer_size_d=0;
	$thread_cache_sized=0;
	$tmp_table_sized=0;
	
	$SquidDBTuningParameters=unserialize(base64_decode($sock->GET_INFO("SquidDBTuningParameters")));
	$query_cache_size=$SquidDBTuningParameters["query_cache_size"];
	$max_allowed_packet=$SquidDBTuningParameters["max_allowed_packet"];
	$max_connections=$SquidDBTuningParameters["max_connections"];
	$connect_timeout=$SquidDBTuningParameters["connect_timeout"];
	$interactive_timeout=$SquidDBTuningParameters["interactive_timeout"];
	$key_buffer_size=$SquidDBTuningParameters["key_buffer_size"];
	$table_open_cache=$SquidDBTuningParameters["table_open_cache"];
	$myisam_sort_buffer_size=$SquidDBTuningParameters["myisam_sort_buffer_size"];
	$bulk_insert_buffer_size=$SquidDBTuningParameters["bulk_insert_buffer_size"];
	$tmp_table_size=$SquidDBTuningParameters["tmp_table_size"];
	$thread_cache_size=$SquidDBTuningParameters["thread_cache_size"];
	$ListenPort=$SquidDBTuningParameters["ListenPort"];
	$read_rnd_buffer_size=$SquidDBTuningParameters["read_rnd_buffer_size"];
	$net_read_timeout=$SquidDBTuningParameters["net_read_timeout"];
	$read_buffer_size=$SquidDBTuningParameters["read_buffer_size"];
	$sort_buffer_size=$SquidDBTuningParameters["sort_buffer_size"];
	$thread_stack=$SquidDBTuningParameters["thread_stack"];
	$join_buffer_size=$SquidDBTuningParameters["join_buffer_size"];
	$max_tmp_table_size=$SquidDBTuningParameters["max_tmp_table_size"];
	$tmpdir=$SquidDBTuningParameters["tmpdir"];
	
	
	
	
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if(!is_numeric($net_read_timeout)){$net_read_timeout=120;}
	
	
	if($tmpdir==null){$tmpdir="/tmp";}
	
	
	$net="--skip-networking";
	
	if($ListenPort>0){
		$net="--port=$ListenPort --skip-name-resolve";
	}
	
	if($memory>512){
			$bulk_insert_buffer_sized=8;$key_buffer_size_d=8;$max_allowed_packetd=50;$thread_cache_sized=2;
			$tmp_table_sized=8;
	}
	if($memory>1024){$bulk_insert_buffer_sized=16;$key_buffer_size_d=32;}
	if($memory>1500){
		$bulk_insert_buffer_sized=20;
		$key_buffer_size_d=64;
		$thread_cache_sized=2;
		$tmp_table_sized=20;
		$tmp_table_sized=8;
	}
	if($memory>2048){
		$bulk_insert_buffer_sized=32;
		$key_buffer_size_d=128;
		$tmp_table_sized=64;
		$tmp_table_sized=16;
	}
	if($memory>2500){
		$bulk_insert_buffer_sized=164;
		$key_buffer_size_d=256;
		$thread_cache_sized=10;
		$tmp_table_sized=16;
	}
	if($memory>3000){
		$bulk_insert_buffer_sized=196;
		$key_buffer_size_d=256;
		$thread_cache_sized=20;
		$tmp_table_sized=16;
	}
	if($memory>3500){
		$bulk_insert_buffer_sized=200;
		$key_buffer_size_d=256;
		$tmp_table_sized=32;
	}
	if($memory>4000){
		$bulk_insert_buffer_sized=204;
		$key_buffer_size_d=300;
		$max_allowed_packetd=100;
		$thread_cache_sized=64;
		$tmp_table_sized=64;
	}	
	
	if(!is_numeric($bulk_insert_buffer_size)){$bulk_insert_buffer_size=$bulk_insert_buffer_sized;}
	if(!is_numeric($key_buffer_size)){$key_buffer_size=$key_buffer_size_d;}
	if(!is_numeric($myisam_sort_buffer_size)){$myisam_sort_buffer_size=$key_buffer_size_d;}
	if(!is_numeric($thread_cache_size)){$thread_cache_size=$thread_cache_sized;}
	$read_rnd_buffer_sized=round($memory/1000);
	
	
	if(!is_numeric($interactive_timeout)){$interactive_timeout=57600;}
	if(!is_numeric($connect_timeout)){$connect_timeout=60;}
	if(!is_numeric($max_connections)){$max_connections=60;}
	if(!is_numeric($max_allowed_packet)){$max_allowed_packet=100;}
	if(!is_numeric($query_cache_size)){$query_cache_size=8;}
	if(!is_numeric($table_open_cache)){$table_open_cache=256;}
	if(!is_numeric($tmp_table_size)){$tmp_table_size=$tmp_table_sized;}
	if(!is_numeric($read_rnd_buffer_size)){$read_rnd_buffer_size=$read_rnd_buffer_sized;}
	if($max_allowed_packet<100){$max_allowed_packet=100;}
	
	
	if(!is_numeric($read_buffer_size)){$read_buffer_size=0;}
	if(!is_numeric($sort_buffer_size)){$sort_buffer_size=0;}
	if(!is_numeric($join_buffer_size)){$join_buffer_size=0;}
	if(!is_numeric($max_tmp_table_size)){$max_tmp_table_size=0;}
	if(!is_numeric($thread_stack)){$thread_stack=0;}
	

	
	
	$lnbin=$unix->find_program("ln");
	$KERNEL_ARCH=$unix->KERNEL_ARCH();
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Architecture.............: $KERNEL_ARCH bits\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Memory...................: {$memory}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Max allowed packet.......: {$max_allowed_packet}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Max connections..........: {$max_connections} cnxs\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Key Buffer size..........: {$key_buffer_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Sort Buffer size.........: {$myisam_sort_buffer_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Bulk Insert Buffer Size..: {$bulk_insert_buffer_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Tables Open cache........: {$table_open_cache} tables\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Thread Cache Size........: {$thread_cache_size}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: TMP Table size...........: {$tmp_table_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Read RND Buffer Size.....: {$read_rnd_buffer_size}M\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Net Read timeout.........: {$net_read_timeout}s\n";}
	$CREATEDB=false;
	
	
	if(!is_file("$WORKDIR/my.cnf")){
			@file_put_contents("$WORKDIR/my.cnf", "\n");
	}
	
	if(!is_file("$WORKDIR/bin/my_print_defaults")){
		$my_print_defaults=$unix->find_program("my_print_defaults");
		shell_exec("$lnbin -s $my_print_defaults $WORKDIR/bin/my_print_defaults");
	}
	
	if(!is_file("$WORKDIR/data/mysql/user.MYD")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Installing defaults databases, Please Wait...\n";}
		install_db($WORKDIR);
		$CREATEDB=true;
	}
	
	$topCopyMysql["host.frm"]=true;
	$topCopyMysql["host.MYD"]=true;
	$topCopyMysql["host.MYI"]=true;
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
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Installing $filename\n";}
				@copy("$MYSQL_DATA_DIR/mysql/$filename", "$WORKDIR/data/mysql/$filename");
				$CREATEDB=true;
			}
		}
	}
	
	
		while (list ($filename, $ligne) = each ($topCopyMysqlForce) ){
			if(!is_file("$WORKDIR/data/mysql/$filename")){
				if(is_file("$MYSQL_DATA_DIR/mysql/$filename")){
					if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Installing $filename\n";}
					@copy("$MYSQL_DATA_DIR/mysql/$filename", "$WORKDIR/data/mysql/$filename");
				}
			}
		}
	
	


	
	
	
	
	@mkdir("$WORKDIR/share/mysql/english",0755,true);
	$Get_errmsgsys=Get_errmsgsys();
	
	if(!is_file("$WORKDIR/share/mysql/english/errmsg.sys")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Creating errmsg.sys\n";}
		ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		if(is_file($Get_errmsgsys)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: copy $Get_errmsgsys -> $WORKDIR/share/mysql/english/errmsg.sys\n";}
			copy(Get_errmsgsys(), "$WORKDIR/share/mysql/english/errmsg.sys");
		}else{
			file_put_contents("$WORKDIR/share/mysql/english/errmsg.sys", "\n");
		}
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: errmsg.sys OK\n";}
	}
	
	//
	
	
	
	
	$f[]="$mysqld";
	$f[]="--defaults-file=$WORKDIR/my.cnf ";
	$f[]="--innodb=OFF";
	$f[]="--user=root";
	$f[]="--pid-file=/var/run/squid-db.pid";
	$f[]="--basedir=$WORKDIR";
	$f[]="--datadir=$WORKDIR/data";
	$f[]="--plugin_dir=$WORKDIR/lib/plugin";
	$f[]="--socket=/var/run/mysqld/squid-db.sock";
	$f[]="--general-log-file=$WORKDIR/general_log.log";
	$f[]="--slow-query-log-file=$WORKDIR/slow-query.log";
	$f[]="--log-error=$WORKDIR/error.log";
	if($skipGrant){
		$f[]="--skip-grant-tables";
	}
	if($max_allowed_packet>0){
		$f[]="--max-allowed-packet={$max_allowed_packet}M";
	}
	
	if($max_connections>0){
		$f[]="--max-connections={$max_connections}";
	}
	if($connect_timeout>0){
		$f[]="--connect_timeout={$connect_timeout}";
	}
	if($interactive_timeout>0){
		$f[]="--interactive_timeout={$interactive_timeout}";
	}
		$f[]="--myisam_repair_threads=4";
	if($key_buffer_size>0){
		$key_buffer_size=($key_buffer_size*1024)*1000;
		$f[]="--key_buffer_size={$key_buffer_size}";
	}
	
	$f[]="--query_cache_type=1";
	if($table_open_cache>0){
		$f[]="--table_open_cache={$table_open_cache}";
	}
	
	$f[]="--myisam_use_mmap=0";
	$f[]="--max_user_connections=0";
	if($myisam_sort_buffer_size>0){
		$myisam_sort_buffer_size=($myisam_sort_buffer_size*1024)*1000;
		$f[]="--myisam_sort_buffer_size={$myisam_sort_buffer_size}";
	}
	if($bulk_insert_buffer_size>0){
		$bulk_insert_buffer_size=($bulk_insert_buffer_size*1024)*1000;
		$f[]="--bulk_insert_buffer_size={$bulk_insert_buffer_size}";
	}
	
	if($read_rnd_buffer_size>1){
		$read_rnd_buffer_size=($read_rnd_buffer_size*1024)*1000;
		$f[]="--read_rnd_buffer_size={$read_rnd_buffer_size}";
	}
	
	if($thread_cache_size>0){
		$f[]="--thread_cache_size=$thread_cache_size";
	}
	
	if($tmp_table_size>0){
		$tmp_table_size=($tmp_table_size*1024)*1000;
		$f[]="--tmp_table_size={$tmp_table_size}";
		$f[]="--max_heap_table_size={$tmp_table_size}";
	}
	
	if($max_tmp_table_size>0){
		if($GetStartedValues["--max_tmp_table_size"]){
			$max_tmp_table_size=($max_tmp_table_size*1024)*1000;
			$f[]="--max_tmp_table_size={$max_tmp_table_size}";
		}
	}

	if($net_read_timeout>0){
		if($GetStartedValues["--net_read_timeout"]){
			$f[]="--net_read_timeout={$net_read_timeout}";
		}
	}
	
	if($sort_buffer_size>0){
		$sort_buffer_size=($sort_buffer_size*1024)*1000;
		$f[]="--sort_buffer_size={$sort_buffer_size}";
	}
	if($read_buffer_size>0){
		$read_buffer_size=($read_buffer_size*1024)*1000;
		$f[]="--read_buffer_size={$read_buffer_size}";
	}
	if($join_buffer_size>0){
		$join_buffer_size=($join_buffer_size*1024)*1000;
		$f[]="--join_buffer_size={$join_buffer_size}";
	}		

	if($thread_stack>0){
		$thread_stack=($thread_stack*1024)*1000;
		$f[]="--thread_stack={$thread_stack}";
	}

	$f[]="--tmpdir=$tmpdir";
	
	
	$f[]="--log-warnings=2";
	
	$f[]="--default-storage-engine=myisam";
	if($GetStartedValues["--default-tmp-storage-engine"]){
		$f[]="--default-tmp-storage-engine=myisam";
	}
	$f[]=$net;
	
	$TMP=$unix->FILE_TEMP();
	
	$cmdline=@implode(" ", $f);
	
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Starting MySQL daemon ($SERV_NAME)\n";}
	
	@unlink("/opt/squidsql/error.log");
	
	
	shell_exec("$nohup $cmdline >$TMP 2>&1 &");
	sleep(1);
	for($i=0;$i<5;$i++){
		$pid=SQUIDDB_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon ($SERV_NAME) started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon wait $i/5\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=SQUIDDB_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon ($SERV_NAME) failed to start\n";}
		if(is_file($TMP)){
			$f=explode("\n",@file_get_contents($TMP));
			while (list ($num, $ligne) = each ($f) ){if(trim($ligne)==null){continue;}if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $ligne\n";}}
		}
		
		$f=explode("\n", @file_get_contents("/opt/squidsql/error.log"));
		while (list ($num, $ligne) = each ($f) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL Results \"$ligne\"\n";}
			if(preg_match("#Incorrect information in file: './mysql/proxies_priv.frm'#", $ligne)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: remove MySQL tables and install again...\n";}
				shell_exec("/bin/rm -rf $WORKDIR/data/mysql/*");
				shell_exec("$nohup $php5 ".__FILE__." --start --recall >/dev/ null 2>&1 &");
				return;
			}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon ($SERV_NAME) success\n";}
		if($CREATEDB){$q=new mysql_squid_builder();$q->CheckTables();}
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $cmdline\n";}}
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
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
			return;
		}
	}
	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon ($SERV_NAME) already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Stopping MySQL Daemon ($SERV_NAME) with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: Stopping MySQL Daemon ($SERV_NAME) smoothly...\n";}
	$cmd="$mysqladmin --socket=/var/run/mysqld/squid-db.sock  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=SQUIDDB_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon ($SERV_NAME) kill pid $pid..\n";}
			shell_exec("$kill -9 $pid");
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: MySQL daemon ($SERV_NAME) wait $i/10\n";}
		sleep(1);
	}	
	$pid=SQUIDDB_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: MySQL daemon ($SERV_NAME) Failed...\n";}
}

function SQUIDDB_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/squid-db.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/opt/squidsql/bin/mysqld");
	
}


function changemysqldir($dir){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/squiddbstart.pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: Already task running PID $oldpid since {$time}mn\n";}
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
	echo "Copy /opt/zarafa-db/data content to next dir size=$Size";
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $WORKDIR/ibdata1 no such file\n";}
	}
	
	if(is_file("/usr/share/mysql/mysql_system_tables.sql")){
		if(is_file($mysqld_safe)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $mysqld_safe with mysql_system_tables.sql\n";}
			$cmd="$mysqld_safe --defaults-file=/opt/squidsql/my.cnf --log-error=/opt/squidsql/error.log --user=root --socket=/var/run/mysqld/squid-db.sock2 --basedir=/opt/squidsql --datadir=/opt/squidsql/data --skip-networking --plugin_dir=/opt/squidsql/lib/plugin --init-file=/usr/share/mysql/mysql_system_tables.sql --verbose";
			shell_exec($cmd);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: `$cmd`\n";}
			
			
			if(is_file("/usr/share/mysql/mysql_system_tables_data.sql")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: $mysqld_safe with mysql_system_tables_data.sql\n";}
				shell_exec("$mysqld_safe --defaults-file=/opt/squidsql/my.cnf --log-error=/opt/squidsql/error.log --socket=/var/run/mysqld/squid-db.sock2 --user=root --basedir=/opt/squidsql --datadir=/opt/squidsql/data --skip-networking --plugin_dir=/opt/squidsql/lib/plugin --init-file=/usr/share/mysql/mysql_system_tables_data.sql --verbose");
				
			}
		}
		
	}
	if(!is_file("$WORKDIR/data/mysql/user.MYD")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: checks with `$mysql_install_db`\n";}
		if(is_file("$mysql_install_db")){
			$cmd="$mysql_install_db --basedir=$WORKDIR --plugin_dir=$WORKDIR/lib/plugin --datadir=$WORKDIR/data --skip-name-resolve --user=root --force --no-defaults --log-error=/opt/squidsql/error.log >/dev/null 2>&1";
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: `$cmd`\n";}
			shell_exec($cmd);
		}
	}
}
	
?>