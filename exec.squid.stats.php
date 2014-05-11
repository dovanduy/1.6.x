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

WriteMyLogs("commands= ".implode(" ",$argv),"MAIN",__FILE__,__LINE__);
$GLOBALS["Q"]=new mysql_squid_builder();
$unix=new unix();
$GLOBALS["CLASS_UNIX"]=$unix;
events("Params: " .@implode(" ",$argv));
$sock=new sockets();
if($argv[1]=='--clean-catz-cache'){$GLOBALS["Q"]->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");die();}

if(!is_dir("/var/log/artica-postfix/artica-squid-events")){@mkdir("/var/log/artica-postfix/artica-squid-events",644,true);die();}
if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;$sock->SET_INFO("DisableArticaProxyStatistics", 1);}
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}


if($EnableRemoteStatisticsAppliance==1){if($GLOBALS["VERBOSE"]){echo "This server is not in charge of statistics... EnableRemoteStatisticsAppliance=1\n";}die();}
if($squidEnableRemoteStatistics==1){events("This server is not in charge of statistics...");die();}
if($DisableArticaProxyStatistics==1){ufdbguard_admin_events("Statistics are disabled in this configuration (DisableArticaProxyStatistics)","MAIN",__FILE__,__LINE__);die();}


if(!ifMustBeExecuted()){
	WriteMyLogs("This server is not in charge of statistics","MAIN",__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "this server is not in charge of statistics (categories repositories or Statistics Appliance) ...\n";}
	events("this server is not in charge of statistics (categories repositories or Statistics Appliance) ...");
	die();
}

if($GLOBALS["VERBOSE"]){echo "LAUNCH: '{$argv[1]}'\n";}
if($argv[1]=='--nocat-sync'){not_categorized_day_resync();exit;}
if($argv[1]=='--repair-hours'){repair_hours(true);if($GLOBALS["VERBOSE"]){echo "END: '{$argv[1]}'\n";}exit;} # Recherche les tables squidhour_* et les réinjectes.
if($argv[1]=='--defrag'){defragment_category_tables();exit;}
if($argv[1]=='--defragtable'){defragment_category_table($argv[2]);exit;}
if($argv[1]=='--rangetables'){rangetables();exit;}
if($argv[1]=='--thumbs'){thumbnail_site($argv[2]);exit;}
if($argv[1]=='--thumbs-sites'){thumbnail_query();exit;}
if($argv[1]=='--thumbs-alexa'){thumbnail_alexa($argv[2],$argv[3]);exit;}
if($argv[1]=='--thumbs-parse'){thumbnail_parse();exit;}


if($argv[1]=='--searchwords-hour'){hour_SearchWordTEMP();die();}
if($argv[1]=='--scan-hours'){scan_hours();die();}
if($argv[1]=='--scan-months'){scan_months();die();}
if($argv[1]=='--tables-days'){table_days();die();}
if($argv[1]=='--summarize-days'){summarize_days();die();}
if($argv[1]=='--isCompressed'){IsCompressed($argv[2]);die();}
if($argv[1]=='--Uncompress'){Uncompress($argv[2]);die();}
if($argv[1]=='--UserSizeD'){UserSizeD();die();}




if($argv[1]=='--block-days'){block_days();die();}
if($argv[1]=='--block-week'){die();}
if($argv[1]=="--week"){week_uris(true);youtube_week();die(true);}


if($argv[1]=='--flow-month'){flow_month();die();}
if($argv[1]=='--members'){members_hours();die();}
if($argv[1]=='--members-month'){members_month();die();}
if($argv[1]=='--parse-cacheperfs'){squid_cache_perfs();die();}
if($argv[1]=='--show-tables'){show_tables();die();}
if($argv[1]=='--tables'){$q=new mysql();$q->CheckTablesSquid();die();}
if($argv[1]=='--members-month-kill'){members_month_delete();exit;}
if($argv[1]=='--fix-tables'){$GLOBALS["Q"]->FixTables();exit;}
if($argv[1]=='--visited-sites2'){visited_sites();exit;}
if($argv[1]=='--sync-categories'){sync_categories();exit;}

if($argv[1]=='--re-categorize-day'){recategorize_singleday($argv[2]);exit;}
if($argv[1]=='--re-categorize-week'){recategorize_week($argv[2]);exit;}
if($argv[1]=='--whois'){visited_sites_whois();exit;}
if($argv[1]=='--calculate-not-categorized'){not_categorized_day_resync();exit;}
if($argv[1]=='--optimize'){optimize_tables();exit;}
if($argv[1]=='--webcacheperfs'){webcacheperfs();exit;}
if($argv[1]=='--visited-days'){visited_websites_by_day();exit;}
if($argv[1]=='--repair-categories'){RepairCategoriesBases();exit;}
if($argv[1]=='--members-central'){members_central();exit;}
if($argv[1]=='--members-central-reset'){members_central_reset();exit;}
if($argv[1]=='--repair-week'){repair_week();exit;}
if($argv[1]=='--dump-days'){dump_days();exit;}
if($argv[1]=='--members-central-grouped'){members_central_grouped();exit;}
if($argv[1]=='--summarize-daysingle'){_summarize_days($argv[2],$argv[3]);exit;}
if($argv[1]=='--youtube-dayz'){youtube_dayz();exit;}
if($argv[1]=='--weekdaynum'){WeekDaysNums();exit;}
if($argv[1]=='--youtubeweek'){youtube_week();exit;}
if($argv[1]=='--macuid'){macuid();exit;}
if($argv[1]=='--export-last-websites'){export_last_websites();exit;}

//visited_sites
if($GLOBALS["VERBOSE"]){echo "UNABLE TO UNDERSTAND: '{$argv[1]}'\n";}
ufdbguard_admin_events("ERROR: UNABLE TO UNDERSTAND: '{$argv[1]}'","MAIN",__FILE__,__LINE__,"ERROR");	






function sync_categories(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	$sql="SELECT * FROM visited_sites WHERE sitename LIKE 'www.%'";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		if(preg_match("#^www\.(.+)#", $website,$re)){
			$ligne2=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT sitename FROM visited_sites WHERE sitename='{$re[1]}'"));
			if($ligne2["sitename"]<>null){
				$GLOBALS["Q"]->QUERY_SQL("DELETE FROM visited_sites WHERE sitename='$website'");
			}else{
				$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET sitename='{$re[1]}' WHERE sitename='{$ligne["sitename"]}'");
			}
			$GLOBALS["Q"]->UPDATE_WEBSITES_TABLES($ligne["sitename"],$re[1]);
		}
	}	
	
	
	$sql="SELECT * FROM visited_sites WHERE LENGTH(category)=0";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Starting analyzing not categorized websites Failed ".$GLOBALS["Q"]->mysql_error,__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	$num_rows = mysql_num_rows($results);
	$t=time();
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	ufdbguard_admin_events("Starting analyzing $num_rows not categorized websites",__FUNCTION__,__FILE__,__LINE__,"stats");
	$c=0;$d=0;
	$CATZP=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		$category=null;
		if($website==null){continue;}
		$t2=time();
		$c++;
		$d++;
		if($d>1000){if($GLOBALS["VERBOSE"]){echo "Analyzed $c websites\n";$d=0;}}
		$category=$GLOBALS["Q"]->GET_CATEGORIES($website,true);
		if(trim($category)<>null){
			$CATZP++;
			$took=$unix->distanceOfTimeInWords($t2,time());
			ufdbguard_admin_events("New category found for `$website` = `$category` $took",__FUNCTION__,__FILE__,__LINE__,"stats");
			$GLOBALS["Q"]->UPDATE_CATEGORIES_TABLES($website,$category);
			if($GLOBALS["VERBOSE"]){echo "UPDATE_CATEGORIES_TABLES DONE..\n";}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET category='$category' WHERE sitename='$website'");
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal error while update visited_sites {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}
		
		if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
		
	
		
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("$CATZP new categorized website task:finish ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --defrag --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
}




function recategorize_week($tablename,$nopid=false,$nochilds=false){
	
	$unix=new unix();
	if(!$nopid){
		
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	}	
	
	$q=new mysql_squid_builder();
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=$q;}
	$time=$q->TIME_FROM_WEEK_TABLE($tablename);
	$week=date("W",$time);
	$year=date("Y",$time);
	$month=date("m",$time);
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as prefix FROM `tables_day` WHERE WEEK(zDate)=$week AND YEAR(zDate)='$year'";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$table="{$ligne["prefix"]}_hour";
			if($q->TABLE_EXISTS($table)){
				$TABLES[$table]=true;
			}	
		}

	$monthtable="$year{$month}_day";
	$sql="SELECT sitename,category FROM `$tablename` GROUP BY sitename HAVING LENGTH(category)=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		$category=$GLOBALS["Q"]->GET_CATEGORIES($website);
		if($category==null){continue;}
		reset($TABLES);
		
		$q->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$website'");
		if(!$nochilds){
			while (list ($tablenameH,$none) = each ($TABLES) ){
				$q->QUERY_SQL("UPDATE $tablenameH SET category='$category' WHERE sitename='$website'");
			}
		}
		if($q->TABLE_EXISTS($monthtable)){
			$q->QUERY_SQL("UPDATE $monthtable SET category='$category' WHERE sitename='$website'");
		}
		
	}
	
}

function recategorize_singleday($day=null,$nopid=false,$tablename=null){
	if($day==null){return;}
	$unix=new unix();
	$php=$unix->find_program("php");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.recategorize.missed.php $day >/dev/null 2>&1 &");
}




