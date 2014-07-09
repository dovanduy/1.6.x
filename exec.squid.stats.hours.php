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



if($argv[1]=="--clean-empty"){clean_empty_tables();exit;}
if($argv[1]=="--macscan"){macscan();exit;}

$q=new mysql_squid_builder();
if(!$q->ifStatisticsMustBeExecuted()){if($GLOBALS["VERBOSE"]){echo "This is not intended to build statistics - ifStatisticsMustBeExecuted -\n";}die();}


if($argv[1]=="--repair"){repair_hours();exit;}
if($argv[1]=="--restore"){restore_from_backup_statistics();exit;}




tables_hours();

function ToSyslog($text){
	if(!function_exists("syslog")){return;}
	$file=basename($file);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}

function macscan(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["FORCE"]){ToSyslog("macscan(): Executed in --force mode");}
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			ToSyslog("macscan(): already executed pid $pid");
			return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<30){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	if($GLOBALS["FORCE"]){ToSyslog("macscan(): start analyze MAC addresses");}
	$CACHE_FILE="/etc/artica-postfix/".basename(__FILE__).".cache";
	$cachetime=$unix->file_time_min($CACHE_FILE);
	if($cachetime<1440){$CACHE=unserialize(base64_decode(@file_get_contents($CACHE_FILE)));}
	
	
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	$sql="SELECT * FROM macscan";
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		ToSyslog("Aborted task with a MySQL error:{$GLOBALS["Q"]->mysql_error}");
		return;
	}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ipaddr=$ligne["ipaddr"];
		$MAC=$ligne["MAC"];
		if(isset($CACHE[$MAC])){
			$GLOBALS["Q"]->QUERY_SQL("DELETE FROM macscan WHERE MAC='$MAC'");
			continue;
		}
		$hostname=gethostbyaddr($ipaddr);
		$cmp=new computers();
		$uid=$cmp->ComputerIDFromMAC($MAC);
		$cmp->ComputerIP=$ipaddr;
		if($uid==null){
			$cmp=new computers("$hostname$");
			$cmp->ComputerRealName=$hostname;
			$cmp->ComputerMacAddress=$MAC;
			$cmp->Add();
		}
		$CACHE[$MAC]=true;
		$GLOBALS["Q"]->QUERY_SQL("DELETE FROM macscan WHERE MAC='$MAC'");
		
		
	}
	
	@file_put_contents($CACHE_FILE, base64_encode(serialize($CACHE)));
	
}


