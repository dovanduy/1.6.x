<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["MailArchiverEnabled"])){MailArchiverEnabled();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["status2"])){status2();exit;}

popup();


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	
	$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
	$MailArchiverToMySQL=$sock->GET_INFO("MailArchiverToMySQL");
	$MailArchiverToMailBox=$sock->GET_INFO("MailArchiverToMailBox");
	$MailArchiverMailBox=$sock->GET_INFO("MailArchiverMailBox");
	$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
	$MailArchiverToSMTP=$sock->GET_INFO("MailArchiverToSMTP");
	$MailArchiverSMTP=$sock->GET_INFO("MailArchiverSMTP");
	$MailArchiverSMTPINcoming=$sock->GET_INFO("MailArchiverSMTPINcoming");
	
	
	
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
	if(!is_numeric($MailArchiverToMySQL)){$MailArchiverToMySQL=1;}
	if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=0;}
	if(!is_numeric($MailArchiverToSMTP)){$MailArchiverToSMTP=0;}
	if(!is_numeric($MailArchiverSMTPINcoming)){$MailArchiverSMTPINcoming=1;}	
	
	
	$milter=Paragraphe_switch_img('{enable_APP_MAILARCHIVER}',
	'{enable_APP_MAILARCHIVER_text}','enable_archiver',$MailArchiverEnabled,'{enable_disable}',800);
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:99%' >
	<tr>
	<td>
		<div style='font-size:26px'>{backupemail_behavior}<hr></div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=353','1024','900');\"
		style='font-size:14px;text-decoration:underline'>{online_help}</a></div>
		$milter
		</td>
	</tr>
	</table>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>{us_v2}:</td>
			<td>". Field_checkbox("MailArchiverUsePerl", 1,$MailArchiverUsePerl)."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:16px'>{save_to_mysqldb}:</td>
			<td>". Field_checkbox("MailArchiverToMySQL", 1,$MailArchiverToMySQL)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{send_to_mailbox}:</td>
			<td>". Field_checkbox("MailArchiverToMailBox", 1,$MailArchiverToMailBox,"MailArchiverToMailBoxCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{mailbox}:</td>
			<td>". Field_text("MailArchiverMailBox",$MailArchiverMailBox,"font-size:14px;width:220px")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:16px'>{send_to_smtp_server}:</td>
			<td>". Field_checkbox("MailArchiverToSMTP", 1,$MailArchiverToSMTP,"MailArchiverToSMTPCheck()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{only_incoming_mails}:</td>
			<td>". Field_checkbox("MailArchiverSMTPINcoming", 1,$MailArchiverSMTPINcoming,"")."</td>
		</tr>
					
		<tr>
			<td class=legend style='font-size:16px'>{smtp_server}:</td>
			<td>". Field_text("MailArchiverSMTP",$MailArchiverSMTP,"font-size:14px;width:220px")."</td>
		</tr>	
		</table>		
	<div style='text-align:right;width:100%'><hr>". button("{apply}","ApplyBackupBehavior$t()","18px")."</div>
	</td>
	</tr>
	</table>
	</div>
	<script>

	
	var XwwApplyBackupBehavior$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		if(document.getElementById('main_config_archiver')){RefreshTab('main_config_archiver');}
		//if(document.getElementById('main_config_archiver')){RefreshTab('main_config_archiver');}
		
		
		}
		
	function ApplyBackupBehavior$t(){
		var XHR = new XHRConnection();
		MailArchiverToMailBox=0;
		MailArchiverToMySQL=0;
		MailArchiverUsePerl=0;
		MailArchiverToSMTP=0;
		MailArchiverSMTPINcoming=0;
		MailArchiverEnabled=0;
		MailArchiverEnabled=document.getElementById('enable_archiver').value;
		XHR.appendData('MailArchiverEnabled',document.getElementById('enable_archiver').value);
		XHR.appendData('MailArchiverMailBox',document.getElementById('MailArchiverMailBox').value);
		XHR.appendData('MailArchiverSMTP',document.getElementById('MailArchiverSMTP').value);
		
		
		
		if(document.getElementById('MailArchiverToMailBox').checked){MailArchiverToMailBox=1;}
		if(document.getElementById('MailArchiverToMySQL').checked){MailArchiverToMySQL=1;}
		if(document.getElementById('MailArchiverUsePerl').checked){MailArchiverUsePerl=1;}
		if(document.getElementById('MailArchiverToSMTP').checked){MailArchiverToSMTP=1;}
		if(document.getElementById('MailArchiverSMTPINcoming').checked){MailArchiverSMTPINcoming=1;}
		
		
		if(MailArchiverEnabled==1){
			if(MailArchiverToMailBox==0){
				if(MailArchiverToMySQL==0){
					if(MailArchiverToSMTP==0){
						alert('There no sense to store messages in nothing !!! Please select MySQL or MailBox');
					}
				}
			}
		}
		XHR.appendData('MailArchiverToMailBox',MailArchiverToMailBox);
		XHR.appendData('MailArchiverToMySQL',MailArchiverToMySQL);
		XHR.appendData('MailArchiverUsePerl',MailArchiverUsePerl);
		XHR.appendData('MailArchiverToSMTP',MailArchiverToSMTP);
		XHR.appendData('MailArchiverSMTPINcoming',MailArchiverSMTPINcoming);
		XHR.sendAndLoad('$page', 'POST',XwwApplyBackupBehavior$t);				
	}
	
	function MailArchiverToMailBoxCheck(){
		document.getElementById('MailArchiverMailBox').disabled=true;
		var MailArchiverToMailBox=0;
		if(document.getElementById('MailArchiverToMailBox').checked){
			MailArchiverToMailBox=1;
		}
		
		if(MailArchiverToMailBox==1){
			document.getElementById('MailArchiverMailBox').disabled=false;
		}
		
	}
	
	function MailArchiverToSMTPCheck(){
		document.getElementById('MailArchiverSMTP').disabled=true;
		document.getElementById('MailArchiverSMTPINcoming').disabled=true;
		
		var MailArchiverToMailBox=0;
		if(document.getElementById('MailArchiverToSMTP').checked){
			MailArchiverToMailBox=1;
		}
		
		if(MailArchiverToMailBox==1){
			document.getElementById('MailArchiverSMTP').disabled=false;
			document.getElementById('MailArchiverSMTPINcoming').disabled=false;
		}
		
	}	
	
	
