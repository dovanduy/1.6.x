<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
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
if(isset($_GET["www-categories"])){www_categories();exit;}
if(isset($_GET["www-requests"])){www_requests();exit;}
if(isset($_GET["requests-search"])){www_requests_search();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title="{$_GET["uid"]}";
	$uid=urlencode($_GET["uid"]);
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT {$_GET["hour"]}H");
	$html="YahooWin4('800','$page?tabs=yes&uid=$uid&xtime={$_GET["xtime"]}&hour={$_GET["hour"]}','$dateT::{$_GET["uid"]}')";
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
	$date=time_to_date($_GET["xtime"]);
	$dateT=$tpl->javascript_parse_text("$date {$_GET["hour"]}H");
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["uid"]}, $dateT");
	$_GET["uid"]=urlencode($_GET["uid"]);
	$array["{websites}"]="$page?www-graĥs=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array["{categories}"]="$page?www-categories=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array["{websites} {values}"]="$page?www-requests=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);
}
function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>

	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph1=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph2=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;

}
function www_categories(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["uid"]=urlencode($_GET["uid"]);

	$html="
	<div id='$t-1' style='width:780px;height:450px'></div>
	<div id='$t-2' style='width:780px;height:450px'></div>
	
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph3=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph4=yes&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}&container=$t-2');
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
	familysite,`hour`,uid HAVING `hour`='{$_GET["hour"]}' AND uid='{$_GET["uid"]}' ORDER BY `hits` DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$PieData[$ligne["familysite"]]=$size;

	}
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites} {hits} (MB) {$_GET["hour"]}h";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{hits} {$_GET["hour"]}h");
	echo $highcharts->BuildChart();
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$sql="SELECT SUM(size) as size,`hour`,uid,familysite FROM $hourtable GROUP BY
	familysite,`hour`,uid HAVING `hour`='{$_GET["hour"]}' AND uid='{$_GET["uid"]}' ORDER BY `size` DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
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
	$highcharts->PiePlotTitle="{top_websites} {size} (MB) {$_GET["hour"]}h";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{size} {$_GET["hour"]}h");
	echo $highcharts->BuildChart();
}
function graph3(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$sql="SELECT SUM(size) as size,`hour`,uid,category FROM $hourtable GROUP BY
	category,`hour`,uid HAVING `hour`='{$_GET["hour"]}' AND uid='{$_GET["uid"]}' ORDER BY `size` DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
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
	$highcharts->PiePlotTitle="{top_websites} {size} (MB) {$_GET["hour"]}h";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{size} {$_GET["hour"]}h");
	echo $highcharts->BuildChart();
}
function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$sql="SELECT SUM(size) as size,`hour`,uid,category FROM $hourtable GROUP BY
	category,`hour`,uid HAVING `hour`='{$_GET["hour"]}' AND uid='{$_GET["uid"]}' ORDER BY `size` DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
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
	$highcharts->PiePlotTitle="{top_websites} {size} (MB) {$_GET["hour"]}h";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{size} {$_GET["hour"]}h");
	echo $highcharts->BuildChart();
}
function www_requests(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$form=$boot->SearchFormGen("sitename","requests-search","&uid={$_GET["uid"]}&hour={$_GET["hour"]}&xtime={$_GET["xtime"]}");
	echo $form;

}
function www_requests_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("requests-search");
	$xtime=$_GET["xtime"];
	$hourtable=date("Ymd",$xtime)."_hour";

	$q=new mysql_squid_builder();

	$sql="SELECT SUM(size) as size,SUM(hits) as hits,`hour`,uid,category,sitename FROM $hourtable GROUP BY
	category,`hour`,sitename,uid 
	HAVING `hour`='{$_GET["hour"]}' 
	AND uid='{$_GET["uid"]}' $search
	ORDER BY `size` DESC,`hits`,`sitename` DESC";
	$results=$q->QUERY_SQL($sql);


	$results=$q->QUERY_SQL($sql);
	$_GET["uid"]=urlencode($_GET["uid"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		
		$sitename=$ligne["sitename"];
		$uri=$ligne["uri"];
		$urilenth=strlen($sitename);
		if($urilenth>80){
			$sitename=substr($sitename,0, 77)."...";
		}
		
		$sitenameenc=urlencode($sitename);
		$js="Loadjs('miniadm.webstats.ByMember.ByHour.queries.php?sitename=$sitenameenc&uid={$_GET["uid"]}&hour={$_GET["hour"]}&xtime={$_GET["xtime"]}')";
		$link=$boot->trswitch($js);
		$tr[]="
		<tr>
			<td $link><i class='icon-globe'></i>&nbsp;$sitename</td>
			<td $link><i class='icon-globe'></i>&nbsp;{$ligne["category"]}</td>
			<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$size</td>
			<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$hits</td>
		</tr>";



	}

	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{websites}</th>
					<th>{category}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";




}