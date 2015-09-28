<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.accesslogs.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["parameters"])){page();exit;}
if(isset($_POST["LogRotateCompress"])){settings_save();exit;}
if(isset($_POST["BackupSquidLogsUseNas"])){remote_nas_save();exit;}
if(isset($_GET["BackupLogsMaxStoragePercent-info"])){BackupLogsMaxStoragePercent_info();exit;}
tabs();


function BackupLogsMaxStoragePercent_info(){
	
	$sock=new sockets();
	$tpl=new templates();
	$data=$sock->getFrameWork("system.php?BackupLogsMaxStoragePercent-info=yes");

	$DIRPART_INFO=unserialize(base64_decode($data));
	
	$percent=$_GET["BackupLogsMaxStoragePercent-info"]/100;
	$TOTAL_PART=$DIRPART_INFO["TOT"]/1024;
	$CURSIZE=$DIRPART_INFO["CURSIZE"];
	
	$TOTAL_PART_SIZE=FormatBytes($TOTAL_PART);
	$finalsize=FormatBytes($TOTAL_PART*$percent);
	$CURSIZE_TEXT=FormatBytes($CURSIZE/1024);
	$line="<div style='font-size:18px'><strong>{backup_folder} ({size}):$TOTAL_PART_SIZE {used}:$CURSIZE_TEXT/$finalsize</div>";
	echo $tpl->_ENGINE_parse_body($line);
	
}



function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	
	$array["parameters"]="{parameters}";
	$array["storage"]='{storage}';

	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){

		if($num=="storage"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.storage.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes\"><span >$ligne</span></a></li>\n";
			
	}
	$t=time();
	echo build_artica_tabs($tab, "main_artica_squidaccesslogs",1100);
		
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$AutoFSEnabled=1;
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	
	if($SquidPerformance>2){$AutoFSEnabled=0;}
	if($EnableIntelCeleron==1){$AutoFSEnabled=0;}
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$LogRotateH=$sock->GET_INFO("LogRotateH");
	$LogRotateM=$sock->GET_INFO("LogRotateM");
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	
	
	
	$t=time();
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	$SquidLogRotateFreq=intval($sock->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));

	$BackupLogsMaxStoragePercent=intval($sock->GET_INFO("BackupLogsMaxStoragePercent"));
	
	if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
	$SquidRotateAutomount=intval($sock->GET_INFO("SquidRotateAutomount"));
	$SquidRotateClean=intval($sock->GET_INFO("SquidRotateClean"));
	$SquidRotateAutomountRes=$sock->GET_INFO("SquidRotateAutomountRes");
	$SquidRotateAutomountFolder=$sock->GET_INFO("SquidRotateAutomountFolder");
	
	$AUTOFSR[null]="{select}";
	if($AutoFSEnabled==1){
		$autofs=new autofs();
		$hashZ=$autofs->automounts_Browse();
		if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
		while (list ($localmount, $array) = each ($hashZ) ){$AUTOFSR[$localmount]="{$localmount}";}
	}

	
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
	
	
	for($i=0;$i<24;$i++){
		$H=$i;
		if($i<10){$H="0$i";}
		$Hours[$i]=$H;
	}
	
	for($i=0;$i<60;$i++){
		$M=$i;
		if($i<10){$M="0$i";}
		$Mins[$i]=$M;
	}	
	
	
	
	
	
	$html="
<div style='width:100%;font-size:42px;margin-bottom:20px'>{legal_logs}: {log_retention}</div>
<div style='width:98%'  class=form>	
<div style='text-align:right;font-size:22px;text-align:right;text-decoration:underline;margin-top:20px'>
		". button("{squid_logs_urgency_section}","Loadjs('system.log.emergency.php')",20)."
					
				</div>			
			
