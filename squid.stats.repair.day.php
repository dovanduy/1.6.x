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
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["start"])){start();exit;}
	if(isset($_GET["logs"])){logs();exit;}
	if(isset($_POST["Filllogs"])){Filllogs();exit;}
	
js();



function js(){
	$t=$_GET["time"];
	$tpl=new templates();
	$day=$tpl->_ENGINE_parse_body(date("{l} {F} d Y",$t));
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{repair}::$day");
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$html="YahooWinBrowse('850','$page?popup=yes&t=$t','$title');";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title="{PLEASE_WAIT_CALCULATING_STATISTICS}";
	
	
	$html="
	
	<center style='font-size:16px;margin:10px'>
	<table style='width:99%'>
	<tr><td><div id='title-$t'>$title</div></center></td>
	<td width=1%><div id='$t-1'></div></td>
	</tr>
	</table>
	<div id='$t'></div>
		<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
		$('#$t').progressbar({ value: 5 });
		LoadAjaxTiny('$t-1','$page?start=yes&t=$t');
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function start(){
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];
	$sock->getFrameWork("squidstats.php?repair-hour=$t");
	header("content-type: application/x-javascript");
	echo "
			
	<script>
		
		LoadAjaxTiny('$t-1','$page?logs=yes&t=$t');	
			
	</script>";
	
	
}

function logs(){
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_GET["t"];	
	$tt=time();
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$t";
	$array=unserialize(@file_get_contents($filelogs));
	$tpl=new templates();
	
	
	
	if(!is_numeric($array["PROGRESS"])){$array["PROGRESS"]=5;}
	header("content-type: application/x-javascript");
	echo "
	<script>
		var x_Fill$tt= function (obj) {
			var res=obj.responseText;
			if (res.length>3){document.getElementById('textToParseCats-$t').value=res;}
			LoadAjaxTiny('$t-1','$page?logs=yes&t=$t');	
		}
	
	
		function Refresh$tt(){
			if(!YahooWinBrowseOpen()){return;}
			$('#$t').progressbar({ value: {$array["PROGRESS"]} });	
			var XHR = new XHRConnection();
		   	XHR.appendData('Filllogs', 'yes');
		   	XHR.appendData('t', '$t');
			XHR.sendAndLoad('$page', 'POST',x_Fill$tt); 			
			
		}
		
		setTimeout(\"Refresh$tt()\",2000);
			
	</script>";
	
	
}

function Filllogs(){
	$page=CurrentPageName();
	$sock=new sockets();
	$t=$_POST["t"];
	$tt=time();
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/repair-webstats-$t";
	$array=unserialize(@file_get_contents($filelogs));
	$tpl=new templates();	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $array["TEXT"]));
}




?>
