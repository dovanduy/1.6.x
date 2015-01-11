<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	if(isset($_GET["virtual-list"])){nics_list();exit;}
	if(isset($_GET["add-js"])){add_js();exit;}
	if(isset($_GET["add-form"])){add_form();exit;}
	if(isset($_POST["ipaddr"])){Save();exit;}
	if(isset($_POST["vde-del"])){Del();exit;}
	page();
	
	//vde_tunctl -t virt1
	//vde_switch -s /var/run/switch1 -t virt1 -daemon -p /var/run/switch1.pid
	//vde_pcapplug -s /var/run/switch1 -d -P /var/run/switch1p.pid eth0
	//vde_switch -tap virt1 -sock /var/run/switch1/ctl -daemon -p /var/run/switch1.pid	
	
function page(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$sock=new sockets();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}	
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}			
	
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$netmask=$tpl->_ENGINE_parse_body("{netmask}");
	$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
	$broadcast_has_ipaddr=$tpl->_ENGINE_parse_body("{broadcast_has_ipaddr}");
	$new_virtual_ip=$tpl->javascript_parse_text("{new_virtual_ip}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");	
	$NoGatewayForVirtualNetWork=$tpl->_ENGINE_parse_body("{NoGatewayForVirtualNetWork}");
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	$virtual_interfaces=$tpl->_ENGINE_parse_body("{virtual_interfaces}");
	$gateway=$tpl->javascript_parse_text("{gateway}");
	$delete_ipaddr_ask=$tpl->javascript_parse_text("{delete_ipaddr_ask}");
	$title=$tpl->_ENGINE_parse_body("{Ethernet_switch}");
	$switch_port=$tpl->javascript_parse_text("{switch_port}");
	$users=new usersMenus();
	$vde_switch_explain=$tpl->_ENGINE_parse_body("{vde_switch_explain}");
	$tablewidth=874;
	$servername_size=412;

	$buttons="
	buttons : [
	{name: '<b>$new_virtual_ip$v4</b>', bclass: 'add', onpress : VirtualIPAdd$t},$new_virtual_ipv6
	{name: '<b>$apply_network_configuration</b>', bclass: 'Reconf', onpress : BuildNetConf$t},

	],";
	$html="
<div style='font-size:14px' class=text-info>$vde_switch_explain</div>
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>			
<script>
VirtualIPMem$t='';
function LoadTable$t(){
	$('#table-$t').flexigrid({
		url: '$page?virtual-list=yes&t=$t',
		dataType: 'json',
		colModel : [
			{display: '&nbsp;', name : 'icon', width : 33, sortable : false, align: 'center'},
			{display: '$switch_port', name : 'port', width :35, sortable : true, align: 'center'},
			{display: '$nic', name : 'ID', width :72, sortable : true, align: 'center'},
			{display: '$tcp_address', name : 'ipaddr', width :269, sortable : true, align: 'left'},
			{display: '$gateway', name : 'gateway', width :180, sortable : true, align: 'left'},
			{display: '$netmask', name : 'netmask', width : 180, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 77, sortable : false, align: 'center'},
			
		],
		$buttons
	
		searchitems : [
			{display: '$tcp_address', name : 'ipaddr'},
			{display: 'NIC', name : 'ID'},
			],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: 954,
		height: 320,
		singleSelect: true
		
		});   
	}

		function VirtualIPAdd$t(){
			Loadjs('$page?add-js=yes&t=$t');		
		}

		var X_VirtualIPAddSave$t=function (obj) {
			var results=obj.responseText;
			if(results.length>5){alert(results);return;}	
			$('#rowvirt'+VirtualIPMem$t).remove();
		}		

		function VirtualsDelete$t(ID){
			if(!confirm('$delete_ipaddr_ask')){return;}		
			VirtualIPMem$t=ID;
			var XHR = new XHRConnection();
			XHR.appendData('vde-del',ID);
			XHR.sendAndLoad('$page', 'POST',X_VirtualIPAddSave$t);
		}

	
		
		function BuildNetConf$t(){
			Loadjs('network.vde-restart.php?t=$t');	
		}

LoadTable$t();	
</script>";
	
	echo $html;	
}	

