<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die("oups");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.youtube.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["www-graĥs"])){section_graphs();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_GET["graph2"])){generate_graph2();exit;}
if(isset($_GET["graph3"])){generate_graph3();exit;}
if(isset($_GET["graph4"])){generate_graph4();exit;}
if(isset($_GET["www-table"])){section_table();exit;}
if(isset($_GET["www-video"])){section_video();exit;}
if(isset($_GET["www-members"])){section_members();exit;}
if(isset($_GET["video-search"])){video_search();exit;}
if(isset($_GET["members-search"])){members_search();exit;}





if(isset($_GET["webstats_middle_table"])){webstats_middle_table();exit;}
if(isset($_GET["items"])){webstats_middle_table_items();exit;}

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
	$dateT=$tpl->javascript_parse_text("{youtube_videos}: $dateT {$_GET["hour"]}h");
	$html="YahooWin5('1002','$page?tabs=yes$suffix','$dateT')";
	echo $html;
	//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}
}
function suffix(){
	$value=urlencode($_GET["value"]);
	return "&xtime={$_GET["xtime"]}&youtubeid={$_GET["youtubeid"]}&hour={$_GET["hour"]}";
}




function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$boot=new boostrap_form();
	call_user_func(BECALL);
	$title=null;
	$suffix=suffix();
	$dateT=time_to_date($_GET["xtime"]);
	$title=$tpl->_ENGINE_parse_body("<H3>&laquo;$dateT {$_GET["hour"]}h&raquo; {youtube_videos}</H3>");
	if($_GET["youtubeid"]<>null){
		$array["{graphs}"]="$page?www-video=yes$suffix";
	}
	
	$array["{graphs}"]="$page?www-graĥs=yes$suffix";
	$array["{videos}"]="$page?www-table=yes$suffix";
	$array["{members}"]="$page?www-members=yes$suffix";
	echo $title.$boot->build_tab($array);

}
function section_video(){
	$boot=new boostrap_form();
	call_user_func(BECALL);
	echo $boot->SearchFormGen("uid,ipaddr,MAC","video-search",suffix());	
	
	
}
function section_members(){
	$boot=new boostrap_form();
	call_user_func(BECALL);
	echo $boot->SearchFormGen("uid,ipaddr,MAC","members-search",suffix());	
}


function section_table(){
	$boot=new boostrap_form();
	call_user_func(BECALL);
	echo $boot->SearchFormGen("title,uid,ipaddr","items",suffix());
	
}

function section_graphs(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$ff=time();
	$suffix=suffix();
	$html="
	<div id='graph-$ff' style='width:99%;height:450px'></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
		AnimateDiv('graph-$ff');
		AnimateDiv('graph2-$ff');
		AnimateDiv('graph3-$ff');
		Loadjs('$page?graph=yes$suffix&container=graph-$ff');
		Loadjs('$page?graph2=yes$suffix&container=graph2-$ff');
		Loadjs('$page?graph3=yes$suffix&container=graph3-$ff');
		
	</script>	
	
	";	
	
	echo $html;
	
}

function video_search(){
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
	$sql="SELECT * FROM $hourtable WHERE youtubeid='{$_GET["youtubeid"]}' AND `hour`='{$_GET["hour"]}' ORDER BY uid ASC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
	$youtube=new YoutubeStats();
	$videotitle=$youtube->youtube_title($_GET["youtubeid"]);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$youtubeid=$ligne["youtubeid"];
		$hits=FormatNumber($ligne["hits"]);
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$link=$boot->trswitch($urljsSIT);
		$tr[]="
		<tr>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</td>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["MAC"]}</td>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["ipaddr"]}</td>
		<td nowrap><i class='icon-user'></i>&nbsp;$hits</td>
		</tr>";
	}
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{member}</th>
			<th>{MAC}</th>
			<th>{ipaddr}</th>
			<th>{hits}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>";	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function members_search(){
	$users=new usersMenus();
	SEND_CORP_LICENSE();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("search-members");
	$xtime=$_GET["xtime"];
	$hourtable=$tablemain="youtubeday_".date("Ymd",$_GET["xtime"]);
	
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(youtubeid) as hits,uid,MAC,ipaddr,`hour` FROM $hourtable 
	GROUP BY uid,MAC,ipaddr,`hour`
	HAVING `hour`='{$_GET["hour"]}' ORDER BY hits ASC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
	$youtube=new YoutubeStats();
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$youtubeid=$ligne["youtubeid"];
		$hits=FormatNumber($ligne["hits"]);
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$link=$boot->trswitch($urljsSIT);
		$tr[]="
		<tr>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</td>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["MAC"]}</td>
		<td $link><i class='icon-user'></i>&nbsp;{$ligne["ipaddr"]}</td>
		<td nowrap><i class='icon-user'></i>&nbsp;$hits</td>
		</tr>";
	}
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{member}</th>
			<th>{MAC}</th>
			<th>{ipaddr}</th>
			<th>{videos}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>";
		
}

	
	
function webstats_middle_table_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
	//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$video=$tpl->_ENGINE_parse_body("{video}");
	$uid=$tpl->_ENGINE_parse_body("{account}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");	
	
	
	$search='%';
	$tablemain="youtubeday_".date("Ymd",$_GET["xtime"]);
	$table="(SELECT SUM( hits ) AS hits, ipaddr, hostname, uid, MAC, youtubeid, title
