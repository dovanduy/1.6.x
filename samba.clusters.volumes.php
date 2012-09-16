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
	if(isset($_GET["volume-js"])){volume_js();exit;}
	if(isset($_GET["volume-tab"])){volume_tab();exit;}
	if(isset($_GET["volume-settings"])){volume_settings();exit;}
	
	
	volumes();
	
	
function volume_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$volume_id=$_GET["volume-id"];
	if($volume_id==0){
		$title=$tpl->_ENGINE_parse_body("{new_volume}");
	}else{
		$vol=new gluster_volume($volume_id);
		$title=$vol->volume_name;
	}
	
	echo "YahooWin6('750','$page?volume-tab=yes&volume-id=$volume-id&t={$_GET["t"]}','$title')";
}


function volume_settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$volume_id=$_GET["volume-id"];	
	$t=$_GET["t"];	
	
	
	$volume_types["REPLICATED"]="{replicated}";
	$volume_types["DISTRIBUED"]="{distribued}";
	$volume_types["STRIPED"]="{striped}";
	
	
	
}



function volume_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$volume_id=$_GET["volume-id"];	
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$array["volume-settings"]='{parameters}';
	if($volume_id>0){
		$array["volume-nodes"]='{nodes}';
	}
	
	$fontsize=14;
	if(count($array)>5){$fontsize=11.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&t=$t&volume-id=$volume_id\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	
	<div id=main_samba_clusters_volumess style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_samba_clusters_volumess').tabs();
			});
		</script>";		
	
	
}

	
function volumes(){	
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
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$volume_type=$tpl->_ENGINE_parse_body("{type}");
	$bricks=$tpl->_ENGINE_parse_body("{bricks}");
	$state=$tpl->_ENGINE_parse_body("status");
	
	$buttons="
	buttons : [
	{name: '$new_volume', bclass: 'Add', onpress : NewVolume$t},
	{name: '$build_parameters', bclass: 'Reconf', onpress : ClusterSetConfig$t},
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?cluster-volumes=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$volumes', name : 'volume_name', width :253, sortable : true, align: 'left'},
		{display: '$volume_type', name : 'volume_type', width : 120, sortable : false, align: 'left'},
		{display: '$bricks', name : 'briks', width : 60, sortable : false, align: 'center'},
		{display: '$state', name : 'state', width : 60, sortable : false, align: 'center'},
		{display: '&nbsp;%', name : 'delete', width : 60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$volumes', name : 'volume_name'},
		
		
		],
	sortname: 'volume_name',
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


function NewVolume$t(){
	Loadjs('$page?volume-js=yes&volume-id=0&t=$t');

}

function ClusterSetConfig$t(){
}

	
</script>";
	
	echo $html;	
	
}	

function volumes_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="glusters_volumes";
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