function nics_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$table="nics_vde";
	$t=$_GET["t"];
	$search='%';
	$sock=new sockets();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/vde_status"));
	$sock->getFrameWork("network.php?vde-status=yes");
	
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("no data");
		
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table, "artica_backup");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$mac_text=$tpl->javascript_parse_text("{mac}");
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	
		return;
	}	
	
	$net=new networking();

	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["ID"]);
		$mac=null;
		$TCP_VLAN=null;
		$eth="virt{$ligne["ID"]}";
		$eth_text="virt{$ligne["ID"]}";
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="ok32-grey.png";
		$color="#B7B7B7";
		
		if($interfaces[$eth_text]<>null){
			$img="ok32.png";
			$color="black";
			$switchtext=null;
			$pcaptext=null;
			if(!isset($STATUS[$eth_text]["VDE"])){
				$img="warning32.png";
			}else{
				$switchtext=$tpl->javascript_parse_text("<div style='width:11px'>Switch {running} pid:{$STATUS[$eth_text]["VDE"]} {since} {$STATUS[$eth_text]["VDE_RUN"]}mn</div>");
			}
			if(!isset($STATUS[$eth_text]["PCAP"])){
				$img="warning32.png";
				
			}else{
				$pcaptext=$tpl->javascript_parse_text("<div style='width:11px'>PCAP {running} pid:{$STATUS[$eth_text]["PCAP"]} {since} {$STATUS[$eth_text]["PCAP_RUN"]}mn</div>");
			}			
			
			$mac="<div><i style='font-size:12px'>$mac_text:{$interfaces[$eth]["MAC"]}</div>$switchtext$pcaptext";
		}
		
		if($ligne["ipv6"]==1){
			$img="22-win-nic.png";
			$color="black";			
		}
		
		if($ligne["vlan"]>0){
			$TCP_VLAN="&nbsp;VLAN {$ligne["vlan"]}";
		}
		
		
		$edit="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?add-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')\" 
		style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";
		$delete="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:VirtualsDelete$t({$ligne["ID"]})\" 
		style='font-size:14px;text-decoration:underline'><img src='img/delete-32.png'></a>";
		
		if($ligne["ipv6"]==1){
			$edit="<a href=\"javascript:blur();\" OnClick=\"javascript:VirtualsEdit6$t({$ligne["ID"]})\" style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>";
			$ligne["netmask"]="/{$ligne["netmask"]}";
		}
		
		
	$data['rows'][] = array(
		'id' => "virt{$ligne['ID']}",
		'cell' => array(
		"<img src='img/$img'>"
		,"<span style='font-size:16px;color:$color;font-weight:bold'>{$ligne["port"]}</span>"
		,"<span style='font-size:16px;color:$color;font-weight:bold'>$eth_text</span>",
		"<span style='font-size:16px'>$edit{$ligne["ipaddr"]}$TCP_VLAN</a></span>$mac",
		"<span style='font-size:16px'>$edit{$ligne["gateway"]}</a></span>",
		"<span style='font-size:16px'>$edit{$ligne["netmask"]}</a></span>"
		,$delete )
		);
	}
	
	
echo json_encode($data);		

}
function add_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$new_virtual_ip=$tpl->javascript_parse_text("{new_virtual_ip}");
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_vde WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$new_virtual_ip="{$ligne["nic"]}::{$ligne["ipaddr"]}";
	}	
	
	echo "YahooWin4('750','$page?add-form=yes&t=$t&ID=$ID','$new_virtual_ip')";
	
}

function add_form(){
	$ldap=new clladp();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=0;}
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));

	$title_button="{add}";
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}

	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_vde WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}
	
	
	for($i=1;$i<33;$i++){
		$ports[$i]=$i;
	}
	$vlans[0]="{none}";
	for($i=1;$i<256;$i++){
		$vlans[$i]=$i;
	}	

	if(isset($_GET["default-datas"])){
		$default_array=unserialize(base64_decode($_GET["default-datas"]));
		if(is_array($default_array)){
			$ligne["nic"]=$default_array["NIC"];
			if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
				if($re[2]>254){$re[2]=1;}
				$re[2]=$re[2]+1;
				$ligne["ipaddr"]="{$re[1]}.{$re[2]}";
				$ligne["gateway"]=$default_array["GW"];
				$ligne["netmask"]=$default_array["NETMASK"];
			}
		}
	}

	if($ligne["metric"]==0){$ligne["metric"]=100+$_GET["ID"];}

	$styleOfFields="font-size:16px;padding:3px";
	while (list ($num, $val) = each ($nics) ){
		if(preg_match("#^virt#", $val)){continue;}
		$nics_array[$val]=$val;}
	$nics_array[null]="{select}";

	
	if(!is_numeric($ligne["port"])){$ligne["port"]=1;}
	$nic_field=Field_array_Hash($nics_array,"nic-$t",$ligne["nic"],null,null,0,"font-size:16px;padding:3px");
	$port_field=Field_array_Hash($ports,"port-$t",$ligne["port"],null,null,0,"font-size:16px;padding:3px");
	$vlan_field=Field_array_Hash($vlans,"vlan-$t",$ligne["vlan"],null,null,0,"font-size:16px;padding:3px");
