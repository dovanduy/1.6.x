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
$GLOBALS["OUTPUT"]=true;
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');


$pidfile="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.pid";
$pidTime="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.time";

if($argv[1]=="--current_access"){current_access_db();die();}
if($argv[1]=="--access"){access_db();die();}
if($argv[1]=="--month"){access_dbmonth();die();}



if($GLOBALS["VERBOSE"]){echo "TimeFile:$pidTime\n";}
$unix=new unix();

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." instances:".count($pids)."\n";}
if(count($pids)>2){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";}
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." killing $pid\n";}
		unix_system_kill_force($pid);
	}

}




if(!$GLOBALS["FORCE"]){
	if($unix->file_time_min($pidTime)<15){die();}
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){die();}
}

@unlink($pidTime);
@file_put_contents($pidTime, time());
@file_put_contents($pidfile, getmypid());

access_db();
$php5=$unix->LOCATE_PHP5_BIN();
$xtime=time();
$q=new mysql_squid_builder();
$q->TablePrimaireHour(date("YmdH",$xtime));
$q->check_youtube_hour(date("YmdH",$xtime));
$q->check_SearchWords_hour(date("YmdH",$xtime));
$q->check_quota_hour(date("YmdH",$xtime));

$f[]="#!/bin/sh";
$f[]="export LC_ALL=C";
$f[]="$php5 ".__FILE__." >/dev/null 2>&1";
$f[]="";
@file_put_contents("/etc/cron.hourly/SquidHourlyTables.sh", @implode("\n",$f));
@chmod("/etc/cron.hourly/SquidHourlyTables.sh",0755);
unset($f);


function tests($fullpath){
	$berekley=new parse_berekley_dbs();
	$filename=basename($fullpath);
	preg_match("#([0-9]+)_#", $filename,$re);
	$xre=$re[1];
	$xtime=$berekley->TIME_FROM_HOUR_INT($re[1]);
	
	print_r($berekley->ACCESS_PARSE_DB($fullpath, $xtime));
	
}


function current_access_db(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.current_access_db.pid";
	$pidTime="/etc/artica-postfix/pids/exec.squid.hourly.tables.php.current_access_db.time";
	
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidTime)<10){return;}
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){return;}
	}
	
	@unlink($pidTime);@file_put_contents($pidTime, time());
	
	@file_put_contents($pidfile, getmypid());
	
	$file="/var/log/squid/".date("YmdH")."_dbaccess.db";
	$berekley=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	$sql=$berekley->ACCESS_PARSE_TABLE_STRING("HOUR_RTT");
	if(!$q->QUERY_SQL($sql)){return;}
	if(!is_file($file)){return;}
	$xtime=time();
	$array=$berekley->ACCESS_PARSE_DB($file, $xtime);
	$q->QUERY_SQL("TRUNCATE TABLE HOUR_RTT");
	if(!$array){return;}
	$q->QUERY_SQL($berekley->ACCESS_PARSE_TABLE_PREFIX("HOUR_RTT")." ".@implode(",", $array));
}


function access_db(){
	
	$unix=new unix();
	$Currentfile=date("YmdH")."_dbaccess.db";
	
	$f=$unix->DirFiles("/var/log/squid","[0-9]+_dbaccess\.db");
	$export_path="/home/artica/squid/dbExport";
	@mkdir($export_path,0755,true);
	$berekley=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	
	while (list ($filename, $none) = each ($f) ){
		
		preg_match("#([0-9]+)_#", $filename,$re);
		$FullPath="/var/log/squid/$filename";
		$xdate=$re[1];
		$xtime=$berekley->TIME_FROM_HOUR_INT($xdate);
		echo "$filename ( $xdate ) ".date("Y-m-d H:i:s",$xtime)."\n";
		if(date("Y-m-d",$xtime)=="1970-01-01"){
			if($GLOBALS["VERBOSE"]){echo "1970-01-01 !!!\n";}
			continue;}
		
		$tablename=date("Ymd",$xtime)."_daccess";
		
		$sql=$berekley->ACCESS_PARSE_TABLE_STRING($tablename);
		if(!$q->QUERY_SQL($sql)){
			if($GLOBALS["VERBOSE"]){echo "$tablename $q->mysql_error\n";}
			return;
		}
		
		if($filename==$Currentfile){
			$q->QUERY_SQL("TRUNCATE TABLE $tablename");
		}
		
		
		$array=$berekley->ACCESS_PARSE_DB($FullPath, $xtime);
		if(!$array){
			if($GLOBALS["VERBOSE"]){echo "$FullPath not an array!\n";}
			continue;
		}
		$q->QUERY_SQL($berekley->ACCESS_PARSE_TABLE_PREFIX($tablename)." ".@implode(",", $array));
		if(!$q->ok){continue;}
		if($filename==$Currentfile){continue;}
		
		
		if(!@copy("/var/log/squid/$filename", "$export_path/$filename")){continue;}
		@unlink("/var/log/squid/$filename");
	}
	
	access_dbmonth();
}

function access_dbmonth(){
	
	$q=new mysql_squid_builder();
	$array=$q->LIST_TABLES_ACCESSDB_DAY();
	$current=date("Ymd")."_daccess";
	
	while (list ($tablename, $none) = each ($array) ){
		if($tablename==$current){continue;}
		$time=$q->TIME_FROM_DAY_TABLE($tablename);
		$day=date("Y-m-d",$time);
		$month=date("Ym",$time);
		echo "$tablename -$day -$month\n";
		$monthtable="{$month}_maccess";
		
		//zDate      | familysite                  | category | hits | size       | hour | uid               | ipaddr        | MAC
		if(_access_dbmonth($tablename,$monthtable)){
			$q->QUERY_SQL("DROP TABLE $tablename");
		}
		
	}
	
	

}

function _access_dbmonth($sourcetable,$monthtable){
	$berekley=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	$sql=$berekley->ACCESS_PARSE_TABLE_STRING($monthtable);
	if(!$q->QUERY_SQL($sql)){
		if($GLOBALS["VERBOSE"]){echo "$monthtable $q->mysql_error\n";}
		return;
	}
	
	$results=$q->QUERY_SQL("SELECT zDate,familysite,category,hour,uid,ipaddr,MAC,SUM(hits) as hits, SUM(size) as size
	FROM $sourcetable GROUP BY zDate,familysite,category,hour,uid,ipaddr,MAC");
	$f=array();
	
	if(!$q->ok){return false;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$familysite=mysql_escape_string2($ligne["familysite"]);
		$uid=trim($ligne["uid"]);
		$MAC=$ligne["MAC"];
		$ipaddr=$ligne["ipaddr"];
		if($uid==null){ $uid=$q->MacToUid($MAC); }
		if($uid==null){ $uid=$q->IpToUid($ipaddr); }
		
		
		$uid=mysql_escape_string2($ligne["uid"]);
		$category=mysql_escape_string2($ligne["category"]);
		$zDate=$ligne["zDate"];
		$MAC=$ligne["MAC"];
		
		$hour=$ligne["hour"];
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$f[]="('$md5','$zDate','$hour','$familysite','$category','$uid','$MAC','$ipaddr','$hits','$size')";
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `$monthtable` 
		(`zmd5`,`zDate`,`hour`,`familysite`,`category`,`uid`,`MAC`,`ipaddr`,`hits`,`size`) VALUES
		".@implode(",", $f)	);
		if(!$q->ok){return false;}
		
	}
	
	return true;
	
}





