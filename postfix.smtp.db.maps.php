<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_POST["database-delete"])){database_delete();exit;}
	if(isset($_GET["database-data"])){database_data_switch();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["databases-list"])){database_list();exit;}
	if(isset($_GET["popup-database"])){database_form();exit;}
	if(isset($_GET["database-parms"])){database_params();exit;}
	
	if(isset($_GET["postfix-ldap-databases"])){database_list();exit;}
	
	if(isset($_POST["bind_dn"])){database_ldap_save();exit;}
	

	if(isset($_POST["database-delete-hash-item"])){database_hash_delete();exit;}
	if(isset($_GET["popup-database-hash-list"])){database_hash_list();exit;}
	if(isset($_GET["popup-database-hash-item"])){database_hash_popup();exit;}
	if(isset($_POST["key"])){database_hash_add();exit;}
	if(isset($_POST["ID"])){database_save();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{remote_users_databases}");
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$add=$tpl->_ENGINE_parse_body("{add}");
	$t=time();
	$html="
	
	function remote_users_databases_$t(){
		YahooWin4('736','$page?popup=yes&ou={$_GET["ou"]}&hostname=$hostname','$ou/$hostname::$title');
		}
	
		remote_users_databases_$t();
	";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{remote_databases}");
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$tpl=new templates();
	$t=time();
	
	$delete_database_ask=$tpl->_ENGINE_parse_body("{delete_database_ask}");
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$delete_database_ask=$tpl->javascript_parse_text("{delete_database_ask}");	
	$new_database=$tpl->javascript_parse_text("{new_database}");
	$type=$tpl->_ENGINE_parse_body("{xtype}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$tables_size=$tpl->_ENGINE_parse_body("{tables_size}");
	if($_GET["instance-id"]>0){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	
	$title=$tpl->_ENGINE_parse_body("$hostname&nbsp;&raquo;&nbsp;{remote_users_databases}");
	
			

	$buttons="
	buttons : [
		{name: '<b>$new_database</b>', bclass: 'add', onpress : AddDatabase$t },
		
	
		],";
	
	$html="
	<table class='POSTFIX_EXTERNALDBS' style='display: none' id='POSTFIX_EXTERNALDBS' style='width:100%;margin:-10px'></table>
<script>
memedb$t='';
$(document).ready(function(){
$('#POSTFIX_EXTERNALDBS').flexigrid({
	url: '$page?databases-list=yes&t=$t&hostname=$hostname&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$database', name : 'dbname', width : 283, sortable : true, align: 'left'},
		{display: '$type', name : 'dbtype', width :348, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'center'},
	],
	
	$buttons

	searchitems : [
		{display: '$database', name : 'dbname'},
		
		],
	sortname: 'dbname',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 721,
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
		$('#POSTFIX_EXTERNALDBS').flexReload();
	}
	
	var x_DeletePostfixDatabase$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+memedb$t).remove();
		
	}
	
	
	function DeletePostfixDatabase(ID,did){
		if(confirm('$delete_rule:'+ID+'?')){
			memedb$t=did;
			var XHR = new XHRConnection();
			XHR.appendData('database-delete',ID);
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.sendAndLoad('$page', 'POST',x_DeletePostfixDatabase$t);			
		}
	
	}
	
	
	function DB{$_GET["instance-id"]}Sizes(){
		Loadjs('mysql.browsesize.php?instance-id={$_GET["instance-id"]}');
	}

	function LoadPostfixDatabase(id){
		YahooWin5('650','$page?popup-database=yes&ID='+id+'&ou={$_GET["ou"]}&hostname=$hostname','$ou/$hostname::$title');
	}
	
	function AddDatabase$t(){
		LoadPostfixDatabase(0);
	}
	
	


	
</script>";
	
	echo $html;	
	
	
}

