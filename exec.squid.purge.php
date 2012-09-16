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
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($nopid){
		$oldpid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($oldpid,$myfile)){
			ufdbguard_admin_events("Task already running PID: $oldpid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);

	$purge=$unix->find_program("purge");
	if(strlen($purge)<5){
		ufdbguard_admin_events("purge no such file, aborting task",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$nice=EXEC_NICE();
	$cmd="$nice$purge -c /etc/squid3/squid.conf -e \".\" -P 0 >/var/cache/purge.calculated.db 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$t1=time();
	shell_exec(trim($cmd));
	$took =$unix->distanceOfTimeInWords($t1,time());
	if($GLOBALS["VERBOSE"]){echo "done $took\n";}
	ufdbguard_admin_events("Extracting items information from cache done took:$took",__FUNCTION__,__FILE__,__LINE__,"stats");
	inject_stored_items();
	
}	

function inject_stored_array(){
$file="/var/cache/purge.calculated.db";
	$handle = @fopen($file, "r"); 
	if (!$handle) {echo "Failed to open file\n";return;}
	$q=new mysql_squid_builder();
	
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

function inject_stored_items(){
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
			if(!$q->ok){ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
		}
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){ufdbguard_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	}	
	ufdbguard_admin_events("Sucess adding $c cached websites",__FUNCTION__,__FILE__,__LINE__,"stats");
	@unlink($file);
}
