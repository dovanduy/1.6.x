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
	
	if(isset($_GET["mysql-check"])){mysqlcheck();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["database"])){database_table_list();exit;}
	if(isset($_GET["databases-list"])){databases_list_json();exit;}
	if(isset($_GET["tables-list"])){database_table_list_json();exit;}
	if(isset($_POST["mysql-add-db"])){database_create();exit;}
	if(isset($_POST["dropdb"])){database_drop();exit;}
	if(isset($_POST["mysql-empty"])){table_empty();exit;}
	if(isset($_GET["mysql-scan"])){mysql_scan();exit;}
js();	

		
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{browse_mysql_server}',"mysql.index.php");
	$tables_list=$tpl->_ENGINE_parse_body('{tables_list}',"mysql.index.php");
	$uid=$_GET["uid"];
	
	
	$q=new mysql();
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$TitleInstance=$tpl->_ENGINE_parse_body("{instance}:{$_GET["instance-id"]}&raquo;");
		if($q->COUNT_ROWS("mysqldbsmulti","artica_backup")==0){
			$sock=new sockets();
			$sock->getFrameWork("mysql.php?filstats=yes");
		}
	
	}
	if($q->COUNT_ROWS("mysqldbs","artica_backup")==0){
		$sock=new sockets();
		$sock->getFrameWork("mysql.php?filstats=yes");			
	}
	
	$prefix=str_replace(".","_",$page);
	$html="
	var mem_id='';
	
	function {$prefix}LoadMainRI(){
		YahooWin3('650','$page?popup=yes&instance-id={$_GET["instance-id"]}','$title');
		}	
		
		
	function LoadMysqlTables(database){
		YahooWin4('650','$page?database='+database+'&instance-id={$_GET["instance-id"]}','$TitleInstance$tables_list&raquo;'+database);
	}
		
	
	
	{$prefix}LoadMainRI();
	";
	
echo $html;	
}

function database_table_list(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$table=$tpl->_ENGINE_parse_body("{table}");
	$table_size=$tpl->_ENGINE_parse_body("{table_size}");
	$rows_number=$tpl->_ENGINE_parse_body("{rows_number}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$perfrom_empty=$tpl->javascript_parse_text("{perform_empty_ask}");
	$tables=$tpl->javascript_parse_text("{tables}");
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	$privileges=$tpl->_ENGINE_parse_body("{privileges}");
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
	$title=$tpl->_ENGINE_parse_body("{browse_mysql_server_text}");

	$buttons="
	buttons : [
		{name: '<b>$rescan</b>', bclass: 'Reload', onpress : Rescan$t},
		{name: '<b>$privileges</b>', bclass: 'Group', onpress : Privileges$t},
	],";
	
	$html="
	<div id='anim-$t'></div>
	<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
<script>
memedb='';
$(document).ready(function(){
$('#mysql-table-$t').flexigrid({
	url: '$page?tables-list=yes&t=$t&databasename={$_GET["database"]}&instance-id={$_GET["instance-id"]}',
	dataType: 'json',

	colModel : [
		{display: '$table', name : 'tablename', width : 238, sortable : true, align: 'left'},
		{display: '$table_size', name : 'tablesize', width :113, sortable : true, align: 'left'},
		{display: '$rows_number', name : 'tableRows', width :133, sortable : true, align: 'right'},
		{display: 'Mysqlcheck', name : 'none1', width : 31, sortable : false, align: 'left'},
		{display: '$empty', name : 'none2', width : 31, sortable : false, align: 'left'},
		
		
		
	],	
	
	$buttons

	searchitems : [
		{display: '$table', name : 'tablename'},
		
		],
	sortname: 'tablesize',
	sortorder: 'desc',
	usepager: true,
	title: '$mmultiTitle{$_GET["database"]}&raquo;$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 627,
	height: 350,
	singleSelect: true
	
	});   
});

	var x_MysqlCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		}
	
	var x_Rescan$t= function (obj) {
		document.getElementById('anim-$t').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		$('#mysql-table-$t').flexReload();
	}	

	function Rescan$t(){
			var XHR = new XHRConnection();
			XHR.appendData('mysql-scan','{$_GET["database"]}');
			XHR.appendData('instance-id','{$_GET["instance-id"]}');	
			AnimateDiv('anim-$t');
			XHR.sendAndLoad('$page', 'GET',x_Rescan$t);	
	
	}
	
	
	function MysqlCheck(table,database){
		if(confirm('$perfrom_mysqlcheck\\n'+database+'/'+table)){
			var XHR = new XHRConnection();
			XHR.appendData('mysql-check',table);
			XHR.appendData('database',table);	
			XHR.appendData('instance-id','{$_GET["instance-id"]}');	
			XHR.sendAndLoad('$page', 'GET',x_MysqlCheck);
			}
		}



	var x_TableEmpty= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#mysql-table-$t').flexReload();
	}	

	function Privileges$t(){
		Loadjs('mysql.browse.privileges.php?databasename={$_GET["database"]}&instance-id={$_GET["instance-id"]}');
	}

		
	function TableEmpty$t(table,database){
		if(confirm('$perfrom_empty\\n'+database+'/'+table)){
			var XHR = new XHRConnection();
			XHR.appendData('mysql-empty',table);
			XHR.appendData('database',database);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');		
			XHR.sendAndLoad('$page', 'POST',x_TableEmpty);
			}
		}		
	
	


	
