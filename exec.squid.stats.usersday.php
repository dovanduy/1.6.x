<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SCHEDULED"]=false;
$GLOBALS["RESTART"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];$GLOBALS["SCHEDULED"]=true;}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--scheduled#",implode(" ",$argv))){$GLOBALS["SCHEDULED"]=true;}
	if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}
	
	
	
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

if($argv[1]=="--condensed"){CONDENSED();exit;}
if($argv[1]=="--rebuild"){REBUILD_FULL_MEMBERS();exit;}
if($argv[1]=="--members-year"){MEMBERS_YEAR();exit;}


start();


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;

	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}
	echo "{$pourc}% $text\n";
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.browse-users.progress.php.log";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}

function start($xtime=0){


	build_progress("Loading...",10);
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "Timefile = $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if(!$GLOBALS["SCHEDULED"]){
			if($timeexec<2880){return;}
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	if($GLOBALS["RESTART"]){REBUILD_FULL_MEMBERS();exit;}
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$php=$unix->LOCATE_PHP5_BIN();
	if(!$GLOBALS["VERBOSE"]){
		build_progress("Execute exec.squid.stats.members.hours.php {please_wait}",20);
		$repaircmd="$php /usr/share/artica-postfix/exec.squid.stats.members.hours.php --repair --verbose --force 2>&1";
		system($repaircmd);
	}
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("tables_day", "usersday")){$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `usersday` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `usersday` )");}
	
	$sql="SELECT tablename, usersday,DATE_FORMAT(zDate,'%Y%m%d') AS `suffix` FROM tables_day 
			WHERE usersday=0 AND DAY(zDate)<DAY(NOW()) AND YEAR(zDate) = YEAR(NOW()) AND MONTH(zDate) = MONTH(NOW())";
	

	$q->CheckTables();
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress("MySQL error",110);
		return;
	}
	
	build_progress("tables_day =  ".mysql_num_rows($results)." rows {please_wait}",30);
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." rows\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$worktable="{$ligne["suffix"]}_members";
		$SourceTable="{$ligne["suffix"]}_hour";
		
		if(!$q->TABLE_EXISTS($worktable)){continue;}
		$desttable="{$ligne["suffix"]}_users";
		build_progress("$desttable {please_wait}",30);
		
		if(!perform($worktable, $desttable)){continue;}
		$q->QUERY_SQL("UPDATE tables_day SET usersday=1 WHERE tablename='{$ligne["tablename"]}'");
	}
	
	$currentdestable=date("Ymd")."_users";
	if(!$q->CreateUsersTempTable()){
		build_progress("CreateUsersTempTable failed",110);
		return false;}
	if(!$q->CreateUsersFullTable()){
		build_progress("CreateUsersFullTable failed",110);
		return false;}
	
	
	build_progress("Empty working tables",40);
	$q->QUERY_SQL("TRUNCATE TABLE UsersTMP");
	$q->QUERY_SQL("TRUNCATE TABLE UsersToTal");
	
	$LIST_TABLES_MEMBERS=$q->LIST_TABLES_MEMBERS();
	while (list ($tablename, $ligne) = each ($LIST_TABLES_MEMBERS)){
		$xtime=$q->TIME_FROM_DAY_TABLE($tablename);
		$desttable=date("Ymd",$xtime)."_users";
		build_progress("Scanning $tablename -> $desttable",50);
		if($desttable==$currentdestable){continue;}
		if(!$q->TABLE_EXISTS($desttable)){
			if(!perform($tablename, $desttable)){continue;}
		}
		MONTH_TABLE_FROM_MEMBER_DAY($tablename);
		CONDENSED_perform($desttable);
	}
	
	
	CONDENSED();
	build_progress("Empty table UsersTMP",90);
	$q->QUERY_SQL("TRUNCATE TABLE UsersTMP");
	build_progress("{done}",100);
	
}
function CONDENSED(){
	
	$q=new mysql_squid_builder();
	
	
	
	$q->QUERY_SQL("TRUNCATE TABLE UsersToTal");
	$sql="SELECT SUM(size) as size,SUM(hits) AS `hits`, client,hostname,MAC,uid FROM `year_members` GROUP BY client,hostname,MAC,uid";
	$results=$q->QUERY_SQL($sql);
	
	$prefix="INSERT IGNORE INTO UsersToTal (zMD5,client,hostname,MAC,uid,size,hits) VALUES ";
	
	build_progress("Creating UsersToTal",60);
	$f=array();
	$d=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$client=mysql_escape_string2($ligne["client"]);
		$hostname=mysql_escape_string2($ligne["hostname"]);
		$MAC=mysql_escape_string2($ligne["MAC"]);
		$client=mysql_escape_string2($ligne["client"]);
		$uid=mysql_escape_string2($ligne["uid"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$d++;
		$f[]="('$md5','$client','$hostname','$MAC','$uid','$size','$hits')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){return false;}
			build_progress("Insterting ".count($f)." elements, $d added rows",80);
			$f=array();
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		build_progress("Insterting ".count($f)." elements, $d added rows",80);
		$f=array();
	}
	return true;	
	
	
	
}

function CONDENSED_perform($sourcetable){
	$q=new mysql_squid_builder();
	$xtime=$q->TIME_FROM_DAY_TABLE($sourcetable);
	$zDate=date("Y-m-d",$xtime);
	echo "$sourcetable = $zDate\n";
	
	
	$sql="SELECT SUM(size) as size,SUM(hits) AS `hits`, client,hostname,MAC,uid FROM $sourcetable GROUP BY client,hostname,MAC,uid";
	$results=$q->QUERY_SQL($sql);
	
	$prefix="INSERT IGNORE INTO UsersTMP (zMD5,zDate,client,hostname,MAC,uid,size,hits) VALUES ";
	$f=array();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne).$zDate);
		$client=mysql_escape_string2($ligne["client"]);
		$hostname=mysql_escape_string2($ligne["hostname"]);
		$MAC=mysql_escape_string2($ligne["MAC"]);
		$client=mysql_escape_string2($ligne["client"]);
		$uid=mysql_escape_string2($ligne["client"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$f[]="('$md5','$zDate','$client','$hostname','$MAC','$uid','$size','$hits')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){return false;}
			$f=array();
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		$f=array();
	}
	return true;	
	
}


