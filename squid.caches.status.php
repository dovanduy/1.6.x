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
	
	
	if(isset($_GET["graph-size"])){graph_size();exit;}
	if(isset($_GET["details"])){details();exit;}
	if(isset($_GET["page"])){page();exit;}
	if(isset($_GET["cpustats"])){cpustats();exit;}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["requests-status"])){requests_status();exit;}
	if(isset($_GET["requests-1"])){requests_status_1();exit;}
	if(isset($_GET["requests-2"])){requests_status_2();exit;}
	if(isset($_GET["CPUS-GP-1"])){cpustats_graphs();exit;}
page_start();

function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["monitor"]='{monitor}';
	$array["memory"]='{memory}';
	$array["caches-status"]='{caches_status}';
	$array["cpustats"]='SMP {status}';
	if($users->AsProxyMonitor){
		$array["loggers-status"]='Loggers {status}';
	}
	
	$array["requests-status"]='{users_requests}';
	$array["smtp-settings"]='{smtp_notifications}';
	
	
	
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){
		if($num=="monitor"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"miniadm.prxy.monitor.php?proxy-service=yes&size=1100&loadjs=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="memory"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.memory.status.php\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="loggers-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.loggers.status.php\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="smtp-settings"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.proxy.watchdog.php?smtp=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}		

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_proxy_monitorv2",1206);
	
	
}


function page_start(){
	$page=CurrentPageName();
	$html="<div style='min-height:600px;width:100%' id='GraphSquidCachesstatus'></div>
	<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('GraphSquidCachesstatus','$page?page=yes');")."</div>
	<script>
		LoadAjax('GraphSquidCachesstatus','$page?page=yes');
	</script>		
	";
	echo $html;
	
	
}


