<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	//http://www.mysidenotes.com/2007/08/17/vlan-configuration-on-ubuntu-debian/
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit;}
	
	if(isset($_GET["vlans-list"])){vlan_list();exit;}
	if(isset($_GET["search"])){vlan_list_list();exit;}
	if(isset($_GET["vlan-popup-add"])){vlan_add_form();exit;}
	if(isset($_GET["cdir-ipaddr"])){vlan_cdir();exit;}
	if(isset($_GET["vlan-ipaddr"])){vlan_add();exit;}
	if(isset($_GET["vlan-del"])){vlan_del();exit;}
	if(isset($_GET["NetWorkBroadCastVLANAsIpAddr"])){NetWorkBroadCastVLANAsIpAddrSave();exit;}
	if(isset($_POST["BuildVLANs"])){vlan_construct();exit;}
	
	vlans_start();
	
	
	
function vlans_start(){
	$page=CurrentPageName();
	$tpl=new templates();
	$virtual_interfaces=$tpl->_ENGINE_parse_body('{virtual_interfaces}');
	$html="
	
	
	
	<div id='vlans-list'></div>	
	<div style='width:100%;text-align:right'>". imgtootltip("20-refresh.png","{refresh}","VLANRefresh()")."</div>
	<script>
	". vlans_js_datas()."
	</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	}
	
	function lastmetric(){
		$q=new mysql();
		$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hash[$ligne["metric"]]=$ligne["metric"];
	
		$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hash[$ligne["metric"]]=$ligne["metric"];
	
		$sql="SELECT metric as tcount FROM `nic_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hash[$ligne["metric"]]=$ligne["metric"];
	
		krsort($hash[$ligne["metric"]]);
		while (list ($a, $b) = each ($hash) ){
			$f[]=$b;
		}
	
		return $f[0]+1;
	
	}	

