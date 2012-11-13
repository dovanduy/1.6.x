<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.fetchmail.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["fetchrc"])){fetchrc();exit;}
if(isset($_POST["fetchmail-import-path"])){perform();exit;}
if(isset($_GET["get-logs"])){events();exit;}
if(isset($_POST["fetchmail-import-compiled-path"])){perform_import_compiled();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_FETCHMAIL}::{import}");
	echo "YahooWin4('700','$page?tabs=yes&t={$_GET["t"]}','$title');";
	
	
}

function tabs(){
	
	$page=CurrentPageName();
	
	$array["popup"]='{from_csv_file}';
	$array["fetchrc"]='{from_compiled_file}';
	$style="style='font-size:13.5px'";
	while (list ($num, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?$num=yes&t={$_GET["t"]}\"><span $style>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_fetchmail_import style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_fetchmail_import').tabs();
			
			
			});
		</script>";		
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($tab);	
	
	
}

function fetchrc(){
$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	if(!isset($_COOKIE["fetchmail-import-compiled-path"])){$_COOKIE["fetchmail-import-compiled-path"]="/etc/fetchmailrc";}	
	$html="
	<center id='simple-$tt'></center>
	<div class=explain style='font-size:13px'>{fetchmail_importcomp_explain}</div>
	<div style='width:100%;text-align:right;font-size:13px'><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=288','1024','900');\" 
	style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{path}:</td>
		<td>". Field_text("fetchmail-import-compiled-path-$tt",$_COOKIE["fetchmail-import-compiled-path"],"font-size:18px;width:410px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?target-form=fetchmail-import-compiled-path-$tt')","14px")."</td>
	</tr>	
	</table>
	<tr>
		<td colspan=3 align='right'><hr>". button("{import}","ImportFetchNow$tt()","16px")."</td>
	</tr>	
	</table>
	<script>
var x_ImportFetchNow$tt = function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('simple-$tt').innerHTML='';
	      $('#flexRT$t').flexReload();
	      }		
		
		function ImportFetchNow$tt(){
			if(!document.getElementById('fetchmail-import-compiled-path-$tt')){alert('fetchmail-import-compiled-path-$tt !!!???');return;}
			var XHR = new XHRConnection();
			var path=document.getElementById('fetchmail-import-compiled-path-$tt').value;
			Set_Cookie('fetchmail-import-compiled-path',path,'3600', '/', '', '');
			XHR.appendData('fetchmail-import-compiled-path',path);
			AnimateDiv('simple-$tt');
			XHR.sendAndLoad('$page', 'POST',x_ImportFetchNow$tt);	
		}	
	</script>
";	
		echo $tpl->_ENGINE_parse_body($html);
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];	
	$html="
	<div class=explain style='font-size:13px'>{fetchmail_import_explain}</div>
	<div style='width:100%;text-align:right;font-size:13px'><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=288','1024','900');\" 
	style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{path}:</td>
		<td>". Field_text("fetchmail-import-path-$tt",$_COOKIE["fetchmail-import-path"],"font-size:18px;width:410px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?select-file=csv&target-form=fetchmail-import-path-$tt')","14px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{analyze}","ImportFetchNow$tt()","16px")."</td>
	</tr>
	</table>
	<div id='simple-$tt' style='min-height:150px;height:250px;overflow:auto;width:95%' class=form></div>	
	<div style='width:100%;text-align:right;font-size:13px'>". imgtootltip("20-refresh.png","{refresh}","FetchMailImportLogs$tt()")."</div>
	<script>
	
	
var x_ImportFetchNow$tt = function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('simple-$tt').innerHTML='';
	      FetchMailImportLogs$tt();
	      $('#flexRT$t').flexReload();
	      }		
		
		function ImportFetchNow$tt(){
			if(!document.getElementById('fetchmail-import-path-$tt')){alert('fetchmail-import-path-$tt !!!???');return;}
			var XHR = new XHRConnection();
			var path=document.getElementById('fetchmail-import-path-$tt').value;
			Set_Cookie('fetchmail-import-path',path,'3600', '/', '', '');
			XHR.appendData('fetchmail-import-path',path);
			AnimateDiv('simple-$tt');
			XHR.sendAndLoad('$page', 'POST',x_ImportFetchNow$tt);	
		}	
	
	
		function FetchMailImportLogs$tt(){
			LoadAjax('simple-$tt','$page?get-logs=yes&tt=$tt');
		}
		
		FetchMailImportLogs$tt();
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function perform(){
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?import=yes&path=".base64_encode($_POST["fetchmail-import-path"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{importation_background_text}",1);
	
}

function perform_import_compiled(){
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?import-compiled=yes&path=".base64_encode($_POST["fetchmail-import-compiled-path"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{importation_background_text}",1);	
}

function events(){
	if(!is_file("ressources/logs/web/fetchmail.import.log")){
		echo "<div style='font-size:12px'><code>Waiting....</code></div>\n";
		return;
		
	}
	$f=file("ressources/logs/web/fetchmail.import.log");
	while (list ($num, $ligne) = each ($f) ){
		$ligne=str_replace("\r", "", $ligne);
		$ligne=str_replace("\n", "", $ligne);
		$ligne=str_replace('"', "", $ligne);
		if(trim($ligne)==null){continue;}	
		echo "<div style='font-size:12px;text-align:left'><code>$ligne</code></div>\n";
	}
		
		
}


