<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	header("content-type: application/javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["page"])){page();exit;}

js();

function js(){
	header("content-type: application/javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{services_operations}");
	echo "YahooWin6('850','$page?page=yes','$title')";
	
	
	
}


function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$button_reconfigure=button("{reconfigure}","Loadjs('squid.compile.progress.php');",32);
	$button_reload=button("{reload}","Loadjs('squid.reload.php');",32);
	$button_restart=button("{restart}","Loadjs('squid.restart.php');",32);
	$button_purge=button("DNS: {purge}","Loadjs('squid.dns.status.php?purge-js');",32);
	$button_squid_logrotate_perform=button("{rotate}","Loadjs('squid.rotate.progress.php');",32);
	
	
	
	$sock=new sockets();
	$SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
	if(!is_numeric($SquidSimpleConfig)){$SquidSimpleConfig=1;}
	
	
	
	$tr[]="
	<div style='font-size:42px'>Proxy: {services_operations}</div>";
	$tr[]="<center style='margin-top:15px'>
	<table style='width:100%'>
	<tr>
		<td style='text-align:right;height:130px' style='width:1%'>$button_reload</td>
		<td style='font-size:16px;text-align:left'><br>{reload_squid_explain}</td>
	</tr>
	";
		
	
	$tr[]="
	
	<tr>
		<td style='text-align:right;height:130px'>$button_reconfigure</td>
		<td style='font-size:16px;text-align:left'><br>{reconfigure_squid_explain}</td>
	</tr>";
	
	$tr[]="
	
	<tr>
	<td style='text-align:right;height:130px'>$button_restart</td>
	<td style='font-size:16px;text-align:left'><br>{restart_squid_explain}</td>
	</tr>
	";
	
	$tr[]="
	
	<tr>
	<td style='text-align:right;height:130px'>$button_squid_logrotate_perform</td>
	<td style='font-size:16px;text-align:left'><br>{squid_logrotate_perform}</td>
	</tr>
	
	";	
	
	$tr[]="
	<tr>
	<td style='text-align:right;height:130px'>$button_purge</td>
	<td style='font-size:16px;text-align:left'><br>{purgedns_squid_explain}</td>
	</tr>
	";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}