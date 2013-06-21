<?php
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(!$GLOBALS["AS_ROOT"]){session_start();}
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$GLOBALS["AS_ROOT"]){if(!$users->AsAnAdministratorGeneric){die("Not autorized");}}
if(isset($_GET["off"])){off();exit;}
if(isset($_GET["squidcklinks-host-infos"])){squidcklinks_host_infos();exit;}
if(isset($_GET["RefreshMyIp"])){RefreshMyIp();exit;}
if(function_exists($_GET["function"])){call_user_func($_GET["function"]);exit;}

if(!$GLOBALS["AS_ROOT"]){
	if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
}

$sock=new sockets();
$page=CurrentPageName();
$tpl=new templates();

$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
$FreeWebLeftMenu=$sock->GET_INFO("FreeWebLeftMenu");
$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
$DisableFreeWebToolBox=$sock->GET_INFO('DisableFreeWebToolBox');
$DisableTimeCapsuleToolBox=$sock->GET_INFO('DisableTimeCapsuleToolBox');
$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
$SambaEnabled=$sock->GET_INFO("SambaEnabled");
$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
$ejabberdEnabled=$sock->GET_INFO("ejabberdEnabled");
if(!is_numeric($ejabberdEnabled)){$ejabberdEnabled=1;}
if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
if(!is_numeric($EnableFetchmail)){$EnableFetchmail=0;}
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
if(!is_numeric($FreeWebLeftMenu)){$FreeWebLeftMenu=1;}
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
if(!is_numeric($DisableFreeWebToolBox)){$DisableFreeWebToolBox=0;}
if(!is_numeric($DisableTimeCapsuleToolBox)){$DisableTimeCapsuleToolBox=0;}
$OnlyWeb=false;
if($users->PROXYTINY_APPLIANCE){$user->SQUID_APPLIANCE=true;}
if($EnableWebProxyStatsAppliance==1){$users->WEBSTATS_APPLIANCE=true;}
if($SambaEnabled==0){$users->SAMBA_INSTALLED=false;}
if($ejabberdEnabled==0){$users->EJABBERD_INSTALLED=false;}


if($users->PROXYTINY_APPLIANCE){$ASSQUID=true;}
if($EnableWebProxyStatsAppliance==1){$ASSQUID=true;}
if($users->WEBSTATS_APPLIANCE){$ASSQUID=true;}
if($users->KASPERSKY_WEB_APPLIANCE){$ASSQUID=true;}
if($user->SQUID_APPLIANCE){$ASSQUID=true;}
if($users->SQUID_INSTALLED){$ASSQUID=true;}
if($users->SQUID_REVERSE_APPLIANCE){$ASSQUID=true;}


if($SambaEnabled==1){
	$samba=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-samba.png", "APP_SAMBA","fileshare_text", "QuickLinksSamba()"));
}
$squid=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("squid-reverse-48.png", "Proxy","proxyquicktext", "SquidMainQuickLinks()"));
//$network=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("network-connection2-48.png", "network",null, "QuickLinksNetwork()"));
$postfix=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("mass-mailing-postfix-48.png", "APP_POSTFIX",null, "QuickLinkPostfix()"));
$postfwd2=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("Firewall-Secure-48.png", "APP_POSTFWD2",null, "QuickLinkSystems('section_postfwd2')"));
$dnsmasq=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("dns-48.png", "APP_DNSMASQ","APP_DNSMASQ_TEXT", "QuickLinkSystems('section_dnsmasq')"));

$postfix_events=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-mailevents.png", "POSTFIX_EVENTS","POSTFIX_EVENTS_TEXT", "QuickLinkSystems('section_postfix_events')"));

if($users->KLMS_INSTALLED){
	$postfwd2=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("logo_KLMS-48.png", "APP_KLMS",null, "QuickLinkSystems('section_klms')"));
}

$powerdns=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("dns-48.png", "APP_PDNS","APP_PDNS", 
"QuickLinkSystems('section_pdns')"));


if(!$users->dnsmasq_installed){$dnsmasq=null;}
if($users->dnsmasq_installed){$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}if($EnableDNSMASQ<>1){$dnsmasq=null;}}
$cyrus=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-mailbox.png", "mailboxes",null, "QuickLinkCyrus()"));
if($EnableRemoteStatisticsAppliance==0){
	if($DisableArticaProxyStatistics==0){	
		$squidStats=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "QuickLinkSystems('section_squid_stats')"));
	}
}

if($ASSQUID){
	if($users->AsDansGuardianAdministrator){
		$SquidRules=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-filtering-48.png", "WEB_FILTERING","softwares_mangement_text", "QuickLinkSystems('section_webfiltering_dansguardian')"));
	}
	
}






$miltergrey=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-milter-greylist.png", "APP_MILTERGREYLIST","", "QuickLinkSystems('section_mgreylist')"));

$postfix_multiple=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("postfix-multi-48.png", "multiple_instances",null, "QuickLinkPostfixMulti()"));
if($EnablePostfixMultiInstance==0){$postfix_multiple=null;}
if(($users->SQUID_APPLIANCE) OR ($users->KASPERSKY_WEB_APPLIANCE) OR ($users->SQUID_REVERSE_APPLIANCE)){$OnlyWeb=true;}

if($EnableRemoteStatisticsAppliance==0){
	$freewebs=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("website-48.png", "free_web_servers","freewebs_explain", "QuickLinkSystems('section_freeweb')"));
}
if($DisableFreeWebToolBox==1){$freewebs=null;}

if($users->cyrus_imapd_installed){$postfwd2=$cyrus;}
if(!$users->APACHE_INSTALLED){$freewebs=null;}
if(!$users->SAMBA_INSTALLED){$samba=null;}
if(!$users->SQUID_INSTALLED){$squid=null;$squidStats=null;}
if(!$users->POSTFIX_INSTALLED){$postfix=null;$postfix_multiple=null;$postfwd2=null;$miltergrey=null;$postfix_events=null;}
if($users->KASPERSKY_WEB_APPLIANCE){$samba=null;$postfix_multiple=null;$postfwd2=null;$postfix_events=null;}
if(!$users->AsSquidAdministrator){$squid=null;}
if(!$users->AsSystemAdministrator){$network=null;}
if($users->ZARAFA_APPLIANCE){$zarafa=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("zarafa-logo-48.png", "APP_ZARAFA",null, "QuickLinkSystems('section_zarafa')"));}
if(!$users->AsWebStatisticsAdministrator){$squidStats=null;}
if(!$users->MILTERGREYLIST_INSTALLED){$miltergrey=null;}


if($OnlyWeb){$samba=null;}

