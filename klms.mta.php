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
	if(isset($_POST["EnablePostfixKLMS"])){EnablePostfixKLMS();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tasks=$tpl->_ENGINE_parse_body("{mta_link}");
	$title="Kaspersky Mail Security Suite:$tasks";
	echo "YahooWin3('550','$page?popup=yes','$title')";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$EnablePostfixKLMS=$sock->GET_INFO("EnablePostfixKLMS");
	if(!is_numeric($EnablePostfixKLMS)){$EnablePostfixKLMS=0;}
	$t=time();
	$p=Paragraphe_switch_img("{connect_klms_to_mta}", "{connect_klms_to_mta_text}","EnablePostfixKLMS-$t",$EnablePostfixKLMS,null,450);
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
		<td align='right'><hr>". button("{apply}","EnablePostfixKLMSSave()","16px")."</td>
	</tr>
	</table>
	<script>
		var x_EnablePostfixKLMSSave=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('img_EnablePostfixKLMS-$t').src='';
	      YahooWin3Hide();
	      }		
		
		function EnablePostfixKLMSSave(){
				var XHR = new XHRConnection();
				XHR.appendData('EnablePostfixKLMS',document.getElementById('EnablePostfixKLMS-$t').value);
				document.getElementById('img_EnablePostfixKLMS-$t').src='img/wait_verybig_mini_red-48.gif';
				XHR.sendAndLoad('$page', 'POST',x_EnablePostfixKLMSSave);	
		}
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnablePostfixKLMS(){
	$sock=new sockets();
	$sock->SET_INFO("EnablePostfixKLMS", $_POST["EnablePostfixKLMS"]);
	$sock->getFrameWork("klms.php?build=yes");
}
