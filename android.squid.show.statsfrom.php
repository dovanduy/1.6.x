<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.calendar.inc');


//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){echo "<H1>No right!!!</H1>";die();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["selector"])){date_selector();exit;}
if(isset($_GET["30days"])){trentedays();exit;}
if(isset($_GET["30daysSize"])){trentedayssize();exit;}
if(isset($_GET["build-calendar"])){date_build_calendar();exit;}
if(isset($_GET["ChangeLabelsText"])){ChangeLabelsText_js();exit;}

js();
function js(){
	$page=CurrentPageName();
	$html="YahooWin('965','$page?popup=yes&field={$_GET["field"]}&value={$_GET["value"]}','{$_GET["field"]}::{$_GET["value"]}')";
	echo $html;
	
}

function ChangeLabelsText_js(){
	if($_GET["month"]==null){return;}
	$tpl=new templates();
	$month=$_GET["month"];
	if(strlen($month)==1){$month="0$month";}
	$zdate=strtotime("{$_GET["year"]}-$month-01 00:00:00");
	$title_date=$tpl->_ENGINE_parse_body(date("{F} Y",$zdate));	
	
	echo "document.getElementById('30daysRid').innerHTML='$title_date';
document.getElementById('30daysSid').innerHTML='$title_date';
document.getElementById('dayselector').value='{$_GET["year"]}-$month-01';
Set_Cookie('android-NavCalendar-month', '{$_GET["month"]}', '3600', '/', '', '');
Set_Cookie('android-NavCalendar-year', '{$_GET["year"]}', '3600', '/', '', '');\n"; 
	
	
	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$subtitle="30 {days}";
	
	if(isset($_COOKIE["android-NavCalendar-month"])){
		$month=intval($_COOKIE["android-NavCalendar-month"]);
		$year=$_COOKIE["android-NavCalendar-year"];
		$zDate=strtotime("$year-$month-01");
		$subtitle=$tpl->_ENGINE_parse_body(date("{F} Y"));
	}
	
	$t=time();
	$array["30days"]="<span id='30daysRid'>$subtitle</span> ({requests})";
	$array["30daysSize"]="<span id='30daysSid'>$subtitle</span> ({size})";
	$array["websites"]="{websites}";
	$array["selector"]="{selector}";
	$array["infos"]="{infos}";
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&field={$_GET["field"]}&value={$_GET["value"]}&t=$t\"><span style='font-size:16px'>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
		}
	echo "
	<input type='hidden' id='dayselector' value=''>
	<div id=mainsuer_main_config style='width:99%;height:445px;overflow:auto;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#mainsuer_main_config').tabs();
				});
		</script>";		
	
	
}

function trentedays(){
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$sql="SELECT zDate,SUM(hits) as hits,{$_GET["field"]} FROM UserAuthDays GROUP BY 
	zDate,{$_GET["field"]}
	HAVING {$_GET["field"]}='{$_GET["value"]}' 
	AND zDate>DATE_SUB(NOW(),interval 31 DAY) ORDER BY zDate";	
	
	if(isset($_COOKIE["android-NavCalendar-month"])){
		$month=intval($_COOKIE["android-NavCalendar-month"]);
		$year=$_COOKIE["android-NavCalendar-year"];
		$sql="SELECT zDate,DAY(zDate) as tday,MONTH(zDate) as tmonth,YEAR(zDate) as tyear,SUM(hits) as hits,{$_GET["field"]} 
		FROM UserAuthDays GROUP BY 
		zDate,{$_GET["field"]},tday,tmonth,tyear
		HAVING {$_GET["field"]}='{$_GET["value"]}' 
		AND	tyear='$year' AND tmonth='$month' ORDER BY zDate";
		$zDate=strtotime("$year-$month-01");
		$title_date=$tpl->_ENGINE_parse_body(date("{F} Y",$zDate));		
	}
	

	$q=new mysql_squid_builder();
	
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body("<center><strong style='color:#d32d2d'>$q->mysql_error</strong></center>");
		return;
	}
	$nbdays=mysql_num_rows($results);
	
	if($nbdays==0){
		echo $tpl->_ENGINE_parse_body("<center><strong style='color:#d32d2d'>$title_date {no_surffor} {{$_GET["field"]}}:{$_GET["value"]}</strong></center>");
		return;
		
	}
	
	$title=$tpl->_ENGINE_parse_body("$title_date ($nbdays {days})");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$t=strtotime($ligne["zDate"]);
		$xdata[]=date("d",$t);
		$ydata[]=$ligne["hits"];
		$tt[]=date("d",$t)." :{$ligne["hits"]}";
	}	
	
	
	
	$time=time();
	$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".UserAuthDays.$time").".png";
	$gp=new artica_graphs();
	$gp->width=890;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	if(!is_file($targetedfile)){$targetedfile="img/nograph-000.png";$error="<br>".$gp->error;}
	
	echo "
	
	<center><div style='margin:5px'>$title</div>$error<img src='$targetedfile'></center>";
}
function trentedayssize(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sql="SELECT zDate,SUM(QuerySize) as hits,{$_GET["field"]} FROM UserAuthDays GROUP BY 
	zDate,{$_GET["field"]}
	HAVING {$_GET["field"]}='{$_GET["value"]}' 
	AND zDate>DATE_SUB(NOW(),interval 31 DAY) ORDER BY zDate";
	
	if(isset($_COOKIE["android-NavCalendar-month"])){
		$month=intval($_COOKIE["android-NavCalendar-month"]);
		$year=$_COOKIE["android-NavCalendar-year"];
		$sql="SELECT zDate,DAY(zDate) as tday,MONTH(zDate) as tmonth,YEAR(zDate) as tyear,SUM(QuerySize) as hits,{$_GET["field"]} 
		FROM UserAuthDays GROUP BY 
		zDate,{$_GET["field"]},tday,tmonth,tyear
		HAVING {$_GET["field"]}='{$_GET["value"]}' 
		AND	tyear='$year' AND tmonth='$month' ORDER BY zDate";
		$zDate=strtotime("$year-$month-01");
		$title_date=$tpl->_ENGINE_parse_body(date("{F} Y",$zDate));
		
	}	
	
	$q=new mysql_squid_builder();
	if(!$q->ok){echo "<H1 style='color:#d32d2d'>$q->mysql_error</H1>";}
	
	
	$results=$q->QUERY_SQL($sql);
	$nbdays=mysql_num_rows($results);
	
	if($nbdays==0){
		echo $tpl->_ENGINE_parse_body("<strong style='color:#d32d2d'>$title_date {no_surffor} {{$_GET["field"]}}:{$_GET["value"]}</strong>");
		return;
		
	}
	
	
	$title=$tpl->_ENGINE_parse_body("$title_date ($nbdays {days}) - MB");
	
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$ligne["hits"]=$ligne["hits"]/1024;
		$ligne["hits"]=round($ligne["hits"]/1024);
		$t=strtotime($ligne["zDate"]);
		$xdata[]=date("d",$t);
		$ydata[]=$ligne["hits"];
		
	}	
	$time=time();
	$targetedfile="ressources/logs/".md5(basename(__FILE__).".".__FUNCTION__.".UserAuthDays.$time").".png";
	$gp=new artica_graphs();
	$gp->width=890;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	echo "<center><div style='margin:5px'>$title</div>";
	
	if(!is_file($targetedfile)){
		echo "<strong style='font-size:13px'>$targetedfile no such file</strong>";
	}else{
	
	echo "<img src='$targetedfile'>";
	}
	
	echo "</center>";
}

