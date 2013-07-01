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
include_once(dirname(__FILE__)."/ressources/class.squid.youtube.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
Privileges_members_ownstats();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-hits"])){www_hits();exit;}
if(isset($_GET["www-allhits"])){www_allhits();exit;}
if(isset($_GET["search-hits"])){www_hits_search();exit;}
if(isset($_GET["search-allhits"])){www_allhits_search();exit;}



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
	SEND_CORP_LICENSE_JAVASCRIPT();
	$uid=$_GET["value"];
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	$suffix=suffix();
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT");
	$html="YahooWin5('800','$page?tabs=yes$suffix','$dateT::{$_GET["filterBy"]}:$uid')";
	echo $html;
//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function suffix(){
	$value=urlencode($_GET["value"]);
	return "&filterBy={$_GET["filterBy"]}&xtime={$_GET["xtime"]}&value=$value&youtubeid={$_GET["youtubeid"]}";
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$dateT=$tpl->javascript_parse_text("$date");
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["filterBy"]} {$_GET["value"]}, $dateT");
	$suffix=suffix();
	$value=urlencode($_GET["value"]);
	
	
	$q=new mysql_squid_builder();
	$hourtable="youtubeday_".date("Ymd",$_GET["xtime"]);
	$sql="SELECT COUNT(youtubeid) as tcount,`{$_GET["filterBy"]}` FROM $hourtable GROUP BY `{$_GET["filterBy"]}` HAVING `{$_GET["filterBy"]}`='{$_GET["value"]}'";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	if($_GET["youtubeid"]<>null){
		$youtube=new YoutubeStats();
		$videotitle="<H4>".$youtube->youtube_title($_GET["youtubeid"])."</h4>";
		$array["{hits}"]="$page?www-hits=yes$suffix";
	}
	
	$array["{$_GET["value"]} <strong>{$ligne["tcount"]}</strong> {videos}"]="$page?www-allhits=yes$suffix";
	echo "<H3>".$title."</H3>$videotitle".$boot->build_tab($array);
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


function www_hits(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$suffix=suffix();
	$form=$boot->SearchFormGen("hour","search-hits",$suffix);
	echo $form;

}
function www_allhits(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$suffix=suffix();
	$form=$boot->SearchFormGen("hour,video","search-allhits",$suffix);
	echo $form;	
}

function www_allhits_search(){
	$users=new usersMenus();
	SEND_CORP_LICENSE();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("search-allhits");
	$xtime=$_GET["xtime"];
	$hourtable=$tablemain="youtubeday_".date("Ymd",$_GET["xtime"]);
	$youtube=new YoutubeStats();
	$q=new mysql_squid_builder();

	
	
	$sql="SELECT * FROM (SELECT $hourtable.*,youtube_objects.title as video FROM $hourtable,youtube_objects
	  WHERE $hourtable.youtubeid=youtube_objects.youtubeid
	  AND `{$_GET["filterBy"]}`='{$_GET["value"]}' ORDER BY `hour` LIMIT 0,250) as t WHERE 1 $search";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}


	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$youtubeid=$ligne["youtubeid"];
		$hits=FormatNumber($ligne["hits"]);
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$videotitle=$ligne["video"];
		$link=$boot->trswitch($urljsSIT);
		
		$urljsHour="Loadjs('miniadm.webstats.ByHourYoutube.php?youtubeid=$youtubeid&hour={$ligne["hour"]}&xtime={$_GET["xtime"]}')";
		$linkHour=$boot->trswitch($urljsHour);		
		
		$tr[]="
		<tr>
			<td $link valign='top' width=1% nowrap><img src='miniadm.webstats.youtube.php?thumbnail={$ligne["youtubeid"]}'></td>
			<td $link><i class='icon-globe'></i>&nbsp;$videotitle</td>
			<td $linkHour><i class='icon-time'></i>&nbsp;{$ligne["hour"]}h</td>
			<td nowrap><i class='icon-info-sign'></i>&nbsp;$hits</td>
		</tr>";



	}

	
	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th colspan=2>{video}</th>
					<th>{hour}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";




}
function  www_hits_search(){
	$users=new usersMenus();
	SEND_CORP_LICENSE();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("search-hits");
	$xtime=$_GET["xtime"];
	$hourtable=$tablemain="youtubeday_".date("Ymd",$_GET["xtime"]);
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM $hourtable WHERE youtubeid='{$_GET["youtubeid"]}' AND `{$_GET["filterBy"]}`='{$_GET["value"]}' ORDER BY `hour`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
	$youtube=new YoutubeStats();
	$videotitle=$youtube->youtube_title($_GET["youtubeid"]);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$youtubeid=$ligne["youtubeid"];
		$hits=FormatNumber($ligne["hits"]);
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$link=$boot->trswitch($urljsSIT);
		
		$urljsHour="Loadjs('miniadm.webstats.ByHourYoutube.php?youtubeid=$youtubeid&hour={$ligne["hour"]}&xtime={$_GET["xtime"]}')";
		$linkHour=$boot->trswitch($urljsHour);
		
		$tr[]="
		<tr>
		<td $linkHour><i class='icon-globe'></i>&nbsp;{$ligne["hour"]}h</td>
		<td $link><i class='icon-globe'></i>&nbsp;$videotitle</td>
		<td nowrap><i class='icon-info-sign'></i>&nbsp;$hits</td>
		</tr>";
	
	
	
	}
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{hour}</th>
			<th>{video}</th>
			<th>{hits}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>";
	
	
	
	
	}
