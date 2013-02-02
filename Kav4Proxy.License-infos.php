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
		YahooWin5('520','$page?infos=yes','$title');
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
	
	$html="<table style='width:95%' class=form>
	<tr>
		
		<td style='font-weight:bold;font-size:14px' colspan=2>{$ligne["productname"]}<hr></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{key_file}:</td>
		<td style='font-weight:bold;font-size:14px'>{$ligne["keyfile"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{serial}:</td>
		<td style='font-weight:bold;font-size:14px'>{$ligne["serial"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{creationdate}:</td>
		<td style='font-weight:bold;font-size:14px'>{$ligne["creationdate"]}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{expiredate}:</td>
		<td style='font-weight:bold;font-size:14px'>{$ligne["expiredate"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{lifespan}:</td>
		<td style='font-weight:bold;font-size:14px'>{$ligne["lifespan"]}&nbsp;{days}</td>
	</tr>				
	</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
}
