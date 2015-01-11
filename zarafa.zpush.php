<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["update"])){update();exit;}
	
	if(isset($_POST["ZarafaApacheEnable"])){zarafa_settings_webmail_save();exit;}
	if(isset($_POST["apacheMasterconfig"])){zarafa_settings_performances_save();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["restore-logs"])){restorelogs();exit;}
	if(isset($_GET["ShowProgress-js"])){ShowProgress_js();exit;}
	if(isset($_GET["logs-starter"])){logs_starter();exit;}
	if(isset($_POST["install-zpush"])){install_zpush();exit;}
	
	
popup();	
	
function popup(){
		$q=new mysql();
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
		$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
		if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
		$users=new usersMenus();
		if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	
		
		$array["status"]="{APP_Z_PUSH}";
		$array["www"]="{webservers}";
		$array["update"]="{update}";
		
			
			
		$fontsize="font-size:18px;";
		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="www"){
				$html[]="<li><a href=\"freeweb.servers.php?force-groupware=Z-PUSH\" style='$fontsize' ><span>$ligne</span></a></li>\n";
				continue;
			}
			
			$html[]="<li><a href=\"$page?$num=yes\" style='$fontsize' ><span>$ligne</span></a></li>\n";
		}
		
	
		$html=build_artica_tabs($html,'main_zarafazpush',975)."
		<script>LeftDesign('push-mail-256-opac20.png');</script>";
	
	echo $html;	
}

function update(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$zpush_version=base64_decode($sock->getFrameWork("zarafa.php?zpush-version=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadFile("ressources/index.ini");
	$couldversion=$ini->_params["NEXT"]["z-push"];
	
	$html="
	<div style='font-size:22px;text-align:center;margin:10px' id='title$t'></div>
	<div style='margin:10px;min-height:75px' id='Status$t'></div>
	<div id='start-$t'></div>	
	<center style='margin:50px'>		
	<hr>". button("{update} v.$couldversion","Restore$t()",32)."</center>
	<script>
		var x_Restore$t= function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
		  document.getElementById('start-$t').innerHTML='';
		  LoadAjax('start-$t','$page?logs-starter=yes&t=$t');
		}		
		
		function Restore$t(){
			var XHR = new XHRConnection();
			XHR.appendData('install-zpush','yes');
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
	</script>				
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}




function status(){
	$sock=new sockets();
	$zpush_version=base64_decode($sock->getFrameWork("zarafa.php?zpush-version=yes"));
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top'><img src='img/smartphone-256.png' style='margin-right:15px'></td>
		<td valign='top' style='vertical-align:top'><div style='font-size:24px'>Z-Push V$zpush_version</div>
		<div class=text-info style='font-size:18px;margin-top:15px'>{APP_Z_PUSH_TEXT}</div>
		<div style='height:450px'>&nbsp;</div>
		</td>
	</tr>
	</table>	
			
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}


function ShowProgress_js(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$titleAdd=null;
	$please_wait=$tpl->javascript_parse_text("{please_wait}");
	$sock=new sockets();
	

	header("content-type: application/x-javascript");
	$file="/usr/share/artica-postfix/ressources/zpush_progress.progress";
	$ARRAY=unserialize(@file_get_contents($file));
	if(!is_array($ARRAY)){
		echo "
		function Start$tt(){
			Loadjs('$page?ShowProgress-js=yes&t=$t');
		}
		if(document.getElementById('title$t')){
			document.getElementById('title$t').innerHTML='$please_wait';
			setTimeout('Start$tt()',3000);
		}
		";
		return;

	}

	$text=$tpl->javascript_parse_text($ARRAY["TEXT"]);
	$prc=$ARRAY["POURC"];

	if($prc>99){
	echo "
		if(document.getElementById('title$t')){
			document.getElementById('title$t').innerHTML='$text&nbsp;';
			$('#Status$t').progressbar({ value: $prc });
		}
	";
	return;
	}



echo "
function Start$tt(){
	GetLogs$t();
	Loadjs('$page?ShowProgress-js=yes&t=$t');
}

if(document.getElementById('title$t')){
	document.getElementById('title$t').innerHTML='$text&nbsp;$titleAdd';
	$('#Status$t').progressbar({ value: $prc });
	setTimeout('Start$tt()',2000);
}
";

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
	$logfile="/usr/share/artica-postfix/ressources/logs/web/zpush-install.log";
	$f=explode("\n",@file_get_contents($logfile));
	krsort($f);
	echo @implode("\n", $f);

}
function install_zpush(){
	$sock=new sockets();
	@unlink("/usr/share/artica-postfix/ressources/zpush_progress.progress");
	$sock->getFrameWork("zarafa.php?install-zpush=yes");
}

