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
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["member-list"])){users_list_json();exit;}
	if(isset($_POST["LinkUser"])){LinkUser();exit;}
	if(isset($_POST["Del-user"])){del_user();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{browse_mysql_server}&raquo;{members}&raquo;{$_GET["databasename"]}","mysql.index.php");
	
	$q=new mysql();
	
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$TitleInstance=$tpl->_ENGINE_parse_body("{instance}:{$_GET["instance-id"]}&raquo;");
		if($q->COUNT_ROWS("mysqldbsmulti","artica_backup")==0){
			$sock=new sockets();
			$sock->getFrameWork("mysql.php?filstats=yes");
		}
	
	}
		
	$prefix=str_replace(".","_",$page);
	$html="
	function {$prefix}LoadMainPI(){
		LoadWinORG2('650','$page?popup=yes&instance-id={$_GET["instance-id"]}&t={$_GET["t"]}&databasename={$_GET["databasename"]}','$TitleInstance$title');
		}	
		
		
	
	{$prefix}LoadMainPI();
	";
	
echo $html;	
}

function popup(){
$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=$_GET["t"];
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$delete_database_ask=$tpl->javascript_parse_text("{delete_database_ask}");	
	$new_user=$tpl->javascript_parse_text("{new_member}");
	$new_database="New database";
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$read=$tpl->_ENGINE_parse_body("{read}");
	$write=$tpl->_ENGINE_parse_body("{write}");
	$admin=$tpl->_ENGINE_parse_body("{admin}");
	$delete_user=$tpl->javascript_parse_text("{delete_user}");
	if($_GET["instance-id"]>0){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	
	$title=$tpl->_ENGINE_parse_body("$mmultiTitle{browse_mysql_server_text}&raquo;{members}&raquo;{$_GET["databasename"]}");
	
			

	$buttons="
	buttons : [
		{name: '<b>$new_user</b>', bclass: 'add', onpress : Add{$_GET["instance-id"]}SMember },
	
		],";
	
	$html="
	<table class='mysqlS-member-$t' style='display: none' id='mysqlS-member-$t' style='width:100%;margin:-10px'></table>
<script>
memedba$t='';
$(document).ready(function(){
$('#mysqlS-member-$t').flexigrid({
	url: '$page?member-list=yes&t=$t&instance-id={$_GET["instance-id"]}&databasename={$_GET["databasename"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: '$members', name : 'User', width :467, sortable : true, align: 'left'},
		{display: 'SELECT', name : 'select', width :31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
	],
	
	$buttons

	searchitems : [
		{display: '$member', name : 'User'},
		
		],
	sortname: 'User',
	sortorder: 'asc',
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
		$('#mysqlS-member-$t').flexReload();
		
	}

	var x_UserDBSelect$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#mysql-member-$t').flexReload();
		
	}
	
	var x_UserDBRemove2$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#mysql-member-$t').flexReload();
		if(!document.getElementById('row'+memedba$t)){
			$('#mysqlS-member-$t').flexReload();
		}else{
			$('#row'+memedba$t).remove();
		}
	}	

	
	function Add{$_GET["instance-id"]}SMember(){
		YahooWin('443','system.mysql.php?members-add=yes&instance-id={$_GET["instance-id"]}&t=$t','$add_user');
	}
	
	function UserDBSelect(uenc){
		var XHR = new XHRConnection();
		XHR.appendData('LinkUser','yes');
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		XHR.appendData('databasename','{$_GET["databasename"]}');
		XHR.appendData('user',uenc);
		XHR.sendAndLoad('$page', 'POST',x_UserDBSelect$t);
			
	}
	
	function UserDBRemove2(uenc,id){
		 if(confirm('$delete_user ?')){
			memedba$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('Del-user','yes');
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.appendData('user',uenc);
			XHR.sendAndLoad('$page', 'POST',x_UserDBRemove2$t);	
		}
	}


	
</script>";
	
	echo $html;	
	
}	

function del_user(){
	$q=new mysql();
	if($_POST["instance-id"]>0){$q=new mysql_multi($_POST["instance-id"]);}
	$databasename=$_POST["databasename"];
	$User=base64_decode($_POST["user"]);	
	if(preg_match("#(.+?)@(.+)#", $User,$re)){$uid=$re[1];$dom=$re[2];}
	
	$sql="DELETE FROM `db` WHERE `Host`='$dom' AND `User`='$uid'";
	$OrginalPassword=$q->mysql_password;
	if(!$q->QUERY_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;	
			if(!$q->QUERY_SQL($sql,"mysql")){
				echo "$uid\nHost:$dom\n\n$q->mysql_error";
				return;
			}
		}			
	}
	
	$sql="DELETE FROM `user` WHERE `Host`='$dom' AND `User`='$uid'";
	if(!$q->QUERY_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;	
			if(!$q->QUERY_SQL($sql,"mysql")){
				echo "$uid\nHost:$dom\n\n$q->mysql_error";
				return;
			}
		}			
	}	
	
	
}


function LinkUser(){
	$q=new mysql();
	if($_POST["instance-id"]>0){$q=new mysql_multi($_POST["instance-id"]);}
	$databasename=$_POST["databasename"];
	$User=base64_decode($_POST["user"]);
	if(preg_match("#(.+?)@(.+)#", $User,$re)){$uid=$re[1];$dom=$re[2];}
	$sql="INSERT IGNORE INTO `db` (`Host`, `Db`, `User`, `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, 
	`Grant_priv`, `References_priv`, `Index_priv`, `Alter_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Create_view_priv`,
	 `Show_view_priv`, `Create_routine_priv`, `Alter_routine_priv`, `Execute_priv`, `Event_priv`, `Trigger_priv`) VALUES
('$dom', '$databasename', '$uid', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N');";
	$OrginalPassword=$q->mysql_password;
	if(!$q->QUERY_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;	
			if(!$q->QUERY_SQL($sql,"mysql")){
				echo "GRANT SELECT user:$uid\nHost:$dom\n\n$q->mysql_error";
				return;
			}
		}			
	}
	
	
	
}


function users_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table="user";
	$FORCE_FILTER=1;
	
	
	$database="mysql";
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
		$host=$ligne["Host"];
		$User=$ligne["User"];
		$md5S=md5("$host@$User");		
		$userenc=base64_encode("$User@$host");
		$select=imgsimple("arrow-right-24.png",null,"UserDBSelect('$userenc')");
		$delete=imgsimple("delete-24.png",null,"UserDBRemove2('$userenc','$md5S')");
			
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<img src='img/winuser.png'>",
					"<strong style='font-size:14px;style='color:$color'>$href$User@$host</a></strong>",
					$select,
					$delete
					)
				);		
		

		}

	echo json_encode($data);		
}



