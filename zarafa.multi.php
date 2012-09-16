<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["zarafa-instance-webmail"])){zarafa_instance_webmail();exit;}
	if(isset($_GET["zarafa-instance-status"])){services_status();exit;}
	if(isset($_GET["zarafa-instances-search"])){instances_list();exit;}
	if(isset($_GET["zarafa-instance-js"])){zarafa_instance_js();exit;}
	if(isset($_GET["zarafa-instance-id"])){zarafa_instance_tabs();exit;}
	if(isset($_GET["zarafa-instance-params"])){zarafa_instance_params();exit;}
	if(isset($_GET["zarafa-instance-imap"])){zarafa_instance_imap();exit;}
	if(isset($_POST["EnablePOP3"])){zarafa_instance_imap_save();exit;}
	if(isset($_GET["zarafa-server-service-js"])){zarafa_instance_service_js();exit;}
	
	if(isset($_POST["usesocket"])){zarafa_instance_params_save();exit;}
	if(isset($_GET["zarafa-instance-service"])){zarafa_instance_service();exit;}
	if(isset($_GET["zarafa-instance-service-perform"])){zarafa_instance_service_perform();exit;}
	
	if(isset($_GET["zarafa-server-delete-js"])){zarafa_delete_js();exit;}
	if(isset($_POST["zarafa-server-delete-perform"])){zarafa_delete_perform();exit;}
	
page();	

function zarafa_instance_service_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";}
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM zarafamulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
		$title=$ligne["servername"];
	}
	$title=$tpl->_ENGINE_parse_body($title).":: -&raquo;{$_GET["action"]}";
	
	echo "YahooWin2('450','$page?zarafa-instance-service=yes&ID={$_GET["ID"]}&action={$_GET["action"]}','[{$_GET["ID"]}]:$title')";	
	
}

function zarafa_instance_service_perform(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("zarafa.php?multi-service=yes&instance-id={$_GET["ID"]}&action={$_GET["action"]}")));
	
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		echo "<div><code style='font-size:12px'>$ligne</code></div>";
	}
	
	echo "<script>FlexReloadZarafaInstanceTable();</script>";
	
}

function zarafa_instance_service(){
	$page=CurrentPageName();
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
		LoadAjax('$t','$page?zarafa-instance-service-perform=yes&ID={$_GET["ID"]}&action={$_GET["action"]}');
	</script>
	";
echo $html;
	
}

function zarafa_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM zarafamulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$servername=$ligne["servername"];
	$t=time();
	$confirm_delete_zarafa=$tpl->javascript_parse_text("{confirm_delete_zarafa}:$servername");
	$html="
		var x_DeleteInstance$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_instance_zarafa_multi');
			if(document.getElementById('zarafa-instances-table')){FlexReloadZarafaInstanceTable();}
		}	
	
	
		function DeleteInstance$t(){
			if(confirm('$confirm_delete_zarafa')){
				var XHR = new XHRConnection();	
				XHR.appendData('zarafa-server-delete-perform','{$_GET["ID"]}');
				XHR.sendAndLoad('$page', 'POST',x_DeleteInstance$t);			
			
			}
		
		}
	DeleteInstance$t();
	";
	
	echo $html;	
	
}

function zarafa_delete_perform(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?delete-instance=yes&instance-id={$_POST["zarafa-server-delete-perform"]}");
	sleep(3);
}


function zarafa_instance_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";}
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM zarafamulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
		$title=$ligne["servername"];
	}
	$title=$tpl->_ENGINE_parse_body($title);
	
	echo "YahooWin2('750','$page?zarafa-instance-id=yes&ID={$_GET["ID"]}','[{$_GET["ID"]}]:$title')";
}

