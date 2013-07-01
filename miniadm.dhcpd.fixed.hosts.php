<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!$_SESSION["ASDCHPAdmin"]){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["search-records"])){list_nets();exit;}
if(isset($_GET["modify-dhcpd-settings-js"])){host_edit_js();exit;}
if(isset($_GET["modify-dhcpd-settings-popup"])){host_edit_popup();exit;}
if(isset($_GET["modify-dhcpd-settings-tab"])){host_edit_tabs();exit;}
if(isset($_GET["modify-dhcpd-advoptions-popup"])){host_edit_options();exit;}
if(isset($_POST["new-mac"])){host_new();exit;}
if(isset($_POST["edit-mac"])){host_edit();exit;}
if(isset($_POST["host-delete"])){hosts_delete();exit;}
if(isset($_POST["host-add"])){host_add();exit;}
if(isset($_POST["edit-mac-adv"])){host_edit_advanced();exit;}


page();

function host_edit_js(){
	header("content-type: application/x-javascript");
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$prefix="modify-dhcpd-settings-popup";
	if($mac==null){
		$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	}else{
		$sql="SELECT hostname FROM dhcpd_fixed WHERE `mac`='{$_GET["mac"]}'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$new_computer=":{$ligne["hostname"]}";
		$prefix="modify-dhcpd-settings-tab";
		
	}
	$html="YahooWin5('650','$page?$prefix=yes&mac=$mac&t=$tt','$mac$new_computer')";
	echo $html;
}
function host_edit_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$bootstrap=new boostrap_form();
	if($mac<>null){
		$bt="{apply}";
		$sql="SELECT * FROM dhcpd_fixed WHERE `mac`='{$_GET["mac"]}'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$bootstrap->set_hidden('edit-mac',"{$_GET["mac"]}");
	}else{
		$bt="{add}";
		$bootstrap->set_field("new-mac", "{ComputerMacAddress}", null,array("MANDATORY"=>true));
		$bootstrap->set_field("domain", "{domain}", null,array("MANDATORY"=>true));
	}

	$bootstrap->set_button($bt);
	$bootstrap->set_field("hostname", "{hostname}", $ligne["hostname"],array("MANDATORY"=>true));
	$bootstrap->set_field("ipaddr", "{ipaddr}", $ligne["ipaddr"],array("MANDATORY"=>true));
	$bootstrap->set_RefreshSearchs();
	echo $tpl->_ENGINE_parse_body($bootstrap->Compile());



}

function host_edit_options(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$bootstrap=new boostrap_form();	
	$q=new mysql();
	if(!$q->FIELD_EXISTS('dhcpd_fixed','routers','artica_backup')){
		$sql="ALTER TABLE `dhcpd_fixed` ADD `routers` VARCHAR( 128 ),
					`time-servers` VARCHAR( 128 ),
					`domain-name-servers` VARCHAR( 255 ),
					`ntp-servers`  VARCHAR( 255 ),
					`local-pac-server` VARCHAR( 255 )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	}
	
	$sql="SELECT * FROM dhcpd_fixed WHERE `mac`='{$_GET["mac"]}'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$bootstrap->set_formdescription("{dhcp_host_explain_options}");
	$bootstrap->set_button("{apply}");
	$bootstrap->set_hidden("edit-mac-adv", $mac);
	$bootstrap->set_field("routers", "{gateway}", $ligne["routers"]);
	$bootstrap->set_field("time-servers", "{time-servers}", $ligne["time-servers"]);
	$bootstrap->set_field("domain-name-servers", "{domain-name-servers}", $ligne["domain-name-servers"]);
	$bootstrap->set_field("ntp-servers", "{ntp-servers}", $ligne["ntp-servers"]);
	$bootstrap->set_field("local-pac-server", "{local-pac-server}", $ligne["local-pac-server"]);
	$bootstrap->set_RefreshSearchs();
	echo $tpl->_ENGINE_parse_body($bootstrap->Compile());	
	
}

