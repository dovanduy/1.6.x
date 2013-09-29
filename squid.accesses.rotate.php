<?php
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.syslogs.inc');

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsWebStatisticsAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["log-js"])){storage_view_js();exit;}
if(isset($_GET["storage-view-search"])){storage_view_search();exit;}
if(isset($_GET["storage-popup"])){storage_view_popup();exit;}
if(isset($_POST["extract-file"])){storage_view_extract();exit;}
if(isset($_POST["storage-delete"])){storage_delete();exit;}
if(isset($_POST["delete-extracted"])){storage_view_delete();exit;}



if(isset($_GET["popup"])){tableau();exit;}
if(isset($_GET["search-store"])){search_store();exit;}

js();

function storage_view_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$filename=$_GET["filename"];
	$html="YahooWin5('1060','$page?storage-popup=yes&storeid={$_GET["storeid"]}&filename=$filename','$filename')";
	echo $html;
}

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{source_logs}");
	header("content-type: application/x-javascript");
	$html="YahooWin2('850','$page?popup=yes','$title');";
	echo $html;
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
	
	$q=new mysql_storelogs();
	$files=$q->COUNT_ROWS("accesslogs");
	$size=$q->TABLE_SIZE("access_store");
	$title=$tpl->_ENGINE_parse_body("MySQL: {storage} {files}:".FormatNumberX($files,0)." (".FormatBytes($size/1024).")");
	$t=time();
	$html="
	
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	<script>
	var rowSquidTask='';
	$(document).ready(function(){
	$('#$t').flexigrid({
	url: '$page?search-store=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'filetime', width : 197, sortable : true, align: 'left'},
	{display: '$filename', name : 'filename', width : 378, sortable : true, align: 'left'},
	{display: '$sizeT', name : 'filesize', width : 95, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],

	searchitems : [
	{display: '$filename', name : 'filename'},
	{display: '$task', name : 'taskid'},
	],
	sortname: 'filetime',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 835,
	height: 400,
	singleSelect: true
	
	});
	});
	
	
	
function EmptyStorage(){
	if(confirm('$askdelete')){
		var XHR = new XHRConnection();
		XHR.appendData('DELETE-STORE','yes');
		XHR.sendAndLoad('logrotate.php', 'POST',x_EmptyStorage);
	}
}
	
function SquidCrontaskUpdateTable(){
	$('#$t').flexReload();
}
	
var x_RotateTaskEnable=function (obj) {
	var ID='{$_GET["ID"]}';
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}
	
var x_EmptyStorage=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#$t').flexReload();
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

function storage_delete(){
	$q=new mysql_storelogs();
	$sock=new sockets();
	$sql="DELETE FROM accesslogs WHERE storeid='{$_POST["storage-delete"]}'";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}
	$sql="DELETE FROM access_store WHERE ID='{$_POST["storage-delete"]}'";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}
}

