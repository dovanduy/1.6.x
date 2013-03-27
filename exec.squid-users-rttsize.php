<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');

if($argv[1]=="--main-table"){main_table();exit;}
if($argv[1]=="--now"){UsersSizeByHour();ParseQueue();}


UsersSizeByHour();
ParseQueue();

UserRTT_SIZE_DAY();
UserSizeRTT_oldfiles();
main_table();


function UsersSizeByHour(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$oldpid=@file_get_contents($pidfile);
	
	$unix=new unix();
	$mypid=getmypid();
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		if($oldpid<>$mypid){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			events("Already executed pid $oldpid since {$time}mn-> DIE");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid since {$time}mn\n";}
			die();
		}
	}
	
	
	events("Starting pid [$mypid]...");
	@file_put_contents($pidfile,$mypid);
	
	$timefile=$unix->file_time_min($pidtime);
	events("Timelock:$timefile Mn");
	
	if(!$GLOBALS["VERBOSE"]){
		if($timefile<10){
			events("Only each 10mn :current {$timefile}Mn");
			if($GLOBALS["VERBOSE"]){echo "Only each 10mn\n";}
			return;
		}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	
	// VF /var/log/artica-postfix/squid/queues/RTTSize;
	$q=new mysql_squid_builder();
	$q->CreateUserSizeRTTTable();
	$RTTSIZEPATH="/var/log/artica-postfix/squid-RTTSize/".date("YmdH");
	
	if(!is_file($RTTSIZEPATH)){
		events("$RTTSIZEPATH no such file...");
		events("UserSizeRTT_oldfiles()");
		UserSizeRTT_oldfiles();
		events("main_table()");
		main_table();
		return;
	}
	
	
	if(!$q->TABLE_EXISTS("UserSizeRTT")){
		events("Fatal UserSizeRTT no such table, die()");
		ufdbguard_admin_events("Fatal UserSizeRTT no such table, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	
	events("$RTTSIZEPATH = ". FormatBytes(@filesize($RTTSIZEPATH)/1024));
	$RTTSIZEARRAY=unserialize(@file_get_contents($RTTSIZEPATH));
	RTTSizeArray($RTTSIZEARRAY);

	
	//$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`uid`,`zdate`,`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`) VALUES";
	//if($mac==null){$mac=$this->GetMacFromIP($ip);}
	
	
}

function ParseQueue(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".ParseQueue.pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".ParseQueue.time";
	$oldpid=@file_get_contents($pidfile);
	
	$unix=new unix();
	$mypid=getmypid();
	
	if($unix->process_exists($oldpid,basename(__FILE__))){
		if($oldpid<>$mypid){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			events("Already executed pid $oldpid since {$time}mn-> DIE");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid since {$time}mn\n";}
			die();
		}
	}
	
	$timeMin=$unix->file_time_min($pidtime);
	if(!$GLOBALS["VERBOSE"]){
	if($timeMin<3){
		events("Need to wait 3mn");
		return;
	}
	}
	
	events("ParseQueue(): Starting pid [$mypid]...");
	@file_put_contents($pidfile,$mypid);
	@unlink($pidtime);
	@file_put_contents($pidtime, time());	
	
	$dirs=$unix->dirdir("/var/log/artica-postfix/squid/queues");
	while (list ($directory,$array) = each ($dirs) ){
		events("ParseQueue(): Scanning $directory");
		$dirs2=$unix->dirdir($directory);
		events("ParseQueue(): Scanning $directory ". count($dirs2)." items");
		if(count($dirs2)==0){
			events("ParseQueue(): remove $directory");
			@rmdir($directory);
			continue;
		}
		if(is_dir("$directory/RTTSize")){ParseRTTSizeDir("$directory/RTTSize");}
		
	}
	events("ParseQueue():: Finish.. ".__LINE__);

}

function ParseRTTSizeDir($dir){
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".ParseQueue.time";
	$unix=new unix();
	events("ParseRTTSizeDir():: count files on $dir Line: ".__LINE__);
	$countDefile=$unix->COUNT_FILES($dir);
	events("ParseRTTSizeDir():: $dir  $countDefile files on Line: ".__LINE__);
	if($countDefile==0){
		events("ParseRTTSizeDir():: $dir:  remove... on Line: ".__LINE__);
		@rmdir($dir);
		return;
	}
	events("ParseRTTSizeDir(): scanning $dir");
	
	$q=new mysql_squid_builder();
	$q->CreateUserSizeRTTTable();
	if(!$q->TABLE_EXISTS("UserSizeRTT")){
		events("ParseRTTSizeDir():: Fatal UserSizeRTT no such table, die()");
		ufdbguard_admin_events("Fatal UserSizeRTT no such table, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	if (!$handle = opendir($dir)) {
		ufdbguard_admin_events("Fatal: $dir no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	$c=0;
	$d=0;
	$D=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$dir/$filename";
		$arrayFile=unserialize(@file_get_contents($targetFile));
		$countDeArrayFile=count($arrayFile);
		if(!is_array($arrayFile)){@unlink($targetFile);continue;}
		while (list ($index,$RTTSIZEARRAY) = each ($arrayFile) ){
			$d++;
			$D++;
			if($d>500){
				events("RTTSizeArray():: $countDeArrayFile/$D items...(". basename($targetFile).")");
				$d=0;
			}
			
			
			RTTSizeArray($RTTSIZEARRAY,"$countDeArrayFile/$D");
			@file_put_contents($pidtime, time());
			usleep(100);
		}
		
		if(!RTTSizeInjectArray()){continue;}
		
		$c++;
		$D=0;
		@unlink($targetFile);
		
	}
			
	if($c>0){events("ParseRTTSizeDir($dir):: $c deleted files...");}
	
}

function RTTSizeInjectArray(){
	$q=new mysql_squid_builder();
	while (list ($prefix,$rows) = each ($GLOBALS["QUERIES_RTT"]) ){
		if(count($rows)<2){continue;}
		events("RTTSizeInjectArray():: ".count($rows). " items");
		$sql=$prefix.@implode(",", $rows);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events("RTTSizeInjectArray() $q->mysql_error");
			return false;
		}
	}
	return true;
}

function RTTSizeArray($RTTSIZEARRAY,$dir=null){
	
	$classParse=new squid_tail();
	
	//events("RTTSizeArray($dir):: ". count($RTTSIZEARRAY). " items");
	
	
	if(count($RTTSIZEARRAY["UID"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`uid`,`size`,`hits`) VALUES ";
		while (list ($username,$array) = each ($RTTSIZEARRAY["UID"]) ){
			$time=$array["xtime"];$date=date("Y-m-d H:i:s",$time);
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($username.$date);
			echo $username." HITS:$hits SIZE:$size\n";
			$GLOBALS["QUERIES_RTT"][$prefix][]="('$md5','$date','$username','$size','$hits')";
		}
	
	}
	
	if(count($RTTSIZEARRAY["IP"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`ipaddr`,`hostname`,`size`,`hits`) VALUES ";
		while (list ($ip,$array) = each ($RTTSIZEARRAY["IP"]) ){
			$time=$array["xtime"];$date=date("Y-m-d H:i:s",$time);
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($ip.$date);
			$hostname=$classParse->GetComputerName($ip);
			echo $ip."/$hostname HITS:$hits SIZE:$size\n";
			$GLOBALS["QUERIES_RTT"][$prefix]="('$md5','$date','$ip','$hostname','$size','$hits')";
		}
	
			
	}
	
	if(count($RTTSIZEARRAY["MAC"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`MAC`,`size`,`hits`) VALUES ";
		while (list ($mac,$array) = each ($RTTSIZEARRAY["MAC"]) ){
			$time=$array["xtime"];$date=date("Y-m-d H:i:s",$time);
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($ip.$date);
			echo "$mac HITS:$hits SIZE:$size\n";
			$GLOBALS["QUERIES_RTT"][$prefix][]="('$md5','$date','$mac','$size','$hits')";
		}
	
	}
	
	
}



function UserRTT_SIZE_DAY($day=null){
	$GLOBALS["Q"]=new mysql_squid_builder();
	events("UserRTT_SIZE_DAY():: Starting.. ".__LINE__);
	
	$GLOBALS["Q"]->QUERY_SQL("DELETE FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) = '1970-01-01'");
	
	
	$sql="SELECT uid, DATE_FORMAT( zdate, '%Y-%m-%d' ) AS tday, DATE_FORMAT( zdate, '%H' ) AS thour , 
			DATE_FORMAT( zdate, '%Y%m%d' ) AS tablesuffix, ipaddr, hostname, account, MAC, UserAgent, size, hits
	FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )";
	
	if($day<>null){
		$sql="SELECT uid, DATE_FORMAT( zdate, '%Y-%m-%d' ) AS tday, DATE_FORMAT( zdate, '%H' ) AS thour ,
			DATE_FORMAT( zdate, '%Y%m%d' ) AS tablesuffix, ipaddr, hostname, account, MAC, UserAgent, size, hits
			FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) = '$day'";		
	}
	
	
	$GLOBALS["Q"]->CreateUserSizeRTTTable();
	if(!$GLOBALS["Q"]->TABLE_EXISTS("UserSizeRTT")){
		events("Fatal UserSizeRTT no such table, die()");
		ufdbguard_admin_events("Fatal UserSizeRTT no such table, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	
	$Allrow=$GLOBALS["Q"]->COUNT_ROWS("UserSizeRTT");
		if($day==null){
			if($Allrow>1000000){
				events("UserRTT_SIZE_DAY():: Too Many items ($Allrow), get rows by elemnts...".__LINE__);
				$sql="SELECT DATE_FORMAT( zdate, '%Y-%m-%d' ) AS tday FROM UserSizeRTT GROUP BY tday ORDER BY tday";
			
				$results=$GLOBALS["Q"]->QUERY_SQL($sql);
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					UserRTT_SIZE_DAY($ligne["tday"]);
				}
				
			}
	}
	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	
	events("UserRTT_SIZE_DAY():: ".mysql_num_rows($results)." result(s)".__LINE__);
	
	if(mysql_num_rows($results)==0){return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename="UserSizeD_{$ligne["tablesuffix"]}";
		$uid=$ligne["uid"];
		$ipaddr=$ligne["ipaddr"];
		$hostname=$ligne["hostname"];
		$account=$ligne["account"];
		$MAC=$ligne["MAC"];
		$zdate=$ligne["tday"];
		$hour=$ligne["thour"];
		$UserAgent=$ligne["UserAgent"];
		$size=$ligne["size"];
		$hits=$ligne["hists"];
		$zMD5=md5($tablename.$uid.$ipaddr.$hostname.$MAC.$hour);
		
		if($tablename=="UserSizeD_19700101"){
		
			continue;
		}
		$f[$tablename][]="('$zMD5','$uid','$zdate','$ipaddr','$hostname','$account','$MAC','$UserAgent','$size','$hits','$hour')";
		
		if(count($f[$tablename])>5000){
			
			if(!UserRTT_SIZE_DAY_inject($f)){
				events("UserRTT_SIZE_DAY():: -> UserRTT_SIZE_DAY_inject Failed  line:" .__LINE__);
				return;
			}
			$f=array();
		}
		
	}
	
	
	if(count($f)>0){
		events("UserRTT_SIZE_DAY():: -> UserRTT_SIZE_DAY_inject for ".count($f)." elements line:" .__LINE__);
		if(UserRTT_SIZE_DAY_inject($f)){
			if($day==null){
				$GLOBALS["Q"]->QUERY_SQL("DELETE FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )");
			}else{
				events("UserRTT_SIZE_DAY():: DELETE FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) = '$day' line:" .__LINE__);
				$GLOBALS["Q"]->QUERY_SQL("DELETE FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) = '$day'");
			}
		}else{
			events("UserRTT_SIZE_DAY():: -> UserRTT_SIZE_DAY_inject Failed  line:" .__LINE__);
		}
	
	}
	
}

function UserRTT_SIZE_DAY_inject($array){
	
	while (list ($tablename, $rows) = each ($array)){
		$GLOBALS["UserRTT_SIZE_DAY_inject"]=$GLOBALS["UserRTT_SIZE_DAY_inject"]+count($rows);
		
		events("UserRTT_SIZE_DAY_inject():: -> $tablename for ".count($rows)." total {$GLOBALS["UserRTT_SIZE_DAY_inject"]} line:" .__LINE__);
		
		if(!$GLOBALS["Q"]->CreateUserSizeRTT_day($tablename)){
			events("UserRTT_SIZE_DAY_inject():: -> CreateUserSizeRTT_day() failed  line:" .__LINE__);
			ufdbguard_admin_events("$tablename: Query failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
		$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
		`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
		
		if(!$GLOBALS["Q"]->QUERY_SQL($sql)){
			events("UserRTT_SIZE_DAY_inject():: -> MySQL error  line:" .__LINE__);
			ufdbguard_admin_events("$tablename: Query failed {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
		
	}
	
	return true;
	
}

function main_table(){
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	if($unix->file_time_min($timefile)<300){return;}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$q=new mysql_squid_builder();
	$sql="SELECT tablename, zDate, DATE_FORMAT( zDate, '%Y%m%d' ) AS tablesuffix
FROM tables_day
WHERE MembersCount=0
AND DATE_FORMAT( zDate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )";
	$results=$q->QUERY_SQL($sql);
	if(mysql_numrows($results)==0){return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablesuffix=$ligne["tablesuffix"];
		$tablename="UserSizeD_$tablesuffix";
		if(!$q->TABLE_EXISTS($tablename)){continue;}
		$count=main_table_exec("UserSizeD_$tablesuffix");
		if($count>0){
			$q->QUERY_SQL("UPDATE tables_day SET MembersCount=$count WHERE tablename='{$ligne["tablename"]}'");
		}
		
	}
	
	
}

function main_table_exec($tablename){
	
	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	
	$sql="SELECT MAC FROM `$tablename` GROUP BY MAC HAVING LENGTH(MAC)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	

	$sql="SELECT COUNT(ipaddr) as tcount FROM $tablename WHERE LENGTH(ipaddr)>0";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}
	
	$sql="SELECT COUNT(hostname) as tcount FROM $tablename WHERE LENGTH(hostname)>0";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>1){return $count;}


	
}


function UserSizeRTT_oldfiles(){
	
	if (!$handle = opendir("/var/log/artica-postfix/squid-RTTSize")) {
		ufdbguard_admin_events("Fatal: /var/log/artica-postfix/squid-RTTSize no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	
	
	
	
	$q=new mysql_squid_builder();
	$classParse=new squid_tail();
	$CurrentFile=date("YmdH");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: scanning /var/log/artica-postfix/squid-RTTSize\n";}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if($filename==$CurrentFile){continue;}
		
		$targetFile="/var/log/artica-postfix/squid-RTTSize/$filename";	
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $targetFile\n";}
		$time=filemtime($targetFile);
		$tablesuffix=date("Ymd",$time);
		$tablename="UserSizeD_$tablesuffix";
		if(!$q->CreateUserSizeRTT_day($tablename)){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		
		$RTTSIZEARRAY=unserialize(@file_get_contents($targetFile));
		$Hour=date('H',$time);
		$date=date("Y-m-d H:00:00",$time);
		
		//$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
		//`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
		
		if(count($RTTSIZEARRAY["UID"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`uid`,`size`,`hits`,`hour`) VALUES ";
			
			while (list ($username,$array) = each ($RTTSIZEARRAY["UID"]) ){
					
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$username$date$Hour");
				echo $username." HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$username','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}
		
		if(count($RTTSIZEARRAY["IP"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`ipaddr`,`hostname`,`size`,`hits`,`hour`) VALUES ";
			while (list ($ip,$array) = each ($RTTSIZEARRAY["IP"]) ){
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$ip$date$Hour");
				$hostname=$classParse->GetComputerName($ip);
				echo $ip."/$hostname HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$ip','$hostname','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}
		
		if(count($RTTSIZEARRAY["MAC"])>0){
			$f=array();
			$prefix="INSERT IGNORE INTO `$tablename` (`zMD5`,`zdate`,`MAC`,`size`,`hits`,`hour`) VALUES ";
			while (list ($mac,$array) = each ($RTTSIZEARRAY["MAC"]) ){
		
				$hits=$array["HITS"];
				$size=$array["SIZE"];
				$md5=md5("$mac$date$Hour");
					
				echo "$mac HITS:$hits SIZE:$size\n";
				$f[]="('$md5','$date','$mac','$size','$hits','$Hour')";
			}
		
			if(count($f)>0){
				$q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
			}
		
		}	

		@unlink($targetFile);
		
		
	}
	
	
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


	events_tail($text);
}

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