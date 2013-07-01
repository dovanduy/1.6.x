<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
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
if(isset($_GET["www-table"])){www_table();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title="{$_GET["uid"]}::{$_GET["familysite"]}";
	$fsite=urlencode($_GET["familysite"]);
	$_GET["uid"]=urlencode($_GET["uid"]);
	
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT");
	$html="YahooWin3('800','$page?tabs=yes&familysite=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}','$dateT::$title')";
	echo $html;
//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}	
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
	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["familysite"]);
	$array["{graphs}"]="$page?www-graĥs=yes&familysite=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array["{websites}"]="$page?www-table=yes&familysite=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	echo $boot->build_tab($array);
}

function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["familysite"]);
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph1=yes&familysite=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph2=yes&familysite=$fsite&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}
function graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";
	
	$sql="SELECT SUM(hits) as hits,`hour`,uid,familysite FROM $hourtable GROUP BY
	familysite,`hour`,uid HAVING familysite='{$_GET["familysite"]}' AND uid='{$_GET["uid"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$xdata[]=$ligne["hour"];
		$ydata[]=$size;

	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{$_GET["familysite"]} {requests}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$sql="SELECT SUM(size) as size,`hour`,uid,familysite FROM $hourtable GROUP BY
	familysite,`hour`,uid HAVING familysite='{$_GET["familysite"]}' AND uid='{$_GET["uid"]}' ORDER BY `hour`";
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
	$highcharts->Title="{$_GET["familysite"]} {requests}/{size} (MB)";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}

function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS($hourtable)){
		echo "<p class=text-error>No table &laquo;$hourtable&raquo; for {$_GET["uid"]}</p>";
		return;
	}

	
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,familysite,sitename FROM `$hourtable` GROUP BY
	familysite,sitename HAVING familysite='{$_GET["familysite"]}' ORDER BY size DESC,hits DESC,sitename";


	$results=$q->QUERY_SQL($sql);
	$_GET["uid"]=urlencode($_GET["uid"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$fsite=urlencode($ligne["familysite"]);
		//$jslink="Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}')";
		$link=$boot->trswitch($jslink);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-globe'></i> {$ligne["sitename"]}</a></td>
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