FROM (
	SELECT $tablemain.hits AS hits, $tablemain.ipaddr, $tablemain.hostname,
	$tablemain.uid, $tablemain.MAC, $tablemain.youtubeid, youtube_objects.title
	FROM `$tablemain` , `youtube_objects`
	WHERE youtube_objects.youtubeid = $tablemain.youtubeid
	) AS t1
GROUP BY ipaddr, hostname, uid, MAC, youtubeid, title) as t";
	
	
	
	
	$page=1;
	$FORCE_FILTER=null;
	
	
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery("items");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER ORDER BY hits DESC LIMIT 0,250";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error);}

	
	$boot=new boostrap_form();
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";

	
	//familysite 	size 	hits
	
	$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid={$ligne["youtubeid"]}&xtime={$_GET["xtime"]}');";
	
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$ligne["familysite"]=$q->GetFamilySites($ligne["sitename"]);
	$truri=$boot->trswitch($urljsSIT);
	$jsuid=null;
	if($ligne["uid"]<>null){
		$jsuid=$boot->trswitch("Loadjs('miniadm.webstats.ByMember.youtube.Byday.php?uid={$ligne["uid"]}&by=uid&xtime={$_GET["xtime"]}')");
		$ligne["uid"]="<i class='icon-user'></i>&nbsp;{$ligne["uid"]}";
		
	
	}
	$tr[]="
	<tr>
		<td $truri><img src='miniadm.webstats.youtube.php?thumbnail={$ligne["youtubeid"]}'></td>
		<td $truri><i class='icon-facetime-video'></i>&nbsp;{$ligne["title"]}</td>
		<td nowrap $jsuid>{$ligne["uid"]}</td>
		<td nowrap $jsuid><i class='icon-user'></i>&nbsp;{$ligne["ipaddr"]}</td>
		<td nowrap $jsuid><i class='icon-user'></i>&nbsp;{$ligne["hostname"]}</td>
		<td nowrap $jsuid><i class='icon-user'></i>&nbsp;{$ligne["MAC"]}</td>
		<td width=1% align=center>{$ligne["hits"]}</td>
	</tr>
	";
	}
	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'>
		<thead>
			<tr>
			<th colspan=2>$video</th>
			<th>{member}</th>
			<th>{ipaddr}</th>
			<th>{hostname}</th>
			<th>{MAC}</th>
			<th>{hits}</th>
			</tr>
		</thead>
		<tbody>
				 ").@implode("\n", $tr)." 
		</tbody>
	</table>";
	
	
	
	
	
	
}
function generate_graph(){
	include_once('ressources/class.artica.graphs.inc');
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$xtime=$_GET["xtime"];
	$tablename="youtubeday_".date("Ymd",$xtime);
	

	$sql="(SELECT $tablename.hits as hits,$tablename.hour as hour, youtube_objects.category FROM `$tablename`,`youtube_objects`
	WHERE youtube_objects.youtubeid=$tablename.youtubeid AND $tablename.hour='{$_GET["hour"]}') as t";
	
	$sql="SELECT SUM(hits) as thits,category FROM $sql GROUP BY category ORDER BY thits DESC LIMIT 0,10" ;
	

	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)>0){
	

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
			$PieData[$ligne["category"]]=$ligne["thits"];
			$c++;
		}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{categories}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {categories}/{hits} ({$_GET["hour"]}h)");
	echo $highcharts->BuildChart();
	}
}
function generate_graph2(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$xtime=$_GET["xtime"];
	$tablename="youtubeday_".date("Ymd",$xtime);


	$sql="SELECT COUNT(youtubeid) as hits,uid,`hour` FROM `$tablename` GROUP BY uid,`hour` 
	HAVING `hour`='{$_GET["hour"]}'
	ORDER BY hits DESC LIMIT 0,15";




	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	if(mysql_num_rows($results)>0){


		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$PieData[$ligne["uid"]]=$ligne["hits"];
			$c++;
		}

		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->PieDatas=$PieData;
		$highcharts->ChartType="pie";
		$highcharts->PiePlotTitle="{videos}";
		$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {top_members}/{videos}");
		echo $highcharts->BuildChart();
	}

}
function generate_graph3(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$xtime=$_GET["xtime"];
	$tablename="youtubeday_".date("Ymd",$xtime);
	$youtube=new YoutubeStats();

	$sql="SELECT SUM(hits) as hits,youtubeid,`hour` FROM `$tablename` GROUP BY youtubeid,`hour`
	HAVING `hour`='{$_GET["hour"]}'
	ORDER BY hits DESC LIMIT 0,15";




	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	if(mysql_num_rows($results)>0){


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$PieData[substr($youtube->youtube_title($ligne["youtubeid"]),0,20)]=$ligne["hits"];
			$c++;
	}

			$highcharts=new highcharts();
			$highcharts->container=$_GET["container"];
			$highcharts->PieDatas=$PieData;
			$highcharts->ChartType="pie";
			$highcharts->PiePlotTitle="{hits}";
			$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {top_videos}/{hits}");
			echo $highcharts->BuildChart();
	}

}





