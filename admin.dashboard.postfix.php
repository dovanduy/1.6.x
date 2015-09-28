<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.postfix-multi.inc');

if(isset($_GET["filter2-section"])){filter2_section();exit;}
if(isset($_GET["infra-section"])){infra_section();exit;}
if(isset($_GET["control-section"])){control_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["update-section"])){update_section();exit;}
if(isset($_GET["debug-section"])){debug_section();exit;}

page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$js_infrasection="LoadAjaxRound('infra-section','$page?infra-section=yes');";
	$js_filtersection="LoadAjaxRound('filter2-section','$page?filter2-section=yes');";
	$js_controlsection="LoadAjaxRound('control-section','$page?control-section=yes');";
	$js_monitorsection="LoadAjaxRound('monitor-section','$page?monitor-section=yes');";
	$js_debugsection="LoadAjaxRound('debug-section','$page?debug-section=yes');";
	$js_updatesection="LoadAjaxRound('update-section','$page?update-section=yes');";
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/cache")){@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755,true);}
	
	$sock=new sockets();
	$MyHostname=$sock->GET_INFO("myhostname");
	
	
	$infrasection_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-INFRASECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($infrasection_file)){
		$infrasection_content=@file_get_contents($infrasection_file);
		if(trim($infrasection_content)<>null){
			$js_infrasection=null;
		}
	}
	
	$filter_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-FILTERSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($filter_file)){
		$filter_content=@file_get_contents($filter_file);
		if(trim($filter_content)<>null){
			$js_filtersection=null;
		}
	}	
	
	$control_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-CONTROLSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($control_file)){
		$control_content=@file_get_contents($control_file);
		if(trim($control_content)<>null){
			$js_controlsection=null;
		}
	}	
	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($monitor_file)){
		$monitor_content=@file_get_contents($monitor_file);
		if(trim($monitor_content)<>null){
			$js_monitorsection=null;
		}
	}	
	
	$debug_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($debug_file)){
		$debug_content=@file_get_contents($debug_file);
		if(trim($debug_content)<>null){
			$js_debugsection=null;
		}
	}
	
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($update_file)){
		$update_content=@file_get_contents($update_file);
		if(trim($update_content)<>null){
			$js_updatesection=null;
		}
	}	
	
	$html="
	<input type='hidden' id='thisIsTheSquidDashBoard' value='1'>
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{messaging} &laquo;&nbsp;".
	texttooltip($MyHostname,"{myhostname}","Loadjs('postfix.myhostname.php')")."&nbsp;&raquo;</div>
	<div style='padding-left:30px;padding-right:30px'>			
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/filter-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{connections_filters}</div>
				<div id='filter2-section' style='padding-left:15px'>$filter_content</div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/infrastructure-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{postfixinfra}</div>
				<div id='infra-section' style='padding-left:15px'>$infrasection_content</div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>
			

	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/users-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{control}</div>
				<div id='control-section' style='padding-left:15px'>$control_content</div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{monitor}</div>
				<div id='monitor-section' style='padding-left:15px'>$monitor_content</div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>	
		
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>		
	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/maintenance-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{update}</div>
				<div id='update-section' style='padding-left:15px'>$update_content</div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:96px'><img src='img/technical-support-96.png' style='width:96px'></td>
				<td valign='top' style='width:99%'>
					<div style='font-size:30px;margin-bottom:20px'>{support_and_debug}</div>
					<div id='debug-section' style='padding-left:15px'>$debug_content</div>
				</td>
			</tr>
			</table>
		</td>
	</tr>		
	</table>
	</div>
	<script>
		$js_filtersection
		$js_infrasection
		$js_controlsection
		$js_monitorsection
		$js_debugsection
		$js_updatesection
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function monitor_section(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$OKStats=true;
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		$OKStats=false;
	}
	
	$icon="arrow-right-24.png";
	$icon_stats=$icon;
	$tr[]="<table style='width:100%'>";
	
	
	if(!$OKStats){
		$icon_stats="arrow-right-24-grey.png";
		$WebFiltering_text="&nbsp;({disabled})";
		$color="#898989";
		
	}
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
		".texttooltip("{queue_management}",
		 "position:top:{queue_management}","GotoPostfixQueues()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
		".texttooltip("{watchdog_queue}",
					"position:top:{watchdog_queue_text}","Loadjs('postfix.postqueuep.php',true)")."</td>
	</tr>";
	
	
	
	$tr[]="</table>";
	$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($monitor_file, $html);
	echo $html;
	
				
	
}


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function infra_section(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$icon="arrow-right-24.png";
	$SSLColor="#000000";
	$icon_ssl="arrow-right-24.png";
	$icon_ssl_enc="arrow-right-24.png";
	$ssl_enc_color="#000000";
	$GotoSSLEncrypt="GotoSSLEncrypt()";
	
	

	
	
	$network="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{postfix_network}","position:left:{postfix_network_text}","GoToPostfixNetworks()")."</td>
	</tr>";	
	
	$domains="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{domains}","{domains}","GoToPostfixDomains()")."</td>
	</tr>";	
	
	$transport="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{transport_table}","{transport_table}","GoToPostfixRouting()")."</td>
	</tr>";
	
	
	$mailbox_agent="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{mailbox_agent}","{mailbox_agent_text}","Loadjs('postfix.mailbox_transport.php?hostname=master&ou=master');")."</td>
	</tr>";	

	
	
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{infrastructure}:</td>
	</tr>";
	
	

	$HaProxy=Paragraphe("64-computer-alias.png","{load_balancing_compatibility}","{load_balancing_compatibility_text}",
			"javascript:");
	

	
	
		$tr[]=$network;
		$tr[]=$domains;
		$tr[]=$transport;
		$tr[]=$mailbox_agent;

	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{APP_FETCHMAIL}",
				"position:left:{APP_FETCHMAIL}","GotoFetchMail()")."</td>
	</tr>";
	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{load_balancing_compatibility}",
		"position:left:{load_balancing_compatibility_text}","Loadjs('postfix.haproxy.php?hostname=master&ou=master');")."</td>
	</tr>";
		
		
	$tr[]="<tR><td colspan=2>&nbsp;</td></tr>";
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{parameters}:</td>
	</tr>";
	
	

	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{SMTP_BANNER}",
					"position:left:{SMTP_BANNER_TEXT}","Loadjs('postfix.banner.php?hostname=master&ou=master')")."</td>
	</tr>";	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{MIME_OPTIONS}",
					"position:left:{MIME_OPTIONS_TEXT}","Loadjs('postfix.mime.php?hostname=master&ou=master')")."</td>
	</tr>";	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{performances_settings}",
		"position:left:{performances_settings_text}","Loadjs('postfix.performances.php')")."</td>
	</tr>";	

	
	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{other_settings}",
					"position:left:{other_settings_text}","Loadjs('postfix.other.php')")."</td>
	</tr>";	
	
	$tr[]="</table>";	
	
