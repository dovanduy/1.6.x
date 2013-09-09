<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if(!isset($_SESSION["ProxyCategoriesPermissions"][$_GET["category"]])){
			if($user->AsWebStatisticsAdministrator==false){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}	
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["CheckCompile"])){CheckCompile();exit;}
	if(isset($_GET["reloadufdb"])){reloadufdb();exit;}
	if(isset($_GET["restartufdb"])){restartufdb();exit;}

js();

function js(){
	$tt=time();
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{compile}::{$_GET["category"]}");
	$ask=$tpl->javascript_parse_text("{confirm_dnsg_compile_db} {$_GET["category"]}");
	$page=CurrentPageName();

	$html="
		function start$tt(){
			if(!confirm('$ask')){return;}
			YahooWinS('550','$page?popup=yes&category={$_GET["category"]}&t=$t','$title');
		
		}
		
		function CloseThis$t(){
			YahooWinSHide();
		}
		
	start$tt();";
	
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$sock=new sockets();
	$_GET["category"]=urlencode($_GET["category"]);
	$sock->getFrameWork("squid.php?ufdbguard-compile-database={$_GET["category"]}&MyCURLTIMEOUT=120");	
	$tt=time();
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{please_wait_compiling_items}");
	
	$html="<div id='progress-$t'></div>
	<center style='font-size:16px;font-weight:bold' id='text-$t'>$text</center>
	<div id='infos-$t'></div>
	
	<script>
		function Start1$tt(){
			LoadAjaxTiny('infos-$t','$page?CheckCompile=yes&t=$t&category={$_GET["category"]}');
		}
		$('#progress-{$t}').progressbar({ value: 10 });
		document.getElementById('text-$t').innerHTML='$text';
		setTimeout('Start1$tt()',2000);
	</script>
	
	";
	
	echo $html;
	
	
}

function CheckCompile(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-database={$_GET["category"]}&MyCURLTIMEOUT=120");	
	
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{please_wait_reconfiguring_filter}");	
	
echo "
<script>
		function Start1$tt(){
			LoadAjaxTiny('infos-$t','$page?reloadufdb=yes&t=$t&category={$_GET["category"]}');
		}
		$('#progress-{$t}').progressbar({ value: 70 });
		document.getElementById('text-$t').innerHTML='$text';
		setTimeout('Start1$tt()',2000);
	</script>";

}

function reloadufdb(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-smooth-tenir=yes&MyCURLTIMEOUT=120");	
	
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{please_wait_restarting_filter}");	
	
echo "
<script>
		function Start1$tt(){
			LoadAjaxTiny('infos-$t','$page?restartufdb=yes&t=$t&category={$_GET["category"]}');
		}
		$('#progress-{$t}').progressbar({ value: 80 });
		document.getElementById('text-$t').innerHTML='$text';
		setTimeout('Start1$tt()',2000);
		
	</script>";	
	
	
}

function restartufdb(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-restart-tenir=yes&MyCURLTIMEOUT=120");		
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{done_you_close}");		
	
	echo "
	<script>
		$('#progress-{$t}').progressbar({ value: 100 });
		document.getElementById('text-$t').innerHTML='$text';
		$('#dansguardian2-category-$t').flexReload();
		setTimeout('CloseThis$t()',5000);
	</script>";		
}
