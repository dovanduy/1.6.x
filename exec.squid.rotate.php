<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);

build();

// A scanner /home/squid/access_logs

function build(){
	
	$timefile="/etc/artica-postfix/pids/exec.squid.rotate.php.build.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	// /etc/artica-postfix/pids/exec.squid.rotate.php.build.time
	
	$sock=new sockets();
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){echo "Already PID $pid is running\n";die();}
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($timefile);
		if($time<60){echo "Only each 60mn\n";die();}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	$syslog=new mysql_storelogs();
	$SquidLogRotateFreq=intval($sock->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	$LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsRotateDeleteSize=intval($sock->GET_INFO("LogsRotateDeleteSize"));
	if($LogsRotateDeleteSize==0){$LogsRotateDeleteSize=5000;}
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	$BackupSquidLogsUseNas=intval($sock->GET_INFO("BackupSquidLogsUseNas"));
	
	$SquidRotateMergeFiles=$sock->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=1;}

	
	
	$php=$unix->LOCATE_PHP5_BIN();
	$hostname=$unix->hostname_g();
	
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateCache="$LogRotatePath/cache";
	$syslog->events("Launch rotation only by schedule.: $SquidRotateOnlySchedule",__FUNCTION__,__LINE__);
	$syslog->events("SquidLogRotateFreq...............: {$SquidLogRotateFreq}Mn",__FUNCTION__,__LINE__);
	$syslog->events("LastRotate.......................: {$LastRotate}Mn",__FUNCTION__,__LINE__);
	$syslog->events("Working directory................: $LogRotatePath",__FUNCTION__,__LINE__);
	$syslog->events("Launch rotation when exceed......: {$LogsRotateDefaultSizeRotation}M",__FUNCTION__,__LINE__);
	$syslog->events("Delete the file when exceed......: {$LogsRotateDeleteSize}M",__FUNCTION__,__LINE__);
	$syslog->events("Final storage directory..........: {$BackupMaxDaysDir}",__FUNCTION__,__LINE__);
	$syslog->events("Merge rotated files to a big file: {$SquidRotateMergeFiles}",__FUNCTION__,__LINE__);
	$syslog->events("Backup files to a NAS............: {$BackupSquidLogsUseNas}",__FUNCTION__,__LINE__);
	
	
	if ($handle = opendir("/var/run/squid")){
		while (false !== ($file = readdir($handle))) {
			if ($file == "." ){continue;}
			if($file == "..") {continue;}
			$path="/var/run/squid/$file";
			if(preg_match("#\.[0-9]+\.status$#", $file)){
				$time=$unix->file_time_min($path);
				if($time>1440){
					$syslog->events("Removing $path",__FUNCTION__,__LINE__);
					@unlink($path);
				}
				continue;
			}
			if(preg_match("#\.[0-9]+\.state$#", $file)){
				$time=$unix->file_time_min($path);
				if($time>1440){
					$syslog->events("Removing $path",__FUNCTION__,__LINE__);
					@unlink($path);
				}
				continue;
			}			
		}
		
		
	}
	
	
	
	
	
	$size=@filesize("/var/log/squid/access.log");
	$size=$size/1024;
	$size=$size/1024;
	$syslog->events("/var/log/squid/access.log........: {$size}M",__FUNCTION__,__LINE__);
	

	$syslog->events("Analyze /var/log/squid directory for cache.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir("/var/log/squid")){
		$syslog->events("Unable to open /var/log/squid directory.",__FUNCTION__,__LINE__);
		return;
		
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="/var/log/squid/$file";
			if(is_dir($path)){continue;}
			if(!preg_match("#^cache\.log\.[0-9]+$#", $file)){continue;}
			@mkdir("$LogRotateCache",0755,true);
			$size=@filesize($path);
			$size=$size/1024;
			$size=$size/1024;
			$destfile="$LogRotateCache/$file.".time().".log";
			if(!@copy($path, $destfile)){
				$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;			
			}
			$syslog->events("Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}
	
	$syslog->events("Analyze /var/log/squid directory for access.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir("/var/log/squid")){
		$syslog->events("Unable to open /var/log/squid directory.",__FUNCTION__,__LINE__);
		return;
	
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="/var/log/squid/$file";
			if(is_dir($path)){continue;}
			if(!preg_match("#^access\.log\.[0-9]+$#", $file)){continue;}
			@mkdir("$LogRotatePath",0755,true);
			$size=@filesize($path);
			$size=$size/1024;
			$size=$size/1024;
			$destfile="$LogRotateAccess/$file.".time().".log";
			if(!@copy($path, $destfile)){
				$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;
			}
			$syslog->events("Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}
	
	@mkdir($LogRotateAccess,0755,true);
	$syslog->events("Analyze $LogRotateAccess for access.log",__FUNCTION__,__LINE__);
	
	if (!$handle = opendir($LogRotateAccess)){
		$syslog->events("Unable to open $LogRotateAccess directory.",__FUNCTION__,__LINE__);
		return;
	
	}
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="$LogRotateAccess/$file";
			echo "OPEN $path\n";
			if(is_dir($path)){continue;}
			if(!preg_match("#^access\.log#", $file)){continue;}
			range_fichier_source($path,$BackupMaxDaysDir);
		}
	}
	
	$q=new mysql();
	
	$sql="CREATE TABLE IF NOT EXISTS `backuped_logs` (
			  `path` CHAR(255)  NOT NULL,
			  `zDate`  DATETIME,
			  `size` INT UNSIGNED NOT NULL,
			  PRIMARY KEY (`path`),
			  KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	
	$syslog->events("Analyze /home/logrotate/work for access.log",__FUNCTION__,__LINE__);
	analyze_directory("/home/logrotate/work",$BackupMaxDaysDir);
	analyze_cache_directory($LogRotateCache,$BackupMaxDaysDir);
	analyze_cache_directory("/home/logrotate/work",$BackupMaxDaysDir);
	analyze_cache_directory("/home/squid/cache-logs",$BackupMaxDaysDir);
	

	if($SquidRotateMergeFiles==1){
		Merge_files();
	}
	
	if($BackupSquidLogsUseNas==1){
		BackupToNas($BackupMaxDaysDir);
		$q->QUERY_SQL("TRUNCATE TABLE backuped_logs","artica_events");
	}else{
		analyze_destination_directory($BackupMaxDaysDir);
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize", $unix->DIRSIZE_KO($BackupMaxDaysDir));
	@chmod("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize",0777);
	
	return;
}

function analyze_destination_directory($path){
	$unix=new unix();
	$find=$unix->find_program("find");
	$sock=new sockets();
	exec("$find \"$path\" 2>&1",$results);
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE backuped_logs","artica_events");
	$f=array();
	while (list ($index, $filepath) = each ($results) ){
		if(is_dir($filepath)){continue;}
		$size=filesize($filepath);
		$basename=basename($filepath);
		//echo $basename." $size\n";
		if(!preg_match("#([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)--([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)\.gz$#", $basename,$re)){continue;}
		$zdate=("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}:{$re[6]}");
		$f[]="('$filepath','$zdate','$size')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `backuped_logs` (`path`,`zDate`,`size`) VALUES ".@implode(",", $f),"artica_events");
	}
	
	$BackupMaxDays=intval($sock->GET_INFO("BackupMaxDays"));
	if($BackupMaxDays<5){$BackupMaxDays=30;}
	
	$sql="SELECT `path` FROM `backuped_logs` WHERE zDate < DATE_SUB(NOW(),INTERVAL $BackupMaxDays DAY) ";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		squid_admin_mysql(0, "MySQL error", "$q->mysql_error",__FILE__,__LINE__);
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$path=$ligne["path"];
		if(preg_match("#^\/mnt\/#", $path)){continue;}
		$basename=basename($path);
		if(!is_file($path)){$q->QUERY_SQL("DELETE FROM backuped_logs WHERE `path`='$path'","artica_events");continue;}
		@unlink($path);
		squid_admin_mysql(2, "$basename was deleted ( exceed $BackupMaxDays days)", null,__FILE__,__LINE__);
		
	}
	
	
}



