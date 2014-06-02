<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.syslog.inc');
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");

	

if(!CheckRightsSyslog()){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["syslog-engine-tabs"])){syslog_engine_tab();exit;}
if(isset($_GET["rotate-tabs"])){rotate_tab();exit;}
if(isset($_POST["PurgeToNas"])){PurgeToNas();exit;}
if(isset($_POST["BackupToNas"])){BackupToNas();exit;}
if(isset($_POST["BackupSquidLogsUseNas"])){remote_nas_save();exit;}
if(isset($_GET["syslog"])){syslog_tab();exit;}
if(isset($_GET["in-front-ajax"])){js_start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["schedules"])){schedules();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["search-store"])){search_store();exit;}
if(isset($_GET["storage-popup"])){storage_view_popup();exit;}
if(isset($_POST["extract-file"])){storage_view_extract();exit;}

if(isset($_GET["Rotate-js"])){rotate_js();exit;}
if(isset($_GET["Rotate-popup"])){rotate_popup();exit;}
if(isset($_POST["postrotate"])){rotate_save();exit;}
if(isset($_POST["rotate-delete"])){rotate_delete();exit;}
if(isset($_POST["rotate-enable"])){rotate_enable();exit;}
if(isset($_GET["storage"])){storage();exit;}
if(isset($_POST["DELETE-ALL"])){schedules_delete();exit;}
if(isset($_POST["DELETE-STORE"])){storage_remove();exit;}
if(isset($_POST["storage-delete"])){storage_delete();exit;}
if(isset($_GET["storage-view-search"])){storage_view_search();exit;}
if(isset($_POST["delete-extracted"])){storage_view_delete();exit;}
if(isset($_POST["LogRotateCompress"])){settings_save();exit;}
if(isset($_GET["settings-popup"])){settings_popup();exit;}
if(isset($_GET["log-retention-time-js"])){log_retention_time_js();exit;}
if(isset($_GET["remote-nas-js"])){remote_nas_js();exit;}
if(isset($_GET["remote-nas-popup"])){remote_nas_popup();exit;}
if(isset($_GET["backup-to-nas-js"])){backup_to_nas_js();exit;}
if(isset($_GET["purge-nas-js"])){purge_to_nas_js();exit;}



if(isset($_GET["log-js"])){storage_view_js();exit;}
function js_start(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
}
function remote_nas_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$filename=$tpl->javascript_parse_text("{use_remote_nas}");
	$html="YahooWin6('980','$page?remote-nas-popup=yes','$filename')";
	echo $html;
}
function purge_to_nas_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	if($MySQLSyslogType==3){
		$BackupSquidLogsUseNas=1;
	}
	if($BackupSquidLogsUseNas==0){echo "alert('".$tpl->javascript_parse_text("{disabled}")." !!')\n";return ;}
	$filename=$tpl->javascript_parse_text("{use_remote_nas}");
	$backup_to_nas=$tpl->javascript_parse_text("{backup_to_nas}");
	$filename=$_GET["filename"];
	$html="
	var xBackupToNas$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		$('#{$_GET["t"]}').flexReload();
	}	


	function BackupToNas$t(){
		if(!confirm('$backup_to_nas - ALL - ?')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('PurgeToNas','yes');
		XHR.sendAndLoad('$page', 'POST',xBackupToNas$t);
	}			
			
BackupToNas$t();			
";
echo $html;	
	
}

function log_retention_time_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$filename=$tpl->javascript_parse_text("{log_retention}");
	$html="YahooWin6('700','$page?settings-popup=yes','$filename')";
	echo $html;
}
function backup_to_nas_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$backup_to_nas=$tpl->javascript_parse_text("{backup_to_nas}");
	$filename=$_GET["filename"];
	$html="
	var xBackupToNas$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		$('#{$_GET["t"]}').flexReload();
	}	


	function BackupToNas$t(){
		if(!confirm('$backup_to_nas $filename ?')){return;}
		var XHR = new XHRConnection();
	  	XHR.appendData('filename','$filename');
		XHR.appendData('storeid','{$_GET["storeid"]}');
		XHR.appendData('BackupToNas','yes');
		XHR.sendAndLoad('$page', 'POST',xBackupToNas$t);
	}			
			
BackupToNas$t();			
";
echo $html;	
	
}

function CheckRightsSyslog(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator){return true;}
	if($usersmenus->AsSquidAdministrator){return true;}
	if($usersmenus->AsWebStatisticsAdministrator){return true;}
	if($usersmenus->AsDansGuardianAdministrator){return true;}
	return false;
}

