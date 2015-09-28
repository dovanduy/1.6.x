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
	}}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.html.pages.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.highcharts.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
if(isset($_GET["list"])){showlist();exit;}
	table();	
	
function table(){
	$tpl=new templates();
	$page=CurrentPageName();

	
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$category=$tpl->javascript_parse_text("{category}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	
	$t=time();
	
	$title=$tpl->javascript_parse_text("{this_hour}");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$familysite', name : 'familysite', width : 237, sortable : true, align: 'left'},
	{display: '$category', name : 'category', width : 165, sortable : true, align: 'left'},
	{display: '$uid', name : 'uid', width : 151, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width : 125, sortable : true, align: 'left'},
	{display: '$mac', name : 'MAC', width : 150, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 142, sortable : true, align: 'right'},
	{display: '$size', name : 'size', width : 142, sortable : true, align: 'right'},
	],

	searchitems : [
		
	{display: '$familysite', name : 'familysite'},
	{display: '$category', name : 'category'},
	{display: '$uid', name : 'uid'},
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$mac', name : 'MAC'},
		
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:20px>$title</span>',
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
	$table="HOUR_RTT";

	if($_GET["uuid"]<>null){
		$q=new mysql_meta();
		$table="{$_GET["uuid"]}_CACHED_SITES";
	}



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
		$familysite=$ligne["familysite"];
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		
		$macenc=urlencode($ligne["MAC"]);
		$macuri="<a href=\"javascript:blur();\" 
		Onclick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macenc',true);\"
		style='text-decoration:underline;font-size:18px'>";

			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array(
						"<span style='font-size:18px'>$familysite</span>",
						"<span style='font-size:18px'>{$ligne["category"]}</span>",
						"<span style='font-size:18px'>{$ligne["uid"]}</span>",
						"<span style='font-size:18px'>{$ligne["ipaddr"]}</span>",
						"<span style='font-size:18px'>$macuri{$ligne["MAC"]}</a></span>",
						"<span style='font-size:18px'>$hits</span>",
						"<span style='font-size:18px'>$size</span>"
				)
		);

	}


	echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}