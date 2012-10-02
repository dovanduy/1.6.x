<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{display_fw_rules}");
	$html="YahooWin5('730','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?fw-rules=yes")));
	$t=@implode("\n", $datas);
	$t=str_replace(' -m comment --comment "ArticaSquidTransparent"','',$t);
	
	
	$html="<textarea 
	style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:350px;
	border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$t</textarea>";
	echo $html;
}