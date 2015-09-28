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
	
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `tmp_{$md5}user`");
	
	$sql="CREATE TABLE IF NOT EXISTS `tmp_{$md5}user`
	(`ZDATE` DATETIME,
	`SIZE` INT UNSIGNED NOT NULL DEFAULT 1,
	`RQS` INT UNSIGNED NOT NULL DEFAULT 1,
	`CATEGORY` VARCHAR(60),
	`FAMILYSITE` VARCHAR(128),
	`USERID` VARCHAR(60),
	`IPADDR` VARCHAR(60),
	`MAC` VARCHAR(60),
	KEY `ZDATE`(`ZDATE`),
	KEY `CATEGORY`(`CATEGORY`),
	KEY `FAMILYSITE`(`FAMILYSITE`),
	KEY `USERID`(`USERID`),
	KEY `IPADDR`(`IPADDR`),
	KEY `MAC`(`MAC`))  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){
		echo "********** FAILED **********\n";
		echo $q->mysql_error."\n";
		build_progress("{step} {insert_data}: MySQL engine, {failed}",110);
		return false;
	}
	
	
	$FIELDS["MAC"]="MAC";
	$FIELDS["IPADDR"]="IPADDR";
	$FIELDS["USERID"]="USERID";
	
	
	$sql="SELECT SIZE,FAMILYSITE,RQS,CATEGORY,MAC,IPADDR,USERID FROM access_log WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')";
	if(isset($params["USER"])){
		
		
		while (list ($field, $size) = each ($FIELDS) ){
			$FINAL_FIELDS[]=$field;
		}
		
		$sql="SELECT SIZE,FAMILYSITE,RQS,CATEGORY,".@implode(",", $FINAL_FIELDS)." FROM access_log WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')";
	}
	
	
	
	
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	$GLOBALS["CSV1"][]=array("date","website","uid","ipaddr","mac","SizeBytes","SizeText","hits");
	$main=$influx->QUERY_SQL($sql);
	echo "MAIN(1): ".count($main)." items\n";
	if(count($main)<2){
		$sql="SELECT SIZE,FAMILYSITE,RQS,".@implode(",", $FINAL_FIELDS)." FROM access_log WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')";
		$main=$influx->QUERY_SQL($sql);
		echo "MAIN(2): ".count($main)." items\n";
	}
	
	
	$c=0;
	foreach ($main as $row) {
		$time=InfluxToTime($row->time);
		$SIZE=intval($row->SIZE);
		if($SIZE==0){continue;}
		$RQS=intval($row->RQS);
		$CATEGORY=mysql_escape_string2($row->CATEGORY);
		$FAMILYSITE=mysql_escape_string2($row->FAMILYSITE);
		$MAC=mysql_escape_string2($row->MAC);
		$IPADDR=mysql_escape_string2($row->IPADDR);
		$USERID=mysql_escape_string2($row->USERID);
		$DATE=date("Y-m-d H:00:00",$time);
		
		
		
		//if($GLOBALS["VERBOSE"]){echo "$DATE','$SIZE','$RQS','$CATEGORY','$FAMILYSITE','$USERID','$IPADDR','$MAC'\n";}
		$f[]="('$DATE','$SIZE','$RQS','$CATEGORY','$FAMILYSITE','$USERID','$IPADDR','$MAC')";
		$SIZE_TEXT=FormatBytes($SIZE/1024);
		$GLOBALS["CSV1"][]=array($DATE,$FAMILYSITE,$USERID,$IPADDR,$MAC,$SIZE,$SIZE_TEXT,$RQS);
		$c++;
		if(count($f)>500){
			
			$q->QUERY_SQL("INSERT IGNORE INTO `tmp_{$md5}user` (`ZDATE`,`SIZE`,`RQS`,`CATEGORY`,`FAMILYSITE`,`USERID`,`IPADDR`,`MAC`)
			VALUES ".@implode(",", $f));
			$f=array();
			if(!$q->ok){
				echo "********** FAILED **********\n";
				echo $q->mysql_error."\n";
				build_progress("{step} {insert_data}: MySQL engine, {failed}",110);
				return false;
			}
		}
		
	}
	
	if(count($f)>0){
		
		$q->QUERY_SQL("INSERT IGNORE INTO `tmp_{$md5}user` (`ZDATE`,`SIZE`,`RQS`,`CATEGORY`,`FAMILYSITE`,`USERID`,`IPADDR`,`MAC`) VALUES ".@implode(",", $f));
		if(!$q->ok){
			echo "********** FAILED **********\n";
			echo $q->mysql_error."\n";
			build_progress("{step} {insert_data}: MySQL engine, {failed}",110);
			return false;
		}
	}	
	
	
	if($c==0){
		echo "MAIN_ARRAY is null....\n";
		return false;
	}
	return true;
}