// ***************************************************************************************************	
	
	$tr2[]="<table style='width:100%'>";
	
	if($users->ZARAFA_INSTALLED){
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{mailboxes}:</td>
	</tr>";
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
	texttooltip("{APP_ZARAFA}","{APP_ZARAFA_TEXT}","GoToZarafaMain()")."</td>
	</tr>";
	
	
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{organizations}","{organizations}","GoToOrganizations()")."</td>
	</tr>";
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{mailboxes}","{APP_ZARAFA_TEXT}","GoToZarafaMailboxes()")."</td>
	</tr>";
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("WebMail","{APP_ZARAFA_TEXT}","GoToZarafaWebMail()")."</td>
	</tr>";	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{smartphones}","{APP_ZARAFA_TEXT}","GoToZarafaZPush()")."</td>
	</tr>";
	
	
	
	
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	
	}
	
	if($users->cyrus_imapd_installed){
		$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{mailboxes}:</td>
	</tr>";
		
		
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
			texttooltip("{APP_CYRUS_IMAP}","{about_cyrus}","GotoCyrusManager()")."</td>
	</tr>";
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("WebMail","{APP_ROUNDCUBE_TEXT}","GoToRoundCube()")."</td>
	</tr>";
	

	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
		
	}
	
	

	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{backup}:</td>
	</tr>";
	
	
	
	$Color="black";
	$icon="Database24.png";
	
	
	
	
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
			texttooltip("{backupemail_behavior}","{backupemail_behavior}","GoToBackupeMail()")."</td>
	</tr>";
	


	
	

	
	

	
	
	
	$tr2[]="</table>";
	
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	
	$filename="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("INFRASECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($filename, $final);
	echo $final;
}

