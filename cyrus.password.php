<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	

	
	if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SaveCyrusPassword"])){SaveCyrusPassword();exit;}
	js();
	
function js(){
		
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{cyrus password}');
	$prefix=str_replace('.','_',$page);
	$html="
	function {$prefix}LoadMainPage(){
		YahooWin3('550','$page?popup=yes','$title');
		
		}
		
	var x_SaveCyrusPassword= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}	
		{$prefix}LoadMainPage();
		}

	
	function SaveCyrusPassword(){
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('cyruspassword').value);
			XHR.appendData('SaveCyrusPassword',pp);
			AnimateDiv('change_cyrus_password');
			XHR.sendAndLoad('$page', 'POST',x_SaveCyrusPassword);
		}
		
	{$prefix}LoadMainPage();
	";
	
	echo $html;
}

function popup(){
	
	
	$ldap=new clladp();
	$cyruspass=$ldap->CyrusPassword();
	
	$html="
	<div id='change_cyrus_password'>
	<table style='width:99%'>
	<tr>
		<td valign='top' width=1%><img src='img/cyrus-password-120.png'></td>
		<td valign='top'><div class=explain style='font-size:16px'>{change_cyrus_password}</div>
		<br>
			<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:16px'>{password}:</td>
				<td>" . Field_password('cyruspassword',$cyruspass,"font-size:16px")."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><hr>
				". button("{apply}","SaveCyrusPassword()","18px")."</td>
			</tr>
		</table>
		</td>
		</tr>
		</table></div>";
	
	$tp=new templates();
	echo $tp->_ENGINE_parse_body($html,'cyrus.index.php');
	
}

function SaveCyrusPassword(){
	$ldap=new clladp();
	$_POST["SaveCyrusPassword"]=url_decode_special_tool(trim($_POST["SaveCyrusPassword"]));
	if($_POST["SaveCyrusPassword"]==null){return null;}
	
	if(strpos($_POST["SaveCyrusPassword"],'@')>0){
		echo "@: denied character\n";
		return;
	}
	if(strpos($_POST["SaveCyrusPassword"],':')>0){
		echo "@: denied character\n";
		return;
	}	
	
	$attrs["userPassword"][0]=$_POST["SaveCyrusPassword"];
	$dn="cn=cyrus,dc=organizations,$ldap->suffix";
	if($ldap->ExistsDN($dn)){
		if(!$ldap->Ldap_modify($dn,$attrs)){echo $ldap->ldap_last_error;exit;}
	}
	
	$dn="cn=cyrus,$ldap->suffix";
	if($ldap->ExistsDN($dn)){
		if(!$ldap->Ldap_modify($dn,$attrs)){echo $ldap->ldap_last_error;exit;}
	}	
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?cyrus-change-password=".base64_encode($_POST["SaveCyrusPassword"]));
	
	
}
 
	

?>