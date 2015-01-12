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
	if(isset($_POST["EnableRemoteSyslogStatsAppliance"])){Save();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{remote_statistics_server}");
	$html="YahooWin2(689,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	
	$RemoteSyslogAppliance=unserialize(base64_decode($sock->GET_INFO("RemoteSyslogAppliance")));
	if(!is_numeric($RemoteSyslogAppliance["SSL"])){$RemoteSyslogAppliance["SSL"]=1;}
	if(!is_numeric($RemoteSyslogAppliance["PORT"])){$RemoteSyslogAppliance["PORT"]=9000;}
	$uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");	
	$t=time();
	$html="
	<div id=animate-$t></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{uuid}:</td>
		<td style='font-size:14px;font-weight:bold' colspan=2>$uuid</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{use_remote_server}:</td>
		<td style='font-size:14px'>". Field_checkbox("EnableRemoteSyslogStatsAppliance-$t",1,$EnableRemoteSyslogStatsAppliance)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td style='font-size:14px'>". Field_text("StatsServervame-$t",$RemoteSyslogAppliance["SERVER"],"font-size:19px;font-weight:bold;width:200px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td style='font-size:14px'>". Field_text("StatsServerPort-$t",$RemoteSyslogAppliance["PORT"],"font-size:14px;width:60px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{use_ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("StatsServerSSL-$t",1,$RemoteSyslogAppliance["SSL"])."</td>
		<td>&nbsp;</td>
	</tr>						
	<tr>
	<td colspan=3 align='right'><hr>". button("{apply}","SaveStatsApp$t()",16)."</td>
	</tr>
	</tbody>
	</table>
		<div class=text-info style='font-size:16px' id='STATISTICS_APPLIANCE_EXPLAIN_DIV'>{STATISTICS_SYSLOGAPPLIANCE_EXPLAIN}</div>
	<script>
		var x_SaveStatsApp$t=function (obj) {
			var results=obj.responseText;
			document.getElementById('animate-$t').innerHTML='';
			if(results.length>10){alert(results);}	
			if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}			
			Loadjs('squid.compile.progress.php?ask=yes');
			YahooWin2Hide();

		}
		
	function SaveStatsApp$t(){
		var XHR = new XHRConnection();
		
		if(document.getElementById('EnableRemoteSyslogStatsAppliance-$t').checked){XHR.appendData('EnableRemoteSyslogStatsAppliance','1');}else{XHR.appendData('EnableRemoteSyslogStatsAppliance','0');}
		if(document.getElementById('StatsServerSSL-$t').checked){XHR.appendData('StatsServerSSL','1');}else{XHR.appendData('StatsServerSSL','0');}
		XHR.appendData('StatsServervame',document.getElementById('StatsServervame-$t').value);
		XHR.appendData('StatsServerPort',document.getElementById('StatsServerPort-$t').value);
		AnimateDiv('animate-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveStatsApp$t);	
	}
	EnableRemoteStatisticsApplianceCheck();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$sock=new sockets();
	$tpl=new templates();
	$curl=new ccurl();
	$proto="http";
	$StatsServervame=$_POST["StatsServervame"];
	$StatsServerPort=$_POST["StatsServerPort"];
	$StatsServerSSL=$_POST["StatsServerSSL"];
	if($StatsServerSSL==1){$proto="https";}
	$EnableRemoteSyslogStatsAppliance=$_POST["EnableRemoteSyslogStatsAppliance"];
	$uri="$proto://$StatsServervame:$StatsServerPort/nodes.listener.php";
	
	
	if($EnableRemoteSyslogStatsAppliance==1){
		
		writelogs("$uri",__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl($uri);
		$curl->parms["OPENSYSLOG"]=1;
		if(!$curl->get()){
			echo "Error ".$tpl->_ENGINE_parse_body($curl->error);
			return;
		}
		
		
		
		if(strpos($curl->data, "<RESULTS>OK</RESULTS>")==0){
			echo "Error Protocol error or bad version on remote server\n";
			return;
		}
	}
	
	$sock->SET_INFO("EnableRemoteSyslogStatsAppliance",$EnableRemoteSyslogStatsAppliance);
	
	$RemoteSyslogAppliance["SSL"]=$_POST["StatsServerSSL"];
	$RemoteSyslogAppliance["PORT"]=$_POST["StatsServerPort"];
	$RemoteSyslogAppliance["SERVER"]=$_POST["StatsServervame"];
	$sock->SaveConfigFile(base64_encode(serialize($RemoteSyslogAppliance)),"RemoteSyslogAppliance");
	
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");
	$sock->getFrameWork("squid.php?compile-schedules-reste=yes");
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
	
	
}
