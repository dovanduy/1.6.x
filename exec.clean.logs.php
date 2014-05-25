<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");



if($GLOBALS["VERBOSE"]){
	echo "Starting ".@implode("; ", $argv)."\n";
}
if($argv[1]=='--apache'){Clean_apache_logs(true);die();}
if($argv[1]=='--urgency'){UrgencyChecks();exit;}
if($argv[1]=='--logrotatelogs'){logrotatelogs(true);die();}
if($argv[1]=='--squid-store-logs'){CleanSquidStoreLogs();die();}
if($argv[1]=='--used-space'){used_space();die();}
if($argv[1]=='--cleandb'){CleanLogsDatabases(true);die();}
if($argv[1]=='--clean-logs'){CleanLOGSF();}
if(!$GLOBALS["FORCE"]){
	if(system_is_overloaded(__FILE__)){
		if($GLOBALS["VERBOSE"]){echo "This system is overloaded, die()\n";}
		writelogs("This system is overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
}


if($GLOBALS["VERBOSE"]){
	echo "Starting LINE:".__LINE__." ->".@implode("; ", $argv)."\n";
}


if($argv[1]=='--logs-urgency'){logs_urgency(true);die();}
if($argv[1]=='--clean-tmp2'){Clean_tmp_path(true);logrotatelogs(true);die();}
if($argv[1]=='--clean-tmp'){CleanLogs();logrotatelogs(true);die();}
if($argv[1]=='--clean-sessions'){sessions_clean();logrotatelogs(true);die();}
if($argv[1]=='--clean-install'){CleanOldInstall();die();}
if($argv[1]=='--paths-status'){PathsStatus();die();}
if($argv[1]=='--maillog'){maillog();die();}
if($argv[1]=='--wrong-numbers'){wrong_number();die();}
if($argv[1]=='--DirectoriesSize'){DirectoriesSize();die();}
if($argv[1]=='--cleanbin'){Cleanbin();die();}
if($argv[1]=='--zarafa-locks'){ZarafaLocks();die();}
if($argv[1]=='--squid-caches'){squidClean();die();}
if($argv[1]=='--rotate'){CleanRotatedFiles();die();}
if($argv[1]=='--squid'){squidClean();die();}
if($argv[1]=='--artica-logs'){artica_logs();die();}
if($argv[1]=='--squidLogs'){squidLogs();die();}
if($argv[1]=='--nginx'){nginx();die();}
if($argv[1]=='--attachs'){Clean_attachments();die();}
if($argv[1]=='--access-logs'){home_squid_access_logs();die();}




echo "Could not understand your query ???\n";


if(systemMaxOverloaded()){
	writelogs("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	die();
}


function CleanLOGSF(){
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
	if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} return;}
	
	
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<15){echo "Only each 15mn\n";die();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	
	
	Clean_tmp_path(true);
	varlog();
	squidLogs();
	CleanLogs();
	logrotatelogs(true);
	die();	
	
	
}



function squidClean(){
	
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;}
		
		
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<15){echo "Only each 15mn\n";die();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	
	
	squidLogs(true);
	CleanCacheStores(true);
	
}


function CleanSyslogsStore(){
	$unix=new unix();
	if(!$unix->is_socket("/var/run/syslogdb.sock")){return;}
	
	
}


function init(){
	$sock=new sockets();
	$ArticaMaxLogsSize=$sock->GET_PERFS("ArticaMaxLogsSize");
	if($ArticaMaxLogsSize<1){$ArticaMaxLogsSize=300;}
	$ArticaMaxLogsSize=$ArticaMaxLogsSize*1000;	
	$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	$GLOBALS["logs_cleaning"]=$sock->GET_NOTIFS("logs_cleaning");
	$GLOBALS["MaxTempLogFilesDay"]=$sock->GET_INFO("MaxTempLogFilesDay");
	if($GLOBALS["MaxTempLogFilesDay"]==null){$GLOBALS["MaxTempLogFilesDay"]=5;}
	
	
}

function varlog(){
	$unix=new unix();
	
	//$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	//$GLOBALS["logs_cleaning"]=$sock->GET_NOTIFS("logs_cleaning");
	//$GLOBALS["MaxTempLogFilesDay"]=$sock->GET_INFO("MaxTempLogFilesDay");
	//if($GLOBALS["MaxTempLogFilesDay"]==null){$GLOBALS["MaxTempLogFilesDay"]=5;}	
	if(!is_numeric($GLOBALS["ArticaMaxLogsSize"])){init();}
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$echo=$unix->find_program("echo");
	
	$syslog[]="/var/log/syslog";
	$syslog[]="/var/log/messages";
	$syslog[]="/var/log/daemon.log";
	$syslog[]="/var/log/auth.log";
	$syslog[]="/var/log/kern.log";
	$syslog[]="/var/log/user.log";
	$other[]="/var/log/php.log";
	$other[]="/var/log/artica-postfix/framework.log";
	$other[]="/var/log/artica-postfix/logrotate.debug";
	$other[]="/var/log/ArticaProc.log";
	
	$checks=array();
	while (list ($index,$filepath) = each ($syslog)){
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>$LogsRotateDefaultSizeRotation){
			$nextfile="$LogsDirectoryStorage/".basename($filepath)."-".time();
			if(!@copy($filepath, $nextfile)){ @unlink($nextfile); continue;}
			$checks[]=$nextfile;
			shell_exec("$echo \"\" >$filepath");
		}
	}
	
	
	while (list ($index,$filepath) = each ($other)){
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>50){
			shell_exec("$echo \"\" >$filepath");
		}
 }	
	
	
	$q=new mysql_storelogs();
	
	if ($handle = opendir($LogsDirectoryStorage)) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$filename="$LogsDirectoryStorage/$fileZ";
			echo "$filename\n";
			$q->InjectFile($filename,null);
				
		}
	}
	
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$BackupMaxDaysAccess=$sock->GET_INFO("BackupMaxDaysAccess");
	if(!is_numeric($BackupMaxDaysAccess)){$BackupMaxDaysAccess=365;}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	$BackupMaxHours=$BackupMaxDays*24;
	$BackupMaxMins=$BackupMaxHours*60;
	
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*24;
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*60;
	
	
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	
	
	
	if(is_dir($BackupMaxDaysDir)){
		if ($handle = opendir($BackupMaxDaysDir)) { 
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$BackupMaxDaysDir/$fileZ";
				$mins=$unix->file_time_min($filename);
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){echo "Removing $filename\n";@unlink($filename);}
					continue;
				}
				
				
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				if($mins>=$BackupMaxMins){echo "Removing $filename\n";@unlink($filename);}
				$q->InjectFile($filename);
			}
		
		}
	}	
	
	if(is_dir($LogRotatePath)){
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				if($mins>=$BackupMaxMins){echo "Removing $filename\n";@unlink($filename);}
				$q->InjectFile($filename);
			}
	
		}
	}	
	
	$LogRotatePath=$LogRotatePath."/work";
	if(is_dir($LogRotatePath)){
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){echo "Removing $filename\n";@unlink($filename);continue;}
					$q->InjectFile($filename);
					continue;
				}
				
				
				if($mins>=$BackupMaxMins){echo "Removing $filename\n";@unlink($filename);}
				$q->InjectFile($filename);
			}
	
		}
	}		
	
}



