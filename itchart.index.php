<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
tabs();


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["parameters"]='{parameters}';
	$array["it_charters"]='{it_charters}';
	$array["events"]='{events}';

	
	
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:26px'><a href=\"itchart.parameters.php\"><span>{parameters}</span></a></li>\n");
		}
		

		if($num=="it_charters"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:26px'><a href=\"itchart.table.php\"><span>
				$ligne</span></a></li>\n");
		}

		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:26px'><a href=\"itchart.events.php\"><span>
					$ligne</span></a></li>\n");
		}
	
	}
	
	
	echo build_artica_tabs($html, "itcharters_tabs");	
	
	
}
