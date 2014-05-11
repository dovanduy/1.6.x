<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	$user=new usersMenus();
		if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["change_password"])){change();exit;}
js();


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{root_password_not_changed}");
	$html="YahooWin5(550,'$page?popup=yes','$title');";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$root_error_pass=$tpl->javascript_parse_text("{root_error_pass}");
	$sock=new sockets();
	$PAMLdapPrio=$sock->GET_INFO("PAMLdapPrio");
	if(!is_numeric($PAMLdapPrio)){$PAMLdapPrio=1;}
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><div id='animate-root'><img src='img/cop-lock-128.png' ></div></td>
		<td valign='top'>
		<table style='width:98%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{LDAP_PRIORITY}:</td>
			<td>". Field_checkbox("PAMLdapPrio",1,$PAMLdapPrio)."</td>
		</tr>		
		
		<tr>
			<td class=legend style='font-size:16px'>{password}:</td>
			<td>". Field_password("root-pass1",null,"font-size:18px;padding:3px;width:120px",null,null,null,false,"CHRootPwdCheck(event)")."</td>
		</tr>
			<td>&nbsp;</td>
			<td>". Field_password("root-pass2",null,"font-size:18px;padding:3px;width:120px",null,null,null,false,"CHRootPwdCheck(event)")."</td>
		<tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","CHRootPwd()",18)."</td>
		</tr>
		</table>
		<div class=explain style='margin-top:10px;font-size:14px'>{root_password_not_changed_text}</div>
		
		<script>
			function CHRootPwdCheck(e){
				if(checkEnter(e)){CHRootPwd();return;}
			}
			
		var X_CHRootPwd= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('animate-root').innerHTML='<img src=img/cop-lock-128.png>';
			if(tempvalue.length>3){alert(tempvalue);return;}
			YahooWin5Hide();
			}			
		
		function CHRootPwd(){
			var pass=document.getElementById('root-pass1').value;
			if(pass.length<3){alert('$root_error_pass');return;}
			if(document.getElementById('root-pass1').value!==document.getElementById('root-pass2').value){alert('$root_error_pass');return;}
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('root-pass1').value);
			XHR.appendData('change_password',document.getElementById('root-pass1').value);
			if(document.getElementById('PAMLdapPrio').checked){XHR.appendData('PAMLdapPrio',1);}else{XHR.appendData('PAMLdapPrio',0);}
			AnimateDiv('animate-root');
			XHR.sendAndLoad('$page', 'POST',X_CHRootPwd);
		}
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function change(){
	$sock=new sockets();
	$sock->SET_INFO("PAMLdapPrio", $_POST["PAMLdapPrio"]);
	
	
	
	if(strlen(trim($_POST["change_password"]))>1){
			$_POST["change_password"]=url_decode_special_tool($_POST["change_password"]);
			
			include_once(dirname(__FILE__))."/ressources/class.samba.inc";
			$smb=new samba();
			if(!$smb->createRootID($_POST["change_password"])){
				return;
			}
			

			$sock->SET_INFO("RootPasswordChanged", 1);
			writelogs(" -> nsswitch ",__FUNCTION__,__FILE__,__LINE__);
			$sock->getFrameWork("services.php?nsswitch=yes");
			$change_password=url_decode_special($_POST["change_password"]);
			$changeRootPasswd=base64_encode($change_password);
			writelogs(" -> services.php?changeRootPasswd= ",__FUNCTION__,__FILE__,__LINE__);
			echo base64_decode($sock->getFrameWork("services.php?changeRootPasswd=$changeRootPasswd&pass=$changeRootPasswd"));			
			
			
	}		
}