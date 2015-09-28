<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	
	if(isset($_GET["behavior"])){behavior();exit;}
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
	$html="YahooWin3('1000','$page?tabs=yes','$title',true);";
	echo $html;
	}
	
function TestsSocket(){
	$errorSock=null;
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	$datastatus=base64_decode($sock->getFrameWork("squid.php?ufdbguardd-status=yes"));
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	
	if($UseRemoteUfdbguardService==0){return;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}	
	
	
	
	
	
	if(!@fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 1)){
			$tr[]="<div style='font-size:14px;color:#CC0A0A;width:95%' class=form><strong style='font-size:14px'>{warn_ufdbguard_remote_error}</strong>
			<p style='font-size:14px'>{server}:&laquo;{$datas["remote_server"]}&raquo;:{$datas["remote_port"]} {error} $errno $errstr</p>
			
			</div><p>&nbsp;</p>";
			
			$datastatus=$datastatus."\n[APP_CONNECTION]
			service_name=APP_CONNECTION
			master_version={$datas["remote_server"]}:{$datas["remote_port"]}
			service_cmd=/etc/init.d/ufdb-client
			service_disabled=1
			watchdog_features=1
			running=0
			installed=1
			application_installed=1
			master_pid=0
			master_memory=0
			processes_number=1\n";
			
	}else{
		$datastatus=$datastatus."\n[APP_CONNECTION]
		service_name=APP_CONNECTION
		master_version={$datas["remote_server"]}:{$datas["remote_port"]}
		service_cmd=/etc/init.d/ufdb-client
		service_disabled=1
		watchdog_features=1
		running=1
		installed=1
		application_installed=1
		master_pid=0
		master_memory=0
		processes_number=1\n";
		
		
	}
	$ini->loadString($datastatus);
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBGUARD_CLIENT",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_CONNECTION",$ini,null,0);
	$tr[]="<div style='text-align:right'>". imgtootltip("refresh-24.png",null,"LoadAjaxTiny('$t-sock','$page?TestsSocket=yes&t=$t');")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}
	