function rotate_js(){
	$ID=$_GET["ID"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	$title="{new_rotate}";
	
	if($ID>0){
		$q=new mysql_syslog();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM logrotate WHERE ID=$ID"));
		$title="{schedule}::$ID::{$ligne["RotateFiles"]}";
	}
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin2('724','$page?Rotate-popup=yes&ID=$ID&t={$_GET["t"]}','$title')";
}

function BackupToNas(){
	$filename=$_POST["filename"];
	$storeid=$_POST["storeid"];
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?syslog_to-nas=yes&storeid=$storeid"));
}

function PurgeToNas(){
	$sock=new sockets();
	echo $sock->getFrameWork("system.php?syslog_purge-nas=yes");	
	
}

function rotate_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql_syslog();
	$sock=new sockets();
	$t=$_GET["t"];
	$buttontext="{add}";
	$ID=$_GET["ID"];
		if($ID>0){
			$buttontext="{apply}";
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM logrotate WHERE ID=$ID"));
			$ligne["description"]=utf8_encode($ligne["description"]);
		}
		
	if(!is_numeric($ligne["RotateType"])){$ligne["RotateType"]=0;}
	if(!is_numeric($ID)){$ID=0;}
	//RotateFiles,RotateType,RotateFreq,MaxSize,RotateCount,postrotate,description,enabled		
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}	
	
	if($ligne["RotateFiles"]==null){$ligne["RotateFiles"]="/var/log/*";}

	$RotateFreq["daily"]="{daily}";
	$RotateFreq["weekly"]="{weekly}";
	if(!is_numeric($ligne["MaxSize"])){$ligne["MaxSize"]=$LogsRotateDefaultSizeRotation;}
	if(!is_numeric($ligne["RotateCount"])){$ligne["RotateCount"]=5;}
	
	

	
	$html="
	<div id='div-$t'>
	<table style='width:99%' class='form'>
	<tr>
		<td class=legend style='font-size:14px'>{path}:</td>
		<td>". Field_text("RotateFiles", $ligne["RotateFiles"],"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{description}:</td>
		<td>". Field_text("LogRotateDesc", $ligne["description"],"font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{interval}:</td>
		<td>". Field_array_Hash($RotateFreq,"RotateFreq",$ligne["RotateFreq"],"style:font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{MaxRotation}:</td>
		<td>". Field_text("RotateCount", $ligne["RotateCount"],"font-size:14px;width:60px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{MaxSize}:</td>
		<td style='font-size:14px'>". Field_text("MaxSize", $ligne["MaxSize"],"font-size:14px;width:90px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px' colspan=2>{postrotate}:</td>
	</tr>	
	<tr>
		<td colspan=2>
		
		<textarea id='postrotate' style='font-size:16px;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:120px;overflow:auto'>{$ligne["postrotate"]}</textarea></td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>". button($buttontext,"SaveTaskLogRotate$t()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		
		
	var x_SaveTaskLogRotate$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin2Hide();
		$('#$t').flexReload();
	}	


	function SaveTaskLogRotate$t(){
		var XHR = new XHRConnection();
	  	XHR.appendData('description',document.getElementById('LogRotateDesc').value);
		XHR.appendData('RotateFiles',document.getElementById('RotateFiles').value);
		XHR.appendData('ID','$ID');
		XHR.appendData('postrotate',document.getElementById('postrotate').value);
		XHR.appendData('RotateFreq',document.getElementById('RotateFreq').value);
		XHR.appendData('RotateCount',document.getElementById('RotateCount').value);
		XHR.appendData('MaxSize',document.getElementById('MaxSize').value);
		
	  	AnimateDiv('div-$t');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveTaskLogRotate$t);
	}		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function schedules_delete(){
	$q=new mysql_syslog();
	$q->QUERY_SQL("TRUNCATE TABLE logrotate");
	$q->CheckDefaults();
}

function storage_remove(){
	$q=new mysql_syslog();
	if($q->TABLE_EXISTS("storage")){$q->QUERY_SQL("TRUNCATE TABLE storage");}	
}

function rotate_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_syslog();
	//RotateFiles,RotateType,RotateFreq,MaxSize,RotateCount,postrotate,description,enabled		
	$ID=$_POST["ID"];
	$_POST["description"]=utf8_encode($_POST["description"]);
	$_POST["postrotate"]=mysql_escape_string2($_POST["postrotate"]);
	$_POST["description"]=mysql_escape_string2($_POST["description"]);
	if($ID==0){
		$sql="INSERT IGNORE INTO logrotate (`RotateFiles`,`RotateType`,`RotateFreq`,`MaxSize`,`RotateCount`,`postrotate`,`description`,`enabled`)
		VALUES ('{$_POST["RotateFiles"]}','0','{$_POST["RotateFreq"]}',
		'{$_POST["MaxSize"]}','{$_POST["RotateCount"]}',
		'{$_POST["postrotate"]}',
		'{$_POST["description"]}',1)";
		
	}
	
	if($ID>0){
		$sql="UPDATE logrotate 
			SET RotateFiles='{$_POST["RotateFiles"]}',
			RotateType='0',
			RotateFreq='{$_POST["RotateFreq"]}',
			MaxSize='{$_POST["MaxSize"]}',
			RotateCount='{$_POST["RotateCount"]}',
			postrotate='{$_POST["postrotate"]}',
			description='{$_POST["description"]}'
			WHERE ID=$ID";
	}
	
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("services.php?rotatebuild=yes");	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["syslog"]="{syslog}";
	$array["rotate-tabs"]="{rotate}";
	$array["syslog-engine-tabs"]="{syslog_engine}";
	


	
	$fontsize=18;
	
	while (list ($num, $ligne) = each ($array) ){
		

		if($num=="master"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="localx"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="client"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
		
			
		}
	echo build_artica_tabs($html, "main_logrotate")."<script>LeftDesign('logs-256-white-opac20.png');</script>";
}