function CleanCacheStores($aspid=false){
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$GLOBALS["FORCE"]){
		$timefile=$unix->file_time_min($TimeFile);
		if($timefile<60){return;}
	}
	if($aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		@file_put_contents($Pidfile, getmypid());
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, getmypid());
	
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){if($GLOBALS["VERBOSE"]){echo "Squid is not installed...\n";}return;}
	$rm=$unix->find_program("rm");
	$f=file("/etc/squid3/squid.conf");
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^cache_dir\s+(.*?)\s+(.+?)\s+#" , $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Found Cache `{$re[2]}`\n";}
			$effective[$re[2]]=true;
		}
		
	}
	$dirs=$unix->dirdir("/var/cache");
	while (list ($directory, $line) = each ($dirs)){
		if(isset($effective[$directory])){
			if($GLOBALS["VERBOSE"]){echo "Checking Directory $directory is used by squid...\n";}
			continue;}
		$dirname=basename($directory);
		if($GLOBALS["VERBOSE"]){echo "Checking Directory [$directory] => $dirname\n";}
		if(preg_match("#^squid.*#", $dirname)){
			if($GLOBALS["VERBOSE"]){echo "Removing dir `$dirname`\n";}
			system_admin_events("Old squid cache directory $dirname will be deleted", __FUNCTION__, __FILE__, __LINE__, "clean");
			squid_admin_mysql(2,"Deleting Old squid cache directory $dirname","Old squid cache directory $dirname will be deleted\nRemoving: `$directory`");
			squid_admin_notifs("Old squid cache directory $dirname will be deleted\nRemoving: `$directory`", __FUNCTION__, __FILE__, __LINE__, "clean");
			shell_exec("$rm -rf $directory >/dev/null 2>&1");
		}
	}
	
	
}


function home_squid_access_logs(){
	$unix=new unix();
	$syslog=new mysql_storelogs();
	$BaseWorkDir="/home/squid/access_logs";
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	
	
	$mysar=$unix->find_program("mysar");
	if(!is_file($mysar)){
		if($GLOBALS["VERBOSE"]){
			echo "MySar, no such binary...\n";
			
		}
		return;
	}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	
	if($GLOBALS["VERBOSE"]){echo "Scanning $BaseWorkDir\n";}
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		
		$size=@filesize($filename);
		if($size==0){
			if($GLOBALS["VERBOSE"]){echo "$filename -> 0 bytes, skip it\n";}
			@unlink($filename);
			continue;
		}
		
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
		$extension = pathinfo($fileZ, PATHINFO_EXTENSION);
		
		if($GLOBALS["VERBOSE"]){echo "Analyze $fileZ / ext:$extension\n";}
		if(preg_match("#access\.log#", $fileZ)){
			$syslog->ROTATE_ACCESS_TOMYSQL($filename,$filedate);
			continue;
		}
		
		
		
		if($extension=="gz"){
			rotate_admin_events("Insert into MySQL file $filename",__FUNCTION__,__FILE__,__LINE__,"proxy",$GLOBALS["SCHEDULE_ID"]);
			$syslog->ROTATE_ACCESS_TOMYSQL($filename, $filedate);
			continue;
		}
		if($extension=="bz2"){
			rotate_admin_events("Insert into MySQL file $filename",__FUNCTION__,__FILE__,__LINE__,"proxy",$GLOBALS["SCHEDULE_ID"]);
			$syslog->ROTATE_ACCESS_TOMYSQL($filename, $filedate);
			continue;
		}		
		
	}
	
}

function squidLogs($aspid=false){
	$unix=new unix();
	
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $TimeFile\n";}
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	
	if(!$GLOBALS["FORCE"]){
		$timefile=$unix->file_time_min($TimeFile);
		if($timefile<60){return;}
	}
	if($aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
			return;}
		@file_put_contents($Pidfile, getmypid());
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, getmypid());
	

	home_squid_access_logs();
	LogRotateTimeAndSize("/var/log/squid");
	LogRotateTimeAndSize("/var/log/samba");
	
	
	
}

