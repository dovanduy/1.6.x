<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
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
if(isset($_GET["graph6"])){graph6();exit;}
if(isset($_GET["graph7"])){graph7();exit;}
if(isset($_GET["rqsize"])){rqsize_page();exit;}
if(isset($_GET["rqsize-graĥs"])){rqsize_graphs();exit;}
if(isset($_GET["rqsize-table"])){rqsize_table();exit;}

if(isset($_GET["www"])){www_page();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["www-search"])){www_search();exit;}

if(isset($_GET["categories"])){categories_page();exit;}
if(isset($_GET["categories-graĥs"])){categories_graphs();exit;}
if(isset($_GET["categories-table"])){categories_table();exit;}
if(isset($_GET["categories-search"])){categories_search();exit;}



$users=new usersMenus();
CheckRights();
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$xtime=$_GET["xtime"];
	
	$dateT=date("{l} {F} d",$xtime);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);}
	$dateT=$tpl->_ENGINE_parse_body($dateT);	
	
	
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("{$_GET["member-value"]}:$dateT");
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="YahooWin('995','$page?tabs=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}','$dateT')";
	echo $html;	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$xtime=$_GET["xtime"];
	
	$title=$tpl->_ENGINE_parse_body("{$_GET["member-value"]}: ".time_to_date($xtime));
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$array["{requests} & {size}"]="$page?rqsize=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	$array["{visited_websites}"]="$page?www=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	$array["{categories}"]="$page?categories=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	echo "<H3>$title</H3>".$boot->build_tab($array);	
	
}

function CheckRights(){
	if($_GET["member-value"]==null){$_GET["member-value"]=$_SESSION["uid"];}
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		if($_SESSION["WebstatisticsByMember"]==0){
			die("<H1>Oups!</H1>");
		}
		$_GET["member-value"]=$_SESSION["uid"];
	}
}

function rqsize_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	$array["{graphs}"]="$page?rqsize-graĥs=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	$array["{hours} {values}"]="$page?rqsize-table=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
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

	
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";	
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,uid,`hour` FROM $hourtable 
	GROUP BY uid,`hour` HAVING uid='{$_GET["member-value"]}' ORDER BY `hour` DESC";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=md5(serialize($ligne));
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$dateT="{$ligne["hour"]}h";
		
		
		$js="Loadjs('miniadm.webstats.ByMember.ByHour.php?member-value=&hour={$ligne["hour"]}&xtime=$xtime')";
		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-time'></i> $dateT</a></td>
		<td $link><i class='icon-info-sign'></i> $size</td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";	
	

	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{date}</th>
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
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="
	<div id='$t-1' style='width:900px;height:450px'></div>
	<div id='$t-2' style='width:900px;height:450px'></div>
			
	<script>
		AnimateDiv('$t-1');
		AnimateDiv('$t-2');
		Loadjs('$page?graph1=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-1');
		Loadjs('$page?graph2=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;
	
}

function www_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);

	$array["{graphs}"]="$page?www-graĥs=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	$array["{values}"]="$page?www-table=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);

}

function categories_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	$array["{graphs}"]="$page?categories-graĥs=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	$array["{values}"]="$page?categories-table=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);	
	
}

function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$form=$boot->SearchFormGen("familysite","www-search","&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}");
	echo $form;
}

function www_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	
	
	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";
	
	
	$sql="SELECT SUM(size) as size,familysite,uid FROM $hourtable GROUP BY
	familysite,uid HAVING uid='{$_GET["member-value"]}' ORDER BY hits DESC LIMIT 0,15";
	
	
	if(!$q->TABLE_EXISTS($hourtable)){
		echo "<p class=text-error>No table &laquo;`$hourtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$search=string_to_flexquery("www-search");
	$sql="SELECT * FROM (SELECT SUM(hits) as hits,SUM(size) as size,familysite,uid FROM `$hourtable` GROUP BY
	familysite,uid HAVING uid='{$_GET["member-value"]}' ORDER BY familysite) as t 
	WHERE 1 $search ORDER BY size DESC,hits DESC,familysite LIMIT 0,250";
	
	
	$results=$q->QUERY_SQL($sql);
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=$xtime;
		$hits=FormatNumber($ligne["hits"]);
		$fsite=urlencode($ligne["familysite"]);
		$jslink="Loadjs('miniadm.webstats.ByMember.website.byday.php?familysite=$fsite&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}')";
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
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="
	<div id='$t-1' style='width:900px;height:450px'></div>
	<div id='$t-2' style='width:900px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph4=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph5=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}

