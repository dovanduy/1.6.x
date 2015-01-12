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
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$q=new mysql_squid_builder();
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	if($SquidPerformance<2){
		$array["thishour"]='{this_hour}';
	}
	$array["realtime"]="{realtime_requests}";

	if($SquidPerformance<2){
		$array["thisday"]='{this_day}';
	}	
	
	if($users->APP_UFDBGUARD_INSTALLED){
		
		if($EnableUfdbGuard==1){
			$array["ufdb-logs"]="{webfiltering}";
			$array["ufdb-mysql"]="{blocked_websites}";
			$array["ufdb-unblocks"]="$webfilters_usersasks {unblocks}";
			
		}
	}
	
	
	$array["watchdog"]="{squid_watchdog_mini}";
	
	

	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="thishour"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.tabs.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="ufdb-unblocks"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.unblock.console.php?popup=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}	


		
		if($num=="thisday"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.day.compressed.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		

		if($num=="ufdb-mysql"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.blocked.events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
	
		if($num=="realtime"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.access.log.php?popup=yes&minsize=1\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="ufdb-logs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.access.webfilter.log.php?popup=yes&minsize=1\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}	
	
		if($num=="today-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.access.today.php?popup=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="events-ziproxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.zipproxy.access.php?popup=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
	
		if($num=="watchdog"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.watchdog-events.php\">
			<span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="events-squidcache"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.cachelogs.php\"><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_logs_tabs",1280)."<script>LeftDesign('logs-white-256-opac20.png');</script>";
	
	
	}	
