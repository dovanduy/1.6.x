<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
$usersmenus=new usersMenus();



if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["REGISTER"])){REGISTER();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{artica_license}");
	$html="YahooWin3('650','$page?popup=yes','$title')";
	echo $html;
}
function popup(){
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
	
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	
	if(!$users->CORP_LICENSE){
		
	if($ASWEB){
		$explain="<div style='font-size:14px' class=explain>{CORP_LICENSE_EXPLAIN}</div>";
		$quotation="
		<div class=explain>
			<div style='font-size:16px;font-weight:bold'>{price_quote}:</div>
			<div>
					<a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=292','1024','900');\"
				style=\"font-size:14px;font-weight;bold;text-decoration:underline\">{click_here_price_quote}</a>
			</div>
		</div>";
		}
	}

	
	if($LicenseInfos["license_status"]==null){
		$LicenseInfos["license_status"]="{waiting_registration}";
		$star="{explain_license_free}";
		$button_text="{request_a_quote}";
	}else{
		$button_text="{update_the_request}";
		$star="{explain_license_order}";
	}	
	
	if($users->CORP_LICENSE){$star=null;}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
	$last_access="	<tr>
		<td class=legend style='font-size:16px'>{last_update}:</td>
		<td style='font-size:16px'>$tt</td>
	</tr>";	
	}
	
	$t=time();
	$html="
	$explain
	$quotation
	<div id='$t'></div>
	<table style='width:99%' class=form>
	$last_access
	<tr>
		<td class=legend style='font-size:16px'>{uuid}:</td>
		<td style='font-size:16px'>$uuid</td>
	</tr>
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
		<td class=legend style='font-size:16px'>{license_number}:</td>
		<td style='font-size:16px'>{$LicenseInfos["license_number"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{license_status}:</td>
		<td style='font-size:16px'>{$LicenseInfos["license_status"]}</td>
	</tr>			
	<tr>
		<td colspan=2 align='right'><hr>". button($button_text,"RegisterSave$t()",18)."</td>
	</tr>	
	</table>
	<div style='margin-top:15px'><i style='font-size:12px'>*&nbsp;$star</i></div>
	<script>
	var x_RegisterSave$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue);return;}
		Loadjs('$page');
		CacheOff();
		}	
	
		function RegisterSave$t(){
			var XHR = new XHRConnection();
			XHR.appendData('COMPANY',document.getElementById('COMPANY-$t').value);
			XHR.appendData('EMAIL',document.getElementById('EMAIL-$t').value);
			XHR.appendData('EMPLOYEES',document.getElementById('EMPLOYEES-$t').value);
			XHR.appendData('REGISTER','1');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_RegisterSave$t);
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function REGISTER(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	while (list ($num, $ligne) = each ($_POST) ){
		$LicenseInfos[$num]=$ligne;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?license-register=yes")));
	
}