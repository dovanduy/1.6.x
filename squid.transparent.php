<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	tabs();
function tabs(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["transparent"]="{transparent_mode}";
	$array["ssl"]="{decrypt_ssl}";
	$array["network_rules"]="{network_rules}";
	
	$array["mikrotik"]="Mikrotik";
	$array["wccpl3"]="{WCCP_LAYER3}";
	
	//$array["wccp"]="WCCP";
	;
	
	
	$fontsize=22;
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="ssl"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.sslbump.php?popup=yes&t=$t\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		if($num=="mikrotik"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.transparent.mikrotik.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="network_rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.transparent.networks.php?t=$t\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		
		if($num=="wccp"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.wccpv2.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		if($num=="wccpl3"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.wccpl3.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="transparent"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.newbee.php?squid-transparent-http=yes&t=$t\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}
	
	}
	
	echo build_artica_tabs($html, "squid_transparent_popup_tabs")."<script>LeftDesign('transparent-256-opac20.png');</script>";
	
	
}	
	