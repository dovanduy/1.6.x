<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	if(isset($_GET["status"])){status_kerb();exit;}
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["ldap-params"])){ldap_params();exit;}
	if(isset($_GET["schedule-params"])){schedule_params();exit;}
	if(isset($_POST["AdSchBuildProxy"])){schedule_save();exit;}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_POST["EnableKerbAuth"])){settingsSave();exit;}
	if(isset($_POST["SambeReconnectAD"])){SambeReconnectAD();exit;}
	if(isset($_GET["kerbchkconf"])){kerbchkconf();exit;}
	if(isset($_GET["test-popup"])){test_popup();exit;}
	if(isset($_GET["test-nettestjoin"])){test_testjoin();exit;}
	if(isset($_GET["test-netadsinfo"])){test_netadsinfo();exit;}
	if(isset($_GET["test-netrpcinfo"])){test_netrpcinfo();exit;}
	if(isset($_GET["test-wbinfoalldom"])){test_wbinfoalldom();exit;}
	if(isset($_GET["test-wbinfomoinst"])){test_wbinfomoinst();exit;}
	if(isset($_GET["test-wbinfomoinsa"])){test_wbinfomoinsa();exit;}
	
	if(isset($_GET["test-auth"])){test_auth();exit;}
	if(isset($_POST["TESTAUTHUSER"])){test_auth_perform();exit;}
	if(isset($_POST["LDAP_SUFFIX"])){ldap_params_save();exit;}
	if(isset($_GET["test-popup-js"])){test_popup_js();exit;}
	if(isset($_GET["intro"])){intro();exit;}
	if(isset($_GET["join-js"])){join_js();exit;}
	if(isset($_GET["join-popup"])){join_popup();exit;}
	if(isset($_GET["join-perform"])){join_perform();exit;}
	
	if(isset($_GET["diconnect-js"])){diconnect_js();exit;}
	if(isset($_GET["disconnect-popup"])){diconnect_popup();exit;}
	if(isset($_GET["disconnect-perform"])){diconnect_perform();exit;}
	if(isset($_GET["DisableSquidBasicAuth-check"])){DisableSquidBasicAuth_save();exit;}
js();

function join_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{restart_connection}");
	echo "YahooWin6('905','$page?join-popup=yes','$title')";
	
}
function diconnect_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{disconnect}");
	echo "YahooWin6('905','$page?disconnect-popup=yes','$title')";
	
}
function join_perform(){
	$sock=new sockets();
	$users=new usersMenus();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?join-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
	</script>
	
	";	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_CONNECT");
	}
	
	echo $html;
}
function diconnect_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?disconnect-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
		RefreshTab('main_adker_tabs');
	</script>
	";

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_DISCONNECT");
	}	
	
	echo $html;	
}

