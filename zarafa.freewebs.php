<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_POST["EnableFreeWeb"])){SaveFreeWeb();exit;}
	
	
page();	
	
function page(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){error_freeweb();return;}
	
	$html="
	<div id='freeweb-zarafa-list'></div>
	
	<script>
		LoadAjax('freeweb-zarafa-list','freeweb.servers.php?force-groupware=ZARAFA&tabzarafa=no&tabzarafa=yes');
	</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}	

function SaveFreeWeb(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb",$_POST["EnableFreeWeb"]);
	$sock->SET_INFO("EnableApacheSystem",$_POST["EnableFreeWeb"]);	
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
}

function error_freeweb(){
		$tpl=new templates();
		$page=CurrentPageName();
		$freeweb=Paragraphe_switch_img("{enable_freeweb}","{enable_freeweb_text}","EnableFreeWebC",$EnableFreeWeb,null,400);
		$form="<hr><div style='text-align:right;width:100%' id='zarafa-error'>". button("{apply}", "SaveZarafaWebEngine()",14)."</div>";		
		$html= "<H2><img src='img/error-128.png' align='left' style='margin:5px'>{ERROR_ZARAAF_MULTIPLE_INSTANCES_FREEWEB}</H2>
		<div style='width:95%;margin:10px' class=form>
		$freeweb
		$form
		</div>
		<script>
	
	
	var x_SaveZarafaWebEngine2=function (obj) {
			var results=obj.responseText;
			RefreshTab('main_config_zarafa');
		}	
		
		function SaveZarafaWebEngine(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',document.getElementById('EnableFreeWebC').value);
 			AnimateDiv('zarafa-error');
    		XHR.sendAndLoad('$page', 'POST',x_SaveZarafaWebEngine2);
			
		}		
	
		
	</script>";		
	echo $tpl->_ENGINE_parse_body($html);
}