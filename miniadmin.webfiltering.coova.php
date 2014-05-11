<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsHotSpotManager){senderror("ERROR_NO_PRIVS}");}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["splash-section"])){splash_section();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-tabs"])){parameters_tab();exit;}
if(isset($_POST["HS_WANIF"])){SaveMainConf();exit;}
if(isset($_GET["rules-search"])){rules_search();exit;}
if(isset($_GET["splash-search"])){splash_search();exit;}
if(isset($_GET["splash-create-js"])){splash_create_js();exit;}
if(isset($_POST["splash-create"])){splash_create();exit;}
if(isset($_POST["splash-delete"])){splash_delete();exit;}



if(isset($_GET["section-uamallowed"])){uamallowed_section();exit;}
if(isset($_GET["uamallowed-search"])){uamallowed_search();exit;}
if(isset($_GET["uamallowed-create-js"])){uamallowed_create_js();exit;}
if(isset($_POST["uamallowed-create"])){uamallowed_create();exit;}
if(isset($_POST["uamallowed-delete"])){uamallowed_delete();exit;}

if(isset($_GET["section-activedirectory"])){activedirectory();exit;}
if(isset($_POST["EnableActiveDirectory"])){activedirectory_save();exit;}


if(isset($_GET["sessions-section"])){sessions_section();exit;}
if(isset($_GET["sessions-search"])){sessions_search();exit;}
if(isset($_POST["session-delete"])){sessions_delete();exit;}
if(isset($_POST["session-connect"])){sessions_connect();exit;}
if(isset($_GET["srv-status"])){status();exit;}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?parameters-tabs=yes";
	$array["{sessions}"]="$page?sessions-section=yes";
	$array["{events}"]="miniadm.system.syslog.php?prepend=coova-chilli";
	$users=new usersMenus();
	echo $boot->build_tab($array);
}
function splash_create_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ask=$tpl->javascript_parse_text("{webserver}");
	header("content-type: application/x-javascript");
	$html="
	
	var xAsk$t=function (obj) {
		var results=obj.responseText;
		if(results.length>10){alert(results);return;}
		ExecuteByClassName('SearchFunction');
	}		
	
	
		function Ask$t(){
			var serv=prompt('$ask ?','hostspot.domain.tld');
			if(!serv){return;}
			var XHR = new XHRConnection();
			XHR.appendData('splash-create',serv);
			XHR.sendAndLoad('$page', 'POST',xAsk$t);
		
		}
			
		Ask$t();";
	
	echo $html;
	
}

function parameters_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{general_settings}"]="$page?parameters=yes";
	$array["Active Directory"]="$page?section-activedirectory=yes";
	$array["{SplashPages}"]="$page?splash-section=yes";
	$array["{allowed_networks}"]="$page?section-uamallowed=yes";
	$users=new usersMenus();
	echo $boot->build_tab($array);	
	
	
}

function activedirectory(){
	$sock=new sockets();
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));	
	
	$boot=new boostrap_form();
	
	if(!is_numeric($ChilliConf["AD_PORT"])){$ChilliConf["AD_PORT"]=389;}
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	$Interfaces[null]="{none}";
	unset($Interfaces["lo"]);
	if($ChilliConf["AD_DOMAIN"]==null){$ChilliConf["AD_DOMAIN"]="my-ad-domain.tld";}
	$boot->set_spacertitle("Microsoft Active Directory");
	$boot->set_checkbox("EnableActiveDirectory", "{enable}", $ChilliConf["EnableActiveDirectory"],array("DISABLEALL"=>true));
	$boot->set_field("AD_SERVER", "{hostname}", $ChilliConf["AD_SERVER"]);
	$boot->set_field("AD_PORT", "{ldap_port}", $ChilliConf["AD_PORT"]);
	$boot->set_field("AD_DOMAIN", "{domain}", $ChilliConf["AD_DOMAIN"]);

	$boot->set_button("{apply}");
	echo $boot->Compile();
}

