<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSquidAdministrator==false){echo "alert('No privs');";die();}

	
	if(isset($_GET["manual-update"])){manual_update();exit;}
	if( isset($_GET['TargetArticaUploaded']) ){upload_artica_perform();exit();}
	if(isset($_GET["file-uploader-demo1"])){upload_artica_final();exit;}
	if(isset($_GET["uncompress"])){uncompress();exit;}
	if(isset($_GET["remove"])){remove();exit;}
	
	
	
	js();
function js(){
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){ echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');"; die(); }	
	header("content-type: application/x-javascript");
	
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{logo}");
	echo "YahooWinBrowse('700','$page?manual-update=yes&zmd5={$_GET["zmd5"]}','$title',true)";
	
}	
function manual_update(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_a_picture}");
	$allowedExtensions="allowedExtensions: ['png','gif','jpeg','jpg'],";
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$sock=new sockets();
	$html="
	<H2>{change_picture}</H2>
	
	
	<center style='margin:10px;width:99%'>
		<center id='file-uploader-demo1' style='width:100%;text-align:center'></center>
	</center>
	<script>
function createUploader$t(){
	var uploader$t = new qq.FileUploader({
		element: document.getElementById('file-uploader-demo1'),
		action: '$page',$allowedExtensions
		template: '<div class=\"qq-uploader\">' +
		'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
		'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
		'<ul class=\"qq-upload-list\"></ul>' +
		'</div>',
		debug: false,
		params: {
		TargetArticaUploaded: 'yes',
		zmd5: '{$_GET["zmd5"]}',
		},
		onComplete: function(id, fileName){
		PathUploaded$t(fileName);
		}
	});

}

function PathUploaded$t(fileName){
	LoadAjax('file-uploader-demo1','$page?file-uploader-demo1=yes&fileName='+fileName+'&zmd5={$_GET["zmd5"]}');
}
createUploader$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);



}
function upload_artica_perform(){

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

	$content_dir=dirname(__FILE__)."/img/upload/";
	@mkdir($content_dir);
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir,true);

	writelogs("upload_form_perform() -> OK",__FUNCTION__,__FILE__,__LINE__);



	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName OK",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;

	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;

}
function upload_artica_final(){
	$fileName=$_GET["fileName"];
	$zmd5=$_GET["zmd5"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$fileNameEnc=urlencode($fileName);
	$q=new mysql_squid_builder();
	
	
	
	
	$sock=new sockets();
	$content_dir=dirname(__FILE__)."/img/upload/$fileName";
	$picture=mysql_escape_string2(@file_get_contents($content_dir));
	@unlink($content_dir);
	
	if($zmd5==null){
		echo FATAL_WARNING_SHOW_128("MD5 is null");
		return;
	}
	
	$sql="UPDATE `ufdb_design` SET `picturename`='$fileName',`picture`='$picture',`SquidHTTPTemplateLogoEnable`=1 WHERE zmd5='$zmd5'";
	$q->QUERY_SQL($sql);
	
	
	echo $tpl->_ENGINE_parse_body("<div style='font-size:22px'>{saving} $fileName<br>$zmd5</div>");
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	echo "<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','$page?uncompress=$fileNameEnc');
	</script>

	";

}


function uncompress(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$fileName=$_GET["uncompress"];
	sleep(3);
	
	
	
	
	echo "<div id='$t'></div>
	<script>
		RefreshTab('ERROR_PAGE_SKIN_TAB');
		YahooWinBrowseHide();
	</script>
	
	";
	
}
