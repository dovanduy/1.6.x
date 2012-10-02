#!/usr/bin/php -q
<?php
  //ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(0);
  $GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $max_execution_time=ini_get('max_execution_time'); 
  WLOG("starting... max_execution_time:$max_execution_time");
  set_time_limit(0);
 

while (!feof(STDIN)) {
 $url = trim(fgets(STDIN));
 if($url<>null){
 	WLOG($url." str:".strlen($url));
  	fwrite(STDOUT, "OK\n");
 }
 //fwrite(STDERR, 'filter: url='.$url."\n");

 //fwrite(STDERR, 'filter: end of while...'."\n");
}   
  
/*
 *  $fp = fopen('php://stdin', 'r');
while($input = trim(fgets($fp, 4096))){
	$Source = trim($input);
    if($Source==null){continue;}
	$Source=trim($input);
	if($Source==null){continue;}
	WLOG($Source." str:".strlen($Source));
	print "OK\n";
	
	
}

*/
  
 $istanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0: die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
@fclose($GLOBALS["F"]);

function WLOG($text=null){
	
	$date=@date("Y-m-d H:i:s");
   	if (is_file("/var/log/squid/external-acl.log")) { 
   		$size=@filesize("/var/log/squid/external-acl.log");
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink("/var/log/squid/external-acl.log");
   			$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($GLOBALS["F"], "$date [{$GLOBALS["PID"]}]: $text\n");
}
?>
