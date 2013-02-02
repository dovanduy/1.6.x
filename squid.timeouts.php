<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	if(isset($_POST["dead_peer_timeout"])){save();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{timeouts}");
	$page=CurrentPageName();
	$html="YahooWin3('700','$page?popup=yes','$title');";
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

	$t=time();
	
	
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	
	
	


		<tr>
			<td align='right' class=legend style='font-size:16px'>{client_lifetime}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("client_lifetime-$t",$squid->client_lifetime,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{client_lifetime_text}',true)."</td>
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:16px'>{shutdown_lifetime}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("shutdown_lifetime-$t",$squid->shutdown_lifetime,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{shutdown_lifetime_text}',true)."</td>
		</tr>					
		<tr>
			<td align='right' class=legend style='font-size:16px'>{read_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("read_timeout-$t",$squid->read_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{read_timeout_text}',true)."</td>
		</tr>	
					 	
					
		<tr>
			<td align='right' class=legend style='font-size:16px'>{dead_peer_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("dead_peer_timeout-$t",$squid->dead_peer_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{dead_peer_timeout_text}',true)."</td>
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:16px'>{dns_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("dns_timeout-$t",$squid->dns_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{dns_timeout_text}',true)."</td>
		</tr>		
		<tr>
			<td align='right' class=legend style='font-size:16px'>{connect_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("connect_timeout-$t",$squid->connect_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{connect_timeout_text}',true)."</td>
		</tr>		
		<tr>
			<td align='right' class=legend style='font-size:16px'>{peer_connect_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("peer_connect_timeout-$t",$squid->peer_connect_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{peer_connect_timeout_text}',true)."</td>
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:16px'>{persistent_request_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("persistent_request_timeout-$t",$squid->persistent_request_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{persistent_request_timeout_text}',true)."</td>
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:16px'>{pconn_timeout}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("pconn_timeout-$t",$squid->pconn_timeout,'width:60%;font-size:16px')."&nbsp;{seconds}</td>
			<td width=1%>" . help_icon('{pconn_timeout_text}',true)."</td>
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:16px'>{incoming_rate}</strong>:</td>
			<td align='left' style='font-size:16px'>" . Field_text("incoming_rate-$t",$squid->incoming_rate,'width:60%;font-size:16px')."&nbsp;</td>
			<td width=1%>" . help_icon('{incoming_rate_text}',true)."</td>
		</tr>
					 	
					
			
		<tr>
		<td align='right' colspan=3>
			<hr>". button("{apply}","SaveSNMP$t()",18)."
		</td>
		</tr>
	</table>
	
	<script>
	var x_SaveSNMP$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('$t').innerHTML='';
		YahooWin3Hide();
	}	
	
	function SaveSNMP$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}	
		var XHR = new XHRConnection();
		XHR.appendData('dead_peer_timeout',document.getElementById('dead_peer_timeout-$t').value);
		XHR.appendData('dns_timeout',document.getElementById('dns_timeout-$t').value);
		XHR.appendData('connect_timeout',document.getElementById('connect_timeout-$t').value);
		XHR.appendData('peer_connect_timeout',document.getElementById('peer_connect_timeout-$t').value);
		XHR.appendData('client_lifetime',document.getElementById('client_lifetime-$t').value);
		XHR.appendData('read_timeout',document.getElementById('read_timeout-$t').value);
		XHR.appendData('shutdown_lifetime',document.getElementById('shutdown_lifetime-$t').value);
		XHR.appendData('persistent_request_timeout',document.getElementById('persistent_request_timeout-$t').value);
		XHR.appendData('incoming_rate',document.getElementById('incoming_rate-$t').value);
		XHR.appendData('pconn_timeout',document.getElementById('pconn_timeout-$t').value);
		
		
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function save(){
	$squid=new squidbee();
	while (list ($index, $line) = each ($_POST)){
		echo "squid->$index = $line\n";
		$squid->$index=$line;
		
	}
	
	$squid->SaveToLdap(true);
}