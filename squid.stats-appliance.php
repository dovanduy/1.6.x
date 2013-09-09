<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["wizard1"])){wizard1();exit;}
	if(isset($_GET["wizard2"])){wizard2();exit;}
	if(isset($_GET["wizard3"])){wizard3();exit;}
	if(isset($_GET["wizard4"])){wizard4();exit;}
	if(isset($_GET["wizard5"])){wizard5();exit;}
	if(isset($_GET["wizard6"])){wizard6();exit;}
	if(isset($_GET["wizard7"])){wizard7();exit;}
	if(isset($_GET["wizard8"])){wizard8();exit;}
	if(isset($_GET["wizard9"])){wizard9();exit;}
	if(isset($_GET["wizard10"])){wizard10();exit;}
	
	if(isset($_POST["EnableRemoteStatisticsAppliance"])){Save();exit;}
	if(isset($_POST["SERVER"])){wizard_save();exit;}
	if(isset($_POST["SquidDBListenPort"])){wizard_save();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{STATISTICS_APPLIANCE}");
	$html="YahooWin2(689,'$page?wizard1=yes','$title')";
	echo $html;
}

function wizard2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time();
	echo $tpl->_ENGINE_parse_body("
	<center style='font-size:18px'>{connecting_to}: {$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}</center>
	<div id='$tt'></div>
	<script>
		LoadAjax('$tt','$page?wizard3=yes&t=$t');
	</script>
	");
}

function wizard_restart(){
	$page=CurrentPageName();
	return "<center style='margin:20px'>".button("{back}", "Loadjs('$page');",22)."</center>";
}

function wizard3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];	
	$tt=time()+rand(0,time());
	
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php?test-connection=yes";
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
	 	echo FATAL_WARNING_SHOW_128($curl->error."<hr><strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong>$deb<hr>".wizard_restart());
	 	return;
	}
	
	if(strpos($curl->data, "CONNECTIONOK")==0){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128("<hr><strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong>{protocol_error}$deb".wizard_restart());
		return;
	}
	
	$tt=time();
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{connected_to}: {$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}</center>
			<center style='font-size:18px'>{checking_compatibilities}</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$tt','$page?wizard4=yes&t=$t');
					</script>
		");	
	
}

function wizard4(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());
	
	$cnxlog="<strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong><hr>";
	
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php?stats-appliance-compatibility=yes";
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr>$cnxlog$deb<hr>".wizard_restart());
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());
		return;
	}

	$array=unserialize(base64_decode($re[1]));
	
	if(!is_array($array)){
		echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());
		return;
	}

	if(count($array["DETAILS"])>0){
		$tR[]="<table style='width:100%'>";
		
		while (list ($num, $val) = each ($array["DETAILS"]) ){
			
			$tR[]="<tr><td class=legend style='font-size:14px'>$val</td></tr>";
		
		}
		$tR[]="</table>";
		$details=@implode("", $tR);
	}
	
	if($array["APP_SYSLOG_DB"]==false){
		echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{error_syslogdb_not_installed}$details".wizard_restart());
		return;
	}
	
	
	
	if($array["APP_SQUID_DB"]==false){
		echo FATAL_WARNING_SHOW_128("{error_squiddb_not_installed}$details".wizard_restart());
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{compatible}</center>
			<center style='font-size:18px'>{retreive_informations}</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$tt','$page?wizard5=yes&t=$t');
			</script>
			");
		
	
}
function wizard5(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());

	$cnxlog="<strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong><hr>";

	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php?stats-appliance-ports=yes";

	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr>$cnxlog$deb<hr>".wizard_restart());
		return;
	}

	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());return;}
	$array=unserialize(base64_decode($re[1]));
	if(!is_array($array)){echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());return;}

	
	$html="
	<div id='$tt'>
	<div style='width:95%' class=form>
	<div class=explain style='font-size:16px'>{STATISTICS_APPLIANCEV2_EXPLAIN_2}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{APP_SYSLOG_DB} {listen_port}:</td>
		<td style='font-size:16px'><strong>{$array["SyslogListenPort"]}</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{APP_SQUID_DB} {listen_port}:</td>
		<td style='font-size:16px'><strong>{$array["SquidDBListenPort"]}</td>		
	</tr>
	</table><hr>
	<table style='width:100%'>
	<tr>
	<td width=50% align=left>".button("{back}", "Loadjs('$page');",22)."</td>
	<td width=50% align=right>".button("{next}","Wizard2$tt()",22)."</td>
	</tr>
	</table>
	</div>	
	</div>	
	<script>
	
		var xWizard2$tt=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);}	
			LoadAjax('$tt','$page?wizard6=yes&t=$t');

		}	
	
	function Wizard2$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidDBListenPort','{$array["SquidDBListenPort"]}');
		XHR.appendData('SyslogListenPort','{$array["SyslogListenPort"]}');
		AnimateDiv('$tt');
		XHR.sendAndLoad('$page', 'POST',xWizard2$tt);	
	}
	</script>					
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function wizard6(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{creating_privileges}...</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$tt','$page?wizard7=yes&t=$t');
			</script>
	");
}

