#!/usr/bin/php -q
<?php
  //ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(0);
  $GLOBALS["SplashScreenURI"]=null;
  $GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  set_time_limit(0);
  $max_execution_time=ini_get('max_execution_time'); 
  if(is_file("/etc/artica-postfix/settings/Daemons/SplashScreenURI")){$GLOBALS["SplashScreenURI"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SplashScreenURI");}
 
  
  WLOG("starting... max_execution_time:$max_execution_time argv[1]={$argv[1]}");
  if($argv[1]=="--mactouid"){$GLOBALS["MACTUIDONLY"]=true;}
  if($argv[1]=="--splash"){$GLOBALS["SPLASH"]=true;}
  
  
 

while (!feof(STDIN)) {
 $url = trim(fgets(STDIN));
 if($url<>null){
 	$array=parseURL($url);
 	$SplashScreenURI=$GLOBALS["SplashScreenURI"];
 	WLOG($url." str:".strlen($url)." LOGIN:{$array["LOGIN"]},IPADDR:{$array["IPADDR"]} MAC:{$array["MAC"]} HOST:{$array["HOST"]} URI:{$array["URI"]}");
 	
 	if($GLOBALS["MACTUIDONLY"]){
 		WLOG("ASK: {$array["MAC"]} = ?");
 		$uid=GetMacToUid($array["MAC"]);
 		if($uid<>null){fwrite(STDOUT, "OK user=$uid\n");continue;}
 		fwrite(STDOUT, "OK\n");
 		continue;
 	}
 	
 	if($GLOBALS["SPLASH"]){
 		
 		if($array["RHOST"]==uriToHost($SplashScreenURI)){
 			WLOG("ASK: Slpash = {$array["URI"]} {$array["RHOST"]} OK");
 			fwrite(STDOUT, "OK\n");
 			continue;
 		}
 		
 		
		
 		WLOG("ASK: Slpash = {$array["URI"]} {$array["IPADDR"]} MAC:{$array["MAC"]} ?");
 		$uid=SplasHCheckAuth($array);
 		fwrite(STDOUT, "ERR message=\"". base64_encode(serialize($array))."\"\n");
 		continue;
 	}
 	
  	if(CheckQuota($array)){fwrite(STDOUT, "OK\n");}else{WLOG("ERR \"Out of quota\"");fwrite(STDOUT, "ERR message=\"Out Of Quota\"\n");}
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

function parseURL($url){
	$uri=null;
	WLOG("Analyze $url");
	$md5=md5($url);
	if(preg_match("#(http|ftp|https|ftps):\/\/(.*)#i", $url,$re)){
		$uri=$re[1]."://".$re[2];
		WLOG("found uri $uri");
		$url=trim(str_replace($uri, "", $url));
		WLOG("Analyze $url");
	}
	if($uri==null){
		if(preg_match("#([a-z0-9\.]+):([0-9]+)$#i", $url,$re)){
			$uri="http://".$re[1].":".$re[2];
			WLOG("found uri $uri");
			$url=trim(str_replace($re[1].":".$re[2], "", $url));
			WLOG("Analyze \"$url\"");
		}
	}
	if($uri<>null){
		$URLAR=parse_url($uri);
		if(isset($URLAR["host"])){$rhost=$URLAR["host"];}
	}
	
	
	
	
	if(isset($GLOBALS["CACHE_URI"][$md5])){return $GLOBALS["CACHE_URI"][$md5];}
	$tr=explode(" ", $url);
	while (list ($index, $line) = each ($tr)){WLOG("tr[$index] = $line");	}
	
	
	//max auth=4
	if(count($tr)==4){
		WLOG("count --> 4");
		$login=$tr[0];
		$ipaddr=$tr[1];
		$mac=$tr[2];
		$forwarded=$tr[3];
		if(isset($tr[4])){$uri=$tr[4];}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $forwarded)){$ipaddr=$forwarded;}
		if($mac==null){$mac=GetMacFromIP($ipaddr);}
		
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
		return $GLOBALS["CACHE_URI"][$md5];
	}
	
	
	
	if(count($tr)==3){
		WLOG("count --> 3");
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $tr[0])){
			//ip en premier donc mac=ok, pas de login
			$login=null;	
			$ipaddr=$tr[0];
			$mac=$tr[1];
			$forwarded=$tr[2];
			if(isset($tr[3])){$uri=$tr[3];}	
		}else{
			//login en premier donc mac=bad
			$login=$tr[0];
			$ipaddr=$tr[1];
			
			$forwarded=$tr[2];
			if(isset($tr[3])){$uri=$tr[3];}	
		}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
		if($mac==null){$mac=GetMacFromIP($ipaddr);}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;		
		return $GLOBALS["CACHE_URI"][$md5];		
		
	}
	
	
	
	if(count($tr)==2){
		WLOG("count --> 2");
		//pas de login et pas de MAC;
		$login=null;	
		$ipaddr=$tr[0];
		$mac=null;
		$forwarded=$tr[1];
		if(isset($tr[2])){$uri=$tr[2];}	
		if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
		
	}
	if($mac==null){$mac=GetMacFromIP($ipaddr);}
	else{		
		if($mac=="00:00:00:00:00:00"){$mac=null;$mac=GetMacFromIP($ipaddr);}
	}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
	$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
	$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
	$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
	$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
	$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
	return $GLOBALS["CACHE_URI"][$md5];
	
	
}

