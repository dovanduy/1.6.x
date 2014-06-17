<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.autofs.inc');
	include_once('ressources/class.computers.inc');

	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_POST["COMPRESS_ENABLE"])){Save();exit;}
	if(isset($_GET["res-list"])){echo list_ressource();exit;}
	if(isset($_GET["backup-config"])){echo backup_config();exit;}
	if(isset($_GET["backup-save-config"])){backup_save_config();exit;}
	if(isset($_GET["cyrus-delete"])){backup_delete();exit;}
	if(isset($_GET["backup-perform-now"])){backup_now();exit;}
	

js();

function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();

	$array["popup"]='{parameters}';
	$array["schedules"]='{schedules}';
	$array["events"]='{events}';
	

	

	$fontsize=18;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		if($num=="schedules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=69\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"cyrus.watchdog-events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}



	$html= build_artica_tabs($html,'main_cyrus_backup');
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;

}



function js(){
$page=CurrentPageName();
	$prefix=str_replace('.','_',$page);
	$prefix=str_replace('-','',$prefix);
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body('{APP_CYRUS_BACKUP}');
	$settings=$tpl->_ENGINE_parse_body('{settings}');
	$CYR_BACKUP_NOW=$tpl->_ENGINE_parse_body('{CYR_BACKUP_NOW}');
	$load="{$prefix}LoadPage();";
	
	if(isset($_GET["add-automount"])){$load="CyrusBackupAddResourceFormWebPages()";}
	
$html="
	var {$prefix}timeout=0;
	var {$prefix}timerID  = null;
	var {$prefix}tant=0;
	var {$prefix}reste=0;	

	var x_CyrusBackupAddResourceFormWebPages= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			{$prefix}LoadPage();
			}	
	
	function CyrusBackupAddResourceFormWebPages(){
		var XHR = new XHRConnection();
		var default_schedule='0 0,3,12,19 * * *';
    	XHR.appendData('cyrus-ressource','{$_GET["add-automount"]}');
    	XHR.appendData('cyrus-schedule',default_schedule);
    	XHR.sendAndLoad('$page', 'GET',x_CyrusBackupAddResourceFormWebPages);
	}
	
	function CyrusBackupNow(HD){
		alert('$CYR_BACKUP_NOW');
		var XHR = new XHRConnection();
		XHR.appendData('backup-perform-now',HD);
		XHR.sendAndLoad('$page','GET');
	}

	function {$prefix}LoadPage(){
		YahooLogWatcher(700,'$page?popup=yes','$title');
	}
	
	function CyrusBackupOptions(HD){
		RTMMail(550,'$page?backup-config='+HD,'$settings');
	}	
	
	
		var x_SaveBackupCyrusSettings= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RTMMailHide();
			}		
	
	function SaveBackupCyrusSettings(HD){
		var XHR = new XHRConnection();
		XHR.appendData('backup-save-config',HD);
    	XHR.appendData('BACKUP_MAILBOXES',document.getElementById('BACKUP_MAILBOXES').value);
    	XHR.appendData('BACKUP_DATABASES',document.getElementById('BACKUP_DATABASES').value);
    	XHR.appendData('BACKUP_ARTICA',document.getElementById('BACKUP_ARTICA').value);
    	XHR.appendData('CONTAINER',document.getElementById('CONTAINER').value);
    	XHR.appendData('MAX_CONTAINERS',document.getElementById('MAX_CONTAINERS').value);
 		document.getElementById('CyrBackDiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
    	XHR.sendAndLoad('$page', 'GET',x_SaveBackupCyrusSettings);
	}
	
	     

	
	
var x_AddCyrusBackupResource=function (obj) {
	LoadAjax('cyrus-list-res','$page?res-list=yes');
	}	
	
	function AddCyrusBackupResource(){
    	var XHR = new XHRConnection();
    	XHR.appendData('cyrus-ressource',document.getElementById('cyrus-ressource').value);
 		document.getElementById('cyrus-list-res').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
    	XHR.sendAndLoad('$page', 'GET',x_AddCyrusBackupResource);
	}
	
	function CyrusBackupDelete(mount){
	var XHR = new XHRConnection();
    	XHR.appendData('cyrus-delete',mount);
 		document.getElementById('cyrus-list-res').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
    	XHR.sendAndLoad('$page', 'GET',x_AddCyrusBackupResource);
	}
	
	function SetCyrusBackupSchedule(res){
		var XHR = new XHRConnection();
    	XHR.appendData('cyrus-ressource',res);
    	XHR.appendData('cyrus-schedule',document.getElementById(res+'_SCHEDULE').value);
 		document.getElementById('cyrus-list-res').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
    	XHR.sendAndLoad('$page', 'GET',x_AddCyrusBackupResource);
	}
	

	$load";

	echo $html;
	}
	
