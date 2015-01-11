<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');

if(isset($_GET["support-tool"])){support_tool();exit;}
if(isset($_GET["support-package-js"])){support_tool_js();exit;}
if(isset($_GET["support-package-1"])){support_tool_step1();exit;}
if(isset($_GET["support-package-progress"])){support_tool_progress();exit;}
if(isset($_GET["support-tool-status"])){support_tool_status();exit;}
if(isset($_GET["website-tool"])){website_tool();exit;}

if(isset($_GET["request-tool"])){request_tool();exit;}
if(isset($_GET["request-package-js"])){request_tool_js();exit;}
if(isset($_GET["request-package-1"])){request_tool_step1();exit;}
if(isset($_GET["request-package-progress"])){request_tool_progress();exit;}
if(isset($_GET["request-tool-status"])){request_tool_status();exit;}
if(isset($_POST["website-analysis"])){website_tool_post();exit;}
if(isset($_GET["website-analysis-report"])){website_tool_report();exit;}

tabs();

function support_tool_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	echo "Loadjs('$page?support-package-1=yes&t={$_GET["t"]}',false);";
}

function request_tool_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	echo "
	var pp=encodeURIComponent(document.getElementById('requestfield-{$_GET["t"]}').value);
	Loadjs('$page?request-package-1=yes&t={$_GET["t"]}&uri='+pp,false);";	
}

function request_tool_step1(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	@unlink("ressources/support/request.tar.gz");
	$uri=url_decode_special_tool($_GET["uri"]);
	$uri=urlencode($uri);
	$sock->getFrameWork("squid.php?request-package-full=yes&uri=$uri");	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	echo "
	$('#progress-report-$t').progressbar({ value: 5 });
	document.getElementById('title-$t').innerHTML='$title';
	Loadjs('$page?request-package-progress=yes&t={$_GET["t"]}',false);
	";	
	
}

function support_tool_step1(){
	header("content-type: application/x-javascript");
	$sock=new sockets();
	@unlink("ressources/support/support.tar.gz");
	$sock->getFrameWork("squid.php?support-package-full=yes");
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	echo "
	$('#report-$t').progressbar({ value: 5 });
	document.getElementById('title-$t').innerHTML='$title';		
	Loadjs('$page?support-package-progress=yes&t={$_GET["t"]}',false);
	";	
	
}

function request_tool_progress(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/support/request.progress"));
	if(!is_array($array)){echo request_tool_wait();exit;}
	$title=$tpl->javascript_parse_text($array[0]);
	$Progress=intval($array[1]);
	$tt=time();	
	if($Progress>99){request_tool_end();exit;}
	$title2=$tpl->javascript_parse_text("{please_wait}...");	
	echo "
	function Start$tt(){
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title... $title2...';
	$('#progress-report-$t').progressbar({ value: $Progress });
	Loadjs('$page?request-package-progress=yes&t={$_GET["t"]}',false);
	}
	setTimeout('Start$tt()',2500);";	
}
function request_tool_end(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/support/request.progress"));
	$title=$tpl->javascript_parse_text($array[0]);
	$Progress=$tpl->javascript_parse_text($array[1]);
	echo "
	function Start$tt(){
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title';
	$('#progress-report-$t').progressbar({ value: 100 });
	LoadAjax('request-$t','$page?request-tool-status=yes',true);
}
setTimeout('Start$tt()',2500);";

}

function support_tool_progress(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/support/support.progress"));
	if(!is_array($array)){echo support_tool_wait();exit;}
	$title=$tpl->javascript_parse_text($array[0]);
	$Progress=intval($array[1]);
	if($Progress>99){support_tool_end();exit;}
	$title2=$tpl->javascript_parse_text("{please_wait}...");
	$tt=time();
echo "
function Start$tt(){
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title... $title2...';
	$('#report-$t').progressbar({ value: $Progress });
	Loadjs('$page?support-package-progress=yes&t={$_GET["t"]}',false);
}
setTimeout('Start$tt()',2500);";	
	
}

