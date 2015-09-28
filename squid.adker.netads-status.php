<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');
	
	
	if(isset($_GET["status"])){status_kerb();exit;}
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
$sock=new sockets();
$sock->getFrameWork("samba.php?net-ads-status=yes");

$f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/NET_ADS_STATUS"));

$tr[]="<div style='width:98%' class=form><table style='width:100%'>";

while (list ($num, $ligne) = each ($f) ){
	if(strpos("-------",$ligne)>0){continue;}
	if(preg_match("#object#",$ligne)){continue;}
	if(preg_match("#access type#",$ligne)){continue;}
	if(preg_match("#Object GUID#i",$ligne)){continue;}
	if(preg_match("#Object type#i",$ligne)){continue;}
	if(preg_match("#-------#i",$ligne)){continue;}
	if(preg_match("#^(.+?):(.+)#", trim($ligne),$re)){
		$md=md5("{$re[1]}{$re[2]}");
		if(isset($AL[$md])){continue;}
		$AL[$md]=true;
	$tr[]="
	<tr>
		<td class=legend style='font-size:18px'>{$re[1]}:&nbsp;</td>
		<td style='font-size:18px;font-weight:bold'>{$re[2]}</td>	
	</tr>
		
	";
	
	}
	
	
}
$tr[]="</table></div>";

echo @implode("\n", $tr);
