<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	// CicapEnabled
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){die('not allowed');}
	
	if(isset($_POST["stop-cicap"])){stop();exit;}
	if(isset($_POST["start-cicap"])){start();exit;}
	if(isset($_POST["restart-cicap"])){restart();exit;}
	if(isset($_GET["rows-table"])){events_table();exit;}
	if($_GET["main"]=="index"){echo index();exit;}
	if($_GET["main"]=="daemons"){echo daemons();exit;}
	if($_GET["main"]=="clamav"){echo clamav();exit;}
	if($_GET["main"]=="logs"){echo logs();exit;}
	if($_GET["main"]=="status"){status();exit;}
	if($_GET["main"]=="events"){events();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["MaxKeepAliveRequests"])){save_settings();exit;}
	if(isset($_POST["srv_clamav_SendPercentData"])){save_settings_post();exit;}
	if(isset($_POST["EnableAV"])){EnableAV();exit;}
	if(isset($_POST["CicapEnabled"])){CicapEnabled();exit;}
	js();
	
	
function stop(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?stop-cicap=yes&stay=yes");
}	
function start(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?start-cicap=yes&stay=yes");
}
function restart(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-cicap=yes&stay=yes");
}		
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body('{cicap_title}');
	$users=new usersMenus();
	if($users->KASPERSKY_WEB_APPLIANCE){$title=$tpl->_ENGINE_parse_body('{APP_C_ICAP}');}
	$title1=$tpl->_ENGINE_parse_body('{clamav_settings}');
	$title2=$tpl->_ENGINE_parse_body('{clamav_settings}');
	
	$start="loadcicap();";
	if(isset($_GET["runthis"])){$start=$_GET["runthis"]."();";}
	
	$html="
	
		function loadcicap(){
			YahooWin(990,'$page?main=index','$title');
		
		}
		
		function cicap_daemons(){
			YahooWin2(550,'$page?main=daemons','$title');
		
		}		

		function cicap_clamav(){
			YahooWin2(550,'$page?main=clamav','$title');
		
		}					
		
		function cicap_logs(){
			YahooWin2(550,'$page?main=logs','$title');
		
		}			
		
		$start
	
	";
	echo $html;
}

function events(){
	$tpl=new templates();
	$page=CurrentPageName();
	$events=$tpl->javascript_parse_text("{events}");
	$t=time();
	$html="
<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zdate', width :115, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$events</span>', name : 'event', width :1298, sortable : true, align: 'left'},

		
		
		
	],
	
	sortname: '	ipaddr',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>C-ICAP $events</strong>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: true,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});
