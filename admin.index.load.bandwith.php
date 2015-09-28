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
include_once('ressources/class.highcharts.inc');


if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph5"])){graph5();exit;}
if(isset($_GET["graph-bwrt-out"])){graph_bwrt_out();exit;}
if(isset($_GET["graph-bwrt-in"])){graph_bwrt_in();exit;}
if(isset($_GET["graph-squid-01"])){graph_squid_cached_minutes();exit;}
if(isset($_GET["graph-squid-02"])){graph_squid_not_cached_minutes();exit;}

if(isset($_GET["graph-squid-03"])){graph_squid_cached_hours();exit;}
if(isset($_GET["graph-squid-04"])){graph_squid_not_cached_hours();exit;}

if(isset($_GET["graph-squid-05"])){graph_squid_cached_hier_hours();exit;}
if(isset($_GET["graph-squid-06"])){graph_squid_not_cached_hier_hours();exit;}
if(isset($_GET["PageDeGarde"])){PageDeGarde();exit;}



tabs();

function tabs(){
	
	$fontsize=18;
	$tpl=new templates();
	$page=CurrentPageName();
	$array["RTT"]="{realtime}";
	$array["WEEK"]="{this_week}";
	$array["websites"]="{websites}";
	$array["cached-websites"]="{cached_websites}";
	$array["PageDeGarde"]="{graphs}";
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="RTT"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rtt.week.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="WEEK"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rttw.week.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}	
		
		if($num=="websites"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.rweb.week.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="cached-websites"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwidth.cached.week.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_bandwidth_tabs');
	
	echo $html;	
	
}


