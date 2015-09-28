#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["TITLENAME"]="Clam AntiVirus virus database updater";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--cli#",implode(" ",$argv),$re)){$GLOBALS["CLI"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

// /etc/clamav/freshclam.conf

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload-database"){$GLOBALS["OUTPUT"]=true;reload_database();die();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--execute"){$GLOBALS["OUTPUT"]=true;execute();die();}
if($argv[1]=="--exec"){$GLOBALS["OUTPUT"]=false;execute();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--updated"){$GLOBALS["OUTPUT"]=false;notify_updated();die();}
if($argv[1]=="--sigtool-ouput"){$GLOBALS["OUTPUT"]=false;sigtool_output();die();}





function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress(10, "{stopping} {APP_FRESHCLAM}");
	stop(true);
	build_progress(50, "{building_configuration}");
	build();
	sleep(1);
	build_progress(70, "{starting} {APP_FRESHCLAM}");
	if(start(true)){
		
		if($GLOBALS["PROGRESS"]){
			build_progress(95, "{restarting} {watchdog}");
			system("/etc/init.d/artica-status restart");
		}
		
		build_progress(100, "{done} {APP_FRESHCLAM}");
	}
	

}
function reload_database($aspid=false){
$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		shell_exec("$kill -USR2 $pid");
		return;
	}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}
	
}
function reload($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}

function execute(){
	$unix=new unix();
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	
	$unix->chown_func("clamav", "clamav","/var/clamav");
	$unix->chown_func("clamav", "clamav","/var/run/clamav");
	$unix->chown_func("clamav", "clamav","/var/lib/clamav");
	$unix->chown_func("clamav", "clamav","/var/log/clamav");	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/var/run/clamav/scheduled.time";
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already Executed since {$time}mn",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$TimEx=$unix->file_time_min($pidTime);
		if($TimEx<120){
			build_progress("Only each 120mn, current is {$TimEx}mn",110);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Only each 120mn, current is {$TimEx}mn\n";}
			return;
		}
	}
	@unlink($pidTime);
	@file_put_contents("$pidTime", time());
	build_progress("{udate_clamav_databases}",10);
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress("Service already started $pid since {$timepid}Mn",110);
		return;
	}

	$Masterbin=$unix->find_program("freshclam");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service freshclam not installed\n";}
		build_progress("Missing freshclam",110);
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building settings\n";}
	build_progress("{building_configuration}",20);
	build();
	
	$verbose=null;
	$log="/var/log/clamav/freshclam.log";
	if($GLOBALS["PROGRESS"]){
		$log="/usr/share/artica-postfix/ressources/logs/web/clamav.update.progress.txt";
		$verbose=" --verbose";
	}
	
	$nohup=$unix->find_program("nohup");
	
	if(is_file(dirname($Masterbin)."/freshexec")){@unlink(dirname($Masterbin)."/freshexec");}
		@copy($Masterbin, dirname($Masterbin)."/freshexec");
		@chmod(dirname($Masterbin)."/freshexec",0755);
		$Masterbin=dirname($Masterbin)."/freshexec";
		
	$cmd="$nohup $Masterbin --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam_manu.pid --user=clamav --log=$log$verbose >/dev/null 2>&1 &";
	
	$Dirs=$unix->dirdir("/var/lib/clamav");
	$rm=$unix->find_program("rm");
	
	while (list ($directory, $MAIN) = each ($Dirs) ){
		echo "Checking $directory\n";
		if(!preg_match("#\.tmp$#", $directory)){continue;}
		echo "Remove directory $directory";
		shell_exec("$rm -rf $directory");
	}
	

	build_progress("{udate_clamav_databases}",50);
	echo $cmd;
	system($cmd);
	
	$PID=fresh_clam_manu_pid();
	$WAIT=true;
	
	while ($WAIT) {
		if(!$unix->process_exists($PID)){
			break;
		}
		$ttl=$unix->PROCCESS_TIME_MIN($PID);
		echo "PID: Running $PID since {$ttl}mn\n";
		build_progress("{udate_clamav_databases} {waiting} PID $PID {since} {$ttl}mn",80);
		sleep(2);
		$PID=fresh_clam_manu_pid();
	}
	
	
	
	
	build_progress("{done}",90);
	@unlink("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
	sigtool();
	build_progress("{done}",100);
	
}

function fresh_clam_manu_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("freshclam");
	$Masterbin=dirname($Masterbin)."/freshexec";
	return $unix->PIDOF_PATTERN(basename($Masterbin).".*?freshclam_manu.pid");

}

