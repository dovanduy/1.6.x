<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["progress-js"])){progress_js();exit;}	
	if(isset($_GET["execute-js"])){execute_now_stats_js();exit;}
	if(isset($_POST["execute-now"])){execute_now();exit;}
	if(isset($_GET["squid-stats-central-details"])){squid_stats_central_details();exit;}
	
page();

function execute_now_stats_js(){
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ArticaProxyStatisticsMaxTime=$sock->GET_INFO("ArticaProxyStatisticsMaxTime");
	if(!is_numeric($ArticaProxyStatisticsMaxTime)){$ArticaProxyStatisticsMaxTime=420;}
	if($ArticaProxyStatisticsMaxTime<5){$ArticaProxyStatisticsMaxTime=420;}
	$tt=time();	
	$execute_now_stats_js=$tpl->javascript_parse_text("{execute_now_stats_js}");
	$execute_now_stats_js=str_replace("%x", $ArticaProxyStatisticsMaxTime, $execute_now_stats_js);
	echo "

	var xStart$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		
	}	
	
	function Start$tt(){
		if(!confirm('$execute_now_stats_js')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('execute-now','yes');
		XHR.sendAndLoad('$page', 'POST',xStart$tt);
	
	}
	
	Start$tt();";
	
	
}

function execute_now(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?statistics-central-run=yes");
	
	
}


function progress_js(){
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/squid.stats.progress.inc"));
	$title=$tpl->javascript_parse_text($array["TITLE"]);
	$pourc=$array["POURC"];
	if(!is_numeric($pourc)){$pourc=0;}
	
	if($pourc==0){
		echo "
				
		function Start$tt(){
			if(!document.getElementById('squid-stats-central-progress-title-$t')){return;}
			LoadAjaxSilent('squid-stats-central-details','$page?squid-stats-central-details=yes&t=$t');
			
		}
		
		
		
		setTimeout('Start$tt()',3000);";
		return;
		
	}
	
	
	echo "
	
	function Start$tt(){
		if(!document.getElementById('squid-stats-central-progress-title-$t')){return;}
		LoadAjaxSilent('squid-stats-central-details','$page?squid-stats-central-details=yes&t=$t');
		//Loadjs('$page?progress-js=yes&t=$t',false);
	}
	
	if(document.getElementById('squid-stats-central-progress-title-$t')){
		document.getElementById('squid-stats-central-progress-title-$t').innerHTML='$title';
		$('#squid-stats-central-progress').progressbar({ value: $pourc });
		setTimeout('Start$tt()',4000);
	
	}";	
	
}	
	
function page(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	$tt=time();
	$t=time();
	$html="
	<div id='main-page-squid-statistics-central'>		
	<div class=text-info style='font-size:16px'>{squid_stats_central_progress_explain}</div>
			
			
	<center style='font-size:32px;margin:15px' id='squid-stats-central-progress-title-$t'></center>	
	<div id='squid-stats-central-progress' style='height:50px'></div>		
	<center style='margin:20px'>". button("{execute_now}", "Loadjs('$page?execute-js=yes',false)",26)."</center>
			
	<div id='squid-stats-central-details'></div>	
	</div>
	<script>
	
		$('#squid-stats-central-progress').progressbar({ value: 0 });
		LoadAjaxSilent('squid-stats-central-details','$page?squid-stats-central-details=yes&t=$t');
	</script>
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function squid_stats_central_details(){
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("squid.php?squid-stats-central-status=yes")));
	
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?squid-stats-central-task=yes")));
	
	
	$T[]="<table style='width:100%'>";
	while (list ($taskid, $zArray) = each ($array)){
		$TITLE=$zArray["TITLE"];
		$distance=null;
		if(isset($TIME)){
			$distance=distanceOfTimeInWords($TIME,$zArray["TIME"]);
		}
		$TIME=$zArray["TIME"];
		$date=date("Y-m-d H:i:s",$TIME);
		$T[]="<tr>
				<td width=32px><img src='img/check-32.png'></td>
				<td style='font-size:18px'>$taskid) $TITLE - $date <div style='font-size:12px'>$distance</div></td>
			</tr>
				
				";
	}	
	
	$T[]="</table>";
	
	$DAEMON_STATUS_ROUND=DAEMON_STATUS_ROUND("APP_ARTICA_STATISTICS",$ini,null,0);
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:300px'>	$DAEMON_STATUS_ROUND</td>
		<td style='vertical-align:top;width:99%'>".@implode("\n", $T)."</td>
	</tr>
	</table>	
	<script>
	function Start$tt(){
		Loadjs('$page?progress-js=yes&t=$t',false);
		}
		setTimeout('Start$tt()',1800);
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


