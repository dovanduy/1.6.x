<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["force"])){$GLOBALS["FORCE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();	}
if(isset($_POST["SquidServerPersistentConnections"])){Save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidClientPersistentConnections=intval($sock->GET_INFO("SquidClientPersistentConnections"));
	$SquidServerPersistentConnections=intval($sock->GET_INFO("SquidServerPersistentConnections"));
	$t=time();
	$html="
	<div style='font-size:26px;margin-bottom:20px'>{persistent_connections}</div>
	<div class=text-info style='font-size:18px'>{persistent_connections_explain}</div>			
	<p>&nbsp;</p>
	". Paragraphe_switch_img("{server_persistent_connections}", "{server_persistent_connections_explain}"
	,"SquidServerPersistentConnections",$SquidServerPersistentConnections,null,750		
	)."<p>&nbsp;</p>". Paragraphe_switch_img("{client_persistent_connections}", "{client_persistent_connections_explain}"
	,"SquidClientPersistentConnections",$SquidClientPersistentConnections,null,750		
	).
	"<div style='margin-top:20px;text-align:right'>". button("{apply}","Save$t()",32)."</div>
	<script>
		var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
		RefreshTab('squid_main_performance');
	
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidServerPersistentConnections',document.getElementById('SquidServerPersistentConnections').value);
		XHR.appendData('SquidClientPersistentConnections',document.getElementById('SquidClientPersistentConnections').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidServerPersistentConnections", $_POST["SquidServerPersistentConnections"]);
	$sock->SET_INFO("SquidClientPersistentConnections", $_POST["SquidClientPersistentConnections"]);
	
}