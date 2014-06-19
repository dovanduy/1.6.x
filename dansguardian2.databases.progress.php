<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

page();

function page(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress"));
	$text=$array["TEXT"];
	$purc=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/toulouse.progress"));
	$text1=$array["TEXT"];
	$purc1=intval($array["POURC"]);
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress"));
	$text2=$array["TEXT"];
	$purc2=intval($array["POURC"]);	
	
	$html="
	<div style='margin-top:15px;font-size:26px'>{categories_databases} %</div>
	<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
	<div id='progress-$t' style='height:50px'></div>
			
	<div style='margin-top:15px;font-size:26px'>{webfiltering_database} (Toulouse University) %</div>
	<center id='title1-$t' style='font-size:18px;margin-bottom:20px'>$text1</center>
	<div id='progress1-$t' style='height:50px'></div>	

	<div style='margin-top:15px;font-size:26px'>{webfiltering_database} (Artica) %</div>
	<center id='title2-$t' style='font-size:18px;margin-bottom:20px'>$text2</center>
	<div id='progress2-$t' style='height:50px'></div>		
	<script>
		$('#progress-$t').progressbar({ value: $purc });
		$('#progress1-$t').progressbar({ value: $purc1 });
		$('#progress2-$t').progressbar({ value: $purc2 });

	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
