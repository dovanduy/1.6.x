<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["popup"])){popup();exit;}
if( isset($_GET['TargetpathUploaded']) ){upload_form_perform();exit();}
if(isset($_GET["UploadedCsv"])){UploadedCsv();exit;}
main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
$html="<div class=BodyContent>
		<H1>{import_contacts}</H1>
		<p>{import_contacts_csv_text}</p>
	</div>
<div class=BodyContentWork id='$t'></div>

<script>LoadAjax('$t','$page?popup=yes')</script>

";	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$UploadAFile=$tpl->javascript_parse_text("{upload_a_file}");
	$allowedExtensions="allowedExtensions: ['csv'],";
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$html="
	<p style='font-size:16px'>{import_contacts_csv_explain}</p>	
	<div style='text-align:right'><a href=\"bin/install/zarafa/example.csv\" style='font-weight:bold;text-decoration:underline'>example.csv</a></div>
	<table class=form style='width:99%;margin-top:20px'>
	<tr>
		<td class=legend valign='top'>{file}:</td>
		<td>	
		<div id='file-uploader-demo1' style='width:100%;text-align:center'>		
		<noscript>			
			<!-- or put a simple form for upload here -->
		</noscript>         
		</div>	
	</td>
	<tr>
		<td colspan=2><div id='message-$t'></div>		
	</tr>
	</table>


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
				        TargetpathUploaded: '{$_SESSION["uid"]}',
				        //select-file: '{$_GET["select-file"]}'
				    },
				onComplete: function(id, fileName){
					var pp=encodeURIComponent(fileName);
					LoadAjax('message-$t','$page?UploadedCsv=yes&filename='+fileName);
					
				}
            });           
        }
        
       createUploader();   
    </script>    	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}			
			
function upload_form_perform(){

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

	$content_dir=dirname(__FILE__)."/ressources/conf/upload/{$_SESSION["uid"]}/";
	if(!is_dir($content_dir)){@mkdir($content_dir,0777);}
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir);

	writelogs("upload_form_perform() -> OK $result",__FUNCTION__,__FILE__,__LINE__);

	$TargetpathUploaded=base64_decode($_GET["TargetpathUploaded"]);

	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName ok -> $TargetpathUploaded",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		//$rettun=$sock->getFrameWork("cmd.php?move_uploaded_file='{$_GET["TargetpathUploaded"]}&src=". base64_encode("$content_dir/$fileName"));
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;

	}else{
		die ("{error: \"$content_dir$fileName no such file\"}");
	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;

}

function UploadedCsv(){
	$tpl=new templates();
	$filename=dirname(__FILE__)."/ressources/conf/upload/{$_SESSION["uid"]}/".url_decode_special_tool($_GET["filename"]);
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($sock->getFrameWork("zarafa.php?import-contacts=yes&uid=".base64_encode($_SESSION["uid"])."&filename=".base64_encode($filename)));
}
	
	