function join_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?join-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function diconnect_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?disconnect-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function status_kerb(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	$tpl=new templates();
	$t=time();

	if($EnableKerbAuth==0){return;}
	writelogs("squid.php?ping-kdc=yes",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("squid.php?ping-kdc=yes");
	$datas=unserialize(@file_get_contents("ressources/logs/kinit.array"));
	
	if(count($datas)==0){
		echo "
		<script>LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');</script>";
		return;
		
	}
	
	$img="img/error-24.png";
	$textcolor="#8A0D0D";
	$text=$datas["INFO"];
	if(preg_match("#Authenticated to#is", $text)){
		$img="img/ok24.png";$textcolor="black";
	}
	
	
	if(trim($text)<>null){$text=": $text";}
	$html="
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1% valign='top'><img src='$img'></td>
		<td nowrap style='font-size:13px' valign='top'><strong style='color:$textcolor'>Active Directory $text</strong></td>
		<td width=1%>".imgtootltip("refresh-24.png","{refresh}","LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}



function test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$viaSmamba=null;
	$t=time();
	if(!isset($_GET["via-samba"])){
		if($EnableKerbAuth==0){
			echo $tpl->_ENGINE_parse_body("<H2>{EnableWindowsAuthentication}: {disabled}</H2>");
			return;
		}
		$reconnectJS="SambeReconnectAD();";
		
		
	}else{
		$viaSmamba="&via-samba=yes";
		$reconnectJS="SambbReconnectAD();";
	}
	
	$html="
	<div id='animate-$t'></div>
	<div id='main-$t'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>{is_connected}?:</td>
		<td width=99%><div id='$t-nettestjoin'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>Active Directory Infos:</td>
		<td width=99%><div id='$t-netadsinfo'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>RPC Infos:</td>
		<td width=99%><div id='$t-netrpcinfo'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>Domains:</td>
		<td width=99%><div id='$t-wbinfoalldom'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>Check shared secret:</td>
		<td width=99%><div id='$t-wbinfomoinst'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:13px' nowrap class=legend>NTLM Auth:</td>
		<td width=99%><div id='$t-wbinfomoinsa'></div></td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>". imgtootltip("refresh-24.png","{refresh}","StartAgain()")."</td>
	</tr>		
	</tbody>
	</table>
	<center>". button("{restart_connection}","$reconnectJS",16)."</center>
	</div>
	<script>
		function StartAgain(){
			LoadAjaxTiny('$t-nettestjoin','$page?test-nettestjoin=yes&time=$t$viaSmamba');
		}
		
	var x_SambeReconnectAD= function (obj) {
		RefreshTab('main_adker_tabs');
	}		
	
		function SambeReconnectAD(){
			var XHR = new XHRConnection();
			XHR.appendData('SambeReconnectAD','yes');
			AnimateDiv('main-$t');
			XHR.sendAndLoad('$page', 'POST',x_SambeReconnectAD);
		
		}
		
	var x_SambbReconnectAD= function (obj) {
		document.getElementById('animate-$t').innerHTML='';
		StartAgain();
	}			
		
		function SambbReconnectAD(){
			var XHR = new XHRConnection();
			XHR.appendData('SambeReconnectAD','yes');
			AnimateDiv('animate-$t');
			XHR.sendAndLoad('ad.connect.php', 'POST',x_SambbReconnectAD);		
		}
		
StartAgain();
		
		
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_netadsinfo(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
	$html="<hr>";
	$html=$html.test_results($datas);
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-netrpcinfo','$page?test-netrpcinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function test_testjoin(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpctestjoin=yes")));
	$html="<hr>";
	$html=$html.test_results($datas);
	$html=$html."
	<script>
	LoadAjaxTiny('{$_GET["time"]}-netadsinfo','$page?test-netadsinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_netrpcinfo(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}

	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpcinfo=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfoalldom','$page?test-wbinfoalldom=yes&time={$_GET["time"]}$viaSmamba');
	</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function test_wbinfoalldom(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
	$html=$html.test_results($datas);	
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinst','$page?test-wbinfomoinst=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function test_wbinfomoinst(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinsa','$page?test-wbinfomoinsa=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_wbinfomoinsa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
		$SambaWinbindUseDefaultDomain=$sock->GET_INFO("SambaWinbindUseDefaultDomain");
		if(!is_numeric($SambaWinbindUseDefaultDomain)){$SambaWinbindUseDefaultDomain=0;}
		if($SambaWinbindUseDefaultDomain==0){$AR["WORKGROUP"]=$array["WORKGROUP"];}
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline$viaSmamba")));
	$html=$html.test_results($datas);

	echo $tpl->_ENGINE_parse_body($html);	
	
}


function test_results($array){
	while (list ($num, $ligne) = each ($array) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color="black";
		
		if(preg_match("#is OK#", $ligne)){$color="#009809;font-weight:bold";}
		if(preg_match("#online#", $ligne)){$color="#009809";}
		if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);}
		if(preg_match("#Could not#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#failed#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#_CANT_#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#succeeded#i", $ligne)){$color="#009809;font-weight:bold";}
		if($color=="black"){
			if(preg_match("#^(.+?):\s+(.+)#", $ligne,$re)){$ligne="<span style='color:#656060;font-weight:bold'>{$re[1]}:&nbsp;</span><span style='color:#009809;font-weight:bold'>{$re[2]}</span>";}
		}
		$html=$html."<div style='font-size:11px;color:$color'>$ligne</div>";
	}	
	return $html;
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUIDKERAUTH}");
	$html="YahooWin4(650,'$page?tabs=yes','$title');";
	echo $html;
	}
	
function test_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{analyze}");
	if(isset($_GET["via-samba"])){$e="&via-samba=yes";}
	$html="YahooWin6(600,'$page?test-popup=yes$e','$title');";
	echo $html;	
	
	
}
	
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["popup"]='{service_parameters}';
	$array["test-popup"]='{analyze}';
	$array["test-auth"]='{test_auth}';
	
	
	$fontsize=14;
	if(count($array)>6){$fontsize=12.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_adker_tabs style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_adker_tabs').tabs();
			});
		</script>";		
	
	
}
	
function popup(){
$page=CurrentPageName();
$users=new usersMenus();
$sock=new sockets();

$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	


$tpl=new templates();
	if(!$users->MSKTUTIL_INSTALLED){
		echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'>
				<div style='font-size:16px'>{error_missing_mskutil}<br>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('setup.index.progress.php?product=APP_MSKTUTIL&start-install=yes')\" style='font-size:16px;text-decoration:underline'>{install}</a></div>
			</td>
		</tr>
		</table>
		");return;
	}
	if($EnableWebProxyStatsAppliance==0){
		if(strlen($users->squid_kerb_auth_path)<2){
			echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'><div style='font-size:16px'>{error_missing_kerbauth}</div></td>
		</tr>
		</table>
		");return;
		}   
	}

	$html="
	<div id='serverkerb-animated'></div>
	<div id='serverkerb-popup'></div>
	
	<script>
	function RefreshServerKerb(){
		LoadAjax('serverkerb-popup','$page?settings=yes');
		}
	
		RefreshServerKerb();
	</script>
	";
		
echo $html;		
}	

function intro(){
	
	$tpl=new templates();
	$intro="{APP_SQUIDKERAUTH_TEXT}<br>{APP_SQUIDKERAUTH_TEXT_REF}";
	if($_GET["switch-template"]=="samba"){$intro="{APP_SAMBAKERAUTH_TEXT}<br>{APP_SAMBAKERAUTH_TEXT_REF}";}	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>$intro</div>");
}
	
