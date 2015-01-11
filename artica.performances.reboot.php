<?php
$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){die();}

if(isset($_POST["NoDryReboot"])){save();exit;}
page();


function page(){
$tpl=new templates();
$page=CurrentPageName();	
$sock=new sockets();
$NoDryReboot=$sock->GET_INFO("NoDryReboot");
$NoOutOfMemoryReboot=$sock->GET_INFO("NoOutOfMemoryReboot");
if(!is_numeric($NoOutOfMemoryReboot)){$NoOutOfMemoryReboot=0;}
if(!is_numeric($NoDryReboot)){$NoDryReboot=0;}
$AutoRebootSchedule=$sock->GET_INFO("AutoRebootSchedule");
$AutoRebootScheduleText=trim($sock->GET_INFO("AutoRebootScheduleText"));
if(!is_numeric($AutoRebootSchedule)){$AutoRebootSchedule=0;}
$DisableForceFCK=intval($sock->GET_INFO("DisableForceFCK"));
$t=$_GET["t"];

$html="		

	<input type='hidden' id='AutoRebootScheduleText' value='$AutoRebootScheduleText'>	
	<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td colspan=3><div style='font-size:28px;margin-bottom:20px'>{reboot}</td>
			</tr>
			<tr>
				<td nowrap width=1% align='right' class=legend>{NoDryReboot}:</td>
				<td>" . Field_checkbox("NoDryReboot",1,$NoDryReboot)."</td>
				<td>" . help_icon("{NoDryReboot_explain}")."</td>
			</tr>
			<tr>
				<td nowrap width=1% align='right' class=legend>{DisableForceFCK}:</td>
				<td>" . Field_checkbox("DisableForceFCK",1,$DisableForceFCK)."</td>
				<td>" . help_icon("{DisableForceFCK_explain}")."</td>
			</tr>						
			<tr>
				<td nowrap width=1% align='right' class=legend>{NoOutOfMemoryReboot}:</td>
				<td>" . Field_checkbox("NoOutOfMemoryReboot",1,$NoOutOfMemoryReboot)."</td>
				<td>" . help_icon("{NoOutOfMemoryReboot_explain}")."</td>
			</tr>
			<tr>
				<td nowrap width=1% align='right' class=legend>{scheduled_reboot}:</td>
				<td>" . Field_checkbox("AutoRebootSchedule",1,$AutoRebootSchedule,'CheckRebootSchedule()')."</td>
				<td align=left><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('cron.php?field=AutoRebootScheduleText')\" style='font-size:13px;text-decoration:underline;color:black' id='scheduleAID2'>{schedule}</a></td>
			</tr>
		</tr>		
			<td colspan=3 align='right'><hr>". button("{apply}","SavePerformancesReboot$t()",26)."</td>
		</tr>			
		</tbody>							
		</table>
<script>

	var x_SavePerformancesReboot$t=function (obj) {
		LoadRebootSection();
	}	


	  function SavePerformancesReboot$t(){
	  	var XHR = new XHRConnection();
	  	
	  	if(document.getElementById('DisableForceFCK').checked){XHR.appendData('DisableForceFCK',1);}else{XHR.appendData('DisableForceFCK',0);}
	  	if(document.getElementById('NoDryReboot').checked){XHR.appendData('NoDryReboot',1);}else{XHR.appendData('NoDryReboot',0);}
	  	if(document.getElementById('NoOutOfMemoryReboot').checked){XHR.appendData('NoOutOfMemoryReboot',1);}else{XHR.appendData('NoOutOfMemoryReboot',0);}
	  	if(document.getElementById('AutoRebootSchedule').checked){XHR.appendData('AutoRebootSchedule',1);}else{XHR.appendData('AutoRebootSchedule',0);}
	  	XHR.appendData('AutoRebootScheduleText',document.getElementById('AutoRebootScheduleText').value);
	  	AnimateDiv('$t-reboot');
	  	XHR.sendAndLoad('$page', 'POST',x_SavePerformancesReboot$t);
	  }




		function CheckRebootSchedule(){
			if(!document.getElementById('AutoRebootSchedule').checked){
				document.getElementById('scheduleAID2').style.color='#CCCCCC';
			}else{
				document.getElementById('scheduleAID2').style.color='black';
			}
		
		}
		
		CheckRebootSchedule();
</script>		
";
echo $tpl->_ENGINE_parse_body($html);	
	
}


function save(){
	$sock=new sockets();
	if(isset($_POST["NoDryReboot"])){$sock->SET_INFO('NoDryReboot',$_POST["NoDryReboot"]);}	
	if(isset($_POST["DisableForceFCK"])){$sock->SET_INFO('DisableForceFCK',$_POST["DisableForceFCK"]);}
	
	if(isset($_POST["NoOutOfMemoryReboot"])){$sock->SET_INFO('NoOutOfMemoryReboot',$_POST["NoOutOfMemoryReboot"]);}
	if(isset($_POST["AutoRebootScheduleText"])){$sock->SET_INFO('AutoRebootScheduleText',$_POST["AutoRebootScheduleText"]);}
	if(isset($_POST["AutoRebootSchedule"])){$sock->SET_INFO('AutoRebootSchedule',$_POST["AutoRebootSchedule"]);}
	if(isset($_POST["AutoRebootSchedule"])){$sock->getFrameWork("services.php?AutoRebootSchedule=yes");}	
	
	
	
	$sock->getFrameWork("services.php?syslogger=yes");

	
}