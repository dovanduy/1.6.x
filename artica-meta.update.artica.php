<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

	$users=new usersMenus();
	if(!$users->AsArticaMetaAdmin){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();
	}	
	
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["update-js"])){update_js();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_POST["update-uuid"])){update_save();exit;}
	
	js();
	
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{update}");
	$page=CurrentPageName();
	echo "YahooWin5('550','$page?table=yes&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}','$title')";
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$hostname=$q->uuid_to_host($_GET["uuid"]);
	$title=$tpl->javascript_parse_text("{delete} {$_GET["version"]} ?");
	$t=time();
	echo "
	var xStart$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	$('#ARTICA_META_UPDATEART_TABLE').flexReload();
	}
	
	
function Start$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','yes');
	XHR.appendData('filename','{$_GET["file"]}');
	XHR.appendData('filetype','{$_GET["KEY"]}');
	XHR.sendAndLoad('$page', 'POST',xStart$t);
}
	
	Start$t();";
	}
	
function delete(){
	$_GET["file"]=urlencode($_GET["file"]);
	$sock=new sockets();
	$sock->getFrameWork("artica.php?delete-artica-meta-package=yes&filename={$_POST["filename"]}&filetype={$_POST["filetype"]}");
	sleep(3);
}

function update_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$hostname=$q->uuid_to_host($_GET["uuid"]);
	$title=$tpl->javascript_parse_text("{host} \"$hostname\" >>> {$_GET["version"]} ?");
	
	if($_GET["gpid"]>0){
		$hostname=$q->gpid_to_name($_GET["gpid"]);
		$title=$tpl->javascript_parse_text("{group2} \"$hostname\" >>> {$_GET["version"]} ?");
	}
	
	$t=time();
	echo "
	var xStart$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		$('#ARTICA_META_MAIN_TABLE').flexReload();
		
	}				
	
	
function Start$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('update-uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	XHR.appendData('filename','{$_GET["file"]}');
	XHR.appendData('filetype','{$_GET["KEY"]}');
	XHR.sendAndLoad('$page', 'POST',xStart$t);		
	
	}
	
	Start$t();";
}

function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("metaorders")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("metaorders")){echo FATAL_ERROR_SHOW_128("Unable to stat metaorders table!");die();}
	
	
	$hostname=$q->uuid_to_host($_GET["uuid"]);
	
	if($_GET["gpid"]>0){
		$hostname=$q->gpid_to_name($_GET["gpid"]);
	}
	
	$disks=$tpl->javascript_parse_text("{disks}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$upload=$tpl->javascript_parse_text("{upload}");
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";

	
	$buttons="	buttons : [
	{name: 'Releases', bclass: 'Search', onpress : Releases$t},
	{name: 'Nightly', bclass: 'Search', onpress : Nightly$t},
	{name: '$upload', bclass: 'import', onpress : Upload$t},
	],";

	$html="
	<table class='ARTICA_META_UPDATEART_TABLE' style='display: none' id='ARTICA_META_UPDATEART_TABLE'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_UPDATEART_TABLE').flexigrid({
	url: '$page?search=yes&uuid={$_GET["uuid"]}&KEY=RELEASES&gpid={$_GET["gpid"]}',
	dataType: 'json',
	colModel : [
		{display: '$version', name : 'version', width : 281, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'update', width : 90, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$version', name : 'version'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>Meta Client:$hostname</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
function Releases$t(){
	$('#ARTICA_META_UPDATEART_TABLE').flexOptions({url: '$page?search=yes&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}&KEY=RELEASES'}).flexReload();
} 
function Nightly$t(){
	$('#ARTICA_META_UPDATEART_TABLE').flexOptions({url: '$page?search=yes&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}&KEY=NIGHTLY'}).flexReload();
} 	

function Upload$t(){
	Loadjs('artica-meta.update.artica.upload.php');

}

</script>";	
	
	echo $html;
	
}

function search(){
	$database="/usr/share/artica-postfix/ressources/conf/meta/updates.db";
	$MyPage=CurrentPageName();
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	$KEY=$_GET["KEY"];
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$dataZ=unserialize(base64_decode(@file_get_contents($database)));
	krsort($dataZ["RELEASES"]);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($dataZ);
	$data['rows'] = array();
	
	$uuid=$_GET["uuid"];
	$c=0;
	while (list ($num, $ligne) = each ($dataZ[$KEY]) ){
		
	
		$c++;
		$ID=md5($num);
		
		$cell=array();
		
		if(preg_match("#artica-([0-9\.]+)\.tgz#", $num,$re)){$version=$re[1];}
	
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=yes&KEY=$KEY&file=$num&version=$version')");
		$cell[]="<span style='font-size:22px'>$version</span>";
		$cell[]=imgsimple("arrow-blue-left-32.png",null,"Loadjs('$MyPage?update-js=yes&KEY=$KEY&file=$num&version=$version&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}')");
		$cell[]=$delete;
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => $cell
		);
	}
	
	if($c==0){json_error_show("no data");}
	echo json_encode($data);
	
	
	
}

function update_save(){
	$q=new mysql_meta();
	$gpid=$_POST["gpid"];
	if($gpid>0){
		if(!$q->CreateOrder_group($gpid, "UPDATE_ARTICA",array("FILENAME"=>$_POST["filename"],"FILETYPE"=>$_POST["filetype"]))){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	
	if(!$q->CreateOrder($_POST["update-uuid"],"UPDATE_ARTICA",array("FILENAME"=>$_POST["filename"],"FILETYPE"=>$_POST["filetype"]))){
		echo $q->mysql_error;
	}
	
	
	
}
