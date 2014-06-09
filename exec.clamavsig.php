<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["VERBOSE"]=false;$GLOBALS["BYCRON"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/".basename(__FILE__).".time";
	if($unix->file_time_min($pidtime)<3){die();}
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$ProcessTime=$unix->PROCCESS_TIME_MIN($pid);
		writelogs("Process $pid  already in memory since $ProcessTime minutes","MAIN",__FILE__,__LINE__);
		die();
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());

$baseDir="/var/lib/clamav";

$patterns["bytecode.cvd"]=true;
$patterns["daily.cld"]=true;
$patterns["main.cvd"]=true;

    
$unix=new unix();
$sigtool=$unix->find_program("sigtool");
if(strlen($sigtool)<5){die();}


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
}

if(count($MAIN)==0){die();}

$q=new mysql();
if(!$q->TABLE_EXISTS("clamavsig", "artica_backup")){$q->BuildTables();}
if(!$q->TABLE_EXISTS("clamavsig", "artica_backup")){return;}
$q->QUERY_SQL("TRUNCATE TABLE clamavsig","artica_backup");
$prefix="INSERT IGNORE INTO clamavsig (`patternfile`,`zDate`,`version`,`signatures`) VALUES ";
while (list ($pattern, $INFOS) = each ($MAIN) ){
	$f[]="('$pattern','{$INFOS["zDate"]}','{$INFOS["version"]}','{$INFOS["signatures"]}')";
	
	
}

if(count($f)==0){return;}
$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
?>