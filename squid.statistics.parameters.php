<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_POST["ArticaProxyStatisticsBackHourTables"])){Save();exit;}
	
	
page();


function page(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$CORP_LICENSE=1;
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	$ArticaProxyStatisticsBackHourTables=$sock->GET_INFO("ArticaProxyStatisticsBackHourTables");
	if(!is_numeric($ArticaProxyStatisticsBackHourTables)){$ArticaProxyStatisticsBackHourTables=1;}
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	$BackupSquidStatsUseNas=$sock->GET_INFO("BackupSquidStatsUseNas");
	$BackupSquidStatsNASIpaddr=$sock->GET_INFO("BackupSquidStatsNASIpaddr");
	$BackupSquidStatsNASFolder=$sock->GET_INFO("BackupSquidStatsNASFolder");
	$BackupSquidStatsNASUser=$sock->GET_INFO("BackupSquidStatsNASUser");
	$BackupSquidStatsNASPassword=$sock->GET_INFO("BackupSquidStatsNASPassword");
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if(!$users->CORP_LICENSE){$CORP_LICENSE=0;$ArticaProxyStatisticsBackupDays=5;}	
	
	$ArticaProxyStatisticsMaxTime=$sock->GET_INFO("ArticaProxyStatisticsMaxTime");
	if(!is_numeric($ArticaProxyStatisticsMaxTime)){$ArticaProxyStatisticsMaxTime=420;}
	if($ArticaProxyStatisticsMaxTime<5){$ArticaProxyStatisticsMaxTime=420;}
	$t=time();
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{max_days}:</td>
		<td>". Field_text("ArticaProxyStatisticsBackupDays-$t",$ArticaProxyStatisticsBackupDays,"font-size:18px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{max_execution_time}:</td>
		<td style='font-size:18px'>". Field_text("ArticaProxyStatisticsMaxTime-$t",$ArticaProxyStatisticsMaxTime,"font-size:18px;width:90px")."&nbsp;{minutes}</td>
		<td>&nbsp;</td>
	</tr>
				
				
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{backup_hourly_tables}:</td>
		<td>". Field_checkbox("ArticaProxyStatisticsBackHourTables-$t",1,$ArticaProxyStatisticsBackHourTables)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{backup_folder}:</td>
		<td>". Field_text("ArticaProxyStatisticsBackupFolder-$t",$ArticaProxyStatisticsBackupFolder,"font-size:18px;width:320px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{use_remote_nas}:</td>
		<td>". Field_checkbox("BackupSquidStatsUseNas-$t", 1,$BackupSquidStatsUseNas,"NasCheck$t()")."</td>
		<td>". help_icon("{BackupSquidLogsUseNas_explain}")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>". Field_text("BackupSquidStatsNASIpaddr-$t",$BackupSquidStatsNASIpaddr,"font-size:18px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{shared_folder}:</td>
		<td>". Field_text("BackupSquidStatsNASFolder-$t",$BackupSquidStatsNASFolder,"font-size:18px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{username}:</td>
		<td>". Field_text("BackupSquidStatsNASUser-$t",$BackupSquidStatsNASUser,"font-size:18px;width:150px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>". Field_password("BackupSquidStatsNASPassword-$t",$BackupSquidStatsNASPassword,"font-size:14px;width:150px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",22)."</td>
	</tr>
	</table>
	</div>
<script>
var x_Save$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	UnlockPage();
}

function Save$t(){
	var LICENSE=$CORP_LICENSE;
	var XHR = new XHRConnection();	
	if(document.getElementById('ArticaProxyStatisticsBackHourTables-$t').checked){ XHR.appendData('ArticaProxyStatisticsBackHourTables',1); }else{ XHR.appendData('ArticaProxyStatisticsBackHourTables',0); }
	if(document.getElementById('BackupSquidStatsUseNas-$t').checked){ XHR.appendData('BackupSquidStatsUseNas',1); }else{ XHR.appendData('BackupSquidStatsUseNas',0); }			
	XHR.appendData('ArticaProxyStatisticsBackupFolder',document.getElementById('ArticaProxyStatisticsBackupFolder-$t').value);
	if(LICENSE==1){ XHR.appendData('ArticaProxyStatisticsBackupDays',document.getElementById('ArticaProxyStatisticsBackupDays-$t').value); }
	XHR.appendData('BackupSquidStatsNASIpaddr',document.getElementById('BackupSquidStatsNASIpaddr-$t').value);
	XHR.appendData('BackupSquidStatsNASFolder',encodeURIComponent(document.getElementById('BackupSquidStatsNASFolder-$t').value));
	XHR.appendData('BackupSquidStatsNASUser',encodeURIComponent(document.getElementById('BackupSquidStatsNASUser-$t').value));
	XHR.appendData('BackupSquidStatsNASPassword',encodeURIComponent(document.getElementById('BackupSquidStatsNASPassword-$t').value));
	XHR.appendData('ArticaProxyStatisticsMaxTime',document.getElementById('ArticaProxyStatisticsMaxTime-$t').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}

function LicenseCheck$t(){
	var LICENSE=$CORP_LICENSE;
	if(LICENSE==1){return;}
	document.getElementById('ArticaProxyStatisticsBackupDays-$t').disabled=true;
}

function NasCheck$t(){
	if(document.getElementById('BackupSquidStatsUseNas-$t').checked){
		document.getElementById('BackupSquidStatsNASIpaddr-$t').disabled=false;
		document.getElementById('BackupSquidStatsNASFolder-$t').disabled=false;
		document.getElementById('BackupSquidStatsNASUser-$t').disabled=false;
		document.getElementById('BackupSquidStatsNASPassword-$t').disabled=false;
		return;
	}
	
	document.getElementById('BackupSquidStatsNASIpaddr-$t').disabled=true;
	document.getElementById('BackupSquidStatsNASFolder-$t').disabled=true;
	document.getElementById('BackupSquidStatsNASUser-$t').disabled=true;
	document.getElementById('BackupSquidStatsNASPassword-$t').disabled=true;
}

NasCheck$t();
LicenseCheck$t();
</script>				
				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("ArticaProxyStatisticsBackHourTables", $_POST["ArticaProxyStatisticsBackHourTables"]);
	$sock->SET_INFO("ArticaProxyStatisticsMaxTime", $_POST["ArticaProxyStatisticsMaxTime"]);
	
	
	
	$sock->SET_INFO("BackupSquidStatsUseNas", $_POST["BackupSquidStatsUseNas"]);
	$sock->SET_INFO("ArticaProxyStatisticsBackupFolder", $_POST["ArticaProxyStatisticsBackupFolder"]);
	if(isset($_POST["ArticaProxyStatisticsBackupDays"])){$sock->SET_INFO("ArticaProxyStatisticsBackupDays", $_POST["ArticaProxyStatisticsBackupDays"]); }
	$sock->SET_INFO("BackupSquidStatsNASIpaddr", url_decode_special_tool($_POST["BackupSquidStatsNASIpaddr"]));
	$sock->SET_INFO("BackupSquidStatsNASFolder",url_decode_special_tool($_POST["BackupSquidStatsNASFolder"]));
	$sock->SET_INFO("BackupSquidStatsNASUser", url_decode_special_tool($_POST["BackupSquidStatsNASUser"]));
	$sock->SET_INFO("BackupSquidStatsNASPassword", url_decode_special_tool($_POST["BackupSquidStatsNASPassword"]));
}
