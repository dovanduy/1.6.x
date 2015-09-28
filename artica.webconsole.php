<?php
/*
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;
$GLOBALS["VERBOSE_SYSLOG"]=true;
*/
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!$GLOBALS["AS_ROOT"]){session_start();unset($_SESSION["MINIADM"]);unset($_COOKIE["MINIADM"]);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}
include_once("ressources/logs.inc");
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.stats-appliance.inc');
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}


tabs();


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$style="style=font-size:26px";
	
	$array["Wparameters"]="{parameters}";
	$array["perfs"]="{artica_performances}";
	$array["security"]="{security_access}";
	if($users->APACHE_MODE_WEBDAV){
		$array["webdav"]="{TAB_WEBDAV}";
	}
	$array["parameters"]="{HTTP_ENGINE}";
	//$array["others"]="{others}";
	
	while (list ($num, $ligne) = each ($array) ){
		$ligne=$tpl->javascript_parse_text($ligne);
		

		if($num=="Wparameters"){
			$html[]= "<li ><a href=\"artica.settings.php?js-web-interface=yes\"><span $style>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="webdav"){
			$html[]= "<li ><a href=\"artica.webdav.php\"><span $style>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="perfs"){
			$html[]= "<li ><a href=\"artica.performances.reboot.php\"><span $style>$ligne</span></a></li>\n";
			continue;
		}			
				
		if($num=="security"){
			$html[]= "<li ><a href=\"artica.web.fw.php\"><span $style>$ligne</span></a></li>\n";
			continue;
		}
				
		if($num=="parameters"){
			$html[]= "<li ><a href=\"artica.settings.php?js-web-interface2=yes\"><span $style>$ligne</span></a></li>\n";
			continue;
		}
				
		if($num=="others"){
			$html[]= "<li ><a href=\"admin.tabs.php?main=system&newfrontend=yes\"><span $style>$ligne</span></a></li>\n";
			continue;
		}
				

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"admin.tabs.php?tab=$num$newfrontend\"><span $style>$ligne</span></a></li>\n");
	}
	
	$t=time();
	
	echo build_artica_tabs($html, "artica_web_interface_tabs")."<script>LeftDesign('dashboard-256-opac20.png');</script>";
}