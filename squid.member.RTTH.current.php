<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.squid.builder.php');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){echo header("content-type: application/x-javascript");"alert('No privs!');";die();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["list"])){showlist();exit;}
if(isset($_GET["refresh"])){refresh();exit;}
js();


function suffix(){
	
	return "&field=".urlencode($_GET["field"])."&value=".urlencode($_GET["value"]);
	
}

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{this_hour}:{$_GET["field"]} {$_GET["value"]}");
	$page=CurrentPageName();
	$suffix=suffix();
	$html="
	function Start$t(){
	YahooWinS('850','$page?popup=yes$suffix','$title')
}

Start$t();";

	echo $html;


}
 
function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$familysite=$tpl->javascript_parse_text("{sitename}");
	$uid=$tpl->javascript_parse_text("{{$_GET["field"]}}");
	$size=$tpl->javascript_parse_text("{size}");


	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();
	$title=$tpl->javascript_parse_text("{this_hour}:{$_GET["field"]} {$_GET["value"]}");
	$suffix=suffix();
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes$suffix',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'xtime', width : 159, sortable : true, align: 'left'},
	{display: '$uid', name : '{$_GET["field"]}', width :142, sortable : true, align: 'left'},
	{display: '$familysite', name : 'sitename', width : 169, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 142, sortable : true, align: 'right'},
	],

	searchitems : [
	{display: '$uid', name : '{$_GET["field"]}'},
	{display: '$familysite', name : 'sitename'},
	],
	sortname: 'xtime',
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
	$q=new mysql_squid_builder();
	$tablesrc="RTTH_".date("YmdH");
	

	$table="( SELECT * FROM `$tablesrc` WHERE `{$_GET["field"]}`='{$_GET["value"]}') as t";
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
		if(!$q->TABLE_EXISTS($tablesrc)){$add=" no table!";}
		json_error_show("no data $add<br><i>$sql</i>",2);
	}

	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();

	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}

	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne[$_GET["field"]];
		$familysite=$ligne["sitename"];
		$sizeText="{$ligne["size"]} Bytes";
		$time=date("H:i:s",$ligne["xtime"]);
		
		
		if($ligne["size"]>1024){$sizeText=FormatBytes($ligne["size"]/1024);}
		

			
		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array("<span style='font-size:16px'>$time</span>",
		"<span style='font-size:16px'>$uid</span>",
		"<span style='font-size:16px'>$familysite</a></span>",
		"<span style='font-size:16px'>$sizeText</span>" )
		);
}


echo json_encode($data);
}