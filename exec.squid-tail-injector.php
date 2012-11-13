<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if($argv[1]=="--unveiltech"){unveiltech();die();}
if($argv[1]=="--youtube"){youtube();die();}
if($argv[1]=="--users-agents"){useragents();die();}
if($argv[1]=="--users-size"){ParseUsersSize();die();}
if($argv[1]=="--squid"){ParseSquidLogMain();die();}
if($argv[1]=="--nudity"){nudityScan();die();}





	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		events("Already executed pid $oldpid since {$time}mn-> DIE");
		
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid since {$time}mn\n";}
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){events("DIE, Artica Statistics are disabled");die();}
	if($EnableRemoteSyslogStatsAppliance==1){events("DIE, using remote statistics Appliance with Syslog..");die();}
	events("Executed pid $mypid");

	events("Execute ParseSquidLogMain()");
	ParseSquidLogMain();
	events("Execute ParseSquidLogMainError()");
	ParseSquidLogMainError();
	events("Execute ParseUserAuth()");
	ParseUserAuth();
	events("Execute youtube()");
	youtube();
	events("Execute useragents()");
	useragents();
	events("Execute ParseUsersSize()");
	ParseUsersSize();
	events("Execute nudityScan()");
	nudityScan();
	events("FINISH....");
//EnableWebProxyStatsAppliance

// /var/log/artica-postfix/dansguardian-stats2
//$q=new mysql_squid_builder();
//$q->CheckTables();
//if($q->MysqlFailed){events_tail("squid-injector:: Mysql connection failed, aborting.... Line: ".__LINE__);die();}

