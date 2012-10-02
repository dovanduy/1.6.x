<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}


if(isset($_POST["ReconfigureUfdb"])){ReconfigureUfdb();exit;}
if(isset($_GET["services"])){section_services();exit;}
if(isset($_GET["status"])){status_start();exit;}
if(isset($_GET["status-left"])){status_squid_left();exit;}
if(isset($_GET["squid-services"])){all_status();exit;}
if(isset($_GET["architecture-tabs"])){section_architecture_tabs();exit;}
if(isset($_GET["architecture-status"])){section_architecture_status();exit;}
if(isset($_GET["architecture-content"])){section_architecture_content();exit;}
if(isset($_GET["architecture-adv"])){section_architecture_advanced();exit;}
if(isset($_GET["architecture-users"])){section_architecture_users();exit;}
if(isset($_GET["architecture-filters"])){section_architecture_filters();exit;}
if(isset($_GET["ptx-status"])){ptx_status();exit;}



if(isset($_GET["members-status"])){section_members_status();exit;}
if(isset($_GET["members-content"])){section_members_content();exit;}
if(isset($_GET["basic_filters-content"])){section_basic_filters_content();exit;}
if(isset($_GET["basic_filters-tabs"])){section_basic_filters_tabs();exit;}



//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$users->AsAnAdministratorGeneric){die("Not autorized");}
if(isset($_GET["off"])){off();exit;}
if(function_exists($_GET["function"])){call_user_func($_GET["function"]);exit;}

$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
$StatsPerfsSquidAnswered=$sock->GET_INFO("StatsPerfsSquidAnswered");
if(!is_numeric($StatsPerfsSquidAnswered)){$StatsPerfsSquidAnswered=0;}
if($DisableArticaProxyStatistics==0){
	if(!$users->WEBSTATS_APPLIANCE){if($StatsPerfsSquidAnswered==0){$CPU=$users->CPU_NUMBER;$MEM=$users->MEM_TOTAL_INSTALLEE;if(($CPU<4) AND (($MEM<3096088))){WARN_SQUID_STATS();die();}}}
}



$statisticsAdded=false;
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("service-check-48.png", "services_status","system_information_text", "QuickLinkSystems('section_status')"));
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-parameters.png", "proxy_parameters","section_security_text", "QuickLinkSystems('section_architecture')"));
if($users->AsSquidAdministrator){$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-tasks.png", "tasks","", "QuickLinkSystems('section_tasks')"));}

//$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-network-user.png", "members","softwares_mangement_text", "QuickLinkSystems('section_members')"));
//$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("Firewall-Secure-48.png", "basic_filters","softwares_mangement_text", "QuickLinkSystems('section_basic_filters')"));

	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-filtering-48.png", "WEB_FILTERING","softwares_mangement_text", "QuickLinkSystems('section_webfiltering_dansguardian')"));

if($users->KAV4PROXY_INSTALLED){
	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("bigkav-48.png", "APP_KAV4PROXY","softwares_mangement_text", "QuickLinkSystems('section_kav4proxy')"));
	
}
	if($EnableRemoteStatisticsAppliance==0){
		if($DisableArticaProxyStatistics==0){
			$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "SquidQuickLinks()"));
			$statisticsAdded=true;
		}
	}



$count=1;

while (list ($key, $line) = each ($tr) ){if($line==null){continue;}$tr2[]=$line;}

if(count($tr2)<6){
	$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-logs.png", "PROXY_EVENTS","PROXY_EVENTS", "QuickLinkSystems('section_squid_rtmm')"));
}

$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-site-48.png", "main_interface","main_interface_back_interface_text", "QuickLinksHide()"));

while (list ($key, $line) = each ($tr2) ){
	if($line==null){continue;}
	$f[]="<li id='kwick1'>$line</li>";
	$count++;
	
}




while (list ($key, $line) = each ($GLOBALS["QUICKLINKS-ITEMS"]) ){
	
	$jsitems[]="\tif(document.getElementById('$line')){document.getElementById('$line').className='QuickLinkTable';}";
}

$start="		
LoadQuickTaskBar();
setTimeout('QuickLinkMemory()',800);
";

