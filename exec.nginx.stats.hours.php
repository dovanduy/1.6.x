<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(system_is_overloaded()){die();}

tables_hours();

function ToSyslog($text){
	if(!function_exists("syslog")){return;}
	$file=basename($file);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}



function tables_hours(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<60){
			if($GLOBALS["VERBOSE"]){echo "Only each 60mn - current {$timeexec}mn, use --force to bypass\n";}
			
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$sock=new sockets();
	
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	$prefix=date("YmdH");
	$currenttable="ngixattck_$prefix";
	
	if($GLOBALS["VERBOSE"]){echo "Current Table: $currenttable\n";}
	
	$tablesBrutes=$GLOBALS["Q"]->LIST_TABLES_NGINX_BLOCKED_RT();
	
	
	while (list ($tablename, $none) = each ($tablesBrutes) ){
		if($tablename==$currenttable){
			if($GLOBALS["VERBOSE"]){echo "Skip table: $tablename\n";}
			continue;
		}
		$t=time();
		
		if($GLOBALS["VERBOSE"]){echo "_table_hours_perform($tablename)\n";}
		if(_table_hours_perform($tablename)){
			$took=$unix->distanceOfTimeInWords($t,time());
			if($GLOBALS["VERBOSE"]){echo "Remove table: $tablename\n";}
			$GLOBALS["Q"]->QUERY_SQL("DROP TABLE `$tablename`");
			
			if(system_is_overloaded()){
				ufdbguard_admin_events("Fatal: Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} sleeping stopping function",__FUNCTION__,__FILE__,__LINE__,"stats");
				return true;
			}
		}
	}
	

}
function _table_hours_perform($tablename){
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	if(!preg_match("#ngixattck_([0-9]+)#",$tablename,$re)){
		writelogs_squid("NOT AN HOUR TABLE `$tablename`",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;}
	
	$q=new mysql_squid_builder();
	$time=$q->TIME_FROM_HOUR_TEMP_TABLE($tablename);
		
	if($GLOBALS["VERBOSE"]){echo "$tablename - $time - ".date("Y-m-d",$time)."\n";}
	
	
	$sql="SELECT HOUR(zDate) as `hour`,COUNT(keyr) as hits,`ipaddr`,`familysite`,`hostname`,`country`,`servername`
	FROM `$tablename` GROUP BY `hour`,`ipaddr`,`familysite`,`hostname`,`country`,`servername`";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);

	
	if(!$GLOBALS["Q"]->ok){
		writelogs_squid("Fatal: {$GLOBALS["Q"]->mysql_error} on `$tablename`\n".@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		if(strpos(" {$GLOBALS["Q"]->mysql_error}", "is marked as crashed and should be repaired")>0){
			$q1=new mysql();
			writelogs_squid("try to repair table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"stats");
			$q1->REPAIR_TABLE("squidlogs",$tablename);
			writelogs_squid(@implode("\n",$GLOBALS["REPAIR_MYSQL_TABLE"]),__FUNCTION__,__FILE__,__LINE__,"stats");
		}

		return false;
	}


	if(mysql_num_rows($results)==0){return true;}
	$timekey=date('Ymd',$time);
	
	$tabledest="ngixattckd_$timekey";
	if(!$q->check_nginx_attacks_DAY($timekey)){return false;}

	$prefix="INSERT IGNORE INTO $tabledest (zmd5,`hour`,`ipaddr`,`familysite`,`hostname`,`country`,`servername`,`hits`) VALUES ";


	$d=0;

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd=array();
		while (list ($key, $value) = each ($ligne) ){$ligne[$key]=mysql_escape_string2($value);$zmd[]=$value;}
		$zMD5=md5(@implode("",$zmd));
		
		
		$f[]="('$zMD5','{$ligne["hour"]}','{$ligne["ipaddr"]}','{$ligne["familysite"]}','{$ligne["hostname"]}','{$ligne["country"]}','{$ligne["servername"]}','{$ligne["hits"]}')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$q->ok){writelogs_squid("Fatal: {$q->mysql_error} on `$tabledest`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}

	}

	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		$f=array();
		if(!$q->ok){writelogs_squid("Fatal: {$q->mysql_error} on `$tabledest`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		
	}
	return true;
}


function events_repair($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/squid.stats.repair.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] $text\n";
	@fwrite($h,$line);
	@fclose($h);
}


?>