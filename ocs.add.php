<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('GetRights::$error')";
		die();
	}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["hostname"])){save();exit;}

js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_computer}");
	$page=CurrentPageName();
	echo "YahooWin5('990','$page?popup=yes','$title')";
}
function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
	if($users->AsSambaAdministrator){return true;}

	return false;
}

function popup(){
	$page=CurrentPageName();
	$t=time();
	$html="<div style='font-size:22px'>{new_computer}</div>
	<div id='popup_import_div' class=form style='width:98%'>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td>". Field_text("hostname-$t",null,"font-size:22px;width:450px",null,null,null,false,"SaveCheck$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{MAC}:</td>
		<td>". Field_text("MAC-$t",null,"font-size:22px;width:450px",null,null,null,false,"SaveCheck$t(event)")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{ipaddr}:</td>
		<td>". field_ipv4("ipaddr-$t",null,"font-size:22px;width:450px",false,"SaveCheck$t(event)")."</td>
	</tr>			
	<tr style='height:80px'>
	<td colspan=2 style='text-align:right'>	<hr>
		". button("{add}","Save$t()",28)."
	</td>
	</tr>
	</table>
</div>
<script>

function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	YahooWin5Hide();
	if(document.getElementById('OCS_SEARCH_TABLE')){
      	var id=document.getElementById('OCS_SEARCH_TABLE').value;
      	$('#'+id).flexReload();
      }
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.appendData('mac',document.getElementById('MAC-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}


function save(){
	
	$_POST["mac"]=str_replace("-", ":", $_POST["mac"]);
	$_POST["mac"]=strtolower($_POST["mac"]);
	
	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($_POST["mac"])){
		echo "MAC: {$_POST["mac"]} Invalid!\n";
		return;
	}
	
	if(!$ipClass->isValid($_POST["ipaddr"])){
		echo "MAC: {$_POST["ipaddr"]} Invalid!\n";
		return;
	}
	
	$cmp=new computers();
	$uid=$cmp->ComputerIDFromMAC($_POST["mac"]);
	if($uid<>null){$cmp=new computers($uid);}
	
	if($uid==null){$uid="{$_POST["hostname"]}$";}
	$cmp->uid=$uid;
	$cmp->ComputerIP=$_POST["ipaddr"];
	$cmp->ComputerMacAddress=$_POST["mac"];
	$cmp->ComputerRealName=$_POST["hostname"];
	if($cmp->Add()){
		echo $cmp->ldap_error;
	}
	
	
	
	}
