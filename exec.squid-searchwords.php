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
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

if($argv[1]=="--hour"){searchwords_hour(true);die();}


BuildWeeks();
function BuildWeeks(){
	if($GLOBALS["VERBOSE"]){echo "BuildWeeks(): OK\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=@file_get_contents($pidfile);
	
	$unix=new unix();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		events("Already executed pid $pid since {$time}mn-> DIE");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$timefile=$unix->file_time_min($pidfile);
	if($GLOBALS["VERBOSE"]){echo "Timelock:$timefile Mn\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		if($timefile<10){return;}
	}
	
	
	@unlink($pidfile);
	@file_put_contents($pidfile, time());
	$q=new mysql_squid_builder();
	
	$CurrentTableDay="searchwordsD_".date("Ymd");
	$LIST_TABLES_SEARCHWORDS_DAY=$q->LIST_TABLES_SEARCHWORDS_DAY();
	$sql="SELECT tablename, zDate, DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesuffix,
			YEAR(zDate) as YearNumber,
			WEEK( zDate ) as WeekNumber  FROM tables_day WHERE SearchWordWeek=0 AND DATE_FORMAT( zDate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )";
	$results=$q->QUERY_SQL($sql);
	
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "SearchWordWeek, no table to increment\n";}
		return;
	}	

	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$SourceTable="searchwordsD_".$ligne["tablesuffix"];
		$tablename=$ligne["tablename"];
		
		if($GLOBALS["VERBOSE"]){echo "$tablename -> $SourceTable\n";}
		
		if(!isset($LIST_TABLES_SEARCHWORDS_DAY[$SourceTable])){
			if($GLOBALS["VERBOSE"]){echo "$SourceTable, no such table...\n";}
			continue;
		}
		
		$NexTable="searchwordsW_".$ligne["YearNumber"].$ligne["WeekNumber"];
		if(!$q->check_SearchWords_week($ligne["YearNumber"].$ligne["WeekNumber"])){
			if($GLOBALS["VERBOSE"]){echo "check_SearchWords_week($NexTable) failed\n";}
		}
		
		if(_BuildWeeks($SourceTable,$NexTable)){
			if($GLOBALS["VERBOSE"]){echo "$SourceTable -> $NexTable OK\n";}
			$q->QUERY_SQL("UPDATE tables_day SET SearchWordWeek=".count($GLOBALS[$SourceTable])." WHERE tablename='$tablename'");
		}
		
		if(system_is_overloaded(__FILE__)){
			ufdbguard_admin_events("Overloaded system, aborting task", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
	}
}

function _BuildWeeks($SourceTable,$NexTable){
	
	$sql="SELECT SUM( hits ) as hits, DATE_FORMAT( zDate, '%Y-%m-%d' ) AS tday, ipaddr, hostname, uid, MAC, account, familysite, words
		FROM `$SourceTable`
		GROUP BY tday, ipaddr, uid, MAC, account, familysite, words";
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	if(mysql_num_rows($results)==0){return true;}
	
	$prefix="INSERT IGNORE INTO $NexTable (`zmd5`,`hits`,`familysite`,`day`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`words`) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$hits=$ligne["hits"];
		$zMD5=md5(serialize($ligne));
		$familysite=addslashes($ligne["familysite"]);
		$day=$ligne["tday"];
		$ipaddr=addslashes($ligne["ipaddr"]);
		$hostname=addslashes($ligne["hostname"]);
		$uid=addslashes($ligne["uid"]);
		$MAC=addslashes($ligne["MAC"]);
		$account=$ligne["account"];
		$GLOBALS[$SourceTable][$ligne["words"]]=true;
		$words=addslashes($ligne["words"]);	
		
		$f[]="('$zMD5','$hits','$familysite','$day','$ipaddr','$hostname','$uid','$MAC','$account','$words')";
		
		
	}
	
	
	if(count($f)>0){
		$q->QUERY_SQL("$prefix".@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
			return false;
		}
	}
	
	return true;
	
	
	
}

