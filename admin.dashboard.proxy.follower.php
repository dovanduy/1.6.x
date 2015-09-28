<?php
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
if(!$user->AsWebStatisticsAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	exit;
}

if(isset($_GET["follow"])){xqueries();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	echo $tpl->_ENGINE_parse_body("<input type='hidden' id='ZRTRQUESTS_COMPTER' value='0'>
	<div style='text-align:left;font-size:30px;margin-right:20px'>{realtime_requests}</div>
	<div style='float:right;margin-top:-30px'>". imgtootltip("refresh-32.png","{refresh}","LoadAjaxRound('proxy-follower-table','$page?follow=yes&t=$t');")."</div>
	<div style='height:1000px;width:100%;overflow:auto;margin-top:15px' id='proxy-follower-table'></div>
	<script>
		LoadAjaxRound('proxy-follower-table','$page?follow=yes&t=$t');
	</script>
	
	");
	
	
}

function xqueries(){
	$page=CurrentPageName();
	$tpl=new templates();
	$influx=new influx();
	
	$sql="SELECT MAX(ZDATE) AS MAX FROM access_log";
	$main=$influx->QUERY_SQL($sql);
	
	$MAX=$main[0]->MAX;
	$LastEntry=$tpl->time_to_date($MAX,true);
	
	if($GLOBALS["VERBOSE"]){echo "<p style='color:blue'>$MAX -> $LastEntry</p>";}
	
	$from_gmt=$tpl->time_to_date($MAX-300,true);
	$from=QueryToUTC($MAX-300);
	$fromTime=date("Y-m-d H:i:s",$from);
	$ToTime=date("Y-m-d H:i:s",QueryToUTC($MAX));
	$sql="SELECT * from access_log WHERE time > '$fromTime' AND time < '$ToTime'";
	//echo "<hr>$sql</HR>";
	$main=null;
	$influx2=new influx();
	$QUERY2=$influx2->QUERY_SQL($sql);

	
	$color=null;
	$ipClass=new IP();
	$q=new mysql_squid_builder();
	$c=0;$D=0;
	foreach ($QUERY2 as $row) {
		
		$USER=trim($row->USERID);
		$IPADDR=trim($row->IPADDR);
		$MAC=trim($row->MAC);
		if($row->SIZE==0){continue;}
		if(is_numeric($USER)){continue;}
		
		
		$RQS=$row->RQS;
		$time=InfluxToTime($row->time);
		
		$DATEKEY=date("H:00",$time);
		$KEYMD5=md5("$USER$IPADDR$MAC");
		$c=$c+$RQS;
		$D=$D+$row->SIZE;
		if(!isset($MAIN[$DATEKEY][$KEYMD5])){
			$MAIN[$DATEKEY][$KEYMD5]["USER"]=$USER;
			$MAIN[$DATEKEY][$KEYMD5]["IPADDR"]=$IPADDR;
			$MAIN[$DATEKEY][$KEYMD5]["MAC"]=$MAC;
			$MAIN[$DATEKEY][$KEYMD5]["SIZE"]=$row->SIZE;
			$MAIN[$DATEKEY][$KEYMD5]["RQS"]=$RQS;
		}else{
			$MAIN[$DATEKEY][$KEYMD5]["SIZE"]=$MAIN[$DATEKEY][$KEYMD5]["SIZE"]+$row->SIZE;
			$MAIN[$DATEKEY][$KEYMD5]["RQS"]=$MAIN[$DATEKEY][$KEYMD5]["RQS"]+$RQS;
		}
		
		
	}
	$D=FormatBytes($D/1024);
	
	$requests=$tpl->javascript_parse_text("{requests}");
	$last_entry_on=$tpl->javascript_parse_text("{last_entry_on}");
	$since=$tpl->_ENGINE_parse_body("{since}");

	$html[]="
	
	<div style='width:98%' class=form>
	<div style='margin-top:5px;font-size:16px;text-align:right;margin-bottom:15px;font-weight:bold'>
		$since 5mn ($c $requests / $D) UTC:".$tpl->time_to_date($from,true)." - GMT $from_gmt / $last_entry_on: $LastEntry</div>";
	
	
	$html[]="
		
	<table style='width:100%'>";
	
	
	$html[]=$tpl->_ENGINE_parse_body("<tr>
			<th style='font-size:18px'>{time}</th>
			<th style='font-size:18px'>{MAC}</th>
			<th style='font-size:18px'>{ipaddr}</th>
			<th style='font-size:18px'>{uid}</th>
			<th style='font-size:18px'>{requests}</th>
			<th style='font-size:18px'>{size}</th>
			</tr>
			");	
	
		
	while (list ($time, $SUBARRAY) = each ($MAIN) ){	
		while (list ($KEYMD5, $BIGARRAY) = each ($SUBARRAY) ){	
			if($color==null){$color="#F2F0F1";}else{$color=null;}
			$MAC=$BIGARRAY["MAC"];
			$RQS=$BIGARRAY["RQS"];
			$SIZE=$BIGARRAY["SIZE"];
			$USER=$BIGARRAY["USER"];
			$IPADDR=$BIGARRAY["IPADDR"];
			$MAC_link=null;
		
		if($SIZE>1024){
			$size=FormatBytes($SIZE/1024);
		}else{
			$size="{$SIZE}Bytes";
		}
	
		$RQS=FormatNumber($RQS);
		if($ipClass->IsvalidMAC($MAC)){
			$MAC_link="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=".urlencode($MAC)."');\"
			style='font-size:16px;text-decoration:underline;font-weight:bold'>		
			";
			
			if(trim($USER)==null){$USER=$q->MacToUid($MAC);}
			
		}
		
		
		
	
		$html[]="<tr style='background-color:$color'>";
		$html[]="<td style='font-size:16px;width:50px;padding:10px;font-weight:bold'>{$time}</td>";
		$html[]="<td style='font-size:16px;width:50px;padding:10px;font-weight:bold'>$MAC_link{$MAC}</a></td>";
		$html[]="<td style='font-size:16px;width:50px;padding:10px;font-weight:bold'>{$IPADDR}</td>";
		$html[]="<td style='font-size:16px;width:50px;padding:10px;font-weight:bold'>{$USER}</td>";
		$html[]="<td style='font-size:16px;width:50px;text-align:right;padding:10px' nowrap>$RQS</td>";
		$html[]="<td style='font-size:16px;width:50px;text-align:right;padding:10px' nowrap>$size</td>";
		$html[]="</tr>";
	
	}
	
}
	$html[]="</table>";
	$html[]="</div>";
	
	$html[]="
	<script>
		function FollowerRefresh(){
			if(!document.getElementById('ZRTRQUESTS_COMPTER')){ return;}
			var compter=parseInt(document.getElementById('ZRTRQUESTS_COMPTER').value);
			if(compter<10){
				compter=compter+1;
				document.getElementById('ZRTRQUESTS_COMPTER').value=compter;
				setTimeout(\"FollowerRefresh()\",1000);
				return;
			}
			
			document.getElementById('ZRTRQUESTS_COMPTER').value=0;
			if(!document.getElementById('proxy-follower-table')){ return;}
			LoadAjaxSilent('proxy-follower-table','$page?follow=yes&t={$_GET["t"]}');
		}
			
			
	setTimeout(\"FollowerRefresh()\",1000);
	</script>";
		
	
			
	
	
	echo @implode("\n", $html);
	
	
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}