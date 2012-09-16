<?php
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');

		$usersmenus=new usersMenus();
		if(!$usersmenus->AsPostfixAdministrator){
			$tpl=new templates();
			echo "alert('".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."');";
			die();
		}
		if(isset($_GET["ip"])){popup_add();exit;}
		if(isset($_GET["popup"])){popup();exit;}
		if(isset($_GET["list"])){echo popup_list();exit;}
		if(isset($_GET["delip"])){popup_delete();exit;}
js();


function js(){
	
	$page=CurrentPageName();
	$prefix=str_replace('.','_',$page);
	$prefix=str_replace('-','',$prefix);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_DHCP_ROUTES_CONF}');
	
	
$html="
	var {$prefix}timeout=0;
	var {$prefix}timerID  = null;
	var {$prefix}tant=0;
	var {$prefix}reste=0;	


	function {$prefix}LoadPage(){
		RTMMail(650,'$page?popup=yes','$title');
	}
	
var x_AddRouteDHCPD= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	LoadAjax('dhcpdroutes','$page?list=yes');
}		
	
	
	function AddRouteDHCPD(){
		var XHR = new XHRConnection();
		XHR.appendData('ip',document.getElementById('dhcpd_ip').value);
		XHR.appendData('netmask',document.getElementById('dhcpd_netmask').value);
		XHR.appendData('gateway',document.getElementById('dhcpd_gateway').value);
		document.getElementById('dhcpdroutes').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_AddRouteDHCPD);	
	}
	
	function DHCPDeleteRoute(ip){
		var XHR = new XHRConnection();
		XHR.appendData('delip',ip);
		AnimateDiv('dhcpdroutes');
		XHR.sendAndLoad('$page', 'GET',x_AddRouteDHCPD);		
	}
	
	{$prefix}LoadPage();";
	
echo $html;
}

function popup_add(){
	$dhcp=new dhcpd(1);
	$dhcp->AddRoute($_GET["ip"],$_GET["netmask"],$_GET["gateway"]);
	
}


function popup_delete(){
	$dhcp=new dhcpd(1);
	$dhcp->DelRoute($_GET["delip"]);
}
function popup_list(){
	$dhcp=new dhcpd(0);
	writelogs(count($dhcp->routes)." routes in array",__FUNCTION__,__FILE__,__LINE__);
	if(!is_array($dhcp->routes)){
		writelogs(count($dhcp->routes)." -> return null",__FUNCTION__,__FILE__,__LINE__);
		return null;}
$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:98%'>
<thead class='thead'>
	<tr>
		
		<th nowrap>{ip_address}/{netmask}</th>
		<th nowrap>{gateway}</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody class='tbody'>";
	$array=$dhcp->routes;
	while (list ($ip, $arr) = each ($array) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-32.png","{delete}","DHCPDeleteRoute('$ip')");
		$info=$arr[2];
		
		$html=$html. "
		<tr class=$classtr>
		<td><strong style='font-size:14px;font-weight:bold' width=75%>$ip/{$arr[0]}</strong></td>
 		<td><strong style='font-size:14px;font-weight:bold' width=35%>{$arr[1]}</td>
		<td width=1% nowrap style='font-size:14px;font-weight:bold'><strong>$info</strong></td>
		<td width=1%>$delete</td>
		
		</tr>
		";
	}
	
	$html=$html."</table>";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);	
	
}


function popup(){
	
	
	$form="<table style='width=99%' class=form>
	<tbody>
	<tr>
		<td class=legend>{ip_address}:</td>
		<td>". field_ipv4("dhcpd_ip",null,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend>{netmask}:</td>
		<td>". field_ipv4("dhcpd_netmask",null,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend>{gateway}:</td>	
		<td>". field_ipv4("dhcpd_gateway",null,"font-size:16px")."</td>
	</tR>
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","AddRouteDHCPD();",16)."</td>
	</tr>
	</tbody>
	</table>
	";
	
	
	$html="
	
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><img src='img/computer-routes-128.png'></td>
		<td valign='top'>
				<div class=explain>{APP_DHCP_ROUTES_EXPLAIN}</div>
				$form
				<br>
				<div style='width:100%;height:120px;overflow:auto' id='dhcpdroutes'>". popup_list()."</div>
		</td>
	</tr>
	</table>
	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}


?>