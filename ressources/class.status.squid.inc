<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
include_once(dirname(__FILE__)."/class.squid.watchdog.inc");
function squid_increment_func($array){
	
	$array[]="adker_watchdog";
	$array[]="squid_nat";
	$array[]="squid_db";
	$array[]="squid_tail";
	$array[]="squid_master_status";
	$array[]="squid_clamav_tail";
	$array[]="squid_cache_tail";
	$array[]="c_icap_master_status";
	$array[]="hotspot_fw";
	$array[]="hotspot_web";
	$array[]="cntlm";
	$array[]="cntlm_parent";
	$array[]="ufdbguardd_tail";
	$array[]="ufdbguardd";
	$array[]="ufdbguardd_client";
	$array[]="squidguardweb";
	$array[]="squidguard_logger";
	
	$array[]="ziproxy";
	$array[]="articadb";
	$array[]="kav4Proxy_keepup2date";
	$array[]="kav4Proxy_status";
	$array[]="ufdbcat";
	$array[]="articadb";
	$array[]="HyperCacheWeb";
	$array[]="HyperCacheStoreID_client";
	$array[]="iptables_transparent";
	$array[]="hypercache_logger";
	$array[]="wanproxy_parent";
	$array[]="wanproxy_childs";
	
	return $array;
}



function zipproxy_pid(){
	
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/ziproxy.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}
function zipproxy_version(){
	
	if(isset($GLOBALS["zipproxy_version"])){return $GLOBALS["zipproxy_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Ziproxy\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["zipproxy_version"]=trim($re[1]);
			return $GLOBALS["zipproxy_version"];
		}
	}
}


function adker_watchdog(){
	$ENABLED=true;
	$sock=new sockets();
	if(!$sock->IsKerbAuth()){$ENABLED=false;}
	$watchdog=new squid_watchdog();
	$MonitConfig= $watchdog->MonitConfig;
		
	if($MonitConfig["CHECK_AD"]==0){$ENABLED=false;}
		
	
	
	
	if(!$ENABLED){
		if(is_file("/etc/cron.d/artica-ads-watchdog")){
			@unlink("/etc/cron.d/artica-ads-watchdog");
			shell_exec("/etc/init.d/cron reload");
			
		}
	
		return;
	}
	
	if(!is_file("/etc/cron.d/artica-ads-watchdog")){
		Popuplate_cron_make("artica-ads-watchdog","* * * * *","exec.kerbauth.watchdog.php");
		shell_exec("/etc/init.d/cron reload");
	}
	
}



function ziproxy(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('ziproxy');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "ziproxy no such binary.\n";}return;}
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	if($SquidAsMasterPeer==0){$EnableProxyCompressor=0;}
	$l[]="";
	$l[]="[APP_ZIPROXY]";
	$l[]="service_name=APP_ZIPROXY";
	$l[]="service_cmd=/etc/init.d/zipproxy";
	$l[]="master_version=".zipproxy_version();
	$l[]="service_disabled=$EnableProxyCompressor";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	if($EnableProxyCompressor==0){return implode("\n",$l);return;}	
	$master_pid=zipproxy_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			if(!is_file("/etc/init.d/zipproxy")){
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --zipproxy >/dev/null 2>&1");
			}
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/zipproxy start >/dev/null 2>&1 &");
		
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
	
}
function wanproxy_childs(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('wanproxy');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "wanproxy no such binary.\n";}return;}
	if(!is_file("/etc/init.d/wanproxy-childs")){return;}


	$l[]="";
	$l[]="[APP_WANPROXY]";
	$l[]="service_name=APP_WANPROXY";
	$l[]="service_cmd=/etc/init.d/wanproxy-childs";
	$l[]="master_version=1.0";
	$l[]="service_disabled=1";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	$l[]="installed=1";

	$master_pid=wanproxy_pid("wanproxy-client.conf");

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			if(!is_file("/etc/init.d/wanproxy-childs")){return;}
			squid_admin_mysql(0, "Fatal: Wan Proxy compressor (child) is not running [action=start]", null,__FILE__,__LINE__);
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/wanproxy-childs start >/dev/null 2>&1 &");

		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}

function wanproxy_parent(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('wanproxy');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "wanproxy no such binary.\n";}return;}
	if(!is_file("/etc/init.d/wanproxy-parent")){return;}
	
	
	$l[]="";
	$l[]="[APP_WANPROXY]";
	$l[]="service_name=APP_WANPROXY";
	$l[]="service_cmd=/etc/init.d/wanproxy-parent";
	$l[]="master_version=1.0";
	$l[]="service_disabled=1";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	
	$master_pid=wanproxy_pid("wanproxy-parent.conf");
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			if(!is_file("/etc/init.d/wanproxy-parent")){return;}
			squid_admin_mysql(0, "Fatal: Wan Proxy compressor (parent) is not running [action=start]", null,__FILE__,__LINE__);
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/wanproxy-parent start >/dev/null 2>&1 &");
	
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
	
	
}
function wanproxy_pid($conffile){
	$daemonbin=$GLOBALS["CLASS_UNIX"]->find_program("wanproxy");
	if($conffile==null){
		return $GLOBALS["CLASS_UNIX"]->PIDOF($daemonbin);
	}
	$daemonbin=basename($daemonbin);
	$conffile=str_replace(".", "\.", $conffile);
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$daemonbin.*?-c.*?$conffile");	
	
}


function squidguard_logger(){



	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("squidGuard");
	if($bin_path==null){return null;}
	$pid_path="/etc/artica-postfix/exec.squidguard-tail.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="";
	$l[]="[APP_ARTICA_SQUIDGUARDTAIL]";
	$l[]="service_name=APP_ARTICA_SQUIDGUARDTAIL";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=squiguard-tail";
	$l[]="service_disabled=1";
	$l[]="family=squid";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="installed=1";

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/dnsmasq start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}


function hypercache_logger(){

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidUrgency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency");
	$HyperCacheStoreID=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID");
	$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
	
	if($SquidUrgency==1){$HyperCacheStoreID=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$HyperCacheStoreID=0;}
	if($SquidPerformance>1){$HyperCacheStoreID=0;}
	$pid_path="/etc/artica-postfix/exec.hypercache-tail.php.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	
	$l[]="";
	$l[]="[APP_HYPERCACHE_TAIL]";
	$l[]="service_name=APP_HYPERCACHE_TAIL";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=/etc/init.d/hypercache-tail";
	$l[]="service_disabled=$HyperCacheStoreID";
	$l[]="family=squid";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	
	if($HyperCacheStoreID==0){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/hypercache-tail stop >/dev/null 2>&1 &");
		}
		return implode("\n",$l);
	}
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		
		if(!is_file("/etc/init.d/hypercache-tail")){
			shell_exec2("$nohup {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --hypercache-tail >/dev/null 2>&1 &");
			
		}
		
		
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.init-tail-hypercache.php --start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	return implode("\n",$l);	
	
	
}