function LogRotateTimeAndSize($BaseWorkDir){
	if(!is_dir($BaseWorkDir)){return;}
	$unix=new unix();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$echo=$unix->find_program("echo");
	$syslog=new mysql_storelogs();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsRotateDeleteSize=intval($sock->GET_INFO("LogsRotateDeleteSize"));
	if($LogsRotateDeleteSize==0){$LogsRotateDeleteSize=5000;}
	
	
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		if(is_dir($filename)){continue;}
		$size=$unix->file_size($filename);
		$sizePHP=round(unix_file_size($filename)/1024);
		$size=round(($size/1024)/1000,2);
		
		
		if($size>=$LogsRotateDeleteSize){
			shell_exec("$echo \" \" > \"$filename\"");
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$sizePHP;
			$GLOBALS["DELETED_FILES"]++;
			$GLOBALS["DELETED_FILES"][]="$filename";
			continue;
		}
	
		if($GLOBALS["VERBOSE"]){echo "Found file: $filename {$size}M !== {$LogsRotateDefaultSizeRotation}M\n";}
	
		if($fileZ=="cache.log"){
			if($size>$LogsRotateDefaultSizeRotation){
				if($SquidRotateOnlySchedule==0){
					exec("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --force 2>&1",$results);
					squid_admin_mysql(2, "$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M", "Artica flush a log rotation\n".@implode("\n", $results));
					return;
				}else{
					if($size>$LogsRotateDeleteSize){
						$warnfile="/etc/artica-postfix/pids/squid.rotate.cache.warn";
						if($unix->file_time_min($warnfile)>30){
							squid_admin_mysql(1, "Warning $fileZ {$size}M Exceed {$LogsRotateDeleteSize}M",null,__FILE__,__LINE__);
							@unlink($warnfile);
							@file_put_contents($warnfile, time());
						}
					}
				}
			}
			continue;
		}
		if($fileZ=="external-acl.log"){continue;}
		if($fileZ=="ufdbguardd.log"){continue;}
		if($fileZ=="access.log"){
			if($size>$LogsRotateDefaultSizeRotation){
				if($SquidRotateOnlySchedule==0){
					exec("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --smooth 2>&1",$results);
					rotate_admin_events("$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M perform a Proxy log rotation",__FUNCTION__,__FILE__,__LINE__,"proxy",$GLOBALS["SCHEDULE_ID"]);
					squid_admin_mysql(2, "$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M", "Artica flush a log rotation\n".@implode("\n", $results));
					return;
				}else{
					
					if($size>$LogsRotateDeleteSize){
						$warnfile="/etc/artica-postfix/pids/squid.rotate.access.warn";
						if($unix->file_time_min($warnfile)>30){
							squid_admin_mysql(1, "Warning $fileZ {$size}M Exceed {$LogsRotateDeleteSize}M",null,__FILE__,__LINE__);
							@unlink($warnfile);
							@file_put_contents($warnfile, time());
						}
					}					
				}
			}
		}
		if($fileZ=="netdb.state"){continue;}
		$time=$unix->file_time_min($filename);
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
	
		if(preg_match("#access\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/access_logs",0755,true);
			if(@copy($filename, "/home/squid/access_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
				
			continue;
		}
	
		if(preg_match("#sarg\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/sarg_logs");
			if(@copy($filename, "/home/squid/sarg_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
	
			continue;
		}
	
	
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
	
		if($GLOBALS["VERBOSE"]){echo "Analyze $filename ($extension) $filedate\n";}
	
		if(is_numeric($extension)){
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
		if($extension=="gz"){
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
		if($extension=="state"){continue;}
	
		if($extension=="bz2"){
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
			
		$time=$unix->file_time_min($filename);
		echo "$filename {$time}Mn\n";
		if($time>1440){
			if(preg_match("#sarg\.#", basename($filename))){
			if($GLOBALS["VERBOSE"]){echo "Sarg -> $filename (exec.sarg.php --rotate)\n";}
			shell_exec("$php5 ".dirname(__FILE__)."/exec.sarg.php --rotate ".basename($filename)." >/dev/null 2>&1 &");
			continue;
		}
		
		$syslog->ROTATE_TOMYSQL($filename, $filedate);
	}
	}
	
	
		
}


function CleanSquidStoreLogs(){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$SquidMaxStoreLogSize=$sock->GET_INFO("SquidMaxStoreLogSize");
	if(!is_numeric($SquidMaxStoreLogSize)){$SquidMaxStoreLogSize=500;}
	if($SquidMaxStoreLogSize<10){$SquidMaxStoreLogSize=500;}
	$deleted=false;
	$syslog=new mysql_storelogs();
	foreach (glob("/var/log/squid/store.*") as $filename) {
		$size=$unix->file_size($filename);
		$size=$size/1024;
		$size=$size/1000;
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if($extension=="bz2"){
			$syslog->ROTATE_TOMYSQL($filename);
			continue;
		}
		
		if(preg_match("#store\.log\.[0-9]+$#", basename($filename))){
			echo "File rotation $filename -> $size\n";
			if($size==0){echo "Remove $filename -> $size\n";@unlink($filename);continue;}
			$syslog->ROTATE_TOMYSQL($filename);
		}
		
		echo "CleanSquidStoreLogs() $filename = $size MB (max = {$SquidMaxStoreLogSize}MB)\n";
		if($size>$SquidMaxStoreLogSize){
			echo "Remove $filename -> $size\n";
			@unlink($filename);
			$deleted=true;
		}
	}
	
	if($deleted){
		$squid=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squid)){return;}
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --rotate --force");

	}
	
}


function ZarafaLocks(){
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	if ($handle = opendir("/tmp")) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$path="/tmp/$file";
					if(preg_match("#-write\.lock$#", $file)){
						$time=$unix->file_time_min($path);
						if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
						if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
						continue;
					}
					
				if(preg_match("#-commit\.lock$#", $file)){
						$time=$unix->file_time_min($path);
						if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
						if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
						continue;
					}				
			}
		}
	}

	if ($handle = opendir($tmpdir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$tmpdir/$file";
				if(preg_match("#-write\.lock$#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
					if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
					continue;
				}
					
				if(preg_match("#-commit\.lock$#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
					if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
					continue;
				}
			}
		}
	}
}


function maillog(){
	init();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		if(is_numeric(basename($filename))){@unlink($filename);}
		if(preg_match("#_[0-9]+_tmp#", $filename)){@unlink($filename);continue;}
		if(is_numeric(basename($filename))){@unlink($filename);continue;}
	}
	
	foreach (glob("/usr/share/artica-postfix/framework/*") as $filename) {
		if(is_numeric(basename($filename))){@unlink($filename);}
		if(preg_match("#_[0-9]+_tmp#", $filename)){@unlink($filename);continue;}
		if(is_numeric(basename($filename))){@unlink($filename);continue;}
	}

	
	

}

function artica_logs(){
	$unix=new unix();
	$Dir="/var/log/artica-postfix";
	if ($handle = opendir($Dir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$Dir/$file";
				if(is_dir($path)){continue;}
				if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
				
				
				if(!is_file($path)){if($GLOBALS["VERBOSE"]){echo "$path, no file...\n";}continue;}
					if(preg_match("#artica-update-[0-9\-]+\.debug#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					
					
					if(preg_match("#backup-starter-[0-9\-]+\.log#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					
					
					if(preg_match("#exec..*?\.[0-9\-]+\.log#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					if(preg_match("#(process1|artica).*?\.(debug|log)#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
								
						}
					}					
					
					
					
					
				}
			}
	
	}	
	
}


function CleanAllindDir($DirPath,$maxtime=180){
	if(!is_dir($DirPath)){return;}
	$unix=new unix();
	if (!$handle = opendir($DirPath)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}		
		if(is_dir($DirPath)){continue;}
		$path="$DirPath/$file";
		if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
		if($unix->is_socket($path)){continue;}
		$time=$unix->file_time_min($path);
		if($time<$maxtime){continue;}
		$size=@filesize($path)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
		if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
		@unlink($path);

	}
}

function logs_urgency(){
	$unix=new unix();
	$df=$unix->find_program("df");
	exec("$df -h /var/log 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		if(!preg_match("#^(.+?)\s+([0-9\.,]+)([A-Z])\s+([0-9\.,]+).*?\s+([0-9\.,]+)%\s+#", $line,$re)){continue;}
		$purc=$re[5];
		break;
	}
	
	if($purc<100){return;}
	$echo=$unix->find_program("echo");
	$logf["artica-router.log"]=true;
	$logf["artica-smtp.log"]=true;
	$logf["auth.log"]=true;
	$logf["daemon.log"]=true;
	$logf["debug"]=true;
	$logf["dpkg.log"]=true;
	$logf["fetchmail.log"]=true;
	$logf["kern.log"]=true;
	$logf["mail.err"]=true;
	$logf["mail.log"]=true;
	$logf["mail.warn"]=true;
	$logf["messages"]=true;
	$logf["syslog"]=true;
	$logf["user.log"]=true;
	$logf["lighttpd/access.log"]=true;
	$logf["squid/store.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"]=true;
	$logf["artica-postfix/framework.log"]=true;
	$logf["samba/log.winbindd"]=true;
	$logf["samba/log.winbindd.old"]=true;
	
	while (list ($filname, $line) = each ($logf) ){
		$path="/var/log/$filname";
		if(!is_file($path)){continue;}
		shell_exec("$echo \" \" > $path 2>&1");
	}
	
if(is_dir("/var/log/squid")){
	if ($handle = opendir("/var/log/squid")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/var/log/squid/$file";
				if(!preg_match("#cache\.log\.[0-9]+#", $file)){continue;}
				@unlink($path);
			}
		}
	}
}
	
	
}


function Clean_attachments(){
	$unix=new unix();
	
	CleanAllindDir("/opt/artica/share/www/attachments");
	CleanAllindDir("/var/virusmail");
}

function Clean_apache_logs(){
	$sock=new sockets();
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$unix=new unix();
	$dirs=$unix->dirdir("/var/log/apache2");
	$echo=$unix->find_program("echo");
	$FILZ["access.log"]=true;  
	$FILZ["error.log"]=true;    
	$FILZ["nginx.access.log"]=true;    
	$FILZ["nginx.error.log"]=true;    
	$FILZ["php.log"]=true;  
	
	$syslog=new mysql_storelogs();
	
	while (list ($dirpath,$none) = each ($dirs) ){
		reset($FILZ);
		while (list ($filename,$none2) = each ($FILZ) ){
			$filepath="$dirpath/$filename";
			if(!is_file($filepath)){continue;}
			if(is_dir($filepath)){continue;}
			$timef=$unix->file_time_min($filepath);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$timef}Mn\n";}
			if($timef>2880){@unlink($filepath);continue;}
			$size=@filesize($filepath);
			$size=$size/1024;
			$size=round($size/1000,2);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$size}MB\n";}
			if($LogsRotateRemoveApacheMaxSize>0){
				if($size>$LogsRotateRemoveApacheMaxSize){ 
					if($GLOBALS["VERBOSE"]){echo "$filepath -> clean\n";}
					shell_exec("$echo \" \" >$filepath");
					continue; 
				}
			}
			
			if($size>$LogsRotateDefaultSizeRotation){
				if($GLOBALS["VERBOSE"]){echo "$filepath -> rotate\n";}
				$syslog->ROTATE_TOMYSQL($filepath);
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "$filepath -> NOTHING\n";}		
		}
		
	}
	
}



