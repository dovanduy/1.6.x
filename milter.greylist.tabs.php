<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.milter.greylist.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.maincf.multi.inc');

if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}

$user=new usersMenus();
if(!isset($_GET["hostname"])){
	if($user->AsPostfixAdministrator==false){header('location:users.index.php');exit();}
}else{
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{$_GET["hostname"]}::{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
}

main_tabs();


function main_tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$array["index"]='{status}';
	$array["popup-settings"]="{main_settings}";
	$array["popup-dumpdb"]='{items}';
	$array["events"]='{events}';



	if(isset($_GET["expand"])){$expdand="&expand=yes";}
	$_GET["ou"]=urlencode($_GET["ou"]);

	while (list ($num, $ligne) = each ($array) ){

		if($num=="popup-settings"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:22px'>
					<a href=\"milter.greylist.index.php?popup-settings=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span>
					</a></li>
					\n");
					continue;
	}

	if($num=="popup-groups"){
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:22px'><a href=\"milter.greylist.objects.php?hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
		continue;
	}
	if($num=="events"){
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:22px'><a href=\"syslog.php?popup=yes&prepend=milter-greylist\"><span>$ligne</span></a></li>\n");
		continue;
	}
	$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:22px'><a href=\"milter.greylist.index.php?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}$expdand\"><span>$ligne</span></a></li>\n");
	}


	echo build_artica_tabs($html, "main_config_mgreylist",1498);

}