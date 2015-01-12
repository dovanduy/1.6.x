<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.reverse.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}	
	
	if(isset($_POST["SquidGuardWebUseExternalUri"])){Save();exit;}
	
page();

function page(){
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$SquidGuardWebUseExternalUri=intval($sock->GET_INFO("SquidGuardWebUseExternalUri"));
	$SquidGuardWebExternalUri=$sock->GET_INFO("SquidGuardWebExternalUri");
	$SquidGuardWebExternalUriSSL=$sock->GET_INFO("SquidGuardWebExternalUriSSL");
	
	$html="
	<div style='width:98%' class=form>		
	<div style='font-size:28px;margin-bottom:20px'>{remote_webpage}</div>
	". Paragraphe_switch_img("{UfdbUseGlobalWebPage}", "{UfdbUseGlobalWebPage_explain}","SquidGuardWebUseExternalUri",$SquidGuardWebUseExternalUri,null,850,"check$t()").
	"<table style='width:98%'>
	<tr>
		<td class=legend style='font-size:22px'>{fulluri}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUri","$SquidGuardWebExternalUri","font-size:22px;padding:3px;width:660px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{fulluri} (ssl):</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUriSSL","$SquidGuardWebExternalUriSSL","font-size:22px;padding:3px;width:660px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
	</tr>	
	</table>
	</div>
	<script>

function check$t(){
		document.getElementById('SquidGuardWebExternalUri').disabled=true;
		document.getElementById('SquidGuardWebExternalUriSSL').disabled=true;
		var enable=document.getElementById('SquidGuardWebUseExternalUri').value;
		if(enable==1){
			document.getElementById('SquidGuardWebExternalUri').disabled=false;
			document.getElementById('SquidGuardWebExternalUriSSL').disabled=false;
		}

}

var xSave$t=function(obj){
	Loadjs('dansguardian2.compile.php');
}



function Save$t(){
	var XHR = new XHRConnection();
    XHR.appendData('SquidGuardWebUseExternalUri',document.getElementById('SquidGuardWebUseExternalUri').value);
    XHR.appendData('SquidGuardWebExternalUri',document.getElementById('SquidGuardWebExternalUri').value);
    XHR.appendData('SquidGuardWebExternalUriSSL',document.getElementById('SquidGuardWebExternalUriSSL').value);
    XHR.sendAndLoad('$page', 'POST',xSave$t);     	
}

 	
	
	
check$t();	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidGuardWebUseExternalUri", $_POST["SquidGuardWebUseExternalUri"]);
	$sock->SET_INFO("SquidGuardWebExternalUri", $_POST["SquidGuardWebExternalUri"]);
	$sock->SET_INFO("SquidGuardWebExternalUriSSL", $_POST["SquidGuardWebExternalUriSSL"]);
	
	if($_POST["SquidGuardWebUseExternalUri"]==1){
		$sock->SET_INFO("EnableSquidGuardHTTPService",0);
	}else{
		$sock->SET_INFO("EnableSquidGuardHTTPService",1);
	}
	$sock->getFrameWork("cmd.php?reload-squidguardWEB=yes");
}