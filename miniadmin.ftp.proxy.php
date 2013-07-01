<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");



if(isset($_POST["FTPProxyPort"])){FTP_PROXY_SAVE();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["status"])){status();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&startup={$_GET["startup"]}&title={$_GET["title"]}')</script>", $content);
	echo $content;	
}

function tabs() {
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{settings}"]="$page?settings=yes";
	//$array["{status}"]="$page?status=yes";
	$array["{events}"]="miniadm.system.syslog.php?prepend=ftp-proxy,ftp-child";
	echo $boot->build_tab($array);	
	
	
}

function settings(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	$FTPProxyPort=$sock->GET_INFO("FTPProxyPort");
	$FTPProxyListen=$sock->GET_INFO("FTPProxyListen");
	$FTPLogLevel=$sock->GET_INFO("FTPLogLevel");
	$EnableFTPProxy=$sock->GET_INFO("EnableFTPProxy");
	if($FTPProxyListen==null){$FTPProxyListen="0.0.0.0";}
	
	if(!is_numeric($FTPProxyPort)){$FTPProxyPort="2121";}
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}

	
	$FTPProxyUseAuth=$sock->GET_INFO("FTPProxyUseAuth");
	if(!is_numeric($FTPProxyUseAuth)){$FTPProxyUseAuth=0;}
	
	
	if($FTPLogLevel==null){$FTPLogLevel="INF";}	
	
	$FTPMaxClients=$sock->GET_INFO("FTPProxyMaxClients");
	if(!is_numeric($FTPMaxClients)){$FTPMaxClients=64;}
	
	$FTPProxyTimeOuts=$sock->GET_INFO("FTPProxyTimeOuts");
	if(!is_numeric($FTPProxyTimeOuts)){$FTPProxyTimeOuts=900;}
	
	$FTPProxyDestinationTransferMode=$sock->GET_INFO("FTPProxyDestinationTransferMode");
	if($FTPProxyDestinationTransferMode==null){$FTPProxyDestinationTransferMode="client";}
	

	$LDAPAuthDN=$sock->GET_INFO("FTPLDAPAuthDN");
	
	$LDAPAuthPWAttr=$sock->GET_INFO("FTPLDAPAuthPWAttr");
	$LDAPBaseDN=$sock->GET_INFO("FTPLDAPBaseDN");
	$LDAPBindDN=$sock->GET_INFO("FTPLDAPBindDN");
	$LDAPServer=$sock->GET_INFO("FTPLDAPServer");
	
	
	
	if($LDAPAuthDN==null){$LDAPAuthDN="dc=domain,dc=tld";}
	if($LDAPAuthPWAttr==null){$LDAPAuthPWAttr="userPassword";}
	if($LDAPBaseDN==null){$LDAPBaseDN="dc=domain,dc=tld";}
	if($LDAPBindDN==null){$LDAPBindDN="%s@domain.tld";}
	if($LDAPServer==null){$LDAPServer="10.10.0.2";}
	
	
	$FTPUserAuthMagic=$sock->GET_INFO("FTPUserAuthMagic");
	$FTPUseMagicChar=$sock->GET_INFO("FTPUseMagicChar");
	$FTPAllowMagicUser=$sock->GET_INFO("FTPAllowMagicUser");
	
	if($FTPUserAuthMagic==null){$FTPUserAuthMagic="@user";}
	if($FTPUseMagicChar==null){$FTPUseMagicChar="@";}
	if(!is_numeric($FTPAllowMagicUser)){$FTPAllowMagicUser=1;}
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips["0.0.0.0"]="{all}";
	
	
	$LogLevel["FLT"]="FLT";
	$LogLevel["ERR"]="ERR";
	$LogLevel["WRN"]="WRN";
	$LogLevel["INF"]="INF";
	$LogLevel["DBG"]="{debug}";
	
	$DestinationTransferModeR["client"]="client";
	$DestinationTransferModeR["client"]["passive"]="passive";
	$DestinationTransferModeR["client"]["active"]="client";
	
	$boot=new boostrap_form();
	$boot->set_checkbox("EnableFTPProxy", "{enabled}", $EnableFTPProxy,array("DISABLEALL"=>true));
	$boot->set_field("FTPProxyPort", "{listen_port}", $FTPProxyPort);
	$boot->set_list("FTPProxyListen", "{listen_addr}",$ips,$FTPProxyListen);
	$boot->set_list("FTPLogLevel", "{log level}",$LogLevel,$FTPLogLevel);
	$boot->set_field("FTPProxyMaxClients", "{MaxClients}", $FTPMaxClients);
	$boot->set_field("FTPProxyTimeOuts", "{timeout2} ({seconds})", $FTPProxyTimeOuts);
	$boot->set_list("FTPProxyDestinationTransferMode", "{FTPProxyDestinationTransferMode}",$DestinationTransferModeR,
			$FTPProxyDestinationTransferMode,array("TOOLTIP"=>"{FTPProxyDestinationTransferMode_explain}"));

	
	$boot->set_subtitle("LDAP");
	$boot->set_spacerexplain("{FTPProxyLDAPExplain}");
	$boot->set_checkbox("FTPProxyUseAuth", "{enabled}", $FTPProxyUseAuth,array("LINK"=>"FTPLDAPServer,FTPLDAPBindDN"));
	$boot->set_field("FTPLDAPServer", "{ldap_server}", $LDAPServer);
	$boot->set_field("FTPLDAPBindDN", "{ldap_user_dn}", $LDAPBindDN);
	$boot->set_checkbox("FTPAllowMagicUser", "{AllowMagicUser}", $FTPAllowMagicUser,
			array("LINK"=>"FTPUserAuthMagic,FTPUseMagicChar","TOOLTIP"=>"{FTPAllowMagicUser_explain}"));
	$boot->set_field("FTPUserAuthMagic", "{UserAuthMagic}", $FTPUserAuthMagic,
			array("TOOLTIP"=>"{UserAuthMagic_explain}")
			);
	$boot->set_field("FTPUseMagicChar", "{FTPUseMagicChar}", $FTPUseMagicChar,
			array("TOOLTIP"=>"{FTPUseMagicChar_explain}")
	);	
	
	
	
	$boot->set_button("{apply}");
	if(!$users->AsSquidAdministrator){$boot->set_form_locked();}
	$form=$boot->Compile();	
	
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' width=350px><div id='$t'></div>
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-24.png",null,"LoadAjax('$t','$page?status=yes');")."</div>
		
		</td>
		<td valign='top' style='padding-left:15px'>$form</td>
	</tr>
	</table>
	<script>
		LoadAjax('$t','$page?status=yes');
	</script>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function status(){
	$sock=new sockets();
	$status=base64_decode($sock->getFrameWork("ftpproxy.php?status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($status);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_FTP_PROXY",$ini,null,1);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($APP_HAARP);	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title="{APP_FTP_PROXY}";

	$html="
	<H3>$title</H3>
		<div id='statistics-$t'></div>
	</div>	
	<div id='middle-$t' class=BodyContent></div>
	
	<script>
		LoadAjax('middle-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function FTP_PROXY_SAVE(){
	$sock=new sockets();
	while (list ($num, $val) = each ($_POST) ){
		$sock->SET_INFO($num, $val);
	}
	
	$sock->getFrameWork("ftpproxy.php?restart=yes");
	
}


