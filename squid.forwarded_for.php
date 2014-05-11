<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["forwarded_for"])){save();exit;}
	js();
	
	
	
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("x-Forwarded-For");
	$page=CurrentPageName();
	$html="YahooWin3('405','$page?popup=yes','$title');";
	echo $html;	
}



function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();
	$users=new usersMenus();	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	
	$arrayParams["on"]="{enabled}";
	$arrayParams["off"]="{unknown}";
	$arrayParams["transparent"]="{disabled}";
	$arrayParams["delete"]="{anonymous}";
	$arrayParams["truncate"]="{hide}";
	$t=time();
	
	
	
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
		<tr>
			<td class=legend style='font-size:16px'>x-Forwarded-For:</td>
			<td>". Field_array_Hash($arrayParams,"x-Forwarded-For",$squid->forwarded_for,null,null,0,"font-size:16px")."</td>
		</tr>
		<tr>
			<td align='right' colspan=2><hr>". button("{apply}", "SaveSNMP$t()","18px")."
			<div style='text-align:right'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=398','1024','900');\"
				style=\"font-size:16px;font-weight;bold;text-decoration:underline\">{online_help}</a></div>		
					
			</td>
		</tr>
	</table>
	</div>
	<script>
	var x_SaveSNMP$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('$t').innerHTML='';
		YahooWin3Hide();
		Loadjs('squid.restart.php?onlySquid=yes&onlyreload=yes&ApplyConfToo=yes&ask=yes',true);
	}	
	
	function SaveSNMP$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}	
		var XHR = new XHRConnection();
		XHR.appendData('forwarded_for',document.getElementById('x-Forwarded-For').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function save(){
	$squid=new squidbee();
	$squid->forwarded_for=$_POST["forwarded_for"];
	$squid->SaveToLdap(true);
	
}