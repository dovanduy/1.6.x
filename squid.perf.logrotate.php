<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["logrotate"])){logrotate();exit;}
	
	if(isset($_POST["logrotate"])){logrotate_events();exit;}

	js();
	
	
function js(){
	$img=$_GET["img"];
	$src=$_GET["src"];	
	$tabs=$_GET["tabs"];
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{rotate_logs}");
	$html="YahooWinBrowse('700','$page?popup=yes&img=$img&src=$src&tabs=$tabs','$title')";
	echo $html;
}

	
function popup(){
	$img=$_GET["img"];
	$src=$_GET["src"];
	$tabs=$_GET["tabs"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{rotate_logs}");
	$title_wait=$tpl->javascript_parse_text("{please_wait}...");
	$html="
	<center id='title-$t' style='font-size:18px'>$title</center>
	<center id='animate-$t'></center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>		
	<div id='wait-$t' style='font-size:18px'></div>
	<script>
	
	var x_exec_squid_logrotate$t=function (obj) {
		if(!YahooWinBrowseOpen()){return;}
		var tempvalue=obj.responseText;
		document.getElementById('textToParseCats-$t').value=tempvalue;
		document.getElementById('title-$t').innerHTML='$title_wait';
		setTimeout(\"exec_squid_logrotate$t()\",8000);

		
	}	
	
	function exec_squid_logrotate$t(){
		if(!YahooWinBrowseOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		var XHR = new XHRConnection();
		XHR.appendData('logrotate','yes');
		XHR.sendAndLoad('$page', 'POST',x_exec_squid_logrotate$t);	
	}
	
	function Start$t(){
		if(document.getElementById('$img')){document.getElementById('$img').src='img/wait.gif';}
		AnimateDiv('animate-$t');
		LoadAjaxSilent('wait-$t','$page?logrotate=yes&img=$img&src=$src&tabs=$tabs&t=$t');
	
	}
	
	
	Start$t();
	
	</script>";
	
	echo $html;
} 

function logrotate_events(){
	$target_file="/usr/share/artica-postfix/ressources/logs/web/squidrotate.txt";
	echo @file_get_contents($target_file);
}

function logrotate(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?logrotate-tenir=yes&MyCURLTIMEOUT=300");
	$tt=time();
	$t=$_GET["t"];
	$img=$_GET["img"];
	$src=$_GET["src"];
	$tabs=$_GET["tabs"];	
	$html="
		<script>
		document.getElementById('animate-$t').innerHTML='';
		if(document.getElementById('$img')){document.getElementById('$img').src='img/$src';}
		setTimeout(\"exec_squid_logrotate$t()\",2000);
		</script>
			
	";
	echo $html;
	
}