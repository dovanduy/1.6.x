<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ocs.inc');
	
	

	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["OcsServerDest"])){save();exit;}
js();	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_OCSI_LINUX_CLIENT}');
	$time=time();
	$startJS="OCSAgentLNXLoadMain()";
	if(isset($_GET["inline"])){
		$prefix="<div id='$time'></div><script>";
		$suffix="</script>";
		$startJS="OCSAgentLNXInLine()";
	}
	
	$html="
	
	
	$prefix
	function OCSAgentLNXLoadMain(){
		YahooWin2('700','$page?popup=yes','$title');
		}	
		
	function OCSAgentLNXInLine(){
		LoadAjax('$time','$page?popup=yes');
	}
		
	var x_SaveAglnxOCSInfos=function (obj) {
		var results=obj.responseText;
		if (results.length>0){alert(results);}
	 	$startJS;
	
	}		
		
	function SaveAglnxOCSInfos(){
		var XHR = new XHRConnection();
		XHR.appendData('OcsServerDest',document.getElementById('OcsServerDest').value);		
		if(document.getElementById('EnableOCSAgent').checked){XHR.appendData('EnableOCSAgent',1);}else{XHR.appendData('EnableOCSAgent',0);}
		if(document.getElementById('OcsServerUseSSL').checked){XHR.appendData('OcsServerUseSSL',1);}else{XHR.appendData('OcsServerUseSSL',0);}
		XHR.appendData('OcsServerDestPort',document.getElementById('OcsServerDestPort').value);
		AnimateDiv('oscaglnx-form');
		XHR.sendAndLoad('$page', 'GET',x_SaveAglnxOCSInfos);	
	}
	
	function RefreshAgentlnxStatus(){
		LoadAjax('oscaglnx-status','$page?status=yes');
	}

	$startJS;
	
	$suffix


	
";

echo $html;
}	

function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->OCS_LNX_AGENT_INSTALLED){
		$html="
		<center>
		<table style='width:80%' class=form>
		<tr>
			<td width=1%><img src='img/error-128.png'></td>
			<td style='font-size:16px'>{OCS_LNX_AGENT_NOTINSTALLED}</td>
		</tr>
		</table>
		
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	$sock=new sockets();
	$OcsServerDest=$sock->GET_INFO("OcsServerDest");
	$OcsServerDestPort=$sock->GET_INFO("OcsServerDestPort");
	$EnableOCSAgent=$sock->GET_INFO("EnableOCSAgent");
	$OcsServerUseSSL=$sock->GET_INFO("OcsServerUseSSL");
	if($EnableOCSAgent==null){$EnableOCSAgent=1;}
	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top'>
		<div id='oscaglnx-form'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td valign='top' class=legend style='font-size:14px'>{ACTIVATE_OCS_AGENT_SERVICE}:</td>
						<td valing='top'>". Field_checkbox("EnableOCSAgent",1,$EnableOCSAgent)."</td>
					</tr>			
					<tr>
						<td valign='top' class=legend style='font-size:14px'>{OCS_SERVER_ADDRESS}:</td>
						<td valing='top'>". Field_text("OcsServerDest",$OcsServerDest,"font-size:14px;padding:3px")."</td>
					</tr>
					<tr>
						<td valign='top' class=legend style='font-size:14px'>{listen_http_port}:</td>
						<td valign='top'>". Field_text("OcsServerDestPort",$OcsServerDestPort,"font-size:14px;padding:3px;width:60px")."</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{UseSSL}:</td>
						<td>". Field_checkbox("OcsServerUseSSL",1,$OcsServerUseSSL)."</td>
					</tr>						
					<tr><td colspan=2 align='right'><hr>". button("{apply}","SaveAglnxOCSInfos()")."</td></tr>	
				</tbody>
			</table>		
			</div>
		</td>
		<td valing='top'>
			<div id='oscaglnx-status'></div>
			<div style='width:100%;text-align:right;margin-top:10px'>". imgtootltip("refresh-24.png","{refresh}","RefreshAgentlnxStatus()")."</div>	
		</td>
		
	</tr>
	</tbody>
	</table>
	<script>
		RefreshAgentlnxStatus();
	</script>
		
	
	";
	
		
		echo $tpl->_ENGINE_parse_body($html);	
	
}

function status(){
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?ocsagntlnx-status=yes')));
	$status=DAEMON_STATUS_ROUND("APP_OCSI_LINUX_CLIENT",$ini);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($status);
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("OcsServerDest",$_GET["OcsServerDest"]);
	$sock->SET_INFO("OcsServerDestPort",$_GET["OcsServerDestPort"]);
	$sock->SET_INFO("EnableOCSAgent",$_GET["EnableOCSAgent"]);
	$sock->SET_INFO("OcsServerUseSSL",$_GET["OcsServerUseSSL"]);
	$sock->getFrameWork("cmd.php?ocsagntlnx-restart=yes");
	
}

	

?>