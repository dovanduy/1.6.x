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
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["flow"])){section_flow();exit;}
if(isset($_GET["flow1"])){section_flow_graph1();exit;}

if(isset($_GET["topwww"])){section_topwww();exit;}
if(isset($_GET["topwww1"])){section_topwww_graph1();exit;}

if(isset($_GET["topusers"])){section_topusers();exit;}
if(isset($_GET["topusers1"])){section_topusers_graph1();exit;}

if(isset($_GET["topou"])){section_topou();exit;}
if(isset($_GET["topou1"])){section_topou_graph1();exit;}

if(isset($_GET["topusers-table-js"])){topusers_table_js();exit;}
if(isset($_GET["topusers-table-section"])){topusers_table_section();exit;}
if(isset($_GET["topusers-search"])){topusers_table_search();exit;}

if(isset($_GET["topwww-table-js"])){topwww_table_js();exit;}
if(isset($_GET["topwww-table-section"])){topwww_table_section();exit;}
if(isset($_GET["topwww-search"])){topwww_table_search();exit;}



main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}')</script>", $content);
	echo $content;
}

function topusers_table_js(){
	header("content-type: application/x-javascript");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$title=$tpl->javascript_parse_text("{top_users}:$title");
	echo "YahooWin('563','$page?topusers-table-section=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}','$title',true);";
}

function topwww_table_js(){
	header("content-type: application/x-javascript");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$page=CurrentPageName();
	$tpl=new templates();
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$suffix=suffix();
	$title=$tpl->javascript_parse_text("{top_websites}:$title");
	echo "YahooWin('563','$page?topwww-table-section=yes$suffix','$title',true);";
}

function suffix(){
	return "&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}";
}

function topusers_table_section(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("uid","topusers-search",$suffix);
	echo $form;	
}
function topwww_table_section(){
	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("familysite","topwww-search",$suffix);
	echo $form;	
	
}
function topwww_table_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("size"=>"DESC"));
	
	
	$searchstring=string_to_flexquery("topwww-search");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";
	$table="(SELECT `familysite`,SUM(size) as `size` FROM $table GROUP BY `familysite` 
	HAVING LENGTH(familysite)>0 ORDER BY `size` DESC LIMIT 0,100) as t";
	
	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$suffix=suffix();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	$color="black";
	
	
	$size=FormatBytes($ligne["size"]/1024);
	$familysiteenc=urlencode($ligne["familysite"]);
	
	$js=$boot->trswitch("Loadjs('miniadm.webstats.popup.ByMonthFamilysite.php?familysite=$familysiteenc$suffix')");
	
			$tr[]="
			<tr>
			<td style='font-size:18px;color:$color' nowrap  width=99% nowrap $js>{$ligne["familysite"]}</td>
			<td style='font-size:18px;color:$color' nowrap  width=1% nowrap $js>$size</td>
			</tr>";
	
	
	}
	echo $boot->TableCompile(
	array("familysite"=>"$familysite",
	"size"=>"$size_text",
			),
			$tr
	);
		
	
}

function topusers_table_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("size"=>"DESC"));
	
	
	$searchstring=string_to_flexquery("topusers-search");
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";	
	$table="(SELECT `uid`,SUM(size) as `size` FROM $table GROUP BY `uid` HAVING LENGTH(uid)>0 ORDER BY `size` DESC LIMIT 0,100) as t";
	
	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$suffix=suffix();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		$urljs="Loadjs('miniadmin.hotspot.php?MessageID-js=$MessageID&table=$table_query')\"
		style='font-size:11px;text-decoration:underline'>";
		
		$size=FormatBytes($ligne["size"]/1024);
		$uidenc=urlencode($ligne["uid"]);
		$js=$boot->trswitch("Loadjs('miniadm.webstats.popup.ByMonthUser.php?uid=$uidenc$suffix')");
		
		$tr[]="
				<tr>
				<td style='font-size:18px;color:$color' nowrap  width=99% nowrap $js>{$ligne["uid"]}</td>
				<td style='font-size:18px;color:$color' nowrap  width=1% nowrap $js>$size</td>
				</tr>";
	
	
	}
	echo $boot->TableCompile(
			array("uid"=>"$members",
					"size"=>"$size_text",
			),
			$tr
	);	
	
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>

	</div>
	<div id='BodySubContent-$t'></div>
	<H3>$title</H3>
	<script>
		LoadAjax('BodySubContent-$t','$page?tabs=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}');
	</script>



	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$boot=new boostrap_form();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$time=strtotime("$year-$month-01 00:00:00");
	$title=date("{F} Y",$time);
	
	
	$array["{flow}"]="$page?flow=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}";
	$array["{top_websites}"]="$page?topwww=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}";
	$array["{top_members}"]="$page?topusers=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}";
	$array["{top_ou}"]="$page?topou=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}";
	
	
	echo $tpl->_ENGINE_parse_body("<div style='margin-top:20px'><H3>$title</H3></div>").$boot->build_tab($array);
	
} 

