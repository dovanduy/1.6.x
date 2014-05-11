<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.fetchmail.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsPostfixAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["fetchrc"])){fetchrc();exit;}
if(isset($_POST["fetchmail-import-path"])){perform();exit;}
if(isset($_GET["get-logs"])){events();exit;}
if(isset($_POST["fetchmail-import-compiled-path"])){perform_import_compiled();exit;}


if(isset($_GET["dump"])){dump();exit;}
if( isset($_GET['TargetArticaUploaded']) ){upload_artica_perform();exit();}
if(isset($_GET["file-uploader-demo1"])){upload_artica_final();exit;}
if(isset($_GET["restore"])){restore();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_FETCHMAIL}::{import}");
	echo "YahooWin4('800','$page?tabs=yes&t={$_GET["t"]}','$title');";
	
	
}

function tabs(){
	
	$page=CurrentPageName();
	
	$array["popup"]='{from_csv_file}';
	$array["fetchrc"]='{from_compiled_file}';
	$array["dump"]='{from_articasrv}';
	$style="style='font-size:16px'";
	while (list ($num, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?$num=yes&t={$_GET["t"]}\"><span $style>$ligne</span></a></li>\n";
			
		}	

	echo build_artica_tabs($html, "main_config_fetchmail_import");
	
	
}

function fetchrc(){
$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	if(!isset($_COOKIE["fetchmail-import-compiled-path"])){$_COOKIE["fetchmail-import-compiled-path"]="/etc/fetchmailrc";}	
	$html="
	<center id='simple-$tt'></center>
	<div class=explain style='font-size:13px'>{fetchmail_importcomp_explain}</div>
	<div style='width:100%;text-align:right;font-size:13px'><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=288','1024','900');\" 
	style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{path}:</td>
		<td>". Field_text("fetchmail-import-compiled-path-$tt",$_COOKIE["fetchmail-import-compiled-path"],"font-size:18px;width:410px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?target-form=fetchmail-import-compiled-path-$tt')","14px")."</td>
	</tr>	
	</table>
	<tr>
		<td colspan=3 align='right'><hr>". button("{import}","ImportFetchNow$tt()","16px")."</td>
	</tr>	
	</table>
	<script>
var x_ImportFetchNow$tt = function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('simple-$tt').innerHTML='';
	      $('#flexRT$t').flexReload();
	      	if( document.getElementById('FETCHMAIL_FLEXRT') ){
				$('#'+  document.getElementById('FETCHMAIL_FLEXRT').value).flexReload();
			}
	      }		
		
		function ImportFetchNow$tt(){
			if(!document.getElementById('fetchmail-import-compiled-path-$tt')){alert('fetchmail-import-compiled-path-$tt !!!???');return;}
			var XHR = new XHRConnection();
			var path=document.getElementById('fetchmail-import-compiled-path-$tt').value;
			Set_Cookie('fetchmail-import-compiled-path',path,'3600', '/', '', '');
			XHR.appendData('fetchmail-import-compiled-path',path);
			AnimateDiv('simple-$tt');
			XHR.sendAndLoad('$page', 'POST',x_ImportFetchNow$tt);	
		}	
	</script>
";	
		echo $tpl->_ENGINE_parse_body($html);
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];	
	$html="
	<div class=explain style='font-size:13px'>{fetchmail_import_explain}</div>
	<div style='width:100%;text-align:right;font-size:13px'><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=288','1024','900');\" 
	style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{path}:</td>
		<td>". Field_text("fetchmail-import-path-$tt",$_COOKIE["fetchmail-import-path"],"font-size:18px;width:410px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?select-file=csv&target-form=fetchmail-import-path-$tt')","14px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{analyze}","ImportFetchNow$tt()","16px")."</td>
	</tr>
	</table>
	<div id='simple-$tt' style='min-height:150px;height:250px;overflow:auto;width:95%' class=form></div>	
	<div style='width:100%;text-align:right;font-size:13px'>". imgtootltip("20-refresh.png","{refresh}","FetchMailImportLogs$tt()")."</div>
	<script>
	
	
