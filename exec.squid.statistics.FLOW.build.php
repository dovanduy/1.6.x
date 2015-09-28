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


xsFLOW($argv[1]);


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
	$user=$params["USER"];
	$md5_table="{$md5}sites";
	echo "FLOW: FROM $from to $to $interval user:$user\n";
	
	$sql="SELECT SIZE,FAMILYSITE FROM access_log WHERE time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."'";
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		
		$time=InfluxToTime($row->time);
		$SIZE=intval($row->SIZE);
		$FAMILYSITE=$row->FAMILYSITE;
		$Hour=date("Y-m-d H:00:00",$time);
		if($SIZE==0){continue;}
		
		if(!isset($MAIN_ARRAY[$Hour][$FAMILYSITE])){
			$MAIN_ARRAY[$Hour][$FAMILYSITE]["SIZE"]=$SIZE;
		}else{
			$MAIN_ARRAY[$Hour][$FAMILYSITE]["SIZE"]=$MAIN_ARRAY[$Hour][$FAMILYSITE]["SIZE"]+$SIZE;
		}
	}
	
	if(count($MAIN_ARRAY)==0){
		echo "MAIN_ARRAY is null....\n";
		return false;
	}
	
	echo "MAIN_ARRAY (1) = ".count($MAIN_ARRAY)."\n";
	
	build_progress("{step} {insert_data}: MySQL engine, {please_wait}",8);
	$f=array();
	
	$GLOBALS["CSV1"][]=array("Date","Websites","SizeBytes");
	
	$sql="CREATE TABLE IF NOT EXISTS `{$md5}sites` 
	(`zDate` DATETIME,`familysite` VARCHAR(128),`size` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `familysite`(`familysite`),
	KEY `zDate`(`zDate`),
	KEY `size`(`size`)
	)  ENGINE = MYISAM;";
	$q=new mysql_squid_builder();
	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		REMOVE_TABLES($md5);
		return false;
	}
	
	while (list ($curhour, $array) = each ($MAIN_ARRAY) ){
		while (list ($FAMILYSITE, $Tarray) = each ($array) ){
			$SIZE=$Tarray["SIZE"];
			$c=0;
			$f[]="('$curhour','$FAMILYSITE','$SIZE')";
			$GLOBALS["CSV1"][]=array($curhour,$FAMILYSITE,$SIZE);
			if(count($f)>500){
				$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}sites` (zDate,familysite,size) VALUES ".@implode(",", $f));
				if(!$q->ok){
					echo $q->mysql_error;
					REMOVE_TABLES($md5);
					return false;
				}
				
				$f=array();
			}
		}
	
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}sites` (zDate,familysite,size) VALUES ".@implode(",", $f));
		$f=array();
		
	}
	
	echo "Websites $c items inserted to MySQL\n";
	
	$sql="CREATE TABLE IF NOT EXISTS `{$md5}users`
	(`user` VARCHAR(128),`size` INT UNSIGNED NOT NULL DEFAULT 1, KEY `user`(`user`), KEY `size`(`size`)
	)  ENGINE = MYISAM;";
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;REMOVE_TABLES($md5);return false;}	
	
	
	$sql="SELECT SIZE,$user FROM access_log WHERE time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."'";
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",8);
	
	$main=$influx->QUERY_SQL($sql);
	$MAIN_ARRAY=array();
	$c=0;
	foreach ($main as $row) {	
		
		$SIZE=intval($row->SIZE);
		$USER=$row->$user;
		if($SIZE==0){continue;}
		if(!isset($MAIN_ARRAY[$USER])){
			$MAIN_ARRAY[$USER]=$SIZE;
		}else{
			$MAIN_ARRAY[$USER]=$MAIN_ARRAY[$USER]+$SIZE;
		}
		
		
	}
	echo "MAIN_ARRAY (2) = ".count($MAIN_ARRAY)."\n";
	
	$c=0;
	$GLOBALS["CSV2"][]=array("member","SizeBytes");
	while (list ($USER, $SIZE) = each ($MAIN_ARRAY) ){
		$GLOBALS["CSV2"][]=array($USER,$SIZE);
		$f[]="('$USER','$SIZE')";
		$c++;
		if(count($f)>500){
			$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}users` (user,size) VALUES ".@implode(",", $f));
			$f=array();
		}
	}
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}users` (user,size) VALUES ".@implode(",", $f));
		$f=array();
	}

	echo "Members $c items inserted to MySQL\n";
	return true;
}

