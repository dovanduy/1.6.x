<?php
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
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["prepare-js"])){cachesave_js();exit;}
	if(isset($_GET["prepare-popup"])){cachesave_popup();exit;}
	if(isset($_GET["prepare-1"])){cachesave_1();exit;}
	if(isset($_GET["prepare-2"])){cachesave_2();exit;}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["start"])){restart();exit;}
	if(isset($_GET["logs"])){logs();exit;}
	if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function cachesave_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$cache_mem=$_GET["cache_mem"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{compiling}....");
	echo "YahooWinBrowse('500','$page?prepare-popup=yes&t=$t','$title')";


}

function cachesave_popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");

	$html="
	<div id='progress-$t' style='height:50px'></div>
	<center id='step$t-0' style='font-size:16px'>$text</center>
	<center id='step$t-1' style='font-size:16px'></center>
	<script>
		function Step1$t(){
			$('#progress-$t').progressbar({ value: 15 });
			LoadAjaxSilent('step$t-1','$page?prepare-1=yes&t=$t');
		}
		$('#progress-$t').progressbar({ value: 5 });
		setTimeout(\"Step1$t()\",1000);
	</script>
	
	
	";
echo $html;
}

function cachesave_1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$sock->getFrameWork("squid.php?prepare-build=yes");
	$text=$tpl->_ENGINE_parse_body("{please_wait_checking_settings}...");
$html="
	
	<center id='step$t-2' style='font-size:16px'>$text</center>
	<center id='step$t-3' style='font-size:16px'></center>
	<script>
		function Step2$t(){
			$('#progress-$t').progressbar({ value: 50 });
			LoadAjaxSilent('step$t-3','$page?prepare-2=yes&t=$t');
		}
		setTimeout(\"Step2$t()\",1000);
	</script>
	
	
	";
echo $html;	

}

function cachesave_2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?prepare-build-tests=yes")));
	$failed=0;
	while (list ($index, $ligne) = each ($datas) ){
		if(preg_match("#FAILED#", $ligne)){
			$failed=1;
			$datas[]="{settings_will_not_be_applied}";
			$error=$tpl->javascript_parse_text(@implode("\n", $datas));
			$error=str_replace("'", "`", $error);
			break;
		}
		
		if(preg_match("#SUCCESS#", $ligne)){
			$error=$tpl->javascript_parse_text("{success}");
			
		}
	}
	
	
	$html="
	<script>
	function StepF$t(){
		$('#progress-$t').progressbar({ value: 100 });
		if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
		var failed=$failed;
		YahooWinBrowseHide();
		if(failed==1){alert('$error');return;}
		
		Loadjs('$page?onlySquid=yes&setTimeout=5000');
	}
	setTimeout(\"StepF$t()\",1000);
	</script>
	
	
	";	
	echo $html;	
}