function squid_tail_pid(){
	
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/bin/access-tail");
	squid_watchdog_events("/bin/access-tail = $pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){return 0;}
	
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("exec.logfile_daemon.php");
	squid_watchdog_events("squid_tail_pid PIDOF_PATTERN = $pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){return 0;}
	return $pid;
}


function squid_tail(){
	
	$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
	$SquidPerformance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	
	$EnableInfluxDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInfluxDB"));
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}
	
	
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=1;
	if($SQUIDEnable==0){$enabled=0;}
	if($SquidPerformance>1){$enabled=0;}
	if($InfluxSyslogRemote==1){$enabled=0;}
	if($EnableInfluxDB==0){$enabled=0;}
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$enabled=1;}
	
	
	
	if(!preg_match("#^2\.#", $ARTICA_VERSION)){
		squid_watchdog_events("[squid_tail]: $ARTICA_VERSION -> No match");
		return;
	}
	
	
	$l[]="";
	$l[]="[APP_ARTICA_SQUID_TAIL]";
	$l[]="service_name=APP_ARTICA_SQUID_TAIL";
	$l[]="master_version=$ARTICA_VERSION";
	$l[]="service_cmd=/etc/init.d/squid-tail";
	$l[]="service_disabled=$enabled";
	$l[]="family=squid";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	
	$master_pid=squid_tail_pid();
	
	squid_watchdog_events("[squid_tail]: PID = {$master_pid} ENABLED=$enabled");
	
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		squid_watchdog_events("[squid_tail]: $master_pid Not running !!!");
		if($enabled==1){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				squid_watchdog_events("[squid_tail]: Not running start it");
				squid_admin_mysql(1, "Squid-tail not running [action=start]", null,__FILE__,__LINE__);
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-tail start >/dev/null 2>&1 &");
			}
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
		
	}else{
		if($enabled==0){
			
			squid_watchdog_events("[squid_tail]: Running stop it");
			squid_admin_mysql(1, "Squid-tail running but disabled [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-tail stop >/dev/null 2>&1 &");
		}
	}
	
	$unix=new unix();
	$timeSec=$unix->file_time_sec("/etc/artica-postfix/cache-tail.time");
	squid_watchdog_events("[squid_tail]: cache-tail.time = {$timeSec} seconds");
	
	
	
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	if($timeSec>10){
		squid_watchdog_events("[squid_tail]: Time out -> Restart");
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-tail restart >/dev/null 2>&1 &");
	}
	return implode("\n",$l);return;
	
	
}
function squidguardweb_version(){
	if(isset($GLOBALS["lighttpd_version"])){return $GLOBALS["lighttpd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	exec("$bin_path -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#lighttpd.*?([0-9\.]+)#", $line,$re)){
			$GLOBALS["lighttpd_version"]=$re[1];
			return $GLOBALS["lighttpd_version"];
		}
	}
}




