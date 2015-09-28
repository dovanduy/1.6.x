<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');



if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();

$users=new usersMenus();
if($GLOBALS["VERBOSE"]){
	if(!$users->AsProxyMonitor){
		echo "<H1>AsProxyMonitor = FALSE</H1>";
		return;
	
	}else{
		echo "<H1>AsProxyMonitor = TRUE</H1>";
	}
}
if(!$users->AsProxyMonitor){header("location:miniadm.logon.php");}

if(isset($_GET["graph0"])){graph0();exit;}
if(isset($_GET["tabs-service"])){monitor_section();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["watchdog"])){watchdog();exit;}
if(isset($_GET["watchdog-params"])){watchdog_params();exit;}
if(isset($_POST["watchdog"])){watchdog_save();exit;}
if(isset($_GET["watchdog-events"])){watchdog_events();exit;}
if(isset($_GET["watchdog-events-search"])){watchdog_events_search();exit;}
if(isset($_GET["cache-perfs"])){cache_perfs();exit;}
if(isset($_GET["all-services-status"])){all_services_status();exit;}
if(isset($_GET["features"])){features();exit;}


main_page();
exit;


if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function content(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{proxy_monitor}</H1>
	<p>{proxy_monitor_text}</p>
	<div id='$t-status'></div>
	<script>
		
		LoadAjax('$t-status','$page?tabs=yes');
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);


}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}	
	
	$t=time();
	$boot=new boostrap_form();
	$mini=new miniadm();
	$users=new usersMenus();
	$array["{features}"]="$page?features=yes";
	$array["{all_services_status}"]="$page?tabs-service=yes";
	$array["{watchdog}"]="$page?watchdog=yes";
	$array["{realtime_requests}"]="miniadmin.proxy.access.php?tabs=yes";
	$array["{sessions}"]="miniadmin.proxy.monitor.sessions.php?section=yes";
	
	
	
	$array["{keywords}"]="miniadmin.proxy.keywords.php";
	echo $boot->build_tab($array);	
}
function watchdog(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$mini=new miniadm();
	$users=new usersMenus();
	$array["{parameters}"]="$page?watchdog-params=yes";
	$array["{watchdog_events}"]="miniadmin.proxy.events.php?watchdog-events=yes";
	
	
	echo $boot->build_tab($array);	
	
}

function monitor_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$mini=new miniadm();	
	$array["{status}"]="$page?all-services-status=yes";
	$array["{performances}"]="miniadm.prxy.monitor.php?proxy-service=yes&size=1600";
	$array["{cache_performance}"]="$page?cache-perfs=yes";
	$array["{service_events}"]="miniadmin.proxy.events.php?tabs=yes";
	echo $boot->build_tab($array);
}

