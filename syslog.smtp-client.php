<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}


	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["ActAsASyslogSMTPClient"])){ActAsASyslogSMTPClientSave();exit;}
	if(isset($_GET["syslog-servers-list"])){SyslogServerList();exit;}
	if(isset($_GET["syslog-host"])){SyslogServerListAdd();exit;}
	if(isset($_GET["syslog-host-delete"])){SyslogServerListDel();exit;}
js();

function js(){
		$t=time();
		$jsstart="syslog{$t}ConfigLoadPopup()";
		if(isset($_GET["windows"])){$jsstart="syslog{$t}ConfigLoadPopup()";}
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{RemoteSMTPSyslog}");
		$html="
		
		
		function syslog{$t}ConfigLoad(){
			$('#BodyContent').load('$page?popup=yes');
			}
			
		function syslog{$t}ConfigLoadPopup(){
			YahooWin4(550,'$page?popup=yes','$title');
			}			
			
		$jsstart;
		";
		echo $html;
		
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	$enable=Paragraphe_switch_img("{enable_syslog_client}","{enable_syslog_client_text}",
	"ActAsASyslogSMTPClient","$ActAsASyslogClient",null,520);
	$t=time();
	$html="
	<div id='ActAsASyslogClientDiv'>
	$enable
	<div style='text-align:right'><hr>". button("{apply}","ActAsASyslogSMTPClientSave()")."</div>
	</div>
	<p>&nbsp;</p>
	
	<table style='width:98%' class=form>
		<tr>
			<td class=legend>{address}:</td>
			<td>". Field_text("syslog-host",null,"font-size:14px;font-weight:bold;width:210px")."</td>
			<td class=legend>{port}:</td>
			<td>". Field_text("syslog-port",514,"font-size:14px;font-weight:bold;width:60px")."</td>	
			<td width=1%>". button("{add}","AddServer{$t}SyslogHost()")."</td>
		</tr>
	</table>
	
	<div id='syslog-{$t}-list' style='width:100%;height:255px;overflow:auto'></div>
	
	<script>
		var x_ActAsASyslogClientSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			
		}	

		var x_AddServer{$t}SyslogHost= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			SyslogServer{$t}ListRefresh();
		}			
		
	function ActAsASyslogSMTPClientSave(){
		var XHR = new XHRConnection();
		XHR.appendData('ActAsASyslogSMTPClient',document.getElementById('ActAsASyslogSMTPClient').value);
		XHR.sendAndLoad('$page', 'GET',x_ActAsASyslogClientSave);		
		}
		
	function AddServer{$t}SyslogHost(){
		var XHR = new XHRConnection();
		XHR.appendData('syslog-host',document.getElementById('syslog-host').value);
		XHR.appendData('syslog-port',document.getElementById('syslog-port').value);
		AnimateDiv('syslog-{$t}servers-list');
		XHR.sendAndLoad('$page', 'GET',x_AddServer{$t}SyslogHost);		
	}
		
	function SyslogServer{$t}ListRefresh(){
		LoadAjax('syslog-{$t}-list','$page?syslog-servers-list=yes&t=$t');
	
	}
	
	SyslogServer{$t}ListRefresh();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ActAsASyslogSMTPClientSave(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogSMTPClient", $_GET["ActAsASyslogSMTPClient"]);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");	
	$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
}
function SyslogServerList(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	if(count($serversList)==0){return;}
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{server}</th>
		<th>{status}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		

	$icon="dns-cp-22.png";
if(is_array($serversList)){	
	while (list ($num, $server) = each ($serversList) ){
		if($server==null){continue;}
		$color="black";
		$udp="warning-panneau-24.png";
		if($ActAsASyslogClient==1){
		if(preg_match("#(.+?):([0-9]+)#",$server,$re)){
			$udp=$sock->getFrameWork("cmd.php?IsUDPport=yes&host={$re[1]}&port={$re[2]}");}
		}
		if($udp=="UNKNOWN"){$udp_img="warning24.png";}
		if($udp=="OK"){$udp_img="ok24.png";}
		if($udp=="FAILED"){$udp_img="danger24.png";}
		
		
		
		if($ActAsASyslogClient<>1){$color="#CCCCCC";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-32.png","{delete}","SyslogServer{$_GET["t"]}Delete('$server')");
		$html=$html . "
		<tr  class=$classtr>
			<td width=1%><img src='img/$icon'></td>
			<td width=99%><strong style='font-size:14px'><code style='color:$color'>$server</code></td>
			<td width=1% align='center'><img src='img/$udp_img'></td>
			<td width=1%>$delete</td>
		</td>
		</tr>";
		
	}
}
	
	$html=$html."</tbody></table>
	<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","SyslogServer{$_GET["t"]}ListRefresh();")."</div>
	<script>

	var x_SyslogServerDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		SyslogServer{$_GET["t"]}ListRefresh();
	}		
	
	function SyslogServer{$_GET["t"]}Delete(key){
		var XHR = new XHRConnection();
		XHR.appendData('syslog-host-delete',key);	
		AnimateDiv('syslog-{$_GET["t"]}-list');
		XHR.sendAndLoad('$page', 'GET',x_SyslogServerDelete);
		}	

	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function SyslogServerListAdd(){
	$sock=new sockets();
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	$serversList["{$_GET["syslog-host"]}:{$_GET["syslog-port"]}"]="{$_GET["syslog-host"]}:{$_GET["syslog-port"]}";
	$sock->SaveConfigFile(base64_encode(serialize($serversList)),"ActAsASyslogClientSMTPList");
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");
	$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
	
	
	
}
function SyslogServerListDel(){
	$sock=new sockets();
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	unset($serversList[$_GET["syslog-host-delete"]]);
	$sock->SaveConfigFile(base64_encode(serialize($serversList)),"ActAsASyslogClientSMTPList");
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");	
	$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
}