function search_store(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_storelogs();
	$search='%';
	$table="accesslogs";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	

	$total=0;
	if($q->COUNT_ROWS($table)==0){json_error_show("No data...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	
	
	

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT `filename`,`storeid`,`filesize`,`filetime` FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}

	


	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("RotateTask{$ligne['filename']}");
		$span="<span style='font-size:16px'>";
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","StorageTaskDelete$t('{$ligne['ID']}','$md5')");

		
		
		
		$jslloop="Loadjs('$MyPage?log-js=yes&storeid={$ligne['storeid']}&t=$t&filename={$ligne["filename"]}');";
		
		
		$view="<a href=\"javascript:blur();\" OnClick=\"javascript:$jslloop\"
		style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";

		$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
		if($ligne['taskid']==0){$jstask=null;}

		$action=null;
		$action=imgsimple("service-restart-32.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}')");

		$xtime=strtotime("{$ligne['filetime']}");
		$dateTex=date("Y {l} {F} d",$xtime);
		if($tpl->language=="fr"){$dateTex=date("{l} d {F} Y",$xtime);}
		$dateTex=$tpl->_ENGINE_parse_body("$dateTex");

		
		$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("$span{$ligne['filetime']}</a></span><div style='font-size:11px'><i>$dateTex</i></div>",
		"$span$view{$ligne["filename"]}</a></span>",
		"$span{$ligne["filesize"]}</a></span>",
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
function storage_view_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$extract=$tpl->_ENGINE_parse_body("{extract}");
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$target=$tpl->_ENGINE_parse_body("{target}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$askdelete=$tpl->javascript_parse_text("{delete} {$_GET["filename"]}.log ?");

	$t=time();
	$html="
	<div style='margin-left:-5px'>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	</div>
	<script>
	var rowSquidTask='';
	$(document).ready(function(){
	$('#$t').flexigrid({
	url: '$page?storage-view-search=yes&filename={$_GET["filename"]}&t=$t&storeid={$_GET["storeid"]}',
	dataType: 'json',
	colModel : [
	{display: '$rows', name : 'rows', width : 1018, sortable : true, align: 'left'},

	],
	buttons : [
	{name: '$extract', bclass: 'add', onpress : ExtractFile$t},
	{name: '$delete', bclass: 'Delz', onpress : DeleteExtractedFile$t},

	],
	searchitems : [
	{display: '$rows', name : 'rows'},
	],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	rpOptions: [10, 20, 30, 50,100,200,500,1500],
	showTableToggleBtn: false,
	width: 1050,
	height: 400,
	singleSelect: true

});
});


function DeleteExtractedFile$t(){
	if(confirm('$askdelete')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-extracted','{$_GET["filename"]}');
		XHR.sendAndLoad('$page', 'POST',xExtractFile$t);
	}
}

function SquidCrontaskUpdateTable(){
	$('#$t').flexReload();
}

var x_RotateTaskEnable=function (obj) {
	var ID='{$_GET["ID"]}';
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}

var xExtractFile$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#$t').flexReload();
}


function ExtractFile$t(){
	if(confirm('$extract {$_GET["filename"]} ?')){
		var XHR = new XHRConnection();
		XHR.appendData('extract-file','{$_GET["filename"]}');
		XHR.appendData('storeid','{$_GET["storeid"]}');
		XHR.sendAndLoad('$page', 'POST',xExtractFile$t);
		}
	}

function DisableSquidDefaultScheduleCheck(){
var XHR = new XHRConnection();
if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
else{XHR.appendData('DisableSquidDefaultSchedule',0);}
XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);
}




var x_RotateTaskDelete=function (obj) {
var results=obj.responseText;
if(results.length>0){alert(results);return;}
$('#rowRotateTask'+rowSquidTask).remove();
}

function RotateTaskDelete(ID){
rowSquidTask=ID;
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
XHR.appendData('rotate-delete','yes');
XHR.sendAndLoad('$page', 'POST',x_RotateTaskDelete);
}



</script>";

echo $html;

}
function storage_view_delete(){
	$mydir=dirname(__FILE__);
	$newtFile=$_POST["delete-extracted"];
	@unlink("$mydir/ressources/logs/$newtFile.log");
}
function storage_view_extract(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	@chmod("ressources/logs",0777);
	$q=new mysql_storelogs();
	$mydir=dirname(__FILE__);
	$newtFile=$_POST["extract-file"];
	$sock=new sockets();
	@unlink("$mydir/ressources/logs/$newtFile");


	$q=new mysql_storelogs();
	$sql="SELECT filecontent INTO DUMPFILE '$mydir/ressources/logs/$newtFile' FROM access_store WHERE ID = '{$_POST["storeid"]}'";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);

	

	$ext=file_extension($newtFile);
	writelogs("$mydir/ressources/logs/$newtFile -> ".@filesize("$mydir/ressources/logs/$newtFile")." bytes...",__FUNCTION__,__FILE__,__LINE__);
	$cmdline="cp -f $mydir/ressources/logs/$newtFile $mydir/ressources/logs/$newtFile.log";



	if($ext=="bz2"){
		$cmdline="bzip2 -d \"$mydir/ressources/logs/$newtFile\" -c >\"$mydir/ressources/logs/$newtFile.log\" 2>&1";
		exec($cmdline,$results);
	}
	if($ext=="gz"){
		$cmdline="gunzip -d \"$mydir/ressources/logs/$newtFile\" -c >\"$mydir/ressources/logs/$newtFile.log\"";
	}
	if($cmdline<>null){
		writelogs("$cmdline",__FUNCTION__,__FILE__,__LINE__);
		exec($cmdline,$results);
		while (list ($key, $line) = each ($results) ){
			writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
		}
	}

	@unlink("$mydir/ressources/logs/$newtFile");
	writelogs(@filesize("$mydir/ressources/logs/$newtFile.log")." bytes...",__FUNCTION__,__FILE__,__LINE__);

}
function file_extension($filename){return pathinfo($filename, PATHINFO_EXTENSION);}
function storage_view_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$filename="ressources/logs/{$_GET["filename"]}.log";
	if(!is_file($filename)){
		json_error_show("You need to extract {$_GET["filename"]}.log first");
		return;
	}


		$rp = $_POST['rp'];
		$search=$_POST["query"];
		if($search==null){$cmdline="tail -n $rp $filename 2>&1";}
		if($search<>null){
			$search=str_replace(".", "\.", $search);
			$search=str_replace("*", ".*", $search);
			$search=str_replace("[", "\[", $search);
			$search=str_replace("]", "\]", $search);
			$search=str_replace("(", "\(", $search);
			$search=str_replace(")", "\)", $search);
			$cmdline="grep -E \"$search\" $filename|tail -n $rp 2>&1";
		}

		exec($cmdline,$datas);

		$data = array();
		$data['page'] = 1;
		$data['total'] = count($datas);
		$data['rows'] = array();
		$c=0;

		if($_POST["sortorder"]=="desc"){krsort($datas);}

		while (list ($key, $line) = each ($datas) ){
			if(trim($line)==null){continue;}
			$c++;
			if(preg_match("#FATAL#i", $line)){$line="<span style='color:#680000;font-size:11px'>$line</line>";}
			if(preg_match("#abnormally#i", $line)){$line="<span style='color:#680000;font-size:11px'>$line</line>";}
			if(preg_match("#Reconfiguring#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}
			if(preg_match("#Accepting HTTP#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}
			if(preg_match("#Ready to serve requests#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}

			$data['rows'][] = array(
					'id' => md5($line),
					'cell' => array("<span style='font-size:11px'>$line</span>")
			);

		}
		$data['total'] = $c;
		echo json_encode($data);
}