function squidguardweb(){
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	$pid_path="/var/run/lighttpd/squidguard-lighttpd.pid";
	if(is_file($pid_path)){$master_pid=trim(@file_get_contents($pid_path));}
	if($master_pid==null){
		$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/artica-postfix/squidguard-lighttpd.conf");
		if($master_pid<>null){@file_put_contents("/var/run/lighttpd/squidguard-lighttpd.pid",$master_pid);}
	}

	$squidGuardEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("squidGuardEnabled");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard());
	$EnableSquidClamav=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidClamav");
	$EnableSquidGuardHTTPToArtica=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardHTTPToArtica"));
	
	if($EnableUfdbGuard==1){$squidGuardEnabled=1;}
	if($EnableSquidClamav==1){$squidGuardEnabled=1;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$EnableSquidGuardHTTPService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardHTTPService");
	if($EnableSquidGuardHTTPService==null){$EnableSquidGuardHTTPService=1;}
	if($EnableSquidGuardHTTPService<>1){$squidGuardEnabled=0;}
	if($EnableSquidGuardHTTPToArtica==1){$squidGuardEnabled=0;}
	
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard -> $EnableUfdbGuard\n";}

	if($GLOBALS["CLASS_UNIX"]->Ufdbguard_remote_srvc_bool()){
		$squidGuardEnabled=0;
	}else{
		if($GLOBALS["VERBOSE"]){echo "Ufdbguard_remote_srvc_bool -> FALSE\n";}
	}

	if($SQUIDEnable==0){$squidGuardEnabled=0;}
	if($squidGuardEnabled==null){$squidGuardEnabled=0;}
	if($EnableWebProxyStatsAppliance==1){$squidGuardEnabled=1;}
	if($SquidPerformance>2){$squidGuardEnabled=0;}
	$l[]="";
	$l[]="[APP_SQUIDGUARD_HTTP]";
	$l[]="service_name=APP_SQUIDGUARD_HTTP";
	$l[]="master_version=".squidguardweb_version();
	$l[]="service_cmd=/etc/init.d/squidguard-http";
	$l[]="service_disabled=$squidGuardEnabled";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	$l[]="pid_path=$pid_path";

	if($squidGuardEnabled==0){
		return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --squidguard-http >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squidguard-http restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function ufdbcatVersion(){
	if(isset($GLOBALS["ufdbcatVersion"])){return $GLOBALS["ufdbcatVersion"];}
	exec("/opt/ufdbcat/bin/ufdbcatdd -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){$GLOBALS["ufdbcatVersion"]= $re[1];return $GLOBALS["ufdbcatVersion"];}
	}
}


function iptables_transparent(){
	
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		$l[]="";
		$l[]="[APP_TRANSPARENT_ROUTING]";
		$l[]="service_name=APP_TRANSPARENT_ROUTING";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}
	
	if(!is_file("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT")){
		return;
	}
	
	$l[]="\n[APP_TRANSPARENT_ROUTING]";
	$l[]="service_name=APP_TRANSPARENT_ROUTING";
	$ports=intval(@file_get_contents("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT"));
	if($ports==0){
		$l[]="installed=1";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}

	$l[]="installed=1";
	$l[]="service_disabled=1";
	$l[]="master_version=".iptables_version();
	$l[]="service_cmd=/etc/init.d/iptables-transparent";
	
	
	
	$iptables_save=$GLOBALS["CLASS_UNIX"]->find_program("iptables-save");
	exec("$iptables_save 2>&1",$results);
	$OK1=false;
	$OK2=false;
	$Active=false;
	while (list ($pid, $ligne) = each ($results) ){
		if(preg_match("#-A PREROUTING.*?-p tcp -m tcp --dport [0-9]+#", $ligne)){
			$OK1=true;
			if($GLOBALS["VERBOSE"]){echo "!!!! match $ligne\n";}
	
		}
	
		if(preg_match("#PREROUTING .*?--dport\s+[0-9]+.*?REDIRECT\s+--to-ports\s+[0-9]+#",$ligne)){
			$OK1=true;
			if($GLOBALS["VERBOSE"]){echo "!!!! match $ligne\n";}
		}
	
		if(preg_match("#-A PREROUTING -p tcp -m tcp --dport [0-9]+ -m comment.*?--to-ports\s+[0-9]+#", $ligne)){
			$OK2=true;
			if($GLOBALS["VERBOSE"]){echo "!!!! match $ligne\n";}
			continue;
		}
	
		if(preg_match("#-A\s+out_trproxy\.[0-9]+.*?-p tcp -j REDIRECT\s+--to-ports\s+[0-9]+#",$ligne)){
			$OK2=true;
			if($GLOBALS["VERBOSE"]){echo "!!!! match $ligne\n";}
			continue;
		}
	
	}
	
	if($OK1==true){if($OK2==true){$Active=true;}}
	
	
	if(!$Active){
		squid_admin_mysql(0, "Transparent method inactive [action=reactive]", @implode("\n", $results),__FILE__,__LINE__);
		shell_exec(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.transparent.php >/dev/null 2>&1"));
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
		
	$l[]="installed=1";
	$l[]="running=1";
	$l[]="master_pid=0";
	$l[]="master_time=0";
	$l[]="master_memory=0";
	$l[]="processes_number=1";
	$l[]="uptime=none";
	$l[]="";
	return @implode("\n", $l);
	
}


//========================================================================================================================================================
function ufdbcat_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/ufdbcat/ufdbguardd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("ufdbcatdd.*?-c.*?conf");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
}

function ufdbcat(){
	
	$EnableIntelCeleron=intval(file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){
		$l[]="";
		$l[]="[APP_UFDBCAT]";
		$l[]="service_name=APP_UFDBCAT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);		
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		$l[]="";
		$l[]="[APP_UFDBCAT]";
		$l[]="service_name=APP_UFDBCAT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}
	$Masterbin="/opt/ufdbcat/bin/ufdbcatdd";
	if(!is_file($Masterbin)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.ufdbcat.php --install >/dev/null 2>&1");
		$l[]="";
		$l[]="[APP_UFDBCAT]";
		$l[]="service_name=APP_UFDBCAT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
		
	}
	
	if(!is_file("/etc/init.d/ufdbcat")){
		shell_exec(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbcat  >/dev/null 2>&1 &"));
	}
	
	
	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	$EnableUfdbCat=1;
	$DisableUfdbCat=$GLOBALS["CLASS_SOCKETS"]->DisableUfdbCat();
	if($GLOBALS["VERBOSE"]){echo "CLASS_SOCKETS = DisableUfdbCat = $DisableUfdbCat\n";}
	
	$l[]="\n[APP_UFDBCAT]";
	$l[]="service_name=APP_UFDBCAT";
	$l[]="master_version=".ufdbcatVersion();
	
	$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="-> DisableUfdbCat() return $DisableUfdbCat";
	if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat = $DisableUfdbCat\n";}
	if($DisableUfdbCat==1){$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="status.squid say not enabled";$EnableUfdbCat=0;}
	if($AsCategoriesAppliance==1){$EnableUfdbCat=1;}
	$master_pid=ufdbcat_pid();
	
	$LOGS=@implode("\n", $GLOBALS["CLASS_SOCKETS_DisableUfdbCat"]);
	
	if($EnableUfdbCat==0){
		if($GLOBALS["VERBOSE"]){echo "EnableUfdbCat =  0 die();\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "Stopping Artica Categories service PID $master_pid [not enabled]", $LOGS,__FILE__,__LINE__);
			ToSyslog("Ufdbguard:: Stopping Artica Categories services EnableUfdbCat==0");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdbcat stop >/dev/null 2>&1 &");
		}
		$l[]="service_disabled=0";
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	
	$l[]="";

	$l[]="service_cmd=/etc/init.d/ufdbcat";
	$l[]="service_disabled=$EnableUfdbCat";
	$l[]="watchdog_features=1";
	$l[]="pid_path=";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		ToSyslog("Ufdbguard:: ufdbcat, Watchdog service is not running -> start it");
		squid_admin_mysql(0, "Starting Artica Categories services [not running]", $LOGS,__FILE__,__LINE__);
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdbcat start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="installed=1";
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	

	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ufdbcat.php.CheckDirs.time");
		if($CacheSchedules>30){
			$dirs=$GLOBALS["CLASS_UNIX"]->dirdir("/home/ufdbcat");
			if(count($dirs)<50){
				squid_admin_mysql(1, "Artica Categories services seems not updated [action=reconfigure]", "Only ". count($dirs)." directories",__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.ufdbcat.php --checkdirs  >/dev/null 2>&1 &");
				@unlink("/etc/artica-postfix/pids/exec.ufdbcat.php.CheckDirs.time");
				@file_put_contents("/etc/artica-postfix/pids/exec.ufdbcat.php.CheckDirs.time", time());
				shell_exec2($cmd);
			}
		}
	}
	
	
	return implode("\n",$l);	
	
	
}
function hypercachestoreid_client_pids(){
	$unix=new unix();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ufdbgclient");
	if(!is_file($Masterbin)){return;}
	$pids=array();

	exec("$pgrep -l -f hypercache-plugin",$f);
	while (list ($num, $line) = each ($f)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["VERBOSE"]){echo "-> hypercachestoreid_client_pids() -> PID:$pid\n";}
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}
	return $pids;
}

function ufdbguardd_client_pids(){
	$unix=new unix();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ufdbgclient");
	if(!is_file($Masterbin)){return;}
	$pids=array();

	exec("$pgrep -l -f ufdbgclient",$f);
	while (list ($num, $line) = each ($f)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PID:$pid\n";}
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}
	return $pids;
}

function HyperCacheStoreID_client(){
	$ufdbguardd_path="/lib/squid3/hypercache-plugin";
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	if(!is_file($ufdbguardd_path)){
		$l[]="\n[HYPERCACHE_STOREID]";
		$l[]="service_name=HYPERCACHE_STOREID";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidUrgency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency");
	$HyperCacheStoreID=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID");
	
	
	if($SquidUrgency==1){$HyperCacheStoreID=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$HyperCacheStoreID=0;}
	$l[]="";
	$l[]="\n[HYPERCACHE_STOREID]";
	$l[]="service_name=HYPERCACHE_STOREID";
	$l[]="master_version=".hypercachestoreid_version();
	$l[]="service_cmd=/etc/init.d/squid";
	$l[]="service_disabled=$HyperCacheStoreID";
	$l[]="watchdog_features=1";
	$l[]="pid_path=";

	if($HyperCacheStoreID==0){return @implode("\n", $l);}

	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");

	if(!is_file($ufdbguardd_path)){

		if($GLOBALS["VERBOSE"]){echo "HYPERCACHE_STOREID path none!\n";}
		return;}


		$pids=hypercachestoreid_client_pids();
		$count=count($pids);
		if($GLOBALS["VERBOSE"]){echo "ufdbguard clients = $count\n";}

		if($count==0){
			$l[]="running=0\ninstalled=1";
			$l[]="";
			return implode("\n",$l);
		}


		$rss=0;
		$vm=0;
		if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PIDs:$count\n";}
		
		
		while (list ($num, $pid) = each ($pids)){
			if(!is_numeric($pid)){continue;}
			$newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
			if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PID:$num-$pid RSS: $newrss\n";}
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
		$l[]="";
		if($GLOBALS["VERBOSE"]){echo "Return ".count($l)." Lines\n";}
		return implode("\n",$l);return;


}

function ufdbguardd_client(){
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbgclient");
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	if(!is_file($ufdbguardd_path)){
		$l[]="\n[APP_UFDBGUARD_CLIENT]";
		$l[]="service_name=APP_UFDBGUARD_CLIENT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidUrgency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency");
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
	if($SquidUrgency==1){$EnableUfdbGuard=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	if($SquidUFDBUrgency==1){$EnableUfdbGuard=0;}
	$l[]="";
	$l[]="\n[APP_UFDBGUARD_CLIENT]";
	$l[]="service_name=APP_UFDBGUARD_CLIENT";
	$l[]="master_version=".ufdbguardd_version("ufdbguardd");
	$l[]="service_cmd=/etc/init.d/ufdb-client";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	$l[]="pid_path=";

	if($EnableUfdbGuard==0){return @implode("\n", $l);}

	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");

	if(!is_file($ufdbguardd_path)){
		if($GLOBALS["VERBOSE"]){echo "ufdbguard path none!\n";}
		return;
	}


	$pids=ufdbguardd_client_pids();

	$count=count($pids);
	events("ufdbguard clients = $count",__FUNCTION__,__LINE__);
	

	if($count==0){
		$l[]="running=0\ninstalled=1";
		$l[]="";

		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Web filtering clients are not running [action=start]", null,__FILE__,__LINE__);
			events("/etc/init.d/ufdb-client start",__FUNCTION__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-client start --monitor --force >/dev/null 2>&1 &");
		}

		return implode("\n",$l);
	}


	$rss=0;
	$vm=0;
	if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PIDs:$count\n";}
	while (list ($num, $pid) = each ($pids)){
		if(!is_numeric($pid)){continue;}
		$newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
		if($GLOBALS["VERBOSE"]){echo "-> ufdbguardd_client() -> PID:$num-$pid RSS: $newrss\n";}
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
	$l[]="";
	if($GLOBALS["VERBOSE"]){echo "Return ".count($l)." Lines\n";}
	return implode("\n",$l);return;


}





function ufdbguardd(){


	if(!$GLOBALS["CLASS_USERS"]->APP_UFDBGUARD_INSTALLED){
		$l[]="";
		$l[]="[APP_UFDBGUARD]";
		$l[]="service_name=APP_UFDBGUARD";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);}


	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");

	$master_pid=ufdbguard_pid();
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$UseRemoteUfdbguardService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UseRemoteUfdbguardService');
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableWebProxyStatsAppliance');
	$UpdateUFDBDec2013=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UpdateUFDBDec2013');
	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
	
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	$remote_server=$datas["remote_server"];
	
	if($GLOBALS["VERBOSE"]){echo "remote_server=$remote_server; EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance; UseRemoteUfdbguardService=$UseRemoteUfdbguardService\n";}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($remote_server=="127.0.0.1"){$remote_server=null;}
	$users=new usersMenus();
	if($GLOBALS["CLASS_USERS"]->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}

	if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard=$EnableUfdbGuard\n";}
	if($GLOBALS["VERBOSE"]){echo "UseRemoteUfdbguardService=$UseRemoteUfdbguardService\n";}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($UpdateUFDBDec2013)){$UpdateUFDBDec2013=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}


	if(trim($remote_server)==null){
		if($EnableRemoteStatisticsAppliance==0){
			$UseRemoteUfdbguardService=0;
		}

	}

	if($UpdateUFDBDec2013==0){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UpdateUFDBDec2013",1);
		shell_exec("{$GLOBALS["BASE_ROOT"]}/bin/artica-make APP_UFDBGUARD");

	}


	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}
	if($SQUIDEnable==0){$SQUIDEnable=0;}
	if($EnableWebProxyStatsAppliance==0){
		if($UseRemoteUfdbguardService==1){
			if($GLOBALS["VERBOSE"]){echo "UseRemoteUfdbguardService = 1 switch EnableUfdbGuard to 0\n";}
			$EnableUfdbGuard=0;
		}
	}

	if($EnableRemoteStatisticsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "EnableRemoteStatisticsAppliance = 1 switch EnableUfdbGuard to 0\n";}
		$EnableUfdbGuard=0;
	}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidUrgency")){
		$SquidUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
		if($SquidUrgency==1){$EnableUfdbGuard=0;}
	}
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency")){
		$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
		if($SquidUFDBUrgency==1){$EnableUfdbGuard=0;}
	}
	
	
	
	

	if($GLOBALS["VERBOSE"]){echo "SQUIDEnable=$SQUIDEnable\n";}
	$l[]="";
	$l[]="[APP_UFDBGUARD]";
	$l[]="service_name=APP_UFDBGUARD";
	$l[]="master_version=".ufdbguardd_version();
	$l[]="service_cmd=/etc/init.d/ufdb";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	
	


	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($EnableUfdbGuard==0){
			if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard =  0 die();\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
				
				if(function_exists("squid_admin_mysql")){
					squid_admin_mysql(2,"Stopping Ufdbguard service","it is disabled",null,__FILE__,__LINE__);
					
				}
				
				$l[]="running=1";
				$l[]=GetMemoriesOf($master_pid);
				$l[]="";
				ToSyslog(basename(__FILE__)." Stopping Ufdbguard PID: $master_pid in line ".__LINE__);
				if(is_file("/proc/$master_pid/cmdline")){
					ToSyslog(basename(__FILE__)." $master_pid is `".trim(@file_get_contents("/proc/$master_pid/cmdline"))."`");
				}
				if(!is_file("/etc/init.d/ufdb")){
					shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
				}
				
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb stop >/dev/null 2>&1 &");
			}
			
			
			if(is_file("/etc/cron.d/ufdb-db-checkv")){
				@unlink("/etc/cron.d/ufdb-db-checkv");
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cron reload >/dev/null 2>&1 &");
			}
			return implode("\n",$l);
			
		}
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($ufdbguardd_path);
		@file_put_contents($pid_path,$master_pid);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon service is not running -> start it...", basename(__FILE__));}
			squid_admin_mysql(1, "Web filtering Engine is down [action=start]","",__FUNCTION__,__FILE__,__LINE__);
			ufdbguard_admin_events("UfdGuard master Daemon service is not running -> start it...",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}	

	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!is_file("/etc/artica-postfix/ufdbfirst")){
			squid_admin_mysql(2, "Launching first updates of Web Filering databases","",__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
			ufdbguard_admin_events("Launching first updates of Ufdbguard databases",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.blacklists.php --ufdb-first >/dev/null 2>&1 &");
			
		}
		
		if(is_file("/var/log/squid/UFDB_SOCKET_ERROR")){
			@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
			squid_admin_mysql(0, "Sockets error encountered, restart Web filtering service",
			"/var/log/squid/UFDB_SOCKET_ERROR exists, see previous errors",__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --force --watchdog >/dev/null 2>&1 &");
		}
	
		@mkdir("/var/log/ufdbguard",0755,true);
		if(!is_file("/var/tmp/ufdbguardd.pid")){@file_put_contents("/var/tmp/ufdbguardd.pid", $master_pid);}

		$MaxThreads=140;
		$CurrentThreads=$GLOBALS["CLASS_UNIX"]->PROCESS_SOCKETS_NUM($master_pid);
		if($CurrentThreads>=$MaxThreads){
			squid_admin_mysql(0, "Web filtering, Max threads limit reached - $CurrentThreads - restarting", "The Web filtering service threads are freeze, the dameon will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --watchdog >/dev/null 2>&1 &");
		}
	
		if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ufdb.queue.release.php.time")>5){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.ufdb.queue.release.php >/dev/null 2>&1 &");
		}
	
		if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squidguard.smtp.php.ufdb_all_smtp.time")>15){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squidguard.smtp.php --smtp >/dev/null 2>&1 &");
		}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/ArticaDbCloud")){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.blacklists.php --get-version >/dev/null 2>&1 &");
		}
		
		if(!is_file("/etc/cron.d/ufdb-db-checkv")){
			$f[]="MAILTO=\"\"";
			$f[]="30 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * * root {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.blacklists.php --get-version >/dev/null 2>&1";
			$f[]="";
			@file_put_contents("/etc/cron.d/squid-run-c", @implode("\n", $f));
			@chmod("/etc/cron.d/ufdb-db-checkv",0644);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cron reload >/dev/null 2>&1 &");
		}
		
		
		
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);

}
//========================================================================================================================================================
function ufdbguardd_tail_pid(){
	$pid=trim(@file_get_contents("/etc/artica-postfix/exec.ufdbguard-tail.php.pid"));
	if($GLOBALS["VERBOSE"]){echo "/etc/artica-postfix/exec.ufdbguard-tail.php.pid = $pid\n";}
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("exec.ufdbguard-tail.php");

}
function ufdbguardd_tail(){
	if(!is_file("{$GLOBALS["BASE_ROOT"]}/exec.ufdbguard-tail.php")){return;}

	if(!$GLOBALS["CLASS_USERS"]->APP_UFDBGUARD_INSTALLED){return;}


	
	$master_pid=ufdbguardd_tail_pid();
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance");
	$UseRemoteUfdbguardService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UseRemoteUfdbguardService');
	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableRemoteStatisticsAppliance');
	if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard=$EnableUfdbGuard\n";}
	if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "UseRemoteUfdbguardService=$UseRemoteUfdbguardService\n";}
	if($GLOBALS["VERBOSE"]){echo "EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance\n";}

	if(!is_dir("/home/ufdb/relatime-events")){
		@mkdir("/home/ufdb/relatime-events",0755,true);
	}
	shell_exec("{$GLOBALS["CHOWN"]} -R squid:squid /home/ufdb/relatime-events");

	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableUfdbGuard==null){$EnableUfdbGuard=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	if($EnableRemoteStatisticsAppliance==1){$EnableUfdbGuard=0;}


	$l[]="";
	$l[]="[APP_UFDBGUARD_TAIL]";
	$l[]="service_name=APP_UFDBGUARD_TAIL";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=/etc/init.d/ufdb-tail";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	

	if($EnableUfdbGuard==1){
		$filetime=file_time_min("/var/log/artica-postfix/ufdbguard-tail.debug");
		events("ufdbguard-tail.debug {$filetime}Mn ",__FUNCTION__,__LINE__);
		if($filetime>5){
			@unlink("/var/log/artica-postfix/ufdbguard-tail.debug");
			ToSyslog("Ufdbguard:: ufdb-tail, /var/log/artica-postfix/ufdbguard-tail.debug = {$filetime}mn -> restart");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-tail restart >/dev/null 2>&1 &");
		}
	}

	if($EnableUfdbGuard==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Ufdbguard:: ufdb-tail, Watchdog service is running PID $master_pid sbut disabled [stop it]");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-tail stop >/dev/null 2>&1 &");
			$l[]="installed=1";
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
		}
		
		return implode("\n",$l);
		
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		ToSyslog("Ufdbguard:: ufdb-tail, Watchdog service is not running -> start it");
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-tail start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	$l[]="installed=1";
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);
}


