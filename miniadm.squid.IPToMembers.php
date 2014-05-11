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
if(isset($_GET["NewipaddrLink-js"])){NewipaddrLink_js();exit;}
if(isset($_GET["NewipaddrLink-popup"])){NewipaddrLink_popup();exit;}
if(isset($_POST["save-ipaddr"])){NewipaddrLink_save();exit;}
if(isset($_GET["delete-ipaddr-js"])){delete_js();exit;}
if(isset($_POST["delete-ipaddr"])){delete_mac();exit;}
if(isset($_POST["apply-changes"])){apply_changes();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["import"])){import_save();exit;}


content();

function content(){
	$sock=new sockets();
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$button=button("{link_ip_to_uid}","NewipaddrLink$t()",16);
	$button3=button("{import}","import$t()",16);
	$button2=button("{apply_changes}","Apply$t()",16);
	$import=$tpl->javascript_parse_text("{import}");
	$SearchQuery=$boot->SearchFormGen("ipaddr,uid,hostname","search-records");
	$apply_changes_mactouid_explain=$tpl->javascript_parse_text("{apply_changes_mactouid_explain}");
	$html="
	<div class=explain>{ipaddrtouid_explain}</div>
	<table style='width:100%'>
	<tr>
	<td>$button $button3 $button2</td>
	<td></td>
	</tr>
	</table>
	$SearchQuery
	<script>
	ExecuteByClassName('SearchFunction');
	
	function NewipaddrLink$t(){
		Loadjs('$page?NewipaddrLink-js=yes')
	
	}
	var x_Apply$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		ExecuteByClassName('SearchFunction');
	}		
	
	function Apply$t(){
		if(!confirm('$apply_changes_mactouid_explain')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('apply-changes','yes');
		XHR.sendAndLoad('$page', 'POST',x_Apply$t);			
		
	}
	
	function import$t(){
		YahooWin2('700','$page?import-popup=yes','$import',true);
	
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

function import_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$MacToUid_import_explain=$tpl->_ENGINE_parse_body("{MacToUid_import_explain}");
	$import=$tpl->javascript_parse_text("{import}");
	$html="
	<table style='width:100%'>
	
	<td style='vertical-align:top'>
	<div class=explain style='font-size:18px'>$MacToUid_import_explain</div>
	</td>
	</tr>
	</table>
	<center style='margin:10px'>
	<textarea style='font-family:Courier New;
	font-weight:bold;width:100%;
	height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;' id='Content-$t'></textarea>
	
	
	<center style='margin:20px'>". button($import, "Save$t()",22)."</center>
	</center>
	</div>
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;};
		ExecuteByClassName('SearchFunction');
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('import',encodeURIComponent(document.getElementById('Content-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	
	";
	echo $html;	
	
	
}

function import_save(){
	$_POST["import"]=url_decode_special_tool($_POST["import"]);
	
	$prefix="INSERT IGNORE INTO webfilters_ipaddr (ipaddr,hostname,uid,ip) VALUES ";
	$f=explode("\n",$_POST["import"]);
	while (list ($a, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		$t=explode(";",$line);
		$hostname=null;
		if($t[0]==null){continue;}
		if($t[1]==null){continue;}
		if($t[2]<>null){$hostname=$t[2];}
		$ip2Long2=ip2Long2($t[1]);
		$n[]="('{$t[1]}','{$hostname}','{$t[0]}','$ip2Long2')";
		
	}
	
	if(count($n)>0){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($prefix.@implode(",", $n));
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	
}

function NewipaddrLink_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$_GET["ipaddr"]=trim($_GET["ipaddr"]);
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{link_ip_to_uid}");
	if($_GET["ipaddr"]<>null){
		$title=$tpl->javascript_parse_text("{link_ip_to_uid}::{$_GET["ipaddr"]}");
	}
	
	echo "YahooWin2('700','$page?NewipaddrLink-popup=yes&ipaddr={$_GET["ipaddr"]}','$title')";
	
	
}
function delete_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$_GET["delete-ipaddr-js"]=trim($_GET["delete-ipaddr-js"]);
	$id=$_GET["id"];
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{delete}: {$_GET["delete-ipaddr-js"]} ?");
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
			XHR.appendData('delete-ipaddr','{$_GET["delete-ipaddr-js"]}');
			XHR.sendAndLoad('$page', 'POST',x_delete$t);			
			
		
		}
	delete$t()";


}
function delete_mac(){
	$sql="DELETE FROM webfilters_ipaddr WHERE ipaddr='{$_POST["delete-ipaddr"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}



function NewipaddrLink_popup(){
	$tpl=new templates();
	$_GET["ipaddr"]=trim($_GET["ipaddr"]);
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_ipaddr WHERE ipaddr='{$_GET["ipaddr"]}'"));
	$boot=new boostrap_form();
	if($_GET["ipaddr"]==null){
			$boot->set_field("ipaddr", "{ipaddr}", null,array("MANDATORY"=>true,"IPV4"=>true));
	}else{
		$boot->set_formtitle("{ipaddr}:{$_GET["ipaddr"]}");
		$boot->set_hidden("ipaddr", $_GET["ipaddr"]);
		
		$ips=array();
		
		$linkstats="<div style='width:100%'>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:miniadm.squid.ipaddrbrowser.php?visits-day-js={$_GET["ipaddr"]}');>{statistics}</a></div>";
			
		
	}
	$linkstats=null;
	$boot->set_hidden("save-ipaddr", 'yes');
	$boot->set_field("uid", "{member}", $ligne["uid"],array("MANDATORY"=>true));
	$boot->set_field("hostname", "{hostname}", $ligne["hostname"]);
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function NewipaddrLink_save(){
	
	$ADDR=explode(".",$_POST["ipaddr"]);
	while (list ($a, $b) = each ($ADDR) ){$ADDR[$a]=intval($b);}
	$_POST["ipaddr"]=@implode(".", $ADDR);
	$_POST["hostname"]=strtolower($_POST["hostname"]);
	
	$ip2Long2=ip2Long2($_POST["ipaddr"]);
	
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS('webfilters_ipaddr')){
		$sql="CREATE TABLE `squidlogs`.`webfilters_ipaddr` (
			`ipaddr` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`uid` VARCHAR( 128 ) NOT NULL ,
			 `ip` int(10) unsigned NOT NULL default '0',
			`hostname` VARCHAR( 128 ) NOT NULL,
			 INDEX ( `uid`,`hostname`)
			)  ENGINE = MYISAM;";
	
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}	
	
	if(!$q->FIELD_EXISTS("webfilters_ipaddr", "ip")){$q->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `ip` int(10) unsigned NOT NULL default '0',ADD INDEX ( `ip` )");}
	$q->CheckTables();
	$_POST["uid"]=$q->StripBadChars_hostname($_POST["uid"]);
	$_POST["hostname"]=$q->StripBadChars_hostname($_POST["hostname"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM webfilters_ipaddr WHERE ipaddr='{$_POST["ipaddr"]}'"));
	if($ligne["ipaddr"]<>null){
		$sql="UPDATE webfilters_ipaddr SET uid='{$_POST["uid"]}',hostname='{$_POST["hostname"]}',`ip`='$ip2Long2' WHERE ipaddr='{$_POST["ipaddr"]}'";
	}else{
		
		$sql="INSERT IGNORE INTO webfilters_ipaddr (ipaddr,hostname,uid,ip) VALUES ('{$_POST["ipaddr"]}','{$_POST["hostname"]}','{$_POST["uid"]}','$ip2Long2');";
		
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
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_ipaddr", "ip")){$q->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `ip` int(10) unsigned NOT NULL default '0',ADD INDEX ( `ip` )");}
	$boot=new boostrap_form();
	$searchstring=string_to_flexquery("search-records");
	$ORDER=$boot->TableOrder(array("ip"=>"ASC"));
	$sql="SELECT *  FROM webfilters_ipaddr WHERE 1 $searchstring  ORDER BY $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql);
	$sock=new sockets();
	
	
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
		$macencoded=urlencode($ligne["ipaddr"]);
		$delete=imgsimple("delete-24.png",null,"Loadjs('$page?delete-ipaddr-js=$macencoded&id=$id')");
		$jshost="NewPDNSEntry$t($id);";
		

		
		$linkipaddr=$boot->trswitch("Loadjs('$page?NewipaddrLink-js=yes&ipaddr=$macencoded')");
		
		
		
		$tr[]="
		<tr id='$id'>
		<td $linkipaddr width=1% nowrap><i class='icon-user'></i> {$ligne["ipaddr"]}</a></td>
		<td nowrap $linkipaddr><i class='icon-user'></i> {$ligne["hostname"]}</a></td>
		<td nowrap $linkipaddr width=1% nowrap><i class='icon-user'></i> {$ligne["uid"]}</td>
		<td style='text-align:center' width=1% nowrap>$delete</td>
		</tr>";


	}
	
	$html=$boot->TableCompile(
			array("ip"=>"{ipaddr}",
				"hostname"=>"{hostname}",
				"uid"=>"{uid}",
				
				"delete:no"=>"{delete}",
				
			),
			$tr
			);
	
	echo $tpl->_ENGINE_parse_body($html);
}