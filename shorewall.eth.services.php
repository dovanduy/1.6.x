<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}		

if(isset($_POST["ACCEPT_ARTICA"])){Save();exit;}

page();


function page(){
	$sock=new sockets();
	
	
	$nic=new system_nic($_GET["eth"]);
	$DATAS=$nic->ShoreWallServices;
	
	$q=new mysql_shorewall();
	$sql="SELECT zone  FROM `fw_zones` ORDER BY zone";
	$results = $q->QUERY_SQL($sql);
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$ZONES["all+"]="{all}";
	$ZONES["NONE"]="{deny}";
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["zone"]=="fw"){continue;}
		$ZONES[$ligne["zone"]]=$ligne["zone"];
	}
	
	$ACCEPT_PING=$DATAS["ACCEPT_PING"];
	$ACCEPT_SMTP=$DATAS["ACCEPT_SMTP"];
	$ACCEPT_ARTICA=$DATAS["ACCEPT_ARTICA"];
	$ACCEPT_WWWW=$DATAS["ACCEPT_WWWW"];
	$ACCEPT_LDAP=$DATAS["ACCEPT_LDAP"];
	$ACCEPT_MYSQL=$DATAS["ACCEPT_MYSQL"];
	$ACCEPT_PROXY=$DATAS["ACCEPT_PROXY"];
	$ACCEPT_IMAP=$DATAS["ACCEPT_IMAP"];
	$ACCEPT_DNS=$DATAS["ACCEPT_DNS"];
	$ACCEPT_SSH=$DATAS["ACCEPT_SSH"];
	
	if($ACCEPT_SSH==null){$ACCEPT_SSH="all+";}
	if($ACCEPT_PING==null){$ACCEPT_PING="all+";}
	if($ACCEPT_SMTP==null){$ACCEPT_SMTP="all+";}
	if($ACCEPT_ARTICA==null){$ACCEPT_ARTICA="all+";}
	if($ACCEPT_WWWW==null){$ACCEPT_WWWW="all+";}
	if($ACCEPT_LDAP==null){$ACCEPT_LDAP="all+";}
	if($ACCEPT_MYSQL==null){$ACCEPT_MYSQL="all+";}
	if($ACCEPT_IMAP==null){$ACCEPT_IMAP="all+";}
	if($ACCEPT_DNS==null){$ACCEPT_DNS="all+";}
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{accept_ping}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_PING-$t",$ACCEPT_PING,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{accept_ssh}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_SSH-$t",$ACCEPT_SSH,null,null,0,"font-size:16px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{accept_smtp}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_SMTP-$t",$ACCEPT_SMTP,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{accept_imap}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_IMAP-$t",$ACCEPT_IMAP,null,null,0,"font-size:16px")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:16px'>{accept_artica_webconsole}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_ARTICA-$t",$ACCEPT_ARTICA,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{accept_web}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_WWWW-$t",$ACCEPT_WWWW,null,null,0,"font-size:16px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{accept_ldap}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_LDAP-$t",$ACCEPT_LDAP,null,null,0,"font-size:16px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{accept_mysql}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_MYSQL-$t",$ACCEPT_MYSQL,null,null,0,"font-size:16px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{accept_proxy}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_PROXY-$t",$ACCEPT_PROXY,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{accept_dns}:</td>
		<td>". Field_array_Hash($ZONES, "ACCEPT_DNS-$t",$ACCEPT_DNS,null,null,0,"font-size:16px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>".button("{apply}","Save$t()",18)."</td>
	</tr>							
	</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	ExecuteByClassName('SearchFunction');
	Loadjs('shorewall.php?apply-js=yes&ask=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',  '{$_GET["eth"]}');
	
	XHR.appendData('ACCEPT_SSH',  encodeURIComponent(document.getElementById('ACCEPT_SSH-$t').value));
	XHR.appendData('ACCEPT_PING',  encodeURIComponent(document.getElementById('ACCEPT_PING-$t').value));
	XHR.appendData('ACCEPT_SMTP',  encodeURIComponent(document.getElementById('ACCEPT_SMTP-$t').value));
	XHR.appendData('ACCEPT_ARTICA',  encodeURIComponent(document.getElementById('ACCEPT_ARTICA-$t').value));
	XHR.appendData('ACCEPT_WWWW',  encodeURIComponent(document.getElementById('ACCEPT_WWWW-$t').value));
	XHR.appendData('ACCEPT_LDAP',  encodeURIComponent(document.getElementById('ACCEPT_LDAP-$t').value));
	XHR.appendData('ACCEPT_MYSQL',  encodeURIComponent(document.getElementById('ACCEPT_MYSQL-$t').value));
	XHR.appendData('ACCEPT_PROXY',  encodeURIComponent(document.getElementById('ACCEPT_PROXY-$t').value));
	XHR.appendData('ACCEPT_IMAP',  encodeURIComponent(document.getElementById('ACCEPT_IMAP-$t').value));
	XHR.appendData('ACCEPT_DNS',  encodeURIComponent(document.getElementById('ACCEPT_DNS-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function Save(){
	$sock=new sockets();
	while (list ($a, $b) = each ($_POST) ){
		$_POST[$a]=url_decode_special_tool($b);
	}
	
	$nic=new system_nic($_POST["eth"]);
	$nic->ShoreWallServices=$_POST;
	$nic->SaveNicFW();
}