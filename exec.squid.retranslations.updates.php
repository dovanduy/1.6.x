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
$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--MAC"){updates_retranslation($argv[2],$argv[3]);die();}

function updates_retranslation($MAC,$uid){
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5($MAC.$uid).".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5($MAC.$uid).".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	
	
	
	
	$q=new mysql_squid_builder();
	$sql="UPDATE youtube_all SET uid='$uid' WHERE MAC='$MAC'";
	$q->QUERY_SQL($sql);
	$sql="UPDATE UserAuthDaysGrouped SET uid='$uid' WHERE MAC='$MAC'";
	$q->QUERY_SQL($sql);	
	$sql="UPDATE UserAuthDays SET uid='$uid' WHERE MAC='$MAC'";
	$q->QUERY_SQL($sql);	
	
	


	$TABLES=$q->LIST_TABLES_QUOTADAY();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	$TABLES=$q->LIST_TABLES_QUOTAMONTH();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}	
	
	$TABLES=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}

	$TABLES=$q->LIST_TABLES_USERSIZED();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_YOUTUBE_HOURS();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_YOUTUBE_DAYS();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_YOUTUBE_WEEK();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_SEARCHWORDS_DAY();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_SEARCHWORDS_HOURS();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	$TABLES=$q->LIST_TABLES_MONTH();
	while (list ($tablename, $rows) = each ($TABLES) ){
		$sql="UPDATE `$tablename` SET uid='$uid' WHERE MAC='$MAC'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
}

function websites_uid_to_categories(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_WWWUID();
	if(count($tables)>0){
		while (list ($tablename, $rows) = each ($tables) ){
			if($GLOBALS["VERBOSE"]){echo "\n\n***** TESTING TABLE `$tablename`\n******\n\n";}
			websites_uid_not_categorised(null,$tablename);

		}

	}
}

function websites_uid_from_hourtable($tablename,$time){
	$zdate=date("Y-m-d",$time);
	$q=new mysql_squid_builder();
	$sql="SELECT uid, SUM(size) as size,SUM(hits) as hits,
	familysite,category FROM $tablename GROUP BY uid,familysite
	HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return false;}

	$a=0;
	$c=0;
	if(mysql_num_rows($results)==0){return true;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;$a++;
		$uid=$ligne["uid"];
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$familysite=trim($ligne["familysite"]);
		$category=trim($ligne["category"]);

		if($familysite==null){continue;}
		$md5=md5("$uid$zdate$familysite");
		$UIDS[$uid]=true;
		$f[$uid][]="('$md5','$zdate','$familysite','$size','$hits')";
		if($c>1000){
			events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
			if(!websites_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
				$c=0;
				$f=array();
				continue;
		}

		if(count($f)>500){
			events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
			if(!websites_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
				$f=array();

		}

	}

	if(count($f)>0){
		events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
		if(!websites_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
			return false;}

	}

	if(count($UIDS)>0){
		while (list ($uid, $rows) = each ($UIDS) ){
			websites_uid_not_categorised($uid);
				
		}

	}


	return true;
	if($GLOBALS["VERBOSE"]){echo "return true ".__LINE__."\n";}


}

function websites_uid_parse_array($array){
	$q=new mysql_squid_builder();
	while (list ($uid, $rows) = each ($array) ){
		$uidtable=$q->uid_to_tablename($uid);

		$sql="CREATE TABLE IF NOT EXISTS `www_$uidtable` ( `zmd5` varchar(90)  NOT NULL, `zDate` date  NOT NULL, `size` BIGINT UNSIGNED  NOT NULL, `hits`  BIGINT UNSIGNED  NOT NULL,
		`familysite` varchar(255)  NOT NULL,`category` varchar(255), PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`), KEY `size` (`size`), KEY `hits` (`hits`),
		KEY `familysite` (`familysite`) ,KEY `category` (`category`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);

		if(!$q->FIELD_EXISTS("www_$uidtable", "category")){
				$q->QUERY_SQL("ALTER TABLE `www_$uidtable` ADD `category` varchar(255), ADD INDEX (`category`)");
				}


				if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}
						return false;
				}
				$sql="INSERT IGNORE INTO `www_$uidtable` (zmd5,zDate,familysite,size,hits) VALUES ".@implode(',', $rows);
				$q->QUERY_SQL($sql);
				if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}
				return false;}

}

	return true;

}

function websites_uid_not_categorised($uid=null,$tablename=null,$aspid=false){
	if(isset($GLOBALS["websites_uid_not_categorised_$uid"])){return;}
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$uid.pid";
	if($aspid){
		$pid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($pid,$myfile)){
			ufdbguard_admin_events("Task already running PID: $pid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);

	$q=new mysql_squid_builder();
	if($uid<>null){
		$uidtable=$q->uid_to_tablename($uid);
		$tablename="www_$uidtable";
	}


	if(!$q->FIELD_EXISTS($tablename, "category")){
		$q->QUERY_SQL("ALTER TABLE `$tablename` ADD `category` varchar(255), ADD INDEX (`category`)");
	}

	$sql="SELECT familysite,`category` FROM `$tablename` GROUP BY familysite,`category` HAVING `category` IS NULL ";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){
	echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}
	return false;
	}


	$c=0;
	$mysql_num_rows=mysql_num_rows($results);
	if($mysql_num_rows==0){
	if($GLOBALS["VERBOSE"]){ echo "$sql (No rows)\n";}return true;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$sitename=$ligne["familysite"];
		$IpClass=new IP();
		if($IpClass->isValid($sitename)){
			if(isset($GLOBALS["IPCACHE"][$sitename])){
				$t=time();
				$sitename=gethostbyaddr($sitename);
				events("$tablename: {$ligne["familysite"]} -> $sitename ". $unix->distanceOfTimeInWords($t,time())." gethostbyaddr() LINE:".__LINE__);
				$GLOBALS["IPCACHE"][$sitename]=$sitename;

			}
		}
		
		
		$category=$q->GET_CATEGORIES($sitename);
		
		
		if($IpClass->isValid($sitename)){
			if($category==null){$category="ipaddr";}
			$q->categorize($sitename, $category);
		}
		events("$tablename: {$ligne["familysite"]} -> $sitename [$category] LINE:".__LINE__);
		
		if(strlen($category)>0){
			$category=mysql_escape_string2($category);
			$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
			$sql="UPDATE `$tablename` SET `category`='$category' WHERE familysite='{$ligne["familysite"]}'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
		}

	}
}

function events($text){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/squid.stats.log";
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