function wizard7(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());

	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php?stats-perform-connection=yes";
	$cnxlog="<strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong><hr>";
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr><strong>{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]} SSL:{$WizardStatsAppliance["SSL"]}</strong>$deb<hr>".wizard_restart());
		return;
	}

	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());return;}
	$array=unserialize(base64_decode($re[1]));
	if(!is_array($array)){echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{artica_protocol_error}$deb".wizard_restart());return;}

	
	
	if(isset($array["ERROR"])){
		echo FATAL_WARNING_SHOW_128("<hr>$cnxlog{error}<hr>{$array["ERROR"]}<hr>$deb".wizard_restart());return;
		
	}
	
	$WizardStatsAppliance["username"]=$array["username"];
	$WizardStatsAppliance["password"]=$array["password"];
	$sock->SaveConfigFile(base64_encode(serialize($WizardStatsAppliance)), "WizardStatsAppliance");
	
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{saving_parameters}</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$tt','$page?wizard8=yes&t=$t');
			</script>
			");

}

function wizard8(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());
	
	
	
	$sock->SET_INFO("DisableLocalStatisticsTasks",1);
	$sock->SET_INFO("EnableSquidRemoteMySQL",1);
	$sock->SET_INFO("EnableRemoteStatisticsAppliance",0);
	
	
	$sock->SET_INFO("squidRemostatisticsServer",$WizardStatsAppliance["SERVER"]);
	$sock->SET_INFO("squidRemostatisticsPort",$WizardStatsAppliance["SquidDBListenPort"]);
	$sock->SET_INFO("squidRemostatisticsUser",$WizardStatsAppliance["username"]);
	$sock->SET_INFO("squidRemostatisticsPassword",$WizardStatsAppliance["password"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{APP_SQUID_DB}:{$WizardStatsAppliance["username"]}@{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["SquidDBListenPort"]}</center>
			<center style='font-size:18px'>{saving_parameters}</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$tt','$page?wizard9=yes&t=$t');
			</script>
			");	
	
	
}

function wizard9(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());
	
	
	
	$sock->SET_INFO("MySQLSyslogType",2);
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	$TuningParameters["username"]=$WizardStatsAppliance["username"];
	$TuningParameters["password"]=$WizardStatsAppliance["password"];
	$TuningParameters["mysqlserver"]=$WizardStatsAppliance["SERVER"];
	$TuningParameters["RemotePort"]=$WizardStatsAppliance["SyslogListenPort"];
	$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
	
	
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
	
	echo $tpl->_ENGINE_parse_body("
			<center style='font-size:18px'>{APP_SYSLOG_DB}:{$WizardStatsAppliance["username"]}@{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["SyslogListenPort"]}</center>
			<center style='font-size:18px'>{success}</center>
			<div id='$tt'></div>
			<script>
			LoadAjax('$t','$page?wizard10=yes&t=$t');
			</script>
			");	
	
}

function wizard10(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$t=$_GET["t"];
	$tt=time()+rand(0,time());
	echo $tpl->_ENGINE_parse_body("<center style='font-size:18px' class=explain>{STATISTICS_APPLIANCEV2_EXPLAIN_3}</center>");	
	
}

function wizard_save(){
	$sock=new sockets();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	while (list ($a, $b) = each ($_POST) ){
		$WizardStatsAppliance[$a]=$b;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($WizardStatsAppliance)), "WizardStatsAppliance");
}

function debug_curl($array){
	$t[]="<table style='width:100%'>";
	
	while (list ($num, $val) = each ($array) ){
		if(is_array($val)){
			while (list ($a, $b) = each ($val) ){$tt[]="<li>$a = $b</li>";}
			$val=null;
			$val=@implode("\n", $tt);
		}
		$t[]="<tr>
		<td class=legend style='font-size:14px'>$num:</td>
		<td style='font-size:14px'>$val</td>
		</tr>";	
		
	}
	$t[]="</table>";
	
	return @implode("\n", $t);
	
}

function wizard1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	$sock->SET_INFO("WizardStatsApplianceSeen", 1);
	
	if(!is_numeric($WizardStatsAppliance["SSL"])){$WizardStatsAppliance["SSL"]=1;}
	if(!is_numeric($WizardStatsAppliance["PORT"])){$WizardStatsAppliance["PORT"]=9000;}
	
	$html="
	<div id='$t'>		
	<div class=explain style='font-size:16px'>{STATISTICS_APPLIANCEV2_EXPLAIN_1}</div>
	<div class=form style='width:95%'>
	<table style='width:100%'>		
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td style='font-size:14px'>". Field_text("SERVER-$t",$WizardStatsAppliance["SERVER"],"font-size:19px;font-weight:bold;width:200px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td style='font-size:14px'>". Field_text("PORT-$t",$WizardStatsAppliance["PORT"],"font-size:14px;width:60px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{use_ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("SSL-$t",1,$WizardStatsAppliance["SSL"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{next}","Wizard1$t()",18)."</td>
	</tr>
	</table>	
	<script>
	
		var xWizard1$t=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);}	
			LoadAjax('$t','$page?wizard2=yes&t=$t');

		}	
	
	function Wizard1$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('SSL-$t').checked){XHR.appendData('SSL','1');}else{XHR.appendData('SSL','0');}
		XHR.appendData('SERVER',document.getElementById('SERVER-$t').value);
		XHR.appendData('PORT',document.getElementById('PORT-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xWizard1$t);	
	}
	
	RefreshTab('squid_main_svc');
	</script>				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
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
