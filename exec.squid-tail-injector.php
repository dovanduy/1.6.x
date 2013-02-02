<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($GLOBALS["VERBOSE"]){echo "DEBUG::: ".@implode(" ", $argv)."\n";}
$GLOBALS["AS_ROOT"]=true;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.tail.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}

if($argv[1]=="--words"){WordScanners(true);die();}
if($argv[1]=="--unveiltech"){unveiltech();die();}
if($argv[1]=="--youtube"){youtube();die();}
if($argv[1]=="--users-agents"){useragents();die();}
if($argv[1]=="--users-size"){ParseUsersSize();die();}
if($argv[1]=="--squid"){ParseSquidLogMain();die();}
if($argv[1]=="--nudity"){nudityScan();die();}
if($argv[1]=="--brut"){ParseSquidLogBrut(true);die();}
if($argv[1]=="--main"){ParseSquidLogMain(true);die();}






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
	if($EnableRemoteSyslogStatsAppliance==1){
		events("DIE, using remote statistics Appliance with Syslog..");
		ParseSquidLogBrut(false);
		die();
	}
	events("Executed pid $mypid");
	events("Execute ParseSquidLogBrut()");
	ParseSquidLogBrut(false);
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
	events("Execute WordScanners()");
	WordScanners();
	events("FINISH....");
	
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec(trim(" $php5 ".dirname(__FILE__)."/exec.squid-users-rttsize.php --now schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1"));

function ParseUsersSize(){
	return;
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
		if(isset($GLOBALS["resvip"][$ip])){
			if(strlen($GLOBALS["resvip"][$ip])>3){return $GLOBALS["resvip"][$ip];}
		}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$ip]=$name;
		return $name;
		}
		
		
