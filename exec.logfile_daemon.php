#!/usr/bin/php -q
<?php
$GLOBALS["COUNT"]=0;
$GLOBALS["VERSION"]="25Jan2014";
$GLOBALS["ACT_AS_REVERSE"]=false;
$GLOBALS["NO_DISK"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["LOG_HOSTNAME"]=false;
$GLOBALS["COUNT_WAKEUP"]=0;
$GLOBALS["COUNT_RQS"]["TIME"]=time();
$GLOBALS["ACCEPTED_REQUESTS"]=0;
$GLOBALS["REFUSED_REQUESTS"]=0;
$GLOBALS["COUNT_HASH_TABLE"]=0;
$GLOBALS["KEYUSERS"]=array();
$GLOBALS["RTTHASH"]=array();
$timezones=@file_get_contents("/etc/artica-postfix/settings/Daemons/timezones"); 
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="{$GLOBALS["ARTICALOGDIR"]}"; } }
@mkdir("/var/log/squid/mysql-queue",0755,true);
@mkdir("/var/log/squid/mysql-rttime",0755,true);
@mkdir("/var/log/squid/mysql-rthash",0755,true);
if($timezones<>null){@date_default_timezone_set($timezones);}
error_reporting(0);
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


if($argv[1]=="--no-disk"){$GLOBALS["NO_DISK"]=true;}
if($argv[1]=="--dump-mac"){print_r(unserialize(@file_get_contents("/etc/squid3/usersMacs.db")));exit;}
$logthis=array();
if($GLOBALS["VERBOSE"]){$logthis[]="Verbosed";}
if($GLOBALS["ACT_AS_REVERSE"]){$logthis[]=" Act as reverse...";}
$GLOBALS["MYPID"]=getmypid();
events("Starting PID: {$GLOBALS["MYPID"]} - TimeZone: $timezones,  version: {$GLOBALS["VERSION"]}, ".@implode(", ", $logthis) ." ({$argv[1]})");
$GLOBALS["COUNT_RQS"]=0;
$GLOBALS["PURGED"]=0;
events("Starting PID: waiting connections...");
$DCOUNT=0;

@file_put_contents("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid", time());

$pipe = fopen("php://stdin", "r");
$buffer=null;

while(!feof($pipe)){
	if($GLOBALS["VERBOSE"]){ events(" fgets PIPE");}
	$buffer= trim(fgets($pipe));
	if($GLOBALS["VERBOSE"]){ events(" fgets PIPE -> ".strlen($buffer));}
	$GLOBALS["COUNT_RQS"]=$GLOBALS["COUNT_RQS"]+1;
	
	if($GLOBALS["VERBOSE"]){events( __LINE__." {$GLOBALS["COUNT_RQS"]} connexions");}
	if($GLOBALS["VERBOSE"]){ events("*******************************"); events("Buffer: $buffer"); }
	if(is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.debug")){
		events("Turn into debug log");
		@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.debug");
		$GLOBALS["VERBOSE"]=true;
	}
	
	if(is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.normal")){
		@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.normal");
		events("Turn off debug log");
		$GLOBALS["VERBOSE"]=true;
	}
	
	if(strpos($buffer, "TCP_DENIED/403")>0){
		if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		continue;
	}
	
	if($GLOBALS["VERBOSE"]){events( __LINE__."] -> WAKEUP ?");}
	Wakeup();
	
	if(strpos($buffer, "NONE:HIER_NONE")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
	if(strpos($buffer, "error:invalid-request")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;continue;}
	if(strpos("NONE error:", $buffer)>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return; }
	if(strpos($buffer, "GET cache_object")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
	
	
	$F=substr($buffer, 0,1);

	
	
	
	
	if($F=="L"){
		$GLOBALS["WAKEUP_LOGS"]=$GLOBALS["WAKEUP_LOGS"]+1;
		$buffer=substr($buffer, 1,strlen($buffer));
		if($GLOBALS["VERBOSE"]){events( __LINE__." Accepting request ". strlen($buffer));}
		
		
		
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
		ParseSizeBuffer($buffer);
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



function ParseSizeBuffer($buffer){
	
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
	if($uid==null){ if($uid2<>null){$uid=$uid2;} }
	
	
	
	$zdate=$re[4];
	$xtime=time();
	$SUFFIX_DATE=date("YmdH",$xtime);
	
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
	
	
	if($sitename=="-"){
		eventsfailed("***** WRONG SITENAME $sitename *****");
		eventsfailed("$buffer");
		eventsfailed("*");
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		return;
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
	
	
	
	
	if(($ipaddr=="127.0.0.1") OR ($ipaddr=="::")){if($uid==null){
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		if($GLOBALS["VERBOSE"]){ events("127.0.0.1 -> uid = null -> SKIP"); }
		return;
		}
	}
	
	
	
	
	if($GLOBALS["VERBOSE"]){
		$logzdate=date("Y-m-d H:i:s",$xtime);
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
	
	$GLOBALS["COUNT_HASH_TABLE"]=$GLOBALS["COUNT_HASH_TABLE"]+1;
	
	$arrayURI=parse_url($uri);
	$sitename=$arrayURI["host"];
	if(strpos($sitename, ":")){
		$xtr=explode(":",$sitename);
		$sitename=$xtr[0];
		if(preg_match("#^www\.(.+)#", $sitename,$rz)){$sitename=$rz[1];}
	}
	
	
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
	if(count($GLOBALS["RTTHASH"])==0){return;}
	reset($GLOBALS["RTTHASH"]);
	while (list ($xtime, $rows) = each ($GLOBALS["RTTHASH"]) ){
		$rand=rand(5, 90000);
		if(count($rows)>0){
			$GLOBALS["PURGED"]=$GLOBALS["PURGED"]+count($rows);
			events("Purge RTTHASH: $xtime = ".count($rows)." elements - purged {$GLOBALS["PURGED"]} elements");
			@file_put_contents("$Dir/hash.$xtime.".microtime(true).".$rand.sql",serialize($GLOBALS["RTTHASH"]));
		}
		
	}
	$GLOBALS["RTTHASH"]=array();
	$GLOBALS["COUNT_HASH_TABLE"]=0;
	
}












?>