if(isset($_GET["NoStart"])){$start=null;}


	
	$html="
            <div id='QuickLinksTop'>
                <ul class='kwicks'>
					".@implode("\n", $f)."
                    
                </ul>
            </div>
	
	<div id='quicklinks-samba' style='width:900px'></div>
	<div id='BodyContent' style='width:900px'></div>
	
	
	<script>
		function LoadQuickTaskBar(){
			$(document).ready(function() {
				$('#QuickLinksTop .kwicks').kwicks({max: 205,spacing:  5});
			});
		}
		
		function QuickLinksSamba(){
			Set_Cookie('QuickLinkCache', 'quicklinks.fileshare.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.fileshare.php');
		}
		
		function QuickLinksProxy(){
			Set_Cookie('QuickLinkCache', 'quicklinks.proxy.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.proxy.php');		
		
		}
		
		function QuickLinksKav4Proxy(){
			Set_Cookie('QuickLinksKav4Proxy', 'kav4proxy.php?inline=yes', '3600', '/', '', '');
			LoadAjax('BodyContent','kav4proxy.php?inline=yes');		
		
		}		
		
		
		
		function QuickLinkSystems(sfunction){
			if(sfunction=='section_squid_rtmm'){
				s_PopUp('squid.accesslogs.php?external=yes',1024,768);
				return;
			}
			Set_Cookie('QuickLinkCacheProxy', '$page?function='+sfunction, '3600', '/', '', '');
			LoadAjax('BodyContent','$page?function='+sfunction);
		}
		
		function QuickLinkMemory(){
			QuickLinkSystems('section_status');
			return;
			
					
		}
		
		
		function QuickLinkShow(id){
			".@implode("\n", $jsitems)."
			if(document.getElementById(id)){document.getElementById(id).className='QuickLinkOverTable';}
			}			
				
		
$start
	</script>
	";
	
	
	


$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);





function section_tasks(){echo "<script>LoadAjax('BodyContent','squid.statistics.tasks.php');QuickLinkShow('quicklinks-tasks');</script>";}

function section_webfiltering_dansguardian(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div id='QuicklinksDansguardian'></div>
	<script>
		LoadAjax('QuicklinksDansguardian','dansguardian2.php');
		QuickLinkShow('quicklinks-WEB_FILTERING');
		
	</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	}	
	



function section_kav4proxy(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div id='QuicklinksKav4proxy'></div>
	<script>
		LoadAjax('QuicklinksKav4proxy','kav4proxy.php?inline=yes');
		QuickLinkShow('quicklinks-APP_KAV4PROXY');
	</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	}

function section_members(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	
	
	<table style='width:100%'>
	<tbody>
		<tr>
			<td style='width:1%' valign='top'><div id='members-status'></div></td>
			<td style='width:99%;padding-left:10px' valign='top'>
			<div class=explain>{squid_members_explain}</div>
			<div id='members-content' class=form style='width:99%'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('members-status','$page?members-status=yes');
		QuickLinkShow('quicklinks-members');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function section_architecture_filters(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div class=explain>{squid_basic_filters_explain}</div>
	<div id='basic_filters-content'></div>	
	<script>
		LoadAjax('basic_filters-content','$page?basic_filters-tabs=yes');
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
	
}


function section_architecture(){
	$page=CurrentPageName();
	$tpl=new templates();
	echo "<div id='squid-section-architecture'></div>
		<script>
		LoadAjax('squid-section-architecture','$page?architecture-tabs=yes');
		QuickLinkShow('quicklinks-parameters');
	</script>
	
	";

}	
	
function section_architecture_start(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div class=explain>{squid_architecture_explain}</div>
	
	<table style='width:100%'>
	<tbody>
		<tr>
			<td style='width:1%' valign='top'><div id='architecture-status'></div></td>
			<td style='width:99%' valign='top'><div id='architecture-content'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('architecture-status','$page?architecture-status=yes');
		QuickLinkShow('quicklinks-parameters');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}




function section_members_content(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$authenticate_users=Paragraphe('members-priv-64.png','{authenticate_users}','{authenticate_users_text}',"javascript:Loadjs('squid.popups.php?script=ldap')");	
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	$blackcomputer=Paragraphe("64-black-computer.png","{black_ip_group}",'{black_ip_group_text}',"javascript:Loadjs('dansguardian.bannediplist.php');");
	$whitecomputer=Paragraphe("64-white-computer.png","{white_ip_group}",'{white_ip_group_text}',"javascript:Loadjs('dansguardian.exceptioniplist.php');");

	if(!$users->MSKTUTIL_INSTALLED){
		$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	}
	if(strlen($users->squid_kerb_auth_path)<2){
		$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	}	
	

	$tr[]=$APP_SQUIDKERAUTH;
	$tr[]=$authenticate_users;
	$tr[]=$blackcomputer;
	$tr[]=$whitecomputer;
	
	$html=CompileTr3($tr);
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
	
		
}

function section_architecture_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["architecture-content"]='{main_parameters}';
	$array["caches"]='{caches}';
	$array["architecture-users"]='{users_interactions}';
	$array["architecture-adv"]='{advanced_options}';
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.php?byQuicklinks=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "$menus
	<div id=main_squid_quicklinks_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_squid_quicklinks_tabs').tabs();
			});
		</script>";	

}

