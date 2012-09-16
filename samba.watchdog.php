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

js();


function Save(){
	$sock=new sockets();
	$final=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($final, "SambaWatchdogMonitConfig");
	$sock->getFrameWork("samba.php?watchdog-config=yes");
	
}

function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{samba_watchdog}");
	$html="YahooWin3('445','$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SambaWatchdogMonitConfig")));
	//print_r($MonitConfig);
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	$MONIT_INSTALLED=0;
	$users=new usersMenus();
	if($users->MONIT_INSTALLED){$MONIT_INSTALLED=1;}
	
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
				<td class=legend style='font-size:14px'>{notify_when_memory_exceed}:</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogMEM", $MonitConfig["watchdogMEM"],"font-size:14px;width:60px")."&nbsp;MB</td>
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
	
	}
	
	
	var x_{$t}_SaveInstance= function (obj) {
			YahooWin3Hide();
		}	
	
	function SaveWatchdog{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-watchdog').checked){XHR.appendData('watchdog',1);}else{XHR.appendData('watchdog',0);}
		XHR.appendData('watchdogMEM',document.getElementById('$t-watchdogMEM').value);
		XHR.appendData('watchdogCPU',document.getElementById('$t-watchdogCPU').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
	}	
</script>

";
	
echo $tpl->_ENGINE_parse_body($html);
	
}
