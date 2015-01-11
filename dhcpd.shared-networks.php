<?php

session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
$users=new usersMenus();
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
	}
	
	if(isset($_GET["list-nets"])){list_nets();exit;}
	if(isset($_GET["shared-js"])){shared_js();exit;}
	if(isset($_GET["shared-edit"])){shared_edit();exit;}
	if(isset($_POST["domain-name"])){shared_post();exit;}
	if(isset($_POST["DelDHCPShared"])){shared_del();exit;}
	if(isset($_POST["SharedNetsApply"])){shared_apply();exit;}
page();

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}

function shared_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	
	$title=$tpl->javascript_parse_text("{new_group}");
	
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_sharednets WHERE ID='$ID'","artica_backup"));
		$title=$ligne["scopename"];
	}
	
	$title=$tpl->javascript_parse_text("{group2}:$title");
	echo "YahooWin5(650,'$page?shared-edit=$ID&t=$t&tt=$tt','$title',true)";	
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	

	$scope=$tpl->_ENGINE_parse_body("{scope}");
	$group=$tpl->_ENGINE_parse_body("{group}");
	$subnet=$tpl->_ENGINE_parse_body("{subnet}");
	$range_1=$tpl->_ENGINE_parse_body("{range} {from}");
	$range_2=$tpl->_ENGINE_parse_body("{range} {to}");
	$mask=$tpl->_ENGINE_parse_body("{mask}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$dhcpd_shared_network_explain=$tpl->_ENGINE_parse_body("{dhcpd_shared_network_explain}");
	$t=$_GET["t"];
	$tt=time();
	$html="
	<div class=text-info style='font-size:14px'>$dhcpd_shared_network_explain</div>
	<table class='table-items-$tt' style='display: none' id='table-items-$tt' style='width:99%'></table>
	<script>
	var DeleteAclKey$tt=0;
	function LoadTable$tt(){
	$('#table-items-$tt').flexigrid({
	url: '$page?list-nets=yes&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '$scope', name : 'scopename', width : 157, sortable : true, align: 'left'},
	{display: '$group', name : 'sharednet_name', width : 100, sortable : false, align: 'left'},
	{display: '$subnet', name : 'subnet', width : 116, sortable : false, align: 'left'},
	{display: '$mask', name : 'netmask', width : 116, sortable : false, align: 'left'},
	{display: '$range_1', name : 'range1', width : 116, sortable : false, align: 'left'},
	{display: '$range_2', name : 'range2', width : 116, sortable : false, align: 'left'},
	{display: '$delete', name : 'del', width : 42, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : NewGroup$tt},
	{name: '$apply', bclass: 'Reload', onpress : SharedNetsApply$tt},
	
	

	],
	searchitems : [
	{display: '$scope', name : 'scopename'},
	{display: '$group', name : 'sharednet_name'},
	{display: '$subnet', name : 'subnet'},
	],
	sortname: 'scopename',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true

});
}
function NewGroup$tt() {
	Loadjs('$page?shared-js=yes&ID=0&tt=$tt');
}


var xDeleteGroup$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-items-$tt').flexReload();
	ExecuteByClassName('SearchFunction');
}


function DeleteGroup$tt(ID){
	if(!confirm('$delete ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DelDHCPShared', ID);
	XHR.sendAndLoad('$page', 'POST',xDeleteGroup$tt);

}


var x_SharedNetsApply$tt= function (obj) {
	var tempvalue=obj.responseText;	
	if(tempvalue.length>3){alert(tempvalue);return;}
}
	
function SharedNetsApply$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('SharedNetsApply','yes');
	XHR.sendAndLoad('$page', 'POST',x_SharedNetsApply$tt);	
}


setTimeout('LoadTable$tt()',600);
</script>

";

echo $html;

}




function list_nets(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="dhcpd_sharednets";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("dhcpd_sharednets: no entry");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no event");}



	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		
		$delete=imgtootltip("delete-32.png",null,"DeleteGroup$tt('{$ligne["ID"]}')");
	

		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?shared-js=yes&ID={$ligne["ID"]}&tt=$tt');\"
		 style='font-size:16px;text-decoration:underline'>";


		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["scopename"]}</a></span>",
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["sharednet_name"]}</a></span>",
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["subnet"]}</a></span>",
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["netmask"]}</a></span>",
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["range1"]}</a></span>",
						"<span style='font-size:16px;font-weight:normal;color:$color'>$href{$ligne["range2"]}</a></span>",
						$delete
						)
		);
	}


	echo json_encode($data);

}

