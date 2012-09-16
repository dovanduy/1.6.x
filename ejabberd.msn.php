<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.ejabberd.inc');
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["MSN_GATEWAY"])){SaveMSNConf();exit;}
	
	js();
		
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_PYMSNT}");
	echo "YahooWin2('550','$page?popup=yes','$title');";
}	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$PYMSNT=unserialize(base64_decode($sock->GET_INFO("PYMSNT")));
	$t=time();
	$html="
	<div id='$t'>
	<div class=explain>{PYMSNT_HOWTO}</div>
	<table class=form style='width:99%'>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". Field_text("MSN_GATEWAY",$PYMSNT["MSN_GATEWAY"],"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveMSNConfig()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveMSNConfig=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			YahooWin2Hide();
		}	
		
		function SaveMSNConfig(){
			var XHR = new XHRConnection();
			XHR.appendData('MSN_GATEWAY',document.getElementById('MSN_GATEWAY').value);
			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveMSNConfig);
			
		}
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveMSNConf(){
	$sock=new sockets();
	$_POST["PASSWORD"]=md5(time());
	$PYMSNT=unserialize(base64_decode($sock->GET_INFO("PYMSNT")));	
	while (list ($num, $ligne) = each ($_POST) ){
		$PYMSNT[$num]=$ligne;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($PYMSNT)), "PYMSNT");
	
}

		
