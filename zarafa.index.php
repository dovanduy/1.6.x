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
	if(isset($_GET["popup-status"])){popup_status();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}
	if(isset($_GET["popup-www"])){popup_www();exit;}
	if(isset($_GET["popup-mailbox"])){popup_mailbox_tabs();exit;}
	if(isset($_GET["popup-mailbox-section"])){popup_mailbox();exit;}
	if(isset($_GET["mailboxes"])){mailbox_list();exit;}
	if(isset($_GET["popup-license"])){popup_license();exit;}
	if(isset($_POST["ZarafaHashRebuild"])){popup_mailbox_rebuild();exit;}
	if(isset($_POST["zlicense"])){save_license();exit;}
js();
function js(){
	$page=CurrentPageName();
	if(isset($_GET["font-size"])){$fontsize="&font-size={$_GET["font-size"]}";}
	echo "$('#BodyContent').load('$page?popup=yes$fontsize');";
	
	
	
}

function popup_www(){
	
	$html="
	<div id='zarafa-inline-config'></div>
	<script>
		Loadjs('zarafa.web.php?in-line=yes');
	</script>
	";
	
	echo $html;
	
	
}

function popup(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableZarafaMulti=$sock->GET_INFO("EnableZarafaMulti");
	if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	
	if($EnableZarafaMulti>0){
		$array["popup-multi"]="{multiple_zarafa_instances}";
	}
	
	$array["popup-status"]="{status}";
	$array["popup-www"]="{parameters}";
	if($q->COUNT_ROWS("zarafa_orphaned", "artica_backup")>0){
		$array["popup-orphans"]="{orphans}";
	}
	
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$adduri="&font-size={$_GET["font-size"]}";$adduri2="?font-size={$_GET["font-size"]}";}
	
	$array["popup-instances"]="{multiple_webmail}";
	$array["popup-mailbox"]="{mailboxes}";
	$array["popup-license"]="{zarafa_license}";
	$array["tools"]="{tools}";
	
	if(count($array)>6){$fontsize="font-size:12px"; }
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-indexer"){
			$html[]="<li><a href=\"zarafa.indexer.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="popup-multi"){
			$html[]="<li><a href=\"zarafa.multi.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="popup-mysql"){
			$html[]="<li><a href=\"zarafa.mysql.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="tools"){
			$html[]="<li><a href=\"zarafa.tools.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="popup-orphans"){
			$html[]="<li><a href=\"zarafa.orphans.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="popup-instances"){
			$html[]="<li><a href=\"zarafa.freewebs.php$adduri2\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		$html[]="<li><a href=\"$page?$num=yes$adduri\"><span>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_zarafa style='width:759px;height:100%;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafa').tabs();
			
			
			});
			QuickLinkShow('quicklinks-APP_ZARAFA');
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);
	
}

function popup_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$users=new usersMenus();
	if($users->YAFFAS_INSTALLED){
		$yaffas="<div class=explain>{APP_YAFFAS_TEXT}</div>";
	}
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><img src='img/zarafa-box-256.png'></td>
		<td valign='top' width=99%>
		<H3>{APP_ZARAFA} v{$ini->_params["APP_ZARAFA"]["master_version"]}</H3>
		<div class=explain>{APP_ZARAFA_TEXT}</div>$yaffas
		<table style='width:100%'>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('postfix.events.new.php?js-zarafa=yes');\" 
			style='font-size:13px;text-decoration:underline'>{APP_ZARAFA}:{events}</a></td>
		</tr>
		</table>
	</tr>
	</table>
	<div id='zarafa-services-status' style='width:100%;height:600px;min-height:600px;overflow:auto'></div>
	
	
	<script>
		LoadAjax('zarafa-services-status','$page?services-status=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function services_status(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$array[]="APP_ZARAFA";
	$array[]="APP_ZARAFA_GATEWAY";
	$array[]="APP_ZARAFA_SPOOLER";
	$array[]="APP_ZARAFA_WEB";
	$array[]="APP_ZARAFA_MONITOR";
	$array[]="APP_ZARAFA_DAGENT";
	$array[]="APP_ZARAFA_ICAL";
	$array[]="APP_ZARAFA_INDEXER";
	$array[]="APP_ZARAFA_LICENSED";
	$array[]="APP_ZARAFA_SEARCH";
	$array[]="APP_YAFFAS";

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
	$ini->loadString($datas);
	
	while (list ($num, $ligne) = each ($array) ){
		$tr[]=DAEMON_STATUS_ROUND($ligne,$ini,null,1);
		
	}
	
$tables[]="<table style='width:99%' class=form>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>
<div style='width:100%;text-align:right'>". 
imgtootltip("32-refresh.png","{refresh}","LoadAjax('zarafa-services-status','$page?services-status=yes');")."</div>";


$html=implode("\n",$tables);	
echo $tpl->_ENGINE_parse_body($html);		

	
	
}

