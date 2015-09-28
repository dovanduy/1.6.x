<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["SquidEnableISPMode"])){parameters_save();exit;}
	if(isset($_POST["SendTestMessage"])){SendTestMessage();exit;}
	if(isset($_GET["SendTestMessage"])){SendTestMessage();exit;}
	
	if(isset($_GET["blacklist-list"])){categories_list();exit;}
	if(isset($_POST["category"])){categories_save();exit;}
page();
	
function page(){
	
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$explain=$tpl->_ENGINE_parse_body("{global_whitelists_explain}");	
	$TB_WIDTH=755;

	$d=time();
	
	$html="
	<div class=explain style='font-size:14px'>$explain</div>
	<table class='blacklist-table-$t-$d' style='display: none' id='blacklist-table-$t-$d' style='width:99%;margin-left:-10px'></table>
<script>

$(document).ready(function(){
$('#blacklist-table-$t-$d').flexigrid({
	url: '$page?blacklist-list=yes&RULEID=$ID&modeblk={$_GET["modeblk"]}&group=$group$CatzByEnabled&TimeID={$_GET["TimeID"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :28, sortable : false, align: 'center'},
		{display: '$category', name : 'categorykey', width : 108, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 527, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 25, sortable : false, align: 'left'},
		
	],

	searchitems : [
		{display: '$category', name : 'categorykey'},
		{display: '$description', name : 'description'},
		],
	sortname: 'categorykey',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 350,
	singleSelect: true
	
	});   
});

//EnableDisableForceCategory

	var x_EnableDisableForceCategory$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}

function EnableDisableForceCategory$t(category,md){
		var XHR = new XHRConnection();
		if(document.getElementById(md).checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
		XHR.appendData('category',category);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableForceCategory$t);
		
		
}


</script>	";
echo $tpl->_ENGINE_parse_body($html);	
}

function categories_save(){
	$q=new mysql_squid_builder();
	if($_POST["enable"]==1){
		$sql="INSERT IGNORE INTO usersisp_blkwcatz (category) VALUES ('{$_POST["category"]}')";
		$q->QUERY_SQL("");
		
	}else{
		$sql="DELETE FROM usersisp_blkwcatz WHERE category='{$_POST["category"]}'";
		
	}
	$q->QUERY_SQL($sql);
	if(strpos($q->mysql_error," doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL($sql);}
	
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function categories_list(){	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	if(!is_numeric($_GET["TimeID"])){$_GET["TimeID"]=0;}
	$t=$_GET["t"];
	$search='%';
	$table="webfilters_categories_caches";
	
	
	
	
	$page=1;
	$ORDER="ORDER BY categorykey ASC";
	$FORCE_FILTER=null;
	
	$count_webfilters_categories_caches=$q->COUNT_ROWS("webfilters_categories_caches");
	writelogs("webfilters_categories_caches $count_webfilters_categories_caches rows",__FUNCTION__,__FILE__,__LINE__);
	if($count_webfilters_categories_caches==0){
		$ss=new dansguardian_rules();
		$ss->CategoriesTableCache();
	}
	
	if(!$q->TABLE_EXISTS($tableProd)){$q->CheckTables();}
	$sql="SELECT `category` FROM usersisp_blkwcatz";
	$results=$q->QUERY_SQL($sql);
		
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$cats[$ligne["category"]]=true;
	}
		

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `webfilters_categories_caches` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		writelogs("$sql = $total rows",__FUNCTION__,__FILE__,__LINE__);
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `webfilters_categories_caches` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	
	

	

	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `webfilters_categories_caches` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["picture"]==null){$ligne["picture"]="20-categories-personnal.png";}
		$img="img/{$ligne["picture"]}";
		$val=0;
		$zmd5=md5($ligne['categorykey']);
		if($cats[$ligne['categorykey']]){$val=1;}
		$disable=Field_checkbox($zmd5, 1,$val,"EnableDisableForceCategory$t('{$ligne['categorykey']}','$zmd5')");
		
		
	$data['rows'][] = array(
		'id' => $ligne['categorykey'],
		'cell' => array("<img src='$img'>","$js{$ligne['categorykey']}</a>", $ligne['description'],$disable)
		);
	}
	
	
echo json_encode($data);
}