function section_architecture_advanced(){
	$sock=new sockets();

	$squid_parent_proxy=Paragraphe('server-redirect-64.png','{squid_parent_proxy}','{squid_parent_proxy_text}',"javascript:Loadjs('squid.parent.proxy.php')");
	$squid_reverse_proxy=Paragraphe('squid-reverse-64.png','{squid_reverse_proxy}','{squid_reverse_proxy_text}',"javascript:Loadjs('squid.reverse.proxy.php')");
	$squid_advanced_parameters=Paragraphe('64-settings.png','{squid_advanced_parameters}','{squid_advanced_parameters_text}',"javascript:Loadjs('squid.advParameters.php')");
	$squid_conf=Paragraphe('script-view-64.png','{configuration_file}','{display_generated_configuration_file}',"javascript:Loadjs('squid.conf.php')");
	$performances_tuning=Paragraphe('performance-tuning-64.png','{tune_squid_performances}','{tune_squid_performances_text}',"javascript:Loadjs('squid.perfs.php')");
	$denywebistes=Paragraphe("folder-64-denywebistes.png","{deny_websites}","{deny_websites_text}","javascript:Loadjs('squid.popups.php?script=url_regex');");
	if($sock->GET_INFO("SquidActHasReverse")==1){
    	$squid_accl_websites=Paragraphe('website-64.png','{squid_accel_websites}','{squid_accel_websites_text}',"javascript:Loadjs('squid.reverse.websites.php')");
    }
    
    $redirectors_options=Paragraphe('redirector-64.png','{squid_redirectors}','{squid_redirectors_text}',
    "javascript:Loadjs('squid.redirectors.php')");  

    
    $memory_option=Paragraphe('bg_memory-64.png','{cache_mem}','{cache_mem_text}',
    "javascript:Loadjs('squid.cache_mem.php')");  
    $dns_servers=Paragraphe('64-bind.png','{dns_servers}','{dns_servers_text}',"javascript:Loadjs('squid.popups.php?script=dns')");
    
    $syslog=Paragraphe("syslog-64.png", "Syslog", "{squid_syslog_text}","javascript:Loadjs('squid.syslog.php')");
    
    $sarg=Paragraphe('sarg-logo.png','{APP_SARG}','{APP_SARG_TXT}',"javascript:Loadjs('sarg.php')","{APP_SARG_TXT}");
    
    $disable_stats=Paragraphe('statistics-64.png','{ARTICA_STATISTICS}','{ARTICA_STATISTICS_TEXT}',"javascript:Loadjs('squid.artica.statistics.php')","{ARTICA_STATISTICS_TEXT}");
    $loadbalancing=Paragraphe("64-computer-alias.png", "{proxy_child}", "{squid_balancersHapxy_explain}","javascript:Loadjs('squid.children.php')");
    $anonym=Paragraphe("hearth-blocked-64.png", "{anonymous_browsing}", "{anonymous_browsing_explain}","javascript:Loadjs('squid.anonymous.php')");
    
    $csvstats=Paragraphe("csv-64.png", "{squid_csv_logs}", "{squid_csv_logs_explain}","javascript:Loadjs('squid.csv.php')");
    
    
    
    $tr[]=$squid_conf;
    $tr[]=$squid_advanced_parameters;
    $tr[]=$memory_option;
    $tr[]=$dns_servers;
    $tr[]=$performances_tuning;
    $tr[]=$loadbalancing;
    $tr[]=$redirectors_options;
    $tr[]=$denywebistes;
    $tr[]=$anonym;
    $tr[]=$syslog;
    $tr[]=$disable_stats;
    $tr[]=$sarg;
    $tr[]=$csvstats;
    $tr[]=$squid_parent_proxy;
    $tr[]=$squid_reverse_proxy;
    
    
    $html=CompileTr3($tr);
    
	$html="<center><div style='width:700px'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
}



