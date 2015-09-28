<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ldap.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<script>alert('$alert');</script>";
	die();
}

tabs();


function tabs(){
	
	$tpl=new templates();
	$array["status"]='{status}';
	//$array["rules"]='{webfiltering}';
	$array["daemons"]='{daemon_settings}';
	$array["clamav"]='ClamAV Antivirus';
	$array["events"]='{events}';
	
	$fontsize="22";
		
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rules"){
			$html[]= "<li><a href=\"c-icap.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.index.php?main=$num&t=$t\">
				 <span style='font-size:{$fontsize}px;'>$ligne</span></a></li>\n");
	}



	$html=build_artica_tabs($html,'main_icapwebfilter_tabs',1490)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	
	echo $html;

}