<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$users=new usersMenus();
	if(!$users->AsOrgAdmin){
			$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
			echo "alert('$error')";
			die();
		}
		
	if(isset($_POST["imapr_server"])){as_gateway_popup_save();exit;}
	if(isset($_GET["status"])){status();exit;}	
	if(isset($_GET["about"])){about();exit;}
	if(isset($_GET["popup"])){users_list();exit;}
	if(isset($_GET["search-list"])){users_list_item();exit;}
	
	if(isset($_GET["import"])){import();exit;}
	if(isset($_GET["tasks-list"])){task_list();exit;}
	if(isset($_GET["IMPORTATION_FILE_PATH"])){MIGRATION_CREATE_USERS();exit;}
	if(isset($_GET["RELAUNCH_TASKS"])){MIGRATION_RELAUNCH_TASKS();exit;}
	if(isset($_GET["DELETE_TASK"])){MIGRATION_DELETE_TASK();exit;}
	if(isset($_GET["RELOAD_MEMBERS"])){MIGRATION_RELAUNCH_MEMBERS();exit;}
	
	if(isset($_GET["users"])){USERS_POPUP();exit;}
	if(isset($_GET["users-list"])){USERS_POPUP_LIST();exit;}
	if(isset($_GET["users-events"])){USERS_EVENTS();exit;}
	if(isset($_GET["RESTART_MEMBERS"])){MIGRATION_RESTART_MEMBERS();exit;}
	
	if(isset($_POST["enable"])){schedule_enable();exit;}
	if(isset($_POST["DeleteRule"])){delete_user_rule();exit;}
	if(isset($_POST["ExecRule"])){ExecRule();exit;}
	if(isset($_GET["item-events"])){item_events();exit;}
	
	if(isset($_GET["item-edit-js"])){item_edit_js();exit;}
	if(isset($_GET["item-edit-popup"])){item_edit_popup();exit;}
	if(isset($_POST["cert_fingerprint"])){item_edit_save();exit;}
	if(isset($_GET["source-folders"])){item_edit_source_folders();exit;}
	if(isset($_POST["folder-active"])){item_edit_source_folders_save();exit;}
	
	if(isset($_GET["as-gateway"])){as_gateway_popup();exit;}
	
js();




function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{MAILBOXES_MIGRATION}");
	$html="
		YahooWin3(780,'$page?popup=yes&ou={$_GET["ou"]}','$title');
		
		function MigrShowLogs(MD){
			YahooWin4(550,'$page?users-events='+MD+'&ou={$_GET["ou"]}',MD);
		}
	";
	
	echo $html;
	}
	
function item_edit_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{MAILBOXES_MIGRATION}::{new_rule}");	
	$md5=$_GET["item-edit-js"];
	if($md5<>null){
		$sql="SELECT * FROM mbx_migr_users WHERE zmd5='$md5'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$title=$tpl->_ENGINE_parse_body("{MAILBOXES_MIGRATION}&raquo;{$ligne["username"]}@{$ligne["imap_server"]}");
	}	
	
	echo "YahooWin4(650,'$page?item-edit-popup=yes&md5=$md5&t={$_GET["t"]}','$title');";
}

function item_edit_source_folders_save(){
	$md5=$_POST["md5"];
	$folder=base64_decode($_POST["folder-active"]);
	$sql="SELECT mbxfolders FROM mbx_migr_users WHERE `zmd5`='$md5'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
	$Folders=unserialize(base64_decode($ligne["mbxfolders"]));	
	if($_POST["f-enable"]==0){
		unset($Folders["FoldersSelectedSourceServer"][$folder]);
	}else{
		$Folders["FoldersSelectedSourceServer"][$folder]=1;
	}
	
	$final=base64_encode(serialize($Folders));
	$q=new mysql();
	$sql="UPDATE `mbx_migr_users` SET `mbxfolders`='$final' WHERE `zmd5`='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}	
}


