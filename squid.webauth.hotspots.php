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
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}
if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
if(isset($_POST["ID"])){save();exit;}
table();

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=time();

$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

";

echo $html;

}
function move_items(){
	$q=new mysql_squid_builder();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zorder FROM hotspot_networks WHERE ID='$ID'"));
	if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}


	$CurrentOrder=$ligne["zorder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE hotspot_networks SET zorder=$CurrentOrder WHERE zorder='$NextOrder'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}


	$sql="UPDATE hotspot_networks SET zorder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM hotspot_networks ORDER by zorder","artica_backup");
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE hotspot_networks SET zorder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
		$c++;
	}


}

function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["ID"];
	if($linkid>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM hotspot_networks WHERE ID='$linkid'"));
		$t=$_GET["t"];
		$tt=time();
		$pattern=$tpl->javascript_parse_text("{network}: {$ligne["pattern"]}");
	}else{
		$pattern=$tpl->javascript_parse_text("{new_network}");
	}
	
	$html="YahooWin3('890','$page?item-popup=yes&ID=$linkid&t={$_GET["t"]}','$pattern',true);";
	echo $html;

}
function items_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["delete-js"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM hotspot_networks WHERE ID='$linkid'"));
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
	if(!confirm('$pattern')){return;} 
	XHR.appendData('delete',  '$linkid');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}			
	
Save$tt();	
	";
	echo $html;

}

function items_delete(){
	$q=new mysql_squid_builder();
	$table="hotspot_networks";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM hotspot_networks WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
}

function item_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_GET["ID"];
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_networks WHERE ID='$ID'"));
		$button="{apply}";
	}
	$array[100]="{garbage}";
	$array[0]="{global}";
	$array[1]="{known-users}";
	$array[2]="{unknown-users}";
	
	

	
	$action["block"]="{block}";
	$action["drop"]="{drop}";
	$action["allow"]="{allow}";
	$action["log"]="{log2}";
	
	$protocol[null]="{all}";
	$protocol["tcp"]="TCP";
	$protocol["udp"]="udp";
	$protocol["icmp"]="icmp";
	
	
	$direction[0]="{from_guest_network_to_internet}";
	$direction[1]="{from_internet_to_guest_network}";
	
	$t=time();
	$html="<div class=form style='width:95%'>
	<div class=explain style='font-size:18px'>{hostpot2_pattern_explain}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{destination}:</td>
		<td>". Field_text("pattern-$t",$ligne["pattern"],"font-size:22px;font-weight:bold;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{direction}:</td>
		<td>". Field_array_Hash($direction,"direction-$t",$ligne["direction"],"SwichDir$t()",'',0,"font-size:22px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{source}:</td>
		<td>". Field_text("destination-$t",$ligne["destination"],"font-size:22px;font-weight:bold;width:300px")."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{order}:</td>
		<td>". Field_text("zorder-$t",$ligne["zorder"],"font-size:22px;font-weight:bold;width:110px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{type}:</td>
		<td>". Field_array_Hash($array,"hotspoted-$t",$ligne["hotspoted"],"style:font-size:22px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{port}:</td>
		<td>". Field_text("port-$t",$ligne["port"],"font-size:22px;font-weight:bold;width:110px")."</td>
	</tr>							
				
	<tr>
		<td class=legend style='font-size:22px'>{protocol}:</td>
		<td>". Field_array_Hash($protocol,"proto-$t",$ligne["proto"],"style:font-size:22px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{action}:</td>
		<td>". Field_array_Hash($action,"action-$t",$ligne["action"],"style:font-size:22px")."</td>
	</tr>				
<tr>
	<td colspan=2 align='right'><hr>". button($button,"Save$t();",28)."</td>
