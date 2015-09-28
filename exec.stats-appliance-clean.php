<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.squidlogs.parser.inc");


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

start_parse();

function start_parse(){
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/exec.stats-appliance-clean.php.start_parse.time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}

	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$q=new mysql_squid_builder();
	
	
	
	$time=date("YmdH");
	$currentTable="{$time}_statsuapp";
	$LIST_TABLES_STATS_UAPP=$q->LIST_TABLES_STATS_UAPP();
	
	
	while (list ($tablename, $arrayF) = each ($LIST_TABLES_STATS_UAPP) ){
		if($currentTable==$tablename){continue;}
		if(parseToMonth($tablename)){
			$q->QUERY_SQL("DROP TABLE `$tablename`");
		}
	}
	
	

}

function parseToMonth($tablename){
	$time=date("Ym");
	$q=new mysql_squid_builder();
	$q->check_stats_appliance_monthly_uploaded();
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as zDate,SUM(filesize) as size, COUNT(filename) as hits, 
	uuid FROM $tablename GROUP BY uuid,DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00')";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	
	$f=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["zDate"]}','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["uuid"]}')";
		
	}
	
	if(count($f)==0){return true;}
	$sql="INSERT IGNORE INTO `{$time}_Mstatsuapp` (zDate,size,hits,uuid) VALUES ".@implode(",", $f);
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	return true;
	
}

