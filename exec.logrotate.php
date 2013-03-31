<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}

	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task","MAIN",__FILE__,__LINE__,"logrotate");
		die();
	}

if($argv[1]=="--moveolds"){moveolds2();die();}
if($argv[1]=="--reconfigure"){reconfigure();die();}
if($argv[1]=="--run"){run();die();}
if($argv[1]=="--mysql"){InstertIntoMysql();die();}
if($argv[1]=="--var"){CheckLogStorageDir($argv[2]);die();}
if($argv[1]=="--clean"){CleanMysqlDatabase();die();}
if($argv[1]=="--squid"){check_all_squid();die();}



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
		shell_exec("$kill -9 $pid");
	}else{
		die();
	}
}



@file_put_contents($pidfile, getmypid());
$time=$unix->file_time_min($timefile);
if(!$GLOBALS["FORCE"]){if($time<30){system_admin_events("No less than 30mn (current {$time}Mn)",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}}
@unlink($timefile);
@file_put_contents($timefile, time());
moveolds2();
$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
$t=time();
$results[]="Results of : $cmd";
exec($cmd,$results);
$took=$unix->distanceOfTimeInWords($t,time(),true);
system_admin_events("Success took: $took".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");



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
	if($time<15){system_admin_events("No less than 15mn or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$q=new mysql_syslog();
	$table="logrotate";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	if($q->COUNT_ROWS($table)==0){$q->CheckDefaults();}
	reconfigure();
	
	$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
	system_admin_events("Executing: $cmd",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	$t=time();
	exec($cmd,$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Success took: $took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");
	InstertIntoMysql();
	
}

function InstertIntoMysql(){
	$unix=new unix();
	
	$bzip2=$unix->find_program("bzip2");
	$cpbin=$unix->find_program("cp");
	$sql="SELECT *  FROM `logrotate` WHERE enabled=1";	
	$q=new mysql_syslog();
	$q->CheckTables();
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){return;}	
	if(!$q->TABLE_EXISTS("store")){return;}
	if(system_is_overloaded(basename(__FILE__))){return;}
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}	
	
	$paths=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$RotateFiles=$ligne["RotateFiles"];
		$dirname=dirname($RotateFiles);
		
		$paths[$dirname]=true;
	}
	
	if($ApacheLogRotate==1){
			$q2=new mysql();
			$sql2="SELECT servername FROM freeweb";
			$results2=$q2->QUERY_SQL($sql2,'artica_backup');
			if(mysql_num_rows($results)==0){return;}
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$servername=$ligne["servername"];
				$paths["/var/log/apache2/$servername"]=true;				
			}	
		}
	
	
	
	if(count($paths)==0){return;}
	while (list ($path,$none) = each ($paths) ){
		foreach (glob("$path/*-TASK-*") as $filename) {
			$filedate=date('Y-m-d H:i:s',filemtime($filename));
			
			$basename=basename($filename);
			if(strpos($basename, ".bz2")==0){
				if($LogRotateCompress==1){
					shell_exec("$bzip2 -z $filename");
					$filename=$filename.".bz2";
					$basename=basename($filename);
				}
			}
			
			if(!preg_match("#-TASK-([0-9]+)#",$basename,$re)){continue;}
			$taskid=$re[1];
			$filesize=$unix->file_size($filename);
			system_admin_events("Task:$taskid File $basename ($filedate)",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			
			if(ROTATE_TOMYSQL($filename,$filedate)){
				@unlink($filename);
				continue;
			}
	

		}
	}
	
	if($LogRotateCompress==0){return;}
	
	reset($paths);
	while (list ($path,$none) = each ($paths) ){
		foreach (glob("$path/*") as $filename) {
			if(system_is_overloaded(basename(__FILE__))){return;}
			if(preg_match("#ipband\.#", $filename)){continue;}
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			if(is_dir($filename)){continue;}
			if($extension==null){continue;}
			if($extension=="log"){continue;}
			echo "$filename = $extension\n";
			$filedate=date('Y-m-d H:i:s',filemtime($filename));
			if($extension=="gz"){
				system_admin_events("$filename => Converting to bz2",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				$filename=ConvertGZToBzip($filename);
				if($filename==null){continue;}
				$extension="bz2";
			}
			
			if(is_numeric($extension)){
				$tA=time();
				$tC=$filename;
				$tB=$unix->file_size_human($filename);
				if(!ROTATE_COMPRESS_FILE($filename)){
					system_admin_events("File ".basename($tC)." Failed to compress file",__FUNCTION__,__FILE__,__LINE__,"logrotate");
					continue;
				}
				
				$took=$unix->distanceOfTimeInWords($tA,time(),true);
				$filename=$filename.".bz2";
				$tD=$unix->file_size_human($filename);
				$extension="bz2";
				system_admin_events("File ".basename($tC)." ($tB) as been converted to bz2 width new size $tD, took: $took",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				
			}
			
			if(preg_match("#[a-z]+-[0-9]+$#", $extension)){
				if(!ROTATE_COMPRESS_FILE($filename)){
						system_admin_events("File ".basename($tC)." Failed to compress file",__FUNCTION__,__FILE__,__LINE__,"logrotate");
						continue;
					}
				$filename=$filename.".bz2";
				$extension="bz2";	
			}
			
			$basename=basename($filename);	
			if($extension<>"bz2"){continue;}

			system_admin_events("Task:$taskid File $basename ($filedate)",__FUNCTION__,__FILE__,__LINE__,"logrotate");	
			if(ROTATE_TOMYSQL($filename,$filedate)){
				@unlink($filename);
				continue;
			}
			
			
		}
	}
	
	
}
function ROTATE_TOMYSQL($filename,$sourceDate){
	$sock=new sockets();
	$unix=new unix();
	$taskid=0;
	$basename=basename($filename);
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
	$LogRotatePathWork="$LogRotatePath/work";
	if($LogRotateMysql==0){$LogRotatePathWork=$LogRotatePath;}
	if(!is_dir($LogRotatePathWork)){@mkdir($LogRotatePathWork,0777);}
	@chmod($LogRotatePathWork, 0777);
	$basenameFF=null;
	$DestinationFile="$LogRotatePathWork/$basename";
	
	
	if(is_file($DestinationFile)){
		$ext = pathinfo($DestinationFile, PATHINFO_EXTENSION);
		$basenameFF=basename($DestinationFile);
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".time().".$ext";
		$DestinationFile=str_replace(basename($DestinationFile), $basenameFF, $DestinationFile);
	}
	
	if(!@copy($filename, $DestinationFile)){
		@unlink($DestinationFile);
		system_admin_events("Failed to copy $filename => $DestinationFile",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;
	}
	
	if(preg_match("#-TASK-([0-9]+)#",$basename,$re)){$taskid=$re[1];}
		
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	$basenameFF=$basename;
	$basenameFF=str_replace(".$ext", "", $basenameFF);
	$basenameFF=$basenameFF.".".time().".$ext";	
	$filesize=$unix->file_size($filename);
	
	if($LogRotateMysql==1){
		$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`)
		VALUES ('$basenameFF','$taskid','$filesize',LOAD_FILE('$DestinationFile'),'$sourceDate')";
	}
	
	if($LogRotateMysql==0){
		$basenameFF=basename($DestinationFile);
		$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`,`SavedInDisk`,`FileStorePath`)
		VALUES ('$basenameFF','$taskid','$filesize','','$sourceDate',1,'$DestinationFile')";
	}

	$q=new mysql_syslog();
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		system_admin_events("MySQL Failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		@unlink($DestinationFile);
		return false;
	}
	if($LogRotateMysql==1){@unlink($DestinationFile);}
	return true;
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

function CleanMysqlDatabase(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/logrotate.". __FUNCTION__.".time";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($timefile);
	if($time<15){system_admin_events("No less than 15mn or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"logrotate");die();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}	
	
	
	@mkdir("$BackupMaxDaysDir",0755);
	if(!is_dir($BackupMaxDaysDir)){
		if($GLOBALS["VERBOSE"]){echo "FATAL $BackupMaxDaysDir permission denied\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;
	}
$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", time());
	if(!is_file("$BackupMaxDaysDir/$t")){
		if($GLOBALS["VERBOSE"]){echo "FATAL $BackupMaxDaysDir permission denied\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;		
	}
	@unlink("$BackupMaxDaysDir/$t");
	
	$q=new mysql_syslog();
	$sql="SELECT `filename`,`taskid`,`filesize`,`filetime` FROM `store` WHERE filetime<DATE_SUB(NOW(),INTERVAL 30 DAY)";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo "$sql ($q->mysql_error) ". mysql_num_rows($results)." file(s)\n";}
	
	if(!$q->ok){
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {	
		if($GLOBALS["VERBOSE"]){echo "Processing {$ligne["filename"]}\n";}
		if(!ExtractFileFromDatabase($ligne["filename"],$BackupMaxDaysDir)){return false;}
	}
	
	
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
		if($GLOBALS["VERBOSE"]){echo "FATAL ($q->mysql_error)\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;
	}
	
	if(!@copy("$TempDir/$filebase", "$nextDir/$filebase")){
		if($GLOBALS["VERBOSE"]){echo "FATAL $nextDir/$filebase permission denied\n";}
		system_admin_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__,"logrotate");
		return false;		
	}
	
	@unlink("$TempDir/$filebase");
	
	$q->QUERY_SQL("DELETE FROM store WHERE filename = '$filename'");
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "FATAL ($q->mysql_error)\n";}
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
	if($squidbin==null){$squidbin=$unix->find_program("squid");}
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($LogRotatePath)){$LogRotatePath="/home/logrotate";}		
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	if($SystemLogsPath<>"/var/log"){CheckLogStorageDir($SystemLogsPath);}
	
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}	
	@mkdir($LogsDirectoryStorage,0755,true);
	$q=new mysql_syslog();
	//RotateFiles,RotateType,RotateFreq,MaxSize,RotateCount,postrotate,description,enabled	
	$sql="SELECT *  FROM `logrotate` WHERE enabled=1";	
	system_admin_events($sql,__FUNCTION__,__FILE__,__LINE__,"logrotate");
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){return;}
	
	
	foreach (glob("/etc/logrotate.d/*") as $filename) {@unlink($filename);}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$f=array();
		$dir=$ligne["RotateFiles"];
		$dir=dirname($ligne["RotateFiles"]);
		if(!is_dir($dir)){continue;}
		$f[]="{$ligne["RotateFiles"]} {";
		$f[]="\t{$ligne["RotateFreq"]}";
		$f[]="\tmissingok";
		if($ligne["MaxSize"]>0){$f[]="\tsize {$ligne["MaxSize"]}M";}
		if($ligne["RotateCount"]>0){$f[]="\trotate {$ligne["RotateCount"]}";}
		if($LogRotateCompress==1){$f[]="\tcompress";}
		$f[]="\tsharedscripts";
		$f[]="\tcreate 640 root";
		$f[]="\tdateext";
		if($LogRotateCompress==1){$f[]="\tcompressext .bz2";}
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
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	
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
			$f[]="\tmissingok";
			if($ligneC["MaxSize"]>0){$f[]="\tsize {$ligne["MaxSize"]}M";}
			if($ligneC["RotateCount"]>0){$f[]="\trotate {$ligne["RotateCount"]}";}
			if($LogRotateCompress==1){$f[]="\tcompress";}
			$f[]="\tsharedscripts";
			$f[]="\tcreate 640 root";
			$f[]="\tdateext";
			if($LogRotateCompress==1){$f[]="\tcompressext .bz2";}
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

	if($CountDeFiles>0){
		system_admin_events("Executing exec.squid.stats.php --thumbs-parse for $CountDeFiles files.",__FUNCTION__,__FILE__,__LINE__,"logrotate");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --thumbs-parse >/dev/null 2>&1");
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


function check_all_squid(){
	$sock=new sockets();
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$pid=file_get_contents("$pidfile");
	
	
	if(system_is_overloaded(basename(__FILE__))){die();}
	

	if($unix->process_exists($pid,basename(__FILE__))){
		$timeMin=$unix->PROCCESS_TIME_MIN($pid);
		if($timeMin>240){
			system_admin_events("Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$kill=$unix->find_program("kill");
			shell_exec("$kill -9 $pid");
		}else{
			die();
		}
	}	
	
	$time=$unix->file_time_min($timefile);
	if($time<300){return;}
	
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($timefile, time());
	
	
	
	$bzip2=$unix->find_program("bzip2");
	$ALREADYCOMP["gz"]=true;
	$ALREADYCOMP["bz2"]=true;
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}	
	
	foreach (glob("/var/log/squid/*") as $filename) {
		if(is_dir($filename)){continue;}
		$size=$unix->file_size($filename);
		$time=$unix->file_time_min($filename);
		$size=round(($size/1024)/1000,2);
		
		if($size>$LogsRotateDefaultSizeRotation){$TOROT[$filename]=true;continue;}
		if($time>1440){$TOROT[$filename]=true;continue;}
	}

	if(count($TOROT)==0){return;}
		
	while (list ($filename, $none) = each ($TOROT) ){
		
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
		$basename=basename($filename);
		if($LogRotateCompress==1){
			if($extension<>"bz2"){
				if(!ROTATE_COMPRESS_FILE($filename)){continue;}
				$filename=$filename.".bz2";
				$extension="bz2";
			}
		}
			
		echo "[$filedate]: $filename ($extension)\n";
		if(ROTATE_TOMYSQL($filename, $filedate)){
			@unlink($filename);
		}
			
		
	}
	
	
}



function events($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
		$logFile="/var/log/artica-postfix/logrotate.debug";
		
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
