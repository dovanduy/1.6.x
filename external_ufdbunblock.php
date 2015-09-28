#!/usr/bin/php -q
<?php
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
ini_set("error_log", "/var/log/squid/ufdbguard.unlock.log");
$GLOBALS["DEBUG_UNLOCK"]=false;
$GLOBALS["PID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["PID"]}");
ap_mysql_load_params();
$GLOBALS["ufdbunlock_c"]=0;
$DCOUNT=0;
while (!feof(STDIN)) {
	$url = trim(fgets(STDIN));
	if($url==null){continue;}
	$DCOUNT++;
	$GLOBALS["ufdbunlock_c"]++;
	
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UNBLOCK::$url");}

	if(UFDGUARD_UNLOCKED($url)){
		if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UNBLOCK::OK");}
		fwrite(STDOUT, "OK\n");
		continue;
	}

	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UNBLOCK::ERR");}
	fwrite(STDOUT, "ERR\n");
	
	
}

WLOG("Stopping PID:".getmypid()." After $DCOUNT event(s)");
	
	
function WLOG($text=null){
	$called=null;
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	
	
	
	if (is_file("/var/log/squid/ufdbguard.unlock.log")) {
		$size=@filesize("/var/log/squid/ufdbguard.unlock.log");
		if($size>1000000){ unlink("/var/log/squid/ufdbguard.unlock.log"); }
	}
	error_log("$date [{$GLOBALS["PID"]}]: $text $called");
}

function _get_memory_usage_158() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}
	
	
function UFDGUARD_UNLOCKED($url){
	
	if(trim($url)==null){if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::URL is null"); return false; }}
	if(strpos(" $url", "127.0.0.1 00:00:00:00:00:00")>0){return false;}
	
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::Call api_mysql_COUNT_ROWS");}
	if(api_mysql_COUNT_ROWS("ufdbunlock")==0){return false;}
	$values=explode(" ",$url);
	$IPADDR=$values[0];
	$MAC=$values[1];
	$XFORWARD=$values[2];
	$WWW=$values[3];
	
	$IPADDR=str_replace("%25", "-", $IPADDR);
	$MAC=str_replace("%25", "-", $MAC);
	$XFORWARD=str_replace("%25", "-", $XFORWARD);
	if($XFORWARD=="-"){$XFORWARD=null;}
	if($MAC=="00:00:00:00:00:00"){$MAC=null;}
	if($MAC=="-"){$MAC=null;}

	
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::Call IPCalls");}
	$IPCalls=new IP();
	if($IPCalls->isIPAddress($XFORWARD)){$IPADDR=$XFORWARD;}
	
	if(preg_match("#(.+?):[0-9]+#", $WWW,$re)){$WWW=$re[1];}
	if(preg_match("#^www\.(.+)#", $WWW,$re)){$WWW=$re[1];}
	
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::$WWW");}
	$WWW=api_GetFamilySites($WWW);
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("api_GetFamilySites::$WWW");}
	if(!isset($GLOBALS["ufdbunlock_c"])){$GLOBALS["ufdbunlock_c"]=0;}
	
	
	
	if($GLOBALS["ufdbunlock_c"]>90){
		QUERY_MYSQL("DELETE FROM ufdbunlock WHERE `finaltime` <". time());
			//if(!$q->ok){WLOG("$q->mysql_error");}
			$GLOBALS["ufdbunlock_c"]=0;
		}
	
	
	
	
	if($MAC<>null){
		if($GLOBALS["DEBUG_UNLOCK"]){WLOG("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND MAC='$MAC'");}
		$ligne=mysql_fetch_array(QUERY_MYSQL("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND MAC='$MAC'"));
		
		
		if($ligne["md5"]<>null){
			if($ligne["finaltime"]<time()){return false;}
				if($IPADDR<>null){
					QUERY_MYSQL("UPDATE ufdbunlock SET ipaddr='$IPADDR' WHERE MAC='$MAC'");
				}
	
				return true;
			}
		}
					
	
		if($IPADDR<>null){
				if($GLOBALS["DEBUG_UNLOCK"]){WLOG("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND ipaddr='$IPADDR'");}
				$ligne=mysql_fetch_array(QUERY_MYSQL("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND ipaddr='$IPADDR'"));
				$time=time();
	
	
				if($ligne["md5"]<>null){
					if($ligne["finaltime"]<time()){
						WLOG("{$ligne["finaltime"]} < $time -> FALSE");
						return false;}
	
						if($MAC<>null){
							QUERY_MYSQL("UPDATE ufdbunlock SET MAC='$MAC' WHERE ipaddr='$IPADDR'");
						}
	
							return true;
					}
				}
	}
	
	
