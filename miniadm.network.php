<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
$PRIV=GetPrivs();
if(!$PRIV){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


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


main_page();

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
	if($_SESSION["ASDCHPAdmin"]){return true;}
	if($_SESSION["AsOrgDNSAdmin"]){return true;}
	return false;
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.network.php\">{network_services}</a>
		</div>
		<H1>{network_services}</H1>
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
	$tr=array();
	$dhcp=Paragraphe("64-dhcp.png", "{APP_DHCP}", "{APP_DHCP_TEXT}","miniadm.dhcp.php");
	$pdns=Paragraphe("dns-64.png", "{APP_PDNS}", "{APP_PDNS_TEXT}","miniadm.pdns.php");
	$domains=Paragraphe("domain-main-64.png", "{manage_internet_domains}", "{manage_internet_domains_text}","miniadm.smtpdom.php");
	
	
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
	echo $tpl->_ENGINE_parse_body(CompileTr3($tr));
}	
