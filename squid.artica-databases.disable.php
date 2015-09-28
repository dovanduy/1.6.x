<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$dirname=dirname(__FILE__);
if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}

include_once($dirname.'/ressources/class.templates.inc');
include_once($dirname.'/ressources/class.ldap.inc');
include_once($dirname.'/ressources/class.users.menus.inc');
include_once($dirname.'/ressources/class.squid.inc');
include_once($dirname.'/ressources/class.ActiveDirectory.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}



if(isset($_POST["enable"])){enable();exit;}
startx();

function startx(){
$page=CurrentPageName();
$tpl=new templates();
header("content-type: application/x-javascript");
$sock=new sockets();
$SquidDatabasesArticaEnable=$sock->GET_INFO("SquidDatabasesArticaEnable");
if(!is_numeric($SquidDatabasesArticaEnable)){$SquidDatabasesArticaEnable=1;}

if($SquidDatabasesArticaEnable==1){
	
	$text=$tpl->javascript_parse_text("{disable_articadb_explain}");
	
}else{
	$text=$tpl->javascript_parse_text("{enable_artica_db_explain}");
	
	
}

$t=time();
echo "
var xRunf$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes&RemoveCache=yes');
	Loadjs('dansguardian2.compile.php');
}			
function Runf$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('enable', 'yes');
	XHR.sendAndLoad('$page', 'POST',xRunf$t);	

}

Runf$t();";

}

function enable(){
	$sock=new sockets();
	$SquidDatabasesArticaEnable=$sock->GET_INFO("SquidDatabasesArticaEnable");
	if(!is_numeric($SquidDatabasesArticaEnable)){$SquidDatabasesArticaEnable=1;}
	if($SquidDatabasesArticaEnable==1){
	
		$sock->SET_INFO("SquidDatabasesArticaEnable", 0);
	}else{
		
		$sock->SET_INFO("SquidDatabasesArticaEnable", 1);
	
	}
}


