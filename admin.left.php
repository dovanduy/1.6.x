<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
if(isset($_GET["part1"])){left_infos_1();exit;}
if(isset($_GET["partall"])){left_infos_1();left_infos_2();exit;}
if(isset($_GET["old-menu"])){old_menus_js();exit;}
if(isset($_GET["old-menu-popup"])){old_menu_popup();exit;}
if(isset($_GET["unity-fill"])){FillUnity();exit;}



function left_infos_1(){
	$newfrontend=false;
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$ldap=new clladp();
	if(!$users->AsArticaAdministrator){die();}
	$page=CurrentPageName();	
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	$EnableMonit=$sock->GET_INFO("EnableMonit");
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$EnableMonit=0;}
	if(!is_numeric($EnableMonit)){$EnableMonit=1;}
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	$NoKaspersky=false;
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}	
	if($AsCategoriesAppliance==1){$SambaEnabled=0;}
	$DisablePurchaseInfo=$sock->GET_INFO("DisablePurchaseInfo");
	if(!is_numeric($DisablePurchaseInfo)){$DisablePurchaseInfo=0;}
	if($EnableMonit==1){
		$ini=new Bs_IniHandler();
		$ini->loadString(base64_decode($sock->getFrameWork("monit.php?status=yes")));
		$running=$ini->_params["APP_MONIT"]["running"];
		if(!is_numeric($running)){$running=0;}
		$cmd=urlencode($ini->_params["APP_MONIT"]["service_cmd"]);
		$monit_cmdline="
			<table style='width:54px;float:right;margin-top:-10px;'>
				<tr>
					<td width=18px>". imgtootltip("16-run.png","{start}","Loadjs('system.services.cmd.php?APPNAME=APP_MONIT&action=start&cmd={$ini->_params["APP_MONIT"]["service_cmd"]}&id=&appcode=APP_MONIT')")."</td>
					<td width=18px>". imgtootltip("restart-16.png","{restart}","Loadjs('system.services.cmd.php?APPNAME=APP_MONIT&action=restart&cmd={$ini->_params["APP_MONIT"]["service_cmd"]}&id=&appcode=APP_MONIT')")."</td>
					<td width=18px>". imgtootltip("16-stop.png","{stop}","Loadjs('system.services.cmd.php?APPNAME=APP_MONIT&action=stop&cmd={$ini->_params["APP_MONIT"]["service_cmd"]}&id=&appcode=APP_MONIT')")."</td>
				</tr>
			</table>";
		
		
		if($running<>1){
			echo $tpl->_ENGINE_parse_body(ParagrapheTEXT("warn-red-32.png",'{APP_MONIT}',"{APP_MONIT_NOT_RUNNING_TEXT}<div style=text-align:right>$monit_cmdline</div>",
					"javascript:Loadjs('system.services.cmd.php?APPNAME=APP_MONIT&action=start&cmd=%2Fetc%2Finit.d%2Fmonit&appcode=APP_MONIT');"));
		}else{
			$text="{running} PID: {$ini->_params["APP_MONIT"]["master_pid"]} {since} {$ini->_params["APP_MONIT"]["uptime"]}<br>{APP_MONIT_RUNNING_TEXT}";
			echo $tpl->_ENGINE_parse_body(ParagrapheTEXT("ok32.png",'{APP_MONIT}',$text."<div style=\"text-align:right\">$monit_cmdline</div>","javascript:Loadjs('monit.php');",0,true));
		}
	}
	
	
	if($DisablePurchaseInfo==0){
		echo $tpl->_ENGINE_parse_body(ParagrapheTEXT("technical-support-32.png",'{ARTICA_P_SUPPORT}','{ARTICA_P_SUPPORT_TEXT}',"javascript:Loadjs('artica.subscription.php');"));
	}
	if(is_file("ressources/logs/status.inform.html")){
		echo $tpl->_ENGINE_parse_body(@file_get_contents("ressources/logs/status.inform.html"));
	}

	if($ldap->ldap_password=="secret"){
		echo ParagrapheTEXT("danger32-user-lock.png",'{MANAGER_DEFAULT_PASSWORD}',
		'{MANAGER_DEFAULT_PASSWORD_TEXT}',"javascript:Loadjs('artica.settings.php?js=yes&bigaccount-interface=yes');",null,330);
	}

		if(!function_exists("browser_detection")){include(dirname(__FILE__).'/ressources/class.browser.detection.inc');}
		$browser=browser_detection();
	
	if($browser=="ie"){
		echo ParagrapheTEXT("no-ie-32.png",'{NOIEPLEASE} !!','{NOIEPLEASE_TEXT}',"javascript:s_PopUp('http://www.mozilla-europe.org/en/firefox/','800',800);",null,330);
	}
	

	
	if($users->VMWARE_HOST){
		if(!$users->VMWARE_TOOLS_INSTALLED){
			echo ParagrapheTEXT("vmware-logo-48.png",'{INSTALL_VMWARE_TOOLS}','{INSTALL_VMWARE_TOOLS_TEXT}',
			"javascript:Loadjs('VMWareTools.php');",null,330);
		}
	}
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	
	
	if($users->VIRTUALBOX_HOST){
		if(!$users->APP_VBOXADDINTION_INSTALLED){
			echo ParagrapheTEXT("virtualbox-48.png",'{INSTALL_VBOX_TOOLS}','{INSTALL_VBOX_TOOLS_TEXT}',
			"javascript:Loadjs('setup.index.progress.php?product=APP_VBOXADDITIONS&start-install=yes');",null,330);
		}
	}
	
	if($users->ZARAFA_INSTALLED){
		$q=new mysql();
		$ctc=$q->COUNT_ROWS("zarafa_orphaned","artica_backup");
		if($ctc>0){
			echo ParagrapheTEXT("inbox-error-48.png","$ctc {ORPHANED_STORES}",'{ORPHANED_STORES_TEXT}',
			"javascript:Loadjs('zarafa.orphans.php?js=yes');",null,330);
			}
	}
	
	
	

	
