#!/usr/bin/php
<?php
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");
include_once(dirname(__FILE__)."/ressources/class.HyperCache.inc");

//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["VERBOSE"]=false;
$GLOBALS["VIDEOCACHE_DEBUG"]=false;
$GLOBALS["HyperCacheDebug"]=true;
$GLOBALS["DebugLoop"]=true;
$GLOBALS["GOOGLE_SAFE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["SquidGuardIPWeb"]=null;
$GLOBALS["CACHE"]=array();
$GLOBALS["AS_34"]=false;
$GLOBALS["UFDBVERS"]="1.1.1";
$cmdline=@implode(" ", $argv);

if(preg_match("#--black=(.+)#", $cmdline,$re)){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["OUTPUT"]=true;
	BlacklistedBase($re[1]);
	die();
}

$GLOBALS["SQUID_VERSION"]=@file_get_contents("/var/log/squid/ufdbgclient.version");
@mkdir("/var/log/squid/ufdbguard-blocks",0755,true);
$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
if(is_array($SquidGuardIPWeb)){
	$GLOBALS["SquidGuardIPWeb"]=$SquidGuardIPWeb["SquidGuardIPWeb"];
}

if(preg_match("#-S\s+([0-9\.]+)\s+-p\s+([0-9]+)#", $cmdline,$re)){$GLOBALS["UFDB_SERVER"]=$re[1]; $GLOBALS["UFDB_PORT"]=$re[2]; }
if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]="3977";}
ufdbconfig();

events("Web filtering service on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} Squid version = {$GLOBALS["SQUID_VERSION"]}");
events("Web filtering Page error on {$GLOBALS["SquidGuardIPWeb"]}");
events("VideoCache enabled:{$GLOBALS["EnableStreamCache"]}");
events("HyperCache enabled:{$GLOBALS["SquidEnforceRules"]} {$GLOBALS["HyperCacheMemEntries"]} Max entries");
events("HyperCache: redirect to {$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}");
events("Google Safe Browsing enabled:{$GLOBALS["EnableGoogleSafeBrowsing"]}");




$temp = array();
stream_set_timeout(STDIN, 86400);

$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin
		1 => array("pipe", "w"),  // stdout
		2 => array("pipe", "w") );

if($GLOBALS["EnableStreamCache"]==1){
	
	$GLOBALS["PROCESS_VIDEOCACHE"] = proc_open("/usr/share/videocache/videocache.py", $descriptorspec, $GLOBALS["pipes_videocache"]);
	if (!is_resource($GLOBALS["PROCESS_VIDEOCACHE"])) {
		events("proc_open /usr/share/videocache/videocache.py failed");
		$GLOBALS["EnableStreamCache"]=0;
	}else{
		events("proc_open /usr/share/videocache/videocache.py Success");
	}
}

if(preg_match("#^3\.4#", $GLOBALS["SQUID_VERSION"])){$AS_34=true;}
if(preg_match("#^3\.5#", $GLOBALS["SQUID_VERSION"])){$AS_34=true;}
$GLOBALS["AS_34"]=$AS_34;

while (!feof(STDIN)) {
	$XzLine = fgets(STDIN);
	$szLine=trim($XzLine);
	if (empty($szLine)) {continue;}
	$CHANNEL=null;
	//if($GLOBALS["VERBOSE"]){events("Receive $szLine");}
	$results=false;

	
	$array=explode(" ", $szLine);
	
	if(is_numeric($array[0])){
		$CHANNEL=$array[0];
		$URI=$array[1];
		$IP=$array[2];
		$userid=$array[3];
		$PROTO=$array[4];
		$myIP=$array[5];
		$myPort=$array[6];
	}else{
		$URI=$array[0];
		$IP=$array[1];
		$userid=$array[2];
		$PROTO=$array[3];
		$myIP=$array[4];
		$myPort=$array[5];
	}
	if(preg_match("#^(.+?)\/#", $IP,$re)){$IPS=$re[1];}
	$H=parse_url($URI);
	$DEBUGHOSTNAME=$H["host"];
	$GLOBALS["LOG_DOM"]=$H["host"];
	$GLOBALS["LOG_AR"]["URI"]=$URI;
	$GLOBALS["LOG_AR"]["IP"]=$IP;
	$GLOBALS["LOG_AR"]["userid"]=$userid;
	$GLOBALS["LOG_AR"]["PROTO"]=$PROTO;
	$GLOBALS["LOG_AR"]["myIP"]=$myIP;
	$GLOBALS["LOG_AR"]["myPort"]=$myPort;
	$GLOBALS["LOG_AR"]["host"]=$GLOBALS["LOG_DOM"];
	
	//if($GLOBALS["VERBOSE"]){while (list ($index, $b) = each ($array) ){events("[$index]: $b");}}
	$ToUfdb="$URI $IP $userid $PROTO $myIP $myPort\n";
	$ToVideoCache="$URI $IPS/- - $PROTO $myIP $myPort\n";
	
	
	if($GLOBALS["EnableUfdbGuard"]==1){
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME: WhitelistedBase ? ".__LINE__);}
		if(WhitelistedBase($URI)){
			if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME: StreamCache ? ".__LINE__);}
			if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "StreamCache($IP..) ".__LINE__);}
			if(StreamCache($PROTO,$ToVideoCache,$URI,$IP,$userid)){continue;}
			if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "HyperCacheRules ?".__LINE__);}
			if(HyperCacheRules($PROTO,$ToVideoCache,$URI,$IP,$userid,$DEBUGHOSTNAME)){continue;}
			if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME: $ OUPTUT NULL $ ".__LINE__);}
			Output_results(null);
			continue;
		}
		
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is Blacklisted ?".__LINE__);}
		if(BlacklistedBase($URI,$IP,$userid)){continue;}
		
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is Unlocked ?".__LINE__);}
		if(Unlocked($URI,$IP,$userid)){
			if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "StreamCache($IP..) ".__LINE__);}
			if(StreamCache($PROTO,$ToVideoCache,$URI,$IP,$userid)){continue;}
			continue;
		}
		if($GLOBALS["DebugLoop"]){events( "To ufdb ?".__LINE__);}
		if(UfdbBlackList($ToUfdb)){continue;}
	}
	
	if($GLOBALS["DebugLoop"]){events( "GoogleSafeBrowsing ?".__LINE__);}
	if(GoogleSafeBrowsing($URI,$PROTO,$H["host"],$IP,$userid)){continue;}
	
	
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "StreamCache($IP..) L.".__LINE__);}
	if($GLOBALS["DebugLoop"]){events( "StreamCache ?".__LINE__);}
	if(StreamCache($PROTO,$ToVideoCache,$URI,$IP,$userid)){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "StreamCache($IP..) -> CONTINUE".__LINE__);}
		continue;
	}
	if($GLOBALS["DebugLoop"]){events( "HyperCacheRules ?".__LINE__);}
	if(HyperCacheRules($PROTO,$ToVideoCache,$URI,$IP,$userid,$DEBUGHOSTNAME)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules: SEND");}
		continue;
	}
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules: DONE");}	
	
	
	
	$results=trim($results);
	if(trim($results)==null){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events( "OUPTUT NULL ".__LINE__);}
		if($GLOBALS["DebugLoop"]){events( "NOTHING -> NULL".__LINE__);}
		Output_results(null);
		continue;
	}
	
	if($GLOBALS["DebugLoop"]){events( "END LOOP".__LINE__);}
	

}