function perform($tabledata,$nexttable){


	$q=new mysql_squid_builder();
	
	if(!$q->CreateUsersDayTable($nexttable)){return false;}
	
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS($nexttable)){
		$q->QUERY_SQL("DROP TABLE $nexttable");
	}
	$f=array();
	if(!$q->CreateUsersDayTable($nexttable)){return false;}
	
	$sql="SELECT SUM(size) as size, SUM(hits) as hits,client,hostname,uid,MAC FROM $tabledata GROUP BY client,hostname,uid,MAC";
	$prefix="INSERT IGNORE INTO $nexttable (zMD5,client,hostname,MAC,size,hits,uid) VALUES";
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		stats_admin_events(0,"Processing failed with $tabledata",$q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$md5=md5(serialize($ligne));
		$client=mysql_escape_string2(trim(strtolower($ligne["client"])));
		$uid=mysql_escape_string2(trim(strtolower($ligne["uid"])));
		$hostname=mysql_escape_string2(trim(strtolower($ligne["hostname"])));
		$MAC=mysql_escape_string2(trim(strtolower($ligne["MAC"])));
		$f[]="('$md5','$client','$hostname','$MAC','{$ligne["size"]}','{$ligne["hits"]}','$uid')";
	
		if(count($f)>500){
			$q->QUERY_SQL("$prefix" .@implode(",", $f));
			events_tail("Processing ". count($f)." rows");
			if(!$q->ok){events_tail("Failed to process query to $nexttable {$q->mysql_error}");return;}
			$f=array();
		}
	
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("$prefix" .@implode(",", $f));
		events_tail("Processing ". count($f)." rows");
		if(!$q->ok){events_tail("Failed to process query to $nexttable {$q->mysql_error}");return;}
		$f=array();
	}
	
	return true;
}

function repair_visited_from_sources_table($sourcetable,$daytable){
	
	//zMD5                             | sitename                   | familysite        | client        | hostname | account | hour | remote_ip     | MAC | country | size  | hits | uid           | category                      | cached
	$f=array();
	$sql="SELECT  sitename,familysite,SUM(size) as size, SUM(hits) as hits FROM $sourcetable GROUP BY `sitename`,familysite";
	$q=new mysql_squid_builder();
	
	
	$prefix="INSERT IGNORE INTO $daytable (`sitename`,`familysite`,`size`,`hits`) VALUES";
	$q=new mysql_squid_builder();
	if(!$q->CreateVisitedDayTable($daytable)){
		return false;
	}

	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		while (list ($key,$val) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($val); }
		$familysite=$ligne["familysite"];
		$sitename=$ligne["sitename"];
		$f[]="('{$ligne["sitename"]}','$familysite','{$ligne["size"]}','{$ligne["hits"]}')";
	
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){echo $q->mysql_error;return false;}
			$f=array();
		}
		
		
	}
		
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error;return false;}
	}

	return true;
}

