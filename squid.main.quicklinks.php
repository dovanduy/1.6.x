<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($_GET["byminiadm"]<>null){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class='text-error'>");ini_set('error_append_string',"</p>");}


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
if(isset($_GET["squid-mem-status"])){squid_mem_status();exit;}
if(isset($_GET["squid-stores-status"])){squid_stores_status();exit;}


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
	if(!$users->PROXYTINY_APPLIANCE){
		if($DisableArticaProxyStatistics==0){
			if(!$users->WEBSTATS_APPLIANCE){if($StatsPerfsSquidAnswered==0){$CPU=$users->CPU_NUMBER;$MEM=$users->MEM_TOTAL_INSTALLEE;if(($CPU<4) AND (($MEM<3096088))){WARN_SQUID_STATS();die();}}}
		}
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
			$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "SquidQuickLinksStatistics()"));
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
            <div id='QuickLinksTop' class=mainHeaderContent>
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
	$t=time();
	echo "<div id='squid-section-architecture-$t'></div>
		<script>
		LoadAjax('squid-section-architecture-$t','$page?architecture-tabs=yes');
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
	$users=new usersMenus();
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}
	
	
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){$AsSquidLoadBalancer=0;}	
	
	if($_GET["byminiadm"]<>null){
		$array["infrastructure"]='{infrastructure}';
		
	}
	
	
	$array["architecture-content"]='{main_parameters}';
	if($AsSquidLoadBalancer==1){
		$array["load-balance"]='{load_balancer}';
	}
	$array["caches"]='{caches}';
	$array["architecture-users"]='{users_interactions}';
	$array["architecture-adv"]='{advanced_options}';
	
	if($_GET["byminiadm"]<>null){
		include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
		$mini=new boostrap_form();
		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="infrastructure"){
				$MINA[$ligne]="miniadmin.proxy.infrastructure.php?tabs=yes";
				continue;
			}
			
			if($num=="caches"){
				$MINA[$ligne]="miniadmin.proxy.caches.php";
				continue;
			}
			if($num=="load-balance"){
				$MINA[$ligne]="squid.loadbalancer.main.php?byQuicklinks=yes&byminiadm=yes";
				continue;
			}

			
			$MINA[$ligne]="$page?$num=yes";
		}
		if(!$users->NGINX_INSTALLED){
			$MINA["{proxy_behavior}"]='miniadmin.proxy.php?architecture-behavior=yes';
		}
		echo $mini->build_tab($MINA);
		return;
	}
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.php?byQuicklinks=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="load-balance"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.loadbalancer.main.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
			
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_squid_quicklinks_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$('#main_squid_quicklinks_tabs').tabs();
		</script>";	

}

