#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL)
;ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

xtstart();


function xtstart(){
$unix=new unix();
$sock=new sockets();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$weektime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".week.time";
// /etc/artica-postfix/pids/exec.squid.rotate.php.build.time

$sock=new sockets();
$unix=new unix();
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid)){echo "Already PID $pid is running\n";die();}
@file_put_contents($pidfile, getmypid());

$influx=new influx();
$UserAgentsStatistics=intval($sock->GET_INFO("UserAgentsStatistics"));
$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
$USERMM="MAC";
if($EnableKerbAuth==1){$USERMM="USERID";}
$time=strtotime('5 minutes ago');
$time_week=strtotime("-7 days");

if($UserAgentsStatistics==1){
	$sql="SELECT SUM(SIZE) as size,COUNT(RQS) as hits,USERAGENT,MAC,uid FROM useragents  WHERE time> {$time}s GROUP BY time(10m),USERAGENT,MAC,uid ORDER BY ASC";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	$main=$influx->QUERY_SQL($sql);
	foreach ($main as $row) {
		$USERAGENT=$row->USERAGENT;
		$MAC=$row->MAC;
		$uid=$row->uid;
		$size=intval($row->size);
		$hits=intval($row->hits);
		if($USERAGENT==null){continue;}
		if($size==null){continue;}
		if($size==0){continue;}
		$USERAGENT=mysql_escape_string2($USERAGENT);
		if($GLOBALS["VERBOSE"]){echo "MAIN_AGT: $USERAGENT -> $size\n";}
		$uid=mysql_escape_string2($uid);
		$f[]="('$size','$hits','$USERAGENT','$MAC','$uid')";
	}
		
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("current_useragnt10m")){
			$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS current_useragnt10m (
				`hits` BIGINT UNSIGNED,
				`size` BIGINT UNSIGNED,
				`USERAGENT` VARCHAR(256) NOT NULL,
				`MAC` VARCHAR(90) NOT NULL,
				`uid` VARCHAR(128) NOT NULL
					
				) ENGINE=MYISAM
				");
		}
		
		if(!$q->TABLE_EXISTS("week_useragnt")){
			$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS week_useragnt (
				`hits` BIGINT UNSIGNED,
				`size` BIGINT UNSIGNED,
				`USERAGENT` VARCHAR(256) NOT NULL,
				`MAC` VARCHAR(90) NOT NULL,
				`uid` VARCHAR(128) NOT NULL
			
				) ENGINE=MYISAM
				");
		}		
		
		if(count($f)>0){
			$q->QUERY_SQL("TRUNCATE TABLE current_useragnt10m");
			$q->QUERY_SQL("INSERT IGNORE INTO current_useragnt10m (hits,size,USERAGENT,MAC,uid) VALUES ".@implode(",", $f));
			$f=array();
		}
		
		$xtime=$unix->file_time_min($weektime);
		if($GLOBALS["VERBOSE"]){$xtime=10000000;}
		if($xtime>360){
			$sql="SELECT SUM(SIZE) as size,COUNT(RQS) as hits,USERAGENT,MAC,uid FROM useragents WHERE time> {$time_week}s GROUP BY time(7d),USERAGENT,MAC,uid ORDER BY ASC";
			if($GLOBALS["VERBOSE"]){echo "$sql\n";}
			$main=$influx->QUERY_SQL($sql);
			$f=array();
			foreach ($main as $row) {
				$USERAGENT=$row->USERAGENT;
				$MAC=$row->MAC;
				$uid=$row->uid;
				$size=intval($row->size);
				$hits=intval($row->hits);
				if($USERAGENT==null){continue;}
				if($size==null){continue;}
				if($size==0){continue;}
				$USERAGENT=mysql_escape_string2($USERAGENT);
				if($GLOBALS["VERBOSE"]){echo "MAIN_AGT: $USERAGENT -> $size\n";}
				$uid=mysql_escape_string2($uid);
				$f[]="('$size','$hits','$USERAGENT','$MAC','$uid')";
			}
			
			if(count($f)>0){
				$q->QUERY_SQL("TRUNCATE TABLE useragents");
				$q->QUERY_SQL("INSERT IGNORE INTO useragents (hits,size,USERAGENT,MAC,uid) VALUES ".@implode(",", $f));
				$f=array();
			}
			
			@unlink($weektime);
			@file_put_contents($weektime, time());
			
		}
		
		
	}
	
	




$sql="SELECT SIZE,RQS,$USERMM from access_log WHERE time> {$time}s";
if($GLOBALS["VERBOSE"]){echo "$sql\n";}

$MAIN=array();
$xdata=array();
$ydata=array();
$f=array();
$influx=new influx();
$main=$influx->QUERY_SQL($sql);
$MEMBERS_COUNT=0;


foreach ($main as $row) {
	$USER=$row->$USERMM;
	$size=intval($row->SIZE);
	$hits=intval($row->RQS);
	if($USER==null){continue;}
	if($size==0){continue;}
	if(!isset($XMAIN[$USER])){
		$XMAIN[$USER]["SIZE"]=$size;
		$XMAIN[$USER]["HITS"]=$hits;
	}else{
		$XMAIN[$USER]["SIZE"]=$XMAIN[$USER]["SIZE"]+$size;
		$XMAIN[$USER]["HITS"]=$XMAIN[$USER]["HITS"]+$hits;
	}
}

while (list ($USER,$ARRAY ) = each ($XMAIN) ){
	if($GLOBALS["VERBOSE"]){echo "$USER {$ARRAY["SIZE"]}/{$ARRAY["HITS"]}\n";}
	$f[]="('$USER','{$ARRAY["SIZE"]}','{$ARRAY["HITS"]}')";
	
}


if($GLOBALS["VERBOSE"]){echo "RESULTS=".count($f)." members...\n";}
if(count($f)==0){return;}

$q=new mysql_squid_builder();
if(!$q->TABLE_EXISTS("current_members10m")){
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS current_members10m (
				`hits` BIGINT UNSIGNED,
				`size` BIGINT UNSIGNED,
				`member` VARCHAR(128) NOT NULL PRIMARY KEY) ENGINE=MYISAM
				");
}
if(count($f)>0){
	$q->QUERY_SQL("TRUNCATE TABLE current_members10m");
	$q->QUERY_SQL("INSERT IGNORE INTO current_members10m (member,hits,size) VALUES ".@implode(",", $f));
	$MEMBERS_COUNT=$q->COUNT_ROWS("current_members10m");
	@file_put_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT10M", $MEMBERS_COUNT);
}
$array["tags"]["proxyname"]=$unix->hostname_g();
$array["fields"]["members"]=$MEMBERS_COUNT;
$influx->insert("members_count", $array);

$f=array();





}