function categories_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
			$html="
			<div id='$t-1' style='width:900px;height:450px'></div>
			<div id='$t-2' style='width:900px;height:450px'></div>
	
			<script>
			AnimateDiv('$t-1');
			AnimateDiv('$t-2');
			Loadjs('$page?graph6=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-1');
			Loadjs('$page?graph7=yes&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}&container=$t-2');
			</script>
			";
			echo $html;	
	
}

function categories_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$users=new usersMenus();
	$uidenc=urlencode($_GET["member-value"]);
	$xtime=$_GET["xtime"];
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}

	$curdate=date("Y-m-d",$xtime);
	$curdateT=time_to_date($xtime);
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,category,zDate FROM `www_$uidtable`
	GROUP BY category,zDate HAVING zDate='$curdate'  ORDER BY size DESC,hits DESC";
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	$_GET["member-value"]=urlencode($_GET["member-value"]);


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$category=$ligne["category"];
		$category_text=$ligne["category"];
		if(trim($ligne["category"])==null){$category_text=$unknown;}
			
			
		$categoryenc=urlencode($ligne["category"]);
		$js="Loadjs('miniadm.webstats.ByMember.ByCategory.ByDay.php?uid={$_GET["member-value"]}&category=$categoryenc&xtime=$xtime')";

		$link=$boot->trswitch($js);
		$tr[]="
		<tr>
		<td $link><i class='icon-tag'></i>&nbsp;$category_text</a></td>
		<td $link><i class='icon-info-sign'></i> $size</td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";



	}

	echo $tpl->_ENGINE_parse_body("
			<H3>{$_GET["member-value"]} {categories} $curdateT</H3>
			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{category}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>
			<div style='text-align:right;margin-top:10px'>$bt</div>";


}

function graph2(){
	$q=new mysql_squid_builder();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";
	$sql="SELECT SUM(size) as size,uid,`hour`
	FROM $hourtable GROUP BY uid,`hour`
	HAVING uid='{$_GET["member-value"]}' ORDER BY `hour`";
	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=round($size/1024,2);
		$xdata[]=$ligne["hour"];
		$ydata[]=$size;
	
	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{size} / {hour}";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->LegendPrefix="{hour} ";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->LegendSuffix=" MB";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}
function graph1(){
	$q=new mysql_squid_builder();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";	
	$sql="SELECT SUM(hits) as hits,uid,`hour` 
	FROM $hourtable GROUP BY uid,`hour`
	HAVING uid='{$_GET["member-value"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$xdata[]=$ligne["hour"];
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{requests} / {hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->LegendPrefix="{hour} ";
	$highcharts->LegendSuffix=" {requests}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";	
	
	
	$sql="SELECT SUM(hits) as hits,familysite,uid FROM $hourtable GROUP BY 
	familysite,uid HAVING uid='{$_GET["member-value"]}' ORDER BY hits DESC LIMIT 0,15";
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
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";	
	
	
	$sql="SELECT SUM(size) as size,familysite,uid FROM $hourtable GROUP BY 
	familysite,uid HAVING uid='{$_GET["member-value"]}' ORDER BY hits DESC LIMIT 0,15";
	
	
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
function graph6(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";
	
	
	$sql="SELECT SUM(hits) as hits,category,uid FROM $hourtable GROUP BY
	category,uid HAVING uid='{$_GET["member-value"]}' ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		if($ligne["category"]==null){$ligne["category"]="UnkNown";}
		$PieData[$ligne["category"]]=$size;
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{hits}");
	echo $highcharts->BuildChart();	
	
}
function graph7(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";	
	
	
	$sql="SELECT SUM(size) as size,category,uid FROM $hourtable GROUP BY 
	category,uid HAVING uid='{$_GET["member-value"]}' ORDER BY hits DESC LIMIT 0,15";
	
	
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		if($ligne["category"]==null){$ligne["category"]="UnkNown";}
		$PieData[$ligne["category"]]=$size;


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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{size}");
	echo $highcharts->BuildChart();

}
