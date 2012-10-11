<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=='--squid-store-logs'){CleanSquidStoreLogs();die();}

if(system_is_overloaded(__FILE__)){
	writelogs("This system is overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	die();
}


if($argv[1]=='--clean-logs'){Clean_tmp_path(true);CleanLogs();die();}
if($argv[1]=='--clean-tmp2'){Clean_tmp_path(true);die();}
if($argv[1]=='--clean-tmp'){CleanLogs();die();}
if($argv[1]=='--clean-sessions'){sessions_clean();die();}
if($argv[1]=='--clean-install'){CleanOldInstall();die();}
if($argv[1]=='--paths-status'){PathsStatus();die();}
if($argv[1]=='--maillog'){maillog();die();}
if($argv[1]=='--wrong-numbers'){wrong_number();die();}
if($argv[1]=='--DirectoriesSize'){DirectoriesSize();die();}
if($argv[1]=='--cleanbin'){Cleanbin();die();}
if($argv[1]=='--zarafa-locks'){ZarafaLocks();die();}
if($argv[1]=='--squid-caches'){CleanCacheStores();die();}




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

function Clean_tmp_path($aspid=false){
	$unix=new unix();
	if($aspid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$oldpid=@file_get_contents($pidpath);
		if($unix->process_exists($oldpid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $oldpid running.. Aborting");
			return;
		}
	}
	@file_put_contents($pidpath,getmypid());
	
	if ($handle = opendir("/tmp")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/tmp/$file";
				if($GLOBALS["VERBOSE"]){echo "$path ?\n";} 
				if(is_dir($path)){if($GLOBALS["VERBOSE"]){echo "$path is a directory\n";} continue;}
				if(preg_match("#^artica-.+?\.tmp#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
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
	$sizePHP=round(unix_file_size("$filepath")/1024);
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
		if($time>2){
			if($owner=="root"){@unlink($filename);}
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
	$f[]="/var/lib";
	$f[]="/var/log";
	$f[]="/var/www";
	$f[]="/usr/share";
	$f[]="/opt";
	$unix=new unix();
	
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE `DirectorySizes`","artica_events");
	$prefix="INSERT IGNORE INTO DirectorySizes (zmd5,path,size) VALUES ";
	
	$du=$unix->find_program($du);
	while (list ($num, $directory) = each ($f)){
		$dirs=array();
		$dirs=$unix->dirdir($directory);
		while (list ($a, $b) = each ($dirs)){
			$size=$unix->DIRSIZE_BYTES($a);
			$tt=round(($size/1024)/1000);
			if($tt>1){
				echo "$a -> " .round(($size/1024)/1000)." MB\n";
			}
			$md=md5($a);
			$sql[]="('$md','$a','$size')";
			
			if(count($sql)>100){
				$q->QUERY_SQL($prefix.@implode(",", $sql),"artica_events");
				$sql=array();
			}
			
		}
		
	}
	if(count($sql)>0){$q->QUERY_SQL($prefix.@implode(",", $sql),"artica_events");}
	
	
}



//############################################################################## 
?>