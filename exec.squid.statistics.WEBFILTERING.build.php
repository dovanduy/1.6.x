<?php
ini_set('memory_limit','1000M');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

$GLOBALS["zMD5"]=$argv[1];
BUILD_REPORT($argv[1]);


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics-{$GLOBALS["zMD5"]}.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}





function GRAB_DATAS($ligne,$md5){
	$GLOBALS["zMD5"]=$md5;
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$from=InfluxQueryFromUTC($params["FROM"]);
	$to=InfluxQueryFromUTC($params["TO"]);
	$interval=$params["INTERVAL"];
	$USER_FIELD=$params["USER"];
	$md5_table=md5(__FUNCTION__."."."$from$to");
	$searchsites=trim($params["searchsites"]);
	$searchuser=trim($params["searchuser"]);
	$categories=trim($params["categories"]);
	$searchsites_sql=null;
	$searchuser_sql=null;
	if($categories=="*"){$categories=null;}
	if($searchuser=="*"){$searchuser=null;}
	
	
	if($searchuser<>null){
		$searchuser_sql=str_replace("*", ".*", $searchuser);
		if($searchuser_sql<>null){
			$searchuser_sql=" AND $USER_FIELD =~ /$searchuser_sql/";
		}
	}	
	
	$SRF["USERID"]=true;
	$SRF["IPADDR"]=true;
	$SRF["MAC"]=true;
	unset($SRF[$USER_FIELD]);
	
	while (list ($A, $P) = each ($SRF) ){
		$srg[]=$A;
	}
	
	$users_fiels=@implode(",",$srg);
	

	if($searchuser<>null){ 
		$whereuser=" AND ($USER_FIELD = '$searchuser') ";
	}
	
	$q=new mysql_squid_builder();
	
	
	$Z[]="SELECT RQS,rulename,category,hostname,website,client FROM webfilter";
	$Z[]="WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')$whereuser";
	
		
	$sql=@implode(" ", $Z);
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$main=$influx->QUERY_SQL($sql);
	$GLOBALS["CSV1"][]=array("date","rulename","website","category","client","uid");
	foreach ($main as $row) {
		
		$time=InfluxToTime($row->time);
		$rulename=$row->rulename;
		$category=$row->category;
		$uid=$row->hostname;
		$website=$row->website;
		$client=$row->client;
		$HOURLY=date("Y-m-d H:00:00",$time);
		$MDKey=md5("$rulename$category$uid$website$client");

		$TIME_TEXT=date("Y-m-d H:i:s",$time);
		
		$GLOBALS["CSV1"][]=array($TIME_TEXT,$rulename,$website,$category,$client,$uid);
		
		if(!isset($MAIN_ARRAY[$HOURLY][$MDKey])){
			$MAIN_ARRAY[$HOURLY][$MDKey]["RULENAME"]=$rulename;
			$MAIN_ARRAY[$HOURLY][$MDKey]["WEBSITE"]=$website;
			$MAIN_ARRAY[$HOURLY][$MDKey]["CATEGORY"]=$category;
			$MAIN_ARRAY[$HOURLY][$MDKey]["CLIENT"]=$client;
			$MAIN_ARRAY[$HOURLY][$MDKey]["UID"]=$uid;
			$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]=1;
			
			
		}else{
			$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]=$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]+1;
			
		}
	}
	
	if(count($MAIN_ARRAY)==0){
		echo "MAIN_ARRAY is null....\n";
		return false;
	}
	
	echo "MAIN_ARRAY (1) = ".count($MAIN_ARRAY)."\n";
	
	build_progress("{step} {insert_data}: MySQL engine, {please_wait}",8);
	$f=array();
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `{$md5}user` (
	`RULENAME` VARCHAR(128),
	`WEBSITE` VARCHAR(128),
	`CATEGORY` VARCHAR(90),
	`CLIENT` VARCHAR(128),
	`UID` VARCHAR(90),
	`zDate` DATETIME,
	`hits` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `RULENAME`(`RULENAME`),
	KEY `WEBSITE`(`WEBSITE`),
	KEY `CATEGORY`(`CATEGORY`),
	KEY `CLIENT`(`CLIENT`),
	KEY `UID`(`UID`),
	KEY `zDate`(`zDate`),
	KEY `hits`(`hits`)
	)  ENGINE = MYISAM;";
	$q=new mysql_squid_builder();
	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		REMOVE_TABLES($md5);
		return false;
	}
	$c=0;

	while (list ($TIME, $SUBARRAY) = each ($MAIN_ARRAY) ){
			while (list ($MDKey, $array) = each ($SUBARRAY) ){		
				$RULENAME=$array["RULENAME"];
				$HITS=$array["RQS"];
				$WEBSITE=$array["WEBSITE"];
				$CATEGORY=$array["CATEGORY"];
				$CLIENT=$array["CLIENT"];
				$UID=$array["UID"];
				$zDate=$TIME;
				$c++;
				
				$f[]="('$RULENAME','$WEBSITE','$CATEGORY','$CLIENT','$UID','$zDate','$HITS')";
			
				if(count($f)>500){
					$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (RULENAME,WEBSITE,CATEGORY,CLIENT,UID,zDate,hits) VALUES ".@implode(",", $f));
					if(!$q->ok){echo $q->mysql_error;REMOVE_TABLES($md5);return false;}
				$f=array();
			}
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user`(RULENAME,WEBSITE,CATEGORY,CLIENT,UID,zDate,hits) VALUES ".@implode(",", $f));
		$f=array();
		
	}
	
	echo "$c items inserted to MySQL\n";
	

	return true;
}

function REMOVE_TABLES($md5){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `{$md5}sites`");
	$q->QUERY_SQL("DROP TABLE `{$md5}users`");
	
}


function BUILD_REPORT($md5){
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$from=InfluxQueryFromUTC($params["FROM"]);
	$to=InfluxQueryFromUTC($params["TO"]);
	$interval=$params["INTERVAL"];
	$user=$params["USER"];
	$md5_table=$md5;
	if(!GRAB_DATAS($ligne,$md5)){
		build_progress("{unable_to_query_to_bigdata}",110);
		return;
	}
	
	$q=new mysql_squid_builder();
	$per["10m"]="DATE_FORMAT(zDate,'%m-%d %Hh') as tdate";
	$per["1h"]="DATE_FORMAT(zDate,'%m-%d %Hh') as tdate";
	$per["1d"]="DATE_FORMAT(zDate,'%m-%d') as tdate";
	$per["1w"]="DATE_FORMAT(zDate,'%U') as tdate";
	$per["30d"]="DATE_FORMAT(zDate,'%m') as tdate";
	$datformat=$per[$interval];
	$md5_table="`{$md5}user`";
//----------------------------------------------------------------------------------------------------------	
	
	$sql="SELECT SUM(hits) as hits,UID,CLIENT FROM $md5_table GROUP BY UID,CLIENT ORDER by hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",15);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		$MAIN["GRAPH1"][$ligne["UID"]."/".$ligne["CLIENT"]]=$ligne["hits"];
	}
	build_progress("$c {rows}",30);
//----------------------------------------------------------------------------------------------------------
	$sql="SELECT SUM(hits) as hits,CATEGORY FROM $md5_table GROUP BY CATEGORY ORDER by hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",15);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$MAIN["GRAPH2"][$ligne["CATEGORY"]]=$ligne["hits"];
	}
		
//----------------------------------------------------------------------------------------------------------
	$sql="SELECT SUM(hits) as hits,RULENAME FROM $md5_table GROUP BY RULENAME ORDER by hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",15);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$MAIN["GRAPH3"][$ligne["RULENAME"]]=$ligne["hits"];
	}	
//----------------------------------------------------------------------------------------------------------	
	$sql="SELECT SUM(hits) as hits,WEBSITE FROM $md5_table GROUP BY WEBSITE ORDER by hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",15);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$MAIN["GRAPH4"][$ligne["WEBSITE"]]=$ligne["hits"];
	}
//----------------------------------------------------------------------------------------------------------	

	$MAIN["csv"]=$GLOBALS["CSV1"];	
	
	
	echo "MD5:{$GLOBALS["zMD5"]}\n";
	
	REMOVE_TABLES($GLOBALS["zMD5"]);
	$encoded_data=base64_encode(serialize($MAIN));
	$datasize=strlen($encoded_data);
	echo "Saving ".strlen($encoded_data)." bytes...\n";
	
	
	$q->QUERY_SQL("UPDATE reports_cache SET `builded`=1,`values`='$encoded_data',`values_size`='$datasize' WHERE `zmd5`='{$GLOBALS["zMD5"]}'");
	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL {failed}",110);
		return;
	}
	
	build_progress("{success}",100);

}