function support_tool_end(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];	
	$tt=time();
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/support/support.progress"));
	if(!is_array($array)){echo support_tool_wait();exit;}
	$title=$tpl->javascript_parse_text($array[0]);
	$Progress=$tpl->javascript_parse_text($array[1]);
echo "
function Start$tt(){
	if(!document.getElementById('report-$t')){return;}
	$('#report-$t').progressbar({ value: 100 });
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title';
	LoadAjax('support-$t','$page?support-tool-status=yes',true);
}
setTimeout('Start$tt()',2500);";		
	
}



function support_tool_wait(){
	header("content-type: application/x-javascript");
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	
	echo "
function Start$tt(){
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title';	
	Loadjs('$page?support-package-progress=yes&t={$_GET["t"]}',false);	
}
	
setTimeout('Start$tt()',2500);";
	
	
}
function request_tool_wait(){
	header("content-type: application/x-javascript");
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{please_wait}...");
	
	echo "
function Start$tt(){
	if(!document.getElementById('title-$t')){return;}
	document.getElementById('title-$t').innerHTML='$title';
	Loadjs('$page?request-package-progress=yes&t={$_GET["t"]}',false);
}
setTimeout('Start$tt()',2500);";	
}

function tabs(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["support-tool"]='{support_package}';
	$array["request-tool"]='{request_package}';
	$array["website-tool"]='{website_analysis}';
	$array["port-tool"]='{proxy_test_port}';
	$sock=new sockets();
	
	
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="port-tool"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.debug.port.php\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}

	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
	}
	echo build_artica_tabs($html, "debug_squid_config",970)."<script>LeftDesign('debug-white-256-opac20.png');</script>";
	
	
}

