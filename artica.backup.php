<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}
if(isset($_GET["restore-follow-js"])){restore_follow_js();exit;}
if(isset($_GET["restore-status"])){restore_status_js();exit;}

if(isset($_GET["help"])){help();exit;}
if(isset($_GET["backup"])){backup();exit;}
if(isset($_GET["restore"])){restore();exit;}
if(isset($_POST["BackupArticaBackNASIpaddr"])){backup_save();exit;}
if(isset($_POST["BackupArticaRestoreNASFolderSource"])){restore_save();exit;}
tabs();


function tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=22;
	
	$array["snapshots"]="{snapshots}";
	$array["backup"]="{backup}";
	$array["schedule"]="{schedule}";
	$array["restore"]="{restore}";
	//$array["InstantLDAPBackup"]="Instant LDAP Restore";
	//$array["help"]="{help}";

	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=75\">
					<span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="snapshots"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"snapshots.php\">
					<span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
					continue;
		}

		if($num=="exclude-www"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.wwwex.php\">
					<span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
					continue;
		}

		if($num=="rules"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" ><span style='font-size:$fontsize;font-weight:normal'>$ligne</span></a></li>\n");
				continue;

				}
				
		if($num=="InstantLDAPBackup"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"artica.instantLdapRestore.php\"><span style='font-size:$fontsize;font-weight:normal'>$ligne</span></a></li>\n");
			continue;
		}				

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\"><span style='font-size:$fontsize;font-weight:normal'>$ligne</span></a></li>\n");
	}



	$html=build_artica_tabs($html,'artica_backup_tabs',1200)."<script>LeftDesign('backup-256-opac20.png');</script>";

	echo $html;


}

function help(){
	
	echo "
			<center style='width:98%' class=form><iframe width=\"853\" height=\"480\" 
			src=\"//www.youtube.com/embed/_vIjzIEOGtY\" frameborder=\"0\" allowfullscreen></iframe></center>";
	
}

