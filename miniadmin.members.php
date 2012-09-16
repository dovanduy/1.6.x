<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$users=new usersMenus();
if(!$users->AsOrgAdmin){header("location:miniadm.messaging.php");die();}
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
	$users=new usersMenus();
	$ct=new user($_SESSION["uid"]);
	$t=time();
	
	$ouencoded=base64_encode($_SESSION["ou"]);
	
	$html="
	<div class=BodyContent>
		<table style='width:100%'>
		<tr>
		<td valign='top'>$picture</td>
		<td valign='top'>
		<H1>{my_members} {organization} {$_SESSION["ou"]}</H1>
		<p>{manage_users_and_groups_ou_explain}</p>
		</td>
		</tr>
		</table>
	</div>
	<div class=BodyContent>
		<div id='anim-$t'></div>
	</div>
	
<script>
	LoadAjax('anim-$t','domains.manage.org.findusers.php?ou=$ouencoded&end-user-interface=yes');
</script>	
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

