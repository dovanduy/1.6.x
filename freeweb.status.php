<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	include_once('ressources/class.pdns.inc');
	
	
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["st"])){st();exit;}
	
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("FreeWebs:{status}");
	$html="YahooWin5('1600','$page?popup=yes','$title')";
	echo $html;	
	
}


function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="<div style='float:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('$t','$page?st=yes')")."
			</div><div id='$t'></div>
			<script>LoadAjax('$t','$page?st=yes')</script>
				";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function st(){
	
	$sock=new sockets();
	$users=new usersMenus();
	$datas=base64_decode($sock->getFrameWork("freeweb.php?status=yes&hostname=$users->hostname"));
	$datas=str_replace("127.0.0.1",$users->hostname, $datas);
	$datas=str_replace("<h1>", "<div style='font-size:18px'>", $datas);
	$datas=str_replace("</h1>", "</div>", $datas);
	$datas=str_replace('table border="0"', "table class=form style='width:99%'", $datas);
	$datas=str_replace('table cellspacing=0 cellpadding=0', "table class=form style='width:99%'", $datas);
	$datas=str_replace('table border="1"',"table style='width:100%'", $datas);
	$datas=str_replace('table border="1" cellpadding="2" cellspacing="2" style="width: 100%"', "table class=form style='width:99%'", $datas);
	$datas=str_replace("table style='width:100%' cellpadding=\"2\" cellspacing=\"2\" style=\"width: 100%\"", "table class=form style='width:99%'", $datas);
	$datas=str_replace("<div title", "<div style='font-size:14px;font-weight:bold'", $datas);
	$datas=str_replace('class="rowe"',"class='rowe' style='font-size:14px;font-weight:bold'", $datas);
	$datas=str_replace('<td>',"<td style='font-size:13px'>", $datas);
	$datas=str_replace('<td nowrap>',"<td style='font-size:13px' nowrap>", $datas);
	echo $datas;
}
