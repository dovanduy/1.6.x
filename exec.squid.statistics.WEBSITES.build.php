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

if($argv[1]=="--tests"){tests();exit;}

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

function tests(){
	$influx=new influx();
	$sql="SELECT SUM(SIZE) as size FROM MAIN_SIZE WHERE time > 1434913322s GROUP BY time(10m) ORDER BY ASC";
	$influx->debug=true;
	$main=$influx->QUERY_SQL($sql);
	

	
	
	
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
	$searchsites_sql=null;
	$searchuser_sql=null;
	if($searchsites=="*"){$searchsites=null;}
	if($searchuser=="*"){$searchuser=null;}
	
	if($searchsites<>null){
		$searchsites_sql=str_replace("*", ".*", $searchsites);
		$searchsites_sql=" AND FAMILYSITE =~ /$searchsites_sql/";
	}
	if($searchuser<>null){
		$searchuser_sql=str_replace("*", ".*", $searchuser);
		$searchuser_sql=" AND $USER_FIELD =~ /$searchuser_sql/";
	}	
	
	$SRF["USERID"]=true;
	$SRF["IPADDR"]=true;
	$SRF["MAC"]=true;
	unset($SRF[$USER_FIELD]);
	
	while (list ($A, $P) = each ($SRF) ){
		$srg[]=$A;
	}
	
	$users_fiels=@implode(",",$srg);
	

	$Z[]="SELECT SIZE,RQS,FAMILYSITE,$USER_FIELD,$users_fiels FROM access_log";
	$Z[]="WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')";
	if($searchsites_sql<>null){
		$Z[]="$searchsites_sql";
	}
	if($searchuser_sql<>null){
		$Z[]="$searchuser_sql";
	}
	
	$sql=@implode(" ", $Z);
	echo "$sql\n";

	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$main=$influx->QUERY_SQL($sql);
	
	
	
	$GLOBALS["CSV1"][]=array("date","website","member","ipaddr","mac","SizeBytes","SizeText","hits");
	foreach ($main as $row) {
		
		
		$time=InfluxToTime($row->time);
		
		$USER=$row->USERID;
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$SIZE=intval($row->SIZE);
		$RQS=intval($row->RQS);
		$FAMILYSITE=$row->FAMILYSITE;
		if(trim($FAMILYSITE)==null){continue;}
		if(trim($IPADDR)==null){continue;}
		$HOURLY=date("Y-m-d H:00:00",$time);
		$MDKey=md5("$FAMILYSITE$USER$IPADDR$MAC");
		if($SIZE==0){continue;}
		$TIME_TEXT=date("Y-m-d H:i:s",$time);
		$SizeText=FormatBytes($SIZE/1024,true);
		
		$GLOBALS["CSV1"][]=array($TIME_TEXT,$FAMILYSITE,$USER,$IPADDR,$MAC,$SIZE,$SizeText,$RQS);
		
		if(!isset($MAIN_ARRAY[$HOURLY][$MDKey])){
			$MAIN_ARRAY[$HOURLY][$MDKey]["FAMILYSITE"]=$FAMILYSITE;
			$MAIN_ARRAY[$HOURLY][$MDKey]["USER"]=$USER;
			$MAIN_ARRAY[$HOURLY][$MDKey]["MAC"]=$MAC;
			$MAIN_ARRAY[$HOURLY][$MDKey]["IPADDR"]=$IPADDR;
			$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]=$RQS;
			$MAIN_ARRAY[$HOURLY][$MDKey]["SIZE"]=$SIZE;
			
		}else{
			$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]=$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]+$RQS;
			$MAIN_ARRAY[$HOURLY][$MDKey]["SIZE"]=$MAIN_ARRAY[$HOURLY][$MDKey]["SIZE"]+$SIZE;
			
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
	`USERID` VARCHAR(128),
	`MAC` VARCHAR(90),
	`IPADDR` VARCHAR(90),
	`familysite` VARCHAR(128),
	`zDate` DATETIME,
	`size` INT UNSIGNED NOT NULL DEFAULT 1,
	`hits` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `USERID`(`USERID`),
	KEY `MAC`(`MAC`),
	KEY `IPADDR`(`IPADDR`),
	KEY `hits`(`hits`),
	KEY `familysite`(`familysite`),
	KEY `size`(`size`)
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
				$USER=$array["USER"];
				$HITS=$array["RQS"];
				$SIZE=$array["SIZE"];
				$MAC=$array["MAC"];
				$IPADDR=$array["IPADDR"];
				$FAMILYSITE=$array["FAMILYSITE"];
				if(trim($FAMILYSITE)==null){continue;}
				if(trim($IPADDR)==null){continue;}
				
				$c++;
				$SIZE_LOGS=$SIZE;
				
				$f[]="('$TIME','$FAMILYSITE','$USER','$MAC','$IPADDR','$SIZE','$HITS')";
				echo "('$TIME','$FAMILYSITE','$USER','$SIZE_LOGS','$HITS')\n";
			
				if(count($f)>500){
					$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (zDate,familysite,USERID,MAC,IPADDR,size,hits) VALUES ".@implode(",", $f));
					if(!$q->ok){echo $q->mysql_error;REMOVE_TABLES($md5);return false;}
				$f=array();
			}
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (zDate,familysite,USERID,MAC,IPADDR,size,hits) VALUES ".@implode(",", $f));
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
	
//----------------------------------------------------------------------------------------------------------	
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,familysite FROM `{$md5}user` GROUP BY familysite ORDER BY size DESC LIMIT 0,10");
	
	build_progress("{parsing_data} (2)",25);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=round($size/1024);
		
		$c++;
		$FAMILYSITE=$ligne["familysite"];
		$TOP_WEBSITES_SIZE[$FAMILYSITE]=$size;
	}
	build_progress("$c {rows}",30);
//----------------------------------------------------------------------------------------------------------
	$results=$q->QUERY_SQL("SELECT SUM(hits) as hits,familysite FROM `{$md5}user` GROUP BY familysite ORDER BY hits DESC LIMIT 0,10");
	build_progress("{parsing_data} (2)",25);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$hits=$ligne["hits"];
		$FAMILYSITE=$ligne["familysite"];
		$TOP_WEBSITES_HITS[$FAMILYSITE]=$hits;
	}		
//----------------------------------------------------------------------------------------------------------
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,$user FROM `{$md5}user` GROUP BY $user ORDER BY size DESC LIMIT 0,10");
	
	build_progress("{parsing_data} (2)",25);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=round($size/1024);
		$USER=$ligne[$user];
		echo "USER: $USER (".FormatBytes($size).")\n";
		$TOP_WEBSITES_MEMBERS[$USER]=$size;
	}
//----------------------------------------------------------------------------------------------------------	
	$MAIN["CSV"]=$GLOBALS["CSV1"];
	$MAIN["TOP_WEBSITES_SIZE"]=$TOP_WEBSITES_SIZE;
	$MAIN["TOP_WEBSITES_MEMBERS"]=$TOP_WEBSITES_MEMBERS;
	$MAIN["TOP_WEBSITES_HITS"]=$TOP_WEBSITES_HITS;
	
	
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
