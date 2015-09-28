<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.artica.graphs.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){ $tpl=new templates(); header("content-type: application/x-javascript");echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');"; die();exit(); }

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_POST["REGISTER"])){step1_register();exit;}
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{kav4proxy_license}");
	$html="YahooWin3('700','$page?popup=yes','$title',true)";
	echo $html;
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:95%' id='div-$t' class=form></div>
	<script>LoadAjax('div-$t','$page?step1=yes&t=$t',true);</script>		
			
	";
	
	echo $html;
}

function step1(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	$license_kav4Proxy_temp=$WizardSavedSettings["license_kav4Proxy_temp"];
	
	$KasperskyAskQuote=$sock->GET_INFO("KasperskyAskQuote");
	if(!is_numeric($KasperskyAskQuote)){$KasperskyAskQuote=0;}
	$KasperskyAskQuoteResults=$sock->GET_INFO("KasperskyAskQuoteResults");
	$t=$_GET["t"];
	$explain="
	<div style='font-size:14px' class=explain>{KAV4PROXY_STEP1_EXPLAIN}</div>";
	
	$bt=button("{submit}","RegisterSave$t()",18);
	
	if($KasperskyAskQuote==0){$step=1;}
	if($license_kav4Proxy_temp<>null){
		
		$tempserial="
		<tr>
			<td class=legend style='font-size:16px'>{temp_serial}:</td>
			<td style='font-size:16px'>$license_kav4Proxy_temp</td>
		</tr>";
		$bt=button("{refresh}","RegisterSave$t()",18);
	}
	
	if($KasperskyAskQuoteResults<>null){
		$colorOK="#666F68";
		if($KasperskyAskQuoteResults=="KEY_OK"){$colorOK="#0C982A";}
		
		$KasperskyAskQuoteResults_text="<tr>
		<td class=legend style='font-size:16px'>{registration_message}:</td>
		<td style='font-size:16px;color:$colorOK'>{{$KasperskyAskQuoteResults}}</td>
	</tr>";
		
	}
	
	$html="
$explain
<table>
<tr>
	<td class=legend style='font-size:22px;font-weight:bold'>{step}:</td>
	<td style='font-size:22px;font-weight:bold'>$step: {ask_a_quote}</td>
</tr>
$KasperskyAskQuoteResults_text
<tr>
	<td class=legend style='font-size:16px'>{uuid}:</td>
	<td style='font-size:16px'>$uuid</td>
</tr>
$tempserial
<tr>
	<td class=legend style='font-size:16px'>{company}:</td>
	<td>". Field_text("COMPANY-$t",$LicenseInfos["COMPANY"],"font-size:16px;width:240px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:16px'>{your_email_address}:</td>
	<td>". Field_text("EMAIL-$t",$LicenseInfos["EMAIL"],"font-size:16px;width:240px")."</td>
	</tr>
</tr>
	<td class=legend style='font-size:16px'>{nb_employees}:</td>
	<td>". Field_text("EMPLOYEES-$t",$LicenseInfos["EMPLOYEES"],"font-size:16px;width:80px")."</td>
	</tr>
<tr>
	<td colspan=2 align='right'>$bt</td>
</tr>
</table>
<script>
var x_RegisterSave$t= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById('div-$t').innerHTML='';
	YahooWin3Hide();
	Loadjs('$page');
	if(tempvalue.length>3){alert(tempvalue);return;}
	CacheOff();
}
	
function RegisterSave$t(){
	var XHR = new XHRConnection();
	XHR.appendData('COMPANY',document.getElementById('COMPANY-$t').value);
	XHR.appendData('EMAIL',document.getElementById('EMAIL-$t').value);
	XHR.appendData('EMPLOYEES',document.getElementById('EMPLOYEES-$t').value);
	XHR.appendData('REGISTER','1');
	AnimateDiv('div-$t');
	XHR.sendAndLoad('$page', 'POST',x_RegisterSave$t);
}
	
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function step1_register(){
	$sock=new sockets();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$WizardSavedSettings[$num]=$ligne;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
	$sock->SET_INFO("KasperskyAskQuote", 1);
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?kaspersky-license-register=yes")));
	
}
