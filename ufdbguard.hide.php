<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["UfdbGuardHide"])){Save();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}::{hide}");
	$page=CurrentPageName();
	$html="YahooWin3('550','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();	
	$sock=new sockets();
	$UfdbGuardHide=$sock->GET_INFO("UfdbGuardHide");
	if(!is_numeric($UfdbGuardHide)){$UfdbGuardHide=0;}
	
	
	$p=Paragraphe_switch_img("{hide_webfiltering_section}", "{hide_webfiltering_section_explain}","UfdbGuardHide",$UfdbGuardHide,null,400);
	
	
	$html="<div style='font-size:14px' id='$t-div'></div>
		
	<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
		
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>".button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
	
	
	
	
	<script>
	var x_SavePicScan$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>5){alert('Error:`'+tempvalue.length+'`::'+tempvalue);}
      	YahooWin3Hide();
      	CacheOff();
      	QuickLinkSystems('section_webfiltering_dansguardian')
     	}	

	function Save$t(){
		
			var XHR = new XHRConnection();
			XHR.appendData('UfdbGuardHide',document.getElementById('UfdbGuardHide').value);
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SavePicScan$t);		
		
	
	}		
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("UfdbGuardHide", $_POST["UfdbGuardHide"]);
	
	
}