function nudityScan(){
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			writelogs_squid("Fatal Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
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
				
				while (list ($key, $val) = each ($array) ){
					$array[$key]=str_replace("'", "`", $val);
				}
				
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

function WordScanners(){
	if(isset($GLOBALS["WordScanners_executed"])){return;}
	$GLOBALS["WordScanners_executed"]=true;
	$workdir="/var/log/artica-postfix/searchwords";
	@mkdir($workdir,0755,true);
	if($GLOBALS["VERBOSE"]){echo "Open $workdir\n";}
	$handle = opendir($workdir);
	
	if(!$handle){
		if($GLOBALS["VERBOSE"]){echo "Fatal unable to opendir $workdir\n";}
		events("Fatal unable to opendir $workdir",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if($GLOBALS["VERBOSE"]){events("WordScanners(".__LINE__."):Open $workdir done.. instanciate mysql_squid_builder()");}
	$q=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){events("WordScanners(".__LINE__."):Start looping()");}
	$FF=array();
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="$workdir/$filename";
				if($GLOBALS["VERBOSE"]){echo "Scanning $targetFile\n";}
				$searchWords=unserialize(@file_get_contents($targetFile));
				while (list ($key, $val) = each ($searchWords) ){
					$searchWords[$key]=addslashes(str_replace("'", "`", $val));
				}
				$zmd5=md5(serialize($searchWords));						
				$ipaddr=$searchWords["ipaddr"];
				$date=$searchWords["date"];
				$Time=strtotime($date);
				$uid=$searchWords["uid"];
				if(strlen($uid)<3){$uid=null;}
				$mac=trim($searchWords["mac"]);
				if($mac==null){$mac=GetMacFromIP($ipaddr);}
				$hostname=$searchWords["hostname"];	
				$words=$searchWords["WORDS"];
				if(trim($words)==null){@unlink($targetFile);continue;}
				$sitename=$searchWords["SITENAME"];
				$sitename=addslashes($sitename);
				if(!isset($GLOBALS["familysite"][$sitename])){$GLOBALS["familysite"][$sitename]=$q->GetFamilySites($sitename);}
				$familysite=$GLOBALS["familysite"][$sitename];
				if($mac<>null){if($uid==null){$uid=$q->UID_FROM_MAC($mac);}}
				$FF[date("Ymdh",$Time)][]="('$zmd5','$sitename','$date','$ipaddr','$hostname','$uid','$mac','0','$familysite','$words')";
				@unlink($targetFile);
		}
		
		events("WordScanners(".__LINE__."): End looping...\n");
		
		
		$q=new mysql_squid_builder();
		while (list ($tablePrefix, $f) = each ($FF) ){
			if(count($f)>0){
				if($q->check_SearchWords_hour($tablePrefix)){
					$tablename="searchwords_$tablePrefix";
					$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`) VALUES ".@implode(",", $f);
					$q->QUERY_SQL($prefix);
					if(!$q->ok){
						writelogs_squid("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
						echo $q->mysql_error;}
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
				while (list ($key, $value) = each ($array) ){
					$value=str_replace("'", "`", $value);
					$array[$key]=addslashes(trim($value));
				}
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
		$val=str_replace("'", "`", $val);
		$val=stripslashes($val);
		$val=mysql_escape_string($val);
		$array[$num]=$val;
	}
	reset($array);
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
	
	if($username<>null){
		$GLOBALS["USERSCACHE"][$CLIENT]=$username;
		if($mac<>null){
			$GLOBALS["USERSCACHE"][$mac]=$username;
		}
	}else{
		if(isset($GLOBALS["USERSCACHE"][$CLIENT])){
			if($GLOBALS["USERSCACHE"][$CLIENT]<>null){$username=$GLOBALS["USERSCACHE"][$CLIENT];}
		}
		if($username==null){
			if(isset($GLOBALS["USERSCACHE"][$mac])){
				if($GLOBALS["USERSCACHE"][$mac]<>null){$username=$GLOBALS["USERSCACHE"][$mac];}
			}
		}
			
	}

	
	
	
	
	$sitename=addslashes($sitename);
	$uri=addslashes($uri);
	$line="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
	return $line;

}

function ParseSquidLogBrut($nopid=false){
	$unix=new unix();
	$lockfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".lck";
	if($nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		if($oldpid<100){$oldpid=null;}
		
		
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			events("ParseSquidLogBrut:: Already executed pid $oldpid since {$time}mn-> DIE",__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid since {$time}mn\n";}
			die();
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);			
		
	}
	
	if(is_file($lockfile)){
		$timelock=$unix->file_time_min($lockfile);
		if($timelock<60){
			events("ParseSquidLogBrut:: $lockfile exists, aborting",__LINE__);
			return;
		}
	}
	
	@unlink($lockfile);
	@file_put_contents($lockfile, time());
	
	
	$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
	$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}
	$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
	$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;	
	
	
	$workingDir="/var/log/artica-postfix/squid-brut";
	events("Open $workingDir");
	$squidtail=new squid_tail();
	$timeStart=time();
	$c=0;
	$f=0;
	$d=0;
	$h=0;
	$size=0;
	
	events("ParseSquidLogBrut():: starting loop on $workingDir",__LINE__);
	
	if (!$handle = opendir($workingDir)) {
		@mkdir($workingDir,0755,true);
		@unlink($lockfile);
		return;
	}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$workingDir/$filename";
		if(!is_file($targetFile)){continue;}
		$d++;
		$h++;
		usleep(100);
		if($d>500){
			if(system_is_overloaded(basename(__FILE__))){
				events("ParseSquidLogBrut()::Overloaded: wait 10s",__LINE__);
				sleep(10);
			}
			$array_load=sys_getloadavg();
			$internal_load=$array_load[0];
			events("ParseSquidLogBrut()::[$h]::Load: $internal_load",__LINE__);
			
			sleep(1);
			
			if(systemMaxOverloaded()){
				events("ParseSquidLogBrut()::[$h]::System is too overloaded, die",__LINE__);
				@unlink($lockfile);
				return;
			}
			
			$d=0;
		}
		
		
		if($EnableRemoteSyslogStatsAppliance==1){
			events("ParseSquidLogBrut()::[$h]::EnableRemoteSyslogStatsAppliance:$EnableRemoteSyslogStatsAppliance removing $targetFile",__LINE__);
			@unlink($targetFile);
			continue;
		}
			
		$data=@file_get_contents($targetFile);
		$time=filemtime($targetFile);
		$strlen=strlen($data);
		if($GLOBALS["VERBOSE"]){echo "-> $targetFile ".strlen($data)." ";}
		if($strlen==0){
			if(!is_file($targetFile)){continue;}
			$timefile=$unix->file_time_min($targetFile);
			events("ParseSquidLogBrut()::[$h]::Removing[NODATA]::{$timefile}mn:: $targetFile",__LINE__);
			@unlink($targetFile);
			continue;
		}
		
		if(!$squidtail->parse_tail($data,$time)){
			events("ParseSquidLogBrut():: parse_tail(): unable to parse: $targetFile $squidtail->error",__LINE__);
			$f++;
			if($squidtail->ToRemove){
				$timefile=$unix->file_time_min($targetFile);
				events("ParseSquidLogBrut()::[$h]::Removing[PARSED]::{$timefile}mn:: $targetFile",__LINE__);
				@unlink($targetFile);
			}
			continue;
		}
		$timefile=$unix->file_time_min($targetFile);
		events("ParseSquidLogBrut()::[$h]::Removing[success]::{$timefile}mn:: $targetFile",__LINE__);
		@unlink($targetFile);
		$c++;
		$size=$strlen+$size;
		
	}
	$size=round(($size/1024),2);
	events("ParseSquidLogBrut():: $c:parsed $f failed, $h total in ".$unix->distanceOfTimeInWords($timeStart,time() ." size={$size}Ko").__LINE__);
	@unlink($lockfile);
	ParseSquidLogMain();
}


function ParseSquidLogMain(){
$lockfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".lck";
$sock=new sockets();
$unix=new unix();
$q=new mysql_squid_builder();
@mkdir("/var/log/artica-postfix/squid-RTTSize",0755,true);

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
$GLOBALS["USERSCACHE"]=array();
if(!is_dir("/etc/squid3")){@mkdir("/etc/squid3",0755,true);}

if(is_file("/etc/squid3/USERSCACHE.DB")){
	if($unix->file_time_min("/etc/squid3/USERSCACHE.DB")<60){
		$GLOBALS["USERSCACHE"]=unserialize(@file_get_contents("/etc/squid3/USERSCACHE.DB"));
	}
}

$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}
$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;

$hostname=$unix->hostname_g();
$WORKDIR="/var/log/artica-postfix/dansguardian-stats2";

if(is_file($lockfile)){
	$timelock=$unix->file_time_min($lockfile);
	if($timelock<60){
		events("ParseSquidLogMain:: $lockfile exists, aborting since {$timelock}mn",__LINE__);
		return;
	}
}

@unlink($lockfile);
@file_put_contents($lockfile, time());


events("Open $WORKDIR");

if (!$handle = opendir($WORKDIR)) {@mkdir($WORKDIR,0755,true);
	events("Fatal opendir() $WORKDIR ".__LINE__);
	@unlink($lockfile);
	die();
}

$GLOBALS["WAIT-OVERLOAD-TIMEOUT"]=0;	
$c=0;
$countDeFiles=0;
	if(!$GLOBALS["VERBOSE"]){
			if(systemMaxOverloaded()){
				events("Fatal:$hostname VERY Overloaded system, die(); on Line: ".__LINE__);
				writelogs_squid("Fatal:$hostname VERY Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
				@unlink($lockfile);
				return;
			}
	}
$array=array();
events("Starting loop for $WORKDIR");



while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$WORKDIR/$filename";
		$countDeFiles++;
		if($GLOBALS["WAIT-OVERLOAD-TIMEOUT"]>1000){events("Fatal: Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: after 1000 cycles, stopping after parsing $countDeFiles");return;}		
		usleep(200);
		
		if($EnableRemoteSyslogStatsAppliance==1){
			events("Removing $targetFile EnableRemoteSyslogStatsAppliance:$EnableRemoteSyslogStatsAppliance");
			@unlink($targetFile);
			continue;
		}
		
		events("ParseSquidLogMain::[$countDeFiles] Scanning $targetFile in line ".__LINE__);
		
		
		
		$datasql=trim(@file_get_contents($targetFile));
		if(trim($datasql)==null){
			events("ParseSquidLogMain()::[$countDeFiles]Removing $targetFile 0 bytes, aborting");
			@unlink($targetFile);
			continue;
		}
		$FINAL_ARRAY=@unserialize($datasql);
		$tablehour="squidhour_". date("YmdH",filemtime($targetFile));
		if(is_array($FINAL_ARRAY)){
			if(!isset($FINAL_ARRAY["xtime"])){$FINAL_ARRAY["xtime"]=0;}
			if($FINAL_ARRAY["xtime"]>0){$tablehour="squidhour_". date("YmdH",$FINAL_ARRAY["xtime"]);}
		}
			
		if(!is_array($FINAL_ARRAY)){
			events("ParseSquidLogMain()::[$countDeFiles] $targetFile not an array()");
			if(strpos($datasql, "')")>0){
				if($GLOBALS["VERBOSE"]){echo $datasql."\n";}
				$array["TABLES"][$tablehour][]=ParseSquidLogMain_sql_toarray($targetFile);
				@unlink($targetFile);
				$c++;
				if($c>800){
					events("ParseSquidLogMain()::[$countDeFiles] 800 rows ->  Inject into ".count($array["TABLES"])." tables...");
					inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;}
				continue;
			}else{
				events("Fatal... unlink $targetFile");
				@unlink($targetFile);
				continue;
			}	
		}
		

		while (list ($key, $val) = each ($FINAL_ARRAY) ){
			$val=str_replace("'", "`", $val);
			$val=stripslashes($val);
			$FINAL_ARRAY[$key]=addslashes($val);
		}
		
		
		$sitename=$FINAL_ARRAY["sitename"];
		$uri=$FINAL_ARRAY["uri"];
		$TYPE=$FINAL_ARRAY["TYPE"];
		$REASON=$FINAL_ARRAY["REASON"];
		$CLIENT=$FINAL_ARRAY["CLIENT"];
		$date=$FINAL_ARRAY["date"];
		$zMD5=$FINAL_ARRAY["zMD5"];
		$site_IP=$FINAL_ARRAY["site_IP"];
		$Country=GetCountry($sitename);
		$size=$FINAL_ARRAY["size"];
		$username=$FINAL_ARRAY["username"];
		$cached=$FINAL_ARRAY["cached"];
		$mac=$FINAL_ARRAY["mac"];
		$hostname=$FINAL_ARRAY["hostname"];	

		
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if($mac==null){$mac=GetMacFromIP($CLIENT);}
		if($username=="-"){$username=null;}
		
		if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
		if($hostname==null){$hostname=GetComputerName($CLIENT);}		
		if(strlen($username)<3){$username=null;}
		if(strlen($hostname)<3){
			events("Fatal: bad hostname `$hostname` client=`$CLIENT`");
			$hostname=null;
			$hostname=GetComputerName($CLIENT);
			events("Fatal: New hostname `$hostname` client=`$CLIENT`");
			
		}
		if(strlen($hostname)<3){$hostname=null;}
		
		if($username<>null){
			$GLOBALS["USERSCACHE"][$CLIENT]=$username;
			if($mac<>null){
				$GLOBALS["USERSCACHE"][$mac]=$username;
			}
		}else{
			if(isset($GLOBALS["USERSCACHE"][$CLIENT])){
				if($GLOBALS["USERSCACHE"][$CLIENT]<>null){$username=$GLOBALS["USERSCACHE"][$CLIENT];}
			}
			if($username==null){
				if(isset($GLOBALS["USERSCACHE"][$mac])){
					if($GLOBALS["USERSCACHE"][$mac]<>null){$username=$GLOBALS["USERSCACHE"][$mac];}
				}
			}
			
		}

		
	
		
		$sql="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
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
		if($c>800){inject_array($array["TABLES"]);
			unset($array["TABLES"]);$c=0;}
			if(!$GLOBALS["VERBOSE"]){
				if(systemMaxOverloaded()){
					events("Fatal: Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: injecting ". count($array["TABLES"]) ." And stopping");
					inject_array($array["TABLES"]);unset($array["TABLES"]);$c=0;
					events("Fatal:$hostname VERY Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: die(); on Line: ".__LINE__);
					writelogs_squid("Fatal:$hostname VERY Overloaded system, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
					@file_put_contents("/etc/squid3/USERSCACHE.DB", serialize($GLOBALS["USERSCACHE"]));
					@unlink($lockfile);
					return;
				}
			}		
	
}
	if(count($array["TABLES"])>0){
		events("Injecting ". count($array["TABLES"]). " lines Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array($array["TABLES"]);
	}
	
	events("Closing... /var/log/artica-postfix/dansguardian-stats2 ($countDeFiles files scanned)");
	@file_put_contents("/etc/squid3/USERSCACHE.DB", serialize($GLOBALS["USERSCACHE"]));
	@unlink($lockfile);

}

function GetCountry($sitename){
	if(!isset($GLOBALS["IPs"])){$GLOBALS["IPs"]=array();}
	if(!isset($GLOBALS["COUNTRIES"])){$GLOBALS["COUNTRIES"]=array();}
	if(trim($GLOBALS["IPs"][$sitename])==null){
		$site_IP=trim(gethostbyname($sitename));
		$GLOBALS["IPs"][$sitename]=$site_IP;
	}else{
		$site_IP=$GLOBALS["IPs"][$sitename];
	}
	
	if(count($GLOBALS["IPs"])>5000){unset($GLOBALS["IPs"]);}
	if(count($GLOBALS["COUNTRIES"])>5000){unset($GLOBALS["COUNTRIES"]);}
	
	
	if(trim($GLOBALS["COUNTRIES"][$site_IP])==null){
		if(function_exists("geoip_record_by_name")){
			if($site_IP==null){$site_IP=$sitename;}
			$record = @geoip_record_by_name($site_IP);
			if ($record) {
				$Country=$record["country_name"];
				$GLOBALS["COUNTRIES"][$site_IP]=$Country;
			}
		}else{
			$geoerror="geoip_record_by_name no such function...";
		}
	}else{
		$Country=$GLOBALS["COUNTRIES"][$site_IP];
	}

	return $Country;
	
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
		
		while (list ($key, $val) = each ($array) ){
			$val=str_replace("'", "`", $val);
			$val=mysql_escape_string($val);
			$array[$key]=addslashes($val);
		}		
		
		
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
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
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
			
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=null;
	}



function inject_array($array){
	
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
		events("Injecting -> inject_array_remote() Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array_remote($array);
		return;
	}
	events("inject_array::Injecting -> inject direct on Line: ".__LINE__);
	$q=new mysql_squid_builder();
	$q->CheckTables();
	if($q->MysqlFailed){events_tail("squid-injector:: Mysql connection failed, aborting.... Line: ".__LINE__);
	inject_failed($array);}
	
	while (list ($table, $contentArray) = each ($array) ){
		if(preg_match("#squidhour_([0-9]+)#",$table,$re)){$q->TablePrimaireHour($re[1]);}
		$prefixsql="INSERT IGNORE INTO $table (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`zDate`,`zMD5`,`remote_ip`,`country`,`QuerySize`,`uid`,`cached`,`MAC`,`hostname`) VALUES ";
		$sql="$prefixsql".@implode(",",$contentArray);
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		events("inject_array::Injecting -> table `$table` -> injecting ".count($contentArray)." rows :".__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events("inject_array::Injecting -> ERROR: $q->mysql_error : in line of main:".__LINE__);
			echo "ERROR: $q->mysql_error\n";
		inject_failed($array);return;}
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
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		$text="$text ($sourcefunction::$sourceline)";
	}
	
	
	events_tail($text);}

function events_tail($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		//if($GLOBALS["VERBOSE"]){echo "$text\n";}
		$pid=@getmypid();
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}	
