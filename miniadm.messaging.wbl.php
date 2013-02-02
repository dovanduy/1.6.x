<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["whitelist"])){table_whitelist();exit;}
if(isset($_GET["white-items"])){table_whitelist_items();exit;}
if(isset($_POST["white"])){table_whitelist_add();exit;}
if(isset($_POST["whited"])){table_whitelist_del();exit;}
if(isset($_POST["Whitenable"])){table_whitelist_enable();exit;}
if(isset($_POST["WhitenableAll"])){table_whitelist_enableall();exit;}
if(isset($_POST["WhiteDisableAll"])){table_whitelist_disableall();exit;}




if(isset($_GET["blacklist"])){table_blacklist();exit;}
if(isset($_GET["black-items"])){table_blacklist_items();exit;}
if(isset($_POST["moveblack"])){table_blacklist_move();exit;}
if(isset($_POST["black"])){table_blacklist_add();exit;}
if(isset($_POST["blackd"])){table_blacklist_del();exit;}
if(isset($_POST["blkenable"])){table_blacklist_enable();exit;}

if(isset($_POST["BlackenableAll"])){table_blacklist_enableall();exit;}
if(isset($_POST["BlackDisableAll"])){table_blacklist_disableall();exit;}




main_page();

function main_page(){
	$users=new usersMenus();
	if(!$users->AllowEditAsWbl){die();}
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}



function title_day(){
	$tpl=new templates();
	$stime=strtotime($_GET["title-day"]." 00:00:00");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}",$stime));
	echo $title;
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		<H1>{white_black_smtp}</H1>
		<p>{white_black_smtp_text}</p>
		
	</div>	
	<div id='backuped-tabs-$t'></div>
	
	<script>
		LoadAjax('backuped-tabs-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["whitelist"]='{whitelist}';
	$array["blacklist"]='{blacklist}';
	$fontsize="16";
	while (list ($num, $ligne) = each ($array) ){
			
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
$html="
		<div id='main_wbl' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_wbl').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function table_blacklist(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$choose_day=$tpl->_ENGINE_parse_body("{choose_date}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$recipient=$tpl->_ENGINE_parse_body("{recipient}");
	$sent_bt="{name: '&nbsp;$add', bclass: 'eMail', onpress : NewWbl$t},";
	$title=$tpl->_ENGINE_parse_body("{banned_senders}");
	$MoveToWhite=$tpl->javascript_parse_text("{move_to_whitelist}");
	
	
	$enable_all=$tpl->javascript_parse_text("{enable_all}");
	$disable_all=$tpl->javascript_parse_text("{disable_all}");


	
	$buttons="buttons : [
	$sent_bt
	{name: '&nbsp;$enable_all', bclass: 'Down', onpress : EnableAll$t},
	{name: '&nbsp;$disable_all', bclass: 'Up', onpress : DisableAll$t},	
	],	";



	//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?black-items=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$from', name : 'sender', width :699, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'enabled', width :50, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'forward', width :50, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none', width :60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
	{display: '$from', name : 'sender'},
	],
	sortname: 'sender',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
});

var x_NewWbl$t= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
$('#flexRT$t').flexReload();
}

function NewWbl$t(){
var mail=prompt('eMail:');
if(!mail){return;}
var XHR = new XHRConnection();
XHR.appendData('black',mail);
XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);
}

var x_BlackDel$t= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
$('#row'+mem$t).remove();
}


function  BlackDel$t(mail,id){
	if(!confirm('$delete '+mail+' ?')){return;}
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('blackd',id);
	XHR.sendAndLoad('$page', 'POST',x_BlackDel$t);
}

function MoveToWhite$t(md5,mail){
	if(!confirm('$MoveToWhite '+mail+' ?')){return;}
	mem$t=md5;
	var XHR = new XHRConnection();
	XHR.appendData('moveblack',md5);
	XHR.sendAndLoad('$page', 'POST',x_BlackDel$t);	
}

function BlckEnable$t(id){
	var XHR = new XHRConnection();
	XHR.appendData('blkenable',id);
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);	
}

function EnableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BlackenableAll','yes');
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);
}
function DisableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BlackDisableAll','yes');
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);
}

</script>";

echo $html;
}

