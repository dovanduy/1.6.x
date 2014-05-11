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
if(isset($_GET["popup"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}
if(isset($_GET["enable-js"])){items_enable_js();exit;}


if(isset($_POST["enable"])){items_enable();exit;}
if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_POST["ports"])){save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$pattern=$tpl->_ENGINE_parse_body("{restricted_ports}");
	$html="YahooWin4('550','$page?popup=yes','$pattern',true);";
	echo $html;
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$explain=$tpl->javascript_parse_text("{add_ports_explain}");
	
	$t=$_GET["t"];
	$tt=time();
	$pattern=$tpl->javascript_parse_text("{delete} {network}: {$ligne["pattern"]} ?");
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
	XHR.appendData('ports',  ports);
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
	XHR.appendData('enable',  '$linkid');
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
	$table="hotspot_blckports";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM hotspot_blckports WHERE `port`={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?restart-firewall=yes");
}

function items_enable(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_POST["enable"];
	if($ID==0){return;}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM hotspot_blckports WHERE `port`='$ID'"));
	
	if($ligne["enabled"]==1){
		$q->QUERY_SQL("UPDATE hotspot_blckports SET `enabled`=0 WHERE `port`='$ID'");
	}else{
		$q->QUERY_SQL("UPDATE hotspot_blckports SET `enabled`=1 WHERE `port`='$ID'");
	}
	
}
function Save(){
	
	$q=new mysql_squid_builder();
	$table="hotspot_blckports";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$_POST["ports"]=str_replace(".", ",", $_POST["ports"]);
	$ports=$_POST["ports"];
	if(strpos($ports, ",")>0){
		$defaultsZ=explode(",",$ports);
	}else{
		$defaultsZ[]=$_POST["ports"];
	}

	
	
	while (list ($none, $port) = each ($defaultsZ) ){
		$sqlZ[]="($port,1)";
	}
	
	$sql="INSERT IGNORE INTO hotspot_blckports (`port`,`enabled`) VALUES ".@implode(',', $sqlZ);
	$q->QUERY_SQL($sql);
	
	

	if(!$q->ok){echo "Mysql error: `$q->mysql_error`\n\n$sql";;return;}
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?restart-firewall=yes");

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
	$new_network=$tpl->javascript_parse_text("{new_network}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$ports=$tpl->javascript_parse_text("{ports}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$title=$tpl->javascript_parse_text("{restricted_ports}");
	$new_port=$tpl->javascript_parse_text("{new_port}");
	$hostpot_blackports_explain=$tpl->_ENGINE_parse_body("{hostpot_blackports_explain}");
	$tt=time();
	$buttons="
	buttons : [
	{name: '$new_port', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},

	],";

	$html="
	<div class=explain style='font-size:14px'>$hostpot_blackports_explain</div>
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
	{display: '$ports', name : 'port'},
	],
	sortname: 'port',
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
	$defaults="22,23,21,24,25,70,81,82,83,3128,88,109,110,113,119,123,143,144,150,194,201,202,203,204,205,206,207,208,209,210,220,389,3306,563,631,873,993,995,1080,1194,1863,3389,5060,5222,5223,5269,5280,5432,5900,5984,6667,6666,6697,7000,8008,8098,9000,9009,25565,49300,8080,9090,3140,3147";
	$t=$_GET["t"];
	$search='%';
	$table="hotspot_blckports";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_blckports` ( `port` BIGINT UNSIGNED , `enabled` smallint(1) NOT NULL, PRIMARY KEY ( `port` ) , INDEX ( `enabled`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		$defaultsZ=explode(",",$defaults);
		while (list ($none, $ports) = each ($defaultsZ) ){	
			$sqlZ[]="($ports,1)";
		}
		
		$sql="INSERT IGNORE INTO hotspot_blckports (`port`,`enabled`) VALUES ".@implode(',', $sqlZ);
		$q->QUERY_SQL($sql);
	}


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
		$port=$ligne["port"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["port"]}&t={$_GET["t"]}',true)");
		$enabled=Field_checkbox("en{$ligne["port"]}",1,$ligne["enabled"],"Loadjs('$MyPage?enable-js={$ligne["port"]}&t={$_GET["t"]}',true)");
	
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