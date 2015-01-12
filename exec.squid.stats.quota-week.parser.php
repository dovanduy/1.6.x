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

if($argv[1]=="--db"){db_dump($argv[2]);die();}

parse();
function parse(){
	
	$TimeFile="/etc/artica-postfix/pids/exec.squid.stats.quota-week.parser.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.quota-week.parser.php.pid";
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
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	$time=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if($time<1440){return;}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$f=$unix->DirFiles("/var/log/squid","[0-9]+_QUOTASIZE\.db");
	$export_path="/home/artica/squid/dbExport";
	@mkdir($export_path,0755,true);
	
	while (list ($filename, $none) = each ($f) ){
		preg_match("#([0-9]+)_#", $filename,$re);
		$xdate=$re[1];
		echo "$filename ( $xdate )\n";
		if($SquidPerformance>1){
			if(!@copy("/var/log/squid/$filename", "$export_path/$filename")){continue;}
			@unlink("/var/log/squid/$filename");
			continue;
		}
		
		if(!parse_file("/var/log/squid/$filename",$xdate)){continue;}
		if(!@copy("/var/log/squid/$filename", "$export_path/$filename")){continue;}
		@unlink("/var/log/squid/$filename");
		
	}
	
	
}

function WEEK_TIME_FROM_INT($xdate){
	$Cyear=substr($xdate, 0,4);
	$Cweek=substr($xdate,4,2);
	$q=new mysql_squid_builder();
	return strtotime("{$Cyear}W{$Cweek}");

}	

function parse_file($filename,$xdate){
	$f=array();
	$q=new mysql_squid_builder();
	$time=WEEK_TIME_FROM_INT($xdate);
	echo "$filename $xdate - $time ".date("Y-m-d",$time)."\n";
	if(date("YW",$time)==date("YW")){return;}
	if(date("Y-m-d",$time)=="1970-01-01"){@unlink($filename);return;}
	$class=new parse_berekley_dbs();
	$array=$class->SQUID_QUOTASIZES($filename);
	if(!is_array($array)){return false;}
	if(!$class->ok){return false;}
	
	$tablename_week=date("YW",$time)."_WQUOTASIZE";
	$sql=$class->SQUID_QUOTASIZE_CREATE_TABLE_WEEK_STRING($tablename_week, "MYISAM");
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	
	$PREFIX=$class->SQUID_QUOTASIZE_CREATE_TABLE_PREFIX($tablename_week);
	$sql=$PREFIX.@implode(",", $array);
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	
	
	$tablename_month=date("Ym",$time)."_MQUOTASIZE";
	$sql=$class->SQUID_QUOTASIZE_CREATE_TABLE_MONTH_STRING($tablename_month);
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	

	$results=$q->QUERY_SQL("SELECT SUM(size) as size,familysite,ipaddr,day,uid,MAC,category FROM `$tablename_week` GROUP BY familysite,ipaddr,day,uid,MAC,category");
	$prefix="INSERT IGNORE INTO `$tablename_month` ( `zmd5`,`ipaddr`,`MAC`,`uid`,familysite,`day`,`size`,`category`) VALUES ";
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=md5(serialize($ligne));
		$category=mysql_escape_string2($ligne["category"]);
		$ipaddr=$ligne["ipaddr"];
		$mac=$ligne["MAC"];
		$uid=$ligne["uid"];
		$familysite=$ligne["familysite"];
		$day=$ligne["day"];
		$size=$ligne["size"];
		$f[]="('$zmd5','$ipaddr','$mac','$uid','$familysite','$day','$size','$category')";
	}
	echo "$tablename_week - > $tablename_month ".count($f)." element(s)\n";
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
	}
	
	
	return true;
	
}


function db_dump($filepath){
	preg_match("#([0-9]+)_#", basename($filepath),$re);
	$time=WEEK_TIME_FROM_INT($re[1]);
	echo "$time ".date("Y-m-d",$time)." WEEK: ".date("YW",$time)." MONTH: ".date("Ym",$time)."\n";
	$class=new parse_berekley_dbs();
	$array=$class->SQUID_QUOTASIZES($filepath);
	echo count($array)." element(s)\n";
	
}