function ParseUsersSize(){
	$f=array();
	$unix=new unix();
	$hostname=$unix->hostname_g();		
	$php5=$unix->LOCATE_PHP5_BIN();
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			ufdbguard_admin_events("Fatal:$hostname Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}	
	}

	$q=new mysql_squid_builder();
	$q->CreateUserSizeRTTTable();
	if(!$q->TABLE_EXISTS("UserSizeRTT")){
		ufdbguard_admin_events("Fatal:$hostname UserSizeRTT no such table, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	if (!$handle = opendir("/var/log/artica-postfix/squid-usersize")) { @mkdir("/var/log/artica-postfix/squid-usersize",0755,true);}
	if (!$handle = opendir("/var/log/artica-postfix/squid-usersize")) { 
		ufdbguard_admin_events("Fatal:$hostname /var/log/artica-postfix/squid-usersize no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	

	$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`uid`,`zdate`,`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`) VALUES";
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/var/log/artica-postfix/squid-usersize/$filename";
				$countDeFiles++;	
				$account=0;
				$array=unserialize(@file_get_contents($targetFile));
				if(!is_array($array)){@unlink($targetFile);continue;}
				
				$time=$array["TIME"];
				$md5=$array["MD5"];
				if($md5==null){@unlink($targetFile);continue;}
				if(!is_numeric($time)){@unlink($targetFile);continue;}
				if($time==0){@unlink($targetFile);continue;}
				$zdate=date("Y-m-d H:i:s",$time);
				
				$md5=md5($md5.$time);
				$uid=$array["uid"];
				if($uid=="-"){$uid=null;}
				$ipaddr=$array["IP"];
				$MAC=$array["MAC"];
				$hostname=$array["HOSTNAME"];
				$UserAgent=$array["UGNT"];
				if(strlen($UserAgent)<2){$UserAgent=null;}
				$size=$array["SIZE"];
				if($size==0){@unlink($targetFile);continue;}
				if($hostname==null){$hostname=GetComputerName($ipaddr);}
				if(!is_numeric($account)){$account=0;}
				if($MAC<>null){if($uid==null){$uid=$q->UID_FROM_MAC($MAC);}}
				if(strlen($UserAgent)<3){$UserAgent=null;}
				if(strlen($uid)<3){$uid=null;}
				if($GLOBALS["VERBOSE"]){echo "('$md5','$uid','$zdate','$ipaddr','$hostname','$account','$MAC','$UserAgent','$size')\n";}
				$f[]="('$md5','$uid','$zdate','$ipaddr','$hostname','$account','$MAC','$UserAgent','$size')";
				@unlink($targetFile);
		}
		if(count($f)>0){
			$q->QUERY_SQL("$prefix ".@implode(",", $f));
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.quotasbuild.php");
			if(!$q->ok){
				ufdbguard_admin_events("Fatal:$hostname $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
			}		
		}
				
	events("Closing... /var/log/artica-postfix/squid-usersize/ ($countDeFiles files scanned)");
}

function GetComputerName($ip){
		if(isset($GLOBALS["resvip"][$ip])){return $GLOBALS["resvip"][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"]=$name;
		return $name;
		}
		
		
function nudityScan(){
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			writelogs_squid("Fatal:$hostname Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}	
	}
	$SquidNuditScanParams=unserialize(base64_decode(@file_get_contents("/etc/squid3/SquidNudityScanParams")));
	$iPicScanVal = $SquidNuditScanParams['picscanval'];
	if(!is_numeric($iPicScanVal)){$iPicScanVal=70;}
	$iPicScanVal=intval($iPicScanVal);
	if($iPicScanVal>99){$iPicScanVal=99;}	
	
	if(!is_dir("/var/log/squid/nudity")){return;}
	if (!$handle = opendir("/var/log/squid/nudity")){return;}
	
	$countDeFiles=0;
	$FF=array();
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/var/log/squid/nudity/$filename";
				$countDeFiles++;
				$array=unserialize(@file_get_contents($targetFile));
				$zmd5=md5(serialize($array));
				$uid=addslashes($array["LOGIN"]);
				$ipaddr=$array["IPADDR"];
				$MAC=addslashes($array["MAC"]);
				$hostname=addslashes($array["HOST"]);
				$uri=addslashes($array["URI"]);
				$servername=addslashes($array["RHOST"]);
				$POURC=$array["POURC"];
				$time=filemtime($targetFile);
				$tablePrefix=date("YmdH",$time);
				$zDate=date("Y-m-d H:i:s");
				$sqline="('$zmd5','$servername','$uri','$ipaddr','$hostname','$zDate','$uid','$MAC','$POURC')";
				$FF[$tablePrefix][]=$sqline;
				@unlink($targetFile);
				
		}

		if(count($FF)==0){return;}
		
		$q=new mysql_squid_builder();
		while (list ($tablePrefix, $f) = each ($FF) ){
			if(count($f)>0){
				if($q->TableNudityHour($tablePrefix)){
					$tablename="znudehour_$tablePrefix";
					$prefix="INSERT IGNORE INTO $tablename (zMD5,sitename,uri,ipaddr,hostname,zDate,uid,MAC,POURC) VALUES ".@implode(",", $f);
					$q->QUERY_SQL($prefix);
					if(!$q->ok){echo $q->mysql_error;}
					}
				}
				
			}
		
}
	


function ParseUserAuth(){
	
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			writelogs_squid("Fatal:$hostname Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}	
	}
	
		$unix=new unix();
		$hostname=$unix->hostname_g();	
		
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$cmdNmap="$nohup $php5 ".dirname(__FILE__)."/exec.nmapscan.php --scan-squid >/dev/null 2>&1 &";		
		
		if (!$handle = opendir("/var/log/artica-postfix/squid-users")) {@mkdir("/var/log/artica-postfix/squid-users",0755,true);die();}
		
			if(systemMaxOverloaded()){
				events("Fatal:$hostname VERY Overloaded system ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), die(); on Line: ".__LINE__);
				writelogs_squid("Fatal:$hostname VERY Overloaded system ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), die();",__FUNCTION__,__FILE__,__LINE__,"stats");
				shell_exec($cmdNmap);
				return;
			}
	$countDeFiles=0;
	
	
	$prefix="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ";
	$f=array();
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/var/log/artica-postfix/squid-users/$filename";
				$countDeFiles++;
				$array=unserialize(@file_get_contents($targetFile));
				while (list ($key, $value) = each ($array) ){$array[$key]=trim($value);}
				if(trim($array["MAC"])==null){$array["MAC"]=GetMacFromIP($array["IP"]);$array["MD5"]=md5("'{$array["MD5"]}','{$array["MAC"]}','{$array["IP"]}','{$array["USER"]}','{$array["HOSTNAME"]}','{$array["USERAGENT"]}'");}
				$f[]="('{$array["MD5"]}','{$array["MAC"]}','{$array["IP"]}','{$array["USER"]}','{$array["HOSTNAME"]}','{$array["USERAGENT"]}')";
				@unlink($targetFile);
		}
	

	shell_exec($cmdNmap);
	if(count($f)>0){$q=new mysql_squid_builder();$q->QUERY_SQL($prefix.@implode(",", $f));}
}

function useragents(){
	$f=array();
	$q=new mysql_squid_builder();	
	if(!$q->TABLE_EXISTS("UserAgents")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("UserAgents")){ufdbguard_admin_events("Fatal, UserAgents no such table", __FUNCTION__, __FILE__, __LINE__, "stats");return;}

if (!$handle = opendir("/var/log/artica-postfix/squid-userAgent")) {@mkdir("/var/log/artica-postfix/squid-userAgent",0755,true);die();}
$c=0;
while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$c++;
		$targetFile="/var/log/artica-postfix/squid-userAgent/$filename";
		$countDeFiles++;
		$pattern=trim(@file_get_contents($targetFile));
		if(strlen($pattern)<3){@unlink($targetFile);continue;}
		
		$pattern=addslashes($pattern);
		$f[]="('$pattern')";
		@unlink($targetFile);
}


	

	if(count($f)>0){
		$sql="INSERT IGNORE INTO UserAgents (pattern) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			ufdbguard_admin_events("Fatal for  ".count($f)." files, $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "stats");
		}
	}
	events("ParseUserAuth():: FINISH.... $c files");
}


