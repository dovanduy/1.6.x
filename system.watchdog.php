<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.syslogs.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$users->AsAnAdministratorGeneric){die("Not autorized");}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["Load1mn"])){Save();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{system_watchdog}");
	$html="YahooWin4(550,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$t=time();
	$EnableLoadAvg1mnUser=$sock->GET_INFO("EnableLoadAvg1mnUser");
	
	$cpunum=$users->CPU_NUMBER;
	$normal=($cpunum*2)+1;
	$normal2=$cpunum*2;
	$busy=$cpunum*4;    
	
	
	$EnableLoadAvg5mnUser=$sock->GET_INFO("EnableLoadAvg5mnUser");
	$EnableLoadAvg15mnUser=$sock->GET_INFO("EnableLoadAvg15mnUser");
	$EnableWatchMemoryUsage=$sock->GET_INFO("EnableWatchMemoryUsage");
	$EnableWatchCPUsage=$sock->GET_INFO("EnableWatchCPUsage");
	$SystemWatchCPUUser=$sock->GET_INFO("SystemWatchCPUUser");
	$SystemWatchCPUSystem=$sock->GET_INFO("SystemWatchCPUSystem");
	$Load5mn==$sock->GET_INFO("Load5mn");
	$Load15mn=$sock->GET_INFO("Load15mn");
	$Load1mn=$sock->GET_INFO("Load1mn");
	$SystemWatchMemoryUsage=$sock->GET_INFO("SystemWatchMemoryUsage");
	if(!is_numeric($SystemWatchMemoryUsage)){$SystemWatchMemoryUsage=75;}
	
	if(!is_numeric($SystemWatchCPUUser)){$SystemWatchCPUUser=80;}
	if(!is_numeric($SystemWatchCPUSystem)){$SystemWatchCPUSystem=80;}
	
	if(!is_numeric($EnableLoadAvg5mnUser)){$EnableLoadAvg5mnUser=1;}
	if(!is_numeric($EnableLoadAvg15mnUser)){$EnableLoadAvg15mnUser=1;}
	if(!is_numeric($EnableLoadAvg1mnUser)){$EnableLoadAvg1mnUser=1;}
	if(!is_numeric($EnableWatchMemoryUsage)){$EnableWatchMemoryUsage=1;}
	if(!is_numeric($EnableWatchCPUsage)){$EnableWatchCPUsage=1;}
	if(!is_numeric($Load5mn)){$Load5mn=$normal;}
	if(!is_numeric($Load15mn)){$Load15mn=$normal2;}
	if(!is_numeric($Load1mn)){$Load1mn=$busy;}
	
	$html="
	<div id='$t'>
	<table style='width:95%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{EnableLoadAvgUser} (1mn):</td>
		<td>". Field_checkbox("EnableLoadAvg1mnUser",1,$EnableLoadAvg1mnUser,"EnableLoadAvgcheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{load} (1mn):</td>
		<td>". Field_text("Load1mn",$Load1mn,"font-size:14px;width:60px")."</td>
	</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:14px'>{EnableLoadAvgUser} (5mn):</td>
		<td>". Field_checkbox("EnableLoadAvg5mnUser",1,$EnableLoadAvg5mnUser,"EnableLoadAvgcheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{load} (5mn):</td>
		<td>". Field_text("Load5mn",$Load5mn,"font-size:14px;width:60px")."</td>
	</tr>
		<tr><td colspan=2>&nbsp;</td></tr>	
	<tr>
		<td class=legend style='font-size:14px'>{EnableLoadAvgUser} (15mn):</td>
		<td>". Field_checkbox("EnableLoadAvg15mnUser",1,$EnableLoadAvg15mnUser,"EnableLoadAvgcheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{load} (15mn):</td>
		<td>". Field_text("Load15mn",$Load15mn,"font-size:14px;width:60px")."</td>
	</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:14px'>{EnableWatchMemoryUsage}:</td>
		<td>". Field_checkbox("EnableWatchMemoryUsage",1,$EnableWatchMemoryUsage,"EnableLoadAvgcheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{memory}:</td>
		<td style='font-size:14px'>". Field_text("SystemWatchMemoryUsage",$SystemWatchMemoryUsage,"font-size:14px;width:60px")."&nbsp;%</td>
	</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:14px'>{EnableWatchCPUsage}:</td>
		<td>". Field_checkbox("EnableWatchCPUsage",1,$EnableWatchCPUsage,"EnableLoadAvgcheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{CpuUsage} - user:</td>
		<td style='font-size:14px'>". Field_text("SystemWatchCPUUser",$SystemWatchCPUUser,"font-size:14px;width:60px")."&nbsp;%</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{CpuUsage} - system:</td>
		<td style='font-size:14px'>". Field_text("SystemWatchCPUSystem",$SystemWatchCPUSystem,"font-size:14px;width:60px")."&nbsp;%</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{apply}","SaveWatchdogconfig()",16)."</td>
	</tr>	
	</table>
	</div>
	<script>
		function EnableLoadAvgcheck(){
			document.getElementById('Load1mn').disabled=true;
			document.getElementById('Load5mn').disabled=true;
			document.getElementById('Load15mn').disabled=true;
			document.getElementById('SystemWatchMemoryUsage').disabled=true;
			document.getElementById('SystemWatchCPUUser').disabled=true;
			document.getElementById('SystemWatchCPUSystem').disabled=true;
			
			if(document.getElementById('EnableLoadAvg1mnUser').checked){document.getElementById('Load1mn').disabled=false;}
			if(document.getElementById('EnableLoadAvg5mnUser').checked){document.getElementById('Load5mn').disabled=false;}
			if(document.getElementById('EnableLoadAvg15mnUser').checked){document.getElementById('Load15mn').disabled=false;}
			if(document.getElementById('EnableWatchMemoryUsage').checked){document.getElementById('SystemWatchMemoryUsage').disabled=false;}
			if(document.getElementById('EnableWatchCPUsage').checked){
				document.getElementById('SystemWatchCPUUser').disabled=false;
				document.getElementById('SystemWatchCPUSystem').disabled=false;
			}
				
		}
		
	var x_SaveWatchdogconfig= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin4Hide();
	 }			
		
		function SaveWatchdogconfig(){
			var XHR = new XHRConnection();
			XHR.appendData('Load1mn',document.getElementById('Load1mn').value);
			XHR.appendData('Load5mn',document.getElementById('Load5mn').value);
			XHR.appendData('Load15mn',document.getElementById('Load15mn').value);
			XHR.appendData('SystemWatchMemoryUsage',document.getElementById('SystemWatchMemoryUsage').value);
			XHR.appendData('SystemWatchCPUUser',document.getElementById('SystemWatchCPUUser').value);
			XHR.appendData('SystemWatchCPUSystem',document.getElementById('SystemWatchCPUSystem').value);
			
			if(document.getElementById('EnableLoadAvg1mnUser').checked){
				XHR.appendData('EnableLoadAvg1mnUser',1);}else{XHR.appendData('EnableLoadAvg1mnUser',0);}
				
			if(document.getElementById('EnableLoadAvg5mnUser').checked){
				XHR.appendData('EnableLoadAvg5mnUser',1);}else{XHR.appendData('EnableLoadAvg5mnUser',0);}

			if(document.getElementById('EnableLoadAvg15mnUser').checked){
				XHR.appendData('EnableLoadAvg15mnUser',1);}else{XHR.appendData('EnableLoadAvg15mnUser',0);}	

			if(document.getElementById('EnableWatchMemoryUsage').checked){
				XHR.appendData('EnableWatchMemoryUsage',1);}else{XHR.appendData('EnableWatchMemoryUsage',0);}

			if(document.getElementById('EnableWatchCPUsage').checked){
				XHR.appendData('EnableWatchCPUsage',1);}else{XHR.appendData('EnableWatchCPUsage',0);}	

			AnimateDiv('$t');
				
			XHR.sendAndLoad('$page', 'POST',x_SaveWatchdogconfig);	
		}
	
	EnableLoadAvgcheck();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	$sock->getFrameWork("services.php?restart-monit=yes");
}