function analyze_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("$directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("$directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(preg_match("#^dmesg\.[0-9]+\.gz$#", $file)){@unlink($path);continue;}
		if(!preg_match("#^access\.log#", $file)){continue;}
		range_fichier_source($path,$BackupMaxDaysDir,true);
	}
	
}

function analyze_cache_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("$directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("$directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}

	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(!preg_match("#^cache\.log#", $file)){continue;}
		range_fichier_cache($path,$BackupMaxDaysDir,true);
	}

}


function range_fichier_cache($filepath,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateCache="$LogRotatePath/cache";
	$LogRotateCacheFailed="$LogRotatePath/cache-failed";
	@mkdir($LogRotateCache,0755,true);
	$basename=basename($filepath);
	$syslog->events("Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("$filepath is a tarball!",__FUNCTION__,__LINE__);
			return;
		}
	
		$syslog->events("Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateCache/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=cache_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateCacheFailed,0755,true);
		if(@copy($filepath, "$LogRotateCacheFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	
	
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	$NewFileName="cache-".filename_from_arraydates($ztimes);	
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
	
	if(!is_dir($FinalDirectory)){
		$syslog->events("Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	$syslog->events("Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);	
	
	

}


function Merge_files(){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccessMerged="$LogRotatePath/merged";
	$SquidRotateMergeFiles=$sock->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=1;}
	$LogsRotateDeleteSize=intval($sock->GET_INFO("LogsRotateDeleteSize"));
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	$BackupMaxDaysDir="$BackupMaxDaysDir/merged";
	$cat=$unix->find_program("cat");
	
	
	if($LogsRotateDeleteSize==0){$LogsRotateDeleteSize=5000;}	
	if(!is_dir($LogRotateAccessMerged)){
		$syslog->events("$LogRotateAccessMerged is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($LogRotateAccessMerged)){
		$syslog->events("$LogRotateAccessMerged failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	$mergedpath="$LogRotateAccessMerged/access.merged.log";
	$MERGED_FILES=unserialize(@file_get_contents("/etc/artica-postfix/accesslogs_merged.db"));
	
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$LogRotateAccessMerged/$file";
		if($file=="access.merged.log"){continue;}
		if($path==$mergedpath){continue;}
		if(is_dir($path)){continue;}
		
		if(preg_match("#^([0-9]+)\.bz2#", $file,$re)){
			@unlink($path);
			continue;
		}
		
		if(preg_match("#^([0-9]+)\.gz#", $file,$re)){
			if(!$unix->uncompress($path, "$LogRotateAccessMerged/{$re[1]}.access.log")){
				@unlink($path);
				continue;
			}
			@unlink($path);
			$path="$LogRotateAccessMerged/{$re[1]}.access.log";
				
		}
		
		if(!preg_match("#access\.log#", $file)){continue;}
		
		
		
		$md5file=md5_file($path);
		if(isset($MERGED_FILES[$md5file])){
			$syslog->events("$path Already merged <$md5file>",__FUNCTION__,__LINE__);
			@unlink($path);
			continue;
		}
		
		$sep=">>";
		if(!is_file($mergedpath)){$sep=">";}
		if(is_file($mergedpath)){
			$size=@filesize($mergedpath);
			$size=$size/1024; // KB
			$size=$size/1024; //MB
			if($size>$LogsRotateDeleteSize){
				$this->events("$mergedpath will be rotated ( $size MB)",__FUNCTION__,__LINE__);
				$ztimes=access_logs_getdates($mergedpath);
				if(!$ztimes){
					$this->events("$mergedpath corrupted!",__FUNCTION__,__LINE__);
					@unlink($mergedpath);
					continue;
				}
				
				$NewFileName=filename_from_arraydates($ztimes);
				if(!is_dir("$BackupMaxDaysDir")){@mkdir($BackupMaxDaysDir,0755,true);}
				if(!is_dir("$BackupMaxDaysDir")){
					$this->events("unable to create $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
					return false;
				}
				if(!$unix->compress($mergedpath, "$BackupMaxDaysDir/$NewFileName")){
					$this->events("unable to compress $mergedpath to $BackupMaxDaysDir/$NewFileName permission denied",__FUNCTION__,__LINE__);
					@unlink("$BackupMaxDaysDir/$NewFileName");
					return false;
				}
				@unlink($mergedpath);
				$sep=">";
			}
		}
		
		$syslog->events("Merge $path to $mergedpath",__FUNCTION__,__LINE__);
		shell_exec("$cat $path $sep $mergedpath");
		$MERGED_FILES[$md5file]=true;
		@file_put_contents("/etc/artica-postfix/accesslogs_merged.db", serialize($MERGED_FILES));
		$syslog->events("removing $path",__FUNCTION__,__LINE__);
		@unlink($path);
	}	
	
}

function filename_from_arraydates($ztimes,$suffix=null){
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	if($suffix<>null){$suffix=".$suffix.";}
	return "$hostname.{$suffix}$dateFrom--$dateTo.gz";
}

function CLEAN_OLD_LOGS(){
	
	
}


function range_fichier_source($filepath,$BackupMaxDaysDir,$EXTERN=false){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateAccessFailed="$LogRotatePath/failed";
	$LogRotateAccessMerged="$LogRotatePath/merged";
	$SquidRotateMergeFiles=$sock->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=1;}
	$LogsRotateDeleteSize=intval($sock->GET_INFO("LogsRotateDeleteSize"));
	if($LogsRotateDeleteSize==0){$LogsRotateDeleteSize=5000;}
	$basename=basename($filepath);
	if($basename=="access.merged.log"){return;}
	
	$syslog->events("Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("$filepath is a tarball!",__FUNCTION__,__LINE__);
			return;
		}
		
		$syslog->events("Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateAccess/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=access_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateAccessFailed,0755,true);
		if(@copy($filepath, "$LogRotateAccessFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	
	
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	$NewFileName=filename_from_arraydates($ztimes);
	
	if($SquidRotateMergeFiles==1){
		@mkdir($LogRotateAccessMerged,0755,true);
		if(!is_dir($LogRotateAccessMerged)){
			$syslog->events("Unable to create Merged directory $LogRotateAccessMerged",__FUNCTION__,__LINE__);
		}else{
			if(!@copy($filepath, "$LogRotateAccessMerged/$basename")){
				@unlink("$LogRotateAccessMerged/$basename");
				$syslog->events("Unable to copy $filepath -> $LogRotateAccessMerged/$basename",__FUNCTION__,__LINE__);
			}
		}
		
	}
	
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
		
	if(!is_dir($FinalDirectory)){
		$syslog->events("Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	$syslog->events("Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);
}

function cache_logs_getdates($file){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	
	$Ttime=0;
	$array=$unix->readlastline($file,5);
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time>$Ttime){$Ttime=$time;}
	}
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,5);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time<$Ttime){$Ttime=$time;}
	}
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
	
}

function access_logs_getdates($file){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	
	
	$array=$unix->readlastline($file,5);
	if(!is_array($array)){return false;}
	$Ttime=0;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$time=strtotime($zdate);
		if($time>$Ttime){$Ttime=$time;}
	}
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,5);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$time=strtotime($zdate);
		if($time<$Ttime){$Ttime=$time;}
	}
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
}

function BackupToNas($directory){
		if(!is_dir($directory)){return;}
		$syslog=new mysql_storelogs();
		$sock=new sockets();
		$users=new usersMenus();
		$unix=new unix();
		$myHostname=$unix->hostname_g();
		$DirSuffix=basename($directory);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		$mv=$unix->find_program("mv");

		if($BackupSquidLogsNASIpaddr==null){
			$this->events("Backup via NAS is disabled, skip",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}

		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			$syslog->events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				
			if($BackupSquidLogsNASRetry==0){return;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				$syslog->events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				return;
			}

		}

		
		$syslog->events("Hostname=$myHostname Suffix = $DirSuffix $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder",__FUNCTION__,__LINE__);
		$BackupMaxDaysDir="$mountPoint/artica-backup-syslog";
		@mkdir("$BackupMaxDaysDir",0755,true);

		if(!is_dir($BackupMaxDaysDir)){
			$syslog->events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}


		$t=time();
		@file_put_contents("$BackupMaxDaysDir/$t", time());
		if(!is_file("$BackupMaxDaysDir/$t")){
			$syslog->events("Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed",__FUNCTION__,__LINE__);
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}

		
		@unlink("$BackupMaxDaysDir/$t");
		
		
		exec("$mv --force $directory --target-directory=$BackupMaxDaysDir/ 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			$syslog->events("$line",__FUNCTION__,__LINE__);
		}
		
		analyze_destination_directory($BackupMaxDaysDir."/proxy");
		$mount->umount($mountPoint);
		return true;
}

?>