<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["thumbnail"])){thumbnail();exit;}
if(isset($_GET["categories-list"])){categories_list();exit;}
if(isset($_POST["rebuild"])){rebuild();exit;}
main_page();

function main_page(){
	$page=CurrentPageName();
	
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}
	
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php\">{web_statistics}</a>
		</div>
		<H1>{members}</H1>
		<p>{display_access_by_members}</p>
	</div>	
	<div id='master-content'></div>
	
	<script>
		LoadAjax('master-content','$page?master-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function master_content(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$uid=$tpl->_ENGINE_parse_body("{uid}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$QuerySize=$tpl->javascript_parse_text("{QuerySize}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$rebuild=$tpl->_ENGINE_parse_body("{rebuild}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$buttons="
	buttons : [
	{name: '$all', bclass: 'Search', onpress : CompressAll$t},
	{name: '$uid', bclass: 'Search', onpress : CompressUid$t},
	{name: '$MAC', bclass: 'Search', onpress : CompressMAC$t},
	{name: '$rebuild', bclass: 'Reload', onpress : Rebuild$t},
	
	],	";


//youtubeid 	 	title 	content 	 	hits 	 	thumbnail	

	$html="
	<div class=BodyContent>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	</div>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&uid=$uid',
	dataType: 'json',
	colModel : [
		{display: '$ipaddr', name : 'ipaddr', width :108, sortable : false, align: 'left'},
		{display: '$MAC', name : 'MAC', width :114, sortable : false, align: 'left'},
		{display: '$hostname', name : 'hostname', width :202, sortable : true, align: 'left'},
		{display: '$uid', name : 'uid', width :180, sortable : true, align: 'left'},	
		{display: '$QuerySize', name : 'QuerySize', width :107, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :107, sortable : true, align: 'left'},

	
	],
	$buttons

	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		{display: '$MAC', name : 'MAC'},
		{display: '$uid', name : 'uid'},

	],
	sortname: 'QuerySize',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\"></span>',
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

function CompressAll$t(){
	$('#flexRT$t').flexOptions({url: '$page?items=yes'}).flexReload();	
}
function CompressUid$t(){
	$('#flexRT$t').flexOptions({url: '$page?items=yes&ByUid=yes'}).flexReload();	
}
function CompressMAC$t(){
	$('#flexRT$t').flexOptions({url: '$page?items=yes&ByMAC=yes'}).flexReload();	
}

var x_Enable$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#flexRT$t').flexReload();
}

function Rebuild$t(){
	if(confirm('$error_want_operation')){
 		var XHR = new XHRConnection();
   	 	XHR.appendData('rebuild','yes');
   	 	XHR.sendAndLoad('$page', 'POST',x_Enable$t);		
	}	

}

var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}


function Enable$t(md){
	mem$t=md;
	var enable=0;
	if(document.getElementById('enable_'+md).checked){enable=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-item',md);
    XHR.appendData('value',enable);
    XHR.sendAndLoad('$page', 'POST',x_Enable$t);		
   
	}

function Delete$t(md){
	mem$t=md;
	if(confirm('$action_delete_rule')){
 		var XHR = new XHRConnection();
   	 	XHR.appendData('delete-item',md);
   	 	XHR.sendAndLoad('$page', 'POST',x_Delete$t);		
	}
}

function Run$t(md){
	mem$t=md;
	if(confirm('$error_want_operation')){
 		var XHR = new XHRConnection();
   	 	XHR.appendData('run-item',md);
   	 	XHR.sendAndLoad('$page', 'POST',x_Enable$t);		
	}

}

function Events$t(md){
	YahooWin5('505','$page?events-table=yes&zmd5='+md+'&t=$t','$events');
}

function NewGItem$t(){
	YahooWin5('600','$page?backup-rule=&uid=$uid&t=$t','$new_entry');

}
function GItem$t(md,title){
	YahooWin5('600','$page?backup-rule='+md+'&uid=$uid&t=$t',title);

}


</script>";
	
	echo $html;
	
	
	
}

function items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$uid=$_GET["uid"];
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->AsMailBoxAdministrator){$uid=$_SESSION["uid"];}	
	
	$search='%';
	$table="UserAuthDaysGrouped";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=null;
	if(isset($_GET["ByUid"])){
		$FORCE_FILTER=" AND LENGTH(uid)>1";
	}
	if(isset($_GET["ByMAC"])){
		$FORCE_FILTER=" AND LENGTH(MAC)>1";
	}	
	
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if(!$q->COUNT_ROWS($table, $database)){json_error_show("No rules");}

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
	$zmd5=md5(serialize($ligne));
	
	$delete=imgsimple("delete-24.png","","Delete$t('$zmd5')");
	$enabled=Field_checkbox("enable_$zmd5", 1,$ligne["enabled"],"Enable$t('$zmd5')");
	$color="black";
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:GItem$t('$zmd5','{$ligne["imapserv"]}/{$ligne["account"]}')\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
	$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
	
	if($ligne["MAC"]<>null){
		if(strlen($ligne["uid"])<2){
			$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);
		}
		
	}
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["ipaddr"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["MAC"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["hostname"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["uid"]}</span>",
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["QuerySize"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["hits"]}</span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function rebuild(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?stats-members-generic=yes");
	
}



