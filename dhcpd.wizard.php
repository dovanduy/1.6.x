<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.system.nics.inc');
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["start-0"])){start_0();exit;}

if(isset($_GET["start-1"])){start_1();exit;}
if(isset($_POST["NIC"])){save();exit;}

if(isset($_GET["start-2"])){start_2();exit;}
if(isset($_POST["SUBNET"])){save();exit;}

if(isset($_GET["start-3"])){start_3();exit;}
if(isset($_POST["RANGE1"])){save();exit;}

if(isset($_GET["start-4"])){start_4();exit;}
if(isset($_POST["GATEWAY"])){save();exit;}

js();
	

function js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{EnableDHCPServer}");
	echo "YahooWin5('990','$page?start-0=yes','$title');";
	
	
	
	
}
	
function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}

function start_0(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?start-1=yes&t=$t');
	</script>
	
	";
	echo $html;
}

function start_1(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$DHCPWizard=unserialize($sock->GET_INFO("DHCPWizard"));
	
	$dhcp=new dhcpd(0,1);
	$nic=$dhcp->array_tcp;
	if($dhcp->listen_nic==null){$dhcp->listen_nic="eth0";}
	
	while (list ($num, $val) = each ($nic) ){
		if($num==null){continue;}
		if($num=="lo"){continue;}
		$nicz=new system_nic($num);
		$nics[$num]="[$num]: $nicz->IPADDR $nicz->NICNAME ($nicz->netzone)";
	}
	
	
$html="<div style='font-size:26px;margin-bottom:20px'>{welcome_to_dhcp_wizard}</div>
<div style='font-size:18px;margin-bottom:20px'>{welcome_to_dhcp_wizard_1}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{interface}:</td>
			<td>".Field_array_Hash($nics, "NIC-$t",$DHCPWizard["NIC"],"style:font-size:26px")."</td>
		</tr>
		</table>
		<div style='text-align:right;width:100%'><HR>". button("{next}","Start1$t()",30)."</div>	
</div>

<script>
var xStart1$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	LoadAjax('$t','$page?start-2=yes&t=$t');
}	

