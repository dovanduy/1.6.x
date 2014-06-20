<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");

//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}

if(count($argv)>0){
	if(isset($argv[1])){
		if($argv[1]=="--tables-primaires"){parse_tables_primaires();die();}
		if($argv[1]=="--wakeup"){Wakeup();die();}
		if($argv[1]=="--caches"){parse_tables_cache_primaires();die();}
		
		
	}
}



$logFile="/var/log/squid/logfile_daemon.debug";
@chmod($logFile, 0755);
@chown($logFile,"squid");
parse_realtime_events();


function parse_tables_primaires(){
	$unix=new unix();
	
	
	$unix->chown_func("squid","squid","/var/log/squid/mysql-rttime");
	$TimePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=@file_get_contents($TimePID);
	
	
	if($unix->process_exists($pid)){
		$timePid=$unix->PROCCESS_TIME_MIN($pid);
		if($timePid>5){
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}else{
			if($GLOBALS["VERBOSE"]){echo "Already running PID $pid since {$timePid}mn";}
			die();
		}
	
	}
	@file_put_contents($TimePID, getmypid());
	
	if (!$handle = opendir("/var/log/squid/mysql-rttime")){return;}
	$q=new mysql_squid_builder();
	$q->TablePrimaireHour(date("YmdH"));
	
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="/var/log/squid/mysql-rttime/$filename";	
		events("parse_tables_primaires():: Scanning $filepath");
		if(!preg_match("#^squidhour_([0-9]+)\.#", $filename,$re)){
			events("parse_tables_primaires():: Failed $filepath -> not match #^squidhour_([0-9]+)\.");
			@unlink($filepath);
			continue;
		}
		$xtime=$re[1];
		$q->TablePrimaireHour($xtime);
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$ArraySize=count($content);
		
		events("parse_tables_primaires():: squidhour_{$xtime} Inserting ".count($content)." element(s)");
		if(count($content)==0){ 
			ToSyslog("parse_tables_primaires():: squidhour_{$xtime}: $filepath no row has been written");
			@unlink($filepath); 
			continue; 
		}
		$sql="INSERT IGNORE INTO `squidhour_{$xtime}`  (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`hostname`,`zDate`,`zMD5`,`uid`,`QuerySize`,`cached`,`MAC`) 
		VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "\n\n ********************************************************************* \n\n$q->mysql_error\n*********************************************************************\n\n";}
			events("parse_tables_primaires(): Fatal: MySQL error:");
			if(preg_match("#Table 'squidlogs\.(.+?)' doesn't exist#",$q->mysql_error,$re)){
				if($GLOBALS["VERBOSE"]){
					echo "Creating table: {$re[1]}\n";
					$q->TablePrimaireHour(null,false,$re[1]);
					$q->QUERY_SQL($sql);
				}
			}
			
		}
		
		if(!$q->ok){
			events("parse_tables_primaires(): Fatal: MySQL error:");
			events("$sql");
			continue;
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with $ArraySize elements...\n";}
		@unlink($filepath);
		$countDeFiles++;
		}
		
	if($GLOBALS["VERBOSE"]){echo "$countDeFiles Files parsed done\n";}
	parse_tables_cache_primaires();
}
function parse_tables_cache_primaires(){
	$unix=new unix();
	if (!$handle = opendir("/var/log/squid/mysql-rtcaches")){return;}
	$q=new mysql_squid_builder();
	$q->TablePrimaireCacheHour(date("YmdH"));

	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="/var/log/squid/mysql-rtcaches/$filename";
		events("parse_tables_primaires():: Scanning $filepath");
		if(!preg_match("#^cachehour_([0-9]+)\.#", $filename,$re)){
			events("parse_tables_cache_primaires():: Failed $filepath -> not match #^cachehour_([0-9]+)\.");
			@unlink($filepath);
			continue;
		}
		$xtime=$re[1];
		
		if($GLOBALS["VERBOSE"]){echo "Checking cachehour_$xtime\n";}
		
		$q->TablePrimaireCacheHour($xtime);
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$ArraySize=count($content);

		if($ArraySize==0){continue;}
		events("parse_tables_cache_primaires():: cachehour_{$xtime} Inserting ".count($content)." element(s)");
		$sql="INSERT IGNORE INTO `cachehour_{$xtime}` (`zDate`,`size`,`cached`,`familysite`) VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "\n\n ********************************************************************* \n\n$q->mysql_error\n*********************************************************************\n\n";}
			if(preg_match("#Table 'squidlogs.(.+?)'\s+doesn't exist#is",$q->mysql_error,$re)){
				ToSyslog("parse_tables_cache_primaires:: Creating Table $re[1]...");
				if($GLOBALS["VERBOSE"]){echo "\n\n Creating Table {$re[1]}\n\n";}
				$q->TablePrimaireCacheHour(null,false,$re[1]);
				$q->QUERY_SQL($sql);
				if(!$q->ok){ToSyslog("parse_tables_cache_primaires:: Creating Table $re[1] failed...");}
			
			}else{
				if($GLOBALS["VERBOSE"]){echo "\n\n NO PREG MATCH\n\n";}
			}
			
		}

		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "\n\n [".__LINE__."] *****************************************************\n\n";}
			echo $q->mysql_error;
			continue;
		}

		if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with $ArraySize elements...\n";}
		@unlink($filepath);
		$countDeFiles++;
	}

	if($GLOBALS["VERBOSE"]){echo "$countDeFiles Files parsed done\n";}
}

