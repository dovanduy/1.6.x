<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["instance-status"])){instance_status();exit;}
if(isset($_GET["instance-delete"])){instance_delete();exit;}
if(isset($_GET["instance-reconfigure"])){instance_reconfigure();exit;}
if(isset($_GET["instance-reconfigure-all"])){instance_reconfigure_all();exit;}
if(isset($_GET["instance-service"])){instance_service();exit;}
if(isset($_GET["instance-memory"])){instance_memory();exit;}
if(isset($_GET["multi-root"])){instance_root_set();exit;}
if(isset($_GET["filstats"])){filstats();exit;}
if(isset($_GET["backuptable"])){backuptable();exit;}
if(isset($_GET["mysqlreport"])){mysqlreport();exit;}
if(isset($_GET["MysqlTunerRebuild"])){MysqlTunerRebuild();exit;}
if(isset($_GET["rescan-db"])){mysql_rescan_db();exit;}
if(isset($_GET["dumpwebdb"])){mysql_dump_database();exit;}
if(isset($_GET["convert-innodb-file-persize"])){mysql_convert_innodb();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
if(isset($_GET["mysql-upgrade"])){mysql_upgrade();exit;}


reset($_GET);
while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function instance_status(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$unix=new unix();
	$pid=multi_get_pid($instance_id);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){echo "<articadatascgi>ON</articadatascgi>";return;}
	echo "<articadatascgi>OFF</articadatascgi>";
}
function instance_memory(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$unix=new unix();
	$pid=multi_get_pid($instance_id);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){
		$rss=$unix->PROCESS_MEMORY($pid,true);
		$vm=$unix->PROCESS_CACHE_MEMORY($pid,true);
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize(array($rss,$vm)))."</articadatascgi>";
}

