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
	$tasks=$tpl->_ENGINE_parse_body("{tasks}");
	$title="Kaspersky Mail Security Suite:$tasks";
	echo "YahooWin3('550','$page?table=yes','$title')";
}
	
function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=530;

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
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'pattern', width :31, sortable : true, align: 'center'},
		{display: '$task', name : 'ID', width :326, sortable : true, align: 'left'},
		{display: 'LOG', name : 'pattern', width :31, sortable : false, align: 'center'},
		{display: '$status', name : 'pattern', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'action', width :31, sortable : true, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$task', name : 'task'},

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
	$tasks=unserialize(base64_decode($sock->getFrameWork("klms.php?get-task-list=yes")));
	$taskslogs=unserialize(base64_decode($sock->getFrameWork("klms.php?get-task-logs=yes")));
	
	
	while (list ($taskname, $ligne) = each ($tasks) ){
		if($_POST["query"]<>null){if(!preg_match("#{$_POST["query"]}#i", $taskname)){continue;}}
		$c++;
		$logs=null;
		$id=$ligne["ID"];
		$State=$ligne["State"];;
		$RID=$ligne["RID"];;;
		$delete=imgsimple("delete-24.png",null,"ItemDelete$tSource('$id')");
		$img="danger24.png";
		$action=imgsimple("24-run.png",null,"item_start$tSource($id)");
		
		if(strtolower($State)=="started"){
			$img="ok24.png";
			$action=imgsimple("24-stop.png",null,"item_stop$tSource($id)");
			
		}
		if(strtolower($State)=="failed"){
			$img="warning-panneau-24.png";
			$action=imgsimple("24-run.png",null,"item_start$tSource($id)");
		}	
		if(strtolower($State)=="starting"){
			$img="warning24.png";
			$action=null;
			
		}
		
		if(isset($taskslogs[$taskname])){
			$logs=imgsimple("events-24.png",null,"Loadjs('klms.tasks.logs.php?taskname=$taskname');");
			
		}
		
		$taskContent=null;
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/KlmsTask$id.txt")){
			$taskContent=trim(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/KlmsTask$id.txt"));
			if($taskContent<>null){$taskContent="<div><i style='font-size:11px;font-weight:bold'>$taskContent</i></div>";}
		}
		
		
		$enable=Field_checkbox("enable-$id", 1,$ligne["enabled"],"ItemEnable$tSource('$id')");
		$ligne["describe"]=stripslashes($ligne["describe"]);
		$ligne["pattern"]=base64_decode($ligne["pattern"]);
		$uri="<a href=\"javascript:blur();\" OnClick=\"javascript:ItemForm$tSource($id);\" style='font-size:16px;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => "Rtask$id",
		'cell' => array(
		"<span style='font-size:16px;'>$id</span>",
		"<span style='font-size:16px;'>$taskname ($State)</span>$taskContent</span>",
		$logs,
		"<img src='img/$img'>",
		
		$action )
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
	

