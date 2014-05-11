<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
ini_set('display_errors', 1);

ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["nightlybuild"])){parameters_save();exit;}

if(isset($_GET["manual-update"])){manual_update();exit;}
if( isset($_GET['TargetPatchUploaded']) ){upload_patch_perform();exit();}
if( isset($_GET['TargetSoftUploaded']) ){upload_patch_perform();exit();}


if(isset($_GET["license"])){license();exit;}
if(isset($_POST["COMPANY"])){REGISTER();exit;}
if(isset($_GET["file-uploader-demo1"])){upload_patch_final();exit;}
if(isset($_GET["file-uploader-demo2"])){upload_soft_final();exit;}


tabs();
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{manual_update}"]="$page?manual-update=yes";
	$array["{artica_license}"]="$page?license=yes";
	echo $boot->build_tab($array);
}

function parameters(){
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$configDisk=trim($sock->GET_INFO('ArticaAutoUpdateConfig'));
	$ini->loadString($configDisk);
	$AUTOUPDATE=$ini->_params["AUTOUPDATE"];
	
	$EnableNightlyInFrontEnd=$sock->GET_INFO("EnableNightlyInFrontEnd");
	$EnableRebootAfterUpgrade=$sock->GET_INFO("EnableRebootAfterUpgrade");
	$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
	$EnablePatchUpdates=$sock->GET_INFO("EnablePatchUpdates");
	$ArticaScheduleUpdates=$sock->GET_INFO("ArticaScheduleUpdates");
	$DisableInstantLDAPBackup=$sock->GET_INFO("DisableInstantLDAPBackup");
	
	if(!is_numeric($DisableInstantLDAPBackup)){$DisableInstantLDAPBackup=0;}
	if(!is_numeric($EnableNightlyInFrontEnd)){$EnableNightlyInFrontEnd=1;}
	if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
	if(!is_numeric($EnableRebootAfterUpgrade)){$EnableRebootAfterUpgrade=0;}
	if(!is_numeric($EnablePatchUpdates)){$EnablePatchUpdates=0;}
	
	writelogs("EnableScheduleUpdates = $EnableScheduleUpdates",__FUNCTION__,__FILE__,__LINE__);
	
	if(trim($AUTOUPDATE["uri"])==null){$AUTOUPDATE["uri"]="http://articatech.net/auto.update.php";}
	if(trim($AUTOUPDATE["enabled"])==null){$AUTOUPDATE["enabled"]="yes";}
	if(trim($AUTOUPDATE["autoinstall"])==null){$AUTOUPDATE["autoinstall"]="yes";}
	if(trim($AUTOUPDATE["CheckEveryMinutes"])==null){$AUTOUPDATE["CheckEveryMinutes"]="60";}
	if(trim($AUTOUPDATE["front_page_notify"])==null){$AUTOUPDATE["front_page_notify"]="yes";}
	if(trim($AUTOUPDATE["samba_notify"])==null){$AUTOUPDATE["samba_notify"]="no";}
	if(trim($AUTOUPDATE["auto_apt"])==null){$AUTOUPDATE["auto_apt"]="no";}	
	

	$ip=new networking();
	$arrcp[null]="{default}";
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	
	
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	
	
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}	
	
	
	$boot=new boostrap_form();
	$boot->set_formtitle("{artica_autoupdate}");
	$boot->set_formdescription("{autoupdate_text}");
	
	$boot->set_checkboxYesNo("enabled", "{enable_autoupdate}", $AUTOUPDATE["enabled"]);
	$boot->set_checkboxYesNo("autoinstall", "{enable_autoinstall}", $AUTOUPDATE["autoinstall"]);
	$boot->set_checkboxYesNo("nightlybuild", "{enable_nightlybuild}", $AUTOUPDATE["nightlybuild"]);
	$boot->set_checkboxYesNo("EnableNightlyInFrontEnd", "{EnableNightlyInFrontEnd}", $AUTOUPDATE["EnableNightlyInFrontEnd"]);
	$boot->set_checkboxYesNo("front_page_notify", "{front_page_notify}", $AUTOUPDATE["front_page_notify"]);
	$boot->set_list("WgetBindIpAddress", "{WgetBindIpAddress}", $arrcp,$WgetBindIpAddress);
	$boot->set_field("CheckEveryMinutes", "{CheckEveryMinutes}", $AUTOUPDATE["CheckEveryMinutes"]);
	$boot->set_checkbox("RebootAfterArticaUpgrade", "{RebootAfterArticaUpgrade}", $RebootAfterArticaUpgrade);
	$boot->set_field("uri", "{uri}", $AUTOUPDATE["uri"]);
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	$boot->set_button("{apply}");
	echo $boot->Compile();
	
}