if(!$users->ZARAFA_APPLIANCE){
	if($users->ZARAFA_INSTALLED){
		$zarafa=quicklinks_paragraphe("zarafa-logo-48.png", "APP_ZARAFA",null, "QuickLinkSystems('section_zarafa')");
		$samba=null;
	}
	
}

if(!$users->POWER_DNS_INSTALLED){$powerdns=null;}

if($users->HAPROXY_INSTALLED){
	$HaProxy=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-blance-servers.png", "load_balancing",null, "QuickLinkSystems('section_haproxy')"));
}

if($users->LOAD_BALANCE_APPLIANCE){
	$postfix=null;
	$squidStats=null;
	$squid=null;
	$crossroads=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-blance-servers.png", "load_balancing",null, "QuickLinkSystems('section_crossroads')"));
	$postfix_multiple=null;
	$postfwd2=null;
	$zarafa=null;
}

if($users->WEBSTATS_APPLIANCE){
	$squidStats=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "QuickLinkSystems('section_squid_stats')"));
	if($users->AsSquidAdministrator){$squidStatsTasks=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-tasks.png", "tasks","", "QuickLinkSystems('section_squid_tasks')"));}
	$postfix=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-settings.png", "APP_SQUID","softwares_mangement_text", "QuickLinkSystems('section_webstats_squids')"));
	$postfix_multiple=null;
	$postfwd2=null;
	$cyrus=null;
	$samba=null;
}

if($FreeWebLeftMenu==0){$freewebs=null;}
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("server-48.png", "manage_your_server","system_information_text", "QuickLinkSystems('section_start')"));
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-computer.png", "system_information","system_information_text", "QuickLinkSystems('section_computers_infos')"));


if(!$users->AsPostfixAdministrator){$postfix=null;$postfix_multiple=null;$postfwd2=null;$postfix_events=null;}
if(!$users->AsMailBoxAdministrator){$zarafa=null;}

$tr[]=$network;
$tr[]=$samba;
$tr[]=$zarafa;
$tr[]=$postfix;
$tr[]=$squid;
$tr[]=$SquidRules;


if($users->POSTFIX_INSTALLED){
	if(($users->ZARAFA_INSTALLED) OR ($users->cyrus_imapd_installed)){
		if($EnableFetchmail==1){
			$fetchmail=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("fetchmail-rule-48.png", "APP_FETCHMAIL","APP_FETCHMAIL",
		 	"QuickLinkSystems('section_fetchmail')"));
		}
	}
}


while (list ($key, $line) = each ($tr) ){if($line==null){continue;}$tr2[]=$line;}

$freeWebAdded=false;
$ejjaberdadded=false;
if(count($tr2)<7){if($postfix_events<>null){$tr2[]=$postfix_events;}}
if(count($tr2)<7){if($squidStats<>null){$tr2[]=$squidStats;}}
if(count($tr2)<7){if($squidStatsTasks<>null){$tr2[]=$squidStatsTasks;}}
if(count($tr2)<7){if($postfix_multiple<>null){$tr2[]=$postfix_multiple;}}
if($users->ZARAFA_INSTALLED){
	if(count($tr2)<7){if($freewebs<>null){$tr2[]=$freewebs;$freeWebAdded=true;}}
	if(count($tr2)<7){
		if($users->EJABBERD_INSTALLED){
			$ejjaberdadded=true;
			$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("jabberd-48.png", "INSTANT_MESSAGING","INSTANT_MESSAGING_EJABBERD",
					"QuickLinkSystems('section_jabberd')"));
		}
	}	
}

if(count($tr2)<7){if($postfwd2<>null){$tr2[]=$postfwd2;}}
if(count($tr2)<7){if($fetchmail<>null){$tr2[]=$fetchmail;}}
if(count($tr2)<7){if($crossroads<>null){$tr2[]=$crossroads;}}
if(count($tr2)<7){if($HaProxy<>null){$tr2[]=$HaProxy;}}
if(count($tr2)<7){if($miltergrey<>null){$tr2[]=$miltergrey;}}



if(!$OnlyWeb){

	if(count($tr2)<7){
		if($SambaEnabled==1){
			if($users->SAMBA_INSTALLED){
				if(!$users->WEBSTATS_APPLIANCE){
					$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("folder-granted-48.png", "shared_folders","system_information_text", "QuickLinkSystems('section_shared_folders')"));
				}
			}
		}
	}
	
	if(count($tr2)<7){
		if(!$ejjaberdadded){
			if($users->EJABBERD_INSTALLED){
				$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("jabberd-48.png", "INSTANT_MESSAGING","INSTANT_MESSAGING_EJABBERD", 
				"QuickLinkSystems('section_jabberd')"));
			}
		}
	}	
	
	if(count($tr2)<7){
		if($users->NETATALK_INSTALLED){
			if($DisableTimeCapsuleToolBox==0){
				$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("TimeMachine-48.png", "time_capsule","system_information_text", "QuickLinkSystems('section_time_capsule')"));
			}
		}
	}
	
	if(count($tr2)<7){
		if($zarafa<>null){
			$tr2[]=$zarafa;
		}
	}
}
$tr=array();
while (list ($key, $line) = each ($tr2) ){if($line==null){continue;}$tr[]=$line;}
$tr2=$tr;





	if(count($tr2)<7){
		if($users->KAV4PROXY_INSTALLED){
			if($users->AsDansGuardianAdministrator){
				$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("bigkav-48.png", "APP_KAV4PROXY","softwares_mangement_text", "QuickLinkSystems('section_kav4proxy')"));
				}
			}
			if($users->SQUID_APPLIANCE){
				if(count($tr2)<7){	
					if($users->AsDansGuardianAdministrator){
						$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-filtering-48.png", "WEB_FILTERING","softwares_mangement_text", "QuickLinkSystems('section_webfiltering_dansguardian')"));
					}
				}
			}
			
			if(($users->SQUID_APPLIANCE) OR ($users->WEBSTATS_APPLIANCE) OR ($users->KASPERSKY_WEB_APPLIANCE)){
					if($users->AsSquidAdministrator){
						$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-logs.png", "PROXY_EVENTS","PROXY_EVENTS", "QuickLinkSystems('section_squid_rtmm')"));
					}
				}
		}
		
		while (list ($key, $line) = each ($tr2) ){$CLEAN[$line]=$line;}	
		$tr2=array();
		while (list ($key, $line) = each ($CLEAN) ){$tr2[]=$line;}	