<table style='width:100%'>
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{only_by_schedule}","{only_by_schedule_squid_rotation_explain}").":</td>
		<td style='font-size:16px'>". Field_checkbox_design("SquidRotateOnlySchedule-$t",1,$SquidRotateOnlySchedule,"SquidRotateOnlyScheduleCheck()")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{schedule}:</td>
		<td style='font-size:16px' colspan=2>
				<table style='width:135px'>
				<tr>
					<td style='font-size:22px'>".Field_array_Hash($Hours, "LogRotateH-$t",$LogRotateH,"style:font-size:22px")."</td>
					<td style='font-size:22px'>:</td>
					<td style='font-size:22px'>".Field_array_Hash($Mins, "LogRotateM-$t",$LogRotateM,"style:font-size:22px")."</td>
				</tr>
				</table>
		</td>
	</tr>				
							
	<tr>
		<td class=legend style='font-size:22px'>{export_logs_each}:</td>
		<td style='font-size:16px'>". Field_array_Hash($freq, "SquidLogRotateFreq-$t",$SquidLogRotateFreq,"style:font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{export_log_if_size_exceed}:</td>
		<td style='font-size:22px'>". Field_text("LogsRotateDefaultSizeRotation-$t",$LogsRotateDefaultSizeRotation,"font-size:22px;width:110px")."&nbsp;Mo</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{temporay_storage_path}","{temporay_storage_path_explain}").":</td>
		<td>". Field_text("LogRotatePath",$LogRotatePath,"font-size:22px;width:420px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=LogRotatePath')",18)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{backup_folder}","{BackupMaxDaysDir_explain}").":</td>
		<td style='font-size:22px;'>". Field_text("BackupMaxDaysDir",$BackupMaxDaysDir,"font-size:22px;width:420px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=BackupMaxDaysDir')",18)."</td>
	</tr>
	<tr><td colspan=3>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{automount_ressource}","{automount_ressource_explain}").":</td>
		<td style='font-size:16px'>". Field_checkbox_design("SquidRotateAutomount-$t",1,
					$SquidRotateAutomount,"SquidRotateAutomountCheck()")."</td>
		<td>&nbsp;</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>{resource}:</td>
		<td style='font-size:16px'>". Field_array_Hash($AUTOFSR, "SquidRotateAutomountRes-$t",
					$SquidRotateAutomountRes,"style:font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{directory}","{SquidRotateAutomountFolder}").":</td>
		<td style='font-size:22px;'>". Field_text("SquidRotateAutomountFolder-$t",$SquidRotateAutomountFolder,"font-size:22px;width:420px")."</td>
		<td></td>
	</tr>
	<tr><td colspan=3>&nbsp;</td></tr>						
				
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{clean_old_files}","{clean_old_files_accesslog_explain}").":</td>
		<td style='font-size:16px'>". Field_checkbox_design("SquidRotateClean-$t",1,$SquidRotateClean,"SquidRotateCleanCheck()")."</td>
		<td>&nbsp;</td>
	</tr>					
				
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{max_storage_days}","{max_storage_days_log_explain}").":</td>
		<td style='font-size:22px;'>". Field_text("BackupMaxDays",$BackupMaxDays,"font-size:22px;width:90px")."&nbsp;{days}</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{max_percent_storage}","{BackupLogsMaxStoragePercent_explain}").":</td>
		<td style='font-size:22px;'>". Field_text("BackupLogsMaxStoragePercent",$BackupLogsMaxStoragePercent,
				"font-size:22px;width:90px",null,"BackupLogsMaxStoragePercent_infos()",null,false,"BackupLogsMaxStoragePercent_infos()")."&nbsp;%</td>
		<td><span id='BackupLogsMaxStoragePercent-info'></span></td>
	</tr>
	<tr>
		<td colspan=3 align=right style='font-size:42px'>". button("{export_logs_now}","Loadjs('squid.rotate.progress.php')",42)."&nbsp;&nbsp;|&nbsp;&nbsp;".button("{apply}", "SaveRotateOptions$t()",42)."</td>
	</tr>
	</table>
</div>
<script>
	
var x_SaveSettsLogRotate$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	CacheOff();
}

