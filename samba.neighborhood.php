<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}

	
	if(!CheckSambaRights()){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H1>$ERROR_NO_PRIVS</H1>";die();
	}	


	if(isset($_GET["change-role"])){change_role_explain();exit;}
	if(isset($_GET["change-role-form"])){change_role_form();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["exp-security-explain"])){security_user_explain();exit;}
	if(isset($_POST["neighborhood-save"])){neighborhood_save();exit;}
	
js();	
	

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$windows_network_neighborhood=$tpl->_ENGINE_parse_body('{windows_network_neighborhood}');
	$html="YahooWinBrowse('550','$page?popup=yes','$windows_network_neighborhood')";
	echo $html;
}



function popup(){
	
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$samba=new samba();
	$users=new usersMenus();
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	$SambaSecurityLevel=$sock->GET_INFO("SambaSecurityLevel");
	if($SambaSecurityLevel==null){$SambaSecurityLevel="user";}	
	
	$hash[null]="{default}";
	$hash["user"]="{user_level_security}";
	$hash["share"]="{share_level_security}";
	$hash["domain"]="{domain_security}";
	$hash["ADS"]="{active_directory_security}";	
	
	
	$currentrole=$sock->getFrameWork("cmd.php?samba-server-role=yes");
	$current_text="<div style='font-size:16px;font-weight:bold;margin-bottom:10px'>{current}:&nbsp;{{$currentrole}}/{$hash[$SambaSecurityLevel]}</div>";
	if($EnableSambaActiveDirectory==1){
		$html="
		<center style='margin:10px;font-size:14px'>{this_server_is_an_ad_member}$current_text</center>
		";
		echo $tpl->_ENGINE_parse_body($html);
		exit;
	}

	$TypeOfSamba=$sock->GET_INFO("TypeOfSamba");
	if(!is_numeric($TypeOfSamba)){
		switch ($currentrole) {
			case "ROLE_STANDALONE": $TypeOfSamba=1;break;
			case "ROLE_DOMAIN_PDC": $TypeOfSamba=3;break;
			default:$TypeOfSamba=1;break;
		}
		
		$sock->SET_INFO("TypeOfSamba",$TypeOfSamba);
		
		
	}
	
	$hasApdc=0;
	$hasLocalMaster=0;
	$HasSingleMode=0;
	
	switch ($TypeOfSamba) {
		case 1: $VALUE="SINGLE_MODE";break;
		case 2: $VALUE="LOCAL_MASTER";break;
		case 3: $VALUE="ROLE_DOMAIN_PDC";break;
		default:$VALUE="SINGLE_MODE";break;
	}
	
	$typeList["SINGLE_MODE"]="{SINGLE_MODE}";
	$typeList["LOCAL_MASTER"]="{LOCAL_MASTER}";
	$typeList["ROLE_DOMAIN_PDC"]="{ROLE_DOMAIN_PDC}";
	
	
	if(!$users->WINBINDD_INSTALLED){
		unset($typeList["ROLE_DOMAIN_PDC"]);
	}
	
	$pdc=Paragraphe_switch_img("{ROLE_DOMAIN_PDC}","{PDC_TEXT}","HasPDC",$hasApdc);	
	$standalone=Paragraphe_switch_img("{SINGLE_MODE}","{SINGLE_MODE_TEXT}","HasSingleMode",$HasSingleMode);
	$localmaster=Paragraphe_switch_img("{LOCAL_MASTER}","{LOCAL_MASTER_TEXT}","hasLocalMaster",$hasLocalMaster);
	
	$field=Field_array_Hash($typeList, "ROLE-$t",$VALUE,"ChangeRoleForm$t()",null,0,"font-size:16px");
	
	
	$html="
	<div id='$t-animate'></div>
	$current_text
	<div class=text-info style='font-size:12px'>
		{windows_network_neighborhood_text}
		<div><span id='neighborhood-$t' style='font-weight:bold'></span><br>
		{samba_security_mode_explain}<br>
		<span style='font-weight:bold' id='exp-security-explain-$t'></span>		
		
		</div>
	</div>
	
	<div id='security-mode-$t'></div>
	
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td>$field</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveNeightborg$t()","18px")."</td>
	</tr>
	</table>
	
	
	
	
	
	<script>
		function ChangeRoleForm$t(){
			var value=document.getElementById('ROLE-$t').value;
			LoadAjaxTiny('neighborhood-$t','$page?change-role='+value);
			LoadAjaxTiny('security-mode-$t','$page?change-role-form='+value+'&t=$t');
		
		}
	var x_SaveNeightborg$t = function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('$t-animate').innerHTML='';
		if(document.getElementById('main_config_samba')){RefreshTab('main_config_samba');}
		if(document.getElementById('admin_perso_tabs')){RefreshTab('admin_perso_tabs');}
	}

	function SaveNeightborg$t(){
		AnimateDiv('$t-animate');
		var XHR = new XHRConnection();
		XHR.appendData('neighborhood-save',document.getElementById('ROLE-$t').value);
		XHR.appendData('SambaSecurityLevel',document.getElementById('security-level-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveNeightborg$t);				
	
	}
		
		
		ChangeRoleForm$t();
	</script>
	
	";
	
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function change_role_explain(){
	$tpl=new templates();
	$typeList["SINGLE_MODE"]="{SINGLE_MODE_TEXT}";
	$typeList["LOCAL_MASTER"]="{LOCAL_MASTER_TEXT}";
	$typeList["ROLE_DOMAIN_PDC"]="{PDC_TEXT}";
	$value=$_GET["change-role"];
	echo $tpl->_ENGINE_parse_body($typeList[$value]);
}


function change_role_form(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$SambaSecurityLevel=$sock->GET_INFO("SambaSecurityLevel");
	if($SambaSecurityLevel==null){$SambaSecurityLevel="user";}
	$t=$_GET["t"];
	
	$hash[null]="{default}";
	$hash["user"]="{user_level_security}";
	$hash["share"]="{share_level_security}";
	$hash["domain"]="{domain_security}";
	$hash["ADS"]="{active_directory_security}";
	
	
	$lockAD=0;
	$smb=new samba();
	if($smb->EnableKerbAuth==1){$lockAD=1;}
	if($smb->EnableSambaActiveDirectory==1){$lockAD=1;}
	
	if($lockAD==1){$SambaSecurityLevel="ADS";}
	$field=Field_array_Hash($hash, "security-level-$t",$SambaSecurityLevel,"SecurityExplain$t()",null,0,"font-size:16px");
	
	
	$html="
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{samba_security_mode}:</td>
		<td>$field</td>
	</tr>
	</table>

	<script>
	function SecurityExplain$t(){
			var lock=$lockAD;
			if(lock==1){document.getElementById('security-level-$t').disabled=true;}
			var value=document.getElementById('security-level-$t').value;
			LoadAjaxTiny('exp-security-explain-$t','$page?exp-security-explain='+value+'&t=$t');
		
		}
		SecurityExplain$t()
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function security_user_explain(){
	$tpl=new templates();
	$value=$_GET["exp-security-explain"];
	$ex["user"]="{user_level_security_explain}";
	$ex["share"]="{share_level_security_explain}";
	$ex["domain"]="{domain_security_explain}";
	$ex["ADS"]="{active_directory_security_explain}";
	echo $tpl->_ENGINE_parse_body($ex[$value]);
}


function neighborhood_save(){

	$sock=new sockets();
	$samba=new samba();
	switch ($_POST["neighborhood-save"]) {
		case "SINGLE_MODE":
			$samba->main_array["global"]["domain logons"]="no";
			$samba->main_array["global"]["preferred master"]="no";
			$samba->main_array["global"]["domain master"]="no";
			$samba->main_array["global"]["local master"]="no";
			$samba->main_array["global"]["os level"]=20;
			$sock->SET_INFO("TypeOfSamba",1);
			$samba->SaveToLdap();
			break; 
			
		case "LOCAL_MASTER":
			$samba->main_array["global"]["domain logons"]="no";
			$samba->main_array["global"]["preferred master"]="yes";
			$samba->main_array["global"]["domain master"]="no";
			$samba->main_array["global"]["local master"]="yes";
			$samba->main_array["global"]["os level"]=33;
			$sock->SET_INFO("TypeOfSamba",2);
			$samba->SaveToLdap();
			break; 			
		
		case "ROLE_DOMAIN_PDC":
			$samba->main_array["global"]["domain logons"]="yes";
			$samba->main_array["global"]["preferred master"]="yes";
			$samba->main_array["global"]["domain master"]="yes";
			$samba->main_array["global"]["local master"]="yes";
			$samba->main_array["global"]["os level"]=40;
			$sock->SET_INFO("TypeOfSamba",3);
			$samba->SaveToLdap();	
			break; 
			
		default:
			$samba->main_array["global"]["domain logons"]="no";
			$samba->main_array["global"]["preferred master"]="no";
			$samba->main_array["global"]["domain master"]="no";
			$samba->main_array["global"]["local master"]="no";
			$samba->main_array["global"]["os level"]=20;
			$samba->SaveToLdap();		
			$sock->SET_INFO("TypeOfSamba",1);	
			break;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?samba-reconfigure=yes");
	$sock->SET_INFO("SambaSecurityLevel", $_POST["SambaSecurityLevel"]);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{need_reboot}");
	
	//You may need to restart your computer before changes take effect
	
	
}