if($GLOBALS["VERBOSE"]){echo "$page:ITEMS = ".count($tr2)." ". __LINE__."\n";}
if(count($tr2)<7){if($dnsmasq<>null){if($GLOBALS["VERBOSE"]){echo "$page:ADD DNSMASQ ITEM ". __LINE__."\n";}$tr2[]=$dnsmasq;}}
if(count($tr2)<7){if(!$freeWebAdded){$tr2[]=$freewebs;}}
if(count($tr2)<7){if($powerdns<>null){$tr2[]=$powerdns;}}

$count=1;



$CLEAN=array();
while (list ($key, $line) = each ($tr2) ){$CLEAN[$line]=$line;}

while (list ($key, $line) = each ($CLEAN) ){
	if($line==null){continue;}
	$f[]="<li id='kwick1'>$line</li>";
	$count++;
	
}

while (list ($key, $line) = each ($GLOBALS["QUICKLINKS-ITEMS"]) ){
	
	$jsitems[]="\tif(document.getElementById('$line')){document.getElementById('$line').className='QuickLinkTable';}";
}



	
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
			Set_Cookie('QuickLinkCacheIndex', 'quicklinks.fileshare.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.fileshare.php');
		}
		
		function QuickLinksProxy(){
			Set_Cookie('QuickLinkCacheIndex', 'quicklinks.proxy.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.proxy.php');		
		
		}
		
		function QuickLinkPostfix(){
			Loadjs('quicklinks.postfix.php?js=yes');		
		}
		
		function QuickLinkPostfixMulti(){
			Loadjs('quicklinks.postfix.multiple.php?js=yes');
		
		}
		
		function QuickLinksNetwork(){
			LoadAjax('BodyContent','quicklinks.network.php?newinterface=yes');			
		}
		
		function QuickLinkCyrus(){
				LoadAjax('BodyContent','quicklinks.cyrus.php');
		}
		
		function QuickLinkSystems(sfunction){
			if(sfunction=='section_squid_rtmm'){
				s_PopUp('squid.accesslogs.php?external=yes',1024,768);
				return;
			}
		
			Set_Cookie('QuickLinkCacheIndex', '$page?function='+sfunction, '3600', '/', '', '');
			LoadAjax('BodyContent','$page?function='+sfunction);
		}
		
		
		function QuickLinkMemory(){
			QuickLinkSystems('section_start');
			return;
			var memorized=Get_Cookie('QuickLinkCacheIndex');
			if(memorized=='section_postfix'){memorized='section_start';}
			if(!memorized){
				QuickLinkSystems('section_start');
				return;
			}
			
			if(memorized.length>0){
				if(memorized=='quicklinks.network.php'){QuickLinkSystems('section_start');return;}
				LoadAjax('BodyContent',memorized);
			}else{
				QuickLinkSystems('section_computers_infos');

			}
		
		}
		
		function QuickLinkShow(id){
			".@implode("\n", $jsitems)."
			if(document.getElementById(id)){document.getElementById(id).className='QuickLinkOverTable';}
			}		
		
		LoadQuickTaskBar();
		QuickLinkMemory();
	</script>
	";
	
	
if($GLOBALS["AS_ROOT"]){
	@file_put_contents($status_path, $html);
	return;
}	


$tpl=new templates();
$html= $tpl->_ENGINE_parse_body($html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	



function section_start(){
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		if($users->AsWebStatisticsAdministrator){
			echo "<script>SquidQuickLinks();</script>";return;
		}
	}
	
	
	
	
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_services")){
		$left_menus_services=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_services");
	}
	
	
	$html="
	<table style='width:100%;margin:0px;padding:0px;'>
	<tbody>
	<tr>
		<td valign='top' style='margin:-1px;padding:-1px;'><div id='admin-left-infos' style='margin-left:-12px'></div></td>
		<td valign='top'><div id='admin-start_page'></div></td>
	</tr>
	</tbody>
	</center>
	
	<script>
		LoadAjax('admin-start_page','admin.index.php?main_admin_tabs=yes&tab-font-size=14px&tab-width=100%&newfrontend=yes');	
		QuickLinkShow('quicklinks-manage_your_server');
	</script>";
	

	
	echo $html;	
	
	
	
}

function section_backup(){
	$t=time();
	$html="<div id='$t'></div>
	
	<script>
		Loadjs('backup.tasks.php?in-tab=$t');
	</script>
	";
	
	echo $html;
	
	
}


function section_security(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$users=new usersMenus();
		$superuser=Paragraphe("superuser-64.png","{account}","{accounts_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes');");
	
	$RootPasswordChangedTXT=Paragraphe('cop-lock-64.png',
		"{root_password_not_changed}",
		"{root_password_not_changed_text}",
		"javascript:Loadjs('system.root.pwd.php')",
		"{root_password_not_changed_text}");	

	if($users->SAMBA_INSTALLED){
		
	$RootPasswordSamba=Paragraphe('members-priv-64.png',
		"{domain_admin}",
		"{domain_admin_text}",
		"javascript:Loadjs('samba.index.php?script=yes&behavior-admin=yes')",
		"{domain_admin_text}");		
		
	}else{
		$RootPasswordSamba=Paragraphe('members-priv-64-grey.png',
		"{domain_admin}",
		"{domain_admin_text}",
		"",
		"{domain_admin_text}");	
		
	}
	
	$systemusers=Paragraphe('member-64.png',
		"{system_users}",
		"{system_users_informations}",
		"javascript:Loadjs('system.users.php')",
		"{system_users_informations}");	
	
	$firewall=Paragraphe('folder-64-fw.png',
		"{firewall_behavior}",
		"{firewall_behavior_text}",
		"javascript:Loadjs('iptables.config.php')",
		"{firewall_behavior_text}");		
	
	
	$certificate_center=Paragraphe("certificate-download-64.png","{certificates_center}",
	"{ssl_certificates_center_text}","javascript:Loadjs('certificates.center.php')");
		
		
	$tr[]=$superuser;
	$tr[]=$RootPasswordChangedTXT;
	$tr[]=$RootPasswordSamba;
	$tr[]=$systemusers;
	$tr[]=$firewall;
	$tr[]=kaspersky();
	$tr[]=statkaspersky();
	$tr[]=clamav();
	$tr[]=icon_troubleshoot();
	$tr[]=$certificate_center;
	$tr[]=certificate();
	$tr[]=icon_externalports();
	$tr[]=incremental_backup();
$tables=CompileTr2($tr);

$t=time();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'><div id='$t'></div></td>
	<td valign='top'>$tables</td>
</tr>
</table>

<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));
	
	
}

