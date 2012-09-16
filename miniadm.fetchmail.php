<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
	if(!is_numeric($EnableFetchmail)){$EnableFetchmail=0;}
	
	if(!$users->AllowFetchMails){header("location:miniadm.messaging.php");die();}
	if(!$users->fetchmail_installed){header("location:miniadm.messaging.php");die();}
	if($EnableFetchmail==0){header("location:miniadm.messaging.php");die();}
	


if(isset($_GET["content"])){content();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
$html="<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		<H1>{myretreival_mailrules}</H1>
		<p>{retreival_mailrules_text}</p>
		
	</div>
<div class=BodyContentWork id='$t'></div>

<script>LoadAjax('$t','fetchmail.user.php?uid={$_SESSION["uid"]}&expanded=usermin')</script>

";	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}



