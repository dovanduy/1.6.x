<?php

session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.computers.inc');
$users=new usersMenus();
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
	}
	
	if(isset($_GET["list-nets"])){list_nets();exit;}
	if(isset($_GET["shared-edit"])){shared_edit();exit;}
	if(isset($_POST["domain-name"])){shared_post();exit;}
	if(isset($_POST["DelDHCPShared"])){shared_del();exit;}
	if(isset($_POST["SharedNetsApply"])){shared_apply();exit;}
	if(isset($_GET["action-rescan"])){action_rescan_js();exit;}
	if(isset($_POST["DCHP_LEASE_RESCAN"])){DCHP_LEASE_RESCAN();exit;}
page();

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}



function action_rescan_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	echo "
	var x_DCHP_LEASE_RESCAN= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
		ExecuteByClassName('SearchFunction');
	 }	
	
	function DCHP_LEASE_RESCAN(){
			var XHR = new XHRConnection();
			XHR.appendData('DCHP_LEASE_RESCAN','yes');
			XHR.sendAndLoad('$page', 'POST',x_DCHP_LEASE_RESCAN);
		}
		
	DCHP_LEASE_RESCAN()";
	
}


function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$title=$tpl->_ENGINE_parse_body($title);
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$link_computer=$tpl->_ENGINE_parse_body("{link_computer}");
	$created=$tpl->_ENGINE_parse_body("{created}");
	$updated=$tpl->_ENGINE_parse_body("{updated}");
	$buttons="
	buttons : [
	{name: '$rescan', bclass: 'add', onpress : rescan$t},
	],";		
		
	$title=$tpl->javascript_parse_text("{dhcp_requests}");
$buttons=null;
$html="
<table class='DHCP_REQUESTS_TABLE' style='display: none' id='DHCP_REQUESTS_TABLE' style='width:100%'></table>
<script>
$(document).ready(function(){
var TMPMD='';
$('#DHCP_REQUESTS_TABLE').flexigrid({
	url: '$page?list-nets=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width : 368, sortable : false, align: 'left'},	
		{display: '$addr', name : 'ipaddr', width :116, sortable : true, align: 'left'},
		{display: '$ComputerMacAddress', name : 'mac', width :140, sortable : true, align: 'left'},
		{display: '$created', name : 'created', width : 173, sortable : true, align: 'left'},
		{display: '$updated', name : 'updated', width : 285, sortable : true, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$addr', name : 'ipaddr'},
		{display: '$ComputerMacAddress', name : 'mac'},
		
		],
	sortname: 'updated',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function rescan$t(){
Loadjs('$page?action-rescan=yes')


}
</script>
";

		

	echo $html;
	
}


function list_nets(){
	
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="dhcpd_hosts";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	

	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		json_error_show("no data<hr>$sql",0);
	}
	$sock=new sockets();
	$cmp=new computers();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($ligne["MAC"]==null){continue;}
		$color="black";
		
		$js="zBlut();";
		$href=null;
		$uid=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		
		
		if($uid<>null){
			
			$js=MEMBER_JS($uid,1,1);
			$href="<a href=\"javascript:blur()\" OnClick=\"javascript:$js\" 
			style='font-size:16px;text-decoration:underline;color:$color'>";
			$uid=" ($uid)";
		}
		
		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}
		if($ligne["MAC"]==null){$ligne["MAC"]="&nbsp;";}
		
		$array["hostname"]=$ligne["hostname"];
		$array["ipaddr"]=$ligne["ipaddr"];
		$array["mac"]=$ligne["MAC"];
		
		$increment=urlencode(serialize($array));
		
		$href="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('dhcpd.fixed.hosts.php?modify-dhcpd-settings-js=yes&increment=$increment');\"
		style='font-size:16px;color:$color;font-weight:bold;text-decoration:underline'>";
		
		
		
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT mac FROM dhcpd_fixed WHERE `mac`='{$ligne["MAC"]}'","artica_backup"));
		if($ligne2["mac"]<>null){
			$href=null;
			$color="#068C22";
			
			$href="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('dhcpd.fixed.hosts.php?modify-dhcpd-settings-js=yes&mac={$ligne["MAC"]}');\"
			style='font-size:16px;color:$color;text-decoration:underline'>";
			
		}
		

	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:16px;color:$color;'><strong>{$ligne["hostname"]}</strong></a>$uid</span>",
			"<span style='font-size:16px;color:$color;'>{$ligne["ipaddr"]}</a></span>",
			"<span style='font-size:16px;color:$color;'>$href{$ligne["MAC"]}</a></span>",
			"<span style='font-size:16px;color:$color;'>{$ligne["created"]}</a></span></span>",
			"<span style='font-size:16px;color:$color;'>{$ligne["updated"]}</a></span><br></span>",
			
			)
		);
	}
	
	
echo json_encode($data);	
	
}



function DCHP_LEASE_RESCAN(){
	$sock=new sockets();
	$sock->getFrameWork("network.php?dhcpd-leases=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
	
	
}