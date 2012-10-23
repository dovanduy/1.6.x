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
WriteMyLogs("commands= ".implode(" ",$argv),"MAIN",__FILE__,__LINE__);
$GLOBALS["Q"]=new mysql_squid_builder();
$unix=new unix();
$GLOBALS["CLASS_UNIX"]=$unix;
events("Params: " .@implode(" ",$argv));
$sock=new sockets();
if($argv[1]=='--clean-catz-cache'){$GLOBALS["Q"]->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");die();}

if(!is_dir("/var/log/artica-postfix/artica-squid-events")){
	@mkdir("/var/log/artica-postfix/artica-squid-events",644,true);
	die();
}
$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}

if($squidEnableRemoteStatistics==1){
		events("this server is not in charge of statistics...");
		die();
}



if($DisableArticaProxyStatistics==1){ufdbguard_admin_events("Statistics are disabled in this configuration (DisableArticaProxyStatistics)","MAIN",__FILE__,__LINE__);die();}


if(!ifMustBeExecuted()){
	WriteMyLogs("This server is not in charge of statistics","MAIN",__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "this server is not in charge of statistics (categories repositories or Statistics Appliance) ...\n";}
	events("this server is not in charge of statistics (categories repositories or Statistics Appliance) ...");
	die();
}

if($GLOBALS["VERBOSE"]){echo "LAUNCH: '{$argv[1]}'\n";}
if($argv[1]=='--repair-hours'){repair_hours();if($GLOBALS["VERBOSE"]){echo "END: '{$argv[1]}'\n";}exit;} # Recherche les tables squidhour_* et les réinjectes.
if($argv[1]=='--defrag'){defragment_category_tables();exit;}
if($argv[1]=='--defragtable'){defragment_category_table($argv[2]);exit;}
if($argv[1]=='--rangetables'){rangetables();exit;}
if($argv[1]=='--thumbs'){thumbnail_site($argv[2]);exit;}
if($argv[1]=='--thumbs-sites'){thumbnail_query();exit;}
if($argv[1]=='--thumbs-alexa'){thumbnail_alexa($argv[2],$argv[3]);exit;}
if($argv[1]=='--thumbs-parse'){thumbnail_parse();exit;}



if($argv[1]=='--users-size'){users_size_hour();die();}
if($argv[1]=='--scan-hours'){scan_hours();die();}
if($argv[1]=='--scan-months'){scan_months();die();}
if($argv[1]=='--tables-days'){table_days();die();}
if($argv[1]=='--tables-hours'){table_hours();die();}
if($argv[1]=='--block-days'){block_days();die();}
if($argv[1]=='--block-week'){week_uris_blocked();die();}
if($argv[1]=='--hours'){clients_hours();die();}
if($argv[1]=="--week"){week_uris(true);week_uris_blocked();die(true);}
if($argv[1]=="--not-cat"){not_categorized_day_scan();exit;}
if($argv[1]=="--nodes-scan"){nodes_scan();exit;}
if($argv[1]=='--flow-month'){flow_month();die();}
if($argv[1]=='--members'){members_hours();die();}
if($argv[1]=='--members-month'){members_month();die();}
if($argv[1]=='--parse-cacheperfs'){squid_cache_perfs();die();}
if($argv[1]=='--show-tables'){show_tables();die();}
if($argv[1]=='--tables'){$q=new mysql();$q->CheckTablesSquid();die();}
if($argv[1]=='--members-month-kill'){members_month_delete();exit;}
if($argv[1]=='--fix-tables'){$GLOBALS["Q"]->FixTables();exit;}
if($argv[1]=='--visited-sites'){not_categorized_day_scan();exit;}
if($argv[1]=='--visited-sites2'){visited_sites();exit;}
if($argv[1]=='--sync-categories'){sync_categories();exit;}
if($argv[1]=='--re-categorize'){re_categorize();exit;}
if($argv[1]=='--re-categorize-day'){recategorize_singleday($argv[2]);exit;}
if($argv[1]=='--re-categorize-week'){recategorize_singleday($argv[2]);exit;}
if($argv[1]=='--whois'){visited_sites_whois();exit;}
if($argv[1]=='--calculate-not-categorized'){_not_categorized_day_scan();exit;}
if($argv[1]=='--optimize'){optimize_tables();exit;}
if($argv[1]=='--webcacheperfs'){webcacheperfs();exit;}
if($argv[1]=='--visited-days'){visited_websites_by_day();exit;}
if($argv[1]=='--repair-categories'){RepairCategoriesBases();exit;}
if($argv[1]=='--members-central'){members_central();exit;}
if($argv[1]=='--repair-week'){repair_week();exit;}
if($argv[1]=='--dump-days'){dump_days();exit;}






if($argv[1]=='--re-re-categorize'){__re_categorize_subtables();exit;}
if($argv[1]=='--export-last-websites'){export_last_websites();exit;}



//visited_sites
if($GLOBALS["VERBOSE"]){echo "UNABLE TO UNDERSTAND: '{$argv[1]}'\n";}
ufdbguard_admin_events("ERROR: UNABLE TO UNDERSTAND: '{$argv[1]}'","MAIN",__FILE__,__LINE__,"ERROR");	



