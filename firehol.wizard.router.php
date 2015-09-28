<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.tcpip.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["step0"])){step0();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_POST["IF_WAN"])){save();exit;}
	if(isset($_POST["IF_LAN"])){save();exit;}
	if(isset($_GET["stepfinal"])){stepfinal();exit;}
	
	if(isset($_GET["step2"])){step2();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ruleid"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	$title=$tpl->javascript_parse_text("{router_mode_require_2nics}");
	echo "YahooWin('1005','$page?step0=yes','$title')";
}

function step0(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='firehol-wizard'></div>		
	<div style='width:98%' class=form>
	<div style='font-size:34px;margin-bottom:10px'>{firewall_wizard}</div>
	<div id='$t'></div>
	</div>
	<script>
		LoadAjax('$t','$page?step1=yes&t=$t');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function step1(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}
	
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	
	
	$html="<div class=explain style='font-size:20px;margin-bottom:15px'>{select_wan_interface_explain}</div>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:28px' nowrap>{interface}:</td>
			<td>". Field_array_Hash($array, "IF_WAN-$t",$FireHolConf["IF_WAN"],"style:font-size:28px")."</td>
		</tr>
		<tr>
			<td colspan=2 align=right style='text-align:right;padding-top:20px'><hr>". button("{next}","Save$t();","32")."</td>
		</tr>
	</table>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('$t','$page?step2=yes&t=$t');
}	
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('IF_WAN', document.getElementById('IF_WAN-$t').value);	
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}
</script>";		
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function step2(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		

	}
	
	unset($array[$FireHolConf["IF_WAN"]]);


	$html="<div class=explain style='font-size:20px;margin-bottom:15px'>{select_lan_interface_explain}</div>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:28px' nowrap>{interface}:</td>
			<td>". Field_array_Hash($array, "IF_LAN-$t",$FireHolConf["IF_LAN"],"style:font-size:28px")."</td>
		</tr>
		<tr>
			<td colspan=2 align=right style='text-align:right;padding-top:20px'><hr>". button("{next}","Save$t();","36")."</td>
		</tr>
	</table>
<script>
	var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('$t','$page?stepfinal=yes&t=$t');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('IF_LAN', document.getElementById('IF_LAN-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";

	echo $tpl->_ENGINE_parse_body($html);


}

function stepfinal(){
	
	$sock=new sockets();
	
	$sock->GET_INFO("FireHolConfigured",1);
	$sock->SET_INFO("FireHolEnable", 1);
	$sock->SET_INFO("FireHolRouter", 1);
	$sock->SET_INFO("FireHolConfigured", 1);
	
	echo "<script>Loadjs('firehol.progress.php');</script>";
	
	
}

function save(){
	$sock=new sockets();
	
	$sock=new sockets();
	
	$sock->GET_INFO("FireHolConfigured",1);
	$sock->SET_INFO("FireHolEnable", 1);
	$sock->SET_INFO("FireHolRouter", 1);
	$sock->SET_INFO("FireHolConfigured", 1);
	
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	while (list ($index, $ligne) = each ($_POST) ){
		$FireHolConf[$index]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($FireHolConf)), "FireHolConf");
	
}