function zarafa_instance_imap(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$q=new mysql();
	$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");
	if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="0.0.0.0";}
	if($ZarafaGatewayBind=="0.0.0.0"){echo $tpl->_ENGINE_parse_body("<H2>{unable_to_perform_operation_zarafa_bind_all_addresses}</H2>");return;}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM zarafamulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
	if($ligne["usesocket"]==0){echo $tpl->_ENGINE_parse_body("<H2>{error_you_need_to_choose_network_addr_config}</H2>");return;}
	$params=unserialize(base64_decode($ligne["params"]));
	$enable_pop3=Paragraphe_switch_img("{enable_pop3}", "{enable_pop3_text}","EnablePOP3",$params["EnablePOP3"],null,450);
	$enable_imap=Paragraphe_switch_img("{enable_imap}", "{enable_imap_text}","EnableIMAP",$params["EnableIMAP"],null,450);
	$t=time();
	$html="
	<div id='$t'>
	$enable_pop3
	<br>
	$enable_imap
	<br>
	</div>
	<div style='width:100%;text-align:right'>". button("{apply}", "SaveZarafaProtos()",16)."</div>
	
	<script>
	var x_SaveZarafaProtos= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_instance_zarafa_multi');
		if(document.getElementById('zarafa-instances-table')){FlexReloadZarafaInstanceTable();}
		}	
	
	function SaveZarafaProtos(){
		var XHR = new XHRConnection();	
		XHR.appendData('EnablePOP3',document.getElementById('EnablePOP3').value);
		XHR.appendData('EnableIMAP',document.getElementById('EnableIMAP').value);
		XHR.appendData('ID','{$_GET["ID"]}');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveZarafaProtos);
	}		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function zarafa_instance_imap_save(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM zarafamulti WHERE ID='{$_POST["ID"]}'","artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));
	$params["EnablePOP3"]=$_POST["EnablePOP3"];
	$params["EnableIMAP"]=$_POST["EnableIMAP"];
	$newparams=base64_encode(serialize($params));
	$q->QUERY_SQL("UPDATE zarafamulti SET params='$newparams' WHERE ID='{$_POST["ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?multi-restart=yes&instance-id={$_POST["ID"]}");
}

function services_status_running_since($ID){
	if(!isset($GLOBALS["services_status_unik"])){
		$sock=new sockets();
		$GLOBALS["services_status_unik"]=new Bs_IniHandler();
		$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
		$GLOBALS["services_status_unik"]->loadString($datas);	
	}

	return $GLOBALS["services_status_unik"]->_params["APP_ZARAFA:$ID"]["uptime"];
	
}

function services_status_unik($ID){
	if(!isset($GLOBALS["services_status_unik"])){
		$sock=new sockets();
		$GLOBALS["services_status_unik"]=new Bs_IniHandler();
		$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
		$GLOBALS["services_status_unik"]->loadString($datas);	
	}
	$array[]="APP_ZARAFA:$ID";
	$array[]="APP_ZARAFA_GATEWAY:$ID";
	$array[]="APP_ZARAFA_SPOOLER:$ID";
	$array[]="APP_ZARAFA_WEB:$ID";
	$array[]="APP_ZARAFA_MONITOR:$ID";
	$array[]="APP_ZARAFA_DAGENT:$ID";
	$array[]="APP_ZARAFA_ICAL:$ID";
	$array[]="APP_ZARAFA_INDEXER:$ID";
	$array[]="APP_ZARAFA_LICENSED:$ID";
	$array[]="APP_YAFFAS:$ID";
	while (list ($num, $ligne) = each ($array) ){
		if(!isset($GLOBALS["services_status_unik"]->_params[$ligne])){continue;}
		if($GLOBALS["services_status_unik"]->_params[$ligne]["running"]<>1){
			writelogs("Instance $ID, service $ligne not running...",__FUNCTION__,__FILE__,__LINE__);
			return false;}	
	}
	return true;
	
}

function services_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];		
	$array[]="APP_ZARAFA:$ID";
	$array[]="APP_ZARAFA_GATEWAY:$ID";
	$array[]="APP_ZARAFA_SPOOLER:$ID";
	$array[]="APP_ZARAFA_WEB:$ID";
	$array[]="APP_ZARAFA_MONITOR:$ID";
	$array[]="APP_ZARAFA_DAGENT:$ID";
	$array[]="APP_ZARAFA_ICAL:$ID";
	$array[]="APP_ZARAFA_INDEXER:$ID";
	$array[]="APP_ZARAFA_LICENSED:$ID";
	$array[]="APP_YAFFAS:$ID";

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
	$ini->loadString($datas);
	
	while (list ($num, $ligne) = each ($array) ){
		$tr[]=DAEMON_STATUS_ROUND($ligne,$ini,null,1);
		
	}
	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>";	
$html="<H3>{services_status}:</H3>".implode("\n",$tables);	
echo $tpl->_ENGINE_parse_body($html);		

	
	
}