function nodes_scan(){
	if(!ifMustBeExecuted()){
		ufdbguard_admin_events("Not necessary to execute this task...",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$f=array();
	if(!$GLOBALS["Q"]->TABLE_EXISTS("webfilters_nodes")){$GLOBALS["Q"]->CheckTables();}
	if(!$GLOBALS["Q"]->TABLE_EXISTS("webfilters_nodes")){writelogs("Fatal, webfilters_nodes, nos such table",__FILE__,__FUNCTION__,__LINE__);return;}
	$sql="SELECT MAC FROM UserAutDB GROUP BY MAC";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$prefix="INSERT IGNORE INTO webfilters_nodes (`MAC`) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(trim($ligne["MAC"])==null){continue;}
		if(strpos($ligne["MAC"], ":")==0){continue;}
		$f[]="('{$ligne["MAC"]}')";
		
	}
	if(count($f)>0){$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));}
}


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
		
		if(systemMaxOverloaded()){
			$took=$unix->distanceOfTimeInWords($t,time());
			ufdbguard_admin_events("Fatal: VERY Overloaded system after $took execution time ->  aborting task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;	
		}		
		
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("$CATZP new categorized website task:finish ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --defrag schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
}


function re_categorize(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		ufdbguard_admin_events("Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"stats");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	not_categorized_day_scan();
	if(systemMaxOverloaded()){
		ufdbguard_admin_events("Fatal: VERY Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;	
	}		
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	$sock=new sockets();
	$RecategorizeSecondsToWaitOverload=$sock->GET_INFO("RecategorizeSecondsToWaitOverload");
	$RecategorizeMaxExecutionTime=$sock->GET_INFO("RecategorizeSecondsToWaitOverload");
	$RecategorizeProxyStats=$sock->GET_INFO("RecategorizeProxyStats");
	if(!is_numeric($RecategorizeProxyStats)){$RecategorizeProxyStats=1;}	
	if(!is_numeric($RecategorizeSecondsToWaitOverload)){$RecategorizeSecondsToWaitOverload=30;}
	if(!is_numeric($RecategorizeMaxExecutionTime)){$RecategorizeMaxExecutionTime=210;}
	if($RecategorizeProxyStats==0){
		ufdbguard_admin_events("RecategorizeProxyStats=0, aborting...",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	$t=time();
	$sql="SELECT * FROM visited_sites";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		if($website==null){continue;}
		$category=trim($GLOBALS["Q"]->GET_CATEGORIES($website,true));
		$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET category='$category' WHERE sitename='$website'");
		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: mysql error {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}	
		$c++;
		if($c>5000){
			$distanceInSeconds = round(abs(time() - $t));
	    	$distanceInMinutes = round($distanceInSeconds / 60);
	    	if($distanceInMinutes>$RecategorizeMaxExecutionTime){
	    		$took=$unix->distanceOfTimeInWords($t,time());
	    		ufdbguard_admin_events("Re-categorized websites task aborted (Max execution time {$RecategorizeMaxExecutionTime}Mn) ($took)",__FUNCTION__,__FILE__,__LINE__,"categorize");
	    		return;
	    	}
	    	$c=0;
		}
		
		
		
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("$num_rows re-categorized  websites in main table  ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
	__re_categorize_subtables($t);
	
}

function recategorize_singleday($day,$nopid=false,$tablename=null){
	$unix=new unix();
	if(!$nopid){
		
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	}
	$daySource=$day;
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	$time=strtotime("$day 00:00:00");
	$day=str_replace("-", "", $day);
	$table="{$day}_hour";
	$table_blocked="{$day}_blocked";
	$table_month=date("Ym",$time)."_day";
	$table_week=date("YW",$time)."_week";
	$table_week_blocked=date("YW",$time)."_blocked_week";
	if($GLOBALS["VERBOSE"]){echo "$daySource time: $time Table day=$table, table_blocked=$table_blocked, table_month=$table_month, table_week=$table_week\n";}
	$t=time();
	$f=0;
	if(!$GLOBALS["Q"]->TABLE_EXISTS($table)){echo $table . " no such table\n";return;}
	$sql="SELECT sitename,category FROM $table GROUP BY sitename,category HAVING LENGTH(category)=0";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table Query failed: `$sql` ({$GLOBALS["Q"]->mysql_error})",__FUNCTION__,__FILE__,__LINE__,"categorize");}
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS($table_month)){
			if(!$GLOBALS["Q"]->CreateMonthTable($table_month)){
				writelogs_squid("failed Create $table_month table {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");
			}
	}
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS($table_week)){
			if(!$GLOBALS["Q"]->CreateWeekTable($table_week)){
				writelogs_squid("failed Create $table_week table {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");
			}
	}	

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		$category=null;
		if(preg_match("#^www\.(.+)#", $website,$re)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $table SET sitename='{$re[1]}' WHERE sitename='$website'");
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_month SET sitename='{$re[1]}' WHERE sitename='$website'");
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_week SET sitename='{$re[1]}' WHERE sitename='$website'");
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_blocked SET website='{$re[1]}' WHERE sitename='$website'");
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_week_blocked SET website='{$re[1]}' WHERE sitename='$website'");
			$website=$re[1];
		}
		if($website==null){continue;}
		if(isset($GLOBALS[__FUNCTION__][$website])){$category=$GLOBALS[__FUNCTION__][$website];}
		if($category==null){	
			$category=$GLOBALS["Q"]->GET_CATEGORIES($website);
			if($GLOBALS["VERBOSE"]){echo "$day] $website = $category\n";}
		}
		$GLOBALS[__FUNCTION__][$website]=$category;
		if($category==null){continue;}		
		$f++;
		$GLOBALS["Q"]->QUERY_SQL("UPDATE $table SET category='$category' WHERE sitename='$website'");
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}

		$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_month SET category='$category' WHERE sitename='$website'");
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table_month failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}

		$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_week SET category='$category' WHERE sitename='$website'");
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table_week failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}

		$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_blocked SET category='$category' WHERE website='$website'");
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table_blocked failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}

		$GLOBALS["Q"]->QUERY_SQL("UPDATE $table_week_blocked SET category='$category' WHERE website='$website'");
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Re-categorized table $table_week_blocked failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");}		
		
	}
	
	if($tablename<>null){
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT COUNT(zMD5) as tcount FROM $table WHERE category=''"));
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET not_categorized={$ligne["tcount"]} WHERE tablename='$tablename'");
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	if($f>0){
		ufdbguard_admin_events("Re-categorized table $table with $f websites ($took)",__FUNCTION__,__FILE__,__LINE__,"statistics");
	}
	if($GLOBALS["VERBOSE"]){echo "recategorize_singleday($day) FINISH\n";}
}