function rotate_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["schedules"]="{schedules}";
	$array["storage"]="{storage}";
	$array["events"]="{events}";
	
	
	
	
	$fontsize=18;
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"system.rotate.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="localx"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="client"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
	
			
	}
	echo build_artica_tabs($html, "main_logrotate_tab")."<script>LeftDesign('logs-256-white-opac20.png');</script>";	
	
	
}

function syslog_engine_tab(){
	$array["master"]='{syslog_server}';
	$array["localx"]='localx';
	$array["client"]='{client}';	
	$tpl=new templates();
	$page=CurrentPageName();
	$fontsize=18;
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="artica"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica.events.php?popup=yes&full-size=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="syslog"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.php?popup=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
	
		if($num=="master"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="localx"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="client"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	
			
	}
	echo build_artica_tabs($html, "main_logrotate_syslog_engine")."<script>LeftDesign('logs-256-white-opac20.png');</script>";
	
	
}

function syslog_tab(){
	
	
	$array["syslog"]='{events}';
	$array["artica"]='{artica_events}';
	



	
	
	$tpl=new templates();
	$page=CurrentPageName();
	$fontsize=18;
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="artica"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica.events.php?popup=yes&full-size=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="syslog"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.php?popup=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
	
		if($num=="master"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="localx"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="client"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"syslog.engine.php?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	
			
	}
	echo build_artica_tabs($html, "main_logrotate_syslog")."<script>LeftDesign('logs-256-white-opac20.png');</script>";
	
}

function rotate_delete(){
	$q=new mysql_syslog();
	$sql="DELETE FROM logrotate WHERE ID={$_POST["ID"]}";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?rotatebuild=yes");
}
function rotate_enable(){
	$q=new mysql_syslog();
	$sql="UPDATE logrotate SET `enabled`={$_POST["value"]} WHERE ID={$_POST["ID"]}";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("services.php?rotatebuild=yes");	
}

function settings_save(){
	$sock=new sockets();
	while (list ($index, $line) = each ($_POST) ){
		$sock->SET_INFO($index, $line);
	}
	$sock->getFrameWork("services.php?rotatebuild=yes");
	
}

function storage_delete(){
	$q=new mysql_syslog();
	$sock=new sockets();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SavedInDisk,FileStorePath FROM store WHERE filename = '{$_POST["filename"]}'"));
	if($ligne["SavedInDisk"]==1){
		$array["FileDest"]="$mydir/ressources/logs/$newtFile";
		$sock->getFrameWork("services.php?DeleteFiles=".base64_encode(serialize($array)));
	}	
	
	
	$sql="DELETE FROM store WHERE filename='{$_POST["filename"]}'";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return;}	
	$sock=new sockets();
	
	
}

