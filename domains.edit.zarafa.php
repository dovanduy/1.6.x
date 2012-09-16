
<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["zarafaEnabled"])){zarafaEnabled();exit;}
	if(isset($_POST["zarafaScanMbxLang"])){zarafaScanMbxLang();exit;}
	js();
	
	
	
function js(){
$page=CurrentPageName();
$users=new usersMenus();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{APP_ZARAFA}');
$ou_decrypted=base64_decode($_GET["ou"]);
$html="

function ZARAFA_OU_LOAD(){
	YahooWin3('610','$page?popup=yes&ou=$ou_decrypted','$title');
	
	}
	

	
ZARAFA_OU_LOAD();
";

echo $html;	
	
}


function popup(){
	$page=CurrentPageName();
	$ldap=new clladp();
	$sock=new sockets();
	$info=$ldap->OUDatas($_GET["ou"]);
	$zarafaEnabled=1;
	if(!$info["objectClass"]["zarafa-company"]){$zarafaEnabled=0;}
	$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
	$languages=unserialize(base64_decode($sock->getFrameWork("zarafa.php?locales=yes")));
	$langbox[null]="{select}";
	while (list ($index, $data) = each ($languages) ){$langbox[$data]=$data;}	
	$t=time();
	$oumd5=md5(strtolower(trim($_GET["ou"])));
	$OuDefaultLang=$sock->GET_INFO("zarafaMBXLang$oumd5");
	$OuZarafaDeleteADM=$sock->GET_INFO("OuZarafaDeleteADM$oumd5");
	
	
	
	
	$filter="(&(objectClass=zarafa-user)(zarafaAdmin=1))";
	$attrs=array("displayName","uid","mail");
	$dn="ou={$_GET["ou"]},dc=organizations,$ldap->suffix";		
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
	$number=$hash["count"];
	$arruid[null]="{select}";
	for($i=0;$i<$number;$i++){
		$userZ=$hash[$i];
		$uid=$userZ["uid"][0];
		$arruid[$uid]=$uid;
	}	
	
	$admin_delete=Field_array_Hash($arruid,"OuZarafaDeleteADM$t",$OuZarafaDeleteADM,"style:font-size:16px;padding:3px");
	
	$mailbox_language=Field_array_Hash($langbox,"zarafaMbxLang$t",$OuDefaultLang,"style:font-size:16px;padding:3px");
	
	if($OuDefaultLang<>null){
		
		$rescan="<div style='width:100%;margin:10px'><center>".button("{scan_mailboxes_language}", "zarafaDScanMBXLang$t()","16")."</center></div>";
	}
	
	
		if($ZarafaUserSafeMode==1){
			$warn="
			<hr>
			<table style='width:99 %' class=form>
			<tr>
			<td valign='top'><img src='img/error-64.png'></td>
			<td valign='top'><strong style='font-size:14px'>{ZARAFA_SAFEMODE_EXPLAIN}</td>
			</tr>
			</table>
			
			";
		}

	$enable=Paragraphe_switch_img("{ENABLE_ZARAFA_COMPANY}","{ENABLE_ZARAFA_COMPANY_TEXT}","zarafaEnabled",$zarafaEnabled,null,400);
	
	$html="
	<table style='width:95%' class=form>
	<tr>
		<td colspan=2>$enable</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{default_language}:</td>
		<td>$mailbox_language</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{delete_store_receiver}:</td>
		<td>$admin_delete</td>
	</tr>	
	<tr>
	<td colspan=2 align='right'>
	<hr>". button("{apply}","ENABLE_ZARAFA_COMPANY()",16)."$warn</td>
	</tr>
	</table>
	$rescan
	<script>
var X_ENABLE_ZARAFA_COMPANY= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	if(document.getElementById('organization-find')){SearchOrgs();YahooWin3Hide();return;}
	ZARAFA_OU_LOAD();
	
	}	
	
function ENABLE_ZARAFA_COMPANY(){
	var XHR = new XHRConnection();
	XHR.appendData('zarafaEnabled',document.getElementById('zarafaEnabled').value);
	XHR.appendData('zarafaMbxLang',document.getElementById('zarafaMbxLang$t').value);
	XHR.appendData('OuZarafaDeleteADM',document.getElementById('OuZarafaDeleteADM$t').value);
	
	
	
	XHR.appendData('ou','{$_GET["ou"]}');
	document.getElementById('img_zarafaEnabled').src='img/wait_verybig.gif';
	XHR.sendAndLoad('$page', 'GET',X_ENABLE_ZARAFA_COMPANY);	
}


function zarafaDScanMBXLang$t(){
	var XHR = new XHRConnection();
	XHR.appendData('zarafaScanMbxLang','yes');
	XHR.appendData('ou','{$_GET["ou"]}');
	document.getElementById('img_zarafaEnabled').src='img/wait_verybig.gif';
	XHR.sendAndLoad('$page', 'POST',X_ENABLE_ZARAFA_COMPANY);	
}

</script>	
	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}

function zarafaEnabled(){
	$ldap=new clladp();
	$sock=new sockets();
	$dn="ou={$_GET["ou"]},dc=organizations,$ldap->suffix";
	$upd["objectClass"]="zarafa-company";
	$upd["cn"]=$_GET["ou"];
	if($_GET["zarafaEnabled"]==1){
		if(!$ldap->Ldap_add_mod("$dn",$upd)){echo $ldap->ldap_last_error;return;}
		$oumd5=md5(strtolower(trim($_GET["ou"])));
		$sock->SET_INFO("zarafaMBXLang$oumd5",$_GET["zarafaMbxLang"]);
		$sock->SET_INFO("OuZarafaDeleteADM$oumd5",$_GET["OuZarafaDeleteADM"]);
	}else{
		if(!$ldap->Ldap_del_mod("$dn",$upd)){echo $ldap->ldap_last_error;}
		return;
	}
	
	$sock=new sockets();
	$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
	if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}	
	if($EnableZarafaMulti==0){$sock->getFrameWork("cmd.php?zarafa-admin=yes");return;}
	$q=new mysql();
	$sql="SELECT servername,ID FROM zarafamulti WHERE ou='{$_GET["ou"]}' AND enabled=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)>0){
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$sock->getFrameWork("cmd.php?zarafa-admin=yes&instance-id={$ligne["ID"]}");
		}
	}
}

function zarafaScanMbxLang(){
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}",1);
	$sock->getFrameWork("zarafa.php?mailboxes-ou-lang=yes&ou={$_POST["ou"]}");
	
}

	
?>