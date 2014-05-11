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
	if(isset($_POST["enable_snmp"])){enable_snmp();exit;}
	if(isset($_GET["mib-js"])){mib_js();exit;}
	if(isset($_GET["mib-popup"])){mib_popup();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("SNMP");
	$page=CurrentPageName();
	$html="YahooWin3('600','$page?popup=yes','$title');";
	echo $html;	
}

function mib_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$html="YahooWin4('700','$page?mib-popup=yes','mib.txt');";
	echo $html;		
	
}
function mib_popup(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("squid.php?mib=yes"));
echo "<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px' 
		id='mibtxt$t'>$datas</textarea>";
	
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
	
	
	$arrayParams=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	$t=time();
	
	if(!isset($arrayParams["--enable-snmp"])){
		if($EnableWebProxyStatsAppliance==0){
			echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_squid_snmp_not_compiled}"));
			return;
		}
	}
	
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
			<tr>
			<td class=legend style='font-size:16px'>{enable_snmp}:</td>
			<td>". Field_checkbox("enable_snmp",1,$squid->snmp_enable,"enable_snmp_check()")."</td>
		</tr>
	
		<tr>
			<td class=legend style='font-size:16px'>{snmp_community}:</td>
			<td>". Field_text("snmp_community",$squid->snmp_community,"font-size:16px;width:220px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{listen_port}:</td>
			<td>". Field_text("snmp_port",$squid->snmp_port,"font-size:16px;width:95px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{remote_snmp_console_ip}:</td>
			<td>". Field_text("snmp_access_ip",$squid->snmp_access_ip,"font-size:16px;width:190px")."</td>
		</tr>
		<tr>
			<td align='right' colspan=2 style='font-size:16px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('$page?mib-js=yes');\" 
			style='font-size:16px;text-decoration:underline'
			>mib.txt</a>&nbsp;|&nbsp;
			
			<a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=361','1024','900');\"
				style=\"font-size:16px;font-weight;bold;text-decoration:underline\">{online_help}</a>			
			
			</td>
		</tr>				
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "SaveSNMP$t()","18px")."</td>
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
		var enable_snmp=0;
		if(document.getElementById('enable_snmp').checked){enable_snmp=1;}
		XHR.appendData('enable_snmp',enable_snmp);
		XHR.appendData('snmp_community',document.getElementById('snmp_community').value);
		XHR.appendData('snmp_port',document.getElementById('snmp_port').value);
		XHR.appendData('snmp_access_ip',document.getElementById('snmp_access_ip').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	
	function enable_snmp_check(){
		var lock=$EnableRemoteStatisticsAppliance;
		document.getElementById('snmp_community').disabled=true;
		document.getElementById('snmp_port').disabled=true;
		document.getElementById('snmp_access_ip').disabled=true;
		if(lock==1){
			document.getElementById('enable_snmp').disabled=true;
			return;
		}
		if(document.getElementById('enable_snmp').checked){
			document.getElementById('snmp_community').disabled=false;
			document.getElementById('snmp_port').disabled=false;
			document.getElementById('snmp_access_ip').disabled=false;		
		}
	}
	enable_snmp_check();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function enable_snmp(){
	$squid=new squidbee();
	$squid->snmp_enable=$_POST["enable_snmp"];
	$squid->snmp_community=$_POST["snmp_community"];
	$squid->snmp_port=$_POST["snmp_port"];
	$squid->snmp_access_ip=$_POST["snmp_access_ip"];
	$squid->SaveToLdap();
	
}