<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.main_cf.inc');

	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["EnableSasl"])){Save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{SASL_TITLE}");
	$html=
	"
	function SASL_MULTI_START(){	
		YahooWin3(790,'$page?popup=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}','$title');
	} 
	
var x_postfix_multi_enable_auth= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	SASL_MULTI_START(); 
	}	
	
	function SaslStatus(){
		YahooWin3(650,'$page?popup-auth-status=yes','SASL...',''); 
		
	}
	
	function SasladvOptions(){
		YahooWin3(550,'$page?popup-auth-adv=yes','SASL...',''); 
		
	}	
	
	function postfix_multi_enable_auth(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableSasl',document.getElementById('EnableSasl').value);
		XHR.appendData('EnableSubmission',document.getElementById('EnableSubmission').value);
		XHR.appendData('TrustMyNetwork',document.getElementById('TrustMyNetwork').value);
		
		
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('EnableSubmission',document.getElementById('EnableSubmission').value);
		document.getElementById('img_EnableSasl').src='img/wait_verybig.gif';
		document.getElementById('img_EnableSubmission').src='img/wait_verybig.gif';
		document.getElementById('img_TrustMyNetwork').src='img/wait_verybig.gif';
		XHR.sendAndLoad('$page', 'GET',x_postfix_multi_enable_auth);	
	
	}
	
	SASL_MULTI_START();";
	echo $html;
	}	
	
function Save(){
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$main->SET_VALUE("EnableSubmission",$_GET["EnableSubmission"]);
	$main->SET_VALUE("EnableSasl",$_GET["EnableSasl"]);
	$main->SET_VALUE("TrustMyNetwork",$_GET["TrustMyNetwork"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-sasl={$_GET["hostname"]}");		
	
}
	
function popup(){
	$sock=new sockets();
	$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
	if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}	
	
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$EnableSubmission=$main->GET("EnableSubmission");
	$TrustMyNetwork=$main->GET("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	$EnableSasl=$main->GET("EnableSasl");
	$sasl=Paragraphe_switch_img('{sasl_title}','{sasl_intro}','EnableSasl',$EnableSasl,'{enable_disable}',300);
	
	
	if($PostfixMultiCreateBubble==1){
		$TrustMyNetwork=
		"<input type=hidden id='TrustMyNetwork' name=\"TrustMyNetwork\" value='$TrustMyNetwork'>".
		Paragraphe_switch_disable('{TrustMyNetwork}','{TrustMyNetwork_text}<hr><i style=color:#9E0000>{PostfixMultiCreateBubbleIsEnabled}</i>','{TrustMyNetwork_text}',300);
	}else{
		$TrustMyNetwork=Paragraphe_switch_img('{TrustMyNetwork}','{TrustMyNetwork_text}','TrustMyNetwork',$TrustMyNetwork,'{enable_disable}',300);
	}
	
	
	$PostfixEnableSubmission_field=Paragraphe_switch_img('{PostfixEnableSubmission}','{PostfixEnableSubmission_text}','EnableSubmission',$EnableSubmission,'{enable_disable}',300);
	
	$old="" . Paragraphe("64-settings-black.png","{SASL_STATUS}","{SASL_STATUS_TEXT}","javascript:SaslStatus();")."
			$settings
			$smtpd_sasl_exceptions_networks";
			
	
$html="
	<div id='sasl-id'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td valign='top'>
			$sasl
			<div style='text-align:right'><hr>". button("{edit}","postfix_multi_enable_auth()",16). "</div>
		</td>
	<td valign='top'>
		$TrustMyNetwork
		<div style='text-align:right'><hr>". button("{edit}","postfix_multi_enable_auth()",16). "</div>
	</td>
	</tr>
	<tr>
	<td colspan=2>
		$PostfixEnableSubmission_field
		<div style='text-align:right'><hr>". button("{edit}","postfix_multi_enable_auth()",16). "</div>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	";



	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'postfix.sasl.php');	
}	
?>