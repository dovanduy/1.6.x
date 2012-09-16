<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["MailingListUseLdap"])){SaveMIL();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{mailing_list_behavior}");
	$html="YahooWin3('500','$page?popup=yes','$title')";
	echo $html;
}
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
	if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{EnableDNSMASQLDAPDB}:</td>
		<td>". Field_checkbox("MailingListUseLdap", 1,$MailingListUseLdap)."</td>
		<td>". help_icon("{mailing_list_behavior_ldap_explain}")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveMilingListBehavior()",16)."</td>
	</tr>
	</table>
	<script>
	var X_SaveMilingListBehavior= function (obj) {
	var results=trim(obj.responseText);
	if(results.length>0){alert(results);}
	YahooWin3Hide();
	}
		
	function SaveMilingListBehavior(){
		var XHR = new XHRConnection();
		if(document.getElementById('MailingListUseLdap').checked){XHR.appendData('MailingListUseLdap',1);}else{	XHR.appendData('MailingListUseLdap',0);}
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SaveMilingListBehavior);				
	}	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveMIL(){
	$sock=new sockets();
	$sock->SET_INFO("MailingListUseLdap", $_POST["MailingListUseLdap"]);
	$sock->getFrameWork("cmd.php?SaveMaincf=yes");	
	$sock->getFrameWork("cmd.php?postfix-hash-tables=yes");
	
}