function zarafa_instance_params(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ip=new networking();
	$q=new mysql();
	$ldap=new clladp();
	
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";}
	
	$button="{add}";
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM zarafamulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
		$title=$ligne["servername"];
		$button="{apply}";
	}

	
	if(!is_numeric($ligne["mysql_instance_id"])){$ligne["mysql_instance_id"]=0;}
	
	$sql="SELECT ID,servername FROM mysqlmulti WHERE enabled=1 ORDER BY servername";
	$results = $q->QUERY_SQL($sql,'artica_backup');
	$mysqlinstances[0]="{mysql_master}";
	while ($ligne2 = mysql_fetch_assoc($results)) {$mysqlinstances[$ligne2["ID"]]=$ligne2["servername"];}
	
	$sql="SELECT value FROM postfix_multi WHERE `key` = 'myhostname' ORDER BY value";
	$results = $q->QUERY_SQL($sql,'artica_backup');
	$PostfixInstances[null]="{postfix_master}";
	while ($ligne2 = mysql_fetch_assoc($results)) {$PostfixInstances[$ligne2["value"]]=$ligne2["value"];}	
	
	
	
	
	$ips=$ip->ALL_IPS_GET_ARRAY();	
	$ips["0.0.0.0"]="{all}";
	$t=time();

	$ous=$ldap->hash_get_ou(true);
	$ous[null]="{all}";
	$GetLastInstanceNum=GetLastInstanceNum()+1;
	if(!is_numeric($ligne["listen_port"])){$ligne["listen_port"]=GetLastPort()+1;}
	if(!is_numeric($ligne["lmtp_port"])){$ligne["lmtp_port"]=GetLastlmtpport()+1;}	
	if($ligne["Dir"]==null){$ligne["Dir"]="/var/lib/zarafa-$GetLastInstanceNum";}
	
	$nets=Field_array_Hash($ips,"$t-addr",$ligne["listen_addr"],"style:font-size:14px;padding:3px");
	$mysqlinstances=Field_array_Hash($mysqlinstances,"$t-mysql_instance_id",$ligne["mysql_instance_id"],"style:font-size:14px;padding:3px");
	$PostfixInstances=Field_array_Hash($PostfixInstances,"$t-PostfixInstance",$ligne["PostfixInstance"],"style:font-size:14px;padding:3px");
	$ous=Field_array_Hash($ous,"$t-ou",$ligne["ou"],"style:font-size:14px;padding:3px");
	$html="
	<div id='$t' ><span style='font-size:16px'>$title</span>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td>". Field_checkbox("$t-enabled", 1,$ligne["enabled"],"InstanceChecKenabled{$t}()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{organization}:</td>
		<td>$ous</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". Field_text("$t-hostname",$ligne["servername"],"font-size:14px;padding:3px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{UseNetworkCard}:</td>
		<td>". Field_checkbox("$t-usesocket", 1,$ligne["usesocket"],"InstanceCheckUsesocket{$t}()")."</td>
		<td>&nbsp;</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:14px'>{listen_address}:</td>
		<td>$nets</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("$t-listen_port",$ligne["listen_port"],"font-size:14px;padding:3px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{lmtp_port}:</td>
		<td>". Field_text("$t-lmtp_port",$ligne["lmtp_port"],"font-size:14px;padding:3px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{mysql_instance}:</td>
		<td>$mysqlinstances</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{postfix_instance}:</td>
		<td>$PostfixInstances</td>
		<td>&nbsp;</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{attachments_path}:</td>
		<td>". Field_text("$t-Dir",$ligne["Dir"],"font-size:14px;padding:3px;width:220px")."</td>
		<td><input type='button' value='{browse}&nbsp;&raquo;' OnClick=\"javascript:Loadjs('tree.php?select-dir=yes&target-form=$t-Dir');\"></td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button($button, "SaveInstance{$t}()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	var x_{$t}_SaveInstance= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_instance_zarafa_multi');
		if(document.getElementById('zarafa-instances-table')){FlexReloadZarafaInstanceTable();}
		}	
	
	function SaveInstance{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-enabled').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		if(document.getElementById('$t-usesocket').checked){XHR.appendData('usesocket',1);}else{XHR.appendData('usesocket',0);}
		XHR.appendData('mysql_instance_id',document.getElementById('$t-mysql_instance_id').value);
		XHR.appendData('hostname',document.getElementById('$t-hostname').value);
		XHR.appendData('ou',document.getElementById('$t-ou').value);
		XHR.appendData('listen_addr',document.getElementById('$t-addr').value);
		XHR.appendData('listen_port',document.getElementById('$t-listen_port').value);
		XHR.appendData('Dir',document.getElementById('$t-Dir').value);
		XHR.appendData('lmtp_port',document.getElementById('$t-lmtp_port').value);
		XHR.appendData('PostfixInstance',document.getElementById('$t-PostfixInstance').value);
		XHR.appendData('ID','{$_GET["ID"]}');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
	}
	
	
	function InstanceChecKenabled{$t}(){
		document.getElementById('$t-usesocket').disabled=true;
		document.getElementById('$t-hostname').disabled=true;
		document.getElementById('$t-addr').disabled=true;
		document.getElementById('$t-listen_port').disabled=true;
		document.getElementById('$t-Dir').disabled=true;
		document.getElementById('$t-ou').disabled=true;
		document.getElementById('$t-mysql_instance_id').disabled=true;
		document.getElementById('$t-lmtp_port').disabled=true;
		document.getElementById('$t-PostfixInstance').disabled=true;
		
		
	
		if(document.getElementById('$t-enabled').checked){
			document.getElementById('$t-hostname').disabled=false;
			document.getElementById('$t-Dir').disabled=false;
			document.getElementById('$t-usesocket').disabled=false;
			document.getElementById('$t-ou').disabled=false;
			document.getElementById('$t-mysql_instance_id').disabled=false;	
			document.getElementById('$t-lmtp_port').disabled=false;	
			document.getElementById('$t-PostfixInstance').disabled=false;	
		}
		InstanceCheckUsesocket{$t}();
	}
	
	function InstanceCheckUsesocket{$t}(){
		document.getElementById('$t-listen_port').disabled=false;
		if(!document.getElementById('$t-enabled').checked){return;}
			document.getElementById('$t-addr').disabled=true;	
		if(document.getElementById('$t-usesocket').checked){
			document.getElementById('$t-addr').disabled=false;
			
		}
	
	}
	
	
	InstanceChecKenabled{$t}();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function zarafa_instance_params_save(){
	$q=new mysql();
	$q->BuildTables();
	$tpl=new templates();
	
	$_POST["hostname"]=replace_accents($_POST["hostname"]);
	$_POST["hostname"]=trim(strtolower($_POST["hostname"]));
	$_POST["hostname"]=str_replace(" ", "-", $_POST["hostname"]);
	
	if($_POST["ID"]>0){
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM zarafamulti WHERE ID!='{$_POST["ID"]}' 
	AND listen_addr='{$_POST["listen_addr"]}' 
	AND listen_port='{$_POST["listen_port"]}'","artica_backup"));
	}else{
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM zarafamulti WHERE  
	listen_addr='{$_POST["listen_addr"]}' 
	AND listen_port='{$_POST["listen_port"]}'","artica_backup"));		
	}
	
	if($ligne["ID"]>0){echo $tpl->javascript_parse_text("{error_instances_same_port}");return;}
	
	
	if($_POST["ID"]>0){
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM zarafamulti WHERE ID!='{$_POST["ID"]}' 
	AND listen_addr='{$_POST["listen_addr"]}' 
	AND lmtp_port='{$_POST["lmtp_port"]}'","artica_backup"));
	}else{
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM zarafamulti WHERE  
	listen_addr='{$_POST["listen_addr"]}' 
	AND lmtp_port='{$_POST["lmtp_port"]}'","artica_backup"));		
	}	
	
	if($ligne["ID"]>0){echo $tpl->javascript_parse_text("{error_instances_same_port} (LMTP)");return;}
	
	if($_POST["ID"]<1){
		$sql="INSERT IGNORE INTO zarafamulti (servername,enabled,listen_addr,listen_port,Dir,usesocket,mysql_instance_id,
		ou,lmtp_port,PostfixInstance)
		VALUES('{$_POST["hostname"]}',
		'{$_POST["enabled"]}',
		'{$_POST["listen_addr"]}',
		'{$_POST["listen_port"]}',
		'{$_POST["Dir"]}',
		'{$_POST["usesocket"]}',
		'{$_POST["mysql_instance_id"]}',
		'{$_POST["ou"]}',
		'{$_POST["lmtp_port"]}',
		'{$_POST["PostfixInstance"]}'
		)";
		
	}else{
		$sql="UPDATE zarafamulti SET 
			servername='{$_POST["hostname"]}',
			enabled='{$_POST["enabled"]}',	
			listen_addr='{$_POST["listen_addr"]}',
			listen_port='{$_POST["listen_port"]}',
			Dir='{$_POST["Dir"]}',
			usesocket='{$_POST["usesocket"]}',
			mysql_instance_id='{$_POST["mysql_instance_id"]}',
			ou='{$_POST["ou"]}',
			lmtp_port='{$_POST["lmtp_port"]}',
			PostfixInstance='{$_POST["PostfixInstance"]}'  
			WHERE ID={$_POST["ID"]}";
		
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$sock=new sockets();
	if($_POST["ID"]>0){$ID=$_POST["ID"];}else{$ID=$q->last_id;}
	
	$sock->getFrameWork("zarafa.php?multi-restart=yes&instance-id=$ID");
	
	if(trim($_POST["PostfixInstance"])<>null){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?postfix-multi-configure-hostname={$_POST["PostfixInstance"]}");
	}
		
}

