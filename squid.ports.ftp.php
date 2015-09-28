<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_POST["ID"])){Save();exit;}


popup();
function popup(){
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();


if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyMaxClients")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyMaxClients` BIGINT(100) NOT NULL DEFAULT '64'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyTimeOuts")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyTimeOuts` INT(100) NOT NULL DEFAULT '360'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}

if(!$q->FIELD_EXISTS("proxy_ports", "FTPProxyDestinationTransferMode")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPProxyDestinationTransferMode` VARCHAR(32) NOT NULL DEFAULT 'client'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "FTPUserAuthMagic")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPUserAuthMagic` VARCHAR(128) NOT NULL DEFAULT '@user'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "FTPUseMagicChar")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPUseMagicChar` VARCHAR(32) NOT NULL DEFAULT '@'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}


if(!$q->FIELD_EXISTS("proxy_ports", "FTPAllowMagicUser")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `FTPAllowMagicUser` SMALLINT(1) NOT NULL DEFAULT '1'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}


$DestinationTransferModeR["client"]="client";
$DestinationTransferModeR["passive"]="passive";
$DestinationTransferModeR["active"]="active";

$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));




$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:20px'>{MaxClients}:</td>
		<td style='font-size:18px'>". field_text("FTPProxyMaxClients-$t", $ligne["FTPProxyMaxClients"],"font-size:20px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{timeout2} ({seconds}):</td>
		<td style='font-size:18px'>". field_text("FTPProxyTimeOuts-$t", $ligne["FTPProxyTimeOuts"],"font-size:20px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>".texttooltip("{FTPProxyDestinationTransferMode}","{FTPProxyDestinationTransferMode_explain}").":</td>
		<td style='font-size:20px'>". Field_array_Hash($DestinationTransferModeR, "FTPProxyDestinationTransferMode-$t",$ligne["FTPProxyDestinationTransferMode"],"style:font-size:20px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>".texttooltip("{FTPAllowMagicUser}","{FTPAllowMagicUser_explain}").":</td>
			<td style='font-size:20px'>". Field_checkbox_design("FTPAllowMagicUser-$t", 1,$ligne["FTPAllowMagicUser"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>".texttooltip("{UserAuthMagic}","{UserAuthMagic_explain}").":</td>
		<td style='font-size:18px'>". field_text("FTPUserAuthMagic-$t", $ligne["FTPUserAuthMagic"],"font-size:20px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>".texttooltip("{FTPUseMagicChar}","{FTPUseMagicChar_explain}").":</td>
		<td style='font-size:18px'>". field_text("FTPUseMagicChar-$t", $ligne["FTPUseMagicChar"],"font-size:20px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>	
<tr>
	<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",32)."</td>
</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	RefreshTab('main_proxy_listen_ports');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('FTPProxyMaxClients',document.getElementById('FTPProxyMaxClients-$t').value);
	XHR.appendData('FTPProxyTimeOuts',document.getElementById('FTPProxyTimeOuts-$t').value);
	XHR.appendData('FTPProxyDestinationTransferMode',document.getElementById('FTPProxyDestinationTransferMode-$t').value);
	XHR.appendData('FTPUserAuthMagic',document.getElementById('FTPUserAuthMagic-$t').value);
	XHR.appendData('FTPUseMagicChar',document.getElementById('FTPUseMagicChar-$t').value);
	if(document.getElementById('FTPAllowMagicUser-$t').checked){XHR.appendData('FTPAllowMagicUser',1);}else{XHR.appendData('FTPAllowMagicUser',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function Save(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE proxy_ports SET
	FTPProxyMaxClients={$_POST["FTPProxyMaxClients"]},
	FTPProxyTimeOuts={$_POST["FTPProxyTimeOuts"]},
	FTPProxyDestinationTransferMode={$_POST["FTPProxyDestinationTransferMode"]},
	FTPUserAuthMagic={$_POST["FTPUserAuthMagic"]},
	FTPUseMagicChar={$_POST["FTPUseMagicChar"]}
	WHERE ID='{$_POST["ID"]}'");
	
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("ftp-proxy.php?reconfigure-silent=yes");
	
}
