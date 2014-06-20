<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}

function squid_increment_func($array){
	
	$array[]="squid_nat";
	$array[]="squid_db";
	$array[]="squid_master_status";
	$array[]="squid_clamav_tail";
	$array[]="squid_cache_tail";
	$array[]="sarg";
	$array[]="c_icap_master_status";
	$array[]="hotspot_fw";
	$array[]="hotspot_web";
	$array[]="cntlm";
	$array[]="ufdbguardd_tail";
	$array[]="ufdbguardd";
	$array[]="ufdbguardd_client";
	$array[]="squidguardweb";
	$array[]="squidguard_logger";
	$array[]="dnsmasq_squid";
	$array[]="ziproxy";
	$array[]="articadb";
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

function ziproxy(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('ziproxy');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "ziproxy no such binary.\n";}return;}
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	if($SquidAsMasterPeer==0){$EnableProxyCompressor=0;}
	
	$l[]="[APP_ZIPROXY]";
	$l[]="service_name=APP_ZIPROXY";
	$l[]="service_cmd=/etc/init.d/zipproxy";
	$l[]="master_version=".zipproxy_version();
	$l[]="service_disabled=$EnableProxyCompressor";
	$l[]="family=network";
	$l[]="watchdog_features=1";
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

function dnsmasq_squid(){

	if(!$GLOBALS["CLASS_USERS"]->dnsmasq_installed){
		if($GLOBALS["VERBOSE"]){echo "dnsmasq_installed FALSE\n";}
		return;
	}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('dnsmasq');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "dnsmasq no such binary.\n";}return;}
	$EnableDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->dnsmasq_enabled();
	$EnableLocalDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalDNSMASQ");

	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	$master_pid=_dnsmasq_pid();




	$l[]="[DNSMASQ_SQUID]";
	$l[]="service_name=dns_cache";
	$l[]="service_cmd=/etc/init.d/dnsmasq";
	$l[]="master_version=".dnsmasq_version();
	$l[]="service_disabled=$EnableLocalDNSMASQ";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableLocalDNSMASQ==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){

		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --dnsmasq >/dev/null 2>&1 &");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/dnsmasq start >/dev/null 2>&1 &");

		}

		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);

	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}

//========================================================================================================================================================
function squidguard_logger(){



	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("squidGuard");
	if($bin_path==null){return null;}
	$pid_path="/etc/artica-postfix/exec.squidguard-tail.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));


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
		WATCHDOG("APP_ARTICA_SQUIDGUARDTAIL","squiguard-tail");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================



function squidguardweb(){
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	$pid_path="/var/run/lighttpd/squidguard-lighttpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/artica-postfix/squidguard-lighttpd.conf");
		if($master_pid<>null){@file_put_contents("/var/run/lighttpd/squidguard-lighttpd.pid",$master_pid);}
	}

	$squidGuardEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("squidGuardEnabled");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$EnableSquidClamav=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidClamav");
	
	if($EnableUfdbGuard==1){$squidGuardEnabled=1;}
	if($EnableSquidClamav==1){$squidGuardEnabled=1;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$EnableSquidGuardHTTPService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardHTTPService");
	if($EnableSquidGuardHTTPService==null){$EnableSquidGuardHTTPService=1;}
	if($EnableSquidGuardHTTPService<>1){$squidGuardEnabled=0;}
	
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}





	if($SQUIDEnable==0){$squidGuardEnabled=0;}
	if($squidGuardEnabled==null){$squidGuardEnabled=0;}
	if($EnableWebProxyStatsAppliance==1){$squidGuardEnabled=1;}
	$l[]="[APP_SQUIDGUARD_HTTP]";
	$l[]="service_name=APP_SQUIDGUARD_HTTP";
	$l[]="master_version=".GetVersionOf("lighttpd");
	$l[]="service_cmd=/etc/init.d/squidguard-http";
	$l[]="service_disabled=$squidGuardEnabled";
	$l[]="watchdog_features=1";
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
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

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



