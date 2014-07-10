<?php
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search-store"])){search_store();exit;}
	if(isset($_GET["download-js"])){download_js();exit;}
	if(isset($_GET["download"])){download();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["remove"])){remove();exit;}
	if(isset($_POST["REFRESH-STORE"])){refresh();exit;}
	tableau();

function download_js(){
	header("content-type: application/x-javascript");
	$filepath=$_GET["download-js"];
	$page=CurrentPageName();
	$filepathenc=urlencode($filepath);
	$sock=new sockets();
	$data=trim($sock->getFrameWork("system.php?copytocache=$filepathenc"));
	if(strlen($data)>3){
		echo "alert('$data')";
		return;
	}
	echo "window.location.href = '$page?download=$filepathenc';";
	//echo "s_PopUp('$page?download=$filepathenc',1,1,'');";
}	
function delete_js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$filepath=$_GET["delete-js"];
	$basename=basename($filepath);
	$page=CurrentPageName();
	$filepathenc=urlencode($filepath);
	$t=time();
	$delete=$tpl->javascript_parse_text("{delete}");
	echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	SystemStoreDiskListLogs();
}
	
function save$t(){
	if(!confirm('$delete $basename ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove','$filepath');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}				
save$t()";

}
function refresh(){
	$sock=new sockets();
	$sock->getFrameWork("system.php?refresh-logs-storefiles=yes");	
	
}

function remove(){
	$filepath=$_POST["remove"];
	$filepathenc=urlencode($filepath);
	$sock=new sockets();
	$sock->getFrameWork("system.php?remove-logs-file=$filepathenc");
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM sysstorestatus WHERE `filepath`='$filepath'","artica_events");
	
}

function download(){
	
	$file=basename($_GET["download"]);
	$path="/usr/share/artica-postfix/ressources/logs/$file";
	
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".urlencode(base64_encode($path))));
	header('Content-type: '.$content_type);
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	@unlink($path);
}	
function tableau(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$sizeT=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$zdate=$tpl->javascript_parse_text("{date}");
	$action=$tpl->javascript_parse_text("{action}");
	
	$q=new mysql();
	$files=$q->COUNT_ROWS("sysstorestatus","artica_events");

	$sql="SELECT SUM(filesize) as tsize FROM sysstorestatus";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$size=$ligne["tsize"];
	$refresh=$tpl->javascript_parse_text("{refresh}");
	$title=$tpl->_ENGINE_parse_body("{storage} {files}:".FormatNumberX($files,0)." (".FormatBytes($size).")");
	$t=time();
	
	$buttons="buttons : [
	{name: '$refresh', bclass: 'Reload', onpress : Refresh$t},
	{separator: true},
	
	],	";
	
	$html="
	
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	<script>
	var rowSquidTask='';
	$(document).ready(function(){
	$('#$t').flexigrid({
	url: '$page?search-store=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'zDate', width : 197, sortable : true, align: 'left'},
	{display: '$filename', name : 'filepath', width : 537, sortable : true, align: 'left'},
	{display: '$sizeT', name : 'filesize', width : 95, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 50, sortable : false, align: 'center'}
	],$buttons

	searchitems : [
	{display: '$filename', name : 'filepath'},
	
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
var x_EmptyStorage=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#$t').flexReload();
}	
	
function Refresh$t(){
	var XHR = new XHRConnection();
	XHR.appendData('REFRESH-STORE','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyStorage);
	
}
	
function SystemStoreDiskListLogs(){
	$('#$t').flexReload();
}
	
var x_RotateTaskEnable=function (obj) {
	var ID='{$_GET["ID"]}';
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}
	

	
	
	
function DisableSquidDefaultScheduleCheck(){
	var XHR = new XHRConnection();
	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);
}
	
var x_StorageTaskDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+rowSquidTask).remove();
}
	
function StorageTaskDelete$t(ID,md5){
	rowSquidTask=md5;
	if(!confirm('Remove source logs '+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('storage-delete',ID);
	XHR.sendAndLoad('$page', 'POST',x_StorageTaskDelete$t);
}
</script>";
	
	echo $html;
	
		
	
}
function search_store(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="sysstorestatus";
	$database="artica_events";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table,"artica_events")){
		json_error_show("No data...");
	}


	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){json_error_show("No data...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,$database);

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
	if(!$q->ok){json_error_show($q->mysql_error,1); }




	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("RotateTask{$ligne['filename']}");
		$span="<span style='font-size:16px'>";
		
		$encodedPath=urlencode($ligne["filepath"]);
		$delete=imgsimple("delete-32.png","{delete} {$ligne['ID']}","Loadjs('$MyPage?delete-js=$encodedPath')");
		$download=imgsimple("32-download.png","{delete} {$ligne['ID']}","Loadjs('$MyPage?download-js=$encodedPath')");
		
		$ligne["filesize"]=FormatBytes($ligne["filesize"]);
		

		

		$xtime=strtotime("{$ligne['zDate']}");
		$dateTex=date("Y {l} {F} d",$xtime);
		if($tpl->language=="fr"){$dateTex=date("{l} d {F} Y",$xtime);}
		$dateTex=$tpl->_ENGINE_parse_body("$dateTex");
		$filepath=basename($ligne["filepath"]);

		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array("$span$dateTex</div>",
				"$span$filepath</a></span>",
				"$span{$ligne["filesize"]}</a></span>",$download,
				$delete )
		);
	}


	echo json_encode($data);

}
function FormatNumberX($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
?>