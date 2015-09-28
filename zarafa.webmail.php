<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["webmail"])){zarafa_settings_webmail();exit;}
	if(isset($_GET["perfs"])){zarafa_settings_performances();exit;}
	if(isset($_POST["ZarafaApacheEnable"])){zarafa_settings_webmail_save();exit;}
	if(isset($_POST["apacheMasterconfig"])){zarafa_settings_performances_save();exit;}
	if(isset($_GET["status"])){status();exit;}
popup();	
	
	function popup(){
		$q=new mysql();
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
		$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
		if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
		$users=new usersMenus();
		if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	
		
		$array["webmail"]="{APP_ZARAFA_WEB}";
		$array["perfs"]="{performance}";
			
			
		$fontsize="font-size:24px;";
		while (list ($num, $ligne) = each ($array) ){
			$html[]="<li><a href=\"$page?$num=yes\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
		$tabwidth=759;
	
		$html=build_artica_tabs($html,'main_zarafaweb',1490)."
		<script>LeftDesign('webmail-256-white-opac20.png');</script>";
	
	echo $html;	
}

function zarafa_settings_webmail(){
$page=CurrentPageName();
$tpl=new templates();	
$sock=new sockets();
$users=new usersMenus();
$zarafa_version=$sock->getFrameWork("zarafa.php?getversion=yes");
preg_match("#^([0-9]+)\.#", $zarafa_version,$re);
$major_version=$re[1];
if(!is_numeric($major_version)){$major_version=6;}

$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$ZarafaApacheServerName=$sock->GET_INFO("ZarafaApacheServerName");
if(trim($ZarafaApacheServerName)==null){$ZarafaApacheServerName=$users->hostname;}

$enable_ssl=$sock->GET_INFO("ZarafaApacheSSL");	
if($ZarafaApachePort==null){$ZarafaApachePort="9010";}
$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
$ZarafaImportContactsInLDAPEnable=$sock->GET_INFO("ZarafaImportContactsInLDAPEnable");
$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");



$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");

if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
if(!is_numeric($ZarafaImportContactsInLDAPEnable)){$ZarafaImportContactsInLDAPEnable=0;}
if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
$ZarafaSessionTime_field=$ZarafaSessionTime/60;




if($enable_ssl==null){$enable_ssl="0";}
if($ZarafaiCalEnable==null){$ZarafaiCalEnable=0;}
if(!is_numeric($ZarafaUserSafeMode)){$sock->SET_INFO("ZarafaUserSafeMode",0);$ZarafaUserSafeMode=0;}
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");
$ZarafaApachePHPFPMEnable=$sock->GET_INFO("ZarafaApachePHPFPMEnable");
$ZarafaApacheWebMailType=$sock->GET_INFO("ZarafaApacheWebMailType");

$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
if(!is_numeric($ZarafaApachePHPFPMEnable)){$ZarafaApachePHPFPMEnable=0;}
$ZarafaAspellInstalled=0;
$ZarafaAspellInstalled_text="({not_installed})";

if($users->ASPELL_INSTALLED){
	$ZarafaAspellInstalled=1;
	$ZarafaAspellInstalled_text="({installed})";
}

$fieldsHTTP[]="ZarafaApacheServerName";
$fieldsHTTP[]="ZarafaApachePort";
$fieldsHTTP[]="ZarafaApacheSSL";
$fieldsHTTP[]="ZarafaSessionTime";
$fieldsHTTP[]="ZarafaWebNTLM";
$fieldsHTTP[]="ZarafaAspellEnabled";
$fieldsHTTP[]="ZarafaEnablePlugins";
$fieldsHTTP[]="ZarafaImportContactsInLDAPEnable";

while (list($num,$val)=each($fieldsHTTP)){	
	$fieldsHTTPjs1[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=true;}";
	$fieldsHTTPjs2[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=false;}";
	
}

if(!$users->APACHE_INSTALLED){
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top'><img id='zrfa-logo' src='img/zarfa-web-error-128.png'></td>
		<td valign='top'>	
			<table style='width:100%'>
			<tr>
				<td colspan=2>
				<p style='font-size:24px;color:#C61010'>{ZARAFA_ERROR_NO_APACHE}</p>
				
				</td>
			</tr>
			</table>
		</td>
		</tr>
		</table>";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
		return;
}

if($users->PHPFPM_INSTALLED){
$phpfpm="	<tr>
		<td colspan=2>
		". Paragraphe_switch_img("{enable_phpfpm_service}", 
				"{enable_phpfpm_service_explain}","ZarafaApachePHPFPMEnable",
				$ZarafaApachePHPFPMEnable,null,1130)."</td>
		</td>
	</tr>";
	
}

$ZarafaApacheWebMailTypeA["APP_ZARAFA"]="Standard - {APP_ZARAFA}";
$ZarafaApacheWebMailTypeA["APP_ZARAFA_WEBAPP"]="WebAPP - {APP_ZARAFA_WEBAPP}";
if($ZarafaApacheWebMailType==null){$ZarafaApacheWebMailType="APP_ZARAFA";}
	
$t=time();	
$html="
<div class=explain style='font-size:24px' id='anim-$t'>{zarafa_settings_webmail}</div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
<td style='vertical-align:top;width:240px'><div id='status-zarafaweb'></div>
<div style='text-align:right'><p>&nbsp</p>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('status-zarafaweb','$page?status=yes');")."</div>
</td>
<td style='vertical-align:top;width:99%'>
<table style='width:100%'>
		
	<tr>
		<td colspan=2>
		". Paragraphe_switch_img("{enable_http_service}", 
				"{enable_http_service_zarafa_explain}","ZarafaApacheEnable",$ZarafaApacheEnable,null,1130)."</td>
		</td>
	</tr>
						$phpfpm
	<tr>
		<td class=legend style='font-size:24px'>{webmail_type}:</td>
		<td>". Field_array_Hash($ZarafaApacheWebMailTypeA, "ZarafaApacheWebMailType-$t",$ZarafaApacheWebMailType,null,null,0,"font-size:24px")."</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:24px'>{hostname}:</td>
		<td>". Field_text("ZarafaApacheServerName",$ZarafaApacheServerName,"font-size:24px;padding:3px;width:450px")."</td>
	</tr>		
		<tr>
		<td class=legend style='font-size:24px'>{listen_port}:</td>
		<td>". Field_text("ZarafaApachePort",$ZarafaApachePort,"font-size:24px;padding:3px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{enable_ssl}:</td>
		<td>". Field_checkbox_design("ZarafaApacheSSL",1,$enable_ssl)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{SessionTime}:</td>
		<td style='font-size:24px;padding:3px;'>". Field_text("ZarafaSessionTime",$ZarafaSessionTime_field,"font-size:24px;padding:3px;width:60px")."&nbsp;{minutes}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:24px'>{ZarafaWebNTLM}:</td>
		<td>". Field_checkbox_design("ZarafaWebNTLM",1,$ZarafaWebNTLM)."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:24px'>{spell_checker}&nbsp;$ZarafaAspellInstalled_text&nbsp;:</td>
		<td>". Field_checkbox_design("ZarafaAspellEnabled",1,$ZarafaAspellEnabled)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{ZarafaEnablePlugins}:</td>
		<td>". Field_checkbox_design("ZarafaEnablePlugins",1,$ZarafaEnablePlugins)."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:24px'>{ZarafaImportContactsInLDAPEnable}&nbsp;:</td>
		<td>". Field_checkbox_design("ZarafaImportContactsInLDAPEnable",1,$ZarafaImportContactsInLDAPEnable)."</td>
	</tr>			
		<tr><td colspan=2 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE$t()","26")."</td></tr>							
	</table>
