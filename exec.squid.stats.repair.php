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
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$tableSource="dansguardian_events_".date("Ymd",$xtime);
	$dayText=date("{l} {F} d Y",$xtime);
	resetlogs($xtime);
	writelogs_repair($xtime,15,"Processing timestamp $xtime `$dayText`");
	writelogs_repair($xtime,16,"Table source `$tableSource` for $dayText");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($tableSource)){
		writelogs_repair($xtime,100,"Table source `$tableSource` does not exists");
		return;
	}
	
	$next_table=date('Ymd',$xtime)."_hour";
	writelogs_repair($xtime,20,"Destination table: $next_table");
	repair_table_hour_perfom($tableSource,$next_table,$xtime);	
	writelogs_repair($xtime,100,"Done...");
	
	
}

function repair_table_hour_perfom($tabledata,$nexttable,$xtime){
	$filter_hour=null;
	$filter_hour_1=null;
	$filter_hour_2=null;
	if(isset($GLOBALS["$tabledata$nexttable"])){if($GLOBALS["VERBOSE"]){echo "$tabledata -> $nexttable already executed, return true\n";}return true;}
	$GLOBALS["Q"]=new mysql_squid_builder();
	
	writelogs_repair($xtime,29,"Removing table `$nexttable` ".__LINE__);
	
	$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$nexttable`");
	$GLOBALS["$tabledata$nexttable"]=true;
	$GLOBALS["Q"]->CreateHourTable($nexttable);
	$todaytable=date('Ymd')."_hour";
	$CloseTable=true;
	$output_rows=false;
	$unix=new unix();

	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS HOUR , CLIENT, Country, uid, sitename,MAC,hostname,account
	FROM $tabledata GROUP BY cached, HOUR( zDate ) , CLIENT, Country, uid, sitename,MAC,hostname,account HAVING QuerySize>0";

	$timeStart=time();
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows=mysql_num_rows($results);
	$disantce=$unix->distanceOfTimeInWords($timeStart,time(),true);
	writelogs_repair($xtime,30,"Processing $tabledata -> $num_rows rows, Query took: $disantce  in line ".__LINE__);
	if($num_rows<10){$output_rows=true;}

	if($num_rows==0){
		writelogs_repair($xtime,90,"Processing $tabledata -> No row".__LINE__);
		$sql="UPDATE tables_day SET Hour=1 WHERE tablename='$tabledata'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		return true;
	}

	$prefix="INSERT IGNORE INTO $nexttable (zMD5,sitename,client,hour,remote_ip,country,size,hits,uid,category,cached,familysite,MAC,hostname,account) VALUES ";
	$prefix_visited="INSERT IGNORE INTO visited_sites (sitename,category,country,familysite) VALUES ";
	$f=array();
	$c=0;
	$TotalRows=0;
	$timeStart=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		
		$sitename=addslashes(trim(strtolower($ligne["sitename"])));
		$client=addslashes(trim(strtolower($ligne["CLIENT"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
		$Country=addslashes(trim(strtolower($ligne["Country"])));
		if(!isset($GLOBALS["MEMORYSITES"][$sitename])){
			$category=$GLOBALS["Q"]->GET_CATEGORIES($sitename);
			$GLOBALS["MEMORYSITES"][$sitename]=$category;
		}else{
			$category=$GLOBALS["MEMORYSITES"][$sitename];
		}

		$familysite=$GLOBALS["Q"]->GetFamilySites($sitename);
		$ligne["Country"]=mysql_escape_string($ligne["Country"]);
		$SQLSITESVS[]="('$sitename','$category','{$ligne["Country"]}','$familysite')";



		$md5=md5("{$ligne["sitename"]}{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["MAC"]}{$ligne["Country"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}{$ligne["cached"]}{$ligne["account"]}$category$Country");
		$sql_line="('$md5','$sitename','$client','{$ligne["HOUR"]}','$client','$Country','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','$category','{$ligne["cached"]}',
		'$familysite','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
		$f[]=$sql_line;

		if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}
		
		if($c>200){
			$TotalRows=$TotalRows+$c;
			$disantce=$unix->distanceOfTimeInWords($timeStart,time(),true);
			writelogs_repair($xtime,80,"Processing $TotalRows/$num_rows - $disantce");
			$timeStart=time();
			$c=0;
			
		}

		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Failed to process query to $nexttable {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}
		if(count($SQLSITESVS)>0){
			$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
			$SQLSITESVS=array();
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Processing ". count($f)." rows");}
		if(!$GLOBALS["Q"]->ok){if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}}

		if(count($SQLSITESVS)>0){
			if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Processing ". count($SQLSITESVS)." visited sites");}
			$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
			if(!$GLOBALS["Q"]->ok){if(!$GLOBALS["Q"]->ok){writelogs_repair($xtime,90,"Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error} in line " .	__LINE__);}}
		}
	}
	return true;
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