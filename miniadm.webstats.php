<?php
session_start();
$_SESSION["MINIADM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-left"])){webstats_left();exit;}
if(isset($_GET["webstats-stats"])){webstats_stats();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tools"])){tools();exit;}


main_page();

function main_page(){
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
	
	$month=$_GET["month"];
	$day=$_GET["day"];
	if(strlen($day)==1){$day="0$day";}
	$year=$_GET["year"];
	$xtime=strtotime("$year-$month-$day 00:00:00");
	
	$title=time_to_date($xtime);
	
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		&nbsp;&raquo;&nbsp;<a href='miniadm.webstats.Bydays.php'>{statistics_by_date}</a>
		&nbsp;&raquo;&nbsp;$title
		</div>
		<H1 id='TitleOfMainPage'>$title</H1>
		<p>{statistics_by_date_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<p>
	<div id='webstats-left'></div>
	</p>
	<script>
		LoadAjax('webstats-left','$page?tabs=yes&t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}&xtime=$xtime');
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$tablename=$_GET["tablename"];
	$xtime=$_GET["xtime"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($xtime)){
		$dayt=$q->HIER();
		$xtime=strtotime("$dayt 00:00:00");
		$tablename=date("Ymd",$xtime)."_hour";
	}
	
	if(!$q->FIELD_EXISTS("tables_day", "totalKeyWords")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `totalKeyWords` BIGINT( 255 ) NOT NULL NOT NULL,ADD INDEX ( `totalKeyWords`)");
	}	
	
	if($tablename==null){$tablename=date("Ymd",$xtime)."_hour";}
	$page=CurrentPageName();
	$dansguardian_events="dansguardian_events_".date("Ymd",$xtime);
	$sql="SELECT totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits,totalKeyWords FROM tables_day WHERE tablename='$dansguardian_events'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	$NotCategorized=$ligne["not_categorized"];
	$SumSize=$ligne["totalsize"];
	$SumHits=$ligne["requests"];
	$MembersCount=$ligne["MembersCount"];
	$YouTubeHits=$ligne["YouTubeHits"];
	$BlockedCount=$ligne["totalBlocked"];
	$totalKeyWords=$ligne["totalKeyWords"];
	$downloaded_size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	
	$membersT=$tpl->_ENGINE_parse_body("{members}");
	$display_members_for_this_day=$tpl->_ENGINE_parse_body("{display_members_for_this_day}");
	$display_youtube_for_this_day=$tpl->_ENGINE_parse_body("{display_youtube_for_this_day}");
	$display_blocked_events=$tpl->_ENGINE_parse_body("{display_blocked_events}");
	$display_visited_websites=$tpl->_ENGINE_parse_body("{display_visited_websites}");
	$display_not_categorized_websites=$tpl->_ENGINE_parse_body("{display_not_categorized_websites}");
	$youtube_videos=$tpl->_ENGINE_parse_body("{youtube_videos}");
	$blocked_hits=$tpl->_ENGINE_parse_body("{blocked_hits}");
	$not_categorized=$tpl->_ENGINE_parse_body("{not_categorized}");
	

	
	
	
	$SumSize=FormatBytes($SumSize/1024);
	$SumHits=numberFormat($SumHits,0,""," ");
	$array["$SumSize $downloaded_size"]="miniadm.webstats.websites.byday.php?month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime&direct=yes";

	$array["&laquo;$YouTubeHits&raquo; $youtube_videos"]="miniadm.webstats.websites.ByDayYoutube.php?webstats-middle=yes&title=yes&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime";
	$array["&laquo;$MembersCount&raquo; $membersT"]="miniadm.webstats.websites.ByDayMembers.php?tabs=yes&title=yes&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime";
	
	if($BlockedCount>0){
		$array["&laquo;$BlockedCount&raquo; $blocked_hits"]="miniadm.webstats.websites.ByDayBlocked.php?webstats-middle=yes&title=yes&month=$month&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime";
	}		
	
	
	$array["&laquo;$totalKeyWords&raquo; {keywords}"]="miniadm.webstats.websites.ByDayKeyWords.php?xtime=$xtime";
	

	
	
		
	
	$array["$NotCategorized $not_categorized"]="miniadm.webstats.ByDayNotCategorized.php?xtime=$xtime";
	
	
	$array["{system_logs}"]="miniadm.webstats.logrotate.php?xtime=$xtime";
	$array["{tools}"]="$page?tools=yes&year={$_GET["year"]}&day={$_GET["day"]}&tablename=$tablename&xtime=$xtime";
	
	
	//echo $tpl->_ENGINE_parse_body(CompileTr4($tR,"none"));
	
	//echo "<center style='margin:5px'>". button($tpl->_ENGINE_parse_body("{refresh_summary}"), "Loadjs('squid.refresh.day.summarize.php?xtime=$xtime')","18px")."</center>";
	
	$boot=new boostrap_form();
	echo $boot->build_tab($array);	
	
}




function webstats_left(){
	
	$t=$_GET["t"];
	$year=$_GET["year"];
	$day=$_GET["day"];
	$month=$_GET["month"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();

	
	
	if($day==null){
		$dayfull=$q->HIER();
		$xdate=strtotime("$dayfull 00:00:00");
		$day=date("d",$xdate);
		$month=date("m",$xdate);
		$year=date("Y",$xdate);
	}
	
	if(!is_numeric($month)){
		if(!is_numeric($_GET["week"])){
		$youtube_objects=$q->COUNT_ROWS("youtube_objects");
		$youtube_objects=numberFormat($youtube_objects,0,""," ");
			
		$UserAuthDaysGrouped=$q->COUNT_ROWS("UserAuthDaysGrouped");
		$UserAuthDaysGrouped=numberFormat($UserAuthDaysGrouped,0,""," ");
		$tR[]=Paragraphe32("$youtube_objects Youtube {objects}", 
		"{youtube_objects_statistics_text}","document.location.href='miniadm.webstats.youtube.php'","youtube-32.png");
		$members=Paragraphe("$UserAuthDaysGrouped {members}",
		"{display_access_by_members}","document.location.href='miniadm.webstats.members.php'","member-32.png");
		}
	}
	
	
	if(is_numeric($_GET["week"])){
		$month=null;
		$xdate=strtotime("$year-$month-$day 00:00:00");
		$tablename="{$_GET["year"]}{$_GET["week"]}_week";
		
		$dateT=$q->WEEK_TITLE($_GET["week"],$_GET["year"]);
		
		
		if($q->TABLE_EXISTS($tablename)){
			
			$tR1[]="<div id='week-$t' style='width:700px'></div><script>LoadAjax('week-$t','$page?buildiconsof-week=yes&tablename=$tablename&year={$_GET["year"]}&day={$_GET["day"]}&week={$_GET["week"]}&t=$t');</script>";
		}		
	}
	
	
	if(is_numeric($month)){
		
		$xdate=strtotime("$year-$month-$day 00:00:00");
		$tablename=date("Ymd",$xdate)."_hour";
		
		$dateT=date("{l} {F} d",$xdate);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xdate);}		
		
		if($q->TABLE_EXISTS($tablename)){
			
			$tR1[]="<div id='day-$t'></div><script>LoadAjax('day-$t','$page?buildiconsof=yes&tablename=$tablename&xtime=$xdate&t=$t');</script>";
		}
	}
	
	$t=time();
	
	$title=$tpl->javascript_parse_text("{statistics}: $dateT");
	
	$html="
	".CompileTr4($tR,"none")."
	". @implode("", $tR1)."
	<div id='$t'></div>
	<script>
		if(document.getElementById('TitleOfMainPage')){document.getElementById('TitleOfMainPage').innerHTML='$title';}
		LoadAjax('$t','squid.traffic.statistics.days.php?day-right-tabs=yes&day=$year-$month-$day&type=size');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function webstats_stats(){
		$t=$_GET["t"];
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

function tools(){
	$xtime=$_GET["xtime"];
	$tpl=new templates();
	$tr[]=Paragraphe("table-synonyms-settings-64.png", "{members_table}", "{squidstats_gen_members_table}",
	"javascript:Loadjs('miniadm.webstats.tools.php?members-table-js=$xtime')"
			
	);
	
	$tr[]=Paragraphe("64-categories-loupe.png", "{categorize_websites}", "{squidstats_gen_categorize_table}",
			"javascript:Loadjs('miniadm.webstats.tools.php?categorize-day-table-js=$xtime')"
				
	);	
	
	$tr[]=Paragraphe("reconstruct-database-64.png", "{update_counters}", "{update_counters_table}",
			"javascript:Loadjs('miniadm.webstats.tools.php?sumary-counters-table-js=$xtime')"
	
	);	
	
	$html=CompileTr4($tr);
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
