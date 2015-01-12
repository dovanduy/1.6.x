<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["SquidEnableISPMode"])){parameters_save();exit;}
	if(isset($_POST["SendTestMessage"])){SendTestMessage();exit;}
	if(isset($_GET["SendTestMessage"])){SendTestMessage();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{SQUID_ISP_MODE}");
	echo "YahooWin('815','$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
		
	$array["parameters"]='{parameters}';
	$array["lockedcatz"]='{locked_categories}';
	$array["whitelists"]='{global_whitelists}';
	$array["members"]='{members}';
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="lockedcatz"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.isp.lockedcatz.php?$num=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="whitelists"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.isp.lockedcatwhitez.php?$num=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="members"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.isp.members.php?$num=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_squid_isp_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_squid_isp_tabs').tabs();
			});
		</script>";	
	
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$t=time();
	
	$SquidEnableISPMode=$sock->GET_INFO("SquidEnableISPMode");
	$SquidEnableISPublicRegister=$sock->GET_INFO("SquidEnableISPublicRegister");
	$SquidISPSmtpServer=$sock->GET_INFO("SquidISPSmtpServer");
	$SquidISPSmtpFrom=$sock->GET_INFO("SquidISPSmtpFrom");
	$SquidISPSmtpAuthUser=$sock->GET_INFO("SquidISPSmtpAuthUser");
	$SquidISPSmtpAuthPassword=$sock->GET_INFO("SquidISPSmtpAuthPassword");
	if(!is_numeric($SquidEnableISPMode)){$SquidEnableISPMode=0;}
	if(!is_numeric($SquidEnableISPublicRegister)){$SquidEnableISPublicRegister=0;}
	$SquidISPProxyServerAddress=$sock->GET_INFO("SquidISPProxyServerAddress");
	if($SquidISPProxyServerAddress==null){$SquidISPProxyServerAddress=$sock->GET_INFO("SquidBinIpaddr");}
	
	if($SquidEnableISPMode<>null){
		$testmessage="<br><div style='width:100%;text-align:right'>". button("{test_message}","SquidISPTestMessage()")."</div>";
	}
	
	$html="
	<div style='font-size:16px' class=text-info id=$t >{SQUID_ISP_MODE_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enable_service}:</td>
		<td>". Field_checkbox("SquidEnableISPMode", 1,$SquidEnableISPMode,"SquidEnableISPModeCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{proxy_public_address}:</td>
		<td>". Field_text("SquidISPProxyServerAddress", $SquidISPProxyServerAddress,"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{allow_public_register}:</td>
		<td>". Field_checkbox("SquidEnableISPublicRegister", 1,$SquidEnableISPublicRegister)."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{smtp_server}:</td>
		<td>". Field_text("SquidISPSmtpServer", $SquidISPSmtpServer,"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{smtp_from}:</td>
		<td>". Field_text("SquidISPSmtpFrom", $SquidISPSmtpFrom,"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{smtp_username}:</td>
		<td>". Field_text("SquidISPSmtpAuthUser", $SquidISPSmtpAuthUser,"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{smtp_password}:</td>
		<td>". Field_password("SquidISPSmtpAuthPassword", $SquidISPSmtpAuthPassword,"font-size:14px;width:220px")."
		
		</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>". button("{apply}", "SquidISPSave()",18)."</td>
	</tr>
	</table>
	$testmessage	
	<script>
	
		function SquidEnableISPModeCheck(){
			document.getElementById('SquidEnableISPublicRegister').disabled=true;
			document.getElementById('SquidISPSmtpServer').disabled=true;
			document.getElementById('SquidISPSmtpFrom').disabled=true;
			document.getElementById('SquidISPSmtpAuthPassword').disabled=true;
			document.getElementById('SquidISPSmtpAuthUser').disabled=true;
			document.getElementById('SquidISPProxyServerAddress').disabled=true;				
			
			
			
			if(document.getElementById('SquidEnableISPMode').checked){
				document.getElementById('SquidEnableISPublicRegister').disabled=false;
				document.getElementById('SquidISPSmtpServer').disabled=false;
				document.getElementById('SquidISPSmtpFrom').disabled=false;
				document.getElementById('SquidISPSmtpAuthPassword').disabled=false;
				document.getElementById('SquidISPSmtpAuthUser').disabled=false;
				document.getElementById('SquidISPProxyServerAddress').disabled=false;						
			}
		}
		
	

    function SquidISPTestMessage(){
    	var XHR = new XHRConnection();
    	XHR.appendData('SendTestMessage','yes');
    	var MailTo=prompt('Recipient:','$SquidISPSmtpFrom');
    	XHR.appendData('MailTo',MailTo);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SquidISPSave);    	
    }
    
	var X_SquidISPSave=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('main_squid_isp_tabs');
		
    }    
    
	function SquidISPSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('SquidEnableISPMode').checked){XHR.appendData('SquidEnableISPMode',1);}else{XHR.appendData('SquidEnableISPMode',0);}
		if(document.getElementById('SquidEnableISPublicRegister').checked){XHR.appendData('SquidEnableISPublicRegister',1);}else{XHR.appendData('SquidEnableISPublicRegister',0);}
		XHR.appendData('SquidISPSmtpServer',document.getElementById('SquidISPSmtpServer').value);
		XHR.appendData('SquidISPSmtpFrom',document.getElementById('SquidISPSmtpFrom').value);
		XHR.appendData('SquidISPSmtpAuthUser',document.getElementById('SquidISPSmtpAuthUser').value);
		var SquidISPSmtpAuthPassword=encodeURIComponent(document.getElementById('SquidISPSmtpAuthPassword').value);
		XHR.appendData('SquidISPSmtpAuthPassword',SquidISPSmtpAuthPassword);
		
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SquidISPSave);		
	}    
    
		
	function SquidISPSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('SquidEnableISPMode').checked){XHR.appendData('SquidEnableISPMode',1);}else{XHR.appendData('SquidEnableISPMode',0);}
		if(document.getElementById('SquidEnableISPublicRegister').checked){XHR.appendData('SquidEnableISPublicRegister',1);}else{XHR.appendData('SquidEnableISPublicRegister',0);}
		XHR.appendData('SquidISPSmtpServer',document.getElementById('SquidISPSmtpServer').value);
		XHR.appendData('SquidISPSmtpFrom',document.getElementById('SquidISPSmtpFrom').value);
		XHR.appendData('SquidISPSmtpAuthUser',document.getElementById('SquidISPSmtpAuthUser').value);
		var SquidISPSmtpAuthPassword=encodeURIComponent(document.getElementById('SquidISPSmtpAuthPassword').value);
		XHR.appendData('SquidISPProxyServerAddress',document.getElementById('SquidISPProxyServerAddress').value);
		XHR.appendData('SquidISPSmtpAuthPassword',SquidISPSmtpAuthPassword);
		
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SquidISPSave);		
	}
	
	SquidEnableISPModeCheck();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function parameters_save(){
	$sock=new sockets();
	$_POST["SquidISPSmtpAuthPassword"]=url_decode_special_tool($_POST["SquidISPSmtpAuthPassword"]);
	
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
}

