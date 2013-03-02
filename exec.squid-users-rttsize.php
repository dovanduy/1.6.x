<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
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
if($argv[1]=="--now"){UsersSizeByHour();}


UsersSizeByHour();
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
	
	$classParse=new squid_tail();
	$RTTSIZEPATH="/var/log/artica-postfix/squid-RTTSize/".date("YmdH");
	
	if(!is_file($RTTSIZEPATH)){
		events("$RTTSIZEPATH no such file...");
		UserSizeRTT_oldfiles();
		main_table();
		return;
	}
	
	$q=new mysql_squid_builder();
	$q->CreateUserSizeRTTTable();
	if(!$q->TABLE_EXISTS("UserSizeRTT")){
		events("Fatal UserSizeRTT no such table, die()");
		ufdbguard_admin_events("Fatal UserSizeRTT no such table, die();",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}	
	
	$RTTSIZEARRAY=unserialize(@file_get_contents($RTTSIZEPATH));
	$date=date('Y-m-d H:00:00');
	
	$sql="DELETE FROM UserSizeRTT WHERE zdate='$date'";
	$q->QUERY_SQL($sql);
	
	if(count($RTTSIZEARRAY["UID"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`uid`,`size`,`hits`) VALUES ";
		while (list ($username,$array) = each ($RTTSIZEARRAY["UID"]) ){
			
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($username.$date);
			echo $username." HITS:$hits SIZE:$size\n";
			$f[]="('$md5','$date','$username','$size','$hits')";
		}
		
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){
				ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
		}
		
	}
	
	if(count($RTTSIZEARRAY["IP"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`ipaddr`,`hostname`,`size`,`hits`) VALUES ";
		while (list ($ip,$array) = each ($RTTSIZEARRAY["IP"]) ){
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($ip.$date);
			$hostname=$classParse->GetComputerName($ip);
			echo $ip."/$hostname HITS:$hits SIZE:$size\n";
			$f[]="('$md5','$date','$ip','$hostname','$size','$hits')";
		}
	
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){
				ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
		}
	
	}

	if(count($RTTSIZEARRAY["MAC"])>0){
		$f=array();
		$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`zdate`,`MAC`,`size`,`hits`) VALUES ";
		while (list ($mac,$array) = each ($RTTSIZEARRAY["MAC"]) ){
	
			$hits=$array["HITS"];
			$size=$array["SIZE"];
			$md5=md5($ip.$date);
			
			echo "$mac HITS:$hits SIZE:$size\n";
			$f[]="('$md5','$date','$mac','$size','$hits')";
		}
	
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){
				ufdbguard_admin_events("Fatal: $q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
		}
	
	}	
	UserRTT_SIZE_DAY();
	UserSizeRTT_oldfiles();
	main_table();
	
	//$prefix="INSERT IGNORE INTO UserSizeRTT (`zMD5`,`uid`,`zdate`,`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`) VALUES";
	//if($mac==null){$mac=$this->GetMacFromIP($ip);}
	
	
}

function UserRTT_SIZE_DAY(){
	
	$sql="SELECT uid, DATE_FORMAT( zdate, '%Y-%m-%d' ) AS tday, DATE_FORMAT( zdate, '%H' ) AS thour , DATE_FORMAT( zdate, '%Y%m%d' ) AS tablesuffix, ipaddr, hostname, account, MAC, UserAgent, size, hits
	FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )";
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL($sql);
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
		$f[$tablename][]="('$zMD5','$uid','$zdate','$ipaddr','$hostname','$account','$MAC','$UserAgent','$size','$hits','$hour')";
		
		
	}
	
	
	if(count($f)>0){
		if(UserRTT_SIZE_DAY_inject($f)){
			$q->QUERY_SQL("DELETE FROM UserSizeRTT WHERE DATE_FORMAT( zdate, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )");
		}
	
	}
	
}

function UserRTT_SIZE_DAY_inject($array){
	

	
	
	$q=new mysql_squid_builder();
	while (list ($tablename, $rows) = each ($array)){
		if(!$q->CreateUserSizeRTT_day($tablename)){ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`uid`,`zdate`,
		`ipaddr`,`hostname`,`account`,`MAC`,`UserAgent`,`size`,`hits`,`hour`) VALUES ".@implode(",", $rows);
		if(!$q->QUERY_SQL($sql)){
			ufdbguard_admin_events("$tablename: Query failed {$q->mysql_error}",__FUNCTION__,__FILE__,__LINE__,"stats");return;
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