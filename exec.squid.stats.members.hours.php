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

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

if($argv[1]=="--bytime"){members_hours_perfom_bytime($argv[2]);die();}

members_hours_perfom($argv[1],$argv[2],$nopid=false);

function members_hours_perfom_bytime($xtime){
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=new unix();
	if(!is_numeric($xtime)){
		events_tail("alert, no time set");
		return;
	}
	
	$tabledata="dansguardian_events_".date('Ymd',$xtime);
	$nexttable=date("Ymd",$xtime)."_members";
	

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5("$tabledata$nexttable").".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid)){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		events_tail("Processing $tabledata $nexttable failed, already pid $oldpid since {$timepid}mn exists...");
		die();
	}
	
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());

	$q=new mysql_squid_builder();

	events_tail("members_hours_perfom($tabledata,$nexttable...)");
	members_hours_perfom($tabledata,$nexttable,true,true);
	
}



function members_hours_perfom($tabledata,$nexttable,$nopid=false,$truncate=false){
	if($tabledata==null){
		events_tail("Processing alert (no tabledata)");
		return;
	}
	if($nexttable==null){
		events_tail("Processing alert (no nexttable)");
		return;
	}

	if($tabledata=="19700101_hour"){
		events_tail("Processing alert (19700101_hour is too old)");
		return;
	}
	
	
	events_tail("Processing $tabledata -> $nexttable");
	$q=new mysql_squid_builder();
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=new unix();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5("$tabledata$nexttable").".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid)){
			$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
			
				events_tail(__LINE__.":Processing $tabledata $nexttable failed, already pid $oldpid since {$timepid}mn exists...");
				die();
			
		}
		
		$timepid=$unix->file_time_min($pidfile);
		if($timepid<60){
			if(!$GLOBALS["VERBOSE"]){
				if($q->COUNT_ROWS($nexttable)>0){
					events_tail(__LINE__.":Processing $tabledata $nexttable failed, action already exectued since {$timepid}Mn (waiting 60mn)");
					return;
				}
			}
		}
		@unlink($pidfile);
		@file_put_contents($pidfile, getmypid());
	}
	
	
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
	if($q->COUNT_ROWS($nexttable)>0){
		$sql="SELECT `hour` FROM $nexttable ORDER BY `hour` DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$starthour=$q->ILYA5HOURS();
		events_tail("processing  $tabledata Last hour >{$starthour}h");
		$filter_hour_2=" AND HOUR>$starthour";
	}

	$countRowOfTableData=$q->COUNT_ROWS($tabledata);
	events_tail("processing $tabledata $countRowOfTableData rows...");
	
	

	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS `HOUR` , 
	CLIENT, uid,MAC,hostname,account
	FROM $tabledata
	GROUP BY cached, HOUR( zDate ) , CLIENT, uid,MAC,hostname,account
	HAVING QuerySize>0  $filter_hour_1$filter_hour_2";
	
	events_tail("Today table is `$todaytable`");
	events_tail("Processing $tabledata $sql");
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		events_tail("Processing failed from $tabledata $q->mysql_error");
		return;
	}
	
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
	
	if($truncate){
		if($q->TABLE_EXISTS($nexttable)){
			events_tail("Processing empty content of $nexttable");
			$q->QUERY_SQL("TRUNCATE TABLE $nexttable");
			if(!$q->ok){events_tail($q->mysql_error);return;}
		}
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
			events_tail("Processing ".count($f). ' row(s)');
			if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
	}
	events_tail("Processing success");
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
	if($GLOBALS["VERBOSE"]){echo "$text ($sourceline)\n";}
	writelogs_squid($text,"members_hours_perfom",__FILE__,$sourceline,"stats",true);
	
}