//========================================================================================================================================================

function cntlm_pid(){
	$filename="/var/run/cntlm.pid";
	if(is_file($filename)){
		$pid=trim(@file_get_contents($filename));
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	}
	$cntlm=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$cntlm.*?cntlm\.pid");
}
function cntlm_parent_pid(){
	$filename="/var/run/cntlm-parent.pid";
	if(is_file($filename)){
		$pid=trim(@file_get_contents($filename));
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	}
	
	
	$cntlm=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$cntlm.*?cntlm-parent");
}


function cntlm_parent(){
	
	if($GLOBALS["VERBOSE"]){echo "\n\n ***** CNTLM PARENT ******* \n\n";}
	
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){
		$l[]="";
		$l[]="[APP_CNTLM_PARENT]";
		$l[]="service_name=APP_CNTLM_PARENT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	$master_pid=cntlm_parent_pid();
	$EnableCNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableParentNTLM"));
	
	$l[]="";
	$l[]="[APP_CNTLM_PARENT]";
	$l[]="service_name=APP_CNTLM_PARENT";
	$l[]="service_cmd=/etc/init.d/cntlm-parent";
	$l[]="master_version=".cntlm_version();
	$l[]="service_disabled=$EnableCNTLM";
	$l[]="pid_path=/var/run/cntlm-parent.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	
	
	if($EnableCNTLM==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.cntlm-parent.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.cntlm-parent.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	return implode("\n",$l);return;
	
}


function cntlm(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){
		$l[]="";
		$l[]="[APP_CNTLM]";
		$l[]="service_name=APP_CNTLM";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	if($GLOBALS["VERBOSE"]){echo "\n\n ***** CNTLM ******* \n\n";}

	$master_pid=cntlm_pid();
	$EnableCNTLM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCNTLM");
	$EnableKerbAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){$EnableCNTLM=0;}
	$l[]="";
	$l[]="[APP_CNTLM]";
	$l[]="service_name=APP_CNTLM";
	$l[]="service_cmd=/etc/init.d/cntlm";
	$l[]="master_version=".cntlm_version();
	$l[]="service_disabled=$EnableCNTLM";
	$l[]="pid_path=/var/run/cntlm.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	

	if($EnableCNTLM==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.cntlm.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		if($GLOBALS["VERBOSE"]){echo "CNTLM :Disbaled\n";}
		$l[]="";
		return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.cntlm.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);;
}




