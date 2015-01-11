<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	include_once('ressources/class.mysql.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["UpdateUtilityRedirectEnable"])){Save();exit;}
	
	
	js();
	
	
function js() {
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->APP_UFDBGUARD_INSTALLED){
		echo "alert('".$tpl->javascript_parse_text("{error_webfiltering_engine_not_installed}")."');";
		return;
		
	}
	$title=$tpl->_ENGINE_parse_body("{UpdateUtility}");
	$page=CurrentPageName();
	$html="YahooWin3('690','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	
	$t=time();
	$q=new mysql();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$results=$q->QUERY_SQL("SELECT servername FROM freeweb WHERE groupware='UPDATEUTILITY'","artica_backup");
	$hash[null]="{select}";
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		$hash[$servername]=$servername;
	
	}	
	$UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
	$UpdateUtilityExternWbsrv=$sock->GET_INFO("UpdateUtilityExternWbsrv");
	$UpdateUtilityHTTPSRV=$sock->GET_INFO("UpdateUtilityHTTPSRV");
	$UpdateUtilityExternWbsrvAddr=$sock->GET_INFO("UpdateUtilityExternWbsrvAddr");
	if($UpdateUtilityExternWbsrvAddr==null){$UpdateUtilityExternWbsrvAddr="updateserver.example:9010";}
	if(!is_numeric($UpdateUtilityExternWbsrv)){$UpdateUtilityExternWbsrv=0;}
	
	
	$html="
	<div id='$t'></div>		
	<div class=text-info style='font-size:14px'>{ufdbguard_updateutility_explain}</div>
	
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{enable_filter_redirection}:</td>
			<td>". Field_checkbox("UpdateUtilityRedirectEnable", 1,$UpdateUtilityRedirectEnable,"CheckU$t()")."</td>
			<td>&nbsp;</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{webserver}:</td>
			<td>". Field_array_Hash($hash, "UpdateUtilityHTTPSRV",$UpdateUtilityHTTPSRV,null,null,0,"font-size:16px")."</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{use_external_server}:</td>
			<td>". Field_checkbox("UpdateUtilityExternWbsrv", 1,$UpdateUtilityExternWbsrv,"CheckU$t()")."</td>
			<td>&nbsp;</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{webserver}:</td>
			<td>". Field_text("UpdateUtilityExternWbsrvAddr",$UpdateUtilityExternWbsrvAddr,"font-size:16px;width:250px")."</td>
			<td>&nbsp;</td>
		</tr>												
		<tr>
			<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
		</tr>
		</table>
<script>
	var x_Save$t= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      document.getElementById('$t').innerHTML='';
	}	

	function Save$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('UpdateUtilityRedirectEnable').checked){XHR.appendData('UpdateUtilityRedirectEnable','1');}else{XHR.appendData('UpdateUtilityRedirectEnable','0');}
			if(document.getElementById('UpdateUtilityExternWbsrv').checked){XHR.appendData('UpdateUtilityExternWbsrv','1');}else{XHR.appendData('UpdateUtilityExternWbsrv','0');}
			XHR.appendData('UpdateUtilityHTTPSRV',document.getElementById('UpdateUtilityHTTPSRV').value);
			XHR.appendData('UpdateUtilityExternWbsrvAddr',document.getElementById('UpdateUtilityExternWbsrvAddr').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);	
		}	

	function CheckU$t(){
		document.getElementById('UpdateUtilityHTTPSRV').disabled=true;
		document.getElementById('UpdateUtilityExternWbsrvAddr').disabled=true;
		if(document.getElementById('UpdateUtilityRedirectEnable').checked){
			document.getElementById('UpdateUtilityHTTPSRV').disabled=false;
		}
		
		if(document.getElementById('UpdateUtilityExternWbsrv').checked){
			document.getElementById('UpdateUtilityHTTPSRV').disabled=true;
			document.getElementById('UpdateUtilityExternWbsrvAddr').disabled=false;		
		}
	}
	CheckU$t()
</script>
	
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("UpdateUtilityRedirectEnable", $_POST["UpdateUtilityRedirectEnable"]);
	$sock->SET_INFO("UpdateUtilityHTTPSRV", $_POST["UpdateUtilityHTTPSRV"]);
	
	$sock->SET_INFO("UpdateUtilityExternWbsrvAddr", $_POST["UpdateUtilityExternWbsrvAddr"]);
	$sock->SET_INFO("UpdateUtilityExternWbsrv", $_POST["UpdateUtilityExternWbsrv"]);
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{need_reconfigure_ufdb}");
}
