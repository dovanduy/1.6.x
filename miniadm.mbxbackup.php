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
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["items"])){items();exit;}

if(isset($_POST["enable-item"])){backup_rule_enable();exit;}
if(isset($_POST["delete-item"])){backup_rule_delete();exit;}
if(isset($_POST["run-item"])){backup_rule_perform();exit;}

if(isset($_GET["backup-rule"])){backup_rule();exit;}
if(isset($_POST["imapserv"])){backup_rule_save();exit;}

if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["events-items"])){events_items();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	
	$jsadd=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		<H1>{mailboxes_backups}</H1>
		<p>{mailboxes_backups_text}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?messaging-left=yes');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function messaging_left(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div class=BodyContentWork id='$t'></div><script>LoadAjax('$t','$page?table=yes&uid={$_SESSION["uid"]}&expanded=usermin')</script>";	
	echo $tpl->_ENGINE_parse_body($html);		
}
function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$new_entry=$tpl->javascript_parse_text("{new_backup_rule}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$run=$tpl->javascript_parse_text("{run}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	
	

	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&uid=$uid',
	dataType: 'json',
	colModel : [
		{display: '$imapserv', name : 'imapserv', width :304, sortable : true, align: 'left'},	
		{display: '$account', name : 'account', width :224, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width :38, sortable : true, align: 'center'},
		{display: '$events', name : 'events', width :38, sortable : false, align: 'center'},
		{display: '$run', name : 'events', width :38, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width :38, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$imapserv', name : 'imapserv'},
		{display: '$account', name : 'account'},

	],
	sortname: 'imapserv',
	sortorder: 'asc',
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

var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}



var x_Enable$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#flexRT$t').flexReload();
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
	$q=new mysql();
	$uid=$_GET["uid"];
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->AsMailBoxAdministrator){$uid=$_SESSION["uid"];}	
	
	$search='%';
	$table="mbxs_backup";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="AND uid='$uid'";
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No rules");}

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
	$zmd5=$ligne["zmd5"];
	$color="black";
	$delete=imgsimple("delete-24.png","","Delete$t('$zmd5')");
	$enabled=Field_checkbox("enable_$zmd5", 1,$ligne["enabled"],"Enable$t('$zmd5')");
	
	if($ligne["enabled"]==0){$color="#B3B3B3";}
	
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:GItem$t('$zmd5','{$ligne["imapserv"]}/{$ligne["account"]}')\"
	style='font-size:16px;text-decoration:underline;color:$color'>";
	
	
	$textadd=null;
	$run=imgsimple("24-run.png","","Run$t('$zmd5')");
	$runmin=trim($sock->getFrameWork("offlineimap.php?run-backup-exec=yes&md5=$zmd5"));
	if(is_numeric($runmin)){
		$run=imgsimple("preloader.gif","","Run$t('$zmd5')");
		$textadd=$tpl->_ENGINE_parse_body("<br>{executed} {since} {$runmin}mn");
		$delete="&nbsp;";
	}
	
	$events=null;
	
	$ligneE=@mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM mbxs_backup WHERE zmd5='$zmd5'","artica_events"));
	if($ligneE["tcount"]>0){
		$events=imgsimple("events-24.png","","Events$t('$zmd5')");
	}
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["imapserv"]}</a></span>$textadd",
			"<span style='font-size:18px;color:$color'>$urljs{$ligne["account"]}</a></span>",
			"<span style='font-size:18px;color:$color'>$enabled</span>",
			"<span style='font-size:18px;color:$color'>$events</span>",
			"<span style='font-size:18px;color:$color'>$run</span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function backup_rule_enable(){
	$md5=$_POST["enable-item"];
	$sql="UPDATE mbxs_backup SET enabled={$_POST["value"]} WHERE zmd5='$md5'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}
function backup_rule_perform(){
	$md5=$_POST["run-item"];
	$sock=new sockets();
	$sock->getFrameWork("offlineimap.php?run-backup=yes&md5=$md5");
	
}