function Save(){
	$sock=new sockets();
	$CyrusBackupSettings=unserialize(base64_decode($sock->GET_INFO("CyrusBackupNas")));
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$CyrusBackupSettings[$key]=$value;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($CyrusBackupSettings)), "CyrusBackupNas");
	
}
	
function backup_save_config(){

	$HD=$_GET["backup-save-config"];
	$cyr=new cyrusbackup();
	
	while (list ($key, $value) = each ($_GET) ){
		$cyr->list[$HD][$key]=$value;	
	}
	$cyr->save();
}
	
	
function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	
	$sock=new sockets();
	$CyrusBackupSettings=unserialize(base64_decode($sock->GET_INFO("CyrusBackupNas")));
	if(!is_numeric($CyrusBackupSettings["COMPRESS_ENABLE"])){$CyrusBackupSettings["COMPRESS_ENABLE"]=1;}
	$t=time();
	$DAVFS_INSTALLED=1;
	if(!$users->DAVFS_INSTALLED){
		$DAVFS_INSTALLED=0;
		$error_DAVFS_INSTALLED="<p class=text-error style='font-size:16px'>{error_davfs_not_installed}</p>";
	}
	
	
	
	
	$html="
	<div style='font-size:26px;margin-bottom:20px'>{APP_CYRUS_BACKUP}</div>
	<div class=explain style='font-size:18px'>{backup_cyrus_mailboxes}</div>
	
			
	<div style='width:98%' class=form>
		<div style='font-size:22px;margin-bottom:20px'>{general_settings}:</div>			
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{compress_containers}:</td>
			<td>". Field_checkbox("COMPRESS_ENABLE-$t",1,$CyrusBackupSettings["COMPRESS_ENABLE"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{max_containers}:</td>
			<td>".Field_text("maxcontainer-$t",$CyrusBackupSettings["maxcontainer"],"font-size:18px;width:110px")."</td>
		</tr>						
		</table>
	</div>
					

						
	<div style='width:98%' class=form>			
	<div style='font-size:22px;margin-bottom:20px'>{TAB_WEBDAV} (WebDAV):</div>
	$error_DAVFS_INSTALLED
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{enable}:</td>
		<td>". Field_checkbox("WEBDAV_ENABLE-$t",1,$CyrusBackupSettings["WEBDAV_ENABLE"],"SwitChDAVS$t()")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{url}:</td>
		<td>". Field_text("WEBDAV_SERVER-$t",$CyrusBackupSettings["WEBDAV_SERVER"],"font-size:18px;padding:3px;width:99%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{web_user}:</td>
		<td>". Field_text("WEBDAV_USER-$t",$CyrusBackupSettings["WEBDAV_USER"],"font-size:18px;padding:3px;width:70%")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>". Field_password("WEBDAV_PASSWORD-$t",$CyrusBackupSettings["WEBDAV_PASSWORD"],"font-size:18px;padding:3px;width:70%")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{webdav_directory}:</td>
		<td>". Field_text("WEBDAV_DIR-$t",$CyrusBackupSettings["WEBDAV_DIR"],"font-size:18px;padding:3px;width:90%",
				null,null,null,false,"SaveCK$t(event)")."</td>
	</tr>			
	</table>	
	</div>
<div style='width:98%' class=form>			
	<div style='font-size:22px;margin-bottom:20px'>{NAS_storage}:</div>		
<table style='width:100%'>						
	<tr>
		<td class=legend style='font-size:18px'>{enable}:</td>
		<td>". Field_checkbox("NAS_ENABLE-$t",1,$CyrusBackupSettings["NAS_ENABLE"],"SwitChNAS$t()")."</td>
	</tr>															
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>".Field_text("hostname-$t",$CyrusBackupSettings["hostname"],"font-size:18px;width:99%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{shared_folder}:</td>
		<td>".Field_text("folder-$t",$CyrusBackupSettings["folder"],"font-size:18px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{username}:</td>
		<td>".Field_text("username-$t",$CyrusBackupSettings["username"],"font-size:18px;width:200px")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>".Field_password("password-$t",$CyrusBackupSettings["password"],"font-size:18px;width:200px")."</td>
	</tr>
	</table>					
