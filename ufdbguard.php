<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["client-js"])){client_js();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["ufdbperf"])){ufdbperf();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["enforce-https-with-hostname"])){save_ssl();exit;}
	if(isset($_GET["ufdbclient"])){ufdbclient_popup();exit;}
	if(isset($_POST["url_rewrite_children_max"])){save_ssl();exit;}
	if(isset($_POST["UseRemoteUfdbguardService"])){save_ssl();exit;}
	if(isset($_POST["url_rewrite_bypass"])){url_rewrite_bypass_save();exit;}
	if(isset($_GET["force-reload-js"])){force_reload_js();exit;}
	if(isset($_POST["force-reload-perform"])){force_reload_perform();exit;}
	if(isset($_GET["import-export"])){import_export();exit;}
	if(isset($_GET["TestsSocket"])){TestsSocket();exit;}
	js();

	
function force_reload_js(){
	$page=CurrentPageName();
	$html="
	var x_force_reload_ufdb=function (obj) {
		 var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
	}	
	function force_reload_ufdb(){
		var XHR = new XHRConnection();
		XHR.appendData('force-reload-perform',1);
    	XHR.sendAndLoad('$page', 'POST',x_force_reload_ufdb);
	}

	force_reload_ufdb();
	";
	
	echo $html;
	
}
function force_reload_perform(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?ufdbguard-reload=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{service_reloaded_in_background_mode}",1);
	
}


function client_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{client_parameters}");
	$html="YahooWin3('730','$page?ufdbclient=yes','$title',true);";
	echo $html;
	
	
}
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}");
	$html="YahooWin3('730','$page?tabs=yes','$title',true);";
	echo $html;
	}
	
function TestsSocket(){
	$errorSock=null;
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$UseRemoteUfdbguardService=$datas["UseRemoteUfdbguardService"];
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}	
	if($UseRemoteUfdbguardService==1){
		if(!@fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 1)){
			$html="<div style='font-size:14px;color:#CC0A0A;width:95%' class=form><strong style='font-size:14px'>{warn_ufdbguard_remote_error}</strong>
			<p style='font-size:14px'>{server}:&laquo;{$datas["remote_server"]}&raquo;:{$datas["remote_port"]} {error} $errno $errstr</p>
			<div style='text-align:right'>". imgtootltip("refresh-24.png",null,"LoadAjaxTiny('$t-sock','$page?TestsSocket=yes&t=$t');")."</div>
			</div>";
			echo $tpl->_ENGINE_parse_body($html);	
		}
	}
	
}
	
