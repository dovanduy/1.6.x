<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["TITLENAME"]="NTLM Proxy";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}



function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress("{failed} Already Artica task running PID $pid since {$time}mn",110);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress("{stopping_service}",10);
	stop(true);
	
	sleep(1);
	build_progress("{starting_service}",50);
	start(true);
	build_progress("{done}",100);
	
}
function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo $text."\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/cntlm.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("cntlm");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
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
		build_progress("{starting_service}",90);
		return;
	}
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){$EnableCNTLM=0;}

	if($EnableCNTLM==0){
		build_progress("Service disabled",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableKerbAuth,EnableCNTLM)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	$cmd=build();
	build_progress("{starting_service}",60);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmd);

	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
	build_progress("{starting_service}",70);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
	build_progress("{starting_service}",90);

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
		build_progress("{stopping_service}",45);
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	build_progress("{stopping_service}",15);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	build_progress("{stopping_service}",20);
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress("{stopping_service}",45);
		return;
	}

	build_progress("{stopping_service}",25);
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
		build_progress("{stopping_service}",45);
		return;
	}
	build_progress("{stopping_service}",45);
	
	


}

function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	$cntlm=$unix->find_program("cntlm");
	return $unix->PIDOF_PATTERN("$cntlm.*?cntlm\.pid");
}

function PID_PATH(){
	return "/var/run/cntlm.pid";
}


function build(){
	$sock=new sockets();
	$q=new mysql();
	$unix=new unix();
	$cntlm=$unix->find_program("cntlm");
	$CnTLMPORT=$sock->GET_INFO("CnTLMPORT");
	$CnTLMDESTPORT=intval($sock->GET_INFO("CnTLMPORT"));
	$CnTLMDESTPORT=$sock->GET_INFO("CnTLMDESTPORT");
	
	
	
	
	$configfile="/etc/cntlm.conf";
	
	if($CnTLMDESTPORT==null){
		$SquidBinIpaddr="0.0.0.0";
		$SquidListen=get_squid_listen_ports();
		if(preg_match("#([0-9\.]+):([0-9]+)#", $SquidListen,$re)){if($re[2]==$CnTLMPORT){$CnTLMPORT=0;}}
		if(preg_match("#([0-9]+)$#", $SquidListen,$re)){if($re[2]==$CnTLMPORT){$CnTLMPORT=0;}}
	}else{
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		preg_match("#^(.*?):(.+)#", $CnTLMDESTPORT,$re);
		$nic=trim($re[1]);
		$port=trim($re[2]);
		if($nic==null){$ipaddr="0.0.0.0";}
		if($nic<>null){$ipaddr=$NETWORK_ALL_INTERFACES[$nic]["IPADDR"];}
		$SquidListen="{$ipaddr}:$port";
		
		
	}
	
	if(!is_numeric($CnTLMPORT)){$CnTLMPORT=0;}
	if($CnTLMPORT==0){
		$CnTLMPORT=rand(35000, 64000);
		$sock->SET_INFO("CnTLMPORT", $CnTLMPORT);
	}	
	
	

	if($CnTLMPORT==0){
		$CnTLMPORT=rand(35000, 64000);
		$sock->SET_INFO("CnTLMPORT", $CnTLMPORT);
	}	
	build_progress("Listen port $CnTLMPORT -> $SquidListen",60);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen Port...: `$CnTLMPORT`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy to......: `$SquidListen`\n";}
	
	if(!is_file("/etc/cntlm.conf.bak")){@copy("/etc/cntlm.conf", "/etc/cntlm.conf.bak");}
	
	$f[]="$cntlm -l 0.0.0.0:$CnTLMPORT";
	$f[]="-g";
	$f[]="-B";
	$f[]="-P /var/run/cntlm.pid";
	@file_put_contents("/etc/cntlm.conf", "#\n");
	$f[]=$SquidListen;
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} command line done\n";}
	return @implode(" ", $f);
		
}

function get_squid_listen_ports(){
	
	
	if(is_file("/etc/squid3/listen_ports.conf")){
		$f=explode("\n", @file_get_contents("/etc/squid3/listen_ports.conf"));
	}else{
		$f=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	}
	
	
	
	while (list ($ID, $line) = each ($f) ){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^http_port\s+(.+)#", $line)){continue;}
		if(preg_match("#(transparent|tproxy|intercept)#i", trim($line))){continue;}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($line),$re)){
			if($re[1]=="127.0.0.1"){continue;}
			return "{$re[1]}:{$re[2]}";
		}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($line),$re)){
			if($re[1]=="127.0.0.1"){continue;}
			return "{$re[1]}:{$re[2]}";
		}

		if(preg_match("#http_port\s+([0-9]+)$#", trim($line),$re)){
			return "0.0.0.0:{$re[1]}";
		}
		if(preg_match("#http_port\s+([0-9]+)\s+#", trim($line),$re)){
			return "0.0.0.0:{$re[1]}";
		}

		if($GLOBALS["VERBOSE"]){echo "Not detected `$line`\n";}
		
	}
	
	
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No proxy port found\n";}
	
	
}




