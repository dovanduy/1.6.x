<?php
//exec.squid.stats.mime.parser.php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');

parse();
function parse(){
	$TimeFile="/etc/artica-postfix/pids/exec.squid.stats.mime.parser.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.mime.parser.php.pid";
	$unix=new unix();
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<14){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	
	$TimeExec=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if($TimeExec<20){return;}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$f=$unix->DirFiles("/var/log/squid","[0-9]+_mime\.db");
	$export_path="/home/artica/squid/dbExport";
	@mkdir($export_path,0755,true);
	$berekley=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	
	while (list ($filename, $none) = each ($f) ){
		preg_match("#([0-9]+)_#", $filename,$re);
		$xdate=$re[1];
		$xtime=$berekley->TIME_FROM_DAY_INT($xdate);
		echo "$filename ( $xdate )\n";
		if(date("Y-m-d",$xtime)==date("Y-m-d")){
			
			if(!$q->QUERY_SQL($berekley->MIME_PARSE_TABLE_STRING("MIME_RTT"))){continue;}
			$q->QUERY_SQL("TRUNCATE TABLE MIME_RTT");
			$array=$berekley->MIME_PARSE_DB("/var/log/squid/$filename", $xdate);
			$prefix=$berekley->MIME_PARSE_TABLE_PREFIX("MIME_RTT");
			if(!$array){continue;}
			$sql=$prefix." ".@implode(",", $array);
			$q->QUERY_SQL($sql);
			continue;
		}
		
		$tablename=date("Ym",$xtime)."_mime";
		if(!$q->QUERY_SQL($berekley->MIME_PARSE_TABLE_STRING($tablename))){continue;}
		$array=$berekley->MIME_PARSE_DB("/var/log/squid/$filename", $xdate);
		$prefix=$berekley->MIME_PARSE_TABLE_PREFIX($tablename);
		if(!$array){continue;}
		$sql=$prefix." ".@implode(",", $array);
		$q->QUERY_SQL($sql);
		if(!$q->ok){continue;}
		
		if(!@copy("/var/log/squid/$filename", "$export_path/$filename")){continue;}
		@unlink("/var/log/squid/$filename");
		
	}
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/TOP_MIME.db");
	$sql="SELECT SUM(size) as size,mime_type FROM MIME_RTT GROUP BY mime_type ORDER BY size DESC LIMIT 0,15;";
	$results=$q->QUERY_SQL($sql);
	$array=array();
	if(mysql_num_rows($results)>1){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["size"]=$ligne["size"]/1024;
			$ligne["size"]=$ligne["size"]/1024;
			$ligne["size"]=round($ligne["size"],2);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["size"]}MB = {$ligne["mime_type"]}\n";}
			if(preg_match("#application\/(.+)#", $ligne["mime_type"],$re)){$ligne["mime_type"]=$re[1];}
			$MAIN[$ligne["mime_type"]]=$ligne["size"];
		}
		@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_MIME.db", serialize($MAIN));
		@chmod("/usr/share/artica-postfix/ressources/logs/web/TOP_MIME.db",0755);
	}
	
	gencache_TOPCACHED();
	
}
function gencache_TOPCACHED(){
	$q=new mysql_squid_builder();
	$file1="/usr/share/artica-postfix/ressources/logs/web/TOP_CACHED.db";
	
	@unlink($file1);
	
	$current_table="CACHED_SITES";
	if(!$q->TABLE_EXISTS($current_table)){return;}
	
	
	$sql="SELECT * FROM CACHED_SITES ORDER BY size DESC LIMIT 0,15";
		
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
	if($Count<2){return ;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size);
		if($GLOBALS["VERBOSE"]){echo "{$size}MB = {$ligne["familysite"]}\n";}
		
		$ARRAY[$ligne["familysite"]]=$size;
	}
	@file_put_contents($file1,serialize($ARRAY));
	@chmod($file1, 0755);
	
}



function gencache_TOP(){
	
	$file="/usr/share/artica-postfix/ressources/logs/web/TOP_CACHED.db";
	@unlink($file1);
	@unlink($file2);
	$q=new mysql_squid_builder();

	$current_table=date("Ymd")."_gcache";
	if(!$q->TABLE_EXISTS($current_table)){
		$xtime=strtotime($q->HIER()." 00:00:00");
		$current_table=date("Ymd",$xtime)."_gcache";
	}

	if(!$q->TABLE_EXISTS($current_table)){ if($GLOBALS["VERBOSE"]){echo "$current_table no such table...\n";} return;}

	$sql="SELECT familysite,cached,SUM(size) as size FROM
	$current_table GROUP BY cached,familysite HAVING cached=1 ORDER BY `size` DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
	if($Count<2){return ;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size);
		$ARRAY[$ligne["familysite"]]=$size;
	}
	@file_put_contents($file1,serialize($ARRAY));
	@chmod($file1, 0755);
	$ARRAY=array();


	$sql="SELECT familysite,cached,SUM(size) as size FROM
	$current_table GROUP BY cached,familysite HAVING cached=0 ORDER BY `size` DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo $sql."\n$Count items \n";}
	if($Count<2){return ;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size);
		$ARRAY[$ligne["familysite"]]=$size;
	}
	@file_put_contents($file2,serialize($ARRAY));
	@chmod($file2, 0755);
	$ARRAY=array();



}

	