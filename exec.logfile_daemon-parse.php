<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BYCRON"]=false;
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
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.ocs.inc");
include_once(dirname(__FILE__)."/ressources/class.squidlogs.parser.inc");

//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}

$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
$unix=new unix();


$pidfile="/etc/artica-postfix/pids/exec.logfile_daemon-parse.php.GLOBAL.pid";
$unix=new unix();
$pid=@file_get_contents($pidfile);

if($unix->process_exists($pid,basename(__FILE__))){
	$timepid=$unix->PROCCESS_TIME_MIN($pid);
	events("$pid already executed since {$timepid}Mn");
	die();
}

@file_put_contents($pidfile, getmypid());
$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));


events("Running instances:".count($pids));


if(count($pids)>1){
	events("Too many instances ". count($pids));
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){
			events("SKIPPING ME $pid");
			continue;
		}
		events("Killing $pid ". @file_get_contents("/proc/$pid/cmdline"));
		unix_system_kill_force($pid);
	}

}

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>1){
	events("Too many instances ". count($pids)." dying");
	die();
}


if(!is_file("/etc/cron.d/logfile-daemon")){
	$ionice=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="MAILTO=\"\"";
	$f[]="* * * * *  root $ionice  $php ".__FILE__." --bycron >/dev/null 2>&1";
	$f[]="";
	$f[]="";
	@file_put_contents("/etc/cron.d/logfile-daemon", @implode("\n", $f));
	@chmod("/etc/cron.d/logfile-daemon",0644);	
	shell_exec("/etc/init.d/cron reload");
	
}



if(count($argv)>0){
	if(isset($argv[1])){
		events("Execute {$argv[1]} - {$argv[2]}...");
		if($argv[1]=="--tables-primaires"){parse_tables_primaires();die();}
		if($argv[1]=="--wakeup"){Wakeup();die();}
		if($argv[1]=="--caches"){parse_tables_cache_primaires();die();}
		if($argv[1]=="--squid-queue"){parse_sql_commands();die();}
		if($argv[1]=="--users"){UserAuthDB_in_mysql_queue();die();}
		if($argv[1]=="--parse"){NewLogsParser();die();}
		if($argv[1]=="--stats-uid"){Scanuuid($argv[2]);die();}
		
		
	}
}

	$TimeFile="/etc/artica-postfix/pids/exec.logfile_daemon-parse.php.time";
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	if($GLOBALS["BYCRON"]){
		events("Executed by CRON...");
	}
	
	$logFile="/var/log/squid/logfile_daemon.debug";

	$unix=new unix();
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
	
	@mkdir($GLOBALS["LogFileDeamonLogDir"],0755,true);
	@chmod($GLOBALS["LogFileDeamonLogDir"], 0755);
	@chown($GLOBALS["LogFileDeamonLogDir"],"squid");
	@chgrp($GLOBALS["LogFileDeamonLogDir"], "squid");
		
	
	NewLogsParser();
	parse_sql_commands(true);
	parse_realtime_events(true);
	parse_tables_primaires(true);
	ParseUsersAgents();
	UserAuthDB_in_mysql_queue();
	events("***************** END ******************");
	
function NewLogsParser(){
	$rrt=new squid_logs_parser();
}

function Scanuuid($uuid){
	events("Scanuuid:: $uuid");
	$unix=new unix();
	$directory="/usr/share/artica-postfix/ressources/conf/upload/StatsApplianceLogs/$uuid";
	$Files=$unix->DirFiles($directory);
	while (list ($filename, $none) = each ($Files) ){
		$SourcePath="$directory/$filename";
		$TargetPath="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
		if(is_file($TargetPath)){continue;}
		if(!@copy($SourcePath,$TargetPath)){continue;}
		events("Scanuuid:: removing $SourcePath");
		@unlink($SourcePath);
	}
}
	