function build_progress($text,$pourc){
	$echotext=$text;
	
	if(is_numeric($text)){
		$old=$pourc;
		$pourc=$text;
		$text=$old;
	}
	
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/clamav.update.progress";
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/clamav.freshclam.progress";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);	
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function sigtool_output(){
	sigtool();
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));

	if(count($bases)==0){
		echo "No database !!!!";
		return;
	}
	while (list ($db, $MAIN) = each ($bases) ){
		$DBS[]=$db;
		$DBS[]="-------------------------------";
		$DBS[]="date: {$MAIN["zDate"]}";
		$DBS[]="version: {$MAIN["version"]}";
		$DBS[]="signatures: {$MAIN["signatures"]}";
		$DBS[]="";
	}
	
	echo @implode("\\n", $DBS);
	

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("freshclam");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	$EnableFreshClam=$sock->GET_INFO("EnableFreshClam");
	
	
	if(!is_numeric($EnableFreshClam)){$EnableFreshClam=0;}
	
	if($EnableFreshClam==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableFreshClam/EnableClamavDaemon)\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {disabled}");
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $Masterbin Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	
	$unix->chown_func("clamav", "clamav","/var/clamav");
	$unix->chown_func("clamav", "clamav","/var/run/clamav");
	$unix->chown_func("clamav", "clamav","/var/lib/clamav");
	$unix->chown_func("clamav", "clamav","/var/log/clamav");
	build_progress(71, "{starting} {APP_FRESHCLAM}");
	
	build();
	build_progress(72, "{starting} {APP_FRESHCLAM}");
	$cmd="$nohup $Masterbin --daemon  --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam.pid --user=clamav --log=/var/log/clamav/freshclam.log --on-update-execute=/usr/share/artica-postfix/exec.freshclam.updated.php >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		build_progress(72+$i, "{starting} {APP_FRESHCLAM}");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	build_progress(80, "{starting} {APP_FRESHCLAM}");
	$pid=PID_NUM();
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
		return false;
	}
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
	}
	


}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("freshclam");
	return $unix->PIDOF_PATTERN("$Masterbin.*?--on-update-execute=");

}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$chmod=$unix->find_program("chmod");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function notify_updated(){
	
	sigtool();
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
	while (list ($db, $MAIN) = each ($bases) ){
		$DBS[]=$db;
		$DBS[]="-------------------------------";
		$DBS[]="date: {$MAIN["zDate"]}";
		$DBS[]="version: {$MAIN["version"]}";
		$DBS[]="signatures: {$MAIN["signatures"]}";
		$DBS[]="";
	}
	system_admin_mysql(2, "ClamAV pattern databases updated", @implode("\n", $DBS));
}