events("Stopping Webfiltering client.");
if(isset($GLOBALS["PROCESS_VIDEOCACHE"])){
	if (is_resource($GLOBALS["PROCESS_VIDEOCACHE"])) {
		events("Stopping VideoCache clients.");
		fclose($GLOBALS["pipes_videocache"][0]);
		fclose($GLOBALS["pipes_videocache"][1]);
		fclose($GLOBALS["pipes_videocache"][2]);
	}
}
events("Die Webfiltering client.");
die();


function HyperCacheRulesBlacklist($domain){
	if(preg_match("#(^|\.)(dropbox|symcd|xiti|etracker)\.com#", $domain)){return true;}
	if(preg_match("#(^|\.)doubleclick\.net#", $domain)){return true;}
	
	
}

function HyperCacheRules($PROTO,$ToUfdb,$URI,$IP,$userid,$DEBUGHOSTNAME){
	
	
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$IP=$re[1];
		$USER=$re[2];
	}
	if($IP=="127.0.0.1"){return;}
	
	
	
	
	if($PROTO<>"GET"){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:$PROTO/$IP <> GET ABORT");}
		return false;
	}
	if($GLOBALS["SquidEnforceRules"]==0){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:$IP Not enabled <> GET ABORT");}
		return;
	}
	
	if(HyperCacheRulesBlacklist($DEBUGHOSTNAME)){return;}
	
	$URI=HyperCacheCleanUri($URI);
	
	if($GLOBALS["HyperCacheOK"][md5($URI)]){
		Output_results($GLOBALS["HyperCacheOK"][md5($URI)]);
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:`$URI` -> REDIRECT MEM");}
		return true;
		
	}
	
	$HyperCache=new HyperCache();
	if($HyperCache->HyperCacheRulesMatchPattern($GLOBALS["HyperCacheListenAddr"], $URI)){return false;}
	
	if(HyperCacheRuleMirror($URI)){return true;}
	if(HyperCacheWhiteUri($URI)){return false;}
	
	
	$ID=$HyperCache->HyperCacheRulesMatches($URI);
	if($ID==0){return false;}
	
	if(HyperCacheRulesSave($URI,$ID)){return true;}
	
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:`$URI` no match");}
}

function HyperCacheRulesMatches($URI){
	$MAIN=$GLOBALS["HyperCacheRules"];
	if(count($MAIN)==0){return 0;}
	
	
	while (list ($ID, $ligne) = each ($MAIN) ){
		$sitename=$ligne["sitename"];
		if(preg_match("#regex:(.+)#", $sitename,$re)){ 
			$sitename=$re[1]; 
			if(preg_match("#$sitename#i", $URI)){return $ID;}
			continue;
		}
		
		$sitename=tool_string_to_regex($sitename);
		if(preg_match("#(^|\.)$sitename$#",$URI)){return $ID;}
			
	}
	
	return 0;
}


function HyperCacheRuleMirror($uri){
	
	if(isset($GLOBALS["HyperCacheOK"][md5($uri)])){
		Output_results($GLOBALS["HyperCacheOK"][md5($uri)]);
		return true;
	}
	
	$RULES=$GLOBALS["HyperCacheRulesMirror"];
	
	$HyperCache=new HyperCache();
	while (list ($sitename, $ligne) = each ($RULES) ){
		$sitename=$HyperCache->HyperCacheUriToHostname($sitename);
		if($HyperCache->HyperCacheRulesMatchPattern($sitename, $uri)){
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $uri, matches $sitename");}
			$H=parse_url($uri);
			$path=$H["path"];
			$query=$H["query"];
			if($query<>null){$query="?$query";}
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $sitename");}
			$link="http://{$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}/$sitename$path$query";
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $link");}
			$GLOBALS["HyperCacheOK"][md5($uri)]=$link;
			Output_results($link);
			return true;
			
		}
		
		
	}
	
}


