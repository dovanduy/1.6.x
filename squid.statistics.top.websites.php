<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo "<script> alert('". $tpl->javascript_parse_text("`{$_SERVER['PHP_AUTH_USER']}/{$_SERVER['PHP_AUTH_PW']}` {ERROR_NO_PRIVS}")."'); </script>";
	die();
}


if(isset($_GET["events-list"])){events_search();exit;}

page();
function DATE_START(){
	$tpl=new templates();
	$q=new mysql_squid_builder();

	$table="dashboard_countwebsite_day";
	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));


	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	return $tpl->_ENGINE_parse_body("{date_start} $time1, {last_date} $time2");
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{hour}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{website}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");

	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$graph=$tpl->_ENGINE_parse_body("{graph}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	
	$q=new mysql_squid_builder();
	$title=$tpl->_ENGINE_parse_body("{websites}:".DATE_START());

	$html="
<table class='SQUID_ALLWEBSITES_TABLE' style='display: none' id='SQUID_ALLWEBSITES_TABLE' style='width:99%'></table>
<script>
	function StartLogsSquidTable$t(){
	$('#SQUID_ALLWEBSITES_TABLE').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$uri</span>', name : 'FAMILYSITE', width : 414, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'SIZE', width : 110, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$hits</span>', name : 'RQS', width : 110, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$members</span>', name : 'members', width : 110, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$graph</span>', name : 'graph', width : 110, sortable : false, align: 'center'},
	],
		

	searchitems : [
	{display: '$sitename', name : 'FAMILYSITE'},
	],
	sortname: 'SIZE',
	sortorder: 'DESC',
	usepager: true,
	title: '<span style=\"font-size:26px\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});

}
setTimeout('StartLogsSquidTable$t()',800);

</script>
";
	echo $html;

}
function events_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$rp=0;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$data = array();
	$data['rows'] = array();
	$data['page'] = $page;
	
	if($searchstring==null){
		$data['total'] = $q->COUNT_ROWS("FAMILY_SITES_DAY");
		
	}else{
		$sql="SELECT COUNT(*) as tcount FROM FAMILY_SITES_DAY WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$data['total']=$ligne["tcount"];
	}
	
	$sql="SELECT * FROM FAMILY_SITES_DAY WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	

	
	
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	
	$tpl=new templates();
	
	$font_size="font-size:16px";
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$FAMILYSITE=$ligne["familysite"];
		$RQS=$ligne["hits"];
		$SIZE=$ligne["size"];
		$color="#000000";
		
		
		
		$ident=array();
		
		if(intval($SIZE)>=1024){
			$SIZE=FormatBytes(intval($SIZE)/1024);
		}else{
			$SIZE="{$SIZE}Bytes";
		}
		
		$RQS=FormatNumber($RQS);
		$md=md5(serialize($ligne));
		$spanON="<span style='color:$color;$font_size'>";
		$spanOFF="</span>";
		$cached_text=null;
		$colorDiv=$color;
		if($colorDiv=="black"){$colorDiv="transparent";}
		$FAMILYSITE_ENCODED=urlencode($FAMILYSITE);
		$members=imgsimple("view_members-32.png",null,"Loadjs('squid.statistics.top.websites.members.php?FAMILYSITE=$FAMILYSITE_ENCODED')");
		$graph=	imgsimple("graph2-32.png",null,"Loadjs('squid.statistics.top.websites.graph.php?FAMILYSITE=$FAMILYSITE_ENCODED')");
		
		
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						"$spanON$FAMILYSITE$spanOFF",
						"$spanON$SIZE$spanOFF",
						"$spanON$RQS$spanOFF",
						"<center>$members</center>",
						"<center>$graph</center>",
						
					)
				);
		
		

	}


echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}