function section_architecture_advanced(){
	$sock=new sockets();
	$users=new usersMenus();
	$squid=new squidbee();
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	$squid_parent_proxy=Paragraphe('server-redirect-64.png','{squid_parent_proxy}','{squid_parent_proxy_text}',"javascript:Loadjs('squid.parent.proxy.php')");
	$squid_reverse_proxy=Paragraphe('squid-reverse-64.png','{squid_reverse_proxy}','{squid_reverse_proxy_text}',"javascript:Loadjs('squid.reverse.proxy.php')");
	$squid_advanced_parameters=Paragraphe('64-settings.png','{squid_advanced_parameters}','{squid_advanced_parameters_text}',"javascript:Loadjs('squid.advParameters.php')");
	$squid_conf=Paragraphe('script-view-64.png','{configuration_file}','{display_generated_configuration_file}',"javascript:Loadjs('squid.conf.php')");
	$performances_tuning=Paragraphe('performance-tuning-64.png','{tune_squid_performances}','{tune_squid_performances_text}',"javascript:Loadjs('squid.perfs.php')");
	$denywebistes=Paragraphe("folder-64-denywebistes.png","{deny_websites}","{deny_websites_text}","javascript:Loadjs('squid.popups.php?script=url_regex');");

	$AsSquidLoadBalancerIcon=Paragraphe("load-blancing-64.png","{load_balancer}","{squid_load_balancer_text}",
	"javascript:Loadjs('squid.loadblancer.php');");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	
	$squid=new squidbee();
	if($squid->isNGnx()){
		$users->SQUID_REVERSE_APPLIANCE=false;
		$squid_reverse_proxy=null;
		$SquidActHasReverse=0;
		
	}
	
	if($users->SQUID_REVERSE_APPLIANCE){$squid_reverse_proxy=null;$SquidActHasReverse=1;}
	
	
	
	if($SquidActHasReverse==1){
		$AsSquidLoadBalancer=0;
    	$squid_accl_websites=Paragraphe('website-64.png','{squid_accel_websites}','{squid_accel_websites_text}',"javascript:Loadjs('squid.reverse.websites.php')");
    }
    
    $redirectors_options=Paragraphe('redirector-64.png','{squid_redirectors}','{squid_redirectors_text}',
    "javascript:Loadjs('squid.redirectors.php')");  

    
    $memory_option=Paragraphe('bg_memory-64.png','{cache_mem}','{cache_mem_text}',
    "javascript:Loadjs('squid.cache_mem.php')");  
    $dns_servers=Paragraphe('64-bind.png','{dns_servers}','{dns_servers_text}',"javascript:Loadjs('squid.popups.php?script=dns')");
    
    $syslog=Paragraphe("syslog-64.png", "Syslog", "{squid_syslog_text}","javascript:Loadjs('squid.syslog.php')");
    $syslogMAC=Paragraphe("syslog-64.png", "{ComputerMacAddress}", "{squid_ComputerMacAddress_text}","javascript:Loadjs('squid.macaddr.php')");
    
    $sarg=Paragraphe('sarg-logo.png','{APP_SARG}','{APP_SARG_TXT}',"javascript:Loadjs('sarg.php')","{APP_SARG_TXT}");
    
    $disable_stats=Paragraphe('statistics-64.png','{ARTICA_STATISTICS}','{ARTICA_STATISTICS_TEXT}',
    		"javascript:Loadjs('squid.artica.statistics.php')","{ARTICA_STATISTICS_TEXT}");
    $loadbalancing=Paragraphe("64-computer-alias.png", "{proxy_child}", "{squid_balancersHapxy_explain}","javascript:Loadjs('squid.children.php')");
    $anonym=Paragraphe("hearth-blocked-64.png", "{anonymous_browsing}", "{anonymous_browsing_explain}","javascript:Loadjs('squid.anonymous.php')");
    
    $csvstats=Paragraphe("csv-64.png", "{squid_csv_logs}", "{squid_csv_logs_explain}","javascript:Loadjs('squid.csv.php')");
    
     $file_descriptors=Paragraphe("64-filetype.png", "{file_descriptors}", "{file_descriptors_squid_explain}",
    "javascript:Loadjs('squid.file_desc.php')");
     
    $snmp=Paragraphe("64-snmp.png", "SNMP", "{squid_snmp_explain}",
    "javascript:Loadjs('squid.snmp.php')");
    
    
    $forwarded_for=Paragraphe("icon-html-64.png", "x-Forwarded-For", "{x-Forwarded-For_explain}",
    "javascript:Loadjs('squid.forwarded_for.php')");
    
    $timeouts=Paragraphe("clock-gold-64.png", "{timeouts}", "{timeouts_squid_parameters}",
    "javascript:Loadjs('squid.timeouts.php')");

    if($users->PROXYTINY_APPLIANCE){$disable_stats=null;}
    
    if($SquidActHasReverse==1){
    	$denywebistes=null;
    	$squid_parent_proxy=null;
    	$redirectors_options=null;
    	$loadbalancing=null;
    	$AsSquidLoadBalancer=null;
    }
    
    if($AsSquidLoadBalancer==1){
    	$loadbalancing=null;
    	$anonym=null;
    	$redirectors_options=null;
    	$squid_reverse_proxy=null;
    	$squid_parent_proxy=null;
    }
    
    if($users->SQUID_REVERSE_APPLIANCE){
    	$squid_accl_websites=null;
    }
    
    
    $tr[]=$squid_conf;
    $tr[]=$squid_advanced_parameters;
    $tr[]=$memory_option;
    
    $tr[]=$file_descriptors;
    $tr[]=$timeouts;
    $tr[]=$forwarded_for;
    $tr[]=$dns_servers;
    $tr[]=$performances_tuning;
    $tr[]=$AsSquidLoadBalancerIcon;
    $tr[]=$loadbalancing;
    $tr[]=$redirectors_options;
    $tr[]=$denywebistes;
    $tr[]=$anonym;
    $tr[]=$syslog;
    $tr[]=$syslogMAC;
    $tr[]=$snmp;
    $tr[]=$disable_stats;
    $tr[]=$sarg;
    $tr[]=$csvstats;
    $tr[]=$squid_parent_proxy;
    $tr[]=$squid_reverse_proxy;
    $tr[]=$squid_accl_websites;
    
    
    $html=CompileTr3($tr);
    
	$html="<center><div style='width:700px'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
}