function page(){
	$page=CurrentPageName();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db";
	$array=unserialize(@file_get_contents($cachefile));
	$tpl=new templates();
	$ARRAY=array();
	if(count($array)>0){
		while (list ($cachedir, $ligne) = each ($array) ){
			$PARTITION=$ligne["PARTITION"];
			$cachename=basename($cachedir)." ({$ligne["index"]})";
			$ARRAY[$PARTITION]["SIZE"]=$ligne["DIRPART_INFO"]["TOT"];
			$ARRAY[$PARTITION]["MOUNT"]=$ligne["DIRPART_INFO"]["MOUNT"];
			$ARRAY[$PARTITION]["FREE"]=$ligne["DIRPART_INFO"]["AIV"];
			$ARRAY[$PARTITION]["CACHES"][]=array("NAME"=>$cachename,"SIZE"=>$ligne["FULL_SIZE"]);
			
		}
		
		
		while (list ($partition, $ligne) = each ($ARRAY) ){
			$partitionid=md5($partition);
			$partitionenc=urlencode($partition);
			$js[]="
			function Fsix$partitionid(){
				AnimateDiv('$partitionid');
				Loadjs('$page?graph-size=yes&container=$partitionid&partition=$partitionenc&next=$partitionid-all',true);
			}
			setTimeout(\"Fsix$partitionid()\",800);
			";
			$f[]="
			<div style='width:98%' class=form>
				<div id='$partitionid' style='width:520px;height:450px'></div>
				<p>&nbsp;</p>
				<div id='$partitionid-all' style='min-height:150px;'></div>
			</div>";
			
		}
	}
	
	$cacheTime=filemtime($cachefile);
	$cacheTime_text=date("{l} d",$cacheTime)." {at} ".date("H:i:s",$cacheTime);
	if(count($ARRAY)==0){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("{no_cache_as_been_detected}"));
	}
	echo 
	$tpl->_ENGINE_parse_body("
			<div style='text-align:right;margin:10px;margin-left:20px;float:right'>".button("{refresh}", "Loadjs('squid.refresh-status.php')",18)."</div>
			<div style='font-size:22px'>{caches_status}</div>
	
	<div style='text-align:right;border-top:1px solid #CCCCCC'><i>{generated_on} $cacheTime_text</i></div>").
	
	CompileTr2($f)."<script>".@implode("\n", $js)."</script>";
	
	
}

function graph_size(){
	$page=CurrentPageName();
	$tpl=new templates();
	$target_partition=$_GET["partition"];
	$target_partitionenc=urlencode($target_partition);
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db";
	$array=unserialize(@file_get_contents($cachefile));
	$freeText=$tpl->javascript_parse_text("{free}");
	$OtherText=$tpl->javascript_parse_text("{others}");
	
	while (list ($cachedir, $ligne) = each ($array) ){
		$PARTITION=$ligne["PARTITION"];
		$cachename=basename($cachedir)." ({$ligne["index"]})";
		$ARRAY[$PARTITION]["SIZE"]=$ligne["DIRPART_INFO"]["TOT"];
		$ARRAY[$PARTITION]["MOUNT"]=$ligne["DIRPART_INFO"]["MOUNT"];
		$ARRAY[$PARTITION]["FREE"]=$ligne["DIRPART_INFO"]["AIV"];
		$ARRAY[$PARTITION]["USED"]=$ligne["DIRPART_INFO"]["USED"];
		
		
		
		$ARRAY[$PARTITION]["CACHES"][]=array("NAME"=>$cachename,"SIZE"=>$ligne["FULL_SIZE"]);
	
	}
	
	
	$c=0;
	$TotalOfCaches=0;
	while (list ($index, $ligne) = each ($ARRAY[$target_partition]["CACHES"]) ){
			$cachename=$ligne["NAME"];
			$cachesize=$ligne["SIZE"];
			$TotalOfCaches=$TotalOfCaches+$cachesize;
			
			$cachesize_text=FormatBytes($cachesize/1024);
			$cachesize_text=str_replace("&nbsp;", " ", $cachesize_text);
			
			$cachesize=round(($cachesize/1024)/1000,0);
			$PieData[$cachename." $cachesize_text"]=$cachesize;
			$c++;
		}
	
		$OthersUsed=$ARRAY[$target_partition]["SIZE"]-$TotalOfCaches;
		$OthersUsed=intval($ARRAY[$target_partition]["FREE"]-$OthersUsed);
		if($OthersUsed>0){
			$cachesize_text=FormatBytes($OthersUsed/1024);
			$cachesize_text=str_replace("&nbsp;", " ", $cachesize_text);
			$PieData[$OtherText." $cachesize_text"]=round(($OthersUsed/1024)/1000,0);;
		}
		
		$freeText_size=$cachesize_text=FormatBytes($ARRAY[$target_partition]["FREE"]/1024);
		$freeText_size=str_replace("&nbsp;", " ", $cachesize_text);
		$PieData[$freeText." $freeText_size"]=round(($ARRAY[$target_partition]["FREE"]/1024)/1000,0);
		
	$PARTITION_TEXT="Disk {$ARRAY[$target_partition]["MOUNT"]}";	
	$PARTITION_SIZE=FormatBytes($ARRAY[$target_partition]["SIZE"]/1024);
	$PARTITION_SIZE=str_replace("&nbsp;", " ", $PARTITION_SIZE);
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle=$PARTITION_TEXT;
	$highcharts->Title=$tpl->_ENGINE_parse_body("$PARTITION_TEXT: $PARTITION_SIZE");
	$highcharts->LegendSuffix=" MB";
	echo $highcharts->BuildChart()."\nLoadAjax('{$_GET["next"]}','$page?details=$target_partitionenc')";
	
}

function details(){
	$page=CurrentPageName();
	$tpl=new templates();
	$target_partition=$_GET["partition"];
	$target_partitionenc=urlencode($target_partition);
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db";
	$array=unserialize(@file_get_contents($cachefile));
	$freeText=$tpl->javascript_parse_text("{free}");
	$OtherText=$tpl->javascript_parse_text("{others}");
	
	
	
	while (list ($cachedir, $ligne) = each ($array) ){
		
		$cachename=basename($cachedir);
		$PARTITION=$ligne["PARTITION"];
		if($PARTITION<>$_GET["details"]){continue;}
		$cachename=basename($cachedir);
		$tr[]="
		<table style='width:100%'>
		<tr>
		<td style='width:50px' valign='top'><img src='img/48-idisk-server.png'></td>
		<td>
			<table style='width:100%'
				<tr>
					<td>". pourcentage($ligne["POURC"],0,"green")."</td>
				</tr>
				<tr>
					<td style='font-size:14px'>$cachename: ". FormatBytes($ligne["CURRENT"])."&nbsp;/&nbsp;".FormatBytes($ligne["MAX"])."</td>
				</tr>
				<tr>
					<td style='font-size:11px'>$cachedir</td>
				</tr>							
			</table>
		</td>
		</tr>
		</table>";
	}

	echo CompileTr2($tr);
	
	
}

function requests_status(){
	
	$page=CurrentPageName();
	$cachefile="/usr/share/artica-postfix/ressources/logs/BuilRequestsStats.db";
	$t=time();
	$html="
	<div id='graph-$t' style='width:1150px;height:450px'></div>
	<div id='graph1-$t' style='width:1150px;height:450px'></div>
	
	
	<script>
		Loadjs('$page?requests-1=yes&container=graph-$t&t=$t',true);
	</script>
	
	";
	
	
	echo $html;
}
	
function requests_status_1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/BuilRequestsStats.db";
	
	if(!is_file($filecache)){
		echo "Loadjs('$page?requests-2=yes&container=graph1-{$_GET["t"]}',true);";
		return;}
		$ARRAY=unserialize(@file_get_contents($filecache));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		$xdata=$ARRAY[0];
		$ydata=$ARRAY[1];
	
		$title="{requests_per_second} ({minutes})";
		$timetext="{minutes}";
		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->xAxis=$xdata;
		$highcharts->TitleFontSize="14px";
		$highcharts->AxisFontsize="12px";
		$highcharts->Title=$title;
		$highcharts->yAxisTtitle="{requests}/s";
		$highcharts->xAxisTtitle=$timetext;
		$highcharts->LegendPrefix=null;
		$highcharts->LegendSuffix=$tpl->javascript_parse_text(" {requests}/s");
		$highcharts->xAxis_labels=false;
		$highcharts->datas=array("{requests}"=>$ydata);
		echo $highcharts->BuildChart()."\nLoadjs('$page?requests-2=yes&container=graph1-{$_GET["t"]}',true);";
	
}