function section_architecture_users(){
	$sock=new sockets();
	
	$authenticate_users=Paragraphe('members-priv-64.png','{authenticate_users}','{authenticate_users_text}',"javascript:Loadjs('squid.popups.php?script=ldap')");	
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	//$blackcomputer=Paragraphe("64-black-computer.png","{black_ip_group}",'{black_ip_group_text}',"javascript:Loadjs('dansguardian.bannediplist.php');");
	//$whitecomputer=Paragraphe("64-white-computer.png","{white_ip_group}",'{white_ip_group_text}',"javascript:Loadjs('dansguardian.exceptioniplist.php');");
    $proxy_pac_rules=Paragraphe('user-script-64.png','{proxy_pac_rules}','{proxy_pac_text}',"javascript:Loadjs('squid.proxy.pac.rules.php')");
    $templates_error=Paragraphe('squid-templates-64.png','{squid_templates_error}','{squid_templates_error_text}',"javascript:Loadjs('squid.templates.php')");
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
 
    
    
    if(($sock->GET_INFO("SquidActHasReverse")==1)){
    	$proxy_pac=Paragraphe('user-script-64-grey.png','{proxy_pac}','{proxy_pac_text}');
    	$proxy_pac_rules=Paragraphe('proxy-pac-rules-64-grey.png','{proxy_pac_rules}','{proxy_pac_text}');
    	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',
    	"javascript:Loadjs('squid.adker.php')");
    	
    }
    
    $SESSIONS_MANAGER=Paragraphe('64-smtp-auth.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"javascript:Loadjs('squid.sessions.php')");
	$SESSIONS_MANAGER=Paragraphe('64-smtp-auth-grey.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"");
	$ISP_MODE=Paragraphe('isp-64.png','{SQUID_ISP_MODE}','{SQUID_ISP_MODE_EXPLAIN}',"javascript:Loadjs('squid.isp.php')");

    
    
    $tr[]=$SESSIONS_MANAGER;
    $tr[]=$authenticate_users;
	$tr[]=$APP_SQUIDKERAUTH;
	$tr[]=$ISP_MODE;
	$tr[]=$proxy_pac_rules;
	$tr[]=$templates_error;
	
	
	$html=CompileTr3($tr);
	
	$t=time();
	echo "<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','squid.adker.php?status=yes&t=$t');
		QuickLinkShow('quicklinks-proxy_parameters');
	</script>
	
	";	
	
	$html="
	<div class=explain>{squid_members_explain}</div>
	<center><div style='width:700px'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
}


function section_architecture_content(){
$page=CurrentPageName();
$sock=new sockets();
$users=new usersMenus();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){
		$sock->getFrameWork("squid.php?compil-params=yes");
	}
	
	$COMPILATION_PARAMS=unserialize(base64_decode(file_get_contents($compilefile)));
	
	
	
	$listen_port=Paragraphe('folder-network-64.png','{listen_port}','{listen_port_text}',"javascript:Loadjs('squid.popups.php?script=listen_port')");
	$listen_addr=Paragraphe('folder-network-64.png','{listen_address}','{squid_listen_text}',"javascript:Loadjs('squid.nic.php')");
	$visible_hostname=Paragraphe('64-work-station-linux.png','{visible_hostname}','{visible_hostname_intro}',"javascript:Loadjs('squid.popups.php?script=visible_hostname')");
	$transparent_mode=Paragraphe('relayhost.png','{transparent_mode}','{transparent_mode_text}',"javascript:Loadjs('squid.newbee.php?squid-transparent-js=yes')");
	$your_network=Paragraphe('folder-realyrules-64.png','{your_network}','{your_network_text}',"javascript:Loadjs('squid.popups.php?script=network')");
    $stat_appliance=Paragraphe("64-dansguardian-stats.png","{STATISTICS_APPLIANCE}","{STATISTICS_APPLIANCE_TEXT}","javascript:Loadjs('squid.stats-appliance.php')");
	$sslbump=Paragraphe('web-ssl-64.png','{squid_sslbump}','{squid_sslbump_text}',"javascript:Loadjs('squid.sslbump.php')");
	$watchdog=Paragraphe('service-check-64-grey.png','{squid_watchdog}','{squid_watchdog_text}',"");
	
	
	
	$ftp_user=Paragraphe('ftp-user-64.png','{squid_ftp_user}','{squid_ftp_user_text}',"javascript:Loadjs('squid.ftp.user.php')");
	$messengers=Paragraphe('messengers-64.png','{instant_messengers}','{squid_instant_messengers_text}',"javascript:Loadjs('squid.messengers.php')");	
		
	$enable_squid_service=Paragraphe('bg-server-settings-64.png','{enable_squid_service}','{enable_squid_service_text}',"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')");
    
    if(!isset($COMPILATION_PARAMS["enable-ssl"])){
    	$sslbump=Paragraphe('web-ssl-64-grey.png','{squid_sslbump}','{squid_sslbump_text}',"");
    }
    
    if($users->MONIT_INSTALLED){
    	$watchdog=Paragraphe('service-check-64.png','{squid_watchdog}','{squid_watchdog_text}',"javascript:Loadjs('squid.watchdog.php')");
 	}
 	
 	$booster=Paragraphe('perfs-64.png','{squid_booster}','{squid_booster_text}',"javascript:Loadjs('squid.booster.php')");
 	

 	

	$tr=array();
	$tr[]=$watchdog;
	$tr[]=$listen_port;
	$tr[]=$listen_addr;
	$tr[]=$visible_hostname;
	$tr[]=$transparent_mode;
	$tr[]=$your_network;
	$tr[]=$booster;
	$tr[]=$stat_appliance;
	$tr[]=$ftp_user;
	$tr[]=$messengers;
	$tr[]=$sslbump;
	$tr[]=$enable_squid_service;
	

	$html=CompileTr3($tr);
	
	
	
$tpl=new templates();
$html="<div id='architecture-status'></div>
<center style='width:100%'>
<div style='width:80%;text-align:center'>$html</div>
</center>
<script>
	LoadAjaxTiny('architecture-status','$page?architecture-status=yes');
	QuickLinkShow('quicklinks-proxy_parameters');
</script>";

$html=$tpl->_ENGINE_parse_body($html,'squid.index.php');
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	
	
}