</td>
</tr>
</table>
</div>
	<script>
		var X_APP_ZARAFA_WEB_SAVE$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_zarafaweb');
			}	
	function ZarafaApacheDisableCheck(){
		var ZarafaAspellInstalled=$ZarafaAspellInstalled;
		". @implode("\n", $fieldsHTTPjs1)."
		if(document.getElementById('ZarafaApacheEnable').checked){
		". @implode("\n", $fieldsHTTPjs2)."
		
		if(ZarafaAspellInstalled==0){
			document.getElementById('ZarafaAspellEnabled').disabled=true;
		}
		
		}
	}			
		
			function APP_ZARAFA_WEB_SAVE$t(){
				var XHR = new XHRConnection();
				
				if(document.getElementById('ZarafaApachePHPFPMEnable')){
					XHR.appendData('ZarafaApachePHPFPMEnable',document.getElementById('ZarafaApachePHPFPMEnable').value);
				}
				XHR.appendData('ZarafaApacheEnable',document.getElementById('ZarafaApacheEnable').value);
				XHR.appendData('ZarafaApacheServerName',document.getElementById('ZarafaApacheServerName').value);
				XHR.appendData('ZarafaApachePort',document.getElementById('ZarafaApachePort').value);
				XHR.appendData('ZarafaApacheWebMailType',document.getElementById('ZarafaApacheWebMailType-$t').value);
				
				
				
				if(document.getElementById('ZarafaApacheSSL').checked){XHR.appendData('ZarafaApacheSSL',1);}else{XHR.appendData('ZarafaApacheSSL',0);}
				if(document.getElementById('ZarafaWebNTLM').checked){XHR.appendData('ZarafaWebNTLM',1);}else{XHR.appendData('ZarafaWebNTLM',0);}
				if(document.getElementById('ZarafaAspellEnabled').checked){XHR.appendData('ZarafaAspellEnabled',1);}else{XHR.appendData('ZarafaAspellEnabled',0);}
				if(document.getElementById('ZarafaEnablePlugins').checked){XHR.appendData('ZarafaEnablePlugins',1);}else{XHR.appendData('ZarafaEnablePlugins',0);}
				if(document.getElementById('ZarafaImportContactsInLDAPEnable').checked){XHR.appendData('ZarafaImportContactsInLDAPEnable',1);}else{XHR.appendData('ZarafaImportContactsInLDAPEnable',0);}
				XHR.sendAndLoad('$page', 'POST',X_APP_ZARAFA_WEB_SAVE$t);	
			}
			
		LoadAjax('status-zarafaweb','$page?status=yes');
		</script>		
		";	
