<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-left"])){webstats_left();exit;}
if(isset($_GET["webstats-stats"])){webstats_stats();exit;}
if(isset($_GET["navcalendar"])){build_calendar();exit;}
if(isset($_GET["build-calendar"])){build_calendar();exit;}
if(isset($_GET["buildiconsof"])){buildiconsof();exit;}
if(isset($_GET["buildiconsof-week"])){buildiconsof_week();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["search-www"])){search_websites();exit;}
main_page();

function main_page(){
	//annee=2012&mois=9&jour=22
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}')</script>", $content);
	echo $content;	
}


function content(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{web_statistics}</H1>
		<p>{web_statistics_member_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');
		$jsadd
	</script>
	";
		
	$html=$tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;
}



function search_websites(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();	
	$query=url_decode_special_tool(trim($_GET["pattern"]));
	$query=str_replace("*", "%", $query);
	$sql="SELECT SUM(size) as size, SUM(hits) as hits, familysite
	FROM visited_sites_days GROUP BY familysite HAVING familysite LIKE '$query' ORDER BY size DESC LIMIT 0,20";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$site=$ligne["familysite"];
		$size=FormatBytes($ligne["size"]/1024);
		if(strlen($site)>27){$site=substr($site,0,27)."...";}
		$text=$tpl->_ENGINE_parse_body("<div style='margin-top:10px;font-size:14px'><strong>{size}:$size<br>".FormatNumber($ligne["hits"])." {hits}</div>");
		$len=strlen($site);
		
		$js="Loadjs('squid.website-zoom.php?&sitename=".urlencode($site)."&js=yes')";
		$f[]=Paragraphe32("noacco:$site", $text, $js, "website-32.png");
		
		
	}	
	if(count($f)>0){
		echo $tpl->_ENGINE_parse_body(CompileTr4($f));
	}
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function webstats_left(){
	//if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");
	$RUNGRAPH=1;
	if(!$q->TABLE_EXISTS("visited_sites_days")){
		$q->CheckTables();
		$RUNGRAPH=0;
	}
	if($q->COUNT_ROWS("visited_sites_days")==0){$RUNGRAPH=0;}
	
	if($RUNGRAPH==0){
		$sock=new sockets();
		$sock->getFrameWork("squidstats.php?alldays=yes");
	}

	if($NotCategorizedTests>0){
		$NotCategorizedTests=numberFormat($NotCategorizedTests,0,""," ");
		$tR[]=Paragraphe("spider-database-64.png", "&laquo;$NotCategorizedTests&raquo; {not_categorized}",
		"{display_not_categorized_tests}","miniadm.webstats.not.categorized.php");
	}		
	
$tR[]=Paragraphe32("statistics_by_date", "statistics_by_date_text", "document.location.href='miniadm.webstats.php'", "statistics2-32.png");
$tR[]=Paragraphe32("member_wwwtrack", "member_wwwtrack_text",
	"document.location.href='miniadm.MembersTrack.php'","unknown-user-48.png");

$tR[]=Paragraphe32("database_maintenance", "database_maintenance_text",
		"document.location.href='miniadm.squiddb.php'","datasource-32.png");


$ff=time();
	$content=CompileTr2($tR,"none");
	$html="<div class=BodyContent>
	<table style='width:100%'>
	<tr>
		<td valign='top'>
				<center>$content</center>
		</td>
		<td valign='top'>
			<table style='width:100%'>
			<tr>
				<td><strong>{websites}:</td>
				<td>". Field_text("Search$ff","focus:{search}","font-size:14px",null,null,null,false,"SearchWebSite$ff(event)",false)."</td>
			</tr>
			</tr>
			<td><strong>{members}:</td>
			<td>". Field_text("Search-Memb-$ff","focus:{search}","font-size:14px",null,null,null,false,"SearchMember$ff(event)")."</td>
			
			</table>
		</td>
	</tr>
	</table>
	<div id='SearchWebsites-results-$ff'></div>			
				
	</div>
		<div id='$ff-graph' style='height:450px' class=BodyContent></div>
		<div id='$ff-graph2' style='height:450px' class=BodyContent></div>
	<script>
	
		function RUNGRAPH$ff(){
			var RUNGRAPH=$RUNGRAPH;
			if(RUNGRAPH==0){
				return;
			}
			AnimateDiv('$ff-graph');
			AnimateDiv('$ff-graph2');
			Loadjs('$page?graph1=yes&container=$ff-graph');
			Loadjs('$page?graph2=yes&container=$ff-graph2');
		}
		function SearchWebSite$ff(e){
			if(!checkEnter(e)){return;}
			var pp=encodeURIComponent(document.getElementById('Search$ff').value);
			LoadAjax('SearchWebsites-results-$ff','$page?search-www=yes&pattern='+pp);
		}
		
		function SearchMember$ff(e){
			var pp=encodeURIComponent(document.getElementById('Search-Memb-$ff').value);
			if(!checkEnter(e)){return;}
			Loadjs('squid.UserAuthDaysGrouped.php?search-js='+pp);
		}		
		
		RUNGRAPH$ff();
	</script>
	
	";
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}

function webstats_stats(){
		if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$DAYSNumbers=$q->COUNT_ROWS("tables_day");
		$month=$_GET["month"];
		$year=$_GET["year"];
		$requests=$q->EVENTS_SUM();
		$requests=numberFormat($requests,0,""," ");	
		$virtdates=strtotime("$year-$month-01 00:00:00");
		if(is_numeric($month)){
			$xdate=date("{F}",$virtdates);
			$add1="&nbsp;|&nbsp;<strong>$xdate $year</strong>";
			
		}
		
		if(!isset($_SESSION[date('YmdH')]["add0"])){
			$sql="SELECT COUNT(sitename) as tcount FROM visited_sites WHERE LENGTH(category)=0";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
			if($ligne["tcount"]>0){
			$add0="&nbsp;|&nbsp;<strong><a href=\"miniadm.webstats.notcategorized-days.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">".numberFormat($ligne["tcount"],0,""," ")." {unknown_websites}</a></strong>";
			}
		
		//not_categorized
		
		$_SESSION[date('YmdHi')]["add0"]=$add0;
		}else{
			$add0=$_SESSION[date('YmdHi')]["add0"];
		}
		
		$NotCategorizedTests=$q->COUNT_ROWS("webtests");
		
		
		$html="<b>$requests</b> {requests}&nbsp;|&nbsp;<b>$DAYSNumbers</b> {daysOfStatistics}$add0$add1&nbsp;|&nbsp;{not_categorized}:&nbsp;<b>".numberFormat($NotCategorizedTests,0,""," ")."</b>";
		
		
		
		
		$html= $tpl->_ENGINE_parse_body($html);
		$_SESSION[__FILE__][__FUNCTION__]=$html;
		echo $html;			
	
}


function messaging_right(){
	$sock=new sockets();
	$users=new usersMenus();

	if(count($t)==0){return;}
	$tpl=new templates();
	$html="<div class=BodyContent>".CompileTr2($t,"none")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}




function graph1(){
	$q=new mysql_squid_builder();
	$sql="SELECT SUM( size ) AS size, zDate
FROM `visited_sites_days`
GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["size"]/1024)/1000);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;
	
	}	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{downloaded_size_by_day}";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);	
	echo $highcharts->BuildChart();
	
}
function graph2(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM( size ) AS size, familysite
FROM `visited_sites_days`
GROUP BY familysite ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["size"]/1024)/1000);
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
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{size}");
	echo $highcharts->BuildChart();
	

}