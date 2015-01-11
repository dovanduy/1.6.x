#!/usr/bin/php -q
<?php

$GLOBALS["VERBOSE"]=false;
if($argv[1]=="--verbose"){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
}
if($argv[1]=="--cached"){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["MYPID"]=getmypid();
	$GLOBALS["VERBOSE"]=true;
	berekley_db_cached($argv[2]);
	berekley_db_notcached($argv[2]);
	
	die();
}


function shutdown() {
	$error = error_get_last();
	$type=trim($error["type"]);
	$message= trim($error["message"]);
	if($message==null){return;}
	$file = $error["file"];
	$line = $error["line"];
	if(function_exists("openlog")){openlog("artica-status", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog(true, "$file: Fatal, stopped with error $type $message line $line");}
	if(function_exists("closelog")){closelog();}
	
}
register_shutdown_function('shutdown');



include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
$GLOBALS["COUNT"]=0;
$GLOBALS["VERSION"]="15Nov2014";
$GLOBALS["UserAuthDB_path"]="/var/log/squid/UserAuthDB.db";
$GLOBALS["ACT_AS_REVERSE"]=false;
$GLOBALS["NO_DISK"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["LOG_HOSTNAME"]=false;
$GLOBALS["COUNT_WAKEUP"]=0;
$GLOBALS["COUNT_RQS"]["TIME"]=time();
$GLOBALS["DisableLogFileDaemonCategories"]=0;
$GLOBALS["ACCEPTED_REQUESTS"]=0;
$GLOBALS["DEBUG_CACHES"]=false;
$GLOBALS["REFUSED_REQUESTS"]=0;
$GLOBALS["COUNT_HASH_TABLE"]=0;
$GLOBALS["KEYUSERS"]=array();
$GLOBALS["RTTHASH"]=array();
$GLOBALS["CACHE_SQL"]=array();
$timezones=@file_get_contents("/etc/artica-postfix/settings/Daemons/timezones"); 
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="{$GLOBALS["ARTICALOGDIR"]}"; } }




if($timezones<>null){@date_default_timezone_set($timezones);}
parseconfig();
CheckDirs();

error_reporting(0);
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


if($argv[1]=="--no-disk"){$GLOBALS["NO_DISK"]=true;}
if($argv[1]=="--dump-mac"){print_r(unserialize(@file_get_contents("/etc/squid3/usersMacs.db")));exit;}
$logthis=array();
if($GLOBALS["VERBOSE"]){$logthis[]="Verbosed";}
if($GLOBALS["ACT_AS_REVERSE"]){$logthis[]=" Act as reverse...";}
$GLOBALS["MYPID"]=getmypid();
events("Starting PID: {$GLOBALS["MYPID"]} - TimeZone: $timezones,  version: {$GLOBALS["VERSION"]}, ".@implode(", ", $logthis) ." ({$argv[1]})");
if($GLOBALS["DisableLogFileDaemonCategories"]==1){events("Starting: WILL NOT USE Categories detection feature..."); }
if($GLOBALS["DisableLogFileDaemonCategories"]==0){events("Starting: USING Categories detection feature..."); }
$GLOBALS["COUNT_RQS"]=0;
$GLOBALS["PURGED"]=0;
events("Starting PID: waiting connections...");
$DCOUNT=0;

@file_put_contents("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid", time());

$pipe = fopen("php://stdin", "r");
$buffer=null;

