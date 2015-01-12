<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	


	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	if(isset($_GET["graph-hour"])){graph_current_hour();exit;}
	if(isset($_GET["graph-hour-day"])){graph_current_hour_day();exit;}
	if(isset($_GET["graph-hour-month"])){graph_current_month_day();exit;}
	
	if(isset($_GET["details"])){details();exit;}
	if(isset($_GET["page"])){page();exit;}
	if(isset($_GET["cpustats"])){cpustats();exit;}

	
page();


function page(){
	$q=new mysql_squid_builder();
	$timekey=date('Ymd');
	$timekeyMonth=date("Ym");
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="squidmemory_$timekey";
	$TableMonth="squidmemoryM_$timekeyMonth";
	if($q->TABLE_EXISTS($table)){
		if($q->COUNT_ROWS($table)>1){
		$f1[]="<div style='width:1150px;height:340px' id='$time-squid-02'></div>";
		$f2[]="
		function XSquid02$time(){
			AnimateDiv('$time-squid-02');
			Loadjs('$page?graph-hour=yes&container=$time-squid-02&time=$time',true);
		}
		setTimeout(\"XSquid02$time()\",500);";
		
		$f1[]="<div style='width:1150px;height:340px' id='$time-squid-03'></div>";
		$f2[]="
		function XSquid03$time(){
			AnimateDiv('$time-squid-03');
			Loadjs('$page?graph-hour-day=yes&container=$time-squid-03&time=$time',true);
		}
		setTimeout(\"XSquid03$time()\",500);";	
		}	
	}
	
	if($q->TABLE_EXISTS($TableMonth)){
		if($q->COUNT_ROWS($table)>1){
			$f1[]="<div style='width:1150px;height:340px' id='$time-squid-04'></div>";
			$f2[]="
			function XSquid04$time(){
			AnimateDiv('$time-squid-04');
			Loadjs('$page?graph-hour-month=yes&container=$time-squid-04&time=$time',true);
			}
			setTimeout(\"XSquid04$time()\",500);";
			
		}
	}
	
	
	$tpl=new templates();
	echo "<div style='font-size:26px'>".$tpl->_ENGINE_parse_body("{proxy_memory_service_status}")."</div><p>&nbsp;</p>".@implode($f1, "\n")."<script>\n".@implode($f2, "\n")."</script>";
}

function graph_current_hour(){

	$timekey=date('Ymd');
	$time=time();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="squidmemory_$timekey";
	
	$sql="SELECT MINUTE(zDate) as zmin, memoryuse FROM `$table` WHERE HOUR(zDate)=HOUR(NOW())";
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if(strlen($ligne["zmin"])==1){$ligne["zmin"]="0{$ligne["zmin"]}";}
		if(strlen($ligne["zhour"])==1){$ligne["zhour"]="0{$ligne["zhour"]}";}
		$ttime="{$ligne["zmin"]}mn";
		$size=$size/1024;
		$xdata[]=$ttime;
		$ydata[]=$ligne["memoryuse"];
		
	}
	
		$title="{memory_size_this_hour} (MB) ".date("H")."h";
		$timetext="{minutes}";
		
	
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
		//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
		$highcharts->datas=array("{size}"=>$ydata);
		echo $highcharts->BuildChart();
	
	
	
}

function graph_current_hour_day(){
	$timekey=date('Ymd');
	$time=time();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="squidmemory_$timekey";
	
	$sql="SELECT HOUR(zDate) as zhour,AVG(memoryuse) as memoryuse FROM `$table` GROUP BY HOUR(zDate) ORDER BY HOUR(zDate)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if(strlen($ligne["zhour"])==1){$ligne["zhour"]="0{$ligne["zhour"]}";}
		$ttime="{$ligne["zhour"]}h";
		$size=$size/1024;
		$xdata[]=$ttime;
		$ydata[]=$ligne["memoryuse"];
	
	}
	
	$title="{memory_size_this_day} (MB)";
	$timetext="{minutes}";
	
	
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
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
	
	
}

function graph_current_month_day(){

	$timekey=date('Ym');
	$time=time();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="squidmemoryM_$timekey";
	
	$sql="SELECT `day` zhour,memoryuse FROM `$table` ORDER BY `day`";
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if(strlen($ligne["zhour"])==1){$ligne["zhour"]="0{$ligne["zhour"]}";}
		$ttime="{$ligne["zhour"]}";
		$xdata[]=$ttime;
		$ydata[]=$ligne["memoryuse"];
	
	}
	
	$title="{this_month} (MB)";
	$timetext="{day}";
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("m")."/".date("Y")." ";
	$highcharts->LegendSuffix="MB";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
	
	
}


