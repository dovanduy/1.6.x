<?php

if(isset($_GET["verbose"])){
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();

}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{browse_computers}");
	$page=CurrentPageName();
	echo "YahooWinBrowse('850','$page?table=yes&function=".urlencode($_GET["function"])."','$title')";
}


function table(){


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
	$policies=$tpl->javascript_parse_text("{policies}");
	$packages=$tpl->javascript_parse_text("{packages}");
	$switch=$tpl->javascript_parse_text("{switch}");
	$browse_hosts=$tpl->javascript_parse_text("{browse_computers}");
	$hosts=$tpl->javascript_parse_text("{hosts}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();

/*	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	],";
*/
	$buttons=null;
	$link_host=$tpl->javascript_parse_text("{link_host}");

	$html="

	<table class='ARTICA_META_BROWSEHOSTS_TABLE' style='display: none' id='ARTICA_META_BROWSEHOSTS_TABLE' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_BROWSEHOSTS_TABLE').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$hosts', name : 'hostname', width : 335, sortable : true, align: 'left'},
	{display: '$tag', name : 'hostag', width : 357, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'link', width : 70, sortable : false, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$hosts', name : 'hostname'},
	{display: '$tag', name : 'hostag'},

	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$browse_hosts</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

function LinkHosts$t(uuid){
	{$_GET["function"]}(uuid);
}

</script>";
echo $html;
}

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="metahosts";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="16";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";

	$t=$_GET["t"];

	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";

		$ColorTime="black";
		$uuid=$ligne["uuid"];
		
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";


		$urijs="Loadjs('$MyPage?group-js=yes&ID={$ligne["ID"]}')";
		$link=imgsimple("arrow-right-24.png",null,"LinkHosts$t('{$ligne["uuid"]}')");

		$cell=array();
		$hostag=utf8_encode($ligne["hostag"]);
		$cell[]="<span $style>{$ligne["hostname"]}</a></span><br>$uuid";
		$cell[]="<span $style>$hostag</a></span>";
		$cell[]="$link";

		$data['rows'][] = array(
				'id' => $ligne['uuid'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}