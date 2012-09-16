<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if($user->AsDnsAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}
if(isset($_GET["DnsDomain-Field"])){DnsDomainField();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_POST["id"])){item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
table();



function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=880;

	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$events=$tpl->_ENGINE_parse_body("events");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewPDNSEntry2$t},
	
	],	";
	
	
	$html="
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
	
function NewPDNSEntry$t(id){
	var title=id;
	if(!id){id=0;title='$new_entry';}
	YahooWin5('550','$page?item-id='+id+'&t=$t','PowerDNS:'+title);
}
function NewPDNSEntry2$t(id){
	var title=id;
	title='$new_entry';
	YahooWin5('550','$page?item-id=0&t=$t','PowerDNS:'+title);
}
	
</script>";
	
	echo $html;		
}	

function items(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="records";
	$database='powerdns';
	$page=1;
	$FORCE_FILTER=" AND `type`='A'";
	
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["id"];
		$articasrv=null;
		$delete=imgsimple("delete-24.png",null,"PdnsRecordDelete$t('$id')");
		if($ligne["articasrv"]<>null){$articasrv="<div><i style='font-size:11px'>serv:{$ligne["articasrv"]}</i></div>";}
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"javascript:NewPDNSEntry$t($id);\" style='font-size:16px;text-decoration:underline'>{$ligne["name"]}</a>$articasrv",
		"<span style='font-size:16px;'>{$ligne["content"]}</span>",
		"<span style='font-size:16px;'>{$ligne["ttl"]}</span>",
		"<span style='font-size:16px;'>{$ligne["prio"]}</span>",

		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$bname="{add}";
	$page=CurrentPageName();
	
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
	}
	
	
	$dnstypeTable=array(""=>"{select}","MX"=>"{mail_exchanger}","A"=>"{dnstypea}");
	$DnsType=Field_array_Hash($dnstypeTable,"DnsType",$DnsType,null,null,0,null);
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	$addDomain=imgtootltip("plus-24.png","{new_dnsdomain}","Loadjs('postfix.transport.table.php?localdomain-js=yes&domain=&t=$t&callback=RefreshFieldDomain$t')");
	
	
	if(count($domains)>0){
		$field_domains="
	<tr>
		<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
		<td align=left style='font-size:14px;font-weight:bolder'><span id='DnsZoneName-$t'></span></td>
		<td>$addDomain</td>
	</tr>";
		
	}else{
	$field_domains="	
		<tr>
			<td class=legend style='font-size:14px' nowrap>{DnsZoneName}:</strong></td>
			<td align=left><span id='DnsZoneName-$t'></span></strong></strong></td>
			<td>$addDomain</td>
		</tr>";		
	}
	
if($ttl==null){$ttl=8600;}
if(!is_numeric($prio)){$prio=0;}
$html="		
<div id='anime-$t'></div>
<div class=explain style='font-size:14px'>{ADD_DNS_ENTRY_TEXT}</div>
<div id='SaveDNSEntry'>
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
			var DnsZoneName=document.getElementById('DnsZoneName').value;
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
			var DnsZoneName=document.getElementById('DnsZoneName').value;
			var ComputerIP=document.getElementById('ComputerIP-$t').value;		
			if(DnsZoneName.length==0){ok=0;}
			if(ComputerIP.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			XHR.appendData('id','$id');
			XHR.appendData('computername',computername);
			XHR.appendData('DnsZoneName',DnsZoneName);
			XHR.appendData('ComputerIP',ComputerIP);
			XHR.appendData('TTL',document.getElementById('TTL-$t').value);
			XHR.appendData('PRIO',document.getElementById('PRIO-$t').value);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveDNSEntry$t);
		
		}
		
		function RefreshFieldDomain$t(){
			LoadAjaxTiny('DnsZoneName-$t','$page?DnsDomain-Field=yes&id=$id&t=$t');
		}
		
		function SaveDNSCheckFields(){
			
		}
		RefreshFieldDomain$t();
		SaveDNSCheckFields();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
	$pdns=new pdns($_POST["DnsZoneName"]);
	$pdns->ttl=$_POST["TTL"];
	$pdns->prio=$_POST["prio"];
	$pdns->EditIPName($_POST["computername"], $_POST["ComputerIP"], "A",$id);
	
}
function item_delete(){
	$pdns=new pdns();
	$pdns->mysql_delete_record_id($_POST["delete-item"]);
}

function DnsDomainField(){
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();	
	$id=$_GET["id"];
	$t=$_GET["t"];
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
	
	$DnsZoneName=Field_array_Hash($domains,"DnsZoneName",$DnsZoneNameV,null,null,0,"font-size:14px");
	echo $DnsZoneName;
}
