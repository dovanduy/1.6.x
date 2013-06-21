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
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$appname=$_GET["APPNAME"];
	$action=$_GET["action"];
	$title=$tpl->javascript_parse_text("{{$appname}}::{{$action}}");
	$_GET["cmd"]=urlencode($_GET["cmd"]);
	$html="YahooWinBrowse('700','$page?popup=yes&appname=$appname&action=$action&cmd={$_GET["cmd"]}&id={$_GET["id"]}&appcode={$_GET["appcode"]}','$title')";
	echo $html;
	
}	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$appname=$_GET["appname"];
	$action=$_GET["action"];	
	$id=$_GET["id"];
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{please_wait} {{$action}} {{$appname}}...");
	$_GET["cmd"]=urlencode($_GET["cmd"]);
	$sock=new sockets();
	$sock->getFrameWork("system.php?generic-start=yes&action={$_GET["action"]}&cmd={$_GET["cmd"]}&key=$t");
	
	$html="
		<center id='title-$t' style='font-size:18px'>$title</center><br>
		<center>
			<div id='Status$t'></div>
		</center>
		<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'></textarea>
			
	<script>
		var timez$t=0;
		var idimg$t='$id';
		
		function step1$t() {
			if(!YahooWinBrowseOpen()){return;}
			timez$t=timez$t+1;
			if(timez$t>100){
				document.getElementById('title-$t').innerHTML='';
				refreshidStatus$t();GetInfos$t();
				Finish$t();
				return;}
			if(timez$t==10){refreshidStatus$t();}
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
			if(document.getElementById('squid_main_svc')){refreshTab('squid_main_svc');}
			if(document.getElementById('main_kav4proxy_config')){refreshTab('main_kav4proxy_config');}
			
		}
		
	var X_refreshidStatus$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('$id').src='img/'+res;
		}
		GetInfos$t();
	}		

		function refreshidStatus$t(){
			if(document.getElementById('$id')){
				var XHR = new XHRConnection();
				XHR.appendData('STATUSOF','{$_GET["appcode"]}');
				XHR.sendAndLoad('$page', 'POST',X_refreshidStatus$t);   
			}
		}
		
	var X_GetInfos$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('textarea$t').value=res;
		}
	}		
		
		function GetInfos$t(){
			if(document.getElementById('$id')){
				var XHR = new XHRConnection();
				XHR.appendData('LOGSOF','$t');
				XHR.sendAndLoad('$page', 'POST',X_GetInfos$t);   
			}
		}		
		
		if(document.getElementById('$id')){
			document.getElementById('$id').src='img/wait_verybig_mini_red-48.gif';
		}else{
		alert('$id!!');
		}
	step1$t();		
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function STATUSOF(){
	$key=$_POST["STATUSOF"];
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("system.php?all-services=yes"));
	$bsini=new Bs_IniHandler();
	$bsini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	$img="42-red.png";
	if($bsini->_params[$key]["running"]==1){
		$img="42-green.png";
	}
	echo $img;
}
function LOGSOF(){
	$key=$_POST["LOGSOF"];
	$file="/usr/share/artica-postfix/ressources/logs/web/$key.log";
	if(!is_file($file)){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{please_wait}...\n",1);
		return;
	}
	$t=explode("\n",@file_get_contents($file));
	krsort($t);
	echo @implode("\n", $t);
	
}
