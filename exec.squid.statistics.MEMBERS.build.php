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
	$to=InfluxQueryFromUTC($params["TO"]);
	$from=InfluxQueryFromUTC($params["FROM"]);
	$interval=$params["INTERVAL"];
	$user=$params["USER"];
	$md5_table="{$md5}sites";
	$search=$params["SEARCH"];
	$USER_FIELD=$params["USER"];
	echo "FLOW: FROM $from to $to $interval user:$user $search\n";
	
	if($search=="*"){$search=null;}
	if($search<>null){
		$search=str_replace("*", ".*", $search);
		$SSEARCH=" AND ($USER_FIELD=~ /$search/)";
	}
	
	$sql="SELECT $user,SIZE FROM access_log WHERE (time >'".date("Y-m-d H:i:s",$from)."' and time < '".date("Y-m-d H:i:s",$to)."')$SSEARCH";
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		
		$time=InfluxToTime($row->time);
		$SIZE=intval($row->SIZE);
		$USER=$row->$USER_FIELD;
		if($SIZE==0){continue;}
		
		if(!isset($MAIN_ARRAY[$USER])){
			$MAIN_ARRAY[$USER]=$SIZE;
		}else{
			$MAIN_ARRAY[$USER]=$MAIN_ARRAY[$USER]+$SIZE;
		}
	}
	
	if(count($MAIN_ARRAY)==0){
		echo "MAIN_ARRAY is null....\n";
		return false;
	}
	
	echo "MAIN_ARRAY (1) = ".count($MAIN_ARRAY)."\n";
	
	build_progress("{step} {insert_data}: MySQL engine, {please_wait}",8);
	$f=array();
	
	$GLOBALS["CSV1"][]=array("member","SizeBytes");
	
	$sql="CREATE TABLE IF NOT EXISTS `{$md5}user` 
	(`user` VARCHAR(128),`size` INT UNSIGNED NOT NULL DEFAULT 1,
	KEY `user`(`user`),
	KEY `size`(`size`)
	)  ENGINE = MYISAM;";
	$q=new mysql_squid_builder();
	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		REMOVE_TABLES($md5);
		return false;
	}
	
	
	while (list ($USER, $SIZE) = each ($MAIN_ARRAY) ){
		$c=0;
		$f[]="('$USER','$SIZE')";
		if($GLOBALS["VERBOSE"]){echo "$USER -> $SIZE\n";}
		$GLOBALS["CSV1"][]=array($USER,$SIZE);
		if(count($f)>500){
			$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (user,size) VALUES ".@implode(",", $f));
			if(!$q->ok){
				echo $q->mysql_error;
				REMOVE_TABLES($md5);
				return false;
			}
			$f=array();
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `{$md5}user` (user,size) VALUES ".@implode(",", $f));
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


function BUILD_REPORT($md5){
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$to=InfluxQueryFromUTC($params["TO"]);
	$from=InfluxQueryFromUTC($params["FROM"]);
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
	$results=$q->QUERY_SQL("SELECT user,size FROM `{$md5}user` ORDER BY size DESC");
	
	
	build_progress("{parsing_data} (2)",25);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$USER=$ligne["user"];
		$SIZE=$ligne["size"];
		$BIGDATA[$USER]=$SIZE;
		$c++;
	}
	
	build_progress("$c {rows}",8);
	
	
	echo "$c rows....\n";
	
	REMOVE_TABLES($md5);
	$encoded_data=base64_encode(serialize($BIGDATA));
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




