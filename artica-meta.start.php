<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();

}

tabs();
function tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$array["hosts"]='{hosts}';
	$array["groups"]='{groups2}';
	$array["server-params"]='{main_parameters}';
	while (list ($num, $ligne) = each ($array) ){

		if($num=="hosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.hosts.php\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="groups"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.groups.php\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="server-params"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.php?parameters=yes\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}		
		

		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "meta-start",1225);

}
