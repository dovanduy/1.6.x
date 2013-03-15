<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["freeweb-aliases-list"])){alias_list();exit;}
	if(isset($_POST["Alias"])){alias_save();exit;}
	if(isset($_POST["DelAlias"])){alias_del();exit;}
	if(isset($_GET["new-alias"])){alias_popup();exit;}
	if(isset($_GET["auth-js"])){auth_js();exit;}
	if(isset($_GET["auth-popup"])){auth_popup();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["connectiontype"])){auth_save();exit;}
	page();	
	
function auth_js(){
	$mdkey=$_GET["mdkey"];
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	header("content-type: application/x-javascript");
	$sql="SELECT * from freeweb_webdav WHERE mdkey='$mdkey'";
	$q=new mysql();
	$resData=$q->QUERY_SQL($sql,"artica_backup");
	$ligne=mysql_fetch_array($resData);
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{authentication}:{$ligne["alias"]}");
	$page=CurrentPageName();
	
	$html="
	function popup$t(){
	YahooWin3('650','$page?auth-popup=yes&t=$t&mdkey=$mdkey&servername={$_GET["servername"]}','$title')
	}
	
	popup$t();";
	
	echo $html;	
	
}


function auth_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$t=$_GET["t"];
	$mdkey=$_GET["mdkey"];
	if($users->APACHE_MOD_AUTHNZ_LDAP){
		
		$CONNECTIONS_TYPE["ad"]="{ActiveDirectory}";
		$CONNECTIONS_TYPE["ldap"]="{ldap}";
	}	
	$q=new mysql();
	$ligne=mysql_fetch_array(
	$q->QUERY_SQL("SELECT * from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$ligne=unserialize(base64_decode($ligne["params"]));
	
	$connect_type=Field_array_Hash($CONNECTIONS_TYPE, "connectiontype-$t",$ligne["connectiontype"],
	"ConnectTypeChangeForm$t()",null,0,"font-size:16px");
	
	$html="<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{connection_type}:</td>
	<td>$connect_type</td>
	</tr>
	</table>
	<div id='cnx-$t'></div>
				
	
	<script>
		function ConnectTypeChangeForm$t(){
			var cnxt=document.getElementById('connectiontype-$t').value;
			LoadAjax('cnx-$t','$page?connection-form=yes&cnxt='+cnxt+'&mdkey=$mdkey&t=$t');
	
		}
	
	ConnectTypeChangeForm$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function auth_save(){
	$mdkey=$_POST["mdkey"];
	
	$q=new mysql();
	$ligne=mysql_fetch_array(
			$q->QUERY_SQL("SELECT * from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$ligne=unserialize(base64_decode($ligne["params"]));	
	
	while (list ($num, $line) = each ($_POST)){
		$ligne[$num]=url_decode_special_tool($_POST[$num]);
	}
	$ligne2=base64_encode(serialize($ligne));
	$ligne2=mysql_escape_string($ligne2);
	$q->QUERY_SQL("UPDATE freeweb_webdav SET `params`='$ligne2' WHERE mdkey='$mdkey'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");	
}

function connection_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$mdkey=$_GET["mdkey"];
	$cnxt=$_GET["cnxt"];

	if($cnxt=="ldap"){connection_form_ldap();exit;}
	if($cnxt=="ad"){connection_form_ad();exit;}


}
function connection_form_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$mdkey=$_GET["mdkey"];
	$cnxt=$_GET["cnxt"];
	$array=array();
	$btname="{apply}";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$array=unserialize(base64_decode($ligne["params"]));

	
	
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]="user@domain.tld";}

	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{hostname}:</td>
	<td>". Field_text("LDAP_SERVER-$tt",$array["LDAP_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$tt",$array["LDAP_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$tt",$array["LDAP_SUFFIX"],"font-size:16px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{username} ({read}):</td>
		<td>". Field_text("LDAP_DN-$tt",$array["LDAP_DN"],"font-size:16px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$tt",$array["LDAP_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{group}:</td>
		<td>". Field_text("ADGROUP-$tt",$array["ADGROUP"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{browse}","Loadjs('BrowseActiveDirectoryGeneric.php?field-user=ADGROUP-$tt&t=$tt&OnlyGroups=yes')",12)."</td>
	<tr>
		<td colspan=2 align='right'>
				<hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='left'>
				<hr>". button("{members}","Loadjs('freeweb.webdavfree.members.php?mdkey=$mdkey')","16px")."</td>
	</tr>	
	</table>
	<script>
		var x_Save$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
			document.getElementById('$tt').innerHTML='';
			$('#$t').flexReload();
		}


		function Save$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('connectiontype','$cnxt');
			XHR.appendData('mdkey', '$mdkey');
			XHR.appendData('servername', '{$_POST["servername"]}');
		
			XHR.appendData('LDAP_SERVER', document.getElementById('LDAP_SERVER-$tt').value);
			XHR.appendData('LDAP_PORT', document.getElementById('LDAP_PORT-$tt').value);
			XHR.appendData('LDAP_SUFFIX', document.getElementById('LDAP_SUFFIX-$tt').value);
			XHR.appendData('LDAP_DN', document.getElementById('LDAP_DN-$tt').value);
			XHR.appendData('ADGROUP', encodeURIComponent(document.getElementById('ADGROUP-$tt').value));
			XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$tt').value));
			AnimateDiv('$tt');
			XHR.sendAndLoad('$page', 'POST',x_Save$tt);
		}


