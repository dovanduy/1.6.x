<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableLocalDNSMASQ"])){EnableLocalDNSMASQ();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$YahooWin=2;
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	$title=$tpl->_ENGINE_parse_body("{dns_cache}");
	$html="
	var YahooWinx=$YahooWin;
	if(YahooWinx==2){
		YahooWin2Hide();
		YahooWin6Hide();
	}	
	YahooWin$YahooWin('700','$page?popup=yes$YahooWinUri','$title')";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$t=time();
	
	$EnableLocalDNSMASQ=$sock->GET_INFO('EnableLocalDNSMASQ');
	$LocalDNSMASQItems=$sock->GET_INFO('LocalDNSMASQItems');
	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	if(!is_numeric($LocalDNSMASQItems)){$LocalDNSMASQItems=250000;}
	$DisableGoogleSSL=$sock->GET_INFO("DisableGoogleSSL");
	if(!is_numeric($DisableGoogleSSL)){$DisableGoogleSSL=0;}
	
	
	$html="
	
	<div id='$t-animate'></div>
	<div id='$t' class=text-info style='font-size:14px'>{dns_cache_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:99%' >
	<tr>
		<td class=legend style='font-size:16px'>{activate_dns_cache}:</td>
		<td>". Field_checkbox("EnableLocalDNSMASQ", 1,$EnableLocalDNSMASQ,"UnCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{enforce_google_to_non_ssl}:</td>
		<td>". Field_checkbox("DisableGoogleSSL-$t", 1,$DisableGoogleSSL)."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{cache_items}:</td>
		<td>". Field_text("LocalDNSMASQItems", $LocalDNSMASQItems,"font-size:16px;width:160px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","32px")."</td>
	</tr>
	</table>
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('system.services.cmd.php?APPNAME=dns_cache&action=restart&cmd=%2Fetc%2Finit.d%2Fdnsmasq');
		RefreshTab('squid_main_svc');
	
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableLocalDNSMASQ').checked){
			XHR.appendData('EnableLocalDNSMASQ',1);
		}else{
			XHR.appendData('EnableLocalDNSMASQ',0);
		}
		
		if(document.getElementById('DisableGoogleSSL-$t').checked){
			XHR.appendData('DisableGoogleSSL',1);
		}else{
			XHR.appendData('DisableGoogleSSL',0);
		}		
		
		XHR.appendData('LocalDNSMASQItems',document.getElementById('LocalDNSMASQItems').value);
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	function UnCheck$t(){
		document.getElementById('LocalDNSMASQItems').disabled=true;
		document.getElementById('DisableGoogleSSL-$t').disabled=true;
		if(!document.getElementById('EnableLocalDNSMASQ').checked){return;}
		document.getElementById('LocalDNSMASQItems').disabled=false;
		document.getElementById('DisableGoogleSSL-$t').disabled=false
		}
	UnCheck$t();
	</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function EnableLocalDNSMASQ(){
	$sock=new sockets();
	$sock->SET_INFO("EnableLocalDNSMASQ", $_POST["EnableLocalDNSMASQ"]);
	$sock->SET_INFO("LocalDNSMASQItems", $_POST["LocalDNSMASQItems"]);
	$sock->SET_INFO("DisableGoogleSSL", $_POST["DisableGoogleSSL"]);
	$sock->getFrameWork("cmd.php?force-restart-squidonly=yes&force=yes&nohup=yes&ApplyConfToo=yes");
	
}