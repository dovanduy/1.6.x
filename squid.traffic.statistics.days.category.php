<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	

	if(isset($_GET["tabs"])){tabs();exit;}
	
	
	if(isset($_GET["top10Users"])){top10Users();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}
	if(isset($_GET["tableau2"])){tableau2();exit;}
	if(isset($_GET["tableau2-datas"])){tableau2_datas();exit;}
	
	
	if(isset($_GET["top10"])){top10();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["tableau1"])){tableau1();exit;}
	if(isset($_GET["tableau1-datas"])){tableau1_datas();exit;}
	
	js();
	
	
	


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$size=950;
	$category=$_GET["category"];
	$day=$_GET["day"];
	$xtime=strtotime("{$_GET["day"]} 00:00:00");
	$title=$tpl->javascript_parse_text(date("{l} {F} d",$xtime)." {category}:$category");
	$title=$tpl->_ENGINE_parse_body("{internet_access_per_day}::$title");
	$html="YahooWin('$size','$page?tabs=yes&category=".urlencode($category)."&day={$_GET["day"]}','$title')";
	echo $html;
}
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if($_GET["day"]==date('Y-m-d')){$_GET["day"]=$q->HIER();}
	$t=time();
	$category=$_GET["category"];

	$tpl=new templates();

	$array["top10"]='{top_10} {websites}';
	$array["top10Users"]='{top_10} {members}';
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&category=".urlencode($category)."&day={$_GET["day"]}\"><span>$ligne</span></a></li>\n";
	}

	$t=time();
	echo $tpl->_ENGINE_parse_body( "
			<div id='ppp$t' style='width:97%;font-size:14px;margin-left:10px;margin-right:-15px;margin-top:-5px'>
			<ul>". implode("\n",$html)."</ul>
			</div>
			<script>
			$(document).ready(function(){
			$('#ppp$t').tabs();
				
				
});
</script>");
}

function top10Users(){
	$category=$_GET["category"];
	$page=CurrentPageName();
	$tpl=new templates();
	$xtime=strtotime("{$_GET["day"]} 00:00:00");
	$t=time();
	$html="
	<table style='width:880px'>
	<tr>
	<td><div id='graph2$t' style='width:400px;height:400px'></div></td>
	<td><div id='graph3$t' style='width:400px;height:400px'></div></td>
	</tr>
	<tr>
		<td><div id='graph4$t' style='width:400px;height:400px'></div></td>
		<td><div id='graph5$t' style='width:400px;height:400px'></div></td>
	
	</tr>
	</table>	
	<div id='table$t'></div>
	
	<script>
	AnimateDiv('$t');
	Loadjs('$page?graph3=yes&container=graph2$t&category=".urlencode($category)."&xtime=$xtime&type=client');
	Loadjs('$page?graph3=yes&container=graph3$t&category=".urlencode($category)."&xtime=$xtime&type=hostname');
	Loadjs('$page?graph3=yes&container=graph4$t&category=".urlencode($category)."&xtime=$xtime&type=MAC');
	Loadjs('$page?graph3=yes&container=graph5$t&category=".urlencode($category)."&xtime=$xtime&type=uid');
	LoadAjax('table$t','$page?tableau2=yes&container=graph$t&category=".urlencode($category)."&xtime=$xtime');
	</script>
	
	";
	echo $html;	
	
}

