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
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.highcharts.inc');


	$users=new usersMenus();
	if(!$GLOBALS["AS_ROOT"]){if(!$users->AsSquidAdministrator){die();}}

if(isset($_GET["popup"])){popup();exit;}	
if(isset($_GET["rtt-day"])){rtt_day();exit;}
if(isset($_GET["rtt-full"])){rtt_full();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{cache_rate_by_hour}");
	echo "YahooWin6(1150,'$page?popup=yes','$title')";
}
	
	
function popup(){
	$page=CurrentPageName();
	
	
	$time=time();
	$f1[]="<div style='width:1100px;height:400px' id='$time-2'></div><hr>";
	$f2[]="function FDeux$time(){
		LoadjsSilent('$page?rtt-day=yes&container=$time-2',false);
	}
	setTimeout(\"FDeux$time()\",500);";
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/CACHED_AVGD")){
		$serials=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/CACHED_AVGD"));
		if(count($serials)>1){
			$f1[]="<div style='width:1100px;height:400px' id='$time-3'></div><hr>";
			$f2[]="function FTrois$time(){
			LoadjsSilent('$page?rtt-full=yes&container=$time-3',false);
			}
			setTimeout(\"FTrois$time()\",500);";
		}
	}
	
	
	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;
}	
function rtt_day(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/CACHED_AVG_ARRAY";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{cache_rate_by_hour}";
	$timetext="{hours}";
	$ARRAY=unserialize(@file_get_contents($filecache));
		
	while (list ($num, $ligne) = each ($ARRAY) ){
		$xdata[]=$num.":00";
		$ydata[]=$ligne;
	
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{rate} %";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=true;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="%";
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{rate}"=>$ydata);
	echo $highcharts->BuildChart();
}

function rtt_full(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/CACHED_AVGD";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{cache_rate_by_day}";
	$timetext="{days}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	
	while (list ($num, $ligne) = each ($ARRAY) ){
		$xdata[]=$num;
		$ydata[]=$ligne;
	
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{rate} %";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=true;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="%";
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{rate}"=>$ydata);
	echo $highcharts->BuildChart();
	}	
	
