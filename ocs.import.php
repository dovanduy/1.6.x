<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('GetRights::$error')";
		die();
	}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["popup_import_list"])){popup_import_list();exit;}

js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{import_artica_computers}");
	$page=CurrentPageName();
	echo "YahooWin5('990','$page?popup=yes','$title')";
}
function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
	if($users->AsSambaAdministrator){return true;}

	return false;
}

function popup(){
	$page=CurrentPageName();
	$t=time();
	$html="<div class=explain style='font-size:22px'>{computer_popup_import_explain_csv}</div>
	<div id='popup_import_div' class=form style='width:98%'>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:546px;border:5px solid #8E8E8E;
	overflow:auto;font-size:14px !important' id='popup_import_list-$t'></textarea>
	
	<div style='text-align:right'>
		<hr>
			". button("{import}","ImportListComputersPerform$t()",28)."
	</div>
</div>
<script>
var x_ImportListComputersPerform$t= function (obj) {
	var results=obj.responseText;
	YahooWin5Hide();
	Loadjs('ocs.import.progress.php');
}
	
function ImportListComputersPerform$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('popup_import_list-$t').value);
	XHR.appendData('popup_import_list',pp);
	XHR.sendAndLoad('$page', 'POST',x_ImportListComputersPerform$t);
}
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}


function popup_import_list(){
	$datas=url_decode_special_tool($_POST["popup_import_list"]);
	$sock=new sockets();
		$sock->SaveConfigFile($datas,"ComputerListToImport");
		$sock->getFrameWork("cmd.php?browse-computers-import-list=yes");

	
	
	}
