<?php
die();
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["FORCE_SCAN"]=false;
$GLOBALS["BYOVERLOAD"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--forcescan#",implode(" ",$argv))){$GLOBALS["FORCE_SCAN"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--byoverload=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["BYOVERLOAD"]=$re[1];}
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
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
if($argv[1]=="--build"){build_last_hour();die();}
if($argv[1]=="--sites"){build_last_access_sites();die();}


if($argv[1]=="dump"){
	$DATA=unserialize(@file_get_contents("/usr/share/squid3/CurrentSizesUsers.db"));
	if($argv[2]<>null){
		$DATA=$DATA[$argv[2]];
	}
	
	if($argv[3]<>null){
		$DATA=$DATA[$argv[2]][$argv[3]];
	}
	print_r($DATA);
	die();
}

if($argv[1]=="--schedules"){set_schedules();exit;}
if($argv[1]=="--speed"){speed();exit;}


mQuotaWebFilter();


function set_schedules(){
	$sock=new sockets();
	$cronfile="/etc/cron.d/artica-quota-speed";
	$EnableQuotasStatistics=intval($sock->GET_INFO("EnableQuotasStatistics"));
	$QuotasStatisticsInterval=intval($sock->GET_INFO("QuotasStatisticsInterval"));
	
	
	if($EnableQuotasStatistics==0){
		if(is_file($cronfile)){@unlink($cronfile);system("/etc/init.d/cron reload");}
		return;
	}
	if($QuotasStatisticsInterval==0){$QuotasStatisticsInterval=15;}
	
	$QuotasStatisticsIntervalA[5]="0,5,10,15,20,25,30,35,40,45,50,55";
	$QuotasStatisticsIntervalA[10]="0,10,20,30,40,50";
	$QuotasStatisticsIntervalA[15]="0,15,30,45";
	$QuotasStatisticsIntervalA[30]="0,30";
	
	
	$unix=new unix();
	$nice=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$CRON[]="MAILTO=\"\"";
	$CRON[]="{$QuotasStatisticsInterval[$QuotasStatisticsInterval]} * * * *  root $nice $php ".__FILE__." --speed >/dev/null 2>&1";
	$CRON[]="";
	file_put_contents($cronfile,@implode("\n", $CRON));
	$CRON=array();
	chmod($cronfile,0640);
	chown($cronfile,"root");
	system("/etc/init.d/cron reload");
	
	
	
}

function speed(){
	$LastHour=InfluxQueryFromUTC(strtotime("-1 hour"));
	$influx=new influx();
	$sql="SELECT SUM(SIZE) as size,USERID,IPADDR,MAC,CATEGORY FROM  access_log GROUP BY time(1h) ,USERID,IPADDR,MAC,CATEGORY WHERE time > {$LastHour}s";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$main=$influx->QUERY_SQL($sql);
	$ipClass=new IP();
	
	foreach ($main as $row) {
		$CATEGORY=$row->CATEGORY;
		$USERID=strtolower($row->USERID);
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$size=intval($row->size);
		if($size==0){continue;}
	
		if($CATEGORY<>null){
			if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$USERID])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]=$size;
			}else{
				$size_old=intval($ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]);
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]=$size_old;
			}
				
			if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR];
				$size_old=$size_old+$size;
	
				if($size_old==0){
					echo "Warning $CATEGORY/$IPADDR {$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]} + $size = 0\n";
				}
	
				$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]=$size_old;
				}
					
				if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$MAC])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC]=$size;
				}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC]=$size_old;
				}
					
					
			}
	
			if(!isset($ARRAY["UID"][$USERID]["HOUR"])){
			$ARRAY["UID"][$USERID]["HOUR"]=$size;
		}else{
			$size_old=$ARRAY["UID"][$USERID]["HOUR"];
			$size_old=$size_old+$size;
			$ARRAY["UID"][$USERID]["HOUR"]=$size_old;
		}
	
		if(!isset($ARRAY["IPADDR"][$IPADDR]["HOUR"])){
			$ARRAY["IPADDR"][$IPADDR]["HOUR"]=$size;
		}else{
			$size_old=$ARRAY["IPADDR"][$IPADDR]["HOUR"];
			$size_old=$size_old+$size;
			$ARRAY["IPADDR"][$IPADDR]["HOUR"]=$size_old;
			}
	
	
		if($ipClass->IsvalidMAC($MAC)){
			if(!isset($ARRAY["MAC"][$MAC]["HOUR"])){
				$ARRAY["MAC"][$MAC]["HOUR"]=$size;
			}else{
				$size_old=$ARRAY["MAC"][$MAC]["HOUR"];
				$size_old=$size_old+$size;
				$ARRAY["MAC"][$MAC]["HOUR"]=$size_old;
			}
		}
	
	}

	@unlink("/usr/share/squid3/SpeedSizesUsers.db");
	@file_put_contents("/usr/share/squid3/SpeedSizesUsers.db", serialize($ARRAY));
	@chmod("/usr/share/squid3/SpeedSizesUsers.db",0755);	
	
	
}

