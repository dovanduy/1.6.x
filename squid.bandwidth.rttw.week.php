<?php
if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__)."/framework/frame.class.inc");
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
	include_once(dirname(__FILE__)."/framework/class.settings.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql-meta.inc");
}}

include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.highcharts.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');


if(isset($_GET["list"])){showlist();exit;}
table();
	
function table(){
$tpl=new templates();	
$page=CurrentPageName();

$zdate=$tpl->javascript_parse_text("{time}");
$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
$mac=$tpl->javascript_parse_text("{MAC}");
$familysite=$tpl->javascript_parse_text("{familysite}");
$uid=$tpl->javascript_parse_text("{uid}");
$size=$tpl->javascript_parse_text("{size}");
// ipaddr        | familysite            | servername                                | uid               | MAC               | size
$t=time();	
$servername=null;
$uuid=urlencode($_GET["uuid"]);
if($_GET["uuid"]<>null){
	$q=new mysql_meta();
	$servername=" ".$q->uuid_to_host($_GET["uuid"])." - ".$q->uuid_to_tag($_GET["uuid"]);
}
$title=$tpl->javascript_parse_text("{downloaded_size}::{this_week}$servername");
$html="
<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
<script>
function StartLogsSquidTable$t(){
	
	$('#flexRT$t').flexigrid({
		url: '$page?list=yes&uuid=$uuid',
		dataType: 'json',
		colModel : [
			{display: '$uid', name : 'uid', width : 141, sortable : true, align: 'left'},
			{display: '$ipaddr', name : 'ipaddr', width :130, sortable : true, align: 'left'},
			{display: '$mac', name : 'MAC', width : 134, sortable : true, align: 'left'},
			{display: '$familysite', name : 'familysite', width : 504, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width : 142, sortable : true, align: 'right'},
			],
	
		searchitems : [
			{display: '$ipaddr', name : 'ipaddr'},
			{display: '$familysite', name : 'familysite'},
			{display: '$uid', name : 'uid'},
			{display: '$mac', name : 'mac'},
			],
		sortname: 'size',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
		
		});   

}

StartLogsSquidTable$t();
</script>			
";
echo $html;	
}


function showlist(){
	$page=1;
	$q=new mysql_squid_builder();
	$tablesrc="WEEK_RTTH";
	
	if($_GET["uuid"]<>null){
		$q=new mysql_meta();
		$tablesrc="{$_GET["uuid"]}_WEEK_RTTH";
	}
	
	
	$table="(SELECT uid,mac,SUM(size) as size,ipaddr,familysite FROM `$tablesrc` GROUP BY uid,mac,ipaddr,familysite) as t";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
	
		$data = array();
		$data['page'] = 1;
		$data['total'] = $total;
		$data['rows'] = array();
	
		//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ipaddr=$ligne["ipaddr"];
			$mac=$ligne["mac"];
			$familysite=$ligne["familysite"];
			$uid=$ligne["uid"];
			$size=FormatBytes($ligne["size"]/1024);
			$macenc=urlencode($mac);
			$category=$ligne["category"];
			if($category<>null){$category=" <i>($category)</i>";}
			$macurl="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macenc',true);\"
			style='font-size:16px;text-decoration:underline'>";
			
		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array("<span style='font-size:16px'>$uid</span>",
				"<span style='font-size:16px'>$ipaddr</span>",
				"<span style='font-size:16px'>$macurl$mac</a></span>",
				"<span style='font-size:16px'>$familysite$category</span>",
				"<span style='font-size:16px'>$size</span>" )
		);
	}
	
	
	echo json_encode($data);	
}