function activedirectory_save(){
	define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
	$_POST["AD_PASS"]=url_decode_special_tool($_POST["AD_PASS"]);
	
	$sock=new sockets();
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	while (list ($num, $ligne) = each ($_POST) ){
		$ChilliConf[$num]=$ligne;
	
	}
	$ChilliConfNew=base64_encode(serialize($ChilliConf));
	$sock->SaveConfigFile($ChilliConfNew, "ChilliConf");
	$sock->getFrameWork("chilli.php?build=yes&nohup=yes");
	
	
	$cnx=@ldap_connect($_POST["AD_SERVER"],$_POST["AD_PORT"]);

	if(!$cnx){
		echo "Fatal: ldap_connect({$_POST["AD_SERVER"]},{$_POST["AD_PORT"]} )\nCheck your configuration\n";
		@ldap_close();
		return false;
	}
	@ldap_close();
	
	
	
	
}


function uamallowed_create_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ask=$tpl->javascript_parse_text("{chilli_allowed_networks_exp}");
	header("content-type: application/x-javascript");
	$html="
	
var xAsk$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
}
	
	
function Ask$t(){
	var serv=prompt('$ask','192.168.1.0/24');
	if(!serv){return;}
	var XHR = new XHRConnection();
	XHR.appendData('uamallowed-create',serv);
	XHR.sendAndLoad('$page', 'POST',xAsk$t);
}
		
Ask$t();";
	
	echo $html;	
	
}

function uamallowed_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_item}","Loadjs('$page?uamallowed-create-js=yes')",16);
	$button_edit=null;
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen(null,"uamallowed-search",null,$EXPLAIN);
	echo $tpl->_ENGINE_parse_body($SearchQuery);
		
	
}


