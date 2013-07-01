<?php
session_start();
$_SESSION["MINIADM"]=true;
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
if(isset($_GET["big-calendar"])){build_calendar();exit;}
if(isset($_GET["build-calendar"])){build_calendar();exit;}
if(isset($_GET["calendar-js"])){calendar_js();exit;}
if(isset($_GET["navcalendar-builder"])){calendar_popup();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}')</script>", $content);
	echo $content;	
}


function calendar_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$calendar=$tpl->javascript_parse_text("{calendar}");
	$t=$_GET["t"];
	$div=null;
	$prefix=null;
	if(isset($_GET["div"])){$div="&div={$_GET["div"]}";}
	if(isset($_GET["prefix"])){$prefix="&prefix={$_GET["prefix"]}";}
	if(isset($_GET["source-page"])){$sourcepage="&source-page={$_GET["source-page"]}";}
	
	header("content-type: application/javascript");
	echo "
	YahooPopupHide();		
	YahooSearchUser('350','$page?navcalendar-builder=yes&t=$t$prefix$div$sourcepage','$calendar');";
}

function calendar_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	
	$div="webstats-left";
	$prefix="webstats-left=yes";
	
	$sourcepage=$page;
	if(isset($_GET["div"])){$div="{$_GET["div"]}";}
	if(isset($_GET["prefix"])){$prefix="{$_GET["prefix"]}";}	
	if(isset($_GET["source-page"])){$sourcepage=$_GET["source-page"];}
	$html="
	<div style='width:100%;height:350px'>
		<div id='navcalendar' style='width:100%;height:225px'></div>
	</div>
	<script>
		$('#SearchUser').dialog({ position: 'left' });
		LoadAjax('navcalendar','$page?navcalendar=yes&t=$t');
		function ChangeDay$t(url){
			LoadAjax('$div','$sourcepage?t=$t&$prefix&'+url);
		}
	</script>	
	";
	
	echo $html;
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
		<H1 id='TitleOfMainPage'>{statistics_by_date}</H1>
		<p>{statistics_by_date_text}</p>
		<div id='statistics-$t'></div>
		<div id='calendar-$t'></div>
	</div>	
	
	
	<script>
		LoadAjax('statistics-$t','$page?big-calendar=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}




function build_calendar(){
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$page=CurrentPageName();
	$obj_cal = new classe_calendrier("calendar-$t");
	$tpl=new templates();
	$obj_cal->USLink=true;
	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$hits_text=$tpl->_ENGINE_parse_body("{hits}");
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
	$obj_cal->SetLienJoursJS("ChangeDay$t");

	$obj_cal->activeJoursPasses();
	$obj_cal->activeJourPresent();
	$obj_cal->activeJoursFuturs();
	$obj_cal->activeJoursEvenements();
	$obj_cal->StyleMoisSize=18;
	$obj_cal->StyleJoursSize=18;
	$obj_cal->StyleHeight=50;
	$obj_cal->InsideEvents=true;
	
	$sql="SELECT DAY(zDate) as tday,
	DATE_FORMAT(zDate,'%Y%m%d') as tprefix,
	MONTH(zDate) as tmonth,YEAR(zDate) as tyear,totalsize as size,requests as hits
	FROM tables_day WHERE MONTH(zDate)={$_GET["month"]} AND YEAR(zDate)={$_GET["year"]} ORDER BY DAY(zDate)";
	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
		
	if(!$q->ok){echo "$q->mysql_error.<hr>$sql</hr>";}

	
	$month=$_GET["month"];
	if(strlen($month)==1){$month="0$month";}
	$tpl=new templates();
	
	$ERR=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$table_work="{$ligne["tprefix"]}_hour";	
		if($ligne["tprefix"]==date('Ymd')){continue;}
		$ligne["size"]=$ligne["size"]/1024;
		$ligne["size"]=FormatBytes($ligne["size"]);
		if(strlen($ligne["tday"])==1){$ligne["tday"]="0".$ligne["tday"];}
		$tr[]="{$_GET["year"]}-$month-{$ligne["tday"]} - size:{$ligne["size"]}";
		$TableTime=strtotime("{$_GET["year"]}-$month-{$ligne["tday"]} 00:00:00");
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		if(!$q->TABLE_EXISTS($table_work)){
				$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}",
				$tpl->_ENGINE_parse_body("<center><table style='width:5%;border:0px'><tr><td width=1% style='border:0px'><img src='img/status_warning.gif'></td><td nowrap width=99% style='border:0px'><a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('squid.stats.repair.day.php?time=$TableTime');\">{repair}</td></td></tr></table></center>"));
				continue;
		}
		$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}","<li>$size_text:{$ligne["size"]}</li>");
		$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}","<li>$hits_text:{$ligne["hits"]}</li>");
	}
	
	$obj_cal->setFormatLienMois("javascript:Blurz();\" OnClick=\"javascript:NavCalendar$t('%s','%s');");
	
	$calendar=$obj_cal->makeCalendrier($_GET["year"],$_GET["month"]);
	
	if(isset($_GET["build-calendar"])){echo $calendar;return;}
	
	$html="
	<div id='calendar-$t' style='width:95%;margin-top:35px' class=form>
	$calendar
	</div>
	<script>
		function NavCalendar$t(year,month){
			Set_Cookie('NavCalendar-month', month, '3600', '/', '', '');
			Set_Cookie('NavCalendar-year', year, '3600', '/', '', '');
			LoadAjax('calendar-$t','$page?build-calendar=yes&t=$t&year='+year+'&month='+month);
		}
		
		function ChangeDay$t(url){
			document.location.href='miniadm.webstats.php?t=$t&'+url;
		}
		
	</script>
	";
	echo $html;
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}