</script>";
echo $html;	
	
}


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?cicap-ini-status=yes')));	
	$CICAP=DAEMON_STATUS_ROUND("C-ICAP",$ini,null,0);
	
	$CLAMAv=DAEMON_STATUS_ROUND("CLAMAV",$ini,null,0);
	$FRESHCLAM=DAEMON_STATUS_ROUND("FRESHCLAM",$ini,null,0);
	
	$CICAP_LOCAL_WARNING=null;
	$t=time();
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}	
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	
	$error_page_js="Loadjs('c-icap.alertpage.php')";
	$memory_booster_js="Loadjs('c-icap.memory.php')";
	
	
	if(!$users->CORP_LICENSE){
		$error_page_js="alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
	}
	
	$sock->getFrameWork("clamav.php?sigtool=yes");
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
	if(count($bases)<2){
		
		echo FATAL_ERROR_SHOW_128("<span style='font-size:48px'>{missing_clamav_pattern_databases}</span>
				<center style='margin:50px'>
					". button("{update_now}", "Loadjs('clamav.update.progress.php')",52).
				"</center>
				
				");
		return;
	}
	

	
	
	$vir="32-virus-find-grey.png";
	$virtxt="{enable_antivirus}";
	$js="EnableAV(1)";
	
	if($EnableClamavInCiCap==1){
		$vir="32-virus-find.png";
		$virtxt="{disable_antivirus}";
		$js="EnableAV(0)";
	}
	
	if($CicapEnabled==1){
		$q=new mysql_squid_builder();
		$ligneSQL=mysql_fetch_array($q->QUERY_SQL("SELECT `enabled` FROM c_icap_services WHERE ID=1"));
		if($ligneSQL["enabled"]==0){
			$CICAP_LOCAL_WARNING=Paragraphe("warning-panneau-64.png", "{local_proxy_service_not_linked}", 
					"{local_proxy_service_not_linked_explain}",
					"javascript:AnimateDiv('BodyContent');LoadAjax('BodyContent','icap-center.php')",null,350);
		}
		
	}
	
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{http_antivirus_for_proxy}</div>
	<div id='$t'>
	<table style='width:100%'>
	<tr>
		<td width=350px valign='top'>$CICAP$CLAMAv$FRESHCLAM$CICAP_LOCAL_WARNING
			<div style='text-align:right'>". 
				imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_icapwebfilter_tabs');RefreshTab('main_config_cicap')")."
			</div>
		</td>
		<td valign='top'>
		<div style='width:98%' class=form>
		
		<table style='width:100%'>
			
			<tr>
				
				<td width=50% nowrap><a href=\"javascript:blur();\" 
				OnClick=\"javascript:$error_page_js;\" 
				style='font-size:22px;text-decoration:underline'>{alert_page}</td>
			
				
				<td width=50%><a href=\"javascript:blur();\" 
				OnClick=\"javascript:$memory_booster_js;\" 
				style='font-size:22px;text-decoration:underline'>{memory_booster}</td>
		</tr>
		<tr style='height:70px'>
		<td>
				
				<td colspan=2 align=right><a href=\"javascript:blur();\" 
				OnClick=\"javascript:GotoClamavUpdates();\" 
				style='font-size:16px;text-decoration:underline'>{also_see_update_databases}</td>
				
				
			</tr>			
			
		</table>
		</div>
			<div style='width:98%' class=form>
			<div style='padding:20px;-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;border:2px solid #CCCCCC'>
	". Paragraphe_switch_img("{ACTIVATE_ICAP_AV}", "{ACTIVATE_ICAP_AV_TEXT}",
			"CicapEnabled-$t",$CicapEnabled, null,900)."
	<hr>
	<div style='text-align:right'>". button("{apply}","SaveEnable$t()",42)."
			</div>
	</div>
	<p>&nbsp;</p>
	
	".@implode("\n", $DBS)."		
			
	</td>
	</td>
	</tr>

	</table>
	
	
	
	
	</div>