echo $tpl->_ENGINE_parse_body($html);
	
}

function zarafa_settings_webmail_save(){
	$sock=new sockets();
	$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
	if(!is_numeric($_POST["ZarafaApachePort"])){$_POST["ZarafaApachePort"]=9010;}
	if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
	
	if($ZarafaApachePort<>$_POST["ZarafaApachePort"]){
		$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		if(socket_connect($socket, "127.0.0.1", $_POST["ZarafaApachePort"])){
			@socket_close($socket);
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{error_port_already_use} {$_POST["ZarafaApachePort"]}");
		}else{
			$sock->SET_INFO("ZarafaApachePort",trim($_POST["ZarafaApachePort"]));	
		}
	}
	
	
	
	$sock->SET_INFO("ZarafaApacheWebMailType", $_POST["ZarafaApacheWebMailType"]);
	$sock->SET_INFO("ZarafaApachePHPFPMEnable",trim($_POST["ZarafaApachePHPFPMEnable"]));
	$sock->SET_INFO("ZarafaApacheEnable",trim($_POST["ZarafaApacheEnable"]));
	$sock->SET_INFO("ZarafaApacheSSL",trim($_POST["ZarafaApacheSSL"]));
	$sock->SET_INFO("ZarafaApacheServerName",trim($_POST["ZarafaApacheServerName"]));
	$sock->SET_INFO("ZarafaWebNTLM",trim($_POST["ZarafaWebNTLM"]));
	$sock->SET_INFO("ZarafaEnablePlugins",trim($_POST["ZarafaEnablePlugins"]));
	$sock->SET_INFO("ZarafaAspellEnabled",trim($_POST["ZarafaAspellEnabled"]));
	$sock->SET_INFO("ZarafaImportContactsInLDAPEnable",trim($_POST["ZarafaImportContactsInLDAPEnable"]));
	$sock->getFrameWork("cmd.php?zarafa-restart-web=yes");
	
}

