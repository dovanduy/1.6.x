<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup-js"])){js();exit;}
	if(isset($_GET["popup"])){alias_start();exit;}
	if(isset($_GET["nginx-aliases-list"])){alias_list();exit;}
	if(isset($_POST["Alias"])){alias_save();exit;}
	if(isset($_POST["DelAlias"])){alias_del();exit;}
	if(isset($_POST["AddAlias"])){alias_add();exit;}
	if(isset($_GET["aliases-list"])){aliases_list();exit;}
	if(isset($_POST["servername_pattern"])){servername_pattern();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_GET["new-js"])){new_js();exit;}
	alias_list();	
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$server=$_GET["servername"];
	$title=$tpl->_ENGINE_parse_body("$server:: {aliases}");
	$server=urlencode($server);
	echo "YahooWin3('850','$page?popup=yes&servername=$server','$server::$title')";
	
	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$t=time();
	$delete=$tpl->javascript_parse_text("{delete}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT alias FROM `nginx_aliases` WHERE ID='$ID'"));
	$alias=$ligne["alias"];
	$html="
var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}	
	$('#NGINX_MAIN_ALIASES').flexReload();
	$('#NGINX_MAIN_TABLE').flexReload();
}	

function Function$t(){
	if(! confirm('$delete $alias') ){ return; }
	var XHR = new XHRConnection();
	XHR.appendData('DelAlias',$ID);
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',xFunction$t);			
}	

Function$t();
";
	echo $html;
}

function new_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$txt=$tpl->javascript_parse_text("{add_serveralias_ask}");
	$t=time();
	
$html="
var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#NGINX_MAIN_ALIASES').flexReload();
	$('#NGINX_MAIN_TABLE').flexReload();
	
}
	
function Function$t(){
	var alias=prompt('$txt');
	if(alias){
		var XHR = new XHRConnection();
		XHR.appendData('AddAlias',alias);
		XHR.appendData('servername','{$_GET["servername"]}');
		XHR.sendAndLoad('$page', 'POST',xFunction$t);
	}
}

Function$t();
";
	echo $html;	
	
}


function alias_start(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<div id='nginx-aliasesserver-list' style='width:100%;heigth:350px;overflow:auto'></div>
	<script>
		function FreeWebAliasList(){
			LoadAjax('nginx-aliasesserver-list','$page?nginx-aliases-list=yes&servername={$_GET["servername"]}');
		}
	FreeWebAliasList();
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}
	
	


function alias_del(){
	$ID=$_POST["DelAlias"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_aliases WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function alias_add(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE nginx_aliases (`alias`,`servername`) VALUES ('{$_POST["AddAlias"]}','{$_POST["servername"]}')");
	if(!$q->ok){echo $q->mysql_error;}
}
//{freeweb_aliasserver_explain}

function alias_list(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$alias=$tpl->_ENGINE_parse_body("{aliases}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
	
	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$about_text=$tpl->javascript_parse_text("{freeweb_aliasserver_explain}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$title=$tpl->javascript_parse_text("{$_GET["servername"]}: {server_aliases_title}");
	$servernameenc=urlencode($_GET["servername"]);
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername_pattern FROM `reverse_www` WHERE servername='{$_GET["servername"]}'"));
	
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px >$new_alias</strong>', bclass: 'add', onpress : NewAliase$t},
	{name: '<strong style=font-size:16px >$apply</strong>', bclass: 'apply', onpress : Apply$t},
	],";

	$explain=$tpl->_ENGINE_parse_body("{postfix_transport_senders_explain}");
	$html="
<div class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{replace_server_directive}:</td>
	<td>". Field_text("servername_pattern-$t",$ligne["servername_pattern"],"font-size:18px;padding:5px;font-weight:bold;width:90%")."</td>
</tr>
<tr>
	<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
</tr>
</table>			
</div>
<p>&nbsp;</p>
<table class='NGINX_MAIN_ALIASES' style='display: none' id='NGINX_MAIN_ALIASES' style='width:100%'></table>
<script>
$(document).ready(function(){
	$('#NGINX_MAIN_ALIASES').flexigrid({
	url: '$page?aliases-list=yes&t=$t&servername=$servernameenc',
	dataType: 'json',
	colModel : [
		{display: '$aliases', name : 'alias', width : 715, sortable : true, align: 'left'},
		{display: '$delete;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
		{display: '$aliases', name : 'alias'},
	],
	sortname: 'alias',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function About$t(){
	alert('$about_text');
}

function Apply$t(){
	Loadjs('nginx.single.progress.php?servername=$servernameenc');
}

function NewAliase$t(){
	Loadjs('$page?new-js=yes&servername=$servernameenc');
}

var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('servername',encodeURIComponent('{$_GET["servername"]}'));
	XHR.appendData('servername_pattern',encodeURIComponent(document.getElementById('servername_pattern-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";

echo $tpl->_ENGINE_parse_body($html);


}

function  servername_pattern(){
	$servername_pattern=url_decode_special_tool($_POST["servername_pattern"]);
	$servername=url_decode_special_tool($_POST["servername"]);
	$q=new mysql_squid_builder();
	$servername_pattern=mysql_escape_string2($servername_pattern);
	$servername=mysql_escape_string2($servername);
	
	if(!$q->FIELD_EXISTS("reverse_www", "servername_pattern")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `servername_pattern` CHAR(255) NULL");if(!$q->ok){echo $q->mysql_error();return;}
	}
	
	$q->QUERY_SQL("UPDATE reverse_www SET `servername_pattern`='{$servername_pattern}' WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function aliases_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();	
	$table="nginx_aliases";
	$q=new mysql_squid_builder();
	$FORCE="servername='{$_GET["servername"]}'";
	$t=$_GET["t"];
	if($_POST["query"]<>null){$search=str_replace("*", ".*?", $_POST["query"]);}
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	
	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$CurrentPage=CurrentPageName();
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();
	
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();
	
	$fontsize=22;
	
	$span="<span style='font-size:{$fontsize}px'>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ID=$ligne["ID"];
		$alias=$ligne["alias"];
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-js=yes&ID=$ID')");

		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"$span$alias",
						$delete
				)
		);		
		
	}	
	echo json_encode($data);
	
}