function Clean_tmp_path($aspid=false){
	$unix=new unix();
	
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.Clean_tmp_path.time
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		$timed=$unix->file_time_min($PidTime);
		if($timed<60){return;}
	}
	
	if($aspid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidpath);
		if($unix->process_exists($oldpid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $oldpid running.. Aborting");
			return;
		}
		
		@file_put_contents($pidpath, getmypid());
	}
	
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($DisableArticaProxyStatistics==1){$EnableRemoteStatisticsAppliance=1;}
	if($EnableRemoteSyslogStatsAppliance==1){$EnableRemoteSyslogStatsAppliance=1;}
	if($EnableRemoteSyslogStatsAppliance==1){clean_squid_users_size(true);}
	
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	$syslog_sql=new mysql_storelogs();
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	if(is_dir($LogsDirectoryStorage)){
		if ($handle = opendir($LogsDirectoryStorage)){
			while (false !== ($file = readdir($handle))) {
				$path="$LogsDirectoryStorage/$file";
				if($GLOBALS["VERBOSE"]){echo "$path -> INJECT\n";}
				if(!$syslog_sql->ROTATE_ACCESS_TOMYSQL($path)){continue;}
				@unlink($path);
			}
		}
	}
	
	if(is_dir("/etc/artica-postfix/pids")){
		if ($handle = opendir("/etc/artica-postfix/pids")){
			while (false !== ($file = readdir($handle))) {
				$path="/etc/artica-postfix/pids/$file";
				if($unix->file_time_min($path)>72000){ @unlink($path); }
			}
		}
	}
	
	
	
	
	logs_urgency();
	Clean_attachments();
	Clean_apache_logs();
	$tmpdir=$unix->TEMP_DIR();
	
	$echo=$unix->find_program("echo");
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"); }
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"); }	
	
		 

	if(!is_dir("/home/logrotate/work")){@mkdir("/home/logrotate/work",0755,true);}
	if(!is_dir("/var/log/artica-postfix/postqueue")){@mkdir("/var/log/artica-postfix/postqueue",0755,true);}
	

	
	if ($handle = opendir("/home/logrotate/work")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/home/logrotate/work/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				$timef=$unix->file_time_min($path);
				if($timef>5760){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					@unlink($path);
				}
			}
		}
	}
	
	
	
	if(is_dir("/home/logs-backup/olds")){
		if ($handle = opendir("/home/logs-backup/olds")){
			while (false !== ($file = readdir($handle))) {
				if ($file == "."){continue;}
				if ($file == ".."){continue;}
				$path="/home/logs-backup/olds/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				if($GLOBALS["VERBOSE"]){echo "$file {$timef}mn\n";}
				$timef=$unix->file_time_min($path);
				if($timef>1880){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					@unlink($path);
				}
		
			}
		}
	}
	
	
	
	
	if ($handle = opendir("/root")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/root/$file";
				if(is_dir($path)){continue;}
				if(is_numeric($file)){@unlink($path);continue;}
				if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
			}
		}
	}

	if(is_dir("/opt/artica/ldap-backup")){
		if ($handle = opendir("/opt/artica/ldap-backup")){
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						$path="/opt/artica/ldap-backup/$file";
						if(preg_match("#[0-9]+-0-9]+-0-9]+-0-9]+\.tar\.gz$#",$file)){
							$time=$unix->file_time_min($path);
							if($time>7200){@unlink($path);}
							continue;
						}
						
						if(is_numeric($file)){@unlink($path);continue;}
						}
					}
			}
	}
	
	
	if ($handle = opendir($tmpdir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$tmpdir/$file";
				if(is_dir($path)){
					if(preg_match("#^category_#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+$#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					continue;
				}
				
				if($GLOBALS["VERBOSE"]){echo "$path ?\n";} 
				if(is_dir($path)){if($GLOBALS["VERBOSE"]){echo "$path is a directory\n";} continue;}
				
				if(preg_match("#\.gif$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
				}
				
				if(preg_match("#process1-(.+?)\.tmp$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
					continue;
					
				}
				
				
				
				
				
				if(preg_match("#^artica-.+?\.tmp#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-.+?\.tmp \n";} 
				}
				
			if(preg_match("#^artica-php#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-php \n";} 
				}				
			}
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$tmpdir failed...\n";} 
	}
	if($GLOBALS["VERBOSE"]){echo "$tmpdir done..\n";}
	
	CleanAllindDir("/etc-artica-postfix/artica-postfix/cron.1",2880);
	CleanAllindDir("/tmp",2880);
	CleanAllindDir("/var/lib/c_icap/temporary",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/cron.2",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/croned.1",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/croned.2",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/pids",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/pids.3",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/loadavg.queue",750);
	CleanAllindDir("/var/log/artica-postfix/sys_mem",750);
	CleanAllindDir("/var/log/artica-postfix/sys_loadavg",750);
	CleanAllindDir("/var/log/artica-postfix/events",240);
	CleanAllindDir("/var/log/artica-postfix/postqueue",240);
	CleanAllindDir("/var/log/artica-postfix/system_failover_events",240);
	CleanAllindDir("/usr/share/artica-postfix/ressources/logs",180);
	CleanAllindDir("/usr/share/artica-postfix/ressources/logs/web",180);
	CleanAllindDir("/usr/share/artica-postfix/ressources/conf/upload",30);
	CleanAllindDir("/usr/share/artica-postfix/ressources/support",30);
	
	ZarafaLocks();
	CleanSquidStoreLogs();
	CleanCacheStores();
	CleanRotatedFiles();
	CleanLogsDatabases();
	sessions_clean();
	clean_artica_workfiles("/var/log/artica-postfix/Postfix-sql-error");
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/conf/upload",60);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web/cache",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web/queue/sessions",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/categorize-tables",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/jGrowl",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/support",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs",240);
	clean_squid_users_size(true);
	artica_logs();
	squidLogs(true);
}

function Cleanbin(){
		if ($handle = opendir("/usr/share/artica-postfix/bin")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
					
				if(preg_match("#^st[0-9A-za-z].*#", $file)){
					echo "Execute ('rm -f ' + BasePath + '/bin/$file');\n";
				}
			}
	}
}
	
}

function IfReallyExists(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$me=basename(__FILE__);
	$meRegx=str_replace(".", "\.", $me);
	$MyPID=getmypid();
	exec("pgrep -l -f $me 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+$meRegx#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "IfReallyExists() {$re[1]} <> $MyPID\n";}
			if($re[1]==$MyPID){continue;}
			return true;
		}
		
	}
	
	return false;
	
}


