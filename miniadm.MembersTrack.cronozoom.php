<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.report.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["items"])){items();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(!is_numeric($t)){$t=time();}
	$rp=new squid_report($ID);
	$sitename=$_GET["sitename"];
	$title=$rp->report."&nbsp;&raquo;Zoom";
	echo "YahooWin('537','$page?table=yes&ID=$ID&t=$t','$title')";
	
	
}

function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=520;
	$uid=$_GET["uid"];
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";		
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
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$date=$tpl->javascript_parse_text("{date}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	
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
	url: '$page?items=yes&t=$t&ID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :101, sortable : true, align: 'left'},		
		{display: '$size', name : 'size', width :101, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :101, sortable : true, align: 'left'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$date', name : 'zDate'},
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
	s_PopUpFull('http://proxy-appliance.org/index.php?cID=332','1024','900');
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
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";		
	
	$search='%';
	
	$ID=$_GET["ID"];
	$tablename="WebTrackMem{$ID}";
	$table="(SELECT SUM(hits) as hits,SUM(size) as size, zDate FROM $tablename GROUP BY zDate) as t";	
	$page=1;
	$FORCE_FILTER=null;
	
	
	if(!$q->TABLE_EXISTS($tablename, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($tablename, $database)==0){json_error_show("No rules");}

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
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$rp=new squid_report($ID);
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	
	
	
	//familysite 	size 	hits
	
	
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.traffic.statistics.hours.php?filterby=$rp->userfield&filterdata=". urlencode($rp->userdata)."&day={$ligne["zDate"]}');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);

	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["zDate"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["size"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);		
}