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
include_once(dirname(__FILE__)."/framework/frame.class.inc");


hourly();


function hourly(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "time: $timefile\n";}
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
			if($pid<100){$pid=null;}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}
	}
	
	$timex=$unix->file_time_min($timefile);
	if($timex<60){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$q=new mysql();
	$LIST_TABLES_CURL_HOUR=$q->LIST_TABLES_CURL_HOUR();
	$HourTable=date("YmdH");
	$currenttable="{$HourTable}_curl";
	
	while (list ($tablenameH,$timex) = each ($LIST_TABLES_CURL_HOUR) ){
		if($tablenameH==$currenttable){continue;}
		CompressTableDay($tablenameH,$timex);
	}
	
	
	
	
}

function CompressTableDay($tablenameH,$timex){
	$DayTable=date("Ymd",$timex);
	$NextTable="{$DayTable}_dcurl";
	$q=new mysql();

	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as xDate, www,SUM(size_download) as size_download,HOUR(zDate) as Hour FROM `$tablenameH` GROUP BY xDate,www,Hour";
	
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return false;}
	
	$prefix="INSERT IGNORE INTO  `$NextTable` (`zDate`,`www`,`size_download`,`Hour`) VALUES ";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(intval($ligne["size_download"])==0){continue;}
		$f[]="('{$ligne["xDate"]}','{$ligne["www"]}','{$ligne["size_download"]}','{$ligne["Hour"]}')";
		
	}
	
	if(count($f)==0){
		$q->QUERY_SQL("DROP TABLE `$tablenameH`","artica_events");
		return;
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `$NextTable` (
	`zDate` DATE NOT NULL ,
	`www` VARCHAR(128) ,
	`size_download` INT UNSIGNED ,
	`Hour` SMALLINT(2) ,
	KEY `Hour` (`Hour`),
	KEY `zDate` (`zDate`),
	KEY `www` (`www`),
	KEY `size_download` (`size_download`)
	)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return false;}
	
	$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
	if(!$q->ok){return false;}
	$q->QUERY_SQL("DROP TABLE `$tablenameH`","artica_events");
	
}
