<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["service"])){service();exit;}
if(isset($_POST["url-lookup-result-during-database-reload"])){service_save();exit;}
if(isset($_GET["ufdbguard-status"])){ufdbguard_status();exit;}

function tabs(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}

	if($EnableRemoteStatisticsAppliance==0){
		if($EnableRemoteStatisticsAppliance==0){
		$array["{service_parameters}"]="$page?service=yes";
		}
	}
	//if(!$users->WEBSTATS_APPLIANCE){$array["{client_parameters}"]="$page?client=yes";}
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
	
	
//	$array["notifs"]='{notifications}';
	//$array["import-export"]="{import}/{export}";	
	
}
function service(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$squid=new squidbee();
	$users=new usersMenus();
	$url_rewrite_bypass=$squid->url_rewrite_bypass;
	$ufdbguardReloadTTL=$sock->GET_INFO("ufdbguardReloadTTL");
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	
	if($datas["enforce-https-with-hostname"]==null){$datas["enforce-https-with-hostname"]=0;}
	if($datas["enforce-https-official-certificate"]==null){$datas["enforce-https-official-certificate"]=0;}
	if($datas["https-prohibit-insecure-sslv2"]==null){$datas["https-prohibit-insecure-sslv2"]=0;}
	if(!is_numeric($datas["url-lookup-result-during-database-reload"])){$datas["url-lookup-result-during-database-reload"]=1;}
	if(!is_numeric($datas["url-lookup-result-when-fatal-error"])){$datas["url-lookup-result-when-fatal-error"]=1;}
	if(!is_numeric($datas["check-proxy-tunnel"])){$datas["check-proxy-tunnel"]=1;}
	if(!is_numeric($datas["strip-domain-from-username"])){$datas["strip-domain-from-username"]=0;}
	if(!is_numeric($datas["refreshuserlist"])){$datas["refreshuserlist"]=15;}
	if(!is_numeric($datas["refreshdomainlist"])){$datas["refreshdomainlist"]=15;}
	
	
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	
	if(!is_numeric($datas["allow-unknown-protocol-over-https"])){$datas["allow-unknown-protocol-over-https"]=1;}
	
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="all";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="all";}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if(!is_numeric($datas["DebugAll"])){$datas["DebugAll"]=0;}
	if(!is_numeric($ufdbguardReloadTTL)){$ufdbguardReloadTTL=10;}
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	$WEBSTATS_APPLIANCE=0;
	
	if($users->WEBSTATS_APPLIANCE){
		$WEBSTATS_APPLIANCE=1;
		$datas["tcpsockets"]=1;
	}
	
	$sys=new networking();
	$ips=$sys->ALL_IPS_GET_ARRAY();
	if($users->WEBSTATS_APPLIANCE){
		unset($ips["127.0.0.1"]);
	}
	
	$as27=0;
	if($squid->IS_27){senderrors("{not_supported} SQUID v2.7");}
	
	$ips["all"]="{all}";	
	
	$boot=new boostrap_form();
	
	
	$boot->set_spacertitle("{feature}");
	$boot->set_checkbox("EnableUfdbGuard","{EnableUfdbGuard}","$EnableUfdbGuard",array("DISABLEALL"=>true));
	
	$boot->set_spacertitle("SSL");
	
	$boot->set_checkbox("enforce-https-with-hostname", "{enforce-https-with-hostname}", $datas["enforce-https-with-hostname"],
			array("TOOLTIP"=>"{UFDBGUARD_SSL_OPTS}"));
	$boot->set_checkbox("enforce-https-official-certificate", "{enforce-https-official-certificate}",
			$datas["enforce-https-official-certificate"]);
	$boot->set_checkbox("https-prohibit-insecure-sslv2", "{https-prohibit-insecure-sslv2}",
			$datas["https-prohibit-insecure-sslv2"]);	
	
	$boot->set_checkbox("allow-unknown-protocol-over-https", "{allow-unknown-protocol-over-https}",
			$datas["allow-unknown-protocol-over-https"]);
	
	
	$boot->set_checkbox("check-proxy-tunnel", "{check-proxy-tunnel}",
			$datas["check-proxy-tunnel"]);
		

	$boot->set_spacertitle("{UFDBGUARD_SERVICE_OPTS}");
	

	
	
	$boot->set_checkbox("DebugAll", "{verbose_mode}",
			$datas["DebugAll"]);	

	$boot->set_checkbox("UfdbDatabasesInMemory", "{UfdbDatabasesInMemory}",
			$UfdbDatabasesInMemory,array("TOOLTIP"=>"{UfdbDatabasesInMemory_explain}"));
	
	$boot->set_field("ufdbguardReloadTTL", "{minimum_reload_interval} {minutes}",
			$ufdbguardReloadTTL);	
	
	$boot->set_checkbox("tcpsockets", "{enable_tcpsockets}",
			$datas["tcpsockets"],array("LINK"=>"listen_addr,listen_port"));

	$boot->set_list("listen_addr", "{listen_address}",$ips,$datas["listen_addr"]);	
	
	$boot->set_field("listen_port", "{listen_port}",
			$datas["listen_port"]);	
	
	
	$boot->set_checkbox("EnableGoogleSafeSearch", "{EnableGoogleSafeSearch}",
			$EnableGoogleSafeSearch);
	$boot->set_checkbox("strip-domain-from-username", "{strip-domain-from-username}",
			$datas["strip-domain-from-username"]);	
	
	$boot->set_checkbox("refreshuserlist", "{refreshuserlist} ({minutes})",
			$datas["refreshuserlist"]);

	$boot->set_checkbox("refreshdomainlist", "{refreshdomainlist} ({minutes})",
			$datas["refreshdomainlist"]);	
	
	$boot->set_spacertitle("{ON_ERRORS}");
	
	$boot->set_checkbox("url_rewrite_bypass", "{bypass_iffailed}",
			$url_rewrite_bypass,array("TOOLTIP"=>"{url_rewrite_bypass_explain}"));
	

	$boot->set_checkbox("url-lookup-result-during-database-reload", "{url-lookup-result-during-database-reload}",$datas["url-lookup-result-during-database-reload"]);	
	$boot->set_checkbox("url-lookup-result-when-fatal-error", "{url-lookup-result-when-fatal-error}",$datas["url-lookup-result-when-fatal-error"]);
	
	$boot->set_button("{apply}");
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){$boot->set_form_locked();}
	$form=$boot->Compile();
	
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:400px'>
			<div id='$t'></div>
			<div style='text-aling:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('$t','$page?ufdbguard-status=yes');")."</div>
			</td>
		<td style='vertical-align:top;padding-left:20px'>$form</td>
	</tr>
	</table>
	<script>
		LoadAjax('$t','$page?ufdbguard-status=yes');
	</script>
			
	";
	echo $html;
	
	
}

