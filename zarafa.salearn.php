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
	
	
	
	
	if(isset($_POST["ZarafaSaLearnSchedule"])){ZarafaSaLearnScheduleSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	if(!$usersmenus->spamassassin_installed){return;}
	$title=$tpl->_ENGINE_parse_body("{salearnschedule}");
	echo "YahooWin3('482','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafaSalearnSchedule=$sock->GET_INFO("ZarafaSaLearnSchedule");
	$html="
	<div class=explain style='font-size:16px'>{salearnschedule_text}<br>{ZARAFA_JUNK_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{schedule}:</td>
		<td>". Field_text("ZarafaSaLearnSchedule",$ZarafaSalearnSchedule,"font-size:18px;border:3px solid #848484;width:280px")."</td>
		<td>".button("{browse}","Loadjs('cron.php?field=ZarafaSaLearnSchedule')",12)."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
		<hr>". button("{apply}","ZarafaSaLearnScheduleSave()",16)."
	</td>
	</tr>
	</table>
	<script>
	var x_ZarafaSaLearnScheduleSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	}	
		
	
	function ZarafaSaLearnScheduleSave(){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaSaLearnSchedule',document.getElementById('ZarafaSaLearnSchedule').value);
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSaLearnScheduleSave);
		}
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSaLearnScheduleSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaSaLearnSchedule", $_POST["ZarafaSaLearnSchedule"]);
	$sock->getFrameWork("services.php?restart-framework=yes");
	
}

