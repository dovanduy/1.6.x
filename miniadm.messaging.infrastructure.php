<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["activedirectory"])){activedirectory();exit;}

function VerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AllowChangeDomains){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return false;}
}

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!VerifyRights()){senderrors("no rights");}

	
	$array["Active Directory"]="$page?activedirectory=yes";
	

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
}

function activedirectory(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	echo "<p class=text-error>Under Construction</p>";
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$active=new ActiveDirectory();
	$sock=new sockets();
	$severtype["WIN_2003"]="Windows 2003";
	$severtype["WIN_2008AES"]="Windows 2008 with AES";
	$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$schedule_parameters=$tpl->javascript_parse_text("{schedule_parameters}");
	$disconnect=$tpl->_ENGINE_parse_body("{disconnect}");
	$samba36=0;
	if(preg_match("#^3\.6\.#", $samba_version)){$samba36=1;}
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$configADSamba=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	$LockKerberosAuthentication=$sock->GET_INFO("LockKerberosAuthentication");
	$KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	
	
	$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	
	$arrayAuth[0]="{all_methods}";
	$arrayAuth[1]="{only_ntlm}";
	$arrayAuth[2]="{only_basic_authentication}";
	
	
	$NTPDATE_INSTALLED=0;
	if($users->NTPDATE){$NTPDATE_INSTALLED=1;}
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	
	if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	if(!is_numeric($KerbAuthDisableNsswitch)){$KerbAuthDisableNsswitch=0;}
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	
	
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
	if(!is_numeric("$LockKerberosAuthentication")){$LockKerberosAuthentication=1;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$samba_installed=1;
	if(!$users->SAMBA_INSTALLED){$samba_installed=0;}
	
	if(!isset($array["SAMBA_BACKEND"])){$array["SAMBA_BACKEND"]="tdb";}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($array["COMPUTER_BRANCH"]==null){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($samba36==1){$arrayBCK["autorid"]="autorid";}
	$arrayBCK["ad"]="ad";
	$arrayBCK["rid"]="rid";
	$arrayBCK["tdb"]="tdb";
	if($LockKerberosAuthentication==1){$EnableKerberosAuthentication=0;}
	$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
	
	
	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}
	$arrayLDAP=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$t=time();
	if($arrayLDAP["LDAP_DN"]==null){$arrayLDAP["LDAP_DN"]=$active->ldap_dn_user;}
	if($arrayLDAP["LDAP_SUFFIX"]==null){$arrayLDAP["LDAP_SUFFIX"]=$active->suffix;}
	if($arrayLDAP["LDAP_SERVER"]==null){$arrayLDAP["LDAP_SERVER"]=$active->ldap_host;}
	if($arrayLDAP["LDAP_PORT"]==null){$arrayLDAP["LDAP_PORT"]=$active->ldap_port;}
	if($arrayLDAP["LDAP_PASSWORD"]==null){$arrayLDAP["LDAP_PASSWORD"]=$active->ldap_password;}
	if(!is_numeric($array["LDAP_RECURSIVE"])){$arrayLDAP["LDAP_RECURSIVE"]=0;}
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	
	$boot=new boostrap_form();	

	
	$boot->set_checkbox("EnableKerbAuth", "{EnableWindowsAuthentication}",$EnableKerbAuth, array("DISABLEALL"=>true));
	$boot->set_checkbox("KerbAuthDisableNsswitch", "{KerbAuthDisableNsswitch}",$KerbAuthDisableNsswitch);
	$boot->set_checkbox("KerbAuthTrusted", "{KerbAuthTrusted}",$KerbAuthTrusted);
	$boot->set_checkbox("KerbAuthDisableGroupListing", "{KerbAuthDisableGroupListing}",$KerbAuthDisableGroupListing);
	$boot->set_checkbox("KerbAuthDisableNormalizeName", "{KerbAuthDisableNormalizeName}",$KerbAuthDisableNormalizeName);
	$boot->set_checkbox("KerbAuthMapUntrustedDomain", "{map_untrusted_to_domain}",$KerbAuthMapUntrustedDomain);
	$boot->set_checkbox("NtpdateAD", "{synchronize_time_with_ad}",$NtpdateAD);
	//$boot->set_checkbox("EnableKerberosAuthentication", "{authenticate_from_kerberos}",$EnableKerberosAuthentication);
	
	$boot->set_field("WINDOWS_DNS_SUFFIX", "{WINDOWS_DNS_SUFFIX}", $array["WINDOWS_DNS_SUFFIX"]);
	$boot->set_field("WINDOWS_SERVER_NETBIOSNAME", "{WINDOWS_SERVER_NETBIOSNAME}", $array["WINDOWS_SERVER_NETBIOSNAME"]);
	$boot->set_field("ADNETBIOSDOMAIN", "{ADNETBIOSDOMAIN}", $array["ADNETBIOSDOMAIN"],array("TOOLTIP"=>"{howto_ADNETBIOSDOMAIN}"));
	$boot->set_field("ADNETIPADDR", "{ADNETIPADDR}", $array["ADNETIPADDR"],array("TOOLTIP"=>"{howto_ADNETIPADDR}"));
	$boot->set_field("LDAP_PORT", "{ldap_port}", $array["LDAP_PORT"]);
	$boot->set_list("WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}", $severtype,$array["WINDOWS_SERVER_TYPE"]);
	$boot->set_field("COMPUTER_BRANCH", "{COMPUTERS_BRANCH}", $array["COMPUTER_BRANCH"]);
	$boot->set_list("SAMBA_BACKEND", "{database_backend}", $arrayBCK,$array["SAMBA_BACKEND"]);
	$boot->set_field("WINDOWS_SERVER_ADMIN", "{administrator}", $array["WINDOWS_SERVER_ADMIN"]);
	$boot->set_fieldpassword("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"],array("ENCODE"=>true,"SPECIALSCHARS"=>true));
		
	echo $boot->Compile();
}

