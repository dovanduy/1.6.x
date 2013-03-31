<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.highcharts.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["graph"])){graph();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["uris"])){uris();exit;}
	if(isset($_GET["uri-list"])){uris_list();exit;}
	
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime($_GET["day"]." 00:00:00");
	$today=date("{l} d {F}",$time);
	$title=$tpl->javascript_parse_text("$today {$_GET["value"]} {website} {$_GET["familysite"]}");
	echo "YahooWin4('980','$page?tabs=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}','$title')";	
}


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$familysite=$q->GetFamilySites($_GET["sitename"]);
	$_GET["xtime"]=strtotime("{$_GET["day"]} 00:00:00");
	
	
	if(is_numeric($_GET["xtime"])){
		$dateT=" ".date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}
	}

	
	$tablesource="dansguardian_events_".date("Ymd",$_GET["xtime"]);
	
	$array["popup"]="{status} $dateT";
	if($q->TABLE_EXISTS($tablesource)){
		$array["uris"]="{urls}";
	}
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}



	echo "
	<div id=main_config_zoomwebsiteAndUser>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zoomwebsiteAndUser').tabs();
		
		
			});
		</script>";

}

function popup(){
	$t=time();
	$page=CurrentPageName();
	echo "
	<div id='container-$t' style='width:850;height:450px'></div>
	<div id='container2-$t' style='width:850;height:450px'></div>
	<script>
		Loadjs('$page?graph=yes&container=container-$t&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}');
		Loadjs('$page?graph2=yes&container=container2-$t&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}');
	</script>
		
	";
}
function graph(){
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";
	

$q=new mysql_squid_builder();

$sql="SELECT SUM(size) as size,hour,{$_GET["field"]},familysite 
FROM $hour_table GROUP BY hour,{$_GET["field"]},familysite 
HAVING familysite='{$_GET["familysite"]}' AND {$_GET["field"]}='{$_GET["value"]}' ORDER BY hour";

if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}
$results=$q->QUERY_SQL($sql);


while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$xdata[]=$ligne["hour"]."h";
	$ydata[]=round(($ligne["size"]/1024)/1000);

	
}


$highcharts=new highcharts();
$highcharts->container=$_GET["container"];
$highcharts->xAxis=$xdata;
$highcharts->Title="{downloaded_flow} {for} {$_GET["value"]} {with} {$_GET["familysite"]}";
$highcharts->yAxisTtitle="{size}";
$highcharts->xAxisTtitle="{hours}";
$highcharts->datas=array("{size} MB"=>$ydata);
echo $highcharts->BuildChart();

	
}
function graph2(){
	$hour_table=date('Ymd',strtotime($_GET["day"]))."_hour";


	$q=new mysql_squid_builder();

	$sql="SELECT SUM(hits) as hits,hour,{$_GET["field"]},familysite
	FROM $hour_table GROUP BY hour,{$_GET["field"]},familysite
	HAVING familysite='{$_GET["familysite"]}' AND {$_GET["field"]}='{$_GET["value"]}' ORDER BY hour";

	if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}
	$results=$q->QUERY_SQL($sql);


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

		$xdata[]=$ligne["hour"]."h";
		$ydata[]=$ligne["hits"];
		


	}


	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{requests} {for} {$_GET["value"]} {with} {$_GET["familysite"]}";
	$highcharts->yAxisTtitle="{requests}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{hits}"=>$ydata);
	echo $highcharts->BuildChart();


}

function uris(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{hours}");
	$uris=$tpl->_ENGINE_parse_body("{requests}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$t=time();
	$title=$tpl->_ENGINE_parse_body("<strong>{requests} {for} {$_GET["value"]} {with} *{$_GET["familysite"]}</strong>");
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
			url: '$page?uri-list=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}',
			dataType: 'json',
			colModel : [
			{display: '$date', name : 'zDate', width :37, sortable : true, align: 'left'},
			{display: '$uris', name : 'uri', width : 701, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width : 63, sortable : true, align: 'left'},
			{display: '$hits', name : 'hits', width : 50, sortable : true, align: 'left'},
		],
	
	searchitems : [
		{display: '$uris', name : 'uri'},
		{display: '$date', name : 'zDate'},
		
		],	
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '$title',
			useRp: false,
			rp: 50,
			showTableToggleBtn: false,
			width: 920,
			height: 400,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
		});
	});
	</script>";

	echo $html;
	
	
}

function  uris_list(){
	
	$q=new mysql_squid_builder();
	$MyPage=CurrentPageName();
	$tpl=new templates();
	if($_GET["field"]=="ipaddr"){$_GET["field"]="CLIENT";}
	$_GET["xtime"]=strtotime("{$_GET["day"]} 00:00:00");
	$page=1;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
	$tablesource="dansguardian_events_".date("Ymd",$_GET["xtime"]);	
	
	$table="(SELECT DATE_FORMAT(zDate,'%Hh') as zDate,QuerySize as size,hits,uri FROM  $tablesource WHERE {$_GET["field"]}='{$_GET["value"]}' AND sitename LIKE '%{$_GET["familysite"]}') as t";

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	

	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	$line=$tpl->_ENGINE_parse_body("{line}");
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data $sql");}
	
	
	$style="style='font-size:12px'";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$style="style='font-size:12px'";
		
		if($ligne["size"]>10000000){
			$style="style='font-size:12px;font-weight:bold'";
		}
		
		if(trim($ligne["category"])==null){$ligne["category"]="<span style='color:#D70707'>{categorize_this_website}</span>";}
		$id=md5(@implode("", $ligne));
	
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=null;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);}
		$ttz=$ligne["size"];
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<span $style>{$ligne["zDate"]}</span>",
						"<span $style>{$ligne["uri"]}</span>",
						"<span $style>{$ligne["size"]}</span>",
						"<span $style>{$ligne["hits"]}</span>",
				)
		);
	
	
	}
	
	echo json_encode($data);	
}