function ufdbclient_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));	
	$lock=0;
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	$UseRemoteUfdbguardService=$datas["UseRemoteUfdbguardService"];
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	
	if($EnableRemoteStatisticsAppliance==1){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
		$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$lock=1;
	}

	
	
	$t=time();
	$html="
	<div class=explain style='font-size:13px'>{ufdbclient_parms_explain}</div>
	
	<div id='$t-sock'></div>
	<div style='width:98%' class=form>
		<table >
		<tr>
			<td class=legend style='font-size:14px'>{UseRemoteUfdbguardService}:</td>
			<td>". Field_checkbox("UseRemoteUfdbguardService",1,$datas["UseRemoteUfdbguardService"],"RemoteUfdbCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{remote_server}:</td>
			<td>". Field_text("remote_server",$datas["remote_server"],"font-size:14px;width:165px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{remote_port}:</td>
			<td>". Field_text("remote_port",$datas["remote_port"],"font-size:14px;width:65px")."</td>
		</tr>
		
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveufdbGuardClient()",16)."</td>
		</tr>	
		</table>
		</div>
	
	<script>
	var x_SaveufdbGuardClient=function (obj) {
		RefreshTab('main_ufdbguard_config');
		
		var flexRT;
		if( document.getElementById('WebFilteringMainTableID') ){
			flexRT=document.getElementById('WebFilteringMainTableID').value;
			$('#flexRT'+flexRT).flexReload();
		}
		
		document.getElementById('$t').innerHTML='';
		Loadjs('squid.compile.progress.php?ask=yes');
	}	

	function RemoteUfdbCheck(){
		var lock=$lock;
		document.getElementById('remote_port').disabled=true;
		document.getElementById('remote_server').disabled=true;	
		
		if(lock==1){
			document.getElementById('UseRemoteUfdbguardService').disabled=true;
			return;
		}
		
		if(document.getElementById('UseRemoteUfdbguardService').checked){
			document.getElementById('remote_server').disabled=false;
			document.getElementById('remote_port').disabled=false;
			LoadAjaxTiny('$t-sock','$page?TestsSocket=yes&t=$t');
		}
		
	}
	
	function SaveufdbGuardClient(){
		var lock=$lock;
		if(lock==1){
			Loadjs('squid.newbee.php?error-remote-appliance=yes');
			return;
		}
		var XHR = new XHRConnection();
		if(document.getElementById('UseRemoteUfdbguardService').checked){
    		XHR.appendData('UseRemoteUfdbguardService',1);}else{
    		XHR.appendData('UseRemoteUfdbguardService',0);}
			XHR.appendData('remote_server',document.getElementById('remote_server').value);
    		XHR.appendData('remote_port',document.getElementById('remote_port').value);	
		
 			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveufdbGuardClient);
	}	
	RemoteUfdbCheck();
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}
	
	
function tabs(){
		$tpl=new templates();
		$page=CurrentPageName();
		$users=new usersMenus();
		$sock=new sockets();
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	
		$array["popup"]='{service_parameters}';
		if(!$users->WEBSTATS_APPLIANCE){$array["ufdbperf"]='{performances}';}
		if(!$users->WEBSTATS_APPLIANCE){$array["ufdbclient"]='{client_parameters}';}
		if($EnableRemoteStatisticsAppliance==1){unset($array["popup"]);}
		$array["notifs"]='{notifications}';
		$array["import-export"]="{import}/{export}";
		
	while (list ($num, $ligne) = each ($array) ){
		if($num=="notifs"){
			
			$tab[]="<li><a href=\"ufdbguard.smtp.notif.php?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		 $tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		}
	
	$html=build_artica_tabs($tab, "main_ufdbguard_config");
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function ufdbperf(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$squid=new squidbee();
	$users=new usersMenus();
	$t=time();

	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
	if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
	if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=20;}

	for($i=0;$i<100;$i++){
		$url_rewrite_children_startup[$i]=" $i ";
		
	}
	
	$url_rewrite_children_concurrency[0]=" 0 " ;
	$url_rewrite_children_concurrency[2]=" 2 ";
	$url_rewrite_children_concurrency[3]=" 3 ";
	$url_rewrite_children_concurrency[4]=" 4 ";
	

	
	$html="
	<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<div class=explain style='font-size:16px'>{ufdb_perfs_explain}</div>
	<table style='width:100%'>
	
	<tr>			
		<tr>
		<td class=legend style='font-size:16px'>{CHILDREN_MAX}:</td>
		<td style='font-size:16px'>".Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_max",
	$datas["url_rewrite_children_max"],null,null,0,"font-size:16px;width:90px;")."&nbsp;{processes}</td>
	</tr>	
	<tr>			
		<tr>
		<td class=legend style='font-size:16px'>{CHILDREN_STARTUP}:</td>
		<td style='font-size:16px'>".Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_startup",
	$datas["url_rewrite_children_startup"],null,null,0,"font-size:16px;width:90px;")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{CHILDREN_IDLE}:</td>
		<td style='font-size:16px'>". Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_idle",
	$datas["url_rewrite_children_idle"],null,null,0,"font-size:16px;width:90px;")."&nbsp;{processes}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{CHILDREN_CONCURRENCY}:</td>
		<td style='font-size:16px'>". Field_array_Hash($url_rewrite_children_concurrency, 
	"url_rewrite_children_concurrency",
	$datas["url_rewrite_children_concurrency"],null,null,0,"font-size:16px;width:90px;")."&nbsp;{processes}</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",16)."</td>
	</tr>					
	</table>		
	</div>	
