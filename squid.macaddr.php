<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableMacAddressFilter"])){save();exit;}
	js();
	
	
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$html="YahooWin2('450','$page?popup=yes','$title');";
	echo $html;	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){
		$EnableMacAddressFilterCentral=$sock->GET_INFO("EnableMacAddressFilterCentral");
		if(!is_numeric($EnableMacAddressFilterCentral)){$EnableMacAddressFilterCentral=1;}
		$EnableMacAddressFilter=$EnableMacAddressFilterCentral;
	}
	$p=Paragraphe_switch_img("{enable_mac_squid_filters}", 
			"{enable_mac_squid_filters_explain}","EnableMacAddressFilter",$EnableMacAddressFilter,null,400);
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		
		<td colspan=2>$p</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",16)."</td>
	</tr>
	</table>
	
	<script>
		var x_Save$t= function (obj) {
			document.getElementById('$t').innerHTML='';
			YahooWin2Hide();
		}
	
	
	function Save$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
		var XHR = new XHRConnection();
		
		XHR.appendData('EnableMacAddressFilter',document.getElementById('EnableMacAddressFilter').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}
</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMacAddressFilter", $_POST["EnableMacAddressFilter"]);
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}