function __re_categorize_subtables($oldT1=0){
	$unix=new unix();
	if(systemMaxOverloaded()){writelogs_squid("Fatal: VERY Overloaded system, return;",__FUNCTION__,__FILE__,__LINE__,"stats");return;	}	
	$sock=new sockets();
	$RecategorizeSecondsToWaitOverload=$sock->GET_INFO("RecategorizeSecondsToWaitOverload");
	$RecategorizeMaxExecutionTime=$sock->GET_INFO("RecategorizeSecondsToWaitOverload");
	if(!is_numeric($RecategorizeSecondsToWaitOverload)){$RecategorizeSecondsToWaitOverload=30;}
	if(!is_numeric($RecategorizeMaxExecutionTime)){$RecategorizeMaxExecutionTime=210;}
	if($oldT1>1){$t=$oldT1;}else{$t=time();}
	
	
	$tables_days=$GLOBALS["Q"]->LIST_TABLES_DAYS();
	$tables_hours=$GLOBALS["Q"]->LIST_TABLES_HOURS();
	$tables_week=$GLOBALS["Q"]->LIST_TABLES_WEEKS();
	$tables_blocked_week=$GLOBALS["Q"]->LIST_TABLES_WEEKS_BLOCKED();
	$tables_blocked_days=$GLOBALS["Q"]->LIST_TABLES_DAYS_BLOCKED();
	
	$sql="SELECT * FROM visited_sites";	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);	
	$CountUpdatedTables=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		$category=trim($ligne["category"]);
		if($website==null){continue;}
		if($category==null){continue;}
		reset($tables_days);
		reset($tables_hours);
		reset($tables_week);
		while (list ($num, $tablename) = each ($tables_days) ){
			$category=addslashes($category);
			$CountUpdatedTables++;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$website'");
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: mysql error on table $tablename {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}
		}
		
		while (list ($num, $tablename) = each ($tables_hours) ){
			$category=addslashes($category);
			$CountUpdatedTables++;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$website'");
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: mysql error on table $tablename {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}
		}
		
		while (list ($num, $tablename) = each ($tables_week) ){
			$category=addslashes($category);
			$CountUpdatedTables++;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$website'");
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: mysql error on table $tablename {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}
		}
		
		
		while (list ($num, $tablename) = each ($tables_blocked_days) ){
			$category=addslashes($category);
			$CountUpdatedTables++;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE website='$website'");
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: mysql error on table $tablename {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}
		}		

		while (list ($num, $tablename) = each ($tables_blocked_week) ){
			$category=addslashes($category);
			$CountUpdatedTables++;
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE website='$website'");
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: mysql error on table $tablename {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}
		}			

		if(system_is_overloaded(__FILE__)){writelogs_squid("Fatal: Overloaded system, sleeping $RecategorizeSecondsToWaitOverload secondes...",__FUNCTION__,__FILE__,__LINE__,"stats");sleep($RecategorizeSecondsToWaitOverload);}
		if(systemMaxOverloaded()){writelogs_squid("Fatal: VERY Overloaded system, return;",__FUNCTION__,__FILE__,__LINE__,"stats");return;	}
		
		$distanceInSeconds = round(abs(time() - $t));
	    $distanceInMinutes = round($distanceInSeconds / 60);
	    if($distanceInMinutes>$RecategorizeMaxExecutionTime){$took=$unix->distanceOfTimeInWords($t,time());writelogs_squid("Re-categorized websites task aborted (Max execution time {$RecategorizeMaxExecutionTime}Mn) ($took)",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}		
		
	}
	
	$took=$unix->distanceOfTimeInWords($t,time());
	writelogs_squid("$num_rows re-categorized  websites updated in `$CountUpdatedTables` MySQL tables ($took)",__FUNCTION__,__FILE__,__LINE__,"categorize");

}


