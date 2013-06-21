<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
$GLOBALS["POLICY_DEFAULT"]="Company retains the right, at its sole discretion, to refuse new service to any individual, group, or business.
Company also reserves the right to monitor Internet access to its services by authorized users and clients, as part of the normal course of its business practice. 
Should Company discover users engaged in any violation of the Acceptable Use Policy, which create denial of access or impediment of service, and which adversely affect Company’s ability to provide services, Company reserves the right to temporarily suspend user access to the its Servers and/or database.  
Company shall make written/electronic notification to user’s point of contact of any temporary suspension, and the cause thereof, as soon as reasonably possible. 
This temporary suspension will remain in effect until all violations have ceased.  
Company also retains the right to discontinue service with 30 days’ prior written notice for repeated violation of the acceptable use policy.
";	
	
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
	if(isset($_GET["terme-of-use"])){echo terme_of_use();exit;}
	if(isset($_POST["USETERMSTEXT"])){xSaveOptions();exit;}
	if(isset($_POST["USELDAP"])){xSaveOptions();exit;}
	if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
	if(isset($_GET["radius"])){radius_config();exit;}
	if(isset($_POST["RAD_SERVER"])){xSaveOptions();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$YahooWin=2;
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	$title=$tpl->_ENGINE_parse_body("{HotSpot}");
	$html="
	var YahooWinx=$YahooWin;
	if(YahooWinx==2){
		YahooWin2Hide();
		YahooWin6Hide();
	}	
	YahooWin$YahooWin('700','$page?tabs=yes$YahooWinUri','$title')";
	echo $html;
}


