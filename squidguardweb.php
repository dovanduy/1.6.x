<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
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
	if(isset($_GET["EnableSquidGuardHTTPService"])){save();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["per-categories"])){per_category_main();exit;}
	if(isset($_GET["per-categories-settings"])){per_category_settings();exit;}
	if(isset($_POST["external_uri"])){per_category_settings_save();exit;}
js();	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{banned_page_webservice}");
	header("content-type: application/x-javascript");
	$html="
		YahooWin5('700','$page?tabs=yes','$title');
	";
	echo $html;
		
		
}

function per_category_main(){
	$tpl=new templates();
	$dans=new dansguardian_rules();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
	}
	$cats=$dans->LoadBlackListes();
	$page=CurrentPageName();
	$tpl=new templates();
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$t=time();
	$newcat[null]="{select}";
	$html="
	$error
	<div style='font-size:14px' class=explain>{ufdbguard_banned_perso_text}</div>
		<table style='width:99%' class=form>
	<tr>
		<td class=legend>{category}:</td>
		<td>". Field_array_Hash($newcat,$t,null,"catgorized_choosen()","style:font-size:16px")."</td>
	</tR>
	</table>
	<div id='free-category-form'></div>
	
	<script>
	function catgorized_choosen(){
		LoadAjaxTiny('free-category-form','$page?per-categories-settings='+escape(document.getElementById('$t').value));
	}
	
	catgorized_choosen();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function per_category_settings_save(){
	$sock=new sockets();	
	$hash=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));	
	$hash[$_POST["category"]]=$_POST;
	$newhash=base64_encode(serialize($hash));
	$sock->SaveConfigFile($newhash, "UfdbGuardRedirectCategories");
	$dans=new dansguardian_rules();
	$dans->RestartFilters();	
	
}

