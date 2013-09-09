<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}


if(isset($_GET["architecture-content"])){section_architecture_content();exit;}
if(isset($_GET["architecture-status"])){section_architecture_status();exit;}
if(isset($_GET["architecture-adv"])){section_architecture_advanced();exit;}
if(isset($_GET["architecture-users"])){section_architecture_users();exit;}

if(isset($_GET["plugins"])){section_plugins();exit;}
if(isset($_POST["EnableUfdbGuard"])){save_plugins();exit;}
if(isset($_POST["EnableCicap"])){save_plugins();exit;}
if(isset($_GET["filters"])){filters_for_node();exit;}
if(isset($_POST["filters"])){filters_for_node_save();exit;}
tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$array["node-status"]='{status}';
	$array["caches"]='{caches}';
	$array["filters"]='{filters}';
	//$array["architecture-users"]='{users_interactions}';
	//$array["architecture-adv"]='{advanced_options}';
	//$array["plugins"]='{proxy_plugins}';
	$array["events"]='Proxy:{service_events}';
	$array["ufdbgclient"]='{webfilter_events}';
	
	$blackbox=new blackboxes($_GET["hostid"]);
	$t=time();
	$DnsFilterCentral=$blackbox->GET_SQUID_INFO('DnsFilterCentral');
	$UfdbEnabledCentral=$blackbox->GET_SQUID_INFO('UfdbEnabledCentral');
	$AntivirusEnabledCentral=$blackbox->GET_SQUID_INFO('AntivirusEnabledCentral');
	$EnableMacAddressFilterCentral=$blackbox->GET_SQUID_INFO('EnableMacAddressFilterCentral');
	$EnableKerbAuth=$blackbox->GET_SQUID_INFO('EnableKerbAuth');
	$EnableKerbAuthCentral=$sock->GET_INFO($EnableKerbAuth);
	if(!is_numeric($UfdbEnabledCentral)){$UfdbEnabledCentral=1;}
	if(!is_numeric($AntivirusEnabledCentral)){$AntivirusEnabledCentral=1;}
	if(!is_numeric($DnsFilterCentral)){$DnsFilterCentral=0;}
	if($UfdbEnabledCentral==0){$DnsFilterCentral=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=$EnableKerbAuthCentral;}
	if(!is_numeric($EnableMacAddressFilterCentral)){$EnableMacAddressFilterCentral=1;}	
	
	
	
	
	if($UfdbEnabledCentral==0){
		unset($array["ufdbgclient"]);	
	}

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.squid.caches32.php?nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="ufdbgclient"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.squid.ufdbgclient.php?nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		if($num=="node-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.squid.status.php?nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"nodes.squid.cachelogs.php?nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&nodeid={$_GET["nodeid"]}&hostid={$_GET["hostid"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_squid_quicklinks_tabs{$_GET["nodeid"]}");
	
}


function section_architecture_content(){
	$page=CurrentPageName();
	$sock=new sockets();
	$listen_port=Paragraphe('folder-network-64.png','{listen_port}','{listen_port_text}',"javascript:Loadjs('nodes.squid.listen.ports.php?nodeid={$_GET["nodeid"]}')");
	$listen_addr=Paragraphe('folder-network-64.png','{listen_address}','{squid_listen_text}',"javascript:Loadjs('squid.nic.php')");
	$visible_hostname=Paragraphe('64-work-station-linux.png','{visible_hostname}','{visible_hostname_intro}',"javascript:Loadjs('nodes.squid.hostname.php?nodeid={$_GET["nodeid"]}')");
	$transparent_mode=Paragraphe('relayhost.png','{transparent_mode}','{transparent_mode_text}',
	"javascript:Loadjs('nodes.squid.transparent.php?nodeid={$_GET["nodeid"]}')");
	$your_network=Paragraphe('folder-realyrules-64.png','{your_network}','{your_network_text}',"javascript:Loadjs('squid.popups.php?script=network')");
	$sslbump=Paragraphe('web-ssl-64.png','{squid_sslbump}','{squid_sslbump_text}',"javascript:Loadjs('squid.sslbump.php')");
	$watchdog=Paragraphe('service-check-64-grey.png','{squid_watchdog}','{squid_watchdog_text}',"");
	$ftp_user=Paragraphe('ftp-user-64.png','{squid_ftp_user}','{squid_ftp_user_text}',"javascript:Loadjs('squid.ftp.user.php')");
	$messengers=Paragraphe('messengers-64.png','{instant_messengers}','{squid_instant_messengers_text}',"javascript:Loadjs('squid.messengers.php')");	
		
	
    
    if(!isset($COMPILATION_PARAMS["enable-ssl"])){
    	$sslbump=Paragraphe('web-ssl-64-grey.png','{squid_sslbump}','{squid_sslbump_text}',"");
    }
    
    if($users->MONIT_INSTALLED){
    	$watchdog=Paragraphe('service-check-64.png','{squid_watchdog}','{squid_watchdog_text}',"javascript:Loadjs('squid.watchdog.php')");
 	}

	$tr=array();
	$tr[]=$watchdog;
	$tr[]=$listen_port;
	$tr[]=$listen_addr;
	$tr[]=$visible_hostname;
	$tr[]=$transparent_mode;
	$tr[]=$your_network;
	$tr[]=$stat_appliance;
	$tr[]=$ftp_user;
	$tr[]=$messengers;
	$tr[]=$sslbump;
	$tr[]=$enable_squid_service;
	

	$html=CompileTr3($tr);
	
	
	
$tpl=new templates();
$html="<div id='architecture-status'></div>
<center style='width:99%' class=form>
<div style='width:80%;text-align:center'>$html</div>
</center>
<script>LoadAjaxTiny('architecture-status','$page?architecture-status=yes&nodeid={$_GET["nodeid"]}');</script>";

$html=$tpl->_ENGINE_parse_body($html,'squid.index.php');

echo $html;	
	
}

function section_architecture_advanced(){
	$sock=new sockets();
	//$squid_parent_proxy=Paragraphe('server-redirect-64.png','{squid_parent_proxy}','{squid_parent_proxy_text}',"javascript:Loadjs('squid.parent.proxy.php')");
	//$squid_reverse_proxy=Paragraphe('squid-reverse-64.png','{squid_reverse_proxy}','{squid_reverse_proxy_text}',"javascript:Loadjs('squid.reverse.proxy.php')");
	$squid_advanced_parameters=Paragraphe('64-settings.png','{squid_advanced_parameters}','{squid_advanced_parameters_text}',"javascript:Loadjs('squid.advParameters.php')");
	$squid_conf=Paragraphe('script-view-64.png','{configuration_file}','{display_generated_configuration_file}',
	"javascript:Loadjs('nodes.squid.conf.php?nodeid={$_GET["nodeid"]}')");
	//$performances_tuning=Paragraphe('performance-tuning-64.png','{tune_squid_performances}','{tune_squid_performances_text}',"javascript:Loadjs('squid.perfs.php')");
	//$denywebistes=Paragraphe("folder-64-denywebistes.png","{deny_websites}","{deny_websites_text}","javascript:Loadjs('squid.popups.php?script=url_regex');");
	if($sock->GET_INFO("SquidActHasReverse")==1){
    	$squid_accl_websites=Paragraphe('website-64.png','{squid_accel_websites}','{squid_accel_websites_text}',"javascript:Loadjs('squid.reverse.websites.php')");
    }
    
    $redirectors_options=Paragraphe('redirector-64.png','{squid_redirectors}','{squid_redirectors_text}',
    "javascript:Loadjs('nodes.squid.redirectors.php?nodeid={$_GET["nodeid"]}')");  

    
    $memory_option=Paragraphe('bg_memory-64.png','{cache_mem}','{cache_mem_text}',
    "javascript:Loadjs('nodes.squid.cache_mem.php?nodeid={$_GET["nodeid"]}')");  
    $dns_servers=Paragraphe('64-bind.png','{dns_servers}','{dns_servers_text}',"javascript:Loadjs('squid.popups.php?script=dns')");
    
    
    $tr[]=$squid_conf;
    $tr[]=$squid_advanced_parameters;
    $tr[]=$memory_option;
    $tr[]=$dns_servers;
    $tr[]=$performances_tuning;
    $tr[]=$redirectors_options;
    $tr[]=$denywebistes;
    $tr[]=$squid_parent_proxy;
    $tr[]=$squid_reverse_proxy;
    
    
    $html=CompileTr3($tr);
    
	$html="<center><div style='width:700px'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	
	echo $html;		
}

function section_architecture_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidnodes($_GET["nodeid"]);
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	$listen_port=$squid->listen_port;
	$second_port=$squid->second_listen_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	$labelport="{listen_port}";
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}
	if($second_port>0){$second_port="/$second_port";$labelport="{listen_ports}";}else{$second_port=null;}
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap>{version}:</td>
		<td>".texthref($squid->SQUID_VERSION,null)."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>
		<td class=legend nowrap>$labelport:</td>
		<td>".texthref("$listen_port$second_port","Loadjs('nodes.squid.listen.ports.php?nodeid={$_GET["nodeid"]}')")."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>
		<td class=legend nowrap>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"Loadjs('nodes.squid.hostname.php?nodeid={$_GET["nodeid"]}')")."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>
		<td class=legend nowrap>{transparent_mode}:</td>
		<td>".texthref($hasProxyTransparent,"Loadjs('nodes.squid.transparent.php?nodeid={$_GET["nodeid"]}')")."</td>
	</tr>
	</table>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function section_plugins(){
	$squid=new squidnodes($_GET["nodeid"]);
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$users=new usersMenus();
	$cicap=Paragraphe_switch_img("{enable_c_icap}","{feature_not_installed}",'EnableCicap',
	$squid->GET("EnableCicap"),'{enable_disable}',250);
	
	$kav=Paragraphe_switch_disable('{enable_kavproxy}',"{feature_not_installed}",'{feature_not_installed}');
	$ufdbguardd=Paragraphe_switch_img("{enable_ufdbguardd}","{feature_not_installed}",
	'feature_not_installed',$squid->GET("EnableUfdbGuard"),'{enable_disable}',250);
	
	
	if($users->KAV4PROXY_INSTALLED){
		$kav=Paragraphe_switch_img("{enable_kavproxy}","{enable_kavproxy_text}",
		'EnableKav4proxy',$squid->GET("EnableKav4proxy"),'{enable_disable}',250);
	}
		
	if($users->C_ICAP_INSTALLED){
		$cicap=Paragraphe_switch_img("{enable_c_icap}","{enable_c_icap_text}",'EnableCicap',$squid->GET("EnableCicap"),'{enable_disable}',250);
	}

	
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$ufdbguardd=Paragraphe_switch_img("{enable_ufdbguardd}","{enable_ufdbguardd_text}",'EnableUfdbGuard',$squid->GET("EnableUfdbGuard"),'{enable_disable}',250);
	}
	

	
	
	$tr[]=$squidclamav;
	$tr[]=$metascanner;
	$tr[]=$cicap;
	$tr[]=$kav;
	$tr[]=$streaming_cache;
	$tr[]=$adzapper;
	$tr[]=$squidguard;
	$tr[]=$ufdbguardd;
	$tr[]=$dans;
	
	$table=CompileTr2($tr);
	$t=time();
	$html="
	<div id='$t'>
	$table
	</div>
	<div style='width:100%;text-align:right'>". button("{apply}", "SaveNodesPlugins()",16)."</div>
	
	<script>
		var x_SaveNodesPlugins= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');
		}
		
		function SaveNodesPlugins(){
			var XHR = new XHRConnection();
			XHR.appendData('plugins','{$_GET["nodeid"]}');
			var XHR = new XHRConnection();
			if(document.getElementById('EnableUfdbGuard')){
				XHR.appendData('EnableUfdbGuard',document.getElementById('EnableUfdbGuard').value);
			}
			if(document.getElementById('EnableCicap')){
				XHR.appendData('EnableCicap',document.getElementById('EnableCicap').value);
			}	

			if(document.getElementById('EnableKav4proxy')){
				XHR.appendData('EnableKav4proxy',document.getElementById('EnableCicap').value);
			}
			XHR.appendData('nodeid','{$_GET["nodeid"]}');
			XHR.sendAndLoad('$page', 'POST',x_SaveNodesPlugins);	
		}	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save_plugins(){
	$squid=new squidnodes($_POST["nodeid"]);
	$squid->SET("EnableUfdbGuard",$_POST["EnableUfdbGuard"]);
	$squid->SET("EnableCicap",$_POST["EnableCicap"]);
	$squid->SET("EnableKav4proxy",$_POST["EnableKav4proxy"]);
	$squid->SaveToLdap();
	
}