</script>";
	
	echo $html;		
	
	
}

function mysql_scan(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("mysql.php?rescan-db=yes&database={$_GET["mysql-scan"]}&instance-id={$_GET["instance-id"]}")));
	echo @implode("\n", $datas);
}



function popup(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$delete_database_ask=$tpl->javascript_parse_text("{delete_database_ask}");	
	$new_database=$tpl->javascript_parse_text("{new_database}");
	$new_database="New database";
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$tables_size=$tpl->_ENGINE_parse_body("{tables_size}");
	if($_GET["instance-id"]>0){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	
	$title=$tpl->_ENGINE_parse_body("$mmultiTitle{browse_mysql_server_text}");
	
			

	$buttons="
	buttons : [
		{name: '<b>$new_database</b>', bclass: 'add', onpress : Add{$_GET["instance-id"]}Database },
		{name: '<b>$tables_size</b>', bclass: 'Db', onpress : DB{$_GET["instance-id"]}Sizes },
	
		],";
	
	$html="
	<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
<script>
memedb='';
$(document).ready(function(){
$('#mysql-table-$t').flexigrid({
	url: '$page?databases-list=yes&t=$t&instance-id={$_GET["instance-id"]}',
	dataType: 'json',
	colModel : [
		{display: '$database', name : 'databasename', width : 283, sortable : true, align: 'left'},
		{display: '$tables_number', name : 'TableCount', width :113, sortable : true, align: 'center'},
		{display: '$database_size', name : 'dbsize', width :133, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
	],
	
	$buttons

	searchitems : [
		{display: '$database', name : 'databasename'},
		
		],
	sortname: 'dbsize',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 627,
	height: 350,
	singleSelect: true
	
	});   
});

	var x_EmptyEvents$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+memedb).remove();
	}	

function DatabaseDelete$t(db,md){
	if(confirm('\"'+db+'\"\\n $delete_database_ask')){
		memedb=md;
		var XHR = new XHRConnection();
		XHR.appendData('dropdb',db);
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		XHR.sendAndLoad('$page', 'POST',x_EmptyEvents$t);
		}
	}
	
	function RefreshTableau$t(){
		$('#mysql-table-$t').flexReload();
	}
	
	var x_Add{$_GET["instance-id"]}Database= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#mysql-table-$t').flexReload();
		setTimeout('RefreshTableau$t()',6000);
	}
	
	function DB{$_GET["instance-id"]}Sizes(){
		Loadjs('mysql.browsesize.php?instance-id={$_GET["instance-id"]}');
	}


	
	function Add{$_GET["instance-id"]}Database(){
		var db=prompt('$new_database');
		if(db){
			var XHR = new XHRConnection();
			XHR.appendData('mysql-add-db','yes');
			XHR.appendData('database',db);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');		
			XHR.sendAndLoad('$page', 'POST',x_Add{$_GET["instance-id"]}Database);		
		}
	}
	
	


	
