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
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
events("commands= ".implode(" ",$argv));
$users=new usersMenus();
if(!$users->POSTFIX_INSTALLED){die();}

$GLOBALS["CLASS_UNIX"]=new unix();
events("Executed " .@implode(" ",$argv));

if($argv[1]=="--days"){STATS_BuildDayTables();return;}
if($argv[1]=="--month"){STATS_BuildMonthTables();return;}
if($argv[1]=="--hourly-cnx"){STATS_hourly_cnx_to_daily_cnx();return;}



function STATS_BuildDayTables(){
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Already PID $pid running since {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$GLOBALS["Q"]->CheckTables();
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	TableDays_add_days();
	day_tables();
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("{$GLOBALS["DAYSTATS"]}: day tables generated from hour tables took: $took" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");

}

function STATS_BuildMonthTables(){
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Already PID $pid running since {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$GLOBALS["Q"]->CheckTables();
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	month_tables();

	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Task Month tables from {$GLOBALS["DAYSTATS"]} day tables took: $took" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");	
	
}

function month_tables(){
	
	$sql="SELECT zDays FROM TableDays WHERE MonthBuilded=0";
	$today=date("Y-m-d");
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	if(mysql_num_rows($results)==0){return;}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["zDays"]==$today){continue;}
		$TableDay=str_replace("-", "", $ligne["zDays"])."_day";
		$TableMonth=date("Ymd",strtotime($ligne["zDays"]))."_month";
		if(!_month_table($ligne["zDays"])){continue;}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE TableDays SET MonthBuilded=1 WHERE `zDays`='{$ligne["zDays"]}'");
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
		$c++;
		if(system_is_overloaded(__FILE__)){system_admin_events("Fatal: Overloaded system after $c calculated tables, try in next cycle..", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
		
	return true;	
	
}
function _month_table($day){
	$TableDay=str_replace("-", "", $day)."_day";
	$TableMonth=date("Ym",strtotime($day))."_month";
	if($GLOBALS["Q"]->TABLE_EXISTS(date("Ymd",strtotime($day))."_month")){$GLOBALS["Q"]->QUERY_SQL("DROP TABLE ".date("Ymd",strtotime($day))."_month");}
	
	$DayNum=date("d",strtotime($day));
	if(!$GLOBALS["Q"]->TABLE_EXISTS($TableDay)){return false;}
	if(!$GLOBALS["Q"]->BuildMonthTable($TableMonth)){
		system_admin_events($GLOBALS["Q"]->mysql_error ." table:$TableMonth", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return false;}
	
	$sql="SELECT SUM(hits) as hits, SUM(size) as mailsize,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode
	FROM  $TableDay GROUP BY mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "[$day]: No results...($TableMonth)\n";}return true;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(@serialize($ligne));
		$f[]="('$md5','$DayNum','{$ligne["hits"]}','{$ligne["mailsize"]}','{$ligne["mailfrom"]}',
		'{$ligne["instancename"]}','{$ligne["mailto"]}','{$ligne["domainfrom"]}','{$ligne["domainto"]}',
		'{$ligne["senderhost"]}','{$ligne["recipienthost"]}','{$ligne["smtpcode"]}')";
		
		if(count($f)>1500){
			if($GLOBALS["VERBOSE"]){echo "[$day]: Insert...". count($f). " items\n";}	
			$sql="INSERT IGNORE INTO `$TableMonth` (`zmd5`,`zday`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			$f=array();
		}		
		
	}
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "[$day]: Insert...". count($f). " items\n";}
		$sql="INSERT IGNORE INTO `$TableMonth` (`zmd5`,`zday`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
	$GLOBALS["DAYSTATS"]=$GLOBALS["DAYSTATS"]+1;
	return true;	
	
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
	$GLOBALS["DAYSTATS"]=$GLOBALS["DAYSTATS"]+1;
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


function STATS_hourly_cnx_to_daily_cnx(){
	
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Already PID $pid running since {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$TimeF=$unix->file_time_min($pidTime);
	if($TimeF<60){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$q=new mysql_postfix_builder();
	$LIST_POSTFIX_CNX_HOUR_TABLES=$q->LIST_POSTFIX_CNX_HOUR_TABLES();
	if(count($LIST_POSTFIX_CNX_HOUR_TABLES)==0){return;}
	$currentHour=date("YmdH")."_hcnx";
	while (list ($tablename, $timeEx) = each ($LIST_POSTFIX_CNX_HOUR_TABLES) ){
		if($tablename==$currentHour){continue;}
		$suffix=date("Ymd",strtotime($timeEx));
		$HOUR_FIELD=date("H",strtotime($timeEx));
		if(!$q->postfix_buildday_connections($suffix)){continue;}
		$desttable="{$suffix}_dcnx";
		if($GLOBALS["VERBOSE"]){echo "$tablename -> $desttable\n";}
		if(!_STATS_hourly_cnx_to_daily_cnx($tablename,$desttable,$HOUR_FIELD)){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
		
	}
}

function _STATS_hourly_cnx_to_daily_cnx($sourcetable,$desttable,$HOUR_FIELD){
	
	$q=new mysql_postfix_builder();
	$sql="SELECT COUNT(zmd5) as tcount,HOUR(zDate) as thour,hostname,domain,ipaddr FROM $sourcetable GROUP BY thour,hostname,domain,ipaddr ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	$prefix="INSERT IGNORE INTO `$desttable` (`zmd5`,`Hour`,`cnx`,`hostname`,`domain`,`ipaddr`) VALUES ";

	if(mysql_num_rows($results)==0){return true;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$f[]="('$md5','{$ligne["thour"]}','{$ligne["tcount"]}','{$ligne["hostname"]}','{$ligne["domain"]}','{$ligne["ipaddr"]}')";
		
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
		$f=array();
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