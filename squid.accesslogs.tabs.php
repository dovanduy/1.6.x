<?php

if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
		$GLOBALS["AS_ROOT"]=true;
		include_once(dirname(__FILE__).'/framework/class.unix.inc');
		include_once(dirname(__FILE__)."/framework/frame.class.inc");
		include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
		include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
		include_once(dirname(__FILE__)."/framework/class.settings.inc");
	}}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.html.pages.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.highcharts.inc');

	tabs();
	
function tabs(){
	
	$fontsize=18;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	
	if($SquidUrgency==1){
		echo FATAL_ERROR_SHOW_128(
			"<div style='font-size:22px'>{proxy_in_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_emergency_mode_explain}</div>
			<div style='text-align:right'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?justbutton=yes');\"
			style='text-decoration:underline'>{disable_emergency_mode}</a></div>	
			");
		return;
		
	}
	
	$array["thishour"]='{this_hour}';
	$array["thishour2"]='{this_hour} ({compressed})';
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="thishour"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}

	
		if($num=="thishour2"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.compressed.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}	
		
		if($num=="thishour3"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.day.compressed.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_logs_subtabs");
	
		
	
}
