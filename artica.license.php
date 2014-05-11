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
	header("content-type: application/x-javascript");
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
	if($users->KASPERSKY_WEB_APPLIANCE){$ASWEB=true;}
	$titleprice="<strong>{start_99_euros}</strong><hr>";
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
					<input type=\"hidden\" name=\"on0\" value=\"Buy a License\" class=legend ><span style='font-size:16px'>{buy_a_license}:</span></td>
				<td >". Field_array_Hash($PAYPAL_TABLE, "os0","1 server 5 users",null,null,0,"font-size:16px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>
						
						
				<input type=\"hidden\" name=\"on1\" value=\"Reseller\">{reseller_company}:</td>
				<td>". Field_text("os1",null,'font-size:16px')."
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
		$explain="{CORP_LICENSE_EXPLAIN}<br><a href=\"http://www.artica.fr/proxy.comparative.php\" target=_new style='font-weight:bold;text-decoration:underline'>{click_here_comparative}</a>";
		$quotation="<div style='font-size:16px;font-weight:bold;margin-top:15px'>{price_quote}:</div>
			
					<a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=292','1024','900');\"
				style=\"font-size:14px;font-weight;bold;text-decoration:underline\">{click_here_price_quote}</a>
			</div>
		";
		}
	}
	
	$unlocklick="<td>". Field_text("UNLOCKLIC-$t",$LicenseInfos["UNLOCKLIC"],"font-size:16px;width:240px")."</td>";

	
	if($LicenseInfos["license_status"]==null){
		$step=1;
		$step_text="{ask_a_quote}";
		
		$LicenseInfos["license_status"]="{waiting_registration}";
		$star="{explain_license_free}";
		$button_text="{request_a_quote}/{refresh}";
		$paypal=null;
	}else{
		$step=2;
		$step_text="{waiting_order}";
		$button_text="{update_the_request}";
		$star="{explain_license_order}";
		$paypal=$paypalform;
	}	
	
	if($LicenseInfos["license_status"]=="{license_active}"){
		$users->CORP_LICENSE=true;
		$titleprice=null;
		$unlocklick="<td style='font-size:16px;font-weight:bold'><input type='hidden' id='UNLOCKLIC-$t' value='{$LicenseInfos["UNLOCKLIC"]}'>{$LicenseInfos["UNLOCKLIC"]}</td>";
	}	
	
	if($users->CORP_LICENSE){
		$star=null;$titleprice=null;
		$step=3;
		$step_text="{license_active}";
	}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
		$last_access="
		<tr>
			<td class=legend style='font-size:16px'>{last_update}:</td>
			<td style='font-size:16px'>{since} $tt</td>
		</tr>";	
	}
	
	if(trim($LicenseInfos["license_number"])<>null){
		
		$explain="{explain_license_order}";
		
	}
	
	
	$CORP_LICENSE=0;
	$textcolor="black";
	$bt="<hr>".button($button_text,"RegisterSave$t()",18);
	if($users->CORP_LICENSE){
		$CORP_LICENSE=1;$bt=null;
		$textcolor="#23A83E";
		$paypal=null;
	}

	if($explain<>null){
		$explain="<div style='font-size:14px' class=explain>$titleprice<br>$explain$quotation</div>";
	}	
	
	
	$html="
	
	$explain
	$paypal
	
	<div id='$t' ></div>
	<div  style='width:98%' class=form>
	<table >
<tr>
	<td class=legend style='font-size:16px'>{step}:</td>
	<td style='font-size:22px;font-weight:bold'>$step: $step_text<br></td>
</tr>	
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
</table></div>
	
	<script>
	var x_RegisterSave$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
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
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$LicenseInfos[$num]=$ligne;
		$WizardSavedSettings[$num]=$ligne;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?license-register=yes")));
	
}