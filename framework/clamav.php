<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["sigtool"])){sigtool();exit;}
if(isset($_GET["freshclam-progress"])){freshclam_progress();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["access-events"])){access_events();exit;}
if(isset($_GET["freshclam-services"])){freshclam_service();exit;}
if(isset($_GET["freshclam-status"])){freshclam_status();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();




function sigtool(){
$unix=new unix();
$sigtool=$unix->find_program("sigtool");
if(strlen($sigtool)<5){die();}
if(is_file("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases")){
	$ttim=$unix->file_time_min("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
	if($ttim<30){return;}
}

$baseDir="/var/lib/clamav";

$patnz=$unix->DirFiles($baseDir,"\.(cvd|cld|hdb|ign2|ndb)$");

while (list ($path, $none) = each ($patnz) ){
	$patterns[basename($path)]=true;
}

while (list ($pattern, $none) = each ($patterns) ){
	if(!is_file("$baseDir/$pattern")){continue;}
	$results=array();
	exec("$sigtool --info=$baseDir/$pattern 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		
		if(preg_match("#Build time:\s+(.+)#", $line,$re)){
			$time=strtotime($re[1]);
			$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s");
			continue;
		}
		
		if(preg_match("#Version:\s+([0-9]+)#",$line,$re)){
			$MAIN[$pattern]["version"]=$re[1];
			continue;
		} 
		
		if(preg_match("#Signatures:\s+([0-9]+)#",$line,$re)){
			$MAIN[$pattern]["signatures"]=$re[1];
			continue;
		} 		
	}
	
	if(!isset($MAIN[$pattern]["zDate"])){
		$time=filemtime("$baseDir/$pattern");
		$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s",$time);
		
		if(!isset($MAIN[$pattern]["version"])){
			$MAIN[$pattern]["version"]=date("YmdHi",$time);
		}
		
	}
	if(!isset($MAIN[$pattern]["signatures"])){
		$MAIN[$pattern]["signatures"]=$unix->COUNT_LINES_OF_FILE("$baseDir/$pattern");
	}
	
}
if(count($MAIN)==0){return;}
@file_put_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases", serialize($MAIN));
	
}


$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.updates.progress";
$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/clamav.updates.progress.txt";


function freshclam_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/clamav.update.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/clamav.update.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.freshclam.php --execute --force --progress --cli >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function freshclam_service(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.freshclam.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/clamav.freshclam.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.freshclam.php --restart --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function freshclam_status(){
	
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --freshclam >/usr/share/artica-postfix/ressources/logs/web/freshclam.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function pattern(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.haarp.php --squid-pattern >/dev/null 2>&1");	
	
}
function access_events(){
	
	$filename="/var/log/squid/haarp.access.log";
	$search=$_GET["access-events"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
