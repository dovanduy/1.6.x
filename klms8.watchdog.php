<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableWatchdog"])){save();exit;}
js();	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{watchdog}");
	$html="YahooWin3('450','$page?popup=yes','$title')";
	echo $html;
	
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->MONIT_INSTALLED){
		echo $tpl->_ENGINE_parse_body("
		<center>
		<table style='width:99%;margin:30px' class=form>
		<tr>
			<td width=1%><img src='img/error-128.png'></td>
			<td style='font-size:18px;color:#CA2A2A'>{APP_MONIT_NOT_INSTALLED}</td>
		</tr>
		</table>
		</center>
		");
		return ;
		
	}
	$t=time();
	$config=unserialize(base64_decode($sock->GET_INFO("klms8Watchdog")));
	$SystemWatchMemoryUsage=$config["SystemWatchMemoryUsage"];
	$SystemWatchCPUUser=$config["SystemWatchCPUUser"];
	$EnableWatchCPUsage=$config["EnableWatchCPUsage"];
	$EnableWatchMemoryUsage=$config["EnableWatchMemoryUsage"];
	$EnableWatchdog=$config["EnableWatchdog"];
	$SystemWatchCPUSystem=$config["SystemWatchCPUSystem"];
	if(!is_numeric($SystemWatchMemoryUsage)){$SystemWatchMemoryUsage=350;}
	if(!is_numeric($SystemWatchCPUUser)){$SystemWatchCPUUser=80;}
	if(!is_numeric($SystemWatchCPUSystem)){$SystemWatchCPUSystem=80;}
	if(!is_numeric($EnableWatchdog)){$EnableWatchdog=1;}
	if(!is_numeric($EnableWatchMemoryUsage)){$EnableWatchMemoryUsage=1;}
	if(!is_numeric($EnableWatchCPUsage)){$EnableWatchCPUsage=1;}
		
	$html="
	<div id='$t'></div>
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{EnableWatchdog}:</td>
		<td>". Field_checkbox("EnableWatchdog-$t",1,$EnableWatchdog,"EnableWatchMemoryUsageCheck$t()")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:14px'>{EnableWatchMemoryUsage}:</td>
		<td>". Field_checkbox("EnableWatchMemoryUsage-$t",1,$EnableWatchMemoryUsage,"EnableWatchMemoryUsageCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{memory}:</td>
		<td style='font-size:14px'>". Field_text("SystemWatchMemoryUsage-$t",$SystemWatchMemoryUsage,"font-size:14px;width:90px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{EnableWatchCPUsage}:</td>
		<td>". Field_checkbox("EnableWatchCPUsage-$t",1,$EnableWatchCPUsage,"EnableWatchMemoryUsageCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{CpuUsage}:</td>
		<td style='font-size:14px'>". Field_text("SystemWatchCPUSystem-$t",$SystemWatchCPUSystem,"font-size:14px;width:60px")."&nbsp;%</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{apply}","SaveWatchdogconfig$t()",16)."</td>
	</tr>	
	</table>
	<script>
		function EnableWatchMemoryUsageCheck$t(){
			
			document.getElementById('SystemWatchMemoryUsage-$t').disabled=true;
			document.getElementById('EnableWatchMemoryUsage-$t').disabled=true;
			document.getElementById('SystemWatchCPUSystem-$t').disabled=true;
			document.getElementById('EnableWatchCPUsage-$t').disabled=true;
			
			if(!document.getElementById('EnableWatchdog-$t').checked){return;}
			document.getElementById('EnableWatchCPUsage-$t').disabled=false;
			document.getElementById('EnableWatchMemoryUsage-$t').disabled=false;
			
			if(document.getElementById('EnableWatchMemoryUsage-$t').checked){document.getElementById('SystemWatchMemoryUsage-$t').disabled=false;}
			if(document.getElementById('EnableWatchCPUsage-$t').checked){document.getElementById('SystemWatchCPUSystem-$t').disabled=false;}
				
		}
		
	var x_SaveWatchdogconfig$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('$t').innerHTML='';
		YahooWin3Hide();
		RefreshTab('main_klms_tabs');
	 }			
		
		function SaveWatchdogconfig$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SystemWatchMemoryUsage',document.getElementById('SystemWatchMemoryUsage-$t').value);
			XHR.appendData('SystemWatchCPUSystem',document.getElementById('SystemWatchCPUSystem-$t').value);
			if(document.getElementById('EnableWatchdog-$t').checked){XHR.appendData('EnableWatchdog',1);}else{XHR.appendData('EnableWatchdog',0);}
			if(document.getElementById('EnableWatchMemoryUsage-$t').checked){XHR.appendData('EnableWatchMemoryUsage',1);}else{XHR.appendData('EnableWatchMemoryUsage',0);}
			if(document.getElementById('EnableWatchCPUsage-$t').checked){XHR.appendData('EnableWatchCPUsage',1);}else{XHR.appendData('EnableWatchCPUsage',0);}	
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveWatchdogconfig$t);	
		}
	
	EnableWatchMemoryUsageCheck$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "klms8Watchdog");
	$sock->getFrameWork("klms.php?watchdog=yes");
	
}

