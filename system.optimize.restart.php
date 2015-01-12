<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_POST["Reboot"])){Reboot();exit;}
if(isset($_GET["popup"])){popup();exit;}


js();


function js(){
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{reboot}");
	
	echo "
	function Start$t(){
		RTMMail('800','$page?popup=yes','$title');
	}
	Start$t();";
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="<div style='margin:15px;padding:20px;width:91%' class=form>
	<center style='margin:30px'><img src='img/reboot-256.png'></center>
	<p>&nbsp;</p>
	<center style='font-size:22px;margin:26px'>{you_need_to_reboot_in_order_to_finish}</center>
		
	<center style='margin:20px'>". button("{reboot}","Reboot$t()",45)."</center>
	</div>
			
	<script>
var xReboot$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RTMMailHide();
	window.location.href='logoff.php';
}	
		
function Reboot$t(){
	var XHR = new XHRConnection();
	XHR.appendData('Reboot',document.getElementById('EnableSystemOptimize').value);
	XHR.sendAndLoad('$page', 'POST',xReboot$t);
}
</script>";
			
	echo $tpl->_ENGINE_parse_body($html);
			
	
}

function Reboot(){
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?system-defrag=yes");
}
