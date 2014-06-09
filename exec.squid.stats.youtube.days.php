<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["ONLYHOURS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--onlyhours#",implode(" ",$argv),$re)){$GLOBALS["ONLYHOURS"]=true;}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
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
if($argv[1]=="--all"){process_all_tables();exit;}
if($argv[1]=="--xtime"){start($argv[2]);exit;}
if($argv[1]=="--youtube-dayz"){youtube_dayz(true);exit;}

start();
function start($xtime=0){
	$dayFilter=0;
	if($xtime>0){
		$dayFilter=date("Ymd",$xtime);
	}
	
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$xtime.pid";
	$pidTime="/etc/artica-postfix/pids/YoutubeByHour.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		die();
	}
	
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	
	if(isset($GLOBALS["youtube_days_executed"])){return;}
	$GLOBALS["youtube_days_executed"]=true;
	$q=new mysql_squid_builder();
	$timekey=date('YmdH');
	$Today=date("Y-m-d H");
	$currenttable="youtubehours_$timekey";
	$LIST_TABLES_YOUTUBE_HOURS=$q->LIST_TABLES_YOUTUBE_HOURS();
	
	youtube_events("LIST_TABLES_YOUTUBE_HOURS = ".count($LIST_TABLES_YOUTUBE_HOURS));
	
	while (list ($tablesource, $value) = each ($LIST_TABLES_YOUTUBE_HOURS) ){
		if($tablesource==$currenttable){continue;}
		$tablesourcetime=$q->TIME_FROM_YOUTUBE_HOUR_TABLE($tablesource);
		if(date("Y-m-d H",$tablesourcetime)==$Today){continue;}
		if($dayFilter>0){
			if(date("Ymd",$tablesourcetime)<>$dayFilter){continue;}
		}
		
		youtube_events("Processing Youtube table $tablesource", __LINE__);
		if($q->COUNT_ROWS($tablesource)==0){$q->QUERY_SQL("DROP TABLE `$tablesource`");continue;}
		if(!_youtube_days($tablesource)){continue;}
		$q->QUERY_SQL("DROP TABLE $tablesource");
	}
	
	if(count($GLOBALS["YOUTUBE_IDS"])>0){
		_youtube_ids();
	}
	
	if(!$GLOBALS["ONLYHOURS"]){
		youtube_events("youtube_count()");
		youtube_count();
		youtube_events("youtube_dayz()");
		youtube_dayz();
	}
	

}

function _youtube_ids(){
	
	while (list ($youtubeid, $line) = each ($GLOBALS["YOUTUBE_IDS"])){
		$f[]="('$youtubeid')";
	}
	
	if(count($f)>0){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("INSERT IGNORE INTO youtube_objects (`youtubeid`) VALUES ".@implode(",", $f));
	}
	
}


function _youtube_days($tablesource){
	$q=new mysql_squid_builder();
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') as tdate,DATE_FORMAT(zDate,'%Y-%m-%d') as tdate2 FROM $tablesource LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;return false;}
	if(trim($ligne["tdate"])==null){return false;}
	
	$tabledesc="youtubeday_{$ligne["tdate"]}";
	$zDay=$ligne["tdate2"];
	if(!$q->check_youtube_day($ligne["tdate"])){return false;}
	$sql="SELECT COUNT(*) as hits,DATE_FORMAT(zDate,'%H') as hour,ipaddr,hostname,uid,MAC,account,youtubeid
	FROM $tablesource GROUP BY hour,ipaddr,hostname,uid,MAC,account,youtubeid";
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `$tablesource`",__FUNCTION__,__FILE__,__LINE__,"stats");return false;}
	$f=array();
	$prefix="INSERT IGNORE INTO $tabledesc (`zmd5`,`zDate`,`hour`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`youtubeid`,`hits`) VALUES ";
	
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$md5=md5(serialize($ligne));
	$sql="('$md5','$zDay','{$ligne["hour"]}','{$ligne["ipaddr"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["account"]}','{$ligne["youtubeid"]}','{$ligne["hits"]}')";
		$GLOBALS["YOUTUBE_IDS"][$ligne["youtubeid"]]=true;
		youtube_events("$sql",__LINE__);
		$f[]=$sql;
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			youtube_events("$q->mysql_error",__LINE__);
			return false;
		}
	}
	return true;
}

function youtube_events($text,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/youtube.inject.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid/".basename(__FILE__)."] $text [Line:$line]\n";
	@fwrite($h,$line);
	@fclose($h);
	
	
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] [".basename(__FILE__)."] $text";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";}
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);
	
	
	
	

}

function youtube_count(){

	$q=new mysql_squid_builder();
	$LIST_TABLES_YOUTUBE_DAYS=$q->LIST_TABLES_YOUTUBE_DAYS();
	while (list ($tablesource, $value) = each ($LIST_TABLES_YOUTUBE_DAYS) ){
		$sql="SELECT SUM(hits) as thits,youtubeid FROM $tablesource GROUP BY youtubeid";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				
			if(isset($YTBE[$ligne["youtubeid"]])){
				$YTBE[$ligne["youtubeid"]]=$YTBE[$ligne["youtubeid"]]+$ligne["thits"];
				continue;
			}
				
			$YTBE[$ligne["youtubeid"]]=$ligne["thits"];
				
		}

	}

	while (list ($youtubeid, $count) = each ($YTBE) ){
		$sql="UPDATE youtube_objects SET hits=$count WHERE youtubeid='$youtubeid'";
		$q->QUERY_SQL($sql);
	}


}

function youtube_dayz($aspid=false){
	
	$unix=new unix();
	if($aspid){
		
		
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".youtube_dayz.pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			die();
		}
	}
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());		
		
		
		
	
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix
	FROM tables_day WHERE youtube_dayz=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	
	
	if(!$q->ok){
		if(preg_match("#Unknown column#i", $q->mysql_error)){
			$q->CheckTables();
			$results=$q->QUERY_SQL($sql);
		}

		if(!$q->ok){
			writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}
	
	youtube_events("youtube_dayz(): ".mysql_num_rows($results)." rows");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$sourcetable="youtubeday_$suffix";
		if( _youtube_dayz($sourcetable) ){
			$q->QUERY_SQL("UPDATE tables_day SET youtube_dayz=1 WHERE tablename='$tablename'");
		}
	}
}

function _youtube_dayz($sourcetable){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($sourcetable)){return true;}
	$sql="SELECT zDate,ipaddr,hostname,uid,MAC,account,youtubeid,SUM(hits) as hits FROM $sourcetable
	GROUP BY zDate,ipaddr,hostname,uid,MAC,account,youtubeid";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `$sourcetable`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	youtube_events("_youtube_dayz(): $sourcetable ".mysql_num_rows($results)." rows");
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["zDate"]}','{$ligne["hits"]}','{$ligne["ipaddr"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["youtubeid"]}')";


	}

	$prefix="INSERT IGNORE INTO youtube_dayz (zDate,hits,ipaddr,hostname,uid,MAC,youtubeid) VALUES ";

	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			writelogs_squid("Fatal: $q->mysql_error on `$sourcetable`",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}

	}
	return true;

}