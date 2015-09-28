<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');


$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();

if ($change_aliases == 0) {
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text( "{ERROR_NO_PRIVILEGES_OR_PLUGIN_DISABLED}" )."');";
	return;
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["HARDWARE_ID"])){Save();exit;}
if(isset($_GET["MEMBER_JS"])){MEMBER_JS_JS();exit;}
js();



function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$computer=$tpl->javascript_parse_text("{new_computer}");
	
	$ocs=new ocs();
	$ocs->HARDWARE_ID=$_GET["HARDWARE_ID"];
	$ocs->LoadParams();
	$computer=$ocs->ComputerName;
	echo "YahooWinBrowse(850,'$page?popup=yes&HARDWARE_ID={$_GET["HARDWARE_ID"]}&t={$_GET["t"]}','$computer')";
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	$ocs=new ocs();
	$ocs->HARDWARE_ID=$_GET["HARDWARE_ID"];
	$ocs->LoadParams();
	$computer=$ocs->ComputerName;
	
	$html="<div style='font-size:32px;margin-bottom:20px'>{computer} {$ocs->mac}/{$ocs->ComputerName}</div>
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{computer_name}:</td>
			<td>". Field_text("computername-$t",$ocs->ComputerName,"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{MAC}:</td>
			<td>". Field_text("MAC-$t",$ocs->mac,"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{ipaddr}:</td>
			<td>". field_ipv4("ipaddr-$t",$ocs->ComputerIP,"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
		</tr>
	</table>
	</div>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	var comp=document.getElementById('MAC-$t').value;
	YahooWinBrowseHide();
	$('#flexRT{$_GET["t"]}').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('HARDWARE_ID','{$_GET["HARDWARE_ID"]}');
	XHR.appendData('computername',document.getElementById('computername-$t').value);
	XHR.appendData('MAC',document.getElementById('MAC-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
</script>";	

	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function MEMBER_JS_JS(){
	header("content-type: application/x-javascript");
	$comp=new computers();
	$uid=$comp->ComputerIDFromMAC($_GET["MEMBER_JS"]);
	echo MEMBER_JS($uid,1,1);
	
}

function Save(){
	
	$ocs=new ocs();
	$ocs->HARDWARE_ID=$_POST["HARDWARE_ID"];
	$ocs->LoadParams();
	$ocs->ComputerName=$_POST["computername"];
	$ocs->ComputerIP=$_POST["ipaddr"];
	$ocs->mac=$_POST["MAC"];
	$ocs->UpdateDirect();
	
	
	
	
	
}

