<?php
session_start();$_SESSION["MINIADM"]=true;

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-left"])){webstats_left();exit;}
if(isset($_GET["webstats-stats"])){webstats_stats();exit;}
if(isset($_GET["navcalendar"])){build_calendar();exit;}
if(isset($_GET["build-calendar"])){build_calendar();exit;}
if(isset($_GET["graph0"])){graph0();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["search-www"])){search_websites();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["WebstatisticsByMember"])){settings_save();exit;}
if(isset($_GET["settings-stats"])){settings_stats();exit;}
if(isset($_GET["settings-db"])){settings_db();exit;}
if(isset($_GET["settings-retention"])){settings_retention();exit;}
if(isset($_POST["ArticaProxyStatisticsBackupFolder"])){settings_retention_save();exit;}
if(isset($_GET["generic-values-tabs"])){generic_values_tabs();exit;}
if(isset($_GET["generic-section1"])){generic_section1();exit;}
if(isset($_GET["generic-categories"])){generic_categories();exit;}
if(isset($_GET["generic-categories-graphs"])){generic_categories_graphs();exit;}
if(isset($_GET["generic-categories-table"])){generic_categories_table();exit;}
if(isset($_GET["generic-categories-table-search"])){generic_categories_table_search();exit;}



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
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	
	$mainjs="LoadAjax('webstats-left','$page?tabs=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{DisableArticaProxyStatistics_disabled_explain}</p>
				<center style='margin:30px;font-size:18px;text-decoration:underline'>
				<a href=\"javascript:Loadjs('squid.artica.statistics.php')\">{ARTICA_STATISTICS_TEXT}</a>
				</center>
				");
		$mainjs=null;
	}	
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{web_statistics}</H1>
		<p>{web_statistics_member_text}</p>
		<div id='statistics-$t'></div>
	</div>	$error
	<div id='webstats-left' style='margin-top:15px'></div>
	
	<script>
		$mainjs
		$jsadd
	</script>
	";
		
	$html=$tpl->_ENGINE_parse_body($html);
	
	echo $html;
}

function generic_values_tabs(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{days}"]="$page?generic-section1=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}";
	$array["{categories}"]="$page?generic-categories=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}";
	echo $boot->build_tab($array);	
	
}

function tabs(){
	$sock=new sockets();
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$array["{statistics}"]="$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}";
	$array["{general_settings}"]="$page?settings=yes";
	if($EnableMacAddressFilter==1){
		$array["{MACtoMembers}"]="miniadm.squid.MacToMembers.php";
	}
	
	$YoutubeCount=$q->COUNT_ROWS("youtube_objects");
	if($YoutubeCount>0){
		$array["$YoutubeCount {youtube_videos}"]="miniadm.webstats.youtube.php?master-content=yes=yes&title=yes";
	}
	
	$array["{source_logs}"]="miniadm.webstats.logrotate.php";
	
	echo $boot->build_tab($array);	
	
	//LoadAjax('webstats-left','$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');
	
}

function settings(){
	$page=CurrentPageName();
	$array["{parameters}: {statistics}"]="$page?settings-stats=yes";
	$sock=new sockets();
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	if(is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	$users=new usersMenus();
	

	if($ProxyUseArticaDB==1){
		$array["{mysql_statistics_engine}"]="miniadm.proxy.mysql.database.php?tabs=yes&title=yes";
	}
	$array["{database_maintenance}"]="$page?settings-db=yes";	
	$array["{APP_ARTICADB}"]="miniadm.proxy.category.database.php?tabs=yes&title=yes";
	
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}

function settings_db(){
	$page=CurrentPageName();
	$array["{retention_time}"]="$page?settings-retention=yes";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);	
	
}


function settings_stats(){	
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();	
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	$WebstatisticsByMember=$sock->GET_INFO("WebstatisticsByMember");
	$PerMembersYoutubeDetails=$sock->GET_INFO("PerMembersYoutubeDetails");
	if(!is_numeric($PerMembersYoutubeDetails)){$PerMembersYoutubeDetails=0;}
	if(!is_numeric($WebstatisticsByMember)){$WebstatisticsByMember=0;}
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}	
	
	$boot=new boostrap_form();
	$boot->set_checkbox("WebstatisticsByMember", "{WebstatisticsByMember}", $WebstatisticsByMember);
	$boot->set_checkbox("PerMembersYoutubeDetails", "{PerMembersYoutubeDetails}", $PerMembersYoutubeDetails);
	
	
	$boot->set_checkbox("EnableMacAddressFilter", "{enable_mac_squid_filters}", $EnableMacAddressFilter);
	
	$boot->set_button("{apply}");
	echo $boot->Compile();
}
function settings_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMacAddressFilter", $_POST["EnableMacAddressFilter"]);
	$sock->SET_INFO("WebstatisticsByMember", $_POST["WebstatisticsByMember"]);
	$sock->SET_INFO("PerMembersYoutubeDetails", $_POST["PerMembersYoutubeDetails"]);
	
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
	

	if($NotCategorizedTests>0){
		$NotCategorizedTests=numberFormat($NotCategorizedTests,0,""," ");
		$tR[]=Paragraphe("spider-database-64.png", "&laquo;$NotCategorizedTests&raquo; {not_categorized}",
		"{display_not_categorized_tests}","miniadm.webstats.not.categorized.php");
	}		
	