function section_crossroads(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('crossroads.index.php?newinterface=yes');QuickLinkShow('quicklinks-load_balancing');</script>";}
//function section_haproxy(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('haproxy.php');</script>";}

//QuickLinkShow('quicklinks-load_balancing');

function section_freeweb(){	echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('freeweb.php?in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-section_freeweb');</script>";}
function section_postfwd2(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes');QuickLinkShow('quicklinks-APP_POSTFWD2');</script>";}
function section_cyrus(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes');</script>";}
function section_dnsmasq(){echo "<script>Loadjs('dnsmasq.index.php?newinterface=yes');QuickLinkShow('quicklinks-APP_DNSMASQ');;</script>";}
function section_pdns(){echo "<script>LoadAjax('BodyContent','pdns.php?tabs=yes&expand=yes');QuickLinkShow('quicklinks-APP_PDNS');</script>";}

function section_kav4proxy(){echo "<div id='QuicklinksKav4proxy'></div><script>LoadAjax('QuicklinksKav4proxy','kav4proxy.php?inline=yes');QuickLinkShow('quicklinks-APP_CYRUS');</script>";}
function section_webfiltering_dansguardian(){echo "<script>LoadAjax('BodyContent','squid.main.quicklinks.php?function=section_webfiltering_dansguardian');QuickLinkShow('quicklinks-WEB_FILTERING');</script>";}
function section_zarafa(){echo "<script>$('#BodyContent').load('zarafa.index.php?popup=yes&font-size=16&tabwith=920');QuickLinkShow('quicklinks-APP_ZARAFA');</script>";}
function section_mgreylist(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('milter.greylist.index.php?js=yes&in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-APP_MILTERGREYLIST');</script>";}
function section_squid_tasks(){echo "<script>LoadAjax('BodyContent','squid.statistics.tasks.php');QuickLinkShow('quicklinks-tasks');</script>";}
function section_fetchmail(){echo "<script>LoadAjax('BodyContent','fetchmail.index.php?quicklinks=yes');QuickLinkShow('quicklinks-APP_FETCHMAIL');</script>";}
function section_postfix_events(){echo "<script>LoadAjax('BodyContent','postfix.events.new.php?quicklinks=yes');QuickLinkShow('quicklinks-POSTFIX_EVENTS');</script>";}
function section_jabberd(){echo "<script>LoadAjax('BodyContent','ejabberd.php');QuickLinkShow('quicklinks-INSTANT_MESSAGING');</script>";}
function section_klms(){echo "<script>LoadAjax('BodyContent','klms.php');QuickLinkShow('quicklinks-APP_KLMS');</script>";}
function section_haproxy(){echo "<script>javascript:AnimateDiv('BodyContent');Loadjs('haproxy.php');</script>";}
function section_webstats_squids(){echo "<script>javascript:AnimateDiv('BodyContent');LoadAjax('middle','squid.webstats.quicklinks.php');</script>";}
function section_squid_stats(){echo "<script>LoadAjax('BodyContent','squid.statistics.central.php')</script>";}


function section_shared_folders(){
	$t=time();
	echo "
	<div id='$t' class=form></div>
	<script>LoadAjax('$t','samba.index.php?main=shared_folders');</script>";
}
function section_time_capsule(){
	$t=time();
	echo "
	<div id='$t' class=form></div>
	<script>LoadAjax('$t','time.capsule.php');</script>";	
}

function section_softwares(){
	$page=CurrentPageName();
	$tr[]=icon_update_clamav();
	$tr[]=icon_update_spamassassin_blacklist();
	$tr[]=icon_update_artica();
	$tr[]=applis();
	$tr[]=apt();
	
$tables=CompileTr2($tr);
	

$t=time();

$html="
<table style='width:100%'>
<tr>
	<td valign='top'><span id='$t'></span></td>
	<td valign='top'>$tables</td>
</tr>
</table>
<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function section_myhost_config(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$frontend_settings=Paragraphe("64-settings.png",'{index_page_settings}','{index_page_settings_text}',"javascript:Loadjs('artica.performances.php?cron-js=yes');","{internal_hard_drives_text}");
	$artica_settings=Paragraphe('folder-interface-64.png',"{advanced_options}","{advanced_artica_options_text}","javascript:Loadjs('artica.settings.php?js=yes&ByPopup=yes');","{advanced_artica_options_text}");
	$proxy=Paragraphe("proxy-64.png","{http_proxy}","{http_proxy_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');");
	$ldap=Paragraphe("database-setup-64.png","{openldap_parameters}","{openldap_parameters_text}","javascript:Loadjs('artica.settings.php?js-ldap-interface=yes');");
	$web_interface_settings=Paragraphe("folder-performances-64.png","{web_interface_settings}",
	"{web_interface_settings_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-webinterface=yes');");
	$SMTP_NOTIFICATIONS_PAGE=Paragraphe("folder-64-fetchmail.png","{smtp_notifications}","{smtp_notifications_text}",
	"javascript:Loadjs('artica.settings.php?js=yes&func-NotificationsInterface=yes');");
		
	$WATCHDOG=Paragraphe("watchdog-64.png","{system_watchdog}","{system_watchdog_text}",
	"javascript:Loadjs('system.watchdog.php');");	
		
	$perfs=Paragraphe("perfs-64.png","{artica_performances}","{artica_performances_text}","javascript:Loadjs('artica.performances.php');");
		
	$tr[]=$frontend_settings;
	$tr[]=$web_interface_settings;
	$tr[]=$WATCHDOG;
	$tr[]="$perfs";
	$tr[]=$proxy;
	$tr[]=$ldap;
	$tr[]=$SMTP_NOTIFICATIONS_PAGE;
	$tr[]=$artica_settings;
	
	

	

$links=CompileTr2($tr);
$t=time();

$html="
<table style='width:100%'>
<tr>
	<td valign='top'><div id='$t'></div></td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));		
	
}

function section_computers_infos(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	
		$array["section_computers_infos_OS"]='{server}';
		$array["section_security"]='{security}';
		$array["section_backup"]='{backup}';
		$array["section_tasks"]='{tasks}';
		$array["section_myhost_config"]='{general_settings}';
		
		if($q->COUNT_ROWS("repquota", "artica_events")){
			$array["section_quotas"]='{quotas}';
			
		}
		
		
		
		$array["section_computers_infos_events"]='{artica_events}';
		$array["syslog"]='{syslog}';
		$array["openports"]='{opened_ports}';
		
		$fontsize=14;
		if($tpl->language=="fr"){
			if(count($array)>7){
				$fontsize=12;
			}
		}
		
		if($fontsize==14){
			if(count($array)>=8){
				$fontsize=13;
			}
		}
		
		
		
	while (list ($num, $ligne) = each ($array) ){
		if($num=="section_computers_infos_events"){
			$tab[]="<li><a href=\"artica.events.php?popup=yes&full-size=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="section_quotas"){
			$tab[]="<li><a href=\"repquotas.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="section_tasks"){
			$tab[]="<li><a href=\"schedules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}			
		
		if($num=="ocsagent"){
			$tab[]="<li><a href=\"ocs.agent.php?inline=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="syslog"){
			$tab[]="<li><a href=\"syslog.php?popup=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}			
		
		if($num=="openports"){
			$tab[]="<li><a href=\"lsof.ports.php?popup=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}			
		$tab[]="<li><a href=\"$page?function=$num\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_computer_infos_quicklinks' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_computer_infos_quicklinks').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function section_computers_infos_OS(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	
		$array["section_computers_infos_OS_2"]='{manage_your_server}';
		$array["ocsagent"]="{APP_OCSI_LNX_CLIENT}";
		
		
		
		
		
		$fontsize=14;
		
		
	while (list ($num, $ligne) = each ($array) ){
		
		
		if($num=="ocsagent"){
			$tab[]="<li><a href=\"ocs.agent.php?inline=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}	

				
		$tab[]="<li><a href=\"$page?function=$num\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_computer_infos_quicklinks2' style='background-color:white;margin-top:-10px;margin-left:-10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_computer_infos_quicklinks2').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function section_computers_infos_OS_2(){
	$page=CurrentPageName();
	if(CACHE_SESSION_GET( __FUNCTION__,__FILE__,120)){return;}
	$users=new usersMenus();
	$syslog=Paragraphe("syslog-64.png","{system_log}","{system_log_text}","javascript:Loadjs('syslog.engine.php?windows=yes');");
	$dmesg=Paragraphe("syslog-64.png","{kernel_infos}","{kernel_infos_text}","javascript:Loadjs('syslog.dmesg.php?windows=yes');");
	
	$clock=Paragraphe("clock-gold-64.png","{server_time2}","{server_time2_text}","javascript:Loadjs('index.time.php?settings=yes');");
	
	if($users->autofs_installed){
		$automount=Paragraphe("magneto-64.png","{automount_center}","{automount_center_text}","javascript:Loadjs('autofs.php?windows=yes');");
	}
	
	
	$tr[]=sysinfos();
	$tr[]=icon_system();
	$tr[]=icon_memory();
	$tr[]=icon_harddrive();
	$tr[]=$automount;
	$tr[]=$clock;
	$tr[]=icon_terminal();
	$tr[]=$syslog;
	$tr[]=$dmesg;
	$tr[]=scancomputers();
	


$links=CompileTr2($tr);
$t=time();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'><span id='$t'></span></td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
	QuickLinkShow('quicklinks-system_information');
	LoadAjax('$t','$page?function=section_computer_header');
</script>
";

	$tpl=new templates();
	CACHE_SESSION_SET(__FUNCTION__,__FILE__, $tpl->_ENGINE_parse_body($html));
	
}

function section_computer_header(){
if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
$hour=date('h');
$key_cache="CACHEINFOS_STATUSSEVERREDNDER$hour";
$page=CurrentPageName();
//if(isset($_SESSION[$key_cache])){return $_SESSION[$key_cache];}
unset($_SESSION["DISTRI"]);

include_once('ressources/class.os.system.inc');
include_once("ressources/class.os.system.tools.inc");
$sock=new sockets();
$datas=unserialize($sock->getFrameWork("services.php?dmicode=yes"));
$img="img/server-256.png";
$foundChassis=false;
if(is_array($datas)){
	$proc_type=$datas["PROC_TYPE"];
	$MANUFACTURER =$datas["MANUFACTURER"];
	$PRODUCT=$datas["PRODUCT"];
	$CHASSIS=$datas["CHASSIS"];
	$md5Chassis=md5("{$datas["MANUFACTURER"]}{$datas["CHASSIS"]}{$datas["PRODUCT"]}");
	if(is_file("img/vendors/$md5Chassis.jpg")){$img="img/vendors/$md5Chassis.jpg";$foundChassis=true;}
	if(is_file("img/vendors/$md5Chassis.jpeg")){$img="img/vendors/$md5Chassis.jpeg";$foundChassis=true;}
	if(is_file("img/vendors/$md5Chassis.png")){$img="img/vendors/$md5Chassis.png";$foundChassis=true;}
	
}

if(!$foundChassis){
	$chassis_serial="<tr>
					<td valign='top' style='font-size:12px' class=legend>{serial}:</td>
					<td valign='top' style='font-size:12px'><strong>$md5Chassis</td>
				</tr>";
}

if(!isset($_SESSION["DISTRI"])){
	$sys=new systeminfos();
	writelogs('Loading datas system for session',__FUNCTION__,__FILE__);
	$distri=$sys->ditribution_name;
	$kernel=$sys->kernel_version;
	$LIBC=$sys->libc_version;
	$temp=$sys->GetCpuTemp();
	$users=new usersMenus();
	$os=new os_system();
	$arraycpu=$os->cpu_info();
	$cpuspeed=round(($arraycpu["cpuspeed"]/1000*100)/100,2); 
	$host=$users->hostname;
	$publicip=@file_get_contents("ressources/logs/web/myIP.conf");
$distri_logo="img/serv-mail-linux.png";
if(is_file("img/$users->LinuxDistriCode.png")){$distri_logo="img/$users->LinuxDistriCode.png";}
if(is_file("img/$users->LinuxDistriCode.gif")){$distri_logo="img/$users->LinuxDistriCode.gif";}

if(preg_match("#Broken pipevmware#i", $MANUFACTURER)){$MANUFACTURER="VMWare";}
if(preg_match("#Broken pipevmware#i", $PRODUCT)){$PRODUCT="VMWare";}
if(preg_match("#Broken pipevmware#i", $CHASSIS)){$CHASSIS="VMWare";}

if($MANUFACTURER<>null){$tr[]=$MANUFACTURER;}
if($PRODUCT<>null){$tr[]=$PRODUCT;}
if($CHASSIS<>null){$tr[]=$CHASSIS;}
if(count($tr)>0){$LINEMANU=@implode(", ", $tr);}
if($publicip==null){$publicip="x.x.x.x";}


	$distri="
	<center>
	
	
	<table style='width:99%;color:black;' class=form>
		<tr>
			<td colspan=2 align=center><img src='$img'></td>
		</tr>
				<tr>
					<td valign='top' style='font-size:12px' class=legend nowrap>{server}:</td>
					<td valign='top' style='font-size:12px'><strong id='squidcklinks-host-infos'></strong>
						<div><i style='font-weight:bold;font-size:11px'>$LINEMANU</i></div></td>
				</tr>
				<tr>
					<td valign='top' style='font-size:12px' class=legend nowrap>{public_ip}:</td>
					<td valign='top' style='font-size:12px'><a href=\"javascript:RefreshMyIP()\" style='font-size:12px;text-decoration:underline;font-weight:bold' id='RefreshMyIP-span'>$publicip</a></td>
				</tr>				
				<tr>
					<td valign='top' style='font-size:12px' class=legend>{processors}:</td>
					<td valign='top' style='font-size:12px'><strong>{$arraycpu["cpus"]} cpu(s):{$cpuspeed}GHz<br>$proc_type</strong></td>
				</tr>				
				<tr>
					<td valign='top' style='font-size:12px' class=legend>Artica:</td>
					<td valign='top' style='font-size:12px'><strong>$users->ARTICA_VERSION</strong></td>
				</tr>							
					<td valign='top' style='font-size:12px'><img src='$distri_logo'></td>
					<td valign='top' style='font-size:12px'><strong>$distri<br>kernel $kernel
					<br>libc $LIBC<br>Temp $temp&nbsp;C</strong>
					</td>
				</tr>
				$chassis_serial
			</table>
</center>
<script>
	
	LoadAjaxTiny('squidcklinks-host-infos','$page?squidcklinks-host-infos=yes');
	function RefreshMyIP(){
		LoadAjaxTiny('RefreshMyIP-span','$page?RefreshMyIp=yes');
	}
	
</script>
	


";
	$tpl=new templates();
	$distri=$tpl->_ENGINE_parse_body($distri);
	$_SESSION["DISTRI"]=$distri;}else{$distri=$_SESSION["DISTRI"];}
	$html="$distri";
	$_SESSION[$key_cache]=$html;
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;
}

function squidcklinks_host_infos(){
	
	$tpl=new templates();
	$sock=new sockets();
	
	$host=$sock->getFrameWork("cmd.php?full-hostname=yes");
	$host="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('system.nic.config.php?change-hostname-js=yes')\" 
	style='font-size:14px;text-decoration:underline;font-weight:bold'>$host</a>";
	
	echo "$host<script>ChangeHTMLTitlePerform()</script>";
}


function off(){
	
$html="<div id='content' style='background-color:white;padding:0px;margin:0px'>
		<table style='width:100%'>
			<tr>
				<td valign='top' style='padding:0px;margin:0px;width:150px' class=tdleftmenus id='id-tdleftmenus'>
					<div id='TEMPLATE_LEFT_MENUS'></div>
				</td>
				<td valign='top' style='padding-left:3px'>
					<div id='template_users_menus'></div>
					<div id='BodyContentTabs'></div>
						<div id='BodyContent' style='margin-top:8px'>
							<div style='float:right'><a href='#' OnClick=\"javascript:QuickLinks()\"><img src='img/arrowup-32.png' id='img-quicklinks'></a></div> <h1 id='template_title'>{TEMPLATE_TITLE}</h1>

						</div>

				</td>
				<td valign='top'><div id='TEMPLATE_RIGHT_MENUS'></div>
				</td>
			</tr>	
	</table>	

	<div class='clearleft'></div>
	<div class='clearright'></div>
	</div id='content'>
	<script>LoadAjax('BodyContent','admin.index.php?admin-ajax=yes');</script>
	
	";	

echo $html;
	
}

function LocalParagraphe($title,$text,$js,$img){
	
		$js=str_replace("javascript:","",$js);
		$id=md5($js);
		$img_id="{$id}_img";
		Paragraphe($img, $title, $text,$js);
	$html="
	<table style='width:198px;'>
	<tr>
	<td width=1% valign='top'>" . imgtootltip($img,"{{$text}}","$js",null,$img_id)."</td>
	<td><strong style='font-size:12px'>{{$title}}</strong><div style='font-size:11px'>{{$text}}</div></td>
	</tr>
	</table>";
	

return "<div style=\"width:200px;margin:2px\" 
	OnMouseOver=\"javascript:ParagrapheWhiteToYellow('$id',0);this.style.cursor='pointer';\" 
	OnMouseOut=\"javascript:ParagrapheWhiteToYellow('$id',1);this.style.cursor='auto'\" OnClick=\"javascript:$js\">
  <b id='{$id}_1' class=\"RLightWhite\">
  <b id='{$id}_2' class=\"RLightWhite1\"><b></b></b>
  <b id='{$id}_3' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_4' class=\"RLightWhite3\"></b>
  <b id='{$id}_5' class=\"RLightWhite4\"></b>
  <b id='{$id}_6' class=\"RLightWhite5\"></b></b>

  <div id='{$id}_0' class=\"RLightWhitefg\" style='padding:2px;'>
   $html
  </div>

  <b id='{$id}_7' class=\"RLightWhite\">
  <b id='{$id}_8' class=\"RLightWhite5\"></b>
  <b id='{$id}_9' class=\"RLightWhite4\"></b>
  <b id='{$id}_10' class=\"RLightWhite3\"></b>
  <b id='{$id}_11' class=\"RLightWhite2\"><b></b></b>
  <b id='{$id}_12' class=\"RLightWhite1\"><b></b></b></b>
</div>
";		
		
	
}


function main_kaspersky_action(){
	include_once('ressources/class.kas-filter.inc');
	$kas=new kas_single();
	$html="
	<table style='width:100%'>
	<tr>
		<td align='right'><strong>{ACTION_SPAM_MODE}:</strong></td>
		<td>" . Field_array_Hash($kas->ACTION_SPAM_MODE_FIELD,'ACTION_SPAM_MODE',$kas->ACTION_SPAM_MODE)."</td>
	</tr>
	<tr>
		<td align='right'><strong>{ACTION_SPAM_SUBJECT}:</strong></td>
		<td>" . Field_text('ACTION_SPAM_SUBJECT_PREFIX',$kas->ACTION_SPAM_SUBJECT_PREFIX,'width:100%')."</td>
	<td>
	<tr><td colspan=2><hr></td></tr>
	
	
	
	<tr>
		<td align='right'><strong>{ACTION_PROBABLE_MODE}:</td>
		<td>" . Field_array_Hash($kas->ACTION_SPAM_MODE_FIELD,'ACTION_PROBABLE_MODE',$kas->ACTION_PROBABLE_MODE)."</td>
	</tr>	
	<tr>
		<td align='right'><strong>{ACTION_PROBABLE_MODE_SUBJECT}:</td>
		<td>" . Field_text('ACTION_PROBABLE_SUBJECT_PREFIX',$kas->ACTION_PROBABLE_SUBJECT_PREFIX,'width:100%')."</td>
	</tr>		
	<tr><td colspan=2 align='right'><input type='button' OnClick=\"javascript:kavStep4();\" value='{build}&nbsp;&raquo;'></td></tr>
	
	
	</table>";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'kas.group.rules.php');	

	
}


function main_kaspersky(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$users->LoadModulesEnabled();
	$page=CurrentPageName();

	$html="
	<p style='font-size:12px;font-weight:bold'>{welcome_kaspersky}</p>
	<table style='width:100%'>
	";
	
	if($users->kas_installed){
		$html=$html . "<tr>
	<td width=1%>
		" . Field_numeric_checkbox_img('enable_kasper',$users->KasxFilterEnabled,'{enable_disable}').
	"</td>
	<td>{enable_kaspersky_antispam}</td>
	</tr>";
	
	
	}
	
	if($users->KAV_MILTER_INSTALLED){
		$html=$html . "<tr>
	<td width=1%>
		" . Field_numeric_checkbox_img('enable_kav',$users->KAVMILTER_ENABLED,'{enable_disable}').
	"</td>
	<td>{enable_kaspersky_antivirus}</td>
	</tr>";
	
	
	}	
	
	$html=$html . "
	<tr><td colspan=2 align='right'><input type='button' OnClick=\"javascript:kavStep2();\" value='{next}&nbsp;&raquo;'></td></tr>
	
	</table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function main_kaspersky_level(){
	
		include_once('ressources/class.kas-filter.inc');
		$kas=new kas_single();
		$OPT_SPAM_RATE_LIMIT_TABLE=array(4=>"{maximum}",3=>"{high}",2=>"{normal}",1=>"{minimum}");
		$OPT_SPAM_RATE_LIMIT=Field_array_Hash($OPT_SPAM_RATE_LIMIT_TABLE,'OPT_SPAM_RATE_LIMIT',$kas->main_array["OPT_SPAM_RATE_LIMIT"]);
		
		$html="
		<table style='width:100%'>
		<tr>
			<td align='right' nowrap valign='top'><strong>{OPT_SPAM_RATE_LIMIT}:</strong></td>
			<td valign='top'>$OPT_SPAM_RATE_LIMIT</td>
			<td valign='top'>{OPT_SPAM_RATE_LIMIT_TEXT}</td>
		<tr><td colspan=3 align='right'><input type='button' OnClick=\"javascript:kavStep3();\" value='{next}&nbsp;&raquo;'></td></tr>
		</tr>
		</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'kas.group.rules.php');		
	
	
}

function kaspersky(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!$users->POSTFIX_INSTALLED){return false;}
	$users->LoadModulesEnabled();
	$page=CurrentPageName();
	if($users->kas_installed OR $users->KAV_MILTER_INSTALLED){
		$img="bigkav-64.png";
		$js="Loadjs('configure.server.php?script=enable_kasper')";
		return Paragraphe($img,"{enable_kaspersky}","{enable_kaspersky_text}","javascript:$js");
		return LocalParagraphe("enable_kaspersky","enable_kaspersky_text","Loadjs('configure.server.php?script=enable_kasper')","bigkav24.png");
	}
	
}
function icon_update_clamav(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->CLAMAV_INSTALLED){return null;}
	if(!$users->KASPERSKY_WEB_APPLIANCE){return null;}
	if(!$users->KASPERSKY_SMTP_APPLIANCE){return null;}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('clamav.update.php');";
	$img="clamav-update-48.png";
	return Paragraphe($img,"{UPDATE_CLAMAV}","{UPDATE_CLAMAV_EXPLAIN}","javascript:$js");
	return LocalParagraphe("UPDATE_CLAMAV","UPDATE_CLAMAV_EXPLAIN",$js,$img);				
}	
function icon_troubleshoot(){
	$GLOBALS["ICON_FAMILY"]="REPAIR";
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){return null;}
	$js="Loadjs('index.troubleshoot.php');";
	$img="64-troubleshoot-index.png";
	return Paragraphe($img,"{troubleshoot}","{troubleshoot_explain}","javascript:$js");
	return LocalParagraphe("troubleshoot","troubleshoot_explain",$js,$img);				
		
}
function icon_externalports(){
	$GLOBALS["ICON_FAMILY"]="SECURITY";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsArticaAdministrator){return null;}
	if($users->KASPERSKY_WEB_APPLIANCE){return null;}
	
	$js="Loadjs('external-ports.php')";
	$img="64-bind.png";
	return Paragraphe($img,"{EXTERNAL_PORTS}","{EXTERNAL_PORTS_TEXT}","javascript:$js");
	return LocalParagraphe("EXTERNAL_PORTS","EXTERNAL_PORTS_TEXT",$js,$img);	
	}	
function postmaster(){
	$GLOBALS["ICON_FAMILY"]="SMTP";
$sock=new sockets();
if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return null;}	
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$users->POSTFIX_INSTALLED){return false;}	
return LocalParagraphe("postmaster","postmaster_text","Loadjs('postfix.postmaster.php')","folder-useraliases2-48.png");	
}

function Firstwizard(){
	return LocalParagraphe("first_settings","first_settings","Loadjs('configure.server.php?script=wizard')","folder-update-48.png");	
}

function wizard_kaspersky_appliance_smtp(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->KASPERSKY_SMTP_APPLIANCE){return null;}
	return LocalParagraphe("wizard_kaspersky_smtp_appliance","wizard_kaspersky_smtp_appliance_text_wizard","Loadjs('wizard.kaspersky.appliance.php')","kaspersky-wizard-48.png");
}
function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}

function clamav(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	$page=CurrentPageName();
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if($users->KASPERSKY_WEB_APPLIANCE){return null;}
	if($users->KASPERSKY_SMTP_APPLIANCE){return null;}
	$img="clamav-64.png";
	$js="Loadjs('clamav.index.php');";
	return Paragraphe($img,"{clamav_av}","{clamav_av_text}","javascript:$js");
	return LocalParagraphe("clamav_av","clamav_av_text","Loadjs('clamav.index.php');","clamav-48.png");
	}



function nic_settings(){
	$GLOBALS["ICON_FAMILY"]="NETWORK";
	$page=CurrentPageName();
	$js="Loadjs('system.nic.config.php?js=yes')";
	$img="64-win-nic.png";
	return Paragraphe($img,"{nic_settings}","{nic_settings_text}","javascript:$js");
	return LocalParagraphe("nic_settings","nic_settings_text",$js,$img);
	}
	
function wizard_backup(){
	$GLOBALS["ICON_FAMILY"]="BACKUP";
$page=CurrentPageName();
	$js="Loadjs('wizard.backup-all.php')";
	$img="48-dar-index.png";
	return LocalParagraphe("manage_backups","manage_backups_text",$js,"48-dar-index.png");
	

	
}

function scancomputers(){
	$GLOBALS["ICON_FAMILY"]="NETWORK";
	$js="Loadjs('computer-browse.php')";
	$img="64-win-nic-browse.png";
	return Paragraphe($img,"{browse_computers}","{browse_computers_text}","javascript:$js");
	return LocalParagraphe("browse_computers","browse_computers_text",$js,$img);
	}
	


		
	
function postfix_events(){
	$GLOBALS["ICON_FAMILY"]="SMTP";
	$js="Loadjs('postfix-realtime-events.php')";
	$img="folder-logs-643.png";
	return Paragraphe($img,"{postfix_realtime_events}","{postfix_realtime_events_text}","javascript:$js");
	return LocalParagraphe("postfix_realtime_events","postfix_realtime_events_text",$js,$img);
	}

function dmidecode(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	$js="Loadjs('dmidecode.php')";
	$img="system-64.org.png";
	return Paragraphe($img,"{dmidecode}","{dmidecode_text}","javascript:$js");
	return LocalParagraphe("dmidecode","dmidecode_text",$js,$img);
	}		
function icon_update_artica(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsArticaAdministrator){return null;}
	$GLOBALS["ICON_FAMILY"]="UPDATE";
	$js="Loadjs('artica.update.php?js=yes')";
	$img="folder-64-artica-update.png";
	$tpl=new templates();
	return Paragraphe($img,"{artica_autoupdate}","{artica_autoupdate_text}","javascript:$js");
	return $tpl->_ENGINE_parse_body(LocalParagraphe("artica_autoupdate","artica_autoupdate_text",$js,$img),'system.index.php');	
	}
	
function icon_update_spamassassin_blacklist(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->spamassassin_installed){return null;}
	if(!$users->AsPostfixAdministrator){return null;}
	$js="Loadjs('sa-blacklist.php')";
	$img="64-spam.png";
	$tpl=new templates();
	return Paragraphe($img,"{APP_SA_BLACKLIST}","{APP_SA_BLACKLIST_AUTOUPDATE}","javascript:$js");
	return $tpl->_ENGINE_parse_body(LocalParagraphe("APP_SA_BLACKLIST","APP_SA_BLACKLIST_AUTOUPDATE",$js,$img),'system.index.php');	
	}	
	
