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
	
	if($categories<>null){
		$searchsites_sql=str_replace("*", ".*", $categories);
		if($searchsites_sql<>null){
			$searchsites_sql=" AND CATEGORY =~ /$searchsites_sql/";
		}
	}
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
	

	$Z[]="SELECT SIZE,RQS,FAMILYSITE,CATEGORY,$USER_FIELD,$users_fiels FROM access_log";
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
	$GLOBALS["CSV1"][]=array("date","website","category","member","ipaddr","mac","SizeBytes","SizeText","hits");
	foreach ($main as $row) {
		
		$time=InfluxToTime($row->time);
		$USER=$row->USERID;
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$SIZE=intval($row->SIZE);
		$RQS=intval($row->RQS);
		$FAMILYSITE=$row->FAMILYSITE;
		$HOURLY=date("Y-m-d H:00:00",$time);
		$CATEGORY=$row->CATEGORY;
		$MDKey=md5("$FAMILYSITE$USER$IPADDR$MAC$CATEGORY");
		if($SIZE==0){continue;}
		$TIME_TEXT=date("Y-m-d H:i:s",$time);
		$SizeText=FormatBytes($SIZE/1024,true);
		$GLOBALS["CSV1"][]=array($TIME_TEXT,$FAMILYSITE,$CATEGORY,$USER,$IPADDR,$MAC,$SIZE,$SizeText,$RQS);
		
		if(!isset($MAIN_ARRAY[$HOURLY][$MDKey])){
			$MAIN_ARRAY[$HOURLY][$MDKey]["FAMILYSITE"]=$FAMILYSITE;
			$MAIN_ARRAY[$HOURLY][$MDKey]["USER"]=$USER;
			$MAIN_ARRAY[$HOURLY][$MDKey]["MAC"]=$MAC;
			$MAIN_ARRAY[$HOURLY][$MDKey]["IPADDR"]=$IPADDR;
			$MAIN_ARRAY[$HOURLY][$MDKey]["RQS"]=$RQS;
			$MAIN_ARRAY[$HOURLY][$MDKey]["SIZE"]=$SIZE;
			$MAIN_ARRAY[$HOURLY][$MDKey]["CATEGORY"]=$CATEGORY;
			
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
	`category` VARCHAR(128),
	`zDate` DATETIME,
	`size` INT UNSIGNED NOT NULL DEFAULT 1,
	`hits` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `USERID`(`USERID`),
	KEY `MAC`(`MAC`),
	KEY `IPADDR`(`IPADDR`),
	KEY `hits`(`hits`),
	KEY `category`(`category`),
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
				$CATEGORY=$array["CATEGORY"];
				$c++;
				$SIZE_LOGS=$SIZE;
				
				$f[]="('$TIME','$FAMILYSITE','$CATEGORY','$USER','$MAC','$IPADDR','$SIZE','$HITS')";
				//echo "('$TIME','$FAMILYSITE','$USER','$SIZE_LOGS','$HITS')\n";
			
				if(count($f)>500){
					$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (zDate,familysite,category,USERID,MAC,IPADDR,size,hits) VALUES ".@implode(",", $f));
					if(!$q->ok){echo $q->mysql_error;REMOVE_TABLES($md5);return false;}
				$f=array();
			}
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (zDate,familysite,category,USERID,MAC,IPADDR,size,hits) VALUES ".@implode(",", $f));
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
	
	$sql="SELECT $datformat,SUM(size) as size FROM $md5_table GROUP BY tdate ORDER by tdate";
	build_progress("{parsing_data} (1)",7);
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("{parsing_data} (1) {failed}",110);REMOVE_TABLES($md5);die();}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["size"]=round($ligne["size"]/1024);
		$MAIN["GRAPH0"]["ydata"][]=$ligne["size"];
		$MAIN["GRAPH0"]["xdata"][]=$ligne["tdate"];
		$c++;
	}
	build_progress("$c {rows}",30);
//----------------------------------------------------------------------------------------------------------
	$sql="SELECT $datformat,SUM(hits) as hits FROM $md5_table GROUP BY zdate ORDER by tdate";
	build_progress("{parsing_data}",35);
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("{parsing_data} {failed}",110);REMOVE_TABLES($md5);die();}
	if(mysql_num_rows($results)==0){build_progress("{parsing_data} {no_data}",7);echo $sql."\n";sleep(10);REMOVE_TABLES($md5);build_progress("{parsing_data} {failed}",110);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$MAIN["GRAPH1"]["ydata"][]=$ligne["hits"];
		$MAIN["GRAPH1"]["xdata"][]=$ligne["tdate"];
	}	
		
//----------------------------------------------------------------------------------------------------------
	$sql="SELECT SUM(size) as size,familysite FROM $md5_table GROUP BY familysite ORDER by size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("{parsing_data} {failed}",110);REMOVE_TABLES($md5);die();}
	if(mysql_num_rows($results)==0){build_progress("{parsing_data} {no_data}",7);echo $sql."\n";sleep(10);REMOVE_TABLES($md5);build_progress("{parsing_data} {failed}",110);}
	
	build_progress("{parsing_data}",40);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["size"]=round($ligne["size"]/1024);
		$MAIN["GRAPH2"][$ligne["familysite"]]=$ligne["size"];
	}
//----------------------------------------------------------------------------------------------------------	
	$sql="SELECT SUM(size) as size,$user FROM $md5_table GROUP BY $user ORDER by size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";build_progress("{parsing_data} {failed}",110);REMOVE_TABLES($md5);die();}
	if(mysql_num_rows($results)==0){build_progress("{parsing_data} {no_data}",7);echo $sql."\n";sleep(10);REMOVE_TABLES($md5);build_progress("{parsing_data} {failed}",110);}
	build_progress("{parsing_data}",45);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["size"]=round($ligne["size"]/1024);
		$MAIN["GRAPH3"][$ligne[$user]]=$ligne["size"];
	}
//----------------------------------------------------------------------------------------------------------	
	$sql="SELECT SUM(hits) as hits,category FROM $md5_table GROUP BY category ORDER by hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data}",50);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$category=$ligne["category"];
		if($category==null){$category="unknown";}
		$MAIN["GRAPH4"][$category]=$ligne["hits"];
	}	
//----------------------------------------------------------------------------------------------------------	
	$sql="SELECT SUM(size) as size,category FROM $md5_table GROUP BY category ORDER by size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data}",55);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$category=$ligne["category"];
		$ligne["size"]=$ligne["size"]/1024;
		if($category==null){$category="unknown";}
		$MAIN["GRAPH5"][$category]=$ligne["size"];
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
