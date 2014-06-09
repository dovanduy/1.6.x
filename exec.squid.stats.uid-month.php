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
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(system_is_overloaded()){die();}

$GLOBALS["Q"]=new mysql_squid_builder();

if($argv[1]=="--cleanall"){CleanAll();exit;}
if($argv[1]=="--year"){table_year();exit;}



tables_months();



function CleanAll(){
	$q=new mysql_squid_builder();
	$LIST_TABLES_MONTH=$q->LIST_TABLES_MONTH();
	while (list ($tablename, $none) = each ($LIST_TABLES_MONTH) ){
		echo "Empty $tablename\n";
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	}
	
	$q->QUERY_SQL("UPDATE tables_day SET monthdone=0 WHERE monthdone=1");
}


function index_tables_day(){
	$q=new mysql_squid_builder();
	$LIST_TABLES_HOURS=$GLOBALS["Q"]->LIST_TABLES_HOURS();
	
	$prefix="INSERT IGNORE INTO tables_day (`tablename`,`zDate`) VALUES ";
	
	while (list ($tablename, $none) = each ($LIST_TABLES_HOURS) ){
		$xtime=$GLOBALS["Q"]->TIME_FROM_HOUR_TABLE($tablename);
		$day=date("Y-m-d",$xtime);
		$table_key="dansguardian_events_".date("Ymd",$xtime);
		$f[]="('$table_key','$day')";
	}
	
	$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
	
}




function tables_months(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	$pid=@file_get_contents($pidfile);
	
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<1240){if($GLOBALS["VERBOSE"]){echo "Only each 1240mn - current {$timeexec}mn, use --force to bypass\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	@file_put_contents($timefile, time());
	
	if($GLOBALS["VERBOSE"]){echo "index_tables_day()\n";}
	
	

	
	if(!$GLOBALS["Q"]->ifStatisticsMustBeExecuted()){
		if($GLOBALS["VERBOSE"]){echo "This is not intended to build statistics - ifStatisticsMustBeExecuted -\n";}
		return;
	}
	
	table_year();
	
}

function table_year(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("tables_day", "year2")){$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `year2` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `year1` )");}
	
	$sql="CREATE TABLE IF NOT EXISTS `alluid` (
	`zmd5` VARCHAR(90) NOT NULL,
	`zDate` DATE NOT NULL,
	`size` BIGINT UNSIGNED NOT NULL,
	`websites` BIGINT UNSIGNED NOT NULL,
	`uid` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`zmd5`),
	KEY `zDate` (`zDate`),
	KEY `size` (`size`),
	KEY `uid` (`uid`)
	
	) ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y-%m-%d') AS `zDate`,DATE_FORMAT(zDate,'%Y%m%d') 
			AS `prefix` FROM tables_day WHERE year2=0 AND `zDate`< DATE_SUB(NOW(),INTERVAL 2 DAY) ORDER BY zDate";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){ if($GLOBALS["VERBOSE"]){echo "Return no row...\n";} return;}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$tableKEY=$ligne["tablename"];
			$zDate=$ligne["zDate"];
			$prefix=$ligne["prefix"];
			$tablesource="quotaday_{$prefix}";
			if($GLOBALS["VERBOSE"]){echo "$tablesource\n";}
			if(!perform($tablesource,$zDate)){continue;}
			$q->QUERY_SQL("UPDATE tables_day set year2=1 WHERE tablename='$tableKEY'");
			if(system_is_overloaded(__FILE__)){return;}
		}
	
}

function perform($tablesource,$zDate){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($tablesource)){return true;}
	$sql="SELECT COUNT(familysite) as familysite, SUM(size) as size,uid FROM $tablesource GROUP BY 
	uid HAVING uid NOT LIKE '%$' AND LENGTH(uid) >0";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	
	$prefix="INSERT IGNORE INTO alluid (zDate,zmd5,size,websites,uid) VALUES ";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$uid=mysql_escape_string2($ligne["uid"]);
		$zmd5=md5(serialize($ligne));
		$f[]="('$zDate','$zmd5','{$ligne["size"]}','{$ligne["familysite"]}','$uid')";
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
	}
	
	return true;
}




function restore_from_backup_statistics(){
	$GLOBALS["VERBOSE"]=true;
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_to_hour_tables();
	$squid_stats_tools->not_categorized_day_scan();
	///home/artica/squid/backup-statistics
}

?>