#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;

//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

 if(preg_match("#--categories\s+([0-9]+)#", @implode(" ", $argv),$re)){
  	WLOG("Starting ACLs dynamic with Categories features Group {$re[1]}...");
  	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
  	$GLOBALS["CATZ-EXTRN"]=$re[1];
  }

$GLOBALS["MYPID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["MYPID"]}");
$GLOBALS["XVFERTSZ"]=XVFERTSZ();


WLOG("Artica categories : Starting Group id:{$GLOBALS["MYPID"]}");


$DCOUNT=0;
while (!feof(STDIN)) {
	$url = trim(fgets(STDIN));
	if($url==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] LOOP::URL `$url` is null [".__LINE__."]");}
		continue;
	}
	
	
	$DCOUNT++;
	
	if(!$GLOBALS["XVFERTSZ"]){
		$error=urlencode("License Error, please remove Artica categories objects in ACL");
		WLOG("LOOP():: License Error ! [".__LINE__."]");
		fwrite(STDOUT, "BH message=$error\n");
		continue;
	}	
	
	try {
		if($GLOBALS["DEBUG"]){WLOG("LOOP Send ->`$url`");}
		$categories_match=categories_match($GLOBALS["CATZ-EXTRN"],$url);
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("$DCOUNT] LOOP::FATAL ERROR $error");
		$result=false;
	}
	
	if($categories_match<>null){
		fwrite(STDOUT, "OK message=$categories_match\n");
		continue;
	}
	fwrite(STDOUT, "ERR\n");
	continue;

	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_categories.log";
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



function categories_match($gpid,$sitname){
	$sitname=trim($sitname);
	if(preg_match("#^www\.(.+)#", $sitname,$re)){$sitname=$re[1];}
	if(preg_match("#^(.+):[0-9]+]#", $sitname,$re)){$sitname=$re[1];}
	if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: $gpid `$sitname`");}
	
	$categories_get_memory=categories_get_memory($gpid,$sitname);
	
	if($categories_get_memory<>null){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> MEMORY: `$categories_get_memory` ");}
		if($categories_get_memory=="UNKNOWN"){
			if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: FROM MEMORY `$sitname` -> UNKNOWN");}
			return null;}
			if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: FROM MEMORY `$sitname` -> $categories_get_memory");}
		return $categories_get_memory;
	}

	$q=new mysql_catz();
	$categoriF=$q->GET_CATEGORIES($sitname);

	$trans=$q->TransArray();
	if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> RESULTS: `$categoriF` ");}

	if($categoriF==null){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> SET TO  `UNKNOWN` ");}
		categories_set_memory($gpid,$sitname,"UNKNOWN");
		return null;
	}

	if(strpos($categoriF, ",")>0){
		$categoriT=explode(",",$categoriF);
	}else{
		$categoriT[]=$categoriF;
	}

	while (list ($a, $b) = each ($categoriT)){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> category IS: [$b] [".__LINE__."]");}
		$MAIN[$b]=true;
	}

	$filename="/etc/squid3/acls/catz_gpid{$gpid}.acl";
	$categories=unserialize(@file_get_contents($filename));

	while (list ($category_table, $category_rule) = each ($categories)){
		$category_rule=urlencode($category_rule);
		$categoryname=$trans[$category_table];
		
		if(isset($MAIN[$categoryname])){
			if($GLOBALS["DEBUG"]){WLOG("FOUND `$categoryname` -> `$category_rule` ");}
			categories_set_memory($gpid,$sitname,$category_rule);
			return $category_rule;
		}else{
			if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> $categoryname = NO MATCH [".__LINE__."]");}
		}

	}

	categories_set_memory($gpid,$sitname,"UNKNOWN");

}
	
function categories_get_memory($gpid,$sitname){

	if(isset($GLOBALS["categories_memory"])){
		if($GLOBALS["DEBUG"]){WLOG("categories_get_memory: $gpid,$sitname ->". strlen($GLOBALS["categories_memory"])." bytes");}
		$data=unserialize($GLOBALS["categories_memory"]);
	}else{
		$data=array();
	}
	if(!isset($data[$sitname])){return null;}
	if(!isset($data[$sitname][$gpid])){return null;}
	if(count($data[$sitname])>64000){$GLOBALS["categories_memory"]=null;}
	if($GLOBALS["DEBUG"]){WLOG("MEMORY: Group: $gpid `$sitname` -> {$data[$sitname][$gpid]}");}
	return $data[$sitname][$gpid];
}	
function categories_set_memory($gpid,$sitname,$result){
	if(isset($GLOBALS["categories_memory"])){
		$data=unserialize($GLOBALS["categories_memory"]);
	}
	$data[$sitname][$gpid]=$result;
	$GLOBALS["categories_memory"]=serialize($data);
}


function XVFERTSZ(){
	$F=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw==");

	if(!is_file($F)){
		WLOG("License check no such license");
		return false;}
		$D=trim(@file_get_contents($F));
		if(trim($D)=="TRUE"){return true;}
		WLOG("License check no such license content");
		return false;

}