function scan_hours($nopid=false){
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			writelogs_squid("Fatal, Overloaded system, aborting task",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/scan_hours.time";
	@file_put_contents($timefile, time());
	
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		$unix=new unix();
		if($unix->process_exists($oldpid)){
			writelogs_squid("Fatal, already executed $oldpid, aborting task",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$GLOBALS["Q"]->FixTables();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.fcron.php --squid-recategorize-task &");
	nodes_scan();
	table_hours();
	table_days();
	clients_hours(true);
	members_hours(true);
	week_uris();
	week_uris_blocked();
}

function scan_months(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($oldpid)){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	table_days();
	members_month(true);
	flow_month(true);
	block_days(true);	
	week_uris();
	week_uris_blocked();
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
		
		if(!$GLOBALS["Q"]->CreateVisitedDayTable($tableDest)){
			ufdbguard_admin_events("Fatal while creating  $tableDest table with error {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
		if(!$GLOBALS["Q"]->TABLE_EXISTS($tableSource)){
			ufdbguard_admin_events("Fatal while filling  $tableDest table $tableSource no such table",__FUNCTION__,__FILE__,__LINE__,"stats");
			continue;
		}
		$c++; 
		if(visited_websites_by_day_perform($tableSource,$tableDest)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET visited_day=1 WHERE tablename='{$ligne["tablename"]}'");
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}
		
		if(systemMaxOverloaded()){
			$took =$unix->distanceOfTimeInWords($t1,time());
			ufdbguard_admin_events("Fatal: Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task after $c subtasks ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time());
	ufdbguard_admin_events("Success update $countWorks in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
	members_central(true);
}

function visited_websites_by_day_perform($tableSource,$tableDest){
		$sql="SELECT SUM(size) as tsize,SUM(hits) as thits, sitename,familysite FROM $tableSource GROUP BY sitename,familysite";
		$results=$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$prefix="INSERT IGNORE INTO $tableDest (sitename,familysite,size,hits) VALUES ";
		$countWorks=mysql_num_rows($results);
		if($countWorks==0){return true;}
		$f=array();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]="('{$ligne["sitename"]}','{$ligne["familysite"]}','{$ligne["tsize"]}','{$ligne["thits"]}')";
			
			if(count($f)>1000){
				$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
				if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
				$f=array();
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
	table_days();
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
	
	table_days();
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

	$sql="SELECT hour FROM $nexttable ORDER BY hour DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	if(!is_numeric($ligne["hour"])){$ligne["hour"]=-1;}
	events_tail("processing  $tabledata Last hour >{$ligne["hour"]}h");
	$filter_hour_2=" AND HOUR>{$ligne["hour"]}";
	
	
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



function clients_hours($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);		
	}
	
	table_days();
	$currenttable="dansguardian_events_".date('Ymd');
	$next_table=date('Ymd')."_hour";
	_clients_hours_perfom($currenttable,$next_table);	
	
	
	$q=new mysql_squid_builder();
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as suffix,tablename FROM tables_day WHERE Hour=0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){events_tail("$q->mysql_error");return;}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$next_table=$ligne["suffix"]."_hour";
		if(!$q->CreateHourTable($next_table)){events_tail("Failed to create $next_table");return;}
		if(!_clients_hours_perfom($ligne["tablename"],$next_table)){events_tail("Failed to process {$ligne["tablename"]} to $next_table");return;}
	}
}




function _clients_hours_perfom($tabledata,$nexttable){
$filter_hour=null;	
$filter_hour_1=null;
$filter_hour_2=null;
$GLOBALS["Q"]->CreateHourTable($nexttable);
$todaytable=date('Ymd')."_hour";
$CloseTable=true;
$output_rows=false;


if($nexttable==$todaytable){
	$filter_hour_1="AND HOUR < HOUR( NOW())";
	$CloseTable=false;
}

$sql="SELECT hour FROM $nexttable ORDER BY hour DESC LIMIT 0,1";
$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
if(!is_numeric($ligne["hour"])){$ligne["hour"]=-1;}
events_tail("processing  $tabledata Last hour >{$ligne["hour"]}h");
$filter_hour_2=" AND HOUR>{$ligne["hour"]}";


events_tail("Processing $tabledata -> $nexttable  (today is $todaytable) filter:'$filter_hour_2' in line ".__LINE__);

$sql="SELECT SUM( QuerySize ) AS QuerySize, SUM(hits) as hits,cached, HOUR( zDate ) AS HOUR , CLIENT, Country, uid, sitename,MAC,hostname,account
FROM $tabledata
GROUP BY cached, HOUR( zDate ) , CLIENT, Country, uid, sitename,MAC,hostname,account
HAVING QuerySize>0  $filter_hour_1$filter_hour_2";

$results=$GLOBALS["Q"]->QUERY_SQL($sql);
$num_rows=mysql_num_rows($results);
events_tail("Processing $tabledata -> $num_rows  rows in line ".__LINE__);
if($num_rows<10){$output_rows=true;}

if($num_rows==0){
	events_tail("$tabledata no rows...");
	if($CloseTable){
		events_tail("$tabledata -> Close table");
		$sql="UPDATE tables_day SET Hour=1 WHERE tablename='$tabledata'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
	}
	return true;
}

$prefix="INSERT IGNORE INTO $nexttable (zMD5,sitename,client,hour,remote_ip,country,size,hits,uid,category,cached,familysite,MAC,hostname,account) VALUES ";
$prefix_visited="INSERT IGNORE INTO visited_sites (sitename,category,country,familysite) VALUES ";
$f=array();
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
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
	
	$SQLSITESVS[]="('$sitename','$category','{$ligne["Country"]}','$familysite')";
	
	
	
	$md5=md5("{$ligne["sitename"]}{$ligne["CLIENT"]}{$ligne["HOUR"]}{$ligne["MAC"]}{$ligne["Country"]}{$ligne["uid"]}{$ligne["QuerySize"]}{$ligne["hits"]}{$ligne["cached"]}{$ligne["account"]}$category$Country");
	$sql_line="('$md5','$sitename','$client','{$ligne["HOUR"]}','$client','$Country','{$ligne["QuerySize"]}','{$ligne["hits"]}','$uid','$category','{$ligne["cached"]}',
	'$familysite','{$ligne["MAC"]}','{$ligne["hostname"]}','{$ligne["account"]}')";
	$f[]=$sql_line;
	
	if($output_rows){if($GLOBALS["VERBOSE"]){echo "$sql_line\n";}}	
	
	if(count($f)>500){
		$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
		$f=array();
	}
	if(count($SQLSITESVS)>0){
		$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
		$SQLSITESVS=array();
	}

}

if(count($f)>0){
	$GLOBALS["Q"]->QUERY_SQL("$prefix" .@implode(",", $f));
	events_tail("Processing ". count($f)." rows");
	if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error}");return;}
	
	if(count($SQLSITESVS)>0){
		events_tail("Processing ". count($SQLSITESVS)." visited sites");
		$GLOBALS["Q"]->QUERY_SQL($prefix_visited.@implode(",", $SQLSITESVS));
		if(!$GLOBALS["Q"]->ok){events_tail("Failed to process query to $next_table {$GLOBALS["Q"]->mysql_error} in line " .	__LINE__);}
	}
}
	return true;
}

function events_tail($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/proxy-injector.debug";
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


function GeoIP($servername){
	
	
	
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

function not_categorized_day_scan(){
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') AS `suffix`, `tablename`  FROM tables_day WHERE `not_categorized`>0 ORDER BY zDate DESC";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$syncatcmdline="$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --sync-categories schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &";	
	
	
	ufdbguard_admin_events("Task started",__FUNCTION__,__FILE__,__LINE__,"statistics");
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"statistics");
		echo "{$GLOBALS["Q"]->mysql_error}\n";
		shell_exec($syncatcmdline);
		return;
	}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){
		ufdbguard_admin_events("No data",__FUNCTION__,__FILE__,__LINE__,"statistics");
		if($GLOBALS["VERBOSE"]){echo "No datas `$sql`". __FUNCTION__." ".__LINE__."\n";}
		_not_categorized_day_scan();
		shell_exec($syncatcmdline);
		return;}
	$c=0;
	$f=0;
	ufdbguard_admin_events("$num_rows table(s) to synchronize",__FUNCTION__,__FILE__,__LINE__,"statistics");
	if($GLOBALS["VERBOSE"]){echo "$num_rows entrie(s)\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "recategorize_singleday({$ligne["suffix"]})\n";}
		
		try{
			recategorize_singleday($ligne["suffix"],true,$ligne["tablename"]);
		}catch (Exception $e) {ufdbguard_admin_events("fatal error:".  $e->getMessage(),__FUNCTION__,__FILE__,__LINE__,"statistics");}
			
			
		
		if(system_is_overloaded(__FILE__)){
			sleep(15);
			if(system_is_overloaded(__FILE__)){
				sleep(10);
				if(system_is_overloaded(__FILE__)){
					sleep(5);
				}
			}
			if(systemMaxOverloaded()){
				ufdbguard_admin_events("Task aborted, system is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]})",__FUNCTION__,__FILE__,__LINE__,"update");
				if($GLOBALS["VERBOSE"]){echo "systemMaxOverloaded !!!\n";}
				return;
			}
		}
	}
	ufdbguard_admin_events("Task finished starting recategorize days",__FUNCTION__,__FILE__,__LINE__,"statistics");
	_not_categorized_day_scan();

	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$syncatcmdline="$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --sync-categories schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &";
	shell_exec($syncatcmdline);
	$syncvisited="$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --visited-sites2 schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &";
	shell_exec($syncatcmdline);		
	
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
	$f=array();
	$sql="TRUNCATE TABLE UserAuthDaysGrouped";
	$GLOBALS["Q"]->QUERY_SQL($sql);
	$sql="SELECT ipaddr, hostname, uid, MAC, account, SUM( QuerySize ) AS QuerySize, SUM( hits ) AS hits FROM UserAuthDays GROUP BY ipaddr, hostname, uid, MAC, account";
	
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "$count items...\n";}
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal {$GLOBALS["Q"]->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");return;}	
	$cc=0;
	$prefix="INSERT INTO UserAuthDaysGrouped (`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`QuerySize`,`hits`) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["ipaddr"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["account"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}')";
		
		if(count($f)>500){
			$cc=$cc+count($f);
			if($GLOBALS["VERBOSE"]){echo "$cc/$count\n";}
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
		}
		
	}
	if(count($f)>0){$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));}
	
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
	}
	clients_hours(true);
	visited_websites_by_day(true);
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDays")){$GLOBALS["Q"]->CheckTables();}
	$unix=new unix();
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserAuthDays")){
		ufdbguard_admin_events("Fatal UserAuthDays no such table", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}
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
		if(system_is_overloaded(__FILE__)){
			ufdbguard_admin_events("Fatal overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task",__FUNCTION__,__FILE__,__LINE__,"stats");	
			break;
		}		
		
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
	$prefix="INSERT IGNORE INTO UserAuthDays (`zMD5`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`QuerySize`,`hits`) VALUES ";
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
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$ipaddr=$ligne["CLIENT"];
		$hostname=$ligne["hostname"];
		$uid=addslashes($ligne["uid"]);
		$MAC=$ligne["MAC"];
		$account=$ligne["account"];
		$QuerySize=$ligne["tsize"];
		$hits=$ligne["thits"];
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
	
	return true;
}



