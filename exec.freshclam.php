<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Clam AntiVirus virus database updater";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
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
	stop(true);
	build();
	sleep(1);
	start(true);

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
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$TimEx=$unix->file_time_min($pidTime);
		if($TimEx<120){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Only each 120mn, current is {$TimEx}mn\n";}
			return;
		}
	}
	@unlink($pidTime);
	@file_put_contents("$pidTime", time());
	
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}

	$Masterbin=$unix->find_program("freshclam");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service freshclam not installed\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building settings\n";}
	build();
	$cmd="$Masterbin --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam.pid --user=clamav --log=/var/log/clamav/freshclam.log >/dev/null 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
	
	
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
	$EnableClamavDaemon=$sock->EnableClamavDaemon();
	
	if(!is_numeric($EnableFreshClam)){$EnableFreshClam=0;}
	if($EnableClamavDaemon==0){$EnableFreshClam=0;}
	
	if($EnableFreshClam==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableFreshClam/EnableClamavDaemon)\n";}
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
	
	
	build();
	$cmd="$nohup $Masterbin --daemon  --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam.pid --user=clamav --log=/var/log/clamav/freshclam.log >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		break;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}}
	


}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("freshclam");
	return $unix->PIDOF($Masterbin);

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

function build(){
	
	$sock=new sockets();
	$unix=new unix();
	$curlstring=$unix->find_program("curl");
	$clamdscan=$unix->find_program("clamdscan");
	$f[]="DatabaseOwner clamav";
	$f[]="UpdateLogFile /var/log/clamav/freshclam.log";
	$f[]="LogVerbose false";
	$f[]="LogSyslog false";
	$f[]="LogFacility LOG_LOCAL6";
	$f[]="LogFileMaxSize 0";
	$f[]="LogTime true";
	$f[]="Foreground false";
	$f[]="Debug false";
	$f[]="MaxAttempts 5";
	$f[]="DatabaseDirectory /var/lib/clamav";
	$f[]="DNSDatabaseInfo current.cvd.clamav.net";
	$f[]="AllowSupplementaryGroups true";
	$f[]="NotifyClamd /etc/clamav/clamd.conf";
	$f[]="PidFile /var/run/clamav/freshclam.pid";
	$f[]="ConnectTimeout 30";
	$f[]="ReceiveTimeout 30";
	$f[]="TestDatabases yes";
	$f[]="ScriptedUpdates yes";
	$f[]="CompressLocalDatabase no";
	$f[]="Bytecode true";
	$f[]="# Check for new database 24 times a day";
	$f[]="Checks 24";
	$f[]="DatabaseMirror db.local.clamav.net";
	$f[]="DatabaseMirror database.clamav.net";
	@mkdir("/etc/clamav",0755,true);
	@file_put_contents("/etc/clamav/freshclam.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} freshclam.conf done\n";}
	
	
	
	
	
	$f=array();
	$f[]="PATH=\"/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin\"";
	$f[]="export PATH";
	$f[]="";
	$f[]="clam_user=\"clamav\"";
	$f[]="clam_group=\"clamav\"";
	$f[]="clam_dbs=\"/var/lib/clamav\"";
	$f[]="clamd_pid=\"/var/run/clamav/clamd.pid\"";
	$f[]="reload_dbs=\"yes\"";
	$f[]="";
	$f[]="reload_opt=\"$clamdscan --reload\"  # Default";
	$f[]="clamd_socket=\"/var/run/clamav/clamav.sock\"";
	$f[]="start_clamd=\"/etc/init.d/clamav-daemon start\"";
	$f[]="enable_random=\"yes\"";
	$f[]="min_sleep_time=\"60\"    # Default minimum is 60 seconds (1 minute).";
	$f[]="max_sleep_time=\"600\"   # Default maximum is 600 seconds (10 minutes).";
	$f[]="ss_dbs=\"";
	$f[]="   junk.ndb";
	$f[]="   jurlbl.ndb";
	$f[]="   phish.ndb";
	$f[]="   rogue.hdb";
	$f[]="   sanesecurity.ftm";
	$f[]="   scam.ndb";
	$f[]="   spamimg.hdb";
	$f[]="   winnow_malware.hdb";
	$f[]="   winnow_malware_links.ndb";
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
	$f[]="curl_proxy=\"$curlstring\"";
	$f[]="user_configuration_complete=\"yes\"";
	@file_put_contents("/etc/clamav-unofficial-sigs.conf", @implode("\n", $f)); 
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} clamav-unofficial-sigs.conf done\n";}
	$f=array();
}