function hotspot_web(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$EnableArticaHotSpot=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaHotSpot");
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
	$lighttpd_user=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$master_version=apache_version();
	$pid_path="/var/run/artica-apache/hotspot-apache.pid";
	$PatternPIDOF=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CTL()." -f /etc/artica-postfix/hotspot-httpd.conf";
	if($GLOBALS["VERBOSE"]){echo "lighttpd-user:$lighttpd_user\n";}


	$array=stat("{$GLOBALS["BASE_ROOT"]}/logon.php");
	$activeuser=posix_getpwuid($array["uid"]);
	if($GLOBALS["VERBOSE"]){echo "Current:{$activeuser["name"]}\n";}



	$l[]="";
	$l[]="[HOTSPOT_WWW]";
	$l[]="service_name=HOTSPOT_WWW";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/artica-hotspot";
	$l[]="service_disabled=$EnableArticaHotSpot";
	$l[]="watchdog_features=1";
	$l[]="family=proxy";
	$l[]="installed=1";
	if($EnableArticaHotSpot==0){return implode("\n",$l);}


	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($PatternPIDOF);}

	$l[]="pid_path=$pid_path";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.hostpot-web.php --start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function wifidog_pid(){
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("wifidog.*?wifidog.conf");
}


function wifidog_version(){
	if(isset($GLOBALS["wifidog_version"])){return $GLOBALS["wifidog_version"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("wifidog");
	if(preg_match("#([0-9\.]+)#", exec("$Masterbin -v 2>&1"),$re)){$GLOBALS["wifidog_version"]=$re[1];return $re[1];}
}

function hotspot_fw(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("wifidog");
	$EnableArticaHotSpot=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaHotSpot");
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
	
	if(!is_file($bin_path)){
		$l[]="";
		$l[]="[HOTSPOT_SERVICE]";
		$l[]="service_name=HOTSPOT_SERVICE";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="application_installed=0";
		$l[]="";
		return implode("\n",$l);
		
	}
	
	
	$master_version=wifidog_version();
	exec("$bin_path 2>&1",$results);
	$running=false;
	$c=0;
	$l[]="";
	$l[]="[HOTSPOT_SERVICE]";
	$l[]="service_name=HOTSPOT_SERVICE";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/wifidog";
	$l[]="service_disabled=$EnableArticaHotSpot";
	$l[]="watchdog_features=1";
	$l[]="family=proxy";
	$l[]="installed=1";
	if($EnableArticaHotSpot==0){return implode("\n",$l);}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/FireHolEnable",0);
	

	$master_pid=wifidog_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.wifidog.php --start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!is_file("/usr/local/etc/wifidog-msg.html")){
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/hotspot.php --templates >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.wifidog.php --restart");
		}
	}
	
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.wifidog.php.TESTCONNECTION.time")>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.wifidog.php --testcnx >/dev/null 2>&1 &");
	}
	return implode("\n",$l);
}

function c_icap_master_version(){
	if(isset($GLOBALS["c_icap_master_version"])){return $GLOBALS["c_icap_master_version"];}
	$cicapbin=$GLOBALS["CLASS_UNIX"]->find_program("c-icap-config");
	$GLOBALS["c_icap_master_version"]=trim(exec("$cicapbin --version"));
	return $GLOBALS["c_icap_master_version"];
	 
}




function c_icap_master_status(){
	$sock=$GLOBALS["CLASS_SOCKETS"];

	if(!is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
		if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
				if($GLOBALS["VERBOSE"]){echo "SQUID NOT INSTALLED\n";}
				$l[]="";
				$l[]="[C-ICAP]";
				$l[]="service_name=APP_C_ICAP";
				$l[]="service_disabled=0";
				$l[]="installed=0";
				
				return @implode("\n", $l);
			}
	}

	$cicapbin=$GLOBALS["CLASS_UNIX"]->find_program("c-icap");
	if(!is_file($cicapbin)){
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		if(is_file("/home/artica/c-icap.tar.gz.old")){
			$tar=$GLOBALS["CLASS_UNIX"]->find_program("tar");
			shell_exec2("$tar xf /home/artica/c-icap.tar.gz.old -C /");
		}else{
			if($GLOBALS["VERBOSE"]){echo "/home/artica/c-icap.tar.gz.old no such file\n";}
			if(is_dir("/var/run/c-icap")){

				shell_exec2("$nohup {$GLOBALS["BASE_ROOT"]}/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
			}else{
				if($GLOBALS["VERBOSE"]){echo "/var/run/c-icap no such dir\n";}
				$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
				if($CicapEnabled==1){
					shell_exec2("$nohup {$GLOBALS["BASE_ROOT"]}/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
				}
			}
		}
	}

	$cicapbin=$GLOBALS["CLASS_UNIX"]->find_program("c-icap");

	if(!is_file($cicapbin)){
		if($GLOBALS["VERBOSE"]){echo "C_ICAP NOT INSTALLED\n";}
		return null;}

		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		$SquidDisableAllFilters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}
		$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableRemoteStatisticsAppliance');
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
		$UnlockWebStats=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}

		if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
			$EnableStatisticsCICAPService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatisticsCICAPService");
			if(!is_numeric($EnableStatisticsCICAPService)){$EnableStatisticsCICAPService=1;}
			$CicapEnabled=1;
			if($EnableStatisticsCICAPService==0){$CicapEnabled=0;}
		}

		if($SQUIDEnable==0){$CicapEnabled=0;}
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
		if(!is_numeric($SquidDisableAllFilters)){$SquidDisableAllFilters=0;}

		if($GLOBALS["CLASS_USERS"]->APP_KHSE_INSTALLED){
			$KavMetascannerEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavMetascannerEnable");
			if(!is_numeric($KavMetascannerEnable)){$KavMetascannerEnable=0;}
			if($KavMetascannerEnable==1){$CicapEnabled=1;}
		}

		if($SquidDisableAllFilters==1){$CicapEnabled=0;}

		if(!$GLOBALS["CLASS_USERS"]->MEM_HIGER_1G){
			if($GLOBALS["VERBOSE"]){echo "MEM_HIGER_1G !!! FALSE\n";}
			if($CicapEnabled==1){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled",0);}
			$CicapEnabled=0;
		}
		if($EnableRemoteStatisticsAppliance==1){$CicapEnabled=0;}

		
		
		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/c-icap/c-icap.pid");
		

		$l[]="[C-ICAP]";
		$l[]="service_name=APP_C_ICAP";
		$l[]="master_version=".c_icap_master_version();
		$l[]="service_cmd=/etc/init.d/c-icap";
		$l[]="service_disabled=$CicapEnabled";
		$l[]="pidpath=/var/run/c-icap/c-icap.pid";
		$l[]="explain=enable_c_icap_text";
		$l[]="family=squid";



		if($CicapEnabled==0){
			return implode("\n",$l);return;
		}

		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("c-icap");
		$l[]="binpath=$binpath";


		if($master_pid==null){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}

		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				shell_exec2("$nohup /etc/init.d/c-icap start >/dev/null 2>&1 &");
			}
			$l[]="running=0\ninstalled=1";$l[]="";
			return implode("\n",$l);

		}





		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);

		$l[]="";
		return implode("\n",$l);return;

}




