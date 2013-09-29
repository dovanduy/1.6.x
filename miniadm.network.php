<?php
session_start();
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
if(isset($_GET["CalcCdir"])){CalcCdir();exit;}

$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["items"])){report_items();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}


if(isset($_GET["SaveNet-refresh"])){save_network_js2();exit;}
if(isset($_POST["SaveNet-refresh"])){save_network_results();exit;}
if(isset($_GET["save-network"])){save_network_js();exit;}
if(isset($_GET["save-network-popup"])){save_network_popup();exit;}
if(isset($_GET["apply-network"])){BuildNetConf();exit;}
main_page();


function save_network_js2(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="
var XCheck$t = function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		document.getElementById('code$t').innerHTML=results;
		ExecuteByClassName('SearchFunction');
		if(document.getElementById('NetFileGeneratedConfig')){ NetFileGeneratedConfigfnt();}
	}
	setTimeout('Check$t()',1000);
}

var NetFileGeneratedConfigfnt$t = function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		document.getElementById('NetFileGeneratedConfig').value=results;
		
	}
	
}
function NetFileGeneratedConfigfnt(){
	NetFileGeneratedConfig
	var XHR = new XHRConnection();
	XHR.appendData('initdajax','yes');
	XHR.sendAndLoad('miniadm.network.interfaces.php', 'GET',NetFileGeneratedConfigfnt$t);	

}
	
	
	function Check$t(){
		if(!YahooSetupControlOpen()){return;}
		if(!document.getElementById('code$t')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('SaveNet-refresh','yes');
		XHR.sendAndLoad('$page', 'POST',XCheck$t);		
	}
	
	Check$t();		
			
	";
	echo $html;
	
	
}



function save_network_js(){
$sock=new sockets();
$users=new usersMenus();
header("content-type: application/x-javascript");
$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");

$page=CurrentPageName();
$tpl=new templates();
$title=$tpl->javascript_parse_text("{save_network_settings}");
$html="YahooSetupControlModalFixed('700','$page?save-network-popup=yes','$title')";
echo $html;

}

function save_network_popup(){

	$f=explode("\n",file_get_contents("/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html"));
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{close}");
	krsort($f);
	$html="<div style='width:100%;height:550px;overflow:auto' id='code$t'>
			<div><code style='font-size:12px;white-space:normal;background-color:transparent;border:0px'>";
	while (list ($index, $val) = each ($f) ){
		$html=$html."$val<br>";
	
	}
	
	echo $html."</code></div></div>
	<hr>
	<center style='margin:5px'>".button($title, "YahooSetupControlHide()",16)."</center>
	<script>
		function Refresh$t(){
			Loadjs('$page?SaveNet-refresh=yes&t=$t');
			
		}
		setTimeout('Refresh$t()',1000);
	</script>
	
	";	
	
}

function save_network_results(){
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html")){
		senderrors("exec.virtuals-ip.php.html no such file, please wait..;");
	}
	
	$f=explode("\n",file_get_contents("/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html"));
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{close}");
	krsort($f);	
	$html="
	<div><code style='font-size:12px;white-space:normal;background-color:transparent;border:0px'>";
	while (list ($index, $val) = each ($f) ){
	$html=$html."$val<br>";
	
	}
	
	echo $html."</code></div>";	
}