function host_edit_advanced(){
	$ip=new IP();
	$tpl=new templates();
	
	$q=new mysql();
	$FIELDS["routers"]=128;
	$FIELDS["time-servers"]=128;
	$FIELDS["domain-name-servers"]=255;
	$FIELDS["ntp-servers"]=255;
	$FIELDS["local-pac-server"]=255;
	while (list ($field, $value) = each ($FIELDS) ){
		if(!$q->FIELD_EXISTS('dhcpd_fixed',$field,'artica_backup')){
			$sql="ALTER TABLE `dhcpd_fixed` ADD `$field` VARCHAR( $value )";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
		}
	}
	
	
	
	
	$mac=$_POST["edit-mac-adv"];
	if(!IsPhysicalAddress($mac)){echo $tpl->javascript_parse_text("{invalid_mac}: $mac");return;}
	unset($_POST["edit-mac-adv"]);
	while (list ($key, $value) = each ($_POST) ){
		$f[]="`$key`='$value'";
	}
	
	$sql="UPDATE dhcpd_fixed SET ".@implode(", ", $f)." WHERE mac='$mac'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");	
}

function  host_edit_tabs(){
	$mac=$_GET["mac"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$title="<H3>$mac</H3>";
	$boot=new boostrap_form();
	
	$array["{settings}"]="$page?modify-dhcpd-settings-popup=yes&mac=$mac";
	$array["{advanced_options}"]="$page?modify-dhcpd-advoptions-popup=yes&mac=$mac";
	$html="$title".$boot->build_tab($array);
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$boot=new boostrap_form();
	$link_computer=$tpl->_ENGINE_parse_body("{link_computer}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$t=time();
	
	$OPTIONS["BUTTONS"][]=button($link_computer,"AddHost$t()",16);
	$OPTIONS["BUTTONS"][]=button($new_computer,"NewHost$t()",16);
	
	
	$SearchQuery=$boot->SearchFormGen("hostname,ipaddr,mac","search-records",null,$OPTIONS);	
	echo $SearchQuery."
			<script>
function NewHost$t(){
	Loadjs('$page?modify-dhcpd-settings-js=yes&t=$t');
}
function AddHost$t(){
	Loadjs('computer-browse.php?callback=DHCPDfixedHostsAdd&OnlyOCS=1&CorrectMac=1&fullvalues=1');
}	

var x_DHCPDfixedHostsAdd= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	FlexReloadDHCP();
}	

function FlexReloadDHCP(){
	ExecuteByClassName('SearchFunction');
}


	
function DHCPDfixedHostsAdd(uid,ip,mac){
	var XHR = new XHRConnection();
	XHR.appendData('uid',uid);
	XHR.appendData('ipaddr',ip);
	XHR.appendData('mac',mac);
	XHR.appendData('host-add','yes');
	XHR.sendAndLoad('$page', 'POST',x_DHCPDfixedHostsAdd);
}			
</script>
";
	
}
function list_nets(){


	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];

	$search='%';
	$table="dhcpd_fixed";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	$ORDER="ORDER BY hostname";

	if(!$q->TABLE_EXISTS($table, $database)){ throw new Exception("$table, No such table...",500);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){throw new Exception("No data...",500);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery("search-records");

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	
	if(mysql_num_rows($results)==0){
		senderror("no data");
	}
	$sock=new sockets();
	$cmp=new computers();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$js="zBlur();";
		$herf=null;
		$md5=md5($ligne["mac"]);
		$cmp=new computers();
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		
		if($uid<>null){$js=MEMBER_JS($uid,1,1);}
		
		if(strpos($ligne["hostname"], ".")>0){$ff=explode(".", $ligne["hostname"]);$ligne["hostname"]=$ff[0];}
		$delete=imgsimple("delete-32.png",null,
		"DHCPFixedDelete('{$ligne["mac"]}','$id')");
		
		$Modify="Loadjs('$MyPage?modify-dhcpd-settings-js=yes&mac={$ligne["mac"]}');";
		
		$ligne["hostname"]=strtolower(str_replace("$", "", $ligne["hostname"]));
		$ligne["mac"]=strtoupper($ligne["mac"]);
		$linkUid=$boot->trswitch($js);
		$linkModify=$boot->trswitch($Modify);
		$tr[]="
		<tr id='row$id'>
			<td $linkModify nowrap width=1%><img src='img/24-parameters.png'></td>
			<td $linkModify><i class='icon-globe'></i>&nbsp;{$ligne["hostname"]}</td>
			<td $linkUid><i class='icon-globe'></i>&nbsp;$uid</td>
			<td $linkModify nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["ipaddr"]}</td>
			<td $linkModify nowrap><i class='icon-star'></i>&nbsp;{$ligne["mac"]}</td>
			<td $linkModify nowrap><i class='icon-globe'></i>&nbsp;{$ligne["domain"]}</td>
			<td nowrap>$delete</td>
		</tr>";		


	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{hostname}</th>
					<th>{uid}</th>
					<th>{ipaddr}</th>
					<th>{ComputerMacAddress}</th>
					<th>{domain}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
<script>

	var x_DHCPFixedDelete= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#row'+TMPMD).remove();
		
	}