function section_architecture_users(){
	$sock=new sockets();
	$squid=new squidbee();
	$authenticate_users=Paragraphe('members-priv-64.png','{authenticate_users}','{authenticate_users_text}',"javascript:Loadjs('squid.popups.php?script=ldap')");	
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	//$blackcomputer=Paragraphe("64-black-computer.png","{black_ip_group}",'{black_ip_group_text}',"javascript:Loadjs('dansguardian.bannediplist.php');");
	//$whitecomputer=Paragraphe("64-white-computer.png","{white_ip_group}",'{white_ip_group_text}',"javascript:Loadjs('dansguardian.exceptioniplist.php');");
    $proxy_pac_rules=Paragraphe('user-script-64.png','{proxy_pac_rules}','{proxy_pac_text}',"javascript:Loadjs('squid.proxy.pac.rules.php')");
    $templates_error=Paragraphe('squid-templates-64.png','{squid_templates_error}','{squid_templates_error_text}',"javascript:Loadjs('squid.templates.php')");
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
 
    $SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
    if($squid->isNGnx()){$SquidActHasReverse=0;}
    if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
    

    
    $SESSIONS_MANAGER=Paragraphe('64-smtp-auth.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"javascript:Loadjs('squid.sessions.php')");
	$SESSIONS_MANAGER=Paragraphe('64-smtp-auth-grey.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"");
	$ISP_MODE=Paragraphe('isp-64.png','{SQUID_ISP_MODE}','{SQUID_ISP_MODE_EXPLAIN}',"javascript:Loadjs('squid.isp.php')");

    $WEB_AUTH=Paragraphe('webfilter-64.png','{HotSpot}','{HotSpot_text}',
    "javascript:Loadjs('squid.webauth.php')");
    
    if($SquidActHasReverse==1){
    	$proxy_pac=Paragraphe('user-script-64-grey.png','{proxy_pac}','{proxy_pac_text}');
    	$proxy_pac_rules=Paragraphe('proxy-pac-rules-64-grey.png','{proxy_pac_rules}','{proxy_pac_text}');
    	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',
    	"javascript:Loadjs('squid.adker.php')");
    	$WEB_AUTH=null;
    	$ISP_MODE=null;
    	
    }    
    
    $tr[]=$SESSIONS_MANAGER;
    $tr[]=$authenticate_users;
	$tr[]=$APP_SQUIDKERAUTH;
	$tr[]=$WEB_AUTH;
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
$squid=new squidbee();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){
		$sock->getFrameWork("squid.php?compil-params=yes");
	}
	
	$COMPILATION_PARAMS=unserialize(base64_decode(file_get_contents($compilefile)));
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if($users->SQUID_REVERSE_APPLIANCE){$SquidActHasReverse=1;}
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	
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
 	
	$googlenossl=Paragraphe('google-64.png','{disable_google_ssl}','{disable_google_ssl_text}',"javascript:Loadjs('squid.google.ssl.php')");
 	
	if($SquidActHasReverse==1){
		$googlenossl=null;
		$messengers=null;
		$sslbump=null;
		$transparent_mode=null;
	}

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
	$tr[]=$googlenossl;
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
	$users=new usersMenus();
	$page=CurrentPageName();	
	include_once(dirname(__FILE__)."/ressources/class.status.inc");
	$sock=new sockets();
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	
	
	$squid=new squidbee();
	$q=new mysql();
	$master_version=$squid->SQUID_VERSION;
	
	$cache_mem=$squid->global_conf_array["cache_mem"];	
	$users=new usersMenus();
	
	$As32=false;
	if(!isset($_GET["uuid"])){$_GET["uuid"]=$sock->getframework("cmd.php?system-unique-id=yes");}
	

	$EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
	$KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
	$KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");	
	if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	
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
	$styleText="font-size:12px;font-weight:bold";
	$migration_pid=unserialize(base64_decode($sock->getFrameWork("squid.php?migration-stats=yes")));
	if(is_array($migration_pid)){
		$text_script="<span style='color:#B80000;font-size:13px'>{migration_script_run_text} PID:{$migration_pid[0]} {since}:{$migration_pid[1]}Mn</span>";
	}	
	
	
	$DisableSquidSNMPModeText="{disabled}";
	$DisableSquidSNMPModeCK="20-check-grey.png";
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $master_version,$re)){
		$MAJOR=$re[1];
		$MINOR=$re[2];
		if($MAJOR>2){if($MINOR>1){$As32=true;}}
		$master_version_text="$MAJOR.$MINOR";
	}	
	
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $master_version,$re)){
		$MAJOR=$re[1];
		$MINOR=$re[2];
		$REV=$re[3];
		$master_version_text="$MAJOR.$MINOR.$REV";
	}
	
	

	
	
	
	if($As32){
		if($CPU_NUMBER>1){
			$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
			if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}

			if($DisableSquidSNMPMode==0){
				$DisableSquidSNMPModeText="{enabled}";
				$DisableSquidSNMPModeCK="20-check.png";
			}
			
			$smptr="		
			<tr>
			<td width=1%><img src='img/$DisableSquidSNMPModeCK'></td>
			<td class=legend nowrap style='font-size:12px'>SMP:</td>
			<td style='font-size:14px'>
			<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.caches32.php?smp-js=yes&uuid={$_GET["uuid"]}');\"
			style='$styleText;text-decoration:underline'>$DisableSquidSNMPModeText</a> <span style='font-size:10px'>($CPU_NUMBER cpu(s))</span></td>
			</tr>";			
			
		}
	}
	
	
	$qs=new mysql();
	$sql="SELECT COUNT(ID) as tcount FROM nics WHERE ucarp_enabled=1";
	$ligne2=mysql_fetch_array($qs->QUERY_SQL($sql,"artica_backup"));
	$failover_icon="20-check-grey.png";
	if($ligne2["tcount"]==0){
		$failover_text="{disabled}";
	}else{
		$failover_text="{enabled}";
		$failover_icon="20-check.png";
	}
	if(!$users->UCARP_INSTALLED){
		$failover_text="-";
		$failover_icon="20-check-grey.png";
	}
	
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	
	$hasProxyTransparentText="{disabled}";
	$hasProxyTransparentCheck="20-check-grey.png";
	
	$DisableAnyCacheText="{enabled}";
	$DisableAnyCacheCheck="20-check.png";
	
	if($hasProxyTransparent==1){
		$hasProxyTransparentText="{enabled}";
		$hasProxyTransparentCheck="20-check.png";
	}
	
	if($DisableAnyCache==1){
		$DisableAnyCacheText="{disabled}";
		$DisableAnyCacheCheck="20-check-grey.png";
	}	
	
	if(preg_match("#^([0-9]+)\s+#", $cache_mem)){
		$cache_mem2=$re[1];
		$cache_mem2=($cache_mem*1024);
		$cache_mem2=FormatBytes($cache_mem2);
	}
	
	
	
	$transparent_mode="
		<tr>
			<td width=1%><img src='img/$hasProxyTransparentCheck'></td>
			<td class=legend nowrap style='font-size:12px'>{transparent}:</td>
			<td style='font-size:14px'>
			<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.newbee.php?squid-transparent-js=yes');\"
			style='$styleText;text-decoration:underline'>$hasProxyTransparentText</a></td>
		</tr>";	
	
	$DisableAnyCache="
		<tr>
			<td width=1%><img src='img/$DisableAnyCacheCheck'></td>
			<td class=legend nowrap style='font-size:12px'>{caches} {disk}:</td>
			<td style='font-size:14px'>
			<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.caches.disable.php');\"
			style='$styleText;text-decoration:underline'>$DisableAnyCacheText</a></td>
		</tr>";	
	
	
	$squidversion="	
	<center>
	<div class=form style='width:93%'>
	<table style='width:250px;margin-top:10px;' class='TableRemove TableMarged'>
	<tbody>
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>Proxy {version}:</td>
			<td style='$styleText'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.compilation.status.php');\" 
			style='$styleText;text-decoration:underline'>$master_version_text</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>{listen_port}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\" 
			style='$styleText;text-decoration:underline'>$squid->listen_port</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>{listen_addr}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.nic.php');\" 
			style='$styleText;text-decoration:underline'>$SquidBinIpaddr</a></td>
		</tr>		
		$smptr
		$transparent_mode
		$DisableAnyCache
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>{cache_memory}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.cache_mem.php');\" 
			style='$styleText;text-decoration:underline'>{$cache_mem2}</a></td>
		</tr>	
		<tr>
			<td width=1%><img src='img/$failover_icon'></td>
			<td class=legend nowrap style='font-size:12px'>{failover2}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.failover.php');\" 
			style='$styleText;text-decoration:underline'>{$failover_text}</a></td>
		</tr>	
		
		
		</tbody>
	</table>
	</div>
	</center>
	";
	
	if($users->WEBSTATS_APPLIANCE){$squidversion=null;}
	
	$design="
	$text_error_sql
	$text_script
	$text_kavicap_error
	$squidversion
	<div id='squid-plugins-activated'></div>
	<div style='width:100%;text-align:right'>". 
	imgtootltip("refresh-24.png","{refresh}",
			"LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');")."
	</div>
	
	";
	
	$classform="class=form";
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	
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
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;
	
	
}


