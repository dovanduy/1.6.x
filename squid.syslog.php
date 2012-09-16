<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ENABLE"])){save();exit;}
	js();
	
	
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="YahooWin2('450','$page?popup=yes','Syslog');";
	echo $html;	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{activate}:</td>
		<td>". Field_checkbox("ENABLE-$t", 1,$array["ENABLE"],"SquidSyslogAddCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{remote_server}: (local6)</td>
		<td>". Field_text("SERVER-$t",$array["SERVER"],"font-size:14px;font-size:14px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSyslogSquid$t()",16)."</td>
	</tr>
	</table>
	
	<script>
		var x_SaveSyslogSquid$t= function (obj) {
			document.getElementById('$t').innerHTML='';
			YahooWin2Hide();
		}
	
	
	function SaveSyslogSquid$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('ENABLE-$t').checked){XHR.appendData('ENABLE',1);}else{XHR.appendData('ENABLE',0);}
		XHR.appendData('SERVER',document.getElementById('SERVER-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveSyslogSquid$t);
	}
	
	function SquidSyslogAddCheck(){
		document.getElementById('SERVER-$t').disabled=true;
		if(document.getElementById('ENABLE-$t').checked){
			document.getElementById('SERVER-$t').disabled=false;
		}
	}
	
	SquidSyslogAddCheck();

</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function save(){
	$squid=new squidbee();
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidSyslogAdd");
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}

