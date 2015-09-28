<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");


$user=new usersMenus();
if(!$user->AsDansGuardianAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	exit;
}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["right"])){right();exit;}
if(isset($_GET["BLOCKED_HOUR"])){BLOCKED_HOUR();exit;}
if(isset($_GET["ufdbguard-dash-chart1"])){ufdbguard_dash_chart1();exit;}
if(isset($_GET["ufdbguard-dash-chart2"])){ufdbguard_dash_chart2();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:256px'>
			<div id='dash-webf-left'></div>
		</td>
		<td valign='top' style='99%'>			
			<div id='dash-webf-right'></div>
		</td>
	</tr>
	</table>
	<script>
		LoadAjaxRound('dash-webf-left','$page?status=yes');
		LoadAjaxRound('dash-webf-right','$page?right=yes');
	</script>
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function status_client(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$shield_ok="shield-ok-256.png";
	$shield_disabled="shield-grey-256.png";
	$shield_red="shield-red-256.png";
	$shield_warn="shield-warn-256.png";
	$ini=new Bs_IniHandler();	
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ini->loadString(base64_decode($sock->getFrameWork("squid.php?ufdbguardd-all-status=yes")));
	$c=0;
	while (list ($key, $array) = each ($ini->_params) ){
		$service_name=$array["service_name"];
		$service_disabled=intval($array["service_disabled"]);
		if($service_disabled==0){continue;}
		$running=intval($array["running"]);
	
		$c++;
		if($running==0){
			$icon="disks-128-warn.png";
			$err[]="<tr><td style='font-size:18px;color:#d32d2d;vertical-align:middle' OnClick=\"javascript:GoToServices()\">
			<img src='img/warn-red-32.png' style='float:left;margin-right:10px'>
			{{$service_name}} <span style='text-decoration:underline' $curs> {stopped}</span></td></tr>";
		}
		}
	
	
		if(count($err)>0){
		echo "<center><img src='img/$shield_red'></center>
		<table>". @implode("\n", $err)."</table>";
	}else{
		
		if(!@fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 1)){
			echo $tpl->_ENGINE_parse_body("<center><img src='img/$shield_red'></center>
			<center style='font-size:14px;color:#CC0A0A;width:95%;margin-top:20px'><strong style='font-size:14px'>{warn_ufdbguard_remote_error}</strong>
			<p style='font-size:14px'>{server}:&laquo;{$datas["remote_server"]}&raquo;:{$datas["remote_port"]}<br> {error} $errno $errstr</p></center>");
			
			
			
		}else{
			echo "<center><img src='img/$shield_ok'></center>";
			}
		}
	
	
	
	
	
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px;margin-top:10px;font-weight:bold'>{APP_UFDBGUARD_CLIENT}<br>{running}</center>");
	echo $tpl->_ENGINE_parse_body("<center style='font-size:16px'>{since}:&nbsp;{$ini->_params["APP_UFDBGUARD_CLIENT"]["uptime"]}</center>");
	echo $tpl->_ENGINE_parse_body("<center style='font-size:16px'>{memory}:&nbsp;".FormatBytes($ini->_params["APP_UFDBGUARD_CLIENT"]["master_memory"])."</center>");
	
	
		
}

function status(){
	$sock=new sockets();
	$users=new usersMenus();
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	if($UseRemoteUfdbguardService==1){status_client();exit;}
	$shield_ok="shield-ok-256.png";
	$shield_disabled="shield-grey-256.png";
	$shield_red="shield-red-256.png";
	$shield_warn="shield-warn-256.png";
	$ini=new Bs_IniHandler();
	$err=array();
	$tpl=new templates();
	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	if($sock->EnableUfdbGuard()==0){
		echo "<center><img src='img/$shield_disabled'></center>";
		return;
	}
	
	$ini->loadString(base64_decode($sock->getFrameWork("squid.php?ufdbguardd-all-status=yes")));
	$c=0;
	while (list ($key, $array) = each ($ini->_params) ){
		$service_name=$array["service_name"];
		$service_disabled=intval($array["service_disabled"]);
		if($service_disabled==0){continue;}
		$running=intval($array["running"]);

		$c++;
		if($running==0){
			$icon="disks-128-warn.png";
			$err[]="<tr><td style='font-size:18px;color:#d32d2d;vertical-align:middle' OnClick=\"javascript:GoToServices()\">
			<img src='img/warn-red-32.png' style='float:left;margin-right:10px'>
			{{$service_name}} <span style='text-decoration:underline' $curs> {stopped}</span></td></tr>";
		}
	}

	
	if(count($err)>0){
		echo "<center><img src='img/$shield_red'></center>
		<table>". @implode("\n", $err)."</table>";
	}else{
		echo "<center><img src='img/$shield_ok'></center>";
	}
	
	
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	if($EnableArticaMetaClient==1){
		$LOCAL_VERSION_TEXT=$tpl->time_to_date($sock->GET_INFO("UfdbMetaClientVersion"));
	}else{
		$MAINARR=unserialize(base64_decode($sock->GET_INFO("CATZ_ARRAY")));
		$LOCAL_VERSION_TEXT=$tpl->time_to_date($MAINARR["TIME"]);
		
	}
	
	
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px;margin-top:10px;font-weight:bold'>{running}</center>");
	echo $tpl->_ENGINE_parse_body("<center style='font-size:16px'>{since}:&nbsp;{$ini->_params["APP_UFDBGUARD"]["uptime"]}</center>");
	echo $tpl->_ENGINE_parse_body("<center style='font-size:16px'>{memory}:&nbsp;".FormatBytes($ini->_params["APP_UFDBGUARD"]["master_memory"])."</center>");
	echo "<hr>";
	echo $tpl->_ENGINE_parse_body("<center style='font-size:16px'>{version}:&nbsp;$LOCAL_VERSION_TEXT</center>");
	
	
}


function right(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$jsload=null;
	
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	if($UseRemoteUfdbguardService==0){
		$jsload="Loadjs('$page?BLOCKED_HOUR=yes')";
		$divGraph="
		<div id='ufdbguard-dash-blocked' style='width:1221px;height:240px'></div>
		<table style='width:100%'>
		<tr>
			<td valign='top' style='width:50%'><div id='ufdbguard-dash-chart1' style='width:610px'></div></td>
			<td valign='top' style='width:50%'><div id='ufdbguard-dash-chart2' style='width:610px'></div></td>
		</tr>
		</table>		
		";
		if(!is_file("{$GLOBALS["BASEDIR"]}/BLOCKED_HOUR")){$jsload=null;$jsload2=null;}
		
		$FLUX_HOUR=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_HOUR"));
		if(count($FLUX_HOUR["xdata"])<2){$jsload=null;$divGraph=null;}
		$COUNT_DE_BLOCKED=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_BLOCKED"));
		$title="{webfiltering} &raquo;&raquo; {blocked_events}: ".FormatNumber($COUNT_DE_BLOCKED);
	}else{
		$title="{APP_UFDBGUARD_CLIENT}";
		$divGraph="<div id='ufdbguard-dash-client' style='width:100%'></div>";
		$jsload="LoadAjaxRound('ufdbguard-dash-client','ufdbguard.php?ufdbclient=yes&without-sock=yes');";
		
	}
	
	$html="<div style='font-size:42px;margin-bottom:20px'>$title
	<div style='text-align:right;text-decoration:underline;font-size:16px;padding-right:400px' OnClick=\"javascript:GoToStatisticsByWebFiltering()\">{also_see_webfstatistics}</div>
	</div>
	
	
	$divGraph
	<script>
	$jsload
	</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function BLOCKED_HOUR(){
	$MAIN=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_HOUR"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{blocked_webistes_this_day}";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container="ufdbguard-dash-blocked";
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{events}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text('{today}: ');
	$highcharts->LegendSuffix="";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{events}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();	
	
	if(is_file("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART1")){
		echo "Loadjs('$page?ufdbguard-dash-chart1=yes')";
	}
}
function ufdbguard_dash_chart1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$PieData=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART1"));
	$highcharts=new highcharts();
	$highcharts->container="ufdbguard-dash-chart1";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_rules}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{rules}/{events}");
	echo $highcharts->BuildChart();
	if(is_file("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART2")){
		echo "Loadjs('$page?ufdbguard-dash-chart2=yes')";
	}	
	
	
}
function ufdbguard_dash_chart2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$PieData=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART2"));
	$highcharts=new highcharts();
	$highcharts->container="ufdbguard-dash-chart2";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{top_websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{events}");
	echo $highcharts->BuildChart();	
	
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
