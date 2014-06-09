<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($GLOBALS["VERBOSE"]){echo "DEBUG::: ".@implode(" ", $argv)."\n";}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOLOCK"]=true;
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
if(preg_match("#--nolock#",implode(" ",$argv))){$GLOBALS["NOLOCK"]=true;}

ParseMainDir();

function ParseMainDir(){
	$unix=new unix();
	$mypid=getmypid();
	$kill=$unix->find_program("kill");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pidtime_hour="/etc/artica-postfix/pids/".basename(__FILE__).".hours.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time>60){
			unix_system_kill_force($pid);
		}else{
			events("Already executed pid $pid since {$time}mn-> DIE");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
			die();
		}
	}
	
	$timeP=$unix->file_time_min($pidtime);
	if($timeP<3){
		events("Main::Line: ".__LINE__." 3Mn minimal current: {$timeP}mn-> DIE");
		die();
	}	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile,$mypid);
	
	
	$dirs=$unix->dirdir("/var/log/artica-postfix/squid/queues");
	
	
	while (list ($directory,$array) = each ($dirs) ){
		$dirs2=$unix->dirdir($directory);
		if(count($dirs2)==0){
			events("$dirs2 0 elements, remove...",__LINE__);
			@rmdir($directory);
			continue;
		}

		if(is_dir("$directory/SearchWords")){
			events("Scanning $directory/SearchWords",__LINE__);
			ParseSubDir("$directory/SearchWords");}

	}
	
	$timeP=$unix->file_time_min($pidtime_hour);
	if($timeP>30){
		@unlink($pidtime_hour);
		@file_put_contents($pidtime_hour, time());
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid-searchwords.php --hour >/dev/null 2>&1");
	}

}

function ParseSubDir($dir){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$countDefile=$unix->COUNT_FILES($dir);
	events("$dir -> $countDefile files on Line: ",__LINE__);
	if($countDefile==0){
		events("ParseSubDir():: $dir: no files... remove... ",__LINE__);
		@rmdir($dir);
		return;
	}
	
	$FINAL=array();
	if (!$handle = opendir($dir)) {
		events("ParseSubDir():: Fatal: $dir no such directory",__LINE__);
		return;
	}
	
	$DUSTBIN["mx.yahoo.com"]=true;
	$DUSTBIN["row.bc.yahoo.com"]=true;
	$DUSTBIN["us.bc.yahoo.com"]=true;
	$DUSTBIN["xiti.com"]=true;
	
	
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$dir/$filename";
		$arrayFile=unserialize(@file_get_contents($targetFile));
		if(!is_array($arrayFile)){@unlink($targetFile);continue;}
		
		if($GLOBALS["VERBOSE"]){echo "$targetFile\n";}
		
		while (list ($index, $array) = each ($arrayFile) ){
			$words=mysql_escape_string2(trim($array["WORDS"]));
			if($words==null){continue;}
			$sitename=$array["SITENAME"];
			$familysite=$q->GetFamilySites($sitename);
			
			if(isset($DUSTBIN[$sitename])){continue;}
			if(isset($DUSTBIN[$familysite])){continue;}
			
			$ipaddr=$array["ipaddr"];
			$zDate=$array["date"];
			$uid=mysql_escape_string2($array["uid"]);
			$MAC=mysql_escape_string2($array["mac"]);
			$hostname=mysql_escape_string2($array["hostname"]);
			$time=strtotime($zDate);
			$prefix=date("YmdH",$time);
			$zmd5=md5(serialize($array));
			
			$account=0;
			$line="('$zmd5','$sitename','$zDate','$ipaddr','$hostname','$uid','$MAC','$account','$familysite','$words')";
			if($GLOBALS["VERBOSE"]){echo "$prefix -> $line\n";}
			$f[$prefix][]=$line;
		}
		@unlink($targetFile);
		
	}
	
	if(count($f)>0){
		inject_array($f);
	}
	
}

function inject_array($array){
	$q=new mysql_squid_builder();
	while (list ($tablePrefix, $f) = each ($array) ){
		if(count($f)>0){
			$tablename="searchwords_$tablePrefix";
			if($GLOBALS["VERBOSE"]){echo "-> $tablename -> ". count($f)."\n";}
			$q->check_SearchWords_hour($tablePrefix);
			
			$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($prefix);
			if(!$q->ok){
				writelogs_squid("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
				@mkdir("/var/log/artica-postfix/searchwords-sql-errors",0755,true);
				@file_put_contents("/var/log/artica-postfix/searchwords-sql-errors/".md5($prefix), $prefix);
			}
		}				
	}

}




function events($text){
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
	@fclose($f);
}