<?php
$GLOBALS["HYPER_CACHE_VERBOSE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["HYPER_CACHE_VERBOSE_RULES"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.services.inc');
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.HyperCache.inc");

ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["HYPER_CACHE_VERBOSE"]=true;$GLOBALS["HYPER_CACHE_VERBOSE_RULES"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(count($argv)>0){
	if(isset($argv[1])){
		if($argv[1]=="--rules"){buildRules();exit;}
		if($argv[1]=="--reconfigure"){reconfigure();exit;}
		if($argv[1]=="--dirsizes"){GetRulesSizes();exit;}
		if($argv[1]=="--delete"){DeleteRules(true);exit;}
		if($argv[1]=="--logs"){HyperCacheLogs(true);exit;}
		if($argv[1]=="--mirror-single"){$GLOBALS["FORCE"]=true;HyperCacheMirror_single($argv[2]);exit;}
		if($argv[1]=="--mirror"){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;} HyperCacheMirror(); exit;}
	}
	
}

xstart();



function xstart(){
	
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/exec.squidcache.php.time";
	$PidFile="/etc/artica-postfix/pids/exec.squidcache.php.pid";
	
	
	$Pid=$unix->get_pid_from_file($PidFile);
	if($unix->process_exists($Pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($Pid);
		if($pidtime>29){
			events("Max execution time reached 30Mn for PID $Pid Kill it...",0,2,__LINE__);
			unix_system_kill_force($Pid);
			die();
		}
		
		
		events("Already running PID $Pid since {$pidtime}Mn",0,2,__LINE__);
		return;
	}
	
	@file_put_contents($PidFile, getmypid());
	
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
	if(count($pids)>3){
		events("Too many instances ". count($pids)." dying",0,1,__LINE__);
		$mypid=getmypid();
		while (list ($pid, $ligne) = each ($pids) ){
			if($pid==$mypid){continue;}
			events("Killing $pid");
			unix_system_kill_force($pid);
		}
	
	}
	
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
	if(count($pids)>3){events("Too many instances ". count($pids)." dying",0,2,__LINE__);die();}	
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$sock=new sockets();
	$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
	if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	@chown("/usr/share/squid3", "squid");
	@chgrp("/usr/share/squid3", "squid");
	
	HyperCacheMirror();
	
	
	if($GLOBALS["HYPER_CACHE_VERBOSE"]){
		events("Storage path: {$GLOBALS["HyperCacheStoragePath"]}",0,2,__LINE__);
		events("Scanning /usr/share/squid3",0,2,__LINE__);
	}
	
	$f=$unix->DirFiles("/usr/share/squid3","HyperCacheQueue-.+?-([0-9]+)\.db$");
	
	$GLOBALS["SIZE_DOWNLOADED"]=0;
	$GLOBALS["HITS"]=0;
	
	while (list ($num, $file) = each ($f) ){
		if($GLOBALS["HYPER_CACHE_VERBOSE"]){events("Found database: $file",0,2,__LINE__);}
		if(!preg_match("#^HyperCacheQueue-.+?-([0-9]+)\.db$#", $file,$re)){continue;}
		if(preg_match("#HyperCacheQueue-dropbox\.com#", $file)){continue;}
		
		$ID=$re[1];
		
		HyperCacheScanDBFile("/usr/share/squid3/$file",$ID);
	}

	if($GLOBALS["SIZE_DOWNLOADED"]>0){
		$size=FormatBytes($GLOBALS["SIZE_DOWNLOADED"]/1024);
		$hits=$GLOBALS["HITS"];
		events("$size downloaded -  $hits requests",$ID,2,__LINE__);
		squid_admin_enforce(2, "$size downloaded and store $hits requests", null,__FILE__,__LINE__);
	}
	
	if($GLOBALS["VERBOSE"]){echo "xstart ---> DeleteRules\n";}
	DeleteRules();
	if($GLOBALS["VERBOSE"]){echo "xstart ---> GetRulesSizes\n";}
	GetRulesSizes();
	
}


function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	if(!$GLOBALS["PROGRESS"]){return;}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.artica-rules.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}
	
function HyperCacheRulesLoad(){
	$dbfile="/usr/share/squid3/HyperCacheRules.db";
	if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "HyperCacheRulesLoad ---> $dbfile [".__LINE__."]\n";}
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "HyperCacheRulesLoad ---> $dbfile FAILED [".__LINE__."]\n";}
		return false; 
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$array=unserialize(dba_fetch($mainkey,$db_con));
		$HyperCacheRules[$mainkey]=$array;
		$mainkey=dba_nextkey($db_con);
	}
	
	dba_close($db_con);
	if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "HyperCacheRulesLoad ---> END \n";}
	return $HyperCacheRules;
}	