function statkaspersky(){
	$GLOBALS["ICON_FAMILY"]="ANTIVIRUS";
	$js="YahooWin(580,'kaspersky.index.php','Kaspersky');";
	$img="bigkav-64.png";		
	return Paragraphe($img,"{Kaspersky}","{kaspersky_av_text}","javascript:$js");
	return LocalParagraphe("Kaspersky","kaspersky_av_text","YahooWin(580,'kaspersky.index.php','Kaspersky');","bigkav-48.png");
}
function sysinfos(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	return Paragraphe("scan-64.png", "{sysinfos}", "{sysinfos_text}","javascript:s_PopUp('phpsysinfo/index.php',1000,600,1);");
}
function certificate(){
	$GLOBALS["ICON_FAMILY"]="SECURITY";
	$js="Loadjs('postfix.tls.php?js-certificate=yes')";
	$img="certificate-download-64.png";	
	return Paragraphe($img,"{ssl_certificate}","{ssl_certificate_text}","javascript:$js");
	return LocalParagraphe("ssl_certificate","ssl_certificate_text","Loadjs('postfix.tls.php?js-certificate=yes')","folder-lock-48.png");

}
function apt(){
	$GLOBALS["ICON_FAMILY"]="UPDATE";
	$js="Loadjs('artica.repositories.php')";
	$img="DEBIAN_mirror-64.png";	
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsDebianSystem){return null;}
	return Paragraphe($img,"{repository_manager}","{repository_manager_text}","javascript:$js");
	return LocalParagraphe("repository_manager","repository_manager_text","Loadjs('artica.repositories.php')","folder-lock-48.png");
}
function incremental_backup(){
	$GLOBALS["ICON_FAMILY"]="BACKUP";
	$js="Loadjs('wizard.backup-all.php')";
	$img="64-dar-index.png";
	return Paragraphe($img,"{manage_backups}","{manage_backups}","javascript:$js");
	return LocalParagraphe("manage_backups","manage_backups",$js,"48-dar-index.png");
}
function atica_perf(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	$img="perfs-64.png";	
	$js="Loadjs('artica.performances.php')";
	
	return Paragraphe($img,"{artica_performances}","{artica_performances_text}","javascript:$js");
	
}
function applis(){
	$GLOBALS["ICON_FAMILY"]="SOFTWARES";
	$js="Loadjs('setup.index.php?js=yes')";
	$img="bg-applis-64.png";
	return Paragraphe($img,"{install_applis}","{install_applis_text}","javascript:$js");
		
		}