var x_ImportFetchNow$tt = function (obj) {
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('simple-$tt').innerHTML='';
	      FetchMailImportLogs$tt();
	      $('#flexRT$t').flexReload();
	      	if( document.getElementById('FETCHMAIL_FLEXRT') ){
				$('#'+  document.getElementById('FETCHMAIL_FLEXRT').value).flexReload();
			}
	      }		
		
		function ImportFetchNow$tt(){
			if(!document.getElementById('fetchmail-import-path-$tt')){alert('fetchmail-import-path-$tt !!!???');return;}
			var XHR = new XHRConnection();
			var path=document.getElementById('fetchmail-import-path-$tt').value;
			Set_Cookie('fetchmail-import-path',path,'3600', '/', '', '');
			XHR.appendData('fetchmail-import-path',path);
			AnimateDiv('simple-$tt');
			XHR.sendAndLoad('$page', 'POST',x_ImportFetchNow$tt);	
		}	
	
	
		function FetchMailImportLogs$tt(){
			LoadAjax('simple-$tt','$page?get-logs=yes&tt=$tt');
		}
		
		FetchMailImportLogs$tt();
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function perform(){
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?import=yes&path=".base64_encode($_POST["fetchmail-import-path"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{importation_background_text}",1);
	
}

function perform_import_compiled(){
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?import-compiled=yes&path=".base64_encode($_POST["fetchmail-import-compiled-path"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{importation_background_text}",1);	
}

function events(){
	if(!is_file("ressources/logs/web/fetchmail.import.log")){
		echo "<div style='font-size:12px'><code>Waiting....</code></div>\n";
		return;
		
	}
	$f=file("ressources/logs/web/fetchmail.import.log");
	while (list ($num, $ligne) = each ($f) ){
		$ligne=str_replace("\r", "", $ligne);
		$ligne=str_replace("\n", "", $ligne);
		$ligne=str_replace('"', "", $ligne);
		if(trim($ligne)==null){continue;}	
		echo "<div style='font-size:12px;text-align:left'><code>$ligne</code></div>\n";
	}
		
		
}


function dump(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	
	
	$UploadAFile=$tpl->javascript_parse_text("{upload_backup}");
	$allowedExtensions="allowedExtensions: ['gz'],";
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);

	
	
	
	
	$html="
	
	<div class=explain style='font-size:16px'>{restore_fetchmail_container}</div>
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
			//select-file: '{$_GET["select-file"]}'
	},
	onComplete: function(id, fileName){
		PathUploaded(fileName);
		}
	});
	
	}
	
	function PathUploaded(fileName){
		LoadAjax('file-uploader-demo1','$page?file-uploader-demo1=yes&fileName='+fileName);
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
	
		$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		$result = $uploader->handleUpload($content_dir);
	
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
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$fileNameEnc=urlencode($fileName);
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>{restoring_data} $fileName</div>");
	echo "$text<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','$page?restore=$fileNameEnc');
	</script>
	
		";
	
	}
function restore(){
		$tpl=new templates();
		$t=time();
		$page=CurrentPageName();
		$fileName=$_GET["restore"];
		$sock=new sockets();
		$fileName=urlencode($fileName);
		$data=base64_decode($sock->getFrameWork("fetchmail.php?restore-root=$fileName"));
	
		if($data==null){
			echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{failed}</div>");
			return;
		}
	
		
	
	
		$text=$tpl->_ENGINE_parse_body("<div style='font-size:14px;text-align:left'>$data</div>");
		echo "$text<div id='$t'></div>
		<script>
			if( document.getElementById('FETCHMAIL_FLEXRT') ){
				$('#'+  document.getElementById('FETCHMAIL_FLEXRT').value).flexReload();
			}
		
		</script>
		";
	
	}