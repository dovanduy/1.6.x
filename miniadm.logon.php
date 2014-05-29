<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_SOCK"]=true;$GLOBALS["DEBUG"]=true;$_GET["debug-page"]=true;$GLOBALS["DEBUG_INCLUDES"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($GLOBALS["VERBOSE"]){echo __LINE__."::session_start()<br>\n";}

session_start();
if($GLOBALS["VERBOSE"]){echo __LINE__."::Includes...()<br>\n";}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
if($GLOBALS["VERBOSE"]){echo __LINE__."::Includes...()<br>\n";}
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");


if(!isset($_SESSION["uid"])){if(isset($_SERVER["PHP_AUTH_USER"])){
	if($GLOBALS["VERBOSE"]){echo __LINE__."::BuildSession...()<br>\n";}
	BuildSession($_SERVER["PHP_AUTH_USER"]);}}

if($GLOBALS["VERBOSE"]){echo __LINE__."::Parse params...()<br>\n";}
if(isset($_SESSION["uid"])){header('location:miniadm.index.php');die();}
if(isset($_POST["IE_POST"])){checklogon_ie();exit;}
if(isset($_POST["username"])){checklogon();exit;}
if(isset($_GET["credentials"])){checklogonCreds();exit;}
if(isset($_GET["js"])){js();exit;}

MainPage();
function MainPage($error=null){

$t=time();
$page=CurrentPageName();
$content=@file_get_contents("ressources/templates/endusers/logon.html");
$browser=browser_detection();
if($browser=="ie"){$content=@file_get_contents("ressources/templates/endusers/logon.ie.html");}
$button=button("{logon}", "Loadjs('$page?js=yes')","18px");
$content=str_replace("{TEMPLATE_TITLE_HEAD}", $_SERVER["SERVER_NAME"], $content);
$content=str_replace("{LOGON_BUTTON}",$button, $content);
$content=str_replace("{SCRIPTS}","Loadjs('$page?js=yes&t=$t');", $content);
$content=str_replace("<!-- ERROR -->","<p class=text-error>$error</p>", $content);
$sublink=null;
if($_SERVER["SERVER_PORT"]==9000){
	if($_SERVER["HTTPS"]=="on"){
		$sublink="<a href='https://{$_SERVER["SERVER_NAME"]}:9000/logon.php'>&laquo;{back_to_artica}&raquo;</a>";
		
	}
}

$content=str_replace("{SUBLINKS}",$sublink, $content);


$tpl=new templates();
$content=$tpl->_ENGINE_parse_body($content);
echo $content;

}

function js(){
	header("content-type: application/x-javascript");
	$location="miniadm.index.php";
	$page=CurrentPageName();
	if($GLOBALS["VERBOSE"]){echo __LINE__."::clladp() ??...()<br>\n";}
	$ldap=new clladp();
	if(isset($_GET["location"])){$location=$_GET["location"];}
	$password="var password=MD5(document.getElementById('artica_password').value);";
	if($GLOBALS["VERBOSE"]){echo __LINE__."::IsKerbAuth ??...()<br>\n";}
	if($ldap->IsKerbAuth()){
		$password="var password=encodeURIComponent(document.getElementById('artica_password').value);";
	}
	
	$t=time();

	$html="

var x_SendLogonButton$t = function (obj) {
	var response=obj.responseText;
	if(response.length>3){alert(response);return;}
	document.location.href='$location';
}

function SendLogonButton$t(){
	var username=document.getElementById('artica_username').value;
	$password
	var XHR = new XHRConnection();
	XHR.appendData('username',username);
	XHR.appendData('password',password);
	XHR.sendAndLoad('$page', 'POST',x_SendLogonButton$t);

}


SendLogonButton$t();
";

echo $html;
	
	
}

