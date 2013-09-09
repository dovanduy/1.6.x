<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="SNMP Daemon";
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



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}



function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
		
	stop(true);
	sleep(1);
	start(true);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("snmpd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableSNMPD=$sock->GET_INFO("EnableSNMPD");
	if(!is_numeric($EnableSNMPD)){$EnableSNMPD=0;}
	

	if($EnableSNMPD==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSNMPD)\n";}
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$IPZ[]="127.0.0.1";
	$ips=$unix->NETWORK_ALL_INTERFACES(true);
	while (list ($ip, $line) = each ($ips) ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} listen $ip\n";}
		$IPZ[]=$ip;
		
	}

	$cmd="$Masterbin -Lsd -Lf /dev/null -u root -I -smux -p /var/run/snmpd.pid " .@implode(" ", $IPZ);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build();
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $oldpid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	shell_exec("$kill $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	shell_exec("$kill -9 $pid >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

	
	


}

function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("snmpd"));
}

function PID_PATH(){
	return "/var/run/snmpd.pid";
}


function build(){
	if(is_file("/etc/snmp/snmpd.conf")){return;}
	$f[]="agentAddress  udp:127.0.0.1:161";
	$f[]="view   systemonly  included   .1.3.6.1.2.1.1";
	$f[]="view   systemonly  included   .1.3.6.1.2.1.25.1";
	$f[]="rouser   authOnlyUser";
	$f[]="sysServices    72";
	$f[]="proc  mountd";
	$f[]="proc  ntalkd    4";
	$f[]="proc  sendmail 10 1";
	$f[]="disk       /     10000";
	$f[]="disk       /var  5%";
	$f[]="includeAllDisks  10%";
	$f[]="load   12 10 5";
	$f[]="trapsink     localhost public";
	$f[]="iquerySecName   internalUser       ";
	$f[]="rouser          internalUser";
	$f[]="defaultMonitors          yes";
	$f[]="linkUpDownNotifications  yes";
	$f[]="master          agentx";	
	@mkdir("/etc/snmp",0755,true);
	@file_put_contents("/etc/snmp/snmpd.conf", @implode("\n", $f));
	
	
}

?>