function section_security(){
	
	$tr[]=kaspersky();
	$tr[]=statkaspersky();
	$tr[]=clamav();
	$tr[]=icon_troubleshoot();
	$tr[]=certificate();
	$tr[]=icon_externalports();
	$tr[]=incremental_backup();
$tables[]="<table style='width:99%' class=form><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}	
	

$links=@implode("\n", $tables);
$heads=section_computer_header();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'>$heads</td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
QuickLinkShow('quicklinks-services_status');
</script>
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function status_squid_left(){
	
	
	
	$tpl=new templates();
	$page=CurrentPageName();	
	include_once(dirname(__FILE__)."/ressources/class.status.inc");
	$status=new status();
	$squid_status=$status->Squid_status();
	
	$q=new mysql();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork("cmd.php?squid-ini-status=yes")));
	$master_version=$ini->_params["SQUID"]["master_version"];
	$master_pid=$ini->_params["SQUID"]["master_pid"];
	$users=new usersMenus();
	$squid=new squidbee();
	
	
	
	
if($ini->_params["SQUID"]["running"]==0){
		$img="status_postfix_bg_failed.png";
		$status="{stopped}";
		$start="<hr><div style='text-align:right'>".button("{start}","Loadjs('StartStopServices.php?APP=APP_SQUID&cmd=squid-cache&action=start')")."</div>";
	}else{
			if(preg_match("#2\.3.*#",$master_version)){$img='status_postfix_bg_ok23.png';}
			if(preg_match("#2\.5.*#",$master_version)){$img='status_postfix_bg_ok25.png';}
			if(preg_match("#2\.7.*#",$master_version)){$img='status_postfix_bg_ok27.png';}
			if(preg_match("#2\.6.*#",$master_version)){$img='status_postfix_bg_ok26.png';}			
			if(preg_match("#2\.8.*#",$master_version)){$img='status_postfix_bg_ok28.png';}
			if(preg_match("#2\.9.*#",$master_version)){$img='status_postfix_bg_ok29.png';}
			if(preg_match("#3\.0.*#",$master_version)){$img='status_postfix_bg_ok30.png';}
			if(preg_match("#3\.1.*#",$master_version)){$img='status_postfix_bg_ok31.png';}
			if(preg_match("#3\.2.*#",$master_version)){$img='status_postfix_bg_ok32.png';}
			if(preg_match("#3\.3.*#",$master_version)){$img='status_postfix_bg_ok33.png';}
			$text="{service_running}<br>{using_version} $master_version {pid} $master_pid";
	}