function scan_hours($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/scan_hours.time";	
	$unix=new unix();
	if($GLOBALS["FORCE"]){$nopid=false;}
	
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		
		if($unix->process_exists($oldpid)){
			$ttl=$unix->PROCCESS_TIME_MIN($oldpid);
			if($ttl>120){
				writelogs_squid("Fatal, Executed $oldpid more than 120mn {$ttl}mn, killing task",__FUNCTION__,__FILE__,__LINE__);
				$kill=$unix->find_program("kill");
				shell_exec("$kill -9 $oldpid >/dev/null");
			}else{
				writelogs_squid("Fatal, Executed $oldpid since {$ttl}mn, aborting task",__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}
	}
	
	if(!$GLOBALS["FORCE"]){
		$timetook=$unix->file_time_min($timefile);
		if($timetook<50){
			writelogs_squid("50mn minimum to run this task current={$timetook}Mn, aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	
	
	@file_put_contents($timefile, time());
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	

	
	
	
	WeekDaysNums();

	
}

function scan_months(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	members_month(true);
	flow_month(true);
	block_days(true);	
	week_uris();
	
}



function flow_month($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		$unix=new unix();
		if($unix->process_exists($oldpid,$myfile)){return;}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$sql="SELECT MONTH(zDate) AS smonth,YEAR(zDate) AS syear FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND month_flow=0 ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	
	if(!$GLOBALS["Q"]->ok){
		if(preg_match("#Unknown column#i", $GLOBALS["Q"]->mysql_error)){$GLOBALS["Q"]->CheckTables();$results=$GLOBALS["Q"]->QUERY_SQL($sql);}
	}
	if(!$GLOBALS["Q"]->ok){
		events_tail("{$GLOBALS["Q"]->mysql_error}\n------\n$sql\n----");
		return;
	}
		
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		$month=$ligne["smonth"];
		$year=$ligne["syear"];
		
		if(isset($already["$month$year"])){continue;}		
		
		flow_month_query($month,$year);
		$already["$month$year"]=true;
	}		
}


function visited_websites_by_day($nopid=false){
	if(isset($GLOBALS["visited_websites_by_day_executed"])){
		if($GLOBALS["VERBOSE"]){echo "visited_websites_by_day():: Already executed, aborting\n";}
		return true;
	}
	$GLOBALS["visited_websites_by_day_executed"]=true;
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
		
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix 
	FROM tables_day WHERE visited_day=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	$countWorks=mysql_num_rows($results);
	if($countWorks==0){
		ufdbguard_admin_events("Task finish: No day to scan",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$t1=time();
	$c=0;
	ufdbguard_admin_events("Starting check visited websites by day in $countWorks tables...",__FUNCTION__,__FILE__,__LINE__,"stats");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tableDest=$ligne["suffix"]."_visited";
		$tableSource=$ligne["suffix"]."_hour";
		events("visited_websites_by_day():: $tableDest -> $tableSource ($countWorks)");
		if(!$GLOBALS["Q"]->CreateVisitedDayTable($tableDest)){
			ufdbguard_admin_events("Fatal while creating  $tableDest table with error {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
		if(!$GLOBALS["Q"]->TABLE_EXISTS($tableSource)){
			ufdbguard_admin_events("$c] Fatal while filling  $tableDest table $tableSource no such table",__FUNCTION__,__FILE__,__LINE__,"stats");
			continue;
		}
		
		$c++; 
		if(visited_websites_by_day_perform($tableSource,$tableDest)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET visited_day=1 WHERE tablename='{$ligne["tablename"]}'");
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}
		
		if(SquidStatisticsTasksOverTime()){
			stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__);
			return;
		}
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time());
	events("visited_websites_by_day():: Success update $countWorks in $took");
	ufdbguard_admin_events("Success update $countWorks in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
	
}

function visited_websites_by_day_perform($tableSource,$tableDest){
		$unix=new unix();
		$sql="SELECT SUM(size) as tsize,SUM(hits) as thits, sitename,familysite FROM $tableSource GROUP BY sitename,familysite";
		$t=time();
		events("visited_websites_by_day_perform()::$tableSource $sql");
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		events("visited_websites_by_day_perform()::$tableSource ".$unix->distanceOfTimeInWords($t,time(),true));
		
		
		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$prefix="INSERT IGNORE INTO $tableDest (sitename,familysite,size,hits) VALUES ";
		$countWorks=mysql_num_rows($results);
		if($countWorks==0){return true;}
		
		
		$f=array();
		$c=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne=mysql_escape_line_query($ligne,true);
			$f[]="('{$ligne["sitename"]}','{$ligne["familysite"]}','{$ligne["tsize"]}','{$ligne["thits"]}')";
			$c++;
			if(count($f)>1000){
				events("visited_websites_by_day_perform()::$tableSource 1000/$c rows ".$unix->distanceOfTimeInWords($t,time(),true));
				$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
				if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
				$f=array();
				if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			}
			
		}
		
	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$f=array();
	}
	ufdbguard_admin_events("Success filling $tableDest with $countWorks entries",__FUNCTION__,__FILE__,__LINE__,"stats");				
	return true;	
		
}



function flow_month_query($month,$year){
	events_tail("Processing $year/$month ".__LINE__);
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m') AS suffix,DATE_FORMAT(zDate,'%Y%m%d') AS suffix2,DAY(zDate) as tday,YEAR(zDate) AS tyear,month(zDate) AS tmonth FROM tables_day 
	WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND YEAR(zDate)=$year AND month(zDate)=$month ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error}");return;}
		
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$next_table=$ligne["suffix"]."_day";
		$tabledatas=$ligne["suffix2"]."_hour";
		$day=$ligne["tday"];
		if(!$GLOBALS["Q"]->CreateMonthTable($next_table)){events_tail("Failed to create $next_table");return;}
		if(!_flow_month_query_perfom($tabledatas,$next_table,$day)){events_tail("Failed to process $tabledatas to $next_table");return;}
	}
	
	if("$year$month"<>date('Ym')){
		events_tail("Processing $year/$month -> Close UPDATE tables_day SET month_flow=1 WHERE MONTH(zDate)=$month AND YEAR(zDate)=$year line ".__LINE__);
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET month_flow=1 WHERE MONTH(zDate)=$month AND YEAR(zDate)=$year");
	}
	return true;
	
}

function _flow_month_query_perfom($SourceTable,$destinationTable,$day){
	
	$output_rows=false;
	$sql="SELECT sitename, familysite, client, remote_ip, country, SUM( size ) as QuerySize, SUM( hits ) as hits, 
	uid, category, cached,MAC,account
	FROM $SourceTable GROUP BY sitename, familysite, client, remote_ip, country, uid, category, cached,MAC,account";
	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error}\n------\n$sql\n----");return;}

	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return true;}
	
	events_tail("Processing $SourceTable -> $destinationTable for day $day $num_rows  rows in line ".__LINE__);
	
	$prefix="INSERT IGNORE INTO $destinationTable (zMD5,sitename,client,`day`,remote_ip,country,size,hits,uid,category,cached,familysite,MAC,account) VALUES ";
	
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$client=addslashes(trim(strtolower($ligne["client"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
		$sitename=addslashes(trim(strtolower($ligne["sitename"])));
		$remote_ip=addslashes(trim(strtolower($ligne["remote_ip"])));
		$country=addslashes(trim(strtolower($ligne["country"])));
		$category=addslashes(trim(strtolower($ligne["category"])));
		$familysite=addslashes(trim(strtolower($ligne["familysite"])));
		
	
		$md5=md5("{$ligne["client"]}$day{$ligne["uid"]}{$ligne["QuerySize"]}$remote_ip$country{$ligne["hits"]}$sitename");
		$sql_line="('$md5','$sitename','$client','$day','$remote_ip','$country','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','$category','{$ligne["cached"]}','$familysite','{$ligne["MAC"]}','{$ligne["account"]}')";
		$f[]=$sql_line;
		
		if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}	
		
		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}
		
	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
	}
	
	return true;	

}





function members_month($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($nopid){
		$oldpid=@file_get_contents($pidfile);
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);		
	}
	
	

	
	$q=new mysql_squid_builder();
	
	
	
	$sql="SELECT MONTH(zDate) AS smonth,YEAR(zDate) AS syear FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND month_members=0 ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error}\n------\n$sql\n----");return;}
		
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		$month=$ligne["smonth"];
		$year=$ligne["syear"];
		
		if(isset($already["$month$year"])){continue;}		
		
		members_month_query($month,$year);
		$already["$month$year"]=true;
	}
}


function members_month_query($month,$year){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$q=new mysql_squid_builder();
	events_tail("Processing $year/$month ".__LINE__);
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m') AS suffix,DATE_FORMAT(zDate,'%Y%m%d') AS suffix2,DAY(zDate) as tday,
	YEAR(zDate) AS tyear,month(zDate) AS tmonth FROM tables_day 
	WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND YEAR(zDate)=$year AND month(zDate)=$month ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error}");return;}
		
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$next_table=$ligne["suffix"]."_members";
		$tabledatas=$ligne["suffix2"]."_members";
		$day=$ligne["tday"];
		if(!$GLOBALS["Q"]->CreateMembersMonthTable($next_table)){events_tail("Failed to create $next_table");return;}
		if(!_members_month_perfom($tabledatas,$next_table,$day)){events_tail("Failed to process $tabledatas to $next_table");return;}
	}
	
	if("$year$month"<>date('Ym')){
		events_tail("Processing $year/$month -> Close UPDATE tables_day SET month_members=1 WHERE MONTH(zDate)=$month AND YEAR(zDate)=$year line ".__LINE__);
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET month_members=1 WHERE MONTH(zDate)=$month AND YEAR(zDate)=$year");
	}
	return true;
	
}

function _members_month_perfom($sourcetable,$destinationtable,$day){
	$output_rows=false;
	

	
	
	$sql="SELECT SUM( size ) AS QuerySize, SUM(hits) as hits,cached, client, uid,MAC,hostname,account
	FROM $sourcetable
	GROUP BY cached, client, uid,MAC,hostname,account
	HAVING QuerySize>0 ";	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail($GLOBALS["Q"]->mysql_error);return;}	
	$num_rows=mysql_num_rows($results);
	events_tail("Processing $sourcetable -> $destinationtable for day $day $num_rows  rows in line ".__LINE__);
	
	$prefix="INSERT IGNORE INTO $destinationtable (`zMD5`,`client`,`day`,`size`,`hits`,`uid`,`cached`,`MAC`,`hostname`,`account`) VALUES ";
	
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$client=addslashes(trim(strtolower($ligne["client"])));
		$uid=addslashes(trim(strtolower($ligne["uid"])));
	
		$md5=md5("$client$day$uid{$ligne["QuerySize"]}{$ligne["hits"]}");
		$sql_line="('$md5','$client','$day','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','{$ligne["cached"]}','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
		$f[]=$sql_line;
		
		if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}	
		
		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
			$f=array();
		}
		
	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
	}
	return true;	
	
	
}



function members_hours($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	
	$currenttable="dansguardian_events_".date('Ymd');
	$next_table=date('Ymd')."_members";
	_members_hours_perfom($currenttable,$next_table);	
	
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as suffix,tablename FROM tables_day WHERE members=0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){events_tail("$q->mysql_error");return;}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$next_table=$ligne["suffix"]."_members";
		if(!$q->CreateMembersDayTable($next_table)){events_tail("Failed to create $next_table");return;}
		if(!_members_hours_perfom($ligne["tablename"],$next_table)){events_tail("Failed to process {$ligne["tablename"]} to $next_table");return;}
	}
}

function _members_hours_perfom($tabledata,$nexttable){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$phpfile=dirname(__FILE__)."/exec.squid.stats.members.hours.php";
	exec("$php $phpfile $tabledata $nexttable schedule-id={$GLOBALS["SCHEDULE_ID"]} 2>&1",$results);
	while (list ($index, $ligne) = each ($results)){
		if(preg_match("#SUCCESS#", $ligne)){
			return true;
		}
		
	}
	
	return false;
}












function events_tail($text){
		events($text);
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		if($GLOBALS["VERBOSE"]){echo "$date $text\n";}
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}


function events($text){
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
function events_repair($text){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/squid.stats.repair.log";
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

function squid_cache_perfs(){
	
$q=new mysql();
$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as tdate FROM squid_cache_perfs
		WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 HOUR) ORDER BY zDate DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){echo "$sql\n$q->mysql_error\n";}
		$lastDate=$ligne["tdate"];
		if($lastDate<>null){$lastDate=" AND DATE_FORMAT( zDate, '%Y-%m-%d %H:00:00' )>'$lastDate' ";}
		if($GLOBALS["VERBOSE"]){echo "lastDate=$lastDate\n";} 	
	
	$dansguardian_events="dansguardian_events_".date('Ymd');
	
	$sql="SELECT SUM( QuerySize ) AS tsize, cached, DATE_FORMAT( zDate, '%Y-%m-%d %H:00:00' ) AS tdate
		FROM $dansguardian_events
		WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 1 HOUR ) $lastDate
		GROUP BY cached, tdate";
	
	$results=$q->QUERY_SQL($sql,"squidlogs");
	if(!$q->ok){echo "$sql\n$q->mysql_error\n";}
	if(mysql_num_rows($results)==0){return;}

	$prefix="INSERT IGNORE INTO squid_cache_perfs(zmd5,zDate,size,cached) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=md5("{$ligne["tdate"]}{$ligne["cached"]}");
		$sqltext="('$zmd5','{$ligne["tdate"]}',{$ligne["tsize"]},{$ligne["cached"]})";
		
		$sqlT[]=$sqltext;
		if(count($sqlT)>100){
			$q->QUERY_SQL("$prefix".@implode(",", $sqlT),"artica_events");
			$sqlT=array();
		}
		
	}	
	
		if(count($sqlT)>0){
			$q->QUERY_SQL("$prefix".@implode(",", $sqlT),"artica_events");
			$sqlT=array();
		}
}

function installgeoip(){
	if(isset($GLOBALS["installgeoip_executed"])){return;}
	$GLOBALS["installgeoip_executed"]=true;
	
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){$time=$unix->file_time_min("/etc/artica-postfix/FROM_ISO");if($time<60){return;}}
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$pecl=$unix->find_program("pecl");
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.geoip.update.php >/dev/null 2>&1 &");
	
	if(is_file($pecl)){
			if(!is_file("/etc/artica-postfix/php-geoip-checked")){
			shell_exec("/usr/share/artica-postfix/bin/setup-ubuntu --check-base-system");
			shell_exec("$pecl install geoip");
			shell_exec("/etc/init.d/artica-postfix restart apache");
			@file_put_contents("/etc/artica-postfix/php-geoip-checked",time());
			}
		}	
	
}