function settings_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$BackupMaxDaysAccess=$sock->GET_INFO("BackupMaxDaysAccess");
	
	
	
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	
	
	
	
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	if(!is_numeric($BackupMaxDaysAccess)){$BackupMaxDaysAccess=365;}

	
	
	$html="<table style='width:100%' class=form>
		
			
			
	<tr>
		<td class=legend style='font-size:14px'>{delete_if_file_exceed}:</td>
		<td style='font-size:14px'>". Field_text("LogsRotateDeleteSize",$LogsRotateDeleteSize,"font-size:14px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{clean_apache_logs}:</td>
		<td style='font-size:14px'>". Field_text("LogsRotateRemoveApacheMaxSize",$LogsRotateRemoveApacheMaxSize,"font-size:14px;width:60px")."&nbsp;MB</td>
		<td>". help_icon("{LogsRotateRemoveApacheMaxSize_explain}")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:14px'>{default_size_for_rotation}:</td>
		<td style='font-size:14px'>". Field_text("LogsRotateDefaultSizeRotation",$LogsRotateDefaultSizeRotation,"font-size:14px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>						
	<tr>
		<td class=legend style='font-size:14px'>{compress_files}:</td>
		<td>". Field_checkbox("LogRotateCompress", 1,$LogRotateCompress)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{insert_in_mysql}:</td>
		<td>". Field_checkbox("LogRotateMysql", 1,$LogRotateMysql,"LogRotateMysqlCheck()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{storage_files_path}:</td>
		<td>". Field_text("LogRotatePath",$LogRotatePath,"font-size:14px;width:220px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=LogRotatePath')",12)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{system_logs_path}:</td>
		<td>". Field_text("SystemLogsPath",$SystemLogsPath,"font-size:14px;width:220px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=SystemLogsPath')",12)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{max_day_in_database}:</td>
		<td style='font-size:14px;'>". Field_text("BackupMaxDays",$BackupMaxDays,"font-size:14px;width:90px")."&nbsp;{days}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{backup_folder}:</td>
		<td style='font-size:14px;'>". Field_text("BackupMaxDaysDir",$BackupMaxDaysDir,"font-size:14px;width:220px")."</td>
		<td>". button("{browse}..","Loadjs('SambaBrowse.php?no-shares=yes&field=BackupMaxDaysDir')",12)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{max_storage_days_accesses}:</td>
		<td style='font-size:14px;'>". Field_text("BackupMaxDaysAccess",$BackupMaxDaysAccess,"font-size:14px;width:120px")."&nbsp;{days}</td>
		<td>&nbsp;</td>
	</tr>
				
				
				
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}", "SaveRotateOptions()",16)."</td>
	</tr>
	</table>
	
	<script>
	
	var x_SaveSettsLogRotate=function (obj) {
		
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin5Hide();
		
	}	

	function LogRotateMysqlCheck(){
		document.getElementById('LogRotatePath').disabled=false;
		document.getElementById('BackupMaxDays').disabled=true;
		document.getElementById('BackupMaxDaysDir').disabled=true;
		if(document.getElementById('LogRotateMysql').checked){
			document.getElementById('LogRotatePath').disabled=true;
			document.getElementById('BackupMaxDays').disabled=false;
			document.getElementById('BackupMaxDaysDir').disabled=false;			
		}
			
	}
	

	function SaveRotateOptions(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('LogRotateCompress').checked){XHR.appendData('LogRotateCompress',1);}
	  	else{XHR.appendData('LogRotateCompress',0);}
	  	if(document.getElementById('LogRotateMysql').checked){XHR.appendData('LogRotateMysql',1);}
	  	else{XHR.appendData('LogRotateMysql',0);}	  	
	  	XHR.appendData('LogRotatePath',document.getElementById('LogRotatePath').value);
	  	XHR.appendData('LogsRotateRemoveApacheMaxSize',document.getElementById('LogsRotateRemoveApacheMaxSize').value);
	  	
	  	
	  	
	  	XHR.appendData('BackupMaxDaysAccess',document.getElementById('BackupMaxDaysAccess').value);
	  	XHR.appendData('LogsRotateDefaultSizeRotation',document.getElementById('LogsRotateDefaultSizeRotation').value);
	  	XHR.appendData('SystemLogsPath',document.getElementById('SystemLogsPath').value);
	  	XHR.appendData('BackupMaxDays',document.getElementById('BackupMaxDays').value);
	  	XHR.appendData('BackupMaxDaysDir',document.getElementById('BackupMaxDaysDir').value);
	  	XHR.appendData('LogsRotateDeleteSize',document.getElementById('LogsRotateDeleteSize').value);
	  	XHR.sendAndLoad('$page', 'POST',x_SaveSettsLogRotate);	
	}	
	
	
	
	
	LogRotateMysqlCheck();";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function FormatNumberX($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function storage(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$sizeT=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$zdate=$tpl->javascript_parse_text("{date}");
	$action=$tpl->javascript_parse_text("{action}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$parameters=$tpl->javascript_parse_text("{service_parameters}");
	$use_remote_nas=$tpl->javascript_parse_text("{parameters}");
	$purge_to_nas=$tpl->javascript_parse_text("{purge_to_nas}");
	
	
	
	$q=new mysql_storelogs();
	$files=$q->COUNT_ROWS("files_info");
	$size=$q->TABLE_SIZE("files_store");
	$title=$tpl->_ENGINE_parse_body("{files}:".FormatNumberX($files,0)." (".FormatBytes($size/1024).")");
	$t=time();
	
	if($q->MySQLSyslogType==3){
		
		$error=$tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>{syslog_used_nas_storage}</div>");
	}
	
	$html="
	$error
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?search-store=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$zdate', name : 'filetime', width : 158, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 336, sortable : true, align: 'left'},
		{display: '$filename', name : 'filename', width : 336, sortable : true, align: 'left'},
		{display: '$sizeT', name : 'filesize', width : 95, sortable : true, align: 'left'},
		{display: '$task', name : 'taskid', width : 40, sortable : true, align: 'center'},
		{display: '$action', name : 'action', width : 40, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],
buttons : [
	
	{name: '$empty', bclass: 'Delz', onpress : EmptyStorage},
	{name: '$parameters', bclass: 'Settings', onpress : Parameters$t},
	{name: '$use_remote_nas', bclass: 'shared', onpress : Nas$t},
	{name: '$purge_to_nas', bclass: 'backup', onpress : backup$t},
	
	
	
	
		],	
	searchitems : [
		{display: '$filename', name : 'filename'},
		{display: '$task', name : 'taskid'},
		],
	sortname: 'filetime',
	sortorder: 'desc',
	usepager: true,
	title: '<strong>$title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});	

function Parameters$t(){
	Loadjs('MySQLSyslog.wizard.php');
	}
	
function Nas$t(){
	Loadjs('$page?remote-nas-js=yes');
}

function backup$t(){
Loadjs('$page?purge-nas-js=yes');
}
	
	function EmptyStorage(){
		if(confirm('$askdelete')){
	  		var XHR = new XHRConnection();
			XHR.appendData('DELETE-STORE','yes');
	  		XHR.sendAndLoad('$page', 'POST',x_EmptyStorage);		
		}
	}
	
	function SquidCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_RotateTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}

	var x_EmptyStorage=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}		



	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	

	
	
	var x_StorageTaskDelete=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+rowSquidTask).remove();
	}	
	
	function StorageTaskDelete(filename,md5){
		rowSquidTask=md5;
	  	var XHR = new XHRConnection();
		XHR.appendData('filename',filename);
	  	XHR.appendData('storage-delete','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_StorageTaskDelete);	
	}
	
	
	
</script>";
	
	echo $html;
	
}

