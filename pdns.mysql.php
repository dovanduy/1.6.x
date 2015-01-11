<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
$GLOBALS["VERBOSEDOMS"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose-domain"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSEDOMS"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(posix_getuid()<>0){
	$user=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}
if(isset($_GET["DnsDomain-Field"])){DnsDomainField();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["items-aliases"])){items_aliases_list();exit;}
if(isset($_POST["del-alias-cname"])){items_aliases_del();exit;}
if(isset($_POST["add-alias-item"])){item_aliases_add();exit;}
if(isset($_GET["item-config"])){item_config();exit;}
if(isset($_GET["item-cname"])){item_aliases_table();exit;}
if(isset($_GET["select-domain"])){select_domain();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_GET["item-id-js"])){item_js();exit;}

if(isset($_GET["controller-id-js"])){item_controller_js();exit;}
if(isset($_GET["item-controller-id"])){item_controller();exit;}
if(isset($_POST["controller"])){item_controller_save();exit;}


if(isset($_POST["id"])){item_save();exit;}
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
	$title=$tpl->javascript_parse_text("{new_record}");
	$size="550";
	if($id>0){
		$q=new mysql();
		$sql="SELECT name,`type`,`content` FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname=$ligne["name"];
		$tr=explode(".", $hostname);
		$computername=$tr[0];
		unset($tr[0]);
		$DnsZoneNameV=@implode(".", $tr);
		$DnsType=$ligne["type"];
		$ComputerIP=$ligne["content"];
		$title="$computername [$DnsZoneNameV] ($DnsType)";	
		$size=700;
		
	}
	echo "YahooWin5('$size','$page?item-id=$id&t=$t','$title');";
	
}

function item_controller_js(){
	$id=$_GET["controller-id-js"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_record}");
	$size="550";
	if($id>0){
		$q=new mysql();
		$sql="SELECT name,`type`,`content` FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname=$ligne["name"];
		$tr=explode(".", $hostname);
		$computername=$tr[0];
		unset($tr[0]);
		$DnsZoneNameV=@implode(".", $tr);
		$DnsType=$ligne["type"];
		$ComputerIP=$ligne["content"];
		$title="$computername [$DnsZoneNameV] ($DnsType)";
		$size=700;
	
	}
	echo "YahooWin5('$size','$page?item-controller-id=$id&t=$t','$title');";	
	
}

function select_domain(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$EnCryptedFunction=base64_encode("LoadAjaxTiny('DnsZoneNameF-$t','$page?DnsDomain-Field=yes&t=$t');");
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px' width=1%>{domains}:</td>
		<td class=legend style='font-size:14px'><span id='DnsZoneNameF-$t'></span></td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{go}","Go$t()","18")."</td>
	</tr>
	</table>
		<script>
		
		function Go$t(){
			var ztype=document.getElementById('type-choose-$t').value;
			var domain=document.getElementById('DnsZoneName').value;
			document.getElementById('domain-choose-$t').value=domain;
			$('#flexRT$t').flexOptions({url: '$page?items=yes&t=$t&record-type='+ztype+'&domain='+domain}).flexReload(); 
		
		}
		$('DnsZoneName').remove();
		LoadAjaxTiny('DnsZoneNameF-$t','$page?DnsDomain-Field=yes&t=$t&EnCryptedFunction=$EnCryptedFunction')
		</script>
			
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function item_aliases_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=500;
	$TB_WIDTH=880;
	$item_id=$_GET["item-id"];
	$table="records";
	$database='powerdns';
	$tt=$_GET["t"];
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$domains=$tpl->javascript_parse_text("{domains}");
	$q=new mysql();
	$sql="SELECT name FROM records WHERE id=$item_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	$hostnameT=$ligne["name"];
	
	$buttons="
		buttons : [
			{name: '$new_entry', bclass: 'Add', onpress : NewPDNSAlias$t},
			

		],	";
			//$('#flexRT$t').flexReload();

		$html="
		<div id='animate-$t'></div>
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
		<script>
			var mem$t='';
			$(document).ready(function(){
			$('#flexRT$t').flexigrid({
			url: '$page?items-aliases=yes&t=$t&item-id=$item_id',
			dataType: 'json',
			colModel : [
			{display: '$hostname', name : 'name', width :539, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},



			],
			$buttons

			searchitems : [
			{display: '$hostname', name : 'name'},
			



			],
			sortname: 'name',
			sortorder: 'asc',
			usepager: true,
			title: '$hostnameT::$aliases',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: 643,
			height: 350,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]

});
});

