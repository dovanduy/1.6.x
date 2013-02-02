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
	include_once('ressources/class.blackboxes.inc');
	
	if(isset($_GET["status"])){status_kerb();exit;}
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_POST["EnableKerbAuth"])){settingsSave();exit;}
	if(isset($_POST["SambeReconnectAD"])){SambeReconnectAD();exit;}
	if(isset($_GET["kerbchkconf"])){kerbchkconf();exit;}
	if(isset($_GET["test-popup"])){test_popup();exit;}
	if(isset($_GET["test-netadsinfo"])){test_netadsinfo();exit;}
	if(isset($_GET["test-netrpcinfo"])){test_netrpcinfo();exit;}
	if(isset($_GET["test-wbinfoalldom"])){test_wbinfoalldom();exit;}
	if(isset($_GET["test-wbinfomoinst"])){test_wbinfomoinst();exit;}
	if(isset($_GET["test-wbinfomoinsa"])){test_wbinfomoinsa();exit;}
	
	
	
	
js();


function status_kerb(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	
	
	$sock->getFrameWork("squid.php?ping-kdc=yes");
	$datas=unserialize(@file_get_contents("ressources/logs/kinit.array"));
	if(!is_array($datas)){
		echo "<script>LoadAjaxTiny('{$_GET["t"]}','squid.adker.php?status=yes&t=$t');</script>";
		return;
		
	}
	$img="img/error-24.png";
	$textcolor="#8A0D0D";
	$text=$datas["INFO"];
	if($datas["RESULTS"]){$img="img/ok24.png";$textcolor="black";}
	
	$html="<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%><img src='$img'></td>
		<td nowrap style='font-size:13px'><strong style='color:$textcolor'>Active Directory: $text</strong></td>
		<td width=1%>".imgtootltip("refresh-24.png","{refresh}","LoadAjaxTiny('{$_GET["t"]}','squid.adker.php?status=yes&t=$t');")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}



function test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$t=time();
	
	if($EnableKerbAuth==0){
		echo $tpl->_ENGINE_parse_body("<H2>{EnableWindowsAuthentication}: {disabled}</H2>");
		return;
	}
	
	$html="
	<div id='main-$t'>
	<table style='width:99%' class=form>
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
		<td colspan=2 align='right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('main_adker_tabs')")."</td>
	</tr>		
	</tbody>
	</table>
	<center>". button("{restart_connection}","SambeReconnectAD()",16)."</center>
	</div>
	<script>
		LoadAjaxTiny('$t-netadsinfo','$page?test-netadsinfo=yes&time=$t');
		
	var x_SambeReconnectAD= function (obj) {
		RefreshTab('main_adker_tabs');
	}		
	
		function SambeReconnectAD(){
			var XHR = new XHRConnection();
			XHR.appendData('SambeReconnectAD','yes');
			AnimateDiv('main-$t');
			XHR.sendAndLoad('$page', 'POST',x_SambeReconnectAD);
		
		}
		
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_netadsinfo(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
	$html="<hr>";
	$html=$html.test_results($datas);
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-netrpcinfo','$page?test-netrpcinfo=yes&time={$_GET["time"]}');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_netrpcinfo(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpcinfo=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfoalldom','$page?test-wbinfoalldom=yes&time={$_GET["time"]}');
	</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function test_wbinfoalldom(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
	$html=$html.test_results($datas);	
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinst','$page?test-wbinfomoinst=yes&time={$_GET["time"]}');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function test_wbinfomoinst(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	$html=$html."
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinsa','$page?test-wbinfomoinsa=yes&time={$_GET["time"]}');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_wbinfomoinsa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	$cmdline=base64_encode(serialize($AR));
		$html="<hr>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline")));
	$html=$html.test_results($datas);

	echo $tpl->_ENGINE_parse_body($html);	
	
}