function zarafa_settings_performances(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$FreeWebPerformances=unserialize(base64_decode($sock->GET_INFO("ZarafaApachePerformances")));
	if(!is_numeric($FreeWebPerformances["Timeout"])){$FreeWebPerformances["Timeout"]=300;}
	if(!is_numeric($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
	if(!is_numeric($FreeWebPerformances["MaxKeepAliveRequests"])){$FreeWebPerformances["MaxKeepAliveRequests"]=100;}
	if(!is_numeric($FreeWebPerformances["KeepAliveTimeout"])){$FreeWebPerformances["KeepAliveTimeout"]=15;}
	if(!is_numeric($FreeWebPerformances["MinSpareServers"])){$FreeWebPerformances["MinSpareServers"]=25;}
	if(!is_numeric($FreeWebPerformances["MaxSpareServers"])){$FreeWebPerformances["MaxSpareServers"]=50;}
	if(!is_numeric($FreeWebPerformances["StartServers"])){$FreeWebPerformances["StartServers"]=50;}
	if(!is_numeric($FreeWebPerformances["MaxClients"])){$FreeWebPerformances["MaxClients"]=512;}
	if(!is_numeric($FreeWebPerformances["MaxRequestsPerChild"])){$FreeWebPerformances["MaxRequestsPerChild"]=10000;}
	
	
	if(!is_numeric($FreeWebPerformances["post_max_size"])){$FreeWebPerformances["post_max_size"]=50;}
	if(!is_numeric($FreeWebPerformances["upload_max_filesize"])){$FreeWebPerformances["upload_max_filesize"]=50;}
	if(!is_numeric($FreeWebPerformances["PhpStartServers"])){$FreeWebPerformances["PhpStartServers"]=20;}
	if(!is_numeric($FreeWebPerformances["PhpMinSpareServers"])){$FreeWebPerformances["PhpMinSpareServers"]=5;}
	if(!is_numeric($FreeWebPerformances["PhpMaxSpareServers"])){$FreeWebPerformances["PhpMaxSpareServers"]=25;}
	if(!is_numeric($FreeWebPerformances["PhpMaxClients"])){$FreeWebPerformances["PhpMaxClients"]=128;}
	
	
	
	$t=time();
	$html="
<div style='width:98%' class=form>	
			
			
<table style='width:99%'>
	<tr>
		<td colspan=3 style='font-size:42px'>PHP {performances}</td></tr>
	<tr>
		<td class=legend style='font-size:24px'>{post_max_size}:</td>
		<td style='font-size:24px'>". Field_text("post_max_size-$t",$FreeWebPerformances["post_max_size"],"font-size:24px;width:110px")."&nbsp;M</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{upload_max_filesize}:</td>
		<td style='font-size:24px'>". Field_text("upload_max_filesize-$t",$FreeWebPerformances["upload_max_filesize"],"font-size:24px;width:110px")."&nbsp;M</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{StartServers}:</td>
		<td style='font-size:24px'>". Field_text("PhpStartServers-$t",$FreeWebPerformances["PhpStartServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheStartServers}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{MinSpareServers}:</td>
		<td style='font-size:24px'>". Field_text("PhpMinSpareServers-$t",$FreeWebPerformances["PhpMinSpareServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{MaxSpareServers}:</td>
		<td style='font-size:24px'>". Field_text("PhpMaxSpareServers-$t",$FreeWebPerformances["PhpMaxSpareServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:24px'>{MaxClients}:</td>
		<td style='font-size:24px'>". Field_text("PhpMaxClients-$t",$FreeWebPerformances["PhpMaxClients"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMaxClients}")."</td>
	</tr>					
	<tr>			
	<tr>
		<td colspan=3 style='font-size:42px'>HTTP {performances}</td></tr>
	<tr>
		<td class=legend style='font-size:24px'>{Timeout}:</td>
		<td style='font-size:24px'>". Field_text("Timeout-$t",$FreeWebPerformances["Timeout"],"font-size:24px;width:120px;padding:3px")."&nbsp;{seconds}</td>
		<td>". help_icon("{ApacheTimeout}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{KeepAlive}:</td>
		<td style='font-size:24px'>". Field_checkbox_design("KeepAlive-$t",1,$FreeWebPerformances["KeepAlive"])."</td>
		<td>". help_icon("{ApacheKeepAlive}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{MaxKeepAliveRequests}:</td>
		<td style='font-size:24px'>". Field_text("MaxKeepAliveRequests-$t",$FreeWebPerformances["MaxKeepAliveRequests"],"font-size:24px;width:120px;padding:3px")."&nbsp;{requests}</td>
		<td>". help_icon("{ApacheMaxKeepAliveRequests}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:24px'>{KeepAliveTimeout}:</td>
		<td style='font-size:24px'>". Field_text("KeepAliveTimeout-$t",$FreeWebPerformances["KeepAliveTimeout"],"font-size:24px;width:120px;padding:3px")."&nbsp;{seconds}</td>
		<td>". help_icon("{ApacheKeepAliveTimeout}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{StartServers}:</td>
		<td style='font-size:24px'>". Field_text("StartServers-$t",$FreeWebPerformances["StartServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheStartServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{MinSpareServers}:</td>
		<td style='font-size:24px'>". Field_text("MinSpareServers-$t",$FreeWebPerformances["MinSpareServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{MaxSpareServers}:</td>
		<td style='font-size:24px'>". Field_text("MaxSpareServers-$t",$FreeWebPerformances["MaxSpareServers"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{MaxClients}:</td>
		<td style='font-size:24px'>". Field_text("MaxClients-$t",$FreeWebPerformances["MaxClients"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMaxClients}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:24px'>{MaxRequestsPerChild}:</td>
		<td style='font-size:24px'>". Field_text("MaxRequestsPerChild-$t",$FreeWebPerformances["MaxRequestsPerChild"],"font-size:24px;width:120px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMaxRequestsPerChild}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'>
		<hr>". button("{apply}","SaveApacheCentralSettings$t()","26")."</td>
	</tr>
	</table>
	</div>