if($ini->_params["SQUID"]["icap_enabled"]<>'1'){
	
	$icap="<table style='width:100%;margin:0px;' " .CellRollOver($js_service).">
		<tr>
			<td width=1%><img src='img/danger16.png'></td>
			<td align='left' nowrap><strong style='color:#D01A1A;font-size:11px'>{no_icap_support}</td>
			<td width=1% align='right'>&nbsp;</td>
		</tr>
	</table>";
	
	
}

	$EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
	$KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
	$KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");	
	if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
	
	if($EnableKavICAPRemote==1){
		$fp=@fsockopen($KavICAPRemoteAddr, $KavICAPRemotePort, $errno, $errstr, 1);
			if(!$fp){
				$text_kavicap_error="<div>{kavicap_unavailable_text}<br><strong>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.kavicap.php');\" style='font-size:12px;color:#D70707;text-decoration:underline'>$KavICAPRemoteAddr:$KavICAPRemotePort</a><br>$errstr</div>";				
			}
		
		@fclose($fp);			
	}
	
	$q=new mysql_squid_builder();
	
	if(!$q->TestingConnection()){
		$img="status_postfix_bg_failed.png";
		$title="{MYSQL_ERROR}";
		$text_error_sql="<div><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.mysql.php');\" 
		style='font-size:12px;color:#D70707;text-decoration:underline'>$title:$q->mysql_error</a></div>";
	}	
	
	$q=new mysql_squid_builder();
	$requests=$q->EVENTS_SUM();
	$requests=numberFormat($requests,0,""," ");
	
	
	$tableblock=date('Ymd')."_blocked";
	$ligneW=$q->COUNT_ROWS($tableblock);
	$blocked_today=numberFormat($ligneW["tcount"],0,""," ")." {blocked_websites} {this_day}";
	
	$q=new mysql_squid_builder();
	$websitesnums=$q->COUNT_ROWS("dansguardian_sitesinfos","artica_backup");
	$websitesnums=numberFormat($websitesnums,0,""," ");	
	
	$q=new mysql_squid_builder();
	$categories=$q->COUNT_ROWS("dansguardian_community_categories");
	$categories=numberFormat($categories,0,""," ");		
	
	$sock=new sockets();
	$sock->SET_INFO("squidStatsCategoriesNum",$categories);
	$sock->SET_INFO("squidStatsWebSitesNum",$websitesnums);
	$sock->SET_INFO("squidStatsBlockedToday",$blocked_today);
	$sock->SET_INFO("squidStatsRequestNumber",$requests);
	
	$migration_pid=unserialize(base64_decode($sock->getFrameWork("squid.php?migration-stats=yes")));
	if(is_array($migration_pid)){
		$text_script="<span style='color:#B80000;font-size:13px'>{migration_script_run_text} PID:{$migration_pid[0]} {since}:{$migration_pid[1]}Mn</span>";
	}	
	
	$squidversion="	<table style='width:250px;margin-top:10px;' class=form>
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td class=legend nowrap>Proxy {version}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.compilation.status.php');\" style='font-size:14px;text-decoration:underline'>$master_version</a></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class=legend>PID:</td>
			<td style='font-size:14px'>{$master_pid}</td>
		</tr>
		</tbody>
	</table>";
	
	if($users->WEBSTATS_APPLIANCE){$squidversion=null;}
	
	$design="
	$text_error_sql
	$text_script
	$text_kavicap_error
	$squidversion
	<div id='squid-plugins-activated'></div>
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');")."</div>
	
	";
	
	$classform="class=form";
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){$classform=null;}	
	
	$html="
	$design
	<center>
	
		<div id='squid-status-stats' $classform style='width:90%'></div>
	</center>
	
	
	<script>
		LoadAjax('squid-status-stats','squid.traffic.statistics.php?squid-status-stats=yes');	
		LoadAjax('squid-services','$page?squid-services=yes');
		LoadAjax('squid-plugins-activated','dansguardian2.php?dansguardian-status=yes');
	</script>
	";
	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	
	
}