function SquidRotateOnlyScheduleCheck(){
	document.getElementById('LogRotateH-$t').disabled=true;
	document.getElementById('LogRotateM-$t').disabled=true;
	document.getElementById('SquidLogRotateFreq-$t').disabled=false;
	if(!document.getElementById('SquidRotateOnlySchedule-$t').checked){return;}
	document.getElementById('LogRotateH-$t').disabled=false;
	document.getElementById('LogRotateM-$t').disabled=false;
	document.getElementById('SquidLogRotateFreq-$t').disabled=true;
}

function SquidRotateCleanCheck(){
	document.getElementById('BackupMaxDays').disabled=true;
	document.getElementById('BackupLogsMaxStoragePercent').disabled=true;
	if(document.getElementById('SquidRotateClean-$t').checked){
		document.getElementById('BackupMaxDays').disabled=false;
		document.getElementById('BackupLogsMaxStoragePercent').disabled=false;	
	}
}

function SquidRotateAutomountCheck(){
	var AutoFSEnabled=$AutoFSEnabled;
	document.getElementById('SquidRotateAutomount-$t').disabled=true;
	document.getElementById('SquidRotateAutomountRes-$t').disabled=true;
	document.getElementById('SquidRotateAutomountFolder-$t').disabled=true;
	
	
	
	if(AutoFSEnabled==0){return;}
	document.getElementById('SquidRotateAutomount-$t').disabled=false;	
	if(document.getElementById('SquidRotateAutomount-$t').checked){
		document.getElementById('SquidRotateAutomountRes-$t').disabled=false;
		document.getElementById('SquidRotateAutomountFolder-$t').disabled=false;
		document.getElementById('BackupMaxDaysDir').disabled=true;
	}
}

	
function SaveRotateOptions$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LogRotateCompress',1);
	XHR.appendData('LogRotateMysql',0);
	
	XHR.appendData('BackupLogsMaxStoragePercent',document.getElementById('BackupLogsMaxStoragePercent').value);
	
	XHR.appendData('LogRotatePath',document.getElementById('LogRotatePath').value);
	XHR.appendData('SquidLogRotateFreq',document.getElementById('SquidLogRotateFreq-$t').value);
	XHR.appendData('SquidRotateAutomountRes',document.getElementById('SquidRotateAutomountRes-$t').value);
	XHR.appendData('SquidRotateAutomountFolder',document.getElementById('SquidRotateAutomountFolder-$t').value);
	XHR.appendData('LogsRotateDefaultSizeRotation',document.getElementById('LogsRotateDefaultSizeRotation-$t').value);
	XHR.appendData('BackupMaxDays',document.getElementById('BackupMaxDays').value);
	XHR.appendData('BackupMaxDaysDir',document.getElementById('BackupMaxDaysDir').value);
	XHR.appendData('LogRotateH',document.getElementById('LogRotateH-$t').value);
	XHR.appendData('LogRotateM',document.getElementById('LogRotateM-$t').value);
	if(document.getElementById('SquidRotateOnlySchedule-$t').checked){ XHR.appendData('SquidRotateOnlySchedule',1); }else{ XHR.appendData('SquidRotateOnlySchedule',0); }
	if(document.getElementById('SquidRotateClean-$t').checked){ XHR.appendData('SquidRotateClean',1); }else{ XHR.appendData('SquidRotateClean',0); }
	if(document.getElementById('SquidRotateAutomount-$t').checked){ XHR.appendData('SquidRotateAutomount',1); }else{ XHR.appendData('SquidRotateAutomount',0); }	
	
	XHR.sendAndLoad('$page', 'POST',x_SaveSettsLogRotate$t);
}
	
var x_SaveSettsLogRotate$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}

function BackupLogsMaxStoragePercent_infos(){
	BackupLogsMaxStoragePercent=document.getElementById('BackupLogsMaxStoragePercent').value;
	LoadAjaxSilent('BackupLogsMaxStoragePercent-info','$page?BackupLogsMaxStoragePercent-info='+BackupLogsMaxStoragePercent);

}
	
SquidRotateOnlyScheduleCheck();
SquidRotateCleanCheck();
SquidRotateAutomountCheck();
BackupLogsMaxStoragePercent_infos();


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
		$value=url_decode_special_tool($value);
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
