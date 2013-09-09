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
if(isset($_GET["www-table"])){www_requests();exit;}
if(isset($_GET["requests-search"])){www_requests_search();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;
		return;
	}	
	
	$title=$tpl->javascript_parse_text("{$_GET["uid"]}::{videos}");
	
	$_GET["uid"]=urlencode($_GET["uid"]);
	
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT");
	$html="YahooWin3('800','$page?tabs=yes&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}','$dateT::$title')";
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
	call_user_func(BECALL);
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["uid"]}, {videos}: $date");
	
	
	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["familysite"]);
	$array["{graphs}"]="$page?www-graĥs=yes&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array["{videos}"]="$page?www-table=yes&familysite=$fsite&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);
}

function www_graphs(){
	call_user_func(BECALL);
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
	Loadjs('$page?graph1=yes&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-1');
	Loadjs('$page?graph2=yes&uid={$_GET["uid"]}&by={$_GET["by"]}&xtime={$_GET["xtime"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}
function graph1(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$xtime=$_GET["xtime"];
	$hourtable="youtubeday_".date("Ymd",$xtime);
	
	$sql="SELECT SUM(hits) as hits,`hour`,uid FROM $hourtable GROUP BY
	`hour`,uid HAVING uid='{$_GET["uid"]}' ORDER BY `hour`";
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
	$highcharts->Title="{videos} {requests}/{hour}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{videos}"=>$ydata);
	echo $highcharts->BuildChart();
}
function graph2(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$_GET["uid"]=mysql_escape_string2($_GET["uid"]);
	$sql="SELECT SUM(hits) as hits,category,zDate,uid FROM `youtube_all` GROUP BY
	category,zDate,uid HAVING zDate='".date("Y-m-d",$_GET["xtime"])."' AND uid='{$_GET["uid"]}' ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
		$PieData[$ligne["category"]]=$size;
	}
	
	if(!$q->ok){
	$tpl->javascript_senderror($q->mysql_error."<br>$sql",$_GET["container"]);
	}
	
	
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{categories}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{hits}");
	echo $highcharts->BuildChart();	
}

function www_requests(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["uid"]=urlencode($_GET["uid"]);
	$form=$boot->SearchFormGen("category,zDate,title","requests-search","&uid={$_GET["uid"]}&familysite={$_GET["familysite"]}&xtime={$_GET["xtime"]}");
	echo $form;	
	
}
function format_time($t,$f=':') // t = seconds, f = separator
{
	return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}

function www_requests_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();	
	$search=string_to_flexquery("requests-search");
	$xtime=$_GET["xtime"];
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$created=$tpl->javascript_parse_text("{created}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$categories=$tpl->javascript_parse_text("{categories}");
	
	
	$q=new mysql_squid_builder();
	$search=string_to_flexquery("requests-search");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$_GET["uid"]=mysql_escape_string2($_GET["uid"]);
	$sql="SELECT t.*,youtube_objects.uploaded,youtube_objects.duration,youtube_objects.title FROM (SELECT SUM(hits) as hits,youtubeid,zDate,uid,category FROM `youtube_all` GROUP BY
	youtubeid,zDate,uid,category HAVING zDate='".date("Y-m-d",$_GET["xtime"])."' 
	AND uid='{$_GET["uid"]}' ORDER BY hits) as t,youtube_objects 
	WHERE t.youtubeid=youtube_objects.youtubeid $search
	LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	
	$results = $q->QUERY_SQL($sql);
	$boot=new boostrap_form();
	
	if(!$q->ok){die("<p class=text-error>$q->mysql_error</p>");}	
	
	$seconds=$tpl->_ENGINE_parse_body("{seconds}");
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$hours=$tpl->_ENGINE_parse_body("{hours}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$youtubeid=$ligne["youtubeid"];
		
		$color="black";
		$unit=$seconds;
		$ligne["duration"]=format_time($ligne["duration"]);
		
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$urljsSIT="Loadjs('miniadm.webstats.ByMember.ByYoutubeByHour.php?filterBy=uid&xtime={$_GET["xtime"]}&value={$_GET["uid"]}&youtubeid=$youtubeid')";
		
		
		$link=$boot->trswitch($urljsSIT);
		$jsvideo=$boot->trswitch("Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid');");
		$tr[]="
		<tr>
		<td $jsvideo><img  src='miniadm.webstats.youtube.php?thumbnail=$youtubeid' class=img-polaroid></td>
		<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["uploaded"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["title"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["category"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["duration"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	}

	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>$created</th>
					<th>$title</th>
					<th>$category</th>
					<th>$duration</th>
					<th>$hits</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	

}
?>