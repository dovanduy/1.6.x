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
	
	
	if(isset($_GET["popup"])){popup();exit;}
	
	if(isset($_POST["debug"])){debug();exit;}
	js();
	
	
function js() {
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{debug}");
	echo "YahooWin6('900','$page?popup=yes','$title')";
	
	
}	

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$datasUFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($datasUFDB["DebugAll"])){$datasUFDB["DebugAll"]=0;}
	$watn=$tpl->javascript_parse_text("{ufdbguard_debug_off}");
	$button_name="{debug} OFF";
	if($datasUFDB["DebugAll"]==0){
		$watn=$tpl->javascript_parse_text("{ufdbguard_debug_on}");
		$button_name="{debug} ON";
	
	}
	
	$html="<center style='margin:30px'>".button($button_name,"Save$t()",35)."</center>
	<script>
	var xttask$t=function (obj) {
		LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');
		YahooWin6Hide();
		Loadjs('dansguardian2.compile.php');
	}
	
		function Save$t(){
			if(!confirm('$watn')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('debug','yes');
			XHR.sendAndLoad('$page', 'POST',xttask$t);
		}
	
			
			
			
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
	

function debug(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($datas["DebugAll"])){$datas["DebugAll"]=0;}
	if($datas["DebugAll"]==1){$datas["DebugAll"]=0;}else{$datas["DebugAll"]=1;}
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");
		
	
}