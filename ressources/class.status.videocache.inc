<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}

function videocache_increment_func($array){
	$array[]="videocache";
	$array[]="videocache_scheduler";
	$array[]="videocache_clients";
	return $array;
}

function videocache_is_enabled(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStreamCache");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$enabled=0;}
	
	videocache_events("Is video cache enabled ?: $enabled",__FUNCTION__,__LINE__);
	$GLOBALS[__FUNCTION__]=$enabled;
	return $GLOBALS[__FUNCTION__];
}

function videocache(){
	if(!is_file("/usr/share/videocache/videocache.py")){
		if($GLOBALS["VERBOSE"]){videocache_events("APP_VIDEOCACHE: /usr/share/videocache/videocache.py no such file",__FUNCTION__,__LINE__);}
		return;}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	if(!is_file($masterbin)){return;}
	$enabled=videocache_is_enabled();
	$l[]="[APP_VIDEOCACHE]";
	$l[]="service_name=APP_VIDEOCACHE";
	$l[]="master_version=".streamsquidcache_version();
	$l[]="service_cmd=/etc/init.d/squid-stream";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=streamsquidcache_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		videocache_events("APP_VIDEOCACHE: Not Running");
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --squid-stream");
			videocache_events("APP_VIDEOCACHE: $cmd");
			shell_exec($cmd);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/squid-stream start >/dev/null 2>&1 &");
			videocache_events("APP_VIDEOCACHE: /etc/init.d/squid-stream start",__FUNCTION__,__LINE__);
			squid_admin_mysql(0,"VideoCache backend not running, start it...",null,__FILE__,__LINE__);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	videocache_events("APP_VIDEOCACHE: Running");
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squidstream.php.get_status.time");
		if($CacheSchedules>15){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squidstream.php --status >/dev/null 2>&1 &");
			videocache_events("APP_VIDEOCACHE_SCHEDULER: $cmd",__FUNCTION__,__LINE__);
			shell_exec($cmd);
		}
	}
	return implode("\n",$l);return;
}
function videocache_scheduler(){
	if(!is_file("/usr/share/videocache/videocache.py")){
		if($GLOBALS["VERBOSE"]){videocache_events("APP_VIDEOCACHE_SCHEDULER: /usr/share/videocache/videocache.py no such file",__FUNCTION__,__LINE__);}
		return;
	}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	if(!is_file($masterbin)){return;}
	$enabled=videocache_is_enabled();
	$l[]="[APP_VIDEOCACHE_SCHEDULER]";
	$l[]="service_name=APP_VIDEOCACHE_SCHEDULER";
	$l[]="master_version=2.4";
	$l[]="service_cmd=/etc/init.d/vc-scheduler";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=videocache_pid();
	if($GLOBALS["VERBOSE"]){echo "APP_VIDEOCACHE_SCHEDULER: master_pid = $master_pid\n"; }

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		videocache_events("APP_VIDEOCACHE_SCHEDULER: not running");
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			video_cache_initd();
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["nohup"]} /etc/init.d/vc-scheduler start >/dev/null 2>&1 &");
			videocache_events("APP_VIDEOCACHE_SCHEDULER: $cmd",__FUNCTION__,__LINE__);
			shell_exec($cmd);
			squid_admin_mysql(0,"VideoCache Scheduler not running, start it...",null,__FILE__,__LINE__);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	videocache_events("APP_VIDEOCACHE_SCHEDULER: Running");
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squidstream.php.ParseScheduler.time");
		if($CacheSchedules>5){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squidstream.php --parse >/dev/null 2>&1 &");
			videocache_events("APP_VIDEOCACHE_SCHEDULER: $cmd",__FUNCTION__,__LINE__);
			shell_exec($cmd);
		}
	}
	
	return implode("\n",$l);return;
}

function videocache_events($text,$function,$line){
	$filename=basename(__FILE__);
	
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/squid/artica-videocache-watchddog.log");
	
}