var x_PdnsAliasRecordDelete$t=function (obj) {
	document.getElementById('animate-$t').innerHTML='';
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+mem$t).remove();
}



var x_NewPDNSAlias$t=function (obj) {
	var results=obj.responseText;
	document.getElementById('animate-$t').innerHTML='';
	if(results.length>0){alert(results);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function NewPDNSAlias$t(){
	mem$t='';
	var aliases=prompt('$hostnameT:: $hostname ?');
	if(aliases){
		var XHR = new XHRConnection();
		XHR.appendData('add-alias-item','$item_id');
		XHR.appendData('add-alias-cname',aliases);
		AnimateDiv('animate-$t');
		XHR.sendAndLoad('$page', 'POST',x_NewPDNSAlias$t);
	}
}

function PdnsAliasRecordDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('del-alias-cname',id);
	AnimateDiv('animate-$t');
	XHR.sendAndLoad('$page', 'POST',x_PdnsAliasRecordDelete$t);	
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

function item_aliases_add(){
	$q=new mysql();
	$item_id=$_POST["add-alias-item"];
	$sql="SELECT * FROM records WHERE id=$item_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	$hostname=$ligne["name"];
	$ttl=$ligne["ttl"];
	$prio=$ligne["prio"];
	$change_date=date("YmdH");
	$domain_id=$ligne["domain_id"];
	$alias=$_POST["add-alias-cname"];
	
	$sql="INSERT IGNORE INTO records (domain_id, name, type, content, ttl, prio, change_date) 
	VALUES ($domain_id,'$alias','CNAME','$hostname','$ttl','$prio','$change_date')";
	$q->QUERY_SQL($sql,"powerdns");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("UPDATE records SET change_date='$change_date' WHERE id='$item_id'","powerdns");
	
}
function items_aliases_del(){
	$q=new mysql();
	$item_id=$_POST["del-alias-cname"];
	$sql="SELECT `content` FROM records WHERE id=$item_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	$hostname=$ligne["content"];
	$change_date=date("YmdH");
	$q->QUERY_SQL("DELETE FROM records WHERE id='$item_id'","powerdns");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE records SET change_date='$change_date' WHERE name='$hostname'","powerdns");
}

function items_aliases_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$users=new usersMenus();
	$item_id=$_GET["item-id"];
	
	$sql="SELECT name FROM records WHERE id=$item_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	$hostnameT=$ligne["name"];	
	
	$search='%';
	$table="records";
	$tablesrc="records";
	$database='powerdns';
	$page=1;
	$FORCE_FILTER=" ";
	$table="(SELECT records.* FROM records WHERE `content`='$hostnameT' AND `type` = 'CNAME') as t";
	
	if(!$q->TABLE_EXISTS($tablesrc, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($tablesrc,$database)==0){json_error_show("No data...",0);}
	
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
	
		while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["id"];
		$articasrv=null;
		$delete=imgsimple("delete-24.png",null,"PdnsAliasRecordDelete$t('$id')");
				
	
				$data['rows'][] = array(
						'id' => $id,
						'cell' => array(
								"<span style='font-size:18px;'>{$ligne["name"]}</span>",
								$delete )
		);
		}
	
	
		echo json_encode($data);	
	
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

	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$controllers=$tpl->_ENGINE_parse_body("{controllers}");
	$records=$tpl->_ENGINE_parse_body("{records}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewPDNSEntry2$t},
	{name: '$new_domain_controller', bclass: 'Add', onpress : NewDomainController$t},
	{name: '$import', bclass: 'Down', onpress : Import$t},
	
	
	{name: '$domains', bclass: 'Search', onpress : FilterDomain$t},
	{name: '$controllers', bclass: 'Search', onpress : ChoosePDC$t},
	{name: '$records', bclass: 'Search', onpress : ChooseRecords$t},
	
	
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
		{display: '$hostname', name : 'name', width :539, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'content', width :129, sortable : true, align: 'left'},
		{display: 'ttl', name : 'ttl', width :74, sortable : true, align: 'left'},
		{display: 'prio', name : 'prio', width :31, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'name'},
		{display: '$ipaddr', name : 'content'},
		
		
		
		],
	sortname: 'name',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 885,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_PdnsRecordDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

function PdnsRecordDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_PdnsRecordDelete$t);	
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

function FilterDomain$t(){
	YahooWin5(550,'$page?select-domain=yes&t=$t','Filter::$domains...');
}
function ChoosePDC$t(){
	document.getElementById('type-choose-$t').value='SRV';
	var domain=document.getElementById('domain-choose-$t').value;
	$('#flexRT$t').flexOptions({url: '$page?items=yes&t=$t&record-type=SRV&domain='+domain}).flexReload(); 
		
}
function ChooseRecords$t(){
	document.getElementById('type-choose-$t').value='A';
	var domain=document.getElementById('domain-choose-$t').value;
	$('#flexRT$t').flexOptions({url: '$page?items=yes&t=$t&record-type=A&domain='+domain}).flexReload(); 
		
}

function Import$t(){
	Loadjs('pdns.import.php?t=$t');
}
	
</script>";
	
	echo $html;		
}	

