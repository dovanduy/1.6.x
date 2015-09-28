#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;
$GLOBALS["DBPATH"]="/var/log/squid/QUOTA_SIZE.db";
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");

if(preg_match("#--gpid\s+([0-9]+)#", @implode(" ", $argv),$re)){
	$GLOBALS["GPID"]=$re[1];
}

$GLOBALS["MYPID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["MYPID"]}");

if(is_file($GLOBALS["DBPATH"])){
	$filesize=@filesize($GLOBALS["DBPATH"]);
	$filesize=$filesize/1024;
	$filesize=$filesize/1024;
	if($filesize>100){@unlink($GLOBALS["DBPATH"]);}
}

if(!is_file($GLOBALS["DBPATH"])){
	
	try {
		WLOG("Creating {$GLOBALS["DBPATH"]} database");
		$db_desttmp = dba_open($GLOBALS["DBPATH"], "c","db4");
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("SIZE_QUOTA::FATAL ERROR $error");
		
	}
	
	
	
	if(!$db_desttmp){WLOG("SIZE_QUOTA::FATAL ERROR, unable to create database {$GLOBALS["DBPATH"]}");}
	dba_close($db_desttmp);
}
@chmod($GLOBALS["DBPATH"],0777);

LOADING_RULES();
WLOG("Quota Database : Starting Group id:{$GLOBALS["MYPID"]}");


