<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');


$users=new usersMenus();
if(!$users->AllowViewStatistics){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	exit;
}

if(isset($_GET["top-web-site-size"])){top_web_site_size();exit;}
if(isset($_GET["top-web-site-hits"])){top_web_site_hits();exit;}

if(isset($_GET["top-web-user-size"])){top_web_user_size();exit;}
if(isset($_GET["top-web-user-hits"])){top_web_user_hits();exit;}
if(isset($_GET["top-web-blocked-site"])){top_web_blocked_site();exit;}
if(isset($_GET["top-web-blocked-category"])){top_web_blocked_category();exit;}




page();


function DATE_START(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$table="dashboard_user_day";
	if($q->COUNT_ROWS($table)==0){
		$table="dashboard_blocked_day";
	}
	
	
	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	return $tpl->_ENGINE_parse_body("{date_start} $time1, {last_date} $time2");
}

function page(){
	//dashboard_user_day
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$html="<div style='width:1490'>
	<div style='float:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjaxRound('squid-top-stats','squid.statistics.top.php');")."</div>
	<div style='font-size:30px'>{top_web}: ". DATE_START()."</div>
	";
	
	if($q->COUNT_ROWS("dashboard_countwebsite_day")>1){
		
		$tr[]="
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-site-size'></div>
			</td>		
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-site-hits'></div>
			</td>	
		</tr>
		";
		$js[]="Loadjs('$page?top-web-site-size=yes');";
		$js[]="Loadjs('$page?top-web-site-hits=yes');";
		
		
	}
	
	if($q->COUNT_ROWS("dashboard_user_day")>1){
		$tr[]="
		<tr>
			<td colspan=2><hr></td>
		</tr>
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-user-size'></div>
			</td>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-user-hits'></div>
			</td>
		</tr>
		";
		$js[]="Loadjs('$page?top-web-user-size=yes');";
		$js[]="Loadjs('$page?top-web-user-hits=yes');";
		
	}
	if($q->COUNT_ROWS("dashboard_blocked_day")>1){
		$tr[]="
		<tr>
			<td colspan=2><hr></td>
		</tr>
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-blocked-site'></div>
			</td>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-web-blocked-category'></div>
			</td>
		</tr>
		";
		$js[]="Loadjs('$page?top-web-blocked-site=yes');";
		$js[]="Loadjs('$page?top-web-blocked-category=yes');";
	
	}	
	
	
	
	$html=$html."<table style='width:100%'>".@implode("\n", $tr)."</table></div>
	<script>		
			
	".@implode("\n", $js)."</script>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function top_web_site_size(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(SIZE) AS SIZE,FAMILYSITE
			FROM dashboard_countwebsite_day GROUP BY FAMILYSITE ORDER BY SIZE DESC LIMIT 15");
	
	if(!$q->ok){echo $q->mysql_error_jsdiv("top-web-site-size");die();}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["SIZE"];
		$SIZE=$SIZE/1024;
		$SIZE=round($SIZE/1024);
		
		$FAMILYSITE=$ligne["FAMILYSITE"];
		$PieData[$FAMILYSITE]=$SIZE;
		
		
	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-site-size";
	$highcharts->subtitle="<div style='margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:GotoMysQLAllWebsites()\" style='text-decoration:underline'>{more_infos}</a></div>";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites_by_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites_by_size} (MB)");
	echo $highcharts->BuildChart();
}
function top_web_site_hits(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(RQS) AS RQS,FAMILYSITE
			FROM dashboard_countwebsite_day GROUP BY FAMILYSITE ORDER BY RQS DESC LIMIT 15");

	if(!$q->ok){die();}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["RQS"];
		$FAMILYSITE=$ligne["FAMILYSITE"];
		$PieData[$FAMILYSITE]=$SIZE;


	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-site-hits";
	$highcharts->PieDatas=$PieData;
	$highcharts->subtitle="<div style='margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:GotoMysQLAllWebsites()\" style='text-decoration:underline'>{more_infos}</a></div>";
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites_by_hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites_by_hits}");
	echo $highcharts->BuildChart();
}
function top_web_user_size(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT SUM(SIZE) AS SIZE,USER
			FROM dashboard_user_day GROUP BY USER ORDER BY SIZE DESC LIMIT 15");
	
	if(!$q->ok){die();}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["SIZE"];
		$SIZE=$SIZE/1024;
		$SIZE=round($SIZE/1024);
		
		$FAMILYSITE=$ligne["USER"];
		$PieData[$FAMILYSITE]=$SIZE;
	
	
	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-user-size";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_members_by_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members_by_size} (MB)");
	echo $highcharts->BuildChart();	
	
	
}
function top_web_user_hits(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(RQS) AS RQS,USER
			FROM dashboard_user_day GROUP BY USER ORDER BY RQS DESC LIMIT 15");

	if(!$q->ok){die();}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["RQS"];
		$FAMILYSITE=$ligne["USER"];
		$PieData[$FAMILYSITE]=$SIZE;


	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-user-hits";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_members_by_hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{members}/{hits}");
	echo $highcharts->BuildChart();
}

function top_web_blocked_site(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT SUM(RQS) AS RQS,WEBSITE
			FROM dashboard_blocked_day GROUP BY WEBSITE ORDER BY RQS DESC LIMIT 15");
	
	if(!$q->ok){die();}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["RQS"];
		$FAMILYSITE=$ligne["WEBSITE"];
		$PieData[$FAMILYSITE]=$SIZE;
	
	
	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-blocked-site";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites} {blocked}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites} {blocked}/{hits}");
	echo $highcharts->BuildChart();	
	
	
}function top_web_blocked_category(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT SUM(RQS) AS RQS,CATEGORY FROM dashboard_blocked_day GROUP BY CATEGORY ORDER BY RQS DESC LIMIT 15");
	
	if(!$q->ok){die();}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["RQS"];
		$FAMILYSITE=$ligne["CATEGORY"];
		$PieData[$FAMILYSITE]=$SIZE;
	
	
	}
	$highcharts=new highcharts();
	$highcharts->container="top-web-blocked-category";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_categories} {blocked}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories} {blocked}/{hits}");
	echo $highcharts->BuildChart();	
	
	
}

