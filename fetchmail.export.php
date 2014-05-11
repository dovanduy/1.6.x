<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.fetchmail.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["action"])){action();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_FETCHMAIL}::{export}");
	echo "YahooWin4('410','$page?action=yes&t={$_GET["t"]}','$title');";
}

function action(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?export-table=yes");
	$target_file="/usr/share/artica-postfix/ressources/logs/fetchmail-export.gz";
	if(!is_file($target_file)){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{failed}","fetchmail-export.gz"));
		return;
		
	}
	
	$size=FormatBytes(@filesize($target_file)/1024);
	
	echo "<center style='margin:10px;padding:10px'>
			<a href='ressources/logs/fetchmail-export.gz'><img src='img/128-import.png'><p>&nbsp;</p>
			<span style='font-size:22px;text-decoration:underline'>". basename($target_file)." - $size</span></a>
			</center>
			";
	
}