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

$GLOBALS["Q"]=new mysql_squid_builder();
if($argv[1]=="--cleanall"){CleanAll();exit;}
if($argv[1]=="--year"){table_year();exit;}
if($argv[1]=="--current"){Calculate_current_month();exit;}




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
		if($timeexec<2880){if($GLOBALS["VERBOSE"]){echo "Only each 2880mn - current {$timeexec}mn, use --force to bypass\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	@file_put_contents($timefile, time());
	
	if($GLOBALS["VERBOSE"]){echo "index_tables_day()\n";}
	index_tables_day();
	

	
	if(!$GLOBALS["Q"]->ifStatisticsMustBeExecuted()){
		if($GLOBALS["VERBOSE"]){echo "This is not intended to build statistics - ifStatisticsMustBeExecuted -\n";}
		return;
	}
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS `suffix`,DATE_FORMAT(zDate,'%Y%m') AS `prefix`,
			MONTH(`zDate`) as MONTH, DAY(`zDate`) AS DAY, YEAR(`zDate`) AS YEAR FROM tables_day
			WHERE monthdone=0 AND `zDate`< DATE_SUB(NOW(),INTERVAL 3 DAY) ORDER BY zDate";
	
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "Return no row...\n";}
		table_year();
		return;}
		
		
		if($GLOBALS["VERBOSE"]){echo mysql_num_rows($result)." items -> start loop\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tableKEY=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$prefix=$ligne["prefix"];
		$tablesource="{$suffix}_hour";
		$nexttable="{$prefix}_month";
		$dayNum=$ligne["DAY"];
		if($GLOBALS["VERBOSE"]){echo "$dayNum $tablesource -> Next table: $nexttable\n";}
		if(!perform($tablesource,$nexttable,$dayNum)){continue;}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET monthdone=1 WHERE `tablename`='$tableKEY'");
		
	}
	
	table_year();
	
}
function perform($tablesource,$nexttable,$dayNum){

	
	
	if(!$GLOBALS["Q"]->CreateMonthTable($nexttable)){
		if($GLOBALS["VERBOSE"]){echo "$nexttable, failed\n";}
		return false;
	}
	$accounts=$GLOBALS["Q"]->ACCOUNTS_ISP();
	$GLOBALS["Q"]->QUERY_SQL("UPDATE `$tablesource` SET MAC='' WHERE MAC='00:00:00:00:00:00'");
	

	$sql="SELECT SUM(size) as size,SUM(hits) as hits,
	familysite,client,account,remote_ip
	,MAC,country,uid,category,cached FROM `$tablesource` 
	GROUP BY familysite,client,account,remote_ip,MAC,country,uid,category,cached";

	if($GLOBALS["VERBOSE"]){echo "$nexttable, QUERY\n$sql\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);

	
	if(!$GLOBALS["Q"]->ok){
		writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tablesource`\n".@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		return false;
	}
	
	$prefix="INSERT IGNORE INTO $nexttable (
	`zMD5`,`day`,`size`,`hits`,`familysite`,`client`,`account`,`remote_ip`
	,`MAC`,`country`,`uid`,`category`,`cached`
	) VALUES ";


	$d=0;
	$TOT=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "$nexttable, LOOP ON $TOT\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($value);}
		$d++;
		
	
		$f[]="('$zMD5','$dayNum','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["familysite"]}','{$ligne["client"]}','{$ligne["account"]}',
		'{$ligne["remote_ip"]}','{$ligne["MAC"]}','{$ligne["country"]}','{$ligne["uid"]}','{$ligne["category"]}','{$ligne["cached"]}')";
		if(count($f)>1000){
			if($GLOBALS["VERBOSE"]){echo "$d - ". ($TOT-$d)."\n";}
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$nexttable`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$nexttable`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}
	return true;
}

function CheckIfAllImported(){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("accesslogs_import")){return true;}
	if($q->COUNT_ROWS("accesslogs_import")==0){if($GLOBALS["VERBOSE"]){echo "accesslogs_import no row OK\n";}return true;}
	$results=$q->QUERY_SQL("SELECT * FROM accesslogs_import WHERE status<3");
	if(mysql_num_rows($results)==0){return true;}
	return false;
}
function events_repair($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
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

function Calculate_current_month(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.month.php.Calculate_current_month.time";
	if($GLOBALS["VERBOSE"]){echo "time: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		return;
		
	}
	
	$timeFile=$unix->file_time_min($timefile);
	if($timeFile<1440){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$q=new mysql_squid_builder();
	$sql="UPDATE tables_day SET year1=0 WHERE YEAR(zDate)=YEAR(NOW()) AND MONTH(zDate)=MONTH(NOW())";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$sql="DELETE FROM allsizes WHERE YEAR(zDate)=YEAR(NOW()) AND MONTH(zDate)=MONTH(NOW())";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
		
	table_year();
}

function table_month_stamp(){
	
	
	
}


function table_year(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("tables_day", "year1")){$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `year1` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `year1` )");}
	$yeartable="allsizes";
	$sql="CREATE TABLE IF NOT EXISTS `allsizes` (
	`zDate` DATE NOT NULL,
	`size` BIGINT UNSIGNED NOT NULL,
	`cached` BIGINT UNSIGNED NOT NULL,
	`websites` BIGINT UNSIGNED NOT NULL,
	`uid` BIGINT UNSIGNED NOT NULL,
	`client` BIGINT UNSIGNED NOT NULL,
	`hostname` BIGINT UNSIGNED NOT NULL,
	`MAC` BIGINT UNSIGNED NOT NULL,
	`cachepourc` smallint(3) NOT NULL,
	PRIMARY KEY (`zDate`),
	KEY `size` (`size`),
	KEY `cached` (`cached`),
	KEY `uid` (`uid`),
	KEY `client` (`client`),
	KEY `hostname` (`hostname`),
	KEY `MAC` (`MAC`),
	KEY `cachepourc` (`cachepourc`)
	
	) ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y-%m-%d') AS `zDate`,DATE_FORMAT(zDate,'%Y%m%d') AS `prefix` FROM tables_day WHERE year1=0 AND `zDate`< DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){ if($GLOBALS["VERBOSE"]){echo "Return no row...\n";} return;}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$tableKEY=$ligne["tablename"];
			$zDate=$ligne["zDate"];
			$prefix=$ligne["prefix"];
			$tablesource="{$prefix}_hour";
			
			$sql="SELECT SUM(size) as size FROM $tablesource WHERE `cached`=0";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$notcached=$ligne2["size"];
			$sql="SELECT SUM(size) as size FROM $tablesource WHERE `cached`=1";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$cached=$ligne2["size"];
			
			$sql="SELECT familysite FROM  $tablesource GROUP BY familysite";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$familysites=mysql_numrows($results2);
			
			$sql="SELECT familysite FROM  $tablesource GROUP BY familysite";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$familysites=mysql_numrows($results2);
			
			$sql="SELECT client FROM  $tablesource GROUP BY client";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$client=mysql_numrows($results2);
			
			$sql="SELECT hostname FROM  $tablesource GROUP BY hostname";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$hostname=mysql_numrows($results2);
			
			$sql="SELECT MAC FROM  $tablesource GROUP BY MAC";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$MAC=mysql_numrows($results2);
			
			$sql="SELECT uid FROM  $tablesource GROUP BY uid";
			$results2=$GLOBALS["Q"]->QUERY_SQL($sql);
			$uid=mysql_numrows($results2);
			
			$tot=$notcached+$cached;
			$perc=($cached/$tot*100);
			$perc=round($perc);
			echo "$tablesource - $zDate familysites: $familysites cached: $cached Not cached:$notcached perc:{$perc}%\n";
			
			$sql="INSERT INTO allsizes (`zDate`,`size`,`cached`,`websites`,`uid`,`client`,`hostname`,`MAC`,`cachepourc`)
			VALUES('$zDate','$notcached','$cached','$familysites','$uid','$client','$hostname','$MAC','$perc')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){return;}
			$q->QUERY_SQL("UPDATE tables_day SET year1=1 WHERE tablename='$tableKEY'");
			
			
		}
	
}




function restore_from_backup_statistics(){
	$GLOBALS["VERBOSE"]=true;
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_to_hour_tables();
	$squid_stats_tools->not_categorized_day_scan();
	///home/artica/squid/backup-statistics
}

?>