function options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	if($HotSpotConfig["USETERMSLABEL"]==null){$HotSpotConfig["USETERMSLABEL"]="I agree to terms";}
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	
	$lockAd=0;
	if($EnableKerbAuth==0){
		$lockAd=1;
		$exp[]="<li style='font-weight:bold'>{not_connected_to_active_directory}</li>";
	}
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$lockAd=1;
		$exp[]="<li style='font-weight:bold'>{license_inactive}</li>";		
	}
	
	if($lockAd==1){
		$explainNotAd="<div class=explain>{ad_database_is_disabled_because}".@implode("<br>-", $exp)."</div>";
	}
	
	$html="
	<div id='$t-animate'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=3 style='font-size:18px'>{authentication}:<hr></td>
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{use_ldap_database}:</td>
		<td>". Field_checkbox("USELDAP", 1,$HotSpotConfig["USELDAP"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{use_dedicated_database}:</td>
		<td>". Field_checkbox("USEMYSQL", 1,$HotSpotConfig["USEMYSQL"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{use_active_directory}:</td>
		<td>". Field_checkbox("USEAD-$t", 1,$HotSpotConfig["USEAD"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin3('600','$page?radius=yes','{use_radius}');\"
		style='font-size:16px;text-decoration:underline'>{use_radius}</a>:</td>
		<td>". Field_checkbox("USERAD-$t", 1,$HotSpotConfig["USERAD"])."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin3('600','$page?terme-of-use=yes','{use_terme_of_use}');\"
		style='font-size:16px;text-decoration:underline'>{use_terme_of_use}</a>:</td>
		<td>". Field_checkbox("USETERMS", 1,$HotSpotConfig["USETERMS"])."</td>
		<td>&nbsp;</td>
	</tr>
			
				
				
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{label}</a>:</td>
		<td style='font-size:16px'>". Field_text("USETERMSLABEL",$HotSpotConfig["USETERMSLABEL"],
		"font-size:16px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{verif_auth_each}:</td>
		<td style='font-size:16px'>". Field_text("CACHE_TIME",$HotSpotConfig["CACHE_TIME"],"font-size:16px;width:90px")."&nbsp;{seconds}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;text-transform:capitalize'>{re_authenticate_each} ({default}):</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"CACHE_AUTH",$HotSpotConfig["CACHE_AUTH"],null,null,0,"font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveHostportConfig()","18px")."</td>
	</tr>
	</table>
	$explainNotAd
	<script>
		function LockAd$t(){
			var lock=$lockAd;
			if(lock==1){
				document.getElementById('USEAD-$t').disabled=true;
				document.getElementById('USEAD-$t').checked=false;
			}
		}
		
	var x_SaveHostportConfig$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('squid.compile.progress.php?ask=yes');
		document.getElementById('$t-animate').innerHTML='';
		
		
	}		
		
		
		function SaveHostportConfig(){
			var lock=$lockAd;
			var USELDAP=0;
			var USEMYSQL=0;
			var USETERMS=0;
			var USERAD=0;
			var USEAD=0;
			var XHR = new XHRConnection();
			if(lock==0){
				if(document.getElementById('USEAD-$t').checked){
					USEAD=1;
				}
			}
			
			if(document.getElementById('USELDAP').checked){USELDAP=1;}
			if(document.getElementById('USEMYSQL').checked){USEMYSQL=1;}
			if(document.getElementById('USETERMS').checked){USETERMS=1;}
			if(document.getElementById('USERAD-$t').checked){USERAD=1;}
			XHR.appendData('USEAD',USEAD);
			XHR.appendData('USERAD',USERAD);
			XHR.appendData('USELDAP',USELDAP);
			XHR.appendData('USEMYSQL',USEMYSQL);
			XHR.appendData('USETERMS',USETERMS);
			XHR.appendData('USETERMSLABEL',document.getElementById('USETERMSLABEL').value);
			XHR.appendData('CACHE_TIME',document.getElementById('CACHE_TIME').value);
			XHR.appendData('CACHE_AUTH',document.getElementById('CACHE_AUTH').value);
			AnimateDiv('$t-animate');
			XHR.sendAndLoad('$page', 'POST',x_SaveHostportConfig$t);	
		}
		
	LockAd$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function xSaveOptions(){
	
	if(isset($_POST["RAD_PASSWORD"])){$_POST["RAD_PASSWORD"]=url_decode_special_tool($_POST["RAD_PASSWORD"]);}
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
	while (list ($num, $ligne) = each ($_POST) ){	
		$HotSpotConfig[$num]=$ligne;
		
	}
	
	$NewHotSpotConfig=base64_encode(serialize($HotSpotConfig));
	$sock->SaveConfigFile($NewHotSpotConfig, "HotSpotConfig");
	$sock->getFrameWork("squid.php?build-smooth=yes");
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	if($users->APP_CHILLI_INSTALLED){
		$proto="https";
		if($_SERVER["HTTPS"]<>"on"){
			$proto="http";
		}
		
		$uri="<a href=\"javascript:blur();\" OnClick=\"javascript:document.location.href='logoff.php?goto=miniadm.logon.php';\"
		style='text-decoration:underline;font-size:16px'>$proto://{$_SERVER["HTTP_HOST"]}/miniadm.logon.php</a>";
		$APP_CHILLI_INSTALLED_FTHOWTO=$tpl->_ENGINE_parse_body("{APP_CHILLI_INSTALLED_FTHOWTO}");
		$APP_CHILLI_INSTALLED_FTHOWTO=str_replace("%S", $uri, $APP_CHILLI_INSTALLED_FTHOWTO);
		$html="
		<center style='margin:10px'><img src='img/hotspot-logo.png'></center>		
		<div class=explain style='font-size:16px'>$APP_CHILLI_INSTALLED_FTHOWTO</div>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	
	
	
	
	
	
	
	
	
	$array["popup"]='{service_parameters}';
	$array["options"]='{options}';
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	
	
	$fontsize=14;
	if(count($array)>6){$fontsize=12.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t$YahooWinUri\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
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
function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_explain}");
	$t=$_GET["t"];
	$html="
		
var x_AddNewFreeWeb$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('squid_hotspot');
}

function AddNewFreeWeb$t(){
var servername=prompt('$addfree');
if(!servername){return;}
var XHR = new XHRConnection();
XHR.appendData('ADD_DNS_ENTRY','');
XHR.appendData('ForceInstanceZarafaID','');
XHR.appendData('ForwardTo','');
XHR.appendData('Forwarder','0');
XHR.appendData('SAVE_FREEWEB_MAIN','yes');
XHR.appendData('ServerIP','');
XHR.appendData('UseDefaultPort','0');
XHR.appendData('UseReverseProxy','0');
XHR.appendData('gpid','');
XHR.appendData('lvm_vg','');
XHR.appendData('servername',servername);
XHR.appendData('sslcertificate','');
XHR.appendData('uid','');
XHR.appendData('useSSL','0');
XHR.appendData('force-groupware','SPLASHSQUID');
AnimateDiv('$t-animate');
XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);
}


AddNewFreeWeb$t();

";
echo $html;

}




