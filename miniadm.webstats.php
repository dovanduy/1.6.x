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

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-left"])){webstats_left();exit;}
if(isset($_GET["webstats-stats"])){webstats_stats();exit;}
main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{web_statistics}</H1>
		<p>{web_statistics_member_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='webstats-left'></div>
	
	<script>
		LoadAjax('webstats-left','$page?webstats-left=yes');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function webstats_left(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$youtube_objects=$q->COUNT_ROWS("youtube_objects");
	$youtube_objects=numberFormat($youtube_objects,0,""," ");
		
	$UserAuthDaysGrouped=$q->COUNT_ROWS("UserAuthDaysGrouped");
	$UserAuthDaysGrouped=numberFormat($UserAuthDaysGrouped,0,""," ");
	
	$t[]=Paragraphe("youtube-64.png", "$youtube_objects Youtube {objects}", "{youtube_objects_statistics_text}","miniadm.webstats.youtube.php");
	$t[]=Paragraphe("member-64.png", "$UserAuthDaysGrouped {members}",
	"{display_access_by_members}","miniadm.webstats.members.php");
	
	$html="<div class=BodyContent><center><div style='width:700px'>".CompileTr3($t,"none")."</div></center></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function webstats_stats(){
		$tpl=new templates();
		$q=new mysql_squid_builder();
		$DAYSNumbers=$q->COUNT_ROWS("tables_day");
	
		$requests=$q->EVENTS_SUM();
		$requests=numberFormat($requests,0,""," ");	
		$html="<b>$requests</b> {requests}&nbsp;|&nbsp;<b>$DAYSNumbers</b> {daysOfStatistics}";
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