function table_whitelist_enableall(){
	$q=new mysql();
	$sql="UPDATE contacts_whitelist SET `enabled`='1' WHERE `uid`='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}
function table_blacklist_enableall(){
	$q=new mysql();
	$sql="UPDATE contacts_blacklist SET `enabled`='1' WHERE `uid`='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}
function table_blacklist_disableall(){
	$q=new mysql();
	$sql="UPDATE contacts_blacklist SET `enabled`='0' WHERE `uid`='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}
function table_whitelist_disableall(){
	$q=new mysql();
	$sql="UPDATE contacts_whitelist SET `enabled`='0' WHERE `uid`='{$_SESSION["uid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}
function table_whitelist_enable(){
	$q=new mysql();
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `uid`,`enabled` FROM `contacts_whitelist` WHERE md5='{$_POST["Whitenable"]}'",
	"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;return;}
	if($ligne2["enabled"]==1){$_POST["value"]=0;}else{$_POST["value"]=1;}
	
	$sql="UPDATE contacts_whitelist SET `enabled`='{$_POST["value"]}' WHERE `md5`='{$_POST["Whitenable"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function table_blacklist_enable(){
	$q=new mysql();
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `uid`,`enabled` FROM `contacts_blacklist` WHERE md5='{$_POST["blkenable"]}'",
	"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;return;}
	if($ligne2["enabled"]==1){$_POST["value"]=0;}else{$_POST["value"]=1;}
	
	$sql="UPDATE contacts_blacklist SET `enabled`='{$_POST["value"]}' WHERE `md5`='{$_POST["blkenable"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function table_blacklist_move(){
	$q=new mysql();
	$uid=$_SESSION["uid"];
	$md5=$_POST["moveblack"];
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT sender FROM `contacts_blacklist` WHERE md5='$md5'","artica_backup"));
	$sender=$ligne2["sender"];
	$sql="INSERT IGNORE INTO contacts_whitelist (`sender`,`uid`,`md5`,`enabled`,`manual`) VALUES
	('$sender','$uid','$md5','1','1')";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM `contacts_blacklist` WHERE md5='$md5'","artica_backup");
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function table_whitelist(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$choose_day=$tpl->_ENGINE_parse_body("{choose_date}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$recipient=$tpl->_ENGINE_parse_body("{recipient}");
	$sent_bt="{name: '&nbsp;$add', bclass: 'eMail', onpress : NewWbl$t},";
	$enable_all=$tpl->javascript_parse_text("{enable_all}");
	$disable_all=$tpl->javascript_parse_text("{disable_all}");
	$title=$tpl->_ENGINE_parse_body("{whitelisted_senders}");
$buttons="buttons : [
		$sent_bt
		{name: '&nbsp;$enable_all', bclass: 'Down', onpress : EnableAll$t},
		{name: '&nbsp;$disable_all', bclass: 'Up', onpress : DisableAll$t},
			],	";	
	

	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?white-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$from', name : 'sender', width :789, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'none', width :60, sortable : true, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$from', name : 'sender'},
	],
	sortname: 'sender',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_NewWbl$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#flexRT$t').flexReload();	
	}		

function NewWbl$t(){
	var mail=prompt('eMail:');
	if(!mail){return;}
	var XHR = new XHRConnection();
	XHR.appendData('white',mail);
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);	
}

	var x_WhiteDel$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
	}

	
function WhiteEnable$t(id){
	var XHR = new XHRConnection();
	XHR.appendData('Whitenable',id);
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);	
}	

function EnableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('WhitenableAll','yes');
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);
}
function DisableAll$t(){
	var XHR = new XHRConnection();
	XHR.appendData('WhiteDisableAll','yes');
	XHR.sendAndLoad('$page', 'POST',x_NewWbl$t);
}


	
		
	function  WhiteDel$t(mail,id){
		if(!confirm('$delete '+mail+' ?')){return;}
		mem$t=id;
		var XHR = new XHRConnection();
		XHR.appendData('whited',mail);
		XHR.sendAndLoad('$page', 'POST',x_WhiteDel$t);
		}

</script>";
	
	echo $html;	
}

