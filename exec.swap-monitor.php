<?php

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	//$GLOBALS["DEBUG_MEM"]=true;
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["FORCE"]=true;
}

if($argv[1]=='--free'){FreeSync();}
if($argv[1]=='--freemem'){FreeMem();}
if($argv[1]=='--watchdog'){Watch();}




include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!Build_pid_func(__FILE__,"MAIN")){
	writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
	die();
}

include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
$os=new os_system();
$mem=$os->memory();


$swap_percent=$mem["swap"]["percent"];
$swap_used=$mem["swap"]["used"];
$ram_free=$mem["ram"]["free"];
$ram_total=$mem["ram"]["total"];
$operation_disponible=$ram_free-$swap_used;


$max=str_replace("&nbsp;"," ",FormatBytes(round($ram_total/2)));

$swap_used=$mem["swap"]["used"];



$swap_used_mo=str_replace("&nbsp;"," ",FormatBytes($swap_used));
$ram_free_mo=FormatBytes($ram_free);
$log="swap used: $swap_percent% ({$swap_used_mo}) , Max $max ; free memory=$ram_free_mo, cache fore back=$operation_disponible";
echo $log."\n";


print_r($mem);

function events($text){
	$d=new debuglogs();
	$logFile="/var/log/artica-postfix/artica-swap-monitor.debug";
	$d->events(basename(__FILE__)." $text",$logFile);
}


function FreeMem($aspid=false,$SwapOffOn=array()){
	$unix=new unix();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
	}
	if(count($SwapOffOn)==0){
		$sock=new sockets();
		$SwapOffOn=unserialize(base64_decode($sock->GET_INFO("SwapOffOn")));
		if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
		if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
		if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	}
	
	$text[]="Configuration was:";
	$text[]="--------------------------------------";
	$text[]="Free memory when Swap exceed {$SwapOffOn["AutoMemPerc"]}%";
	$text[]="Watchdog scanning interval: each {$SwapOffOn["AutoMemInterval"]}mn";
	if(isset($SwapOffOn["CURRENT"])){$text[]=$SwapOffOn["CURRENT"];}
	$text[]=$unix->ps_mem_report();
	
	$TOTAL_MEMORY_MB_FREE=$unix->TOTAL_MEMORY_MB_FREE();
	$text[]="{$TOTAL_MEMORY_MB_FREE}MB before operation";
	$sync=$unix->find_program("sync");
	$sysctl=$unix->find_program("sysctl");
	$squid=$unix->LOCATE_SQUID_BIN();
	shell_exec($sync);
	shell_exec("$sysctl -w vm.drop_caches=3");
	shell_exec($sync);
	shell_exec("/etc/init.d/apache2 restart");
	if(is_file("/etc/init.d/ssh")){
		shell_exec("/etc/init.d/ssh restart");
	}
	if($unix->is_socket("/var/run/mysqld/mysqld.sock")){
		$q=new mysql();
		$q->EXECUTE_SQL("RESET QUERY CACHE;");
	}
	
	if($unix->is_socket("/var/run/mysqld/squid-db.sock")){
		$q=new mysql_squid_builder();
		$q->EXECUTE_SQL("RESET QUERY CACHE;");
	}
	
	$TOTAL_MEMORY_MB_FREE2=$unix->TOTAL_MEMORY_MB_FREE();
	$text[]="{$TOTAL_MEMORY_MB_FREE2}MB After operation";
	$TOTAL_MEMORY_MB=$TOTAL_MEMORY_MB_FREE2-$TOTAL_MEMORY_MB_FREE;
	$text[]="{$TOTAL_MEMORY_MB}MB restored";
	$FINAL_TEXT=@implode("\n", $text);
	
	system_admin_events("Free memory operation has been executed - {$TOTAL_MEMORY_MB}MB restored\n$FINAL_TEXT",__FUNCTION__,__FILE__,__LINE__);
	if(is_file($squid)){
		squid_admin_mysql(1, "Swap exceed rule: Free memory operation has been executed - {$TOTAL_MEMORY_MB}MB restored", $FINAL_TEXT,__FILE__,__LINE__);
	}	
}


function FreeSync(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	FreeMem(true);
	shell_exec("swapoff -a && swapon -a");
	
	
}

function Watch(){
	$unix=new unix();
	// 
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "Time: $pidTime\n";}
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}	
	$sock=new sockets();
	$SwapOffOn=unserialize(base64_decode($sock->GET_INFO("SwapOffOn")));
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	if($SwapOffOn["AutoMemWatchdog"]==0){return;}
	
	if(!$GLOBALS["VERBOSE"]){
		$timefile=$unix->file_time_min($pidTime);
		if($timefile<$SwapOffOn["AutoMemInterval"]){return;}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$TOTAL_MEMORY_MB_FREE=$unix->TOTAL_MEMORY_MB_FREE();
	$TOTAL_MEM_POURCENT_USED=$unix->TOTAL_MEM_POURCENT_USED();
	$TOTAL_MEMORY=$unix->TOTAL_MEMORY_MB();
	
	$SwapOffOn["CURRENT"]="Before operation: Server memory {$TOTAL_MEMORY}MB {$TOTAL_MEMORY_MB_FREE}Mb Free used {$TOTAL_MEM_POURCENT_USED}%";
	if($GLOBALS["VERBOSE"]){echo "TOTAL_MEM_POURCENT_USED = $TOTAL_MEM_POURCENT_USED / {$SwapOffOn["AutoMemPerc"]}% FREE: {$TOTAL_MEMORY_MB_FREE}MB\n";}
	if($TOTAL_MEM_POURCENT_USED>$SwapOffOn["AutoMemPerc"]){FreeMem(true,$SwapOffOn);}
}
function ps_mem_report(){
	$unix=new unix();
	$python=$unix->find_program("python");
	$results[]="************* Memory summarize ************************";
	$results[]="";
	exec("$python /usr/share/artica-postfix/bin/ps_mem.py 2>&1",$results);
	return @implode("\n", $results);
	$ps=$unix->find_program("ps");
	$results[]="";
	$results[]="";
	$results[]="************* Processes ************************";
	exec("$ps auxww 2>&1",$results);
	return @implode("\n", $results);
}

?>