function BuildNetConf(){
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	header("content-type: application/x-javascript");
	$t=time();
	echo "Loadjs('network.restart.php');";	


	
}

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function GetPrivs(){
		return isNetSessions();
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();

	$buuton0=button("{save_network_settings}", "Loadjs('$page?save-network=yes')");
	$buuton1=button("{compile_network_settings}", "Loadjs('$page?apply-network=yes')");
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.network.php\">{network_services}</a>
		</div>
		<H1>{network_services}</H1>
		<div style='text-align:right'>$buuton0&nbsp;$buuton1</div>
		<p>{network_services_text}</p>
	</div>	
	<div id='webstats-middle-$ff' class=BodyContent></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$boot=new boostrap_form();
	
	
	
	
	if(isset($_GET["title"])){
		$buuton0=button("{save_network_settings}", "Loadjs('$page?save-network=yes')");
		$buuton1=button("{compile_network_settings}", "Loadjs('$page?apply-network=yes')");
		$title=$tpl->_ENGINE_parse_body("<div style='float:right'>$buuton0&nbsp;$buuton1</div><H3>{network_services}</H3><p>{network_services_text}</p>");
	}
	
	if(isNetSessions()){
		$array["{edit_networks}"]="miniadm.network.interfaces.php";
		$array["{routing_tables}"]="miniadm.network.routes.php";
		
	}
	
	
	if($users->AsDnsAdministrator){
		if($users->POWER_DNS_INSTALLED){
			$array["{dns_service}"]="miniadm.PowerDNS.php?popup=yes&explain-title=yes";
		}	
		
	}
	
	
	if($_SESSION["ASDCHPAdmin"]){
		if($users->dhcp_installed){
			$array["{APP_DHCP}"]="miniadm.dhcp.php?webstats-middle=yes&explain-title=yes";
		}
	}

	
	if($_SESSION["AllowChangeDomains"]){
		$array["{manage_internet_domains}"]="miniadm.smtpdom.php?webstats-middle=yes&title=yes";
	}

	$array["{etc_hosts}"]="miniadm.network.etchosts.php";
	$array["{computers}"]="miniadm.computers.browse.php?page=yes";
	//$array["{events}"]="$page?events=yes";
	echo $title.$boot->build_tab($array);
	return;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
	
	
	$page=CurrentPageName();
	$tpl=new templates();	
	

	$tr=array();
	$dhcp=Paragraphe("64-dhcp.png", "{APP_DHCP}", "{APP_DHCP_TEXT}","miniadm.dhcp.php?webstats-middle=yes");
	$pdns=Paragraphe("dns-64.png", "{APP_PDNS}", "{APP_PDNS_TEXT}","miniadm.pdns.php");
	$domains=Paragraphe("domain-main-64.png", "{manage_internet_domains}",
			 "{manage_internet_domains_text}","miniadm.smtpdom.php");
	
	
	if(!$_SESSION["ASDCHPAdmin"]){$dhcp=Paragraphe("64-dhcp-grey.png", "{APP_DHCP}", "{APP_DHCP_TEXT}");}
	if(!$users->dhcp_installed){
		$dhcp=null;
		if($_SESSION["ASDCHPAdmin"]){
			$dhcp=Paragraphe("64-dhcp-grey.png", "{APP_DHCP}", "{APP_DHCP_TEXT}");
		}
	}

	
	if(!$_SESSION["AsOrgDNSAdmin"]){$pdns=Paragraphe("dns-64-grey.png", "{manage_DNS_database}", "{APP_PDNS_TEXT}");}
	if(!$_SESSION["AllowChangeDomains"]){$domains=Paragraphe("domain-main-64-grey.png", 
			"{manage_internet_domains}", "{manage_internet_domains_text}");}

	if(!$users->POWER_DNS_INSTALLED){$pdns=null;}
	
	
	
	
	$tr[]=$dhcp;
	$tr[]=$domains;
	$tr[]=$pdns;
	echo $title.$tpl->_ENGINE_parse_body(CompileTr3($tr));
}	
function CalcCdir(){
	if(isset($_GET["netmask"])){$netmask=$_GET["netmask"];}
	if(isset($_GET["ipaddr"])){$ipaddr=$_GET["ipaddr"];}
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$ip=new IP();
	
	$ipaddrTR=explode(".",$ipaddr);
	$ipaddr="{$ipaddrTR[0]}.{$ipaddrTR[1]}.{$ipaddrTR[2]}.0";
	
	if($netmask<>null){echo $ip->maskTocdir($ipaddr, $netmask);}	
	
	
}