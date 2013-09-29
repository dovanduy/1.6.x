<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");

start();

function start(){
	// /etc/artica-postfix/pids/exec.loadavg.php.start.time
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "$pidfileTime\n";}
	if(!$GLOBALS["VERBOSE"]){
	if($unix->file_time_min($pidfileTime)<4){return;}
	}
	
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){die();}
	
	
	
	
	@unlink($pidfileTime);
	@file_put_contents($pidfileTime, time());
	
	start1();
	start2();
	start3();
	
}



function start1(){
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate,
		MINUTE(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY `time` ,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d %H') ORDER BY `time`";
	
	$filecache=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVG.db";
	@unlink($filecache);
	
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
		return;}
	
	if(mysql_num_rows($results)<2){
		if($GLOBALS["VERBOSE"]){echo "mysql_num_rows($results) <2\n";}
		return;}
		
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["time"];
		$ydata[]=round($ligne["value"],2);
	}
	
	
	if(count($xdata)>1){
		$ARRAY=array($xdata,$ydata);
		if($GLOBALS["VERBOSE"]){echo "-> $filecache\n";}
		@file_put_contents($filecache, serialize($ARRAY));
		@chmod($filecache,0755);
	}	
	
	
}

function start2(){
	
	$sql="SELECT DATE_FORMAT( zDate, '%Y-%m-%d %H' ) AS tdate, MINUTE( zDate ) AS time,
				AVG( memory_used ) AS value
				FROM `sys_mem`
				GROUP BY `time` , tdate
				HAVING tdate = DATE_FORMAT( NOW( ) , '%Y-%m-%d %H' )
				ORDER BY `time`";
	
	$title="{memory_consumption_this_hour}";
	$timetext="{minutes}";
	
	
	$filecache=$filecache=dirname(__FILE__)."/ressources/logs/web/INTERFACE_LOAD_AVG2.db";
	@unlink($filecache);
	
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}
	
	if(mysql_num_rows($results)<2){return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$xdata[]=$ligne["time"];
			$ligne["value"]=$ligne["value"]/1024;
			$ydata[]=round($ligne["value"],2);
		}
		
	
		if(count($xdata)>1){
			$ARRAY=array($xdata,$ydata);
			@file_put_contents($filecache, serialize($ARRAY));
			@chmod($filecache,0755);
		}	
	
}

function start3(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.stats.size.hour.db";
	@unlink($cacheFile);
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){return;}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$currenttime=date("YmdH");
	$table="squidhour_$currenttime";
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS($table)){return;}
	
	$sql="SELECT SUM(QuerySize) as tsize,DATE_FORMAT(zDate,'%i') as tdate FROM $table
	group by tdate HAVING tsize>0
	ORDER BY tdate ";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error";return;}
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
	$size=round(($ligne["tsize"]/1024),2);;
			if($GLOBALS["VERBOSE"]){echo "<strong>{$ligne["tdate"]} = $size</strong><br>\n";}
			if(strlen($ligne["tdate"])==1){$ligne["tdate"]="0".$ligne["tdate"];}
			$xdata[]="\"{$ligne["tdate"]}mn\"";
		$ydata[]=$size;
	}

	
	if(count($xdata)<2){return;}
	$array=array($xdata,$ydata);
	
	
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0755);
	
	
}

