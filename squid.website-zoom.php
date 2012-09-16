<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	
	if(isset($_GET["js"])){echo js();exit;}
	
	
	
page();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$www=$_GET["sitename"];
	$html="RTMMail(918,'$page?sitename=$www','ZOOM:$www')";
	echo $html;
	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$www=$_GET["sitename"];
	$md5=md5($www);
	$t=time();
	$html="
	<div id='$t$md5'></div>
	
	
	<script>
		$('#startpoint-$md5').remove();
		LoadAjax('$t$md5','squid.www-ident.php?www=$www');
	</script>
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
