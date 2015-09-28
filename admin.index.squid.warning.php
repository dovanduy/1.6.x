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
	include_once('ressources/class.rrd.inc');

if($GLOBALS["VERBOSE"]){echo "<H1>injectSquid()</H1>\n";}
$sock=new sockets();
$users=new usersMenus();
if(!$users->SQUID_INSTALLED){die();}

$tpl=new templates();
$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
$MyBrowsersSetupShow=$sock->GET_INFO("MyBrowsersSetupShow");
$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
if(!is_numeric($MyBrowsersSetupShow)){$MyBrowsersSetupShow=0;}
if($SQUIDEnable==0){return;}
$CategoriesDatabasesShowIndex=$sock->GET_INFO("CategoriesDatabasesShowIndex");
if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}


$TOTAL_MEM_POURCENT_USED=intval(base64_decode($sock->getFrameWork("system.php?TOTAL_MEM_POURCENT_USED=yes")));

if($TOTAL_MEM_POURCENT_USED>79){
		if($users->KAV4PROXY_INSTALLED){
			$kavicapserverEnabled=intval($sock->GET_INFO("kavicapserverEnabled"));
			if($kavicapserverEnabled==1){
				$html="<div style='margin-bottom:15px'>".
						Paragraphe("warning64.png", "{antivirus_eat_memory}","{antivirus_eat_memory_text}",
								"javascript:Loadjs('kav4Proxy.disable.progress.php')","go_to_section",715,132,1);
				echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
	}
}
	

if($LogsWarninStop==1){
	$LogsWarninStop_CONTENT=FATAL_ERROR_SHOW_128("<div style='font-size:20px'>{squid_logs_urgency}</div>
	<div style='text-align:right;font-size:22px;text-align:right;text-decoration:underline;margin-top:20px'>
	<a href=\"javascript:Loadjs('system.log.emergency.php')\">{squid_logs_urgency_section}</a>
	</div>");

}
if($EnableKerbAuth==1){
	if($ActiveDirectoryEmergency==1){
		$html="<div style='margin-bottom:15px'>".
				Paragraphe("warning64.png", "{activedirectory_emergency_mode}","{activedirectory_emergency_mode_explain}",
						"javascript:Loadjs('squid.urgency.php?activedirectory=yes')","go_to_section",715,132,1);
		echo $tpl->_ENGINE_parse_body($html)."</div>";	
		
	}
}

if($SquidSSLUrgency==1){
	$html="<div style='margin-bottom:15px'>".
			Paragraphe("warning64.png", "{proxy_in_ssl_emergency_mode}","{proxy_in_ssl_emergency_mode_explain}",
					"javascript:Loadjs('squid.urgency.php?ssl=yes')","go_to_section",715,132,1);
	echo $tpl->_ENGINE_parse_body($html)."</div>";
}

if($SquidUrgency==1){
	$html="<div style='margin-bottom:15px'>".
			Paragraphe("warning64.png", "{proxy_in_emergency_mode}","{proxy_in_emergency_mode_explain}",
			"javascript:Loadjs('squid.urgency.php?justbutton=yes')","go_to_section",715,132,1);
		echo $tpl->_ENGINE_parse_body($html)."</div>";
			
			
}else{
	
	
	if($SquidDebugAcls==1){
			$html="<div style='margin-bottom:15px'>".
					Paragraphe("warning-panneau-64.png", "{acl_in_debug_mode}","{acl_in_debug_mode_explain}",
							"javascript:Loadjs('squid.acls.options.php',true)","go_to_section",715,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
				
		}
	

if($users->KAV4PROXY_INSTALLED){
	$licenseerror=base64_decode($sock->getFrameWork("squid.php?kav4proxy-license-error=yes"));
	if($licenseerror<>null){
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body("{KAV_LICENSE_ERROR_EXPLAIN}");
	$text=str_replace("%s", "«{$licenseerror}»", $text);
	echo $tpl->_ENGINE_parse_body(Paragraphe("64-red.png", "Kaspersky {license_error}",$text,
	"javascript:Loadjs('Kav4Proxy.license-manager.php',true)","go_to_section",715,132,1));
	}
}	
	
	
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==1){
		$StoreIDUrgency=intval($sock->GET_INFO("StoreIDUrgency"));
		if($StoreIDUrgency==1){
			$html="<div style='margin-bottom:15px'>".
					Paragraphe("warning64.png", "{hypercache_in_emergency_mode}","{hypercache_in_emergency_mode_explain}",
							"javascript:Loadjs('squid.urgency.hypercache.php')","go_to_section",715,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
	}
}




if($users->APP_UFDBGUARD_INSTALLED){
		$datasUFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		if(!is_numeric($datasUFDB["DebugAll"])){$datasUFDB["DebugAll"]=0;}
		if($datasUFDB["DebugAll"]==1){
			$html="<div style='margin-bottom:15px'>".
					Paragraphe("warning64.png", "{webfiltering_in_debug_mode}","{webfiltering_in_debug_mode_text}",
							"javascript:Loadjs('ufdbguard.debug.php')","go_to_section",715,132,1);
			echo $tpl->_ENGINE_parse_body($html)."</div>";
		}
	}