</tr>
				
	</table></div>	
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID='$ID';
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWin3Hide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function SwichDir$t(){
	document.getElementById('destination-$t').disabled=false;
	document.getElementById('hotspoted-$t').disabled=false;
	
	
	var direction=document.getElementById('direction-$t').value;
	if(direction==0){
		document.getElementById('destination-$t').disabled=true;
		
	}else{
		document.getElementById('hotspoted-$t').disabled=true;
	}
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID',  '$ID');
	XHR.appendData('pattern',  document.getElementById('pattern-$t').value);
	XHR.appendData('zorder',  document.getElementById('zorder-$t').value);
	XHR.appendData('hotspoted',  document.getElementById('hotspoted-$t').value);
	XHR.appendData('action',  document.getElementById('action-$t').value);
	XHR.appendData('proto',  document.getElementById('proto-$t').value);
	XHR.appendData('port',  document.getElementById('port-$t').value);
	XHR.appendData('destination',  document.getElementById('destination-$t').value);
	XHR.appendData('direction',  document.getElementById('direction-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
SwichDir$t();
</script>	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	
	$q=new mysql_squid_builder();
	$table="hotspot_networks";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}

	if(!$q->FIELD_EXISTS("hotspot_networks", "direction")){$q->CheckTables();}

	$editF=false;
	$ID=$_POST["ID"];
	unset($_POST["ID"]);

	


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
	$new_network=$tpl->javascript_parse_text("{new_rule}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$order=$tpl->javascript_parse_text("{order}");
	$networks=$tpl->javascript_parse_text("{networks}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$type=$tpl->javascript_parse_text("{type}");
	$protocol=$tpl->javascript_parse_text("{protocol}");
	$port=$tpl->javascript_parse_text("{port}");
	$trusted_MAC=$tpl->javascript_parse_text("{trusted_MAC}");
	$trusted_sslwebsites=$tpl->javascript_parse_text("{trusted_ssl_sites}");
	$title=$networks;
	$tt=time();
	$q=new mysql_squid_builder();
	$q->check_hotspot_tables();
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:20px>$new_network</strong>', bclass: 'add', onpress : NewRule$tt},
	{name: '<strong style=font-size:20px>$trusted_MAC</strong>', bclass: 'Settings', onpress : rports},
	{name: '<strong style=font-size:20px>$trusted_sslwebsites</strong>', bclass: 'Settings', onpress : rssls},
	{name: '<strong style=font-size:20px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt},
	],";

	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$order</span>', name : 'zorder', width :78, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>$protocol</span>', name : 'proto', width :138, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>$networks</span>', name : 'pattern', width :612, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$type</span>', name : 'hotspoted', width :100, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>$action</span>', name : 'action', width :100, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>up</span>', name : 'up', width :81, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>down</span>', name : 'down', width :81, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 81, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$networks', name : 'pattern'},
	{display: '$port', name : 'port'},
	],
	sortname: 'zorder',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
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
	Loadjs('squid.webauth.restart.php');
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

function rports(){
	Loadjs('squid.webauth.hotspots.allowed.macs.php',true);
}

function rssls(){
	Loadjs('squid.webauth.hotspots.ssl.objects.php',true);
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
	$table="hotspot_networks";
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

	$no_rule=$tpl->_ENGINE_parse_body("{no_item}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$fontsize="18";
	$color="black";
	$check32="<img src='img/check-32.png'>";
	$AllSystems=$tpl->_ENGINE_parse_body("{AllSystems}");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	
	if(mysql_num_rows($results)==0){json_error_show($no_rule,1);}
	
	$Typearray[100]=$tpl->_ENGINE_parse_body("{garbage}");
	$Typearray[0]=$tpl->_ENGINE_parse_body("{global}");
	$Typearray[1]=$tpl->_ENGINE_parse_body("{known-users}");
	$Typearray[2]=$tpl->_ENGINE_parse_body("{unknown-users}");
	
	$ActionArray[]="cloud-deny-42.png";
	
	$ActionArray["block"]="cloud-deny-42.png";
	$ActionArray["drop"]="cloud-drop-32.png";
	$ActionArray["allow"]="cloud-goto-32.png";
	$ActionArray["log"]="cloud-log-32.png";
	
	$direction[0]=$tpl->javascript_parse_text("{outgoing}");
	$direction[1]=$tpl->javascript_parse_text("{incoming2}");
	$hostpot_net=$tpl->javascript_parse_text("{hostpot_net}");
	$all_text=$tpl->javascript_parse_text("{all}");
	$to_text=$tpl->javascript_parse_text("{to} $hostpot_net");
	$from_text=$tpl->javascript_parse_text("{from}");

	$fontsize="22";
	$check32="<img src='img/check-32.png'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$hotspoted=$ligne["hotspoted"];
		$proto=strtoupper($ligne["proto"]);
		$port=$ligne["port"];
		if($port==0){$port=null;}
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}&t={$_GET["t"]}',true)");
		$pattern=$ligne["pattern"];
		$entrant_text=null;
		
		$icon_action=$ActionArray[$ligne["action"]];
		
		if($hotspoted==100){
			$color="#8a8a8a";
			$icon_action="cloud-filtered-32-grey.png";
		}
		
		
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?item-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}',true)\"
		style='font-size:{$fontsize}px;font-weight:normal;color:$color;text-decoration:underline'>";
		
		$order=$ligne["zorder"];
		if($port<>null){$pattern="$pattern:$port";}
		
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		
		if($pattern==null){$pattern=$all_text;}
		$direction_text=$direction[$ligne["direction"]];
		$entrant_text="{from} $hostpot_net {to} ";
		$hostpoted_text=$Typearray[$hotspoted];
		
		if($ligne["direction"]==1){
			$entrant_text="$from_text: {$ligne["destination"]}<br>$to_text:";
			$hostpoted_text="*";
		}
		
		if($proto==null){$proto=$all_text;}
		
		$explain=$tpl->javascript_parse_text("$direction_text $entrant_text $pattern");
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$order</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$proto</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$explain</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$hostpoted_text</span>",
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>". imgsimple($icon_action)."</center>",
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>". $up."</center>",
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>". $down."</center>",
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</center>",)
		);
	}


	echo json_encode($data);

}