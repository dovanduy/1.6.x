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
	$html="YahooWin2('890','$page?popup=yes','Syslog');";
	echo $html;	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	$t=time();
	$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
	
	if(trim($array["PERSO_EVENT"])==null){$array["PERSO_EVENT"]="%>eui %>a %[ui %[un %tl %rm %ru HTTP/%rv %>Hs %<st %Ss:%Sh %{User-Agent}>h %{X-Forwarded-For}>h %<A %>A %tr %mt";}
	
	$html="
	<div id='$t' style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{activate} Syslog", "{squid_syslog_text}","ENABLE-$t",$array["ENABLE"],null,700,"SquidSyslogAddCheck()")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{remote_server} (local6):</td>
		<td>". Field_text("SERVER-$t",$array["SERVER"],"font-size:22px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{use_personalized_event}:</td>
		<td>". Field_checkbox_design("ENABLE_PERSO_EVENT-$t", 1,$array["ENABLE_PERSO_EVENT"],"SquidSyslogAddCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{remove_local_access_events}:</td>
		<td>". Field_checkbox_design("SquidNoAccessLogs-$t", 1,$SquidNoAccessLogs)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' colspan=2>{pattern}:</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' colspan=2>
				
		<textarea id='PERSO_EVENT-$t' style='font-size:16px !important;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:180px;overflow:auto'>{$array["PERSO_EVENT"]}</textarea>		
				
		</td>
	</tr>				
				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveSyslogSquid$t()",28)."</td>
	</tr>
	</table>
	
	<script>
		var x_SaveSyslogSquid$t= function (obj) {
			Loadjs('squid.compile.progress.php');
			YahooWin2Hide();
		}
	
	
	function SaveSyslogSquid$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SERVER',document.getElementById('SERVER-$t').value);
		XHR.appendData('ENABLE',document.getElementById('ENABLE-$t').value);
		if(document.getElementById('ENABLE_PERSO_EVENT-$t').checked){XHR.appendData('ENABLE_PERSO_EVENT',1);}else{XHR.appendData('ENABLE_PERSO_EVENT',0); }
		if(document.getElementById('SquidNoAccessLogs-$t').checked){XHR.appendData('SquidNoAccessLogs',1);}else{XHR.appendData('SquidNoAccessLogs',0); }
		XHR.appendData('PERSO_EVENT',encodeURIComponent(document.getElementById('PERSO_EVENT-$t').value));
		XHR.sendAndLoad('$page', 'POST',x_SaveSyslogSquid$t);
	}
	
	function SquidSyslogAddCheck(){
		document.getElementById('SERVER-$t').disabled=true;
		document.getElementById('PERSO_EVENT-$t').disabled=true;
		document.getElementById('SquidNoAccessLogs-$t').disabled=true;
		
		
		
		if(document.getElementById('ENABLE-$t').value==1){
			document.getElementById('SERVER-$t').disabled=false;
			document.getElementById('SquidNoAccessLogs-$t').disabled=false;

			if(document.getElementById('ENABLE_PERSO_EVENT-$t').checked){
				document.getElementById('PERSO_EVENT-$t').disabled=false;
				
			}
			
		}else{
			document.getElementById('SquidNoAccessLogs-$t').checked=false;
		
		}
		
		CheckBoxDesignRebuild();
	}
	
	SquidSyslogAddCheck();

</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function save(){
	$squid=new squidbee();
	$sock=new sockets();
	$sock->SET_INFO("SquidNoAccessLogs", $_POST["SquidNoAccessLogs"]);
	$_POST["PERSO_EVENT"]=url_decode_special_tool($_POST["PERSO_EVENT"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidSyslogAdd");
	
}