function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$sock=new sockets();
	$severtype["WIN_2003"]="Windows 2003";
	$severtype["WIN_2008AES"]="Windows 2008 with AES";
	$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$schedule_parameters=$tpl->javascript_parse_text("{schedule_parameters}");
	$disconnect=$tpl->_ENGINE_parse_body("{disconnect}");
	$samba36=0;
	if(preg_match("#^3\.6\.#", $samba_version)){$samba36=1;}
	

	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$configADSamba=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	$LockKerberosAuthentication=$sock->GET_INFO("LockKerberosAuthentication");
	$KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	$DisableSilentNTLM=$sock->GET_INFO("DisableSilentNTLM");
	$DisableSquidBasicAuth=$sock->GET_INFO("DisableSquidBasicAuth");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	
	$NTPDATE_INSTALLED=0;
	if($users->NTPDATE){$NTPDATE_INSTALLED=1;}
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	if(!is_numeric($KerbAuthDisableNsswitch)){$KerbAuthDisableNsswitch=0;}
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($DisableSilentNTLM)){$DisableSilentNTLM=0;}
	if(!is_numeric($DisableSquidBasicAuth)){$DisableSquidBasicAuth=1;}
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
	if(!is_numeric("$LockKerberosAuthentication")){$LockKerberosAuthentication=1;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$samba_installed=1;
	if(!$users->SAMBA_INSTALLED){$samba_installed=0;}
	
	if(!isset($array["SAMBA_BACKEND"])){$array["SAMBA_BACKEND"]="tdb";}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($array["COMPUTER_BRANCH"]==null){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($samba36==1){$arrayBCK["autorid"]="autorid";}
	$arrayBCK["ad"]="ad";
	$arrayBCK["rid"]="rid";
	$arrayBCK["tdb"]="tdb";
	if($LockKerberosAuthentication==1){$EnableKerberosAuthentication=0;}
	$DisableSquidBasicAuth_error=$tpl->javascript_parse_text("{DisableSquidBasicAuth_error}");
	
	if($EnableKerbAuth==1){
		$disconnectTR="
		<tr>
			<td width=1%><img src='img/stop-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:Loadjs('$page?diconnect-js=yes')\" 
					style='font-size:14px;text-decoration:underline'>$disconnect</a>
				</td>
		</tr>";
	}
	
	if($samba_installed==0){
		
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{samba_is_not_installed}"));
		return;
	}
	
	if($DisableSilentNTLM==1){
		$DisableSilentNTLM_text="<div class=explain style='font-size:14px'>{DisableSilentNTLM_explain}</div>";
		
	}
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' width=50%><span id='kerbchkconf'></span>
		<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshAll()")."</div></td>
	<td valign='top' width=50%'>
		<table style='width:50%'>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap><a href=\"javascript:blur();\" 
			OnClick=\"javascript:YahooWinBrowse('550','$page?intro=yes&switch-template={$_GET["switch-template"]}','$about_this_section');\" 
			style='font-size:14px;text-decoration:underline'>{about_this_section}</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap><a href=\"javascript:blur();\" 
			OnClick=\"javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=170','1024','900');\" 
			style='font-size:14px;text-decoration:underline'>{online_help}</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:YahooSearchUser('702','$page?ldap-params=yes','$ldap_parameters');\" 
					style='font-size:14px;text-decoration:underline'>$ldap_parameters</a>
				</td>
		</tr>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:YahooSearchUser('550','$page?schedule-params=yes','$schedule_parameters');\" 
					style='font-size:14px;text-decoration:underline'>$schedule_parameters</a>
				</td>
		</tr>	
		$disconnectTR	
		</table>		
	</td>
	</table>
	$DisableSilentNTLM_text
	<div style='width:95%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{EnableWindowsAuthentication}:</td>
		<td>". Field_checkbox("EnableKerbAuth",1,"$EnableKerbAuth","EnableKerbAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{DisableSilentNTLM}:</td>
		<td>". Field_checkbox("DisableSilentNTLM",1,"$DisableSilentNTLM","DisableSquidBasicAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px' nowrap>{DisableSquidBasicAuth}:<div id='DisableSquidBasicAuth-anim'></div></td>
		<td>". Field_checkbox("DisableSquidBasicAuth",1,"$DisableSquidBasicAuth","DisableSquidBasicAuthCheck()")."</td>
		<td></td>
	</tr>

				
	<tr>
		<td class=legend style='font-size:14px'>{KerbAuthDisableNsswitch}:</td>
		<td>". Field_checkbox("KerbAuthDisableNsswitch",1,"$KerbAuthDisableNsswitch")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{KerbAuthTrusted}:</td>
		<td>". Field_checkbox("KerbAuthTrusted",1,"$KerbAuthTrusted")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px' nowrap>{KerbAuthDisableGroupListing}:</td>
		<td>". Field_checkbox("KerbAuthDisableGroupListing",1,"$KerbAuthDisableGroupListing")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{KerbAuthDisableNormalizeName}:</td>
		<td>". Field_checkbox("KerbAuthDisableNormalizeName",1,"$KerbAuthDisableNormalizeName")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px' nowrap>{map_untrusted_to_domain}:</td>
		<td>". Field_checkbox("KerbAuthMapUntrustedDomain",1,"$KerbAuthMapUntrustedDomain")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{synchronize_time_with_ad}:</td>
		<td>". Field_checkbox("NtpdateAD",1,"$NtpdateAD")."</td>
		<td>&nbsp;</td>
	</tr>									
	<tr>
		<td class=legend style='font-size:14px'>{authenticate_from_kerberos}:</td>
		<td>". Field_checkbox("EnableKerberosAuthentication",1,"$EnableKerberosAuthentication","EnableKerbAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{WINDOWS_DNS_SUFFIX}:</td>
		<td>". Field_text("WINDOWS_DNS_SUFFIX",$array["WINDOWS_DNS_SUFFIX"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{WINDOWS_SERVER_NETBIOSNAME}:</td>
		<td>". Field_text("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{ADNETBIOSDOMAIN}:</td>
		<td>". Field_text("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"],"font-size:14px;padding:3px;width:165px")."</td>
		<td>". help_icon("{howto_ADNETBIOSDOMAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ADNETIPADDR}:</td>
		<td>". field_ipv4("ADNETIPADDR",$array["ADNETIPADDR"],"font-size:14px")."</td>
		<td>". help_icon("{howto_ADNETIPADDR}")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{WINDOWS_SERVER_TYPE}:</td>
		<td>". Field_array_Hash($severtype,"WINDOWS_SERVER_TYPE",$array["WINDOWS_SERVER_TYPE"],"style:font-size:14px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{COMPUTERS_BRANCH}:</td>
		<td>". Field_text("COMPUTER_BRANCH",$array["COMPUTER_BRANCH"],"font-size:14px;padding:3px;width:165px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{database_backend}:</td>
			<td>". Field_array_Hash($arrayBCK,"SAMBA_BACKEND",$array["SAMBA_BACKEND"],"style:font-size:14px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$array["WINDOWS_SERVER_ADMIN"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$array["WINDOWS_SERVER_PASS"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveKERBProxy()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		function EnableKerbAuthCheck(){
			var EnableKerbAuth=0;
			var EnableKerberosAuthentication=$EnableKerberosAuthentication;
			var LockKerberosAuthentication=$LockKerberosAuthentication;
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			var NTPDATE_INSTALLED=$NTPDATE_INSTALLED;
			var samba_installed=$samba_installed;
			document.getElementById('WINDOWS_DNS_SUFFIX').disabled=true;
			document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=true;
			document.getElementById('WINDOWS_SERVER_TYPE').disabled=true;
			document.getElementById('WINDOWS_SERVER_ADMIN').disabled=true;
			document.getElementById('WINDOWS_SERVER_PASS').disabled=true;
			document.getElementById('ADNETBIOSDOMAIN').disabled=true;
			document.getElementById('ADNETIPADDR').disabled=true;
			document.getElementById('SAMBA_BACKEND').disabled=true;
			document.getElementById('COMPUTER_BRANCH').disabled=true;
			document.getElementById('KerbAuthDisableNsswitch').disabled=true;
			document.getElementById('KerbAuthDisableGroupListing').disabled=true;
			document.getElementById('KerbAuthDisableNormalizeName').disabled=true;
			document.getElementById('KerbAuthMapUntrustedDomain').disabled=true;
			document.getElementById('NtpdateAD').disabled=true;
			document.getElementById('DisableSilentNTLM').disabled=true;
			
			
			
			document.getElementById('KerbAuthTrusted').disabled=true;
			if(document.getElementById('EnableKerbAuth').checked){EnableKerbAuth=1;}
			
			
			if(LockKerberosAuthentication==0){
				if(document.getElementById('EnableKerberosAuthentication').checked){
					EnableKerbAuth=1;
					EnableKerberosAuthentication=1;
				}
			}
			
			
			if(EnableRemoteStatisticsAppliance==1){
				document.getElementById('EnableKerbAuth').disabled=true;
			}
			
			if(EnableKerbAuth==1){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('WINDOWS_DNS_SUFFIX').disabled=false;
					document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=false;
					document.getElementById('WINDOWS_SERVER_TYPE').disabled=false;
					document.getElementById('WINDOWS_SERVER_ADMIN').disabled=false;
					document.getElementById('WINDOWS_SERVER_PASS').disabled=false;							
					document.getElementById('ADNETBIOSDOMAIN').disabled=false;
					document.getElementById('ADNETIPADDR').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
					document.getElementById('COMPUTER_BRANCH').disabled=false;
					document.getElementById('KerbAuthDisableNsswitch').disabled=false;
					document.getElementById('KerbAuthDisableGroupListing').disabled=false;
					document.getElementById('KerbAuthDisableNormalizeName').disabled=false;
					document.getElementById('KerbAuthMapUntrustedDomain').disabled=false;
					document.getElementById('KerbAuthTrusted').disabled=false;
					document.getElementById('DisableSilentNTLM').disabled=false;
					
					
					if(NTPDATE_INSTALLED==1){
						document.getElementById('NtpdateAD').disabled=false;
					
					}
					
					if(document.getElementById('EnableKerberosAuthentication').checked){
						document.getElementById('EnableKerbAuth').checked=false
						document.getElementById('EnableKerbAuth').disabled=true;
						document.getElementById('SAMBA_BACKEND').disabled=true;
					}else{
						document.getElementById('EnableKerbAuth').disabled=false;
						document.getElementById('SAMBA_BACKEND').disabled=false;
					}
					if(document.getElementById('EnableKerbAuth').checked){
						document.getElementById('EnableKerberosAuthentication').checked=false;
						document.getElementById('EnableKerberosAuthentication').disabled=true;
						document.getElementById('SAMBA_BACKEND').disabled=false;
					}else{
						document.getElementById('EnableKerberosAuthentication').disabled=false;
					}
				}			
			}
			
			if(document.getElementById('EnableKerbAuth').checked){
				if(samba_installed==1){
					if(EnableRemoteStatisticsAppliance==0){
						document.getElementById('ADNETBIOSDOMAIN').disabled=false;
					}
				}
			}
			
			if(!document.getElementById('EnableKerberosAuthentication').checked){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('EnableKerbAuth').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
				}
				
			}
			if(document.getElementById('EnableKerbAuth').checked){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('EnableKerberosAuthentication').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
				}
			}
			
			if(samba_installed==0){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('SAMBA_BACKEND').disabled=true;
					document.getElementById('EnableKerbAuth').disabled=true;
				}
			}
			
			if(LockKerberosAuthentication==1){
				document.getElementById('EnableKerberosAuthentication').disabled=true;
			}
			
			
		}
		
		function RefreshAll(){
			RefreshServerKerb();
		}
		
	var x_SaveKERBProxy= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('serverkerb-animated').innerHTML='';return;}
		RefreshServerKerb();
		document.getElementById('serverkerb-animated').innerHTML='';
		if(document.getElementById('AdSquidStatusLeft')){RefreshDansguardianMainService();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		Loadjs('squid.ad.progress.php');
	}		
	
		function SaveKERBProxy(){
			DisableSquidBasicAuthCheck();
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			if(EnableRemoteStatisticsAppliance==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
			var pp=encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value);
			var XHR = new XHRConnection();
			if(document.getElementById('EnableKerbAuth').checked){XHR.appendData('EnableKerbAuth',1);}else{XHR.appendData('EnableKerbAuth',0);}
			if(document.getElementById('EnableKerberosAuthentication').checked){XHR.appendData('EnableKerberosAuthentication',1);}else{XHR.appendData('EnableKerberosAuthentication',0);}
			if(document.getElementById('KerbAuthDisableNsswitch').checked){XHR.appendData('KerbAuthDisableNsswitch',1);}else{XHR.appendData('KerbAuthDisableNsswitch',0);}
			if(document.getElementById('KerbAuthDisableGroupListing').checked){XHR.appendData('KerbAuthDisableGroupListing',1);}else{XHR.appendData('KerbAuthDisableGroupListing',0);}
			if(document.getElementById('KerbAuthDisableNormalizeName').checked){XHR.appendData('KerbAuthDisableNormalizeName',1);}else{XHR.appendData('KerbAuthDisableNormalizeName',0);}
			if(document.getElementById('KerbAuthTrusted').checked){XHR.appendData('KerbAuthTrusted',1);}else{XHR.appendData('KerbAuthTrusted',0);}
			if(document.getElementById('KerbAuthMapUntrustedDomain').checked){XHR.appendData('KerbAuthMapUntrustedDomain',1);}else{XHR.appendData('KerbAuthMapUntrustedDomain',0);}
			if(document.getElementById('NtpdateAD').checked){XHR.appendData('NtpdateAD',1);}else{XHR.appendData('NtpdateAD',0);}
			if(document.getElementById('DisableSilentNTLM').checked){XHR.appendData('DisableSilentNTLM',1);}else{XHR.appendData('DisableSilentNTLM',0);}
			if(document.getElementById('DisableSquidBasicAuth').checked){XHR.appendData('DisableSquidBasicAuth',1);}else{XHR.appendData('DisableSquidBasicAuth',0);}
			
			
			
			XHR.appendData('COMPUTER_BRANCH',document.getElementById('COMPUTER_BRANCH').value);
			XHR.appendData('SAMBA_BACKEND',document.getElementById('SAMBA_BACKEND').value);
			XHR.appendData('WINDOWS_DNS_SUFFIX',document.getElementById('WINDOWS_DNS_SUFFIX').value);
			XHR.appendData('WINDOWS_SERVER_NETBIOSNAME',document.getElementById('WINDOWS_SERVER_NETBIOSNAME').value);
			XHR.appendData('WINDOWS_SERVER_TYPE',document.getElementById('WINDOWS_SERVER_TYPE').value);
			XHR.appendData('WINDOWS_SERVER_ADMIN',document.getElementById('WINDOWS_SERVER_ADMIN').value);
			XHR.appendData('WINDOWS_SERVER_PASS',pp);
			XHR.appendData('ADNETBIOSDOMAIN',document.getElementById('ADNETBIOSDOMAIN').value);
			XHR.appendData('ADNETIPADDR',document.getElementById('ADNETIPADDR').value);
			AnimateDiv('serverkerb-animated');
			XHR.sendAndLoad('$page', 'POST',x_SaveKERBProxy);
		
		}
		
		function DisableSquidBasicAuthCheck(){
			var DisableSilentNTLM=0;
			var DisableSquidBasicAuth=0;
			if(document.getElementById('DisableSquidBasicAuth').checked){DisableSquidBasicAuth=1;}
			if(document.getElementById('DisableSilentNTLM').checked){DisableSilentNTLM=1;}
			if(DisableSilentNTLM==1){
				if(DisableSquidBasicAuth==1){
					alert('$DisableSquidBasicAuth_error');
					document.getElementById('DisableSilentNTLM').checked=false;
					document.getElementById('DisableSquidBasicAuth').checked=false;
					return;
				}
				
			}
			
			LoadAjaxTiny('DisableSquidBasicAuth-anim','$page?DisableSquidBasicAuth-check=yes&value='+DisableSquidBasicAuth);
				
		}		
		
		
		EnableKerbAuthCheck();
		LoadAjax('kerbchkconf','$page?kerbchkconf=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function DisableSquidBasicAuth_save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidBasicAuth", $_GET["value"]);
	$tpl=new templates();
	if($_GET["value"]==1){$enabled="{enabled}";}else{$enabled="{disabled}";}
	echo $tpl->_ENGINE_parse_body("<strong style='font-size:14px;font-weight:bold'>{success}:$enabled</strong>...");
}

