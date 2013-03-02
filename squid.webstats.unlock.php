<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.main_cf_filtering.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.policyd-weight.inc');						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["UnlockWebStats"])){UnlockWebStats();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}

	$title=$tpl->_ENGINE_parse_body('{lock_unlock}');
	$html="RTMMail(500,'$page?popup=yes','$title');";
	echo $html;
	}
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	$t=time();
	$p=Paragraphe_switch_img("{lock_unlock}", "{unlock_webstats_explain2}","UnlockWebStats",$UnlockWebStats,null,450);
	
	$html="
	<table style='width:99%' class=form>
	<tr>
	<td>	$p</td>	
	</tr>
	<td align='right'><hr>".button("{apply}", "Save$t()","18px")."</td>
	</tr>
	</table>
	<script>
	var x_Save$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		RTMMailHide();
		CacheOff();
		QuickLinkSystems('section_webfiltering_dansguardian');
	}			
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('UnlockWebStats',document.getElementById('UnlockWebStats').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);	
	}			
</script>							
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function UnlockWebStats(){
	$sock=new sockets();
	$sock->SET_INFO("UnlockWebStats", $_POST["UnlockWebStats"]);
	
}