function repair_hours(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$sock=new sockets();
	$EnableImportOldSquid=$sock->GET_INFO("EnableImportOldSquid");
	$ArticaProxyStatisticsBackHourTables=$sock->GET_INFO("ArticaProxyStatisticsBackHourTables");
	if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
	if(!is_numeric($ArticaProxyStatisticsBackHourTables)){$ArticaProxyStatisticsBackHourTables=1;}
	
	if($EnableImportOldSquid==1){
		if(!CheckIfAllImported()){return false;}
	}
	
	events_repair("Starting L:".__LINE__);
	$q=new mysql_squid_builder();
	$CurrentHourTable="squidhour_".date("YmdH");
	events_repair("Find hours tables...L: ".__LINE__);
	
	$tables=$q->LIST_TABLES_HOURS_TEMP();
	$c=0;
	$t=time();
	events_repair("Find hours tables done ". count($tables)." table(s)... L: ".__LINE__);
	
	while (list ($table, $none) = each ($tables) ){
		if($table==$CurrentHourTable){events_repair("SKIP `$table`... L: ".__LINE__);continue;}
		events_repair("Analyze `$table`... L: ".__LINE__);
		if(!preg_match("#squidhour_([0-9]+)#",$table,$re)){events_repair("No match `$table` abort... L: ".__LINE__);continue;}
		
		
		$hour=$re[1];
		$year=substr($hour,0,4);
		$month=substr($hour,4,2);
		$day=substr($hour,6,2);
	
		if($GLOBALS["VERBOSE"]){echo "_table_hours_perform($table)\n";}
		if(_table_hours_perform($table)){
			$c++;
			$took=$unix->distanceOfTimeInWords($t,time());
			if($ArticaProxyStatisticsBackHourTables==1){backup_hourly_table($table);}
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
	
}

function cache_hours(){
	// comprime les tables horaires de mise en cache en table jour.
	$sock=new sockets();
	$ArticaProxyStatisticsBackHourTables=$sock->GET_INFO("ArticaProxyStatisticsBackHourTables");
	if(!is_numeric($ArticaProxyStatisticsBackHourTables)){$ArticaProxyStatisticsBackHourTables=1;}
	$q=new mysql_squid_builder();
	$prefix=date("YmdH");
	
	
	
}



function tables_hours(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$RepairTimefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".Repair.time";
	
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){
			if($GLOBALS["VERBOSE"]){echo "Only each 60mn - current {$timeexec}mn, use --force to bypass\n";}
			
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$sock=new sockets();
	$EnableImportOldSquid=$sock->GET_INFO("EnableImportOldSquid");
	$ArticaProxyStatisticsBackHourTables=$sock->GET_INFO("ArticaProxyStatisticsBackHourTables");
	if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
	if(!is_numeric($ArticaProxyStatisticsBackHourTables)){$ArticaProxyStatisticsBackHourTables=1;}
	
	
	
	
	if($EnableImportOldSquid==1){
		if(!CheckIfAllImported()){return false;}
	}
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	$prefix=date("YmdH");
	
	if($GLOBALS["VERBOSE"]){echo "**********\nsquid_stats_tools->check_sizehours() in line ".__LINE__."\n**********\n";}
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_sizehours();
	$squid_stats_tools->check_cachehours();
	if($GLOBALS["VERBOSE"]){echo "**********\nDone\n**********\n";}
	
	$currenttable="squidhour_$prefix";
	
	if($GLOBALS["VERBOSE"]){echo "Current Table: $currenttable\n";}
	
	$tablesBrutes=$GLOBALS["Q"]->LIST_TABLES_WORKSHOURS();
	
	
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		if($tablename==$currenttable){
			if($GLOBALS["VERBOSE"]){echo "Skip table: $tablename\n";}
			continue;
		}
		
		$t=time();
		$q=new mysql_squid_builder();
		if($GLOBALS["VERBOSE"]){echo "_table_hours_perform($tablename)\n";}
		
		ToSyslog("Parsing table `$tablename`");
		
		if(_table_hours_perform($tablename)){
			$Entries=FormatNumber($q->COUNT_ROWS($tablename));
			$took=$unix->distanceOfTimeInWords($t,time());
			ToSyslog("Parsing table `$tablename` took: $took");
			if($GLOBALS["VERBOSE"]){echo "Remove table: $tablename\n";}
			if($Entries>0){
				stats_admin_events(2, "$tablename, $Entries entrie(s) Took $took", "",__FILE__,__LINE__);
			}
			
			if($ArticaProxyStatisticsBackHourTables==1){backup_hourly_table($tablename);}
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablename`");
	
		}
	}
	
$RepairTime=$unix->file_time_min($RepairTimefile);
if($RepairTime>180){
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->NoCategorize=true;
	$squid_stats_tools->check_to_hour_tables();
	@unlink($RepairTimefile);
	@file_put_contents($RepairTimefile, time());
}

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}


function _table_hours_perform($tablename){
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	if(!preg_match("#squidhour_([0-9]+)#",$tablename,$re)){
		writelogs_squid("NOT AN HOUR TABLE `$tablename`",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;}
	$hour=$re[1];
	$year=substr($hour,0,4);
	$month=substr($hour,4,2);
	$day=substr($hour,6,2);
	$compressed=false;
	$f=array();
	$dansguardian_table="dansguardian_events_$year$month$day";
	$accounts=$GLOBALS["Q"]->ACCOUNTS_ISP();
	
	if(!$GLOBALS["Q"]->Check_dansguardian_events_table($dansguardian_table)){return false;}
	$sql="SELECT COUNT(ID) as hits,SUM(QuerySize) as QuerySize,DATE_FORMAT(zDate,'%Y-%m-%d %H:00:00') as zDate,sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,hostname FROM $tablename GROUP BY sitename,uri,TYPE,REASON,CLIENT,uid,remote_ip,country,cached,MAC,zDate,hostname";


	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
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

	$SUM=mysql_num_rows($results);
	$d=0;

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($value);$zmd[]=$value;}

		$zMD5=md5(@implode("",$zmd));
		$accountclient=null;
		if(isset($accounts[$ligne["CLIENT"]])){$accountclient=$accounts[$ligne["CLIENT"]];}
		$d++;
		
		$uid=$ligne["uid"];
		if($uid==null){$uid=$GLOBALS["Q"]->MacToUid($ligne["MAC"]);if(is_numeric($uid)){$uid=null;}}
		if($uid==null){$uid=$GLOBALS["Q"]->IpToUid($ligne["CLIENT"]);if(is_numeric($uid)){$uid=null;}}	
		$uid=mysql_escape_string2($uid);
		
		$hostname=$ligne["hostname"];
		if($hostname==null){$hostname=$GLOBALS["Q"]->MacToHost($ligne["MAC"]);if(is_numeric($uid)){$uid=null;}}
		if($hostname==null){$hostname=$GLOBALS["Q"]->IpToHost($ligne["CLIENT"]);if(is_numeric($uid)){$uid=null;}}
		$hostname=mysql_escape_string2($hostname);
		
		
		
		$f[]="('{$ligne["sitename"]}','{$ligne["uri"]}','{$ligne["TYPE"]}','{$ligne["REASON"]}','{$ligne["CLIENT"]}','{$ligne["MAC"]}','{$ligne["zDate"]}','$zMD5','$uid','{$ligne["remote_ip"]}','{$ligne["country"]}','{$ligne["QuerySize"]}','{$ligne["hits"]}','{$ligne["cached"]}','$hostname','$accountclient')";
		if(count($f)>500){
			ToSyslog("$dansguardian_table: $d/$SUM");
			$GLOBALS["Q"]->UncompressTable($dansguardian_table);
			$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}

	}

	if(count($f)>0){
		$GLOBALS["Q"]->UncompressTable($dansguardian_table);
		$GLOBALS["Q"]->QUERY_SQL($prefix.@implode(",", $f));
		if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$dansguardian_table`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$squid_stats_tools=new squid_stats_tools();
		$squid_stats_tools->NoCategorize=true;
		$squid_stats_tools->check_table_days();
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
		stats_admin_events(0, "Fatal Error: day: Dump failed $tablename", "",__FILE__,__LINE__);
	}
	$size=@filesize($container);
	@mkdir("/home/artica/squid/backup-statistics",0755,true);
	chdir("/home/artica/squid/backup-statistics");
	
		
	$cmdline="$tar cfz $container.tar.gz $container 2>&1";
	$resultsZ=array();
	exec($cmdline,$resultsZ);
	if($GLOBALS["VERBOSE"]){while (list ($a, $b) = each ($resultsZ)){echo "Compress: `$b`\n";}}
	
	if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
		stats_admin_events(0, "Test container failed: $container.tar.gz", "",__FILE__,__LINE__);
		@unlink($container);
		@unlink("$container.tar.gz");
		return ;
	}
	
	$size=FormatBytes($size/1024);
	ToSyslog("Hourly Backup ".basename("$container.tar.gz")." $size");
	stats_admin_events(2, "Hourly Backup ".basename("$container.tar.gz")." $size", "",__FILE__,__LINE__);
	@unlink($container);
	
}




function restore_from_backup_statistics(){
	$GLOBALS["VERBOSE"]=true;
	$squid_stats_tools=new squid_stats_tools();
	$squid_stats_tools->check_to_hour_tables();
	$squid_stats_tools->not_categorized_day_scan();
	///home/artica/squid/backup-statistics
}

function clean_empty_tables(){
	
	$unix=new unix();
	///etc/artica-postfix/pids/exec.squid.stats.hours.php.clean_empty_tables.time
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["FORCE"]){ToSyslog("macscan(): Executed in --force mode");}
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			ToSyslog("clean_empty_tables(): already executed pid $pid");
			return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<30){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$q=new mysql_squid_builder();
	$TABLES=$q->LIST_TABLES_HOURS_TEMP();
	$current="squidhour_".date("YmdH");
	while (list ($tablename, $none) = each ($TABLES) ){
		if($tablename==$current){continue;}
		if($q->COUNT_ROWS($tablename)>0){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
		
	}
	
	$TABLES=$q->LIST_TABLES_SIZEHOURS();
	$current="sizehour_".date("YmdH");
	while (list ($tablename, $none) = each ($TABLES) ){
		if($tablename==$current){continue;}
		if($q->COUNT_ROWS($tablename)>0){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	
	}	
	$TABLES=$q->LIST_TABLES_dansguardian_events();
	$current=" dansguardian_events_".date("Ymd");
	while (list ($tablename, $none) = each ($TABLES) ){
		if($tablename==$current){continue;}
		if($q->COUNT_ROWS($tablename)>0){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	
	}	
	
	
}


?>