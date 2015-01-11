<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["FORCE_TIME"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--force-time#",implode(" ",$argv))){$GLOBALS["FORCE_TIME"]=true;}
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
if($argv[1]=="--coherences-tables"){repair_from_sources_tables();die();}
if($argv[1]=="--youtube"){Repair_youtube_objects();die();}
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

function repair_from_sources_tables(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "time: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
			if($pid<100){$pid=null;}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}
	}
	
	if($GLOBALS["FORCE_TIME"]){
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<240){return;}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$Prefix="/usr/share/artica-postfix";
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$EXEC_NICE=$unix->EXEC_NICE();
	
	$q=new mysql_squid_builder();
	$C=0;
	$array=$q->LIST_TABLES_dansguardian_events();
	$current="dansguardian_events_".date("Ymd");
	while (list ($tablename,$none) = each ($array) ){
		if($tablename==$current){continue;}
		$time=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
		$xtime=date("Y-m-d",$time);
		$hour_table=date("Ymd",$time)."_hour";
		$member_table=date("Ymd",$time)."_members";
		$SUM_SOURCE=$q->COUNT_ROWS($tablename);
		if($SUM_SOURCE==0){continue;}
		$SUM_DEST=$q->COUNT_ROWS($hour_table);
		$PERC=($SUM_DEST/$SUM_SOURCE)*100;
		$PERC=intval($PERC);
		echo "$xtime] $SUM_SOURCE - $SUM_DEST = {$PERC}% - $tablename\n";
		
		
		
		if($PERC<5){
			if(!$q->CreateHourTable($hour_table)){
				echo "$xtime] $tablename unable to create $hour_table\n";
				continue;
			}
			_repair_from_sources_tables($tablename,$hour_table);
			$q->QUERY_SQL("UPDATE tables_day SET `totalsize`='0',`requests`=0,`MembersCount`=0,`month_flow`=0,weekdone=0,weekbdone=0 WHERE tablename='$tablename'");
			$C++;
		}
		
		$ligne1=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as hits FROM $tablename"));
		if(!$q->ok){echo $q->mysql_error;}
		$SumDehits_src=$ligne1["hits"];
		$ligne1=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as hits FROM $member_table"));
		$SumDehits_dest=$ligne1["hits"];
		$PERC=($SumDehits_dest/$SumDehits_src)*100;
		$PERC=intval($PERC);
		echo "$xtime] $SumDehits_src - $SumDehits_dest = {$PERC}% - $member_table\n";
		
		
		if($PERC<90){
			if(!$q->CreateMembersDayTable($hour_table)){
				echo "$xtime] $tablename unable to create $hour_table\n";
				continue;
			}
			_repair_members_sources_tables($tablename,$member_table);
			$C++;
		}
		
		
	}
	
	if($C>0){
		shell_exec(trim("$EXEC_NICE $php5 $Prefix/exec.squid.stats.totals.php --repair --byschedule --schedule-id={$GLOBALS["SCHEDULE_ID"]}"));
	}
	
}
function _repair_members_sources_tables($sourcetable,$member_table){
	$f=array();
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("$member_table")){
		$q->CreateMembersDayTable($member_table);
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE $member_table");
	$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS `HOUR` ,
	CLIENT, uid,MAC,hostname,account FROM $sourcetable GROUP BY cached, HOUR( zDate ) , CLIENT, uid,MAC,hostname,account HAVING QuerySize>0";
	
	$prefix="INSERT IGNORE INTO $member_table (zMD5,client,hour,size,hits,uid,cached,MAC,hostname,account) VALUES ";
	$results=$q->QUERY_SQL($sql);
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$client=addslashes(trim(strtolower($ligne["CLIENT"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
	
		$md5=md5("{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}");
		$sql_line="('$md5','$client','{$ligne["HOUR"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','{$ligne["cached"]}','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
		$f[]=$sql_line;
	
		
	
		if(count($f)>500){
			$q->QUERY_SQL("$prefix" .@implode(",", $f));
			$f=array();
		}
	
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("$prefix" .@implode(",", $f));
	}
	
	
	$sql="SELECT CLIENT, uid,MAC,hostname FROM `$member_table` GROUP BY CLIENT,uid,MAC,hostname";
	$results1=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q-mysql_error;return;}
	$Sum=mysql_num_rows($results1);
	if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} -> $member_table = $Sum\n";}
	$q->QUERY_SQL("UPDATE tables_day SET `MembersCount`='$Sum' WHERE tablename='$sourcetable'");	

	
}


function Repair_youtube_objects(){
	include_once(dirname(__FILE__)."/ressources/class.squid.youtube.inc");
	
	$q=new mysql_squid_builder();
	$sql="SELECT youtubeid FROM youtube_objects WHERE LENGTH(title)<5 LIMIT 0,100";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		echo "{$ligne["youtubeid"]}\n";
		$ytbe=new YoutubeStats();
		$ytbe->youtube_infos($ligne["youtubeid"],true);
		
	}
}

	
	


function _repair_from_sources_tables($sourcetable,$daytable){
	percentage("Repair $daytable FROM $sourcetable",2);
	//zMD5                             | sitename                   | familysite        | client        | hostname | account | hour | remote_ip     | MAC | country | size  | hits | uid           | category                      | cached
	$f=array();
	$sql="SELECT HOUR(zDate) as `hour`,SUM(QuerySize) as size, SUM(hits) as hits, 
	sitename,uid,CLIENT,hostname,MAC,account,cached FROM $sourcetable  
	GROUP BY `hour`,sitename,uid,CLIENT,hostname,MAC,account,cached";
	
	$prefix="INSERT IGNORE INTO $daytable 
	(`zMD5`,`sitename`,`familysite`,`client`,`hostname`,`uid`,`account`,`hour`,`MAC`,`size`,`hits`) VALUES";
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		while (list ($key,$val) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($val); }
		
		$f[]="('$zMD5','{$ligne["sitename"]}','$familysite','{$ligne["CLIENT"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["account"]}','{$ligne["hour"]}','{$ligne["MAC"]}','{$ligne["size"]}','{$ligne["hits"]}')";
		
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
		}
	}
	
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		$f=array();
	}
	
	$sql="SELECT COUNT(`sitename`) as tcount FROM $daytable WHERE LENGTH(`category`)=0";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
	$max=$ligne2["tcount"];
	$sql="UPDATE tables_day SET `not_categorized`=$max WHERE tablename='$sourcetable'";
	$q->QUERY_SQL($sql);
	
	
	
	
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


function percentage($text,$purc){


	$array["TITLE"]=$text." ".date("d H:i:s");
	$array["POURC"]=$purc;
	@file_put_contents("/usr/share/artica-postfix/ressources/squid.stats.progress.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/squid.stats.progress.inc",0755);
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] [$purc] $text";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";}
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);

}
?>