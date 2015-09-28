<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.main_cf_filtering.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.policyd-weight.inc');						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ApplyFetchmailRules"])){ApplyFetchmailRules();exit;}
if(isset($_GET["reload-service"])){reload_service();exit;}
if(isset($_GET["reload-service-end"])){compile_end();exit;}
if(isset($_GET["Status"])){echo Status($_GET["Status"]);exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	$title=$tpl->_ENGINE_parse_body('{fetchmail_parameters_compilation}');
	$html="RTMMail(500,'$page?popup=yes&t={$_GET["t"]}','$title');";
	echo $html;
	}
	
	
function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	if(!$users->AsMailBoxAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	$t=$_GET["t"];
	$pourc=0;
	$table=Status(0);
	$color="#5DD13D";
	$html="
	<div class=explain>{APPLY_SETTINGS_FETCHMAIL}</div>
	<table style='width:100%'>
	<tr>
		<td width=1%><div id='wait_image'><img src='img/wait.gif'></div>
		</td>
		<td width=99%>
			<table style='width:100%'>
			<tr>
			<td>
				<div style='width:100%;background-color:white;padding-left:0px;border:1px solid $color'>
					<div id='progression_postfix_compile'>
						<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
							<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%</strong></center>
						</div>
					</div>
				</div>
			</td>
			</tr>
			</table>		
		</td>
	</tr>
	</table>
	<br>
	<div id='textlogs' style='width:99%;height:120px;overflow:auto'></div>
	
	<script>
	function StartCompileFetchmail$t(){
		setTimeout('ApplyFetchmailRules()',1000);
	}

	function finish$t(){
		ChangeStatusFetchMail(100);
		document.getElementById('wait_image').innerHTML='&nbsp;';
		document.getElementById('wait_image').innerHTML='&nbsp;';
		$('#flexRT$t').flexReload();
		RTMMailHide();
	}
	
	function ApplyFetchmailRules(){
		ChangeStatusFetchMail(40);
		LoadAjaxSilent('textlogs','$page?ApplyFetchmailRules=yes&t=$t');
		}
		

		
	var x_ChangeStatusFetchMail= function (obj) {
		var tempvalue=obj.responseText;
		if(document.getElementById('progression_postfix_compile')){
			document.getElementById('progression_postfix_compile').innerHTML=tempvalue;
		}
	}		
		
		
	function ChangeStatusFetchMail(number){
		var XHR = new XHRConnection();
		XHR.appendData('Status',number);
		XHR.sendAndLoad('$page', 'GET',x_ChangeStatusFetchMail);	
	}

	
	StartCompileFetchmail$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}
function Status($pourc){
$color="#5DD13D";	
$html="
	<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
		<strong style='color:#BCF3D6;font-size:14px;font-weight:bold'>{$pourc}%</strong></center>
	</div>
";	


return $html;
	
}


function ApplyFetchmailRules(){
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=$_GET["t"];
	
	$script="
	<div id='ApplyFetchmailRules'></div>
	<script>
		ChangeStatusFetchMail(15);
		LoadAjaxSilent('ApplyFetchmailRules','$page?reload-service=yes&t=$t');
	</script>	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{APP_FETCHMAIL}:{please_wait_configuring_the_module}:</strong></div>").$script;
	
$sock->getFrameWork("fetchmail.php?reconfigure=yes&MyCURLTIMEOUT=300");

	
}

function reload_service(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$t=$_GET["t"];
	$script="
	<div id='reload_service_fetchmail'></div>
	<script>
		ChangeStatusFetchMail(80);
		LoadAjaxSilent('reload_service_fetchmail','$page?reload-service-end=yes&t=$t');
	</script>
	";	
	
	echo $tpl->_ENGINE_parse_body("<div><strong style='font-size:16px'>{APP_FETCHMAIL}:{please_wait_reloading_service}:</strong></div>").$script;
	$sock->getFrameWork("fetchmail.php?reload-fetchmail=yes&tenir=yes&MyCURLTIMEOUT=300");

}

function compile_end(){	
		$tpl=new templates();
		$page=CurrentPageName();	
		$t=$_GET["t"];
	$script="
	<div id='compile_header_check'></div>
	<script>
		finish$t();
	</script>
	";				
		
	echo $tpl->_ENGINE_parse_body("<strong>{success}</strong>").$script;
		
}






?>