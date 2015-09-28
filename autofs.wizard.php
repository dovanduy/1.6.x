<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.nfs.inc');
	include_once("ressources/class.harddrive.inc");
	include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
	
	$users=new usersMenus();
	if(!IsPriv()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["popup"])){
		popup();exit;
	}
	
	
js();



function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{remote_connection}");
	$html="LoadWinORG(550,'$page?popup=yes&field={$_GET["field"]}','$title')";
	echo $html;
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$add=Paragraphe("64-wizard.png","{add_mount_point}","{add_mount_point_text}",
			"javascript:Loadjs('autofs.php?form-add-js=yes&field={$_GET["field"]}')");
	
	
	$autofs=new autofs();
	$hash=$autofs->automounts_Browse();
	if(count($hash)>0){
		$list=Paragraphe("wifi-ok-64.png","{mounts_list} (.".count($hash).")","{mounts_list_explain}",
				"javascript:Loadjs('autofs.php?mounts-list-js=yes&field={$_GET["field"]}')");		
		
	}
	
	
	$html="
	<div style='width:99%' class=form>		
	<div  style='font-size:14px' class=explain>{autofs_wizard_explain}</div>
	<table style='width:100%'>
	<tr>
		<td width=50% align=center>$add</td>
		<td width=50% align=center>$list</td>
	</tr>
	</table>
	</div>
			
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


	
	
	function IsPriv(){
		$users=new usersMenus();
		if($users->AsArticaAdministrator){return true;}
		if($users->AsSambaAdministrator){return true;}
		if($users->AsSystemAdministrator){return true;}
		if($users->AsOrgStorageAdministrator){return true;}
		return false;
	}
	