function ldap_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$active=new ActiveDirectory();
	$sock=new sockets();
	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$t=time();
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
	if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
	if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
	if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
	if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
	if(!is_numeric($array["LDAP_RECURSIVE"])){$array["LDAP_RECURSIVE"]=0;}
	$html="
	<div id='serverkerb-$t'></div>
	<div class=explain style='font-size:14px' nowrap>{ldap_ntlm_parameters_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{use_dynamic_groups_acls}:</td>
		<td>". Field_checkbox("UseDynamicGroupsAcls",1,$UseDynamicGroupsAcls,"UseDynamicGroupsAclsCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{TTL_CACHE}:</td>
		<td style='font-size:14px'>". Field_text("DynamicGroupsAclsTTL",$DynamicGroupsAclsTTL,"font-size:14px;padding:3px;width:90px")."&nbsp;{seconds}</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:14px' nowrap>{non_ntlm_domain}:</td>
		<td>". Field_text("LDAP_NONTLM_DOMAIN",$array["LDAP_NONTLM_DOMAIN"],"font-size:14px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". Field_text("LDAP_SERVER",$array["LDAP_SERVER"],"font-size:14px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT",$array["LDAP_PORT"],"font-size:14px;padding:3px;width:90px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX",$array["LDAP_SUFFIX"],"font-size:14px;padding:3px;width:310px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN",$array["LDAP_DN"],"font-size:12px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$t",$array["LDAP_PASSWORD"],"font-size:14px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{recursive}:</td>
		<td>". Field_checkbox("LDAP_RECURSIVE-$t",1,$array["LDAP_RECURSIVE"])."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveLDAPADker()",16)."</td>
	</tr>
	</table>
<script>
	var x_SaveLDAPADker= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('serverkerb-$t').innerHTML='';return;}
		document.getElementById('serverkerb-$t').innerHTML='';
		YahooSearchUserHide();
		if(document.getElementById('main_dansguardian_mainrules')){
			RefreshTab('main_dansguardian_mainrules');
		}
	}		
	
		function SaveLDAPADker(){
			var UseDynamicGroupsAcls=0;
			var pp=encodeURIComponent(document.getElementById('LDAP_PASSWORD-$t').value);
			var XHR = new XHRConnection();
			if(document.getElementById('UseDynamicGroupsAcls').checked){UseDynamicGroupsAcls=1;}
			XHR.appendData('UseDynamicGroupsAcls',UseDynamicGroupsAcls);
			XHR.appendData('DynamicGroupsAclsTTL',document.getElementById('DynamicGroupsAclsTTL').value);
			
			XHR.appendData('LDAP_NONTLM_DOMAIN',document.getElementById('LDAP_NONTLM_DOMAIN').value);
			XHR.appendData('LDAP_SERVER',document.getElementById('LDAP_SERVER').value);
			XHR.appendData('LDAP_PORT',document.getElementById('LDAP_PORT').value);
			XHR.appendData('LDAP_SUFFIX',document.getElementById('LDAP_SUFFIX').value);
			XHR.appendData('LDAP_DN',document.getElementById('LDAP_DN').value);
			if(document.getElementById('LDAP_RECURSIVE-$t').checked){XHR.appendData('LDAP_RECURSIVE',1);}else{XHR.appendData('LDAP_RECURSIVE',0);}
			XHR.appendData('LDAP_PASSWORD',pp);
			AnimateDiv('serverkerb-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveLDAPADker);
		
		}
		
		function UseDynamicGroupsAclsCheck(){
			document.getElementById('DynamicGroupsAclsTTL').disabled=true;
			if(document.getElementById('UseDynamicGroupsAcls').checked){
				document.getElementById('DynamicGroupsAclsTTL').disabled=false;
			}
		}
		UseDynamicGroupsAclsCheck();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function ldap_params_save(){
	$sock=new sockets();
	$tpl=new templates();
	$sock->SET_INFO("DynamicGroupsAclsTTL", $_POST["DynamicGroupsAclsTTL"]);
	$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($_POST["UseDynamicGroupsAcls"]==1){
		if($_POST["LDAP_SERVER"]==null){echo $tpl->javascript_parse_text("LDAP: {hostname} Not set\n");return;}
		if(!is_numeric($_POST["LDAP_PORT"])){echo $tpl->javascript_parse_text("LDAP: {ldap_port} Not set\n");return;}			
		if($_POST["LDAP_SUFFIX"]==null){echo $tpl->javascript_parse_text("LDAP: {suffix} Not set\n");return;}
		if($_POST["LDAP_DN"]==null){echo $tpl->javascript_parse_text("LDAP: {bind_dn} Not set\n");return;}
		if($_POST["LDAP_PASSWORD"]==null){echo $tpl->javascript_parse_text("LDAP: {password} Not set\n");return;}		
		
	}
	
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){
		$array[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
	
	$ldap_connection=@ldap_connect($_POST["LDAP_SERVER"],$_POST["LDAP_PORT"]);
	if(!$ldap_connection){
		echo "Connection Failed to connect to DC ldap://{$_POST["LDAP_SERVER"]}:{$_POST["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}		
		@ldap_close();
		return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $_POST["LDAP_DN"],$_POST["LDAP_PASSWORD"]);
	if(!$bind){
		
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}
		
		echo "Failed to login to DC {$_POST["LDAP_SERVER"]} - {$_POST["LDAP_DN"]} \n`$error`";
		return false;
	}	
	
	
	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("BUILDCONF");
		return;
	}
	
	
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
}

