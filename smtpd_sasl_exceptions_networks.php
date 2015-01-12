<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	

	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["list"])){popup_list();exit;}
	if(isset($_GET["popup"])){main_table();exit;}
	if(isset($_GET["smtpd_sasl_exceptions_networks_add"])){add();exit;}
	if(isset($_GET["smtpd_sasl_exceptions_networks_del"])){del();exit;}
	
	
	
	if(isset($_GET["popup-toolbox"])){toolbox();exit;}
	if(isset($_GET["smtpd_sasl_exceptions_mynet"])){smtpd_sasl_exceptions_mynet_save();exit;}
	
	js();
	
	
function js(){
if(GET_CACHED(__FILE__,__FUNCTION__,null)){return null;}
$prefix="smtpd_sasl_exceptions_networks_";
$page=CurrentPageName();
$users=new usersMenus();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{smtpd_sasl_exceptions_networks}');
$give_the_new_network=$tpl->javascript_parse_text("{give the new network}");


$html="

function SaslExceptionsNetworksLoadpage(){
	YahooWin5(550,'$page?popup=yes','$title');
	}
	

	
function smtpd_sasl_exceptions_delete(id_encrypted){
	var XHR = new XHRConnection();
	XHR.appendData('smtpd_sasl_exceptions_networks_del',id_encrypted);
	document.getElementById('smtpd_sasl_exceptions_networks_list').innerHTML='<center><img src=img/wait_verybig.gif></center>';
	XHR.sendAndLoad('$page', 'GET',X_smtpd_sasl_exceptions_networks_add);
	
	}

	
var X_SmtpdSaslExceptionsMynetSave= function (obj) {
		LoadAjax('smtpd_sasl_exceptions_networks_list','$page?popup-list=yes');
		LoadAjax('smtpd_sasl_exceptions_toolbox','$page?popup-toolbox=yes');
	}	
	
function SmtpdSaslExceptionsMynetSave(){
			var XHR = new XHRConnection();
			XHR.appendData('smtpd_sasl_exceptions_mynet',document.getElementById('smtpd_sasl_exceptions_mynet').value);
			document.getElementById('smtpd_sasl_exceptions_toolbox').innerHTML='<center><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',X_SmtpdSaslExceptionsMynetSave);
}
	
	
	
	SaslExceptionsNetworksLoadpage();
";
	SET_CACHED(__FILE__,__FUNCTION__,null,$html);
	echo $html;	
	
	
}

function main_table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();

	$q=new mysql();
	if(!$q->TABLE_EXISTS("sender_dependent_relay_host", "artica_backup")){
		$q->BuildTables();
	}
	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}

	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{sender_domain_email}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$networks=$tpl->javascript_parse_text("{networks}");
	$hostname=$_GET["hostname"];
	$apply=$tpl->javascript_parse_text("{apply}");
	$about2=$tpl->javascript_parse_text("{about2}");
	$title=$tpl->javascript_parse_text("{smtpd_sasl_exceptions_networks_text}");
	$add_sender_routing_rule=$tpl->_ENGINE_parse_body("{add_new_network}");
	$explain=$tpl->javascript_parse_text("{smtpd_sasl_exceptions_networks_explain}");
	$give_the_new_network=$tpl->javascript_parse_text("{give the new network}");
	
	

	$buttons="
	buttons : [
	{name: '$add_sender_routing_rule', bclass: 'add', onpress : newrule$t},
	{name: '$apply', bclass: 'recycle', onpress : apply$t},
	{name: '$about2', bclass: 'help', onpress : Help$t},
	],";

	
	$html="
	<input type='hidden' id='ou' value='$ou'>
	<table class='SMTP_SASL_EXCEPT_TABLE' style='display: none' id='SMTP_SASL_EXCEPT_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SMTP_SASL_EXCEPT_TABLE').flexigrid({
	url: '$page?list=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$networks', name : 'domain', width : 749, sortable : true, align: 'left'},
	{display: '$delete;', name : 'delete', width : 90, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$domain', name : 'domain'},
	],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:16px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '550',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function  Help$t(){
alert('$explain');
}

var xnewrule$t= function (obj) {
	$('#SMTP_SASL_EXCEPT_TABLE').flexReload();

}
		


function newrule$t(){
	var a=prompt('$give_the_new_network');
	if(!a){
		return;
	}
	var XHR = new XHRConnection();
	XHR.appendData('smtpd_sasl_exceptions_networks_add',a);
	XHR.sendAndLoad('$page', 'GET',xnewrule$t);
}
	
function  apply$t(){
	Loadjs('postfix.sasl.progress.php');
}

function smtpd_sasl_exceptions_delete(id_encrypted){
	var XHR = new XHRConnection();
	XHR.appendData('smtpd_sasl_exceptions_networks_del',id_encrypted);
	XHR.sendAndLoad('$page', 'GET', xnewrule$t);
}

</script>
";

	echo $html;


}

