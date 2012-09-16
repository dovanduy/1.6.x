<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=="verbose"){echo "Verbosed\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
		
	
	$user=new usersMenus();
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
		}
	}
	
	if(isset($_GET["databases-list"])){databases_list_json();exit;}
	if(isset($_GET["popup"])){popup();exit;}

js();	

		
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{mysql_server}',"mysql.index.php");
	$tables_list=$tpl->_ENGINE_parse_body('{tables_list}::{size}',"mysql.index.php");
	$uid=$_GET["uid"];
	
	
	$q=new mysql();
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$TitleInstance=$tpl->_ENGINE_parse_body("{tables_list}::{size}::{instance}:{$_GET["instance-id"]}&raquo;");
	}
	
	$prefix=str_replace(".","_",$page);
	$html="
	var mem_id='';
	
	function {$prefix}LoadMainRI(){
		YahooWin5('650','$page?popup=yes&instance-id={$_GET["instance-id"]}','$tables_list');
		}	
		
	{$prefix}LoadMainRI();
	";
	
echo $html;	
}
function popup(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$table=$tpl->_ENGINE_parse_body("{table}");
	$table_size=$tpl->_ENGINE_parse_body("{table_size}");
	$rows_number=$tpl->_ENGINE_parse_body("{rows_number}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$perfrom_empty=$tpl->javascript_parse_text("{perform_empty_ask}");
	$tables=$tpl->javascript_parse_text("{tables}");
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$total=$tpl->_ENGINE_parse_body("{total}");
	$t=time();
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	

	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$title=$tpl->_ENGINE_parse_body("{browse_mysql_server_text}::{size}");

	
	$html="
	<div id='anim-$t'></div>
	<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
<script>
memedb='';
$(document).ready(function(){
$('#mysql-table-$t').flexigrid({
	url: '$page?databases-list=yes&t=$t&instance-id={$_GET["instance-id"]}',
	dataType: 'json',

	colModel : [
		{display: '$table', name : 'xTables', width : 312, sortable : true, align: 'left'},
		{display: '$rows', name : 'rows', width :59, sortable : true, align: 'left'},
		{display: 'index', name : 'idx', width :59, sortable : true, align: 'right'},
		{display: '$total', name : 'total_size', width : 59, sortable : true, align: 'left'},
		{display: 'idxfrac', name : 'idxfrac', width : 59, sortable : true, align: 'left'},
		
		
		
	],	
	
	$buttons

	searchitems : [
		{display: '$table', name : 'xTables'},
		
		],
	sortname: 'total_size',
	sortorder: 'desc',
	usepager: true,
	title: '$mmultiTitle&raquo;$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 627,
	height: 350,
	singleSelect: true
	
	});   
});
</script>
";
	
echo $html;
}

function databases_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table="(SELECT CONCAT( table_schema, '/', table_name ) as xTables , CONCAT( ROUND( table_rows /1000000, 2 ) , 'M' ) ROWS ,
	CONCAT( ROUND( data_length / ( 1024 *1024 *1024 ) , 2 ) , 'G' ) DATA, 
	CONCAT( ROUND( index_length / ( 1024 *1024 *1024 ) , 2 ) , 'G' ) idx, 
	CONCAT( ROUND( ( data_length + index_length ) / ( 1024 *1024 *1024 ) , 2 ) , 'G' ) total_size, 
	ROUND( index_length / data_length, 2 ) idxfrac FROM information_schema.TABLES ORDER BY data_length + index_length) as t";
	
	if($_GET["instance-id"]>0){$q=new mysql_multi($_GET["instance-id"]);}
	
	
	$database="mysql";
	$t=$_GET["t"];
	//if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table is empty");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error<br>Line:".__LINE__);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error<br>Line:".__LINE__);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM $table WHERE  1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show("Query return an error<hr>$sql<hr>($q->mysql_error)");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("Query return empty array, $sql, ($q->mysql_error)");}
	
	while ($ligne = mysql_fetch_assoc($results)) {

	
		$tablename=$ligne["xTables"];
		$r=explode("/", $tablename);
		$table=$r[1];$db=$r[0];
		$md5S=md5($tablename);
		
		$js="Loadjs('mysql.browse.table.php?table=$table&database=$db&instance-id={$_GET["instance-id"]}');";
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";
		
		$spanStyle1="<span style='font-size:13px;font-weight:bold;color:#5F5656;'>";
		
		$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<strong style='font-size:14px;style='color:$color'>$href$tablename</a></strong>",
					"$spanStyle1{$ligne["ROWS"]}</span>",
					"$spanStyle1{$ligne["idx"]}</span>",
					"$spanStyle1{$ligne["total_size"]}</span>",
					"$spanStyle1{$ligne["idxfrac"]}</span>",
					)
				);		
		

		}

	echo json_encode($data);		
}