function status_start(){
	$page=CurrentPageName();	
	$html="
	<table style='width:100%' class='TableRemove TableMarged'>
	<tr>
		<td width=1% valign='top' style='vertical-align:top;'><div id='squid-status'></div></td>
		<td width=99% valign='top' style='vertical-align:top;'><div id='squid-services'></div></td>
	</tr>
	</table>
	
	<script>
		LoadAjax('squid-status','$page?status-left=yes');
	</script>
	
	";
	
	echo $html;
	
	
	
}

function section_members_status(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$squid=new squidbee();
	$listen_port=$squid->listen_port;
	$ssl_port=$squid->ssl_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}

	if($users->SQUID_REVERSE_APPLIANCE){
		$listen_port=80;
		$ssl_port=443;
	}
	
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

	if($squid->ICP_PORT>0){
		$icp_port="	<tr>
		<td class=legend nowrap style='font-size:12px'>{icp_port}:</td>
		<td>".texthref($icp_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>";
	}
	
	if($squid->HTCP_PORT>0){
		$htcp_port="	<tr>
		<td class=legend nowrap style='font-size:12px'>{htcp_port}:</td>
		<td>".texthref($icp_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>";
	}	
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	if($ssl_port>0){$listen_port="$listen_port/$ssl_port";}
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap style='font-size:12px'>{version}:</td>
		<td>".texthref($squid->SQUID_VERSION,null)."</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:12px'>{listen_port}:</td>
		<td>".texthref($listen_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>$icp_port$htcp_port
	<tr>
		<td class=legend nowrap style='font-size:12px'>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"Loadjs('squid.popups.php?script=visible_hostname')")."</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:12px'>{transparent_mode}:</td>
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
	
	if($users->SQUID_REVERSE_APPLIANCE){
		$listen_port=80;
		$ssl_port=443;
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	$js1="Loadjs('squid.popups.php?script=listen_port')";
	$js2="Loadjs('squid.popups.php?script=visible_hostname')";
	$js3="Loadjs('squid.newbee.php?squid-transparent-js=yes')";
	
	if($EnableRemoteStatisticsAppliance==1){$js1=null;$js2=null;$js3=null;}
	
	$labelport="{listen_port}";
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}
	if($second_port>0){$second_port="/$second_port";$labelport="{listen_ports}";}else{$second_port=null;}
	
	if($squid->ICP_PORT>0){
		$second_port=$second_port."/icp:$squid->ICP_PORT";
	}
	
	if($squid->HTCP_PORT>0){
		$second_port=$second_port."/htcp:$squid->HTCP_PORT";
	}	
	
	if($ssl_port>0){$second_port="$second_port/$ssl_port&nbsp;(ssl)";}
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	
	$VER=$squid->SQUID_VERSION;
	if(preg_match("#([0-9\.]+)#", $VER,$re)){$VER=$re[1];}
	
	$squid_version_text="<td class=legend nowrap style='font-size:12px'>{version}:</td>
		<td>".texthref($VER,"Loadjs('squid.compilation.status.php');")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	$visible_hostname_text="		<td class=legend nowrap style='font-size:12px;'>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"$js2","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	if($users->WEBSTATS_APPLIANCE){$squid_version_text=null;$visible_hostname_text=null;}
	
	$html="
	<div style='width:95%' class=form>
	<table style='width:100%' class=TableRemove>
	<tr>
		$squid_version_text
		<td class=legend nowrap style='font-size:12px;'>$labelport:</td>
		<td style='font-size:12px;'>".texthref("$listen_port$second_port","$js1","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>
		$visible_hostname_text
		<td class=legend nowrap style='font-size:12px;'>{transparent_mode}:</td>
		<td style='font-size:12px;'>".texthref($hasProxyTransparent,"$js3","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
	</tr>
	</table>
	</div>
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
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}		
	
	
	$array["status"]="{services_status}";
	$array["events-squidcache"]='{proxy_service_events}';
	$array["events-squidaccess"]='{realtime_requests}';
	if($DisableAnyCache==0){
		if($q->TABLE_EXISTS("cacheitems_localhost")){
			$ct=$q->COUNT_ROWS("cacheitems_localhost");
			if($ct>0){
				$array["cached_items"]="$ct {cached_items}";
				
			}
		}
	}
	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($users->WEBSTATS_APPLIANCE){unset($array["events-squidcache"]);}
	
	//$array["graphs"]="{statistics}";
	$array["software-update"]='{softwares_update}';
	
	if($users->PROXYTINY_APPLIANCE){
		unset($array["software-update"]);
	}
	
	
	$fontsize=14;
	
	if($language=="fr"){$fontsize="12.5";}
	
	if(isset($_GET["byminiadm"])){
		unset($array["events-squidaccess"]);
		unset($array["events-squidcache"]);
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="software-update"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.softwares.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.php?table-size=942&url-row=488\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
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
		
	$t=time();
	echo "
	<div id=squid_main_svc style='width:105%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			
			
			$('#squid_main_svc').tabs(); 
			
			function Show$t(){
				QuickLinkShow('quicklinks-services_status');
			}
				
			setTimeout('Show$t()',500);
		</script>";			
	
	
}

