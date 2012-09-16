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
	if(isset($_POST["user-privs"])){user_priv();exit;}
	if(isset($_POST["user-del"])){user_del();exit;}
	if(isset($_POST["flush-privs"])){flush_privs();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{browse_mysql_server}&raquo;{$_GET["databasename"]}&raquo;{privileges}","mysql.index.php");
	$tables_list=$tpl->_ENGINE_parse_body('{tables_list}',"mysql.index.php");
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
	function {$prefix}LoadMainPI(){
		LoadWinORG('650','$page?popup=yes&instance-id={$_GET["instance-id"]}&databasename={$_GET["databasename"]}','$TitleInstance$title');
		}	
		
		
	
	{$prefix}LoadMainPI();
	";
	
echo $html;	
}

function popup(){
$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	$privileges=$tpl->_ENGINE_parse_body("{privileges}");
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$delete_database_ask=$tpl->javascript_parse_text("{delete_database_ask}");	
	$new_user=$tpl->javascript_parse_text("{new_member}");
	$delete_user=$tpl->javascript_parse_text("{delete_user}");
	$new_database="New database";
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$read=$tpl->_ENGINE_parse_body("{read}");
	$write=$tpl->_ENGINE_parse_body("{write}");
	$admin=$tpl->_ENGINE_parse_body("{admin}");
	$apply_privileges=$tpl->_ENGINE_parse_body("{apply_privileges}");
	if($_GET["instance-id"]>0){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	
	$title=$tpl->_ENGINE_parse_body("$mmultiTitle{browse_mysql_server_text}");
	
			

	$buttons="
	buttons : [
		{name: '<b>$new_user</b>', bclass: 'add', onpress : Add{$_GET["instance-id"]}Member },
		{name: '<b>$apply_privileges</b>', bclass: 'Reload', onpress : ApplyPrivs$t },
	
		],";
	
	$html="
	<table class='mysql-member-$t' style='display: none' id='mysql-member-$t' style='width:100%;margin:-10px'></table>
<script>
memedb$t='';
$(document).ready(function(){
$('#mysql-member-$t').flexigrid({
	url: '$page?member-list=yes&t=$t&instance-id={$_GET["instance-id"]}&databasename={$_GET["databasename"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: '$member', name : 'User', width :344, sortable : true, align: 'left'},
		{display: '$read', name : 'read', width :43, sortable : true, align: 'center'},
		{display: '$write', name : 'write', width :43, sortable : true, align: 'center'},
		{display: '$admin', name : 'admin', width :43, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
	],
	
	$buttons

	searchitems : [
		{display: '$member', name : 'User'},
		
		],
	sortname: 'User',
	sortorder: 'asc',
	usepager: true,
	title: '{$_GET["databasename"]}&nbsp;$privileges',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 627,
	height: 350,
	singleSelect: true
	
	});   
});


	function Add{$_GET["instance-id"]}Member(){
		Loadjs('mysql.browse.members.php?instance-id={$_GET["instance-id"]}&t=$t&databasename={$_GET["databasename"]}');
	}
	
	var x_UpdateDPriv= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		
	}

	var x_DeletePrivs= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		if(!document.getElementById('row'+memedb$t)){alert('row'+memedb$t +' no such id');$('#mysql-member-$t').flexReload();}
		$('#row'+memedb$t).remove();
	}	
	
	function DeletePrivs(uid,md){
		memedb$t=md;
		if(confirm('$delete_user ?')){
			var XHR = new XHRConnection();
			XHR.appendData('user-del','yes');
			XHR.appendData('databasename','{$_GET["databasename"]}');
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.appendData('user',uid);						
			XHR.sendAndLoad('$page', 'POST',x_DeletePrivs);			
		
		}
	
	}
	
	var x_ApplyPrivs$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#mysql-member-$t').flexReload();
	}	
	
	function ApplyPrivs$t(){
		var XHR = new XHRConnection();
		XHR.appendData('flush-privs','yes');
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		XHR.sendAndLoad('$page', 'POST',x_ApplyPrivs$t);		
		
	}
	
	function UpdateDPriv(id,userenc,type){
			var XHR = new XHRConnection();
			if(document.getElementById(id).checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
			XHR.appendData('user-privs',type);
			XHR.appendData('databasename','{$_GET["databasename"]}');
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.appendData('user',userenc);
			XHR.appendData('type',type);						
			XHR.sendAndLoad('$page', 'POST',x_UpdateDPriv);		
		
	}

</script>";
	
	echo $html;	
	
}