while(!feof($pipe)){
	$buffer= trim(fgets($pipe));
	$GLOBALS["COUNT_RQS"]=$GLOBALS["COUNT_RQS"]+1;
	
	if($GLOBALS["VERBOSE"]){events( __LINE__." {$GLOBALS["COUNT_RQS"]} connexions");}

	if(strpos($buffer, "TCP_DENIED/403")>0){
		if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		continue;
	}
	
	Wakeup();
	
	if(strpos($buffer, "NONE:HIER_NONE")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
	if(strpos($buffer, "error:invalid-request")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
	if(strpos("NONE error:", $buffer)>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return; }
	if(strpos($buffer, "GET cache_object")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
	
	
	$F=substr($buffer, 0,1);

	
	if($F=="L"){
		$GLOBALS["WAKEUP_LOGS"]=$GLOBALS["WAKEUP_LOGS"]+1;
		$buffer=substr($buffer, 1,strlen($buffer));
		
		
		
		if( $GLOBALS["WAKEUP_LOGS"]>50 ){
			events("{$GLOBALS["REFUSED_REQUESTS"]} refused requests ".
			"- {$GLOBALS["ACCEPTED_REQUESTS"]} accepted requests ".
			"- {$GLOBALS["COUNT_RQS"]} connexions received ".
			"- Hash Table = ".count($GLOBALS["RTTHASH"])." ".
			"- Queued items = {$GLOBALS["COUNT_HASH_TABLE"]} element(s)"
			);
			$GLOBALS["WAKEUP_LOGS"]=0;
		}		
		
		
		
		if(strpos($buffer, "TCP_MISS/000")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
		if(strpos($buffer, "TCP_DENIED:")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
		if(strpos($buffer, "RELEASE -1")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
		if(strpos($buffer, "RELEASE 00")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
		if(strpos($buffer, "SWAPOUT 00")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
		
		if($GLOBALS["VERBOSE"]){$time_start = microtime(true);}
		
		ParseSizeBuffer($buffer);
		if($GLOBALS["VERBOSE"]){$time_end = microtime(true);$time_calc = $time_end - $time_start;}
		if($GLOBALS["VERBOSE"]){events("ParseSizeBuffer = {$time_calc}ms");}
		
		
		$buffer=null;
		Wakeup();
		continue;
	}
	
	
	
	if(is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.shutdown")){
		events("Stopping loop PID:".getmypid());
		@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.shutdown");
		break;
	}
	
	if(count($GLOBALS["RTTHASH"])>2){empty_TableHash();}
	if($GLOBALS["COUNT_HASH_TABLE"]>50){empty_TableHash();}
	$buffer=null;
}

if(!is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid")){@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid");}
events("Stopping PID:".getmypid()." After $DCOUNT event(s)");
empty_TableHash();


function CheckDirs(){
	$f[]="/var/log/squid/mysql-queue";
	$f[]="/var/log/squid/mysql-rttime";
	$f[]="/var/log/squid/mysql-rthash";
	$f[]="/var/log/squid/mysql-rtterrors";
	$f[]="/var/log/squid/mysql-squid-queue";
	$f[]="/var/log/squid/mysql-rtterrors";
	$f[]="/var/log/squid/mysql-UserAgents";
	$f[]="/var/log/squid/mysql-computers";
	$f[]="/var/log/squid/ufdbguard-blocks";
	$f[]="/var/log/squid/squid_admin_mysql";
	
	while (list ($num, $directory) = each ($f)){
		if(!is_dir($directory)){@mkdir($directory,0755,true);}
		@chown($directory, "squid");
		@chgrp($directory, "squid");
		
	}
	
	
}


function Wakeup(){
	$GLOBALS["COUNT_WAKEUP"]=$GLOBALS["COUNT_WAKEUP"]+1;
	if($GLOBALS["COUNT_WAKEUP"]>10){
		$GLOBALS["MYPID"]=getmypid();
		if(!is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid")){@file_put_contents("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid", $GLOBALS["MYPID"]); }
		$GLOBALS["COUNT_WAKEUP"]=0;
		$Array["PURGED"]=$GLOBALS["PURGED"];
		$Array["COUNT_RQS"]=$GLOBALS["COUNT_RQS"];
		@file_put_contents("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.state", serialize($Array));
	}
	
	if(!is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.wakeup")){return;}
	
	@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.wakeup");
	@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.status");
	@touch("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.status");
	events("{$GLOBALS["REFUSED_REQUESTS"]} refused requests ".
			"- {$GLOBALS["ACCEPTED_REQUESTS"]} accepted requests ".
			"- {$GLOBALS["COUNT_RQS"]} connexions received ".
			"- Hash Table = ".count($GLOBALS["RTTHASH"])." ".
			"- Queued items = {$GLOBALS["COUNT_HASH_TABLE"]} element(s)"
	);
	
	empty_TableHash();
		
	
}


function parseconfig(){
	
	$GLOBALS["ProxyUseArticaDB"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB"));
	$GLOBALS["EnableSquidRemoteMySQL"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidRemoteMySQL"));
	$GLOBALS["squidRemostatisticsServer"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsServer"));
	$GLOBALS["squidRemostatisticsPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPort"));
	$GLOBALS["squidRemostatisticsUser"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsUser"));
	$GLOBALS["squidRemostatisticsPassword"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPassword"));
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL")){
		$GLOBALS["DisableLogFileDaemonMySQL"]=1;
	}else{
		$GLOBALS["DisableLogFileDaemonMySQL"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL"));
	}
	
	
	ConfigUfdcat();
	
	
}



function berekley_db(){
	
	$date=date("YW");
	$GLOBALS["DBPATH"]="/var/log/squid/{$date}_QUOTASIZE.db";
	$GLOBALS["DBSIZE"]="/var/log/squid/{$date}_size.db";
	
	
	if(!is_file($GLOBALS["DBSIZE"])){
		try {
			events("berekley_db:: Creating {$GLOBALS["DBSIZE"]} database");
			$db_desttmp = @dba_open($GLOBALS["DBSIZE"], "c","db4");
			
		}
		catch (Exception $e) {
			$error=$e->getMessage();
			events("berekley_db::FATAL ERROR $error on {$GLOBALS["DBPATH"]}");
	
		}
		@dba_close($db_desttmp);
	
	}
	
	
	if(!is_file($GLOBALS["UserAuthDB_path"])){
		try {
			events("berekley_db:: Creating {$GLOBALS["UserAuthDB_path"]} database");
			$db_desttmp = @dba_open($GLOBALS["UserAuthDB_path"], "c","db4");
		}
		catch (Exception $e) {
			$error=$e->getMessage();
			events("berekley_db::FATAL ERROR $error");
	
		}
		@dba_close($db_desttmp);
		
	}
	
	
	if(!is_file($GLOBALS["DBPATH"])){
	
		try {
			events("berekley_db:: Creating {$GLOBALS["DBPATH"]} database");
			$db_desttmp = @dba_open($GLOBALS["DBPATH"], "c","db4");
		}
		catch (Exception $e) {
			$error=$e->getMessage();
			events("berekley_db::FATAL ERROR $error");
	
		}
	
		if(!$db_desttmp){events("berekley_db: FATAL ERROR, unable to create database {$GLOBALS["DBPATH"]}");}
		@dba_close($db_desttmp);
		@chmod($GLOBALS["DBPATH"], 0777);
	}
	
	
}

function UserAuthDB($mac,$ipaddr,$uid,$hostname,$UserAgent){
	$keymd5=md5("$mac$ipaddr$uid$hostname$UserAgent");
	if($mac<>null){$keymd5=md5("$mac$uid$UserAgent");}
	
	if(isset($GLOBALS["UserAuthDB"][$keymd5])){return;}
	$array["MAC"]=$mac;
	$array["IPADDR"]=$ipaddr;
	$array["uid"]=$uid;
	$array["hostname"]=$hostname;
	$array["UserAgent"]=$UserAgent;
	
	$db_con = @dba_open($GLOBALS["UserAuthDB_path"], "c","db4");
	if(!$db_con){
		events("UserAuthDB:: FATAL!!!::{$GLOBALS["UserAuthDB_path"]}, unable to open");
		return false;
	}
	
	
	if(!@dba_exists($keymd5,$db_con)){
		@dba_replace($keymd5,serialize($array),$db_con);
		$GLOBALS["UserAuthDB"][$keymd5]=true;
	}else{
		$GLOBALS["UserAuthDB"][$keymd5]=true;
	}
	@dba_close($db_con);
}

function berekley_db_cached_www($familysite,$SIZE){
	$DatabasePath="/var/log/squid/".date("YW")."_wwwcached.db";
	
	if(!is_file($DatabasePath)){
		try {
			events("berekley_db:: Creating $DatabasePath database");
			$db_desttmp = @dba_open($DatabasePath, "c","db4");}
			catch (Exception $e) {$error=$e->getMessage();events("berekley_db::FATAL ERROR $error on $DatabasePath");return;}
			@dba_close($db_desttmp);
	}
	
	$db_con = @dba_open($DatabasePath, "c","db4");
	if(!$db_con){
		events("berekley_db_cached_www:: FATAL!!!::$DatabasePath, unable to open");
		return false;
	}
	
	$CURRENT=intval(dba_fetch($familysite,$db_con));
	$CURRENT=$CURRENT+$SIZE;
	dba_replace($familysite,$CURRENT,$db_con);
	@dba_close($db_con);
	
}


function berekley_db_notcached_www($familysite,$SIZE){
	$familysite=trim($familysite);
	if($familysite==null){return;}
	
	$DatabasePath="/var/log/squid/".date("YW")."_NOTCACHED_WEEK.db";
	berekley_db_create($DatabasePath);
	
	$db_con = @dba_open($DatabasePath, "c","db4");
	if(!$db_con){
		events("berekley_db_notcached_www:: FATAL!!!::$DatabasePath, unable to open");
		return false;
	}
	
	if(!@dba_exists($familysite,$db_con)){
		$array["SIZE"]=$SIZE;
		$array["HIT"]=1;
		dba_replace($familysite,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	$dba_fetch=dba_fetch($familysite,$db_con);
	$array=unserialize($dba_fetch);
	$array["SIZE"]=intval($array["SIZE"])+intval($SIZE);
	$array["HIT"]=intval($array["HIT"])+1;
	dba_replace($familysite,serialize($array),$db_con);
	@dba_close($db_con);
}




function berekley_db_cached($familysite,$SIZE){
	berekley_db_cached_www($familysite,$SIZE);
	$date=date("Y-m-d H:00:00")."/cached";
	if(!isset($GLOBALS["DBPATH"])){
		$dateW=date("YW");
		$GLOBALS["DBPATH"]="/var/log/squid/{$dateW}_QUOTASIZE.db";
		$GLOBALS["DBSIZE"]="/var/log/squid/{$dateW}_size.db";
	}
	
	$DatabasePath=$GLOBALS["DBSIZE"];
	$db_con = @dba_open($DatabasePath, "c","db4");
	if(!$db_con){
		events("berekley_db_size:: FATAL!!!::$DatabasePath, unable to open");
		return false;
	}
	
	$array=array();
	$CURRENT=intval(dba_fetch("TOTALS_CACHED",$db_con));
	if($GLOBALS["VERBOSE"]){echo "*** CACHED: TOTALS_CACHED = $CURRENT\n";}
	
	$CURRENT=$CURRENT+$SIZE;
	dba_replace("TOTALS_CACHED",$CURRENT,$db_con);
	@dba_close($db_con);
	

	$db_con = @dba_open($DatabasePath, "c","db4");
	
	
	if($GLOBALS["VERBOSE"]){echo "CACHED: FIND $date\n";}
	$CURRENT=intval(dba_fetch($date,$db_con));
	if($GLOBALS["VERBOSE"]){echo "CACHED: $CURRENT\n";}
	
	$NEXT=$CURRENT+$SIZE;
	
	if($GLOBALS["VERBOSE"]){echo "CACHED: Key: $date Add $NEXT\n";}
	dba_delete($date, $db_con);
	dba_replace($date,$NEXT,$db_con);
	@dba_close($db_con);
	
}
function berekley_db_notcached($SIZE){
	
	if(!isset($GLOBALS["DBPATH"])){
		$dateW=date("YW");
		$GLOBALS["DBPATH"]="/var/log/squid/{$dateW}_QUOTASIZE.db";
		$GLOBALS["DBSIZE"]="/var/log/squid/{$dateW}_size.db";
	}
	
	$date=date("Y-m-d H:00:00")."/not_cached";
	$DatabasePath=$GLOBALS["DBSIZE"];

	$db_con = @dba_open($DatabasePath, "c","db4");
	if(!$db_con){
		events("berekley_db_size:: FATAL!!!::{$GLOBALS["DBPATH"]}, unable to open");
		return false;
	}


	$CURRENT=intval(dba_fetch("TOTALS_NOT_CACHED",$db_con));
	$CURRENT=$CURRENT+$SIZE;
	dba_replace("TOTALS_NOT_CACHED",$CURRENT,$db_con);

	
	if($GLOBALS["VERBOSE"]){echo "NOT CACHED: FIND $date\n";}
	$CURRENT=intval(dba_fetch($date,$db_con));
	if($GLOBALS["VERBOSE"]){echo "NOT CACHED: $CURRENT\n";}
	
	$NEXT=$CURRENT+$SIZE;
	
	if($GLOBALS["VERBOSE"]){echo "NOT CACHED: Key: $date Add $NEXT\n";}
	dba_delete($date, $db_con);
	dba_replace($date,$NEXT,$db_con);
	@dba_close($db_con);
	

}

function berekly_db_mime($mac,$ipaddr,$uid,$SIZE,$mime){
	
	$date=date("Ymd");
	$database="/var/log/squid/{$date}_mime.db";
	
	
	if(!is_file($database)){
		try {
			events("berekley_db:: Creating $database database");
			$db_desttmp = @dba_open($database, "c","db4");
		}
		catch (Exception $e) {
			$error=$e->getMessage();
			events("berekley_db::FATAL ERROR $error on $database");
	
		}
		@dba_close($db_con);
	}
	
	$db_con = @dba_open($database, "c","db4");
	if(!$db_con){
		events("berekley_db_size:: FATAL!!!::{$GLOBALS["DBPATH"]}, unable to open");
		return false;
	}
	
	if($ipaddr<>null){$keymd5=md5("$ipaddr");}
	if($mac<>null){$keymd5=md5("$mac"); }
	if($uid<>null){$keymd5=md5("$uid"); }
	
	if(!@dba_exists($keymd5,$db_con)){
		$array[$mime]["SIZE"]=$SIZE;
		$array[$mime]["MAC"]=$mac;
		$array[$mime]["UID"]=$uid;
		$array[$mime]["IPADDR"]=$ipaddr;
		dba_replace($keymd5,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	
	$array=unserialize(dba_fetch($keymd5,$db_con));
	if(!isset($array[$mime]["SIZE"])){$array[$mime]["SIZE"]=0;}
	$array[$mime]["SIZE"]=$array[$mime]["SIZE"]+$SIZE;
	$array[$mime]["MAC"]=$mac;
	$array[$mime]["UID"]=$uid;
	$array[$mime]["IPADDR"]=$ipaddr;
	dba_replace($keymd5,serialize($array),$db_con);
	@dba_close($db_con);
	
}

function clean_mac($MAC){
	$f=explode(":",$MAC);
	while (list ($index, $line) = each ($f) ){

		if(strlen($line)>2){
			$line=substr($line, strlen($line)-2,2);
			$f[$index]=$line;
			continue;
		}
	}


	return @implode(":", $MAC);
}

function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	if(!is_file($db_path)){
		try {
			events("berekley_db_create:: Creating $db_path database");
			$db_desttmp = @dba_open($db_path, "c","db4");
			@dba_close($db_con);
				
		}
		catch (Exception $e) {
			$error=$e->getMessage();
			events("berekley_db_create::FATAL ERROR $error on $db_path");
			@dba_close($db_con);
			return;
		}
	
	}
	return true;
	
}




function berekley_db_access($familysite,$mac,$ipaddr,$uid,$SIZE,$category=null,$cached,$MimeType){
	
	$db_path="/var/log/squid/".date("YmdH")."_dbaccess.db";

	if(!berekley_db_create($db_path)){return;}
	
	$db_con = @dba_open($db_path, "c","db4");
	if(!$db_con){events("berekley_db_size:: FATAL!!!::$db_path, unable to open"); return false; }
	
	if($ipaddr<>null){$keymd5=md5("$familysite$ipaddr");}
	if($mac<>null){$keymd5=md5("$familysite$mac"); }
	if($uid<>null){$keymd5=md5("$familysite$uid"); }
	
	if(!@dba_exists($keymd5,$db_con)){
		$array["IPADDR"]=$ipaddr;
		$array["MAC"]=$mac;
		$array["UID"]=$uid;
		$array["WWW"]=$familysite;
		$array["category"]=$category;
		$array["HITS"]=1;
		$array["SIZE"]=$SIZE;
		events("berekley_db_access:: NEW $mac,$ipaddr,$uid $familysite hits:1 Size:$SIZE");
		dba_replace($keymd5,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	$dba_fetch=dba_fetch($keymd5,$db_con);
	$array=unserialize($dba_fetch);
	
	$SIZEA=intval($array["SIZE"]);
	$HITSA=intval($array["HITS"])+1;
	
	
	
	
	$array["SIZE"]=$SIZEA+$SIZE;
	$sizemb=$array["SIZE"];
	$sizemb=$sizemb/1024;
	$sizemb=round($sizemb/1024,2);
	//events("berekley_db_access:: ADD $mac,$ipaddr,$uid $familysite hits:$HITSA Size:( $SIZEA + $SIZE) = {$array["SIZE"]} ($sizemb MB)");
	
	$array["HITS"]=$HITSA;
	$array["IPADDR"]=$ipaddr;
	$array["MAC"]=$mac;
	$array["UID"]=$uid;
	$array["WWW"]=$familysite;
	$array["category"]=$category;
	dba_replace($keymd5,serialize($array),$db_con);
	@dba_close($db_con);
	
}

function berekley_proto($proto,$familysite,$SIZE,$cached){
	$db_path="/var/log/squid/".date("Ymd")."_proto.db";
	if(!berekley_db_create($db_path)){return;}
	
	$db_con = @dba_open($db_path, "c","db4");
	if(!$db_con){
		events("berekley_db_size:: FATAL!!!::$db_path, unable to open");
		return false;
	}
	
	$keymd5=$familysite;
	
	if(!@dba_exists($keymd5,$db_con)){
		
		$array[$proto]["SIZE"]=$SIZE;
		$array[$proto]["HIT"]=1;
		dba_replace($keymd5,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	
	$array=unserialize(dba_fetch($keymd5,$db_con));
	$array[$proto]["SIZE"]=intval($array[$proto]["SIZE"])+$SIZE;
	$array[$proto]["HIT"]=intval($array[$proto]["HIT"])+1;
	dba_replace($keymd5,serialize($array),$db_con);
	@dba_close($db_con);
	
}


function berekley_db_size($familysite,$mac,$ipaddr,$uid,$SIZE,$category=null,$cached,$MimeType){
	if($SIZE==0){return;}
	if(trim($familysite)==null){return;}
	if($GLOBALS["DEBUG_CACHES"]){
		events("$familysite: Mime: $MimeType cached=$cached");
	}
	
	berekley_db();
	berekley_db_access($familysite,$mac,$ipaddr,$uid,$SIZE,$category,$cached,$MimeType);
	berekly_db_mime($mac,$ipaddr,$uid,$SIZE,$MimeType);
	// Cached or not.
	if($cached==1){
		berekley_db_cached($familysite,$SIZE);
		
	}
	
	if($cached==0){
		berekley_db_notcached($SIZE);
		berekley_db_notcached_www($familysite,$SIZE);
	}

	$db_con = @dba_open($GLOBALS["DBPATH"], "c","db4");
	if(!$db_con){
		events("berekley_db_size:: FATAL!!!::{$GLOBALS["DBPATH"]}, unable to open");
		return false;
	}
	

	
	$Fetched=true;
	if($ipaddr<>null){$keymd5=md5("$familysite$ipaddr");}
	if($mac<>null){$keymd5=md5("$familysite$mac"); }
	if($uid<>null){$keymd5=md5("$familysite$uid"); }
	
	$array=array();
	
	if(!@dba_exists($keymd5,$db_con)){
		$array["DAILY"][date("d")]=$SIZE;
		$array["HOURLY"][date("d")][date("H")]=$SIZE;
		$array["WEEKLY"]=$SIZE;
		$array["IPADDR"]=$ipaddr;
		$array["MAC"]=$mac;
		$array["UID"]=$uid;
		$array["WWW"]=$familysite;
		$array["category"]=$category;
		dba_replace($keymd5,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	
	
	$array=unserialize(dba_fetch($keymd5,$db_con));
	
	if(!isset($array["HOURLY"][date("d")][date("H")])){
		$array["HOURLY"][date("d")][date("H")]=$SIZE;
	}else{
		$array["HOURLY"][date("d")][date("H")]=intval($array["HOURLY"][date("d")][date("H")])+$SIZE;
	}
	
	
	if(!isset($array["WEEKLY"])){
		$array["WEEKLY"]=$array["DAILY"][date("d")];
	}else{
		$array["WEEKLY"]=intval($array["WEEKLY"])+$SIZE;
	}	
	
	
	if(!isset($array["DAILY"][date("d")])){
		$array["DAILY"][date("d")]=$SIZE;
	}else{
		$array["DAILY"][date("d")]=intval($array["DAILY"][date("d")])+$SIZE;
	}	
	
	$array["category"]=$category;
	dba_replace($keymd5,serialize($array),$db_con);
	@dba_close($db_con);
}


function ConfigUfdcat(){
	events("Starting: Using a remote category service: version 2014-12-24...");
	$GLOBALS["RemoteUfdbCat"]=0;
	$GLOBALS["DisableLogFileDaemonCategories"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonCategories"));
	$GLOBALS["EnableLocalUfdbCatService"]=trim(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableLocalUfdbCatService")));
	
	
	if($GLOBALS["EnableLocalUfdbCatService"]==1){
		events("Using the *** LOCAL *** Categories service");
		$GLOBALS["RemoteUfdbCat"]=0;
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		return;
	}
	
	
	if(is_file("/etc/artica-postfix/settings/Daemons/RemoteUfdbCat")){
		$GLOBALS["RemoteUfdbCat"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteUfdbCat"));
		events("Starting: Using a remote category service: {$GLOBALS["RemoteUfdbCat"]}");
	}
	
	
	if($GLOBALS["RemoteUfdbCat"]==1){
		$GLOBALS["ufdbCatInterface"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface"));
		$GLOBALS["ufdbCatPort"]=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatPort")));
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		events("Starting: Using a remote category service: ufdbCatInterface:{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]}");
		
		if($GLOBALS["ufdbCatInterface"]==null){
			events("Warning: {$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} not a valid address");
			$GLOBALS["RemoteUfdbCat"]=0;
			$GLOBALS["DisableLogFileDaemonCategories"]=1;
			return ;
		}
		
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		return;
	}
	
	

	
	
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
		$GLOBALS["SquidPerformance"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
		if($GLOBALS["SquidPerformance"]>0){$GLOBALS["DisableLogFileDaemonCategories"]=1;}
	}
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
		$GLOBALS["DisableLogFileDaemonCategories"]=1;
	
	}
	
}


function ParseSizeBuffer($buffer){
	if(!class_exists("class.logfile_daemon.inc")){include_once("/usr/share/artica-postfix/ressources/class.logfile_daemon.inc"); }
	$re=explode(":::", $buffer);
	
	
	$mac=trim(strtolower($re[0]));
	if($mac=="-"){$mac==null;}
	$mac=str_replace("-", ":", $mac);
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	$ipaddr=trim($re[1]);
	
	// uid
	$uid=$re[2];
	$uid2=$re[3];
	if($uid=="-"){$uid=null;}
	if($uid2=="-"){$uid2=null;}
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid2)){$uid2=null;}
	if($uid==null){ if($uid2<>null){$uid=$uid2;} }
	
	
	
	$zdate=$re[4];
	$xtime=time();
	$SUFFIX_DATE=date("YmdH",$xtime);
	$logzdate=date("Y-m-d H:i:s",$xtime);
	
	
	$proto=$re[5];
	$uri=$re[6];
	$code_error=$re[8];
	$SIZE=$re[9];
	$SquidCode=$re[10];
	$UserAgent=urldecode($re[11]);
	$Forwarded=$re[12];
	$sitename=trim($re[13]);
	$hostname=$re[14];
	$response_time=$re[15];
	$MimeType=$re[16];
	
	$uid=str_replace("%20", " ", $uid);
	$uid=str_replace("%25", "-", $uid);
	if($uid=="-"){$uid=null;}
	$Forwarded=str_replace("%25", "", $Forwarded);
	//events("MimeType: ......: $MimeType");

	
	if(strpos($uid, '$')>0){
		if(substr($uid, strlen($uid)-1,1)=="$"){
			$uid=null;
		}
	}
	
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid)){$uid=null;}
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){
		eventsfailed("***** WRONG LINE ipaddr:$ipaddr column 13 ". @implode(" | ", $re)."*****");
		return;
	}
	
	
	if($sitename=="-"){
		$h=parse_url($uri);
		if(isset($h["host"])){$sitename=$h["host"]; }
		
		if($sitename=="-"){
			eventsfailed("***** WRONG SITENAME $sitename column 13 ". @implode(" | ", $re)."*****");
			eventsfailed("$buffer");
			eventsfailed("*");
			$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
			return;
		}
		if($sitename==null){
			eventsfailed("***** WRONG SITENAME $sitename column 13 ". @implode(" | ", $re)."*****");
			eventsfailed("$buffer");
			eventsfailed("*");
			$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
			return;
		}
	}
	

	if(strpos($sitename, ":")>0){
		$XA=explode(":",$sitename);
		$sitename=$XA[0];
	}
	
	if($sitename=="127.0.0.1"){
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		if($GLOBALS["VERBOSE"]){ events("127.0.0.1 -> uid = null -> SKIP"); }
		return;
	}
	
	if($Forwarded=="unknown"){$Forwarded=null;}
	if($Forwarded=="-"){$Forwarded=null;}
	if($Forwarded=="0.0.0.0"){$Forwarded=null;}
	if($Forwarded=="255.255.255.255"){$Forwarded=null;}
	
	
	if(strlen($Forwarded)>4){
		$ipaddr=$Forwarded;
		$mac=null;
	}
	
	$ipaddr=str_replace("%25", "-", $ipaddr);
	$mac=str_replace("%25", "-", $mac);
	if($mac=="-"){$mac=null;}
	
	if(($ipaddr=="127.0.0.1") OR ($ipaddr=="::")){if($uid==null){
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		if($GLOBALS["VERBOSE"]){ events("127.0.0.1 -> uid = null -> SKIP"); }
		return;
		}
	}
	if(preg_match("#([0-9:a-z]+)$#", $mac,$z)){$mac=$z[1];}
	
	
	
	
	if($GLOBALS["VERBOSE"]){
		
		events("ITEM: DATE......: $logzdate");
		events("ITEM: MAC.......: $mac");
		events("ITEM: IP........: $ipaddr");
		events("ITEM: Size......: $SIZE");
		events("ITEM: SQUID CODE: $SquidCode");
		events("ITEM: HTTP CODE.: $code_error");
		events("ITEM: uid.......: $uid");
		events("ITEM: uri.......: $uri");
		events("ITEM: UserAgent.: $UserAgent");
		events("ITEM: Forwarded.: $Forwarded");
		events("ITEM: SiteName..: $sitename");
	}
	if($UserAgent<>null){
		UserAuthDB($mac,$ipaddr,$uid,$hostname,$UserAgent);
	}else{
		events("No UserAgents in $buffer");
	}
	
	
	
	$GLOBALS["COUNT_HASH_TABLE"]=$GLOBALS["COUNT_HASH_TABLE"]+1;
	
	$arrayURI=parse_url($uri);
	$sitename=$arrayURI["host"];
	if(strpos($sitename, ":")){
		$xtr=explode(":",$sitename);
		$sitename=$xtr[0];
		if(preg_match("#^www\.(.+)#", $sitename,$rz)){$sitename=$rz[1];}
	}
	
	$TimeCache=date("YmdH");
	if(!isset($GLOBALS["FAMLILYSITE"][$sitename])){
		$fam=new squid_familysite();
		$GLOBALS["FAMLILYSITE"][$sitename]=$fam->GetFamilySites($sitename);
	}
	$FamilySite=$GLOBALS["FAMLILYSITE"][$sitename];
	$TablePrimaireHour="squidhour_".$TimeCache;
	$TableSizeHours="sizehour_".$TimeCache;
	$TableCacheHours="cachehour_".$TimeCache;
	$tableYoutube="youtubehours_".$TimeCache;
	$tableSearchWords="searchwords_".$TimeCache;
	$tableQuotaTemp="quotatemp_".$TimeCache;
	$category=null;
	
	if($GLOBALS["DisableLogFileDaemonCategories"]==0){
		if($GLOBALS["VERBOSE"]){$time_start = microtime(true);}
		$category=ufdbcat($sitename);
		if($GLOBALS["VERBOSE"]){$time_end = microtime(true);$time_calc = $time_end - $time_start;}
		if($GLOBALS["VERBOSE"]){events("$sitename = $category {$time_calc}ms");}
	
	}
	$logfile_daemon=new logfile_daemon();
	$cached=$logfile_daemon->CACHEDORNOT($SquidCode);
	
	
	berekley_proto($proto,$GLOBALS["FAMLILYSITE"][$sitename],$SIZE,$cached);
	berekley_db_size($GLOBALS["FAMLILYSITE"][$sitename],$mac,$ipaddr,$uid,$SIZE,$category,$cached,$MimeType);
	FileSystemUserAgent($UserAgent);
	NewComputer($mac,$ipaddr);
	
	
	
	if(!isset($GLOBALS["RTTCREATED"][$TimeCache])){
		events("Creating RTTH_$TimeCache table...");
		if(create_tables($TimeCache)){
			$GLOBALS["RTTCREATED"][$TimeCache]=true;
		}
	}
	
	$sql="INSERT IGNORE INTO `squidlogs`.`RTTH_$TimeCache` (`xtime`,`sitename`,`ipaddr`,`uid`,`MAC`,`size`) VALUES('$xtime','$FamilySite','$ipaddr','$uid','$mac','$SIZE')";
	if($GLOBALS["VERBOSE"]){$time_start = microtime(true);}
	if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array( "TABLE"=>"RTTH_$TimeCache","CMD"=>$sql))); }
	
	if($GLOBALS["VERBOSE"]){$time_end = microtime(true);$time_calc = $time_end - $time_start;}
	if($GLOBALS["VERBOSE"]){events("RTTH_$TimeCache {$time_calc}ms DisableLogFileDaemonMySQL={$GLOBALS["DisableLogFileDaemonMySQL"]}");}
	
	
	
	
	
	$SearchWords=$logfile_daemon->SearchWords($uri);
	$uri=xmysql_escape_string2($uri);
		
		
	if(!isset($GLOBALS["CODE_TO_STRING"][$code_error])){$GLOBALS["CODE_TO_STRING"][$code_error]=$logfile_daemon->codeToString($code_error); }
	
		
	$zMD5=md5("$uri$xtime$mac$ipaddr");
	$TYPE=$GLOBALS["CODE_TO_STRING"][$code_error];
	$cached=$GLOBALS["CACHEDX"][$SquidCode];
	$UserAgent=xmysql_escape_string2($UserAgent);
		
	

		
		
	if($GLOBALS["VERBOSE"]){$time_start = microtime(true);}
	$sql="INSERT IGNORE INTO `$TableSizeHours` (`zDate`,`size`,`cached`) VALUES ('$logzdate','$SIZE','$cached')";
	if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array("TimeCache"=>$TimeCache,"TABLE"=>$TableSizeHours,"CMD"=>$sql)));}
	if($GLOBALS["VERBOSE"]){$time_end = microtime(true);$time_calc = $time_end - $time_start;}
	if($GLOBALS["VERBOSE"]){events("$TableSizeHours = {$time_calc}ms");}
		
		
	$sql="INSERT IGNORE INTO `$tableQuotaTemp` (`xtime`,`keyr`,`ipaddr`,`familysite`,`servername`,`uid`,`MAC`,`size`) VALUES 
	('$logzdate','$zMD5','$ipaddr','$FamilySite','$FamilySite','$uid','$mac','$SIZE')";
	if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array("TimeCache"=>$TimeCache,"TABLE"=>$tableQuotaTemp,"CMD"=>$sql)));}
	
	$sql="INSERT IGNORE INTO `$TablePrimaireHour` (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`hostname`,`zDate`,`zMD5`,`uid`,`QuerySize`,`cached`,`MAC`,`category`) VALUES ('$sitename','$uri','$TYPE','$TYPE','$ipaddr','$hostname','$logzdate','$zMD5','$uid','$SIZE','$cached','$mac','$category')";
	if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array( "TABLE"=>$TablePrimaireHour,"CMD"=>$sql))); }
		
	$sql="INSERT IGNORE INTO `$TableCacheHours` (`zDate`,`size`,`cached`,`familysite`) VALUES ('$logzdate','$SIZE','$cached','$FamilySite')";
	if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array("TimeCache"=>$TimeCache,"TABLE"=>$TableCacheHours,"CMD"=>$sql)));}
		
	if(strpos(" $uri", "youtube")>0){
		$VIDEOID=$logfile_daemon->GetYoutubeID($uri);
		if($VIDEOID<>null){
			$sql="INSERT IGNORE INTO `$tableYoutube` (`zDate`,`ipaddr`,`hostname`,`uid`,`MAC` ,`account`,`youtubeid`) VALUES ('$logzdate','$ipaddr','','$uid','$mac','0','$VIDEOID')";
			events_youtube($sql);
			if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array("TimeCache"=>$TimeCache,"TABLE"=>$tableYoutube,"CMD"=>$sql)));}
		}
	}
		
		
		
	if(is_array($SearchWords)){
		$words=xmysql_escape_string2($SearchWords["WORDS"]);
		$sql="INSERT IGNORE INTO `$tableSearchWords` (`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`) VALUES ('$zMD5','$sitename','$logzdate','$ipaddr','$hostname','$uid','$mac','0','$FamilySite','$words')";
		if(!SEND_MYSQL($sql)){@file_put_contents("/var/log/squid/mysql-rtterrors/".md5($sql), serialize(array("TimeCache"=>$TimeCache,"TABLE"=>$tableYoutube,"CMD"=>$sql)));}
	}
		
		
		
	$GLOBALS["ACCEPTED_REQUESTS"]=$GLOBALS["ACCEPTED_REQUESTS"]+1;
	
	if(count($GLOBALS["CACHE_SQL"])>2){ 
		events("CACHE_SQL = ".count($GLOBALS["CACHE_SQL"]." seems 2 minutes"));
		empty_TableHash();
	}
	
	
	
	
	
	$dd=date("Hi");
	if(count($GLOBALS["CACHE_SQL"][$dd])>1000){
		events("CACHE_SQL[$dd] = ".count($GLOBALS["CACHE_SQL"][$dd]));
		empty_TableHash();
	}
		
	return;
	
	
	
	$GLOBALS["RTTHASH"][$SUFFIX_DATE][]=array(
			"TIME"=>$xtime,
			"MAC"=>$mac,
			"IPADDR"=>$ipaddr,
			"SIZE"=>$SIZE,
			"SQUID_CODE"=>$SquidCode,
			"HTTP_CODE"=>$code_error,
			"UID"=>$uid,
			"URI"=>$uri,
			"USERAGENT"=>$UserAgent,
			"SITENAME"=>$sitename,
			"HOSTNAME"=>$hostname,
			"RESPONSE_TIME"=>$response_time
			);
	
	$GLOBALS["ACCEPTED_REQUESTS"]=$GLOBALS["ACCEPTED_REQUESTS"]+1;
	if(count($GLOBALS["RTTHASH"][$SUFFIX_DATE])>50){
		if($GLOBALS["VERBOSE"]){events("-> empty_TableHash()");}
		empty_TableHash();
	}
	
	if($GLOBALS["VERBOSE"]){events("---------------------- DONE ----------------------");}
}

function events($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);
}
function events_youtube($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.youtube.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);
}
function eventsfailed($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.failed.debug";
	
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);	
}

