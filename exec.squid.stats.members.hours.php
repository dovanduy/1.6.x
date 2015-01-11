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
if($argv[1]=="--repair"){members_repair($argv[2]);die();}


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
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		events_tail("Processing $tabledata $nexttable failed, already pid $pid since {$timepid}mn exists...");
		die();
	}
	
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());

	$q=new mysql_squid_builder();

	events_tail("members_hours_perfom($tabledata,$nexttable...)");
	members_hours_perfom($tabledata,$nexttable,true,true);
	
}


function members_repair(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	
	$C=0;
	$currentDay=date("Ymd");
	$LIST_TABLES_HOURS=$q->LIST_TABLES_HOURS();
	while (list ($tablename, $value) = each ($LIST_TABLES_HOURS) ){
		$xtime=$q->TIME_FROM_HOUR_TABLE($tablename);
		if(date("Ymd",$xtime)==$currentDay){continue;}
		$member_table=date("Ymd",$xtime)."_members";
		$users_table=date("Ymd",$xtime)."_users";
		$source_table="dansguardian_events_".date("Ymd",$xtime);
		
		if(!$q->TABLE_EXISTS($member_table)){
			if($GLOBALS["VERBOSE"]){echo "$source_table -> $member_table -> BUILD\n";}
			stats_admin_events(1,"Repair: Members $source_table -> $member_table",null,__FILE__,__LINE__);
			if(members_hours_perfom($source_table,$member_table)){ 
				users_day_perfom($member_table,$users_table);
				$C++; }
			continue;
		}
		
		if($q->COUNT_ROWS($tablename)>0){
			if($q->COUNT_ROWS($member_table)==0){
				
				if(members_hours_perfom($source_table,$member_table)){ 
					stats_admin_events(2,"Repair: Members Success from \"$source_table\" to \"$member_table\"",null,__FILE__,__LINE__);
					users_day_perfom($member_table,$users_table);
					$C++; }
				continue;
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "$source_table -> $member_table OK\n";}
	
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	if($C>0){shell_exec("$php /usr/share/artica-postfix/exec.squid.stats.totals.php --repair-members --byschedule");}
	
	
}



function members_hours_perfom($tabledata,$nexttable,$nopid=false,$truncate=false){
	$t=time();
	if($tabledata==null){
		stats_admin_events(1,"$tabledata: Processing alert (no tabledata)",null,__FILE__,__LINE__);
		return;
	}
	if($nexttable==null){
		stats_admin_events(1,"$tabledata: Processing alert (no nexttable)",null,__FILE__,__LINE__);
		return;
	}

	if($tabledata=="19700101_hour"){
		stats_admin_events(1,"$tabledata: Processing alert (19700101_hour is too old)",null,__FILE__,__LINE__);
		return;
	}
	
	
	events_tail("Processing $tabledata -> $nexttable");
	$q=new mysql_squid_builder();
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=new unix();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5("$tabledata$nexttable").".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			
				events_tail(__LINE__.":Processing $tabledata $nexttable failed, already pid $pid since {$timepid}mn exists...");
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
		stats_admin_events(0,"Processing failed with $tabledata",$q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	$num_rows=mysql_num_rows($results);
	
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
	
	
	
	stats_admin_events(2,"$tabledata -> $nexttable took:" .$unix->distanceOfTimeInWords($t,time()) ,null,__FILE__,__LINE__);
	echo "SUCCESS\n";
	return true;
}

function users_day_perfom($tabledata,$nexttable){
	
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS($nexttable)){
		$q->QUERY_SQL("DROP TABLE $nexttable");
	}
	$f=array();
	if(!$q->CreateUsersDayTable($nexttable)){return false;}
		
	$sql="SELECT SUM(size) as size, SUM(hits) as hits,client,hostname,uid,MAC FROM $tabledata GROUP BY client,hostname,uid,MAC";
	$prefix="INSERT IGNORE INTO $nexttable (zMD5,client,hostname,MAC,size,hits,uid) VALUES";
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		stats_admin_events(0,"Processing failed with $tabledata",$q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$md5=md5(serialize($ligne));
		$client=mysql_escape_string2(trim(strtolower($ligne["client"])));
		$uid=mysql_escape_string2(trim(strtolower($ligne["uid"])));	
		$hostname=mysql_escape_string2(trim(strtolower($ligne["hostname"])));
		$MAC=mysql_escape_string2(trim(strtolower($ligne["MAC"])));
		$f[]="('$md5','$client','$hostname','$MAC','{$ligne["size"]}','{$ligne["hits"]}','$uid')";
		
		if(count($f)>500){
			$q->QUERY_SQL("$prefix" .@implode(",", $f));
			events_tail("Processing ". count($f)." rows");
			if(!$q->ok){events_tail("Failed to process query to $nexttable {$q->mysql_error}");return;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$q->ok){events_tail("Failed to process query to $nexttable {$q->mysql_error}");return;}
		$f=array();
	}	
	
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


