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
js();



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
	$SquidEnableProxyPac=$sock->GET_INFO("SquidEnableProxyPac");
	$listen_port=$sock->GET_INFO("SquidProxyPacPort");
	if($listen_port==null){$listen_port=8890;}
	$fiedld=Paragraphe_switch_img("{enable_squid_proxy_pac}",
	"{enable_squid_proxy_pac_text}","SquidEnableProxyPac",$SquidEnableProxyPac,null,350);
	
	$ip=new networking();
	if(is_array($ip->array_TCP)){
		while (list ($eth, $tcip) = each ($ip->array_TCP)){
			if($tcip==null){continue;}
			$uris=$uris."<li style='font-size:16px'>http://$tcip:$listen_port/proxy.pac</li>";
			
		}
		
	}	
	
	$t=time();
	
	$html="
	
	
	
	<center style='width:100%' id='proxypacid'>
	<table style='width:100%' >
	<tr>
	<td valign='top' width=1%><div id='pac-status'></div></td>
	<td valign='top' width=100%>
	<table style='width:99%' class=form>
	<tr>
	<td colspan=2>$fiedld</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>HTTP&nbsp;{listen_port}:</td>
		<td>". Field_text("listen_port-$t",$listen_port,"font-size:16px;width:90px;padding:5px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><div style='margin-top:15px'>". button("{apply}","squid_proxy_pac_save()",18)."</div></td>
	</tr>
	</table>
	</td>
	</tr>
	</table>
	</center>
	<hr>
	<div style='font-size:18px'>{uri_add_in_browser}:</div>
	<ul>$uris</ul>
	<hr>
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
	
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("APP_PROXY_PAC",$ini,null,1);
	
	$script="
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_config_proxypac')")."</div> 
	";
	
	echo $tpl->_ENGINE_parse_body($APP_PROXY_PAC).$script;
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