function parse_realtime_hash(){
	@mkdir("/var/log/squid/mysql-rtcaches",0755,true);
	@mkdir("/var/log/squid/mysql-rttime",0755,true);
	
	$GLOBALS["TablePrimaireHour"]=array();
	$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]=array();
	$GLOBALS["MacResolvFrfomIP"]=null;
	$GLOBALS["MacResolvInterface"]=null;
	$WORKDIR="/var/log/squid/mysql-rthash";
	$GLOBALS["MacResolvInterface"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/MacResolvInterface"));
	$GLOBALS["EnableMacAddressFilter"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMacAddressFilter"));
	if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}
	$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
	$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
	$EnableRemoteStatisticsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance"));
	$SquidActHasReverse=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidActHasReverse"));
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	
	@mkdir($WORKDIR,0755,true);
	chown($WORKDIR,"squid");
	chgrp($WORKDIR, "squid");
	
	if (!$handle = opendir("/var/log/squid/mysql-rthash")){return;}
	$GLOBALS["LOG_HOSTNAME"]=false;
	$EnableProxyLogHostnames=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableProxyLogHostnames")));
	if($EnableProxyLogHostnames==1){$GLOBALS["LOG_HOSTNAME"]=true;}
	$GLOBALS["IPCACHE"]=unserialize(@file_get_contents("/etc/squid3/IPCACHE.db"));
	$GLOBALS["SitenameResolved"]=unserialize(@file_get_contents("/etc/squid3/SitenameResolved.db"));
	$GLOBALS["GetFamilySites"]=unserialize(@file_get_contents("/etc/squid3/GetFamilySites.db"));
	$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));
	$GLOBALS["KEYUSERS"]=unserialize(@file_get_contents("/etc/squid3/KEYUSERS.db"));
	$GLOBALS["CACHEARP"]=unserialize(@file_get_contents("/etc/squid3/CACHEARP.db"));
	if($GLOBALS["MacResolvInterface"]<>null){$GLOBALS["MacResolvFrfomIP"]=ethToIp();}
	$GLOBALS["UserAgents"]=array();
	$q=new mysql_squid_builder();
	$logfileD=new logfile_daemon();
	$IpClass=new IP();
	$CountDeFiles=0;
	$AA=0;
	
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="$WORKDIR/$filename";
		
		events("parse_realtime_hash():: Scanning $WORKDIR/$filename");
		
		$content=unserialize(@file_get_contents($filepath));
		$CountDeFiles++;
		@unlink($filepath);
		
		while (list ($SUFFIX_TABLE, $Arrayz) = each ($content) ){
			while (list ($index, $rows) = each ($Arrayz) ){
			$AA++;
			$cached=0;
			$hostname=null;
			$SUFFIX_DATE=$SUFFIX_TABLE;
			$key=null;
			$xtime=$rows["TIME"];
			$sitename=$rows["SITENAME"];
			$mac=$rows["MAC"];
			$uid=$rows["UID"];
			$ipaddr=$rows["IPADDR"];
			if(isset($rows["HOSTNAME"])){$hostname=$rows["HOSTNAME"]; }
			$SquidCode=$rows["SQUID_CODE"];
			$SIZE=$rows["SIZE"];
			$uri=$rows["URI"];
			$zMD5=md5(serialize($rows));
			$UserAgent=$rows["USERAGENT"];
			$code_error=$rows["HTTP_CODE"];
			if($IpClass->isValid($uid)){ $uid=null; }
			$RESPONSE_TIME=$rows["RESPONSE_TIME"];
			if($GLOBALS["VERBOSE"]){echo "Scanning $SUFFIX_DATE $xtime $ipaddr $sitename\n";}
			if(isset($GLOBALS["ZMD5"][$zMD5])){
				events("$uri - md5 = $zMD5 is the same !!!");
			}
			$GLOBALS["ZMD5"][$zMD5]=true;
			
			if($mac==null){
				if($GLOBALS["EnableMacAddressFilter"]==1){ $mac=IpToMac($ipaddr); }
			}
			
			
			if($uid==null){
				if($mac<>null){
					if(isset($GLOBALS["USERSDB"]["MACS"][$mac])){ $uid=$GLOBALS["USERSDB"]["MACS"][$mac]["UID"];}
				}
			}
			
			
			if(strpos("   $sitename", "www.")>0){ if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];} }
			
			if($IpClass->isValid($sitename)){
				if(!isset($GLOBALS["SitenameResolved"][$sitename])){$GLOBALS["SitenameResolved"][$sitename]=gethostbyaddr2($sitename); }
				if($GLOBALS["SitenameResolved"][$sitename]<>null){$sitename=$GLOBALS["SitenameResolved"][$sitename];}
			}
			
			if(!isset($GLOBALS["GetFamilySites"][$sitename])){
				$GLOBALS["GetFamilySites"][$sitename]=x_GetFamilySites($sitename);
				if($GLOBALS["GetFamilySites"][$sitename]==null){$GLOBALS["GetFamilySites"][$sitename]=$sitename;}
			}	

			
			$familysite=$GLOBALS["GetFamilySites"][$sitename];
			if($familysite=="localhost"){continue;}
			
			if($uid<>null){$key="uid";}
			if($key==null){if($mac<>null){$key="MAC";}}
			if($key==null){if($ipaddr<>null){$key="ipaddr";}}
			if($key==null){continue;}
			
			$hour=date("H",$xtime);
			$date=date("Y-m-d H:i:s",$xtime);
			
			if($GLOBALS["VERBOSE"]){echo "Date: $date: $familysite $uid/$ipaddr\n";}
			$uri=trim($uri);
			if($uri==null){continue;}
			if($uid==null){$uid=x_MacToUid($mac);}
			if($uid==null){$uid=x_IpToUid($ipaddr);}
			if($hostname==null){$hostname=x_MacToHost($mac);}
			if($hostname==null){$hostname=x_IpToHost($ipaddr);}
			if(trim($hostname)==null){
				if($GLOBALS["LOG_HOSTNAME"]){ $hostname=gethostbyaddr2($ipaddr); }
			}
			if(preg_match("#(.+?):(.+)#", $SquidCode,$re)){ $SquidCode=$re[1]; }
			
			if($logfileD->CACHEDORNOT($SquidCode)){$cached=1;}
			
			if($GLOBALS["VERBOSE"]){
				echo "[".__LINE__."]: Uri <$uri> Squid code=$SquidCode cached=$cached  Client = $uid/$mac/$hostname [$ipaddr] , Size=$SIZE bytes\n";
			}

			//events("$familysite - Squid code=$SquidCode cached=$cached  Client = $uid/$mac/$hostname [$ipaddr] , Size=$SIZE bytes");
			$KeyUser=md5($uid.$hostname.$ipaddr.$mac.$UserAgent);
			$UserAgent=x_mysql_escape_string2($UserAgent);
			
			if(!isset($GLOBALS["KEYUSERS"][$KeyUser])){
				
				$GLOBALS["UserAutDB"][]="('$KeyUser','$mac','$ipaddr','$uid','$hostname','$UserAgent')";
				//$sql="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ('$KeyUser','$mac','$ipaddr','$uid','$hostname','$UserAgent')";
			}	
			
			if($UserAgent<>null){
				$GLOBALS["UserAgents"][]="('$UserAgent')";
			}
			

			$TablePrimaireHour="squidhour_".$SUFFIX_DATE;
			$TableSizeHours="sizehour_".$SUFFIX_DATE;
			$TableCacheHours="cachehour_".$SUFFIX_DATE;
			$tableYoutube="youtubehours_".$SUFFIX_DATE;
			$tableSearchWords="searchwords_".$SUFFIX_DATE;
			$sitename=x_mysql_escape_string2($sitename);
			
			$uri=substr($uri, 0,254);
			$uri=x_mysql_escape_string2($uri);
			$uriT=x_mysql_escape_string2($uri);
			$hostname=x_mysql_escape_string2($hostname);
			$TYPE=$logfileD->codeToString($code_error);
			$REASON=$TYPE;
			
			if($mac<>null){$GLOBALS["macscan"][]="('$mac','$ipaddr')";}
			$GLOBALS["TablePrimaireHour"][$TablePrimaireHour][]="('$sitename','$uriT','$TYPE','$REASON','$ipaddr','$hostname','$date','$zMD5','$uid','$SIZE','$cached','$mac')";
			//$sql="INSERT IGNORE INTO `$TableSizeHours` (`zDate`,`size`,`cached`) VALUES('$date','$SIZE','$cached')";
			$GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"][$TableSizeHours][]="('$date','$SIZE','$cached')";
			if($SIZE>0){
				$GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"][$TableCacheHours][]="('$date','$SIZE','$cached','$familysite')";
			}
						
			if(strpos(" $uri", "youtube")>0){
				$VIDEOID=$logfileD->GetYoutubeID($uri);
				if($VIDEOID<>null){
					events("YOUTUBE:: $date: $ipaddr $uid $mac [$VIDEOID]");
					$sql="INSERT IGNORE INTO `$tableYoutube`
					(`zDate`,`ipaddr`,`hostname`,`uid`,`MAC` ,`account`,`youtubeid`)
					VALUES ('$date','$ipaddr','','$uid','$mac','0','$VIDEOID')";
					$rand=rand(100,65000);
					@file_put_contents("/var/log/squid/mysql-queue/YoutubeRTT.".time().".$rand.sql", $sql);
				}
			}		

			$SearchWords=$logfileD->SearchWords($uri);
			if(is_array($SearchWords)){
				$words=x_mysql_escape_string2($SearchWords["WORDS"]);
				$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"][$tableSearchWords][]="('$zMD5','$sitename','$date','$ipaddr','$hostname','$uid','$mac','0','$familysite','$words')";
				
			}		
		
			//
			
			$timekey=date('YmdH',$xtime);
			$stime=date("Y-m-d H:i:s",$xtime);
			$table="quotatemp_$timekey";
			$keyr2=md5("$stime$date$uid$ipaddr$mac$sitename");
			$GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"][$table][]="('$stime','$keyr2','$ipaddr','$familysite','$familysite','$uid','$mac','$SIZE')";		
		}
		
	}

}
events("$WORKDIR -> $AA elements scanned");


