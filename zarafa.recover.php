<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	
	
	if(isset($_POST["recovery"])){recovery();exit;}	
	
	
	
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	$warn=$tpl->javascript_parse_text("{zarafa_database_recovery_warn}");
	$t=time();
	$html="
	var x_Zarafa$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		
		
	}	
		
	
	function Zarafa$t(){
		var XHR = new XHRConnection();
		if(!confirm('$warn')){return;}
		
		XHR.appendData('recovery','yes');
		XHR.sendAndLoad('$page', 'POST',x_Zarafa$t);
		}

	
	Zarafa$t()";
	
	
}	


function ZarafaSave(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?recover-last=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{recovery_background_task}");
}