<script>
	var x_cicapdefault=function(obj){
     var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
	  RefreshTab('main_config_cicap');
	
	}

	var xSaveEnable$t=function(obj){
     var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
	  RefreshTab('main_config_cicap');
	  RefreshTab('squid_main_svc');
	  Loadjs('squid.compile.progress.php?ask=yes');
	
	}	
	
	function StopCicap(){
		var XHR = new XHRConnection();
	    XHR.appendData('stop-cicap','yes');
		AnimateDiv('$t');
       	XHR.sendAndLoad('$page', 'POST',x_cicapdefault);
	}
	function StartCicap(){
		var XHR = new XHRConnection();
	    XHR.appendData('start-cicap','yes');
		AnimateDiv('$t');
       	XHR.sendAndLoad('$page', 'POST',x_cicapdefault);
	}
	function RestartCicap(){
		var XHR = new XHRConnection();
	    XHR.appendData('restart-cicap','yes');
		AnimateDiv('$t');
       	XHR.sendAndLoad('$page', 'POST',x_cicapdefault);
	}
	function EnableAV(enable){
		var XHR = new XHRConnection();
	    XHR.appendData('EnableAV',enable);
		AnimateDiv('$t');
       	XHR.sendAndLoad('$page', 'POST',x_cicapdefault);
	}	

	function SaveEnable$t(){
		var XHR = new XHRConnection();
		XHR.appendData('CicapEnabled',document.getElementById('CicapEnabled-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSaveEnable$t);
	}
	
	
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function index(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{this_service_is_managed_remote_statsappliance}"));
		return;
		
	}
	
	
	$array["status"]='{status}';
	//$array["rules"]='{rules}';
	$array["daemons"]='{daemon_settings}';
	$array["clamav"]='ClamAV Antivirus';
	$array["events"]='{events}';
	
	
	


	
	//$array["logs"]='{icap_logs}';
	$fontsize="22";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="rules"){
			$html[]= "<li><a href=\"c-icap.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		$html[]= "<li><a href=\"$page?main=$num\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "main_config_cicap",950);
}

function logs(){
	$page=CurrentPageName();
	
	
	
	$dd=logs_datas();
$html="<H1>{icap_logs}</H1>
	<p class=caption>{icap_logs_text}</p>
	
	". RoundedLightWhite("<div style='width:99%;height:300px;overflow:auto'>$dd</div>");
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function logs_datas(){
	
	$sock=new sockets();
	$datas=$sock->getfile('cicapevents');
	$tbl=explode("\n",$datas);
	if(!is_array($tbl)){return null;}
	$tbl=array_reverse($tbl);
	while (list ($num, $line) = each ($tbl) ){
		if(trim($line==null)){continue;}
		$line=htmlspecialchars($line);
		$html=$html."<div><code style='font-size:10px'>$line</code></div>";
		

		
	}
	
	return $html;
	
	
}

function EnableAV(){
	$sock=new sockets();
	$sock->SET_INFO("EnableClamavInCiCap",$_POST["EnableAV"]);
	$sock->SET_INFO("EnableClamavInCiCap2",$_POST["EnableAV"]);
	$q=new mysql_squid_builder();
	
	if($_POST["EnableAV"]==1){
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=1 WHERE ID=1");
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=2 WHERE ID=2");
		}
	else{
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");
	
	}
	
	
	
	
	$users=new usersMenus();
	if($users->WEBSTATS_APPLIANCE){
		$sock->SET_INFO("EnableStatisticsCICAPService",$_POST["EnableAV"]);
		
	}
	$sock->getFrameWork("services.php?restart-artica-status=yes");
	$ci=new cicap();
	$ci->Save();
	$sock->getFrameWork("cmd.php?clamd-restart=yes");
	NotifyServers();
}

function NotifyServers(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
	}	
	
}


function clamav(){
	$sock=new sockets();
	$ci=new cicap();
	$page=CurrentPageName();
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	$EnableClamavInCiCap2=$sock->GET_INFO("EnableClamavInCiCap2");
	$ClamavTemporaryDirectory=$sock->GET_INFO("ClamavTemporaryDirectory");
	if($ClamavTemporaryDirectory==null){$ClamavTemporaryDirectory="/home/clamav";}
	
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}			
	$html="
	<div style='font-size:26px;margin-bottom:20px'>{clamav_settings_text}</div>
	
	<div id='ffmcc2' style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{ENABLE_CLAMAV}:</td>
		<td style=';font-size:14px'>" . Field_checkbox_design('EnableClamavInCiCap',1,$EnableClamavInCiCap,'EnableClamavInCiCapCheck()')."</td>
		<td>&nbsp;</td>
	</tr>	
	
	
	
	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{srv_clamav.SendPercentData}","{srv_clamav.SendPercentData_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.SendPercentData',
				$ci->main_array["CONF"]["srv_clamav.SendPercentData"],'width:55px;font-size:22px;padding:3px')."&nbsp;%</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.StartSendPercentDataAfter}","{srv_clamav.StartSendPercentDataAfter_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.StartSendPercentDataAfter',$ci->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"],'width:125px;font-size:22px;padding:3px')."&nbsp;M</td>
		<td>" . help_icon()."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.MaxObjectSize}","{srv_clamav.MaxObjectSize_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.MaxObjectSize',$ci->main_array["CONF"]["srv_clamav.MaxObjectSize"],'width:150px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.ClamAvMaxFilesInArchive}","{srv_clamav.ClamAvMaxFilesInArchive}").":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.ClamAvMaxFilesInArchive',
		$ci->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"],'width:150px;font-size:22px;padding:3px')."&nbsp;{files}</td>

	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.ClamAvMaxFileSizeInArchive}","{srv_clamav.ClamAvMaxFileSizeInArchive}").":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.ClamAvMaxFileSizeInArchive',
		$ci->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"],'width:150px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.ClamAvMaxRecLevel}",'{srv_clamav.ClamAvMaxRecLevel}').":</td>
		<td style=';font-size:22px'>" . Field_text('srv_clamav.ClamAvMaxRecLevel',
		$ci->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"],'width:150px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{temp_dir}","{temp_dir}").":</td>
		<td style=';font-size:22px;valign:middle'>" . Field_text('ClamavTemporaryDirectory',
		$ClamavTemporaryDirectory,'width:450px;font-size:22px;padding:3px')."&nbsp;".button_browse("ClamavTemporaryDirectory")."</td>
	</tr>				
				
	
	<tr>
		<td colspan=2 align='right'><hr>
		". button("{apply}","SaveICapCLam()",28)."
			
		</td>
	</tr>
	</table>
	</div>
	
	<script>
	
	
	var x_SaveICapCLam=function(obj){
     var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
	  RefreshTab('main_config_cicap');
	
	}	
	
	function SaveICapCLam(){
		var XHR = new XHRConnection();
		
		
		XHR.appendData('ClamavTemporaryDirectory',document.getElementById('ClamavTemporaryDirectory').value);
	    XHR.appendData('srv_clamav.SendPercentData',document.getElementById('srv_clamav.SendPercentData').value);
	    XHR.appendData('srv_clamav.StartSendPercentDataAfter',document.getElementById('srv_clamav.StartSendPercentDataAfter').value);
	    XHR.appendData('srv_clamav.MaxObjectSize',document.getElementById('srv_clamav.MaxObjectSize').value);
	    XHR.appendData('srv_clamav.ClamAvMaxFilesInArchive',document.getElementById('srv_clamav.ClamAvMaxFilesInArchive').value);
	    XHR.appendData('srv_clamav.ClamAvMaxFileSizeInArchive',document.getElementById('srv_clamav.ClamAvMaxFileSizeInArchive').value);
	    XHR.appendData('srv_clamav.ClamAvMaxRecLevel',document.getElementById('srv_clamav.ClamAvMaxRecLevel').value);
	    if(document.getElementById('EnableClamavInCiCap').checked){XHR.appendData('EnableClamavInCiCap',1);}else{XHR.appendData('EnableClamavInCiCap',0);}
	    
		AnimateDiv('ffmcc2');
       	XHR.sendAndLoad('$page', 'POST',x_SaveICapCLam);
	}
	
	function EnableClamavInCiCapCheck(){
	 	document.getElementById('ClamavTemporaryDirectory').disabled=true;
		document.getElementById('srv_clamav.SendPercentData').disabled=true;
		document.getElementById('srv_clamav.StartSendPercentDataAfter').disabled=true;
		document.getElementById('srv_clamav.MaxObjectSize').disabled=true;
		document.getElementById('srv_clamav.ClamAvMaxFilesInArchive').disabled=true;
		document.getElementById('srv_clamav.ClamAvMaxFileSizeInArchive').disabled=true;
		document.getElementById('srv_clamav.ClamAvMaxRecLevel').disabled=true;
		if(document.getElementById('EnableClamavInCiCap').checked){
			document.getElementById('ClamavTemporaryDirectory').disabled=false;
			document.getElementById('srv_clamav.SendPercentData').disabled=false;
			document.getElementById('srv_clamav.StartSendPercentDataAfter').disabled=false;
			document.getElementById('srv_clamav.MaxObjectSize').disabled=false;
			document.getElementById('srv_clamav.ClamAvMaxFilesInArchive').disabled=false;
			document.getElementById('srv_clamav.ClamAvMaxFileSizeInArchive').disabled=false;
			document.getElementById('srv_clamav.ClamAvMaxRecLevel').disabled=false;		
		}else{
			
		}
	
	}
	
	setTimeout('EnableClamavInCiCapCheck()',500);
	
