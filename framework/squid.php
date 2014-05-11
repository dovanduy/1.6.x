<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($_GET["IS_APP_SQUIDDB_INSTALLED"])){IS_APP_SQUIDDB_INSTALLED();exit;}
if(isset($_GET["support-package-full"])){support_package_full();exit;}
if(isset($_GET["request-package-full"])){request_package_full();exit;}
if(isset($_GET["ssl-windows-gen"])){remove_ssl_cert_default();exit;}
if(isset($_GET["videocache-status"])){videocache_status();exit;}
if(isset($_GET["videocache-restart"])){videocache_restart();exit;}
if(isset($_GET["videocache-streamsquidcache"])){videocache_streamsquidcache();exit;}
if(isset($_GET["videocache-query"])){videocache_query();exit;}
if(isset($_GET["videocache-reinstall"])){videocache_reinstall();exit;}
if(isset($_GET["loggers-status"])){loggers_status();exit;}
if(isset($_GET["access-real"])){access_real();exit;}

if(isset($_GET["rttlogs-parse"])){realtime_logs_parse();exit;}



if(isset($_GET["icap-clients"])){icap_clients();exit;}


if(isset($_GET["active-requests"])){active_requests();exit;}
if(isset($_GET["squid-conf-copy"])){squid_conf_copy();exit;}
if(isset($_GET["statistics-central-run"])){statistics_central_run();exit;}
if(isset($_GET["IsIcapClient"])){IS_ICAP_CLIENT();exit;}
if(isset($_GET["UtDNSUpdate"])){UtDNSUpdate();exit;}
if(isset($_GET["UtDNSRegister"])){UtDNSRegister();exit;}
if(isset($_GET["export-category-tickets"])){export_category_tickets();exit;}
if(isset($_GET["remove-ssl-cert-def"])){remove_ssl_cert_default();exit;}
if(isset($_GET["proxy-pac-debug"])){proxy_pack_debug();exit;}
if(isset($_GET["proxy-pac-empty-debug"])){proxy_pack_debug_empty();exit;}
if(isset($_GET["proxy-pac-debug-compress"])){proxy_pack_debug_compress();exit;}
if(isset($_GET["reload-squid"])){reload_squid();exit;}
if(isset($_GET["kav4proxy-install"])){kav4proxy_install();exit;}
if(isset($_GET["kav4proxy-uninstall"])){kav4proxy_uninstall();exit;}

if(isset($_GET["kaspersky-is-installed"])){kav4proxy_is_installed();exit;}
if(isset($_GET["quick-ban"])){squid_quick_ban();exit;}
if(isset($_GET["sarg-index"])){sarg_index();exit;}
if(isset($_GET["sarg-monthly"])){sarg_monthly();exit;}
if(isset($_GET["sarg-weekly"])){sarg_weekly();exit;}
if(isset($_GET["show-content-group"])){show_content_group();exit;}
if(isset($_GET["caches-center"])){caches_center();exit;}
if(isset($_GET["force-cache-status"])){squid_force_cache_status();exit;}
if(isset($_GET["catzdb-changedir"])){catzdb_changedir();exit;}
if(isset($_GET["ufdbclient"])){ufdbclient_tests();exit;}
if(isset($_GET["squid-get-system-info"])){squid_get_system_info();exit;}
if(isset($_GET["squid-get-storage-info"])){squid_get_storage_info();exit;}
if(isset($_GET["MacToUidStats"])){MacToUidStats();exit;}
if(isset($_GET["MacToUid"])){MacToUid();exit;}
if(isset($_GET["watchdog-log"])){watchdoglogs();exit;}
if(isset($_GET["artica-db-restart"])){articadb_restart();exit;}
if(isset($_GET["rrd-perform"])){rrd_perform();exit;}
if(isset($_GET["squidhour-repair-exec"])){squidhour_repair_executed();exit;}
if(isset($_GET["squidhour-repair"])){squidhour_repair();exit;}
if(isset($_GET["firewall"])){firewall();exit;}
if(isset($_GET["prepare-build"])){prepare_build();exit;}
if(isset($_GET["prepare-build-tests"])){prepare_build_tests();exit;}
if(isset($_GET["saveSquidContent"])){saveSquidContent();exit;}
if(isset($_GET["logrotate-tenir"])){logrotate_tenir();exit;}
if(isset($_GET["import-acls"])){import_acls();exit;}
if(isset($_GET["import-squid-conf"])){import_squid_conf();exit;}
if(isset($_GET["import-webfiltering-rules"])){import_webfiltering();exit;}
if(isset($_GET["reverse-proxy-apply"])){reverse_proxy_apply();exit;}
if(isset($_GET["reload_unlock"])){reload_unlock();exit;}
if(isset($_GET["test-sarg"])){test_sarg();exit;}
if(isset($_GET["sarg-conf"])){sarg_conf();exit;}
if(isset($_GET["sarg-log"])){sarg_logs();exit;}
if(isset($_GET["sarg-restore"])){sarg_restore();exit;}
if(isset($_GET["dump-peers"])){dump_peers();exit;}
if(isset($_GET["reconstruct-caches"])){reconstruct_caches();exit;}
if(isset($_GET["restart-cache-tail"])){restart_cache_tail();exit;}
if(isset($_GET["downgrade"])){downgrade();exit;}
if(isset($_GET["current-version"])){current_version();exit;}
if(isset($_GET["user-retranslation"])){user_retranslation();exit;}
if(isset($_GET["samba-proxy"])){samba_proxy();exit;}
if(isset($_GET["idns"])){idns();exit;}
if(isset($_GET["ipcache"])){ipcache();exit;}
if(isset($_GET["purge-dns"])){purge_dns();exit;}
if(isset($_GET["cache-center-empty"])){cache_center_empty();exit;}

if(isset($_GET["purge-all-statistics"])){purge_all_statistics();exit;}
if(isset($_GET["backup-db-statistics"])){backup_all_statistics();exit;}
if(isset($_GET["squid-nat-status"])){squid_nat_status();exit;}
if(isset($_GET["squid-nat-reload"])){squid_nat_reload();exit;}
if(isset($_GET["squid-refresh"])){squid_refresh();exit;}


if(isset($_GET["link-csv"])){link_csv();exit;}
if(isset($_GET["build-whitelist"])){build_whitelist();exit;}
if(isset($_GET["build-blacklist"])){build_blacklist();exit;}


