<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsPostfixAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ENABLED_WATCHDOG"])){save_watchdog_notif();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["notifs"])){smtp_notifs();exit;}
if(isset($_POST["test-it"])){test_smtp_notifs();exit;}
js();

function js(){

	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{watchdog_queue}");
	$html="YahooWin3('850','$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["notifs"]="{smtp_notifications}";
	





	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "postqueuep_tabs");

}

function smtp_notifs(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ini->loadString($sock->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){$users=new usersMenus();$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";}
	$t=time();
	$PostfixSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("PostfixSMTPNotifs")));
	if(!isset($PostfixSMTPNotifs["ENABLED_WATCHDOG"])){$PostfixSMTPNotifs["ENABLED_WATCHDOG"]=0;}
	if(!is_numeric($PostfixSMTPNotifs["ENABLED_WATCHDOG"])){$PostfixSMTPNotifs["ENABLED_WATCHDOG"]=0;}
	if(!isset($PostfixSMTPNotifs["smtp_server_name"])){$PostfixSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if(!isset($PostfixSMTPNotifs["smtp_server_port"])){$PostfixSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if(!isset($PostfixSMTPNotifs["smtp_sender"])){$PostfixSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!isset($PostfixSMTPNotifs["smtp_dest"])){$PostfixSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
	if(!isset($PostfixSMTPNotifs["smtp_auth_user"])){$PostfixSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
	if(!isset($PostfixSMTPNotifs["smtp_auth_passwd"])){$PostfixSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
	if(!isset($PostfixSMTPNotifs["tls_enabled"])){$PostfixSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
	if(!isset($PostfixSMTPNotifs["ssl_enabled"])){$PostfixSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
	if(!is_numeric($PostfixSMTPNotifs["smtp_server_port"])){$PostfixSMTPNotifs["smtp_server_port"]=25;}
	if(!is_numeric($PostfixSMTPNotifs["max_messages"])){$PostfixSMTPNotifs["max_messages"]=300;}

	$html="
	<div id='notif1-$t'></div>

	<table style='width:99%' class=form>
	<tr>
	<td nowrap class=legend style='font-size:14px'>{smtp_enabled}:</strong></td>
	<td>" . Field_checkbox("ENABLED_WATCHDOG",1,$PostfixSMTPNotifs["ENABLED_WATCHDOG"],"SMTPNotifArticaEnableSwitch$t()")."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{max_messages}:</strong></td>
		<td>" . Field_text('max_messages',trim($PostfixSMTPNotifs["max_messages"]),'font-size:14px;padding:3px;width:90px')."</td>
	</tr>			
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text('smtp_server_name',trim($PostfixSMTPNotifs["smtp_server_name"]),'font-size:14px;padding:3px;width:250px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text('smtp_server_port',trim($PostfixSMTPNotifs["smtp_server_port"]),'font-size:14px;padding:3px;width:40px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_sender}:</strong></td>
		<td>" . Field_text('smtp_sender',trim($PostfixSMTPNotifs["smtp_sender"]),'font-size:14px;padding:3px;width:290px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_dest}:</strong></td>
		<td>" . Field_text('smtp_dest',trim($PostfixSMTPNotifs["smtp_dest"]),'font-size:14px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text('smtp_auth_user',trim($PostfixSMTPNotifs["smtp_auth_user"]),'font-size:14px;padding:3px;width:200px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($PostfixSMTPNotifs["smtp_auth_passwd"]),'font-size:14px;padding:3px;width:200px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox("tls_enabled",1,$PostfixSMTPNotifs["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:14px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox("ssl_enabled",1,$PostfixSMTPNotifs["ssl_enabled"])."</td>
	</tr>
	<tr>
		<td align='right' colspan=2>".button('{apply}',"SaveArticaSMTPNotifValues$t();",16)."</td>
	</tr>
	<tr>
		<td align='right' colspan=2>".button('{test}',"TESTSSMTPNotifValues$t();",16)."</td>
	</tr>
		</tr>
		</table>
		<script>
var x_SaveArticaSMTPNotifValues$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('notif1-$t').innerHTML='';
		if(results.length>3){alert(results);}
		RefreshTab('postqueuep_tabs');
}

var xtests$t= function (obj) {
		var results=obj.responseText;
		
		if(results.length>3){alert(results);}
		
}

function SaveArticaSMTPNotifValues$t(){
	var XHR = new XHRConnection();
	var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
	if(document.getElementById('ENABLED_WATCHDOG').checked){XHR.appendData('ENABLED_WATCHDOG',1);}else {XHR.appendData('ENABLED_SQUID_WATCHDOG',0);}
	if(document.getElementById('tls_enabled').checked){XHR.appendData('tls_enabled',1);}else {XHR.appendData('tls_enabled',0);}
	if(document.getElementById('ssl_enabled').checked){XHR.appendData('ssl_enabled',1);}else {XHR.appendData('ssl_enabled',0);}
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
	XHR.appendData('smtp_dest',document.getElementById('smtp_dest').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
	XHR.appendData('max_messages',document.getElementById('max_messages').value);
	
	
	
	XHR.appendData('smtp_auth_passwd',pp);
	XHR.appendData('smtp_notifications','yes');
	AnimateDiv('notif1-$t');
	XHR.sendAndLoad('$page', 'POST',x_SaveArticaSMTPNotifValues$t);
}

function TESTSSMTPNotifValues$t(){
	SaveArticaSMTPNotifValues$t();
	var XHR = new XHRConnection();
	XHR.appendData('test-it','yes');
	XHR.sendAndLoad('$page', 'POST',xtests$t);
}

function SMTPNotifArticaEnableSwitch$t(){
	document.getElementById('smtp_auth_passwd-$t').disabled=true;
	document.getElementById('smtp_auth_user').disabled=true;
	document.getElementById('smtp_dest').disabled=true;
	document.getElementById('smtp_sender').disabled=true;
	document.getElementById('smtp_server_port').disabled=true;
	document.getElementById('smtp_server_name').disabled=true;
	document.getElementById('tls_enabled').disabled=true;
	document.getElementById('ssl_enabled').disabled=true
	document.getElementById('max_messages').disabled=true;
	
	
	
	if(!document.getElementById('ENABLED_WATCHDOG').checked){return;}
	
	document.getElementById('smtp_auth_passwd-$t').disabled=false;
	document.getElementById('smtp_auth_user').disabled=false;
	document.getElementById('smtp_dest').disabled=false;
	document.getElementById('smtp_sender').disabled=false;
	document.getElementById('smtp_server_port').disabled=false;
	document.getElementById('smtp_server_name').disabled=false;
	document.getElementById('tls_enabled').disabled=false;
	document.getElementById('ssl_enabled').disabled=false;
	document.getElementById('max_messages').disabled=false;

}
SMTPNotifArticaEnableSwitch$t();
</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function save_watchdog_notif(){
	$sock=new sockets();
	$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	$PostfixSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("PostfixSMTPNotifs")));
	while (list ($num, $ligne) = each ($_POST) ){
		$PostfixSMTPNotifs[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($PostfixSMTPNotifs)), "PostfixSMTPNotifs");
	
}
function test_smtp_notifs(){
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("postfix.php?tests-smtp-watchdog=yes"));
	
}