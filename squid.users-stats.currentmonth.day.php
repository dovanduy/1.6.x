<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}



if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["flow-day"])){flow_day();exit;}
if(isset($_GET["flow-month-graph1"])){flow_month_graph1();exit;}
if(isset($_GET["flow-month-graph2"])){flow_month_graph2();exit;}
if(isset($_GET["websites"])){websites_table();exit;}
if(isset($_GET["search-websites"])){websites_search();exit;}

if(isset($_GET["categories"])){categories_table();exit;}
if(isset($_GET["search-categories"])){categories_search();exit;}

if(isset($_GET["days"])){days_table();exit;}
if(isset($_GET["search-days"])){days_search();exit;}





js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$suffix=suffix();
	$t=time();
	
	$q=new mysql_squid_builder();
	$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($_GET["time"]));
	$title=$tpl->javascript_parse_text("$time_text::{$_GET["field"]}::{$_GET["value"]}");
	$page=CurrentPageName();
	$html="
	function Start$t(){
	YahooWin3('1019','$page?tabs=yes$suffix','$title')
}

Start$t();";

	echo $html;


}

function suffix(){
	if(isset($_GET["zdate"])){$_GET["time"]=strtotime($_GET["zdate"]);}
	return "&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&time={$_GET["time"]}";
}

function tabs(){
	$sock=new sockets();
	$fontsize=16;
	$tpl=new templates();
	$page=CurrentPageName();

	$md5=md5(serialize($_GET));

	$date=date("Ym",$_GET["time"]);
	$table="{$date}_maccess";


	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){
		echo FATAL_ERROR_SHOW_128("{no_table_see_support}");
		return;
	}

	$suffix=suffix();
	$array["flow-day"]='{flow_by_hour}';
	$array["websites"]='{chronology}';

	
	




	while (list ($num, $ligne) = each ($array) ){

		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.accesslogs.params.php?parameters=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}

		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php?TaskType=54\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes$suffix\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "squid_users_stats_$md5",990)."";

}

