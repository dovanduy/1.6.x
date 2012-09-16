<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["category-search"])){table_list();exit;}
if(isset($_POST["PerformUpdate"])){PerformUpdate();exit;}

table();
function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$q=new mysql_squid_builder();
	$update_now=$tpl->_ENGINE_parse_body("{update_now}");
	$events=$tpl->_ENGINE_parse_body("{events}");
		$t=time();
	$buttons="buttons : [
	{name: '$update_now', bclass: 'Reload', onpress : PerformUpdate$t},
	{name: '$events', bclass: 'Search', onpress : ViewEvents$t},
	
		],	";
	
	$sql="SELECT COUNT(ID) as tcount, SUM(size) as TSIZE FROM webfilters_databases_disk";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$BIGSIZE=FormatBytes(($ligne["TSIZE"]/1024));
	$SUMTABLES=$ligne["tcount"];
	$title=$tpl->_ENGINE_parse_body("$SUMTABLES {files} - $BIGSIZE");
	
	
	//webfilters_databases_disk

	$html="
	<div style='margin-left:-15px'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	</div>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?category-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$filename', name : 'filename', width : 277, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 253, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 102, sortable : true, true: 'left'},
	],
$buttons
	searchitems : [
		{display: '$category', name : 'category'},
		],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 687,
	height: 350,
	singleSelect: true
	
	});   
});

var X_PerformUpdate$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	$('#table-$t').flexReload();		
}		

function PerformUpdate$t(){
	var XHR = new XHRConnection();
	XHR.appendData('PerformUpdate','yes');
	XHR.sendAndLoad('$page', 'POST',X_PerformUpdate$t);	
}
function ViewEvents$t(){
	Loadjs('squid.update.events.php?category=ufbd-artica');
	}


</script>";

	echo $html;
	
	
	
}

function PerformUpdate(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$scheduled=$tpl->_ENGINE_parse_body("{scheduled}...");
	$q->QUERY_SQL("UPDATE webfilters_databases_disk SET `filename`='$scheduled'");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?update-ufdb-precompiled=yes");
	
}

function table_list(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();	
	
	$t=$_GET["t"];
	$search='%';
	$page=1;
	$ORDER="ORDER BY table_name";
	$table="webfilters_databases_disk";
	
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table, no such table",1);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data....");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$filename=$ligne["filename"];
		$categoryText=$q->filaname_tocat($ligne["filename"]);
		$size=FormatBytes($ligne["size"]/1024);
		$filtime=$ligne["filtime"];
		$since=distanceOfTimeInWords($filtime,time());
		$filedate=date("{l} d {F} H:i:s",$filtime);
		$filedate=$tpl->_ENGINE_parse_body($filedate);
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<span style='font-size:12px'>$filename</span>",
			"<span style='font-size:14px'>$categoryText</span><div style='font-size:11px'><i>$filedate ($since)</i></div>",
			"<span style='font-size:14px'>$size</span>",)
			);
	}
	
	
echo json_encode($data);	
		
	
}
