<?php
$GLOBALS["SIMULATE"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
$GLOBALS["LOGFILE"]="/var/log/squid-import-logs.debug";
$GLOBALS["SYSTEM_INTERNAL_LOAD"]=0;


$_GET["LOGFILE"]=$GLOBALS["LOGFILE"];
$unix=new unix();
$sock=new sockets();
$EnableImportOldSquid=$sock->GET_INFO("EnableImportOldSquid");
if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
if(!$GLOBALS["SIMULATE"]){
	if($EnableImportOldSquid==0){die("Feature disabled\n\n");}
}
$GLOBALS["CLASS_UNIX"]=$unix;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if($unix->process_number_me($argv)>0){die("Already executed\n\n");}

if($argv[1]=="--remove-memory"){remove_memory_tables();die();}
if($argv[1]=="--scan"){scan();die();}
if($argv[1]=="--analyze"){analyze();die();}
if($argv[1]=="--file"){analyze_single_file($argv[2]);die();}
if($argv[1]=="--testnas"){tests_nas().killNas();die();}
if($argv[1]=="--test-nas"){tests_nas().killNas();die();}
if($argv[1]=="--all"){analyze_all();die();}
if($argv[1]=="--tosarg"){tosarg($argv[2]);die();}

events("unable to understand your query");
die();



function tests_nas(){
	$sock=new sockets();
	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";
	$SquidOldLogsNAS=unserialize(base64_decode($sock->GET_INFO("SquidOldLogsNAS")));
	if(!isset($SquidOldLogsNAS["hostname"])){return;}
	if($SquidOldLogsNAS["hostname"]==null){return;}
	
	if($GLOBALS["VERBOSE"]){
	while (list ($index, $line) = each ($SquidOldLogsNAS) ){
		echo "$index.........: $line\n";
	}
	}
	
	$mount=new mount($GLOBALS["LOGFILE"]);
	$NasFolder=$SquidOldLogsNAS["folder"];
	$NasFolder=str_replace('\\\\', '/', $NasFolder);
	if(strpos($NasFolder, "/")>0){
		$f=explode("/",$NasFolder);
		$NasFolder=$f[0];
	}
	
	$mountPoint="/mnt/SquidImportLogs";
	
	if($mount->ismounted($mountPoint)){return true;}
	
	if(!$mount->smb_mount($mountPoint,$SquidOldLogsNAS["hostname"],
			$SquidOldLogsNAS["username"],$SquidOldLogsNAS["password"],$NasFolder)){	
			if($GLOBALS["VERBOSE"]){echo $failed.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);return;}
	}
	
	if($GLOBALS["VERBOSE"]){echo $success.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);}
	return true;
	
}

function tosarg($WORKDIR){
	if(!is_dir($WORKDIR)){
		events("!!! Fatal $WORKDIR, nu such directory...");
		return;
	}
	
	$unix=new unix();
	$sarg=$unix->find_program("sarg");
	if (!$handle = opendir($WORKDIR)) { return;}	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$WORKDIR/$filename";
		echo "Analyze $targetFile\n";
		shell_exec("$sarg -l $targetFile");
	}
		
	
	
}

function killNas(){
	$sock=new sockets();
	$mount=new mount($GLOBALS["LOGFILE"]);
	$mountPoint="/mnt/SquidImportLogs";	
	if($mount->ismounted($mountPoint)){
		$mount->umount($mountPoint);
		
	}
	
}

