<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_POST["SquidBinIpaddr"])){save();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{listen_address}");
	$page=CurrentPageName();
	$html="
		YahooWin3('420','$page?popup=yes','$title');
	
	";
		echo $html;
	
	
	
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidBinIpaddr", $_POST["SquidBinIpaddr"]);
	$squid=new squidbee();
	$squid->global_conf_array["tcp_outgoing_address"]=$_POST["tcp_outgoing_address"];
	$squid->SaveToLdap(true);
	$sock->getFrameWork("cmd.php?squid-restart=yes");
}




function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr=null;}
	$squid=new squidbee();
	$tcp_outgoing_address=$squid->global_conf_array["tcp_outgoing_address"];
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips[null]="{all}";
	$t=time();
	$pfws=$ip->ALL_IPS_GET_ARRAY();
	$pfws[null]="{none}";
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{listen_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($ips,"bindip-$t",$SquidBinIpaddr,"style:font-size:16px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{forward_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($pfws,"tcp_outgoing_address",$tcp_outgoing_address,"style:font-size:16px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSquidBinIpaddr()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_SaveSquidBinIpaddr=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin3Hide();
	}	
	
	function SaveSquidBinIpaddr(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidBinIpaddr',document.getElementById('bindip-$t').value);
		XHR.appendData('tcp_outgoing_address',document.getElementById('tcp_outgoing_address').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveSquidBinIpaddr);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}	