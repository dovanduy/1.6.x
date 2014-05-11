<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

$users=new usersMenus();
if(isset($_GET["active-directory"])){active_directory_section();exit;}
if(isset($_GET["active-directory-parameters"])){active_directory_parameters();exit;}
if(isset($_POST["EnableKerbAuth"])){active_directory_save();exit;}
if(isset($_GET["LDAP-AUTH"])){ldap_auth_parameters();exit;}
if(isset($_POST["ldap_auth"])){ldap_auth_save();exit;}
tabs();


function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();

	$mini=new boostrap_form();
	$array["Active Directory"]="$page?active-directory=yes";
	$array["LDAP"]="$page?LDAP-AUTH=yes";
	echo $mini->build_tab($array);
}

function active_directory_section(){
	$page=CurrentPageName();
	$sock=new sockets();	
	$mini=new boostrap_form();
	
	
	
	
	$array["{parameters}"]="$page?active-directory-parameters=yes";
	$array["{analyze}"]="squid.adker.php?test-popup=yes";
	$array["{test_auth}"]="squid.adker.php?test-auth=yes";
	
	
	echo $mini->build_tab($array);	
	
}

function active_directory_parameters(){
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
	$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
	
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
	if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
	
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
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
	
	
	$boot=new boostrap_form();
	
	$boot->set_spacertitle("Active Directory");
	$boot->set_checkbox("EnableKerbAuth", "{EnableWindowsAuthentication}",$EnableKerbAuth, array("DISABLEALL"=>true));
	$boot->set_list("KerbAuthMethod", "{authentication_method}", $arrayAuth,$KerbAuthMethod);
	$boot->set_checkbox("KerbAuthDisableNsswitch", "{KerbAuthDisableNsswitch}",$KerbAuthDisableNsswitch);
	$boot->set_checkbox("KerbAuthTrusted", "{KerbAuthTrusted}",$KerbAuthTrusted);
	$boot->set_checkbox("KerbAuthDisableGroupListing", "{KerbAuthDisableGroupListing}",$KerbAuthDisableGroupListing);
	$boot->set_checkbox("KerbAuthDisableNormalizeName", "{KerbAuthDisableNormalizeName}",$KerbAuthDisableNormalizeName);
	$boot->set_checkbox("KerbAuthMapUntrustedDomain", "{map_untrusted_to_domain}",$KerbAuthMapUntrustedDomain);
	$boot->set_checkbox("NtpdateAD", "{synchronize_time_with_ad}",$NtpdateAD);
	$boot->set_checkbox("SquidNTLMKeepAlive", "{keep_alive}",$SquidNTLMKeepAlive,array("TOOLTIP"=>"{SquidNTLMKeepAlive_explain}"));
	
	
	//$boot->set_checkbox("EnableKerberosAuthentication", "{authenticate_from_kerberos}",$EnableKerberosAuthentication);
	
	$boot->set_field("WINDOWS_DNS_SUFFIX", "{WINDOWS_DNS_SUFFIX}", $array["WINDOWS_DNS_SUFFIX"]);
	$boot->set_field("WINDOWS_SERVER_NETBIOSNAME", "{WINDOWS_SERVER_NETBIOSNAME}", $array["WINDOWS_SERVER_NETBIOSNAME"]);
	$boot->set_field("ADNETBIOSDOMAIN", "{ADNETBIOSDOMAIN}", $array["ADNETBIOSDOMAIN"],array("TOOLTIP"=>"{howto_ADNETBIOSDOMAIN}"));
	$boot->set_field("ADNETIPADDR", "{ADNETIPADDR}", $array["ADNETIPADDR"],array("TOOLTIP"=>"{howto_ADNETIPADDR}"));
	$boot->set_list("WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}", $severtype,$array["WINDOWS_SERVER_TYPE"]);
	$boot->set_field("COMPUTER_BRANCH", "{COMPUTERS_BRANCH}", $array["COMPUTER_BRANCH"]);
	$boot->set_list("SAMBA_BACKEND", "{database_backend}", $arrayBCK,$array["SAMBA_BACKEND"]);
	$boot->set_field("WINDOWS_SERVER_ADMIN", "{administrator}", $array["WINDOWS_SERVER_ADMIN"]);
	$boot->set_fieldpassword("WINDOWS_SERVER_PASS", "{password}", $array["WINDOWS_SERVER_PASS"],array("ENCODE"=>true,"SPECIALSCHARS"=>true));
	
	
	$boot->set_spacertitle("LDAP");
	$boot->set_spacerexplain("{ldap_ntlm_parameters_explain}");
	
	$boot->set_checkbox("UseDynamicGroupsAcls", "{use_dynamic_groups_acls}",$UseDynamicGroupsAcls);
	$boot->set_field("DynamicGroupsAclsTTL", "{TTL_CACHE}", $DynamicGroupsAclsTTL);
	$boot->set_field("LDAP_NONTLM_DOMAIN", "{non_ntlm_domain}", $arrayLDAP["LDAP_NONTLM_DOMAIN"]);
	$boot->set_field("LDAP_SERVER", "{hostname}", $arrayLDAP["LDAP_SERVER"]);
	$boot->set_field("LDAP_PORT", "{ldap_port}", $arrayLDAP["LDAP_PORT"]);
	$boot->set_field("LDAP_SUFFIX", "{suffix}", $arrayLDAP["LDAP_SUFFIX"]);
	$boot->set_field("LDAP_DN", "{bind_dn}", $arrayLDAP["LDAP_DN"]);
	$boot->set_fieldpassword("LDAP_PASSWORD", "{password}", $arrayLDAP["LDAP_PASSWORD"]);
	$boot->set_field("LDAP_RECURSIVE", "{recursive}", $arrayLDAP["LDAP_RECURSIVE"]);

	$cntlm_error=null;
	
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	
	$boot->set_spacertitle("{APP_CNTLM}");
	if(!$users->CNTLM_INSTALLED){$cntlm_error="<p class=text-error>{CNTLM_NOT_INSTALLED}</p>";}
	$boot->set_spacerexplain("{APP_CNTLM_EXPLAIN}$cntlm_error");
	$boot->set_checkbox("EnableCNTLM", "{activate_CNTLM_service}",$EnableCNTLM);
	$boot->set_field("CnTLMPORT", "{listen_port}", $CNTLMPort,array("TOOLTIP"=>"{CnTLMPORT_explain2}"));
	
	$boot->set_button("{apply}");
	
	
	if((!$users->AsSquidAdministrator) OR (!$users->AsPostfixAdministrator)){
		$boot->set_form_locked();
	}else{
		
		$boot->set_Newbutton("{disconnect}", "Loadjs('squid.adker.php?diconnect-js=yes')");
		$boot->set_Newbutton("{restart_connection}", "Loadjs('squid.adker.php?join-js=yes')");
		
	}

	echo $boot->Compile();
	
	
}
function active_directory_save(){
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	unset($_SESSION["EnableKerbAuth"]);

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	
	
	//CNTLM
	$sock->SET_INFO("EnableCNTLM", $_POST["EnableCNTLM"]);
	$sock->SET_INFO("CnTLMPORT", $_POST["CnTLMPORT"]);
	$sock->getFrameWork("squid.php?cntlm-restart=yes");


	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");
	$MyhostnameTR=explode(".", $Myhostname);
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));
	if($MyDomain<>$_POST["WINDOWS_DNS_SUFFIX"]){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SET_INFO("EnableKerberosAuthentication", 0);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {WINDOWS_DNS_SUFFIX} {$_POST["WINDOWS_DNS_SUFFIX"]}\n{is_not_a_part_of} $Myhostname ($MyDomain)",1);
		return;
	}

	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	$resolved=gethostbyname($adhost);
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $resolved)){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SET_INFO("EnableKerberosAuthentication", 0);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} $adhost",1);
		return;
	}



	$sock->SET_INFO("KerbAuthDisableNormalizeName", $_POST["KerbAuthDisableNormalizeName"]);
	$sock->SET_INFO("EnableKerberosAuthentication", $_POST["EnableKerberosAuthentication"]);
	$sock->SET_INFO("KerbAuthDisableNsswitch", $_POST["KerbAuthDisableNsswitch"]);
	$sock->SET_INFO("KerbAuthDisableGroupListing", $_POST["KerbAuthDisableGroupListing"]);
	$sock->SET_INFO("KerbAuthTrusted", $_POST["KerbAuthTrusted"]);
	$sock->SET_INFO("KerbAuthMapUntrustedDomain", $_POST["KerbAuthMapUntrustedDomain"]);
	$sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);
	$sock->SET_INFO("KerbAuthMethod", $_POST["KerbAuthMethod"]);
	$sock->SET_INFO("SquidNTLMKeepAlive", $_POST["SquidNTLMKeepAlive"]);
	
	



	if($_POST["EnableKerberosAuthentication"]==1){$sock->SET_INFO("EnableKerbAuth", 0);}

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
		while (list ($num, $a) = each ($ipaddrZ) ){
			$ipaddrZ[$num]=intval($a);
		}
		$_POST["ADNETIPADDR"]=@implode(".", $ipaddrZ);
	}

	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	
	$sock->SET_INFO("DynamicGroupsAclsTTL", $_POST["DynamicGroupsAclsTTL"]);
	$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($_POST["UseDynamicGroupsAcls"]==1){
		if($_POST["LDAP_SERVER"]==null){echo $tpl->javascript_parse_text("LDAP: {hostname} Not set\n");return;}
		if(!is_numeric($_POST["LDAP_PORT"])){echo $tpl->javascript_parse_text("LDAP: {ldap_port} Not set\n");return;}
		if($_POST["LDAP_SUFFIX"]==null){echo $tpl->javascript_parse_text("LDAP: {suffix} Not set\n");return;}
		if($_POST["LDAP_DN"]==null){echo $tpl->javascript_parse_text("LDAP: {bind_dn} Not set\n");return;}
		if($_POST["LDAP_PASSWORD"]==null){echo $tpl->javascript_parse_text("LDAP: {password} Not set\n");return;}
	
	}
	
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){$array[$num]=$ligne;}
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
	
	$ldap_connection=@ldap_connect($_POST["LDAP_SERVER"],$_POST["LDAP_PORT"]);
	if(!$ldap_connection){
		echo "Connection Failed to connect to DC ldap://{$_POST["LDAP_SERVER"]}:{$_POST["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
		$error=$error."\n$extended_error";
		}
		@ldap_close();
		return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $_POST["LDAP_DN"],$_POST["LDAP_PASSWORD"]);
	if(!$bind){
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error."\n$extended_error";}
		echo "Failed to login to DC {$_POST["LDAP_SERVER"]} - {$_POST["LDAP_DN"]} \n`$error`";
		return false;
	}
	
	
	if($EnableWebProxyStatsAppliance==1){
	include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("BUILDCONF");
		return;
	}
	
	
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");	

}

