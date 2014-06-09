<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
$GLOBALS["AS_ROOT"]=true;

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

if($argv[1]=="--tables-day"){repair_table_days();die();}
if($argv[1]=="--tables-dayh"){repair_table_days_hours();die();}
if($argv[1]=="--tables-visited-sites"){repair_visited_sites();die();}


if($argv[1]=="--repair-table-hour"){repair_table_hour($argv[2]);die();}




function repair_table_days(){
	
	$q=new mysql_squid_builder();
	echo "Delete table tables_day\n";
	$q->DELETE_TABLE("tables_day");
	echo "Check databases...\n";
	$q->CheckTables();
	
	$array=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename,$none) = each ($array) ){
		echo $tablename."\n";
		$time=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
		$date=date("Y-m-d",$time);
		$q->QUERY_SQL("INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ('$tablename','$date')");
	}
	
	
	
}
function repair_table_days_hours(){
	$q=new mysql_squid_builder();
	$array=$q->LIST_TABLES_HOURS();
	while (list ($tablename,$none) = each ($array) ){
		
		$time=$q->TIME_FROM_HOUR_TABLE($tablename);
		$date=date("Y-m-d",$time);
		$tablename="dansguardian_events_".date("Ymd",$time);
		echo "$tablename -> $date\n";
		$q->QUERY_SQL("INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ('$tablename','$date')");
	}	
	
}



function repair_visited_sites(){

	$q=new mysql_squid_builder();
	echo "Delete table visited_sites\n";
	$q->DELETE_TABLE("visited_sites");
	$q->DELETE_TABLE("phraselists_weigthed");
	$q->DELETE_TABLE("squidtpls");
	$q->DELETE_TABLE("webfilters_databases_disk");
	$q->DELETE_TABLE("squidservers");
	$q->DELETE_TABLE("webfilters_schedules");
	echo "Check databases...\n";
	$q->CheckTables();


}

function repair_table_hour($xtime){
	if(!is_numeric($xtime)){
		writelogs_repair($xtime,100,"No timestamp set");
		return;
	}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/repair_table_hour_$xtime.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->dansguardian_events_to_table_hour($xtime);
}



function resetlogs($xtime){
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$xtime";
	@file_put_contents($filelogs, serialize(array()));
}

function writelogs_repair($xtime,$progress,$text){
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$xtime";
	$array=unserialize(@file_get_contents($filelogs));
	$array["PROGRESS"]=$progress;
	$array["TEXT"][]="$date [$pid]: $text";
	@file_put_contents($filelogs, serialize($array));
	@chmod($filelogs, 0775);
	
	
}
?>