function shared_edit(){
	$ID=$_GET["shared-edit"];
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql();	
	$tt=$_GET["tt"];
	if(!is_numeric($ID)){$ID=0;}
	$sql="SELECT sharednet_name FROM dhcpd_sharednets GROUP BY sharednet_name ORDER BY sharednet_name";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$dhcpd_sharednets[$ligne["sharednet_name"]]=$ligne["sharednet_name"];
	}
	
	$groupname_field=Field_array_Hash($dhcpd_sharednets, "sharednet_name",$ligne["sharednet_name"],"style:font-size:16px;padding:3px");
	$sql="SELECT * FROM dhcpd_sharednets WHERE ID=$ID";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();
	
	if(count($domains)==0){$dom=Field_text('domain-name',$ligne["domain-name"],"font-size:16px;");}
	else{
		$domains[null]="{select}";
		$dom=Field_array_Hash($domains,'domain-name',$ligne["domain-name"],null,null,null,"font-size:16px;padding:3px");
	}
	
	$button="{apply}";
	if($ID==0){$button="{add}";}	
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px'>{groupname}:</td>
		<td>$groupname_field</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{groupname} ({add}):</td>
		<td>". Field_text("groupnameAdd",null,"font-size:16px")."</td>
		<td>". help_icon("{dhcp-groupnameAdd-explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{scope}:</td>
		<td>". Field_text("scope",$ligne["scopename"],"font-size:16px")."</td>
		<td>". help_icon("{dhcp-scope-explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ddns_domainname}:</td>
		<td>$dom</td>
		<td>". help_icon("{dhcp-domain-name-explain}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{subnet}:</td>
		<td>". field_ipv4("subnet_$t",$ligne["subnet"],"font-size:16px;padding:3px",true)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{netmask}:</td>
		<td>". field_ipv4("netmask_$t",$ligne["netmask"],"font-size:16px;padding:3px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{gateway}:</td>
		<td>".field_ipv4("routers_$t",$ligne["routers"],'font-size:16px;padding:3px')."&nbsp;</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>	
		<td class=legend style='font-size:16px'>{range}&nbsp;{from}:</td>
		<td>".field_ipv4('range1_'.$t,$ligne["range1"],'font-size:16px;padding:3px')."&nbsp;</td>
	</tr>
	<tr>	
		<td class=legend style='font-size:16px'>{range}&nbsp;{to}:</td>
		<td>".field_ipv4('range2_'.$t,$ligne["range2"],'font-size:16px;padding:3px')."&nbsp;</td>
	</tr>

	<tr>
		<td class=legend style='font-size:16px'>{subnet-mask}:</td>
		<td>".field_ipv4('subnet-mask_'.$t,$ligne["subnet-mask"],'font-size:16px;padding:3px')."</td>
		<td>". help_icon("{dhcp-subnet-masq_text}")."</td>
	</tr>	
	
	

	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{DNSServer} 1:</td>
		<td>".field_ipv4('domain-name-servers1',$ligne["domain-name-servers1"],'font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{DNSServer} 2:</td>
		<td>".field_ipv4('domain-name-servers2',$ligne["domain-name-servers2"],'font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{wins_server}:</td>
		<td>".field_ipv4('wins-server-group',$ligne["wins-server"],'font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>".Field_text('server-name',$ligne["server-name"],'width:210px;font-size:16px;padding:3px')."&nbsp;</td>
		<td>". help_icon("{dhcp-server-name_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{next-server}:</td>
		<td>".Field_text('next-server',$ligne["next-server"],'width:210px;font-size:16px;padding:3px')."&nbsp;</td>
		<td>". help_icon("{dhcp-next-server-explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{pxe_file}:</td>
		<td>".Field_text('pxe_filename',$ligne["pxe_filename"],'width:110px;font-size:16px;padding:3px')."&nbsp;</td>
		<td>". help_icon("{filename_pxe_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{pxe_root-path}:</td>
		<td>".Field_text('pxe_root-path',$ligne["pxe_root-path"],'width:210px;font-size:16px;padding:3px')."&nbsp;</td>
		<td>". help_icon("{pxe_root-path_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{tftp-server-name}:</td>
		<td>".Field_text('tftp-server-name',$ligne["tftp-server-name"],'width:210px;font-size:16px;padding:3px')."&nbsp;</td>
		<td>". help_icon("{tftp-server-name_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{option-176}:</td>
		<td><textarea style='font-size:16px;height:50px;width:100%;overflow:auto' id='option-176'>{$ligne["option-176"]}</textarea>&nbsp;</td>
		<td>". help_icon("{option-176-explain}")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button($button,"SharedDHCPNetSave$t()",22)."</td>
	</tr>
	
	
	</table>
	</div>
	<script>
	
var x_SharedDHCPNetSave$t= function (obj) {
	var tempvalue=obj.responseText;
	var ID='$ID'
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#table-items-$tt').flexReload();
	if(ID==0){YahooWin5Hide();}
	}		
	
	function SharedDHCPNetSave$t(){
	var XHR = new XHRConnection();
		XHR.appendData('domain-name',document.getElementById('domain-name').value);
		XHR.appendData('sharednet_name',document.getElementById('sharednet_name').value);
		XHR.appendData('groupnameAdd',document.getElementById('groupnameAdd').value);
		XHR.appendData('scope',document.getElementById('scope').value);
		XHR.appendData('subnet',document.getElementById('subnet_$t').value);
		XHR.appendData('netmask',document.getElementById('netmask_$t').value);
		XHR.appendData('routers',document.getElementById('routers_$t').value);
		XHR.appendData('range1',document.getElementById('range1_$t').value);
		XHR.appendData('range2',document.getElementById('range2_$t').value);
		XHR.appendData('subnet-mask',document.getElementById('subnet-mask_$t').value);
		XHR.appendData('domain-name-servers1',document.getElementById('domain-name-servers1').value);
		XHR.appendData('domain-name-servers2',document.getElementById('domain-name-servers2').value);
		XHR.appendData('tftp-server-name',document.getElementById('tftp-server-name').value);
		XHR.appendData('server-name',document.getElementById('server-name').value);
		XHR.appendData('next-server',document.getElementById('next-server').value);
		XHR.appendData('pxe_filename',document.getElementById('pxe_filename').value);
		XHR.appendData('pxe_root-path',document.getElementById('pxe_root-path').value);
		XHR.appendData('option-176',document.getElementById('option-176').value);
		XHR.appendData('wins-server',document.getElementById('wins-server-group').value);
		XHR.appendData('ID',$ID);
		XHR.sendAndLoad('$page', 'POST',x_SharedDHCPNetSave$t);	
	}
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function shared_post(){
	$sharednet_name=$_POST["sharednet_name"];
	if(trim($_POST["groupnameAdd"])<>null){$sharednet_name=$_POST["groupnameAdd"];}
	$tpl=new templates();
	$sharednet_name=replace_accents($sharednet_name);
	$_POST["scope"]=replace_accents($_POST["scope"]);
	$q=new mysql();
	$dhcp=new dhcpd();
	if($_POST["subnet"]==$dhcp->subnet){
		echo "{$_POST["subnet"]} cannot be the same of the main DHCP subnet";
		return;
	}
	if(trim($_POST["ID"])==0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM dhcpd_sharednets WHERE subnet='{$_POST["subnet"]}'","artica_backup"));
		if($ligne["ID"]>0){
			echo "{$_POST["subnet"]} Already defined\n";
			return;
		}
	}
	
	$sql="
	INSERT INTO dhcpd_sharednets (`sharednet_name`,`scopename`,
	`subnet`,
	`netmask`,
	`range1`,
	`range2`,
	`domain-name-servers1`,
	`domain-name-servers2`,
	`domain-name`,
	`routers`,
	`subnet-mask`,
	`tftp-server-name`,
	`next-server`,
	`pxe_filename`,
	`pxe_root-path`,
	`option-176`,
	`server-name`,
	`wins-server`
	) 
	VALUES('$sharednet_name','{$_POST["scope"]}',
	'{$_POST["subnet"]}',
	'{$_POST["netmask"]}',
	'{$_POST["range1"]}',
	'{$_POST["range2"]}',
	'{$_POST["domain-name-servers1"]}',
	'{$_POST["domain-name-servers2"]}',
	'{$_POST["domain-name"]}',
	'{$_POST["routers"]}',
	'{$_POST["subnet-mask"]}',
	'{$_POST["tftp-server-name"]}',
	'{$_POST["next-server"]}',
	'{$_POST["pxe_filename"]}',
	'{$_POST["pxe_root-path"]}',
	'{$_POST["option-176"]}',
	'{$_POST["server-name"]}',
	'{$_POST["wins-server"]}'
	
	)
	
	";
	
	if(trim($_POST["ID"])>0){
		$sql="UPDATE dhcpd_sharednets SET
		`sharednet_name`='$sharednet_name',
		`scopename`='{$_POST["scope"]}',
		`subnet`='{$_POST["subnet"]}',
		`netmask`='{$_POST["netmask"]}',
		`range1`='{$_POST["range1"]}',
		`range2`='{$_POST["range2"]}',
		`domain-name-servers1`='{$_POST["domain-name-servers1"]}',
		`domain-name-servers2`='{$_POST["domain-name-servers2"]}',
		`domain-name`='{$_POST["domain-name"]}',
		`routers`='{$_POST["routers"]}',
		`subnet-mask`='{$_POST["subnet-mask"]}',
		`tftp-server-name`='{$_POST["tftp-server-name"]}',
		`next-server`='{$_POST["next-server"]}',
		`pxe_filename`='{$_POST["pxe_filename"]}',
		`pxe_root-path`='{$_POST["pxe_root-path"]}',
		`option-176`='{$_POST["option-176"]}',
		`server-name`='{$_POST["server-name"]}',
		`wins-server`='{$_POST["wins-server"]}'
		WHERE ID='{$_POST["ID"]}'";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;echo "\n$sql\n";return;}
	
}

function shared_del(){
	$sql="DELETE FROM dhcpd_sharednets WHERE ID={$_POST["DelDHCPShared"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;echo "\n$sql\n";return;}	

	
}
function shared_apply(){
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?restart-dhcpd=yes');
}
