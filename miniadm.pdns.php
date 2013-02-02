<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!checkrights()){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.network.php\">{network_services}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.pdns.php\">{APP_PDNS}</a>
		</div>
		<H1>{APP_PDNS}</H1>
		<p>{APP_PDNS_TEXT}</p>
	</div>	
	<div id='webstats-middle-$ff' class=BodyContent></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$html="<div id='BodyContent'></div><script>LoadAjax('BodyContent','pdns.php?tabs=yes&expand=yes');</script>";
	echo $html;
}
function checkrights(){
	if($_SESSION["AsOrgDNSAdmin"]){return true;}
	if($_SESSION["AsDnsAdministrator"]){return true;}
	
}	
