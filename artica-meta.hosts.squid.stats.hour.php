<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
include_once('ressources/class.meta_uuid.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}
if(isset($_GET["list"])){showlist();exit;}

popup();


function suffix(){
	
	return "&uuid=".$_GET["uuid"];
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$familysite=$tpl->javascript_parse_text("{sitename}");
	$uid=$tpl->javascript_parse_text("{members}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	
	$t=time();
	$title=$tpl->javascript_parse_text("{this_hour}:".$q->uuid_to_host($_GET["uuid"]));
	$suffix=suffix();
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes$suffix',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'zDate', width : 61, sortable : true, align: 'left'},
	{display: '$uid', name : 'uid', width :112, sortable : true, align: 'left'},
	{display: '$mac', name : 'mac', width :142, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width :142, sortable : true, align: 'left'},
	{display: '$familysite', name : 'sitename', width : 169, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width : 66, sortable : true, align: 'right'},
	{display: '$size', name : 'size', width : 142, sortable : true, align: 'right'},
	],

	searchitems : [
	{display: '$uid', name : 'uid'},
	{display: '$mac', name : 'mac'},
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$familysite', name : 'sitename'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 100,
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
	$q=new mysql_uuid_meta($_GET["uuid"]);
	
	$table="squid_hourly_".date("YmdH");

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}

	if(mysql_num_rows($results)==0){
		if(!$q->TABLE_EXISTS($table)){$add=" no table!";}
		json_error_show("no data $add<br><i>$sql</i>",2);
	}

	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();

	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}

	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne["uid"];
		$familysite=$ligne["sitename"];
		$sizeText="{$ligne["size"]} Bytes";
		$time=$ligne["zDate"];
		$mac=$ligne["mac"];
		$ipaddr=$ligne["ipaddr"];
		$time=strtotime($time);
		$xtime=date("H:i:00",$time);

		if($ligne["size"]>1024){$sizeText=FormatBytes($ligne["size"]/1024);}
		$hits=FormatNumber($ligne["hits"]);

			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:14px'>$xtime</span>",
						"<span style='font-size:14px'>$uid</span>",
						"<span style='font-size:14px'>$mac</span>",
						"<span style='font-size:14px'>$ipaddr</span>",
						"<span style='font-size:14px'>$familysite</a></span>",
						"<span style='font-size:14px'>$hits</span>",
						"<span style='font-size:14px'>$sizeText</span>"
						
						)
		);
	}


	echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}