function ParseSquidLogMain_sql_toarray($filename){
	$data=trim(@file_get_contents($filename));
	$q=new mysql_squid_builder();
	$array=explode(",", $data);
	while (list ($num, $val) = each ($array) ){
		$array[$num]=str_replace("'", "", $array[$num]);
		$array[$num]=addslashes($array[$num]);
	}
	$array[0]=str_replace("(", "", $array[0]);
	$array[13]=str_replace(")", "", $array[13]);
	
	$sitename=$array[0];
	$uri=$array[1];
	$TYPE=$array[2];
	$REASON=$array[3];
	$CLIENT=$array[4];
	$date=$array[5];
	$zMD5=$array[6];
	$site_IP=$array[7];
	$Country=$array[8];
	$size=$array[9];
	$username=$array[10];
	$cached=$array[11];
	$mac=$array[12];
	$hostname=$array[13];
	if(!is_numeric($cached)){return $data;}
	if($cached>1){return $data;}
	if(strlen($username)<3){$username=null;}
	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if($mac==null){$mac=GetMacFromIP($CLIENT);}
	if($username=="-"){$username=null;}
	if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
	if($hostname==null){$hostname=GetComputerName($CLIENT);}
	$line="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
	return $line;

}
function ParseSquidLogMain(){

$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$GLOBALS["EnableRemoteStatisticsAppliance"]=$EnableRemoteStatisticsAppliance;
$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
if(!isset($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
if(!isset($RemoteStatisticsApplianceSettings["SERVER"])){$RemoteStatisticsApplianceSettings["SERVER"]=null;}
if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
$unix=new unix();
$q=new mysql_squid_builder();
$hostname=$unix->hostname_g();
events("Open /var/log/artica-postfix/dansguardian-stats2");
if (!$handle = opendir("/var/log/artica-postfix/dansguardian-stats2")) {@mkdir("/var/log/artica-postfix/dansguardian-stats2",0755,true);die();}

$GLOBALS["WAIT-OVERLOAD-TIMEOUT"]=0;	
$c=0;
$countDeFiles=0;
	if(!$GLOBALS["VERBOSE"]){
			if(systemMaxOverloaded()){
				events("Fatal:$hostname VERY Overloaded system, die(); on Line: ".__LINE__);
				writelogs_squid("Fatal:$hostname VERY Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
	}
$array=array();
events("Starting loop for /var/log/artica-postfix/dansguardian-stats2");
while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/var/log/artica-postfix/dansguardian-stats2/$filename";
		$countDeFiles++;
		if($GLOBALS["WAIT-OVERLOAD-TIMEOUT"]>1000){events("Fatal: Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: after 1000 cycles, stopping after parsing $countDeFiles");return;}		
		usleep(500);
		
		$datasql=trim(@file_get_contents($targetFile));
		if(trim($datasql)==null){@unlink($targetFile);continue;}
		$tablehour="squidhour_". date("YmdH",filemtime($targetFile));
		$FINAL_ARRAY=@unserialize($datasql);
		if(!is_array($FINAL_ARRAY)){
			if(strpos($datasql, "')")>0){
				if($GLOBALS["VERBOSE"]){echo $datasql."\n";}
				$array["TABLES"][$tablehour][]=ParseSquidLogMain_sql_toarray($targetFile);
				@unlink($targetFile);
				$c++;
				if($c>800){inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;}
				continue;
			}else{
				events("Fatal... unlink $targetFile");
				@unlink($targetFile);
				continue;
			}	
		}
		
		
		$sitename=$FINAL_ARRAY["sitename"];
		$uri=$FINAL_ARRAY["uri"];
		$TYPE=$FINAL_ARRAY["TYPE"];
		$REASON=$FINAL_ARRAY["REASON"];
		$CLIENT=$FINAL_ARRAY["CLIENT"];
		$date=$FINAL_ARRAY["date"];
		$zMD5=$FINAL_ARRAY["zMD5"];
		$site_IP=$FINAL_ARRAY["site_IP"];
		$Country=$FINAL_ARRAY["Country"];
		$size=$FINAL_ARRAY["size"];
		$username=$FINAL_ARRAY["username"];
		$cached=$FINAL_ARRAY["cached"];
		$mac=$FINAL_ARRAY["mac"];
		$hostname=$FINAL_ARRAY["hostname"];	

		if(strlen($username)<3){$username=null;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if($mac==null){$mac=GetMacFromIP($CLIENT);}
		if($username=="-"){$username=null;}
		if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
		if($hostname==null){$hostname=GetComputerName($CLIENT);}		
		
		$sql="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
		events($sql);
		$array["TABLES"][$tablehour][]=$sql;
		
		
		
		
		
		@unlink($targetFile);
		if(system_is_overloaded(__FILE__)){
			$GLOBALS["WAIT-OVERLOAD-TIMEOUT"]++;
			events("Fatal: Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: injecting ". count($array["TABLES"]) ." events and sleeping 1 seconde ($countDeFiles files)...");
			inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;
			echo "Fatal: Overloaded system, sleeping 10 secondes...\n";
			sleep(1);
			continue;
		}
		
		$c++;
		$GLOBALS["WAIT-OVERLOAD-TIMEOUT"]=0;
		if($c>800){inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;}
			if(!$GLOBALS["VERBOSE"]){
				if(systemMaxOverloaded()){
					events("Fatal: Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: injecting ". count($array["TABLES"]) ." And stopping");
					inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;
					events("Fatal:$hostname VERY Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: die(); on Line: ".__LINE__);
					writelogs_squid("Fatal:$hostname VERY Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
					return;
				}
			}		
	
}
	if(count($array["TABLES"])>0){
		events("Injecting ". count($array["TABLES"]). " lines Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array($array["TABLES"]);
	}
	
	events("Closing... /var/log/artica-postfix/dansguardian-stats2 ($countDeFiles files scanned)");

}

function ParseSquidLogMainError(){
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2-errors")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/dansguardian-stats2-errors")) {return;}
	$unix=new unix();
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$c++;
		$targetFile="/var/log/artica-postfix/dansguardian-stats2-errors/$filename";
		$minutes=$unix->file_time_min($targetFile);
		events("ParseSquidLogMainError():: $filename {$minutes}Mn");
		if($minutes>2880){@unlink($targetFile);continue;}
		$array["TABLES"]=unserialize(@file_get_contents($targetFile));
		@unlink($targetFile);
		echo "Inject file $targetFile\n";
		events("Inject file $targetFile on Line: ".__LINE__);
		inject_array($array["TABLES"]);
		unset($array["TABLES"]);
	}
	events("ParseSquidLogMainError():: Done... ($c files)");
}

function youtube(){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("youtube_objects")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("youtube_objects")){echo "youtube_objects no such table\n";return;}
	
	$array_sql=array();
	
	@mkdir("/var/log/artica-postfix/youtube",0755,true);
	@mkdir("/var/log/artica-postfix/youtube-errors",0755,true);
	if (!$handle = opendir("/var/log/artica-postfix/youtube")) {return;}
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$c++;
		$targetFile="/var/log/artica-postfix/youtube/$filename";
		$array=unserialize(@file_get_contents($targetFile));
		$VIDEOID=$array["VIDEOID"];
		$clientip=$array["clientip"];
		$username=$array["username"];
		$time=$array["time"];
		$mac=$array["mac"];
		$hostname=$array["hostname"];
		if($username=="-"){$username=null;}
		if(strlen($username)<3){$username=null;}	
		
		
		
		if($mac==null){$mac=GetMacFromIP($clientip);}
		if($GLOBALS["VERBOSE"]){echo "$mac:: $VIDEOID -> \n";}	
		if(!youtube_infos($VIDEOID)){
			if($GLOBALS["VERBOSE"]){echo "youtube_infos:: $VIDEOID -> FAILED \n";}	
			continue;}
		$timeint=strtotime($time);
		$timeKey=date('YmdH');
		$account=0;
		if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
		$array_sql[$timeKey][]="('$time','$clientip','$hostname','$username','$mac','$account','$VIDEOID')";
		@unlink($targetFile);
		}

	if(count($array_sql)==0){
		if($GLOBALS["VERBOSE"]){
			echo "array_sql no rows...\n";
			return;
		}
		
	}
		while (list ($timeKey, $rows) = each ($array_sql) ){
			$q->check_youtube_hour($timeKey);
			$sql="INSERT INTO youtubehours_$timeKey (zDate,ipaddr,hostname,uid,MAC,account,youtubeid) VALUES ".
			@implode(",", $rows);
			
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, 'youtube');
				@file_put_contents("/var/log/artica-postfix/youtube-errors/".md5($sql), $sql);
			}
		}
		
	events("youtube():: Done... ($c files)");
}

