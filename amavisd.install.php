<?php
include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.clamav.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	if($usersmenus->AMAVIS_INSTALLED){return;}
	$title=$tpl->_ENGINE_parse_body("{APP_AMAVISD_NEW}");
	echo "YahooWin3('550','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();			
		$html="
		<center>
		<table style='width:80%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/software-remove-128.png'></td>
			<td valign='top' width=99%>
			<p style='font-size:14px'>{enable_amavis_text}
			</p>
			<div style='text-align:right'><hr>". button("{install}","InstallAmavisd()",18)."</div>
		</td>
		</tr>
		</table>
		</center>
			<script>
				function InstallAmavisd(){
					Loadjs('setup.index.progress.php?product=APP_AMAVISD_MILTER&start-install=yes');
					YahooWin3Hide();
				}
			</script>";
		echo $tpl->_ENGINE_parse_body($html);
}