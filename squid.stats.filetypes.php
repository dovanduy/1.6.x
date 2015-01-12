<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.squid.builder.php');

$user=new usersMenus();
if(!$user->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	exit;
		
}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["today"])){today_filetypes();exit;}
if(isset($_GET["today-list"])){today_filetypes_list();exit;}
if(isset($_GET["js-mime"])){js_mime();exit;}
if(isset($_GET["mime-table"])){mime_table();exit;}
if(isset($_GET["mime-list"])){mime_list();exit;}
js();


function js(){
	header("Content-type: text/javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{file_types}");
	$html="YahooWin3('1024','$page?tabs=yes','$title');";
	echo $html;
}
function js_mime(){
	header("Content-type: text/javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$mime_type=$_GET["mime_type"];
	$mime_typeenc=urlencode($mime_type);
	$title=$tpl->javascript_parse_text("{file_type}::$mime_type");
	$html="YahooWin4('990','$page?mime-table=yes&mime_type=$mime_typeenc','$title');";
	echo $html;
}

function tabs(){
	$tpl=new templates();
	$array["today"]='{file_types}';
	$page=CurrentPageName();
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_stats_filetypes");	
}


function today_filetypes(){
	$tpl=new templates();
	$page=CurrentPageName();

	$filetype=$tpl->javascript_parse_text("{file_type}");
	$size=$tpl->javascript_parse_text("{size}");


	$servername=null;
	$t=time();
	$title=$tpl->javascript_parse_text("{downloaded_size}::{this_day}::{file_types}");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?today-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$filetype', name : 'mime_type', width : 747, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 142, sortable : true, align: 'right'},
	],

	searchitems : [
	{display: '$filetype', name : 'mime_type'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
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

function today_filetypes_list(){
	$page=1;
	$q=new mysql_squid_builder();
	$tablesrc="MIME_RTT";
	$MyPage=CurrentPageName();
	

	$table="(SELECT SUM(size) as size,mime_type FROM MIME_RTT GROUP BY mime_type ORDER BY size DESC ) as t";
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

	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		if(!$q->TABLE_EXISTS($tablesrc)){$add=" no table!";}
		json_error_show("no data $add <i>$sql</i>",3);
	}

	$data = array();
	$data['page'] = 1;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();

	while ($ligne = mysql_fetch_assoc($results)) {
		$mime_type=$ligne["mime_type"];
		$size=FormatBytes($ligne["size"]/1024);

		$mime_typeenc=urlencode($mime_type);
		$loupe_uid="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?js-mime=yes&mime_type=$mime_typeenc')\">
		<img src='img/loupe-32.png' style='float:right'>
		</a>";
			
		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array("$loupe_uid<span style='font-size:16px'>$mime_type</span>",
		"<span style='font-size:16px'>$size</span>" )
		);
}


echo json_encode($data);
}


function mime_table(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$uid=$tpl->javascript_parse_text("{uid}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$size=$tpl->javascript_parse_text("{size}");
	$mime_type=$_GET["mime_type"];
	$mime_typeenc=urlencode($mime_type);
	
	$servername=null;
	$t=time();
	$title=$tpl->javascript_parse_text("{downloaded_size}::{this_day}::$mime_type");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){
	
	$('#flexRT$t').flexigrid({
	url: '$page?mime-list=$mime_typeenc',
	dataType: 'json',
	colModel : [
	{display: '$uid', name : 'uid', width : 394, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width : 130, sortable : true, align: 'left'},
	{display: '$mac', name : 'MAC', width : 184, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 150, sortable : true, align: 'right'},
	],
	
	searchitems : [
	{display: '$uid', name : 'uid'},
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$mac', name : 'mac'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
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

function mime_list(){
	$page=1;
	$q=new mysql_squid_builder();
	$tablesrc="MIME_RTT";
	$MyPage=CurrentPageName();
	$mime_type=$_GET["mime-list"];

	$table="(SELECT SUM(size) as size,uid,ipaddr,MAC,mime_type FROM MIME_RTT 
			GROUP BY mime_type,uid,ipaddr,MAC HAVING mime_type='$mime_type') as t";
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

	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		if(!$q->TABLE_EXISTS($tablesrc)){$add=" no table!";}
		json_error_show("no data $add <i>$sql</i>",3);
	}

	$data = array();
	$data['page'] = 1;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();

	while ($ligne = mysql_fetch_assoc($results)) {
		$uid=$ligne["uid"];
		$ipaddr=$ligne["ipaddr"];
		$mac=$ligne["MAC"];
		$size=FormatBytes($ligne["size"]/1024);

		$mime_typeenc=urlencode($mime_type);
		$loupe_uid="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?js-mime=yes&mime_type=$mime_typeenc')\">
		<img src='img/loupe-32.png' style='float:right'>
		</a>";
			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array(
				"<span style='font-size:18px'>$uid</span>",
				"<span style='font-size:18px'>$ipaddr</span>",
				"<span style='font-size:18px'>$mac</span>",
				"<span style='font-size:18px'>$size</span>" 
						
						
						)
		);
	}


	echo json_encode($data);
}
