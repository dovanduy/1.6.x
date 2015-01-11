<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	js();
	if(isset($_POST["debug"])){debug();exit;}
	
function js(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datasUFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($datasUFDB["DebugAll"])){$datasUFDB["DebugAll"]=0;}
	$watn=$tpl->javascript_parse_text("{ufdbguard_debug_off}");
	if($datasUFDB["DebugAll"]==0){
		$watn=$tpl->javascript_parse_text("{ufdbguard_debug_on}");
		
		
	}
	$t=time();
	$html="
			
	var xttask$t=function (obj) {
		if(document.getElementById('rules-toolbox-left')){
			RefreshTab('main_dansguardian_mainrules');
		}
	}
	
		function ttask$t(){
			if(!confirm('$watn')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('debug','yes');
			XHR.sendAndLoad('$page', 'POST',xttask$t);
		}
			
ttask$t();	";
	
	echo $html;
}
function debug(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($datas["DebugAll"])){$datas["DebugAll"]=0;}
	if($datas["DebugAll"]==1){$datas["DebugAll"]=0;}else{$datas["DebugAll"]=1;}
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");
	$sock->getFrameWork("cmd.php?reload-squidguard=yes");		
	
}