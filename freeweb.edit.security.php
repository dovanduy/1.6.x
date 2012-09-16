<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.system.network.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_POST["ApacheServerSignature"])){SaveConf();exit;}
	params();
	

	
function params(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
	$ApacheServerTokens=$sock->GET_INFO("ApacheServerTokens");
	if($ApacheServerTokens==null){$ApacheServerTokens="Full";}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$Params=unserialize(base64_decode($ligne["Params"]));
	$apache_auth_ip_explain=$tpl->javascript_parse_text("{apache_auth_ip_explain}");
	$users=new usersMenus();
	$APACHE_MOD_AUTHNZ_LDAP=0;
	$APACHE_MOD_GEOIP=0;
	if($users->APACHE_MOD_AUTHNZ_LDAP){$APACHE_MOD_AUTHNZ_LDAP=1;}
	if($users->APACHE_MOD_GEOIP){$APACHE_MOD_GEOIP=1;}
	$ServerSignature=$sock->GET_INFO("ApacheServerSignature");
	if(!is_numeric($ServerSignature)){$ServerSignature=1;}	
	if(!is_numeric($FreeWebsEnableModSecurity)){$FreeWebsEnableModSecurity=0;}
	if(!is_numeric($FreeWebsEnableModEvasive)){$FreeWebsEnableModEvasive=0;}
	$SecServerSignatureOffExplain=false;
	if($FreeWebsEnableModSecurity==0){$SecServerSignatureOffExplain=true;}
	if($ApacheServerTokens<>"Full"){$SecServerSignatureOffExplain=true;}	
	
	$ZarafaWebNTLM=0;
	if($ligne["groupware"]=="ZARAFA"){
		$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");
		if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
		
	}
	
	
	$t=time();
	$authentication_banner=$Params["LDAP"]["authentication_banner"];
	$EnableLDAPAllSubDirectories=$Params["LDAP"]["EnableLDAPAllSubDirectories"];
	if(strlen($authentication_banner)<3){
		$authentication_banner=base64_encode($tpl->javascript_parse_text("{$_GET["servername"]}::{authentication}"));
	}

	$FreeWebsDisableBrowsing=$Params["SECURITY"]["FreeWebsDisableBrowsing"];
	if(!is_numeric($FreeWebsDisableBrowsing)){$FreeWebsDisableBrowsing=0;}
	$ApacheServerSignature=$Params["SECURITY"]["ServerSignature"];
	$SecServerSignature=$Params["mod_security"]["SecServerSignature"];
	$DisableHtAccess=$Params["SECURITY"]["DisableHtAccess"];
	if(!is_numeric($ApacheServerSignature)){$ApacheServerSignature=$ServerSignature;}
	if(!is_numeric($EnableLDAPAllSubDirectories)){$EnableLDAPAllSubDirectories=0;}
	if($SecServerSignatureOffExplain){$SecServerSignatureOffExplainText=imgtootltip("warning-panneau-24.png","{SecServerSignatureOffExplain}");}
	$SecServerSignature=$sock->GET_INFO("SecServerSignature");
	$SecServerSignatureOffExplain=null;

$mod_security="
	<tr>
		<td class=legend style='font-size:14px'>{security_enforcement}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.mod.security.php?servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>
";

$mod_geoip="
	<tr>
		<td class=legend style='color:#CCCCCC;;font-size:14px'>{country_block}:</td>
		<td><span style='font-size:13px;text-decoration:underline;color:#CCCCCC'>{edit}</span></td>
		<td width=1%>&nbsp;</td>
	</tr>
";




$mod_evasive="
	<tr>
		<td class=legend style='font-size:14px'>{DDOS_prevention}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.mod.evasive.php?servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>
";
	
if($FreeWebsEnableModSecurity==0){
	$mod_security="
	<tr>
		<td class=legend style='color:#CCCCCC;font-size:14px'>{security_enforcement}:</td>
		<td><a href=\"javascript:blur();\" style='font-size:13px;color:#CCCCCC'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>
";
}
if($FreeWebsEnableModEvasive==0){
	$mod_evasive="
	<tr>
		<td class=legend style='color:#CCCCCC;font-size:14px' >{DDOS_prevention}:</td>
		<td><a href=\"javascript:blur();\"
		style='font-size:13px;color:#CCCCCC'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>
";
}

if($APACHE_MOD_GEOIP==1){

$mod_geoip="
<tr>
		<td class=legend style='font-size:14px'>{country_block}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.mode.geoip.php?servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>
";
}


	
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{ApacheServerSignature}:</td>
		<td>". Field_checkbox("ApacheServerSignature",1,$ApacheServerSignature)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{SecServerSignature}:</td>
		<td>". Field_text("SecServerSignature",$SecServerSignature,"font-size:14px;width:240px")."</td>
		<td width=1%>$SecServerSignatureOffExplainText</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:14px'>{DisableHtAccess}:</td>
		<td>". Field_checkbox("DisableHtAccess",1,$DisableHtAccess)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsDisableBrowsing}:</td>
		<td width=1%>". Field_checkbox("FreeWebsDisableBrowsing",1,$FreeWebsDisableBrowsing)."&nbsp;
		<a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.edit.IndexIgnore.php?servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%></td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{RewriteRules}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.edit.php?rewrite=yes&servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{files_and_folders_permissions}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.permissions.php?servername={$_GET["servername"]}');\"
		style='font-size:13px;text-decoration:underline'>{edit}<a></td>
		<td width=1%>&nbsp;</td>
	</tr>		
	$mod_security	
	$mod_evasive	
	$mod_geoip
	</table>
	<div style='text-align:right;width:100%'><hr>". button("{apply}","SaveConfig$t()",16)."</div>
	</div>
	
	<script>
		var x_SaveConfig$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			RefreshTab('main_config_freewebeditsecu');		
		}

		
		function SaveConfig$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SecServerSignature',document.getElementById('SecServerSignature').value);
			if(document.getElementById('ApacheServerSignature').checked){XHR.appendData('ApacheServerSignature',1);}else{XHR.appendData('ApacheServerSignature',0);}
			if(document.getElementById('DisableHtAccess').checked){XHR.appendData('DisableHtAccess',1);}else{XHR.appendData('DisableHtAccess',0);}
			if(document.getElementById('FreeWebsDisableBrowsing').checked){XHR.appendData('FreeWebsDisableBrowsing',1);}else{XHR.appendData('FreeWebsDisableBrowsing',0);}
			
			
			
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveConfig$t);			
		}
		
		function CheckForm$t(){
			var FreeWebsEnableModSecurity=$FreeWebsEnableModSecurity;
			var ApacheServerTokens='$ApacheServerTokens';
			document.getElementById('SecServerSignature').disabled=true;
			if(FreeWebsEnableModSecurity==0){return;}
			return;
			if(ApacheServerTokens=='Full'){
				document.getElementById('SecServerSignature').disabled=false;
			}
		
		
		}
		
		CheckForm$t();
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	}
	
function SaveConf(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_POST["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$Params=unserialize(base64_decode($ligne["Params"]));	
	$Params["SECURITY"]["ServerSignature"]=$_POST["ApacheServerSignature"];
	$Params["SECURITY"]["DisableHtAccess"]=$_POST["DisableHtAccess"];
	$Params["SECURITY"]["FreeWebsDisableBrowsing"]=$_POST["FreeWebsDisableBrowsing"];
	$Params["mod_security"]["SecServerSignature"]=$_POST["SecServerSignature"];
	
	
	
	$data=addslashes(base64_encode(serialize($Params)));
	$sql="UPDATE freeweb SET `Params`='$data' WHERE servername='{$_POST["servername"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
	
}	
		
