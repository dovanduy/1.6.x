<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	
	
	if(isset($_GET["certificate-upload-popup"])){certificate_upload_popup();exit;}
	if(isset($_GET["certificate-upload-js"])){certificate_upload_js();exit;}
	if(isset($_GET['uploaded-certificate-CommonName']) ){certificate_upload_perform();exit();}
	if(isset($_POST["certificate-uploaded"])){certificate_upload_save();exit;}	

function certificate_upload_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{upload}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinBrowse(550,'$page?certificate-upload-popup=yes&RunAfter={$_GET["RunAfter"]}&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}&textid={$_GET["textid"]}','$title')";
}
	
function certificate_upload_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$allowedExtensions=null;
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$type=$_GET["type"];
	$typeText=$type;
	if($type=="crt"){$typeText="crt,pem";}
	if($type=="privkey"){$typeText="key";}
	$UploadAFile=$tpl->javascript_parse_text("{upload_certificate} - $typeText");
	$CommonName=$_GET["CommonName"];
	if($_GET["RunAfter"]<>null){$RunAfter="{$_GET["RunAfter"]}();";}
	$html="
		<div id='file-uploader-$t' style='width:100%;text-align:center'>
		<noscript>
		<!-- or put a simple form for upload here -->
		</noscript>
		</div>
		<script>
	
	
		var xUploadStep2$t=function (obj) {
		var results=obj.responseText;
		document.getElementById('file-uploader-$t').innerHTML='';
		if (results.length>50){
		if( document.getElementById('{$_GET["textid"]}') ){
		document.getElementById('{$_GET["textid"]}').value=results;
		$RunAfter
	}
	
	}
	
	
		ExecuteByClassName('SearchFunction');
		YahooWinBrowseHide();
	}
	function UploadStep2$t(fileName){
	var XHR = new XHRConnection();
	XHR.appendData('certificate-uploaded',encodeURIComponent(fileName));
	XHR.appendData('certificate-uploaded-type','$type');
	XHR.appendData('CommonName',encodeURIComponent('$CommonName'));
	AnimateDiv('file-uploader-$t');
	XHR.sendAndLoad('$page', 'POST',xUploadStep2$t);
	}
	
	
	function createUploader$t(){
	var uploader = new qq.FileUploader({
	element: document.getElementById('file-uploader-$t'),
	action: '$page',$allowedExtensions
	template: '<div class=\"qq-uploader\">' +
	'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
	'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
	'<ul class=\"qq-upload-list\"></ul>' +
	'</div>',
	debug: false,
	params: {
	'uploaded-certificate-type': '$type',
	'uploaded-certificate-CommonName': '$CommonName'
	},
	onComplete: function(id, fileName){
	UploadStep2$t(fileName);
				
	}
	});
	}
	
			createUploader$t();
			</script>
			";
	
			//$html="<iframe style='width:100%;height:250px;border:1px' src='$page?form-upload={$_GET["upload-file"]}&select-file={$_GET["select-file"]}'></iframe>";
		echo $html;
	}
	
function certificate_upload_perform(){
		usleep(300);
		writelogs("OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		$sock->getFrameWork("services.php?lighttpd-own=yes");
	
		if (isset($_GET['qqfile'])){
			$fileName = $_GET['qqfile'];
			if(function_exists("apache_request_headers")){
				$headers = apache_request_headers();
				if ((int)$headers['Content-Length'] == 0){writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);die ('{error: "content length is zero"}');}
			}else{
				writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
			}
		} elseif (isset($_FILES['qqfile'])){
			$fileName = basename($_FILES['qqfile']['name']);
			writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
			if ($_FILES['qqfile']['size'] == 0){writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);die ('{error: "file size is zero"}');}
		} else {
			writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "file not passed"}');
		}
	
		writelogs("OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
	
		if (count($_GET)){
			$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));
			writelogs($datas,__FUNCTION__,__FILE__,__LINE__);
		} else {
			writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "query params not passed"}');
		}
		writelogs("OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
		include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
		$allowedExtensions = array();
		$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
		$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));
		if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}
	
		$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		$result = $uploader->handleUpload($content_dir);
	
		writelogs("OK -> check $content_dir$fileName",__FUNCTION__,__FILE__,__LINE__);
	
	
	
		if(is_file("$content_dir$fileName")){
			writelogs("upload_form_perform() -> $content_dir$fileName ok",__FUNCTION__,__FILE__,__LINE__);
			echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
			return;
		}
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		return;
	}
	
	function certificate_upload_save(){
		$fileName=url_decode_special_tool($_POST["certificate-uploaded"]);
		$CommonName=url_decode_special_tool($_POST["CommonName"]);
		$type=url_decode_special_tool($_POST["certificate-uploaded-type"]);
		$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
		$filePath="$content_dir$fileName";
		if(!is_file($filePath)){echo "$filePath no such file\n";return;}
		$q=new mysql();
		$CONTENT=@file_get_contents($filePath);
		$certificate_content=mysql_escape_string2($CONTENT);
		$sql="UPDATE sslcertificates SET `$type`='$certificate_content' WHERE CommonName='$CommonName'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		echo $CONTENT;
	}