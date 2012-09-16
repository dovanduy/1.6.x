<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["upload-pic-js"])){upload_pic_js();exit;}
if(isset($_GET["upload-pic-popup"])){upload_pic_popup();exit;}

if(isset($_GET["content"])){content();exit;}
if(isset($_POST["displayName"])){save();exit;}
if( isset($_GET['TargetpathUploaded']) ){upload_form_perform();exit();}


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
	$users=new usersMenus();
	$ct=new user($_SESSION["uid"]);
	$t=time();
	
	if($users->AllowChangeUserPassword){
		$password="
		<tr>
			<td class=legend>{password}:</td>
			<td>". Field_password("password-$t",$ct->password)."</td>
		</tr>";
		
	}
	
	
	$picture="ressources/$ct->ThumbnailPath";
	if(is_file("$picture")){
		$picture="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadm.profile.php?upload-pic-js=yes');\"><img src='ressources/$ct->ThumbnailPath' style='margin:10px'></a>";
	}
	
	$html="
	<div class=BodyContent>
		<table style='width:100%'>
		<tr>
		<td valign='top'>$picture</td>
		<td valign='top'>
		<H1>{myaccount}</H1>
		<p>{myaccount_text}</p>
		</td>
		</tr>
		</table>
	</div>
	<div class=BodyContent>
		<div id='anim-$t'></div>
		<table style='width:100%'>
		<tr>
			<td class=legend>{displayName}:</td>
			<td>". Field_text("displayName-$t",$ct->DisplayName)."</td>
		</tr>
		<tr>
			<td class=legend>{sn}:</td>
			<td>". Field_text("sn-$t",$ct->sn)."</td>
		</tr>		
		<tr>
			<td class=legend>{givenName}:</td>
			<td>". Field_text("givenName-$t",$ct->givenName)."</td>
		</tr>	
		$password	
		<tr>
			<td class=legend>{telephoneNumber}:</td>
			<td>". Field_text("telephoneNumber-$t",$ct->telephoneNumber)."</td>
		</tr>		
		<tr>
			<td class=legend>{mobile}:</td>
			<td>". Field_text("mobile-$t",$ct->mobile)."</td>
		</tr>	

		<tr>
			<td colspan=2 align='right' class='right'>
				<hr>
				". button("{apply}", "SaveAccount$t()","18px")."
			</td>
		</tr>
		
		</table>
	</div>
	
<script>
	var x_SaveAccount$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('anim-$t').innerHTML='';
			if(results.length>3){alert(results);return;}
			AjaxTopMenu('headNav','miniadm.index.php?headNav=yes');
		}		
		
		function SaveAccount$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('password-$t')){
				var pp=encodeURIComponent(document.getElementById('password-$t').value);
				XHR.appendData('password',pp);
			}
			var sn=encodeURIComponent(document.getElementById('sn-$t').value);
			XHR.appendData('displayName',encodeURIComponent(document.getElementById('displayName-$t').value));
			XHR.appendData('sn',sn);
			XHR.appendData('givenName',encodeURIComponent(document.getElementById('givenName-$t').value));
			XHR.appendData('telephoneNumber',document.getElementById('telephoneNumber-$t').value);
			XHR.appendData('mobile',document.getElementById('mobile-$t').value);
			AnimateDiv('anim-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveAccount$t);			
		
		}
</script>	
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
		$ct=new user($_SESSION["uid"]);
		$ct->DisplayName=url_decode_special_tool($_POST["displayName"]);
		$ct->sn=url_decode_special_tool($_POST["sn"]);
		$ct->givenName=url_decode_special_tool($_POST["givenName"]);
		$ct->telephoneNumber=$_POST["telephoneNumber"];
		$ct->mobile=$_POST["mobile"];
		if(isset($_POST["password"])){
			$ct->password=url_decode_special_tool($_POST["password"]);
		}
		if(!$ct->add_user()){
			echo $ct->error;
		}
}

function upload_pic_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{upload_your_picture}");
	$html="YahooWin2('550','$page?upload-pic-popup=yes','$title')";
	echo $html;
}
function upload_pic_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_your_picture}");
	$allowedExtensions="'jpg','jpeg','png'"; 
	
	
	
	$targetpath=base64_decode($_GET["upload-file"]);
	if($allowedExtensions<>null){
		$allowedExtensions="allowedExtensions: [$allowedExtensions],"; 
	}
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$html="
	<div id='file-uploader-demo1' style='width:100%;text-align:center'>		
		<noscript>			
			<!-- or put a simple form for upload here -->
		</noscript>         
	</div>	
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
				        TargetpathUploaded: '{$_GET["upload-file"]}',
				        //select-file: '{$_GET["select-file"]}'
				    },
				onComplete: function(id, fileName){
					document.location.reload();
				}
            });           
        }
        
       createUploader();   
    </script>    	
	";
	
	//$html="<iframe style='width:100%;height:250px;border:1px' src='$page?form-upload={$_GET["upload-file"]}&select-file={$_GET["select-file"]}'></iframe>";
	echo $html;	
	
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

$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
$result = $uploader->handleUpload($content_dir);

writelogs("upload_form_perform() -> OK $resultTXT",__FUNCTION__,__FILE__,__LINE__);

$TargetpathUploaded=base64_decode($_GET["TargetpathUploaded"]);

if(!is_file("$content_dir$fileName")){
	die ("{error: \"$content_dir$fileName no such file \"}");
}
   
	$user=new user($_SESSION["uid"]);
	$jpegPhoto_datas=file_get_contents("$content_dir$fileName"); 
	$user->add_user(); 
	writelogs("PHOTO: Edit: ". strlen($jpegPhoto_datas)." bytes",__FUNCTION__,__FILE__,__LINE__);
	if(!$user->SaveUserPhoto($jpegPhoto_datas)){
		die ("{error: \"$user->ldap_error\"}");
	}
		
    if(is_file($user->thumbnail_path)){unlink($user->thumbnail_path);} 
    $user->draw_jpeg_photos("$content_dir$fileName"); 
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;
		
}