function build_last_access_sites(){
	$influx=new influx();
	$MAIN_ARRAY=array();
	if($GLOBALS["VERBOSE"]){echo "*******************************************\n";}
	if($GLOBALS["VERBOSE"]){echo "\n";}
	if($GLOBALS["VERBOSE"]){echo "\n";}
	if($GLOBALS["VERBOSE"]){echo "\n";}
	$data=$influx->QUERY_SQL("SELECT MAX(ZDATE) as MAX from access_sites");
	$date_end=$data[0]->MAX;
	$WHERE_TIME=null;
	
	if($GLOBALS["VERBOSE"]){echo "*******************************************\n";}
	echo "Date Start: $date_end ". date("Y-m-d H:i:s",$date_end)."\n";
	if($GLOBALS["VERBOSE"]){echo "*******************************************\n";}
	$LastDay=InfluxQueryFromUTC(strtotime("-1 day"));
	$thisDay=InfluxQueryFromUTC(strtotime(date("Y-m-d 00:00:00",$LastDay)));
	$WHERE_TIME="WHERE ZDATE < $LastDay";
	if($GLOBALS["VERBOSE"]){echo "Minimal Time: $LastDay = ".date("Y-m-d H:i:s",$LastDay)."\n";}
	
	if($date_end>0){
		$WHERE_TIME="WHERE ZDATE > $date_end AND ZDATE < $thisDay";
		$seconds=time()-strtotime(date("Y-m-d H:00:00",$date_end));
		$diff = (abs($thisDay - $date_end)/60);
		if($GLOBALS["VERBOSE"]){echo "{$diff}mn..\n";}
		if($diff<1440){
		if($GLOBALS["VERBOSE"]){echo "{$diff}mn < 1440 aborting...\n";}return;}
	}
	
	$sql="SELECT SIZE,RQS,FAMILYSITE,ZDATE FROM access_hour $WHERE_TIME";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	$main=$influx->QUERY_SQL($sql);
	$sq=new influx();
	$catz=new mysql_catz();
	
	
	foreach ($main as $row) {
		$time=date("Y-m-d H:00:00",$row->ZDATE);
		$xtime=$time;
		$FAMILYSITE=$row->FAMILYSITE;
		$RQS=intval($row->RQS);
		$SIZE=intval($row->SIZE);
		if($SIZE==0){continue;}
		
		if(!isset($MAIN_ARRAY[$xtime][$FAMILYSITE])){
			$MAIN_ARRAY[$time][$FAMILYSITE]["SIZE"]=$SIZE;
			$MAIN_ARRAY[$time][$FAMILYSITE]["RQS"]=$RQS;
			$MAIN_ARRAY[$time][$FAMILYSITE]["ZDATE"]=$row->ZDATE;
		
		}else{
			$MAIN_ARRAY[$time][$FAMILYSITE]["SIZE"]=$MAIN_ARRAY[$time][$FAMILYSITE]["SIZE"]+$SIZE;
			$MAIN_ARRAY[$time][$FAMILYSITE]["RQS"]=$MAIN_ARRAY[$time][$FAMILYSITE]["RQS"]+$RQS;
			if($row->ZDATE>$MAIN_ARRAY[$time][$FAMILYSITE]["ZDATE"]){
				$MAIN_ARRAY[$time][$FAMILYSITE]["ZDATE"]=$row->ZDATE;
			}
		}
	}
	
	if(count($MAIN_ARRAY)==0){return;}
	while (list ($ztime, $array) = each ($MAIN_ARRAY) ){
		while (list ($FAMILYSITE, $Tarray) = each ($array) ){
			$zArray=array();
			$sdate=date("Y-m-d",$ztime);
			$SIZE=$Tarray["SIZE"];
			$RQS=$Tarray["RQS"];
			$ZDATE=$Tarray["ZDATE"];
			$zArray["fields"]["time"]=$ztime;
			$zArray["fields"]["SIZE"]=intval($SIZE);
			$zArray["tags"]["FAMILYSITE"]=$FAMILYSITE;
			$zArray["fields"]["ZDATE"]=$ZDATE;
			$zArray["fields"]["RQS"]=intval($RQS);
			if($GLOBALS["VERBOSE"]){echo "access_sites: $sdate [$FAMILYSITE] $SIZE/$RQS\n";}
			$sq->insert("access_sites", $zArray);
		}
	
	}
	
}


