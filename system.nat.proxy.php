<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["EnableNatProxy"])){Save();exit;}
	tabs();
	
	
	function tabs(){
		$page=CurrentPageName();
		$tpl=new templates();
		$fontsize="font-size:18px;";
		$eth=$_GET["eth"];
		$ID=$_GET["ID"];
		$table=$_GET["table"];
		$eth=$_GET["eth"];
		$t=$_GET["t"];
	
		$array["status"]='{status}';
		
	
		$fontsize="font-size:18px";
		while (list ($index, $ligne) = each ($array) ){
	
			if($index=="antihack"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.iptables.php?tab-iptables-rules=yes&sshd=yes\"><span style='$fontsize'>$ligne</span></a></li>\n");
				continue;
			}
	
			
			$html[]= "<li><a href=\"$page?$index=yes\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			
		}
	
	
		echo build_artica_tabs($html,'tabs_nat_proxy');
	
	}
	
	
function status(){
	$sock=new sockets();
	$EnableNatProxy=intval($sock->GET_INFO("EnableNatProxy"));
	$NatProxyServer=$sock->GET_INFO("NatProxyServer");
	$NatProxyPort=intval($sock->GET_INFO("NatProxyPort"));
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{enable_NAT_proxy}", 
				"{enable_NAT_proxy_text}","EnableNatProxy",$EnableNatProxy,null,800)."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{destination_server}:</td>
		<td>". Field_text("NatProxyServer",$NatProxyServer,"font-size:18px;width:80%")."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{destination_port}:</td>
		<td>". Field_text("NatProxyPort",$NatProxyPort,"font-size:18px;width:120px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>". button("{apply}","Save$t()",26)."</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	
}

function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('EnableNatProxy',  document.getElementById('EnableNatProxy').value);
	XHR.appendData('NatProxyServer',  document.getElementById('NatProxyServer').value);
	XHR.appendData('NatProxyPort',  document.getElementById('NatProxyPort').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>						
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
		
	}
}
