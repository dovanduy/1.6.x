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

popup();


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$milter=Paragraphe_switch_img('{enable_APP_MAILARCHIVER}',
	'{enable_APP_MAILARCHIVER_text}','enable_archiver',$sock->GET_INFO("MailArchiverEnabled"),'{enable_disable}',450);
	
	$html="
	<table style='width:99%' class=form>
	<tr>
	<td>
		<div style='font-size:22px'>{backupemail_behavior}<hr></div>
		<div style='text-align:right'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=353','1024','900');\"
		style='font-size:14px;text-decoration:underline'>{online_help}</a></div>
	$milter
	<div style='text-align:right;width:100%'><hr>". button("{apply}","ApplyBackupBehavior$t()","16px")."</div>
	</td>
	</tr>
	</table>
	<script>

	
	var X_ApplyBackupBehavior$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		if(document.getElementById('main_config_archiver')){RefreshTab('main_config_archiver');}
		}
		
	function ApplyBackupBehavior$t(){
		var XHR = new XHRConnection();
		XHR.appendData('MailArchiverEnabled',document.getElementById('enable_archiver').value);
		document.getElementById('img_enable_archiver').src='img/wait_verybig.gif';
		XHR.sendAndLoad('$page', 'POST',X_ApplyBackupBehavior$t);				
	}

</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'postfix.plugins.php');		
	
}
function MailArchiverEnabled(){
	$MailArchiverEnabled=$_POST["MailArchiverEnabled"];
	writelogs("MailArchiverEnabled=$MailArchiverEnabled",__FUNCTION__,__FILE__);
	$sock=new sockets();
	$sock->SET_INFO('MailArchiverEnabled',$MailArchiverEnabled);
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?milters=yes");
	$sock->getFrameWork("postfix.php?restart-mailarchiver=yes");	
}

function status(){
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
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td valign='top'>$status</td>
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
	";
	
	echo $tpl->_ENGINE_parse_body($html);

	
}