function table_days(){
	events_tail("Executed table_days in line ".__LINE__);
	$tables=$GLOBALS["Q"]->LIST_TABLES_QUERIES();
	if(count($tables)==0){events_tail("No working tables ? in line ".__LINE__);return;}
	$today=date('Y-m-d');
	events_tail(count($tables)." tables to scan in line ".__LINE__);
	
	while (list ($tablename, $date) = each ($tables) ){
		if($today==$date){events_tail("Skipping Today table $tablename in line ".__LINE__);continue;}
		$sql="SELECT zDate FROM tables_day WHERE tablename='$tablename'";
		$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
		if($ligne["zDate"]==null){
				$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(QuerySize) as tsize FROM $tablename WHERE cached=0"));
				$notcached=$ligne["tsize"];
				$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(QuerySize) as tsize FROM $tablename WHERE cached=1"));
				$cached=$ligne["tsize"];
				if(!is_numeric($notcached)){$notcached=0;}
				if(!is_numeric($cached)){$cached=0;}
				$totalsize=$notcached+$cached;
				$cache_perfs=round(($cached/$totalsize)*100);
				$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT SUM(hits) as thist FROM $tablename"));
				$requests=$ligne["thist"];
				if($GLOBALS["VERBOSE"]){echo "$date cached = $cached , not cached =$notcached total=$totalsize perf=$cache_perfs% requests=$requests\n";}
				$GLOBALS["Q"]->QUERY_SQL("INSERT INTO tables_day (tablename,zDate,size,size_cached,totalsize,cache_perfs,requests) 
				VALUES('$tablename','$date','$notcached','$cached','$totalsize','$cache_perfs','$requests');");
				if(!$GLOBALS["Q"]->ok){events_tail("{$GLOBALS["Q"]->mysql_error} in line ".__LINE__);}
			}
		}
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
	if(!$GLOBALS["Q"]->ok){echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas `$sql`". __FUNCTION__." ".__LINE__."\n";}return;}
	$c=0;
	$f=0;
	$already=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
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
		
		if($GLOBALS["VERBOSE"]){echo "update $domain width ". strlen($whoisdatas) ." bytes\n";}
		
		$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE familysite='$domain'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	}
	
	if($c>0){
		$unix=new unix();
		$took=$unix->distanceOfTimeInWords($t1,time());
		writelogs_squid("WHOIS: $c visisted websites informations updated $f failed, $took",__FUNCTION__,__FILE__,__LINE__,"visited");
		
	}
	
	
}

function visited_sites(){
	
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
		if($timeOfFile<240){return;}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	
	$t1=time();	
	
	
	if(!$GLOBALS["Q"]->TABLE_EXISTS("visited_sites_catz")){$GLOBALS["Q"]->CheckTables();}
	
	$sql="SELECT sitename,country,category FROM visited_sites";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);
	if($num_rows==0){if($GLOBALS["VERBOSE"]){echo "No datas ". __FUNCTION__." ".__LINE__."\n";}return;}
	
	$ROWS_visited_sites_catz=array();
	$ROWS_visited_sites_prefix="INSERT IGNORE INTO visited_sites_catz (`zmd5`,`category`,`familysite`) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(system_is_overloaded(__FILE__)){
			ufdbguard_admin_events("Fatal overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task",__FUNCTION__,__FILE__,__LINE__,"visited");	
			return;}
		$country=null;
		$FamilySite_update=null;
		$array=_visited_sites_calculate($ligne["sitename"]);
		if(!is_array($array)){continue;}
		if(trim($ligne["country"]==null)){$array_country=GeoIP($ligne["sitename"]);}
		if(isset($array_country)){if(isset($array_country[0])){$country=$array_country[0];}}
		if($country<>null){$country=",country='".addslashes($country)."'";;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["sitename"]} {$array[0]} hits, {$array[1]} size Country '$country' on {$array[2]} tables\n";}
		$categories=$GLOBALS["Q"]->GET_CATEGORIES($ligne["sitename"],true);
		$FamilySite=$GLOBALS["Q"]->GetFamilySites($ligne["sitename"]);
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
		if(system_is_overloaded(__FILE__)){sleep(30);}
			
			
	}
	
	if(count($ROWS_visited_sites_catz)>0){
		$GLOBALS["Q"]->QUERY_SQL($ROWS_visited_sites_prefix.@implode(",", $ROWS_visited_sites_catz));
		$ROWS_visited_sites_catz=array();
	}		
		
	$took=$unix->distanceOfTimeInWords($t1,time());
	ufdbguard_admin_events("Scanned $num_rows visisted websites $took",__FUNCTION__,__FILE__,__LINE__,"visited");
	visited_sites_whois();
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

