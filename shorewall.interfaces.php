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
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-tabs"])){interface_tabs();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_POST["interface-save"])){interface_save();exit;}
if(isset($_POST["interface-delete"])){interface_delete();exit;}
if(isset($_GET["interface-options"])){interface_options();exit;}
table();	

function interface_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	if($ID==0){$title=$tpl->javascript_parse_text("{link_interface}");}
	if($ID>0){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_interfaces WHERE eth='$ID'"));
		$ip=new system_nic($ID);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["eth"]);
	}
	
	echo "YahooWin('700','$page?interface-tabs=yes&ID=$ID&t=$t','$title')";
}

function interface_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_shorewall();
	$bt_title="{add}";
	$ID=$_GET["ID"];
	
	if($ID==null){$title=$tpl->javascript_parse_text("{link_interface}");}
	if($ID<>null){
		$bt_title="{apply}";
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT eth FROM fw_interfaces WHERE eth='$ID'"));
		$ip=new system_nic($ID);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["eth"]);
	}
	
	$net=new networking();
	$ethz=$net->Local_interfaces();
	while (list ($num, $ligne) = each ($ethz) ){
		$ip=new system_nic($num);
		$ethz[$num]="$ip->NICNAME ($num)";
	}
	
	
	$t=time();
	$html="
	<div style='font-size:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td>". Field_array_Hash($ethz, "interface-$t",$ligne["type"],null,null,0,"font-size:16px")."</td>
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
	XHR.appendData('interface-save',  encodeURIComponent(document.getElementById('interface-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function interface_delete(){
	$q=new mysql_shorewall();
	$q->INTERFACE_DELETE($_POST["interface-delete"]);
	
	
}

function interface_save(){
	$q=new mysql_shorewall();
	$table="fw_interfaces";
	$editF=false;
	$ID=$_POST["interface-save"];
	$sql="INSERT IGNORE INTO `$table` (`eth`,`routeback`,`logmartians`) VALUES ('$ID',1,1)";

	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	
}

function interface_options(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_shorewall();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM fw_interfaces WHERE eth='$ID'"));
	$tr[]="<div style='font-size:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>";
	
	$t=time();
	while (list ($num, $none) = each ($q->INTERFACE_OPT) ){
		$tr[]="
		<tr>
			<td style='font-size:16px' class=legend>{shorwallnic_{$num}}:</td>
			<td>". Field_checkbox("$num-$t", 1,$ligne[$num])."</td>
		</tr>
		";
		
		$js[]="if(document.getElementById('$num-$t').checked){XHR.appendData('$num', 1);}else{XHR.appendData('$num', 0);}";
		
	}
	$tr[]="
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()")."</td>
		</tr>	
		</table>
	</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID='$ID';
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('options-save',  '$ID');
	".@implode("\n", $js)."\n
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>						
";
	$html=@implode("\n", $tr);
	echo $tpl->_ENGINE_parse_body($html);
}


function interface_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if($ID==null){$title=$tpl->javascript_parse_text("{link_interface}");}
	
	if($ID==null){
		$array["interface-popup"]=$title;
	}
	
	if($ID<>null){
		$q=new mysql_shorewall();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT eth FROM fw_interfaces WHERE eth='$ID'"));
		$ip=new system_nic($ID);
		$title=$tpl->javascript_parse_text("{interface}: $ip->NICNAME - ".$ligne["eth"]);
		$array["interface-options"]=$title;
		
		
	}
	
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_zone_interface_$ID");
}
	
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$new_text=$tpl->javascript_parse_text("{link_interface}");
	$interfaces=$tpl->javascript_parse_text("{interfaces}");
	$delete=$tpl->javascript_parse_text("{delete} {interface} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$maintitle=$tpl->javascript_parse_text("{network_interfaces}");
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
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: '$interfaces', name : 'eth', width :242, sortable : true, align: 'left'},
		{display: '$comment', name : 'comment', width : 508, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
	{display: '$interfaces', name : 'eth'},
	
	],
	sortname: 'eth',
	sortorder: 'asc',
	usepager: true,
	title: '$maintitle',
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
	Loadjs('$page?interface-js=yes&ID=&t=$tt','$new_text');
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('interface-delete', zmd5);
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
	$table="fw_interfaces";
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
		
		$delete=imgsimple("delete-32.png",null,"Delete$t('{$ligne["eth"]}')");
		$ip=new system_nic($ligne["eth"]);
		$interface="$ip->NICNAME - ".$ligne["eth"];
		$linkinterface="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?interface-js=yes&ID={$ligne["eth"]}&t=$t');\" style='font-size:{$fontsize}px;text-decoration:underline'>";

		$data['rows'][] = array(
				'id' => $ligne['eth'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>
							<img src='img/32-win-nic.png'>
						</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$linkinterface$interface</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["comment"]}</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}

?>	