function instance_service(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();		
	$instance_id=$_GET["instance_id"];
	$action=$_GET["action"];
	$results[]="Action: $action";
	if($action=="start"){
		$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start $instance_id 2>&1";
	}else{
		$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-stop $instance_id 2>&1";
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function instance_root_set(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();		
	$instance_id=$_GET["instance-id"];
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql-multi.php --rootch $instance_id 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function multi_get_pid($ID){
	$unix=new unix();
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pidfile)){
		if($unix->process_exists($pid)){
			writelogs_framework("$pidfile ->$pid",__FUNCTION__,__FILE__,__LINE__);
			return $pid;
		}
	}
	
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"socket=/var/run/mysqld/mysqld$ID.sock\" 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){
			writelogs_framework("$ligne -> {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			return $re[1];}
	}
	return null;
}

function instance_reconfigure(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start {$_GET["instance-id"]} >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function filstats(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --dbstats --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function instance_reconfigure_all(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start-all >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function MysqlTunerRebuild(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --mysqltuner --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function instance_delete(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$database_path=$ini->get("mysqld$instance_id","datadir");
	$unix=new unix();
	if(is_file("/usr/sbin/mysqlmulti-start{$instance_id}")){@unlink("/usr/sbin/mysqlmulti-start{$instance_id}");}
	if(is_file("/usr/sbin/mysqlmulti-stop{$instance_id}")){@unlink("/usr/sbin/mysqlmulti-stop{$instance_id}");}
	if(is_file("/etc/monit/conf.d/mysqlmulti$instance_id.monitrc")){@unlink("/etc/monit/conf.d/mysqlmulti$instance_id.monitrc");}
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	
	
	$rm=$unix->find_program("rm");
	$kill=$unix->find_program("kill");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$pid=$unix->get_pid_from_file($pidfile);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){
		$cmd="$kill -9 $pid";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	writelogs_framework("database path -> '$database_path'",__FUNCTION__,__FILE__,__LINE__);
	if(is_dir($database_path)){
		$cmd="$rm -rf \"$database_path\" 2>&1";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function backuptable(){
	$PARAMS=unserialize(base64_decode($_GET["backuptable"]));
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){
		echo "<articadatascgi>". base64_encode("ERROR: mysqldump no such binary")."</articadatascgi>";
		return;
	}
	
	$t=time();
	$tfile="{$PARAMS["PATH"]}/{$PARAMS["DB"]}.{$PARAMS["TABLE"]}.$t.sql";
	
	if(!is_numeric($PARAMS["PORT"])){$PARAMS["PORT"]=3306;}
	$PARAMS["PASS"]=escapeshellarg($PARAMS["PASS"]);
	@mkdir($PARAMS["PATH"],0755,true);
	$cmd="$mysqldump --user={$PARAMS["ROOT"]} --password={$PARAMS["PASS"]} --port={$PARAMS["PORT"]} --host={$PARAMS["HOST"]} {$PARAMS["DB"]} {$PARAMS["TABLE"]} > $tfile 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	if(!is_file($tfile)){
		echo "<articadatascgi>". base64_encode("ERROR: mysqldump $tfile no such file")."</articadatascgi>";
		return;		
	}
	
	$filesize=$unix->file_size($tfile);
	$filesize=round($filesize/1024);
	echo "<articadatascgi>".base64_encode("$tfile ($filesize K) done\n".@implode("\n", $results))."</articadatascgi>";
	
	
	
}
function mysqlreport(){
	$user=base64_decode($_GET["user"]);
	$password=base64_decode($_GET["password"]);
	$socket=base64_decode($_GET["socket"]);
	$hostname=base64_decode($_GET["hostname"]);
	$port=base64_decode($_GET["port"]);
	
	$instanceid=$_GET["instance-id"];
	if(!is_numeric($instanceid)){$instanceid=0;}
	if($instanceid==0){
		$user=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	}
	
	writelogs("password: ".strlen($password),__FUNCTION__,__FILE__,__LINE__);
	
	if($socket<>null){
		if(!is_file($socket)){
			$socket=" --socket $socket";
		}
	}
	
	if($socket==null){
		if($hostname<>null){
			$socket=" --host $hostname --port $port";
		}
	}
	
	if($user<>null){
		$user=" --user $user";
		if($passord<>null){
			$user=" --user $user --password \"$passord\"";
		}
	}
	
	$unix=new unix();
	$mysqlreport=$unix->find_program("mysqlreport");
	if(strlen($mysqlreport)<4){
		$mysqlreport="/usr/share/artica-postfix/bin/mysqlreport";
		@chmod($mysqlreport, 0755);
	}
	
	$cmd="$mysqlreport$socket$user 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	while (list ($key, $value) = each ($results) ){
	if(preg_match("#Access denied for user#", $value)){
		$results=array();
		$cmd="$mysqlreport$socket --user=root 2>&1";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
		break;
		}
	}
	
	
	reset($results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function mysql_rescan_db(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --database-rescan {$_GET["instance-id"]} {$_GET["database"]} --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}
function mysql_dump_database(){
	$database=$_GET["database"];
	$instance=$_GET["instance"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --database-dump {$_GET["database"]} {$_GET["instance-id"]} --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}
function mysql_convert_innodb(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/mysqldefrag.php --innodbfpt >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function getramtmpfs(){
	$dir=base64_decode($_GET["dir"]);
	if($dir==null){return;}
	$unix=new unix();
	$df=$unix->find_program("df");
	$cmd="$df -h \"$dir\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$df -h \"$dir\" 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(!preg_match("#tmpfs\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.]+)%#", $value,$re)){
			writelogs_framework("$value no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		writelogs_framework("{$re[2]}:{$array["PURC"]}%",__FUNCTION__,__FILE__,__LINE__);
			$array["SIZE"]=$re[1];
			$array["PURC"]=$re[4];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			return;
		
	}
		
}
function mysql_upgrade(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --mysql-upgrade {$_GET["instance-id"]}  >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}	