function HyperCacheMD5File_set($MD5File,$TargetFile,$FileType,$size,$OriginalFile){
	$dbfile="/usr/share/squid3/HyperCacheMD5.db";
	
	if(!is_file($dbfile)){
		try {
			
			$db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {$error=$e->getMessage(); 
			events("FATAL ERROR $error on $dbfile",0,0,__LINE__);
		}
		@dba_close($db_desttmp);
	
	}
	
	if(!is_file($dbfile)){
		events("FATAL ERROR $error on $dbfile",0,0,__LINE__);
		return;
	}
	$db_con = dba_open($dbfile, "c","db4");
	
	$array["FILEPATH"]=$TargetFile;
	$array["FILENAME"]=$OriginalFile;
	$array["FILESIZE"]=$size;
	$array["FILETYPE"]=$FileType;
	$array["TIME"]=time();
	
	if(!@dba_replace($MD5File,serialize($array),$db_con)){
		events("$dbfile unable to save $md5,$path information...",0,0,__LINE__);
		@dba_close($db_con);
		return;
	}
	
	return true;
	
	
}

function HyperCacheMD5File_clean(){
	
	$unix=new unix();
	$sock=new sockets();
	if(!isset($GLOBALS["HyperCacheStoragePath"])){
		$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
		if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	}
	
	$dbfile="/usr/share/squid3/HyperCacheMD5.db";
	if(!is_file($dbfile)){return;}
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	$mainkey=dba_firstkey($db_con);
	$f=array();
	$c=0;
	while($mainkey !=false){
		$c++;
		$content=dba_fetch($mainkey,$db_con);
		$array=@unserialize($content);
		
		if($content==null){
			$f[$mainkey]=true;
			if($GLOBALS["VERBOSE"]){echo "MARK '$mainkey' to deletion\n";}
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		if(!is_array($array)){
			$f[$mainkey]=true;
			if($GLOBALS["VERBOSE"]){echo "MARK '$mainkey' to deletion\n";}
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		if(!isset($array["FILEPATH"])){
			$f[$mainkey]=true;
			if($GLOBALS["VERBOSE"]){echo "MARK '$mainkey' to deletion\n";}
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		$filepath=$array["FILEPATH"];
		$FullPath="{$GLOBALS["HyperCacheStoragePath"]}/$filepath";
		
		if(!is_file($FullPath)){
			$f[$mainkey]=true;
			$mainkey=dba_nextkey($db_con);
			continue;
			
		}
		if($GLOBALS["VERBOSE"]){echo "SKIP $FullPath - $mainkey\n";}
		$mainkey=dba_nextkey($db_con);
	
	}
	
	@dba_close($db_con);
	
	if(count($f)==0){return;}
	
	$db_con = dba_open($dbfile, "c","db4");
	
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return null; }
	while (list ($mainkey, $filetype) = each ($f) ){
		dba_delete($mainkey, $db_con);
		
	}
	@dba_close($db_con);
	
	
	
	
	
	
}

function HyperCacheRetranslation_scan(){
	$unix=new unix();
	$sock=new sockets();
	if(!isset($GLOBALS["HyperCacheStoragePath"])){
		$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
		if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	}
	
	$files=$unix->DirFiles("/usr/share/squid3","HyperCache-(.+?)-Retranslation\.db$");
	
	while (list ($filename, $none) = each ($files) ){
		HyperCacheRetranslation_clean("/usr/share/squid3/$filename");
	}

}

function  HyperCacheRetranslation_clean($dbfile){
	if(!is_file($dbfile)){return;}
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	$mainkey=dba_firstkey($db_con);
	$f=array();
	$c=0;
	while($mainkey !=false){
		$fetch_content=@dba_fetch($mainkey,$db_con);
		$array=@unserialize($fetch_content);
		$filepath=$array["TARGET"];
		$FullPath="{$GLOBALS["HyperCacheStoragePath"]}/$filepath";
		
		if(!is_file($FullPath)){
			$f[$mainkey]=true;
			$mainkey=dba_nextkey($db_con);
			continue;
				
		}
		
		$mainkey=dba_nextkey($db_con);
	
	}
	
	
	
	@dba_close($db_con);	
	
	
	
	if(count($f)==0){return;}
	$db_con = dba_open($dbfile, "c","db4");
	
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return null; }
	while (list ($mainkey, $filetype) = each ($f) ){
		dba_delete($mainkey, $db_con);
	
	}
	@dba_close($db_con);
}




function HyperCacheMD5File_get($md5){
	$dbfile="/usr/share/squid3/HyperCacheMD5.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	if(!$db_con){return;}
	
	if(!@dba_exists($md5,$db_con)){
		@dba_close($db_con);
		return null;
	}
	
	$fetch_content=@dba_fetch($md5,$db_con);
	$array=@unserialize($fetch_content);
	
	if(!isset($array["TIME"])){
		events("HyperCacheMD5File_get:: $md5 no time set...",0,1,__LINE__);
		return null;
	}
	
	$Path=$array["FILEPATH"];
	
	
	@dba_close($db_con);
	
	if(is_file($GLOBALS["HyperCacheStoragePath"]."/".$Path)){
		events("HyperCacheMD5File_get::  **** return $Path *****",0,3,__LINE__);
		return $Path;
	}else{
		events("HyperCacheMD5File_get:: {$GLOBALS["HyperCacheStoragePath"]}/$Path no such file",0,1,__LINE__);
	}
	return null;
	
}


function HyperCacheRetranslation_get($uri){
	$familysite=tool_get_familysite($uri);
	$dbfile="/usr/share/squid3/HyperCache-$familysite-Retranslation.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	if(!$db_con){return;}
	$md5=md5($uri);
	
	
	
	if(!@dba_exists($md5,$db_con)){
		@dba_close($db_con);
		return null;
	}
	$fetch_content=@dba_fetch($md5,$db_con);$array=@unserialize($fetch_content);
	@dba_close($db_con);
	return $array["TARGET"];
	
}

function tool_get_familysite($uri){
	$parse_url=parse_url($uri);
	$sitename=$parse_url["host"];
	if(isset($GLOBALS["FAMILYSITES"][$sitename])){return $GLOBALS["FAMILYSITES"][$sitename];}
	$f=new familysite();
	$GLOBALS["FAMILYSITES"][$sitename]=$f->GetFamilySites($sitename);
	return $GLOBALS["FAMILYSITES"][$sitename];

}

function HyperCacheRetranslation_set($uri,$MD5File,$FileType,$TargetFile){
	$familysite=tool_get_familysite($uri);
	$unix=new unix();
	$extention=$unix->file_extension(basename($TargetFile));
	$dbfile="/usr/share/squid3/HyperCache-$familysite-Retranslation.db";
	
	if(!is_file($dbfile)){
		try {
			events("Creating $dbfile database",0,2,__LINE__);
			$db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {$error=$e->getMessage();
			events("FATAL ERROR $error on $dbfile",0,0,__LINE__);
			}
			@dba_close($db_desttmp);
	
	}
	
	if(!is_file($dbfile)){
		events("FATAL ERROR $error on $dbfile",0,0,__LINE__);
		return;
	}
	$db_con = dba_open($dbfile, "c","db4");
	$md5=md5($uri);
	
	$array["MD5FILE"]=$MD5File;
	$array["MD5TYPE"]=$FileType;
	$array["EXT"]=$extention;
	$array["TARGET"]=$TargetFile;
	
	if(!@dba_replace($md5,serialize($array),$db_con)){
		events("$dbfile unable to save $md5 information...",0,0,__LINE__);
		@dba_close($db_con);
		return;
	}
	
	return true;
	
}

function HyperCacheScanBuildLocalPath($uri,$ID){
	
	$path=HyperCacheRetranslation_get($uri);
	if($path<>null){return $path;}
	
	$unix=new unix();
	$Root=$GLOBALS["HyperCacheStoragePath"];
	if(!is_dir($Root)){
		@mkdir($Root,0755,true);
		@chown($Root,"squid");
		@chgrp($Root, "squid");
	}
	
	$RulePath="$Root/$ID";
	if(!is_dir($RulePath)){
		@mkdir($RulePath,0755,true);
		@chown($RulePath,"squid");
		@chgrp($RulePath, "squid");
	}
	
	$H=parse_url($uri);
	$sitename=$H["host"];
	$f=new familysite();
	$Family=$f->GetFamilySites($sitename);
	$finalDir="$RulePath/$Family";
	if(!is_dir($finalDir)){
		@mkdir($finalDir,0755,true);
		@chown($finalDir,"squid");
		@chgrp($finalDir, "squid");
	}
	$path=$H["path"];
	$filename=basename($path);
	$extension=$unix->file_ext($filename);
	return "$ID/$Family/".md5($uri).".$extension";
	
}
	
function HyperCacheScanDBFile_set($dbfile,$url,$filetype){
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "c","db4");
	
	if(!@dba_replace($url,$filetype,$db_con)){
		events("$dbfile unable to save $url,$filetype information...",0,0,__LINE__);
		@dba_close($db_con);
		return;
	}
	
	@dba_close($db_con);
	return true;
	
}	

function HyperCacheScanDBFile_setbulk($dbfile,$array){
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "c","db4");

	while (list ($url, $filetype) = each ($array) ){
		if(!@dba_replace($url,$filetype,$db_con)){
			events("$dbfile unable to save $url,$filetype information...",0,0,__LINE__);
			@dba_close($db_con);
			return;
		}
	}

	@dba_close($db_con);
	return true;

}

	
function HyperCacheScanDBFile($dbfile,$ID){
	$TT=time();
	
	
	$filesize=FormatBytes(@filesize($dbfile)/1024);
	
	if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "HyperCacheScanDBFile ---> $dbfile [".__LINE__."]\n";}
	$db_con = dba_open($dbfile, "r","db4");
	if(!$db_con){
		events("{failed}: DB open".basename($dbfile),$ID,0,__LINE__);
		return false;
	}
	
	$HyperCacheRules=HyperCacheRulesLoad();
	if(!isset($HyperCacheRules[$ID])){
		if($GLOBALS["HYPER_CACHE_VERBOSE"]){events("No rule for ID $ID!",0,2,__LINE__);}
		return;}
	
	$FileTypesArray=unserialize($HyperCacheRules[$ID]["FileTypes"]);
	if(count($FileTypesArray)==0){
		events("No Files type defined, aborting",$ID,0,__LINE__);
		return;
	}
	

	$urikey=dba_firstkey($db_con);
	$ARRAY_SAVE=array();
	
	$c=0;
	
	$HyPerCacheClass=new HyperCache();
	
	while($urikey !=false){
		
		
		$FileType=dba_fetch($urikey,$db_con);
		if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "HyperCacheScanDBFile::[$FileType]: $urikey\n";}

		if($FileType=="NONE"){
			$FileType=HyperCacheGetMimeType($urikey,$ID);
			if($FileType==null){$urikey=dba_nextkey($db_con); continue; }
			$ARRAY_SAVE[$urikey]=$FileType;			
			
		}
		
		if(!isset($FileTypesArray[$FileType])){
			if(!$HyPerCacheClass->ChecksOtherRules($urikey,$FileType,$ID)){
				
				if($GLOBALS["HYPER_CACHE_VERBOSE"]){events("$urikey $FileType No match...",$ID,3,__LINE__);}
				$urikey=dba_nextkey($db_con);
				continue;
			}
		}
		
		
		$TargetFile=HyperCacheScanBuildLocalPath($urikey,$ID);
		$FullTargetFile=$GLOBALS["HyperCacheStoragePath"]."/".$TargetFile;
		
		
		if($GLOBALS["HYPER_CACHE_VERBOSE"]){events("HyperCacheScanDBFile::Local file: $FullTargetFile",$ID,3,__LINE__);}
		if(is_file($FullTargetFile)){
			$urikey=dba_nextkey($db_con);
			continue;
		}
		events("Downloading $urikey [$FileType]",$ID,2,__LINE__);
		HyperCacheScanDownload($urikey,$TargetFile,$ID,$FileType);
		$urikey=dba_nextkey($db_con);
		
	}
	
	
	
	@dba_close($db_con);
	HyperCacheScanDBFile_setbulk($dbfile,$ARRAY_SAVE);
	
	
	
	
}