function table_whitelist_add(){
	$uid=$_SESSION["uid"];
	$emailAddress_str=$_POST["white"];
	$md5=md5("$emailAddress_str$uid");
	if(!ValidateMail($emailAddress_str)){echo "Fatal {$_POST["white"]}, wrong email address\n";return;}
	$sql="INSERT IGNORE INTO contacts_whitelist (`sender`,`uid`,`manual`,`md5`,`enabled`) VALUES ('$emailAddress_str','$uid','1','$md5','1')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function table_blacklist_add(){
	$uid=$_SESSION["uid"];
	$emailAddress_str=$_POST["black"];
	$md5=md5("$emailAddress_str$uid");
	if(!ValidateMail($emailAddress_str)){echo "Fatal {$_POST["white"]}, wrong email address\n";return;}
	$sql="INSERT IGNORE INTO contacts_blacklist (`sender`,`uid`,`enabled`,`md5`) VALUES ('$emailAddress_str','$uid','1','$md5')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}

function table_whitelist_del(){
	$uid=$_SESSION["uid"];
	$emailAddress_str=$_POST["whited"];	
	$sql="DELETE FROM contacts_whitelist WHERE `sender`='$emailAddress_str' AND `uid`='$uid'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}

function table_blacklist_del(){
	$uid=$_SESSION["uid"];
	$md5=$_POST["blackd"];
	$sql="DELETE FROM contacts_blacklist WHERE `md5`='$md5'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}


function ValidateMail($emailAddress_str) {
	$emailAddress_str=trim(strtolower($emailAddress_str));
	$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (preg_match($regex, $emailAddress_str)) {return true;}
	return false;
}

function MessageID_resend_popup(){
	$tpl=new templates();
	$q=new mysql_mailarchive_builder();
	$sql="SELECT mailto,mailfrom,message_size,original_messageid,zDate FROM `{$_GET["table"]}` WHERE MessageID='{$_GET["MessageID-resend-popup"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	$subkect=mime_decode($ligne["subject"]);
	$page=CurrentPageName();
			
	
	$t=time();
	$tpl=new templates();
	$ligne["zDate"]=date('{l} d {F} H:i:s',strtotime($ligne["zDate"]));
	
	$html="
	<div class=BodyContent>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{zDate}:</td>
		<td style='font-size:16px'>{$ligne["zDate"]}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{message_id}:</td>
		<td style='font-size:16px'>{$ligne["original_messageid"]}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{sender}:</td>
		<td>". Field_text("mailfrom-$t",$ligne["mailfrom"],"font-size:16px;width:240px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{recipient}:</td>
		<td>". Field_text("mailto-$t",$ligne["mailto"],"font-size:16px;width:240px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{size}:</td>
		<td style='font-size:16px'>". FormatBytes($ligne["message_size"]/1024)."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{resend}","Resend$t()","18px")."</td>
	</tr>
	</table>	
	</div>
	<span id='$t-div'></span>
<script>
	var x_Resend$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-div').innerHTML=results;
	}		
	
		
	function  Resend$t(){
		
		
		AnimateDiv('$t-div');
		var mailfrom=document.getElementById('mailfrom-$t').value;
		var mailto=document.getElementById('mailto-$t').value;
		
		var XHR = new XHRConnection();
		XHR.appendData('mailfrom',mailfrom);
		XHR.appendData('mailto',mailto);
		XHR.appendData('MessageID-send','{$_GET["MessageID-resend-popup"]}');
		XHR.appendData('table','{$_GET["table"]}');
		XHR.sendAndLoad('$page', 'POST',x_Resend$t);
		}
</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function table_whitelist_items(){
	$myPage=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$table="`contacts_whitelist`";
	$uid=$_SESSION["uid"];
	$search='%';
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND uid='$uid'";
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS($table,"artica_backup")){
			json_error_show("$table: No such table",0,true);
	}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$sender=$ligne["sender"];
		$md=$ligne["md5"];
		$color="black";
		$delete=imgsimple("delete-32.png",null,"WhiteDel$t('$sender','$md')");
		$enable=Field_checkbox($ligne["md5"], 1,$ligne["enabled"],"WhiteEnable$t('$md')");
		
		
		$manual=$ligne["manual"];
		if($manual==0){$delete=null;$color="#847F7F";}
		if($ligne["enabled"]==0){$color="#847F7F";}
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => md5($sender),
				'cell' => array(
					"<span style='font-size:18px;color:$color;font-weight:bold'>$sender</a></span>",
					"<span style='font-size:18px;color:$color'>$enable</a></span>",
					"<span style='font-size:18px;color:$color'>$delete</a></span>",
					
					)
				);
			}
	
	
echo json_encode($data);	
	
	
	
}

function table_blacklist_items(){
	$myPage=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$table="`contacts_blacklist`";
	$uid=$_SESSION["uid"];
	$search='%';
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND uid='$uid'";
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS($table,"artica_backup")){
		json_error_show("$table: No such table",0,true);
	}
	
	if($q->COUNT_ROWS($table, $database)==0){
		json_error_show("$table: Empty",0,true);
	}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$sender=$ligne["sender"];
		$md=$ligne["md5"];
		$color="black";
		$delete=imgsimple("delete-32.png",null,"BlackDel$t('$sender','$md')");
		$enabled=$ligne["enabled"];
		if($enabled==0){$color="#847F7F";}
	
		$move=imgsimple("arrow-blue-left-32.png",null,"MoveToWhite$t('$md','$sender')");
		$enablechk=Field_checkbox("enable_$md", 1,$enabled,"BlckEnable$t('$md')");
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						"<span style='font-size:18px;color:$color;font-weight:bold'>$sender</a></span>",
						"<span style='font-size:18px;color:$color'>$enablechk</a></span>",
						"<span style='font-size:18px;color:$color'>$move</a></span>",
						"<span style='font-size:18px;color:$color'>$delete</a></span>",
							
				)
		);
	}
	
	
	echo json_encode($data);	
	
}