if(isset($_GET["build-whitelist"])){build_whitelist();exit;}
if(isset($_GET["build-whitelist-tenir"])){build_whitelist_tenir();exit;}
if(isset($_GET["kav4proxy-templates"])){kav4_proxy_templates();exit;}
if(isset($_GET["restart-squid"])){restart_squid();exit;}
if(isset($_GET["caches-smp-create"])){caches_smp_create();exit;}
if(isset($_GET["tests-smtp-watchfog"])){test_smtp_watchdog();exit;}
if(isset($_GET["currentusersize"])){currentusersize_array();exit;}
if(isset($_GET["exec_squid_rebuild_cache_mem"])){exec_squid_rebuild_cache_mem();exit;}
if(isset($_GET["isufdbguard-squidconf"])){isufdbguard_squidconf();exit;}
if(isset($_GET["squidlogs-stats"])){squid_logs_stats();exit;}
if(isset($_GET["ActiveRequestsNumber"])){ActiveRequestsNumber();exit;}
if(isset($_GET["squid-z-reconfigure"])){squid_z_reconfigure();exit;}
if(isset($_GET["squid-k-reconfigure"])){squid_k_reconfigure();exit;}
if(isset($_GET["CounterInfos"])){CounterInfos();exit;}
if(isset($_GET["StorageCapacity"])){StorageCapacity();exit;}
if(isset($_GET["5mncounter"])){fivemncounter();exit;}
if(isset($_GET["watchdog-logs"])){watchdog_logs();exit;}
if(isset($_GET["watchdog-auth"])){watchdog_auth();exit;}
if(isset($_GET["smp-booster-status"])){smp_booster_status();exit;}
if(isset($_GET["smp-status"])){smp_status();exit;}
if(isset($_GET["build-smooth"])){build_smooth();exit;}
if(isset($_GET["build-smooth-tenir"])){build_smooth_tenir();exit;}
if(isset($_GET["build-smooth"])){build_smooth();exit;}
if(isset($_GET["rethumbnail"])){rethumbnail();exit;}
if(isset($_GET["access-logs"])){access_logs();exit;}
if(isset($_GET["accesslogs"])){accesslogs();exit;}
if(isset($_GET["ufdbguard-logs"])){ufdbguard_logs();exit;}
if(isset($_GET["reprocess-database"])){community_reprocess_category();exit();}
if(isset($_GET["kav4proxy-update-now"])){kav4proxy_update();exit();}
if(isset($_GET["categorize-tests"])){categorize_test();exit;}
if(isset($_GET["update-database-blacklist"])){blacklist_update();exit();}
if(isset($_GET["compil-params"])){compile_params();exit();}
if(isset($_GET["migration-stats"])){migration_stats();exit();}
if(isset($_GET["re-categorize"])){re_categorize();exit();}
if(isset($_GET["kav4proxy-license-error"])){kav4proxy_license_error();exit();}
if(isset($_GET["kav4proxy-license-generate"])){kav4proxy_license_generate();exit();}
if(isset($_GET["kav4proxy-pattern-date"])){kav4proxy_pattern_date();exit();}
if(isset($_GET["kav4proxy-configure"])){kav4proxy_configure();exit();}
if(isset($_GET["squid-realtime-cache"])){squid_realtime_cache();exit();}
if(isset($_GET["visited-sites"])){visited_sites();exit();}
if(isset($_GET["rebuild-filters"])){rebuild_filters();exit();}
if(isset($_GET["ufdbguardconf"])){ufdbguardconf();exit();}
if(isset($_GET["export-web-categories"])){export_web_categories();exit();}
if(isset($_GET["export-deleted-categories"])){export_deleted_categories();exit();}
if(isset($_GET["ufdbguard-compile-database"])){ufdbguard_compile_database();exit();}
if(isset($_GET["ufdbguard-compile-alldatabases"])){ufdbguard_compile_all_databases();exit();}
if(isset($_GET["caches-types"])){caches_type();exit;}
if(isset($_GET["full-version"])){root_squid_version();exit;}
if(isset($_GET["full-dans-version"])){root_dansg_version();exit;}
if(isset($_GET["full-ufdbg-version"])){root_ufdbg_version();exit;}
if(isset($_GET["recategorize-task"])){recategorize_task();exit;}
if(isset($_GET["recategorize-day"])){recategorize_day();exit;}
if(isset($_GET["recategorize-week"])){recategorize_week();exit;}
if(isset($_GET["cron-tail-injector-plus"])){cron_tail_injector_plus();exit;}
if(isset($_GET["cron-tail-injector-moins"])){cron_tail_injector_moins();exit;}
if(isset($_GET["cachelogs"])){cachelogs();exit;}
if(isset($_GET["cache-smtp-logs"])){cache_smp_logs();exit;}
if(isset($_GET["clean-catz-cache"])){clean_catz_cache();exit;}
if(isset($_GET["build-default-tpls"])){build_default_tpls();exit;}
if(isset($_GET["build-templates"])){build_templates();exit;}
if(isset($_GET["rebuild-caches"])){rebuild_caches();exit;}
if(isset($_GET["squid-build-default-caches"])){rebuild_default_cache();exit;}
if(isset($_GET["reindex-caches"])){reindex_caches();exit;}
if(isset($_GET["remove-cache"])){remove_cache();exit;}
if(isset($_GET["logrotate"])){logrotate();exit;}
if(isset($_GET["squid-iptables"])){squid_iptables();exit;}
if(isset($_GET["join-reste"])){squid_join_reste();exit;}
if(isset($_GET["disconnect-reste"])){squid_disconnect_reste();exit;}
if(isset($_GET["compile-schedules-reste"])){compile_schedule_reste();exit;}
if(isset($_GET["reconfigure-quotas-tenir"])){reconfigure_quotas_tenir();exit;}
if(isset($_GET["reconfigure-quotas"])){reconfigure_quotas();exit;}
if(isset($_GET["isInjectrunning"])){isInjectrunning();exit;}
if(isset($_GET["pamlogon"])){samba_pam_logon();exit;}
if(isset($_GET["articadb-version"])){articadb_version();exit;}
if(isset($_GET["articadb-checkversion"])){articadb_checkversion();exit;}
if(isset($_GET["articadb-nextversion"])){articadb_nextversion();exit;}
if(isset($_GET["articadb-progress"])){articadb_progress();exit;}
if(isset($_GET["articadb-launch"])){articadb_update_now();exit;}
if(isset($_GET["articadb-nextcheck"])){articadb_next_check();exit;}
if(isset($_GET["run-report"])){run_report();exit;}
if(isset($_GET["GoogleSSL-dump"])){GoogleSSL_dump();exit;}
if(isset($_GET["weekdaynum"])){statistics_weekdaynum();exit;}
if(isset($_GET["artica-catz-restart"])){artica_catz_restart();exit;}
if(isset($_GET["summarize-day"])){summarize_day();exit;}
if(isset($_GET["mib"])){mib();exit;}
if(isset($_GET["rotate-restore"])){rotate_restore();exit;}