function checklogonCreds($Aspost=false){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include("ressources/settings.inc");
	

	if(!isset($_GET["credentials"])){
		header("location:".basename(__FILE__));
		return;
	}
	$array=unserialize(base64_decode($_GET["credentials"]));
	
	$username=$array["USERNAME"];
	$password=$array["PASSWORD"];
	
	if(trim($_POST["artica_username"])==trim($_GLOBAL["ldap_admin"])){
		if($password==md5(trim($_GLOBAL["ldap_password"]))){
			$_SESSION["uid"]='-100';
			$_SESSION["groupid"]='-100';
			$_SESSION["passwd"]=$_GLOBAL["ldap_password"];
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"';
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			$_SESSION["AsWebStatisticsAdministrator"]=true;
			header("location:miniadm.index.php");
			if($Aspost){return;}
		}
		
	}
	
	

	
	$ldap=new clladp();
	
	if($ldap->IsKerbAuth()){
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($username,$password)){
			$users=new usersMenus();
			$privs=new privileges($_POST["username-logon"]);
			$privileges_array=$privs->privs;
			$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
			setcookie("mem-logon-user", $_POST["username-logon"], time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["uid"]=$_POST["username-logon"];
			$_SESSION["passwd"]=$_POST["username-logon"];
			$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;			
			BuildSession($username);
			header("location:miniadm.index.php");
			if($Aspost){return;}
		}
	}	
	
	
	
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	if(trim($u->uidNumber)==null){header("location:".basename(__FILE__));return;}	
	if(!$Aspost){if( trim($password)<>md5(trim($userPassword))){header("location:".basename(__FILE__));return;}}
	if($Aspost){
		if(trim($password)<>trim($userPassword)){
			MainPage("{connection_failed}");
			return;
		}	
	}
	
	$ldap=new clladp();
	$sock=new sockets();
	if(!isset($GLOBALS["FixedLanguage"])){$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");}
	$users=new usersMenus();
	$_SESSION["CORP"]=$users->CORP_LICENSE;
	$privs=new privileges($u->uid);
	$privs->SearchPrivileges();
	$privileges_array=$privs->privs;
	$_SESSION["privileges_array"]=$privs->privs;
	if(!isset($privileges_array["VIRTUALS_SERVERS"])){$privileges_array["VIRTUALS_SERVERS"]=array();}
	$_SESSION["privs"]=$privileges_array;
	if(isset($privileges_array["ForceLanguageUsers"])){$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];}
	$_SESSION["uid"]=$username;
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
	$_SESSION["VIRTUALS_SERVERS"]=$privileges_array["VIRTUALS_SERVERS"];
	$_SESSION["POSTFIX_SERVERS"]=$privileges_array["POSTFIX_SERVERS"];
	
	$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
	$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
	$_SESSION["MailboxActive"]=$u->MailboxActive;
	$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
	$_SESSION["ou"]=$u->ou;
	$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
	$_SESSION["AsWebStatisticsAdministrator"]=$users->AsWebStatisticsAdministrator;
	if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]=null;}
	
			if(trim($_SESSION["OU_LANG"])<>null){
				$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
			}else{
				include_once(dirname(__FILE__)."/ressources/class.langages.inc");
				$lang=new articaLang();
				$_SESSION["detected_lang"]=$lang->get_languages();
			}
			if(trim($GLOBALS["FixedLanguage"])<>null){$_SESSION["detected_lang"]=$GLOBALS["FixedLanguage"];}	
			
			include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
			$cr=new TranslateRights(null, null);
			$r=$cr->GetPrivsArray();
			while (list ($key, $val) = each ($r) ){if($users->$key){$_SESSION[$key]=$users->$key;}}
				
			if(is_array($_SESSION["privs"])){
				$r=$_SESSION["privs"];
				while (list ($key, $val) = each ($r) ){
					$_SESSION[$key]=$val;
				}
			}			
			
			
	header("location:miniadm.index.php");
}

function checklogon_ie(){
	$_POST["username"]=$_POST["artica_username"];
	$_POST["password"]=$_POST["artica_password"];
	checklogon(true);
	
}

