<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--backup-nas"){ExtractFromMySQL($argv[2]);die();}

if($argv[1]=="--run"){run();die();}

	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task","MAIN",__FILE__,__LINE__,"logrotate");
		die();
	}

if($argv[1]=="--moveolds"){moveolds2();die();}
if($argv[1]=="--reconfigure"){reconfigure();die();}
if($argv[1]=="--mysql"){die();}
if($argv[1]=="--var"){CheckLogStorageDir($argv[2]);die();}
if($argv[1]=="--clean"){CleanMysqlDatabase(false);die();}
if($argv[1]=="--purge-nas"){CleanMysqlDatabase(true);die();}
if($argv[1]=="--squid"){die();}
if($argv[1]=="--convert"){die();}
if($argv[1]=="--test-nas"){tests_nas(true);die();}





	$sock=new sockets();
	$ArticaMaxLogsSize=$sock->GET_PERFS("ArticaMaxLogsSize");
	if($ArticaMaxLogsSize<1){$ArticaMaxLogsSize=300;}
	$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	

$unix=new unix();
$logrotate=$unix->find_program("logrotate");if(!is_file($logrotate)){echo "logrotate no such file\n";}





$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/logrotate.time";

$pid=file_get_contents("$pidfile");



if($unix->process_exists($pid,basename(__FILE__))){
	$timeMin=$unix->PROCCESS_TIME_MIN($pid);
	system_admin_events("Already executed PID $pid since $timeMin Minutes",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	if($timeMin>240){
		system_admin_events("Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}else{
		die();
	}
}

$logrotate_pid=$unix->PIDOF($logrotate);
if($unix->process_exists($logrotate_pid)){
	$time=$unix->PROCCESS_TIME_MIN($logrotate_pid);
	system_admin_events("Warning, a logrotate task PID $logrotate_pid still running since {$time}Mn, Aborted task",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	die();
}



@file_put_contents($pidfile, getmypid());
$time=$unix->file_time_min($timefile);
if(!$GLOBALS["FORCE"]){if($time<30){system_admin_events("No less than 30mn (current {$time}Mn)",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}}
@unlink($timefile);
@file_put_contents($timefile, time());
moveolds2();
reconfigure();
$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
$t=time();
$results[]="Results of : $cmd";
exec($cmd,$results);
$took=$unix->distanceOfTimeInWords($t,time(),true);
rotate_events("Success took: $took\n\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");



function run(){
	$sock=new sockets();	
	$unix=new unix();
	$logrotate=$unix->find_program("logrotate");
	if(!is_file($logrotate)){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/logrotate.". __FUNCTION__.".time";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($timefile);
	if($time<60){events("No less than 1h or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	reconfigure();
	
	$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
	events("Executing: $cmd");
	rotate_events("Last scan {$time}mn, Executing: $cmd",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	$t=time();
	exec($cmd,$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	
	system_admin_events("Success took: $took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");
	events("Success took: $took\n".@implode("<br>", $results));
	rotate_events("Success took: $took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");
}






function ConvertGZToBzip($filesource){
	$t=time();
	$fromTime=time();
	$fileDest=str_replace(".gz", ".bz2", $filesource);
	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$bzip2=$unix->find_program("bzip2");
	$cmd="$gunzip --to-stdout \"$filesource\" | $bzip2 > \"$fileDest\"";
	shell_exec($cmd);
	$took=$unix->distanceOfTimeInWords($fromTime,time(),true);
	system_admin_events("File $filesource as been converted to bz2, took: $took",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	if(!is_file($fileDest)){return null;}
	return $fileDest;
}

function CheckLogStorageDir($DirPath=null){
	$DirPath=rtrim($DirPath, '/');
	if($DirPath=="/var/log"){return;}
	
		
	$unix=new unix();
	
	//$dir=new DirectoryIterator("/var/log");

	
	if($unix->FILE_IS_LINK("/var/log")){
		$realpath=$unix->FILE_REALPATH("/var/log");
		echo "/var/log is a symbolic link to $realpath <> $DirPath\n";
		if($realpath==$DirPath){return true;}
		
	}
	
	if(!is_dir($DirPath)){
		echo "Creating $DirPath\n";
		@mkdir($DirPath,0755,true);
	}
	
	if(!is_dir($DirPath)){
		echo "Creating $DirPath failed, permissions denied\n";
		return;
		
	}
	
	$t=time();
	$mv=$unix->find_program("mv");
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");	
	$ln=$unix->find_program("ln");	
	
	$tmpdir="/var/syslog-transfered-$t";
	echo "rename /var/log to $tmpdir\n";
	shell_exec("$mv /var/log $tmpdir");
	
	if(!is_dir($tmpdir)){
		echo "Rename /var/log /var/syslog-transfered-$t failed no such directory\n";
		return;
	}
	
	echo "linking /var/log -> $DirPath\n";
	if(is_dir("/var/log")){
		$cmd="$rm -rf /var/log && $ln -s -f $DirPath /var/log";
	}else{
		$cmd="$ln -s -f $DirPath /var/log";
	}
	echo $cmd."\n";
	
	shell_exec($cmd);
	
	if(!$unix->FILE_IS_LINK("/var/log")){
		echo "Failed linking /var/log to $DirPath go back\n";
		shell_exec("$rm -rf /var/log");
		shell_exec("$mv $tmpdir /var/log");
		return;
	}else{
		echo "success linking /var/log to ". $unix->FILE_REALPATH("/var/log")." go back\n";
	}
	
	

	echo "Copy $tmpdir to $DirPath\n";
	shell_exec("$cp -ru $tmpdir/* $DirPath/");
	echo "remove olddir  $tmpdir\n";
	shell_exec("$rm -rf $tmpdir 2>&1");
	
	
}

function tests_nas(){
	$sock=new sockets();
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	
	
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	
	if($BackupSquidLogsUseNas==0){echo "Backup using NAS is not enabled\n";return;}
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
	
	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";
	
	$mountPoint="/mnt/BackupSquidLogsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,
			$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
		
		if($BackupSquidLogsNASRetry==1){
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				echo "$failed\nUnable to connect to NAS storage system: $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
				echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
				return;					
			}
		}else{
			echo "$failed\nUnable to connect to NAS storage system: $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
			echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
			return;
		}
	}
			
	
	$BackupMaxDaysDir="$mountPoint/artica-backup-syslog";

	@mkdir($BackupMaxDaysDir,0755,true);
	if(!is_dir($BackupMaxDaysDir)){
		echo "$failed$BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder/artica-backup-syslog permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", "#");
	if(!is_file("$BackupMaxDaysDir/$t")){
		echo "$failed$BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder/artica-backup-syslog/* permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}	
	@unlink("$BackupMaxDaysDir/$t");
	$mount->umount($mountPoint);
	echo "$success";
	
}


function ExtractFromMySQL($storeid){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$sock=new sockets();
	$users=new usersMenus();
	$unix=new unix();
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
	$mountPoint="/mnt/BackupSquidLogsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
		events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr");
		if($BackupSquidLogsNASRetry==0){return;}
		sleep(3);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			events("Unable to connect to NAS storage system (2): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr");
			return;
		}
			
	}
	
	
	$BackupMaxDaysDir="$mountPoint/artica-backup-syslog/$users->hostname";
	@mkdir("$BackupMaxDaysDir",0755,true);
	
	if(!is_dir($BackupMaxDaysDir)){
		if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
		events("Fatal $BackupMaxDaysDir permission denied");
		squid_admin_mysql(1,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		$mount->umount($mountPoint);
		return false;
	}
	
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", time());
	if(!is_file("$BackupMaxDaysDir/$t")){
		events("Fatal $BackupMaxDaysDir permission denied");
		if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
		squid_admin_mysql(1,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		$mount->umount($mountPoint);
		return false;
	}
	@unlink("$BackupMaxDaysDir/$t");
	
	
	
	
	$temppath=$unix->TEMP_DIR()."/$t";
	@mkdir($temppath,0777,true);
	
	$q=new mysql_storelogs();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT hostname,filename FROM files_info WHERE storeid = '$storeid'"));
	$filename=$ligne["filename"];
	if($filename==null){$filename=time().".tgz";}
	$hostname=$ligne["hostname"];
	if($hostname<>$users->hostname){
		$filename="$hostname.$filename";
	}
	
	$sql="SELECT filecontent INTO DUMPFILE '$temppath/$filename' FROM files_store WHERE ID = '$storeid'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		@unlink("$temppath/$filename");
		@rmdir($temppath);
		$mount->umount($mountPoint);
		return;
	}	

	if(!copy("$temppath/$filename", "$mountPoint/$filename")){
		@unlink("$temppath/$filename");
		@rmdir($temppath);
		$mount->umount($mountPoint);
		echo "Failed to copy $temppath/$filename -> $mountPoint/$filename\n";
		return;
	}
	
	@unlink("$temppath/$filename");
	@rmdir($temppath);
	$mount->umount($mountPoint);
	$q->QUERY_SQL("DELETE FROM files_store WHERE `ID`='$storeid'");
	$q->QUERY_SQL("DELETE FROM files_info WHERE `storeid`='$storeid'");
	echo "Success to copy $temppath/$filename \nTo:\n$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder/artica-backup-syslog/$users->hostname/$filename\n";
	
}


function CleanMysqlDatabase($PURGE_ALL=false){
	
	$filter=null;
	$users=new usersMenus();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/logrotate.". __FUNCTION__.".time";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($timefile);
	if(!$PURGE_ALL){
		if(!$GLOBALS["FORCE"]){
			if($time<15){
				events("No less than 15mn or delete $timefile file to force...");
				system_admin_events("No less than 15mn or delete $timefile file",
				__FUNCTION__,__FILE__,__LINE__,"logrotate");return;
			}
		}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	$sock=new sockets();
	
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($MySQLSyslogType==0){$MySQLSyslogType=4;}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	
	if($EnableSyslogDB==1){
		if($MySQLSyslogType==2){
			events("Is a client of remote MySQL server , aborting");
			if($GLOBALS["VERBOSE"]){echo "Is a client of remote MySQL server , aborting\n";}
			return;
		}
	}
	
	
	
	$LogRotateCompress=1;
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}	
	if($EnableSyslogDB==1){if($MySQLSyslogType<>1){return;}}
	
	
	
	
	
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}	
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	if($BackupSquidLogsUseNas==1){
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");	
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}		
		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr");
			if($BackupSquidLogsNASRetry==0){return;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				events("Unable to connect to NAS storage system (2): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr");
				return;
			}
			
		}
		$BackupMaxDaysDir="$mountPoint/artica-backup-syslog/$users->hostname";
	}
	
	
	@mkdir("$BackupMaxDaysDir",0755,true);
	
	if(!is_dir($BackupMaxDaysDir)){
		if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
		events("Fatal $BackupMaxDaysDir permission denied");
		squid_admin_mysql(1,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		if($BackupSquidLogsUseNas==1){$mount->umount($mountPoint);}
		return false;
	}
	
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", time());
	if(!is_file("$BackupMaxDaysDir/$t")){
		events("Fatal $BackupMaxDaysDir permission denied");
		if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
		squid_admin_mysql(1,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		if($BackupSquidLogsUseNas==1){$mount->umount($mountPoint);}
		return false;
	}
	@unlink("$BackupMaxDaysDir/$t");	
	
	
	if($BackupSquidLogsUseNas==1){
		if(is_dir("/home/logrotate_backup")){
			$files=$unix->DirFiles("/home/logrotate_backup");
			events("Scanning the old storage systems.. ".count($files)." file(s)");
			while (list ($basename, $none) = each ($files) ){
				$filepath="/home/logrotate_backup/$basename";
				if($GLOBALS["VERBOSE"]){echo "Checking \"$filepath\"\n";}
				$size=@filesize($filepath);
				if($size<20){events("Removing $filepath");@unlink($filepath);continue;}
				if(!@copy($filepath, "$BackupMaxDaysDir/$basename")){
					events("copy Failed $filepath to \"$BackupMaxDaysDir/$basename\" permission denied...");
					continue;
				}
				events("Move $filepath to $BackupSquidLogsNASIpaddr success...");
				@unlink($filepath);
			}
		}
	}
	
	if($PURGE_ALL==false){
		$filter="WHERE filetime<DATE_SUB(NOW(),INTERVAL $BackupMaxDays DAY)";
		
	}

	
	if($EnableSyslogDB==1){
		$q=new mysql_storelogs();
		$sql="SELECT `filename`,`hostname`,`storeid` FROM `files_info` $filter";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
		
		$Count=mysql_num_rows($results);
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$c++;
			if($GLOBALS["VERBOSE"]){echo "$c/$Count ******** {$ligne["filename"]} {$ligne["storeid"]} *********\n";}
			if(!$q->ExtractFile("$BackupMaxDaysDir/{$ligne["hostname"]}.{$ligne["filename"]}",$ligne["storeid"])){
				if($GLOBALS["VERBOSE"]){echo "$c/$Count ******** {$ligne["filename"]} ExtractFile() = FALSE !!! *********\n";}
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo "$c/$Count DelteItem({$ligne["storeid"]}) *********\n";}
			$q->DelteItem($ligne["storeid"]);
			if($GLOBALS["VERBOSE"]){echo "********* EVENTS NOW --->\n";}
			$q->events("{$ligne["filename"]} saved into $BackupMaxDaysDir");
			if($GLOBALS["VERBOSE"]){echo "\n\n###### $c/$Count Continue to Next ##########\n";}
		}
		
		
		$sql="SELECT `filename`,`hostname`,`storeid` FROM `accesslogs` $filter";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		
		if(!$q->ok){system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
		$Count=mysql_num_rows($results);
		$c=0;
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$c++;
			if(!$q->ExtractAccessFile("$BackupMaxDaysDir/{$ligne["hostname"]}.{$ligne["filename"]}",$ligne["storeid"])){continue;}
			$q->DelteAccessItem($ligne["storeid"]);
			$q->events("{$ligne["filename"]} saved into $BackupMaxDaysDir");
				
		}
		
		
		if($BackupSquidLogsUseNas==1){$mount->umount($mountPoint);}
		return;
	}
	
	
	$q=new mysql_syslog();
	$sql="SELECT `filename`,`taskid`,`filesize`,`filetime` FROM `store` $filter";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo "$sql ($q->mysql_error) ". mysql_num_rows($results)." file(s)\n";}
	
	if(!$q->ok){
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {	
		if($GLOBALS["VERBOSE"]){echo "Processing {$ligne["filename"]}\n";}
		if(!ExtractFileFromDatabase($ligne["filename"],$BackupMaxDaysDir)){
			events("Unable to extract {$ligne["filename"]} to $BackupMaxDaysDir");
			squid_admin_mysql(1,"SYSLOG: Unable to extract {$ligne["filename"]} to $BackupMaxDaysDir",null,__FILE__,__LINE__);
			if($BackupSquidLogsUseNas==1){$mount->umount($mountPoint);}
			return false;
		}else{
			events("Success extracting {$ligne["filename"]} to $BackupMaxDaysDir");
		}
	}
	
	if($BackupSquidLogsUseNas==1){$mount->umount($mountPoint);}

}

function ExtractFileFromDatabase($filename,$nextDir){
	$q=new mysql_syslog();
	$unix=new unix();
	$TempDir="/home/artica-extract-temp";
	@mkdir("/home/artica-extract-temp",0777);
	@chown("/home/artica-extract-temp", "mysql");
	@chdir("/home/artica-extract-temp", "mysql");
	$filebase=basename($filename);

	
	$q->QUERY_SQL("SELECT filedata INTO DUMPFILE '$TempDir/$filebase' FROM store WHERE filename = '$filename'");
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "Fatal ($q->mysql_error)\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;
	}
	
	if(!@copy("$TempDir/$filebase", "$nextDir/$filebase")){
		if($GLOBALS["VERBOSE"]){echo "Fatal $nextDir/$filebase permission denied\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;		
	}
	
	@unlink("$TempDir/$filebase");
	
	$q->QUERY_SQL("DELETE FROM store WHERE filename = '$filename'");
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "Fatal ($q->mysql_error)\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;
	}
	system_admin_events("Success Extract log file $filebase to $nextDir",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	return true;
}


function reconfigure(){
	$sock=new sockets();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$postfix=$unix->find_program("postfix");
	$squidbin=$unix->find_program("squid3");
	$gzip=$unix->find_program("gzip");
	if($squidbin==null){$squidbin=$unix->find_program("squid");}
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if(!is_numeric($LogRotatePath)){$LogRotatePath="/home/logrotate";}		
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	if($SystemLogsPath<>"/var/log"){CheckLogStorageDir($SystemLogsPath);}
	
	$LogRotateH=intval($sock->GET_INFO("LogRotateH"));
	$LogRotateM=intval($sock->GET_INFO("LogRotateM"));
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	
	if($SquidRotateOnlySchedule==1){
		$CRON[]="MAILTO=\"\"";
		$CRON[]="$LogRotateM $LogRotateH * * *  root $php5 /usr/share/artica-postfix/exec.squid.php --rotate --byschedule >/dev/null 2>&1";
		$CRON[]="";
		file_put_contents("/etc/cron.d/squid-rotate",@implode("\n", $CRON));
		$CRON=array();
		chmod("/etc/cron.d/squid-rotate",0640);
		chown("/etc/cron.d/squid-rotate","root");
		system("/etc/init.d/cron reload");
	}else{
		if(is_file("/etc/cron.d/squid-rotate")){
			@unlink("/etc/cron.d/squid-rotate");
			system("/etc/init.d/cron reload");
		}
		
	}
	
	
	
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}	
	@mkdir($LogsDirectoryStorage,0755,true);
	$q=new mysql_syslog();
	//RotateFiles,RotateType,RotateFreq,MaxSize,RotateCount,postrotate,description,enabled	
	$sql="SELECT *  FROM `logrotate` WHERE enabled=1";	
	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return;}
	
	
	foreach (glob("/etc/logrotate.d/*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Remove $filename\n";}
		@unlink($filename);}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$f=array();
		$dir=$ligne["RotateFiles"];
		$dir=dirname($ligne["RotateFiles"]);
		if(!is_dir($dir)){continue;}
		if(is_numeric($ligne["MaxSize"])){$ligne["MaxSize"]=100;}
		if(!is_numeric($ligne["RotateCount"])){$ligne["RotateCount"]=5;}
		
		if(preg_match("#\/var\/log\/squid#is", $ligne["RotateFiles"])){continue;}
		if(preg_match("#\/var\/log\/mail\.log#is", $ligne["RotateFiles"])){continue;}
		
		$f[]="{$ligne["RotateFiles"]} {";
		$f[]="\t{$ligne["RotateFreq"]}";
		$f[]="\tmissingok";
		if($ligne["MaxSize"]>0){$f[]="\tsize {$ligne["MaxSize"]}M";}
		if($ligne["RotateCount"]>0){$f[]="\trotate {$ligne["RotateCount"]}";}
		$f[]="\tcompress";
		$f[]="\tsharedscripts";
		$f[]="\tcreate 640 root";
		$f[]="\tdateext";
		$f[]="\tcompresscmd $gzip";
		$f[]="\tcompressoptions -9";
		$f[]="\tcompressext .gz";
		$f[]="\textension -TASK-{$ligne["ID"]}";
		
		if($ligne["postrotate"]<>null){
			$ligne["postrotate"]=str_replace("%SQUIDBIN%", $squidbin, $ligne["postrotate"]);
			$ligne["postrotate"]=str_replace("%POSTFIX%", $postfix, $ligne["postrotate"]);
			$ligne["postrotate"]=str_replace("%PHP%", $php5, $ligne["postrotate"]);
			$f[]="\tpostrotate";
			$f[]=$ligne["postrotate"];
			$f[]="endscript";
		}
		$f[]="}\n";
		@file_put_contents("/etc/logrotate.d/rotate-{$ligne["ID"]}", @implode("\n", $f));
		
	}
	
	LoagRotateApache();

}

function LoagRotateApache(){
	$sock=new sockets();
	$unix=new unix();
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if($ApacheLogRotate==0){return;}
	$gzip=$unix->find_program("gzip");
	$ligneC=unserialize(base64_decode($sock->GET_INFO("ApacheLogRotateParams")));
	if(!is_numeric($ligneC["RotateType"])){$ligneC["RotateType"]=0;}
	if(!is_numeric($ligneC["MaxSize"])){$ligneC["MaxSize"]=100;}
	if(!is_numeric($ligneC["RotateCount"])){$ligneC["RotateCount"]=5;}
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$q=new mysql();
	$sql="SELECT servername FROM freeweb";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(mysql_num_rows($results)==0){return;}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=$ligne["servername"];
			$f=array();		
			$f[]="/var/log/apache2/$servername/*.log {";
			$f[]="\t{$ligneC["RotateFreq"]}";
			$f[]="\tsu ".$unix->APACHE_SRC_ACCOUNT()." ".$unix->APACHE_SRC_GROUP();
			$f[]="\tmissingok";
			if($ligneC["MaxSize"]>0){$f[]="\tsize {$ligneC["MaxSize"]}M";}
			if($ligneC["RotateCount"]>0){$f[]="\trotate {$ligneC["RotateCount"]}";}
			$f[]="\tcompress";
			$f[]="\tsharedscripts";
			$f[]="\tcreate 640 root";
			$f[]="\tdateext";
			$f[]="\tcompressext .gz";
			$f[]="\tcompresscmd $gzip";
			$f[]="\tcompressoptions -9";
			$f[]="\textension -TASK-99999";
			$f[]="\tpostrotate";
			$f[]="$php5 /usr/share/artica-postfix/exec.freeweb.php --reload";
			$f[]="endscript";
			$f[]="}\n";
			@file_put_contents("/etc/logrotate.d/rotate-$servername", @implode("\n", $f));		
		
	}
}

function moveolds2(){
	$sock=new sockets();
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$unix=new unix();
	$mv=$unix->find_program("mv");
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo "Scanning /var/log....\n";}
	$d=$unix->DirFiles("/var/log","(.+)-[0-9]+");	
	@mkdir("$LogsDirectoryStorage/olds",0755,true);
	$delete=0;
	$size=0;	
	$CountDeFiles=count($d);
	while (list ($num, $path) = each ($d) ){
		
		$time=$unix->file_time_min("/var/log/$path");
		if($time>7200){
			$delete++;
			$size=$size+$unix->file_size("/var/log/$path");
			$targetFile="$LogsDirectoryStorage/olds/$path";
			if(is_file("$targetFile")){$targetFile=$targetFile.".".time();}
			shell_exec("$mv /var/log/$path $targetFile");
		}
	}
	if($delete>0){
		$size=FormatBytes($size/1024);
		system_admin_events("Moving $delete/$CountDeFiles old log file to $LogsDirectoryStorage/olds ($size)",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	}
	
	$delete=0;
	$size=0;
	if($GLOBALS["VERBOSE"]){echo "Scanning /var/log/artica-postfix/loadavg....\n";}
	$d=$unix->DirFiles("/var/log/artica-postfix/loadavg");
	$CountDeFiles=count($d);	
	while (list ($num, $path) = each ($d) ){
		usleep(700);
		$time=$unix->file_time_min("/var/log/artica-postfix/loadavg/$path");
		if($time>7200){
			$size=$size+$unix->file_size("/var/log/artica-postfix/loadavg/$path");
			@unlink("/var/log/artica-postfix/loadavg/$path");
			$delete++;
		}
	}
	
	
	if($delete>0){
		$size=FormatBytes($size/1024);
		system_admin_events("$delete/$CountDeFiles deleted old files in /var/log/artica-postfix/loadavg ($size free)",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	}
	
// Page Peeker	
	if($GLOBALS["VERBOSE"]){echo "Scanning /var/log/artica-postfix/pagepeeker....\n";}
	$delete=0;
	$size=0;
	$d=$unix->DirFiles("/var/log/artica-postfix/pagepeeker");
	$CountDeFiles=count($d);	
	if($GLOBALS["VERBOSE"]){echo "Scanning /var/log/artica-postfix/pagepeeker -> $CountDeFiles files....\n";}
	while (list ($num, $path) = each ($d) ){
		usleep(700);
		$time=$unix->file_time_min("/var/log/artica-postfix/pagepeeker/$path");
		if($time>7200){
			$size=$size+$unix->file_size("/var/log/artica-postfix/pagepeeker/$path");
			@unlink("/var/log/artica-postfix/pagepeeker/$path");
			$delete++;
		}
	}
	if($delete>0){
		$size=FormatBytes($size/1024);
		system_admin_events("$CountDeFiles/$delete deleted old files in /var/log/artica-postfix/pagepeeker ($size free)",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	}

}




function moveolds(){
	return;
	$sock=new sockets();
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$unix=new unix();
	$mv=$unix->find_program("mv");
	$d=$unix->DirRecursiveFiles("/var/log","*.bz2");
	@mkdir($LogsDirectoryStorage,0755,true);
	while (list ($num, $path) = each ($d) ){
		$filename=basename($path);
		if(is_file("$LogsDirectoryStorage/$filename")){$filename="$filename.".time().".bz2";}
		system_admin_events("Moving $path to $LogsDirectoryStorage",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		events("$mv $path $LogsDirectoryStorage/$filename");
		shell_exec("$mv $path $LogsDirectoryStorage/$filename");
		
	}
	
	
}

function ROTATE_COMPRESS_FILE($filename){
	$unix=new unix();
	if(!isset($GLOBALS["BZ2BIN"])){$GLOBALS["BZ2BIN"]=$unix->find_program("bzip2");;}
	$EXEC_NICE=$unix->EXEC_NICE();
	events("$filename -> Compressing");
	$cmdline="$EXEC_NICE {$GLOBALS["BZ2BIN"]} -z $filename";
	shell_exec($cmdline);
	if(!is_file("$filename.bz2")){return false;}
	$cmdline="{$GLOBALS["BZ2BIN"]} -t -v $filename.bz2 2>&1";
	exec($cmdline,$results);
	while (list ($num, $line) = each ($results) ){
		if(strpos($line,": ok")>0){return true;}
	}
	@unlink("$filename.bz2");
}








function events($text){
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	}
	
	if(!isset($GLOBALS["CLASS_SYSTEMLOGS"])){$GLOBALS["CLASS_SYSTEMLOGS"]=new mysql_storelogs();}
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$GLOBALS["CLASS_SYSTEMLOGS"]->events($text,$function,$line);
}
