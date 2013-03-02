<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.system.network.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["items"])){items();exit;}
	if(isset($_POST["foldername"])){Addstore();exit;}
	if(isset($_POST["delete"])){DelStore();exit;}
	page();
	
	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$servername=$_GET["servername"];
	$t=time();
	$title=$tpl->javascript_parse_text("{shared_public_stores}");
	$item=$tpl->javascript_parse_text("{item}");
	$choose_member=$tpl->javascript_parse_text("{choose_member}");
	$folder=$tpl->javascript_parse_text("{folder}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$buttons="
	buttons : [
	{name: '$choose_member', bclass: 'Search', onpress : ChooseMember$t},
	
	],	";	
	
	echo "<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&servername=$servername&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'events', width : 31, sortable : false, align: 'center'},
		{display: '$folder', name : 'folder', width :769, sortable : true, align: 'left'},
		{display: '$delete', name : 'events', width : 31, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$folder', name : 'folder'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 887,
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});	


function ChooseMember$t(){
	Loadjs('MembersBrowse.php?OnlyUsers=1&Zarafa=1&callback=BrowseCallBack$t');
}

function BrowseCallBack$t(num,prepend,gid){
	Loadjs('PublicStoreBrowse.php?uid='+num+'&callback=AddStore$t');
}

var x_AddStore$t=function (obj) {
	var results=obj.responseText;			
	if(results.length>3){alert(results);return;}			
	$('#flexRT$t').flexReload();
}
var x_DelStoreD$t=function (obj) {
	var results=obj.responseText;			
	if(results.length>3){alert(results);return;}			
	$('#row'+mem$t).remove();
}

function AddStore$t(foldername,id,uid,type){
	var XHR = new XHRConnection();
	XHR.appendData('servername','$servername');
	XHR.appendData('foldername',foldername);
	XHR.appendData('uid',uid);
	XHR.appendData('ID',id);
	XHR.appendData('type',type);
    XHR.sendAndLoad('$page', 'POST',x_AddStore$t);	
}
function StoreDelete$t(ID){
	mem$t=ID;
	var XHR = new XHRConnection();
	XHR.appendData('servername','$servername');
	XHR.appendData('delete',ID);
    XHR.sendAndLoad('$page', 'POST',x_DelStoreD$t);	
}



</script>
";
	
}

function Addstore(){
	
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->Params["ZPUSH"]["STORES"][$_POST["ID"]]=array(
				"F"=>$_POST["foldername"],"U"=>$_POST["uid"],"T"=>$_POST["type"]);
	$freeweb->SaveParams();
	
}

function DelStore(){
	$freeweb=new freeweb($_POST["servername"]);
	unset($freeweb->Params["ZPUSH"]["STORES"][$_POST["delete"]]);
	$freeweb->SaveParams();	
}


function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$freeweb=new freeweb($_GET["servername"]);
	$page=1;
	$t=$_GET["t"];
	$search='%';
	$total=0;
	$params=$freeweb->Params["ZPUSH"]["STORES"];
	if(count($params)==0){json_error_show("No data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$search=string_to_flexregex();

	$c=0;
	while (list ($ID, $folder) = each ($params) ){
		if($search<>null){
			if(!preg_match("#$search#", $folder["F"])){continue;}
		}
		$type="{$folder["T"]}";
		$img="icon_mailfolder.gif";
		if($type=="SYNC_FOLDER_TYPE_USER_APPOINTMENT"){$img="icon_calendar.gif";}
		if($type=="SYNC_FOLDER_TYPE_USER_CONTACT"){$img="icon_contact.gif";}		
		
		$delete=imgtootltip("delete-24.png",null,"StoreDelete$t('$ID')");
		$c++;
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<img src='img/$img'>",
						"<span style='font-size:18px'>{$folder["U"]}::{$folder["F"]}</span>
						<div style='text-align:right;font-size:12px'>$ID</div>
						",$delete )
		);
	}

	$data['total'] = $c;
	echo json_encode($data);

}
