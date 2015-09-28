<?php
if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',null);
		ini_set('error_append_string',null);
}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.dhcpd-sub.inc');


if(!GetRights()){		
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
	}

	
if(isset($_POST["range1"])){Save();exit;}	

dhcp_form();
function dhcp_form(){
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();
	$dhcp=new dhcpd_sub($_GET["nic"]);
	$page=CurrentPageName();
	$users=new usersMenus();
	$t=time();

	if(count($domains)==0){
		$dom=Field_text("ddns_domainname-$t",$dhcp->ddns_domainname,"font-size:22px;");
	}else{
		$domains[null]="{select}";
		$dom=Field_array_Hash($domains,"ddns_domainname-$t",$dhcp->ddns_domainname,null,null,null,";font-size:22px;padding:3px");
	}


	
	$EnableDHCPUseHostnameOnFixed=Field_checkbox_design("EnableDHCPUseHostnameOnFixed-$t",1,$dhcp->EnableDHCPUseHostnameOnFixed);
	$authoritative=Field_checkbox_design("authoritative-$t",1,$dhcp->authoritative);
	$ping_check=Field_checkbox_design("ping_check-$t",1,$dhcp->ping_check);
	$get_lease_hostnames=Field_checkbox_design("get_lease_hostnames-$t",1,$dhcp->get_lease_hostnames);
	
	
	$nicz=new system_nic($_GET["nic"]);
	$ipaddrEX=explode(".",$nicz->IPADDR);
	unset($ipaddrEX[3]);

	if($dhcp->subnet==null){
		$dhcp->subnet=@implode(".", $ipaddrEX).".0";
	}
	if($dhcp->netmask==null){
		$dhcp->netmask=$nicz->NETMASK;
	}
	if($dhcp->gateway==null){
		$dhcp->gateway=$nicz->GATEWAY;
	}	
	if($dhcp->range1==null){
		$dhcp->range1=@implode(".", $ipaddrEX).".50";
	}
	if($dhcp->range2==null){
		$dhcp->range2=@implode(".", $ipaddrEX).".254";
	}
	if($dhcp->broadcast==null){
		$dhcp->broadcast=@implode(".", $ipaddrEX).".255";
	}	
	
	
	$html="<div id='dhscpsettings' class=form>
	<div class='BodyContent'>
	<table style='width:98%'>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td>". Field_checkbox_design("EnableDHCPServer-$t", 1,$dhcp->EnableDHCPServer)."</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>{EnableDHCPUseHostnameOnFixed}:</td>
		<td>$EnableDHCPUseHostnameOnFixed</td>
		<td>&nbsp;</td>
		<td>". help_icon('{EnableDHCPUseHostnameOnFixed_explain}')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{authoritative}:</td>
		<td>$authoritative</td>
		<td>&nbsp;</td>
		<td>". help_icon('{authoritativeDHCP_explain}')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DHCPPing_check}:</td>
		<td>$ping_check</td>
		<td>&nbsp;</td>
		<td>". help_icon('{ping_check_explain}')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{get_lease_hostnames}:</td>
		<td>$get_lease_hostnames</td>
		<td>&nbsp;</td>
		<td>". help_icon('{get_lease_hostnames_text}')."</td>
	</tr>
<tr>
	<td colspan=4>
				<div style='margin:10px;border:1px solid #CCCCCC;padding:10px'>
				<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:22px;font-weight:bold;width:607px'>{ipfrom}:</td>
					<td>".field_ipv4("range1-$t",$dhcp->range1,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px;font-weight:bold'>{ipto}:</td>
					<td>".field_ipv4("range2-$t",$dhcp->range2,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				</table>
				</div>					
		</td>
</tr>					

	<tr>
		<td class=legend style='font-size:22px'>{ddns_domainname}:</td>
		<td>$dom</td>
		<td>&nbsp;</td>
		<td width=1% nowrap>". imgtootltip("plus-16.png",null,"Loadjs('domains.edit.domains.php?js-all-localdomains=yes')")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{max_lease_time}:</td>
		<td style='font-size:16px'>".Field_text("max_lease_time-$t",$dhcp->max_lease_time,'width:90px;font-size:22px;padding:3px')."&nbsp;{seconds}</td>
		<td>&nbsp;</td>
		<td >".help_icon('{max_lease_time_text}')."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>{wpad_label}:</td>
		<td>".Field_text("local_pac_server-$t",$dhcp->local_pac_server,'width:300px;font-size:22px;padding:3px',false)."</td>
		<td>&nbsp;</td>
		<td>".help_icon('{wpad_label_text}')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{subnet}:</td>
		<td>".field_ipv4("subnet-$t",$dhcp->subnet,"font-size:22px;padding:3px;font-weight:bold",false)."</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{netmask}:</td>
		<td>".field_ipv4("netmask-$t",$dhcp->netmask,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{gateway}:</td>
		<td>".field_ipv4("gateway-$t",$dhcp->gateway,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
				
				

	<tr>
		<td class=legend style='font-size:22px'>{DNSServer} 1:</td>
		<td>".field_ipv4("DNS_1-$t",$dhcp->DNS_1,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DNSServer} 2:</td>
		<td>".field_ipv4("DNS_2-$t",$dhcp->DNS_2,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{wins_server}:</td>
		<td>".field_ipv4("WINS-$t",$dhcp->WINS,'font-size:22px;padding:3px')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ntp_server} <span style='font-size:10px'>({optional})</span>:</td>
		<td>".Field_text("ntp_server-$t",$dhcp->ntp_server,'width:228px;font-size:22px;padding:3px')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{broadcast}:</td>
		<td>".field_ipv4("broadcast-$t",$dhcp->broadcast,'font-size:22px;padding:3px')."&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=4 align='right'><hr>". button("{apply}","SaveDHCPSettings$t()",40)."</td>
	</tr>
</table>
</div>
</div>
<br>
<script>
var x_SaveDHCPSettings$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	Loadjs('dhcpd.progress.php');
	}		
		
	function SaveDHCPSettings$t(){
		var XHR = new XHRConnection();
		XHR.appendData('nic','{$_GET["nic"]}');
		XHR.appendData('range1',document.getElementById('range1-$t').value);
		XHR.appendData('range2',document.getElementById('range2-$t').value);
		XHR.appendData('gateway',document.getElementById('gateway-$t').value);
		XHR.appendData('netmask',document.getElementById('netmask').value);
		XHR.appendData('DNS_1',document.getElementById('DNS_1-$t').value);
		XHR.appendData('DNS_2',document.getElementById('DNS_2-$t').value);
		XHR.appendData('max_lease_time',document.getElementById('max_lease_time-$t').value);
		XHR.appendData('ntp_server',document.getElementById('ntp_server-$t').value);
		XHR.appendData('subnet',document.getElementById('subnet-$t').value);
		XHR.appendData('broadcast',document.getElementById('broadcast-$t').value);
		XHR.appendData('WINS',document.getElementById('WINS-$t').value);
		XHR.appendData('local_pac_server',document.getElementById('local_pac_server-$t').value);
		
		if(document.getElementById('EnableDHCPServer-$t').checked){
		XHR.appendData('EnableDHCPServer',1);}else{XHR.appendData('EnableDHCPServer',0);}
		
		if(document.getElementById('EnableDHCPUseHostnameOnFixed-$t').checked){XHR.appendData('EnableDHCPUseHostnameOnFixed',1);}else{XHR.appendData('EnableDHCPUseHostnameOnFixed',0);}
		if(document.getElementById('ping_check-$t').checked){XHR.appendData('ping_check',1);}else{XHR.appendData('ping_check',0);}
		if(document.getElementById('authoritative-$t').checked){XHR.appendData('authoritative',1);}else{XHR.appendData('authoritative',0);}
		XHR.appendData('ddns_domainname',document.getElementById('ddns_domainname-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveDHCPSettings$t);	

	}
</script>
";

$tpl=new templates();
echo  $tpl->_ENGINE_parse_body($html);

}

function Save(){
	
	$dhcp=new dhcpd_sub($_POST["nic"]);
	unset($_POST["nic"]);
	while (list ($a, $b) = each ($_POST) ){
		$dhcp->$a=$b;
		
	}
	
	$dhcp->Save();
	
}

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}