function HyperCacheScanDownload($urikey,$TargetFile,$ID,$FileType){
	if(!isset($GLOBALS["SIZE_DOWNLOADED"])){$GLOBALS["SIZE_DOWNLOADED"]=0;}
	if(!isset($GLOBALS["FAILED_DOWNLOADED"])){$GLOBALS["FAILED_DOWNLOADED"]=0;}
	if(!is_numeric($GLOBALS["SIZE_DOWNLOADED"])){$GLOBALS["SIZE_DOWNLOADED"]=0;}
	if(!is_numeric($GLOBALS["FAILED_DOWNLOADED"])){$GLOBALS["FAILED_DOWNLOADED"]=0;}
	$curl=new ccurl($urikey);
	$FullTarGetPath=$GLOBALS["HyperCacheStoragePath"]."/".$TargetFile;
	$GLOBALS["HITS"]++;
	$parse_url=parse_url($urikey);
	$hostname=$parse_url["host"];
	$OriginalFile=basename($parse_url["path"]);
	
	
	$t=time();
	events("{downloading} $OriginalFile {from} $hostname",$ID,3,__LINE__);
	if(!$curl->GetFile($FullTarGetPath)){
		events("HyperCacheScanDownload:: Download failed with error $curl->error",$ID,2,__LINE__);
		$GLOBALS["FAILED_DOWNLOADED"]++;
		return false;
	}
	
	$size=@filesize($FullTarGetPath);
	$MD5File=md5_file($FullTarGetPath);
	
	$sizeLog=FormatBytes($size/1024);
	events("$hostname: $OriginalFile ($sizeLog) {took}: ".distanceOfTimeInWords($t,time()),$ID,2,__LINE__);
	$GLOBALS["SIZE_DOWNLOADED"]=$GLOBALS["SIZE_DOWNLOADED"]+$size;
	
	$path=HyperCacheMD5File_get($MD5File);
	
	if($path<>null){
		if(!HyperCacheRetranslation_set($urikey,$MD5File,$FileType,$path)){
			$GLOBALS["FAILED_DOWNLOADED"]++;
			return;
		}
		return;
	}
	
	
	if(!HyperCacheMD5File_set($MD5File,$TargetFile,$FileType,$size,$OriginalFile)){
		$GLOBALS["FAILED_DOWNLOADED"]++;
		return;
	}
	
	if(!HyperCacheRetranslation_set($urikey,$MD5File,$FileType,$TargetFile)){
		$GLOBALS["FAILED_DOWNLOADED"]++;
		return;
	}

	return true;
	
}


