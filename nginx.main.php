<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}

tabs();


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();


	$array["websites"]="{websites}";
	$array["destinations"]='{destinations}';
	$array["caches"]='{caches}';
	$array["events"]='{events}';


	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){

		if($num=="websites"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.www.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="destinations"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.destinations.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}


		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes\"><span >$ligne</span></a></li>\n";
			
	}



	$t=time();
	//

	echo build_artica_tabs($tab, "main_artica_nginx",1100)."<script>LeftDesign('reverse-proxy-256-white.png');</script>";

}