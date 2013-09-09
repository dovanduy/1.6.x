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


if($GLOBALS["VERBOSE"]){"******* echo Loading... *******\n";}

include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

$GLOBALS["Q"]=new mysql_squid_builder();

if($GLOBALS["VERBOSE"]){"echo Parsing arguments...\n";}

$sock=new sockets();
$DisableLocalStatisticsTasks=$sock->GET_INFO("DisableLocalStatisticsTasks");
if(!is_numeric($DisableLocalStatisticsTasks)){$DisableLocalStatisticsTasks=0;}
if($DisableLocalStatisticsTasks==1){die();}
process_all_tables();


function process_all_tables(){
	
	if($GLOBALS["VERBOSE"]){echo "Loading...\n";}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
		
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<720){if($GLOBALS["VERBOSE"]){echo "{$timeexec} <>720...\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	
	$tables=$q->LIST_TABLES_HOURS();
	$current_table=date("Ymd")."_hour";
	$BIGARRAY=array();
	$d=0;
	while (list ($tablename, $ligne) = each ($tables)){
		if($current_table==$tablename){
			if($GLOBALS["VERBOSE"]){echo "$tablename SKIP...\n";}
			continue;}
		$d++;
		$sql="SELECT sitename,familysite,category,SUM(size) as size,SUM(hits) as hits,country 
		FROM $tablename GROUP BY sitename,familysite,category HAVING LENGTH(category)=0";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){categorize_tables_events("MySQL error (after $d tables)","$q->mysql_error",$tablename);return;}
		$count=mysql_num_rows($results);
		if($count==0){
			if($GLOBALS["VERBOSE"]){echo "$tablename no row...\n";}
			continue;
		}else{
			if($GLOBALS["VERBOSE"]){echo "$tablename $count rows...\n";}
		}
		
		$TIME_FROM_HOUR_TABLE=$q->TIME_FROM_HOUR_TABLE($tablename);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$sitename=trim($ligne["sitename"]);
			$familysite=trim($ligne["familysite"]);
			if($sitename==null){
				if($GLOBALS["VERBOSE"]){echo "Null value for $sitename,$familysite aborting\n";}
				$q->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
				continue;
			}
				
			if($sitename=='.'){if($GLOBALS["VERBOSE"]){echo "'.' value for $sitename,$familysite aborting\n";}
				$q->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
				 continue;
			}
			
			if(strpos($sitename, ',')>0){
				$sitename=str_replace(",", "", $sitename);
				$q->QUERY_SQL("UPDATE $tablename SET `sitename`='$sitename' WHERE `sitename`='{$ligne["sitename"]}'");
			}
			
			if(is_numeric($sitename)){
				if($GLOBALS["VERBOSE"]){echo "Numeric value for $sitename,$familysite aborting\n";}
				$q->QUERY_SQL("DELETE FROM $tablename WHERE `sitename`='{$ligne["sitename"]}'");
				continue;
			}
			
			
			if(strpos($sitename, ".")==0){
				if($GLOBALS["VERBOSE"]){echo "Seems to be a local domain for $sitename,$familysite aborting\n";}
				$q->QUERY_SQL("UPDATE $tablename SET `category`='internal' WHERE `sitename`='{$ligne["sitename"]}'");
				continue;
			}
			
			
			if(!isset($BIGARRAY[$sitename])){
				$BIGARRAY[$sitename]["familysite"]=$familysite;
				$BIGARRAY[$sitename]["country"]=$ligne["country"];
				$BIGARRAY[$sitename]["size"]=$ligne["size"];
				$BIGARRAY[$sitename]["hits"]=$ligne["hits"];
			}else{
				$BIGARRAY[$sitename]["hits"]=$BIGARRAY[$ligne["sitename"]]["hits"]+$ligne["hits"];
				$BIGARRAY[$sitename]["size"]=$BIGARRAY[$ligne["sitename"]]["size"]+$ligne["size"];
			}
			
			$BIGARRAY[$sitename]["TIME"][$TIME_FROM_HOUR_TABLE]=true;
		
		}
		
		
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `notcategorized`");
	$sql="CREATE TABLE IF NOT EXISTS `notcategorized` (
		`sitename` VARCHAR(255) NOT NULL,
		`familysite` VARCHAR(255) NOT NULL,
		`domain` VARCHAR(5) NOT NULL,
		`country` VARCHAR(60) NOT NULL,
		`hits` bigint(255) unsigned NOT NULL,
		`size` bigint(255) unsigned NOT NULL,
		`seen` TEXT NOT NULL,
		PRIMARY KEY (`sitename`),
		KEY `size` (`size`),
		 KEY `hits` (`hits`),
		 KEY `familysite` (`familysite`),
		 KEY `domain` (`domain`),
		 KEY `country` (`country`)
		) ENGINE=MyISAM;";
	
	
	$q->QUERY_SQL($sql);	
	if(!$q->ok){
		categorize_tables_events("MySQL error (after $d items)","$q->mysql_error","notcategorized");
		return;
	}

	
	
	if($GLOBALS["VERBOSE"]){echo "FINAL ".count($BIGARRAY)." items...\n";}
	
	if(count($BIGARRAY)>0){
		$d=0;
		$prefix="INSERT IGNORE INTO notcategorized (`sitename`,`familysite`,`country`,`domain`,`size`,`hits`,`seen`) VALUES ";
		while (list ($sitename, $infos) = each ($BIGARRAY)){
			$d++;
			$times=array();
			$sitename=mysql_escape_string2($sitename);
			$family=mysql_escape_string2($infos["familysite"]);
			$country=mysql_escape_string2($infos["country"]);
			$tt=explode(".",$family);unset($tt[0]);$domain=mysql_escape_string2(@implode(".", $tt));
			while (list ($a, $b) = each ($infos["TIME"])){$times[]=$a;}
			
			
			$text_time=mysql_escape_string2(serialize($times));
			
			$f[]="('$sitename','$family','$country','$domain','{$infos["size"]}','{$infos["hits"]}','$text_time')";
			if(count($f)>500){
				if($GLOBALS["VERBOSE"]){echo "notcategorized 500 rows...\n";}
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){
					echo $q->mysql_error."\n";
					categorize_tables_events("MySQL error (after $d items)","$q->mysql_error","notcategorized");return;}
				$f=array();
			}
		}
		if(count($f)>0){
			if($GLOBALS["VERBOSE"]){echo "notcategorized ".count($f)." rows...\n";}
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$q->ok){categorize_tables_events("MySQL error (after $d items)","$q->mysql_error","notcategorized");return;}
		}	
	}

}


?>