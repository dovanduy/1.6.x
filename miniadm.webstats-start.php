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

if(isset($_GET["cached-graphs-js"])){cached_graph_js();exit;}
if(isset($_GET["cached-graphs-popup"])){cached_graph_popup();exit;}
if(isset($_GET["tabs-translate"])){tabs_translate();exit;}
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
	$users=new usersMenus();
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

	if($users->PROXYTINY_APPLIANCE){
		$jsadd="LoadAjax('statistics-$t','miniadm.webstats.sarg.php?tabs=yes');";
		$mainjs=null;
		$error=null;
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

function cached_graph_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{statistics}");
	echo "YahooWin2(1200,'$page?cached-graphs-popup=yes','$title')";
	
}

function tabs_translate(){
	$sock=new sockets();
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	$boot=new boostrap_form();
	
	if($EnableMacAddressFilter==1){
		$array["{MACtoMembers}"]="miniadm.squid.MacToMembers.php";
	}	
	
	$array["{IPtoMembers}"]="miniadm.squid.IPToMembers.php";
	echo $boot->build_tab($array);
	
}

function tabs(){
	$sock=new sockets();
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	$SQUID_LOCAL_STATS_DISABLED=$sock->SQUID_LOCAL_STATS_DISABLED();
	
	if($SQUID_LOCAL_STATS_DISABLED){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{SQUID_LOCAL_STATS_DISABLED}</p>");
		
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	if(!$SQUID_LOCAL_STATS_DISABLED){
		$array["{statistics}"]="$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}";
		$array["{general_settings}"]="$page?settings=yes";
	}
	$array["{users_translation}"]="$page?tabs-translate=yes";
	
	$YoutubeCount=$q->COUNT_ROWS("youtube_objects");
	if($YoutubeCount>0){
		$array["$YoutubeCount {youtube_videos}"]="miniadm.webstats.youtube.php?master-content=yes=yes&title=yes";
	}
	
	$array["{APP_SARG}"]="miniadm.webstats.sarg.php?tabs=yes";
	
	
	echo $boot->build_tab($array);	
	
	//LoadAjax('webstats-left','$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');
	
}

function settings(){
	$page=CurrentPageName();
	$array["{parameters}: {statistics}"]="$page?settings-stats=yes";
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
	
	$cached=unserialize(@file_get_contents(dirname(__FILE__)."/ressources/logs/web/SQUID_STATS_GLOBALS_VALUES"));
	
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");
	
	$timefile=dirname(__FILE__)."/ressources/logs/web/SQUID_STATS_GLOBALS_VALUES";
	
	
	$NotCategorized=$q->COUNT_ROWS("notcategorized");

	if($NotCategorized>0){
		$tR[]=stats_paragraphe($NotCategorized,"{not_categorized}", "{not_categorized_explain_why}",
				"document.location.href='miniadm.webstats.notcategorized.php'","spider-warn-database-64.png");
		
	}		
	

	$q=new mysql_squid_builder();
	$DAYSNumbers=$q->COUNT_ROWS("tables_day");
	

	
$tR[]=stats_paragraphe($DAYSNumbers,"{daysOfStatistics}", "{statistics_by_date}<br>{statistics_by_date_text}", 
		"document.location.href='miniadm.webstats.Bydays.php'","calendar-64.png");






$tR[]=stats_paragraphe($cached["CountDeMembers"],"{members}", "{member_www_stats_text}",
		"document.location.href='miniadm.webstats.members2.php'","canonical-64.png");


$CountDeWebsites=$q->COUNT_ROWS("visited_sites_tot"); 
$tR[]=stats_paragraphe($CountDeWebsites,"{websites}", "{visited_sites_days_text}",
		"document.location.href='miniadm.webstats.bywebsites.php'","domain-main-64.png");


//$tR[]=Paragraphe32("member_wwwtrack", "member_wwwtrack_text",	"document.location.href='miniadm.MembersTrack.php'","unknown-user-48.png",320);


$sock=new sockets();
$users=new usersMenus();
$boot=new boostrap_form();
$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
if(!$users->CORP_LICENSE){$ArticaProxyStatisticsBackupDays=5;}


$tR[]=stats_paragraphe($cached["AVG_CACHED"],"{cached_data_avg}", "{cached_data_graph_text}",
		"Loadjs('$page?cached-graphs-js=yes')","64-hd-stats.png");


$FAMS=$boot->SQUID_CATEGORIES_FAM;


$tR[]=stats_paragraphe($ArticaProxyStatisticsBackupDays,"{days} {retention_time}", "
		{database_size}: (".$cached["DATABASE_INFOS"].")<br>
		{database_maintenance_text}",
		"document.location.href='miniadm.squiddb.php'","database-check.png");


if($cached["CATFAM"][1]>0){
	$tR[]=stats_paragraphe($cached["CATFAM"][1],"{dangerous_websites} {this_month}", "
		{dangerous_websites_fam_explain}",
		"document.location.href='miniadm.webstats.fam.php?catfam=1&xtime=".time()."'",
		"bug-warning-64.png");
}
if($cached["CATFAM"][2]>0){
	$tR[]=stats_paragraphe($cached["CATFAM"][2],"{websites_network_pollution} {this_month}", "
		{websites_network_pollution_explain}",
			"document.location.href='miniadm.webstats.fam.php?catfam=2&xtime=".time()."'",
			"stop-ads-64.png");
}
if($cached["CATFAM"][3]>0){
	$tR[]=stats_paragraphe($cached["CATFAM"][3],"{websites_human_suspects} {this_month}", "
		{websites_human_suspects_explain}",
			"document.location.href='miniadm.webstats.fam.php?catfam=3&xtime=".time()."'",
			"user-error-64.png");
}
if($cached["CATFAM"][4]>0){
	$tR[]=stats_paragraphe($cached["CATFAM"][4],"{websites_heavy_cat} {this_month}", "
		{websites_heavy_cat_explain}",
			"document.location.href='miniadm.webstats.fam.php?catfam=4&xtime=".time()."'",
			"64-download.png");
}
if($cached["CATFAM"][5]>0){
	$tR[]=stats_paragraphe($cached["CATFAM"][5],"{websites_noprod} {this_month}", "
		{websites_noprod_explain}",
			"document.location.href='miniadm.webstats.fam.php?catfam=5&xtime=".time()."'",
			"domain-whitelist-64.png");
}

$ff=time();
	$content=CompileTr3($tR,"none");
	$html="
	<div class=BodyContent>$content</div>
	
		
		
	</div>
		

	
	";
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}

function cached_graph_popup(){
	$ff=time();
	$page=CurrentPageName();
	$html="<div id='generic-values-$ff'></div>	<script>
		LoadAjax('generic-values-$ff','$page?generic-values-tabs=yes');
	</script>";
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



