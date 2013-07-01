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

if($argv[1]=='--blocked-uid'){blocked_uid();exit;}
if($argv[1]=='--blocked-uid-reset'){blocked_uid_reset();exit;}
if($argv[1]=='--reset'){blocked_uid_reset();exit;}


blocked_uid();


function blocked_uid_reset(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET blocked_uid=0");
	blocked_uid();
}

function blocked_uid(){
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}
			return;
		}
	
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<540){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	if(isset($GLOBALS["blocked_uid_executed"])){return;}
	$GLOBALS["blocked_uid_executed"]=true;
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate FROM `tables_day` WHERE blocked_uid=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#Unknown column#",$q->mysql_error)){
			$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `blocked_uid` TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `blocked_uid` )");
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;
			}
				
			$results=$q->QUERY_SQL($sql);

		}
	}

	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;}

	if(mysql_num_rows($results)==0){if($GLOBALS["VERBOSE"]){echo "No rows... aborting\n";}return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$date=$ligne["zDate"];
		$time=strtotime($date." 00:00:00");
		$tablename=$ligne["tablename"];
		$but=null;
		$hourtable=date("Ymd",$time)."_blocked";
		if(!$q->TABLE_EXISTS($hourtable)){
			if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$hourtable no such table ($date) $but\n#############\n";}
			continue;
		}
		if(blocked_uid_from_hourtable($hourtable,$time)){
			if($GLOBALS["VERBOSE"]){echo "$tablename OK\n";}
			$q->QUERY_SQL("UPDATE tables_day SET blocked_uid=1 WHERE tablename='$tablename'");
			continue;
		}else{
			if($GLOBALS["VERBOSE"]){echo "Return false for $hourtable injection\n";}
		}
	}

}
function blocked_uid_from_hourtable($tablename,$time){

	blocked_macuid($tablename);

	$zdate=date("Y-m-d",$time);
	$q=new mysql_squid_builder();
	$sql="SELECT uid, COUNT(website) as hits,DATE_FORMAT(zDate,'%Y-%m-%d') as zDate
	,website,category FROM `$tablename` GROUP BY category,uid,website,DATE_FORMAT(zDate,'%Y-%m-%d')
	HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return false;}

	$c=0;
	if(mysql_num_rows($results)==0){return true;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$c++;
		$uid=$ligne["uid"];
		$hits=$ligne["hits"];
		$sitename=trim($ligne["website"]);
			$category=trim($ligne["category"]);
			$zdate=$ligne["zDate"];
			if($sitename==null){continue;}
			$md5=md5("$uid$zdate$sitename");
			$UIDS[$uid]=true;
			$f[$uid][]="('$md5','$zdate','$sitename','$category','$hits')";
			if($c>1000){
			if(!blocked_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
			return false;}
			$c=0;
			$f=array();
			continue;
			}
				
			if(count($f)>500){
			if(!blocked_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
					return false;}
							$f=array();
								
			}
				
			}
				
			if(count($f)>0){
			if(!blocked_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
					return false;}
				
			}
				
			return true;
			if($GLOBALS["VERBOSE"]){echo "return true ".__LINE__."\n";}

}

function blocked_uid_parse_array($array){
	$q=new mysql_squid_builder();
	while (list ($uid, $rows) = each ($array) ){
		$uidtable=$q->uid_to_tablename($uid);
		// $f[$uid][]="('$md5','$zdate','$sitename','$category','$hits')";
		$sql="CREATE TABLE IF NOT EXISTS `blocked_$uidtable` ( `zmd5` varchar(90)  NOT NULL,
		`zDate` date  NOT NULL,
		`hits`  BIGINT(100)  NOT NULL,
		`sitename` varchar(255)  NOT NULL,
		`category` varchar(255),
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `hits` (`hits`),
		KEY `sitename` (`sitename`) ,
		KEY `category` (`category`) )
		ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}return false;}
		$sql="INSERT IGNORE INTO `blocked_$uidtable` (zmd5,zDate,sitename,category,hits) VALUES ".@implode(',', $rows);
				$q->QUERY_SQL($sql);

				if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}return false;}

				}

				return true;


}

function blocked_macuid($tablename){
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilters_nodes WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["MAC"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$ligne["MAC"]} = {$ligne["uid"]}\n";}
		$array[$ligne["MAC"]]=$ligne["uid"];
	}

	while (list ($mac, $uid) = each ($array) ){
		if($GLOBALS["VERBOSE"]){echo "$tablename, $mac -> $uid\n";}
		if(IsCompressed($tablename)){Uncompress($tablename);}
		$uid=mysql_escape_string($uid);
		$q->QUERY_SQL("UPDATE $tablename SET uid='$uid' WHERE MAC='$mac'");

	}

}