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
	
	
	if(isset($_POST["enable_ldap_authentication"])){SaveConfig();exit;}
	params();
	
	
function params(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$free=new freeweb($_GET["servername"]);
	$Params=$free->Params;
	$apache_auth_ip_explain=$tpl->javascript_parse_text("{apache_auth_ip_explain}");
	$users=new usersMenus();
	$APACHE_MOD_AUTHNZ_LDAP=0;
	$APACHE_MOD_GEOIP=0;
	$PERL_AUTHNTLM=0;
	$info=null;
	if($users->APACHE_MOD_AUTHNZ_LDAP){$APACHE_MOD_AUTHNZ_LDAP=1;}
	if($users->APACHE_MOD_GEOIP){$APACHE_MOD_GEOIP=1;}
	if($users->APP_PYAUTHENNTLM){$PERL_AUTHNTLM=1;}
	$ServerSignature=$sock->GET_INFO("ApacheServerSignature");
	if(!is_numeric($ServerSignature)){$ServerSignature=1;}	
	if(!is_numeric($FreeWebsEnableModSecurity)){$FreeWebsEnableModSecurity=0;}
	if(!is_numeric($FreeWebsEnableModEvasive)){$FreeWebsEnableModEvasive=0;}
	$ZarafaWebNTLM=0;
	$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");
	if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}	
	if($ligne["groupware"]=="ZARAFA"){
		$PARAMS=$free->Params["ZARAFAWEB_PARAMS"];
		if(!isset($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$ZarafaWebNTLM;}
		if(!is_numeric($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$ZarafaWebNTLM;}
		$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
		
	}
	
	
	
	$authentication_banner=$Params["LDAP"]["authentication_banner"];
	$EnableLDAPAllSubDirectories=$Params["LDAP"]["EnableLDAPAllSubDirectories"];
	if(strlen($authentication_banner)<3){
		$authentication_banner=base64_encode($tpl->javascript_parse_text("{$_GET["servername"]}::{authentication}"));
	}

	$ApacheServerSignature=$Params["SECURITY"]["ServerSignature"];
	$DisableHtAccess=$Params["SECURITY"]["DisableHtAccess"];
	if(!is_numeric($ApacheServerSignature)){$ApacheServerSignature=$ServerSignature;}
	if(!is_numeric($EnableLDAPAllSubDirectories)){$EnableLDAPAllSubDirectories=0;}
	$t=time();

	$FreeWebPerformances=unserialize(base64_decode($sock->GET_INFO("FreeWebPerformances")));
	if(!isset($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
	
	if($FreeWebPerformances["KeepAlive"]==0){
		$info=$info.FATAL_ERROR_SHOW_128("{KeepAlive_tlm_notset}");
	}

	if($PERL_AUTHNTLM==0){
		$info=$info.FATAL_ERROR_SHOW_128("{pythonntlm_not_installed}");
		
	}
	//http://www.mail-appliance.org/index.php?cID=393
	$onlineHelp="<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://mail-appliance.org/index.php?cID=393','1024','900');\"
	style='font-size:12px;font-weight:bold;text-decoration:underline'>{online_help}</a>";
	
	$html="
	<div id='$t'>
	<input type='hidden' id='EnableLDAPAllSubDirectories' value='$EnableLDAPAllSubDirectories'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enable_ldap_authentication}:</td>
		<td>". Field_checkbox("enable_ldap_authentication",1,$Params["LDAP"]["enabled"],"CheckApacheLdap$t()")."</td>
		<td width=1%><span id='disabled-why' style='font-size:11px;color:#d32d2d'></span>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{authentication_banner}:</td>
		<td>". Field_text("authentication_banner",base64_decode($authentication_banner),"font-size:16px;padding:3px;width:280px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{members}:</td>
		<td><input type='button' OnClick=\"javascript:Loadjs('freeweb.edit.ldap.users.php?servername={$_GET["servername"]}')\" value='{browse}...' style='font-size:13px'></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enable_ntlm_authentication}:</td>
		<td>". Field_checkbox("enable_ntlm_authentication",1,$Params["NTLM"]["enabled"],"CheckApacheNTLM$t()")."</td>
		<td width=1% nowrap>$onlineHelp</span>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{members}:</td>
		<td><input type='button' OnClick=\"javascript:Loadjs('freeweb.edit.ldap.users.php?servername={$_GET["servername"]}')\" value='{browse}...' style='font-size:13px'></td>
		<td>&nbsp;</td>
	</tr>
	</table>	
	<div style='text-align:right;width:100%'><hr>". button("{apply}","CheckApacheLdap$t()",16)."</div>
	</div>
	$info
	<script>
		var x_CheckApacheLdap$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_freewebeditsecu');				
		}	
	
	
		function CheckApacheForm$t(){
			var ZarafaWebNTLM=$ZarafaWebNTLM;
			var APACHE_MOD_AUTHNZ_LDAP=$APACHE_MOD_AUTHNZ_LDAP;
			var PERL_AUTHNTLM=$PERL_AUTHNTLM;
			document.getElementById('enable_ldap_authentication').disabled=true;
			document.getElementById('authentication_banner').disabled=true;
			document.getElementById('EnableLDAPAllSubDirectories').disabled=true;
			document.getElementById('enable_ntlm_authentication').disabled=true;
			
			
			
			if(APACHE_MOD_AUTHNZ_LDAP==1){
				document.getElementById('enable_ldap_authentication').disabled=false;
				document.getElementById('authentication_banner').disabled=false;
				if(document.getElementById('enable_ldap_authentication').checked){
					document.getElementById('EnableLDAPAllSubDirectories').disabled=false;
				}
			}
			
			if(PERL_AUTHNTLM==1){
				document.getElementById('enable_ntlm_authentication').disabled=false;
			}
			
			if(ZarafaWebNTLM==1){
				
				document.getElementById('enable_ntlm_authentication').disabled=true;
				document.getElementById('enable_ldap_authentication').disabled=true;
				document.getElementById('enable_ldap_authentication').checked=true;
				document.getElementById('disabled-why').innerHTML='NTLM enabled';
			}
			
		}
		
		
		
		function CheckApacheLdap$t(){
			var XHR = new XHRConnection();
			
			
			if(document.getElementById('enable_ldap_authentication').checked){
				XHR.appendData('enable_ldap_authentication',1);
				document.getElementById('EnableLDAPAllSubDirectories').disabled=false;
			}else{
				XHR.appendData('enable_ldap_authentication',0);
				document.getElementById('EnableLDAPAllSubDirectories').disabled=true;
			}
			
			if(document.getElementById('enable_ntlm_authentication').checked){
				XHR.appendData('enable_ntlm_authentication',1);
			}else{
				XHR.appendData('enable_ntlm_authentication',0);
			}
			if(document.getElementById('EnableLDAPAllSubDirectories').checked){XHR.appendData('EnableLDAPAllSubDirectories',1);}else{XHR.appendData('EnableLDAPAllSubDirectories',0);}				
			XHR.appendData('servername','{$_GET["servername"]}');
			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST', x_CheckApacheLdap$t);
		}
		

	CheckApacheForm$t();
	</script>";

	echo $tpl->_ENGINE_parse_body($html);
	}	

	
function SaveConfig(){
	$free=new freeweb($_POST["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$free->Params["NTLM"]["enabled"]=$_POST["enable_ntlm_authentication"];
	$free->Params["LDAP"]["enabled"]=$_POST["enable_ldap_authentication"];
	$free->Params["LDAP"]["authentication_banner"]=base64_encode($_POST["authentication_banner"]);
	$free->Params["LDAP"]["EnableLDAPAllSubDirectories"]=$_POST["EnableLDAPAllSubDirectories"];
	$free->SaveParams();

	
	
}	