function requests_status_2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/BuilRequestsStatsH.db";
	
	if(!is_file($filecache)){
		//echo "Loadjs('$page?requests-2=yes&container=graph1-{$_GET["t"]}',true);";
		return;}
		$ARRAY=unserialize(@file_get_contents($filecache));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		$xdata=$ARRAY[0];
		$ydata=$ARRAY[1];
	
		$title="{requests_per_second} ({hours})";
		$timetext="{hours}";
		$highcharts=new highcharts();
		$highcharts->container=$_GET["container"];
		$highcharts->xAxis=$xdata;
		$highcharts->TitleFontSize="14px";
		$highcharts->AxisFontsize="12px";
		$highcharts->Title=$title;
		$highcharts->yAxisTtitle="{requests}/s";
		$highcharts->xAxisTtitle=$timetext;
		$highcharts->LegendPrefix=null;
		$highcharts->LegendSuffix=$tpl->javascript_parse_text(" {requests}/s");
		$highcharts->xAxis_labels=false;
		$highcharts->datas=array("{requests}"=>$ydata);
		echo $highcharts->BuildChart()."\n// Loadjs('$page?requests-2=yes&container=graph1-{$_GET["t"]}',true);";	
	
}

function cpustats(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/AllSquidKids.db";
	$MAIN=unserialize(@file_get_contents($filecache));
	
	while (list ($CPU, $ARRAY) = each ($MAIN) ){
		
		$requestsS="{$ARRAY["5mn"]["client_http_requests"]} {requests}/s";
		$requestsT=FormatNumber($ARRAY["TOTAL"]["client_http_requests"]);
		
		
		$client_http_kbytes_in="{$ARRAY["5mn"]["client_http_kbytes_in"]} Kbytes/s";
		$client_http_kbytes_out="{$ARRAY["5mn"]["client_http_kbytes_out"]} Kbytes/s";
		
		
		$client_http_kbytes_inT=FormatBytes($ARRAY["TOTAL"]["client_http_kbytes_in"]);
		$client_http_kbytes_outT=FormatBytes($ARRAY["TOTAL"]["client_http_kbytes_out"]);
		
		
		$TOTAL=$TOTAL+$requestsT;
		
		
		$tr[]=$tpl->_ENGINE_parse_body("
		<div style='width:450px;border-radius:5px 5px 5px 5px;border:1px solid #CCCCCC;margin:15px;padding:5px'>
		<table style='width:100%'>
		<tr>
			<td style='width:68px;vertical-align:top'>
				<center>
				<img src='img/processor-64.png' style='margin-bottom:10px'>
				<span style='font-size:18px;font-weigth:bold'>C.P.U #$CPU</span>
				</center>
			</td>
			<td valign='top'>
				<table style='width:100%'>
					<tr>
						<td class=legend style='font-size:14px'>{requests}:</td>
						<td style='font-size:14px;font-weight:bold'>$requestsT <span style='font-size:11px'>$requestsS</span></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{inbound}:</td>
						<td style='font-size:14px;font-weight:bold'>$client_http_kbytes_inT <span style='font-size:11px'>$client_http_kbytes_in</span></td>
					</tr>					
					<tr>
						<td class=legend style='font-size:14px'>{outbound}:</td>
						<td style='font-size:14px;font-weight:bold'>$client_http_kbytes_outT <span style='font-size:11px'>$client_http_kbytes_out</span></td>
					</tr>				
					<tr>
						<td class=legend style='font-size:14px'>{cpu}:</td>
						<td style='font-size:14px;font-weight:bold'>". pourcentage($ARRAY["5mn"]["CPU"])."</td>
					</tr>				
				</table>
			</td>
		</tr>
		</table></div>");
		
		
	}
	
	
	echo CompileTr2($tr)."
	<center>
	<div id='CPUS-GP-1' style='width:600px;height:450px'></div>
	</center>
	
	<script>
		Loadjs('$page?CPUS-GP-1=yes&container=CPUS-GP-1&t=$t',true);
	</script>";
	
}

function cpustats_graphs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$filecache="/usr/share/artica-postfix/ressources/logs/AllSquidKids.db";
	$MAIN=unserialize(@file_get_contents($filecache));
	

	while (list ($CPU, $ARRAY) = each ($MAIN) ){
	
		$requestsS="{$ARRAY["5mn"]["client_http_requests"]} {requests}/s";
		$requestsT=$ARRAY["TOTAL"]["client_http_requests"];
		$PieData["CPU #$CPU"]=$requestsT;
	}
	
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{requests}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("CPUs");
	$highcharts->LegendSuffix="";
	echo $highcharts->BuildChart();	
	
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}