function tool_string_to_regex($pattern){
	if(trim($pattern)==null){return null;}
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace(".", "\.", $pattern);
	//$pattern=str_replace("-", "\-", $pattern);
	$pattern=str_replace("[", "\[", $pattern);
	$pattern=str_replace("]", "\]", $pattern);
	$pattern=str_replace("(", "\(", $pattern);
	$pattern=str_replace(")", "\)", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("?", "\?", $pattern);
	$pattern=str_replace("#", "\#", $pattern);
	$pattern=str_replace("{", "\{", $pattern);
	$pattern=str_replace("}", "\}", $pattern);
	$pattern=str_replace("^", "\^", $pattern);
	$pattern=str_replace("!", "\!", $pattern);
	$pattern=str_replace("+", "\+", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	$pattern=str_replace("|", "\|", $pattern);
	return $pattern;

}


function HyperCacheRulesIsStored($uri){
	
	$familysite=tool_get_familysite($uri);
	$dbfile="/usr/share/squid3/HyperCache-$familysite-Retranslation.db";
	if(!is_file($dbfile)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $dbfile no such file");}
		return false;
	}
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){
		events("HyperCacheRulesIsStored:: FATAL!!!::$dbfile, unable to open"); 
		return false; 
	}
	$md5=md5($uri);
	
	if(!@dba_exists($md5,$db_con)){
		@dba_close($db_con);
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored:`$uri` NONE");}
		return false;
	}
		
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored:`$uri` Exists");}
	
	$fetch_content=@dba_fetch($md5,$db_con);$array=@unserialize($fetch_content);
	@dba_close($db_con);
	
	
	
	
	$MD5File=$array["MD5FILE"];
	$FileType=$array["MD5TYPE"]=$FileType;
	$extention=$array["EXT"];
	$TargetFile=$array["TARGET"];
	$basename=basename($TargetFile);
	if($extention==null){$extention="html";}
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $basename / $MD5File.$extention / content type:$FileType");}
	$link="http://{$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}/$MD5File.$extention";
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $link");}
	$GLOBALS["HyperCacheOK"][md5($uri)]=$link;
	Output_results($link);
	return true;
}

function EnforceRules_extension($filename){
	$parts = explode('.',strtolower($filename));
	$last = count($parts) - 1;
	$ext = $parts[$last];
	return $ext;
}


function ifTracker($uri){
	
	$H=parse_url($uri);
	$host=$H["host"];
	
	
	
	if(preg_match("#(^|\.)(graph\.facebook|unica|google-analytics|coremetrics|googlesyndication|chango|pinterest)\.com$#", $host)){
		$path=$H["path"];
		$scheme=$H["scheme"];
		return "$scheme://$host$path";
		
	}
	
	
	
	if(preg_match("#(^|\.)go2cloud\.org$#", $host)){
		$path=$H["path"];
		$scheme=$H["scheme"];
		return "$scheme://$host$path";
		
	}
	
	if(preg_match("#(^|\.)(doubleclick|owneriq)\.net$#", $host)){
		$path=$H["path"];
		$scheme=$H["scheme"];
		return "$scheme://$host$path";
	
	}	
	
	
	if(preg_match("#^ads\.yahoo\.com$#", $host)){
		$path=$H["path"];
		$scheme=$H["scheme"];
		return "$scheme://$host$path";
	
	}	
	
	return $uri;
	
}

function HyperCacheWhiteUri($URI){
	
	if(count($GLOBALS["HyperCacheRulesWhiteList"])==0){return false;}
	$HyperCache=new HyperCache();
	$HyperCacheRulesWhiteList=$GLOBALS["HyperCacheRulesWhiteList"];
	
	
	while (list ($pattern, $line) = each ($HyperCacheRulesWhiteList)){
		if($HyperCache->HyperCacheRulesMatchPattern($pattern,$URI)){return true;}
		
	}
}

function HyperCacheCleanUri($uri){
	
	if(preg_match("#\&rand=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("&rand={$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#\?__t=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("?__t={$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#[a-z0-9]+\.js\?(.+)#",$uri,$re)){
		$uri=str_replace("?{$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#\?dojo\.preventCache=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("?dojo.preventCache={$re[1]}", "", $uri);
		return $uri;
	}
	

	
	$uri=ifTracker($uri);
	
	return $uri;
}

function HyperCacheRules_get($URI,$ID){
	$uri_md5=md5($URI);
	$familysite=tool_get_familysite($URI);
	
	if(isset($GLOBALS["HyperCacheRulesSave"]["SAVED"][$uri_md5])){return true;}
	$dbfile="/usr/share/squid3/HyperCacheQueue-$familysite-$ID.db";
	
	if(!is_file($dbfile)){return false;}
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("HyperCacheRulesSave:: FATAL!!!::{$dbfile}, unable to open");return false; }
	if(@dba_exists($uri,$db_con)){
		@dba_close($db_con);
		$GLOBALS["HyperCacheRulesSave"]["SAVED"][$uri_md5]=true;
		return true;
	}
}

function tool_create_berekley($dbfile){
	if(is_file($dbfile)){return true;}
	try {
		events("tool_create_berekley:: Creating $dbfile database");
		$db_desttmp = @dba_open($dbfile, "c","db4");
		if(!$db_desttmp){ events("tool_create_berekley::FATAL Error on $dbfile");}
	}
	catch (Exception $e) {$error=$e->getMessage(); events("tool_create_berekley::FATAL ERROR $error on $dbfile");}
	@dba_close($db_desttmp);
	if(is_file($dbfile)){return true;}
	return false;
}

function HyperCacheRules_set($URI,$ID){
	$uri_md5=md5($URI);
	$familysite=tool_get_familysite($URI);
	$HyperCacheBuffer=$GLOBALS["HyperCacheBuffer"];
	if(!isset($GLOBALS["HYPER_CACHE_BUFFER_COUNT"])){$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;}
	
	$dbfile="/usr/share/squid3/HyperCacheQueue-$familysite-$ID.db";
	$GLOBALS["HYPER_CACHE_BUFFER"][$dbfile][]=$URI;
	$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]++;
	
	if($GLOBALS["HYPER_CACHE_BUFFER_COUNT"]<$HyperCacheBuffer){return true;}
	
	events("HyperCacheRules_set:: Clean buffer with {$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]} elements...");
		
	while (list ($dbfile, $array) = each ($GLOBALS["HYPER_CACHE_BUFFER"]) ){
		if(!tool_create_berekley($dbfile)){return;}
		$db_con = @dba_open($dbfile, "c","db4");
		if(!$db_con){events("HyperCacheRules_set:: FATAL!!!::{$dbfile}, unable to open");return false; }
		while (list ($index, $url) = each ($array) ){
			events("HyperCacheRules_set:: Clean buffer $dbfile -> $url");
			if(!@dba_replace($url,"NONE",$db_con)){events("HyperCacheRules_set:: FAILED SAVING *** $URI ***"); @dba_close($db_con); return false; }
		}
		$GLOBALS["HYPER_CACHE_BUFFER"][$dbfile]=array();
		@dba_close($db_con);
	}
	$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;
	$GLOBALS["HYPER_CACHE_BUFFER"]=array();
	return true;

	
}

function tool_get_familysite($uri){
	$parse_url=parse_url($uri);
	$sitename=$parse_url["host"];
	if(isset($GLOBALS["FAMILYSITES"][$sitename])){return $GLOBALS["FAMILYSITES"][$sitename];}
	$f=new familysite();
	$GLOBALS["FAMILYSITES"][$sitename]=$f->GetFamilySites($sitename);
	return $GLOBALS["FAMILYSITES"][$sitename];
	
}


function HyperCacheRulesSave($uri,$ID){
	$uri_md5=md5($uri);
	
	if(HyperCacheRulesIsStored($uri)){return true;}
	if(HyperCacheRules_get($uri,$ID)){return;}
	HyperCacheRules_set($uri,$ID);
	
	
	
	
}


function StreamCache($PROTO,$ToUfdb,$URI,$IP,$userid){
	$AS_34=$GLOBALS["AS_34"];
	if($GLOBALS["EnableStreamCache"]==0){return false;}
	if($PROTO<>"GET"){return false;}
	
	
	if($GLOBALS["VIDEOCACHE_DEBUG"]){ events("ask_to_videocache..");}
	$results=ask_to_videocache($ToUfdb);
	$length=strlen(trim($results));
	if($length<10){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){ events("**** VIDEO CACHE !NOT! DETECTED ****\n");}
		return false;}
	
	if($GLOBALS["VIDEOCACHE_DEBUG"]){ events("**** VIDEO CACHE DETECTED ****\n");}
	
	if($AS_34){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("To squid -> 34 OK rewrite-url=\"$results\" + CRLF");}
		print("OK rewrite-url=\"$results\"\n");
		return;	
	}
	
	
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events("To squid -> $results + CRLF");}
	print($results."\n");
	return true;
	
	
}


function GoogleSafeBrowsing($URI,$PROTO,$HOST,$IP,$userid){
	if($PROTO<>"GET"){return false;}
	if($GLOBALS["EnableGoogleSafeBrowsing"]==0){return false;}
	if(trim($GLOBALS["GoogleSafeBrowsingApiKey"])==null){return false;}
	if(preg_match("#(youtube|google|dropbox|apple)\.[a-z]+$#", $HOST)){return false;}
	$response=GoogleSafeBrowsingInCache($HOST);
	if($response==null){$response=GoogleSafeBrowsingGet($PROTO,$HOST);}
	
	if(!$response){return false;}
	if($response==null){return false;}
	
	//A BLOCKER safebrowsing.clients.google.com
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: response: $HOST = `$response`");}
	$response=trim(strtolower($response));
	if(preg_match("#(malware|phishing)#is", $response) ){
		if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: Saving: $HOST = `$response`");}
		GoogleSafeBrowsingOutCache($HOST,trim(strtolower($response)));
		if(preg_match("#([0-9\.]+)#", $IP,$re)){$IP=$re[1];}
		if($userid=="-"){$userid=null;}
		$urlenc=urlencode($URI);
		$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
		base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
		"&clientgroup=Default-$response&targetgroup=safebrowsing&url=$urlenc";
		ufdbgevents($response,"safebrowsing");
		Output_results($returned);
		return true;
		
	}
	
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: $HOST = `clean`");}
	GoogleSafeBrowsingOutCache($HOST,"clean");
	
	
	
}


function GoogleSafeBrowsingOutCache($servername,$response){
	$dbfile="/var/log/squid/GoogleSafeBrowsing.db";
	$MD5=md5($servername);
	$GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5]=$response;
	
	tool_create_berekley($dbfile);
	if(!is_file($dbfile)){return null;}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("GoogleSafeBrowsingOutCache:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	if(!@dba_exists("CREATED",$db_con)){
		$GLOBALS["GoogleSafeBrowsingCache_time"]=time();
		@dba_replace("CREATED",time(),$db_con);
	
	}
	
	@dba_replace($MD5,$response,$db_con);
	@dba_close($db_con);
	
}