function ufdbclient_popup(){
	if(!class_exists("compile_ufdbguard")){include_once("ressources/class.compile.ufdbguard.inc");}
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ufdbclass=new compile_ufdbguard();
	$datas=$ufdbclass->SetDefaultsConfig($datas);
	$UfdbUseArticaClient=$sock->GET_INFO("UfdbUseArticaClient");
	$UfdbArticaClientLocalCache=$sock->GET_INFO("UfdbArticaClientLocalCache");
	$UfdbgclientSockTimeOut=intval($sock->GET_INFO("UfdbgclientSockTimeOut"));
	$UfdbgclientMaxSockTimeOut=intval($sock->GET_INFO("UfdbgclientMaxSockTimeOut"));
	if(!is_numeric($UfdbUseArticaClient)){$UfdbUseArticaClient=1;}
	if(!is_numeric($UfdbArticaClientLocalCache)){$UfdbArticaClientLocalCache=1;}
	if($UfdbgclientSockTimeOut==0){$UfdbgclientSockTimeOut=2;}
	if($UfdbgclientMaxSockTimeOut==0){$UfdbgclientMaxSockTimeOut=5;}
	
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
	
	$redirect_behaviorA[null]="{default}";
	$redirect_behaviorA["url"]="{redirect_connexion}";
	$redirect_behaviorA["url-rewrite"]="{rewrite_url}";
	
	$HTTP_CODE[0]="{default}";
	$HTTP_CODE[301]="{Moved_Permanently} (301)";
	$HTTP_CODE[302]="{Moved_Temporarily} (302)";
	$HTTP_CODE[303]="{http_code_see_other} (303)";
	$HTTP_CODE[307]="{Moved_Temporarily} (307)";
	
	$SquidGuardRedirectBehavior=$sock->GET_INFO("SquidGuardRedirectBehavior");
	$SquidGuardRedirectSSLBehavior=$sock->GET_INFO("SquidGuardRedirectSSLBehavior");
	$SquidGuardRedirectHTTPCode=intval($sock->GET_INFO("SquidGuardRedirectHTTPCode"));
	

	if(isset($_GET["without-sock"])){$sock_return="return;";}
	
	$t=time();
	$html="
	<div class=explain style='font-size:18px'>{ufdbgclient_explain}</div>
	
	<table style='width:100%'>
	<tr>
	<td valign='top'><div id='$t-sock'></div></td>
	<td valign='top'>
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td colspan=2>
			". Paragraphe_switch_img("{UfdbUseArticaClient}", "{UfdbUseArticaClient_explain}",
					"UfdbUseArticaClient",$UfdbUseArticaClient,null,1024,"UfdbgClientLOck()")."
			</td>
		</tr>
	<tr>
		<td class=legend style='font-size:22px'>{redirect_behavior}:</td>
		<td>". Field_array_Hash($redirect_behaviorA,"SquidGuardRedirectBehavior-$t",$SquidGuardRedirectBehavior,
				"RedirectBehaCheck()",null,null,"font-size:24px;padding:3px;width:75%",false,"")."</td>
		<td>&nbsp;</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:22px'>{redirect_code}:</td>
		<td>". Field_array_Hash($HTTP_CODE,"SquidGuardRedirectHTTPCode-$t",$SquidGuardRedirectHTTPCode,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>						
							
		<tr>
			<td style='font-size:22px' class=legend>{socket_timeout}:</td>
			<td style='font-size:22px;'>". Field_text("UfdbgclientSockTimeOut", $UfdbgclientSockTimeOut,"font-size:22px;width:110px")."&nbsp;{seconds}</td>
		</tr>	
		<tr>
			<td style='font-size:22px' class=legend>{max_errors_before_restart_service}:</td>
			<td style='font-size:22px;'>". Field_text("UfdbgclientMaxSockTimeOut", $UfdbgclientMaxSockTimeOut,"font-size:22px;width:110px")."&nbsp;{times}</td>
		</tr>												
		<tr>
			<td style='font-size:22px' class=legend>{enable_local_cache}:</td>
			<td>". Field_checkbox_design("UfdbArticaClientLocalCache", 1,$UfdbArticaClientLocalCache)."</td>
		</tr>							
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveufdbGuardClient()",40)."</td>
		</tr>	
		</table>
		</div>					

		<p>&nbsp;</p>			
		<div style='width:98%' class=form>
		<table style='width:100%'>			
		<tr>
			<td colspan=2>
			". Paragraphe_switch_img("{UseRemoteUfdbguardService}", "{ufdbclient_parms_explain}",
					"UseRemoteUfdbguardService",$datas["UseRemoteUfdbguardService"],null,750)."
			</td>
			
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{remote_server}:</td>
			<td>". Field_text("remote_server",$datas["remote_server"],"font-size:22px;width:265px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{remote_port}:</td>
			<td>". Field_text("remote_port",$datas["remote_port"],"font-size:22px;width:110px")."</td>
		</tr>
		
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","SaveufdbGuardClient()",28)."</td>
		</tr>	
		</table>
		</div>
	</td>
	</tr>
	</table>
	
	
	<script>
	var x_SaveufdbGuardClient=function (obj) {
		RefreshTab('main_ufdbguard_config');
		Loadjs('squid.compile.progress.php?ask=yes&restart=yes');
	}	

	function RemoteUfdbCheck(){
		$sock_return
		LoadAjaxTiny('$t-sock','$page?TestsSocket=yes&t=$t');
		
	}
	
	function RedirectBehaCheck(){
		var SquidGuardRedirectBehavior= document.getElementById('SquidGuardRedirectBehavior-$t').value;
		if( SquidGuardRedirectBehavior =='url-rewrite'){
			document.getElementById('SquidGuardRedirectHTTPCode-$t').disabled=true;
		}
	}
	
	function SaveufdbGuardClient(){
		var XHR = new XHRConnection();
		
		XHR.appendData('UfdbgclientSockTimeOut',document.getElementById('UfdbgclientSockTimeOut').value);
		XHR.appendData('UfdbgclientMaxSockTimeOut',document.getElementById('UfdbgclientMaxSockTimeOut').value);
		
		
		XHR.appendData('SquidGuardRedirectBehavior',document.getElementById('SquidGuardRedirectBehavior-$t').value);
		XHR.appendData('SquidGuardRedirectHTTPCode',document.getElementById('SquidGuardRedirectHTTPCode-$t').value);
		
		
		XHR.appendData('UfdbUseArticaClient',document.getElementById('UfdbUseArticaClient').value);
		XHR.appendData('remote_server',document.getElementById('remote_server').value);
   		XHR.appendData('remote_port',document.getElementById('remote_port').value);
   		XHR.appendData('UseRemoteUfdbguardService',document.getElementById('UseRemoteUfdbguardService').value);		
   		XHR.sendAndLoad('$page', 'POST',x_SaveufdbGuardClient);
	}	
	
	function UfdbgClientLOck(){
		var UfdbUseArticaClient=document.getElementById('UfdbUseArticaClient').value;
		document.getElementById('UfdbgclientSockTimeOut').disabled=true;
		document.getElementById('UfdbgclientMaxSockTimeOut').disabled=true;
		if(UfdbUseArticaClient==1){
			document.getElementById('UfdbgclientSockTimeOut').disabled=false;
			document.getElementById('UfdbgclientMaxSockTimeOut').disabled=false;
		}
	}
	
	
	RemoteUfdbCheck();
	UfdbgClientLOck();
	RedirectBehaCheck();
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
		$array["behavior"]='{behavior}';
		$array["popup"]='{service_parameters}';
		
		if(!$users->WEBSTATS_APPLIANCE){$array["ufdbperf"]='{performances}';}
		if(!$users->WEBSTATS_APPLIANCE){$array["ufdbclient"]='{client_parameters}';}
		if($EnableRemoteStatisticsAppliance==1){unset($array["popup"]);unset($array["behavior"]);}
		$array["notifs"]='{notifications}';
		$array["import-export"]="{import}/{export}";
		
	while (list ($num, $ligne) = each ($array) ){
		if($num=="notifs"){
			
			$tab[]="<li><a href=\"ufdbguard.smtp.notif.php?$num=yes\"><span style='font-size:24px'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		 $tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:24px'>$ligne</span></a></li>\n";
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
	if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=30;}

	for($i=0;$i<100;$i++){
		$url_rewrite_children_startup[$i]=" $i ";
		
	}
	
	$url_rewrite_children_concurrency[0]=" 0 " ;
	$url_rewrite_children_concurrency[2]=" 2 ";
	$url_rewrite_children_concurrency[3]=" 3 ";
	$url_rewrite_children_concurrency[4]=" 4 ";
	

	$SquidGuardUseRefreshDomainList=intval($sock->GET_INFO("SquidGuardUseRefreshDomainList"));
	
	$SquidGuardUseRefreshDomainList_p=Paragraphe_switch_img("{SquidGuardUseRefreshDomainList}", 
			"{SquidGuardUseRefreshDomainList_explain}","SquidGuardUseRefreshDomainList",
			$SquidGuardUseRefreshDomainList,null,1300);
	
	
	$html="
	<div id='anim-$t'></div>
	<div style='width:98%' class=form>
	<div class=explain style='font-size:22px'>{ufdb_perfs_explain}</div>
	
	$SquidGuardUseRefreshDomainList_p
	
	<table style='width:100%'>
	
	<tr>			
		<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_MAX}:</td>
		<td style='font-size:22px'>".Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_max",
	$datas["url_rewrite_children_max"],null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>	
	<tr>			
		<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_STARTUP}:</td>
		<td style='font-size:22px'>".Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_startup",
	$datas["url_rewrite_children_startup"],null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_IDLE}:</td>
		<td style='font-size:22px'>". Field_array_Hash($url_rewrite_children_startup, 
	"url_rewrite_children_idle",
	$datas["url_rewrite_children_idle"],null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{CHILDREN_CONCURRENCY}:</td>
		<td style='font-size:22px'>". Field_array_Hash($url_rewrite_children_concurrency, 
	"url_rewrite_children_concurrency",
	$datas["url_rewrite_children_concurrency"],null,null,0,"font-size:22px;width:90px;")."&nbsp;{processes}</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
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
		
		XHR.appendData('SquidGuardUseRefreshDomainList',
		document.getElementById('SquidGuardUseRefreshDomainList').value);
		XHR.appendData('url_rewrite_children_max',document.getElementById('url_rewrite_children_max').value);
    	XHR.appendData('url_rewrite_children_startup',document.getElementById('url_rewrite_children_startup').value);
    	XHR.appendData('url_rewrite_children_idle',document.getElementById('url_rewrite_children_idle').value);
    	XHR.appendData('url_rewrite_children_concurrency',document.getElementById('url_rewrite_children_concurrency').value);		
    	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>				
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function behavior(){
	if(!class_exists("compile_ufdbguard")){include_once("ressources/class.compile.ufdbguard.inc");}
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$squid=new squidbee();
	$url_rewrite_bypass=$squid->url_rewrite_bypass;
	$t=time();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ufdbclass=new compile_ufdbguard();
	$datas=$ufdbclass->SetDefaultsConfig($datas);
	$UfdbReloadBySchedule=$sock->GET_INFO("UfdbReloadBySchedule");
	if(!is_numeric($UfdbReloadBySchedule)){$UfdbReloadBySchedule=1;}

	
	$html="<div style='width:98%' class=form>
	". Paragraphe_switch_img("{reload_byschedule}", "{ufdb_reload_byschedule_explain}",
			"UfdbReloadBySchedule-$t",$UfdbReloadBySchedule,null,850)."<p>&nbsp;</p>".
	Paragraphe_switch_img("{url_rewrite_bypass}", "{url_rewrite_bypass_explain}",
			"url_rewrite_bypass-$t",$url_rewrite_bypass,null,850)."<p>&nbsp;</p>
	". Paragraphe_switch_img("{url-lookup-result-during-database-reload}", "{url-lookup-result-during-database-reload-explain}",
			"url-lookup-result-during-database-reload-$t",$datas["url-lookup-result-during-database-reload"],null,850)."<p>&nbsp;</p>

	". Paragraphe_switch_img("{url-lookup-result-when-fatal-error}", "{url-lookup-result-when-fatal-error-explain}",
			"url-lookup-result-when-fatal-error-$t",$datas["url-lookup-result-when-fatal-error"],null,850)."<p>&nbsp;</p>
					
					

		
		<div style='text-align:right;margin-top:20px'><hr>". button("{apply}", "Save$t()",26)."</td>
				
			
	</div>
