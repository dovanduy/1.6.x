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
	$t=time();
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	
	if(!$users->CORP_LICENSE){

		if($ASWEB){
		$PAYPAL_TABLE["1 server 5 users"]="1 server 5 users : &99,00 EUR - annuel";
		$PAYPAL_TABLE["1 server < 10 users"]="1 server < 10 users : &200,00 EUR - annuel";
		$PAYPAL_TABLE["1 server 11 < 50 users"]="1 server 11 < 50 users : &600,00 EUR - annuel";
		$PAYPAL_TABLE["1 server  50 < 100"]="1 server  50 < 100 : &800,00 EUR - annuel";
		$PAYPAL_TABLE["1 server  100 < 2500 users"]="1 server  100 < 2500 users : &1 800,00 EUR - annuel";
		
		
		$paypalform="		
			<form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\" target=\"_top\">
			<input type=\"hidden\" name=\"cmd\" value=\"_s-xclick\">
			<input type=\"hidden\" name=\"hosted_button_id\" value=\"7VDNE34DF6MUU\">
			<div class=form style='width:95%'>
					
			
			<table style='width:100%'>
			<tr>
			 	<td class=legend>
					<input type=\"hidden\" name=\"on0\" value=\"Buy a License\" class=legend style='font-size:16px'>{buy_a_license}</td>
				<td >". Field_array_Hash($PAYPAL_TABLE, "os0","1 server 5 users","font-size:14px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>
						
						
				<input type=\"hidden\" name=\"on1\" value=\"Reseller\">{reseller_company}</td>
				<td>". Field_text("os1",null,'font-size:14px')."
				<input type=\"hidden\" name=\"on5\" value=\"Company Name\">
				<input type=\"hidden\" name=\"on4\" value=\"email\">
				
				<input type=\"hidden\" name=\"on2\" value=\"Computer serial\">
				<input type=\"hidden\" name=\"on3\" value=\"Unique License\">
				
				
				<input type=\"hidden\" name=\"os2\" value=\"$uuid\">
				<input type=\"hidden\" name=\"os3\" value=\"{$LicenseInfos["license_number"]}\">
				<input type=\"hidden\" name=\"os4\" value=\"{$LicenseInfos["EMAIL"]}\">
				<input type=\"hidden\" name=\"os5\" value=\"{$LicenseInfos["COMPANY"]}\">
							
						
						
				</td>
			</tr>
			<tr>
			<td colspan=2 align='right'>
			<hr>
			<input type=\"hidden\" name=\"currency_code\" value=\"EUR\">
			<input type=\"image\" style='border:0px' src=\"https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG_global.gif\" border=\"0\" name=\"submit\" alt=\"PayPal – The safer, easier way to pay online.\">
			<img alt=\"\" border=\"0\" src=\"https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif\" width=\"1\" height=\"1\">
			</td>
			</tr>				
							
			</table>				
			
			</form>
			</div>";
		}
		
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
	
	$unlocklick="<td>". Field_text("UNLOCKLIC-$t",$LicenseInfos["UNLOCKLIC"],"font-size:16px;width:240px")."</td>";

	
	if($LicenseInfos["license_status"]==null){
		$LicenseInfos["license_status"]="{waiting_registration}";
		$star="{explain_license_free}";
		$button_text="{request_a_quote}/{refresh}";
		$paypal=null;
	}else{
		$button_text="{update_the_request}";
		$star="{explain_license_order}";
		$paypal=$paypalform;
	}	
	
	if($LicenseInfos["license_status"]=="{license_active}"){
		$users->CORP_LICENSE=true;
		$unlocklick="<td style='font-size:16px;font-weight:bold'><input type='hidden' id='UNLOCKLIC-$t' value='{$LicenseInfos["UNLOCKLIC"]}'>{$LicenseInfos["UNLOCKLIC"]}</td>";
	}	
	
	if($users->CORP_LICENSE){$star=null;}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
	$last_access="	<tr>
		<td class=legend style='font-size:16px'>{last_update}:</td>
		<td style='font-size:16px'>$tt</td>
	</tr>";	
	}
	
	
	$CORP_LICENSE=0;
	$textcolor="black";
	$bt="<hr>".button($button_text,"RegisterSave$t()",18);
	if($users->CORP_LICENSE){
			$CORP_LICENSE=1;$bt=null;
			$textcolor="#23A83E";
	
	}

	
	
	
	$html="
	$explain
	$paypal
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
	</tr>
		<td class=legend style='font-size:16px'>{unlock_license}:</td>
		$unlocklick
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{license_status}:</td>
		<td style='font-size:16px;color:$textcolor'>{$LicenseInfos["license_status"]}</td>
	</tr>			
	<tr>
		<td colspan=2 align='right'>$bt</td>
	</tr>	
	</table>
	<div style='margin-top:15px'><i style='font-size:14px;font-weight:bold;color:#D91515'>*&nbsp;$star</i></div>
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
			XHR.appendData('UNLOCKLIC',document.getElementById('UNLOCKLIC-$t').value);
			XHR.appendData('REGISTER','1');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_RegisterSave$t);
		}
		
		function CheckCorpLic(){
			var lic=$CORP_LICENSE;
			if(lic==1){
				document.getElementById('COMPANY-$t').disabled=true;
				document.getElementById('EMAIL-$t').disabled=true;
				document.getElementById('EMPLOYEES-$t').disabled=true;
				
				
			}
		}
	CheckCorpLic();
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