function UpdateGeoip(){
	if(isset($GLOBALS["UpdateGeoip_executed"])){return;}
	$GLOBALS["UpdateGeoip_executed"]=true;
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$database="/usr/share/GeoIP/GeoIP.dat";
	if(!is_file($database)){installgeoip();return null;}
	if(!is_file("/usr/local/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/local/share/GeoIP/GeoLiteCity.dat")){
			shell_exec("$ln -s /usr/local/share/GeoIP/GeoLiteCity.dat /usr/local/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}


	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/share/GeoIP/GeoLiteCity.dat")){
			system("$ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}

	if(!function_exists("geoip_record_by_name")){installgeoip();return null;}
	
}


function GeoIP($servername){
	
	
	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		UpdateGeoip();
		if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){return array();}
	}
	
	
	if(!function_exists("geoip_record_by_name")){
		if($GLOBALS["VERBOSE"]){echo "geoip_record_by_name no such function\n";}
		return array();
	}
	$site_IP=gethostbyname($servername);
	if($site_IP==null){events("GeoIP():: $site_IP is Null");return array();}
	
	
	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+#",$site_IP)){
		events("GeoIP():: $site_IP ->gethostbyname()");
		$site_IP=gethostbyname($site_IP);
		events("GeoIP():: $site_IP");
	}
	
	if(isset($GLOBALS["COUNTRIES"][$site_IP])){
		if(trim($GLOBALS["COUNTRIES"][$site_IP])<>null){
			events("GeoIP():: $site_IP {$GLOBALS["COUNTRIES"][$site_IP]}/{$GLOBALS["CITIES"][$site_IP]}");
			if($GLOBALS["VERBOSE"]){echo "$site_IP:: MEM={$GLOBALS["COUNTRIES"][$site_IP]}\n";}
			return array($GLOBALS["COUNTRIES"][$site_IP],$GLOBALS["CITIES"][$site_IP]);
		}
	}
	
	$record = geoip_record_by_name($site_IP);
	if ($record) {
		$Country=$record["country_name"];
		$city=$record["city"];
		$GLOBALS["COUNTRIES"][$site_IP]=$Country;
		$GLOBALS["CITIES"][$site_IP]=$city;
		events("GeoIP():: $site_IP $Country/$city");
		return array($GLOBALS["COUNTRIES"][$site_IP],$GLOBALS["CITIES"][$site_IP]);
	}else{
		events("GeoIP():: $site_IP No record");
		if($GLOBALS["VERBOSE"]){echo "$site_IP:: No record\n";}
		return array();
	}
		
	return array();
}

function show_tables(){
	$q=new mysql_squid_builder();
	$q->EVENTS_SUM();
}

function COUNT_REQUESTS($tablename){
	
	$sql="";
	
	
}

function not_categorized_day_resync(){
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `tprefix`, DATE_FORMAT(zDate,'%Y%u') AS `tprefixW`, `tablename`  FROM tables_day WHERE `not_categorized`>0 ORDER BY zDate DESC";
	if($GLOBALS["VERBOSE"]){echo "not_categorized_day_resync\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}
		ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){
		if($GLOBALS["VERBOSE"]){echo "No item\n";}
		return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$table_day="{$ligne["tprefix"]}_hour";
		$table_week="{$ligne["tprefixW"]}_week";
		
		if($GLOBALS["Q"]->TABLE_EXISTS("$table_week")){
			$WEEKZ[$table_week]=true;
		}
		
		if(!$GLOBALS["Q"]->TABLE_EXISTS("$table_day")){
			if($GLOBALS["VERBOSE"]){echo "$table_day no such table\n";}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized=0 WHERE tablename='$tablename'");
			continue;
		}
		$sql="SELECT sitename,category FROM $table_day GROUP BY sitename,category HAVING LENGTH(category)=0 ";
		$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
		
		if(!$GLOBALS["Q"]->ok){
			if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["Q"]->mysql_error}\n";}
			ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}		
		
		$NotCategorized=mysql_num_rows($results2);
			
		if($GLOBALS["VERBOSE"]){echo "$table_day $NotCategorized Websites...\n";}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized=$NotCategorized WHERE tablename='$tablename'");
	}
	while (list ($tableweek, $ligne) = each ($WEEKZ)){
		recategorize_week($tableweek,true,true);
	}
	
	
}






function _not_categorized_day_scan(){
	
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as `suffix`, `tablename` FROM tables_day ORDER BY zDate DESC";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){
		ufdbguard_admin_events("Task finished No data",__FUNCTION__,__FILE__,__LINE__,"statistics");
		echo "No data `$sql`". __FUNCTION__." ".__LINE__."\n";}return;}
	$c=0;
	$f=0;
	ufdbguard_admin_events("$num_rows items to scan",__FUNCTION__,__FILE__,__LINE__,"statistics");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tableToScan="{$ligne["suffix"]}_hour";
		if($GLOBALS["VERBOSE"]){echo "tableToScan:$tableToScan\n";}
		$ligne2=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT COUNT(zMD5) as tcount FROM $tableToScan WHERE category=''"));
		if(!$GLOBALS["Q"]->ok){continue;}
		if($ligne2["tcount"]>0){$c++;$f=$f+$ligne2["tcount"];}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized={$ligne2["tcount"]} WHERE tablename='{$ligne["tablename"]}'");
	}	
	if($c>0){
		ufdbguard_admin_events("$c tables with not categorized websites ($f rows)",__FUNCTION__,__FILE__,__LINE__,"statistics");
		
	}
	
}

function members_central_grouped(){
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDaysGrouped")){$GLOBALS["Q"]->CheckTables();}
	$unix=new unix();
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDaysGrouped")){ufdbguard_admin_events("Fatal UserAuthDaysGrouped no such table", __FUNCTION__, __FILE__, __LINE__, "stats");return;}

	$sql="TRUNCATE TABLE `UserAuthDaysGrouped`";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}	
	
	
	
	$sql="SELECT ipaddr, hostname, uid, MAC, account, SUM( QuerySize ) AS QuerySize, SUM( hits ) AS hits 
	FROM UserAuthDays GROUP BY ipaddr, hostname, uid, MAC, account";
	
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "$count items...\n";}
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}	
	$cc=0;
	$f=array();
	$prefix="INSERT INTO UserAuthDaysGrouped (`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`QuerySize`,`hits`) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["uid"]=trim($ligne["uid"]);
		$ligne["MAC"]=trim($ligne["MAC"]);
		if(trim($ligne["ipaddr"])==null){continue;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$ligne["ipaddr"])){continue;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$ligne["uid"])){continue;}
		if($ligne["MAC"]<>null){if(!preg_match("#[0-9a-z\:]+$#", $ligne["MAC"])){continue;}}
		if(strpos($ligne["hostname"], ":")>0){continue;}
		if(strpos($ligne["hostname"], "%")>0){continue;}
		if(strpos($ligne["ipaddr"], ":")>0){continue;}
		if(strpos($ligne["ipaddr"], "%")>0){continue;}
		if(strpos($ligne["uid"], "/")>0){continue;}
		if(strpos($ligne["uid"], "&")>0){continue;}
		writeDebugLogs("Insert {$ligne["ipaddr"]} {$ligne["hostname"]} {$ligne["uid"]}",__FUNCTION__,__FILE__,__LINE__);
		
		$f[]="('{$ligne["ipaddr"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["account"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}')";
		
		if(count($f)>500){
			$cc=$cc+count($f);
			if($GLOBALS["VERBOSE"]){echo "$cc/$count\n";}
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			unset($f);
			$f=array();
		}
		
	}
	
	
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "$cc/$count\n";}
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
	}
	
}

function members_central_reset(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE UserAuthDays");
	$q->QUERY_SQL("DROP TABLE UserAuthDaysGrouped");
	$q->QUERY_SQL("UPDATE tables_day SET memberscentral=0 WHERE memberscentral=1");
	$q->CheckTables();
	members_central();
	
	
	
}


function members_central($aspid=false){
	
	if(!ifMustBeExecuted()){
		ufdbguard_admin_events("Not necessary to execute this task...",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	if(!$aspid){	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		$unix=new unix();
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("$oldpid already running, aborting",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
		@file_put_contents($pidfile, getmypid());
		
	}
	
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDays")){$GLOBALS["Q"]->CheckTables();}
	$unix=new unix();
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDays")){ufdbguard_admin_events("Fatal UserAuthDays no such table", __FUNCTION__, __FILE__, __LINE__, "stats");return;}
	$sql="SELECT tablename,zDate FROM tables_day WHERE memberscentral=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	
	
	
	if(!$GLOBALS["Q"]->ok){if(preg_match("#Unknown column#", "{$GLOBALS["Q"]->mysql_error}")){$GLOBALS["Q"]->CheckTables();$results=$GLOBALS["Q"]->QUERY_SQL($sql);}}
	if(!$GLOBALS["Q"]->ok){
		ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}
	
	$t=time();
	$c=0;
	if($GLOBALS["VERBOSE"]){echo "checking ".mysql_num_rows($results)." items\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(_members_central_perform($ligne["tablename"],$ligne["zDate"])){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET memberscentral=1 WHERE tablename='{$ligne["tablename"]}'");
			if(!$GLOBALS["Q"]->ok){
				ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
				return;
			}
		}else{
			ufdbguard_admin_events("Fatal unable to update members central table from {$ligne["tablename"]}", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
		$c++;
				
		
	}
	
	if($c>0){
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		ufdbguard_admin_events("Success update members central tab with $c analyzed tables, took:$took",__FUNCTION__,__FILE__,__LINE__,"stats");
	}
	

	members_central_grouped();
	
}





function _members_central_perform($tablesource,$date){
	$f=array();
	if(!$GLOBALS["Q"]->TABLE_EXISTS("$tablesource")){return true;}
	if($GLOBALS["VERBOSE"]){echo "checking table $tablesource\n";}
	$sql="SELECT SUM(hits) as thits,SUM(QuerySize) as tsize,CLIENT,hostname,uid,MAC,account FROM `$tablesource`
	GROUP BY CLIENT,hostname,uid,MAC,account";
	$unix=new unix();
	
	
	
	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		ufdbguard_admin_events("$tablesource is an old table, change to compatible mode", __FUNCTION__, __FILE__, __LINE__, "stats");
		if(preg_match("#Unknown column 'hits'#i",$GLOBALS["Q"]->mysql_error)){
			$sql="SELECT COUNT(ID) as thits,SUM(QuerySize) as tsize,CLIENT,hostname,uid,MAC,account FROM `$tablesource`
			GROUP BY CLIENT,hostname,uid,MAC,account";	
			$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		}
	}
		
		
	if(!$GLOBALS["Q"]->ok){	
		ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}	
	
	$numrows=mysql_num_rows($results);
	echo "$tablesource $numrows entries\n";
	$prefix="INSERT IGNORE INTO UserAuthDays (`zMD5`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`QuerySize`,`hits`) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ligne["uid"]=trim($ligne["uid"]);
		$ligne["MAC"]=trim($ligne["MAC"]);
		if(trim($ligne["CLIENT"])==null){continue;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$ligne["CLIENT"])){continue;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$ligne["uid"])){continue;}
		if($ligne["MAC"]<>null){if(!preg_match("#[0-9a-z\:]+$#", $ligne["MAC"])){continue;}}
		if(strpos($ligne["hostname"], ":")>0){continue;}
		if(strpos($ligne["hostname"], "%")>0){continue;}
		if(strpos($ligne["CLIENT"], ":")>0){continue;}
		if(strpos($ligne["CLIENT"], "%")>0){continue;}
		if(strpos($ligne["uid"], "/")>0){continue;}
		if(strpos($ligne["uid"], "&")>0){continue;}		
		
		
		
		$md5=md5(serialize($ligne));
		$ipaddr=$ligne["CLIENT"];
		$hostname=$ligne["hostname"];
		
		$MAC=$ligne["MAC"];
		$account=$ligne["account"];
		$QuerySize=$ligne["tsize"];
		$hits=$ligne["thits"];
		$uid=$ligne["uid"];
		
		if(strlen($uid)<3){if(strlen($MAC)>3){$uid=$GLOBALS["Q"]->UID_FROM_MAC($MAC);}}
		if($ipaddr<>null){if($uid==null){$uid=$GLOBALS["Q"]->UID_FROM_IP($ipaddr);}}
		
		$uid=addslashes($uid);
		
		
		$f[]="('$md5','$date','$ipaddr','$hostname','$uid','$MAC','$account','$QuerySize','$hits')";
		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}
			$f=array();
		}
	}
	
		if(count($f)>0){
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}
			$f=array();
		}	
		
	$sql="SELECT uid,MAC FROM webfilters_nodes";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strlen($ligne["uid"])>1){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE UserAuthDays SET uid='{$ligne["uid"]}' WHERE MAC='{$ligne["MAC"]}' AND LENGTH(uid)<2");
		}
	}			
	
	return true;
}