function test_results($array){
	while (list ($num, $ligne) = each ($array) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color="black";
		
		
		if(preg_match("#online#", $ligne)){$color="#009809";}
		if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);}
		if(preg_match("#Could not#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#failed#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#_CANT_#i", $ligne)){$color="#D30F0F;font-weight:bold";}
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
	$node=new blackboxes($_GET["nodeid"]);
	$title=$tpl->_ENGINE_parse_body("{APP_SQUIDKERAUTH}");
	$html="YahooWin4(600,'$page?tabs=yes&nodeid={$_GET["nodeid"]}','$node->hostname::$title');";
	echo $html;
	}
	
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["popup"]='{service_parameters}';
	$array["test-popup"]='{analyze}';
	
	
	$fontsize=14;
	if(count($array)>6){$fontsize=12.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&nodeid={$_GET["nodeid"]}\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
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
$node=new blackboxes($_GET["nodeid"]);
$tpl=new templates();
	if($node->settings_inc["MSKTUTIL_INSTALLED"]<>1){
		echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'><div style='font-size:16px'>{error_missing_mskutil}</div></td>
		</tr>
		</table>
		");return;
	}
	if(strlen($node->settings_inc["squid_kerb_auth_path"])<2){
		echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'><div style='font-size:16px'>{error_missing_kerbauth}</div></td>
		</tr>
		</table>
		");return;
	}   


	$html="
	<div id='serverkerb-animated'></div>
	<div id='serverkerb-popup'></div>
	
	<script>
	function RefreshServerKerb(){
		LoadAjax('serverkerb-popup','$page?settings=yes&nodeid={$_GET["nodeid"]}');
		}
	
		RefreshServerKerb();
	</script>
	";
		
echo $html;		
}	
	
