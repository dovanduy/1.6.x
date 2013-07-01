<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.external.ad.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	//if(count($_POST)>0)
	$usersmenus=new usersMenus();
	if(!$usersmenus->AllowAddUsers){
		writelogs("Wrong account : no AllowAddUsers privileges",__FUNCTION__,__FILE__);
		if(isset($_GET["js"])){
			$tpl=new templates();
			$error="{ERROR_NO_PRIVS}\\n{AllowAddUsers}:False\\n";
			echo $tpl->_ENGINE_parse_body("alert('$error')");
			die();
		}
		header("location:domains.manage.org.index.php?ou={$_GET["ou"]}&dn=".urlencode($_GET["dn"]));
		}
		
		if(isset($_GET["gid-popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$Groupname=$_GET["name"];
	$_GET["dn"]=urlencode($_GET["dn"]);
	$_GET["ou"]=urlencode($_GET["ou"]);
	$_GET["gid"]=urlencode($_GET["gid"]);
	if($_GET["dn"]<>null){$_GET["gid"]=$_GET["dn"];}
	$t=time();
	echo "
	function RefreshOrgTabMain$t(){
		RefreshTab('org_main');
	}
	
	
	Loadjs('domains.edit.group.php?js=yes&group-id={$_GET["gid"]}&ou={$_GET["ou"]}&dn={$_GET["dn"]}&encoded=yes&main_group_config=no')
	
	//setTimeout('RefreshOrgTabMain$t()',3500);
	";
	
	
	
}

function popup(){
	$t=time();
	$_GET["dn"]=urlencode($_GET["dn"]);
	$html="<div id='$t'></div>
	
	<script>
		LoadAjax('$t','domains.edit.group.php?popup=yes&GroupSettingsID=$t&ou={$_GET["ou"]}&crypted=yes&group-id={$_GET["gid-popup"]}&dn={$_GET["dn"]}');
	</script>";
	
	echo $html;
}




