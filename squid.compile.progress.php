<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.main_cf_filtering.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.policyd-weight.inc');						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ApplyUfdbguard"])){compile_ufdb();exit;}
if(isset($_GET["ApplyWhiteList"])){compile_whitelist();exit;}
if(isset($_GET["compile-cicap"])){compile_cicap();exit;}
if(isset($_GET["compile-kav"])){compile_kav();exit;}
if(isset($_GET["compile-squid"])){compile_squid();exit;}
if(isset($_GET["compile-pdns"])){compile_pdns();exit;}
if(isset($_GET["compile-end-1"])){compile_end_1();exit;}
if(isset($_GET["compile-end-2"])){compile_end_2();exit;}
if(isset($_GET["compile-end-finish"])){compile_end_finish();exit;}
if(isset($_GET["compile-end"])){compile_end();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$restart=null;
	if($_GET["restart"]=="yes"){$restart="?restart=yes"; }

	
	echo "Loadjs('squid.reconfigure.php$restart')";
	return;
	$page=CurrentPageName();
	$tpl=new templates();
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	

	

	if($EnableWebProxyStatsAppliance==1){
		echo "Loadjs('squid.compile.php')";
		return;
	}
	
	if(isset($_GET["OnlySquid"])){
		echo "Loadjs('squid.reconfigure.php');";
		return;
		$extension2="&OnlySquid=yes";
	}
	
	if(isset($_GET["onlywhitelist"])){
		$extension="&onlywhitelist=yes";
	}
	
	if(isset($_GET["restart"])){
		$extension3="&restart=yes";
	}
	
	
	$title=$tpl->_ENGINE_parse_body('{proxy_parameters_compilation}');
	if(isset($_GET["ask"])){
		$compile_squid_ask=$tpl->javascript_parse_text("{compile_squid_ask}");
		echo "if(confirm('$compile_squid_ask')){
				RTMMail(800,'$page?popup=yes$extension$extension2$extension3','$title');
			}";
		return;
	}
	
	
	$html="RTMMail(800,'$page?popup=yes$extension$extension2$extension3','$title');";
	echo $html;
	}
	
	