function SendTestMessage(){
	$sock=new sockets();
	include_once(dirname(__FILE__)."/ressources/class.smtp.sockets.inc");
	$tpl=new templates();
	$smtp=new SMTP_SOCKETS();

	
	$SquidISPSmtpServer=$sock->GET_INFO("SquidISPSmtpServer");
	$SquidISPSmtpFrom=$sock->GET_INFO("SquidISPSmtpFrom");
	$SquidISPSmtpAuthUser=$sock->GET_INFO("SquidISPSmtpAuthUser");
	$SquidISPSmtpAuthPassword=$sock->GET_INFO("SquidISPSmtpAuthPassword");	
	if($_POST["MailTo"]<>null){
		$SquidISPSmtpTo=$_POST["MailTo"];
		
	}else{
		$SquidISPSmtpTo=$SquidISPSmtpFrom;
	}
	
	if(!$smtp->SendSMTPMailInline($SquidISPSmtpServer, 25, $SquidISPSmtpAuthUser,$SquidISPSmtpAuthPassword,$SquidISPSmtpFrom, $SquidISPSmtpTo, "This is a test message", "If you read it, the message has been successfully forwarded")){
		echo $tpl->javascript_parse_text("{failed}\n".@implode("\n",$smtp->error),1);
		
	}else{
		echo $tpl->javascript_parse_text("{success} $SquidISPSmtpFrom -> $SquidISPSmtpTo\n",1);
	}
	
	
}




