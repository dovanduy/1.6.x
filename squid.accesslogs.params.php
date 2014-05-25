<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.accesslogs.inc');
	include_once('ressources/class.tcpip.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["LogRotateCompress"])){settings_save();exit;}
if(isset($_POST["BackupSquidLogsUseNas"])){remote_nas_save();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	
	
	
	$t=time();
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	$SquidLogRotateFreq=intval($sock->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	
	$freq[10]="10mn";
	$freq[20]="20mn";
	$freq[30]="30mn";
	$freq[60]="1h";
	$freq[120]="2h";
	$freq[300]="5h";
	$freq[600]="10h";
	$freq[1440]="24h";
	$freq[2880]="48h";
	$freq[4320]="3 {days}";
	$freq[10080]="1 {week}";
	
	$html="
<div style='width:100%;font-size:22px;margin-bottom:20px'>{log_retention}</div>
	<div class=explain style='font-size:16px'>{log_retention_mysql_text}</div>	
			
			
	<div style='width:98%'  class=form>	

". Paragraphe_switch_img("{only_by_schedule}","{only_by_schedule_squid_rotation_explain}","SquidRotateOnlySchedule" ,$SquidRotateOnlySchedule,null,650)."		
			
			
			
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{rotate_logs_each}:</td>
		<td style='font-size:16px'>". Field_array_Hash($freq, "SquidLogRotateFreq-$t",$SquidLogRotateFreq,"style:font-size:18px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{delete_if_file_exceed}:</td>
		<td style='font-size:18px'>". Field_text("LogsRotateDeleteSize",$LogsRotateDeleteSize,"font-size:18px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{clean_apache_logs}:</td>
		<td style='font-size:18px'>". Field_text("LogsRotateRemoveApacheMaxSize",$LogsRotateRemoveApacheMaxSize,"font-size:18px;width:60px")."&nbsp;MB</td>
		<td>". help_icon("{LogsRotateRemoveApacheMaxSize_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{default_size_for_rotation}:</td>
		<td style='font-size:18px'>". Field_text("LogsRotateDefaultSizeRotation",$LogsRotateDefaultSizeRotation,"font-size:18px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{compress_files}:</td>
		<td>". Field_checkbox("LogRotateCompress", 1,$LogRotateCompress)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{insert_in_mysql}:</td>
		<td>". Field_checkbox("LogRotateMysql", 1,$LogRotateMysql,"LogRotateMysqlCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{storage_files_path}:</td>
		<td>". Field_text("LogRotatePath",$LogRotatePath,"font-size:18px;width:420px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=LogRotatePath')",12)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{system_logs_path}:</td>
		<td>". Field_text("SystemLogsPath",$SystemLogsPath,"font-size:18px;width:420px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=SystemLogsPath')",12)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{max_day_in_database}:</td>
		<td style='font-size:18px;'>". Field_text("BackupMaxDays",$BackupMaxDays,"font-size:18px;width:90px")."&nbsp;{days}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{backup_folder}:</td>
		<td style='font-size:18px;'>". Field_text("BackupMaxDaysDir",$BackupMaxDaysDir,"font-size:18px;width:420px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=BackupMaxDaysDir')",12)."</td>
	</tr>
	
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}", "SaveRotateOptions$t()",28)."</td>
	</tr>
	</table>
</div>
<p><hr></p>
<div style='width:100%;font-size:22px'>{NAS_storage}</div>
	<div class=explain style='font-size:16px'>{log_retention_nas_text}</div>	
	<div style='width:95%'  class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{use_remote_nas}:</td>
		<td>". Field_checkbox("BackupSquidLogsUseNas", 1,$BackupSquidLogsUseNas,"SaveBackupSquidNasCheck$t()")."</td>
		<td>". help_icon("{BackupSquidLogsUseNas_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>". Field_text("BackupSquidLogsNASIpaddr",$BackupSquidLogsNASIpaddr,"font-size:18px;width:420px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{shared_folder}:</td>
		<td>". Field_text("BackupSquidLogsNASFolder",$BackupSquidLogsNASFolder,"font-size:18px;width:420px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{username}:</td>
		<td>". Field_text("BackupSquidLogsNASUser",$BackupSquidLogsNASUser,"font-size:18px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>". Field_password("BackupSquidLogsNASPassword",$BackupSquidLogsNASPassword,"font-size:18px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}", "SaveBackupSquidNas$t()",28)."</td>
	</tr>
	<tr>
		<td colspan=3 align=right><hr>". button("{test_connection}", "Loadjs('miniadm.system.syslogstore.php?test-nas-js=yes')",18)."</td>
	</tr>	
	
	</table>
</div>

<script>
	
var x_SaveSettsLogRotate$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}
	
function LogRotateMysqlCheck$t(){
	document.getElementById('LogRotatePath').disabled=false;
	document.getElementById('BackupMaxDays').disabled=true;
	document.getElementById('BackupMaxDaysDir').disabled=true;
	if(document.getElementById('LogRotateMysql').checked){
		document.getElementById('LogRotatePath').disabled=true;
		document.getElementById('BackupMaxDays').disabled=false;
		document.getElementById('BackupMaxDaysDir').disabled=false;
	}
}
	
	
function SaveRotateOptions$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LogRotateCompress').checked){XHR.appendData('LogRotateCompress',1);}
	else{XHR.appendData('LogRotateCompress',0);}
	if(document.getElementById('LogRotateMysql').checked){XHR.appendData('LogRotateMysql',1);}
	else{XHR.appendData('LogRotateMysql',0);}
	XHR.appendData('LogRotatePath',document.getElementById('LogRotatePath').value);
	XHR.appendData('LogsRotateRemoveApacheMaxSize',document.getElementById('LogsRotateRemoveApacheMaxSize').value);
	XHR.appendData('SquidLogRotateFreq',document.getElementById('SquidLogRotateFreq-$t').value);
	XHR.appendData('LogsRotateDefaultSizeRotation',document.getElementById('LogsRotateDefaultSizeRotation').value);
	XHR.appendData('SystemLogsPath',document.getElementById('SystemLogsPath').value);
	XHR.appendData('BackupMaxDays',document.getElementById('BackupMaxDays').value);
	XHR.appendData('BackupMaxDaysDir',document.getElementById('BackupMaxDaysDir').value);
	XHR.appendData('LogsRotateDeleteSize',document.getElementById('LogsRotateDeleteSize').value);
	XHR.appendData('SquidRotateOnlySchedule',document.getElementById('SquidRotateOnlySchedule').value);
	
	XHR.sendAndLoad('$page', 'POST',x_SaveSettsLogRotate$t);
	}
	
var x_SaveSettsLogRotate$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}
	