function build_last_hour(){

	$sock=new sockets();
	$influx=new influx();
	$data=$influx->QUERY_SQL("SELECT MAX(ZDATE) as MAX from access_hour");
	$date_end=InfluxQueryFromUTC($data[0]->MAX);
	echo "Date Start: $date_end\n";
	
	$LastHour=InfluxQueryFromUTC(strtotime("-1 hour"));
	$ThisHour=strtotime(date("Y-m-d H:00:00",$LastHour));
	
	if($date_end>0){
		$WHERE_TIME="WHERE time > '".date("Y-m-d H:i:s",$date_end)."' AND time < '".date("Y-m-d H:i:s",$ThisHour)."'";
		$seconds=InfluxQueryFromUTC(time())-strtotime(date("Y-m-d H:00:00",$date_end));
		$diff = (abs($ThisHour - $date_end)/60);
		if($GLOBALS["VERBOSE"]){echo "Last date saved is ".date("Y-m-d H:i:s",$date_end)."($date_end) To ".date("Y-m-d H:i:s",$ThisHour)." ($ThisHour) {$diff}mn..\n";}
		if($GLOBALS["VERBOSE"]){echo "$WHERE_TIME\n";}
		
		if($diff<60){
			if($GLOBALS["VERBOSE"]){echo "{$diff}mn < 60 aborting...\n";}
			return;}
	}else{
		$WHERE_TIME="WHERE time < '".date("Y-m-d H:i:s",$ThisHour)."'";
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "Query From ".date("Y-m-d H:i:s",$date_end)." to ".date("Y-m-d H:i:s",$ThisHour)."\n";}
	
	$sql="SELECT SIZE,RQS,FAMILYSITE,USERID,IPADDR,MAC,CATEGORY FROM access_log $WHERE_TIME";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	if($GLOBALS["VERBOSE"]){echo "*******************************************\n";}
	if($GLOBALS["VERBOSE"]){echo "\n";}
	$main=$influx->QUERY_SQL($sql);

	
	$MAIN_ARRAY=array();
	$catz=new mysql_catz();
	if($GLOBALS["VERBOSE"]){echo count($main)." elements";}
	
	
	
	$xtime=0;
	foreach ($main as $row) {
		
		$time=InfluxToTime($row->time);
		$Time_hour=date("Y-m-d H:00:00",$time);
		$size=intval($row->SIZE);
		$RQS=intval($row->RQS);

		$FAMILYSITE=$row->FAMILYSITE;
		$CATEGORY=$row->CATEGORY;
		if($CATEGORY==null){$CATEGORY=$catz->GET_CATEGORIES($FAMILYSITE);}
		$USERID=$row->USERID;
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		//if($GLOBALS["VERBOSE"]){echo "$row->time] [$Time_hour] $FAMILYSITE {$size}Bytes, $CATEGORY $USERID/$IPADDR/$MAC\n";}
		if($size==0){continue;}
		if($RQS==0){continue;}
		if($time>$xtime){$xtime=$time;}
		
		
		$MD5KEY=md5("$CATEGORY$USERID$FAMILYSITE$IPADDR$MAC");
		if(!isset($MAIN_ARRAY[$Time_hour][$MD5KEY])){
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["FAMILYSITE"]=$FAMILYSITE;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["CATEGORY"]=$CATEGORY;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["USERID"]=$USERID;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["IPADDR"]=$IPADDR;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["MAC"]=$MAC;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["size"]=$size;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["hits"]=$RQS;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["ZDATE"]=$time;
			
		}else{
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["size"]=$MAIN_ARRAY[$Time_hour][$MD5KEY]["size"]+$size;
			$MAIN_ARRAY[$Time_hour][$MD5KEY]["hits"]=$MAIN_ARRAY[$Time_hour][$MD5KEY]["hits"]+$RQS;
			if($time>$MAIN_ARRAY[$Time_hour][$MD5KEY]["ZDATE"]){$MAIN_ARRAY[$Time_hour][$MD5KEY]["ZDATE"]=$time;}
		}
	}
	
	$sq=new influx();
	if(count($MAIN_ARRAY)==0){
		if($GLOBALS["VERBOSE"]){echo "No array....\n";}
		
	}
	
	
	while (list ($ztime, $array) = each ($MAIN_ARRAY) ){
		while (list ($md5, $Tarray) = each ($array) ){
			$sdate=$ztime;
			$USERID=$Tarray["USERID"];
			$IPADDR=$Tarray["IPADDR"];
			$MAC=$Tarray["MAC"];
			$FAMILYSITE=$Tarray["FAMILYSITE"];
			$CATEGORY=$Tarray["CATEGORY"];
			$size=$Tarray["size"];
			$RQS=$Tarray["hits"];
			$ZDATE=$Tarray["ZDATE"];
			if($GLOBALS["VERBOSE"]){echo date("Y-m-d H:i:s",$ZDATE)." $USERID/$IPADDR/$MAC -> [$FAMILYSITE/$CATEGORY] $size/$RQS\n";}
			
			$zArray=array();
			$zArray["fields"]["time"]=$sdate;
			$zArray["tags"]["CATEGORY"]=$CATEGORY;
			$zArray["tags"]["USERID"]=$USERID;
			$zArray["tags"]["IPADDR"]=$IPADDR;
			$zArray["tags"]["MAC"]=$MAC;
			$zArray["fields"]["SIZE"]=intval($size);
			$zArray["tags"]["FAMILYSITE"]=$FAMILYSITE;
			$zArray["fields"]["RQS"]=intval($RQS);
			$zArray["fields"]["ZDATE"]=$ZDATE;
			$sq->insert("access_hour", $zArray);
		}
		
		
	}
	
	
	$data=$influx->QUERY_SQL("SELECT MAX(ZDATE) as MAX from access_hour");
	$date_end=$data[0]->MAX;
	if($GLOBALS["VERBOSE"]){echo "Query was $WHERE_TIME\n";}
	if($GLOBALS["VERBOSE"]){echo "Last time : $xtime ".date("Y-m-d H:i:s",$xtime)."\n";}
	if($GLOBALS["VERBOSE"]){echo "Last date saved is ".date("Y-m-d H:i:s",$date_end)."($date_end) To ".date("Y-m-d H:i:s",$ThisHour)." ($ThisHour) {$diff}mn..\n";}
	if($GLOBALS["VERBOSE"]){echo "Last final access_hour saved date $date_end - ".date("Y-m-d H:i:s",$date_end)."\n";}
	
}


