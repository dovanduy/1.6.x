<?php
$GLOBALS["FULL"]=false;
$GLOBALS["FORCE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		$GLOBALS["VERBOSE"]=true;
	}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}

ScanFoldders();
function ScanFoldders(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($GLOBALS["VERBOSE"]){echo "$timefile\n";}
	
	if(system_is_overloaded(basename(__FILE__))){die();}
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeMin=$unix->PROCCESS_TIME_MIN($pid);
		if($timeMin>240){
			system_admin_events("Too many TTL, $pid will be killed",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}else{
			die();
		}
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeExec=$unix->file_time_min($timefile);
		if($TimeExec<240){return;}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$maillogStoragePath=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/maillogStoragePath"));
	
	if($GLOBALS["VERBOSE"]){echo "Starting Main\n";}
	
	$q=new mysql();

	
	$q->QUERY_SQL("DROP TABLE `sysstorestatus`","artica_events");
	
	
	$sql="CREATE TABLE IF NOT EXISTS `sysstorestatus` (
			  `filepath` VARCHAR(255) NOT NULL,
			  `filesize`  BIGINT UNSIGNED NOT NULL,
			  `zDate` DATETIME,
			  PRIMARY KEY (`filepath`),
			  KEY `zDate` (`zDate`),
			  KEY `filesize` (`filesize`)
		
			)";
	$q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){echo $q->mysql_error; return; }
	
	
	ScanThis("/home/postfix/maillog");
	ScanThis("/home/logrotate");
	ScanThis("/home/logrotate_backup");
	ScanThis("/home/logs-backup");
	ScanThis("/home/backup/postfix");
	ScanThis($LogRotatePath);
	ScanThis($SystemLogsPath);
	ScanThis($maillogStoragePath);
}


function ScanThis($Directory=null){
	
	if($Directory==null){return;}
	if(!is_dir($Directory)){return null;}
	if(isset($GLOBALS["ALREADY_SCANNED"][$Directory])){return null;}
	if($GLOBALS["VERBOSE"]){echo "About \"$Directory\"\n";}
	
	$GLOBALS["ALREADY_SCANNED"][$Directory]=true;
	if(is_link($Directory)){$Directory=@readlink($Directory);}
	$unix=new unix();
	$dirs=$unix->dirdir($Directory);
	if(count($dirs)>0){
		while (list ($directoryPath, $value) = each ($dirs) ){
			if($GLOBALS["VERBOSE"]){echo "Rescan \"$directoryPath\"\n";}
			ScanThis($directoryPath);
			
		}
		
	}
	
	$files=$unix->DirFiles($Directory);
	if(count($files)==0){return;}
	$FILES_ARRAY_SQL=array();
	while (list ($filename, $value) = each ($files) ){
		$filepath="$Directory/$filename";
		$filetime=0;
		$filesize=round(@filesize($filepath)/1024,2);
		
		if($filetime==0){
			if(preg_match("#\.([0-9]+)\.[a-z]+$#", $filename,$re)){
				$filetime=$re[1];
			}
		}
		if($filetime==0){
			if(preg_match("#-([0-9]+)\.[a-z]+$#", $filename,$re)){
				$filetime=$re[1];
			}
		}
		if($filetime==0){
			if(preg_match("#\.log([0-9]+)\.[a-z]+$#", $filename,$re)){ $filetime=$re[1];}
		}
		
		
		if($filetime==0){
			if(preg_match("#-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)\.#", $filename,$re)){
				if(strlen($re[4])==1){$re[4]="0{$re[4]}";}
				$strdate="{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:00:00";
				$filetime=strtotime($strdate);
			}
		}
		
		if($filetime==0){$filetime=filemtime($filepath);}
		
		$filedate=date("Y-m-d H:i:s",$filetime);
		$FILES_ARRAY_SQL[]="('".mysql_escape_string2($filepath)."','$filesize','$filedate')";
		if($GLOBALS["VERBOSE"]){echo "$filedate - $filepath: $filesize\n";}
	}
	
	
	
	if(count($FILES_ARRAY_SQL)>0){
		$q=new mysql();
		$q->QUERY_SQL("INSERT IGNORE INTO sysstorestatus 
				(`filepath`,`filesize`,`zDate`) VALUES ".@implode(",", $FILES_ARRAY_SQL),"artica_events");
		
	}
	
	
	
}