function checklogon($Aspost=false){
	
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	include("ressources/settings.inc");
	
	$username=$_POST["username"];
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$password=trim($_POST["password"]);
	$users=new usersMenus();
	if($users->WEBSTATS_APPLIANCE){$users->SQUID_INSTALLED=true;}
	//echo $username."\n$password\n";
	
	if($password==null){
		if($Aspost){MainPage("Bad password");return;}
		echo "Bad password";
		return;
	}
	
	if(trim($username)==trim($_GLOBAL["ldap_admin"])){
		$passwordMD=md5(trim($_GLOBAL["ldap_password"]));
		if($password==$passwordMD){
			$_SESSION["uid"]='-100';
			$_SESSION["groupid"]='-100';
			$_SESSION["passwd"]=$_GLOBAL["ldap_password"];
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"';
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			$_SESSION["AsWebStatisticsAdministrator"]=true;
			if($Aspost){header("location:miniadm.index.php");return;}
			return;
		}
	
	}	
	
	
	
	if($users->SQUID_INSTALLED){
		$q=new mysql_squid_builder();
		$passwordMD=md5($password);
		$sql="SELECT webfilters_sqitems.gpid AS maingpid
			FROM webfilters_sqacllinks, webfilters_sqgroups, webfilters_sqitems, webfilters_sqacls
			WHERE webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqgroups.ID = webfilters_sqitems.gpid
			AND webfilters_sqacls.enabled =1
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqitems.enabled =1
			AND webfilters_sqgroups.GroupType = 'dynamic_acls'
			AND webfilters_sqitems.pattern = '$username:$passwordMD'";
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->mysql_error){echo $q->mysql_error;}
		$CountDerules=mysql_num_rows($results);
		writelogs("$username::webfilters_sqitems:: $CountDerules rules",__FUNCTION__,__FILE__,__LINE__);
		if($CountDerules>0){
			writelogs("$username::webfilters_sqitems:: Building rules....",__FUNCTION__,__FILE__,__LINE__);
			while ($ligne = mysql_fetch_assoc($results)) {$_SESSION["SQUID_DYNAMIC_ACLS_VIRTUALS"][$ligne["maingpid"]]=true;}
				$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
				$_SESSION["VirtAclUser"]=true;
				$_SESSION["ou"]="Proxy Service";
				$_SESSION["CORP"]=$users->CORP_LICENSE;
				setcookie("mem-logon-user", $_POST["username-logon"], time()+172800);
				$_SESSION["privileges_array"]=$privs->privs;
				$_SESSION["uid"]=$username;
				$_SESSION["privileges"]["ArticaGroupPrivileges"]=array();
				BuildSession($username);
				if($Aspost){header("location:miniadm.index.php");return;}
				return;
		}
	}
	writelogs("$username:: Continue, processing....",__FUNCTION__,__FILE__,__LINE__);
	$ldap=new clladp();
	$IsKerbAuth=$ldap->IsKerbAuth();
	writelogs("$username:: Is AD -> $IsKerbAuth",__FUNCTION__,__FILE__,__LINE__);
	if($ldap->IsKerbAuth()){
		$external_ad_search=new external_ad_search();
		if($external_ad_search->CheckUserAuth($username,$password)){
			$users=new usersMenus();
			$privs=new privileges($_POST["username-logon"]);
			$privileges_array=$privs->privs;
			$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
			$_SESSION["VirtAclUser"]=false;
			setcookie("mem-logon-user", $_POST["username-logon"], time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["uid"]=$_POST["username-logon"];
			$_SESSION["passwd"]=$_POST["username-logon"];
			$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
			BuildSession($username);
			if($Aspost){header("location:miniadm.index.php");return;}
			return;
		}
		writelogs("$username:: Checks Active Directory failed, continue processing...",__FUNCTION__,__FILE__,__LINE__);
	}

	
	writelogs("$username:: Continue, processing....",__FUNCTION__,__FILE__,__LINE__);
	
	$q=new mysql();
	$sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$username' AND `attribute`='Cleartext-Password' LIMIT 0,1";
	writelogs("$username:: Is a RADIUS users \"$sql\"",__FUNCTION__,__FILE__,__LINE__);
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
	if(!$q->ok){writelogs("$username:: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	writelogs("$username:: $password <> ".md5($ligne["value"]),__FUNCTION__,__FILE__,__LINE__);
	if($ligne["id"]>0){
			$checkRadiusPass=false;
			if(md5($ligne["value"])==$password){
				writelogs("$username:: RADIUS Password true for no MD5",__FUNCTION__,__FILE__,__LINE__);
				$checkRadiusPass=true;
			} 
			if(md5($ligne["value"])==$passwordMD){
				writelogs("$username:: RADIUS Password true for yes MD5",__FUNCTION__,__FILE__,__LINE__);
				$checkRadiusPass=true;
			}
		
		
		if($checkRadiusPass){
			writelogs("$username:: Authenticated as a RADIUS users id={$ligne["id"]}",__FUNCTION__,__FILE__,__LINE__);
			$privs=new privileges($_POST["username-logon"],null,$ligne["id"]);
			$privileges_array=$privs->privs;
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			$_SESSION["InterfaceType"]="{ARTICA_MINIADM}";
			setcookie("mem-logon-user", $username, time()+172800);
			$_SESSION["privileges_array"]=$privs->privs;
			while (list ($key, $val) = each ($_SESSION["privileges_array"]) ){if(!isset($_SESSION[$key])){$_SESSION[$key]=$val;}}
			reset($_SESSION["privileges_array"]);
			$_SESSION["uid"]=$username;
			$_SESSION["RADIUS_ID"]=$ligne["id"];
			BuildSession($username);
			if($Aspost){header("location:miniadm.index.php");return;}
			return;
			
		}
	}
	writelogs("$username::Finally Is LOCAL LDAP ? -> $IsKerbAuth",__FUNCTION__,__FILE__,__LINE__);
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	
	
	if(trim($u->uidNumber)==null){
		writelogs('Unable to get user infos abort',__FUNCTION__,__FILE__);
		if($Aspost){MainPage("Unknown user (".__LINE__.")");return;}
		echo "Unknown user (".__LINE__.")";
		die();
	}
	
	writelogs("$username:: Password match ? Aspost = $Aspost",__FUNCTION__,__FILE__,__LINE__);
	if($Aspost){
		if( trim($password)<>trim($userPassword)){
			writelogs("$username:: Password match NO Aspost = $Aspost",__FUNCTION__,__FILE__,__LINE__);
			MainPage("Bad password (".__LINE__.")");
			return;
		}
	}
	
	
	if(!$Aspost){
		if( trim($password)<>md5(trim($userPassword))){
			writelogs("$username:: Password match NO Aspost = $Aspost",__FUNCTION__,__FILE__,__LINE__);
			writelogs("[{$_POST["username"]}]: The password typed  is not the same in ldap database...",__FUNCTION__,__FILE__);
			artica_mysql_events("Failed to logon on the management console as user `$username` from {$_SERVER["REMOTE_HOST"]} (bad password)",@implode("\n",$notice),"security","security");
			if($Aspost){MainPage("Bad password (".__LINE__.")");return;}
			echo "Error: (".__LINE__.") bad password";
			return null;		
		}
	}
	writelogs("$username:: Password match YES Aspost = $Aspost",__FUNCTION__,__FILE__,__LINE__);
			$ldap=new clladp();
			$users=new usersMenus();
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			
			$privs=new privileges($u->uid);
			$privs->SearchPrivileges();
			$privileges_array=$privs->privs;
			$_SESSION["VirtAclUser"]=false;
			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["privs"]=$privileges_array;
			if(isset($privileges_array["ForceLanguageUsers"])){$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];}
			$_SESSION["uid"]=$username;
			$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
			$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
			$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
			$_SESSION["MailboxActive"]=$u->MailboxActive;
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			$_SESSION["ou"]=$u->ou;
			$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
			include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
			$cr=new TranslateRights(null, null);
			$r=$cr->GetPrivsArray();
			while (list ($key, $val) = each ($r) ){
				
				if($users->$key){$_SESSION[$key]=$users->$key;}}
			
			if(is_array($_SESSION["privs"])){
				$r=$_SESSION["privs"];
				while (list ($key, $val) = each ($r) ){
					$t[$key]=$val;
					$_SESSION[$key]=$val;
				}
			}
			
			
			
			if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]=null;}
			if(!isset($_SESSION["ASDCHPAdmin"])){$_SESSION["ASDCHPAdmin"]=false;}
			
		
			if(trim($_SESSION["OU_LANG"])<>null){
				$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
			}else{
				include_once(dirname(__FILE__)."/ressources/class.langages.inc");
				$lang=new articaLang();
				$_SESSION["detected_lang"]=$lang->get_languages();
			}
			
			if(isset($GLOBALS["FixedLanguage"])){
				$sock=new sockets();
				$GLOBALS["FixedLanguage"]=$sock->GET_INFO("FixedLanguage");
			}
			
			if(trim($GLOBALS["FixedLanguage"])<>null){$_SESSION["detected_lang"]=$GLOBALS["FixedLanguage"];}
			
			if($Aspost){
				header("location:miniadm.index.php");
				return;
			}
}