function REMOVE_TABLES($md5){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `tmp_{$md5}user`");
	
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
	$md5_table=$md5;
	$searchsites_sql=null;
	
	$USER_FIELD=$params["USER"];
	$SEARCH=$params["SEARCH"];
	

	if($SEARCH<>null){
		$searchuser_sql=str_replace("*", "%", $SEARCH);
		if(strpos(" $SEARCH", "%")>0){
			$searchuser_sql=" HAVING $USER_FIELD LIKE '$SEARCH'";
		}else{
			$searchuser_sql=" HAVING `$USER_FIELD`='$SEARCH'";
		}
		
	}
	
	
	
	
	if(!GRAB_DATAS($ligne,$md5)){
		build_progress("{unable_to_query_to_bigdata}",110);
		return;
	}
	
	$q=new mysql_squid_builder();

	
	//zDate,familysite,user,size,hits
	
	
	
	$sql="SELECT SUM(SIZE) as SIZE, SUM(RQS) as RQS,$USER_FIELD,`ZDATE` FROM `tmp_{$md5}user` 
	GROUP BY $USER_FIELD,`ZDATE` $searchuser_sql ORDER BY `ZDATE`";
	
	echo "********** SQL **********\n";
	echo $sql."\n";
	echo "********** SQL **********\n";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "$sql\n$q->mysql_error\n";
		build_progress("MySQL error [".__LINE__."]",110);		
		return;
	}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$time=$ligne["ZDATE"];
		$c++;
		if(intval($ligne["SIZE"])==0){continue;}
		$size=$ligne["SIZE"]/1024;
		$size=round($size/1024,2);
		
		$xdata[]=$ligne["ZDATE"];
		$ydata[]=$size;
		
	}
	
	echo "Building chronology\n";
	$sql="SELECT SUM(SIZE) as SIZE, SUM(RQS) as RQS,`ZDATE`,FAMILYSITE,$USER_FIELD
	FROM `tmp_{$md5}user` GROUP BY FAMILYSITE,$USER_FIELD,ZDATE $searchuser_sql ORDER BY ZDATE";
	
	echo "********** SQL **********\n";
	echo $sql."\n";
	echo "********** SQL **********\n";
	
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "$sql\n$q->mysql_error\n";
		build_progress("MySQL error [".__LINE__."]",110);
		return;
	}
	
	$c=0;
	
	$MAIN["CSV"][]=array("time","bytes","site","hits");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$c++;
		$time=$ligne["ZDATE"];
		if($GLOBALS["VERBOSE"]){echo "CHRONOS: {$ligne["FAMILYSITE"]} $time'\n";}
		
		$MAIN["CRONOS"][]=array("TIME"=>$time,"BYTES"=>$ligne["SIZE"],"SITE"=>$ligne["FAMILYSITE"],
				"RQS"=>$ligne["RQS"]);
		
		$MAIN["CSV"][]=array("$time","{$ligne["SIZE"]}","{$ligne["FAMILYSITE"]}","{$ligne["RQS"]}");
	}

	
	
	

	
	$MAIN["GRAPH1"]["xdata"]=$xdata;
	$MAIN["GRAPH1"]["ydata"]=$ydata;
	
	if($c==0){build_progress("{chronology}: {failed} C == 0",110);return;}
//----------------------------------------------------------------------------------------------------------
	$results=$q->QUERY_SQL("SELECT USERID as uid,IPADDR as ipaddr,MAC as mac FROM `tmp_{$md5}user` GROUP BY uid,ipaddr,mac");
	build_progress("{parsing_data} (2)",25);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$IDENT[]=array("IPADDR"=>$ligne["ipaddr"],"USERID"=>$ligne["uid"],"MAC"=>$ligne["mac"]);
	}		
//----------------------------------------------------------------------------------------------------------

	$sql="SELECT SUM(SIZE) as SIZE,FAMILYSITE,$USER_FIELD FROM `tmp_{$md5}user` 
	GROUP BY FAMILYSITE,$USER_FIELD $searchuser_sql ORDER BY SIZE DESC";
	echo "********** SQL **********\n";
	echo $sql."\n";
	echo "********** SQL **********\n";
	
	
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",30);
	
	if(!$q->ok){
		echo "$sql\n$q->mysql_error\n";
		build_progress("MySQL error [".__LINE__."]",110);
		return;
	}
	
	$tt=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=$ligne["SIZE"];
			$FAMILYSITE=$ligne["FAMILYSITE"];
			$size=$size/1024;
			$PieData2[$FAMILYSITE]=$size;
			if($GLOBALS["VERBOSE"]){echo "PIE DATA: $FAMILYSITE' -> $size\n";}
			if($tt<11){ 
					$MAIN["GRAPH2"]["PIEDATA"][$FAMILYSITE]=$size; 
					$MAIN["GRAPH2"]["TABLE"][$FAMILYSITE]=$size; 
			}
			$tt++;
			$MAIN["FAMS"][$FAMILYSITE]=$ligne["SIZE"];
	}
//----------------------------------------------------------------------------------------------------------
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