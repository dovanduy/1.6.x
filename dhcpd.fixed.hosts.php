<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){		
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
	


function host_edit_js(){
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if($mac==null){	
		$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	}
	$html="YahooWin5('550','$page?modify-dhcpd-settings-popup=yes&mac=$mac&t=$tt','$mac$new_computer')";
	echo $html;
}

function host_edit_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$mac=$_GET["mac"];
	$tt=$_GET["t"];
	if($mac<>null){
		$bt="{apply}";
		$sql="SELECT * FROM dhcpd_fixed WHERE `mac`='{$_GET["mac"]}'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}else{
		$bt="{add}";
		$mac_form="<tr>
			<td class=legend style='font-size:16px'>{ComputerMacAddress}:</td>
			<td>". Field_text("MAC-$t",null,"font-size:16px;width:220px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{domain}:</td>
			<td>". Field_text("domain-$t",null,"font-size:16px;width:220px")."</td>
		</tr>
		";
	}
	
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{hostname}:</td>
			<td>". Field_text("hostname-$t",$ligne["hostname"],"font-size:16px;width:220px")."</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:16px'>{ipaddr}:</td>
			<td>". field_ipv4("ipaddr-$t",$ligne["ipaddr"],"font-size:16px")."</td>
		</tr>
		$mac_form	
		<tr>
		<td colspan=2 align='right'><hr>". button("$bt","SaveCMP$t()","16px")."</td>
	</tr>
	</table>
	
	<script>
	
	var x_SaveCMP$t=function(obj){
		  var results=trim(obj.responseText);
		  document.getElementById('$t').innerHTML='';
		  if(results.length>2){alert(results);return;} 
	      YahooWin5Hide();
	      $('#flexRT$tt').flexReload();
		}	
		
		function SaveCMP$t(){
		  var XHR = new XHRConnection();
	      XHR.appendData('hostname',document.getElementById('hostname-$t').value);   
	      XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value); 
	      if(document.getElementById('MAC-$t')){
	      	XHR.appendData('new-mac',document.getElementById('MAC-$t').value);  
	      }else{
	      	XHR.appendData('edit-mac','{$_GET["mac"]}');  
	      }
	      
	      if(document.getElementById('domain-$t')){
	      	XHR.appendData('domain',document.getElementById('domain-$t').value);  
	      }	      
	      
	      
	      AnimateDiv('$t'); 
	      XHR.sendAndLoad('$page', 'POST',x_SaveCMP$t);       
		  }
	</script>
		";
	
echo $tpl->_ENGINE_parse_body($html);
	

	
}

function host_edit(){
	
	$sql="UPDATE dhcpd_fixed SET hostname='{$_POST["hostname"]}',ipaddr='{$_POST["ipaddr"]}' WHERE mac='{$_POST["edit-mac"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");		
	
}