function ap_mysql_load_params(){
	$GLOBALS["MYSQL_SOCKET"]=null;
	$GLOBALS["MYSQL_PASSWORD"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	if($GLOBALS["MYSQL_PASSWORD"]=="!nil"){$GLOBALS["MYSQL_PASSWORD"]=null;}
	$GLOBALS["MYSQL_PASSWORD"]=stripslashes($GLOBALS["MYSQL_PASSWORD"]);
	$GLOBALS["MYSQL_USERNAME"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
	$GLOBALS["MYSQL_SERVER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
	$GLOBALS["MYSQL_PORT"]=intval(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
	if($GLOBALS["MYSQL_PORT"]==0){$GLOBALS["MYSQL_PORT"]=3306;}
	if($GLOBALS["MYSQL_SERVER"]==null){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
	$GLOBALS["MYSQL_USERNAME"]=str_replace("\r", "", $GLOBALS["MYSQL_USERNAME"]);
	$GLOBALS["MYSQL_USERNAME"]=trim($GLOBALS["MYSQL_USERNAME"]);
	$GLOBALS["MYSQL_PASSWORD"]=str_replace("\r", "", $GLOBALS["MYSQL_PASSWORD"]);
	$GLOBALS["MYSQL_PASSWORD"]=trim($GLOBALS["MYSQL_PASSWORD"]);
	
	if($GLOBALS["MYSQL_USERNAME"]==null){$GLOBALS["MYSQL_USERNAME"]="root";}
	if($GLOBALS["MYSQL_SERVER"]=="localhost"){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
	if($GLOBALS["MYSQL_SERVER"]=="127.0.0.1"){$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/squid-db.sock";}
}

function api_GetFamilySites($sitename){
	if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
	if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/class.squid.familysites.inc");}
	$fam=new squid_familysite();
	$GLOBALS["GetFamilySites"][$sitename]=$fam->GetFamilySites($sitename);
	return $GLOBALS["GetFamilySites"][$sitename];
}

function api_QUERY_SQL($sql){
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::Call api_mysql_connect");}
	$mysql_connection=api_mysql_connect();
	if(!$mysql_connection){return false;}
	
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("UFDGUARD_UNLOCKED::Call mysql_select_db");}
	$ok=@mysql_select_db("squidlogs",$mysql_connection);
	if(!$ok){
		$errnum=@mysql_errno($mysql_connection);
		$des=@mysql_error($mysql_connection);
		@mysql_close($mysql_connection);
		WLOG("mysql_select_db() failed (N:$errnum) \"$des\"");
		return false;
	}
	
	$mysql_unbuffered_query_log=null;
	if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
		$mysql_unbuffered_query_log="mysql_unbuffered_query";
		$results=@mysql_unbuffered_query($sql,$mysql_connection);
			
	}else{
		$mysql_unbuffered_query_log="mysql_query";
		$results=@mysql_query($sql,$mysql_connection);
	}

	if(!$results){
		$errnum=@mysql_errno($mysql_connection);
		$des=@mysql_error($mysql_connection);
		@mysql_close($mysql_connection);
		WLOG("$mysql_unbuffered_query_log() failed (N:$errnum) \"$des\"");
		return false;
	}
	@mysql_close($mysql_connection);
	return $results;
	
	
}
function api_mysql_COUNT_ROWS($table){
	$sql="show TABLE STATUS WHERE Name='$table'";
	if($GLOBALS["DEBUG_UNLOCK"]){WLOG("api_mysql_COUNT_ROWS::$sql");}
	$ligne=@mysql_fetch_array(api_QUERY_SQL($sql));
	if($ligne["Rows"]==null){$ligne["Rows"]=0;}
	return $ligne["Rows"];
}

	
function api_mysql_connect(){
	
	if($GLOBALS["MYSQL_SOCKET"]<>null){
		$bd=@mysql_connect(":{$GLOBALS["MYSQL_SOCKET"]}",$GLOBALS["MYSQL_USERNAME"],$GLOBALS["MYSQL_PASSWORD"]);
	}else{
		$bd=@mysql_connect("{$GLOBALS["MYSQL_SERVER"]}:{$GLOBALS["MYSQL_PORT"]}","{$GLOBALS["MYSQL_USERNAME"]}","{$GLOBALS["MYSQL_PASSWORD"]}");
	}	
	
	if($bd){return $bd;}
	$des=@mysql_error(); 
	$errnum=@mysql_errno();
	WLOG("api_mysql_connect() failed (N:$errnum) \"$des\"");
	return false;
}