function top10(){
	$category=$_GET["category"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$xtime=strtotime("{$_GET["day"]} 00:00:00");	
	$t=time();
	$html="
	<div id='graph$t' style='width:880px;height:350px'></div>
	<div id='table$t'></div>
	
	<script>
		AnimateDiv('$t');
		Loadjs('$page?graph1=yes&container=graph$t&category=".urlencode($category)."&xtime=$xtime');
		LoadAjax('table$t','$page?tableau1=yes&container=graph$t&category=".urlencode($category)."&xtime=$xtime');
	</script>
	
	";
	echo $html;
}
function graph3(){
	$xtime=$_GET["xtime"];
	$tpl=new templates();
	$tablename=date("Ymd",$xtime)."_hour";
	$type=$_GET["type"];
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(size) as size, $type,category FROM $tablename
	GROUP BY $type,category HAVING category='{$_GET["category"]}' ORDER BY SUM(size) DESC LIMIT 0,10";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		$error=$tpl->javascript_parse_text($q->mysql_error);
		echo "alert('$error')";
		return;
	}	
	
	if(mysql_num_rows($results)<2){return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["$type"]]=round((($ligne["size"]/1024)/1000));
	}

	$title=$tpl->_ENGINE_parse_body();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="$type";
	$highcharts->Title="{$type} {for} {$_GET["category"]} ({size})";
	echo $highcharts->BuildChart();
}

function graph1(){
	$xtime=$_GET["xtime"];
	$tpl=new templates();
	$tablename=date("Ymd",$xtime)."_hour";
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(size) as size, familysite,category FROM $tablename
	GROUP BY familysite,category HAVING category='{$_GET["category"]}' ORDER BY SUM(size) DESC LIMIT 0,15";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		$error=$tpl->javascript_parse_text($q->mysql_error);
		echo "alert('$error')";
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["familysite"]]=round((($ligne["size"]/1024)/1000));
	}
	
	$title=$tpl->_ENGINE_parse_body();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title="{websites} {for} {$_GET["category"]} ({size})";
	echo $highcharts->BuildChart();	
	
}

function tableau2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$member=$tpl->_ENGINE_parse_body("{members}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$t=time();

	$title=$tpl->javascript_parse_text("$member {for} $category {$_GET["category"]}");
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#table-$t').flexigrid({
	url: '$page?tableau2-datas=yes&container=graph$t&category=".urlencode($_GET["category"])."&xtime={$_GET["xtime"]}',
	dataType: 'json',
	colModel : [
	{display: '$member', name : 'uid', width :134, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'client', width :134, sortable : true, align: 'left'},
	{display: '$hostname', name : 'hostname', width :134, sortable : true, align: 'left'},
	{display: '$MAC', name : 'MAC', width :134, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width :121, sortable : true, align: 'right'},
	{display: '$size', name : 'size', width :121, sortable : true, align: 'right'},


	],


	searchitems : [
	{display: '$member', name : 'uid'},
	{display: '$ipaddr', name : 'client'},
	{display: '$hostname', name : 'hostname'},
	{display: '$MAC', name : 'MAC'},
	],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 880,
	height: 350,
	singleSelect: true

});
});

</script>";

	echo $html;

}

function tableau1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$website=$tpl->_ENGINE_parse_body("{website}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$t=time();

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#table-$t').flexigrid({
	url: '$page?tableau1-datas=yes&container=graph$t&category=".urlencode($_GET["category"])."&xtime={$_GET["xtime"]}',
	dataType: 'json',
	colModel : [
	{display: '$website', name : 'sitename', width :581, sortable : true, align: 'left'},
	{display: '$hits', name : 'hits', width :121, sortable : true, align: 'center'},
	{display: '$size', name : 'size', width :121, sortable : true, align: 'center'},
	

	],


	searchitems : [
	{display: '$website', name : 'sitename'},
	{display: '$category', name : 'category'},
	],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 880,
	height: 350,
	singleSelect: true

});
});

</script>";

echo $html;

}