function squid_booster_smp(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?smp-booster-status=yes")));
	if(count($array)==0){return;}
	$html[]="
			<div style='min-height:115px'>
			<table>
			<tr><td colspan=2 style='font-size:14px;font-weight:bold'>Cache(s) Booster</td></tr>
			";
	while (list ($proc, $pourc) = each ($array)){
		$html[]="<tr>
				<td width=1% nowrap style='font-size:13px;font-weight:bold'>Proc #$proc</td><td width=1% nowrap>". pourcentage($pourc)."</td></tr>";
	}
	$html[]="</table></div>";
	
	return RoundedLightGreen(@implode("\n", $html));
}

function all_status(){
	$t=time();
	echo "<div id='squid-adker-status'></div>
	<script>
			
		function RefreshAdKer$t(){	
			LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');
		}
		RefreshAdKer$t();
		setTimeout('RefreshAdKer$t()',2000);	
			
		
	</script>
	
	";
	
	//if(CACHE_SESSION_GET(__FUNCTION__, ___FILE__,5)){return;}
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini2=new Bs_IniHandler();
	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();

	
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$ini2->loadString(base64_decode($sock->getFrameWork('cmd.php?cicap-ini-status=yes')));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}	
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$kav=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$cicap=DAEMON_STATUS_ROUND("C-ICAP",$ini2,null,1);
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("APP_PROXY_PAC",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$APP_FRESHCLAM=DAEMON_STATUS_ROUND("APP_FRESHCLAM",$ini,null,1);
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_HAARP",$ini,null,1);
	if($users->PROXYTINY_APPLIANCE){$APP_ARTICADB=null;}
	if($EnableRemoteStatisticsAppliance==1){$APP_ARTICADB=null;}
	$squid=new squidbee();
	
	
	if($EnableKerbAuth==1){
		$APP_SAMBA_WINBIND=DAEMON_STATUS_ROUND("SAMBA_WINBIND",$ini,null,1);
	}	
	$tr[]="<div id='squid-mem-status'></div><script>LoadAjaxTiny('squid-mem-status','$page?squid-mem-status=yes');</script>";
	$tr[]="<div id='squid-stores-status'></div><script>LoadAjaxTiny('squid-stores-status','$page?squid-stores-status=yes');</script>";
	
	

	
	
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
		
		if($AsSquidLoadBalancer==1){$SquidAsSeenDNS=1;}
		if(!$users->IsSquidReverse()){
			$SquidAsSeenDNS=$sock->GET_INFO("SquidAsSeenDNS");
			if(!is_numeric($SquidAsSeenDNS)){$SquidAsSeenDNS=0;}
			if( count($squid->dns_array)==0){
				if($SquidAsSeenDNS==0){
					$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{add_dns_in_config}",
					"{add_dns_in_config_perf_explain}","Loadjs('squid.popups.php?script=dns')");
				}
			}
			
		}
	}
	
	
	$CicapEnabled=0;
	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::DisableSquidSNMPMode -> $DisableSquidSNMPMode<br>\n";}
	
	if($DisableSquidSNMPMode==0){
		$squid_status=null;
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::DisableSquidSNMPMode -> squid.php?smp-status=yes<br>\n";}
		$ini=new Bs_IniHandler();
		$ini->loadString(base64_decode($sock->getFrameWork('squid.php?smp-status=yes')));
		
		while (list ($index, $line) = each ($ini->_params) ){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::$index -> DAEMON_STATUS_ROUND<br>\n";}
			$tr[]=DAEMON_STATUS_ROUND($index,$ini,null,1);
			
		}
		
	}
	

	
	if($SquidBoosterMem>0){
		if($DisableSquidSNMPMode==0){
			if($DisableAnyCache==0){
				$tr[]=squid_booster_smp();
			}
		}
	}
	
	
	$tr[]=$squid_status;
	$tr[]=$APP_HAARP;
	$tr[]=$APP_SAMBA_WINBIND;
	$tr[]=$dansguardian_status;
	$tr[]=$kav;
	$tr[]=$cicap;
	$tr[]=$APP_PROXY_PAC;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$APP_FRESHCLAM;
	$tr[]=$APP_ARTICADB;
	$tr[]=$APP_SQUID_DB;
	
	
	
	

	
	
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}
	
	$tables[]="<div style='min-height:350px;'>
		<table style='width:100%' class='TableRemove TableMarged'><tr>";
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
			<td width=1%><img src='img/memory-32.png'></td>
			<td><div id='ptx-status'></div></td>
		</tr>
	";
	
	
	
	if($EnableKerbAuth==1){	
		$winbind="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('winbindd.events.php');\"
		style='font-size:12px;text-decoration:underline'>{APP_SAMBA_WINBIND}</a></td>
		</tr>
	";

		$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
		
		if($UseDynamicGroupsAcls==1){
			$UseDynamicGroupsAclsTR="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('DynamicGroupsAcls.events.php');\"
		style='font-size:12px;text-decoration:underline'>{dynamicgroupsAcls_events}</a></td>
		</tr>
	";			
		}
		
		
	}
	
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
	
	if($CicapEnabled==1){
		$cicapButt="
			<tr>
		<td width=1%><img src='img/icon-antivirus-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('c-icap.index.php');\" 
		style='font-size:12px;text-decoration:underline'>{antivirus_parameters}</a></td>
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


$reconfigure="
			<tr>
		<td width=1%><img src='img/reconfigure-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.compile.progress.php');\"
		style='font-size:12px;text-decoration:underline'>{reconfigure}</a></td>
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