function item_edit_source_folders(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$md5=$_GET["source-folders"];
	$sql="SELECT mbxfolders FROM mbx_migr_users WHERE zmd5='$md5'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$Folders=unserialize(base64_decode($ligne["mbxfolders"]));
	$Array=$Folders["SourceServer"];
	$ArraySelected=$Folders["FoldersSelectedSourceServer"];
	$t=time();
	$html="<div style='font-size:14px' class=explain>{explain_sourcefolder_offlineimap}</div>
	<div style='height:450px;width:100%;overflow:auto'>
	<table style='width:99%' class=form>";
	
	while (list ($folder, $ligne) = each ($Array) ){
		
		$folder_enc=base64_encode($folder);
		$folderid=md5($folder);
		
		$field=Field_checkbox($folderid, 1,$ArraySelected[$folder],"EnableFolderid$t('$folderid','$folder_enc')");
		//$js[]="if(document.getElementById('$folderid').checked){XHR.appendData('folder','$folder_enc');XHR.appendData('foldera-".count($js)."','1');}else{XHR.appendData('folder-".count($js)."','$folder_enc');XHR.appendData('foldera-".count($js)."','0');}";
		$folderFieldName=str_replace("'", "`", $folder);
		$folderFieldName=$tpl->javascript_parse_text($folderFieldName);
		$html=$html."
		<tr>
			<td style='font-size:13px;border-bottom:1px dotted #CCCCCC' width=1%><span id='$folderid-img'></span></td>
			<td style='font-size:13px;border-bottom:1px dotted #CCCCCC' width=100%>$folderFieldName</td>
			<td style='font-size:13px;border-bottom:1px dotted #CCCCCC' width=1%>$field</td>
		</tr>
		";
	}
	$html=$html."
			</table>
		</div>
		
	<script>
	var mem$t='';
	var x_EnableFolderid$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById(mem$t+'-img').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue);return;};
	}		
	
	function EnableFolderid$t(id,fold){
		mem$t=id;
		document.getElementById(id+'-img').innerHTML='<img src=\"/img/preloader.gif\">';
		var XHR = new XHRConnection();
		XHR.appendData('md5','$md5');
		XHR.appendData('folder-active',fold);
		if(document.getElementById(id).checked){XHR.appendData('f-enable',1);}else{XHR.appendData('f-enable',0);}
		AnimateDiv('form-$t');
		XHR.sendAndLoad('$page', 'POST',x_EnableFolderid$t);		
	}
	
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function as_gateway_popup_save(){
	$_POST["passwordr"]=addslashes(url_decode_special_tool($_POST["passwordr"]));
	$md5=$_POST["asgmd5"];
	
	$sql="UPDATE mbx_migr_users SET 
	imapr_server='{$_POST["imapr_server"]}',
	AsGateway='{$_POST["AsGateway"]}',
	usesslr='{$_POST["usesslr"]}',
	cert_fingerprintr='{$_POST["cert_fingerprintr"]}',
	usernamer='{$_POST["usernamer"]}',
	passwordr='{$_POST["passwordr"]}'
	WHERE zmd5='$md5'";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		if(preg_match("#Unknown column#", $q->mysql_error)){
			$q->BuildTables();
			$q->QUERY_SQL($sql,"artica_backup");
		}
	}
	
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	
	if($_POST["cert_fingerprintr"]<>null){
		$q->QUERY_SQL($sql,"UPDATE mbx_migr_users SET cert_fingerprint='{$_POST["cert_fingerprintr"]}' WHERE `imap_server`='{$_POST["imapr_server"]}'","artica_backup");
	}	
	
}


function as_gateway_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["as-gateway"];
	$sql="SELECT * FROM mbx_migr_users WHERE zmd5='$md5'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$t=time();
	$html="
	<div class=explain style='font-size:14px'>{offlineimap_gateway_explain}</div>
<div id='form-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td style='font-size:16px'>".Field_checkbox("AsGateway-$t",1,$ligne["AsGateway"],"formCheck$t()")."</td>
		<td width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{imap_server_name}:</td>
		<td style='font-size:16px'>". Field_text("imapr_server-$t",$ligne["imapr_server"],"font-size:16px;width:240px")."</td>
		<td>$buttonf</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{UseSSL}:</td>
		<td style='font-size:16px'>". Field_checkbox("usesslr-$t",1,$ligne["usesslr"],"usesslcheck$t()")."</td>
		<td></td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{fingerprint}:</td>
		<td style='font-size:16px'>". Field_text("cert_fingerprintr-$t",$ligne["cert_fingerprintr"],"font-size:16px;width:300px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td style='font-size:16px'>". Field_text("usernamer-$t",$ligne["usernamer"],"font-size:16px;width:300px")."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td style='font-size:16px'>". Field_password("passwordr-$t",$ligne["passwordr"],"font-size:16px;width:240px")."</td>
		<td></td>
	</tr>
<tr>
		<td colspan=3 align='right'><hr>". button("{apply}", "SaveOfflineRule$t()","18px")."</td>
	</tr>
	</table>	