function clean_squid_users_size($nopid=false){
	$lockfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".lck";
	
	
	
	$unix=new unix();
	
	if(is_file($lockfile)){
		$lockfileTime=$unix->file_time_min($lockfile);
		if($lockfileTime<20){
			if(IfReallyExists()){
				if($GLOBALS["VERBOSE"]){echo "Lock file since {$lockfileTime}mn\n";}
				return;
			}
		}
	}
	

	
	if(!$nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidpath);
		if($unix->process_exists($oldpid)){
			if($GLOBALS["VERBOSE"]){echo "Already process $oldpid running.. aborting";}
			system_admin_events("Already process $oldpid running.. aborting",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
			return;
		}
	
		@file_put_contents($pidpath, getmypid());
	}
	
	@unlink($lockfile);
	@file_put_contents($lockfile, time());	
	
	
	if(!isset($GLOBALS["EnableRemoteStatisticsAppliance"])){
		$GLOBALS["EnableRemoteStatisticsAppliance"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance");
		if(!is_numeric($GLOBALS["EnableRemoteStatisticsAppliance"])){$GLOBALS["EnableRemoteStatisticsAppliance"]=0;}
	}	
	if(!isset($GLOBALS["DisableArticaProxyStatistics"])){
		$GLOBALS["DisableArticaProxyStatistics"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics");
		if(!is_numeric($GLOBALS["DisableArticaProxyStatistics"])){$GLOBALS["DisableArticaProxyStatistics"]=0;}
	}	
	
	$disable=0;
	$c=0;
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){$disable=1;}
	if($GLOBALS["DisableArticaProxyStatistics"]==1){$disable=1;}
	
	$dirs[]="/var/log/artica-postfix/squid-users";
	
	while (list ($index, $Directory) = each ($dirs) ){
		if($GLOBALS["VERBOSE"]){echo "Scan; $Directory \n";}
		if(!is_dir($Directory)){if($GLOBALS["VERBOSE"]){echo "$Directory doesn't exists, continue;\n";}continue;}
		if (!$handle = opendir($Directory)) {if($GLOBALS["VERBOSE"]){echo "$Directory fatal error\n";}continue;}
		if($GLOBALS["VERBOSE"]){echo "LOOP -> $Directory \n";}
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$c++;
				$size=$size+@filesize("$Directory/$file");
				if($GLOBALS["VERBOSE"]){echo "removing $Directory/$file\n";}
				@unlink("$Directory/$file");
			}
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "Scan; DONE, next \n";}
	
	
	if($c>0){
		$size=$size/1024;
		$size=round($size/1000,2);
		system_admin_events("$c deleted files ({$size}MB cleaned)",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
	}
	
	
	
	
	if($disable==0){return;@unlink($lockfile);}
	$dirs[]="/var/log/artica-postfix/searchwords";
	$dirs[]="/var/log/artica-postfix/squid-usersize";
	$dirs[]="/var/log/artica-postfix/squid-brut";
	$dirs[]="/var/log/artica-postfix/squid-reverse";
	
	
	

	
	while (list ($index, $Directory) = each ($dirs) ){
		if(!is_dir($Directory)){continue;}
		if (!$handle = opendir($Directory)) {continue;}
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$c++;
				$size=$size+@filesize("$Directory/$file");
				events_tail_squid("WARNING! DELETING $Directory/$file");
				@unlink("$Directory/$file");
				}
			}
		}	
		
	
	
		@unlink($lockfile);
	if($c>0){
		$size=$size/1024;
		$size=round($size/1000,2);
		system_admin_events("$c deleted files ({$size}MB cleaned)",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
	}
}

function events_tail_squid($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}