function scan($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidfile);
		if($oldpid<100){$oldpid=null;}
	
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
			events("Already executed pid $oldpid since {$timepid}Mn");
			return;
		}	
	
		@file_put_contents($pidfile, time());
	}
	if(!tests_nas()){return;}
	$SquidOldLogsNAS=unserialize(base64_decode($sock->GET_INFO("SquidOldLogsNAS")));
	$mountPoint="/mnt/SquidImportLogs";
	$NasFolder=$SquidOldLogsNAS["folder"];
	$NasFolder=str_replace('\\', '/', $NasFolder);
	$NasFolder=str_replace('//', '/', $NasFolder);
	if(strpos($NasFolder, "/")>0){$f=explode("/",$NasFolder);unset($f[0]);$NasFolder=@implode("/", $f);}

	$WORKDIR="$mountPoint/$NasFolder";
	$WORKDIR=str_replace("//", "/", $WORKDIR);
	
	if(!is_dir($WORKDIR)){
		events("!!! Fatal $WORKDIR, nu such directory...");
		return;
	}
	
	if (!$handle = opendir($WORKDIR)) { return;}
	$countDeFiles=0;
	$array=array();
	if(!$q->FIELD_EXISTS("accesslogs_import", "lnumbers")){$q->QUERY_SQL("ALTER IGNORE TABLE `accesslogs_import` ADD `lnumbers` BIGINT UNSIGNED ,ADD INDEX( `lnumbers` )");}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$WORKDIR/$filename";
		$countDeFiles++;
		$md5=md5_file($targetFile);
		events("$targetFile MD5:$md5");
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT filename FROM accesslogs_import WHERE zmd5='$md5'"));
		if(!$q->ok){echo $q->mysql_error;killNas();return;}
		if($GLOBALS["VERBOSE"]){echo "$md5 = {$ligne["filename"]}\n";}
		
		
		$ext=$unix->file_ext($targetFile);
		if($ext=="gz"){
			@mkdir("/home/squid/wkdir",0755,true);
			if($GLOBALS["VERBOSE"]){echo "Uncompress $targetFile\n";}
			$unix->uncompress($targetFile, "/home/squid/wkdir/$filename");
			$date=GetDateOfFile("/home/squid/wkdir/$filename");
			$lnumbers=$unix->COUNT_LINES_OF_FILE("/home/squid/wkdir/$filename");
			@unlink("/home/squid/wkdir/$filename");
		}else{
			$date=GetDateOfFile($targetFile);
			$lnumbers=$unix->COUNT_LINES_OF_FILE($targetFile);
		}
		if($date==null){
			events("$targetFile = No date");
			continue;
		}
		
		if($lnumbers==0){
			events("$targetFile = No Lines");
			continue;
		}
		
		if(trim($ligne["filename"])<>null){
			$q->QUERY_SQL("UPDATE accesslogs_import SET zDate='$date', lnumbers='$lnumbers' WHERE zmd5='$md5'");
			
			if(!$q->ok){events("$q->mysql_error");killNas();return;}
			continue;
		}		
		
		$size=$unix->file_size($targetFile);
		events("Found new file to analyze $targetFile - $md5 ($date) ".round(($size/1024)/1024)."MB");
		$q->QUERY_SQL("INSERT INTO accesslogs_import (zmd5,filename,zDate,size,status,percent,lnumbers) VALUES ('$md5','$filename','$date','$size',0,0,$lnumbers)");
		if(!$q->ok){echo events("$q->mysql_error");killNas();return;}
	}
	killNas();
}

function GetDateOfFile($filename){
	$handle = @fopen($filename, "r");
	if (!$handle) {echo "Failed to open file\n";return;}
	$date=null;
	$c=0;
	while (!feof($handle)){
		$c++;
		if($c>10){return;}
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$array=parseline($buffer);
		if(count($array)>1){
			$time=$array["TIME"];
			$date=date("Y-m-d H:i:s",$time);
			@fclose($handle);
			return $date;
			break;
	
		}
	}
		
}