function js(){
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{restart_all_services}");
	@unlink(dirname(__FILE__)."/ressources/logs/web/squid.status.html");
	@unlink(dirname(__FILE__)."/ressources/logs/web/squid_stores_status.html");
	@unlink(dirname(__FILE__)."/ressources/logs/web/admin.index.status.html");
	
	$compile_squid_ask=$tpl->javascript_parse_text("{compile_squid_ask}");
	if($_GET["ask"]=="yes"){
		$warn="if(!confirm('$compile_squid_ask')){return;}";
	}
	
	if($_GET["reconfigure"]=="yes"){
		$reconfigure="&ApplyConfToo=yes";
	}	
	
	if(isset($_GET["onlySquid"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{restart_service}");
		$onlySquid="&onlySquid=yes";
	}
	
	if(isset($_GET["firewall"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{reconfigure_transparent_rules}");
		$onlySquid="&firewall=yes";
		
	}
	
	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}	
	
	if(isset($_GET["onlyreload"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{reload_service}");
		$onlySquid="&onlyreload=yes";
	}	
	
	if(isset($_GET["CheckCaches"])){
		$onlySquid="&CheckCaches=yes";
		$warn=$tpl->javascript_parse_text("{check_caches_warning}");
		$warn="if(!confirm('$warn')){return;}";
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{check_caches}");
		
	}
	
	$page=CurrentPageName();
	$html="
	
	function squid_restart_proxy_load$t(){
			$warn
			YahooWin3('998','$page?popup=yes$onlySquid&t=$t&setTimeout={$_GET["setTimeout"]}$ApplyConfToo$reconfigure','$title');
		
		}
		
	function GetLogs$t(){
		Loadjs('$page?logs=yes&t=$t&setTimeout={$_GET["setTimeout"]}');
		if(document.getElementById('IMAGE_STATUS_INFO')){
			Loadjs('admin.tabs.php?refresh-status-js=yes&nocache=yes');
			
		}
	}
		
	squid_restart_proxy_load$t();";
	
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";
	@unlink($cachefile);
	$title="{PLEASE_WAIT_RESTARTING_ALL_SERVICES}";
	if(isset($_GET["onlySquid"])){
		$title="{please_wait}, {restarting_proxy_service}";
		$onlySquid="&onlySquid=yes";
	}	
	
	if(isset($_GET["onlyreload"])){
		$onlySquid="&onlyreload=yes";
		$title="{please_wait_reloading_service}";
	}

	if(isset($_GET["CheckCaches"])){
		$onlySquid="&CheckCaches=yes";
		$title="{please_wait_check_caches}";
	}	
	
	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}	
	
	if(isset($_GET["firewall"])){
		$onlySquid="&firewall=yes";
		$title="{please_wait}, {reconfigure_transparent_rules}";
	}	
	$title=$tpl->_ENGINE_parse_body($title);
	$html="
	<input type='hidden' id='stop-$t' value='0'>
	<center ><div id='title-$t' style='font-size:28px;margin:10px;margin-bottom:20px'>$title</div></center>
	<div id='progress-$t' style='height:50px'></div>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:97%;min-height:450px;' id='squid-restart'>
	</div>
	
	<script>
		$('#progress-$t').progressbar({ value: 5 });
		LoadAjax('squid-restart','$page?start=yes$onlySquid&t=$t$ApplyConfToo');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart(){
	
	$sock=new sockets();
	$t=$_GET["t"];
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	

	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}	
	
	
	$cmd="cmd.php?force-restart-squid=yes$ApplyConfToo";
	if(isset($_GET["onlySquid"])){
		$ApplyConfToo="&ApplyConfToo=yes";
		$cmd="cmd.php?force-restart-squidonly=yes$ApplyConfToo&force=yes";
	}
	
	if(isset($_GET["onlyreload"])){
		$cmd="squid.php?squid-k-reconfigure=yes&force=yes$ApplyConfToo";
		
	}
	
	if(isset($_GET["CheckCaches"])){
		$cmd="squid.php?squid-z-reconfigure=yes&force=yes";
	}
	
	if(isset($_GET["firewall"])){
		$cmd="squid.php?firewall=yes";
	}	
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px;width:100%'><div>{proxy_clients_was_notified}</div></center>");
		return;
	}
	
	$sock->getFrameWork($cmd);
	
	echo "
	<center id='animate-$t'></center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
			setTimeout(\"GetLogs$t()\",1000);
	</script>";
	
	
}

function Filllogs(){
	$datas=explode("\n",@file_get_contents("ressources/logs/web/restart.squid"));
	$t=$_POST["t"];
	
	krsort($datas);
	echo @implode("\n", $datas);
}

function logs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$datas=@file_get_contents("ressources/logs/web/restart.squid");
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";
	$array=unserialize(@file_get_contents($cachefile));
	header("content-type: application/x-javascript");
	
	$pourc=intval($array["POURC"]);
	$text=$tpl->javascript_parse_text($array["TEXT"]);
	if($text==null){$text=$tpl->javascript_parse_text("{please_wait}...");}
	if($pourc>0){
		echo "$('#progress-$t').progressbar({ value: $pourc });";
		if($text<>null){
			echo "
			if(	document.getElementById('title-$t') ){	
				document.getElementById('title-$t').innerHTML='$text';
			}";
		}
	}
	
	if(strlen($datas)<10){
		
		echo "
		if(document.getElementById('textToParseCats-$t')){
			Loadjs('$page?logs=yes&t=$t');
		}";
		return;
	}
	$strlenOrg=$_GET["strlen"];
	if(!is_numeric($strlenOrg)){$strlenOrg=0;}
	$strlen=strlen($datas);
	
	if(is_numeric($_GET["setTimeout"])){
		$setTimeout="setTimeout('DefHide()','{$_GET["setTimeout"]}');";
	}
	
	if($strlenOrg<>$strlen){
		echo "
				
			function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				if(document.getElementById('stop-$t').value==1){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
				LayersTabsAllAfter();
				
			
			}
		
		
			var x_Fill$tt= function (obj) {
				var res=obj.responseText;
				if (res.length>3){
					document.getElementById('textToParseCats-$t').value=res;
					LayersTabsAllAfter();	
					
					}
					
				
			}				
		
		
			function Fill$tt(){
				if(!YahooWin3Open()){return;}
				document.getElementById('animate-$t').innerHTML='';
				var XHR = new XHRConnection();
		   	 	XHR.appendData('Filllogs', 'yes');
		   	 	XHR.appendData('t', '$t');
		   	 	XHR.setLockOff();
			    XHR.sendAndLoad('$page', 'POST',x_Fill$tt,false); 
				setTimeout(\"Refresh$tt()\",5000);
			}
				
			Fill$tt();	
		";
	}else{

		echo "function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
			
			}
			
			function DefHide(){
				YahooWin3Hide();
			}
			
			setTimeout(\"Refresh$tt()\",3000);\n$setTimeout";
			
		
		
	}
}




?>