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
	include_once('ressources/class.rrd.inc');
	$users=new usersMenus();
	if(!$GLOBALS["AS_ROOT"]){if(!$users->AsAnAdministratorGeneric){die();}}
	if(isset($_GET["rtt-hour"])){rtt_hour();exit;}
	if(isset($_GET["rtt-month"])){rtt_month();exit;}
start();


function start(){
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){return;}
	
	$page=CurrentPageName();
	$time=time();
	$f1[]="<div style='width:665px;height:240px' id='$time-2'></div>";
	$f2[]="function FDeux$time(){
		LoadjsSilent('$page?rtt-hour=yes&container=$time-2',false);
	}
	setTimeout(\"FDeux$time()\",500);";
	
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/SQUID_MQUOTAZIE.db";
	if(is_file($cache_file)){
		$array=unserialize(@file_get_contents($cache_file));
		if(count($array)>1){
			$f1[]="<div style='width:665px;height:240px' id='$time-3'></div>";
			$f2[]="function FTrois$time(){
			LoadjsSilent('$page?rtt-month=yes&container=$time-3',false);
			}
			setTimeout(\"FTrois$time()\",800);";
			
			
		}
	}
	
	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;
	
	
}

function rtt_hour(){
	
	
	$date=date("YmdH");
	$table="RTTH_{$date}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(FROM_UNIXTIME(xtime),'%i') as MIN ,SUM(`size`) as `SIZE` FROM `$table` GROUP BY MIN ORDER BY MIN");
	$CountRow=mysql_num_rows($results);
	
	if(mysql_num_rows($results)<2){
		$date=date("YmdH",time()-60);
		$table="RTTH_{$date}";
		$results=$q->QUERY_SQL("SELECT DATE_FORMAT(FROM_UNIXTIME(xtime),'%i') as MIN ,SUM(`size`) as `SIZE` FROM `$table` GROUP BY MIN ORDER BY MIN");
		
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=intval($ligne["SIZE"])/1024;
		$size=$size/1024;
		$min=$ligne["MIN"];
		$xdata[]=$min;
		$ydata[]=$size;
		if($GLOBALS["VERBOSE"]){echo "<li>$min = {$ligne["SIZE"]}Bytes ,{$size}MB</li>";}
	}
	
	
	$title="{downloaded_flow_this_hour} (MB)";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function rtt_month(){
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/SQUID_MQUOTAZIE.db";
	$array=unserialize(@file_get_contents($cache_file));
	$tpl=new templates();
	
	$day=$tpl->_ENGINE_parse_body("{day}");
	while (list ($day, $size) = each ($array) ){
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		$xdata[]=$day;
		$ydata[]=$size;
	}
	
	$title="{downloaded_flow_this_month} (MB)";
	$timetext="{days}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	$highcharts->LegendPrefix="$day ";
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	
}



	

