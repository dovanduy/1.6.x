<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$unix=new unix();

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." instances:".count($pids)."\n";}
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){continue;}
		echo "Starting......: ".date("H:i:s")." killing $pid\n";
		unix_system_kill_force($pid);
	}

}

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." dying\n";
	die();
}


if($argv[1]=="--import"){include_tpl_file($argv[2],$argv[3]);die();}
if($argv[1]=="--streamget"){streamget();die();}
if($argv[1]=="--notifs"){die();}
if($argv[1]=="--blocked"){die();}

if($argv[1]=="--errors"){die();}




$pid=getmypid();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
$GLOBALS["CLASS_UNIX"]=$unix;
if($unix->process_exists($pid)){
	if($pid<>$pid){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		events(basename(__FILE__).": Already executed $pid (since {$time}Mn).. aborting the process (line:  Line: ".__LINE__.")");
		events_tail("Already executed $pid (since {$time}Mn). aborting the process (line:  Line: ".__LINE__.")");
		if($time>120){
			events(basename(__FILE__).": killing $pid  (line:  Line: ".__LINE__.")");
			unix_system_kill_force($pid);
		}else{	
			die();
		}
	}
}

$t1=time();
file_put_contents($pidfile,$pid);
events(basename(__FILE__).": running $pid");
events_tail("running $pid");	
$nohup=$unix->find_program("nohup");




$t2=time();
$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);

events(basename(__FILE__).": finish in $distanceOfTimeInWords");
$mem=round(((memory_get_usage()/1024)/1000),2);
events_tail("finish in $distanceOfTimeInWords {$mem}MB");
die();	


function events($text){
		$date=@date("H:i:s");
		$pid=getmypid();
		$logFile=$_GET["LOGFILE"];
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["debug"]){echo "$pid $text\n";}
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}
		
function GetRuleName($filename){
	$tb=explode("\n", $filename);
	while (list ($index, $ligne) = each ($tb) ){
		if(preg_match("#^groupname.+?'(.+?)'#", $ligne,$re)){return $re[1];}
	}
}




function streamget(){
	
	$sock=new sockets();
	$unix=new unix();
	$SquidGuardStorageDir=$sock->GET_INFO("SquidGuardStorageDir");	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$hostname=$unix->FULL_HOSTNAME();
	$PREFIX="INSERT IGNORE INTO `youtubecache`(`filename`,`filesize`,`urlsrc`,`zDate`,`zMD5`,`proxyname`) VALUES ";
	$q=new mysql_squid_builder();
	if (!$handle = opendir($SquidGuardStorageDir)) {
		events_tail("streamget:: -> glob failed $SquidGuardStorageDir in Line: ".__LINE__);
		return;
	}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}		
		$fullFileName="$SquidGuardStorageDir/$filename";
		$time=null;
		if(strpos($filename, ".url")>0){continue;}
		if(strpos($filename, ".log")>0){continue;}
		$filesize=$unix->file_size($fullFileName);
		$time=filemtime($fullFileName);
		$zdate=date("Y-m-d H:i:s",$time);
		$url=null;
		if(is_file($fullFileName.".url")){$url=@file_get_contents($fullFileName.".url");}
		if($GLOBALS["VERBOSE"]){echo "\n\nFile:$fullFileName\nSize:$filesize\ndate:$zdate\nurl:$url\n";}
		$md5=md5($filename.$hostname);
		$f[]="('$fullFileName','$filesize','$url','$zdate','$md5','$hostname')";
	}
	
	if(count($f)>0){
		$sql=$PREFIX." ".@implode(",",$f);
		if($EnableRemoteStatisticsAppliance==0){
			$q->QUERY_SQL($sql);
				if(!$q->ok){
				events_tail("streamget:: Fatal $q->mysql_error");	
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "streamget_send_remote() with hostname $hostname\n";}
			streamget_send_remote($sql,$hostname);
		}
	}	
	
	
}

function _LoadStatisticsSettings(){
	if(isset($GLOBALS["REMOTE_SSERVER"])){return;}
	$sock=new sockets();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];		
}





function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("danguardian-injector", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}






function streamget_send_remote($sql,$hostname){
	_LoadStatisticsSettings();
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/squid.blocks.listener.php";
	$curl=new ccurl($uri,true);
	$f=base64_encode($sql);
	$curl->parms["STREAM_LINE"]=$f;
	$curl->parms["HOSTNAME"]=$hostname;
	events_tail("streamget_send_remote:: send ".strlen($sql)." bytes to `$uri`");
	if(!$curl->get()){events_tail("FAILED ".$curl->error);return;}
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){events_tail("streamget_send_remote():: SUCCESS...");
	return true;}	
	events_tail("streamget_send_remote():: FAILED ".$curl->data."...");
	

}






function include_tpl_file($path,$category){
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "UUID=NULL; Aborting";return;}
	if($category==null){echo "CATEGORY=NULL; Aborting";return;}				
	if(!is_file($path)){echo "$path no such file\n";return;}
	
	$q=new mysql_squid_builder();
	$q->CreateCategoryTable($category);
	$TableDest="category_".$q->category_transform_name($category);	
	$array=array();
	$f=@explode("\n",@file_get_contents($path));
	$count_websites=count($f);
	$i=0;$d=0;$group=0;
	$prefix="INSERT IGNORE INTO $TableDest (zmd5,zDate,category,pattern,uuid) VALUES";
	while (list ($index, $website) = each ($f) ){
		$i++;$d++;
		if($d>1000){$group=$group+$d;events_tail("include_tpl_file($category):: importing $group sites...");$d=0;}
		if($website==null){return;}
		$www=trim(strtolower($website));
		if(preg_match("#www\.(.+?)$#i",$www,$re)){$www=$re[1];}
		$md5=md5($www.$category);	
		if($array[$md5]){echo "$www already exists\n";continue;}
		$enabled=1;
		$sql_add[]="('$md5',NOW(),'$category','$www','$uuid')";		
		$array[$md5]=true;
		if($GLOBALS["SIMULATE"]){echo "$i/$count_websites: $sql_add\n";continue;}
		if(count($sql_add)>500){
			$sql=$prefix.@implode(",",$sql_add);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "$i/$count_websites Failed: $www\n";}else{echo "$i/$count_websites Success: $www\n";}
			$sql_add=array();
		}
	}
	
if(count($sql_add)>0){
			$sql=$prefix.@implode(",",$sql_add);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "$i/$count_websites Failed: $www\n";}else{echo "$i/$count_websites Success: $www\n";}
			$sql_add=array();
		}	
	
	
echo " -------------------------------------------------\n";	
echo count($array)." websites done\n";
echo " -------------------------------------------------\n";	
}



function events_tail($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		//if($GLOBALS["VERBOSE"]){echo "$text\n";}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}




		
		
		
?>