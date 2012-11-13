<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.main_cf_filtering.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.policyd-weight.inc');						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ApplyUfdbguard"])){compile_ufdb();exit;}
if(isset($_GET["compile-cicap"])){compile_cicap();exit;}
if(isset($_GET["compile-squid"])){compile_squid();exit;}
if(isset($_GET["compile-end-1"])){compile_end_1();exit;}
if(isset($_GET["compile-end-2"])){compile_end_2();exit;}
if(isset($_GET["compile-end"])){compile_end();exit;}
if(isset($_GET["Status"])){echo Status($_GET["Status"]);exit;}


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
	
	$title=$tpl->_ENGINE_parse_body('{proxy_parameters_compilation}');
	$html="RTMMail(500,'$page?popup=yes','$title');";
	echo $html;
	}
	
	
function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	if(!$users->AsSquidAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	$t=time();
	$pourc=0;
	$table=Status(0);
	$color="#5DD13D";
	$html="
	<div class=explain>{APPLY_SETTINGS_SQUID}</div>
	<table style='width:100%'>
	<tr>
		<td width=1%><div id='wait_image'><img src='img/wait.gif'></div>
		</td>
		<td width=99%>
			<table style='width:100%'>
			<tr>
			<td>
				<div style='width:100%;background-color:white;padding-left:0px;border:1px solid $color'>
					<div id='progression_postfix_compile'>
						<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
							<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%</strong></center>
						</div>
					</div>
				</div>
			</td>
			</tr>
			</table>		
		</td>
	</tr>
	</table>
	<br>
	<div id='textlogs' style='width:99%;height:120px;overflow:auto'></div>
	
	<script>
	function StartCompileSquid$t(){
		setTimeout('ApplyUfdbguard()',1000);
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
		RTMMailHide();
	}
	
	function ApplyUfdbguard(){
		ChangeStatusSQUID(10);
		LoadAjaxSilent('textlogs','$page?ApplyUfdbguard=yes&t=$t');
		}
		

		
	var x_ChangeStatusSQUID= function (obj) {
		var tempvalue=obj.responseText;
		if(document.getElementById('progression_postfix_compile')){
			document.getElementById('progression_postfix_compile').innerHTML=tempvalue;
		}
	}		
		
		
	function ChangeStatusSQUID(number){
		var XHR = new XHRConnection();
		XHR.appendData('Status',number);
		XHR.sendAndLoad('$page', 'GET',x_ChangeStatusSQUID);	
	}

	
	StartCompileSquid$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}
function Status($pourc){
$color="#5DD13D";	
$html="
	<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
		<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%</strong></center>
	</div>
";	


return $html;
	
}


function compile_ufdb(){
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	$script="
	<div id='compile_ufdb'></div>
	<script>
		ChangeStatusSQUID(15);
		LoadAjaxSilent('compile_ufdb','$page?compile-cicap=yes&t=$t');
	</script>	
	
	";
	
	if(!$users->APP_UFDBGUARD_INSTALLED){
			echo $tpl->_ENGINE_parse_body("<div><strong>{APP_UFDBGUARD}:</strong> {error_module_not_installed}</div>").$script;
			die();
	}
	
	if($EnableUfdbGuard==0){
		echo $tpl->_ENGINE_parse_body("<div><strong>{APP_UFDBGUARD}:</strong> {error_module_not_enabled}</div>").$script;
		die();		
	}
	
	echo $tpl->_ENGINE_parse_body("<div><strong>{APP_UFDBGUARD}:{please_wait_configuring_the_module}:</strong></div>").$script;
	
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
	<div id='compile_squid'></div>
	<script>
		ChangeStatusSQUID(45);
		LoadAjaxSilent('compile_squid','$page?compile-squid=yes&t=$t');
	</script>
	";	
	
	if(!$users->C_ICAP_INSTALLED){
			echo $tpl->_ENGINE_parse_body("<div><strong>{CICAP_AV}:</strong> {error_module_not_installed}</div>").$script;
			die();
	}	
	
	if($CicapEnabled==0){
		echo $tpl->_ENGINE_parse_body("<div><strong>{CICAP_AV}:</strong> {error_module_not_enabled})</div>").$script;
		die();		
	}	
	echo $tpl->_ENGINE_parse_body("<div><strong>{CICAP_AV}:{please_wait_configuring_the_module}:</strong></div>").$script;
	$sock->getFrameWork("cmd.php?cicap-reconfigure=yes&tenir=yes&MyCURLTIMEOUT=300");

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
		$cmd="squid.php?recompile-debug=yes&MyCURLTIMEOUT=300";
		$sock->getFrameWork($cmd);	
	}
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		$text="{proxy_clients_was_notified}";
		
	}
	
	$script="
	<div id='compile_end'></div>
	<script>
		ChangeStatusSQUID(90);
		LoadAjaxSilent('compile_end','$page?compile-end-1=yes&t=$t');
	</script>
	";	
	
	echo $tpl->_ENGINE_parse_body("<div><strong>{APP_SQUID}:$text</strong></div>").$script;

	
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
		CacheOff();
		ChangeStatusSQUID(95);
		LoadAjaxSilent('compile_end-2$t','$page?compile-end-2=yes&t=$t');
	</script>
	";	
	
	echo $script;	
	
}

function compile_end_2(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$text=$tpl->_ENGINE_parse_body("{please_wait_refresh_web_pages}");
	$script="
	<div id='compile_end-2$t'>$text</div>
	<script>
		
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
		
	echo $tpl->_ENGINE_parse_body("<strong>{success}</strong>").$script;
		
}






?>