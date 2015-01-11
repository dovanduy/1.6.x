<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.nodes.squid.inc');
	
	
	
	$user=new usersMenus();
	
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["url_rewrite_children"])){save();exit;}
	
	
js();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{squid_redirectors}");
	$html="YahooWin2('440','$page?popup=yes&nodeid={$_GET["nodeid"]}','$title')";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	

	$sock=new squidnodes($_GET["nodeid"]);
	
	$RedirectorsArray=unserialize($sock->GET("SquidRedirectorsOptions"));
	if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
	if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
	if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
	if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
	$t=time();
	$EnableUfdbGuard=$sock->GET("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	
	
	
	$html="
	<div class=text-info style='font-size:12px'>{squid_redirectors_howto}</div>
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{url_rewrite_children}:</td>
		<td>". Field_text("url_rewrite_children",$RedirectorsArray["url_rewrite_children"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{url_rewrite_children_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{url_rewrite_startup}:</td>
		<td>". Field_text("url_rewrite_startup",$RedirectorsArray["url_rewrite_startup"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{url_rewrite_startup_text}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{url_rewrite_idle}:</td>
		<td>". Field_text("url_rewrite_idle",$RedirectorsArray["url_rewrite_idle"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{url_rewrite_idle_text}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{url_rewrite_concurrency}:</td>
		<td>". Field_text("url_rewrite_concurrency",$RedirectorsArray["url_rewrite_concurrency"],"font-size:14px;width:60px")."</td>
		<td width=1%>". help_icon("{url_rewrite_concurrency_text}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","UrlReWriteSave()",16)."</td>
	</tr>
	
	</table>
	</div>
	<script>
	var x_UrlReWriteSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin2Hide();
		
	}		

	function UrlReWriteSave(){
		var XHR = new XHRConnection();
		XHR.appendData('nodeid',{$_GET["nodeid"]});
		XHR.appendData('url_rewrite_children',document.getElementById('url_rewrite_children').value);
		XHR.appendData('url_rewrite_startup',document.getElementById('url_rewrite_startup').value);
		XHR.appendData('url_rewrite_idle',document.getElementById('url_rewrite_idle').value);
		XHR.appendData('url_rewrite_concurrency',document.getElementById('url_rewrite_concurrency').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_UrlReWriteSave);		
		}
		
	function CheckConcurrency(){
		document.getElementById('url_rewrite_children').disabled=true;
		document.getElementById('url_rewrite_startup').disabled=true;
		document.getElementById('url_rewrite_idle').disabled=true;
		document.getElementById('url_rewrite_concurrency').disabled=true;
		
		var enable_UfdbGuard=$EnableUfdbGuard;
		if(enable_UfdbGuard==1){
			document.getElementById('url_rewrite_concurrency').value=0;
			document.getElementById('url_rewrite_concurrency').disabled=true;
			document.getElementById('url_rewrite_children').disabled=false;
			document.getElementById('url_rewrite_startup').disabled=false;
			document.getElementById('url_rewrite_idle').disabled=false;
			document.getElementById('url_rewrite_concurrency').disabled=true;			
			
		}
	
	}
	
	CheckConcurrency();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function save(){
	$sock=new squidnodes($_POST["nodeid"]);
	$datas=serialize($_POST);
	$sock->SET("SquidRedirectorsOptions",$datas);
	$sock->SaveToLdap();
	
}


	
	