$tR[]=Paragraphe32("statistics_by_date", "statistics_by_date_text", 
		"document.location.href='miniadm.webstats.Bydays.php'", "statistics2-32.png",320);

$tR[]=Paragraphe32("member_wwwtrack", "member_wwwtrack_text",
	"document.location.href='miniadm.MembersTrack.php'","unknown-user-48.png",320);

if($q->COUNT_ROWS("members_uid")>0){
		$tR[]=Paragraphe32("members", "member_www_stats_text",
		"document.location.href='miniadm.webstats.members2.php'","member-32.png",320);	
}

$tR[]=Paragraphe32("database_maintenance", "database_maintenance_text",
		"document.location.href='miniadm.squiddb.php'","datasource-32.png",320);


$ff=time();
	$content=CompileTr2($tR,"none");
	$html="
	<div class=BodyContent>
		<table style='width:100%'>
		<tr>
			<td valign='top'>
					<center>$content</center>
			</td>
			<td valign='top' style='vertical-align:top';>
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
		<div id='generic-values-$ff'></div>
		
	</div>
		
	<script>
		LoadAjax('generic-values-$ff','$page?generic-values-tabs=yes');
		
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

	</script>
	
	";
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}

function generic_section1(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$RUNGRAPH=1;
	$ff=time();
	
	if(!$q->TABLE_EXISTS("visited_sites_days")){
		$q->CheckTables();
		$RUNGRAPH=0;
	}
	$cached_total=1;
	$cached_total_div="<div id='$ff-graph0' style='height:450px' class=BodyContent></div>";
	
	
	if($q->COUNT_ROWS("visited_sites_days")<2){$RUNGRAPH=0;}
	if($q->COUNT_ROWS("cached_total")<2){$cached_total=0;$cached_total_div=null;}
	
	if($RUNGRAPH==0){
		$sock=new sockets();
		$sock->getFrameWork("squidstats.php?alldays=yes");
	}
	
	
	
$html="
$cached_total_div
<div id='$ff-graph' style='height:450px' class=BodyContent></div>
<div id='$ff-graph2' style='height:450px' class=BodyContent></div>

<script>
function RUNGRAPH$ff(){
			var RUNGRAPH=$RUNGRAPH;
			var cached_total=$cached_total;
			
			if(cached_total==1){
				AnimateDiv('$ff-graph0');
				Loadjs('$page?graph0=yes&container=$ff-graph0');
			}
			
			if(RUNGRAPH==0){ return;}
			AnimateDiv('$ff-graph');
			AnimateDiv('$ff-graph2');
			Loadjs('$page?graph1=yes&container=$ff-graph');
			Loadjs('$page?graph2=yes&container=$ff-graph2');
		}
		
		RUNGRAPH$ff();
</script>
";	
echo $html;	
}

function generic_categories(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	
	$array["{graphs}"]="$page?generic-categories-graphs=yes";
	$array["{table}"]="$page?generic-categories-table=yes";
	echo $boot->build_tab($array);
		
	
	
}


function generic_categories_graphs(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$RUNGRAPH=1;
	$ff=time();
	$html="<div id='$ff-graph' style='height:450px' class=BodyContent></div>
	<div id='$ff-graph2' style='height:450px' class=BodyContent></div>
	
	<script>
	function RUNGRAPH$ff(){
		AnimateDiv('$ff-graph');
		AnimateDiv('$ff-graph2');
		Loadjs('$page?graph3=yes&container=$ff-graph');
		Loadjs('$page?graph4=yes&container=$ff-graph2');
	}
	
	RUNGRAPH$ff();
	</script>
	";
	echo $html;	
	
}

function generic_categories_table(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	echo $boot->SearchFormGen("category","generic-categories-table-search");
}

