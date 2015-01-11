<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["FailOverArtica"])){parameters_save();exit;}
tabs();



function tabs(){
	
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$explain=null;
	$t=time();
	$boot=new boostrap_form();
	
	if(!$users->CORP_LICENSE){
		$explain="<p class=text-error>{UCARP_LICENSE_EXPLAIN}</p>";
		
	}
	
	$explain=$explain.$tpl->_ENGINE_parse_body("<div class=text-info>{UCARP_HOWTO_EXPLAIN}</div>");
	$array["{parameters}"]="$page?parameters=yes";
	$array["{system_events}"]='miniadm.ucarp.events.php';
	echo $explain.$boot->build_tab($array);	

}
function parameters(){
	$users=new usersMenus();
	$sock=new sockets();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	$FailOverArticaParams=unserialize(base64_decode($sock->GET_INFO("FailOverArticaParams")));
	if(!is_numeric($FailOverArticaParams["squid-internal-mgr-info"])){$FailOverArticaParams["squid-internal-mgr-info"]=1;}
	if(!is_numeric($FailOverArticaParams["ExternalPageToCheck"])){$FailOverArticaParams["ExternalPageToCheck"]=1;}
	
	
	
	$boot=new boostrap_form();
	$boot->set_checkbox("FailOverArtica", "{FailOverArtica}", $FailOverArtica,array("TOOLTIP"=>"{FailOverArtica_explain}","DISABLEALL"=>true));

	$boot->set_spacertitle("{APP_PROXY}");
	$boot->set_checkbox("squid-internal-mgr-info","{failover_mgrinfo}",$FailOverArticaParams["squid-internal-mgr-info"],array("TOOLTIP"=>"{failover_mgrinfo_explain}"));
	$boot->set_checkbox("ExternalPageToCheck","{failover_ExternalPageToCheck}",$FailOverArticaParams["ExternalPageToCheck"],array("TOOLTIP"=>"{failover_ExternalPageToCheck_explain}"));
	
	
	if(!$users->CORP_LICENSE){$boot->set_form_locked();}
	echo $boot->Compile();
}
function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("FailOverArtica", $_POST["FailOverArtica"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "FailOverArticaParams");
}

?>