function _statussquid($text=null){
	if(!isset($GLOBALS["MIPIDSQUID"])){$GLOBALS["MIPIDSQUID"]=getmypid();}
	
	$TIME=date("M d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$logFile="/var/log/artica-status-squid.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$TIME [{$GLOBALS["MIPIDSQUID"]}] $text\n");
	@fclose($f);
}





function squid_nat(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($masterbin)){return;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
	$l[]="[APP_SQUID_NAT]";
	$l[]="service_name=APP_SQUID_NAT";
	$l[]="master_version=".squid_27_version();
	$l[]="service_cmd=/etc/init.d/squid-nat";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=squid_nat_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --squid-nat");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-nat start >/dev/null 2>&1 &");
			squid_admin_mysql(0,"Squid-Cache NAT front-end not running, start it...",null,__FILE__,__LINE__);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}

function squid_db_version(){
	if(isset($GLOBALS["SQUID_DB_VERSION"])){return $GLOBALS["SQUID_DB_VERSION"];}
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");

	exec("$mysqld --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["SQUID_DB_VERSION"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}

function squid_db(){

	
	if($GLOBALS["VERBOSE"]){echo " * * * * SQUID DB * * * *\n";}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseArticaDB");
	if($GLOBALS["VERBOSE"]){echo " * * * * Enabled = $enabled  * * * *\n";}
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/squid-db.pid";
	$master_pid=squid_db_pid();
	
	
	$l[]="[APP_SQUID_DB]";
	$l[]="service_name=APP_SQUID_DB";
	$l[]="master_version=".squid_db_version();
	$l[]="service_cmd=/etc/init.d/squid-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	
	events("ProxyUseArticaDB ->`$enabled`",__FUNCTION__,__LINE__);
	

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			events("Running /exec.squid-db.php --init",__FUNCTION__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.squid-db.php --init");
			shell_exec2($cmd);
			events("Running /etc/init.d/squid-db restart",__FUNCTION__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}else{
		events("Running PID $master_pid",__FUNCTION__,__LINE__);
		if($enabled==0){
			events("Stopping PID $master_pid",__FUNCTION__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db stop >/dev/null 2>&1 &");
		}
	}
	if($enabled==1){
		events("Checking /var/run/mysqld/squid-db.sock",__FUNCTION__,__LINE__);
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/mysqld/squid-db.sock")){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db restart >/dev/null 2>&1 &");
		}
	}



	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	if($enabled==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid-db.php --databasesize >/dev/null 2>&1 &");
		
	}

	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time");
	events("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time $CacheSchedules/240Min",__FUNCTION__,__LINE__);
	
	if($CacheSchedules>240){
		if(!system_is_overloaded()){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.mysqld.crash.php --crashed-squid >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			shell_exec2($cmd);
		}
	}


	return implode("\n",$l);return;
}

function squid_master_status($buildcache=false){
	$time_start=microtime(true);
	_statussquid("squid_master_status()...");
	
	$sock=$GLOBALS["CLASS_SOCKETS"];
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		$l[]="";
		$l[]="[SQUID]";
		$l[]="service_name=APP_SQUID";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		@unlink("/usr/share/artica-postfix/ressources/databases/SQUID.version");
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is not installed\n";}
		return @implode("\n", $l);
	}
	
	$xCacheTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($xCacheTime);
	_statussquid("squid_master_status() $xCacheTime = {$CacheSchedules}s/20s...");
	if($CacheSchedules<20){
		_statussquid("squid_master_status(): {$CacheSchedules}s/20s Return cache");
		return @file_get_contents($xCacheTime);
	}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	
	$prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
	$sqdbin=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
	if($EnableArticaMetaClient==1){@touch("/etc/squid3/EnableArticaMetaClient_ON");}else{@unlink("/etc/squid3/EnableArticaMetaClient_ON"); }
	
	if(!is_file($sqdbin)){
		$l[]="";
		$l[]="[SQUID]";
		$l[]="service_name=APP_SQUID";
		$l[]="master_version=". squid_master_status_version();
		$l[]="service_cmd=/etc/init.d/squid";
		$l[]="service_disabled=0";
		$l[]="watchdog_features=1";
		$l[]="binpath=$sqdbin";
		$l[]="explain=SQUID_CACHE_TINYTEXT";
		$l[]="remove_cmd=--squid-remove";
		$l[]="family=squid";
		$l[]="running=0\ninstalled=0";$l[]="";
		return @implode("\n", $l);
		
	}
	
	
	if($SQUIDEnable==0){
		if(is_file("/etc/cron.d/artica-calamaris")){
			@unlink("/etc/cron.d/artica-calamaris");
			shell_exec("/etc/init.d/cron reload");
		}
	}
		
	if($SQUIDEnable==1){
		if(!is_file("/etc/cron.d/artica-calamaris")){
			Popuplate_cron_make("artica-calamaris","0 * * * *","exec.calamaris.php");
			shell_exec("/etc/init.d/cron reload");
		}
	}
	
	
	

	if($SQUIDEnable==0){
		$l[]="";
		$l[]="[SQUID]";
		$l[]="service_name=APP_SQUID";
		$l[]="master_version=". squid_master_status_version();
		$l[]="service_cmd=/etc/init.d/squid";
		$l[]="service_disabled=0";
		$l[]="watchdog_features=1";
		$l[]="binpath=$sqdbin";
		$l[]="explain=SQUID_CACHE_TINYTEXT";
		$l[]="remove_cmd=--squid-remove";
		$l[]="family=squid";
		$l[]="installed=1";
		$l[]="";
		return @implode("\n", $l);
	}
		
	$squid_master_status_version=squid_master_status_version();
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
	
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/SQUID.version",$squid_master_status_version);
	@chmod("/usr/share/artica-postfix/ressources/databases/SQUID.version",0755);
	$l[]="";
	$l[]="[SQUID]";
	$l[]="service_name=APP_SQUID";
	$l[]="master_version=". squid_master_status_version();
	$l[]="service_cmd=/etc/init.d/squid";
	$l[]="service_disabled=$SQUIDEnable";
	$l[]="watchdog_features=1";
	$l[]="binpath=$sqdbin";
	$l[]="explain=SQUID_CACHE_TINYTEXT";
	$l[]="remove_cmd=--squid-remove";
	$l[]="family=squid";
	if($GLOBALS["CLASS_UNIX"]->SQUID_ICAP_ENABLED()){
		if($GLOBALS["VERBOSE"]){echo "SQUID_ICAP_ENABLED OK\n";}
		$l[]="icap_enabled=1";
	}
	
	
	
	
	
	$timefile="/etc/artica-postfix/pids/exec.squid.run.schedules.php.time";
	$timex=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	_statussquid("/etc/artica-postfix/pids/exec.squid.run.schedules.php.time = $timex/6min");
	if($timex>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.run.schedules.php >/dev/null 2>&1 &");
	
	}


	if(!$buildcache){
		$TimeEX=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/databases/ALL_SQUID_STATUS");
		_statussquid("ALL_SQUID_STATUS: cache file {$TimeEX}Mn");
		
		
		if($TimeEX>10){
			if(!system_is_overloaded(basename(__FILE__))){
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.status.php --all-squid --nowachdog");
			}
		}
	}

	$pidpath=squid_pid_path();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::Pidpath= $pidpath\n";}
	$logs[]="pidpath: $pidpath";
	if($SQUIDEnable==0){$l[]="running=0\ninstalled=1";return implode("\n",$l);return;}
	if($GLOBALS["VERBOSE"]){echo "Check $pidpath\n";}

	$pid=trim(@file_get_contents($pidpath));
	if($GLOBALS["VERBOSE"]){echo "Pidpath= $pidpath\n";}
	$logs[]="pid: $pid";
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::PID = $pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		
		$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($sqdbin);
		$logs[]="pid: $pid (after pidof $sqdbin)";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":pid: $pid (after pidof $sqdbin)\n";}
		
	}
	
	
	$time_end = microtime(true);
	$time_calc = $time_end - $time_start;
	_statussquid("squid_master_status(): Running squid_tasks [{$time_calc}ms]");
	squid_tasks_events("Invoke squid_tasks($DisableStats)",__FUNCTION__,__FILE__,__LINE__);
	squid_tasks($DisableStats);
	$notiftime="/etc/artica-postfix/squid-failed-notiftime";
	
	$time_end = microtime(true);$time_calc = $time_end - $time_start;
	_statussquid("squid_master_status(): PID=$pid [{$time_calc}ms]");
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		$time_end = microtime(true);$time_calc = $time_end - $time_start;
		_statussquid("squid_master_status(): Seems not running...{$time_calc}ms");
		
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
			$logs[]="pgrep:";
			exec("$pgrep -l -f \"$sqdbin\" 2>&1",$logs);
			if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
			$notiftimeex=$GLOBALS["CLASS_UNIX"]->file_time_min($notiftime);
			if($notiftimeex>5){
				squid_admin_mysql(0,"Proxy service Stopped",
				"launch the start procedure...\nNotif time:$notiftimeex\n$executed\n".@implode("\n", $logs),__FILE__,__LINE__);
				
				@unlink($notiftime)	;
				@file_put_contents($notiftime, time());
			}
			squid_watchdog_events("Start Watchdog! Starting Squid cache Daemon -> /etc/init.d/squid start --exec-status...");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid start --exec-status >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);return;
	}
	@unlink($notiftime)	;
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($pid);

	$l[]="";
	$time_end = microtime(true);
	$time_calc = $time_end - $time_start;
	_statussquid("squid_master_status():: Running squidmaster_status [{$time_calc}ms]");
	
	
	$time_end = microtime(true);
	$time_calc = $time_end - $time_start;
	_statussquid("Running squidmaster_status [{$time_calc}ms], saving $xCacheTime");
	@unlink($xCacheTime);
	@file_put_contents($xCacheTime,implode("\n",$l));
	return implode("\n",$l);return;
}
// ========================================================================================================================================================
function squid_master_status_version(){
	if(isset($GLOBALS["squid_master_status_version"])){return $GLOBALS["squid_master_status_version"];}
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if($squidbin==null){$squidbin=$unix->find_program("squid3");}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";}
			$GLOBALS["squid_master_status_version"]=$re[1];
			return $GLOBALS["squid_master_status_version"];
		}
	}
	
}
//========================================================================================================================================================
function cntlm_version(){
	$cacheFile="/etc/artica-postfix/cntlm_version";
	$unix=new unix();
	if($GLOBALS["CLASS_UNIX"]->file_time_min($cacheFile)<800){return @file_get_contents("/etc/artica-postfix/cntlm_version");}

	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){if($GLOBALS['VERBOSE']){echo "cntlm -> no such file\n";}return;}
	exec("$bin -h 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#Proxy version\s+([0-9\.\-a-z]+)#i", $line,$re)){
			@unlink($cacheFile);
			@file_put_contents($cacheFile, $re[1]);
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "cntlm_version(),  \"$line\", not found \n";}
	}
}
//========================================================================================================================================================

