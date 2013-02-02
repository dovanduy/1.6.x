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
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		
		</div>
		<H1>{statistics_by_date}</H1>
		<p>{statistics_by_date_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?webstats-left=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function buildiconsof_week(){
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
	
	
	
	echo $tpl->_ENGINE_parse_body("<div style='width:700px'>".CompileTr2($tR,"none")."</div>");	
	
}



function webstats_left(){
	
	$t=$_GET["t"];
	$year=$_GET["year"];
	$day=$_GET["day"];
	$month=$_GET["month"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	
	if(!is_numeric($month)){
		if(!is_numeric($_GET["week"])){
		$youtube_objects=$q->COUNT_ROWS("youtube_objects");
		$youtube_objects=numberFormat($youtube_objects,0,""," ");
			
		$UserAuthDaysGrouped=$q->COUNT_ROWS("UserAuthDaysGrouped");
		$UserAuthDaysGrouped=numberFormat($UserAuthDaysGrouped,0,""," ");
		$tR[]=Paragraphe("youtube-64.png", "$youtube_objects Youtube {objects}", "{youtube_objects_statistics_text}","miniadm.webstats.youtube.php");
		$members=Paragraphe("member-64.png", "$UserAuthDaysGrouped {members}",
		"{display_access_by_members}","miniadm.webstats.members.php");
		}
	}
	
	
	if(is_numeric($_GET["week"])){
		$month=null;
		$xdate=strtotime("$year-$month-$day 00:00:00");
		$tablename="{$_GET["year"]}{$_GET["week"]}_week";
		
		$dateT=$q->WEEK_TITLE($_GET["week"],$_GET["year"]);
		
		
		if($q->TABLE_EXISTS($tablename)){
			$tR1[]="<hr><strong style='font-size:18px'>{statistics}: $dateT</strong><hr>";
			$tR1[]="<div id='week-$t' style='width:700px'></div><script>LoadAjax('week-$t','$page?buildiconsof-week=yes&tablename=$tablename&year={$_GET["year"]}&day={$_GET["day"]}&week={$_GET["week"]}');</script>";
		}		
	}
	
	
	if(is_numeric($month)){
		
		$xdate=strtotime("$year-$month-$day 00:00:00");
		$tablename=date("Ymd",$xdate)."_hour";
		
		$dateT=date("{l} {F} d",$xdate);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xdate);}		
		
		if($q->TABLE_EXISTS($tablename)){
			$tR1[]="<hr><strong style='font-size:18px'>{statistics}: $dateT</strong><hr>";
			$tR1[]="<div id='day-$t' style='width:700px'></div><script>LoadAjax('day-$t','$page?buildiconsof=yes&tablename=$tablename&xtime=$xdate');</script>";
		}
	}
	
	
	$html="<div class=BodyContent>
	<table style='width:100%'>
	<tr>
	<td valign='top'><div id='navcalendar'></div></td>
	<td valign='top' style='padding-left:15px'><center><div style='width:700px'>".CompileTr2($tR,"none")."</div>
	". @implode("", $tR1)."
	
	</center></div></td>
	</tr>
	</table>
	<script>
		LoadAjax('navcalendar','$page?navcalendar=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function webstats_stats(){
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
		
		
		
		
		$html="<b>$requests</b> {requests}&nbsp;|&nbsp;<b>$DAYSNumbers</b> {daysOfStatistics}$add0$add1";
		
		
		
		
		echo $tpl->_ENGINE_parse_body($html);
	
}


function messaging_right(){
	$sock=new sockets();
	$users=new usersMenus();

	if(count($t)==0){return;}
	$tpl=new templates();
	$html="<div class=BodyContent>".CompileTr2($t,"none")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

function build_calendar(){
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$page=CurrentPageName();
	$obj_cal = new classe_calendrier("calendar-$t");
	$obj_cal->USLink=true;
	//$obj_cal->activeAjax($_GET["t"],"LoadCalendar");
	if(!isset($_GET["month"])){if(isset($_COOKIE["NavCalendar-month"])){$_GET["month"]=$_COOKIE["NavCalendar-month"];}}
	if(!isset($_GET["year"])){if(isset($_COOKIE["NavCalendar-year"])){$_GET["year"]=$_COOKIE["NavCalendar-year"];}}
	if(!isset($_GET["month"])){$_GET["month"]=date("m");}
	if(!isset($_GET["year"])){$_GET["year"]=date("Y");}
	if(!isset($_GET["day"])){$_GET["day"]=date("d");}
	
	$obj_cal->afficheMois();
	$obj_cal->afficheSemaines(true);
	$obj_cal->afficheJours(true);
	$obj_cal->afficheNavigMois(true);
	
	$obj_cal->activeLienMois();
	$obj_cal->activeLiensSemaines();

	$obj_cal->activeJoursPasses();
	$obj_cal->activeJourPresent();
	$obj_cal->activeJoursFuturs();
	
	$obj_cal->activeJoursEvenements();
	
	$sql="SELECT DAY(zDate) as tday,
	DATE_FORMAT(zDate,'%Y%m%d') as tprefix,
	MONTH(zDate) as tmonth,YEAR(zDate) as tyear,totalsize as size,requests as hits
	FROM tables_day WHERE MONTH(zDate)={$_GET["month"]} AND YEAR(zDate)={$_GET["year"]} ORDER BY DAY(zDate)";
	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
		
	if(!$q->ok){echo "$q->mysql_error.<hr>$sql</hr>";}

	
	$month=$_GET["month"];
	if(strlen($month)==1){$month="0$month";}
	
	
	$ERR=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$table_work="{$ligne["tprefix"]}_hour";	
		$ligne["size"]=$ligne["size"]/1024;
		$ligne["size"]=round($ligne["size"]/1024);
		if(strlen($ligne["tday"])==1){$ligne["tday"]="0".$ligne["tday"];}
		$tr[]="{$_GET["year"]}-$month-{$ligne["tday"]} - size:{$ligne["size"]}";
		if(!$q->TABLE_EXISTS($table_work)){
			$ERR[]="$table_work no such table";
			continue;}
		$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}","Downloaded size:{$ligne["size"]}M&nbsp;|&nbsp;Hits Number: {$ligne["hits"]}");
	}
	

	$obj_cal->setFormatLienMois("javascript:Blurz();\" OnClick=\"javascript:NavCalendar$t('%s','%s');");
	$calendar=$obj_cal->makeCalendrier($_GET["year"],$_GET["month"]);
	if(count($ERR)>0){$err=@implode("<br>", $ERR);}
	if(isset($_GET["build-calendar"])){echo 
		$calendar.$err
		."<script>LoadAjaxTiny('statistics-$t','$page?webstats-stats=yes&month=$month&year={$_GET["year"]}');</script>";
		return;}
	
	$html="
	<div id='calendar-$t' class=form style='width:95%'>
	$calendar$err
	</div>
	
	<script>
		function NavCalendar$t(year,month){
			Set_Cookie('NavCalendar-month', month, '3600', '/', '', '');
			Set_Cookie('NavCalendar-year', year, '3600', '/', '', '');
			LoadAjax('calendar-$t','$page?build-calendar=yes&t=$t&year='+year+'&month='+month);
		}
		
		function ChangeLabelsText(){
			LoadAjaxTiny('statistics-$t','$page?webstats-stats=yes&month=$month&year={$_GET["year"]}&day={$_GET["day"]}');
			
		
		}
		ChangeLabelsText();
	</script>
	";
	echo $html;
	
}

function buildiconsof(){
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];
	$BlockedCount=$ligne["totalBlocked"];
	
	
	
	if($NotCategorized>0){
		$tR[]=Paragraphe("tables-failed-64.png", "$NotCategorized {not_categorized}",
		"{display_not_categorized_websites}","javascript:Loadjs('squid.visited.php?day=". date("Y-m-d",$xtime)."&onlyNot=yes')");		
	}
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
	
	
	
	echo $tpl->_ENGINE_parse_body("<div style='width:700px'>".CompileTr2($tR,"none")."</div>");
	
	echo "<center>". button($tpl->_ENGINE_parse_body("{refresh_summary}"), "Loadjs('squid.refresh.day.summarize.php?xtime=$xtime')","18px")."</center>";
	
	//echo "$tablename - $tablename_blocked - $xtime";
}


