<?php
include_once('ressources/class.templates.inc');



$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_GET["content-js"])){content_js();exit;}
if(isset($_GET["content-table"])){content_table();exit;}
if(isset($_GET["content-search"])){content_search();exit;}

if(isset($_GET["unlink-js"])){unlink_js();exit;}
if(isset($_POST["unlink"])){unlink_perform();exit;}
if(isset($_GET["search"])){search();exit;}


page();

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
	$('#ARTICA_META_SNAPSHOTS_TABLE').flexReload();
	
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
	
	
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();
	
	$sql="SELECT SUM(size) as size FROM snapshots WHERE uuid='{$_GET["uuid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){
		if(stripos($q->mysql_error, "crashed and should be repaired")>0){
			$button="<center style='margin:20px'>".button("{repair_table}","Loadjs('mysql.repair.progress.php?table=snapshots&database=articameta')",40)."</center>";
				
		}
		echo FATAL_ERROR_SHOW_128(__FUNCTION__."/".__LINE__."<br>".$q->mysql_error.$button);
		return;
	}
	$size=$ligne["size"];
	$size=FormatBytes($size/1024,true);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name,policy_type FROM policies WHERE ID='{$_GET["policy-id"]}'"));
	$groupname=$tpl->javascript_parse_text($ligne["policy_name"]);
	$buttons="
	buttons : [
	{name: '$create_a_snapshot', bclass: 'apply', onpress : run$t},
	
	],";

	$buttons=null;
	$uuidenc=urlencode($_GET["uuid"]);
	$html="

	<table class='ARTICA_META_SNAPSHOTS_TABLE' style='display: none' id='ARTICA_META_SNAPSHOTS_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_SNAPSHOTS_TABLE').flexigrid({
	url: '$page?search=yes&uuid=$uuidenc',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 692, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 150, sortable : true, align: 'right'},
	{display: '$restore', name : 'delete', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : true, align: 'center'},
	

	],
	$buttons
	searchitems : [
	{display: '$date', name : 'zDate'},
	

	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title ( $size )</strong>',
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

	if(!$q->TABLE_EXISTS($table,$database)){
		json_error_show("no data - no table");
	}

	$searchstring=string_to_flexquery();
	$page=1;
	$table="(SELECT zmd5,zDate,size FROM $table WHERE uuid='{$_GET["uuid"]}') as t";

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
		
		$xdate=$ligne["zDate"];
		$xtime=strtotime($xdate);
		$date=$tpl->time_to_date($xtime,true);
		$size=FormatBytes($ligne["size"]/1024);
	
		$urijs="Loadjs('$MyPage?content-js=yes&ID={$ligne["ID"]}');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
	
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?unlink-js={$ligne["zmd5"]}')");
		$restore=imgtootltip("32-import.png",null,"Loadjs('artica-meta.menus.php?snapshot-restore-js=yes&zmd5={$ligne["zmd5"]}&uuid={$_GET["uuid"]}')");
		$cell=array();
		$cell[]="<span $style>$xdate - $date</a></span>";
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


function content_table(){


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
	$all=$tpl->javascript_parse_text("{all}");
	$file=$tpl->javascript_parse_text("{file}");
	$size=$tpl->javascript_parse_text("{size}");
	$table="snapshots";
	$database=null;
	
	$ID=$_GET["ID"];
	$q=new mysql_meta();
	$sql="SELECT zDate FROM $table WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$title=$tpl->time_to_date(strtotime($ligne["zDate"]));
	
	
	$t=time();



	$buttons=null;
	$html="

	<table class='ARTICA_SNAPSHOTS_DETAILS_TABLE' style='display: none' id='ARTICA_SNAPSHOTS_DETAILS_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_SNAPSHOTS_DETAILS_TABLE').flexigrid({
	url: '$page?content-search=yes&ID=$ID',
	dataType: 'json',
	colModel : [
	{display: '$file', name : 'zDate', width : 611, sortable : false, align: 'left'},
	{display: '$size', name : 'size', width : 150, sortable : false, align: 'right'},
	
	

	],
	$buttons
	searchitems : [
	{display: '$all', name : 'zDate'},
	

	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:200,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});


</script>";
echo $html;
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
	$sql="SELECT `content` FROM $table WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$MAIN=unserialize($ligne["content"]);

	$size_content=strlen($ligne["content"]);
	
	if(!is_array($MAIN)){json_error_show("no data ID:$ID Size:$size_content");}
	
	$searchstring=string_to_flexregex();
	$total = count($MAIN);


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";


	$c=0;
	while (list ($filename, $size) = each ($MAIN)){
		$sizeText="$size Bytes";
		
		if($size>1024){
			$sizeText=FormatBytes($size/1024);
		}
		
		if($searchstring<>null){
			if(!preg_match("#$searchstring#i", $filename."$sizeText")){continue;}
		}
		
		$c++;
		$key=md5($filename);
		$size=FormatBytes($size/1024);
		$cell=array();
		$cell[]="<span $style>$filename</a></span>";
		$cell[]="<span $style>$sizeText</a></span>";
		

		$data['rows'][] = array(
				'id' => $key,
				'cell' => $cell
		);
	}

	$data['total'] = $c;
	echo json_encode($data);
}