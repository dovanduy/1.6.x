<?php
$GLOBALS["MAIN_PATH"]="/usr/share/artica-postfix/ressources/conf/meta";
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
	if(isset($_POST["update-uuid"])){update_save();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete_perform();exit;}
	
	
	js();
	
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{update}");
	$_GET["gpid"]=intval($_GET["gpid"]);
	$page=CurrentPageName();
	echo "YahooWin4('850','$page?table=yes&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}','$title')";
}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{delete} {$_GET["file"]}");
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_PACKAGE_TABLE').flexReload();

}

function xFunct$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','yes');
	XHR.appendData('filename','{$_GET["file"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
echo $html;
}



function update_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	
	if($_GET["gpid"]>0){
		$hostname=$tpl->javascript_parse_text("{computers}: ").$q->gpid_to_name($_GET["gpid"]);
	}else{
		$hostname=$q->uuid_to_host($_GET["uuid"]);
	}
	
	
	$title=$tpl->javascript_parse_text("{install_package} $hostname -> {$_GET["filename"]} ?");
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
	XHR.appendData('filename','{$_GET["filename"]}');
	XHR.appendData('SIZE','{$_GET["size"]}');
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
	
	
	
	
	$disks=$tpl->javascript_parse_text("{disks}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$package=$tpl->javascript_parse_text("{package}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$upload=$tpl->javascript_parse_text("{upload}");
	
	$title=$tpl->javascript_parse_text("{packages}");
	
	if($_GET["uuid"]<>null){
		$hostname=$q->uuid_to_host($_GET["uuid"]);
		$title="Meta Client:$hostname - $package";
	}
	
	if($_GET["gpid"]>0){
		$hostname=$q->gpid_to_name($_GET["gpid"]);
		$title="Meta Clients:$hostname - $package";
	}
	
	
	$buttons="	buttons : [
	{name: '$upload', bclass: 'export', onpress : Upload$t},
	],";

	$html="
	<table class='ARTICA_META_PACKAGE_TABLE' style='display: none' id='ARTICA_META_PACKAGE_TABLE'></table>
	<script>
	$(document).ready(function(){
	$('#ARTICA_META_PACKAGE_TABLE').flexigrid({
	url: '$page?search=yes&uuid={$_GET["uuid"]}&gpid={$_GET["gpid"]}&KEY=RELEASES',
	dataType: 'json',
	colModel : [
		{display: '$package', name : 'version', width : 609, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'update', width : 50, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$package', name : 'version'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
function Upload$t(){
	Loadjs('artica-meta.packages.upload.php');
} 
function Nightly$t(){
	$('#ARTICA_META_UPDATEART_TABLE').flexOptions({url: '$page?search=yes&uuid={$_GET["uuid"]}&KEY=NIGHTLY'}).flexReload();
} 	
</script>";	
	
	echo $html;
	
}

function search(){
	$database="{$GLOBALS["MAIN_PATH"]}/softwares.db";
	$MyPage=CurrentPageName();
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	$KEY=$_GET["KEY"];
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$dataZ=unserialize(base64_decode(@file_get_contents($database)));
	krsort($dataZ);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($dataZ);
	$data['rows'] = array();
	
	$uuid=$_GET["uuid"];
	
	$search=string_to_flexregex();
	$c=0;
	while (list ($filename, $MAIN_ARRAY) = each ($dataZ) ){
		if($search<>null){
			if(!preg_match($search, $filename)){continue;}
		}
	
		$c++;
		$ID=md5($filename);
		$size=FormatBytes($MAIN_ARRAY["SIZE"]/1024);
		
		$cell=array();
		$filenameenc=urlencode($filename);
		
		$affect=imgsimple("arrow-blue-left-32.png",null,"Loadjs('$MyPage?update-js=yes&filename=$filenameenc&uuid={$_GET["uuid"]}')");
		if($_GET["uuid"]==null){$affect=null;}
		if($_GET["gpid"]>0){
			$affect=imgsimple("arrow-blue-left-32.png",null,"Loadjs('$MyPage?update-js=yes&filename=$filenameenc&gpid={$_GET["gpid"]}')");
		}
		
		
		$cell[]="<span style='font-size:22px'>$filename<br><i>$size</i></span>";
		$cell[]=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=yes&file=$filenameenc&size={$MAIN_ARRAY["SIZE"]}')");
		$cell[]=$affect;
	
	
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => $cell
		);
	}
	
	if($c==0){json_error_show("no data");}
	echo json_encode($data);
	
	
	
}

function update_save(){
	
	
	
	$q=new mysql_meta();
	
	if($_GET["gpid"]>0){
		
		
		if(!$q->CreateOrder_group($_POST["update-uuid"],"INSTALL_SOFTWARE",array("FILENAME"=>$_POST["filename"],"FILESIZE"=>$_POST["SIZE"]),true)){
			echo $q->mysql_error;
			
		}
		
		return;
	}
	
	
	if(!$q->CreateOrder($_POST["update-uuid"],"INSTALL_SOFTWARE",array("FILENAME"=>$_POST["filename"],"FILESIZE"=>$_POST["SIZE"]))){
		echo $q->mysql_error;
	}
	
	
	
}

function delete_perform(){
	$sock=new sockets();
	$fileName=$_POST["filename"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$fileNameEnc=urlencode($fileName);
	$sock=new sockets();
	$sock->getFrameWork("artica.php?meta-delete-repos=yes&filename=$fileNameEnc");
	sleep(3);
	
}