function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	if(!$users->AsSquidAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	$start="StartCompileSquid$t();";
	if(isset($_GET["onlywhitelist"])){
		$extension="&onlywhitelist=yes";
		$start="StartCompileWhitelist$t();";
	}	
	
	if(isset($_GET["OnlySquid"])){ 
		$extension2="&OnlySquid=yes"; 
		$start="StartCompileOnlySquid$t();";
	}	
	if(isset($_GET["restart"])){
		$extension3="&restart=yes";
	}
	
	
	
	
	$pourc=0;
	$color="#5DD13D";
	$html="
	<div class=text-info style='font-size:16px'>{APPLY_SETTINGS_SQUID}</div>
	<div id='Status' style='height:40px:margin-top:20px;margin-bottom:20px'></div>	
	<div id='textlogs' style='width:99%;min-height:120px;font-size:16px !important'></div>
	
	<script>
	function StartCompileSquid$t(){
		setTimeout('ApplyUfdbguard()',1000);
	}

	function finish$t(){
		ChangeStatusSQUID(100);
		LoadAjax('div-poubelle','CacheOff.php?cache=yes');
		if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		if(document.getElementById('TEMPLATE_LEFT_MENUS')){ RefreshLeftMenu();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		if(document.getElementById('squid_hotspot')){RefreshTab('squid_hotspot');;}
		if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
		if(document.getElementById('main_dansguardian_mainrules')){RefreshTab('main_dansguardian_mainrules');}
		if(document.getElementById('main_cache_rules_main_tabs')){RefreshTab('main_cache_rules_main_tabs');}
		if(document.getElementById('rules-toolbox-left')){LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');}
		if(document.getElementById('ufdb-main-toolbox-status')){LoadAjaxTiny('ufdb-main-toolbox-status','dansguardian2.mainrules.php?rules-toolbox-left=yes');}
		RTMMailHide();
	}
	
	function ApplyUfdbguard(){
		ChangeStatusSQUID(10);
		LoadAjaxSilent('textlogs','$page?ApplyUfdbguard=yes&t=$t$extension2$extension3');
		}
		
	function StartCompileOnlySquid$t(){
		ChangeStatusSQUID(52);
		LoadAjaxSilent('textlogs','$page?compile-squid=yes&t=$t$extension3');
		
	}
		
	function ApplyWhiteList(){
		ChangeStatusSQUID(52);
		LoadAjaxSilent('textlogs','$page?ApplyWhiteList=yes&t=$t$extension3');
		}		
		

	function StartCompileWhitelist$t(){
		setTimeout('ApplyWhiteList()',1000);
	}
		
	var x_ChangeStatusSQUID= function (obj) {
		var tempvalue=obj.responseText;
		if(document.getElementById('progression_postfix_compile')){
			document.getElementById('progression_postfix_compile').innerHTML=tempvalue;
		}
	}		
		
		
	function ChangeStatusSQUID(number){
		$('#Status').progressbar({ value: number });
	}

	$('#Status').progressbar({ value: 2 });
	$start
	</script>
	";
	$sock=new sockets();
	$sock->getFrameWork("squid.php?caches-center=yes");
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}


function compile_whitelist(){
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=$_GET["t"];
	
	$script="
	<div id='compile_ufdb'></div>
	<script>
	finish$t();
	</script>";	
	
	echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_UFDBGUARD}:{please_wait_compiling_database}")).$script;
	
	$sock->getFrameWork("squid.php?build-whitelist-tenir=yes&MyCURLTIMEOUT=300");
}


function compile_ufdb(){
	
	
	
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if($SquidActHasReverse==1){$EnableUfdbGuard=0;}
	
	
	$script="
	<div id='compile_ufdb'></div>
	<script>
		ChangeStatusSQUID(15);
		LoadAjaxSilent('compile_ufdb','$page?compile-cicap=yes&t=$t');
	</script>	
	
	";
	
	if(!$users->APP_UFDBGUARD_INSTALLED){
		echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_UFDBGUARD}: {error_module_not_installed}")).$script;
		die();
	}
	
	if($EnableUfdbGuard==0){
		echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_UFDBGUARD}: {error_module_not_enabled}")).$script;
		
		die();		
	}
	echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_UFDBGUARD}:{please_wait_configuring_the_module}:")).$script;
	
	
$sock->getFrameWork("squid.php?ufdbguard-compile-smooth-tenir=yes&MyCURLTIMEOUT=300");

	
}

function compile_cicap(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$CicapEnabled=$sock->GET_INFO('CicapEnabled');
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	$t=$_GET["t"];
	$script="
	<div id='compile_kav'></div>
	<script>
		ChangeStatusSQUID(50);
		LoadAjaxSilent('compile_kav','$page?compile-kav=yes&t=$t');
	</script>
	";	
	
	if(!$users->C_ICAP_INSTALLED){
			echo $tpl->_ENGINE_parse_body(build_progress_text("{CICAP_AV}: {error_module_not_installed}")).$script;
			die();
	}	
	
	if($CicapEnabled==0){
		echo $tpl->_ENGINE_parse_body(build_progress_text("{CICAP_AV}: {error_module_not_enabled}")).$script;
		
		die();		
	}	
	echo $tpl->_ENGINE_parse_body(build_progress_text("{CICAP_AV}:{please_wait_configuring_the_module}:")).$script;
	
	$sock->getFrameWork("cmd.php?cicap-reconfigure=yes&tenir=yes&MyCURLTIMEOUT=300");

}
function compile_kav(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$kavicapserverEnabled=$sock->GET_INFO('kavicapserverEnabled');
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}
	$t=$_GET["t"];
	$script="
	<div id='compile_kav'></div>
	<script>
	ChangeStatusSQUID(55);
	LoadAjaxSilent('compile_kav','$page?compile-squid=yes&t=$t');
	</script>
	";

	if(!$users->KAV4PROXY_INSTALLED){
		echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:14px'>{APP_KAV4PROXY}:</strong> {error_module_not_installed}</div>").$script;
		die();
	}

	if($kavicapserverEnabled==0){
		echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:14px'>{APP_KAV4PROXY}:</strong> {error_module_not_enabled})</div>").$script;
		die();
	}
	
	echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_KAV4PROXY}:{please_wait_configuring_the_module}")).$script;
	
	$sock->getFrameWork("cmd.php?kav4proxy-reconfigure-tenir=yes&MyCURLTIMEOUT=300");

}


