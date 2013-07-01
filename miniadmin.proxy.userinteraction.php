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

$users=new usersMenus();
tabs();


function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();

	$mini=new boostrap_form();
	$array["{parameters}"]="squid.main.quicklinks.php?architecture-users=yes";
	$array["{IT_charter}"]="miniadmin.proxy.charter.php?tabs=yes";
	echo $mini->build_tab($array);
}
