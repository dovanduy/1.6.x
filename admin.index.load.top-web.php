<?php
if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
		$GLOBALS["AS_ROOT"]=true;
		include_once(dirname(__FILE__).'/framework/class.unix.inc');
		include_once(dirname(__FILE__)."/framework/frame.class.inc");
		include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
		include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
		include_once(dirname(__FILE__)."/framework/class.settings.inc");
	}}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.html.pages.inc');
	include_once('class.highcharts.inc');
	
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}
	
	PageDeGarde();
	
	
function PageDeGarde(){
	$page=CurrentPageName();
	$time=time();
	$tpl=new templates();
	$f1[]=$tpl->_ENGINE_parse_body("<div style='font-size:26px;margin-bottom:20px'>{top_web} {today}</div>");
	$tr[]="<div style='width:500px;height:500px' id='$time-01'></div>";
	$tr[]="<div style='width:500px;height:500px' id='$time-02'></div>";
	$tr[]="<div style='width:500px;height:500px' id='$time-03'></div>";
	$f1[]=CompileTr2($tr);
	if(is_file("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-fam.db")){
		
		$f2[]="function XDeux$time(){
		AnimateDiv('$time-01');
		Loadjs('$page?graph1=yes&container=$time-01&time=$time',true);
	}
	setTimeout(\"XDeux$time()\",500);";
	
	}

	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;
}

function graph1(){
	
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-fam.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}");
	echo $highcharts->BuildChart()."\nLoadjs('$page?graph2=yes&container={$_GET["time"]}-02&time={$_GET["time"]}',true);";
	
}
function graph2(){

	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-uid.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members}");
	echo $highcharts->BuildChart()."\nLoadjs('$page?graph3=yes&container={$_GET["time"]}-03&time={$_GET["time"]}',true);";

}
function graph3(){

	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-ipaddr.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_ipaddr}");
	echo $highcharts->BuildChart();

}