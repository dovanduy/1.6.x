<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");



if(isset($_GET["localdomain-section"])){localdomain_section();exit;}
if(isset($_GET["localdomain-search"])){localdomain_search();exit;}
if(isset($_GET["localdomain-new-js"])){localdomain_add_js();exit;}
if(isset($_GET["localdomain-new"])){localdomain_add();exit;}
if(isset($_POST["localdomain"])){localdomain_save();exit;}
if(isset($_POST["localdomain-remove"])){localdomain_remove();exit;}

if(isset($_GET["remotedomain-section"])){remotedomain_section();exit;}
if(isset($_GET["remotedomain-search"])){remotedomain_search();exit;}
if(isset($_GET["remotedomain-new-js"])){remotedomain_add_js();exit;}
if(isset($_GET["remotedomain-new"])){remotedomain_add();exit;}
if(isset($_POST["remotedomain"])){remotedomain_save();exit;}
if(isset($_POST["remotedomain-remove"])){remotedomain_remove();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["buildpage"])){page();exit;}
if(isset($_GET["content"])){content();exit;}
page();

function page(){
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
	
	
	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
	<H1>{APP_POSTFIX}</H1>
	<p>{APP_POSTFIX_TEXT}</p>
	</div>
	<div id='messaging-left'></div>
	
	<script>
	LoadAjax('messaging-left','$page?tabs=yes&notitle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function VerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AllowChangeDomains){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return false;}
}

function tabs(){
	$tpl=new templates();
	if($_SESSION["ou"]<>null){$subtitle=":{$_SESSION["ou"]}";}
	$users=new usersMenus();
	if(!VerifyRights()){senderrors("no rights");}

	
	$array["{infrastructure}"]="miniadm.messaging.infrastructure.php?tabs=yes";
	$array["{domains}"]="miniadm.messaging.domains.php?tabs=yes";
	$array["{archive_module}"]="miniadmin.postfix.archive.php?popup=yes&title=yes";
	$array["{events}"]="miniadmin.postfix.events.php?section=yes";

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}