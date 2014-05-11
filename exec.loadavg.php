<?php
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
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");

if($argv[1]=="--tables"){showtables();exit;}
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
		if($unix->file_time_min($pidfileTime)<4){
			return;
		}
	}
	
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		if($GLOBALS["VERBOSE"]){echo "$oldpid already executed since {$timepid}Mn\n";}
		if($timepid<15){return;}
		$kill=$unix->find_program("kill");
		shell_exec("$kill -9 $oldpid");
	}
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){
		if($GLOBALS["VERBOSE"]){echo "Overloaded\n";}
		die();}
	
	
	
	
	@unlink($pidfileTime);
	@file_put_contents($pidfileTime, time());
	
	if($GLOBALS["VERBOSE"]){echo " ****  TIME :  ". date("Y-m-d H:i:s")." **** \n";}
	if($GLOBALS["VERBOSE"]){echo "start1\n";}
	start1();
	if($GLOBALS["VERBOSE"]){echo "start2\n";}
	start2();
	if($GLOBALS["VERBOSE"]){echo "start3\n";}
	start3();
	if($GLOBALS["VERBOSE"]){echo "start4\n";}
	start4();
	if($GLOBALS["VERBOSE"]){echo "start5\n";}
	start5();
	if($GLOBALS["VERBOSE"]){echo "start6\n";}
	start6();
	if($GLOBALS["VERBOSE"]){echo "cpustats\n";}
	cpustats();
}


function cpustats(){
	
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$filecache=dirname(__FILE__)."/ressources/logs/web/cpustats.db";
	$q=new mysql();
	if(!$q->TABLE_EXISTS("cpustats", "artica_events")){return;}
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate,hostname,
		MINUTE(zDate) as `time`,AVG(cpu) as value FROM `cpustats` GROUP BY `time` ,tdate,hostname
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d %H') AND `hostname`='$hostname' ORDER BY `time`";	
	
	
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		if(strpos($q->mysql_error, "is marked as crashed")>0){ $q->QUERY_SQL("DROP TABLE `cpustats`","artica_events");return;}
		
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";} return;}
	
	if(mysql_num_rows($results)<2){ if($GLOBALS["VERBOSE"]){echo "mysql_num_rows($results) <2\n";} return;}
	
	
	
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

function cmdline(){
	$GLOBALS["DEBUG"]=true;
	$q=new mysql_squid_builder();
	echo $q->MYSQL_CMDLINES."\n";
	
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
	
	$size=round(($ligne["tsize"]/1024),2);
			if($GLOBALS["VERBOSE"]){echo "<strong>Size: {$ligne["tdate"]} = {$ligne["tsize"]} = $size KB</strong><br>\n";}
			if(strlen($ligne["tdate"])==1){$ligne["tdate"]="0".$ligne["tdate"];}
			$xdata[]="\"{$ligne["tdate"]}mn\"";
		$ydata[]=$size;
	}

	
	if(count($xdata)<2){return;}
	$array=array($xdata,$ydata);
	
	
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0755);
	
	
}

function showtables(){
	
	$q=new mysql_squid_builder();
	//$results=$q->QUERY_SQL("SHOW TABLES");
	//while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	//	echo $ligne["Tables_in_squidlogs"]."\n";
	//}
	
	
	$LIST_TABLES_SIZEHOURS=$q->LIST_TABLES_SIZEHOURS();
	while (list ($tablename,$none) = each ($LIST_TABLES_SIZEHOURS) ){
		echo "Size Hour: $tablename\n";
		
	}
	
}


function start4(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG3.db";
	@unlink($cacheFile);
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){
		if($GLOBALS["VERBOSE"]){echo "start4(): squid no such binary\n";}
		
		return;}
	
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo " ****  TIME :  ". date("Y-m-d H:i:s")." **** \n";}
	$TableSizeHours="sizehour_".date("YmdH");
	
	if($GLOBALS["VERBOSE"]){echo " ****  TABLE $TableSizeHours **** \n";}
	
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS($TableSizeHours)){
		if($GLOBALS["VERBOSE"]){echo "start4(): $TableSizeHours no such table\n";}
		return;}
	
	$sql="SELECT SUM(`size`) as tsize,DATE_FORMAT(zDate,'%i') as tdate FROM $TableSizeHours group by tdate HAVING tsize>0 ORDER BY tdate ";
	
	
	if($GLOBALS["VERBOSE"]){echo " ****\n$sql\n**** \n";}
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error";return;}
	
	if($GLOBALS["VERBOSE"]){echo " ****  TABLE $TableSizeHours mysql_num_rows = ". mysql_num_rows($results)."**** \n";}
	
	if(mysql_num_rows($results)<2){return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$size=$ligne["tsize"]/1024;
		$size=round($size/1024);
		if($GLOBALS["VERBOSE"]){echo "<strong>{$ligne["tdate"]} = $size</strong><br>\n";}
		if(strlen($ligne["tdate"])==1){$ligne["tdate"]="0".$ligne["tdate"];}
		$xdata[]="\"{$ligne["tdate"]}\"";
		$ydata[]=$size;
	}
	
	
	if(count($xdata)<2){
		if($GLOBALS["VERBOSE"]){echo "start4(): ".count($xdata)." < 2\n";}
		return;}
	$array=array($xdata,$ydata);
	
	
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0755);	
	
}
function start5(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG5.db";
	@unlink($cacheFile);
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){if($GLOBALS["VERBOSE"]){echo "start4(): squid no such binary\n";}return;}

	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo " ****  TIME :  ". date("Y-m-d H:i:s")." **** \n";}
	$TableSizeMonth=$table="quotamonth_".date("Ym");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($TableSizeMonth)){
		if($GLOBALS["VERBOSE"]){echo "start4(): $TableSizeMonth no such table\n";}
		return;
	}

	$sql="SELECT SUM(`size`) as tsize,`day` as tdate FROM $TableSizeMonth
	group by tdate HAVING tsize>0
	ORDER BY tdate ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error";return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["tsize"]/1024;
		$size=round($size/1024);
		if($GLOBALS["VERBOSE"]){echo "<strong>{$ligne["tdate"]} = $size</strong><br>\n";}
		if(strlen($ligne["tdate"])==1){$ligne["tdate"]="0".$ligne["tdate"];}
		$xdata[]="\"{$ligne["tdate"]}\"";
		$ydata[]=$size;
	}


	if(count($xdata)<2){
	if($GLOBALS["VERBOSE"]){echo "start4(): ".count($xdata)." < 2\n";}
	return;}
	$array=array($xdata,$ydata);
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0755);

}

function start6(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_WEBFILTER_BLOCKED.db";
	@unlink($cacheFile);
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){if($GLOBALS["VERBOSE"]){echo "start6: squid no such binary\n";}return;}
	$sock=new sockets();
	if($sock->EnableUfdbGuard()==0){return;}
	$zday=date('Ymd');
	$table=$zday."_blocked";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){
		$hier=strtotime($q->HIER()." 00:00:00");
		$zday=date('Ymd',$hier);
		$table=$zday."_blocked";
	}
	if(!$q->TABLE_EXISTS($table)){return;}
	
	$sql="SELECT COUNT(*) as hits,HOUR(zDate) as `hour` FROM $table GROUP BY `hour` ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$hits=$ligne["hits"];
		$xdata[]=$ligne["hour"];
		$ydata[]=$hits;
		}
		
		
	if(count($xdata)<2){if($GLOBALS["VERBOSE"]){echo "start4(): ".count($xdata)." < 2\n";} return;}
	$array=array($xdata,$ydata);
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0755);

	
	
}
