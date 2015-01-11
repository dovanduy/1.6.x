<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["PASSWORD"])){PASSWORD();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tasks=$tpl->_ENGINE_parse_body("{reset_admin_password}");
	$title="Kaspersky Mail Security Suite:$tasks";
	echo "YahooWin3('550','$page?popup=yes','$title')";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center id='anim-$t'></center>
	<div class=text-info style='font-size:14px'>{reset_admin_password_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{username}</td>
		<td style='font-size:16px;font-weight:bold'>Administrator</td>
	</tr>
						
	<tr>
		<td class=legend style='font-size:16px'>{password}</td>
		<td>". Field_password("PASSWORD-$t",null,"font-size:16px;width:70%;font-weight:bold",null,null,null,false,"CheckPass$t(event)")."</td>
	</tr>
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}","EnablePostfixKLMSSave$t()","16px")."</td>
	</tr>
	</table>
	<script>
		var x_EnablePostfixKLMSSave$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('anim-$t').innerHTML='';
	      YahooWin3Hide();
	      }		
		
		function EnablePostfixKLMSSave$t(){
				var XHR = new XHRConnection();
				var pp=encodeURIComponent(document.getElementById('PASSWORD-$t').value);
				AnimateDiv('anim-$t');
				XHR.appendData('PASSWORD',pp);
				XHR.sendAndLoad('$page', 'POST',x_EnablePostfixKLMSSave$t);	
		}
				
				
		function CheckPass$t(e){
				if(checkEnter(e)){EnablePostfixKLMSSave$t();}
				
		}
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function PASSWORD(){
	$sock=new sockets();
	$_POST["PASSWORD"]=base64_encode(url_decode_special_tool($_POST["PASSWORD"]));
	echo "results:\n".trim(@implode("\n", unserialize(base64_decode($sock->getFrameWork("klms.php?reset-password={$_POST["PASSWORD"]}")))));
}
