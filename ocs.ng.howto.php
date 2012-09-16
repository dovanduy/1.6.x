<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ocs.inc');
	
	

	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	
	
page();


function page(){
	$sock=new sockets();
	$users=new usersMenus();
	$OCSWebPort=$sock->GET_INFO("OCSWebPort");
	$OCSWebPortSSL=$sock->GET_INFO("OCSWebPortSSL");
	if($OCSWebPort==null){$OCSWebPort=9080;}
	if($OCSWebPortSSL==null){$OCSWebPortSSL=$OCSWebPort+50;}
	if($ocswebservername==null){$ocswebservername=$users->hostname;}
	$UseFusionInventoryAgents=$sock->GET_INFO("UseFusionInventoryAgents");
	if($UseFusionInventoryAgents==null){$UseFusionInventoryAgents=1;}
		
	if($UseFusionInventoryAgents==1){
		$http_suffix="https";
		$OCSWebPortLink=$OCSWebPortSSL;
		}else{
			$http_suffix="http";
			$OCSWebPortLink=$OCSWebPort;
		}
	

	$www="$http_suffix://$ocswebservername:$OCSWebPortLink/ocsinventory";
	$silentinstall="OCS-NG-Windows-Agent-Setup.exe /S /NOW /SERVER=$www /NOTAG /NP";
	
	$page=CurrentPageName();
	$tpl=new templates();
	$uti1="http://forge.fusioninventory.org/projects/fusioninventory-agent/wiki/Platforms_tested";
	$uti2="http://www.ocsinventory-ng.org/en/download/download-agent.html";
	$html="
	<div style='font-size:14px' class=explain>{OCSNG_HOWTO_1}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%><img src='img/icon-download.gif'></td>
		<td width=99%'>
		<div style='font-size:14px;margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:s_PopUpFull('$uti1',800,800);\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>{download}:FusionInventory Agents</a></div>
		</td>
	</tr>
	<tr>
		<td width=1%><img src='img/icon-download.gif'></td>
		<td width=99%'>	
	<div style='font-size:14px;margin-top:10px'><a href=\"javascript:blur()\" OnClick=\"javascript:s_PopUpFull('$uti2',800,800);\"
	style='font-size:14px;font-weight:bold;text-decoration:underline'>{download}:OCS Inventory Official Agents</a></div>
	</td>
	</tr>
	</tbody>
	</table>
	<br>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td width=1%><img src='img/ocs-nagent1-howto.png'></td>
	<td width=99% valign='top'><div style='font-size:14px' class=explain>{ocs-nagent1-howto}</div></td>
	</tr>
	<tr><td colspan=2><center><code style='font-size:16px;font-weight:bold'>$www</code></center></td></tr>
	<tr><td colspan=2><center><code style='font-size:16px;font-weight:bold'>{silent_install}<br>$silentinstall</code></center></td></tr>
	</tbody>
	</table>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
