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


function buildiconsof_week(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sql="SELECT SUM(totalBlocked) as totalBlocked,AVG(MembersCount) as MembersCount,SUM(requests) as requests,
	SUM(totalsize) as totalsize,
	SUM(not_categorized) as not_categorized,
	SUM(YouTubeHits) as YouTubeHits
	FROM tables_day WHERE WEEK(zDate)='{$_GET["week"]}' AND YEAR(zDate)={$_GET["year"]}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	
	if(!$q->ok){
		echo "<H2>$q->mysql_error</H2>";
	}
	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=round($ligne["MembersCount"]);
	$YouTubeHits=$ligne["YouTubeHits"];
	$BlockedCount=$ligne["totalBlocked"];	
	
	if($NotCategorized>0){
		$tR[]=Paragraphe("tables-failed-64.png", "$NotCategorized {not_categorized}",
		"{display_not_categorized_websites}","javascript:Loadjs('squid.visited.php?week={$_GET["week"]}&onlyNot=yes')");		
	}
	$SumSize=FormatBytes($SumSize/1024);
	$SumHits=numberFormat($SumHits,0,""," ");
	$tR[]=Paragraphe("64-webscanner.png", "$SumSize {downloaded_size}",
	"$SumSize {downloaded_size}, $SumHits {hits}, {display_visited_websites}","miniadm.webstats.websites.byweek.php?week={$_GET["week"]}&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
	
	if($YouTubeHits>0){
			$tR[]=Paragraphe("youtube-64.png", "&laquo;$YouTubeHits&raquo; {youtube_videos}",
			"{display_youtube_for_this_day}","miniadm.webstats.websites.ByWeekYoutube.php?week={$_GET["week"]}&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
		}		
	
	
	if($MembersCount>0){
		$tR[]=Paragraphe("member-64.png", "&laquo;$MembersCount&raquo; {members}",
		"{display_members_for_this_week}","miniadm.webstats.websites.ByWeekMembers.php?week={$_GET["week"]}&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
	}
	

		
		
		if($BlockedCount>0){
			$tR[]=Paragraphe("hearth-blocked-64.png", "&laquo;$BlockedCount&raquo; {blocked_hits}",
			"{display_blocked_events}","miniadm.webstats.websites.ByWeekBlocked.php?week={$_GET["week"]}&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
		}

		
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");
	if($NotCategorizedTests>0){
		$tR[]=Paragraphe("spider-database-64.png", "&laquo;$NotCategorizedTests&raquo; {not_categorized}",
		"{display_not_categorized_tests}","miniadm.webstats.not.categorized.php");
	}		
	
	
	
	$html=$tpl->_ENGINE_parse_body("<div style='width:700px'>".CompileTr2($tR,"none")."</div>");
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
	
}



function webstats_left(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");

	if($NotCategorizedTests>0){
		$NotCategorizedTests=numberFormat($NotCategorizedTests,0,""," ");
		$tR[]=Paragraphe("spider-database-64.png", "&laquo;$NotCategorizedTests&raquo; {not_categorized}",
		"{display_not_categorized_tests}","miniadm.webstats.not.categorized.php");
	}		
	
$tR[]=Paragraphe("statistics-64.png", "{statistics_by_date}",
	"{statistics_by_date_text}","miniadm.webstats.php");
		
$tR[]=Paragraphe("unknown-user-64.png", "{member_wwwtrack}",
	"{member_wwwtrack_text}","miniadm.MembersTrack.php");

	$content=CompileTr3($tR,"none");
	$html="<div class=BodyContent><center><div style='width:700px'>$content</div></center></div>";
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


function buildiconsof(){
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];
	$BlockedCount=$ligne["totalBlocked"];
	

	$SumSize=FormatBytes($SumSize/1024);
	$SumHits=numberFormat($SumHits,0,""," ");
	$tR[]=Paragraphe("64-webscanner.png", "$SumSize {downloaded_size}",
	"$SumSize {downloaded_size}, $SumHits {hits}, {display_visited_websites}","miniadm.webstats.websites.byday.php?month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
	
	if($YouTubeHits>0){
			$tR[]=Paragraphe("youtube-64.png", "&laquo;$YouTubeHits&raquo; {youtube_videos}",
			"{display_youtube_for_this_day}","miniadm.webstats.websites.ByDayYoutube.php?month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
		}		
	
	
	if($MembersCount>0){
		$tR[]=Paragraphe("member-64.png", "&laquo;$MembersCount&raquo; {members}",
		"{display_members_for_this_day}","miniadm.webstats.websites.ByDayMembers.php?month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
	}
	
	if($BlockedCount>0){
			$tR[]=Paragraphe("hearth-blocked-64.png", "&laquo;$BlockedCount&raquo; {blocked_hits}",
			"{display_blocked_events}","miniadm.webstats.websites.ByDayBlocked.php?month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime");
	}		
	if($NotCategorizedTests>0){
		$tR[]=Paragraphe("tables-failed-64.png", "$NotCategorizedTests {not_categorized}",
		"{display_not_categorized_websites}","javascript:Loadjs('squid.visited.php?day=". date("Y-m-d",$xtime)."&onlyNot=yes')");		
	}	
	
	
	echo $tpl->_ENGINE_parse_body("<div style='width:700px'>".CompileTr2($tR,"none")."</div>");
	
	echo "<center>". button($tpl->_ENGINE_parse_body("{refresh_summary}"), "Loadjs('squid.refresh.day.summarize.php?xtime=$xtime')","18px")."</center>";
	
	//echo "$tablename - $tablename_blocked - $xtime";
}