<script>
var x_SaveOfflineRule$t= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById('form-$t').innerHTML='';
	if(tempvalue.length>3){alert(tempvalue);return;};
	
}	
	
	function SaveOfflineRule$t(){
		var XHR = new XHRConnection();
		XHR.appendData('asgmd5','$md5');
		var pp=encodeURIComponent(document.getElementById('passwordr-$t').value);
		XHR.appendData('imapr_server',document.getElementById('imapr_server-$t').value);
		if(document.getElementById('usesslr-$t').checked){XHR.appendData('usesslr',1);}else{XHR.appendData('usesslr',0);}
		if(document.getElementById('AsGateway-$t').checked){XHR.appendData('AsGateway',1);}else{XHR.appendData('AsGateway',0);}
		XHR.appendData('cert_fingerprintr',document.getElementById('cert_fingerprintr-$t').value);
		XHR.appendData('usernamer',document.getElementById('usernamer-$t').value);
		XHR.appendData('passwordr',pp);
		AnimateDiv('form-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveOfflineRule$t);		
	}
	
	function usesslcheck$t(){
		if(!document.getElementById('AsGateway-$t').checked){return;}
	
		document.getElementById('cert_fingerprintr-$t').disabled=true;
		if(document.getElementById('usesslr-$t').checked){
			document.getElementById('cert_fingerprintr-$t').disabled=false;
		}
	
	}
	
	function formCheck$t(){
		document.getElementById('imapr_server-$t').disabled=true;
		document.getElementById('cert_fingerprintr-$t').disabled=true;
		document.getElementById('passwordr-$t').disabled=true;
		document.getElementById('usesslr-$t').disabled=true;
		document.getElementById('usernamer-$t').disabled=true;

		if(document.getElementById('AsGateway-$t').checked){
			document.getElementById('imapr_server-$t').disabled=false;
			document.getElementById('passwordr-$t').disabled=false;
			document.getElementById('usesslr-$t').disabled=false;
			document.getElementById('usernamer-$t').disabled=false;
		}
	
	}	
	formCheck$t();
	usesslcheck$t();
	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function item_edit_popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{MAILBOXES_MIGRATION}::{new_rule}");	
	$md5=$_GET["md5"];
	$field=Field_text("uid-$t",$ligne["imap_server"],"font-size:16px;width:240px");
	$btname="{add}";
	$CountDeFolders=0;
	
	if($md5<>null){
		$sql="SELECT * FROM mbx_migr_users WHERE zmd5='$md5'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$btname="{apply}";
		$Folders=unserialize(base64_decode($ligne["mbxfolders"]));
		$CountDeFolders=count($Folders["SourceServer"]);
	$asgateway="	<tr>
		<td class=legend style='font-size:16px' colspan=2 align='right'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:YahooWin5('650','$page?as-gateway=$md5','{$ligne["imap_server"]}::{act_as_gateway}...');\"
		style='font-size:14px;text-decoration:underline;font-weight:bold'>{act_as_gateway}</a>
		</td>
		<td></td>
	</tr>";
	}

	if($CountDeFolders>0){
		$buttonf=button("{folders}...", "YahooWin5('650','$page?source-folders=$md5','{$ligne["imap_server"]}::{folders}...');");
		
	}
	
	

	$html="
	<div id='form-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{local_mailbox}:</td>
		<td style='font-size:16px'>".Field_text("uid-$t",$ligne["uid"],"font-size:16px;width:300px")."</td>
		<td width=1%>". button("{browse}", "Loadjs('MembersBrowse.php?OnlyUsers=1&NOComputers=1&field-user=uid-$t')")."</td>
	</tr>
	
	$asgateway
	
	<tr>
		<td class=legend style='font-size:16px'>{imap_server_name}:</td>
		<td style='font-size:16px'>". Field_text("imap_server-$t",$ligne["imap_server"],"font-size:16px;width:240px")."</td>
		<td>$buttonf</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{debug}:</td>
		<td style='font-size:16px'>". Field_checkbox("verbosed-$t",1,$ligne["verbosed"],"")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{maxsize}:</td>
		<td style='font-size:16px'>". Field_text("maxsize-$t",$ligne["maxsize"],"font-size:16px;width:60px")."&nbsp;M</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{maxage}:</td>
		<td style='font-size:16px'>". Field_text("maxage-$t",$ligne["maxage"],"font-size:16px;width:60px")."&nbsp;{days}</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{createfolders}:</td>
		<td style='font-size:16px'>". Field_checkbox("createfolders-$t",1,$ligne["createfolders"],"")."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{readonly}:</td>
		<td style='font-size:16px'>". Field_checkbox("readonly-$t",1,$ligne["readonly"],"")."</td>
		<td></td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{UseSSL}:</td>
		<td style='font-size:16px'>". Field_checkbox("usessl-$t",1,$ligne["usessl"],"usesslcheck$t()")."</td>
		<td></td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{fingerprint}:</td>
		<td style='font-size:16px'>". Field_text("cert_fingerprint-$t",$ligne["cert_fingerprint"],"font-size:16px;width:300px")."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td style='font-size:16px'>". Field_text("username-$t",$ligne["username"],"font-size:16px;width:300px")."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td style='font-size:16px'>". Field_password("password-$t",$ligne["password"],"font-size:16px;width:240px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button($btname, "SaveOfflineRule$t()","18px")."</td>
	</tr>
	</table>	
<script>
var x_SaveOfflineRule$t= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById('form-$t').innerHTML='';
	$('#flexRT$t').flexReload();
	if(tempvalue.length>3){alert(tempvalue);return;};
	var md='$md5';
	if(md.length===0){YahooWin4Hide();}
	
}	
	
	function SaveOfflineRule$t(){
		var XHR = new XHRConnection();
		XHR.appendData('md5','$md5');
		var pp=encodeURIComponent(document.getElementById('password-$t').value);
		XHR.appendData('imap_server',document.getElementById('imap_server-$t').value);
		if(document.getElementById('usessl-$t').checked){XHR.appendData('usessl',1);}else{XHR.appendData('usessl',0);}
		if(document.getElementById('verbosed-$t').checked){XHR.appendData('verbosed',1);}else{XHR.appendData('verbosed',0);}
		if(document.getElementById('createfolders-$t').checked){XHR.appendData('createfolders',1);}else{XHR.appendData('createfolders',0);}
		XHR.appendData('cert_fingerprint',document.getElementById('cert_fingerprint-$t').value);
		XHR.appendData('username',document.getElementById('username-$t').value);
		XHR.appendData('maxage',document.getElementById('maxage-$t').value);
		XHR.appendData('maxsize',document.getElementById('maxsize-$t').value);
		
		
		
		XHR.appendData('password',pp);
		AnimateDiv('form-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveOfflineRule$t);		
	}
	
	function usesslcheck$t(){
		
	
		document.getElementById('cert_fingerprint-$t').disabled=true;
		if(document.getElementById('usessl-$t').checked){
			document.getElementById('cert_fingerprint-$t').disabled=false;
		}
	
	}
	
	usesslcheck$t();
	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}
