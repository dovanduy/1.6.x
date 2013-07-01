<?php
session_start();
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
//ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
checkrights();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["www-table"])){www_table();exit;}


js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title="{$_GET["member-value"]}::{$_GET["familysite"]}";
	$fsite=urlencode($_GET["familysite"]);
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="YahooWin2('900','$page?tabs=yes&familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}','$title')";
	echo $html;
//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}	
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["member-value"]}, {website}:{$_GET["familysite"]}");
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$fsite=urlencode($_GET["familysite"]);
	$array["{graphs}"]="$page?www-graĥs=yes&familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{values}"]="$page?www-table=yes&familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array[$_GET["familysite"]]="miniadm.webstats.website.infos.php?familysite=$fsite";
	echo "<H3>$title</H3>".$boot->build_tab($array);	
}
function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$fsite=urlencode($_GET["familysite"]);
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph1=yes&familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
	Loadjs('$page?graph2=yes&familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}
function graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as hits,zDate,familysite FROM `www_$uidtable` GROUP BY
	familysite,zDate HAVING familysite='{$_GET["familysite"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{$_GET["familysite"]} {requests}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(size) as size,zDate,familysite FROM `www_$uidtable` GROUP BY
	familysite,zDate HAVING familysite='{$_GET["familysite"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{$_GET["familysite"]} {size} (MB)";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}


function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,zDate FROM `www_$uidtable`
	GROUP BY familysite,zDate HAVING familysite='{$_GET["familysite"]}' ORDER BY zDate DESC";
	$results=$q->QUERY_SQL($sql);
	$_GET["familysite"]=urlencode($_GET["familysite"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$dateT=date("{l} {F} d",$date);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$date);}
		$dateT=$tpl->_ENGINE_parse_body($dateT);
		    $jshost="Loadjs('miniadm.webstats.ByMember.website.byday.php?xtime=$date&uid={$_GET["member-value"]}&familysite={$_GET["familysite"]}')";
		
			$link=$boot->trswitch($jshost);
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


function CheckRights(){$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){if($_SESSION["WebstatisticsByMember"]==0){die("<H1>Oups!</H1>");}$_GET["member-value"]=$_SESSION["uid"];}}


