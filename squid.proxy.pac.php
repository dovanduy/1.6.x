<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["SquidEnableProxyPac"])){Save();exit;}
	if(isset($_GET["pac-status"])){Pac_status();exit;}
	if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
js();

function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_wpad_explain}");
	$t=$_GET["t"];
	$html="
		
	var x_AddNewFreeWeb$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('main_config_proxypac');
}

function AddNewFreeWeb$t(){
var servername=prompt('$addfree');
if(!servername){return;}
var XHR = new XHRConnection();
XHR.appendData('ADD_DNS_ENTRY','');
XHR.appendData('ForceInstanceZarafaID','');
XHR.appendData('ForwardTo','');
XHR.appendData('Forwarder','0');
XHR.appendData('SAVE_FREEWEB_MAIN','yes');
XHR.appendData('ServerIP','');
XHR.appendData('UseDefaultPort','0');
XHR.appendData('UseReverseProxy','0');
XHR.appendData('gpid','');
XHR.appendData('lvm_vg','');
XHR.appendData('servername','wpad.'+servername);
XHR.appendData('sslcertificate','');
XHR.appendData('uid','');
XHR.appendData('useSSL','0');
XHR.appendData('force-groupware','WPAD');
AnimateDiv('status-$t');
XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);
}


AddNewFreeWeb$t();

";
echo $html;

}

function js(){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{proxy_pac}");
	$page=CurrentPageName();
	$html="
		function squid_proxy_pac_load(){
			YahooWin3('600','$page?popup=yes','$title');
		
		}
		

		
	squid_proxy_pac_load();";
	
	echo $html;
	
}


function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	
	
	$t=time();
	
	$link="
		<table style='width:99%' class=form>
		<tr>
						<td width=1%><img src='img/plus-24.png'></td>
						
						<td width=99%>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('$page?add-freeweb-js=yes&t=$t');\"
					 		style=\"font-size:14px;text-decoration:underline\">{add_a_web_service}</a>
						</td>
					</tr></table>";
	
	$html="
	
	
	
	<center style='width:100%' id='proxypacid'>
	$link
	</center>
	
	<div style='font-size:18px;margin-bottom:10px'>{uri_add_in_browser}:</div>
	<div id='pac-status'></div>
	
<script>
		var x_squid_proxy_pac_save= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('squid_main_config');
			RefreshTab('main_config_proxypac');						
				
		}		
		
		function squid_proxy_pac_save(){
		 	var XHR = new XHRConnection();
			XHR.appendData('SquidEnableProxyPac',document.getElementById('SquidEnableProxyPac').value);
			XHR.appendData('listen_port',document.getElementById('listen_port-$t').value);
			AnimateDiv('proxypacid');
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pac_save);
		}
		
		
		LoadAjax('pac-status','$page?pac-status=yes');
		
</script>	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Pac_status(){
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT * FROM freeweb WHERE groupware='WPAD'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$servername=$ligne["servername"];
	
		$tr[]="
		<tr>
		<td width=1%><img src=\"img/arrow-right-24.png\"></td>
		<td width=99%>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername');\" 
		style=\"font-size:16px;text-decoration:underline\">http://$servername/proxy.pac</a>
		</td>
		</tr>
		<tr>
		<td width=1%><img src=\"img/arrow-right-24.png\"></td>
		<td width=99%>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername');\" 
		style=\"font-size:16px;text-decoration:underline\">http://$servername/wpad.dat</a>
		</td>
		</tr>		
		";
	
	}
	
		$html="
		
			<table style=\"width:99%\" class=\"form\">".@implode("\n", $tr)."</table>";
		
		echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidEnableProxyPac",$_GET["SquidEnableProxyPac"]);
	$sock->SET_INFO("SquidProxyPacPort",$_GET["listen_port"]);
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");	
	popup_add_proxy_list();
}

function popup_add_proxy_list(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));		
	if(!is_array($datas["PROXYS"])){
		$squid=new squidbee();
		$listend_port=$squid->listen_port;
		$tpc=new networking();
		while (list ($eth, $ip) = each ($tpc->array_TCP)){
		if($ip==null){continue;}
		$datas["PROXYS"][]="$ip:$listend_port";
		}	
	}else{
		return;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	
}




	
?>