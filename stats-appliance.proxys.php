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
if(isset($_GET["list"])){showlist();exit;}
	
	table();
	
function tabs(){
	
$page=CurrentPageName();
$tpl=new templates();
$artica_meta=new mysql_meta();
$array["hour"]='{this_hour}';
$array["month"]='{this_month}';
	
		while (list ($num, $ligne) = each ($array) ){
	
			if($num=="hosts"){
				$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.hosts.php\"><span style='font-size:18px'>$ligne</span></a></li>\n");
				continue;
			}
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?table=yes&type=$num&uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
		}
	
		echo build_artica_tabs($html, "meta-start-uploads");
	
	}	
	
	
function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$hits=$tpl->javascript_parse_text("{files}");
	$size=$tpl->javascript_parse_text("{size}");
	
	
	$t=time();
	$title=$tpl->javascript_parse_text("{proxys_clients_and_uploaded_files}");
	$html="
		<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
		<script>
		function StartLogsSquidTable$t(){
	
		$('#flexRT$t').flexigrid({
		url: '$page?list=yes',
		dataType: 'json',
		colModel : [
		{display: '$ipaddr', name : 'ipaddr', width :142, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 436, sortable : true, align: 'left'},
		{display: 'UPLOAD $hits', name : 'hits', width : 142, sortable : false, align: 'right'},
		{display: 'UPLOAD $size', name : 'size', width : 142, sortable : false, align: 'right'},
		],
	
		searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		],
		sortname: 'hostname',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});
	
	}
	
	StartLogsSquidTable$t();
	</script>
	";
	echo $html;
	}
function showlist(){
	$page=1;
	$q=new mysql_squid_builder();
	$table="StatsApplianceReceiver";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	if(mysql_num_rows($results)==0){
	if(!$q->TABLE_EXISTS($table)){$add=" no table!";}
		json_error_show("no data $add <i>$sql</i>",3);
	}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$hostname=$ligne["hostname"];
		$SIZES=GetSizeFrom($ligne["uuid"]);
		$size=FormatBytes($SIZES[1]/1024);
		$hits=FormatNumber($SIZES[0]);
				
		$ipaddr_enc=urlencode($ipaddr);
		$loupe_mac="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.access.log.php?js=yes&SearchString=$ipaddr&data=&minsize=1')\">
			<img src='img/loupe-32.png' style='float:right'>
			</a>";
				
				
			$data['rows'][] = array(
			'id' => md5(serialize($ligne)),
			'cell' => array("<span style='font-size:18px'>$ipaddr</span>",
			"<span style='font-size:18px'>$hostname</span>",
			"<span style='font-size:18px'>$hits</a></span>",
			"<span style='font-size:18px'>$size</span>",
			)
			);
	}
	
	
	echo json_encode($data);
	}
	
function GetSizeFrom($uuid){
	
	$month=date("Ym");
	$Hour=date("YmdH");
	$tablemonth="{$month}_Mstatsuapp";
	$tableHour="{$Hour}_statsuapp";
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT SUM(size) as size, SUM(hits) as hits FROM $tablemonth WHERE uuid='$uuid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(intval($ligne["hits"]>0)){
		return array($ligne["hits"],$ligne["size"]);
	}
	
	$sql="SELECT SUM(filesize) as size, COUNT(filename) as hits FROM $tableHour WHERE uuid='$uuid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	return array($ligne["hits"],$ligne["size"]);
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}