function status_start(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$html="
	<table style='width:100%'>
	<tr>
		<td width=1% valign='top'><div id='squid-status'></div></td>
		<td width=99% valign='top'><div id='squid-services'>". @implode("\n", $tables)."</div></td>
	</tr>
	</table>
	
	<script>
		LoadAjax('squid-status','$page?status-left=yes');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function section_members_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidbee();
	$listen_port=$squid->listen_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}
	
	if(!$squid->ACL_ARP_ENABLED){
		$arpinfos=
		"<table style='width:99%;margin-top:5px' class=form>
		<tbody>
		<tr>
			<td width:1% valign='top'><img src='img/warning-panneau-32.png'></td>
			<td><strong style='font-size:12px'>{no_acl_arp}</strong><br>
			<span style='font-size:11px'>{no_acl_arp_text}</span></td>
		</tr>
		</tbody>
		</table>";
		
		
	}else{
		
		$arpinfos=
		"<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td width:1% valign='top'><img src='img/32-infos.png'></td>
			<td><strong style='font-size:12px'>{yes_acl_arp}</strong><br>
			<span style='font-size:11px'>{yes_acl_arp_text}</span></td>
		</tr>
		</tbody>
		</table>";		
		
		
	}	
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap>{version}:</td>
		<td>".texthref($squid->SQUID_VERSION,null)."</td>
	</tr>	
	<tr>
		<td class=legend nowrap>{listen_port}:</td>
		<td>".texthref($listen_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>
	<tr>
		<td class=legend nowrap>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"Loadjs('squid.popups.php?script=visible_hostname')")."</td>
	</tr>	
	<tr>
		<td class=legend nowrap>{transparent_mode}:</td>
		<td>".texthref($hasProxyTransparent,"Loadjs('squid.newbee.php?squid-transparent-js=yes')")."</td>
	</tr>	
	
	</table>
	$arpinfos
	<script>
		LoadAjax('members-content','$page?members-content=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function section_architecture_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$listen_port=$squid->listen_port;
	$second_port=$squid->second_listen_port;
	$ssl_port=$squid->ssl_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	$js1="Loadjs('squid.popups.php?script=listen_port')";
	$js2="Loadjs('squid.popups.php?script=visible_hostname')";
	$js3="Loadjs('squid.newbee.php?squid-transparent-js=yes')";
	
	if($EnableRemoteStatisticsAppliance==1){$js1=null;$js2=null;$js3=null;}
	
	$labelport="{listen_port}";
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}
	if($second_port>0){$second_port="/$second_port";$labelport="{listen_ports}";}else{$second_port=null;}
	
	if($ssl_port>0){$second_port="$second_port/$ssl_port&nbsp;(ssl)";}
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	
	$squid_version_text="<td class=legend nowrap>{version}:</td>
		<td>".texthref($squid->SQUID_VERSION,"Loadjs('squid.compilation.status.php');")."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	$visible_hostname_text="		<td class=legend nowrap>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"$js2")."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	if($users->WEBSTATS_APPLIANCE){$squid_version_text=null;$visible_hostname_text=null;}
	
	$html="<table style='width:99%' class=form>
	<tr>
		$squid_version_text
		<td class=legend nowrap>$labelport:</td>
		<td>".texthref("$listen_port$second_port","$js1")."</td>
		<td style='font-size:14px;font-weight:bold'>&nbsp;|&nbsp;</td>
		$visible_hostname_text
		<td class=legend nowrap>{transparent_mode}:</td>
		<td>".texthref($hasProxyTransparent,"$js3")."</td>
	</tr>
	</table>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function section_status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_blackbox();
	$tpl=new templates();
	$language=$tpl->language;
	$array["status"]="{services_status}";
	$array["events-squidcache"]='{proxy_service_events}';
	$array["events-squidaccess"]='{realtime_requests}';
	if($q->TABLE_EXISTS("cacheitems_localhost")){
		$ct=$q->COUNT_ROWS("cacheitems_localhost");
		if($ct>0){
			$array["cached_items"]="$ct {cached_items}";
			
		}
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($users->WEBSTATS_APPLIANCE){unset($array["events-squidcache"]);}
	
	$array["graphs"]="{statistics}";
	$array["software-update"]='{softwares_update}';
	
	$fontsize=14;
	
	if($language=="fr"){$fontsize="12.5";}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="software-update"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.softwares.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.php?table-size=898&url-row=433\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		
		if($num=="cached_items"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.cached.itemps.php?hostid=localhost\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="graphs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.graphs.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}			
		
		if($num=="events-squidcache"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.cachelogs.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
		}
	echo "
	<div id=squid_main_svc style='width:100%;100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_main_svc').tabs();
			
			
			});
			
			QuickLinkShow('quicklinks-services_status');
		</script>";			
	
	
}

function all_status(){
	$t=time();
	echo "<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','squid.adker.php?status=yes&t=$t');
	</script>
	
	";
	
	if(CACHE_SESSION_GET(__FUNCTION__, ___FILE__,5)){return;}
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$users=new usersMenus();

	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));


	$squid_status=DAEMON_STATUS_ROUND("ID:SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("ID:DANSGUARDIAN",$ini,null,1);
	$kav=DAEMON_STATUS_ROUND("ID:KAV4PROXY",$ini,null,1);
	$cicap=DAEMON_STATUS_ROUND("ID:C-ICAP",$ini,null,1);
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("ID:APP_PROXY_PAC",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("ID:APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("ID:APP_UFDBGUARD",$ini,null,1);
	$squid=new squidbee();
	
	$md=md5(date('Ymhis'));
	if(!$users->WEBSTATS_APPLIANCE){
		$swappiness=intval($sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.swappiness")));
		$sock=new sockets();
		$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		if(!is_numeric($swappiness_saved["swappiness"])){
			if($swappiness>30){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{high_swap_value}",
				"{high_swap_value_text}","Loadjs('squid.perfs.php')");
			}
			
		}

		$SquidAsSeenDNS=$sock->GET_INFO("SquidAsSeenDNS");
		if(!is_numeric($SquidAsSeenDNS)){$SquidAsSeenDNS=0;}
		if( count($squid->dns_array)==0){
			if($SquidAsSeenDNS==0){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{add_dns_in_config}",
				"{add_dns_in_config_perf_explain}","Loadjs('squid.popups.php?script=dns')");
			}
		}
		
	}
	$tr[]=$squid_status;
	$tr[]=$dansguardian_status;
	$tr[]=$kav;
	$tr[]=$cicap;
	$tr[]=$APP_PROXY_PAC;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$APP_UFDBGUARD;
	
	

	
	
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}
	
	$tables[]="<div style='min-height:350px;'>
		<table style='width:100%'><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
			$line=trim($line);
			if($line==null){continue;}
			$t=$t+1;
			$tables[]="<td valign='top'>$line</td>";
			if($t==2){$t=0;$tables[]="</tr><tr>";}
			}
	
	if($t<2){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";				
		}
	}
	

	

	$SquidBoosterMemText="
		<tr>
			<td width=1%><img src='img/service-restart-32.png'></td>
			<td><div id='ptx-status'></div></td>
		</tr>
	";
	
	

	
	
	if($EnableUfdbGuard==1){
		$ufdbbutt="
			<tr>
		<td width=1%><img src='img/service-check-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:ReconfigureUfdb();\" 
		style='font-size:12px;text-decoration:underline'>{reconfigure_webfilter_service}</a></td>
		</tr>	
	";
	}
	

