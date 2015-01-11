<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

$users=new usersMenus();
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
	}
	
	
	if(isset($_GET["now-search"])){hosts_list();exit;}
	if(isset($_POST["host-delete"])){hosts_delete();exit;}
	if(isset($_POST["host-add"])){host_add();exit;}
	if(isset($_GET["modify-dhcpd-settings-js"])){host_edit_js();exit;}
	if(isset($_GET["modify-dhcpd-settings-popup"])){host_edit_popup();exit;}
	
	if(isset($_POST["new-mac"])){host_new();exit;}
	if(isset($_POST["edit-mac"])){host_edit();exit;}
	
	
popup();	
	
function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}

function host_edit_js(){
	header("content-type: application/x-javascript");
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if(isset($_GET["increment"])){$_GET["increment"]=urlencode($_GET["increment"]);}
	if($mac==null){	
		$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
		
	}
	$html="YahooWin5('900','$page?modify-dhcpd-settings-popup=yes&mac=$mac&t=$tt&increment={$_GET["increment"]}','$mac$new_computer')";
	echo $html;
}

function host_edit_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$fixedHosts=$tpl->_ENGINE_parse_body("{dhcpfixed}");
	if($mac<>null){
		$bt="{apply}";
		$sql="SELECT * FROM dhcpd_fixed WHERE `mac`='{$_GET["mac"]}'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		$title="<div style='margin:20px;font-size:30px'>{$ligne["hostname"]}
		<div style='width:100%;text-align:left;font-size:18px'><i>DHCP:$fixedHosts</i></div>
		</div>
		";
		
	}else{
		
		$title="<div style='margin:20px;font-size:30px'>$new_computer
		<div style='width:100%;text-align:left;font-size:18px'><i>DHCP:$fixedHosts</i></div>
		</div>
		";

		if($_GET["increment"]<>null){
			$ligne=unserialize($_GET["increment"]);
			
		}
		
		$bt="{add}";
		$mac_form="
	
		<tr>
			<td class=legend style='font-size:22px'>{ComputerMacAddress}:</td>
			<td>". Field_text("MAC-$t",$ligne["mac"],"font-size:22px;width:250px")."</td>
			<td>&nbsp;</td>
		</tr>

		";
	}

	

	$html="
	$title
	<div id='$t' style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{hostname}:</td>
			<td>". Field_text("hostname-$t",$ligne["hostname"],"font-size:22px;width:370px")."</td>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:22px'>{ipaddr}:</td>
			<td>". field_ipv4("ipaddr-$t",$ligne["ipaddr"],"font-size:22px")."</td>
			<td>&nbsp;</td>
		</tr>
		$mac_form
		<tr>
			<td class=legend style='font-size:22px'>{domain}:</td>
			<td>". Field_text("domain-$t",$ligne["domain"],"font-size:22px;width:370px")."</td>
			<td>&nbsp;</td>
		</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{gateway}:</td>
		<td>".field_ipv4("routers-$t",$ligne["routers"],'font-size:22px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{DNSServer} 1:</td>
		<td>".field_ipv4("domain-name-servers1-$t",$ligne["domain-name-servers"],'font-size:22px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DNSServer} 2:</td>
		<td>".field_ipv4("domain-name-servers2-$t",$ligne["domain-name-servers-2"],'font-size:22px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=32 align='right'><hr>". button("$bt","SaveCMP$t()","30")."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var x_SaveCMP$t=function(obj){
		  var results=trim(obj.responseText);
		  
		  if(results.length>2){alert(results);return;} 
	      YahooWin5Hide();
	      
	      if(document.getElementById('DHCP_REQUESTS_TABLE')){
	      	$('#DHCP_REQUESTS_TABLE').flexReload();
	      }
	      
	      $('#flexRT$tt').flexReload();
		}	
		
		function SaveCMP$t(){
		  var XHR = new XHRConnection();
	      XHR.appendData('hostname',document.getElementById('hostname-$t').value);   
	      XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value); 
	      XHR.appendData('routers',document.getElementById('routers-$t').value);   
	      XHR.appendData('domain-name-servers',document.getElementById('domain-name-servers1-$t').value);
	      XHR.appendData('domain-name-servers2',document.getElementById('domain-name-servers2-$t').value);   
	      XHR.appendData('domain',document.getElementById('domain-$t').value);
	      
	      if(document.getElementById('MAC-$t')){
	      	XHR.appendData('new-mac',document.getElementById('MAC-$t').value);  
	      }else{
	      	XHR.appendData('edit-mac','{$_GET["mac"]}');  
	      }
	      
	      	      
	      
	      
	    
	      XHR.sendAndLoad('$page', 'POST',x_SaveCMP$t);       
		  }
	</script>
		";
	