function database_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$hostname=$_GET["hostname"];
	
	$search='%';
	$table="postfix_externaldbs";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	$database="artica_backup";
	$FORCE=" `hostname`='$hostname' ";
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((`{$_POST["qtype"]}` LIKE '$search') OR (`content` LIKE '$search'))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	$pp=new postfix_extern();
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
	$typname=$pp->dbSources[$ligne["dbtype"]].":".$pp->dbTypes[$ligne["postfixdb"]];
	$typname=$tpl->_ENGINE_parse_body("$typname");
	$spanOn="<a href=\"javascript:blur();\" OnClick=\"javascript:LoadPostfixDatabase({$ligne["ID"]});\" style='font-size:16px;font-weight:bold;text-decoration:underline'>";
	$id=md5(serialize($ligne));
	$delete=imgsimple("delete-24.png",null,"DeletePostfixDatabase({$ligne["ID"]},'$id')");
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
	
			$spanOn.$ligne["dbname"]."</a>","$spanOn$typname</a>",$delete )
		);
	}
	
	
echo json_encode($data);		

}

function database_form(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	
	$array["database-parms"]="{parameters}";
	$array["database-data"]='{items}';
	if($ID==0){unset($array["database-data"]);}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]="<li><a href=\"$page?$num=yes&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}&ID=$ID\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_extern_postdb style='width:100%;height:600px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_extern_postdb').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";		
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($tab);	
	
}

function database_ldap_save(){
	$q=new mysql();
	$sql="SELECT content FROM postfix_externaldbs WHERE ID='{$_POST["dbid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$_POST["bind_password"]=url_decode_special_tool($_POST["bind_password"]);
	
	
	$newdata=base64_encode(serialize($_POST));
	$sql="UPDATE postfix_externaldbs SET `content`='$newdata' WHERE ID={$_POST["dbid"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-cfdb={$_POST["hostname"]}");		
	
}


function database_save(){
	$ID=$_POST["ID"];
	$dbname=addslashes($_POST["dbname"]);
	
	$sqlInst="INSERT IGNORE INTO postfix_externaldbs (hostname,dbname,dbtype,postfixdb)
	VALUES('{$_POST["hostname"]}','{$_POST["dbname"]}','{$_POST["dbtype"]}','{$_POST["postfixdb"]}')";
	
	$sql="UPDATE postfix_externaldbs SET dbname='{$_POST["dbname"]}' WHERE ID=$ID;";
	
	if($ID==0){$sql=$sqlInst;}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-cfdb={$_POST["hostname"]}");		
	
}

function database_hash_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$button_name="{add}";	
	$t=time();
	

	
	$html="
	<div id=$t></div>
	<table style='width:99.5%' class=form>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{key}:</td>
			<td>". Field_text("key-$t",null,"width:250px;padding:3px;font-size:16px;font-weight:bolder")."</td>
		</tr>		
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{value}:</td>
			<td>". Field_text("value-$t",null,"width:250px;padding:3px;font-size:16px;font-weight:bolder")."</td>
		</tr>			
		
		<tr>
		<td colspan=2 align='right'><hr>". button("$button_name","SaveHashItem$t()",16)."</td>
		</tr>	
	</table>
	
	<script>
	
	
var X_SaveHashItem$t= function (obj) {
		var results=trim(obj.responseText);
		document.getElementById('$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		YahooWin6Hide();
		$('#POSTFIX_EXTERNAL_HAHS_DBS').flexReload();
	
	}		
function SaveHashItem$t(){
		var XHR = new XHRConnection();
		XHR.appendData('dbid','$ID');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('key',document.getElementById('key-$t').value);
		XHR.appendData('value',document.getElementById('value-$t').value);
		AnimateDiv('$t');   
		XHR.sendAndLoad('$page', 'POST',X_SaveHashItem$t);
		
	}
</script>
";	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function database_hash_add(){
	$q=new mysql();
	$sql="SELECT content FROM postfix_externaldbs WHERE ID='{$_POST["dbid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$datas=unserialize(base64_decode($ligne["content"]));	
	$datas[$_POST["key"]]=$_POST["value"];
	$newdata=base64_encode(serialize($datas));
	$sql="UPDATE postfix_externaldbs SET `content`='$newdata' WHERE ID={$_POST["dbid"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-cfdb={$_POST["hostname"]}");	
}


