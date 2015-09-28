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

if(isset($_POST["SquidAsMasterLogChilds"])){SquidAsMasterLogChilds();exit;}
popup();
function popup(){
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();


if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterCacheChilds")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterCacheChilds` smallint(1) NOT NULL DEFAULT '1'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterLogExtern")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterLogExtern` smallint(1) NOT NULL DEFAULT '0'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterFollowxForward")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterFollowxForward` smallint(1) NOT NULL DEFAULT '0'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}
if(!$q->FIELD_EXISTS("proxy_ports", "SquidAsMasterLogChilds")){
	$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidAsMasterLogChilds` smallint(1) NOT NULL DEFAULT '0'");
	if(!$q->ok){echo $q->mysql_error."\n";}
}


$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));


$p2=Paragraphe_switch_img("{logging_childs_connections}", "{logging_childs_connections_explain}",
		"SquidAsMasterLogChilds-$t",$ligne["SquidAsMasterLogChilds"],null,850);

$p21=Paragraphe_switch_img("{logging_childs_connections2}", "{logging_childs_connections_explain2}",
		"SquidAsMasterLogExtern-$t",$ligne["SquidAsMasterLogChilds"],null,850);


$p3=Paragraphe_switch_img("{cache_childs_requests}", "{cache_childs_requests_explain}",
		"SquidAsMasterCacheChilds-$t",$ligne["SquidAsMasterCacheChilds"],null,850);

$p4=Paragraphe_switch_img("{follow_x_forwarded_for}", "{follow_x_forwarded_for_explain}",
		"SquidAsMasterFollowxForward-$t",$ligne["SquidAsMasterFollowxForward"],null,850);


if(intval($ligne["SquidAsMasterFollowxForward"])==1){
	$error="<p class=explain style='font-size:16px'>{SquidAsMasterFollowxForward_error}</p>";

}

$html="
<div style='width:98%' class=form>
<table style='width:100%'>
<tr> <td colspan=2>$p2</td> </tr>
<tr> <td colspan=2>$p21</td> </tr>
<tr> <td colspan=2>$p3</td> </tr>
<tr> <td colspan=2>$p4</td> </tr>
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
	XHR.appendData('SquidAsMasterLogChilds',document.getElementById('SquidAsMasterLogChilds-$t').value);
	XHR.appendData('SquidAsMasterCacheChilds',document.getElementById('SquidAsMasterCacheChilds-$t').value);
	XHR.appendData('SquidAsMasterLogExtern',document.getElementById('SquidAsMasterLogExtern-$t').value);
	XHR.appendData('SquidAsMasterFollowxForward',document.getElementById('SquidAsMasterFollowxForward-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function SquidAsMasterLogChilds(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE proxy_ports SET
	SquidAsMasterLogChilds={$_POST["SquidAsMasterLogChilds"]},
	SquidAsMasterCacheChilds={$_POST["SquidAsMasterCacheChilds"]},
	SquidAsMasterFollowxForward={$_POST["SquidAsMasterFollowxForward"]}
	WHERE ID='{$_POST["ID"]}'");
	
	if(!$q->ok){echo $q->mysql_error;}
}
