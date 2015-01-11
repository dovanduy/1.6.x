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
	
	
	
	
	if(isset($_POST["ZarafadAgentJunk"])){ZarafaSave();exit;}	
	if(isset($_GET["popup"])){popup();exit;}
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	
	$title=$tpl->_ENGINE_parse_body("{junk_mail_folder}");
	echo "YahooWin3('590','$page?popup=yes','$title')";
	
}	
	



function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ZarafadAgentJunk=$sock->GET_INFO("ZarafadAgentJunk");
	if(!is_numeric($ZarafadAgentJunk)){$ZarafadAgentJunk=0;}
	$ZarafadAgentJunkHeader=$sock->GET_INFO("ZarafadAgentJunkHeader");
	$ZarafadAgentJunkValue=$sock->GET_INFO("ZarafadAgentJunkValue");
	if($ZarafadAgentJunkHeader==null){$ZarafadAgentJunkHeader="X-Spam-Status";}
	if($ZarafadAgentJunkValue==null){$ZarafadAgentJunkValue="Yes,";}
	
	
	
	$t=time();

	$html="
	<div id='div-$t'></div>
	<div class=text-info style='font-size:16px'>{zspam_header_name}</div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=325','1024','900');\" 
		style='font-size:16px;text-decoration:underline'>{online_help}</a></div>
		<table style='width:99%' class=form>
				<tr>
				<td class=legend style='font-size:16px'>{enable}:</td>
				<td>". Field_checkbox("ZarafadAgentJunk",1,$ZarafadAgentJunk,"ZarafadAgentJunkCheck()")."</td>
				<td>&nbsp;</td>
			</tr>		
			
			<tr>
				<td class=legend style='font-size:16px'>{header}:</td>
				<td>". Field_text("ZarafadAgentJunkHeader",$ZarafadAgentJunkHeader,"font-size:16px;padding:3px;width:190px")."</td>
				<td>&nbsp;</td>
			</tr>				
			<tr>
				<td class=legend style='font-size:16px'>{value}:</td>
				<td>". Field_text("ZarafadAgentJunkValue",$ZarafadAgentJunkValue,"font-size:16px;padding:3px;width:190px")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}","ZarafadAgentJunkSave$t()","18px")."</td>
			</tr>		
		</table>
	<script>
	var x_ZarafadAgentJunkSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
		
	}	
		
	
	function ZarafadAgentJunkSave$t(){
		var XHR = new XHRConnection();
		var ZarafadAgentJunk=0;
		if(document.getElementById('ZarafadAgentJunk').checked){ZarafadAgentJunk=1;}	
		XHR.appendData('ZarafadAgentJunk',ZarafadAgentJunk);
		XHR.appendData('ZarafadAgentJunkHeader',document.getElementById('ZarafadAgentJunkHeader').value);
		XHR.appendData('ZarafadAgentJunkValue',document.getElementById('ZarafadAgentJunkValue').value);
		AnimateDiv('div-$t');
		XHR.sendAndLoad('$page', 'POST',x_ZarafadAgentJunkSave$t);
		}
		
	function ZarafadAgentJunkCheck(){
		document.getElementById('ZarafadAgentJunkHeader').disabled=true;
		document.getElementById('ZarafadAgentJunkValue').disabled=true;
		
		
		if(document.getElementById('ZarafadAgentJunk').checked){
			document.getElementById('ZarafadAgentJunkHeader').disabled=false;
			document.getElementById('ZarafadAgentJunkValue').disabled=false;
					
		}
	}
	
	ZarafadAgentJunkCheck();
		
		
	</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function ZarafaSave(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafadAgentJunkHeader", $_POST["ZarafadAgentJunkHeader"]);
	$sock->SET_INFO("ZarafadAgentJunkValue", $_POST["ZarafadAgentJunkValue"]);
	$sock->SET_INFO("ZarafadAgentJunk", $_POST["ZarafadAgentJunk"]);
	$sock->getFrameWork("zarafa.php?restart-dagent=yes");
	
}