$html="
<div id='animate-$t'></div>
<div id='virtip'>". Field_hidden("ID","{$_GET["ID"]}")."
<div style='width:98%' class=form>
<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px'>{nic}:</td>
		<td>$nic_field</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{vlan_id}:</td>
		<td>$vlan_field</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{switch_port}:</td>
		<td>$port_field</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:16px'>{tcp_address}:</td>
		<td>" . field_ipv4("ipaddr-$t",$ligne["ipaddr"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{netmask}:</td>
		<td>" . field_ipv4("netmask-$t",$ligne["netmask"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>CDIR:</td>
			<td style='padding:-1px;margin:-1px'>
			<table style='width:99%;padding:-1px;margin:-1px'>
			<tr>
			<td width=1%>
			" . Field_text("cdir-$t",$ligne["cdir"],"$styleOfFields;width:190px",null,null,null,false,null,$DISABLED)."</td>
			<td align='left'> ".imgtootltip("img_calc_icon.gif","cdir","CalcCdirVirt$t(1)") ."</td>
			</tr>
			</table></td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{gateway}:</td>
			<td>" . field_ipv4("gateway-$t",$ligne["gateway"],$styleOfFields,false)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{metric}:</td>
			<td>" . field_text("metric-$t",$ligne["metric"],"$styleOfFields;width:90px",false)."</td>
		</tr>
	</table>
	</div>

	<div id='infosVirtual' style='font-size:13px'></div>
	<div style='text-align:right'><hr>". button($title_button,"Save$t()",18)."</div>
</div>
<script>
var Netid={$_GET["ID"]};
var cdir=document.getElementById('cdir-$t').value;
var netmask=document.getElementById('netmask-$t').value;
if(netmask.length>0){if(cdir.length==0){CalcCdirVirt$t(0);}}


var X_CalcCdirVirt$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('cdir-$t').value=results;
}

var xSave$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('animate-$t').innerHTML='';
	if(results.length>3){alert(results);return;}
	$('#table-$t').flexReload();
	YahooWin4Hide();
	
}

function CalcCdirVirt$t(recheck){
	var cdir=document.getElementById('cdir-$t').value;
	if(recheck==0){if(cdir.length>0){return;}}
	var XHR = new XHRConnection();
	XHR.setLockOff();
	XHR.appendData('cdir-ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask',document.getElementById('netmask-$t').value);
	XHR.sendAndLoad('artica.settings.php', 'GET',X_CalcCdirVirt$t);
}	


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask',document.getElementById('netmask-$t').value);
	XHR.appendData('cdir',document.getElementById('cdir-$t').value);
	XHR.appendData('metric',document.getElementById('metric-$t').value);
	XHR.appendData('nic',document.getElementById('nic-$t').value);
	XHR.appendData('gateway',document.getElementById('gateway-$t').value);
	XHR.appendData('port',document.getElementById('port-$t').value);
	XHR.appendData('vlan',document.getElementById('vlan-$t').value);
	XHR.appendData('ID','{$_GET["ID"]}');
	MemFlexGrid=$t;
	AnimateDiv('animate-$t');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function Del(){
	$ID=$_POST["vde-del"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM nics_vde WHERE ID='{$ID}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function Save(){
	$ID=$_POST["ID"];
	
	$ip=new IP();
	if(!$ip->isIPAddress($_POST["gateway"])){$_POST["gateway"]="";}
	if(!$ip->isIPAddress($_POST["ipaddr"])){
		echo "{$_POST["ipaddr"]} -> FALSE\n";
		return;
	}
	
	if(!preg_match("#(.+?)\/(.+)#", $_POST["cdir"])){
		echo "CDIR: {$_POST["cdir"]} -> FALSE\n";
		return;
	}
	
	$q=new mysql();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `nics_vde` (`ID` int(11) NOT NULL AUTO_INCREMENT,  `nic` varchar(20) NOT NULL,`ipaddr` varchar(128) NOT NULL,`netmask` varchar(25) NOT NULL,`cdir` varchar(30) NOT NULL,`gateway` varchar(30) NOT NULL,`metric` INT( 5 ) NOT NULL,PRIMARY KEY (`ID`),KEY `nic` (`nic`),KEY `ipaddr` (`ipaddr`),KEY `metric` (`metric`),KEY `cdir` (`cdir`)) ENGINE=MYISAM;","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	if(!$q->FIELD_EXISTS("nics_vde", "port", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `nics_vde` ADD `port` smallint( 2 ) NOT NULL,ADD INDEX (`port`)","artica_backup");
	}
	if(!$q->FIELD_EXISTS("nics_vde", "vlan", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `nics_vde` ADD `vlan` smallint( 2 ) NOT NULL,ADD INDEX (`vlan`)","artica_backup");
	}
	
	$sql="INSERT INTO nics_vde (`nic`, `ipaddr`,`netmask`,`cdir`,`gateway`,`metric`,`port`,`vlan`) VALUES 
			('{$_POST["nic"]}','{$_POST["ipaddr"]}','{$_POST["netmask"]}','{$_POST["cdir"]}',
			'{$_POST["gateway"]}','{$_POST["metric"]}','{$_POST["port"]}','{$_POST["vlan"]}')";
	
	$sql_edit="UPDATE nics_vde SET `nic`='{$_POST["nic"]}',
		`ipaddr`='{$_POST["ipaddr"]}',
		 `netmask`='{$_POST["netmask"]}',
		 `cdir`='{$_POST["cdir"]}',
		 `gateway`='{$_POST["gateway"]}',
		 `port`='{$_POST["port"]}',
		 `vlan`='{$_POST["vlan"]}',
		 `metric`='{$_POST["metric"]}' WHERE ID='{$_POST["ID"]}'";
	
	if($_POST["ID"]>0){$sql=$sql_edit;}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

	
