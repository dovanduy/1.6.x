<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["hostname-js"])){hostname_js();exit;}

Graphs1();

function hostname_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["hostname-js"]}:{graphs}");
	
	echo "YahooWin2('875','$page?popup-hostname=yes&hostname={$_GET["hostname-js"]}','$title');";	
	
}

function zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$hostname=null;
	$title=$_GET["zoom-js"];
	if(isset($_GET["hostname"])){$hostname="&hostname={$_GET["hostname"]}";$title="{$_GET["hostname"]}::$title";}
	echo "YahooWin3('875','$page?zoom-popup={$_GET["zoom-js"]}$hostname','$title (zoom)');";
	
}

function zoom_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	
$base="squid-rrd";
if(isset($_GET["hostname"])){
	$base="ressources/conf/upload/{$_GET["hostname"]}";
	$addjs="&hostname={$_GET["hostname"]}";
	$refresh=null;
}	
	
	$array["cnx"][]="connections.day.1.png";
	$array["cnx"][]="connections.week.png";
	$array["cnx"][]="connections.month.png";
	
	$array["cpu"][]="cpu.day.png";
	$array["cpu"][]="cpu.week.png";
	$array["cpu"][]="cpu.month.png";		
	
	$array["mem"][]="memory.day.png";
	$array["mem"][]="memory.week.png";
	$array["mem"][]="memory.month.png";		

	
	while (list ($num, $img) = each ($array[$_GET["zoom-popup"]]) ){
	$f[]="<center><div class=form style='width:90%'><img src='$base/$img?$t'></div></center>";
		
	}
	
	
$html=@implode("\n", $f);
echo $tpl->_ENGINE_parse_body($html);
}


function Graphs1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
//$f[]="connections.day.1.png";
//$f[]="connections.day.png";
$f[]="connections.week.png";
$f[]="connections.hour.png";
$f[]="cpu.day.png";
$f[]="diskd.day.png";
$f[]="fd.day.png";
$f[]="hitratio.day.png";
$f[]="memory.day.png";
$f[]="memory.hour.png";
$f[]="select.day.png";
$f[]="svctime.day.png";	

$refresh="<div style='float:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('squid_main_svc')")."</div>";
$base="squid-rrd";
if(isset($_GET["hostname"])){
	$base="ressources/conf/upload/{$_GET["hostname"]}";
	$addjs="&hostname={$_GET["hostname"]}";
	$refresh=null;
}




$html="
$refresh
<center>
		<div class=form style='width:90%'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?zoom-js=cnx$addjs');\" style='border:0px'>
			<img src='$base/connections.hour.png?$t'>
		</a>
		</div>
	</center>

<center><div class=form style='width:90%'>
	<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?zoom-js=cpu$addjs');\" style='border:0px'>
		<img src='$base/cpu.hour.png?$t'>
	</a>
</div></center>
<center><div class=form style='width:90%'>
	<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?zoom-js=mem$addjs');\" style='border:0px'>
		<img src='$base/memory.hour.png?$t'>
	</a>
</div></center>

";


echo $tpl->_ENGINE_parse_body($html);
	
}