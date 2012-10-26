<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["build-smooth"])){build_smooth();exit;}
if(isset($_GET["build-smooth"])){build_smooth();exit;}
if(isset($_GET["rethumbnail"])){rethumbnail();exit;}
if(isset($_GET["access-logs"])){access_logs();exit;}
if(isset($_GET["accesslogs"])){accesslogs();exit;}
if(isset($_GET["reprocess-database"])){community_reprocess_category();exit();}
if(isset($_GET["kav4proxy-update-now"])){kav4proxy_update();exit();}
if(isset($_GET["categorize-tests"])){categorize_test();exit;}
if(isset($_GET["update-database-blacklist"])){blacklist_update();exit();}
if(isset($_GET["compil-params"])){compile_params();exit();}
if(isset($_GET["migration-stats"])){migration_stats();exit();}
if(isset($_GET["re-categorize"])){re_categorize();exit();}
if(isset($_GET["kav4proxy-license-error"])){kav4proxy_license_error();exit();}
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



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function access_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$search=$_GET["search"];
	if(strlen($search)>1){
		$search=str_replace("*", ".*", $search);
		$cmd="$tail -n 1000 /var/log/squid/access.log|$grep -E \"$search\" 2>&1";
	}else{
		$cmd="$tail -n 500 /var/log/squid/access.log 2>&1";
	}
	
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
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
	