function database_params(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$button_name="{apply}";
	$t=time();
	
	if($ID==0){$button_name="{add}";}
	$pp=new postfix_extern();

	
	$q=new mysql();
	$sql="SELECT * FROM postfix_externaldbs WHERE ID='$ID'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	
	
	$html="
	<div id=$t></div>
	<table style='width:99.5%' class=form>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{name}:</td>
			<td>". Field_text("dbname-$t",$ligne["dbname"],"width:250px;padding:3px;font-size:16px;font-weight:bolder")."</td>
		</tr>		
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{source}:</td>
			<td>". Field_array_Hash($pp->dbSources,"dbtype-$t",$ligne["dbtype"],"style:padding:3px;font-size:14px")."</td>
		</tr>			
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{database_type}:</td>
			<td>". Field_array_Hash($pp->dbTypes,"postfixdb-$t",$ligne["postfixdb"],"style:padding:3px;font-size:14px")."</td>
		</tr>	
		<tr>
		<td colspan=2 align='right'><hr>". button("$button_name","SaveLDAPS$t()",16)."</td>
		</tr>	
	</table>
	
	<script>
	
	
var X_SaveLDAPS$t= function (obj) {
		var id=$ID;
		var results=trim(obj.responseText);
		document.getElementById('$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		if(id==0){YahooWin5Hide();}
		$('#POSTFIX_EXTERNALDBS').flexReload();
	
	}		
function SaveLDAPS$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ID','$ID');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('dbname',document.getElementById('dbname-$t').value);
		XHR.appendData('dbtype',document.getElementById('dbtype-$t').value);
		XHR.appendData('postfixdb',document.getElementById('postfixdb-$t').value);
		AnimateDiv('$t');   
		XHR.sendAndLoad('$page', 'POST',X_SaveLDAPS$t);
		
	}
	
	function CheckEnabled$t(){
		var id=$ID;
		document.getElementById('postfixdb-$t').disabled=true;
		document.getElementById('dbtype-$t').disabled=true;
		
		if(id==0){
			document.getElementById('postfixdb-$t').disabled=false;
			document.getElementById('dbtype-$t').disabled=false;
		}	
	
	}
CheckEnabled$t();
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function howto(){
$tpl=new templates();	
$html="	
	<div class=explain>{remote_users_databases_howto}</div>
	<div style='height:350px;width:100%;overflow:auto'>
	<table style='width:100%'>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{database_type}:</td>
			<td><code style='font-size:14px'>{virtual_mailbox_maps}</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{server_host}:</td>
			<td><code style='font-size:14px'>192.168.0.100</code></td>
		</tr>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{search_base}:</td>
			<td><code style='font-size:14px'>CN=Users,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{bind_dn}:</td>
			<td><code style='font-size:14px'>CN=Administrator,CN=Users,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{password}:</td>
			<td><code style='font-size:14px'>secret</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{query_filter}:</td>
			<td><code style='font-size:14px'>(&(objectClass=person)(mail=%s))</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{scope}:</td>
			<td><code style='font-size:14px'>sub</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{result_attribute}:</td>
			<td><code style='font-size:14px'>mail</code></td>
		</tr>
	</table>
	<hr>
	<table style='width:100%'>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{database_type}:</td>
			<td><code style='font-size:14px'>{virtual_alias_maps}</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{server_host}:</td>
			<td><code style='font-size:14px'>192.168.0.100</code></td>
		</tr>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{search_base}:</td>
			<td><code style='font-size:14px'>CN=Users,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{bind_dn}:</td>
			<td><code style='font-size:14px'>CN=Administrator,CN=Users,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{password}:</td>
			<td><code style='font-size:14px'>secret</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{query_filter}:</td>
			<td><code style='font-size:14px'>(&(objectClass=person)(otherMailbox=%s))</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{scope}:</td>
			<td><code style='font-size:14px'>sub</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{result_attribute}:</td>
			<td><code style='font-size:14px'>mail</code></td>
		</tr>
	</table>	
	<hr>
	<table style='width:100%'>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{database_type}:</td>
			<td><code style='font-size:14px'>{virtual_alias_maps}</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{server_host}:</td>
			<td><code style='font-size:14px'>192.168.0.100</code></td>
		</tr>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{search_base}:</td>
			<td><code style='font-size:14px'>CN=Builtin,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{bind_dn}:</td>
			<td><code style='font-size:14px'>CN=Administrator,CN=Users,DC=example,DC=local</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{password}:</td>
			<td><code style='font-size:14px'>secret</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{query_filter}:</td>
			<td><code style='font-size:14px'>(&(objectclass=group)(mail=%s))</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{scope}:</td>
			<td><code style='font-size:14px'>sub</code></td>
		</tr>	
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{leaf_result_attribute}:</td>
			<td><code style='font-size:14px'>mail</code></td>
		</tr>
		<tr>
			<td valign='top' class=legend style='font-size:14px'>{special_result_attribute}:</td>
			<td><code style='font-size:14px'>member</code></td>
		</tr>		
	</table>		
	</div>
	";	
		echo $tpl->_ENGINE_parse_body($html);
}

function database_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$hostname=$_POST["hostname"];
	$q=new mysql();
	$sql="DELETE FROM postfix_externaldbs WHERE ID={$_POST["database-delete"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-cfdb={$_POST["hostname"]}");		

}