<script>

	var xSave$t=function (obj) {
		Loadjs('dansguardian2.compile.php');
		RefreshTab('main_ufdbguard_config');
	}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('url_rewrite_bypass',document.getElementById('url_rewrite_bypass-$t').value);
	XHR.appendData('reload',document.getElementById('url-lookup-result-during-database-reload-$t').value);
	XHR.appendData('error',document.getElementById('url-lookup-result-when-fatal-error-$t').value);
	XHR.appendData('UfdbReloadBySchedule',document.getElementById('UfdbReloadBySchedule-$t').value);
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
	if(!class_exists("compile_ufdbguard")){include_once("ressources/class.compile.ufdbguard.inc");}
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ufdbclass=new compile_ufdbguard();
	$datas=$ufdbclass->SetDefaultsConfig($datas);
	
	$squid=new squidbee();
	$users=new usersMenus();
	$url_rewrite_bypass=$squid->url_rewrite_bypass;
	$ufdbguardReloadTTL=$sock->GET_INFO("ufdbguardReloadTTL");
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	
	
	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	$UfdbGuardThreads=intval($sock->GET_INFO("UfdbGuardThreads"));
	if(!is_numeric($UfdbGuardThreads)==0){$UfdbGuardThreads=65;}
	if($UfdbGuardThreads>140){$UfdbGuardThreads=140;}
	
	
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	
	if(!is_numeric($datas["allow-unknown-protocol-over-https"])){$datas["allow-unknown-protocol-over-https"]=1;}
	
	
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
	<td colspan=2><span style='font-size:32px'>{ssl}:</span>
	<tr>
		<td class=legend style='font-size:22px'>{enforce-https-with-hostname}:</td>
		<td>". Field_checkbox_design("enforce-https-with-hostname",1,$datas["enforce-https-with-hostname"])."</td>
		<td width=1%>". help_icon("{UFDBGUARD_SSL_OPTS}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{enforce-https-official-certificate}:</td>
		<td>". Field_checkbox_design("enforce-https-official-certificate",1,$datas["enforce-https-official-certificate"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{https-prohibit-insecure-sslv2}:</td>
		<td>". Field_checkbox_design("https-prohibit-insecure-sslv2",1,$datas["https-prohibit-insecure-sslv2"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{allow-unknown-protocol-over-https}:</td>
		<td>". Field_checkbox_design("allow-unknown-protocol-over-https",1,$datas["allow-unknown-protocol-over-https"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>				
				
	
	</table>
	</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	
	<td colspan=3><span style='font-size:32px'>{UFDBGUARD_SERVICE_OPTS}:</span><p>&nbsp;</p>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:22px'>{UfdbDatabasesInMemory}:</td>
		<td>". Field_checkbox_design("UfdbDatabasesInMemory",1,$UfdbDatabasesInMemory)."</td>
		<td width=1%>". help_icon("{UfdbDatabasesInMemory_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{DisableExpressionLists}:</td>
		<td>". Field_checkbox_design("DisableExpressionList",1,$datas["DisableExpressionList"])."</td>
		<td width=1%>". help_icon("{DisableExpressionLists_explain}")."</td>
	</tr>								
		
	<tr>
		<td class=legend style='font-size:22px'>{verbose_mode}:</td>
		<td>". Field_checkbox_design("DebugAll",1,$datas["DebugAll"],"")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{minimum_reload_interval}:</td>
		<td style='font-size:22px'>". Field_text("ufdbguardReloadTTL",$ufdbguardReloadTTL,"font-size:22px;width:110px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>Threads:</td>
		<td style='font-size:22px'>". Field_text("UfdbGuardThreads",$UfdbGuardThreads,"font-size:22px;width:110px")."&nbsp;{threads}</td>
		<td width=1%>&nbsp;</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:22px'>{enable_tcpsockets}:</td>
		<td>". Field_checkbox_design("tcpsockets",1,$datas["tcpsockets"],"tcpsocketsCheck()")."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{listen_address}:</td>
		<td>". Field_array_Hash($ips,"listen_addr",$datas["listen_addr"],"style:font-size:22px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{listen_port}:</td>
		<td>". Field_text("listen_port",$datas["listen_port"],"font-size:22px;width:120px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>			

	<tr>
		<td class=legend style='font-size:22px'>{check-proxy-tunnel}:</td>
		<td>". Field_checkbox_design("check-proxy-tunnel",1,$datas["check-proxy-tunnel"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{NoMalwareUris}:</td>
		<td>". Field_checkbox_design("NoMalwareUris",1,$datas["NoMalwareUris"])."</td>
		<td width=1%>". help_icon("{NoMalwareUris_explain}")."</td>
	</tr>				

	<tr>
		<td class=legend style='font-size:22px'>{strip-domain-from-username}:</td>
		<td>". Field_checkbox_design("strip-domain-from-username",1,$datas["strip-domain-from-username"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{refreshuserlist}:</td>
		<td style='font-size:22px'>". Field_text("refreshuserlist",$datas["refreshuserlist"],"font-size:22px;width:120px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{refreshdomainlist}:</td>
		<td style='font-size:22px'>". Field_text("refreshdomainlist",$datas["refreshdomainlist"],"font-size:22px;width:120px")."&nbsp;{minutes}</td>
		<td width=1%>&nbsp;</td>
	</tr>					
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveufdbGuardSSL()",36)."</td>
	</tr>	
	</table>
	</div>
	</div>
	</div>
	<script>
	var x_SaveufdbGuardSSLl=function (obj) {
		RefreshTab('main_ufdbguard_config');
		Loadjs('ufdbguard.restart.progress.php?ask=yes');
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


		if(document.getElementById('check-proxy-tunnel').checked){
    		XHR.appendData('check-proxy-tunnel',1);}else{
    		XHR.appendData('check-proxy-tunnel',0);}      

		if(document.getElementById('tcpsockets').checked){
    		XHR.appendData('tcpsockets',1);}else{
    		XHR.appendData('tcpsockets',0);} 
    		
    		
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
    	
    	
    		
 		
    	XHR.sendAndLoad('$page', 'POST',x_SaveufdbGuardSSLl);
	}	
	tcpsocketsCheck();
	CHECKWEBSTATS_APPLIANCE();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function url_rewrite_bypass_save(){
	if(!class_exists("compile_ufdbguard")){include_once("ressources/class.compile.ufdbguard.inc");}
	$squid=new squidbee();
	$squid->url_rewrite_bypass=$_POST["url_rewrite_bypass"];
	$squid->SaveToLdap();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$ufdbclass=new compile_ufdbguard();
	$datas=$ufdbclass->SetDefaultsConfig($datas);
	
	$sock->SET_INFO("UfdbReloadBySchedule", $_POST["UfdbReloadBySchedule"]);
	$datas["url-lookup-result-during-database-reload"]=$_POST["reload"];
	$datas["url-lookup-result-when-fatal-error"]=$_POST["error"];
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");

	
}


function save_ssl(){
	$sock=new sockets();
	
	
	if(isset($_POST["UfdbUseArticaClient"])){$sock->SET_INFO("UfdbUseArticaClient", $_POST["UfdbUseArticaClient"]);}
	if(isset($_POST["UfdbgclientSockTimeOut"])){$sock->SET_INFO("UfdbgclientSockTimeOut", $_POST["UfdbgclientSockTimeOut"]);}
	if(isset($_POST["UfdbgclientMaxSockTimeOut"])){$sock->SET_INFO("UfdbgclientMaxSockTimeOut", $_POST["UfdbgclientMaxSockTimeOut"]);}
	
	
	if(isset($_POST["SquidGuardRedirectBehavior"])){$sock->SET_INFO("SquidGuardRedirectBehavior", $_POST["SquidGuardRedirectBehavior"]);}
	if(isset($_POST["SquidGuardRedirectHTTPCode"])){$sock->SET_INFO("SquidGuardRedirectHTTPCode", $_POST["SquidGuardRedirectHTTPCode"]);}
	
	if(isset($_POST["SquidGuardUseRefreshDomainList"])){
		$sock->SET_INFO("SquidGuardUseRefreshDomainList", $_POST["SquidGuardUseRefreshDomainList"]);
	}
	 
	
	
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
?>