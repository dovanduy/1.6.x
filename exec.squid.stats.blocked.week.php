<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
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

$GLOBALS["Q"]=new mysql_squid_builder();
week_uris_blocked();

function week_uris_blocked_verify(){
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesource, DAYOFWEEK(zDate) 
			as DayNumber,WEEK( zDate ) AS tweek, YEAR( zDate ) 
			AS tyear FROM tables_day  WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 1 DAY ) ORDER BY zDate";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$tablename=$ligne["tablename"];
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
		if($GLOBALS["VERBOSE"]){echo "\n*********** WEEK $week_table -> {$ligne["tweek"]} of year {$ligne["tyear"]} ***********\n";}
		if(!$q->TABLE_EXISTS($week_table)){
			if($GLOBALS["VERBOSE"]){echo "Restart $tablename\n";}
			$q->QUERY_SQL("UPDATE tables_day SET weekbdone=0 WHERE `tablename`='$tablename'");
		}
		$Rows=$q->COUNT_ROWS($week_table);
		if($GLOBALS["VERBOSE"]){echo "$week_table =  $Rows rows\n";}
		
	}
	
	
}



function week_uris_blocked($asPid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$pid=@file_get_contents($pidfile);
	$myfile=basename(__FILE__);
	if($unix->process_exists($pid,$myfile)){ return; }

	$tStart=time();
	

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
	if(!$GLOBALS["Q"]->ok){
		stats_admin_events(0, "[Weekly]: Fatal  MySQL error on `tables_day`", "{$GLOBALS["Q"]->mysql_error}",__FILE__,__LINE__);
		return;
	}


	$c=0;
	$FailedTables=0;
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "\n*********** WEEK {$ligne["tweek"]} of year {$ligne["tyear"]} ***********\n";}
		continue;
		$week_table="{$ligne["tyear"]}{$ligne["tweek"]}_blocked_week";
		if($GLOBALS["VERBOSE"]){echo "Week Table:$week_table  - > CreateWeekBlockedTable('{$ligne["tyear"]}{$ligne["tweek"]}')\n";}
		if(!$GLOBALS["Q"]->CreateWeekBlockedTable("{$ligne["tyear"]}{$ligne["tweek"]}")){ufdbguard_admin_events("Fatal: {$GLOBALS["Q"]->mysql_error} on `$week_table` (CREATE)",__FUNCTION__,__FILE__,__LINE__,"stats");continue;}
		$DayNumber=$ligne["DayNumber"];
		$tablesource="{$ligne["tablesource"]}_blocked";
		$tablesources[]=$tablesource;
		if($GLOBALS["VERBOSE"]){echo "Table source :$week_table  - > _week_uris_blocked_perform($tablesource,$week_table,$DayNumber)\n";}
		$t=time();
		if(_week_uris_blocked_perform($tablesource,$week_table,$DayNumber)){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET weekbdone=1 WHERE tablename='{$ligne["tablename"]}'");
			$c++;
		}else{
			$FailedTables++;
		}
		
		if(SquidStatisticsTasksOverTime()){ stats_admin_events(1,"Statistics overtime... Aborting",null,__FILE__,__LINE__); return; }
	}
		


	$took=$unix->distanceOfTimeInWords($tStart,time(),true);
	if($FailedTables>0){
		stats_admin_events(2, "[Weekly]: blocked events done ( $took ) $FailedTables Failed tables", "Tables:\n".@implode("\n", $tablesources),__FILE__,__LINE__);
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
		$GLOBALS["Q"]->QUERY_SQL("ALTER TABLE `$week_table` ADD `account` BIGINT UNSIGNED NOT NULL ,ADD INDEX ( `account` )");
	}
	if(!$GLOBALS["Q"]->FIELD_EXISTS($week_table, "MAC")){
		ufdbguard_admin_events("Alter table $week_table (create new `MAC` field)",__FUNCTION__,__FILE__,__LINE__,"stats");
		$GLOBALS["Q"]->QUERY_SQL("ALTER TABLE `$week_table` ADD `MAC` VARCHAR(20) NOT NULL ,ADD INDEX ( `MAC` )");
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


	$sql="SELECT uid,MAC FROM webfilters_nodes";

	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strlen($ligne["uid"])>1){
			$GLOBALS["Q"]->QUERY_SQL("UPDATE $week_table SET uid='{$ligne["uid"]}' WHERE MAC='{$ligne["MAC"]}' AND LENGTH(uid)<2");
		}
	}

	ufdbguard_admin_events("Success: added $t1 rows on `$week_table`",__FUNCTION__,__FILE__,__LINE__,"stats");
	return true;

}

?>