function mQuotaWebFilter(){

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.hours.php.mQuotaWebFilter.pid";
	$timefile="/etc/artica-postfix/pids/exec.squid.stats.hours.php.mQuotaWebFilter.time";
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){die();}
	
	$pid=$unix->get_pid_from_file($pidfile);
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			$rpcessTime=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$rpcessTime}Mn\n";}
			if($rpcessTime<10){return;}
			$unix->KILL_PROCESS($pid,9);
		}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<30){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}

	build_last_hour();
	$ipClass=new IP();
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	$ARRAY["TIME_BUILD"]=time();
	$q=new mysql_squid_builder();
	$influx=new influx();
	$date_from=InfluxQueryFromUTC(strtotime("-1 hour"));
	
	$sql="SELECT SUM(SIZE) as size,USERID,IPADDR,MAC,CATEGORY FROM  access_log GROUP BY time(1h) ,USERID,IPADDR,MAC,CATEGORY WHERE time > {$date_from}s";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	$main=$influx->QUERY_SQL($sql);
	
	
	foreach ($main as $row) {
		$CATEGORY=$row->CATEGORY;
		$USERID=strtolower($row->USERID);
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$size=intval($row->size);
		if($size==0){continue;}
		
		if($CATEGORY<>null){
			if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$USERID])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]=$size;
			}else{
				$size_old=intval($ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]);
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["HOUR"][$USERID]=$size_old;
			}
			
			if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR];
				$size_old=$size_old+$size;
				
				if($size_old==0){
					echo "Warning $CATEGORY/$IPADDR {$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]} + $size = 0\n";
				}
				
				$ARRAY["categories"][$CATEGORY]["HOUR"][$IPADDR]=$size_old;
			}
			
			if(!isset($ARRAY["categories"][$CATEGORY]["HOUR"][$MAC])){
				$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["HOUR"][$MAC]=$size_old;
			}			
			
			
		}
		
		if(!isset($ARRAY["UID"][$USERID]["HOUR"])){
			$ARRAY["UID"][$USERID]["HOUR"]=$size;
		}else{
			$size_old=$ARRAY["UID"][$USERID]["HOUR"];
			$size_old=$size_old+$size;
			$ARRAY["UID"][$USERID]["HOUR"]=$size_old;
		}
		
		if(!isset($ARRAY["IPADDR"][$IPADDR]["HOUR"])){
			$ARRAY["IPADDR"][$IPADDR]["HOUR"]=$size;
		}else{
			$size_old=$ARRAY["IPADDR"][$IPADDR]["HOUR"];
			$size_old=$size_old+$size;
			$ARRAY["IPADDR"][$IPADDR]["HOUR"]=$size_old;
		}	

		
		if($ipClass->IsvalidMAC($MAC)){
			if(!isset($ARRAY["MAC"][$MAC]["HOUR"])){
				$ARRAY["MAC"][$MAC]["HOUR"]=$size;
			}else{
				$size_old=$ARRAY["MAC"][$MAC]["HOUR"];
				$size_old=$size_old+$size;
				$ARRAY["MAC"][$MAC]["HOUR"]=$size_old;
			}	
		}	
		
	}

	
