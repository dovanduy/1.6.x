<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ocs.inc');
	
	

	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	if(isset($_POST["disable"])){disable();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["OcsServerDest"])){OCSURI_SAVE();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{INSTALL_OCS_CLIENT}");
	echo "YahooWin2('370','$page?popup=yes','$title')";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$OcsServerDest=$sock->GET_INFO("OcsServerDest");
	$OcsServerDestPort=$sock->GET_INFO("OcsServerDestPort");
	$OcsServerUseSSL=$sock->GET_INFO("OcsServerUseSSL");
	if(!is_numeric($OcsServerUseSSL)){$OcsServerUseSSL=1;}
	if(!is_numeric($OcsServerDestPort)){$OcsServerDestPort=9138;}
	
	
	$p=Paragraphe("64-install-soft.png", "{INSTALL_OCS_CLIENT}", "{INSTALL_OCS_CLIENT_TEXT}","javascript:Loadjs('ocs.clientinstall.php')");
	
	$html="<div style='background-color:#005447'><img src='img/ocs-logo.png'></div>
	<div class=explain style='font-size:14px'>{OCS_CLIENT_PUB_TEXT}</div>
	<center id='OCSURIDIV'>
	<table style='width:90%' class=form>
	<tboy>
	<tr>
	<td class=legend style='font-siz:14px'>{APP_OCSI}:</td>
	<td>". Field_text("OcsServerDest",$OcsServerDest,"font-size:14px;padding:4px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-siz:14px'>{listen_port}:</td>
		<td>". Field_text("OcsServerDestPort",$OcsServerDestPort,"font-size:14px;padding:4px;width:60px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-siz:14px'>{UseSSL}:</td>
		<td>". Field_checkbox("OcsServerUseSSL",1,$OcsServerUseSSL)."</td>
	</tr>			
	<tr>
		<td colspan=2 align='right'><hr>". button("{install_upgrade}","SaveAndInstallOCSCLient()")."</td>
	</tr>
	</tbody>
	</table>
	</center>
	<div style='font-size:16px;font-weight:bold;text-align:right;margin-top:10px'><a href=\"javascript:blur();\" OnClick=\"javascript:RemoveOcClientInstall()\" style='font-size:16px;font-weight:bold;text-decoration:underline'>{ihavereaditremove}</a></div>
	
	
	<script>
	var x_RemoveOcClientInstall= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin2Hide();
		LoadAjax('admin-left-infos','admin.index.status-infos.php');
		RefreshTab('admin_perso_tabs');
	}		
	
	
	
	function RemoveOcClientInstall(){
		var XHR = new XHRConnection();
		XHR.appendData('disable','yes');
		XHR.sendAndLoad('$page', 'POST',x_RemoveOcClientInstall);	
		
	}
	
	
	function SaveAndInstallOCSCLient(){
		var XHR = new XHRConnection();
		XHR.appendData('OcsServerDest',document.getElementById('OcsServerDest').value);
		XHR.appendData('OcsServerDestPort',document.getElementById('OcsServerDestPort').value);
		if(document.getElementById('OcsServerUseSSL').checked){XHR.appendData('OcsServerUseSSL',1);}else{XHR.appendData('OcsServerUseSSL',0);}
		AnimateDiv('OCSURIDIV');
		XHR.sendAndLoad('$page', 'POST',x_RemoveOcClientInstall);
	}
	
	
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function disable(){
	$sock=new sockets();
	$sock->SET_INFO("DisableOCSClientPub", 1);
	
}

function OCSURI_SAVE(){
	$sock=new sockets();
	$tpl=new templates();
	$echo="{APP_OCSI_LINUX_CLIENT}\n{installation_lauched}";
	$echo=$tpl->javascript_parse_text($echo,1);	
	echo $echo;
	$sock->SET_INFO("DisableOCSClientPub", 1);
	$sock->SET_INFO("EnableOCSAgent", 1);
	$sock->SET_INFO("OcsServerDest", $_POST["OcsServerDest"]);
	$sock->SET_INFO("OcsServerDestPort", $_POST["OcsServerDestPort"]);
	$sock->SET_INFO("OcsServerUseSSL", $_POST["OcsServerUseSSL"]);
	$sock->getFrameWork("cmd.php?start-install-app=APP_OCSI_LINUX_CLIENT");

}

