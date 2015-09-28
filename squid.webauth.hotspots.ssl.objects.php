<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}	
if(isset($_GET["popup"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}

if(isset($_GET["enable-js"])){items_enable_js();exit;}
if(isset($_POST["mac-enable"])){items_enable();exit;}


if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_POST["gpid"])){save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$pattern=$tpl->_ENGINE_parse_body("{trusted_ssl_sites}");
	$html="YahooWin4('550','$page?popup=yes','$pattern',true);";
	echo $html;
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	
	
	$t=$_GET["t"];
	$tt=time();
	
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}

	

function Save$tt(gpid){
	var XHR = new XHRConnection();
	XHR.appendData('gpid',  gpid);
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}	

Loadjs('squid.BrowseAclGroups.php?callback=Save$tt&t={$_GET["t"]}&FilterType=FW-OUT');
";
echo $html;

}

function items_enable_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["enable-js"];
	
	$t=$_GET["t"];
	$tt=time();
	
	$html="
	var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	
	
	
	function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('mac-enable',  '$linkid');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
	
	Save$tt();
	";
	echo $html;
	
}

function items_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["delete-js"];
	
	$t=$_GET["t"];
	$tt=time();
	
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}

	

function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('delete',  '$linkid');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}			
	
Save$tt();	
	";
	echo $html;

}

function items_delete(){
	$q=new mysql_squid_builder();
	$table="hotspot_sslwhitelists";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM hotspot_sslwhitelists WHERE `objectid`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function items_enable(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_POST["mac-enable"];
	if(trim($ID)==null){return;}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM hotspot_sslwhitelists WHERE `objectid`='$ID'"));
	
	if($ligne["enabled"]==1){
		$q->QUERY_SQL("UPDATE hotspot_sslwhitelists SET `enabled`='0' WHERE `objectid`='$ID'");
	}else{
		$q->QUERY_SQL("UPDATE hotspot_sslwhitelists SET `enabled`='1' WHERE `objectid`='$ID'");
	}
	
}
function Save(){
	
	$q=new mysql_squid_builder();
	$table="hotspot_sslwhitelists";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `hotspot_sslwhitelists` (
				`objectid` INT UNSIGNED NOT NULL  PRIMARY KEY,
				`enabled` smallint(1) NOT NULL DEFAULT 1,
				KEY `enabled` (`enabled`)
			 )  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	
	$sql="INSERT IGNORE INTO hotspot_sslwhitelists (`objectid`,`enabled`) VALUES ('{$_POST["gpid"]}','1')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "Mysql error: `$q->mysql_error`\n\n$sql";;return;}
	$tpl=new templates();
	

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
	$new_network=$tpl->javascript_parse_text("{new_network}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$title=$tpl->javascript_parse_text("{trusted_ssl_sites}");
	$new_port=$tpl->javascript_parse_text("{new_proxy_object}");
	$hostpot_MACWHITE_explain=$tpl->_ENGINE_parse_body("{trusted_ssl_sites_explain}");
	$tt=time();
	$buttons="
	buttons : [
	{name: '$new_port', bclass: 'add', onpress : NewRule$tt},
	

	],";

	$html="
	<div class=explain style='font-size:14px'>$hostpot_MACWHITE_explain</div>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	
	{display: '$groups', name : 'groupname', width :263, sortable : true, align: 'left'},
	{display: '$enabled', name : 'enabled', width :100, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 100, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$groups', name : 'gpid'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
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
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
	Loadjs('system.services.cmd.php?APPNAME=HOTSPOT_FW&action=restart&cmd=%2Fetc%2Finit.d%2Fartica-hotfw&appcode=HOTSPOT_FW');
}


function NewRule$tt(){
	Loadjs('$page?item-js=yes&ID=0&t=$tt',true);
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
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="hotspot_sslwhitelists";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	$table="(SELECT hotspot_sslwhitelists.objectid,webfilters_sqgroups.GroupName as groupname,hotspot_sslwhitelists.enabled FROM webfilters_sqgroups,hotspot_sslwhitelists
			WHERE webfilters_sqgroups.ID=hotspot_sslwhitelists.objectid) as t";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show($q->mysql_error);}
	$total = $ligne["TCOUNT"];

	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$no_rule=$tpl->_ENGINE_parse_body("{no_item}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$fontsize="18";
	$color="black";
	$check32="<img src='img/check-32.png'>";

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	

	$fontsize="18";
	$check32="<img src='img/check-32.png'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$hostspoted="&nbsp;";
		$restrict_web="&nbsp;";
		$gpid=$ligne["objectid"];
		$mpacenc=urlencode($ligne["MAC"]);
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=$gpid&t={$_GET["t"]}',true)");
		$enabled=Field_checkbox("en{$ligne["MAC"]}",1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=$gpid&t={$_GET["t"]}',true)");
		$ligne["groupname"]=utf8_encode($ligne["groupname"]);
		
		$url= "<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-acls-t=0');\"
		style=\"font-size:{$fontsize}px;text-decoration:underline\">
		{$ligne['groupname']}</a>";
		
		if($ligne["enabled"]==0){$color="#BBBBBB";}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$url</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$enabled</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}