</script>	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function daemons(){
	
	$ci=new cicap();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	$MaxCICAPWorkTimeMin=$sock->GET_INFO("MaxCICAPWorkTimeMin");
	$MaxCICAPWorkSize=$sock->GET_INFO("MaxCICAPWorkSize");
	if(!is_numeric($MaxCICAPWorkTimeMin)){$MaxCICAPWorkTimeMin=1440;}
	if(!is_numeric($MaxCICAPWorkSize)){$MaxCICAPWorkSize=5000;}
	$CICAPListenAddress=$sock->GET_INFO("CICAPListenAddress");
	
	if(!is_numeric($EnableSquidGuardInCiCAP)){$EnableSquidGuardInCiCAP=0;}
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}
	if($CICAPListenAddress==null){$CICAPListenAddress="127.0.0.1";}
	
	$users=new usersMenus();
	
	if(!$users->SQUIDGUARD_INSTALLED){
		$disableSquiduard=true;
		$EnableSquidGuardInCiCAP=0;	
	}
	
	if($users->APP_UFDBGUARD_INSTALLED){
		if($EnableUfdbGuard==1){
			$disableSquiduard=true;
			$EnableSquidGuardInCiCAP=0;
		}
	}
	
	if($disableSquiduard){$DisableSquidGuardCheckCicap="DisableSquidGuardCheckCicap();";}
	
	$tcp=new networking();
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	
	
	
	$notifyVirHTTPServer=false;
	if($ci->main_array["CONF"]["ViralatorMode"]==1){
	if(preg_match('#https://(.*?)/exec#',$ci->main_array["CONF"]["VirHTTPServer"],$re)){
		if(trim($re[1])==null){$notifyVirHTTPServer=true;}
		if(trim($re[1])=="127.0.0.1"){$notifyVirHTTPServer=true;}
		if(trim($re[1])=="localhost"){$notifyVirHTTPServer=true;}
	}}
	
	if($notifyVirHTTPServer==true){
		$color="color:#d32d2d;font-weight:bolder";
	}
	
	
	for($i=1;$i<13;$i++){
		$f[$i]=$i;
	}
	
	
	$html="
	
	<div style='font-size:26px;margin-bottom:20px'>{daemon_settings_text}</div>
	
	<input type='hidden' id='EnableClamavInCiCapCheck' value='$EnableClamavInCiCap'>
	<div id='ffmcc1' style='width:95%'  class=form>
	<table  style='width:100%'>	
	
	
	<tr>
		<td class=legend style='font-size:22px'>{listen_address}:</td>
		<td style='font-size:22px'>" . Field_array_Hash($ips,'CICAPListenAddress',
				$CICAPListenAddress,null,null,0,'font-size:22px;padding:3px')."</td>
		<td></td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{Timeout}","{Timeout_text}").":</td>
		<td style='font-size:22px'>" . Field_text('Timeout',$ci->main_array["CONF"]["Timeout"],
				'width:150px;font-size:22px;padding:3px')."&nbsp;{seconds}</td>
	</tr>
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{MaxKeepAliveRequests}","{MaxKeepAliveRequests_text}").":</td>
		<td>" . Field_text('MaxKeepAliveRequests',$ci->main_array["CONF"]["MaxKeepAliveRequests"],
				'width:150px;font-size:22px;padding:3px')."&nbsp;</td>

	</tr>	
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{KeepAliveTimeout}","{KeepAliveTimeout_text}").":</td>
		<td style='font-size:22px'>" . Field_text('KeepAliveTimeout',
				$ci->main_array["CONF"]["KeepAliveTimeout"],'width:150px;font-size:22px;padding:3px')."&nbsp;{seconds}</td>
	</tr>
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{MaxServers}","{MaxServers_text}").":</td>
		<td>" . Field_text('MaxServers',$ci->main_array["CONF"]["MaxServers"],'width:150px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>	
	
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{MinSpareThreads}","{MinSpareThreads_text}").":</td>
		<td>" . Field_text('MinSpareThreads',$ci->main_array["CONF"]["MinSpareThreads"],
				'width:150px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>		
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{MaxSpareThreads}","{MaxSpareThreads_text}").":</td>
		<td>" . Field_text('MaxSpareThreads',$ci->main_array["CONF"]["MaxSpareThreads"],'width:150px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>	

	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{ThreadsPerChild}","{ThreadsPerChild_text}").":</td>
		<td>" . Field_text('ThreadsPerChild',$ci->main_array["CONF"]["ThreadsPerChild"],'width:150px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>	

	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{MaxRequestsPerChild}","{MaxRequestsPerChild_text}").":</td>
		<td>" . Field_text('MaxRequestsPerChild',$ci->main_array["CONF"]["MaxRequestsPerChild"],'width:150px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>	
		<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{debug_mode}","{log level_text}").":</td>
		<td>" . Field_array_Hash($f,"DebugLevel",$ci->main_array["CONF"]["DebugLevel"],null,null,0,
				'font-size:22px;padding:3px')."&nbsp;</td>
	</tr>
				
				
				
	<tr>
		<td colspan=3>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 style='border-top:1px solid #CCCCCC'>&nbsp;</td>
	</tr>

				
	</tr>	
		<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{max_time_in_tmp}","{max_time_in_tmp_explain}").":</td>
		<td style='font-size:22px'>" . Field_text("MaxCICAPWorkTimeMin",$MaxCICAPWorkTimeMin,
				'width:150px;font-size:22px;padding:3px')."&nbsp;{minutes}</td>
	</tr>				
	</tr>	
		<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{max_tempdir_size}","{max_tempdir_size_explain}").":</td>
		<td style='font-size:22px'>" . Field_text("MaxCICAPWorkSize",$MaxCICAPWorkSize,
				'width:150px;font-size:22px;padding:3px')."&nbsp;MB</td>
	</tr>					

				
	<tr>
		<td colspan=3>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 style='border-top:1px solid #CCCCCC'>&nbsp;</td>
	</tr>
	
	<tr>
		<td class=legend nowrap style='font-size:22px'>".texttooltip("{ViralatorMode}","{ViralatorMode_text}").":</td>
		<td>" . Field_checkbox_design("ViralatorMode",1,$ci->main_array["CONF"]["ViralatorMode"],"EnableDisableViralatorMode()")."</td>

	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{VirSaveDir}","{VirSaveDir_text}").":</td>
		<td>" . Field_text('VirSaveDir',$ci->main_array["CONF"]["VirSaveDir"],
				'width:490px;font-size:22px;padding:3px')."&nbsp;</td>

	</tr>		
	<tr>
		<td class=legend style='$color;font-size:22px'>".texttooltip("{VirHTTPServer}","{VirHTTPServer_text}").":</td>
		<td>" . Field_text('VirHTTPServer',$ci->main_array["CONF"]["VirHTTPServer"],'width:290px;font-size:22px;padding:3px')."&nbsp;</td>
	</tr>
	<tr>	
		<td class=legend>{example}:</td>
		<td colspan=2><strong><a href='https://{$_SERVER['SERVER_NAME']}/exec.cicap.php?usename=%f&remove=1&file='>https://{$_SERVER['SERVER_NAME']}/exec.cicap.php?usename=%f&remove=1&file=</a></strong></td>
	</tr>	
			
	

	<tr>
		<td colspan=3 align='right'>
		<hr>
			". button("{apply}","SaveIcapDaemonSet()",26)."
		</td>
	</tr>
	</table>
	</div>
	
	<script>
