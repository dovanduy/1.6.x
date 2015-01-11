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
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["masq-js"])){masq_js();exit;}
if(isset($_GET["masq-tabs"])){masq_tabs();exit;}
if(isset($_GET["masq-popup"])){masq_popup();exit;}
if(isset($_POST["masq-save"])){masq_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}
if(isset($_GET["unlink-group-js"])){unlink_group_js();exit;}
if(isset($_POST["unlink-group"])){unlink_group();exit;}
if(isset($_POST["INOUT"])){INOUT();exit;}



js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$zmd5=$_GET["zmd5"];
	$t=$_GET["tsource"];
	$eth=$_GET["eth"];
	if($zmd5==null){$title=$tpl->javascript_parse_text("{new_role}");}
	if($zmd5<>null){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_roles WHERE zmd5='$zmd5'","artica_backup"));
		$title=$tpl->javascript_parse_text("{shorewall_masq}");
		if($eth==null){$eth=$ligne["nic"];}
	}

	echo "YahooWin5('650','$page?popup=yes&zmd5=$zmd5&tsource=$t&eth=$eth','$title')";
}


function masq_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$t=$_GET["t"];
	$eth=$_GET["eth"];
	if($eth==null){
		$ip=new system_nic($eth);
		$ethText=$ip->NICNAME;
	}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_provider}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_masq WHERE ID='$ID'"));
		$ip=new system_nic($ligne["eth"]);
		$ethText=$ip->NICNAME;
		$ip=new system_nic($ligne["INTERFACE"]);
		$title=$tpl->javascript_parse_text("{interface}:$ethText &raquo; $ip->NICNAME");
	}
	
	echo "YahooWinBrowse('700','$page?masq-tabs=yes&ID=$ID&t=$t&eth=$eth','$title')";
}

function masq_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	$bt_title="{add}";
	$ID=$_GET["ID"];
	$eth=$_GET["eth"];
	if($ID==null){$title=$tpl->javascript_parse_text("{new_network}");}
	
	$net=new networking();
	$ethz=$net->Local_interfaces();
	while (list ($num, $ligne) = each ($ethz) ){
		$ip=new system_nic($num);
		$ethz[$num]="$ip->NICNAME ($num)";
	}	
	
	
	if($ID>0){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_masq WHERE ID='$ID'"));
		$ip=new system_nic($ligne["INTERFACE"]);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["INTERFACE"]);
		$eth=$ligne["eth"];
	}
	

	
	for($i=1;$i<16;$i++){
		$table_number[$i]=$i;
	}
	$ip=new system_nic($eth);
	if($ligne["SOURCE"]==null){$ligne["SOURCE"]=$ip->NETWORK;}
	$t=time();
	$html="
	<div style='font-size:20px;margin-bottom:20px'>$title</div>
	<div style='font-size:16px' class=text-info>{shorewall_masq_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{source_network}:</td>
		<td>". Field_text("SOURCE-$t",$ligne["SOURCE"],"font-size:18px;width:190px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{destination_interface}:</td>
		<td>". Field_array_Hash($ethz, "INTERFACE-$t",$ligne["INTERFACE"],null,null,0,"font-size:16px")."</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:16px'>{translated_address}:</td>
		<td>". Field_text("ADDRESS-$t",$ligne["ADDRESS"],"font-size:18px;width:190px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>
			
	<tr>
		<td colspan=2 align='right'>". button($bt_title,"Save$t()",18)."</td>
	</tr>		
	</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID='$ID';
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID.length==0){YahooWinBrowseHide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('masq-save',  '$ID');
	XHR.appendData('eth',  '$eth');
	XHR.appendData('INTERFACE',  document.getElementById('INTERFACE-$t').value);
	XHR.appendData('SOURCE',  document.getElementById('SOURCE-$t').value);
	XHR.appendData('ADDRESS',  document.getElementById('ADDRESS-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function provider_delete(){
	$q=new mysql_shorewall();
	$q->PROVIDER_DELETE($_POST["provider-delete"]);
	
	
}

function masq_save(){
	$q=new mysql_shorewall();
	$table="fw_masq";
	$q->CheckTables();
	
	
	$editF=false;
	$ID=$_POST["masq-save"];
	unset($_POST["masq-save"]);
	
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



function masq_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_shorewall();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{new_network}");
	
	if($ID>0){
		$title=$tpl->_ENGINE_parse_body("{network2}");
	}
	
	$array["masq-popup"]=$title;
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID&eth={$_GET["eth"]}\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_zone_masq_$ID");
}
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_text=$tpl->javascript_parse_text("{new_network}");
	$title=$tpl->javascript_parse_text("{shorewall_masq}");
	$delete=$tpl->javascript_parse_text("{delete} {provider} ?");
	$INTERFACE=$tpl->javascript_parse_text("{interface}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$ADDRESS=$tpl->javascript_parse_text("{address}");
	$table=$tpl->javascript_parse_text("{table}");
	$SOURCE=$tpl->javascript_parse_text("{source}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$buttons="
	buttons : [
	{name: '$new_text', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";
	
$html="
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
function Start$tt(){
	$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt&eth={$_GET["eth"]}',
		dataType: 'json',
		colModel : [
		
		{display: '$SOURCE', name : 'SOURCE', width :120, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'dcdcd', width : 31, sortable : false, align: 'center'},
		{display: '$INTERFACE', name : 'INTERFACE', width :140, sortable : true, align: 'left'},
		{display: '$ADDRESS', name : '$ADDRESS', width : 120, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$INTERFACE', name : 'INTERFACE'},
	{display: '$SOURCE', name : 'SOURCE'},
	{display: '$ADDRESS', name : 'ADDRESS'},
	
	],
	sortname: 'zOrder',
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

	
function NewRule$tt(){
	Loadjs('$page?masq-js=yes&ID=&t=$tt&eth={$_GET["eth"]}','$new_text');
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('provider-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}

function Apply$tt(){
	Loadjs('shorewall.php?apply-js=yes',true);
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
	$table="fw_masq";
	$page=1;
	$FORCE_FILTER="AND `eth`='{$_GET["eth"]}'";
	$total=0;
	
	if(!$q->FIELD_EXISTS("fw_masq","zOrder")){
		$sql="ALTER TABLE `fw_masq` ADD `zOrder` INT( 3 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,'artica_backup');
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

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$options_text=null;
		$delete=imgsimple("delete-32.png",null,"Delete$t('{$ligne["ID"]}')");
		$ip=new system_nic($ligne["INTERFACE"]);
		$interface="$ip->NICNAME - ".$ligne["INTERFACE"];
		
		$linkprovider="<a href=\"javascript:blur();\"
		style='font-size:{$fontsize}px;text-decoration:underline'
		OnClick=\"javascript:Loadjs('$MyPage?masq-js=yes&eth={$_GET["eth"]}&ID={$ligne["ID"]}&t={$_GET["t"]}');\">";
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkprovider{$ligne["SOURCE"]}</a></span>",
						"<img src='img/arrow-right-24.png'>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$interface</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkInterface{$ligne["ADDRESS"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}