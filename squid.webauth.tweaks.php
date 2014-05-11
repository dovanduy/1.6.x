<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.images.inc');
	
$GLOBALS["POLICY_DEFAULT"]="Company retains the right, at its sole discretion, to refuse new service to any individual, group, or business.
Company also reserves the right to monitor Internet access to its services by authorized users and clients, as part of the normal course of its business practice. 
Should Company discover users engaged in any violation of the Acceptable Use Policy, which create denial of access or impediment of service, and which adversely affect Company’s ability to provide services, Company reserves the right to temporarily suspend user access to the its Servers and/or database.  
Company shall make written/electronic notification to user’s point of contact of any temporary suspension, and the cause thereof, as soon as reasonably possible. 
This temporary suspension will remain in effect until all violations have ceased.  
Company also retains the right to discontinue service with 30 days’ prior written notice for repeated violation of the acceptable use policy.
";	

if(isset($_POST["EnableArticaHostPotBackground"])){Save();exit;}

page();


function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$EnableArticaHostPotBackground=$sock->GET_INFO("EnableArticaHostPotBackground");
	$ArticaHostPotBackgroundPositionH=$sock->GET_INFO("ArticaHostPotBackgroundPositionH");
	$ArticaHostPotBackgroundPositionV=$sock->GET_INFO("ArticaHostPotBackgroundPositionV");
	if(!is_numeric($EnableArticaHostPotBackground)){$EnableArticaHostPotBackground=1;}
	if(!is_numeric($ArticaHostPotBackgroundPositionH)){$ArticaHostPotBackgroundPositionH=5;}
	if(!is_numeric($ArticaHostPotBackgroundPositionV)){$ArticaHostPotBackgroundPositionV=5;}
	$ArticaHostPotBackgroundPath=$sock->GET_INFO("ArticaHostPotBackgroundPath");
	if($ArticaHostPotBackgroundPath==null){$ArticaHostPotBackgroundPath="logo-artica.png";}
	$licenseerror=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	$license=0;
	if($users->CORP_LICENSE){$license=1;}
	
	if(!is_file("img/reduced-$ArticaHostPotBackgroundPath")){
		
		$img=new images("img/$ArticaHostPotBackgroundPath");
		$img->thumbnail(256, 256, "img/reduced-$ArticaHostPotBackgroundPath");
	}
	
	$t=time();
	$html="<div class=form style='width:95%'>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>{enable_background_image}:</td>
			<td>". Field_checkbox("EnableArticaHostPotBackground", 1,$EnableArticaHostPotBackground,"EnableArticaHostPotBackgroundCheck();")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{background_image}:</td>
			<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.webauth.tweaks.upload.php');\"><img src='img/reduced-$ArticaHostPotBackgroundPath'></a></td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{horizontal_position}:</td>
			<td style='font-size:16px'>". Field_text("ArticaHostPotBackgroundPositionH",$ArticaHostPotBackgroundPositionH,"font-size:16px;width:100px")."&nbsp;%</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{vertical_position}:</td>
			<td style='font-size:16px'>". Field_text("ArticaHostPotBackgroundPositionV",$ArticaHostPotBackgroundPositionV,"font-size:16px;width:100px")."&nbsp;%</td>
		</tr>	
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveHotSpot$t()",18)."</td>
		</tr>					
	</table>
	</div>
<script>
	var x_SaveHotSpot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	
function EnableArticaHostPotBackgroundCheck(){
	EnableArticaHostPotBackground=0;
	if(document.getElementById('EnableArticaHostPotBackground').checked){EnableArticaHostPotBackground=1;}
	document.getElementById('ArticaHostPotBackgroundPositionV').disabled=true;
	document.getElementById('ArticaHostPotBackgroundPositionH').disabled=true;
	
	if(EnableArticaHostPotBackground==1){
		document.getElementById('ArticaHostPotBackgroundPositionV').disabled=false;
		document.getElementById('ArticaHostPotBackgroundPositionH').disabled=false;	
	}
}
	
function SaveHotSpot$t(){
	var license=$license;
	if(license==0){ alert('$licenseerror');return;}
	var XHR = new XHRConnection();
	EnableArticaHostPotBackground=0;
	if(document.getElementById('EnableArticaHostPotBackground').checked){EnableArticaHostPotBackground=1;}
	XHR.appendData('ArticaHostPotBackgroundPositionV',document.getElementById('ArticaHostPotBackgroundPositionV').value);
	XHR.appendData('ArticaHostPotBackgroundPositionH',document.getElementById('ArticaHostPotBackgroundPositionH').value);
	XHR.appendData('EnableArticaHostPotBackground',EnableArticaHostPotBackground);
	XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
}

EnableArticaHostPotBackgroundCheck();
</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaHostPotBackground", $_POST["EnableArticaHostPotBackground"]);
	$sock->SET_INFO("ArticaHostPotBackgroundPositionV", $_POST["ArticaHostPotBackgroundPositionV"]);
	$sock->SET_INFO("ArticaHostPotBackgroundPositionH", $_POST["ArticaHostPotBackgroundPositionH"]);
	
}