function REMOVE_TABLES($md5){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `{$md5}sites`");
	$q->QUERY_SQL("DROP TABLE `{$md5}users`");
	
}


function xsFLOW($md5){
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
	}
	
	$q=new mysql_squid_builder();
	$per["10m"]="DATE_FORMAT(zDate,'%m-%d %Hh') as tdate";
	$per["1h"]="DATE_FORMAT(zDate,'%m-%d %Hh') as tdate";
	$per["1d"]="DATE_FORMAT(zDate,'%m-%d') as tdate";
	$per["1w"]="DATE_FORMAT(zDate,'%U') as tdate";
	$per["30d"]="DATE_FORMAT(zDate,'%m') as tdate";
	
	
	$datformat=$per[$interval];
	$sql="SELECT SUM(size) as size,$datformat FROM `{$md5}sites` GROUP BY tdate ORDER BY tdate";
	echo "$sql\n";
	$results=$q->QUERY_SQL($sql);
	
	build_progress("{parsing_data} (2)",25);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"]/1024;
		$size=round($size/1024);
		if($GLOBALS["VERBOSE"]){echo "{$ligne["tdate"]} = {$size}MB\n";}
		if($size==0){continue;}
		$c++;
		$xdata[]=$ligne["tdate"];
		$ydata[]=$size;
	}
	
	build_progress("$c {rows}",8);
	
	
	echo "$c rows....\n";
	if(count($xdata)<2){
		$q->QUERY_SQL("DROP TABLE `{$md5}sites`");
		build_progress("$c {rows} ({only})",110);
		REMOVE_TABLES($md5);
		return;
	}
	$time=time();
	$MAIN["GRAPH1"]["xdata"]=$xdata;
	$MAIN["GRAPH1"]["ydata"]=$ydata;
	$xdata=array();
	$ydata=array();
	
	
	$sql="SELECT SUM(size) as size,familysite FROM `{$md5}sites` GROUP BY familysite ORDER BY size DESC LIMIT 0,10";
	echo "$sql\n";
	$results=$q->QUERY_SQL($sql);
	build_progress("{parsing_data} (2)",30);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$FAMILYSITE=$ligne["familysite"];
		$PieData2[$FAMILYSITE]=$size;
		$size=$size/1024;
		$size=round($size/1024,2);
		$PieData[$FAMILYSITE]=$size;
		$c++;
	}
	
	
	
	$MAIN["GRAPH2"]["PIEDATA"]=$PieData;
	$MAIN["GRAPH2"]["TABLE"]=$PieData2;
	
	build_progress("{saving}",50);
	if($GLOBALS["zMD5"]==null){
		build_progress("MD5 - > NULL {failed}",110);
		return;
	}
	build_progress("$c {rows}",60);
	build_progress("{building_query} $user (3)",70);
	$results=$q->QUERY_SQL("SELECT user,size FROM `{$md5}users` ORDER BY size DESC LIMIT 0,20");
	
	
	build_progress("{parsing_data} $user (3)",90);
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$size=$ligne["size"];
	$FAMILYSITE=$ligne["user"];
	$PieData4[$FAMILYSITE]=$size;
	$size=$size/1024;
	$size=round($size/1024,2);
	$PieData3[$FAMILYSITE]=$size;
}

	$MAIN["GRAPH3"]["PIEDATA"]=$PieData3;
	$MAIN["GRAPH3"]["TABLE"]=$PieData4;
	$MAIN["GRAPH3"]["TYPE"]=$user;
	$MAIN["CSV1"]=$GLOBALS["CSV1"];
	$MAIN["CSV2"]=$GLOBALS["CSV2"];
	
	echo "MD5:{$GLOBALS["zMD5"]}\n";
	
	REMOVE_TABLES($md5);
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




