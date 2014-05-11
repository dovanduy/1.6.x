<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

		
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){die('not allowed');}	
	
	if(isset($_GET["items"])){items();exit;}
	if(isset($_POST["add-item"])){items_add();exit;}
	if(isset($_POST["delete-item"])){items_del();exit;}
	popup();
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=350;
	$TB_WIDTH=650;
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_website}");
	
	$title=$tpl->_ENGINE_parse_body("{exclude}&nbsp;&raquo;&raquo;&nbsp;{websites}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$delete=$tpl->javascript_parse_text("{delete}");
	
	$websitename=$tpl->javascript_parse_text("{acls_add_dstdomaindst}");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : GitemReconf$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	
	
	
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$items', name : 'websitename', width :563, sortable : true, align: 'left'},	
		{display: '$delete', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$items', name : 'websitename'},
		

	],
	sortname: 'websitename',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=318','1024','900');
}
function GitemReconf$t(){
	Loadjs('squid.restart.php?onlySquid=yes');
}

var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}
function Delete$t(www,md5){
	if(confirm('$delete '+www+'?')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('delete-item',www);
      	XHR.sendAndLoad('$page', 'POST',x_Delete$t);		
		}
	}
	
var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
     $('#flexRT$t').flexReload();
}	
function NewGItem$t(){
	var www=prompt('$websitename');
	if(!www){return;}
 	var XHR = new XHRConnection();
    XHR.appendData('add-item',www);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		

}



</script>";
	
	echo $html;
}

function items_add(){

	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilter_avwhitedoms")){$q->CheckTables();}
	$q->QUERY_SQL("INSERT IGNORE INTO webfilter_avwhitedoms (websitename) VALUES ('{$_POST["add-item"]}')");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function items_del(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_avwhitedoms WHERE websitename='{$_POST["delete-item"]}'");
	if(!$q->ok){echo $q->mysql_error;}	
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$search='%';
	$table="webfilter_avwhitedoms";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table)==0){
		json_error_show("no data...");
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5($ligne["websitename"]);

	
	$delete=imgsimple("delete-24.png","","Delete$t('{$ligne["websitename"]}','$zmd5')");
	
	
	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["websitename"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}