function parameters(){
	$sock=new sockets();
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	
	$NICS=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	if(count($NICS)==1){
		$tpl=new templates();
		$COOVA_ERROR_NO_2_INTERFACES=$tpl->_ENGINE_parse_body("{COOVA_ERROR_NO_2_INTERFACES}");
		echo "<p class=text-error>$COOVA_ERROR_NO_2_INTERFACES</p>";
		return;
	}
	
	
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	$Interfaces[null]="{none}";
	unset($Interfaces["lo"]);
	
	if(!isset($ChilliConf["HS_WANIF"])){$ChilliConf["HS_WANIF"]=null;}
	if(!isset($ChilliConf["HS_LANIF"])){
		$arrayTCP=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
		$ALLARRAY=$arrayTCP["eth0"];
		$PR=explode(".",$ALLARRAY["IPADDR"]);
		$ChilliConf["HS_NETWORK"]="{$PR[0]}.{$PR[2]}.{$PR[3]}.0";
		$ChilliConf["HS_DYNIP"]="{$PR[0]}.{$PR[2]}.{$PR[3]}.50";
		$ChilliConf["HS_DYNIP_MASK"]=$ALLARRAY["NETMASK"];
		$ChilliConf["HS_UAMLISTEN"]=$ALLARRAY["IPADDR"];
		$ChilliConf["HS_NETMASK"]=$ALLARRAY["NETMASK"];
		$ChilliConf["HS_LANIF"]="eth0";
		$ChilliConf["HS_DYNIP_START"]=50;
	}	
	
	$boot=new boostrap_form();
	$boot->set_spacertitle("{service_parameters}");
	$boot->set_checkbox("EnableChilli", "{enable}", $EnableChilli,array("DISABLEALL"=>true));
	$boot->set_checkbox("HS_DEBUG", "{debug}", $ChilliConf["HS_DEBUG"]);
	
	if(!isset($ChilliConf["HS_DNS_DOMAIN"])){$ChilliConf["HS_DNS_DOMAIN"]="hotspot.domain.tld";}
	
	if(!isset($ChilliConf["HS_PROVIDER"])){$ChilliConf["HS_PROVIDER"]="Artica";}
	if(!isset($ChilliConf["HS_PROVIDER_LINK"])){$ChilliConf["HS_PROVIDER_LINK"]="http://www.articatech.net";}
	if(!isset($ChilliConf["HS_LOC_NAME"])){$ChilliConf["HS_LOC_NAME"]="Artica HotSpot";}	
	if($ChilliConf["HS_LOC_NETWORK"]==null){$ChilliConf["HS_LOC_NETWORK"]="HotSpot Network";}
	
	if(!isset($ChilliConf["HS_DNS1"])){$ChilliConf["HS_DNS1"]=null;}
	if(!isset($ChilliConf["HS_DNS2"])){$ChilliConf["HS_DNS2"]=null;}	
	
	if(!isset($ChilliConf["SQUID_HTTP_PORT"])){$ChilliConf["SQUID_HTTP_PORT"]=rand(45000,65400);}
	if(!is_numeric($ChilliConf["SQUID_HTTP_PORT"])){$ChilliConf["SQUID_HTTP_PORT"]=rand(45000,65400);}
	
	if(!isset($ChilliConf["SQUID_HTTPS_PORT"])){$ChilliConf["SQUID_HTTPS_PORT"]=rand(45000,65400);}
	if(!is_numeric($ChilliConf["SQUID_HTTPS_PORT"])){$ChilliConf["SQUID_HTTPS_PORT"]=rand(45000,65400);}	
	
	if(!is_numeric($ChilliConf["ENABLE_DHCP_RELAY"])){$ChilliConf["ENABLE_DHCP_RELAY"]=0;}
	
	if($ChilliConf["HS_DNS1"]==null){$ChilliConf["HS_DNS1"]="8.8.8.8";}
	if($ChilliConf["HS_DNS2"]==null){$ChilliConf["HS_DNS2"]="8.8.4.4";}
	
	$boot->set_spacertitle("{hotspot_network}");
	$boot->set_list("HS_LANIF", "{HS_LANIF}", $Interfaces,$ChilliConf["HS_LANIF"]);
	$boot->set_field("HS_UAMLISTEN", "{ipaddr}", $ChilliConf["HS_UAMLISTEN"]);
	$boot->set_field("HS_NETMASK", "{mask}", $ChilliConf["HS_NETMASK"]);
	$boot->set_field("HS_DNS1", "DNS 1", $ChilliConf["HS_DNS1"]);
	$boot->set_field("HS_DNS2", "DNS 2", $ChilliConf["HS_DNS2"]);
	$boot->set_checkbox("HS_LAN_ACCESS", "{HS_LAN_ACCESS}", $ChilliConf["HS_LAN_ACCESS"]);	
	
	
	$boot->set_subtitle("{dhcp_parameters}");
	$boot->set_field("HS_DYNIP_START", "{dhcp_start_ip}", $ChilliConf["HS_DYNIP_START"]);
	$boot->set_field("HS_DNS_DOMAIN", "{domain}", $ChilliConf["HS_DNS_DOMAIN"]);
	$boot->set_checkbox("ENABLE_DHCP_RELAY", "{use_remote_dhcp_server}",
			$ChilliConf["ENABLE_DHCP_RELAY"],
			array("LINK"=>"DHCP_IF,HS_DHCPGATEWAY",
					"TOOLTIP"=>"{coova_ssl_splash_explain}"));
	$boot->set_field("HS_DHCPGATEWAY", "{dhcp_server_ip}", $ChilliConf["HS_DHCPGATEWAY"]);
	$boot->set_list("DHCP_IF", "{nic}", $Interfaces,$ChilliConf["DHCP_IF"]);
	
	
	
	$boot->set_spacertitle("{internet_network}");
	$boot->set_list("HS_WANIF", "{HS_WANIF}", $Interfaces,$ChilliConf["HS_WANIF"]);
	
	
	
	
	$boot->set_spacertitle("{proxy_parameters}");
	$boot->set_field("SQUID_HTTP_PORT", "{proxy_http_port} (local)", $ChilliConf["SQUID_HTTP_PORT"],array("TOOLTIP"=>"{coova_proxy_port}"));
	$boot->set_field("SQUID_HTTPS_PORT", "{proxy_https_port} (local)", $ChilliConf["SQUID_HTTPS_PORT"],array("TOOLTIP"=>"{coova_proxy_sslport}"));
	$boot->set_checkbox("CoovaUFDBEnabled", "{webfiltering}", $ChilliConf["CoovaUFDBEnabled"],array("TOOLTIP"=>"{coova_CoovaUFDBEnabled_explain}"));
	$q=new mysql();
	$sql="SELECT servername FROM freeweb WHERE groupware='CHILLI'";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$FREEWEBS[$ligne["servername"]]=$ligne["servername"];
	
	}
	

	
	
	
	

	
	

	
	
	$boot->set_spacertitle("{design}");
	
	$q=new mysql();
	$sslcertificates[null]="{default}";
	$results=$q->QUERY_SQL("SELECT * FROM sslcertificates",'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"];
	}
	
	$boot->set_checkbox("EnableSSLRedirection", "{UseSSL}", $ChilliConf["EnableSSLRedirection"],array("LINK"=>"certificate_center","TOOLTIP"=>"{coova_ssl_splash_explain}"));
	$boot->set_list("certificate_center", "{default_certificate}", $sslcertificates,$ChilliConf["certificate_center"]);	
	
	$boot->set_list("HS_UAMFREEWEB", "FreeWeb", $FREEWEBS,$ChilliConf["HS_UAMFREEWEB"]);
	$boot->set_field("HS_PROVIDER", "{company}", $ChilliConf["HS_PROVIDER"]);
	$boot->set_field("HS_PROVIDER_LINK", "{website}", $ChilliConf["HS_PROVIDER_LINK"]);
	$boot->set_field("HS_LOC_NAME", "{servicename}", $ChilliConf["HS_LOC_NAME"]);
	$boot->set_field("HS_LOC_NETWORK", "{network_name}", $ChilliConf["HS_LOC_NETWORK"]);
	$page=CurrentPageName();
	
	$boot->set_AjaxFinal("LoadAjax('chilli-status','$page?srv-status=yes');");
	
	$boot->set_formtitle("HotSpot");
	$form=$boot->Compile();
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top;width:30%'><div id='chilli-status'></div>
		
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('chilli-status','$page?srv-status=yes');")."</div>
		</td>
		<td valign='top' style='vertical-align:top;padding-left:15px;width:70%'>$form</td>
	</tr>
	</table>
	<script>
		LoadAjax('chilli-status','$page?srv-status=yes');		
			
	</script>";
	echo $html;
	
	
}

