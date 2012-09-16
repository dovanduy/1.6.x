<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.spamassassin.inc');
	include_once('ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
	$user=new usersMenus();
		if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["item-id"])){item_popup();exit;}
	if(isset($_POST["ID"])){item_save();exit;}
	if(isset($_POST["ItemEnable"])){item_enable();exit;}
	if(isset($_POST["delete-item"])){item_delete();exit;}
	if(isset($_POST["rebuild"])){rebuild();exit;}
js();	
	
function js(){
	$page=CurrentPageName();
	$id=$_GET["byid"];
	echo "LoadAjax('$id','$page?table=yes');";
	
}


function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=897;

	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$pattern=$tpl->_ENGINE_parse_body("pattern");
	$execute=$tpl->_ENGINE_parse_body("{execute}");
	$header=$tpl->_ENGINE_parse_body("{header}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$check_recipients=$tpl->_ENGINE_parse_body("{check_recipients}");
	$build_rules=$tpl->_ENGINE_parse_body("{build_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewItem$t},
	{name: '$check_recipients', bclass: 'eMail', onpress : check_recipients$t},
	{name: '$build_rules', bclass: 'Reconf', onpress : BuildRules$t},
	{name: '$online_help', bclass: 'Help', onpress : help$t},
	
	
	
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$rule', name : 'ID', width :31, sortable : true, align: 'center'},
		{display: '$pattern', name : 'pattern', width :315, sortable : true, align: 'left'},
		{display: '$header', name : 'header', width :91, sortable : true, align: 'left'},
		{display: 'score 1', name : 'score1', width :77, sortable : true, align: 'left'},
		{display: 'score 2', name : 'score2', width :64, sortable : true, align: 'left'},
		{display: 'score 3', name : 'score3', width :64, sortable : true, align: 'left'},
		{display: 'score 4', name : 'score4', width :64, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width :31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$description', name : 'describe'},
		{display: '$header', name : 'header'},
		{display: '$rule', name : 'ID'},
	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: 'SpamAssassin $rules',
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
	
function XapianDir$t(id){
	YahooWin5('682','$page?item-id='+id+'&t=$t','Xapian Desktop:'+id);
}
function NewItem$t(){
	title='$new_entry';
	YahooWin5('682','$page?item-id=0&t=$t','SpamAssassin:'+title);
}
function ItemForm$t(ID){
	title=ID;
	YahooWin5('682','$page?item-id='+ID+'&t=$t','SpamAssassin:'+title);
}
var x_XapianExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);return;}
	$('#flexRT$t').flexReload();
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
	$q=new mysql();
	$tSource=$_GET["t"];
	
	$search='%';
	$table="spamassassin_rules";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",1);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$articasrv=null;
		$address=null;
		$delete=imgsimple("delete-24.png",null,"ItemDelete$tSource('$id')");
		$enable=Field_checkbox("enable-$id", 1,$ligne["enabled"],"ItemEnable$tSource('$id')");
		$ligne["describe"]=stripslashes($ligne["describe"]);
		$ligne["pattern"]=base64_decode($ligne["pattern"]);
		$uri="<a href=\"javascript:blur();\" OnClick=\"javascript:ItemForm$tSource($id);\" style='font-size:16px;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:16px;'>$id</span>",
		"$uri{$ligne["pattern"]}</a><div style='font-size:12px'>{$ligne["describe"]}</div>",
		"<span style='font-size:16px;'>$uri{$ligne["header"]}</a></span>",
		"<span style='font-size:16px;'>{$ligne["score1"]}</span>",
		"<span style='font-size:16px;'>{$ligne["score2"]}</span>",
		"<span style='font-size:16px;'>{$ligne["score3"]}</span>",
		"<span style='font-size:16px;'>{$ligne["score4"]}</span>",
		$enable,
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function item_delete(){
	$q=new mysql();
	$ID=$_POST["delete-item"];
	$sql="DELETE FROM spamassassin_rules WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}

function item_enable(){
	$ID=$_POST["ItemEnable"];
	$q=new mysql();
	$sql="SELECT enabled FROM spamassassin_rules WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["enabled"]==0){$enabled=1;}
	if($ligne["enabled"]==1){$enabled=0;}
	$sql="UPDATE spamassassin_rules SET enabled=$enabled WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	$bname="{add}";

	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
	
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM spamassassin_rules WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$describe=stripslashes($ligne["describe"]);
		$pattern=base64_decode($ligne["pattern"]);
		$header=$ligne["header"];
		$score1=$ligne["score1"];
		$score2=$ligne["score2"];
		$score3=$ligne["score3"];
		$score4=$ligne["score4"];
		
	}

	
	if($score1==null){$score1="2.899";}
	if($score2==null){$score2="2.800";}
	if($score3==null){$score3="2.696";}
	if($score4==null){$score4="0.989";}
	if($describe==null){$describe="New Content rule";}
	$headers[null]="{all}";
$headersT=file("ressources/databases/db.headers.txt");
while (list ($num, $ligne) = each ($headersT) ){
	if(isset($alr[strtolower($ligne)])){continue;}
	$headers[$ligne]=$ligne;
	$alr[strtolower($ligne)]=true;
}
	ksort($headers);

$headerF=Field_array_Hash($headers,"header-$t",$header,null,null,0,"font-size:18px");

$html="		
<div id='anime-$t'></div>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:18px' nowrap>{description}:</strong></td>
	<td align=left colspan=2>". Field_text("describe-$t",$describe,"width:480px;font-size:18px","script:FormCheck$t(event)")."</strong></td>
</tr>	
<tr>
	<td class=legend style='font-size:18px' nowrap>{header}:</strong></td>
	<td align=left style='font-size:18px' colspan=2 >$headerF</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:18px' nowrap>Regex:</strong></td>
	<td align=left colspan=2 style='font-size:18px'>". Field_text("pattern-$t",$pattern,"width:480px;font-size:18px;padding:3px","script:FormCheck$t(event)")."</strong></td>

</tr>
<tr>
	<td class=legend style='font-size:18px' nowrap>{score} 1:</strong></td>
	<td align=left style='font-size:18px'>". Field_text("score1-$t",$score1,"width:80px;font-size:18px","script:FormCheck$t(event)")."</strong></td>
	<td style='font-size:12px' align='left'>&nbsp;{spamass_scr1}</td>
</tr>
<tr>
	<td class=legend style='font-size:18px' nowrap>{score} 2:</strong></td>
	<td align=left style='font-size:18px'>". Field_text("score2-$t",$score2,"width:80px;font-size:18px","script:FormCheck$t(event)")."</strong></td>
	<td style='font-size:12px' align='left'>&nbsp;{spamass_scr2}</td>
</tr>
<tr>
	<td class=legend style='font-size:18px' nowrap>{score} 3:</strong></td>
	<td align=left style='font-size:18px'>". Field_text("score3-$t",$score3,"width:80px;font-size:18px","script:FormCheck$t(event)")."</strong></td>
	<td style='font-size:12px' align='left'>&nbsp;{spamass_scr3}</td>
</tr>
<tr>
	<td class=legend style='font-size:18px' nowrap width=1% nowrap>{score} 4:</strong></td>
	<td align=left style='font-size:18px' width=5%>". Field_text("score4-$t",$score4,"width:80px;font-size:18px","script:FormCheck$t(event)")."</strong></td>
	<td style='font-size:12px' align='left' width=99%>&nbsp;{spamass_scr4}</td>
</tr>
<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveItem$t();","18px")."</td>
<tr>
</table>
<script>

		function FormCheck$t(e){
			if(checkEnter(e)){SaveItem$t();return;}
		}
		

		var x_SaveItem$t=function (obj) {
			var results=obj.responseText;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>3){alert(results);return;}
			$('#flexRT$t').flexReload();
		}

		function SaveItem$t(){
			var ok=1;
			WebCopyID=0;
			var describe=encodeURIComponent(document.getElementById('describe-$t').value);
			var pattern=encodeURIComponent(document.getElementById('pattern-$t').value);
			var header=document.getElementById('header-$t').value;
			if(header.length==0){header='ALL'}
			if(pattern.length>1){ok=1;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			
			XHR.appendData('ID','$id');
			XHR.appendData('describe',describe);
			XHR.appendData('pattern',pattern);
			XHR.appendData('header',header);
			XHR.appendData('score4',document.getElementById('score4-$t').value);
			XHR.appendData('score3',document.getElementById('score3-$t').value);
			XHR.appendData('score2',document.getElementById('score2-$t').value);
			XHR.appendData('score1',document.getElementById('score1-$t').value);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveItem$t);
		
		}

</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}
function item_save(){
	$ID=$_POST["ID"];
	$_POST["describe"]=addslashes(url_decode_special_tool($_POST["describe"]));
	$_POST["pattern"]=$pattern=base64_encode(url_decode_special_tool($_POST["pattern"]));
	$_POST["header"]=trim($_POST["header"]);
	
	if($ID==0){
		$sql="INSERT INTO spamassassin_rules (`describe`,`pattern`,`header`,`score1`,`score2`,`score3`,`score4`,`enabled`) 
		VALUES('{$_POST["describe"]}','{$_POST["pattern"]}','{$_POST["header"]}','{$_POST["score1"]}','{$_POST["score2"]}','{$_POST["score3"]}','{$_POST["score4"]}',1)
		";
		
		
	}else{
		$sql="UPDATE spamassassin_rules SET `describe`='{$_POST["describe"]}',
		`pattern`='{$_POST["pattern"]}',
		`header`='{$_POST["header"]}',
		`score1`='{$_POST["score1"]}',
		`score2`='{$_POST["score2"]}',
		`score3`='{$_POST["score3"]}',
		`score4`='{$_POST["score4"]}' WHERE ID=$ID";
		
	}
	
	$q=new mysql();
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
}
function rebuild(){
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-amavis=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_in_background}");
}