function ufdbguard_status(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?ufdb-ini-status=yes')));
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	echo $tpl->_ENGINE_parse_body($APP_UFDBGUARD."<br>".$APP_SQUIDGUARD_HTTP);
}


function service_save(){
	$RESTARTSQUID=false;
	if(isset($_POST["url_rewrite_bypass"])){
		$squid=new squidbee();
		$squid->url_rewrite_bypass=$_POST["url_rewrite_bypass"];
		$squid->SaveToLdap();
	}

	$sock=new sockets();
	if(isset($_POST["UseRemoteUfdbguardService"])){$sock->SET_INFO('UseRemoteUfdbguardService', $_POST["UseRemoteUfdbguardService"]);}
	
	if(isset($_POST["ufdbguardReloadTTL"])){
		writelogs("SET_INFO ufdbguardReloadTTL= {$_POST["ufdbguardReloadTTL"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('ufdbguardReloadTTL', $_POST["ufdbguardReloadTTL"]);
	}
	if(isset($_POST["EnableGoogleSafeSearch"])){
		writelogs("SET_INFO EnableGoogleSafeSearch= {$_POST["EnableGoogleSafeSearch"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('EnableGoogleSafeSearch', $_POST["EnableGoogleSafeSearch"]);
	}
	if(isset($_POST["UfdbDatabasesInMemory"])){
		writelogs("SET_INFO UfdbDatabasesInMemory= {$_POST["UfdbDatabasesInMemory"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('UfdbDatabasesInMemory', $_POST["UfdbDatabasesInMemory"]);
	}
	
	if(isset($_POST["EnableUfdbGuard"])){
		$EnableUfdbGuard=$sock->EnableUfdbGuard();
		if($EnableUfdbGuard<>$_POST["EnableUfdbGuard"]){$RESTARTSQUID=TRUE;}
		$sock->SET_INFO('EnableUfdbGuard', $_POST["EnableUfdbGuard"]);
		
	}
	
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	while (list ($key, $line) = each ($_POST) ){
		writelogs("SAVE $key = $line",__FUNCTION__,__FILE__,__LINE__);
		$datas[$key]=$line;
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");
	$sock->getFrameWork("cmd.php?reload-squidguard=yes");
	if($RESTARTSQUID){
		$sock->getFrameWork("cmd.php?squid-rebuild=yes");
	}	
	
}