function ufdbguardd_client(){
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbgclient");
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	if(!is_file($ufdbguardd_path)){return;}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidUrgency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency");
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	if($SquidUrgency==1){$EnableUfdbGuard=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	$l[]="\n[APP_UFDBGUARD_CLIENT]";
	$l[]="service_name=APP_UFDBGUARD_CLIENT";
	$l[]="master_version=".ufdbguardd_version("ufdbguardd");
	$l[]="service_cmd=/etc/init.d/ufdb-client";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	$l[]="pid_path=";

	if($EnableUfdbGuard==0){return @implode("\n", $l);}

	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");

	if(!is_file($ufdbguardd_path)){return;}


	$pids=ufdbguardd_client_pids();



	$count=count($pids);

	if($count==0){
		$l[]="running=0\ninstalled=1";
		$l[]="";

		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("/etc/init.d/ufdb-client start --monitor");
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


	return implode("\n",$l);return;


}



function ufdbguardd(){


	if(!$GLOBALS["CLASS_USERS"]->APP_UFDBGUARD_INSTALLED){return;}


	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");

	$master_pid=ufdbguard_pid();
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$UseRemoteUfdbguardService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UseRemoteUfdbguardService');
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableWebProxyStatsAppliance');
	$UpdateUFDBDec2013=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('UpdateUFDBDec2013');

	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig")));

	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
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

	if($GLOBALS["VERBOSE"]){echo "SQUIDEnable=$SQUIDEnable\n";}

	$l[]="[APP_UFDBGUARD]";
	$l[]="service_name=APP_UFDBGUARD";
	$l[]="master_version=".ufdbguardd_version();
	$l[]="service_cmd=/etc/init.d/ufdb";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";

	if($EnableUfdbGuard==0){
		if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard =  0 die();\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Ufdbguard:: Stopping Ufdbguard EnableUfdbGuard==0");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["KILL"]} $master_pid >/dev/null 2>&1 &");
		}
		return implode("\n",$l);
		return;
	}


	if(!is_file("/etc/artica-postfix/ufdbfirst")){
		if(!is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){
			squid_admin_mysql(2, "Launching first updates of Web Filering databases","");
			@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
			ufdbguard_admin_events("Launching first updates of Ufdbguard databases",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.blacklists.php --ufdb-first >/dev/null 2>&1 &");
		}
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		ToSyslog("Ufdbguard:: $master_pid -> not running try pidof $ufdbguardd_path");
		if($GLOBALS["VERBOSE"]){echo "$master_pid -> not running try pidof $ufdbguardd_path\n";}
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($ufdbguardd_path);
		ToSyslog("Ufdbguard:: $ufdbguardd_path -> $master_pid");
		@file_put_contents($pid_path,$master_pid);
	}else{
		if($EnableUfdbGuard==0){
			if(function_exists("squid_admin_notifs")){
				squid_admin_mysql(2,"Stopping Ufdbguard service","it is disabled");
				squid_admin_notifs("Stopping Ufdbguard service, it is disabled...", __FUNCTION__, __FILE__, __LINE__, "proxy");}
				if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon service is running but disabled-> stop it...", basename(__FILE__));}
				ufdbguard_admin_events("UfdGuard master Daemon service is running but disabled-> stop it...",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb stop >/dev/null 2>&1 &");
		}
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon service is not running -> start it...", basename(__FILE__));}
			squid_admin_mysql(2, "Web filtering Engine is down, start it","");
			ufdbguard_admin_events("UfdGuard master Daemon service is not running -> start it...",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --ufdbguard >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	@mkdir("/var/log/ufdbguard",0755,true);
	if(!is_file("/var/tmp/ufdbguardd.pid")){@file_put_contents("/var/tmp/ufdbguardd.pid", $master_pid);}

	$MaxThreads=140;
	$CurrentThreads=$GLOBALS["CLASS_UNIX"]->PROCESS_SOCKETS_NUM($master_pid);
	if($CurrentThreads>=$MaxThreads){
		squid_admin_mysql(0, "Web filtering, Max threads limit reached - $CurrentThreads - restarting", "The Web filtering service threads are freeze, the dameon will be restarted",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --watchdog >/dev/null 2>&1 &");
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



	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableUfdbGuard==null){$EnableUfdbGuard=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	if($EnableRemoteStatisticsAppliance==1){$EnableUfdbGuard=0;}



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
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Ufdbguard:: ufdb-tail, Watchdog service is running but disabled -> stop it");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-tail stop >/dev/null 2>&1 &");
		}
		return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		ToSyslog("Ufdbguard:: ufdb-tail, Watchdog service is not running -> start it");
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-tail start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}


//========================================================================================================================================================

function cntlm(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){return;}

	$master_pid=@file_get_contents('/var/run/cntlm.pid');
	$EnableCNTLM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCNTLM");
	$EnableKerbAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){$EnableCNTLM=0;}

	$l[]="[APP_CNTLM]";
	$l[]="service_name=APP_CNTLM";
	$l[]="service_cmd=/etc/init.d/cntlm";
	$l[]="master_version=".cntlm_version();
	$l[]="service_disabled=$EnableCNTLM";
	$l[]="pid_path=/var/run/cntlm.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}

	if($EnableCNTLM==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.cntlm.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";return implode("\n",$l);
		return;
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

	return implode("\n",$l);return;
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




	$l[]="[HOTSPOT_WWW]";
	$l[]="service_name=HOTSPOT_WWW";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/artica-hotspot";
	$l[]="service_disabled=$EnableArticaHotSpot";
	$l[]="watchdog_features=1";
	$l[]="family=proxy";

	if($EnableArticaHotSpot==0){return implode("\n",$l);}


	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($PatternPIDOF);}

	$l[]="pid_path=$pid_path";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.hostpot-web.php --start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function hotspot_fw(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$EnableArticaHotSpot=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaHotSpot");
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("iptables-save");
	$master_version=iptables_version();
	exec("$bin_path 2>&1",$results);
	$running=false;
	$c=0;
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#ArticaHotSpot#",$line)){$running=true;$c++;}
	}

	$l[]="[HOTSPOT_FW]";
	$l[]="service_name=HOTSPOT_FW";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/artica-hotfw";
	$l[]="service_disabled=$EnableArticaHotSpot";
	$l[]="watchdog_features=1";
	$l[]="family=proxy";
	if($EnableArticaHotSpot==0){return implode("\n",$l);}



	if(!$running){
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-hotfw start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}

	$l[]="installed=1";
	$l[]="running=1";
	$l[]="application_installed=1";
	$l[]="master_pid=0";
	$l[]="master_memory=0";
	$l[]="master_cached_memory=0";
	$l[]="processes_number=$c";
	$l[]="uptime=".$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/hotspot.conf")."mn";

	$l[]="";

	return implode("\n",$l);
}

function c_icap_master_status(){
	$sock=$GLOBALS["CLASS_SOCKETS"];

	if(!is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
		if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
			if($GLOBALS["VERBOSE"]){echo "SQUID NOT INSTALLED\n";}
				
			return null;}
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
		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
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

		$master_pid=trim(@file_get_contents("/var/run/c-icap/c-icap.pid"));

		$l[]="[C-ICAP]";
		$l[]="service_name=APP_C_ICAP";
		$l[]="master_version=".GetVersionOf("c-icap");
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

function sarg(){

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSargGenerator");
	if(!is_numeric($enabled)){$enabled=0;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("sarg");
	if(!is_file($Masterbin)){return;}



	$l[]="[APP_SARG]";
	$l[]="service_name=APP_SARG";
	$l[]="master_version=".sarg_version();
	$l[]="service_cmd=/etc/init.d/sarg";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}
	$l[]="running=1";
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]=GetMemoriesOf($master_pid);
	}
	$l[]="";
	return implode("\n",$l);return;
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

function squid_db(){

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseArticaDB");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/squid-db.pid";
	$master_pid=squid_db_pid();
	$EnableSquidRemoteMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){$enabled=0;}

	$l[]="[APP_SQUID_DB]";
	$l[]="service_name=APP_SQUID_DB";
	$l[]="master_version="._MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/squid-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.squid-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix start articadb >/dev/null 2>&1 &");
			squid_watchdog_events("Artica MySQL statistics did not running, start it...");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db stop >/dev/null 2>&1 &");
		}
	}
	if($enabled==1){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/mysqld/squid-db.sock")){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-db restart >/dev/null 2>&1 &");
		}
	}



	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	if($enabled==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid-db.php --databasesize >/dev/null 2>&1 &");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid-db.php --statistics >/dev/null 2>&1 &");
	}

	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time");
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
	$sock=$GLOBALS["CLASS_SOCKETS"];
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is not installed\n";}
		return null;
	}else{if($GLOBALS["VERBOSE"]){echo __FUNCTION__." squid is installed\n";}}


	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}
	exec("{$GLOBALS["BASE_ROOT"]}/bin/artica-install --export-version squid 2>&1",$results);
	$version=trim(implode("",$results));
	if($SQUIDEnable==1){
		if(preg_match("#^2\.6#", $version)){
			if($GLOBALS["VERBOSE"]){echo "#^2\.6# detected in $version, squid is disabled...\n";}
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",0);
			$SQUIDEnable=0;
		}
	}
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


	if(!$buildcache){
		if($GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/databases/ALL_SQUID_STATUS")>10){
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
	squid_tasks($DisableStats);
	$notiftime="/etc/artica-postfix/squid-failed-notiftime";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
			$logs[]="pgrep:";
			exec("$pgrep -l -f \"$sqdbin\" 2>&1",$logs);
			if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
			$notiftimeex=$GLOBALS["CLASS_UNIX"]->file_time_min($notiftime);
			if($notiftimeex>5){
				squid_admin_mysql(0,"Proxy service Stopped",
				"launch the start procedure...\nNotif time:$notiftimeex\n$executed\n".@implode("\n", $logs),__FILE__,__LINE__);
				squid_admin_notifs("Squid seem stopped, launch the start procedure...\nNotif time:$notiftimeex\n$executed\n".@implode("\n", $logs), __FUNCTION__, __FILE__, __LINE__, "proxy");
				@unlink($notiftime)	;
				@file_put_contents($notiftime, time());
			}
			squid_watchdog_events("Start Watchdog! Starting Squid cache Daemon -> /exec.squid.watchdog.php --init...");
			shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.squid.watchdog.php --init >/dev/null 2>&1");
			
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
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logfile_daemon-parse.php --tables-primaires >/dev/null 2>&1 &");
	$GLOBALS["CLASS_UNIX"]->Winbindd_privileged_SQUID();
	
	
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
	if($GLOBALS["VERBOSE"]){echo "Warning !!!!!! cannot find version in $squidbin ! !!\n";}
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
	if(system_is_overloaded()){return;}
	include_once(dirname(__FILE__)."/class.squid.automatic-tasks.inc");
	$t=new squid_auto_tasks();
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
function sarg_version(){
	if(isset($GLOBALS["SARGVERSION"])){return $GLOBALS["SARGVERSION"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("sarg");
	exec("$Masterbin -v 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#SARG Version:\s+([0-9\.]+)#",$ligne,$re)){
			$GLOBALS["SARGVERSION"]=$re[1];
			return $GLOBALS["SARGVERSION"];
		}
	}

}
function ufdbguard_pid(){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/urlfilterdb/ufdbguardd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
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

function articadb(){
	$DIR_COUNT_OF_FILES=$GLOBALS["CLASS_UNIX"]->DIR_COUNT_OF_FILES("/home/artica/categories_databases");
	if($DIR_COUNT_OF_FILES<140){
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.blacklists.php.cicap_artica.time");
		if($CacheSchedules>60){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.squid.blacklists.php --cicap-dbs --notime >/dev/null 2>&1 &");
		}
	}
	
	$timePid="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["CLASS_UNIX"]->file_time_min($timePid)<10){return;}
	@unlink($timePid);
	@file_put_contents($timePid, time());
	
	$PRC=0;
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY")){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.squid.blacklists.php --get-version >/dev/null 2>&1 &");
		return;
		
	}
	
	if(!is_file("/etc/artica-postfix/univtoulouse-global_usage")){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.update.squid.tlse.php --refresh-index >/dev/null 2>&1 &");
		
	}
	
	$array=unserialize(base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY")));
	unset($array["TIME"]);
	$MAX=count($array);
	
	if($MAX==0){
		echo "No item !\n";
		return;}
	$c=0;
	while (list ($table, $items) = each ($array) ){
		if(!is_file("/home/artica/categories_databases/$table.db")){
			echo "/home/artica/categories_databases/$table.db no such file\n";
			continue;}
		$c++;
		
		$prc=intval($c)/intval($MAX);
		$prc=round($prc*100);
		$ARRAY["CAT_STATS_COUNT"]=$c;
		if($GLOBALS["VERBOSE"]){echo "$c / $MAX = {$prc}%\n";}
	}
	
	
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/ARTICA_STAT_DB_SIZE")>60){
		$ARTICA_STAT_DB_SIZE=$GLOBALS["CLASS_UNIX"]->DIRSIZE_KO("/home/artica/categories_databases");
		@unlink("/etc/artica-postfix/ARTICA_STAT_DB_SIZE");
		@file_put_contents("/etc/artica-postfix/ARTICA_STAT_DB_SIZE", $ARTICA_STAT_DB_SIZE);
	}
	
	$ARRAY["CAT_STATS_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/ARTICA_STAT_DB_SIZE"));
	$ARRAY["CAT_STATS_PERCENT"]=$prc;
	$ARRAY=ArticaUfdb($ARRAY);
	$ARRAY=ArticaWebFilter($ARRAY);
	print_r($ARRAY);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS.db", serialize($ARRAY));
	@chmod("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS.db",0755);


}
function ArticaWebFilter($ARRAY){
	if(trim(@file_get_contents("/usr/local/share/artica/.lic"))<>"TRUE"){
		if($GLOBALS["VERBOSE"]){echo "NO LICENSE...\n";}
		return $ARRAY;}
	$REMOTE_CACHE=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	$WORKDIR="/var/lib/ufdbartica";
	$MAX=count($REMOTE_CACHE);
	$c=0;
	$prc=0;
	$ARRAY["CAT_ARTICA_MAX"]=$MAX;
	while (list ($tablename, $size) = each ($REMOTE_CACHE) ){
		
		$destfile="$WORKDIR/$tablename/domains.ufdb";
		$size=@filesize($destfile);
		if($size<10){continue;}
		$c++;
		$prc=intval($c)/intval($MAX);
		$prc=round($prc*100);
		$ARRAY["CAT_ARTICA_COUNT"]=$c;
		$ARRAY["CAT_ARTICA_PRC"]=$prc;
		if($GLOBALS["VERBOSE"]){echo "ArticaWebFilter:: $destfile $c / $MAX = {$prc}% {$size}Bytes\n";}
	}
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CAT_ARTICA_DB_SIZE")>60){
		$CAT_ARTICA_DB_SIZE=$GLOBALS["CLASS_UNIX"]->DIRSIZE_KO("/var/lib/ufdbartica");
		@unlink("/etc/artica-postfix/CAT_ARTICA_DB_SIZE");
		@file_put_contents("/etc/artica-postfix/CAT_ARTICA_DB_SIZE", $CAT_ARTICA_DB_SIZE);
	}
	
	
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$ARRAY["CAT_ARTICA_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/CAT_ARTICA_DB_SIZE"));
	$ARRAY["CAT_ARTICA_SINCE"]=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($STATUS["LAST_DOWNLOAD"]["TIME"],time());
	$ARRAY["CAT_ARTICA_LAST_CAT"]=$STATUS["LAST_DOWNLOAD"]["CATEGORY"];
	$ARRAY["CAT_ARTICA_LAST_SIZE"]=$STATUS["LAST_DOWNLOAD"]["SIZE"];
	$ARRAY["CAT_ARTICA_LAST_ERROR"]=$STATUS["LAST_DOWNLOAD"]["FAILED"];
	
	
	return $ARRAY;
	
}



