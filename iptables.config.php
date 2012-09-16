<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.iptables.inc');
	include_once('ressources/class.ntpd.inc');	
	$user=new usersMenus();
	if($user->AsArticaAdministrator==false){die("No privs");}
	
	if(isset($_GET["popup"])){popup();exit();}
	if(isset($_POST["GlobalIptablesEnabled"])){Save();exit;}
js();



function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{firewall_behavior}");
	$html="YahooWin2('550','$page?popup=yes','$title')";
	echo $html;
	
}

function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
	if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}
	$t=time();
	
	$p=Paragraphe_switch_img("{enable_fw_autorules}", "{enable_fw_autorules_text}","GlobalIptablesEnabled",$GlobalIptablesEnabled,null,450);
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}","SaveIptableBhvior()",16)."</td>
	</tr>
	</table>
	<script>
var x_SaveIptableBhvior= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	YahooWin2Hide();
	}

function SaveIptableBhvior(){
		var XHR = new XHRConnection();
		XHR.appendData('GlobalIptablesEnabled',document.getElementById('GlobalIptablesEnabled').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveIptableBhvior);

}		
	
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("GlobalIptablesEnabled", $_POST["GlobalIptablesEnabled"]);
	$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
	
}
	
	