function schedules(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$target=$tpl->_ENGINE_parse_body("{target}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{delete_all_rules} ?");
	$settings=$tpl->javascript_parse_text("{settings}");
	$t=time();
	$html="
	<div style='margin-left:-15px'>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	</div>
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?search=yes&minisize={$_GET["minisize"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width : 32, sortable : true, align: 'center'},
		{display: '$target', name : 'RotateFiles', width : 326, sortable : false, align: 'left'},
		{display: '$size', name : 'MaxSize', width : 66, sortable : false, align: 'left'},
		{display: 'FREQ', name : 'RotateFreq', width : 79, sortable : false, align: 'left'},
		{display: '$description', name : 'description', width : 318, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enable', width : 32, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],
buttons : [
	{name: '$new_schedule', bclass: 'add', onpress : AddNewRotate},
	{name: '$empty', bclass: 'Delz', onpress : EmptyRules},
	{name: '$settings', bclass: 'Catz', onpress : LogRotateSettings},
	
	
		],	
	searchitems : [
		{display: '$description', name : 'description'},
		{display: '$target', name : 'RotateFiles'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});	

	function AddNewRotate(){
			Loadjs('$page?Rotate-js=yes&ID=0&t=$t');
	}
	
	function LogRotateSettings(){
		YahooWin5('675','$page?settings-popup=yes','$settings');
	}
	
	function EmptyRules(){
		if(confirm('$askdelete')){
	  		var XHR = new XHRConnection();
			XHR.appendData('DELETE-ALL','yes');
	  		XHR.sendAndLoad('$page', 'POST',x_EmptyRules);		
		}
	}
	
	function SquidCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_RotateTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}

	var x_EmptyRules=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}		


	function RotateTaskEnable(md,id){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById(md).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
		XHR.appendData('ID',id);
	  	XHR.appendData('rotate-enable','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_RotateTaskEnable);
	}

	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	

	
	
	var x_RotateTaskDelete=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#rowRotateTask'+rowSquidTask).remove();
	}	
	
	function RotateTaskDelete(ID){
		rowSquidTask=ID;
	  	var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
	  	XHR.appendData('rotate-delete','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_RotateTaskDelete);	
	}
	
	
	
</script>";
	
	echo $html;
	
}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_syslog();
	$search='%';
	$table="logrotate";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	if($q->COUNT_ROWS($table)==0){$q->CheckDefaults();}
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));echo json_encode($data);return;}	
	
//######"
	//TimeText TimeDescription TaskType enabled
	
	
	$q2=new mysql();
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("RotateTask{$ligne['ID']}");
		$jstaskexplain=$tpl->javascript_parse_text($q->tasks_array[$ligne["TaskType"]]);
		$ligne["TaskType"]=$tpl->_ENGINE_parse_body($q->tasks_array[$ligne["TaskType"]]);
		
		
		$enable=Field_checkbox($md5, 1,$ligne["enabled"],"RotateTaskEnable('$md5',{$ligne['ID']})");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","RotateTaskDelete('{$ligne['ID']}')");
		
		
		
		
		if($ligne["enabled"]==0){$color="#A0A0A0";}
		$jsEdit="Loadjs('$MyPage?Rotate-js=yes&ID={$ligne['ID']}&t=$t');";
		$RotateFreq=$tpl->_ENGINE_parse_body("{{$ligne["RotateFreq"]}}");
		$description=utf8_encode($tpl->_ENGINE_parse_body("{$ligne["description"]}"));
		$description=htmlentities($description);
		$span="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsEdit\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";
		
		
		//rowSquidTask
	$data['rows'][] = array(
		'id' => "RotateTask".$ligne['ID'],
		'cell' => array("$span{$ligne['ID']}</a>",
		"$span{$ligne["RotateFiles"]}</a>","$span{$ligne["MaxSize"]}M</a>",
		"$span$RotateFreq</span>",
		$description,
		
		"<div style='margin-top:5px'>$enable</div>",$delete )
		);
	}
	
	
echo json_encode($data);		

}