function flow_day(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	
	$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($_GET["time"]));
	
	$date=date("Ym",$_GET["time"]);
	$zdate=date("Y-m-d",$_GET["time"]);
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,{$_GET["field"]},`hour` as hour,zDate 
	FROM {$date}_maccess GROUP BY {$_GET["field"]},zDate,`hour` 
	HAVING {$_GET["field"]}='{$_GET["value"]}' AND `zDate`='$zdate' ORDER BY `hour`";
	$time=time();
	
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	echo $tpl->_ENGINE_parse_body("<div style='font-size:22px;margin-bottom:20px'>$time_text</div>");
	
	if(!$q->ok){
		echo FATAL_ERROR_SHOW_128($q->mysql_error_html()."<hr>$sql");
		return;
		
	}
	
	
	if(mysql_num_rows($results)<2){
		echo FATAL_ERROR_SHOW_128("{request_is_less_2}");
		return;
		
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$value1[$ligne["hour"]]=round($ligne["size"]/1024);
		$value2[$ligne["hour"]]=$ligne["hits"];
	}
	
	$value1_enc=urlencode(base64_encode(serialize($value1)));
	$value2_enc=urlencode(base64_encode(serialize($value2)));
	
	$f1[]="
	
	<div style='width:955px;height:340px' id='$time-2'></div>";
	$f1[]="<div style='width:955px;height:340px' id='$time-3'></div>";
	
	
	$f2[]="function FDeux$time(){
		LoadjsSilent('$page?container=$time-2&flow-month-graph1=yes&serialize=$value1_enc',false);
	}
	setTimeout(\"FDeux$time()\",500);";
	

	$f2[]="function F3$time(){
	LoadjsSilent('$page?container=$time-3&flow-month-graph2=yes&serialize=$value2_enc',false);
	}
	setTimeout(\"F3$time()\",500);";
	
	
	echo @implode("\n", $f1);
	echo "<script>".@implode("\n", $f2)."</script>";
}
function flow_month_graph1(){


	$data=unserialize(base64_decode($_GET["serialize"]));
	
	
	
	while (list ($day, $size) = each ($data) ){
		
		$size=$size/1024;
		$xdata[]=$day;
		$ydata[]=$size;
		
	}


	$title="{downloaded_flow_this_hour} (MB)";
	$timetext="{days}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	//$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function flow_month_graph2(){
	$data=unserialize(base64_decode($_GET["serialize"]));
	
	
	
	while (list ($day, $size) = each ($data) ){
	
		
		$xdata[]=$day;
		$ydata[]=$size;
	
	}
	
	
	$title="{requests_number_per_hour}";
	$timetext="{days}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	//$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle="{hour}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function websites_table(){

	
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$hour=$tpl->javascript_parse_text("{hour}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$category=$tpl->javascript_parse_text("{category}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	
	$q=new mysql_squid_builder();
	$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($_GET["time"]));
	

	
	
	$title=$tpl->javascript_parse_text("$time_text:: {websites}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
	$suffixMD=md5($suffix);
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;

	//	$q=new mysql();
	//$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));

	$html="
	<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
	url: '$page?search-websites=yes&$suffix',
	dataType: 'json',
	colModel : [
	{display: '$hour', name : 'hour', width : 45, sortable : true, align: 'center'},
	{display: '$familysite', name : 'familysite', width : 407, sortable : true, align: 'left'},
	{display: '$category', name : 'category', width : 176, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
	{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$familysite', name : 'familysite'},
	{display: '$category', name : 'category'},
	{display: '$hour', name : 'hour'},
	],
	sortname: 'hour',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});
</script>";
echo $html;
}

function websites_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$date=date("Ym",$_GET["time"]);
	
	$zdate=date("Y-m-d",$_GET["time"]);
	$familysite=",familysite";
	if($_GET["field"]=="familysite"){$familysite=null;}
	
	
	$table="(SELECT hits,size{$familysite},{$_GET["field"]},`hour`,category
	FROM {$date}_maccess WHERE {$_GET["field"]}='{$_GET["value"]}' AND zDate='$zdate') as t";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");


	while ($ligne = mysql_fetch_assoc($results)) {
	$LOGSWHY=array();

	$uid=$ligne["uid"];
	$MAC=$ligne["MAC"];
	$ipaddr=$ligne["ipaddr"];
	$size=FormatBytes($ligne["size"]/1024);
	$hits=FormatNumber($ligne["hits"]);
	$familysite=$ligne["familysite"];
	$hour=$ligne["hour"];
	if(strlen($hour)==1){$hour="0{$hour}";}
	$category=$ligne["category"];
		
		$MAC_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=MAC&value=".urlencode($MAC)."')";
		$UID_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=uid&value=".urlencode($uid)."')";
		$IPADDR_FILTER="Loadjs('squid.users-stats.currentmonth.php?field=ipaddr&value=".urlencode($ipaddr)."')";
	

		$MAC_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$MAC_FILTER\" $styleHref>";


		$UID_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$UID_FILTER\" $styleHref>";

		$IPADDR_FILTERlink="<a href=\"javascript:blur();\"
		OnClick=\"javascript:$IPADDR_FILTER\" $styleHref>";


				$cell=array();
				$cell[]="<span $style>$hour</a></span>";
				$cell[]="<span $style>$familysite</a></span>";
				$cell[]="<span $style>$category</a></span>";
				$cell[]="<span $style>$size</a></span>";
				$cell[]="<span $style>$hits</a></span>";


				$data['rows'][] = array(
						'id' => $ligne['zmd5'],
				'cell' => $cell
				);
	}


	echo json_encode($data);
}
function categories_table(){


	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$categories=$tpl->javascript_parse_text("{categories}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$type=$tpl->javascript_parse_text("{type}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$size=$tpl->javascript_parse_text("{size}");
	$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$tag=$tpl->javascript_parse_text("{tag}");
	$synchronize=$tpl->javascript_parse_text("{synchronize}");
	$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$date=$tpl->javascript_parse_text("{date}");
	$title=$tpl->javascript_parse_text("{this_month}:: {categories}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
	$suffixMD=md5(serialize($_GET));
	$buttons="
	buttons : [
	{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
	{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
	],";
	$buttons=null;

	//	$q=new mysql();
	//$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));

	$html="
	<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
	url: '$page?search-categories=yes&$suffix',
	dataType: 'json',
	colModel : [
	{display: '$categories', name : 'category', width : 641, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
	{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$categories', name : 'category'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});
</script>";
	echo $html;
}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$date=date("Ym",$_GET["time"]);



	$table="(SELECT SUM(hits) as hits,SUM(size) as size,category,{$_GET["field"]}
	FROM {$date}_maccess GROUP BY category,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
	$total = $ligne["tcount"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}

	$suffix=suffix();
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="18";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");


	while ($ligne = mysql_fetch_assoc($results)) {
	$LOGSWHY=array();

	$uid=$ligne["uid"];
	$MAC=$ligne["MAC"];
	$ipaddr=$ligne["ipaddr"];
	$size=FormatBytes($ligne["size"]/1024);
	$hits=FormatNumber($ligne["hits"]);
	$category=$ligne["category"];

	$categoryenc=urlencode($category);
	$FILTER="Loadjs('squid.users-stats.currentmonth.category.php?category=$categoryenc&$suffix')";


	$FILTERlink="<a href=\"javascript:blur();\"
	OnClick=\"javascript:$FILTER\" $styleHref>";


	
	if($category==null){$category="Unknown";}

	$cell=array();
	$cell[]="<span $style>$FILTERlink$category</a></span>";
	$cell[]="<span $style>$size</a></span>";
	$cell[]="<span $style>$hits</a></span>";


	$data['rows'][] = array(
	'id' => $ligne['zmd5'],
	'cell' => $cell
	);
	}


	echo json_encode($data);
	}
	
function days_table(){
	
	
		$suffix=suffix();
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
		$days=$tpl->javascript_parse_text("{days}");
		$MAC=$tpl->javascript_parse_text("{MAC}");
		$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
		$load=$tpl->javascript_parse_text("{load}");
		$version=$tpl->javascript_parse_text("{version}");
		$filename=$tpl->javascript_parse_text("{filename}");
		$status=$tpl->javascript_parse_text("{status}");
		$events=$tpl->javascript_parse_text("{events}");
		$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
		$policies=$tpl->javascript_parse_text("{policies}");
		$orders=$tpl->javascript_parse_text("{orders}");
		$type=$tpl->javascript_parse_text("{type}");
		$hits=$tpl->javascript_parse_text("{hits}");
		$size=$tpl->javascript_parse_text("{size}");
		$link_all_hosts_ask=$tpl->javascript_parse_text("{link_all_hosts_ask}");
		$policies=$tpl->javascript_parse_text("{policies}");
		$tag=$tpl->javascript_parse_text("{tag}");
		$synchronize=$tpl->javascript_parse_text("{synchronize}");
		$synchronize_policies_explain=$tpl->javascript_parse_text("{synchronize_policies_explain}");
		$t=time();
		$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
		$categorysize=387;
		$date=$tpl->javascript_parse_text("{date}");
		$title=$tpl->javascript_parse_text("{this_month}:: {days}:: {$_GET["field"]}:: &laquo;{$_GET["value"]}&raquo;");
		$suffixMD=md5(serialize($_GET));
		$buttons="
		buttons : [
		{name: '$link_host', bclass: 'add', onpress : LinkHosts$t},
		{name: '$synchronize', bclass: 'ScanNet', onpress : Orders$t},
		],";
		$buttons=null;
	
		//	$q=new mysql();
		//$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM backuped_logs","artica_events"));
	
		$html="
		<table class='SQUID_USERS_PROFILE_TABLE$suffixMD' style='display: none' id='SQUID_USERS_PROFILE_TABLE$suffixMD' style='width:1200px'></table>
		<script>
		$(document).ready(function(){
		$('#SQUID_USERS_PROFILE_TABLE$suffixMD').flexigrid({
		url: '$page?search-days=yes&$suffix',
		dataType: 'json',
		colModel : [
		{display: '$days', name : 'zDate', width : 641, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 124, sortable : true, align: 'right'},
		{display: '$hits', name : 'hits', width : 124, sortable : true, align: 'right'},
		],
		$buttons
		searchitems : [
		{display: '$days', name : 'zDate'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:22px>$title</strong>',
		useRp: true,
		rpOptions: [10, 20, 30, 50,100,200],
		rp:50,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	</script>";
		echo $html;
	}	

	
function days_search(){
		$MyPage=CurrentPageName();
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$date=date("Ym",$_GET["time"]);
	
	
	
		$table="(SELECT SUM(hits) as hits,SUM(size) as size,zDate,{$_GET["field"]}
		FROM {$date}_maccess GROUP BY zDate,{$_GET["field"]} HAVING {$_GET["field"]}='{$_GET["value"]}') as t";
		$searchstring=string_to_flexquery();
		$page=1;
	
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		if(!is_numeric($rp)){$rp=50;}
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
		$results = $q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
		$suffix=suffix();
		if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		$fontsize="18";
		$style=" style='font-size:{$fontsize}px'";
		$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
		$free_text=$tpl->javascript_parse_text("{free}");
		$computers=$tpl->javascript_parse_text("{computers}");
		$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
		$orders_text=$tpl->javascript_parse_text("{orders}");
		$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$LOGSWHY=array();
	
			$uid=$ligne["uid"];
			$MAC=$ligne["MAC"];
			$ipaddr=$ligne["ipaddr"];
			$size=FormatBytes($ligne["size"]/1024);
			$hits=FormatNumber($ligne["hits"]);
			$zDate=$ligne["zDate"];
	
			$zDateEnc=urlencode($zDate);
			$FILTER="Loadjs('squid.users-stats.currentmonth.day.php?zdate=$zDateEnc&$suffix')";
	
	
			$FILTERlink="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$FILTER\" $styleHref>";
	
			$time=strtotime($zDate);
			$time_text=$tpl->_ENGINE_parse_body($q->time_to_date($time));
	
			
	
			$cell=array();
			$cell[]="<span $style>$FILTERlink$zDate&nbsp;-&nbsp;$time_text</a></span>";
			$cell[]="<span $style>$size</a></span>";
			$cell[]="<span $style>$hits</a></span>";
	
	
			$data['rows'][] = array(
					'id' => $ligne['zmd5'],
					'cell' => $cell
			);
		}
	
	
		echo json_encode($data);
	}
	
	
	
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
