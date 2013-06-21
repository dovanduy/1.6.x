<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}

	if(isset($_POST["ZarafaMySQLServiceType"])){Save();exit;}
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();

	$ZarafaMySQLServiceType=$sock->GET_INFO("ZarafaMySQLServiceType");
	if(!is_numeric($ZarafaMySQLServiceType)){$ZarafaMySQLServiceType=1;}
	
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	$arraySRV[1]="{main_mysql_server_1}";
	$arraySRV[2]="{main_mysql_server_2}";
	if($ZarafaDedicateMySQLServer==1){
		$arraySRV[3]="{main_mysql_server_3}";
	}
	$arraySRV[4]="{main_mysql_server_4}";
	
	$t=time();
	$ZarafaRemoteMySQLServer=$sock->GET_INFO("ZarafaRemoteMySQLServer");
	$ZarafaRemoteMySQLServerPort=$sock->GET_INFO("ZarafaRemoteMySQLServerPort");
	$ZarafaRemoteMySQLServerAdmin=$sock->GET_INFO("ZarafaRemoteMySQLServerAdmin");
	$ZarafaRemoteMySQLServerPassword=$sock->GET_INFO("ZarafaRemoteMySQLServerPassword");
	
	if(!is_numeric($ZarafaRemoteMySQLServerPort)){$ZarafaRemoteMySQLServerPort=3306;}
	
	$DropDownF=Field_array_Hash($arraySRV, "ZarafaMySQLServiceType",$ZarafaMySQLServiceType,"ZarafaMySQLServiceTypeForm()",null,0,"font-size:16px");
	
	
	$html="
	<div id='$t'></div>
	<div class=explain id='zarafa_mysql_tuning_text' style='font-size:14px'>{zarafa_mysql_params_text}</div>
	
	<table style='width:99%' class=form>
	<tR>
		<td class=legend style='font-size:16px'>{mysql_server}:</td>
		<td>$DropDownF</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{remote_mysql_server}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaRemoteMySQLServer",$ZarafaRemoteMySQLServer,"font-size:16px;width:190px")."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{mysql_server_port}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaRemoteMySQLServerPort",$ZarafaRemoteMySQLServerPort,"font-size:16px;width:90px")."</td>
		<td width=1%></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{mysql_admin}:</td>
		<td style='font-size:16px'>". Field_text("ZarafaRemoteMySQLServerAdmin",$ZarafaRemoteMySQLServerAdmin,"font-size:16px;width:190px")."</td>
		<td width=1%></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td style='font-size:16px'>". Field_password("ZarafaRemoteMySQLServerPassword",$ZarafaRemoteMySQLServerPassword,"font-size:16px;width:190px")."</td>
		<td width=1%></td>
	</tr>					
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","ZarafaSave$t()",18)."</td>
	</tr>	
</table>
<script>
	function ZarafaMySQLServiceTypeForm(){
		document.getElementById('ZarafaRemoteMySQLServer').disabled=true;
		document.getElementById('ZarafaRemoteMySQLServerPort').disabled=true;
		document.getElementById('ZarafaRemoteMySQLServerAdmin').disabled=true;
		document.getElementById('ZarafaRemoteMySQLServerPassword').disabled=true;
		if(document.getElementById('ZarafaMySQLServiceType').value==4){
			document.getElementById('ZarafaRemoteMySQLServer').disabled=false;
			document.getElementById('ZarafaRemoteMySQLServerPort').disabled=false;
			document.getElementById('ZarafaRemoteMySQLServerAdmin').disabled=false;
			document.getElementById('ZarafaRemoteMySQLServerPassword').disabled=false;
		}
	
	}
	var x_ZarafaSave$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue);}
		
		}	
	
	function ZarafaSave$t(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('ZarafaRemoteMySQLServerPassword').value);
		XHR.appendData('ZarafaMySQLServiceType',document.getElementById('ZarafaMySQLServiceType').value);
		XHR.appendData('ZarafaRemoteMySQLServer',document.getElementById('ZarafaRemoteMySQLServer').value);
		XHR.appendData('ZarafaRemoteMySQLServerPort',document.getElementById('ZarafaRemoteMySQLServerPort').value);
		XHR.appendData('ZarafaRemoteMySQLServerAdmin',document.getElementById('ZarafaRemoteMySQLServerAdmin').value);
		XHR.appendData('ZarafaRemoteMySQLServerPassword',pp);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSave$t);
	
	}
	
ZarafaMySQLServiceTypeForm();
</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	$_POST["ZarafaRemoteMySQLServerPassword"]=url_decode_special_tool($_POST["ZarafaRemoteMySQLServerPassword"]);
	$sock->SET_INFO("ZarafaMySQLServiceType", $_POST["ZarafaMySQLServiceType"]);
	$sock->SET_INFO("ZarafaRemoteMySQLServer", $_POST["ZarafaRemoteMySQLServer"]);
	$sock->SET_INFO("ZarafaRemoteMySQLServerPort", $_POST["ZarafaRemoteMySQLServerPort"]);
	$sock->SET_INFO("ZarafaRemoteMySQLServerAdmin", $_POST["ZarafaRemoteMySQLServerAdmin"]);
	$sock->SET_INFO("ZarafaRemoteMySQLServerPassword", $_POST["ZarafaRemoteMySQLServerPassword"]);
	$sock->getFrameWork("zarafa.php?reload=yes");
	
	}
	
	
	
	