function UserRTT_SIZE_DAY_inject($array){
	

	
	
	$q=new mysql_squid_builder();
	while (list ($tablename, $rows) = each ($array)){
		if(!$q->CreateUserSizeRTT_day($tablename)){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
		`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
		if(!$q->QUERY_SQL($sql)){
			ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;
		}
		
	}
	
	return true;
	
}

function main_table(){
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	if($unix->file_time_min($timefile)<300){return;}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$q=new mysql_squid_builder();
	$sql="SELECT tablename, zDate, DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesuffix
FROM tables_day
WHERE MembersCount=0
AND DATE_FORMAT( zDate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )";
	$results=$q->QUERY_SQL($sql);
	if(mysql_numrows($results)==0){return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablesuffix=$ligne["tablesuffix"];
		$tablename="UserSizeD_$tablesuffix";
		if(!$q->TABLE_EXISTS($tablename)){continue;}
		$count=main_table_exec("UserSizeD_$tablesuffix");
		if($count>0){
			$q->QUERY_SQL("UPDATE tables_day SET MembersCount=$count WHERE tablename='{$ligne["tablename"]}'");
		}
		
	}
	
	
}

function main_table_exec($tablename){
	
	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	
	$sql="SELECT MAC FROM `$tablename` GROUP BY MAC HAVING LENGTH(MAC)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	

	$sql="SELECT COUNT(ipaddr) as tcount FROM $tablename WHERE LENGTH(ipaddr)>0";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	
	$sql="SELECT COUNT(hostname) as tcount FROM $tablename WHERE LENGTH(hostname)>0";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}


	
}


function UserSizeRTT_oldfiles(){
	
	if (!$handle = opendir("/var/log/artica-postfix/squid-RTTSize")) {
		ufdbguard_admin_events("Fatal: /var/log/artica-postfix/squid-RTTSize no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	
	
	
	$q=new mysql_squid_builder();
	$classParse=new squid_tail();
	$CurrentFile=date("YmdH");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: scanning /var/log/artica-postfix/squid-RTTSize\n";}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if($filename==$CurrentFile){continue;}
		
		$targetFile="/var/log/artica-postfix/squid-RTTSize/$filename";	
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $targetFile\n";}
		$time=filemtime($targetFile);
		$tablesuffix=date("Ymd",$time);
		$tablename="UserSizeD_$tablesuffix";
		if(!$q->CreateUserSizeRTT_day($tablename)){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		
		$RTTSIZEARRAY=unserialize(@file_get_contents($targetFile));
		$Hour=date('H',$time);
		$date=date("Y-m-d H:00:00",$time);
		
		//$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
		//`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
		
		if(count($RTTSIZEARRAY["UID"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`uid`,`size`,`hits`,`hour`) VALUES ";
			
			while (list ($username,$array) = each ($RTTSIZEARRAY["UID"]) ){
					
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$username$date$Hour");
				echo $username." HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$username','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}
		
		if(count($RTTSIZEARRAY["IP"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`ipaddr`,`hostname`,`size`,`hits`,`hour`) VALUES ";
			while (list ($ip,$array) = each ($RTTSIZEARRAY["IP"]) ){
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$ip$date$Hour");
				$hostname=$classParse->GetComputerName($ip);
				echo $ip."/$hostname HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$ip','$hostname','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}
		
		if(count($RTTSIZEARRAY["MAC"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`MAC`,`size`,`hits`,`hour`) VALUES ";
			while (list ($mac,$array) = each ($RTTSIZEARRAY["MAC"]) ){
		
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$mac$date$Hour");
					
				echo "$mac HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$mac','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}	

		@unlink($targetFile);
		
		
	}
	
	
}

function hour_SearchWordTEMP(){
	$prefix=date("YmdH");
	$unix=new unix();
	$CurrentTableDay="searchwordsD_".date("Ymd");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($CurrentTableDay)){
		if($GLOBALS["VERBOSE"]){echo "$CurrentTableDay, no such table\n";}
		return;}
		$sql="SELECT `words` FROM $CurrentTableDay GROUP BY `words`";
		$results=$q->QUERY_SQL($sql);
		$SourceTable=mysql_num_rows($results);
		$currentdate=date("Y-m-d");
		$q->QUERY_SQL("UPDATE tables_day SET SearchWordTEMP=$SourceTable WHERE `zDate`='$currentdate'");
		ufdbguard_admin_events("$SourceTable searched words",__FUNCTION__,__FILE__,__LINE__,"stats");
}

function searchwords_hour($aspid=false){
	if(isset($GLOBALS["searchwords_hour_executed"])){return true;}
	$GLOBALS["searchwords_hour_executed"]=true;
	$unix=new unix();
	$GLOBALS["Q"]=new mysql_squid_builder();
	
	$pidtime="/etc/artica-postfix/pids/exec.squid-searchwords.php.searchwords_hour.time";
	if($GLOBALS["VERBOSE"]){echo "PidTime: $pidtime\n";}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$myfile=basename(__FILE__);
	if($unix->process_exists($pid,$myfile)){
		ufdbguard_admin_events("$pid already running, aborting",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
		
	$timeP=$unix->file_time_min($pidtime);
	if($timeP<30){ events("Main::Line: ".__LINE__." 30Mn minimal current: {$timeP}mn-> DIE - $pidtime"); die(); }	
		
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());
	
	
	$currenttable="searchwords_".date("YmdH");
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	$LIST_TABLES_SEARCHWORDS_HOURS=$GLOBALS["Q"]->LIST_TABLES_SEARCHWORDS_HOURS();
	
	while (list ($num, $tablename) = each ($LIST_TABLES_SEARCHWORDS_HOURS) ){
		if($tablename==$currenttable){
			if($GLOBALS["VERBOSE"]){echo "$tablename -> $currenttable >skip\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$tablename -> $currenttable\n";}
		
		if(searchwords_hour_to_day($tablename)){
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE $tablename");
		}
	}
	hour_SearchWordTEMP();
	
	
	$mysqladmin=$unix->find_program("mysqladmin");
	shell_exec("$mysqladmin -u root -S /var/run/mysqld/squid-db.sock flush-tables >/dev/null 2>&1 &");
	
}

function searchwords_hour_to_day($sourcetable){

	
	$time=$GLOBALS["Q"]->TIME_FROM_SEARCHHOUR_TABLE($sourcetable);
	$hour=date("H",$time);
	if(date("YmdH",$time)==date("YmdH")){
		if($GLOBALS["VERBOSE"]){echo "$sourcetable -> $hour >skip\n";}
		return false;
	}
	
	
	$sql="SELECT COUNT(zmd5) as hits,DATE_FORMAT(zDate,'%Y%m%d') as prefix,
	DATE_FORMAT(zDate,'%Y-%m-%d') as `newdate`,`sitename`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words` 
	FROM `$sourcetable`
	GROUP BY prefix,sitename,ipaddr,hostname,uid,MAC,account,familysite,words,newdate HAVING LENGTH(words)>1";

	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$sourcetable`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}

	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$zmd5=md5(serialize($ligne));
	$sitename=$ligne["sitename"];
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		$words=addslashes(utf8_encode($ligne["words"]));

		$s="('$zmd5','{$ligne["hits"]}','$sitename','{$ligne["newdate"]}','$hour','{$ligne["ipaddr"]}',
		'{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["account"]}','{$ligne["familysite"]}','$words')";
		$f[$ligne["prefix"]][]=$s;


	}

	if(count($f)>0){
		while (list ($index_table, $rows) = each ($f) ){
			$newtable="searchwordsD_$index_table";
			if(!$GLOBALS["Q"]->check_SearchWords_day($index_table)){writelogs_squid("Fatal: Creating $newtable {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			$sql="INSERT IGNORE INTO $newtable (`zmd5`,`hits`,`sitename`,`zDate`,`hour`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`) VALUES ".@implode(",", $rows);
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$newtable`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}

	}
	return true;
}


function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		$text="$text ($sourcefunction::$sourceline)";
	}


	events_tail($text);}

	function events_tail($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		//if($GLOBALS["VERBOSE"]){echo "$text\n";}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);
	}