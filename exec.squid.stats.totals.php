<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.squid.youtube.inc');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

$GLOBALS["Q"]=new mysql_squid_builder();
if($argv[1]=="--interface"){donnees_interface();exit;}
if($argv[1]=="--repair"){TOTALS_REPAIR();exit;}
if($argv[1]=="--repair-members"){TOTALS_REPAIR_MEMBERS();exit;}
if($argv[1]=="--xtime"){start($argv[2]);exit;}

start();
function start($xtime=0){
	
	
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
	if(count($pids)>5){
		die();
	}
	
	if($xtime==0){
		if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pid=@file_get_contents($pidfile);
		if(!$GLOBALS["FORCE"]){
			if($pid<100){$pid=null;}
			$unix=new unix();
			if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
			$timeexec=$unix->file_time_min($timefile);
			if($timeexec<720){return;}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}	
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($xtime>0){
		$dateRequested=date("Y-m-d",$xtime);
		$dateRequested_sql=" WHERE zDate='$dateRequested'";
		if(SquidStatisticsTasksOverTime()){ 
			stats_admin_events(1,"Statistics overtime... Aborting ( requested for $dateRequested ) ",null,__FILE__,__LINE__); 
			return; 
		}
	}
	
	$sql="SELECT
	DATE_FORMAT(zDate,'%Y%m%d') as tprefix,DATE_FORMAT(zDate,'%Y-%m-%d') as CurDay,tablename FROM tables_day$dateRequested_sql  
	ORDER BY zDate DESC";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
	//bigint(100)
	if($q->FIELD_TYPE("tables_day", "totalBlocked","syslogs")=="bigint(100)"){
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size` `size` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalBlocked` `totalBlocked` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `requests` `requests` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalsize` `totalsize` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size_cached` `size_cached` BIGINT( 255 ) NOT NULL');
	}
	
	if(!$q->FIELD_EXISTS("tables_day", "totalKeyWords")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `totalKeyWords` BIGINT( 255 ) NOT NULL NOT NULL,ADD INDEX ( `totalKeyWords`)");
	}
	if(!$q->FIELD_EXISTS("tables_day", "DangerousCatz")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `DangerousCatz` smallint( 1 ) NOT NULL NOT NULL,ADD INDEX ( `DangerousCatz`)");
	}

	
	if(!$q->ok){echo "$q->mysql_error.<hr>$sql</hr>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$hourtable=$ligne["tprefix"]."_hour";
		if($ligne["tprefix"]==date("Ymd")){continue;}
		$KeyWordsTable="searchwordsD_".$ligne["tprefix"];
		$members_table="{$ligne["tprefix"]}_members";
		$youtube_table="youtubeday_{$ligne["tprefix"]}";
		$myXtime=strtotime($ligne["CurDay"]."00:00:00");
		
		if($q->TABLE_EXISTS($hourtable)){
			$sql="SELECT SUM(size) as tsize, SUM(hits) as thits FROM $hourtable";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$size=$ligne2["tsize"];
			$hits=$ligne2["thits"];
			$sizeL=FormatBytes($size/1024);
			if($GLOBALS["VERBOSE"]){echo "$tablename - $sizeL / $hits $hourtable [ $sql ]\n";}
			$sql="UPDATE tables_day SET totalsize='$size',requests='$hits' WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){
					echo $q->mysql_error."\n";
				}
			}
			
			$sql="SELECT COUNT(`sitename`) as tcount FROM $hourtable WHERE LENGTH(`category`)=0";
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$max=$ligne2["tcount"];
			$sql="UPDATE tables_day SET `not_categorized`=$max WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);	

			
			if(!$q->TABLE_EXISTS($members_table)){continue;}
			$MembersField=which_filter($members_table,true);
			if($GLOBALS["VERBOSE"]){echo "Table members Calculate Members by $MembersField\n";}
			if($MembersField<>null){
				$MembersCount=CalculateElements($members_table,$MembersField);
				$sql="UPDATE tables_day SET `MembersCount`=$MembersCount WHERE tablename='$tablename'";
				if($GLOBALS["VERBOSE"]){echo $sql."\n";}
				$q->QUERY_SQL($sql);
			}				
				
		

		}
		
		if($q->TABLE_EXISTS($youtube_table)){
			$sql="SELECT youtubeid FROM $youtube_table GROUP BY youtubeid";
			$results2=$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$YouTubeHits=mysql_num_rows($results2);
			$sql="UPDATE tables_day SET `YouTubeHits`=$YouTubeHits WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
		}	
		
		
		if($q->TABLE_EXISTS($KeyWordsTable)){
			$sql="SELECT `words` FROM $KeyWordsTable GROUP BY `words`";
			$results2=$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$CountOfWords=mysql_num_rows($results2);
			$sql="UPDATE tables_day SET totalKeyWords='$CountOfWords' WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
		}

	}
	
	

	
}
function CalculateElements($tablename,$groupby){
	$q=new mysql_squid_builder();
	$sql="SELECT $groupby FROM $tablename GROUP BY $groupby";
	$results=$q->QUERY_SQL($sql);
	return mysql_num_rows($results);

}
function which_filter($tablename,$return_fields=false){
	if($GLOBALS["VERBOSE"]){echo "$tablename -> $return_fields\n";}
	$ipfield="client";
	if(preg_match("#^UserSizeD_#", $tablename)){
		$ipfield="ipaddr";
	}

	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "COUNT = $count\n";}
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


		$sql="SELECT COUNT(client) as tcount FROM $tablename WHERE LENGTH(client)>0";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$count=mysql_num_rows($results);
		if($count>1){
			if($GLOBALS["VERBOSE"]){echo "Number of members client key for $count items\n";}
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

function donnees_interface(){
	
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile=dirname(__FILE__)."/ressources/logs/web/SQUID_STATS_GLOBALS_VALUES";
	@mkdir(dirname($timefile),0755,true);
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<30){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT uid FROM members_uid GROUP BY uid";
	
	
	
	$results=$q->QUERY_SQL($sql);
	$CountDeMembers=mysql_num_rows($results);
	
	events("donnees_interface:: members_uid = $CountDeMembers");
	
	
	$ARRAY["CountDeMembers"]=$CountDeMembers;
	
	
	$sql="SELECT AVG(size) as avg FROM `cached_total` WHERE cached=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$ligne["avg"]=$ligne["avg"]/1024;
	$UNIT="KB";
	if($ligne["avg"]>1024){$ligne["avg"]=$ligne["avg"]/1024;$UNIT="MB";}
	$ligne["avg"]=round($ligne["avg"]);
	$ARRAY["AVG_CACHED"]=$ligne["avg"].$UNIT;	

	$current_month=date("Ym");
	$catFamMonth="{$current_month}_catfam";
	if($q->TABLE_EXISTS($catFamMonth)){
		$sql="SELECT COUNT(familysite) as tcount, catfam FROM `$catFamMonth` GROUP BY catfam";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ARRAY["CATFAM"][$ligne["catfam"]]=$ligne["tcount"];
		}
		
	}
	
	
	$ARRAY["DATABASE_INFOS"]=$q->DATABASE_INFOS();
	$ARRAY["TIME"]=time();
	@unlink($timefile);
	@file_put_contents($timefile, serialize($ARRAY));
	@chmod($timefile,0777);
	
}

