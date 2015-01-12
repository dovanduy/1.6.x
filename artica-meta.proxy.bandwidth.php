<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


if(isset($_GET["js"])){js();exit;}

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();}


tabs();


function tabs(){
	
	$uuid=$_GET["uuid"];
	$fontsize=18;
	$tpl=new templates();
	$page=CurrentPageName();
	$array["RTT"]="{realtime}";
	$array["WEEK"]="{this_week}";
	$array["websites"]="{websites}";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="RTT"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rtt.week.php?uuid=$uuid&meta=1\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="WEEK"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rttw.week.php?uuid=$uuid&meta=1\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="websites"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rweb.week.php?uuid=$uuid&meta=1\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_bandwidth_meta_tabs');
	echo $html;
	
}