function storage_view_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$filename="ressources/logs/{$_GET["filename"]}.log";
	if(!is_file($filename)){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));$data['rows'][] = array('id' => $ligne[time()],'cell' => array("ressources/logs/{$_GET["filename"]}.log no such file"));
		echo json_encode($data);return;}	
	
	
	$rp = $_POST['rp'];
	$search=$_POST["query"];
	if($search==null){$cmdline="tail -n $rp $filename 2>&1";}
	if($search<>null){
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*", $search);
		$search=str_replace("[", "\[", $search);
		$search=str_replace("]", "\]", $search);
		$search=str_replace("(", "\(", $search);
		$search=str_replace(")", "\)", $search);
		$cmdline="grep -E \"$search\" $filename|tail -n $rp 2>&1";
	}
	
	exec($cmdline,$datas);
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($datas);
	$data['rows'] = array();	
	$c=0;
	
	if($_POST["sortorder"]=="desc"){krsort($datas);}
	
	while (list ($key, $line) = each ($datas) ){
		if(trim($line)==null){continue;}
		$c++;
		if(preg_match("#FATAL#i", $line)){$line="<span style='color:#680000;font-size:11px'>$line</line>";}
		if(preg_match("#abnormally#i", $line)){$line="<span style='color:#680000;font-size:11px'>$line</line>";}
		if(preg_match("#Reconfiguring#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}
		if(preg_match("#Accepting HTTP#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}
		if(preg_match("#Ready to serve requests#i", $line)){$line="<span style='color:#003D0D;font-weight:bold;font-size:11px'>$line</line>";}
		
		$data['rows'][] = array(
			'id' => md5($line),
			'cell' => array("<span style='font-size:11px'>$line</span>")
		);		
		
	}
	$data['total'] = $c;
		echo json_encode($data);	
}


function search_store(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_storelogs();
	
	if($q->MySQLSyslogType==3){
		json_error_show($tpl->javascript_parse_text("{syslog_used_nas_storage}"),1);
	}
	
	$search='%';
	$table="files_info";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$sock=new sockets();
	$t=$_GET["t"];
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$database="files_store";
	
	
	
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	$results=$q->QUERY_SQL($sql);
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!$q->ok){
		json_error_show($q->mysql_error,1);
	}	
	
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$events="&nbsp;";
		$md5=md5("RotateTask{$ligne['filename']}");
		$span="<span style='font-size:16px'>";
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","StorageTaskDelete('{$ligne['filename']}','$md5')");
		
		$jsEdit="Loadjs('$MyPage?Rotate-js=yes&ID={$ligne['taskid']}&t=$t');";
		$jstask="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsEdit\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";		
		
		$jslloop="Loadjs('$MyPage?log-js=yes&filename={$ligne['filename']}&t=$t&storeid={$ligne["storeid"]}');";
		$view="<a href=\"javascript:blur();\" OnClick=\"javascript:$jslloop\"
		 style='font-size:16px;font-weight:bold;color:$color;text-decoration:underline'>";	
		
		$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
		if($ligne['taskid']==0){$jstask=null;}
		
		$action=null;
		if(preg_match("#auth\.log-.*?#", $ligne["filename"])){
			$action=imgsimple("service-restart-32.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}')");
			
		}
		
		if($BackupSquidLogsUseNas==1){
			$action=imgsimple("backup-tool-32.png",null,"Loadjs('$MyPage?backup-to-nas-js=yes&filename={$ligne['filename']}&t=$t&storeid={$ligne["storeid"]}')");
		}
		
		
		//rowSquidTask
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("$span{$ligne['filetime']}</a></span>",
		"$span$view{$ligne["hostname"]}</a></span>",
		"$span$view{$ligne["filename"]}</a></span>",
		"$span{$ligne["filesize"]}</a></span>",
		"$span$jstask{$ligne["taskid"]}</a></span>",$action,
		$delete )
		);
	}
	
	
echo json_encode($data);		

}

function storage_view_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$filename=$_GET["filename"];
	$html="YahooWin5('1060','$page?storage-popup=yes&storeid={$_GET["storeid"]}&filename=$filename','$filename')";
	echo $html;
}
function storage_view_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$extract=$tpl->_ENGINE_parse_body("{extract}");
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$target=$tpl->_ENGINE_parse_body("{target}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$askdelete=$tpl->javascript_parse_text("{delete} {$_GET["filename"]}.log ?");
	
	$t=time();
	$html="
	<div style='margin-left:-5px'>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	</div>
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?storage-view-search=yes&filename={$_GET["filename"]}&t=$t&storeid={$_GET["storeid"]}',
	dataType: 'json',
	colModel : [
		{display: '$rows', name : 'rows', width : 1018, sortable : true, align: 'left'},

	],
buttons : [
	{name: '$extract', bclass: 'add', onpress : ExtractFile},
	{name: '$delete', bclass: 'Delz', onpress : DeleteExtractedFile},
	
		],	
	searchitems : [
		{display: '$rows', name : 'rows'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	rpOptions: [10, 20, 30, 50,100,200,500,1500],
	showTableToggleBtn: false,
	width: 1050,
	height: 400,
	singleSelect: true
	
	});   
});	

	
	function DeleteExtractedFile(){
		if(confirm('$askdelete')){
	  		var XHR = new XHRConnection();
			XHR.appendData('delete-extracted','{$_GET["filename"]}');
	  		XHR.sendAndLoad('$page', 'POST',x_ExtractFile);		
		}
	}
	
	function SquidCrontaskUpdateTable(){
		$('#$t').flexReload();
	 }
	
	var x_RotateTaskEnable=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
	}

	var x_ExtractFile=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#$t').flexReload();		
	}		


	function ExtractFile(){
	if(confirm('$extract {$_GET["filename"]} ?')){
	  	var XHR = new XHRConnection();
	  	XHR.appendData('extract-file','{$_GET["filename"]}');
	  	XHR.appendData('storeid','{$_GET["storeid"]}');
	  	XHR.sendAndLoad('$page', 'POST',x_ExtractFile);
	  	}
	}

	function DisableSquidDefaultScheduleCheck(){
	  	var XHR = new XHRConnection();
	  	if(document.getElementById('DisableSquidDefaultSchedule').checked){XHR.appendData('DisableSquidDefaultSchedule',1);}
	  	else{XHR.appendData('DisableSquidDefaultSchedule',0);}
	  	XHR.sendAndLoad('$page', 'POST',x_DisableSquidDefaultScheduleCheck);	
	}
	

	
	
	var x_RotateTaskDelete=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#rowRotateTask'+rowSquidTask).remove();
	}	
	
	function RotateTaskDelete(ID){
		rowSquidTask=ID;
	  	var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
	  	XHR.appendData('rotate-delete','yes');
	  	XHR.sendAndLoad('$page', 'POST',x_RotateTaskDelete);	
	}
	
	
	