function status(){
	$sock=new sockets();
	$status=base64_decode($sock->getFrameWork("chilli.php?status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($status);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_HOTSPOT",$ini,null,1);
	$APP_HOTSPOT_DNSMASQ=DAEMON_STATUS_ROUND("APP_HOTSPOT_DNSMASQ",$ini,null,1);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($APP_HAARP);
	echo $tpl->_ENGINE_parse_body($APP_HOTSPOT_DNSMASQ);

	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	$ChilliConf=GetInterfaceArray($ChilliConf);
	$wan_ip=$ChilliConf["HS_WANIF_IP"];
	$lan_ip=$ChilliConf["HS_UAMLISTEN"];
	
	if($ChilliConf["HS_LANIF"]==null){
		$CHILLI_ERROR_SAME_NETS=$tpl->_ENGINE_parse_body("{CHILLI_HS_LANIF_NULL}");
		echo "<p class=text-error>$CHILLI_ERROR_SAME_NETS</p>";
		
	}
	if($ChilliConf["HS_WANIF"]==null){
		$CHILLI_ERROR_SAME_NETS=$tpl->_ENGINE_parse_body("{CHILLI_HS_WANIF_NULL}");
		echo "<p class=text-error>$CHILLI_ERROR_SAME_NETS</p>";
	
	}	
	$PR=explode(".",$lan_ip);
	$lan="{$PR[0]}.{$PR[1]}.{$PR[2]}.0";
	$PR=explode(".",$wan_ip);
	$wan="{$PR[0]}.{$PR[1]}.{$PR[2]}.0";
	if($lan==$wan){
		$CHILLI_ERROR_SAME_NETS=$tpl->_ENGINE_parse_body("{CHILLI_ERROR_SAME_NETS}");
		$CHILLI_ERROR_SAME_NETS=str_replace("%a", $lan, $CHILLI_ERROR_SAME_NETS);
		$CHILLI_ERROR_SAME_NETS=str_replace("%b", $wan, $CHILLI_ERROR_SAME_NETS);
		echo "<p class=text-error>$CHILLI_ERROR_SAME_NETS</p>";	
		
	}
	
	if($ChilliConf["HS_UAMFREEWEB"]==0){$ChilliConf["HS_UAMFREEWEB"]=null;}
	if(trim($ChilliConf["HS_UAMFREEWEB"])==null){
		$CHILLI_ERROR_NO_FREEWEB=$tpl->_ENGINE_parse_body("{CHILLI_ERROR_NO_FREEWEB}");
		echo "<p class=text-error>$CHILLI_ERROR_NO_FREEWEB</p>";
		
	}
	
}

function SaveMainConf(){
	$sock=new sockets();
	$tpl=new templates();
	
	$ok=true;
	if($_POST["HS_WANIF"]==null){
		echo $tpl->javascript_parse_text("{you_need_to_define_the_second_network_card}");
		$ok=false;
	}
	if($_POST["HS_WANIF"]==$_POST["HS_LANIF"]){
		echo $tpl->javascript_parse_text("{interfaces_cards_cannot_be_the_same}");
		$ok=false;		
	}
	
	if($_POST["HS_UAMFREEWEB"]==null){
		echo $tpl->javascript_parse_text("{you_need_to_define_the_hostpot_webservice}");
		$ok=false;
	}	
	
	if($ok){
		$sock->SET_INFO("EnableChilli", $_POST["EnableChilli"]);
		if($_POST["EnableChilli"]==1){$sock->SET_INFO("EnableFreeRadius",1);}
	}else{
		$sock->SET_INFO("EnableChilli", 0);
	}
	
	
	

	
	
	
	
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	while (list ($num, $ligne) = each ($_POST) ){
		$ChilliConf[$num]=$ligne;
		
	}
	$ChilliConfNew=base64_encode(serialize($ChilliConf));
	$sock->SaveConfigFile($ChilliConfNew, "ChilliConf");
	if($ok){
		$sock->getFrameWork("chilli.php?restart=yes&nohup=yes");
	}
}

function splash_section(){
	//personal_categories
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{new_splash_page}","Loadjs('$page?splash-create-js=yes')",16);
	$button_edit=null;
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen("servername","splash-search",null,$EXPLAIN);
	echo $tpl->_ENGINE_parse_body($SearchQuery);
	
}

function sessions_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen(null,"sessions-search",null);
	echo $tpl->_ENGINE_parse_body($SearchQuery);	
	
}

