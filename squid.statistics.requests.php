<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
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
	
if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
if(isset($_GET["requeteur-js"])){requeteur_js();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["list"])){list1();exit;}



	

	
page();


function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ahref_sys="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$page?requeteur-js=yes')\">";
	echo $tpl->_ENGINE_parse_body("$ahref_sys{build_the_query}</a>");
}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{requests}");
	echo "YahooWin('670','$page?requeteur-popup=yes','$build_the_query');";
}

function requeteur_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	$t=time();
	$per["1m"]="{minute}";
	$per["5m"]="5 {minutes}";
	$per["10m"]="10 {minutes}";
	$per["1h"]="{hour}";
	$per["1d"]="{day}";
	
	$Maxlines[50]=50;
	$Maxlines[100]=100;
	$Maxlines[150]=150;
	$Maxlines[200]=200;
	
	$q=new influx();
	$date_start=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_START")));
	$date_end=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_END")));
	$Selectore="mindate:$date_start;maxdate:$date_end";
	
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	$stylelegend="style='vertical-align:top;font-size:18px;padding-top:5px' nowrap";
	
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
					
		<td $stylelegend class=legend>{members}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$_SESSION["SQUID_STATS_MEMBER"],"blur()",null,0,"font-size:18px;")."</td>		
	</tr>
	<tr>	
		<td $stylelegend class=legend>{max_lines}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($Maxlines,"Maxlines-$t",$_SESSION["SQUID_STATS_MAX_LINES"],"blur()",null,0,"font-size:18px;")."</td>				
	</tr>
	<tr>					
		<td $stylelegend class=legend nowrap>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>		
		
		<td $stylelegend class=legend nowrap>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>
	</tr>
	<tr>
		<td $stylelegend class=legend>{search}:</td>
		<td colspan=2 style='vertical-align:top;font-size:18px'>". Field_text("search-$t",$_SESSION["SQUID_STATS_MEMBER_SEARCH"],";font-size:18px;width:99%")."</td>
	</tr>
	<tr>
		<td style='vertical-align:top;font-size:18px;text-align:right' colspan=2>". button("{search}","Run$t()",26)."</td>
	</tr>
	</table>
	</div>
<script>
function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var user=document.getElementById('members-$t').value;
	var Maxlines=document.getElementById('Maxlines-$t').value;
	var search=encodeURIComponent(document.getElementById('search-$t').value);
	var interval=0;
	
	LoadAjax('table-squid-stats-requests','$page?query-js=yes&container=graph-$t&Maxlines='+Maxlines+'&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval+'&user='+user+'&search='+search);
	
	
}
</script>				
				
				
";
	
echo $tpl->_ENGINE_parse_body($html);
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	$t=time();
	
	echo "<div style='float:right;margin:5px;margin-top:-47px'>".button($tpl->_ENGINE_parse_body("{build_the_query}"), "Loadjs('$page?requeteur-js=yes&t=$t')",16)."</div>";
	$html="	
	
	<div style='width:1490px;margin-bottom:10px' id='table-squid-stats-requests'></div>	
<script>
	LoadAjax('table-squid-stats-requests','$page?query-js=yes&Maxlines={$_SESSION["SQUID_STATS_MAX_LINES"]}&date1={$_SESSION["SQUID_STATS_DATE1"]}&time1={$_SESSION["SQUID_STATS_TIME1"]}&date2={$_SESSION["SQUID_STATS_DATE2"]}&time2={$_SESSION["SQUID_STATS_TIME2"]}&interval=0&user={$_SESSION["SQUID_STATS_MEMBER"]}&search={$_SESSION["SQUID_STATS_MEMBER_SEARCH"]}');
	
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
		
}


function query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$requests=$tpl->_ENGINE_parse_body("{requests}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$TB_WIDTH=570;
	
	$from=strtotime("-1 hour");
	
	
	$title=$tpl->javascript_parse_text("{last_requests} {since}:".date("H:i:s",$from));
	
	$t=time();
	
	$html="
<table class='SQUID_STATISTICS_REQUESTS' style='display: none' id='SQUID_STATISTICS_REQUESTS' style='width:99%'></table>
	<script>
$(document).ready(function(){
	$('#SQUID_STATISTICS_REQUESTS').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$time', name : 'MAC', width : 147, sortable : true, align: 'left'},
	{display: '$website', name : 'SITE', width : 303, sortable : true, align: 'left'},
	{display: '$category', name : 'SITE', width : 199, sortable : true, align: 'left'},
	{display: '$ComputerMacAddress', name : 'MAC', width : 147, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'MAC', width : 147, sortable : true, align: 'left'},
	{display: '$member', name : 'uid', width : 169, sortable : true, align: 'left'},
	{display: '$requests', name : 'requests', width : 127, sortable : false, align: 'right'},
	{display: '$size', name : 'size', width : 127, sortable : false, align: 'right'},
	],
	searchitems : [
	{display: '$website', name : 'SITE'},
	{display: '$ComputerMacAddress', name : 'MAC'},
	{display: '$ipaddr', name : 'IPADDR'},
	{display: '$member', name : 'USERID'},
	
	],
	sortname: 'MAC',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 100,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [100,200,300,500]
	
	});
	});
	
function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
}