function GoogleSafeBrowsingInCache($servername){
	$dbfile="/var/log/squid/GoogleSafeBrowsing.db";
	$GoogleSafeBrowsingCacheTime=$GLOBALS["GoogleSafeBrowsingCacheTime"];
	
	if(isset($GLOBALS["GoogleSafeBrowsingCache_time"])){
		$Since=tool_time_min($GLOBALS["GoogleSafeBrowsingCache_time"]);
		
		if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsingInCache: $dbfile ({$Since}Mn/{$GoogleSafeBrowsingCacheTime}Mn");}
		
		if($Since>$GoogleSafeBrowsingCacheTime){
			$GLOBALS["GoogleSafeBrowsingMEMCache"]=array();
			@unlink($dbfile); 
		}
	}
	
	$MD5=md5($servername);
	if(isset($GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5])){
		if(count($GLOBALS["GoogleSafeBrowsingMEMCache"])>64000){$GLOBALS["GoogleSafeBrowsingMEMCache"]=array();}
		return $GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5];
	}
	
	tool_create_berekley($dbfile);
	if(!is_file($dbfile)){return null;}
	
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("GoogleSafeBrowsingInCache:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	if(!@dba_exists("CREATED",$db_con)){
		$GLOBALS["GoogleSafeBrowsingCache_time"]=time();
		@dba_replace("CREATED",time(),$db_con);
	
	}
	
	if(!isset($GLOBALS["GoogleSafeBrowsingCache_time"])){
		if(@dba_exists("CREATED",$db_con)){
			$GLOBALS["GoogleSafeBrowsingCache_time"]=dba_fetch("CREATED",$db_con);
		}
	}
	
	if(!@dba_exists($MD5,$db_con)){
		@dba_close($db_con);
		return null;
	}
	
	$result = dba_fetch($MD5,$db_con);
	$GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5]=$result;
	@dba_close($db_con);
	return $result;
	
}

function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

function GoogleSafeBrowsingGet($PROTO,$servername){
	$start_time = microtime(true);
	if(!isset($GLOBALS["PROXY"]["ArticaProxyServerEnabled"])){
		$GLOBALS["PROXY"]["ArticaProxyServerEnabled"]="no";
		$GLOBALS["PROXY"]["ArticaProxyServerName"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerPort"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerUsername"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"]=null;
	}

	$ArticaProxyServerEnabled=$GLOBALS["PROXY"]["ArticaProxyServerEnabled"];
	$ArticaProxyServerName=$GLOBALS["PROXY"]["ArticaProxyServerName"];
	$ArticaProxyServerPort=$GLOBALS["PROXY"]["ArticaProxyServerPort"];
	$ArticaProxyServerUsername=trim($GLOBALS["PROXY"]["ArticaProxyServerUsername"]);
	$ArticaProxyServerUserPassword=$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"];

	
	$servername=urlencode("http://$servername/");
	
	$url="https://sb-ssl.google.com/safebrowsing/api/lookup?client=api&apikey={$GLOBALS["GoogleSafeBrowsingApiKey"]}&appver=1.5.2&pver=3.1&url=$servername";
	
	if($GLOBALS["GOOGLE_SAFE"]){
		events("GoogleSafeBrowsingGet: $url");
	}
	
	$curl = curl_init($url);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
	curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 3600);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	if(trim($GLOBALS["GoogleSafeBrowsingDNS"])<>null){
			@curl_setopt($curl, CURLOPT_DNS_SERVERS,$GLOBALS["GoogleSafeBrowsingDNS"]); 
	}
	if($GLOBALS["GoogleSafeBrowsingInterface"]<>null){curl_setopt($curl, CURLOPT_INTERFACE,$GLOBALS["GoogleSafeBrowsingInterface"]); }

	if($ArticaProxyServerEnabled=="yes"){
		curl_setopt($curl,CURLOPT_HTTPPROXYTUNNEL,FALSE);
		curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($curl, CURLOPT_PROXY, $ArticaProxyServerName);
		curl_setopt($curl, CURLOPT_PROXYPORT, $ArticaProxyServerPort);
		if($ArticaProxyServerUsername<>null){
			curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_PROXYUSERPWD, $ArticaProxyServerUsername.':'.$ArticaProxyServerUserPassword);
		}
	}

	$response = curl_exec($curl);
	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$end_time = microtime(true);
	$Infos= curl_getinfo($curl);
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsingGet: Connection ".($end_time - $start_time)."ms"); }
	
	if(!$response){
		if($http_status==204){
			curl_close($curl);
			return "clean";
		}
		$errno = curl_errno($curl);
		$error_message=curl_strerror($errno);
		events("GoogleSafeBrowsingGet: DNS...: {$GLOBALS["GoogleSafeBrowsingDNS"]}, Interface \"{$GLOBALS["GoogleSafeBrowsingInterface"]}\"");
		events("GoogleSafeBrowsingGet: failed:HTTP:$http_status Error Number: $errno ($error_message) - ".curl_error($curl));
		ufdbg_admin_mysql(1, "Google Safe Browsing failed with Error $errno ($error_message)", null,__FILE__,__LINE__);
		curl_close($curl);
		return null;
	}
	
	
	curl_close($curl);
	return $response;
}