function item_controller(){
	
	
	$ldap=new clladp();
	$tpl=new templates();
	$id=$_GET["item-controller-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$bname="{add}";
	$page=CurrentPageName();
	$q=new mysql();
	$controllerName=null;
	$DnsZoneNameTEXT=null;
	$weight=0;
	$service=null;
	$DcController=null;
	
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname=$ligne["name"];
		if(preg_match("#^_(.+?)\.#", $hostname,$re)){$service="_{$re[1]}";}
		if(preg_match("#\.dc\._msdcs#", $hostname)){$DcController=1;}
		$DnsZoneNameV=@implode(".", $tr);
		$DnsType=$ligne["type"];
		$content=$ligne["content"];
		$ttl=$ligne["ttl"];
		$prio=$ligne["prio"];
		$explian=null;
		$domain_id=$ligne["domain_id"];
		$ligneZ=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM domains WHERE id=$domain_id","powerdns"));
		$DnsZoneName=$ligneZ["name"];
		$DnsZoneNameTEXT=$DnsZoneName;
		$DnsZoneName="<span style='font-size:14px;font-weight:bold'>$DnsZoneName</span>";
		
		if(preg_match("#([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.+)#", $content,$re)){
			$weigth=$re[1];
			$priority=$re[2];
			$port=$re[3];
			$controller_name=$re[4];
		}		
		
		$sql="SELECT id,content FROM records WHERE name='$controller_name' AND `type` ='A'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$ipaddr=$ligne["content"];
		
		
		
	}
	
	
	$Kservices["_ldap"]="_ldap";
	$Kservices["_sip"]="_sip";
	$Kservices["_sips"]="_sips";
	$Kservices["_slpda"]="_slpda";
	$Kservices["_tunnel"]="_tunnel";
	$Kservices["_http"]="_http";
	$Kservices["_ocsp"]="_ocsp";
	$Kservices["_xmpp"]="_xmpp";
	$Kservices["_xmpp-server"]="_xmpp-server";
	$Kservices["_syslog"]="_syslog";
	$Kservices["_kerberos-"]="_kerberos-";
	$Kservices["_dvbservdsc"]="_dvbservdsc";
	$Kservices["_im"]="_im";
	$Kservices["_jabber"]="_jabber";
	$Kservices["_mip6"]="_mip6";
	$Kservices["_msrps"]="_msrps";
	$Kservices["_mtqp"]="_mtqp";
	$Kservices["_pres"]="_pres";
	$Kservices["_pgp"]="_pgp";
	$Kservices["_rwhois"]="_rwhois";
	ksort($Kservices);
	if(!is_numeric($DcController)){$DcController=1;}
	
	
	if(!is_numeric($weight)){$weight=0;}
	if(!is_numeric($priority)){$priority=0;}
	if(!is_numeric($port)){$port=389;}
	if($service==null){$service="_ldap";}
	
	if(!is_numeric($domain_id)){$domain_id=0;}
	$dnstypeTable=array(""=>"{select}","MX"=>"{mail_exchanger}","A"=>"{dnstypea}");
	$DnsType=Field_array_Hash($dnstypeTable,"DnsType",$DnsType,null,null,0,null);
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	$addDomain=imgtootltip("plus-24.png","{new_dnsdomain}","Loadjs('postfix.transport.table.php?localdomain-js=yes&domain=&t=$t&callback=RefreshFieldDomain$t')");
	
	
	if(!$users->AsSystemAdministrator){
		if(!$users->AsDnsAdministrator){
			$ldap=new clladp();
			$addDomain=null;
	
		}
	}
	
	
	if(count($domains)>0){
		$field_domains="
		<tr>
		<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
		<td align=left style='font-size:14px;font-weight:bolder'><span id='DnsZoneName-$t'>$DnsZoneName</span></td>
		<td>$addDomain</td>
		</tr>";
	
	}else{
		$field_domains="
		<tr>
		<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
		<td align=left><span id='DnsZoneName-$t'>$DnsZoneName</span></strong></strong></td>
		<td>$addDomain</td>
		</tr>";
	}
	
	if($ttl==null){$ttl=8600;}
	if(!is_numeric($prio)){$prio=0;}
	
	$EnCryptedFunction=base64_encode("LoadAjaxTiny('DnsZoneName-$t','$page?DnsDomain-Field=yes&id=$id&t=$t');");
	
	
	
	
	
	$html="
	<div id='anime-$t'></div>
	$explian
	<div id='SaveDNSEntry' class=BodyContent>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{controller}:<i>$ipaddr</i></strong></td>
		<td align=left>".Field_text("controller-name-$t",$controller_name,"width:190px;font-size:14px;font-weight:bold","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</td>
		<td><input type='button' 
		style='font-size:13px' value='&laquo;{browse}...&raquo;' 
		OnClick=\"javascript:BrowsePDNS$t();\">
		</td>
	<tr>
	$field_domains
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{service}:</strong></td>
		<td align=left>". Field_array_Hash($Kservices,"service-$t",$service,null,null,0,"font-size:14px")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap> Active Directory:</strong></td>
		<td align=left>". Field_checkbox("dccontroller-$t",1,$DcController,"FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:14px' nowrap>{weight}:</strong></td>
		<td align=left>". Field_text("weight-$t",$weight,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{priority}:</strong></td>
		<td align=left>". Field_text("priority-$t",$priority,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>Port:</strong></td>
		<td align=left>". Field_text("port-$t",$port,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>
													
	<tr>
		<td class=legend style='font-size:14px' nowrap>TTL:</strong></td>
		<td align=left>". Field_text("TTL-$t",$ttl,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>PRIO:</strong></td>
		<td align=left>". Field_text("PRIO-$t",$prio,"width:90px;font-size:14px","script:SaveDNSEntryCheck$t(event)","FillDNSNAME$t()")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("$bname","SaveDNSEntry$t();","18px")."</td>
	<tr>
	</table>
	<center style='margin-top:10px'>
		<span style='font-size:16px;font-weight:bold' id='GiveHereComputerName$t'></span>
	</center>
	</div>
	
<script>
	
	
	function SaveDNSEntryCheck(e){
		SaveDNSCheckFields();
		if(checkEnter(e)){SaveDNSEntry$t();return;}
		FillDNSNAME();
	}
	
	
	function BrowsePDNS$t(){
		var DnsZoneName='$DnsZoneNameTEXT';
		if(DnsZoneName.length==0){
			if(document.getElementById('DnsZoneName')){DnsZoneName=document.getElementById('DnsZoneName').value;}
		}
		if(DnsZoneName.length<2){alert('Please choose domain first...');return;}
		Loadjs('BrowsePDNS.php?domain='+DnsZoneName+'&field=controller-name-$t');
	}
	
	function FillDNSNAME$t(){
		var dccontroller=0;
		var ddcontrol='';
		var DnsZoneName='$DnsZoneNameTEXT';
		var service=document.getElementById('service-$t').value;
		if(DnsZoneName.length==0){
			if(document.getElementById('DnsZoneName')){DnsZoneName=document.getElementById('DnsZoneName').value;}
		}
		if(document.getElementById('dccontroller-$t').checked){dccontroller=1;}
		if(DnsZoneName.length==0){return;}
		if(dccontroller==1){ddcontrol='dc._msdcs.';}
		document.getElementById('GiveHereComputerName$t').innerHTML=service+'.tcp.'+ddcontrol+DnsZoneName;
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
		var DnsZoneName='$DnsZoneNameTEXT';
		var dccontroller=0;
		var computername=document.getElementById('controller-name-$t').value;
		if(DnsZoneName.length==0){
			if(document.getElementById('DnsZoneName')){
				var DnsZoneName=document.getElementById('DnsZoneName').value;
			
			}
		}
		if(DnsZoneName.length==0){ok=0;}	
		if(computername.length==0){ok=0;}
		if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
		var XHR = new XHRConnection();
		XHR.appendData('id','$id');
		XHR.appendData('controller',computername);
		if(document.getElementById('dccontroller-$t').checked){dccontroller=1}
		if(document.getElementById('DnsZoneName')){XHR.appendData('DnsZoneName',DnsZoneName);}
		if(document.getElementById('weight-$t')){XHR.appendData('weight',document.getElementById('weight-$t').value);}
		if(document.getElementById('priority-$t')){XHR.appendData('priority',document.getElementById('priority-$t').value);}
		if(document.getElementById('port-$t')){XHR.appendData('priority',document.getElementById('port-$t').value);}
		if(document.getElementById('service-$t')){XHR.appendData('service',document.getElementById('service-$t').value);}
		XHR.appendData('dccontroller',dccontroller);
		
		XHR.appendData('TTL',document.getElementById('TTL-$t').value);
		XHR.appendData('PRIO',document.getElementById('PRIO-$t').value);
		AnimateDiv('anime-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveDNSEntry$t);
	
	}
	
	function RefreshFieldDomain$t(){
		var domain_id=$domain_id;
		if(domain_id==0){
			LoadAjaxTiny('DnsZoneName-$t','$page?DnsDomain-Field=yes&id=$id&t=$t&EnCryptedFunction=$EnCryptedFunction');
		}
		document.getElementById('controller-name-$t').disabled=true;
		FillDNSNAME$t();
	}
	
	function SaveDNSEntryCheck$t(e){
		if(checkEnter(e)){SaveDNSEntry$t();}
		FillDNSNAME$t();
	}
	
	
	RefreshFieldDomain$t();
	setTimeout('FillDNSNAME$t()','1000');
	</script>
	
	";
		
		
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function item_controller_save(){
	$id=$_POST["id"];
	$controller=$_POST["controller"];
	$PRIO=$_POST["PRIO"];
	$TTL=$_POST["TTL"];
	$DnsZoneName=$_POST["DnsZoneName"];
	$domain_id=0;
	$zdate=time();
	$q=new mysql();
	$service=$_POST["service"];
	$dccontroller=$_POST["dccontroller"];
	$dccontroller_txt=null;
	
	if(!isset($_POST["DnsZoneName"])){
		if($id==0){echo "DnsZoneName not set\n";return;}
		$q=new mysql();
		$sql="SELECT domain_id FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$domain_id=$ligne["domain_id"];
		$sql="SELECT name FROM domains WHERE id=$domain_id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$_POST["DnsZoneName"]=trim($ligne["name"]);
	}	
	
	if($domain_id==0){
		$sql="SELECT id FROM domains WHERE `name`='{$_POST["DnsZoneName"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));	
		$domain_id=$ligne["id"];
	}
	
	if($_POST["DnsZoneName"]==null){
		$sql="SELECT name FROM domains WHERE id=$domain_id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$_POST["DnsZoneName"]=trim($ligne["name"]);		
	}
	
	$sql="SELECT id,content FROM records WHERE name='$controller' AND `type` ='A'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	if(!$q->ok){
		echo "Unable to verify controller with the following error\n$q->mysql_error\n";
		return;
	}
	$controller_id=$ligne["id"];
	if(!is_numeric($controller_id)){$controller_id=0;}
	//if($controller_id==0){echo "$controller [ {$ligne["id"]} ] no such record (type=A)\n";return;}
	$controller_ip="192.168.1.10";
	
	if($domain_id==0){echo "{$_POST["DnsZoneName"]} no such domain\n";return;}
	if($_POST["DnsZoneName"]==null){echo "Unable to find domain with domain id: `$domain_id`....\n";return;}
	if($controller_ip==null){echo "$controller no such ip address\n";return;}
	
	if($dccontroller==1){
		$dccontroller_txt="dc._msdcs.";
	}
	
	$name="{$service}.tcp.$dccontroller_txt{$_POST["DnsZoneName"]}";
	$controller_name=$name;
	$type="SRV";
	
	  
	
	$content="{{$_POST["weight"]} {$_POST["priority"]} {$_POST["port"]} $controller";
	// weigth priority port target
	if($id==0){
		$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`)
			VALUES($domain_id,'$name','$type','$content','$TTL','$PRIO','$zdate')","powerdns");
			if(!$q->ok){
				echo $q->mysql_error."\nLine :".__LINE__;
				return false;
			}	
	}else{
		$q->QUERY_SQL("UPDATE records SET `name`='$name',
				`change_date`='$zdate',`ttl`='$TTL',
				`prio`='$PRIO',
				`content`='$content'
				WHERE id='{$ligne["id"]}'","powerdns");
			if(!$q->ok){
				echo $q->mysql_error."\nLine :".__LINE__;
				return false;
			}
		
	}
	
	$sql="SELECT id FROM records WHERE name='$controller_name'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	$controller_id=$ligne["id"];
	
	echo "SELECT id FROM records WHERE name='$controller_name' = $controller_id\n";
	
	if($controller_id==0){
		$sql="INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`)
		VALUES($domain_id,'$controller_name','A','$controller_ip','$TTL','$PRIO','$zdate')";
		echo $sql."\n";
		$q->QUERY_SQL($sql,"powerdns");
		
		if(!$q->ok){
			echo $q->mysql_error."\nLine :".__LINE__;
			return false;
		}
		
	}else{
		
		$sql="UPDATE records 
				SET `name`='$controller_name',
				`change_date`='$zdate',`ttl`='$TTL',
				`prio`='$PRIO',
				`type`='A',
				`content`='$controller_ip'
				WHERE id='$controller_id'";
		echo $sql."\n";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo $q->mysql_error."\nLine :".__LINE__;
			return false;
		}
	}
	
	
	
	
}



