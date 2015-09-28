<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.squid.watchdog.inc');
	
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
		
	}
	if(isset($_POST["SAVEGLOBAL"])){SAVE();exit;}
	
ACIVE_DIRECTORY_PAGE();




function ACIVE_DIRECTORY_PAGE(){
	$t=time();
	$watchdog=new squid_watchdog();
	$MonitConfig= $watchdog->MonitConfig;
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$ActiveDirectoryWatchdogINTS[1]="1 {minute}";
	for($i=1;$i<16;$i++){
		$ActiveDirectoryWatchdogINTS[$i]="$i {minutes}";
	}
	


	$array["none"]="{none}";
	$array["restart"]="{restart_services}";
	$array["failover"]="{failover}";
	$array["disable_ad"]="{emergency_mode}";
	$array["reboot"]="{reboot}";

	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{activedirectory_checking}", "{activedirectory_checking_watchdog}",
					"CHECK_AD",$MonitConfig["CHECK_AD"],null,1400)."
							
	<div style='width:100%;margin-top:15px;text-align:right'>
	
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:GotoWatchDogSMTPNotifs();\"
		style='text-decoration:underline;font-size:18px'>					
		{see_how_to_receive_smtp_notifications}</a></div>

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{interval}):</td>
		<td	style='font-size:26px'>". Field_array_Hash($ActiveDirectoryWatchdogINTS, 
				"CHECK_AD_INTERVAL",$MonitConfig["CHECK_AD_INTERVAL"],null,null,0,
				"font-size:26px")."</td>
	</tr>					
					
					
	<tr>
		<td class=legend style='font-size:26px'>{ifconnection_broken} (MAX):</td>
		<td	style='font-size:18px'>". Field_text("CHECK_AD_MAX_ATTEMPTS",$MonitConfig["CHECK_AD_MAX_ATTEMPTS"],
				"font-size:26px;width:110px")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:26px;vertical-align:middle'>{ping_kdc}:</td>
		<td	style='font-size:18px'>". Field_checkbox_design("CHECK_AD_FAILED_PING",1,$MonitConfig["CHECK_AD_FAILED_PING"])."&nbsp;</td>
	</tr>
				
				
				
	<tr>
		<td class=legend style='font-size:26px'>{action}:</td>
		<td	style='font-size:26px'>". Field_array_Hash($array,"CHECK_AD_ACTION",$MonitConfig["CHECK_AD_ACTION"]
				,null,'',0,
				"font-size:26px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",40)."</td>
		</tr>
		</table>
		</div>
		<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	if(document.getElementById('CHECK_AD_FAILED_PING').checked){XHR.appendData('CHECK_AD_FAILED_PING',1);}else{XHR.appendData('CHECK_AD_FAILED_PING',0);}
	XHR.appendData('CHECK_AD',document.getElementById('CHECK_AD').value);
	XHR.appendData('CHECK_AD_MAX_ATTEMPTS',document.getElementById('CHECK_AD_MAX_ATTEMPTS').value);
	XHR.appendData('CHECK_AD_ACTION',document.getElementById('CHECK_AD_ACTION').value);
	XHR.appendData('CHECK_AD_INTERVAL',document.getElementById('CHECK_AD_INTERVAL').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function SAVE(){
	$watchdog=new squid_watchdog();
	$MonitConfig= $watchdog->MonitConfig;
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$MonitConfig[$num]=$ligne;

	}

	$newparam=base64_encode(serialize($MonitConfig));
	
	$sock=new sockets();
	$sock->SaveConfigFile($newparam,"SquidWatchdogMonitConfig");
	$sock->getFrameWork("system.php?artica-status-restart=yes");

}