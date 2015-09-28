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
	
	if(isset($_POST["SquidWCCPL3Addr"])){SquidWCCPL3Enabled();exit;}
	if(isset($_GET["wccp50"])){wccp50();exit;}
	
	if(isset($_GET["js"])){js();exit;}
	
	
popup();	
function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{wccp_options}");
	echo "YahooWin3('935','$page','$title')";
}


function popup(){
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$WCCP=1;
	$t=time();
	$SquidWCCPL3Addr=$sock->GET_INFO("SquidWCCPL3Addr");
	$SquidWCCPL3Inter=$sock->GET_INFO("SquidWCCPL3Inter");
	$SquidWCCPL3Eth=$sock->GET_INFO("SquidWCCPL3Eth");
	$SquidWCCPL3Route=$sock->GET_INFO("SquidWCCPL3Route");
	$SquidWCCPL3ProxPort=intval($sock->GET_INFO("SquidWCCPL3ProxPort"));
	$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
	$SquidWCCPL3SSServiceID=intval($sock->GET_INFO("SquidWCCPL3SSServiceID"));
	$SquidWCCPL3SSCertificate=intval($sock->GET_INFO("SquidWCCPL3SSCertificate"));
	if($SquidWCCPL3SSServiceID==0){$SquidWCCPL3SSServiceID=70;}
	$ID=$_GET["port-id"];

	

	


	
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidWCCPL3Addr")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidWCCPL3Addr` VARCHAR(60)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidWCCPL3Route")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidWCCPL3Route` VARCHAR(60)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}

	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));
	

$html="
<div style='font-size:36px'>{WCCP_LAYER3}</div>
<div class=explain style='font-size:14px'>{WCCP_LAYER3_EXPLAIN}</div>
<div id='SquidAVParamWCCP' style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend nowrap>{wccp_asa_addr}:</td>
		<td>". field_ipv4("SquidWCCPL3Addr-$t",$SquidWCCPL3Addr,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend nowrap>".texttooltip("Route ({optional})","{gre_route_explain}").":</td>
		<td>". field_ipv4("SquidWCCPL3Route-$t",$SquidWCCPL3Route,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
				
				
	<tr>
		<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{service_id} (SSL):</td>
		<td>". Field_text("SquidWCCPL3SSServiceID-$t",$SquidWCCPL3SSServiceID,"font-size:22px;width:110px")."</td>
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
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');

	XHR.appendData('SquidWCCPL3Addr',
	document.getElementById('SquidWCCPL3Addr-$t').value);

	XHR.appendData('SquidWCCPL3SSServiceID',
	document.getElementById('SquidWCCPL3SSServiceID-$t').value);	
	
	
	XHR.appendData('SquidWCCPL3Route',
	document.getElementById('SquidWCCPL3Route-$t').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function SquidWCCPL3Enabled(){
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	$sql="UPDATE proxy_ports SET 
		SquidWCCPL3Addr='{$_POST["SquidWCCPL3Addr"]}',
		SquidWCCPL3Route='{$_POST["SquidWCCPL3Route"]}'
		WHERE ID='{$_POST["ID"]}'";
	
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidWCCPL3Addr")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidWCCPL3Addr` VARCHAR(60)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("proxy_ports", "SquidWCCPL3Route")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `SquidWCCPL3Route` VARCHAR(60)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
		
	$sock->SET_INFO("SquidWCCPL3SSServiceID", $_POST["SquidWCCPL3SSServiceID"]);
	
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