function uamallowed_create(){
	$sock=new sockets();
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));	
	$ChilliConf["uamallowed"][$_POST["uamallowed-create"]]=true;
	$NewArray=base64_encode(serialize($ChilliConf));
	$sock->SaveConfigFile($NewArray, "ChilliConf");
	$sock->getFrameWork("chilli.php?restart=yes&nohup=yes");
}
function uamallowed_delete(){
	$sock=new sockets();
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	unset($ChilliConf["uamallowed"][$_POST["uamallowed-delete"]]);
	$NewArray=base64_encode(serialize($ChilliConf));
	$sock->SaveConfigFile($NewArray, "ChilliConf");
	$sock->getFrameWork("chilli.php?restart=yes&nohup=yes");
}

function sessions_search(){

	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$searchstring=string_to_flexregex("sessions-search");
	$arrayS=unserialize(base64_decode($sock->getFrameWork("chilli.php?query=yes")));
	if(!is_array($arrayS)){senderrors("no data");}
	
	
	while (list ($ID, $array) = each ($arrayS) ){
		$md=md5(serialize($array));
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", serialize($array))){continue;}
		}
	
		$MAC=$array["MAC"];
		$IP=$array["IP"];
		$STATUS=$array["STATUS"];
		$UID=$array["UID"];
		$URI=$array["URI"];
		$delete="&nbsp;";
		$img="ok32-grey.png";
		$color="#A2A2A2";
		$delete=null;
		
		if($STATUS=="pass"){
			$img="ok32.png";
			$color="black";
			$delete=imgtootltip("delete-32.png",null,"Delete$t('$ID')");
		}else{
			$delete=imgtootltip("check-32.png",null,"Connect$t('$ID')");
			if($UID<>null){
				
				$color="black";
			}
		}
		
		$tr[]="
		<tr id='$md'>
		<td width=1% nowrap style='vertical-align:middle;text-align:center'><img src='img/$img'></td>
		<td width=5% style='vertical-align:middle' nowrap><span style='font-size:18px;font-weight:bold;color:$color'>$UID</span></td>
		<td width=5% style='vertical-align:middle' nowrap><span style='font-size:18px;font-weight:bold;color:$color'>$IP</span></td>
		<td width=5% style='vertical-align:middle' nowrap><span style='font-size:18px;font-weight:bold;color:$color'>$MAC</span></td>
		<td width=1% nowrap style='vertical-align:middle;text-align:center'>$delete</td>
		</tr>
		";		
		
	}
	$deleteTXT=$tpl->javascript_parse_text("{disconnect_session}");
	echo $tpl->_ENGINE_parse_body("
<table class='table table-bordered table-hover'>
<thead>
	<tr>
		<th colspan=2>{members} $searchstring</th>
		<th>{ipaddr}</th>
		<th>{MAC}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody>").
	@implode("", $tr)."</tbody></table>
	<script>
var FreeWebIDMEM$t='';
	var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	}
	