function curl_strerror($errno){
	$error_codes=array(
			1 => 'CURLE_UNSUPPORTED_PROTOCOL',
			2 => 'CURLE_FAILED_INIT',
			3 => 'CURLE_URL_MALFORMAT',
			4 => 'CURLE_URL_MALFORMAT_USER',
			5 => 'CURLE_COULDNT_RESOLVE_PROXY',
			6 => 'CURLE_COULDNT_RESOLVE_HOST',
			7 => 'CURLE_COULDNT_CONNECT',
			8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
			9 => 'CURLE_REMOTE_ACCESS_DENIED',
			11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
			13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
			14=>'CURLE_FTP_WEIRD_227_FORMAT',
			15 => 'CURLE_FTP_CANT_GET_HOST',
			17 => 'CURLE_FTP_COULDNT_SET_TYPE',
			18 => 'CURLE_PARTIAL_FILE',
			19 => 'CURLE_FTP_COULDNT_RETR_FILE',
			21 => 'CURLE_QUOTE_ERROR',
			22 => 'CURLE_HTTP_RETURNED_ERROR',
			23 => 'CURLE_WRITE_ERROR',
			25 => 'CURLE_UPLOAD_FAILED',
			26 => 'CURLE_READ_ERROR',
			27 => 'CURLE_OUT_OF_MEMORY',
			28 => 'CURLE_OPERATION_TIMEDOUT',
			30 => 'CURLE_FTP_PORT_FAILED',
			31 => 'CURLE_FTP_COULDNT_USE_REST',
			33 => 'CURLE_RANGE_ERROR',
			34 => 'CURLE_HTTP_POST_ERROR',
			35 => 'CURLE_SSL_CONNECT_ERROR',
			36 => 'CURLE_BAD_DOWNLOAD_RESUME',
			37 => 'CURLE_FILE_COULDNT_READ_FILE',
			38 => 'CURLE_LDAP_CANNOT_BIND',
			39 => 'CURLE_LDAP_SEARCH_FAILED',
			41 => 'CURLE_FUNCTION_NOT_FOUND',
			42 => 'CURLE_ABORTED_BY_CALLBACK',
			43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
			45 => 'CURLE_INTERFACE_FAILED',
			47 => 'CURLE_TOO_MANY_REDIRECTS',
			48 => 'CURLE_UNKNOWN_TELNET_OPTION',
			49 => 'CURLE_TELNET_OPTION_SYNTAX',
			51 => 'CURLE_PEER_FAILED_VERIFICATION',
			52 => 'CURLE_GOT_NOTHING',
			53 => 'CURLE_SSL_ENGINE_NOTFOUND',
			54 => 'CURLE_SSL_ENGINE_SETFAILED',
			55 => 'CURLE_SEND_ERROR',
			56 => 'CURLE_RECV_ERROR',
			58 => 'CURLE_SSL_CERTPROBLEM',
			59 => 'CURLE_SSL_CIPHER',
			60 => 'CURLE_SSL_CACERT',
			61 => 'CURLE_BAD_CONTENT_ENCODING',
			62 => 'CURLE_LDAP_INVALID_URL',
			63 => 'CURLE_FILESIZE_EXCEEDED',
			64 => 'CURLE_USE_SSL_FAILED',
			65 => 'CURLE_SEND_FAIL_REWIND',
			66 => 'CURLE_SSL_ENGINE_INITFAILED',
			67 => 'CURLE_LOGIN_DENIED',
			68 => 'CURLE_TFTP_NOTFOUND',
			69 => 'CURLE_TFTP_PERM',
			70 => 'CURLE_REMOTE_DISK_FULL',
			71 => 'CURLE_TFTP_ILLEGAL',
			72 => 'CURLE_TFTP_UNKNOWNID',
			73 => 'CURLE_REMOTE_FILE_EXISTS',
			74 => 'CURLE_TFTP_NOSUCHUSER',
			75 => 'CURLE_CONV_FAILED',
			76 => 'CURLE_CONV_REQD',
			77 => 'CURLE_SSL_CACERT_BADFILE',
			78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
			79 => 'CURLE_SSH',
			80 => 'CURLE_SSL_SHUTDOWN_FAILED',
			81 => 'CURLE_AGAIN',
			82 => 'CURLE_SSL_CRL_BADFILE',
			83 => 'CURLE_SSL_ISSUER_ERROR',
			84 => 'CURLE_FTP_PRET_FAILED',
			84 => 'CURLE_FTP_PRET_FAILED',
			85 => 'CURLE_RTSP_CSEQ_ERROR',
			86 => 'CURLE_RTSP_SESSION_ERROR',
			87 => 'CURLE_FTP_BAD_FILE_LIST',
			88 => 'CURLE_CHUNK_FAILED'
			);
	
	return $error_codes[$errno];
}



function reset_memory(){
	if(!is_file("/var/log/squid/reload/{$GLOBALS["MYPID"]}.ufdbgclient.php")){return;}
	events("FREE MEMORY");
	unset($GLOBALS["WhitelistedBase"]);
	unset($GLOBALS["BlacklistedBase"]);
	unset($GLOBALS["CACHE"]);
	unset($GLOBALS["HyperCacheRulesSave"]);
	unset($GLOBALS["GoogleSafeBrowsingCache_time"]);
	unset($GLOBALS["GoogleSafeBrowsingMEMCache"]);
	unset($GLOBALS["NOTIFS"]);
	unset($GLOBALS["HyperCacheRules"]);
	unset($GLOBALS["HyperCacheOK"]);
	HyperCacheRulesLoad();
	
	@unlink("/var/log/squid/reload/{$GLOBALS["MYPID"]}.ufdbgclient.php");
	
	ufdbconfig();
}

function HyperCacheRulesLoad(){
	if($GLOBALS["SquidEnforceRules"]==0){return;}
	$GLOBALS["HyperCacheRulesMirror"]=array();
	$GLOBALS["HyperCacheRules"]=array();
	$GLOBALS["HyperCacheRulesWhiteList"]=array();
	
	
	$dbfile="/usr/share/squid3/HyperCacheRules.db";
	if(!is_file($dbfile)){return;}
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$array=unserialize(dba_fetch($mainkey,$db_con));
		
		$GLOBALS["HyperCacheRules"][$mainkey]=$array;
		events("HyperCache: HyperCacheRulesLoad():: Loading rule ID $mainkey");
		$mainkey=dba_nextkey($db_con);
		
	}
	
	dba_close($db_con);
	
	$dbfile="/usr/share/squid3/HyperCacheRules_wl.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		
	
		$GLOBALS["HyperCacheRulesWhiteList"][$mainkey]=TRUE;
		events("HyperCache: HyperCacheRulesLoad():: Loading whitelist rule $mainkey");
		$mainkey=dba_nextkey($db_con);
	
	}
	
	dba_close($db_con);	
// -------------------------------------------------------------------------------------------------------	
	$dbfile="/usr/share/squid3/HyperCacheRules_mirror.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$GLOBALS["HyperCacheRulesMirror"][$mainkey]=TRUE;
		events("HyperCache: HyperCacheRulesLoad():: Loading Mirror rule $mainkey");
		$mainkey=dba_nextkey($db_con);
	
	}
	
	dba_close($db_con);
// -------------------------------------------------------------------------------------------------------	
	
}


function Unlocked($url,$IP,$userid){
	$dbfile="/var/log/squid/ufdbgclient.unlock.db";
	if(!is_file($dbfile)){
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: $dbfile no such file");}
		return false;
	}
	$H=parse_url($url);
	$domain=$H["host"];
	$hostname=null;
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: berekley_db_size:: FATAL!!!::$dbfile, unable to open");}
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	if($GLOBALS["VERBOSE"]){events("ufdbunlock: mainkey: { $mainkey }");}
	
	if(strlen($mainkey)>3){
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: $mainkey");}
		$array=unserialize(dba_fetch($mainkey,$db_con));
		if(Unlocked_parse($array,$url,$IP,$userid)){
			dba_close($db_con);
			return true;
		}
	}
	
	while($mainkey !=false){
		$val=0;
	
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: { $mainkey }");}
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		$array=unserialize(dba_fetch($mainkey,$db_con));
		if(Unlocked_parse($array,$url,$IP,$userid)){
			dba_close($db_con);
			return true;
		}
		
	
		$mainkey=dba_nextkey($db_con);
	}
	
	
	dba_close($db_con);
	return false;
}

function Unlocked_parse($array,$url,$IP,$userid){
	$userid=trim(strtolower($userid));
	$uid=trim(strtolower($array["uid"]));
	$ipaddr=$array["ipaddr"];
	$www=$array["www"];
	$finaltime=$array["finaltime"];
	
	$H=parse_url($url);
	$domain=$H["host"];
	
	if($GLOBALS["VERBOSE"]){events("ufdbunlock: $uid,$ipaddr,$www,$finaltime -> $domain,$IP,$userid");}
	
	if($finaltime<time()){
		events("$finaltime -> EXPIRED");
		return false;
	}
	$www=str_replace(".", "\.", $www);
	if(!preg_match("#$www#i", $domain)){
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: '$domain'/$www -> NO MATCH");}
		return false;
	}
	
	if($IP==$ipaddr){
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: '$IP'/'$ipaddr' -> MATCH");}
		Output_results(null);
		return true;
	
	}else{
		if($GLOBALS["VERBOSE"]){events("ufdbunlock: '$IP'/'$ipaddr' -> NO MATCH");}
	}
	
	
	if($userid<>null){
		if($uid<>null){
			if($userid<>$uid){
				if($GLOBALS["VERBOSE"]){events("ufdbunlock: $userid -> NO MATCH");}
				return false;
				
			}
		}
		
	}

	
	
	Output_results(null);
	return true;
	
	

	
}