function empty_TableHash(){
	
	$Dir="/var/log/squid/mysql-rthash";
	if(count($GLOBALS["RTTHASH"])>0){
		reset($GLOBALS["RTTHASH"]);
		while (list ($xtime, $rows) = each ($GLOBALS["RTTHASH"]) ){
			$rand=rand(5, 90000);
			if(count($rows)>0){
				$GLOBALS["PURGED"]=$GLOBALS["PURGED"]+count($rows);
				events("Purge RTTHASH: $xtime = ".count($rows)." elements - purged {$GLOBALS["PURGED"]} elements");
				@file_put_contents("$Dir/hash.$xtime.".microtime(true).".$rand.sql",serialize($GLOBALS["RTTHASH"]));
			}
			
		}
	}
	
	$Dir="/var/log/squid/mysql-squid-queue";
	
	if(count($GLOBALS["CACHE_SQL"])>0){
		reset($GLOBALS["CACHE_SQL"]);
		while (list ($xtime, $rows) = each ($GLOBALS["CACHE_SQL"]) ){
			$GLOBALS["PURGED"]=$GLOBALS["PURGED"]+count($rows);
			$rand=rand(5, 90000);
			events("Purge CACHE_SQL: $xtime = ".count($rows)." elements - purged {$GLOBALS["PURGED"]} elements");
			@file_put_contents("$Dir/$xtime.".microtime(true).".$rand.sql",serialize($rows));
		}
	}
	
	
	FileSystemUserAgent_empty();
	
	
	$GLOBALS["CACHE_SQL"]=array();
	$GLOBALS["RTTHASH"]=array();
	$GLOBALS["COUNT_HASH_TABLE"]=0;
	
}