function icon_system(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('admin.index.services.status.php?js=yes')";
	$img="rouage-64.png";
	return Paragraphe($img,"{manage_services}","{manage_services_text}","javascript:$js");
}

function icon_terminal(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('system.terminal.php')";
	$img="terminal-64.png";
	return Paragraphe($img,"{commandline}","{commandline_text}","javascript:$js");	
	
}
	


function icon_memory(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('system.memory.php?js=yes')";
	$img="bg_memory-64.png";
	return Paragraphe($img,"{system_memory}","{system_memory_text}","javascript:$js");
}
function icon_harddrive(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="Loadjs('system.internal.disks.php')";
	$img="64-hd.png";
	return Paragraphe($img,"{internal_hard_drives}","{internal_hard_drives_text}","javascript:$js");
		
	}	
function RefreshMyIp(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?refresh-my-ip=yes");
	$publicip=@file_get_contents("ressources/logs/web/myIP.conf");
	echo $publicip;
}
	
function icon_adduser(){
	$GLOBALS["ICON_FAMILY"]="USER";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	$sock=new sockets();
	if(!$users->AllowAddUsers){return null;}
	if($users->ARTICA_META_ENABLED){
		if($sock->GET_INFO("AllowArticaMetaAddUsers")<>1){return null;}
	}
	$js="Loadjs('create-user.php');";
	$img="identity-add-64.png";
	return Paragraphe($img,"{add_user}","{add user explain}","javascript:$js");
			
}		