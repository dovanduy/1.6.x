<?php
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
events("commands= ".implode(" ",$argv));
$users=new usersMenus();
if(!$users->POSTFIX_INSTALLED){die();}

$GLOBALS["CLASS_UNIX"]=new unix();
events("Executed " .@implode(" ",$argv));

if($argv[1]=="--days"){STATS_BuildDayTables();return;}



function STATS_BuildDayTables(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid)){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		system_admin_events("Already PID $oldpid running since {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	TableDays_add_days();
	day_tables();
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("day tables generated from hour tables took: $took" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");

}



function TableDays_add_days(){
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$tables=$GLOBALS["Q"]->LIST_HOUR_TABLES();
	if(count($tables)==0){return;}
	$GLOBALS["Q"]->CheckTables();
	while (list ($tablename, $date) = each ($tables) ){
		$time=strtotime($date);
		$day=date("Y-m-d",$time);
		$rounded[$day]=true;
	}
	if(count($rounded)==0){return;}
	while (list ($sday, $none) = each ($rounded) ){
		$f[]="('$sday')";
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO TableDays (zDays) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");}
	}
	
}

function day_tables(){
	
	$sql="SELECT zDays FROM TableDays WHERE DayBuilded=0";
	$today=date("Y-m-d");
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	if(mysql_num_rows($results)==0){return;}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["zDays"]==$today){continue;}
		if(_day_tables($ligne["zDays"])){
			$tableDest=str_replace("-", "", $ligne["zDays"])."_day";
			$ligne2=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL(
				"SELECT SUM( hits ) AS hits, SUM( size ) AS size FROM `$tableDest`")
			);
			$GLOBALS["Q"]->QUERY_SQL("UPDATE TableDays SET DayBuilded=1, 
			`size`='{$ligne2["size"]}',
			`events`='{$ligne2["hits"]}' WHERE `zDays`='{$ligne["zDays"]}'");
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			 
		}
		$c++;
		if(system_is_overloaded(__FILE__)){system_admin_events("Fatal: Overloaded system after $c calculated tables, try in next cycle..", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
}

function _day_tables($day){
	$tables=$GLOBALS["Q"]->LIST_HOUR_TABLES();
	if(count($tables)==0){return;}
	while (list ($tablename, $date) = each ($tables) ){
		$time=strtotime($date);
		$Dday=date("Y-m-d",$time);
		if($Dday==$day){$nexttables[]=$tablename;}
	}	
	if(count($nexttables)==0){return;}
	
	while (list ($index, $tablename) = each ($nexttables) ){
		if(!_day_tables_inject($tablename,$day)){return false;}

		
	}
	
	return true;
}

function _day_tables_inject($sourcetable,$day){
	$tableDest=str_replace("-", "", $day)."_day";
	if(!$GLOBALS["Q"]->BuildDayTable($tableDest)){return false;}
	
	
	$sql="SELECT COUNT(zhour) as hits,zhour,mailfrom,instancename,
	mailto,domainfrom,domainto,senderhost,recipienthost,SUM(mailsize) as mailsize,
	smtpcode FROM $sourcetable 
	GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode";	
	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	
	if(mysql_num_rows($results)==0){return true;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(@serialize($ligne));
		$f[]="('$md5','{$ligne["zhour"]}','{$ligne["hits"]}','{$ligne["mailsize"]}','{$ligne["mailfrom"]}',
		'{$ligne["instancename"]}','{$ligne["mailto"]}','{$ligne["domainfrom"]}','{$ligne["domainto"]}',
		'{$ligne["senderhost"]}','{$ligne["recipienthost"]}','{$ligne["smtpcode"]}')";
		
		if(count($f)>1500){
		$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			$f=array();
		}		
		
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
	
	return true;
}


function events($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		$common="/var/log/artica-postfix/postfix.stats.log";
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