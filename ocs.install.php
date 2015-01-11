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
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{INSTALL_OCS}");
	echo "YahooWin2('650','$page?popup=yes','$title')";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$p=Paragraphe("64-install-soft.png", "{INSTALL_OCS}", "{INSTALL_OCS_TEXT}","javascript:Loadjs('setup.index.progress.php?product=APP_OCSI2&start-install=yes')");
	
	$html="<div style='background-color:#005447'><img src='img/ocs-logo.png'></div>
	<table style='width:100%'>
	<TBODY>
	<tr>
	<TD VALIGN='TOP'>$p</td>
	<td valign='top' width=99%>
		<div class=text-info style='font-size:14px'>{OCS_PUB_TEXT}</div>
	</td>
	</tr>
	<tr>
		<td colspan=2 align='left' style='font-size:16px;font-weight:bold'><a href=\"javascript:blur();\" OnClick=\"javascript:RemoveOcInstall()\"
			style='font-size:16px;font-weight:bold;text-decoration:underline'>{ihavereaditremove}</a>
		</td>
	</tr>
	</tbody>
	</table>	
	
	<script>
	var x_RemoveOcInstall= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWin2Hide();
		LoadAjax('admin-left-infos','admin.index.status-infos.php');
		RefreshTab('admin_perso_tabs');
	}		
	
	
	
	function RemoveOcInstall(){
		var XHR = new XHRConnection();
		XHR.appendData('disable','yes');
		XHR.sendAndLoad('$page', 'POST',x_RemoveOcInstall);	
		
	}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function disable(){
	$sock=new sockets();
	$sock->SET_INFO("DisableOCSPub", 1);
	
}