function settingsSave(){
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	unset($_SESSION["EnableKerbAuth"]);
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	
	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");	
	$MyhostnameTR=explode(".", $Myhostname);
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));
	if($MyDomain<>$_POST["WINDOWS_DNS_SUFFIX"]){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SET_INFO("EnableKerberosAuthentication", 0);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {WINDOWS_DNS_SUFFIX} {$_POST["WINDOWS_DNS_SUFFIX"]}\n{is_not_a_part_of} $Myhostname ($MyDomain)",1);
		return;
	}
	
	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	$resolved=gethostbyname($adhost);
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $resolved)){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SET_INFO("EnableKerberosAuthentication", 0);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} $adhost",1);
		return;	
	}
	
	
	
	$sock->SET_INFO("KerbAuthDisableNormalizeName", $_POST["KerbAuthDisableNormalizeName"]);
	$sock->SET_INFO("EnableKerberosAuthentication", $_POST["EnableKerberosAuthentication"]);
	$sock->SET_INFO("KerbAuthDisableNsswitch", $_POST["KerbAuthDisableNsswitch"]);
	$sock->SET_INFO("KerbAuthDisableGroupListing", $_POST["KerbAuthDisableGroupListing"]);
	$sock->SET_INFO("KerbAuthTrusted", $_POST["KerbAuthTrusted"]);
	$sock->SET_INFO("KerbAuthMapUntrustedDomain", $_POST["KerbAuthMapUntrustedDomain"]);
	$sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);
	$sock->SET_INFO("DisableSilentNTLM", $_POST["DisableSilentNTLM"]);
	$sock->SET_INFO("DisableSquidBasicAuth", $_POST["DisableSquidBasicAuth"]);
	
	
	
	if($_POST["EnableKerberosAuthentication"]==1){$sock->SET_INFO("EnableKerbAuth", 0);}
	
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){$ArrayKerbAuthInfos[$num]=$ligne;}	
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	if(strpos($_POST["ADNETBIOSDOMAIN"], ".")>0){
		echo "The netbios domain \"{$_POST["ADNETBIOSDOMAIN"]}\" is invalid.\n";
		$sock->SET_INFO("EnableKerbAuth", 0);
		return;
	}
	
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	
}