function parameters_save(){
	writelogs("AUTOUPDATE -> SAVE",__FUNCTION__,__FILE__);
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$configDisk=trim($sock->GET_INFO('ArticaAutoUpdateConfig'));
	$ini->loadString($configDisk);
	while (list ($num, $ligne) = each ($_POST) ){
		writelogs("AUTOUPDATE:: $num=$ligne",__FUNCTION__,__FILE__);
		$ini->_params["AUTOUPDATE"][$num]=$ligne;
	}
	
	$data=$ini->toString();
	$sock->SET_INFO("WgetBindIpAddress",$_POST["WgetBindIpAddress"]);
	$sock->SET_INFO("EnableNightlyInFrontEnd",$_POST["EnableNightlyInFrontEnd"]);
	if(isset($_POST["ArticaScheduleUpdates"])){$sock->SET_INFO("ArticaScheduleUpdates",$_POST["ArticaScheduleUpdates"]);}
	$sock->SET_INFO("RebootAfterArticaUpgrade",$_POST["RebootAfterArticaUpgrade"]);
	
	
	
	
	if(isset($_GET["EnableScheduleUpdates"])){$sock->SET_INFO("EnableScheduleUpdates",$_POST["EnableScheduleUpdates"]);}
	$sock->SaveConfigFile($data,"ArticaAutoUpdateConfig");
	if(isset($_POST["EnablePatchUpdates"])){$sock->SET_INFO("EnablePatchUpdates",$_POST["EnablePatchUpdates"]);}
	if(isset($_POST["EnableRebootAfterUpgrade"])){$sock->SET_INFO("EnableRebootAfterUpgrade", $_POST["EnableRebootAfterUpgrade"]);}
	if(isset($_POST["DisableInstantLDAPBackup"])){$sock->SET_INFO("DisableInstantLDAPBackup", $_POST["DisableInstantLDAPBackup"]);}
	
	
	
	$sock->getFrameWork("cmd.php?ForceRefreshLeft=yes");
	$sock->getFrameWork("services.php?artica-update-cron=yes");
	$sock->getFrameWork("services.php?artica-patchs=yes");
	
}

function manual_update(){
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_a_file}");
	$allowedExtensions="allowedExtensions: ['gz'],";
	
	
	
	
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$html="
	<H2>Artica Patch</H2>
	<div class=explain>{artica_patch_explain}</div>
	<center style='margin:10px;width:80%'>
		<div id='file-uploader-demo1' style='width:100%;text-align:center'></div>
	</center>
	
	<H2>{software}</H2>
	<div class=explain>{artica_software_explain}</div>
	<center style='margin:10px;width:80%'>
		<div id='file-uploader-demo2' style='width:100%;text-align:center'></div>
	</center>	
	
	<script>
function createUploader(){
	var uploader = new qq.FileUploader({
		element: document.getElementById('file-uploader-demo1'),
		action: '$page',$allowedExtensions
		template: '<div class=\"qq-uploader\">' +
			'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
			'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
			'<ul class=\"qq-upload-list\"></ul>' +
		'</div>',
	debug: false,
		params: {
			TargetPatchUploaded: 'yes',
			//select-file: '{$_GET["select-file"]}'
		},
		onComplete: function(id, fileName){
			PathUploaded(fileName);
		}
	});

	
	var uploader2 = new qq.FileUploader({
		element: document.getElementById('file-uploader-demo2'),
		action: '$page',$allowedExtensions
		template: '<div class=\"qq-uploader\">' +
			'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
			'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
			'<ul class=\"qq-upload-list\"></ul>' +
		'</div>',
	debug: false,
		params: {
			TargetSoftUploaded: 'yes',
		},
		onComplete: function(id, fileName){
			SoftUploaded(fileName);
		}
	});
	}	
	
	function PathUploaded(fileName){
		LoadAjax('file-uploader-demo1','$page?file-uploader-demo1=yes&fileName='+fileName);
	
	}
	function SoftUploaded(fileName){
		LoadAjax('file-uploader-demo2','$page?file-uploader-demo2=yes&fileName='+fileName);
	
	}	
	
	createUploader();
	</script>
	";
	
	//$html="<iframe style='width:100%;height:250px;border:1px' src='$page?form-upload={$_GET["upload-file"]}&select-file={$_GET["select-file"]}'></iframe>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function upload_patch_perform(){

	usleep(300);
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?lighttpd-own=yes");

	if (isset($_GET['qqfile'])){
		$fileName = $_GET['qqfile'];
		if(function_exists("apache_request_headers")){
			$headers = apache_request_headers();
			if ((int)$headers['Content-Length'] == 0){
				writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);
				die ('{error: "content length is zero"}');
			}
		}else{
			writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
		}
	} elseif (isset($_FILES['qqfile'])){
		$fileName = basename($_FILES['qqfile']['name']);
		writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
		if ($_FILES['qqfile']['size'] == 0){
			writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "file size is zero"}');
		}
	} else {
		writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "file not passed"}');
	}

	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

	if (count($_GET)){
		$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));
		writelogs($datas,__FUNCTION__,__FILE__,__LINE__);

	} else {
		writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "query params not passed"}');
	}
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
	$allowedExtensions = array();
	$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
	$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));

	if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}

	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir);

	writelogs("upload_form_perform() -> OK",__FUNCTION__,__FILE__,__LINE__);

	

	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName OK",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		//$rettun=$sock->getFrameWork("cmd.php?move_uploaded_file='{$_GET["TargetpathUploaded"]}&src=". base64_encode("$content_dir$fileName"));
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;

	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;

}

