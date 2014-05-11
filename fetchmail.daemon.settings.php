<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.fetchmail.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["FetchmailPoolingTime"])){section_fetchmail_daemon_save();exit;}
if(isset($_GET["ajax"])){popup();exit;}
if(isset($_POST["EnableFetchmail"])){EnableFetchmail();exit;}
if(isset($_POST["watchdog"])){watchdog_save();exit;}
section_Fetchmail_Daemon();



function popup(){
	$t=time();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$MONIT_INSTALLED=1;
	$ZARAFA_INSTALLED=0;
	if(!$users->MONIT_INSTALLED){$MONIT_INSTALLED=0;}
	if($users->ZARAFA_INSTALLED){$ZARAFA_INSTALLED=1;}
	$FetchMailGLobalDropDelivered=$sock->GET_INFO("FetchMailGLobalDropDelivered");
	$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");
	$FetchMailToZarafa=$sock->GET_INFO("FetchMailToZarafa");
	if(!is_numeric($FetchMailToZarafa)){$FetchMailToZarafa=1;}
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("FetchMailMonitConfig")));
	
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=0;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	
	if(!is_numeric($FetchMailGLobalDropDelivered)){$FetchMailGLobalDropDelivered=0;}
	if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
	$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
	$EnableFetchmail_popp=Paragraphe_switch_img('{enable_fetchmail}',
			'{enable_fetchmail_text}',"enable_fetchmail$t",$EnableFetchmail,null,650);
	$yum=new usersMenus();
	for($i=1;$i<60;$i++){
		$hash[$i*60]=$i;
		
		
	}
	$fetch=new fetchmail();
	$list=Field_array_Hash($hash,'FetchmailPoolingTime',$fetch->FetchmailPoolingTime,null,null,null,'font-size:18px');
	
