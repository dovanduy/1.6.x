<?php
function load_stats(){
	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
	$unix=new unix();
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	$datas=shell_exec(dirname(__FILE__)."/mem.pl");
	if(preg_match('#T=([0-9]+) U=([0-9]+)#',$datas,$re)){$ram_used=$re[2];}

	@mkdir("/var/log/artica-postfix/sys_loadavg",0755,true);
	@mkdir("/var/log/artica-postfix/sys_mem",0755,true);
	@mkdir("/var/log/artica-postfix/sys_alerts",0755,true);
	@mkdir("/etc/artica-postfix/croned.1",0755,true);
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	events("Internal Load: $internal_load Ram used: $ram_used",__FUNCTION__,__LINE__);
	
	@file_put_contents("/var/log/artica-postfix/sys_loadavg/$time", $internal_load);
	@file_put_contents("/var/log/artica-postfix/sys_mem/$time", $ram_used);

	if(system_is_overloaded(basename(__FILE__))){
		$date=date("Y-m-d-H-i");
		if(!is_file("/var/log/artica-postfix/sys_alerts/$date")){
			$ps=$unix->find_program("ps");
			if(!$unix->process_exists($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$ps"))){
				$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]} $ps aux >/var/log/artica-postfix/sys_alerts/$date 2>&1");
			}
		}
	}else{
		if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
			shell_exec_time("exec.squid.php --ping-clients-proxy",5);
		}

	}
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.syslog-engine.php.load_stats.time");
	if($time_file>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.syslog-engine.php --load-stats >/dev/null 2>&1 &");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mpstat.php.time");
	if($time_file>1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.mpstat.php >/dev/null 2>&1 &");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.jgrowl.php.BuildJgrowl.time");
	if($time_file>1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.jgrowl.php --build >/dev/null 2>&1 &");
	}
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/croned.1/cron.notifs.php.time");
	if($time_file>1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/cron.notifs.php >/dev/null 2>&1 &");
	}



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.cleanfiles.php.time");
	if($time_file>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.cleanfiles.php >/dev/null 2>&1 &");
	}



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.CHECK_DNS_SYSTEMS.time");
	events("CHECK_DNS_SYSTEMS: {$time_file}mn",__FUNCTION__,__LINE__);

	if($time_file>4){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --dns >/dev/null 2>&1 &";
		events($cmd,__FUNCTION__,__LINE__);
		shell_exec2("$cmd");
	}


	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("pgrep -l -f \"exec.schedules.php --run\" 2>&1",$results);

	while (list ($index,$line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		events("$line -> {$TTL}Mn");
		if($TTL<420){continue;}
		ToSyslog("Killing exec.schedules.php PID $pid");
		unix_system_kill_force($pid);
	}



	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
}