function SaveBackupSquidNasCheck$t(){
	document.getElementById('BackupSquidLogsNASIpaddr').disabled=true;
	document.getElementById('BackupSquidLogsNASFolder').disabled=true;
	document.getElementById('BackupSquidLogsNASUser').disabled=true;
	document.getElementById('BackupSquidLogsNASPassword').disabled=true;
	if(document.getElementById('BackupSquidLogsUseNas').checked){
		document.getElementById('BackupSquidLogsNASIpaddr').disabled=false;
		document.getElementById('BackupSquidLogsNASFolder').disabled=false;
		document.getElementById('BackupSquidLogsNASUser').disabled=false;
		document.getElementById('BackupSquidLogsNASPassword').disabled=false;
	}
}
	
	
function SaveBackupSquidNas$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('BackupSquidLogsUseNas').checked){XHR.appendData('BackupSquidLogsUseNas',1);}
	else{XHR.appendData('BackupSquidLogsUseNas',0);}
	XHR.appendData('BackupSquidLogsNASIpaddr',document.getElementById('BackupSquidLogsNASIpaddr').value);
	XHR.appendData('BackupSquidLogsNASFolder',encodeURIComponent(document.getElementById('BackupSquidLogsNASFolder').value));
	XHR.appendData('BackupSquidLogsNASUser',encodeURIComponent(document.getElementById('BackupSquidLogsNASUser').value));
	XHR.appendData('BackupSquidLogsNASPassword',encodeURIComponent(document.getElementById('BackupSquidLogsNASPassword').value));
	XHR.sendAndLoad('$page', 'POST',x_SaveSettsLogRotate$t);
}
SaveBackupSquidNasCheck$t();	
LogRotateMysqlCheck$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}
function remote_nas_save(){
	$sock=new sockets();

	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupMaxDaysDir"])){$_POST["BackupMaxDaysDir"]=url_decode_special_tool($_POST["BackupMaxDaysDir"]);}
	if(isset($_POST["BackupSquidLogsNASFolder"])){$_POST["BackupSquidLogsNASFolder"]=url_decode_special_tool($_POST["BackupSquidLogsNASFolder"]);}
	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupSquidLogsNASPassword"])){$_POST["BackupSquidLogsNASPassword"]=url_decode_special_tool($_POST["BackupSquidLogsNASPassword"]);}

	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}

}
function settings_save(){
	$sock=new sockets();
	while (list ($index, $line) = each ($_POST) ){
		$sock->SET_INFO($index, $line);
	}
	$sock->getFrameWork("services.php?rotatebuild=yes");

}
