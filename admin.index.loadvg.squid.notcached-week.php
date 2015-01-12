<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){echo header("content-type: application/x-javascript");"alert('No privs!');";die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["list"])){items_list();exit;}
	
js();



function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{not_cached_this_week}");
	$page=CurrentPageName();
	$html="
	function Start$t(){
		YahooWin5('850','$page?popup=yes','$title')
	}
	
	Start$t();";
	
	echo $html;
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$size=$tpl->javascript_parse_text("{size}");
	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();
	$TOTALS_NOT_CACHED=intval(@file_get_contents("/usr/share/artica-postfix/ressources/logs/stats/NOT_CACHED"));
	$TOTALS_NOT_CACHED=FormatBytes($TOTALS_NOT_CACHED/1024);
	$title=$tpl->javascript_parse_text("{not_cached_this_week}::$TOTALS_NOT_CACHED");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$familysite', name : 'familysite', width : 400, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width :150, sortable : true, align: 'right'},
	{display: '$hits', name : 'familysite', width : 150, true : false, align: 'right'},
	],

	searchitems : [
	{display: '$familysite', name : 'familysite'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=title-$t style=font-size:22px>$title</span>',
	useRp: true,
	rp: 100,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

StartLogsSquidTable$t();

</script>
";
	echo $html;
}

function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$currentWeek=date("YW");
	$table="{$currentWeek}_not_cached";



	$search='%';
	
	$page=1;

	if($q->COUNT_ROWS("$table")==0){json_error_show("No datas");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$familysite=$ligne["familysite"];
		
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$data['rows'][] = array(
				'id' => "$familysite",
				'cell' => array("<span style='font-size:16px;'>$familysite</span>",
						"<span style='font-size:16px;font-weight:bold'>$size</span>",
						"<span style='font-size:16px;font-weight:bold'>$hits</span>",
						)
		);
	}


	echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}