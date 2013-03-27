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
	
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime($_GET["day"]." 00:00:00");
	$today=date("{l} d {F}",$time);
	$title=$tpl->javascript_parse_text("$today {$_GET["value"]} {website} {$_GET["familysite"]}");
	echo "YahooWin4('880','$page?popup=yes&field={$_GET["field"]}&value=".urlencode($_GET["value"])."&familysite={$_GET["familysite"]}&day={$_GET["day"]}','$title')";	
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