function section_flow(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
	AnimateDiv('graph1-$ff');
	//AnimateDiv('graph2-$ff');
	//AnimateDiv('graph3-$ff');
	Loadjs('$page?flow1=yes&container=graph1-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?flow2=yes&container=graph2-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?flow3=yes&container=graph3-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?flow4=yes&container=graph4-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	
	</script>
	
	";
	
	echo $html;
}

function section_topwww(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
	AnimateDiv('graph1-$ff');
	//AnimateDiv('graph2-$ff');
	//AnimateDiv('graph3-$ff');
	Loadjs('$page?topwww1=yes&container=graph1-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww2=yes&container=graph2-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww3=yes&container=graph3-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww4=yes&container=graph4-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	
	</script>
	
	";
	
	echo $html;	
	
}

function section_topou(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
	AnimateDiv('graph1-$ff');
	//AnimateDiv('graph2-$ff');
	//AnimateDiv('graph3-$ff');
	Loadjs('$page?topou1=yes&container=graph1-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww2=yes&container=graph2-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww3=yes&container=graph3-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topwww4=yes&container=graph4-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	
	</script>
	
	";
	
	echo $html;	
	
}

function section_topusers(){
	$tpl=new templates();
	$pleasewait=$tpl->_ENGINE_parse_body("{please_wait}");
	
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	
	$html="
	<div id='graph1-$ff' style='width:99%;height:650px'><span style='font-size:22px'>$pleasewait</span></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
	AnimateDiv('graph1-$ff');
	//AnimateDiv('graph2-$ff');
	//AnimateDiv('graph3-$ff');
	Loadjs('$page?topusers1=yes&container=graph1-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topusers2=yes&container=graph2-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topusers3=yes&container=graph3-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	//Loadjs('$page?topusers4=yes&container=graph4-$ff&year={$_GET["year"]}&month={$_GET["month"]}');
	
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
	
	$sql="SELECT `day`,SUM(size) as `size` FROM $table GROUP BY `day` ORDER BY `day`";
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
	$highcharts->Title="{flow} (MB)";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->LegendSuffix="MB";
	$highcharts->datas=array("{flow}"=>$ydata);
	echo $highcharts->BuildChart();
	
	
}

function section_topwww_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";
	
	$sql="SELECT `familysite`,SUM(size) as `size` FROM $table GROUP BY `familysite` 
	ORDER BY `size` DESC LIMIT 0,20 ";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$size=$ligne["size"];
	$size=$size/1024;
	$size=$size/1024;
	$size=round($size);
	$PieData[$ligne["familysite"]]=$size;
	}
	
	
	$page=CurrentPageName();
	$suffix=suffix();
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->subtitle="<a href=\"javascript:Loadjs('$page?topwww-table-js=yes$suffix')\" style='text-decoration:underline;font-size:18px'>{more_details}</a>";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites} {size} MB");
	echo $highcharts->BuildChart();
}
function section_topusers_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";

	$sql="SELECT `uid`,SUM(size) as `size` FROM $table GROUP BY `uid`
	HAVING LENGTH(uid)>0 
	ORDER BY `size` DESC LIMIT 0,20 ";
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$size=$ligne["size"];
	$size=$size/1024;
	$size=$size/1024;
	$size=round($size);
	$PieData[$ligne["uid"]]=$size;
	}


	$suffix=suffix();
	$page=CurrentPageName();
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->subtitle="<a href=\"javascript:Loadjs('$page?topusers-table-js=yes$suffix')\" style='text-decoration:underline;font-size:18px'>{more_details}</a>";
	
	
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_users} {size} MB");
	echo $highcharts->BuildChart();
}

function section_topou_graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$year=$_GET["year"];
	$month=$_GET["month"];
	$table="quotamonth_$year$month";
	
	$sql="SELECT `ou`,SUM(size) as `size` FROM $table GROUP BY `ou`
	ORDER BY `size` DESC LIMIT 0,20 ";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			$size=round($size);
			if($ligne["ou"]==null){$ligne["ou"]="unknown";}
			$PieData[$ligne["ou"]]=$size;
			}
	
	
	
	
			$tpl=new templates();
			$highcharts=new highcharts();
			$highcharts->container=$_GET["container"];
			$highcharts->PieDatas=$PieData;
			$highcharts->ChartType="pie";
			$highcharts->PiePlotTitle="{size} MB";
			$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ou} {size} MB");
			echo $highcharts->BuildChart();	
	
}