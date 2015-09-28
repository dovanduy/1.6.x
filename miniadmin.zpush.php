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
	$zpush_version=base64_decode($sock->getFrameWork("zarafa.php?zpush-version=yes"));
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top'><img src='img/smartphone-256.png' style='margin-right:15px'></td>
		<td valign='top' style='vertical-align:top'><div style='font-size:22px'>Z-Push V$zpush_version</div>
		<div class=explain style='font-size:16px;margin-top:15px'>{APP_Z_PUSH_TEXT}</div>
		
		</td>
	</tr>
	</table>	
			
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