function table_hours(){
	$prefix=date("YmdH");
	$unix=new unix();
	$currenttable="squidhour_$prefix";
	$tablesBrutes=$GLOBALS["Q"]->LIST_TABLES_WORKSHOURS();
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		if($tablename==$currenttable){continue;}
		$t=time();
		if(_table_hours_perform($tablename)){
			$took=$unix->distanceOfTimeInWords($t,time());
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablename`");
			writelogs_squid("success analyze $tablename in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
		}
	}
	
	$scan_hour_timefile="/etc/artica-postfix/pids/scan_hours.time";
	$time=$unix->file_time_min($scan_hour_timefile);
	if($time>119){
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup ".$unix->LOCATE_PHP5_BIN() . __FILE__." --scan-hours schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");		
		ufdbguard_admin_events("Scan hour function was not executed since {$time}Mn, execute it...",__FUNCTION__,__FILE__,__LINE__,"visited");
	}

}
function _table_hours_perform($tablename){
	if(!preg_match("#squidhour_([0-9]+)#",$tablename,$re)){return;}
	$hour=$re[1];
	$year=substr($hour,0,4);
	$month=substr($hour,4,2);
	$day=substr($hour,6,2);
	
	$f=array();
	$dansguardian_table="dansguardian_events_$year$month$day";
	$accounts=$GLOBALS["Q"]->ACCOUNTS_ISP();
	
	$GLOBALS["Q"]->CheckTables($dansguardian_table);
	$sql="SELECT COUNT(ID) as hits,SUM(QuerySize) as QuerySize,DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as zDate,sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,hostname FROM $tablename GROUP BY sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,zDate,hostname";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tablename`\n".@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		if(strpos(" {$GLOBALS["Q"]->mysql_error}", "is marked as crashed and should be repaired")>0){
			$q1=new mysql();
			writelogs_squid("try to repair table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"stats");
			$q1->REPAIR_TABLE("squidlogs",$tablename);
			writelogs_squid(@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		}
		return false;
	}
	$prefix="INSERT IGNORE INTO $dansguardian_table (sitename,uri,TYPE,REASON,CLIENT,MAC,zDate,zMD5,uid,remote_ip,country,QuerySize,hits,cached,hostname,account) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){
			$ligne[$key]=addslashes($value);
			$zmd[]=$value;
		}
		
		$zMD5=md5(@implode("",$zmd));
		$accountclient=null;
		if(isset($accounts[$ligne["CLIENT"]])){$accountclient=$accounts[$ligne["CLIENT"]];}
		
		$f[]="('{$ligne["sitename"]}','{$ligne["uri"]}','{$ligne["TYPE"]}','{$ligne["REASON"]}','{$ligne["CLIENT"]}',
		'{$ligne["MAC"]}','{$ligne["zDate"]}','$zMD5','{$ligne["uid"]}','{$ligne["remote_ip"]}','{$ligne["country"]}','{$ligne["QuerySize"]}',
		'{$ligne["hits"]}','{$ligne["cached"]}','{$ligne["hostname"]}','$accountclient')";
		if(count($f)>500){
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}	
		
	}
	
	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}
	
	
	

	
	return true;
	
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
					_clients_hours_perfom("dansguardian_events_{$ligne["tablesource"]}",$next_table);
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
			_clients_hours_perfom($dansguardian_events_table,$tablesource);
			$source_events=$GLOBALS["Q"]->COUNT_ROWS($tablesource);
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
	
	return true;	
	
}

