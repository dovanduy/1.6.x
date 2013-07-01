<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
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
	$array["{service_status}"]="$page?tabs-service=yes";
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
	$array["{status}"]="squid.main.quicklinks.php?status=yes";
	$array["{performances}"]="prxy.monitor.php?proxy-service=yes&size=1600";
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
	//print_r($MonitConfig);
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=10;}
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	$ExternalPageToCheck=$MonitConfig["ExternalPageToCheck"];
	
	if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!is_numeric($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
	if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	if($MonitConfig["DNSIssuesMAX"]==0){$MonitConfig["DNSIssuesMAX"]=1;}
	
	
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
	
	if(!isset($UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"])){$UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"]=1;}
	if(!is_numeric($UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"])){$UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"]=1;}
	
	$boot=new boostrap_form();
	$boot->set_checkbox("watchdog","{enable}",$MonitConfig["watchdog"],array("DISABLEALL"=>true));
	$boot->set_checkbox("EnableFailover","{enable} {failover}",$EnableFailover,array("TOOLTIP"=>"{EnableFailover_explain}"));
	$boot->set_checkbox("ALLOW_RETURN_1CPU","{ALLOW_RETURN_1CPU}",$UfdbguardSMTPNotifs["ALLOW_RETURN_1CPU"],array("TOOLTIP"=>"{ALLOW_RETURN_1CPU_EXPLAIN}"));
	
	
	$boot->set_field("watchdogCPU", "{notify_when_cpu_exceed} %", $MonitConfig["watchdogCPU"]);
	$boot->set_field("watchdogMEM", "{notify_when_memory_exceed} (MB)", $MonitConfig["watchdogMEM"]);
	$boot->set_field("SquidCacheReloadTTL", "{minimum_reload_interval} ({minutes})", $SquidCacheReloadTTL,array("TOOLTIP"=>"{SquidCacheReloadTTL_explain}"));
	
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	
	
	
	$boot->set_field("MAX_RESTART", "{SQUID_MAX_RESTART}", $MonitConfig["MAX_RESTART"],array("TOOLTIP"=>"{SQUID_MAX_RESTART_EXPLAIN}"));
	$boot->set_checkbox("TestExternalWebPage","{TestExternalWebPage}",$MonitConfig["TestExternalWebPage"],array("TOOLTIP"=>"{squid_TestExternalWebPage_explain}"));
	$boot->set_field("MgrInfosMaxTimeOut", "{tests_timeout}  ({seconds})", $MonitConfig["MgrInfosMaxTimeOut"]);
	$boot->set_field("MaxSwapPourc", "{MaxSwapPourc}  (%)", $MonitConfig["MaxSwapPourc"],array("TOOLTIP"=>"{MaxSwapPourc_explain}"));
	
	
	$boot->set_checkbox("NotifyDNSIssues","{NotifyDNSIssues}",$MonitConfig["NotifyDNSIssues"],array("TOOLTIP"=>"{NotifyDNSIssues_explain}"));
	$boot->set_field("DNSIssuesMAX", "{DNSIssuesMAX}", $MonitConfig["DNSIssuesMAX"]);

	
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
	
	
	$content=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-logs=yes&rp=$rp")));
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
function graph0(){
		$q=new mysql_squid_builder();
		$sql="SELECT size AS size,cached, zDate FROM `cached_total` ORDER BY zDate";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ARRAY[$ligne["zDate"]][$ligne["cached"]]=$ligne["size"];

	
		}
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