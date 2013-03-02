<?php
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--varrun"){varrun();exit;}

$unix=new unix();
if($argv[1]=="--reload"){reload_dnsmasq();die();}
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
if(!$GLOBALS["FORCE"]){
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		writelogs("Already executed pid $oldpid, aborting...","MAIN",__FILE__,__LINE__);
		die();
	}
	
	$time=$unix->file_time_min($pidtime);
	if($time<2){
		if($time>0){
			writelogs("Current {$time}Mn Requested 2mn, schedule this task","MAIN",__FILE__,__LINE__);
			$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
		}
		die();
	}
}


@unlink($pidtime);
@file_put_contents($pidtime, time());
@file_put_contents($pidfile, getmypid());

$users=new settings_inc();
if(!$users->dnsmasq_installed){writelogs("DNSMasq is not installed, aborting","MAIN",__FILE__,__LINE__);die();}

$sock=new sockets();
$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
if($EnableDNSMASQ==0){writelogs("DNSMasq is not enabled, aborting","MAIN",__FILE__,__LINE__);die();}
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$DNSMasqUseStatsAppliance=$sock->GET_INFO("DNSMasqUseStatsAppliance");
if(!is_numeric($DNSMasqUseStatsAppliance)){$DNSMasqUseStatsAppliance=0;}	
$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
if(is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}


if($EnableRemoteStatisticsAppliance==1){
	if($DNSMasqUseStatsAppliance==1){
		writelogs("DNSMasq -> use Web statistics Appliance...","MAIN",__FILE__,__LINE__);
		UseStatsAppliance();
		die();
	}
}


$dnsmasq=new dnsmasq();
$dnsmasq->SaveConfToServer();
$resolv=new resolv_conf();
$resolvFile=$dnsmasq->main_array["resolv-file"];
$resolvConfBuild=$resolv->build();
@file_put_contents($resolvFile,$resolvConfBuild);
@mkdir("/var/run/dnsmasq",0755,true);
@file_put_contents("/var/run/dnsmasq/resolv.conf",$resolvConfBuild);



shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php dnsmasq");

if($EnableWebProxyStatsAppliance==1){notify_remote_proxys_dnsmasq();}

function UseStatsAppliance(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$sock=new sockets();
	$unix=new unix();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	$unix=new unix();
	$hostname=$unix->hostname_g();	
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/dnsmasq.conf";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("/tmp/dnsmasq.conf")){ufdbguard_admin_events("Failed to download dnsmasq.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"dns-compile");return;}		
	
	$mv=$unix->find_program("mv");
	$cp=unix-find_program("cp");
	$chmod=$unix->find_program("chmod");
	
	shell_exec("$mv /tmp/dnsmasq.conf /etc/dnsmasq.conf");	
	shell_exec("cp /etc/dnsmasq.conf /etc/artica-postfix/settings/Daemons/DnsMasqConfigurationFile");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	
	if(is_file($dnsmasqbin)){
		$pid=$unix->PIDOF($dnsmasqbin);
		if(is_numeric($pid)){
			echo "Starting......: dnsmasq reloading PID:`$pid`\n";
			$kill=$unix->find_program("kill");
			shell_exec("$kill -HUP $pid");
		}
	}	
}

function reload_dnsmasq(){
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
	if($EnableDNSMASQ==0){
		echo "Starting......: dnsmasq unable to reload DnsMASQ (not enabled)\n";
		return ;
	}
	$unix=new unix();
	
	$dnsmasqbin=$unix->find_program("dnsmasq");
	if(is_file(!$dnsmasqbin)){echo "Starting......: dnsmasq unable to reload DnsMASQ (not such dsnmasq binary)\n";return;}
	$pid=$unix->PIDOF($dnsmasqbin);
	if(!is_numeric($pid)){echo "Starting......: dnsmasq unable to reload DnsMASQ (not running)\n";return;}
	
	echo "Starting......: dnsmasq reloading PID:`$pid`\n";
	$kill=$unix->find_program("kill");
	shell_exec("$kill -HUP $pid");
}

function varrun(){
	if(!is_file("/var/run/dnsmasq/resolv.conf")){
		echo "Starting......: /var/run/dnsmasq/resolv.conf no such file\n";
		ResolvConfChecks();
		return;
	}
	$f=file("/var/run/dnsmasq/resolv.conf");
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	if(!$configured){
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: /var/run/dnsmasq/resolv.conf not configured, write it...\n";
		@file_put_contents("/var/run/dnsmasq/resolv.conf", $resolvConfBuild);
		reload_dnsmasq();
	}
	ResolvConfChecks();
}

function ResolvConfChecks(){
	$unix=new unix();
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}	
	$f=file("/etc/resolv.conf");
	$dnsmasqbin=$unix->find_program("dnsmasq");
	$configured=false;
	while (list ($dir, $line) = each ($f) ){
		if(preg_match("#^nameserver.+#",$line, $re)){$configured=true;}
	}
	
	
	if($configured){return;}
		
	if(file_exists($dnsmasqbin)){
		if($EnableDNSMASQ==0){
			$resolv=new resolv_conf();
			$resolvConfBuild=$resolv->build();
			echo "Starting......: /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
		}
		if($EnableDNSMASQ==1){
			reset($f);
			$f[]="nameserver 127.0.0.1";
			echo "Starting......: /etc/resolv.conf not configured, write it...\n";
			@file_put_contents("/etc/resolv.conf", $resolvConfBuild);			
			reload_dnsmasq();
		}
	}else{
		$resolv=new resolv_conf();
		$resolvConfBuild=$resolv->build();
		echo "Starting......: /etc/resolv.conf not configured, write it...\n";
		@file_put_contents("/etc/resolv.conf", $resolvConfBuild);
	}
	
	
}


function notify_remote_proxys_dnsmasq(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM squidservers";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		writelogs("remote server $server:$port",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="DNSMASQ";
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error for DNSMASQ");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration for DNSMASQ");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data for DNSMASQ");
	}
}