var x_SaveIcapDaemonSet=function(obj){
     var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
	  RefreshTab('main_config_cicap');
	
	}	
	
	function SaveIcapDaemonSet(){
		var XHR = new XHRConnection();
	    XHR.appendData('Timeout',document.getElementById('Timeout').value);
	    XHR.appendData('MaxKeepAliveRequests',document.getElementById('MaxKeepAliveRequests').value);
	    XHR.appendData('KeepAliveTimeout',document.getElementById('KeepAliveTimeout').value);
	    
	    XHR.appendData('MaxServers',document.getElementById('MaxServers').value);
	    XHR.appendData('MinSpareThreads',document.getElementById('MinSpareThreads').value);
	    XHR.appendData('ThreadsPerChild',document.getElementById('ThreadsPerChild').value);
	    XHR.appendData('MaxRequestsPerChild',document.getElementById('MaxRequestsPerChild').value);
	    XHR.appendData('VirSaveDir',document.getElementById('VirSaveDir').value);
	    XHR.appendData('VirHTTPServer',document.getElementById('VirHTTPServer').value);
	    XHR.appendData('DebugLevel',document.getElementById('DebugLevel').value);
	    XHR.appendData('CICAPListenAddress',document.getElementById('CICAPListenAddress').value);
	    if(document.getElementById('ViralatorMode').checked){XHR.appendData('ViralatorMode',1);}else{XHR.appendData('ViralatorMode',0);}
		XHR.sendAndLoad('$page', 'GET',x_SaveIcapDaemonSet);
	}
	
	function EnableDisableViralatorMode(){
		 document.getElementById('VirSaveDir').disabled=true;
	     document.getElementById('VirHTTPServer').disabled=true;
	     if(document.getElementById('EnableClamavInCiCapCheck').value==0){return;}
	     
	     
	     if(document.getElementById('ViralatorMode').checked){
	      document.getElementById('VirSaveDir').disabled=false;
	      document.getElementById('VirHTTPServer').disabled=false;
		 }
	
	}
	
	function DisableSquidGuardCheckCicap(){
	 	if(document.getElementById('EnableSquidGuardInCiCAP')){
	 		document.getElementById('EnableSquidGuardInCiCAP').checked=false;
	 		document.getElementById('EnableSquidGuardInCiCAP').disabled=true;
		}
	}
	
	EnableDisableViralatorMode();
	$DisableSquidGuardCheckCicap
	
