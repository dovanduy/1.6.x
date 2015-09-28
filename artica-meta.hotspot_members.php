<?php
include_once('ressources/class.templates.inc');



$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_GET["export"])){export();exit;}
if(isset($_GET["content-table"])){content_table();exit;}
if(isset($_GET["content-search"])){content_search();exit;}

if(isset($_GET["unlink-js"])){unlink_js();exit;}
if(isset($_POST["unlink"])){unlink_perform();exit;}
if(isset($_GET["search"])){search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$date=$tpl->javascript_parse_text("{date}");
	$create_a_snapshot=$tpl->javascript_parse_text("{create_a_snapshot}");
	$link_all_hosts=$tpl->javascript_parse_text("{link_all_hosts}");
	$export=$tpl->javascript_parse_text("{export}");
	$servers=$tpl->javascript_parse_text("{servers}");
	$members=$tpl->javascript_parse_text("{members}");
	$title=$tpl->javascript_parse_text("{hotspot_members}");


	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$export</strong>', bclass: 'export', onpress : Export$t},

	],";

	
	$uuidenc=urlencode($_GET["uuid"]);
	$html="

	<table class='ARTICA_META_HOTSPOTMEMBERS_TABLE' style='display: none' id='ARTICA_META_HOTSPOTMEMBERS_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_HOTSPOTMEMBERS_TABLE').flexigrid({
	url: '$page?content-search=yes&uuid=$uuidenc',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$date</span>', name : 'creationtime', width : 288, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$members</span>', name : 'uid', width : 288, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$servers</span>', name : 'hostname', width : 288, sortable : true, align: 'right'},
	

	

	],
	$buttons
	searchitems : [
	{display: '$members', name : 'uid'},
	{display: '$servers', name : 'uuid'},


	],
	sortname: 'creationtime',
	sortorder: 'desc',
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

function run$t(){
Loadjs('snapshots.progress.php');
}

var xLinkEdHosts$t= function (obj) {
var res=obj.responseText;
if (res.length>3){ alert(res); return; }
$('#ARTICA_META_POLICYHOSTS_TABLE').flexReload();
$('#ARTICA_META_GROUP_TABLE').flexReload();
}


function Export$t(uuid){
	YahooWin3('850','$page?export=yes','$export');
}

function LinkHostsAll$t(){
if(!confirm('$link_all_hosts_ask')){return;}
var XHR = new XHRConnection();
XHR.appendData('link-all','{$_GET["ID"]}');
XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

function Orders$t(){
Loadjs('artica-meta.menus.php?gpid={$_GET["ID"]}');
}

</script>";
echo $html;
}

function export(){
	$q=new mysql_meta();
	$table="(SELECT hotspot_members.creationtime, hotspot_members.uid, metahosts.hostname,hotspot_members.uuid FROM
	hotspot_members,metahosts WHERE hotspot_members.uuid=metahosts.uuid) as t";
	$sql="SELECT * FROM $table";
	$results = $q->QUERY_SQL($sql,$database);
	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne["uid"];
		$hostname=$ligne["hostname"];
		$cell[]="$uid;$hostname";
		
		
	}
	
	echo "<textarea style='width:98%;height:350px;'>".@implode("\n", $cell)."</textarea>";
	
	
}

function content_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="snapshots";
	$database=null;
	$ID=$_GET["ID"];

	if(!$q->TABLE_EXISTS($table,$database)){
		json_error_show("no data - no table");
	}

	$searchstring=string_to_flexquery();
	$page=1;

	$q=new mysql_meta();
	
	$table="(SELECT hotspot_members.creationtime, hotspot_members.uid, metahosts.hostname,hotspot_members.uuid FROM 
	hotspot_members,metahosts WHERE hotspot_members.uuid=metahosts.uuid) as t";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=22;

	$c=1;
	// 	//service,server_port,client_port,helper,enabled
	$style="style='font-size:18px'";
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		
		$creationtime=$ligne["creationtime"];
		$zdate=$tpl->time_to_date($creationtime,true);
		$uid=$ligne["uid"];
		$hostname=$ligne["hostname"];
		$key=md5(serialize($ligne));
		$cell=array();
		$cell[]="<span $style>$zdate</a></span>";
		$cell[]="<span $style>$uid</a></span>";
		$cell[]="<span $style>$hostname</a></span>";


		$data['rows'][] = array(
				'id' => $key,
				'cell' => $cell
		);
	}

	$data['total'] = $c;
	echo json_encode($data);
}