function vlan_add(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	if($_GET["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	
	if(!is_numeric($_GET["vlanid"])){echo "Vlan ID must be a numeric value...\n";return;}
	
	
	if($_GET["netmask"]=='___.___.___.___'){$_GET["netmask"]="0.0.0.0";}
	if($_GET["gateway"]=='___.___.___.___'){$_GET["gateway"]="0.0.0.0";}
	if($_GET["vlan-ipaddr"]=='___.___.___.___'){$_GET["vlan-ipaddr"]="0.0.0.0";}
	
	
	$sql="INSERT INTO nics_vlan (nic,org,ipaddr,netmask,cdir,gateway,vlanid,metric)
		VALUES('{$_GET["nic"]}','{$_GET["org"]}','{$_GET["vlan-ipaddr"]}',
		'{$_GET["netmask"]}','{$_GET["cdir"]}','{$_GET["gateway"]}',{$_GET["vlanid"]},{$_GET["metric"]});
		";
	
	if($_GET["ID"]>0){
		$sql="UPDATE nics_vlan SET 
		`nic`='{$_GET["nic"]}',
		`org`='{$_GET["org"]}',
		`ipaddr`='{$_GET["vlan-ipaddr"]}',
		`netmask`='{$_GET["netmask"]}',
		`cdir`='{$_GET["cdir"]}',
		`vlanid`='{$_GET["vlanid"]}',
		`metric`='{$_GET["metric"]}',
		`gateway`='{$_GET["gateway"]}' 
		WHERE `ID`='{$_GET["ID"]}'";
	}
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	
		if(!$q->ok){
			if(preg_match("#Unknown column#", $q->mysql_error)){
				$q->BuildTables();
				$q->QUERY_SQL($sql,"artica_backup");
			}
		}	
	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("system.php?vlans-build=yes");	
	
}	
function vlan_cdir(){
	$ipaddr=$_GET["cdir-ipaddr"];
	$newmask=$_GET["netmask"];
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$ip=new IP();
	if($newmask<>null){echo $ip->maskTocdir($ipaddr, $newmask);}
	}

function vlan_add_form(){
	$ldap=new clladp();
	$sock=new sockets();
	$page=CurrentPageName();
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$title_button="{add}";
	$t=$_GET["t"];
	
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_vlan WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
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
	
	$styleOfFields="font-size:16px;padding:3px";
	$ous=$ldap->hash_get_ou(true);
	$ous["openvpn_service"]="{APP_OPENVPN}";
	while (list ($num, $val) = each ($nics) ){
		$nics_array[$val]=$val;
	}
	$nics_array[null]="{select}";
	$ous[null]="{select}";
	
	$nic_field=Field_array_Hash($nics_array,"nic",$ligne["nic"],null,null,0,"font-size:16px;padding:3px");
	$ou_fields=Field_array_Hash($ous,"org",$ligne["org"],null,null,0,"font-size:16px;padding:3px");
	
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
	if($ligne["metric"]==0){$ligne["metric"]=lastmetric();}
	
	$html="
	<div id='virtip-vlan-$t'></div>
	". Field_hidden("ID","{$_GET["ID"]}")."
	<table style='width:99%' class=form> 
	<tr>
		<td class=legend style='font-size:16px'>{nic}</td>
		<td>$nic_field</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{organization}</td>
		<td>$ou_fields</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>Vlan ID</td>
		<td>". Field_text("vlanid-$t",$ligne["vlanid"],"font-size:16px;width:60px")."</td>
	</tr>	
	<tr>
			<td class=legend style='font-size:16px'>{tcp_address}:</td>
			<td>" . field_ipv4("ipaddr",$ligne["ipaddr"],"font-size:16px",null,"CalcCdirVirt(0)",null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{netmask}:</td>
			<td>" . field_ipv4("netmask",$ligne["netmask"],"font-size:16px",null,"CalcCdirVirt(0)",null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>CDIR:</td>
			<td style='padding:-1px;margin:-1px'>
			<table style='width:99%;padding:-1px;margin:-1px'>
			<tr>
			<td width=1%>
			" . Field_text("cdir",$ligne["cdir"],"font-size:16px;width:210px",null,null,null,false,null,$DISABLED)."</td>
			<td align='left'> ".imgtootltip("img_calc_icon.gif","cdir","CalcCdirVirt(1)") ."</td>
			</tr>
			</table></td>
		</tr>			
		<tr>
			<td class=legend style='font-size:16px'>{gateway}:</td>
			<td>" . field_ipv4("gateway",$ligne["gateway"],"font-size:16px",null,null,null,false,null,$DISABLED)."</td>
		</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{metric}</td>
		<td>". Field_text("metric-$t",$ligne["metric"],"font-size:16px;width:60px")."</td>
	</tr>					
	</table>

	<div style='text-align:right'><hr>". button($title_button,"VLANSave$t()","18px")."</div>
	<script>
	
		function VLANSave$t(){	
			var XHR = new XHRConnection();
			XHR.appendData('vlan-ipaddr',document.getElementById('ipaddr').value);
			XHR.appendData('netmask',document.getElementById('netmask').value);
			XHR.appendData('cdir',document.getElementById('cdir').value);
			XHR.appendData('gateway',document.getElementById('gateway').value);
			XHR.appendData('nic',document.getElementById('nic').value);
			XHR.appendData('org',document.getElementById('org').value);
			XHR.appendData('ID',document.getElementById('ID').value);
			XHR.appendData('vlanid',document.getElementById('vlanid-$t').value);
			XHR.appendData('metric',document.getElementById('metric-$t').value);
			AnimateDiv('virtip-vlan-$t');
			XHR.sendAndLoad('$page', 'GET',XVLANSave$t);
		}	

		
		
		function CheckCDR$t(){
			var cdir=document.getElementById('cdir').value;
			var netmask=document.getElementById('netmask').value;
			if(netmask.length>0){if(cdir.length==0){CalcCdirVirt(0);}}
		}
			
		var XVLANSave$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('virtip-vlan-$t').innerHTML='';
			if(results.length>0){alert(results);}
			YahooWin2Hide();
			if(document.getElementById('main_openvpn_config')){RefreshTab('main_openvpn_config');}
			VLANRefresh();
			
		}
		
		

	CheckCDR$t();
	</script>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function vlans_js_datas(){
	$page=CurrentPageName();
	$tpl=new templates();
	$virtual_interfaces=$tpl->_ENGINE_parse_body('{virtual_interfaces}');
	$tpl=new templates();
	$default_load="VLANRefresh();";
	if(isset($_GET["js-add-nic"])){
		$default_load="VirtualIPJSAdd('{$_GET["js-add-nic"]}');";
	}
	
	$t=time();
	$sock=new sockets();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	
	
	$html="
		var windows_size=500;
	

		
		function VLANIPJSAdd(nic){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			var defaultDatas='';
			if(document.getElementById('infos_'+nic)){
				defaultDatas=document.getElementById('infos_'+nic).value;
			}
			YahooWin2(windows_size,'$page?virtual-popup-add=yes&default-datas='+defaultDatas,'$virtual_interfaces');
		}
		

		
		var X_CalcCdirVirt= function (obj) {
			var results=obj.responseText;
			document.getElementById('cdir').value=results;
		}		
		
		function CalcCdirVirt(recheck){
			var cdir=document.getElementById('cdir').value;
			if(recheck==0){
				if(cdir.length>0){return;}
			}
			var XHR = new XHRConnection();
			XHR.setLockOff();
			XHR.appendData('cdir-ipaddr',document.getElementById('ipaddr').value);
			XHR.appendData('netmask',document.getElementById('netmask').value);
			XHR.sendAndLoad('$page', 'GET',X_CalcCdirVirt);
		}
		

		function VLANRefresh(){
			
			if(!document.getElementById('flexRT$t')){
				LoadAjax('vlans-list','$page?vlans-list=yes&default-datas={$_GET["default-datas"]}&t=$t');
			}else{
				$('#flexRT$t').flexReload();
			}
		}
		
		function BuildVLANs(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
			if(document.getElementById('vlans-list')){
				LoadAjax('vlans-list','$page?vlans-list=yes&build=yes');
			}
		}
		

		
		$default_load	
	";
		
	return $html;
}

function vlan_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
	$netmask=$tpl->javascript_parse_text("{netmask}");
	$t=$_GET["t"];
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{add}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$reconstruct_vlans=$tpl->javascript_parse_text("{reconstruct_vlans}");
	$bts[]="{name: '$add', bclass: 'add', onpress : VlanAdd$t},";
	$bts[]="{name: '$reconstruct_vlans', bclass: 'Reload', onpress : BuildVLANs$t},";
	
	
	
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		

	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$html="
	<div style='margin-left:5px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var mm$t=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'status', width : 31, sortable : false, align: 'center'},
		{display: 'Vlan id', name : 'vlanid', width : 64, sortable : true, align: 'center'},
		{display: '$organization', name : 'org', width : 130, sortable : true, align: 'left'},
		{display: '$nic', name : 'nic', width : 136, sortable : false, align: 'left'},
		{display: '$tcp_address', name : 'ipaddr', width : 174, sortable : false, align: 'left'},
		{display: '$netmask', name : 'netmask', width : 154, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none2', width : 31, sortable : false, align: 'center'},
	],$buttons
	searchitems : [
		{display: '$organization', name : 'org'},
		{display: '$nic', name : 'nic'},
		{display: '$tcp_address', name : 'ipaddr'},
		{display: '$netmask', name : 'netmask'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: $tablesize,
	height: 400,
	singleSelect: true
	
	});   
});

	function VlanAdd$t(){
		YahooWin2('500','$page?vlan-popup-add=yes&default-datas={$_GET["default-datas"]}&t=$t','VLAN::');
	}
	
	function VLANEdit(ID){
		YahooWin2(500,'$page?vlan-popup-add=yes&t=$t&ID='+ID,'VLAN::'+ID);
	}

	var x_BuildVLANs$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#flexRT$t').flexReload();		
	}	
	
	function BuildVLANs$t(){
		Loadjs('network.restart.php?t=$t');
	
	}