$fetchmail_daemon="
					<div id='fetchdaemondiv'>
					<center>
					<div class=form style='width:95%'>
					<table style='width:95%;margin-bottom:15px'>
					<tr>
							<td valign='top' colspan=3>
							<div style='width:95%;margin:20px'>
								$EnableFetchmail_popp
								
							</div>
							
						</td>
					</tr>
						<tr>
						<td colspan=3 align='right'><hr>". button("{apply}", "SaveFetchEnable$t()",24)."</td>
						</tr>	
					<tr>
						<td colspan=3 align='right'><p>&nbsp;</p></td>
					</tr>												
										
						<tr>
							<td align='right' nowrap class=legend nowrap><span style='font-size:18px' >{use_schedule}: </strong></td>
							<td>". Field_checkbox("EnableFetchmailScheduler", 1,$EnableFetchmailScheduler,"FetchmailCheck()")."</td>
							<td width=1%>". help_icon("{EnableFetchmailScheduler_explain}")."</td>
						</tr>					
						<tr>
							<td align='right' nowrap class=legend nowrap><span style='font-size:18px' >{daemon_interval}: </strong></td>
							<td align='left' style='font-size:14px'>$list&nbsp;(minutes)</td>
							<td width=1%>&nbsp;</td>
						</tr>
						<tr>
							<td align='right' class=legend><span style='font-size:18px' nowrap>{postmaster}</strong></td>
							<td align='left'>" . Field_text('FetchmailDaemonPostmaster',$fetch->FetchmailDaemonPostmaster,"font-size:18px;padding:3px;width:220px") . "</td>
							<td width=1%>&nbsp;</td>
						</tr>	
						<tr>
							<td class=legend><span style='font-size:18px' >{dropdelivered}:</strong></td>
							<td>". Field_checkbox("FetchMailGLobalDropDelivered", 1,$FetchMailGLobalDropDelivered)."</td>
							<td width=1%>". help_icon("{dropdelivered_explain}")."</td>
						</tr>
						<tr>
							<td class=legend><span style='font-size:18px' >{FetchMailToZarafa}:</strong></td>
							<td>". Field_checkbox("FetchMailToZarafa", 1,$FetchMailToZarafa)."</td>
							<td width=1%>". help_icon("{FetchMailToZarafa_explain}")."</td>
						</tr>									
							<tr>
								<td colspan=3 align='right'><hr>". button("{apply}","SaveFetchMailDaemon()",24)."
							</tr>
						</tr>
							<tr>
								<td colspan=3 align='right'><p>&nbsp;</p></td>
							</tr>										
						<tr>
							<td class=legend style='font-size:18px'>{enable_watchdog}:</td>
							<td>". Field_checkbox("$t-watchdog", 1,$MonitConfig["watchdog"],"FetchMailCheckWatchdog{$t}()")."</td>
							<td>&nbsp;</td>
						</tr>		
						<tr>
							<td class=legend style='font-size:18px'>{notify_when_cpu_exceed}:</td>
							<td style='font-size:14px'>". Field_text("$t-watchdogCPU", $MonitConfig["watchdogCPU"],"font-size:18px;width:110px")."&nbsp;%</td>
							<td>&nbsp;</td>
						</tr>	
						<tr>
							<td class=legend style='font-size:18px'>{notify_when_memory_exceed}:</td>
							<td style='font-size:14px'>". Field_text("$t-watchdogMEM", $MonitConfig["watchdogMEM"],"font-size:18px;width:110px")."&nbsp;MB</td>
							<td>&nbsp;</td>
						</tr>	
						<tr>
							<td colspan=3 align='right'><hr>". button("{apply}", "SaveWatchdog{$t}()",24)."</td>
						</tr>	
					</tbody>
					</table>					

				</div>";
		

		$title="{fetchmail}";
		
		
		
		$html="
						$fetchmail_daemon
						
						
						
						
						
						
		<center><img src='img/bg_fetchmail.png'></center>
		<script>
		var x_SaveFetchMailDaemon= function (obj) {
				var results=obj.responseText;
				if(results.length>0){alert(results);}
			 	RefreshTab('main_config_fetchmail');
				}	
		
				
		function SaveFetchEnable$t(){
			var XHR = new XHRConnection();		
			XHR.appendData('EnableFetchmail',document.getElementById('enable_fetchmail$t').value);
			AnimateDiv('fetchdaemondiv');
			XHR.sendAndLoad('$page', 'POST',x_SaveFetchMailDaemon);	
		}
		
		function SaveFetchMailDaemon(){
				var XHR = new XHRConnection();		
				XHR.appendData('FetchmailDaemonPostmaster',document.getElementById('FetchmailDaemonPostmaster').value);
				XHR.appendData('FetchmailPoolingTime',document.getElementById('FetchmailPoolingTime').value);
				if(document.getElementById('FetchMailGLobalDropDelivered').checked){XHR.appendData('FetchMailGLobalDropDelivered',1);}else{XHR.appendData('FetchMailGLobalDropDelivered',0);}
				if(document.getElementById('EnableFetchmailScheduler').checked){XHR.appendData('EnableFetchmailScheduler',1);}else{XHR.appendData('EnableFetchmailScheduler',0);}
				if(document.getElementById('FetchMailToZarafa').checked){XHR.appendData('FetchMailToZarafa',1);}else{XHR.appendData('FetchMailToZarafa',0);}
				AnimateDiv('fetchdaemondiv');
				XHR.sendAndLoad('$page', 'GET',x_SaveFetchMailDaemon);			
			
			}
			
		function FetchmailCheck(){
			var MONIT_INSTALLED=$MONIT_INSTALLED;
			var ZARAFA_INSTALLED=$ZARAFA_INSTALLED;
			document.getElementById('FetchmailPoolingTime').disabled=true;
			
			if(ZARAFA_INSTALLED==0){
				document.getElementById('FetchMailToZarafa').disabled=true;
			}
			
			if(!document.getElementById('EnableFetchmailScheduler').checked){
				document.getElementById('FetchmailPoolingTime').disabled=false;
				EnableFetchMonit();
			}else{
				DisableFetchMonit();
				return;
			}
			if(MONIT_INSTALLED==0){DisableFetchMonit();return;}
			FetchMailCheckWatchdog{$t}()
		}
		
		function DisableFetchMonit(chck){
			if(!chck){document.getElementById('$t-watchdog').disabled=true;}
			document.getElementById('$t-watchdogMEM').disabled=true;
			document.getElementById('$t-watchdogCPU').disabled=true;
		
		}
		
		function EnableFetchMonit(chck){
			var MONIT_INSTALLED=$MONIT_INSTALLED;
			if(MONIT_INSTALLED==0){DisableFetchMonit();return;}
			if(!chck){document.getElementById('$t-watchdog').disabled=false;}
			document.getElementById('$t-watchdogMEM').disabled=false;
			document.getElementById('$t-watchdogCPU').disabled=false;		
		
		}
		
		function FetchMailCheckWatchdog{$t}(){
			DisableFetchMonit(1);
			if(document.getElementById('$t-watchdog').checked){
				EnableFetchMonit(1);
			}
		
		}
		
	
	function SaveWatchdog{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-watchdog').checked){XHR.appendData('watchdog',1);}else{XHR.appendData('watchdog',0);}
		XHR.appendData('watchdogMEM',document.getElementById('$t-watchdogMEM').value);
		XHR.appendData('watchdogCPU',document.getElementById('$t-watchdogCPU').value);
		AnimateDiv('fetchdaemondiv');
		XHR.sendAndLoad('$page', 'POST',x_SaveFetchMailDaemon);
	}		
		
		
		FetchmailCheck();
		</script>
				";
		
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
	
}


