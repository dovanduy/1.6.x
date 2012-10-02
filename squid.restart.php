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
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["start"])){restart();exit;}
	if(isset($_GET["logs"])){logs();exit;}
js();


function js(){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{restart_all_services}");
	if(isset($_GET["onlySquid"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{restart_service}");
		$onlySquid="&onlySquid=yes";
	}
	$page=CurrentPageName();
	$html="
	
var tantS=0;


	function demarreSsquid(){
	
	   tantS = tantS+1;
	   if(!YahooWin3Open()){return;}
		if (tantS < 10 ) {                           
	     setTimeout(\"demarreSsquid()\",1000);
	      } else {
	               tantS = 0;
	               SquidChargeLogs();
	               demarreSsquid();
	   }
	}
	
		function squid_restart_proxy_load(){
			YahooWin3('700','$page?popup=yes$onlySquid','$title');
		
		}
		
	function SquidChargeLogs(){
		LoadAjax('squid-restart','$page?logs=yes');
		if(document.getElementById('squid-services')){
			LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
		}
	}
		
	squid_restart_proxy_load();";
	
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	
	
	if(isset($_GET["onlySquid"])){

		$onlySquid="&onlySquid=yes";
	}	
	
	$html="
	<div style='font-size:16px'>{PLEASE_WAIT_RESTARTING_ALL_SERVICES}</div>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:95%;height:450px;overflow:auto' id='squid-restart'>
	</div>
	
	<script>
		LoadAjax('squid-restart','$page?start=yes$onlySquid');
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restart(){
	
	$sock=new sockets();
	
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	
	$cmd="cmd.php?force-restart-squid=yes";
	if(isset($_GET["onlySquid"])){
		$cmd="cmd.php?force-restart-squidonly=yes";
	}
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		
		echo $tpl->_ENGINE_parse_body("
		<center style='font-size:18px'>{proxy_clients_was_notified}</center>");
		return;
	}
	
	$sock->getFrameWork($cmd);
	
	echo "
	<center><img src=\"img/wait_verybig.gif\"></center>
	<script>demarreSsquid();</script>";
	
	
}

function logs(){
	
$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99%'>
<thead class='thead'>
	<tr>
		
		<th width=99% colspan=2>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	$f=explode("\n", @file_get_contents("ressources/logs/web/restart.squid"));
	while (list ($num, $val) = each ($f) ){
		if(trim($val)==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$html=$html."
		<tr class=$classtr>
			<td width=99% style='font-size:13px'>$val</td>
		</tr>
		";		
	}
	
	$html=$html."</tbody></table>
	";
	echo $html;
}




?>