<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_POST["ItemStart"])){items_start();exit;}
	if(isset($_POST["ItemStop"])){items_stop();exit;}
	
	

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tasks=$tpl->_ENGINE_parse_body("{tasks}::{events}");
	$title="Kaspersky Mail Security Suite:$tasks::{$_GET["taskname"]}";
	echo "YahooWin4('895','$page?table=yes&taskname={$_GET["taskname"]}','$title')";
}
	
function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=881;

	$task=$tpl->_ENGINE_parse_body("{task}");
	$t=time();
	$status=$tpl->_ENGINE_parse_body("{status}");

	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewItem$t},
	{name: '$check_recipients', bclass: 'eMail', onpress : check_recipients$t},
	{name: '$build_rules', bclass: 'Reconf', onpress : BuildRules$t},
	{name: '$online_help', bclass: 'Help', onpress : help$t},
	
	
	
	
	],	";
	
	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&taskname={$_GET["taskname"]}',
	dataType: 'json',
	colModel : [
		{display: 'Date', name : 'Date', width :115, sortable : true, align: 'left'},
		{display: 'PID', name : 'PID', width :42, sortable : true, align: 'center'},
		{display: 'LOG', name : 'pattern', width :668, sortable : false, align: 'left'},
		

	],
	$buttons

	searchitems : [
		{display: 'LOG', name : 'LOG'},

	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_ItemDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}
	
function check_recipients$t(){Loadjs('postfix.debug.mx.php');}

function ItemDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_ItemDelete$t);	
	}
function XapianEvents$t(){
		Loadjs('squid.update.events.php?table=system_admin_events&category=xapian');
}

function help$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=270','1024','900');
}
	

function NewItem$t(){
	title='$new_entry';
	YahooWin5('682','$page?item-id=0&t=$t','SpamAssassin:'+title);
}
function ItemForm$t(ID){
	title=ID;
	YahooWin5('682','$page?item-id='+ID+'&t=$t','SpamAssassin:'+title);
}
var x_ItemExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}

function item_start$t(id){
	var XHR = new XHRConnection();
	XHR.appendData('ItemStart',id);
    XHR.sendAndLoad('$page', 'POST',x_ItemExec$t);
}
function item_stop$t(id){
	var XHR = new XHRConnection();
	XHR.appendData('ItemStop',id);
    XHR.sendAndLoad('$page', 'POST',x_ItemExec$t);
}


var x_ItemEnable$t=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);return;}
	
}

function BuildRules$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rebuild','yes');
    XHR.sendAndLoad('$page', 'POST',x_ItemEnable$t);
}

function ItemEnable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('ItemEnable',ID);
    XHR.sendAndLoad('$page', 'POST',x_ItemEnable$t);	
}
function XapianExec$t(){
	var XHR = new XHRConnection();
	XHR.appendData('exec','yes');
    XHR.sendAndLoad('$page', 'POST',x_XapianExec$t);	
}
	
</script>";
	
	echo $html;		
}	

function items(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$tSource=$_GET["t"];
	$taskname=$_GET["taskname"];
	$taskslogs=unserialize(base64_decode($sock->getFrameWork("klms.php?get-task-logs=yes")));
	if(!isset($_GET["taskfile"])){$_GET["taskfile"]=$taskslogs[$taskname][0];}
	$search='';
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){$_POST["query"]=string_to_regex($_POST["query"]);}
		
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	$search=base64_encode(string_to_regex($_POST["query"]));
	$events=unserialize(base64_decode($sock->getFrameWork("klms.php?get-task-events=yes&taskfile={$_GET["taskfile"]}&search=$search&rp={$_POST["rp"]}")));
	
	
	
	while (list ($taskname, $ligne) = each ($events) ){
		$id=md5($ligne);
		if(trim($ligne)==null){continue;}
		if(preg_match("#(.*)\s+([0-9\:]+)\.[0-9]+.*?\s+[0-9a-z]+\s+(.*?)\s+(.*)#", $ligne,$re)){
			$date=strtotime("{$re[1]} {$re[2]}");
			$zdate=date("Y-m-d H:i:s");
			$pid=$re[3];
			$event=$re[4];
		}else{
		$event=$ligne;
		}
		
		
	$data['rows'][] = array(
		'id' => "Rtask$id",
		'cell' => array(
		"<span style='font-size:12px;'>$zdate</span>",
		"<span style='font-size:12px;'>$pid</span>",
		"<span style='font-size:12px;'>$event</span>",
		)
		);
		$data['total'] = $c;
	}
	
	
echo json_encode($data);		
	
}



function items_start(){
	$taskid=$_POST["ItemStart"];
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("klms.php?task-start=$taskid"));
}
function items_stop(){
	$taskid=$_POST["ItemStop"];
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("klms.php?task-stop=$taskid"));
}
	