function per_category_settings(){
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	$category=$_GET["per-categories-settings"];
	if(trim($category)==null){die();}
	$explain=$cats[$category];
	$page=CurrentPageName();
	$sock=new sockets();	
	$hash=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
	$datas=$hash[$category];
	$tpl=new templates();
	$block=0;
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$block=1;
		$MOD_TEMPLATE_ERROR_LICENSE=$tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
	}
	
	$t=time();
	$html="<div class=explain style='font-size:14px'>$explain</div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:14px'>{enable}:</td>
		<td>". Field_checkbox("enable-$t",1,$datas["enable"],"enable_uri_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{external_uri}:</td>
		<td>". Field_checkbox("external_uri",1,$datas["external_uri"],"external_uri_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{redirect_url}:</td>
		<td>". Field_text("redirect_url",$datas["redirect_url"],"font-size:14px;width:99%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{blank_page}:</td>
		<td>". Field_checkbox("blank_page",1,$datas["blank_page"],"blank_page_check()")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='center' style='font-size:16px'>{template}</td>
	</tr>
	<tr>
		<td colspan=2 align='center' style='font-size:16px'>
			<textarea style='width:100%;height:120px;overflow:auto;font-size:12px' id='template_data'>{$datas["template_data"]}</textarea></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SavePerCatForm()",18)."</td>
	</tr>
	</tbody>
	</table>	
	</div>
	<script>
		var x_SavePerCatForm= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			catgorized_choosen();
		}		
	
	
		function SavePerCatForm(){
			var block=$block;
			if(block==1){
				alert('$MOD_TEMPLATE_ERROR_LICENSE');
				return;
			}
	      	var XHR = new XHRConnection();
	     	if(document.getElementById('external_uri').checked){XHR.appendData('external_uri',1);}else{XHR.appendData('external_uri',0);}
	    	if(document.getElementById('blank_page').checked){XHR.appendData('blank_page',1);}else{XHR.appendData('blank_page',0);}
	    	if(document.getElementById('enable-$t').checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
	    	XHR.appendData('redirect_url',document.getElementById('redirect_url').value);
	    	XHR.appendData('template_data',document.getElementById('template_data').value);
	 		XHR.appendData('category','$category');
	 		
	     	AnimateDiv('free-category-form');
	     	XHR.sendAndLoad('$page', 'POST',x_SavePerCatForm);     	
		}
	
	
		function external_uri_check(){
			if(!document.getElementById('enable-$t').checked){return;}
			document.getElementById('redirect_url').disabled=true;
			document.getElementById('blank_page').disabled=true;
			document.getElementById('template_data').disabled=true;
			
			if(document.getElementById('external_uri').checked){
				document.getElementById('redirect_url').disabled=false;
			}else{
				document.getElementById('blank_page').disabled=false;
				document.getElementById('template_data').disabled=false;		
			}
			
			blank_page_check();
		
		}
		
		function blank_page_check(){
			if(!document.getElementById('enable-$t').checked){return;}
			if(document.getElementById('external_uri').checked){return;}
			document.getElementById('template_data').disabled=true;
			
			if(document.getElementById('blank_page').checked){
				document.getElementById('template_data').disabled=true;
			}else{
				document.getElementById('template_data').disabled=false;		
			}
		
		}

		function enable_uri_check(){
			document.getElementById('redirect_url').disabled=true;
			document.getElementById('blank_page').disabled=true;
			document.getElementById('template_data').disabled=true;
		
			document.getElementById('external_uri').disabled=true;
			if(document.getElementById('enable-$t').checked){
				document.getElementById('external_uri').disabled=false;
			}
			external_uri_check();
		
		}
		enable_uri_check();
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function tabs(){
	$tpl=new templates();
	$array["popup"]='{default_webrule}';
	$array["per-categories"]='{per_category}';
	$array["service"]='{status}';
	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="service"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.service.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_squidguardweb_error_pages");
	
	
}


function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$LICENSE=0;
	if($users->CORP_LICENSE){$LICENSE=1;}
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	if(strlen(trim($EnableSquidGuardHTTPService))==0){$EnableSquidGuardHTTPService=1;}
	$SquidGuardWebUseExternalUri=$sock->GET_INFO("SquidGuardWebUseExternalUri");
	$SquidGuardWebExternalUri=$sock->GET_INFO("SquidGuardWebExternalUri");
	$SquidGuardWebExternalUriSSL=$sock->GET_INFO("SquidGuardWebExternalUriSSL");
	$SquidGuardApacheSSLPort=$sock->GET_INFO("SquidGuardApacheSSLPort");
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	$SquidGuardWebBlankReferer=intval($sock->GET_INFO("SquidGuardWebBlankReferer"));
	
	$SquidGuardApacheShowGroupName=$sock->GET_INFO("SquidGuardApacheShowGroupName");
	$SquidGuardApacheShowGroupNameTXT=$sock->GET_INFO("SquidGuardApacheShowGroupNameTXT");
	
	if(!is_numeric($SquidGuardApacheShowGroupName)){$SquidGuardApacheShowGroupName=0;}
	if(!is_numeric($SquidGuardApacheSSLPort)){$SquidGuardApacheSSLPort=9025;}
	if($SquidGuardApachePort==null){$SquidGuardApachePort=9020;}
	
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	$fulluri=$sock->GET_INFO("SquidGuardIPWeb");
	$SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
	$SquidGuardWebAllowUnblock=$sock->GET_INFO("SquidGuardWebAllowUnblock");
	
	if(!is_numeric($SquidGuardWebAllowUnblock)){$SquidGuardWebAllowUnblock=0;}
	if(!is_numeric($SquidGuardWebUseExternalUri)){$SquidGuardWebUseExternalUri=0;}
	
	
	
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	
	
	if($SquidGuardIPWeb==null){
			$SquidGuardIPWeb="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/exec.squidguard.php";
			$SquidGuardIPWebSSL="https://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApacheSSLPort."/exec.squidguard.php";
			$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/exec.squidguard.php";
			$fulluriSSL="https://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApacheSSLPort."/exec.squidguard.php";
	}	
	$SquidGuardIPWeb=str_replace("http://",null,$SquidGuardIPWeb);
	$SquidGuardIPWeb=str_replace("https://",null,$SquidGuardIPWeb);
	
	if(preg_match("#\/(.+?):([0-9]+)\/#",$SquidGuardIPWeb,$re)){$SquidGuardIPWeb="{$re[1]}:{$re[2]}";}
	
	if(preg_match("#(.+?):([0-9]+)#",$SquidGuardIPWeb,$re)){
		$SquidGuardServerName=$re[1];
		$SquidGuardApachePort=$re[2];
	}	

	if(!is_numeric($SquidGuardWebFollowExtensions)){$SquidGuardWebFollowExtensions=1;}
		

	
	$html="
	<div id='EnableSquidGuardHTTPServiceDiv'>
	<div class=explain style='font-size:14px'>{banned_page_webservice_text}</div>
	<hr>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enable_http_service}:</td>
		<td>". Field_checkbox("EnableSquidGuardHTTPService",1,$EnableSquidGuardHTTPService,"EnableSquidGuardHTTPService()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("listen_port_squidguard",$SquidGuardApachePort,"font-size:14px;padding:3px;width:60px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port} (SSL):</td>
		<td>". Field_text("listen_port_squidguard_ssl",$SquidGuardApacheSSLPort,"font-size:14px;padding:3px;width:60px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{FollowExtensions}:</td>
		<td>". Field_checkbox("SquidGuardWebFollowExtensions",1,$SquidGuardWebFollowExtensions)."</td>
		<td>". help_icon("{SquidGuardWebFollowExtensions_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{SquidGuardWebBlankReferer}:</td>
		<td>". Field_checkbox("SquidGuardWebBlankReferer",1,$SquidGuardWebBlankReferer)."</td>
		<td>". help_icon("{SquidGuardWebBlankReferer_explain}")."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:14px'>{SquidGuardApacheShowGroupName}:</td>
		<td>". Field_checkbox("SquidGuardApacheShowGroupName",1,$SquidGuardApacheShowGroupName)."</td>
		<td>". help_icon("{SquidGuardApacheShowGroupName_explain}")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{ou}:</td>
		<td>". Field_text("SquidGuardApacheShowGroupNameTXT",$SquidGuardApacheShowGroupNameTXT,"font-size:14px;padding:3px;width:260px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:14px'>{allow_unblock}:</td>
		<td>". Field_checkbox("SquidGuardWebAllowUnblock",1,$SquidGuardWebAllowUnblock)."</td>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squidguardweb.unblock.php')\" style='font-size:14px;text-decoration:underline'>{settings}</a></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td style='font-size:14px'>". Field_text("servername_squidguard",$SquidGuardServerName,"font-size:14px;padding:3px;width:180px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{fulluri}:</td>
		<td style='font-size:14px'>". Field_text("fulluri","$fulluri","font-size:14px;padding:3px;width:290px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{fulluri} (ssl):</td>
		<td style='font-size:14px'>". Field_text("fulluriSSL","$fulluriSSL","font-size:14px;padding:3px;width:290px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td colspan=2><hr></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{use_external_link}:</td>
		<td>". Field_checkbox("SquidGuardWebUseExternalUri",1,$SquidGuardWebUseExternalUri,"EnableSquidGuardHTTPService()")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{fulluri}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUri","$SquidGuardWebExternalUri","font-size:14px;padding:3px;width:290px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{fulluri}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardWebExternalUriSSL","$SquidGuardWebExternalUriSSL","font-size:14px;padding:3px;width:290px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSquidGuardHTTPService()",16)."</td>
	</tr>	
	</table>
	</div>
	<script>
		function EnableSquidGuardHTTPService(){
			 var LICENSE=$LICENSE;
			 document.getElementById('listen_port_squidguard').disabled=true;
			 document.getElementById('listen_port_squidguard_ssl').disabled=true;
			 
			 document.getElementById('servername_squidguard').disabled=true;
			 
			 document.getElementById('fulluri').disabled=true;
			 document.getElementById('fulluriSSL').disabled=true;
			 
			 document.getElementById('SquidGuardWebFollowExtensions').disabled=true;
			 document.getElementById('SquidGuardWebAllowUnblock').disabled=true;
			 
			 document.getElementById('SquidGuardWebExternalUri').disabled=true;
			 document.getElementById('SquidGuardWebExternalUriSSL').disabled=true;
			 document.getElementById('SquidGuardApacheShowGroupName').disabled=true;
			 document.getElementById('SquidGuardApacheShowGroupNameTXT').disabled=true;
			 document.getElementById('SquidGuardWebBlankReferer').disabled=true;
			 
			 
			 
			 
			 if(!document.getElementById('SquidGuardWebUseExternalUri').checked){
			 	document.getElementById('EnableSquidGuardHTTPService').disabled=false;
			 
				 if(document.getElementById('EnableSquidGuardHTTPService').checked){
				 	document.getElementById('listen_port_squidguard').disabled=false;
				 	document.getElementById('listen_port_squidguard_ssl').disabled=false;
				 	
				 	document.getElementById('servername_squidguard').disabled=false;
				 	document.getElementById('SquidGuardWebAllowUnblock').disabled=false;
				 	document.getElementById('SquidGuardWebFollowExtensions').disabled=false;
				 	document.getElementById('SquidGuardWebBlankReferer').disabled=false;
				 	if(LICENSE==1){
				 		document.getElementById('SquidGuardApacheShowGroupName').disabled=false;
				 		document.getElementById('SquidGuardApacheShowGroupNameTXT').disabled=false;
				 		}
				 }else{
				 	document.getElementById('fulluri').disabled=false;
				 	document.getElementById('fulluriSSL').disabled=false;
				 }
			 
			 }else{
			 	document.getElementById('SquidGuardWebExternalUriSSL').disabled=false;
			 	document.getElementById('SquidGuardWebExternalUri').disabled=false;
			 	document.getElementById('listen_port_squidguard').disabled=true;
			 	document.getElementById('listen_port_squidguard_ssl').disabled=true;
			 	document.getElementById('SquidGuardApacheShowGroupName').disabled=true;
			 	document.getElementById('SquidGuardApacheShowGroupNameTXT').disabled=true;
			 	document.getElementById('SquidGuardWebBlankReferer').disabled=false;
			 	
			 
			 }
		
		}
		
var x_SaveSquidGuardHTTPService=function(obj){
	  YahooWin5Hide();
      Loadjs('$page');
	}

	function SaveSquidGuardHTTPService(){
      var XHR = new XHRConnection();
     if(document.getElementById('EnableSquidGuardHTTPService').checked){XHR.appendData('EnableSquidGuardHTTPService',1);}else{XHR.appendData('EnableSquidGuardHTTPService',0);}
     if(document.getElementById('SquidGuardWebFollowExtensions').checked){XHR.appendData('SquidGuardWebFollowExtensions',1);}else{XHR.appendData('SquidGuardWebFollowExtensions',0);}
     if(document.getElementById('SquidGuardWebAllowUnblock').checked){XHR.appendData('SquidGuardWebAllowUnblock',1);}else{XHR.appendData('SquidGuardWebAllowUnblock',0);}
     if(document.getElementById('SquidGuardWebUseExternalUri').checked){XHR.appendData('SquidGuardWebUseExternalUri',1);}else{XHR.appendData('SquidGuardWebUseExternalUri',0);}
     if(document.getElementById('SquidGuardApacheShowGroupName').checked){XHR.appendData('SquidGuardApacheShowGroupName',1);}else{XHR.appendData('SquidGuardApacheShowGroupName',0);}
     if(document.getElementById('SquidGuardWebBlankReferer').checked){XHR.appendData('SquidGuardWebBlankReferer',1);}else{XHR.appendData('SquidGuardWebBlankReferer',0);}


     
	 XHR.appendData('listen_port_squidguard',document.getElementById('listen_port_squidguard').value);
	 XHR.appendData('listen_port_squidguard_ssl',document.getElementById('listen_port_squidguard_ssl').value);
	 XHR.appendData('SquidGuardApacheShowGroupNameTXT',document.getElementById('SquidGuardApacheShowGroupNameTXT').value);
	 
	 
	 
     XHR.appendData('servername_squidguard',document.getElementById('servername_squidguard').value);
     
     XHR.appendData('SquidGuardWebExternalUri',document.getElementById('SquidGuardWebExternalUri').value);
     XHR.appendData('SquidGuardWebExternalUriSSL',document.getElementById('SquidGuardWebExternalUriSSL').value);
     
     XHR.appendData('fulluri',document.getElementById('fulluri').value);
     XHR.appendData('fulluriSSL',document.getElementById('fulluriSSL').value);
     AnimateDiv('EnableSquidGuardHTTPServiceDiv'); 
     XHR.sendAndLoad('$page', 'GET',x_SaveSquidGuardHTTPService);     	
	
	}
	
	EnableSquidGuardHTTPService();";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new sockets();
	if($_GET["EnableSquidGuardHTTPService"]==0){
		$SquidGuardIPWeb=$_GET["fulluri"];
		$SquidGuardIPWebSSL=$_GET["fulluriSSL"];
	}else{
		$SquidGuardIPWeb="http://".$_GET["servername_squidguard"].":".$_GET["listen_port_squidguard"]."/exec.squidguard.php";
		$SquidGuardIPWebSSL="https://".$_GET["servername_squidguard"].":".$_GET["listen_port_squidguard_ssl"]."/exec.squidguard.php";
	}
	
	
	
	
	$sock->SET_INFO("SquidGuardWebUseExternalUri",$_GET["SquidGuardWebUseExternalUri"]);
	$sock->SET_INFO("SquidGuardWebExternalUri",$_GET["SquidGuardWebExternalUri"]);
	$sock->SET_INFO("SquidGuardWebExternalUriSSL",$_GET["SquidGuardWebExternalUriSSL"]);
	
	
	$sock->SET_INFO("SquidGuardApacheShowGroupNameTXT", $_GET["SquidGuardApacheShowGroupNameTXT"]);
	$sock->SET_INFO("SquidGuardApacheShowGroupName", $_GET["SquidGuardApacheShowGroupName"]);
	$sock->SET_INFO("SquidGuardWebFollowExtensions",$_GET["SquidGuardWebFollowExtensions"]);
	$sock->SET_INFO("SquidGuardApachePort",$_GET["listen_port_squidguard"]);
	$sock->SET_INFO("SquidGuardApacheSSLPort",$_GET["listen_port_squidguard_ssl"]);
	$sock->SET_INFO("EnableSquidGuardHTTPService",$_GET["EnableSquidGuardHTTPService"]);
	$sock->SET_INFO("SquidGuardWebAllowUnblock",$_GET["SquidGuardWebAllowUnblock"]);
	$sock->SET_INFO("SquidGuardWebBlankReferer",$_GET["SquidGuardWebBlankReferer"]);
	
	
	
	$sock->SET_INFO("SquidGuardIPWeb",$SquidGuardIPWeb);
	$sock->SET_INFO("SquidGuardIPWebSSL",$SquidGuardIPWebSSL);
	$sock->getFrameWork("cmd.php?squid-wrapzap=yes");
	$sock->getFrameWork("cmd.php?reload-squidguardWEB=yes");
	$dans=new dansguardian_rules();
	$dans->RestartFilters();	
	
	
}

?>