function SambeReconnectAD(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	if(!is_numeric($EnableKerberosAuthentication)){$EnableKerberosAuthentication=0;}
	$sock->getFrameWork("services.php?kerbauth=yes");
	if($EnableKerberosAuthentication==0){
		$sock->getFrameWork("services.php?nsswitch=yes");
		$sock->getFrameWork("cmd.php?samba-reconfigure=yes");
	}

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("WINBIND_RECONFIGURE");
	}	
	
}

function test_auth(){
	include_once(dirname(__FILE__)."ressources/class.system.network.inc");
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr=null;}
	$squid=new squidbee();
	$port=$squid->listen_port;
	$t=time();
	if($SquidBinIpaddr==null){
		$ip=new networking();
		$ips=$ip->ALL_IPS_GET_ARRAY();	
		while (list ($num, $ligne) = each ($ips) ){if($num==null){continue;}if($num=="127.0.0.1"){continue;}$net[]=$num;}
		$SquidBinIpaddr=$net[0];
	}
	
	if (!extension_loaded('curl')) {echo "<H2>Fatal curl extension not loaded</H2>";die();}

	$html="
	<div id='test-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{proxy}:</td>
		<td style='font-size:16px'>$SquidBinIpaddr:$port</td>
	</tr>
	<tr>
	<tr>
		<td class=legend  style='font-size:16px'>{username}:</td>
		<td>". Field_text("TESTAUTHUSER",$array["WINDOWS_SERVER_ADMIN"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>		
	<tr>
		<td class=legend  style='font-size:16px'>{password}:</td>
		<td>". Field_password("TESTAUTHPASS",$array["WINDOWS_SERVER_PASS"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>". button("{submit}","TestAuthPerform()",18)."</td>
	</tr>
	</table>
	
	<script>
	var x_TestAuthPerform= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('test-$t').innerHTML='';
			
		}		
		
		function TestAuthPerform(){
			var pp=encodeURIComponent(document.getElementById('TESTAUTHPASS').value);
			var XHR = new XHRConnection();
			
			XHR.appendData('TESTAUTHUSER',document.getElementById('TESTAUTHUSER').value);
			XHR.appendData('TESTPROXYIP','$SquidBinIpaddr');
			XHR.appendData('TESTPROXYPORT','$port');
			XHR.appendData('TESTAUTHPASS',pp);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_TestAuthPerform);
		
		}
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_auth_perform(){
	$tpl=new templates();
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$SquidBinIpaddr=$_POST["TESTPROXYIP"];
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
	$port=$_POST["TESTPROXYPORT"];
	$TESTAUTHPASS=url_decode_special_tool($_POST["TESTAUTHPASS"]);
	$TESTAUTHUSER=stripslashes($_POST["TESTAUTHUSER"]);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_INTERFACE,$SquidBinIpaddr);
	curl_setopt($ch, CURLOPT_URL, "http://www.google.com");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
	curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
	curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
	curl_setopt ($ch, CURLOPT_PROXY,"$SquidBinIpaddr:$port");
	curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
	
	
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $TESTAUTHUSER.':'.$TESTAUTHPASS);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));		
	curl_setopt($ch, CURLOPT_NOBODY, true);
	$data=curl_exec($ch);
	
	if(preg_match("#X-Squid-Error:.*?([A-Z\_]+)#is", $data,$re)){echo "****  FAILED WITH ERROR \"{$re[1]}\" ***\n\n";}
	if(preg_match("#Proxy-Authenticate: NTLM\s+(.*?)\s+#",$data,$re)){$data=str_replace($re[1], "***", $data);}
	$error=curl_errno($ch);		
	$curl=new ccurl(null);
	
	if(!$curl->ParseError($error)){
		echo $error_text=$tpl->javascript_parse_text($curl->error)."\n";
	}
	$info = curl_getinfo($ch);
	curl_close($ch);	
	if(is_array($info)){
		while (list ($num, $ligne) = each ($info) ){
			$infos[]="$num: $ligne";
		}
	}
	$sep="\n------------------------------------------------------\n";
	echo "http://www.google.com return error $error$sep Datas:$sep$data\nInfos:$sep".@implode("\n", $infos);
}


