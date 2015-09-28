<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["popup"])){table();exit;}
	if(isset($_POST["BigDatav3Read"])){BigDatav3Read();exit;}
js();



function js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{warning_bigdata_v3}");
	echo "YahooWin3(990,'$page?popup=yes','$title')";	
}
	

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="<div class=explain style='font-size:22px'>{warning_bigdata_v3_explain}</div>
			
	<center style='margin:20px'>
			<a href=\"http://artica-proxy.com/?p=1261\" style='font-size:20px;text-decoration:underline' target=_new>Import access.log in statistics database</a>
	</center>
	<div style='margin:20px;text-align:right'>". button("{ihavereaditremove}",
				"Save$t()",33)."</div>
						
<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWin3Hide();
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('BigDatav3Read', '1');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}			
	
</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function BigDatav3Read(){
	$sock=new sockets();
	$sock->SET_INFO("BigDatav3Read", 1);
	
}

?>