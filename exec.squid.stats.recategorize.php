<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;

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

$GLOBALS["Q"]=new mysql_squid_builder();

if($GLOBALS["VERBOSE"]){"echo Parsing arguments...\n";}

$sock=new sockets();
$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");
if(!is_numeric($DisableLocalStatisticsTasks)){$DisableLocalStatisticsTasks=0;}
if($DisableLocalStatisticsTasks==1){die();}
re_categorize();


function re_categorize($nopid=false){
	
	$q=$GLOBALS["Q"];
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){
			ufdbguard_admin_events("Already executed pid $oldpid",__FUNCTION__,__FILE__,__LINE__,"stats");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
			return;
		}
	
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	}
	
		
	

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
	if(!$GLOBALS["Q"]->FIELD_EXISTS("visited_sites", "recatgorized")){
		$GLOBALS["Q"]->QUERY_SQL("ALTER TABLE `visited_sites` ADD `recatgorized` smallint(1) NOT NULL ,
				ADD KEY `recatgorized` (`recatgorized`)");
	}
	
	$sql="SELECT * FROM visited_sites WHERE recatgorized='0' LIMIT 0,300";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	$num_rows = mysql_num_rows($results);
	
	stats_admin_events(2, "Trying to recategorize $num_rows visited websites", "",__FILE__,__LINE__);
	
	$t=time();
	
	$c=0;
	$L=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=trim($ligne["sitename"]);
		$categorySrc=$ligne["category"];
		if($website==null){continue;}
		$category=trim($GLOBALS["Q"]->GET_CATEGORIES($website,true));
		if($category==$categorySrc){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET recatgorized=1 WHERE sitename='$website'");
			continue;
		}
		
		if($category==null){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET recatgorized=1 WHERE sitename='$website'");
			continue;			
		}
		
		$GLOBALS["Q"]->QUERY_SQL("UPDATE visited_sites SET category='$category',recatgorized=1 WHERE sitename='$website'");
		if(!$GLOBALS["Q"]->ok){stats_admin_events(0,"Fatal: mysql error","{$GLOBALS["Q"]->mysql_error}",__FILE__,__LINE__);return;}	
		$c++;
		$L++;
		
		$websites[$website]=$category;
		
		if($L>400){
			$took=$unix->distanceOfTimeInWords($t,time());
			if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
			$L=0;
		}
	}
	$took=$unix->distanceOfTimeInWords($t,time());
	stats_admin_events(2,"$c re-categorized  websites in main table  ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
	if(count($websites)>0){
		__re_categorize_subtables($t,$websites);
	}
	
}

function __re_categorize_subtables($oldT1=0,$websites){
	$unix=new unix();
	if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
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

	
	$CountUpdatedTables=0;
	while (list ($website, $category) = each ($websites) ){		
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

		if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
		$distanceInSeconds = round(abs(time() - $t));
		$distanceInMinutes = round($distanceInSeconds / 60);
		if($distanceInMinutes>$RecategorizeMaxExecutionTime){$took=$unix->distanceOfTimeInWords($t,time());writelogs_squid("Re-categorized websites task aborted (Max execution time {$RecategorizeMaxExecutionTime}Mn) ($took)",__FUNCTION__,__FILE__,__LINE__,"categorize");return;}

	}

	$took=$unix->distanceOfTimeInWords($t,time());
	stats_admin_events(2,count($websites)."re-categorized  websites updated in `$CountUpdatedTables` MySQL tables ($took)",__FUNCTION__,__FILE__,__LINE__,"stats");
}
?>