if(count($GLOBALS["UserAgents"])>0){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO `UserAgents` (`pattern`) VALUES ".@implode(",", $GLOBALS["UserAgents"]));
	$GLOBALS["UserAgents"]=array();
}


if($CountDeFiles>0){
	$GLOBALS["PARSE_SECOND_TIME"]=true;	
	events(__FUNCTION__."():: $CountDeFiles parsed files");	
	@file_put_contents("/etc/squid3/IPCACHE.db", serialize($GLOBALS["IPCACHE"]));
	@file_put_contents("/etc/squid3/SitenameResolved.db", serialize($GLOBALS["SitenameResolved"]));
	@file_put_contents("/etc/squid3/GetFamilySites.db", serialize($GLOBALS["GetFamilySites"]));
	@file_put_contents("/etc/squid3/KEYUSERS.db", unserialize($GLOBALS["KEYUSERS"]));
	@file_put_contents("/etc/squid3/CACHEARP.db", serialize($GLOBALS["CACHEARP"]));
	PurgeMemory();
	empty_TablePrimaireHour();
}
	
	
}
function empty_TablePrimaireHour(){
	$rand=rand(5, 9000);
	while (list ($tablename, $rows) = each ($GLOBALS["TablePrimaireHour"]) ){
		if(count($rows)>0){
			$filename="/var/log/squid/mysql-rttime/$tablename.".microtime(true).".$rand.sql";
			events("empty_TablePrimaireHour:: Purge: $tablename ".count($rows)." Into $filename");
			@file_put_contents("$filename", serialize($rows));
		}
	}
	$GLOBALS["TablePrimaireHour"]=array();
	
	if(count($GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"])>0){
		while (list ($tablename, $rows) = each ($GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"]) ){
			if(count($rows)>0){
				$filename="/var/log/squid/mysql-rtcaches/$tablename.".microtime(true).".$rand.sql";
				events("empty_TablePrimaireHour:: Purge: $tablename ".count($rows)." Into $filename");
				@file_put_contents("$filename", serialize($rows));
			}
		}
	}
	
	$GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"]=array();

}


function gethostbyaddr2($ipaddr){
	if(!isset($GLOBALS["IPCACHE"][$ipaddr]["TIME"])){
		$GLOBALS["IPCACHE"][$ipaddr]["TIME"]=time();
		$GLOBALS["IPCACHE"][$ipaddr]["VALUE"]=gethostbyaddr($ipaddr);
		if($GLOBALS["IPCACHE"][$ipaddr]["VALUE"]==$ipaddr){$GLOBALS["IPCACHE"][$ipaddr]["VALUE"]=null;}
		return $GLOBALS["IPCACHE"][$ipaddr]["VALUE"];
	}
	$data1=$GLOBALS["IPCACHE"][$ipaddr]["TIME"];
	$difference = (time() - $data1);
	$MinTTL=intval(round($difference/60));
	if($MinTTL<0){$MinTTL=1;}
	if($MinTTL<14){
		if(isset($GLOBALS["IPCACHE"][$ipaddr]["VALUE"])){return $GLOBALS["IPCACHE"][$ipaddr]["VALUE"];}
	}

	$GLOBALS["IPCACHE"][$ipaddr]["VALUE"]=gethostbyaddr($ipaddr);
	if($GLOBALS["IPCACHE"][$ipaddr]["VALUE"]==$ipaddr){$GLOBALS["IPCACHE"][$ipaddr]["VALUE"]=null;}
	return $GLOBALS["IPCACHE"][$ipaddr]["VALUE"];

}
function PurgeMemory(){
	$Dir="/var/log/squid/mysql-queue";

	events("Purge UserAutDB: ".count($GLOBALS["UserAutDB"])." elements");
	events("Purge MacScan: ".count($GLOBALS["macscan"])." elements");
	$rand=rand(5, 9000);
	if(count($GLOBALS["UserAutDB"])>0){
		@file_put_contents("$Dir/UserAutDB.".microtime(true).".$rand.sql", serialize($GLOBALS["UserAutDB"]));
	}
	
	if(count($GLOBALS["macscan"])>0){
		@file_put_contents("$Dir/macscan.".microtime(true).".$rand.sql", serialize($GLOBALS["macscan"]));
		$GLOBALS["macscan"]=array();
	}
	




	if(count($GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"])>0){
		while (list ($tablename, $rows) = each ($GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"]) ){
			events("$tablename: Saving ".count($rows));
			@file_put_contents("$Dir/$tablename.".microtime(true).".$rand.sql", serialize($rows));
		}
		$GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"]=array();
	}

	if(count($GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"])>0){
		while (list ($tablename, $rows) = each ($GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]) ){
			events("$tablename: Saving ".count($rows));
			@file_put_contents("$Dir/$tablename.".microtime(true).".$rand.sql", serialize($rows));
		}
		$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]=array();
	}

	if(count($GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"])>0){
		while (list ($tablename, $rows) = each ($GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"]) ){
			events("$tablename: Saving ".count($rows));
			@file_put_contents("$Dir/$tablename.".microtime(true).".$rand.sql", serialize($rows));
		}
		$GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"]=array();
	}

}

function Wakeup(){
	
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"exec.logfile_daemon.php\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$PID=$re[1];
		$PID_LIST[$re[1]]=true;
		if($GLOBALS["VERBOSE"]){echo "Wakup pid:$PID\n";}
		
		$WakeupFile="/var/run/squid/exec.logfilefile_daemon.$PID.wakeup";
		if(!is_file($WakeupFile)){
			events("Wakeup():: Wakup exec.logfilefile_daemon.php process pid:$PID");
			@touch($WakeupFile);
		}else{
			$TimePID=$unix->file_time_min($WakeupFile);
			events("Wakeup():: pid:$PID did not respond since {$TimePID}mn");
		}
		@chmod($WakeupFile,0777);
		@chown($WakeupFile,"squid");
		@chgrp($WakeupFile,"squid");
	}
	
	foreach (glob("/var/run/squid/exec.logfilefile_daemon.*.pid") as $filepath) {
		if($GLOBALS["VERBOSE"]){echo "$filepath\n";}
		$basename=basename($filepath);
		if(!preg_match("#exec\.logfilefile_daemon\.([0-9]+)\.pid#", $basename,$re)){continue;}
		$PID=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Found pid:$PID\n";}
		if(!$unix->process_exists($PID)){
			if($GLOBALS["VERBOSE"]){echo "pid:$PID not running, delete it\n";}
			@unlink("/var/run/squid/exec.logfilefile_daemon.$PID.wakeup");
			@unlink("/var/run/squid/exec.logfilefile_daemon.$PID.debug");
			@unlink($filepath);
		}
	}
	
	
}


function parse_realtime_events(){
	$unix=new unix();
	
	$TimePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=@file_get_contents($TimePID);
	if($unix->process_exists($pid)){
		$timePid=$unix->PROCCESS_TIME_MIN($pid);
		events("parse_realtime_events():: Already process exists $pid since {$timePid}Mn");
		if($timePid>10){
			$kill=$unix->find_program("kill");
			events("parse_realtime_events():: Killing $pid running since {$timePid}Mn");
			unix_system_kill_force($pid);
		}else{
			if($GLOBALS["VERBOSE"]){echo "Already running PID $pid since {$timePid}mn";}
			die();
		}
		
	}
	
	events("parse_realtime_events():: Time File: $TimeExec");
	@file_put_contents($TimePID, getmypid());
	@unlink($TimeExec);
	@file_put_contents($TimeExec, time());
	
	
	Wakeup();
	
	events("parse_realtime_events():: -> parse_realtime_hash()");
	$GLOBALS["PARSE_SECOND_TIME"]=false;
	parse_realtime_hash();
	if(!$GLOBALS["PARSE_SECOND_TIME"]){return;}
	
	
	@mkdir("/var/log/squid/mysql-queue",0755,true);
	if (!$handle = opendir("/var/log/squid/mysql-queue")){return;}
	$q=new mysql_squid_builder();
	$q->check_youtube_hour(date("YmdH"));
	
	



$countDeFiles=0;
while (false !== ($filename = readdir($handle))) {
	if($filename=="."){continue;}
	if($filename==".."){continue;}
	$filepath="/var/log/squid/mysql-queue/$filename";
	
	if(preg_match("#^UserAutDB#", $filename)){
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$ArraySize=count($content);
		$sql="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){ echo $q->mysql_error."\n"; }else{
			if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with $ArraySize elements...\n";}
			@unlink($filepath);
		}
		continue;
	}
	
	if(preg_match("#^macscan#", $filename)){
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$ArraySize=count($content);
		$sql="INSERT IGNORE INTO `macscan` (`MAC`,`ipaddr`) VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){ echo $q->mysql_error."\n"; }else{
			if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with $ArraySize elements...\n";}
			@unlink($filepath);
		}
		continue;
	}
	
	if(preg_match("#^YoutubeRTT#", $filename)){
		$sql=trim(@file_get_contents($filepath));
		$contentSize=strlen($sql)/1024;
		
		if(preg_match("#INSERT IGNORE INTO `(.+?)`#",$sql,$re)){
			$tablename=$re[1];
			if(!preg_match("#youtubehours_([0-9]+)#", $tablename)){
				echo "***** replace $tablename to youtubehours_date(YmdH) ****\n";
				$sql=str_replace($tablename, "youtubehours_".date("YmdH"), $sql);
			}
		}
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){ 
				echo $q->mysql_error."\n"; 
		}else{
			if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with 1 element...\n";}
			@unlink($filepath);
		}
		continue;
	}
	
	if(preg_match("#^sizehour_([0-9]+)\.#", $filename,$re)){
		$TableSizeHours="sizehour_{$re[1]}";
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$q->check_sizehour($TableSizeHours);
		$sql="INSERT IGNORE INTO `$TableSizeHours` (`zDate`,`size`,`cached`) VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){ 
			echo $q->mysql_error."\n";
		}else{
			if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with ". count($content)." elements...\n";}
			@unlink($filepath);
		}
		continue;
	}
	
	if(preg_match("#^searchwords_([0-9]+)\.#", $filename,$re)){
		$TableSource="searchwords_{$re[1]}";
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$q->check_SearchWords_hour(null,$TableSource);
		$sql="INSERT IGNORE INTO `$TableSource`
		(`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`)
		VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
		}else{
			if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with ". count($content)." elements...\n";}
			@unlink($filepath);
		}
		continue;
	}
	if(preg_match("#^quotatemp_([0-9]+)\.#", $filename,$re)){
		$TableSource="quotatemp_{$re[1]}";
		$q->check_quota_hour_tmp($re[1]);
		$q->check_quota_hour($re[1]);
		$content=unserialize(@file_get_contents($filepath));
		$contentSize=filesize($filepath)/1024;
		$sql="INSERT IGNORE INTO `$TableSource` (`xtime`,`keyr`,`ipaddr`,`familysite`,`servername`,`uid`,`MAC`,`size`) VALUES ".@implode(",", $content);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
		echo $q->mysql_error."\n";
		}else{
		if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with ". count($content)." elements...\n";}
			@unlink($filepath);
		}
		continue;
	}	
}

