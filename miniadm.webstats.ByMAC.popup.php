<?php
session_start();
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
//ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}
if(isset($_GET["rqsize"])){rqsize_page();exit;}
if(isset($_GET["rqsize-graĥs"])){rqsize_graphs();exit;}
if(isset($_GET["rqsize-table"])){rqsize_table();exit;}

if(isset($_GET["www"])){www_page();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["www-search"])){www_search();exit;}



$users=new usersMenus();
Privileges_members_ownstats();
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$xtime=$_GET["xtime"];
	header("content-type: application/x-javascript");
	$dateT=date("{l} {F} d",$xtime);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);}	
	$title=$tpl->javascript_parse_text("{MAC}::{$_GET["MAC"]}::$dateT");
	echo "YahooWin6('990','$page?tabs=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}','$title')";

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{requests} & {size}"]="$page?rqsize=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	$array["{visited_websites}"]="$page?www=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);	
	
}
function CheckRights(){
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("<H1>Oups!</H1>");}
}

function rqsize_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["MAC"]=urlencode($_GET["MAC"]);
	
	$array["{graphs}"]="$page?rqsize-graĥs=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	$array["{values}"]="$page?rqsize-table=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function rqsize_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,`hour`,MAC FROM $hourtable GROUP BY MAC,`hour` HAVING MAC='{$_GET["MAC"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);	

	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=$ligne["hour"]."h";
		$hits=FormatNumber($ligne["hits"]);
		
		
		
		$link=$boot->trswitch($jshost);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-time'></i> $date</a></td>
		<td $link><i class='icon-info-sign'></i> $size</td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";	
	

	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{hour}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
}


function rqsize_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["MAC"]=urlencode($_GET["MAC"]);
	$html="
	<div id='$t-1' style='width:900px;height:450px'></div>
	<div id='$t-2' style='width:900px;height:450px'></div>
	<div id='$t-3' style='width:900px;height:450px'></div>			
	<script>
		AnimateDiv('$t-1');
		AnimateDiv('$t-2');
		Loadjs('$page?graph1=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}&container=$t-1');
		Loadjs('$page?graph2=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;
	
}

function www_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["MAC"]=urlencode($_GET["MAC"]);

	$array["{graphs}"]="$page?www-graĥs=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	$array["{values}"]="$page?www-table=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);

}

function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["MAC"]=urlencode($_GET["MAC"]);
	$form=$boot->SearchFormGen("familysite","www-search","&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}");
	echo $form;
}

function www_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";

	
	$search=string_to_flexquery("www-search");
	$sql="SELECT * FROM (SELECT SUM(size) as size, SUM(hits) as hits, `familysite`,MAC FROM $hourtable 
	GROUP BY MAC,`familysite` HAVING MAC='{$_GET["MAC"]}' ORDER BY familysite) as t 
	WHERE 1 $search ORDER BY size DESC,hits DESC,familysite LIMIT 0,250";
	
	
	$results=$q->QUERY_SQL($sql);
	$_GET["MAC"]=urlencode($_GET["MAC"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$fsite=urlencode($ligne["familysite"]);
		//$jslink="Loadjs('miniadm.webstats.ByMAC.website.php?familysite=$fsite&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}')";
		$link=$boot->trswitch($jslink);
			$tr[]="
				<tr id='$id'>
					<td $link><i class='icon-globe'></i> {$ligne["familysite"]}</a></td>
					<td $link><i class='icon-info-sign'></i> $size</td>
					<td $link><i class='icon-info-sign'></i> $hits</td>
				</tr>";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
		
	
	
}

function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	

	$_GET["MAC"]=urlencode($_GET["MAC"]);
	$html="
	<div id='$t-1' style='width:900px;height:450px'></div>
	<div id='$t-2' style='width:900px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph4=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph5=yes&MAC={$_GET["MAC"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}

function graph2(){
	$q=new mysql_squid_builder();
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";
	
	

	
	$sql="SELECT SUM(size) as size,MAC,`hour` FROM $hourtable GROUP BY MAC,`hour` HAVING MAC='{$_GET["MAC"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	$tpl=new templates();
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=round($size/1024,2);
		$date=$ligne["hour"];
		$xdata[]=$date;
		$ydata[]=$size;
	
	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{size}";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}
function graph1(){
	$q=new mysql_squid_builder();
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";
	$sql="SELECT SUM(hits) as hits,`hour`,MAC FROM $hourtable GROUP BY MAC,`hour` HAVING MAC='{$_GET["MAC"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	
	$tpl=new templates();
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=$ligne["hour"];
		$xdata[]=$date;
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{requests}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";
	$sql="SELECT SUM(hits) as hits,`familysite`,MAC FROM $hourtable GROUP BY MAC,`familysite` HAVING MAC='{$_GET["MAC"]}' ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$PieData[$ligne["familysite"]]=$size;
	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{hits}");
	echo $highcharts->BuildChart();
	
}
function graph5(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$hourtable=date("Ymd",$_GET["xtime"])."_hour";
	$sql="SELECT SUM(size) as size,`familysite`,MAC FROM $hourtable GROUP BY MAC,`familysite` HAVING MAC='{$_GET["MAC"]}' ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		$PieData[$ligne["familysite"]]=$size;


	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} (MB)";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{size}");
	echo $highcharts->BuildChart();

}