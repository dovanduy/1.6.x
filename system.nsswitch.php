<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
$usersmenus=new usersMenus();

if(!$usersmenus->AsSystemAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_POST["nsswitchEnableLdap"])){Save();exit;}

page();



function page(){
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$nsswitchEnableLdap=intval($sock->GET_INFO("nsswitchEnableLdap"));
	$nsswitchEnableWinbind=intval($sock->GET_INFO("nsswitchEnableWinbind"));
	
	
	$p1=Paragraphe_switch_img("{nsswitchEnableLdap}", "{nsswitchEnableLdap_explain}","nsswitchEnableLdap",$nsswitchEnableLdap,null,890);
	$p2=Paragraphe_switch_img("{nsswitchEnableWinbind}", "{nsswitchEnableWinbind_explain}","nsswitchEnableWinbind",$nsswitchEnableWinbind,null,890);
	
	$html="
<div style='width:98%' class=form>
	$p1		
<p>&nbsp;</p>
$p2	

<div style='text-align:right;margin-top:50px'>". button("{apply}","Save$t()",42)."</div>
</div>
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
}	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nsswitchEnableLdap',document.getElementById('nsswitchEnableLdap').value);
	XHR.appendData('nsswitchEnableWinbind',document.getElementById('nsswitchEnableWinbind').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
	
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("nsswitchEnableLdap", $_POST["nsswitchEnableLdap"]);
	$sock->SET_INFO("nsswitchEnableWinbind", $_POST["nsswitchEnableWinbind"]);
	$sock->getFrameWork("services.php?nsswitch=yes");
}