function EmptyTask$t(){
	if(confirm('$empty::{$_GET["taskid"]}')){
		
    }
}
	var X_VLANDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+mm$t).remove();		
	}
	
	
		function VLANDelete(id){
			if(confirm('$delete '+id+'?')){
				var DisableNetworksManagement=$DisableNetworksManagement;
				if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
				mm$t=id;
				var XHR = new XHRConnection();
				XHR.appendData('vlan-del',id);
				XHR.sendAndLoad('$page', 'GET',X_VLANDelete$t);
			}
		}

</script>

";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
	
}

function vlan_list_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();	
	$table="nics_vlan";
	$database="artica_backup";
	$search='%';
	$page=1;
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();		
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$search=string_to_sql_search($_POST["query"]);
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
		$total = $ligne["tcount"];
		
	}else{
		
		$total = $q->COUNT_ROWS($table, $database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No VLAN interface set...",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$ip=new IP();
		$cdir=$ligne["cdir"];
		$eth="{$ligne["nic"]}.{$ligne["ID"]}/{$ligne["nic"]}";
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="22-win-nic-off.png";
		
		if($interfaces["{$ligne["nic"]}.{$ligne["ID"]}"]<>null){
			$img="22-win-nic.png";
		}
		
		if(trim($ligne["org"])==null){
			$ligne["org"]=$tpl->_ENGINE_parse_body("<strong style='color:red'>{no_organization}</strong>");
		}
		
		$edit=imgsimple("24-administrative-tools.png","{edit}","VLANEdit({$ligne["ID"]})");
		$delete=imgsimple("delete-24.png","{delete}","VLANDelete({$ligne["ID"]})");
		
		if($DisableNetworksManagement==1){
			$edit="&nbsp;";
			$delete="&nbsp;";
		}
		
		$a=$ip->parseCIDR($cdir);
		if($a[0]==0){
			$img="warning-panneau-24.png";
			$cdir="<span style='color:red'>$cdir</span>";
		}
		
		
		

	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<img src='img/$img'>",
		"<div style='font-size:14px;font-weight:normal'>{$ligne["vlanid"]}</div>",
		"<div style='font-size:14px;font-weight:normal'>{$ligne["org"]}</div>",
		"<div style='font-size:14px;font-weight:normal'>$eth</div>",
		"<div style='font-size:14px;font-weight:normal'>{$ligne["ipaddr"]}</div>",
		"<div style='font-size:14px;font-weight:normal'>{$ligne["netmask"]}<div style='font-size:11px'>$cdir</div></div>"
		,$edit
		,$delete
		)
		);
	}
	
	
