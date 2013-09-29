<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["search-interfaces"])){interfaces_search();exit;}
if(isset($_GET["ifconfig"])){ifconfig();exit;}
if(isset($_GET["current"])){tab_current();exit;}
if(isset($_GET["initd"])){initd();exit;}
if(isset($_GET["initdajax"])){initdajax();exit;}

if(isset($_POST["initdcontent"])){initdcontent();exit;}
tabs();




function tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{main_interfaces}"]="$page?interfaces=yes";
	if($users->VLAN_INSTALLED){$array["VLAN"]='miniadm.network.vlans.php';}
	$array["{virtual_ips}"]='miniadm.network.virtips.php';
	$array["{network_bridges}"]="miniadm.network.bridges.php";
	$array["{status}"]="$page?current=yes";
	$array["{failover}"]="miniadm.ucarp.php";
	echo $boot->build_tab($array);	
}

function interfaces(){
	
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-interfaces");
	
	
}

function tab_current(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$array["{display_current_config}"]="$page?ifconfig=yes";
	$array["{init_script}"]="$page?initd=yes";
	echo $boot->build_tab($array);
}

function initdajax(){
	$tpl=new templates();
	$sock=new sockets();
	$datas=@implode("\n",unserialize(base64_decode($sock->getFrameWork("system.php?ifconfig-initd=yes"))));
	echo $datas;
}