function FileSystemUserAgent_empty(){
	if(count($GLOBALS["UserAgents"])==0){return;}
	$Dir="/var/log/squid/mysql-UserAgents";
	$rand=rand(5, 90000);
	$filetemp="$Dir/UsersAgents.".microtime(true).".$rand.sql";
	events("Purge $filetemp = ".count($GLOBALS["UserAgents"])." UserAgents");
	@file_put_contents($filetemp,serialize($GLOBALS["UserAgents"]));
	$GLOBALS["UserAgents"]=array();
	
}

function FileSystemUserAgent($UserAgent){
	if(trim($UserAgent)==null){return;}
	$UserAgent_md=md5($UserAgent);
	if(isset($GLOBALS[$UserAgent])){return;}
	$GLOBALS["UserAgent"]=true;
	if(count($GLOBALS["UserAgents"])<20){return;}
	FileSystemUserAgent_empty();
	
}

function NewComputer($mac,$ipaddr){
	if($mac==null){return;}
	if(isset($GLOBALS["COMPUTERS_MEM"][$mac])){return;}
	$Dir="/var/log/squid/mysql-computers";
	$rand=rand(5, 90000);
	$filetemp="$Dir/Computers.$mac.sql";
	if(is_file($filetemp)){$GLOBALS["COMPUTERS_MEM"][$mac]=true;return;}
	$array["IP"]=$ipaddr;
	$array["MAC"]=$mac;
	@file_put_contents($filetemp,serialize($array));
	$GLOBALS["COMPUTERS_MEM"][$mac]=true;
}