function item_edit_save(){
	$_POST["password"]=addslashes(url_decode_special_tool($_POST["password"]));
	if($_POST["md5"]==null){
		$md5=md5(serialize($_POST));	
		$sql="INSERT INTO `mbx_migr_users` (`zmd5`,`uid`,`imap_server`,`usessl`,`cert_fingerprint`,`username`,
		`password`,`maxage`,`maxsize`,`createfolders`,`verbosed`)
		VALUES ('$md5','{$_POST["uid"]}','{$_POST["imap_server"]}','{$_POST["usessl"]}','{$_POST["cert_fingerprint"]}','{$_POST["username"]}',
		'{$_POST["password"]}',
		'{$_POST["maxage"]}',
		'{$_POST["maxsize"]}',
		'{$_POST["createfolders"]}',
		'{$_POST["verbosed"]}'
		
		
		)";	

	
	}else{
		$md5=$_POST["md5"];
		$sql="UPDATE mbx_migr_users SET imap_server='{$_POST["imap_server"]}',
		usessl='{$_POST["usessl"]}',
		`uid`='{$_POST["uid"]}',
		cert_fingerprint='{$_POST["cert_fingerprint"]}',
		username='{$_POST["username"]}',
		password='{$_POST["password"]}',
		maxage='{$_POST["maxage"]}',
		maxsize='{$_POST["maxsize"]}',
		createfolders='{$_POST["createfolders"]}',
		verbosed='{$_POST["verbosed"]}'
		WHERE zmd5='$md5'";
	}

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		if(preg_match("#Unknown column#", $q->mysql_error)){
			$q->BuildTables();
			$q->QUERY_SQL($sql,"artica_backup");
		}
	}
	
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}

	if($_POST["cert_fingerprint"]<>null){
		$q->QUERY_SQL($sql,"UPDATE mbx_migr_users SET cert_fingerprint='{$_POST["cert_fingerprint"]}' WHERE `imap_server`='{$_POST["imap_server"]}'","artica_backup");
	}
}
	