function DHCPFixedDelete(mac,md){
	TMPMD=md;
	var XHR = new XHRConnection();
	XHR.appendData('host-delete',mac);
	XHR.sendAndLoad('$page', 'POST',x_DHCPFixedDelete);		
}
</script>					
				";

}

function host_edit(){
	$ip=new IP();
	$tpl=new templates();
	if(!$ip->isIPAddress($_POST["ipaddr"])){echo $tpl->javascript_parse_text("{invalid_ipaddr}: {$_POST["ipaddr"]}");return;}
	$sql="UPDATE dhcpd_fixed SET hostname='{$_POST["hostname"]}',ipaddr='{$_POST["ipaddr"]}' WHERE mac='{$_POST["edit-mac"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");

}

function host_new(){
	$ip=new IP();
	$tpl=new templates();
	$mac=$_POST["new-mac"];
	if(!IsPhysicalAddress($mac)){
		echo $tpl->javascript_parse_text("{invalid_mac}: $mac");
		return;}
	
	$sql="SELECT * FROM dhcpd_fixed WHERE `mac`='$mac'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["hostname"]<>null){
		echo $tpl->_ENGINE_parse_body("{already_exists}: $mac [{$ligne["ipaddr"]}] ({$ligne["hostname"]})");
		return;
	}

	$sql="INSERT IGNORE INTO dhcpd_fixed (hostname,ipaddr,domain,mac) VALUES
	('{$_POST["hostname"]}','{$_POST["ipaddr"]}','{$_POST["domain"]}','$mac')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
}
function hosts_delete(){
	$q=new mysql();
	$sql="DELETE FROM dhcpd_fixed WHERE `mac`='{$_POST["host-delete"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");

}
function host_add(){
	$dhcp=new dhcpd(0,1);
	$ip=new IP();
	$tpl=new templates();
	$cmp=new computers();
	if(!$dhcp->IsPhysicalAddress($_POST["mac"])){
		echo "Wrong value {$_POST["mac"]}\n";
		return;
	}
	$_POST["uid"]=str_replace( "$","",$_POST["uid"]);
	$_POST["ipaddr"]=str_replace( "$", "",$_POST["ipaddr"]);
	
	if(!$ip->isIPAddress($_POST["ipaddr"])){
		echo $tpl->javascript_parse_text("{invalid_ipaddr}: {$_POST["ipaddr"]}");
		return;
	
	}	

	$q=new mysql();
	$sql="SELECT hostname FROM dhcpd_fixed WHERE mac='{$_POST["mac"]}'";
	$ligneCK=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligneCK["hostname"]<>null){
	
		echo $tpl->javascript_parse_text("{this_computer_already_exists}\n{hostname}:{$ligneCK["hostname"]} ({$_POST["mac"]})",1);
		return;
	}



	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $_POST["ipaddr"])){
		echo "Wrong value {$_POST["ipaddr"]}\n";
		return;
	}

	$uid=$cmp->ComputerIDFromMAC($_POST["mac"]);
	if($uid<>null){
		$cmp=new computers($uid);
		$domain=$cmp->DnsZoneName;
		if($cmp->ComputerRealName<>null){$_POST["hostname"]=$cmp->ComputerRealName;}
	}
	if($domain<>null){$domain=$dhcp->ddns_domainname;}
	if($domain==null){echo "DNS domain is null";}
	$sql="INSERT INTO dhcpd_fixed (mac,ipaddr,hostname,domain) VALUES
	('{$_POST["mac"]}','{$_POST["ipaddr"]}','{$_POST["hostname"]}','$domain')";

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
}