function SEND_MYSQL($sql){
	$socket=null;
	
	if($GLOBALS["DisableLogFileDaemonMySQL"]==1){
		$dd=date("Hi");
		$GLOBALS["CACHE_SQL"][$dd][]=$sql;
		return true;
	}
	
	
	if(!isset($GLOBALS["SEND_MYSQL"])){
	
		$GLOBALS["SEND_MYSQL"]["USER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$GLOBALS["SEND_MYSQL"]["PASSWORD"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		$GLOBALS["SEND_MYSQL"]["SERVER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		if($GLOBALS["SEND_MYSQL"]["USER"]==null){$GLOBALS["SEND_MYSQL"]["USER"]="root";}
		if($GLOBALS["SEND_MYSQL"]["SERVER"]==null){$GLOBALS["SEND_MYSQL"]["SERVER"]="127.0.0.1";}
		if($GLOBALS["SEND_MYSQL"]["SERVER"]=="localhost"){$GLOBALS["SEND_MYSQL"]["SERVER"]="127.0.0.1";}
	}
		
	
	if($GLOBALS["ProxyUseArticaDB"]==0){
		if($GLOBALS["EnableSquidRemoteMySQL"]==1){
			$bd=@mysql_connect("{$GLOBALS["squidRemostatisticsServer"]}:{$GLOBALS["squidRemostatisticsPort"]}",$GLOBALS["squidRemostatisticsUser"],$GLOBALS["squidRemostatisticsPassword"]);
		}
		
	}else{
		$bd=@mysql_connect(":/var/run/mysqld/squid-db.sock","root",null);
	}
	if(!$bd){
		$des=@mysql_error(); 
		$errnum=@mysql_errno();
		events("MySQL error: $errnum $des");
		return false;
	}
	$ok=@mysql_select_db("squidlogs",$bd);
	if(!$ok){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		events("MySQL error: $errnum $des");
		@mysql_close($bd);
		return false;
	}
	$results=@mysql_query($sql,$bd);
	if(!$results){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		events("MySQL error: $errnum $des");
		events("MySQL error: $sql");
		return false;
	}
	
	@mysql_close($bd);
	return true;
}
function xmysql_escape_string2($line){
	
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function ufdbcat($sitename){

	if(isset($GLOBALS["CATEGORYOF"])){
		if(count($GLOBALS["CATEGORYOF"])>50000){$GLOBALS["CATEGORYOF"]=array();}
	}
	if(isset($GLOBALS["CATEGORYOF"][$sitename])){return $GLOBALS["CATEGORYOF"][$sitename];}
	
	$time_start = microtime_float();
	$s=new mysql_catz();
	$resp=$s->GET_CATEGORIES($sitename);
	$time_stop = microtime_float();
	$TimeExec = round($time_stop - $time_start,3);
	if($resp<>null){
		$GLOBALS["CATEGORYOF"][$sitename]=$resp;
		return $GLOBALS["CATEGORYOF"][$sitename];
	}
	
	

}
function _xTransArray(){

	$trans["category_society"]="society";
	$trans["category_association"]="associations";
	$trans["category_publicite"]="publicite";
	$trans["category_phishtank"]="phishtank";
	$trans["category_shopping"]="shopping";
	$trans["category_abortion"]="abortion";
	$trans["category_agressive"]="agressive";
	$trans["category_alcohol"]="alcohol";
	$trans["category_animals"]="animals";
	$trans["category_associations"]="associations";
	$trans["category_astrology"]="astrology";
	$trans["category_audio_video"]="audio-video";
	$trans["category_automobile_bikes"]="automobile/bikes";
	$trans["category_automobile_boats"]="automobile/boats";
	$trans["category_automobile_carpool"]="automobile/carpool";
	$trans["category_automobile_cars"]="automobile/cars";
	$trans["category_automobile_planes"]="automobile/planes";
	$trans["category_bicycle"]="bicycle";
	$trans["category_blog"]="blog";
	$trans["category_books"]="books";
	$trans["category_browsersplugins"]="browsersplugins";
	$trans["category_celebrity"]="celebrity";
	$trans["category_chat"]="chat";
	$trans["category_children"]="children";
	$trans["category_cleaning"]="cleaning";
	$trans["category_clothing"]="clothing";
	$trans["category_converters"]="converters";
	$trans["category_cosmetics"]="cosmetics";
	$trans["category_culture"]="culture";
	$trans["category_dangerous_material"]="dangerous_material";
	$trans["category_dating"]="dating";
	$trans["category_dictionaries"]="dictionaries";
	$trans["category_downloads"]="downloads";
	$trans["category_drugs"]="drugs";
	$trans["category_dynamic"]="dynamic";
	$trans["category_electricalapps"]="electricalapps";
	$trans["category_electronichouse"]="electronichouse";
	$trans["category_filehosting"]="filehosting";
	$trans["category_finance_banking"]="finance/banking";
	$trans["category_finance_insurance"]="finance/insurance";
	$trans["category_finance_moneylending"]="finance/moneylending";
	$trans["category_finance_other"]="finance/other";
	$trans["category_finance_realestate"]="finance/realestate";
	$trans["category_financial"]="financial";
	$trans["category_forums"]="forums";
	$trans["category_gamble"]="gamble";
	$trans["category_games"]="games";
	$trans["category_genealogy"]="genealogy";
	$trans["category_gifts"]="gifts";
	$trans["category_governements"]="governments";
	$trans["category_governments"]="governments";
	$trans["category_green"]="green";
	$trans["category_hacking"]="hacking";
	$trans["category_handicap"]="handicap";
	$trans["category_health"]="health";
	$trans["category_hobby_arts"]="hobby/arts";
	$trans["category_hobby_cooking"]="hobby/cooking";
	$trans["category_hobby_other"]="hobby/other";
	$trans["category_hobby_pets"]="hobby/pets";
	$trans["category_paytosurf"]="paytosurf";
	$trans["category_terrorism"]="terrorism";
	$trans["category_hobby_fishing"]="hobby/fishing";
	$trans["category_hospitals"]="hospitals";
	$trans["category_houseads"]="houseads";
	$trans["category_housing_accessories"]="housing/accessories";
	$trans["category_housing_doityourself"]="housing/doityourself";
	$trans["category_housing_builders"]="housing/builders";
	$trans["category_housing_reale_state"]="housing/reale_state";
	$trans["category_humanitarian"]="humanitarian";
	$trans["category_imagehosting"]="imagehosting";
	$trans["category_industry"]="industry";
	$trans["category_internal"]="internal";
	$trans["category_isp"]="isp";
	$trans["category_smalladds"]="smalladds";
	$trans["category_stockexchnage"]="stockexchange";
	$trans["category_jobsearch"]="jobsearch";
	$trans["category_jobtraining"]="jobtraining";
	$trans["category_justice"]="justice";
	$trans["category_learning"]="learning";
	$trans["category_liste_bu"]="liste_bu";
	$trans["category_luxury"]="luxury";
	$trans["category_mailing"]="mailing";
	$trans["category_malware"]="malware";
	$trans["category_manga"]="manga";
	$trans["category_maps"]="maps";
	$trans["category_marketingware"]="marketingware";
	$trans["category_medical"]="medical";
	$trans["category_mixed_adult"]="mixed_adult";
	$trans["category_mobile_phone"]="mobile-phone";
	$trans["category_models"]="models";
	$trans["category_movies"]="movies";
	$trans["category_music"]="music";
	$trans["category_nature"]="nature";
	$trans["category_news"]="news";
	
	$trans["category_passwords"]="passwords";
	$trans["category_phishing"]="phishing";
	$trans["category_photo"]="photo";
	$trans["category_pictures"]="pictures";
	$trans["category_pictureslib"]="pictureslib";
	$trans["category_politic"]="politic";
	$trans["category_porn"]="porn";
	$trans["category_press"]="news";
	$trans["category_proxy"]="proxy";
	$trans["category_publicite"]="publicite";
	$trans["category_reaffected"]="reaffected";
	$trans["category_recreation_humor"]="recreation/humor";
	$trans["category_recreation_nightout"]="recreation/nightout";
	$trans["category_recreation_schools"]="recreation/schools";
	$trans["category_recreation_sports"]="recreation/sports";
	$array["category_getmarried"]="getmarried";
	$array["category_police"]="police";
	$trans["category_recreation_travel"]="recreation/travel";
	$trans["category_recreation_wellness"]="recreation/wellness";
	$trans["category_redirector"]="redirector";
	$trans["category_religion"]="religion";
	$trans["category_remote_control"]="remote-control";
	$trans["category_ringtones"]="ringtones";
	$trans["category_sciences"]="sciences";
	$trans["category_science_astronomy"]="science/astronomy";
	$trans["category_science_computing"]="science/computing";
	$trans["category_science_weather"]="science/weather";
	$trans["category_science_chemistry"]="science/chemistry";
	$trans["category_searchengines"]="searchengines";
	$trans["category_sect"]="sect";
	$trans["category_sexual_education"]="sexual_education";
	$trans["category_sex_lingerie"]="sex/lingerie";
	$trans["category_smallads"]="smallads";
	$trans["category_socialnet"]="socialnet";
	$trans["category_spyware"]="spyware";
	$trans["category_sslsites"]="sslsites";
	$trans["category_stockexchange"]="stockexchange";
	$trans["category_strict_redirector"]="redirector";
	$trans["category_strong_redirector"]="redirector";
	$trans["category_suspicious"]="suspicious";
	$trans["category_teens"]="teens";
	$trans["category_tobacco"]="tobacco";
	$trans["category_tracker"]="tracker";
	$trans["category_translator"]="translators";
	$trans["category_translators"]="translators";
	$trans["category_transport"]="transport";
	$trans["category_tricheur"]="tricheur";
	$trans["category_updatesites"]="updatesites";
	$trans["category_violence"]="violence";
	$trans["category_warez"]="warez";
	$trans["category_weapons"]="weapons";
	$trans["category_webapps"]="webapps";
	$trans["category_webmail"]="webmail";
	$trans["category_webphone"]="webphone";
	$trans["category_webplugins"]="webplugins";
	$trans["category_webradio"]="webradio";
	$trans["category_webtv"]="webtv";
	$trans["category_wine"]="wine";
	$trans["category_womanbrand"]="womanbrand";
	$trans["category_horses"]="horses";
	$trans["category_meetings"]="meetings";
	$trans["category_tattooing"]="tattooing";
	$trans["category_advertising"]="publicite";
	$trans["category_getmarried"]="getmarried";
	$trans["category_literature"]="literature";
	$trans["category_police"]="police";
	$trans["category_search"]="searchengines";
		
	return $trans;

}
function tablename_tocat($tablename){
	
	$trans=_xTransArray();
	if(!isset($trans[$tablename])){return $tablename;}
		
}

function create_tables($TimeCache){
	
	REALTIME_RTTH($TimeCache);
	REALTIME_squidhour($TimeCache);
	REALTIME_cachehour($TimeCache);
	REALTIME_sizehour($TimeCache);
	REALTIME_youtubehours($TimeCache);
	REALTIME_quotatemp($TimeCache);
	REALTIME_searchwords($TimeCache);
	return true;
	
}

?>