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

if($argv[1]=="--tests"){tests($argv[2]);die();}


parse();


function tests($filepath){
	$berekley=new parse_berekley_dbs();
	$array=$berekley->PROTO_PARSE_DB($filepath, $xdate);
	print_r($array);
}

function parse(){
	$TimeFile="/etc/artica-postfix/pids/exec.squid.stats.mime.proto.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.mime.proto.php.pid";
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
	
	$f=$unix->DirFiles("/var/log/squid","[0-9]+_proto\.db");
	$export_path="/home/artica/squid/dbExport";
	@mkdir($export_path,0755,true);
	$berekley=new parse_berekley_dbs();
	$q=new mysql_squid_builder();
	
	while (list ($filename, $none) = each ($f) ){
		preg_match("#([0-9]+)_#", $filename,$re);
		$xdate=$re[1];
		$xtime=$berekley->TIME_FROM_DAY_INT($xdate);
		echo "$filename ( $xdate )\n";
		
		if(date("Y-m-d",$xtime)=="1970-01-01"){continue;}
		
		if(date("Y-m-d",$xtime)==date("Y-m-d")){
			
			if(!$q->QUERY_SQL($berekley->PROTO_PARSE_TABLE_STRING("PROTO_RTT"))){continue;}
			$q->QUERY_SQL("TRUNCATE TABLE PROTO_RTT");
			$array=$berekley->PROTO_PARSE_DB("/var/log/squid/$filename", $xdate);
			$prefix=$berekley->PROTO_PARSE_TABLE_PREFIX("PROTO_RTT");
			if(!$array){continue;}
			$sql=$prefix." ".@implode(",", $array);
			$q->QUERY_SQL($sql);
			continue;
		}
		
		$tablename=date("Ym",$xtime)."_proto";
		if(!$q->QUERY_SQL($berekley->PROTO_PARSE_TABLE_STRING($tablename))){continue;}
		$array=$berekley->PROTO_PARSE_DB("/var/log/squid/$filename", $xdate);
		$prefix=$berekley->PROTO_PARSE_TABLE_PREFIX($tablename);
		if(!$array){continue;}
		$sql=$prefix." ".@implode(",", $array);
		$q->QUERY_SQL($sql);
		if(!$q->ok){continue;}
		
		if(!@copy("/var/log/squid/$filename", "$export_path/$filename")){continue;}
		@unlink("/var/log/squid/$filename");
		
	}
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_SIZE.db");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_HITS.db");
	
	$sql="SELECT * FROM PROTO_RTT ORDER BY size DESC;";
	$results=$q->QUERY_SQL($sql);
	$array=array();
	if(mysql_num_rows($results)>1){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["size"]=$ligne["size"]/1024;
			$ligne["size"]=$ligne["size"]/1024;
			$ligne["size"]=round($ligne["size"],2);
			if($GLOBALS["VERBOSE"]){echo "{$ligne["size"]}MB = {$ligne["proto"]}\n";}
			if($GLOBALS["VERBOSE"]){echo "{$ligne["hits"]}hits = {$ligne["proto"]}\n";}
			
			$MAIN_SIZE[$ligne["proto"]]=$ligne["size"];
			$MAIN_HITS[$ligne["proto"]]=$ligne["hits"];
		}
		@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_SIZE.db", serialize($MAIN_SIZE));
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_HITS.db", serialize($MAIN_HITS));
		@chmod("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_SIZE.db",0755);
		@chmod("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_HITS.db",0755);
	}
	
	
	
}


	