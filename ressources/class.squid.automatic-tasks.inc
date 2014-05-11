<?php
include_once(dirname(__FILE__)."/class.os.system.inc");

class squid_auto_tasks{
	
	
	function squid_auto_tasks(){
		$this->LoadClasses();
		$this->execute();
	}
	
	
	private function ifStatisticsMustBeExecuted(){
		$users=new usersMenus();
		$sock=new sockets();
		$update=true;
	
		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		$EnableRemoteStatisticsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance"));
		$CategoriesRepositoryEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRepositoryEnable"));
		if($EnableRemoteStatisticsAppliance==1){return false;}
		if($EnableWebProxyStatsAppliance==1){return true;}
		if($CategoriesRepositoryEnable==1){return true;}
		return true;
	}
	
	private function squid_rtt_events($text){
		if(trim($text)==null){return;}
	
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
	
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
		@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
		@fclose($f);
		@chmod($logFile,0755);
		@chown($logFile, "squid");
	
	}	
	
	
	private function execute(){
		$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
		$prefixcmdBIN=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}"." ";
		$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
		if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}
		$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
		$DisableArticaProxyStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaProxyStatistics");
		$EnableImportOldSquid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableImportOldSquid");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		$UnlockWebStats=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
		$ArticaDBPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDBPath");
		$EnableSargGenerator=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSargGenerator");
		$CacheManagement2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheManagement2");
		if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
		if(!is_numeric($CacheManagement2)){$CacheManagement2=0;}
		
		
		$EnableArticaHotSpot=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaHotSpot"));
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
		if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
		$DisableStats=0;
		if($SQUIDEnable==0){return;}
		if($EnableRemoteStatisticsAppliance==1){$DisableStats=1;}
		if($DisableArticaProxyStatistics==1){$DisableStats=1;}
		if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
		$ifStatisticsMustBeExecuted=$this->ifStatisticsMustBeExecuted();
		$BASEPATH="/usr/share/artica-postfix";
		
		
		
//**********************************************************************************************************************		
//D'importe quel cas, vider le dossier /var/log/squid/mysql-queue
		
		$TimeFile="/etc/artica-postfix/pids/exec.logfile_daemon-parse.php.time";
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($TimeFile);
		$this->squid_rtt_events("exec.logfile_daemon-parse.php.time = {$CacheSchedules}s");
		if($CacheSchedules>10){
			@unlink($TimeFile);
			@file_put_contents($TimeFile, time());
			$cmd=trim($prefixcmd."$BASEPATH/exec.logfile_daemon-parse.php >/dev/null 2>&1 &");
			$this->squid_rtt_events("Running $cmd");
			shell_exec($cmd);
		}
