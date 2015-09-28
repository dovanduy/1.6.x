<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$html="<div style='width:98%' class=form>
			<div style='font-size:18px' class=explain>{squid_articadb_restore_explain}</div>
			
			<center style='margin:30px'>". button("{upload_backuped_container}",
					"Loadjs('squid.articadb.restore.upload.php')",32)."</center>
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