function backup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$tt=time();
	$BackupArticaBackUseNas=intval($sock->GET_INFO("BackupArticaBackUseNas"));
	$BackupArticaBackLocalFolder=intval($sock->GET_INFO("BackupArticaBackLocalFolder"));
	$BackupArticaBackAllDB=intval($sock->GET_INFO("BackupArticaBackAllDB"));
	
	$BackupArticaBackNASIpaddr=$sock->GET_INFO("BackupArticaBackNASIpaddr");
	$BackupArticaBackNASFolder=$sock->GET_INFO("BackupArticaBackNASFolder");
	$BackupArticaBackNASUser=$sock->GET_INFO("BackupArticaBackNASUser");
	$BackupArticaBackCategory=$sock->GET_INFO("BackupArticaBackCategory");
	$BackupArticaBackNASPassword=$sock->GET_INFO("BackupArticaBackNASPassword");
	$BackupArticaBackLocalDir=$sock->GET_INFO("BackupArticaBackLocalDir");
	
	if($BackupArticaBackLocalDir==null){$BackupArticaBackLocalDir="/home/artica/backup";}
	
	if(!is_numeric($BackupArticaBackCategory)){$BackupArticaBackCategory=1;}
	$users=new usersMenus();
	if($users->SQUID_INSTALLED){
		$catback="<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{backup_categories}:</strong></td>
			<td>". Field_checkbox("BackupArticaBackCategory-$tt", 1,$BackupArticaBackCategory)."</td>
		</tr>";
		
	}
	
	
	
	$html="<div class=text-info style='font-size:16px'>{BACKUPARTICA_TYPE_NAS_EXPLAIN}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18'>{backup_all_databases}:</td>
		<td>". Field_checkbox("BackupArticaBackAllDB-$tt", 1,$BackupArticaBackAllDB,"Check$tt()")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:18'>{use_remote_nas}:</td>
		<td>". Field_checkbox("BackupArticaBackUseNas-$tt", 1,$BackupArticaBackUseNas,"Check$tt()")."</td>
	</tr>	
	$catback		
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{hostname}:</strong></td>
			<td align='left'>" . Field_text("BackupArticaBackNASIpaddr-$tt",$BackupArticaBackNASIpaddr,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{shared_folder}:</strong></td>
			<td align='left'>" . Field_text("BackupArticaBackNASFolder-$tt",$BackupArticaBackNASFolder,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{username}:</strong></td>
			<td align='left'>" . Field_text("BackupArticaBackNASUser-$tt",$BackupArticaBackNASUser,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{password}:</strong></td>
			<td align='left'>" . Field_password("BackupArticaBackNASPassword-$tt",$BackupArticaBackNASPassword,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18'>{use_local_directory}:</td>
			<td>". Field_checkbox("BackupArticaBackLocalFolder-$tt", 1,$BackupArticaBackLocalFolder,"Check2$tt()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18'>{local_directory}:</td>
			<td>". Field_text("BackupArticaBackLocalDir-$tt", $BackupArticaBackLocalDir,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>										
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td>
		</tr>
		</table>
	</div>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('artica_backup_tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('BackupArticaBackUseNas-$tt').checked){ XHR.appendData('BackupArticaBackUseNas',1); }else{ XHR.appendData('BackupArticaBackUseNas',0);}
	if(document.getElementById('BackupArticaBackLocalFolder-$tt').checked){ XHR.appendData('BackupArticaBackLocalFolder',1); }else{ XHR.appendData('BackupArticaBackLocalFolder',0);}
	if(document.getElementById('BackupArticaBackAllDB-$tt').checked){ XHR.appendData('BackupArticaBackAllDB',1); }else{ XHR.appendData('BackupArticaBackAllDB',0);}
	
	
	
	XHR.appendData('BackupArticaBackNASIpaddr',document.getElementById('BackupArticaBackNASIpaddr-$tt').value);
	XHR.appendData('BackupArticaBackNASFolder',encodeURIComponent(document.getElementById('BackupArticaBackNASFolder-$tt').value));
	XHR.appendData('BackupArticaBackNASUser',encodeURIComponent(document.getElementById('BackupArticaBackNASUser-$tt').value));
	XHR.appendData('BackupArticaBackNASPassword',encodeURIComponent(document.getElementById('BackupArticaBackNASPassword-$tt').value));
	XHR.appendData('BackupArticaBackLocalDir',encodeURIComponent(document.getElementById('BackupArticaBackLocalDir-$tt').value));
	
	if(document.getElementById('BackupArticaBackCategory-$tt')){
		if(document.getElementById('BackupArticaBackCategory-$tt').checked){ XHR.appendData('BackupArticaBackCategory',1); }else{ XHR.appendData('BackupArticaBackCategory',0);}
	}
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	
}

function Check2$tt(){
	document.getElementById('BackupArticaBackLocalDir-$tt').disabled=true;
	if(document.getElementById('BackupArticaBackLocalFolder-$tt').checked){
		document.getElementById('BackupArticaBackUseNas-$tt').disabled=true;
		document.getElementById('BackupArticaBackLocalDir-$tt').disabled=false;
		Check$tt();
		if(document.getElementById('BackupArticaBackUseNas-$tt').checked){
			document.getElementById('BackupArticaBackUseNas-$tt').checked=false;
		}
	}else{
		document.getElementById('BackupArticaBackUseNas-$tt').disabled=false;
	}
	
	CheckBoxDesignHidden();

}

function Check$tt(){
	document.getElementById('BackupArticaBackNASIpaddr-$tt').disabled=true;
	document.getElementById('BackupArticaBackNASFolder-$tt').disabled=true;
	document.getElementById('BackupArticaBackNASUser-$tt').disabled=true;
	document.getElementById('BackupArticaBackNASPassword-$tt').disabled=true;
	
	document.getElementById('BackupArticaBackLocalFolder-$tt').disabled=false;
	
	
	
	if(document.getElementById('BackupArticaBackCategory-$tt')){
		document.getElementById('BackupArticaBackCategory-$tt').disabled=true;
	}
	
	
	
	
	if(document.getElementById('BackupArticaBackUseNas-$tt').checked){
		if(document.getElementById('BackupArticaBackLocalFolder-$tt').checked){
			document.getElementById('BackupArticaBackLocalFolder-$tt').checked=false;
			document.getElementById('BackupArticaBackLocalFolder-$tt').disabled=true;
		}
		document.getElementById('BackupArticaBackLocalDir-$tt').disabled=true;
		document.getElementById('BackupArticaBackNASIpaddr-$tt').disabled=false;
		document.getElementById('BackupArticaBackNASFolder-$tt').disabled=false;
		document.getElementById('BackupArticaBackNASUser-$tt').disabled=false;
		document.getElementById('BackupArticaBackNASPassword-$tt').disabled=false;
		if(document.getElementById('BackupArticaBackCategory-$tt')){
			document.getElementById('BackupArticaBackCategory-$tt').disabled=false;
		}
	}
	
	CheckBoxDesignHidden();
}