function support_tool(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{build_support_package}");
	$t=time();
	$html="
	<div class=text-info style='font-size:18px'>{build_support_package_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='width:30%;vertical-align:top'><div id='support-$t'></div></td>
		<td style='width:70%;vertical-align:top;padding-left:30px'>
				<div style='font-size:32px;margin-bottom:20px' id='title-$t'>$title</div>
				<div id='report-$t' style='margin:30px'></div>
				<center style='margin:40px'>". button("{build_now}","AnimateDiv('title-$t');Loadjs('$page?support-package-js=yes&t=$t',true)",32)."</center>
		</td>
	</tr>
	</table>
	</div>
	<div style='margin-bottom:50px'>&nbsp;</div>					
	<script>
	$('#report-$t').progressbar({ value: 0 });
	LoadAjax('support-$t','$page?support-tool-status=yes',true);
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function support_tool_status(){
	if(!is_file("ressources/support/support.tar.gz")){return;}
	
	$size=filesize("ressources/support/support.tar.gz");
	$size=FormatBytes($size/1024);
	$date=date("Y-m-d H:i:s",filemtime("ressources/support/support.tar.gz"));
	
	echo "
	<center style='margin:10px;width:95%' class=form>
		<a href='ressources/support/support.tar.gz'>
		<img src='img/file-compressed-128.png' style='margin:10px'>
		</a>
	<div><a href='ressources/support/support.tar.gz' style='font-size:16px;text-decoration:underline'>support.tar.gz ($size)</a><br>
	<a href='ressources/support/support.tar.gz' style='font-size:16px;text-decoration:underline'>$date</a>
	</div>			
			
	";
	
}

function request_tool_status(){
	if(!is_file("ressources/support/request.tar.gz")){return;}
	
	$size=filesize("ressources/support/request.tar.gz");
	$size=FormatBytes($size/1024);
	$date=date("Y-m-d H:i:s",filemtime("ressources/support/request.tar.gz"));
	
	echo "
	<center style='margin:10px;width:95%' class=form>
	<a href='ressources/support/request.tar.gz'>
	<img src='img/file-compressed-128.png' style='margin:10px'>
	</a>
	<div><a href='ressources/support/request.tar.gz' style='font-size:16px;text-decoration:underline'>request.tar.gz ($size)</a><br>
	<a href='ressources/support/request.tar.gz' style='font-size:16px;text-decoration:underline'>$date</a>
	</div>
		
	";	
	
}

function request_tool(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{build_request_package}");
	$t=time();
	$html="
<div class=text-info style='font-size:18px'>{build_request_package_explain}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
		<td style='width:30%;vertical-align:top'><div id='request-$t'></div></td>
		<td style='width:70%;vertical-align:top;padding-left:30px'>
			<div style='font-size:32px;margin-bottom:20px' id='title-$t'>$title</div>
			". Field_text("requestfield-$t","http://www.artica.fr","font-size:22px;width:80%;margin:10px")."
			<div id='progress-report-$t' style='margin:30px'></div>
			<center style='margin:40px'>". button("{build_now}","AnimateDiv('title-$t');Loadjs('$page?request-package-js=yes&t=$t',true)",32)."</center>
		</td>
	</tr>
	</table>
	</div>
<div style='margin-bottom:50px'>&nbsp;</div>
<script>
	$('#progress-report-$t').progressbar({ value: 0 });
	LoadAjax('request-$t','$page?request-tool-status=yes',true);
</script>
";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function website_tool(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{build_request_package}");
	$t=time();
	$sock=new sockets();
	$ip=new networking();
	
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips[null]="{select}";
	
	$array=unserialize(base64_decode($sock->GET_INFO("WebSiteAnalysis")));
	
	if(!isset($array["website-analysis"])){$array["website-analysis"]="http://www.articatech.com";}
	
	$html="
	<div style='font-size:32px;margin-bottom:20px' id='title-$t'>{website_analysis}</div>
	<div class=text-info style='font-size:18px'>{website_tool_explain}</div>	
	<div id='report-$t' style='margin:30px'></div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='width:30%;vertical-align:middle;font-size:22px'>{request}:</td>
			<td style='width:70%;vertical-align:middle;'>
				". Field_text("requestfield-$t","http://www.artica.fr","font-size:22px;width:80%;margin:10px")."
			</td>
		</tr>
		<tr>
			<td class=legend style='width:30%;vertical-align:middle;font-size:22px'>{connection_timeout}:</td>
			<td style='width:70%;vertical-align:top;'>
				". Field_text("timeout-$t","2","font-size:22px;width:95px;margin:10px")."
			</td>
		</tr>
	<tr>
		<td class=legend style='font-size:22px'>{forward_address}:</td>
		<td style='font-size:22px;vertical-align:middle;'>". Field_array_Hash($ips,"address-$t",$array["website-analysis-address"],"style:font-size:22px")."<td>
	</tr>													
		<tr>
			<td class=legend style='width:30%;vertical-align:middle;font-size:22px'>{proxy_address}:</td>
			<td style='width:70%;vertical-align:top;'>
				". Field_text("proxy-$t","","font-size:22px;width:80%;margin:10px")."
			</td>
		</tr>	
		<tr><td colspan=2><p style='font-size:14px;text-align:right' class=text-info>{website_tool_explain_proxy}</p></td></tr>				
	</table>
	<center style='margin:40px'>". button("{submit}","Submit$t()",32)."</center>
	</div>	
<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		LoadAjax('report-$t','$page?website-analysis-report=yes&t=$t');
		
	}


	function Submit$t(){
		var XHR = new XHRConnection();	
		document.getElementById('report-$t').innerHTML='';
		XHR.appendData('website-analysis',document.getElementById('requestfield-$t').value);
		XHR.appendData('website-analysis-proxy',document.getElementById('proxy-$t').value);
		XHR.appendData('website-analysis-timeout',document.getElementById('timeout-$t').value);
		XHR.appendData('website-analysis-address',document.getElementById('address-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);	
	}
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function website_tool_post(){
	
	$sock=new sockets();
	$datas=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($datas, "WebSiteAnalysis");
	$sock->getFrameWork("squid.php?WebSiteAnalysis=yes");
	
}

function website_tool_report(){
	
	echo "<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' 
	id='c-icap-error-page'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/curl.trace")."</textarea>";
	
}