</script>";
	
	echo $html;
	
}
function storage_view_delete(){
	$mydir=dirname(__FILE__);
	$newtFile=$_POST["delete-extracted"];	
	@unlink("$mydir/ressources/logs/$newtFile.log");
}


function storage_view_extract(){
	@chmod("ressources/logs",0777);
	$q=new mysql_syslog();
	$mydir=dirname(__FILE__);
	$newtFile=$_POST["extract-file"];
	$sock=new sockets();
	@unlink("$mydir/ressources/logs/$newtFile");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	
	if($EnableSyslogDB==0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SavedInDisk,FileStorePath FROM store WHERE filename = '$newtFile'"));
		writelogs("SavedInDisk = {$ligne["SavedInDisk"]}",__FUNCTION__,__FILE__,__LINE__);
	
		if($ligne["SavedInDisk"]==1){
			$array["FROM"]=$ligne["FileStorePath"];
			$array["TO"]="$mydir/ressources/logs/$newtFile";
			$sock->getFrameWork("services.php?copyFiles=".base64_encode(serialize($array)));
			return;
		}
	
		$sql="SELECT filedata INTO DUMPFILE '$mydir/ressources/logs/$newtFile' FROM access_store WHERE ID = '$newtFile'";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
	
	
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			echo $q->mysql_error;return;
		}
		
	}else{
		$q=new mysql_storelogs();
		$sql="SELECT filecontent INTO DUMPFILE '$mydir/ressources/logs/$newtFile' FROM files_store WHERE ID = '{$_POST["storeid"]}'";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		
	}
	
	$ext=file_extension($newtFile);
	writelogs("$mydir/ressources/logs/$newtFile -> ".@filesize("$mydir/ressources/logs/$newtFile")." bytes...",__FUNCTION__,__FILE__,__LINE__);
	$cmdline="cp -f $mydir/ressources/logs/$newtFile $mydir/ressources/logs/$newtFile.log";
	
	
	
	if($ext=="bz2"){
		$cmdline="bzip2 -d \"$mydir/ressources/logs/$newtFile\" -c >\"$mydir/ressources/logs/$newtFile.log\" 2>&1";
		exec($cmdline,$results);
	}
	if($ext=="gz"){
		$cmdline="gunzip -d \"$mydir/ressources/logs/$newtFile\" -c >\"$mydir/ressources/logs/$newtFile.log\"";
	}
	if($cmdline<>null){
		writelogs("$cmdline",__FUNCTION__,__FILE__,__LINE__);
		exec($cmdline,$results);
		while (list ($key, $line) = each ($results) ){
			writelogs("$line",__FUNCTION__,__FILE__,__LINE__);		
		}
	}
	
	@unlink("$mydir/ressources/logs/$newtFile");
	writelogs(@filesize("$mydir/ressources/logs/$newtFile.log")." bytes...",__FUNCTION__,__FILE__,__LINE__);
	
}


function remote_nas_popup(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$LogRotateCompress=$sock->GET_INFO("LogRotateCompress");
	$LogRotateMysql=$sock->GET_INFO("LogRotateMysql");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$SystemLogsPath=$sock->GET_INFO("SystemLogsPath");
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$LogsRotateDeleteSize=$sock->GET_INFO("LogsRotateDeleteSize");
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	
	if($SystemLogsPath==null){$SystemLogsPath="/var/log";}
	if(!is_numeric($LogRotateCompress)){$LogRotateCompress=1;}
	if(!is_numeric($LogRotateMysql)){$LogRotateMysql=1;}
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
	
	
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_numeric($LogsRotateDeleteSize)){$LogsRotateDeleteSize=5000;}
	

	
	
