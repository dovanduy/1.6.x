<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Kaspersky Anti-Virus for Proxy Server";
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.kav4proxy.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--reload-avbase"){$GLOBALS["OUTPUT"]=true;reload_avbase();die();}
if($argv[1]=="--stats"){$GLOBALS["OUTPUT"]=true;reload_stats();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}


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
	sleep(1);
	start(true);
	
}
function reload() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($kavicapserverEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Reloading......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not enabled,stop it\n";}
		stop();
		return;
	}
	
	
	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
		
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running, start it\n";}
		start();
		return;
	}
	build();
	$timepid=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} TTL of {$timepid}Mn\n";}
	$unix->KILL_PROCESS($pid,1);


}
function reload_avbase() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());

	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($kavicapserverEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Reloading......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not enabled,stop it\n";}
		stop();
		return;
	}


	$pid=PID_NUM();

	if(!$unix->process_exists($pid)){

		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running, start it\n";}
		start();
		return;
	}

	$timepid=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} TTL of {$timepid}Mn for databases\n";}
	$unix->KILL_PROCESS($pid,10);


}

function reload_stats(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($kavicapserverEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Reloading......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not enabled,stop it\n";}
		stop();
		return;
	}
	
	
	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
	
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running, start it\n";}
		start();
		return;
	}
	
	$timepid=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} TTL of {$timepid}Mn for statistics\n";}
	$unix->KILL_PROCESS($pid,12);
}

function kav4proxy_version(){
	
	if(isset($GLOBALS["kav4proxy_version"])){return $GLOBALS["kav4proxy_version"];}
	exec("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver -v 2>&1",$results);
	while (list ($none, $line) = each ($results)){
		if(preg_match("#version\s+([0-9\.]+)\/RELEASE build.*?([0-9]+)#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1]." build {$re[2]}";
			break;
		}
		if(preg_match("#version\s+([0-9\.]+)\/RELEASE#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1];
			break;
		}
		if(preg_match("#version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1];
			break;
		}
	}
	
	return $GLOBALS["kav4proxy_version"];
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Not installed\n";}
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
	
	
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		$sock->SET_INFO("kavicapserverEnabled", 0);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}

	$pid=PID_NUM();
	$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if($kavicapserverEnabled==0){stop();}
		
		return;
	}
	
	
	

	if($kavicapserverEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see kavicapserverEnabled)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$unix->CreateUnixUser("kluser","klusers");
	

	build();
	$version=kav4proxy_version();
	$KL_SERVICE_CONFIG="/etc/opt/kaspersky/kav4proxy.conf";
	$f[]=$nohup;
	$f[]=$Masterbin;
	$f[]="-C \"$KL_SERVICE_CONFIG\"";
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
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
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}


function build(){
	$unix=new unix();
	$DIRS[]="/var/run/kav4proxy";
	$DIRS[]="/etc/opt/kaspersky";
	$DIRS[]="/tmp/Kav4proxy";
	$DIRS[]="/var/log/kaspersky/kav4proxy";
	$DIRS[]="/var/log/artica-postfix/ufdbguard-blocks";
	$DIRS[]="/opt/kaspersky/kav4proxy/share/notify";
	
	while (list ($none, $path) = each ($DIRS)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Permissions on $path\n";}
		@mkdir("$path",0755,true);
		$unix->chown_func("kluser","klusers",$path);
	}	
	$kav=new Kav4Proxy();
	$conf=$kav->build_config();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building /etc/opt/kaspersky/kav4proxy.conf done\n";}
	@file_put_contents("/etc/opt/kaspersky/kav4proxy.conf",$conf);
	
	$kav->LoadTemplates();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".count($kav->templates_data)." templates\n";}
	
	while (list ($templateName, $val) = each ($kav->templates_data) ){
		if(is_array($val)){echo "Warning $templateName: val is array\n";}
		if(strlen($val)<100){echo "Warning $templateName: val lenght is not supported!\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} building \"$templateName\" template\n";}
		@file_put_contents("/opt/kaspersky/kav4proxy/share/notify/$templateName", $val);
		@chmod("/opt/kaspersky/kav4proxy/share/notify/$templateName",0755);
		@chown("/opt/kaspersky/kav4proxy/share/notify/$templateName","kluser");
	}
	
	
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

function PID_NUM(){
	$PID_PATH="/var/run/kav4proxy/kavicapserver.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($PID_PATH);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver");
	
}
?>