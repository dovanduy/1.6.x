<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["ActAsASyslogServer"])){ActAsASyslogServerSave();exit;}
	if(isset($_GET["inline"])){popup();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["appliance-settings"])){appliance_settings();exit;}
	if(isset($_GET["squid-settings"])){squid();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{STATISTICS_APPLIANCE}");
	$html="YahooWin2(550,'$page?popup=yes','$title')";
	echo $html;
}

function popup(){
		$tpl=new templates();
		$page=CurrentPageName();
		$users=new usersMenus();
	
		$array["appliance-settings"]='{main_settings}';
		$array["RemoteStatsServer"]='{production_servers}';
		//if($users->APP_UFDBGUARD_INSTALLED){
			//$array["ufdbguard"]='{APP_UFDBGUARD}';
		//}
		
		
		$array["squid-settings"]='{APP_SQUID}';
		
		
	while (list ($num, $ligne) = each ($array) ){
		
			if($num=="RemoteStatsServer"){
				$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.statsappliance.clients.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;			
			
			}
			
			if($num=="ufdbguard"){
				$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.php?ufdbguard-options=yes&byAppliance=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
				continue;
			}
			
			
		
			$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		}
	
	$html="
		<div id='main_squid_statsquicklinks_config' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_squid_statsquicklinks_config').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function squid(){
	
	$templates_error=Paragraphe('squid-templates-64.png','{squid_templates_error}','{squid_templates_error_text}',"javascript:Loadjs('squid.templates.php')");
	
    $tr[]=$authenticate_users;
    $tr[]=$blackcomputer;
    $tr[]=$whitecomputer;
	$tr[]=$proxy_pac;
	$tr[]=$proxy_pac_rules;
	$tr[]=$APP_SQUIDKERAUTH;
	$tr[]=$templates_error;	
	
	$html="
	
	<center><div style='width:700px'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html);
	echo $html;		
}


function appliance_settings(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$enable=Paragraphe_switch_img("{enable_syslog_server}","{enable_syslog_server_text}","ActAsASyslogServer","$ActAsASyslogServer",null,540);
	$t=time();
	
	$html="<div class=form style='margin-top:10px'>
	<div style='font-size:16px;margin-bottom:10px'>{syslog_server}</div>
	<div id='$t'></div>
	<div class=text-info style='font-size:13px'>{STATISTICS_APPLIANCE_SYSLOG_EXPLAIN}</div>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td>
			<div id='ActAsASyslogServerDiv'>
				$enable
				<div style='text-align:right'><hr>". button("{apply}","ActAsASyslogServerSave()",16)."</div>
			</div>
	
		</td>
	</tr>
	</tbody>
	</table>
	<script>
		var x_ActAsASyslogServerSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			document.getElementById('$t').innerHTML='';
		}			
		
	function ActAsASyslogServerSave(){
		var XHR = new XHRConnection();
		AnimateDiv('$t');
		XHR.appendData('ActAsASyslogServer',document.getElementById('ActAsASyslogServer').value);
		XHR.sendAndLoad('$page', 'POST',x_ActAsASyslogServerSave);		
		}

	</script>
	</div>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ActAsASyslogServerSave(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer",$_POST["ActAsASyslogServer"]);
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
	if($_POST["ActAsASyslogServer"]==1){$sock->getFrameWork("squid.php?cron-tail-injector-plus=yes");}else{$sock->getFrameWork("squid.php?cron-tail-injector-moins=yes");	}
}
