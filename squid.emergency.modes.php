<?php
if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');




if(isset($_GET["popup"])){popup();exit;}
$users=new usersMenus();
if(!$users->AsSquidAdministrator){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
}

if(isset($_POST["SquidUrgency"])){Save();exit;}



js();

function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{emergency_modes}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3('700','$page?popup=yes','$title');";

}


function popup(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	$StoreIDUrgency=intval($sock->GET_INFO("StoreIDUrgency"));
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
	$t=time();

	
	
	$SquidUrgency_admin="<div style='font-size:16px' class=explain>{squid_urgency_explain}</div>";
	
	$html="<div style='width:98%' class=form >
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend style='font-size:22px'>{global_urgency_mode}:</td>
		<td valign='top' >". Field_checkbox_design("SquidUrgency-$t", 1,$SquidUrgency,"blur()")."</td>
		<td valign='top' style='font-size:12px'>{squid_urgency_explain}</td>
	</tr>
	<tr style='height:30px;'><td style='text-align:right' colspan=3><hr></td></tR>
	<tr>
		<td valign='middle' class=legend style='font-size:22px'nowrap>Active Directory:</td>
		<td valign='top' >". Field_checkbox_design("ActiveDirectoryEmergency-$t", 1,$ActiveDirectoryEmergency,"blur()")."</td>
		<td valign='top' style='font-size:12px'>{activedirectory_emergency_mode_explain2}</td>
	</tr>	
	<tr style='height:30px;'><td style='text-align:right' colspan=3><hr></td></tR>		
	<tr>
		<td valign='middle' class=legend style='font-size:22px'>{ssl_methods}:</td>
		<td valign='top' >". Field_checkbox_design("SquidSSLUrgency-$t", 1,$SquidSSLUrgency,"blur()")."</td>
		<td valign='top' style='font-size:12px'>{SquidSSLUrgency_explain}</td>
	</tr>
	<tr style='height:30px;'><td style='text-align:right' colspan=3><hr></td></tR>	
	<tr>
		<td valign='middle' class=legend style='font-size:22px'>HyperCache:</td>
		<td valign='top' >". Field_checkbox_design("StoreIDUrgency-$t", 1,$StoreIDUrgency,"blur()")."</td>
		<td valign='top' style='font-size:12px'>{StoreIDUrgency_explain}</td>
	</tr>
	<tr style='height:30px;'><td style='text-align:right' colspan=3><hr></td></tR>					
	<tr>
		<td valign='middle' class=legend style='font-size:22px'>{logs}:</td>
		<td valign='top' >". Field_checkbox_design("LogsWarninStop-$t", 1,$LogsWarninStop,"blur()")."</td>
		<td valign='top' style='font-size:12px'>{enable_logs_urgency_explain}</td>
	</tr>		
	<tr style='height:140px;'>
		<td style='text-align:right' colspan=3>". button("{apply}", "Save$t()",34)."</td>
	</tr>			
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}	
	Loadjs('squid.compile.progress.php');
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('SquidUrgency-$t').checked){XHR.appendData('SquidUrgency',1);}
	else{XHR.appendData('SquidUrgency',0);}
	
	if(document.getElementById('ActiveDirectoryEmergency-$t').checked){XHR.appendData('ActiveDirectoryEmergency',1);}
	else{XHR.appendData('ActiveDirectoryEmergency',0);}

	if(document.getElementById('SquidSSLUrgency-$t').checked){XHR.appendData('SquidSSLUrgency',1);}
	else{XHR.appendData('SquidSSLUrgency',0);}

	if(document.getElementById('StoreIDUrgency-$t').checked){XHR.appendData('StoreIDUrgency',1);}
	else{XHR.appendData('StoreIDUrgency',0);}	

	if(document.getElementById('LogsWarninStop-$t').checked){XHR.appendData('LogsWarninStop',1);}
	else{XHR.appendData('LogsWarninStop',0);}		
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
	
function Save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
		
	}
	
}	
	