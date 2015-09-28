<?php
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

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["watchdog"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events_table();exit;}
if(isset($_GET["rows-table"])){rows_table();exit;}
if(isset($_GET["notifs"])){smtp_notifs();exit;}
if(isset($_POST["ENABLED_SQUID_WATCHDOG"])){save_watchdog_notif();exit;}
js();


function Save(){
	$sock=new sockets();
	$final=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($final, "SquidWatchdogMonitConfig");
	$sock->SET_INFO("SquidCacheReloadTTL", $_POST["SquidCacheReloadTTL"]);
	$sock->getFrameWork("squid.php?watchdog-config=yes");
	
}

function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_watchdog}");
	$html="YahooWin3('850','$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["popup"]="{parameters}";
	$array["notifs"]="{smtp_notifications}";
	



$fontsize=16;

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"miniadmin.proxy.monitor.php?watchdog-params=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.watchdog-events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		

		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "watchdogsquid")."<script>LeftDesign('artica-watchdog-256-opac20.png');</script>";
	


}
function smtp_notifs(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$t=time();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	
	$UfdbguardSMTPNotifs=$sock->FillSMTPNotifsDefaults($UfdbguardSMTPNotifs);
	
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_warn"])){$UfdbguardSMTPNotifs["smtp_warn"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_info"])){$UfdbguardSMTPNotifs["smtp_warn"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_critic"])){$UfdbguardSMTPNotifs["smtp_critic"]=1;}
	//Switchdiv
	
	$html="
	<div id='notif1-$t' class=form style='width:98%'>
	
	<table style='width:99%' >
	<tr>
	<td nowrap class=legend style='font-size:14px'>{smtp_enabled}:</strong></td>
	<td>" . Field_checkbox("ENABLED_SQUID_WATCHDOG",1,$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"],"SMTPNotifArticaEnableSwitch$t()")."</td>
	</tr>
			
	<tr>
	<td nowrap class=legend style='font-size:14px'>{info}:</strong></td>
	<td>" . Field_checkbox("smtp_info",1,$UfdbguardSMTPNotifs["smtp_info"])."</td>
	</tr>
	<tr>
	<td nowrap class=legend style='font-size:14px'>{warning}:</strong></td>
	<td>" . Field_checkbox("smtp_warn",1,$UfdbguardSMTPNotifs["smtp_warn"])."</td>
	</tr>
	<tr>
	<td nowrap class=legend style='font-size:14px'>{critical}:</strong></td>
	<td>" . Field_checkbox("smtp_critic",1,$UfdbguardSMTPNotifs["smtp_critic"])."</td>
	</tr>									
			
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text('smtp_server_name',trim($UfdbguardSMTPNotifs["smtp_server_name"]),'font-size:14px;padding:3px;width:250px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text('smtp_server_port',trim($UfdbguardSMTPNotifs["smtp_server_port"]),'font-size:14px;padding:3px;width:40px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_sender}:</strong></td>
		<td>" . Field_text('smtp_sender',trim($UfdbguardSMTPNotifs["smtp_sender"]),'font-size:14px;padding:3px;width:290px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_dest}:</strong></td>
		<td>" . Field_text('smtp_dest',trim($UfdbguardSMTPNotifs["smtp_dest"]),'font-size:14px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text('smtp_auth_user',trim($UfdbguardSMTPNotifs["smtp_auth_user"]),'font-size:14px;padding:3px;width:200px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($UfdbguardSMTPNotifs["smtp_auth_passwd"]),'font-size:14px;padding:3px;width:200px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox("tls_enabled",1,$UfdbguardSMTPNotifs["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox("ssl_enabled",1,$UfdbguardSMTPNotifs["ssl_enabled"])."</td>
	</tr>
	<tr>
		<td align='right' colspan=2>".button('{apply}',"SaveArticaSMTPNotifValues$t();",16)."</td>
	</tr>