function videocache_clients(){
	
	$enabled=videocache_is_enabled();
	
	if(!is_file("/usr/share/videocache/videocache.py")){
		if($GLOBALS["VERBOSE"]){videocache_events("APP_VIDEOCACHE_CLIENT: /usr/share/videocache/videocache.py no such file: enabled=$enabled",__FUNCTION__,__LINE__);}
		if($enabled==1){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squidstream.php --install >/dev/null 2>&1 &");
			videocache_events("APP_VIDEOCACHE: videocache.py no such file \"$cmd\"",__FUNCTION__,__LINE__);
			shell_exec($cmd);
			
		}
		return;
	}
	
	$l[]="\n[APP_VIDEOCACHE_CLIENT]";
	$l[]="service_name=APP_VIDEOCACHE_CLIENT";
	$l[]="master_version=2.4";
	$l[]="service_cmd=/etc/init.d/squid-stream";
	$l[]="service_disabled=$enabled";
	$l[]="watchdog_features=1";
	$l[]="pid_path=";

	if($enabled==0){return @implode("\n", $l);}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$pids=videocache_clients_pids();
	$count=count($pids);

	if($count==0){
		$l[]="running=0\ninstalled=1";
		$l[]="";

		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			video_cache_initd();
			$cmd="{$GLOBALS["nohup"]} /etc/init.d/squid-stream restart >/dev/null 2>&1 &";
			videocache_events("APP_VIDEOCACHE_CLIENT: \"$cmd\"");
			shell_exec($cmd);
			squid_admin_mysql(0,"VideoCache Clients not running, restart VideoCache backend...",null,__FILE__,__LINE__);
		}

		return implode("\n",$l);
	}


	$rss=0;
	$vm=0;
	videocache_events("APP_VIDEOCACHE_CLIENT: PIDs:$count");
	if($GLOBALS["VERBOSE"]){echo "-> videocache_clients_pids() -> PIDs:$count\n";}
	while (list ($num, $pid) = each ($pids)){
		if(!is_numeric($pid)){continue;}
		$newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
		if($GLOBALS["VERBOSE"]){echo "-> videocache_clients_pids() -> PID:$num-$pid RSS: $newrss\n";}
		$rss=$rss+$newrss;
		$vm=$vm+$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid,true);
		$uptime=$GLOBALS["CLASS_UNIX"]->PROCESS_UPTIME($pid);
		$master_pid=$pid;
	}
	$l[]="master_pid=$master_pid";
	$l[]="running=1";
	$l[]="master_memory=$rss";
	$l[]="master_cached_memory=$vm";
	$l[]="processes_number=$count";
	$l[]=$uptime;


	return implode("\n",$l);return;


}

function video_cache_initd(){
	$build=false;
	if(!is_file("/etc/init.d/squid-stream")){$build=true;}
	if(!is_file("/etc/init.d/vc-scheduler")){$build=true;}
	if($build){
		$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --squid-stream >/dev/null 2>&1 &");
		videocache_events("APP_VIDEOCACHE_*: \"$cmd\"");
		shell_exec($cmd);
	}
}

function streamsquidcache_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/squid-stream.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/streamsquidcache/squid.conf");
}
function videocache_pid(){
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/videocache.pid');
	if($GLOBALS["VERBOSE"]){echo "APP_VIDEOCACHE_SCHEDULER: /var/run/squid/videocache.pid = $pid\n"; }
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("python.*?vc-scheduler");
	if($GLOBALS["VERBOSE"]){echo "APP_VIDEOCACHE_SCHEDULER: PIDOF_PATTERN(python.*?vc-scheduler) = $pid\n"; }
	return $pid;
}
function videocache_clients_pids(){
	$unix=new unix();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	
	$pids=array();

	exec("$pgrep -l -f videocache.py 2>&1",$f);
	while (list ($num, $line) = each ($f)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["VERBOSE"]){echo "-> videocache_clients_pids() -> PID:$pid\n";}
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}
	return $pids;
}
function streamsquidcache_version(){
	if(isset($GLOBALS["streamsquidcache_version"])){return $GLOBALS["streamsquidcache_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			$GLOBALS["streamsquidcache_version"]=trim($re[1]);
			return $GLOBALS["streamsquidcache_version"];
		}
	}
}