function HyperCacheGetMimeType($uri,$ID){
	$curl=new ccurl($uri);
	if(!$curl->GetHeads()){
		events("headers {failed} $uri Error number: $curl->CURLINFO_HTTP_CODE",$ID,1,__LINE__);
		return "NONE_$curl->CURLINFO_HTTP_CODE";
	}
	
	if($curl->CURLINFO_HTTP_CODE==501){return "NONE_501"; }
	$content_type=$curl->CURL_ALL_INFOS["content_type"];
	if(strpos($content_type, ";")>0){
		$tbl=explode(";",$content_type);
		$content_type=trim($tbl[0]);
	}
	return $content_type;
	
	
}


function ifAlreadyDownloaded($uri){
	$fam=new familysite();
	$parse_url=parse_url($uri);
	$hostname=$parse_url["host"];
	
	
	$familysite=$fam->GetFamilySites($hostname);
	$dbfile="{$GLOBALS["HyperCacheStoragePath"]}/cache.db";
	if(!is_file($dbfile)){
		events("ifAlreadyDownloaded:: $dbfile no such file...");
		return false;
	}

	
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return false; }
	
	
	if(@dba_exists($uri,$db_con)){
			$array=unserialize(dba_fetch($uri,$db_con));
			$filepath=$array["filepath"];
			if(is_file("{$GLOBALS["HyperCacheStoragePath"]}/$filepath")){
				$filesize=$array["filesize"];
				if($filesize==@filesize("{$GLOBALS["HyperCacheStoragePath"]}/$filepath")){
					events("ifAlreadyDownloaded:: {$GLOBALS["HyperCacheStoragePath"]}/$filepath already exists");
					@dba_close($db_con);
					return true;
				}
			}
	}else{
		events("ifAlreadyDownloaded:: $uri doesn't exists...");
	}
	
		@dba_close($db_con);
		return false;
	
	
}