function GetMacToUid($mac){
	if($mac==null){return;}
	if(isset($GLOBALS["GetMacToUidMD5"])){
			$md5file=md5_file("/etc/squid3/MacToUid.ini");
			if($md5file<>$GLOBALS["GetMacToUidMD5"]){
				unset($GLOBALS["GetMacToUid"]);
			}
	}
	if(isset($GLOBALS["GetMacToUid"])){
		WLOG("MEM: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");
		if(isset($GLOBALS["GetMacToUid"][$mac])){
				return $GLOBALS["GetMacToUid"][$mac];
			}
		return;
	}
	
	$GLOBALS["GetMacToUid"]=unserialize(@file_get_contents("/etc/squid3/MacToUid.ini"));
	$GLOBALS["GetMacToUidMD5"]=md5_file("/etc/squid3/MacToUid.ini");
	WLOG("DISK: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");
	if(isset($GLOBALS["GetMacToUid"][$mac])){return $GLOBALS["GetMacToUid"][$mac];}
}




function CheckQuota($CPINFOS){
	$RULES=unserialize(@file_get_contents("/etc/squid3/squid.durations.ini"));
	if(!is_array($RULES)){return true;}
	if(count($RULES)==0){return true;}
	
	while (list ($duration, $array_duration) = each ($RULES)){
		while (list ($xtype, $array_type) = each ($array_duration)){
			while (list ($pattern, $quotaBytes) = each ($array_type)){
				WLOG("Check rule for duration:$duration type:$xtype ($pattern) $quotaBytes bytes");
				
				if($duration==1){
					if(CheckQuota_day($CPINFOS,$xtype,$pattern,$quotaBytes)){return false;}
					continue;
				}
				if($duration==2){
					if(CheckQuota_hour($CPINFOS,$xtype,$pattern,$quotaBytes)){return false;}
					continue;
				}
			}
		}
		
	}

return true;
	
	
}
function CheckQuota_day($infos,$xtype,$pattern,$quotaBytes){
	$IPADDR=$infos["IPADDR"];
	$MAC=$infos["MAC"];
	$HOST=$infos["HOST"];
	$LOGIN=$infos["LOGIN"];
	
	$array=unserialize(@file_get_contents("/etc/squid3/squid.quotasD.ini"));
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);	
	
	if($xtype=="ipaddr"){
		if($IPADDR==null){WLOG("$IPADDR is null");return false;}
		if(!preg_match("#$pattern#i", $IPADDR)){WLOG("$IPADDR did nor match rule $pattern");return false;}
		if(count($array["ipaddr"])==0){WLOG("ipaddr: not an array...");return false;}
		if(!isset($array["ipaddr"][$IPADDR])){WLOG("ipaddr[$IPADDR]: !isset");return false;}
		$CurrentQuota=$array["ipaddr"][$IPADDR];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}

	if($xtype=="uid"){
		if($LOGIN==null){WLOG("LOGIN is null");return false;}
		if(!preg_match("#$pattern#i", $LOGIN)){WLOG("$LOGIN did nor match rule $pattern");return false;}
		if(count($array["uid"])==0){WLOG("uid: not an array...");return false;}
		if(!isset($array["uid"][$LOGIN])){WLOG("uid[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["uid"][$LOGIN];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="hostname"){
		if($HOST==null){WLOG("HOST is null");return false;}
		if(!preg_match("#$pattern#i", $HOST)){WLOG("$HOST did nor match rule $pattern");return false;}
		if(count($array["hostname"])==0){WLOG("hostname: not an array...");return false;}
		if(!isset($array["hostname"][$HOST])){WLOG("hostname[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["hostname"][$HOST];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="MAC"){
		if($MAC==null){WLOG("MAC is null");return false;}
		if(!preg_match("#$pattern#i", $MAC)){WLOG("$MAC did nor match rule $pattern");return false;}
		if(count($array["MAC"])==0){WLOG("MAC: not an array...");return false;}
		if(!isset($array["MAC"][$MAC])){WLOG("MAC[$MAC]: !isset");return false;}
		$CurrentQuota=$array["MAC"][$MAC];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}		
	
	
}

function CheckQuota_hour($infos,$xtype,$pattern,$quotaBytes){
	
	$IPADDR=$infos["IPADDR"];
	$MAC=$infos["MAC"];
	$HOST=$infos["HOST"];
	$LOGIN=$infos["LOGIN"];
	
	
	$array=unserialize(@file_get_contents("/etc/squid3/squid.quotasH.ini"));
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);

	if($xtype=="ipaddr"){
		if($IPADDR==null){WLOG("IPADDR is null");return false;}
		if(!preg_match("#$pattern#i", $IPADDR)){WLOG("$IPADDR did nor match rule $pattern");return false;}
		if(count($array["ipaddr"])==0){WLOG("ipaddr: not an array...");return false;}
		if(!isset($array["ipaddr"][$IPADDR])){WLOG("ipaddr[$IPADDR]: !isset");return false;}
		$CurrentQuota=$array["ipaddr"][$IPADDR];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}

	if($xtype=="uid"){
		if($LOGIN==null){WLOG("LOGIN is null");return false;}
		if(!preg_match("#$pattern#i", $LOGIN)){WLOG("$LOGIN did nor match rule $pattern");return false;}
		if(count($array["uid"])==0){WLOG("uid: not an array...");return false;}
		if(!isset($array["uid"][$LOGIN])){WLOG("uid[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["uid"][$LOGIN];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="hostname"){
		if($HOST==null){WLOG("HOST is null");return false;}
		if(!preg_match("#$pattern#i", $HOST)){WLOG("$HOST did nor match rule $pattern");return false;}
		if(count($array["hostname"])==0){WLOG("hostname: not an array...");return false;}
		if(!isset($array["hostname"][$HOST])){WLOG("hostname[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["hostname"][$HOST];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="MAC"){
		if($MAC==null){WLOG("MAC is null");return false;}
		if(!preg_match("#$pattern#i", $MAC)){WLOG("$MAC did nor match rule $pattern");return false;}
		if(count($array["MAC"])==0){WLOG("MAC: not an array...");return false;}
		if(!isset($array["MAC"][$MAC])){WLOG("MAC[$MAC]: !isset");return false;}
		$CurrentQuota=$array["MAC"][$MAC];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	

	
	
}

function SplasHCheckAuth($array){
	$ch = curl_init();
	
	$params="?checks=".base64_encode(serialize($array));
	curl_setopt($ch, CURLOPT_INTERFACE,"127.0.0.1");
	curl_setopt($ch, CURLOPT_URL, $GLOBALS["SplashScreenURI"].$params);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
	$data=curl_exec($ch);
	$errno=curl_errno($ch);
	curl_close($ch);
}

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

function uriToHost($uri){
	if(count($GLOBALS["uriToHost"])>20000){$GLOBALS["uriToHost"]=array();}
	if(isset($GLOBALS["uriToHost"][$uri])){return $GLOBALS["uriToHost"][$uri];}
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	if(preg_match("#^www\.(.*?)#", $sitename,$re)){$sitename=$re[1];}
	if(preg_match("#(.*?):[0-9]+#", $sitename)){$sitename=$re[1];}
	$GLOBALS["uriToHost"][$uri]=$sitename;
	return $sitename;
	
}
function GetComputerName($ip){
		$time=time("Ymh");
		if(count($GLOBALS["resvip"])>5){unset($GLOBALS["resvip"]);}
		if(isset($GLOBALS["resvip"][$time][$ip])){return $GLOBALS["resvip"][$time][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$time]=$name;
		return $name;
		}
function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		if(!isset($GLOBALS["SBIN_ARP"])){$GLOBALS["SBIN_ARP"]=find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$GLOBALS["SBIN_ARPING"]=find_program("arping");}
		
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
		}
		
		
		$results=array();
			
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$GLOBALS["SBIN_PING"]=find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$GLOBALS["SBIN_NOHUP"]=find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		$this->events($cmd);
		exec($cmd,$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
function find_program($strProgram) {
	  $key=md5($strProgram);
	  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
	  $value=trim(internal_find_program($strProgram));
	  $GLOBALS["find_program"][$key]=$value;
      return $value;
}
function internal_find_program($strProgram){
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', 
	  '/usr/local/sbin',
	  '/usr/kerberos/bin',
	  
	  );
	  
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {
	      $strProgrammpath = $strPath . "/" . $strProgram;
	      if (is_executable($strProgrammpath)) {
	      	  return $strProgrammpath;
	      }
	    }
	  } else {
	   	return strpos($strProgram, '.exe');
	  }
	}	
?>
