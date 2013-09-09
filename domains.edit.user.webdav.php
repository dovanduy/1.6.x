<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.donkey.inc');	

			
	if(!CheckRights){
		$tpl=new templates();
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["WebDavUser"])){SaveInfos();exit;}

	js();
	
function popup(){
	$uid=$_GET["uid"];
	$user=new user($uid);
	$sock=new sockets();
	$error=array();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
	$WebDavPerUserSets=unserialize(base64_decode($sock->GET_INFO("WebDavPerUserSets")));
	if(!is_numeric($EnableWebDavPerUser)){$EnableWebDavPerUser=0;}
	$users=new usersMenus();
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	if(!$users->MPM_ITK_MODULE){$error[]="MPM itk module";}
	if(!$users->APACHE_MODE_WEBDAV){$error[]="WebDav module";}
	if($EnableWebDavPerUser==0){$error[]="WebDav Per user not enabled";}
	if($EnableFreeWeb==0){$error[]="FreeWebs not enabled";}
	
	if(count($error)>0){
		$html="<table style='width:80%' class=form><tbody><tr><td width=1%><img src='img/error-64.png'><td width=99%><div style='font-size:16px;color:#9D0000'>{missing_module}!:<br>".@implode("<br>", $error)."</div></td></tr></tbody></table>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	
	$field=Paragraphe_switch_img("{ACTIVATE_THIS_USER_WEBDAV}","{ACTIVATE_THIS_USER_WEBDAV_TEXT}",
	"WebDavUser",null,$user->WebDavUser,350);
	
	
	
	$html="
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width=1%><img src='img/webdav-128.png' id='webdav-image'></td>
	<td valing='top'>
	
	<table style='width:100%'>
	<tr>
		<td>$field</td>
	</tr>
	<tr>
	<tr>
		<td align='right'><hr>". button("{apply}","SaveWebDavEnabledUser()")."</td>
	</tr>
	</table>	
	</td>
	</tr>
	</table>
	
	";
				
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	}	
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$uid=$_GET["uid"];
	$title=$tpl->_ENGINE_parse_body($uid.'::{USER_WEBDAV}');	
	
	
	
$html="

function user_webdav_load(){
	YahooWin4('600','$page?popup=yes&uid={$_GET["uid"]}','$title');
	}


function SaveWebDavEnabledUser(){
	var XHR = new XHRConnection();
	document.getElementById('webdav-image').src='img/wait_verybig.gif';
    XHR.appendData('WebDavUser',document.getElementById('WebDavUser').value);
    XHR.appendData('uid','{$_GET["uid"]}');
    XHR.sendAndLoad('$page', 'GET',x_SaveWebDavEnabledUser); 

}

	
var x_SaveWebDavEnabledUser= function (obj) {
	var results=trim(obj.responseText);
	if(results.length>1){alert('<'+results+'>');}
	document.getElementById('webdav-image').src='img/webdav-128.png';
	if(document.getElementById('WebDavTableUsersFindPopupDiv')){WebDavTableUsersFindPopupDivRefresh();}
	
	}
		


user_webdav_load();";	
	
	
echo $html;	
}	

function SaveInfos(){
	$users=new user($_GET["uid"]);
	$users->WebDavUser=$_GET["WebDavUser"];
	$users->SaveWebDav();
	
}
	
	
function CheckRights(){
	if(!$_GET["uid"]){return false;}
	$usersprivs=new usersMenus();
	if($usersprivs->AsAnAdministratorGeneric){return true;}
	if($usersprivs->AllowAddGroup){return true;}
	if($usersprivs->AllowAddUsers){return true;}
	return false;
}	

?>