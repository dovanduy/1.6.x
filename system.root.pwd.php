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
	$html="YahooWin5(900,'$page?popup=yes','$title');";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$root_error_pass=$tpl->javascript_parse_text("{root_error_pass}");
	$sock=new sockets();
	$nsswitchEnableLdap=intval($sock->GET_INFO("nsswitchEnableLdap"));
	
	$html="
	<div style='width:98%' class=form>
		<div style='font-size:32px;margin-bottom:30px'>{root_password_not_changed}</div>
		<div class=explain style='margin-top:10px;font-size:20px'>{root_password_not_changed_text}</div>
		<table style='width:98%' >
		<tr>
			<td class=legend style='font-size:32px'>{password}:</td>
			<td>". Field_password("root-pass1",null,"font-size:32px;padding:20px;width:70%",null,null,null,false,"CHRootPwdCheck(event)")."</td>
		</tr>
			<td>&nbsp;</td>
			<td>". Field_password("root-pass2",null,"font-size:32px;padding:20px;width:70%",null,null,null,false,"CHRootPwdCheck(event)")."</td>
		<tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","CHRootPwd()",42)."</td>
		</tr>
		</table>
	</div>
		
		<script>
			function CHRootPwdCheck(e){
				if(checkEnter(e)){CHRootPwd();return;}
			}
			
		var X_CHRootPwd= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;}
			YahooWin5Hide();
			CacheOff();
			}			
		
		function CHRootPwd(){
			var pass=document.getElementById('root-pass1').value;
			if(pass.length<3){alert('$root_error_pass');return;}
			if(document.getElementById('root-pass1').value!==document.getElementById('root-pass2').value){alert('$root_error_pass');return;}
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('root-pass1').value);
			XHR.appendData('change_password',document.getElementById('root-pass1').value);
			XHR.sendAndLoad('$page', 'POST',X_CHRootPwd);
		}
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function change(){
	$sock=new sockets();
	$nsswitchEnableLdap=intval($sock->GET_INFO("nsswitchEnableLdap"));
	
	if(strpos(" {$_POST["change_password"]}", ":")>0){
		echo "`:` not supported !\n";
		return;
	}
	
	if(strlen(trim($_POST["change_password"]))>1){
			$_POST["change_password"]=url_decode_special_tool($_POST["change_password"]);
			
			if($nsswitchEnableLdap==1){
				include_once(dirname(__FILE__))."/ressources/class.samba.inc";
				$smb=new samba();
				if(!$smb->createRootID($_POST["change_password"])){
					return;
				}
			}
			$sock->SET_INFO("RootPasswordChanged", 1);
			$change_password=url_decode_special($_POST["change_password"]);
			$changeRootPasswd=base64_encode($change_password);
			writelogs(" -> services.php?changeRootPasswd= ",__FUNCTION__,__FILE__,__LINE__);
			echo base64_decode($sock->getFrameWork("services.php?changeRootPasswd=$changeRootPasswd&pass=$changeRootPasswd"));			
			
			
	}		
}