function popup_mailbox_tabs(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup-mailbox-section"]="{production_mailboxes}";
	$array["popup-orphans"]="{orphans}";
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="popup-orphans"){
			$html[]="<li><a href=\"zarafa.orphans.php$adduri2\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}

	
		
		$html[]="<li><a href=\"$page?$num=yes$adduri\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_zarafaMBX style='width:100%;height:100%;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafaMBX').tabs();
			
			
			});
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);
		
	
	
}


function popup_mailbox(){
	$page=CurrentPageName();
	$html="
	<div id='zarafa-inline-mailbox'></div>
	<script>
		LoadAjax('zarafa-inline-mailbox','$page?mailboxes=yes');
	</script>
	";
	
	echo $html;	
	
}

function popup_mailbox_rebuild(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?zarafa-hash=yes&rebuild=yes");
	
}

function mailbox_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$db=unserialize(base64_decode($sock->getFrameWork("cmd.php?zarafa-hash=yes")));
	$ZarafaHashRebuild=$tpl->javascript_parse_text("{ZarafaHashRebuild}");
	
		$html="
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>". imgtootltip("refresh-24.png","{refresh}","ZarafaHashRebuild()")."</th>
	<th>{email}</th>
	<th>{mailbox_size}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
		while (list ($domain, $array) = each ($db) ){	
			
			while (list ($uid, $infos) = each ($array["USERS"]) ){	
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$js=MEMBER_JS($uid,1,1);
				if($infos["CURRENT_STORE_SIZE"]==null){$infos["CURRENT_STORE_SIZE"]=0;}
				$html=$html."
				<tr class=$classtr>
				<td width=1%>". imgtootltip("user-32.png","{view}",$js)."</td>
				<td><strong style='font-size:13px'>{$infos["EMAILADDRESS"]}</strong></td>
				<td align=center width=1%><strong style='font-size:13px'>{$infos["CURRENT_STORE_SIZE"]}</strong></td>
				</tr>
				
				";
				
			}
			
			
		}
	
	$html=$html."</table>
	<script>
	var x_ZarafaHashRebuild= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		LoadAjax('zarafa-inline-mailbox','$page?mailboxes=yes');
	}
			
	
	
	function ZarafaHashRebuild(){
		if(confirm('$ZarafaHashRebuild')){
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaHashRebuild','yes');
			AnimateDiv('zarafa-inline-mailbox');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaHashRebuild);
		}
	}
		
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function popup_license(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$license=base64_decode($sock->getFrameWork("cmd.php?zarafa-read-license=yes"));
	
	if($license==null){
		$license_info="{ZARAFA_LICENSE_USE_FREE}";
	}else{
		$license_info=trim($sock->GET_INFO("ZarafaLicenseInfos"));
		if(preg_match("#([0-9]+)\s+total#",$license_info,$re)){$license_info=$re[1]." {users}";}
	}
	
	$html="
	<div style='font-size:14px;font-weight:bolder'>{license_info}: $license_info</div>
	<br>
	<center>
	<table class=form>
	<tr>
		<td class=legend>{serial_number}:</td>
		<td><code style='font-size:16px;font-weight:bold'>$license</td>
	</tr>
	</table>
	</center>
	<hr>
	
	<div class=explain>{ZARAFA_UPDATE_SERIAL_EXPLAIN}</div>
	<center>
	<div id='zarafa-license-form'>
	<table class=form>
	<tr>
		<td class=legend>{update_serial_number}:</td>
		<td>". Field_text("serial_number",null,"font-size:16px;padding:5px")."</td>
		<td width=1%>". button("{apply}","ZarafaUpdateLicense()")."</td>
	</tr>
	</table>
	</center>
	</div>
	<script>
		
	var x_ZarafaUpdateLicense= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		RefreshTab('main_config_zarafa');
		}
		
	function ZarafaUpdateLicense(){
			var XHR = new XHRConnection();
			XHR.appendData('zlicense',document.getElementById('serial_number').value);
			AnimateDiv('zarafa-license-form');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaUpdateLicense);
			
			
		}


</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
		
	
	
}

function save_license(){
	$zlicense=base64_encode($_POST["zlicense"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?zarafa-write-license=yes&license=$zlicense");
	
}
//zarafa-stats : http://forums.zarafa.com/viewtopic.php?f=9&t=2913
?>