function recategorize_day(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --re-categorize-day {$_GET["recategorize-day"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function NoCategorizedAnalyze(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --calculate-not-categorized >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function watchdog_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
		
}

function build_schedules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart watchdog >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function run_schedules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --run-schedules {$_GET["run-scheduled-task"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);			
}


function recategorize_week(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --re-categorize-week {$_GET["recategorize-week"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}
function cron_tail_injector_plus(){
	cron_tail_injector_moins();
}
function cron_tail_injector_moins(){
	@unlink("/etc/cron.d/SquidTailInjector");
}

	
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
 

function community_reprocess_category(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --reprocess-database  {$_GET["reprocess-database"]} >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function recategorize_task(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.fcron.php --squid-recategorize-task >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function categorize_test(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.categorize-tests.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function export_web_categories(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --export-perso-cats >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function export_deleted_categories(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --export-deleted >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}


function migration_stats(){
	$unix=new unix();
	if(is_file("/etc/artica-postfix/exec.squid.logs.migrate.php.pid")){
		$pid=$unix->get_pid_from_file("/etc/artica-postfix/exec.squid.logs.migrate.php.pid");
		if(is_numeric($pid)){
			if($unix->process_exists($pid)){
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
			if($unix->process_exists($pid)){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				echo "<articadatascgi>". base64_encode(serialize(array($pid,$time)))."</articadatascgi>";
			}
		}
	}		
}

function compile_params(){
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --compilation-params");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function kav4proxy_configure(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.kav4proxy.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
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
		$cmd="$grep -E '$search' /var/log/UpdateUtility/access.log|$tail -n $max 2>&1";
		
		
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
		$cmd="$grep -E '$search' /var/log/squid/ufdbguardd.log|$tail -n $max 2>&1";
		
		
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



function kav4proxy_license_error(){
	$unix=new unix();
	
	$cmd=trim("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -i 2>&1");
	writelogs_framework("$cmd = ".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
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
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --visited-sites >/dev/null 2>&1 &");
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
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category $database >/dev/null 2>&1 &");
	shell_exec($cmd);
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
	$caches["ufs"]="ufs";
	while (list ($num, $ligne) = each ($results) ){	
		if(!preg_match("#--enable-storeio=(.+?)'#", $ligne,$re)){
			writelogs_framework("$num) $ligne no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		$list=explode(",", $re[1]);
		while (list ($a, $b) = each ($list) ){
			$b=trim($b);
			if($b==null){continue;}
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
	
	$search=str_replace(".","\.",$search);
	$search=str_replace("*",".*?",$search);
	$search=str_replace("(","\(",$search);
	$search=str_replace(")","\)",$search);
	$search=str_replace("[","\[",$search);
	$search=str_replace("]","\]",$search);
	
	$cmd="$grep -E '$search' /var/log/squid/cache.log 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec("$cmd",$results);
	
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
		
		$cmd="$grep -E 'squid\[$OnlyIpAddr' /var/log/auth.log|$tail -n $rp 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
		exec("$cmd",$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=str_replace(".","\.",$search);
	$search=str_replace("*",".*?",$search);
	$search=str_replace("(","\(",$search);
	$search=str_replace(")","\)",$search);
	$search=str_replace("[","\[",$search);
	$search=str_replace("]","\]",$search);
	
	$cmd="$grep -E 'squid\[' /var/log/auth.log 2>&1|$grep -E '$search' 2>&1|$tail -n $rp 2>&1";
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
	exec("$php5 /usr/share/artica-postfix/exec.kerbauth.php --build 2>&1",$results);
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
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --smooth-build >/dev/null &");
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
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.usrmactranslation.php >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");	
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
				if(preg_match("#(.*?)=(.*)#", $b,$re)){$newArray[trim($re[1])]=trim($re[2]);continue;}
				$newArray[$b]=null;
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
	$cmd="$nohup $purge purge -c /etc/squid3/squid.conf -e \"$sitename\" -P 1 >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}

function support_step1(){
	$unix=new unix();
	$ps=$unix->find_program("ps");
	$df=$unix->find_program("df");
	$files[]="/etc/hostname";
	$files[]="/etc/resolv.conf";
	$files[]="/usr/share/artica-postfix/ressources/settings.inc";
	$files[]="/usr/share/artica-postfix/ressources/logs/global.status.ini";						     
	$files[]="/usr/share/artica-postfix/ressources/logs/global.versions.conf";
	
	if(is_dir("/usr/share/artica-postfix/ressources/support")){
		shell_exec("/bin/rm -rf /usr/share/artica-postfix/ressources/support");
	}
	
	@mkdir("/usr/share/artica-postfix/ressources/support",0755,true);
	while (list ($a, $b) = each ($files) ){	
		$destfile=basename($b);
		writelogs_framework("cp $b -> support/$destfile",__FUNCTION__,__FILE__,__LINE__);		
		@copy($b, "/usr/share/artica-postfix/ressources/support/$destfile");
	}
	
	shell_exec("$ps aux >/usr/share/artica-postfix/ressources/support/ps.txt 2>&1");
	shell_exec("$df -h >/usr/share/artica-postfix/ressources/support/dfh.txt 2>&1");
	writelogs_framework("DONE...",__FUNCTION__,__FILE__,__LINE__);
}
function support_step2(){
	
	$files[]="/var/log/squid/cache.log";
	$files[]="/var/log/syslog";
	$files[]="/var/log/messages";
	$files[]="/var/log/auth.log";
	$files[]="/var/log/mail.log";
	$files[]="/var/log/squid/ufdbguardd.log";						     
	$files[]="/var/log/samba/log.winbindd";
	$files[]="/etc/samba/smb.conf";
	$files[]="/var/log/samba/log.nmbd";
	$files[]="/var/log/samba/log.smbd";
	$files[]="/var/run/mysqld/mysqld.err";
	$unix=new unix();
	$cp=$unix->find_program("cp");
	
	if(is_dir("/etc/squid3")){
		@mkdir("/usr/share/artica-postfix/ressources/support/etc-squid3",0755,true);
		$cmd="/bin/cp -rf /etc/squid3/* /usr/share/artica-postfix/ressources/support/etc-squid3/";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$cmd");
	}
	if(is_dir("/etc/postfix")){
		@mkdir("/usr/share/artica-postfix/ressources/support/etc-postfix",0755,true);
		$cmd="/bin/cp -rf /etc/postfix/* /usr/share/artica-postfix/ressources/support/etc-postfix/";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$cmd");
	}	
	
	
	while (list ($a, $b) = each ($files) ){	
		if(is_file($b)){
			$destfile=basename($b);
			writelogs_framework("$cp $b -> support/$destfile",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$cp $b /usr/share/artica-postfix/ressources/support/$destfile");
		}else{
			writelogs_framework("$b no such file",__FUNCTION__,__FILE__,__LINE__);	
		}
	}
	writelogs_framework("Generate versions",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$uname=$unix->find_program("uname");
	$results[]="$uname -a:";
	exec("$uname -a 2>&1",$results);
	$results[]="\n";
	$results[]="/bin/bash --version:";
	exec("/bin/bash --version 2>&1",$results);
	
	$results[]="\n";
	
	$gdb=$unix->find_program("gdb");
	if(is_file($gdb)){
		$results[]="$gdb --version:";
		exec("$gdb --version 2>&1",$results);
	}else{
		$results[]="gdb no such binary....";
	}
	$results[]="\n";
	$smbd=$unix->find_program("smbd");
	if(is_file($smbd)){
		$results[]="$smbd -V:";
		exec("$smbd -V 2>&1",$results);
	}else{
		$results[]="smbd no such binary....";
	}
	
	$results[]="\n";
	$squidbin=$unix->find_program("squid");
	if(is_file($squidbin)){
		$results[]="$squidbin -v:";
		exec("$squidbin -v 2>&1",$results);
		exec("$squidbin -k reconfigure 2>&1",$results);
	}else{
		$results[]="squid no such binary....";
	}	
	$results[]="\n";
	$squidbin=$unix->find_program("squid3");
	if(is_file($squidbin)){
		$results[]="$squidbin -v:";
		exec("$squidbin -v 2>&1",$results);
		exec("$squidbin -k reconfigure 2>&1",$results);
	}else{
		$results[]="squid3 no such binary....";
	}
	$results[]="\n";
	$df=$unix->find_program("df");
	if(is_file($df)){
		$results[]="$df -h:";
		exec("$df -h 2>&1",$results);
	}else{
		$results[]="$df no such binary....";
	}	
	writelogs_framework("Generate versions done ".count($results)." elements",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/support/generated.versions.txt", @implode("\n", $results));
	writelogs_framework("DONE...",__FUNCTION__,__FILE__,__LINE__);
}
function support_step3(){
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$filename="support.tar.gz";
	chdir("/usr/share/artica-postfix/ressources/support");
	$cmd="$tar -cvzf /usr/share/artica-postfix/ressources/support/$filename * 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	while (list ($a, $b) = each ($results) ){	
		writelogs_framework($b,__FUNCTION__,__FILE__,__LINE__);
	}
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
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --build --verbose >/usr/share/artica-postfix/ressources/logs/web/squid.indebug.log 2>&1");	
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
function update_blacklist(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --v2 >/dev/null 2>&1 &");	
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}


function squidclient_infos(){
	$unix=new unix();
try {
		$builded=$unix->squidclient_builduri();
	} catch (Exception $e) {
		writelogs_framework("Fatal: ".$e->getMessage(),__FUNCTION__,__LINE__);
		echo "<articadatascgi>".base64_decode(serialize(array()))."</articadatascgi>";
		return;
	}
	
	$cmd="$builded:info 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);	
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
	try {
		$builded=$unix->squidclient_builduri();
	} catch (Exception $e) {
		writelogs_framework("Fatal: ".$e->getMessage(),__FUNCTION__,__LINE__);
		echo "<articadatascgi>".base64_decode(serialize(array()))."</articadatascgi>";
		return;
	}
	
	$cmd="$builded:active_requests";
	exec($cmd,$results);
	
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
	if(!is_file("/opt/articatech/VERSION")){
		echo "<articadatascgi>0.000</articadatascgi>";
		return;
	}
	echo "<articadatascgi>". @file_get_contents("/opt/articatech/VERSION")."</articadatascgi>";
}

function stats_members_generic(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.php --members-central-grouped >/dev/null 2>&1 &");		
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
}