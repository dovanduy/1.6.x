<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	include_once('ressources/class.ccurl.inc');
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_POST["EnableITChart"])){EnableITChart();exit;}
	
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableITChart=intval($sock->GET_INFO("EnableITChart"));
	$t=time();
	
	$p=Paragraphe_switch_img("{enable_it_charter}", "{IT_charter_explain}<br>{IT_charter_explain2}",
	"EnableITChart",$EnableITChart,null,1400		
	);	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{IT_charter}</div>
	<div style='width:99%' class=form>
	$p
	<div style='width:100%;text-align:right'><hr>". button("{apply}", "Save$t()",40)."</div>
	</div>
<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		Loadjs('itchart.progress.php');
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableITChart', document.getElementById('EnableITChart').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}			
	
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function EnableITChart(){
	$sock=new sockets();
	$sock->SET_INFO("EnableITChart", $_POST["EnableITChart"]);
	
}