MailArchiverToMailBoxCheck();
MailArchiverToSMTPCheck();
</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'postfix.plugins.php');		
	
}
function MailArchiverEnabled(){
	$MailArchiverEnabled=$_POST["MailArchiverEnabled"];
	writelogs("MailArchiverEnabled=$MailArchiverEnabled",__FUNCTION__,__FILE__);
	$sock=new sockets();
	$sock->SET_INFO('MailArchiverEnabled',$MailArchiverEnabled);
	$sock->SET_INFO('MailArchiverMailBox',$_POST["MailArchiverMailBox"]);
	$sock->SET_INFO('MailArchiverToMailBox',$_POST["MailArchiverToMailBox"]);
	$sock->SET_INFO('MailArchiverToMySQL',$_POST["MailArchiverToMySQL"]);
	$sock->SET_INFO('MailArchiverUsePerl',$_POST["MailArchiverUsePerl"]);
	
	$sock->SET_INFO('MailArchiverToSMTP',$_POST["MailArchiverToSMTP"]);
	$sock->SET_INFO('MailArchiverSMTP',$_POST["MailArchiverSMTP"]);
	$sock->SET_INFO('MailArchiverSMTPINcoming',$_POST["MailArchiverSMTPINcoming"]);
	
	
	$sock->getFrameWork("postfix.php?milters=yes");
	$sock->getFrameWork("postfix.php?restart-mailarchiver=yes");	
}

function status(){
	$page=CurrentPageName();
	$html="<div id='mailarchiver-status'></div>
	<script>LoadAjax('mailarchiver-status','$page?status2=yes');</script>";	
	echo $html;
}

function status2(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();	
	include_once(dirname(__FILE__).'/ressources/class.mysql.archive.builder.inc');	
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("postfix.php?mailarchiver-status=yes")));
	$status=DAEMON_STATUS_ROUND("APP_MAILARCHIVER",$ini,null);
	$qArch=new mysql_mailarchive_builder();
	$qArchLigne=mysql_fetch_array($qArch->QUERY_SQL("SELECT SUM(rowsnum) as trows,SUM(size) as tsize FROM indextables"));
	$emailsNumber=numberFormat($qArchLigne["trows"],0,'.',' ');
	$emailsSize=FormatBytes($qArchLigne["tsize"]/1024);
	
	$html="
			
	<div style='font-size:16px' class=text-info>{backupemail_behavior_text}</div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td valign='top' width=50% valign='top'>$status</td>
		<td width=50% valign='middle' align='center'>". imgtootltip("64-refresh.png","{refresh}","LoadAjax('mailarchiver-status','$page?status2=yes')")."</td>
	</tr>
	<td valign='top'>
		<center>
		<table style='width:10%'>
		<tr>
			<td class=legend style='font-size:16px' nowrap>{backup}:</td>
			<td style='font-size:16px;font-weight:bold' nowrap>$emailsNumber&nbsp;emails&nbsp;($emailsSize)</td>
		</tr>
		</table>
		</center>
	</td>
	</tr>		
	</table>
	</div>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

	
}

