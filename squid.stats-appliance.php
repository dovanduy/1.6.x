<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableRemoteStatisticsAppliance"])){Save();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{STATISTICS_APPLIANCE}");
	$html="YahooWin2(689,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	
	
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");	
	
	//$RemoteStatisticsApplianceSettings["SERVER"]
	$html="


	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{uuid}:</td>
		<td style='font-size:14px;font-weight:bold' colspan=2>$uuid</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{use_stats_appliance}:</td>
		<td style='font-size:14px'>". Field_checkbox("EnableRemoteStatisticsAppliance",1,$EnableRemoteStatisticsAppliance,"EnableRemoteStatisticsApplianceCheck()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td style='font-size:14px'>". Field_text("StatsServervame",$RemoteStatisticsApplianceSettings["SERVER"],"font-size:19px;font-weight:bold;width:200px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td style='font-size:14px'>". Field_text("StatsServerPort",$RemoteStatisticsApplianceSettings["PORT"],"font-size:14px;width:60px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{use_ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("StatsServerSSL",1,$RemoteStatisticsApplianceSettings["SSL"])."</td>
		<td>&nbsp;</td>
	</tr>						
	<tr>
		<td class=legend style='font-size:14px'>{send_syslogs_to_server}:</td>
		<td style='font-size:14px'>". Field_checkbox("EnableRemoteSyslogStatsAppliance",1,$EnableRemoteSyslogStatsAppliance)."</td>
		<td>". help_icon("{send_syslogs_to_server_client_explain}")."</td>
	</tr>	


	<tr>
	<td colspan=3 align='right'><hr>". button("{apply}","SaveStatsApp()",16)."</td>
	</tr>
	</tbody>
	</table>
		<div class=explain style='font-size:13px' id='STATISTICS_APPLIANCE_EXPLAIN_DIV'>{STATISTICS_APPLIANCE_EXPLAIN}</div>
	<script>
		var x_SaveStatsApp=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);}	
			if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}			
			CacheOff();
			YahooWin2Hide();

		}
	
	
		function EnableRemoteStatisticsApplianceCheck(){
			document.getElementById('StatsServervame').disabled=true;
			document.getElementById('StatsServerPort').disabled=true;
			document.getElementById('StatsServerSSL').disabled=true;
			document.getElementById('EnableRemoteSyslogStatsAppliance').disabled=true;
			
			if(document.getElementById('EnableRemoteStatisticsAppliance').checked){
				document.getElementById('StatsServervame').disabled=false;
				document.getElementById('StatsServerPort').disabled=false;
				document.getElementById('StatsServerSSL').disabled=false;
				document.getElementById('EnableRemoteSyslogStatsAppliance').disabled=true;	
				document.getElementById('EnableRemoteSyslogStatsAppliance').checked=true;			
			
			}
		
		}
		
	function SaveStatsApp(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableRemoteStatisticsAppliance').checked){XHR.appendData('EnableRemoteStatisticsAppliance','1');}else{XHR.appendData('EnableRemoteStatisticsAppliance','0');}
		if(document.getElementById('EnableRemoteSyslogStatsAppliance').checked){XHR.appendData('EnableRemoteSyslogStatsAppliance','1');}else{XHR.appendData('EnableRemoteSyslogStatsAppliance','0');}
		if(document.getElementById('StatsServerSSL').checked){XHR.appendData('StatsServerSSL','1');}else{XHR.appendData('StatsServerSSL','0');}
		XHR.appendData('StatsServervame',document.getElementById('StatsServervame').value);
		XHR.appendData('StatsServerPort',document.getElementById('StatsServerPort').value);
		AnimateDiv('STATISTICS_APPLIANCE_EXPLAIN_DIV');
		XHR.sendAndLoad('$page', 'POST',x_SaveStatsApp);	
	}
	EnableRemoteStatisticsApplianceCheck();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort=9000;}	
	$sock->SET_INFO("EnableRemoteStatisticsAppliance",$_POST["EnableRemoteStatisticsAppliance"]);
	
	$RemoteStatisticsApplianceSettings["SSL"]=$_POST["StatsServerSSL"];
	$RemoteStatisticsApplianceSettings["PORT"]=$_POST["StatsServerPort"];
	$RemoteStatisticsApplianceSettings["SERVER"]=$_POST["StatsServervame"];
	$sock->SaveConfigFile(base64_encode(serialize($RemoteStatisticsApplianceSettings)),"RemoteStatisticsApplianceSettings");
	$sock->SET_INFO("EnableRemoteSyslogStatsAppliance",$_POST["EnableRemoteSyslogStatsAppliance"]);
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");		
	writelogs("EnableRemoteStatisticsAppliance -> {$_POST["EnableRemoteStatisticsAppliance"]}",__FUNCTION__,__FILE__,__LINE__);
	if($_POST["EnableRemoteStatisticsAppliance"]==1){
		$sock->getFrameWork("services.php?netagent=yes");
	}	
	
	
}