function BlackListedBase($url,$IP,$userid){
	$db_path="/var/log/squid/ufdbgclient.black.db";
	
	if(!is_file($db_path)){
		if($GLOBALS["VERBOSE"]){events("$db_path -> no such file");}
		return false;
	}
	
	$H=parse_url($url);
	$domain=$H["host"];
	reset_memory();
	if($GLOBALS["SquidGuardIPWeb"]==null){
		$GLOBALS["SquidGuardIPWeb"]="http://127.0.0.1/exec.squidguard.php";
	}
	$urlenc=urlencode($url);
	if(preg_match("#([0-9\.]+)#", $IP,$re)){$IP=$re[1];}
	if($userid=="-"){$userid=null;}
	$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
	base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
	"&clientgroup=default&targetgroup=blacklist&url=$urlenc";

	if(isset($GLOBALS["BlacklistedBase"][$domain])){
		
		if($GLOBALS["BlacklistedBase"][$domain]){
			if($GLOBALS["VERBOSE"]){events("BlackListedBase: $domain -> MEM BLOCK"); }
			ufdbgevents("blacklist","default");
			Output_results($returned);
			return true;
		}else{
			if($GLOBALS["VERBOSE"]){events("BlackListedBase: $domain -> MEM PASS"); }
			return false;
		}
	}
	
	
	$db_con = dba_open($db_path, "r","db4");
	if(!$db_con){return false;}
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$val=0;
	
	
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){events("BlackListedBase:Checking $mainkey -> $domain"); }
		if(preg_match("#$mainkey#", $domain)){
			$GLOBALS["BlacklistedBase"][$domain]=true;
			if($GLOBALS["VERBOSE"]){events("BlackListedBase:  BLACKLIST MATCH $mainkey -> $domain"); }
			ufdbgevents("blacklist","global-blacklist");
			Output_results($returned);
			dba_close($db_con);
			return true;
		}
	
		$mainkey=dba_nextkey($db_con);
	
	}
	$GLOBALS["BlacklistedBase"][$domain]=false;
	dba_close($db_con);
	return false;	
	
	
}

function Output_results($results=null){
	$AS_34=$GLOBALS["AS_34"];
	
	if($results==null){
		if($AS_34){
			if($GLOBALS["VERBOSE"]){ events("PASS in 3.4 mode");}
			print("OK\n");
			return;
		}
	}
	
	if($results==null){
		if($GLOBALS["VERBOSE"]){ events("PASS in 3.3 mode");}
		print("\n");
		return;
	}
	
	$length=strlen($results);
	if($AS_34){
		if($GLOBALS["VERBOSE"]){ events("Query must be locked send $length bytes in 3.4 mode");}
		if(preg_match("#^OK\s+#", $results)){print($results."\n");return;}
		print("OK rewrite-url=\"$results\"");
		return;
	}
	if($GLOBALS["VERBOSE"]){ events("Query must be locked send $length bytes in 3.3 mode");}
	print("$results\n");
}

function UfdbBlackList($ToUfdb){
	
	$results=trim(ask_to_ufdb($ToUfdb));
	if(preg_match("#SquidGuardIPWeb=(.+?)&#", $results,$re)){$GLOBALS["SquidGuardIPWeb"]=base64_decode($re[1]);}
	
	$length=strlen(trim($results));
	if($GLOBALS["VERBOSE"]){ events("ask_to_ufdb return $length bytes");}
	if($length<5){return false;}

	Output_results($results);
	return true;
}

function WhitelistedBase($url){
	$db_path="/var/log/squid/ufdbgclient.white.db";
	$H=parse_url($url);
	$domain=$H["host"];
	
	
	
	
	if(isset($GLOBALS["WhitelistedBase"][$domain])){
		
		if($GLOBALS["WhitelistedBase"][$domain]){
			if($GLOBALS["VERBOSE"]){events("WhitelistedBase MEM WHITELISTED"); }
			Output_results(null);
			return true;
		}else{
			if($GLOBALS["VERBOSE"]){events("WhitelistedBase MEM NOT WHITELISTED"); }
			return false;
		}
	}
	
	if(!is_file($db_path)){
		if($GLOBALS["VERBOSE"]){events("$db_path -> no such file");}
		return false;
	}
	$db_con = dba_open($db_path, "r","db4");
	if(!$db_con){return false;}
	$mainkey=dba_firstkey($db_con);
	
	
	while($mainkey !=false){
		$val=0;
	
	
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		if($GLOBALS["VERBOSE"]){events("WhitelistedBase: Checking $mainkey -> $domain"); }
		if(preg_match("#$mainkey#", $domain)){
			if($GLOBALS["VERBOSE"]){events("WhitelistedBase $mainkey MATCH $domain"); }
			$GLOBALS["WhitelistedBase"][$domain]=true;
			Output_results(null);
			dba_close($db_con);
			return true;
		}
	
		$mainkey=dba_nextkey($db_con);
	
	}
	$GLOBALS["WhitelistedBase"][$domain]=false;
	dba_close($db_con);
	return false;

}

function ask_to_videocache($datatosend){
	
	$array=explode(" ", $datatosend);
	$URI=$array[0];
	$IP=$array[1];
	$userid=$array[2];
	$PROTO=$array[3];
	$myIP=$array[4];
	$myPort=$array[5];
	if(!videocache_checker($URI)){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE videocache_checker return FALSE"); }
		return "\n";
	}
	
	if(!isset($GLOBALS["PROCESS_VIDEOCACHE"])){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE not a ressource"); }
		return;
	}
	if (!is_resource($GLOBALS["PROCESS_VIDEOCACHE"])) {
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE not a ressource (2)"); }
		return;
	}
	$text=null;
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events("Send \"$datatosend\""); }
	fwrite($GLOBALS["pipes_videocache"][0], $datatosend);
	$text= fgets($GLOBALS["pipes_videocache"][1], 2048);
	
	$strlen=strlen(trim($text));
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE RESPONSE \"$text\""); }
	if($strlen>5){
		
		$text=str_replace("\r\n", "", $text);
		$text=str_replace("\r", "", $text);
		$text=str_replace("\n", "", $text);
		$text=trim($text);
		if($GLOBALS["VERBOSE"]){
			if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE RESPONSE"); }
		}
	}
	
	return $text;

	
	
}