function initd(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$explain=$tpl->_ENGINE_parse_body("{init_script_net_explain}");
	$page=CurrentPageName();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?ifconfig-initd=yes")));
	
	while (list ($key, $value) = each ($datas) ){
		if(preg_match("#^echo#", $value)){continue;}
		$value=str_replace(">>/var/log/net-start.log 2>&1 || true","",$value);
		$tt[]=str_replace(">>/var/log/net-start.log 2>&1","",$value);
		
	}
	$datasTXT=@implode("\n", $tt);
	$datas2=@implode("\n",unserialize(base64_decode($sock->getFrameWork("system.php?ifconfig-initdcontent=yes"))));
	if(trim($datas2)==null){$datas2="#!/bin/sh -e\n#Content here\nexit 0\n";}
	$html= "
	<div class=explain>$explain</div>		
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:12px !important'
	id='NetFileGeneratedConfig'>$datasTXT</textarea>

	<H3>Personal commands</H3>
	<div id='$t'></div>
	<center>". button("{apply}","Save$t()")."</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:12px !important'
	id='textarea2$t'>$datas2</textarea>		
	<center>". button("{apply}","Save$t()")."</center>	
	<script>
	var xSave$t = function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
		}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('initdcontent', encodeURIComponent(document.getElementById('textarea2$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function initdcontent(){
	$_POST["initdcontent"]=url_decode_special_tool($_POST["initdcontent"]);
	$content=urlencode(base64_encode($_POST["initdcontent"]));
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("system.php?network-initdcontent=$content"));
	echo $datas;
}


function interfaces_search(){
	$sock=new sockets();
	$tpl=new templates();
	$tcp=new networking();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$snortInterfaces=array();
	$LXCEthLocked=$sock->GET_INFO("LXCEthLocked");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$ASDEBIAN=0;
	if($users->AS_DEBIAN_FAMILY){$ASDEBIAN=1;}
	if(!is_numeric($LXCEthLocked)){$LXCEthLocked=0;}
	$users=new usersMenus();
	$GLOBALS["AsSystemAdministrator"]=$users->AsSystemAdministrator;
	
	$LXCInterface=$sock->GET_INFO("LXCInterface");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
	if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
	
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort==1){
			$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
		}
	}	
	
	$searchstring=string_to_flexregex("search-interfaces");
	
	while (list ($num, $val) = each ($datas) ){
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		if(preg_match('#master#',$val)){continue;}
		if(preg_match("#^veth.+?#",$val)){continue;}
		if(preg_match("#^tunl[0-9]+#",$val)){continue;}
		if(preg_match("#^dummy[0-9]+#",$val)){continue;}
		if(preg_match("#^gre[0-9]+#",$val)){continue;}
		if(preg_match("#^ip6tnl[0-9]+#",$val)){continue;}
		if(preg_match("#^sit[0-9]+#",$val)){continue;}
		if(preg_match("#^vlan[0-9]+#",$val)){continue;}
		if($searchstring<>null){if(!preg_match("#$searchstring#",$val)){continue;}}
	
		$nic=new system_nic();
		if(!$nic->unconfigured){
			if($LXCEthLocked==1){if($val==$LXCInterface){
				writelogs("LXCEthLocked:$LXCEthLocked; $val==$LXCInterface -> abort",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			}
		}
	
		if(trim($val)==null){continue;}
		$tr[]=NicBuildTR($val);
		
	
	}
	
	if($searchstring==null){
		$tr[]=NicBuildTR("tun0");
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				
		<tr>
		<th colspan=2 width=1% nowrap>{interface}</th>
		<th>{tcp_address}</th>
		<th>{mac_addr}</th>
		<th>{gateway}</th>
		<th>{netmask}</th>
		</tr>
				
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
				";
	
	
}

function NicBuildTR($NicRequested){
	
	$val=$NicRequested;
	
	$val=trim($val);
	if(preg_match('#master#',$val)){return;}
	if(preg_match("#^veth.+?#",$val)){return;}
	if(preg_match("#^tunl[0-9]+#",$val)){return;}
	if(preg_match("#^dummy[0-9]+#",$val)){return;}
	if(preg_match("#^gre[0-9]+#",$val)){return;}
	if(preg_match("#^ip6tnl[0-9]+#",$val)){return;}
	if(preg_match("#^sit[0-9]+#",$val)){return;}
	if(preg_match("#^vlan[0-9]+#",$val)){return;}
	if(preg_match("#^lxc[0-9]+#",$val)){return;}
	
	
	
	
	$sock=new sockets();
	$snortInterfaces=array();
	$LXCEthLocked=$sock->GET_INFO("LXCEthLocked");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	if(!is_numeric($LXCEthLocked)){$LXCEthLocked=0;}
	$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));
	$LXCInterface=$sock->GET_INFO("LXCInterface");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort==1){
			$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
		}
	}	
	
	$tcp=new networking();
	
	if(!isset($GLOBALS["ipsZ"])){
		$GLOBALS["ipsZ"]=$tcp->ALL_IPS_GET_ARRAY();
	}
	
	
	$nic=new system_nic($NicRequested);
		if(!$nic->unconfigured){		
			if($LXCEthLocked==1){if($val==$LXCInterface){
				writelogs("LXCEthLocked:$LXCEthLocked; $val==$LXCInterface -> abort",__FUNCTION__,__FILE__,__LINE__);
				return;
				}
			}
		}
		
		$NIC_UP=false;
		
		if(trim($val)==null){continue;}
		$tcp->ifconfig(trim($val));
		$array_ipcfg=listnicinfos(trim($val));
		$ipddr=$array_ipcfg["tcp_address"];
		
		
		$defaultroute_text=null;
		
		if($nic->defaultroute==1){
			$defaultroute_text="<div><i style='color:#C40000;font-size:11px'>{default_route}</i></div>";
		}
		

