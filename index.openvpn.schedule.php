<?php
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');
include_once('ressources/class.cron.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("alert('no access');");}


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["EnableOpenVPNServerSchedule"])){Save();exit;}
js();


function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_OPENVPN}::{OPENVPN_SCHEDULE_RUN}");
	$page=CurrentPageName();
	$html="YahooWin3('440','$page?popup=yes','$title');";
	echo $html;
}


function popup(){
	
	$sock=new sockets();
	$cron=new cron_macros();
	$hoursT=$cron->cron_hours;
	$MinsT=$cron->cron_mins;
	$page=CurrentPageName();
	$params=unserialize(base64_decode($sock->GET_INFO("EnableOpenVPNServerScheduleDatas")));
	
	$tpl=new templates();
	$html="
	<div id='EnableOpenVPNServerScheduleDiv' style='width:98%' class=form>
	<table style='width:70%'>
	<tr>
<tr>
		<td colspan=3>". Paragraphe_switch_img("{enable_openvpn_schedule}", 
				"{OPENVPN_SCHEDULE_EXPLAIN}","EnableOpenVPNServerSchedule",$sock->GET_INFO("EnableOpenVPNServerSchedule"),null,850,
				"EnableOpenVPNServerScheduleSwitch()")."</td>
</tr>				
	<tr>
		<td class=legend style='font-size:32px' nowrap>{start_time}:</td>
		<td width=1%>". Field_array_Hash($hoursT,"hour_begin",$params["hour_begin"],null,null,0,"font-size:32px;padding:3px")."</td>
		<td width=1% style='font-size:32px'>:</td>
		<td width=1%>". Field_array_Hash($MinsT,"min_begin",$params["min_begin"],null,null,0,"font-size:32px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:32px' nowrap>{end_time}:</td>
		<td width=1%>". Field_array_Hash($hoursT,"hour_end",$params["hour_end"],null,null,0,"font-size:32px;padding:3px")."</td>
		<td width=1% style='font-size:32px'>:</td>
		<td width=1%>". Field_array_Hash($MinsT,"min_end",$params["min_end"],null,null,0,"font-size:32px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=4 align='right'><hr>". button("{apply}","SaveOpenVPNSchedule()",40)."</td>
	</tr>	
	</table>
	</div>
	<script>
var x_SaveOpenVPNSchedule= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>2){alert(tempvalue);}
}				
			
		function SaveOpenVPNSchedule(){
			var XHR = new XHRConnection();
			XHR.appendData('EnableOpenVPNServerSchedule',document.getElementById('EnableOpenVPNServerSchedule').value);
			XHR.appendData('hour_begin',document.getElementById('hour_begin').value);
			XHR.appendData('min_begin',document.getElementById('min_begin').value);
			XHR.appendData('hour_end',document.getElementById('hour_end').value);
			XHR.appendData('min_end',document.getElementById('min_end').value);
			document.getElementById('EnableOpenVPNServerScheduleDiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'POST',x_SaveOpenVPNSchedule);
		
		}
		
		function EnableOpenVPNServerScheduleSwitch(){
		var EnableOpenVPNServerSchedule=document.getElementById('EnableOpenVPNServerSchedule').value;
			document.getElementById('hour_begin').disabled=true;
			document.getElementById('min_begin').disabled=true;
			document.getElementById('hour_end').disabled=true;
			document.getElementById('min_end').disabled=true;
			if(EnableOpenVPNServerSchedule==1){
			document.getElementById('hour_begin').disabled=false;
			document.getElementById('min_begin').disabled=false;
			document.getElementById('hour_end').disabled=false;
			document.getElementById('min_end').disabled=false;			
			}
			
			
		}
	EnableOpenVPNServerScheduleSwitch();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableOpenVPNServerSchedule",$_POST["EnableOpenVPNServerSchedule"]);
	
	$sock->SaveConfigFile(base64_encode(serialize($_POST)),"EnableOpenVPNServerScheduleDatas");
	$sock->getFrameWork("cmd.php?openvpn-server-schedule=yes");
	
}

?>