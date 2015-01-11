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
if(!$users->AsMailBoxAdministrator){die();}
if(isset($_GET["status"])){status();exit;}
tabs();

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();


	$array["{status}"]="$page?status=yes";
	echo $boot->build_tab($array);


}

function status(){
	$sock=new sockets();
	$version=base64_decode($sock->getFrameWork("zarafa.php?webaccess-version=yes"));
	$WebAPPVersion=base64_decode($sock->getFrameWork("zarafa.php?webapp-version=yes"));
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top' width=296px><img src='img/webaccess-256.png' style='margin-right:15px'></td>
		<td valign='top' style='vertical-align:top'><div style='font-size:22px'>Zarafa Web-Access V$version</div>
		<div class=text-info style='font-size:16px;margin-top:15px'>{APP_ZARAFA_WEBACCESS_TEXT}</div>
		<div style='text-align:right'>". button("{manual_update}", "Loadjs('miniadmin.zarafa-webaccess.update.php')")."</div>
		</td>
	</tr>
	<tr>
		<td valign='top' style='vertical-align:top' width=296px><img src='img/webaccess-256.png' style='margin-right:15px'></td>
		<td valign='top' style='vertical-align:top'><div style='font-size:22px'>Zarafa Web-APP V$WebAPPVersion</div>
		<div class=text-info style='font-size:16px;margin-top:15px'>{APP_ZARAFA_WEBAPP_TEXT}</div>
		<div style='text-align:right'>". button("{manual_update}", "Loadjs('miniadmin.zarafa-webapp.update.php')")."</div>
		</td>
	</tr>				
	</table>	
			
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
