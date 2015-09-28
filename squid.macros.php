<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "<script>alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');</script>";
		die();exit();
	}
	if(isset($_POST["AllowSquidDropBox"])){Save();exit;}
	
page();

function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
	$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
	$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
	$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
	$AllowSquidOtherProtocols=intval($sock->GET_INFO("AllowSquidOtherProtocols"));
	$AllowSquidHSTS=intval($sock->GET_INFO("AllowSquidHSTS"));
	$SquidAllowSmartPhones=intval($sock->GET_INFO("SquidAllowSmartPhones"));
	
	$MobilesPhones=Paragraphe_switch_img("{AllowSmartphonesRuleText}", "{AllowSmartphonesRuleExplain}",
			"SquidAllowSmartPhones",
			$SquidAllowSmartPhones,null,1400);
	
	$DropBox=Paragraphe_switch_img("{AllowSquidDropBox}", "{AllowSquidDropBox_explain}","AllowSquidDropBox",
			$AllowSquidDropBox,null,1400);
	
	$skype=Paragraphe_switch_img("{AllowSquidSkype}", "{AllowSquidSkype_explain}","AllowSquidSkype",
			$AllowSquidSkype,null,1400);	
	
	$office=Paragraphe_switch_img("{AllowSquidOffice365}", "{AllowSquidOffice365_explain}","AllowSquidOffice365",
			$AllowSquidOffice365,null,1400);
	
	$Google=Paragraphe_switch_img("{AllowSquidGoogle}", "{AllowSquidGoogle_explain}","AllowSquidGoogle",
			$AllowSquidGoogle,null,1400);
	
	
	
	$QuicProto=Paragraphe_switch_img("{AllowSquidOtherProtocols}", "{AllowSquidOtherProtocols_explain}","AllowSquidOtherProtocols",
			$AllowSquidOtherProtocols,null,1400);
	
	$HSTS=Paragraphe_switch_img("{AllowSquidHSTS}", "{AllowSquidHSTS_explain}","AllowSquidHSTS",
			$AllowSquidHSTS,null,1400);
	
	
	$html="<div style='width:98%' class=form>
	$MobilesPhones
	<p>&nbsp</p>	
	$DropBox
	<p>&nbsp</p>	
	$skype	
	<p>&nbsp</p>		
	$office
	<p>&nbsp</p>		
	$Google	
	<p>&nbsp</p>		
	$QuicProto	
	<p>&nbsp</p>		
	$HSTS			
	<div style='width:100%;text-align:right'><hr>".button("{apply}","Save$t()",40)."</div>
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	RefreshTab('main_dansguardian_tabs');
	Loadjs('squid.reconfigure.php');
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('AllowSquidDropBox',  document.getElementById('AllowSquidDropBox').value);
	XHR.appendData('AllowSquidSkype',  document.getElementById('AllowSquidSkype').value);
	XHR.appendData('AllowSquidOffice365',  document.getElementById('AllowSquidOffice365').value);
	XHR.appendData('AllowSquidGoogle',  document.getElementById('AllowSquidGoogle').value);
	XHR.appendData('SquidAllowSmartPhones',  document.getElementById('SquidAllowSmartPhones').value);
	XHR.appendData('AllowSquidHSTS',  document.getElementById('AllowSquidHSTS').value);
	XHR.appendData('AllowSquidOtherProtocols',  document.getElementById('AllowSquidOtherProtocols').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}		
</script>
	
	
	";
echo $tpl->_ENGINE_parse_body($html);	
	
}

function Save(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num, $ligne);
	}
}