function parseline($buffer){
	$tail=new squid_tail();
	$return=array();
	$ipaddr=null;
	if(preg_match("#^([0-9\.]+)\s+([0-9\-]+)\s+(.*?)\s+([A-Z_]+)\/([0-9]+)\s+([0-9]+)\s+([A-Z_]+)\s+(.*?)\s+(.*?)\s+([A-Z_]+)\/(.*?)\s+#is", $buffer,$re)){
    	
    	$cached=0;
    	
    	$time=$re[1];
		$hostname=$re[3];
		$SquidCode=$re[4];
		$code_error=$re[5];
		$size=$re[6];
		$proto=$re[7];
		$uri=$re[8];
		$uid=$re[9];
		$basenameECT=$re[10];
		$remote_ip=$re[11];
		
		
		
		
		if(trim($uid)=="-"){$uid=null;}
		if(preg_match("#^[0-9\.]+$#", $hostname)){$ipaddr=$hostname;$hostname=null;}
		$Fdate=date("Y-m-d H:i:s",$time);
		$xtime=strtotime($Fdate);
		if(preg_match("#^(.+?)\\\\(.+)#", $uid,$ri)){$uid=$ri[2];}
		if($tail->CACHEDORNOT($SquidCode)){$cached=1;}	

		
		
		$return=array(
			"TIME"=>$time,
			"IPADDR"=>$ipaddr,
			"CACHED"=>$cached,
			"UID"=>$uid,
			"HOSTNAME"=>$hostname,
			"ERRCODE"=>$code_error,
			"SIZE"=>$size,
			"PROTO"=>$proto,
			"URI"=>$uri,
			"REMOTE"=>$remote_ip,
				
				
				);
		return $return;
		
	}else{
		events("no match \"$buffer\"");
	}
	if($GLOBALS["VERBOSE"]){echo "NO MATCH\n$buffer\n";}
	
}
function events($text,$line=0){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
	
		if($line>0){$sourceline=$line;}
		$text="$text ($sourcefunction::$sourceline)";
	}	
	
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/squid-import-logs.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}
function analyze_single_file($filename){
	$sock=new sockets();
	$unix=new unix();
	if(!isset($GLOBALS["squidtail"])){$GLOBALS["squidtail"]=new squid_tail();}
	if($GLOBALS["VERBOSE"]){echo $filename." -> '/' = ".strpos($filename, '/')." pos\n";}
	if(!is_file($filename)){
		if($GLOBALS["VERBOSE"]){echo $filename." no such file\n";}
		if(!tests_nas()){return;}
		$SquidOldLogsNAS=unserialize(base64_decode($sock->GET_INFO("SquidOldLogsNAS")));
		$mountPoint="/mnt/SquidImportLogs";
		$NasFolder=$SquidOldLogsNAS["folder"];
		$NasFolder=str_replace('\\', '/', $NasFolder);
		$NasFolder=str_replace('//', '/', $NasFolder);
		if(strpos($NasFolder, "/")>0){$f=explode("/",$NasFolder);unset($f[0]);$NasFolder=@implode("/", $f);}
		$targetFile="$mountPoint/$NasFolder/$filename";
		$targetFile=str_replace("//", "/", $targetFile);
	}else{
		$targetFile=$filename;
	}
	$REMOVE=false;
	$ext=$unix->file_ext($targetFile);
	
	if($GLOBALS["SIMULATE"]){echo "Simulate enabled, no MySQL events will be injected\n";}
	
	if(!$GLOBALS["SIMULATE"]){$zmd5=md5_file($targetFile);}
	@mkdir("/home/squid/wkdir",0755,true);
	if($ext=="gz"){
		$basename=$filename;
		if(count(explode('/',$basename))>0){$basename=basename($basename);}
		if($GLOBALS["VERBOSE"]){echo "Uncompress $targetFile\n";}
		$unix->uncompress($targetFile, "/home/squid/wkdir/$basename");
		$targetFile="/home/squid/wkdir/$basename";
		$REMOVE=TRUE;
	}else{
		$basename=$targetFile;
		if(count(explode('/',$basename))>0){$basename=basename($basename);}
		if($GLOBALS["VERBOSE"]){echo "Copy $targetFile -> /home/squid/wkdir/$basename\n";}
		if(!@copy($targetFile, "/home/squid/wkdir/$basename")){
			events("Unable to copy $targetFile to /home/squid/wkdir/$basename");
			return false;
		}
		$targetFile="/home/squid/wkdir/$basename";
		$REMOVE=TRUE;
	}
	
	events("_analyze_file - $targetFile");
	if(_analyze_file($targetFile,$zmd5)){
		if($REMOVE){@unlink($targetFile);}
	}
	killNas();
	
	
}

