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



if(!IsRights()){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_POST["EnableBandwithCalculation"])){EnableBandwithCalculation();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}

if(isset($_GET["cpu-day"])){cpu_day();exit;}
if(isset($_GET["cpu-week"])){cpu_week();exit;}
if(isset($_GET["cpu-month"])){cpu_month();exit;}

page();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{your_bandwidth}:{options}");
	echo "YahooWin(990,'$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$EnableBandwithCalculation=intval($sock->GET_INFO("EnableBandwithCalculation"));
	$BandwithCalculationSchedule=intval($sock->GET_INFO("BandwithCalculationSchedule"));
	$schedules[1]="1 {hour}";
	$schedules[2]="2 {hours}";
	$schedules[4]="4 {hours}";
	$schedules[8]="8 {hours}";
	$schedules[24]="1 {day}";
	
	
	$p=Paragraphe_switch_img("{EnableBandwithCalculation}", "{EnableBandwithCalculation_explain}","EnableBandwithCalculation-$t",$EnableBandwithCalculation,null,960);
	
	$field=Field_array_Hash($schedules, "BandwithCalculationSchedule-$t",$BandwithCalculationSchedule,"blur()",null,0,"font-size:26px");
	
	
	$html="
	<div style='width:98%' class=form>
	$p
	
	
	<table style='width:100%'>
	<tbody>
	<tr>
		<td class=legend style='font-size:26px'>{schedule}:</td>
		<td style='font-size:16px'>$field</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",45)."</td>
	</tr>
	</tbody>
	</table>
	</div>	
<script>

var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
}
				
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableBandwithCalculation', document.getElementById('EnableBandwithCalculation-$t').value);
	XHR.appendData('BandwithCalculationSchedule', document.getElementById('BandwithCalculationSchedule-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}  	
	
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableBandwithCalculation(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	
	$sock->getFrameWork("system.php?EnableBandwithCalculation=yes");
	
}

function IsRights(){
	

	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsSystemAdministrator){return true;}
	if($users->AsWebMaster){return true;}
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$html[]="<div style='font-size:30px;margin-bottom:20px'>{your_bandwidth} &nbsp;&nbsp;&laquo;&nbsp;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?js=yes');\" style='text-decoration:underline'>{options}</a>&nbsp;&nbsp;&raquo;</div>";
	if($q->COUNT_ROWS("speedtests", "artica_events")<2){
		echo FATAL_ERROR_SHOW_128("{NO_DATA_COME_BACK_LATER}");
		die();
	
	
	
	}
	
	$html[]="<div id='cpu-day' style='with:1450;height:350px'></div>";
	$js[]="Loadjs('$page?cpu-day=yes');";
	
	
	$html[]="<div id='cpu-week' style='with:1450;height:350px'></div>";
	$js[]="Loadjs('$page?cpu-week=yes');";
		
	$html[]="<div id='cpu-month' style='with:1450;height:350px'></div>";
	$js[]="Loadjs('$page?cpu-month=yes');";	
	
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html)."<script>".@implode("\n" ,$js)."</script>");
	
}

function cpu_week(){
	$tpl=new templates();
	$q=new mysql();
	
	
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,AVG(download) as download FROM speedtests GROUP BY DATE_FORMAT(zDate,'%Y-%m-%d') HAVING  WEEK(zDate)=WEEK(NOW());","artica_events");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["zDate"]." 00:00:00");
		$xAxis[]=date("l",$date);
		$datas[]=$ligne["download"];
		
	}

	$title="{bandwidth_lastweek}";
	$timetext="{day}";
	$highcharts=new highcharts();
	$highcharts->container="cpu-week";
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="kbt/s";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix="kbt/s";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("kbt/s"=>$datas);
	echo $highcharts->BuildChart();
}
function cpu_day(){
	$tpl=new templates();
	$q=new mysql();
	
	$curdat=date("Y-m-d 00:00:00");
	$sql="SELECT zDate,download FROM speedtests WHERE zDate >'$curdat'";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."<br>$sql";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["zDate"]);
		$xAxis[]=date("H:i",$date);
		$datas[]=$ligne["download"];
		
	}
	
	
	$title="{bandwidth_last24h}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="cpu-day";
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="kbt/sec";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix=" kbt/sec";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("kbt/sec"=>$datas);
	echo $highcharts->BuildChart();
}
function cpu_month(){
	$tpl=new templates();
	$q=new mysql();

	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,AVG(download) as download FROM speedtests GROUP BY DATE_FORMAT(zDate,'%Y-%m-%d') HAVING  MONTH(zDate)=MONTH(NOW());","artica_events");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["zDate"]." 00:00:00");
		$xAxis[]=date("l d",$date);
		$datas[]=$ligne["download"];
		
	}



	$title="{bandwidth_this_month}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container="cpu-month";
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="kbt/sec";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix=" kbt/sec";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("kbt/sec"=>$datas);
	echo $highcharts->BuildChart();
}