function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$severtype["WIN_2003"]="Windows 2003";
	$severtype["WIN_2008AES"]="Windows 2008 with AES";
	
	$intro="{APP_SQUIDKERAUTH_TEXT}<br>{APP_SQUIDKERAUTH_TEXT_REF}";
	if($_GET["switch-template"]=="samba"){$intro="{APP_SAMBAKERAUTH_TEXT}<br>{APP_SAMBAKERAUTH_TEXT_REF}";}
	
	
	$node=new blackboxes($_GET["nodeid"]);
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$configADSamba=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	$samba_installed=1;
	if(!$users->SAMBA_INSTALLED){$samba_installed=0;}
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1%><span id='kerbchkconf'></span>
		<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshAll()")."</div></td>
	<td valign='top' width=99%'>
		<div class=explain>$intro</div>
	</td>
	</table>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{EnableWindowsAuthentication}:</td>
		<td>". Field_checkbox("EnableKerbAuth",1,"$EnableKerbAuth","EnableKerbAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend>{WINDOWS_DNS_SUFFIX}:</td>
		<td>". Field_text("WINDOWS_DNS_SUFFIX",$array["WINDOWS_DNS_SUFFIX"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend>{WINDOWS_SERVER_NETBIOSNAME}:</td>
		<td>". Field_text("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:12px'>{ADNETBIOSDOMAIN}:</td>
		<td>". Field_text("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"],"font-size:14px;padding:3px;width:165px")."</td>
		<td>". help_icon("{howto_ADNETBIOSDOMAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:12px'>{ADNETIPADDR}:</td>
		<td>". field_ipv4("ADNETIPADDR",$array["ADNETIPADDR"],"font-size:14px")."</td>
		<td>". help_icon("{howto_ADNETIPADDR}")."</td>
	</tr>			
	<tr>
		<td class=legend>{WINDOWS_SERVER_TYPE}:</td>
		<td>". Field_array_Hash($severtype,"WINDOWS_SERVER_TYPE",$array["WINDOWS_SERVER_TYPE"],"style:font-size:14px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$array["WINDOWS_SERVER_ADMIN"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$array["WINDOWS_SERVER_PASS"],"font-size:14px;padding:3px;width:190px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveKERBProxy()",16)."</td>
	</tr>
	</table>
	
	<script>
		function EnableKerbAuthCheck(){
			var samba_installed=$samba_installed;
			document.getElementById('WINDOWS_DNS_SUFFIX').disabled=true;
			document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=true;
			document.getElementById('WINDOWS_SERVER_TYPE').disabled=true;
			document.getElementById('WINDOWS_SERVER_ADMIN').disabled=true;
			document.getElementById('WINDOWS_SERVER_PASS').disabled=true;
			document.getElementById('ADNETBIOSDOMAIN').disabled=true;
			document.getElementById('ADNETIPADDR').disabled=true;
			
			
			
			if(document.getElementById('EnableKerbAuth').checked){
				document.getElementById('WINDOWS_DNS_SUFFIX').disabled=false;
				document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=false;
				document.getElementById('WINDOWS_SERVER_TYPE').disabled=false;
				document.getElementById('WINDOWS_SERVER_ADMIN').disabled=false;
				document.getElementById('WINDOWS_SERVER_PASS').disabled=false;							
				document.getElementById('ADNETBIOSDOMAIN').disabled=false;
				document.getElementById('ADNETIPADDR').disabled=false;
			}
			
			if(document.getElementById('EnableKerbAuth').checked){
				if(samba_installed==1){
					document.getElementById('ADNETBIOSDOMAIN').disabled=false;
				}
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
	}		
	
		function SaveKERBProxy(){
			var pp=encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value);
			var XHR = new XHRConnection();
			if(document.getElementById('EnableKerbAuth').checked){XHR.appendData('EnableKerbAuth',1);}else{XHR.appendData('EnableKerbAuth',0);}
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
		
		
		EnableKerbAuthCheck();
		LoadAjax('kerbchkconf','$page?kerbchkconf=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function settingsSave(){
	$sock=new sockets();
	$users=new usersMenus();
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	
	
	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");	
	$MyhostnameTR=explode(".", $Myhostname);
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));
	if($MyDomain<>$_POST["WINDOWS_DNS_SUFFIX"]){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {WINDOWS_DNS_SUFFIX} {$_POST["WINDOWS_DNS_SUFFIX"]}\n{is_not_a_part_of} $Myhostname ($MyDomain)",1);
		return;
	}
	
	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	$resolved=gethostbyname($adhost);
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $resolved)){
		$tpl=new templates();
		if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
		echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} $adhost",1);
		return;	
	}
	
	
	
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
	$sock->getFrameWork("services.php?kerbauth=yes");
	if($users->SQUID_INSTALLED){$sock->getFrameWork("cmd.php?squid-rebuild=yes");}
	
	if($users->SAMBA_INSTALLED){
		$sock->getFrameWork("services.php?nsswitch=yes");
		$sock->getFrameWork("cmd.php?samba-reconfigure=yes");
	}
}


function SambeReconnectAD(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?kerbauth=yes");
	$sock->getFrameWork("services.php?nsswitch=yes");
	$sock->getFrameWork("cmd.php?samba-reconfigure=yes");	
}

function kerbchkconf(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	if(!$users->MSKTUTIL_INSTALLED){
		echo $tpl->_ENGINE_parse_body(Paragraphe32("APP_MSKTUTIL", "APP_MSKTUTIL_NOT_INSTALLED", "Loadjs('setup.index.php?js=yes');", "error-24.png"));
		return;
	}
	
	
	
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	if($users->SAMBA_INSTALLED){
		if($array["ADNETBIOSDOMAIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "ADNETBIOSDOMAIN", null, "error-24.png"));return;}
	}
	
	
	if($array["WINDOWS_DNS_SUFFIX"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_DNS_SUFFIX", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_NETBIOSNAME", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_TYPE"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_TYPE", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "administrator", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_PASS"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "password", null, "error-24.png"));return;}
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){echo $tpl->_ENGINE_parse_body(Paragraphe32("WINDOWS_NAME_SERVICE_NOT_KNOWN", "noacco:<strong style='font-size:12px'>$hostname</strong>", null, "error-24.png"));return;}
	
	
	
	
}