</div>
										
<div style='width:98%' class=form>			
	<div style='font-size:22px;margin-bottom:20px'>{smtp_notifications}:</div>		
<table style='width:100%'>							
	<tr>
		<td class=legend style='font-size:18px'>{enable_smtp_notifications}:</td>
		<td>". Field_checkbox("$t-notifs", 1,$CyrusBackupSettings["notifs"],"notifsCheck{$t}()")."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text("smtp_server_name-$t",trim($CyrusBackupSettings["smtp_server_name"]),'font-size:18px;padding:3px;width:250px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text("smtp_server_port-$t",trim($CyrusBackupSettings["smtp_server_port"]),'font-size:18px;padding:3px;width:40px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("smtp_sender-$t",trim($CyrusBackupSettings["smtp_sender"]),'font-size:18px;padding:3px;width:290px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_dest}:</strong></td>
		<td>" . Field_text("smtp_dest-$t",trim($CyrusBackupSettings["smtp_dest"]),'font-size:18px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text("smtp_auth_user-$t",trim($CyrusBackupSettings["smtp_auth_user"]),'font-size:18px;padding:3px;width:200px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($CyrusBackupSettings["smtp_auth_passwd"]),'font-size:18px;padding:3px;width:200px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox("tls_enabled-$t",1,$CyrusBackupSettings["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:18px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox("ssl_enabled-$t",1,$CyrusBackupSettings["ssl_enabled"])."</td>
	</tr>	
</table>
	</div>
	
		<div style='text-align:right;width:100%'>
		<hr>". button("{apply}"," Save$t()","26")."</div>	
	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('main_cyrus_backup');
}

function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function SwitChDAVS$t(){
	var DAVFS_INSTALLED=$DAVFS_INSTALLED;
	document.getElementById('WEBDAV_SERVER-$t').disabled=true;
	document.getElementById('WEBDAV_USER-$t').disabled=true;
	document.getElementById('WEBDAV_PASSWORD-$t').disabled=true;
	document.getElementById('WEBDAV_DIR-$t').disabled=true;
	document.getElementById('WEBDAV_ENABLE-$t').disabled=true;
	if(DAVFS_INSTALLED==0){CheckBoxDesignHidden();return;}
	document.getElementById('WEBDAV_ENABLE-$t').disabled=false;
	if(!document.getElementById('WEBDAV_ENABLE-$t').checked){CheckBoxDesignHidden();return;}
	document.getElementById('WEBDAV_SERVER-$t').disabled=false;
	document.getElementById('WEBDAV_USER-$t').disabled=false;
	document.getElementById('WEBDAV_PASSWORD-$t').disabled=false;
	document.getElementById('WEBDAV_DIR-$t').disabled=false;
	document.getElementById('WEBDAV_ENABLE-$t').disabled=false;	
	CheckBoxDesignHidden();
}

function SwitChNAS$t(){
	document.getElementById('hostname-$t').disabled=true;
	document.getElementById('folder-$t').disabled=true;
	document.getElementById('username-$t').disabled=true;
	document.getElementById('password-$t').disabled=true;
	if(!document.getElementById('NAS_ENABLE-$t').checked){CheckBoxDesignHidden();return;}
	document.getElementById('hostname-$t').disabled=false;
	document.getElementById('folder-$t').disabled=false;
	document.getElementById('username-$t').disabled=false;
	document.getElementById('password-$t').disabled=false;
	CheckBoxDesignHidden();
	
}
function notifsCheck{$t}(){
	document.getElementById('smtp_auth_passwd-$t').disabled=true;
	document.getElementById('smtp_auth_user-$t').disabled=true;
	document.getElementById('smtp_dest-$t').disabled=true;
	document.getElementById('smtp_sender-$t').disabled=true;
	document.getElementById('smtp_server_port-$t').disabled=true;
	document.getElementById('smtp_server_name-$t').disabled=true;
	document.getElementById('tls_enabled-$t').disabled=true;
	document.getElementById('ssl_enabled-$t').disabled=true;

	if( document.getElementById('$t-notifs').checked){
		document.getElementById('smtp_auth_passwd-$t').disabled=false;
		document.getElementById('smtp_auth_user-$t').disabled=false;
		document.getElementById('smtp_dest-$t').disabled=false;
		document.getElementById('smtp_sender-$t').disabled=false;
		document.getElementById('smtp_server_port-$t').disabled=false;
		document.getElementById('smtp_server_name-$t').disabled=false;
		document.getElementById('tls_enabled-$t').disabled=false;
		document.getElementById('ssl_enabled-$t').disabled=false;
	}	
	
	CheckBoxDesignHidden();
	
}


	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('WEBDAV_ENABLE-$t').checked){ XHR.appendData('WEBDAV_ENABLE',1); }else{ XHR.appendData('WEBDAV_ENABLE',0);}
	if(document.getElementById('NAS_ENABLE-$t').checked){ XHR.appendData('NAS_ENABLE',1); }else{ XHR.appendData('NAS_ENABLE',0);}
	if(document.getElementById('COMPRESS_ENABLE-$t').checked){ XHR.appendData('COMPRESS_ENABLE',1); }else{ XHR.appendData('COMPRESS_ENABLE',0);}
	XHR.appendData('WEBDAV_SERVER',encodeURIComponent(document.getElementById('WEBDAV_SERVER-$t').value));
	XHR.appendData('WEBDAV_USER',encodeURIComponent(document.getElementById('WEBDAV_USER-$t').value));
	XHR.appendData('WEBDAV_PASSWORD',encodeURIComponent(document.getElementById('WEBDAV_PASSWORD-$t').value));
	XHR.appendData('WEBDAV_DIR',encodeURIComponent(document.getElementById('WEBDAV_DIR-$t').value));
	
	XHR.appendData('hostname',encodeURIComponent(document.getElementById('hostname-$t').value));
	XHR.appendData('folder',encodeURIComponent(document.getElementById('folder-$t').value));
	XHR.appendData('username',encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
	
	var tls_enabled=0;
	var ssl_enabled=0;
	var notifs=0;
	
	if(document.getElementById('tls_enabled-$t').checked){tls_enabled=1;}
	if(document.getElementById('ssl_enabled-$t').checked){ssl_enabled=1;}
	if(document.getElementById('$t-notifs').checked){notifs=1;}
	XHR.appendData('smtp_server_name',encodeURIComponent(document.getElementById('smtp_server_name-$t').value));
	XHR.appendData('smtp_server_port',encodeURIComponent(document.getElementById('smtp_server_port-$t').value));
	XHR.appendData('smtp_sender',encodeURIComponent(document.getElementById('smtp_sender-$t').value));
	XHR.appendData('smtp_auth_user',encodeURIComponent(document.getElementById('smtp_auth_user-$t').value));
	XHR.appendData('smtp_auth_passwd',encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value));
	
	XHR.appendData('tls_enabled',tls_enabled);
	XHR.appendData('ssl_enabled',ssl_enabled);
	XHR.appendData('notifs',notifs);	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	
}
SwitChDAVS$t();				
SwitChNAS$t();		
notifsCheck{$t}();		
</script>			
";
	
	echo $tpl->_ENGINE_parse_body($html);
	return;
	$add=Paragraphe('disk-backup-64-add.png','{CYRUS_ADD_RESOURCES}','{CYRUS_ADD_RESOURCES_TEXT}',"javascript:Loadjs('automount.php?field=cyrus-ressource');");
	$resources=list_ressource();
	$html="<H1></H1>
	<p class=caption>{GENERIC_BACKUP_TEXT}</p>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%>$add</td>
		<td valign='top'>
		<table style='width:99%' class=form>
		<tr>
			<td valign='middle' class=legend nowrap>{resource}:</td>
			<td>". Field_text('cyrus-ressource',null,'width:240px')."</td>
			<td width=1%><input type='button' OnClick=\"javascript:AddCyrusBackupResource();\" value='{add}&nbsp&raquo;'>
		</tr>
		</table>
		<br>
		
		". RoundedLightWhite("<div id='cyrus-list-res'>$resources</div>")."
		
	</td>
	</tr>
	</table>";
		
	
	echo $tpl->_ENGINE_parse_body($html);
	}


