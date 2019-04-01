<?php

$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.log";

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["VERBOSE"]=true;
}

	include_once('ressources/class.templates.inc');
						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();

function title(){
	
	$tpl=new templates();
	$sock=new sockets();
	
	$title=$tpl->_ENGINE_parse_body("{update2}::{$_GET["filename"]}");
	return $title;
}


function suffix(){
	$filename=$_GET["filename"];
	return "&filename=".urlencode($filename);
	
}


function js(){
	
	
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$suffix=suffix();
	$title=title();
	
	echo "
	function Start$t(){	
		RTMMail('800','$page?popup=yes$suffix','$title');
	}
	Start$t();";
	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;
	$suffix=suffix();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($GLOBALS["CACHEFILE"]));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
	$md5file=trim(md5_file($GLOBALS["LOGSFILES"]));
	
	echo "// CACHE FILE: {$GLOBALS["CACHEFILE"]} {$prc}%\n";
	echo "// LOGS FILE: {$GLOBALS["LOGSFILES"]} - $md5file ". strlen($md5file)."\n";
	
if ($prc==0){
	if(strlen($md5file)<32){
	echo "
	// PRC = $prc ; md5file=$md5file
	function Start$time(){
			if(!RTMMailOpen()){return;}
			Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}{$suffix}');
	}
	setTimeout(\"Start$time()\",1000);";
	
	
	return;
	}
}


if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file{$suffix}');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc>100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		document.getElementById('title-$t').style.border='1px solid #C60000';
		document.getElementById('title-$t').style.color='#C60000';
		$('#progress-$t').progressbar({ value: 100 });
	}
	setTimeout(\"Start$time()\",1000);
	";
	return;	
	
}

if($prc==100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		RefreshTab('main_artica_meta');
		RTMMailHide();
		}
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
	}
	setTimeout(\"Start$time()\",1500);
";
}

function Launch(){
	$sock=new sockets();
	$filename=$_GET["filename"];
	$fileNameEnc=urlencode($filename);
	$cmd="artica.php?meta-scan-update=yes&filename=$fileNameEnc";
	if($GLOBALS["VERBOSE"]){echo "<H1>RUN $cmd</H1>";}
	writelogs("launch $cmd",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork($cmd);
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	Launch();
	$suffix=suffix();
	
	

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	
		$title=title();
		$text=$tpl->_ENGINE_parse_body($title);
	
	
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0{$suffix}');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	$t=explode("\n",@file_get_contents($GLOBALS["LOGSFILES"]));
	krsort($t);
	echo @implode("\n", $t);
	
}