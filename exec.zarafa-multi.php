<?php

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.zarafa-multi.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
if(system_is_overloaded(basename(__FILE__))){echo "Overloaded, die()";die();}
$GLOBALS["NOWATCH"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--multi#",implode(" ",$argv))){$GLOBALS["MULTI"]=true;}
if(preg_match("#--nowtachdog#",implode(" ",$argv))){$GLOBALS["NOWATCH"]=true;}



if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
$unix=new unix();
$unix->events("Executing ".@implode(" ",$argv));
$GLOBALS["CLASS_USERS"]=new settings_inc();
$GLOBALS["CLASS_UNIX"]=$unix;

if($argv[1]=="--start"){multi_start($argv[2]);die();}
if($argv[1]=="--stop"){multi_stop($argv[2]);die();}
if($argv[1]=="--restart"){multi_restart($argv[2]);die();}
if($argv[1]=="--start-all"){multi_start_all();die();}
if($argv[1]=="--status"){multi_status();die();}
if($argv[1]=="--delete"){multi_delete($argv[2]);die();}




function multi_status(){
	$users=new usersMenus();
	if(!$users->ZARAFA_INSTALLED){die();}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT ID  FROM `zarafamulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if($GLOBALS["VERBOSE"]){echo "Count -> ".mysql_num_rows($results);}
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]=status_zarafa_licensed($ligne["ID"]);
		$f[]=status_zarafa_dagent($ligne["ID"]);
		$f[]=status_zarafa_gateway($ligne["ID"]);
		$f[]=status_zarafa_monitor($ligne["ID"]);
		$f[]=status_zarafa_server($ligne["ID"]);
		$f[]=status_zarafa_spooler($ligne["ID"]);
	}
	echo @implode("\n", $f);
	if($GLOBALS["NOWATCH"]){return;}
	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 ".__FILE__." --start-all >/dev/null 2>&1 &";
	writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}


function multi_start_all(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}	
	@file_put_contents($pidfile,getmypid());
	
	
	
	$sql="SELECT ID  FROM `zarafamulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {multi_start($ligne["ID"]);}
	
}

function multi_get_pid($ID){
	$unix=new unix();
	$pidfile="/var/run/zarafa-server-$ID.pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){
		if($unix->process_exists($pid)){return $pid;}
	}
	if($GLOBALS["VERBOSE"]){echo "Starting......: zarafa-server instance id:$ID $pidfile ($pid) not running\n";}
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"--config=/etc/zarafa-$ID\" 2>&1";
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}
	}
	return null;
}
function DAEMONS_PID($binary,$ID){
	$unix=new unix();
	
	if($binary=="licensed"){
		$pidfile="/var/run/zarafa-licensed-$ID.pid";
		$bin_path=$unix->find_program("zarafa-licensed");
		$pattern="$bin_path.+?--config=/etc/zarafa-$ID";
	}
	
	if($binary=="monitor"){
		$pidfile="/var/run/zarafa-monitor-$ID.pid";
		$bin_path=$unix->find_program("zarafa-monitor");
		$pattern="$bin_path.+?--config=/etc/zarafa-$ID";
	}

	if($binary=="spooler"){
		$pidfile="/var/run/zarafa-spooler-$ID.pid";
		$bin_path=$unix->find_program("zarafa-spooler");
		$pattern="$bin_path.+?--config=/etc/zarafa-$ID";
	}
	
	if($binary=="gateway"){
		$pidfile="/var/run/zarafa-gateway-$ID.pid";
		$bin_path=$unix->find_program("zarafa-gateway");
		$pattern="$bin_path.+?--config=/etc/zarafa-$ID";
	}	

	if($binary=="dagent"){
		$pidfile="/var/run/zarafa-dagent-$ID.pid";
		$bin_path=$unix->find_program("zarafa-dagent");
		$pattern="$bin_path.+?-d -c /etc/zarafa-$ID";
	}	
			
		
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){if($unix->process_exists($pid)){return $pid;}}
	
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"$bin_path.+?--config=/etc/zarafa-$ID\" 2>&1";
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){if(preg_match("#pgrep -l#", $ligne)){continue;}if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}}
	return null;
}


