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
	
	if(isset($_GET["graphProtoHits"])){proto_hits();exit;}
	if(isset($_GET["graphProtoSize"])){proto_size();exit;}
	if(isset($_GET["graphMime"])){top_mime();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}
	if(isset($_GET["graph001"])){top_cached_sites();exit;}
	if(isset($_GET["graph002"])){top_not_cached_sites();exit;}
	
	
	PageDeGarde();
	
	
function PageDeGarde(){
	$page=CurrentPageName();
	$time=time();
	$tpl=new templates();
	$f1[]=$tpl->_ENGINE_parse_body("<div style='font-size:26px;margin-bottom:20px'>{top_web} {today}</div>");
	
	
	$file="/usr/share/artica-postfix/ressources/logs/web/TOP_MIME.db";
	if(is_file($file)){
		$tr[]="<div style='width:500px;height:500px' id='$time-000'></div>";
		$f2[]="function X000$time(){
		AnimateDiv('$time-000');
		Loadjs('$page?graphMime=yes&container=$time-000&time=$time',true);
	}
	setTimeout(\"X000$time()\",500);";
	}	
	

	
	
	
	
	
	$file1="/usr/share/artica-postfix/ressources/logs/web/TOP_CACHED.db";
	$file2="/usr/share/artica-postfix/ressources/logs/web/TOP_NOT_CACHED.db";
	
	if(is_file($file1)){
		$tr[]="<div style='width:500px;height:500px' id='$time-001'></div>";
		$f2[]="function X001$time(){
		AnimateDiv('$time-001');
		Loadjs('$page?graph001=yes&container=$time-001&time=$time',true);
		}
		setTimeout(\"X001$time()\",500);";
	}
	
	if(is_file($file2)){
		$tr[]="<div style='width:500px;height:500px' id='$time-002'></div>";

		$f2[]="function X002$time(){
		AnimateDiv('$time-002');
		Loadjs('$page?graph002=yes&container=$time-002&time=$time',true);
		}
		setTimeout(\"X002$time()\",500);";
	}

	
	$file="/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_SIZE.db";
	if(is_file($file)){
		$tr[]="<div style='width:500px;height:500px' id='$time-100'></div>";
		$f2[]="function X100$time(){
		AnimateDiv('$time-100');
		Loadjs('$page?graphProtoSize=yes&container=$time-100&time=$time',true);
	}
	setTimeout(\"X100$time()\",500);";
	}
	
	$file="/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_HITS.db";
	if(is_file($file)){
		$tr[]="<div style='width:500px;height:500px' id='$time-200'></div>";
		$f2[]="function X200$time(){
		AnimateDiv('$time-200');
		Loadjs('$page?graphProtoHits=yes&container=$time-200&time=$time',true);
	}
	setTimeout(\"X200$time()\",500);";
	}	
	
	
	
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

function top_cached_sites(){
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_CACHED.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.bandwidth.cached.week.php?js=yes');\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_cached_websites}");
	echo $highcharts->BuildChart();
}

function top_mime(){
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_MIME.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.stats.filetypes.php');\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_filetypes}");
	echo $highcharts->BuildChart();
}

function proto_size(){
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_SIZE.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.stats.filetypes.php');\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{protocol} {size}");
	echo $highcharts->BuildChart();	
	
}

function proto_hits(){
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_PROTO_HITS.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.stats.filetypes.php');\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{protocol} {hits}");
	echo $highcharts->BuildChart();	
	
}



function top_not_cached_sites(){
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TOP_NOT_CACHED.db"));
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} MB";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_not_cached_websites}");
	echo $highcharts->BuildChart();
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