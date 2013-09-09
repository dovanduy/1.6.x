<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["service-status"])){service_status();exit;}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-popup"])){service_cmds_popup();exit;}
	if(isset($_GET["service-cmds-perform"])){service_cmds_perform();exit;}
	if(isset($_GET["main"])){main();exit;}

page();


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}");
	$html="YahooWin4('650','$page?service-cmds-popup=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$cmd=$_GET["service-cmds-popup"];
	$t=time();
	$html="
	<div id='pleasewait-$t''><center><div style='font-size:22px;margin:50px'>{please_wait}</div><img src='img/wait_verybig_mini_red.gif'></center></div>
	<div id='results-$t'></div>
	<script>LoadAjax('results-$t','$page?service-cmds-perform=$cmd&t=$t');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function service_cmds_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("ufdbguard.php?service-cmds={$_GET["service-cmds-perform"]}")));
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:14px'>".@implode("\n", $datas)."</textarea>
<script>
	 document.getElementById('pleasewait-$t').innerHTML='';
	RefreshTab('main_dansguardian_mainrules');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' width=5%>
			<div id='service-status-$t'></div>
		</td>
		<td valign='top' width=99%><div id='main-status-$t'></div></td>
	</tr>
	</table>
	<script>
		LoadAjax('service-status-$t','$page?service-status=yes&t=$t');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function main(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$t=time();
	$html="
		<div style='font-size:18px'>{webfilter}::{service_status}</div>
		<table style='width:99%' style='margin:10px'>
		<tr>
		<td valign='top'>
			<div id='artica-status-databases-$t'></div>
		</td>
		</tr>
		<tr>
		
		<td valign='top'>
			<div id='tlse-status-databases-$t'></div>
		</td>
		</tr>
		</table>

		
		<script>
			LoadAjaxTiny('ufdb-main-toolbox-status','dansguardian2.mainrules.php?rules-toolbox-left=yes');
			setTimeout('dbstatus$t()',3000);
			
			function dbstatus$t(){
				LoadAjaxTiny('artica-status-databases-$t','dansguardian2.databases.php?global-artica-status-databases=yes&t=$t');
				
			}			
		</script>
		
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
}


function service_status(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?ufdb-ini-status=yes')));
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	
	$html="
		<center style='margin-top:10px;margin-bottom:10px;width:100%'>
		<table style='width:10%'  class=form>
		<tbody>
		<tr>
			<td width=10% align='center;'>". imgtootltip("32-stop.png","{stop}","Loadjs('$page?service-cmds=stop')")."</td>
			<td width=10% align='center'>". imgtootltip("restart-32.png","{stop} & {start}","Loadjs('$page?service-cmds=restart')")."</td>
			<td width=10% align='center'>". imgtootltip("reload-32.png","{reload}","Loadjs('$page?service-cmds=reconfig')")."</td>
			<td width=10% align='center'>". imgtootltip("reconfigure-32.png","{reconfigure}","Loadjs('$page?service-cmds=reconfigure')")."</td>
			<td width=10% align='center'>". imgtootltip("32-run.png","{start}","Loadjs('$page?service-cmds=start')")."</td>
		</tr>
		</tbody>
		</table>
		</center>	
	<table style='width:245px' class=form>
	<tr>
		<td valign='top'>$APP_UFDBGUARD</td>
	</tr>
	<tr>
		<td valign='top'>$APP_SQUIDGUARD_HTTP</td>
	</tr>	
	</table>
	<br>
	<div id='ufdb-main-toolbox-status'></div>
	<script>
		LoadAjax('main-status-$t','$page?main=yes&t=$t');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