function youtube_infos($VIDEOID){
	
	if(isset($GLOBALS["youtubeid"][$VIDEOID])){return true;}
	$uri="https://gdata.youtube.com/feeds/api/videos/$VIDEOID?v=2&alt=jsonc";
	if($GLOBALS["VERBOSE"]){echo "$VIDEOID:: $uri -> \n";}	
	$curl=new ccurl($uri);
	
	if(!$curl->GetFile("/tmp/jsonc.inc")){return false;}
	$infox=@file_get_contents("/tmp/jsonc.inc");
	$infos=json_decode($infox);
	$uploaded=$infos->data->uploaded;
	$title=$infos->data->title;	
	if($title==null){return false;}
	$category=$infos->data->category;
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
	
	if(!$q->ok){
		if(strpos("youtube_objects' doesn't exist", $q->mysql_error)>0){
			
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
		}
	}
	
	if($ligne["youtubeid"]<>null){
		$GLOBALS["youtubeid"][$VIDEOID]=true;
		if($GLOBALS["VERBOSE"]){echo "$VIDEOID already exists in table\n";}
		return true;
	}
	
	$tumbnail=$infos->data->thumbnail->sqDefault;
	$curl=new ccurl($tumbnail);
	$curl->GetFile("/tmp/thumbnail");
	$CATZ["Autos & Vehicles"]="automobile/cars";
	$CATZ["Film & Animation"]="movies";
	$CATZ["Gaming"]="games";
	$CATZ["Education"]="recreation/schools";
	$CATZ["Music"]="music";
	$CATZ["News & Politics"]="news";
	$CATZ["People & Blogs"]="hobby/pets";
	$CATZ["Science & Technology"]="sciences";
	$CATZ["Sports"]="recreation/sports";
	$CATZ["Travel & Events"]="recreation/travel";
	if(isset($CATZ[$category])){$category=$CATZ[$category];}
	
	$date=strtotime($uploaded);
	$zDate=date("Y-m-d H:i:s");
	$infox_enc=base64_encode($infox);
	$title=addslashes($title);
	$category=addslashes($category);
	$duration=$infos->data->duration;
	
	
	$thumbnail=addslashes(@file_get_contents("/tmp/thumbnail"));
	$sql="INSERT INTO youtube_objects (youtubeid,category,title,content,uploaded,duration,thumbnail) 
	VALUES('$VIDEOID','$category','$title','$infox_enc','$zDate','$duration','$thumbnail')";
	$q->QUERY_SQL($sql,"artica");
	if(!$q->ok){
		if(strpos("youtube_objects' doesn", " $q->mysql_error")>0){
			$q->CheckTables();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
		}
	}	
	if(!$q->ok){return false;}
	return true;
	
	
	
}



