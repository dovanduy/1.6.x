<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){die("oups");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["www-graĥs"])){section_graphs();exit;}
if(isset($_GET["graph"])){generate_graph();exit;}
if(isset($_GET["graph2"])){generate_graph2();exit;}
if(isset($_GET["graph3"])){generate_graph3();exit;}
if(isset($_GET["graph4"])){generate_graph4();exit;}
if(isset($_GET["www-table"])){section_table();exit;}
if(isset($_GET["www-hits"])){hits_search();exit;}



if(isset($_GET["webstats_middle_table"])){webstats_middle_table();exit;}
if(isset($_GET["items"])){webstats_middle_table_items();exit;}

if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}	
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	call_user_func(BECALL);

	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes');";
	
	$q=new mysql_squid_builder();
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];
			
		$SumSize=FormatBytes($SumSize/1024);
		$SumHits=numberFormat($SumHits,0,""," ");
		
		
		$dateT=date("{l} {F} d",$_GET["xtime"]);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}
		
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		</div>
		<H1>&laquo;$YouTubeHits&raquo; {youtube_videos}</H1>
		<p>$dateT: {display_youtube_for_this_day}</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function suffix(){
	$t=$_GET["t"];
	return "&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&tablename={$_GET["tablename"]}&xtime={$_GET["xtime"]}";
}

function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$boot=new boostrap_form();
	
	call_user_func(BECALL);
	$title=null;
	$suffix=suffix();
	if(isset($_GET["title"])){
		
		$dateT=time_to_date($_GET["xtime"]);
		$title=$tpl->_ENGINE_parse_body("<H4>&laquo;$dateT&raquo; {youtube_videos}</H4>
		<p>{display_youtube_for_this_day}</p>");
		
	}
	
	

	$_GET["uid"]=urlencode($_GET["uid"]);
	$fsite=urlencode($_GET["familysite"]);
	$array["{graphs}"]="$page?www-graĥs=yes$suffix";
	$array["{videos}"]="$page?www-table=yes$suffix";
	$array["{hits}"]="$page?www-hits=yes$suffix";
	echo $title.$boot->build_tab($array);

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
	<table style='width:950px' class=TableRemove>
	<tr>
	<td valign='top'><div id='graph-$ff' style='width:450px;height:450px'></div></td>
	<td valign='top'><div id='graph2-$ff' style='width:450px;height:450px'></div></td>
	</tr>
	<tr>
	<td align='center' style='text-align:center'><div id='graph3-$ff' style='width:450px;height:450px'></div></td>
	<td align='center' style='text-align:center'><div id='graph4-$ff' style='width:450px;height:450px'></div></td>
	</tr>
	</table>
	
	<script>
		Loadjs('$page?graph=yes$suffix&container=graph-$ff');
		Loadjs('$page?graph2=yes$suffix&container=graph2-$ff');
		Loadjs('$page?graph3=yes$suffix&container=graph3-$ff');
		Loadjs('$page?graph4=yes$suffix&container=graph4-$ff');
		LoadAjax('table-$ff','$page?webstats_middle_table=yes$suffix');
	</script>	
	
	";	
	
	echo $html;
	
}

function hits_search(){
	$tablename="youtubeday_".date("Ymd",$_GET["xtime"]);
	$sql="SELECT COUNT(youtubeid) as hits,`hour` FROM `$tablename` GROUP BY `hour` ORDER BY `hour` DESC";
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error);}
	
	
	$boot=new boostrap_form();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$color="black";
	
	
		//familysite 	size 	hits
	
		$urljsSIT="Loadjs('miniadm.webstats.ByHourYoutube.php?xtime={$_GET["xtime"]}&hour={$ligne["hour"]}');";
		$truri=$boot->trswitch($urljsSIT);
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$tr[]="
		<tr $truri>
		<td ><i class='icon-time'></i>&nbsp;{$ligne["hour"]}h</td>
		<td width=1% align=center>{$ligne["hits"]}</td>
		</tr>
		";
	}
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th>{hour}</th>
			<th>{hits}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)."
			</tbody>
			</table>";
	
		
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
function generate_graph2(){
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
	

	$sql="(SELECT $tablename.hits as hits, youtube_objects.category FROM `$tablename`,`youtube_objects`
	WHERE youtube_objects.youtubeid=$tablename.youtubeid) as t";
	
	$sql="SELECT SUM(hits) as thits,category FROM $sql GROUP BY category ORDER BY thits DESC LIMIT 0,10" ;
	

	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error."<br>$sql",$_GET["container"]);
	}	
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {categories}/{hits}");
	echo $highcharts->BuildChart();
	}
}

function generate_graph3(){
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
	
	
	$sql="SELECT SUM(hits) as hits,uid FROM `$tablename` GROUP BY uid
	ORDER BY hits DESC LIMIT 0,15";
	
	
	
	
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error."<br>$sql",$_GET["container"]);
	}
	if(mysql_num_rows($results)>0){
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$PieData[$ligne["uid"]]=$ligne["hits"];
			$c++;
		}
	
		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->PieDatas=$PieData;
		$highcharts->ChartType="pie";
		$highcharts->PiePlotTitle="{members}";
		$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {top_members}/{hits} ({{$_GET["hour"]}})");
		echo $highcharts->BuildChart();
	}
}

function generate_graph4(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$graph1=null;
	$graph2=null;
	$xtime=$_GET["xtime"];
	$tablename="youtubeday_".date("Ymd",$xtime);
	
	
	$sql="SELECT COUNT(youtubeid) as hits,uid FROM `$tablename` GROUP BY uid ORDER BY hits DESC LIMIT 0,15";
	
	
	
	
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error."<br>$sql",$_GET["container"]);
	}
	if(mysql_num_rows($results)>0){
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$PieData[$ligne["uid"]]=$ligne["hits"];
			$c++;
		}
	
		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->PieDatas=$PieData;
		$highcharts->ChartType="pie";
		$highcharts->PiePlotTitle="{members}";
		$highcharts->Title=$tpl->_ENGINE_parse_body("Youtube: {top_members}/{videos}");
		echo $highcharts->BuildChart();
	}	
	
}


function generate_graph(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$xtime=$_GET["xtime"];
	$tablename="youtubeday_".date("Ymd",$xtime);

	$sql="SELECT SUM(hits) as thits, hour FROM $tablename GROUP BY hour ORDER BY hour";
	
	 
	
	$c=0;
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error."<br>$sql",$_GET["container"]);
	}
	if(mysql_num_rows($results)>0){
	
			$nb_events=mysql_num_rows($results);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$xdata[]=$ligne["hour"];
				$ydata[]=$ligne["thits"];
				
			$c++;
		
	}	
				
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="Youtube: {statistics} ". $tpl->_ENGINE_parse_body("{hits}/{hours}");
	$highcharts->yAxisTtitle="{requests}";
	$highcharts->xAxisTtitle="{hours}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();				
				
	}
	
}

