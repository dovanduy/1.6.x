<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica-smtp-sync.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsPostfixAdministrator){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["add"])){popup_add();exit;}
	if(isset($_GET["servername"])){popup_save();exit;}
	if(isset($_GET["sync-table"])){echo popup_table();exit;}
	if(isset($_GET["delete"])){popup_delete();exit;}
	js();
	
	
function js(){

	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body('{smtp_sync_artica}');
	$title2=$tpl->_ENGINE_parse_body('{smtp_sync_artica_add}');
	
	$html="
		function smtp_sync_artica_start(){
			YahooWin2(600,'$page?popup=yes','$title');
		}

		
		function SaveServerSyncArticaSMTP(){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixAddRelayRecipientTableSave','yes');
		XHR.appendData('recipient',document.getElementById('recipient').value);
		XHR.sendAndLoad('$page', 'GET',X_PostfixDeleteRelayRecipient);
		
		}
		
		
		function RefreshList(){
			LoadAjax('sync-table','$page?sync-table=yes');
		}
	

	
		
	

	
	smtp_sync_artica_start();
	";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$server=$tpl->_ENGINE_parse_body("{server}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$members=$tpl->javascript_parse_text("{members}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$smtp_sync_artica_explain=$tpl->_ENGINE_parse_body("{smtp_sync_artica_explain}");
	$hostname=$_GET["hostname"];
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$smtp_sync_artica_add=$tpl->javascript_parse_text("{smtp_sync_artica_add}");
	$title=$tpl->_ENGINE_parse_body('{smtp_sync_artica}');
	$buttons="
	buttons : [
	{name: '$smtp_sync_artica_add', bclass: 'add', onpress : smtp_sync_artica_add$t},
	],";		

	
$html="
<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<div class=text-info>$smtp_sync_artica_explain</div>
	
<script>
var memid='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?sync-table=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$server', name : 'domain', width : 409, sortable : true, align: 'left'},
		{display: '$members', name : 'members', width :66, sortable : false, align: 'center'},
		{display: '$delete;', name : 'delete', width : 44, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$server', name : 'domain'},
		],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 571,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function smtp_sync_artica_add$t(){
			YahooWin3(450,'$page?add=yes&t=$t','$title');
	}

	function sender_routing_ruleED$t(domainName){
		YahooWin3(552,'postfix.routing.table.php?SenderTable=yes&domainName='+domainName+'&t=$t','$sender_dependent_relayhost_maps_title::'+domainName);	
	}	
	
	
function DelServerSyncArticaSMTP(server,md){
		memid=md;
		var XHR = new XHRConnection();
		XHR.appendData('delete',server);
		XHR.sendAndLoad('$page', 'GET',X_SaveServerSyncArticaSMTP$t);
}
	

var X_SaveServerSyncArticaSMTP$t= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);return;}
		$('#rowdom'+memid).remove();
	}		

</script>
";
	
	echo $html;
			
	
	
	
}

function popup_add(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$html="
	<p style='font-size:13px'>{smtp_sync_artica_add_text}</p>
	<div id='smtpsyncid'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{hostname}:</td>
		<td valign='top'>". Field_text("servername",null,'width:120px;font-size:14px')."</td>
	<tr>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{port} (SSL):</td>
		<td valign='top'>". Field_text("port","9000",'width:90px;font-size:14px')."</td>
	<tr>	
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{username}:</td>
		<td valign='top'>". Field_text("username","admin",'width:120px;font-size:14px')."</td>
	<tr>	
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{password}:</td>
		<td valign='top'>". Field_password("password","",'width:120px;font-size:14px')."</td>
	<tr>	
	</table>
	<div style='width:100%;text-align:right'><hr>". button("{add}","SaveServerSyncArticaSMTP();",16)."</div>
	</div>
<script>
var X_SaveServerSyncArticaSMTP= function (obj) {
		var results=obj.responseText;
		if (results.length>0){alert(results);}
		YahooWin3Hide();
		$('#flexRT$t').flexReload();
	}		
function SaveServerSyncArticaSMTP(){
		var XHR = new XHRConnection();
		XHR.appendData('servername',document.getElementById('servername').value);
		XHR.appendData('port',document.getElementById('port').value);
		XHR.appendData('username',document.getElementById('username').value);
		XHR.appendData('password',document.getElementById('password').value);
		AnimateDiv('smtpsyncid');
		XHR.sendAndLoad('$page', 'GET',X_SaveServerSyncArticaSMTP);
		
	}
</script>	
	
	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function popup_save(){
	$sync=new articaSMTPSync();
	$sync->Add($_GET["servername"],$_GET["port"],$_GET["username"],$_GET["password"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?sync-remote-smtp-artica=yes");
	}
	
function popup_delete(){
	$sync=new articaSMTPSync();
	$sync->Delete($_GET["delete"]);
	
	
}
function popup_table(){
	$sync=new articaSMTPSync();
	if($_POST["query"]<>null){$searchZ=str_replace("*", ".*?", $_POST["query"]);}
	$t=$_GET["t"];
	$tpl=new templates();
	
	if(count($sync->serverList)==0){
		if($sync->error<>null){json_error_show("$sync->error");}
		json_error_show("No rules...");
	}
	
	while (list ($server, $array) = each ($sync->serverList) ){
		if($searchZ<>null){if(!preg_match("#$searchZ#", $cn_email)){continue;}}
		$m5=md5($server);
		
				$data['rows'][] = array(
					'id' => "dom$m5",
					'cell' => array("
					<a href=\"javascript:blur();\" 
						OnClick=\"\" 
						style='font-size:16px;font-weight:bold;text-decoration:'>{$array["user"]}@$server:{$array["PORT"]}</span>",
					"<span style='font-size:14px;font-weight:bold'>{$array["users"]}</span>",
					 imgtootltip('delete-32.png','{delete}',"DelServerSyncArticaSMTP('$server:{$array["PORT"]}','$m5')") )
					);
					if($c>$_POST["rp"]){break;}
					$c++;		
		
	}
	
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);	
	
}

?>