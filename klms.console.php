<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	if(isset($_POST["EnableFreeWeb"])){EnableFreeWeb();exit;}
	
	
	popup();
	
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){popup_enable_freeweb();exit;}
	
	
	$html="<div id='$t'></div>
	<script>
		LoadAjax('$t','freeweb.servers.php?force-groupware=KLMS&tabzarafa=no&minimal-tools=yes');
	</script>
	
	
	";
	
	echo $html;
}	


function popup_enable_freeweb(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$sock=new sockets();
	
	$TOTAL_MEMORY_MB=$sock->getFrameWork("system.php?TOTAL_MEMORY_MB=yes");
	if($TOTAL_MEMORY_MB<550){
		$EnableFreeWeb=0;
		$p=FATAL_ERROR_SHOW_128("{NO_ENOUGH_MEMORY_FOR_THIS_SECTION}<br><strong style='font-size:18px'>{require}:550MB</strong>",true,true);
		echo $tpl->_ENGINE_parse_body($p);
		return;
	}
	
	
	$html="
	<center style='margin-top:30px' id='div-$t'>
	<table style='width:75%' class=form>
	<td style='width:1%' valign='top'><img src='img/error-128.png'></td>
	<td valign='top' width=99%'>
		<div style='font-size:18px;font-weight:bold'>{web_engine_not_activated}</div>
		<p style='font-size:16px'>{web_engine_not_activated_explain}</p>
		<center style='margin:20px'>". button("{activate_web_engine}","EnableFreeWebSave$t()","18px")."</center>
	</td>
	</tr>
	</table>
	</center>
	
	<script>
	var x_EnableFreeWebSave$t=function (obj) {
			var results=obj.responseText;
			RefreshTab('main_klms_tabs');
		}	
		
		function EnableFreeWebSave$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',1);
    		AnimateDiv('div-$t');
    		XHR.sendAndLoad('$page', 'POST',x_EnableFreeWebSave$t);
			
		}	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function EnableFreeWeb(){
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb",1);
	$sock->SET_INFO("EnableApacheSystem",1);
	$sock->getFrameWork("freeweb.php?changeinit-on=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
	
}
	
	