function analyze_all($aspid=false){
	$unix=new unix();
	$GLOBALS["NICE"]=$unix->EXEC_NICE();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){echo "pidTime=$pidTime\n";}
	if($GLOBALS["VERBOSE"]){echo "pidfile=$pidfile\n";}
	
	
	
	
	if(!$aspid){
		$oldpid=@file_get_contents($pidfile);
		if($oldpid<100){$oldpid=null;}
	
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
			events("Already executed pid $oldpid since {$timepid}Mn");
			return;
		}
	
		@file_put_contents($pidfile, time());
	}	
	
	if(!$GLOBALS["NOTIME"]){
		$TimeExec=$unix->file_time_min($pidTime);
		if($TimeExec<30){return;}
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	$GLOBALS["nohup"]=$unix->find_program("nohup");
	
	$sock=new sockets();
	$unix=new unix();
	$SquidOldLogsNAS=unserialize(base64_decode($sock->GET_INFO("SquidOldLogsNAS")));
	$mountPoint="/mnt/SquidImportLogs";
	$NasFolder=$SquidOldLogsNAS["folder"];
	$NasFolder=str_replace('\\', '/', $NasFolder);
	$NasFolder=str_replace('//', '/', $NasFolder);
	if(strpos($NasFolder, "/")>0){$f=explode("/",$NasFolder);unset($f[0]);$NasFolder=@implode("/", $f);}
	
	events("Ressources: $mountPoint/$NasFolder");
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("accesslogs_import")){events("accesslogs_import no such table");return;}
	if($q->COUNT_ROWS("accesslogs_import")==0){if($GLOBALS["VERBOSE"]){echo "accesslogs_import no row\n";}return;}
	$results=$q->QUERY_SQL("SELECT * FROM accesslogs_import WHERE status<3 ORDER BY zDate");
	if(mysql_num_rows($results)==0){killNas();if($GLOBALS["VERBOSE"]){echo "accesslogs_import all done\n";}return;}
	
	if(!tests_nas()){events("NAS is unavailable");return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$filename=$ligne["filename"];
		$targetFile="$mountPoint/$NasFolder/$filename";
		events("Scanning $targetFile");
		if($GLOBALS["VERBOSE"]){echo "Scanning $targetFile\n";}
		$targetFile=str_replace("//", "/", $targetFile);
		analyze_single_file($filename);
		if(system_is_overloaded()){killNas();return;}
	}
	
	
	$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$unix->LOCATE_PHP5_BIN()." ";
	shell_exec("$prefixcmd ".dirname(__FILE__)."/exec.squid.stats.hours.php --nocheck >/dev/null 2>&1 &");
	scan(true);
	killNas();
	
}



