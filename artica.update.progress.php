<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["compile-infos-wait"])){compile_infos_wait();exit;}
if(isset($_GET["compile-infos"])){compile_infos();exit;}
if(isset($_POST["scan-now"])){scan_now();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$users=new usersMenus();
	$sock->getFrameWork("system.php?artica-update=yes");
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{building_parameters}");
	$html="YahooSetupControlModalFixed('700','$page?popup=yes','$title')";
	echo $html;
	
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{please_wait},{downloading}");
	$html="
	<div style='width:100%;min-height:600px'>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width=99%>
		<div id='$t' style='width:100%;min-height:600px'>	
			<div style='font-size:18px'>$title</div>
				<center style='margin:20px;margin-top:100px'>
					<img src='img/wait_verybig_mini_red.gif'>
				</center>
			</div>
	</td>
	</tr>
	</table>
	</div>
	<script>
		function SquidCompileAmorce$t(){
			LoadAjax('$t','$page?compile-infos-wait=yes&t=$t',true);
		}
		
		setTimeout('SquidCompileAmorce$t()',2000);
	</script>
	
	";
	
	echo $html;

}

function compile_infos_wait(){
	//YahooSetupControlHide
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	
	$title=$tpl->_ENGINE_parse_body("{please_wait},{downloading}");
	
	$html="
	<div style='font-size:18px;margin-bottom:15px'>$title</div>
	<div id='progress-$t'></div>
	<textarea style='margin-top:5px;
	font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
		function SquidCompileRestartWait$t(){
			Loadjs('$page?compile-infos=yes&t=$t');
		
		}
	
	
		if(YahooSetupControlOpen()){
			$('#progress-$t').progressbar({ value: 0 });
			setTimeout('SquidCompileRestartWait$t()',2000);
		
		}
		
	
	</script>";
	echo $html;
	
}

function compile_infos(){
	$t1=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	if(!is_numeric($_GET["lastsize"])){$_GET["lastsize"]=0;}
	$size=@filesize("/usr/share/artica-postfix/ressources/logs/web/download_progress_text");
	$pgrog=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/download_progress");
	if(!is_numeric($pgrog)){$pgrog=0;}
	if(!is_numeric($size)){$size=0;}
	if($size<>$_GET["lastsize"]){
		echo "
		var xScan$t1= function (obj) {
			var results=obj.responseText;
			if(results.length>3){
				if(document.getElementById('textToParseCats-$t')){document.getElementById('textToParseCats-$t').value=results;}
			}
			if(YahooSetupControlOpen()){
				Loadjs('$page?compile-infos=yes&t=$t&lastsize=$size');
			}
			
		}
		function Scan$t1(){
			$('#progress-$t').progressbar({ value: $pgrog });
			var XHR = new XHRConnection();
			XHR.appendData('scan-now','yes');
			XHR.setLockOff();
			XHR.sendAndLoad('$page', 'POST',xScan$t1);	
		
		}	
		if(YahooSetupControlOpen()){ setTimeout('Scan$t1()',2000); }";
	
		return;
	}
	
echo "function Scan$t1(){
			$('#progress-$t').progressbar({ value: $pgrog });
			Loadjs('$page?compile-infos=yes&t=$t&lastsize=$size');
		
		}		
 		if(YahooSetupControlOpen()){ setTimeout('Scan$t1()',2000); }";	
	
	
}

function scan_now(){
	$datas=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/download_progress_text"));
	krsort($datas);
	echo @implode("\n", $datas);
	
}