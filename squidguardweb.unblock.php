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
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidGuardWebAllowUnblockSinglePassContent"])){SaveBlockSettings();exit;}
js();	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{banned_page_webservice}::{unblock_parameters}");
	
	$html="
		YahooWin6('550','$page?tabs=yes','$title');
	";
	echo $html;
		
		
}
function tabs(){
	$tpl=new templates();
	$array["popup"]='{unblock_parameters}';
	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=unblock_parameters_tabs style='width:100%;height:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#unblock_parameters_tabs').tabs();
			
			
			});
		</script>";	
	
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidGuardWebAllowUnblockSinglePass=$sock->GET_INFO("SquidGuardWebAllowUnblockSinglePass");
	$SquidGuardWebAllowUnblockSinglePassContent=$sock->GET_INFO("SquidGuardWebAllowUnblockSinglePassContent");
	$SquidGuardWebUseLocalDatabase=$sock->GET_INFO("SquidGuardWebUseLocalDatabase");
	$html="
<div style='width:95%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px'>{UseAglobalPassword}:</td>
		<td>". Field_checkbox("SquidGuardWebAllowUnblockSinglePass",1,$SquidGuardWebAllowUnblockSinglePass,"EnableSquidGuardWebAllowUnblockSinglePass()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{single_password}:</td>
		<td>". Field_password("SquidGuardWebAllowUnblockSinglePassContent",$SquidGuardWebAllowUnblockSinglePassContent,"font-size:14px;padding:3px;width:160px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{UseLocalDatabase}:</td>
		<td>". Field_checkbox("SquidGuardWebUseLocalDatabase",1,$SquidGuardWebUseLocalDatabase,"SquidGuardWebUseLocalDatabaseCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSquidGuardPassService()",16)."</td>
	</tr>	
	</table>
</div>
	<script>
		function EnableSquidGuardWebAllowUnblockSinglePass(){
			 document.getElementById('SquidGuardWebAllowUnblockSinglePassContent').disabled=true;
				
			 if(document.getElementById('SquidGuardWebAllowUnblockSinglePass').checked){
			 	document.getElementById('SquidGuardWebAllowUnblockSinglePassContent').disabled=false;
			 	document.getElementById('servername_squidguard').disabled=false;
			 	}
		}
		
	var X_SaveSquidGuardPassService= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		RefreshTab('unblock_parameters_tabs');
		}		
		
	function SaveSquidGuardPassService(){
		var password=encodeURIComponent(document.getElementById('SquidGuardWebAllowUnblockSinglePassContent').value);
		var XHR = new XHRConnection();
		XHR.appendData('SquidGuardWebAllowUnblockSinglePassContent',password);
		if(document.getElementById('SquidGuardWebAllowUnblockSinglePass').checked){XHR.appendData('SquidGuardWebAllowUnblockSinglePass',1);}else{XHR.appendData('SquidGuardWebAllowUnblockSinglePass',0);}
		if(document.getElementById('SquidGuardWebUseLocalDatabase').checked){XHR.appendData('SquidGuardWebUseLocalDatabase',1);}else{XHR.appendData('SquidGuardWebUseLocalDatabase',0);}
		XHR.sendAndLoad('$page', 'POST',X_SaveSquidGuardPassService);     		
	}
	
	function SquidGuardWebUseLocalDatabaseCheck(){
		 document.getElementById('SquidGuardWebAllowUnblockSinglePassContent').disabled=true;
		 document.getElementById('SquidGuardWebAllowUnblockSinglePass').disabled=true;
		 if(document.getElementById('SquidGuardWebUseLocalDatabase').checked){
		 	 document.getElementById('SquidGuardWebAllowUnblockSinglePass').checked=false;
		 	 
		 }else{
		 	document.getElementById('SquidGuardWebAllowUnblockSinglePassContent').disabled=false;
		 	document.getElementById('SquidGuardWebAllowUnblockSinglePass').disabled=false;		 
		 }
	}

	EnableSquidGuardWebAllowUnblockSinglePass();
	SquidGuardWebUseLocalDatabaseCheck();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function SaveBlockSettings(){
	$sock=new sockets();
	$_POST["SquidGuardWebAllowUnblockSinglePassContent"]=url_decode_special_tool($_POST["SquidGuardWebAllowUnblockSinglePassContent"]);
	$sock->SET_INFO("SquidGuardWebAllowUnblockSinglePass",$_POST["SquidGuardWebAllowUnblockSinglePass"]);
	$sock->SET_INFO("SquidGuardWebAllowUnblockSinglePassContent",$_POST["SquidGuardWebAllowUnblockSinglePassContent"]);	
	$sock->SET_INFO("SquidGuardWebUseLocalDatabase",$_POST["SquidGuardWebUseLocalDatabase"]);
	
	
	
}