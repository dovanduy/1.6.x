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
	
	
	
	
	if(isset($_POST["EnableZarafaSalearnSchedule"])){ZarafaSaLearnScheduleSave();exit;}	
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
	$EnableZarafaSalearnSchedule=$sock->GET_INFO("EnableZarafaSalearnSchedule");
	if(!is_numeric($EnableZarafaSalearnSchedule)){$EnableZarafaSalearnSchedule=0;}
	
	$p=Paragraphe_switch_img("{salearnschedule}", "{salearnschedule_text}<br>{ZARAFA_JUNK_EXPLAIN}","EnableZarafaSalearnSchedule",$EnableZarafaSalearnSchedule,null,400);
	
	
	$help="<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://mail-appliance.org/index.php?cID=390','1024','900');\"
	style='font-size:14px;font-weight:bold;text-decoration:underline'>{online_help}</a>";
	
	$html="
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=3 align='right'>$help</td>
	</tr>	
	<tr>
		<td colspan=3 align='left'>$p</td>
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
		XHR.appendData('EnableZarafaSalearnSchedule',document.getElementById('EnableZarafaSalearnSchedule').value);
		XHR.sendAndLoad('$page', 'POST',x_ZarafaSaLearnScheduleSave);
		}
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSaLearnScheduleSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableZarafaSalearnSchedule", $_POST["EnableZarafaSalearnSchedule"]);
	
	
}