function zarafa_instance_tabs(){
	if(!isset($_GET["tab"])){$_GET["tab"]=0;};
	$page=CurrentPageName();
	$tpl=new templates();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";}
	if($_GET["ID"]>0){$array["zarafa-instance-status"]="{services_status}";}
	
	$array["zarafa-instance-params"]="{global_parameters}";
	
	
	
	if($_GET["ID"]>0){
		$array["zarafa-instance-webmail"]="WebMail";
		$array["zarafa-instance-imap"]="{mailbox_protocols}";
		
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_instance_zarafa_multi style='width:100%;height:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_instance_zarafa_multi').tabs();
				});
				
				$('#freeweb-zarafa-list').remove();
		</script>";			
}

	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_address=$tpl->_ENGINE_parse_body("{listen_address}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$mysql_multi_explain=$tpl->_ENGINE_parse_body("{mysql_multi_explain}");
	$buttons="
	buttons : [
	{name: '$new_server', bclass: 'add', onpress : AddZarafaServer},
	],";		
		
	

$html="
<span id='zarafa-instances-table'></span>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?zarafa-instances-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'servername', width : 183, sortable : false, align: 'left'},	
		{display: '$listen_address', name : 'listen_addr', width :150, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'description', width :173, sortable : false, align: 'left'},
		{display: 'stats', name : 'stats', width :32, sortable : false, align: 'left'},
		{display: '$status', name : 'enabled', width : 25, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'pattern'},
		],
	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 730,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}

	function FlexReloadZarafaInstanceTable(){
		$('#flexRT$t').flexReload();
	}