function host_new(){
	$mac=$_POST["new-mac"];
	if(!IsPhysicalAddress($mac)){echo "$mac!! failed\n";return;}
	$tpl=new templates();
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

	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{fixedHosts}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$title=$tpl->_ENGINE_parse_body($title);
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$link_computer=$tpl->_ENGINE_parse_body("{link_computer}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$buttons="
	buttons : [
	{name: '$link_computer', bclass: 'add', onpress : AddHost},
	{name: '$new_computer', bclass: 'add', onpress : NewHost},
	
	
	],";		
		
	
if($explain<>null){$explain="<div class=explain style='font-size:13px'>$explain</div>";}	
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
		{display: '$hostname', name : 'hostname', width : 247, sortable : false, align: 'left'},	
		{display: '$ComputerMacAddress', name : 'mac', width :130, sortable : true, align: 'left'},
		{display: '$addr', name : 'ipaddr', width :130, sortable : true, align: 'left'},
		{display: '$domain', name : 'domain', width : 246, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$addr', name : 'ipaddr'},
		{display: '$ComputerMacAddress', name : 'mac'},
		{display: '$domain', name : 'domain'}
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 865,
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
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");	
	
}

function hosts_import(){
	
	$array=LoadOldfixedAddresses();
	

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
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->SET_INFO("DHCPDOlFixedImported", 1);
	}
	
	
}


function LoadOldfixedAddresses(){
		$ldap=new clladp();
		$dhcpd=new dhcpd();
		$IP=new IP();	
		$filter=array("computerip","ComputerMacAddress","cn","uid","DnsZoneName");
		$query="(&(objectClass=ArticaComputerInfos)(DnsZoneName=$dhcpd->ddns_domainname))";
		$hash=$ldap->Ldap_search($ldap->suffix,$query,$filter);
		$count=$hash["count"];
		$ARR=array();
		
		if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {writelogs("LoadfixedAddresses:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}			
		
		writelogs("compile {$hash["count"]} computers for $dhcpd->ddns_domainname $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		for($i=0;$i<$hash["count"];$i++){
			if($GLOBALS["VERBOSE"]){echo "->LoadfixedAddresses() ->". $hash[$i]["uid"][0]." item Line ".__LINE__."\n";}
			$uid=str_replace('$','',$hash[$i]["uid"][0]);
			if($uid==null){if($GLOBALS["VERBOSE"]){echo "->LoadfixedAddresses() -> null item, aborting Line ".__LINE__."\n";}continue;}
			$ip_addr=$hash[$i]["computerip"][0];
			$ComputerMacAddress=$hash[$i][strtolower("ComputerMacAddress")][0];
			if($ComputerMacAddress=="Unknown"){continue;}
			$DnsZoneName=$hash[$i][strtolower("DnsZoneName")][0];
			if($DnsZoneName<>$dhcpd->ddns_domainname){$DnsZoneName=$dhcpd->ddns_domainname;}
			
			
			if(!$IP->isIPAddress($ip_addr)){continue;}
			if($ComputerMacAddress==null){continue;}
			
			if(preg_match("#ethernet\s+(.+)#i", $ComputerMacAddress,$re)){
				writelogs("Corrupted pattern $ComputerMacAddress change it to `{$re[1]}`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$FROMComputerMacAddress=$ComputerMacAddress;
				$ComputerMacAddress=$re[1];
				$cmp2=new computers($uid);$cmp2->UpdateComputerMacAddress($FROMComputerMacAddress,$ComputerMacAddress);
			}
			
			if(!$dhcpd->IsPhysicalAddress($ComputerMacAddress)){
				writelogs("Corrupted pattern `$ComputerMacAddress` skip it ....",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			$ARR[$uid]=array("MAC"=>"hardware ethernet $ComputerMacAddress","IP"=>"fixed-address $ip_addr","domainname"=>$DnsZoneName);
		}
		
	$query="(&(objectClass=dhcpHost)(cn=*))";
	$dn="cn=DhcpConfig,ou=dhcp,$ldap->suffix";
	$filter=array();
	
	$hash=$ldap->Ldap_search($dn,$query,$filter);
	$count=$hash["count"];	
	
	for($i=0;$i<$count;$i++){
		$uid=$hash[$i]["cn"][0];	
		$MacAddress=$hash[$i][strtolower("dhcpHWAddress")][0];
		if(preg_match("#ethernet\s+(.+)#i", $MacAddress,$re)){writelogs("Corrupted pattern $MacAddress change it to `{$re[1]}`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);$MacAddress=$re[1];}		
		if(!$dhcpd->IsPhysicalAddress($MacAddress)){
			writelogs("Corrupted pattern `$MacAddress` skip it ....",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		$ARR[$uid]=array("MAC"=>"hardware ".$hash[$i][strtolower("dhcpHWAddress")][0],"IP"=>$hash[$i][strtolower("dhcpStatements")][0]);
	}
	$GLOBALS[__CLASS__][__FUNCTION__]=$ARR;
	return $ARR;
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
	if($domain==null){echo "DNS domain is null";}
	$sql="INSERT INTO dhcpd_fixed (mac,ipaddr,hostname,domain) VALUES 
	('{$_POST["mac"]}','{$_POST["ipaddr"]}','{$_POST["hostname"]}','$domain')";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?apply-dhcpd=yes");		
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
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = 0;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
		
		if($uid<>null){
			
			$jsfiche=MEMBER_JS($uid,1,1);
			$herf="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\"  style='font-size:14px;text-decoration:underline'>";
		}
		
		if(strpos($ligne["hostname"], ".")>0){$ff=explode(".", $ligne["hostname"]);$ligne["hostname"]=$ff[0];}
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["mac"]}","DHCPFixedDelete('{$ligne["mac"]}','$md5')");
		
		$Modify="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?modify-dhcpd-settings-js=yes&mac={$ligne["mac"]}&t=$tt');\" 
		style='font-size:14px;text-decoration:underline;font-weight:bold'>";
		
		$ligne["hostname"]=strtolower(str_replace("$", "", $ligne["hostname"]));
		$ligne["mac"]=strtoupper($ligne["mac"]);
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("<span style='font-size:14px'>$herf{$ligne["hostname"]}</a></span>"
		,"<span style='font-size:14px'>$herf{$ligne["mac"]}</a></span>",
		"<span style='font-size:14px'>$Modify{$ligne["ipaddr"]}</a></span>",
		"<span style='font-size:14px'>{$ligne["domain"]}</a></span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		

}