function events($text,$RULEID,$type,$line){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	
	if(trim($text)==null){return;}
	$text=str_replace(";", ", ", $text);
	
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/HyperCache-downloader.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["HYPER_CACHE_VERBOSE"]){echo "$date $text`\n";}
	@fwrite($f, "$date;$pid;$RULEID;$type;$line;$text\n");
	@fclose($f);
	@chown($logFile,"squid");
}

function buildRules(){
	$dbfile="/usr/share/squid3/HyperCacheRules.db";
	@unlink($dbfile);
	if(!is_file($dbfile)){
		try {
			events("analyze:: Creating $dbfile database"); $db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {$error=$e->getMessage(); events("analyze::FATAL ERROR $error on $dbfile");}
			@dba_close($db_desttmp);
	
	}
	if(!is_file($dbfile)){return;}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("analyze:: FATAL!!!::$dbfile, unable to open"); return false; }
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches","MarkToDelete","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MarkToDelete` smallint(1) NOT NULL DEFAULT 0, ADD INDEX(MarkToDelete)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$results=$q->QUERY_SQL("SELECT * FROM artica_caches WHERE enabled=1 AND MarkToDelete=0");
	while ($ligne = mysql_fetch_assoc($results)) {
		$sitename=$ligne["sitename"];
		$ID=$ligne["ID"];
		$Mimes=$ligne["FileTypes"];
		$MaxSizeBytes=$ligne["MaxSizeBytes"];
		echo "Building rule $ID $sitename {$MaxSizeBytes}Bytes\n";
		@dba_replace($ID,serialize($ligne),$db_con);
	}
	
	@dba_close($db_con);
	@chown($dbfile,"squid");
	buildRules_whitelists();
	buildRules_mirror();
	
	
}


function DeleteRules($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$aspid){
		$TimeExec=$unix->file_time_min($pidtime);
		if($TimeExec<240){return;}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	
	if(!isset($GLOBALS["HyperCacheStoragePath"])){
		$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
		if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	}
	
	$rm=$unix->find_program("rm");
	$q=new mysql_squid_builder();
	$RootPath=$GLOBALS["HyperCacheStoragePath"];
	
	$results=$q->QUERY_SQL("SELECT * FROM artica_caches_mirror WHERE ToDelete=1");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$Directory=$unix->shellEscapeChars("$RootPath/mirror/{$ligne["sitename"]}");
		if(is_dir($Directory)){
			if($GLOBALS["VERBOSE"]){echo "Remove $Directory\n";}
			shell_exec("$rm -rf $Directory");
		}
		$q->QUERY_SQL("DELETE FROM artica_caches_mirror WHERE ID=$ID");
		
		
	}
	
	
	$results=$q->QUERY_SQL("SELECT ID FROM artica_caches WHERE MarkToDelete=1");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$Directory="$RootPath/$ID";
		if(is_dir($Directory)){ 
			if($GLOBALS["VERBOSE"]){echo "Remove $Directory\n";}
			shell_exec("$rm -rf $Directory");
		}
		
		$DirFiles=$unix->DirFiles("/usr/share/squid3","HyperCacheQueue-.*?-$ID\.db$");
		while (list ($database, $none) = each ($DirFiles) ){
			if($GLOBALS["VERBOSE"]){echo "Remove $database\n";}
			@unlink($database);
		}
		
		$q->QUERY_SQL("DELETE FROM artica_caches WHERE ID=$ID");
	}
	
	
	HyperCacheMD5File_clean();
	HyperCacheRetranslation_scan();
		
	
	
}


function GetRulesSizes(){
	$unix=new unix();
	$sock=new sockets();
	
	$fileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$TimeExec=$unix->file_time_min($fileTime);
	if($TimeExec<20){
		events("GetRulesSizes:: Require 20mn, current {$TimeExec}Mn",0,2,__LINE__);
		return;
	}
	
	@unlink($fileTime);
	@file_put_contents($fileTime, time());
	
	if(!isset($GLOBALS["HyperCacheStoragePath"])){
		$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
		if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	}
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches","MarkToDelete","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MarkToDelete` smallint(1) NOT NULL DEFAULT 0, ADD INDEX(MarkToDelete)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_caches_sizes` (
		`ruleid` BIGINT UNSIGNED,
		`sizebytes` BIGINT UNSIGNED,
		`sitename` VARCHAR( 128 ) NOT NULL,
		 KEY `ruleid` (`ruleid`),
		 KEY `sitename` (`sitename`)
		)  ENGINE = MYISAM;
			";
	
	$q->QUERY_SQL($sql);
	
	
	$results=$q->QUERY_SQL("SELECT ID FROM artica_caches WHERE enabled=1 AND MarkToDelete=0");
	
	$RootPath=$GLOBALS["HyperCacheStoragePath"];
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$RULES[$ID]=0;
		$Directory="$RootPath/$ID";
		$Dirs=$unix->dirdir($Directory);
		while (list ($SubDir, $none) = each ($Dirs) ){
			$domain=basename($SubDir);
			$size=$unix->DIRSIZE_BYTES($SubDir);
			$RULES[$ID]=$RULES[$ID]+$size;
			$f[]="('$ID','$size','$domain')";
		}
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE artica_caches_sizes");
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO artica_caches_sizes (`ruleid`,`sizebytes`,`sitename`) VALUES ".@implode(",", $f));
		while (list ($ID, $size) = each ($RULES) ){
			$q->QUERY_SQL("UPDATE artica_caches SET `foldersize`='$size' WHERE ID='$ID'");
			
		}
	}
	
	
}


function buildRules_whitelists(){
	$dbfile="/usr/share/squid3/HyperCacheRules_wl.db";
	@unlink($dbfile);
	if(!is_file($dbfile)){
		try {
			events("Creating $dbfile database",0,2,__LINE__); 
			$db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {$error=$e->getMessage(); 
			events("FATAL ERROR $error on $dbfile",0,0,__LINE__);}
			@dba_close($db_desttmp);

	}
	if(!is_file($dbfile)){return;}

	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("FATAL ERROR $error on $dbfile",0,0,__LINE__);return false; }

	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM artica_caches_wl WHERE enabled=1");
	while ($ligne = mysql_fetch_assoc($results)) {

		$sitename=$ligne["sitename"];
		$ID=$ligne["ID"];
		$Mimes=$ligne["FileTypes"];
		$MaxSizeBytes=$ligne["MaxSizeBytes"];
		echo "Building whitelist rule $ID $sitename\n";
		@dba_replace($sitename,"NONE",$db_con);
	}

	@dba_close($db_con);
	@chown($dbfile,"squid");
}

function buildRules_mirror(){
	$dbfile="/usr/share/squid3/HyperCacheRules_mirror.db";
	@unlink($dbfile);
	if(!is_file($dbfile)){
		try {
			events("Creating $dbfile database",0,2,__LINE__);
			$db_desttmp = @dba_open($dbfile, "c","db4"); }
			catch (Exception $e) {$error=$e->getMessage(); events("analyze::FATAL ERROR $error on $dbfile");}
			@dba_close($db_desttmp);
	
	}
	if(!is_file($dbfile)){return;}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){
		events("FATAL!!!::$dbfile, unable to open",0,0,__LINE__);
		return false; }
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM artica_caches_mirror WHERE enabled=1");
	while ($ligne = mysql_fetch_assoc($results)) {
		$sitename=$ligne["sitename"];
		$ID=$ligne["ID"];
		echo "Building mirror rule $ID $sitename\n";
		@dba_replace($sitename,"NONE",$db_con);
	}
	
	@dba_close($db_con);
	@chown($dbfile,"squid");
}



function reconfigure(){
	$unix=new unix();
	$sock=new sockets();
	$SquidEnforceRules=intval($sock->GET_INFO("SquidEnforceRules"));
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($SquidEnforceRules==1){
		build_progress("{building_service}",10);
		system("$php /usr/share/artica-postfix/exec.initslapd.php --hypercache-web");
		build_progress("{building_rules}",20);
		buildRules();
		build_progress("{removing_old_rules}",20);
		DeleteRules();
		build_progress("{checking_proxy_service}",30);
		if(!IsClientInProxy()){
			build_progress("{reconfiguring_proxy_service}",30);
			$sock->SET_INFO("UfdbUseArticaClient", 1);
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		}
		
		build_progress("{reloading_web_service}",50);
		system("$php /usr/share/artica-postfix/exec.HyperCacheWeb.php --reload");
		build_progress("{reloading_proxy_plugins}",80);
		system("$php /usr/share/artica-postfix/exec.ufdbclient.reload.php");
		build_progress("{please_wait_restarting_artica_status}",90);
		system("/etc/init.d/artica-status restart");
		
		build_progress("{done}",100);
		
	}else{
		build_progress("{stopping_web_service}",50);
		system("$php /usr/share/artica-postfix/exec.HyperCacheWeb.php --stop");
		build_progress("{please_wait_restarting_artica_status}",90);
		system("/etc/init.d/artica-status restart");
		build_progress("{done}",100);
	}
	
	
}

function IsClientInProxy(){

	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^url_rewrite_program.*?ufdbgclient\.php#", $line)){return true; }

	}
}




function HyperCacheMirror_pid($pidpath){
	$f=explode("\n",@file_get_contents($pidpath));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^PID=([0-9]+)#", $line,$re)){continue;}
		return $re[1];
	}
	
}

function HyperCacheMirror_single($ID){
	events("Scrapping rule ID $ID",0,2,__LINE__);
	HyperCacheMirror($ID);
	
}

function HyperCacheMirror($JustID=0){
	$unix=new unix();
	
	$httrack=$unix->find_program("httrack");
	if(!is_file($httrack)){
		if($GLOBALS["VERBOSE"]){echo "httrack no such binary\n";}
		return;}
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches_mirror","ToDelete")){
		$sql="ALTER TABLE `artica_caches_mirror` ADD `ToDelete` SMALLINT(1) NOT NULL DEFAULT '0',ADD INDEX(`ToDelete`)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			squid_admin_enforce(1, "Fatal: MySQL error", $q->mysql_error,__FILE__,__LINE__);
			return;
		}
		
	}
	
	if(!$q->FIELD_EXISTS("artica_caches_mirror","RunEvents")){
		$sql="ALTER TABLE `artica_caches_mirror` ADD `RunEvents` TEXT";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			squid_admin_enforce(1, "Fatal: MySQL error", $q->mysql_error,__FILE__,__LINE__);
			return;
		}
	
	}	
	
	$nice=EXEC_NICE();
	$sql="SELECT * FROM artica_caches_mirror WHERE enabled=1 AND `ToDelete`=0";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_enforce(1, "Fatal: MySQL error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	if(!isset($GLOBALS["HyperCacheStoragePath"])){
		$sock=new sockets();
		$GLOBALS["HyperCacheStoragePath"]=$sock->GET_INFO("HyperCacheStoragePath");
		if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	}
	
	
	$t1=time();
	$count=0;
	if(mysql_num_rows($results)==0){return;}
	
	
	$proxyport=$unix->squid_internal_port();
	$HyperCache=new HyperCache();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($JustID>0){
			if($ligne["ID"]<>$JustID){
				events("Scrapping rule ID {$ligne["ID"]} !skipped",0,2,__LINE__);
				continue;}
		}
		$t=time();
		$count++;
		$sitename=$ligne["sitename"];
		
		$sitename_path=$HyperCache->HyperCacheUriToHostname($sitename);
		$workingdir=$GLOBALS["HyperCacheStoragePath"]."/mirror/$sitename_path";
		$TimeExec=$ligne["TimeExec"];
		$TimeExecLast=$unix->file_time_min("$workingdir/TimeExec");
		if(!$GLOBALS["FORCE"]){
			events("Scrapping $sitename require {$TimeExec}mn, current {$TimeExecLast}Mn",0,2,__LINE__);
			if($TimeExecLast<$TimeExec){continue;}
		}
		
		events("Scrapping rule ID {$ligne["ID"]} for $sitename",0,2,__LINE__);
		
		$minrate=$ligne["minrate"];
		$maxfilesize=$ligne["maxfilesize"];
		$maxsitesize=$ligne["maxsitesize"];
		$maxfilesize=$maxfilesize*1000;
		$maxsitesize=$maxsitesize*1000;
		$minrate=$minrate*1000;
		$update=null;
		$resultsCMD=array();
		
		$pidpath="{$GLOBALS["HyperCacheStoragePath"]}/mirror/$sitename_path/hts-in_progress.lock";
		if(!is_dir($workingdir)){@mkdir($workingdir,0755,true); }
		@chown("{$GLOBALS["HyperCacheStoragePath"]}/mirror", "squid");
		@chgrp("{$GLOBALS["HyperCacheStoragePath"]}/mirror", "squid");
		@chown("{$GLOBALS["HyperCacheStoragePath"]}/mirror/$sitename_path", "squid");
		@chgrp("{$GLOBALS["HyperCacheStoragePath"]}/mirror/$sitename_path", "squid");	
		if(is_file($pidpath)){
			$PID=HyperCacheMirror_pid($pidpath);
			if($unix->process_exists($PID)){
				events("Scrapping rule ID {$ligne["ID"]} for $sitename Process $PID already running since ".$unix->PROCESS_TIME_INT($PID),0,2,__LINE__);
				continue;
			}
			
		}	
		
		@file_put_contents("$workingdir/TimeExec", time());
		
		if(is_file("$workingdir/hts-cache")){$update=" --update";}
		$cmdline=array();
		$cmdline[]="$httrack \"$sitename\" --quiet$update -%U squid --proxy 127.0.0.1:$proxyport";
		$cmdline[]="--stay-on-same-domain -u2 -C1 -I0 -N100 --robots=0 --max-files=$maxfilesize";
		$cmdline[]="--max-size=$maxsitesize";
		$cmdline[]="-O \"$workingdir\" 2>&1";
		
		squid_admin_enforce(2,"Scrapping $sitename using proxy 127.0.0.1:$proxyport...",null,__FILE__,__LINE__);
		$cmd=@implode(" ", $cmdline);
		
		if($GLOBALS["VERBOSE"]){echo"$cmd\n";}
		exec($cmd,$resultsCMD);
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $resultsCMD);}
		$dirsize=$unix->DIRSIZE_BYTES($workingdir);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$dirsizeText=round((($dirsize/1024)/1000),2);
		squid_admin_enforce(2,"Mirror on $sitename done took $took size=$dirsizeText MB",null,__FILE__,__LINE__);
		$logs=mysql_escape_string2(@file_get_contents("$workingdir/hts-log.txt"));
		
		
		$q->QUERY_SQL("UPDATE artica_caches_mirror SET 
				size='$dirsize',`RunEvents`='$logs' WHERE ID={$ligne["ID"]}","artica_backup");
			if(!$q->ok){squid_admin_enforce(1,"MySQL error",$q->mysql_error,__FILE__,__LINE__); }
		
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	squid_admin_enforce(2,"$count web site(s) scrapped took $took",null,__FILE__,__LINE__);
	
	
	
}

function HyperCacheLogs(){
	HyperCacheSizeLog("/usr/share/squid3/HyperCacheSizeLog.db");
	$q=new mysql_squid_builder();
	
	$unix=new unix();
	$files=$unix->DirFiles("/usr/share/squid3","[0-9]+_HyperCacheSizeLog\.db");
	while (list ($num, $filename) = each ($files) ){
		$filepath="/usr/share/squid3/$filename";
		if(!is_file($filepath)){continue;}
		$time=$q->TIME_FROM_DAY_TABLE($filename);
		echo "$filepath $time ".date("Y-m-d")."\n";
		
	}
	
}

function HyperCacheSizeLog($dbfile){
	$dbfile="/usr/share/squid3/HyperCacheSizeLog.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){return false;}
	
	$mainkey=dba_firstkey($db_con);
	while($mainkey !=false){
		$val=0;
		
		$array=unserialize(dba_fetch($mainkey,$db_con));
		$zDate=$mainkey;
		$mainkey=dba_nextkey($db_con);
		if(!is_array($data)){continue;}
				
		}
		
		dba_close($db_con);
		$this->ok=true;
		return $wwwUH;
		
		

}