//-----------------------------------------------------------------------------------------------
	$date_from=InfluxQueryFromUTC(strtotime("-1 day"));
	
	$sql="SELECT SUM(SIZE) as size,USERID,IPADDR,MAC,CATEGORY FROM  access_hour GROUP BY time(1d) ,USERID,IPADDR,MAC,CATEGORY WHERE time > {$date_from}s";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	$main=$influx->QUERY_SQL($sql);
	
	
	foreach ($main as $row) {
		$CATEGORY=$row->CATEGORY;
		$USERID=strtolower($row->USERID);
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$size=intval($row->size);
		if($size==0){continue;}
	
		if($CATEGORY<>null){
			if(!isset($ARRAY["categories"][$CATEGORY]["DAY"][$USERID])){
				$ARRAY["categories"][$CATEGORY]["DAY"][$USERID]=$size;
			}else{
				$size_old=intval($ARRAY["categories"][$CATEGORY]["DAY"][$USERID]);
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["DAY"][$USERID]=$size_old;
			}
				
			if(!isset($ARRAY["categories"][$CATEGORY]["DAY"][$IPADDR])){
				$ARRAY["categories"][$CATEGORY]["DAY"][$IPADDR]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["DAY"][$IPADDR];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["DAY"][$IPADDR]=$size_old;
			}
					
			if(!isset($ARRAY["categories"][$CATEGORY]["DAY"][$MAC])){
				$ARRAY["categories"][$CATEGORY]["DAY"][$MAC]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["DAY"][$MAC];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["DAY"][$MAC]=$size_old;
			}
					
					
		}
	
		if(!isset($ARRAY["UID"][$USERID]["DAY"])){
			$ARRAY["UID"][$USERID]["DAY"]=$size;
		}else{
			$size_old=$ARRAY["UID"][$USERID]["DAY"];
			$size_old=$size_old+$size;
			$ARRAY["UID"][$USERID]["DAY"]=$size_old;
		}
	
		if(!isset($ARRAY["IPADDR"][$IPADDR]["DAY"])){
			$ARRAY["IPADDR"][$IPADDR]["DAY"]=$size;
		}else{
			$size_old=$ARRAY["IPADDR"][$IPADDR]["DAY"];
			$size_old=$size_old+$size;
			$ARRAY["IPADDR"][$IPADDR]["DAY"]=$size_old;
		}
	
	
		if($ipClass->IsvalidMAC($MAC)){
			if(!isset($ARRAY["MAC"][$MAC]["DAY"])){
				$ARRAY["MAC"][$MAC]["DAY"]=$size;
			}else{
				$size_old=$ARRAY["MAC"][$MAC]["DAY"];
				$size_old=$size_old+$size;
				$ARRAY["MAC"][$MAC]["DAY"]=$size_old;
			}
		}
	
		}
	
	
		//-----------------------------------------------------------------------------------------------
	
	$influx=new influx();
	
	$date_from=strtotime("-1 week");
	$sql="SELECT SUM(SIZE) as size,USERID,IPADDR,MAC,CATEGORY FROM  access_hour GROUP BY time(1w) ,USERID,IPADDR,MAC,CATEGORY WHERE time > {$date_from}s";
	$main=$influx->QUERY_SQL($sql);	
	
	foreach ($main as $row) {
		$CATEGORY=$row->CATEGORY;
		$USERID=strtolower($row->USERID);
		$IPADDR=$row->IPADDR;
		$MAC=$row->MAC;
		$size=intval($row->size);
		if($size==0){continue;}
		if($CATEGORY<>null){
			if(!isset($ARRAY["categories"][$CATEGORY]["WEEK"][$USERID])){
				$ARRAY["categories"][$CATEGORY]["WEEK"][$USERID]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["WEEK"][$USERID];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["WEEK"][$USERID]=$size_old;
			}
			
			if(!isset($ARRAY["categories"][$CATEGORY]["WEEK"][$IPADDR])){
				$ARRAY["categories"][$CATEGORY]["WEEK"][$IPADDR]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["WEEK"][$IPADDR];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["WEEK"][$IPADDR]=$size_old;
			}		
				
			if(!isset($ARRAY["categories"][$CATEGORY]["WEEK"][$MAC])){
				$ARRAY["categories"][$CATEGORY]["WEEK"][$MAC]=$size;
			}else{
				$size_old=$ARRAY["categories"][$CATEGORY]["WEEK"][$MAC];
				$size_old=$size_old+$size;
				$ARRAY["categories"][$CATEGORY]["WEEK"][$MAC]=$size_old;
			}			
			
			
			
		}
		
		
	
		if($USERID<>null){
			if(!isset($ARRAY["UID"][$USERID]["WEEK"])){
				$ARRAY["UID"][$USERID]["WEEK"]=$size;
			}else{
				$size_old=$ARRAY["UID"][$USERID]["WEEK"];
				$size_old=$size_old+$size;
				$ARRAY["UID"][$USERID]["WEEK"]=$size_old;
			}
		}
	
		
		if($ipClass->isValid($IPADDR)){
			if(!isset($ARRAY["IPADDR"][$IPADDR]["WEEK"])){
				$ARRAY["IPADDR"][$IPADDR]["IPADDR"]=$size;
			}else{
				$size_old=$ARRAY["IPADDR"][$IPADDR]["WEEK"];
				$size_old=$size_old+$size;
				$ARRAY["IPADDR"][$IPADDR]["WEEK"]=$size_old;
			}
		}
		if($ipClass->IsvalidMAC($MAC)){
			if(!isset($ARRAY["MAC"][$MAC]["WEEK"])){
				$ARRAY["MAC"][$MAC]["WEEK"]=$size;
			}else{
				$size_old=$ARRAY["MAC"][$MAC]["WEEK"];
				$size_old=$size_old+$size;
				$ARRAY["MAC"][$MAC]["WEEK"]=$size_old;
			}
		}
	
	}	

	
//-----------------------------------------------------------------------------------------------

	
	
	

	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	@unlink("/usr/share/squid3/CurrentSizesUsers.db");
	@file_put_contents("/usr/share/squid3/CurrentSizesUsers.db", serialize($ARRAY));
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/CurrentSizesUsers.db", serialize($ARRAY));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/CurrentSizesUsers.db",0755);
}
