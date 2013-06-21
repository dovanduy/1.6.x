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
if(isset($_GET["webstats-stats"])){exit;}
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
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;|&nbsp;
		<a href=\"miniadm.webstats-start.php\">{web_statistics}</a></div>
		<H1>{database_maintenance}</H1>
		<p>{database_maintenance_text}</p>
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
	$users=new usersMenus();
	
	$squiddb=Paragraphe32('mysql_statistics_engine','mysql_statistics_engine_params'
			,"blur()","database-connect-settings-32-grey.png");	
	

	$tr[]=Paragraphe32('purge_statistics_database','purge_statistics_database_explain'
			,"Loadjs('squid.artica.statistics.purge.php')","table-delete-32.png");
	
	
	$tr[]=table_heures_enretard();
	
	
	$tr[]=$squiddb;
	
	$tr[]=Paragraphe32('remote_mysql_server','remote_mysqlsquidserver_text'
			,"Loadjs('squid.remote-mysql.php')","artica-meta-32.png");
	
	
	
	
	
	$tr[]=Paragraphe32('restore_purged_statistics','restore_purged_statistics_explain'
			,"Loadjs('squid.artica.statistics.restore.php')","32-import.png");
	
	
	
	$tr[]=Paragraphe32('source_logs','source_logs_squid_text'
			,"Loadjs('squid.logrotate.php')","32-logs.png");
	
	
	
	$tr[]=Paragraphe32('enable_disable_statistics','ARTICA_STATISTICS_TEXT'
			,"Loadjs('squid.artica.statistics.php')","statistics-32.png");	
	
	
	$html="
		<div class=BodyContent>". CompileTr4($tr)."</div>
			
	";
	
	
	$html= $tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;	
}
function table_heures_enretard(){

	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$CurrentHourTable="squidhour_".date("YmdH");
	if($GLOBALS["VERBOSE"]){echo "Find hours tables...\n";}
	$tables=$q->LIST_TABLES_HOURS_TEMP();
	$c=0;
	$t=time();
	$CountDeTable=0;
	while (list ($table, $none) = each ($tables) ){
		if($table==$CurrentHourTable){if($GLOBALS["VERBOSE"]){echo "SKIP `$table`\n";}continue;}
		if(!preg_match("#squidhour_([0-9]+)#",$table,$re)){continue;}
		$hour=$re[1];
		$year=substr($hour,0,4);
		$month=substr($hour,4,2);
		$day=substr($hour,6,2);
		$tt[$table]=true;
	}
	if(!is_array($tt)){return null;}
	$CountDeTable=count($tt);
	if($CountDeTable>0){
		$sock=new sockets();
		$time=$sock->getFrameWork("squid.php?squidhour-repair-exec=yes");
		if(is_numeric($time)){
			$title=$tpl->javascript_parse_text("{squidhour_not_scanned} {running} {$time}Mn");
			$title=str_replace("%s", $CountDeTable, $title);
			$title=str_replace("%", $CountDeTable, $title);
			return Paragraphe32("noacco:$title ",'launch_squidhour_explain'
					,"blur()","wait-clock.gif");
		}
		$launch_squidhour_explain=$tpl->_ENGINE_parse_body("{launch_squidhour_explain}");
		$title=$tpl->javascript_parse_text("{squidhour_not_scanned}");
		$title=str_replace("%s", $CountDeTable, $title);
		$title=str_replace("%", $CountDeTable, $title);
		return Paragraphe32("noacco:$title","$launch_squidhour_explain"
				,"Loadjs('squid.statistics.central.php?squidhour-js=yes')","Database32-red.png");
	}

}