<script>
		var x_SaveApacheCentralSettings$t=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}			
			
		}	
		
		function SaveApacheCentralSettings$t(){
			var XHR = new XHRConnection();
			XHR.appendData('apacheMasterconfig','yes');
    		
    		
			if(document.getElementById('KeepAlive-$t').checked){XHR.appendData('KeepAlive',1);}else{XHR.appendData('KeepAlive',0);}
			XHR.appendData('Timeout',document.getElementById('Timeout-$t').value);
			XHR.appendData('MaxKeepAliveRequests',document.getElementById('MaxKeepAliveRequests-$t').value);
			XHR.appendData('KeepAliveTimeout',document.getElementById('KeepAliveTimeout-$t').value);
			XHR.appendData('MinSpareServers',document.getElementById('MinSpareServers-$t').value);
			XHR.appendData('MaxSpareServers',document.getElementById('MaxSpareServers-$t').value);
			XHR.appendData('StartServers',document.getElementById('StartServers-$t').value);
			XHR.appendData('MaxClients',document.getElementById('MaxClients-$t').value);
			XHR.appendData('MaxRequestsPerChild',document.getElementById('MaxRequestsPerChild-$t').value);
			
			XHR.appendData('post_max_size',document.getElementById('post_max_size-$t').value);
			XHR.appendData('upload_max_filesize',document.getElementById('upload_max_filesize-$t').value);
			XHR.appendData('PhpStartServers',document.getElementById('PhpStartServers-$t').value);
			XHR.appendData('PhpMinSpareServers',document.getElementById('PhpMinSpareServers-$t').value);
			XHR.appendData('PhpMaxSpareServers',document.getElementById('PhpMaxSpareServers-$t').value);
			XHR.appendData('PhpMaxClients',document.getElementById('PhpMaxClients-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveApacheCentralSettings$t);
			
		}
</script>						
				
				
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function zarafa_settings_performances_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ZarafaApachePerformances");
	$sock->getFrameWork("cmd.php?zarafa-restart-web=yes");
	
}
function status(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
	$ini->loadString($datas);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("APP_ZARAFA_WEB",$ini,null,1));
	echo "<p>&nbsp;</p>";
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("APP_PHPFPM",$ini,null,1));
}