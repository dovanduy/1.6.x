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
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}

if(isset($_GET["enable-js"])){items_enable_js();exit;}
if(isset($_POST["mac-enable"])){items_enable();exit;}


if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_POST["MAC"])){save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$pattern=$tpl->_ENGINE_parse_body("{trusted_MAC}");
	$html="YahooWin4('550','$page?popup=yes','$pattern',true);";
	echo $html;
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$explain=$tpl->javascript_parse_text("{add_macs_popup_explain}");
	
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
	var ports=prompt('$explain');
	if(!ports){return;}
	XHR.appendData('MAC',  ports);
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}			
	
Save$tt();	
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
	$table="hotspot_whitemacs";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM hotspot_whitemacs WHERE `MAC`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function items_enable(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_POST["mac-enable"];
	if(trim($ID)==null){return;}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM hotspot_whitemacs WHERE `MAC`='$ID'"));
	
	if($ligne["enabled"]==1){
		$q->QUERY_SQL("UPDATE hotspot_whitemacs SET `enabled`='0' WHERE `MAC`='$ID'");
	}else{
		$q->QUERY_SQL("UPDATE hotspot_whitemacs SET `enabled`='1' WHERE `MAC`='$ID'");
	}
	
}
function Save(){
	
	$q=new mysql_squid_builder();
	$table="hotspot_whitemacs";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$_POST["MAC"]=str_replace("-", ":", $_POST["MAC"]);
	$ports=$_POST["MAC"];
	if(strpos($ports, ",")>0){
		$defaultsZ=explode(",",$ports);
	}else{
		$defaultsZ[]=$_POST["MAC"];
	}

	
	
	while (list ($none, $port) = each ($defaultsZ) ){
		$sqlZ[]="('$port',1)";
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_whitemacs` (
			`MAC` VARCHAR(90) NOT NULL ,
			`enabled` smallint(1) NOT NULL,
			PRIMARY KEY ( `MAC` ) ,
			INDEX ( `enabled`)
	
			)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	$sql="INSERT IGNORE INTO hotspot_whitemacs (`MAC`,`enabled`) VALUES ".@implode(',', $sqlZ);
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
	$ports=$tpl->javascript_parse_text("{MAC}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$title=$tpl->javascript_parse_text("{trusted_MAC}");
	$new_port=$tpl->javascript_parse_text("{new_mac}");
	$hostpot_MACWHITE_explain=$tpl->_ENGINE_parse_body("{hostpot_MACWHITE_explain}");
	$tt=time();
	$buttons="
	buttons : [
	{name: '$new_port', bclass: 'add', onpress : NewRule$tt},
	

	],";

	$html="
	<div class=text-info style='font-size:14px'>$hostpot_MACWHITE_explain</div>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	
	{display: '$ports', name : 'port', width :263, sortable : true, align: 'left'},
	{display: '$enabled', name : 'enabled', width :100, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 100, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$ports', name : 'MAC'},
	],
	sortname: 'MAC',
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
	$table="hotspot_whitemacs";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
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
		$port=$ligne["MAC"];
		$mpacenc=urlencode($ligne["MAC"]);
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=$mpacenc&t={$_GET["t"]}',true)");
		$enabled=Field_checkbox("en{$ligne["MAC"]}",1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=$mpacenc&t={$_GET["t"]}',true)");
	
		if($ligne["enabled"]==0){$color="#BBBBBB";}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$port</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$enabled</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}