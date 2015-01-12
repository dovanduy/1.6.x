<?php
function load_stats(){
	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
	
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$unix=new unix();
	}else{
		$unix=$GLOBALS["CLASS_UNIX"];
	}
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	$files=$unix->DirFiles("/usr/share/artica-postfix/bin");
	while (list ($filename,$line) = each ($files)){
		@chmod("/usr/share/artica-postfix/bin/$filename",0755);
		@chown("/usr/share/artica-postfix/bin/$filename","root");
	}
	
	
	@chmod("/usr/share/artica-postfix/ressources/mem.pl",0755);
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
	$NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
	$NTPDClientEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDClientEnabled"));
	if($NtpdateAD==1){$NTPDClientEnabled=1;}
	
	

	if(system_is_overloaded(basename(__FILE__))){
		$date=time();
		if(!is_file("/var/log/artica-postfix/sys_alerts/$date")){
			$ps=$unix->find_program("ps");
			$load=$GLOBALS["SYSTEM_INTERNAL_LOAD"];
			if(!$unix->process_exists($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$ps"))){
				$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]} $ps auxww >/var/log/artica-postfix/sys_alerts/$date-$load 2>&1");
				shell_exec($cmd);
			}
		}
	}else{
		if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){shell_exec_time("exec.squid.php --ping-clients-proxy",5); }

	}
	
	
// NTP CLIENT *****************************************************************************
if($NTPDClientEnabled==1){
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.ntp.time");
	$NTPDClientPool=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDClientPool"));
	if($NTPDClientPool==0){$NTPDClientPool=120;}
	if($time_file>$NTPDClientPool){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.ntpdate.php >/dev/null 2>&1 &");
	}
}
// ****************************************************************************************
	
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.syslog-engine.php.load_stats.time");
	if($time_file>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.syslog-engine.php --load-stats >/dev/null 2>&1 &");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mpstat.php.time");
	if($time_file>1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.mpstat.php >/dev/null 2>&1 &");
	}
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.philesight.php.scan_directories.time");
	if($time_file>60){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.philesight.php --directories >/dev/null 2>&1 &");
	}
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.seeker.php.xtart.time");
	events("seeker: {$time_file}mn/30mn");
	$GLOBALS["CLASS_UNIX"]->events("seeker: {$time_file}mn/30mn (/etc/artica-postfix/pids/exec.seeker.php.xtart.time)","/var/log/seeker.log",false,__FUNCTION__,__LINE__,basename(__FILE__));
	if($time_file>5){
		events("************ Executing seeker... ************");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.seeker.php >/dev/null 2>&1 &");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/croned.1/cron.notifs.php.time");
	if($time_file>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/cron.notifs.php >/dev/null 2>&1 &");
	}



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.cleanfiles.php.time");
	if($time_file>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.cleanfiles.php >/dev/null 2>&1 &");
	}
	
	$timefile=$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.CleanLogs.time");
	if($time_file>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-tmp >/dev/null 2>&1 &");
	}	



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.CHECK_DNS_SYSTEMS.time");
	events("CHECK_DNS_SYSTEMS: {$time_file}mn",__FUNCTION__,__LINE__);

	if($time_file>4){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php --dns >/dev/null 2>&1 &";
		events($cmd,__FUNCTION__,__LINE__);
		shell_exec2("$cmd");
	}
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.clean_space.time");
	events("clean_space: {$time_file}mn",__FUNCTION__,__LINE__);
	if($time_file>240){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-space >/dev/null 2>&1 &";
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