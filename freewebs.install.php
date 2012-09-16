<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
page();




function page(){
$page=CurrentPageName();
$t=time();
$tpl=new templates();
$TABLE_WIDTH=675;
$TABLE_HEIGHT=500;
$SOFT_ROWS=210;	
if(isset($_GET["full-expand"])){
	$TABLE_WIDTH=867;
	$SOFT_ROWS=408;
}	
	
	$html="
	<div id='software-list-$t' style='width:100%;height:650px;overflow:auto'></div>
	
	
	<script>
	
	function SoftwareSearch(){
		LoadAjax('software-list-$t','setup.index.php?software-list-by-family=yes&FAMILY=WEB&search=&softwares-row=$SOFT_ROWS&table-width=$TABLE_WIDTH&height-table=500&margin-left=0');
	}	
	SoftwareSearch();
</script>	
	
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}
