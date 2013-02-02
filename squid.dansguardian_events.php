<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.artica.graphs.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
		
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if($_GET["items"]){items();exit;}
	
	js();
	
function js(){
	$tpl=new templates();
	$table=$_GET["table"];
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$time=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($table);
	$dateT=date("Y {l} {F} d",$time);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} Y",$time);}	
	$title[]=$dateT;
	if($_GET["field"]<>null){$title[]=$_GET["field"];}
	if($_GET["value"]<>null){$title[]=$_GET["value"];}
	if($_GET["sitename"]<>null){$title[]=$_GET["sitename"];}

	$finaltitle=$tpl->_ENGINE_parse_body(@implode("&raquo;", $title));
	$uri="popup=yes&table=$table&field={$_GET["field"]}&value={$_GET["value"]}&sitename={$_GET["sitename"]}";
	echo "YahooWinBrowse('950','$page?$uri','$finaltitle');";
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$uri=$tpl->javascript_parse_text("{url}");
	$time=$tpl->javascript_parse_text("{time}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$buttons="
	buttons : [
	
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&table={$_GET["table"]}&field={$_GET["field"]}&value={$_GET["value"]}&sitename={$_GET["sitename"]}',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'zDate', width :120, sortable : true, align: 'left'},
		{display: '$uri', name : 'uri', width :459, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :107, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :88, sortable : true, align: 'left'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$uri', name : 'uri'},
		
		

	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\"></span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	//s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
}


</script>";
	
	echo $html;
	
	
	
}	

	
function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	$search='%';
	$table=$_GET["table"];
	
	$page=1;
	$FORCE_FILTER=null;
	if($_GET["field"]<>null){
		$FORCE_FILTER=" AND `{$_GET["field"]}`='{$_GET["value"]}'";
	}
	
	if($_GET["sitename"]<>null){
		$FORCE_FILTER=$FORCE_FILTER."  AND `sitename`='{$_GET["sitename"]}'";
	}
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){
		json_error_show("$sql<hr>No row",1);
	}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	
	$date=strtotime($ligne["zDate"]);
	$Hour=date("H:i");
	
	//familysite 	size 	hits
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.website-zoom.php?js=yes&sitename={$ligne["sitename"]}&xtime={$_GET["xtime"]}');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	
	$urijs="s_PopUpFull('{$ligne["uri"]}','1024','900');";
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$ligne["familysite"]=$q->GetFamilySites($ligne["sitename"]);
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:12px;color:$color'>$Hour</a></span>",
			"<span style='font-size:12px;color:$color'><a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" style='text-decoration:underline'>{$ligne["uri"]}</a></span>",
			"<span style='font-size:12px;color:$color'>{$ligne["size"]}</span>",
			"<span style='font-size:12px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);		
	
	
}