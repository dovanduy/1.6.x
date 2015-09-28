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

if(isset($_GET["top-userid-size"])){top_userid_size();exit;}
if(isset($_GET["top-userid-hits"])){top_userid_hits();exit;}


if(isset($_GET["top-ipaddr-site"])){top_web_blocked_site();exit;}
if(isset($_GET["top-ipaddr-category"])){top_web_blocked_category();exit;}




page();



function page(){
	//dashboard_user_day
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$html="<div style='width:1490'>
	<div style='float:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjaxRound('squid-top-stats','squid.statistics.top.php');")."</div>
	<div style='font-size:30px'>{top_members} {last_15_minutes}</div>
	";
	if($q->COUNT_ROWS("dashboard_currentusers")==0){
		echo FATAL_ERROR_SHOW_128("No data");
		return;
	}
	
		
		$tr[]="
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-userid-size'></div>
			</td>		
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-userid-hits'></div>
			</td>	
		</tr>
		";
		$js[]="Loadjs('$page?top-userid-size=yes&token=USER&id=top-userid-size');";
		$js[]="Loadjs('$page?top-userid-hits=yes&token=USER&id=top-userid-hits');";
		
		
	$tr[]="
		<tr>
			<td colspan=2><hr></td>
		</tr>
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-MAC-size'></div>
			</td>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-MAC-hits'></div>
			</td>
		</tr>
		";
		$js[]="Loadjs('$page?top-userid-size=yes&token=MAC&id=top-MAC-size');";
		$js[]="Loadjs('$page?top-userid-hits=yes&token=MAC&id=top-MAC-hits');";
		
	
	
		$tr[]="
		<tr>
			<td colspan=2><hr></td>
		</tr>
		<tr>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-ipaddr-size'></div>
			</td>
			<td style='width:100%'>
				<div style='width:745;height:700px' id='top-ipaddr-hits'></div>
			</td>
		</tr>
		";
		$js[]="Loadjs('$page?top-userid-size=yes&token=IPADDR&id=top-ipaddr-size');";
		$js[]="Loadjs('$page?top-userid-hits=yes&token=IPADDR&id=top-ipaddr-hits');";
	
	
	
	
	$html=$html."<table style='width:100%'>".@implode("\n", $tr)."</table></div>
	<script>		
			
	".@implode("\n", $js)."</script>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function top_userid_size(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(SIZE) AS SIZE,{$_GET["token"]}
			FROM dashboard_currentusers GROUP BY {$_GET["token"]} ORDER BY SIZE DESC LIMIT 15");
	
	if(!$q->ok){echo $q->mysql_error_jsdiv("top-userid-size");die();}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["SIZE"];
		$SIZE=$SIZE/1024;
		$SIZE=round($SIZE/1024);
		
		$FAMILYSITE=$ligne[$_GET["token"]];
		if($FAMILYSITE==null){$FAMILYSITE=$tpl->javascript_parse_text("{undefined}");}
		$PieData[$FAMILYSITE]=$SIZE;
		
		
	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->subtitle="<div style='margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:GotoProxyMysqlTOPMembersTable()\" style='text-decoration:underline'>{more_infos}</a></div>";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{$_GET["token"]} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members} {$_GET["token"]} (MB)");
	echo $highcharts->BuildChart();
}
function top_userid_hits(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(RQS) AS RQS,{$_GET["token"]}
			FROM dashboard_currentusers GROUP BY {$_GET["token"]} ORDER BY RQS DESC LIMIT 15");

	if(!$q->ok){die();}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne["RQS"];
		$USER=$ligne[$_GET["token"]];
		if($USER==null){$USER=$tpl->javascript_parse_text("{undefined}");}
		$PieData[$USER]=$SIZE;


	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->PieDatas=$PieData;
	$highcharts->subtitle="<div style='margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:GotoProxyMysqlTOPMembersTable()\" style='text-decoration:underline'>{more_infos}</a></div>";
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{$_GET["token"]} ({hits})";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members} - {$_GET["token"]} - ({hits})");
	echo $highcharts->BuildChart();
}



