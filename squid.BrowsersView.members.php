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
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["items"])){items();exit;}
	
table();


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	
	
	$tt=time();
	$t=$_GET["t"];
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$QuerySize=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$maintitle=$tpl->_ENGINE_parse_body("{members}");

//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits

$buttons="
	buttons : [
	{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";

$buttons=null;
	
	$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt',
	dataType: 'json',
	colModel : [
	{display: '$ipaddr', name : 'ipaddr', width : 124, sortable : false, align: 'left'},
	{display: '$hostname', name : 'hostname', width :330, sortable : true, align: 'left'},
	{display: '$uid', name : 'uid', width : 226, sortable : true, align: 'left'},
	{display: '$MAC', name : 'MAC', width : 129, sortable : false, align: 'left'},
	{display: '$QuerySize', name : 'size', width : 107, sortable : false, align: 'left'},
	{display: '$hits', name : 'hits', width : 100, sortable : true, align: 'left'},
	],
	$buttons
	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$hostname', name : 'hostname'},
	{display: '$uid', name : 'uid'},
	{display: '$MAC', name : 'MAC'},
	
	
	],
	sortname: 'QuerySize',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$maintitle</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}

Start$tt();
</script>
";
echo $html;
}

//UserAuthDaysGrouped
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();

	$t=$_GET["t"];
	$search='%';
	$table="UserAuthDaysGrouped";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("UserAuthDaysGrouped");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$fontsize="16";

	//ipaddr          | hostname      | uid               | MAC               | account | QuerySize    | hits

	$IpClass=new IP();
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$md=md5(serialize($ligne));
		$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		
		$uiduri="<a href=\"javascript:Loadjs('squid.members.zoom.php?field=uid&value=".urlencode($ligne["uid"])."')\"
				style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		$macencode=urlencode($ligne["MAC"]);
		$MACUri="<a href=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macencode',true);\"
				style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		if(!$IpClass->IsvalidMAC($ligne["MAC"])){$MACUri=null;}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
				"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["ipaddr"]}</span>",
				"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["hostname"]}</span>",
				"<span style='font-size:{$fontsize}px;color:$color'>$uiduri{$ligne["uid"]}</a></span>",
				"<span style='font-size:{$fontsize}px;color:$color'>$MACUri{$ligne["MAC"]}</a></span>",
				"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["QuerySize"]}</span>",
				"<span style='font-size:{$fontsize}px;color:$color'>{$ligne["hits"]}</span>",
				)
				
		);
	}


	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}