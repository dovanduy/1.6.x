<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["dump"])){dump();exit;}	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{saveToDisk}:{$_GET["db"]}");
	echo "RTMMail('650','$page?popup=yes&db={$_GET["db"]}','$title');";
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$wait=$tpl->_ENGINE_parse_body("<center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}</center>");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-database={$_GET["db"]}&debug=yes");	
	
	$html="
	<div id='$t' style='margin:10px;width:95%;height:550px;overflow:auto' class=form>$wait
		<center style='margin:30px'><img src='img/wait_verybig.gif'></center>
	</div>
	
	<script>
		function Lll$t(){
			if(RTMMailOpen()){
				LoadAjax('$t','$page?dump=yes&t=$t&db={$_GET["db"]}');
			}
		
		}
		setTimeout('Lll$t()',3000);
	</script>
	
	";
	
	echo $html;
	
	
}

function dump(){
	$t=$_GET["t"];
	$database=$_GET["db"];
	$logfile="ressources/logs/web/squidguard-$database.dbg";
	if(!is_file($logfile)){
		$tpl=new templates();
		$wait=$tpl->_ENGINE_parse_body("<center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}</center>");
		echo "$wait<center style='margin:30px'>
			<img src='img/wait_verybig.gif'></center>
				<script>setTimeout('Lll$t()',8000);</script>";
		return;
	}
	
	
	$f=explode("\n",@file_get_contents($logfile));
if(count($f)>50){echo "<hr><center>".imgtootltip("refresh-32.png","Refresh","Lll$t()")."<hr></center>";}
	krsort($f);
	$c=0;
	while (list ($num, $ligne) = each ($f) ){
		$c++;	
		$ligne=str_replace("/usr/share/artica-postfix/ressources", "", $ligne);
		$color="black";$weight="normal";
		if(preg_match("#Notice:#", $ligne)){$color="red";}
		if(preg_match("#Starting#", $ligne)){$weight="bold";}
		if(preg_match("#took#i", $ligne)){$weight="bold";}
		if(preg_match("#warning:#i", $ligne)){$color="#B10000";}
		if(preg_match("#failed#i", $ligne)){$color="#B10000";}  
		echo "<div style='font-size:11px;color:$color;font-weight:$weight'>". htmlentities($ligne)."</div>";
	}
	if(count($f)<50){
		echo "<script>setTimeout('Lll$t()',8000);</script>";
	}
	
}
