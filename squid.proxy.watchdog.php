<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){die();}
	if(isset($_GET["SWAP"])){SWAP_PAGE();exit;}
	if(isset($_GET["PING"])){PING_PAGE();exit;}
	if(isset($_GET["DNS"])){DNS_PAGE();exit;}
	if(isset($_GET["external-page"])){EXTERNAL_PAGE();exit;}
	if(isset($_GET["performance"])){PERFORMANCE_PAGE();exit;}
	if(isset($_GET["settings"])){SETTINGS_PAGE();exit;}
	if(isset($_GET["smtp"])){SMTP_PAGE();exit;}
	if(isset($_POST["SAVEGLOBAL"])){SAVE();exit;}
	if(isset($_POST["SAVESMTP"])){SAVE_SMTP();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	
js();

function js(){
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{watchdog_settings}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin4('850','$page?tabs=yes','$title')";
	
	
}


function tabs(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["settings"]="{global_settings}";
	$array["performance"]="{performance}";
	$array["SWAP"]="SWAP";
	$array["PING"]="PING";
	$array["DNS"]="DNS";
	$array["external-page"]="{external_page}";
	$array["smtp"]="{smtp_notifications}";
	$style="style='font-size:16px';";
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $style>$ligne</span></a></li>\n");
		
	}
	
	
	
	
	echo build_artica_tabs($html, "watchdog_settings_tabs");
	
	
	
}

