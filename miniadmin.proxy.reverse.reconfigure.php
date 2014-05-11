<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){header("content-type: application/x-javascript");echo "alert('No privileges');";die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["STATUSOF"])){STATUSOF();exit;}
	if(isset($_POST["LOGSOF"])){LOGSOF();exit;}
	if(isset($_GET["ifStopped"])){ifStopped();exit;}
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$servername=$_GET["servername"];
	$servernameenc=urlencode($servername);
	$title=$tpl->javascript_parse_text("$servername::{reconfigure}");
	$_GET["cmd"]=urlencode($_GET["cmd"]);
	$html="YahooWinBrowse('700','$page?popup=yes&servername=$servernameenc','$title')";
	echo $html;
	
}	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$_GET["servername"];
	$action=$_GET["action"];	
	$id=$_GET["id"];
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{please_wait} {reconfigure} $servername...");
	$servernamenc=urlencode($servername);
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?reconfigure-single=yes&servername=$servernamenc&key=$t");
	$finish_text=$tpl->_ENGINE_parse_body("{$servername} {reconfigure} {success}");
	$html="
		<center id='title-$t' style='font-size:18px'>$title</center><br>
		<center>
			<div id='Status$t'></div>
		</center>
		<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'></textarea>
	<input type='hidden' id='stopall-$t' value='0'>
			
	<script>
		var timez$t=0;
		var idimg$t='$id';
		
		function step1$t() {
			if(!YahooWinBrowseOpen()){return;}
			timez$t=timez$t+1;
			
			var stopall=document.getElementById('stopall-$t').value;
			if(stopall==1){
				timez$t=110;
				$('#Status$t').progressbar({ value: 100 });
				Finish$t();
				return;
			}
			
			if(timez$t>100){
				document.getElementById('title-$t').innerHTML='';
				refreshidStatus$t();GetInfos$t();
				Finish$t();
				return;}
			if(timez$t==1){GetInfos$t();}
			if(timez$t==5){GetInfos$t();}
			if(timez$t==10){refreshidStatus$t();GetInfos$t();}
			if(timez$t==15){GetInfos$t();}
			if(timez$t==20){GetInfos$t();}
			if(timez$t==30){GetInfos$t();}
			if(timez$t==40){GetInfos$t();}
			if(timez$t==50){GetInfos$t();}
			if(timez$t==60){refreshidStatus$t();}
			if(timez$t==70){GetInfos$t();}
			if(timez$t==80){GetInfos$t();}
			if(timez$t==90){GetInfos$t();}
			setTimeout(step2$t, 300);
		}
		
		function step2$t() {
			$('#Status$t').progressbar({ value: timez$t });
			step1$t();
		}
		
		function Finish$t(){
			if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
			if(document.getElementById('main_kav4proxy_config')){RefreshTab('main_kav4proxy_config');}
			if(document.getElementById('main_config_openssh')){RefreshTab('main_config_openssh');}
			
			document.getElementById('title-$t').innerHTML='$finish_text';
		}
		
	var X_refreshidStatus$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			if(document.getElementById('$id')){
				document.getElementById('$id').src='img/'+res;
			}
		}
		GetInfos$t();
	}		

		function refreshidStatus$t(){
			if(document.getElementById('$id')){
				var XHR = new XHRConnection();
				XHR.appendData('STATUSOF','$servername');
				XHR.setLockOff();
				XHR.sendAndLoad('$page', 'POST',X_refreshidStatus$t);   
			}
		}
		
	var X_GetInfos$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('textarea$t').value=res;
			Loadjs('$page?ifStopped=$servername&t=$t');
		}
	}		
		
	function GetInfos$t(){
		var XHR = new XHRConnection();
		XHR.appendData('LOGSOF','$servername');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',X_GetInfos$t);   
	}		
		
	if(document.getElementById('$id')){
		document.getElementById('$id').src='img/wait_verybig_mini_red-48.gif';
	}
	step1$t();		
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function STATUSOF(){
}
function LOGSOF(){
	$key=$_POST["LOGSOF"];
	$file="/usr/share/artica-postfix/ressources/logs/web/nginx-$key.log";
	if(!is_file($file)){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{please_wait}...\n",1);
		return;
	}
	$t=explode("\n",@file_get_contents($file));
	@file_put_contents($file, @implode("\n", $t));
	
	
	krsort($t);
	echo @implode("\n", $t);
	
	
}

function ifStopped(){
	$key=$_GET["ifStopped"];
	$tTime=$_GET["t"];
	header("content-type: application/x-javascript");
	$file="/usr/share/artica-postfix/ressources/logs/web/nginx-$key.log";
	if(!is_file($file)){return;}
	$t=explode("\n",@file_get_contents($file));
	
	while (list ($num, $ligne) = each ($t) ){
		if(preg_match("#Starting.*?started with new PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;	
			}

			if(preg_match("#Success service reloaded#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Service already started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Starting.*?Success PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#already started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Already running using PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Success service started pid#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
	}
	
	
	
}
