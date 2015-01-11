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
	
	page();	
	
	
	
function page(){
	
	
$tpl=new templates();
$page=CurrentPageName();
$alias=$tpl->_ENGINE_parse_body("{alias}");
$directory=$tpl->_ENGINE_parse_body("{directory}");
$description=$tpl->_ENGINE_parse_body("{description}");
$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
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
		{display: '$alias', name : 'alias', width : 386, sortable : false, align: 'left'},	
		{display: '$directory', name : 'directory', width :427, sortable : false, align: 'left'},
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
	title: '',
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
	
	return;
	
	
	
	
	
	
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$free=new freeweb($_GET["servername"]);
	//if($free->groupware<>null){
		//echo $tpl->_ENGINE_parse_body("<div class=text-info>{freeweb_is_groupware_feature_disabled}</div>");
		//return;
	//}
	
	
	$free->CheckWorkingDirectory();
	$direnc=urlencode(base64_encode($free->WORKING_DIRECTORY));
	
	$html="
	<p>&nbsp;</p>
	<div id='freeweb-aliases-list' style='width:100%;heigth:350px;overflow:auto'></div>
	
	
	
	<script>
		var x_FreeWebAddAlias=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			FreeWebAliasList();	
		}			
		
		function FreeWebAddAliasCheck(e){
			if(checkEnter(e)){FreeWebAddAlias();}
		}
		
		function FreeWebAddAlias(){
			var XHR = new XHRConnection();
			var Alias=document.getElementById('alias_freeweb').value;
			if(Alias.length<2){return;}
			var directory=document.getElementById('alias_dir').value;
			if(directory.length<2){return;}			
			XHR.appendData('Alias',document.getElementById('alias_freeweb').value);
			XHR.appendData('directory',document.getElementById('alias_dir').value);
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('freeweb-aliases-list');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddAlias);			
		}
		
		function FreeWebDelAlias(id){
			var XHR = new XHRConnection();
			XHR.appendData('DelAlias',id);
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('freeweb-aliases-list');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebAddAlias);			
		}		
		
		function FreeWebAliasList(){
			LoadAjax('freeweb-aliases-list','$page?freeweb-aliases-list=yes&servername={$_GET["servername"]}');
		}
	FreeWebAliasList();
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function alias_popup(){
$page=CurrentPageName();
$tpl=new templates();
$free=new freeweb($_GET["servername"]);
$t=$_GET["t"];
$free->CheckWorkingDirectory();
$direnc=urlencode(base64_encode($free->WORKING_DIRECTORY));
$users=new usersMenus();
if($users->AsSystemAdministrator){
	$browseWebservers=browseWebserversdirs();
	$browseServer="
	<tr>
		<td class=legend style='font-size:16px'>{webserver}:</td>
		<td>". Field_array_Hash($browseWebservers, "FromWebServer-$t",$_GET["servername"],null,null,0,"font-size:16px")."</td>
	</tr>	
	
	";
}

$html="

	<div id='alias-animate-$t'></div>
	<div class=text-info style='font-size:14px'>{freeweb_alias_explain}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{alias}:</td>
		<td>". Field_text("alias_freeweb-$t",null,"font-size:16px;padding:3px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("alias_dir-$t",null,"font-size:16px;padding:3px;width:320px",null,null,null,false,"FreeWebAddAliasCheck$t(event)").
		"&nbsp;<input type='button' OnClick=\"javascript:Loadjs('browse-disk.php?start-root=$direnc&field=alias_dir-$t&replace-start-root=1');\" style='font-size:16px' value='{browse}...'></td>
	</tr>
	$browseServer	
	<tr>
		<td colspan=2 align='right'><hr>". button("{add} {alias}","FreeWebAddAlias$t()","18px")."</td>
	</tr>
	</table>
	
	<script>
		var x_FreeWebAddAlias$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			document.getElementById('alias-animate-$t').innerHTML='';
			$('#flexRT$t').flexReload();
		}

		function FreeWebAddAliasCheck$t(e){
			if(checkEnter(e)){FreeWebAddAlias$t();}
		
		}
		

		function FreeWebAddAlias$t(){
			var XHR = new XHRConnection();
			var Alias=document.getElementById('alias_freeweb-$t').value;
			if(Alias.length<2){return;}
			var directory=document.getElementById('alias_dir-$t').value;
			if(document.getElementById('FromWebServer-$t')){
				var directory2=document.getElementById('FromWebServer-$t').value;
				if(directory2.length>3){directory=directory2;}	
			}
			
			if(directory.length<2){return;}		

			
			
			XHR.appendData('Alias',document.getElementById('alias_freeweb-$t').value);
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
	
	if(substr($_POST["Alias"], 0,1)<>"/"){$_POST["Alias"]="/{$_POST["Alias"]}";}
	
	$sql="INSERT INTO freewebs_aliases (alias,directory,servername) VALUES('{$_POST["Alias"]}','{$_POST["directory"]}','{$_POST["servername"]}')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");
	
}

function alias_del(){
	if(!is_numeric($_POST["DelAlias"])){return;}
	$sql="DELETE FROM freewebs_aliases WHERE ID={$_POST["DelAlias"]} AND servername='{$_POST["servername"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-website=yes&servername={$_POST["servername"]}");	
}


function browseWebserversdirs(){
	$q=new mysql();
	$H[null]="{none}";
	$sql="SELECT servername FROM freeweb ORDER BY servername";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$free=new freeweb($ligne["servername"]);
		$free->CheckWorkingDirectory();
		$H[$free->WORKING_DIRECTORY]=$ligne["servername"];
	}
	return $H;
}


function alias_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$search='%';
	$table="freewebs_aliases";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" servername='{$_GET["servername"]}'";
	
	if($q->COUNT_ROWS("freewebs_aliases",'artica_backup')==0){json_error_show("No data");}
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
		$delete=imgsimple("delete-24.png","{delete}","FreeWebDelAlias$t('{$ligne["ID"]}')");
		
	$data['rows'][] = array(
		'id' => "{$ligne["ID"]}",
		'cell' => array(
			"<span style='font-size:16px;'>{$ligne["alias"]}</a></span>",
			"<span style='font-size:16px;'>{$ligne["directory"]}</a></span>",$delete
			)
		);
	}
	
	
echo json_encode($data);		

}