$DCOUNT=0;
while (!feof(STDIN)) {
	$url = trim(fgets(STDIN));
	if($url==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::URL is null [".__LINE__."]");}
		continue;
	}
	$DCOUNT++;
	
	
	
	try {
		$result = SIZE_QUOTA($url);
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("$DCOUNT] SIZE_QUOTA::FATAL ERROR $error");
		$result=false;
	}
	
	if(!$result){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::ERR");}
		fwrite(STDOUT, "ERR\n");
		continue;
	}

	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::OK");}
	fwrite(STDOUT, "OK\n");
	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_sizequota.log";
	if(isset($trace[0])){$called=" called by ". basename($trace[0]["file"])." {$trace[0]["function"]}() line {$trace[0]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function _get_memory_usage_158() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function LOADING_RULES(){
	$file="/etc/squid3/acls/size_gpid{$GLOBALS["GPID"]}.acl";
	if(!is_file($file)){
		WLOG("LOADING_RULES::$file no such file! [".__LINE__."]");
		$GLOBALS["ACL_RULES"]=array();
		return;
	}
	$array=unserialize(@file_get_contents($file));
	$c=0;
	foreach($array as $line){
		
		if(preg_match("#max_day:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c Max time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"]["DAY"]=$re[1];
		}
		if(preg_match("#max_hour:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"]["HOUR"]=$re[1];
		}	
		if(preg_match("#max_week:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"]["WEEK"]=$re[1];
		}
		$c++;
	}
	
	if(!isset($GLOBALS["ACL_RULES"]["WEEK"])){$GLOBALS["ACL_RULES"]["WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"]["HOUR"])){$GLOBALS["ACL_RULES"]["HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"]["DAY"])){$GLOBALS["ACL_RULES"]["DAY"]=0;}
	
	
	
}
	
	
function SIZE_QUOTA($url){
	
	if(trim($url)==null){if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::URL is null [".__LINE__."]"); return false; }}
	if(strpos(" $url", "127.0.0.1 00:00:00:00:00:00")>0){return false;}
	
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::$url [".__LINE__."]");}
	$values=explode(" ",$url);
	$USERNAME=$values[0];
	
	
	if(strpos($USERNAME, '$')>0){
		if(substr($USERNAME, strlen($USERNAME)-1,1)=="$"){
			$USERNAME=null;
		}
	}
	$IPADDR=$values[1];
	$MAC=$values[2];
	$XFORWARD=$values[3];
	$WWW=$values[4];
	
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::USERNAME:$USERNAME [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::IPADDR..:$IPADDR [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::MAC.....:$MAC [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::XFORWARD:$XFORWARD [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::WWW.....:$WWW [".__LINE__."]");}
	
	$USERNAME=str_replace("%20", " ", $USERNAME);
	$USERNAME=str_replace("%25", "-", $USERNAME);
	
	$IPADDR=str_replace("%25", "-", $IPADDR);
	$MAC=str_replace("%25", "-", $MAC);
	$XFORWARD=str_replace("%25", "-", $XFORWARD);
	if($XFORWARD=="-"){$XFORWARD=null;}
	if($MAC=="00:00:00:00:00:00"){$MAC=null;}
	if($MAC=="-"){$MAC=null;}
	if($USERNAME=="-"){$USERNAME=null;}
	
	$IPCalls=new IP();
	
	if($IPCalls->isIPAddress($XFORWARD)){$IPADDR=$XFORWARD;}
	
	if(preg_match("#(.+?):[0-9]+#", $WWW,$re)){$WWW=$re[1];}
	if(preg_match("#^www\.(.+)#", $WWW,$re)){$WWW=$re[1];}
	if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");}
	$fam=new squid_familysite();
	$WWW=$fam->GetFamilySites($WWW);
	
	if($IPADDR<>null){
		$keymd5=md5("$WWW$IPADDR");
		$LOG_PREFIX="$IPADDR/$WWW";
	}
	
	if($MAC<>null){
		$keymd5=md5("$WWW$MAC");
		$LOG_PREFIX="$MAC/$WWW";
	}
	
	if($USERNAME<>null){
		$keymd5=md5("$WWW$USERNAME");
		$LOG_PREFIX="$USERNAME/$WWW";
	}
	
	
	$database_size_path="/var/log/squid/".date("YW")."_QUOTASIZE.db";
	
	if(!is_file($database_size_path)){
		if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX:FATAL!!! $database_size_path doesn't exists");}
		return false;
	}
	
	$db_con = dba_open($database_size_path, "r","db4");
	if(!$db_con){
		if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX:FATAL!!! SIZE_QUOTA::$database_size_path, unable to open");}
		return false;
	}	
	
	if(!dba_exists($keymd5,$db_con)){
		if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX:FATAL!!! SIZE_QUOTA::$keymd5 doesn't exists");}
		return false;
	}
	$array=unserialize(dba_fetch($keymd5,$db_con));
	dba_close($db_con);
	
	$current_hour=0;
	$current_day=0;
	$current_week=0;
	if(isset($array["HOURLY"][date("d")][date("H")])){
		$current_hour=intval($array["HOURLY"][date("d")][date("H")]);
		$current_hour=$current_hour/1024;
		$current_hour=$current_hour/1024;
	}
	
	if(isset($array["DAILY"][date("d")])){
		$current_day=intval($array["DAILY"][date("d")]);
		$current_day=$current_day/1024;
		$current_day=$current_day/1024;
	}
	
	if(isset($array["WEEK"])){
		$current_week=intval($array["WEEK"]);
		$current_week=$current_week/1024;
		$current_week=$current_week/1024;
	}	
	
	$rules_week=$GLOBALS["ACL_RULES"]["WEEK"];
	$rules_hour=$GLOBALS["ACL_RULES"]["HOUR"];
	$rules_day=$GLOBALS["ACL_RULES"]["DAY"];
	
	if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX:{$current_hour}MB/{$current_day}MB/{$current_week}MB - {$rules_hour}MB/{$rules_day}MB/{$rules_week}MB");}
	
	if($rules_week>0){
		if($current_week>$rules_week){
			if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX: WEEKLY: {$current_week}MB/{$rules_week}MB MACTHES --> OK");}
			return true;
		}
	}
	
	if($rules_day>0){
		if($current_day>$rules_day){
			if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX: DAILY: {$current_day}MB/{$rules_day}MB MACTHES --> OK");}
			return true;
		}
	}
	
	if($rules_hour>0){
		if($current_hour>$rules_hour){
			if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX:HOURLY: {$current_hour}MB/{$rules_hour}MB MACTHES --> OK");}
			return true;
		}
	}
	
	return false;
}