function flush_privs(){
	
	$instance_id=$_POST["instance-id"];
	$q=new mysql();
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	$sql="FLUSH PRIVILEGES";	
writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$q->EXECUTE_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		$q->ok=true;
		if(!$q->EXECUTE_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;
			$q->ok=true;	
			if(!$q->EXECUTE_SQL($sql,"mysql")){
				echo "$sql\n\n$q->mysql_error\n";
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
	$table="db";
	$FORCE_FILTER=1;
	
	
	
	$database="mysql";
	$t=$_GET["t"];
	if($q->COUNT_ROWS($table,$database)==0){
		$OrginalPassword=$q->mysql_password;
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		if($q->COUNT_ROWS($table,$database)==0){
			$q->mysql_admin="root";
			$q->mysql_password=null;	
			if($q->COUNT_ROWS($table,$database)==0){
				json_error_show("$table/$database is empty");}
		}			
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
		$admin=0;
		$read=0;
		$write=0;
		$host=$ligne["Host"];
		$User=$ligne["User"];
		$md5S=md5("$host@$User");
		if(ifIsAdmin($ligne)){
			$admin=1;
			$read=1;
			$write=1;
		}else{
			if(ifIsWrite($ligne)){
				$read=1;
				$write=1;	
			}else{
				if(ifIsRead($ligne)){
					$read=1;
				}
			}
		}
		
			$userenc=base64_encode("$User@$host");
			$delete=imgsimple("delete-24.png",null,"DeletePrivs('$userenc','$md5S')");
			
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<img src='img/winuser.png'>",
					"<strong style='font-size:14px;style='color:$color'>$href$User@$host</a></strong>",
					"<span>". Field_checkbox("$md5S-read",1,$read,"UpdateDPriv('$md5S-read','$userenc','r')")."</span>",
					"<span>". Field_checkbox("$md5S-write",1,$write,"UpdateDPriv('$md5S-write','$userenc','w')")."</span>",
					"<span>". Field_checkbox("$md5S-admin",1,$admin,"UpdateDPriv('$md5S-admin','$userenc','a')")."</span>",
					$delete
					)
				);		
		

		}

	echo json_encode($data);		
}

function user_del(){
	$databasename=$_POST["databasename"];
	$User=base64_decode($_POST["user"]);
	$instance_id=$_POST["instance-id"];
	if(preg_match("#(.+?)@(.+)#", $User,$re)){$uid=$re[1];$dom=$re[2];}		
	$q=new mysql();
	if($instance_id>0){$q=new mysql_multi($instance_id);}

	$sql="DELETE FROM `db` WHERE `Host`='$dom' AND `Db`='$databasename' AND `User`='$uid'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$q->QUERY_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		$q->ok=true;
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;
			$q->ok=true;	
			if(!$q->QUERY_SQL($sql,"mysql")){
				echo "user:$uid\nHost:$dom\n\n$q->mysql_error\n$sql";
				return;
			}
		}			
	}	
	
	
}

