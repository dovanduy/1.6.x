<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
$GLOBALS["MAIN"]=array();
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(!Build_pid_func(__FILE__,"MAIN")){
	events("Already executed.. aborting the process");
	die();
}

if($argv[1]=='--date'){echo date("Y-m-d H:i:s")."\n";}

$pid=getmypid();
$pidfile="/etc/artica-postfix/exec.nginx-tail.php.pid";
events("running $pid ");
$unix=new unix();
$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();


file_put_contents($pidfile,$pid);
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	Parseline($buffer);
	$buffer=null;
}

fclose($pipe);
events("Shutdown...");
die();



function Parseline($buffer){
$buffer=trim($buffer);
if($buffer==null){return null;}



	if(!preg_match("#^[0-9]+,\s+\[(.+?)]#",$buffer,$re)){
		events("Not filtered: $buffer");
		return;
	}
	
	$date=date("Y-m-d H:i:00");
	
	$category=trim(strtolower($re[1]));
	if(!isset($GLOBALS["MAIN"][$date][$category])){
		
		$GLOBALS["MAIN"][$date][$category]=1;
	}else{
		$GLOBALS["MAIN"][$date][$category]=$GLOBALS["MAIN"][$date][$category]+1;
	}
	
	if(count($GLOBALS["MAIN"])>2){
		dump_main();
		
	}
	
	
	

}

function dump_main(){
	$date=date("Y-m-d H:i:00");
	
	$influx=new influx();
	while (list ($xdate, $array) = each ($GLOBALS["MAIN"]) ){
		if($xdate==$date){continue;}
		while (list ($category, $count) = each ($array) ){
			$zArray=array();
			$zArray["tags"]["category"]=$category;
			$zArray["fields"]["hits"]=intval($count);
			$zArray["tags"]["proxyname"]=$GLOBALS["MYHOSTNAME"];
			squid_watchdog_events("Influx -> $xdate/$date: $category: $count");
			$influx->insert("hypercache", $zArray);
		}
		unset($GLOBALS["MAIN"][$xdate]);
		
	}
	
	reset($GLOBALS["MAIN"]);
	
}



function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/nginx.watchdog.log",false,$sourcefunction,$sourceline);
}

function IfFileTime($file,$min=10){
	if(file_time_min($file)>$min){return true;}
	return false;
}
function WriteFileCache($file){
	@unlink("$file");
	@unlink($file);
	@file_put_contents($file,"#");	
}
function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/nginx-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid]:: ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
	

?>