function members_month_delete(){
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m') AS suffix,DATE_FORMAT(zDate,'%Y%m%d') AS suffix2,DAY(zDate) as tday,YEAR(zDate) AS tyear,month(zDate) AS tmonth FROM tables_day";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error}\n------\n$sql\n----");return;}
		
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["Q"]->TABLE_EXISTS("{$ligne["suffix"]}_members")){
			echo "Delete table {$ligne["suffix"]}_members\n";
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `{$ligne["suffix"]}_members`");
		}
		
		
	}	
	$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET month_members=0");
	
	
	
	
}

function visited_sites_whois(){
	$unix=new unix();
	if(!$GLOBALS["FORCE"]){
		
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
		$timeOfFile=$unix->file_time_min($timefile);
		if($timeOfFile<520){return;}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}	
	
	
	$t1=time();	
	$sql="SELECT familysite FROM visited_sites WHERE whois IS NULL AND `familysite`!='ipaddr' AND LENGTH(familysite)>0";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		stats_admin_events(0,$q->mysql_error,null,__FILE__,__LINE__); 
		return; 
	}
	$num_rows = mysql_num_rows($results);
	
	stats_admin_events(2,"visited_sites -> whois -> $num_rows rows" ,null,__FILE__,__LINE__);
	
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas `$sql`". __FUNCTION__." ".__LINE__."\n";}return;}
	$c=0;
	$f=0;
	$already=array();
	$MinutesFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".minutes";
	@unlink($MinutesFile);
	@file_put_contents($MinutesFile, time());
	$D=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$D++;
		$domain=$ligne["familysite"];
		if(!isset($already[$domain])){
			$whois = new Whois();	
			$result = $whois->Lookup($domain);
			$already[$domain]=$result;
			$c++;
		}else{
			continue;
		}
		if(!is_array($result)){
			if($GLOBALS["VERBOSE"]){echo "$domain no results...\n";$f++;continue;} 
		}
		$whoisdatas=addslashes(serialize($result));
		
		$TimeLoop=$unix->file_time_min($MinutesFile);
		if($TimeLoop>4){
			if(count($ROWS_visited_sites_prefix)>0){
				$GLOBALS["Q"]->QUERY_SQL($ROWS_visited_sites_prefix.@implode(",", $ROWS_visited_sites_catz));
				$ROWS_visited_sites_catz=array();
			}
				
			stats_admin_events(2,"$D/$num_rows rows" ,null,__FILE__,__LINE__);
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			@unlink($MinutesFile);
			@file_put_contents($MinutesFile, time());
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "update $domain width ". strlen($whoisdatas) ." bytes\n";}
		
		$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE familysite='$domain'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	}
	
	
	
	if($c>0){
		$unix=new unix();
		$took=$unix->distanceOfTimeInWords($t1,time());
		stats_admin_events(2,"WHOIS: $c visisted websites informations updated $f failed, $took",null,__FILE__,__LINE__,"visited");
		
	}
	
	
}

function visited_sites(){
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	if(!$GLOBALS["FORCE"]){
		$timeOfFile=$unix->file_time_min($timefile);
		if($timeOfFile<240){
			if($GLOBALS["VERBOSE"]){echo "{$timeOfFile}Mn,require 240Mn\n";}
			return;
		}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	squid_status_sub_percentage("Running query",5);
	
	$t1=time();	
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("visited_sites_catz")){$GLOBALS["Q"]->CheckTables();}
	
	squid_status_sub_percentage();
	
	$sql="SELECT sitename,country,category FROM visited_sites";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);
	
	stats_admin_events(2,"visited_sites $num_rows items" ,null,__FILE__,__LINE__);
	
	if($num_rows==0){
		squid_status_sub_percentage("Done",100);
		if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "$num_rows entries... in ". __FUNCTION__." ".__LINE__."\n";}
	
	
	squid_status_sub_percentage("$num_rows entries... in",10);
	
	$ROWS_visited_sites_catz=array();
	$ROWS_visited_sites_prefix="INSERT IGNORE INTO visited_sites_catz (`zmd5`,`category`,`familysite`) VALUES ";
	$z=0;
	
	$MinutesFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".minutes";
	@unlink($MinutesFile);
	@file_put_contents($MinutesFile, time());
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$z++;
		$perc=round($z/$num_rows)*100;
		$country=null;
		$FamilySite_update=null;
		$array=_visited_sites_calculate($ligne["sitename"]);
		if(!is_array($array)){continue;}
		if(trim($ligne["country"]==null)){$array_country=GeoIP($ligne["sitename"]);}
		if(isset($array_country)){if(isset($array_country[0])){$country=$array_country[0];}}
		if($country<>null){$country=",country='".addslashes($country)."'";;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["sitename"]} {$array[0]} hits, {$array[1]} size Country '$country' on {$array[2]} tables\n";}
		
		$TimeLoop=$unix->file_time_min($MinutesFile);
		if($TimeLoop>4){
			if(count($ROWS_visited_sites_prefix)>0){
				$GLOBALS["Q"]->QUERY_SQL($ROWS_visited_sites_prefix.@implode(",", $ROWS_visited_sites_catz));
				$ROWS_visited_sites_catz=array();
			}
			
			stats_admin_events(2,"$perc% $z/$num_rows rows" ,null,__FILE__,__LINE__);
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			@unlink($MinutesFile);
			@file_put_contents($MinutesFile, time());
		}
		
		
		
		$categories=$GLOBALS["Q"]->GET_CATEGORIES($ligne["sitename"],true);
		
		if($categories<>null){
			if($GLOBALS["VERBOSE"]){echo "UPDATE {$ligne["sitename"]} ->$categories\n";}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET category='$categories' WHERE sitename='{$ligne["sitename"]}'");
			if(!$GLOBALS["Q"]->ok){
			if($GLOBALS["VERBOSE"]){echo "UPDATE {$GLOBALS["Q"]->mysql_error}\n";}
			}
		}
		squid_status_sub_percentage("visited_sites();$z/$num_rows entries... in",$perc);
		$FamilySite=$GLOBALS["Q"]->GetFamilySites($ligne["sitename"]);
		if($GLOBALS["VERBOSE"]){echo "{$ligne["sitename"]} categories = $categories\n";} 
		thumbnail_site($ligne["sitename"]);
		if($FamilySite<>null){
			$FamilySite_update=",familysite='$FamilySite'";
			if(strpos(" $categories", ",")>0){
				$bb=explode(",", $categories);
				while (list ($hh, $ii) = each ($bb) ){
					if(trim($ii)==null){continue;}
					$mmd5=md5("$FamilySite$ii");
					$ROWS_visited_sites_catz[]="('$mmd5','$FamilySite','$ii')";
				}
			}else{
				$mmd5=md5("$FamilySite$categories");
				$ROWS_visited_sites_catz[]="('$mmd5','$FamilySite','$categories')";
			}
			
		 if(count($ROWS_visited_sites_catz)>1500){
		 	
		 	if($GLOBALS["VERBOSE"]){echo "visited_sites_catz:: 1500\n";}
		 	$GLOBALS["Q"]->QUERY_SQL($ROWS_visited_sites_prefix.@implode(",", $ROWS_visited_sites_catz));
		 	$ROWS_visited_sites_catz=array();
		 }	
		}
		
		$categories=addslashes($categories);
		$sql="UPDATE visited_sites SET HitsNumber='{$array[0]}',Querysize='{$array[1]}'$country,category='$categories'$FamilySite_update WHERE sitename='{$ligne["sitename"]}'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		
			
			
	}
	
	if(count($ROWS_visited_sites_catz)>0){
		$GLOBALS["Q"]->QUERY_SQL($ROWS_visited_sites_prefix.@implode(",", $ROWS_visited_sites_catz));
		$ROWS_visited_sites_catz=array();
	}		
		
	$took=$unix->distanceOfTimeInWords($t1,time());
	ufdbguard_admin_events("Scanned $num_rows visisted websites $took",__FUNCTION__,__FILE__,__LINE__,"visited");
	visited_sites_whois();
	WeekDaysNums();
}

function _visited_sites_calculate($sitename){
	
		if(!isset($GLOBALS["HOURS_TABLES"])){
			$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_hour'";
			$results=$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
			if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$GLOBALS["HOURS_TABLES"][$ligne["c"]]=$ligne["c"];
			}
			
		}
			
		$size=0;
		$hits=0;
		reset($GLOBALS["HOURS_TABLES"]);
		while (list ($num, $table) = each ($GLOBALS["HOURS_TABLES"]) ){
			$ligne2=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(size) AS tsize,SUM(hits) AS thits FROM $table WHERE sitename='$sitename'"));
			$size=$size+$ligne2["tsize"];
			$hits=$hits+$ligne2["thits"];
		}
		
		return array($hits,$size,count($GLOBALS["HOURS_TABLES"]));			
}
function ifMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	if($users->PROXYTINY_APPLIANCE){$update=false;}
	return $update;
}

function export_last_websites(){
	$q=new mysql_squid_builder();
	$categories=$q->LIST_TABLES_CATEGORIES();
	$prefix="INSERT IGNORE INTO categorize (zmd5,pattern,zdate,uuid,category) VALUES ";
	while (list ($num, $table) = each ($categories) ){
		$sql="SELECT * FROM $table WHERE enabled=1 ORDER BY zDate DESC LIMIT 0,1000";
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["pattern"])==null){
				$q->QUERY_SQL("DELETE FROM $table WHERE zmd5='{$ligne["zmd5"]}'");
				writelogs_squid("{$ligne["zmd5"]} has no website.\nIt has been deleted from table $table",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			
			
			$f[]="('{$ligne["zmd5"]}','{$ligne["pattern"]}','{$ligne["zDate"]}','{$ligne["uuid"]}','{$ligne["category"]}')";
			
		}
		
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
		}
		
		
	}
	
	
	
}

function youtube_uid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.youtube_uid.php --schedule-id={$GLOBALS["SCHEDULE_ID"]}");
	
}

function block_days($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$GLOBALS["Q"]->CheckTables();
	$sql="SELECT zDate FROM `tables_day` WHERE blocks=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zDate=$ligne["zDate"];
		$date=$ligne["zDate"]." 00:00:00";
		$time=strtotime($date);
		$TableSource=date('Ymd',$time)."_blocked";
		$TableDest=date('Ym',$time)."_blocked_days";
		if(!$GLOBALS["Q"]->TABLE_EXISTS("$TableSource")){
			writelogs("Checking table $TableSource does not exists",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET blocks=1 WHERE zDate='$zDate'");
			continue;
		}
		writelogs("Checking table $TableSource for $zDate -> $TableDest",__FUNCTION__,__FILE__,__LINE__);
		if(block_days_perform($TableSource,$TableDest)){
			$sql="SELECT SUM(hits) as tcount FROM $TableDest WHERE zDate='$date'";
			$ligne2=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
			if(!$GLOBALS["Q"]->ok){
				writelogs("$sql failed -> $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			
			$count=$ligne2["tcount"];
			if(!is_numeric($count)){$count=0;}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET blocks=1,totalBlocked=$count WHERE zDate='$zDate'");			
		}
	}
	
	thumbnail_query();
	
}

function block_days_perform($TableSource,$TableDest){
	
	$sql="SELECT COUNT(ID) as hits, DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,client,website,category,rulename,public_ip FROM $TableSource GROUP BY zDate,client,website,category,rulename,public_ip ORDER BY zDate";
	$prefix="INSERT IGNORE INTO $TableDest (zmd5,hits,zDate,client,website,category,rulename,public_ip) VALUES ";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tt=array();
		while (list ($a, $b) = each ($ligne) ){$tt[]=$b;}
		$zmd5=md5(@implode("", $tt));
		$f[]="('$zmd5','{$ligne["hits"]}','{$ligne["zDate"]}','{$ligne["client"]}','{$ligne["website"]}','{$ligne["category"]}','{$ligne["rulename"]}','{$ligne["public_ip"]}')";
		if(count($f)>500){
			writelogs("$TableDest -> 500 items",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){echo $GLOBALS["Q"]->mysql_error."\n";return;}
		}
	}
	
	if(count($f)>0){
			writelogs("$TableDest -> ".count($f)." items",__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){echo $GLOBALS["Q"]->mysql_error."\n";return;}
		}


	
return true;
	
}






function macuid(){
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$unix=new unix();
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["MAC"]} = {$ligne["uid"]}\n";}
		$array[$ligne["MAC"]]=$ligne["uid"];
	}	
	
	$sql="SELECT * FROM webfilters_ipaddr WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$array2[$ligne["ipaddr"]]=$ligne["uid"];
	}	
	
	
	$tablesBrutes=$q->LIST_TABLES_HOURS();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
		}
		
		reset($array2);
		while (list ($ipaddr, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE client='$ipaddr' AND LENGTH(uid)=0");
		}		
		
		
	}
	
	
	
	$tablesBrutes=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			if(IsCompressed($tablename)){Uncompress($tablename);}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
			
		}
		
		reset($array2);
		while (list ($ipaddr, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE CLIENT='$ipaddr' AND LENGTH(uid)=0");
		}		
		
		
	}
	
	
	
	$tablesBrutes=$q->LIST_TABLES_BLOCKED_DAY();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			if(IsCompressed($tablename)){Uncompress($tablename);}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
				
		}
	}	
	$tablesBrutes=$q->LIST_TABLES_BLOCKED_WEEK();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			if(IsCompressed($tablename)){Uncompress($tablename);}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
	
		}
	}	
	$tablesBrutes=$q->LIST_TABLES_YOUTUBE_DAYS();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			if(IsCompressed($tablename)){Uncompress($tablename);}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
	
		}
		reset($array2);
		while (list ($ipaddr, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE ipaddr='$ipaddr' AND LENGTH(uid)=0");
		}
		
		
		
	}	
	$tablesBrutes=$q->LIST_TABLES_YOUTUBE_WEEK();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		reset($array);
		while (list ($mac, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			if(IsCompressed($tablename)){Uncompress($tablename);}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
	
		}
		
		reset($array2);
		while (list ($ipaddr, $uid) = each ($array) ){
			$uid=mysql_escape_string2($uid);
			if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
			$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE ipaddr='$ipaddr' AND LENGTH(uid)=0");
		}		
		
		
	}
	uid_resets();
	
			
}

