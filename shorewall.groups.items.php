<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		

if(isset($_GET["popup"])){table();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_GET["delete-item-js"])){delete_item_js();exit;}
if(isset($_POST["delete-item"])){delete_item();exit;}
if(isset($_POST["add"])){items_add();exit;}
js();



function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_shorewall();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM fw_objects WHERE ID='{$_GET["groupid"]}'"));
	$groupname=$tpl->javascript_parse_text("{$ligne["groupname"]}");
	$html="YahooWin4('500','$page?popup=yes&groupid={$_GET["groupid"]}&t={$_GET["t"]}','$groupname');";
	echo $html;
	
}

function delete_item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_shorewall();	
	$remove=$tpl->javascript_parse_text("{delete_item}");
	$t=time();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM fw_objects WHERE ID='{$_GET["groupid"]}'"));
	$groupname=$tpl->javascript_parse_text("{$ligne["groupname"]}");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT item FROM fw_items WHERE ID='{$_GET["ID"]}'"));
	$title=$tpl->javascript_parse_text("{$ligne["item"]}/$groupname");
	
	echo "
	var xRemove$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	$('#flexRT{$_GET["t-rule"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	function Remove$t(){
	if(!confirm('$remove $title ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',  '{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xRemove$t);
	}
	
	Remove$t();";	
	
}

function delete_item(){
	$ID=$_POST["delete-item"];
	$q=new mysql_shorewall();
	$q->QUERY_SQL("DELETE FROM fw_items WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function items_add(){
	$data=$_POST["add"];
	if(strpos($data, ",")>0){
		$items=explode(",",$data);
	}else{
		$items[]=$data;
	}
	while (list ($num, $ligne) = each ($items) ){
		if(trim($ligne)==null){continue;}
		$f[]="('{$_POST["groupid"]}','$ligne')";
	}
	
	if(count($f)>0){
		$q=new mysql_shorewall();
		$q->QUERY_SQL("INSERT INTO fw_items (groupid,item) VALUES ".@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error;}
	}
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$_GET["ruleid"]=$_GET["ID"];
	$groups=$tpl->javascript_parse_text("{groups}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$linkgroup=$tpl->javascript_parse_text("{link_group}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$newitem=$tpl->javascript_parse_text("{new_item}");
	$items=$tpl->javascript_parse_text("{items}");
	
	
	$q=new mysql_shorewall();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,grouptype FROM fw_objects WHERE ID='{$_GET["groupid"]}'"));
	$title=$tpl->javascript_parse_text("{items}: \"{$ligne["groupname"]}\"");
	
	$grouptype=$q->RULES_POLICIES_GROUP_TYPE[$ligne["grouptype"]];
	$grouptype=$tpl->javascript_parse_text($grouptype);
	
	if($ligne["grouptype"]=="net"){
		$gpexpl=$tpl->javascript_parse_text("{SHOWREWALL_NET_EXPLAIN}",1);
	}
	if($ligne["grouptype"]=="port"){
		$gpexpl=$tpl->javascript_parse_text("{SHOWREWALL_PORT_EXPLAIN}",1);
	}
	if($ligne["grouptype"]=="mac"){
		$gpexpl=$tpl->javascript_parse_text("{MAC}",1);
	}
	
	
	
	
	$buttons="
	buttons : [
	{name: '$newitem', bclass: 'add', onpress : NewRule$tt},
	

	],";

	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&groupid={$_GET["groupid"]}&t-table={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '$items', name : 'item', width :402, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$items', name : 'item'},
	],
	sortname: 'item',
	sortorder: 'asc',
	usepager: true,
	title: '$title $grouptype',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
}


function NewRule$tt(){
	var data=prompt('$gpexpl');
	if(!data){return;}
	var XHR = new XHRConnection();
	XHR.appendData('add', data);
	XHR.appendData('groupid', '{$_GET["groupid"]}');
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);	
}
function Delete$tt(zmd5){
if(confirm('$delete')){
var XHR = new XHRConnection();
XHR.appendData('policy-delete', zmd5);
XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
}

var xRuleEnable$t= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
$('#flexRT$tt').flexReload();
}


function RuleEnable$tt(ID,md5){
var XHR = new XHRConnection();
XHR.appendData('rule-enable', ID);
if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
}
var x_LinkAclRuleGpid$tt= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#table-$t').flexReload();
$('#flexRT$tt').flexReload();
ExecuteByClassName('SearchFunction');
}
function FlexReloadRulesRewrite(){
$('#flexRT$t').flexReload();
}

function MoveRuleDestination$tt(mkey,direction){
var XHR = new XHRConnection();
XHR.appendData('rules-destination-move', mkey);
XHR.appendData('direction', direction);
XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}

function MoveRuleDestinationAsk$tt(mkey,def){
var zorder=prompt('Order',def);
if(!zorder){return;}
var XHR = new XHRConnection();
XHR.appendData('rules-destination-move', mkey);
XHR.appendData('rules-destination-zorder', zorder);
XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
Start$tt();

</script>
";
echo $html;

}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_shorewall();

	$t=$_GET["t"];
	$search='%';
	$table="fw_items";
	
	
	$page=1;
	$FORCE_FILTER=null;
	$groupid=$_GET["groupid"];
	$total=0;
	$FORCE_FILTER="AND `groupid`=$groupid";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("fw_items");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$NICNAME=null;
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?delete-item-js=yes&ID={$ligne["ID"]}&groupid={$ligne["groupid"]}&t={$_GET["t"]}')");
		$fw_items=utf8_encode($ligne["item"]);
		$ID=$ligne["ID"];
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM fw_items WHERE groupid='{$ligne['groupid']}'"));
		$itemsNum=$ligne2["tcount"];

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$fw_items</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}