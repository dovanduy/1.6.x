<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.system.network.inc');
	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["FreeRadiusListenIP"])){save();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{connections_settings}");
	echo "YahooWin3('550','$page?popup=yes','$title')";
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$FreeRadiusListenIP=$sock->GET_INFO("FreeRadiusListenIP");
	$FreeRadiusListenPort=$sock->GET_INFO("FreeRadiusListenPort");
	
	if($FreeRadiusListenIP=="0.0.0.0"){$FreeRadiusListenIP=null;}
	if(!is_numeric($FreeRadiusListenPort)){$FreeRadiusListenPort=1812;}	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips[null]="{all}";
	$t=time();

	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{listen_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($ips,"FreeRadiusListenIP-$t",$FreeRadiusListenIP,"style:font-size:16px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td style='font-size:16px'>". Field_text("FreeRadiusListenPort-$t",$FreeRadiusListenPort,"font-size:16px;width:90px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_Save$t=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin3Hide();
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('FreeRadiusListenPort',document.getElementById('FreeRadiusListenPort-$t').value);
		XHR.appendData('FreeRadiusListenIP',document.getElementById('FreeRadiusListenIP-$t').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_Save$t);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}
	

function save(){
	
	
	$sock=new sockets();
	$sock->SET_INFO("FreeRadiusListenIP", $_POST["FreeRadiusListenIP"]);
	$sock->SET_INFO("FreeRadiusListenPort", $_POST["FreeRadiusListenPort"]);
	$sock->getFrameWork("freeradius.php?restart=yes");
	
}



