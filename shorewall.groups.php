<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		


if(isset($_GET["items"])){items();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_GET["unlink-group-js"])){unlink_group_js();exit;}
if(isset($_POST["unlink-group"])){unlink_group();exit;}
if(isset($_POST["INOUT"])){INOUT();exit;}
if(isset($_POST["reverse"])){reverse();exit;}


table();


function unlink_group_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_shorewall();
	$linkid=$_GET["linkid"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM fw_objects WHERE ID='{$_GET["groupid"]}'"));
	$t=$_GET["t"];
	$tt=time();
	$ask=$tpl->javascript_parse_text("{unlink_group} {$ligne["groupname"]} ?");
	$html="
			
	var xunlink$tt= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#flexRT$t').flexReload();
		ExecuteByClassName('SearchFunction');
	}
	
	function unlink$tt(){
		if(!confirm('$ask')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('unlink-group',  '$linkid');
		XHR.sendAndLoad('$page', 'POST',xunlink$tt);
	}
			
	unlink$tt();		
			
	";
	
	echo $html;
	
}

function unlink_group(){
	$ID=$_POST["unlink-group"];
	$q=new mysql_shorewall();
	$q->QUERY_SQL("DELETE FROM fw_objects_lnk WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function INOUT(){
	$ID=$_POST["INOUT"];
	$q=new mysql_shorewall();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `INOUT` FROM fw_objects_lnk WHERE ID='$ID'"));
	if($ligne["INOUT"]==0){$INOUT=1;}
	if($ligne["INOUT"]==1){$INOUT=0;}
	$q->QUERY_SQL("UPDATE fw_objects_lnk SET `INOUT`='$INOUT' WHERE ID='$ID'");
}
function reverse(){
	$ID=$_POST["reverse"];
	$q=new mysql_shorewall();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `reverse` FROM fw_objects_lnk WHERE ID='$ID'"));
	if($ligne["reverse"]==0){$INOUT=1;}
	if($ligne["reverse"]==1){$INOUT=0;}
	$q->QUERY_SQL("UPDATE fw_objects_lnk SET `reverse`='$INOUT' WHERE ID='$ID'");
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
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$items=$tpl->javascript_parse_text("{items}");
	
	
	$q=new mysql_shorewall();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM `fw_rules` WHERE ID='{$_GET["ruleid"]}'"));
	$title=$tpl->javascript_parse_text($ligne["rulename"]);
	
	
	
	$buttons="
	buttons : [
	{name: '$linkgroup', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},

	],";

	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	
	{display: '$groupname', name : 'groupname', width :357, sortable : true, align: 'left'},
	{display: '$items', name : 'items', width :60, sortable : false, align: 'center'},
	{display: 'IN', name : 'INOUT', width :35, sortable : false, align: 'center'},
	{display: 'OUT', name : 'INOUT', width :35, sortable : false, align: 'center'},
	{display: 'REV', name : 'reverse', width :35, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title::$groups',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var xNewRule$tt= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
$('#flexRT$tt').flexReload();
}

function Apply$tt(){
Loadjs('shorewall.php?apply-js=yes',true);
}


function NewRule$tt(){
	Loadjs('shorewall.browse.groups.php?ruleid={$_GET["ruleid"]}&t=$tt&t-rule={$_GET["t"]}',true);
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('policy-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}

var xINOUT$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}


function INOUT$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('INOUT', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}

function reverse$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('reverse', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
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
	$table="(SELECT fw_objects.groupname,fw_objects.grouptype,
			fw_objects_lnk.`ID` as linkid,
			fw_objects_lnk.`ruleid`,
			fw_objects_lnk.`reverse`,
			`fw_objects_lnk`.`groupid`,
			fw_objects_lnk.`INOUT` FROM `fw_objects_lnk`,`fw_objects`
			WHERE `fw_objects_lnk`.`groupid`=`fw_objects`.`ID`) as t";
	
	
	$page=1;
	$FORCE_FILTER=null;
	$ruleid=$_GET["ruleid"];
	$total=0;
	$FORCE_FILTER="AND `ruleid`=$ruleid";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("fw_objects_lnk");
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
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?unlink-group-js=yes&linkid={$ligne["linkid"]}&groupid={$ligne["groupid"]}&t={$_GET["t"]}&ruleid=$ruleid')");

		$editjs="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('shorewall.groups.items.php?js=yes&groupid={$ligne['groupid']}&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";

		$grouptype=$tpl->_ENGINE_parse_body($q->RULES_POLICIES_GROUP_TYPE[$ligne["grouptype"]]);

		$groupname=utf8_encode($ligne["groupname"]);
		$ID=$ligne["ID"];
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM fw_items WHERE groupid='{$ligne['groupid']}'"));
		$itemsNum=$ligne2["tcount"];
		
		if($ligne["INOUT"]==0){
			$in=Field_checkbox("INOUT", 1,1,"INOUT$t({$ligne["linkid"]})");
			$out=Field_checkbox("INOUT", 1,0,"INOUT$t({$ligne["linkid"]})");
		}else{
			$in=Field_checkbox("INOUT", 1,0,"INOUT$t({$ligne["linkid"]})");
			$out=Field_checkbox("INOUT", 1,1,"INOUT$t({$ligne["linkid"]})");			
		}
		
		$rev=Field_checkbox("reverse", 1,$ligne["reverse"],"reverse$t({$ligne["linkid"]})");	
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$groupname</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$itemsNum</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$in</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$out</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$rev</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}