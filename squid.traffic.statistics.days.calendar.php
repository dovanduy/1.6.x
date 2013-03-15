<?php
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);

if(isset($_GET["navcalendar"])){build_calendar();exit;}
if(isset($_GET["build-calendar"])){build_calendar();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["FormatQuery"])){FormatQuery();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{calendar}");
	echo "YahooWin6('500','$page?popup=yes','$title','top')";	
	
	
}
function FormatQuery(){
	header("content-type: application/x-javascript");
	
	$date="{$_GET["year"]}-{$_GET["month"]}-{$_GET["day"]}";
	
	$html="
	if(document.getElementById('squid-stats-day-hide-type')){type=document.getElementById('squid-stats-day-hide-type').value;}
	if(!type){type='size';}				
	LoadAjax('days-right-infos','squid.traffic.statistics.days.php?day-right-tabs=yes&day=$date&type='+type);
			
	";
	
	echo $html;
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$html="<div id='navcalendar'></div>
	<script>
		LoadAjax('navcalendar','$page?navcalendar=yes&t=$t');
	</script>		
			
	";
echo $html;	
	
}

/*

function SquidFlowDaySizeQuery(type){
	if(!type){
		if(document.getElementById('squid-stats-day-hide-type')){type=document.getElementById('squid-stats-day-hide-type').value;}
	}
	if(!type){type='size';}
		
	var sdate=document.getElementById('sdate').value;
	
}

function NavCalendar$t(year,month){
	Set_Cookie('NavCalendar-month', month, '3600', '/', '', '');
	Set_Cookie('NavCalendar-year', year, '3600', '/', '', '');
	LoadAjax('calendar-$t','$page?build-calendar=yes&t=$t&year='+year+'&month='+month);
}
*/

function build_calendar(){
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$page=CurrentPageName();
	$obj_cal = new classe_calendrier("calendar-$t");
	$obj_cal->USLink=true;
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
	$obj_cal->SetLienJoursJS("FormatQuery$t");

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
		$ligne["size"]=$ligne["size"]/1024;
		$ligne["size"]=round($ligne["size"]/1024);
		if(strlen($ligne["tday"])==1){$ligne["tday"]="0".$ligne["tday"];}
		$tr[]="{$_GET["year"]}-$month-{$ligne["tday"]} - size:{$ligne["size"]}";
		$TableTime=strtotime("{$_GET["year"]}-$month-{$ligne["tday"]} 00:00:00");
		if(!$q->TABLE_EXISTS($table_work)){
			$REPAIR[]=$tpl->_ENGINE_parse_body("
					<tr>
					<td width=1%><img src='img/arrow-right-16.png'></td>
					<td><a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('squid.stats.repair.day.php?time=$TableTime');\">{repair}: {$_GET["year"]}-$month-{$ligne["tday"]}</td>
					</td>
						
					");
			continue;}
			$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}","Downloaded size:{$ligne["size"]}M&nbsp;|&nbsp;Hits Number: {$ligne["hits"]}");
}

$obj_cal->setFormatLienMois("javascript:Blurz();\" OnClick=\"javascript:NavCalendar$t('%s','%s');");
$calendar=$obj_cal->makeCalendrier($_GET["year"],$_GET["month"]);
if(isset($_GET["build-calendar"])){echo $calendar;return;}
$REPAIRTR=@implode("", $REPAIR);
$html="
<div id='calendar-$t' class=form style='width:95%'>
$calendar$REPAIRTR
</div>

<script>

function FormatQuery$t(value){
	Loadjs('$page?FormatQuery=yes&'+value);
}

function NavCalendar$t(year,month){
	Set_Cookie('NavCalendar-month', month, '3600', '/', '', '');
	Set_Cookie('NavCalendar-year', year, '3600', '/', '', '');
	LoadAjax('calendar-$t','$page?build-calendar=yes&t=$t&year='+year+'&month='+month);
}
</script>
";
echo $html;

}