function uid_resets(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.members_uid.php --reset >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.websites_uid.php --reset >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.blocked_uid.php --reset >/dev/null 2>&1 &");
	
}









function week_uris($asPid=false){
	
	if(!ifMustBeExecuted()){
		ufdbguard_admin_events("Not necessary to execute this task...",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		$unix=new unix();
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("$oldpid already running, aborting",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
		@file_put_contents($pidfile, getmypid());
	}

	visited_websites_by_day(true);
	if($GLOBALS["VERBOSE"]){echo "Search tables sources in tables_day\n";}
	$sql="SELECT tablename,DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesource, 
	DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) AS tweek, YEAR( zDate ) AS tyear 
	FROM tables_day WHERE weekdone =0 AND zDate < DATE_SUB( NOW( ) , INTERVAL 1 DAY ) ORDER BY zDate";
	
	
	
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	
	$unix=new unix();
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "Search tables sources in tables_day:: ".mysql_num_rows($results)." rows\n";}
	if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return false;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_week";
		if($GLOBALS["VERBOSE"]){echo "******\n\nWeek table ->`$week_table`\n\n******\n";}
		if(!$GLOBALS["Q"]->CreateWeekTable($week_table)){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$week_table` (CREATE)",__FUNCTION__,__FILE__,__LINE__,"stats");continue;}
		$DayNumber=$ligne["DayNumber"];
		$tablesource="{$ligne["tablesource"]}_hour";
			if(!$GLOBALS["Q"]->TABLE_EXISTS($tablesource)){
				if($GLOBALS["Q"]->TABLE_EXISTS("dansguardian_events_{$ligne["tablesource"]}")){
					$next_table=$tablesource;
					 ufdbguard_admin_events("Create lost day table $tablesource from dansguardian_events_{$ligne["tablesource"]}",__FUNCTION__,__FILE__,__LINE__,"stats");
					
				}else{
					ufdbguard_admin_events("Fatal, lost day table $tablesource and lost working table dansguardian_events_{$ligne["tablesource"]}, skipping",__FUNCTION__,__FILE__,__LINE__,"stats");
					continue;
				}	
			}
		if($GLOBALS["VERBOSE"]){echo "_week_uris_perform($tablesource,$week_table,$DayNumber)\n";}
		$t=time();
		if(_week_uris_perform($tablesource,$week_table,$DayNumber)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET weekdone=1 WHERE tablename='{$ligne["tablename"]}'");
			$took=$unix->distanceOfTimeInWords($t,time(),true);
			writelogs_squid("Success update day Number $DayNumber from $tablesource to $week_table in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
		}
		
	}
	members_central();
	WeekDaysNums();
	youtube_week();
	
	
}

function _week_uris_perform($tablesource,$week_table,$DAYOFWEEK){
	$GLOBALS["Q"]->CreateWeekTable($week_table);
	$sql="SELECT SUM( size ) as size, SUM( hits ) as hits,sitename, familysite, client, uid, category, cached, MAC, hostname,account
	FROM $tablesource
	GROUP BY sitename, familysite, client, uid, category, cached, MAC, hostname,account";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: on source table `$tablesource` {$GLOBALS["Q"]->mysql_error} ",__FUNCTION__,__FILE__,__LINE__,"stats");return false;}
	$f=array();
	$source_events=$GLOBALS["Q"]->COUNT_ROWS($tablesource);
	
	if($source_events==0){
		preg_match("#^([0-9]+)_hour#", $tablesource,$re);
		$dansguardian_events_table="dansguardian_events_{$re[1]}";
		$dansguardian_events_rows=$GLOBALS["Q"]->COUNT_ROWS($dansguardian_events_table);
		if($dansguardian_events_rows>0){
			continue;
			
		}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "$tablesource:: store ".mysql_num_rows($results)." rows / $source_events events source-table: $dansguardian_events_table ( $dansguardian_events_rows events )\n";}
	$prefix="INSERT IGNORE INTO $week_table (zMD5,sitename,familysite,client,hostname,day,MAC,size,hits,uid,category,cached,account) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=addslashes($value);$zmd[]=$value;}
		$zMD5=md5(@implode("",$zmd));
		$f[]="('$zMD5','{$ligne["sitename"]}','{$ligne["familysite"]}','{$ligne["client"]}','{$ligne["hostname"]}','$DAYOFWEEK','{$ligne["MAC"]}','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["uid"]}','{$ligne["category"]}','{$ligne["cached"]}','{$ligne["account"]}')";
		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: on destination `$week_table` {$GLOBALS["Q"]->mysql_error} ",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}			
	}
	
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "$week_table:: Added ". count($f) ." events\n";}
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: on destination `$week_table` {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}
	
	$sql="SELECT uid,MAC FROM webfilters_nodes";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strlen($ligne["uid"])>1){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $week_table SET uid='{$ligne["uid"]}' WHERE MAC='{$ligne["MAC"]}' AND LENGTH(uid)<2");
		}
	}	
	
	return true;	
	
}

function youtube_week($asPid=false){
	if(isset($GLOBALS["youtube_week_executed"])){return;}
	$GLOBALS["youtube_week_executed"]=true;
	$unix=new unix();
	

	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("$oldpid already running, aborting",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}

	$GLOBALS["Q"]->CheckTables();
	$sql="SELECT YEAR(zDate) as year,zDate,WeekDay,WeekNum,DATE_FORMAT(zDate,'%Y%m%d') as prefix FROM 
	tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND YEAR(zDate)>1970 AND youtube_week=0";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	$q=new mysql_squid_builder();
	
	$c=0;
	$FailedTables=0;
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["WeekNum"]==0){continue;}
		$tableweek="youtubeweek_{$ligne["year"]}{$ligne["WeekNum"]}";
		if(!$GLOBALS["Q"]->TABLE_EXISTS($tableweek)){
			_youtube_week($ligne["year"],$ligne["WeekNum"]);
		}
	}

	$sql="SELECT WEEK(NOW()) as `WeekNum`, YEAR(NOW()) as `year`";
	$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	_youtube_week($ligne["year"],$ligne["WeekNum"]);
	
	
}

function _youtube_week($year,$WeekNum){
	if(isset($GLOBALS["ALREADYyoutube_week$year$WeekNum"])){return;}
	$tableweek="youtubeweek_{$year}{$WeekNum}";
	$GLOBALS["ALREADYyoutube_week$year$WeekNum"]=true;
	$sql="SELECT tablename,YEAR(zDate) as year,zDate,WeekDay,WeekNum,DATE_FORMAT(zDate,'%Y%m%d') as prefix FROM tables_day 
	WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) AND WeekNum=$WeekNum AND YEAR(zDate)=$year AND youtube_week=0";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	
	
	$c=0;
	$FailedTables=0;
	if($GLOBALS["VERBOSE"]){echo "youtubeweek_{$year}{$WeekNum} -> ".mysql_num_rows($results)." rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$tablesource="youtubeday_{$ligne["prefix"]}";
		if(!$GLOBALS["Q"]->TABLE_EXISTS($tablesource)){
			if($GLOBALS["VERBOSE"]){echo "$tablesource -> No such table\n";}
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET youtube_week=1 WHERE tablename='{$ligne["tablename"]}'");
			continue;}
		if(!$GLOBALS["Q"]->createWeekYoutubeTable($tableweek)){
			if($GLOBALS["VERBOSE"]){echo "$tableweek -> Failed to create...\n";}
			return;}
		if($GLOBALS["VERBOSE"]){echo "$tablesource -> $tableweek -> _youtube_week_perform()\n";}
		if(_youtube_week_perform($tableweek,$tablesource)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET youtube_week=1 WHERE tablename='{$ligne["tablename"]}'");
		}
		
	}
}

function _youtube_week_perform($tableweek,$tablesource){
	
	$sql="SELECT WEEKDAY(zDate) as `day`,ipaddr,hostname,uid,MAC,account,youtubeid,SUM(hits) as hits FROM $tablesource GROUP BY `day`,ipaddr,hostname,uid,MAC,account,youtubeid";
	
	$prefix="INSERT IGNORE INTO `$tableweek`(zmd5,`day`,ipaddr,hostname,uid,MAC,account,youtubeid,hits) VALUES ";
	$f=array();
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=md5(serialize($ligne));
		$day=$ligne["day"];
		$hits=$ligne["hits"];
		$ipaddr=addslashes($ligne["ipaddr"]);
		$hostname=addslashes($ligne["hostname"]);
		$MAC=addslashes($ligne["MAC"]);
		$uid=addslashes($ligne["uid"]);
		$account=addslashes($ligne["account"]);
		$youtubeid=addslashes($ligne["youtubeid"]);
		$f[]="('$zmd5','$day','$ipaddr','$hostname','$uid','$MAC','$account','$youtubeid','$hits')";
		
	}	
	
	if($GLOBALS["VERBOSE"]){echo "$tablesource -> $tableweek -> ".count($f)." rows\n";}
	if(count($f)>0){
		
		$sql="$prefix".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){
			ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tableweek`",__FUNCTION__,__FILE__,__LINE__,"stats");
			return false;
		}
	}
	
	return true;
	
}




function WeekDaysNums(){
	if(isset($GLOBALS["ALREADYWeekDaysNums"])){return;}
	$GLOBALS["ALREADYWeekDaysNums"]=true;
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) as WeekNumber FROM tables_day WHERE WeekNum=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("UPDATE tables_day SET WeekDay={$ligne["DayNumber"]},WeekNum={$ligne["WeekNumber"]}  WHERE tablename='{$ligne["tablename"]}'");
		
	}
	
	
}



function defragment_category_tables(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timeF="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	$timeMin=$unix->file_time_min($timeF);
	if($timeMin<4320){return;}
	
	@unlink($timeF);
	@file_put_contents($timeF, time());
	
	$q=new mysql_squid_builder();
	
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $none) = each ($tables) ){
		if(preg_match("bak$", $table)){
			
		}
		
	}
	rangetables();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $none) = each ($tables) ){
		if($GLOBALS["VERBOSE"]){echo "Check $table\n";}
		$sql="SHOW KEYS FROM $table WHERE Key_name='pattern'";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne2["Non_unique"]==0){
			if($GLOBALS["VERBOSE"]){echo "$table already checked\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "Migrate $table\n";}
		if(!defragment_category_table($table)){return;}
	}
	
	rangetables();
	
}

function defragment_category_table($tablename){
	if(preg_match("#(.+?)bak$#", $tablename,$re)){$oldtable=$tablename;$tablename=$re[1];}else{$oldtable="{$tablename}bak";}
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($oldtable)){
		$sql="RENAME TABLE `squidlogs`.`$tablename` TO `squidlogs`.`$oldtable`;";
		$q->QUERY_SQL($sql);
	}
	
	
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error} on `$tablename`",__FUNCTION__,__FILE__,__LINE__,"defrag");return;}
	$tablename=strtolower($tablename);
	$q->CreateCategoryTable(null,$tablename);
	$f=array();
	
	$countTotal=$q->COUNT_ROWS($oldtable);
	
	$sql="INSERT IGNORE INTO $tablename (`zmd5`,`zDate`,`category`,`pattern`,`enabled`,`uuid`,`sended`) 
	SELECT zmd5,zDate,category,pattern,enabled,uuid,sended FROM $oldtable";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return false;}
	$q->QUERY_SQL("DROP TABLE $oldtable");
	return true;

}

