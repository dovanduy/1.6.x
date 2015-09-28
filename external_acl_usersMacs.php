#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
  ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
  
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["SPLASH_DEBUG"]=false;
  $GLOBALS["SPLASH"]=false;
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["DEBUG_LEVEL"]=0;
  
  $max_execution_time=ini_get('max_execution_time'); 
  WLOG("Starting... Log level:{$GLOBALS["DEBUG_LEVEL"]};");
  
  
  
while (!feof(STDIN)) {
 	$url = trim(fgets(STDIN));
 	if($url==null){continue;}
 	$clt_conn_tag=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url);}
	$array=parseURL($url);
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("ASK: {$array["MAC"]} = ?");}
	$uid=GetMacToUid($array["MAC"],$array["IPADDR"]);
	$Group=GetGroup($array["MAC"],$array["IPADDR"]);
	if($uid<>null){
		if($Group<>null){
			$clt_conn_tag=" clt_conn_tag=$Group log=$Group,none";
		}
		fwrite(STDOUT, "OK user=$uid{$clt_conn_tag}\n");
		continue;
	}
	
	fwrite(STDOUT, "OK\n");
	
	
}


$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function parseURL($url){
	$uri=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: Analyze [$url]");}
	$md5=md5($url);
	
	if(isset($GLOBALS["CACHE_URI"][$md5])){return $GLOBALS["CACHE_URI"][$md5];}
	if(count($GLOBALS["CACHE_URI"])>1000){unset($GLOBALS["CACHE_URI"]);}
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-(.+?):([0-9]+)$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[3];
		return $GLOBALS["CACHE_URI"][$md5];		
	}
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=null;
		return $GLOBALS["CACHE_URI"][$md5];		
	}
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-\s+([a-z]+)-$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[3];
		return $GLOBALS["CACHE_URI"][$md5];
	}	
	
	
	
		
	if(preg_match("#(http|ftp|https|ftps):\/\/(.*)#i", $url,$re)){
		$uri=$re[1]."://".$re[2];
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("found uri $uri");}
		$url=trim(str_replace($uri, "", $url));
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Analyze $url");}
		
	}
	if($uri==null){
		if(preg_match("#([a-z0-9\.]+):([0-9]+)$#i", $url,$re)){
			$uri="http://".$re[1].":".$re[2];
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("found uri $uri");}
			$url=trim(str_replace($re[1].":".$re[2], "", $url));
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Analyze \"$url\"");}
		}
	}
	if($uri<>null){
		$URLAR=parse_url($uri);
		if(isset($URLAR["host"])){$rhost=$URLAR["host"];}
	}
	
	
	
	
	if(isset($GLOBALS["CACHE_URI"][$md5])){return $GLOBALS["CACHE_URI"][$md5];}
	$tr=explode(" ", $url);
	if($GLOBALS["DEBUG_LEVEL"]>1){
		while (list ($index, $line) = each ($tr)){
			WLOG("tr[$index] = $line");	
		}
	}
	
	
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
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("count --> 3");}
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
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("count --> 2");}
		//pas de login et pas de MAC;
		$login=null;	
		$ipaddr=$tr[0];
		$mac=null;
		$forwarded=$tr[1];
		if(isset($tr[2])){$uri=$tr[2];}	
		if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
		
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

function GetGroup($mac,$ipaddr){
	$uid=MacToGroup($mac);
	if($uid<>null){return $uid;}
	$uid=IpToGroup($ipaddr);
	return $uid;
	
}

function GetMacToUid($mac,$ipaddr){
	if($mac==null){return;}
	$filereload="/var/log/squid/reload/{$GLOBALS["PID"]}.MACTOUID";
	if(is_file("/var/log/squid/reload/{$GLOBALS["PID"]}.MACTOUID")){
		LoadDatabase();
		@unlink("/var/log/squid/reload/{$GLOBALS["PID"]}.MACTOUID");
	}
	
	
	if(isset($GLOBALS["GetMacToUidTIME"])){
		if(tool_time_min($GLOBALS["GetMacToUidTIME"])>10){unset($GLOBALS["USERSDB"]);}
	}
	
	if(!isset($GLOBALS["USERSDB"])){LoadDatabase();}
	$uid=MacToUid($mac);
	if($uid<>null){return $uid;}
	$uid=IpToUid($ipaddr);
	return $uid;
	
	
	
	
}

function LoadDatabase(){
	WLOG("Reloading database...");
	$GLOBALS["GetMacToUidTIME"]=time();
	$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));
}

function MacToUid($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);

}

function MacToGroup($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["GROUP"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["GROUP"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$mac]["GROUP"]);

}


function IpToUid($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);

}
function IpToGroup($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["GROUP"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["GROUP"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["GROUP"]);

}

function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}



function WLOG($text=null){
	if(!isset($GLOBALS["F"])){$GLOBALS["F"] = @fopen("/var/log/squid/MacToUid.log", 'a');}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
   	if (is_file("/var/log/squid/MacToUid.log")) { 
   		$size=@filesize("/var/log/squid/MacToUid.log");
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink("/var/log/squid/MacToUid.log");
   			$handle = @fopen("/var/log/squid/MacToUid.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($handle, "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
	@fclose($handle);
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
function GetComputerName($ip){return $ip;}

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
