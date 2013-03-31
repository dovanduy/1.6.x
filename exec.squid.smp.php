<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if($argv[1]=="--status"){echo status();return;}
if($argv[1]=="--caches"){echo caches_generate();return;}
if($argv[1]=="--squid-z-fly"){echo caches_squid_z();return;}


fstab();
ismounted();

function fstab(){
	$sock=new sockets();
	$unix=new unix();
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	$mkdir=$unix->find_program("mkdir");
	$mount=$unix->find_program("mount");
	if(!is_dir("/dev/shm")){
		echo "Starting......: [SMP] creating /dev/shm directory..\n";
		shell_exec("$mkdir -m 1777 /dev/shm");
	}
	echo "Starting......: [SMP] checking fstab...\n";
	$datas=explode("\n",@file_get_contents("/etc/fstab"));
	
	while (list ($num, $val) = each ($datas)){
		if(preg_match("#^shm.*?tmpfs#", $val,$re)){
			echo "Starting......: [SMP] checking fstab already set...\n";
			return;
		}
		
	}
	
	echo "Starting......: [SMP] Adding SHM mount point\n";
	$datas[]="shm\t/dev/shm\ttmpfs\tnodev,nosuid,noexec\t0\t0";
	@file_put_contents("/etc/fstab", @implode("\n", $datas)."\n");
	echo "Starting......: [SMP] mounting shm point\n";
	exec("$mount shm 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		echo "Starting......: [SMP] mounting shm `$val`\n";
	}
}

function ismounted(){
	$unix=new unix();
	$datas=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($num, $val) = each ($datas)){
		if(preg_match("#^shm\s+\/dev\/shm\s+tmpfs#", $val,$re)){
			echo "Starting......: [SMP] shm is mounted\n";
			return;
		}
	}	
	$mount=$unix->find_program("mount");
	echo "Starting......: [SMP] mounting shm point\n";
	exec("$mount shm 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		echo "Starting......: [SMP] mounting shm `$val`\n";
	}
}

function status(){
	$unix=new unix();
	$sock=new sockets();
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	if($DisableSquidSNMPMode==1){return;}
	$pidof=$unix->find_program("pidof");
	$squidbin=$unix->find_program("squid");	
	
	
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	exec("$pidof $squidbin 2>&1",$results);
	$tb=explode(" ",@implode("", $results));
	
	while (list ($num, $pid) = each ($tb)){
		$pid=trim($pid);
		$other=null;$other1="<i>{main}</i>";
		if(!is_numeric($pid)){continue;}
		$filename="/proc/$pid/cmdline";
		if(!is_file($filename)){continue;}
		$names=@file_get_contents($filename);
		if(preg_match("#^\((.*?)\)#", $names,$re)){
			$other=":".$re[1];
			$other1="<i>Proc:{$re[1]}</i>";
		}
		
		$l[]="[SQUID$other]";
		$l[]="service_name=APP_SQUID";
		$l[]="master_version=". squid_master_status_version();
		$l[]="service_cmd=squid-cache";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="binpath=$squidbin";
		$l[]="explain=SQUID_CACHE_TINYTEXT";
		$l[]="remove_cmd=--squid-remove";
		$l[]="family=squid";
		$l[]="other=$other1";
		$l[]="running=1";
		$l[]=$unix->GetMemoriesOfChild($pid,true);		
		
	}
	
	
	echo @implode("\n", $l);
	
	
}

