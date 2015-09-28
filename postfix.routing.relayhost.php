<?php
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_POST["hostname"])){save();exit;}
	page();
function page(){

	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();

	$ligne=mysql_fetch_array(
			
			$q->QUERY_SQL("SELECT * FROM relay_host WHERE hostname='{$_GET["hostname"]}'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error_html();}
	$t=time();
	
	if(!is_numeric($ligne["relay_port"])){$ligne["relay_port"]=25;}
	

	
	$form="<div style='font-size:30px'>{relayhost}</div>
	<div class=explain style='font-size:18px'>{relayhost_text}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		</tr>
			<td align='right' nowrap class=legend style='font-size:22px'>{enabled}:</strong></td>
			<td style='font-size:12px'>" . Field_checkbox_design("enabled-$t",1,$ligne["enabled"],"CheckEnabled$t()") . "</td>
		</tr>
								
		<tr>
			<td align='right' nowrap class=legend style='font-size:22px'>{relay_address}:</strong></td>
			<td style='font-size:22px'>" . Field_text("relay_address-$t",$ligne["relay"],"font-size:22px;padding:3px") . "</td>
		</tr>
		</tr>
			<td align='right' nowrap class=legend style='font-size:22px'>{smtp_port}:</strong></td>
			<td style='font-size:12px'>" . Field_text("relay_port-$t",$ligne["relay_port"],"font-size:22px;padding:3px;width:110px") . "</td>
		</tr>
		<tr>
			<td style='font-size:22px' class=legend>{MX_lookups}</td>
			<td>" . Field_checkbox_design("lookups-$t",1,$ligne["lookups"])."</td>
		</tr>					
		</tr>
			<td align='right' nowrap class=legend style='font-size:22px'>{authenticate}:</strong></td>
			<td style='font-size:12px'>" . Field_checkbox_design("enabledauth-$t",1,$ligne["enabledauth"],"Checkenabledauth$t()") . "</td>
		</tr>					
					
					
		<tR>
			<td align='right' nowrap class=legend style='font-size:22px'>{username}:</strong></td>
			<td style='font-size:12px'>" . Field_text("relay_username-$t",$ligne["username"],"font-size:22px;padding:3px") . "</td>
		</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:22px'>{password}:</strong></td>
		<td style='font-size:12px'>" . Field_password("relay_password-$t",$ligne["password"],"font-size:22px;padding:3px;") . "</td>
	</tr>
	<tr>
	<td align='right' colspan=2 align='right' style='font-size:22px'>
				<p>&nbsp;</p>".button("{apply}","Loadjs('postfix.sender.routing.progress.php?hostname={$_GET["hostname"]}')",40)."&nbsp;|&nbsp;".
				 button("{save}","PostfixSaveRelayHost$t()",40)."</td>
	</tr>
	</table>
	</div>
<script>
var X_PostfixSaveRelayHost$t= function (obj) {
	var results=trim(obj.responseText);
	if(results.length>2){alert(results);}
}
function PostfixSaveRelayHost$t(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('relay_address',document.getElementById('relay_address-$t').value);
	XHR.appendData('relay_username',document.getElementById('relay_username-$t').value);
	
	
	
	
	XHR.appendData('relay_password',encodeURIComponent(document.getElementById('relay_password-$t').value));
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled','1');}else{XHR.appendData('enabled','0');}
	if(document.getElementById('enabledauth-$t').checked){XHR.appendData('enabledauth','1');}else{XHR.appendData('enabledauth','0');}
	if(document.getElementById('lookups-$t').checked){XHR.appendData('lookups','1');}else{XHR.appendData('lookups','0');}
	
	XHR.appendData('relay_port',document.getElementById('relay_port-$t').value);
	XHR.sendAndLoad('$page', 'POST',X_PostfixSaveRelayHost$t);
	
	}
function CheckEnabled$t(){
	document.getElementById('relay_address-$t').disabled=true;
	document.getElementById('relay_port-$t').disabled=true;
	document.getElementById('relay_username-$t').disabled=true;
	document.getElementById('relay_password-$t').disabled=true;
	document.getElementById('lookups-$t').disabled=true;

	if(document.getElementById('enabled-$t').checked){
		document.getElementById('relay_address-$t').disabled=false;
		document.getElementById('relay_port-$t').disabled=false;
		document.getElementById('relay_username-$t').disabled=false;
		document.getElementById('relay_password-$t').disabled=false;
		document.getElementById('lookups-$t').disabled=false;
	}
	Checkenabledauth$t();
}

function Checkenabledauth$t(){
	document.getElementById('relay_username-$t').disabled=true;
	document.getElementById('relay_password-$t').disabled=true;
	if(!document.getElementById('enabled-$t').checked){return;}
	if(document.getElementById('enabledauth-$t').checked){
		document.getElementById('relay_username-$t').disabled=false;
		document.getElementById('relay_password-$t').disabled=false;
	}
}

CheckEnabled$t();

</script>";
echo $tpl->_ENGINE_parse_body("$form");
}	

function save(){

	$_POST["relay_password"]=url_decode_special_tool($_POST["relay_password"]);
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("relay_host","enabledauth","artica_backup")){
		$sql="ALTER TABLE `relay_host` ADD `enabledauth` smallint( 1 ) NOT NULL ";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("relay_host","username","artica_backup")){
		$sql="ALTER TABLE `relay_host` ADD `username` VARCHAR( 128 ) NOT NULL ";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	if(!$q->FIELD_EXISTS("relay_host","password","artica_backup")){
		$sql="ALTER TABLE `relay_host` ADD `password` VARCHAR( 128 ) NOT NULL ";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	if(!$q->FIELD_EXISTS("relay_host","lookups","artica_backup")){
		$sql="ALTER TABLE `relay_host` ADD `lookups` smallint(1) NOT NULL ";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	if($_POST["relay_address"]==null){
		echo "relay address: not set...\n";
		return;
	}
	
	if($_POST["relay_port"]==0){$_POST["relay_port"]=25;}

	$q->QUERY_SQL("DELETE FROM `relay_host` WHERE hostname='{$_POST["hostname"]}'","artica_backup");
	$sql="INSERT IGNORE INTO relay_host
	(`hostname`,`enabled`,`enabledauth`,`relay`,`relay_port`,`username`,`password`,`lookups`)
	VALUES('{$_POST["hostname"]}','{$_POST["enabled"]}','{$_POST["enabledauth"]}','{$_POST["relay_address"]}',
	'{$_POST["relay_port"]}','{$_POST["relay_username"]}','{$_POST["relay_password"]}','{$_POST["lookups"]}')";
	$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){echo $q->mysql_error;}
	
}
