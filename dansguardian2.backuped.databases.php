<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["delete-item"])){delete_item();exit;}
if(isset($_GET["params"])){params();exit;}
if(isset($_POST["SquidDBBackupCatzMaxDay"])){params_save();exit;}
if(isset($_GET["f"])){downloadfile();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	

	$title=$tpl->_ENGINE_parse_body("{backup_containers}");
	
	if($_GET["taskid"]>0){
		$q=new mysql_squid_builder();
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT TimeDescription FROM webfilters_schedules WHERE ID={$_GET["taskid"]}","artica_events"));	
		$title="{$_GET["taskid"]}::".$ligne2["TimeDescription"];
	}	
	
	$html="YahooWin5('650','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("{backup_containers}");
	$size=$tpl->_ENGINE_parse_body("{filesize}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$t=time();
	$html="
	<div style='margin-left:5px'>
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	</div>
<script>
var memid$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 148, sortable : true, align: 'left'},
		{display: '$filename', name : 'filepath', width : 312, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 70, sortable : true, align: 'left'},
		{display: '$delete', name : 'size', width : 31, sortable : false, align: 'center'},
	],
buttons : [
	{name: '$parameters', bclass: 'Reconf', onpress : backupedItemsParameters},
	
		],		
	
	searchitems : [
		{display: '$filename', name : 'filepath'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: 629,
	height: 350,
	singleSelect: true
	
	});   
});

	var xBackupedItemsDelete= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
	    	$('#row'+memid$t).remove();
	    	RefreshArticaDBStatus();
		}	

	function BackupedItemsDelete(filename,fullencodedpath,id){
		memid$t=id;
		if(confirm('$delete '+filename+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-item',fullencodedpath);
			XHR.sendAndLoad('$page', 'POST',xBackupedItemsDelete);		
		
		}
	}
	
	function backupedItemsParameters(){
		RTMMail('550','$page?params=yes&t=$t','$parameters');
	
	}

</script>

";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function search(){
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="webfilters_backupeddbs";
	$search='%';
	$page=1;
	$WHERE="1";
	
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE $WHERE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $WHERE $ADD2";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE $WHERE $searchstring $ORDER $limitSql";
	
	$line=$tpl->_ENGINE_parse_body("{line}");
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["filepath"]);
		$fullpath=base64_encode($ligne["filepath"]);
		$ligne["filepath"]=basename($ligne["filepath"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$delete=imgsimple("delete-24.png",null,"BackupedItemsDelete('{$ligne["filepath"]}','$fullpath','$id')");
		
		$link="<a href=\"$Mypage?f={$ligne["filepath"]}\" style='font-size:14px;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:14px'>{$ligne["zDate"]}</span>",
		"<span style='font-size:14px'>$link{$ligne["filepath"]}</a></span>",
		"<span style='font-size:14px'>{$ligne["size"]}</span>",
		$delete
		)
		);
	}
	
	
echo json_encode($data);	
	
}

function delete_item(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?delete-backuped-category-container={$_POST["delete-item"]}");
	
	
}

function params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidDBBackupCatzMaxDay=$sock->GET_INFO("SquidDBBackupCatzMaxDay");
	if(!is_numeric($SquidDBBackupCatzMaxDay)){$SquidDBBackupCatzMaxDay=15;}
	$t=$_GET["t"];
	
	$html="
	<div id='div-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{MaxDays}:</td>
		<td>". Field_text("SquidDBBackupCatzMaxDay",$SquidDBBackupCatzMaxDay,"font-size:16px;width:90px",null,null,null,false,"SaveBckParmzchk(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveBckParmz()",18)."</td>
	</tr>
	</table>
	
	<script>
	
	function SaveBckParmzchk(e){
		if(checkEnter(e)){SaveBckParmz();}
	}
	
	var x_DisableSquidDefaultScheduleCheck=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#table-$t').flexReload();
		RTMMailHide();	
		RefreshArticaDBStatus();	
	}			
	
	function SaveBckParmz(){
		AnimateDiv('div-$t');
		var XHR = new XHRConnection();
		XHR.appendData('SquidDBBackupCatzMaxDay',document.getElementById(SquidDBBackupCatzMaxDay).value);
	  	XHR.sendAndLoad('$page', 'POST',x_SaveBckParmz);
	  }
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function params_save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	$sock->getFrameWork("squid.php?delete-backuped-category-container=yes");
	
}


function downloadfile(){
header('Content-type: application/x-tar');
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=\"{$_GET["f"]}\"");	
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
$fsize = filesize("/home/squid/categories_backuped/storage/{$_GET["f"]}"); 
header("Content-Length: ".$fsize); 
ob_clean();
flush();
readfile("/home/squid/categories_backuped/storage/{$_GET["f"]}");
	
}