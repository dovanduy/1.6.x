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
	
	$SquidGuardRedirectBehavior=$sock->GET_INFO("SquidGuardRedirectBehavior");
	$SquidGuardRedirectSSLBehavior=$sock->GET_INFO("SquidGuardRedirectSSLBehavior");
	$SquidGuardRedirectHTTPCode=intval($sock->GET_INFO("SquidGuardRedirectHTTPCode"));
	
	if($SquidGuardRedirectBehavior==null){$SquidGuardRedirectBehavior="url";}
	if($SquidGuardRedirectSSLBehavior==null){$SquidGuardRedirectSSLBehavior="url";}
	if(!is_numeric($SquidGuardRedirectHTTPCode)){$SquidGuardRedirectHTTPCode=302;}
	
	$redirect_behaviorA["url"]="{redirect_connexion}";
	$redirect_behaviorA["url-rewrite"]="{rewrite_url}";
	
	$HTTP_CODE[301]="{Moved_Permanently} (301)";
	$HTTP_CODE[302]="{Moved_Temporarily} (302)";
	$HTTP_CODE[303]="{http_code_see_other} (303)";
	$HTTP_CODE[307]="{Moved_Temporarily} (307)";
	
	$html="
	<div style='width:98%' class=form>		
	<div style='font-size:50px;margin-bottom:20px'>{remote_webpage}</div>
	". Paragraphe_switch_img("{UfdbUseGlobalWebPage}", "{UfdbUseGlobalWebPage_explain}",
			"SquidGuardWebUseExternalUri",$SquidGuardWebUseExternalUri,null,1000,"check$t()").
	"<table style='width:98%'>
	<tr>
		<td class=legend style='font-size:24px'>{fulluri}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUri","$SquidGuardWebExternalUri","font-size:24px;padding:3px;width:1073px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{fulluri} (ssl):</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUriSSL","$SquidGuardWebExternalUriSSL","font-size:24px;padding:3px;width:1073px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{redirect_behavior}:</td>
		<td>". Field_array_Hash($redirect_behaviorA,"SquidGuardRedirectBehavior-$t",$SquidGuardRedirectBehavior,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{redirect_behavior} (ssl):</td>
		<td>". Field_array_Hash($redirect_behaviorA,"SquidGuardRedirectSSLBehavior-$t",$SquidGuardRedirectSSLBehavior,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
						
	<tr>
		<td class=legend style='font-size:24px'>{redirect_code}:</td>
		<td>". Field_array_Hash($HTTP_CODE,"SquidGuardRedirectHTTPCode-$t",$SquidGuardRedirectHTTPCode,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
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
		document.getElementById('SquidGuardRedirectSSLBehavior-$t').disabled=true;
		document.getElementById('SquidGuardRedirectBehavior-$t').disabled=true;
	 	document.getElementById('SquidGuardRedirectHTTPCode-$t').disabled=true;
		
		var enable=document.getElementById('SquidGuardWebUseExternalUri').value;
		if(enable==1){
			document.getElementById('SquidGuardWebExternalUri').disabled=false;
			document.getElementById('SquidGuardWebExternalUriSSL').disabled=false;
			document.getElementById('SquidGuardRedirectSSLBehavior-$t').disabled=false;
			document.getElementById('SquidGuardRedirectBehavior-$t').disabled=false;
	 		document.getElementById('SquidGuardRedirectHTTPCode-$t').disabled=false;
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
    
     
     XHR.appendData('SquidGuardRedirectHTTPCode',document.getElementById('SquidGuardRedirectHTTPCode-$t').value);
     XHR.appendData('SquidGuardRedirectBehavior',document.getElementById('SquidGuardRedirectBehavior-$t').value);
     XHR.appendData('SquidGuardRedirectSSLBehavior',document.getElementById('SquidGuardRedirectSSLBehavior-$t').value);
     
    
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
	$sock->SET_INFO("SquidGuardRedirectHTTPCode",$_GET["SquidGuardRedirectHTTPCode"]);
	$sock->SET_INFO("SquidGuardRedirectBehavior",$_GET["SquidGuardRedirectBehavior"]);
	$sock->SET_INFO("SquidGuardRedirectSSLBehavior",$_GET["SquidGuardRedirectSSLBehavior"]);
	
	if($_POST["SquidGuardWebUseExternalUri"]==1){
		$sock->SET_INFO("EnableSquidGuardHTTPService",0);
	}else{
		$sock->SET_INFO("EnableSquidGuardHTTPService",1);
	}
	$sock->getFrameWork("cmd.php?reload-squidguardWEB=yes");
}