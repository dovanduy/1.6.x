<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}


page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div style='font-size:50px;margin-bottom:20px'>{messaging}: {statistics} {today}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:745px'>
			<div id='graph1-$t' style='width:710px;height:710px'></div>
		</td>
		<td valign='top' style='width:745px'>
			<div id='graph2-$t' style='width:710px;height:710px'></div>
		</td>		
	</tr>
	<tr>
		<td valign='top' style='width:745px'>
			<div id='graph3-$t' style='width:710px;height:710px'></div>
		</td>
		<td valign='top' style='width:745px'>
			<div id='graph4-$t' style='width:710px;height:710px'></div>
		</td>		
	</tr>	
	<tr>
		<td valign='top' style='width:745px'>
			<div id='graph5-$t' style='width:710px;height:710px'></div>
		</td>
		<td valign='top' style='width:745px'>
			<div id='graph6-$t' style='width:710px;height:710px'></div>
		</td>		
	</tr>				
	</table>
	</div>
<script>
Loadjs('$page?graph1=yes&t=$t');
Loadjs('$page?graph2=yes&t=$t');
Loadjs('$page?graph3=yes&t=$t');
Loadjs('$page?graph4=yes&t=$t');
Loadjs('$page?graph5=yes&t=$t');
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function graph1(){
	
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$results=$q->QUERY_SQL("SELECT * FROM dashboard_smtpdeliver ORDER BY RQS DESC LIMIT 0,20","artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["DOMAIN"]]=$ligne["RQS"];
		
	}
	
	
	$highcharts=new highcharts();
	$highcharts->container="graph1-{$_GET["t"]}";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{messages}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_domains}/{messages}/{delivered}");
	echo $highcharts->BuildChart();
	
}
function graph2(){

	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();

	$results=$q->QUERY_SQL("SELECT * FROM dashboard_smtpdeliver ORDER BY SIZE DESC LIMIT 0,20","artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["DOMAIN"]]=($ligne["SIZE"]/1024);

	}
	$highcharts=new highcharts();
	$highcharts->container="graph2-{$_GET["t"]}";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_domains}/{size}/{delivered}");
	echo $highcharts->BuildChart();

}


function graph3(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();

	$results=$q->QUERY_SQL("SELECT * FROM dashboard_smtpsenders ORDER BY RQS DESC LIMIT 0,20","artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["email"]]=$ligne["RQS"];

	}
	$highcharts=new highcharts();
	$highcharts->container="graph3-{$_GET["t"]}";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_senders}/{messages}");
	echo $highcharts->BuildChart();


}


function graph4(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();

	$results=$q->QUERY_SQL("SELECT * FROM dashboard_smtprecipients ORDER BY RQS DESC LIMIT 0,20","artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["email"]]=$ligne["RQS"];

	}
	$highcharts=new highcharts();
	$highcharts->container="graph4-{$_GET["t"]}";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_recipients}/{messages}");
	echo $highcharts->BuildChart();
	

}
function graph5(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();

	$results=$q->QUERY_SQL("SELECT * FROM dashboard_smtprejects ORDER BY RQS DESC LIMIT 0,20","artica_events");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$PieData[$ligne["rule"]]=$ligne["RQS"];

	}
	$highcharts=new highcharts();
	$highcharts->container="graph5-{$_GET["t"]}";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{messages}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_reject_rules}/{messages}");
	echo $highcharts->BuildChart();


}