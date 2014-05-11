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
if(isset($_POST["compile-progress"])){compile_infos();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	echo "Loadjs('squid.reconfigure.php')";
	return;
	
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
	$title=$tpl->javascript_parse_text("{building_parameters}");
	$html="YahooSetupControlModalFixed('700','$page?popup=yes','$title')";
	echo $html;
	
	
}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{please_wait},{building_parameters}");
	$done_title=$tpl->javascript_parse_text("{success}");
	$html="
	<div style='width:100%;min-height:600px'>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width=99%>
		<div id='$t' style='width:100%;min-height:600px'>	
			<div style='font-size:22px;margin:15px' id='title-$t'>
			<table style='width:100%'>
			<tr>
			<td style='width:99%' nowrap><span style='font-size:22px;'>$title</span></td>
			<td width=35px><img src='img/wait-clock.gif'></td>
			</tr>
			</table>
			
			</div>
			<div id='progress-text-$t'>
				<center style='margin:20px;margin-top:100px'>
					<img src='img/wait_verybig_mini_red.gif'>
				</center>
			</div>
		</div>
	</td>
	</tr>
	</table>
	</div>
	<script>
	
var xSendProgress$t = function (obj) {
	var res=obj.responseText;
	if (res.length>3){
		document.getElementById('progress-text-$t').innerHTML=res;
	}
	if(!YahooSetupControlOpen()){return;}
	
	if( document.getElementById('done-$t') ){
		document.getElementById('title-$t').innerHTML='$done_title';
		return;
	
	}
	
	setTimeout('SendProgress$t()',1000);
}
	
function SendProgress$t(){
	var XHR = new XHRConnection();
	XHR.appendData('compile-progress',  '$t');
	
	if( document.getElementById('md5-$t') ){
		var md5=document.getElementById('md5-$t').value
		XHR.appendData('md5',  md5);
	}
	XHR.setLockOff();
	XHR.sendAndLoad('$page', 'POST',xSendProgress$t);
		
}

function SquidCompileAmorce$t(){
		SendProgress$t();
}
		
setTimeout('SquidCompileAmorce$t()',1000);
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
	if(!is_file("ressources/logs/web/squid.compile.txt")){return;}
	$md5=$_POST["md5"];
	$t=$_POST["compile-progress"];
	
	
	$md52=md5_file("ressources/logs/web/squid.compile.txt");
	
	if($md5<>null){
		if($md52==$md5){return;}
	}
	
	
	
	echo "<input type='hidden' id='md5-$t' value='$md52'>";
	$f=file("ressources/logs/web/squid.compile.txt");
	
	
	
	
	krsort($f);
	$html="<div style='width:100%;height:550px;overflow:auto'><div><code style='font-size:12px;white-space:normal;background-color:transparent;border:0px'>";
	while (list ($index, $val) = each ($f) ){
		if(preg_match("#:.*?Done.*?Took:#", $val)){
			echo "<input type='hidden' id='done-$t' value='done'>";
		}
		$html=$html."$val<br>";
		
	}
	
	echo $html."</code></div></div>";
	
	
	$z="
	<center style='margin:5px'>".button($title, "YahooSetupControlHide()",16)."</center>
	<script>ExecuteByClassName('SearchFunction');</script>
	
	";
}

