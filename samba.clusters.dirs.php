<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.drdb.inc');
	
	
	if(isset($_GET["cluster-table"])){directories_list();exit;}
	
	directories_index();
	
	
function directories_index(){	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	$TB_WIDTH=845;
	$TB2_WIDTH=610;
	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$t=time();
	$directory=$tpl->_ENGINE_parse_body("{directories}");
	$new_directory=$tpl->_ENGINE_parse_body("{new_directory}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$used=$tpl->_ENGINE_parse_body("{used}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$buttons="
	buttons : [
	{name: '$new_volume', bclass: 'Add', onpress : NewVolume$t},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig},
	
	],	";
	

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cluster-table=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'hostname', width :253, sortable : true, align: 'left'},
		{display: '$directory', name : 'DIRNAME', width : 339, sortable : false, align: 'left'},
		{display: '$used', name : 'USED', width : 60, sortable : false, align: 'center'},
		{display: '$AVAILABLE', name : 'AVAILABLE', width : 60, sortable : false, align: 'center'},
		{display: '%', name : 'POURC', width : 60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$directory', name : 'DIRNAME'},
		{display: '$hostname', name : 'hostname'},
		
		],
	sortname: 'DIRNAME',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	
</script>";
	
	echo $html;	
	
}	

function directories_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="glusters_clientssize";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){$q->CheckTables_gluster();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
	
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:16px;'>{$ligne["hostname"]} ({$ligne["client_ip"]})</span>",
			"<span style='font-size:16px;'>{$ligne["DIRNAME"]}</span>",
			"<span style='font-size:16px;'>{$ligne["USED"]}</span>",
			"<span style='font-size:16px;'>{$ligne["AVAILABLE"]}</span>",
			"<span style='font-size:16px;'>{$ligne["POURC"]}%</span>",
		  	

		 )
		);
	}
	
	
echo json_encode($data);	
	
}