</script>	";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function list1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$influx=new influx();
	$q=new mysql_squid_builder();
	$USER_FIELD=$_GET["user"];
	$search=$_GET["search"];
	if($search==null){$search="*";}
	
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	
	if(!isset($_GET["date1"])){
		$from=strtotime("-1 hour");
	}
	
	if(isset($_POST["qtype"])){
		if($_POST["query"]<>null){
			$search=str_replace("*", ".*", $_POST["query"]);
			$search_query="AND {$_POST["qtype"]} =~ /$search/";
		}
		
	}
	
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$md5_table=md5("$from$to$USER_FIELD");
	$_SESSION["SQUID_STATS_MEMBER_SEARCH"]=$search;
	
	$Maxlines=$_GET["Maxlines"];
	$_SESSION["SQUID_STATS_MAX_LINES"]=$Maxlines;
	
	
	
	

	if($search<>"*"){
		//$search_query="AND (SITE =~ /$search/ or $USER_FIELD =~ /$search/)";
	}
	
	$totext="and time < {$to}s";
	
	if(!isset($_POST["rp"])){$_POST["rp"]=100;}
	if($from==$to){
		$totext=null;
	}
	$totext=null;
	$sql="SELECT * from access_log WHERE time > {$from}s $totext $search_query ORDER BY ASC LIMIT {$_POST["rp"]}";

	$main=$influx->QUERY_SQL($sql);
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
	$c=0;
	$fontsize="18px";
	$color=null;
	
	
	$ipClass=new IP();
	
	foreach ($main as $row) {
		$USER=trim($row->USERID);
		if($row->SIZE==0){continue;}
		
		$time=date("H:i:00",InfluxToTime($row->time));
		
		$CURTIME=strtotime($time);
		$CATEGORY=$row->CATEGORY;
		$SITE=$row->SITE;
		$RQS=$row->RQS;
		$MAC_link=null;
		$MAC=$row->MAC;
		$IPADDR=$row->IPADDR;
		$USER=trim($row->USERID);
		$SIZE=$row->SIZE;
		$MD5=md5("$time$MAC$IPADDR$USER$SITE");
		if(!isset($MAIN[$CURTIME][$MD5])){
			$MAIN[$CURTIME][$MD5]["TIME"]=$time;
			$MAIN[$CURTIME][$MD5]["RQS"]=$RQS;
			$MAIN[$CURTIME][$MD5]["MAC"]=$MAC;
			$MAIN[$CURTIME][$MD5]["IPADDR"]=$IPADDR;
			$MAIN[$CURTIME][$MD5]["USER"]=$USER;
			$MAIN[$CURTIME][$MD5]["SIZE"]=$SIZE;
			$MAIN[$CURTIME][$MD5]["SITE"]=$SITE;
			$MAIN[$CURTIME][$MD5]["CATEGORY"]=$CATEGORY;
		}else{
			$MAIN[$CURTIME][$MD5]["RQS"]=$MAIN[$MD5]["RQS"]+$RQS;
			$MAIN[$CURTIME][$MD5]["SIZE"]=$MAIN[$MD5]["SIZE"]+$SIZE;
			$MAIN[$CURTIME][$MD5]["CATEGORY"]=$CATEGORY;
		}
		
		
	}	
	
	krsort($MAIN);
	while (list ($curtime, $array0) = each ($MAIN) ){
	while (list ($MDKey, $array) = each ($array0) ){	
		$USER=trim($array["USER"]);
		
		if($color==null){$color="#F2F0F1";}else{$color=null;}
		$time=$array["TIME"];
		$RQS=$array["RQS"];
		$MAC_link=null;
		$SIZE=$array["SIZE"];
		if($SIZE>1024){
			$size=FormatBytes($SIZE/1024);
		}else{
			$size="{$SIZE} Bytes";
		}
		$MAC=$array["MAC"];
		$IPADDR=$array["IPADDR"];
		$CATEGORY=$array["CATEGORY"];
		$SITE=$array["SITE"];
		$RQS=FormatNumber($RQS);
		
		$c++;

		
		if($ipClass->IsvalidMAC($MAC)){
			$MAC_link="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=".urlencode($MAC)."');\"
			style='font-size:$fontsize;text-decoration:underline'>		
			";
			
		}
		
		
		if($ipClass->isValid($SITE)){
			
			$SITE="<a href=\"https://db-ip.com/$SITE\" style='text-decoration:underline;color:black' target=_new>$SITE</a>";
		}
		
		
		if($c>$_POST["rp"]){break;}
		
		$data['rows'][] = array(
				'id' => $c,
				'cell' => array(
						"<span style='font-size:$fontsize'>{$time}</a></span>",
						"<span style='font-size:$fontsize'>$SITE</a></span>",
						"<span style='font-size:$fontsize'>$CATEGORY</a></span>",
						"<span style='font-size:$fontsize'>$MAC_link{$MAC}</a></span>",
						"<span style='font-size:$fontsize'>{$IPADDR}</a></span>",
						"<span style='font-size:$fontsize'>{$USER}</a></span>",
						"<span style='font-size:$fontsize'>{$RQS}</a></span>",
						"<span style='font-size:$fontsize'>{$size}</a></span>",
						
						)
		);
		

		
	}
	}

	

	$data['total'] = $c;
	echo json_encode($data);
	return;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}