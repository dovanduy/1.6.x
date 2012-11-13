#!/usr/bin/php -q
<?php
require_once(dirname(__FILE__).'/ressources/class.image.nudity.inc');
require_once(dirname(__FILE__).'/ressources/class.squid.external.acl.helper.inc');
$GLOBALS["DEBUG_LEVEL"]=0;
$GLOBALS["CACHE_URLS_COUNT"]=0;
$GLOBALS["PID"]=getmypid();
$GLOBALS["CACHE_FILE"]="/etc/squid3/SquidNudityScanCache.{$GLOBALS["PID"]}";
$SquidNuditScanParams=unserialize(base64_decode(@file_get_contents("/etc/squid3/SquidNudityScanParams")));
ReadCache();
@mkdir("/var/log/squid/nudity",0755,true);
$iPicScanVal = $SquidNuditScanParams['picscanval'];
$curlTimeOut=$SquidNuditScanParams["curlTimeOut"];
$MemoryDir=$SquidNuditScanParams["MemoryDir"];
$GLOBALS["CacheSizeItems"]=$SquidNuditScanParams["CacheSizeItems"];
if(!is_numeric($iPicScanRes)){$iPicScanRes=480000;}
if(!is_numeric($iPicScanVal)){$iPicScanVal=70;}
if(!is_numeric($curlTimeOut)){$curlTimeOut=10;}
if(!is_numeric($MemoryDir)){$MemoryDir=0;}
if(!is_numeric($GLOBALS["CacheSizeItems"])){$GLOBALS["CacheSizeItems"]=50000;}
$GLOBALS["CURL_TIMEOUT"]=$curlTimeOut;
$GLOBALS["MEM_DIR"]=$MemoryDir;
$iPicScanVal=intval($iPicScanVal);
if($iPicScanVal>99){$iPicScanVal=99;}
unset($SquidNuditScanParams);
$filter = new ImageFilter;
$GLOBALS["HELPER"]=new externhelper();
 

while (!feof(STDIN)) {
	 	$url = trim(fgets(STDIN));
	  	if($url==null){continue;}
	 	$array=$GLOBALS["HELPER"]->parseURL($url);
		if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG($url." str:".strlen($url)." LOGIN:{$array["LOGIN"]},IPADDR:{$array["IPADDR"]} MAC:{$array["MAC"]} HOST:{$array["HOST"]} URI:{$array["URI"]}");}
	    $szMac = $array["MAC"];
	    $szUrl = $array["URI"];
	    
	    $iScore=GetCache($szUrl);
	    if($iScore>0){
	    	 if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["HELPER"]->WLOG("Cached: {$iScore}% ".basename($szUrl));}
	    	if ($iScore > $iPicScanVal) {print("OK\n");continue;}else{print("ERR\n");continue;}
	    }
	    
	    
	    if(strlen($szUrl)<3){if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG("-> STOP no uri...");}print("ERR\n");continue; }
        
        
       
       $szFilename=GetImageFile($szUrl,$GLOBALS["HELPER"]);
       if($szFilename==null){
       		if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["HELPER"]->WLOG(basename($szUrl)." SKIP (no image)");}	
       		print("ERR\n");
       		continue;
       }
       
       $t=time();
       if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["HELPER"]->WLOG("GetScore of $szFilename");}
       $iScore =intval($filter->GetScore($szFilename));
       @unlink($szFilename);
       
       $t2=time();
       $seconds=$t2-$t;
	   if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["HELPER"]->WLOG(basename($szUrl).": $iScore% in ({$seconds}s)");}
	  
	   
	   
	   SetCache($szUrl,$iScore);
	   
	   if ($iScore >= $iPicScanVal) {
			$array["POURC"]=$iScore;
			$tfile="/var/log/squid/nudity/".md5(serialize($array));
			if(!is_file($tfile)){@file_put_contents($tfile, serialize($array));}
			if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG("BLOCK->".$szMac." ".$szUrl." score:".$iScore." ".$iPicScanVal." ".$iRes);}
			print("OK\n");
			continue;
		}
		
		print("ERR\n");

}  