function multi_stop_server($ID){
	if(!is_numeric($ID)){echo "Stopping......: zarafa-server instance no id specified\n";return;}
	$PID=multi_get_pid($ID);
	echo "Stopping......: zarafa-server instance id:$ID PID:$PID..\n";
	$unix=new unix();
	if(!$unix->process_exists($PID)){echo "Stopping......: zarafa-server instance id:$ID already stopped..\n";return;}
	$kill=$unix->find_program("kill");
	
	
	shell_exec("$kill $PID");
	sleep(1);
	
	for($i=0;$i<10;$i++){
		$PID=multi_get_pid($ID);
		if(!$unix->process_exists($PID)){break;}
		if(is_numeric($PID)){
			$cmd="$kill -9 $PID";
			echo "Stopping......: zarafa-server instance id:$ID killing PID: $PID\n";
			shell_exec($cmd);
			sleep(1);
		}
	}
	$PID=multi_get_pid($ID);
	if(!$unix->process_exists($PID)){echo "Stopping......: zarafa-licensed instance id:$ID success..\n";return;}	
	echo "Stopping......: zarafa-licensed instance id:$ID failed..\n";
}
function multi_stop_daemons($binary,$ID){
	if(!is_numeric($ID)){echo "Stopping......: zarafa-$binary instance no id specified\n";return;}
	$PID=DAEMONS_PID($binary,$ID);
	echo "Stopping......: zarafa-$binary instance id:$ID PID:$PID..\n";
	$unix=new unix();
	if(!$unix->process_exists($PID)){echo "Stopping......: zarafa-$binary instance id:$ID already stopped..\n";return;}
	$kill=$unix->find_program("kill");
	
	
	shell_exec("$kill $PID");
	sleep(1);
	
	for($i=0;$i<10;$i++){
		$PID=DAEMONS_PID($binary,$ID);
		if(!$unix->process_exists($PID)){break;}
		if(is_numeric($PID)){$cmd="$kill -9 $PID";echo "Stopping......: zarafa-$binary instance id:$ID killing PID: $PID\n";shell_exec($cmd);sleep(1);}
	}
	
	$PID=DAEMONS_PID($binary,$ID);
	if(!$unix->process_exists($PID)){echo "Stopping......: zarafa-$binary instance id:$ID success..\n";return;}	
	echo "Stopping......: zarafa-$binary instance id:$ID failed..\n";
}

function multi_start($ID){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}	
	@file_put_contents($pidfile,getmypid());
	
	$sf=new zarafamulti($ID);
	if($sf->enabled==0){echo "Starting......: zarafa-server instance is disabled, stop it\n";multi_stop($ID);return;}	
	
	multi_start_server($ID);
	multi_start_daemons("licensed",$ID);
	multi_start_daemons("monitor",$ID);
	multi_start_daemons("spooler",$ID);
	multi_start_daemons("dagent",$ID);
	if($sf->GatewayEnabled==1){
		multi_start_daemons("gateway",$ID);
	}
	
}
function multi_stop($ID){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile,getmypid());
	
	@file_put_contents($pidfile, getmypid());	
	multi_stop_daemons("licensed",$ID);
	multi_stop_daemons("monitor",$ID);
	multi_stop_daemons("spooler",$ID);
	multi_stop_daemons("dagent",$ID);
	multi_stop_daemons("gateway",$ID);
	multi_stop_server($ID);
}

function multi_restart($ID){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile,getmypid());
	
	multi_stop($ID);
	multi_start($ID);
}

function multi_delete($ID){
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	echo "Deleting......: zarafa-server instance id:$ID..\n";
	echo "Deleting......: zarafa-server disable instance\n";
	$q=new mysql();
	$q->QUERY_SQL("UPDATE zarafamulti SET enabled=0 WHERE ID='$ID'","artica_backup");
	echo "Deleting......: zarafa-server stopping instance\n";
	multi_stop($ID);
	
	$zarafa=new zarafamulti($ID);
	echo "Deleting......: zarafa-server removing directory $zarafa->attachment_path\n";
	if(is_dir($zarafa->attachment_path)){shell_exec("$rm -rf $zarafa->attachment_path");}
	echo "Deleting......: zarafa-server removing directory /etc/zarafa-$ID\n";
	if(is_dir("/etc/zarafa-$ID")){shell_exec("$rm -rf /etc/zarafa-$ID");}
	
	
	$database="zarafa$ID";
	echo "Deleting......: zarafa-server removing database $database\n";
	if($zarafa->mysql_instance_id>0){
		$q=new mysql_multi($zarafa->mysql_instance_id);
		$q->QUERY_SQL_NO_BASE("DROP DATABASE `$database`");
	}else{
		$q=new mysql();
		$q->DELETE_DATABASE($database);
	}
	
	echo "Deleting......: zarafa-server removing entry\n";
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM zarafamulti WHERE ID=$ID","artica_backup");
	
	if($zarafa->PostfixInstance<>null){
		echo "Deleting......: zarafa-server reconfigure $zarafa->PostfixInstance postfix instance \n";
		$sock=new sockets();
		$sock->getFrameWork("postfix.php?reconfigure-single-instance=$zarafa->PostfixInstance");
		
	}
	
	echo "Deleting......: zarafa-server done...\n";
	
}

