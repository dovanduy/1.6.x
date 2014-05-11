<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["mybrowsers-js"])){mybrowsers_js();exit;}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["squid-infra"])){squid_infra();exit;}

js();

function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "LoadAjax('BodyContent','$page?popup=yes',true)";
}


function mybrowsers_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->SET_INFO("MyBrowsersSetupShow", 1);
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{my_browsers}");
	echo "YahooWin('895.6','squid.popups.php?browsers-setup=yes','$title',true)";	
	
}



function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:1099px' class=form>
	<table style='width:100%'>
		<tr>
			<td valign='top' style='width:33%'>
				<center style='font-size:22px'>{infrastructure}</center>
				<div id='squid-infra'></div>
			</td>
			<td valign='top' style='width:33%'><center style='font-size:22px'>{status}</center></td>
			<td valign='top' style='width:33%'><center style='font-size:22px'>{suggestions}</center></td>
		</tr>
	</table>
	<script>
		LoadAjaxTiny('squid-infra','$page?squid-infra=yes');
	</script>
	
			
	</script>
		
			
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function squid_infra(){
	$page=CurrentPageName();
	$tpl=new templates();
	$squid_all_ports=squid_all_ports();
	
	$help=Paragraphe32("my_browsers", "how_to_connect_browsers", "Loadjs('$page?mybrowsers-js=yes')", "info-24.png");
	
	$html="
	<div class=form style='width:93%'>		
	$squid_all_ports
	</div>
	<br>$help";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	//
}

function squid_all_ports(){
	
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$listen_port=$squid->listen_port;
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr=$_SERVER["REMOTE_ADDR"];}
	
	$ssl_port=$squid->ssl_port;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	
	if($hasProxyTransparent==1){
		$listen_port=$squid->second_listen_port;
		
	}
	
	$f[]="<table style='width:100%'>
	<tr>
	<td style='width:48px;vertical-align:top'><img src='img/48-idisk-server.png'></td>
	<td style='width:100%;vertical-align:top'>	<table style='width:100%'>	
	";
	
	$f[]="
	<tr>
		<td style='font-size:14px;width:5%' class=legend nowrap>{listen_port}</td>
		<td style='font-size:12px;font-weight:bold'><a href=\"javascript:blur();\" style='font-size:14px;font-weight:bold;text-decoration:underline' OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\">$SquidBinIpaddr:$listen_port</a></td>
	</tr>
	";
	
	$f[]="</table></td>
			</tr>
	</table>";
	
	return @implode("\n", $f);
	
	
}


