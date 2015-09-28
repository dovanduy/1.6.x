<?php
if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.squid.acls.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){die();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-items-list"])){rule_list();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["help"])){show_help();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_POST["delete"])){rule_delete();exit;}
if(isset($_POST["enable"])){rule_enable();exit;}
js();
function enable_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_GET["enable-js"];

	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#SQUID_NTLM_WHITE').flexReload();
}

function xFunct$t(){

var XHR = new XHRConnection();
XHR.appendData('enable','$ID');
LockPage();
XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;



}

function rule_enable(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_POST["enable"];


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM `acls_ntlm` WHERE `ID`='$ID'"));
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE acls_ntlm SET `enabled`='$enabled' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$ID=$_GET["delete-js"];


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT item FROM `acls_ntlm` WHERE `ID`='$ID'"));
	$text=$tpl->javascript_parse_text("{delete} {rule} $ID - {$ligne["item"]} ?");

	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#SQUID_NTLM_WHITE').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}
function rule_delete(){
	$ID=$_POST["delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM acls_ntlm WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

}
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{whitelist_ntlm}");
	$page=CurrentPageName();
	$html="
	function Start$t(){
	YahooWin2('850','$page?popup=yes','$title')
}

Start$t();";

	echo $html;


}


function rule_save(){
	$ID=intval($_POST["ID"]);
	$item=url_decode_special_tool($_POST["item"]);
	
	if($ID==0){
		$sql="INSERT IGNORE INTO acls_ntlm (item,enabled,`Type`)
		VALUES('$item','1','{$_POST["type"]}')";
	}else{
		$sql="UPDATE acls_ntlm SET
		item='{$item}',
		`Type`='{$_POST["Type"]}',
		WHERE ID=$ID";
}
$q=new mysql_squid_builder();
$q->QUERY_SQL($sql);
if(!$q->ok){echo $q->mysql_error;}

}


function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT item FROM webfilter_quotas WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text("{whitelist_ntlm}:{$ligne["item"]}");
	}else{
		$title=$tpl->javascript_parse_text("{whitelist_ntlm}:{new_rule}");
	}

	$html="YahooWin3('650','$page?rule-popup=yes&ID=$ID','$title')";
	echo $html;
}

function rule_popup(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$fields_size=22;
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$t=time();
	$bt="{add}";
	if($ID>0){$bt="{apply}";}


	



	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM acls_ntlm WHERE ID='$ID'"));
	if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	$title="{rule}: ({$ID}) {$ligne["item"]}";
	$Timez[0]="{by_hour}";
	if(!is_numeric($ligne["quotasize"])){$ligne["quotasize"]=250;}
	if(!is_numeric($ligne["quotaPeriod"])){$ligne["quotaPeriod"]=1;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_rule}");$ligne["enabled"]=1;}
	$q->acl_NTLM[null]="{select}";
	$html[]="<div style='width:98%;font-size:28px;margin-bottom:20px'>$title</div>
	<div style='margin-top:5px;margin-bottom:20px;font-size:18px'>{whitelist_ntlm_explain}</div>
	";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_list_table("type-$t","{type}",$ligne["Type"],$fields_size,$q->acl_NTLM,"ShowHelp$t()",450);
	$html[]=Field_text_table("item-$t","{item}",$ligne["item"],$fields_size,null,450);
	$html[]=Field_checkbox_table("enabled-$t", "{enabled}",$ligne["enabled"],$fields_size,null,"CheckEnabled$t()");
	
	$html[]="<tr><td colspan=2><div id='help-$t'></div></td></tr>";
	$html[]=Field_button_table_autonome($bt,"Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
	<script>
	var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SQUID_NTLM_WHITE').flexReload();
	var ID='$ID';
	if(ID==0){ YahooWin3Hide();return;}
	
}
function CheckEnabled$t(){
	document.getElementById('type-$t').disabled=true;
	document.getElementById('item-$t').disabled=true;
	if(document.getElementById('enabled-$t').checked){
		document.getElementById('type-$t').disabled=false;
		document.getElementById('item-$t').disabled=false;
	}
}


function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('item',encodeURIComponent(document.getElementById('item-$t').value));
	XHR.appendData('type',document.getElementById('type-$t').value);
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled','1');}else{XHR.appendData('enabled','0');}
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}

