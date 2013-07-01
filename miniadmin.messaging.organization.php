<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["messaging-stats"])){messaging_stats();exit;}
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
	$q=new mysql_postfix_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT DATE_SUB(NOW(),INTERVAL 1 HOUR) as tdate"));
	$currenthour=date("YmdH",strtotime($ligne["tdate"]))."_hour";
	
	
	$rows=$q->COUNT_ROWS($currenthour);
	$jsadd=null;
	if($rows>0){$jsadd="LoadAjax('statistics-$t','$page?messaging-stats=yes');";}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>&laquo;{$_SESSION["ou"]}&raquo; {messaging}</H1>
		<p>{mymessaging_text}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?tabs=yes');
		//LoadAjax('messaging-left','domains.manage.org.index.php?org_section=postfix&SwitchOrgTabs={$_SESSION["ou"]}&ou={$_SESSION["ou"]}&mem=yes&dn=');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{localdomains}"]="miniadm.messaging.domains.php";
	$array["{parameters}"]="domains.manage.org.index.php?org_section=postfix&SwitchOrgTabs={$_SESSION["ou"]}&ou={$_SESSION["ou"]}&mem=yes&dn=&miniadm=yes";
	echo $boot->build_tab($array);
	
	
}