</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function save_settings_post(){
$sock=new sockets();
$reconfigure_squid=false;
	if(isset($_POST["EnableClamavInCiCap"])){
		$sock->SET_INFO("EnableClamavInCiCap",$_POST["EnableClamavInCiCap"]);
		writelogs("EnableClamavInCiCap -> `{$_POST["EnableClamavInCiCap"]}`",__FUNCTION__,__FILE__,__LINE__);
		$reconfigure_squid=true;
		
	}
	
	if(isset($_GET["CICAPListenAddress"])){
		$sock->SET_INFO("CICAPListenAddress",$_GET["CICAPListenAddress"]);
		writelogs("CICAPListenAddress -> `{$_GET["CICAPListenAddress"]}`",__FUNCTION__,__FILE__,__LINE__);
		$reconfigure_squid=true;
	
	}	
	
	if(isset($_POST["ClamavTemporaryDirectory"])){
		$sock->SET_INFO("ClamavTemporaryDirectory",$_POST["ClamavTemporaryDirectory"]);
		
	}
	
	if($reconfigure_squid){
		$sock->getFrameWork("cmd.php?squid-reconfigure=yes");
	}
	
	
	$ci=new cicap();
	while (list ($num, $line) = each ($_POST)){	
		if(preg_match('#^srv_clamav_(.+)#',$num,$re)){
			$num="srv_clamav.{$re[1]}";
		}
		
		writelogs("Save $num => $line",__FUNCTION__,__FILE__,__LINE__);
		$ci->main_array["CONF"][$num]=$line;
	}
	
	$tpl=new templates();
	$ci->Save();
	$sock->getFrameWork("cmd.php?clamd-restart=yes");
	NotifyServers();
}

