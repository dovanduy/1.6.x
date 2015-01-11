<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.kav4proxy.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSquidAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["uninstall"])){uninstall();exit;}
	if(isset($_GET["stepunins"])){step_uninstall();exit;}
	if(isset($_GET["stepuninst2"])){step_uninstall2();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["status-js"])){status_js();exit;}
	if(isset($_POST["POST_TEXTAREA"])){POST_TEXTAREA();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	$INSTALLED=trim($sock->getFrameWork("squid.php?kaspersky-is-installed=yes"));
	if($INSTALLED=="TRUE"){
		$title=$tpl->javascript_parse_text("Kaspersky: {unistallation}");
		$html="YahooWinBrowse('700','$page?uninstall=yes','$title',true)";
		echo $html;
		return;
	}

	$title=$tpl->javascript_parse_text("Kaspersky: {installation} $INSTALLED");
	$html="YahooWinBrowse('700','$page?popup=yes','$title',true)";
	echo $html;
}
function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	
	<div style='width:95%' id='div-$t' class=form></div>
	<script>LoadAjax('div-$t','$page?step1=yes&t=$t',true);</script>";
	echo $html;
}

function uninstall(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	
	<div style='width:95%' id='div-$t' class=form></div>
	<script>LoadAjax('div-$t','$page?stepunins=yes&t=$t',true);</script>";
	
	echo $html;	
	
}
function step_uninstall(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$explain="
	<div style='font-size:18px;margin:30px' class=text-info>{KAV4PROXYINST_UNINST_EXPLAIN}</div>
	<div style='width:100%;text-align:right'>". button("{uninstall}", "LoadAjax('div-$t','$page?stepuninst2=yes&t={$_GET["t"]}')",22)."</div>";
	
	echo $tpl->_ENGINE_parse_body($explain);	
	
}


function step1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$explain="
	<div style='font-size:18px;margin:30px' class=text-info>{KAV4PROXYINST_STEP1_EXPLAIN}</div>
	<div style='width:100%;text-align:right'>". button("{install_now}", "LoadAjax('div-$t','$page?step2=yes&t={$_GET["t"]}')",22)."</div>";
	
	echo $tpl->_ENGINE_parse_body($explain);
}

function status_js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	if(!isset($_GET["fsize"])){$_GET["fsize"]=0;}
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	$data=unserialize(@file_get_contents($cacheFile));
	$size=@filesize($cacheFile);
	
	$Time=time();
	if(!is_array($data)){
		$title="{please_wait}";
		$text=array();
		$progress=5;
	}else{
		$title=$data["TITLE"];
		$progress=$data["POURC"];
		$text=$data["LOGS"];
	}
	
	
	
	if($size==$_GET["fsize"]){
		$title="{please_wait}";
		$text=array();	
		$progress=0;
	}else{
		$_GET["fsize"]=$size;
	}
	
	if(!is_numeric($progress)){$progress=0;}
	$lenght_text=count($text);
	$title=$tpl->javascript_parse_text($title);
	echo "
			
	var xPOST_TEXTAREA$Time= function (obj) {
		var results=obj.responseText;
		if(results.length>5){
			document.getElementById('textarea$t').value=results;
		}
	}	
	
			
	function POST_TEXTAREA$Time(){
		 var XHR = new XHRConnection();
		 XHR.appendData('POST_TEXTAREA','yes');
		 XHR.setLockOff();
		 XHR.sendAndLoad('$page', 'POST',xPOST_TEXTAREA$Time);		
		}
	
	
			
	function Refresh$Time(){
		if(!YahooWinBrowseOpen()){return;}
		var title='$title';
		var lenght_text=$lenght_text;
		var progress=$progress;
		
		if(title.length>0){
			document.getElementById('title-$t').innerHTML=title;
		}
		
		if(lenght_text>0){
			POST_TEXTAREA$Time();
		
		}
		if(progress>0){
			
			$('#Status$t').progressbar({ value: $progress });
		}
		
		
		if(progress>99){CacheOff();return;}
		Loadjs('$page?status-js=yes&t=$t&fsize={$_GET["fsize"]}');
	}	

	setTimeout('Refresh$Time()',3000);	
	
	";
	
	
}
function step_uninstall2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$Time=time();
	$title="{please_wait}";
	$sock=new sockets();
	$sock->getFrameWork("squid.php?kav4proxy-uninstall=yes");
	$title=$tpl->javascript_parse_text($title);
	echo "
	<div style='font-size:28px;text-align:center;margin:15px' id='title-$t'>$title</div>
	<center>
	<div id='Status$t'></div>
	
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'></textarea>
	</center>
	<script>
	
	function Refresh$Time(){
	$('#Status$t').progressbar({ value: 5 });
	Loadjs('$page?status-js=yes&t=$t');
	}
	setTimeout('Refresh$Time()',3000);
	</script>";	
	
}

function step2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$Time=time();	
	$title="{please_wait}";
	$sock=new sockets();
	$sock->getFrameWork("squid.php?kav4proxy-install=yes");
	$title=$tpl->javascript_parse_text($title);
	echo "
	<div style='font-size:28px;text-align:center;margin:15px' id='title-$t'>$title</div>
	<center>
	<div id='Status$t'></div>
	
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'></textarea>
	</center>
	<script>
	
	function Refresh$Time(){
		$('#Status$t').progressbar({ value: 5 });
		Loadjs('$page?status-js=yes&t=$t');
	}
	setTimeout('Refresh$Time()',3000);		
	</script>";
}

function POST_TEXTAREA(){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/KAV4PROXYINST.status";
	$data=unserialize(@file_get_contents($cacheFile));
	krsort($data["LOGS"]);
	
	echo @implode("\n", $data["LOGS"]);
	
	
	
}
