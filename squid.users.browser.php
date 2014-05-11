<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.users.inc");


if(isset($_GET["internalnets"])){internalnets();exit;}
if(isset($_GET["yourbrowsers"])){yourbrowsers();exit;}
if(isset($_GET["img-zoomjs"])){img_zoom_js();exit;}
if(isset($_GET["img-zoomp"])){img_zoom_popup();exit;}
if(isset($_GET["yourproxy"])){yourproxy();exit;}
tabs();

function img_zoom_js(){
	$page=CurrentPageName();
	$title="{$_GET["img-zoomjs"]}";
	echo "YahooWin2('650','$page?img-zoomp={$_GET["img-zoomjs"]}','$title')";
	
}

function img_zoom_popup(){
	
	echo "<center><img src='img/{$_GET["img-zoomp"]}'></center>";
}

function tabs(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;
	
	$array["internalnets"]="{internal_networks}";
	$array["yourbrowsers"]="{configure_your_browser}";
	$array["yourproxy"]="{configure_your_proxy}";
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="internalnets"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.users.proxy.pac.rules.php?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_browser_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_browser_tabs').tabs();
			});
		</script>";	

}

function internalnets(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;	
	
	$add_categories=Paragraphe("64-categories-add.png", "{add_category}", "{add_category_text}","AddCategory(0)");
	$html="<div class=explain style='font-size:14px'>{banned_categories_explain}</div>";
	
	$tr[]=$add_categories;
	
	$table=CompileTr3($tr);
	
	echo "<div class=form>".$tpl->_ENGINE_parse_body("$html{$table}")."</div>";
	
}

function yourbrowsers(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();
	$sql="SELECT zmd5 FROM usersisp WHERE userid='{$_SESSION["uid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));		
	$packuri="http://{$_SERVER["SERVER_NAME"]}:{$_SERVER["SERVER_PORT"]}/squid.users.logon.php?proxypac={$ligne["zmd5"]}";
	
	$html="<div style='font-size:18px'>{how_to_configure_browser_isp}</div>
	<div style='font-size:18px'>{how_to_configure_browser_isp2}</div>
	<table style='width:99%;margin-top:10px' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/chrome-logo-128.png'></td>
			<td valign='top'><div style='font-size:16px'>Google Chrome</div>
				<p style='font-size:14px'>{chrome_pac_how_to}</p>
		</td>
		<tr><td colspan=2><div style='font-size:15px;color:#AC0909'>$packuri</strong></td></tr>
		</tr>
	</table>
	<table style='width:99%;margin-top:10px' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/ie-logo-128.png'></td>
			<td valign='top'>
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:1%'><a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$page?img-zoomjs=ieproxypac.png')\"-><img src='img/ieproxypac-128.png'></td>
				<td valign='top'>
				<div style='font-size:16px'>Internet Explorer</div>
				<p style='font-size:14px'>{iepac_how_to}</p>
				<div style='font-size:15px;color:#AC0909'>$packuri</strong>
				</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr><td colspan=2><div style='font-size:15px;color:#AC0909'>$packuri</strong></td></tr>
	</table>	
	


	<table style='width:99%;margin-top:10px' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/firefox-logo-128.png'></td>
			<td valign='top'>
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:1%'><a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$page?img-zoomjs=firefoxpachowto.png')\"-><img src='img/firefoxpachowto-128.png'></td>
				<td valign='top'>
				<div style='font-size:16px'>Mozilla FireFox</div>
				<p style='font-size:14px'>{firefoxpac_how_to}</p>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr><td colspan=2><div style='font-size:15px;color:#AC0909'>$packuri</strong></td></tr>
	</table>	
	
	<table style='width:99%;margin-top:10px' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/safari-logo-128.png'></td>
			<td valign='top'>
				<div style='font-size:16px'>Safari</div>
				<p style='font-size:14px'>{safaripac_how_to}</p>
				<div>
				<a href=\"http://www.articatech.net/download/Proxy_Configuration_Mac_OSX_Leopard.pdf\" style='font-size:16px;color:#AC0909;text-decoration:underline'>Configuring your proxy settings – MAC OSX</a>
				</div>
				
			</td>
		</td>
		</tr>
		<tr><td colspan=2><div style='font-size:15px;color:#AC0909'>$packuri</strong></td></tr>
	</table>	
	
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function yourproxy(){
	$tpl=new templates();
	$sock=new sockets();
	$squid=new squidbee();
	$SquidISPProxyServerAddress=$sock->GET_INFO("SquidISPProxyServerAddress");
	if($SquidISPProxyServerAddress==null){$SquidISPProxyServerAddress=$sock->GET_INFO("SquidBinIpaddr");}
	$SquidISPProxyServerAddress=$SquidISPProxyServerAddress.":".$squid->listen_port;
	if($squid->ICP_PORT>0){
		$icp="
	<tr>
		<td class=legend style='font-size:16px'>{icp_port}:</td>
		<td style='font-size:16px'>$squid->ICP_PORT</td>
	</tr>
		";
		
	}
	$html="
	<div class=explain style='font-size:16px'>
	{configure_your_isp_proxy_explain}</div>
	<table style='width:100%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{parent_proxy}:</td>
		<td style='font-size:16px'>$SquidISPProxyServerAddress</td>
	</tr>
	$icp
	</table>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


?>