function save_settings(){
	$sock=new sockets();
	if(isset($_GET["EnableClamavInCiCap"])){
		$ci=new cicap();
		if($ci->EnableClamavInCiCap<>$_GET["EnableClamavInCiCap"]){
			$sock->SET_INFO("EnableClamavInCiCap",$_GET["EnableClamavInCiCap"]);
			$reconfigure_squid=true;
			$sock->getFrameWork("cmd.php?squid-reconfigure=yes");
		}
	}
	if(isset($_GET["EnableSquidGuardInCiCAP"])){
		if($sock->GET_INFO("EnableSquidGuardInCiCAP")<>$_GET["EnableSquidGuardInCiCAP"]){
			$sock->SET_INFO("EnableSquidGuardInCiCAP",$_GET["EnableSquidGuardInCiCAP"]);
			$reconfigure_squid=true;
				
		}
	}
	
	
	if(isset($_GET["CICAPListenAddress"])){
		if($sock->GET_INFO("CICAPListenAddress")<>$_GET["CICAPListenAddress"]){
			$sock->SET_INFO("CICAPListenAddress",$_GET["CICAPListenAddress"]);
			writelogs("CICAPListenAddress -> `{$_GET["CICAPListenAddress"]}`",__FUNCTION__,__FILE__,__LINE__);
			$reconfigure_squid=true;
		}
	
	}
	
	if($reconfigure_squid){
		$sock->getFrameWork("cmd.php?squid-reconfigure=yes");
		
	}
	
	
	$ci=new cicap();
	while (list ($num, $line) = each ($_GET)){	
		if(preg_match('#^srv_clamav_(.+)#',$num,$re)){
			$num="srv_clamav.{$re[1]}";
		}
		
		writelogs("Save $num => $line",__FUNCTION__,__FILE__,__LINE__);
		$ci->main_array["CONF"][$num]=$line;
	}
	
	$tpl=new templates();
	$ci->Save();
	
	$sock->getFrameWork("cmd.php?clamd-restart=yes");
	NotifyServers();
}