function videocache_checker($uri){
	
	
	$parse_url=parse_url($uri);
	$hosntame=$parse_url["host"];
	if(isset($GLOBALS["videocache_checker_false"][$hosntame])){return false;}
	
	$domzDeny[]="\.manifest\.youtube\.com";
	$domzDeny[]="\.manifest\.googlevideo\.com";
	$domzDeny[]="\.redirector\.googlevideo\.com";
	$domzDeny[]="\.redirector\.youtube\.com";
	
	while (list ($num, $pattern) = each ($domzDeny)){
		if(preg_match("#$pattern#i", $hosntame)){
			$GLOBALS["videocache_checker_false"][$hosntame]=false;
			return false;
		}
		
	}
			
			
	
	$vc_deny_url[]="crossdomain\.xml";
	$vc_deny_url[]="\.blip\.tv\/(.*?)filename \.hardsextube\.com\/videothumbs";
	$vc_deny_url[]="\.xtube\.com\/(.*)(Thumb|videowall)";
	
	$vc_deny_url[]="\.(youtube|googlevideo)\.com\/.*\/manifest";
	$vc_deny_url[]="\.(youtube|googlevideo)\.com\/videoplayback?.*?playerretry=[0-9]";
	
	while (list ($num, $pattern) = each ($domzDeny)){
		if(preg_match("#$pattern#i", $uri)){
			
			return false;}
	
	}
	
	$domz[]="\.stream\.aol\.com";
	$domz[]="\.5min\.com";
	$domz[]="\.msn\.com";
	$domz[]="\.blip\.tv";
	$domz[]="\.dmcdn\.net";
	$domz[]="\.break\.com";
	$domz[]="\.vimeo\.com";
	$domz[]="\.vimeocdn\.com ";
	$domz[]="video\.thestaticvube\.com";
	$domz[]="\.dailymotion\.com";
	$domz[]="\.c\.wrzuta\.pl";
	$domz[]="\.v\.imwx\.com";
	$domz[]="\.mccont\.com";
	$domz[]="\.myspacecdn\.com";
	$domz[]="video-http\.media-imdb\.com";
	$domz[]="fcache\.veoh\.com";
	$domz[]="\.hardsextube\.com";
	$domz[]="\.public\.extremetube\.phncdn\.com";
	$domz[]="\.redtubefiles\.com";
	$domz[]="\.video\.pornhub\.phncdn\.com";
	$domz[]="\.videos\.videobash\.com";
	$domz[]="\.public\.keezmovies\.com";
	$domz[]="\.public\.keezmovies\.phncdn\.com";
	$domz[]="\.slutload-media\.com";
	$domz[]="\.public\.spankwire\.com";
	$domz[]="\.xtube\.com";
	$domz[]="\.public\.youporn\.phncdn\.com";
	$domz[]="\.xvideos\.com";
	$domz[]="\.tube8\.com";
	$domz[]="\.public\.spankwire\.phncdn\.com";
	$domz[]="\.pornhub\.com";
	$domz[]="\.youtube\.com";
	$domz[]="\.googlevideo\.com";	
	$domza[]="msn\..*?\.(com|net)";
	$domza[]="msnbc\..*?\.(com|net)";
	$domza[]="video\..*?\.fbcdn\.net";
	$domza[]="myspacecdn\..*?\.footprint\.net";
	
	while (list ($num, $pattern) = each ($domz)){
		if(preg_match("#$pattern#i", $hosntame)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(1) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(1) $hosntame not detected..."); }
	
	
	while (list ($num, $pattern) = each ($domza)){
		if(preg_match("#^$pattern#i", $hosntame)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(2) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(2) $hosntame not detected..."); }
	
	$vc_url[]="\/youku\/[0-9A-Z]+\/[0-9A-Z\-]+\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$vc_url[]="\/(.*?)key=[a-z0-9]+(.*?)\.flv";
	$vc_url[]="\-xh\.clients\.cdn[0-9a-zA-Z]?[0-9a-zA-Z]?[0-9a-zA-Z]?\.com\/data\/(.*?)\.flv";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/feeds\/api\/videos\/[0-9a-zA-Z_-]{11}\/";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/(videoplayback|get_video|watch_popup|user_watch|stream_204|get_ad_tags|get_video_info|player_204|ptracking|set_awesome)\?";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/(v|e|embed)\/[0-9a-zA-Z_-]{11}";
	$vc_url[]="\.youtube\.com\/s\? \.youtube\.com\/api\/stats\/(atr|delayplay|playback|watchtime)\?";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/videoplayback\/id\/[0-9a-zA-Z_-]+\/";
	$vc_url[]="\.android\.clients\.google\.com\/market\/GetBinary\/";
	$vc_url[]="cs(.*?)\.vk\.me\/(.*?)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$vc_url[]="video(.*?)\.rutube\.ru\/(.*?)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)Seg[0-9]+-Frag[0-9]+";
	
	while (list ($num, $pattern) = each ($vc_url)){
		if(preg_match("#$pattern#i", $uri)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(3) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(3) $hosntame not detected ..."); }
	
}

function ask_to_ufdb_cache_get($datatosend){
	$MD5=md5($datatosend);
	$dbfile="/var/log/squid/UfdbguardCache.db";
	if(isset($GLOBALS["CACHE"][$MD5])){return array(true,$GLOBALS["CACHE"][$MD5]);}
	if(!is_file($dbfile)){return false;}
	
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("ask_to_ufdb_cache_get:: FATAL!!!::$dbfile, unable to open"); return false; }
	
	if(!@dba_exists($MD5,$db_con)){
		@dba_close($db_con);
		return array(false,null);
	}
	
	$result = dba_fetch($MD5,$db_con);
	$GLOBALS["CACHE"][$MD5]=$result;
	@dba_close($db_con);
	return array(true,$result);
	
}
function ask_to_ufdb_cache_set($datatosend,$buf){

	$MD5=md5($datatosend);
	$GLOBALS["CACHE"][$MD5]=$buf;
	if(count($GLOBALS["CACHE"])>50000){unset($GLOBALS["CACHE"]);}
	$dbfile="/var/log/squid/UfdbguardCache.db";
	
	
	
	if(!is_file($dbfile)){
		try {
			events("ask_to_ufdb_cache_set:: Creating $dbfile database"); $db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {
				$error=$e->getMessage(); events("ask_to_ufdb_cache_set::FATAL ERROR $error on $dbfile");
				@dba_close($db_desttmp);
				return;
			}
		@dba_close($db_desttmp);
	}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("ask_to_ufdb_cache_set:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	@dba_replace($MD5,$buf,$db_con);
	@dba_close($db_con);

}

function ask_to_ufdb($datatosend){
	
	$CachedZ=ask_to_ufdb_cache_get($datatosend);
	if($CachedZ[0]){return $CachedZ[1];}
	
	$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
	
	if(!@socket_connect($socket, $GLOBALS["UFDB_SERVER"], $GLOBALS["UFDB_PORT"])){
		$socketerror=socket_strerror(socket_last_error($socket));
		events("ask_to_ufdb: [{$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]}] ");
		ufdbg_admin_mysql(1, "Web filtering socket error $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		@socket_close($socket);
		return false;
	}
	
	//if($GLOBALS["VERBOSE"]){events("ask_to_ufdb:\"$datatosend\"");}
	
	@socket_write($socket, $datatosend, strlen($datatosend));
	$buf = @socket_read($socket, 1024);
	if(!$buf){
		$socketerror=socket_strerror(socket_last_error($socket));
		events("ask_to_ufdb: Socket error:$socketerror");
		ufdbg_admin_mysql(1, "Web filtering socket error $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		@socket_close($socket);
		return false;
	}
	@socket_close($socket);
	
	$buf=str_replace("\r\n", "", $buf);
	$buf=str_replace("\r", "", $buf);
	$buf=str_replace("\n", "", $buf);
	if($GLOBALS["VERBOSE"]){events("ask_to_ufdb: Receive \"$buf\"");}
	ask_to_ufdb_cache_set($datatosend,$buf);
	return $buf;
}

function ufdbconfig(){
	
	
	$GLOBALS["GoogleSafeBrowsingApiKey"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingApiKey"));
	$GLOBALS["EnableGoogleSafeBrowsing"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableGoogleSafeBrowsing"));
	$GLOBALS["GoogleSafeBrowsingCacheTime"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingCacheTime"));
	
	$GLOBALS["HyperCacheStoragePath"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheStoragePath"));
	$GLOBALS["HyperCacheMemEntries"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheMemEntries"));
	$GLOBALS["HyperCacheBuffer"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheBuffer"));
	
	
	$GLOBALS["HyperCacheHTTPListenPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheHTTPListenPort"));
	if($GLOBALS["HyperCacheHTTPListenPort"]==0){$GLOBALS["HyperCacheHTTPListenPort"]=8700;}
	
	$GLOBALS["HyperCacheListenAddr"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheListenAddr"));
	
	
	
	
	if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	if($GLOBALS["GoogleSafeBrowsingCacheTime"]==0){$GLOBALS["GoogleSafeBrowsingCacheTime"]=10080;}
	$GLOBALS["GoogleSafeBrowsingDNS"]=trim(strtolower(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingDNS")));
	$GLOBALS["GoogleSafeBrowsingInterface"]=trim(strtolower(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingInterface")));
	
	
	if($GLOBALS["HyperCacheMemEntries"]==0){$GLOBALS["HyperCacheMemEntries"]=500000;}
	if($GLOBALS["HyperCacheBuffer"]==0){$GLOBALS["HyperCacheBuffer"]=50;}
	
	
	if($GLOBALS["GoogleSafeBrowsingDNS"]==null){$GLOBALS["GoogleSafeBrowsingDNS"]="8.8.8.8,4.4.4.4";}
	if($GLOBALS["GoogleSafeBrowsingDNS"]=="*"){$GLOBALS["GoogleSafeBrowsingDNS"]=null;}
	if($GLOBALS["GoogleSafeBrowsingDNS"]=="default"){$GLOBALS["GoogleSafeBrowsingDNS"]=null;}
	
	
	if($GLOBALS["GOOGLE_SAFE"]){
		events("ufdbconfig:: /etc/artica-postfix/settings/Daemons/EnableGoogleSafeBrowsing = {$GLOBALS["EnableGoogleSafeBrowsing"]}");
	}
	
	$GLOBALS["PROXY"]=array();
	$ini=new Bs_IniHandler();
	
	$datas=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaProxySettings");
	if(trim($datas)<>null){$ini->loadString($datas);}
	if(isset($ini->_params["PROXY"])){$GLOBALS["PROXY"]=$ini->_params["PROXY"];}
	
	$GLOBALS["SquidEnforceRules"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidEnforceRules"));
	$GLOBALS["EnableStreamCache"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStreamCache"));
	$EnableUfdbGuard=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard"));
	$UseRemoteUfdbguardService=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UseRemoteUfdbguardService"));
	$EnableRemoteStatisticsAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance"));
	$datas=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")));
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
	$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
	if(is_array($SquidGuardIPWeb)){$GLOBALS["SquidGuardIPWeb"]=$SquidGuardIPWeb["SquidGuardIPWeb"];}
	$SquidGuardWebUseExternalUri=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebUseExternalUri"));
	if($SquidGuardWebUseExternalUri==1){
		$GLOBALS["SquidGuardIPWeb"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUri");
		$GLOBALS["SquidGuardIPWeb_SSL"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUriSSL");
	}
	
	if($GLOBALS["SquidEnforceRules"]==1){
		HyperCacheRulesLoad();
	}
	
	

	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if($datas["remote_server"]=="all"){$datas["remote_server"]="127.0.0.1";}
	if($datas["remote_server"]==null){$datas["remote_server"]="127.0.0.1";}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}

	
	if($EnableRemoteStatisticsAppliance==1){
		$GLOBALS["EnableUfdbGuard"]=1;
		$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$UseRemoteUfdbguardService=1;
		$GLOBALS["EnableUfdbGuard"]=1;
		$datas["remote_port"]=$datas["listen_port"];
		events("Using remote appliance {$RemoteStatisticsApplianceSettings["SERVER"]}:{$datas["listen_port"]} as Web filtering engine");
		$GLOBALS["UFDB_SERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$GLOBALS["UFDB_PORT"]=$datas["listen_port"];
		return;
	}

	
	if($UseRemoteUfdbguardService==1){
		$GLOBALS["EnableUfdbGuard"]=1;
		$GLOBALS["UFDB_SERVER"]=$datas["remote_server"];
		$GLOBALS["UFDB_PORT"]=$datas["remote_port"];
		if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		if(trim($GLOBALS["UFDB_SERVER"]==null)){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]=null;}
		if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		if($GLOBALS["UFDB_SERVER"]=="all"){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if($GLOBALS["UFDB_SERVER"]==null){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		events("Using remote service {$datas["remote_server"]}:{$datas["remote_port"]} as Web filtering engine");
		
		return;
	}
	if($EnableUfdbGuard==0){
		events("Web filtering engine is disabled");
		$GLOBALS["EnableUfdbGuard"]=0;
		return;
	}
	$effective_port=ufdbguard_value("port");
	$interface=ufdbguard_value("interface");
	
	$GLOBALS["UFDB_SERVER"]=$interface;
	$GLOBALS["UFDB_PORT"]=$effective_port;
	$GLOBALS["EnableUfdbGuard"]=1;
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if(trim($GLOBALS["UFDB_SERVER"]==null)){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]=null;}
	if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if($GLOBALS["UFDB_SERVER"]=="all"){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if($GLOBALS["UFDB_SERVER"]==null){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	events("Using local service {$interface}:{$effective_port} as Web filtering engine");
}

function ufdbguard_value($key){
	if(!is_file("/etc/squid3/ufdbGuard.conf")){return null;}
	if(isset($GLOBALS[__FUNCTION__][$key])){return $GLOBALS[__FUNCTION__][$key];}
	if(!isset($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	if(!is_array($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	while (list ($num, $ligne) = each ($GLOBALS["UFDGUARDDATAFILE"]) ){
		if(preg_match("#^$key\s+(.*)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__][$key]=$re[1];
			return $re[1];}
	}
	
	

}

function events($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/ufdbgclient.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["OUTPUT"]){echo "$pid `[{$GLOBALS["LOG_DOM"]}]: $text`\n";}
	@fwrite($f, "$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid `[{$GLOBALS["LOG_DOM"]}]:$text`\n");
	@fclose($f);
}
function ufdbgevents($category,$rulename){
	$array["uid"]=$GLOBALS["LOG_AR"]["userid"];
	$array["TIME"]=time();
	$array["category"]=$category;
	$array["rulename"]=$rulename;
	$array["public_ip"]=null;
	$array["blocktype"]="blocked domain";
	$array["why"]="blocked domain";
	$array["hostname"]=null;
	$array["website"]=$GLOBALS["LOG_AR"]["host"];
	$array["client"]=$GLOBALS["LOG_AR"]["IP"];
	$LLOG=array();
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(is_file("/var/log/squid/ufdbguard-blocks/$md5.sql")){return;}
	@file_put_contents("/var/log/squid/ufdbguard-blocks/$md5.sql",$serialize);
}

function ufdbg_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	
	$key=md5($subject);
	if(isset($GLOBALS["NOTIFS"][$key])){
		if(tool_time_min($GLOBALS["NOTIFS"][$key])<5){return;}
		
	}
	$GLOBALS["NOTIFS"][$key]=time();
	
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$GLOBALS["SCHEDULE_ID"]=0;
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/squid/squid_admin_mysql")){@mkdir("/var/log/squid/squid_admin_mysql",0755,true);}
	@file_put_contents("/var/log/squid/squid_admin_mysql/$md5.log", $serialize);
	

}