Check2$tt();
Check$tt();


</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function backup_save(){
	$sock=new sockets();
	$sock->SET_INFO("BackupArticaBackUseNas",$_POST["BackupArticaBackUseNas"]);
	$sock->SET_INFO("BackupArticaBackNASIpaddr",$_POST["BackupArticaBackNASIpaddr"]);
	$sock->SET_INFO("BackupArticaBackAllDB",$_POST["BackupArticaBackAllDB"]);
	
	
	
	$sock->SET_INFO("BackupArticaBackNASFolder",url_decode_special_tool($_POST["BackupArticaBackNASFolder"]));
	$sock->SET_INFO("BackupArticaBackNASUser",url_decode_special_tool($_POST["BackupArticaBackNASUser"]));
	$sock->SET_INFO("BackupArticaBackNASPassword",url_decode_special_tool($_POST["BackupArticaBackNASPassword"]));
	
	$sock->SET_INFO("BackupArticaBackLocalFolder",url_decode_special_tool($_POST["BackupArticaBackLocalFolder"]));
	$sock->SET_INFO("BackupArticaBackLocalDir",url_decode_special_tool($_POST["BackupArticaBackLocalDir"]));
	
	if(isset($_POST["BackupArticaBackCategory"])){
		$sock->SET_INFO("BackupArticaBackCategory",$_POST["BackupArticaBackCategory"]);
	}
	
	
}

function restore(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$tt=time();
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");
	$please_wait=$tpl->javascript_parse_text("{please_wait}");
	
	
	$html="
	<input type='hidden' id='progressVal$t' value='1'>
	<center id='title$t' style='font-size:28px'></center>
	<div id='progress$t' style='width:95%;margin-top:15px'></div>
		
	<div class=text-info style='font-size:16px'>{RESTOREARTICA_TYPE_NAS_EXPLAIN}</div>
			
			
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{restore_network_settings}", "{restore_network_settings_explain}",
				"BackupArticaRestoreNetwork-$tt",$BackupArticaRestoreNetwork,null,550)."</td>
	<tr>
		<td align='right' nowrap class=legend style='font-size:18px'>{hostname}:</strong></td>
		<td align='left'>" . Field_text("BackupArticaRestoreNASIpaddr-$tt",$BackupArticaRestoreNASIpaddr,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:18px'>{shared_folder}:</strong></td>
		<td align='left'>" . Field_text("BackupArticaRestoreNASFolder-$tt",$BackupArticaRestoreNASFolder,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:18px'>{username}:</strong></td>
		<td align='left'>" . Field_text("BackupArticaRestoreNASUser-$tt",$BackupArticaRestoreNASUser,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:18px'>{password}:</strong></td>
		<td align='left'>" . Field_password("BackupArticaRestoreNASPassword-$tt",$BackupArticaRestoreNASPassword,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
	</tr>
	<tr>
		<td align='right' nowrap class=legend style='font-size:18px'>{backup_folder}:</strong></td>
		<td align='left'>" . Field_text("BackupArticaRestoreNASFolderSource-$tt",$BackupArticaRestoreNASFolderSource,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td>
	</tr>
	<tr>
		<td colspan=2 align=center>". button("{restore}","Restore$t()",48)."</td>
	<tr>
	
	</table>
	</div>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	RefreshTab('artica_backup_tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BackupArticaRestoreNASIpaddr',document.getElementById('BackupArticaRestoreNASIpaddr-$tt').value);
	XHR.appendData('BackupArticaRestoreNASFolder',encodeURIComponent(document.getElementById('BackupArticaRestoreNASFolder-$tt').value));
	XHR.appendData('BackupArticaRestoreNASUser',encodeURIComponent(document.getElementById('BackupArticaRestoreNASUser-$tt').value));
	XHR.appendData('BackupArticaRestoreNASPassword',encodeURIComponent(document.getElementById('BackupArticaRestoreNASPassword-$tt').value));
	XHR.appendData('BackupArticaRestoreNASFolderSource',encodeURIComponent(document.getElementById('BackupArticaRestoreNASFolderSource-$tt').value));
	XHR.appendData('BackupArticaRestoreNetwork',document.getElementById('BackupArticaRestoreNetwork-$tt').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	
}
var xSaveR$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	document.getElementById('title$t').innerHTML='$please_wait';
	$('#progress$t').progressbar({ value: 5 });
	Loadjs('$page?restore-follow-js=yes&t=$t');
}