function popup_old(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$array["task"]="{create_task}";
	$array["users"]="{users}";

	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ou={$_GET["ou"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_migrmbx style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_migrmbx').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";			
}	

function status(){
	
}
	
function import(){
	$ou=base64_decode($_GET["ou"]);
	$page=CurrentPageName();
	$tpl=new templates();	
	$ldap=new clladp();
	$domains=$ldap->hash_get_domains_ou($ou);
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$t=$_GET["t"];
	
	$html="
	
	<div id='import-task-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{domain}:</td>
		<td>". Field_array_Hash($domains,"domain",null,null,null,0,"font-size:16px;padding:3px")."&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:YahooWinBrowse('500','$page?about=yes','$about_this_section');\" style='font-size:14px;text-decoration:underline'>$about_this_section</a></td>
		<td></td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{file_path}:</td>
		<td width=99%>". Field_text("IMPORTATION_FILE_PATH","",'width:85%;font-size:16px;padding:3px'). " </td>
		<td width=1%>". button("{browse}","javascript:Loadjs('tree.php?select-file=txt&target-form=IMPORTATION_FILE_PATH')","16px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{import_datas}","MigrationImportDatas()","18px")."</td>
	</tr>
	</table>
	</div>
	<center>
	<div id='taskslistMigr' style='height:200px;overflow:auto;margin-top:8px;width:95%' class=form></div>
	</center>
	
	
	<script>
	var x_MigrationImportDatas=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('import-task-$t').innerHTML='';
		$('#flexRT$t').flexReload();
		
	}	
	
	function MigrationImportDatas(){
		var XHR = new XHRConnection();
		XHR.appendData('IMPORTATION_FILE_PATH',document.getElementById('IMPORTATION_FILE_PATH').value);
		XHR.appendData('domain',document.getElementById('domain').value);
		XHR.appendData('ou','{$_GET["ou"]}');		
		AnimateDiv('import-task-$t');	
		XHR.sendAndLoad('$page', 'GET', x_MigrationImportDatas);			 
	}
	
	function LauchTasks(){
		var XHR = new XHRConnection();
		XHR.appendData('RELAUNCH_TASKS','yes');
		AnimateDiv('import-task-$t');	
		XHR.sendAndLoad('$page', 'GET', x_MigrationImportDatas);			 
	}	
	
	function TaskListsRefresh(){
		LoadAjax('taskslistMigr','$page?tasks-list=yes&ou={$_GET["ou"]}');
	}
	
	function TaskMigrDelete(ID){
		var XHR = new XHRConnection();
		XHR.appendData('DELETE_TASK',ID);
		XHR.appendData('ou','{$_GET["ou"]}');		
		AnimateDiv('import-task-$t');	
		XHR.sendAndLoad('$page', 'GET', x_MigrationImportDatas);	
	}
		
	function ReloadMembers(){
		var XHR = new XHRConnection();
		XHR.appendData('RELOAD_MEMBERS','yes');
		document.getElementById('users-popup-list').innerHTML='<center><img src=img/wait_verybig.gif></center>';	
		XHR.sendAndLoad('$page', 'GET', x_MigrationImportDatas);			
	}	
	
	function RestartMembers(){
		var XHR = new XHRConnection();
		XHR.appendData('RESTART_MEMBERS','yes');
		XHR.appendData('ou','{$_GET["ou"]}');	
		document.getElementById('users-popup-list').innerHTML='<center><img src=img/wait_verybig.gif></center>';	
		XHR.sendAndLoad('$page', 'GET', x_MigrationImportDatas);			
	}		
	
	

	
	TaskListsRefresh();
</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function task_list(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$ou=$_GET["ou"];
	$sql="SELECT * FROM mbx_migr WHERE ou='$ou'";
	$q=new mysql();
	$classtr=null;
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H3>$q->mysql_error</h3>";}
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=3>$ou</th>
	<th>{imported}</th>
	<th>{terminated}</th>
	<th>{members}</th>
	<th>&nbsp;</th>
	<th>{delete}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$local_domain=$ligne["local_domain"];
			$filename=basename($ligne["filepath"]);
			$delete=imgtootltip("delete-24.png","{delete}","TaskMigrDelete('{$ligne["ID"]}')");
			if($ligne["imported"]==0){$imported="danger24.png";}else{$imported="ok24.png";}
			if($ligne["finish"]==0){$finish="danger24.png";}else{$finish="ok24.png";}
			$relaunch=null;
			if($ligne["members_count"]<1){$relaunch=imgtootltip("task-run.gif","{run}","LauchTasks()");}else{$relaunch="&nbsp;";}
			$html=$html."
			<tr class=$classtr>
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td align='center' width=99%><strong style='font-size:14px'>$local_domain</td>
			<td align='center'  width=99%><strong style='font-size:14px'>$filename</td>
			<td align='center' width=1%><strong style='font-size:14px'><img src='img/$imported'></td>
			<td align='center' width=1%><strong style='font-size:14px'><img src='img/$finish'></td>
			<td align='center' width=1%><strong style='font-size:14px'>{$ligne["members_count"]}</strong></td>
			<td align='center' width=1%>$relaunch</td>
			<td align='center' width=1%>$delete</td>
			</tr>
			";
			
		}
	
	
	$html=$html."</tbody></table>
	<div style='text-align:right;margin-top:8px'>". imgtootltip("refresh-32.png","{refresh}","TaskListsRefresh()")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function MIGRATION_CREATE_USERS(){
	$path=$_GET["IMPORTATION_FILE_PATH"];
	$ou=$_GET["ou"];
	if($ou==null){echo "Organization is null !\n";return;}
	$local_domain=$_GET["domain"];
	$sql="INSERT INTO mbx_migr (ou,filepath,imported,finish,local_domain) VALUES ('$ou','$path','0','0','$local_domain')";
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mbx-migr-add-file=yes");
}
function MIGRATION_RELAUNCH_TASKS(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mbx-migr-add-file=yes");	
}

function MIGRATION_DELETE_TASK(){
	$ou=base64_decode($_GET["ou"]);
	if(!is_numeric($_GET["DELETE_TASK"])){echo "Not numeric!";return;}
	$sql="DELETE FROM mbx_migr WHERE ID='{$_GET["DELETE_TASK"]}' AND ou='$ou'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="DELETE FROM mbx_migr_users WHERE 	mbx_migr_id='{$_GET["DELETE_TASK"]}' AND ou='$ou'";
	$q->QUERY_SQL($sql,"artica_backup");
}

function USERS_POPUP(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$ou=base64_decode($_GET["ou"]);
	$html="
	<div id='users-popup-list' style='height:400px;overflow:auto'></div>
	
	
	<script>
		LoadAjax('users-popup-list','$page?users-list=yes&ou={$_GET["ou"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}



function users_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$imap_server=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$terminated=$tpl->_ENGINE_parse_body("{terminated}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$ou=base64_decode($_GET["ou"]);
	$t=time();
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$MAILBOXES_MIGRATION=$tpl->_ENGINE_parse_body("{MAILBOXES_MIGRATION}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$schedule=$tpl->_ENGINE_parse_body("{schedule}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$action_exec_rule=$tpl->javascript_parse_text("{action_exec_rule}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'Add', onpress : add_single_rule$t},
	{name: '$import', bclass: 'Copy', onpress : ImportBulk$t},
	{name: '$events', bclass: 'Script', onpress : events$t},
	{name: '$online_help', bclass: 'Help', onpress : online_help$t},
		],	";		
	
	
	$html="
	<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search-list=yes&t=$t&ou=$ou',
	dataType: 'json',
	colModel : [
		{display: '$members', name : 'uid', width :184, sortable : true, align: 'left'},
		{display: '$imap_server', name : 'imap_server', width :170, sortable : true, align: 'left'},
		{display: '$account', name : 'username', width : 141, sortable : true, align: 'left'},
		{display: '$events', name : 'exec', width : 31, sortable : false, align: 'center'},
		{display: 'exec', name : 'exec', width : 31, sortable : false, align: 'center'},
		{display: '$schedule', name : 'schedule', width : 31, sortable : true, align: 'center'},
		{display: '$terminated', name : 'imported', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'DEL', width : 31, sortable : false, align: 'center'},
		],$buttons
	
	searchitems : [
		{display: '$members', name : 'uid'},
		{display: '$imap_server', name : 'imap_server'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 768,
	height: 380,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function about_this_section$t(){
	YahooWinBrowse('550','$page?about=yes','$MAILBOXES_MIGRATION::$about_this_section');
}

function ItemEvents$t(zmd5){
	YahooWinBrowse('750','$page?item-events='+zmd5,'$events');
}

function add_single_rule$t(){
	Loadjs('$page?item-edit-js=&t=$t');
}

function ItemEdit$t(zmd5){
	Loadjs('$page?item-edit-js='+zmd5+'&t=$t');

}

function online_help$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=290','1024','900');
}

function events$t(){
	Loadjs('squid.update.events.php?table=system_admin_events&category=mbximport');
}

function ImportBulk$t(){
	YahooWin5('710','$page?import=yes&ou=$ou&t=$t','$MAILBOXES_MIGRATION::$import');
}

	var x_ItemEnable$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
	} 
	
var x_ItemDelete$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	if(!document.getElementById('row'+mem$t)){ $('#flexRT$t').flexReload();return; }
   	$('#row'+mem$t).remove();
	}   

var x_ItemRun$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
   	$('#flexRT$t').flexReload();
	} 	

function ItemEnable$t(id,md5){
  	 var XHR = new XHRConnection();
  	 if(document.getElementById(id).checked){XHR.appendData('value',1);}else{XHR.appendData('value',0);}
  	 XHR.appendData('md5',md5);
  	 XHR.appendData('enable','yes');
  	 XHR.sendAndLoad('$page', 'POST',x_ItemEnable$t); 
}

function ItemDelete$t(md5){
	if(confirm('$action_delete_rule')){
		mem$t=md5;
	    var XHR = new XHRConnection();
        XHR.appendData('DeleteRule',md5);
		XHR.sendAndLoad('$page', 'POST',x_ItemDelete$t);  		
	}
}

function ItemRun$t(md5){
	if(confirm('$action_exec_rule')){
	    var XHR = new XHRConnection();
        XHR.appendData('ExecRule',md5);
		XHR.sendAndLoad('$page', 'POST',x_ItemRun$t);  		
	}
}


</script>";
	
	echo $html;
	return;
}

function delete_user_rule(){
	$md5=$_POST["DeleteRule"];
	$q=new mysql();
	$sql="DELETE FROM mbx_migr_users WHERE  zmd5='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}
function ExecRule(){
	$ExecRule=$_POST["ExecRule"];
	$sock=new sockets();
	$sock->getFrameWork("offlineimap.php?exec=yes&md5=$ExecRule");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{install_app}",1);
	
}

function schedule_enable(){
	$md5=$_POST["md5"];
	$value=$_POST["value"];
	$q=new mysql();
	$sql="UPDATE mbx_migr_users SET scheduled=$value WHERE zmd5='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function users_list_item(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$searchstring=null;
	$t=$_GET["t"];
	$ou=$_GET["ou"];
	$search='%';
	$table="mbx_migr_users";
	$page=1;
	
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("No rules....");}
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$folderstext=$tpl->_ENGINE_parse_body("{folders}");
	$totext=$tpl->_ENGINE_parse_body("{to}");
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$sock=new sockets();
	$span="<span style='font-size:14px'>";
	while ($ligne = mysql_fetch_assoc($results)) {
			$PID=$ligne["PID"];
			$uid=$ligne["uid"];
			$delete=null;
			$status=null;
			$text_folders=null;
			$text_AsGateway=null;
			$AsGateway=$ligne["AsGateway"];
			$imapr_server=$ligne["imapr_server"];
			$usesslr=$ligne["usesslr"];
			$usernamer=$ligne["usernamer"];			
			$status="<br><strong style='color:#7C7777;font-size:11px'><i>{stopped} PID:$PID</i></strong>";
			
			if($uid<>null){$showuser=MEMBER_JS($ligne["uid"],1,1);}
			if($uid==null){$uid=$unknown;}
			if($ligne["imported"]==0){$imported="danger24.png";}else{$imported="ok24.png";}
			$ProcessExists=$sock->getFrameWork("cmd.php?ProcessExists=yes&PID=$PID");
			
			$Folders=unserialize(base64_decode($ligne["mbxfolders"]));
			$CountDeFolders=count($Folders["SourceServer"]);
			if($CountDeFolders>0){
				$CountDeSelectFolders=count($Folders["FoldersSelectedSourceServer"]);
				if($CountDeSelectFolders==0){$CountDeSelectFolders=$CountDeFolders;}
				$text_folders="<br><span style='font-size:11px;color:#7C7777;'><i>$CountDeSelectFolders/".count($Folders["SourceServer"])." $folderstext</i></span>";
			}
			if($AsGateway==1){$text_AsGateway="<br><span style='font-size:11px;color:#7C7777;'><i>$totext: $usernamer@$imapr_server</i></span>";}
			
			
			$exec=imgsimple("24-run.png",null,"ItemRun$t('{$ligne["zmd5"]}')");
			$events="&nbsp;";
			
			if($ProcessExists=="TRUE"){
				$ttl=$sock->getFrameWork("cmd.php?process-ttl=yes&pid=$PID");
				$status="<br><strong style='color:red;font-size:11px'><i>{running} PID $PID {since} $ttl {minutes}</i></strong>";
				$exec="&nbsp;";
			}
			
			if(strlen($ligne["events"])>10){$events=imgsimple("events-24.png",null,"ItemEvents$t('{$ligne["zmd5"]}')");}
			
			$href="<a href=\"javascript:blur();\" OnClick=\"javascript:MigrShowLogs('{$ligne["zmd5"]}')\"
			style='font-size:14px;text-decoration:underline'>";
			
			$href="<a href=\"javascript:blur();\" OnClick=\"javascript:ItemEdit$t('{$ligne["zmd5"]}')\"
			style='font-size:14px;text-decoration:underline'>";
			
			$hrefuid="<a href=\"javascript:blur();\" OnClick=\"javascript:$showuser\"
			style='font-size:14px;text-decoration:underline'>";
			$status= $tpl->_ENGINE_parse_body($status);
			
			$scheduled=Field_checkbox("{$ligne["zmd5"]}-schedule", 1,
			$ligne["scheduled"],"ItemEnable$t('{$ligne["zmd5"]}-schedule','{$ligne["zmd5"]}')");
			
			$delete=imgsimple("delete-24.png",null,"ItemDelete$t('{$ligne["zmd5"]}')");
			
			
		$data['rows'][] = array(
		'id' => $ligne["zmd5"],
		'cell' => array(
			$span.$href.$uid."</a></span>$status",
			$span.$href.$ligne["imap_server"]."</a>$text_folders$text_AsGateway</span>",
			$span.$href.$ligne["username"]."</a></span>",
			$events,
			$exec,
			$scheduled,
			"<img src='img/$imported'>",
			$delete
			)
		);
	}
	
	
echo json_encode($data);		

}



function USERS_POPUP_LIST(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();		
	$ou=$_GET["ou"];
	$sql="SELECT * FROM mbx_migr_users WHERE ou='$ou'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H3>$q->mysql_error</h3>";}
	
	$html="
	<center>
	<table style='margin:5px'>
	<tr>
		<td width=50%>". button("{restart_task}","RestartMembers('{$_GET["ou"]}')")."</td>
		<td width=50%>". button("{run_task}","ReloadMembers()")."</td>
	</tr>
	</table>
	<hr style='width:70%'>
	</center>
	
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>&nbsp;</th>
	<th>{member}</th>
	<th>{imap_server}</th>
	<th>{account}</th>
	<th>{terminated}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	$over="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$PID=$ligne["PID"];
			$status="<br><strong style='color:#7C7777;font-size:11px'><i>{stopped} PID:$PID</i></strong>";
			$showuser=MEMBER_JS($ligne["uid"],1,1);
			if($ligne["imported"]==0){$imported="danger24.png";}else{$imported="ok24.png";}
			
			if($sock->getFrameWork("cmd.php?ProcessExists=yes&PID=$PID")){
				$status="<br><strong style='color:red;font-size:11px'><i>{running} PID $PID</i></strong>";
			}
			
			$html=$html."
			<tr class=$classtr>
			<td width=1%>". imgtootltip("user-32.png",$ligne["uid"],$showuser)."</td>
			<td  width=99%><strong style='font-size:14px;text-decoration:underline' $over OnClick=\"javascript:MigrShowLogs('{$ligne["zmd5"]}')\">{$ligne["uid"]}</td>
			<td  width=99%><strong style='font-size:14px;' $over OnClick=\"javascript:MigrShowLogs('{$ligne["zmd5"]}')\">{$ligne["imap_server"]}$status</td>
			<td  width=99%><strong style='font-size:14px;' $over OnClick=\"javascript:MigrShowLogs('{$ligne["zmd5"]}')\">{$ligne["username"]}</td>
			<td align='center' width=1%><strong style='font-size:14px'><img src='img/$imported'></td>
			</tr>
			";
			
		}
	
	
	$html=$html."</tbody></table>
	<div style='text-align:right;margin-top:8px'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_config_migrmbx')")."</div>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function USERS_EVENTS(){
	$sql="SELECT * FROM mbx_migr_users WHERE zmd5='{$_GET["users-events"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$tbl=explode("\n",$ligne["events"]);
	if(!is_array($tbl)){return;}
	krsort($tbl);
	while (list ($num, $line) = each ($tbl) ){
		if($line==null){continue;}
		$html=$html."<div><code>$line</code></div>";
		
	}
	
	echo "<div style='height:450px;overflow:auto'>$html</div>";
}


