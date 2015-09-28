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
	
	if(isset($_GET["MAIN_STATUS_NGINX"])){MAIN_STATUS_NGINX();exit;}
	if(isset($_GET["MAIN_STATUS_MIDDLE"])){MAIN_STATUS_MIDDLE();exit;}
	if(isset($_POST["EnableFreeWeb"])){MAIN_STATUS_NGINX_SAVE();exit;}
	page();
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div style='font-size:36px;margin-bottom:20px'>Reverse Proxy</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:250px;vertical-align:top'><div id='MAIN_STATUS_NGINX'></div></td>		
		<td valign='top' style='width:99%;vertical-align:top'><div id='MAIN_STATUS_MIDDLE'></div></td>	
	</tr>
	</table>
	<script>
		LoadAjax('MAIN_STATUS_NGINX','$page?MAIN_STATUS_NGINX=yes');
		LoadAjax('MAIN_STATUS_MIDDLE','$page?MAIN_STATUS_MIDDLE=yes');
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function MAIN_STATUS_NGINX(){
	$q=new mysql();
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$datas=$sock->getFrameWork("cmd.php?apachesrc-ini-status=yes");
	
	$ini->loadString(base64_decode($datas));
	
	
	
	
	$serv[]=DAEMON_STATUS_ROUND("APP_APACHE_SRC",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_NGINX",$ini,null,0);
	
	
	$refresh="<div style='text-align:right;margin-top:8px'>
			".imgtootltip("refresh-24.png","{refresh}","LoadAjax('MAIN_STATUS_NGINX','$page?MAIN_STATUS_NGINX=yes');")."</div>";
	
	
	
	$status=@implode("<br>", $serv).$refresh;
	echo $tpl->_ENGINE_parse_body($status);
	
	
}

function MAIN_STATUS_MIDDLE(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	$EnableNginxMail=intval($sock->GET_INFO("EnableNginxMail"));
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	$users=new usersMenus();
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$p1=Paragraphe_switch_img("{enable_reverse_proxy_service}", "{enable_reverse_proxy_service_explain}",
			"EnableNginx-$t",$EnableNginx,null,1072);
	
	$p2=Paragraphe_switch_disable("{enable_reverse_imap_proxy_service}", "{enable_reverse_imap_proxy_service_explain}",
			"EnableNginxMail-$t",$EnableNginxMail,null,1072);	
	
	$p3=Paragraphe_switch_img("{enable_freeweb}","{enable_freeweb_text}",
			"EnableFreeWeb-$t",$EnableFreeWeb,null,1072);
	
	
	if($users->SQUID_INSTALLED){
		$p4=Paragraphe_switch_img("{enable_squid_service}",
				"{enable_squid_service_explain}<br>{enable_squid_service_text}","SQUIDEnable-$t",$SQUIDEnable
			,null,1072);
	
	}
	
	$p2="<br>$p2";
	$p2=null;
	
	$html="$p1<br>$p3<br>$p4$p2<hr>
	<div style='text-align:right;width:98%'>". button("{apply}","Save$t()",40)."</div>
	<script>
			
	var xSave$t=function (obj) {
			var results=obj.responseText;
			Loadjs('nginx.verif.progress.php');
		}	
		
		function Save$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',document.getElementById('EnableFreeWeb-$t').value);
    		XHR.appendData('EnableNginx',document.getElementById('EnableNginx-$t').value);
    		if(document.getElementById('EnableNginxMail-$t')){
    			XHR.appendData('EnableNginxMail',document.getElementById('EnableNginxMail-$t').value);
    		}
    		if(document.getElementById('SQUIDEnable-$t')){
    			XHR.appendData('SQUIDEnable',document.getElementById('SQUIDEnable-$t').value);
    		}
    		XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
			
	
}

function MAIN_STATUS_NGINX_SAVE(){
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb",$_POST["EnableFreeWeb"]);
	if(isset($_POST["EnableNginxMail"])){
		$sock->SET_INFO("EnableNginxMail",$_POST["EnableNginxMail"]);
	}
	$sock->SET_INFO("EnableNginx",$_POST["EnableNginx"]);
	if(isset($_POST["SQUIDEnable"])){
		$sock->SET_INFO("SQUIDEnable",$_POST["SQUIDEnable"]);
	}
	

}