function Restore$t(){
	var XHR = new XHRConnection();
	XHR.appendData('BackupArticaRestoreNASIpaddr',document.getElementById('BackupArticaRestoreNASIpaddr-$tt').value);
	XHR.appendData('BackupArticaRestoreNASFolder',encodeURIComponent(document.getElementById('BackupArticaRestoreNASFolder-$tt').value));
	XHR.appendData('BackupArticaRestoreNASUser',encodeURIComponent(document.getElementById('BackupArticaRestoreNASUser-$tt').value));
	XHR.appendData('BackupArticaRestoreNASPassword',encodeURIComponent(document.getElementById('BackupArticaRestoreNASPassword-$tt').value));
	XHR.appendData('BackupArticaRestoreNASFolderSource',encodeURIComponent(document.getElementById('BackupArticaRestoreNASFolderSource-$tt').value));
	XHR.appendData('BackupArticaRestoreNetwork',document.getElementById('BackupArticaRestoreNetwork-$tt').value);
	XHR.sendAndLoad('$page', 'POST',xSaveR$t);

}

</script>";

echo $tpl->_ENGINE_parse_body($html);	
	
	
	
	
}

function restore_status_js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$time=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress"));
	if(!is_array($array)){
		echo "
		function Restore$time(){
			if( document.getElementById('progressVal$t')){
				Loadjs('$page?restore-status=yes&t=$t');
			}
		}
		setTimeout('Restore$time()',1500);";
		return;
		
	}
	
	$PURC=$array["POURC"];
	$TEXT=$tpl->javascript_parse_text($array["TEXT"]);
	if(!is_numeric($PURC)){$PURC=5;}
	
echo "
	function Restore$time(){
		var purc='{$PURC}';
		if( !document.getElementById('progressVal$t')){return;}
		$('#progress$t').progressbar({ value: {$PURC} });
		document.getElementById('title$t').innerHTML='$TEXT';
		if(purc<100){ Loadjs('$page?restore-status=yes&t=$t'); }
		
	}
	setTimeout('Restore$time()',1500);";
	return;	
}

function restore_follow_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("system.php?backup-restore-new=yes");
	$t=$_GET["t"];
	echo "
	$('#progress$t').progressbar({ value: 5 });
	Loadjs('$page?restore-status=yes&t=$t');";
	
}

function restore_save(){
	$sock=new sockets();
	$sock->SET_INFO("BackupArticaRestoreNASIpaddr", $_POST["BackupArticaRestoreNASIpaddr"]);
	$sock->SET_INFO("BackupArticaRestoreNetwork", $_POST["BackupArticaRestoreNetwork"]);
	$sock->SET_INFO("BackupArticaRestoreNASFolder", url_decode_special_tool($_POST["BackupArticaRestoreNASFolder"]));
	$sock->SET_INFO("BackupArticaRestoreNASUser", url_decode_special_tool($_POST["BackupArticaRestoreNASUser"]));
	$sock->SET_INFO("BackupArticaRestoreNASPassword", url_decode_special_tool($_POST["BackupArticaRestoreNASPassword"]));
	$sock->SET_INFO("BackupArticaRestoreNASFolderSource", url_decode_special_tool($_POST["BackupArticaRestoreNASFolderSource"]));
	
	
}

