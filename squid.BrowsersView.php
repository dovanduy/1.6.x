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
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	tabs();
	
	
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$fontsize="style='font-size:18px'";
	
	$array["members"]="{connected_members}";
	$array["browser"]="{browsers}";
	$array["browser-rules"]="{browsers_rules}";
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="members"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.BrowsersView.members.php\" $fontsize><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="browser"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.browsers.php?popup=yes\" $fontsize><span>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="browser-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.browsers-rules.php?popup=yes\" $fontsize><span>$ligne</span></a></li>\n");
			continue;
		}		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" $fontsize><span>$ligne</span></a></li>\n");
	
			
	}
	echo build_artica_tabs($html, "squid_main_useragents",1150);
	
	
	
	
}