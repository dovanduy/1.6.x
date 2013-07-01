<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');

	if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{failover}");
	echo "YahooWin6('650','$page?popup=yes','$title')";

}

function popup(){
	$tpl=new templates();
	$html="<div style='font-size:16px' class=explain>{squid_php_failover_explain}</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