function kerbchkconf(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if(!$users->SAMBA_INSTALLED){
		
		echo $tpl->_ENGINE_parse_body("<center><p class=text-error style='font-size:14px'>{APP_SAMBA}: {NOT_INSTALLED}</div></p>");
	}


	if(!$users->MSKTUTIL_INSTALLED){echo $tpl->_ENGINE_parse_body(Paragraphe32("APP_MSKTUTIL", "APP_MSKTUTIL_NOT_INSTALLED", "Loadjs('setup.index.php?js=yes');", "error-24.png"));return;}

	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));

	if($users->SAMBA_INSTALLED){if($array["ADNETBIOSDOMAIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "ADNETBIOSDOMAIN", null, "error-24.png"));return;}}


	if($array["WINDOWS_DNS_SUFFIX"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_DNS_SUFFIX", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_NETBIOSNAME", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_TYPE"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_TYPE", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "administrator", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_PASS"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "password", null, "error-24.png"));return;}

	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){echo $tpl->_ENGINE_parse_body(Paragraphe32("WINDOWS_NAME_SERVICE_NOT_KNOWN", "noacco:<strong style='font-size:12px'>$hostname</strong>", null, "error-24.png"));return;}

	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if($EnableKerbAuth==1){
		$page=CurrentPageName();
		
	}


}

function ldap_auth_parameters(){
	$boot=new boostrap_form();
	
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	

	
	
	if($EnableKerbAuth==1){
		$error="<p class=text-error>{ldap_with_ad_explain}</p>";
	}	
	
	$boot->set_spacertitle("{local_ldap}");
	$boot->set_spacerexplain("{authenticate_users_explain}");
	$boot->set_checkbox("ldap_auth", "{local_ldap}", $squid->LDAP_AUTH);
	//$boot->set_checkbox("SquidLdapAuthEnableGroups", "{enable_group_checking}",$SquidLdapAuthEnableGroups);
	$boot->set_field("SquidLdapAuthBanner", "{auth_banner}", $SquidLdapAuthBanner,array("ENCODE"=>true));
	
	$boot->set_spacertitle("{remote_database}");
	$boot->set_spacerexplain("{SQUID_LDAP_AUTH_EXT}");
	
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$ldap_filter_users=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
	$ldap_filter_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$auth_banner=$squid->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
	$EnableSquidExternalLDAP=$squid->LDAP_EXTERNAL_AUTH;
	if($auth_banner==null){$auth_banner=$SquidLdapAuthBanner;}
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_port==null){$ldap_port=389;}

	
	$boot->set_checkbox("EnableSquidExternalLDAP", "{activate}",$EnableSquidExternalLDAP);
	$boot->set_field("ldap_server", "{hostname}", $ldap_server);
	$boot->set_field("ldap_port", "{listen_port}", $ldap_port);
	$boot->set_field("auth_banner", "{auth_banner}", $auth_banner);
	$boot->set_field("ldap_user", "{userdn}", $userdn);
	$boot->set_fieldpassword("ldap_password", "{ldap_password}", $ldap_password,array("ENCODE"=>true));
	$boot->set_field("ldap_suffix", "{ldap_suffix}", $ldap_suffix);
	$boot->set_field("ldap_filter_users", "{ldap_filter_users}", $ldap_filter_users);
	$boot->set_field("ldap_filter_group", "{ldap_filter_group}", $ldap_filter_group);
	$boot->set_button("{apply}");
	
	
	if(!$users->AsSquidAdministrator){
		$boot->set_form_locked();
	}
	$boot->set_Newbutton("{restart_onlysquid}", "Loadjs('squid.restart.php?onlySquid=yes&ask=yes');");
	echo $error.$boot->Compile();
	
}

function  ldap_auth_save(){
	$_POST["ldap_password"]=url_decode_special_tool($_POST["ldap_password"]);
	$squid=new squidbee();
	$tpl=new templates();
	$sock=new sockets();
	$squid->LDAP_AUTH=$_POST["ldap_auth"];
	$squid->SquidLdapAuthEnableGroups=0;
	
	
	if(isset($_POST["SquidLdapAuthBanner"])){
		$sock->SET_INFO("SquidLdapAuthBanner", $_POST["SquidLdapAuthBanner"]);
	}
	
	if($_POST["EnableSquidExternalLDAP"]==1){$squid->LDAP_AUTH=1;}else{
		if($_POST["ldap_auth"]==0){$squid->LDAP_AUTH=0;}
		
	}
	$squid->LDAP_EXTERNAL_AUTH=$_POST["EnableSquidExternalLDAP"];
	$squid->EXTERNAL_LDAP_AUTH_PARAMS=$_POST;
	
	
	
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		exit;
	}
}