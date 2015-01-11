<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.mimedefang.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_POST["delete-item"])){delete_item();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{items}::{mimedefang_filehosting}");
	echo "YahooWin2('750','$page?popup=yes','$title')";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=710;
	
	$q=new mysql_mimedefang_builder();
	$attachments_storage=$q->COUNT_ROWS("storage");
	$sql="SELECT SUM(filesize) as tcount FROM `storage`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size=FormatBytes($ligne["tcount"]/1024);
		
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$ask_delete_file=$tpl->javascript_parse_text("{ask_delete_file}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	
	
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$compile_rules', bclass: 'Reconf', onpress : MimeDefangCompileRules},
	{name: '$options', bclass: 'Settings', onpress : Options$t},
	{name: '$items', bclass: 'Db', onpress : ShowTable$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	$buttons=null;
	
	$explain=$tpl->_ENGINE_parse_body("{mimedefang_filehosting_items_explain}");
	$html="
	<div class=text-info style='font-size:14px'>$explain</div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'filetime', width :152, sortable : true, align: 'left'},	
		{display: '$filename', name : 'filename', width :379, sortable : true, align: 'left'},
		{display: '$filessize', name : 'filesize', width :77, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$filename', name : 'filename'},
		{display: '$to', name : 'mailto'},

	],
	sortname: 'filetime',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
}

function ShowTable$t(){
	Loadjs('mimedefang.filehosting.table.php');
}

var x_DeleteFileNameHosting$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#rowD'+mem$t).remove();
}
function DeleteFileNameHosting$t(filename,md5){
	if(confirm('$ask_delete_file')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('delete-item',filename);
      	XHR.appendData('filename',filename);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteFileNameHosting$t);		
	
	}

}

</script>";
	
	echo $html;
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_mimedefang_builder();
	
	$search='%';
	$table="storage";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5($ligne["filename"]);

	
	$delete=imgsimple("delete-24.png","","DeleteFileNameHosting$t('{$ligne["filename"]}','$zmd5')");
	
	$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
	
	
	$data['rows'][] = array(
		'id' => "D$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["filetime"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["filename"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["filesize"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}


function delete_item(){
	$q=new mysql_mimedefang_builder();
	$q->QUERY_SQL("DELETE FROM storage WHERE filename='{$_POST["filename"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}


