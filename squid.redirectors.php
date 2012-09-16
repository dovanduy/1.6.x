<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
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
	$html="YahooWin2('440','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$squid=new squidbee();
	$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
	if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
	if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
	if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
	if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
	$t=time();
	$enable_UfdbGuard=0;
	if($squid->enable_UfdbGuard==1){$enable_UfdbGuard=1;}
	
	$html="
	<div class=explain style='font-size:12px'>{squid_redirectors_howto}</div>
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
		XHR.appendData('url_rewrite_children',document.getElementById('url_rewrite_children').value);
		XHR.appendData('url_rewrite_startup',document.getElementById('url_rewrite_startup').value);
		XHR.appendData('url_rewrite_idle',document.getElementById('url_rewrite_idle').value);
		XHR.appendData('url_rewrite_concurrency',document.getElementById('url_rewrite_concurrency').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_UrlReWriteSave);		
		}
		
	function CheckConcurrency(){
		var enable_UfdbGuard=$enable_UfdbGuard;
		if(enable_UfdbGuard==1){
			document.getElementById('url_rewrite_concurrency').value=0;
			document.getElementById('url_rewrite_concurrency').disabled=true;
		}
	
	}
	
	CheckConcurrency();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function save(){
	$sock=new sockets();
	$datas=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($datas, "SquidRedirectorsOptions");
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}


	
	
