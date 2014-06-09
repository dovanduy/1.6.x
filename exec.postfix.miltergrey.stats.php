<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["ULIMITED"]=false;
$GLOBALS["VERBOSE2"]=false;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.ini-frame.inc");
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--unlimit#",implode(" ",$argv))){$GLOBALS["ULIMITED"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--verb2#",implode(" ",$argv))){$GLOBALS["VERBOSE2"]=true;}

$sock=new sockets();
$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
if($DisableMessaging==1){die();}

if($argv[1]=="--count"){count_tables_hours();die();}




MiltergreyList_days();
function MiltergreyList_days(){
	
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Already process PID: $pid running since $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($timeFile)<60){die();}
	}
	
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}	
	}
	
	
	$q=new mysql_postfix_builder();
	if($GLOBALS["VERBOSE"]){
		echo "Scanning tables...\n";
	}
	$tables=$q->LIST_MILTERGREYLIST_HOUR_TABLES();
	$currentHour=date("Y-m-d h");
	$tt=0;
	if(is_array($tables)){
		while (list ($tablesource, $time) = each ($tables) ){
			$tt++;
			if(date("Y",$time)=="1970"){
				$q->QUERY_SQL("DROP TABLE $tablesource");
				continue;
			}
			
			if( date("Y-m-d H",$time)== $currentHour ){if($GLOBALS["VERBOSE"]){echo "Skipping $currentHour\n";}continue;}
			if($GLOBALS["VERBOSE"]){echo "Processing $tablesource: ".date("Y-m-d H",$time)."\n";}
			
			
			if(MiltergreyList_scan($tablesource,$time)){
				if($GLOBALS["VERBOSE"]){echo "DUMP_TABLE $tablesource: ".date("Y-m-d H",$time)."\n";}
				if($q->DUMP_TABLE($tablesource)){
					$q->QUERY_SQL("DROP TABLE $tablesource");
				}
			}
			
			if(system_is_overloaded(basename(__FILE__))){
				system_admin_events("Overloaded system, aborting task after $tt processed tables ", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
				return;
			}	
			
		}
	
	}
	
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			system_admin_events("Overloaded system, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}	

	$tables=$q->LIST_MILTERGREYLIST_DAY_TABLES();
	if(is_array($tables)){
		while (list ($tablesource, $time) = each ($tables) ){
			if( date("Y-m-d",$time)== date("Y-m-d") ){
				if($GLOBALS["VERBOSE"]){echo "Skipping $currentHour\n";}continue;}
				if($GLOBALS["VERBOSE"]){echo "Processing $tablesource: ".date("Y-m-d",$time)."\n";}
				MiltergreyList_month($tablesource,$time);
		
		}
	}	
	
	

}

function MiltergreyList_scan($tablesource,$time){
	$q=new mysql_postfix_builder();
	if(date("Y-m-d h")==date("Y-m-d h",$time)){return false;}
	
	
	$NextTable="mgreyd_".date("Ymd",$time);
	if($GLOBALS["VERBOSE"]){echo "Processing $tablesource -> $NextTable\n";}
	if(!$q->milter_BuildDayTable($NextTable)){return false;}
	$database=$q->database;

	
	$prefix="INSERT IGNORE INTO $NextTable 
	(zmd5,hits,zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,`failed`) VALUES ";
	
	$sql="SELECT COUNT(zmd5) as hits,zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed FROM $tablesource 
	GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed";
	$results = $q->QUERY_SQL($sql,$database);
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$zhour=$ligne["zhour"];
		$hits=$ligne["hits"];
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$instancename=mysql_escape_string2($ligne["instancename"]);
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		
		$domainfrom=mysql_escape_string2($ligne["domainfrom"]);
		$domainto=mysql_escape_string2($ligne["domainto"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$senderhost=mysql_escape_string2($ligne["senderhost"]);
		$failed=$ligne["failed"];
		$f[]="('$zmd5','$hits','$zhour','$mailfrom','$instancename','$mailto','$domainfrom','$domainto','$senderhost','$failed')";
		
		if(count($f)>500){
			if($GLOBALS["VERBOSE"]){echo $NextTable." "."500\n";}
			$q->QUERY_SQL($prefix.@implode(",", $f),$database);
			if(!$q->ok){return false;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo $NextTable." ".count($f)."\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f),$database);
		if(!$q->ok){return false;}
		$f=array();		
		
	}
	return true;
}

function count_tables_hours(){
	$dir="/var/lib/mysql/postfixlog";
	$unix=new unix();
	return $unix->COUNT_FILES($dir);
	
	
}

function MiltergreyList_month($tablesource,$time){
	$q=new mysql_postfix_builder();
	if(date("Y-m-d")==date("Y-m-d",$time)){return false;}


	$NextTable="mgreym_".date("Ym",$time);
	if($GLOBALS["VERBOSE"]){echo "Processing $tablesource -> $NextTable\n";}
	if(!$q->milter_BuildMonthTable($NextTable)){return false;}
	$database=$q->database;


	$prefix="INSERT IGNORE INTO $NextTable
	(zmd5,hits,zday,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,`failed`) VALUES ";

	$sql="SELECT SUM(hits) as hits,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed FROM $tablesource
	GROUP BY mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed";
	$results = $q->QUERY_SQL($sql,$database);
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$zday=date("Y-m-d",$time);
		$zmd5=md5(serialize($ligne).$zday);
		$hits=$ligne["hits"];
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$instancename=mysql_escape_string2($ligne["instancename"]);
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$domainfrom=mysql_escape_string2($ligne["domainfrom"]);
		$domainto=mysql_escape_string2($ligne["domainto"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$senderhost=mysql_escape_string2($ligne["senderhost"]);
		$failed=$ligne["failed"];
		$f[]="('$zmd5','$hits','$zday','$mailfrom','$instancename','$mailto','$domainfrom','$domainto','$senderhost','$failed')";

		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f),$database);
			if(!$q->ok){return false;}
			$f=array();
		}

	}

	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),$database);
		if(!$q->ok){return false;}
		$f=array();
	
	}
	return true;
}