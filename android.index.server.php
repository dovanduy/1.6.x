<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');

$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){
	header("location:android.logoff.php");
	exit;
}

if(isset($_GET["mem-status"])){mem_status();exit;}


$users=new usersMenus();
$page=CurrentPageName();
$t=time();
$html="
<table style='width:100%;margin:0px;padding:0px'>
<tr>
	<td valign='top'>
		<div style='font-size:18px;width:254px'>$users->hostname</div>
		<div style='font-size:12px'>Artica v.$users->ARTICA_VERSION</div>
		<p>&nbsp;</p>
		<div id='squid-stats-$t'></div>
	</td>
	<td valign='top'><div id='mem' style='width:420px'></div></td>
</tr>
</table>
<script>
	LoadAjax('mem','$page?mem-status=yes&t=$t');
</script>
	

";

echo $html;


function mem_status(){
	$t=$_GET["t"];
	$tpl=new templates();
	include_once("ressources/class.os.system.tools.inc");
	$os=new os_system();
	$html=RoundedLightGrey($os->html_Memory_usage())."<br>";
	echo $tpl->_ENGINE_parse_body($html);
	echo "<script>LoadAjax('squid-stats-$t','android.squid.stats.status.php');</script>";
	
	
}