function ArticaUfdb($ARRAY){
	$DB=array();
	$FILEDBS=array();
	$prc=0;
	$ARRAY["TLSE_PRC"]=0;
	include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
	if($GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard()==0){
		$ARRAY["TLSE_ENABLED"]=0;
		return $ARRAY;
	}
	
	$ARRAY["TLSE_ENABLED"]=1;
	if(is_file("/etc/artica-postfix/univtoulouse-global_usage")){
		$contentF=explode("\n",@file_get_contents("/etc/artica-postfix/univtoulouse-global_usage"));
		while (list ($index, $line) = each ($contentF) ){
			if(preg_match("#NAME:\s+(.+)#", $line,$re)){
				$DB[trim($re[1])]=trim($re[1]);
			}
		}
	}
	if(count($DB)>0){
		$q=new mysql_squid_builder();
		$TLSE_CONVERTION=$q->TLSE_CONVERTION();
		while (list ($TLSE, $line) = each ($DB) ){
			$catzname=$TLSE_CONVERTION[$TLSE];
			if($catzname==null){
				if($GLOBALS["VERBOSE"]){echo "Unable to understand $TLSE\n";}
				continue;
			}
			$catzname=str_replace("/", "_", $catzname);
			$FILEDBS[$catzname]="/var/lib/ftpunivtlse1fr/$catzname/domains.ufdb";
			$FILEDBSC[]="/var/lib/ftpunivtlse1fr/$catzname/domains";
			
		}
		
	}
	if(count($FILEDBS)>0){
		$c=0;
		
		$MAX=count($FILEDBS);
		
		while (list ($table, $path) = each ($FILEDBS) ){
			if(!is_file($path)){
				echo "$path such file\n";continue;}
				$size=@filesize($path);
				if($size<10){continue;}
				$c++;
				$prc=intval($c)/intval($MAX);
				$prc=round($prc*100);
				$ARRAY["TLSE_COUNT"]=$c;
				
			}
		
	}
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE")>60){
		$UNIVTLSE_STAT_DB_SIZE=$GLOBALS["CLASS_UNIX"]->DIRSIZE_KO("/var/lib/ftpunivtlse1fr");
		@unlink("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE");
		@file_put_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE", $UNIVTLSE_STAT_DB_SIZE);
	}
	
	$C=0;
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS")>60){
		while (list ($table, $path) = each ($FILEDBSC) ){
			$unix=new unix();
			$C=$C+intval($unix->COUNT_LINES_OF_FILE($path));
			
		}
		@unlink("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS");
		@file_put_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS", $C);
	}
	
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD"));
	$ARRAY["TLSE_PRC"]=$prc;
	$ARRAY["TLSE_STAT_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE"));
	$ARRAY["TLSE_STAT_ITEMS"]=trim(@file_get_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS"));
	$ARRAY["TLSE_LAST_SINCE"]=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($STATUS["LAST_DOWNLOAD"]["TIME"],time());
	$ARRAY["TLSE_LAST_CAT"]=$STATUS["LAST_DOWNLOAD"]["CATEGORY"];
	$ARRAY["TLSE_LAST_SIZE"]=$STATUS["LAST_DOWNLOAD"]["SIZE"];
	return $ARRAY;
}