function AddZarafaServer(){
	Loadjs('$page?zarafa-instance-js=yes&ID=');

}

function MysqlInstanceDelete(ID){
	
}




</script>

";	
	echo $html;
	
}

function instances_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="zarafamulti";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,'artica_backup')==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}else{$_POST['rp']=10;}
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$errorou=null;
		$uptime=null;
		$PostfixInstanceText=null;
		$icon_status="danger24.png";
		$icon_stop=imgtootltip("24-stop.png","{stop}","Loadjs('$MyPage?zarafa-server-service-js=yes&ID={$ligne["ID"]}&action=stop');");
		$icon_stopoff=imgtootltip("24-stop-grey.png","{stop}","");
		$icon_run=imgtootltip("24-run.png","{run}","Loadjs('$MyPage?zarafa-server-service-js=yes&ID={$ligne["ID"]}&action=start');");
		
		$icon_stats=imgtootltip("statistics-24.png","{run}","Loadjs('system.mysql.graphs.php?instance-id={$ligne["ID"]}');");
		$icon_stats="&nbsp;";
		if(!isset($ligne["usesocket"])){$ligne["usesocket"]=0;}
		$GBSTAT=services_status_unik($id);
		if($GBSTAT){$icon_status="ok24.png";}else{$icon_stop=$icon_run;$explain="{running}";}
		if(!$GBSTAT){$explain="{stopped}";}
		$explain=$tpl->_ENGINE_parse_body($explain);
		
		$js="Loadjs('$MyPage?zarafa-instance-js=yes&ID={$ligne["ID"]}');";
		$delete=imgtootltip("delete-24.png","{delete} {$ligne["pattern"]}","Loadjs('$MyPage?zarafa-server-delete-js=yes&ID={$ligne["ID"]}');");
		
		if($ligne["ou"]==null){
			$errorou=$tpl->_ENGINE_parse_body("<div style='color:#D71313;font-size:11px'>{no_organization_selected}</div>");
			$icon_status="warning-panneau-24.png";
		}
		if($ligne["ou"]<>null){
			if($ligne["PostfixInstance"]<>null){
				$PostfixInstanceText="<div style='font-size:11px'>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:YahooWin('650','domains.postfix.multi.config.php?ou={$ligne["ou"]}&hostname={$ligne["PostfixInstance"]}','{$ligne["PostfixInstance"]}');\"
				style='font-size:11px;text-decoration:underline'>SMTP: {$ligne["PostfixInstance"]}</a></div>";
			}
		}
		
		
		$net="{$ligne["listen_addr"]}:{$ligne["listen_port"]}";
		if($ligne["usesocket"]==0){$net="127.0.0.1:{$ligne["listen_port"]}";}
		$uptime=services_status_running_since($ligne["ID"]);
		if($uptime<>null){$uptime=$tpl->_ENGINE_parse_body("{running} {since}:$uptime");}
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;text-decoration:underline'>{$ligne["servername"]}</span>"
		,"<span style='font-size:16px'>$net</span>",
		"<span style='font-size:14px'>$explain $uptime$errorou$PostfixInstanceText</span>",
		$icon_stats,
		"<img src='img/$icon_status' style='margin-top:5px'>",
		"<span id='animate-service-instance-{$ligne["ID"]}'>$icon_stop</span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		
}

