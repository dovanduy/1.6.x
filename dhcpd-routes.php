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

		$usersmenus=new usersMenus();
		if(!GetRights()){
			$tpl=new templates();
			echo "alert('".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."');";
			die();
		}
		
		if(isset($_GET["route-js"])){route_js();exit;}
		if(isset($_GET["new-route-js"])){route_add_js();exit;}
		if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
		
		if(isset($_GET["search"])){search();exit;}
		if(isset($_GET["new-route-popup"])){route_add();exit;}
		if(isset($_POST["ip"])){route_add_save();exit;}
		if(isset($_GET["popup"])){popup();exit;}
		if(isset($_GET["list"])){echo popup_list();exit;}
		if(isset($_POST["delete"])){route_delete();exit;}
table();

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$route=$tpl->javascript_parse_text("{ip_address}/{netmask}");
	$gateway=$tpl->javascript_parse_text("{gateway}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$new_route=$tpl->javascript_parse_text("{new_route}");
		$servername=$tpl->javascript_parse_text("{servername2}");
		$status=$tpl->javascript_parse_text("{status}");
		$events=$tpl->javascript_parse_text("{events}");
		$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
		$policies=$tpl->javascript_parse_text("{policies}");
		$orders=$tpl->javascript_parse_text("{orders}");
		$type=$tpl->javascript_parse_text("{type}");
		$link_host=$tpl->javascript_parse_text("{link_policy}");
		$link_all_hosts=$tpl->javascript_parse_text("{link_all_hosts}");
		$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
		$policies=$tpl->javascript_parse_text("{policies}");
		$interface=$tpl->javascript_parse_text("{interface}");
		$apply=$tpl->javascript_parse_text("{apply}");
		$title=$tpl->javascript_parse_text("{APP_DHCP_ROUTES_CONF}");
		$t=time();
		$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
		$categorysize=387;
		$tag=$tpl->javascript_parse_text("{tag}");
	
		$q=new mysql_meta();
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
		$groupname=$tpl->javascript_parse_text($ligne["groupname"]);
		$buttons="
		buttons : [
		{name: '$new_route', bclass: 'add', onpress : NewRoute$t},
		{name: '$apply', bclass: 'Apply', onpress : Orders$t},
		],";
	
	
	
		$html="
<table class='ARTICA_DHCP_ROUTES' style='display: none' id='ARTICA_DHCP_ROUTES' style='width:1200px'></table>
<script>
$(document).ready(function(){
		$('#ARTICA_DHCP_ROUTES').flexigrid({
		url: '$page?search=yes',
		dataType: 'json',
		colModel : [
		{display: '$interface', name : 'nic', width : 141, sortable : true, align: 'left'},
		{display: '$route', name : 'subnet', width : 482, sortable : true, align: 'left'},
		{display: '$gateway', name : 'gateway', width : 300, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	
		],
		$buttons
		searchitems : [
		{display: '$route', name : 'subnet'},
		{display: '$gateway', name : 'gateway'},
		
		],
		sortname: 'subnet',
		sortorder: 'asc',
		usepager: true,
		title: '<strong style=font-size:22px>$title</strong>',
		useRp: true,
		rpOptions: [10, 20, 30, 50,100,200],
		rp:50,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	
function NewRoute$t(){
	Loadjs('$page?new-route-js=yes');
}
	
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_GROUPPOLICY_TABLE').flexReload();
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}
	
	
function LinkEdHosts$t(policyid){
	var XHR = new XHRConnection();
	XHR.appendData('link-policy',policyid);
	XHR.appendData('gpid','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
	
function LinkHostsAll$t(){
	if(!confirm('$link_all_hosts_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('link-all','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
	
function Orders$t(){
	Loadjs('dhcpd.progress.php');
}
	
</script>";
	echo $html;
	}	



function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();	
	$table="dhcpd_routes";
	$dhcp=new dhcpd_sub();
	
	$searchstring=string_to_flexquery();
	$page=1;

	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
		
	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(!is_numeric($rp)){$rp=50;}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="26";
	
	$style="<span style='font-size:{$fontsize}px'>";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$ID=$ligne["ID"];
		$nic=$ligne["nic"];
		$netmask=$ligne["netmask"];
		$subnet=$ligne["subnet"];
		$gateway=$ligne["gateway"];
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?route-delete-js={$ligne["ID"]}')");
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?route-js={$ligne["ID"]}');\" 
		style='text-decoration:underline'>";
		$cell=array();
		
		$cell[]="$style$js$nic</a></span>";
		$cell[]="$style$js$subnet/$netmask</a></span>";
		$cell[]="$style$js$gateway</a></span>";
		$cell[]="$style$delete</span>";
		
		
	$data['rows'][] = array(
		'id' => $ligne['uuid'],
		'cell' => $cell
		);
	}
	
	
echo json_encode($data);	
	
}


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
	

	
	function DHCPDeleteRoute(ip){
		var XHR = new XHRConnection();
		XHR.appendData('delip',ip);
		AnimateDiv('dhcpdroutes');
		XHR.sendAndLoad('$page', 'GET',x_AddRouteDHCPD);		
	}
	
	{$prefix}LoadPage();";
	
echo $html;
}

function route_add_save(){
	$dhcp=new dhcpd_sub($_POST["nic"]);
	$dhcp->DelRoute($_POST["ID"]);
	$dhcp->AddRoute($_POST["ip"],$_POST["netmask"],$_POST["gateway"]);
}


function route_delete(){
	$dhcp=new dhcpd_sub($_POST["nic"]);
	$dhcp->DelRoute($_POST["delete"]);
}


function route_add_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_route}");
	$page=CurrentPageName();
	echo "YahooWin5(850,'$page?new-route-popup=yes','$title')";
	
	
}
function route_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	
	$dhcp=new dhcpd_sub();
	$dhcp->LoadRoute($_GET["route-js"]);
	
	$title="$dhcp->route_subnet/$dhcp->route_netmask -> $dhcp->route_gateway";
	$page=CurrentPageName();
	echo "YahooWin5(850,'$page?new-route-popup=yes&ID={$_GET["route-js"]}','$title')";
	
	
}

function route_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	
	$dhcp=new dhcpd_sub($_POST["nic"]);
	$dhcp->LoadRoute($_GET["route-delete-js"]);
	
	$title=$tpl->javascript_parse_text("{delete} $dhcp->route_subnet/$dhcp->route_netmask -> $dhcp->route_gateway");
	$t=time();
echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_DHCP_ROUTES').flexReload();
}
	
	
function LinkEdHosts$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["route-delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
	
LinkEdHosts$t();
" ;	
}



function route_add(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$dhcp=new dhcpd(0,1);
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$btname="{add}";
	$dhcpR=new dhcpd_sub();
	$dhcpR->LoadRoute($ID);
	if($ID>0){$btname="{apply}";}
	
	$nic=$dhcp->array_tcp;
	
	while (list ($num, $val) = each ($nic) ){
		if($num==null){continue;}
		if($num=="lo"){continue;}
		$q=new system_nic($num);
		$array[$num]="$num $q->NICNAME - $q->netzone";
	}
	
	$form="
	
	<div style='width:98%' class=form>	
			<div style='font-size:18px;margin:15px' class=explain>{APP_DHCP_ROUTES_EXPLAIN}</div>	
	<table style='width:99%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:26px'>{interface}:</td>
		<td>". Field_array_Hash($array, "nic-$t",$dhcpR->route_nic,null,null,0,"font-size:26px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:26px'>{network2}:</td>
		<td>". field_ipv4("dhcpd_ip-$t",$dhcpR->route_subnet,"font-size:26px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{netmask}:</td>
		<td>". field_ipv4("dhcpd_netmask-$t",$dhcpR->route_netmask,"font-size:26px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{gateway}:</td>	
		<td>". field_ipv4("dhcpd_gateway-$t",$dhcpR->route_gateway,"font-size:26px")."</td>
	</tR>
	<tr>
		<td colspan=2 align='right'><hr>". button($btname,"AddRouteDHCPD$t();",40)."</td>
	</tr>
	</tbody>
	</table>
</div>
<script>
var xAddRouteDHCPD$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#ARTICA_DHCP_ROUTES').flexReload();
	YahooWin5Hide();
}		
	
	
function AddRouteDHCPD$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('ip',document.getElementById('dhcpd_ip-$t').value);
	XHR.appendData('netmask',document.getElementById('dhcpd_netmask-$t').value);
	XHR.appendData('gateway',document.getElementById('dhcpd_gateway-$t').value);
	XHR.appendData('nic',document.getElementById('nic-$t').value);
	XHR.sendAndLoad('$page', 'POST',xAddRouteDHCPD$t);	
}
</script>
";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($form);
}


?>