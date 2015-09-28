#!/usr/bin/php -q
<?php
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
$GLOBALS["DEBUG"]=false;
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
error_reporting(0);



$GLOBALS["MYPID"]=getmypid();
if(preg_match("#--gpid=([0-9]+)#", @implode(" ", $argv),$re)){
	$GLOBALS["GPID"]=$re[1];
	WLOG("Starting Group Number:{$GLOBALS["GPID"]}");
	LOADRULES($GLOBALS["GPID"]);
}


WLOG("Starting PID:{$GLOBALS["MYPID"]}");
WLOG("SSL : Starting SNI certificate verification.. ARGV=[".@implode(" ", $argv)."]");
$q=new influx();

$c=0;
$DCOUNT=0;
while (!feof(STDIN)) {
	$data = trim(fgets(STDIN));
	if($data==null){continue;}
	
	$c++;
	$DCOUNT++;
	$array["tags"]["website"]=$data;
	$array["fields"]["RQS"]=1;
	$q->insert("sni_certs", $array);
	
	
	$result=isMatches($data);
	
	if($c>500){
		WLOG("$DCOUNT requests...");
		$c=0;
	}
	
	if(!$result){
		fwrite(STDOUT, "ERR\n");
		continue;
	}

	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] OK");}
	fwrite(STDOUT, "OK\n");
	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT events");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_ssl.log";
	if(isset($trace[0])){$called=" called by ". basename($trace[0]["file"])." {$trace[0]["function"]}() line {$trace[0]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_159();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function _get_memory_usage_159() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function isMatches($sitename){
	$sitename=trim(strtolower($sitename));
	if(isset($GLOBALS["isMatches"][$sitename])){return $GLOBALS["isMatches"][$sitename];}
	reset($GLOBALS["RULES"]);
	while (list($regex,$none)=each($GLOBALS["RULES"])){
		if(preg_match("#$regex#i", $sitename)){
			WLOG("isMatches '$sitename' -> $regex [".__LINE__."]");
			$GLOBALS["isMatches"][$sitename]=true;
			return true;
		}
		
	}
	$GLOBALS["isMatches"][$sitename]=false;
	return false;
	
}	
	
function LOADRULES($id){
	$f=explode("\n",@file_get_contents("/etc/squid3/acls/container_{$id}.txt"));
	while (list($num,$val)=each($f)){
		$val=trim(strtolower($val));
		if($val==null){continue;}
		if(is_regex($val)){
			$GLOBALS["RULES"][$val]=true;
			continue;
		}
		
		$val=str_replace(".", "\.", $val);
		$val=str_replace("*", ".*?", $val);
		$GLOBALS["RULES"][$val]=true;
	}
	
	WLOG("Starting Group Number:{$id} ".count($GLOBALS["RULES"])." rules");
	
}

function is_regex($pattern){
	$f[]="{";
	$f[]="[";
	$f[]="+";
	$f[]="\\";
	$f[]="?";
	$f[]="$";
	$f[]=".*";
	
	while (list ($key, $val) = each ($f) ){
		if(strpos(" $pattern", $val)>0){return true;}
	}
	
	
}

function time_passed_min($StartTime=0,$EndTime=0){
	$difference = ($EndTime - $StartTime);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}