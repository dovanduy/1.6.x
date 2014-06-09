<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONITOR"]=false;
$GLOBALS["ADPLUS"]=null;
$GLOBALS["TITLENAME"]="UfdbGuard Web filter Client";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;}
if(preg_match("#--monitor#",implode(" ",$argv),$re)){$GLOBALS["MONITOR"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

if($GLOBALS["WATCHDOG"]){$GLOBALS["ADPLUS"]=" (By Watchdog)";}
if($GLOBALS["MONITOR"]){$GLOBALS["ADPLUS"]=" (By Monitor)";}



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}




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
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Restarting...\n";}
	if(!start(true)){return;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Chock proxy...\n";}
	shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Done\n";}
}




function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("ufdbgclient");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ufdbgclient not installed\n";}
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$sock=new sockets();
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	if($EnableUfdbGuard==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Not Enabled\n";}
		return false;
	}
	

	$pids=GetAllPids();
	if(count($pids)>0){
		while (list ($pid, $none) = each ($pids) ){$ttl=$unix->PROCESS_TTL($pid);if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, already running PID $pid since {$ttl}Mn\n";}}
		return true;
	}
	
	if(IsInSquid()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Hooked chock proxy\n";}
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
	}else{
		
		EnableClient();
	}
	
	
	for($i=1;$i<8;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Waiting $i/5\n";}
		sleep(1);
		$pids=GetAllPids();
		if(count($pids)>0){break;}
	}

	$pids=GetAllPids();
	if(count($pids)>0){
		while (list ($pid, $none) = each ($pids) ){$ttl=$unix->PROCESS_TTL($pid);$fty[]="Success PID $pid since {$ttl}Mn";if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid since {$ttl}Mn\n";}}
		
		squid_admin_mysql(1,"Succes starting Web Filtering Client service from the proxy{$GLOBALS["ADPLUS"]}",
		@implode("\n", $fty),__FILE__,__LINE__);
		return true;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Failed\n";}
		
	}


}

function GetAllPids(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$Masterbin=$unix->find_program("ufdbgclient");
	if(!is_file($Masterbin)){return;}
	
	
	exec("$pgrep -l -f ufdbgclient",$f);
	while (list ($num, $line) = each ($f)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PID:$pid\n";}
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}	
	return $pids;
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	DisableClient();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Unlinked from the proxy...\n";}
	
}



function IsInSquid(){
	
	$sock=new sockets();
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	if($EnableUfdbGuard==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Not Enabled\n";}
		return;
	}


	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}

	
	
	$Detected=false;


	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){
			if($GLOBALS["VERBOSE"]){echo "`$line` OK\n";
			return true;
			}
		}

		if($GLOBALS["VERBOSE"]){echo "`$line` no match\n";}

	}

	return false;
}
function DisableClient(){
	$unix=new unix();
	$Detected=false;
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){
			$f[$index]="#$line";
			$replaced_line="#$line";
			$Detected=true;
		}

	}
	if($Detected){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $f));
		
		squid_admin_mysql(1,"Unlink Web Filtering service from the proxy{$GLOBALS["ADPLUS"]}","Detected `$replaced_line` in squid.conf",__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
	}
}

function CheckAvailable(){
	
	$unix=new unix();
	$sock=new sockets();

	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	if($EnableUfdbGuard==0){return false;}

	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}

	
	$Detected=false;


	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, OK CLIENT IS LINKED\n";}
			if($GLOBALS["VERBOSE"]){echo "`$line` OK\n";
			$Detected=true;
			break;
			}
		}

		if($GLOBALS["VERBOSE"]){echo "`$line` no match\n";}

	}
	
	if($datas["UseRemoteUfdbguardService"]==1){
		$host=$datas["remote_server"];
		$port=$datas["remote_port"];
	}else{
		$array=GetLocalConf();
		$host=$array[1];
		$port=$array[0];	
	}
	if($host=="all"){$host="127.0.0.1";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Remote host...: $host\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Remote port.....: $port\n";}
				
	$fsock = fsockopen($host, $port, $errno, $errstr, 5);
	if ( ! $fsock ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Failed to connect to the remote Webfiltering service $host:$port\n";}
		squid_admin_mysql(0,"Fatal, failed to connect to the remote Webfiltering service{$GLOBALS["ADPLUS"]}",
		"{$datas["remote_server"]}:{$datas["remote_port"]} Error number $errno $errstr",__FILE__,__LINE__);
	

		return false;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Success to connect to the remote Webfiltering service $host:$port\n";}
	return true;

}

function EnableClient(){

	if(!CheckAvailable()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Master service failed\n";}
		
	}
	
	$Detected=false;
	$unix=new unix();
	$replaced_line=null;
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^\#url_rewrite_program.*?ufdbgclient#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Relink to proxy\n";}
			$line=str_replace("#", "", $line);
			$replaced_line=$line;
			$f[$index]=$line;
			$Detected=true;
			continue;
		}

		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){ return;}

	}


	if($Detected){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $f));
		$GLOBALS["FORCE"]=true;
		squid_admin_mysql(1,"Reconfigure Proxy service to relink Web Filtering service{$GLOBALS["ADPLUS"]}","Detected `$replaced_line` in squid.conf",__FILE__,__LINE__);
		$squid=$unix->LOCATE_SQUID_BIN();
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Relink done\n";}
		return;
	}


	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Reconfigure Proxy for linking \n";}
	if($GLOBALS["VERBOSE"]){echo "$php /usr/share/artica-postfix/exec.squid.php --build --force\n";}
	exec("$php /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &",$results);
	squid_admin_mysql(1,"Reconfigure Proxy service to relink Web Filtering service{$GLOBALS["ADPLUS"]}","Not Detected in squid.conf\nexecuted exec.squid.php --build --force\n".@implode("\n", $results),__FILE__,__LINE__);
}

function GetLocalConf(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^port\s+([0-9]+)#", $line,$re)){$port=$re[1];continue;}
		if(preg_match("#^interface\s+(.+)#", $line,$re)){$interface=trim($re[1]);continue;}
	}	
	
	return array($port,$interface);
	
}



?>