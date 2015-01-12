<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["download-js"])){download_js();exit;}
	if(isset($_GET["download-popup"])){download_popup();exit;}
	
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$bt1=button("{backup}", "Loadjs('nginx.backup.progress.php');",42);
	$bt2=button("{restore}", "Loadjs('nginx.restore.upload.php');",42);
	
	$html="<div style='width:98%' class=form>
			<center style='margin:30px'>$bt1</center>
			<center style='margin:30px'>$bt2</center>
		</siv>	
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function download_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin6(550,'$page?download-popup=yes','nginx.dump.gz');";	
	
}
function download_popup(){
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/nginx.dump.gz")){
		echo FATAL_ERROR_SHOW_128("nginx.dump.gz no such file!");
		return;
	}
	
	$size=@filesize("ressources/logs/web/nginx.dump.gz");
	$size=FormatBytes($size/1024);
	
	echo "<center style='margin:30px'><a href='ressources/logs/web/nginx.dump.gz'><img src='img/download-64.png'></a>
	<center style='margin:30px'>
		<a href='ressources/logs/web/nginx.dump.gz' style='font-size:18px;text-decoration:underline'>nginx.dump.gz ($size)</a>
	</center>";
	
}