function Delete$t(id){
	if(confirm('$deleteTXT \"'+id+'\" ?')){
		var XHR = new XHRConnection();
		XHR.appendData('session-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
function Connect$t(id){
	if(confirm('Allow \"'+id+'\" ?')){
		var XHR = new XHRConnection();
		XHR.appendData('session-connect',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}	</script>";	
	
}

function uamallowed_search(){

	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$searchstring=string_to_flexregex();
	$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
	$t=time();
	if(!isset($ChilliConf["uamallowed"])){$ChilliConf["uamallowed"]=array();}
	if(!is_array($ChilliConf["uamallowed"])){$ChilliConf["uamallowed"]=array();}
	while (list ($num, $ligne) = each ($ChilliConf["uamallowed"]) ){
		$md=md5(serialize($num));
		
		$servername=$num;
		$servername_enc=urlencode($servername);
		$delete=imgtootltip("delete-64.png",null,"Delete$t('$servername','$md')");
		$tr[]="
		<tr id='$md'>
		<td width=1% nowrap style='vertical-align:middle'><img src='img/folder-network-64.png'></td>
		<td width=80% style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$servername</span></td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";		
		
	}
	
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
<table class='table table-bordered table-hover'>
<thead>
	<tr>
		<th colspan=2>{allowed_networks}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody>").
@implode("", $tr)."</tbody></table>
<script>
var FreeWebIDMEM$t='';
var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#'+FreeWebIDMEM$t).remove();
}
	
function Delete$t(id,md){
	FreeWebIDMEM$t=md;
	if(confirm('$deleteTXT \"'+id+'\" ?')){
		var XHR = new XHRConnection();
		XHR.appendData('uamallowed-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
}
</script>";	
}

function splash_search(){
	$q=new mysql();
	$searchstring=string_to_flexquery();
	
	$q->QUERY_SQL("DELETE FROM freeweb WHERE servername=''","artica_backup");
	
	$sql="SELECT * FROM freeweb WHERE groupware='CHILLI' $searchstring";
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$t=time();
	if(mysql_num_rows($results)==0){senderror("No data");}
	
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$md=md5(serialize($ligne));
		
		$servername=$ligne["servername"];
		$delete=imgtootltip("delete-64.png",null,"Delete$t('$servername','$md')");
		$tr[]="
		<tr id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/webfilter-64.png'></td>
		<td width=80% $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$servername</span></td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
		
	}
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{servername2}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
					<script>
					var FreeWebIDMEM$t='';
	
					var xDelete$t=function (obj) {
					var results=obj.responseText;
					if(results.length>10){alert(results);return;}
					$('#'+FreeWebIDMEM$t).remove();
	}
	
	function Delete$t(id,md){
	FreeWebIDMEM$t=md;
	if(confirm('$deleteTXT')){
	var XHR = new XHRConnection();
	XHR.appendData('splash-delete',id);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	}
</script>";

	
	
	
}
function splash_create(){
	$f=new freeweb($_POST["splash-create"]);
	$f->servername=$_POST["splash-create"];
	$f->groupware="CHILLI";
	$f->CreateSite();
	
}
function splash_delete(){
	$f=new freeweb($_POST["splash-delete"]);
	$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('DELETE_FREEWEB','{$_POST["splash-delete"]}')";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");
}


function sessions_delete(){
	$ID=$_POST["session-delete"];
	$sock=new sockets();
	$sock->getFrameWork("chilli.php?sessiondel=$ID");
	
}
function sessions_connect(){
	$ID=$_POST["session-connect"];
	$sock=new sockets();
	$sock->getFrameWork("chilli.php?sessioncon=$ID");	
	
}

function GetInterfaceArray($ChilliConf){
	
	if(!is_numeric($ChilliConf["HS_DYNIP"])){$ChilliConf["HS_DYNIP"]=50;}

	$array=InterfaceToIP($ChilliConf["HS_LANIF"]);

	
	
	$ChilliConf["HS_NETWORK"]=$array["NETWORK"];
	
	$ChilliConf["HS_DYNIP_MASK"]=$array["NETMASK"];
	
	if($ChilliConf["HS_UAMLISTEN"]==null){
		$ChilliConf["HS_UAMLISTEN"]=$array["IP"];
	}
	
	if($ChilliConf["HS_NETMASK"]==null){
		$ChilliConf["HS_NETMASK"]=$array["NETMASK"];
	}
	
	$PR=explode(".",$array["HS_UAMLISTEN"]);
	$ChilliConf["HS_DYNIP"]="{$PR[0]}.{$PR[1]}.{$PR[2]}.{$ChilliConf["HS_DYNIP"]}";
	$array=InterfaceToIP($ChilliConf["HS_WANIF"]);
	$ChilliConf["HS_WANIF_IP"]=$array["IP"];

	$array=InterfaceToIP($ChilliConf["DHCP_IF"]);
	$ChilliConf["HS_DHCPRELAYAGENT"]=$array["IP"];
	return $ChilliConf;

}

function InterfaceToIP($eth){
	if($eth==null){return array("IP"=>null,"NETMASK"=>null,"NETWORK"=>null,"IPADDR"=>null);}
	$nics=new system_nic($eth);
	if($nics->IPADDR<>null){
		$PR=explode(".",$nics->IPADDR);


		return array("IP"=>$nics->IPADDR,"IPADDR"=>$nics->IPADDR,
				"NETMASK"=>$nics->NETMASK,
				"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0"
		);
	}
	$sock=new sockets();
	$arrayTCP=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$ALLARRAY=$arrayTCP[$eth];

	$PR=explode(".",$ALLARRAY["IPADDR"]);

	return array("IP"=>$ALLARRAY["IPADDR"],"NETMASK"=>$ALLARRAY["NETMASK"]
			,"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0","IPADDR"=>$ALLARRAY["IPADDR"],
				
	);
}