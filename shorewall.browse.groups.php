<?php
// fw_objects
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["remove-group-js"])){remove_group_js();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}
if(isset($_POST["new-group"])){new_group_save();exit;}
if(isset($_POST["remove-group"])){remove_group_perform();exit;}
if(isset($_POST["link-group"])){link_group();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	
	if($_GET["ruleid"]>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM fw_rules WHERE ID='{$_GET["ruleid"]}'"));
		$title=$tpl->javascript_parse_text("{$ligne["rulename"]}$title::{browse}:{groups}");
	}
	
	echo "YahooWin2('550','$page?popup=yes&ruleid={$_GET["ruleid"]}&t={$_GET["t"]}&t-rule={$_GET["t-rule"]}','$title')";
	
}
function remove_group_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["remove-group-js"];
	$page=CurrentPageName();
	$q=new mysql_shorewall();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM fw_objects WHERE ID='$ID'"));
	$remove=$tpl->javascript_parse_text("{delete_group}");
	$t=time();
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
		if(!confirm('$remove \"{$ligne["groupname"]}\" ?') ){return;}
		var XHR = new XHRConnection();
		XHR.appendData('remove-group',  '$ID');
		XHR.sendAndLoad('$page', 'POST',xRemove$t);
		}
	
	 Remove$t();";
	
}

function remove_group_perform(){
	$ID=$_POST["remove-group"];
	$q=new mysql_shorewall();
	$q->GROUP_DELETE($ID);
}


function new_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	$title=$tpl->javascript_parse_text("{new_group}");
	echo "YahooWin3('500','$page?new-group-popup=yes&t={$_GET["t"]}','$title')";
		
	
}
function new_group_popup(){
	$q=new mysql_shorewall();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div style='font-size:22px;margin-bottom:20px'>{new_group}</div>
	<div style='width:98%' class=form>
		<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:18px'>{groupname}:</td>
				<td>". Field_text("groupname-$t",null,"font-size:18px;width:95%",null,null,null,false,"SaveCHK$t(event)")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{type}:</td>
				<td>". Field_array_Hash($q->RULES_POLICIES_GROUP_TYPE,"type-$t","net","style:font-size:18px;width:95%")."</td>
			</tr>						
			<tr>
				<td colspan=2 align='right'><hr>". button("{add}","Save$t();",22)."</td>
			</tr>		
		</table>	
	</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	YahooWin3Hide();
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('new-group',  'yes');
	XHR.appendData('groupname',  encodeURIComponent(document.getElementById('groupname-$t').value));
	XHR.appendData('type',  encodeURIComponent(document.getElementById('type-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}

function new_group_save(){
	$q=new mysql_shorewall();
	$q->CheckTables();
	$_POST["groupname"]=url_decode_special_tool($_POST["groupname"]);
	
	$q->QUERY_SQL("INSERT INTO fw_objects (groupname,grouptype) VALUES ('{$_POST["groupname"]}','{$_POST["type"]}')");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function link_group(){
	$ruleid=$_POST["ruleid"];
	if(!is_numeric($ruleid)){echo "No main rule\n";return;}
	$ID=$_POST["link-group"];
	$q=new mysql_shorewall();
	if(!is_numeric($ID)){echo "No group id\n";return;}
	$q->QUERY_SQL("INSERT INTO fw_objects_lnk (`groupid`,`ruleid`,`INOUT`) VALUES ('$ID','$ruleid',0)");
	if(!$q->ok){echo $q->mysql_error;}
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	
	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$items=$tpl->javascript_parse_text("{items}");
	
	if($_GET["ruleid"]>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM fw_rules WHERE ID='{$_GET["ruleid"]}'"));
		$title=$tpl->javascript_parse_text("{$ligne["rulename"]}$title::{browse}:{groups}");
	}
	
	
	$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : NewRule$tt},
	],";
	
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&ruleid={$_GET["ruleid"]}&t={$_GET["t"]}&t-rule={$_GET["t-rule"]}&tt=$tt',
		dataType: 'json',
		colModel : [
		{display: '$groupname', name : 'groupname', width :200, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width :150, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width :32, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'link', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$groupname', name : 'groupname'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
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
	$('#flexRT$tt').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["t-rule"]}').flexReload();		
}

function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
}

function Link$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('link-group', ID);
	XHR.appendData('ruleid', '{$_GET["ruleid"]}');
	XHR.sendAndLoad('$page', 'POST', xNewRule$tt);
}

	
function NewRule$tt(){
	Loadjs('$page?new-group-js=yes&t=$tt',true);
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('policy-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
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
	$table="fw_objects";
	$page=1;
	$FORCE_FILTER=null;
	$ruleid=$_GET["ruleid"];
	$total=0;
	$FORCE_FILTER=null;
	
	if(!$q->TABLE_EXISTS("fw_objects")){
		
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `fw_objects` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`groupname` VARCHAR( 255 ) NOT NULL,
			`grouptype` VARCHAR(20) NOT NULL,
			 KEY `groupname` (`groupname`),
			 KEY `grouptype` (`grouptype`)
			) ENGINE=MYISAM;");
	
	
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("fw_objects");
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
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?remove-group-js={$ligne["ID"]}&t={$_GET["t"]}&t-rule={$_GET["t-rule"]}&tt={$_GET["tt"]}')");
		
		$link=imgsimple("arrow-right-32.png",null,"Link{$_GET["tt"]}({$ligne["ID"]})");

			$editjs="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('shorewall.groups.items.php?js=yes&groupid={$ligne['ID']}&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";

		$groupname=utf8_encode($ligne["groupname"]);
		$grouptype=$tpl->_ENGINE_parse_body($q->RULES_POLICIES_GROUP_TYPE[$ligne["grouptype"]]);
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM fw_items WHERE groupid='{$ligne['ID']}'"));
		$itemsNum=$ligne2["tcount"];

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$groupname</span>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$grouptype</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$itemsNum</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"
								
						,)
		);
	}


	echo json_encode($data);

}