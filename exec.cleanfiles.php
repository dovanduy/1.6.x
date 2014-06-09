<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
	
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="{$GLOBALS["ARTICALOGDIR"]}"; } }


if($GLOBALS["VERBOSE"]){echo "TimeFile: $pidTime\n";}
if($argv[1]=="--admin-events"){clean_admin_events();exit;}
if($argv[1]=="--nmap"){clean_nmap_processes();exit;}
if($argv[1]=="--mysql"){CleanMySQL();exit;}




$unix=new unix();

if($unix->process_exists(@file_get_contents($pidpath))){
	writelogs(basename(__FILE__).":Already executed.. PID: ". @file_get_contents($pidpath). " aborting the process",basename(__FILE__),__FILE__,__LINE__);
	die();
}
@file_put_contents($pidpath,getmypid());
$time=$unix->file_time_min($pidTime);
if($time<120){die();}
@unlink($pidTime);
@file_put_contents($pidTime, time());
	
	
	CleanTinyProxy();
	CleanTempDirs();
	CleanArticaUpdateLogs();
	ParseMysqlEventsQueue();
	clean_nmap_processes();
	CleanMySQL();
	die();
	
	
function CleanMySQL(){
	$sock=new sockets();
	$unix=new unix();
	$dirs=$unix->dirdir("/var/lib/mysql");
	while (list ($directory, $ligne) = each ($dirs) ){
		CleanMySQLBAK($directory);
		
	}
	
	
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}	
	if(is_dir($WORKDIR)){
		$dirs=$unix->dirdir("$WORKDIR/data");
		while (list ($directory, $ligne) = each ($dirs) ){
			CleanMySQLBAK($directory);
	
		}
	}
	
	
}

function CleanMySQLBAK($directory){

	$unix=new unix();
	foreach (glob("$directory/*.BAK") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time<380){continue;}
		@unlink($filename);
	}


}
	
	
function CleanTempDirs(){
	$unix=new unix();
	$dirs=$unix->dirdir("/tmp");
	if(!is_array($dirs)){return null;}
	while (list ($num, $ligne) = each ($dirs) ){
		if(trim($num)==null){continue;}
		$time=$unix->file_time_min($num);
		if($time<380){continue;}
		if(is_dir($num)){
			shell_exec("/bin/rm -rf \"$num\"");
		}
		
	}
	if (!$handle = opendir("/")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/$filename";
		if(is_numeric($filename)){@unlink($targetFile);}
	}
	
	if(is_file("/usr/share/artica-postfix/ressources/exec.syslog-engine.php")){ @unlink("/usr/share/artica-postfix/ressources/exec.syslog-engine.php"); }
	
	CleanTimedFiles($unix->TEMP_DIR(),380);
	CleanTimedFiles("/tmp",680);
	CleanTimedFiles("/usr/share/artica-postfix/ressources/logs/jGrowl",240);
	
	
}

function CleanTimedFiles($directory,$maxtime){
	
	if (!$handle = opendir($directory)) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$directory/$filename";
		if(!is_file($targetFile)){continue;}
		$file_time_min=file_time_min($filename);
		if(file_time_min($filename)<$maxtime){continue;}
		@unlink($filename);
	}
	
}

function clean_nmap_processes(){
	$unix=new unix();
	$nmap=$unix->find_program("nmap");
	if(!is_file($nmap)){return;}
	$pids=$unix->PIDOF_PATTERN_ALL($nmap);
	
	while (list ($pid, $ar) = each ($pids) ){
		$Time=$unix->PROCCESS_TIME_MIN($pid,60);
		
	}
	
	
}


function CleanTinyProxy(){
	if(!is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){return;}
	$BaseWorkDirs[]="{$GLOBALS["ARTICALOGDIR"]}/squid-usersize";
	$BaseWorkDirs[]="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-queue";
	while (list ($num, $workdir) = each ($BaseWorkDirs) ){
		if(!is_dir($workdir)){return;}
		if (!$handle = opendir($workdir)) {continue;}
		while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="$workdir/$filename";
				@unlink($targetFile);
				$c++;
		}		
	}
	
}

function CleanArticaUpdateLogs(){
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/artica-update-*.debug") as $filename) {
		$file_time_min=file_time_min($filename);
		if(file_time_min($filename)>5752){@unlink($filename);}
		}

}


function ParseMysqlEventsQueue(){
	$q=new mysql();
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/sql-events-queue/*.sql") as $filename) {
			$sql=@file_get_contents($filename);
			$q->QUERY_SQL($sql,"artica_events");
			if($q->ok){
				@unlink($filename);
			}
		}	
	}
	
function clean_admin_events(){
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/system_admin_events";
	if (!$handle = opendir($BaseWorkDir)) {
		echo "Failed open $BaseWorkDir\n";
		return;
	}
	$c=0;
	while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			@unlink($targetFile);
			$c++;
	}
	echo "$c cleaned files\n";
}

?>