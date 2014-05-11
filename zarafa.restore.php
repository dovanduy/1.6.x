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
	
	if(isset($_GET["logs-starter"])){logs_starter();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["restore-path"])){restorefrom();exit;}
	if(isset($_POST["restore-logs"])){restorelogs();exit;}
	if(isset($_GET["ShowProgress-js"])){ShowProgress_js();exit;}
	
popup();


function ShowProgress_js(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$titleAdd=null;
	$please_wait=$tpl->javascript_parse_text("{please_wait}");
	$sock=new sockets();
	$ISRunAR=unserialize(base64_decode($sock->getFrameWork("zarafa.php?restore-process-array=yes")));
	if(is_array($ISRunAR)){
		$PID=intval($ISRunAR["PID"]);
		$TIME=$ISRunAR["TIME"];
		if($ISRunAR["SIZE"]>0){
			$ISRunARS=FormatBytes($ISRunAR["SIZE"]);
		}
	
	}
	
	if($PID>0){
		$titleAdd=$tpl->javascript_parse_text("{running} {since} {$TIME}Mn PID $PID");
	}
	header("content-type: application/x-javascript");
	$file="/usr/share/artica-postfix/ressources/RestoreFromBackup_progress.progress";
	$ARRAY=unserialize(@file_get_contents($file));
	if(!is_array($ARRAY)){
		echo "
		function Start$tt(){
			Loadjs('$page?ShowProgress-js=yes&t=$t');
		}
		document.getElementById('title$t').innerHTML='$please_wait';
		setTimeout('Start$tt()',3000);
		";
		return;
		
	}
	
	$text=$tpl->javascript_parse_text($ARRAY["TEXT"]);
	$prc=$ARRAY["POURC"];
	
	if($prc>99){
		echo "
			document.getElementById('title$t').innerHTML='$text&nbsp;$ISRunARS';
			$('#Status$t').progressbar({ value: $prc });
			";
		return;
	}
	
	
	
	echo "
	function Start$tt(){
		
		GetLogs$t();
		Loadjs('$page?ShowProgress-js=yes&t=$t');
	}
	
	if(document.getElementById('title$t')){
		document.getElementById('title$t').innerHTML='$text&nbsp;$ISRunARS&nbsp;$titleAdd';
		$('#Status$t').progressbar({ value: $prc });
		setTimeout('Start$tt()',2000);
	}
	";	
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$PID=0;
	$ISRunAR=unserialize(base64_decode($sock->getFrameWork("zarafa.php?restore-process-array=yes")));
	if(is_array($ISRunAR)){
		$PID=intval($ISRunAR["PID"]);
		$TIME=$ISRunAR["TIME"];
		
	}
	
	$warn_restore_articadb=$tpl->javascript_parse_text("{warn_restore_articadb}");
	
	$html="
	<div style='font-size:18px' class=explain>{zarafadb_restore_explain}</div>
	<div style='font-size:22px;text-align:center;margin:10px' id='title$t'></div>
	<div style='margin:10px;min-height:75px' id='Status$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:26px'>{backup_file}:</td>
		<td>". field_text("backup$t",null,"font-size:26px;width:600px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?target-form=backup$t')",22)."</td>
	</tr>
	<tr>
	<td colspan=3 align='center'><hr>". button("{restore}","Restore$t()",32)."</td>
	</tr>
	</table>		
	<div id='start-$t'></div>		
	<script>
		var PID=$PID;
		var x_Restore$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
		  document.getElementById('start-$t').innerHTML='';
		  LoadAjax('start-$t','$page?logs-starter=yes&t=$t');
		}		
		
		function Restore$t(){
			var path=document.getElementById('backup$t').value;
			if(!confirm('$warn_restore_articadb'+path)){return;}
			var XHR = new XHRConnection();
			XHR.appendData('restore-path',path);
			XHR.sendAndLoad('$page', 'POST',x_Restore$t);	
		}
		
		
		
		
		var x_GetLogs$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){
	      	document.getElementById('textToParseCats-$t').innerHTML=tempvalue;
	       }

	      }	

	      
		function GetLogs$t(){
			var XHR = new XHRConnection();
			XHR.appendData('restore-logs','yes');
			XHR.appendData('t','$t');
			XHR.setLockOff();
			XHR.sendAndLoad('$page', 'POST',x_GetLogs$t);		
		
		}
		$('#Status$t').progressbar({ value: 1 });
		
		if(PID>0){
			LoadAjax('start-$t','$page?logs-starter=yes&t=$t');
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
$page=CurrentPageName();
$html="	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>Loadjs('$page?ShowProgress-js=yes&t=$t');</script>
	";
echo $html;
}
function restorelogs(){
	$logfile="/usr/share/artica-postfix/ressources/logs/web/zarafa_restore_task.log";
	$f=explode("\n",@file_get_contents($logfile));
	krsort($f);
	echo @implode("\n", $f);
	
}

