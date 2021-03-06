<?php
$EnableIntelCeleron=intval(file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");


if($argv[1]=="--cmdline"){cmdline();exit;}

if($GLOBALS["VERBOSE"]){echo "Starting....\n";}
start();

function start(){
	// /etc/artica-postfix/pids/exec.loadavg.php.start.time
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "$pidfileTime\n";}
	
	
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidfileTime)<59){
			return;
		}
	}
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<15){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){if($GLOBALS["VERBOSE"]){echo "Overloaded\n";}die();}
	@unlink($pidfileTime);
	@file_put_contents($pidfileTime, time());

	if($GLOBALS["VERBOSE"]){echo "cpustats\n";}
	cpustats();
}


function cpustats(){
	
	$xdata=array();
	$ydata=array();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$filecache=dirname(__FILE__)."/ressources/logs/web/cpustatsH.db";
	$filecache_load=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVGH.db";
	$filecache_mem=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVG2H.db";
	
	
	$now=InfluxQueryFromUTC(strtotime("-24 hour"));
	$influx=new influx();
	$sql="SELECT MEAN(CPU_STATS) as cpu,MEAN(LOAD_AVG) as load,MEAN(MEM_STATS) as memory FROM SYSTEM  where proxyname='$hostname' and time > {$now}s GROUP BY time(10m) ORDER BY ASC";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		$time=InfluxToTime($row->time);
		if(!is_numeric($row->cpu)){continue;}
		if(!is_numeric($row->load)){continue;}
	
	
		$min=date("l H:i",$time)."mn";
		$xdata[]=$min;
		$ydata[]=round($row->cpu,2);
		$ydataL[]=round($row->load,2);
		$ydataM[]=round(($row->memory),2);
		if($GLOBALS["VERBOSE"]){echo "$min -> $row->cpu | $row->load | $row->memory\n";}
	}
	
	
	if(count($xdata)>1){
		$ARRAY=array($xdata,$ydata);
		$ARRAYL=array($xdata,$ydataL);
		$ARRAYM=array($xdata,$ydataM);
		if($GLOBALS["VERBOSE"]){echo "-> $filecache\n";}
		@file_put_contents($filecache, serialize($ARRAY));
		@file_put_contents($filecache_load, serialize($ARRAYL));
		@file_put_contents($filecache_mem, serialize($ARRAYM));
	
		@chmod($filecache,0755);
		@chmod($filecache_load,0755);
		@chmod($filecache_mem,0755);
	}
	
	
	$xdata=array();
	$ydata=array();
	$ydataL=array();
	$ydataM=array();
	
	$filecache=dirname(__FILE__)."/ressources/logs/web/cpustats.db";
	$filecache_load=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVG.db";
	$filecache_mem=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVG2.db";
	$now=InfluxQueryFromUTC(strtotime("-168 hour"));
	$influx=new influx();
	$sql="SELECT MEAN(CPU_STATS) as cpu,MEAN(LOAD_AVG) as load,MEAN(MEM_STATS) as memory FROM SYSTEM  where proxyname='$hostname' and time > {$now}s GROUP BY time(1h) ORDER BY ASC";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		$time=InfluxToTime($row->time);
		if(!is_numeric($row->cpu)){continue;}
		if(!is_numeric($row->load)){continue;}
		
		
		$min=date("l H:00",$time);
		$xdata[]=$min;
		$ydata[]=round($row->cpu,2);
		$ydataL[]=round($row->load,2);
		$ydataM[]=round(($row->memory/1024),2);
		if($GLOBALS["VERBOSE"]){echo "$min -> $row->cpu | $row->load | $row->memory\n";}
	}
	
	
	if(count($xdata)>1){
		$ARRAY=array($xdata,$ydata);
		$ARRAYL=array($xdata,$ydataL);
		$ARRAYM=array($xdata,$ydataM);
		if($GLOBALS["VERBOSE"]){echo "-> $filecache\n";}
		@file_put_contents($filecache, serialize($ARRAY));
		@file_put_contents($filecache_load, serialize($ARRAYL));
		@file_put_contents($filecache_mem, serialize($ARRAYM));
		
		@chmod($filecache,0755);
		@chmod($filecache_load,0755);
		@chmod($filecache_mem,0755);
	}

	

	
	
}

function cmdline(){
	$GLOBALS["DEBUG"]=true;
	$q=new mysql_squid_builder();
	echo $q->MYSQL_CMDLINES."\n";
	
}
?>