function unveiltech(){
	$deleteForce=false;
	if(is_file("/etc/artica-postfix/nounveicloud")){$deleteForce=true;}
	if(!is_dir("/var/log/artica-postfix/unveiltech")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/unveiltech")) {return;}
	$unix=new unix();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$targetFile="/var/log/artica-postfix/unveiltech/$filename";
		$minutes=$unix->file_time_min($targetFile);
		if($minutes>2880){@unlink($targetFile);continue;}
		if(!$deleteForce){
		//	unveiltech_SendSite(@file_get_contents($targetFile));
		}
		@unlink($targetFile);
	}
	
}




function unveiltech_SendSite($www){
		return;
		if($www==null){return;}
		$ch = curl_init();
		$uris="http://api.unveiltech.com/articapushed.php?apikey=GfNDdlP7AKvwUtVI&url=$www";
		if($GLOBALS["VERBOSE"]){echo "$uris\n";}
		curl_setopt($ch, CURLOPT_URL, $uris);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla"); 
		curl_setopt($ch, CURLOPT_POST, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));   
		$data = curl_exec($ch);
		if(preg_match("#<STOP>OK</STOP>#is", $data)){@file_put_contents("/etc/artica-postfix/nounveicloud", time());}
		curl_close($ch);
}

function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		events($cmd);
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		events("$ipaddr not found (".__LINE__.")");
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}