function activedirectory_save(){
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	unset($_SESSION["EnableKerbAuth"]);
	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");
	$MyhostnameTR=explode(".", $Myhostname);
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));

	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	$resolved=gethostbyname($adhost);
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $resolved)){
		$tpl=new templates();
		$sock->SET_INFO("EnableKerberosAuthentication", 0);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} $adhost",1);
		return;
	}
	
	
	
	if(isset($_POST["KerbAuthDisableNormalizeName"])){$sock->SET_INFO("KerbAuthDisableNormalizeName", $_POST["KerbAuthDisableNormalizeName"]);}
	if(isset($_POST["EnableKerberosAuthentication"])){$sock->SET_INFO("EnableKerberosAuthentication", $_POST["EnableKerberosAuthentication"]);}
	if(isset($_POST["KerbAuthDisableNsswitch"])){$sock->SET_INFO("KerbAuthDisableNsswitch", $_POST["KerbAuthDisableNsswitch"]);}
	if(isset($_POST["KerbAuthDisableGroupListing"])){$sock->SET_INFO("KerbAuthDisableGroupListing", $_POST["KerbAuthDisableGroupListing"]);}
	if(isset($_POST["KerbAuthTrusted"])){$sock->SET_INFO("KerbAuthTrusted", $_POST["KerbAuthTrusted"]);}
	if(isset($_POST["KerbAuthMapUntrustedDomain"])){$sock->SET_INFO("KerbAuthMapUntrustedDomain", $_POST["KerbAuthMapUntrustedDomain"]);}
	if(isset($_POST["NtpdateAD"])){$sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);}
	if(isset($_POST["KerbAuthMethod"])){$sock->SET_INFO("KerbAuthMethod", $_POST["KerbAuthMethod"]);}
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	while (list ($num, $ligne) = each ($_POST) ){$ArrayKerbAuthInfos[$num]=$ligne;}
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	if(strpos($_POST["ADNETBIOSDOMAIN"], ".")>0){
		echo "The netbios domain \"{$_POST["ADNETBIOSDOMAIN"]}\" is invalid.\n";
		$sock->SET_INFO("EnableKerbAuth", 0);
		return;
	}
	
	if($_POST["ADNETIPADDR"]<>null){
		$ipaddrZ=explode(".",$_POST["ADNETIPADDR"]);
		while (list ($num, $a) = each ($ipaddrZ) ){$ipaddrZ[$num]=intval($a);}
		$_POST["ADNETIPADDR"]=@implode(".", $ipaddrZ);
		$adhost=$_POST["ADNETIPADDR"];
	}
	
	$username=$_POST["WINDOWS_SERVER_ADMIN"]."@".$_POST["WINDOWS_DNS_SUFFIX"];
	
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){$array[$num]=$ligne;}
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
	$ldap_connection=@ldap_connect($adhost,$_POST["LDAP_PORT"]);
	if(!$ldap_connection){
		echo "Connection Failed to connect to DC ldap://$adhost:{$_POST["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
		$error=$error."\n$extended_error";
	}
	@ldap_close();
	return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $username,$_POST["WINDOWS_SERVER_PASS"]);
	
	if(!$bind){
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error."\n$extended_error";}
		echo "Failed to login to DC $adhost - $username \n`$error`";
		return false;
	}
	
	
	$sock->getFrameWork("postfix.php?active-directory=yes");
	
	
	
}
