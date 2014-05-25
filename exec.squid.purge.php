<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');


if($argv[1]=="--scan"){scan_stored_items();die();}
if($argv[1]=="--inject"){inject_stored_items();die();}	
	
function scan_stored_items($nopid=true){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){
		$php=$unix->LOCATE_PHP5_BIN();
		ufdbguard_admin_events("Overloaded system... ask to run this task later...",__FUNCTION__,__FILE__,__LINE__,"proxy");
		$unix->THREAD_COMMAND_SET("$php ".__FILE__." --scan");
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"proxy");
			return;
		}
	}
	
	$TimePid=$unix->file_time_min($pidTime);
	if($TimePid<1440){
		ufdbguard_admin_events("Task cannot be used less than 14h currently ({$TimePid}Mn)",__FUNCTION__,__FILE__,__LINE__,"proxy");
		return;
	}
	
	if(ScanPurgeexc()){
		ufdbguard_admin_events("Already Executed...",__FUNCTION__,__FILE__,__LINE__,"proxy");
		return;
	}	
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);

	$purge=$unix->find_program("purge");
	if(strlen($purge)<5){
		ufdbguard_admin_events("purge no such file, aborting task",__FUNCTION__,__FILE__,__LINE__,"proxy");
		return;
	}
	$nice=EXEC_NICE();
	$cmd="$nice$purge -c /etc/squid3/squid.conf -e \".\" -P 0 -n >/var/cache/purge.calculated.db 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$t1=time();
	system(trim($cmd));
	$took =$unix->distanceOfTimeInWords($t1,time());
	if($GLOBALS["VERBOSE"]){echo "done $took\n";}
	squid_admin_mysql(2,"Stored items: Extracting items information from cache done took:$took",null,__FILE__,__LINE__,"proxy");
	inject_stored_items(true);
	
}	

function ScanPurgeexc(){
	$unix=new unix();
	$purge=$unix->find_program("purge");
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");
	exec("$pidof $purge 2>&1",$results);
	$pp=array();
	$pids=explode(" ",@implode("", $results));
	while (list ($index, $pid) = each ($pids)){
		if(!is_numeric(trim($pid))){continue;}
		$pp[]=$pid;
	}
	
	$count=count($pp);
	if($count>1){
		unset($pp[0]);
		while (list ($index, $pid) = each ($pp)){
			shell_exec("$kill -9 $pid >/dev/null");
		}
	}
	
	$count=count($pp);
	return $count;
	
}

function inject_stored_array(){
$file="/var/cache/purge.calculated.db";
	$handle = @fopen($file, "r"); 
	if (!$handle) {echo "Failed to open file\n";return;}
	$q=new mysql_squid_builder();
	$c=0;
	while (!feof($handle)){
		$c++;
		$line =trim(fgets($handle, 4096));	
		if(!preg_match("#^.+?\s+[0-9]+\s+([0-9]+)\s+(.+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$line no match\n";}
			continue;
		}
		$size=intval($re[1]);
		$uri=$re[2];
		$p=parse_url($uri);
		
		$sitename=$p["host"];
		if(preg_match("#^www\.(.+)#", $sitename,$rz)){$sitename=$rz[1];}
		if($GLOBALS["VERBOSE"]){echo "Found: $sitename $size {$BIGARRAY[$sitename]["ITEMS"]}+1\n";}
		if(!isset($BIGARRAY[$sitename])){
			$BIGARRAY[$sitename]["SIZE"]=$size;
			$BIGARRAY[$sitename]["ITEMS"]=1;
			$BIGARRAY[$sitename]["FAMILY"]=$q->GetFamilySites($sitename);
			continue;
		}
		$BIGARRAY[$sitename]["SIZE"]=$BIGARRAY[$sitename]["SIZE"]+$size;
		$BIGARRAY[$sitename]["ITEMS"]++;
		$BIGARRAY[$sitename]["FAMILY"]=$q->GetFamilySites($sitename);
	}

	return $BIGARRAY;
	
}

function inject_stored_items($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(system_is_overloaded(basename(__FILE__))){
		$php=$unix->LOCATE_PHP5_BIN();
		ufdbguard_admin_events("Overloaded system... ask to run this task later...",__FUNCTION__,__FILE__,__LINE__,"proxy");
		$unix->THREAD_COMMAND_SET("$php ".__FILE__." --inject");
	}
	
	
	
	if(!$nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"proxy");
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	$t1=time();
	
	
	$file="/var/cache/purge.calculated.db";
	if(!is_file($file)){echo "$file no such file\n";return;}
	$q=new mysql_blackbox();
	if(!$q->TABLE_EXISTS("cacheitems_localhost")){$q->build_cached_items_table("localhost");}
	$BIGARRAY=inject_stored_array();
	$prefix="INSERT IGNORE INTO cacheitems_localhost(sitename,familysite,size,items) VALUES ";
	$q->QUERY_SQL("TRUNCATE TABLE cacheitems_localhost");
	$f=array();
	$c=0;
	while (list ($sitename, $array) = each ($BIGARRAY)){
		$c++;
		$f[]="('$sitename','{$array["FAMILY"]}','{$array["SIZE"]}','{$array["ITEMS"]}')";
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){squid_admin_mysql(0,"MySQL error!",$q->mysql_error,__FILE__,__LINE__);return;}
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){squid_admin_mysql(0,"MySQL error!",$q->mysql_error,__FILE__,__LINE__);return;}
	}	
	$took =$unix->distanceOfTimeInWords($t1,time());
	squid_admin_mysql(2,"Sucess adding $c cached websites took:$took",null,__FILE__,__LINE__);
	@unlink($file);
}