function inject_array($array){
	
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
		events("Injecting -> inject_array_remote() Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array_remote($array);
		return;
	}
	events("Injecting -> inject direct on Line: ".__LINE__);
	$q=new mysql_squid_builder();
	$q->CheckTables();
	if($q->MysqlFailed){events_tail("squid-injector:: Mysql connection failed, aborting.... Line: ".__LINE__);inject_failed($array);}
	
	while (list ($table, $contentArray) = each ($array) ){
		if(preg_match("#squidhour_([0-9]+)#",$table,$re)){$q->TablePrimaireHour($re[1]);}
		$prefixsql="INSERT IGNORE INTO $table (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`zDate`,`zMD5`,`remote_ip`,`country`,`QuerySize`,`uid`,`cached`,`MAC`,`hostname`) VALUES ";
		$sql="$prefixsql".@implode(",",$contentArray);
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "ERROR: $q->mysql_error\n";inject_failed($array);return;}
	}	
	
	
}


function inject_array_remote($array){
	$sock=new sockets();
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort=9000;}
	
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/squid.stats.listener.php";
	events("Injecting -> $uri on line:".__LINE__);
	$curl=new ccurl($uri,true);
	$f=base64_encode(serialize($array));
	$curl->parms["STATS_LINE"]=$f;
	$curl->parms["MYSSLPORT"]=$ArticaHttpsPort;
	if(!$curl->get()){
		inject_failed($array);
		events("Injecting -> FAILED ".$curl->error." on line:".__LINE__);
		echo "FAILED ".$curl->error."\n";
		return;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){return true;}	
	events("Injecting -> FAILED ".$curl->data." on line:".__LINE__);
	echo "FAILED ".$curl->data."\n";
	inject_failed($array);
}

function inject_failed($array){
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2-errors")){@mkdir("/var/log/artica-postfix/dansguardian-stats2-errors",0755,true);}
	$serialized=serialize($array);
	@file_put_contents("/var/log/artica-postfix/dansguardian-stats2-errors/".md5($serialized),$serialized);
	
}
function events($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
		$logFile=$GLOBALS["LOGFILE"];
		if($GLOBALS["VERBOSE"]){echo "$date [$pid]:".basename(__FILE__).": $text\n";}
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid]:".basename(__FILE__).": $text\n");
		@fclose($f);	
		}