function user_priv(){
	$admin["Select_priv"]=true;
	$admin["Insert_priv"]=true;
	$admin["Update_priv"]=true;
	$admin["Delete_priv"]=true;
	$admin["Create_priv"]=true;
	$admin["Drop_priv"]=true;
	$admin["Grant_priv"]=true;
	$admin["References_priv"]=true;
	$admin["Index_priv"]=true;
	$admin["Alter_priv"]=true;
	$admin["Create_tmp_table_priv"]=true;
	$admin["Lock_tables_priv"]=true;
	$admin["Create_view_priv"]=true;
	$admin["Show_view_priv"]=true;
	$admin["Create_view_priv"]=true;	
	
	$write["Insert_priv"]=true;
	$write["Update_priv"]=true;
	$write["Delete_priv"]=true;
	$write["Create_priv"]=true;
	$write["Drop_priv"]=true;
	$write["Index_priv"]=true;
	$write["Alter_priv"]=true;
	$write["Create_tmp_table_priv"]=true;
	$write["Create_view_priv"]=true;
	$write["Show_view_priv"]=true;	
	
	$databasename=$_POST["databasename"];
	$User=base64_decode($_POST["user"]);
	$instance_id=$_POST["instance-id"];
	$type=$_POST["type"];
	if(preg_match("#(.+?)@(.+)#", $User,$re)){$uid=$re[1];$dom=$re[2];}	
	
	
	while (list ($num, $none) = each ($admin) ){
		$tt[]="`$num`='N'";
		
	}
	reset($admin);
	$sql="UPDATE `db` SET ".@implode(",", $tt)." WHERE `Host`='$dom' AND `Db`='$databasename' AND `User`='$uid'";
	$tt=array();
	$q=new mysql();
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	$OrginalPassword=$q->mysql_password;
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$q->QUERY_SQL($sql,"mysql")){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		$q->ok=true;
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=null;
			$q->ok=true;	
			if(!$q->QUERY_SQL($sql,"mysql")){
				echo "user:$uid\nHost:$dom\n\n$q->mysql_error\n$sql";
				return;
			}
		}			
	}

	if(($type=="a") && ($_POST["enable"]==1) ){
		while (list ($num, $none) = each ($admin) ){$tt[]="`$num`='Y'";}
		$sql="UPDATE `db` SET ".@implode(",", $tt)." WHERE `Host`='$dom' AND `Db`='$databasename' AND `User`='$uid'";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=$OrginalPassword;
			$q->ok=true;
			if(!$q->QUERY_SQL($sql,"mysql")){
				$q->mysql_admin="root";
				$q->mysql_password=null;
				$q->ok=true;	
				if(!$q->QUERY_SQL($sql,"mysql")){
					echo "user:$uid\nHost:$dom\n\n$q->mysql_error\n$sql";
					return;
				}
			}			
		}
		
		return;
	}
	
	if(($type=="w") && ($_POST["enable"]==1) ){
		$tt=array();
		while (list ($num, $none) = each ($write) ){$tt[]="`$num`='Y'";}
		$sql="UPDATE `db` SET ".@implode(",", $tt)." WHERE `Host`='$dom' AND `Db`='$databasename' AND `User`='$uid'";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=$OrginalPassword;
			$q->ok=true;
			if(!$q->QUERY_SQL($sql,"mysql")){
				$q->mysql_admin="root";
				$q->mysql_password=null;
				$q->ok=true;	
				if(!$q->QUERY_SQL($sql,"mysql")){
					echo "user:$uid\nHost:$dom\n\n$q->mysql_error\n$sql";
					return;
				}
			}			
		}
		
		return;
	}	
	
	if(($type=="r") && ($_POST["enable"]==1) ){
		$tt=array();
		$sql="UPDATE `db` SET `Select_priv`='Y' WHERE `Host`='$dom' AND `Db`='$databasename' AND `User`='$uid'";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->QUERY_SQL($sql,"mysql")){
			$q->mysql_admin="root";
			$q->mysql_password=$OrginalPassword;
			$q->ok=true;
			if(!$q->QUERY_SQL($sql,"mysql")){
				$q->mysql_admin="root";
				$q->mysql_password=null;
				$q->ok=true;	
				if(!$q->QUERY_SQL($sql,"mysql")){
					echo "user:$uid\nHost:$dom\n\n$q->mysql_error\n$sql";
					return;
				}
			}			
		}
		
		return;
	}	
	
	
	
}

function ifIsAdmin($ligne){
	$f["Select_priv"]=true;
	$f["Insert_priv"]=true;
	$f["Update_priv"]=true;
	$f["Delete_priv"]=true;
	$f["Create_priv"]=true;
	$f["Drop_priv"]=true;
	$f["Grant_priv"]=true;
	$f["References_priv"]=true;
	$f["Index_priv"]=true;
	$f["Alter_priv"]=true;
	$f["Create_tmp_table_priv"]=true;
	$f["Lock_tables_priv"]=true;
	$f["Create_view_priv"]=true;
	$f["Show_view_priv"]=true;
	$f["Create_view_priv"]=true;
		
	while (list ($num, $none) = each ($f) ){
		if(strtolower($ligne[$num])<>"y"){return false;}
		
	}
	return true;
}
function ifIsWrite($ligne){
	
	$f["Insert_priv"]=true;
	$f["Update_priv"]=true;
	$f["Delete_priv"]=true;
	$f["Create_priv"]=true;
	$f["Drop_priv"]=true;
	$f["Index_priv"]=true;
	$f["Alter_priv"]=true;
	$f["Create_tmp_table_priv"]=true;
	$f["Create_view_priv"]=true;
	$f["Show_view_priv"]=true;	
		
	while (list ($num, $none) = each ($f) ){
		if(strtolower($ligne[$num])<>"y"){return false;}
		
	}
	return true;
}

function ifIsRead($ligne){
	$f["Select_priv"]=true;
	while (list ($num, $none) = each ($f) ){
		if(strtolower($ligne[$num])<>"y"){return false;}
		
	}
	return true;
}

