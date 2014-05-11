<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["CacheManagement2"])){CacheManagement2();exit;}

	js();
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	header("content-type: application/x-javascript");
	$APP_RDPPROXY=$tpl->javascript_parse_text("{CacheManagement2}");
	echo "YahooWin3('650','$page?popup=yes','$APP_RDPPROXY',true);";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->SET_INFO("SquidAsSeenCacheCenter",1);
	$CacheManagement2=$sock->GET_INFO("CacheManagement2");
	if(!is_numeric($CacheManagement2)){$CacheManagement2=0;}
	$t=time();
	$CacheManagement2=Paragraphe_switch_img('{CacheManagement2}',"{enableCacheManagement2_explain}",
			"CacheManagement2-$t",
			$CacheManagement2,null,550);
	
$html="<div id='animate-$t'></div>
	<div style='margin:10px;padding:10px' class=form>
	<table style='width:100%'>
	<tr>
	<td style='margin-bottom:15px;vertical-align:top'>$CacheManagement2<p>&nbsp;</p></td>
	</tr>	
</tr>	
	<tr><td style='text-align:right'><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>
	
	
	
	</table>
	</div>
		
	<script>
	
	var x_Save$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin3Hide();
			CacheOff();
		}		

		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('CacheManagement2',document.getElementById('CacheManagement2-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}

	</script>";
	
			echo $tpl->_ENGINE_parse_body($html);
}

function CacheManagement2(){
	$sock=new sockets();
	$sock->SET_INFO("CacheManagement2", $_POST["CacheManagement2"]);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
}