function _analyze_file($filepath,$zmd5){
	if(!is_file($filepath)){
		events("$filepath no such file");
		return false;
	}
	$sock=new sockets();
	$unix=new unix();
	$EnableImportWithSarg=$sock->GET_INFO("EnableImportWithSarg");
	if(!is_numeric($EnableImportWithSarg)){$EnableImportWithSarg=1;}
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$basename=basename($filepath);
	$timeStart=time();
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$TimeOfFile=strtotime(GetDateOfFile($filepath));
	
	$ContainerDir="/var/log/artica-postfix/squid/queues/".date("Y-m-d-h",$TimeOfFile);
	@mkdir($ContainerDir,0755,true);
	$handle = @fopen($filepath, "r");
	if (!$handle) {events("Failed to open file $filepath");echo "Failed to open file\n";return;}
	
	
	
	$c=0;
	
	$max=$unix->COUNT_LINES_OF_FILE($filepath);
	$GLOBALS["BUFFER_FILE_ANALYZED"]=$filepath;
	events("$filepath $max lines");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM accesslogs_import WHERE zmd5='$zmd5'"));
	$FileStatus=$ligne["status"];
	
	if(!$GLOBALS["SIMULATE"]){
		if($ligne["filename"]==null){
			echo "$filepath: $zmd5 did not match expected md5\n";
			@fclose($handle);
			return;
		}
	}
	if($GLOBALS["VERBOSE"]){
		echo "Container: $ContainerDir\n";
		echo "Status...: $FileStatus\n";
		echo "Lines....: $max\n";
	}
	
	if(!$GLOBALS["FORCE"]){
		if($FileStatus==3){
			events("$filepath already analyzed, skip it...");
			@fclose($handle);
			return true;
		}
	}
	
	if($EnableImportWithSarg==1){
		$u=null;
		$nice=EXEC_NICE();
		$sarg=$unix->find_program("sarg");
		$php=$unix->LOCATE_PHP5_BIN();
		$squid=new squidbee();
		if($squid->LDAP_AUTH==1){$usersauth=true;}
		if($squid->LDAP_EXTERNAL_AUTH==1){$usersauth=true;}
		if($usersauth){echo "Starting......: ".date("H:i:s")." Sarg, user authentification enabled\n";$u=" -i ";}
		
		
		if(is_file($sarg)){
			shell_exec("$php /usr/share/artica-postfix/exec.sarg.php --conf >/dev/null 2>&1");
			exec("$nice$sarg $u-f /etc/squid3/sarg.conf -l $filepath -o \"$SargOutputDir\" 2>&1",$sargR);
			while (list ($index, $line) = each ($sargR) ){
				events("Sarg: $line\n");
			}
		}
		
	}
	
	
	
	$percent_ret=0;
	while (!feof($handle)){	
		$c++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$array=parseline($buffer);
		if(count($array)==0){continue;}
		$ip=null;
		$user=null;
		
		$xtime=$array["TIME"];
		$ip=$array["IPADDR"];
		$user=$array["UID"];
		$code_error=$array["ERRCODE"];
		$size=$array["SIZE"];
		$uri=$array["URI"];
		$cached=$array["CACHED"];
		// $q->QUERY_SQL("INSERT INTO accesslogs_import (zmd5,filename,zDate,size,status,percent) VALUES ('$md5','$filename','$date','$size',0,0)");
		$HOSTNAME=$array["HOSTNAME"];
		
		if(is_numeric($user)){
			echo "\n\n\n****************\n\nNumeric user:$user\n$buffer\n\n";
			die();
		}
		
		if($ip==null){if($HOSTNAME<>null){$ip=$HOSTNAME;}}
		$GLOBALS["BUFFER_ANALYZED"]=$buffer;
		
		$GLOBALS["squidtail"]->Builsql($ip,$user,$uri,$code_error,$size,$xtime,$cached,null,$xtime);
		if($GLOBALS["SIMULATE"]){
			continue;
		}
	
			$percent=($c/$max)*100;
			$percent=round($percent);
			if($percent<>$percent_ret){
				if($GLOBALS["VERBOSE"]){echo "****************** $percent% ********************\n";}
				$percent_ret=$percent;
				events("{$percent_ret}% ".count($GLOBALS["squidtail"]->GLOBAL_QUEUE)." in memory - $filepath");
				$q->QUERY_SQL("UPDATE accesslogs_import SET percent='$percent',status=1 WHERE zmd5='$zmd5'");
			}
		
	
		if(count($GLOBALS["squidtail"]->GLOBAL_QUEUE)>2000){
			events("analyze_file()::$basename::{$percent}% GLOBAL_RTTSIZE......: ".count($GLOBALS["squidtail"]->GLOBAL_RTTSIZE) ." items...",__LINE__);
			events("analyze_file()::$basename::{$percent}% GLOBAL_PAGEKEEPER...: ".count($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER) ." items...",__LINE__);
			events("analyze_file()::$basename::{$percent}% GLOBAL_YOUTUBE......: ".count($GLOBALS["squidtail"]->GLOBAL_YOUTUBE) ." items...",__LINE__);
			events("analyze_file()::$basename::{$percent}% GLOBAL_SQUIDUSERS...: ".count($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS) ." items...",__LINE__);
			events("analyze_file()::$basename::{$percent}% GLOBAL_SEARCHWORDS..: ".count($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS) ." items...",__LINE__);
				
				
			PURGE_GLOBAL_QUEUE($GLOBALS["squidtail"]->GLOBAL_QUEUE);
			$GLOBALS["squidtail"]->GLOBAL_QUEUE=array();
				
				
			if(count($GLOBALS["squidtail"]->GLOBAL_RTTSIZE)>500){
				@mkdir("$ContainerDir/RTTSize",0755,true);
				@file_put_contents("$ContainerDir/RTTSize/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_RTTSIZE)),
				serialize($GLOBALS["squidtail"]->GLOBAL_RTTSIZE));
				$GLOBALS["squidtail"]->GLOBAL_RTTSIZE=array();
				$RTTSIZE=true;
			}
				
			if(count($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER)>500){
				@mkdir("$ContainerDir/PageKeeper",0755,true);
				@file_put_contents("$ContainerDir/PageKeeper/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER)),
				serialize($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER));
				$GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER=array();
				$PAGEKEEP=true;
			}
			if(count($GLOBALS["squidtail"]->GLOBAL_YOUTUBE)>500){
				@mkdir("$ContainerDir/Youtube",0755,true);
				$md5=md5(serialize($GLOBALS["squidtail"]->GLOBAL_YOUTUBE));
				youtube_events("Saving queue:(2000) $ContainerDir/Youtube/".$md5, __LINE__);
				@file_put_contents("$ContainerDir/Youtube/".$md5,
				serialize($GLOBALS["squidtail"]->GLOBAL_YOUTUBE));
				$GLOBALS["squidtail"]->GLOBAL_YOUTUBE=array();
				$YOUTUBE=true;
			}
			if(count($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS)>500){
				@mkdir("$ContainerDir/Members",0755,true);
				@file_put_contents("$ContainerDir/Members/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS)),
				serialize($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS));
				$GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS=array();
		
			}
				
			if(count($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS)>500){
				@mkdir("$ContainerDir/SearchWords",0755,true);
				@file_put_contents("$ContainerDir/SearchWords/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS)),
				serialize($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS));
				$GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS=array();
					
			}
				
		} // PURGE OVER 2000
	
	
	} // END GLOBAL LOOP
	
	@fclose($handle);
	
	PURGE_GLOBAL_QUEUE($GLOBALS["squidtail"]->GLOBAL_QUEUE);
	events("analyze_file()::$basename::  Container.........: `$ContainerDir` ",__LINE__);
	events("analyze_file()::$basename::  GLOBAL_RTTSIZE....: ".count($GLOBALS["squidtail"]->GLOBAL_RTTSIZE) ." items...",__LINE__);
	events("analyze_file()::$basename::  GLOBAL_PAGEKEEPER.: ".count($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER) ." items...",__LINE__);
	events("analyze_file()::$basename::  GLOBAL_YOUTUBE....: ".count($GLOBALS["squidtail"]->GLOBAL_YOUTUBE) ." items...",__LINE__);
	events("analyze_file()::$basename::  GLOBAL_SQUIDUSERS.: ".count($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS) ." items...",__LINE__);
	events("analyze_file()::$basename::  GLOBAL_SEARCHWORDS: ".count($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS) ." items...",__LINE__);
	$q->QUERY_SQL("UPDATE accesslogs_import SET percent='100',status='3' WHERE zmd5='$zmd5'");
	
	if(count($GLOBALS["squidtail"]->GLOBAL_RTTSIZE)>0){
		@mkdir("$ContainerDir/RTTSize",0755,true);
		@file_put_contents("$ContainerDir/RTTSize/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_RTTSIZE)),
		serialize($GLOBALS["squidtail"]->GLOBAL_RTTSIZE));
		$RTTSIZE=true;
	}
	
	if(count($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER)>0){
		@mkdir("$ContainerDir/PageKeeper",0755,true);
		@file_put_contents("$ContainerDir/PageKeeper/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER)),
		serialize($GLOBALS["squidtail"]->GLOBAL_PAGEKEEPER));
		$PAGEKEEP=true;
	}
	if(count($GLOBALS["squidtail"]->GLOBAL_YOUTUBE)>0){
		@mkdir("$ContainerDir/Youtube",0755,true);;
		$md5=md5(serialize($GLOBALS["squidtail"]->GLOBAL_YOUTUBE));
		youtube_events("Saving queue: $ContainerDir/Youtube/".$md5, __LINE__);
		@file_put_contents("$ContainerDir/Youtube/".$md5,
		serialize($GLOBALS["squidtail"]->GLOBAL_YOUTUBE));
		$YOUTUBE=true;
	}
	if(count($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS)>0){
		@mkdir("$ContainerDir/Members",0755,true);
		@file_put_contents("$ContainerDir/Members/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS)),
		serialize($GLOBALS["squidtail"]->GLOBAL_SQUIDUSERS));
	}
	
	if(count($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS)>0){
		@mkdir("$ContainerDir/SearchWords",0755,true);
		@file_put_contents("$ContainerDir/SearchWords/".md5(serialize($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS)),
		serialize($GLOBALS["squidtail"]->GLOBAL_SEARCHWORDS));
	}
	
	$size=round(($size/1024),2);
	events("analyze_file()::$basename:: $max: lines parsed in ".$unix->distanceOfTimeInWords($timeStart,time()).__LINE__);
	
	if(system_is_overloaded(basename(__FILE__))){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 ".__FILE__." --squid >/dev/null 2>&1 &";
	
	events("analyze_file()::$cmd");
	shell_exec($cmd);
	if($PAGEKEEP){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --thumbs-parse >/dev/null 2>&1 &";
		events(__FUNCTION__.":: $cmd",__LINE__);
		shell_exec($cmd);
	}
	
	if($YOUTUBE){
		$cmd="$nohup $php5 ".__FILE__." --youtube >/dev/null 2>&1 &";
		events(__FUNCTION__.":: $cmd",__LINE__);
		shell_exec($cmd);
	}
	if($RTTSIZE){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid-users-rttsize.php --now >/dev/null 2>&1 &";
		events(__FUNCTION__.":: $cmd",__LINE__);
		shell_exec($cmd);
	}
	
	

	
}

function PURGE_GLOBAL_QUEUE($QUEUE){
	if(count($QUEUE)==0){return;}
	
	while (list ($index, $FINAL_ARRAY) = each ($QUEUE) ){
		$NewArray=$GLOBALS["squidtail"]->ArrayToMysql($FINAL_ARRAY);
		if(!is_array($NewArray)){
			events("Failed Index($index), not an array... Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
			continue;
		}
		$array["TABLES"][$NewArray[0]][]=$NewArray[1];
	}

	if(count($array["TABLES"])>0){
		events("Injecting ". count($array["TABLES"]). " lines Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array($array["TABLES"]);
	}

}
function inject_array($array){

	$q=new mysql_squid_builder();
	while (list ($table, $contentArray) = each ($array) ){
		if(preg_match("#squidhour_([0-9]+)#",$table,$re)){$q->TablePrimaireHour($re[1],true);}
		$prefixsql="INSERT IGNORE INTO $table (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`zDate`,`zMD5`,`remote_ip`,`country`,`QuerySize`,`uid`,`cached`,`MAC`,`hostname`) VALUES ";
		$sql="$prefixsql".@implode(",",$contentArray);
		
		//if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		
		$q->QUERY_SQL($sql);
		events("inject_array::Injecting -> table `$table` ".count($contentArray)." rows affected: $q->mysql_affected_rows in line:".__LINE__);
		if(!$q->ok){
			if($GLOBALS["FORCE"]){echo "\n\n**************\n\n".$sql."\n**************\n";}
			if($GLOBALS["VERBOSE"]){echo "\n\n**************\n\n".$sql."\n**************\n";}
			events("FATAL !!! inject_array::Injecting -> ERROR: $q->mysql_error : in line:".__LINE__);
			inject_failed($array);
			return;
		}
	}


}
function inject_failed($array){
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2-errors")){@mkdir("/var/log/artica-postfix/dansguardian-stats2-errors",0755,true);}
	$serialized=serialize($array);
	events("FATAL !!! save into /var/log/artica-postfix/dansguardian-stats2-errors in line:".__LINE__);
	@file_put_contents("/var/log/artica-postfix/dansguardian-stats2-errors/".md5($serialized),$serialized);

}
function remove_memory_tables(){
	
	$q=new mysql_squid_builder();
	$QUEUE=$q->LIST_TABLES_HOURS_TEMP();
	while (list ($tablename, $FINAL_ARRAY) = each ($QUEUE) ){
		echo "removing $tablename\n";
		$q->QUERY_SQL("DROP TABLE $tablename");
	}
}


