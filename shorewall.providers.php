<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		


if(isset($_GET["items"])){items();exit;}
if(isset($_GET["provider-js"])){provider_js();exit;}
if(isset($_GET["provider-tabs"])){provider_tabs();exit;}
if(isset($_GET["provider-popup"])){provider_popup();exit;}
if(isset($_POST["provider-save"])){provider_save();exit;}
if(isset($_POST["provider-delete"])){provider_delete();exit;}

table();	

function provider_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$t=$_GET["t"];
	
	if($ID==0){$title=$tpl->javascript_parse_text("{new_provider}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_providers WHERE ID='$ID'"));
		$ip=new system_nic($ligne["INTERFACE"]);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["NAME"]);
	}
	
	echo "YahooWin('700','$page?provider-tabs=yes&ID=$ID&t=$t','$title')";
}

function provider_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	$bt_title="{add}";
	$ID=$_GET["ID"];
	
	if($ID==null){$title=$tpl->javascript_parse_text("{new_provider}");}
	
	$tables["main"]="main";
	$tables["local"]="local";
	$tables["default"]="default";
	$sql="SELECT NAME FROM `fw_providers`";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$tables[$ligne["NAME"]]=$ligne["NAME"];
	}
	
	$net=new networking();
	$ethz=$net->Local_interfaces();
	while (list ($num, $ligne) = each ($ethz) ){
		$ip=new system_nic($num);
		$ethz[$num]="$ip->NICNAME ($num)";
	}	
	
	
	if($ID>0){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_providers WHERE ID='$ID'"));
		$ip=new system_nic($ligne["INTERFACE"]);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["NAME"]);
	}
	

	
	for($i=1;$i<16;$i++){
		$table_number[$i]=$i;
	}
	

	
	if(!is_numeric($ligne["balance"])){$ligne["balance"]=-1;}
	if(!is_numeric($ligne["fallback"])){$ligne["fallback"]=-1;}
	
	$t=time();
	$html="
	<div style='font-size:20px;margin-bottom:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	
	
	<tr>
		<td class=legend style='font-size:16px'>{name}:</td>
		<td>". Field_text("NAME-$t",$ligne["NAME"],"font-size:18px;width:190px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td>". Field_array_Hash($ethz, "INTERFACE-$t",$ligne["INTERFACE"],null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{gateway}:</td>
		<td>". Field_text("GATEWAY-$t",$ligne["GATEWAY"],"font-size:18px;width:190px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{unique_number}:</td>
		<td>". Field_array_Hash($table_number, "NUMBER-$t",$ligne["NUMBER"],null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{balance}:</td>
		<td>". Field_text("balance-$t",$ligne["balance"],"font-size:18px;width:40px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{fallback}:</td>
		<td>". Field_text("fallback-$t",$ligne["fallback"],"font-size:18px;width:40px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>				
	
	<tr>
		<td class=legend style='font-size:16px'>{routing_table}:</td>
		<td>". Field_array_Hash($tables, "DUPLICATE-$t",$ligne["DUPLICATE"],null,null,0,"font-size:16px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{routing_track}:</td>
		<td>". Field_checkbox("track-$t",1,$ligne["track"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{routing_tproxy}:</td>
		<td>". Field_checkbox("tproxy-$t",1,$ligne["tproxy"])."</td>
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
	if(ID.length==0){YahooWinHide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('provider-save',  '$ID');
	XHR.appendData('NAME',  document.getElementById('NAME-$t').value);
	XHR.appendData('INTERFACE',  document.getElementById('INTERFACE-$t').value);
	XHR.appendData('DUPLICATE',  document.getElementById('DUPLICATE-$t').value);
	XHR.appendData('GATEWAY',  document.getElementById('GATEWAY-$t').value);
	XHR.appendData('NUMBER',  document.getElementById('NUMBER-$t').value);
	
	XHR.appendData('fallback',  document.getElementById('fallback-$t').value);
	XHR.appendData('balance',  document.getElementById('balance-$t').value);
	
	if(document.getElementById('track-$t').checked){ XHR.appendData('track',  1); }else{ XHR.appendData('track',  0); }
	if(document.getElementById('tproxy-$t').checked){ XHR.appendData('tproxy',  1); }else{ XHR.appendData('tproxy',  0); }	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function provider_delete(){
	$q=new mysql_shorewall();
	$q->PROVIDER_DELETE($_POST["provider-delete"]);
	
	
}

function provider_save(){
	$q=new mysql_shorewall();
	$table="fw_providers";
	$q->CheckTables();
	
	
	$editF=false;
	$ID=$_POST["provider-save"];
	unset($_POST["provider-save"]);
	
	$_POST["NAME"]=trim($_POST["NAME"]);
	$_POST["NAME"]=str_replace(" ", "", $_POST["NAME"]);
	$_POST["NAME"]=strtoupper(replace_accents($_POST["NAME"]));
	$_POST["NAME"]=substr($_POST["NAME"], 0,16);
	
	
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



function provider_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_shorewall();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{new_provider}");
	
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT NAME FROM fw_providers WHERE ID='$ID'"));
		$title=$ligne["NAME"];
	}
	if($title==null){$title=$tpl->_ENGINE_parse_body("{provider}");}
	$array["provider-popup"]=$title;
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_zone_provider_$ID");
}
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_text=$tpl->javascript_parse_text("{new_provider}");
	$providers=$tpl->javascript_parse_text("{providers}");
	$delete=$tpl->javascript_parse_text("{delete} {provider} ?");
	$interface=$tpl->javascript_parse_text("{interface}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$table=$tpl->javascript_parse_text("{table}");
	$gateway=$tpl->javascript_parse_text("{gateway}");
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
		url: '$page?items=yes&t=$tt&tt=$tt&ruleid={$_GET["ID"]}',
		dataType: 'json',
		colModel : [
		{display: '$providers', name : 'NAME', width :401, sortable : true, align: 'left'},
		{display: '$table', name : 'DUPLICATE', width :67, sortable : true, align: 'left'},
		{display: '$interface', name : 'INTERFACE', width : 147, sortable : true, align: 'left'},
		{display: '$gateway', name : 'GATEWAY', width : 130, sortable : true, align: 'left'},
		
		
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$providers', name : 'NAME'},
	{display: '$interface', name : 'INTERFACE'},
	{display: '$gateway', name : 'GATEWAY'},
	
	],
	sortname: 'NAME',
	sortorder: 'asc',
	usepager: true,
	title: '$providers',
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
	Loadjs('$page?provider-js=yes&ID=&t=$tt','$new_text');
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
	$table="fw_providers";
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
		$linkInterface=$q->JS_INTERFACE($ligne["INTERFACE"]);
		$linkprovider=$q->JS_PROVIDER($ligne["ID"]);
		$options=array();
		
		if($ligne["track"]==1){$options[]=$tpl->_ENGINE_parse_body("{routing_track}");}
		if($ligne["tproxy"]==1){$options[]=$tpl->_ENGINE_parse_body("{routing_tproxy}");}
		if($ligne["fallback"]>-1){$options[]=$tpl->_ENGINE_parse_body("{fallback}");}
		if($ligne["balance"]>-1){$options[]=$tpl->_ENGINE_parse_body("{balance}");}
		
		if(count($options)>0){
			$options_text="<br><i style='font-size:12px'>".@implode(", ", $options)."</i>";
		}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkprovider{$ligne["NAME"]}</a></span>$options_text",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkprovider{$ligne["DUPLICATE"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkInterface$interface</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkprovider{$ligne["GATEWAY"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}

?>	