function backup_rule(){
	$t=$_GET["t"];
	$tt=time();
	$uid=$_GET["uid"];
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){$uid=$_SESSION["uid"];}
	$buttonname="{add}";
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$zmd5=$_GET["backup-rule"];
	if($zmd5<>null){
		$buttonname="{apply}";
		$sql="SELECT * FROM mbxs_backup WHERE zmd5='$zmd5'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ligne2=unserialize(base64_decode($ligne["config"]));
	}
	
	
	$html="
	<div id='anim-$tt'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{UseMyCorporateMBX}:</td>
		<td>". Field_checkbox("$tt-UseLocal",1,$ligne2["UseLocal"],"UseLocal$tt()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{imap_server}:</td>
		<td>". Field_text("$tt-imapserv",$ligne["imapserv"],"font-size:16px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{account}:</td>
		<td>". Field_text("$tt-account",$ligne["account"],"font-size:16px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("$tt-password",$ligne2["password"],"font-size:16px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{UseSSL}:</td>
		<td>". Field_checkbox("$tt-UseSSL",1,$ligne2["UseSSL"])."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($buttonname, "Save$tt()","18px")."</td>
	</tr>
	</table>	
	<script>
	var x_Save$tt= function (obj) {
			var results=obj.responseText;
			document.getElementById('anim-$tt').innerHTML='';
			if(results.length>3){alert(results);return;}
			$('#flexRT$t').flexReload();
			var xmd5='$zmd5';
			if(xmd5.length==0){YahooWin5Hide();}
		}		
		
		function Save$tt(){
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('$tt-password').value);
			var UseLocal=0;
			var UseSSL=0;
			
			if(document.getElementById('$tt-UseLocal').checked){UseLocal=1;}
			if(document.getElementById('$tt-UseSSL').checked){UseSSL=1;}
			XHR.appendData('zmd5','$zmd5');
			XHR.appendData('uid','$uid');
			XHR.appendData('UseLocal',UseLocal);
			XHR.appendData('UseSSL',UseSSL);
			XHR.appendData('password',pp);
			XHR.appendData('account',document.getElementById('$tt-account').value);
			XHR.appendData('imapserv',document.getElementById('$tt-imapserv').value);
			AnimateDiv('anim-$tt');
			XHR.sendAndLoad('$page', 'POST',x_Save$tt);			
		
		}
		
	function UseLocal$tt(){
		var UseLocal=0;
		if(document.getElementById('$tt-UseLocal').checked){UseLocal=1;}
		if(UseLocal==1){
			document.getElementById('$tt-UseSSL').disabled=true;
			document.getElementById('$tt-password').disabled=true;
			document.getElementById('$tt-account').disabled=true;
			document.getElementById('$tt-imapserv').disabled=true;
		
		}else{
			document.getElementById('$tt-UseSSL').disabled=false;
			document.getElementById('$tt-password').disabled=false;
			document.getElementById('$tt-account').disabled=false;
			document.getElementById('$tt-imapserv').disabled=false;		
		
		}
	}
	UseLocal$tt();
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function backup_rule_delete(){
	$md5=$_POST["delete-item"];
	$q=new mysql();
	$sql="DELETE FROM mbxs_backup WHERE zmd5='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function events_table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=480;
	$uid=$_GET["uid"];
		
	$t=time();
	$new_entry=$tpl->javascript_parse_text("{new_backup_rule}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$run=$tpl->javascript_parse_text("{run}");

	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events-items=yes&t=$t&uid=$uid&zmd5={$_GET["zmd5"]}',
	dataType: 'json',
	colModel : [
		{display: '$zdate', name : 'zDate', width :378, sortable : true, align: 'left'},	
		{display: '$delete', name : 'delete', width :59, sortable : false, align: 'center'},

	],
	$buttons

	
	sortname: 'zDate',
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
</script>
";
	echo $html;
}

function events_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$uid=$_GET["uid"];
	$zmd5=$_GET["zmd5"];
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->AsMailBoxAdministrator){$uid=$_SESSION["uid"];}	
	
	$search='%';
	$table="mbxs_backup";
	$database="artica_events";
	$page=1;
	$FORCE_FILTER=" AND zmd5='$zmd5'";
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No rules");}

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
	
	$sql="SELECT zDate FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	if(mysql_num_rows($results)==0){
		json_error_show("No events $sql");
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=md5(serialize($ligne));
	
	$delete=imgsimple("delete-24.png","","Delete$t('{$ligne["zdate"]}')");
	
	
	$urljs="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:GItem$t('$zmd5','{$ligne["imapserv"]}/{$ligne["account"]}')\"
	style='font-size:16px;text-decoration:underline;color:$color'>";
	
	$dateToText=$tpl->_ENGINE_parse_body(date("{l} d {F} Y H:i:s",strtotime($ligne["zDate"])));
	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs$dateToText</a></span>$textadd",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function backup_rule_save(){
	$q=new mysql();
	$tpl=new templates();
	$users=new usersMenus();
	
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	if($_POST["zmd5"]==null){
		if(!$users->CORP_LICENSE){if($q->COUNT_ROWS("mbxs_backup", "artica_backup")>0){echo $tpl->javascript_parse_text("{free_license_one_rule_text}");return;}}
		$zmd5=md5(serialize($_POST));
		$config=base64_encode(serialize($_POST));
		$sql="INSERT IGNORE INTO mbxs_backup (`uid`,`enabled`,`imapserv`,`account`,`config`,`zmd5`)
		VALUES ('{$_POST["uid"]}',1,'{$_POST["imapserv"]}','{$_POST["account"]}','$config','$zmd5')";
		
	}else{
		$sql="SELECT config FROM mbxs_backup WHERE zmd5='{$_POST["zmd5"]}'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ligne2=unserialize(base64_decode($ligne["config"]));
		while (list ($num, $ligne) = each ($_POST) ){
			$ligne2[$num]=$ligne;
		}
		$config=base64_encode(serialize($ligne2));
		$sql="UPDATE mbxs_backup
		SET imapserv='{$_POST["imapserv"]}',
		account='{$_POST["account"]}',
		config='$ligne2' WHERE zmd5='{$_POST["zmd5"]}'";
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";}

	
}