function events_table(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?cicap-events=yes");
	$rows=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cicap.events"));
	@krsort($rows);
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($rows);
	$data['rows'] = array();
	$c=0;	
	while (list ($num, $line) = each ($rows)){	
		$line=trim($line);
		$color="black";
		$line=str_replace("#012", "", $line);
		$c++;
		if(preg_match("#No Profile configured#", $line)){continue;}
		if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?:(.+)#" , $line,$re)){
			$date="{$re[1]} {$re[2]} {$re[3]}";
			$line=trim($re[4]);
		}
		if(substr($line, 0,1)==":"){$line=substr($line, 1,strlen($line));}
		$md5=md5("$date$line");
		$line=htmlentities($line);
		if(preg_match("#(crashing|failed|No such|FATAL|abnormally|WARNING|refused|The line is)#i", $line)){$color="#CC0A0A";}
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:13.5px;color:$color'>$date</span>",
			"<span style='font-size:13.5px;color:$color'>$line</span>",		 
	
		)
		);
	}	
	
	if($c==0){json_error_show("no data");}
	$data['total'] =$c;
	echo json_encode($data);
}

function CicapEnabled(){
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$q->CheckTablesICAP();
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}
	if($_POST["CicapEnabled"]==1){
		if($EnableClamavInCiCap==1){
			$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=1 WHERE ID=1");
			$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=2 WHERE ID=2");
		}
	}else{
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");
		
	}
	
	
	
	$sock->SET_INFO("CicapEnabled",$_POST["CicapEnabled"]);
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
//
	
?>	