function CleanLogsDatabases($nopid=false){
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	
	$unix=new unix();
	if($nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidpath);
		if($unix->process_exists($oldpid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
			system_admin_events(basename(__FILE__).":: ".__FUNCTION__." Already process $oldpid running since $pidtime Mn.. Aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}	
	 @file_put_contents($pidpath, getmypid());
	}
	
	$sock=new sockets();
	$q=new mysql();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}	
	if($GLOBALS["VERBOSE"]){echo "max_events::{$settings["max_events"]}\n";}
	$tables=array();
	$tables["dhcpd_logs"]=true;
	$tables["ps_mem"]=true;
	$tables["update_events"]=true;
	$tables["nmap_events"]=true;
	$tables["backup_events"]=true;
	$tables["computers_available"]=true;
	$tables["auth_events"]=true;
	$tables["events"]=true;
	
	$ff=$q->LIST_TABLES_EVENTS_SYSTEM();
	while (list ($tbl, $ligne) = each ($ff) ){$tables[$tbl]=true;}
	$ff=$q->LIST_TABLES_EVENTS_SQUID();	
	while (list ($tbl, $ligne) = each ($ff) ){$tables[$tbl]=true;}
		
	while (list ($tbl, $ligne) = each ($tables)){
		_CleanLogsDatabases($tbl,$settings["max_events"]);
	}
}

function _CleanLogsDatabases($tablename,$max_events){
	$q=new mysql();
	$NumRows=$q->COUNT_ROWS($tablename, "artica_events");
	if($GLOBALS["VERBOSE"]){echo "$tablename::$NumRows/$max_events\n";}
	if($NumRows>$max_events){
		$toDelete=$NumRows-$max_events;
		$q->QUERY_SQL("DELETE FROM $tablename ORDER BY zDate LIMIT $toDelete","artica_events");
	}	
}


function CleanRotatedFiles(){
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
	$unix=new unix();
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	$cpbin=$unix->find_program("cp");
	$php5=$unix->LOCATE_PHP5_BIN();
	$tmpdir=$unix->TEMP_DIR();
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($LogRotatePath)){$LogRotatePath="/home/logrotate";}	
	
	$DirsToScan["/var/log/squid"]=true;
	$DirsToScan["/var/log"]=true;
	$DirsToScan["/var/log/apache2"]=true;
	$DirsToScan["/var/log/lighttpd"]=true;
	$DirsToScan["/var/log/ejabberd"]=true;
	$DirsToScan["/var/log/squid"]=true;
	
	$apache2=$unix->dirdir("/var/log/apache2");
	while (list ($WorkingDir, $ligne) = each ($apache2) ){	
		$DirsToScan[$WorkingDir]=true;
	}

	$q=new mysql_syslog();
	if($q->EnableSyslogDB==1){$LogRotateMysql=1;}
	$q2=new mysql_storelogs();
	
	while (list ($WorkingDir, $ligne) = each ($DirsToScan) ){
	$RotateSquid=false;
	if($WorkingDir=="/var/log/squid"){$RotateSquid=true;}
	$table=$unix->DirFiles($WorkingDir,"(\.|-)[0-9]+.*?$");
	$compressed["gz"]=true;
	$compressed["bz"]=true;
	$compressed["bz2"]=true;
	
	while (list ($filename, $ligne) = each ($table) ){
		$path="$WorkingDir/$filename";
		if($unix->file_time_min($path)<1440){continue;}
		
		if(preg_match("#sarg\.#", $filename)){
			shell_exec("$php5 ".dirname(__FILE__)."/exec.sarg.php --rotate ".basename($filename)." >/dev/null 2>&1 &");
			continue;
		}
		
		
		$ext=$unix->file_extension($filename);
		echo "$path -> `$ext`\n";
		if(!isset($compressed[$ext])){
			if(!$unix->compress($path, "$path.gz")){
				$q2->events("Unable to compress $path");
				system_admin_events("Unable to compress $path", __FUNCTION__, __FILE__, __LINE__, "clean");
				continue;
			}
			@unlink($path);
			$filename="$filename.gz";
			$path="$WorkingDir/$filename";
			
		}
		
		
		
		$filedate=date('Y-m-d H:i:s',filemtime($path));
		
		if($q->EnableSyslogDB==1){
			$q2->events("Injecting $path $filedate");
			if(!$q2->InjectFile($path, $filedate)){return;}
			$q2->events("remove: $path");
			@unlink($path);
			continue;
		}
		
		$filesize=$unix->file_size($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$basenameFF=$filename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".basename($WorkingDir).".".time().".$ext";			
		$taskid=0;
		
		
		
		if($LogRotateMysql==1){
			$q2->events("$path File Date:$filedate MySQL EnableSyslogDB:`$q->EnableSyslogDB`");
			system_admin_events("$path => $tmpdir/$filename => MySQL...",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@copy($path, "$tmpdir/$filename");
			@unlink($path);
			@chmod("$tmpdir/$filename", 0777);

				
			$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`) 
			VALUES ('$basenameFF','$taskid','$filesize',LOAD_FILE('/tmp/$filename'),'$filedate')";
			echo "$sql\n";
			$q->QUERY_SQL($sql);
				
				
			if(!$q->ok){
				$q2->events("$q->mysql_error, go back $tmpdir/$filename => $path...");
				system_admin_events("$q->mysql_error, go back $tmpdir/$filename => $path...",__FUNCTION__,__FILE__,__LINE__,"logrotate");@copy("/tmp/$basename", "$filename");}
				@unlink("$tmpdir/$filename");
			continue;
		}
		
		
		@mkdir("$LogRotatePath",0755,true);
		system_admin_events("$path => $LogRotatePath/$basenameFF => Store Disk...",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		shell_exec("$cpbin \"$path\" \"$LogRotatePath/$basenameFF\"");
		@unlink($path);
				
		$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`,`SavedInDisk`,`FileStorePath`) 
		VALUES ('$basenameFF','$taskid','$filesize','Nil','$filedate',1,'$LogRotatePath/$basenameFF')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			system_admin_events("$q->mysql_error, go back $LogRotatePath/$basenameFF => $path...",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@copy("$LogRotatePath/$basenameFF", "$path");
			@unlink("$LogRotatePath/$basenameFF");
		}		
		
		
		
	}
	
	
	
	}
	
	
}

function UrgencyChecks(){
	$unix=new unix();
	$sock=new sockets();
	
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidpath);
	if($unix->process_exists($oldpid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
		$unix->events("UrgencyChecks():: ".__FUNCTION__." Already process $oldpid running since $pidtime Mn.. Aborting");
		return;
	}
	@file_put_contents($pidpath, getmypid());
	$echo=$unix->find_program("echo");
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	
	
	$f=$unix->DirFiles("/var/log");
	$f[]="syslog";
	$f[]="messages";
	$f[]="user.log";
	varlog();
			
	while (list ($num, $filename) = each ($f) ){
		$filepath="/var/log/$filename";
		if(!is_file($filepath)){continue;}
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$unix->events("UrgencyChecks():: $filepath {$size}M");
		$ARRAY[$filepath]=$size;
	}
	
	$restart=false;
	
	while (list ($filepath, $sizeM) = each ($ARRAY) ){
		if($sizeM>$LogsRotateDeleteSize){
			shell_exec("$echo \"\" >$filepath");
			$restart=true;
			$unix->send_email_events("$filepath was cleaned ({$sizeM}M)", "It exceed maximal size {$LogsRotateDeleteSize}M", "system");
			$size=$unix->file_size($filepath);$size=$size/1024;$size=round($size/1000,2);
			$unix->events("UrgencyChecks():: $filepath {$sizeM}M > {$LogsRotateDeleteSize}M `$echo \"\" >$filepath` = {$size}M");
		}
	}
	
	if($restart){
		@chmod("/etc/init.d/syslog",0755);
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-syslog restart");
		shell_exec("/etc/init.d/auth-tail restart");
		shell_exec("/etc/init.d/postfix-logger restart");
	}	

	
}


function logrotatelogs($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if($nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidpath);
		if($unix->process_exists($oldpid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
			system_admin_events(basename(__FILE__).":: ".__FUNCTION__." Already process $oldpid running since $pidtime Mn.. Aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		@file_put_contents($pidpath, getmypid());
	}	
	
	
	$echo=$unix->find_program("echo");
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." line:".__LINE__."\n";}
	$q=new mysql_syslog();
	if($q->COUNT_ROWS("logrotate")==0){
		$q->CheckDefaults();
	}
	$sql="SELECT RotateFiles FROM logrotate WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$filepath=$ligne["RotateFiles"];
		if(strpos($filepath, "*")>0){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Scanning $filepath line:".__LINE__."\n";}
			foreach (glob($filepath) as $filename) {
				$size=$unix->file_size($filename);
				$size=$size/1024;
				$size=round($size/1000,2);
				$ARRAY[$filename]=$size;
				
				
			}
			
		}else{
			if(is_file($filepath)){
				$size=$unix->file_size($filepath);
				$size=$size/1024;
				$size=round($size/1000,2);
				$ARRAY[$filepath]=$size;
			}
			if(is_dir($filepath)){
				while (list ($num, $filename) = each ($f) ){
					$filepath="/var/log/$filename";
					$f=$unix->DirFiles("$filepath");
					$size=$unix->file_size($filepath);
					$size=$size/1024;
					$size=round($size/1000,2);
					$ARRAY[$filepath]=$size;
				}
			}
						
		}
		
	}
	$f=$unix->DirFiles("/var/log");
	while (list ($num, $filename) = each ($f) ){
		$filepath="/var/log/$filename";
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$ARRAY[$filepath]=$size;		
	}
	
	$f=$unix->DirFiles("/var/log/artica-postfix");
	while (list ($num, $filename) = each ($f) ){
		$filepath="/var/log/artica-postfix/$filename";
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$ARRAY[$filepath]=$size;
	}	
	
	$restart=false;
	
	while (list ($filepath, $sizeM) = each ($ARRAY) ){
		if($sizeM>$LogsRotateDeleteSize){
			shell_exec("$echo \"\" >$filepath");
			$restart=true;
			$unix->send_email_events("$filepath was cleaned ({$sizeM}M)", "It exceed maximal size {$LogsRotateDeleteSize}M", "system");
		}
	}
	
	if($restart){
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-syslog restart");
		shell_exec("/etc/init.d/auth-tail restart");
		shell_exec("/etc/init.d/postfix-logger restart");
	}
	
}

function cleanRoot(){
	
	$BaseWorkDir="/root";
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_numeric($filename)){@unlink($targetFile);}
	}	
	
}


