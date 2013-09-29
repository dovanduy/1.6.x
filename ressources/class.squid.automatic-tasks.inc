<?php


class squid_auto_tasks{
	
	
	function squid_auto_tasks(){
		$this->LoadClasses();
		$this->execute();
	}
	
	
	private function execute(){
		$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
		$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
		if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}
		$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
		$DisableArticaProxyStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaProxyStatistics");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
		$UnlockWebStats=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
		$DisableStats=0;
		if($EnableRemoteStatisticsAppliance==1){$DisableStats=1;}
		if($DisableArticaProxyStatistics==1){$DisableStats=1;}
		
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.build_schedules_tests.time");
		if(is_file("/usr/share/artica-postfix"."/exec.squid.php")){
			if($CacheSchedules>5){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --build-schedules-test >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		}
		
		if($EnableRemoteStatisticsAppliance==1){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.netagent.php --timeout >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		$unix=new unix();
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
		if($CacheSchedules>1440){
			squid_watchdog_events("Building scheduled tasks...");
			$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --build-schedules >/dev/null 2>&1 &");
			@unlink("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
			@file_put_contents("/etc/artica-postfix/CACHES_SQUID_SCHEDULE", time());
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_ROTATE");
		if($CacheSchedules>300){
			squid_watchdog_events("Running log rotation");
			$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.logrotate.php --squid >/dev/null 2>&1 &");
			@unlink("/etc/artica-postfix/CACHES_SQUID_ROTATE");
			@file_put_contents("/etc/artica-postfix/CACHES_SQUID_ROTATE", time());
		}
		
		
		if(is_file("/usr/share/artica-postfix"."/exec.squid.watchdog.php")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php --counters >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php --peer-status >/dev/null 2>&1 &"));
		}
		
		
		if(is_file("/usr/share/artica-postfix"."/exec.squid-tail-injector.php")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid-tail-injector.php >/dev/null 2>&1 &"));
		}
		if(is_file("/usr/share/artica-postfix"."/exec.squid.php")){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
			if($time>15){
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --cache-infos >/dev/null 2>&1 &"));
			}
		}
		
		if(is_file("/usr/share/artica-postfix"."/exec.kerbauth.php")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.kerbauth.php --klist >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.kerbauth.php --winbinddpriv >/dev/null 2>&1 &"));
		}
		
		if($DisableStats==0){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.quotaday.php.start.time");
			if($time>60){
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.stats.quotaday.php >/dev/null 2>&1 &"));
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.ad.ous.php >/dev/null 2>&1 &"));
			}
		}
		
		if($DisableStats==0){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid-searchwords.php.searchwords_hour.time");
			if($time>30){
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid-searchwords.php --hour >/dev/null 2>&1 &"));
			}
		}
		
		if($DisableStats==0){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.hours.php.tables_hours.time");
			if($time>60){
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.stats.hours.php >/dev/null 2>&1 &"));
			}
		}
		
		if($DisableStats==0){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.totals.php.TOTALS_REPAIR.time");
			if($time>1440){
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.stats.totals.php --repair >/dev/null 2>&1 &"));
			}
		}
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidLogs.time");
		if($time>1440){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.clean.logs.php --squidLogs >/dev/null 2>&1 &"));
		}		
		
		
		
		if($DisableStats==1){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.clean.logs.php --squid >/dev/null 2>&1 &"));
		}else{
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.stats.totals.php --interface >/dev/null 2>&1 &"));
		}
		
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --tpl-save >/dev/null 2>&1 &"));
		}

		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysar.php.build.time");
		if($time>60){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.mysar.php >/dev/null 2>&1 &"));
		}
		
	}
	
	private function shell_exec2($cmdline){
		$this->events("Execute: $cmdline",_CLASS_."/".__FUNCTION__,__LINE__);
		shell_exec($cmdline);
	
	}
	
	private function LoadClasses(){
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		if(!isset($GLOBALS["CLASS_SOCKETS"])){
			include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
			$GLOBALS["CLASS_SOCKETS"]=new sockets();
		}
		
	}

	private function events($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-stats-executor.log");
	}	
	
}