function defragment_category_table_sort($numstart,$tablename,$oldtable){
	$q=new mysql_squid_builder();
	$countTotal=$q->COUNT_ROWS($oldtable);
	$sql="SELECT * FROM $oldtable LIMIT $numstart,5000";
	$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`zDate`,`category`,`pattern`,`enabled`,`uuid`,`sended`) VALUES ";
	$results=$q->QUERY_SQL($sql);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$f[]="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["enabled"]}','{$ligne["uuid"]}','{$ligne["sended"]}')";
		if(count($f)>1000){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			$c=$c+count($f);
			if($GLOBALS["VERBOSE"]){echo "$c/$countTotal {$mem}MB\n";}
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error} on `$tablename`",__FUNCTION__,__FILE__,__LINE__,"defrag");return;}
			$f=null;
		}
	}	
	
	if(count($f)>0){
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error} on `$tablename`",__FUNCTION__,__FILE__,__LINE__,"defrag");return;}
			$f=null;
		}
	
	return true;
}

function rangetables(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $none) = each ($tables) ){
		$sql="SELECT category FROM $table GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if(mysql_numrows($results)==1){continue;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			if(trim($ligne["category"])==null){continue;}
			$sourceCategory=$ligne["category"];
			if($ligne["category"]=="category_publicite"){$ligne["category"]="publicite";}
			if($ligne["category"]=="category_automobile_"){$ligne["category"]="automobile/cars";}
			if($ligne["category"]=="category_finance_oth"){$ligne["category"]="finance/other";}
			if($ligne["category"]=="category_recreation_"){$ligne["category"]="recreation/travel";} 	
			
			echo "Found category `{$ligne["category"]}`\n";
			$destTable="category_". $q->category_transform_name($ligne["category"]);
			$destTable=str_replace("category_category_","category_",$destTable);
			
			if(trim($destTable)==trim($table)){continue;}
			echo "Move from $table: Category {$ligne["category"]} to table $destTable\n";
			$sql="INSERT IGNORE INTO $destTable (`zmd5`,`zDate`,`category`,`pattern`,`enabled`,`uuid`,`sended`) 
			SELECT zmd5,zDate,category,pattern,enabled,uuid,sended FROM $table WHERE category='$sourceCategory'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";continue;}			
			$q->QUERY_SQL("DELETE FROM $table WHERE category='$sourceCategory'");
		}
	}
		
	
	
}
function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}

function optimize_tables(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timeF="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){
		ufdbguard_admin_events("Fatal Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"maintenance");
		echo "Already executed pid $oldpid\n";}
		return;
	}
	
	$mypid=getmypid();
	if($unix->file_time_min($timeF)<60){
		ufdbguard_admin_events("Fatal 20 mn minimal time (current ".$unix->file_time_min($timeF)."Mn)",__FUNCTION__,__FILE__,__LINE__,"maintenance");
		return;
	}
	
	@file_put_contents($pidfile,$mypid);
	@file_put_contents($timeF,time());			
	
	if(!$q->TABLE_EXISTS("work_optimize")){$q->CheckTables();}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("Fatal $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"maintenance");
		return;
	}
	$t=array();
	ufdbguard_admin_events("Starting optimize tables",__FUNCTION__,__FILE__,__LINE__,"maintenance");
	$prefix="INSERT IGNORE INTO work_optimize (`table_name`) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["c"]=="category_"){$this->QUERY_SQL("DROP TABLE `category_`");continue;}
			if(trim($ligne["c"])==null){continue;}
			$t[]="('{$ligne["c"]}')";
		}	
	

	if(count($t)>0){
		$q->QUERY_SQL($prefix.@implode(",", $t));
	}

	$t=time();	
	$sql="SELECT table_name FROM work_optimize WHERE `job`=0";
	$results=$q->QUERY_SQL($sql);
	if(mysql_numrows($results)>0){
		$c=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$c++;	
			if($GLOBALS["VERBOSE"]){echo "OPTIMIZE TABLE `{$ligne["table_name"]}`\n";}
			ufdbguard_admin_events("OPTIMIZE TABLE `{$ligne["table_name"]}`",__FUNCTION__,__FILE__,__LINE__,"maintenance");
			$q->QUERY_SQL("OPTIMIZE TABLE `{$ligne["table_name"]}`");
			
			$q->QUERY_SQL("UPDATE work_optimize SET job=1 WHERE `table_name`='{$ligne["table_name"]}'");
			if(system_is_overloaded(basename(__FILE__))){
				$load=$GLOBALS["SYSTEM_INTERNAL_LOAD"];
				$took=$unix->distanceOfTimeInWords($t,time());
				ufdbguard_admin_events("Aborting task , overloaded system $load after $took ($c optimized tables)",__FUNCTION__,__FILE__,__LINE__,"maintenance");
				return;
			}
		}
		
		$took=$unix->distanceOfTimeInWords($t,time());
		ufdbguard_admin_events("Success optimize $c tables $took",__FUNCTION__,__FILE__,__LINE__,"maintenance");
		return;
	}
	
	$q->QUERY_SQL("UPDATE work_optimize SET `job`=0 WHERE `job`=1");	
	$sql="SELECT table_name FROM work_optimize WHERE `job`=0";
	$results=$q->QUERY_SQL($sql);
	if(mysql_numrows($results)>0){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$c++;	
			$q->QUERY_SQL("OPTIMIZE TABLE `{$ligne["table_name"]}`");
			$q->QUERY_SQL("UPDATE work_optimize SET job=1 WHERE `table_name`='{$ligne["table_name"]}'");
			if(system_is_overloaded(basename(__FILE__))){
				$load=$GLOBALS["SYSTEM_INTERNAL_LOAD"];
				$took=$unix->distanceOfTimeInWords($t,time());
				ufdbguard_admin_events("Aborting task , overloaded system $load after $took ($c optimized tables)",__FUNCTION__,__FILE__,__LINE__,"maintenance");
				return;
			}
		}
		
	}	
	
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Success optimize $c tables $took",__FUNCTION__,__FILE__,__LINE__,"maintenance");	
}

function webcacheperfs(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timeF="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){ufdbguard_admin_events("Fatal Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"stats");echo "Already executed pid $oldpid\n";}return;}	
	$prefix=date("Ymd");
	$currenttable="{$prefix}_hour";
	
	$q=new mysql_squid_builder();
	$currentHour=date("H");
	$currentDay=date('d');
	$currentMonth=date('m');
	$currentYear=date('Y');
	$hierT=strtotime($q->HIER()." 00:00:00");
	$hierDay=date("d",$hierT);
	$hierMonth=date("m",$hierT);
	$hierYear=date("Y",$hierT);
	$Hiertable=date("Ymd",$hierT)."_hour";
	
	
	$sql="SELECT SUM( size ) AS tsize, cached, `HOUR` FROM $currenttable GROUP BY cached, `HOUR` HAVING HOUR <$currentHour LIMIT 0 , 30";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	if(mysql_num_rows($results)==0){return;}	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$HASH[$ligne["HOUR"]][$ligne["cached"]]=$ligne["tsize"];
	}

	
	if(!$q->TABLE_EXISTS("webcacheperfs")){$q->CheckTables();}
	$prefix="INSERT IGNORE INTO webcacheperfs (zTimeInt,zHour,zDay,zMonth,zYear,notcached,cached,pourc) VALUES";
	
	while (list ($hour, $array) = each ($HASH) ){
		if(!isset($array[1])){$array[1]=0;}
		$zTimeInt="$hour$currentDay$currentMonth$currentYear";
		if($array[1]==0){
			ufdbguard_admin_events("Today {$hour}h 0% cache performance...",__FUNCTION__,__FILE__,__LINE__,"stats");
			$sqlQuery="$prefix ('$zTimeInt','$hour','$currentDay','$currentMonth','$currentYear','{$array[0]}',0,0)";
			$q->QUERY_SQL($sqlQuery);
			if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n$sqlQuery",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			continue;
		}
		
		$sum=$array[1]+$array[0];
		$p1=$array[1]/$sum;
		$p1=round($p1*100);
		ufdbguard_admin_events("Today {$hour}h $p1% cache performance...",__FUNCTION__,__FILE__,__LINE__,"stats");
		$sqlQuery="$prefix ($zTimeInt,$hour,$currentDay,$currentMonth,$currentYear,{$array[0]},{$array[1]},'$p1')";
		$q->QUERY_SQL($sqlQuery);
		if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n$sqlQuery",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}
	
	unset($HASH);
	
	
	$sql="SELECT SUM( size ) AS tsize, cached, `HOUR` FROM $Hiertable GROUP BY cached, `HOUR` LIMIT 0 , 30";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	if(mysql_num_rows($results)==0){return;}	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$HASH[$ligne["HOUR"]][$ligne["cached"]]=$ligne["tsize"];
	}
	
	while (list ($hour, $array) = each ($HASH) ){
		if(!isset($array[1])){$array[1]=0;}
		$zTimeInt="$hour$currentDay$currentMonth$currentYear";
		if($array[1]==0){
			$q->QUERY_SQL("$prefix ($zTimeInt,$hour,$hierDay,$hierMonth,$hierYear,{$array[0]},0,0)");
			if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			continue;
		}
		
		$sum=$array[1]+$array[0];
		$p1=$array[1]/$sum;
		$p1=round($p1*100);
		$q->QUERY_SQL("$prefix ($zTimeInt,$hour,$hierDay,$hierMonth,$hierYear,{$array[0]},{$array[1]},'$p1')");
		if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}	
	
}

function RepairCategoriesBases(){
	$q=new mysql_squid_builder();
	$tables_cats=$q->LIST_TABLES_CATEGORIES();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timeF="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid)){
		ufdbguard_admin_events("Fatal Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"stats");
		echo "Already executed pid $oldpid\n";
		return;
	}	
	if($unix->file_time_min($timeF)<10){
		$time=$unix->file_time_min($timeF);
		ufdbguard_admin_events("Need 10Mn, currently {$time}Mn",__FUNCTION__,__FILE__,__LINE__,"stats");
		@unlink($timeF);
		@file_put_contents($timeF, time());		
		return;
	}
	
	
	while (list ($num, $tablename) = each ($tables_cats) ){
		$newcat=$q->tablename_tocat($tablename);
		if($newcat==null){continue;}
		if($tablename=="category_english_malware"){continue;}
		if($tablename=="category_spywmare"){$newcat="spyware";}
		if($tablename=="category_forum"){$newcat="forums";}
		if($tablename=="category_housing_reale_state_"){$newcat="housing/reale_state_office";}
		if($tablename=="category_radio"){$newcat="webradio";}
		if($tablename=="category_radiotv"){$newcat="webradio";}
		if($tablename=="category_shopping"){continue;}
		echo "$tablename -> $newcat\n";
		ufdbguard_admin_events("define $tablename to $newcat done",__FUNCTION__,__FILE__,__LINE__,"maintenance");
		$q->QUERY_SQL("UPDATE `$tablename` SET category='$newcat' WHERE category='{$ligne["category"]}'");
		
	}
	
}