SaveCache();
@unlink($GLOBALS["CACHE_FILE"]); 
$GLOBALS["HELPER"]->shutdown();
unset($filter );


function GetCache($szUrl){
	$md5=md5($szUrl);
	if(!isset($GLOBALS["CACHE_URLS"][$md5])){return 0;}
	return $GLOBALS["CACHE_URLS"][$md5];
	
}

function ReadCache(){
	foreach (glob("/etc/squid3/SquidNudityScanCache.*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		while (list ($a, $b) = each ($array) ){$GLOBALS["CACHE_URLS"][$a]=$b;}
	}
	if(!isset($GLOBALS["HELPER"])){$GLOBALS["HELPER"]=new externhelper();}
	$GLOBALS["HELPER"]->WLOG("SaveCache()::". count($GLOBALS["CACHE_URLS"])." cached elements");
	
}

function SetCache($szUrl,$results){
	if(!is_numeric($results)){$results=1;}
	if($results==0){$results=1;}
	$md5=md5($szUrl);
	$GLOBALS["CACHE_URLS"][$md5]=intval($results);
	
	if(count($GLOBALS["CACHE_URLS"])>$GLOBALS["CacheSizeItems"]){
		unset($GLOBALS["CACHE_URLS"]);
		@file_put_contents($GLOBALS["CACHE_FILE"], serialize($GLOBALS["CACHE_URLS"]));
	}
	
	$GLOBALS["CACHE_URLS_COUNT"]++;
	if($GLOBALS["CACHE_URLS_COUNT"]>500){
		SaveCache();
		ReadCache();
		$GLOBALS["CACHE_URLS_COUNT"]=0;
	}
}

function SaveCache(){
	ReadCache();
	$GLOBALS["HELPER"]->WLOG("SaveCache():: ".count($GLOBALS["CACHE_URLS"])." element(s)");
	@file_put_contents("/etc/squid3/SquidNudityScanCache.000", serialize($GLOBALS["CACHE_URLS"]));
}

 
function GetImageFile($szUrl){
	$allowedEXTS["gif"]=true;
	$allowedEXTS["png"]=true;
	$allowedEXTS["jpeg"]=true;
	$allowedEXTS["jpg"]=true;
	$allowedEXTS["bmp"]=true;
	$PicScannerPrefix=time();
	$szUrlTemp=$szUrl;
	$szUrlExp=explode("?", $szUrlTemp);
	
	if(count($szUrlExp)>0){
	 $szUrlTemp=$szUrlExp[0];
	}
	$file_extension=@pathinfo($szUrlTemp, PATHINFO_EXTENSION);
	
 	$GLOBALS["HELPER"]->WLOG("OK GetImageFile():: EXT::`$file_extension` $szUrlTemp");
	
	if(!isset($allowedEXTS["$file_extension"])){return null;}
	$workdir="/tmp";
	if($GLOBALS["MEM_DIR"]>0){$workdir="/var/lib/nudityScan";}
	$BaseName=basename($szUrl);
				
    $tmpfname = tempnam($workdir, $PicScannerPrefix.".{$GLOBALS["PID"]}.");
	$fw = fopen($tmpfname, "w");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $szUrl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FILE, $fw);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
     if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG("GetImageFile()::$BaseName: Downloading...");}
    $t1=time();
    $data = curl_exec($ch);
	$errorNumber=curl_errno($ch);
	$errorText=curl_error($ch);
	$t2=time();
	$timeD=$t2-$t1;
	 if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG("GetImageFile():: Done ( $timeD seconds) Er.$errorNumber $errorText...");}
	if($errorNumber>0){
			$GLOBALS["HELPER"]->WLOG("GetImageFile():: ". basename($szUrl). " error:$error");
			@unlink($tmpfname);
	}                
    curl_close($ch);
    fclose($fw);
    if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["HELPER"]->WLOG("OK GetImageFile():: $szUrl -> $tmpfname");}
    return $tmpfname;
}


?>
