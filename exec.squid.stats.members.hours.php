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


members_hours_perfom($argv[1],$argv[2]);

function members_hours_perfom($tabledata,$nexttable){
	events_tail("Processing $tabledata -> $nexttable");
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5("$tabledata$nexttable").".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid)){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		events_tail("Processing $tabledata $nexttable failed, already pid $oldpid since {$timepid}mn exists...");
		die();
	}
	
	$timepid=$unix->file_time_min($pidfile);
	if($timepid<60){
		events_tail("Processing $tabledata $nexttable failed, action already exectued since {$timepid}Mn (waiting 60mn)");
		return;
	}
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	
	
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	$filter_hour=null;
	$filter_hour_1=null;
	$filter_hour_2=null;
	$GLOBALS["Q"]->CreateMembersDayTable($nexttable);
	$todaytable=date('Ymd')."_members";
	$CloseTable=true;
	$output_rows=false;


	if($nexttable==$todaytable){
		$filter_hour_1="AND HOUR < HOUR( NOW())";
		$CloseTable=false;
	}

	$q=new mysql_squid_builder();
	$sql="SELECT hour FROM $nexttable ORDER BY hour DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$starthour=$q->ILYA5HOURS();
	
	
	events_tail("processing  $tabledata Last hour >{$starthour}h");
	$filter_hour_2=" AND HOUR>$starthour";


	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS HOUR , CLIENT, uid,MAC,hostname,account
	FROM $tabledata
	GROUP BY cached, HOUR( zDate ) , CLIENT, uid,MAC,hostname,account
	HAVING QuerySize>0  $filter_hour_1$filter_hour_2";


	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows=mysql_num_rows($results);
	events_tail("Processing $tabledata -> $nexttable CLOSE:$CloseTable (today is $todaytable) filter:'$filter_hour_2' $num_rows  rows in line ".__LINE__);
	if($num_rows<10){$output_rows=true;}

	if($num_rows==0){
		events_tail("$tabledata no rows...CloseTable=$CloseTable");
		if($CloseTable){
			events_tail("$tabledata -> Close table");
			$sql="UPDATE tables_day SET members=1 WHERE tablename='$tabledata'";
			$GLOBALS["Q"]->QUERY_SQL($sql);
		}
		echo "SUCCESS\n";
		return true;
	}

	$prefix="INSERT IGNORE INTO $nexttable (zMD5,client,hour,size,hits,uid,cached,MAC,hostname,account) VALUES ";

	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$client=addslashes(trim(strtolower($ligne["CLIENT"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));

		$md5=md5("{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}");
		$sql_line="('$md5','$client','{$ligne["HOUR"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','{$ligne["cached"]}','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
		$f[]=$sql_line;

		if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}

		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
	}
	echo "SUCCESS\n";
	return true;
}


function events_tail($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}
	
	writelogs_squid($text,"members_hours_perfom",__FILE__,$sourceline,"stats");
	
}


