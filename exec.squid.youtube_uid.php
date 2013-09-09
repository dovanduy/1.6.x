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

if($argv[1]=="--reset"){youtube_reset();exit;}
if($argv[1]=="--all"){youtube_all();exit;}

build_youtube();


function build_youtube(){
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
		return;
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("tables_day", "youtube_uid")){$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `youtube_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `youtube_uid` )");}
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate FROM `tables_day` WHERE youtube_uid=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;}
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "No results...\n";}
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." results...\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$date=$ligne["zDate"];
		$time=strtotime($date." 00:00:00");
		$tablename=$ligne["tablename"];
		$but=null;
		$hourtable="youtubeday_".date("Ymd",$time);
		if(!$q->TABLE_EXISTS($hourtable)){
			if($GLOBALS["VERBOSE"]){echo "$hourtable no such table\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$hourtable ->macToUid()\n";}
		macToUid($hourtable);
		if($GLOBALS["VERBOSE"]){echo "$hourtable ->youtube_uid_from_hourtable($hourtable,$time)\n";}
		if(youtube_uid_from_hourtable($hourtable,$time)){
			$q->QUERY_SQL("UPDATE tables_day SET youtube_uid=1 WHERE tablename='$tablename'");
			continue;
		}else{
			if($GLOBALS["VERBOSE"]){echo "Return false for $hourtable injection\n";}
		}
	}
	
}

function youtube_reset(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET youtube_uid=0 WHERE youtube_uid=1");
	build_youtube();
}

function youtube_uid_from_hourtable($tablename,$time){
	$zdate=date("Y-m-d",$time);
	$q=new mysql_squid_builder();
	$sql="SELECT uid,youtubeid,zDate, SUM(hits) as hits FROM $tablename GROUP BY uid,zDate,youtubeid HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return false;}
	$youtube=new YoutubeStats();

	$c=0;
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "$sql --> No rows\n";}return true;
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "$tablename -> ".mysql_num_rows($results)." entries\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		$uid=$ligne["uid"];
		$hits=$ligne["hits"];
		$youtubeid=trim($ligne["youtubeid"]);
		$category=$youtube->youtube_category($youtubeid);
		if($GLOBALS["VERBOSE"]){echo "$youtubeid -> $category\n";}
		
		$md5=md5("$uid$zdate$youtubeid$category");
		
		$UIDS[$uid]=true;
		$uid=mysql_escape_string2($uid);
		$category=mysql_escape_string2($category);
		$f[$uid][]="('$md5','$zdate','$youtubeid','$category','$hits')";
		if($c>1000){
			if(!youtube_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "youtube_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
			$c=0;
			$f=array();
			continue;
		}
		
		if(count($f)>500){
			if(!youtube_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "youtube_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
			$f=array();
				
		}
	
	}
	
	if(count($f)>0){
		if(!youtube_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "youtube_uid_parse_array return false in line ".__LINE__."\n";}
			return false;}
		
	}
	


	return true;

}

function youtube_uid_parse_array($array){
	$q=new mysql_squid_builder();
	while (list ($uid, $rows) = each ($array) ){
		$uidtable="youtube_".$q->uid_to_tablename($uid);

		//$f[$uid][]="('$md5','$zdate','$youtubeid','$category','$hits')";
		
		$sql="CREATE TABLE IF NOT EXISTS `$uidtable` ( 
		`zmd5` varchar(90)  NOT NULL, 
		`zDate` date  NOT NULL, 
		`hits`  BIGINT(100)  NOT NULL,
		`youtubeid` varchar(90)  NOT NULL,
		`category` varchar(255), 
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`), 
		KEY `hits` (`hits`),
		KEY `familysite` (`youtubeid`),
		KEY `category` (`category`) )  
		ENGINE = MYISAM;";
		
		$q->QUERY_SQL($sql);
		$sql="INSERT IGNORE INTO `$uidtable` (zmd5,zDate,youtubeid,category,hits) VALUES ".@implode(',', $rows);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}
			return false;
		}

	}

	return true;

}





function macToUid($tablename){
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}
		$array[$ligne["MAC"]]=$ligne["uid"];
	}	
	while (list ($mac, $uid) = each ($array) ){
		$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");
	}
	
	if($GLOBALS["VERBOSE"]){echo "macToUid($tablename) Done\n";}
}

function youtube_all(){
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `youtube_all` (
	`zmd5` varchar(40)  NOT NULL,
	`zDate` date  NOT NULL,
	`hits`  BIGINT(100)  NOT NULL,
	`youtubeid` varchar(20)  NOT NULL,
	`category` varchar(128),
	`uid` varchar(128),
	`MAC` varchar(20),
	PRIMARY KEY (`zmd5`),
	KEY `zDate` (`zDate`),
	KEY `hits` (`hits`),
	KEY `youtubeid` (`youtubeid`),
	KEY `category` (`category`),
	KEY `uid` (`uid`),
	KEY `MAC` (`MAC`)
	)
	ENGINE = MYISAM;";
	
	macToUid("youtube_dayz");
	macToUid("youtube_all");
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);	
	
	
	if(!$q->FIELD_EXISTS("tables_day", "youtube_all")){$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `youtube_all` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `youtube_uid` )");}
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate FROM `tables_day` WHERE youtube_all=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;}
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "No results...\n";}
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." results...\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$date=$ligne["zDate"];
		$time=strtotime($date." 00:00:00");
		$tablename=$ligne["tablename"];
		$but=null;
		$hourtable="youtubeday_".date("Ymd",$time);
		if(!$q->TABLE_EXISTS($hourtable)){
			if($GLOBALS["VERBOSE"]){echo "$hourtable no such table\n";}
			continue;
		}	
		
		if(youtube_all_from_hourtable($hourtable)){
			$q->QUERY_SQL("UPDATE tables_day SET youtube_all=1 WHERE tablename='$tablename'");
			continue;			
			
		}
	}
}

function youtube_all_from_hourtable($tablename){
	$q=new mysql_squid_builder();
	
	
	
	$sql="SELECT SUM(hits) as hits,zDate,uid,youtubeid,MAC 
	FROM `$tablename` 
	GROUP BY zDate,uid,youtubeid,MAC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;}
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "No results...\n";}
		return true;
	}
	
	$prefix="INSERT IGNORE INTO `youtube_all` (zmd5,hits,zDate,uid,MAC,youtubeid,category) VALUES ";
	$youtube=new YoutubeStats();
	if($GLOBALS["VERBOSE"]){echo mysql_num_rows($results)." results...\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$md5=md5(serialize($ligne));
		$ligne["uid"]=mysql_escape_string2($ligne["uid"]);
		$category=mysql_escape_string2($youtube->youtube_category($ligne["youtubeid"]));
		$f[]="('$md5','{$ligne["hits"]}','{$ligne["zDate"]}','{$ligne["uid"]}','{$ligne["MAC"]}','{$ligne["youtubeid"]}','$category')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$q->ok){return false;}
		}
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		$f=array();
		if(!$q->ok){return false;}
	}

	return true;
}




?>	