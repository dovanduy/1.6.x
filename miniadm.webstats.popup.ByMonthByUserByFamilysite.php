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

if(isset($_GET["topwww"])){section_topwww();exit;}
if(isset($_GET["topwww1"])){section_topwww_graph1();exit;}

if(isset($_GET["topwebsites-table-js"])){topwebsites_table_js();exit;}
if(isset($_GET["flow-details-section"])){section_flow_section();exit;}
if(isset($_GET["flow-details-search"])){section_flow_search();exit;}

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
	$title=$tpl->javascript_parse_text("{member}:{$_GET["uid"]} {webiste} {$_GET["familysite"]} $title");
	echo "YahooWin4('900','$page?tab=yes$suffix','$title',true);";
}

function suffix(){
	$uid=urlencode($_GET["uid"]);
	$familysite=urlencode($_GET["familysite"]);
	return "&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}&uid=$uid&familysite=$familysite";
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
	$array["{details}"]="$page?flow-details-section=yes$suffix";
	echo $tpl->_ENGINE_parse_body("<div style='margin-top:20px'><H3>&laquo;{$_GET["uid"]}&raquo; {webiste} {$_GET["familysite"]} $title</H3></div>").$boot->build_tab($array);
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
function section_flow_section(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("day","flow-details-search",$suffix);
	echo $form;	
}
function section_flow_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("day"=>"DESC"));
	
	
	$searchstring=string_to_flexquery("topwebsites-search");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$uid=mysql_escape_string2($_GET["uid"]);
	$familysite=mysql_escape_string2($_GET["familysite"]);
	$familysiteEnc=urlencode($_GET["familysite"]);
	$uidenc=urlencode($_GET["uid"]);
	$table="quotamonth_$year$month";
	$table="(SELECT `uid`,`day`,familysite,SUM(size) as `size` FROM $table GROUP BY `uid`,familysite,`day`
	HAVING uid='$uid' 
	AND familysite='$familysite'
	ORDER BY `day` LIMIT 0,30) as t";
	
	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$websites_text=$tpl->_ENGINE_parse_body("{day}");
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,20";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$suffix=suffix();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		$day=$ligne["day"];
		if(strlen($day)==1){$day="0$day";}
		$size=FormatBytes($ligne["size"]/1024);
		$time=strtotime("$year-$month-$day 00:00:00");
		$dayname=$tpl->_ENGINE_parse_body(date("{l} $day",$time));
		$js=$boot->trswitch("Loadjs('miniadm.webstats.websites.ByDayByFamilySiteByMember.php?familysite=$familysiteEnc&xtime=$time&uid=$uidenc')");
	
		$tr[]="
		<tr>
		<td style='font-size:18px;color:$color' nowrap  width=99% nowrap $js>$dayname</td>
		<td style='font-size:18px;color:$color' nowrap  width=1% nowrap $js>$size</td>
		</tr>";
	
	
	}
	echo $boot->TableCompile(
			array("day"=>"{days}",
					"size"=>"$size_text",
			),
					$tr
			);
	
	
	
}


function section_flow_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";

	$sql="SELECT `day`,uid,familysite,SUM(size) as `size` FROM $table GROUP BY `day`,uid,familysite
	HAVING uid='{$_GET["uid"]}' AND `familysite`='{$_GET["familysite"]}' ORDER BY `day`";
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
	$highcharts->Title="{$_GET["uid"]} {website} {$_GET["familysite"]}: {flow} (MB)";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->LegendSuffix="MB";
	$highcharts->datas=array("{flow}"=>$ydata);
	echo $highcharts->BuildChart();
}