function control_section(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$sock=new sockets();
	$tpl=new templates();
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	$EnableIntelCeleron=$sock->EnableIntelCeleron;
	$squid=new squidbee();
	$users=new usersMenus();
	
	
	$OKQuota=true;
	
	
	$color_quota="black";
	$tr[]="<table style='width:100%'>";
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($EnableUfdbGuard==0){$OKQuota=false;}
	if($SquidPerformance>1){$OKQuota=false;}
	
	
	$icon="arrow-right-24.png";
	$icon_quota=$icon;
	$js_quota="GotoSquidWebfilterQuotas()";
	if(!$OKQuota){
		$js_quota="blur()";
		$icon_quota="arrow-right-24-grey.png";
		$color_quota="#898989";
	}
	
	$tr[]="<table style='width:100%'>";
	
	
	
	$ad_icon="arrow-right-24.png";
	$ad_script="GoToActiveDirectory()";
	$ad_color="black";
	$ad_explain="{dashboard_activedirectory_explain}";
	
	$phpldapadm_icon="arrow-right-24.png";
	$phpldapadm_color="#898989";
	$phpldapadm_title="{APP_PHPLDAPADMIN}";
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	
	
	$remote_ldap_color="#898989";
	$remote_ldap_icon="arrow-right-24-grey.png";
	
	
	if($squid->LDAP_EXTERNAL_AUTH==1){
		$ad_icon="arrow-right-24-grey.png";
		$remote_ldap_icon="arrow-right-24.png";
		$ad_script="blur()";
		$ad_color="#898989";
		$remote_ldap_color="black";
		$ad_explain="{dashboard_activedirectory_disabled_explain}";
	}else{
		if($EnableKerbAuth==0){
			$ad_icon="arrow-right-24.png";
			$ad_color="#898989";
		}
	}
	
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	$sock=new sockets();
	$phpldapadmin_installed=false;
	if(trim($sock->getFrameWork("system.php?phpldapadmin_installed=yes"))=="TRUE"){
		$phpldapadmin_installed=true;
	}
	
	if($phpldapadmin_installed){
			$phpldapadm_icon="arrow-right-24.png";
			$phpldapadm_color="#000000";
			$phpldapadm_title="{APP_PHPLDAPADMIN}";
			$phpldapadm_js="s_PopUpFull('/ldap',1024,768,'PHPLDAPADMIN')";
			
		}else{
			$phpldapadm_icon="info-24.png";
			$phpldapadm_title="{INSTALL_PHPLDAPADMIN}";
			$phpldapadm_js="Loadjs('phpldapadmin.progress.php')";
		}

	//<APP_PHPLDAPADMIN>phpLDAPadmin</APP_PHPLDAPADMIN>
	//<APP_PHPLDAPADMIN_TEXT>Browse the LDAP directory using phpLDAPAdmin front-end</APP_PHPLDAPADMIN_TEXT>

	

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{smtp_authentication}",
				"position:right:{smtp_authentication}","GotoPostfixAuth()")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$ad_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$ad_color'>".
	texttooltip("Active Directory","position:right:$ad_explain",$ad_script)."</td>
	</tr>";

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{postmaster}",
			"position:right:{postmaster_text}","Loadjs('postfix.postmaster.php')")."</td>
	</tr>";
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24-grey.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{postmaster_identity}",
				"position:right:{postmaster_identity_text}","Loadjs('postfix.postmaster-ident.php')")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{unknown_users}",
				"position:right:{postfix_unknown_users_tinytext}","Loadjs('postfix.luser_relay.php')")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$phpldapadm_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$phpldapadm_color'>".texttooltip($phpldapadm_title,
			"position:right:$phpldapadm_explain",$phpldapadm_js)."</td>
	</tr>";	
	
	


	
	
	
	$tr[]="</table>";
	$html= $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	$control_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-CONTROLSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($control_file, $html);
	echo $html;
}

