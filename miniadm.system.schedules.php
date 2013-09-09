<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}



tabs();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function tabs(){
	$page=CurrentPageName();
	$array["{tasks}"]="miniadm.system.schedules-engine.php";
	$array["{parameters}"]="$page?parameters=yes";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$users=new usersMenus();
	$start="LoadAjax('tabs-$t','$page?tabs=yes');";
	
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{manage_system}</H1>
		<p>{manage_system_text}</p>
	</div>	
	<div id='tabs-$t' class=BodyContent></div>
	
	<script>
		$start
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