function CleanLogs(){
	maillog();
	MakeSpace();
	cleanRoot();
	CleanSquidStoreLogs();
	home_squid_access_logs();
	$maxtime=480;
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidpath);
	if($unix->process_exists($oldpid)){
		$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $oldpid running.. Aborting");
		return;
	}
	
	@file_put_contents($pidpath,getmypid());
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timeOfFile=$unix->file_time_min($timefile);
	$unix->events("CleanLogs():: Time $timeOfFile/$maxtime");
	if($timeOfFile<$maxtime){
		$unix->events("CleanLogs():: Aborting");
		return;
	}
	
	$phplog=$unix->file_size("/var/log/php.log");
	$sizePHP=round(unix_file_size("/var/log/php.log")/1024);
	writelogs("/var/log/php.log = $sizePHP Ko",__FUNCTION__,__FILE__,__LINE__);
	if($sizePHP>11200000){
		@unlink("/var/log/php.log");
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$sizePHP;
		$GLOBALS["DELETED_FILES"]++;
		$GLOBALS["DELETED_FILES"][]="/var/log/php.log";
	}
	
	$atoplog=$unix->file_size("/var/log/atop.log");
	$sizePHP=round(unix_file_size("/var/log/atop.log")/1024);
	writelogs("/var/log/atop.log = $sizePHP Ko",__FUNCTION__,__FILE__,__LINE__);
	if($sizePHP>51200){
		@unlink("/var/log/atop.log");
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$sizePHP;
		$GLOBALS["DELETED_FILES"]++;
		$GLOBALS["DELETED_FILES"][]="/var/log/atop.log";
	}	
	
	@unlink($timefile);
	@file_put_contents($timefile,time());
	
	
	wrong_number();
	Clean_tmp_path();
	CleanOldInstall();
	LogRotateTimeAndSize("/var/log/samba");
	
	
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanOldInstall()\nWill restart in next cycle...", "system");
		return;
	}
	
	CleanBindLogs();
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanBindLogs()\nWill restart in next cycle...", "system");
		return;
	}
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning Clamav bases");
	CleanClamav();
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanClamav()\nWill restart in next cycle...", "system");
		return;
	}	
	
	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
	if($GLOBALS["DELETED_SIZE"]>500){
		send_email_events("$size logs files cleaned",
		"{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),"logs_cleaning");
	}	
	$GLOBALS["DELETED_SIZE"]=0;
	$GLOBALS["DELETED_FILES"]=0;
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." initalize");
	init();
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." cleanTmplogs()");
	cleanTmplogs();
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after cleanTmplogs()\nWill restart in next cycle...", "system");return;}	
	

	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/artica/tmp");
	CleanDirLogs('/opt/artica/tmp');
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after CleanDirLogs(/opt/artica/tmp)\nWill restart in next cycle...", "system");return;}		
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/artica/install");
	CleanDirLogs('/opt/artica/install');
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after CleanDirLogs(/opt/artica/install)\nWill restart in next cycle...", "system");return;}		
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning phplogs");
	phplogs();
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after phplogs()\nWill restart in next cycle...", "system");return;}		
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/openemm/tomcat/logs");
	CleanDirLogs('/opt/openemm/tomcat/logs');
	

	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning PHP Sessions");
	sessions_clean();
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning old install sources packages");

	
	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
	if($GLOBALS["DELETED_SIZE"]>500){
		send_email_events("$size logs files cleaned",
		"{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),"logs_cleaning");
	}
	
	
}

function cleanTmplogs(){
$badfiles["100k"]=true;
$badfiles["2"]=true;
$badfiles["size"]=true;
$badfiles["versions"]=true;
$badfiles["3"]=true;
$badfiles["named_dump.db"]=true;
$badfiles["named.stats"]=true;
$badfiles["log-queries.info"]=true;
$badfiles["log-named-auth.info"]=true;
$badfiles["log-lame.info"]=true;
$badfiles["bind.pid"]=true;
$badfiles["ipp.txt"]=true;
$badfiles["debug"]=true;
$badfiles["log-update-debug.log"]=true;
$badfiles["ldap.ppu"]=true;
$badfiles["#"]=true;	
$badfiles["bin/stIFOQ6A"]=true;
$badfiles["bin/stMSOCis"]=true;
$baddirs["2000"]=true;



	while (list ($num, $ligne) = each ($badfiles) ){
		if($num==null){continue;}
		if(is_file("/usr/share/artica-postfix/$num")){@unlink("/usr/share/artica-postfix/$num");}
	}
	
	while (list ($num, $ligne) = each ($baddirs) ){
		if($num==null){continue;}
		if(is_dir("/usr/share/artica-postfix/$num")){shell_exec("/bin/rm -rf /usr/share/artica-postfix/$num");}
	}	
	
	$unix=new unix();
	$countfile=0;
	foreach (glob("/tmp/artica*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/artica*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				die();
			}
			$countfile=0;
		}		
		
    	$time=$unix->file_time_min($filename);
    	if($time>2){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");
    		@unlink($filename);
    	}else{
    	if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
	foreach (glob("/var/log/artica-postfix/postfix.awstats.log.*") as $filename){
		$countfile++;
		$size=@filesize($filename)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
		@unlink($filename);  
	}
	
	
	$countfile=0;
if($GLOBALS["VERBOSE"]){echo "/tmp/process1*\n";}
	foreach (glob("/tmp/process1*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/process1*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				die();
			}
			$countfile=0;
		}				
		
    	$time=$unix->file_time_min($filename);
    	if($time>1){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size; 
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;   
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");	
    		@unlink($filename);
    	}else{
    		if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
}

function sessions_clean(){
	$unix=new unix();
	$sock=new sockets();
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	
	$CleanPHPSessionTime=$sock->GET_INFO("CleanPHPSessionTime");
	if(!is_numeric($CleanPHPSessionTime)){$CleanPHPSessionTime=2880;}
	
	if($unix->file_time_min($TimeFile)<60){return;}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	if($CleanPHPSessionTime>5){
		if(is_dir("/var/lib/php5")){
			foreach (glob("/var/lib/php5/*") as $filename) {
				$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,$filename);
				$time=$unix->file_time_min($filename);
				if($time>2880){
					$unix->_syslog("Clean: Remove PHP Session file ".basename($filename)." {$time}Mn", basename(__FILE__));
					@unlink($filename);}
			}
		}
		
		if(is_dir("/var/lib/php5-zarafa")){
			foreach (glob("/var/lib/php5-zarafa/*") as $filename) {
				$time=$unix->file_time_min($filename);
				if($time>2880){
					$unix->_syslog("Clean: Remove PHP Zarafa Session file ".basename($filename)." {$time}Mn", basename(__FILE__));
					@unlink($filename);}
			}
		}
	}
	
	foreach (glob("/usr/share/artica-postfix/ressources/logs/jGrowl/*") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time>360){@unlink($filename);}
	}
	
	foreach (glob("/usr/share/artica-postfix/ressources/conf/*") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time>360){@unlink($filename);}
	}	
	
}
function wrong_number(){
	$unix=new unix();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		$name=basename($filename);
		if($name=="?"){@unlink($filename);continue;}
		if(preg_match("#^[0-9]+$#", $name)){@unlink($filename);}
	}
	
}

function phplogs(){
	$filename="/usr/share/artica-postfix/ressources/logs/php.log";
	$size=@filesize($filename)/1024;
	if($GLOBALS["VERBOSE"]){echo "php.log size:{$size}Ko \n";}
	if($size>50681){
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
		$GLOBALS["DELETED_SIZE"]=$size;
		@unlink($filename);
	}
}


function CleanClamav(){
	$unix=new unix();
	
	foreach (glob("/var/lib/clamav/clamav-*") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time>60){
			if(is_dir($filename)){
				$size=dirsize($filename)/1024;
				$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
				$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
				shell_exec($unix->find_program("rm")." -rf $filename");
				if($GLOBALS["VERBOSE"]){echo "Delete directory $filename ($size Ko) TTL:$time\n";}
				continue;
				
			}
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
			if($GLOBALS["VERBOSE"]){echo "Delete $filename ($size Ko)\n";}		
			unlink($filename);
		}
		
	}
}

