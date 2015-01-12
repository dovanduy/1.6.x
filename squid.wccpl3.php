<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
	$user=new usersMenus();

	if($user->SQUID_INSTALLED==false){
		if(!$user->WEBSTATS_APPLIANCE){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_POST["SquidWCCPL3Enabled"])){SquidWCCPL3Enabled();exit;}
	if(isset($_GET["wccp50"])){wccp50();exit;}
popup();	



function popup(){
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$WCCP=1;
	$arrayParams=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	$t=time();
	$ip=new networking();
	for($i=0;$i<255;$i++){
	$ipsH["gre$i"]="gre$i";
	}
	$SquidWCCPL3Enabled=intval($sock->GET_INFO("SquidWCCPL3Enabled"));
	$SquidWCCPL3Addr=$sock->GET_INFO("SquidWCCPL3Addr");
	$SquidWCCPL3Inter=$sock->GET_INFO("SquidWCCPL3Inter");
	$SquidWCCPL3Eth=$sock->GET_INFO("SquidWCCPL3Eth");
	$SquidWCCPL3Route=$sock->GET_INFO("SquidWCCPL3Route");
	$SquidWCCPL3ProxPort=intval($sock->GET_INFO("SquidWCCPL3ProxPort"));
	$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
	$SquidWCCPL3SSServiceID=intval($sock->GET_INFO("SquidWCCPL3SSServiceID"));
	$SquidWCCPL3SSCertificate=intval($sock->GET_INFO("SquidWCCPL3SSCertificate"));
	if($SquidWCCPL3SSServiceID==0){$SquidWCCPL3SSServiceID=70;}
	
	$sslproxy_version=intval($sock->GET_INFO("sslproxy_version"));
	if($sslproxy_version==0){$sslproxy_version=1;}
	
	if($SquidWCCPL3ProxPort==0){
		$SquidWCCPL3ProxPort=rand(35000,62680);
		$sock->SET_INFO("SquidWCCPL3ProxPort", $SquidWCCPL3ProxPort);
	}
	
	$ip=new networking();
	$ipsH=$ip->Local_interfaces();
	

	
	$q=new mysql();
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	$sslproxy_versions[1]="{default}";
	$sslproxy_versions[2]="SSLv2 {only}";
	$sslproxy_versions[3]="SSLv3 {only}";
	$sslproxy_versions[4]="TLSv1.0 {only}";
	$sslproxy_versions[5]="TLSv1.1 {only}";
	$sslproxy_versions[6]="TLSv1.2 {only}";
	
	

$html="
<table style='width:100%'>
<tr>
	<td style='width:350px;vertical-align:top'  nowrap align='top'><div id='wccp50'></div></td>
	<td style='width:99%' style='vertical-align:top'>
<div style='font-size:36px'>{WCCP_LAYER3}</div>
<div class=text-info style='font-size:14px'>{WCCP_LAYER3_EXPLAIN}</div>
<div id='SquidAVParamWCCP' style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td colspan=3>". Paragraphe_switch_img("{wccp2_enabled}", "{wccp2_gre_enabled_explain}","SquidWCCPL3Enabled-$t","$SquidWCCPL3Enabled",null,650)."</td>
	<tr>
	

	<tr>
		<td style='font-size:22px' class=legend nowrap>{wccp_asa_addr}:</td>
		<td>". field_ipv4("SquidWCCPL3Addr-$t",$SquidWCCPL3Addr,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend nowrap>{wccp_asa_int}:</td>
		<td>". field_ipv4("SquidWCCPL3Inter-$t",$SquidWCCPL3Inter,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td style='font-size:22px' class=legend nowrap>{wccp_local_gre_interface}:</td>
		<td>". Field_array_Hash($ipsH,"SquidWCCPL3Eth-$t",
		$SquidWCCPL3Eth,"style:font-size:22px")."</td>
		<td></td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend nowrap>Route:</td>
		<td>". field_ipv4("SquidWCCPL3Route-$t",$SquidWCCPL3Route,"font-size:22px")."</td>
		<td>". help_icon("{gre_route_explain}")."</td>
	</tr>
				
	<tr>
		<td colspan=3>". Paragraphe_switch_img("{wccp2_enabled_ssl}", "{wccp2_gre_enabled_explain_ssl}","SquidWCCPL3SSLEnabled-$t","$SquidWCCPL3SSLEnabled",null,650)."</td>
	<tr>				
	<tr>
		<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{service_id}:</td>
		<td>". Field_text("SquidWCCPL3SSServiceID-$t",$SquidWCCPL3SSServiceID,"font-size:22px;width:110px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{certificate}:</td>
		<td>". Field_array_Hash($sslcertificates,"SquidWCCPL3SSCertificate-$t",$SquidWCCPL3SSCertificate,"style:font-size:22px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{sslproxy_version}:</td>
		<td>". Field_array_Hash($sslproxy_versions,"sslproxy_version-$t",$sslproxy_version,"style:font-size:22px")."</td>
		<td></td>
	</tr>
				
			
				
				
	<tr>
		<td colspan=3 align='right'>
			<hr>
				". button("{apply}","Save$t()",32)."
		</td>
	</tr>
	</table>