function section_Fetchmail_Daemon(){
	$page=CurrentPageName();
	$yum=new usersMenus();
	for($i=1;$i<60;$i++){
		$hash[$i*60]=$i;
		
		
	}
	$fetch=new fetchmail();
	$list=Field_array_Hash($hash,'FetchmailPoolingTime',$fetch->FetchmailPoolingTime,null,null,null,'width:90px');
	
$fetchmail_daemon=RoundedLightGrey("
		<form name=ffmFetch>
					<center>
					
					<table>
					<tr>
						<td align='right' nowrap><strong>{fetch_messages_every} </strong></td>
						<td align='left'>$list  (minutes)</td>
					</tr>
					<tr>
						<td align='right'><strong>{postmaster}</strong></td>
						<td align='left'>" . Field_text('FetchmailDaemonPostmaster',$fetch->FetchmailDaemonPostmaster) . "</td>
					</tr>	
					<tr>
					<td colspan=2 align='right'><input type='button' value='{submit}&nbsp;&raquo;' OnClick=\"javascript:ParseForm('ffmFetch','$page',true);\"></td>
					</tr>	
				</table>
				</form>
			</center>");
		

		$title="{fetchmail}";
		
		$status=fetchmail_status();
		
		$html="<table style='width:600px'>
		<tr>
		<td valign='top'><img src='img/bg_fetchmail.jpg'>
		<td valign='top'>$status</td>
		</tr>
		<td colspan=2>
				<table style='width:100%'>
				<tr>
				<td valign='top' width=60%>
					<H5>{fetchmail_daemon_settings}</H5>
						$fetchmail_daemon
				</td>
				<td valing='top'>" . applysettings("fetch") . "
				
				</td>
				</tr>
				</table>
			</td>
			</tr>			
					</table>";
				
				
$tpl=new template_users($title,$html,0,0,0,0,$cfg);
echo $tpl->web_page;		
	
	}
	
function fetchmail_status(){
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString($sock->getfile('fetchmailstatus'));
	$status=DAEMON_STATUS_ROUND("FETCHMAIL",$ini,null);
	return  $tpl->_ENGINE_parse_body($status);	
}
	
function section_fetchmail_daemon_save(){
	$sock=new sockets();
	$fetch=new fetchmail();
	$fetch->FetchmailDaemonPostmaster=$_GET["FetchmailDaemonPostmaster"];
	$fetch->FetchmailPoolingTime=$_GET["FetchmailPoolingTime"];
	$sock->SET_INFO("FetchMailGLobalDropDelivered", $_GET["FetchMailGLobalDropDelivered"]);
	$sock->SET_INFO("EnableFetchmailScheduler", $_GET["EnableFetchmailScheduler"]);
	$sock->SET_INFO("FetchMailToZarafa", $_GET["FetchMailToZarafa"]);
	echo $fetch->Save();
	
}
function EnableFetchmail(){
	$sock=new sockets();
	$sock->SET_INFO('EnableFetchmail',$_POST["EnableFetchmail"]);
	$fetch=new fetchmail();
	$fetch->Save();
	}
function watchdog_save(){
	$sock=new sockets();
	$final=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($final, "FetchMailMonitConfig");
	$sock->getFrameWork("services.php?fetchmail-monit=yes");
}