function watchdog_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$tcp=new networking();
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
	$ALL_IPS_GET_ARRAY[null]="{none}";
	//echo base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig"));
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	
	if(!isset($MonitConfig["SWAP_MONITOR"])){$MonitConfig["SWAP_MONITOR"]=1;}
	if(!isset($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
	if(!isset($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
	if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!isset($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!isset($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!isset($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	if(!isset($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	if(!isset($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!isset($MonitConfig["RestartWhenCrashes"])){$MonitConfig["RestartWhenCrashes"]=1;}
	if(!isset($MonitConfig["DisableWebFilteringNetFailed"])){$MonitConfig["DisableWebFilteringNetFailed"]=1;}
	
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!isset($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!isset($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	
	if(!is_numeric($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
	if(!is_numeric($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
	
	if(!is_numeric($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!is_numeric($MonitConfig["WEBPROCISSUE"])){$MonitConfig["WEBPROCISSUE"]=3;}
	
	
	
	if(!is_numeric($MonitConfig["DisableWebFilteringNetFailed"])){$MonitConfig["DisableWebFilteringNetFailed"]=1;}
	
	
		
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
	
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	
	if(!is_numeric($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=0;}
	

	if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
	if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	if($MonitConfig["DNSIssuesMAX"]==0){$MonitConfig["DNSIssuesMAX"]=1;}
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	if(!is_numeric($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!is_numeric($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!is_numeric($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	if(!is_numeric($MonitConfig["MinFreeMem"])){$MonitConfig["MinFreeMem"]=50;}
	
	if(!is_numeric($MonitConfig["RestartWhenCrashes"])){$MonitConfig["RestartWhenCrashes"]=1;}

	
	
	
	
	
	if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
	if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	
	
	
	if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
	if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	
	
	$ExternalPageToCheck=$MonitConfig["ExternalPageToCheck"];
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	if($MonitConfig["REBOOT_INTERVAL"]<10){$MonitConfig["REBOOT_INTERVAL"]=10;}
	if($MonitConfig["MinTimeFailOverSwitch"]<5){$MonitConfig["MinTimeFailOverSwitch"]=5;}
	
	if($MonitConfig["PING_GATEWAY"]==null){
		$PING_GATEWAY=null;
		$TCP_NICS_STATUS_ARRAY=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
		if(isset($TCP_NICS_STATUS_ARRAY["eth0"])){
			$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth0"]["GATEWAY"];
		}
		if($PING_GATEWAY==null){
			if(isset($TCP_NICS_STATUS_ARRAY["eth1"])){
				$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth1"]["GATEWAY"];
			}	
		}	
		$MonitConfig["PING_GATEWAY"]=$PING_GATEWAY;
	}
	
	
	//FATAL: kid3 registration timed out
	
	
	$MONIT_INSTALLED=0;
	$users=new usersMenus();
	if($users->MONIT_INSTALLED){$MONIT_INSTALLED=1;}
	$SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
	if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}
	
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){$users=new usersMenus();$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";}
	$t=time();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}	
	if(!isset($UfdbguardSMTPNotifs["smtp_server_name"])){$UfdbguardSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_user"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_passwd"])){$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
	if(!isset($UfdbguardSMTPNotifs["tls_enabled"])){$UfdbguardSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
	if(!isset($UfdbguardSMTPNotifs["ssl_enabled"])){$UfdbguardSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=25;}
	
	if(!isset($MonitConfig["ALLOW_RETURN_1CPU"])){$MonitConfig["ALLOW_RETURN_1CPU"]=1;}
	if(!is_numeric($MonitConfig["ALLOW_RETURN_1CPU"])){$MonitConfig["ALLOW_RETURN_1CPU"]=1;}
	
	$boot=new boostrap_form();
	$boot->set_checkbox("watchdog","{enable}",$MonitConfig["watchdog"],array("DISABLEALL"=>true));
	$boot->set_checkbox("EnableFailover","{enable} {failover}",$EnableFailover,array("TOOLTIP"=>"{EnableFailover_explain}"));
	$boot->set_field("MinTimeFailOverSwitch", "{failover_ttl} ({minutes})", $MonitConfig["MinTimeFailOverSwitch"],array("TOOLTIP"=>"{failover_ttl_explain}"));
	
	$boot->set_checkbox("ALLOW_RETURN_1CPU","{ALLOW_RETURN_1CPU}",$MonitConfig["ALLOW_RETURN_1CPU"],array("TOOLTIP"=>"{ALLOW_RETURN_1CPU_EXPLAIN}"));
	$boot->set_field("WEBPROCISSUE", "{max_attempts}",$MonitConfig["WEBPROCISSUE"]);
	
	
	$boot->set_checkbox("DisableWebFilteringNetFailed","{DisableWebFilteringNetFailed}",$MonitConfig["DisableWebFilteringNetFailed"],array("TOOLTIP"=>"{DisableWebFilteringNetFailed_explain}"));
	
	

	$boot->set_field("SquidCacheReloadTTL", "{minimum_reload_interval} ({minutes})", $SquidCacheReloadTTL,array("TOOLTIP"=>"{SquidCacheReloadTTL_explain}"));
	$boot->set_field("REBOOT_INTERVAL", "{minimum_reboot_interval} ({minutes})", $MonitConfig["REBOOT_INTERVAL"],array("TOOLTIP"=>"{minimum_reboot_interval_explain}"));
	
	
	
	$boot->set_field("MAX_RESTART", "{SQUID_MAX_RESTART}", $MonitConfig["MAX_RESTART"],array("TOOLTIP"=>"{SQUID_MAX_RESTART_EXPLAIN}"));
	$boot->set_field("MgrInfosMaxTimeOut", "{tests_timeout}  ({seconds})", $MonitConfig["MgrInfosMaxTimeOut"]);
	
	$boot->set_spacertitle("{performance}");
	$boot->set_field("watchdogCPU", "{notify_when_cpu_exceed} %", $MonitConfig["watchdogCPU"]);
	$boot->set_field("watchdogMEM", "{notify_when_memory_exceed} (MB)", $MonitConfig["watchdogMEM"]);
	$boot->set_field("MaxSwapPourc", "{MaxSwapPourc}  (%)", $MonitConfig["MaxSwapPourc"],array("TOOLTIP"=>"{MaxSwapPourc_explain}"));
	$boot->set_field("MaxLoad", "{max_system_load}", $MonitConfig["MaxLoad"],array("TOOLTIP"=>"{max_system_load_squid_explain}"));
	$boot->set_field("MinFreeMem", "{MinFreeMem} MB", $MonitConfig["MinFreeMem"],array("TOOLTIP"=>"{MinFreeMem_squid_explain}"));
	$boot->set_checkbox("MaxLoadFailOver", "{max_system_load_failover}", $MonitConfig["MaxLoadFailOver"],array("TOOLTIP"=>"{max_system_load_failover_explain}"));
	$boot->set_checkbox("MaxLoadReboot", "{max_system_load_reboot}", $MonitConfig["MaxLoadReboot"],array("TOOLTIP"=>"{max_system_load_reboot_explain}"));
	$boot->set_checkbox("RestartWhenCrashes", "{RestartWhenCrashes}", $MonitConfig["RestartWhenCrashes"],array("TOOLTIP"=>"{RestartWhenCrashes_explain}"));
	
	
	$boot->set_spacertitle("SWAP");
	$boot->set_checkbox("SWAP_MONITOR","{enable}",$MonitConfig["SWAP_MONITOR"],array("TOOLTIP"=>"{SWAP_MONITOR_EXPLAIN}"));
	$boot->set_field("SWAP_MIN", "{SWAP_MIN} %", $MonitConfig["SWAP_MIN"],array("TOOLTIP"=>"{SWAP_MIN_EXPLAIN}"));
	$boot->set_field("SWAP_MAX", "{SWAP_MAX} %", $MonitConfig["SWAP_MAX"],array("TOOLTIP"=>"{SWAP_MAX_EXPLAIN}"));
	
	
	
	$boot->set_spacertitle("PING");
	$boot->set_checkbox("ENABLE_PING_GATEWAY","{enable}",$MonitConfig["ENABLE_PING_GATEWAY"],array("TOOLTIP"=>"{ENABLE_PING_GATEWAY_EXPLAIN}"));
	$boot->set_field("MAX_PING_GATEWAY", "{MAX_PING_GATEWAY}", $MonitConfig["MAX_PING_GATEWAY"],array("TOOLTIP"=>"{MAX_PING_GATEWAY_EXPLAIN}"));
	$boot->set_field("PING_GATEWAY", "{ipaddr}", $MonitConfig["PING_GATEWAY"],array("IPV4"=>true));
	$boot->set_checkbox("PING_FAILED_RELOAD_NET","{reload_network}",$MonitConfig["PING_FAILED_RELOAD_NET"],array("TOOLTIP"=>"{PING_FAILED_RELOAD_NET_EXPLAIN}"));
	$boot->set_checkbox("PING_FAILED_REPORT","{send_report}",$MonitConfig["PING_FAILED_REPORT"],array("TOOLTIP"=>"{PING_FAILED_REPORT_EXPLAIN}"));
	$boot->set_checkbox("PING_FAILED_FAILOVER","{switch_to_failover}",$MonitConfig["PING_FAILED_FAILOVER"],array("TOOLTIP"=>"{PING_FAILED_FAILOVER_EXPLAIN}"));
	$boot->set_checkbox("PING_FAILED_REBOOT","{reboot_system}",$MonitConfig["PING_FAILED_REBOOT"],array("TOOLTIP"=>"{reboot_system_explain}"));
	
	
	
	
	$boot->set_spacertitle("DNS");
	$boot->set_checkbox("NotifyDNSIssues","{NotifyDNSIssues}",$MonitConfig["NotifyDNSIssues"],array("TOOLTIP"=>"{NotifyDNSIssues_explain}"));
	$boot->set_field("DNSIssuesMAX", "{DNSIssuesMAX}", $MonitConfig["DNSIssuesMAX"]);

	$boot->set_spacertitle("{external_page}");
	$boot->set_checkbox("TestExternalWebPage","{TestExternalWebPage}",$MonitConfig["TestExternalWebPage"],array("TOOLTIP"=>"{squid_TestExternalWebPage_explain}"));
	$boot->set_field("ExternalPageToCheck", "{page_to_check}", $MonitConfig["ExternalPageToCheck"],array("TOOLTIP"=>"{ExternalPageToCheck_explain}"));
	$boot->set_field("ExternalPageUsername", "{username}", $MonitConfig["ExternalPageUsername"],array("TOOLTIP"=>"{ExternalPageUsername_EXPLAIN}"));
	$boot->set_fieldpassword("ExternalPagePassword", "{password}", $MonitConfig["ExternalPagePassword"],array("TOOLTIP"=>"{ExternalPageUsername_EXPLAIN}","ENCODE"=>TRUE));
	$boot->set_list("ExternalPageListen", "{addr}", $ALL_IPS_GET_ARRAY,$MonitConfig["ExternalPageListen"],array("TOOLTIP"=>"{ExternalPageListen_explain}"));
	

	$boot->set_spacertitle("{smtp_notifications}");
	$boot->set_checkbox("ENABLED_SQUID_WATCHDOG","{smtp_enabled}",$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]);
	$boot->set_field("smtp_server_name", "{smtp_server_name}", $UfdbguardSMTPNotifs["smtp_server_name"]);
	$boot->set_field("smtp_server_port", "{smtp_server_port}", $UfdbguardSMTPNotifs["smtp_server_port"]);
	$boot->set_field("smtp_sender", "{smtp_sender}", $UfdbguardSMTPNotifs["smtp_sender"]);
	$boot->set_field("smtp_dest", "{smtp_dest}", $UfdbguardSMTPNotifs["smtp_dest"]);
	$boot->set_field("smtp_auth_user", "{smtp_auth_user}", $UfdbguardSMTPNotifs["smtp_auth_user"]);
	$boot->set_fieldpassword("smtp_auth_passwd", "{smtp_auth_passwd}", $UfdbguardSMTPNotifs["smtp_auth_passwd"],array("ENCODE"=>true));
	$boot->set_checkbox("tls_enabled","{tls_enabled}",$UfdbguardSMTPNotifs["tls_enabled"]);
	echo $boot->Compile();
}

function watchdog_save(){
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	
	
	
	$MonitConfig["ExternalPagePassword"]=url_decode_special_tool($_POST["ExternalPagePassword"]);
	$MonitConfig["ExternalPageUsername"]=stripslashes($_POST["ExternalPageUsername"]);
	$MonitConfig["watchdog"]=$_POST["watchdog"];
	$MonitConfig["watchdogCPU"]=$_POST["watchdogCPU"];
	$MonitConfig["watchdogMEM"]=$_POST["watchdogMEM"];
	$MonitConfig["SquidCacheReloadTTL"]=$_POST["SquidCacheReloadTTL"];
	$MonitConfig["MgrInfosMaxTimeOut"]=$_POST["MgrInfosMaxTimeOut"];
	$MonitConfig["ExternalPageToCheck"]=$_POST["ExternalPageToCheck"];
	$MonitConfig["MAX_RESTART"]=$_POST["MAX_RESTART"];
	$MonitConfig["TestExternalWebPage"]=$_POST["TestExternalWebPage"];
	$MonitConfig["REBOOT_INTERVAL"]=$_POST["REBOOT_INTERVAL"];
	$MonitConfig["MinTimeFailOverSwitch"]=$_POST["MinTimeFailOverSwitch"];
	$MonitConfig["ALLOW_RETURN_1CPU"]=$_POST["ALLOW_RETURN_1CPU"];
	
	
	$MonitConfig["MaxLoad"]=$_POST["MaxLoad"];
	$MonitConfig["MaxLoadReboot"]=$_POST["MaxLoadReboot"];
	$MonitConfig["MaxLoadFailOver"]=$_POST["MaxLoadFailOver"];
	

	
	$MonitConfig["NotifyDNSIssues"]=$_POST["NotifyDNSIssues"];
	$MonitConfig["DNSIssuesMAX"]=$_POST["DNSIssuesMAX"];
	
	
	$trMAX_PING_GATEWAY=explode(".",$_POST["MAX_PING_GATEWAY"]);
	while (list ($num, $ligne) = each ($trMAX_PING_GATEWAY) ){$trMAX_PING_GATEWAY[$num]=intval($ligne);}
	$_POST["MAX_PING_GATEWAY"]=@implode(".", $trMAX_PING_GATEWAY);
	
	$MonitConfig["ENABLE_PING_GATEWAY"]=$_POST["ENABLE_PING_GATEWAY"];
	$MonitConfig["MAX_PING_GATEWAY"]=$_POST["MAX_PING_GATEWAY"];
	$MonitConfig["PING_GATEWAY"]=$_POST["PING_GATEWAY"];
	$MonitConfig["PING_FAILED_REPORT"]=$_POST["PING_FAILED_REPORT"];
	$MonitConfig["PING_FAILED_REBOOT"]=$_POST["PING_FAILED_REBOOT"];
	$MonitConfig["PING_FAILED_FAILOVER"]=$_POST["PING_FAILED_FAILOVER"];
	$MonitConfig["PING_FAILED_RELOAD_NET"]=$_POST["PING_FAILED_RELOAD_NET"];
	
	
	
	$sock->SET_INFO("SquidCacheReloadTTL",$_POST["SquidCacheReloadTTL"]);
	$sock->SaveConfigFile(base64_encode(serialize($MonitConfig)), "SquidWatchdogMonitConfig");
	
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	
	
	
	$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=$_POST["ENABLED_SQUID_WATCHDOG"];
	$UfdbguardSMTPNotifs["smtp_server_name"]=$_POST["smtp_server_name"];
	$UfdbguardSMTPNotifs["smtp_server_port"]=$_POST["smtp_server_port"];
	$UfdbguardSMTPNotifs["smtp_sender"]=$_POST["smtp_sender"];
	$UfdbguardSMTPNotifs["smtp_dest"]=$_POST["smtp_dest"];
	$UfdbguardSMTPNotifs["smtp_auth_user"]=$_POST["smtp_auth_user"];
	$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$_POST["smtp_auth_passwd"];
	$UfdbguardSMTPNotifs["tls_enabled"]=$_POST["tls_enabled"];
	$sock->SaveConfigFile(base64_encode(serialize($UfdbguardSMTPNotifs)), "UfdbguardSMTPNotifs");
	$sock->getFrameWork("squid.php?restart-cache-tail=yes");
}

function watchdog_events(){
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("search","watchdog-events-search");
	echo $form;	
}
function watchdog_events_search(){
	$sock=new sockets();
	$boot=new boostrap_form();
	$tpl=new templates();
	$rp=$_GET["rp"];
	if(!is_numeric($rp)){$rp=250;}
	
	$search=urlencode($_GET["watchdog-events-search"]);
	$content=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-logs=yes&rp=$rp&search=$search")));
	$boot=new boostrap_form();
	$c=0;
	krsort($content);
	while (list ($num, $ligne) = each ($content) ){
		
	
		if(preg_match("#^(.+?)\s+(.*?)\s+\[([0-9]+)\](.*?)$#", $ligne,$re)){
			$date=$re[1]." ".$re[2];
			$pid=$re[3];
			$ligne=$re[4];
		}
		
		$class=LineToClass($ligne);
		//$link=$boot->trswitch($jslink);
		$ligne=$tpl->javascript_parse_text("$ligne");
		$tr[]="
		<tr class='$class'>
		<td style='font-size:12px;' width=1% nowrap><i class='icon-time'></i>&nbsp;$date</a></td>
		<td style='font-size:12px;'>$pid</td>
		<td style='font-size:12px;'>$ligne</td>
		</tr>";
		
		
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>&nbsp;</th>
					<th>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	
}

function cache_perfs(){
	$t=time();
	$page=CurrentPageName();
	$html="
	<div class=BodyContent id='graph0-$t' style='height:600px'></div>

	
	
	<script>
	AnimateDiv('graph0-$t');

	function Start1$t(){
	Loadjs('$page?graph0=yes&container=graph0-$t$suffix');
	}
	
	setTimeout('Start1$t()',500);
	
	</script>
	";
	
	echo $html;
	
		
	
	
}

function all_services_status(){
	$t=time();
	$page=CurrentPageName();
	$html="
	<div style='width:100%;text-align:right;float:right;'>". imgtootltip("refresh-48.png",null,"LoadAjax('$t','squid.main.quicklinks.php?squid-services=yes&miniadmin=yes')")."</div>
	<div id='$t' style='width:100%'></div>		
			
	<script>
		LoadAjax('$t','squid.main.quicklinks.php?squid-services=yes&miniadmin=yes');
	</script>

	";
	
	
	echo $html;
}
function all_services_status_build(){
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();

	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}	
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	
	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$kav=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$cicap=DAEMON_STATUS_ROUND("C-ICAP",$ini,null,1);
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("APP_PROXY_PAC",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$APP_FRESHCLAM=DAEMON_STATUS_ROUND("APP_FRESHCLAM",$ini,null,1);
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_HAARP",$ini,null,1);
	if($users->PROXYTINY_APPLIANCE){$APP_ARTICADB=null;}
	if($EnableRemoteStatisticsAppliance==1){$APP_ARTICADB=null;}
	$APP_FTP_PROXY=DAEMON_STATUS_ROUND("APP_FTP_PROXY",$ini,null,1);
	$squid=new squidbee();
	
	
	if($EnableKerbAuth==1){
		$APP_SAMBA_WINBIND=DAEMON_STATUS_ROUND("SAMBA_WINBIND",$ini,null,1);
	}	
	$tr[]="<div id='squid-mem-status'></div><script>LoadAjaxTiny('squid-mem-status','$page?squid-mem-status=yes');</script>";
	$tr[]="<div id='squid-stores-status'></div><script>LoadAjaxTiny('squid-stores-status','$page?squid-stores-status=yes');</script>";
	
	

	
	
	$md=md5(date('Ymhis'));
	if(!$users->WEBSTATS_APPLIANCE){
		$swappiness=intval($sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.swappiness")));
		$sock=new sockets();
		$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		if(!is_numeric($swappiness_saved["swappiness"])){
			if($swappiness>30){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{high_swap_value}",
				"{high_swap_value_text}","Loadjs('squid.perfs.php')");
			}
			
		}
		
		if($AsSquidLoadBalancer==1){$SquidAsSeenDNS=1;}
		if(!$users->IsSquidReverse()){
			$SquidAsSeenDNS=$sock->GET_INFO("SquidAsSeenDNS");
			if(!is_numeric($SquidAsSeenDNS)){$SquidAsSeenDNS=0;}
			if( count($squid->dns_array)==0){
				if($SquidAsSeenDNS==0){
					$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{add_dns_in_config}",
					"{add_dns_in_config_perf_explain}","Loadjs('squid.popups.php?script=dns')");
				}
			}
			
		}
	}
	
	
	$CicapEnabled=0;
	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	}
	
	
	
	
	$squid_status=null;
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('squid.php?smp-status=yes')));
		
	while (list ($index, $line) = each ($ini->_params) ){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::$index -> DAEMON_STATUS_ROUND<br>\n";}
		$tr[]=DAEMON_STATUS_ROUND($index,$ini,null,1);
	}
		
	
	

	
	if($SquidBoosterMem>0){
		
			if($DisableAnyCache==0){
				$tr[]=squid_booster_smp();
			}
		
	}
	
	
	$tr[]=$squid_status;
	$tr[]=$APP_HAARP;
	$tr[]=$APP_SAMBA_WINBIND;
	$tr[]=$dansguardian_status;
	$tr[]=$kav;
	$tr[]=$cicap;
	$tr[]=$APP_PROXY_PAC;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$APP_FRESHCLAM;
	$tr[]=$APP_ARTICADB;
	$tr[]=$APP_SQUID_DB;
	$tr[]=$APP_FTP_PROXY;
	
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}
	
	echo CompileTr3($tr,true);
	
	
}
function squid_booster_smp(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?smp-booster-status=yes")));
	if(count($array)==0){return;}
	$html[]="
			<div style='min-height:115px'>
			<table>
			<tr><td colspan=2 style='font-size:14px;font-weight:bold'>Cache(s) Booster</td></tr>
			";
	while (list ($proc, $pourc) = each ($array)){
		$html[]="<tr>
		<td width=1% nowrap style='font-size:13px;font-weight:bold'>Proc #$proc</td><td width=1% nowrap>". pourcentage($pourc)."</td></tr>";
	}
	$html[]="</table></div>";

	return RoundedLightGreen(@implode("\n", $html));
}

function graph0(){
		$q=new mysql_squid_builder();
		$sql="SELECT size AS size,cached, zDate FROM `cached_total` ORDER BY zDate";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ARRAY[$ligne["zDate"]][$ligne["cached"]]=$ligne["size"];

	
		}
		
		if(!is_array($ARRAY)){$ARRAY=array();}
		
		while (list ($day, $array) = each ($ARRAY) ){
			if(!is_numeric($array[1])){continue;}
			if(!is_numeric($array[0])){continue;}
			if($array[1]==0){continue;}
			if($array[0]==0){continue;}
			
			
			$pourc=round(($array[0]/$array[1])*100,2);
			$date=strtotime($day."00:00:00");
			$xdata[]=date("m-d",$date);
			$ydata[]=$pourc;
		}
		

		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->xAxis=$xdata;
		$highcharts->Title="{performance} % / {day}";
		$highcharts->yAxisTtitle="%";
		$highcharts->xAxisTtitle="{days}";
		$highcharts->datas=array("%"=>$ydata);
		echo $highcharts->BuildChart();
}

function features(){
	$sock=new sockets();
	$users=new usersMenus();
	$check="42-green.png";
	$uncheck="42-red.png";
	$squid=new squidbee();
	$INTEGER[0]=$uncheck;
	$INTEGER[1]=$check;
	$INTEGER[-1]="42-green-grey.png";
	$tpl=new templates();
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	$SquidGuardIPWeb=trim($sock->GET_INFO("SquidGuardIPWeb"));
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidDisableAllFilters=$sock->GET_INFO("SquidDisableAllFilters");
	$SquideCapAVEnabled=$sock->GET_INFO("SquideCapAVEnabled");
	$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
	$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
	$PdnsHotSpot=$sock->GET_INFO("EnableSplashScreen");
	$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	$UfdbEnabledCentral=$sock->GET_INFO('UfdbEnabledCentral');
	$AntivirusEnabledCentral=$sock->GET_INFO('AntivirusEnabledCentral');
	$EnableKerbAuthCentral=$sock->GET_INFO('EnableKerbAuthCentral');
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$DnsFilterCentral=$sock->GET_INFO('DnsFilterCentral');
	$SquidBubbleMode=$sock->GET_INFO('SquidBubbleMode');
	
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");

		
	
		
	
	$EnableFTPProxy=$sock->GET_INFO('EnableFTPProxy');
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$Watchdog=$MonitConfig["watchdog"];
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
	
	$EnableHaarp=$sock->GET_INFO("EnableHaarp");
	if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}
	
	// APP_HAARP $EnableHaarp
	
	
	
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}
	$PDSNInUfdb=$sock->GET_INFO("PDSNInUfdb");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($SquideCapAVEnabled)){$SquideCapAVEnabled=0;}
	if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
	if(!is_numeric($SquidDisableAllFilters)){$SquidDisableAllFilters=0;}
	if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
	if(!is_numeric($PdnsHotSpot)){$PdnsHotSpot=0;}
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}
	if(!is_numeric($SquidBubbleMode)){$SquidBubbleMode=0;}
	
	if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}	
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){$EnableCNTLM=0;}
	
	
	
	$isNGnx=0;
	if(!$users->CNTLM_INSTALLED){$EnableCNTLM=-1;}
	if(!$users->APP_FTP_PROXY){$EnableFTPProxy=-1;}
	if(!$users->SAMBA_INSTALLED){$EnableKerbAuth=-1;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=-1;}
	if(!$users->KAV4PROXY_INSTALLED){$kavicapserverEnabled=-1;}
	if(!$users->HAARP_INSTALLED){$EnableHaarp=-1;}
	if($squid->isNGnx()){$isNGnx=1;}
	if(!$users->NGINX_INSTALLED){$isNGnx=-1;}
	if($users->C_ICAP_INSTALLED){$CicapEnabled=-1;}
	
	$LICENSE=0;
	if($users->CORP_LICENSE){$LICENSE=1;}
	
	$ARRAY["CORP_LICENSE"]["TITLE"]="{artica_license}";
	$ARRAY["CORP_LICENSE"]["EXPL"]="{license_proxy_benefits}";
	$ARRAY["CORP_LICENSE"]["ICON"]=$INTEGER[$LICENSE];	
	
	
	
	$ARRAY["transparent"]["TITLE"]="Active Directory";
	$ARRAY["transparent"]["EXPL"]="{squid_ad_benefits}";
	$ARRAY["transparent"]["ICON"]=$INTEGER[$EnableKerbAuth];	
	
	$ARRAY["EnableCNTLM"]["TITLE"]="{APP_CNTLM}";
	$ARRAY["EnableCNTLM"]["EXPL"]="{APP_CNTLM_EXPLAIN}";
	$ARRAY["EnableCNTLM"]["ICON"]=$INTEGER[$EnableCNTLM];
	
	$ARRAY["DisableAnyCache"]["TITLE"]="{caches} {disk}";
	$ARRAY["DisableAnyCache"]["EXPL"]="{DisableAnyCache_explain2}";
	$ARRAY["DisableAnyCache"]["ICON"]=$INTEGER[$DisableAnyCache];
	
	$ARRAY["SquidBubbleMode"]["TITLE"]="Bubble";
	$ARRAY["SquidBubbleMode"]["EXPL"]="{bubble_mode_explain}";	
	$ARRAY["SquidBubbleMode"]["ICON"]=$INTEGER[$SquidBubbleMode];
	
	$ARRAY["EnableHaarp"]["TITLE"]="{APP_HAARP}";
	$ARRAY["EnableHaarp"]["EXPL"]="{APP_HAARP_EXPLAIN}";
	$ARRAY["EnableHaarp"]["ICON"]=$INTEGER[$EnableHaarp];	


	
	$ARRAY["isNGnx"]["TITLE"]="{squid_reverse_proxy}";
	$ARRAY["isNGnx"]["EXPL"]="{nginx_benefits}";
	$ARRAY["isNGnx"]["ICON"]=$INTEGER[$isNGnx];	
	

	
	$ARRAY["EnableRemoteStatisticsAppliance"]["TITLE"]="{use_stats_appliance}";
	$ARRAY["EnableRemoteStatisticsAppliance"]["EXPL"]="{STATISTICS_APPLIANCE_EXPLAIN}";
	$ARRAY["EnableRemoteStatisticsAppliance"]["ICON"]=$INTEGER[$EnableRemoteStatisticsAppliance];

	
	$ARRAY["EnableFTPProxy"]["TITLE"]="FTP Proxy";
	$ARRAY["EnableFTPProxy"]["EXPL"]="{FTP_PROXY_EXPLAIN}";
	$ARRAY["EnableFTPProxy"]["ICON"]=$INTEGER[$EnableFTPProxy];	
	
	$ARRAY["EnableFTPProxy"]["TITLE"]="FTP Proxy";
	$ARRAY["EnableFTPProxy"]["EXPL"]="{FTP_PROXY_EXPLAIN}";
	$ARRAY["EnableFTPProxy"]["ICON"]=$INTEGER[$EnableFTPProxy];	
	
	$ARRAY["EnableUfdbGuard"]["TITLE"]="{webfilter_engine}";
	$ARRAY["EnableUfdbGuard"]["EXPL"]="{webfilter_engine_benefits}";
	$ARRAY["EnableUfdbGuard"]["ICON"]=$INTEGER[$EnableUfdbGuard];	
	
	$ARRAY["kavicapserverEnabled"]["TITLE"]="Kaspersky For Proxy server";
	$ARRAY["kavicapserverEnabled"]["EXPL"]="{kav4proxy_about}";
	$ARRAY["kavicapserverEnabled"]["ICON"]=$INTEGER[$kavicapserverEnabled];	
	
	$ARRAY["CicapEnabled"]["TITLE"]="{cicap_title}";
	$ARRAY["CicapEnabled"]["EXPL"]="{enable_c_icap_text}";
	$ARRAY["CicapEnabled"]["ICON"]=$INTEGER[$CicapEnabled];	
	

	while (list ($day, $array) = each ($ARRAY) ){
		$title=$tpl->_ENGINE_parse_body($array["TITLE"]);
		$explain=$tpl->_ENGINE_parse_body($array["EXPL"]);
		$tr[]="
		<tr id='$id'>
		<td $link nowrap style='font-size:16px;font-weight:bolder'><i class='icon-info-sign'></i> $title</a></td>
		<td $link>$explain</td>
		<td $link><img src='img/{$array["ICON"]}'></td>
		</tr>";	
		
	}
	
	

	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th>{feature}</th>
					<th>{explain}</th>
					<th>{status}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	
	
	
}
