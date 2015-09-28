<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_POST["ADSERVERNAME"])){Save();exit;}
	

page();




function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ldap=new clladp();
	$t=time();
	$ImportAdSettings=unserialize($sock->GET_INFO("ZarafaImportADSettings"));
	
	
	$OUS=$ldap->hash_get_ou(true);

	$html="
	<div style='font-size:30px;margin-bottom:20px'>{active_directory_importation}</div>
	<div style='font-size:22px' class=explain>{active_directory_importation_contacts_explain}</div>
	
	
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{organization}:</td>
		<td>". Field_array_Hash($OUS, "ADOU",$ImportAdSettings["ADOU"],"style:font-size:22px;")."</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{activedirectory_server}:</td>
		<td>". Field_text("ADSERVERNAME",$ImportAdSettings["ADSERVERNAME"],"font-size:22px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX",$ImportAdSettings["LDAP_SUFFIX"],"font-size:22px;padding:3px;width:580px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{username}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$ImportAdSettings["WINDOWS_SERVER_ADMIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$ImportAdSettings["WINDOWS_SERVER_PASS"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	<tr style='height:70px'>
		<td colspan=3 align='right'>".button("{import_contacts}", "Save$t()",30)."</td>
	</tR>
	
	</table>
	</div>
	<script>
		
		

var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>5){alert(tempvalue);}
    Loadjs('zarafa.import-ad-contacts.progress.php');
}	
		
function Save$t(){
	var pp=encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value);
	var XHR = new XHRConnection();
	XHR.appendData('ADOU',document.getElementById('ADOU').value);
	XHR.appendData('ADSERVERNAME',document.getElementById('ADSERVERNAME').value);
	XHR.appendData('WINDOWS_SERVER_ADMIN',document.getElementById('WINDOWS_SERVER_ADMIN').value);
	XHR.appendData('LDAP_SUFFIX',encodeURIComponent(document.getElementById('LDAP_SUFFIX').value));
	
	XHR.appendData('WINDOWS_SERVER_PASS',pp);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);	
}
function Save(){
	$sock=new sockets();
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	$_POST["LDAP_SUFFIX"]=url_decode_special_tool($_POST["LDAP_SUFFIX"]);
	$sock->SaveConfigFile(serialize($_POST),"ZarafaImportADSettings");
}