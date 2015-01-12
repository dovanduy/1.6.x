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

if(isset($_GET["status"])){status();exit;}

tabs();


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	$EnableRemoteSyslogStatsAppliance=intval($sock->GET_INFO("EnableRemoteSyslogStatsAppliance"));
	
	if($EnableSquidRemoteMySQL==1){
		$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
		
		
		if(!is_numeric($WizardStatsAppliance["SSL"])){$WizardStatsAppliance["SSL"]=1;}
		if(!is_numeric($WizardStatsAppliance["PORT"])){$WizardStatsAppliance["PORT"]=9000;}
		
		if($WizardStatsAppliance["SSL"]){$WizardStatsAppliance["SSL"]="{yes}";}else{$WizardStatsAppliance["SSL"]="{no}";}
		if($EnableRemoteSyslogStatsAppliance==1){$EnableRemoteSyslogStatsAppliance="{yes}";}else{$EnableRemoteSyslogStatsAppliance="{no}";}
		
		$squidRemostatisticsServer=$sock->GET_INFO("squidRemostatisticsServer");
		$squidRemostatisticsPort=$sock->GET_INFO("squidRemostatisticsPort");
		$squidRemostatisticsUser=$sock->GET_INFO("squidRemostatisticsUser");
		$squidRemostatisticsPassword=$sock->GET_INFO("squidRemostatisticsPassword");
		
		
		
		$html="
		<div style='font-size:42px'>{webproxy_statistics_appliance}</div>
		<div class=text-info style='font-size:18px' id='STATISTICS_APPLIANCE_EXPLAIN_DIV'>{STATISTICS_APPLIANCE_EXPLAIN}</div>
		<div style='width:98%' class=form>		
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:32px'>{mysql_server}:</td>
			<td style='font-size:32px'>{$squidRemostatisticsServer}:$squidRemostatisticsPort</td>
		</tr>
		<tr>
			<td class=legend style='font-size:32px'>{mysql_username}:</td>
			<td style='font-size:32px'>{$squidRemostatisticsUser}</td>
		</tr>		
		<tr>
		<td class=legend style='font-size:32px'>{hostname}:</td>
		<td style='font-size:32px'>{$WizardStatsAppliance["SERVER"]}</td>
		
		</tr>		
		
		<tr>
		<td class=legend style='font-size:32px'>{listen_port}:</td>
		<td style='font-size:32px'>{$WizardStatsAppliance["PORT"]}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:32px'>{use_ssl}:</td>
			<td style='font-size:32px'>{$WizardStatsAppliance["SSL"]}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:32px'>{disconnected_mode}:</td>
		<td style='font-size:32px'>$EnableRemoteSyslogStatsAppliance</td>
		</tr>
		<tr><td colspan=2 align='right' style='font-size:32px'><hr>". 
		button("{disconnect}","Loadjs('squid.stats-appliance.disconnect.php')",40)."&nbsp;|&nbsp;".
		button("{change}","Loadjs('squid.stats-appliance.php');",40)."</td>
		</tr>
		</table>
		</div>";
		
		
	}else{
		$html="
		<div style='font-size:26px'>{webproxy_statistics_appliance}</div>
		<div class=text-info style='font-size:18px'>{STATISTICS_APPLIANCE_EXPLAIN}</div>
		<center style='width:100%'>
		<center style='margin:50px;width:70%' class=form>
				<p>&nbsp;</p>
				". button("{launch_wizard}","Loadjs('squid.stats-appliance.php');",30)."<p>&nbsp;</p>
		</center></center>
		";
		
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function tabs(){
	
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$array["status"]="{status}";
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		
	
	
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:20px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	
	}
	
	$id=time();
	echo build_artica_tabs($html, "artica_squid_stats_tabs");
}