</script>";

	echo $tpl->_ENGINE_parse_body($html);

}

function connection_form_ldap(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$mdkey=$_GET["mdkey"];
	$cnxt=$_GET["cnxt"];
	$array=array();
	$btname="{apply}";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params from freeweb_webdav WHERE mdkey='$mdkey'","artica_backup"));
	$array=unserialize(base64_decode($ligne["params"]));

	
	
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]="cn=Manager,dc=...";}
	if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="?uid";}
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($array["GROUP_ATTRIBUTE"]==null){$array["GROUP_ATTRIBUTE"]="memberUid";}
	

	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{hostname}:</td>
	<td>". Field_text("LDAP_SERVER-$tt",$array["LDAP_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$tt",$array["LDAP_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$tt",$array["LDAP_SUFFIX"],"font-size:16px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN-$tt",$array["LDAP_DN"],"font-size:12px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$tt",$array["LDAP_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{GROUP_ATTRIBUTE}:</td>
		<td>". Field_text("GROUP_ATTRIBUTE-$tt",$array["GROUP_ATTRIBUTE"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_filter}:</td>
		<td>". Field_text("LDAP_FILTER-$tt",$array["LDAP_FILTER"],"font-size:14px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{group}:</td>
		<td>". Field_text("LDAPGROUP-$tt",$array["LDAPGROUP"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{browse}","Loadjs('BrowseActiveDirectoryGeneric.php?field-user=LDAPGROUP-$tt&t=$tt&OnlyGroups=yes')",12)."</td>
	<tr>
		<td colspan=2 align='right'>
				<hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='left'>
				<hr>". button("{members}","Loadjs('freeweb.webdavfree.members.php?mdkey=$mdkey')","16px")."</td>
	</tr>	
	</table>
	<script>
		var x_Save$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
			document.getElementById('$tt').innerHTML='';
			$('#$t').flexReload();
		}


		function Save$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('connectiontype','$cnxt');
			XHR.appendData('mdkey', '$mdkey');
			XHR.appendData('servername', '{$_POST["servername"]}');
		
			XHR.appendData('LDAP_SERVER', document.getElementById('LDAP_SERVER-$tt').value);
			XHR.appendData('LDAP_PORT', document.getElementById('LDAP_PORT-$tt').value);
			XHR.appendData('LDAP_SUFFIX', document.getElementById('LDAP_SUFFIX-$tt').value);
			XHR.appendData('LDAP_DN', document.getElementById('LDAP_DN-$tt').value);
			XHR.appendData('ADGROUP', encodeURIComponent(document.getElementById('LDAPGROUP-$tt').value));
			XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$tt').value));
			XHR.appendData('GROUP_ATTRIBUTE', encodeURIComponent(document.getElementById('GROUP_ATTRIBUTE-$tt').value));
			XHR.appendData('LDAP_FILTER', encodeURIComponent(document.getElementById('LDAP_FILTER-$tt').value));
			
			AnimateDiv('$tt');
			XHR.sendAndLoad('$page', 'POST',x_Save$tt);
		}


</script>";

