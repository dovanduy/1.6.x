<?php
session_start();
$servers=$_SESSION["VIRTUALS_SERVERS"];
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!isset($servers[$_REQUEST["hostname"]])){header("location:miniadm.index.php");die();}
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
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&hostname={$_REQUEST["hostname"]}')</script>", $content);
	echo $content;	
}


function content(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{$_GET["hostname"]}</H1>
		<p>{manage_this_file_server}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?content2=yes&hostname={$_REQUEST["hostname"]}');
	</script>
	";
		
	$html=$tpl->_ENGINE_parse_body($html);
	
	echo $html;
}




function content2(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$tpl=new templates();
	$hostname=$_GET["hostname"];
	$array["shares"]='{shared_folders}';
	$array["fsopt"]='{file_sharing_behavior}';
	while (list ($num, $ligne) = each ($array) ){
		if($num=="shares"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"miniadm.samba-virtual.shares.php?hostname={$_GET["hostname"]}&t=$t\"><span style='font-size:16px'>$ligne</span></a></li>\n");
			continue;
		}
			
		if($num=="fsopt"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"miniadm.samba-virtual.options.php?hostname={$_GET["hostname"]}&t=$t\"><span style='font-size:16px'>$ligne</span></a></li>\n");
			continue;
		}
			
			
	
			
			
	}
	
	
	echo "
	<div class=BodyContent>
	<div id=main_config_virtsamba style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_virtsamba').tabs();
			});
		</script>";		
}


