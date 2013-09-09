<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


$users=new usersMenus();
if(!Privileges_members_admins()){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search-events"])){events_table();exit;}


main_page();
//archiverlogs

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
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{users_and_groups}</H1>
		<p>{users_and_groups_system_explain}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$boot=new boostrap_form();
	$mini=new miniadm();
	$users=new usersMenus();
	$ldap=new clladp();
	$tpl=new templates();
	
	if(isset($_GET["title"])){
		$title=$tpl->_ENGINE_parse_body("<H3>{users_and_groups}</H3><p>{users_and_groups_system_explain}</p>");
	}
	
	
	
	if($ldap->IsKerbAuth()){
		$array["{activedirectory_members}"]="miniadm.members.browse.php?section-search-ad=yes";
		
	}
	
	
	$array["{radius_members}"]="miniadm.system.members.radius.php";
	
	if($mini->IFItsProxy()){
		$array["{hostpot_members}"]="miniadmin.hotspot.php?tabs=yes&title=yes";
	}

	echo $title.$boot->build_tab($array);
}
