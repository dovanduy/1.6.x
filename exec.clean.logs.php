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

if(!$GLOBALS["VERBOSE"]){
	echo "starting ".@implode("; ", $argv)."\n";
}
if($argv[1]=='--urgency'){UrgencyChecks();exit;}
if($argv[1]=='--logrotatelogs'){logrotatelogs(true);die();}
if($argv[1]=='--squid-store-logs'){CleanSquidStoreLogs();die();}
if($argv[1]=='--used-space'){used_space();die();}
if($argv[1]=='--cleandb'){CleanLogsDatabases(true);die();}
if(!$GLOBALS["FORCE"]){
	if(system_is_overloaded(__FILE__)){
		writelogs("This system is overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
}


if($argv[1]=='--clean-logs'){Clean_tmp_path(true);CleanLogs();logrotatelogs(true);die();}
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
if($argv[1]=='--squid-caches'){CleanCacheStores();die();}
if($argv[1]=='--rotate'){CleanRotatedFiles();die();}
if($argv[1]=='--squid'){clean_squid_users_size();die();}
if($argv[1]=='--artica-logs'){artica_logs();die();}




echo "Could not understand your query ???\n";


if(systemMaxOverloaded()){
	writelogs("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	die();
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

function CleanCacheStores(){
	$unix=new unix();
	$users=new usersMenus();
	return;
	if(!$users->SQUID_INSTALLED){return;}
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
		if(isset($effective[$directory])){continue;}
		$dirname=basename($directory);
		
		if(preg_match("#^squid.*#", $dirname)){
			if($GLOBALS["VERBOSE"]){echo "Found dir `$dirname`\n";}
			$unix->send_email_events("Old squid cache directory $dirname will be deleted", "", "logs_cleaning");
			system_admin_events("Old squid cache directory $dirname will be deleted", __FUNCTION__, __FILE__, __LINE__, "clean");
			squid_admin_notifs("Old squid cache directory $dirname will be deleted", __FUNCTION__, __FILE__, __LINE__, "clean");
			shell_exec("$rm -rf $directory >/dev/null 2>&1");
		}
	}
	
	
}

function CleanSquidStoreLogs(){
	$unix=new unix();
	$sock=new sockets();
	$SquidMaxStoreLogSize=$sock->GET_INFO("SquidMaxStoreLogSize");
	if(!is_numeric($SquidMaxStoreLogSize)){$SquidMaxStoreLogSize=500;}
	$deleted=false;
	foreach (glob("/var/log/squid/store.*") as $filename) {
		$size=$unix->file_size($filename);
		$size=$size/1024;
		$size=$size/1000;
		echo "$filename = $size MB\n";
		if($size>$SquidMaxStoreLogSize){
			echo "Remove $filename -> $size\n";
			@unlink($filename);
			$deleted=true;
		}
	}
	
	if($deleted){
		$squid=$unix->find_program("squid");
		if(!is_file($squid)){$squid=$unix->find_program("squid3");}
		if(!is_file($squid)){return;}
		shell_exec("$squid -k rotate");
	}
	
}


function ZarafaLocks(){
	$unix=new unix();
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


}


function maillog(){
	init();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		if(is_numeric(basename($filename))){@unlink($filename);}
	}
	

}

function artica_logs(){
	$unix=new unix();
	$Dir="/var/log/artica-postfix";
	if ($handle = opendir($Dir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$Dir/$file";
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

function Clean_tmp_path($aspid=false){
	$unix=new unix();
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
	

	
	
	
	if ($handle = opendir("/tmp")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/tmp/$file";
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
		if($GLOBALS["VERBOSE"]){echo "/tmp failed...\n";} 
	}
	if($GLOBALS["VERBOSE"]){echo "/tmp done..\n";}
	ZarafaLocks();
	CleanSquidStoreLogs();
	CleanCacheStores();
	CleanRotatedFiles();
	CleanLogsDatabases();
	sessions_clean();
	clean_artica_workfiles("/var/log/artica-postfix/Postfix-sql-error");
	clean_squid_users_size(true);
	artica_logs();
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
	$date=@date("h:i:s");
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
	
	while (list ($WorkingDir, $ligne) = each ($DirsToScan) ){	
	
	$table=$unix->DirFiles($WorkingDir,"(\.|-)[0-9]+.*?$");
	$compressed["gz"]=true;
	$compressed["bz"]=true;
	$compressed["bz2"]=true;
	
	while (list ($filename, $ligne) = each ($table) ){
		$path="$WorkingDir/$filename";
		$ext=$unix->file_extension($filename);
		echo "$path -> `$ext`\n";
		if(!isset($compressed[$ext])){
			if(!$unix->compress($path, "$path.gz")){system_admin_events("Unable to compress $path", __FUNCTION__, __FILE__, __LINE__, "clean");continue;}
			@unlink($path);
			$filename="$filename.gz";
			$path="$WorkingDir/$filename";
			
		}
		$filedate=date('Y-m-d H:i:s',filemtime($path));
		$filesize=$unix->file_size($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$basenameFF=$filename;
		$basenameFF=str_replace(".$ext", "", $basenameFF);
		$basenameFF=$basenameFF.".".basename($WorkingDir).".".time().".$ext";			
		$taskid=0;
		
		if($LogRotateMysql==1){
			system_admin_events("$path => /tmp/$filename => MySQL...",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			@copy($path, "/tmp/$filename");
			@unlink($path);
			@chmod("/tmp/$filename", 0777);

				
			$sql = "INSERT INTO `store` (`filename`,`taskid`,`filesize`,`filedata`,`filetime`) 
			VALUES ('$basenameFF','$taskid','$filesize',LOAD_FILE('/tmp/$filename'),'$filedate')";
			echo "$sql\n";
			$q->QUERY_SQL($sql);
				
				
			if(!$q->ok){system_admin_events("$q->mysql_error, go back /tmp/$filename => $path...",__FUNCTION__,__FILE__,__LINE__,"logrotate");@copy("/tmp/$basename", "$filename");}
			@unlink("/tmp/$filename");
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
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-postfix restart sysloger");
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
		shell_exec("/etc/init.d/artica-postfix restart postfix-logger");
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
		shell_exec("/etc/init.d/artica-postfix restart sysloger");
		shell_exec("/etc/init.d/artica-postfix restart auth-logger");
		shell_exec("/etc/init.d/artica-postfix restart postfix-logger");
	}
	
}


function CleanLogs(){
	maillog();
	MakeSpace();
	CleanSquidStoreLogs();
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
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$sizePHP;
		$GLOBALS["DELETED_FILES"]++;
		$GLOBALS["DELETED_FILES"][]="/var/log/php.log";
	}
	
	
	@unlink($timeOfFile);
	@file_put_contents($timeOfFile,"#");
	wrong_number();
	Clean_tmp_path();
	CleanOldInstall();
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
	foreach (glob("/var/lib/php5/*") as $filename) {
		$array=$unix->alt_stat($filename);
		$owner=$array["owner"]["owner"]["name"];
		$time=file_time_min($filename);
		if($GLOBALS["VERBOSE"]){echo "$filename :{$time}Mn\n";}
		if($time>360){
			@unlink($filename);
		}
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

function clean_artica_workfiles($dir){
	$unix=new unix();
	foreach (glob("$dir/*") as $filepath) {
		$time=$unix->file_time_min($filepath);
		if($time>240){@unlink($filepath);}
		
	}
	// 
	
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