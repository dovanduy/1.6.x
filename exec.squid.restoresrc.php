<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");

if($argv[1]=="--restore"){echo restore($argv[2],$argv[3]);return;}


function restore($filename,$storeid){
	$filename=trim($filename);
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$filename.pid";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){die();}
	@file_put_contents($pidfile, getmypid());
	$EnableSyslogDB=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	@mkdir("/var/log/artica-postfix/squid-brut",0777,true);
	@mkdir("/var/log/artica-postfix/squid-reverse",0777,true);
	

	
	$GLOBALS["filename"]=$filename;
	$sock=new sockets();
	$TempDir="/home/artica-extract-temp";
	@mkdir($TempDir,0777);
	@chown($TempDir, "mysql");
	@chdir($TempDir, "mysql");
	
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");


	$bzip2=$unix->find_program("bzip2");
	$gunzip=$unix->find_program("gunzip");
	
	
	
	progress("Extract $filename from MySQL database into $TempDir",4);
	if($EnableSyslogDB==1){
		$q=new mysql_storelogs();
		$sql="SELECT filecontent INTO DUMPFILE '$TempDir/$filename' FROM files_store WHERE ID = '$storeid'";
		$q->QUERY_SQL($sql);
	}else{
		$q=new mysql_syslog();
		$sql="SELECT filedata INTO DUMPFILE '$TempDir/$filename' FROM store WHERE filename = '$filename'";
		$q->QUERY_SQL($sql);
	}
	if(!$q->ok){progress("Failed!!! $q->mysql_error",100);return;}
	$file_extension=file_extension($filename);
	progress("Extract $filename extension: $file_extension",5);
	$newtFile=$filename.".log";
	
	if($file_extension=="bz2"){
		$cmdline="bzip2 -d \"$TempDir/$filename\" -c >\"$TempDir/$newtFile.log\" 2>&1";
		exec($cmdline,$results);
	}
	if($file_extension=="gz"){
		$cmdline="gunzip -d \"$TempDir/$filename\" -c >\"$TempDir/$newtFile.log\" 2>&1";
	}
	if($cmdline<>null){
		exec($cmdline,$results);
		progress("Extract done ".@implode(" ", $results),7);
	}else{
		if(!@copy("$TempDir/$filename","$TempDir/$newtFile.log")){
			progress("Failed!!! Copy error",100);
			return;
		}
	}
	@unlink("$TempDir/$filename");
	if(!is_file("$TempDir/$newtFile.log")){
		progress("Failed!!! $TempDir/$newtFile.log error no such file",100);	
		return;
	}
	$linesNumber=$unix->COUNT_LINES_OF_FILE("$TempDir/$newtFile.log");
	progress("Open $TempDir/$newtFile.log $linesNumber",10);
	
	$handle = @fopen("$TempDir/$newtFile.log", "r");
	if (!$handle) {progress("Failed!!! $TempDir/$newtFile.log open failed",100);return;}
	$c=0;
	$d=0;
	$TTEV=0;
	while (!feof($handle)){
		$c++;
		$buffer =trim(fgets($handle, 4096));
		if(!preg_match("#MAC:.*?\[([0-9]+)\/(.*?)\/([0-9]+).*?:([0-9]+):([0-9]+):([0-9]+)\s+(.*?)\]\s+\"#", $buffer,$re)){continue;}
		$dteStr="{$re[1]}/{$re[2]}/{$re[3]}:{$re[4]}:{$re[5]}:{$re[6]} {$re[7]}";
		$ttime=strtotime($dteStr);
		$newDate=date("Y-m-d H",$ttime)."h";
		$datelog=date("Y-m-d-h",$ttime);
		$MD5Buffer=md5($buffer);
		$TTEV++;
		@mkdir("/var/log/artica-postfix/squid-brut/$datelog",0777,true);
		@file_put_contents("/var/log/artica-postfix/squid-brut/$datelog/$MD5Buffer", $buffer);
		
		
		if($c>10){
			$d=$d+$c;
			$pp=$d/$linesNumber;
			$pp=$pp*100;
			$pp=round($pp,1);
			if($pp>10){
				if($pp>100){$pp=99;}
				progress("Processing $d/$linesNumber - $newDate ",$pp);
				$c=0;
			}
		}
	}
	
	progress("Success, $TTEV events sent to MySQL injector ",100);
	@unlink("$TempDir/$newtFile.log");
}

function progress($text,$pourc){
	if($pourc>100){$pourc=99;}
	$pid=getmypid();
	$time=date("H:i:s");
	$file="/usr/share/artica-postfix/ressources/logs/web/{$GLOBALS["filename"]}-restore.pr";
	$array["POURC"]=$pourc;
	$array["TEXT"]="[$pid]: $time  - $text";
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% [$pid]: $time - $text\n";}
	@file_put_contents($file, serialize($array));
	@chmod($file,0777);
}

function file_extension($filename){
	return pathinfo($filename, PATHINFO_EXTENSION);
}

function events_squid_caches($text,$function,$line){
	$file="/var/log/squid/artica-caches32.log";
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	@mkdir(dirname($file));
	$logFile=$file;
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	echo "$date [$pid] $function::$line $text\n";
	@fwrite($f, "$date [$pid] $function::$line $text\n");
	@fclose($f);
	
}

