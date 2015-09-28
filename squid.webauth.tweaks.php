<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.images.inc');


if(isset($_POST["SKIN_FONT_SIZE"])){Save();exit;}

page();


function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_FONT_SIZE"]="22px";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"]="32px";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]==null)){$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"]="Calibri, Candara, Segoe, Segoe UI, Optima, Arial, sans-serif";}
	if(trim($ArticaHotSpotSMTP["SKIN_FONT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_FONT_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"]="263849";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"]="5CB85C";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"]="398439";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"]="FFFFFF";}
	if(trim($ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"])==null){$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"]="47A447";}
	
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"]="485px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"]="221px";}
	if(trim($ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"]="10pt";}
	if(trim($ArticaHotSpotSMTP["SKIN_TEXT_LOGON"])==null){$ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_LINK_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_LINK_COLOR"]="000000";}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]=null;}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"]="15px";}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_COLOR"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_COLOR"]="FFFFFF";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"]="50";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"]="0";}
	if(!is_numeric($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"])){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"]="401";}
	if(trim($ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"])==null){$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"]="logo-hotspot.png";}
	
	$licenseerror=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	$license_error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
	$license=0;
	if($users->CORP_LICENSE){
		$button=button("{apply}","SaveHotSpot$t()",26);
		$license_error=null;
		$license=1;
	}

	
	
	$html="$license_error<div class=form style='width:98%'>
			<center style='background-color:#CCCCCC'><img src='img/{$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO"]}' style='margin:20px'></center>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{picture}/logo:</td>
			<td style='font-size:16px'>". button("{upload}", "Loadjs('squid.webauth.tweaks.upload.php')",26)."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{picture} {top_position}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_COMPANY_LOGO_TOP",$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_TOP"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{picture} {height}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_COMPANY_LOGO_HEIGHT",$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_HEIGHT"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{picture} {width}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_COMPANY_LOGO_WIDTH",$ArticaHotSpotSMTP["SKIN_COMPANY_LOGO_WIDTH"],"font-size:22px;width:100px")."</td>
		</tr>					
					
		<tr>
			<td class=legend style='font-size:22px'>{fontsize}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_FONT_SIZE",$ArticaHotSpotSMTP["SKIN_FONT_SIZE"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{links_color}:</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_LINK_COLOR",$ArticaHotSpotSMTP["SKIN_LINK_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{label} {fontsize}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_LABEL_FONT_SIZE",$ArticaHotSpotSMTP["SKIN_LABEL_FONT_SIZE"],"font-size:22px;width:100px")."</td>
		</tr>	
									
		<tr>
			<td class=legend style='font-size:22px'>{buttons_size}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_BUTTON_SIZE",$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{background_color} (button):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_BUTTON_BG_COLOR",$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{background_color} (button/Over):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_BUTTON_BG_COLOR_HOVER",$ArticaHotSpotSMTP["SKIN_BUTTON_BG_COLOR_HOVER"],"font-size:22px;width:100px")."</td>
		</tr>							
		<tr>
			<td class=legend style='font-size:22px'>{border_color} (button):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_BUTTON_BD_COLOR",$ArticaHotSpotSMTP["SKIN_BUTTON_BD_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{font_color} (button):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_BUTTON_TXT_COLOR",$ArticaHotSpotSMTP["SKIN_BUTTON_TXT_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>
	<tr>
		<td class=legend style='font-size:22px'>{logon_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SKIN_TEXT_LOGON'>{$ArticaHotSpotSMTP["SKIN_TEXT_LOGON"]}</textarea>
		</td>
	</tr>					
					
		<tr>
			<td class=legend style='font-size:22px'>{font_family}:</td>
			<td style='font-size:16px'>". Field_text("SKIN_FONT_FAMILY",$ArticaHotSpotSMTP["SKIN_FONT_FAMILY"],"font-size:22px;width:350px")."</td>
		</tr>											
		<tr>
			<td class=legend style='font-size:22px'>{font_color}:</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_FONT_COLOR",$ArticaHotSpotSMTP["SKIN_FONT_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{background_color}:</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_BACKGROUND_COLOR",$ArticaHotSpotSMTP["SKIN_BACKGROUND_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:22px'>{windows_content} ({width}):</td>
			<td style='font-size:16px'>". Field_text("SKIN_CONTENT_WIDTH",$ArticaHotSpotSMTP["SKIN_CONTENT_WIDTH"],"font-size:22px;width:150px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{windows_content} ({height}):</td>
			<td style='font-size:16px'>". Field_text("SKIN_CONTENT_HEIGHT",$ArticaHotSpotSMTP["SKIN_CONTENT_HEIGHT"],"font-size:22px;width:150px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{background_color} ({windows_content}):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_CONTENT_BG_COLOR",$ArticaHotSpotSMTP["SKIN_CONTENT_BG_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>
	<tr>
		<td class=legend style='font-size:22px'>{footer_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SKIN_COMPANY_NAME'>{$ArticaHotSpotSMTP["SKIN_COMPANY_NAME"]}</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{fontsize} ({footer_message}):</td>
		<td style='font-size:16px'>". Field_text("SKIN_COMPANY_NAME_FONT_SIZE",$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_SIZE"],"font-size:22px;width:100px")."</td>
	</tr>						
		<tr>
			<td class=legend style='font-size:22px'>{font_color} ({footer_message}):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_COMPANY_NAME_FONT_COLOR",$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_FONT_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{background_color} ({footer_message}):</td>
			<td style='font-size:16px'>". Field_ColorPicker("SKIN_COMPANY_NAME_BG_COLOR",$ArticaHotSpotSMTP["SKIN_COMPANY_NAME_BG_COLOR"],"font-size:22px;width:100px")."</td>
		</tr>					
					
		<tr>
			<td colspan=2 align='right'><hr>$button</td>
		</tr>					
	</table>
	</div>