function ParseComputersOCS(){
	
	$Dir="/var/log/squid/mysql-computers";
	if (!$handle = opendir($Dir)){
		events("Unable to open $Dir");
		return;
	}
	
	$f=array();
	
	events("ParseComputersOCS()::Scanning $Dir");
	$ocs=new ocs();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="$Dir/$filename";
		events("ParseComputersOCS():: Scanning $filepath");
		$array=unserialize(@file_get_contents($filepath));
		
		if(!is_array($array)){
			events("ParseUsersAgents:: $filepath Not an array");
			continue;
		}
		
		$mac=$array["MAC"];
		$ipaddr=$array["IP"];
		
		$ocs->ADD_HARDWARE($ipaddr, $mac);
		
	}
	
}	
	
	
function ParseUsersAgents(){
	
	
	$Dir="/var/log/squid/mysql-UserAgents";
	
	if (!$handle = opendir("/var/log/squid/mysql-UserAgents")){
		events("Unable to open /var/log/squid/mysql-UserAgents");
		return;
	}
	$f=array();
	
	events("Scanning /var/log/squid/mysql-UserAgents");
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="$Dir/$filename";
		events("ParseUsersAgents():: Scanning $filepath");
		
		
		$array=unserialize(@file_get_contents($filepath));
		
		
		if(!is_array($array)){
			events("ParseUsersAgents:: $filepath Not an array");
			continue;
		}
		
		
		
		while (list ($useragent, $sql) = each ($array) ){
			$useragent=mysql_escape_string2($useragent);
			$f[]="('$useragent')";
		}
		events("ParseUsersAgents()::".count($f)." items");
		@unlink($filepath);
		
	}
	
	if(count($f)>0){
		events("ParseUsersAgents ".count($f)." items...");
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("INSERT IGNORE INTO `UserAgents` (`pattern`) VALUES ".@implode(",", $f));
		if(!$q->ok){events("ParseUsersAgents $q->mysql_error");}
	}else{
		events("ParseUsersAgents Nothing...");
	}
	
	
}


function parse_sql_commands($nopid=false){
	$unix=new unix();
	@mkdir("/var/log/squid/mysql-squid-queue",0755,true);
	$unix->chown_func("squid","squid","/var/log/squid/mysql-squid-queue");
	
	$TimePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	
	
	if(!$nopid){
		events("parse_sql_commands():: Checking PID");
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
		
		
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["VERBOSE"]){
				$Time=$unix->file_time_min($TimeExec);
				if($Time==0){return;}
			}
		}
	}
	
	
	@unlink($TimeExec);
	@file_put_contents($TimeExec, time());
	
	if (!$handle = opendir("/var/log/squid/mysql-squid-queue")){
		events("Unable to open /var/log/squid/mysql-squid-queue");
		return;
	}
	
	events("Scanning: /var/log/squid/mysql-squid-queue");
	$d=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filepath="/var/log/squid/mysql-squid-queue/$filename";
		$d++;
		
		
		
		
		$data=@file_get_contents($filepath);
		$SQL_CMDS=unserialize($data);
		if(count($SQL_CMDS)==0){
			events("parse_sql_commands()::Removing $filepath, no sql commands");
			@unlink($filepath);
			continue;
		}
		$MAX=count($SQL_CMDS);
		events("parse_sql_commands()::Open $filepath with $MAX rows");
		
		$NEWSQL=array();
		$q=new mysql_squid_builder();
		
		$c=0;
		while (list ($index, $sql) = each ($SQL_CMDS) ){
			$c++;
			
			$sql=trim($sql);
			if($sql==null){continue;}
			$q->QUERY_SQL($sql);
			if($q->ok){continue;}
			
			events("$filename: $c/$MAX FAILED \"$q->mysql_error\"");
			if(preg_match("#Table 'squidlogs\.(.+?)' doesn\'t exist#",$q->mysql_error,$re)){
					parse_sql_commands_create_table($re[1]);
					$q->QUERY_SQL($sql);
					if($q->ok){continue;}
				}
			
			if($GLOBALS["VERBOSE"]){echo "\n\n$filename: $c/$MAX no match...\n";}
			
			$NEWSQL[]=$sql;
		}
		
		if(count($NEWSQL)==0){
			events("parse_sql_commands()::Removing $filepath, success to parse");
			@unlink($filepath);
			continue;
		}
		
		events("parse_sql_commands()::Saving $filepath with ".count($NEWSQL)." sql commands");
		@file_put_contents($filepath, serialize($NEWSQL));
	
	}
	
	events("parse_sql_commands()::$d parsed files");
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("ufdbunlock")){
		$q->QUERY_SQL("DELETE FROM ufdbunlock WHERE `finaltime` < ". time());
	}
	
}

