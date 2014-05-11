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


if(isset($_GET["items"])){items();exit;}
if(isset($_GET["zone-js"])){zone_js();exit;}
if(isset($_GET["zone-tabs"])){zone_tabs();exit;}
if(isset($_GET["zone-popup"])){zone_popup();exit;}
if(isset($_POST["zone-save"])){zone_save();exit;}
if(isset($_POST["zone-delete"])){zone_delete();exit;}

table();	

function zone_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_netzone}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zone FROM fw_zones WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["zone"]);
	}
	
	echo "YahooWin('700','$page?zone-tabs=yes&ID=$ID&t=$t','$title')";
}

function zone_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	
	$sql="SELECT eth FROM `fw_interfaces`";
	$results = $q->QUERY_SQL($sql);
	
	
	$INTERFACES[null]="{none}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$nic=new system_nic($ligne["eth"]);
		$INTERFACES[$ligne["eth"]]=$nic->NICNAME;
	}
	
	
	
	$bt_title="{add}";
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_netzone}");}
	if($ID>0){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_zones WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["zone"]);
	}
	
	
	
	$t=time();
	$html="
	<div style='font-size:30px;margin-bottom:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{name}:</td>
		<td>". Field_text("zone-$t",$ligne["zone"],"font-size:16px;width:250px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td>". Field_array_Hash($INTERFACES, "eth-$t",$ligne["eth"],null,null,0,"font-size:16px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td>". Field_array_Hash($q->zones_type, "type-$t",$ligne["type"],null,null,0,"font-size:16px")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:16px'>{comment}:</td>
		<td>". Field_text("comment-$t",$ligne["comment"],"font-size:16px;width:250px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>". button($bt_title,"Save$t()",18)."</td>
	</tr>		
	</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID=$ID;
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWinHide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('zone-save',  '$ID');
	XHR.appendData('eth',  encodeURIComponent(document.getElementById('eth-$t').value));
	XHR.appendData('zone',  encodeURIComponent(document.getElementById('zone-$t').value));
	XHR.appendData('type',  encodeURIComponent(document.getElementById('type-$t').value));
	XHR.appendData('comment',  encodeURIComponent(document.getElementById('comment-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function zone_delete(){
	$q=new mysql_shorewall();
	$q->ZONE_DELETE($_POST["zone-delete"]);
	
	
}

function zone_save(){
	$q=new mysql_shorewall();
	
	
	
	if(!$q->FIELD_EXISTS("fw_zones", "eth")){
		$q->QUERY_SQL("ALTER TABLE `fw_zones` ADD `eth` varchar(20) NULL, ADD INDEX (`eth`)");

		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if(!$q->FIELD_EXISTS("fw_zones", "zOrder")){
		$q->QUERY_SQL("ALTER TABLE `fw_zones` ADD `zOrder` INT(3) NULL, ADD INDEX (`zOrder`)");

		if(!$q->ok){echo $q->mysql_error;return;}
	}	
	
	$_POST["zone"]=trim($_POST["zone"]);
	if(strlen($_POST["zone"])>5){
		echo "Network zone {$_POST["netzone"]} at most 5 characters long\n";
		return;
	}
	
	if(is_numeric(substr($_POST["zone"], 0,1))){
		echo "Network Zone Must start with a letter\n";
		return;
	}
	
	if($q->ZONES_RESERVED_WORDS[$_POST["zone"]]){
		echo "Network zone '{$_POST["zone"]}' is a reserved word\n";
		return;
	}
	

	
	$table="fw_zones";
	$editF=false;
	$ID=$_POST["zone-save"];
	unset($_POST["zone-save"]);
	
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	$sql_edit="UPDATE `$table` SET ".@implode(",", $edit)." WHERE ID='$ID'";
	$sql="INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	if($ID>0){$sql=$sql_edit;}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "Mysql error: `$q->mysql_error`";;return;}
	$tpl=new templates();
	$tpl->javascript_parse_text("{success}");
	
}


function zone_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==0){$title=$tpl->javascript_parse_text("{new_netzone}");}
	
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zone FROM fw_zones WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["zone"]);
	}
	$array["zone-popup"]=$title;
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_zone_tab_$ID");
}
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_zone=$tpl->javascript_parse_text("{new_netzone}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$zones=$tpl->javascript_parse_text("{netzones}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$buttons="
	buttons : [
	{name: '$new_zone', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";
	
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt&ruleid={$_GET["ID"]}',
		dataType: 'json',
		colModel : [
		
		{display: '$zone', name : 'zone', width :169, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width :84, sortable : true, align: 'left'},
		{display: '$comment', name : 'comment', width : 508, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$zone', name : 'zone'},
	{display: '$type', name : 'type'},
	{display: '$comment', name : 'comment'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$zones',
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

	
function NewRule$tt(){
	Loadjs('$page?zone-js=yes&ID=0&t=$tt','$new_zone');
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('zone-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}
	
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
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
	$table="fw_zones";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	

	if($q->COUNT_ROWS($table)==0){$q->CheckTables();}
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

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$fontsize="16";
	if($searchstring==null){
		$shorewall_firewall=$tpl->_ENGINE_parse_body("{shorewall_firewall}");
		$data['total']++;
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;'>fw</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;'>firewall</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;'>$shorewall_firewall</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;'></span>",)
		);
		
	}
	
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$NICNAME=null;
		if($ligne['ID']>0){
			$delete=imgsimple("delete-32.png",null,"Delete$t({$ligne["ID"]})");
		}
		
		
		
		$editjs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?zone-js=yes&ID={$ligne['ID']}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		if(trim($ligne["eth"])<>null){
			$nic=new system_nic($ligne["eth"]);
			
			$jsinterface="javascript:Loadjs('shorewall.interfaces.php?interface-js=yes&ID={$ligne["eth"]}&t=$t');";
			
			if($nic->NICNAME<>null){$NICNAME="<i><a href=\"javascript:blur();\" OnClick=\"$jsinterface\"
			style='text-decoration:underline'>$nic->NICNAME</a></i>, ";}
		}
		
		if(isset($q->ZONES_RESERVED_WORDS[$ligne["zone"]])){$delete=null;$editjs=null;}

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs{$ligne["zone"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["type"]}</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$NICNAME{$ligne["comment"]}</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}

?>	