function events($text){
	
	$common="/var/log/artica-squid-statistics.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] ". basename(__FILE__)." $text\n";
	if($GLOBALS["VERBOSE"]){echo $line;}
	@fwrite($h,$line);
	@fclose($h);
}


function TOTALS_REPAIR($aspid=false){
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "time: $timefile\n";}
		$pid=@file_get_contents($pidfile);
		if(!$GLOBALS["VERBOSE"]){
			if(!$GLOBALS["FORCE"]){
				if($pid<100){$pid=null;}
				$unix=new unix();
				if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
				if(!$GLOBALS["BY_SCHEDULE"]){
					$timeexec=$unix->file_time_min($timefile);
					if($timeexec<1440){return;}
				}
				
				$mypid=getmypid();
				@file_put_contents($pidfile,$mypid);
			}
		}
	}
	
	
	$q=new mysql_squid_builder();

	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	$currentDay=date("Ymd");
	if($GLOBALS["FORCE"]){$q->QUERY_SQL("UPDATE tables_day SET totalsize=0"); }
	
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y%m%d') as tprefix,totalsize,tablename FROM tables_day WHERE totalsize<100");

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["tprefix"]==$currentDay){continue;}
		$quota_day="quotaday_{$ligne["tprefix"]}";
		
		if($q->TABLE_EXISTS($quota_day)){
			$sql="SELECT SUM(size) as tsize FROM `$quota_day`";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$SumSize=$ligne2["tsize"];
			stats_admin_events(1,"Repair: {$ligne["tablename"]} = {$ligne["totalsize"]} $quota_day = $SumSize",null,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} = {$ligne["totalsize"]} $quota_day = $SumSize\n";}
			$q->QUERY_SQL("UPDATE tables_day SET `totalsize`='$SumSize' WHERE tablename='{$ligne["tablename"]}'");
			
		}
	}
	

	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y%m%d') as tprefix,totalKeyWords,tablename FROM tables_day WHERE totalKeyWords=0");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["tprefix"]==$currentDay){continue;}
		$SourceTable="searchwordsD_{$ligne["tprefix"]}";
		if($GLOBALS["VERBOSE"]){echo "**** $SourceTable ****\n";}
		if($q->TABLE_EXISTS($SourceTable)){
			$sql="SELECT `words` FROM `$SourceTable` GROUP BY `words`";
			$results2=$q->QUERY_SQL($sql);
			$SumSize=mysql_num_rows($results2);
			stats_admin_events(1,"Repair: {$ligne["tablename"]} totalKeyWords = {$ligne["totalKeyWords"]} $SourceTable = $SumSize",null,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} = {$ligne["totalKeyWords"]} $SourceTable = $SumSize\n";}
			$q->QUERY_SQL("UPDATE tables_day SET `totalKeyWords`='$SumSize' WHERE tablename='{$ligne["tablename"]}'");
				
		}
	}
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y%m%d') as tprefix,totalKeyWords,tablename FROM tables_day WHERE totalKeyWords=0");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["tprefix"]==$currentDay){continue;}
		$SourceTable="youtubeday_{$ligne["tprefix"]}";
		if($GLOBALS["VERBOSE"]){echo "**** $SourceTable ****\n";}
		if($q->TABLE_EXISTS($SourceTable)){
			$sql="SELECT `youtubeid` FROM `$SourceTable` GROUP BY `youtubeid`";
			$results2=$q->QUERY_SQL($sql);
			$SumSize=mysql_num_rows($results2);
			stats_admin_events(1,"Repair: {$ligne["tablename"]} YouTubeHits: $SourceTable = $SumSize",null,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} $SourceTable = $SumSize\n";}
			$q->QUERY_SQL("UPDATE tables_day SET `YouTubeHits`='$SumSize' WHERE tablename='{$ligne["tablename"]}'");
	
		}
	}	
	
	$results=$q->QUERY_SQL("SELECT zDate,DATE_FORMAT(zDate,'%Y%m%d') as tprefix,MembersCount,tablename FROM tables_day WHERE MembersCount=0 ORDER BY zDate");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablesource="{$ligne["tprefix"]}_members";
		if($ligne["tprefix"]==$currentDay){continue;}
		if($q->TABLE_EXISTS($tablesource)){
			$sql="SELECT CLIENT, uid,MAC,hostname FROM `$tablesource` GROUP BY CLIENT,uid,MAC,hostname";
			$results1=$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q-mysql_error;return;}
			$Sum=mysql_num_rows($results1);
			stats_admin_events(1,"Repair: {$ligne["tablename"]} MembersCount: $SourceTable = $SumSize",null,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} -> $tablesource = $Sum\n";}
			$q->QUERY_SQL("UPDATE tables_day SET `MembersCount`='$Sum' WHERE tablename='{$ligne["tablename"]}'");
	
		}
	}	
	
	
}


function TOTALS_REPAIR_MEMBERS(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET MembersCount=0");
}


//totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day