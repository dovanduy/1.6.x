<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_POST["logrotate"])){logrotate();exit;}

	js();

	
function js(){
	$img=$_GET["img"];
	$src=$_GET["src"];
	$page=CurrentPageName();
	$html="
	var x_exec_squid_logrotate=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		if(document.getElementById('$img')){document.getElementById('$img').src='img/$src';}
	}	
	
	function exec_squid_logrotate(){
		var XHR = new XHRConnection();
		XHR.appendData('logrotate','yes');
		if(document.getElementById('$img')){document.getElementById('$img').src='img/wait.gif';}
		XHR.sendAndLoad('$page', 'POST',x_exec_squid_logrotate);	
	}
	exec_squid_logrotate();";
	
	echo $html;
} 

function logrotate(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?logrotate=yes");
	
}