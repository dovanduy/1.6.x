<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



xstart();


function xstart(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$timefile="/etc/artica-postfix/pids/exec.ufdb.parse-categories.php.time";
	$unix=new unix();
	$me=basename(__FILE__);
	$pid=$unix->get_pid_from_file($pidfile);
	if(system_is_overloaded()){die();}
	
	if($unix->process_exists($pid,$me)){
		if($GLOBALS["VERBOSE"]){echo " $pid --> Already executed.. aborting the process\n";}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		die();
	}
	
	@file_put_contents($pidfile, getmypid());
	if($unix->file_time_min($timefile)<60){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$q=new mysql_squid_builder();
	$DirsArtica=$unix->dirdir("/var/lib/ufdbartica");

	$sql="CREATE TABLE IF NOT EXISTS `UPDATE_DBWF_INFOS` ( 
	`category` varchar(90) NOT NULL, `size_artica` INT UNSIGNED NOT NULL, `date_artica` INT UNSIGNED NOT NULL, `count_artica` INT UNSIGNED NOT NULL, `size_tlse` INT UNSIGNED NOT NULL, `date_tlse` INT UNSIGNED NOT NULL, `count_tlse` INT UNSIGNED NOT NULL, `size_perso` INT UNSIGNED NOT NULL, `date_perso` INT UNSIGNED NOT NULL, `count_perso` INT UNSIGNED NOT NULL, PRIMARY KEY (`category`) 
			) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$MAX=144;
	$c=0;
	$UFDB=array();
	
	$UFDBCOUNT=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/ufdbcounts.txt")));
	
	
	while (list ($dir, $line) = each ($DirsArtica)){
		if(is_link($dir)){continue;}
		$database_path="$dir/domains.ufdb";
		if(!is_file($database_path)){continue;}
		$tablename=basename($dir);
		$size=@filesize("$dir/domains.ufdb");
		$time=filemtime("$dir/domains.ufdb");
		$cat=$q->tablename_tocat($tablename);
		$MAIN[$cat]["ART"]["SIZE"]=$size;
		$MAIN[$cat]["ART"]["TIME"]=$time;
		$MAIN[$cat]["ART"]["COUNT"]=$UFDBCOUNT[$tablename];
	}
	
	$DirsArtica=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($dir, $line) = each ($DirsArtica)){
		$database_path="$dir/domains.ufdb";
		$sourcefile="$dir/domains";
		if(!is_file($database_path)){continue;}
		$cat=basename($dir);
		$cat=$q->filaname_tocat($cat);
		$size=@filesize("$dir/domains.ufdb");
		$time=filemtime("$dir/domains.ufdb");
		$MAIN[$cat]["TLSE"]["SIZE"]=$size;
		$MAIN[$cat]["TLSE"]["TIME"]=$time;
		$MAIN[$cat]["TLSE"]["COUNT"]=$unix->COUNT_LINES_OF_FILE($sourcefile);
		if(system_is_overloaded()){ @unlink("$timefile"); die(); }
	}
	
	
	$DirsArtica=$unix->dirdir("/var/lib/squidguard");
	while (list ($dir, $line) = each ($DirsArtica)){
		$database_path="$dir/domains.ufdb";
		if(!is_file($database_path)){continue;}
		$tablename="category_".basename($dir);
		$cat=$q->tablename_tocat($tablename);
		$size=@filesize("$dir/domains.ufdb");
		$time=filemtime("$dir/domains.ufdb");
		$sourcefile="$dir/domains";
		$MAIN[$cat]["PERSO"]["SIZE"]=$size;
		$MAIN[$cat]["PERSO"]["PATH"]=$dir;
		$MAIN[$cat]["PERSO"]["CATEGORY"]=$cat;
		$MAIN[$cat]["PERSO"]["TIME"]=$time;
		$MAIN[$cat]["PERSO"]["COUNT"]=$unix->COUNT_LINES_OF_FILE($sourcefile);
		if(system_is_overloaded()){ @unlink("$timefile"); die(); }
	}
	
	$prefix="INSERT IGNORE INTO `UPDATE_DBWF_INFOS` (`category`,
	`size_artica` ,
	`date_artica` ,
	`count_artica` ,

	`size_tlse` ,
	`date_tlse` ,
	`count_tlse` ,			
			
	`size_perso` ,
	`date_perso` ,
	`count_perso`) VALUES ";
	
	
	while (list ($category, $MAINZ) = each ($MAIN)){
		$f[]="('$category','{$MAINZ["ART"]["SIZE"]}','{$MAINZ["ART"]["TIME"]}','{$MAINZ["ART"]["COUNT"]}','{$MAINZ["TLSE"]["SIZE"]}','{$MAINZ["TLSE"]["TIME"]}','{$MAINZ["TLSE"]["COUNT"]}','{$MAINZ["PERSO"]["SIZE"]}','{$MAINZ["PERSO"]["TIME"]}','{$MAINZ["PERSO"]["COUNT"]}')";
		
	}
	
	$q->QUERY_SQL("TRUNCATE TABLE `UPDATE_DBWF_INFOS`");
	$sql=$prefix.@implode(",", $f);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
}