function database_data_switch(){
	$q=new mysql();
	$ID=$_GET["ID"];
	$sql="SELECT * FROM postfix_externaldbs WHERE ID='$ID'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	switch (intval($ligne["dbtype"])) {
		case 1:
			database_hash();
			return;break;
		
		case 2:
			database_ldap();	
			return;break;
		default:
			;
		break;
	}
	
	echo "<H2>Unknown database type &laquo;{$ligne["dbtype"]}&raquo; !!</H2>";
	
}

function database_ldap(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT content FROM postfix_externaldbs WHERE ID='$ID'";
	$ligneZ=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$ligne=unserialize(base64_decode($ligneZ["content"]));	
	$t=time();
	
	$button_name="{apply}";

	
	$html="
<div id='AdPostfixExtLdapDiv'></div>
	<table style='width:100%'>
	<tr>
	<td valign='top'>". imgtootltip("help-64.png","{howto}","ADExtHowto()")."</td>
	<td valign='top' width=100%>
			<table style='width:99.5%' class=form>		
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{server_host}:</td>
				<td>". Field_text("server_host",$ligne["server_host"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{search_base}:</td>
				<td>". Field_text("search_base",$ligne["search_base"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>	
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{bind_dn}:</td>
				<td>". Field_text("bind_dn",$ligne["bind_dn"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>	
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{password}:</td>
				<td>". Field_password("bind_password-$t",$ligne["bind_password"],"width:120px;padding:3px;font-size:14px")."</td>
			</tr>	
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{query_filter}:</td>
				<td>". Field_text("query_filter",$ligne["query_filter"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>	
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{scope}:</td>
				<td>". Field_text("scope",$ligne["scope"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>	
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{result_attribute}:</td>
				<td>". Field_text("result_attribute",$ligne["result_attribute"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>		
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{leaf_result_attribute}:</td>
				<td>". Field_text("leaf_result_attribute",$ligne["leaf_result_attribute"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>
			<tr>
				<td valign='top' class=legend style='font-size:14px'>{special_result_attribute}:</td>
				<td>". Field_text("special_result_attribute",$ligne["special_result_attribute"],"width:250px;padding:3px;font-size:14px")."</td>
			</tr>
			
			<tr>
			<td colspan=2 align='right'><hr>". button("$button_name","SaveExternalLDAPParameter$t()",16)."</td>
			</tr>	
			</table>
	</td>
	</tr>
	</table>	
	
	<script>
	
	
var X_SaveExternalLDAPParameter$t= function (obj) {
		var results=trim(obj.responseText);
		document.getElementById('AdPostfixExtLdapDiv').innerHTML='';
		if(results.length>0){alert(results);}
		PostfixLoadDatabases();
		YahooWin6Hide();
		$('#POSTFIX_EXTERNALDBS').flexReload();
	}		
function SaveExternalLDAPParameter$t(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('bind_password-$t').value);	
		XHR.appendData('dbid','{$_GET["ID"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('server_host',document.getElementById('server_host').value);
		XHR.appendData('search_base',document.getElementById('search_base').value);
		XHR.appendData('bind_dn',document.getElementById('bind_dn').value);
		XHR.appendData('bind_password',pp);
		XHR.appendData('query_filter',document.getElementById('query_filter').value);
		XHR.appendData('scope',document.getElementById('scope').value);
		XHR.appendData('result_attribute',document.getElementById('result_attribute').value);
		XHR.appendData('special_result_attribute',document.getElementById('special_result_attribute').value);
		XHR.appendData('leaf_result_attribute',document.getElementById('leaf_result_attribute').value);
		AnimateDiv('AdPostfixExtLdapDiv'); 
		XHR.sendAndLoad('$page', 'POSt',X_SaveExternalLDAPParameter$t);
		
	}

	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}	
	
	