function items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$users=new usersMenus();

	if(!isset($_GET["record-type"])){$_GET["record-type"]="A";}
	if($_GET["record-type"]==null){$_GET["record-type"]="A";}
	$search='%';
	$table="records";
	$tablesrc="records";
	$database='powerdns';
	$page=1;
	$FORCE_FILTER=" AND `type` = '{$_GET["record-type"]}'";
	

	
	
	if(!$users->AsSystemAdministrator){
		if(!$users->AsDnsAdministrator){
			$ldap=new clladp();
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			while (list ($num, $ligne) = each ($domains) ){
				$tt[]="(domains.id = records.domain_id AND domains.name = '$num')";
			}
			
			$table="(SELECT records.* FROM records, domains WHERE ".@implode(" OR ", $tt).") as t";
		}
	}	
	
	if($_GET["domain"]<>null){
		$table="(SELECT records.* FROM records, domains WHERE (domains.id = records.domain_id AND domains.name = '{$_GET["domain"]}') ) as t";
	
	}	
	
	
	if(!$q->TABLE_EXISTS($tablesrc, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($tablesrc,$database)==0){json_error_show("No data...",0);}
	
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
		$id=$ligne["id"];
		$explainthis=null;
		$articasrv=null;
		$aliases_text=null;
		$delete=imgsimple("delete-24.png",null,"PdnsRecordDelete$t('$id')");
		if($ligne["articasrv"]<>null){$articasrv="<div><i style='font-size:11px'>serv:{$ligne["articasrv"]}</i></div>";}
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(id) as tcount FROM records WHERE `content`='{$ligne["name"]}' AND `type` = 'CNAME'","powerdns"));
		$aliases_count=$ligne2["tcount"];
		if($aliases_count>0){
			$aliases_text="<div><i style='font-size:11px;font-weight:bold'>$aliases_count $aliases</i></div>";
		}
		
		
		$jshost="NewPDNSEntry$t($id);";
		
		
		if($ligne["type"]=="SRV"){
			
			if(preg_match("#([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.+)#", $ligne["content"],$re)){
				$port=$re[3];
				$hostname=$re[4];
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT content FROM records WHERE `name`='$hostname'","powerdns"));
				$ligne["content"]=$ligne2["content"];
				$aliases_text=$aliases_text."<div><i style='font-size:11px;font-weight:bold'>&laquo;<span style='font-size:14px'>$hostname</span>&raquo; Port:$port</i></div>";
				$jshost="NewDomainController$t($id)";
			}
			
		}
		
		$explainthisH="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:explainthis('$id');\"
		style=\"text-decoration:normal\">
		";
		if($ligne["explainthis"]<>null){
			$explainthis="&nbsp;&nbsp;<i style='font-weight:bold;font-size:11px'>(".$ligne["explainthis"].")</i>";
		}
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" 
				OnClick=\"javascript:$jshost;\" 
				style=\"font-size:16px;text-decoration:underline\">{$ligne["name"]}</a>$explainthis$articasrv$aliases_text",
		"<span style='font-size:16px;'>{$ligne["content"]}</span>",
		"<span style='font-size:16px;'>{$ligne["ttl"]}</span>",
		"<span style='font-size:16px;'>{$ligne["prio"]}</span>",

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
	$tpl=new templates();
	$page=CurrentPageName();
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	if($id==0){item_config();return;}
	$t=$_GET["t"];
	
	$styleText="font-size:14px";
	$arr["item-config"]="{item}";
	$arr["item-cname"]="{aliases}";

	while(list( $num, $ligne ) = each ($arr)){
		$ligne=$tpl->_ENGINE_parse_body($ligne);
		$toolbox [] = "<li><a href=\"$page?$num=yes&item-id=$id&t=$t\"><span style='$styleText'>$ligne</span></a></li>";
	}
	
	$html ="<div id='pdnsmysql-item-tabs' style='width:99%;margin:0px;background-color:white'>
			<ul>
				" . implode ( "\n\t", $toolbox ) . "
			</ul>
		</div>
		<script>
		 $(document).ready(function() {
			$(\"#pdnsmysql-item-tabs\").tabs();});
		</script>";
	
	echo $html;	
	
}
function item_config(){
	$ldap=new clladp();
	$tpl=new templates();
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$bname="{add}";
	$page=CurrentPageName();
	$explian="<div class=text-info style='font-size:14px'>{ADD_DNS_ENTRY_TEXT}</div>";
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname=$ligne["name"];
		$tr=explode(".", $hostname);
		$computername=$tr[0];
		unset($tr[0]);
		$DnsZoneNameV=@implode(".", $tr);
		$DnsType=$ligne["type"];
		$ComputerIP=$ligne["content"];
		$ttl=$ligne["ttl"];
		$prio=$ligne["prio"];
		$explainthis=$ligne["explainthis"];
		$domain_id=$ligne["domain_id"];
		$ligneZ=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM domains WHERE id=$domain_id","powerdns"));
		$DnsZoneName=$ligneZ["name"];		
		
	}
	
	if(!is_numeric($domain_id)){$domain_id=0;}
	$dnstypeTable=array(""=>"{select}","MX"=>"{mail_exchanger}","A"=>"{dnstypea}");
	$DnsType=Field_array_Hash($dnstypeTable,"DnsType",$DnsType,null,null,0,null);
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	$addDomain=imgtootltip("plus-24.png","{new_dnsdomain}","Loadjs('postfix.transport.table.php?localdomain-js=yes&domain=&t=$t&callback=RefreshFieldDomain$t')");
	
	
	if(!$users->AsSystemAdministrator){
		if(!$users->AsDnsAdministrator){
			$ldap=new clladp();
			$addDomain=null;
				
		}
	}
	
	
	if($domain_id>0){
		$field_domains="
	<tr>
		<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
		<td align=left style='font-size:14px;font-weight:bolder'>
				".Field_text("DnsZoneName-$t",$DnsZoneName,"width:220px;font-size:14px;color:white",null,null,null,false,null,true,null)."</td>
		<td>$addDomain</td>
	</tr>";
		
	}else{
	$field_domains="	
		<tr>
			<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
			<td align=left style='font-size:14px;font-weight:bolder'>
			<span id='DnsZoneNameSpan-$t'></span>
			<td>$addDomain</td>
		</tr>";		
	}
	
if($ttl==null){$ttl=8600;}
if(!is_numeric($prio)){$prio=0;}

$EnCryptedFunction=base64_encode("LoadAjaxTiny('DnsZoneNameSpan-$t','$page?DnsDomain-Field=yes&id=$id&t=$t');");

$html="		
<div id='anime-$t'></div>
$explian
<div id='SaveDNSEntry' class=BodyContent>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{computer_ip}:</strong></td>
	<td align=left>". field_ipv4("ComputerIP-$t",$ComputerIP,'font-size:14px')."</strong></td>
	<td>&nbsp;</td>
<tr>
$field_domains
<tr>
	<td class=legend style='font-size:14px' nowrap>{computer_name}:</strong></td>
	<td align=left>". Field_text("computername-$t",$computername,"width:220px;font-size:14px","script:SaveDNSEntryCheck(event)","FillDNSNAME()")."</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>TTL:</strong></td>
	<td align=left>". Field_text("TTL-$t",$ttl,"width:90px;font-size:14px","script:SaveDNSEntryCheck(event)","FillDNSNAME()")."</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>PRIO:</strong></td>
	<td align=left>". Field_text("PRIO-$t",$prio,"width:90px;font-size:14px","script:SaveDNSEntryCheck(event)","FillDNSNAME()")."</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{explain}:</strong></td>
	<td align=left>". Field_text("explainthis-$t",$explainthis,"width:300px;font-size:12px;font-style:italic","script:SaveDNSEntryCheck(event)","FillDNSNAME()")."</strong></td>
	<td>&nbsp;</td>
</tr>			
			

<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveDNSEntry$t();","18px")."</td>
<tr>
</table>
<center style='margin-top:10px'>
	<span style='font-size:16px;font-weight:bold' id='GiveHereComputerName'></span>
</center>
</div>

<script>

		
		function SaveDNSEntryCheck(e){
			SaveDNSCheckFields();
			if(checkEnter(e)){SaveDNSEntry$t();return;}
			FillDNSNAME();
		}
		
		function FillDNSNAME(){
			var computername=document.getElementById('computername-$t').value;
			var DnsZoneName=document.getElementById('DnsZoneName-$t').value;
			if(computername.length==0){return;}
			if(DnsZoneName.length==0){return;}
			document.getElementById('GiveHereComputerName').innerHTML=computername+'.'+DnsZoneName;
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
			var computername=document.getElementById('computername-$t').value;
			
			var ComputerIP=document.getElementById('ComputerIP-$t').value;	
			var explainthis=encodeURIComponent(document.getElementById('explainthis-$t').value);	
			
			if(ComputerIP.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			XHR.appendData('id','$id');
			XHR.appendData('computername',computername);
			if(document.getElementById('DnsZoneName-$t')){
				var DnsZoneName=document.getElementById('DnsZoneName-$t').value;
				if(DnsZoneName.length==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM: Domain');return;}
				XHR.appendData('DnsZoneName',DnsZoneName);
			}
			XHR.appendData('ComputerIP',ComputerIP);
			XHR.appendData('TTL',document.getElementById('TTL-$t').value);
			XHR.appendData('PRIO',document.getElementById('PRIO-$t').value);
			XHR.appendData('explainthis',explainthis);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveDNSEntry$t);
		
		}
		
		function RefreshFieldDomain$t(){
			var domain_id=$domain_id;
			if(domain_id==0){
				LoadAjaxTiny('DnsZoneNameSpan-$t','$page?DnsDomain-Field=yes&id=$id&t=$t&EnCryptedFunction=$EnCryptedFunction');
			}
			
		}
		
		function SaveDNSCheckFields(){
			
		}
		RefreshFieldDomain$t();
		SaveDNSCheckFields();
		FillDNSNAME();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
	$id=$_POST["id"];
	$_POST["explainthis"]=url_decode_special_tool($_POST["explainthis"]);
	if(!isset($_POST["DnsZoneName"])){
		if($id==0){echo "DnsZoneName not set\n";return;}
		$q=new mysql();
		$sql="SELECT domain_id FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$domain_id=$ligne["domain_id"];		
		$sql="SELECT name FROM domains WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$_POST["DnsZoneName"]=$ligne["name"];
	}
	
	$pdns=new pdns($_POST["DnsZoneName"]);
	$pdns->ttl=$_POST["TTL"];
	$pdns->prio=$_POST["prio"];
	
	$pdns->EditIPName($_POST["computername"], $_POST["ComputerIP"], "A",$id,$_POST["explainthis"]);
	
}
function item_delete(){
	$pdns=new pdns();
	$pdns->mysql_delete_record_id($_POST["delete-item"]);
}

function DnsDomainField(){
	$ldap=new clladp();
	$domains=array();
	$tpl=new templates();
	$users=new usersMenus();
	$id=$_GET["id"];
	$t=$_GET["t"];
	$EnCryptedFunction=$_GET["EnCryptedFunction"];
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$add="<div style='text-align:right'>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('domain-local-create.php?t=$t&EnCryptedFunction=$EnCryptedFunction')\"
				style='font-size:12px;text-decoration:underline'>$add_local_domain</a></div>";	

	if(!$users->AsSystemAdministrator){
		if(!$users->AsDnsAdministrator){
			$ldap=new clladp();
			if($GLOBALS["VERBOSE"]){echo "ldap->hash_get_domains_ou({$_SESSION["ou"]})\n";}
			$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
			
		};
	}	
	if(($users->AsSystemAdministrator) OR ($users->AsDnsAdministrator)){
		if($GLOBALS["VERBOSE"]){echo "ldap->hash_get_all_domains()\n";}
		$domains=$ldap->hash_get_all_domains();

	}
	
	
	//$GLOBALS["VERBOSEDOMS"]
	$DnsZoneNameV=null;

	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT name FROM records WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname=$ligne["name"];
		$tr=explode(".", $hostname);
		$computername=$tr[0];
		unset($tr[0]);
		$DnsZoneNameV=@implode(".", $tr);

	}	
	
	$DnsZoneName=Field_array_Hash($domains,"DnsZoneName-$t",$DnsZoneNameV,null,null,0,"font-size:14px").$add;
	echo $DnsZoneName;
}