function squid_pid_path(){
	if(is_file("/var/run/squid3.pid")){
		if($GLOBALS["VERBOSE"]){echo "squid_pid_path:: /var/run/squid3.pid exists...\n";}
		$pid=trim(@file_get_contents("/var/run/squid3.pid"));
		if($GLOBALS["VERBOSE"]){echo "squid_pid_path:: /var/run/squid3.pid -> $pid...\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return "/var/run/squid3.pid";}
	}

	$pidfile=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_PID();

	if(is_file($pidfile)){
		$pid=trim(@file_get_contents($pidfile));
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pidfile;}
	}
	return "/var/run/squid/squid.pid";

}
function squid_tasks(){
	
	$nice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
	$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$ionice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
	

	if(!is_file("/etc/cron.d/squid-client-list")){
		$f[]="MAILTO=\"\"";
		$f[]="10,20,30,40,50,0 * * * * root $ionice $php /usr/share/artica-postfix/exec.squidclient.mgr.clientlist.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/squid-client-list", @implode("\n", $f));
		@chmod("/etc/cron.d/squid-client-list",0644);
		shell_exec("/etc/init.d/cron reload");
	}
	
	
	if(is_file("/etc/cron.d/squid-run-c")){
		if(is_file("/etc/artica-postfix/pids/exec.squid.run.schedules.php.time")){
			return;
		}
	}
	
	$nice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
	$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$ionice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
	$f=array();
	$f[]="MAILTO=\"\"";
	$f[]="5,10,15,20,25,30,35,40,45 * * * * root $ionice $php /usr/share/artica-postfix/exec.squid.run.schedules.php >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/squid-run-c", @implode("\n", $f));
	@chmod("/etc/cron.d/squid-run-c",0644);
	shell_exec("/etc/init.d/cron reload");
	shell_exec("{$GLOBALS["nohup"]} $php /usr/share/artica-postfix/exec.squid.run.schedules.php >/dev/null 2>&1 &");
	unset($f);
}
function squid_tasks_events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	$function=__CLASS__."/".$function;
	$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-stats-executor.log",false,$function,$line,$filename);
}