function compile_squid(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	
	$text="{apply config}&nbsp;{success}";
	$cmd="squid.php?build-smooth-tenir=yes&MyCURLTIMEOUT=300&force=yes";
	$sock->getFrameWork($cmd);	
	if(isset($_GET["restart"])){
		$sock->getFrameWork("cmd.php?force-restart-squidonly=yes&force=yes");
	}
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		$text="{proxy_clients_was_notified}";
		
	}
	
	$script="
	<div id='compile_pdns'></div>
	<script>
		ChangeStatusSQUID(85);	
		LoadAjaxSilent('compile_pdns','$page?compile-pdns=yes&t=$t');
	</script>
	";	
	echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_SQUID}:$text")).$script;
	

	
}
function compile_pdns(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}

	
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}

	if($users->POWER_DNS_INSTALLED){
		if($EnablePDNS==1){
			$text="{apply config}&nbsp;{success}";
			$cmd="pdns.php?reload=yes&MyCURLTIMEOUT=300";
			$sock->getFrameWork($cmd);
		}else{
			$text="{powerdns_not_enabled}";
		}
	}else{
		$text="{powerdns_not_installed}";
	}
	
	

	$script="
	<div id='compile_end'></div>
	<script>
	ChangeStatusSQUID(90);
	LoadAjaxSilent('compile_end','$page?compile-end-1=yes&t=$t');
	</script>
	";

	echo $tpl->_ENGINE_parse_body(build_progress_text("{APP_PDNS}:$text")).$script;


}

function build_progress_text($text){
	return "
	<div style='margin-top:0px'>
		<table style='width:100%'>
		<tr>
			<td style='width:26px;vertical-align:center'><img src='img/arrow-right-24.png'></td>
			<td style='width:100%;font-size:16px;font-weight:bold;vertical-align:center'>$text</td>
		</tr>
		</table>
	</div>";
	
}


function compile_end_1(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$text=$tpl->_ENGINE_parse_body("{please_wait_empty_console_cache}");
	$script="
	<div id='compile_end-2$t'>$text</div>
	<script>
		
		ChangeStatusSQUID(92);
		LoadAjaxSilent('compile_end-2$t','$page?compile-end-2=yes&t=$t');
	</script>
	";	
	
	echo $script;	
	
}
function compile_end_2(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$text=$tpl->_ENGINE_parse_body("{please_wait_restarting_artica_status}....");
	$script="
	<div id='compile_end-3$t'>$text</div>
	<script>
		ChangeStatusSQUID(92);
		LoadAjaxSilent('compile_end-3$t','$page?compile-end-finish=yes&t=$t');
	</script>
	";

	echo $script;
	$sock->getFrameWork("cmd.php?restart-artica-status");
	sleep(1);
}


function compile_end_finish(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$text=$tpl->_ENGINE_parse_body(build_progress_text("{please_wait_refresh_web_pages}"));
	$script="
	<div id='compile_end-2$t'>$text</div>
	<script>
		CacheOff();
		finish$t();
	</script>
	";	
	
	echo $script;		
	
}


function compile_end(){	
		$tpl=new templates();
		$page=CurrentPageName();	
		$t=$_GET["t"];
	$script="
	<div id='compile_header_check'></div>
	<script>
		finish$t();
	</script>
	";				
		
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	echo $tpl->_ENGINE_parse_body("<strong>{success}</strong>").$script;
		
}






?>