function backup_config(){
	
	$cyrusbackup=new cyrusbackup();
	$datas=$cyrusbackup->list[$_GET["backup-config"]];
	
	if($datas["CONTAINER"]==null){$datas["CONTAINER"]="D";}
	if($datas["MAX_CONTAINERS"]==null){$datas["MAX_CONTAINERS"]="3";}
	if($datas["BACKUP_MAILBOXES"]==null){$datas["BACKUP_MAILBOXES"]="1";}
	if($datas["BACKUP_DATABASES"]==null){$datas["BACKUP_DATABASES"]="1";}
	if($datas["BACKUP_ARTICA"]==null){$datas["BACKUP_ARTICA"]="1";}
	if($datas["STOP_SERVICES"]==null){$datas["STOP_SERVICES"]="1";}
	
	$backup_container=array("D"=>"{day}","W"=>"{week}");
	$backup_container=Field_array_Hash($backup_container,"CONTAINER",$datas["CONTAINER"]);
	
	for($i=1;$i<20;$i++){
		$MaxContainer[$i]=$i;
	}
	
	$MaxContainer=Field_array_Hash($MaxContainer,"MAX_CONTAINERS",$datas["MAX_CONTAINERS"]);
	
	$BACKUP_MAILBOXES=Field_numeric_checkbox_img('BACKUP_MAILBOXES',$datas["BACKUP_MAILBOXES"],'{mailboxes_backup_explain}');
	$BACKUP_DATABASES=Field_numeric_checkbox_img('BACKUP_DATABASES',$datas["BACKUP_DATABASES"],'{databases_backup_explain}');
	$BACKUP_ARTICA=Field_numeric_checkbox_img('BACKUP_ARTICA',$datas["BACKUP_ARTICA"],'{artica_confs_explain}');
	$STOP_SERVICES=Field_numeric_checkbox_img('STOP_SERVICES',$datas["STOP_SERVICES"],'{STOP_SERVICES_BACKUP_EXPLAIN}');
	
	$perform_backup=Paragraphe("64-recycle.png","{backup_now}","{backup_now_text}","javascript:CyrusBackupNow('{$_GET["backup-config"]}');");
	
	
	$form="<table style='width:100%'>
		<tr>
			<td valign='top' class=legend>{backup_container}:</td>
			<td valign='top'>$backup_container</td>
		</tr>
		<tr>
			<td valign='top' class=legend>{max_backup_container}:</td>
			<td valign='top'>$MaxContainer</td>
		</tr>
		<tr>
			<td valign='top' class=legend>{stop_services}:</td>
			<td valign='top'>$STOP_SERVICES</td>
		</tr>		
		<tr>
			<td colspan=2><hr><H3>{what_to_backup}</H3></td></tr>
		<tr>
			<td valign='top' class=legend>{mailboxes}:</td>
			<td valign='top'>$BACKUP_MAILBOXES</td>
		</tr>	
		<tr>
			<td valign='top' class=legend>{databases}:</td>
			<td valign='top'>$BACKUP_DATABASES</td>
		</tr>	
		<tr>
			<td valign='top' class=legend>{artica_confs}:</td>
			<td valign='top'>$BACKUP_ARTICA</td>
		</tr>	
		<tr>
			<td colspan=2 align=right>
				<hr>
			<input type='button' OnClick=\"javascript:SaveBackupCyrusSettings('{$_GET["backup-config"]}');\" value='{apply}&nbsp;&raquo;'>
			</td>
		</tr>							
		</table>
	";
	
	$form=RoundedLightWhite($form);
	$html="<H1>{$_GET["backup-config"]}::{settings}</H1>
	<table style='width:100%'>
	<tr>
		<td valign='top'>$perform_backup</td>
		<td valign='top'>
			<div id='CyrBackDiv'>$form</div>
		</td>
	</tr>
	</table>
	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'cyrus.index.php,artica.backup.index.php,dar.index.php');
	}
	
function backup_now(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?cyrus-backup-now={$_GET["backup-perform-now"]}");
	
}

function backup_delete(){
	$cyr=new cyrusbackup();
	unset($cyr->list[$_GET["cyrus-delete"]]);
	$cyr->save();
}




class cyrusbackup{
	var $list;
	
	function cyrusbackup(){
		$this->load();
		
	}
	
	private function load(){
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$datas=$sock->GET_INFO('CyrusBackupRessource');
		$ini->loadString($datas);
		$this->list=$ini->_params;
	}
	//artica-backup --single-cyrus mount
	public function save(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->list;
		$sock=new sockets();
		$sock->SaveConfigFile($ini->toString(),'CyrusBackupRessource');
		$sock->getFrameWork('RestartDaemon');
		
	}
	
	
}
	
?>