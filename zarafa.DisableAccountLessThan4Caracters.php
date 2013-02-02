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
	
	
	
	
	if(isset($_POST["DisableAccountLessThan4Caracters"])){Save();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{uid_length_protection}");
	echo "YahooWin3('482','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$DisableAccountLessThan4Caracters=$sock->GET_INFO("DisableAccountLessThan4Caracters");
	if(!is_numeric($DisableAccountLessThan4Caracters)){$DisableAccountLessThan4Caracters=0;}
	$t=time();
	$p=Paragraphe_switch_img("{disable_uid_length_protection}",
			 "{uid_length_protection_text}","DisableAccountLessThan4Caracters",$DisableAccountLessThan4Caracters,null,400);
	
	
	//$help="<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://mail-appliance.org/index.php?cID=390','1024','900');\"
	//style='font-size:14px;font-weight:bold;text-decoration:underline'>{online_help}</a>";
	
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
		<hr>". button("{apply}","Save$t()",16)."
	</td>
	</tr>
	</table>
	<script>
	var x_Save$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	}	
		
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('DisableAccountLessThan4Caracters',document.getElementById('DisableAccountLessThan4Caracters').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function Save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableAccountLessThan4Caracters", $_POST["DisableAccountLessThan4Caracters"]);
	
	
}

