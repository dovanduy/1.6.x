<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.nginx.inc");



parse_days();

function parse_days(){

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidTime)<1440){return;}
	}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$sock=new sockets();
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	if($EnableNginxStats==0){return;}	
	if(system_is_overloaded(basename(__FILE__))){
		events("Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting",__FUNCTION__,__LINE__);
		return;
		
	}
	
	$FALSES["information_schema"]=true;
	$FALSES["mysql"]=true;
	
	$q=new nginx_stats();
	$DATABASE_LIST_SIMPLE=$q->DATABASE_LIST_SIMPLE();
	while (list ($db, $b) = each ($DATABASE_LIST_SIMPLE)){
		if(isset($FALSES[$db])){continue;}
		if($GLOBALS["VERBOSE"]){echo "Parsing database $db\n";}
		parse_database($db);
	}
	
	
	
}


function parse_database($database){
	$q=new nginx_stats($database);
	$currenttable="day_".date("Ymd");
	$LIST_TABLES_DAY=$q->LIST_TABLES_DAY();
	while (list ($tablesource, $b) = each ($LIST_TABLES_DAY)){
		if(!preg_match("#^day_[0-9]+#",$tablesource)){continue;}
		if($currenttable==$tablesource){
			events("$database/$tablesource SKIPPING table $tablesource",__FUNCTION__,__LINE__);
			continue;}
		if(!parse_table($tablesource,$database)){continue;}
		
	}
}

function parse_table($tablesource,$database){
	$q=new nginx_stats($database);
	$sql="SELECT SUM(size) as tsize,SUM(hits) as hits,
			DATE_FORMAT( zDate, '%d' ) as zDay,zDate,ipaddr,hostname,country
			FROM $tablesource
			GROUP BY zDay,zDate,ipaddr,hostname,country
			";
	
	
	
	$results=$q->QUERY_SQL($sql);
	events("$database/$tablesource add ". mysql_num_rows($results). " elements",__FUNCTION__,__LINE__);
	
	
	
	if(!$q->ok){
		events($q->mysql_error,__FUNCTION__,__LINE__);
		return false;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zDay=$ligne["zDay"];
		$time=strtotime("{$ligne["zDate"]} 00:00:00");
		$tablename="month_".date("Ym",$time);
		$zmd5=md5(serialize($ligne));
		$ipaddr=$ligne["ipaddr"];
		$hostname=mysql_escape_string2($ligne["hostname"]);
		$country=mysql_escape_string2($ligne["country"]);
		$size=$ligne["size"];
		$hits=$ligne["hits"];

		
		$line="('$zmd5','$zDay','$ipaddr','$hostname','$country','$size','$hits')";
		$f[$tablename][]=$line;
		
		if(count($f[$tablename])>500){
			if(!parse_table_array($f,$database)){return false;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		if(!parse_table_array($f,$database)){return false;}
	}
	
	return true;
	
}

function parse_table_array($array,$database){
	$q=new nginx_stats($database);
	
	while (list ($tabledest, $rows) = each ($array)){
		if($rows==0){
			events("$database/$tabledest No row, continue",__FUNCTION__,__LINE__);
			continue;}
		if(!$q->MonthTable($tabledest)){
			events("$database/$tabledest unable to create table",__FUNCTION__,__LINE__);
			return false;
		}
		
		events("$database/$tabledest add ". count($rows). " elements",__FUNCTION__,__LINE__);
	
		$sql="INSERT IGNORE INTO `$tabledest` 
		(`zmd5`,`zDay`,`ipaddr`,`hostname`,`country`,`size`,`hits`) 
		VALUES ".@implode(",", $rows);
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events($q->mysql_error,__FUNCTION__,__LINE__);
			return false;
		}
		
	}
	
	return true;

}


function events($text,$function,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo "[$function]::$line:: $text\n";}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/nginx.stats.log",false,$function,$line);
	
}