$squidconf="
			<tr>
		<td width=1%><img src='img/script-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.conf.php');\"
		style='font-size:12px;text-decoration:underline'>{configuration_file}</a></td>
		</tr>
	";




$performances="
			<tr>
		<td width=1%><img src='img/performance-tuning-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.squidclient.info.php');\" 
		style='font-size:12px;text-decoration:underline'>{display_performance_status}</a></td>
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

$reloadservice="
	<tr>
		<td width=1%><img src='img/reload-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.restart.php?onlyreload=yes');\"
		style='font-size:12px;text-decoration:underline'>{reload_service}</a></td>
	</tr>	";

$checkCaches="
	<tr>
		<td width=1%><img src='img/database-connect-32-2.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.restart.php?CheckCaches=yes');\"
		style='font-size:12px;text-decoration:underline'>{check_caches}</a></td>
	</tr>	";
	
$users=new usersMenus();

if($users->WEBSTATS_APPLIANCE){
	$squid_rotate=null;
	$debug_compile=null;
	$current_sessions=null;
	$restart_service_only=null;
	$performances=null;
	$SquidBoosterMemText=null;
	$supportpckg=null;
	$squidconf=null;
}

if($DisableAnyCache==1){
	$SquidBoosterMemText=null;
}

	$refresh=imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-services','$page?squid-services=yes');");
	$tables[]="
	</table>
	<div style='text-align:right;margin-top:-15px'>$refresh</div>
	</div>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width='50%'>
		<table style='width:100%'>
	$squidconf
	$reconfigure
	$restart_all_services
	$restart_service_only
	$SquidBoosterMemText
	$squid_rotate
	$winbind
	$UseDynamicGroupsAclsTR
	</table>
	</td>
	<td valign='top' width='50%'>
		<table style='width:100%'>
			$reloadservice
			$checkCaches
			$ufdbbutt
			$debug_compile
			$supportpckg		
			$cicapButt
			$current_sessions
			$performances
		</table>
	</td>
	</tr>
	</table>";
	
	
	
	
	
	
	$html=@implode("\n", $tables)."
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
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}		
		$ptxt="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.booster.php');\" 
		style='font-size:12px;text-decoration:underline'>{squid_booster}</a>";
	
	if($SquidBoosterMem>0){
		if($DisableAnyCache==0){
			if($DisableSquidSNMPMode==1){
				$pourc=$sock->getFrameWork("squid.php?boosterpourc=yes");
				$ptxt="
				<table>
					<tr>
						<td>$ptxt</td>
						<td>". pourcentage($pourc)."</td>
					</tr>
				</table>";
			}
		}
		
	}	
	
	echo $tpl->_ENGINE_parse_body($ptxt);
	
}