function squid_master_status_version(){
	if(isset($GLOBALS["squid_master_status_version"])){return $GLOBALS["squid_master_status_version"];}
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if($squidbin==null){$squidbin=$unix->find_program("squid3");}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : Version (as root) '{$re[1]}'\n";}
			$GLOBALS["squid_master_status_version"]=$re[1];
			return $GLOBALS["squid_master_status_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "Warning !!!!!! cannot find version in $squidbin ! !!\n";}
}


function caches_generate(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$pidffile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidffile);
	if($unix->process_exists($oldpid)){
		events_squid_caches( "Starting......: [SMP] Aready running pid $oldpid",__FUNCTION__,__LINE__);
		return;
	}
	$squidbin=$unix->LOCATE_SQUID_BIN();
	events_squid_caches("Starting......: [SMP] Using binary `$squidbin`",__FUNCTION__,__LINE__);
	if(!is_file($squidbin)){
		events_squid_caches("Starting......: [SMP] unable to stat squid...",__FUNCTION__,__LINE__);
		return;
	}
	@file_put_contents($pidffile, getmypid());
	$uuid=$unix->GetUniqueID();
	events_squid_caches("Starting......: [SMP] uuid=$uuid",__FUNCTION__,__LINE__);
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=1 AND uuid='$uuid' AND ToDelete=0 AND Building=0","artica_backup");
	if(mysql_num_rows($results)==0){
		events_squid_caches("Starting......: [SMP] No cache to build..",__FUNCTION__,__LINE__);
		caches_delete();
		return;
	}
	$stopstart=false;
	$conffile="/tmp/squid-".time().".conf";
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid-temp.pid";
	$f[]="http_port 65478";
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$cache_directory=$ligne["cache_directory"];
		$cacheid=$ligne["cacheid"];
		$cache_size=$ligne["size"]*1000;
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_maxsize=$ligne["cache_maxsize"];
		$cache_type=$ligne["cache_type"];
		$f[]="cache_dir	$cache_type $cache_directory $cache_size $cache_dir_level1 $cache_dir_level2";
		if(!is_dir($cache_directory)){
			$stopstart=true;
			events_squid_caches("Starting......: [SMP] creating $cache_directory",__FUNCTION__,__LINE__);
			@mkdir($cache_directory,0755,true);
			@chown($cache_directory, "squid");
			@chgrp($cache_directory, "squid");
		}
		
		events_squid_caches("Starting......: [SMP] Stamp cache $cacheid to be build",__FUNCTION__,__LINE__);
		$cacheid_array[$cacheid]=true;
		$q->QUERY_SQL("UPDATE squid_caches32 SET Building=1 WHERE cacheid='$cacheid'","artica_backup");
	}
	
	$su=$unix->find_program("su");
	echo "Starting......: [SMP] writing config $conffile\n";
	@file_put_contents($conffile, @implode("\n", $f));
	
	$cmd="$su -c \"$squidbin -f $conffile -z\" squid 2>&1";
	events_squid_caches("Starting......: [SMP] Launch $cmd",__FUNCTION__,__LINE__);
	exec("$cmd",$results);
	while (list ($a, $b) = each ($results)){events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);}
	
	while (list ($cacheid, $val) = each ($cacheid_array)){
		events_squid_caches("Starting......: [SMP] Stamp cache $cacheid to be builded",__FUNCTION__,__LINE__);
		$q->QUERY_SQL("UPDATE squid_caches32 SET Building=2 WHERE cacheid='$cacheid'","artica_backup");
		
	}
	
	
	events_squid_caches("Starting......: [SMP] reconfiguring the proxy cache",__FUNCTION__,__LINE__);
	$results=array();
	exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force --nocaches 2>&1",$results);
	while (list ($a, $b) = each ($results)){events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);}
	events_squid_caches("Starting......: [SMP] restarting the proxy cache",__FUNCTION__,__LINE__);
	$results=array();
	exec("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force 2>&1",$results);
	while (list ($a, $b) = each ($results)){events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);}		
	caches_delete();
	
}