function thumbnail_parse(){
	$unix=new unix();
		
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$ttim=$unix->file_time_min($pidTime);
	if($ttim<5){
		writelogs("Aborting, neew to wait 5mn Current:{$ttim}mn",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	if(!is_dir("/var/log/artica-postfix/pagepeeker")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/pagepeeker")) {return;}
	$unix=new unix();
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$targetFile="/var/log/artica-postfix/pagepeeker/$filename";
		$minutes=$unix->file_time_min($targetFile);
		if($minutes>2880){@unlink($targetFile);continue;}
		$sitename=@file_get_contents($targetFile);
		thumbnail_site($sitename);
		@unlink($targetFile);
		$c++;
		if($c>100){
			$c=0;
			if(SquidStatisticsTasksOverTime()){ 
				stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); 
				return; 
			}
		}
	}

	
	thumbnail_parse_hours();
	
}

function thumbnail_parse_hours(){
	$unix=new unix();
	$dirs=$unix->dirdir("/var/log/artica-postfix/squid/queues");
	while (list ($directory,$array) = each ($dirs) ){
		$dirs2=$unix->dirdir($directory);if(count($dirs2)==0){@rmdir($directory);continue;}
		if(is_dir("$directory/PageKeeper")){thumbnail_parse_dir("$directory/PageKeeper");}
		
	}
	
	
	
	
}

function thumbnail_parse_dir($directory){
	$unix=new unix();
	$countDefile=$unix->COUNT_FILES($directory);
	$sock=new sockets();
	$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");
	if(!is_numeric($DisableLocalStatisticsTasks)){$DisableLocalStatisticsTasks=0;}
	events_tail("$directory  $countDefile files on Line: ".__LINE__);
	if($countDefile==0){
		events("thumbnail_parse_dir():: $directory:  remove... on Line: ".__LINE__);
		@rmdir($directory);
		return;
	}
	
	
	if (!$handle = opendir($directory)) {
		ufdbguard_admin_events("Fatal: $directory no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$c=0;
	$d=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$directory/$filename";
		$d++;
		if($DisableLocalStatisticsTasks==1){@unlink($targetFile);continue;}
		$arrayFile=unserialize(@file_get_contents($targetFile));
		if(!is_array($arrayFile)){@unlink($targetFile);continue;}
		while (list ($sitename,$RTTSIZEARRAY) = each ($arrayFile) ){
			thumbnail_site($sitename);
				
		}
		$c++;
		@unlink($targetFile);
		if($d>100){
			if(SquidStatisticsTasksOverTime()){stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			$d=0;
		}
	
	}
		
	
}



function thumbnail_query(){
	$sock=new sockets();
	$unix=new unix();
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	$EnablePagePeeker=$sock->GET_INFO("EnablePagePeeker");
	$PagePeekerMinRequests=$sock->GET_INFO("PagePeekerMinRequests");
	if(!is_numeric($EnablePagePeeker)){$EnablePagePeeker=1;}
	if(!is_numeric($PagePeekerMinRequests)){$PagePeekerMinRequests=20;}
	if($EnablePagePeeker==0){return;}
	$GLOBALS["THUMBNAILS_QUERY"]=0;
	$sql="SELECT sitename FROM visited_sites WHERE `HitsNumber`>$PagePeekerMinRequests AND thumbnail=0 ORDER BY HitsNumber DESC";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){if(strpos(" {$GLOBALS["Q"]->mysql_error}" , "Unknown column")>0){$GLOBALS["Q"]->CheckTables();$GLOBALS["Q"]->QUERY_SQL($sql);}}
	if(!$GLOBALS["Q"]->ok){	
		ufdbguard_admin_events("Query failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
		return;
	}
	$t2=time();
	if(mysql_num_rows($results)==0){return;}	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		if(thumbnail_site($ligne["sitename"])){
			$c++;
			if($GLOBALS["VERBOSE"]){echo "{$ligne["sitename"]} STAMP\n";}
				 $GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET thumbnail=1 WHERE `sitename`='{$ligne["sitename"]}'");
				if(!$GLOBALS["Q"]->ok){
	      		ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
	      		return false;
	     	 }			 
		}
	}	
	if($c>0){
		$took=$unix->distanceOfTimeInWords($t2,time());
		ufdbguard_admin_events("$c Web site thumbnails generated done and {$GLOBALS["THUMBNAILS_QUERY"]} queries to source web site performed...",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
	}
	
}

function thumbnail_site($sitename){
	$sitename=trim(strtolower($sitename));
	if(!isset($GLOBALS["THUMBNAILS_QUERY"])){$GLOBALS["THUMBNAILS_QUERY"]=0;}
	if(!isset($GLOBALS["ALREADY_THUMBS"])){$GLOBALS["ALREADY_THUMBS"]=array();}
	if(isset($GLOBALS["ALREADY_THUMBS"][$sitename])){return false;}
	$GLOBALS["ALREADY_THUMBS"][$sitename]=true;
	$tmpName="/tmp/$sitename.gif";
	$uriDomain="free.pagepeeker.com/v2";
	
	
	$familysite=$GLOBALS["Q"]->GetFamilySites($sitename);
	$md=md5($sitename);
	if(!isset($GLOBALS["PagePeekerID"])){
		$sock=new sockets();
		$GLOBALS["PagePeekerID"]=$sock->GET_INFO("PagePeekerID");
	}	
	
	
    $withid=1;
    if(strlen($GLOBALS["PagePeekerID"])==0){$withid=0;}	
	
	if(!$GLOBALS["FORCE"]){
		
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT filemd5 FROM webfilters_thumbnails WHERE zmd5='$md'"));
 		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"thumbnails");return;}	
		if(strlen($ligne["filemd5"])>10){
			 if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: $md Already done...\n";}
			return true;
		}
		
		if(trim($ligne["filemd5"])<>null){
			if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: $md filemd5=`{$ligne["filemd5"]}`\n";}
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: FORCED\n";}
	}
	
	
	

	
	if(strlen($GLOBALS["PagePeekerID"])>2){
		$uriDomain="custom.pagepeeker.com";
	}
	
	//http://custom.pagepeeker.com/thumbs.php 
	
	
	$curl=new ccurl("http://$uriDomain/thumbs.php?size=l&code={$GLOBALS["PagePeekerID"]}&refresh=&wait=1&url=$sitename");
	$GLOBALS["THUMBNAILS_QUERY"]=$GLOBALS["THUMBNAILS_QUERY"]+1;
	if(!$curl->GetFile("/tmp/$sitename.gif")){ return false; }
	
	 
	  $size = filesize($tmpName);
	  if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: $tmpName -> $size bytes\n";}
	  if($size==0){return;}
	  
	  $fp   = fopen($tmpName, 'r');
      $data = fread($fp, filesize($tmpName));
      $md5F=md5_file($tmpName);
      
      	@unlink(dirname(__FILE__)."/ressources/logs/thumbnails.$md.400.gif");
      	@unlink(dirname(__FILE__)."/ressources/logs/thumbnails.$md.64.gif");
      	@unlink(dirname(__FILE__)."/ressources/logs/thumbnails.$md.48.gif");
      	@unlink(dirname(__FILE__)."/ressources/logs/thumbnails.$md.32.gif");        
      
      if($md5F=="05fb7cf4798cec7a918dea6956b6a666"){if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: FALSE thumbnail unavailable\n";}return false;}
      if($md5F=="45ec39db5951e8e11be11f5148bd707a"){if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: FALSE thumbnail unavailable\n";}return false;}
      
      
      if($GLOBALS["FORCE"]){
      	$sql="DELETE FROM webfilters_thumbnails WHERE zmd5='$md'";
      	$GLOBALS["Q"]->QUERY_SQL($sql);
      }
      
    
      
      if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: Tmpfile.: $tmpName\n";}
	  if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: MD5 File: $md5F\n";}
	  if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: MD5 Site: $md\n";}
	  
	  $ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT zmd5 FROM webfilters_thumbnails WHERE filemd5='$md5F'"));
	  if($ligne["zmd5"]<>null){
	  	if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: Already set has [{$ligne["zmd5"]}]\n";}
	  	$mdPhant=md5($md.time().rand(200, 50000000));
	  	$sql="INSERT IGNORE INTO webfilters_thumbnails (zmd5,withid,sended,picture,savedate,filemd5,LinkTo) VALUES ('$md',$withid,0,'',NOW(),'$mdPhant','{$ligne["zmd5"]}')";
	  	$GLOBALS["Q"]->QUERY_SQL($sql);
		
	  	if($familysite<>$sitename){
	   			if(!isset($GLOBALS["ALREADY_THUMBS"][$familysite])){
	    			if($GLOBALS["VERBOSE"]){echo "[$familysite]::-> thumbnail_site('$familysite')\n";}
	  				thumbnail_site($familysite);
	   			}
	   		}
	   	 return true;
	  	}
	  	
 	
	  
	  
      $data = addslashes($data);

      fclose($fp);
      $sql="INSERT IGNORE INTO webfilters_thumbnails (zmd5,withid,sended,picture,savedate,filemd5)
      VALUES ('$md',$withid,0,'$data',NOW(),'$md5F')";
 	  
      $GLOBALS["Q"]->QUERY_SQL($sql);
      if(!$GLOBALS["Q"]->ok){
      	ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
      	writelogs("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__);
      	return false;
      }
      
      $dataSize=round(strlen($data)/1024,2);
      writelogs("$sitename thumbnail generated ($md5F) $dataSize Ko",__FUNCTION__,__FILE__,__LINE__);
      
      
	  if($GLOBALS["VERBOSE"]){echo "[$familysite]::$sitename: TRUE thumbnail saved\n";}
      @unlink($tmpName);
	  if($familysite<>$sitename){
	  	if(!isset($GLOBALS["ALREADY_THUMBS"][$familysite])){
	    	if($GLOBALS["VERBOSE"]){echo "[$familysite]::-> thumbnail_site('$familysite')\n";}
	  		thumbnail_site($familysite);
	  		}
	  	}
	
}

function thumbnail_alexa($path,$max){
	if(!is_numeric($max)){$max=500;}
	if(!is_file($path)){echo "$path no such file\n";return;}
	$f=file($path);
	$c=0;
	while (list ($index, $line) = each ($f) ){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\r\n", "", $line);
		$line=str_replace("\n", "", $line);
		if(trim($line)==null){continue;}
		if(trim($line)=="\r"){continue;}
		
		if($c>=$max){break;}
		$tt=explode(",", $line);
		if(trim($tt[1])==null){continue;}
		$c++;
		echo "$c/$max {$tt[1]}\n";
		thumbnail_site($tt[1]);
	}
	
}



function repair_hours($aspid=false){
	return;
	$unix=new unix();
	
	repair_week();
	week_uris();
	
	
}

function repair_week(){
	$q=new mysql_squid_builder();
	
	
	$sql="SELECT tablename,DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesource, 
	DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) AS tweek, 
	YEAR( zDate ) AS tyear FROM tables_day WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 1 DAY ) ORDER BY zDate";	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");
		return false;
	}
	
	if($GLOBALS["VERBOSE"]){echo "Search tables sources in tables_day:: ".mysql_num_rows($results)." rows\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_week";	
		if(isset($already[$week_table])){continue;}
		$tablesource="{$ligne["tablesource"]}_hour";
		$DayNumber=$ligne["DayNumber"];
		$weekNum=$ligne["tweek"];
		$already[$week_table]=true;
		if($GLOBALS["VERBOSE"]){echo "Week: $weekNum, Scanning table $week_table - source $tablesource Day number:$DayNumber \n";}
		if(!$q->TABLE_EXISTS($week_table)){
			if($GLOBALS["VERBOSE"]){echo "Week: $weekNum, $week_table !!! Not exists.....\n";}
			repair_week_refresh($ligne["tyear"],$ligne["tweek"]);
		}
		if($q->COUNT_ROWS($week_table)==0){
			if($GLOBALS["VERBOSE"]){echo "Week: $weekNum, $week_table !!! No row.....\n";}
			repair_week_refresh($ligne["tyear"],$ligne["tweek"]);
		}
		
	}
	
	members_central_grouped();
	
}

function UserSizeD(){
	$q=new mysql_squid_builder();
	
	
	$TABLES=$q->LIST_TABLES_HOURS();
	$Current=date("Ymd")."_hour";
	while (list($table_hour,$val)=each($TABLES)){
		if($table_hour==$Current){continue;}
		$CountDeSource=$q->COUNT_ROWS($table_hour);
		if($CountDeSource==0){continue;}
		$xtime=$q->TIME_FROM_HOUR_TABLE($table_hour);
		$DestTable="UserSizeD_".date("Ymd",$xtime);
		$zDate=date("Y-m-d",$xtime);
		if(!$q->TABLE_EXISTS($DestTable)){
			UserSizeD_REPAIR($zDate);
			continue;
		}
		
		if($q->COUNT_ROWS($DestTable)==0){
			UserSizeD_REPAIR($zDate);
			continue;
		}
		
	}
	
	
	
	
	
	
}




function repair_week_refresh($YEAR,$WEEK){
	$q=new mysql_squid_builder();
	$week_table="$YEAR{$WEEK}_week";	
	$sql="UPDATE tables_day SET weekdone=0 WHERE WEEK(zDate)=$WEEK AND YEAR( zDate )=$YEAR";
	writelogs_squid("Week number $WEEK is ordered to be re-calculated",__FUNCTION__,__FILE__,__LINE__,"stats");
	if(!$q->TABLE_EXISTS($week_table)){$q->QUERY_SQL("DROP TABLE $week_table");}
	$q->QUERY_SQL($sql);
	week_uris(false);
	
}

function dump_days(){
	$q=new mysql_squid_builder();
	
	echo "Today: " . date("Y-m-d")."\n";
	
	$LIST_TABLES_QUERIES=$q->LIST_TABLES_QUERIES();
	while (list ($index, $value) = each ($LIST_TABLES_QUERIES) ){
		echo "$value : `$index` \n";
		
	}
	
	$LIST_TABLES_HOURS=$q->LIST_TABLES_HOURS();
	while (list ($index, $value) = each ($LIST_TABLES_HOURS) ){
		echo "$value : `$index` \n";
		
	}	
	$sql="SELECT *,DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesource,DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) AS tweek, 
	YEAR( zDate ) AS tyear FROM tables_day ORDER BY zDate";	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");
		return false;
	}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		
		$tablename=$ligne["tablename"];
		$countRows=$q->COUNT_ROWS($tablename);
		$tablesource="{$ligne["tablesource"]}_hour";
		$DayNumber=$ligne["DayNumber"];
		$weekNum=$ligne["tweek"];
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_week";	
		echo "$tablename ( $countRows rows) ------------------------------------------------------------\n";
		$tablesource_exists="yes";
		$week_table_exists="yes";
		if(!$q->TABLE_EXISTS($tablesource)){$tablesource_exists="no";}
		if(!$q->TABLE_EXISTS($week_table)){$week_table_exists="no";}
		$week_table_rows=$q->COUNT_ROWS($week_table);
		echo "Table source $tablesource Exists:$tablesource_exists\n";
		echo "Table Week $week_table Exists:$week_table_exists ( $week_table_rows rows )\n";
		
		while (list ($index, $value) = each ($ligne) ){
			if(is_numeric($index)){continue;}
			//echo "$index: `$value`\n";
			
		}
		
	}
	
}