function database_hash(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{remote_databases}");
	$ou=$_GET["ou"];
	$hostname=$_GET["hostname"];
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$key=$tpl->_ENGINE_parse_body("{key}");
	$value=$tpl->_ENGINE_parse_body("{value}");
	$delete_item=$tpl->javascript_parse_text("{delete_item}");
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	
	$tables_size=$tpl->_ENGINE_parse_body("{tables_size}");
	
	
	$q=new mysql();
	$sql="SELECT dbname FROM postfix_externaldbs WHERE ID='$ID'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$title=$tpl->_ENGINE_parse_body("$hostname&nbsp;&raquo;&nbsp;{remote_users_databases}&nbsp;&raquo;{$ligne["dbname"]}");
	$ligne["dbname"]=replace_accents($ligne["dbname"]);
			

	$buttons="
	buttons : [
		{name: '<b>$new_item</b>', bclass: 'add', onpress : AddDatabaseItem$t },
		
	
		],";
	
	$html="
	<div style='margin-left:-10px'>
	<table class='POSTFIX_EXTERNAL_HAHS_DBS' style='display: none' id='POSTFIX_EXTERNAL_HAHS_DBS' style='width:100%;margin:-10px'></table>
	</div>
<script>
memedb$t='';
$(document).ready(function(){
$('#POSTFIX_EXTERNAL_HAHS_DBS').flexigrid({
	url: '$page?popup-database-hash-list=yes&t=$t&hostname=$hostname&ID={$_GET["ID"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$key', name : 'dbname', width : 195, sortable : true, align: 'left'},
		{display: '$value', name : 'dbtype', width :348, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'center'},
	],
	
	$buttons

	searchitems : [
		{display: '$database', name : 'dbname'},
		
		],
	sortname: 'dbname',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 621,
	height: 350,
	singleSelect: true
	
	});   
});

	function AddDatabaseItem$t(){
		YahooWin6('360','$page?popup-database-hash-item=yes&ou={$_GET["ou"]}&hostname=$hostname&ID={$_GET["ID"]}','$ou/$hostname::{$ligne["dbname"]}::$new_item');
	}
	
	var x_DeletePostfixDatabaseHashItem$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+memedb$t).remove();
		
	}
	
	
	function DeletePostfixDatabaseHashItem(num,did){
		if(confirm('$delete_item:'+num+'?')){
			memedb$t=did;
			var XHR = new XHRConnection();
			XHR.appendData('database-delete-hash-item',num);
			XHR.appendData('dbid','{$_GET["ID"]}');
			
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.sendAndLoad('$page', 'POST',x_DeletePostfixDatabaseHashItem$t);			
		}
	
	}	

</script>";	
	echo $html;
}

function database_hash_delete(){
	$q=new mysql();
	$sql="SELECT content FROM postfix_externaldbs WHERE ID='{$_POST["dbid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$datas=unserialize(base64_decode($ligne["content"]));	
	unset($datas[$_POST["database-delete-hash-item"]]);
	$newdata=base64_encode(serialize($datas));
	$sql="UPDATE postfix_externaldbs SET `content`='$newdata' WHERE ID={$_POST["dbid"]}";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-multi-cfdb={$_POST["hostname"]}");		
}

function database_hash_list(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT content FROM postfix_externaldbs WHERE ID='$ID'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$datas=unserialize(base64_decode($ligne["content"]));
	if((!is_array($datas)) OR (count($datas)==0) ){json_error_show("No data");}
	
	$spanOn="<span style='font-size:16px;font-weight:bold'>";
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	while (list ($num, $ligne) = each ($datas) ){
		$id=md5(serialize("$num, $ligne"));
		$delete=imgsimple("delete-24.png",null,"DeletePostfixDatabaseHashItem('$num','$id')");
		$c++;
		$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
	
			$spanOn.$num."</span>","$spanOn$ligne</span>",$delete )
		);
	}	
	$data['total'] = $c;
	echo json_encode($data);		
}	
	