</div>
</td>
</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.reconfigure.php?restart=yes&wccp=yes');
	
}

function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('SquidWCCPL3Enabled',
	document.getElementById('SquidWCCPL3Enabled-$t').value);

	XHR.appendData('SquidWCCPL3Addr',
	document.getElementById('SquidWCCPL3Addr-$t').value);

	XHR.appendData('SquidWCCPL3Inter',
	document.getElementById('SquidWCCPL3Inter-$t').value);
	
	XHR.appendData('SquidWCCPL3Eth',
	document.getElementById('SquidWCCPL3Eth-$t').value);

	XHR.appendData('SquidWCCPL3SSLEnabled',
	document.getElementById('SquidWCCPL3SSLEnabled-$t').value);
	
	XHR.appendData('SquidWCCPL3SSServiceID',
	document.getElementById('SquidWCCPL3SSServiceID-$t').value);	
	
	XHR.appendData('SquidWCCPL3SSCertificate',
	document.getElementById('SquidWCCPL3SSCertificate-$t').value);	
	
	XHR.appendData('sslproxy_version',
	document.getElementById('sslproxy_version-$t').value);	

	XHR.appendData('SquidWCCPL3Route',
	document.getElementById('SquidWCCPL3Route-$t').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

LoadAjax('wccp50','$page?wccp50=yes');

</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function SquidWCCPL3Enabled(){
	$sock=new sockets();
	$sock->SET_INFO("SquidWCCPL3Enabled", $_POST["SquidWCCPL3Enabled"]);
	$sock->SET_INFO("SquidWCCPL3Addr", $_POST["SquidWCCPL3Addr"]);
	$sock->SET_INFO("SquidWCCPL3Inter", $_POST["SquidWCCPL3Inter"]);
	$sock->SET_INFO("SquidWCCPL3Eth", $_POST["SquidWCCPL3Eth"]);
	$sock->SET_INFO("SquidWCCPL3Route", $_POST["SquidWCCPL3Route"]);
	
	$sock->SET_INFO("SquidWCCPL3SSLEnabled", $_POST["SquidWCCPL3SSLEnabled"]);
	$sock->SET_INFO("SquidWCCPL3SSServiceID", $_POST["SquidWCCPL3SSServiceID"]);
	$sock->SET_INFO("SquidWCCPL3SSCertificate", $_POST["SquidWCCPL3SSCertificate"]);
	$sock->SET_INFO("sslproxy_version", $_POST["sslproxy_version"]);
	
}
function wccp50(){
	$tpl=new templates();
	$sock=new sockets();
	$data=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$status="ok32.png";
	if(!isset($data["wccp50"])){
		$status="danger32.png";
		
		
	}
	$SquidWCCPL3Enabled=intval($sock->GET_INFO("SquidWCCPL3Enabled"));
	
	if($SquidWCCPL3Enabled==0){
		$status="warning32.png";
		$data["wccp50"]["IPADDR"]="{disabled}";
	}

	
        
        $html="
        <div style='width:95%' class=form>
        <table style='width:99%'>
        <tr>
        	<td valign='top'><img src='img/$status'></td>
        	<td valign='top'>
        		<table style='with:100%'>
        		<tr>
        			<td class=legend style='font-size:16px'>{interface}:</td>
        			<td><strong style='font-size:16px'>wccp50</strong>
        		</tr>
        		<tr>
        			<td class=legend style='font-size:16px'>{ipaddr}:</td>
        			<td><strong style='font-size:16px'>{$data["wccp50"]["IPADDR"]}</strong>
        		</tr>
        		</table>
        	</td>
        	</tr>
        	</table>
        		</div>
        		";
	echo $tpl->_ENGINE_parse_body($html);
}