function parse_sql_commands_create_table($tablename){
	events("Checking table $tablename");
	
	if(preg_match("#searchwords_(.+)#", $tablename,$re)){
		events("Creating table searchwords_{$re[1]}");
		REALTIME_searchwords($re[1]);
		return;
	}
	
	if(preg_match("#quotatemp_(.+)#", $tablename,$re)){
		events("Creating table quotatemp_{$re[1]}");
		REALTIME_quotatemp($re[1]);
	}

	if(preg_match("#RTTH_(.+)#", $tablename,$re)){
		events("Creating table RTTH_{$re[1]}");
		REALTIME_RTTH($re[1]);
		
	}
	
	if(preg_match("#squidhour_(.+)#", $tablename,$re)){
		events("Creating table squidhour_{$re[1]}");
		REALTIME_squidhour($re[1]);
	
	}
	if(preg_match("#sizehour_(.+)#", $tablename,$re)){
		events("Creating table sizehour_{$re[1]}");
		REALTIME_sizehour($re[1]);
	
	}	
	if(preg_match("#cachehour_(.+)#", $tablename,$re)){
		events("Creating table cachehour_{$re[1]}");
		REALTIME_cachehour($re[1]);
	
	}
	
	if(preg_match("#youtubehours_(.+)#", $tablename,$re)){
		events("Creating table youtubehours_{$re[1]}");
		REALTIME_youtubehours($re[1]);
	}

}


function parse_tables_primaires($nopid=false){
	$unix=new unix();
	
	
	$unix->chown_func("squid","squid","/var/log/squid/mysql-rttime");
	$TimePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	if(!$nopid){
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
	}
	
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
		$sql="INSERT IGNORE INTO `squidhour_{$xtime}`  (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`hostname`,`zDate`,`zMD5`,`uid`,`QuerySize`,`cached`,`MAC`,`category`) 
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
			
			$catz=new mysql_catz();
			$category=x_mysql_escape_string2($catz->GetMemoryCache($sitename,true));
			
			events("RTTHASH:: $sitename Category = `$category`");

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
			$GLOBALS["TablePrimaireHour"][$TablePrimaireHour][]="('$sitename','$uriT','$TYPE','$REASON','$ipaddr','$hostname','$date','$zMD5','$uid','$SIZE','$cached','$mac','$category')";
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

function UserAuthDB_in_mysql_queue(){
	$unix=new unix();
	
	$Dir="/var/log/squid/mysql-queue";
	
	
	events("Scanning $Dir for UserAutDB");
	$ScanFiles=$unix->DirFiles($Dir,"UserAutDB\.[0-9\.]+\.sql");
	$q=new mysql_squid_builder();
	while (list ($filename, $rows) = each ($ScanFiles) ){
		$filepath="$Dir/$filename";
		$array=unserialize(@file_get_contents($filepath));
		if(count($array)==0){
			events("$filepath Not an array");
			@unlink($filepath);
			continue;
		}
		$sql="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ".@implode(",", $array);
		if($q->QUERY_SQL($sql)){
			@unlink($filepath);
			continue;
		}
		
		
	}
	
	
	
	
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
	$TimeFile="/etc/artica-postfix/pids/exec.logfile_daemon-parse.php.Wakeup.time";
	$TimExec=$unix->file_time_min($TimeFile);
	if($TimExec==0){return;}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
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


function parse_realtime_events($nopid=false){
	events("parse_realtime_events():: nopid => $nopid");
	$unix=new unix();
	
	$TimePID="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	
	if(!$nopid){
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
		@file_put_contents($TimePID, getmypid());
	}
	
	events("parse_realtime_events():: Time File: $TimeExec");
	
	@unlink($TimeExec);
	@file_put_contents($TimeExec, time());
	
	events("Wakup...");
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
			ToSyslog("$q->mysql_error in line [".__LINE__."]");
			if(preg_match("#Table\s+'.+?\.youtubehours_(.+?)'\s+doesn't exist#", $q->mysql_error,$re)){
				ToSyslog("Building youtubehours_{$re[1]} table");
				$q->check_youtube_hour($re[1]);
				$q->QUERY_SQL($sql);
				}
		}
		
		if(!$q->ok){
			ToSyslog("Failed ->$filename");
			continue;
		}
		
		 
		if($GLOBALS["VERBOSE"]){echo $filepath." ($contentSize KB) done with 1 element...\n";}
		@unlink($filepath);
				
		
		
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
events("$countDeFiles Scanned files...");
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