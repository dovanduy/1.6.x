<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.pdns.inc');
	
	

	
	if(!CheckSambaRights()){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items-list"])){items();exit;}
	if(isset($_POST["pattern"])){save_files();exit;}
	if(isset($_POST["del-ID"])){delete_files();exit;}
	js();
	
function js(){
	$tpl=new templates();
	$simple_share=$tpl->_ENGINE_parse_body("{banned_files}");
	$page=CurrentPageName();
	$html="YahooWin2('535','$page?popup=yes&path={$_GET["path"]}','$simple_share');";
	
	echo $html;
	
	
}

function popup(){
	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=519;
	$path=base64_decode($_GET["path"]);
	$md5path=md5($path);
	$veto_files_explain=$tpl->_ENGINE_parse_body("{veto_files_explain}");
	$veto_files_add_explain=$tpl->javascript_parse_text("{veto_files_add_explain}");
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ask_delete_rule=$tpl->javascript_parse_text("{ask_delete_rule}");
	$help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	
	$html="
	<div class=text-info style='font-size:14px'>$veto_files_explain</div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-list=yes&t=$t&md5path=$md5path',
	dataType: 'json',
	colModel : [	
		{display: '$files', name : 'files', width :443, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$files', name : 'files'},
	],
	sortname: 'files',
	sortorder: 'asc',
	usepager: true,
	title: '$path',
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
	s_PopUpFull('http://nas-appliance.org/index.php?cID=199','1024','900');
}




var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}



function NewGItem$t(){
	var pattern=prompt('$veto_files_add_explain');
	if(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',pattern);
		XHR.appendData('md5path','$md5path');
		XHR.sendAndLoad('$page', 'POST', x_NewGItem$t);
	}
	
}

var x_DeleteAttribute$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}

function DeleteAttribute$t(ID){
		mem$t=ID;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-ID',ID);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteAttribute$t);		
	}

</script>";
	
	echo $html;
	
//openldap_proxy	
}

function save_files(){
	$md5path=$_POST["md5path"];
	$pattern=$_POST["pattern"].",";
	$PArray=explode(",",$pattern);
	while (list ($index, $ext) = each ($PArray) ){
		$ext=trim($ext);
		if($ext==null){continue;}
		if(isset($arlredy[$ext])){continue;}
		$ext=addslashes($ext);
		$f[]="('$md5path','$ext')";
		$arlredy[$ext]=true;
		
	}
	if(count($f)>0){
		$sql="INSERT INTO samba_veto_files (`md5path`,`files`) VALUES ".@implode(",", $f);
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	}
	
}

function delete_files(){
	$ID=$_POST["del-ID"];
	$sql="DELETE FROM samba_veto_files WHERE ID='$ID'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	


}

function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$sock=new sockets();		
	
	$search='%';
	$table="samba_veto_files";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND md5path='{$_GET["md5path"]}'";

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
		$ID=$ligne["ID"];
		$color="black";
		$delete=imgsimple("delete-24.png","","DeleteAttribute$t('$ID')");
		
	
	$data['rows'][] = array(
		'id' => "$ID",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["files"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}