function tableau2_datas(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$xtime=$_GET["xtime"];
	$tablesrc=date("Ymd",$xtime)."_hour";
	$day=date("Y-m-d",$xtime);
	
	
	$search='%';
	$page=1;
	$total=0;
	
	
	if($q->COUNT_ROWS($tablesrc,"artica_events")==0){json_error_show("No data in $tablesrc");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$table="(SELECT SUM(size) as size, SUM(hits) as hits,uid,client,MAC,hostname,
	category FROM `$tablesrc` GROUP BY uid,client,MAC,hostname,category HAVING `category`='{$_GET["category"]}') as t";
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total =$ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total =$ligne["TCOUNT"];
	
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(mysql_num_rows($results)==0){
		json_error_show("No data $sql");
	}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error);
	}
	
	$textcss="<span style=\"font-size:16px\">";
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$familysite=$ligne["familysite"];
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		$uriuid="Loadjs('squid.traffic.week.user.category.php?category=".urlencode($_GET["category"])."&table=$tablesrc&field=uid&user={$ligne["uid"]}')";
		$uriuidF="<a href=\"javascript:blur();\" OnClick=\"javascript:$uriuid\"
		style='font-size:16px;text-decoration:underline'>";
		
		$uriclient="Loadjs('squid.traffic.week.user.category.php?category=".urlencode($_GET["category"])."&table=$tablesrc&field=client&user={$ligne["client"]}')";
		$uriclient="<a href=\"javascript:blur();\" OnClick=\"javascript:$uriclient\"
		style='font-size:16px;text-decoration:underline'>";		
		
		$urihostname="Loadjs('squid.traffic.week.user.category.php?category=".urlencode($_GET["category"])."&table=$tablesrc&field=hostname&user={$ligne["hostname"]}')";
		$urihostname="<a href=\"javascript:blur();\" OnClick=\"javascript:$urihostname\"
		style='font-size:16px;text-decoration:underline'>";
				
		$urihMAC="Loadjs('squid.traffic.week.user.category.php?category=".urlencode($_GET["category"])."&table=$tablesrc&field=MAC&user={$ligne["MAC"]}')";
		$urihMAC="<a href=\"javascript:blur();\" OnClick=\"javascript:$urihMAC\"
		style='font-size:16px;text-decoration:underline'>";		
		
		$data['rows'][] = array(
				'id' => $ligne['sitename'],
				'cell' => array(
						$uriuidF.$ligne["uid"]."</a></span>",
						$uriclient.$ligne["client"]."</a></span>",
						$urihostname.$ligne["hostname"]."</a></span>",
						$urihMAC.$ligne["MAC"]."</a></span>",
						$textcss.$ligne["hits"]."</span>",
						$textcss.$ligne["size"]."</span>"
	
	
			)
				);
	}
	
	
	echo json_encode($data);	
	
}

function tableau1_datas(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$xtime=$_GET["xtime"];
	$tablesrc=date("Ymd",$xtime)."_hour";
	$day=date("Y-m-d",$xtime);
	
	
	$search='%';
	$page=1;
	$total=0;
	
	
	if($q->COUNT_ROWS($tablesrc,"artica_events")==0){json_error_show("No data in $tablesrc");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$table="(SELECT SUM(size) as size, SUM(hits) as hits,sitename,familysite,
	category FROM `$tablesrc` GROUP BY sitename,familysite,category HAVING `category`='{$_GET["category"]}') as t";
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total =$ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total =$ligne["TCOUNT"];
	
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(mysql_num_rows($results)==0){
		json_error_show("No data $sql");
	}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
	json_error_show($q->mysql_error);
	}
	
	$textcss="<span style='font-size:16px'>";
	
	while ($ligne = mysql_fetch_assoc($results)) {

		$familysite=$ligne["familysite"];
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		$uriweb="Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite=$familysite&day=$day')";
		$uriwebHEF="<a href=\"javascript:blur();\" OnClick=\"javascript:$uriweb\"
		style='font-size:16px;text-decoration:underline'>";
	$data['rows'][] = array(
			'id' => $ligne['sitename'],
			'cell' => array(
					$textcss.$uriwebHEF.$ligne["sitename"]."</a></span>",
					$textcss.$ligne["hits"]."</span>",
					$textcss.$ligne["size"]."</span>"
	
	
			)
	);
	}
	
	
	echo json_encode($data);	
	
}

