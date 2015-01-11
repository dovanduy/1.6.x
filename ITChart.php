<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');

	
	if(isset($_GET["popup"])){popup();exit;}

	js();
	
	
function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$YahooWin=2;
		if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
		$title=$tpl->_ENGINE_parse_body("{IT_charter}");
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
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->CORP_LICENSE){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{ERROR_NO_LICENSE}</p>");
		
	}
	
	$html="<div class=text-info style='font-size:16px !important'>{IT_charter_explain}
			
			</div><p class=text-error>{only_miniadm}</p>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}