/*		return array(
				"textColor"=>$textColor,
				"tcp_address"=>$IPADDR,
				"IPV6"=>$IPV6,
				"NETMASK"=>$tbl[2],
				"gateway"=>$tbl[4],
				"mac_addr"=>$tbl[1],
				"HAMACHI"=>$HAMACHI
		);		
		*/
		$js="javascript:Loadjs('system.nic.edit.php?nic=$val')";
		if(!$tcp->linkup){
			$img_on="64-win-nic-off.png";
			
		}else{
			$img_on="64-win-nic.png";
			$NIC_UP=true;
			if($snortInterfaces[trim($val)]==1){$img_on="64-win-nic-snort.png";}
		}
		
		$icon1=imgtootltip("service-restart-16.png","{rebuild}","RebuildMyNic$val()");
		$icon2=imgtootltip("plus-16.png","{add_virtual_ip_addr_explain_js}","Loadjs('$page?js-add-nic=$val')");
		
		if($array_ipcfg["HAMACHI"]){
			$img_on="64-win-nic-hamachi.png";
			$js="javascript:Loadjs('hamachi.php')";
			$icon1=null;
			$icon2=null;
		}
		
		if($IPBANS[$ipddr]){
			$img_on="64-win-nic-off.png";
			$icon1=null;
			$icon2=null;
			$js=null;			
		}
		
		$boot=new boostrap_form();
		$link=$boot->trswitch("Loadjs('miniadm.network.interfaces.NIC.php?nic=$NicRequested')");
		if(!$GLOBALS["AsSystemAdministrator"]){$link=null;}
		$nic=new system_nic($NicRequested);
		$oldnic=null;
		$oldMac=null;
		$failover_text=null;
		$error=null;
		if($nic->ucarp_enabled==0){$failover_text="{disabled}";}
		if($nic->ucarp_enabled==1){
			$oldnic=$ipddr;
			$ipddr=$nic->ucarp_vip;
			if(!isset($GLOBALS["ipsZ"][$nic->ucarp_vip])){
				$error="<img src='img/ok24-grey.png' style='marign-right:10px;float-left'>";
			}
			
			if($nic->ucarp_master==1){$failover_text="{master2}";}else{$failover_text="{slave}";}
			$failover_text=$error.$failover_text.":&nbsp;$oldnic";
			$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$NicRequested:ucarp");
			$tbl=explode(";",$nicinfos);
			$oldMac="<div style='color:{$array_ipcfg["textColor"]};font-size:12px'><i>{$array_ipcfg["mac_addr"]}</i></div>";
			$array_ipcfg["mac_addr"]=$tbl[1];
			
		}
		if(!$users->UCARP_INSTALLED){$failover_text="{not_installed}";}
		
		if($array_ipcfg["gateway"]==null){
			$nicz=new system_nic($NicRequested);
			$array_ipcfg["gateway"]=$nicz->GATEWAY;
		}
		
		return $tpl->_ENGINE_parse_body("
		<tr style='font-size:18px' $link>
			<td><img src='img/$img_on'></td>
			<td style='color:{$array_ipcfg["textColor"]}'>$NicRequested</td>
			<td style='color:{$array_ipcfg["textColor"]}'><i class='icon-signal'></i>&nbsp;$ipddr<div style='color:{$array_ipcfg["textColor"]};font-size:12px'><i>{failover}:$failover_text</i><br>{$array_ipcfg["IPV6"]}</td>
			<td style='color:{$array_ipcfg["textColor"]}'><i class='icon-signal'></i>&nbsp;{$array_ipcfg["mac_addr"]}$oldMac</td>
			<td style='color:{$array_ipcfg["textColor"]}'>{$array_ipcfg["gateway"]}$defaultroute_text</td>
			<td style='color:{$array_ipcfg["textColor"]}'>{$array_ipcfg["NETMASK"]}</td>
		</tr>");
		
		
}

function listnicinfos($nicname,$js=null){
	$sock=new sockets();
	$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$nicname");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$HAMACHI=FALSE;

	$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));
	$tbl=explode(";",$nicinfos);
	$tpl=new templates();
	if($EnableipV6==1){
		$ip6s=unserialize(base64_decode($sock->getFrameWork("network.php?ifconfig6=$nicname")));
		while (list ($num, $ligne) = each ($ip6s) ){
			$ip6z[]="<i style='font-size:11px'>$num</i>";
		}
	}
	
	if(count($ip6z)){$IPV6=@implode(", ", $ip6z);}
	if(trim($tbl[5])=="yes"){$wire=" (wireless)";}

	if(preg_match("#^5\.[0-9]+\.#", $tbl[0])){
		if($tbl[2]=="255.0.0.0"){
			$HAMACHI=true;
			$js="javascript:Loadjs('hamachi.php')";
		}
	}

	if($IPBANS[$tbl[0]]){$hidde_interface=true;}
	$IPADDR=$tbl[0];
			
	if($js<>null){$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold;font-size:13px;text-decoration:underline'>";}
	$textColor="black";
	if($hidde_interface){$href=null;$textColor="#ACAAAA";}

			
			return array(
					"textColor"=>$textColor,
					"tcp_address"=>$IPADDR,
					"IPV6"=>$IPV6,
					"NETMASK"=>$tbl[2],
					"gateway"=>$tbl[4],
					"mac_addr"=>$tbl[1],
					"HAMACHI"=>$HAMACHI
					);


}


function ifconfig(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?ifconfig-show=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:16px !important'
	id='textarea$t'>".@implode("\n", $datas)."</textarea>";
}