function writeDebugLogs($text,$function,$file,$line){
	$t=date("Y-m-d H:i:s");
	$pid=getmypid();
	$log="/var/log/$function.".basename($file).".log";
	writeOtherlogs($log,"$t [$pid] $function:: $text in line $line");
}




function youtube_events($text,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/youtube.inject.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid/".basename(__FILE__)."] $text [Line:$line]\n";
	@fwrite($h,$line);
	@fclose($h);

}

function summarize_days(){
	if(isset($GLOBALS["summarize_days_executed"])){return;}
	$GLOBALS["summarize_days_executed"]=true;
	

		
	$unix=new unix();
	if(!$GLOBALS["VERBOSE"]){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($unix->file_time_min($timefile)<480){return;}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$sql="SELECT tablename,zDate,DATE_FORMAT(zDate,'%Y%m%d') as dpref FROM tables_day";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$dpref=$ligne["dpref"];
		if($GLOBALS["VERBOSE"]){echo "Scanning: {$ligne["tablename"]}\n";}
		_summarize_days($dpref,$ligne["tablename"],$ligne["zDate"]);
	}
	writelogs_squid("Success Summarize ".mysql_num_rows($results)." day tables",__FUNCTION__,__FILE__,__LINE__,"stats");	
	
}

function _summarize_days($dpref,$tablename,$zDate=null){
		$q=new mysql_squid_builder();
		$tablename_members="{$dpref}_members";
		$tablename_blocked="{$dpref}_blocked";
		$tablename_youtube="youtubeday_{$dpref}";
		$tablename_hour="{$dpref}_hour";
		$BlockedCount=0;
		$MembersCount=0;
		$SumHits=0;
		$SumSize=0;
		$NotCategorized=0;
		$YouTubeHits=0;
		if($zDate==null){
			$Cyear=substr($dpref, 0,4);
			$CMonth=substr($dpref,4,2);
			$CDay=substr($dpref,6,2);
			$zDate="$Cyear-$CMonth-$CDay";
		}
		if(!$q->TABLE_EXISTS("$tablename_hour")){return;}
		$sql="SELECT COUNT(zMD5) as tcount FROM $tablename_hour WHERE LENGTH(category)=0";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$NotCategorized=$ligne2["tcount"];
	
		$sql="SELECT SUM(size) as tsize,SUM(hits) as thits FROM $tablename_hour";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));	
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$SumSize=$ligne2["tsize"];
		$SumHits=$ligne2["thits"];	

		if(!$q->TABLE_EXISTS("UserSizeD_{$dpref}")){
			if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> UserSizeD_{$dpref} no such table\n";}
			if($GLOBALS["VERBOSE"]){echo "<strong>Try to repair the table for `$zDate`</strong>\n";}
			UserSizeD_REPAIR($zDate);
		}
		$countrows=$q->COUNT_ROWS("UserSizeD_{$dpref}");
		if($countrows==0){
			if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> UserSizeD_{$dpref} no row\n";}
			if($GLOBALS["VERBOSE"]){echo "<strong>Try to repair the table for `$zDate`</strong>\n";}
			UserSizeD_REPAIR($zDate);
			
		}
		
		if($q->TABLE_EXISTS($tablename_members)){
			$MembersField=which_filter($tablename_members,true);
			if($GLOBALS["VERBOSE"]){echo "Table members Calculate Members by $MembersField\n";}
			$MembersCount=CalculateElements($tablename_members,$MembersField);
		}else{
			if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> $tablename_members no such table\n";}
		}
		
		if($q->TABLE_EXISTS($tablename_blocked)){$BlockedCount=$q->COUNT_ROWS($tablename_blocked);}	

		if($q->TABLE_EXISTS($tablename_youtube)){
			$sql="SELECT SUM( hits ) AS tcount FROM $tablename_youtube";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));	
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$YouTubeHits=$ligne2["tcount"];		
			
		}
		if(!is_numeric($SumSize)){$SumSize=0;}
		if(!is_numeric($SumHits)){$SumHits=0;}
		if(!is_numeric($BlockedCount)){$BlockedCount=0;}
		if(!is_numeric($MembersCount)){$MembersCount=0;}
		if(!is_numeric($NotCategorized)){$NotCategorized=0;}
		if(!is_numeric($YouTubeHits)){$YouTubeHits=0;}
		
		
		if($GLOBALS["VERBOSE"]){echo "totalBlocked=$BlockedCount,MembersCount=$MembersCount, requests=$SumHits, totalsize=$SumSize, not_categorized=$NotCategorized, YouTubeHits=$YouTubeHits\n";}
		
		$sql="UPDATE tables_day
		SET totalBlocked=$BlockedCount,
		MembersCount=$MembersCount,
		requests=$SumHits,
		totalsize=$SumSize,
		not_categorized=$NotCategorized,
		YouTubeHits=$YouTubeHits
		WHERE tablename='$tablename'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!: $q->mysql_error</strong>\n";} 
		}
		
		if($GLOBALS["VERBOSE"]){echo "<hr><strong style='font-size:16px'>Please Refresh this web page to update icons</strong><hr>\n";} 
	}
	
function UserSizeD_REPAIR($day){
	$time=strtotime("$day 00:00:00");
	$sourcetable=date("Ymd",$time)."_hour";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($sourcetable)){
		if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> $sourcetable no such table\n";}
		return;
	}
	
	$tablename="UserSizeD_".date("Ymd",$time);
	
	if(!$q->CreateUserSizeRTT_day($tablename)){
		
		if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:$tablename</strong> Query failed {$q->mysql_error}\n";}
		ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
	`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ";
	
	
	$sql="SELECT client,hostname,account,hour,MAC,SUM(size) as size,SUM(hits) as hits,uid FROM $sourcetable
	GROUP BY client,hostname,account,hour,MAC,uid";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> $q->mysql_error\n";}
		return;
	}	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> No row for $sourcetable\n";}
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$zMD5=md5(serialize($ligne));
		$uid=addslashes($ligne["uid"]);
		$hostname=addslashes($ligne["hostname"]);
		$f[]="('$zMD5','$uid','$day','{$ligne["client"]}','$hostname','{$ligne["account"]}','{$ligne["MAC"]}','','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["hour"]}')";
		if(count($f)>500){
			if($GLOBALS["VERBOSE"]){echo "<strong>Injecting 500 lines</strong>\n";}
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> $q->mysql_error\n";}
				return;
			}
			$f=array();
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "<strong>Fatal!:</strong> $q->mysql_error\n";}
			return;
		}
		$f=array();
	}
	if($GLOBALS["VERBOSE"]){echo "<strong>SUCCESS: $tablename</strong>\n";}
}

function CalculateElements($tablename,$groupby){
	$q=new mysql_squid_builder();
	$sql="SELECT $groupby FROM $tablename GROUP BY $groupby";
	$results=$q->QUERY_SQL($sql);
	return mysql_num_rows($results);
	
}
	
function which_filter($tablename,$return_fields=false){
	$ipfield="client";
	if(preg_match("#^UserSizeD_#", $tablename)){
		$ipfield="ipaddr";
	}
	
		$q=new mysql_squid_builder();
		$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
		$results=$q->QUERY_SQL($sql);
		$count=mysql_num_rows($results);
		if($count>2){
			if($GLOBALS["VERBOSE"]){echo "Number of members uid key for $count items\n";}
			if($return_fields){return "uid";}
			return $count;
		}
	
		$sql="SELECT MAC FROM `$tablename` GROUP BY MAC HAVING LENGTH(MAC)>0";
		$results=$q->QUERY_SQL($sql);
		$count=mysql_num_rows($results);
		if($count>2){
			if($GLOBALS["VERBOSE"]){echo "Number of members MAC key for $count items\n";}
			if($return_fields){return "MAC";}
			return $count;}
	
	
		$sql="SELECT COUNT(ipaddr) as tcount FROM $tablename WHERE LENGTH($ipfield)>0";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$count=mysql_num_rows($results);
		if($count>1){
			if($GLOBALS["VERBOSE"]){echo "Number of members ipaddr key for $count items\n";}
			if($return_fields){return "$ipfield";}
			return $count;}
	
		$sql="SELECT COUNT(hostname) as tcount FROM $tablename WHERE LENGTH(hostname)>0";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$count=mysql_num_rows($results);
		if($count>1){
			if($GLOBALS["VERBOSE"]){echo "Number of members hostname key for $count items\n";}
			if($return_fields){return "hostname";}
			return $count;}
	
	}





function IsCompressed($tablename){
	$q=new mysql_squid_builder();
	if($q->isTableCompressed($tablename)){
		if($GLOBALS["VERBOSE"]){echo "OK $tablename is compressed\n";}
		return true;
}
	
}

function Uncompress($tablename){
	if(isset($GLOBALS["Uncompress-$tablename"])){return;}
	$GLOBALS["Uncompress-$tablename"]=true;
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$myisamchk=$unix->find_program("myisamchk");
	$myisampack=$unix->find_program("myisampack");
	if($GLOBALS["VERBOSE"]){echo "$myisamchk --unpack $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI\n";}
	shell_exec("$myisamchk --unpack $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI");
	shell_exec("$myisamchk --safe-recover $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI");
	$q->QUERY_SQL("UNLOCK TABLES");
	$q->QUERY_SQL("FLUSH TABLE $tablename");
}
function CompressTable($tablename){
	$unix=new unix();
	$sock=new sockets();
	$myisamchk=$unix->find_program("myisamchk");
	$myisampack=$unix->find_program("myisampack");
	$q=new mysql_squid_builder();	
	$q->QUERY_SQL("OPTIMIZE TABLE $tablename");
	$q->QUERY_SQL("LOCK TABLE $tablename WRITE");
	$q->QUERY_SQL("FLUSH TABLE $tablename");
	if(!is_file("$q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI")){
		if($GLOBALS["VERBOSE"]){echo "Skip $q->MYSQL_DATA_DIR/$tablename.MYI (did not exists)\n";}
		$fL[]="Skip $q->MYSQL_DATA_DIR/$tablename.MYI (did not exists)";
		return;
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "$myisamchk -cFU $q->MYSQL_DATA_DIR/$tablename.MYI\n";}
	shell_exec("$myisamchk -cFU $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI");
	shell_exec("$myisampack -f $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI");
	shell_exec("$myisamchk -raqS $q->MYSQL_DATA_DIR/squidlogs/$tablename.MYI");
	$q->QUERY_SQL("FLUSH TABLE $tablename");
	$q->QUERY_SQL("UPDATE tables_day SET `compressed`=1 WHERE `tablename`='$tablename'");
}


?>