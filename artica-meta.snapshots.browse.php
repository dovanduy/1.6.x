<?php
include_once('ressources/class.templates.inc');



$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_WARNING_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_GET["content-js"])){content_js();exit;}
if(isset($_GET["content-table"])){content_table();exit;}
if(isset($_GET["content-search"])){content_search();exit;}

if(isset($_GET["unlink-js"])){unlink_js();exit;}
if(isset($_POST["unlink"])){unlink_perform();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["table"])){page();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{snapshots}");
	$_GET["gpid"]=intval($_GET["gpid"]);
	echo "YahooWin2('750','$page?table=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}','$title')";


}

function content_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$table="snapshots";
	$database=null;
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	
	$q=new mysql_meta();
	$sql="SELECT zDate FROM $table WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$title=$tpl->time_to_date(strtotime($ligne["zDate"]),true);
	echo "YahooWin2('850','$page?content-table=yes&ID=$ID','$title')";
	
	
}
function unlink_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{unlink}");
	$table="snapshots";
	$database=null;
	$page=CurrentPageName();
	$ID=$_GET["unlink-js"];




	$q=new mysql_meta();
	$sql="SELECT zDate FROM $table WHERE zmd5='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$title=$tpl->time_to_date(strtotime($ligne["zDate"]),true);
	$title=$tpl->javascript_parse_text("{delete}: $title");


	
	$t=time();
	echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	$('#ARTICA_META_SNAPSHOTS_BROWSE_TABLE').flexReload();
	
}


function LinkEdHosts$t(){
	if(!confirm('$title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink','$ID');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}

LinkEdHosts$t();
" ;

}

function unlink_perform(){
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM snapshots WHERE zmd5='{$_POST["unlink"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}


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
	$restore=$tpl->javascript_parse_text("{restore}");
	$create_a_snapshot=$tpl->javascript_parse_text("{create_a_snapshot}");
	$link_all_hosts=$tpl->javascript_parse_text("{link_all_hosts}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$date=$tpl->javascript_parse_text("{date}");
	$size=$tpl->javascript_parse_text("{size}");
	$title=$tpl->javascript_parse_text("{snapshots}");
	$hostag=$tpl->javascript_parse_text("{tag}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	
	$artica_meta=new mysql_meta();
	
	if($_GET["gpid"]==0){
		if($_GET["uuid"]<>null){
			$subtitle=$tpl->javascript_parse_text("{host}: ").$artica_meta->uuid_to_host($_GET["uuid"]);
		}
	}else{
		$subtitle=$tpl->javascript_parse_text("{group2}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	
	
	
	
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name,policy_type FROM policies WHERE ID='{$_GET["policy-id"]}'"));
	$groupname=$tpl->javascript_parse_text($ligne["policy_name"]);
	$buttons="
	buttons : [
	{name: '$create_a_snapshot', bclass: 'apply', onpress : run$t},
	
	],";

	$buttons=null;
	$uuidenc=urlencode($_GET["uuid"]);
	$html="

	<table class='ARTICA_META_SNAPSHOTS_BROWSE_TABLE' style='display: none' id='ARTICA_META_SNAPSHOTS_BROWSE_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_SNAPSHOTS_BROWSE_TABLE').flexigrid({
	url: '$page?search=yes&uuid=$uuidenc&gpid={$_GET["gpid"]}',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 191, sortable : true, align: 'left'},
	{display: '$hostname', name : 'hostname', width : 150, sortable : true, align: 'right'},
	{display: '$size', name : 'size', width : 150, sortable : true, align: 'right'},
	
	{display: '$restore', name : 'delete', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : true, align: 'center'},
	

	],
	$buttons
	searchitems : [
	{display: '$date', name : 'zDate'},
	{display: '$hostname', name : 'hostname'},
	{display: '$hostag', name : 'hostag'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$subtitle - $title</strong>',
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

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="snapshots";
	$database=null;
	$uuid=$_GET["uuid"];
	$uuidenc=urlencode($uuid);
	$gpid=intval($_GET["gpid"]);

	if(!$q->TABLE_EXISTS($table,$database)){
		json_error_show("no data - no table");
	}

	$searchstring=string_to_flexquery();
	$page=1;
	$table="(SELECT 
		snapshots.zmd5,snapshots.zDate,
		snapshots.size,
		metahosts.hostname,
		metahosts.hostag
		FROM snapshots,metahosts WHERE metahosts.uuid=snapshots.uuid) as t";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",0);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="14";
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
		
		$hostname=$ligne["hostname"];
		$hostag=utf8_encode($ligne["hostag"]);
		$zmd5=$ligne["zmd5"];
	
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		
		$xdate=$ligne["zDate"];
		$xtime=strtotime($xdate);
		$date=$tpl->time_to_date($xtime,true);
		$size=FormatBytes($ligne["size"]/1024);
	
		$urijs="Loadjs('$MyPage?content-js=yes&ID={$ligne["ID"]}');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
	
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?unlink-js={$ligne["zmd5"]}')");
		$restore=imgtootltip("32-import.png",null,"Loadjs('artica-meta.menus.php?snapshot-restore-js=yes&zmd5={$ligne["zmd5"]}&uuid=$uuidenc&gpid=$gpid')");
		if($uuid==null){
			if($gpid==0){
				$restore="&nbsp;-&nbsp;";
			}
		}
		
		
		$cell=array();
		$cell[]="<span $style>$xdate<br><i style='font-size:12px'>$date</i></a></span>";
		$cell[]="<span $style>$hostname<br><i style='font-size:12px'>$hostag</i></a></span>";
		$cell[]="<span $style>$size</a></span>";
		$cell[]=$restore;
		$cell[]="$delete";

		$data['rows'][] = array(
				'id' => $ligne['uuid'],
				'cell' => $cell
		);
	}


	echo json_encode($data);
}
