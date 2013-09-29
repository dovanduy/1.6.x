<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["statsdb"])){statsdb_tabs();exit;}
	if(isset($_GET["webfdb"])){webfdb_tabs();exit;}
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$width=950;
	$statusfirst=null;
	$title=$tpl->_ENGINE_parse_body("{update_parameters}");
	$YahooWin="YahooWinS";
	$start="$YahooWin('$width','$page?tabs=yes','$title');";
	$html="$start";
	echo $html;

}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["statsdb"]="{statistics_database}";
	$array["webfdb"]="{webfiltering_databases}";
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="statsdb"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="webfdb"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}	
		
	}
	$t=time();
	echo build_artica_tabs($html, "webfilter_db_tabs");	
	
}

function webfdb_tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]="{status}";
	$array["schedule"]="{schedules}";

	while (list ($num, $ligne) = each ($array) ){

		if($num=="status"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"dansguardian2.databases.php?status=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="schedule"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"squid.databases.schedules.php?TaskType=30\"><span>$ligne</span></a></li>\n");
			continue;
		}

	}
	$t=time();
	echo build_artica_tabs($html, "webfilter_ufdb_tabs");

}

function statsdb_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array["status"]="{status}";
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
	if($DisableArticaProxyStatistics==0){
		$array["schedule"]="{schedules}";
	}
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="status"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"dansguardian2.databases.php?statusDB=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="schedule"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"squid.databases.schedules.php?TaskType=1\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:13px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	
	}
	$t=time();
	echo build_artica_tabs($html, "webfilter_mysqldb_tabs");	
	
}

