<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;
		//$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);		
}
if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}


if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

execute();

function execute(){
	
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
				return;
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<1440){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <>1440...\n";}
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `suffix`, DATE_FORMAT(zDate,'%Y-%m-%d') AS `zDay`,`tablename`
		FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 2 DAY)
		ORDER BY zDate DESC";

	$q=new mysql_squid_builder();
	
	if(!CheckTable()){
		if($GLOBALS['VERBOSE']){echo "Fatal generic_categories, no such table\n";}
		@unlink($timefile);
		ufdbguard_admin_events("Fatal generic_categories, no such table", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		@unlink($timefile);
		ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		die();
	}
	
	if(mysql_num_rows($results)==0){@unlink($timefile);return;}
	
	$q->QUERY_SQL("TRUNCATE TABLE `generic_categories`");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$table_source="{$ligne["suffix"]}_hour";
		if(!$q->TABLE_EXISTS($table_source)){
			if($GLOBALS['VERBOSE']){echo "$table_source, no such table\n";}
			continue;
		}
		
		perform($table_source,$ligne["zDay"]);
		
	
	}	
	
}

function perform($table_source,$zDate){
	$f=array();
	$q=new mysql_squid_builder();
	$sql="SELECT SUM( hits ) AS hits, SUM( size ) AS size, category FROM $table_source GROUP BY category";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");die();}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$category=mysql_escape_string2($ligne["category"]);
		$f[]="('$zDate','$category','{$ligne["size"]}','{$ligne["hits"]}')";
		
	}
	
	if(count($f)>0){
		$sql="INSERT INTO generic_categories (`zDate`,`category`,`size`,`hits`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");die();}
	}
	
}



function CheckTable(){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS('generic_categories')){
		$sql="CREATE TABLE `squidlogs`.`generic_categories` (
			 `category` VARCHAR(250),
			 `hits` BIGINT(250) NOT NULL,
			 `size` BIGINT(250) NOT NULL,
			 `zDate` date NOT NULL,
			 KEY `category`(`category`),
			 KEY `size`(`size`),
			 KEY `hits`(`hits`),
			 KEY `zDate`(`zDate`)
			 )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){return false;}
		return true;
	}	
	return true;
	
}