function upload_patch_final(){
	
	$sock=new sockets();
	$results=unserialize(base64_decode($sock->getFrameWork("system.php?apply-patch=".urlencode($_GET["fileName"]))));
	
	echo "<div style='text-align:left'><code style='font-size:14px !important'>";
	while (list ($eth, $line) = each ($results) ){
		echo "$line<br>";
		
	}
	echo "</code></div>";
}

function upload_soft_final(){

	$sock=new sockets();
	$results=unserialize(base64_decode($sock->getFrameWork("system.php?apply-soft=".urlencode($_GET["fileName"]))));
	
	echo "<div style='text-align:left'><code style='font-size:14px !important'>";
	while (list ($eth, $line) = each ($results) ){
		echo "$line<br>";
	
	}
	echo "</code></div>";
}
function license(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	$t=time();
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	$lastupdate="<p><strong>{license_status}:</strong> {$LicenseInfos["license_status"]}</p>
	<p><strong>{uuid}:</strong> $uuid</p>
	<p><strong>{license_number}:</strong> {$LicenseInfos["license_number"]}</p>
	
	";
	
	
	
	
	if(!$users->CORP_LICENSE){
		$exp1="{CORP_LICENSE_EXPLAIN}
		<div style='font-size:16px;font-weight:bold'>{price_quote}:</div>
			<div>
			<a href=\"javascript:blur();\"
				OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=292','1024','900');\"
				style=\"font-size:14px;font-weight;bold;text-decoration:underline\">{click_here_price_quote}</a>
			</div>";
	}
	
	
	
	
	
	
	
	
	
	$boot->set_formtitle("{artica_license} - {$LicenseInfos["license_status"]}");
	if($LicenseInfos["license_status"]==null){
		$exp2="<br>{explain_license_free}$lastupdate";
		$boot->set_formtitle("{artica_license} ({waiting_registration})");
		$button_text="{request_a_quote}/{refresh}";
	}else{
		$button_text="{update_the_request}";
		$exp2="<br>{explain_license_order}$lastupdate";
		
	}
	if($users->CORP_LICENSE){$exp2="$lastupdate";}
	$boot->set_formdescription("$exp1$exp2");
	
	
	
	if($users->CORP_LICENSE){
		$boot->set_form_locked();
	
	}
	
	$boot->set_hidden("REGISTER", 1);
	$boot->set_field("COMPANY", "{company}", $LicenseInfos["COMPANY"]);
	$boot->set_field("EMAIL", "{your_email_address}", $LicenseInfos["EMAIL"]);
	$boot->set_field("EMPLOYEES", "{nb_employees}", $LicenseInfos["EMPLOYEES"]);
	$boot->set_field("license_number", "{license_number}", $LicenseInfos["license_number"],array("DISABLED"=>true));
	
	if($LicenseInfos["license_status"]=="{license_active}"){
		$users->CORP_LICENSE=true;
		$boot->set_hidden("UNLOCKLIC", $LicenseInfos["UNLOCKLIC"]);
	}else{
		$boot->set_field("UNLOCKLIC", "{unlock_license}", $LicenseInfos["UNLOCKLIC"]);
	}
	
	$boot->set_button($button_text);
	echo $boot->Compile();
	
	
}
function REGISTER(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	while (list ($num, $ligne) = each ($_POST) ){
		$LicenseInfos[$num]=$ligne;
	}

	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?license-register=yes")));

}