$php=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
$cmd="$nohup $php ".__FILE__." --tables-primaires >/dev/null 2>&1 &";
if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
shell_exec($cmd);
	
}	
	
function x_GetFamilySites($sitename){
	if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
	$fam=new squid_familysite();
	$GLOBALS["GetFamilySites"][$sitename]=$fam->GetFamilySites($sitename);
	return $GLOBALS["GetFamilySites"][$sitename];
}
function x_MacToUid($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
	return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);

}
function x_IpToUid($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);

}

function x_MacToHost($mac=null){
	if($mac==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]);
}
function x_IpToHost($ipaddr=null){
	if($ipaddr==null){return;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"])){return;}
	if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]==null){return;}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]);
}
function x_mysql_escape_string2($line){
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}
function events($text){
	if(trim($text)==null){return;}
	
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.debug";

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);

}
function IpToMac($ipaddr){
	if($GLOBALS["EnableMacAddressFilter"]==0){
		if($GLOBALS["VERBOSE"]){events("IpToMac($ipaddr): EnableMacAddressFilter set to disabled, aborting");}
		return null;
	}
	if($GLOBALS["MacResolvFrfomIP"]==null){
		if($GLOBALS["VERBOSE"]){events("IpToMac($ipaddr): MacResolvInterface/MacResolvFrfomIP Not set, aborting");}
		return null;
	}
	
	if(!is_file("/usr/bin/arping")){ $GLOBALS["MacResolvFrfomIP"]=null; return; }
	
	$ipaddr=trim($ipaddr);
	$ttl=date('YmdH');
	if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
	
	$IpClass=new IP();
	$unix=new unix();
	

	if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
	if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
	
	$mac=$unix->IpToMac($ipaddr);

	if($IpClass->IsvalidMAC($mac)){
		if($GLOBALS["VERBOSE"]){events("IpToMac -> $ipaddr -> $mac OK");}
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$mac;

	}else{
		if($GLOBALS["VERBOSE"]){events("IpToMac -> $ipaddr -> $mac FAILED");}
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=null;
		return null;
	}


}
function ethToIp(){
	$cmd="/sbin/ip addr show {$GLOBALS["MacResolvInterface"]} 2>&1";
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){events("ethToIp():: $cmd ".count($results)." lines");}
	while (list ($num, $line) = each ($results)){

		if(preg_match("#inet\s+([0-9\.]+)\/#", $line,$re)){
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){events("ethToIp():: $line No match");}
	}
}
function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("access-injector", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}