function zarafa_instance_webmail(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){zarafa_instance_webmail_error_freeweb();return;}

	$html="
	<div id='freeweb-zarafa-list'></div>
	
	<script>
		LoadAjax('freeweb-zarafa-list','freeweb.php?popup=yes&force-groupware=ZARAFA-WEBS&no-add=yes&ForceInstanceZarafaID={$_GET["ID"]}');
	</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
	
}
function zarafa_instance_webmail_error_freeweb(){
		$tpl=new templates();
		$page=CurrentPageName();
		$sock=new sockets();
		$t=time();
		$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
		$freeweb=Paragraphe_switch_img("{enable_freeweb}","{enable_freeweb_text}","EnableFreeWebC$t",$EnableFreeWeb,null,400);
		$form="<hr><div style='text-align:right;width:100%' id='zarafa-error'>". button("{apply}", "SaveZarafaWebEngine()",14)."</div>";		
		$html= "<H2><img src='img/error-128.png' align='left' style='margin:5px'>{ERROR_ROUNDCUBE_MULTIPLE_INSTANCES_FREEWEB}</H2>
		<div style='width:95%;margin:10px' class=form>
		$freeweb
		$form
		</div>
		<script>
	
	
	var x_SaveZarafaWebEngine2=function (obj) {
			var results=obj.responseText;
			RefreshTab('main_config_instance_zarafa_multi');
		}	
		
		function SaveZarafaWebEngine(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',document.getElementById('EnableFreeWebC$t').value);
 			AnimateDiv('zarafa-error');
    		XHR.sendAndLoad('zarafa.freewebs.php', 'POST',x_SaveZarafaWebEngine2);
			
		}		
	
		
	</script>";		
	echo $tpl->_ENGINE_parse_body($html);
}


function GetLastPort(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT listen_port FROM zarafamulti ORDER BY listen_port DESC LIMIT 0,1","artica_backup"));
	if(!is_numeric($ligne["listen_port"])){$ligne["listen_port"]=236;}
	return $ligne["listen_port"];
}
function GetLastlmtpport(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT lmtp_port FROM zarafamulti ORDER BY lmtp_port DESC LIMIT 0,1","artica_backup"));
	if(!is_numeric($ligne["lmtp_port"])){$ligne["lmtp_port"]=2003;}
	return $ligne["lmtp_port"];
}
function GetLastInstanceNum(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM zarafamulti ORDER BY ID DESC LIMIT 0,1","artica_backup"));
	if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}	
	return $ligne["ID"];
}