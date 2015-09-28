<?php
if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$user=new usersMenus();
$sock=new sockets();
$EnableSquidUrgencyPublic=$sock->GET_INFO("EnableSquidUrgencyPublic");
if(!is_numeric($EnableSquidUrgencyPublic)){$EnableSquidUrgencyPublic=0;}

if(!$user->AsArticaMetaAdmin){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
	
}

if(isset($_GET["justbutton"])){justbutton_js();exit;}
if(isset($_GET["popup-justbutton"])){justbutton();exit;}
if(isset($_POST["Disable"])){Disable();exit;}
justbutton_js();

function justbutton_js(){
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$uuid=$_GET["uuid"];
	
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	
	$title=$tpl->_ENGINE_parse_body("$hostname:{urgency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin6('700','$page?popup-justbutton=yes&uuid=$uuid&gpid={$_GET["gpid"]}','$title');";

}

function justbutton(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
			".button("{disable_emergency_mode}","Save$t()",32)."
		</center>
<script>
	var xSave$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		$('#ARTICA_META_MAIN_TABLE').flexReload();
		YahooWin6Hide();
	}	


	function Save$t(){
		var XHR = new XHRConnection();
	  	XHR.appendData('Disable','yes');
	  	XHR.appendData('uuid','{$_GET["uuid"]}');
	  	XHR.appendData('gpid','{$_GET["gpid"]}');
	  	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>			
			
			
");

	



}

function Disable(){
	$uuid=$_POST["uuid"];
	$meta=new mysql_meta();

	if(intval($_POST["gpid"])>0){
		if(!$meta->CreateOrder_group(intval($_POST["gpid"]), "PROXY_DISABLE_URGENCY",array())){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	if(!$meta->CreateOrder($uuid, "PROXY_DISABLE_URGENCY",array())){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
	
}