</script>";
	
	echo $html;	
	
}	

function database_create(){
	$q=new mysql();
	$instance_id=$_POST["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	
	$q->CREATE_DATABASE($_POST["database"]);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($instance_id==0){
		$q->QUERY_SQL("INSERT IGNORE INTO mysqldbs (databasename) VALUES('{$_POST["database"]}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->BuildTables();
	}else{
		$q=new mysql();
		$q->QUERY_SQL("INSERT IGNORE INTO  mysqldbsmulti (databasename,instance_id) VALUES ('{$_POST["database"]}','$instance_id')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?filstats=yes");	
	
}


function database_drop(){
	
	$q=new mysql();
	$instance_id=$_POST["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$q=new mysql_multi($instance_id);}	
	$q->DELETE_DATABASE($_POST["dropdb"]);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	if($instance_id==0){
		$q->QUERY_SQL("DELETE FROM mysqldbs WHERE databasename='{$_POST["dropdb"]}'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->BuildTables();
	}else{
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM mysqldbsmulti WHERE databasename='{$_POST["dropdb"]}' AND instance_id=$instance_id","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?filstats=yes");
	
}


function table_empty(){
	$q=new mysql();
	$instance_id=$_POST["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$q=new mysql_multi($instance_id);}		
	$sql="TRUNCATE TABLE `{$_POST["mysql-empty"]}`";
	$q->QUERY_SQL($sql,$_POST["database"]);
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	
	if($instance_id==0){
		$q->QUERY_SQL("UPDATE mysqldbtables SET tableRows=0,tablesize=0 WHERE tablename='{$_POST["mysql-empty"]}' AND databasename='{$_POST["database"]}'","artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	}else{
		$q=new mysql();
		$q->QUERY_SQL("UPDATE mysqldbtablesmulti SET tableRows=0,tablesize=0 WHERE tablename='{$_POST["mysql-empty"]}' AND databasename='{$_POST["database"]}' AND instance_id='{$_POST["instance-id"]}'","artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?filstats=yes");	
	
}

function databases_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table="mysqldbs";
	$FORCE_FILTER=1;
	if($_GET["instance-id"]>0){
		$table="mysqldbsmulti";
		$FORCE_FILTER="instance_id={$_GET["instance-id"]}";
	}
	
	
	$database="artica_backup";
	$t=$_GET["t"];
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table is empty");}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE  $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("Query return empty array, $sql, ($q->mysql_error)");}
	$ldap=new clladp();
	while ($ligne = mysql_fetch_assoc($results)) {

	
		$databasename=$ligne["databasename"];
		$TableCount=$ligne["TableCount"];
		$dbsize=$ligne["dbsize"];
		
		$md5S=md5($ligne["databasename"]);
		$js="LoadMysqlTables('{$ligne["databasename"]}');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";
		
		$spanStyle1="<span style='font-size:13px;font-weight:bold;color:#5F5656;'>";
		$dbsize=FormatBytes($dbsize/1024);
		
		
		
		$delete="<a href=\"javascript:blur();\" OnClick=\"javascript:DatabaseDelete$t('{$ligne["databasename"]}','$md5S');\"><img src='img/delete-24.png'></a>";
		if($databasename=="artica_backup"){$delete="&nbsp;";}
		if($databasename=="mysql"){$delete="&nbsp;";}
		if($databasename=="zarafa"){$delete="&nbsp;";}	
		if($databasename=="information_schema"){$delete="&nbsp;";}
		if($TableCount==0){$href=null;}		
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<strong style='font-size:14px;style='color:$color'>$href$databasename</a></strong>",
					"$spanStyle1$TableCount</span>",
					"$spanStyle$dbsize</span>",
					$delete
					)
				);		
		

		}

	echo json_encode($data);		
}	


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
} 
	

function database_table_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table="mysqldbtables";
	$database="artica_backup";
	
	$FORCE_FILTER=null;
	if($_GET["instance-id"]>0){
		$table="mysqldbtablesmulti";
		$FORCE_FILTER=" AND instance_id={$_GET["instance-id"]}";
	}	
	
	$t=$_GET["t"];
	
		
	
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("$table is empty");
	}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE databasename='{$_GET["databasename"]}' $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE databasename='{$_GET["databasename"]}'$FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		
		if(!$q->ok){json_error_show("$q->mysql_error<br>$sql");}
		
		$total = $ligne["TCOUNT"];
		if($total==0){if(!$q->ok){json_error_show("No rows<br>$sql");}}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE databasename='{$_GET["databasename"]}' $searchstring $FORCE_FILTER $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql,$database);
	writelogs("Instance:{$_GET["instance-id"]}/$database {".mysql_num_rows($results)." items.} \"$sql\"",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->ok){json_error_show("Instance:{$_GET["instance-id"]}/$database::`$q->mysql_error`<br>$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$ldap=new clladp();
	while ($ligne = mysql_fetch_assoc($results)) {

	
		$tablename=$ligne["tablename"];
		$TableCount=$ligne["tableRows"];
		$dbsize=$ligne["tablesize"];
		
		$md5S=md5($ligne["tablename"]);
		$js="LoadMysqlTables('{$ligne["databasename"]}');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>";
		
		$spanStyle1="<span style='font-size:13px;font-weight:bold;color:#5F5656;'>";
		$dbsize=FormatBytes($dbsize/1024);
		$mysqlcheck=imgtootltip("tables-failed-22.png","MySQL check","MysqlCheck('$tablename','{$_GET["databasename"]}')");
		$TableCount=FormatNumber($TableCount,0,'.',' ',3);
		$databasename=$_GET["databasename"];
		$delete="<a href=\"javascript:blur();\" OnClick=\"javascript:TableEmpty$t('$tablename','{$_GET["databasename"]}');\"><img src='img/table-delete-24.png'></a>";
		$mysqlcheck="<a href=\"javascript:blur();\" OnClick=\"javascript:MysqlCheck('$tablename','{$_GET["databasename"]}');\"><img src='img/tables-failed-22.png'></a>";
		if($databasename=="artica_backup"){$delete="&nbsp;";}
		if($databasename=="mysql"){$delete="&nbsp;";}
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('mysql.browse.table.php?table=$tablename&database={$_GET["databasename"]}&instance-id={$_GET["instance-id"]}')\" style='text-decoration:underline'>";
		
		
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<strong style='font-size:14px;style='color:$color'>$href$tablename</a></strong>",
					"<span style='font-size:14px'>$spanStyle$dbsize</span>",
					"<span style='font-size:14px'>$spanStyle1$TableCount</span>",
					
					$mysqlcheck,
					$delete
					)
				);		
		

		}

	echo json_encode($data);		
}	






function database_infos(){
	
	$database=$_GET["database"];
	$list=TABLE_LIST($database);
	

$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1%>$warn$refresh<br>$add<br>$DEF_ICO_REMOTE_STORAGE</td>
	<td valign='top'>
		<div class=explain>{browse_mysql_server_text}</div>
		<div id='tablemysqllist' style='width:100%;height:550px;overflow:auto'>$list</div>
	</td>
	</tr>
	</table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'mysql.index.php');		
}




function mysqlcheck(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mysql-check=yes&database={$_GET["database"]}&table={$_GET["mysql-check"]}&instance-id={$_GET["instance-id"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
	
}



function MYSQL_NO_CONNECTIONS($q){
	
	$a=Paragraphe("warning64.png","{ERROR_MYSQL_CONNECTION}",$q->mysql_error);
	$html="<table style='width:99 %' class=form>
	<tr>
		<td valign='top'>$a</td>
		<td valign='top'>$i</td>
	</tr>
	<tr>
		<td valign='top'>$s</td>
		<td valign='top'>&nbsp;</td>
	</tr>
	</table>";
	return $html;
}




?>