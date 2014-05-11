<?php
	include_once('ressources/class.templates.inc');
	
$GLOBALS["LOGS_PATH"]="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.txt";	
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.cache.center.empty.progress";
$GLOBALS["YAHOOWIN"]="YahooWin5";

	if(isset($_GET["logs-starter"])){logs_starter();exit;}
	if(isset($_GET["step1"])){popup();exit;}
	if(isset($_POST["restore-path"])){restorefrom();exit;}
	if(isset($_POST["restore-logs"])){LogsDetails();exit;}
	if(isset($_GET["ShowProgress-js"])){ShowProgress_js();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "{$GLOBALS["YAHOOWIN"]}('650','$page?step1=yes','progesss')";
	
	
}


function ShowProgress_js(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$titleAdd=null;
	$please_wait=$tpl->javascript_parse_text("{please_wait}");
	$sock=new sockets();
	
	
	
	header("content-type: application/x-javascript");
	$file=$GLOBALS["PROGRESS_FILE"];
	$ARRAY=unserialize(@file_get_contents($file));
	if(!is_array($ARRAY)){
		echo "
		function Start$tt(){
			if( !{$GLOBALS["YAHOOWIN"]}Open() ){return;}
			Loadjs('$page?ShowProgress-js=yes&t=$t');
		}
		document.getElementById('title$t').innerHTML='$please_wait';
		setTimeout('Start$tt()',3000);
		";
		return;
		
	}
	
	$text=$tpl->javascript_parse_text($ARRAY["TEXT"]);
	$prc=$ARRAY["POURC"];
	
	if($prc>99){
		echo "
			if({$GLOBALS["YAHOOWIN"]}Open()){
				document.getElementById('title$t').innerHTML='$text&nbsp;';
				$('#Status$t').progressbar({ value: $prc });
			}
			";
		return;
	}
	
	
	
	echo "
	function Start$tt(){
		if(!{$GLOBALS["YAHOOWIN"]}Open()){return;}
		GetLogs$t();
		Loadjs('$page?ShowProgress-js=yes&t=$t');
	}
	
	if(document.getElementById('title$t')){
		if({$GLOBALS["YAHOOWIN"]}Open()){
			document.getElementById('title$t').innerHTML='$text&nbsp;$titleAdd';
			$('#Status$t').progressbar({ value: $prc });
			setTimeout('Start$tt()',2000);
		}
	}
	";	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	
	<div style='font-size:22px;text-align:center;margin:10px' id='title$t'></div>
	<div style='margin:10px;min-height:75px' id='Status$t'></div>
	<div id='start-$t'></div>		
	<script>
		var x_GetLogs$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){
	      	document.getElementById('textToParseCats-$t').innerHTML=tempvalue;
	       }

	      }	

	      
		function GetLogs$t(){
			var XHR = new XHRConnection();
			XHR.appendData('restore-logs','yes');
			XHR.appendData('t','$t');
			XHR.setLockOff();
			XHR.sendAndLoad('$page', 'POST',x_GetLogs$t);		
		
		}
		$('#Status$t').progressbar({ value: 1 });
		
		LoadAjax('start-$t','$page?logs-starter=yes&t=$t');
		
		
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function logs_starter(){
$t=$_GET["t"];
$page=CurrentPageName();
$html="	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>Loadjs('$page?ShowProgress-js=yes&t=$t');</script>
	";
echo $html;
}
function LogsDetails(){
	$logfile=$GLOBALS["LOGS_PATH"];
	$f=explode("\n",@file_get_contents($logfile));
	krsort($f);
	echo @implode("\n", $f);
	
}

