<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
ini_set('display_errors', 1);
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;
		//$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);	
		$GLOBALS["FORCE"]=true;
}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}



if($GLOBALS["VERBOSE"]){"******* echo Loading... *******\n";}

include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

$q=new mysql_squid_builder();

if($GLOBALS["VERBOSE"]){"echo Parsing arguments...\n";}

$sock=new sockets();
$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");
if(!is_numeric($DisableLocalStatisticsTasks)){$DisableLocalStatisticsTasks=0;}
if($DisableLocalStatisticsTasks==1){die();}

if($argv[1]=="--last7-days"){categorize_last7days();die();}
if($argv[1]=="--repair"){repair();die();}

categorize($argv[1]);


function categorize($day=null){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$day.". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	
	if($day==null){ return; }
	
	if(system_is_overloaded()){
		echo "Overloaded system, aborting task\n";
		writelogs_squid("Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__,"categorize");
		return ;
	}
	
	$daySource=$day;
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$q=new mysql_squid_builder();
	$time=strtotime("$day 00:00:00");
	$day=str_replace("-", "", $day);
	$table="{$day}_hour";
	$table_blocked="{$day}_blocked";
	$table_month=date("Ym",$time)."_day";
	$table_week=date("YW",$time)."_week";
	$table_week_blocked=date("YW",$time)."_blocked_week";
	$ipClass=new IP();
	echo "$daySource time: $time Table day=$table, table_blocked=$table_blocked, table_month=$table_month, table_week=$table_week\n";
	events("$daySource time: $time Table day=$table, table_blocked=$table_blocked, table_month=$table_month, table_week=$table_week");
	$t=time();
	$f=0;
	if(!$q->TABLE_EXISTS($table)){echo $table . " no such table\n";return;}
	$sql="SELECT sitename,category FROM $table GROUP BY sitename,category HAVING LENGTH(category)=0";
	events("$sql");
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Re-categorized table $table Query failed: `$sql` ({$q->mysql_error})",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
	
	if(!$q->TABLE_EXISTS($table_month)){
		if(!$q->CreateMonthTable($table_month)){
			writelogs_squid("failed Create $table_month table {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");
		}
	}
	
	if(!$q->TABLE_EXISTS($table_week)){
		if(!$q->CreateWeekTable($table_week)){
			writelogs_squid("failed Create $table_week table {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");
		}
	}
	
	$L=0;
	
	$q->QUERY_SQL("DELETE FROM `catztemp` WHERE `category`=''");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		
		if(preg_match("#^www\.(.+)#", $website,$re)){
			$q->QUERY_SQL("UPDATE $table SET sitename='{$re[1]}' WHERE sitename='$website'");
			$q->QUERY_SQL("UPDATE $table_month SET sitename='{$re[1]}' WHERE sitename='$website'");
			$q->QUERY_SQL("UPDATE $table_week SET sitename='{$re[1]}' WHERE sitename='$website'");
			$q->QUERY_SQL("UPDATE $table_blocked SET website='{$re[1]}' WHERE sitename='$website'");
			$q->QUERY_SQL("UPDATE $table_week_blocked SET website='{$re[1]}' WHERE sitename='$website'");
			$website=$re[1];
		}
		
		if($website==null){continue;}
		
		if($ipClass->isValid($website)){
			$website=gethostbyaddr($website);
			
		}
		
		if(isset($GLOBALS[__FUNCTION__][$website])){$category=$GLOBALS[__FUNCTION__][$website];}
		
		$category=$q->GET_CATEGORIES($website);
		if($category==null){
			if($ipClass->isValid($website)){ $category="ipaddr"; }
		}
		
		
		events("$day] $website = $category");
		$GLOBALS[__FUNCTION__][$website]=$category;
	
		if($L>500){
			if(system_is_overloaded()){ufdbguard_admin_events("Fatal: Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			$L=0;
		}
	
	
		if($category==null){continue;}
		$f++;
		events("Update $table $website = $category");
		$q->QUERY_SQL("UPDATE $table SET category='$category' WHERE sitename='$website'");
		if(!$q->ok){writelogs_squid("Re-categorized table $table failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
		events("Update $table_month $website = $category");
		$q->QUERY_SQL("UPDATE $table_month SET category='$category' WHERE sitename='$website'");
		if(!$q->ok){writelogs_squid("Re-categorized table $table_month failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
		events("Update $table_week $website = $category");
		$q->QUERY_SQL("UPDATE $table_week SET category='$category' WHERE sitename='$website'");
		if(!$q->ok){writelogs_squid("Re-categorized table $table_week failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
		events("Update $table_blocked $website = $category");
		$q->QUERY_SQL("UPDATE $table_blocked SET category='$category' WHERE website='$website'");
		if(!$q->ok){writelogs_squid("Re-categorized table $table_blocked failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
		events("Update $table_week_blocked $website = $category");
		if($q->CreateWeekBlockedTable($table_week_blocked));
		$q->QUERY_SQL("UPDATE $table_week_blocked SET category='$category' WHERE website='$website'");
		if(!$q->ok){writelogs_squid("Re-categorized table $table_week_blocked failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
	}
	
	$took=$unix->distanceOfTimeInWords($t,time());
	if($f>0){
		ufdbguard_admin_events("Re-categorized table $table with $f websites ($took)",__FUNCTION__,__FILE__,__LINE__,"statistics");
	}
	if($GLOBALS["VERBOSE"]){echo "recategorize_singleday($day) FINISH\n";}	
	
	
}


function categorize_last7days_percentage($text){


	$array["TITLE"]="Last 7 days: ".$text." ".date("H:i:s");
	$array["POURC"]=55;
	@file_put_contents("/usr/share/artica-postfix/ressources/squid.stats.progress.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/squid.stats.progress.inc",0755);
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] [46] $text";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";}
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);

}

function categorize_last7days(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT zDate,DATE_FORMAT(zDate,'%Y%m%d') as tprefix,not_categorized,tablename FROM tables_day WHERE not_categorized>0 AND zDate>DATE_SUB(zDate,INTERVAL 8 DAY) ORDER BY zDate");
	$currentDay=date("Ymd");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablesource="{$ligne["tprefix"]}_hour";
		if($ligne["tprefix"]==$currentDay){continue;}
		if($q->TABLE_EXISTS($tablesource)){
			_categorize_last7days($tablesource);
			$sql="SELECT COUNT(`sitename`) as tcount FROM $tablesource WHERE LENGTH(`category`)=0";
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$sql="UPDATE tables_day SET `not_categorized`='{$ligne2["tcount"]}' WHERE tablename='{$ligne["tablename"]}'";
			$q->QUERY_SQL($sql);
		}
	}

	
	

	
	
}

function repair(){
	
	$q=new mysql_squid_builder();

	$C=0;
	$currentDay=date("Ymd");
	$LIST_TABLES_HOURS=$q->LIST_TABLES_HOURS();
	while (list ($tablename, $value) = each ($LIST_TABLES_HOURS) ){
		$xtime=$q->TIME_FROM_HOUR_TABLE($tablename);
		if(date("Ymd",$xtime)==$currentDay){continue;}
		$DayTable=date("Ymd",$xtime)."_hour";
		if(!$q->TABLE_EXISTS($DayTable)){continue;}

		$tablenameS="dansguardian_events_".date("Ymd",$xtime);
		$sql="SELECT COUNT(`sitename`) as tcount FROM $DayTable WHERE LENGTH(`category`)=0";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		$max=$ligne2["tcount"];
		if($GLOBALS["VERBOSE"]){echo "$DayTable/$tablenameS = $max no categorized\n";}
		$sql="UPDATE tables_day SET `not_categorized`=$max WHERE tablename='$tablenameS'";
		$q->QUERY_SQL($sql);
		
	}

	

}



function _categorize_last7days($tablesource){
	

	$sql="SELECT SUM(hits) as hits ,sitename,category FROM `$tablesource` GROUP BY sitename,category HAVING LENGTH(category)=0 ORDER BY hits DESC LIMIT 0,500";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$currentDay=date("Ymd");
	if($GLOBALS["VERBOSE"]){echo "$sql\n$tablesource = ".mysql_num_rows($results)."\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		categorize_last7days_percentage($ligne["sitename"]);
		$cat=$q->GET_FULL_CATEGORIES($ligne["sitename"]);
		if($cat==null){
			
			continue;
		}
		$q->QUERY_SQL("UPDATE $tablesource SET `category`='$cat' WHERE `sitename`='{$ligne["sitename"]}'");
		
	}
	
	
}



function events($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/squid.stats.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] $text\n";
	@fwrite($h,$line);
	@fclose($h);
}