//**********************************************************************************************************************		
		//Regénères les états de l'interface ( toutes les 10mn)	
		$TimeFile="/etc/artica-postfix/pids/SQUID_ALL_STATUS.time";
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
		if($CacheSchedules>10){
			@unlink($TimeFile);
			@file_put_contents($TimeFile, time());
			$cmd=trim($prefixcmd."$BASEPATH/exec.squid.watchdog.php --all-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
//**********************************************************************************************************************		

		
		// Scan les videos Youtube toutes les heures.
		if($ifStatisticsMustBeExecuted){
			$TimeFile="/etc/artica-postfix/pids/YoutubeByHour.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
			if($CacheSchedules>65){
				$cmd=trim($prefixcmd."$BASEPATH/exec.squid.stats.youtube.days.php --onlyhours >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
				if(!is_file($TimeFile)){ @file_put_contents($TimeFile, time()); }
			}
		}
		
		
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
		if($CacheSchedules>59){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --rotate >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		

		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.quotaday.php.quotatemp.time");
		if($CacheSchedules>59){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.stats.quotaday.php --quotatemp >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			shell_exec($cmd);
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid-searchwords.php.searchwords_hour.time");
		if($CacheSchedules>62){
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid-searchwords.php --hour >/dev/null 2>&1 &");
			shell_exec($cmd);
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.quotaday.php.start.time");
		if($CacheSchedules>62){
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.stats.quotaday.php >/dev/null 2>&1 &");
			shell_exec($cmd);
		}	
		
		if(is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager")){
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/kav4proxy-licensemanager-i");
			if($CacheSchedules>60){
				if(system_is_overloaded()){return;}
				@unlink("/etc/artica-postfix/kav4proxy-licensemanager-i");
				$cmd=trim($prefixcmdBIN."/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -i >/etc/artica-postfix/kav4proxy-licensemanager-i 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				shell_exec($cmd);
			}
			
			
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.kav4proxy.php.license_infos.time");
			if($CacheSchedules>240){
				if(system_is_overloaded()){return;}
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.kav4proxy.php --license >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
			$KasperskyAskQuote=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskyAskQuote");
			if(!is_numeric($KasperskyAskQuote)){$KasperskyAskQuote=0;}
			
			if($KasperskyAskQuote==1){
				$KasperskyAskQuoteResults=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskyAskQuoteResults"));
				if($KasperskyAskQuoteResults<>"KEY_OK"){
					$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/KasperskyAskQuoteResults");
					if($CacheSchedules>5){
						$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.web-community-filter.php --register-kaspersky >/dev/null 2>&1 &");
					}
				}
			}
				
				
			
		}
		
		
		if($EnableSargGenerator==1){
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/SargDirStatus");
			if($CacheSchedules>60){
				if(system_is_overloaded()){return;}
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.sarg.php --status >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		}
		
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.hourly.tables.php.time");
		if($CacheSchedules>15){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.hourly.tables.php >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		
		if($EnableArticaHotSpot==1){
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.artica.hotspot.php.remove_mysql_sessions.time");
			if($CacheSchedules>10){
				if(system_is_overloaded()){return;}
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.artica.hotspot.php --remove-mysql-sessions >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
			
		}
		
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.cache_center_status.time");
		if($CacheSchedules>5){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.watchdog.php --caches-center-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
		if($CacheSchedules>1440){
			if(system_is_overloaded()){return;}
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --rotate >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysar.php.build.time");
		if($CacheSchedules>1440){
			if(system_is_overloaded()){return;}
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.mysar.php >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
			@unlink($CacheSchedules);
			@file_put_contents($CacheSchedules, time());
		}
		
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.syslog-engine.php.squid_rt_mysql_failed.time");
		if($CacheSchedules>5){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.syslog-engine.php --squid-rt-failed >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}		
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time");
		if($CacheSchedules>2){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.dansguardian.injector.php >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
			@unlink("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time");
			@file_put_contents("/etc/artica-postfix/pids/exec.dansguardian.injector.php.time", time());
		}
		

		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.c-icap.php.purge.time");
		if($CacheSchedules>20){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.c-icap.php --purge --schedule >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
		if($CacheSchedules>30){
			@unlink("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squidguard.php --artica-db-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/squid.compilation.params");
		if($CacheSchedules>20){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --compilation-params >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.rebuild.caches.php.clean_old_caches.time");
		if($CacheSchedules>60){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.rebuild.caches.php --clean >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.build_schedules_tests.time");
		if(is_file("/usr/share/artica-postfix/exec.squid.php")){
			if($CacheSchedules>10){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --build-schedules-test >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		}
		
		if(system_is_overloaded()){return;}
		if($EnableRemoteStatisticsAppliance==1){
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.netagent.php --timeout >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}
		
		if(system_is_overloaded()){return;}
		if(is_file("$ArticaDBPath/VERSION")){
				$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/categories-db.size.db");
				if($CacheSchedules>20){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.catz-db.php --databasesize >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		}
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
		if($CacheSchedules>1440){
			squid_watchdog_events("Building scheduled tasks...");
			$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --build-schedules >/dev/null 2>&1 &");
			@unlink("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
			@file_put_contents("/etc/artica-postfix/CACHES_SQUID_SCHEDULE", time());
		}
		
		if(system_is_overloaded()){return;}
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_ROTATE");
		if($CacheSchedules>300){
			squid_watchdog_events("Running log rotation");
			$cmd=trim($prefixcmd."/usr/share/artica-postfix"."/exec.logrotate.php --squid >/dev/null 2>&1 &");
			@unlink("/etc/artica-postfix/CACHES_SQUID_ROTATE");
			@file_put_contents("/etc/artica-postfix/CACHES_SQUID_ROTATE", time());
		}
		
		
		if(is_file("/usr/share/artica-postfix/exec.squid.watchdog.php")){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php --counters >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.watchdog.php --peer-status >/dev/null 2>&1 &"));
		}
		
		
		if(system_is_overloaded()){return;}
		if(is_file("/usr/share/artica-postfix/exec.squid-tail-injector.php")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid-tail-injector.php >/dev/null 2>&1 &"));
		}
		
		
		if(is_file("/usr/share/artica-postfix/exec.squid.php")){
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
			if($time>15){
				if(system_is_overloaded()){return;}
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.squid.php --cache-infos >/dev/null 2>&1 &"));
			}
		}
		
		if(system_is_overloaded()){return;}
		if(is_file("/usr/share/artica-postfix/exec.kerbauth.php")){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.kerbauth.php --klist >/dev/null 2>&1 &"));
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.kerbauth.php --winbinddpriv >/dev/null 2>&1 &"));
		}
		
		if($DisableStats==0){
			
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY");
			if($time>60){
				@unlink("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY");
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.blacklists.php --get-version --verbose >/dev/null 2>&1 &"));
			}
			
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid-searchwords.php.searchwords_hour.time");
			if($time>30){
				if(system_is_overloaded()){return;}
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid-searchwords.php --hour >/dev/null 2>&1 &"));
			}
			
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.hours.php.tables_hours.time");
			if($time>60){
				if(system_is_overloaded()){return;}
				$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.stats.hours.php >/dev/null 2>&1 &"));
			}			
			
			
			
		}
		
		
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidLogs.time");
		if($time>1440){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.clean.logs.php --squidLogs >/dev/null 2>&1 &"));
		}		
		
		
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time");
		if($time>15){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix"."/exec.clean.logs.php --squid >/dev/null 2>&1 &"));
		}
		
		
		
		
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --tpl-save >/dev/null 2>&1 &"));
		}
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.rotate.php.build.time");
		if($time>60){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.rotate.php >/dev/null 2>&1 &"));
		}		
		

		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysar.php.build.time");
		if($time>60){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.mysar.php >/dev/null 2>&1 &"));
		}
		
		
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.stats.hours.php.macscan.time");
		if($time>30){
			if(system_is_overloaded()){return;}
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.stats.hours.php --macscan >/dev/null 2>&1 &"));
		}		
		
	// Importation des vieux fichiers de logs Squid... ****************************************************************
		if($EnableImportOldSquid==1){
			$pidTime="/etc/artica-postfix/pids/exec.squid.logs.import.php.analyze_all.time";
			$pidfile="/etc/artica-postfix/pids/exec.squid.logs.import.php.analyze_all.pid";		
			if(!$GLOBALS["CLASS_UNIX"]->process_exists(@file_get_contents($pidfile))){
				$time=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
				if($time>30){
					if(system_is_overloaded()){return;}
					$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.logs.import.php --all >/dev/null 2>&1 &"));
				}
				
			}
		}
		
	//*******************************************************************************************************************
	
		
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/squiddb.size.db");
	if($time>20){
		if(system_is_overloaded()){return;}
		$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid-db.php --databasesize >/dev/null 2>&1 &"));
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
		
		if(!isset($GLOBALS["SQUIDBIN"])){$GLOBALS["SQUIDBIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();}
		
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