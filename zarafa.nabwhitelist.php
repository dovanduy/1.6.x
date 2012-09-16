<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	
	
	if(isset($_POST["ZarafaAdbksWhiteTask"])){ZarafaSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{addressbooks_whitelisting}");
	echo "YahooWin3('590','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafaAdbksWhiteTask=$sock->GET_INFO("ZarafaAdbksWhiteTask");
	if(!is_numeric($ZarafaAdbksWhiteTask)){$ZarafaAdbksWhiteTask=0;}
	
	
	$p=Paragraphe_switch_img("{addressbooks_whitelisting}", "{addressbooks_whitelisting_zarafa}",
	"ZarafaAdbksWhiteTask",$ZarafaAdbksWhiteTask,null,$width="400");
	
	$t=time();

	$html="
	<div id='div-$t'></div>
	<div class=explain style='font-size:16px'>{addressbooks_whitelisting_explain}</div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=328','1024','900');\" 
		style='font-size:16px;text-decoration:underline'>{online_help}</a></div>
		<table style='width:99%' class=form>
				<tr>
				
				<td>$p</td>
				
			</tr>		
			
			
			<tr>
				<td align='right'><hr>". button("{apply}","Zarafa$t()","18px")."</td>
			</tr>		
		</table>
	<script>
	var x_Zarafa$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
		
	}	
		
	
	function Zarafa$t(){
		var XHR = new XHRConnection();
		var ZarafadAgentJunk=0;
		XHR.appendData('ZarafaAdbksWhiteTask',document.getElementById('ZarafaAdbksWhiteTask').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_Zarafa$t);
		}
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaAdbksWhiteTask", $_POST["ZarafaAdbksWhiteTask"]);
}

