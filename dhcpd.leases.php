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
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	$buttons="
	buttons : [
	{name: '$rescan', bclass: 'add', onpress : rescan$t},
	],";		
		
	
if($explain<>null){$explain="<div class=explain style='font-size:13px'>$explain</div>";}	
$html="
$explain
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
var TMPMD='';
$('#flexRT$t').flexigrid({
	url: '$page?list-nets=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width : 353, sortable : false, align: 'left'},	
		{display: '$addr', name : 'ipaddr', width :95, sortable : true, align: 'left'},
		{display: '$ComputerMacAddress', name : 'mac', width :102, sortable : true, align: 'left'},
		{display: 'Starts', name : 'starts', width : 117, sortable : true, align: 'left'},
		{display: 'END', name : 'ends', width : 117, sortable : true, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$addr', name : 'ipaddr'},
		{display: '$ComputerMacAddress', name : 'mac'},
		
		],
	sortname: 'ends',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 865,
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
	$table="dhcpd_leases";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
$_POST["query"]=trim($_POST["query"]);
	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
		if($ligne["mac"]==null){continue;}
		$tooltip="<div style=font-size:11px>start {$ligne["starts"]} cltt:{$ligne["cltt"]} tstp:{$ligne["tstp"]}</div>";
		$js="zBlut();";
		$href=null;
		$uid=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		if($uid<>null){
			
			$js=MEMBER_JS($uid,1,1);
			$href="<a href=\"javascript:blur()\" OnClick=\"javascript:$js\" style='font-size:12px;text-decoration:underline'>";
			$uid=" ($uid)";
		}
		
		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}
		if($ligne["mac"]==null){$ligne["mac"]="&nbsp;";}
		

		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:12px;'>$href<strong>{$ligne["hostname"]}</strong></a>$uid</span>",
			"<span style='font-size:12px;'>$href{$ligne["ipaddr"]}</a></span>",
			"<span style='font-size:12px;'>$href{$ligne["mac"]}</span>",
			"<span style='font-size:12px;'>$href{$ligne["starts"]}</span>",
			"<span style='font-size:12px;'>$href{$ligne["ends"]}</span>",
			
			)
		);
	}
	
	
echo json_encode($data);	
	
}


function list_nets_old(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$search=$_GET["search"];
	$q=new mysql();
	if($search<>null){
		$search="*$search*";
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", "%", $search);
		$search_sql=" WHERE (mac LIKE '$search') OR (ipaddr LIKE '$search') OR (hostname LIKE '$search')";
	}
	
	$sql="SELECT * FROM dhcpd_leases $search_sql ORDER BY `dhcpd_leases`.`cltt` DESC LIMIT 0,100";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}	
	
	
$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<th width=1%>&nbsp;</th>
	<th>{hostname}</th>
	<th>{ipaddr}</th>
	<th>{ComputerMacAddress}</th>
	<th>{end}</th>
</thead>
<tbody class='tbody'>";	
	
$cmp=new computers();

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["mac"]==null){continue;}
		$tooltip="
		<table class=form>
		<tr>
			<td class=legend>start:</td>
			<td><strong style=font-size:13px>{$ligne["starts"]}</td>
		</tr>
		<tr>
			<td class=legend>cltt:</td>
			<td><strong style=font-size:13px>{$ligne["cltt"]}</td>
		</tr>
		<tr>
			<td class=legend>tstp:</td>
			<td><strong style=font-size:13px>{$ligne["tstp"]}</td>
		</tr>
		</table>
		";
		$js=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		if($uid<>null){
			$img="30-computer.png";
			$js=MEMBER_JS($uid,1,1);
			$tooltip=$tooltip."<br>{view}";
		}else{
			$img="30-computer-grey.png";
		}
		
		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}
		if($ligne["mac"]==null){$ligne["mac"]="&nbsp;";}
		
		$html=$html."
		<tr class=$classtr>
		<td width=1% style='font-size:14px' align='center'>". imgtootltip("30-computer.png","$tooltip",$js)."</td>
		<td style='font-size:13px'>$href{$ligne["hostname"]}</a></td>
		<td style='font-size:13px' nowrap>$href{$ligne["ipaddr"]}</a></td>
		<td style='font-size:13px'>$href{$ligne["mac"]}</a></td>
		<td style='font-size:13px' nowrap>$href{$ligne["ends"]}</td>
	</tr>
		";
		
	}
	
$html=$html."</table>


";

echo $tpl->_ENGINE_parse_body($html);
	
}
function DCHP_LEASE_RESCAN(){
	$sock=new sockets();
	$sock->getFrameWork("network.php?dhcpd-leases=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
	
	
}