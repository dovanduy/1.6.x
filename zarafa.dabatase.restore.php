<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["restore-path"])){restorefrom();exit;}
	if(isset($_POST["restore-logs"])){restorelogs();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{restore_from_backup}");
	$html="YahooWin3('650','$page?popup=yes','$title')";
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$warn_restore_articadb=$tpl->javascript_parse_text("{warn_restore_articadb}");
	
	$html="
	<div style='font-size:14px' class=text-info>{zarafadb_restore_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{backup_file}:</td>
		<td>". field_text("backup$t",null,"font-size:16px;width:210px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?target-form=backup$t')",13)."</td>
	</tr>
	<tr>
	<td colspan=3 align='right'><hr>". button("{restore}","Restore$t()",18)."</td>
	</tr>
	</table>		
	<div id='start-$t'></div>		
	<script>
		var x_Restore$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('start-$t').innerHTML='';
	      LoadAjax('start-$t','$page?logs-starter=yes&t=$t');
	      }		
		
		function Restore$t(){
			var path=document.getElementById('backup$t').value;
			if(!confirm('$warn_restore_articadb')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('restore-path',path);
			AnimateDiv('start-$t');
			XHR.sendAndLoad('$page', 'POST',x_Restore$t);	
		}
		
		
		
		
		var x_GetLogs$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){
	      	document.getElementById('textToParseCats-$t').innerHTML=tempvalue;
	       }
	      if(!YahooWin3Open()){return;}
	      setTimeout(\"GetLogs$t()\",1000);
	      }	

	      
		function GetLogs$t(){
			if(!YahooWin3Open()){return;}
			var XHR = new XHRConnection();
			XHR.appendData('restore-logs','yes');
			XHR.appendData('t','$t');
			XHR.sendAndLoad('$page', 'POST',x_GetLogs$t);		
		
		}
		
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function restorefrom(){
	$sock=new sockets();
	$path=base64_encode($_POST["restore-path"]);
	$sock->getFrameWork("zarafa.php?zarafadb-restore=$path");
	
	
}

function logs_starter(){
$t=$_GET["t"];

$html="	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
			setTimeout(\"GetLogs$t()\",1000);
	</script>";
echo $html;
}
function restorelogs(){
	$logfile="/usr/share/artica-postfix/ressources/logs/web/zarafa_restore_task.log";
	$f=explode("\n",@file_get_contents($logfile));
	krsort($f);
	echo $f;
	
}

