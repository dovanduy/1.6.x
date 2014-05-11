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
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}

table();	

function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_rule}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM fw_rules WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["rulename"]);
	}
	
	echo "YahooWin('700','$page?rule-tabs=yes&ID=$ID&t=$t','$title')";
}

function rule_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	
	$sql="SELECT * FROM `fw_zones`";
	$results = $q->QUERY_SQL($sql);
	$fw_zones[0]="{all}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$fw_zones[$ligne["ID"]]="{$ligne["zone"]} - {$ligne["type"]}";
	}
	
	
	
	
	
	$bt_title="{add}";
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("{new_rule}");}
	if($ID>0){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		if(!$q->FIELD_EXISTS("fw_rules", "RATELIM")){
			$q->QUERY_SQL("ALTER TABLE `fw_rules` ADD `RATELIM` smallint(1) NOT NULL DEFAULT 0, ADD`RATELIMIT` VARCHAR(100), ADD INDEX(`RATELIM`)");
		}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_rules WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["rulename"]);
	}

	$PROTO["-"]="{all}";
	$PROTO["tcp"]="TCP";
	$PROTO["udp"]="UDP";
	$LIMIT_T["sec"]="{second}";
	$LIMIT_T["min"]="{minute}";
	$LIMIT_T["hour"]="{hour}";
	$LIMIT_T["day"]="{day}";
	$LIMIT_T["week"]="{week}";
	$LIMIT_T["month"]="{month}";
	
	$LIMITTD["a"]="{all}";
	$LIMITTD["s"]="{source}";
	$LIMITTD["d"]="{destination}";
	
	if(preg_match("#^(s|d|a):([0-9]+)\/(.+?):([0-9]+)#", $ligne["RATELIMIT"],$re)){
		$LIMIT_D=$re[1];
		$connections=$re[2];
		$LIMIT_F=$re[3];
		$BURST=$re[4];
	}
	if($LIMIT_D==null){$LIMIT_D="a";}
	if(!is_numeric($BURST)){$BURST=5;}
	$t=time();
	$html="
	<div style='font-size:30px;margin-bottom:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{name}:</td>
		<td>". Field_text("rulename-$t",$ligne["rulename"],"font-size:16px;width:250px",null,null,null,false,"SaveCHK$t(event)")."</td>
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
		<td class=legend style='font-size:16px'>{protocol}:</td>
		<td>". Field_array_Hash($PROTO, "PROTO-$t",$ligne["PROTO"],null,null,0,"font-size:16px")."</td>
	</tr>												
	<tr>
		<td class=legend style='font-size:16px'>{policy}:</td>
		<td>". Field_array_Hash($q->RULES_POLICIES, "ACTION-$t",$ligne["ACTION"],null,null,0,"font-size:16px")."</td>
	</tr>
	<tr><td colspan=2>&nbsp;</td></tr>	
	<tr>
		<td class=legend style='font-size:16px'>{RATELIM}:</td>
		<td>". Field_checkbox("RATELIM", 1,$ligne["RATELIM"],"RATELIMCK()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{direction}:</td>
		<td>". Field_array_Hash($LIMITTD, "LIMIT_TD-$t",$LIMIT_D,null,null,0,"font-size:16px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{connections}:</td>
		<td>". Field_text("connections-$t",$connections,"font-size:16px;width:90px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{per}:</td>
		<td>". Field_array_Hash($LIMIT_T, "LIMIT_T-$t",$LIMIT_F,null,null,0,"font-size:16px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{burst}:</td>
		<td>". Field_text("burst-$t",$BURST,"font-size:16px;width:90px",null,null,null,false,"SaveCHK$t(event)")."</td>
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
	XHR.appendData('rule-save',  '$ID');
	XHR.appendData('zone_id_from',  encodeURIComponent(document.getElementById('zone_id_from-$t').value));
	XHR.appendData('zone_id_to',  encodeURIComponent(document.getElementById('zone_id_to-$t').value));
	XHR.appendData('rulename',  encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('PROTO',  encodeURIComponent(document.getElementById('PROTO-$t').value));
	
	XHR.appendData('LIMIT_TD',document.getElementById('LIMIT_TD-$t').value);
	XHR.appendData('LIMIT_T',document.getElementById('LIMIT_T-$t').value);
	XHR.appendData('connections',document.getElementById('connections-$t').value);
	XHR.appendData('burst',document.getElementById('burst-$t').value);
	XHR.appendData('ACTION',document.getElementById('ACTION-$t').value);
	
	
	
	if(document.getElementById('RATELIM').checked){
		XHR.appendData('RATELIM',1);
	}else{
		XHR.appendData('RATELIM',0);
	}
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
function RATELIMCK(){
	document.getElementById('LIMIT_TD-$t').disabled=true;
	document.getElementById('LIMIT_T-$t').disabled=true;
	document.getElementById('connections-$t').disabled=true;
	document.getElementById('burst-$t').disabled=true;
	
	if(!document.getElementById('RATELIM').checked){return;}
	document.getElementById('LIMIT_TD-$t').disabled=false;
	document.getElementById('LIMIT_T-$t').disabled=false;
	document.getElementById('connections-$t').disabled=false;
	document.getElementById('burst-$t').disabled=false;	
	
	
}
RATELIMCK();
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function rule_delete(){
	$q=new mysql_shorewall();
	$q->RULE_DELETE($_POST["rule-delete"]);
}

function rule_save(){
	$q=new mysql_shorewall();
	if(!is_numeric($_POST["connections"])) {$_POST["connections"]=5;}
	if($_POST["burst"]<5){$_POST["burst"]=5;}
	$_POST["RATELIMIT"]="{$_POST["LIMIT_TD"]}:{$_POST["connections"]}/{$_POST["LIMIT_T"]}:{$_POST["burst"]}";
	
	unset($_POST["LIMIT_TD"]);
	unset($_POST["connections"]);
	unset($_POST["LIMIT_T"]);
	unset($_POST["burst"]);
	
	$table="fw_rules";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	$editF=false;
	$ID=$_POST["rule-save"];
	unset($_POST["rule-save"]);
	
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


function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==0){$title=$tpl->javascript_parse_text("{new_rule}");}
	
	
	$array["rule-popup"]=$title;
	
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM fw_rules WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["rulename"]);
		$array["rule-popup"]=$title;
		$array["rule-groups"]="{groups}";
		
	}
	
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rule-groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"shorewall.groups.php?ID=$ID=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_rule_tab_$ID");
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
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$tt},
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
		{display: '&nbsp;', name : 'none', width :32, sortable : false, align: 'center'},
		{display: '$rule', name : 'rulename', width :200, sortable : true, align: 'left'},
		{display: '$explain', name : 'none', width :537, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$rule', name : 'policy_name'},
	{display: '$from', name : 'zone_from'},
	{display: '$to', name : 'zone_to'},
	{display: '$comment', name : 'comment'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$rules',
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
	Loadjs('$page?rule-js=yes&ID=0&t=$tt',true);
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
	$table="fw_rules";
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
		$total = $q->COUNT_ROWS("fw_rules");
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
		
		$editjs="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?rule-js=yes&ID={$ligne['ID']}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		$icon=$q->RULES_POLICIES_ICON[$ligne["ACTION"]];
		
		

		$explain=explain_rule($ligne);

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs<img src='img/$icon'></span>",
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs{$ligne["rulename"]}</a></span>",
				"<span style='font-size:12px;font-weight:normal;color:$color'>$explain</span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}
function explain_rule($ligne){
	$q=new mysql_shorewall();
	$tpl=new templates();
	$ACTION_CODE_TEXT=null;
	$zone_id_from=$ligne["zone_id_from"];
	$PROTO=$ligne["PROTO"];
	$ID=$ligne["ID"];
	$zone_id_to=$ligne["zone_id_to"];
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT zone FROM fw_zones WHERE ID='$zone_id_from'"));
	$zone_from=$ligne2["zone"];
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT zone FROM fw_zones WHERE ID='$zone_id_to'"));
	$zone_to=$ligne2["zone"];
	$log=$ligne["log"];
	//if($log==1){$event_text=" {and} {write_events}";}
	
	
	if($ligne["RATELIM"]==1){
		$LIMIT_T["sec"]="{second}";
		$LIMIT_T["min"]="{minute}";
		$LIMIT_T["hour"]="{hour}";
		$LIMIT_T["day"]="{day}";
		$LIMIT_T["week"]="{week}";
		$LIMIT_T["month"]="{month}";
		
		$LIMITTD["a"]="{all}";
		$LIMITTD["s"]="{source}";
		$LIMITTD["d"]="{destination}";
		if(preg_match("#^(s|d|a):([0-9]+)\/(.+?):([0-9]+)#", $ligne["RATELIMIT"],$re)){
			$LIMIT_D=$re[1];
			$connections=$re[2];
			$LIMIT_F=$re[3];
			$BURST=$re[4];
		}
		
		$limit=$tpl->_ENGINE_parse_body("<br>{and} {RATELIM} {$LIMITTD[$LIMIT_D]} $connections {per} {$LIMIT_T[$LIMIT_F]} {burst} $BURST");
		
	}
	
	$ACTION_CODE=$ligne["ACTION"];
	$action=$q->RULES_POLICIES[$ACTION_CODE];
	
	$sql="SELECT fw_objects.groupname,fw_objects.grouptype,
			fw_objects_lnk.`ID` as linkid,
			fw_objects_lnk.`ruleid`,
			fw_objects_lnk.`reverse`,
			`fw_objects_lnk`.`groupid`,
			fw_objects_lnk.`INOUT` FROM `fw_objects_lnk`,`fw_objects`
			WHERE 
			`fw_objects_lnk`.`groupid`=`fw_objects`.`ID`
			AND fw_objects_lnk.`ruleid`=$ID";
	

	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$IN=$q->mysql_error;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$not=null;
		$grouptype=$ligne["grouptype"];
		$grouptype=$tpl->javascript_parse_text($q->RULES_POLICIES_GROUP_TYPE[$grouptype]);
		$groupname="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('shorewall.groups.items.php?js=yes&groupid={$ligne['groupid']}&t={$_GET["t"]}',true);\"
		style='text-decoration:underline'
		>{$ligne["groupname"]} <i>$grouptype</i></a>";
		if($ligne["reverse"]==1){$not="{not} ";}
		$GPS[$ligne["INOUT"]][]="$not$groupname";
		
		
	}
	
	
	
	if(count($GPS[0])>0){
		$IN=" ".@implode("<br>{and} ", $GPS[0])."";
	}
	if(count($GPS[1])>0){
		$OUT=" ".@implode("<br>{and} ", $GPS[1])."";
	}	
	
	if($ACTION_CODE=="DNAT"){
		$ACTION_CODE_TEXT=" {to} $OUT";
		$OUT=null;
	}
	
	$html="{when_connections_came_from} $zone_from ($PROTO) $IN<br>{to} $zone_to ($PROTO) $OUT<br>{then} $action$ACTION_CODE_TEXT$limit";
	return $tpl->_ENGINE_parse_body($html);
	
	
}



?>	