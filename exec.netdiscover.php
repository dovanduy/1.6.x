<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="ARP reconnaissance service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
include_once(dirname(__FILE__).'/ressources/class.computers.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--reload-avbase"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--stats"){$GLOBALS["OUTPUT"]=true;stats();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}


function stats() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "$pidTime\n";}
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$TimeExec=$unix->file_time_min($pidTime);
	if($TimeExec<15){return;}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	stop(true);
	build();
	@unlink("/etc/artica-postfix/discover.txt");
	sleep(1);
	start(true);

}

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
	
	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
		
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running, start it\n";}
		start();
		return;
	}
	
	$timepid=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} TTL of {$timepid}Mn\n";}
	stop(true);
	start(true);

}



function netdiscover_version(){
	
	if(isset($GLOBALS["netdiscover_version"])){return $GLOBALS["netdiscover_version"];}
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/usr/share/artica-postfix/bin/netdiscover";
	exec("$Masterbin -h 2>&1",$results);
	while (list ($none, $line) = each ($results)){
		if(preg_match("#Netdiscover\s+(.+?)\s+#", $line,$re)){
			$GLOBALS["netdiscover_version"]= $re[1];
			break;
		}
		
	}
	
	return $GLOBALS["netdiscover_version"];
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/usr/share/artica-postfix/bin/netdiscover";
	
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
	
	
	$DisableNetDiscover=intval($sock->GET_INFO("DisableNetDiscover"));	
	$DisableNetDiscover=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/NetDiscoverSaved")){
		$DisableNetDiscover=1;
	}

	$pid=PID_NUM();
	$DisableNetDiscover=intval($sock->GET_INFO("DisableNetDiscover"));
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if($DisableNetDiscover==1){stop();}
		
		return;
	}
	
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$DisableNetDiscover=1;}

	if($DisableNetDiscover==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see DisableNetDiscover)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	

	build(true);
	@chmod("$Masterbin",0755);
	$version=netdiscover_version();
	
	$f[]=$nohup;
	$f[]=$Masterbin;
	$f[]="-p -P >/etc/artica-postfix/discover.txt  2>&1 &";
	
	$cmd=@implode(" ", $f);
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
	if(!is_file("/etc/artica-postfix/discover.txt")){
		if($GLOBALS["VERBOSE"]){echo "/etc/artica-postfix/discover.txt no such file\n";}
		return;}
	
	if($GLOBALS["VERBOSE"]){echo "Open /etc/artica-postfix/discover_cache.db\n";}
	$ARRAY_CACHE=unserialize(@file_get_contents("/etc/artica-postfix/discover_cache.db"));
	
	if($GLOBALS["VERBOSE"]){echo "Open /etc/artica-postfix/discover.txt\n";}
	$results=explode("\n",@file_get_contents("/etc/artica-postfix/discover.txt"));
	
	while (list ($num, $line) = each ($results)){
		
		$line=trim($line);
		if($line==null){continue;}
		$hostname=null;
		if(!preg_match("#([0-9\.]+)\s+(.+?)\s+[0-9]+\s+[0-9]+\s+(.+?)$#", $line,$re)){continue;}
		$ipaddr=$re[1];
		$MAC=$re[2];
		$text=$re[3];
		$timeEx=0;
		
		$CacheMin=$ARRAY_CACHE[$MAC];
		if($CacheMin>0){
			$timeEx=computer_time_min($CacheMin);
			if($timeEx<60){
				if($GLOBALS["VERBOSE"]){echo "$MAC = {$timeEx}Mn\n";}
				continue;}
		}
		
		if($GLOBALS["VERBOSE"]){echo "$ipaddr\t$MAC\t$text\t[$CacheMin]={$timeEx}Mn\n";}
		
		$computer=new computers();
		$cpid=$computer->ComputerIDFromMAC($MAC);
		if($cpid<>null){
			$hostname=$cpid;
			$hostname=str_replace("$", "", $hostname);
			
			if(preg_match("#[0-9\.]+#", $hostname)){
				$cpidR=gethostbyaddr($ipaddr);
				if($cpidR<>$ipaddr){$hostname=$cpidR;}
			}
		
		}
		if($hostname==null){$hostname=gethostbyaddr($ipaddr);}
		
		
		$computer=new computers($cpid);
		
		if($computer->ComputerMachineType==null){$computer->ComputerMachineType=$text;}
		$computer->ComputerMacAddress=$MAC;
		$computer->ComputerIP=$ipaddr;
		$computer->ComputerRealName=$hostname;
		if($computer->Add()){
			$ARRAY_CACHE[$MAC]=time();
		}else{
			if($GLOBALS["VERBOSE"]){echo "$MAC - $hostname Failed\n";}
		}
		
	}
	@file_put_contents("/etc/artica-postfix/discover_cache.db", serialize($ARRAY_CACHE));
}

function computer_time_min($time){
	$data2 = time();
	$difference = ($data2 - $time);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
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
	$unix=new unix();
	$Masterbin="/usr/share/artica-postfix/bin/netdiscover";
	return $unix->PIDOF($Masterbin);
	
}
?>