function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
	$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
	if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
	$PdnsHotSpot=$sock->GET_INFO("PdnsHotSpot");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
	if(!is_numeric($PdnsHotSpot)){$PdnsHotSpot=0;}	
	
	
	
	$sql="SELECT servername FROM freeweb WHERE groupware='SPLASHSQUID'";
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$hash[null]="{select}";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$hash[$servername]=$servername;
	
	}	
	
	
	$PDNSHotSpot=Paragraphe_switch_img("{force_dns_redirection}","{PDNSHotSpot_explain}",
		"PdnsHotSpot",$PdnsHotSpot,null,"450");
	
	if(!$users->POWER_DNS_INSTALLED){
		$PDNSHotSpot=Paragraphe_switch_disable("{force_dns_redirection}", "{PDNSHotSpot_explain}");
	}
	
	
	$t=time();
	$html="
	
	<div id='$t-animate'></div>
	<div id='$t' class=explain style='font-size:14px'>{HotSpot_text}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{activate_hostpot}","{activate_hostpot_explain}",
		"EnableSplashScreen",$EnableSplashScreen,null,"450")."
		</td>
	</tr>
				
	<tr>
		<td colspan=2>$PDNSHotSpot</td>
	</tr>				
				
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{EnableSplashScreenAsObject}","{EnableSplashScreenAsObject_explain}",
		"EnableSplashScreenAsObject",$EnableSplashScreenAsObject,null,"450")."
		
		<div style='width:100%;text-align:right'>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:s_PopUp('http://proxy-appliance.org/index.php?cID=297',1024,900);\"
			style='font-size:18px;text-decoration:underline'>{online_help}</a>
		</div>		
		</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px' valign='top'>{redirect_to}:</td>
		<td>". Field_array_Hash($hash, "SplashScreenURI",$SplashScreenURI,null,null,0,"font-size:16px")."
		
		
		<div style='text-align:right'>
		<table style='width:100%'>
			<tr>
			<td width=1%><img src='img/plus-24.png'></td>
			<td width=100%>
		<a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('$page?add-freeweb-js=yes&t=$t');\"
					 		style=\"font-size:14px;text-decoration:underline;font-weight:bold\">{add_a_web_service}</a>
			</td>
			</tr>
			</table>
					 		
		</div>
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
		Loadjs('squid.compile.progress.php');
		
	}


function SaveHotSpot(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
		var XHR = new XHRConnection();
		XHR.appendData('EnableSplashScreen',document.getElementById('EnableSplashScreen').value);
		XHR.appendData('SplashScreenURI',document.getElementById('SplashScreenURI').value);
		XHR.appendData('EnableSplashScreenAsObject',document.getElementById('EnableSplashScreenAsObject').value);				
		
		if(document.getElementById('PdnsHotSpot')){
			XHR.appendData('PdnsHotSpot',document.getElementById('PdnsHotSpot').value);
		}
		
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot);
	}		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function terme_of_use(){
$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$page=CurrentPageName();
	$tpl=new templates();
$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
$USETERMSTEXT=$HotSpotConfig["USETERMSTEXT"];
if($USETERMSTEXT==null){$USETERMSTEXT=$GLOBALS["POLICY_DEFAULT"];}
$USETERMSTEXT=stripslashes($USETERMSTEXT);
$t=time();
$html="
<div id='$t-animate'></div>
<textarea 
	style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;
	border:5px solid #8E8E8E;overflow:auto;font-size:16px' 
	id='$t'>$USETERMSTEXT</textarea>
	<center style='margin:20px'>
	". button("{apply}", "SaveTermsOfUse$t()","18px")."
	</center>
<script>
	var x_SaveHotSpot$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		document.getElementById('$t-animate').innerHTML='';
		YahooWin3Hide();
	}


function SaveTermsOfUse$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
		var XHR = new XHRConnection();
		XHR.appendData('USETERMSTEXT',document.getElementById('USETERMSTEXT').value);
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
	}
</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function SaveOptions(){
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
}


function SaveConfig(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSplashScreen", $_POST["EnableSplashScreen"]);
	$sock->SET_INFO("SplashScreenURI", $_POST["SplashScreenURI"]);
	$sock->SET_INFO("EnableSplashScreenAsObject", $_POST["EnableSplashScreenAsObject"]);
	
	if(isset($_POST["PdnsHotSpot"])){
		$sock->SET_INFO("PdnsHotSpot", $_POST["PdnsHotSpot"]);
		if($_POST["PdnsHotSpot"]==1){$sock->SET_INFO("EnablePDNS", 1);}
		$sock->getFrameWork("cmd.php?pdns-restart=yes");
	}
	
	$sock->getFrameWork("squid.php?build-smooth=yes");
}
function radius_config(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$array=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));	
	if(!is_numeric($array["RAD_PORT"])){$array["RAD_PORT"]=1812;}
	
	
	
	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{radius_server}:</td>
	<td>". Field_text("RAD_SERVER-$tt",$array["RAD_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("RAD_PORT-$tt",$array["RAD_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("RAD_PASSWORD-$tt",$array["RAD_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$tt()","18px")."</td>
					</tr>
					</table>
					<script>
					var x_Save$tt= function (obj) {
					var results=obj.responseText;
					if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
					document.getElementById('$tt').innerHTML='';
					YahooWin3Hide();
	}
	
					function Save$tt(){
					var XHR = new XHRConnection();
					XHR.appendData('RAD_SERVER', document.getElementById('RAD_SERVER-$tt').value);
					XHR.appendData('RAD_PORT', document.getElementById('RAD_PORT-$tt').value);
					XHR.appendData('RAD_PASSWORD', encodeURIComponent(document.getElementById('RAD_PASSWORD-$tt').value));
					AnimateDiv('$tt');
					XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
	</script>";
	
		echo $tpl->_ENGINE_parse_body($html);
	
	
	}