function filter2_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$OKStats=true;
	$icon="arrow-right-24.png";
	$icon_category=$icon;
	$color_category="black";
	$tr[]="<table style='width:100%'>";
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		$OKStats=false;
	}
	
	
	if(!$OKStats){
		$icon_stats="arrow-right-24-grey.png";
		$stats_text=" ({disabled})";
		$color_stats="#898989";
		$icon_stats="arrow-right-24-grey.png";
		$stats_text=" ({disabled})";
		$color_stats="#898989";
	
	}else{
		$color_stats="#000000";
		$icon_stats="arrow-right-24.png";
		$stats_text="";
		
	}
	
	
	if($EnableUfdbGuard==1){
		$color="#000000";
		$icon_ufdb="arrow-right-24.png";
		$WebFiltering_text="{enabled}";
		
	}else{
		$icon_category="arrow-right-24-grey.png";;
		$icon_ufdb="arrow-right-24-grey.png";
		$WebFiltering_text="{disabled}";
		$color="#898989";
		$color_category="#898989";
	}
	
	$explain_av="{web_antivirus_explain}";
	$js_cicap="GoToCICAP()";
	if($CicapEnabled==1){
		$color_av="#000000";
		$icon_av="arrow-right-24.png";
		$av_text=null;
	
	}else{
		$icon_av="arrow-right-24-grey.png";
		$av_text=" ({disabled})";
		$color_av="#898989";
	}	
	
	if($EnableIntelCeleron==1){
		$icon_av="arrow-right-24-grey.png";
		$av_text=" ({disabled})";
		$color_av="#898989";
		$js_cicap=null;
		$explain_av="{ERROR_FEATURE_CELERON}";
	}
	
	
	$explain_category="{your_categories_explain}";
	
	if(!$users->CORP_LICENSE){
		$icon_category="arrow-right-24-grey.png";
		$color_category="#898989";
		$explain_category="{this_feature_is_disabled_corp_license}";
	}
	
	$mgrey_js2="GotoMilterGreyListACLS();";
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{transport}:</td>
	</tr>";
	
	 
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{safety_standards}","position:right:{safety_standards}","GotoSMTPRFC()")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
	texttooltip("{acls}","position:right:{acls}",$mgrey_js2)."</td>
		</tr>";
	
		
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{APP_OPENDKIM}","position:right:{APP_OPENDKIM_TEXT}","GoToOpenDKIM()")."</td>
	</tr>";
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{APP_POSTFWD2}","position:right:{APP_POSTFWD2}","GotoPostfixPostfwd2()")."</td>
	</tr>";

	$tr[]="<tR><td colspan=2>&nbsp;</td></tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{APP_MILTERGREYLIST}:</td>
	</tr>";
	
	
	$mgrey_color="#000000";
	$mgrey_icon="arrow-right-24.png";
	$mgrey_js="GotoMilterGreyListMain()";
	$mgrey_js3="GotoMilterGreyListUpdate();";
	
	$MilterGreyListEnabled=intval($sock->GET_INFO("MilterGreyListEnabled"));
	
	if(!$users->MILTERGREYLIST_INSTALLED){
		$mgrey_color="#898989";
		$mgrey_js=null;
		$mgrey_js2=null;
		$mgrey_js3=null;
		$mgrey_icon="arrow-right-24-grey.png";
	}
	
	if($MilterGreyListEnabled==0){
		$mgrey_color="#898989";
		$mgrey_icon="arrow-right-24-grey.png";
		
	}
	
		$tr[]="
			<tr>
			<td valign='middle' style='width:25px'><img src='img/$mgrey_icon'></td>
			<td valign='middle' style='font-size:18px;width:99%;color:$mgrey_color'>".
					texttooltip("{main_settings}","position:right:{main_settings}",$mgrey_js)."</td>
			</tr>";
	
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$mgrey_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$mgrey_color'>".
		texttooltip("{rules_update}","position:right:{rules_update}",$mgrey_js3)."</td>
		</tr>";		
		
		
	
	
	
	
	
	$tr[]="</table>";
	
	
	$tr2[]="
	<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{content_filters}:</td>
	</tr>";
	if($users->AsPostfixAdministrator){
		$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));
		$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
		$milter_regex_icon="arrow-right-24.png";
		$milter_regex_color="#000000";
		$milter_regex_js="GotoPostfixMilterRegex()";
		
		$milter_spamass_icon="arrow-right-24.png";
		$milter_spamass_color="#000000";
		$milter_spamass_js="GotoMilterSpamass()";
		
		
		if(!is_file("/usr/sbin/milter-regex")){
			$milter_regex_color="#898989";
			$milter_regex_icon="arrow-right-24-grey.png";
			$milter_regex_js="blur()";
		}
		
		if(!is_file("/usr/sbin/spamass-milter")){
			$milter_spamass_color="#898989";
			$milter_spamass_icon="arrow-right-24-grey.png";
			$milter_spamass_js="blur()";
		}
		
		if($EnableMilterRegex==0){
			$milter_regex_color="#898989";
			$milter_regex_icon="arrow-right-24-grey.png";
			
		}
		
		if($SpamAssMilterEnabled==0){
			$milter_spamass_icon="arrow-right-24-grey.png";
			$milter_spamass_color="#898989";
		}
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$milter_regex_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$milter_regex_color'>".texttooltip("{milter_regex}","position:right:{milter_regex_explain}",$milter_regex_js)."</td>
		</tr>";	

		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$milter_spamass_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$milter_spamass_color'>".texttooltip("{APP_SPAMASS_MILTER}","position:right:{APP_SPAMASSASSIN_TEXT}",$milter_spamass_js)."</td>
		</tr>";		
		
		
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{global_smtp_rules}","position:right:{global_smtp_rules}","GotoPostfixBodyChecks()")."</td>
		</tr>";		
		
		
		
		
		
		

		
		$postscreen_color="#000000";
		$postscreen_icon="arrow-right-24.png";
		$instantIptables_icon="arrow-right-24.png";
		$instantIptables_color="#000000";
		$APP_POLICYD_WEIGHT_icon="arrow-right-24.png";
		$APP_POLICYD_WEIGHT_color="#000000";
		
		
		$main=new maincf_multi("master","master");
		$EnablePostScreen=intval($main->GET("EnablePostScreen"));
		$EnablePolicydWeight=intval($sock->GET_INFO("EnablePolicydWeight"));
		$EnablePostfixAutoBlock=$sock->GET_INFO("EnablePostfixAutoBlock");
		if(!is_numeric($EnablePostfixAutoBlock)){$EnablePostfixAutoBlock=1;}
		
		if($EnablePostScreen==0){
			$postscreen_color="#898989";
			$postscreen_icon="arrow-right-24-grey.png";
			
		}
		
		if($EnablePolicydWeight==0){
			$APP_POLICYD_WEIGHT_color="#898989";
			$APP_POLICYD_WEIGHT_icon="arrow-right-24-grey.png";
		}
		if($EnablePostfixAutoBlock==0){
			$instantIptables_icon="arrow-right-24-grey.png";
			$instantIptables_color="#898989";
		}
		
		
		$tr2[]="
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{connections_filters}:</td>
		</tr>";
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{dnsbl_service}",
						"position:right:{DNSBL_EXPLAIN}","GotoPostfixDNSBL()").
						"</td>
		</tr>";
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{RHSBL}",
						"position:right:{RHSBL_EXPLAIN}","GotoPostfixRHSBL()").
						"</td>
		</tr>";		
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$postscreen_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$postscreen_color'>".texttooltip("PostScreen","position:right:{POSTSCREEN_TEXT}","GotoPostScreen()")."</td>
		</tr>";
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$APP_POLICYD_WEIGHT_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$APP_POLICYD_WEIGHT_color'>".texttooltip("{APP_POLICYD_WEIGHT}","position:right:{APP_POLICYD_WEIGHT_ICON_TEXT}","GotoPolicyDaemon()")."</td>
		</tr>";	
		
		
	
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$instantIptables_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$instantIptables_color'>".
		texttooltip("{postfix_autoblock}","position:right:{postfix_autoblock_text}","GotoInstantIpTables()")."</td>
		</tr>";

		 
		
		
		

	}
	
	
	
	
	$tr2[]="</table>";
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	$filename="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-FILTERSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($filename, $final);
	echo $final;
	
}