function build(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$clamdscan=$unix->find_program("clamdscan");
	
	$FreshClamCheckDay=intval($sock->GET_INFO("FreshClamCheckDay"));
	$FreshClamMaxAttempts=intval($sock->GET_INFO("FreshClamMaxAttempts"));
	if($FreshClamCheckDay==0){$FreshClamCheckDay=16;}
	if($FreshClamMaxAttempts==0){$FreshClamMaxAttempts=16;}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} clamdscan = $clamdscan\n";}
	
	$f[]="DatabaseOwner clamav";
	$f[]="UpdateLogFile /var/log/clamav/freshclam.log";
	$f[]="LogVerbose false";
	$f[]="LogSyslog true";
	$f[]="LogFacility LOG_LOCAL6";
	$f[]="LogFileMaxSize 0";
	$f[]="LogTime true";
	$f[]="Foreground false";
	$f[]="Debug false";
	$f[]="MaxAttempts $FreshClamMaxAttempts";
	$f[]="DatabaseDirectory /var/lib/clamav";
	
	$f[]="AllowSupplementaryGroups true";
	$f[]="NotifyClamd /etc/clamav/clamd.conf";
	$f[]="PidFile /var/run/clamav/freshclam.pid";
	$f[]="ConnectTimeout 30";
	$f[]="ReceiveTimeout 30";
	$f[]="TestDatabases yes";
	$f[]="ScriptedUpdates yes";
	$f[]="CompressLocalDatabase no";
	$f[]="Bytecode true";
	$f[]="# Check for new database $FreshClamCheckDay times a day";
	$f[]="Checks $FreshClamCheckDay";
	$f[]="DNSDatabaseInfo current.cvd.clamav.net";
	$f[]="DatabaseMirror db.local.clamav.net";
	$f[]="DatabaseMirror database.clamav.net";
	$f[]="OnUpdateExecute ".__FILE__." --updated";
	
	
	$HTTPProxyServer=$unix->GET_HTTP_PROXY_STRING();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy:$HTTPProxyServer\n";}
	
	
	if($HTTPProxyServer<>null){
		
		if(preg_match("#\/\/(.+?):([0-9]+)#", $HTTPProxyServer,$re)){
			$f[]="HTTPProxyServer {$re[1]}";
			$f[]="HTTPProxyPort {$re[2]}";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy:$HTTPProxyServer no match\n";}
		}
		
		
	}
	
	
	
	
	
	@mkdir("/etc/clamav",0755,true);
	
	$SecuriteInfoCode=$sock->GET_INFO("SecuriteInfoCode");
	$EnableClamavUnofficial=intval($sock->GET_INFO("EnableClamavUnofficial"));
	
	if($SecuriteInfoCode<>null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Enabled: securiteinfo\n";}
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfo.hdb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfo.ign2";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/javascript.ndb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/spam_marketing.ndb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfohtml.hdb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfoascii.hdb";
	
	}
	
	$f[]="";
	$f[]="";
	@file_put_contents("/etc/clamav/freshclam.conf", @implode("\n", $f));
	
	if($EnableClamavUnofficial==1){
		if(!is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			$CRON[]="MAILTO=\"\"";
			$CRON[]="45 * * * * root /usr/share/artica-postfix/bin/clamav-unofficial-sigs.sh -c /etc/clamav-unofficial-sigs.conf >/dev/null 2>&1";
			$CRON[]="";
			file_put_contents("/etc/cron.d/squid-notifications",@implode("\n", $CRON));
			$CRON=array();
			chmod("/etc/cron.d/clamav-unofficial-sigs-cron",0640);
			chown("/etc/cron.d/clamav-unofficial-sigs-cron","root");
			system("/etc/init.d/cron reload");
		}
	}else{
		if(is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			@unlink("/etc/cron.d/clamav-unofficial-sigs-cron");
			system("/etc/init.d/cron reload");
		}
	}
		
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} freshclam.conf done\n";}
	$f=array();
	$f[]="PATH=\"/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin\"";
	$f[]="export PATH";
	$f[]="";
	$f[]="clam_user=\"clamav\"";
	$f[]="clam_group=\"clamav\"";
	$f[]="clam_dbs=\"/var/lib/clamav\"";
	$f[]="clamd_pid=\"/var/run/clamav/clamd.pid\"";
	$f[]="#reload_dbs=\"yes\"";
	$f[]="";
	$f[]="#reload_opt=\"$clamdscan --reload\"  # Default";
	$f[]="curl_connect_timeout=\"15\"";
	$f[]="curl_max_time=\"90\"";
	$f[]="clamd_socket=\"/var/run/clamav/clamav.sock\"";
	$f[]="#start_clamd=\"/etc/init.d/clamav-daemon start\"";
	$f[]="enable_random=\"yes\"";
	$f[]="min_sleep_time=\"60\"    # Default minimum is 60 seconds (1 minute).";
	$f[]="max_sleep_time=\"600\"   # Default maximum is 600 seconds (10 minutes).";
	$f[]="ss_dbs=\"";
	$f[]="	 blurl.ndb";
	$f[]="   junk.ndb";
	$f[]="   jurlbl.ndb";
	$f[]="   phish.ndb";
	$f[]="   rogue.hdb";
	$f[]="   sanesecurity.ftm";
	$f[]="   scam.ndb";
	$f[]="   sigwhitelist.ign2";
	$f[]="   spamattach.hdb";
	$f[]="   spamimg.hdb";
	$f[]="   winnow.attachments.hdb";
	$f[]="   winnow_bad_cw.hdb";
	$f[]="   winnow_extended_malware.hdb";
	$f[]="   winnow_malware.hdb";
	$f[]="   winnow_malware_links.ndb";
	$f[]="   doppelstern.hdb";
	$f[]="   bofhland_cracked_URL.ndb";
	$f[]="   bofhland_malware_attach.hdb";
	$f[]="   bofhland_malware_URL.ndb";
	$f[]="   bofhland_phishing_URL.ndb";
	$f[]="   crdfam.clamav.hdb";
	$f[]="   phishtank.ndb";
	$f[]="   porcupine.ndb";
	$f[]="\"";
	$f[]="";
	$f[]="si_dbs=\"";
	$f[]="   honeynet.hdb";
	$f[]="   securiteinfobat.hdb";
	$f[]="   securiteinfodos.hdb";
	$f[]="   securiteinfoelf.hdb";
	$f[]="   securiteinfo.hdb";
	$f[]="   securiteinfohtml.hdb";
	$f[]="   securiteinfooffice.hdb";
	$f[]="   securiteinfopdf.hdb";
	$f[]="   securiteinfosh.hdb";
	$f[]="\"";
	$f[]="";
	$f[]="si_update_hours=\"4\"   # Default is 4 hours (6 update checks daily).";
	$f[]="mbl_dbs=\"";
	$f[]="   mbl.ndb";
	$f[]="\"";
	$f[]="mbl_update_hours=\"6\"   # Default is 6 hours (4 downloads daily).";
	$f[]="work_dir=\"/home/clamav/unofficial-dbs\"   #Top level working directory";
	$f[]="ss_dir=\"\$work_dir/ss-dbs\"        # Sanesecurity sub-directory";
	$f[]="si_dir=\"\$work_dir/si-dbs\"        # SecuriteInfo sub-directory";
	$f[]="mbl_dir=\"\$work_dir/mbl-dbs\"      # MalwarePatrol sub-directory";
	$f[]="config_dir=\"\$work_dir/configs\"   # Script configs sub-directory";
	$f[]="gpg_dir=\"\$work_dir/gpg-key\"      # Sanesecurity GPG Key sub-directory";
	$f[]="add_dir=\"\$work_dir/add-dbs\"      # User defined databases sub-directory";
	$f[]="keep_db_backup=\"no\"";
	$f[]="curl_silence=\"no\"      # Default is \"no\" to report curl statistics";
	$f[]="rsync_silence=\"no\"     # Default is \"no\" to report rsync statistics";
	$f[]="gpg_silence=\"no\"       # Default is \"no\" to report gpg signature status";
	$f[]="comment_silence=\"no\"   # Default is \"no\" to report script comments";
	$f[]="";
	$f[]="enable_logging=\"yes\"";
	$f[]="log_file_path=\"/var/log\"";
	$f[]="log_file_name=\"clamav-unofficial-sigs.log\"";
	$unix=new unix();
	$sock=new sockets();
	$CurlProxy=null;
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==1){
			$port=$unix->squid_internal_port();
			$CurlProxy="-x 127.0.0.1:$port";
		}
		
	}
	
	if($CurlProxy==null){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		if(trim($datas)<>null){
			$ini->loadString($datas);
			$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
			$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}
		}
		
		if($ArticaProxyServerEnabled=="yes"){
			$CurlProxy="-x $ArticaProxyServerName:$ArticaProxyServerPort";
			if($ArticaProxyServerUsername<>null){
				$ArticaProxyServerUserPassword=$unix->shellEscapeChars($ArticaProxyServerUserPassword);
				$CurlProxy=$CurlProxy." -U $ArticaProxyServerUsername:$ArticaProxyServerUserPassword";
			}
			
		}
		
	}
	
	@chmod("/usr/share/artica-postfix/exec.freshclam.updated.php", 0755);
	@chmod("/usr/share/artica-postfix/exec.freshclam.sansecurity.updated.php", 0755);
	$f[]="curl_proxy=\"$CurlProxy\"";
	$f[]="user_configuration_complete=\"yes\"";
	@file_put_contents("/etc/clamav-unofficial-sigs.conf", @implode("\n", $f)); 
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} clamav-unofficial-sigs.conf done\n";}
	$f=array();
}

