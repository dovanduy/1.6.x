<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(count($_SESSION["VIRTUALS_SERVERS"])<2){header("location:miniadm.index.php");die();}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["content2"])){content2();exit;}
main_page();

function main_page(){
	//annee=2012&mois=9&jour=22
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{file_sharing_services}</H1>
		<p>{file_sharing_services_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?content2=yes');
	</script>
	";
		
	$html=$tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;
}




function content2(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$tpl=new templates();
	$servers=$_SESSION["VIRTUALS_SERVERS"];
	
	
	
	while (list ($key, $val) = each ($servers) ){
		$tR[]=Paragraphe("blue-server.png", "$key","{manage_this_file_server}","miniadm.samba-virtual.php?hostname=$key");
	}
	


	$content=CompileTr3($tR,"none");
	$html="<div class=BodyContent><center><div style='width:700px'>$content</div></center></div>";
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}