</table>
</div>
<script>
var x_SaveArticaSMTPNotifValues$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.proxy.watchdog.smtp.progress.php');
	RefreshTab('watchdogsquid');
}
	
	function SaveArticaSMTPNotifValues$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
	if(document.getElementById('ENABLED_SQUID_WATCHDOG').checked){XHR.appendData('ENABLED_SQUID_WATCHDOG',1);}else {XHR.appendData('ENABLED_SQUID_WATCHDOG',0);}
	if(document.getElementById('tls_enabled').checked){XHR.appendData('tls_enabled',1);}else {XHR.appendData('tls_enabled',0);}
	if(document.getElementById('ssl_enabled').checked){XHR.appendData('ssl_enabled',1);}else {XHR.appendData('ssl_enabled',0);}
	
	if(document.getElementById('smtp_warn').checked){XHR.appendData('smtp_warn',1);}else {XHR.appendData('smtp_warn',0);}
	if(document.getElementById('smtp_info').checked){XHR.appendData('smtp_info',1);}else {XHR.appendData('smtp_info',0);}
	if(document.getElementById('smtp_critic').checked){XHR.appendData('smtp_critic',1);}else {XHR.appendData('smtp_critic',0);}
	
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
	XHR.appendData('smtp_dest',document.getElementById('smtp_dest').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
	XHR.appendData('smtp_auth_passwd',pp);
	XHR.appendData('smtp_notifications','yes');
	
	XHR.sendAndLoad('$page', 'POST',x_SaveArticaSMTPNotifValues$t);
	}
	
	function SMTPNotifArticaEnableSwitch$t(){
	document.getElementById('smtp_auth_passwd-$t').disabled=true;
	document.getElementById('smtp_auth_user').disabled=true;
	document.getElementById('smtp_dest').disabled=true;
	document.getElementById('smtp_sender').disabled=true;
	document.getElementById('smtp_server_port').disabled=true;
	document.getElementById('smtp_server_name').disabled=true;
	document.getElementById('tls_enabled').disabled=true;
	document.getElementById('ssl_enabled').disabled=true;
	
	document.getElementById('smtp_critic').disabled=true;
	document.getElementById('smtp_info').disabled=true;
	document.getElementById('smtp_warn').disabled=true;
	
	
	
	if(!document.getElementById('ENABLED_SQUID_WATCHDOG').checked){return;}
	
	document.getElementById('smtp_auth_passwd-$t').disabled=false;
	document.getElementById('smtp_auth_user').disabled=false;
	document.getElementById('smtp_dest').disabled=false;
	document.getElementById('smtp_sender').disabled=false;
	document.getElementById('smtp_server_port').disabled=false;
	document.getElementById('smtp_server_name').disabled=false;
	document.getElementById('tls_enabled').disabled=false;
	document.getElementById('ssl_enabled').disabled=false;
	document.getElementById('smtp_critic').disabled=false;
	document.getElementById('smtp_info').disabled=false;
	document.getElementById('smtp_warn').disabled=false;
	
	}
	SMTPNotifArticaEnableSwitch$t();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	}	
	
function save_watchdog_notif(){
	$sock=new sockets();
	$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	while (list ($num, $ligne) = each ($_POST) ){
		$UfdbguardSMTPNotifs[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($UfdbguardSMTPNotifs)), "UfdbguardSMTPNotifs");
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	//echo base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig"));
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	//print_r($MonitConfig);
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
	if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	if(!is_numeric($MonitConfig["WEBPROCISSUE"])){$MonitConfig["WEBPROCISSUE"]=3;}
	
	
	
	if($MonitConfig["DNSIssuesMAX"]==0){$MonitConfig["DNSIssuesMAX"]=1;}
	
	
	
	if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=5;}
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];	
	
	if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
	if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}	
	$ExternalPageToCheck=$MonitConfig["ExternalPageToCheck"];
	
	$MaxSwapPourc=$MonitConfig["MaxSwapPourc"];
	if(!is_numeric($MaxSwapPourc)){$MaxSwapPourc=10;}
	
	$MONIT_INSTALLED=0;
	$users=new usersMenus();
	if($users->MONIT_INSTALLED){$MONIT_INSTALLED=1;}
	$SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
	if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}
	$t=time();
	$html="
	<div id='$t'>
		<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend style='font-size:14px'>{enable_watchdog}:</td>
				<td>". Field_checkbox("$t-watchdog", 1,$MonitConfig["watchdog"],"InstanceCheckWatchdog{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:14px'>{notify_when_cpu_exceed}:</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogCPU", $MonitConfig["watchdogCPU"],"font-size:14px;width:60px")."&nbsp;%</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{NotifyDNSIssues}:</td>
				<td style='font-size:14px'>". Field_text("$t-NotifyDNSIssues", $MonitConfig["NotifyDNSIssues"],"font-size:14px;width:60px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{DNSIssuesMAX}:</td>
				<td style='font-size:14px'>". Field_text("$t-DNSIssuesMAX", $MonitConfig["DNSIssuesMAX"],"font-size:14px;width:60px")."</td>
				<td>&nbsp;</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:14px'>{notify_when_memory_exceed}:</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogMEM", $MonitConfig["watchdogMEM"],"font-size:14px;width:60px")."&nbsp;MB</td>
				<td>&nbsp;</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:14px'>{MaxSwapPourc}:</td>
				<td style='font-size:14px'>". Field_text("$t-MaxSwapPourc", $MaxSwapPourc,"font-size:14px;width:60px")."&nbsp;%</td>
				<td>&nbsp;</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:14px'>{minimum_reload_interval}:</td>
				<td style='font-size:14px'>". Field_text("$t-SquidCacheReloadTTL", $SquidCacheReloadTTL,"font-size:14px;width:60px")."&nbsp;{minutes}</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{tests_timeout}:</td>
				<td style='font-size:14px'>". Field_text("$t-MgrInfosMaxTimeOut", $MgrInfosMaxTimeOut,"font-size:14px;width:60px")."&nbsp;{seconds}</td>
				<td>&nbsp;</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:14px'>{page_to_check}:</td>
				<td style='font-size:14px'>". Field_text("$t-ExternalPageToCheck", $ExternalPageToCheck,"font-size:14px;width:260px")."&nbsp;{url}</td>
				<td>&nbsp;</td>
			</tr>
						
						
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}", "SaveWatchdog{$t}()",16)."</td>
			</tr>	
		</tbody>
	</table>
