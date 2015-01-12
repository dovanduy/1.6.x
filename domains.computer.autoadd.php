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
if(isset($_POST["computername"])){Save();exit;}
if(isset($_GET["MEMBER_JS"])){MEMBER_JS_JS();exit;}
js();



function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$computer=$tpl->javascript_parse_text("{new_computer}");
	
	
	echo "YahooWinBrowse(850,'$page?popup=yes&mac=".urlencode($_GET["mac"])."&ipaddr=".urlencode($_GET["ipaddr"]).
	"&computername=".urlencode($_GET["computername"])."&t={$_GET["t"]}','$computer {$_GET["mac"]}')";
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	$html="<div style='font-size:32px;margin-bottom:20px'>{new_computer} {$_GET["mac"]}/{$_GET["computername"]}</div>
	<div style='font-size:18px;margin-bottom:20px' class=explain>{this_computers_database_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{computer_name}:</td>
			<td>". Field_text("computername-$t",$_GET["computername"],"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{MAC}:</td>
			<td>". Field_text("MAC-$t",$_GET["mac"],"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{ipaddr}:</td>
			<td>". field_ipv4("ipaddr-$t",$_GET["ipaddr"],"font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","Save$t()",32)."</td>
		</tr>
	</table>
	</div>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	var comp=document.getElementById('MAC-$t').value;
	YahooWinBrowseHide();
	Loadjs('$page?MEMBER_JS='+comp);
	$('#flexRT{$_GET["t"]}').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
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
	$comp=new computers();
	$comp->uid=$_POST["computername"]."$";
	$comp->ComputerRealName=$_POST["computername"];
	$comp->ComputerIP=$_POST["ipaddr"];
	$comp->ComputerMacAddress=$_POST["MAC"];
	$comp->Add();
	
	
	
	
}