function week_uris_blocked($asPid=false){
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
	
	
	$tStart=time();
	$GLOBALS["week_uris_blocked"]=0;
	if($GLOBALS["VERBOSE"]){echo "Check all tables...\n";}
	$GLOBALS["Q"]->CheckTables();
	if($GLOBALS["VERBOSE"]){echo "Create current week table\n";}
	$GLOBALS["Q"]->CreateWeekBlockedTable();
	if(!$GLOBALS["REBUILD"]){if($GLOBALS["VERBOSE"]){echo "Rebuild is not ordered\n";}}
	if($GLOBALS["REBUILD"]){
		if($GLOBALS["VERBOSE"]){echo "Rebuild tables...\n";}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET weekbdone=0 WHERE weekbdone=1");
	}
	
	
	$sql="SELECT tablename,DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesource,
	 DAYOFWEEK(zDate) as DayNumber,WEEK( zDate ) AS tweek,
	 YEAR( zDate ) AS tyear FROM tables_day WHERE weekbdone=0 AND zDate < DATE_SUB( NOW( ) , INTERVAL 1 DAY ) ORDER BY zDate";
	
	$unix=new unix();
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	
	
	$c=0;
	$FailedTables=0;
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "\n*********** WEEK {$ligne["tweek"]} of year {$ligne["tyear"]} ***********\n";}
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
		if($GLOBALS["VERBOSE"]){echo "Week Table:$week_table  - > CreateWeekBlockedTable('{$ligne["tyear"]}{$ligne["tweek"]}')\n";}
		if(!$GLOBALS["Q"]->CreateWeekBlockedTable("{$ligne["tyear"]}{$ligne["tweek"]}")){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$week_table` (CREATE)",__FUNCTION__,__FILE__,__LINE__,"stats");continue;}
		$DayNumber=$ligne["DayNumber"];
		$tablesource="{$ligne["tablesource"]}_blocked";
		if($GLOBALS["VERBOSE"]){echo "Table source :$week_table  - > _week_uris_blocked_perform($tablesource,$week_table,$DayNumber)\n";}
		$t=time();
		if(_week_uris_blocked_perform($tablesource,$week_table,$DayNumber)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET weekbdone=1 WHERE tablename='{$ligne["tablename"]}'");
			$took=$unix->distanceOfTimeInWords($t,time(),true);
			$c++;
			ufdbguard_admin_events("Success update day Number $DayNumber from $tablesource to $week_table in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
		}else{
			$FailedTables++;
		}
		
	}
	
	
	$took=$unix->distanceOfTimeInWords($tStart,time(),true);
	if($c>0){
		ufdbguard_admin_events("Success: added {$GLOBALS["week_uris_blocked"]} rows on $c tables with $FailedTables error(s) took:$took",__FUNCTION__,__FILE__,__LINE__,"stats");
	}
	
}
function _week_uris_blocked_perform($tablesource,$week_table,$DAYOFWEEK){
	$f=array();
	
	$t1=0;
	$GLOBALS["Q"]->RepairTableBLock($tablesource);
	if(!$GLOBALS["Q"]->TABLE_EXISTS($tablesource)){ufdbguard_admin_events("$tablesource does not exists, skiping",__FUNCTION__,__FILE__,__LINE__,"stats");return true;}
	
	$sql="SELECT COUNT( ID ) as hits,`website`,`category`,`client`,`hostname`,`rulename`,`event`,`why`,`explain`,`blocktype`,`account`
	FROM `$tablesource`
	GROUP BY website,`category`, `client`, `hostname`, `rulename`,`event`,`why`,`explain`,`blocktype`,`account`";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	
	if(!$GLOBALS["Q"]->ok){echo "\n\n$sql\n";
		ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tablesource`",__FUNCTION__,__FILE__,__LINE__,"stats");
		return false;
	}

	$prefix="INSERT IGNORE INTO $week_table (`zMD5`,`hits`,`website`,`category`, `client`, `hostname`, `rulename`,`event`,`why`,`explain`,`blocktype`,`day`,`account`) VALUES ";
	
	if(!$GLOBALS["Q"]->FIELD_EXISTS($week_table, "account")){
		ufdbguard_admin_events("Alter table $week_table (create new `account` field)",__FUNCTION__,__FILE__,__LINE__,"stats");
		$GLOBALS["Q"]->QUERY_SQL("ALTER TABLE `$week_table` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");
	}
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=addslashes($value);$zmd[]=$value;}
		$zMD5=md5(@implode("",$zmd));
		
		
		$f[]="('$zMD5','{$ligne["hits"]}','{$ligne["website"]}','{$ligne["category"]}','{$ligne["client"]}','{$ligne["hostname"]}','{$ligne["rulename"]}','{$ligne["event"]}',
		'{$ligne["why"]}','{$ligne["explain"]}','{$ligne["blocktype"]}',$DAYOFWEEK,'{$ligne["account"]}')";
		
		if(count($f)>500){
			$t1=$t1+count($f);
			$GLOBALS["week_uris_blocked"]=$GLOBALS["week_uris_blocked"]+count($f);
			if($GLOBALS["VERBOSE"]){echo "$week_table: Adding ". count($f). " events\n";}
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$week_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return false;}
		}			
	}
	
	
	if(count($f)>0){
		$t1=$t1+count($f);
		$GLOBALS["week_uris_blocked"]=$GLOBALS["week_uris_blocked"]+count($f);
		if($GLOBALS["VERBOSE"]){echo "$week_table: Adding ". count($f). " events\n";}
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$week_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return false;}
	}
	
	ufdbguard_admin_events("Success: added $t1 rows on `$week_table`",__FUNCTION__,__FILE__,__LINE__,"stats");
	return true;	
	
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
	
		if(system_is_overloaded(basename(__FILE__))){
			$took=$unix->distanceOfTimeInWords($t,time());
			ufdbguard_admin_events("Fatal: Overloaded aborting task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;	
		}	
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){writelogs("Already executed pid:$oldpid",__FUNCTION__,__FILE__,__LINE__);return;}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	$ttim=$unix->file_time_min($pidTime);
	if($ttim<3){writelogs("Aborting, neew to wait 3mn Current:{$ttim}mn",__FUNCTION__,__FILE__,__LINE__);return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	if(!is_dir("/var/log/artica-postfix/pagepeeker")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/pagepeeker")) {return;}
	$unix=new unix();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$targetFile="/var/log/artica-postfix/pagepeeker/$filename";
		$minutes=$unix->file_time_min($targetFile);
		if($minutes>2880){@unlink($targetFile);continue;}
		$sitename=@file_get_contents($targetFile);
		thumbnail_site($sitename);
		@unlink($targetFile);
	}	
	
	
}