function defaults_values(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){$users=new usersMenus();$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";}
	$t=time();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	
	
	if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!isset($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!isset($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!isset($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	if(!isset($MonitConfig["MinFreeMem"])){$MonitConfig["MinFreeMem"]=50;}
	if(!isset($MonitConfig["MgrInfosRestartFailed"])){$MonitConfig["MgrInfosRestartFailed"]=1;}
	if(!is_numeric($MonitConfig["MgrInfosRestartFailed"])){$MonitConfig["MgrInfosRestartFailed"]=1;}
	if(!isset($MonitConfig["MgrInfosFaileOverFailed"])){$MonitConfig["MgrInfosFaileOverFailed"]=1;}
	if(!is_numeric($MonitConfig["MgrInfosFaileOverFailed"])){$MonitConfig["MgrInfosFaileOverFailed"]=1;}	
	
	if(!isset($MonitConfig["MgrInfosMaxFailed"])){$MonitConfig["MgrInfosMaxFailed"]=2;}
	if(!is_numeric($MonitConfig["MgrInfosMaxFailed"])){$MonitConfig["MgrInfosMaxFailed"]=2;}
	if($MonitConfig["MgrInfosMaxFailed"]==0){$MonitConfig["MgrInfosMaxFailed"]=1;}
	
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	if(!isset($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!isset($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	if(!isset($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!isset($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	
	if(!isset($MonitConfig["StopMaxTTL"])){$MonitConfig["StopMaxTTL"]=90;}
	if(!is_numeric($MonitConfig["StopMaxTTL"])){$MonitConfig["StopMaxTTL"]=90;}
	if($MonitConfig["StopMaxTTL"]<5){$MonitConfig["StopMaxTTL"]=5;}
	
	
	
	
	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!isset($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!isset($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	
	if(!isset($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	
	
	if(!isset($MonitConfig["SWAP_MONITOR"])){$MonitConfig["SWAP_MONITOR"]=1;}
	if(!isset($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
	if(!isset($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
	if(!is_numeric($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
	if(!is_numeric($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
	
	
	if(!is_numeric($MonitConfig["MinFreeMem"])){$MonitConfig["MinFreeMem"]=50;}
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	if(!is_numeric($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
	if(!is_numeric($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}

	if(!is_numeric($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
	if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=1;}
	
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
	if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	
	if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	
	if(!is_numeric($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
	if(!is_numeric($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
	if(!is_numeric($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	
	
	
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=15;}
	if($MonitConfig["MIN_INTERVAL"]<3){$MonitConfig["MIN_INTERVAL"]=3;}
	if($MonitConfig["MaxSwapPourc"]<5){$MonitConfig["MaxSwapPourc"]=5;}
	if($MonitConfig["DNSIssuesMAX"]<1){$MonitConfig["DNSIssuesMAX"]=1;}
	if($MonitConfig["REBOOT_INTERVAL"]<10){$MonitConfig["REBOOT_INTERVAL"]=10;}
	if($MonitConfig["MinTimeFailOverSwitch"]<5){$MonitConfig["MinTimeFailOverSwitch"]=5;}
	if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=1;}
	if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
	if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=1;}
	if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
	
	
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if(!isset($MonitConfig["EnableFailover"])){
		$sock=new sockets();
		$MonitConfig["EnableFailover"]=$sock->GET_INFO("EnableFailover");
		if(!is_numeric($MonitConfig["EnableFailover"])){$MonitConfig["EnableFailover"]=1;}
		
	}
	return $MonitConfig;
		
}

function SAVE(){
	$MonitConfig=defaults_values();
	$sock=new sockets();
	
	
	if(isset($_POST["StopMaxTTL"])){
		$squid=new squidbee();
		$squid->shutdown_lifetime=$_POST["shutdown_lifetime"];
		$squid->SaveToLdap(true);
		if($_POST["StopMaxTTL"]<$_POST["shutdown_lifetime"]){
			$_POST["StopMaxTTL"]=$_POST["shutdown_lifetime"]+1;
		}
		
	}
	
	
	if(isset($_POST["EnableFailover"])){$sock->SET_INFO("EnableFailover",$_POST["EnableFailover"]);}
	if(isset($_POST["SquidCacheReloadTTL"])){$sock->SET_INFO("SquidCacheReloadTTL",$_POST["SquidCacheReloadTTL"]);}
	
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$MonitConfig[$num]=$ligne;
		
	}
	
	$newparam=base64_encode(serialize($MonitConfig));
	$sock=new sockets();
	$sock->SaveConfigFile($newparam, "SquidWatchdogMonitConfig");
	
}

function SAVE_SMTP(){
	$UfdbguardSMTPNotifs=smtp_defaults();
	while (list ($num, $ligne) = each ($_POST) ){
		$UfdbguardSMTPNotifs[$num]=$ligne;
	
	}
	$newparam=base64_encode(serialize($UfdbguardSMTPNotifs));
	$sock=new sockets();
	$sock->SaveConfigFile($newparam, "UfdbguardSMTPNotifs");
	
}


function SWAP_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("SWAP {enable}", "{SWAP_MONITOR_EXPLAIN}","SWAP_MONITOR",$MonitConfig["SWAP_MONITOR"],null,750)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{SWAP_MIN}:</td>
		<td	style='font-size:18px'>". Field_text("SWAP_MIN",$MonitConfig["SWAP_MIN"],"font-size:18px;width:90px")."&nbsp;%</td>
		<td width=1%>". help_icon("{SWAP_MONITOR_EXPLAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{SWAP_MAX}:</td>
		<td	style='font-size:18px'>". Field_text("SWAP_MAX",$MonitConfig["SWAP_MAX"],"font-size:18px;width:90px")."&nbsp;%</td>
		<td width=1%>". help_icon("{SWAP_MONITOR_EXPLAIN}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>			
	</div>		
<script>
		var xSave$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
		}


		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SAVEGLOBAL','yes');
			XHR.appendData('SWAP_MONITOR',document.getElementById('SWAP_MONITOR').value);
			XHR.appendData('SWAP_MIN',document.getElementById('SWAP_MIN').value);
			XHR.appendData('SWAP_MAX',document.getElementById('SWAP_MAX').value);
			XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}
</script>
				
";
	
echo $tpl->_ENGINE_parse_body($html);
		
	
	
}

function PING_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{ENABLE_PING_GATEWAY}", "{ENABLE_PING_GATEWAY_EXPLAIN}","ENABLE_PING_GATEWAY",$MonitConfig["ENABLE_PING_GATEWAY"],null,750)."
			". Paragraphe_switch_img("{reload_network}", "{PING_FAILED_RELOAD_NET_EXPLAIN}",
					"PING_FAILED_RELOAD_NET",$MonitConfig["PING_FAILED_RELOAD_NET"],null,750)."
			". Paragraphe_switch_img("{send_report}", "{PING_FAILED_REPORT_EXPLAIN}",
					"PING_FAILED_REPORT",$MonitConfig["PING_FAILED_REPORT"],null,750)."	
			". Paragraphe_switch_img("{switch_to_failover}", "{PING_FAILED_FAILOVER_EXPLAIN}",
					"PING_FAILED_FAILOVER",$MonitConfig["PING_FAILED_FAILOVER"],null,750)."	
			". Paragraphe_switch_img("{reboot_system}", "{reboot_system_explain}",
					"PING_FAILED_REBOOT",$MonitConfig["PING_FAILED_REBOOT"],null,750)."								

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{MAX_PING_GATEWAY}:</td>
		<td	style='font-size:18px'>". Field_text("MAX_PING_GATEWAY",$MonitConfig["MAX_PING_GATEWAY"],"font-size:18px;width:90px")."&nbsp;</td>
		<td width=1%>". help_icon("{MAX_PING_GATEWAY_EXPLAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}:</td>
		<td	style='font-size:18px'>". field_ipv4("PING_GATEWAY",$MonitConfig["PING_GATEWAY"],"font-size:18px;width:90px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
			</div>
			<script>
			var xSave$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
	}
	
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('ENABLE_PING_GATEWAY',document.getElementById('ENABLE_PING_GATEWAY').value);
	XHR.appendData('PING_GATEWAY',document.getElementById('PING_GATEWAY').value);
	XHR.appendData('MAX_PING_GATEWAY',document.getElementById('MAX_PING_GATEWAY').value);
	XHR.appendData('PING_FAILED_RELOAD_NET',document.getElementById('PING_FAILED_RELOAD_NET').value);
	XHR.appendData('PING_FAILED_REPORT',document.getElementById('PING_FAILED_REPORT').value);
	XHR.appendData('PING_FAILED_FAILOVER',document.getElementById('PING_FAILED_FAILOVER').value);
	XHR.appendData('PING_FAILED_REBOOT',document.getElementById('PING_FAILED_REBOOT').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function DNS_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{NotifyDNSIssues}", "{NotifyDNSIssues_explain}","NotifyDNSIssues",$MonitConfig["NotifyDNSIssues"],null,750)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{DNSIssuesMAX}:</td>
		<td	style='font-size:18px'>". Field_text("DNSIssuesMAX",$MonitConfig["DNSIssuesMAX"],
				"font-size:18px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('NotifyDNSIssues',document.getElementById('NotifyDNSIssues').value);
	XHR.appendData('DNSIssuesMAX',document.getElementById('DNSIssuesMAX').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);	
}
function EXTERNAL_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$tcp=new networking();
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{TestExternalWebPage}", "{squid_TestExternalWebPage_explain}",
					"TestExternalWebPage",$MonitConfig["TestExternalWebPage"],null,750)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{page_to_check}:</td>
		<td	style='font-size:18px'>". Field_text("ExternalPageToCheck",$MonitConfig["ExternalPageToCheck"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{addr}:</td>
		<td	style='font-size:18px'>". Field_array_Hash($ALL_IPS_GET_ARRAY,"ExternalPageListen",$MonitConfig["ExternalPageToCheck"],
					"style:font-size:18px;")."&nbsp;</td>
		<td width=1%></td>
	</tr>							
	<tr>
		<td class=legend style='font-size:18px'>{username}:</td>
		<td	style='font-size:18px'>". Field_text("ExternalPageUsername",$MonitConfig["ExternalPageUsername"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{ExternalPageUsername_EXPLAIN}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td	style='font-size:18px'>". Field_password("ExternalPagePassword",$MonitConfig["ExternalPagePassword"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{ExternalPageUsername_EXPLAIN}")."</td>
	</tr>										
	<tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
</div>
<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('TestExternalWebPage',document.getElementById('TestExternalWebPage').value);
	XHR.appendData('ExternalPageUsername',document.getElementById('ExternalPageUsername').value);
	XHR.appendData('ExternalPagePassword',document.getElementById('ExternalPagePassword').value);
	XHR.appendData('ExternalPageListen',document.getElementById('ExternalPageListen').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function PERFORMANCE_PAGE(){
	
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$tcp=new networking();
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{max_system_load_failover}", "{max_system_load_failover_explain}",
						"MaxLoadFailOver",$MonitConfig["MaxLoadFailOver"],null,750)
		. Paragraphe_switch_img("{max_system_load_reboot}", "{max_system_load_reboot_explain}",
						"MaxLoadReboot",$MonitConfig["MaxLoadReboot"],null,750).
		 Paragraphe_switch_img("{RestartWhenCrashes}", "{RestartWhenCrashes_explain}",
						"RestartWhenCrashes",$MonitConfig["RestartWhenCrashes"],null,750)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{notify_when_cpu_exceed}:</td>
		<td	style='font-size:18px'>". Field_text("watchdogCPU",$MonitConfig["watchdogCPU"],
					"font-size:18px;width:190px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{notify_when_memory_exceed}:</td>
<td	style='font-size:18px'>". Field_text("watchdogMEM",$MonitConfig["watchdogMEM"],
					"font-size:18px;width:190px")."&nbsp;MB</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{MaxSwapPourc}:</td>
		<td	style='font-size:18px'>". Field_text("MaxSwapPourc",$MonitConfig["MaxSwapPourc"],
					"font-size:18px;width:190px")."&nbsp;%</td>
		<td width=1%>". help_icon("{MaxSwapPourc_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{max_system_load}:</td>
		<td	style='font-size:18px'>". Field_text("MaxLoad",$MonitConfig["MaxLoad"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{max_system_load_squid_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{MinFreeMem}:</td>
		<td	style='font-size:18px'>". Field_text("MinFreeMem",$MonitConfig["MinFreeMem"],
					"font-size:18px;width:190px")."&nbsp;MB</td>
		<td width=1%>". help_icon("{MinFreeMem_squid_explain}")."</td>
	</tr>							
	<tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('MaxLoadFailOver',document.getElementById('MaxLoadFailOver').value);
	XHR.appendData('MaxLoadReboot',document.getElementById('MaxLoadReboot').value);
	XHR.appendData('RestartWhenCrashes',document.getElementById('RestartWhenCrashes').value);
	XHR.appendData('watchdogCPU',document.getElementById('watchdogCPU').value);
	XHR.appendData('watchdogMEM',document.getElementById('watchdogMEM').value);
	XHR.appendData('MaxSwapPourc',document.getElementById('MaxSwapPourc').value);
	XHR.appendData('MaxLoad',document.getElementById('MaxLoad').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function SETTINGS_PAGE(){
	$squid=new squidbee();
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}	
	$SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
	if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}

	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{enable_watchdog}", "{enable_watchdog_squid_explain}",
						"watchdog",$MonitConfig["watchdog"],null,750).
				Paragraphe_switch_img("{enable} {failover}", "{EnableFailover_explain}",
						"EnableFailover",$EnableFailover,null,750).
				Paragraphe_switch_img("{ALLOW_RETURN_1CPU}", "{ALLOW_RETURN_1CPU_EXPLAIN}",
						"ALLOW_RETURN_1CPU",$MonitConfig["ALLOW_RETURN_1CPU"],null,750).
				Paragraphe_switch_img("{DisableWebFilteringNetFailed}", "{DisableWebFilteringNetFailed_explain}",
						"DisableWebFilteringNetFailed",$MonitConfig["DisableWebFilteringNetFailed"],null,750)."								

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{failover_ttl}:</td>
		<td	style='font-size:18px'>". Field_text("MinTimeFailOverSwitch",$MonitConfig["MinTimeFailOverSwitch"],
					"font-size:18px;width:190px")."&nbsp;{minutes}</td>
		<td width=1%>". help_icon("{failover_ttl_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{max_attempts} (CPU):</td>
<td	style='font-size:18px'>". Field_text("WEBPROCISSUE",$MonitConfig["WEBPROCISSUE"],
			"font-size:18px;width:190px")."&nbsp;{errors}</td>
		<td width=1%></td>
	</tr>
<tr><td colspan=3 style='font-size:22px'>{services_timeout}</td></tr>
					
					
	<tr>
		<td class=legend style='font-size:18px'>{StopMaxTTL}:</td>
		<td	style='font-size:18px'>". Field_text("StopMaxTTL",$MonitConfig["StopMaxTTL"],
					"font-size:18px;width:90px")."&nbsp;{seconds}</td>
		<td width=1%>". help_icon("{StopMaxTTL_explain}")."</td>
	</tr>	
		<tr>
			<td align='right' class=legend style='font-size:18px'>{shutdown_lifetime}</strong>:</td>
			<td align='left' style='font-size:18px'>" . Field_text("shutdown_lifetime-$t",$squid->shutdown_lifetime,'width:90px;font-size:18px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{shutdown_lifetime_text}',true)."</td>
		</tr>
				
	<tr>
		<td class=legend style='font-size:18px'>{minimum_reload_interval}:</td>
		<td	style='font-size:18px'>". Field_text("SquidCacheReloadTTL",$SquidCacheReloadTTL,
					"font-size:18px;width:190px")."&nbsp;{minutes}</td>
		<td width=1%>". help_icon("{SquidCacheReloadTTL_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{minimum_reboot_interval}:</td>
		<td	style='font-size:18px'>". Field_text("REBOOT_INTERVAL",$MonitConfig["REBOOT_INTERVAL"],
					"font-size:18px;width:190px")."&nbsp;{minutes}</td>
		<td width=1%>". help_icon("{minimum_reboot_interval_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{SQUID_MAX_RESTART}:</td>
		<td	style='font-size:18px'>". Field_text("MAX_RESTART",$MonitConfig["MAX_RESTART"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{SQUID_MAX_RESTART_EXPLAIN}")."</td>
	</tr>
				
				
	<tr><td colspan=3 style='font-size:24px'>{when_fetching_proxy_informations}</td></tr>
	<tr><td colspan=3 ><div class=explain style='font-size:16px'>{when_fetching_proxy_informations_explain}</div>		
	<tr>
		<td class=legend style='font-size:18px'>{tests_timeout}:</td>
		<td	style='font-size:18px'>". Field_text("MgrInfosMaxTimeOut",$MonitConfig["MgrInfosMaxTimeOut"],
					"font-size:18px;width:190px")."&nbsp;{seconds}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{max_failed_before_action}:</td>
		<td	style='font-size:18px'>". Field_text("MgrInfosMaxFailed",$MonitConfig["MgrInfosMaxFailed"],
					"font-size:18px;width:90px")."&nbsp;{times}</td>
		<td width=1%></td>
	</tr>							
	<tr>
		<td class=legend style='font-size:18px'>{restart_if_failed}:</td>
		<td	style='font-size:18px'>". Field_checkbox("MgrInfosRestartFailed",1,$MonitConfig["MgrInfosRestartFailed"])."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{failover_if_failed}:</td>
		<td	style='font-size:18px'>". Field_checkbox("MgrInfosFaileOverFailed",1,$MonitConfig["MgrInfosFaileOverFailed"])."&nbsp;</td>
		<td width=1%></td>
	</tr>				
				
							
	<tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('watchdog',document.getElementById('watchdog').value);
	XHR.appendData('EnableFailover',document.getElementById('EnableFailover').value);
	XHR.appendData('ALLOW_RETURN_1CPU',document.getElementById('ALLOW_RETURN_1CPU').value);
	XHR.appendData('WEBPROCISSUE',document.getElementById('WEBPROCISSUE').value);
	XHR.appendData('DisableWebFilteringNetFailed',document.getElementById('DisableWebFilteringNetFailed').value);
	XHR.appendData('SquidCacheReloadTTL',document.getElementById('SquidCacheReloadTTL').value);
	XHR.appendData('REBOOT_INTERVAL',document.getElementById('REBOOT_INTERVAL').value);
	XHR.appendData('MAX_RESTART',document.getElementById('MAX_RESTART').value);
	XHR.appendData('MgrInfosMaxTimeOut',document.getElementById('MgrInfosMaxTimeOut').value);
	XHR.appendData('MgrInfosMaxFailed',document.getElementById('MgrInfosMaxFailed').value);
	XHR.appendData('shutdown_lifetime',document.getElementById('shutdown_lifetime-$t').value);
	XHR.appendData('StopMaxTTL',document.getElementById('StopMaxTTL').value);
	
	
	if(document.getElementById('MgrInfosRestartFailed').checked){ XHR.appendData('MgrInfosRestartFailed',1); }else{ XHR.appendData('MgrInfosRestartFailed',0); }
	if(document.getElementById('MgrInfosFaileOverFailed').checked){ XHR.appendData('MgrInfosFaileOverFailed',1); }else{ XHR.appendData('MgrInfosFaileOverFailed',0); }
	
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}
function smtp_defaults(){
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
	return $UfdbguardSMTPNotifs;
	
}
function SMTP_PAGE(){
	$t=time();
	$UfdbguardSMTPNotifs=smtp_defaults();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{smtp_enabled}", "{smtp_enabled_watchdog_explain}",
						"ENABLED_SQUID_WATCHDOG",$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"],null,750)."
			". Paragraphe_switch_img("{tls_enabled}", "{tls_enabled_explain}",
						"tls_enabled",$UfdbguardSMTPNotifs["tls_enabled"],null,750)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_server_name}:</td>
		<td	style='font-size:18px'>". field_ipv4("smtp_server_name",$UfdbguardSMTPNotifs["smtp_server_name"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_server_port}:</td>
<td	style='font-size:18px'>". Field_text("smtp_server_port",$UfdbguardSMTPNotifs["smtp_server_port"],
			"font-size:18px;width:90px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_sender}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_sender",$UfdbguardSMTPNotifs["smtp_sender"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_dest}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_dest",$UfdbguardSMTPNotifs["smtp_dest"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_auth_user}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_auth_user",$UfdbguardSMTPNotifs["smtp_auth_user"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_auth_passwd}:</td>
		<td	style='font-size:18px'>". Field_password("smtp_auth_passwd",$UfdbguardSMTPNotifs["smtp_auth_passwd"],
					"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>							
	<tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVESMTP','yes');
	XHR.appendData('ENABLED_SQUID_WATCHDOG',document.getElementById('ENABLED_SQUID_WATCHDOG').value);
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
	XHR.appendData('smtp_dest',document.getElementById('smtp_dest').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
	XHR.appendData('smtp_auth_passwd',encodeURIComponent(document.getElementById('smtp_auth_passwd').value));
	XHR.appendData('tls_enabled',document.getElementById('tls_enabled').value);
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

