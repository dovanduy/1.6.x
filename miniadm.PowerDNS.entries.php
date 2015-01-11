<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


$users=new usersMenus();
if(!$users->AsDnsAdministrator){die();}

if(isset($_GET["item-config"])){item_config();exit;}

if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_GET["item-id-js"])){item_js();exit;}
if(isset($_GET["search-records"])){search_records();exit;}
if(isset($_POST["explainthis"])){item_save();exit;}
content();



function content(){
	$q=new mysql();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	if(!$q->TABLE_EXISTS("records", "powerdns")){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_missing_tables_click_to_repair}")."
		<hr>
		<center id='$t'>". button("{repair}", "RepairPDNSTables()","22px")."</center>
		<script>
	var x_RepairPDNSTables=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			MyHref('$page');
	}
	function RepairPDNSTables(){
		var XHR = new XHRConnection();
		XHR.appendData('RepairPDNSTables','yes');
		AnimateDiv('$t');
		XHR.sendAndLoad('pdns.mysql.php', 'POST',x_RepairPDNSTables);
	}
	</script>
	");
	return;
	
}	
	
	$sock=new sockets();
	$boot=new boostrap_form();
	
	$button=button("{new_item}","NewPDNSEntry2()",16);
	$SearchQuery=$boot->SearchFormGen("name,content,explainthis","search-records");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	

	
	if($EnablePDNS==0){
		$error="<div class=text-infoWarn>{EnablePDNS_disable_text}</div>";
	}
	 $html="
	 $error
	 <table style='width:100%'>
	 <tr>
	 <td>$button</td>
	 <td></td>
	 </tr>
	 </table>
	 $SearchQuery
	 <script>
	 	ExecuteByClassName('SearchFunction');
	 </script>
	 ";
	 			
	 echo $tpl->_ENGINE_parse_body($html);	 
	
	
	
	
}
function search_records(){
if(!isset($_GET["record-type"])){$_GET["record-type"]="A";}
	if($_GET["record-type"]==null){$_GET["record-type"]="A";}
	$search='%';
	$table="records";
	$tablesrc="records";
	$database='powerdns';
	$page=1;
	$FORCE_FILTER=" AND `type` = '{$_GET["record-type"]}'";
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$limitSql="LIMIT 0,150";
	$t=time();
	
	$searchstring=string_to_flexquery("search-records");
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER ORDER BY name $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	$sock=new sockets();
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$boot=new boostrap_form();
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}
	
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
		
		$link=$boot->trswitch($jshost);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-globe'></i> {$ligne["name"]}</a>$explainthis$articasrv$aliases_text</td>
		<td $link><i class='icon-info-sign'></i> {$ligne["content"]}</td>
		<td $link><i class='icon-time'></i> {$ligne["ttl"]}</td>
		<td $link><i class='icon-star'></i> {$ligne["prio"]}</td>
		<td style='text-align:center'>$delete</td>
		</tr>";		
	
		
	}	

	
	
	
echo $tpl->_ENGINE_parse_body("
		
		<table class='table table-bordered table-hover'>
		
			<thead>
				<tr>
					<th>{hostname}</th>
					<th>{ipaddr}</th>
					<th>{ttl}</th>
					<th>PRIO</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
<script>
var mem$t='';
	
	function NewPDNSEntry$t(id){
		$.getScript('$page?item-id-js='+id+'&t=$t');
	}
	function NewPDNSEntry2(){
		$.getScript('$page?item-id-js=0&t=$t');
	}	

	var x_PdnsRecordDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#'+mem$t).remove();
	}

function PdnsRecordDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('pdns.mysql.php', 'POST',x_PdnsRecordDelete$t);	
	}	
	
</script>		
			
			
	";
	
	
	
}

function item_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	if($id==0){item_config();return;}
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{item}"]="$page?item-config=yes&item-id=$id";
	$array["{aliases}"]="pdns.mysql.php?item-cname=yes&item-id=$id";
	echo $boot->build_tab($array);


}
function item_js(){
	$id=$_GET["item-id-js"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_record}");
	$size=650;
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
	header("content-type: application/x-javascript");
	echo "YahooWin5('$size','$page?item-id=$id&t=$t','$title');";

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



	if($ttl==null){$ttl=8600;}
	if(!is_numeric($prio)){$prio=0;}

	

	
	$boot=new boostrap_form();
	$boot->set_field("ComputerIP", "{computer_ip}", $ComputerIP);
	if($domain_id>0){
		$boot->set_field("DnsZoneName", "{DnsZoneName}", $DnsZoneName,array("DISABLED"=>true));
	}else{
		$boot->set_field("DnsZoneName", "{DnsZoneName}", $DnsZoneName);
	}
	$boot->set_field("computername", "{computer_name}", $computername);
	$boot->set_field("TTL", "TTL", $ttl);
	$boot->set_field("PRIO", "PRIO", $prio);
	$boot->set_field("explainthis", "{explain}", $explainthis,array("ENCODE"=>true));
	
	$boot->set_button($bname);
	$boot->set_hidden("id", $id);
	$boot->set_RefreshSearchs();
	$boot->set_formtitle("{record}");
	$form=$boot->Compile();
			
			
	echo $tpl->_ENGINE_parse_body($form);
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