function squid_stores_status(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$reboot=false;	
	$StoreDirs=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-get-storage-info=yes")));
	

	if(!is_array($StoreDirs)){$reboot=true;}
	
	
	if($reboot){
		$functt=time();
		echo "
		<script>
		function Restart$functt(){
		LoadAjax('squid-stores-status','$page?squid-stores-status=yes');
	}
	
	setTimeout('Restart$functt()',2500);
	</script>
	";
	return;
	
	}	
	

	
	
	while (list($directory,$arrayStore)=each($StoreDirs)){
		if($directory=="MEM"){continue;}
		if($directory=="CURCAP"){
			$TTR[]="<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{capacity}:</td>
		<td style='font-weight:bold;font-size:12px'>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>". pourcentage($arrayStore,10)."</td>
				</tr>";			
			
			
			
			continue;}
		
		$directory=basename($directory);
		$TTR[]="<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>$directory:</td>
		<td style='font-weight:bold;font-size:12px'>". FormatBytes($arrayStore["SIZE"])."</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>". pourcentage($arrayStore["PERC"],10)."</td>
				</tr>";
	
	}
	
	if(count($TTR)>0){
		echo $tpl->_ENGINE_parse_body(RoundedLightGreen("<div style='min-height:147px'>
		<table style='width:100%'>".@implode($TTR, "\n")."</table></div>")."<br>");
	}
	
}

function squid_mem_status(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$reboot=false;
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-get-system-info=yes")));
	$StoreDirs=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-get-storage-info=yes")));
	
	if(!is_array($datas)){$reboot=true;}
	
	
	if($reboot){
		$functt=time();
		echo "
			<script>
				function Restart$functt(){
					LoadAjax('squid-mem-status','$page?squid-mem-status=yes');
				}
				
				setTimeout('Restart$functt()',2500);
			</script>
			";
		return;
		
	}
	
	$MEMSEC=$datas["Memory usage for squid via mallinfo()"];
	$Total_space_in_arena=trim($MEMSEC["Total space in arena"]);
	$Total_in_use=trim($MEMSEC["Total in use"]);
	
	$InternalDataStructures=$datas["Internal Data Structures"];
	$StoreEntriesWithMemObjects=$InternalDataStructures["StoreEntries with MemObjects"];
	$HotObjectCacheItems=$InternalDataStructures["Hot Object Cache Items"];
	
	
	
	$ConnectionInformationForSquid=$datas["Connection information for squid"];
	
	$NumberOfHTTPRequestsReceived=$ConnectionInformationForSquid["Number of HTTP requests received"];
	$AverageHTTPRequestsPerMinuteSinceStart=round($ConnectionInformationForSquid["Average HTTP requests per minute since start"]);
	
	
	$StorageMemSize=$datas["Cache information for squid"]["Storage Mem size"];
	$StorageMemCapacity=$datas["Cache information for squid"]["Storage Mem capacity"];
	
	
	preg_match("#^([0-9]+)\s+([A-Z]+)#", trim($StorageMemSize),$re);
	$StorageMemSize=round($re[1]/1024,2);
	
	preg_match("#([0-9\.]+)% used#", trim($StorageMemCapacity),$re);
	$StorageMemCapacityPourc=$re[1];
	
	
	preg_match("#^([0-9]+)\s+([A-Z]+)#", trim($MEMSEC["Total space in arena"]),$re);
	
	
	
	if($re[2]=="KB"){
		$Total_space_in_arena=round(($Total_space_in_arena/1024),2);
	}
	
	if($re[2]=="GB"){
		$Total_space_in_arena=round(($Total_space_in_arena*1024),2);
	}
	
	preg_match("#^([0-9]+)\s+([A-Z]+).*?([0-9\.]+)%#", $Total_in_use,$re);
	$USED_VALUE=$re[1];
	$USED_UNIT=$re[2];
	$USED_PRC=$re[3];
	if($USED_UNIT=="KB"){
		$USED_VALUE=round(($USED_VALUE/1024),2);
	}
	
	if($USED_UNIT=="GB"){
		$USED_VALUE=round(($USED_VALUE*1024),2);
	}	
	
	$NumberOfHTTPRequestsReceived=FormatNumber($NumberOfHTTPRequestsReceived);
	$HotObjectCacheItems=FormatNumber($HotObjectCacheItems);
	$StoreEntriesWithMemObjects=FormatNumber($StoreEntriesWithMemObjects);
	if(isset($StoreDirs["MEM"])){
		$BigMem=$StoreDirs["MEM"]["SIZE"];
		$Items=$StoreDirs["MEM"]["ENTRIES"];
			if($BigMem>0){
				$MemDir="	<tr>
				<td style='font-weight:bold;font-size:12px' align='right' nowrap>{memory_cache}:</td>
				<td style='font-weight:bold;font-size:12px'>". FormatBytes($BigMem)." ($Items {items})</td>		
			</tr>	
			<tr>
			<td>&nbsp;</td>
			<td>". pourcentage($StoreDirs["MEM"]["PERC"])."</td>
			</tr>	";
				
			}
		
	}	
	$html="
	<div style='min-height:147px'>		
	<table style='width:100%'>
$MemDir
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{memory}:</td>
		<td style='font-weight:bold;font-size:12px'>$StorageMemSize&nbsp;MB</td>		
	</tr>
	<tr>
	<td>&nbsp;</td>
	<td>". pourcentage($StorageMemCapacityPourc)."</td>
	</tr>
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{objects}:</td>
		<td style='font-weight:bold;font-size:12px'>$StoreEntriesWithMemObjects</td>		
	</tr>
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{hot_objects}:</td>
		<td style='font-weight:bold;font-size:12px'>$HotObjectCacheItems</td>		
	</tr>	
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{requests}:</td>
		<td style='font-weight:bold;font-size:12px'>$NumberOfHTTPRequestsReceived ({$AverageHTTPRequestsPerMinuteSinceStart} {requests}/{minute})</td>		
	</tr>	
	
			
	
</table></div>
	";
	
	
	echo RoundedLightGreen($tpl->_ENGINE_parse_body($html));
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function ReconfigureUfdb(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes&force=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
}


function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}