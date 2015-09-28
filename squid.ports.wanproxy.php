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

$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));




$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:20px'>{memory_cache} (MB):</td>
		<td style='font-size:18px'>". field_text("WanProxyMemory-$t", $ligne["WanProxyMemory"],"font-size:20px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{caches_on_disk} (GB):</td>
		<td style='font-size:18px'>". field_text("WanProxyCache-$t", $ligne["WanProxyCache"],"font-size:20px;width:120px")."</td>
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
	XHR.appendData('WanProxyMemory',document.getElementById('WanProxyMemory-$t').value);
	XHR.appendData('WanProxyCache',document.getElementById('WanProxyCache-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function Save(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE proxy_ports SET
	WanProxyMemory={$_POST["WanProxyMemory"]},
	WanProxyCache={$_POST["WanProxyCache"]}
	WHERE ID='{$_POST["ID"]}'");
	
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("wanproxy.php?reconfigure-silent=yes");
	
}
