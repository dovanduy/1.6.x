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


if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}


graph1();



function graph1(){
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));

	
	if(count($MAIN)<2){
		header("content-type: application/x-javascript");
		die();
	}

	$tpl=new templates();

	$title="{received}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph1-dashboard";
	$highcharts->xAxis=$MAIN["RECEIVED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$MAIN["RECEIVED"]["Y"]);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph2=yes');\n";


}

function graph2(){
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));
	$tpl=new templates();

	$tpl=new templates();

	$title="{delivered}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph2-dashboard";
	$highcharts->xAxis=$MAIN["DELIVERED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("{size}"=>$MAIN["DELIVERED"]["Y"]);
	echo $highcharts->BuildChart();
	echo "\nLoadjs('$page?graph3=yes');\n";
}
function graph3(){
	$page=CurrentPageName();
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/SMTP_DASHBOARD_GRAPHS"));
	$tpl=new templates();
	
	$tpl=new templates();
	
	$title="{rejected}";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container="graph3-dashboard";
	$highcharts->xAxis=$MAIN["REJECTED"]["X"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{messages}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="Mails";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("{size}"=>$MAIN["REJECTED"]["Y"]);
	echo $highcharts->BuildChart();
	

}