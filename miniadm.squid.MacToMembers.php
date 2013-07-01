<?php
session_start();$_SESSION["MINIADM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}
if(isset($_GET["search-records"])){search_records();exit;}
if(isset($_GET["NewMacLink-js"])){NewMacLink_js();exit;}
if(isset($_GET["NewMacLink-popup"])){NewMacLink_popup();exit;}
if(isset($_POST["save-mac"])){NewMacLink_save();exit;}
if(isset($_GET["delete-mac-js"])){delete_js();exit;}
if(isset($_POST["delete-mac"])){delete_mac();exit;}
if(isset($_POST["apply-changes"])){apply_changes();exit;}

content();

function content(){
	$sock=new sockets();
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$button=button("{link_mac_to_uid}","NewMacLink$t()",16);
	$button2=button("{apply_changes}","Apply$t()",16);
	$SearchQuery=$boot->SearchFormGen("MAC,uid,hostname","search-records");
	$apply_changes_mactouid_explain=$tpl->javascript_parse_text("{apply_changes_mactouid_explain}");
	$html="
	<div class=explain>{mactouid_explain}</div>
	<table style='width:100%'>
	<tr>
	<td>$button $button2</td>
	<td></td>
	</tr>
	</table>
	$SearchQuery
	<script>
	ExecuteByClassName('SearchFunction');
	
	function NewMacLink$t(){
		Loadjs('$page?NewMacLink-js=yes')
	
	}
	var x_Apply$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}		
	
	function Apply$t(){
		if(!confirm('$apply_changes_mactouid_explain')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('apply-changes','yes');
		XHR.sendAndLoad('$page', 'POST',x_Apply$t);			
		
	}
	
	</script>
	";
		
	echo $tpl->_ENGINE_parse_body($html);
}

function apply_changes(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?MacToUid=yes");
	$sock->getFrameWork("squid.php?MacToUidStats=yes");
	
}

function NewMacLink_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$_GET["MAC"]=trim($_GET["MAC"]);
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{link_mac_to_uid}");
	if($_GET["MAC"]<>null){
		$title=$tpl->javascript_parse_text("{link_mac_to_uid}::{$_GET["MAC"]}");
	}
	
	echo "YahooWin2('700','$page?NewMacLink-popup=yes&MAC={$_GET["MAC"]}','$title')";
	
	
}
function delete_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$_GET["delete-mac-js"]=trim($_GET["delete-mac-js"]);
	$id=$_GET["id"];
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{delete}: {$_GET["delete-mac-js"]} ?");
	$t=time();
	echo "
		var x_delete$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#$id').remove();
		}				
	
	
		function delete$t(){
			if(!confirm('$title')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('delete-mac','{$_GET["delete-mac-js"]}');
			XHR.sendAndLoad('$page', 'POST',x_delete$t);			
			
		
		}
	delete$t()";


}
function delete_mac(){
	$sql="DELETE FROM webfilters_nodes WHERE MAC='{$_POST["delete-mac"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}



function NewMacLink_popup(){
	$tpl=new templates();
	$_GET["MAC"]=trim($_GET["MAC"]);
	if($_GET["MAC"]<>null){if(!IsPhysicalAddress($_GET["MAC"])){unset($_GET["MAC"]);}}else{unset($_GET["MAC"]);}
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$_GET["MAC"]}'"));
	$boot=new boostrap_form();
	if($_GET["MAC"]==null){
			$boot->set_field("MAC", "{MAC}", null,
			array("MANDATORY"=>true,
				   "BUTTON"=>array("JS"=>"Loadjs('miniadm.squid.macbrowser.php?field=%f')","LABEL"=>$tpl->_ENGINE_parse_body("{browse}"))));
	}else{
		$boot->set_formtitle("{MAC}:{$_GET["MAC"]}");
		$boot->set_hidden("MAC", $_GET["MAC"]);
		
		$ips=array();
		$results2 = $q->QUERY_SQL("SELECT ipaddr FROM members_macip WHERE MAC='{$_GET["MAC"]}' ORDER BY ipaddr");
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$ips[]=$ligne2["ipaddr"];
		}
		if(count($ips)>0){
			
			$boot->set_formdescription(@implode(", ", $ips));
		}
					
		$linkstats="<div style='width:100%'>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:miniadm.squid.macbrowser.php?visits-day-js={$_GET["MAC"]}');>{statistics}</a></div>";
			
		
	}
	
	$boot->set_hidden("save-mac", 'yes');
	$boot->set_field("hostname", "{hostname}", $ligne["hostname"]);
	$boot->set_field("uid", "{member}", $ligne["uid"],array("MANDATORY"=>true));
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function NewMacLink_save(){
	$_POST["MAC"]=str_replace("-", ":", $_POST["MAC"]);
	$_POST["MAC"]=strtolower($_POST["MAC"]);
	$_POST["hostname"]=strtolower($_POST["hostname"]);
	if(!IsPhysicalAddress($_POST["MAC"])){echo "{$_POST["MAC"]} Wrong value\n";return;}
	
	
	$q=new mysql_squid_builder();
	$_POST["uid"]=$q->StripBadChars_hostname($_POST["uid"]);
	$_POST["hostname"]=$q->StripBadChars_hostname($_POST["hostname"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT MAC FROM webfilters_nodes WHERE MAC='{$_POST["MAC"]}'"));
	if($ligne["MAC"]<>null){
		$sql="UPDATE webfilters_nodes SET uid='{$_POST["uid"]}',hostname='{$_POST["hostname"]}' WHERE MAC='{$_POST["MAC"]}'";
	}else{
		
		$sql="INSERT IGNORE INTO webfilters_nodes (MAC,hostname,uid) VALUES ('{$_POST["MAC"]}','{$_POST["hostname"]}','{$_POST["uid"]}');";
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}



function search_records(){
	
	
	//SELECT MacAddress, uid FROM hostsusers
	$search='%';
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$q2=new mysql();
	$limitSql="LIMIT 0,150";
	$t=time();

	$searchstring=string_to_flexquery("search-records");

	$sql="SELECT *  FROM webfilters_nodes WHERE 1 $searchstring  ORDER BY uid $limitSql";
	
	$results = $q->QUERY_SQL($sql);
	$sock=new sockets();
	$boot=new boostrap_form();
	
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}

	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$explainthis=null;
		$articasrv=null;
		$aliases_text=null;
		$explainMac=null;
		$OS=null;
		$macencoded=urlencode($ligne["MAC"]);
		$delete=imgsimple("delete-24.png",null,"Loadjs('$page?delete-mac-js=$macencoded&id=$id')");
		$jshost="NewPDNSEntry$t($id);";
		if(!IsPhysicalAddress($ligne["MAC"])){continue;}

		if($ligne["nmap"]==1){
			$array=unserialize(base64_decode($ligne["nmapreport"]));
			$OS=$array["OS"];
			$tt=array();
			if(count($array["PORTS"])>0){while (list ($port, $explain) = each ($array["PORTS"]) ){$tt[]="$port ($explain)";}
			$ports=@implode(", ", $tt);
			$explainMac="<div><i style='font-size:11px'>$OS $ports</i></div>";
			}
			
		}
		if($ligne["uid"]==null){
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT uid FROM hostsusers WHERE MacAddress='{$ligne["MAC"]}'","artica_backup"));
			if($ligne2["uid"]<>null){
				$q->QUERY_SQL("UPDATE webfilters_nodes SET uid='{$ligne2["uid"]}' WHERE MAC='{$ligne["MAC"]}'");
				$ligne["uid"]=$ligne2["uid"];
			}
		
		}
		
		$ips=array();
		$results2 = $q->QUERY_SQL("SELECT ipaddr FROM members_macip WHERE MAC='{$ligne["MAC"]}' ORDER BY ipaddr");
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$ips[]=$ligne2["ipaddr"];
		}
		if(count($ips)>0){$explainMac="$explainMac<div><i style='font-size:11px'>".@implode(", ", $ips)."</i></div>";}
		
		
		
		$linkMAC=$boot->trswitch("Loadjs('$page?NewMacLink-js=yes&MAC=$macencoded')");
		$linkVisit=$boot->trswitch("Loadjs('miniadm.squid.macbrowser.php?visits-day-js=$macencoded')");
		
		
		$tr[]="
		<tr id='$id'>
		<td $linkMAC><i class='icon-user'></i> {$ligne["MAC"]}</a>$explainMac</td>
		<td $linkVisit nowrap><i class='icon-user'></i> {$ligne["hostname"]}</a></td>
		<td $linkVisit nowrap><i class='icon-user'></i> {$ligne["uid"]}</td>
		<td style='text-align:center'>$delete</td>
		</tr>";


	}
	echo $tpl->_ENGINE_parse_body("
		<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>{MAC}</th>
					<th>{hostname}</th>
					<th>{uid}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
			</table>
			";
}