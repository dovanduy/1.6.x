<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["apply-js"])){apply_js();exit;}
if(isset($_GET["apply-popup"])){apply_popup();exit;}
if(isset($_GET["apply-next"])){apply_next();exit;}
if(isset($_POST["shorewall-progress"])){apply_progress();exit;}
if(isset($_POST["shorewall-restart"])){apply_restart();exit;}
tabs();	

function tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$array["providers"]='{providers}';
	




	$fontsize=14;

	while (list ($num, $ligne) = each ($array) ){
		if($num=="providers"){
			$tab[]="<li><a href=\"shorewall.providers.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="interfaces"){
			$tab[]="<li><a href=\"shorewall.interfaces.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="policies"){
			$tab[]="<li><a href=\"shorewall.policies.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}


		if($num=="rules"){
			$tab[]="<li><a href=\"shorewall.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="localservices"){
			$tab[]="<li><a href=\"shorewall.localservices.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="route"){
			$tab[]="<li><a href=\"shorewall.routes.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}

	}

	echo build_artica_tabs($tab, "main_shorewall_routes",890);
}