function Start1$t(){
	var XHR = new XHRConnection();
	XHR.appendData('NIC',document.getElementById('NIC-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStart1$t);	
}
</script>
	";
echo $tpl->_ENGINE_parse_body($html);
	
}
function start_2(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$DHCPWizard=unserialize($sock->GET_INFO("DHCPWizard"));
	$nicz=new system_nic($DHCPWizard["NIC"]);
	$dhcp=new dhcpd(0,1);
	if(!isset($DHCPWizard["SUBNET"])){$DHCPWizard["SUBNET"]=$dhcp->subnet;}
	if(!isset($DHCPWizard["NETMASK"])){$DHCPWizard["NETMASK"]=$dhcp->netmask;}
	
	if($DHCPWizard["SUBNET"]==null){
		
		$ipaddr=explode(".",$nicz->IPADDR);
		$DHCPWizard["SUBNET"]="{$ipaddr[0]}.{$ipaddr[1]}.{$ipaddr[2]}.0";
	}
	
	if($DHCPWizard["NETMASK"]==null){
		$DHCPWizard["NETMASK"]=$nicz->NETMASK;
	}

	$dhcp_wizard_2=$tpl->_ENGINE_parse_body("{dhcp_wizard_2}");
	$dhcp_wizard_2=str_replace("%i", $DHCPWizard["NIC"], $dhcp_wizard_2);

	$html="<div style='font-size:40px;margin-bottom:20px'>{network_parameters} {$DHCPWizard["NIC"]}</div>
<div style='font-size:18px;margin-bottom:20px'>$dhcp_wizard_2<br>{welcome_to_dhcp_wizard_2}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{subnet}:</td>
			<td>".field_ipv4("SUBNET-$t",$DHCPWizard["SUBNET"],"font-size:22px;padding:3px;font-weight:bold",false)."</td>
		</tr>			
			<tr>
				<td class=legend style='font-size:22px'>{netmask}:</td>
				<td>".field_ipv4("NETMASK-$t",$DHCPWizard["NETMASK"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
			</tr>
		</table>
		<div style='text-align:right;width:100%'><HR>". button("{next}","Start2$t()",30)."</div>
		</div>

		<script>
		var xStart2$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		LoadAjax('$t','$page?start-3=yes&t=$t');
}

function Start2$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SUBNET',document.getElementById('SUBNET-$t').value);
	XHR.appendData('NETMASK',document.getElementById('NETMASK-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStart2$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function start_3(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$DHCPWizard=unserialize($sock->GET_INFO("DHCPWizard"));
	
	$dhcp=new dhcpd(0,1);
	if(!isset($DHCPWizard["SUBNET"])){$DHCPWizard["SUBNET"]=$dhcp->subnet;}
	if(!isset($DHCPWizard["NETMASK"])){$DHCPWizard["NETMASK"]=$dhcp->netmask;}
	

	$dhcp_wizard_3=$tpl->_ENGINE_parse_body("{dhcp_wizard_3}");
	$dhcp_wizard_3=str_replace("%i", $DHCPWizard["NIC"], $dhcp_wizard_3);
	$dhcp_wizard_3=str_replace("%n", "{$DHCPWizard["SUBNET"]}/{$DHCPWizard["NETMASK"]}", $dhcp_wizard_3);
	
	if($DHCPWizard["RANGE1"]==null){
		$tr=explode(".",$DHCPWizard["SUBNET"]);
		$DHCPWizard["RANGE1"]="{$tr[0]}.{$tr[1]}.{$tr[2]}.35";
		$DHCPWizard["RANGE2"]="{$tr[0]}.{$tr[1]}.{$tr[2]}.254";
	}
	
	

	$html="<div style='font-size:26px;margin-bottom:40px'>{network_parameters}</div>
<div style='font-size:18px;margin-bottom:20px'>$dhcp_wizard_3<br>{welcome_to_dhcp_wizard_3}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px;font-weight:bold;width:622px'>{ipfrom}:</td>
			<td>".field_ipv4("range1-$t",$DHCPWizard["RANGE1"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px;font-weight:bold'>{ipto}:</td>
			<td>".field_ipv4("range2-$t",$DHCPWizard["RANGE2"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>
		</table>
		<div style='text-align:right;width:100%'><HR>". button("{next}","Start3$t()",30)."</div>
		</div>

<script>
var xStart3$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	LoadAjax('$t','$page?start-4=yes&t=$t');
}

function Start3$t(){
	var XHR = new XHRConnection();
	XHR.appendData('RANGE1',document.getElementById('range1-$t').value);
	XHR.appendData('RANGE2',document.getElementById('range2-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStart3$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);

}
function start_4(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$DHCPWizard=unserialize($sock->GET_INFO("DHCPWizard"));
	$dhcp=new dhcpd(0,1);
	$nicz=new system_nic($DHCPWizard["NIC"]);

	$dhcp_wizard_4=$tpl->_ENGINE_parse_body("{dhcp_wizard_4}");
	$dhcp_wizard_4=str_replace("%i", $DHCPWizard["NIC"], $dhcp_wizard_4);
	$dhcp_wizard_4=str_replace("%n", "{$DHCPWizard["SUBNET"]}/{$DHCPWizard["NETMASK"]}", $dhcp_wizard_4);
	$dhcp_wizard_4=str_replace("%t", "{$DHCPWizard["RANGE1"]} - {$DHCPWizard["RANGE2"]}", $dhcp_wizard_4);

	
	if($DHCPWizard["GATEWAY"]==null){$DHCPWizard["GATEWAY"]=$nicz->IPADDR;}
	if($DHCPWizard["DNS1"]==null){$DHCPWizard["DNS1"]=$nicz->IPADDR;}
	if($DHCPWizard["DNS2"]==null){$DHCPWizard["DNS2"]="8.8.8.8";}
	
	$html="<div style='font-size:26px;margin-bottom:40px'>{network_parameters}</div>
	<div style='font-size:18px;margin-bottom:20px'>$dhcp_wizard_4<br>{welcome_to_dhcp_wizard_4}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{gateway}:</td>
			<td>".field_ipv4("gateway-$t",$DHCPWizard["GATEWAY"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{ddns_domainname}:</td>
			<td>".field_text("DOMAINNAME-$t",$DHCPWizard["DOMAINNAME"],'font-size:22px;width:250px;padding:3px;font-weight:bold')."</td>
					
				</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{DNSServer} 1:</td>
			<td>".field_ipv4("DNS1-$t",$DHCPWizard["DNS1"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{DNSServer} 2:</td>
			<td>".field_ipv4("DNS2-$t",$DHCPWizard["DNS2"],'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>					
					
					
		</table>
<div style='text-align:right;width:100%'><HR>". button("{build_parameters}","Start4$t()",30)."</div>
</div>

<script>
var xStart4$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	Loadjs('dhcpd.wizard.progress.php');
}

function Start4$t(){
	var XHR = new XHRConnection();
	XHR.appendData('GATEWAY',document.getElementById('gateway-$t').value);
	XHR.appendData('DNS1',document.getElementById('DNS1-$t').value);
	XHR.appendData('DNS2',document.getElementById('DNS2-$t').value);
	XHR.appendData('DOMAINNAME',document.getElementById('DOMAINNAME-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStart4$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);

}
function save(){
	$sock=new sockets();
	$DHCPWizard=unserialize($sock->GET_INFO("DHCPWizard"));
	while (list ($num, $val) = each ($_POST) ){
		$DHCPWizard[$num]=$val;
	}
	$sock->SaveConfigFile(serialize($DHCPWizard), "DHCPWizard");
}
