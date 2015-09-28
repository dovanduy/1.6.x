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

if(isset($_GET["search"])){search();exit;}
table();


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$zmd5=time();
	$tt=time();
	$SITE=$tpl->javascript_parse_text("{website}");
	$USER=$tpl->javascript_parse_text("{uid}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$IPADDR=$tpl->javascript_parse_text("{ipaddr}");
	
	$chronology=$tpl->javascript_parse_text("{top_members}");

	
	
	
	$html="
	<table class='TABLE-$zmd5' style='display: none' id='TABLE-$zmd5' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#TABLE-$zmd5').flexigrid({
	url: '$page?search=yes&zmd5=$zmd5',
	dataType: 'json',
	colModel : [

	{display: '<span style=font-size:22px>$USER</span>', name : 'USER', width :294, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$MAC</span>', name : 'MAC', width : 193, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$IPADDR</span>', name : 'IPADDR', width : 200, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$hits</span>', name : 'RQS', width : 151, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$size</span>', name : 'SIZE', width : 151, sortable : true, align: 'left'},

	],
	$buttons
	searchitems : [
	{display: '$USER', name : 'USER'},
	{display: '$MAC', name : 'MAC'},
	{display: '$IPADDR', name : 'IPADDR'},
	
	],
	sortname: 'SIZE',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:20px>$chronology</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}
Start$tt();
</script>";
	echo $html;
}
function search(){
	$page=1;
	$zmd5=$_GET["zmd5"];
	$q=new mysql_squid_builder();
	$table="dashboard_currentusers";
	$MyPage=CurrentPageName();



	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";

	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		json_error_show("$table no data",1);
	}



	$fontsize="26px";
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();




	while ($ligne = mysql_fetch_assoc($results)) {
		$USER=$ligne["USER"];
		$BYTES=$ligne["SIZE"];
		$RQS=$ligne["RQS"];
		$MAC=$ligne["MAC"];
		$IPADDR=$ligne["IPADDR"];
		$RQS=FormatNumber($RQS);
		$BYTES=FormatBytes($BYTES/1024);
			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:22px'>$USER</span>",
						"<span style='font-size:22px'>$MAC</a></span>",
						"<span style='font-size:22px'>$IPADDR</span>",
						"<span style='font-size:22px'>$RQS</span>",
						"<span style='font-size:22px'>$BYTES</span>",

				)
		);
	}


	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}