function date_selector(){
	$page=CurrentPageName();
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?build-calendar=yes&t=$t&field={$_GET["field"]}&value={$_GET["value"]}');
	</script>
	
	
	";
	echo $html;
}

function date_build_calendar(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$obj_cal = new classe_calendrier("calendar-$t");
	//$obj_cal->activeAjax($_GET["t"],"LoadCalendar");
	if(!isset($_GET["month"])){if(isset($_COOKIE["android-NavCalendar-month"])){$_GET["month"]=$_COOKIE["android-NavCalendar-month"];}}
	if(!isset($_GET["year"])){if(isset($_COOKIE["android-NavCalendar-year"])){$_GET["year"]=$_COOKIE["android-NavCalendar-year"];}}
	if(!isset($_GET["month"])){$_GET["month"]=date("m");}
	if(!isset($_GET["year"])){$_GET["year"]=date("Y");}
	
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
	
	$sql="SELECT DAY(zDate) as tday,MONTH(zDate) as tmonth,YEAR(zDate) as tyear,SUM(QuerySize) as size,SUM(hits) as hits,{$_GET["field"]} 
	FROM UserAuthDays GROUP BY 
	tday,tmonth,tyear,{$_GET["field"]}
	HAVING {$_GET["field"]}='{$_GET["value"]}' 
	AND tmonth={$_GET["month"]} AND tyear={$_GET["year"]} ORDER BY tday";
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
		
	if(!$q->ok){echo "$q->mysql_error.<hr>$sql</hr>";}
	$month=$_GET["month"];
	if(strlen($month)==1){$month="0$month";}
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){		
		$ligne["size"]=$ligne["size"]/1024;
		$ligne["size"]=round($ligne["size"]/1024);
		if(strlen($ligne["tday"])==1){$ligne["tday"]="0".$ligne["tday"];}
		$tr[]="{$_GET["year"]}-$month-{$ligne["tday"]} - size:{$ligne["size"]}";
		$obj_cal->ajouteEvenement("{$_GET["year"]}-$month-{$ligne["tday"]}","Downloaded size:{$ligne["size"]}M&nbsp;|&nbsp;Hits Number: {$ligne["hits"]}");
	}
	
	//$obj_cal->activeAjax("ajax_calendrier","calendrier.php");
//makeCalendrier($a_annee,$a_mois)
	$obj_cal->setFormatLienMois("javascript:Blurz();\" OnClick=\"javascript:NavCalendar$t('%s','%s');");
	$calendar=$obj_cal->makeCalendrier($_GET["year"],$_GET["month"]);
	
	$html="$calendar
	<script>
		function NavCalendar$t(year,month){
			Set_Cookie('android-NavCalendar-month', month, '3600', '/', '', '');
			Set_Cookie('android-NavCalendar-year', year, '3600', '/', '', '');
			LoadAjax('$t','$page?build-calendar=yes&t=$t&field={$_GET["field"]}&value={$_GET["value"]}&year='+year+'&month='+month);
		}
		
		function ChangeLabelsText(){
			Loadjs('$page?ChangeLabelsText=yes&month=$month&year={$_GET["year"]}');
		
		}
		ChangeLabelsText();
	</script>
	";
	echo $html;
	
}