function REBUILD_FULL_MEMBERS(){
	
	
	$now=date("Ymd");
	$currentable="{$now}_hour";
	$q=new mysql_squid_builder();
	$TABLES=$q->LIST_TABLES_HOURS();
	$username=trim($username);
	$c=0;
	$MAX=count($TABLES);
	
	
	while (list ($tablesource, $b) = each ($TABLES)){
		
		if($tablesource==$currentable){continue;}
		
		if(!preg_match("#^[0-9]+_hour#", $tablesource)){
			echo "Skipping $tablesource\n";
			continue;
		}
		$time=$q->TIME_FROM_HOUR_TABLE($tablesource);
		$DAY=date("d",$time);
		$YEAR=date("Y",$time);
		$MONTH=date("m",$time);
		
		
		$c++;
		$month_table=date("Ym",$time)."_members";
		echo "$tablesource -> Day: $DAY, Year: $YEAR, Month, $MONTH, table:$month_table\n";
		$MONTH_TABLES[$month_table]["YEAR"]=$YEAR;
		$MONTH_TABLES[$month_table]["MONTH"]=$MONTH;
		build_progress("Parsing $tablesource $c/$MAX",20);
		$NEWTABLES[$tablesource]=$month_table;
		
	}
	
	while (list ($tablesource, $b) = each ($MONTH_TABLES)){
		echo "Cleaning/Creating $tablesource\n";
		if( $q->TABLE_EXISTS($month_table) ){$q->QUERY_SQL("TRUNCATE TABLE $month_table");}else{
			echo "Creating $month_table\n";$q->CreateMembersMonthTable($month_table);}
		
	}
	
	
	$c=0;
	while (list ($tablesource, $month_table) = each ($NEWTABLES)){
		if(!preg_match("#^[0-9]+_hour#", $tablesource)){continue;}
		$c++;
		build_progress("Parsing $tablesource $c/$MAX",20);
		$results=$q->QUERY_SQL("SELECT SUM(hits) as hits,SUM(size) as size, MAC,client,hostname,uid FROM $tablesource GROUP BY MAC,client,hostname,uid");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("Parsing $tablesource $c/$MAX failed",110);
			return;
		}
		$sum=mysql_num_rows($results);
		if($sum==0){echo "$tablesource Nothing!\n";continue;}
		$time=$q->TIME_FROM_HOUR_TABLE($tablesource);
		
		$DAY=date("d",$time);
		echo "Month table:$month_table Day $DAY $sum rows\n";
		$prefix="INSERT IGNORE INTO `$month_table` (zMD5,client,`day`,size,hits,uid,MAC,hostname) VALUES ";
		
		
		
		$row=array();
		$d=0;
		$IP=new IP();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$zMD5=md5(serialize($ligne));
			$client=mysql_escape_string2($ligne["client"]);
			$uid=mysql_escape_string2($ligne["uid"]);
			$MAC=mysql_escape_string2($ligne["MAC"]);
			$hostname=mysql_escape_string2($ligne["hostname"]);
			$size=$ligne["size"];
			$hits=$ligne["hits"];
			if($IP->isValid($uid)){$uid=null;}
			
			$row[]="('$zMD5','$client','$DAY','$size','$hits','$uid','$MAC','$hostname')";
			$d++;
			if(count($row)>500){
				$q->QUERY_SQL($prefix.@implode(",", $row));
				build_progress("Insterting ".count($row)." elements",20);
				echo "Added $d rows\n";
				$row=array();
			}
		}
		
		if(count($row)>0){
			$q->QUERY_SQL($prefix.@implode(",", $row));
			build_progress("Insterting ".count($row)." elements",20);
			echo "Added $d rows\n";
			$row=array();
		}
	}
	
	MEMBERS_YEAR(true);
	CONDENSED(true);
	build_progress("{success}",20);
}