function multi_start_server($ID){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$oldpid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
		
	
	$q=new zarafamulti($ID);
	echo "Starting......: zarafa-server instance id:$ID..\n";

	$q->Build();
	$unix=new unix();
	if($unix->process_exists(multi_get_pid($ID))){echo "Starting......: zarafa-server instance id:$ID already running...\n";return;}
	$chmod=$unix->find_program("chmod");
	$zarafa_server=$unix->find_program("zarafa-server");
	$cmd="$zarafa_server --config=/etc/zarafa-$ID/server.cfg";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){echo "Starting......: zarafa-server instance id:$ID $ligne\n";}
	
	for($i=0;$i<4;$i++){
		sleep(1);
		if($unix->process_exists(multi_get_pid($ID))){sleep(1);break;}
	}
	
	if(!$unix->process_exists(multi_get_pid($ID))){
		echo "Starting......: zarafa-server instance id:$ID failed..\n";
		return;
	}
	echo "Starting......: zarafa-server instance success..\n";
	
}
function multi_start_daemons($binary,$ID){
	
	
	$unix=new unix();
	if($unix->process_exists(DAEMONS_PID($binary,$ID))){echo "Starting......: $binary: instance id:$ID already running...\n";return;}
	$chmod=$unix->find_program("chmod");
	$daemon=$unix->find_program("zarafa-$binary");
	
	if(!is_file("/etc/zarafa-$ID/$binary.cfg")){
		echo "Starting......: $binary: instance id:$ID /etc/zarafa-$ID/$binary.cfg no such file\n";
		return;
	}
	
	$cmd="$daemon --config=/etc/zarafa-$ID/$binary.cfg";
	
	if($binary=="dagent"){$cmd="$daemon -d -c /etc/zarafa-$ID/$binary.cfg";}
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	while (list ($index, $ligne) = each ($results) ){echo "Starting......: $binary: instance id:$ID $ligne\n";}
	
	for($i=0;$i<4;$i++){
		sleep(1);
		if($unix->process_exists(DAEMONS_PID($binary,$ID))){sleep(1);break;}
	}
	
	if(!$unix->process_exists(DAEMONS_PID($binary,$ID))){
		echo "Starting......: $binary: instance id:$ID failed..\n";
		return;
	}
	echo "Starting......: $binary: instance success..\n";
}


function status_zarafa_server($ID){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=1;
	$pid_path="/var/run/zarafa-server-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!is_numeric($enabled)){$enabled=1;}
	
	$l[]="[APP_ZARAFA_SERVER:$ID]";
	$l[]="service_name=APP_ZARAFA_SERVER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	
	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		multi_start($ID);
		$l[]="running=0\ninstalled=1";$l[]="";

	}else{
		$l[]="running=1";
	}



	$meme=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]=$meme;
	$l[]="";
	$l[]="[APP_ZARAFA:$ID]";
	$l[]="service_name=APP_ZARAFA";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="family=mailbox";
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ZARAFA","zarafa");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=$meme;
	$l[]="";
	return implode("\n",$l);
}
function status_zarafa_dagent($ID){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=1;
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/zarafa-dagent-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[APP_ZARAFA_DAGENT:$ID]";
	$l[]="service_name=APP_ZARAFA_DAGENT";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
function status_zarafa_monitor($ID){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=1;
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/zarafa-monitor-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[APP_ZARAFA_MONITOR:$ID]";
	$l[]="service_name=APP_ZARAFA_MONITOR";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=mailbox";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	if($enabled==0){return implode("\n",$l);return;}	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
function status_zarafa_gateway($ID){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$zf=new zarafamulti($ID);
	if($zf->GatewayEnabled==0){return;}
	$pid_path="/var/run/zarafa-gateway-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[APP_ZARAFA_GATEWAY:$ID]";
	$l[]="service_name=APP_ZARAFA_GATEWAY";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
function status_zarafa_spooler($ID){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}	
	$pid_path="/var/run/zarafa-spooler-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[APP_ZARAFA_SPOOLER:$ID]";
	$l[]="service_name=APP_ZARAFA_SPOOLER";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	if($enabled==0){return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
function status_zarafa_licensed($ID){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$pid_path="/var/run/zarafa-licensed-$ID.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[APP_ZARAFA_LICENSED:$ID]";
	$l[]="service_name=APP_ZARAFA_LICENSED";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);}
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
?>