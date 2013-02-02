<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["item-config"])){item_config();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_GET["item-id-js"])){item_js();exit;}
if(isset($_POST["ID"])){forward_item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["RepairPDNSTables"])){RepairPDNSTables();exit;}
table();

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}


function item_js(){
	$id=$_GET["item-id-js"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_forward_zone}");
	$size="550";
	if($id>0){
		$q=new mysql();
		$sql="SELECT * FROM pdns_fwzones WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hostname=$ligne["hostname"];
		$port=$ligne["port"];
		$zone=$ligne["zone"];
		$title="$zone [$hostname:$port]";	
		$size=550;
		
	}
	echo "YahooWin5('$size','$page?item-id=$id&t=$t','$title');";
	
}










function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=500;
	$TB_WIDTH=880;
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$new_domain_controller=$tpl->_ENGINE_parse_body("{new_domain_controller}");
	$table="records";
	$database='powerdns';
	$t=time();
	
	
	if(!$q->TABLE_EXISTS("records", "powerdns")){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_missing_tables_click_to_repair}")."
		<hr>
		<center id='$t'>". button("{repair}", "RepairPDNSTables()","22px")."</center>
		<script>
			var x_RepairPDNSTables=function (obj) {
					var results=obj.responseText;
					if(results.length>0){alert(results);}	
			
					RefreshTab('main_config_pdns');
				}
			function RepairPDNSTables(){
				var XHR = new XHRConnection();
				XHR.appendData('RepairPDNSTables','yes');
				AnimateDiv('$t');
			    XHR.sendAndLoad('$page', 'POST',x_RepairPDNSTables);	
			}
			</script>		
		
		");
		return;
		
	}

	$new_entry=$tpl->_ENGINE_parse_body("{new_forward_zone}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$controllers=$tpl->_ENGINE_parse_body("{controllers}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewPDNSEntry2$t},
	
	],	";
			//$('#flexRT$t').flexReload();
	
	$html="
	<input type='hidden' id='domain-choose-$t' value=''>
	<input type='hidden' id='type-choose-$t' value=''>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$zone', name : 'zone', width :349, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :413, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$zone', name : 'zone'},
		{display: '$hostname', name : 'hostname'},
		
		
		
		],
	sortname: 'zone',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 845,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_PdnsZoneDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

function PdnsZoneDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_PdnsZoneDelete$t);	
	}
	
function NewDomainController$t(id){
	if(!id){id=0;}
	Loadjs('$page?controller-id-js='+id+'&t=$t');

}
	
function NewPDNSEntry$t(id){
	Loadjs('$page?item-id-js='+id+'&t=$t');
	
}
function NewPDNSEntry2$t(id){
	Loadjs('$page?item-id-js=0&t=$t');
}


	
</script>";
	
	echo $html;		
}	


function items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$users=new usersMenus();

	
	$search='%';
	$table="pdns_fwzones";
	$database='artica_backup';
	$page=1;

	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		json_error_show("No item");
	}

	$sock=new sockets();
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$delete=imgsimple("delete-24.png",null,"PdnsZoneDelete$t('$id')");
		$text_recursive=null;
		
		$jshost="NewPDNSEntry$t($id);";
		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=$ligne["zone"];
		$recursive=$ligne["recursive"];
		if($recursive==1){$text_recursive=$tpl->_ENGINE_parse_body("&nbsp;<i>({recursive})</i>");}
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$jshost;\" style='font-size:16px;text-decoration:underline'>
		$zone</a>",
				"<a href=\"javascript:blur();\" OnClick=\"javascript:$jshost;\" style='font-size:16px;text-decoration:underline'>
				$hostname$text_recursive</a>",

		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function RepairPDNSTables(){
	$sock=new sockets();
	echo @implode("\n",unserialize(base64_decode($sock->getFrameWork("pdns.php?repair-tables=yes"))));
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$bname="{add}";
	$page=CurrentPageName();
	$explian="<div class=explain style='font-size:14px'>{ADD_DNS_ENTRY_TEXT}</div>";
	$explian=null;
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM pdns_fwzones WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hostname=$ligne["hostname"];
		$zone=$ligne["zone"];
		$port=$ligne["port"];
		$recursive=$ligne["recursive"];
	}
	
	
	if(!is_numeric($port)){$port=53;}

$html="		
<div id='anime-$t'></div>
$explian
<div id='SaveDNSZone' class=BodyContent>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{domain}:</strong></td>
	<td align=left>". Field_text("zone-$t",$zone,"width:220px;font-size:14px","script:SaveDNSEntryCheck$t(event)")."</strong></td>
	<td>&nbsp;</td>
<tr>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{recursive}:</strong></td>
	<td align=left>". Field_checkbox("recursive-$t",1,$recursive)."</strong></td>
	<td>&nbsp;</td>
<tr>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{hostname}:</strong></td>
	<td align=left>". Field_text("hostname-$t",$hostname,"width:220px;font-size:14px","script:SaveDNSEntryCheck$t(event)")."</strong></td>
	<td>&nbsp;</td>
<tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{port}:</strong></td>
	<td align=left>". Field_text("port-$t",$port,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)")."</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveDNSEntry$t();","18px")."</td>
<tr>
</table>
</div>

<script>

		
		function SaveDNSEntryCheck$t(e){
			
			if(checkEnter(e)){SaveDNSEntry$t();return;}
			
		}
		

		var x_SaveDNSEntry$t=function (obj) {
			var results=obj.responseText;
			var id=$id;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>0){alert(results);return;}
			if(id==0){YahooWin5Hide();}
			$('#flexRT$t').flexReload();
			
		}				
		
		function SaveDNSEntry$t(){
			var ok=1;
			recursive=0;
			var hostname=document.getElementById('hostname-$t').value;
			if(document.getElementById('recursive-$t').checked){recursive=1;}
			var port=document.getElementById('port-$t').value;
			var zone=document.getElementById('zone-$t').value;		
			if(zone.length==0){ok=0;}
			if(hostname.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			XHR.appendData('ID','$id');
			XHR.appendData('hostname',hostname);
			XHR.appendData('port',port);
			XHR.appendData('zone',zone);
			XHR.appendData('recursive',recursive);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveDNSEntry$t);
		
		}
		
		
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function forward_item_save(){
	$id=$_POST["ID"];
	$q=new mysql();
	if($_POST["hostname"]==null){return;}
	if($_POST["zone"]==null){return;}
	if(!is_numeric($_POST["port"])){$_POST["port"]=53;}
	$sql="INSERT IGNORE INTO pdns_fwzones (zone,port,hostname,recursive) VALUES('{$_POST["zone"]}','{$_POST["port"]}','{$_POST["hostname"]}','{$_POST["recursive"]}')";
	if($id>0){
		$sql="UPDATE pdns_fwzones SET port='{$_POST["port"]}',
		zone='{$_POST["zone"]}',
		recursive='{$_POST["recursive"]},
		hostname='{$_POST["hostname"]}' WHERE ID='$id'";
		
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?reconfigure=yes");	
	
}
function item_delete(){
	$id=$_POST["delete-item"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM pdns_fwzones WHERE ID='$id'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?reconfigure=yes");	
}

