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
if(isset($_GET["policy-js"])){policy_js();exit;}
if(isset($_GET["policy-tabs"])){policy_tabs();exit;}
if(isset($_GET["policy-popup"])){policy_popup();exit;}
if(isset($_POST["policy-save"])){policy_save();exit;}
if(isset($_POST["policy-delete"])){zone_delete();exit;}

table();	

function policy_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_policy}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name FROM zones_policies WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["policy_name"]);
	}
	
	echo "YahooWin('700','$page?policy-tabs=yes&ID=$ID&t=$t','$title')";
}

function policy_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	
	$sql="SELECT ID,zone,type,eth FROM `fw_zones`";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {
		$fw_zones[$ligne["ID"]]="{$ligne["zone"]}:{$ligne["eth"]} - {$ligne["type"]}";
	}
	
	
	
	$bt_title="{add}";
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_policy}");}
	if($ID>0){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM zones_policies WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["policy_name"]);
	}

	
	
	
	$t=time();
	$html="
	<div style='font-size:30px;margin-bottom:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{name}:</td>
		<td>". Field_text("policy_name-$t",$ligne["policy_name"],"font-size:16px;width:250px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{from}:</td>
		<td>". Field_array_Hash($fw_zones, "zone_id_from-$t",$ligne["zone_id_from"],null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{to}:</td>
		<td>". Field_array_Hash($fw_zones, "zone_id_to-$t",$ligne["zone_id_to"],null,null,0,"font-size:16px")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:16px'>{policy}:</td>
		<td>". Field_array_Hash($q->POLICIES, "policy-$t",$ligne["policy"],null,null,0,"font-size:16px")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:16px'>{write_events}:</td>
		<td>". Field_checkbox("log-$t",1,$ligne["log"])."</td>
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
	XHR.appendData('policy-save',  '$ID');
	XHR.appendData('zone_id_from',  encodeURIComponent(document.getElementById('zone_id_from-$t').value));
	XHR.appendData('zone_id_to',  encodeURIComponent(document.getElementById('zone_id_to-$t').value));
	XHR.appendData('policy',  encodeURIComponent(document.getElementById('policy-$t').value));
	XHR.appendData('log',  encodeURIComponent(document.getElementById('log-$t').value));
	XHR.appendData('policy_name',  encodeURIComponent(document.getElementById('policy_name-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function policy_delete(){
	$q=new mysql_shorewall();
	$q->POLICY_DELETE($_POST["policy-delete"]);
}

function policy_save(){
	$q=new mysql_shorewall();
	
	
	$table="zones_policies";
	$editF=false;
	$ID=$_POST["policy-save"];
	unset($_POST["policy-save"]);
	
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


function policy_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==0){$title=$tpl->javascript_parse_text("{new_policy}");}
	
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name FROM zones_policies WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["policy_name"]);
	}
	$array["policy-popup"]=$title;
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_policy_tab_$ID");
}
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_policy=$tpl->javascript_parse_text("{new_policy}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$policy=$tpl->javascript_parse_text("{policy}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$buttons="
	buttons : [
	{name: '$new_policy', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	
	],";
	
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt',
		dataType: 'json',
		colModel : [
		{display: '$policy', name : 'policy_name', width :200, sortable : true, align: 'left'},
		{display: '$from', name : 'zone_from', width :90, sortable : true, align: 'left'},
		{display: '$to', name : 'zone_to', width :90, sortable : true, align: 'left'},
		{display: '$rule', name : 'policy', width : 130, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$policy', name : 'policy_name'},
	{display: '$from', name : 'zone_from'},
	{display: '$to', name : 'zone_to'},
	{display: '$comment', name : 'comment'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$policies',
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
	Loadjs('$page?policy-js=yes&ID=0&t=$tt',true);
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
	$table="( SELECT fw_zones1.zone as zone_from,fw_zones1.ID as ID1, 
			fw_zones2.zone as zone_to,fw_zones2.ID as ID2, zones_policies.* 
			FROM fw_zones as fw_zones1,fw_zones as fw_zones2,zones_policies 
			WHERE fw_zones1.ID=zones_policies.zone_id_from AND fw_zones2.ID=zones_policies.zone_id_to ) as t";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("zones_policies");
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
		$delete=imgsimple("delete-32.png",null,"Delete$t({$ligne["ID"]})");
		
		$editjs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?policy-js=yes&ID={$ligne['ID']}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		$policy=$tpl->javascript_parse_text($q->POLICIES[$ligne["policy"]]);


		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs{$ligne["policy_name"]}</a></span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["zone_from"]}</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["zone_to"]}</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$policy</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}

?>	