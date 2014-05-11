<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit;}

	
	if(isset($_GET["install-js"])){install_js();exit;}
	if(isset($_GET["install-popup"])){install_popup();exit;}
	
function install_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("APP_DHCP");
	header("content-type: application/x-javascript");
	$html="YahooWin2('350','$page?install-popup=yes&nic={$_GET["nic"]}','$title::{$_GET["nic"]}');";
	echo $html;
}

function install_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$data=unserialize(base64_decode($sock->getFrameWork("dnsmasq.php?dhcp-installed={$_GET["nic"]}")));
	
	
	
}