function kerbchkconf(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$users=new usersMenus();
	if($users->SAMBA_INSTALLED){
		$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
		echo $tpl->_ENGINE_parse_body("<center><div style='font-size:14px'>{APP_SAMBA}:$samba_version</div></center>");
	}else{
		echo $tpl->_ENGINE_parse_body("<center><div style='font-size:14px'>{APP_SAMBA}: {NOT_INSTALLED}</div></center>");
	}
	
	
	if(!$users->MSKTUTIL_INSTALLED){echo $tpl->_ENGINE_parse_body(Paragraphe32("APP_MSKTUTIL", "APP_MSKTUTIL_NOT_INSTALLED", "Loadjs('setup.index.php?js=yes');", "error-24.png"));return;}
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	if($users->SAMBA_INSTALLED){if($array["ADNETBIOSDOMAIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "ADNETBIOSDOMAIN", null, "error-24.png"));return;}}
	
	
	if($array["WINDOWS_DNS_SUFFIX"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_DNS_SUFFIX", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_NETBIOSNAME", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_TYPE"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_TYPE", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "administrator", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_PASS"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "password", null, "error-24.png"));return;}
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){echo $tpl->_ENGINE_parse_body(Paragraphe32("WINDOWS_NAME_SERVICE_NOT_KNOWN", "noacco:<strong style='font-size:12px'>$hostname</strong>", null, "error-24.png"));return;}
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if($EnableKerbAuth==1){
		$page=CurrentPageName();
		echo $tpl->_ENGINE_parse_body("<center style='margin:5px'>".button("{restart_connection}",
		 "Loadjs('$page?join-js=yes')","14px")."</center>");
	}
	
	
}

function schedule_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	
	$AdSchBuildProxy=$sock->GET_INFO("AdSchBuildProxy");
	$AdSchBuildUfdb=$sock->GET_INFO("AdSchBuildUfdb");
	$AdSchRestartSquid=$sock->GET_INFO("AdSchRestartSquid");
	if(!is_numeric($AdSchBuildProxy)){$AdSchBuildProxy=0;}
	if(!is_numeric($AdSchBuildUfdb)){$AdSchBuildUfdb=0;}
	if(!is_numeric($AdSchRestartSquid)){$AdSchRestartSquid=0;}
	$html="<div class='explain' style='font-size:14px'>
	{ad_kerb_schedule_explain}
	</div>
	<div id='test-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{build_proxy_parameters}:</td>
		<td>". Field_checkbox("AdSchBuildProxy", 1,$AdSchBuildProxy)."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{build_web_filtering_rules}:</td>
		<td>". Field_checkbox("AdSchBuildUfdb", 1,$AdSchBuildUfdb)."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{restart_the_web_proxy_service}:</td>
		<td>". Field_checkbox("AdSchRestartSquid", 1,$AdSchRestartSquid)."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "AdSchBuildProxy$t()","16px")."</td>
	</tr>
	</table>
	<script>
	var x_AdSchBuildProxy$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('test-$t').innerHTML='';
			
		}		
		
		function AdSchBuildProxy$t(){
			var XHR = new XHRConnection();
			AdSchBuildProxy=0;
			AdSchBuildUfdb=0;
			AdSchRestartSquid=0;
			if(document.getElementById('AdSchBuildProxy').checked){AdSchBuildProxy=1;}
			if(document.getElementById('AdSchBuildUfdb').checked){AdSchBuildUfdb=1;}
			if(document.getElementById('AdSchRestartSquid').checked){AdSchRestartSquid=1;}
			
			XHR.appendData('AdSchRestartSquid',AdSchRestartSquid);
			XHR.appendData('AdSchBuildUfdb',AdSchBuildUfdb);
			XHR.appendData('AdSchBuildProxy',AdSchBuildProxy);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_AdSchBuildProxy$t);
		
		}		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function schedule_save(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO("$num",$ligne);
	}
}