function MIGRATION_RELAUNCH_MEMBERS(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mbx-migr-reload-members=yes");
	
}
function MIGRATION_RESTART_MEMBERS(){
	$ou=base64_decode($_GET["ou"]);
	$sql="UPDATE mbx_migr_users SET imported=0 WHERE ou='$ou'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mbx-migr-reload-members=yes");	
}
function about(){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{MAILBOXES_MIGRATION_EXPLAIN}<hr>
	{MAILBOXES_MIGRATION_EXPLAIN_2}
	
	</div>");
	
}
function item_events(){
	$sql="SELECT events FROM mbx_migr_users WHERE zmd5='{$_GET["item-events"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$now=date('Y-m-d');
	$ligne["events"]=str_replace("$now ", "", $ligne["events"]);
	$ligne["events"]=str_replace("DEBUG: [imap]:   ", "", $ligne["events"]);
	$ligne["events"]=str_replace("DEBUG: ", "", $ligne["events"]);
	$ligne["events"]=str_replace("INFO: ", "", $ligne["events"]);
	
	$f=explode("\n",$ligne["events"]);
	while (list ($num, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		$tt[]=$line;
	}
	
	echo "<textarea 
	style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:550px;border:5px solid #8E8E8E;overflow:auto;font-size:12px' 
	id='textToParseCats$t'>".@implode("\n", $tt)."</textarea>";
	
	
}

