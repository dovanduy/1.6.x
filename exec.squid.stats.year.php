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
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

///etc/artica-postfix/pids/exec.squid.stats.year.php.tables_year.time

if(system_is_overloaded()){die();}

$GLOBALS["Q"]=new mysql_squid_builder();
if(!$GLOBALS["Q"]->ifStatisticsMustBeExecuted()){if($GLOBALS["VERBOSE"]){echo "This is not intended to build statistics - ifStatisticsMustBeExecuted -\n";}die();}


if($argv[1]=="--repair"){repair_day();exit;}

tables_year();


function tables_year(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<2880){
			if($GLOBALS["VERBOSE"]){echo "Only each 2880mn - current {$timeexec}mn, use --force to bypass\n";}
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	
	@unlink($timefile);
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	@file_put_contents($timefile, time());
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m') AS `suffix`,
			DATE_FORMAT(zDate,'%Y') AS `prefix`,
			MONTH(`zDate`) as MONTH FROM tables_day
			WHERE yeardone=0 AND `zDate`< DATE_SUB(NOW(),INTERVAL 1 MONTH) ORDER BY zDate";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "Return no row...\n";}
		return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$prefix=$ligne["prefix"];
		$MONTH=$ligne["MONTH"];
		$tablesource="{$suffix}_month";
		$nexttable="{$prefix}_year";
		$array[$tablesource]["NEXT"]=$nexttable;
		$array[$tablesource]["SUFFIX"]=$suffix;
		$array[$tablesource]["MONTH"]=$MONTH;
		
		
	}
	while (list ($tablesource, $ligne) = each ($array) ){
		$nexttable=$ligne["NEXT"];
		$suffix=$ligne["SUFFIX"];
		$MONTH=$ligne["MONTH"];
		if($GLOBALS["VERBOSE"]){echo "$MONTH $tablesource -> $nexttable\n";}
		
		if(!perform($tablesource,$nexttable,$MONTH)){continue;}
		
		
		$GLOBALS["Q"]->QUERY_SQL("UPDATE tables_day SET yeardone=1 WHERE DATE_FORMAT(zDate,'%Y%m') ='$suffix'");
		if(system_is_overloaded()){
			if($GLOBALS["VERBOSE"]){echo "Overloaded\n";}
			@unlink($timefile);
			$php=$unix->LOCATE_PHP5_BIN();
			$unix->THREAD_COMMAND_SET($php ." ".__FILE__);
			die();
		}
	}
	
}








function perform($tablesource,$nexttable,$MONTH){
	$q=new mysql_squid_builder();
	if(!$q->CreateYearTable($nexttable)){
		if($GLOBALS["VERBOSE"]){echo "$nexttable, failed\n";}
		return false;
	}
	$q->QUERY_SQL("TRUNCATE TABLE `$nexttable`");
	$q->QUERY_SQL("UPDATE `$tablesource` SET MAC='' WHERE MAC='00:00:00:00:00:00'");
	ini_set('memory_limit', '750M');
	$sql="SELECT SUM(size) as size,SUM(hits) as hits,
	familysite,client,hostname,account,remote_ip
	,MAC,country,uid,category,cached FROM `$tablesource` 
	GROUP BY familysite,client,hostname,account,remote_ip,MAC,country,uid,category,cached";

	$t=time();
	if($GLOBALS["VERBOSE"]){echo "$nexttable -> $tablesource QUERY\n";}
	
	$results=$q->QUERY_PDO($sql);
	if(!$results){return false;}
	
	$prefix="INSERT IGNORE INTO $nexttable (
	`zMD5`,`month`,`size`,`hits`,`familysite`,`client`,`hostname`,`account`,`remote_ip`
	,`MAC`,`country`,`uid`,`category`,`cached`
	) VALUES ";

	$d=0;
	$TOT=mysql_num_rows($results);
	$c=0;
	$s=0;
	$memory_get_usage=memory_get_usage(true);
	$ko=round($memory_get_usage/1024,2);
	$ko=$ko/1024;
	$lastKo=$ko;
	if($GLOBALS["VERBOSE"]){echo "$nexttable, LOOP ON $TOT\n";}
	foreach ($results->fetchAll(PDO::FETCH_ASSOC) as $ligne) {		
		$zMD5=md5(serialize($ligne));
		$c++;
		$d++;
		$s++;
		$ligne=mysql_escape_line_query($ligne,true);
		
		
		$sql="$prefix ('$zMD5','$MONTH','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["familysite"]}','{$ligne["client"]}','{$ligne["hostname"]}','{$ligne["account"]}',
		'{$ligne["remote_ip"]}','{$ligne["MAC"]}','{$ligne["country"]}','{$ligne["uid"]}','{$ligne["category"]}','{$ligne["cached"]}')";
		
		if($s>5000){
			
			$memory_get_usage=memory_get_usage(true);
			$ko=round($memory_get_usage/1024,2);
			$ko=$ko/1024;
			$added=$ko-$lastKo;
			$lastKo=$ko;
			if($GLOBALS["VERBOSE"]){echo "FRR:$d - ". ($TOT-$d)." {$ko}M +{$added}M\n";}
			$s=0;
		}
		unset($ligne);

	}

	return false;
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

function backup_hourly_table($tablename){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$tar=$unix->find_program("tar");
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose --force $q->database ";
	$container="/home/artica/squid/backup-statistics/$tablename.sql";
	if(is_file($container)){return;}
	$cmdline="$mysqldump_prefix$tablename >$container";
	events_repair($cmdline);
	if($GLOBALS["VERBOSE"]){echo "\n*******\n$cmdline\n*******\n";}
	exec($cmdline,$resultsZ);
		
	if(!$unix->Mysql_TestDump($resultsZ,$container)){
		
	events_repair("Fatal Error: day: Dump failed $tablename");
	ufdbguard_admin_events("Fatal Error: day: Dump failed $tablename",__FUNCTION__,__FILE__,__LINE__,"backup");return;}
	$size=@filesize($container);
	chdir("/home/artica/squid/backup-statistics");
		
	$cmdline="$tar cfz $container.tar.gz $container 2>&1";
	$resultsZ=array();
	exec($cmdline,$resultsZ);
	if($GLOBALS["VERBOSE"]){while (list ($a, $b) = each ($resultsZ)){echo "Compress: `$b`\n";}}
	
	if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
		@unlink($container);
		@unlink("$container.tar.gz");
		return ;
	}
	
	@unlink($container);
	
}




function restore_from_backup_statistics(){
	$GLOBALS["VERBOSE"]=true;
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_to_hour_tables();
	$squid_stats_tools->not_categorized_day_scan();
	///home/artica/squid/backup-statistics
}

?>