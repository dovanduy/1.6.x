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
if(isset($_POST["reconfigure-squid"])){reconfigure_squid();exit;}
if(isset($_POST["restart-squid"])){restart_squid();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$blackbox=new blackboxes($_GET["nodeid"]);
	$hostid=$_GET["hostid"];
	$t=time();
	$tpl=new templates();	
	$t=time();
	
	$actions[]=Paragraphe32("reload_proxy_service", "reload_proxy_service_text", "SquidNodeReload$t()", "reload-32.png");
	$actions[]=Paragraphe32("restart_proxy_service", "restart_proxy_service_text", "SquidNodeRestart$t()", "service-restart-32.png");
	
	
	
	$action=CompileTr3($actions);
		
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>{ipaddr}:</td>
			<td><strong style='font-size:14px'><strong style='font-size:14px'>{$blackbox->ipaddress}:{$blackbox->port}</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px'>{APP_SQUID}:</td>
			<td><strong style='font-size:14px'><strong style='font-size:14px'>{$blackbox->squid_version}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{last_status}:</td>
			<td><strong style='font-size:14px'>$blackbox->laststatus</td>
		</tr>		
	</table>
	$action
	
	<script>
	var x_SquidNodeReload$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		document.getElementById('$t').innerHTML='';
		if(document.getElementById('main_squid_quicklinks_tabs{$_GET["nodeid"]}')){
			RefreshTab('main_squid_quicklinks_tabs{$_GET["nodeid"]}');
		}
	}	


	function SquidNodeReload$t(){
		var XHR = new XHRConnection();
		XHR.appendData('reconfigure-squid','$hostid');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SquidNodeReload$t);
	}
	
	function SquidNodeRestart$t(){
		var XHR = new XHRConnection();
		XHR.appendData('restart-squid','$hostid');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SquidNodeReload$t);
	}	
	
	
	</script>
	
	";
	

	
	
	
echo $tpl->_ENGINE_parse_body($html);
}
function reconfigure_squid(){
	$tpl=new templates();
	$hostid=$_POST["reconfigure-squid"];
	$q=new blackboxes($hostid);
	if(!$q->reconfigure_squid()){$tpl->javascript_parse_text("{failed}: $q->ipaddress");return;}
	echo $tpl->javascript_parse_text("{success}: $q->ipaddress");
	
}

function restart_squid(){
	$tpl=new templates();
	$hostid=$_POST["restart-squid"];
	$q=new blackboxes($hostid);
	if(!$q->restart_squid()){$tpl->javascript_parse_text("{failed}: $q->ipaddress");return;}
	echo $tpl->javascript_parse_text("{success}: $q->ipaddress");	
	
}