echo $tpl->_ENGINE_parse_body($html);
	

	
}

function host_edit(){
	
	$_POST["edit-mac"]=str_replace("-", ":", $_POST["edit-mac"]);
	$mac=trim(strtolower($_POST["edit-mac"]));
	if(!IsPhysicalAddress($mac)){echo "host_edit():: $mac!! pattern failed\n";return;}
	
	$q=new mysql();
	$tpl=new templates();
	if(!$q->FIELD_EXISTS('dhcpd_fixed',"domain-name-servers-2",'artica_backup')){
		$sql="ALTER TABLE `dhcpd_fixed` ADD `domain-name-servers-2` VARCHAR( 90 )";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT mac FROM dhcpd_fixed WHERE `mac`='{$_POST["edit-mac"]}'","artica_backup"));
	if(trim($ligne["mac"])==null){
		$_POST["new-mac"]=$mac;
		host_new();
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_fixed WHERE `ipaddr`='{$_POST["ipaddr"]}'","artica_backup"));
	if(trim($ligne["mac"])<>null){
		if($ligne["mac"]<>$_POST["edit-mac"]){
			echo $tpl->javascript_parse_text("host_edit({$_POST["edit-mac"]}):: {ipaddr}:{$_POST["ipaddr"]} {already_exists}: [{$ligne["mac"]}]");
			return;
		}
	}
	
	
	
	$sql="UPDATE dhcpd_fixed SET 
		hostname='{$_POST["hostname"]}',
		ipaddr='{$_POST["ipaddr"]}' ,
		`routers`='{$_POST["routers"]}',
		`domain-name-servers`='{$_POST["domain-name-servers"]}',
		`domain-name-servers-2`='{$_POST["domain-name-servers2"]}',
		`domain`='{$_POST["domain"]}'
		WHERE mac='{$_POST["edit-mac"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$cp=new computers();
	$uid=$cp->ComputerIDFromMAC($_POST["edit-mac"]);
	
	if($uid<>null){
		$cp=new computers($uid);
		$cp->ComputerIP=$_POST["ipaddr"];
		$cp->ComputerRealName=$_POST["hostname"];
		$cp->DnsZoneName=$_POST["domain"];
		$cp->ComputerMacAddress=$_POST["edit-mac"];
		$cp->Edit();
	}
	
	if(!isset($GLOBALS["APPLY_DHCP"])){
		$GLOBALS["APPLY_DHCP"]=true;
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
	}	
	
}

function host_new(){
	$_POST["new-mac"]=str_replace("-", ":", $_POST["new-mac"]);
	$mac=trim(strtolower($_POST["new-mac"]));
	if(!IsPhysicalAddress($mac)){echo "$mac!! failed\n";return;}
	$tpl=new templates();

	$q=new mysql();	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_fixed WHERE `mac`='$mac'","artica_backup"));
	if($ligne["hostname"]<>null){
		echo $tpl->javascript_parse_text("host_new():: {already_exists}: $mac [{$ligne["ipaddr"]}] ({$ligne["hostname"]})");
		return;
	}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_fixed WHERE `ipaddr`='{$_POST["ipaddr"]}'","artica_backup"));
	if($ligne["mac"]<>null){
		echo $tpl->javascript_parse_text("host_new():: {already_exists}: [{$ligne["ipaddr"]}] ({$ligne["mac"]})");
		return;
	}
	
	
	
	$sql="INSERT IGNORE INTO dhcpd_fixed (
		`hostname`,
		`ipaddr`,
		`domain`,
		`mac`,
		`routers`,
		`domain-name-servers`,
		`domain-name-servers-2`
		
		) VALUES
			(
			'{$_POST["hostname"]}',
			'{$_POST["ipaddr"]}',
			'{$_POST["domain"]}',
			'$mac',
			'{$_POST["routers"]}',
			'{$_POST["domain-name-servers"]}',
			'{$_POST["domain-name-servers2"]}'
			
	)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n\n$sql\n";return;}
	
	if(!isset($GLOBALS["APPLY_DHCP"])){
		$GLOBALS["APPLY_DHCP"]=true;
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
	}		
}

	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostname=$tpl->javascript_parse_text("{fixedHosts}");
	$description=$tpl->javascript_parse_text("{description}");
	$title=$tpl->javascript_parse_text($title);
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$link_computer=$tpl->javascript_parse_text("{link_computer}");
	$new_computer=$tpl->javascript_parse_text("{new_computer}");
	$gateway=$tpl->javascript_parse_text("{gateway}");
	$dns=$tpl->javascript_parse_text("{primary_dns}");
	$buttons="
	buttons : [
	{name: '$link_computer', bclass: 'add', onpress : AddHost},
	{name: '$new_computer', bclass: 'add', onpress : NewHost},
	
	
	],";		
		
	

$html="
$explain
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
var TMPMD='';
$('#flexRT$t').flexigrid({
	url: '$page?now-search=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 45, sortable : false, align: 'center'},
		{display: '$hostname', name : 'hostname', width : 194, sortable : false, align: 'left'},	
		{display: '$ComputerMacAddress', name : 'mac', width :142, sortable : true, align: 'left'},
		{display: '$addr', name : 'ipaddr', width :142, sortable : true, align: 'left'},
		{display: '$gateway', name : 'routers', width :142, sortable : true, align: 'left'},
		{display: '$dns', name : 'domain-name-servers', width :142, sortable : true, align: 'left'},
		{display: '$domain', name : 'domain', width : 199, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 45, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$addr', name : 'ipaddr'},
		{display: '$ComputerMacAddress', name : 'mac'},
		{display: '$gateway', name : 'routers'},
		{display: '$dns', name : 'domain-name-servers'},
		{display: '$domain', name : 'domain'}
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_DHCPFixedDelete= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#row'+TMPMD).remove();
		
	}
	
	var x_DHCPDfixedHostsAdd= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		FlexReloadDHCP();
		
	}	

	function FlexReloadDHCP(){
		$('#flexRT$t').flexReload();
	}

function DHCPFixedDelete(mac,md){
		TMPMD=md;
		var XHR = new XHRConnection();
		XHR.appendData('host-delete',mac);
		XHR.sendAndLoad('$page', 'POST',x_DHCPFixedDelete);		
	
	
}

function DHCPDfixedHostsAdd(uid,ip,mac){
		var XHR = new XHRConnection();
		XHR.appendData('uid',uid);
		XHR.appendData('ipaddr',ip);
		XHR.appendData('mac',mac);
		XHR.appendData('host-add','yes');
		XHR.sendAndLoad('$page', 'POST',x_DHCPDfixedHostsAdd);

	
}

function NewHost(){
	Loadjs('$page?modify-dhcpd-settings-js=yes&t=$t');
}

function AddHost(){
	Loadjs('computer-browse.php?callback=DHCPDfixedHostsAdd&OnlyOCS=1&CorrectMac=1&fullvalues=1');
}



function AddBySquidGroup(){
	YahooWin5('550','$page?squid-groups=yes&blk={$_GET["blk"]}','$squidGroup');

}

function AddBySquidGroupWWW(){
	YahooWin5('550','$page?squid-groups=yes&blk={$_GET["blk"]}','$squidGroup');
}


function AddByIPAdr(){
	var mac=prompt('$addr:$acl_src_text');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',0);
		XHR.appendData('blk','');
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
	
}
function AddByWebsite(){
	var mac=prompt('$AddWWW:$squid_ask_domain');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',0);
		XHR.appendData('blk','');
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);		
	}
	
}


