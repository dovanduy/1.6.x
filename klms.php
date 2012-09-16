<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
	if(isset($_POST["accept-eula"])){accept_eula();exit;}
	if(isset($_GET["pattern-status"])){pattern_status();exit;}
	if(isset($_POST["klms-reset-password"])){reset_web_password();exit;}
	if(isset($_POST["apply-config"])){apply_config();exit;}
tabs();




function tabs(){
	$sock=new sockets();
	$klmsReadLicense=$sock->GET_INFO("klmsReadLicense");
	if(!is_numeric($klmsReadLicense)){$klmsReadLicense=0;}
	if($klmsReadLicense==0){start_license();exit;}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	
		$array["status"]='{status}';
		$array["web-console"]='{webconsoles}';
		$array["events"]='{events}';
		
		
		
		$fontsize=14;
		if($tpl->language=="fr"){
			if(count($array)>7){
				$fontsize=12;
			}
			
		}
		
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$tab[]="<li><a href=\"klms.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="web-console"){
			$tab[]="<li><a href=\"klms.console.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_klms_tabs' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_klms_tabs').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$t=time();
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' style='width:1%'>
			<div id='$t-status'></div>
			
			<div style='width:100%;margin-top:10px;text-align:right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjaxTiny('$t-status','$page?service-status=yes');")."</div>
			</td>
		<td valign='top' style='width:1%'><div id='$t-toolbox'></div></td>
	</tr>
	</table>
	<script>
		LoadAjaxTiny('$t-status','$page?service-status=yes');
		LoadAjaxTiny('$t-toolbox','$page?service-toolbox=yes');		
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function services_status(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=base64_decode($sock->getFrameWork("klms.php?status=yes"));
	
	$ini->loadString($datas);	
	$APP_KLMSS=DAEMON_STATUS_ROUND("APP_KLMSS",$ini,null,0);
	$APP_KLMSD=DAEMON_STATUS_ROUND("APP_KLMSDB",$ini,null,0);
	$APP_KLMS_MILTER=DAEMON_STATUS_ROUND("APP_KLMS_MILTER",$ini,null,0);
	$table_status="<table style='width:99%' class=form>
	<tr>
	<td><div id='pattern-status' style='margin-bottom:10px'></div></td>
	</tr>	
	<tr>
	<td>$APP_KLMSS</td>
	</tr>
	<tr>
	<td>$APP_KLMSD</td>
	</tr>
	<td>$APP_KLMS_MILTER</td>
	</tr>	
	</table>
	<script>
		LoadAjax('pattern-status','$page?pattern-status=yes');
	</script>
	";	
	
	echo $tpl->_ENGINE_parse_body($table_status);
}

function services_toolbox(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	

	
	
	$tr[]=Paragraphe32("watchdog", "watchdog_klms8_text", "Loadjs('klms8.watchdog.php')", "watchdog-32.png");
	$tr[]=Paragraphe32("tasks", "tasks_klms8_text", "Loadjs('klms.tasks.php')", "folder-tasks-32.png");
	$tr[]=Paragraphe32("license_info", "license_info_text", "Loadjs('klms.license.php')", "32-key.png");
	$tr[]=Paragraphe32("mta_link", "mta_link_text", "Loadjs('klms.mta.php')", "comut-32.png");
	$tr[]=Paragraphe32("apply_config", "apply_klms_config_text", "ApplyConfigKLMS()", "32-settings-refresh.png");
	
	
	
	
	
	$table=CompileTr2($tr,"form");
	
	$html="
	$table
	<script>
		var X_applycf= function (obj) {
 			var tempvalue=obj.responseText;
	      	if(tempvalue.length>3){alert(tempvalue);}
		}
			
		function ApplyConfigKLMS(){
			var XHR = new XHRConnection();
			XHR.appendData('apply-config','yes');
			XHR.sendAndLoad('$page', 'POST',X_applycf);	
		}		
	
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function start_license(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$text=utf8_decode(base64_decode($sock->getFrameWork("klms.php?legal-license=yes")));
	$text=htmlentities($text);
	$text=nl2br($text);	
	$textR=explode("\n", $text);
	while (list ($num, $ligne) = each ($textR) ){
		$textR[$num]=str_replace("KASPERSKY LAB END USER LICENSE AGREEMENT","<H1>KASPERSKY LAB END USER LICENSE AGREEMENT</H1>",$textR[$num]);
		if(preg_match("#^[0-9]+\.\s+[A-Z]+#", trim($ligne))){
			$textR[$num]="<div style='font-size:14px;font-weight:bold;margin-bottom:5px'>$ligne</div>";
			continue;
		}
	if(preg_match("#^[0-9]+\.[0-9]+\.\s+[A-Z]+#", trim($ligne))){
			$textR[$num]="<div style='font-size:12.5px;font-weight:normal;margin:5px;padding-left:8px'>$ligne</div>";
			continue;
		}	
		
	if(preg_match("#^-\s+(.+)#", trim($ligne),$re)){
			$textR[$num]="<div style='margin-left:20px;'>
				<li style='font-size:12.5px'>{$re[1]}</li></div>";
			continue;
		}

		if(preg_match("#^[a-z]\.\s+(.+)#", trim($ligne),$re)){
			$textR[$num]="<div style='margin-left:30px;'>
				<div style='font-size:12.5px'>$ligne</div></div>";
			continue;
		}		
	
	}
	$text=@implode("\n", $textR);

	echo "<div style='width:95%;font-size:12.5px' class=form id='eula-div'>$text
	<center style='margin-top:20px'>". $tpl->_ENGINE_parse_body(button("{accept}","AcceptEula()","18px"))."</center>
	</div>
	
	<script>
		var X_AcceptEula= function (obj) {
			var results=obj.responseText;
			QuickLinkSystems('section_klms');
			}
			
		function AcceptEula(){
			var XHR = new XHRConnection();
			XHR.appendData('accept-eula','yes');
			AnimateDiv('eula-div');
			XHR.sendAndLoad('$page', 'POST',X_AcceptEula);
			
		}		
	
	</script>
	";
	
}
function accept_eula(){
	$sock=new sockets();
	$sock->SET_INFO("klmsReadLicense", 1);
	$sock->getFrameWork("klms.php?build=yes");
	$sock->getFrameWork("klms.php?setup=yes");
	}

function pattern_status(){
	$sock=new sockets();
	$tpl=new templates();
	$as_info=base64_decode($sock->getFrameWork("klms.php?as-info=yes"));
	$av_info=base64_decode($sock->getFrameWork("klms.php?av-info=yes"));
	
	if(preg_match("#ERROR:.*?(.*)#", $av_info,$re)){$error_bases="<tr>
	<td width=1% align='right'><img src='img/status_warning.gif'></td>
	<td valign='middle'><span style='color:#E21919;font-size:12px'>{error}:{$re[1]}</strong></td></tr>";}
	$av_info=str_replace("UpToDate","",$av_info);
	$as_info=str_replace("UpToDate","",$as_info);
	
	
	$time=strtotime($as_info);
	$adate=date('Y-m-d H:i:s');
	
	if(preg_match("#([0-9]+)\s+(.+?)#", $av_info,$re)){
		$date=$re[1];
		$time=strtotime($date);
		$pdate=date('Y-m-d H:i:s');
	}
	
	$html="
	<table style='width:100%;background-color:#D5EED9;font-size:11px;line-height:auto;margin-bottom:10px' class=TableRemove>
<tr>
<td width=1% valign='top' style='background-color:#D5EED9;font-size:11px;line-height:auto'>
	<img src='img/database-link-48.png'></td>
<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto'>
	<table style='width:100%;background:transparent;border:0px;padding:0px;background-color:#D5EED9;
		font-size:11px;line-height:auto' class=TableRemove>
		<tr>
		<td colspan=2 ><strong style='font-size:14px'>{update_status}:</td>
		</tr>
		<tr>
		<td align='right' nowrap style='background-color:#D5EED9;font-size:11px;line-height:auto'>
			<strong>anti-spam:
		</td>
		<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto'>
			<strong><span style='font-size:12px'>$adate</strong></td>
		</tr>
		
		<tr>
		<td align='right' nowrap style='background-color:#D5EED9;font-size:11px;line-height:auto'>
			<strong>antivirus:
		</td>
		<td style='background-color:#D5EED9;font-size:11px;border:0px;line-height:auto'>
			<strong><span style='font-size:12px'>$pdate</strong></td>
		</tr>		
		$error_bases
	</table>
	</td>
	</tr>
</table>";
	
	echo $tpl->_ENGINE_parse_body(RoundedLightGreen($html));
	
}

function reset_web_password(){
	$sock=new sockets();
	$sock->getFrameWork("klms.php?reset-web-password=yes");
	
	
}

function apply_config(){
	$sock=new sockets();
	$sock->getFrameWork("klms.php?apply-config=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{service_reloaded_in_background_mode}",1);
	
}