if(isset($_GET["refresh-caches-infos"])){refresh_cache_infos();exit;}
if(isset($_GET["purge-categories"])){purge_categories();exit;}
if(isset($_GET["schedule-maintenance-db"])){schedule_maintenance_db();exit;}
if(isset($_GET["schedule-maintenance-exec"])){schedule_maintenance_executed();exit;}
if(isset($_GET["schedule-import-exec"])){schedule_import_executed();exit;}
if(isset($_GET["schedule-maintenance-tlse"])){schedule_maintenance_executed_tlse();exit;}
if(isset($_GET["schedule-maintenance-toulouse-db"])){schedule_maintenance_tlse_db();exit;}
if(isset($_GET["tlse-checks"])){schedule_maintenance_tlse_check();exit;}
if(isset($_GET["ping-kdc"])){ping_kdc();exit;}
if(isset($_GET["khse-database"])){khse_database();exit;}
if(isset($_GET["squid-reconfigure"])){reconfigure_squid();exit;}
if(isset($_GET["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}
if(isset($_GET["watchdog-config"])){watchdog_config();exit;}
if(isset($_GET["build-schedules"])){build_schedules();exit;}
if(isset($_GET["run-scheduled-task"])){run_schedules();exit;}
if(isset($_GET["UpdateUtility-webevents"])){UpdateUtility_webevents();exit;}
if(isset($_GET["ufdbguard-events"])){ufdbguard_events();exit;}
if(isset($_GET["ufdbguard-compile-smooth-tenir"])){ufdbguard_compile_smooth_tenir();exit;}
if(isset($_GET["ufdbguard-compile-smooth2"])){ufdbguard_compile_smooth2();exit;}
if(isset($_GET["ufdbguard-restart-tenir"])){ufdbguard_restart_tenir();exit;}


if(isset($_GET["cntlm-restart"])){cntlm_restart();exit;}



if(isset($_GET["purge-site"])){purge_site();exit;}
if(isset($_GET["boosterpourc"])){boosterpourc();exit;}

if(isset($_GET["compile-list"])){compile_list();exit;}
if(isset($_GET["ufdbguard-compile-smooth"])){ufdbguard_compile_smooth();exit;}
if(isset($_GET["compile-by-interface"])){compile_by_interface();exit;}
if(isset($_GET["support-step1"])){support_step1();exit;}
if(isset($_GET["support-step2"])){support_step2();exit;}
if(isset($_GET["support-step3"])){support_step3();exit;}
if(isset($_GET["delete-backuped-category-container"])){delete_backuped_container();exit;}
if(isset($_GET["restore-backup-catz"])){restore_backuped_categories();exit;}
if(isset($_GET["empty-perso-catz"])){empty_personal_categories();exit;}
if(isset($_GET["ScanThumbnails"])){ScanThumbnails();exit;}
if(isset($_GET["recompile-debug"])){recompile_debug();exit;}
if(isset($_GET["clean-mysql-stats"])){clean_mysql_stats_db();exit;}
if(isset($_GET["follow-xforwarded-for-enabled"])){follow_xforwarded_for_enabled();exit;}
if(isset($_GET["enable-http-violations-enabled"])){enable_http_violations_enabled();exit;}
if(isset($_GET["update-ufdb-precompiled"])){update_ufdb_precompiled();exit;}
if(isset($_GET["squid-sessions"])){squidclient_sessions();exit;}
if(isset($_GET["notify-remote-proxy"])){notify_remote_proxy();exit;}
if(isset($_GET["fw-rules"])){fw_rules();exit;}
if(isset($_GET["update-blacklist"])){update_blacklist();exit;}
if(isset($_GET["cicap-template"])){CICAP_TEMPLATE();exit;}
if(isset($_GET["cicap-memboost"])){CICAP_MEMBOOST();exit;}
if(isset($_GET["stats-members-generic"])){stats_members_generic();exit;}
if(isset($_GET["squidclient-infos"])){squidclient_infos();exit;}
if(isset($_GET["articadbsize"])){articadb_size();exit;}
if(isset($_GET["CheckRunningTasks"])){CheckRunningTasks();exit;}
if(isset($_GET["dynamicgroups-logs"])){dynamic_groups_logs();exit;}
if(isset($_GET["old-logs-scan-nas"])){squid_oldlogs_scan_nas();exit;}
if(isset($_GET["old-logs-import-nas"])){squid_oldlogs_import_nas();exit;}
if(isset($_GET["purge-numeric-members-statistics"])){purge_numeric_members_statistics();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

//-----------------------------------------------------------------------------------------------------------------------------------
function access_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$search=$_GET["search"];
	if(strlen($search)>1){
		$search=str_replace("*", ".*", $search);
		$cmd="$tail -n 1000 /var/log/squid/access.log|$grep -i -E \"$search\" 2>&1";
	}else{
		$cmd="$tail -n 500 /var/log/squid/access.log 2>&1";
	}
	
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

//-----------------------------------------------------------------------------------------------------------------------------------
function root_squid_version(){
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		exec("$squidbin -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
				echo "<articadatascgi>". trim($re[1])."</articadatascgi>";	
			}
		}
		
	}
	//-----------------------------------------------------------------------------------------------------------------------------------	
	
function smp_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.smp.php --status 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function smp_booster_status(){
	$unix=new unix();
	$df=$unix->find_program("df");
	exec("$df -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#^tmpfs\s+.+?\s+.+?\s+.+?\s+([0-9\.]+).*?\/var\/squid\/cache_booster-([0-9]+)#", trim($val),$re)){
			writelogs_framework("{$re[2]} - > {$re[1]}%",__FUNCTION__,__FILE__,__LINE__);
			$array[$re[2]]=$re[1];
			continue;
		}
		
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
//-----------------------------------------------------------------------------------------------------------------------------------
	
function recategorize_day(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.recategorize.missed.php {$_GET["recategorize-day"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function NoCategorizedAnalyze(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --calculate-not-categorized >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function export_category_tickets(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --export-category-tickets >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
//-----------------------------------------------------------------------------------------------------------------------------------




function watchdog_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
		
}
//-----------------------------------------------------------------------------------------------------------------------------------
function GoogleSSL_dump(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.nosslsearch.google.com.php --dump 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
//-----------------------------------------------------------------------------------------------------------------------------------


function CheckRunningTasks(){
	$unix=new unix();
	$ps=$unix->find_program("ps");
	$grep=$unix->find_program("grep");
	$array=array();
	exec("$ps -x|grep -i -E \"schedule-id=\" 2>&1",$results);
	writelogs_framework(count($results)." items..",__FUNCTION__,__FILE__,__LINE__);	
	while (list ($num, $val) = each ($results)){
		
		if(preg_match("#^([0-9]+).*?schedule-id=([0-9]+)#", $val,$re)){
			$array[$re[2]]=$unix->PROCCESS_TIME_MIN($re[1]);
			
		}else{
			writelogs_framework("BAD:{$re[2]} -> $val",__FUNCTION__,__FILE__,__LINE__);	
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}
//-----------------------------------------------------------------------------------------------------------------------------------


function build_schedules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart watchdog >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function run_schedules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --run-schedules {$_GET["run-scheduled-task"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
}
//-----------------------------------------------------------------------------------------------------------------------------------

function recategorize_week(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --re-categorize-week {$_GET["recategorize-week"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function cron_tail_injector_plus(){
	cron_tail_injector_moins();
}
function cron_tail_injector_moins(){
	@unlink("/etc/cron.d/SquidTailInjector");
}
//-----------------------------------------------------------------------------------------------------------------------------------
	
function root_dansg_version(){
		$unix=new unix();
		$bin=$unix->find_program("dansguardian");
		if(!is_file($bin)){echo "<articadatascgi>0.0.0.0</articadatascgi>";return;}
		exec("$bin -v 2>&1",$results);	
		while (list ($num, $val) = each ($results)){
			if(preg_match("#^DansGuardian\s+([0-9\.\-a-z]+)#", $val,$re)){
				echo "<articadatascgi>". trim($re[1])."</articadatascgi>";	
			}
		}	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function root_ufdbg_version(){
		$unix=new unix();
		$bin=$unix->find_program("ufdbguardd");
		if(!is_file($bin)){echo "<articadatascgi>0.0.0.0</articadatascgi>";return;}
		exec("$bin -v 2>&1",$results);	
		while (list ($num, $val) = each ($results)){
			if(preg_match("#^ufdbguardd:\s+([0-9\.\-a-z]+)#", $val,$re)){
				echo "<articadatascgi>". trim($re[1])."</articadatascgi>";	
			}
		}	
}
//----------------------------------------------------------------------------------------------------------------------------------- 

function community_reprocess_category(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --reprocess-database  {$_GET["reprocess-database"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
//-----------------------------------------------------------------------------------------------------------------------------------
function recategorize_task(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.fcron.php --squid-recategorize-task >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function categorize_test(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.categorize-tests.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
//-----------------------------------------------------------------------------------------------------------------------------------
function export_web_categories(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --export-perso-cats >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
//-----------------------------------------------------------------------------------------------------------------------------------
function export_deleted_categories(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --export-deleted >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
//-----------------------------------------------------------------------------------------------------------------------------------

function migration_stats(){
	$unix=new unix();
	if(is_file("/etc/artica-postfix/exec.squid.logs.migrate.php.pid")){
		$pid=$unix->get_pid_from_file("/etc/artica-postfix/exec.squid.logs.migrate.php.pid");
		if(is_numeric($pid)){
			if($unix->process_exists($pid,"exec.squid.logs.migrate.php")){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				writelogs_framework("/etc/artica-postfix/exec.squid.logs.migrate.php.pid ->$pid $time mn",__FUNCTION__,__FILE__,__LINE__);
				echo "<articadatascgi>". base64_encode(serialize( array($pid,$time)))."</articadatascgi>";
				return;
			}
		}
	}
	if(is_file("/etc/artica-postfix/pids/exec.squid.logs.migrate.php.pid")){
		$pid=$unix->get_pid_from_file("/etc/artica-postfix/pids/exec.squid.logs.migrate.php.pid");
		if(is_numeric($pid)){
			if($unix->process_exists($pid,"exec.squid.logs.migrate.php")){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				echo "<articadatascgi>". base64_encode(serialize(array($pid,$time)))."</articadatascgi>";
			}
		}
	}		
}
//-----------------------------------------------------------------------------------------------------------------------------------
function compile_params(){
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --compilation-params");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function kav4proxy_configure(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kav4proxy.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function blacklist_update(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --update >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function notify_remote_proxy(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --notify-clients-proxy >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function squid_force_cache_status(){
	$statusfile="/usr/share/artica-postfix/ressources/logs/web/status.squid";
	@unlink($statusfile);
	@touch($statusfile);
	@chmod($statusfile,0777);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --squid-store-status --force >$statusfile 2>&1 &";	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function squid_k_reconfigure(){
	$statusfile="/usr/share/artica-postfix/ressources/logs/web/restart.squid";
	@unlink($statusfile);
	@touch($statusfile);
	@chmod($statusfile,0777);
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	$force=null;
	if(isset($_GET["force"])){$force=" --force";}
	squid_watchdog_events("Reconfiguring Proxy parameters...");
	
	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	
	if(isset($_GET["ApplyConfToo"])){
		$php5=$unix->LOCATE_PHP5_BIN();
		$cmd="$php5 /usr/share/artica-postfix/exec.squid.php --build{$force} >>$statusfile 2>&1 &";
		squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @file_get_contents($statusfile));
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		return;
	}
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --kreconfigure >> /usr/share/artica-postfix/ressources/logs/web/restart.squid 2>&1");
	squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @file_get_contents($statusfile));
	sleep(2);
	$tail=$unix->find_program("tail");
	shell_exec("$tail -n 100 /var/log/squid/cache.log >> $statusfile 2>&1");
}

function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function squid_z_reconfigure(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/restart.squid");
	shell_exec("/bin/touch /usr/share/artica-postfix/ressources/logs/web/restart.squid");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/restart.squid",0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$force=null;
	if(isset($_GET["force"])){$force=" --force";}
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --squidz{$force} >> /usr/share/artica-postfix/ressources/logs/web/restart.squid 2>&1 &");
	
}
function firewall(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/restart.squid");
	shell_exec("/bin/touch /usr/share/artica-postfix/ressources/logs/web/restart.squid");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/restart.squid",0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(!is_file("/etc/init.d/tproxy start")){
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.transparent.php >> /usr/share/artica-postfix/ressources/logs/web/restart.squid 2>&1 &");
	}else{
		shell_exec("$nohup /etc/init.d/tproxy start >> /usr/share/artica-postfix/ressources/logs/web/restart.squid 2>&1 &");
	}

}

function kav4proxy_update(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.keepup2date.php --update >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function kav4ProxyPatternDatePath(){
$unix=new unix();
	$base=$unix->KAV4PROXY_GET_VALUE("path","BasesPath");
	if(is_file("$base/u0607g.xml")){return "$base/u0607g.xml";}	
	if(is_file("$base/master.xml")){return "$base/master.xml";}
	if(is_file("$base/av-i386-0607g.xml")){return "$base/av-i386-0607g.xml";}
	return "$base/master.xml";
}

function UpdateUtility_webevents(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$max="500";
	if(is_numeric($_GET["rp"])){$max=$_GET["rp"];}
	if($_GET["search"]<>null){
		$search=base64_decode($_GET["search"]);
		$cmd="$grep -i -E '$search' /var/log/UpdateUtility/access.log|$tail -n $max 2>&1";
		
		
	}else{
		$cmd="$tail -n $max /var/log/UpdateUtility/access.log 2>&1";
	}
	writelogs_framework("rp={$_GET["rp"]} `$cmd`",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function ufdbguard_events(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$max="500";
	if(is_numeric($_GET["rp"])){$max=$_GET["rp"];}
	if($_GET["search"]<>null){
		$search=base64_decode($_GET["search"]);
		writelogs_framework("SEARCH $search",__FUNCTION__,__FILE__,__LINE__);
		
		$cmd="$grep -i -E '$search' /var/log/squid/ufdbguardd.log|$tail -n $max 2>&1";
		
		
	}else{
		$cmd="$tail -n $max /var/log/squid/ufdbguardd.log 2>&1";
	}
	writelogs_framework("rp={$_GET["rp"]} `$cmd`",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}


function kav4proxy_pattern_date(){
	$unix=new unix();
	$base=kav4ProxyPatternDatePath();
	writelogs_framework("Found $base",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($base)){
		writelogs_framework("$base no such file",__FUNCTION__,__FILE__,__LINE__);
		return;}
	$f=explode("\n",@file_get_contents($base));
	$reg='#UpdateDate="([0-9]+)\s+([0-9]+)"#';
	
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match($reg,$ligne,$re)){
			writelogs_framework("Found {$re[1]} {$re[2]}",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match('#([0-9]{1,2})([0-9]{1,2})([0-9]{1,4});([0-9]{1,2})([0-9]{1,2})#',trim($re[1]).";".trim($re[2]),$regs)){
				echo "<articadatascgi>". base64_encode($regs[3]. "/" .$regs[2]. "/" .$regs[1] . " " . $regs[4] . ":" . $regs[5])."</articadatascgi>";
				return;
			}
		}
	}	
	writelogs_framework("Not found in $base",__FUNCTION__,__FILE__,__LINE__);
}

function kav4proxy_license_generate(){
	$unix=new unix();
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if(isset($_GET["keep"])){
		$cmd=trim("$php5 /usr/share/artica-postfix/exec.kav4proxy.php --license --force >/dev/null 2>&1");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		return;
	}
	
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kav4proxy.php --license --force >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function kav4proxy_license_error(){
	$unix=new unix();
	
	$cacheFile="/etc/artica-postfix/kav4proxy-licensemanager-i";
	if(is_file($cacheFile)){
		$cmd=$cacheFile;
		$results=explode("\n",@file_get_contents($cacheFile));
	}else{
		$cmd=trim("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -i 2>&1");
		exec($cmd,$results);
	}
	writelogs_framework("$cmd = ".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Error loading license :(.+)#", $ligne,$re)){
			writelogs_framework("{$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(trim($re[1]))."</articadatascgi>";
			return;
		}		
	}
}

function squid_realtime_cache(){
	echo "<articadatascgi>".@file_get_contents("/etc/artica-postfix/squid-realtime.cache")."</articadatascgi>";
}

function visited_sites(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.visited.sites.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}


function re_categorize(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.re-categorize.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function ufdbguard_compile_smooth_tenir(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/compile.ufdbguard.interface.txt");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --build-ufdb-smoothly --force >/usr/share/artica-postfix/ressources/logs/web/compile.ufdbguard.interface.txt 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
	
	
}
function ufdbguard_compile_smooth2(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/ufdbguard-output");
	@touch("/usr/share/artica-postfix/ressources/logs/ufdbguard-output");
	@chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard-output",0755);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --build-ufdb-smoothly --force >/usr/share/artica-postfix/ressources/logs/ufdbguard-output 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function ufdbguard_compile_smooth(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --build-ufdb-smoothly >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
	
	
}
function rebuild_filters(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(isset($_GET["force"])){$f=" --force ";}
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --build $f>/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
}
function ufdbguard_compile_database(){
	$unix=new unix();
	$database=$_GET["ufdbguard-compile-database"];
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$devnull=">/dev/null";
	$debugfile="/usr/share/artica-postfix/ressources/logs/web/squidguard-$database.dbg";
	if(isset($_GET["debug"])){
		@unlink($debugfile);
		$devnull="--verbose >$debugfile";
	}
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category \"$database\" $devnull 2>&1 &");
	shell_exec($cmd);
	
	if(isset($_GET["debug"])){sleep(1);@chmod($debugfile,0777);}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function ufdbguard_compile_all_databases(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --compile-all-categories >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function CICAP_TEMPLATE(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.c-icap.php --template >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function CICAP_MEMBOOST(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.c-icap.php --memboost >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
}

function isInjectrunning(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("pgrep -l -f \"exec.squid.blacklists.php --v2\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#^([0-9]+).*?blacklists\.php#", $ligne,$re)){
			echo "<articadatascgi>". $unix->PROCCESS_TIME_MIN($re[1])."</articadatascgi>";
			return;
		}
	}
	 
	
}


function ufdbguardconf(){
	$tpl=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	echo "<articadatascgi>". base64_encode(serialize($tpl))."</articadatascgi>";
}


function caches_type(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid3");
	if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
	exec("$squidbin -v 2>&1",$results);
	writelogs_framework("$squidbin -v = ".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);	
	$caches["aufs"]="aufs";
	while (list ($num, $ligne) = each ($results) ){	
		if(!preg_match("#--enable-storeio=(.+?)'#", $ligne,$re)){
			writelogs_framework("$num) $ligne no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		$list=explode(",", $re[1]);
		while (list ($a, $b) = each ($list) ){
			$b=trim($b);
			if($b==null){continue;}
			if($b=="ufs"){$b="aufs";}
			$caches[$b]="{squid_$b}";}
	}
	echo "<articadatascgi>". base64_encode(serialize($caches))."</articadatascgi>";
	
}
function cachelogs(){
	$search=trim(base64_decode($_GET["cachelogs"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}
	
	if($search==null){
		
		$cmd="$tail -n $rp /var/log/squid/cache.log 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
		exec("$tail -n $rp /var/log/squid/cache.log 2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=$unix->StringToGrep($search);
	
	
	$cmd="$grep -i -E '$search' /var/log/squid/cache.log 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec("$cmd",$results);
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}
function watchdoglogs(){
	$search=trim(base64_decode($_GET["watchdog-log"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}

	if($search==null){

		$cmd="$tail -n $rp /var/log/squid.watchdog.log  2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$tail -n $rp /var/log/squid.watchdog.log  2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}

	$search=$unix->StringToGrep($search);

	$cmd="$grep -E '$search' /var/log/squid.watchdog.log  2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);

	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}

function sarg_logs(){
	$search=trim(base64_decode($_GET["watchdog-log"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}
	
	if($search==null){
	
		$cmd="$tail -n $rp /var/log/sarg-exec.log  2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$tail -n $rp /var/log/sarg-exec.log  2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=$unix->StringToGrep($search);
	
	$cmd="$grep -E '$search' /var/log/sarg-exec.log  2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}


function cache_smp_logs(){
	$search=trim(base64_decode($_GET["cachelogs"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}
	
	if($search==null){
	
		$cmd="$tail -n $rp /var/log/squid/artica-caches32.log 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$tail -n $rp /var/log/squid/artica-caches32.log 2>&1",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=$unix->StringToGrep($search);
	
	$cmd="$grep -i -E '$search' /var/log/squid/artica-caches32.log 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function ufdbguard_logs(){
	$unix=new unix();
	$search=trim(base64_decode($_GET["ufdbguard-logs"]));
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");	
	$rp=300;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}	
	$results=array();
	$filetemp=$unix->FILE_TEMP();
	$search=$unix->StringToGrep($search);
	$results=$unix->tail_php("/var/log/squid/ufdbguardd.log",$rp,"\] BLOCK\s+.*?$search");
	writelogs_framework(" SEARCH -> ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}


function accesslogs(){
	$search=trim(base64_decode($_GET["accesslogs"]));
	$OnlyIpAddr=$_GET["OnlyIpAddr"];
	if($OnlyIpAddr<>null){
		$OnlyIpAddr=str_replace(".", "\.", $OnlyIpAddr);
		$OnlyIpAddr=".*?$OnlyIpAddr";
	}
	
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=$_GET["rp"];}
	
	if($search==null){
		
		$cmd="$tail -n 3000 /var/log/auth.log|$grep -i -E 'squid.*?$OnlyIpAddr'|$tail -n $rp 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
		exec("$cmd",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=$unix->StringToGrep($search);
	
	$cmd="$tail -n 3000 /var/log/auth.log|$grep -i -E 'squid([\[|\-])' 2>&1|$grep -E '$search' 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec("$cmd",$results);
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}
function rebuild_caches(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function rebuild_default_cache(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --default >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function reindex_caches(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --reindex >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function clean_catz_cache(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --clean-catz-cache >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function build_default_tpls(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --mysql-tpl >/dev/null");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function build_templates(){
	$unix=new unix();
	$params="--tpl-save";
	if(isset($_GET["zmd5"])){$params="--tpl-unique {$_GET["zmd5"]}";}
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php $params >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function schedule_import_executed(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"exec.squid.blacklists.php --inject\" 2>&1");
	while (list ($num, $ligne) = each ($results) ){	
		if(!preg_match("#^([0-9]+).+?blacklists\.php#", $ligne,$re)){continue;}	
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		writelogs_framework("Found:$ligne",__FUNCTION__,__FILE__,__LINE__);	
		$time=$unix->PROCCESS_TIME_MIN($re[1]);
		$array["RUNNING"]=true;
		$array["TIME"]=$time;
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		}		
}

function squid_join_reste(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmdline="$php5 /usr/share/artica-postfix/exec.kerbauth.php --build --output 2>&1";
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmdline",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function squid_disconnect_reste(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.kerbauth.php --disconnect 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}


function refresh_cache_infos(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function purge_categories(){
	@unlink("/etc/artica-postfix/instantBlackList.cache");
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /opt/artica/proxy");
}
function schedule_maintenance_db(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --schedule-maintenance >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
}
function schedule_maintenance_tlse_db(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.update.squid.tlse.php --force >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function schedule_maintenance_tlse_check(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.update.squid.tlse.php --mysqlcheck >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function ping_kdc(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kerbauth.php --ping --force >/dev/null &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
}

function build_smooth(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --smooth-build --force >/dev/null &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
function build_smooth_tenir(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
function schedule_maintenance_executed(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$cmd=trim("$pgrep -l -f exec.squid.blacklists.php 2>&1");
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(!preg_match("#^([0-9]+).+?blacklists\.php#", $ligne,$re)){
			writelogs_framework("No Match:$ligne",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}	
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		writelogs_framework("Found:$ligne",__FUNCTION__,__FILE__,__LINE__);	
		$time=$unix->PROCCESS_TIME_MIN($re[1]);
		$array["RUNNING"]=true;
		$array["TIME"]=$time;
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		}
}
function schedule_maintenance_executed_tlse(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	
	$cmd=trim("$pgrep -l -f exec.update.squid.tlse.php 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(!preg_match("#^([0-9]+).+?squid\.tlse\.php#", $ligne,$re)){
			writelogs_framework("No Match:$ligne",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}	
		writelogs_framework("Match:$ligne",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		$time=$unix->PROCCESS_TIME_MIN($re[1]);
		$array["RUNNING"]=true;
		$array["TIME"]=$time;
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		
	}
}

function reconfigure_squid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	user_retranslation();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function user_retranslation(){
	$unix=new unix();
	$update=null;
	if(isset($_GET["update"])){$update=" --update";}
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.usrmactranslation.php --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function samba_proxy(){
	$unix=new unix();
	$update=null;
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kerbauth.php --samba-proxy >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function khse_database(){
	if(!is_file("/opt/kaspersky/khse/libexec/khse-0607g.xml")){return null;}
	$f=explode("\n", @file_get_contents("opt/kaspersky/khse/libexec/khse-0607g.xml"));
	while (list ($num, $ligne) = each ($f) ){	
		if(preg_match("#UpdateDate=\"(.+?)\"#", $ligne,$re)){echo "<articadatascgi>{$re[1]}</articadatascgi>";}
		
	}
	
}
function boosterpourc(){
	$unix=new unix();
	$df=$unix->find_program("df");
	
	exec("$df 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(preg_match("#^tmpfs\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9]+)%\s+\/var\/squid\/cache_booster#", $ligne,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
			
		}
		
	}
	
	writelogs_framework("$df 2>&1",__FUNCTION__,__FILE__,__LINE__);		
	
}

function samba_pam_logon(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$creds=unserialize(base64_decode($_GET["pamlogon"]));
	exec("$wbinfo --pam-logon={$creds["username"]}%'{$creds["password"]}' 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(preg_match("#succeeded#", $ligne)){
			echo "<articadatascgi>". base64_encode("SUCCESS")."</articadatascgi>";
			return;
		}
	}
	
}

if(isset($_GET["pamlogon"])){samba_pam_logon();exit;}

function compile_list(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";return;}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){	
		if(preg_match("#configure options:\s+(.*)#" , $ligne,$re)){
			$f=explode(" ", $re[1]);
			while (list ($a, $b) = each ($f) ){	
				$b=str_replace("'", "", $b);
				if(preg_match("#(.*?)=(.*)#", $b,$re)){
					if(trim($re[2])==null){$re[2]=true;}
					$newArray[trim($re[1])]=$re[2];
					continue;
				}
				$newArray[$b]=true;
			}
		}
	}
	
	echo "<articadatascgi>". base64_encode(serialize($newArray))."</articadatascgi>";
	
}

function purge_site(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$purge=$unix->find_program("purge");
	if(!is_file($purge)){return;}
	$sitename=$_GET["purge-site"];
	$sitename=str_replace(".", "\.", $sitename);
	$SquidMgrListenPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/$SquidMgrListenPort");
	$cmd="$nohup $purge purge -c /etc/squid3/squid.conf -h 127.0.0.1:$SquidMgrListenPort -e \"$sitename\" -P 1 >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function support_step1(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.support.package.php --step1");
	writelogs_framework("DONE...",__FUNCTION__,__FILE__,__LINE__);
}
function support_step2(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.support.package.php --step2");
	writelogs_framework("DONE...",__FUNCTION__,__FILE__,__LINE__);
}
function support_step3(){
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$filename="support.tar.gz";
	$nohup=$unix->find_program("nohup");
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.support.package.php --step3");
	$size=$unix->file_size("/usr/share/artica-postfix/ressources/support/$filename");
	$sizeText=$size/1024;
	$sizeText=$sizeText/1000;
	$sizeText=round($sizeText,2);
	writelogs_framework("Task finish $sizeText Mb",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". $unix->file_size("/usr/share/artica-postfix/ressources/support/$filename")."</articadatascgi>";
	@chmod("/usr/share/artica-postfix/ressources/support/$filename", 0755);
	writelogs_framework("DONE...",__FUNCTION__,__FILE__,__LINE__);
}

function restore_backuped_categories(){
	$path=base64_decode($_GET["restore-backup-catz"]);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.cloud.compile.php --import-backuped-categories \"$path\" >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}



function delete_backuped_container(){
	$path=base64_decode($_GET["delete-backuped-category-container"]);
	if(is_file($path)){@unlink($path);}
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.cloud.compile.php --backup-catz-mysql >/dev/null 2>&1");	
	shell_exec($cmd);
	writelogs_framework("`$path` -> $cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function empty_personal_categories(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.cloud.compile.php --empty-perso-catz >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function import_no_catz_artica(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.categorize-tests.php --import-artica-cloud >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function ScanThumbnails(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --thumbs-sites >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function rethumbnail(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.stats.php --thumbs \"{$_GET["rethumbnail"]}\" --force >/dev/null 2>&1");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function compile_by_interface(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.compile.txt");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build --force >/usr/share/artica-postfix/ressources/logs/web/squid.compile.txt 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function recompile_debug(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.indebug.log");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build --force --verbose >/usr/share/artica-postfix/ressources/logs/web/squid.indebug.log 2>&1");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function clean_mysql_stats_db(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.mysql.clean.php --squid-stats >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function remove_cache(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --remove-cache \"{$_POST["remove-cache"]}\" >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function follow_xforwarded_for_enabled(){
		$enabled=false;
		$unix=new unix();
		$squidbin=$unix->find_program("squid3");
		if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
		exec("$squidbin -v 2>&1",$results);
		while (list($num,$val)=each($results)){if(preg_match("#enable-follow-x-forwarded-for#", $val)){$enabled=true;}}
		if(!$enabled){echo "<articadatascgi>FALSE</articadatascgi>";return;}
		echo "<articadatascgi>TRUE</articadatascgi>";
}
function enable_http_violations_enabled(){
		$enabled=false;
		$unix=new unix();
		$squidbin=$unix->find_program("squid3");
		if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
		exec("$squidbin -v 2>&1",$results);
		while (list($num,$val)=each($results)){if(preg_match("#enable-http-violations#", $val)){$enabled=true;}}
		if(!$enabled){echo "<articadatascgi>FALSE</articadatascgi>";return;}
		echo "<articadatascgi>TRUE</articadatascgi>";
}

function update_ufdb_precompiled(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --ufdb --force >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function logrotate(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --rotate >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function logrotate_tenir(){
	writelogs_framework("logrotate_tenir()...",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$target_file="/usr/share/artica-postfix/ressources/logs/web/squidrotate.txt";
	@unlink($target_file);
	@file_put_contents("$target_file", "Please wait.....\n");
	@chmod($target_file,0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --rotate --verbose >>$target_file 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}



function update_blacklist(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --v2 --force >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}


function squidclient_infos(){
	$unix=new unix();
	$results=explode("\n",$unix->squidclient("info"));
	$start=false;
	while (list($num,$val)=each($results)){
		if(preg_match("#Current Time#", $val)){$start=true;continue;}
		if(!$start){continue;}
		$f[]=$val;
	}
	echo "<articadatascgi>".base64_encode(serialize($f))."</articadatascgi>";
	
}

function squidclient_sessions(){
	writelogs_framework("OK START",__FUNCTION__,__LINE__);
	$unix=new unix();
	$results=array();
	
	if(is_file("/var/log/squid/monitor.sessions.cache")){
		$results=unserialize(@file_get_contents("/var/log/squid/monitor.sessions.cache"));
	}
	
	if(count($results)<2){
		$results=explode("\n",$unix->squidclient("active_requests"));
	}
	
	while (list($num,$val)=each($results)){
		if(preg_match("#Connection:\s+(.+)#i", $val,$re)){$conexion=trim($re[1]);continue;}
		if(preg_match("#FD desc:\s+(.+)#i", $val,$re)){$array[$conexion]["URI"]=trim($re[1]);continue;}
		if(preg_match("#uri\s+(.+)#i", $val,$re)){
			if(preg_match("#cache_object#", $val)){continue;}
			$array[$conexion]["URI"]=trim($re[1]);continue;}
		if(preg_match("#remote:\s+(.*?):#i",$val,$re)){$array[$conexion]["CLIENT"]=trim($re[1]);continue;}
		if(preg_match("#start\s+([0-9\.]+)\s+\((.+?)\)#i",$val,$re)){$array[$conexion]["SINCE"]=trim($re[2]);continue;}
		if(preg_match("#username\s+(.+?)#i",$val,$re)){$array[$conexion]["USER"]=trim($re[2]);continue;}
		writelogs_framework("Not parsed \"$val\"",__FUNCTION__,__LINE__);
	}
	writelogs_framework("$cmd 2>&1 ".count($results)." rows returned ". count($array). " rows",__FUNCTION__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
	
}
function squid_iptables(){
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return;}
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.transparent.php >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
function compile_schedule_reste(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build-schedules --verbose 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}

function reconfigure_quotas_tenir(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.quotasbuild.php --build --verbose 2>&1");	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";		
}
function reconfigure_quotas(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.quotasbuild.php --build --verbose >/dev/null 2>&1 &");	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";		
}
function fw_rules(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$grep=$unix->find_program("grep");
	exec("$iptables_save|grep ArticaSquidTransparent 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}

function articadb_version(){
	
	$ArticaDBPath=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	
	if(!is_file("$ArticaDBPath/VERSION")){
		echo "<articadatascgi>0.000</articadatascgi>";
		return;
	}
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY")){
		@chmod("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY",0755);
	}
	
	echo "<articadatascgi>". @file_get_contents("$ArticaDBPath/VERSION")."</articadatascgi>";
}
function articadb_checkversion(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$results[]="$php5 /usr/share/artica-postfix/exec.squid.blacklists.php --get-version --verbose 2>&1";
	exec("$php5 /usr/share/artica-postfix/exec.squid.blacklists.php --get-version --verbose 2>&1",$results);	
	echo "<articadatascgi>".base64_encode(@implode("<br>\n",$results))."</articadatascgi>";
}
function articadb_nextversion(){
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY")){
		echo "<articadatascgi>". @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY")."</articadatascgi>";
	}
}
function articadb_progress(){
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress"));
	$downl=trim(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.download"));
	$array["DOWN"]=$downl;
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
}

function articadb_update_now(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --v2 --checktime --noiso >/dev/null 2>&1 &");		
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
}

function articadb_size(){
	$unix=new unix();
	$du=$unix->find_program("du");
	$ArticaDBPath=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$cmd="$du -hs $ArticaDBPath/data 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec("$cmd",$results);
	preg_match("#^(.+?)\s+#", $results[0],$re);
	echo "<articadatascgi>".$re[1]."</articadatascgi>";
}

function stats_members_generic(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --members-central-grouped >/dev/null 2>&1 &");		
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
}

function summarize_day(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.stats.php --summarize-daysingle {$_GET["summarize-day"]} {$_GET["tablename"]} --verbose");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	exec("$cmd",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function statistics_weekdaynum(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.stats.php --weekdaynum >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);	
}

function run_report(){
	$ID=$_GET["run-report"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.reports.php --ID $ID  >/dev/null 2>&1 &");		
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);	
}

function ufdbguard_restart_tenir(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	squid_admin_mysql(2, "Framework ask to reload the Web filtering service","");
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --reload-ufdb");
	shell_exec("$nohup /etc/init.d/ufdb-tail restart >/dev/null 2>&1");
	
}
function articadb_next_check(){
	$timeFile="/etc/artica-postfix/pids/exec.squid.blacklists.php.updatev2.time";
	$unix=new unix();
	$CHECKTIME=$unix->file_time_min($timeFile)-2880;
	echo "<articadatascgi>$CHECKTIME</articadatascgi>";
	
	
}
function mib(){
	$datas=base64_encode(@file_get_contents("/usr/share/squid3/mib.txt"));
	echo "<articadatascgi>$datas</articadatascgi>";
}
function watchdog_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	if(!isset($_GET["rp"])){$rp=50;}else{$rp=$_GET["rp"];}
	$cmdline="$tail -n $rp /var/log/squid.watchdog.log";
	if($_GET["search"]<>null){
		$grep=$unix->find_program("grep");
		$_GET["search"]=base64_decode($_GET["search"]);
		$cmdline="$grep -i -E '{$_GET["search"]}' /var/log/squid.watchdog.log|$tail -n $rp";
	}
	
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmdline 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function watchdog_auth(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	if(!isset($_GET["rp"])){$rp=50;}else{$rp=$_GET["rp"];}
	$file="/var/log/squid/externalAcl{$_GET["ID"]}Auth.log";
	writelogs_framework("$tail -n $rp $file",__FUNCTION__,__FILE__,__LINE__);
	exec("$tail -n $rp $file 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}


function StorageCapacity(){
	$unix=new unix();
	
	$results=explode("\n",$unix->squidclient("storedir"));
	
	while (list($num,$val)=each($results)){
	
	if(preg_match("#Current Capacity\s+:.*?([0-9\.]+)% used, ([0-9\.]+)% free#", $val,$re)){
	writelogs_framework("OK: $val",__FUNCTION__,__FILE__,__LINE__);
	$rr[]=$re[1];
	continue;
	}
	
	
	}
	echo "<articadatascgi>". base64_encode(serialize($rr))."</articadatascgi>";	
}

function ActiveRequestsNumber(){
	$unix=new unix();
	$results=explode("\n",$unix->squidclient("active_requests"));
	while (list($num,$val)=each($results)){
	
	if(preg_match("#nrequests:\s+([0-9\.]+)#", $val,$re)){
	$nrequests=$nrequests+intval($re[1]);
	continue;
	}
	
	
	}
	echo "<articadatascgi>$nrequests</articadatascgi>";	
	
}

function CounterInfos(){
	$unix=new unix();
	$results=explode("\n",$unix->squidclient("info"));
	
	while (list($num,$val)=each($results)){
	
		if(preg_match("#Total accounted.*?([0-9\.]+)%#", $val,$re)){
			$rr["TotalAccounted"]=$re[1];
			continue;
		}
		if(preg_match("#Maximum number of file descriptors.*?([0-9]+)#", $val,$re)){
			$rr["MAXFD"]=$re[1];
			continue;
		}
		
		if(preg_match("#Number of file desc currently in use.*?([0-9]+)#", $val,$re)){
			$rr["CURFD"]=$re[1];
			continue;
		}		
	
	}
	echo "<articadatascgi>". base64_encode(serialize($rr))."</articadatascgi>";	
}

function fivemncounter(){
	$unix=new unix();
	$results=explode("\n",$unix->squidclient("5min"));
	
	while (list($num,$val)=each($results)){
		if(preg_match("#^([a-z\.\_]+).*?=\s+(.+)#",$val,$re)){
			if(preg_match("#(.*?)\/#",$re[2],$ri)){$re[2]=$ri[1];}
			$re[2]=str_replace("%", "", $re[2]);
			$ARR[$re[1]]=$re[2];
		}
		
	}
	echo "<articadatascgi>". base64_encode(serialize($ARR))."</articadatascgi>";
}



function dynamic_groups_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	if(!isset($_GET["rp"])){$rp=50;}else{$rp=$_GET["rp"];}
	exec("$tail -n $rp /var/log/squid/external-acl.log 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}

function isufdbguard_squidconf(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list($num,$val)=each($f)){
		if(preg_match("#ufdbgclient#i", $val)){
			writelogs_framework("$val -> OK",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>OK</articadatascgi>";
			return;
		}
		
	}
	writelogs_framework("/etc/squid3/squid.conf -> BAD",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi></articadatascgi>";
	
}

function squid_logs_stats(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.cache.pages.php --squidlogs --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function exec_squid_rebuild_cache_mem(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$cmd="$pgrep -l -f exec.squid.rebuild.caches.php 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	while (list($num,$val)=each($results)){
		if(preg_match("#pgrep#", $val)){continue;}
		if(preg_match("#([0-9]+)\s+.*?rebuild\.caches\.php#", $val,$re)){
			$arr["TIME"]=str_replace("uptime=", "", $unix->PROCESS_UPTIME($re[1]));
			$arr["PID"]=$re[1];
			writelogs_framework("{$arr["PID"]} {$arr["TIME"]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(serialize($arr))."</articadatascgi>";
			return;
		}
		
	}
	
	
}
function currentusersize_array(){
	$RTTSIZEPATH="{$GLOBALS["ARTICALOGDIR"]}/squid-RTTSize/".date("YmdH");
	echo "<articadatascgi>". base64_encode(@file_get_contents($RTTSIZEPATH))."</articadatascgi>";
}
function test_smtp_watchdog(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --tests-smtp  >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function link_csv(){
	$aclid=$_GET["link-csv"];
	$path="/var/log/squid/access_acl_$aclid.csv";
	$dest="/usr/share/artica-postfix/ressources/logs/web/access_acl_$aclid.csv";
	$unix=new unix();
	$cp=$unix->find_program("cp");
	@unlink($dest);
	$apache=$unix->APACHE_SRC_ACCOUNT();
	$cmd="$cp -f $path $dest";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($path,0777);
	@chown($dest, $apache);
	chgrp($dest, $unix->APACHE_SRC_GROUP());
	
}

function build_whitelist(){
	@unlink("/usr/share/artica-postfix/ressources/logs/squid.wb.progress");
	@touch("/usr/share/artica-postfix/ressources/logs/squid.wb.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/squid.wb.progress",0755);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt");
	@touch("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt", 0755);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-whitelists >/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function build_blacklist(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/squid.wb.progress");
	@touch("/usr/share/artica-postfix/ressources/logs/squid.wb.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/squid.wb.progress",0755);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt");
	@touch("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt", 0755);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-whitelists >/usr/share/artica-postfix/ressources/logs/web/squid.wb.txt 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function build_whitelist_tenir(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build-whitelists 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
}

function caches_smp_create(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.smp.php --caches 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function restart_squid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --reconfigure --framework >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	$cmd=trim("$nohup /etc/init.d/artica-status reload  >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function kav4_proxy_templates(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kav4proxy.php --templates  >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function prepare_build(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build --noapply 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function prepare_build_tests(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --check-temp 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}


function import_squid_conf(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$file=$_GET["import-squid-conf"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.import.conf.php --import \"$file\" 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function import_acls(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$file=$_GET["import-acls"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --import-acls \"$file\" 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function import_webfiltering(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$file=$_GET["import-webfiltering-rules"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --import-webfilter \"$file\" 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function rotate_restore(){
	$unix=new unix();
	$filename=$_GET["rotate-restore"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$EnableSyslogDB=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	$storeid=$_GET["storeid"];
	
	if($EnableSyslogDB==1){
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.restoresrc.php --restore $filename $storeid >/dev/null 2>&1 &");
		return;
		
	}
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.restoresrc.php --restore $filename >/dev/null 2>&1 &");
	
	
}
function saveSquidContent(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
		
		echo "<articadatascgi>". base64_encode("FATAL NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.conf");
	@file_put_contents("/etc/squid3/squid.user.conf", $datas);
	@chown("/etc/squid3/squid.user.conf", "squid");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.user.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.user.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
		
		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}		
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.user.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return;
		}

	}
	
	@file_put_contents($SQUID_CONFIG_PATH, $datas);
	@file_put_contents("/etc/squid3/squid.conf", $datas);
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");
	
}

function squidhour_repair(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.hours.php --repair >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function rrd_perform(){
	return;
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid-rrd.php --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function squidhour_repair_executed(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.squid.stats.hours.php.repair_hours.pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$pidtime=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "<articadatascgi>$pidtime</articadatascgi>";
	}
		
}

function articadb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid-db.php --restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function artica_catz_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.catz-db.php --restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function squid_get_system_info(){
	$unix=new unix();
	
	$fileCache="/etc/squid3/squid_get_system_info.db";
	if($unix->file_time_min($fileCache)<10){
		$dats=unserialize(@file_get_contents($fileCache));
	}
	if(!is_array($dats)){$dats=array();}
	if(count($dats)<2){
		@unlink($fileCache);
		$dats=$unix->squid_get_system_info();
		@file_put_contents($fileCache,serialize($dats));
	}
	
	echo "<articadatascgi>". base64_encode(serialize($dats))."</articadatascgi>";
}

function squid_get_storage_info(){
	$unix=new unix();
	$dats=null;
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	if($unix->file_time_min($StoreDirCache)<10){
		$dats=unserialize(@file_get_contents($StoreDirCache));
	}
	
	
	if(!is_array($dats)){$dats=array();}
	if(count($dats)<1){
		$results=explode("\n",$unix->squidclient("storedir"));
		writelogs_framework("$StoreDirCache not an array  = ".count($results)." items...",__FUNCTION__,__FILE__,__LINE__);
		$dirs=0;
		while (list($num,$ligne)=each($results)){
			if(preg_match("#Current Capacity.*?:\s+([0-9\.]+)%\s+used#",$ligne,$re)){$CURCAP=trim($re[1]);continue;}
			if(preg_match("#Store Directory.*?:\s+(.+)#", $ligne,$re)){$StoreDir=trim($re[1]);$dirs++;continue;}
			if(preg_match("#Percent Used:\s+([0-9\.]+)%#", $ligne,$re)){$dats[$StoreDir]["PERC"]=$re[1];continue;}
			if(preg_match("#Maximum Size:\s+([0-9\.]+)#", $ligne,$re)){$dats[$StoreDir]["SIZE"]=$re[1];continue;}
			if(preg_match("#Shared Memory Cache#", $ligne)){$StoreDir="MEM";continue;}
			
			if(preg_match("#Current entries:\s+([0-9\.]+)\s+([0-9\.]+)%#",$ligne,$re)){
				$dats[$StoreDir]["ENTRIES"]=$re[1];
				$dats[$StoreDir]["PERC"]=$re[2];
			}


		}
		
		if($dirs==0){
			if($CURCAP<>null){
				$dats["CURCAP"]=$CURCAP;
			}
		}
		
		@unlink($StoreDirCache);
		if(is_array($dats)){
			writelogs_framework("Saving new array in $StoreDirCache",__FUNCTION__,__FILE__,__LINE__);
			file_put_contents($StoreDirCache, serialize($dats));
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($dats))."</articadatascgi>";
}

function MacToUid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.quotasbuild.php --macuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function reload_squid(){
	$unix=new unix();
	$php5=$this->LOCATE_PHP5_BIN();
	$nohup=$this->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function MacToUidStats(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --macuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function reverse_proxy_apply(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.nginx.php --restart >/dev/null 2>&1 &");
		
	}else{
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid-reverse.php --build >/dev/null 2>&1 &");
	}
	
	
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function  reload_unlock(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-whitelists --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function test_sarg(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --test-sarg >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function sarg_conf(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sarg.php --conf >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function sarg_restore(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$restoreid=$_GET["sarg-restore"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sarg.php --restore-id $restoreid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function cntlm_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --cntlm >/dev/null");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$cmd=trim("$nohup /etc/init.d/cntlm restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function reconstruct_caches(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/rebuild-cache.txt");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function restart_cache_tail(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/cache-tail >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}


function dump_peers(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.watchdog.php --peer-status --dump 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function current_version(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->squid_version())."</articadatascgi>";
	
}
function downgrade(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.downgrade.php \"{$_GET["downgrade"]}\" >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function squid_oldlogs_scan_nas(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.logs.import.php --scan --notime >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function squid_oldlogs_import_nas(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.logs.import.php --all --notime >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function purge_all_statistics(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidlogs.purge.php --remove-all >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function purge_numeric_members_statistics(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidlogs.purge.php --numeric-members >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function backup_all_statistics(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	writelogs_framework("**** backup_all_statistics ****",__FUNCTION__,__FILE__,__LINE__);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidlogs.purge.php --backup >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function ufdbclient_tests(){
	$unix=new unix();
	$cmdline=base64_decode($_GET["ufdbclient"]);
	
	$ufdbgclient=$unix->find_program("ufdbgclient");
	$cmd="$ufdbgclient $cmdline 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	writelogs_framework("$cmd ".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("", $results)))."</articadatascgi>";
		
}
function catzdb_changedir(){
	$unix=new unix();
	$dir=base64_decode($_GET["catzdb-changedir"]);
	@mkdir($dir,0755,true);
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.catz-db.php --changemysqldir \"$dir\"  >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function idns(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->squidclient("idns"))."</articadatascgi>";
	
}

function ipcache(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->squidclient("ipcache"))."</articadatascgi>";	
}
function purge_dns(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --purge-dns >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function caches_center(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --caches-center >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function show_content_group(){
	$groupid=$_GET["show-content-group"];
	writelogs_framework("/etc/squid3/acls/container_$groupid.txt -> get content",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/squid3/acls/container_$groupid.txt"))."</articadatascgi>";
}

function sarg_weekly(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/cron.weekly/0sarg.sh >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function sarg_monthly(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/cron.monthly/0sarg.sh >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function sarg_index(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.sarg.php --index >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function proxy_pack_debug(){
	$unix=new unix();
	$filename="/var/log/apache2/proxy.pack.debug";
	$searchstring=$_GET["searchstring"];
	$tail=$unix->find_program("tail");
	if($searchstring<>null){
		$datas=exec("$tail -n 500 /var/log/apache2/proxy.pack.debug 2>&1",$results);
		echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
		return;
	}
	
	$grep=$unix->find_program("grep");
	$datas=exec("$grep -E '$searchstring' /var/log/apache2/proxy.pack.debug|$tail -n 500 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}

function proxy_pack_debug_empty(){
	$unix=new unix();
	$filename="/var/log/apache2/proxy.pack.debug";
	$echo=$unix->find_program("echo");
	shell_exec("$echo \"\" >$filename 2>&1");
	
}
function proxy_pack_debug_compress(){
	$unix=new unix();
	$filename="/var/log/apache2/proxy.pack.debug";
	$unix->compress($filename, "/usr/share/artica-postfix/ressources/logs/web/proxy.pack.debug.gz");	
}
function kav4proxy_install(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.kav4Proxy.install.php --install >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function kav4proxy_uninstall(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.kav4Proxy.install.php --uninstall >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function squid_quick_ban(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --quick-ban >/dev/null 2>&1 &";
	writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function kav4proxy_is_installed(){
	if(is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager")){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
}





function remove_ssl_cert_default(){
	$unix=new unix();
	$commname=$unix->hostname_g();
	
	foreach (glob("/etc/squid3/ssl/*.dyn") as $filename) {
		@unlink($filename);
	}
	
	@unlink("/usr/share/artica-postfix/ressources/squid/certificate.der");
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(!isset($_GET["ssl-windows-gen"]))
	{	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --cert >/dev/null 2>&1 &";
	shell_exec($cmd);return;}
	
	$cmd="$php5 /usr/share/artica-postfix/exec.squid.php --cert 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
}




function UtDNSUpdate(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --update-unveiltech >/dev/null 2>&1 &";	
	
}

function UtDNSRegister(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-unveiltech 2>&1";	
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
}
function support_package_full(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@mkdir("/usr/share/artica-postfix/ressources/support",0777,true);
	@unlink("/usr/share/artica-postfix/ressources/support/support.tar.gz");
	@unlink("/usr/share/artica-postfix/ressources/support/support.progress");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.support.package.php >/dev/null 2>&1 &";
	shell_exec($cmd);
}
function request_package_full(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@mkdir("/usr/share/artica-postfix/ressources/support",0777,true);
	@unlink("/usr/share/artica-postfix/ressources/support/request.tar.gz");
	@unlink("/usr/share/artica-postfix/ressources/support/request.progress");
	$uri=$_GET["uri"];
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.request.package.php \"$uri\" >/dev/null 2>&1 &";
	shell_exec($cmd);	
	
}
function IS_ICAP_CLIENT(){
	$unix=new unix();
	if($unix->SQUID_ICAP_ENABLED()){
		echo "<articadatascgi>TRUE</articadatascgi>";
		return;
	}
	
}
function statistics_central_run(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.central.php >/dev/null 2>&1 &";
	shell_exec($cmd);
}

function active_requests(){
$unix=new unix();
$f=explode("\n",$unix->squidclient("active_requests"));

while (list ($mkey, $ligne) = each ($f) ){
	
	if(preg_match("#Connection:\s+(.+)#", $ligne,$re)){
		$CON=$re[1];
		$H["CON"][$re[1]]=true;
		continue;
	}
	
	if(preg_match("#remote:\s+([0-9\.]+):#", $ligne,$re)){
		$H[$CON]["IPS"][$re[1]]=true;
		$H["IPS"][$re[1]]=true;
		$H["ALL"][$re[1]]=true;
		continue;
	}
	
	if(preg_match("#username\s+(.+)#", $ligne,$re)){
		$H[$CON]["USERS"][$re[1]]=true;
		$H["USERS"][$re[1]]=true;
		$H["ALL"][$re[1]]=true;
		continue;
	}
	
}

@file_put_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc", serialize($H));
@chmod("/usr/share/artica-postfix/ressources/logs/active_requests.inc", 0755);
	
}

function IS_APP_SQUIDDB_INSTALLED(){
	$unix=new unix();
	if($unix->is_socket("/var/run/mysqld/squid-db.sock")){
		writelogs_framework("/var/run/mysqld/squid-db.sock socket OK",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>TRUE</articadatascgi>";return;}
		
		
	writelogs_framework("/var/run/mysqld/squid-db.sock socket FALSE",__FUNCTION__,__FILE__,__LINE__);
	if(is_dir("/opt/squidsql/data/squidlogs")){
		writelogs_framework("/opt/squidsql/data/squidlogs OK",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>TRUE</articadatascgi>";return;}
		
		
		writelogs_framework("/opt/squidsql/data/squidlogs no such dir",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>FALSE</articadatascgi>";
	
}
function cache_center_empty(){
	$unix=new unix();
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.progress");
	
	$file="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.progress";
	$ARRAY["TEXT"]="{waiting}";
	$ARRAY["POURC"]=5;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild.caches.php --empty {$_GET["cache-center-empty"]} >/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.txt 2>&1 &";
	shell_exec($cmd);	
}

function squid_nat_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --squid-nat --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}
function videocache_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --videocache --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}
function squid_nat_reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/init.d/squid-nat")){
		$cmd="$php5 /usr/share/artica-postfix/exec.initslapd.php --squid-nat";
		shell_exec($cmd);
	}
	shell_exec("/etc/init.d/squid-nat reload");
	
}
function squid_refresh(){
	$file="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
	$ARRAY["TEXT"]="{waiting}";
	$ARRAY["POURC"]=5;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$LOGSFILES="/usr/share/artica-postfix/ressources/logs/web/squid_reload.txt";
	@unlink($LOGSFILES);
	@touch($LOGSFILES);
	@chmod($LOGSFILES, 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --all-status --force --verbose >$LOGSFILES 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function videocache_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squidstream.php --restart --check >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function videocache_streamsquidcache(){
	$unix=new unix();
	if(is_file($unix->find_program("streamsquidcache"))){echo "<articadatascgi>TRUE</articadatascgi>";return;}
	echo "<articadatascgi>FALSE</articadatascgi>";
}
function videocache_query(){
	$preprend=$_GET["prepend"];
	
	$pattern=trim(base64_decode($_GET["videocache-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$logpath="/var/log/squid/videocache.log";
	$maxrows=0;
	
	$unix=new unix();
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){
		writelogs_framework("TAIL = NULL !!!" ,__FUNCTION__,__FILE__,__LINE__);
		return;}
	
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		$grep="$grepbin -i -E '$pattern'";
	}
	
	unset($results);
	$l=$unix->FILE_TEMP();
	
	if($grep<>null){
		$cmd="$tail -n 5000 $logpath|$grep|$tail -n $maxrows 2>&1";
	}else{
		$cmd="$tail -n $maxrows $logpath 2>&1";
	}
	
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(count($results)<3){
		$maxrows=$maxrows+2000;
		if($grep<>null){
			$cmd="$tail -n 5000 $logpath|$grep |$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $logpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
	}
	
	if(count($results)<3){
		$maxrows=$maxrows+5000;
		if($grep<>null){
			$cmd="$grep $logpath|$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $logpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
	}
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/videocache-query", @implode("\n", $results));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/videocache-query", 0755);	
	
	
}
function realtime_logs_parse(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.logfile_daemon-parse.php >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function videocache_reinstall(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squidstream.php --reinstall >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function loggers_status(){
	$unix=new unix();
	
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"exec.logfile_daemon.php\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$PID=$re[1];
		$PID_LIST[$re[1]]=true;
		
		$timettl=$unix->PROCCESS_TIME_MIN($PID);
		$statusfile="/var/run/squid/exec.logfilefile_daemon.$PID.state";
		$timefile=filemtime($statusfile);
		$statusHash=unserialize(@file_get_contents($statusfile));
		$ARRAY[$PID]["TTL"]=$timettl;
		$ARRAY[$PID]["PURGED"]=$statusHash["PURGED"];
		$ARRAY[$PID]["COUNT_RQS"]=$statusHash["COUNT_RQS"];
		$ARRAY[$PID]["LASTTIME"]=$timefile;
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";
	
}
function squid_conf_copy(){
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid.conf")){
		@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.conf");
	}
	@copy("/etc/squid3/squid.conf","/usr/share/artica-postfix/ressources/logs/web/squid.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.conf",0755);
	$unix=new unix();
	@chown("/usr/share/artica-postfix/ressources/logs/web/squid.conf",$unix->APACHE_SRC_ACCOUNT());
	
}

function icap_clients(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.watchdog.php --icap >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/access.log.tmp";
	$rp=$_GET["rp"];
	$query=$_GET["query"];
	$cmd="$tail -n $rp /var/log/squid/access.log  >$targetfile 2>&1";
	
	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){
		$grep=$unix->find_program("grep");
		$cmd="$grep -E \"$pattern\" /var/log/squid/access.log | $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod(0755,"$targetfile");
	
	
	
	
	
	
}



?>