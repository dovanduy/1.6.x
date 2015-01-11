<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');


	if(isset($_GET["about"])){about();exit;}
	if(isset($_GET["schedules"])){schedules();exit;}


tabs();


function tabs(){
	$tpl=new templates();
	$array["about"]='{about2}';
	$array["schedules"]='{schedules}';
	$array["events"]='{events}';
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="schedules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=76\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.automation-events.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$time\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_postfix_automation");
}


function about(){
	$tpl=new templates();
	$html="<div style='font-size:18px' class=text-info>{postfix_automation_about}</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

function schedules(){
	
	
}