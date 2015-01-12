<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");


if($argv[1]=="-xscan"){xscan2();exit;}


xscan();


function xscan2(){
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=intval($sock->GET_INFO("BackupMaxDays"));
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}	
	
	if(!is_dir($BackupMaxDaysDir)){return;}
	if (!$handle = opendir($BackupMaxDaysDir)) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BackupMaxDaysDir/$fileZ";
		if(preg_match("#logrotate\.state\.[0-9\.]+\.bz2$#", $fileZ)){
			echo "Remove $filename\n";
			@unlink($filename);
			continue;
		}
		if(preg_match("#log\.smbd\.[0-9\.]+\.bz2$#", $fileZ)){
			echo "Remove $filename\n";
			@unlink($filename);
			continue;
		}
		
		if(preg_match("#\.messages-[0-9\.]+\.gz#", $fileZ)){
			echo "Remove $filename\n";
			@unlink($filename);
			continue;
		}
		
		if(preg_match("#videocache-watchddog\.log\.[0-9\.]+\.bz2#", $fileZ)){
			echo "Remove $filename\n";
			@unlink($filename);
			continue;
		}
		
		$size=@filesize($filename);
		if($size==0){
			echo "Remove $filename\n";
			@unlink($filename);
			continue;
			}
				
				
	}
				
				
				
	
}

function xscan(){
	$ARRAY=array();
	$sock=new sockets();
	$unix=new unix();
	
	
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if($GLOBALS["VERBOSE"]){
		echo "Memory Free: ".$unix->TOTAL_MEMORY_MB_FREE()."/".$unix->TOTAL_MEMORY_MB()."\n";
		echo "Memory Use: ".$unix->TOTAL_MEMORY_MB_USED()." - ".$unix->TOTAL_MEM_POURCENT_USED()."%\n";
	}
	
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeExec=$unix->file_time_min($PidTime);
		if($TimeExec<20){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task {$TimeExec}mn, require 20mn ".__FUNCTION__."()\n";}
			return;
		}
	}
	
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	@file_put_contents($Pidfile, getmypid());
	
	xscan2();
	$q=new mysql_storelogs();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=intval($sock->GET_INFO("BackupMaxDays"));
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_dir("$BackupMaxDaysDir")){@mkdir("$BackupMaxDaysDir",true);}
	if($BackupMaxDays==0){$BackupMaxDays=30;}
	$BackupLogsMaxStoragePercent=intval($sock->GET_INFO("BackupLogsMaxStoragePercent"));
	if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
	
	
	$BackupMaxDaysMins=$BackupMaxDays*1440;
	echo "Max TTL : $BackupMaxDaysMins Minutes\n";
	$files=$unix->DirFiles($BackupMaxDaysDir);
	
	
	while (list ($filepath,$none) = each ($files)){
		$filename="$BackupMaxDaysDir/$filepath";
		$filetime=$unix->file_time_min($filename);
		echo "$filepath = {$filetime}/$BackupMaxDaysMins\n";
		if($filetime>$BackupMaxDaysMins){
			rotate_admin_events("Removed $filepath {$filetime}mn, exceed {$BackupMaxDays} days",__FUNCTION__,__FILE__,__LINE__);
			@unlink($filename);
			continue;
		}
		
		$filesecs=filemtime($filename);
		$ARRAY[$filesecs]=$filepath;
		
	}
	$DIRPART_INFO=$unix->DIRPART_INFO($BackupMaxDaysDir);
	$TOTAL_PART=$DIRPART_INFO["TOT"];
	$percent=$BackupLogsMaxStoragePercent/100;
	$TOTAL_AVAILABLE=$TOTAL_PART*$percent;
	
	
	
	$DIRSIZE=$unix->DIRSIZE_BYTES($BackupMaxDaysDir);
	$TOTAL_PART=$DIRPART_INFO["TOT"]/1024;
	
	$q->events("Directory size = $DIRSIZE/$TOTAL_AVAILABLE ".FormatBytes($DIRSIZE/1024,true)."/".FormatBytes($TOTAL_AVAILABLE/1024,true),__FUNCTION__,__LINE__);
	
	
	if($DIRSIZE>$TOTAL_AVAILABLE){
		CleanPercent($BackupMaxDaysDir,$TOTAL_AVAILABLE);
		
	}
	
	
	
	
	
}


function CleanPercent($BackupMaxDaysDir,$TOTAL_AVAILABLE){
	$unix=new unix();
	$q=new mysql_storelogs();
	$ARRAY=array();
	$DIRSIZE=$unix->DIRSIZE_BYTES($BackupMaxDaysDir);
	$q->events("Remove files in $BackupMaxDaysDir ".FormatBytes($DIRSIZE/1024)."/".FormatBytes($TOTAL_AVAILABLE/1024),__FUNCTION__,__LINE__);
	
	
	$q2=new mysql();
	$results=$q2->QUERY_SQL("SELECT * FROM backuped_logs ORDER BY zDate LIMIT 0,50","artica_backup");
	if(!$q2->ok){
		squid_admin_mysql(0, "MySQL error", $q2->mysql_error,__FILE__,__LINE__);
		return;
	}
	while ($ligne = mysql_fetch_assoc($results)) {
		if(preg_match("#^\/mnt\/", $ligne["path"])){continue;}
		$filepath=$ligne["path"];
		if(!is_file($filepath)){continue;}
		$sizeBytes=@filesize($filepath);
		@unlink($filepath);
		$DIRSIZE=$DIRSIZE-$sizeBytes;
		$q->events("Remove $filepath (".FormatBytes($sizeBytes/1024).") New DIR SIZE=".FormatBytes($DIRSIZE/1024)."/".FormatBytes($TOTAL_AVAILABLE/1024),__FUNCTION__,__LINE__);
		if($DIRSIZE<$TOTAL_AVAILABLE){break;}
	}
}


