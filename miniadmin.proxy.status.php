<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if($GLOBALS["VERBOSE"]){
	if(!$users->AsProxyMonitor){
		echo "<H1>AsProxyMonitor = FALSE</H1>";
		return;
	
	}else{
		echo "<H1>AsProxyMonitor = TRUE</H1>";
	}
}
if(!$users->AsProxyMonitor){header("location:miniadm.logon.php");}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["table"])){table();exit;}




main_page();
exit;


if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function content(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{proxy_status}</H1>
	<p>{proxy_status_text}</p>
	<div id='$t-status'></div>
	<script>
		LoadAjax('$t-status','squid.main.quicklinks.php?status=yes');
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);


}