function MONTH_TABLE_FROM_MEMBER_DAY($tablesource){
	$q=new mysql_squid_builder();
	$time=$q->TIME_FROM_HOUR_TABLE($tablesource);
	$DAY=date("d",$time);
	$YEAR=date("Y",$time);
	$MONTH=date("m",$time);
	$month_table=date("Ym",$time)."_members";
	$CurrentTable=date("Ymd")."_members";
	if($CurrentTable==$tablesource){return;}
	$q->CreateMembersMonthTable($month_table);
	
	$results=$q->QUERY_SQL("SELECT SUM(hits) as hits,SUM(size) as size, MAC,client,hostname,uid FROM $tablesource GROUP BY MAC,client,hostname,uid");
	if(!$q->ok){echo $q->mysql_error."\n"; return; }
	
	$sum=mysql_num_rows($results);
	if($sum==0){echo "$tablesource Nothing!\n";return;}
	
	echo "Month table:$month_table Day $DAY $sum rows\n";
	$prefix="INSERT IGNORE INTO `$month_table` (zMD5,client,`day`,size,hits,uid,MAC,hostname) VALUES ";
		
	
	$IP=new IP();
	$d=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		$client=mysql_escape_string2($ligne["client"]);
		$uid=mysql_escape_string2($ligne["uid"]);
		$MAC=mysql_escape_string2($ligne["MAC"]);
		$hostname=mysql_escape_string2($ligne["hostname"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		if($IP->isValid($uid)){$uid=null;}
			
		$row[]="('$zMD5','$client','$DAY','$size','$hits','$uid','$MAC','$hostname')";
		$d++;
		if(count($row)>500){
			$q->QUERY_SQL($prefix.@implode(",", $row));
			build_progress("Insterting ".count($row)." elements",20);
			echo "Added $d rows\n";
			$row=array();
		}
	}
	
	
	
	if(count($row)>0){
		$q->QUERY_SQL($prefix.@implode(",", $row));
		build_progress("Insterting ".count($row)." elements",20);echo "Added $d rows\n";$row=array();
	}	
	
	
}


function MEMBERS_YEAR($aspid=false){
	
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "Timefile = $timefile\n";}
	$pid=@file_get_contents($pidfile);
	
	if(!$aspid){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if(!$GLOBALS["SCHEDULED"]){
			if($timeexec<2880){return;}
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	
	$q=new mysql_squid_builder();
	
	if(!$q->CreateMembersYearTable()){
		echo $q->mysql_error;
		return false;
	}
	
	$MONTH_TABLES=$q->LIST_TABLES_MEMBERS_MONTH();

	echo "Empty table year_members for ".count($MONTH_TABLES). " Month tables\n";
	$q->QUERY_SQL("TRUNCATE TABLE year_members");
	
	
	$MAX=count($MONTH_TABLES);
	$c=0;
	
	while (list ($tablesource, $xzr) = each ($MONTH_TABLES)){
		$c++;
		build_progress("Parsing $tablesource $c/$MAX",40);
		$xtime=$q->TIME_FROM_MONTH_TABLE($tablesource);
		$ouptut_time=date("Y-m-d",$xtime);
		$month=$xzr["MONTH"];
		$year=$xzr["YEAR"];
		echo "$tablesource: $ouptut_time\n";
		
	
		$results=$q->QUERY_SQL("SELECT SUM(hits) as hits,SUM(size) as size, MAC,client,hostname,uid FROM $tablesource GROUP BY MAC,client,hostname,uid");
				if(!$q->ok){
				echo $q->mysql_error."\n";
				build_progress("Parsing $tablesource $c/$MAX failed",110);
				return;
		}
		$sum=mysql_num_rows($results);
		if($sum==0){echo "$tablesource Nothing!\n";continue;}
	
		$prefix="INSERT IGNORE INTO `year_members` (zMD5,client,`month`,`year`,size,hits,uid,MAC,hostname) VALUES ";
	
		$row=array();
		$d=0;
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		$client=mysql_escape_string2($ligne["client"]);
		$uid=mysql_escape_string2($ligne["uid"]);
		$MAC=mysql_escape_string2($ligne["MAC"]);
		$hostname=mysql_escape_string2($ligne["hostname"]);
		$size=$ligne["size"];
			$hits=$ligne["hits"];
			$row[]="('$zMD5','$client','$month','$year','$size','$hits','$uid','$MAC','$hostname')";
				$d++;
				if(count($row)>500){
		$q->QUERY_SQL($prefix.@implode(",", $row));
	
		if(!$q->ok){echo $q->mysql_error."\n";build_progress("Insterting ".count($row)." elements failed",110);return; }
	
		build_progress("Insterting ".count($row)." elements",40);
		echo "Added $d rows\n";
		$row=array();
			}
		}
	
		if(count($row)>0){
				$q->QUERY_SQL($prefix.@implode(",", $row));
				if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("Insterting ".count($row)." elements failed",110);
			return;
			}
			build_progress("Insterting ".count($row)." elements",40);
			echo "Added $d rows\n";
			$row=array();
		}
	
	
		}
	
}







function events_tail($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	if($GLOBALS["VERBOSE"]){echo "$text ($sourceline)\n";}
	writelogs_squid($text,"",__FILE__,$sourceline,"stats",true);

}