function BlksProxyDelete(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('delete-pattern',pattern);
		XHR.sendAndLoad('$page', 'POST',x_AddByMac);
}

function BlksProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.sendAndLoad('$page', 'POST');
}

</script>

";	
	echo $html;
	
}	

function hosts_delete(){
	$q=new mysql();
	$sql="DELETE FROM dhcpd_fixed WHERE `mac`='{$_POST["host-delete"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if(!isset($GLOBALS["APPLY_DHCP"])){
		$GLOBALS["APPLY_DHCP"]=true;
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
	}	
	
}

function hosts_import(){
	
	
	

	$prefix="INSERT IGNORE INTO dhcpd_fixed (`mac`,`ipaddr`,`hostname`,`domain`) VALUES ";
	
	
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($num))){continue;}
		$MAC=$ligne["MAC"];
		$MAC=strtolower(trim(str_replace("hardware ethernet","",$MAC)));
		$hostname=$num;
		$ipaddr=$ligne["IP"];
		$ipaddr=trim(str_replace("fixed-address", "", $ipaddr));
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$hostname)){continue;}
		$domainname=$ligne["domainname"];
		$f[]="('$MAC','$ipaddr','$hostname','$domainname')";
		
	}	
	
	if(count($f)>0){
		$q=new mysql();
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->SET_INFO("DHCPDOlFixedImported", 1);
	}
	
	
}




