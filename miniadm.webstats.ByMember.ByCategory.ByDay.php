<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
Privileges_members_ownstats();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["www-top"])){www_top();exit;}
if(isset($_GET["www-top-table"])){www_top_table();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{$_GET["uid"]}::{$_GET["category"]}::".time_to_date($_GET["xtime"]));
	if($_GET["category"]==null){$title=$tpl->javascript_parse_text("{$_GET["uid"]}::{unkown}");}
	$fsite=urlencode($_GET["category"]);
	$_GET["uid"]=urlencode($_GET["uid"]);
	$html="YahooWin2('800','$page?tabs=yes&category=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}','$title')";
	echo $html;
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["uid"]}, {category} {$_GET["category"]}, ".time_to_date($_GET["xtime"]));
	
	
	$_GET["uid"]=urlencode($_GET["uid"]);
	
	$fsite=urlencode($_GET["category"]);
	$array["{graphs}"]="$page?www-graĥs=yes&category=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array["{values}"]="$page?www-table=yes&category=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	
	
	echo "<H3>$title</H3>".	$boot->build_tab($array);
}
function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["category"]);
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>

	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph1=yes&category=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph2=yes&category=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;

}
function www_top(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["category"]);
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph3=yes&category=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph4=yes&category=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;	
}


function graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);
	$xtime=$_GET["xtime"];
	$curday=date("Y-m-d",$xtime);
	$sql="SELECT SUM(hits) as hits,category,familysite,zDate FROM `$tablename` GROUP BY
	category,zDate,familysite HAVING category='{$_GET["category"]}' AND zDate='$curday' ORDER BY `hits` DESC LIMIT 0,15";
	
	if($_GET["category"]==null){
		$sql="SELECT SUM(hits) as hits,category,familysite,zDate FROM `$tablename` GROUP BY
		category,zDate,familysite HAVING category IS NULL AND zDate='$curday' ORDER BY `hits` DESC LIMIT 0,15";
	}

	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{hits} {$_GET["category"]}");
	echo $highcharts->BuildChart();
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);

	$xtime=$_GET["xtime"];
	$curday=date("Y-m-d",$xtime);
	$sql="SELECT SUM(size) as size,category,familysite,zDate FROM `$tablename` GROUP BY
	category,zDate,familysite HAVING category='{$_GET["category"]}' AND zDate='$curday' ORDER BY `hits` DESC LIMIT 0,15";
	
	if($_GET["category"]==null){
		$sql="SELECT SUM(size) as size,category,familysite,zDate FROM `$tablename` GROUP BY
		category,zDate,familysite HAVING category IS NULL AND zDate='$curday' ORDER BY `hits` DESC LIMIT 0,15";
	}
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		$xdata[]=$ligne["hour"];
		$ydata[]=$size;

	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{size} {$_GET["category"]}");
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}

function www_table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$boot=new boostrap_form();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);

	$q=new mysql_squid_builder();

	if(!$q->TABLE_EXISTS($tablename)){
		echo "<p class=text-error>No table &laquo;$tablename&raquo; for {$_GET["uid"]}</p>";
		return;
	}
	$xtime=$_GET["xtime"];
	$curday=date("Y-m-d",$xtime);

	$sql="SELECT SUM(hits) as hits,SUM(size) as size,category,familysite,zDate FROM `$tablename` GROUP BY
	category,zDate HAVING category='{$_GET["category"]}' AND zDate='$curday' ORDER BY size DESC,hits DESC";
	
	if($_GET["category"]==null){
		$sql="SELECT SUM(hits) as hits,SUM(size) as size,category,familysite,zDate FROM `$tablename` GROUP BY
		category,zDate HAVING category IS NULL AND zDate='$curday' ORDER BY size DESC,hits DESC";
	}	

	$category=urlencode($_GET["category"]);
	$results=$q->QUERY_SQL($sql);
	$_GET["uid"]=urlencode($_GET["uid"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		
		
		
		$fsite=urlencode($ligne["familysite"]);
		$jslink="Loadjs('miniadm.webstats.ByMember.website.byday.php?familysite=$fsite&member-value={$_GET["member-value"]}&xtime={$_GET["xtime"]}')";
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr>
		<td $link><i class='icon-globe'></i>&nbsp;{$ligne["familysite"]}</a></td>
		<td $link><i class='icon-info-sign'></i> $size</td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";



	}

	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{websites}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";



}

function www_top_table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$boot=new boostrap_form();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);
	$member=urlencode($_GET["uid"]);
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS($tablename)){
		echo "<p class=text-error>No table &laquo;$tablename&raquo; for {$_GET["uid"]}</p>";
		return;
	}
	
	
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,familysite FROM `$tablename` GROUP BY
	category,familysite HAVING category='{$_GET["category"]}' ORDER BY size DESC,hits DESC LIMIT 0,100";
	
	if($_GET["category"]==null){
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,familysite FROM `$tablename` GROUP BY
	category,familysite HAVING category IS NULL ORDER BY size DESC,hits DESC LIMIT 0,100";
	}	
	
	$results=$q->QUERY_SQL($sql);
	$_GET["uid"]=urlencode($_GET["uid"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$familysite=$ligne["familysite"];
		$fsite=urlencode($familysite);
		$jslink="Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value=$member&by=uid')";
	$link=$boot->trswitch($jslink);
	$tr[]="
	<tr>
	<td $link><i class='icon-globe'></i> $familysite</a></td>
	<td $link><i class='icon-info-sign'></i> $size</td>
	<td $link><i class='icon-info-sign'></i> $hits</td>
	</tr>";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{websites}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	
}


function graph3(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);
	$sql="SELECT SUM(size) as size,familysite,category FROM `$tablename` GROUP BY familysite,category
	HAVING `category`='{$_GET["category"]}'
	ORDER BY size DESC LIMIT 0,15";
	
	
	if($_GET["category"]==null){
	$sql="SELECT SUM(size) as size,familysite,category FROM `$tablename` GROUP BY familysite,category
	HAVING `category` IS NULL
	ORDER BY size DESC LIMIT 0,15";
	}	
	
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{$_GET["category"]}/{top_websites}/{size}");
	echo $highcharts->BuildChart();	
	
}
function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$tablename="www_".$q->uid_to_tablename($_GET["uid"]);
	$sql="SELECT SUM(hits) as hits,familysite,category FROM `$tablename` GROUP BY familysite,category
	HAVING `category`='{$_GET["category"]}'
	ORDER BY hits DESC LIMIT 0,15";
	
	
	if($_GET["category"]==null){
		$sql="SELECT SUM(hits) as hits,familysite,category FROM `$tablename` GROUP BY familysite,category
		HAVING `category` IS NULL
		ORDER BY hits DESC LIMIT 0,15";
	}	
	
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{$_GET["category"]}/{top_websites}/{size}");
	echo $highcharts->BuildChart();

}