function PageDeGarde(){
	$page=CurrentPageName();
	$time=time();
	$DISABLE_STATS=false;
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($sock->SQUID_LOCAL_STATS_DISABLED()){$SquidPerformance=2;}
	if($SquidPerformance>1){
		$DISABLE_STATS=true;
	}
	
	if(!$DISABLE_STATS){
			if(is_file("/usr/share/artica-postfix/ressources/logs/web/CACHED_HOUR.db")){
				$f1[]="<div style='width:1150px;height:340px' id='$time-squid-01'></div>";
				$f2[]="function XSquid01$time(){
				AnimateDiv('$time-squid-01');
				Loadjs('$page?graph-squid-01=yes&container=$time-squid-01&time=$time',true);
			}
			setTimeout(\"XSquid01$time()\",500);";
			
			}
		
		
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HOUR.db")){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-02'></div>";
			$f2[]="function XSquid02$time(){
			AnimateDiv('$time-squid-02');
			Loadjs('$page?graph-squid-02=yes&container=$time-squid-02&time=$time',true);
		}
		setTimeout(\"XSquid02$time()\",500);";
		
		}	
		
		
		
		$file1="/usr/share/artica-postfix/ressources/logs/web/CACHED_DAY.db";
		$file2="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_DAY.db";
		
		if(is_file($file1)){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-03'></div>";
			$f2[]="function XSquid03$time(){
			AnimateDiv('$time-squid-03');
			Loadjs('$page?graph-squid-03=yes&container=$time-squid-03&time=$time',true);
		}
		setTimeout(\"XSquid03$time()\",500);";
		
		}	
		if(is_file($file2)){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-04'></div>";
			$f2[]="function XSquid04$time(){
			AnimateDiv('$time-squid-04');
			Loadjs('$page?graph-squid-04=yes&container=$time-squid-04&time=$time',true);
		}
		setTimeout(\"XSquid04$time()\",500);";
		
		}	
		
		
		$file1="/usr/share/artica-postfix/ressources/logs/web/CACHED_HIER_DAY.db";
		$file2="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HIER_DAY.db";
	
		if(is_file($file1)){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-05'></div>";
			$f2[]="function XSquid05$time(){
			AnimateDiv('$time-squid-05');
			Loadjs('$page?graph-squid-05=yes&container=$time-squid-05&time=$time',true);
		}
		setTimeout(\"XSquid05$time()\",500);";
		
		}
		if(is_file($file2)){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-06'></div>";
			$f2[]="function XSquid06$time(){
			AnimateDiv('$time-squid-06');
			Loadjs('$page?graph-squid-06=yes&container=$time-squid-06&time=$time',true);
		}
		setTimeout(\"XSquid06$time()\",500);";
		
		}	
	
	}	
	
	$sock=new sockets();
	$DisableBWMng=1;
	
	if($DisableBWMng==0){
		if(is_file("ressources/logs/web/BWMRT_OUT.db")){
			$f1[]="<div style='width:1150px;height:340px' id='$time-01'></div>";
			$f2[]="function XDeux$time(){
			AnimateDiv('$time-01');
			Loadjs('$page?graph-bwrt-out=yes&container=$time-01&time=$time',true);
		}
		setTimeout(\"XDeux$time()\",500);";
		
		}
		
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/BWMRT_IN.db")){
			$f1[]="<div style='width:1150px;height:340px' id='$time-02'></div>";
			$f2[]="
			\nfunction XCDeux$time(){
			AnimateDiv('$time-02');
			Loadjs('$page?graph-bwrt-in=yes&container=$time-02',true);
		}
		setTimeout(\"XCDeux$time()\",500);";
		}
	}

	if(is_file("/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG3.db")){
		$f1[]="<div style='width:1150px;height:340px' id='$time-3'></div>";
		$f2[]="function FTrois$time(){AnimateDiv('$time-3');Loadjs('$page?graph3=yes&container=$time-3',true);} setTimeout(\"FTrois$time()\",600);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/INTERFACE_LOAD_AVG3.db no such file</H1>\n";}
	}
	
	if(!$DISABLE_STATS){
		$cacheFile="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG5.db";
		if(is_file($cacheFile)){
			$f1[]="<div style='width:1150px;height:340px' id='$time-5'></div>";
			$f2[]="function FCinq$time(){
			AnimateDiv('$time-5');
			Loadjs('$page?graph5=yes&container=$time-5',true);
		}
		setTimeout(\"FCinq$time()\",600);";
		}else{
			if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/INTERFACE_LOAD_AVG5.db no such file</H1>\n";}
		}	
	}
	
	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;	
}
function graph3(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG3.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{downloaded_size_this_hour}";
	$timetext="{minutes}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="MB";
	$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph5(){
	if(isset($_SESSION["SQUID-MONTH-LOADVG"])){echo $_SESSION["SQUID-MONTH-LOADVG"];}
	$filecache="/usr/share/artica-postfix/ressources/logs/web/INTERFACE_LOAD_AVG5.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{downloaded_size_this_month}";
	$timetext="{days}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->_ENGINE_parse_body("{".date('F') ."} {day}:");
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("MB");
	$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs-month.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{size}"=>$ydata);
	$_SESSION["SQUID-MONTH-LOADVG"]=$highcharts->BuildChart();
	echo $_SESSION["SQUID-MONTH-LOADVG"];

}

function graph_bwrt_out(){
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/web/BWMRT_OUT.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{network_outbound_size}";
	$timetext="{minutes}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="Mn";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");
	
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	$time=$_GET["time"];



}
function graph_bwrt_in(){

	$filecache="/usr/share/artica-postfix/ressources/logs/web/BWMRT_IN.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{network_inbound_size}";
	$timetext="{minutes}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="Mn";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");
	
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();


}

function graph_squid_cached_minutes(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/CACHED_HOUR.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{cached_data} {this_hour}";
	$timetext="{minutes}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="Mn";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph_squid_cached_hours(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/CACHED_DAY.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{cached_data} {this_day}";
	$timetext="{hours}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="H";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}

function graph_squid_cached_hier_hours(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/CACHED_HIER_DAY.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{cached_data} {yesterday}";
	$timetext="{hours}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="H";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}

function graph_squid_not_cached_minutes(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HOUR.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{not_cached_data} {this_hour}";
	$timetext="{minutes}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="{minute}:";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");
	
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}

function graph_squid_not_cached_hours(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_DAY.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{not_cached_data} {this_day}";
	$timetext="{hours}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="{hour}:";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}

function graph_squid_not_cached_hier_hours(){
	$filecache="/usr/share/artica-postfix/ressources/logs/web/NOT_CACHED_HIER_DAY.db";
	if(!is_file($filecache)){if($GLOBALS["VERBOSE"]){echo "$filecache no such file\n<br>";}return;}
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{not_cached_data} {yesterday}";
	$timetext="{hours}";
	$ARRAY=unserialize(@file_get_contents($filecache));
	$xdata=$ARRAY[0];
	$ydata=$ARRAY[1];
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} KB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix="{hour}:";
	$highcharts->LegendSuffix=$tpl->_ENGINE_parse_body("KB");

	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}