function sigtool(){
		$unix=new unix();
		$sigtool=$unix->find_program("sigtool");
		if(strlen($sigtool)<5){return;;}
		if(is_file("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases")){
			$ttim=$unix->file_time_min("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
			if($ttim<30){return;}
		}
	
		$baseDir="/var/lib/clamav";
	
		$patnz=$unix->DirFiles($baseDir,"\.(cvd|cld|hdb|ign2|ndb)$");
	
		while (list ($path, $none) = each ($patnz) ){
			$patterns[basename($path)]=true;
		}
	
		while (list ($pattern, $none) = each ($patterns) ){
			if(!is_file("$baseDir/$pattern")){continue;}
			$results=array();
			exec("$sigtool --info=$baseDir/$pattern 2>&1",$results);
			while (list ($index, $line) = each ($results) ){
	
				if(preg_match("#Build time:\s+(.+)#", $line,$re)){
					$time=strtotime($re[1]);
					$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s");
					continue;
				}
	
				if(preg_match("#Version:\s+([0-9]+)#",$line,$re)){
					$MAIN[$pattern]["version"]=$re[1];
					continue;
				}
	
				if(preg_match("#Signatures:\s+([0-9]+)#",$line,$re)){
					$MAIN[$pattern]["signatures"]=$re[1];
					continue;
				}
			}
	
			if(!isset($MAIN[$pattern]["zDate"])){
				$time=filemtime("$baseDir/$pattern");
				$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s",$time);
	
				if(!isset($MAIN[$pattern]["version"])){
					$MAIN[$pattern]["version"]=date("YmdHi",$time);
				}
	
			}
			if(!isset($MAIN[$pattern]["signatures"])){
				$MAIN[$pattern]["signatures"]=$unix->COUNT_LINES_OF_FILE("$baseDir/$pattern");
			}
	
		}
		if(count($MAIN)==0){return;}
		@file_put_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases", serialize($MAIN));
	
	}