function generic_categories_table_search(){
	$q=new mysql_squid_builder();
	
	$searchstring=string_to_flexquery("generic-categories-table-search");
	$boot=new boostrap_form();
	$tpl=new templates();
	$sql="SELECT SUM( size ) AS size,SUM(hits) as hits, category FROM generic_categories GROUP BY category 
			HAVING LENGTH(category)>1 $searchstring ORDER BY size DESC,hits DESC";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){senderror($q->mysql_error);}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		
		$js="Loadjs('miniadm.webstats.websites.ByCategory.php?category=".urlencode($ligne["category"])."')";
		$link=$boot->trswitch($js);
		
		$tr[]="<tr $link>
				
		<td width=99% style='font-size:18px'>{$ligne["category"]}</td>
		<td width=1% nowrap style='font-size:18px'>$size</td>
		<td width=1% nowrap style='text-align:right;font-size:18px'>$hits</td>
		</tr>
		";
	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>{category}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>";	
	
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

function graph0(){
	$q=new mysql_squid_builder();
	$sql="SELECT size AS size, zDate FROM `cached_total` WHERE cached=1 ORDER BY zDate";
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
	$highcharts->Title="{cached_requests_by_day}";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
	
	
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
	$sql="SELECT SUM( hits ) AS size, zDate
FROM `visited_sites_days`
GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{requests_by_day}";
	$highcharts->yAxisTtitle="{size}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();

}
function graph3(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM( size ) AS size, category FROM generic_categories GROUP BY category HAVING LENGTH(category)>1 ORDER BY size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round((($ligne["size"]/1024)/1000)/1000);
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
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{size} (GB)");
	echo $highcharts->BuildChart();


}
function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM( hits ) AS size, category FROM generic_categories GROUP BY category HAVING LENGTH(category)>1  ORDER BY size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
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
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{hits}");
	echo $highcharts->BuildChart();


}

function graph5(){
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

function settings_retention(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if($users->CORP_LICENSE){$LICENSE=1;}else{$LICENSE=0;}
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	$q=new mysql_squid_builder();
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
		$ArticaProxyStatisticsBackupDays=5;}
	$t=time();
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	
	if($EnableSquidRemoteMySQL==1){
		$EnableSquidRemoteMySQL_text="{EnableSquidRemoteMySQL_text}";
	}	

	$lock=false;
	$boot=new boostrap_form();
	
	$boot->set_formdescription($EnableSquidRemoteMySQL_text."<br>{purge_statistics_database_explain2}");
	$boot->set_field("ArticaProxyStatisticsBackupFolder", "{backup_folder}", $ArticaProxyStatisticsBackupFolder,array("BROWSE"=>true));
	$boot->set_field("ArticaProxyStatisticsBackupDays", "{max_days}", $ArticaProxyStatisticsBackupDays,array("BROWSE"=>true));
	$boot->set_button("{apply}");
	$boot->set_formtitle("{purge_statistics_database}");
	if(!$users->CORP_LICENSE){$boot->set_form_locked();$lock=true;}
	if($EnableSquidRemoteMySQL==1){$boot->set_form_locked();$lock=true;}
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	if(!$lock){
		$boot->set_Newbutton("{new_schedule}", "YahooWin3('650','squid.databases.schedules.php?AddNewSchedule-popup=yes&ID=0&t=$t&ForceType=47&YahooWin=3&jsback=ReloadSchedules$t','$new_schedule')");
		$ReloadSchedules="ReloadSchedules$t()";
	}
	
	$form=$boot->Compile();
	
	$html="
		
		<div id='title-$t'></div>
		$error
		$form
		<div id='schedules-$t'></div>
	
	<script>
	function ReloadSchedules$t(){
			LoadAjax('schedules-$t','squid.artica.statistics.purge.php?schedules=yes');
		}
		
	function RefreshTableTitle$t(){
		LoadAjaxTiny('title-$t','squid.artica.statistics.purge.php?title=yes&t=$t');
	}
	RefreshTableTitle$t();
	$ReloadSchedules;
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function settings_retention_save(){
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	if($users->CORP_LICENSE){
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays", $_POST["ArticaProxyStatisticsBackupDays"]);
	}else{
		echo $tpl->javascript_parse_text("{no_license_backup_max5}",1);
		$sock->SET_INFO("ArticaProxyStatisticsBackupDays",5);
	
	}
	$sock->SET_INFO("ArticaProxyStatisticsBackupFolder", $_POST["ArticaProxyStatisticsBackupFolder"]);	
	
}