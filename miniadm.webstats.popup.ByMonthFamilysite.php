<?php
session_start();
$_SESSION["MINIADM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["tab"])){tab();exit;}
if(isset($_GET["flow"])){section_flow();exit;}
if(isset($_GET["flow1"])){section_flow_graph1();exit;}

if(isset($_GET["topuser"])){section_topuser();exit;}
if(isset($_GET["topuser1"])){section_topuser_graph1();exit;}

if(isset($_GET["topmembers-table-js"])){topmembers_table_js();exit;}
if(isset($_GET["topmembers-table-section"])){topmembers_table_section();exit;}
if(isset($_GET["topmembers-search"])){topmembers_table_search();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$suffix=suffix();
	$title=$tpl->javascript_parse_text("{familysite}:{$_GET["familysite"]}: $title");
	echo "YahooWin2('900','$page?tab=yes$suffix','$title',true);";	
}

function topmembers_table_js(){
	header("content-type: application/x-javascript");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$title=$tpl->javascript_parse_text("{top_users}:$title {$_GET["familysite"]}");
	$suffix=suffix();
	echo "YahooWin3('563','$page?topmembers-table-section=yes$suffix','$title',true);";
}
function topmembers_table_section(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("uid","topmembers-search",$suffix);
	echo $form;
}

function topmembers_table_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("size"=>"DESC"));


	$searchstring=string_to_flexquery("topmembers-search");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$familysite=mysql_escape_string2($_GET["familysite"]);
	$table="quotamonth_$year$month";
	
	$table="(SELECT `uid`,familysite,SUM(size) as `size` FROM $table GROUP BY `uid`,familysite 
	HAVING familysite='$familysite' ORDER BY `size` DESC LIMIT 0,50) as t";

	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$members_text=$tpl->_ENGINE_parse_body("{members}");
	$websites_text=$tpl->_ENGINE_parse_body("{websites}");

	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,20";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$suffix=suffix();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		
		$size=FormatBytes($ligne["size"]/1024);
		$websiteenc=urlencode($ligne["familysite"]);
		$js=$boot->trswitch("Loadjs('miniadm.webstats.popup.ByMonthByUserByFamilysite.php?uid={$ligne["uid"]}$suffix')");

		$tr[]="
		<tr>
		<td style='font-size:18px;color:$color' nowrap  width=99% nowrap $js>{$ligne["uid"]}</td>
		<td style='font-size:18px;color:$color' nowrap  width=1% nowrap $js>$size</td>
		</tr>";


	}
	echo $boot->TableCompile(
			array("uid"=>"$members_text",
					"size"=>"$size_text",
			),
			$tr
	);

}

function suffix(){
	$uid=urlencode($_GET["familysite"]);
	return "&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}&familysite=$uid";
}

function tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$boot=new boostrap_form();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$suffix=suffix();
	$array["{flow}"]="$page?flow=yes$suffix";
	$array["{top_users}"]="$page?topuser=yes$suffix";
	$array["{members}"]="$page?topmembers-table-section=yes$suffix";
	echo $tpl->_ENGINE_parse_body("<div style='margin-top:20px'><H3>{$_GET["uid"]} $title</H3></div>").$boot->build_tab($array);
	
	
}
function section_flow(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");

	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	$suffix=suffix();
	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>

	<script>
	AnimateDiv('graph1-$ff');
	Loadjs('$page?flow1=yes&container=graph1-$ff$suffix');
	</script>

	";

	echo $html;
}

function section_topuser(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	$suffix=suffix();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();

	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>
	</tr>
	</table>

	<script>
	AnimateDiv('graph1-$ff');

	Loadjs('$page?topuser1=yes&container=graph1-$ff$suffix');
	</script>

	";

	echo $html;

}
function section_flow_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";
	$familysite=mysql_escape_string2($_GET["familysite"]);
	$sql="SELECT `day`,`familysite`,SUM(size) as `size` FROM $table GROUP BY `day`,`familysite` 
	HAVING `familysite`='$familysite' ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size);

		$date=$ligne["day"];
		$xdata[]=$date;
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{$_GET["familysite"]}: {flow} (MB)";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->LegendSuffix="MB";
	$highcharts->datas=array("{flow}"=>$ydata);
	echo $highcharts->BuildChart();
}
function section_topuser_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";
	$page=CurrentPageName();
	$familysite=mysql_escape_string2($_GET["familysite"]);
	$sql="SELECT `familysite`,SUM(size) as `size`,uid FROM $table GROUP BY `familysite`,uid
	HAVING familysite='$familysite' ORDER BY `size` DESC LIMIT 0,50";
	$results=$q->QUERY_SQL($sql);
	$suffix=suffix();

	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size);
		$PieData[$ligne["uid"]]=$size;
	}

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{$_GET["familysite"]}: {top_users} {size} MB");
	$highcharts->subtitle="<a href=\"javascript:Loadjs('$page?topmembers-table-js=yes$suffix')\" style='text-decoration:underline;font-size:18px'>{more_details}</a>";
	echo $highcharts->BuildChart();
}