$supportpckg="
			<tr>
		<td width=1%><img src='img/technical-support-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.support.package.php');\" 
		style='font-size:12px;text-decoration:underline'>{build_support_package}</a></td>
		</tr>	
	";	

$squid_rotate="
			<tr>
		<td width=1%><img src='img/events-rotate-32.png' id='events-rotate-32-squid'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.perf.logrotate.php?img=events-rotate-32-squid&src=events-rotate-32.png');\" 
		style='font-size:12px;text-decoration:underline'>{squid_logrotate_perform}</a></td>
		</tr>	
	";
	
$debug_compile="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.debug.compile.php');\" 
		style='font-size:12px;text-decoration:underline'>{compile_in_debug}</a></td>
		</tr>	
	";	

$current_sessions="
			<tr>
		<td width=1%><img src='img/32-connect.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.squidclient.clientlist.php');\" 
		style='font-size:12px;text-decoration:underline'>{display_current_sessions}</a></td>
		</tr>	
	";	

$restart_all_services="	<tr>
		<td width=1%><img src='img/service-restart-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.restart.php');\" 
		style='font-size:12px;text-decoration:underline'>{restart_all_services}</a></td>
	</tr>
	";

$restart_service_only="
	<tr>
		<td width=1%><img src='img/service-restart-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.restart.php?onlySquid=yes');\" 
		style='font-size:12px;text-decoration:underline'>{restart_onlysquid}</a></td>
	</tr>	";
	
$users=new usersMenus();
if($users->WEBSTATS_APPLIANCE){
	$squid_rotate=null;
	$debug_compile=null;
	$current_sessions=null;
	$restart_service_only=null;
}
	
	$tables[]="
	</table>
	</div>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width='50%'>
		<table style='width:100%'>
		$SquidBoosterMemText


	$restart_all_services
	$restart_service_only
	$squid_rotate
	$ufdbbutt
	$debug_compile
	$supportpckg
	</table>
	</td>
	<td valign='top' width='50%'>
		<table style='width:100%'>
			$current_sessions
		</table>
	</td>
	</tr>
	</table>";
	
	
	
	
	$refresh=imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-services','$page?squid-services=yes');");
	
	$html="".@implode("\n", $tables)."<div style='text-align:right'>$refresh</div>
		
		
		
		
		<script>
	var x_ReconfigureUfdb= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTab('squid_main_svc');
	}		
		
	function ReconfigureUfdb(){
			var XHR = new XHRConnection();
		    XHR.appendData('ReconfigureUfdb', 'yes');
		    AnimateDiv('squid-services');
		    XHR.sendAndLoad('$page', 'POST',x_ReconfigureUfdb); 
		
	}
	
	LoadAjaxTiny('ptx-status','$page?ptx-status=yes');

</script>	
		";
	
			
	
	
	CACHE_SESSION_SET(__FUNCTION__, __FILE__, $tpl->_parse_body($html));
	
	
	
	}
	
function ptx_status(){
	$tpl=new templates();
	$sock=new sockets();
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}	
	
		$ptxt="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.booster.php');\" 
		style='font-size:12px;text-decoration:underline'>{squid_booster}</a>";
	
	if($SquidBoosterMem>0){
		$pourc=$sock->getFrameWork("squid.php?boosterpourc=yes");
		$ptxt="
		<table>
			<tr>
				<td>$ptxt</td>
				<td>". pourcentage($pourc)."</td>
			</tr>
		</table>";
		
	}	
	
	echo $tpl->_ENGINE_parse_body($ptxt);
	
}

function ReconfigureUfdb(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes&force=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
}


function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}