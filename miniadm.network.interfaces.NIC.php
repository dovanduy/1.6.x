<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){throw new ErrorException("Bad gateway",500);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["nic-config"])){nic_config();exit;}
if(isset($_GET["failover"])){failover();exit;}
if(isset($_POST["save_nic"])){save_nic();exit;}
if(isset($_POST["start-vip"])){start_vip();exit;}
if(isset($_POST["stop-vip"])){stop_vip();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{interface}:{$_GET["nic"]}");
	echo "YahooWin(700,'$page?tabs=yes&nic={$_GET["nic"]}','$title')";

	
	
}

function tabs(){
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){throw new ErrorException("Bad gateway",500);}
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$array["{$_GET["nic"]}::{parameters}"]="$page?nic-config=yes&nic={$_GET["nic"]}";
	
	if($users->UCARP_INSTALLED){
		$array["{failover}"]="$page?failover=yes&nic={$_GET["nic"]}";
	}
	
	echo $boot->build_tab($array);	
	
	
}

function nic_config(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$eth=$_GET["nic"];
	$BUTTON=true;
	
	if(preg_match("#^tun#", $eth)){$BUTTON=false;}
	
	$nic=new system_nic($eth);
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort<>1){$jsSnort="DisableSnortInterface();";}
		$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
	
	}
	if(!$users->SNORT_INSTALLED){$jsSnort="DisableSnortInterface();";}
	$button="{apply}";
	if($_GET["button"]=="confirm"){$button="{button_i_confirm_nic}";}
	
	
	$boot=new boostrap_form();
	$boot->set_hidden("UseSnort", $snortInterfaces[$eth]);
	$boot->set_hidden("noreboot", $_GET["noreboot"]);
	$boot->set_hidden("save_nic", $eth);
	$boot->set_checkbox("enabled", "{enabled}", $nic->enabled,array("DISABLEALL"=>true));
	$boot->set_checkbox("dhcp", "{use_dhcp}", $nic->dhcp);
	$boot->set_field("IPADDR", "{tcp_address}", $nic->IPADDR,array("IPV4"=>true));
	$boot->set_field("NETMASK", "{netmask}", $nic->NETMASK,array("IPV4"=>true));
	$boot->set_field("GATEWAY", "{gateway}", $nic->GATEWAY,array("IPV4"=>true));
	$boot->set_field("DNS_1", "{primary_dns}", $nic->DNS1);
	$boot->set_field("DNS_2", "{secondary_dns}", $nic->DNS2);
	$boot->set_field("metric", "{metric}", $nic->metric);
	$boot->setAjaxPage("system.nic.edit.php");
	$boot->set_RefreshSearchs();
	
	
	if($BUTTON){$boot->set_button("{apply}");}else{
		$boot->set_form_locked();
	}
	$boot->set_PROTO("GET");
	if($DisableNetworksManagement==1){$boot->set_form_locked();}
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	$form=$boot->Compile();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($form);
}

function failover(){
	//this_feature_is_disabled_corp_license
	$users=new usersMenus();
	$boot=new boostrap_form();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();	
	$sock=new sockets();
	$eth=$_GET["nic"];
	$nic=new system_nic($eth);	
	for($i=1;$i<256;$i++){
		$ucarp_vids[$i]=$i;
	}
	$boot->set_hidden("save_nic", $eth);
	
	$array=unserialize(base64_decode($sock->getFrameWork("system.php?ucarp-status=$eth")));
	
	if(!isset($array["PID"])){
		$boot->set_formdescription("{status}:{stopped}");
		$boot->set_Newbutton("{start}", "Start$t()");
	}else{
		$boot->set_Newbutton("{stop}", "Stop$t()");
		$boot->set_formdescription("{status}:{running} PID:{$array["PID"]} {since} {$array["TIME"]}Mn");
	}
	
	$XHR=array();$XHR["start-vip"]="yes";
	$boot->set_AddScript("Start$t",array("XHR"=>$XHR));
	
	$XHR=array();$XHR["stop-vip"]="yes";
	$boot->set_AddScript("Stop$t",array("XHR"=>$XHR));	
	
	$boot->set_checkbox("ucarp_enabled", "{enabled}", $nic->ucarp_enabled,array("DISABLEALL"=>true));
	$boot->set_checkbox("ucarp_master", "{isamaster}", $nic->ucarp_master,array("TOOLTIP"=>"{ucarp_master_explain}"));
	$boot->set_list("ucarp_vid", "{ucarp-vid}",$ucarp_vids, $nic->ucarp_vid);
	$boot->set_field("ucarp_vip", "{ucarp-vip}", $nic->ucarp_vip,array("MANDATORY"=>true,"IPV4"=>true,"TOOLTIP"=>"{ucarp_vip_explain}"));
	$boot->set_list("ucarp_advskew", "{ucarp-advskew}", $ucarp_vids,$nic->ucarp_advskew);
	$boot->set_field("ucarp_advbase", "{interval} ({seconds})", $nic->ucarp_advbase,array("MANDATORY"=>true));
	$boot->set_button("{apply}");
	
	
	
	$boot->set_RefreshSearchs();
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
		$boot->set_form_locked();
	}
	
	$form=$boot->Compile();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($error.$form);	

}

function save_nic(){
	
	$eth=$_POST["save_nic"];
	if(preg_match("#^tun#", $eth)){
		echo "{$eth} cannot be edited manually...";
		return;
	}
	
	
	unset($_POST["save_nic"]);
	$nic=new system_nic($eth);
	
	if($_POST["ucarp_vip"]==$nic->IPADDR){
		echo "{$_POST["ucarp_vip"]} cannot be the same of the real interface !";
		return;
	}
	
	while (list ($key, $value) = each ($_POST) ){
		writelogs("$key = `$value`",__FUNCTION__,__FILE__,__LINE__);
		$nic->$key=$value;
		
	}
	$nic->NoReboot=true;
	$nic->SaveNic();
	$sock=new sockets();
	$sock->getFrameWork("system.php?ucarp-compile=yes");
	$sock->getFrameWork("chilli.php?restart=yes&nohup=yes");
	
	
	
	
}

function start_vip(){
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){return;}
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?ucarp-start-tenir=yes"));	
	
}
function stop_vip(){
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){return;}	
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?ucarp-stop-tenir=yes"));

}