function squid_clamav_tail(){
	if(!is_file("{$GLOBALS["BASE_ROOT"]}/exec.squid-clamav-tail.php")){return;}
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is not installed\n";}return null;}else{if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is installed\n";}}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}
	$EnableSquidClamav=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidClamav");
	if($SQUIDEnable==0){$EnableSquidClamav=0;}

	$master_pid=trim(@file_get_contents("/etc/artica-postfix/exec.squid-clamav-tail.php.pid"));

	
	$l[]="[APP_SQUID_CLAMAV_TAIL]";
	$l[]="service_name=APP_SQUID_CLAMAV_TAIL";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=squidclamav-tail";
	$l[]="service_disabled=$EnableSquidClamav";
	$l[]="watchdog_features=1";


	if($EnableSquidClamav==0){$l[]="running=0\ninstalled=1";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_SQUID_CLAMAV_TAIL","squidclamav-tail");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);

	$l[]="";
	return implode("\n",$l);return;

}
// ========================================================================================================================================================
function squid_cache_tail(){
	if(!is_file("{$GLOBALS["BASE_ROOT"]}/exec.cache-logs.php")){return;}
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is not installed\n";}return null;}else{if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is installed\n";}}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}

	$master_pid=trim(@file_get_contents("/etc/artica-postfix/pids/exec.cache-logs.php.pid"));


	$l[]="[APP_SQUID_CACHE_TAIL]";
	$l[]="service_name=APP_SQUID_CACHE_TAIL";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=squidcache-tail";
	$l[]="service_disabled=$SQUIDEnable";
	$l[]="watchdog_features=1";


	if($SQUIDEnable==0){$l[]="running=0\ninstalled=1";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("php.*?exec.cache-logs.php");
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		squid_watchdog_events("Starting Squid cache tail..");
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cache-tail start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);

	$l[]="";
	return implode("\n",$l);return;

}
// ========================================================================================================================================================
function squid_nat_pid(){
	$unix=new unix();
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/squid-nat.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/squid27/squid.conf");
}
function squid_27_version(){
	if(isset($GLOBALS["squid_27_version"])){return $GLOBALS["squid_27_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			$GLOBALS["squid_27_version"]=trim($re[1]);
			return $GLOBALS["squid_27_version"];
		}
	}
}
function squid_db_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/squid-db.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$SquidStatsDatabasePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStatsDatabasePath");
	if($SquidStatsDatabasePath==null){$SquidStatsDatabasePath="/opt/squidsql";}
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");

	$WORKDIR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}

	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?squid-db.sock");

}

function ufdbguard_pid(){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/urlfilterdb/ufdbguardd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	
	$ufdbguardd=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($ufdbguardd);
}
function ufdbguardd_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");
	exec("$ufdbguardd_path -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}

function hypercachestoreid_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	
	exec("/lib/squid3/hypercache-plugin -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Version:\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}

function articadb(){
	$timefile=$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS_FULL.db");
	_statussquid("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS_FULL.db ( $timefile mn/30)");
	
	if($time_file>30){
		_statussquid("-> /usr/share/artica-postfix/exec.ufdb.databases.php");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb.databases.php >/dev/null 2>&1 &");
	}
}






function kav4Proxy_pid(){
	$PID_PATH="/var/run/kav4proxy/kavicapserver.pid";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver");

}
function kav4proxy_version(){

	if(isset($GLOBALS["kav4proxy_version"])){return $GLOBALS["kav4proxy_version"];}
	exec("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver -v 2>&1",$results);
	while (list ($none, $line) = each ($results)){
		if(preg_match("#version\s+([0-9\.]+)\/RELEASE build.*?([0-9]+)#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1]." build {$re[2]}";
			break;
		}
		if(preg_match("#version\s+([0-9\.]+)\/RELEASE#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1];
			break;
		}
		if(preg_match("#version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["kav4proxy_version"]= $re[1];
			break;
		}
	}

	return $GLOBALS["kav4proxy_version"];
}


function kav4Proxy_status(){
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		$l[]="";
		$l[]="[KAV4PROXY]";
		$l[]="service_name=APP_KAV4PROXY";
		$l[]="service_disabled=0";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return @implode("\n", $l).kav4Proxy_keepup2date();
	}
	if(!$GLOBALS["CLASS_USERS"]->KAV4PROXY_INSTALLED){
		$l[]="";
		$l[]="[KAV4PROXY]";
		$l[]="service_name=APP_KAV4PROXY";
		$l[]="service_disabled=0";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return @implode("\n", $l).kav4Proxy_keepup2date();
	}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kavicapserverEnabled"));
	if($SQUIDEnable==0){$enabled=0;}


	$master_pid=kav4Proxy_pid();

	$l[]="[KAV4PROXY]";
	$l[]="service_name=APP_KAV4PROXY";
	$l[]="master_version=".kav4proxy_version();
	$l[]="service_cmd=/etc/init.d/kav4proxy";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kav4Proxy-remove";
	$l[]="explain=enable_kavproxy_text";
	$l[]="installed=1";
	$l[]="family=squid";
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver");
	}
	
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "Kaspersky Anti-Virus for Proxy Server MUST not running [action=stop]", null,__FILE__,__LINE__);
			if(function_exists("WriteToSyslog")){WriteToSyslog("kavicapserverEnabled = 0 : Stopping kav4proxy...", basename(__FILE__));}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/kav4proxy stop >/dev/null 2>&1 &");
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
			return implode("\n",$l).kav4Proxy_keepup2date();
		}
		
		return implode("\n",$l)."\n";
	}
	
	
	

	if($enabled==1){
		$licenseManager=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager");
		if($GLOBALS["CLASS_UNIX"]->process_exists($licenseManager)){
			if($GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($licenseManager)>1){
				if(function_exists("WriteToSyslog")){WriteToSyslog("Killing /opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager", basename(__FILE__));}
				events("Killing /opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager $licenseManager",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($licenseManager,9);
			}
		}
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.kaspersky-update-logs.php >/dev/null 2>&1 &");

	}




	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Kaspersky Anti-Virus for Proxy Server is not running [action=start]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/kav4proxy start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		
		return implode("\n",$l).kav4Proxy_keepup2date();
	}

	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/Kav4ProxyDatabaseSize.db")){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.keepup2date.php --update-kav4proxy-status >/dev/null 2>&1 &");
	}
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/KAV4PROXY_LICENSE_INFO")>2880){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.kav4proxy.php --license >/dev/null 2>&1 &");
	}
	
	

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l).kav4Proxy_keepup2date();

}
function kav4Proxy_keepup2date(){
	if(!$GLOBALS["CLASS_USERS"]->KASPERSKY_WEB_APPLIANCE){
		if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
			$l[]="";
			$l[]="[KAV4PROXY_KEEPUP2DATE]";
			$l[]="service_name=KAV4PROXY_KEEPUP2DATE";
			$l[]="installed=0";
			$l[]="service_disabled=0";
			return @implode("\n", $l);
		}
		if(!$GLOBALS["CLASS_USERS"]->KAV4PROXY_INSTALLED){
			$l[]="";
			$l[]="[KAV4PROXY_KEEPUP2DATE]";
			$l[]="service_name=KAV4PROXY_KEEPUP2DATE";
			$l[]="installed=0";
			$l[]="service_disabled=0";
			return @implode("\n", $l);
			
		}
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kavicapserverEnabled");
		if($SQUIDEnable==0){$enabled=0;}
	}else{
		$enabled=1;
	}

	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$l[]="";
	$l[]="[KAV4PROXY_KEEPUP2DATE]";
	$l[]="service_name=KAV4PROXY_KEEPUP2DATE";
	$l[]="master_version=".GetVersionOf("kav4proxy");
	$l[]="service_cmd=kav4proxy";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kav4Proxy-remove";
	$l[]="explain=enable_kavproxy_text";
	$l[]="family=squid";
	$l[]="installed=1";
	$l[]="running=1";
	$l[]=GetMemoriesOf($pid);
	$l[]="";
	return implode("\n",$l);
}

function HyperCacheWeb(){
	$SquidEnforceRules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnforceRules"));
	
	$l[]="";
	$l[]="[APP_HYPERCACHE_WEB]";
	$l[]="service_name=APP_HYPERCACHE_WEB";
	$l[]="service_cmd=/etc/init.d/hypercache-web";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_VERSION();
	$l[]="service_disabled=$SquidEnforceRules";
	$l[]="pid_path=/var/run/cntlm-parent.pid";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	$l[]="family=network";
	$master_pid=HyperCacheWeb_pid();
	
	if($SquidEnforceRules==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.HyperCacheWeb.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Hyper Cache Web server not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.HyperCacheWeb.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0";
		$l[]="";
		return implode("\n",$l);
		
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
	
}

function HyperCacheWeb_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/HyperCacheWeb.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CTL();
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($apache2ctl." -f /etc/artica-postfix/HyperCacheHTTPD.conf");
}

// ========================================================================================================================================================