function update_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$icon="arrow-right-24.png";
	$clamav_icon=$icon;
	$tr[]="<table style='width:100%'>";
	


	
	
	
	
	$clamav_explain="{clamav_antivirus_databases_explain}";
	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	if($CicapEnabled==1){
		$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
		if(count($bases)<2){
			$clamav_explain="{missing_clamav_pattern_databases}";
			$clamav_icon="alert-24.png";
		}
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{clamav_antivirus_databases}",
			"position:top:$clamav_explain","GotoClamavUpdates()")."</td>
	</tr>";	
	
	
	

	
	
	
	$tr[]="</table>";
	$html =$tpl->_ENGINE_parse_body(@implode("\n", $tr));
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($update_file, $html);
	echo $html;
	
}

function debug_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";
	$tr[]="<table style='width:100%'>";
	

	$debug=Paragraphe('syslog-64.png','{POSTFIX_DEBUG}','{POSTFIX_DEBUG_TEXT}',"",90);
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{POSTFIX_DEBUG}","position:top:{POSTFIX_DEBUG_TEXT}","javascript:Loadjs('postfix.debug.php?hostname=master&ou=master');")."</td>
	</tr>";
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{RemoteSMTPSyslog}","position:top:{RemoteSMTPSyslogText}","javascript:Loadjs('syslog.smtp-client.php');")."</td>
	</tr>";

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{move_the_spooldir}","position:top:{move_the_spooldir_text}","javascript:Loadjs('postfix.varspool.php?hostname=master&ou=master');")."</td>
	</tr>";	
	

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{stop_messaging}",
			"position:top:{stop_messaging_text}","javascript:Loadjs('postfix.stop.php');")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{remove_postfix_section}","position:top:{remove_postfix_section_text}","javascript:Loadjs('postfix.remove.php');")."</td>
	</tr>";	
	

	$tr[]="</table>";
		
		
		$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));
		$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
		@file_put_contents($monitor_file, $html);
		echo $html;
	
	
}