function CleanBindLogs(){
	$f["/var/cache/bind/log-lame.info"]=1;
	$f["/var/cache/bind/log-queries.info"]=1;
	while (list ($filepath, $none) = each ($f) ){
		$size=round(unix_file_size("$filepath")/1024);
		if($size>51200000){
			@unlink($filepath);
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		}
		
		
	}
	
}


function PathsStatus(){
	$f[]="/root";
	foreach (glob("/usr/share/*",GLOB_ONLYDIR) as $filename) {
		$f[]=$filename;
	}
	
	while (list ($num, $dir) = each ($f) ){
		echo "$dir\t".str_replace("&nbsp;"," ",FormatBytes(dirsize($dir)/1024))."\n";
	}
	
}




// /var/log/samba

function dirsize($path){
	$unix=new unix();
	
	exec($unix->find_program("du")." -b $path",$results);
	$tt=implode("",$results);
	if(preg_match("#([0-9]+)\s+#",$tt,$re)){return $re[1];}
	
}

function CleanOldInstall(){
	
	foreach (glob("/root/APP_*",GLOB_ONLYDIR) as $dirname) {
		if(!is_dir($dirname)){return;}
		$time=file_get_time_min($dirname);
		
		if($time>2880){
			echo "Removing $dirname\n";
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+dirsize($dirname);
			shell_exec("/bin/rm -rf $dirname");}
		}
	
}
function is_overloaded($file=null){
	if(!isset($GLOBALS["CPU_NUMBER"])){
			$users=new usersMenus();
			$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
	}
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$cpunum=$GLOBALS["CPU_NUMBER"]+1.5;
	if($file==null){$file=basename(__FILE__);}

	if($internal_load>$cpunum){
		$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;
		return true;
		
	}
	return false;

	
}

function clean_artica_workfiles($dir,$maxtime=0){
	$unix=new unix();
	if(!is_dir($dir)){return;}
	if($maxtime==0){$maxtime=240;}
	foreach (glob("$dir/*") as $filepath) {
		$time=$unix->file_time_min($filepath);
		if($time>$maxtime){@unlink($filepath);}
		
	}
}


function CleanDirLogs($path){
	return;
	if($GLOBALS["VERBOSE"]){echo "CleanDirLogs($path)\n";}
	$BigSize=false;
	if($path=='/var/log'){$BigSize=true;}
	if($GLOBALS["ArticaMaxLogsSize"]<100){$GLOBALS["ArticaMaxLogsSize"]=100;}
	$maxday=$GLOBALS["MaxTempLogFilesDay"]*24;
	$maxday=$maxday*60; 
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;	
	

	$unix=new unix();
	$sock=new sockets();

	$restartSyslog=false;
	if($path==null){return;}

	$countfile=0;
	foreach (glob("$path/*") as $filepath) {
		if($filepath==null){continue;}
		if(is_link($filepath)){continue;}
		if(is_dir($filepath)){continue;}
		if($filepath==$maillog_path){continue;}
		if(preg_match("#\/log\/artica-postfix\/#",$filepath)){continue;}
		
		$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [$path/*] System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
			"The clean logs function is stopped and wait a new schedule with best performances",
			"logs_cleaning");
			die();
			}
			$countfile=0;
		}
		
		
		usleep(300);
		$size=round(unix_file_size("$filepath")/1024);
		$time=$unix->file_time_min($filepath);
		$unix->events("$filepath $size Ko, {$time}Mn/{$maxday}Mn TTL");
		if($size>$GLOBALS["ArticaMaxLogsSize"]){
				if($GLOBALS["VERBOSE"]){echo "Delete $filepath\n";}
				$restartSyslog=true;
				$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
				$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
				$GLOBALS["UNLINKED"][]=$filepath;
				@unlink($filepath);
				continue;
		}
		
		if($time>$maxday){
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
			if($GLOBALS["VERBOSE"]){echo "Delete $filepath\n";}
			@unlink($filepath);
			$GLOBALS["UNLINKED"][]=$filepath;
			$restartSyslog=true;
			continue;
		}
		
		
	}
	


		if($restartSyslog){
			$unix->send_email_events("System log will be restarted",
			"Logs files was deleted and log daemons will be restarted
			".@implode("\n",$GLOBALS["UNLINKED"]),
			"logs_cleaning");
			$unix->RESTART_SYSLOG();
		}

}


function systemLogs(){
$f[]="/var/log/daemons/errors.log";
$f[]="/var/log/daemons/info.log";
$f[]="/var/log/daemons/warnings.log";

}



function unix_file_size($path){
	$unix=new unix();
	if($GLOBALS["stat"]==null){$GLOBALS["stat"]=$unix->find_program("stat");}
	$path=$unix->shellEscapeChars($path);
	exec("{$GLOBALS["stat"]} $path ",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			$res=$re[1];break;
		}
	}
	if(!is_numeric($res)){$res=0;}
	return $res;
}

function MakeSpace(){
	if(is_dir("/usr/share/doc")){shell_exec("/bin/rm -rf /usr/share/doc");}
	$sock=new sockets();
	$EnableBackupPc=$sock->GET_INFO("EnableBackupPc");
	if(!is_numeric($EnableBackupPc)){$EnableBackupPc=0;}
	if($EnableBackupPc==0){
		if(is_dir("/var/lib/backuppc")){shell_exec("/bin/rm -rf /var/lib/backuppc");}
	}
	
	if(is_dir("/var/log/fai")){shell_exec("/bin/rm -rf /var/log/fai");}
	
}

function DirectoriesSize(){
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	$f[]="/var";
	$f[]="/var/log";
	$f[]="/var/log/artica-postfix";
	$f[]="/var/lib";
	$f[]="/var/www";
	$f[]="/usr/share";
	$f[]="/opt";
	
	$unix=new unix();
	
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE `DirectorySizes`","artica_events");
	$prefix="INSERT IGNORE INTO DirectorySizes (zmd5,path,size) VALUES ";
	
	if($GLOBALS["OUTPUT"]){
		echo "Checking special directories size....Please wait...\n";
	}
	
	$du=$unix->find_program($du);
	while (list ($num, $directory) = each ($f)){
		$size=$unix->DIRSIZE_BYTES($directory);
		$tt=round(($size/1024)/1000);
		$md=md5($directory);
		$sql[]="('$md','$directory','$size')";
		echo "$directory... $tt MB\n";
		
		$dirs=array();
		$dirs=$unix->dirdir($directory);
		while (list ($a, $b) = each ($dirs)){
			$size=$unix->DIRSIZE_BYTES($a);
			$tt=round(($size/1024)/1000);
			if($tt>1){
				echo "\t$a -> " .round(($size/1024)/1000)." MB\n";
			}
			$md=md5($a);
			$sql[]="('$md','$a','$size')";
			
			if(count($sql)>100){
				$q->QUERY_SQL($prefix.@implode(",", $sql),"artica_events");
				$sql=array();
			}
			
		}
		
	}
	if(count($sql)>0){
		if($GLOBALS["OUTPUT"]){
			echo "Checking special directories done.. injecting into DirectorySizes table...\n";
		}
		$q->QUERY_SQL($prefix.@implode(",", $sql),"artica_events");}
	
	
}
//##############################################################################
function used_space(){
	$GLOBALS["OUTPUT"]=true;
	DirectoriesSize();
}

?>