echo $tpl->_ENGINE_parse_body($html);

}	
function page(){
	
	
$tpl=new templates();
$page=CurrentPageName();
$alias=$tpl->_ENGINE_parse_body("{alias}");
$directory=$tpl->_ENGINE_parse_body("{directory}");
$description=$tpl->_ENGINE_parse_body("{description}");
$new_alias=$tpl->_ENGINE_parse_body("{new_folder}");
$webdavfolders=$tpl->_ENGINE_parse_body("{webdav_folders}");
$t=time();

	
	$buttons="
	buttons : [
	{name: '<b>$new_alias</b>', bclass: 'Add', onpress : AddNewAlias$t},
	
		],";	

$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?freeweb-aliases-list=yes&servername={$_GET["servername"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$alias', name : 'alias', width : 341, sortable : false, align: 'left'},	
		{display: '$directory', name : 'directory', width :427, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'auth', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : true, align: 'center'},
		
		],
	$buttons
	searchitems : [
		{display: '$alias', name : 'alias'},
		{display: '$directory', name : 'directory'},
		],
	sortname: 'alias',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:16px>$webdavfolders</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 900,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function AddNewAlias$t(){
		YahooWin6('600','$page?new-alias=yes&servername={$_GET["servername"]}&t=$t','$new_alias');
	}
	
		var x_FreeWebAddAlias$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}	
			$('#row'+mem$t).remove();
		}		
	
	function FreeWebDelAlias$t(id){
		mem$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('DelAlias',id);
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddAlias$t);		
	}


</script>

";	
	
//$('#flexRT$t').flexReload();
	echo $html;	
	

	
}	

function alias_popup(){
$page=CurrentPageName();
$tpl=new templates();
$free=new freeweb($_GET["servername"]);
$t=$_GET["t"];
$users=new usersMenus();

$html="

	<div id='alias-animate-$t'></div>
	
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{share_name}:</td>
		<td>". Field_text("alias_freeweb-$t",null,"font-size:16px;padding:3px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("alias_dir-$t",null,"font-size:16px;padding:3px;width:320px",null,null,null,false,"FreeWebAddAliasCheck$t(event)").
		"&nbsp;<input type='button' OnClick=\"javascript:Loadjs('browse-disk.php?field=alias_dir-$t&replace-start-root=1');\" style='font-size:16px' value='{browse}...'></td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{add} {folder}","FreeWebAddAlias$t()","18px")."</td>
	</tr>
	</table>
	
	<script>
		var x_FreeWebAddAlias$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			document.getElementById('alias-animate-$t').innerHTML='';
			$('#flexRT$t').flexReload();
			YahooWin6Hide();
		}

		function FreeWebAddAliasCheck$t(e){
			if(checkEnter(e)){FreeWebAddAlias$t();}
		
		}
		

		function FreeWebAddAlias$t(){
			var XHR = new XHRConnection();
			var Alias=encodeURIComponent(document.getElementById('alias_freeweb-$t').value);
			if(Alias.length<2){return;}
			var directory=encodeURIComponent(document.getElementById('alias_dir-$t').value);
			if(directory.length<2){return;}		
			XHR.appendData('Alias',encodeURIComponent(document.getElementById('alias_freeweb-$t').value));
			XHR.appendData('directory',directory);
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('alias-animate-$t');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddAlias$t);			
		}
	</script>	
	
	
	";	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function alias_save(){
	
	$mdkey=md5(serialize($_POST));
	$_POST["Alias"]=url_decode_special_tool($_POST["Alias"]);
	$_POST["directory"]=url_decode_special_tool($_POST["directory"]);
	$_POST["Alias"]=str_replace(" ", "-", $_POST["Alias"]);
	$sql="INSERT INTO freeweb_webdav (mdkey,alias,directory,servername) 
	VALUES('$mdkey','{$_POST["Alias"]}','{$_POST["directory"]}','{$_POST["servername"]}')";
	$q=new mysql();
	if(!$q->TABLE_EXISTS("freeweb_webdav", "artica_backup")){
		$q->BuildTables();
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");
	
}

function alias_del(){
	if(!is_numeric($_POST["DelAlias"])){return;}
	$sql="DELETE FROM freeweb_webdav WHERE mdkey='{$_POST["DelAlias"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");	
}


function alias_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$search='%';
	$table="freeweb_webdav";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" servername='{$_GET["servername"]}'";
	
	if($q->COUNT_ROWS("freeweb_webdav",'artica_backup')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){json_error_show("No data...");}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgsimple("delete-24.png","{delete}","FreeWebDelAlias$t('{$ligne["mdkey"]}')");
		$auth=imgsimple("members-priv-32.png",null,"Loadjs('$MyPage?auth-js=yes&mdkey={$ligne["mdkey"]}&servername={$_GET["servername"]}')");
	$data['rows'][] = array(
		'id' => "{$ligne["mdkey"]}",
		'cell' => array(
			"<span style='font-size:16px;'>{$ligne["alias"]}</a></span>",
			"<span style='font-size:16px;'>{$ligne["directory"]}</a></span>",$auth,$delete
			)
		);
	}
	
	
echo json_encode($data);		

}