function host_add(){
	$dhcp=new dhcpd(0,1);
	$cmp=new computers();
	if(!$dhcp->IsPhysicalAddress($_POST["mac"])){
		echo "Wrong value {$_POST["mac"]}\n";
		return;
	}
	$_POST["uid"]=str_replace( "$","",$_POST["uid"]);
	$_POST["ipaddr"]=str_replace( "$", "",$_POST["ipaddr"]);
	
	$q=new mysql();
	$sql="SELECT hostname FROM dhcpd_fixed WHERE mac='{$_POST["mac"]}'";
	$ligneCK=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligneCK["hostname"]<>null){
		$tpl=new templates();
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
	
	$sql="INSERT INTO dhcpd_fixed (mac,ipaddr,hostname,domain) VALUES 
	('{$_POST["mac"]}','{$_POST["ipaddr"]}','{$_POST["hostname"]}','$domain')";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if(!isset($GLOBALS["APPLY_DHCP"])){
		$GLOBALS["APPLY_DHCP"]=true;
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?apply-dhcpd=yes");
	}	
}



function hosts_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$Importold=$sock->GET_INFO("DHCPDOlFixedImported");
	if(!is_numeric($Importold)){$Importold=0;}
	if($Importold==0){hosts_import();}
	$q=new mysql();
	$tt=$_GET["t"];
	
	$search='%';
	$table="dhcpd_fixed";
	$page=1;
	$_POST["query"]=trim($_POST["query"]);
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data"); return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(mysql_num_rows($results)==0){json_error_show("No item<hr>$sql");}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$jsfiche=null;
		$herf=null;
		$md5=md5($ligne["mac"]);
		$cmp=new computers();
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		$routers=$ligne["routers"];
		$domain_name_servers=$ligne["domain-name-servers"];
		$fiche=imgsimple("computer-32-grey.png",null,null);
		
		
		if($uid<>null){
			$jsfiche=MEMBER_JS($uid,1,1);
			$fiche=imgsimple("computer-32.png",null,$jsfiche);
			
			
		}
		
		
		
		if(strpos($ligne["hostname"], ".")>0){$ff=explode(".", $ligne["hostname"]);$ligne["hostname"]=$ff[0];}
		$delete=imgtootltip("delete-32.png","{delete} {$ligne["mac"]}","DHCPFixedDelete('{$ligne["mac"]}','$md5')");
		
		$Modify="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?modify-dhcpd-settings-js=yes&mac={$ligne["mac"]}&t=$tt');\" 
		style='font-size:16px;text-decoration:underline;font-weight:bold'>";
		
		$ligne["hostname"]=strtolower(str_replace("$", "", $ligne["hostname"]));
		$ligne["mac"]=strtoupper($ligne["mac"]);
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
				$fiche,
		"<span style='font-size:16px'>$Modify{$ligne["hostname"]}</a></span>"
		,"<span style='font-size:16px'>$Modify{$ligne["mac"]}</a></span>",
		"<span style='font-size:16px'>$Modify{$ligne["ipaddr"]}</a></span>",
		"<span style='font-size:16px'>$Modify{$ligne["routers"]}</a></span>",
		"<span style='font-size:16px'>$Modify{$domain_name_servers}</a></span>",
		"<span style='font-size:16px'>{$ligne["domain"]}</a></span>",
		$delete )
		);
	}
	
	if(count($data['rows'])==0){json_error_show("no data");}
echo json_encode($data);		

}