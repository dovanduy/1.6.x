#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG_UNLOCK"]=false;
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");



WLOG("Starting PID:".getmypid()."");
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
	$trace=@debug_backtrace();
	$filename="/var/log/squid/ufdbguard.unlock.log";
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["PID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
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
	
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("ufdbunlock")==0){return false;}
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
	
	$IPCalls=new IP();
	
	if($IPCalls->isIPAddress($XFORWARD)){$IPADDR=$XFORWARD;}
	
	if(preg_match("#(.+?):[0-9]+#", $WWW,$re)){$WWW=$re[1];}
	if(preg_match("#^www\.(.+)#", $WWW,$re)){$WWW=$re[1];}
	
	
	$WWW=$q->GetFamilySites($WWW);
	if(!isset($GLOBALS["ufdbunlock_c"])){$GLOBALS["ufdbunlock_c"]=0;}
	
	
	
	if($GLOBALS["ufdbunlock_c"]>90){
		$q->QUERY_SQL("DELETE FROM ufdbunlock WHERE `finaltime` <". time());
			if(!$q->ok){WLOG("$q->mysql_error");}
			$GLOBALS["ufdbunlock_c"]=0;
		}
	
	
	
	
	if($MAC<>null){
		if($GLOBALS["DEBUG_UNLOCK"]){WLOG("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND MAC='$MAC'");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND MAC='$MAC'"));
		if(!$q->ok){WLOG("$q->mysql_error");}
		
		if($ligne["md5"]<>null){
			if($ligne["finaltime"]<time()){return false;}
				if($IPADDR<>null){
					$q->QUERY_SQL("UPDATE ufdbunlock SET ipaddr='$IPADDR' WHERE MAC='$MAC'");
				}
	
				return true;
			}
		}
					
	
		if($IPADDR<>null){
				if($GLOBALS["DEBUG_UNLOCK"]){WLOG("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND ipaddr='$IPADDR'");}
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT md5,finaltime FROM ufdbunlock WHERE `www`='$WWW' AND ipaddr='$IPADDR'"));
				if(!$q->ok){WLOG("$q->mysql_error");}
				$time=time();
	
	
				if($ligne["md5"]<>null){
					if($ligne["finaltime"]<time()){
						WLOG("{$ligne["finaltime"]} < $time -> FALSE");
						return false;}
	
						if($MAC<>null){
							$q->QUERY_SQL("UPDATE ufdbunlock SET MAC='$MAC' WHERE ipaddr='$IPADDR'");
						}
	
							return true;
					}
				}
	}