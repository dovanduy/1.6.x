<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.nginx.inc");



if($argv[1]=="--parse"){parse_logs();exit;}
if($argv[1]=="--hosts"){ParseHostnames();exit;}

parse_logs();

function parse_logs(){

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidTime)<45){return;}
	}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$sock=new sockets();
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	if($EnableNginxStats==0){return;}	
	if(system_is_overloaded(basename(__FILE__))){
		events("Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting",__FUNCTION__,__LINE__);
		return;
		
	}
	
	$nice=EXEC_NICE();
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	$NginxWorkLogsDir=$sock->GET_INFO("NginxWorkLogsDir");
	if($NginxWorkLogsDir==null){$NginxWorkLogsDir="/home/nginx/logsWork";}
	$sys=new mysql_storelogs();
	$files=$unix->DirFiles($NginxWorkLogsDir,"-([0-9\-]+)\.log");
	
	
	
	
	while (list ($filename, $line) = each ($files) ){
		if(!preg_match("#^(.+?)-[0-9]+-[0-9]+-[0-9]+-[0-9]+\.log$#", $filename,$re)){
			if($GLOBALS["VERBOSE"]){echo "$filename, skip\n";}
			continue;
		}
		
		$servername=$re[1];
		$fullpath="$NginxWorkLogsDir/$filename";
		ParseFile($servername,$fullpath);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php ".__FILE__." --hosts >/dev/null 2>&1 &");
	shell_exec("$nohup $php ".dirname(__FILE__)."/exec.nginx-stats-day.php >/dev/null 2>&1 &");
	
	
}

function events($text,$function,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo "[$function]::$line:: $text\n";}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/nginx.stats.log",false,$function,$line);
	
}

function ParseFile($servername,$fullpath){
	events("[$servername]: Parsing $fullpath",__FUNCTION__,__LINE__);
	$unix=new unix();
	$size=@filesize($fullpath);
	events("[$servername]: open $fullpath $size bytes",__FUNCTION__,__LINE__);
	
	$handle = @fopen($fullpath, "r");
	if (!$handle) {events("[$servername]: open $fullpath fatal, unable to open ",__FUNCTION__,__LINE__);return;}
	$c=0;
	$d=0;
	$t=time();
	$WORKARRAY=array();
	while (!feof($handle)){
		$d++;
		$line=trim(fgets($handle, 4096));
		if($line==null){continue;}
		if(!preg_match('#(.*?)\s+(.*?)\s+(.*?)\s+\[(.*?)\]\s+([A-Z]+)\s+(.*?)\s+HTTP.*?\/.*?"([0-9]+)"\s+([0-9]+)\s+"(.*?)"\s+"(.*?)"\s+"(.*?)"#',$line,$re)){
			events("[$servername]: {{$line}} unable to parse...",__FUNCTION__,__LINE__);
			continue;
		}
		
		while (list ($a, $b) = each ($re)){$re[$a]=mysql_escape_string2($b);}
		$c++;
		$md5=md5($re[0]);
		$ipaddr=$re[1];
		$time=strtotime($re[4]);
		$proto=$re[5];
		$uri=$re[6];
		$code=$re[7];
		$size=$re[8];
		$UserAgent=$re[10];
		$Country=mysql_escape_string2(GeoLoc($ipaddr));
		$currDate=date("Y-m-d H:i:s",$time);
		$linesql="('$md5','$currDate','$ipaddr','$proto','$uri','$code','$size','$UserAgent','$Country')";
		$table="hour_".date("YmdH",$time);
		$WORKARRAY[$table][]=$linesql;
		if($c>500){
			if(!ParseArray($servername,$WORKARRAY)){return;}
			$WORKARRAY=array();
			$c=0;
		}
		

	}
	
	if(count($WORKARRAY)>0){
		if(!ParseArray($servername,$WORKARRAY)){return;}
	}
	
	
	$timeTOScan=$unix->distanceOfTimeInWords($t,time(),true);
	events("[$servername]: $fullpath $timeTOScan $d lines",__FUNCTION__,__LINE__);
	if($d==0){@unlink($fullpath);}
	$sys=new mysql_storelogs();
	$filedate=date('Y-m-d H:i:s',filemtime($fullpath));
	$sys->ROTATE_TOMYSQL($fullpath,$filedate);
	
	
}

function ParseArray($servername,$WORKARRAY){
	$q=new nginx_stats($servername);
	while (list ($table, $rows) = each ($WORKARRAY)){
		if(!$q->hourtable($table)){return false;}
		$sql="INSERT IGNORE INTO `$table` (`zmd5`,`zDate`,`ipaddr`,`proto`,`uri`,`httpcode`,`size`,`useragent`,`country`) 
		VALUES ".@implode(",", $rows);
		$q->QUERY_SQL($sql);
		if(!$q->ok){return false;}
		
		
	}
	return true;
	
}


function ParseHostnames(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidTime)<15){return;}
	}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		return;
	}

	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$sql="SELECT servername  FROM reverse_www";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$SERVERS[$ligne["servername"]]=$ligne["servername"];
		
	}
	while (list ($servername, $rows) = each ($SERVERS)){
		ParseHostnames_hours($servername);
		
	}
}

function ParseHostnames_hours($servername){
	
	$q=new nginx_stats($servername);
	$hourstables=$q->LIST_TABLES_HOURS_TEMP();
	while (list ($tablename, $rows) = each ($hourstables)){
		$sql="SELECT ipaddr FROM `$tablename` WHERE LENGTH(hostname)=0 GROUP BY `ipaddr`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ipaddr=$ligne["ipaddr"];
			if(!isset($GLOBALS[$ipaddr])){$GLOBALS[$ipaddr]=gethostbyaddr($ipaddr);}
			$q->QUERY_SQL("UPDATE `$tablename` SET `hostname`='{$GLOBALS[$ipaddr]}' WHERE `ipaddr`='$ipaddr'");
			
		}
		
	}
	
	
	
	
	
}



function GeoLoc($ipaddr){
	
	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		UpdateGeoip();
		if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){return array();}
	}
	
	if(isset($GLOBALS["GEO"][$ipaddr])){$country=$GLOBALS["GEO"][$ipaddr];}
	if(!function_exists("geoip_record_by_name")){return;}
	$record = geoip_record_by_name($ipaddr);
	if ($record) {
		$country=$record["country_name"];
		$GLOBALS["GEO"][$ipaddr]=$country;
	}
	return $country;
	
}
function UpdateGeoip(){
	if(isset($GLOBALS["UpdateGeoip_executed"])){return;}
	$GLOBALS["UpdateGeoip_executed"]=true;
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$database="/usr/share/GeoIP/GeoIP.dat";
	if(!is_file($database)){installgeoip();return null;}
	if(!is_file("/usr/local/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/local/share/GeoIP/GeoLiteCity.dat")){
			shell_exec("$ln -s /usr/local/share/GeoIP/GeoLiteCity.dat /usr/local/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}


	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/share/GeoIP/GeoLiteCity.dat")){
			system("$ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}

	if(!function_exists("geoip_record_by_name")){installgeoip();return null;}

}