<script>
	var x_SaveHotSpot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	

	
function SaveHotSpot$t(){
	var license=$license;
	if(license==0){ alert('$licenseerror');return;}
	var XHR = new XHRConnection();
	
	
	XHR.appendData('SKIN_FONT_SIZE',document.getElementById('SKIN_FONT_SIZE').value);
	XHR.appendData('SKIN_BUTTON_SIZE',document.getElementById('SKIN_BUTTON_SIZE').value);
	XHR.appendData('SKIN_FONT_FAMILY',document.getElementById('SKIN_FONT_FAMILY').value);
	XHR.appendData('SKIN_FONT_COLOR',document.getElementById('SKIN_FONT_COLOR').value);
	XHR.appendData('SKIN_BACKGROUND_COLOR',document.getElementById('SKIN_BACKGROUND_COLOR').value);
	XHR.appendData('SKIN_BUTTON_BG_COLOR',document.getElementById('SKIN_BUTTON_BG_COLOR').value);
	XHR.appendData('SKIN_BUTTON_BD_COLOR',document.getElementById('SKIN_BUTTON_BD_COLOR').value);
	XHR.appendData('SKIN_BUTTON_BG_COLOR_HOVER',document.getElementById('SKIN_BUTTON_BG_COLOR_HOVER').value);
	
	XHR.appendData('SKIN_CONTENT_WIDTH',document.getElementById('SKIN_CONTENT_WIDTH').value);
	XHR.appendData('SKIN_CONTENT_HEIGHT',document.getElementById('SKIN_CONTENT_HEIGHT').value);
	XHR.appendData('SKIN_CONTENT_BG_COLOR',document.getElementById('SKIN_CONTENT_BG_COLOR').value);
	XHR.appendData('SKIN_LABEL_FONT_SIZE',document.getElementById('SKIN_LABEL_FONT_SIZE').value);
	XHR.appendData('SKIN_TEXT_LOGON',document.getElementById('SKIN_TEXT_LOGON').value);
	XHR.appendData('SKIN_LINK_COLOR',document.getElementById('SKIN_LINK_COLOR').value);
	XHR.appendData('SKIN_COMPANY_NAME',document.getElementById('SKIN_COMPANY_NAME').value);
	XHR.appendData('SKIN_COMPANY_NAME_FONT_SIZE',document.getElementById('SKIN_COMPANY_NAME_FONT_SIZE').value);
	XHR.appendData('SKIN_COMPANY_NAME_FONT_COLOR',document.getElementById('SKIN_COMPANY_NAME_FONT_COLOR').value);
	XHR.appendData('SKIN_COMPANY_LOGO_TOP',document.getElementById('SKIN_COMPANY_LOGO_TOP').value);
	XHR.appendData('SKIN_COMPANY_LOGO_HEIGHT',document.getElementById('SKIN_COMPANY_LOGO_HEIGHT').value);
	XHR.appendData('SKIN_COMPANY_LOGO_WIDTH',document.getElementById('SKIN_COMPANY_LOGO_WIDTH').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
}


</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	
	$ArticaHotSpotSMTP=unserialize(base64_decode($sock->GET_INFO("ArticaHotSpotSMTP")));
	

	
	while (list ($num, $ligne) = each ($_POST) ){
		$ArticaHotSpotSMTP[$num]=utf8_encode($ligne);
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($ArticaHotSpotSMTP)), "ArticaHotSpotSMTP");
	
}
