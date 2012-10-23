<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["compile-infos"])){compile_infos();exit;}

js();


function js(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{proxy_clients_was_notified}")."');";
		return;
	}	
	
	$sock->getFrameWork("squid.php?compile-by-interface=yes");
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{building_parameters}");
	$html="YahooSetupControlModalFixed('700','$page?popup=yes','$title')";
	echo $html;
	
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{please_wait},{building_parameters}");
	$html="
	<div style='width:100%;min-height:600px'>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width=99%>
		<div id='$t' style='width:100%;min-height:600px'>	<div style='font-size:18px'>$title</div>
	<center style='margin:20px;margin-top:100px'><img src='img/wait_verybig_mini_red.gif'></center></div>
	</td>
	</tr>
	</table>
	</div>
	<script>
		function SquidCompileAmorce$t(){
			LoadAjax('$t','$page?compile-infos=yes&t=$t');
		}
		
		setTimeout('SquidCompileAmorce$t()',2000);
	</script>
	
	";
	
	echo $html;

}

function compile_infos_wait(){
	//YahooSetupControlHide
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	
	$title=$tpl->_ENGINE_parse_body("{please_wait},{building_parameters}");
	
	$html="
	<div style='font-size:18px'>$title</div>
	<center style='margin:20px;margin-top:100px'><img src='img/wait_verybig_mini_red.gif'></center>
	<script>
		function SquidCompileRestartWait$t(){
			LoadAjax('$t','$page?compile-infos=yes&t=$t');
		
		}
	
	
		if(YahooSetupControlOpen()){
			setTimeout('SquidCompileRestartWait$t()',5000);
		
		}
		
	
	</script>";
	echo $html;
	
}

function compile_infos(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{close}");
	if(!is_file("ressources/logs/web/squid.compile.txt")){
		compile_infos_wait();return;
		
	}
	
	$f=file("ressources/logs/web/squid.compile.txt");
	krsort($f);
	$html="<div style='width:100%;height:550px;overflow:auto'>";
	while (list ($index, $val) = each ($f) ){
		$html=$html."<div><code style='font-size:12px'>$val</code></div>";
		
	}
	
	echo $html."</div>
	<hr>
	<center style='margin:5px'>".button($title, "YahooSetupControlHide()",16)."</center>
	
	";
}

