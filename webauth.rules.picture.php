<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.wifidog.settings.inc');
include_once('ressources/class.wifidog.templates.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["ruleid"])){Save();exit;}

Page();
function Page(){
	$ruleid=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$this_feature_is_disabled_corp_license=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	$CORP=0;
	if($users->CORP_LICENSE){$CORP=1;}
	
	$sock=new wifidog_settings($ruleid);
	$wifidog_templates=new wifidog_templates($ruleid);
	
	$users=new usersMenus();
	$CORP=0;
	if($users->CORP_LICENSE){$CORP=1;}
	
	$BACK_REPEAT["no-repeat"]="no-repeat";
	$BACK_REPEAT["repeat-y"]="repeat-y";
	$BACK_REPEAT["repeat-x"]="repeat-x";
	$BACK_REPEAT["repeat"]="repeat";
	

	
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='width:500px;'>
		<div style='width:500px;height:500px;border-radius:5px 5px 5px 5px;\n-moz-border-radius:5px;
		-webkit-border-radius:5px;background-repeat: $wifidog_templates->BackgroundRepeat;background-position: {$wifidog_templates->BackgroundTOP}% {$wifidog_templates->BackgroundBottom}%;
		background-image:url(\"$wifidog_templates->BackgroundPicturePath\");background-color:#$wifidog_templates->backgroundColor'>&nbsp;</div>
		</td>
		<td valign='top'>
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:22px'>{picture}:</td>
					<td style='font-size:16px'>". button("{upload}", "Loadjs('webauth.rules.picture.upload.php?ruleid=$ruleid')",26)."</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:22px'>{top_position}:</td>
					<td style='font-size:22px'>". Field_text("BackgroundTOP-$t",$wifidog_templates->BackgroundTOP,"font-size:22px;width:100px")."%</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{bottom_position}:</td>
					<td style='font-size:22px'>". Field_text("BackgroundBottom-$t",$wifidog_templates->BackgroundBottom,"font-size:22px;width:100px")."%</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{repeat}",null).":</td>
					<td style='font-size:22px'>". Field_array_Hash($BACK_REPEAT,"BackgroundRepeat-$t",$wifidog_templates->BackgroundRepeat,null,null,0,"font-size:22px")."</td>
				</tr>
					<tr>
						<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","42px")."</td>
					</tr>							
			</table>
		</td>
	</tr>
	</table>
	</td>
	</table>
	</div>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#HOSTPOT_RULES').flexReload();
		RefreshTab('HOTSPOT_TAB');
	}	
	
	function Save$t(){
		var CORP=$CORP;
		if(CORP==0){alert('$this_feature_is_disabled_corp_license');return;}		
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('BackgroundTOP',encodeURIComponent(document.getElementById('BackgroundTOP-$t').value));
		XHR.appendData('BackgroundBottom',encodeURIComponent(document.getElementById('BackgroundBottom-$t').value));
		XHR.appendData('BackgroundRepeat',encodeURIComponent(document.getElementById('BackgroundRepeat-$t').value));		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}
function Save(){
	$sock=new wifidog_settings($_POST["ruleid"]);
	unset($_POST["ruleid"]);
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$sock->SET_INFO($key, $value);

	}

}