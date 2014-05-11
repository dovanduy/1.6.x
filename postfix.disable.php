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
	if(isset($_POST["DisableMessaging"])){Save();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{disable_messaging}");
	echo "YahooWin3('650','$page?popup=yes','$title')";
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
	$t=time();
	$page=CurrentPageName();
	$p=Paragraphe_switch_img("{disable_messaging}", "{disable_messaging_explain}","DisableMessaging",$DisableMessaging,null,550);
	
	$html="<div style='width:98%' class=form>
		$p
		<div style='width:100%;text-align:right'>
		<hr>". button("{apply}", "Save$t()",26)	."</div>
			
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){
		document.location.href='logoff.php';
	}
	
}
		
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('DisableMessaging',document.getElementById('DisableMessaging').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);				
	}
</script>				
				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$tpl=new templates();
	$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
	if($DisableMessaging<>$_POST["DisableMessaging"]){
		$sock->SET_INFO("DisableMessaging", $_POST["DisableMessaging"]);
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
		echo $tpl->javascript_parse_text("{you_will_be_disconnected}");
	}
}