function thumbnail_query(){
	$sock=new sockets();
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
	$uriDomain="pagepeeker.com";
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
	if(!$curl->GetFile("/tmp/$sitename.gif")){
		ufdbguard_admin_events("Failed to get thumbnail for $sitename",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
		return false;
	}
	
	 
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
      ufdbguard_admin_events("$sitename thumbnail generated ($md5F) $dataSize Ko",__FUNCTION__,__FILE__,__LINE__,"thumbnails");
      
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

function users_size_hour(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
		if($pidtime>50){
			ufdbguard_admin_events("Killing pid $oldpid, running since $pidtime mn", __FUNCTION__, __FILE__, __LINE__, "stats");
			$kill=$unix->find_program("kill");
			shell_exec("$kill -9 $oldpid");
		}else{
			ufdbguard_admin_events("pid already in memory $oldpid, running since $pidtime mn", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
		
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$q=new mysql_squid_builder();
	$numrows_0=$q->COUNT_ROWS("UserSizeRTT");
	$UsersAgents=array();
	ufdbguard_admin_events("Starting PID $mypid, table store $numrows_0 rows", __FUNCTION__, __FILE__, __LINE__, "stats");
	$sql="SELECT DATE_FORMAT(zdate,'%Y%m%d') as tablesuffix,
	DATE_FORMAT(zdate,'%Y-%m-%d') as tday,
	COUNT(zMD5) as thits,
	SUM(size) as size,HOUR(zdate) as zhour,
	uid,ipaddr,hostname,account,MAC,UserAgent
	FROM (SELECT * FROM UserSizeRTT WHERE zdate<DATE_SUB(NOW(),INTERVAL 1 HOUR)) as t
	GROUP BY tablesuffix,zhour,uid,ipaddr,hostname,account,MAC,UserAgent";
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){ufdbguard_admin_events("Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	$t=time();
	if(mysql_num_rows($results)==0){return;}	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$zMD5=md5(serialize($ligne));
		if($ligne["tday"]=="1970-01-01"){continue;}
		$ligne["UserAgent"]=addslashes($ligne["UserAgent"]);
		$ligne["uid"]=addslashes($ligne["uid"]);
		if($ligne["uid"]=="-"){$ligne["uid"]=null;}
		$f[$ligne["tablesuffix"]][]="('$zMD5','{$ligne["uid"]}','{$ligne["tday"]}','{$ligne["ipaddr"]}','{$ligne["hostname"]}','{$ligne["account"]}','{$ligne["MAC"]}','{$ligne["UserAgent"]}','{$ligne["size"]}','{$ligne["thits"]}','{$ligne["zhour"]}')";
		if(trim($ligne["UserAgent"])<>null){
			$UsersAgents[]="('".addslashes($ligne["UserAgent"])."')";
		}
	}	
	
	if(count($f)>0){
		while (list ($tablesuffix, $rows) = each ($f) ){
			$tablename="UserSizeD_$tablesuffix";
			if(!$q->CreateUserSizeRTT_day($tablename)){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
			$q->QUERY_SQL($sql);
			if(!$q->ok){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			
		}
		
		$sql="DELETE FROM UserSizeRTT WHERE zdate<DATE_SUB(NOW(),INTERVAL 1 HOUR)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$q->QUERY_SQL("OPTIMIZE TABLE UserSizeRTT");
		$numrows_1=$q->COUNT_ROWS("UserSizeRTT");
		$total=$numrows_0-$numrows_1;
		ufdbguard_admin_events(count($f). " new injected table and $total new row(s)",__FUNCTION__,__FILE__,__LINE__,"stats");
	}
	
	if(count($UsersAgents)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO UserAgents (`pattern`) VALUES ". @implode(",", $UsersAgents));
	}
	
	if($q->COUNT_ROWS("UserAgents")==0){
		$sql="SELECT UserAgent FROM UserSizeD_".date("Ymd")." GROUP BY UserAgent";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			if(trim($ligne["UserAgent"])<>null){$UsersAgents[]="('".addslashes($ligne["UserAgent"])."')";}
		}
		$q->QUERY_SQL("INSERT IGNORE INTO UserAgents (`pattern`) VALUES ". @implode(",", $UsersAgents));
	}
	
	scan_hours(true);
	
}

function repair_hours(){
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){echo "Starting ".__FUNCTION__."\n";}
	$q=new mysql_squid_builder();
	$CurrentHourTable="squidhour_".date("YmdH");
	if($GLOBALS["VERBOSE"]){echo "Find hours tables...\n";}
	$tables=$q->LIST_TABLES_HOURS_TEMP();
	$c=0;
	if($GLOBALS["VERBOSE"]){echo "Find hours tables done ". count($tables)." table(s)...\n";}
	while (list ($table, $none) = each ($tables) ){
		if($table==$CurrentHourTable){if($GLOBALS["VERBOSE"]){echo "SKIP `$table`\n";}continue;}
		if($GLOBALS["VERBOSE"]){echo "Analyze: $table\n";}
		if(!preg_match("#squidhour_([0-9]+)#",$table,$re)){continue;}
		$hour=$re[1];
		$year=substr($hour,0,4);
		$month=substr($hour,4,2);
		$day=substr($hour,6,2);		
		
		
		if(_table_hours_perform($table)){
			$c++;
			$took=$unix->distanceOfTimeInWords($t,time());
			$q->QUERY_SQL("DROP TABLE `$table`");
			writelogs_squid("success analyze $table in $took",__FUNCTION__,__FILE__,__LINE__,"stats");
		}		
		
		
		$dansguardian_table="dansguardian_events_$year$month$day";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT tablename FROM  tables_day WHERE tablename='$dansguardian_table'"));
		if($ligne["tablename"]==null){
			$sql="INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ('$dansguardian_table','$year-$month-$day')";
		}else{
			$sql="UPDATE tables_day SET Hour=0,members=0,month_members=0,weekdone=0 WHERE tablename='$dansguardian_table'";
		}
		$q->QUERY_SQL($sql);
	}
	
	if($c>0){
		ufdbguard_admin_events("Success repair $c tables ",__FUNCTION__,__FILE__,__LINE__,"stats");
		
	}
	
	
	repair_week();
	week_uris();
	week_uris_blocked();	
	
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



?>