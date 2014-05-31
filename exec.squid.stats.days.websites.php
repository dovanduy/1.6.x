<?php
//exec.squid.stats.days.websites.php
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
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

if($argv[1]=="--restart"){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET wwwvisited=0 WHERE wwwvisited=1");
	
}
if($argv[1]=="--fams"){
	familysites();
	exit;

}




$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$oldpid=@file_get_contents($pidfile);
$myfile=basename(__FILE__);
$unix=new unix();
if($unix->process_exists($oldpid,$myfile)){
	ufdbguard_admin_events("$oldpid already running, aborting",__FUNCTION__,__FILE__,__LINE__,"stats");
	return;
}

$time=$unix->file_time_min($pidTime);
if(!$GLOBALS["FORCE"]){
	if($time<1440){if($GLOBALS["VERBOSE"]){echo "$pidTime -> {$time}Mn/240mn\n";}die();}
}
@unlink($pidTime);
@file_put_contents($pidTime, time());

@file_put_contents($pidfile, getmypid());

$sql="SELECT DATE_FORMAT(zDate,'%Y%m%d') AS `suffix`, DATE_FORMAT(zDate,'%Y-%m-%d') AS `zDay`,`tablename`  
		FROM tables_day WHERE `wwwvisited`=0 
		AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)
		ORDER BY zDate DESC";


$q=new mysql_squid_builder();
$q->CheckTables();

$results=$q->QUERY_SQL($sql);
if(!$q->ok){
	ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
	die();
}

$COUNT=mysql_num_rows($results);
events("checking $COUNT items");
$c=0;
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$c++;
	$table_source="{$ligne["suffix"]}_hour";
	$prc=round(( $c/$COUNT )*100,2 );
	events("$table_source {$ligne["zDay"]} {$prc}%");
	if(perform($table_source,$ligne["zDay"])){
		$q->QUERY_SQL("UPDATE tables_day SET wwwvisited=1 WHERE `tablename`='{$ligne["tablename"]}'");
		continue;
	}
	
	
	
	
	
}

familysites();

function events($text){
	
	$common="/var/log/artica-squid-statistics.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] [".basename(__FILE__)."] $text\n";
	if($GLOBALS["VERBOSE"]){echo $line;}
	@fwrite($h,$line);
	@fclose($h);
}

function perform($tablesource,$zday){
	$q=new mysql_squid_builder();
	$sql="SELECT familysite,SUM(size) as size,SUM(hits) as hits FROM $tablesource GROUP BY familysite";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}

	
	$prefix="INSERT IGNORE INTO `visited_sites_days` (`zmd5`,zDate,familysite,`size` ,`hits`) VALUES ";

	$COUNT=mysql_num_rows($results);
	event("perform($tablesource,$zday) -> $COUNT rows");
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5($zday.$ligne["familysite"]);
		$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
		$f[]="('$md5','$zday','{$ligne["familysite"]}','{$ligne["size"]}','{$ligne["hits"]}')";
		
		
	}
	
	
	
	if(count($f)>0){
		
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
			ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
	}
	
	return true;
	
}


function familysites($nopid=false){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE visited_sites_tot");
	$sql="CREATE TABLE IF NOT EXISTS `visited_sites_tot` (
			  `familysite` varchar(255) NOT NULL,
			  `size` BIGINT(255) UNSIGNED NOT NULL,
			  `hits` BIGINT(255) UNSIGNED NOT NULL,
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  PRIMARY KEY `familysite` (`familysite`)
			)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	
	
	$sql="SELECT familysite,SUM(size) as size,SUM(hits) as hits FROM visited_sites_days GROUP BY familysite HAVING hits>2";
	$prefix="INSERT IGNORE INTO `visited_sites_tot` (familysite,`size` ,`hits`) VALUES ";
	$results=$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){
		ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
		return;
	}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
		$f[]="('{$ligne["familysite"]}','{$ligne["size"]}','{$ligne["hits"]}')";
	}
		
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo count($f)." rows...\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo count($f)."  Fatal {$q->mysql_error}\n";}
			ufdbguard_admin_events("Fatal {$q->mysql_error}", __FUNCTION__, __FILE__, __LINE__, "stats");
			return;
		}
	}
		
		return true;
}