echo json_encode($data);	
}


function vlan_construct(){
	$tpl=new templates();
	$sock=new sockets();
	ConstructVLANIP();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
	
}


function vlan_list_old(){
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$q=new mysql();
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	

	
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$sql="SELECT * FROM nics_vlan ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$style=CellRollOver();
	$html=$html."
	<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","VlanAdd()")."</th>
		<th nowrap>{organization}</th>
		<th nowrap>{nic}</th>
		<th nowrap>{tcp_address}</th>
		<th nowrap>{netmask}</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody class='tbody'>
	";	
	
			$net=new networking();
			$ip=new IP();	
		
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$eth="vlan{$ligne["ID"]}/{$ligne["nic"]}";
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="22-win-nic-off.png";
		
		if($interfaces["vlan{$ligne["ID"]}"]<>null){
			$img="22-win-nic.png";
		}
		
		if(trim($ligne["org"])==null){
			$ligne["org"]="<strong style='color:red'>{no_organization}</strong>";
		}
		
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html."
		<tr class=$classtr>
			<td width=1%><img src='img/$img'></td>
			<td><strong style='font-size:16px' align='right'>{$ligne["org"]}</strong></td>
			<td><strong style='font-size:16px' align='right'>$eth</strong></td>
			<td><strong style='font-size:16px' align='right'>{$ligne["ipaddr"]}</strong></td>
			<td><strong style='font-size:16px' align='right'>{$ligne["netmask"]}</strong></td>
			<td width=1%>". imgtootltip("24-administrative-tools.png","{edit}","VLANEdit({$ligne["ID"]})")."</td>
			<td width=1%>". imgtootltip("ed_delete.gif","{delete}","VLANDelete({$ligne["ID"]})")."</td>
		</tr>
		
		
		";
		
	}
	$sock=new sockets();
	$page=CurrentPageName();
	
	$html=$html."</tbody></table></center>
	<p>&nbsp;</p>
	<div style='text-align:right'>". button("{reconstruct_vlans}","BuildVLANs()")."</div>
	<p>&nbsp;</p>
	<table class=form>
	<tr>
		<td class=legend>{broadcast_has_ipaddr}</td>
		<td>". Field_checkbox("NetWorkBroadCastVLANAsIpAddr",1,$sock->GET_INFO("NetWorkBroadCastVLANAsIpAddr"),"NetWorkBroadCastAsVLANIpAddrSave()")."</td>
	</tr>
	</table>
	
	<script>
	
		var X_NetWorkBroadCastAsIpAddrSave= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			
		}
		
		function NetWorkBroadCastAsVLANIpAddrSave(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			var XHR = new XHRConnection();
			if(document.getElementById('NetWorkBroadCastVLANAsIpAddr').checked){
			XHR.appendData('NetWorkBroadCastVLANAsIpAddr',1);}else{XHR.appendData('NetWorkBroadCastVLANAsIpAddr',0);}
			XHR.sendAndLoad('$page', 'GET',X_NetWorkBroadCastAsIpAddrSave);
		}	
	
	</script>		
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function vlan_del(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
		if(!is_numeric(trim($_GET["vlan-del"]))){return ;}
		$sql="DELETE FROM nics_vlan WHERE ID={$_GET["vlan-del"]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		
		$sql="DELETE FROM iptables_bridge WHERE nics_vlan_id={$_GET["vlan-del"]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
				
		
}
function NetWorkBroadCastVLANAsIpAddrSave(){
	$sock=new sockets();
	$sock->SET_INFO("NetWorkBroadCastVLANAsIpAddr",$_GET["NetWorkBroadCastVLANAsIpAddr"]);
}

function ConstructVLANIP(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?vlan-ip-reconfigure=yes");
}

