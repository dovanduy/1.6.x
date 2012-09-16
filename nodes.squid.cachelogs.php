<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
	if(isset($_GET["rows-table"])){rows_table();exit;}
table();


function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=807;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=157;
	$ROW2_WIDTH=607;
	
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width :$ROW1_WIDTH, sortable : true, align: 'left'},
		{display: '$description', name : 'line', width :$ROW2_WIDTH, sortable : true, align: 'left'},
	],
	
	searchitems : [
		{display: '$description', name : 'line'},
		],	
	
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	
	
</script>";
	
	echo $html;	
	
}
function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_blackbox();
	$nodeid=$_GET["nodeid"];
	
	$search='%';
	$table="cachelogs$nodeid";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	if(!$q->TABLE_EXISTS($table)){
		writelogs("`$table` no such table",__FUNCTION__,__FILE__,__LINE__);
	}
	
	writelogs("`$table` ".$q->COUNT_ROWS($table)." items",__FUNCTION__,__FILE__,__LINE__);
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total =$q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs("`$table` $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	writelogs("`$sql` ".mysql_num_rows($results),__FUNCTION__,__FILE__,__LINE__);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
	if(preg_match("#(crashing|failed|No such|FATAL|abnormally|WARNING)#", $ligne["line"])){$color="red";}		
		
		$data['rows'][] = array(
		'id' => $ligne['mac'],
		'cell' => array(
			"<span style='font-size:14px;color:$color'>{$ligne["zDate"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["line"]}</span>",
		 
	
		)
		);
	}
	
	
echo json_encode($data);		

}