$NoOCS=false;
if($users->WEBSECURIZE){$NoKaspersky=true;$NoOCS=true;}
if($AsCategoriesAppliance==1){$NoKaspersky=true;}
if(!$users->OCS_LNX_AGENT_INSTALLED){$NoOCS=true;}
if(intval($sock->GET_INFO("DisableOCSClientPub"))==1){$NoOCS=true;}
if(!$users->SQUID_INSTALLED){$NoKaspersky=true;}
if(!$users->KAV4PROXY_INSTALLED){$NoKaspersky=true;}

if(!$NoKaspersky){echo ParagrapheTEXT("bigkav-48.png","Kaspersky For Artica",'{kaspersky_pub1}',"javascript:Loadjs('artica.pubs.php?KasperskyPromo-022014-show=yes');",null,330);}
if(!$NoOCS){echo ParagrapheTEXT("48-install-soft.png","{INSTALL_OCS}",'{INSTALL_OCS_TEXT}',"javascript:Loadjs('ocs.install.php');",null,330);	 }
	
	
	$t=time();
	echo "
<script>
	
	function Start$t(){
	 if(document.getElementById('admin-left-infos')){
	 	var leftcontent=document.getElementById('admin-left-infos').innerHTML;
			if(leftcontent.length<70){
				LoadAjax('admin-left-infos','admin.index.status-infos.php');
			}
		}else{
			UnlockPage();
		}
		
	}
	
	 Start$t();
	 UnlockPage();
</script>";
	
	
	
}

function left_infos_2(){
	$users=new usersMenus();
	if(is_file("ressources/logs/web/debian.update.html"));
	$apt=@file_get_contents("ressources/logs/web/debian.update.html");
	$html=@file_get_contents("ressources/logs/status.global.html");
	$sock=new sockets();
	$DisableWarningCalculation=$sock->GET_INFO("DisableWarningCalculation");
	$DisableAPTNews=$sock->GET_INFO("DisableAPTNews");
	$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}
	if(!is_numeric($DisableWarningCalculation)){$DisableWarningCalculation=0;}
	if(!is_numeric($DisableAPTNews)){$DisableAPTNews=0;}
	
	if($users->KASPERSKY_WEB_APPLIANCE){$kavicapserverEnabled=1;}
	
	if($DisableAPTNews==1){$apt=null;}
	
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	echo $tpl->_ENGINE_parse_body("$apt$html")."
	<div id='artica-meta'></div>
	
	<script>
		function CheckArticaMeta(){
			var kavicapserverEnabled=$kavicapserverEnabled;
			LoadAjax('artica-meta','$page?artica-meta=yes');
			
			if(kavicapserverEnabled==1){
				if(document.getElementById('kav4proxyGraphs')){LoadAjax('kav4proxyGraphs','admin.index.kav4proxy.php');}
			}
		}
	
	
		function CheckSquid(){
			if(document.getElementById('page-index-squid-status')){
				LoadAjax('page-index-squid-status','squid.index.php?page-index-squid-status=yes');
			}
		
		}
	
		CheckArticaMeta();
	</script>
	
	";
	$sock=new sockets();
	FillUnity();
	$sock->getFrameWork('cmd.php?ForceRefreshLeft=yes');	
	
}

function FillUnity(){
	
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	$page="artica.settings.php";
	Paragraphe("folder-performances-64.png","{web_interface_settings}","{web_interface_settings_text}","javascript:Loadjs('artica.settings.php?js=yes&func-webinterface=yes');");
	Paragraphe("folder-64-fetchmail.png","{smtp_notifications}","{smtp_notifications_text}","javascript:Loadjs('artica.settings.php?js=yes&func-NotificationsInterface=yes');");
	Paragraphe("proxy-64.png","{http_proxy}","{http_proxy_text}","javascript:Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');");
	Paragraphe("superuser-64.png","{account}","{accounts_text}","javascript:Loadjs('artica.settings.php?js=yes&func-AccountsInterface=yes');");
	Paragraphe("scan-64.png","{logs_cleaning}","{logs_cleaning_text}","javascript:Loadjs('artica.settings.php?js=yes&func-LogsInterface=yes');");
	
	//Paragraphe("database-setup-64.png","{openldap_parameters}","{openldap_parameters_text}","javascript:Loadjs('artica.settings.php?js-ldap-interface=yes');");	
	
}

function NightlyNotifs(){
	$sock=new sockets();
	$ini=new Bs_IniHandler("ressources/index.ini");
	$nightly=$ini->get("NEXT","artica-nightly");
	$version=$sock->getFrameWork("cmd.php?uri=artica_version");
	
	$nightlybin=str_replace('.','',$nightly);
	$versionbin=str_replace('.','',$version);
	if($versionbin==0){return;}
	if($nightlybin==0){return;}
	
	if($nightlybin>$versionbin){
		echo ParagrapheTEXT("32-infos.png","{NEW_NIGHTLYBUILD}: $nightly",'{NEW_NIGHTLYBUILD_TEXT}',
			"javascript:Loadjs('artica.update.php?js=yes');",null,330);
		
	}
}

function old_menus_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{advanced_options}");
	$html="YahooSetupControl(189,'$page?old-menu-popup=yes','$title')";
	echo $html;
	
}

function old_menu_popup(){
	echo"<div id='old-menus-popup'></div>
	<script>LoadAjax('old-menus-popup','admin.tabs.php?left-menus=yes');</script>";
	 
}
