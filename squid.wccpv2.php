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
	
	if(isset($_POST["wccp2_enabled"])){wccp2_save();exit;}
	
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
	$ipsH=$ip->ALL_IPS_GET_ARRAY();
	$SquidWCCPEnabled=$sock->GET_INFO("SquidWCCPEnabled");
	if(!is_numeric($SquidWCCPEnabled)){$SquidWCCPEnabled=0;}
	
	
	if(!isset($arrayParams["--enable-wccpv2"])){$WCCP=0;}
	
	$WCCPHash=unserialize(base64_decode($sock->GET_INFO("WCCPHash")));
	
	
	$wccp2_forwarding_method_hash=array(
			1=>"{wccp2_forwarding_method_hash_1}",
			2=>"{wccp2_forwarding_method_hash_2}");

	$wccp2_return_method_hash=array(
			"gre"=>"GRE encapsulation",
			//"l2"=>"L2 redirect"

	);

	$wccp2_assignment_method_hash=array(
			"hash"=>"Hash assignment",
			"mask"=>"Mask assignment"

	);
$html="
<div style='font-size:22px'>{WCCP_NAME}</div>
<div class=text-info style='font-size:14px'>{WCCP_HOWTO}</div>
<div id='SquidAVParamWCCP' style='width:98%' class=form>
<table style='width:100%'>
	<tr>
	<td style='font-size:16px;' class=legend>{wccp2_enabled}:</td>
	<td>". Field_checkbox("wccp2_enabled",1,$squid->wccp2_enabled,"wccp2_enabled()")."</td>
	<td>&nbsp;</td>
	</tr>


	<tr>
		<td style='font-size:16px' class=legend nowrap>{wccp2_routers}:</td>
		<td>". Field_text("wccp2_router",$WCCPHash["wccp2_router"],"font-size:16px;padding:3px;width:320px")."</td>
		<td>". help_icon("{wccp2_routers_explain}")."</td>
	</tr>
	<tr>
		<td style='font-size:16px' class=legend nowrap>{listen_address}:</td>
		<td>". Field_array_Hash($ipsH,"listen_address-$t",
		$WCCPHash["listen_address"],"style:font-size:16px")."</td>
		<td></td>
	</tr>
	<tr>
		<td style='font-size:16px' class=legend nowrap>{wccp2_forwarding_method}:</td>
		<td>". Field_array_Hash($wccp2_forwarding_method_hash,"wccp2_forwarding_method",
		$WCCPHash["wccp2_forwarding_method"],"style:font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td style='font-size:16px' class=legend nowrap>{wccp2_return_method}:</td>
		<td>". Field_array_Hash($wccp2_return_method_hash,"wccp2_return_method",
				$WCCPHash["wccp2_return_method"],"style:font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style='font-size:16px' class=legend nowrap>{wccp2_assignment_method}:</td>
		<td>". Field_array_Hash($wccp2_assignment_method_hash,"wccp2_assignment_method",
				$WCCPHash["wccp2_assignment_method"],"style:font-size:16px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>
				". button("{apply}","SquidWccp2ParamSave()",18)."
		</td>
	</tr>
	</table>
</div>
<script>
var X_SquidWccp2ParamSave= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.restart.php?prepare-js=yes');
	
}

function SquidWccp2ParamSave(){
	var XHR = new XHRConnection();
	if(document.getElementById('wccp2_enabled').checked){
		XHR.appendData('wccp2_enabled',1);
	}else{
		XHR.appendData('wccp2_enabled',0);
	}
	XHR.appendData('wccp2_router',
	document.getElementById('wccp2_router').value);

	XHR.appendData('wccp2_forwarding_method',
	document.getElementById('wccp2_forwarding_method').value);

	XHR.appendData('wccp2_return_method',
	document.getElementById('wccp2_return_method').value);

	XHR.appendData('wccp2_assignment_method',
	document.getElementById('wccp2_assignment_method').value);
	
	XHR.appendData('listen_address',
	document.getElementById('listen_address-$t').value);

	XHR.sendAndLoad('$page', 'POST',X_SquidWccp2ParamSave);
}


function wccp2_disable_all(){
	document.getElementById('wccp2_forwarding_method').disabled=true;
	document.getElementById('wccp2_router').disabled=true;
	document.getElementById('wccp2_forwarding_method').disabled=true;
	document.getElementById('wccp2_return_method').disabled=true;
	document.getElementById('wccp2_assignment_method').disabled=true;
	document.getElementById('listen_address-$t').disabled=true;
	
}
function wccp2_enable_all(){
	document.getElementById('wccp2_forwarding_method').disabled=false;
	document.getElementById('wccp2_router').disabled=false;
	document.getElementById('wccp2_forwarding_method').disabled=false;
	document.getElementById('wccp2_return_method').disabled=false;
	document.getElementById('wccp2_assignment_method').disabled=false;
	document.getElementById('listen_address-$t').disabled=false;
}

function wccp2_enabled(){
	wccp2_disable_all();
	var wccp2=$WCCP;
	if(wccp2==0){
		document.getElementById('wccp2_enabled').disabled=true;
		document.getElementById('wccp2_enabled').checked=false;
	}
	if(document.getElementById('wccp2_enabled').checked){wccp2_enable_all();}
}

wccp2_enabled();
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function wccp2_save(){
	
	
	$sock=new sockets();
	$sock->SET_INFO("SquidWCCPEnabled", $_POST["wccp2_enabled"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "WCCPHash");
}