$html="<div class=explain style='font-size:14px'>{MYSQLSYSLOG_TYPE_NAS_EXPLAIN}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{delete_if_file_exceed}:</strong></td>
			<td align='left' style='font-size:18px'>" . Field_text("LogsRotateDeleteSize-$tt",$LogsRotateDeleteSize,'width:90px;padding:3px;font-size:18px',null,null,'')."&nbsp;MB</td>
			<td>&nbsp;</td>
		</tr>		
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{default_size_for_rotation}:</strong></td>
			<td align='left' style='font-size:18px'>" . Field_text("LogsRotateDefaultSizeRotation-$tt",$LogsRotateDefaultSizeRotation,'width:90px;padding:3px;font-size:18px',null,null,'')."&nbsp;MB</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{system_logs_path}:</strong></td>
			<td align='left' style='font-size:18px'>" . Field_text("SystemLogsPath-$tt",$SystemLogsPath,'width:200px;padding:3px;font-size:18px',null,null,'')."</td>
			<td width=1% nowrap>". button_browse("SystemLogsPath-$tt")."</td>
		</tr>	
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{max_day_in_database}:</strong></td>
			<td align='left' style='font-size:18px'>" . Field_text("BackupMaxDays-$tt",$BackupMaxDays,'width:90px;padding:3px;font-size:18px',null,null,'')."&nbsp;{days}</td>
			<td width=1% nowrap>". help_icon("{BackupMaxDaysDir_explain}")."</td>
		</tr>	
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{backup_folder}:</strong></td>
			<td align='left' style='font-size:18px'>" . Field_text("BackupMaxDaysDir-$tt",$BackupMaxDaysDir,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
			<td width=1% nowrap>". button_browse("BackupMaxDaysDir-$tt")."</td>
		</tr>	

<tr><td colspan=3><div style='font-size:24px;margin-top:20px'>{NAS_storage}</div></td></tr>				
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{use_remote_nas}:</strong></td>
			<td align='left'>" . Field_checkbox("BackupSquidLogsUseNas-$tt",1,$BackupSquidLogsUseNas)."</td>
			<td>". help_icon("{BackupSquidLogsUseNas_explain}")."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{hostname}:</strong></td>
			<td align='left'>" . Field_text("BackupSquidLogsNASIpaddr-$tt",$BackupSquidLogsNASIpaddr,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
			
			<td>&nbsp;</td>
		</tr>					

		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{shared_folder}:</strong></td>
			<td align='left'>" . Field_text("BackupSquidLogsNASFolder-$tt",$BackupSquidLogsNASFolder,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{username}:</strong></td>
			<td align='left'>" . Field_text("BackupSquidLogsNASUser-$tt",$BackupSquidLogsNASUser,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{password}:</strong></td>
			<td align='left'>" . Field_password("BackupSquidLogsNASPassword-$tt",$BackupSquidLogsNASPassword,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
			<td>&nbsp;</td>
		</tr>
			<tr><td colspan=3 align='right'><hr>". button("{apply}", "Next$tt()",24)."</td></tr>
			<tr><td colspan=3 align='right'><hr>". button("{test_connection}", "Loadjs('miniadm.system.syslogstore.php?test-nas-js=yes')",16)."</td></tr>
	
	
	</table>
</div>
<script>
var xNext$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	LoadAjax('$t','$page?Next3=yes&t=$t');
	}
	
	function Next$tt(){
	var XHR = new XHRConnection();
	
	XHR.appendData('LogsRotateDeleteSize',document.getElementById('LogsRotateDeleteSize-$tt').value);
	XHR.appendData('LogsRotateDefaultSizeRotation',document.getElementById('LogsRotateDefaultSizeRotation-$tt').value);
	XHR.appendData('SystemLogsPath',encodeURIComponent(document.getElementById('SystemLogsPath-$tt').value));
	XHR.appendData('BackupMaxDays',document.getElementById('BackupMaxDays-$tt').value);
	XHR.appendData('BackupMaxDaysDir',encodeURIComponent(document.getElementById('BackupMaxDaysDir-$tt').value));
	if(document.getElementById('BackupSquidLogsUseNas-$tt').checked){
		XHR.appendData('BackupSquidLogsUseNas',1);
	}else{
		XHR.appendData('BackupSquidLogsUseNas',0);
	}
	
	
	
	XHR.appendData('BackupSquidLogsNASIpaddr',document.getElementById('BackupSquidLogsNASIpaddr-$tt').value);
	XHR.appendData('BackupSquidLogsNASFolder',encodeURIComponent(document.getElementById('BackupSquidLogsNASFolder-$tt').value));
	XHR.appendData('BackupSquidLogsNASUser',document.getElementById('BackupSquidLogsNASUser-$tt').value);
	XHR.appendData('BackupSquidLogsNASPassword',encodeURIComponent(document.getElementById('BackupSquidLogsNASPassword-$tt').value));
	XHR.sendAndLoad('$page', 'POST',xNext$tt);
	}
	</script>";
		echo $tpl->_ENGINE_parse_body($html);
	}


	
function remote_nas_save(){
	$sock=new sockets();
	
	
	
	
	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupMaxDaysDir"])){$_POST["BackupMaxDaysDir"]=url_decode_special_tool($_POST["BackupMaxDaysDir"]);}
	if(isset($_POST["BackupSquidLogsNASFolder"])){$_POST["BackupSquidLogsNASFolder"]=url_decode_special_tool($_POST["BackupSquidLogsNASFolder"]);}
	if(isset($_POST["SystemLogsPath"])){$_POST["SystemLogsPath"]=url_decode_special_tool($_POST["SystemLogsPath"]);}
	if(isset($_POST["BackupSquidLogsNASPassword"])){$_POST["BackupSquidLogsNASPassword"]=url_decode_special_tool($_POST["BackupSquidLogsNASPassword"]);}
	
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	
}
	
function file_extension($filename){return pathinfo($filename, PATHINFO_EXTENSION);}