<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_POST["link-host"])){link_host();exit;}
if(isset($_GET["unlink-js"])){unlink_host();exit;}
if(isset($_POST["unlink"])){unlink_host_perform();exit;}
if(isset($_POST["link-all"])){link_all_hosts();exit;}
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
	$switch=$tpl->javascript_parse_text("{switch}");
	$link_host=$tpl->javascript_parse_text("{link_host}");
	$link_all_hosts=$tpl->javascript_parse_text("{link_all_hosts}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$hosts=$tpl->javascript_parse_text("{hosts}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
	$groupname=$tpl->javascript_parse_text($ligne["groupname"]);
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$link_host</strong>', bclass: 'add', onpress : LinkHosts$t},
	{name: '<strong style=font-size:18px>$link_all_hosts</strong>', bclass: 'add', onpress : LinkHostsAll$t},
	{name: '<strong style=font-size:18px>$orders</strong>', bclass: 'add', onpress : Orders$t},
	],";



	$html="

	<table class='ARTICA_META_GROUPHOSTS_TABLE' style='display: none' id='ARTICA_META_GROUPHOSTS_TABLE' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_GROUPHOSTS_TABLE').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$hosts', name : 'hostname', width : 482, sortable : true, align: 'left'},
	{display: '$tag', name : 'hostag', width : 300, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$hosts', name : 'hostname'},
	{display: '$tag', name : 'hostag'},

	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$groupname: $hosts</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

function LinkHosts$t(){
	Loadjs('artica-meta.browse-hosts.php?function=LinkEdHosts$t');
}

var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_GROUPHOSTS_TABLE').flexReload();
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}			
	

function LinkEdHosts$t(uuid){
	var XHR = new XHRConnection();
	XHR.appendData('link-host',uuid);
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
	Loadjs('artica-meta.menus.php?gpid={$_GET["ID"]}');
}

</script>";
echo $html;
}

function unlink_host(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{unlink}");
	$page=CurrentPageName();
	$zmd5=$_GET["unlink-js"];
	
	
	
	
	$q=new mysql_meta();
	$sql="SELECT uuid FROM metagroups_link WHERE zmd5='$zmd5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$uuid= $ligne["uuid"];
	
	
	
	$hostname=$q->uuid_to_host($uuid);
	$t=time();
	echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_GROUPHOSTS_TABLE').flexReload();
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}			
	

function LinkEdHosts$t(){
	if(!confirm('$title $hostname ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}	

LinkEdHosts$t();
" ;
	
}
function unlink_host_perform(){
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM metagroups_link WHERE zmd5='{$_POST["unlink"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function link_host(){
	$zmd5=md5("{$_POST["link-host"]}{$_POST["gpid"]}");
	$q=new mysql_meta();
	$q->QUERY_SQL("INSERT IGNORE INTO metagroups_link (zmd5,gpid,uuid) 
				VALUES ('$zmd5','{$_POST["gpid"]}','{$_POST["link-host"]}')");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function link_all_hosts(){
	$q=new mysql_meta();
	$gpid=$_POST["link-all"];
	$results=$q->QUERY_SQL("SELECT uuid FROM metahosts");
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5("{$ligne["uuid"]}{$gpid}");
		$q->QUERY_SQL("INSERT IGNORE INTO metagroups_link (zmd5,gpid,uuid)
				VALUES ('$zmd5','$gpid','{$ligne["uuid"]}')");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	
}


function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="metagroups";

	if(!$q->TABLE_EXISTS("metagroups_link")){$q->CheckTables();}

	
	$table="(SELECT metahosts.hostname,metahosts.hostag,
			metahosts.uuid,metagroups_link.zmd5 
			FROM metahosts,metagroups_link WHERE
			metagroups_link.uuid=metahosts.uuid
			AND metagroups_link.gpid={$_GET["ID"]}) as t";
	
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];

	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");


	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";

		$ColorTime="black";
		$uuid=$ligne["uuid"];
		$hostname=$ligne["hostname"];
		$hostag=utf8_encode($ligne["hostag"]);
		$zmd5=$ligne["zmd5"];

		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";


		$urijs="Loadjs('artica-meta.menus.php?js=yes&uuid=$uuid');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";

		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?unlink-js=$zmd5')");
		$cell=array();
		$cell[]="<span $style>$link$hostname</a></span><br>$uuid";
		$cell[]="<span $style>$hostag</a></span>";
		$cell[]="$delete";

		$data['rows'][] = array(
				'id' => $ligne['uuid'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}