function popup(){
	
	

	
	
	$page=CurrentPageName();
	$html="<div class=text-info>{smtpd_sasl_exceptions_networks_text}<br>{smtpd_sasl_exceptions_networks_explain}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top'>
			<div id='smtpd_sasl_exceptions_networks_list'></div>
		</td>
		<td valign='top'>
			<div id='smtpd_sasl_exceptions_toolbox'></div>
			
		</td>
	</tr>
	</table>
	<script>
		LoadAjax('smtpd_sasl_exceptions_networks_list','$page?popup-list=yes');
		LoadAjax('smtpd_sasl_exceptions_toolbox','$page?popup-toolbox=yes');
	</script>
	";
	
	$tpl=new templates();
 $html=$tpl->_ENGINE_parse_body($html);
 SET_CACHED(__FILE__,__FUNCTION__,null,$html);
 echo $html;
}

function add(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("smtpd_sasl_exceptions_networks")));
	$array[$_GET["smtpd_sasl_exceptions_networks_add"]]=$_GET["smtpd_sasl_exceptions_networks_add"];
	if(is_array($array)){
		while (list ($num, $net) = each ($array) ){
			$finale[$net]=$net;
		}
	}
	
	$text=base64_encode(serialize($finale));
	$sock->SaveConfigFile($text,"smtpd_sasl_exceptions_networks");
	$sock->getFrameWork("cmd.php?SaveMaincf=yes");
}

function del(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("smtpd_sasl_exceptions_networks")));
	$net=base64_decode($_GET["smtpd_sasl_exceptions_networks_del"]);	
	unset($array[$net]);
	if(is_array($array)){
		while (list ($num, $net) = each ($array) ){
			$finale[$net]=$net;
		}
	}
	
	$text=base64_encode(serialize($finale));
	$sock->SaveConfigFile($text,"smtpd_sasl_exceptions_networks");
	$sock->getFrameWork("cmd.php?SaveMaincf=yes");	
}


function popup_list(){
	$sock=new sockets();
	
	$main=new maincf_multi($_GET["hostname"]);
	$array=unserialize(base64_decode($sock->GET_INFO("smtpd_sasl_exceptions_networks")));
	$smtpd_sasl_exceptions_mynet=$sock->GET_INFO("smtpd_sasl_exceptions_mynet");
	$TrustMyNetwork=$main->GET("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}	
	
	$style="style='font-size:26px'";
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	$c=0;
	if($smtpd_sasl_exceptions_mynet==1){
		$main=new main_cf();
		if(is_array($main->array_mynetworks)){
			while (list ($num, $val) = each ($main->array_mynetworks) ){
				$c++;
				$cell=array();
				$cell[]="<span $style>$val</a></span>";
				$cell[]="<span $style>&nbsp;</a></span>";
				$data['rows'][] = array(
						'id' => $c,
						'cell' => $cell
				);
			}
			
		}
	}
	
if(is_array($array)){
		while (list ($num, $net) = each ($array) ){
			
			$net_encrypted=base64_encode($net);
			
			$c++;
			$cell=array();
			$cell[]="<span $style>$net</a></span>";
			$cell[]="<span $style>".imgsimple("delete-32.png",null,"smtpd_sasl_exceptions_delete('$net_encrypted')")."</span>";
			$data['rows'][] = array(
					'id' => $c,
					'cell' => $cell
			);

			
		}
	}	
	
	if($c==0){json_error_show("no rule");}
	
	$data['total'] = $c;
echo json_encode($data);
	
}

function smtpd_sasl_exceptions_mynet_save(){
	$sock=new sockets();
	$sock->SET_INFO("smtpd_sasl_exceptions_mynet",$_GET["smtpd_sasl_exceptions_mynet"]);
	
	
}






?>