function caches_delete(){
	$unix=new unix();
	
	$uuid=$unix->GetUniqueID();
	events_squid_caches("Starting......: [SMP] uuid=`$uuid`",__FUNCTION__,__LINE__);
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE enabled=0 AND uuid='$uuid' AND ToDelete=1","artica_backup");
	if(mysql_num_rows($results)==0){
		events_squid_caches("Starting......: [SMP] No cache to delete for `$uuid`...aborting task...",__FUNCTION__,__LINE__);
		return;
	}	
	
	events_squid_caches("Starting......: [SMP] Reconfiguring squid-cache in order to disconnect caches...",__FUNCTION__,__LINE__);
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	
	events_squid_caches("Starting......: [SMP] reconfiguring the proxy cache",__FUNCTION__,__LINE__);
	$results=array();
	exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force 2>&1",$results);
	while (list ($a, $b) = each ($results)){events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);}
	events_squid_caches("Starting......: [SMP] restarting the proxy cache",__FUNCTION__,__LINE__);
	$results=array();
	exec("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force 2>&1",$results);
	while (list ($a, $b) = each ($results)){events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);}
	
		
	
	
	$rm=$unix->find_program("rm");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$cache_directory=$ligne["cache_directory"];
		$cacheid=$ligne["cacheid"];
		
		if(!is_dir($cache_directory)){
			events_squid_caches("Starting......: [SMP] $cache_directory no such directory, removing the cache",__FUNCTION__,__LINE__);
			$q->QUERY_SQL("DELETE FROM squid_caches32 WHERE cacheid='$cacheid'","artica_backup");
			continue;
		}
		events_squid_caches("Starting......: [SMP] Stamp cache $cacheid to be build",__FUNCTION__,__LINE__);
		$cacheid_array[$cacheid]=$cache_directory;
		$q->QUERY_SQL("UPDATE squid_caches32 SET Building=1 WHERE cacheid='$cacheid'","artica_backup");
		events_squid_caches("Starting......: [SMP] removing $cache_directory",__FUNCTION__,__LINE__);
		$cmd="$nohup $rm -rf $cache_directory >/dev/null 2>&1 &";
		
		
	}
	
	$c=0;
	while(count($cacheid_array)>0){
		sleep(2);
		$c++;
		reset($cacheid_array);
		while (list ($cacheid, $cache_directory) = each ($cacheid_array)){
			if(!is_dir($cache_directory)){
				events_squid_caches("Starting......: [SMP] $cache_directory deleted, removing the cache",__FUNCTION__,__LINE__);
				$q->QUERY_SQL("DELETE FROM squid_caches32 WHERE cacheid='$cacheid'","artica_backup");
				unset($cacheid_array[$cacheid]);
				continue;
			}
			events_squid_caches("Starting......: [SMP] $cache_directory still exists, waiting " . count($cacheid_array)." cache(s)...",__FUNCTION__,__LINE__);
			
		}
		if($c>900){
			events_squid_caches("Starting......: [SMP] timeout...",__FUNCTION__,__LINE__);
			break;
		}
	}
	
}

function caches_squid_z(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		events_squid_caches( "Starting......: [SMP] squid no such binary",__FUNCTION__,__LINE__);
		return;
	}
	$pidffile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidffile);
	if($unix->process_exists($oldpid)){
		events_squid_caches( "Starting......: [SMP] Aready running pid $oldpid",__FUNCTION__,__LINE__);
		return;
	}
	$stopstart=false;
	$GetLocalCaches=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	$conffile="/tmp/squid-".time().".conf";
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid-temp.pid";
	$f[]="http_port 65478";
	
	while (list ($cache_dir, $line) = each ($GetLocalCaches)){
		if(!is_dir($cache_dir)){
			events_squid_caches("Starting......: [SMP] creating $cache_dir",__FUNCTION__,__LINE__);
			$stopstart=true;
			@mkdir($cache_dir,0755,true);
			@chown($cache_dir, "squid");
			@chgrp($cache_dir, "squid");
		}
		$f[]=$line;
		
	}
		
	$su=$unix->find_program("su");
	echo "Starting......: [SMP] writing config $conffile\n";
	@file_put_contents($conffile, @implode("\n", $f));
	
	$cmd="$su -c \"$squidbin -f $conffile -z\" squid 2>&1";
	events_squid_caches("Starting......: [SMP] Launch $cmd",__FUNCTION__,__LINE__);
	exec("$cmd",$results);
	while (list ($a, $b) = each ($results)){
		events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);
	}	
	echo "Starting......: [SMP] writing restarting squid-cache\n";
	exec("/etc/init.d/squid restart 2>&1",$results);
	while (list ($a, $b) = each ($results)){
		events_squid_caches("Starting......: [SMP] $b",__FUNCTION__,__LINE__);
	}	
}



function events_squid_caches($text,$function,$line){
	$file="/var/log/squid/artica-caches32.log";
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	@mkdir(dirname($file));
	$logFile=$file;
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	echo "$date [$pid] $function::$line $text\n";
	@fwrite($f, "$date [$pid] $function::$line $text\n");
	@fclose($f);
	
}