function section_architecture_users(){
	$nodes=new blackboxes($_GET["nodeid"]);
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$showAD=true;
	if($nodes->settings_inc["SAMBA_INSTALLED"]<>1){$showAD=false;}
	if($nodes->settings_inc["MSKTUTIL_INSTALLED"]<>1){$showAD=false;}
	if(strlen($nodes->settings_inc["squid_kerb_auth_path"])<2){$showAD=false;}
	
	if(!$showAD){$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");}
	
	if($showAD){
		$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png',
		'{APP_SQUIDKERAUTH}',
		'{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('nodes.squid.adker.php?nodeid={$_GET["nodeid"]}')");
	}
	$tr[]=$APP_SQUIDKERAUTH;
	
		$table=CompileTr2($tr);
	$t=time();
	$html="
	<div id='$t'>
	$table
	</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function filters_for_node(){
	$tpl=new templates();
	$page=CurrentPageName();
	$hostid=$_GET["hostid"];
	$sock=new sockets();
	$uuid=$hostid;
	$blackbox=new blackboxes($hostid);
	$t=time();
	$DnsFilterCentral=$blackbox->GET_SQUID_INFO('DnsFilterCentral');
	$UfdbEnabledCentral=$blackbox->GET_SQUID_INFO('UfdbEnabledCentral');
	$AntivirusEnabledCentral=$blackbox->GET_SQUID_INFO('AntivirusEnabledCentral');
	$EnableMacAddressFilterCentral=$blackbox->GET_SQUID_INFO('EnableMacAddressFilterCentral');
	$EnableKerbAuth=$blackbox->GET_SQUID_INFO('EnableKerbAuth');
	$EnableKerbAuthCentral=$sock->GET_INFO($EnableKerbAuth);
	if(!is_numeric($UfdbEnabledCentral)){$UfdbEnabledCentral=1;}
	if(!is_numeric($AntivirusEnabledCentral)){$AntivirusEnabledCentral=1;}
	if(!is_numeric($DnsFilterCentral)){$DnsFilterCentral=0;}
	if($UfdbEnabledCentral==0){$DnsFilterCentral=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=$EnableKerbAuthCentral;}
	if(!is_numeric($EnableMacAddressFilterCentral)){$EnableMacAddressFilterCentral=1;}
	
	

	$tr[]=Paragraphe_switch_img("{enable_webfilter_engine}", "{enable_webfilter_engine_stats}","UfdbEnabledCentral",$UfdbEnabledCentral,null,400);
	$tr[]=Paragraphe_switch_img("{activate_pdnsinufdb}", "{activate_pdnsinufdb_explain}","DnsFilterCentral",$DnsFilterCentral,null,400);
	$tr[]=Paragraphe_switch_img("{enable_antivirus_checking}", "{enable_antivirus_checking_stats}","AntivirusEnabledCentral",$AntivirusEnabledCentral,null,400);
	$tr[]=Paragraphe_switch_img("{enable_activedirectory}", "{enable_activedirectory_stats}","EnableKerbAuth",$EnableKerbAuth,null,400);
	$tr[]=Paragraphe_switch_img("{enable_mac_squid_filters}", "{enable_mac_squid_filters_explain}","EnableMacAddressFilterCentral",$EnableMacAddressFilterCentral,null,400);

	$table=CompileTr2($tr);

	$html="$table
	<div style='margin:5px;text-align:right'><hr>". button("{apply}", "Save$t()","18")."</div>
	
	<script>
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
	
		if(document.getElementById('UfdbEnabledCentral')){
			XHR.appendData('UfdbEnabledCentral',document.getElementById('UfdbEnabledCentral').value);
			document.getElementById('img_UfdbEnabledCentral').src='img/wait_verybig.gif';
			
		}
		
		if(document.getElementById('DnsFilterCentral')){
			XHR.appendData('DnsFilterCentral',document.getElementById('DnsFilterCentral').value);
			document.getElementById('img_DnsFilterCentral').src='img/wait_verybig.gif';
		}	
		
		if(document.getElementById('AntivirusEnabledCentral')){
			XHR.appendData('AntivirusEnabledCentral',document.getElementById('AntivirusEnabledCentral').value);
			document.getElementById('img_AntivirusEnabledCentral').src='img/wait_verybig.gif';
		}
		
		if(document.getElementById('EnableKerbAuth')){
			XHR.appendData('EnableKerbAuth',document.getElementById('EnableKerbAuth').value);
			document.getElementById('img_EnableKerbAuth').src='img/wait_verybig.gif';
		}

		if(document.getElementById('EnableMacAddressFilterCentral')){
			XHR.appendData('EnableMacAddressFilterCentral',document.getElementById('EnableMacAddressFilterCentral').value);
			document.getElementById('img_EnableMacAddressFilterCentral').src='img/wait_verybig.gif';
		}			
		
		XHR.appendData('filters','$uuid');	
		XHR.appendData('uuid','$uuid');	
		XHR.sendAndLoad('$page', 'POST',X_Save$t);	
		
	
	}		
	
	</script>
	
	";

	echo $tpl->_ENGINE_parse_body($html);
}

function filters_for_node_save(){
	$uuid=$_POST["uuid"];
	unset($_POST["uuid"]);
	unset($_POST["filters"]);
	$blackbox=new blackboxes($uuid);
	$blackbox->SET_SQUID_POST_INFO($_POST);
	
}