</div>
<script>
	function InstanceCheckWatchdog{$t}(){
		var MONIT_INSTALLED=$MONIT_INSTALLED;
		document.getElementById('$t-watchdog').disabled=true;
		document.getElementById('$t-watchdogMEM').disabled=true;
		document.getElementById('$t-watchdogCPU').disabled=true;
		if(MONIT_INSTALLED==0){return;}
		document.getElementById('$t-watchdog').disabled=false;
		if(!document.getElementById('$t-watchdog').checked){return;}
		document.getElementById('$t-watchdogMEM').disabled=false;
		document.getElementById('$t-watchdogCPU').disabled=false;
		document.getElementById('$t-NotifyDNSIssues').disabled=false;
		document.getElementById('$t-DNSIssuesMAX').disabled=false;				
		
	
		
	}
	
	
	var x_{$t}_SaveInstance= function (obj) {
			if(document.getElementById('squid-status')){
				LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');;
			}
		
			RefreshTab('watchdogsquid');
		}	
	
	function SaveWatchdog{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-watchdog').checked){XHR.appendData('watchdog',1);}else{XHR.appendData('watchdog',0);}
		if(document.getElementById('$t-NotifyDNSIssues').checked){XHR.appendData('NotifyDNSIssues',1);}else{XHR.appendData('NotifyDNSIssues',0);}
		XHR.appendData('watchdogMEM',document.getElementById('$t-watchdogMEM').value);
		XHR.appendData('watchdogCPU',document.getElementById('$t-watchdogCPU').value);
		XHR.appendData('ExternalPageToCheck',document.getElementById('$t-ExternalPageToCheck').value);
		XHR.appendData('MgrInfosMaxTimeOut',document.getElementById('$t-MgrInfosMaxTimeOut').value);
		XHR.appendData('SquidCacheReloadTTL',document.getElementById('$t-SquidCacheReloadTTL').value);
		XHR.appendData('MaxSwapPourc',document.getElementById('$t-MaxSwapPourc').value);
		XHR.appendData('DNSIssuesMAX',document.getElementById('$t-DNSIssuesMAX').value);
		
		
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
	}	
</script>

";
	
echo $tpl->_ENGINE_parse_body($html);
	
}
function events_table(){

	$page=CurrentPageName();
	$tpl=new templates();

	$description=$tpl->_ENGINE_parse_body("{description}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=800;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=157;
	$ROW2_WIDTH=607;


	$t=time();

	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},

	],	";
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
	{display: '$zDate', name : 'zDate', width :118, sortable : true, align: 'left'},
	{display: 'PID', name : 'zDate', width :42, sortable : true, align: 'center'},
	{display: '$description', name : 'line', width :583, sortable : true, align: 'left'},
	],

	searchitems : [
	{display: '$description', name : 'line'},
	],

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true

});
});



</script>";

echo $html;

}
function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$search=string_to_flexregex();

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	
	$content=unserialize(base64_decode($sock->getFrameWork("squid.php?watchdog-logs=yes&rp=$rp")));
	
	$c=0;
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
	krsort($content);
	while (list ($num, $ligne) = each ($content) ){
		$color="black";
		
		if(preg_match("#^(.+?)\s+(.*?)\s+\[([0-9]+)\](.*?)$#", $ligne,$re)){
			$date=$re[1]." ".$re[2];
			$pid=$re[3];
			$ligne=$re[4];
		}
		$ligne=str_replace("\n", "<br>", $ligne);
		$ligne=$tpl->javascript_parse_text("$ligne");
		if($search<>null){if(!preg_match("#$search#i", $ligne)){continue;}}
			$c++;
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
						"<span style='font-size:12px;color:$color'>$date</span>",
						"<span style='font-size:12px;color:$color'>$pid</span>",
						"<span style='font-size:12px;color:$color'>$ligne</span>",
							

				)
		);
	}

	$data['total'] =$c;
	echo json_encode($data);

}