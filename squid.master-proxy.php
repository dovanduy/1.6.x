<?php
if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_MEM"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["childs"])){childs();exit;}
	if(isset($_POST["SquidAsMasterPeer"])){SquidAsMasterPeer();exit;}
	tabs();
	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["childs"]='{childs_proxy}';
		
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="childs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.children.php?popup=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_childs_tabs",900);
}	

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ip=new networking();
	$t=time();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	if($SquidAsMasterPeerIPAddr==null){$SquidAsMasterPeerIPAddr="0.0.0.0";}
	
	if($SquidAsMasterPeerPort==0){$SquidAsMasterPeerPort=8050;}
	if($SquidAsMasterPeerPortSSL==0){$SquidAsMasterPeerPortSSL=8051;}
	$SquidAsMasterLogChilds=intval($sock->GET_INFO("SquidAsMasterLogChilds"));
	$p1=Paragraphe_switch_img("{enable_as_master_proxy}", "{enable_as_master_proxy_explain}","SquidAsMasterPeer-$t",$SquidAsMasterPeer,null,550);
	$p2=Paragraphe_switch_img("{logging_childs_connections}", "{logging_childs_connections_explain}","SquidAsMasterLogChilds-$t",$SquidAsMasterLogChilds,null,550);
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips["0.0.0.0"]="{all}";
	
	
	$html="
	<div style='width:98%' class=form>
		<table style='width:100%'>
			<tr>
				<td>&nbsp;</td>
				<td >$p1</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td >$p2</td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{listen_address}:</td>
				<td style='font-size:24px'>". Field_array_Hash($ips,"SquidAsMasterPeerIPAddr-$t",$SquidAsMasterPeerIPAddr,"style:font-size:24px")."<td>
				
			</tr>			
			<tr>
				<td class=legend nowrap style='font-size:24px'>{listen_port}:</td>
				<td >". Field_text("SquidAsMasterPeerPort-$t",$SquidAsMasterPeerPort,"font-size:24px;width:100px")."</td>
			</tr>
			<tr>
				<td class=legend nowrap style='font-size:24px'>{listen_port} (SSL):</td>
				<td >". Field_text("SquidAsMasterPeerPortSSL-$t",$SquidAsMasterPeerPortSSL,"font-size:24px;width:100px")."</td>
			</tr>								
			<tr>
				<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",32)."</td>
			</tr>	
		</table>		
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	RefreshTab('main_squid_childs_tabs');
	
}
	
function Save$t(){
	var enabled=1;
	var XHR = new XHRConnection();
	
	XHR.appendData('SquidAsMasterPeer',document.getElementById('SquidAsMasterPeer-$t').value);
	XHR.appendData('SquidAsMasterLogChilds',document.getElementById('SquidAsMasterLogChilds-$t').value);
	XHR.appendData('SquidAsMasterPeerPort',document.getElementById('SquidAsMasterPeerPort-$t').value);
	XHR.appendData('SquidAsMasterPeerPortSSL',document.getElementById('SquidAsMasterPeerPortSSL-$t').value);
	XHR.appendData('SquidAsMasterPeerIPAddr',document.getElementById('SquidAsMasterPeerIPAddr-$t').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>												
						
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SquidAsMasterPeer(){
	$sock=new sockets();
	$sock->SET_INFO("SquidAsMasterPeer", $_POST["SquidAsMasterPeer"]);
	$sock->SET_INFO("SquidAsMasterLogChilds", $_POST["SquidAsMasterLogChilds"]);
	$sock->SET_INFO("SquidAsMasterPeerPort", $_POST["SquidAsMasterPeerPort"]);
	$sock->SET_INFO("SquidAsMasterPeerPortSSL", $_POST["SquidAsMasterPeerPortSSL"]);
	$sock->SET_INFO("SquidAsMasterPeerIPAddr", $_POST["SquidAsMasterPeerIPAddr"]);
	
}

function childs(){
	
	
}
	
	

