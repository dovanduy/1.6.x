<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.kav4proxy.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["infos"])){infos();exit;}
	if(isset($_POST["xrescan"])){rescan();exit;}
	
	
kav4proxy_license_js();

function kav4proxy_license_delete(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('cmd.php?Kav4ProxyLicenseDelete&type='.$_GET["license-type"]));	
}

function kav4proxy_license_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_KAV4PROXY}::{license_info}');
	$t=time();
	
	$html="
	function Kav4ProxyLicenseStart$t(){
		YahooWin5('650','$page?infos=yes','$title');
	}
	Kav4ProxyLicenseStart$t();
";
	echo $html;	
	}
function infos(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT *  FROM kav4proxy_license ORDER BY expiredate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$expiredate_color="black";
	$expiredate=$ligne["expiredate"];
	$expiredate=strtotime($expiredate);
	$button="<hr><div style='width:100%;text-align:right'>".button("{license_manager}", "YahooWin5Hide();Loadjs('Kav4Proxy.license-manager.php')",18)."</div>";
	if($expiredate-time()<0){
			$expiredate_color="#B60000";
			$button="<hr><div style='width:100%;text-align:right'>".button("{renew}", "YahooWin5Hide();Loadjs('Kav4Proxy.license-manager.php')",18)."</div>";
	}
	if($tpl->language=="fr"){
		$expiredate=date("Y l F d",$expiredate);
	}else{
		$expiredate=date("{l} d {F} Y",$expiredate);
	}
	
	$t=time();
	$html="
	<div style='width:97%'  id='div-$t' class=form>		
	<table style='width:100%'>
	<tr>
		
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color' colspan=2>{$ligne["productname"]}<hr></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;color:$expiredate_color'>{key_file}:</td>
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color'>{$ligne["keyfile"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;color:$expiredate_color'>{serial}:</td>
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color'>{$ligne["serial"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;color:$expiredate_color'>{creationdate}:</td>
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color'>{$ligne["creationdate"]}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px;color:$expiredate_color'>{expiredate}:</td>
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color'>$expiredate</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;color:$expiredate_color'>{lifespan}:</td>
		<td style='font-weight:bold;font-size:16px;color:$expiredate_color'>{$ligne["lifespan"]}&nbsp;{days}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{rescan}","Rescan$t()",14)."</td>
	</tr>				
	</table>
	</div>
	$button
	
<script>
var xRescan$t = function (obj) {
	document.getElementById('div-$t').innerHTML='';
	YahooWin5Hide();
	Loadjs('$page');
}	
	
function Rescan$t(){
var XHR = new XHRConnection();
	XHR.appendData('xrescan',1);
	document.getElementById('div-$t').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
	XHR.sendAndLoad('$page', 'POST',xRescan$t);	
}
</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function rescan(){
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?kav4proxy-license-generate=yes&keep=yes");
}