<?php
session_start ();
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');


if(!Isright()){$tpl=new templates();echo "alert('".$tpl->javascript_parse_text('{ERROR_NO_PRIVS}')."');";die();}

if(isset($_POST["DeleteComputer"])){DeleteComputer();exit;}

js();



function js(){
	$tpl=new templates();
	$delete=$tpl->javascript_parse_text("{delete}");
	$page=CurrentPageName();
	$uid=$_GET["uid"];
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tt=time();
$html="	
var x_DeleteComputer$tt= function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);}
	YahooUserHide();
	
	if(document.getElementById('computerlist')){BrowsComputersRefresh();}
	if(document.getElementById('DnsZoneName')){BindComputers(document.getElementById('DnsZoneName').value)}	
	if(document.getElementById('bind9_hosts_list')){BindRefresh();}
	if(document.getElementById('main-content')){Loadjs('start.php');}
	if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
	if(document.getElementById('COMPUTER_BROWSE_TABLE')){ $('#COMPUTER_BROWSE_TABLE').flexReload();}
	if(document.getElementById('flexRT{$_GET["t"]}')){ $('#flexRT{$_GET["t"]}').flexReload(); }
	
}
	
function DeleteComputer$tt(uid){
	if(!confirm('$delete $uid ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteComputer',uid);
	XHR.appendData('uid',uid);
	XHR.appendData('echo','yes');
	XHR.sendAndLoad('$page', 'POST',x_DeleteComputer$tt);
	}	
	

DeleteComputer$tt('$uid');
";

echo $html;


}


function DeleteComputer(){
	$computer=new computers($_POST["DeleteComputer"]);
	if($computer->ComputerMacAddress<>null){
		$ocs=new ocs($computer->ComputerMacAddress);
		$ocs->DeleteComputer();
		return;
	}
	
	$computer->DeleteComputer();
	
}

function IsRight(){
	if(!isset($_REQUEST["uid"])){return false;}
	$users=new usersMenus();
	if($users->AsArticaAdministrator){return true;}
	if($users->AsInventoryAdmin){return true;}
	if($users->AsSambaAdministrator){return true;}
	if($users->AllowAddUsers){return true;}
	if($users->AllowManageOwnComputers){return true;}
	return false;
	}
?>