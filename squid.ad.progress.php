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
if(isset($_GET["nsswitch"])){compile_nsswitch();exit;}
if(isset($_GET["ApplyWhiteList"])){compile_whitelist();exit;}
if(isset($_GET["connect-ad"])){connect_ad();exit;}
if(isset($_GET["restart-winbind"])){restart_winbind();exit;}
if(isset($_GET["compile-squid"])){compile_squid();exit;}
if(isset($_GET["compile-pdns"])){compile_pdns();exit;}
if(isset($_GET["compile-end-1"])){compile_end_1();exit;}
if(isset($_GET["compile-end-2"])){compile_end_2();exit;}
if(isset($_GET["compile-end-finish"])){compile_end_finish();exit;}
if(isset($_GET["compile-end"])){compile_end();exit;}

js();


function js(){
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
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}

	if($EnableWebProxyStatsAppliance==1){
		echo "Loadjs('squid.compile.php')";
		return;
	}
	
	if(isset($_GET["onlywhitelist"])){
		$extension="&onlywhitelist=yes";
	}
	
	$title=$tpl->_ENGINE_parse_body('{active_directory_connection}');
	$html="RTMMail(500,'$page?popup=yes$extension','$title');";
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
	
	
	$html="
	<div class=explain>{APPLY_SETTINGS_SQUID}</div>
	<table style='width:100%'>
	<tr>
		<td width=1%><div id='wait_image'><img src='img/wait.gif'></div></td>
		<td width=99%>
			<div id='Status$t'></div>	
		</td>
	</tr>
	</table>
	<br>
	<div id='textlogs' style='width:99%;height:120px;overflow:auto'></div>
	
	<script>
	function StartCompileSquid$t(){
		setTimeout('nsswitch$t()',1000);
	}

	function finish$t(){
		ChangeStatusSQUID(100);
		document.getElementById('wait_image').innerHTML='&nbsp;';
		document.getElementById('wait_image').innerHTML='&nbsp;';
		LoadAjax('div-poubelle','CacheOff.php?cache=yes');
		if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		if(document.getElementById('TEMPLATE_LEFT_MENUS')){ RefreshLeftMenu();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		if(document.getElementById('squid_hotspot')){RefreshTab('squid_hotspot');;}
		if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
		if(document.getElementById('main_dansguardian_mainrules')){RefreshTab('main_dansguardian_mainrules');}
		if(document.getElementById('main_adker_tabs')){RefreshTab('main_adker_tabs');}
		RTMMailHide();
	}
	
	function nsswitch$t(){
		ChangeStatusSQUID(10);
		LoadAjaxSilent('textlogs','$page?nsswitch=yes&t=$t');
		}
		
	function ApplyWhiteList(){
		ChangeStatusSQUID(52);
		LoadAjaxSilent('textlogs','$page?ApplyWhiteList=yes&t=$t');
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
		$('#Status$t').progressbar({ value: number });
	}

	$('#Status$t').progressbar({ value: 2 });
	StartCompileSquid$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}
function compile_nsswitch(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();

	$t=$_GET["t"];
	$script="
	<div id='compile_nsswitch'></div>
	<script>
	ChangeStatusSQUID(50);
	LoadAjaxSilent('compile_nsswitch','$page?connect-ad=yes&t=$t');
	</script>
	";

	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{apply_parameters_to_the_system}</strong></div>").$script;
	$sock->getFrameWork("services.php?nsswitch-tenir=yes");

}

function connect_ad(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$t=$_GET["t"];
	$script="
	<div id='restart_winbind'></div>
	<script>
		ChangeStatusSQUID(50);
		LoadAjaxSilent('restart_winbind','$page?restart-winbind=yes&t=$t');
	</script>
	";	
	
	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{join_activedirectory_domain}</strong></div>").$script;
	$sock->getFrameWork("services.php?kerbauth-tenir=yes&MyCURLTIMEOUT=300");

}
function restart_winbind(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$script="
	<div id='compile_squid'></div>
	<script>
	ChangeStatusSQUID(70);
	LoadAjaxSilent('compile_squid','$page?compile-squid=yes&t=$t');
	</script>
	";

	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{restarting_winbind}</strong></div>").$script;
	$sock->getFrameWork("services.php?restart-winbind-tenir=yes&MyCURLTIMEOUT=300");

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
	if($EnableWebProxyStatsAppliance==0){
		$cmd="squid.php?build-smooth-tenir=yes&MyCURLTIMEOUT=300";
		$sock->getFrameWork($cmd);	
	}
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		$text="{proxy_clients_was_notified}";
		
	}
	
	$script="
	<script>
		finish$t();
	</script>
	";	
	
	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{APP_SQUID}:$text</strong></div>").$script;
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
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
	$text=$tpl->_ENGINE_parse_body("{please_wait_refresh_web_pages}");
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
		
	echo $tpl->_ENGINE_parse_body("<strong style='font-size:16px'>{success}</strong>").$script;
		
}






?>