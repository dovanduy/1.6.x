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

if(isset($_GET["dns-day"])){dns_day();exit;}
if(isset($_GET["dns-dayt"])){dns_dayt();exit;}

if(isset($_GET["cpu-week"])){cpu_week();exit;}
if(isset($_GET["dns-month"])){dns_month();exit;}

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
	$sock=new sockets();
	$minperf=$sock->GET_INFO("DNSPerfsPointer");
	if(!is_numeric($minperf)){$minperf=301450;}
	$minperfFloat=$minperf/10000;
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT DNS FROM dashboard_dnsperf_day GROUP BY DNS","artica_events");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$DNS=$ligne["DNS"];
		if($DNS==null){continue;}
		$DNS_TITLE[]=$DNS;
		$DNS_ARRAY[$DNS]=true;
	}
	
	
	$explain=$tpl->_ENGINE_parse_body("{dnsperf_explain}");
	$explain=str_replace("%s", $minperfFloat, $explain);
	
	$html[]="<div style='font-size:30px;margin-bottom:20px'>{dns_performance} ".@implode(",", $DNS_TITLE)."</div>
	<div style='font-size:18px' class=explain>$explain</div>";
	
	if($q->COUNT_ROWS("dashboard_dnsperf_day", "artica_events")<2){
		echo FATAL_ERROR_SHOW_128("{NO_DATA_COME_BACK_LATER}");
		die();
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT DNS FROM dashboard_dnsperf_day GROUP BY DNS","artica_events");
	
	while (list ($DNS,$MAIN) = each ($DNS_ARRAY) ){
		
		$DNS_MD=md5($DNS);
		$html[]="<div id='dns-day-$DNS_MD' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?dns-day=yes&id=dns-day-$DNS_MD&DNS=$DNS');";
	
		$html[]="<div id='dns-dayt-$DNS_MD' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?dns-dayt=yes&id=dns-dayt-$DNS_MD&DNS=$DNS');";		
		
		
		$html[]="<div id='dns-month-$DNS_MD' style='with:1450;height:350px'></div>";
		$js[]="Loadjs('$page?dns-month=yes&id=dns-month-$DNS_MD&DNS=$DNS');";	
	
	}
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html)."<script>".@implode("\n" ,$js)."</script>");
	
}

function dns_dayt(){
	$tpl=new templates();
	$q=new mysql();
	
	
	$sql="SELECT `TIME`,`RESPONSE` FROM dashboard_dnsperf_day WHERE `DNS`='{$_GET["DNS"]}' ORDER BY `TIME`";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."<br>$sql";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["TIME"]);
		$xAxis[]=date("H:i",$date);
		$datas[]=$ligne["RESPONSE"];
	
	}
	
	
	$title="{$_GET["DNS"]} {response_time} {this_day}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{response_time} millisecond";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix=" ms";
	$highcharts->xAxisTtitle=$timetext;
	
	$highcharts->datas=array("ms"=>$datas);
	echo $highcharts->BuildChart();	
	
}

function dns_day(){
	$tpl=new templates();
	$q=new mysql();
	
	
	$sql="SELECT `TIME`,`PERCENT` FROM dashboard_dnsperf_day WHERE `DNS`='{$_GET["DNS"]}' ORDER BY `TIME`";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."<br>$sql";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["TIME"]);
		$xAxis[]=date("H:i",$date);
		$datas[]=$ligne["PERCENT"];
		
	}
	
	
	$title="{$_GET["DNS"]} {percent}/{score} {this_day}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="%";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix=" %";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("%"=>$datas);
	echo $highcharts->BuildChart();
}
function dns_month(){
	$tpl=new templates();
	$q=new mysql();

	$sql="SELECT `TIME`,`PERCENT` FROM dashboard_dnsperf_month WHERE `DNS`='{$_GET["DNS"]}' ORDER BY `TIME`";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."<br>$sql";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["TIME"]);
		$xAxis[]=date("Y-m-d",$date);
		$datas[]=$ligne["PERCENT"];
	
	}
	
	
	$title="{$_GET["DNS"]} {percent}/{score} {this_month}";
	$timetext="{time}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$xAxis;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="%";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{date}: ');
	$highcharts->LegendSuffix=" %";
	$highcharts->xAxisTtitle=$timetext;

	$highcharts->datas=array("%"=>$datas);
	echo $highcharts->BuildChart();
}

