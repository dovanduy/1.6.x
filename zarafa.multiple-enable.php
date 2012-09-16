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
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableZarafaMulti"])){EnableZarafaMulti();exit;}
	
js();


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{multiple_zarafa_instances}");
	$html="YahooWin2('550','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");
	if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="0.0.0.0";}
	$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	
	/*if($ZarafaGatewayBind=="0.0.0.0"){
		$html="<H2 style='font-size:16px'>{unable_to_perform_operation_zarafa_bind_all_addresses}</H2>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}*/
	$t=time();
	$p=Paragraphe_switch_img("{enable_zarafa_multi}", "{enable_zarafa_multi_text}","EnableZarafaMulti",$EnableZarafaMulti,null,530);
	

	$postfix=Paragraphe_switch_img("{ENABLE_POSTFIX_MULTI_INSTANCE}:{APP_POSTFIX}","{POSTFIX_MULTI_INSTANCE_TEXT}",
	"EnablePostfixMultiInstance$t",$EnablePostfixMultiInstance,null,530);	
	
	
	$t=time();
	$html="
	<div id='$t'>
	$p
	<br>
	$postfix
	<hr>
	<div style='width:100%;text-align:right'>". button("{apply}","SaveEnableZarafaMulti()",16)."</div>
	</div>
	<script>
var x_SaveEnableZarafaMulti=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>5){alert(tempvalue);}
      YahooWin2Hide();
      QuickLinkSystems('section_zarafa')
      }	
		
	function SaveEnableZarafaMulti(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableZarafaMulti',document.getElementById('EnableZarafaMulti').value);
		XHR.appendData('EnablePostfixMultiInstance',document.getElementById('EnablePostfixMultiInstance$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveEnableZarafaMulti);
	}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function EnableZarafaMulti(){
	$sock=new sockets();
	$sock->SET_INFO("EnableZarafaMulti",$_POST["EnableZarafaMulti"]);
	$sock->SET_INFO("EnablePostfixMultiInstance",$_POST["EnablePostfixMultiInstance"]);
	
	
	if($_POST["EnablePostfixMultiInstance"]==0){$sock->getFrameWork("cmd.php?postfix-multi-disable=yes");return;}
	$sock->getFrameWork("cmd.php?restart-postfix-single=yes");	
	
}