<script>
	var xSave$t=function (obj) {
		RefreshTab('main_ufdbguard_config');
		Loadjs('squid.compile.progress.php?ask=yes');
	}	

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('url_rewrite_children_max',document.getElementById('url_rewrite_children_max').value);
    	XHR.appendData('url_rewrite_children_startup',document.getElementById('url_rewrite_children_startup').value);
    	XHR.appendData('url_rewrite_children_idle',document.getElementById('url_rewrite_children_idle').value);
    	XHR.appendData('url_rewrite_children_concurrency',document.getElementById('url_rewrite_children_concurrency').value);		
		AnimateDiv('$t');
    	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>				
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$squid=new squidbee();
	$users=new usersMenus();
	$url_rewrite_bypass=$squid->url_rewrite_bypass;
	$ufdbguardReloadTTL=$sock->GET_INFO("ufdbguardReloadTTL");
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	
	if($datas["enforce-https-with-hostname"]==null){$datas["enforce-https-with-hostname"]=0;}
	if($datas["enforce-https-official-certificate"]==null){$datas["enforce-https-official-certificate"]=0;}
	if($datas["https-prohibit-insecure-sslv2"]==null){$datas["https-prohibit-insecure-sslv2"]=0;}
	if(!is_numeric($datas["url-lookup-result-during-database-reload"])){$datas["url-lookup-result-during-database-reload"]=1;}
	if(!is_numeric($datas["url-lookup-result-when-fatal-error"])){$datas["url-lookup-result-when-fatal-error"]=1;}
	if(!is_numeric($datas["check-proxy-tunnel"])){$datas["check-proxy-tunnel"]=1;}
	if(!is_numeric($datas["strip-domain-from-username"])){$datas["strip-domain-from-username"]=0;}
	if(!is_numeric($datas["refreshuserlist"])){$datas["refreshuserlist"]=15;}
	if(!is_numeric($datas["refreshdomainlist"])){$datas["refreshdomainlist"]=15;}
	if(!is_numeric($datas["NoMalwareUris"])){$datas["NoMalwareUris"]=1;}
	if(!is_numeric($datas["DisableExpressionList"])){$datas["DisableExpressionList"]=1;}
	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	$UfdbGuardThreads=$sock->GET_INFO("UfdbGuardThreads");
	
	
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	
	if(!is_numeric($datas["allow-unknown-protocol-over-https"])){$datas["allow-unknown-protocol-over-https"]=1;}
	
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="all";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="all";}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if(!is_numeric($datas["DebugAll"])){$datas["DebugAll"]=0;}	
	if(!is_numeric($ufdbguardReloadTTL)){$ufdbguardReloadTTL=10;}
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}	
	$WEBSTATS_APPLIANCE=0;
	
	if($users->WEBSTATS_APPLIANCE){
		$WEBSTATS_APPLIANCE=1;
		$datas["tcpsockets"]=1;
	}
	
	$sys=new networking();
	$ips=$sys->ALL_IPS_GET_ARRAY();
	if($users->WEBSTATS_APPLIANCE){
		unset($ips["127.0.0.1"]);
	}
	
	$as27=0;
	if($squid->IS_27){$as27=1;}
	
	if($UseRemoteUfdbguardService==1){$UseRemoteUfdbguardService_error="<p class=text-error>{warn_ufdbguard_remote_use}</p>";}
	
	$ips["all"]="{all}";
	$html="
	$UseRemoteUfdbguardService_error
	<div id='From-ufdbguard'>
	<div id='GuardSSL'>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=2><span style='font-size:16px'>{ssl}:</span>
	<tr>
		<td class=legend style='font-size:14px'>{enforce-https-with-hostname}:</td>
		<td>". Field_checkbox("enforce-https-with-hostname",1,$datas["enforce-https-with-hostname"])."</td>
		<td width=1%>". help_icon("{UFDBGUARD_SSL_OPTS}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enforce-https-official-certificate}:</td>
		<td>". Field_checkbox("enforce-https-official-certificate",1,$datas["enforce-https-official-certificate"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{https-prohibit-insecure-sslv2}:</td>
		<td>". Field_checkbox("https-prohibit-insecure-sslv2",1,$datas["https-prohibit-insecure-sslv2"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{allow-unknown-protocol-over-https}:</td>
		<td>". Field_checkbox("allow-unknown-protocol-over-https",1,$datas["allow-unknown-protocol-over-https"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>				
				
	
	</table>
	</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	
	<td colspan=3><span style='font-size:16px'>{UFDBGUARD_SERVICE_OPTS}:</span>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:14px'>{UfdbDatabasesInMemory}:</td>
		<td>". Field_checkbox("UfdbDatabasesInMemory",1,$UfdbDatabasesInMemory)."</td>
		<td width=1%>". help_icon("{UfdbDatabasesInMemory_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{DisableExpressionLists}:</td>
		<td>". Field_checkbox("DisableExpressionList",1,$datas["DisableExpressionList"])."</td>
		<td width=1%>". help_icon("{DisableExpressionLists_explain}")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:14px'>{bypass_iffailed}:</td>
		<td>". Field_checkbox("url_rewrite_bypass",1,$url_rewrite_bypass,"url_rewrite_bypassCheck()")."</td>
		<td width=1%>". help_icon("{url_rewrite_bypass_explain}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{verbose_mode}:</td>
		<td>". Field_checkbox("DebugAll",1,$datas["DebugAll"],"")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{minimum_reload_interval}:</td>
		<td style='font-size:14px'>". Field_text("ufdbguardReloadTTL",$ufdbguardReloadTTL,"font-size:14px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>Threads:</td>
		<td style='font-size:14px'>". Field_text("UfdbGuardThreads",$UfdbGuardThreads,"font-size:14px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:14px'>{enable_tcpsockets}:</td>
		<td>". Field_checkbox("tcpsockets",1,$datas["tcpsockets"],"tcpsocketsCheck()")."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{listen_address}:</td>
		<td>". Field_array_Hash($ips,"listen_addr",$datas["listen_addr"],"style:font-size:14px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("listen_port",$datas["listen_port"],"font-size:14px;width:65px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{url-lookup-result-during-database-reload}:</td>
		<td>". Field_checkbox("url-lookup-result-during-database-reload",1,$datas["url-lookup-result-during-database-reload"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{url-lookup-result-when-fatal-error}:</td>
		<td>". Field_checkbox("url-lookup-result-when-fatal-error",1,$datas["url-lookup-result-when-fatal-error"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{check-proxy-tunnel}:</td>
		<td>". Field_checkbox("check-proxy-tunnel",1,$datas["check-proxy-tunnel"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{NoMalwareUris}:</td>
		<td>". Field_checkbox("NoMalwareUris",1,$datas["NoMalwareUris"])."</td>
		<td width=1%>". help_icon("{NoMalwareUris_explain}")."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:14px'>{EnableGoogleSafeSearch}:</td>
		<td>". Field_checkbox("EnableGoogleSafeSearch",1,$EnableGoogleSafeSearch)."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{strip-domain-from-username}:</td>
		<td>". Field_checkbox("strip-domain-from-username",1,$datas["strip-domain-from-username"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{refreshuserlist}:</td>
		<td style='font-size:14px'>". Field_text("refreshuserlist",$datas["refreshuserlist"],"font-size:14px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{refreshdomainlist}:</td>
		<td style='font-size:14px'>". Field_text("refreshdomainlist",$datas["refreshdomainlist"],"font-size:14px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>					
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveufdbGuardSSL()",16)."</td>
	</tr>	
	</table>
	</div>
	</div>
	</div>
	<script>
	var x_SaveufdbGuardSSLl=function (obj) {
		RefreshTab('main_ufdbguard_config');
	}

	function CHECKWEBSTATS_APPLIANCE(){
		var WEBSTATS_APPLIANCE=$WEBSTATS_APPLIANCE;
		var UseRemoteUfdbguardService=$UseRemoteUfdbguardService;
		if(WEBSTATS_APPLIANCE==1){document.getElementById('tcpsockets').disabled=true;}
		if(UseRemoteUfdbguardService==1){
			DisableFieldsFromId('From-ufdbguard');	
		}
	}

	function tcpsocketsCheck(){
			document.getElementById('listen_addr').disabled=true;
			document.getElementById('listen_port').disabled=true;	
		if(document.getElementById('tcpsockets').checked){
			document.getElementById('listen_addr').disabled=false;
			document.getElementById('listen_port').disabled=false;
		}
	}
	
	function url_rewrite_bypassCheck(){
		var is27=$as27;
		document.getElementById('url_rewrite_bypass').disabled=true;
		if(is27==0){document.getElementById('url_rewrite_bypass').disabled=false;}
		var XHR = new XHRConnection();
		if(document.getElementById('url_rewrite_bypass').checked){XHR.appendData('url_rewrite_bypass',1);}else{XHR.appendData('url_rewrite_bypass',0);}
		XHR.sendAndLoad('$page', 'POST');
	}
	
	function SaveufdbGuardSSL(){
		var UseRemoteUfdbguardService=$UseRemoteUfdbguardService;
		if(UseRemoteUfdbguardService==1){return;}
		var XHR = new XHRConnection();
		
		
		if(document.getElementById('DebugAll').checked){
    		XHR.appendData('DebugAll',1);}else{
    		XHR.appendData('DebugAll',0);}		
		
		if(document.getElementById('enforce-https-with-hostname').checked){
    		XHR.appendData('enforce-https-with-hostname',1);}else{
    		XHR.appendData('enforce-https-with-hostname',0);}

		if(document.getElementById('enforce-https-official-certificate').checked){
    		XHR.appendData('enforce-https-official-certificate',1);}else{
    		XHR.appendData('enforce-https-official-certificate',0);}    		

		if(document.getElementById('https-prohibit-insecure-sslv2').checked){
    		XHR.appendData('https-prohibit-insecure-sslv2',1);}else{
    		XHR.appendData('https-prohibit-insecure-sslv2',0);}  
    		
    		
		if(document.getElementById('allow-unknown-protocol-over-https').checked){
    		XHR.appendData('allow-unknown-protocol-over-https',1);}else{
    		XHR.appendData('allow-unknown-protocol-over-https',0);}     		

		if(document.getElementById('DisableExpressionList').checked){
    		XHR.appendData('DisableExpressionList',1);}else{
    		XHR.appendData('DisableExpressionList',0);}    
    		
    		

		if(document.getElementById('url-lookup-result-when-fatal-error').checked){
    		XHR.appendData('url-lookup-result-when-fatal-error',1);}else{
    		XHR.appendData('url-lookup-result-when-fatal-error',0);}      		
    		
		if(document.getElementById('url-lookup-result-during-database-reload').checked){
    		XHR.appendData('url-lookup-result-during-database-reload',1);}else{
    		XHR.appendData('url-lookup-result-during-database-reload',0);}   

		if(document.getElementById('check-proxy-tunnel').checked){
    		XHR.appendData('check-proxy-tunnel',1);}else{
    		XHR.appendData('check-proxy-tunnel',0);}      

		if(document.getElementById('tcpsockets').checked){
    		XHR.appendData('tcpsockets',1);}else{
    		XHR.appendData('tcpsockets',0);} 
    		
		if(document.getElementById('EnableGoogleSafeSearch').checked){
    		XHR.appendData('EnableGoogleSafeSearch',1);}else{
    		XHR.appendData('EnableGoogleSafeSearch',0);} 
    		
		if(document.getElementById('strip-domain-from-username').checked){
    		XHR.appendData('strip-domain-from-username',1);}else{
    		XHR.appendData('strip-domain-from-username',0);}     		

		if(document.getElementById('UfdbDatabasesInMemory').checked){
    		XHR.appendData('UfdbDatabasesInMemory',1);}else{
    		XHR.appendData('UfdbDatabasesInMemory',0);}       		
		
		if(document.getElementById('NoMalwareUris').checked){
    		XHR.appendData('NoMalwareUris',1);}else{
    		XHR.appendData('NoMalwareUris',0);}      		
    		
    		
    		    		
    	XHR.appendData('listen_port',document.getElementById('listen_port').value);
    	XHR.appendData('listen_addr',document.getElementById('listen_addr').value);	
    	XHR.appendData('ufdbguardReloadTTL',document.getElementById('ufdbguardReloadTTL').value);
    	XHR.appendData('refreshuserlist',document.getElementById('refreshuserlist').value);
    	XHR.appendData('refreshdomainlist',document.getElementById('refreshdomainlist').value);
    	XHR.appendData('UfdbGuardThreads',document.getElementById('UfdbGuardThreads').value);
    	
    	
    		
 		AnimateDiv('GuardSSL');
    	XHR.sendAndLoad('$page', 'POST',x_SaveufdbGuardSSLl);
	}	
	tcpsocketsCheck();
	CHECKWEBSTATS_APPLIANCE();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function url_rewrite_bypass_save(){
	
	$squid=new squidbee();
	$squid->url_rewrite_bypass=$_POST["url_rewrite_bypass"];
	$squid->SaveToLdap();
	
}


function save_ssl(){
	$sock=new sockets();
	if(isset($_POST["UseRemoteUfdbguardService"])){
		$sock->SET_INFO('UseRemoteUfdbguardService', $_POST["UseRemoteUfdbguardService"]);
		if($_POST["UseRemoteUfdbguardService"]==1){
			$remote_server=$_POST["remote_server"];
			$remote_port=$_POST["remote_port"];
			if(!is_numeric($remote_port)){$remote_port=3977;$_POST["remote_port"]=3977;}
			if(@fsockopen($remote_server, 9000, $errno, $errstr, 1)){
				$uri="https://$remote_server:9000/nodes.listener.php?ufdbguardport=$remote_port";
				$curl=new ccurl($uri);
				$curl->NoHTTP_POST=true;
				if(!$curl->get()){}
			}
			
			
			if(!@fsockopen($remote_server, $remote_port, $errno, $errstr, 1)){
				echo "$remote_server:$remote_port\nError: $errno $errstr\n";
				
			}
			
		}
	}
	
	if(isset($_POST["UfdbGuardThreads"])){
		writelogs("SET_INFO UfdbGuardThreads= {$_POST["UfdbGuardThreads"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('UfdbGuardThreads', $_POST["UfdbGuardThreads"]);
	}
	
	if(isset($_POST["ufdbguardReloadTTL"])){
		writelogs("SET_INFO ufdbguardReloadTTL= {$_POST["ufdbguardReloadTTL"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('ufdbguardReloadTTL', $_POST["ufdbguardReloadTTL"]);
	}
	if(isset($_POST["EnableGoogleSafeSearch"])){
		writelogs("SET_INFO EnableGoogleSafeSearch= {$_POST["EnableGoogleSafeSearch"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('EnableGoogleSafeSearch', $_POST["EnableGoogleSafeSearch"]);
	}	
	if(isset($_POST["UfdbDatabasesInMemory"])){
		writelogs("SET_INFO UfdbDatabasesInMemory= {$_POST["UfdbDatabasesInMemory"]}",__FUNCTION__,__FILE__,__LINE__);
		$sock->SET_INFO('UfdbDatabasesInMemory', $_POST["UfdbDatabasesInMemory"]);
	}	
	
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	while (list ($key, $line) = each ($_POST) ){
		writelogs("SAVE $key = $line",__FUNCTION__,__FILE__,__LINE__);
		$datas[$key]=$line;
		
	}
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");
	$sock->getFrameWork("cmd.php?reload-squidguard=yes");
}

function import_export(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$export=Paragraphe("64-export.png", "{export_rules}", "{export_acl_rules_explain}",
			"javascript:Loadjs('dansguardian2.export.php')");
	
	$import=Paragraphe("64-import.png", "{import_rules}", "{import_acl_rules_explain}",
			"javascript:Loadjs('dansguardian2.import.php')");
	$html="
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
	<td align='center'>$export</td>
	<td align='center'>$import</td>
	</tr>
	</table>
	</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