function ShowHelp$t(){
	LoadAjaxTiny('help-$t','$page?help=yes&type='+document.getElementById('type-$t').value+'&t=$t');
}

CheckEnabled$t();
ShowHelp$t();
</script>

";
echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function show_help(){
	$tpl=new templates();
	$t=$_GET["t"];
	$GroupType=$_GET["type"];
	if($GroupType==null){return;}
	
	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse=button("{browse}...","Loadjs('squid.BrowseItems.php?item-$t&type=ipaddr')");
	}
	if($GroupType=="dst"){$explain="{acl_dst_text}";}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
	if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
	if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
	if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
	if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
	if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
	if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>$explain</div>");
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$item=$tpl->_ENGINE_parse_body("{item}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$compile_rules=$tpl->javascript_parse_text("{compile_rules}");
	$title=$tpl->javascript_parse_text("{whitelist_ntlm}");
	$newrule=$tpl->javascript_parse_text("{new_rule}");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("acls_ntlm")){$q->CheckTables();}

	$compile_bt="{name: '<strong style=font-size:16px;font-weight:bold>$compile_rules</strong>', bclass: 'Reconf', onpress : compile$t},";

	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px;font-weight:bold>$newrule</strong>', bclass: 'add', onpress : NewRule$t},$compile_bt
	],";


	$html="
	<table class='SQUID_NTLM_WHITE' style='display: none' id='SQUID_NTLM_WHITE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_NTLM_WHITE').flexigrid({
	url: '$page?rule-items-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$item', name : 'item', width : 349, sortable : false, align: 'left'},
	{display: '$type', name : 'Type', width : 281, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'enabled', width : 35, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 78, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$item', name : 'item'},
	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

var x_RuleRewriteDeleteItem= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);}
FlexReloadRulesRewriteItems();
if(document.getElementById('tableau-reecriture-regles')){FlexReloadRulesRewrite();}
}

function RuleRewriteDeleteItem(ID){
var XHR = new XHRConnection();
XHR.appendData('rewrite-rule-item-delete', ID);
XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDeleteItem);
}

function RuleRewriteEnableItem(ID,md5){
var XHR = new XHRConnection();
XHR.appendData('rewrite-rule-item-enable', ID);
if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
XHR.sendAndLoad('$page', 'POST',x_RuleRewriteDeleteItem);
}

function NewRule$t(){
Loadjs('$page?rule-js=yes&ID=0');
}

function compile$t(){
Loadjs('squid.whitelist.ntlm.progress.php');
}

function FlexReloadRulesRewriteItems(){
$('#flexRT$t').flexReload();
}



</script>

";
echo $html;

}

function rule_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$search='%';
	$table="acls_ntlm";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if($q->COUNT_ROWS($table)==0){json_error_show("no data"); }
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$item=$ligne["item"];
		$color="black";
		$type=$tpl->javascript_parse_text($q->acl_GroupType[$ligne["Type"]]);
		$js="Loadjs('$MyPage?rule-js=yes&ID={$ligne["ID"]}');";
		$delete=imgsimple("delete-32.png","","Loadjs('$MyPage?delete-js={$ligne["ID"]}')");
		$enable=Field_checkbox($ID,1,$ligne["enabled"],"Loadjs('$MyPage?enable-js={$ligne["ID"]}')");
		if($ligne["enabled"]==0){$color="#9A9A9A";}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
						style='font-size:22px;text-decoration:underline;color:$color'>{$item}</a>",
						"<span style='font-size:22px;color:$color'>$type</span>",
						$enable,$delete )
		);
	}


	echo json_encode($data);

}