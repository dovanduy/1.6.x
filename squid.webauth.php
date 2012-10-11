<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["options"])){options();exit;}
	if(isset($_POST["EnableSplashScreen"])){SaveConfig();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{HotSpot}");
	$html="YahooWin2('700','$page?tabs=yes','$title')";
	echo $html;
}


function options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:18px'>{authentication}:<hr></td>
	<tr>
		<td class=legend style='font-size:16px'>{use_ldap_database}:</td>
		<td>". Field_checkbox("USELDAP", 1,$HotSpotConfig["USELDAP"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{use_dedicated_database}:</td>
		<td>". Field_checkbox("USEMYSQL", 1,$HotSpotConfig["USEMYSQL"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{verif_auth_each}:</td>
		<td style='font-size:16px'>". Field_text("CACHE_TIME",$HotSpotConfig["CACHE_TIME"],"font-size:16px;width:90px")."&nbsp;{seconds}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{re_authenticate_each}:</td>
		<td style='font-size:16px'>". Field_text("CACHE_AUTH",$HotSpotConfig["CACHE_AUTH"],"font-size:16px;width:90px")."&nbsp;{minutes}</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHostportConfig()")."</td>
	</tr>
	</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["popup"]='{service_parameters}';
	$array["options"]='{options}';
	
	
	$fontsize=14;
	if(count($array)>6){$fontsize=12.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=squid_hotspot style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#squid_hotspot').tabs();
			});
		</script>";		
	
	
}




function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
	if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$t=time();
	$html="
	<H1>Currently in dev, do not using it...</H1>
	<div id='$t-animate'></div>
	<div id='$t' class=explain style='font-size:14px'>{HotSpot_text}</div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{activate_hostpot}","{activate_hostpot_explain}",
		"EnableSplashScreen",$EnableSplashScreen,null,$width="450")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{redirect_to}:</td>
		<td>". Field_text("SplashScreenURI",$SplashScreenURI,"font-size:16px;")."
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:AnimateDiv('BodyContent');Loadjs('freeweb.php?in-front-ajax=yes&newinterface=yes');QuickLinkShow('quicklinks-section_freeweb');\"
		style='font-size:12px;text-decoration:underline'>&laquo;{use_freeweb_service}&raquo;</a></div>
		</td>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHotSpot()","16px")."</td>
	</tr>
	</table>
	<script>
	var x_SaveHotSpot= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('$t-animate').innerHTML='';
		RefreshTab('squid_hotspot');
	}


function SaveHotSpot(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
		var XHR = new XHRConnection();
		XHR.appendData('EnableSplashScreen',document.getElementById('EnableSplashScreen').value);
		XHR.appendData('SplashScreenURI',document.getElementById('SplashScreenURI').value);		
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot);
	}		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SaveConfig(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSplashScreen", $_POST["EnableSplashScreen"]);
	$sock->SET_INFO("SplashScreenURI", $_POST["SplashScreenURI"]);
	$sock->getFrameWork("squid.php?build-smooth=yes");
}

