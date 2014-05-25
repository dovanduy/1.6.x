<?php
session_start();$_SESSION["MINIADM"]=true;

include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_GET["verbose"])){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_TEMPLATE"]=true;
	$GLOBALS["DEBUG_LANG"]=true;
}

if(!isset($_SESSION["uid"])){
	echo "<H1>NO SESSION</H1>";
	die();
}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");

include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["upload-pic-js"])){upload_pic_js();exit;}
if(isset($_GET["upload-pic-popup"])){upload_pic_popup();exit;}
if(isset($_GET["lang"])){lang_popup();exit;}
if(isset($_POST["lang"])){lang_save();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_POST["DisplayName"])){save();exit;}
if( isset($_GET['TargetpathUploaded']) ){upload_form_perform();exit();}
if(isset($_GET["privileges"])){privileges();exit;}

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
	$VirtualUser=$_SESSION["VirtAclUser"];
	
	if(isset($_SESSION["RADIUS_ID"])){if($_SESSION["RADIUS_ID"]>0){$VirtualUser=true;}}
	if(!$VirtualUser){$ct=new user($_SESSION["uid"]);}else{$ct=new user();}
	
	$t=time();
	
	$ActiveDirectory=0;
	if($ct->AsActiveDirectoryMember){$ActiveDirectory=1;}
	$boot=new boostrap_form();
	if($VirtualUser){$ct->DisplayName=$_SESSION["uid"];}

	if($_SESSION["uid"]==-100){
		include("ressources/settings.inc");
		$ct->DisplayName=$_GLOBAL["ldap_admin"];
		$ct->sn=$ct->DisplayName;
		$ct->givenName=$ct->DisplayName;
		$VirtualUser=true;
	}
	$boot->set_field("DisplayName", "{displayName}", $ct->DisplayName);
	$boot->set_field("sn", "{sn}", $ct->sn);
	$boot->set_field("givenName", "{givenName}", $ct->givenName);
	if($users->AllowChangeUserPassword){$boot->set_fieldpassword("password", "{password}", $ct->password);}
	$boot->set_field("telephoneNumber", "{telephoneNumber}", $ct->telephoneNumber);
	$boot->set_field("mobile", "{mobile}", $ct->mobile);
	$boot->set_button("{apply}");
	$boot->set_CallBack("AjaxTopMenu('headNav','miniadm.index.php?headNav=yes');");
	
	if($VirtualUser){
		$boot->set_form_locked();
	}else{
		if($ActiveDirectory==1){$boot->set_form_locked();}
	}
	
	$picture="ressources/$ct->ThumbnailPath";

	if(is_file("$picture")){
		$picture="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('miniadm.profile.php?upload-pic-js=yes');\">
		<img src='ressources/$ct->ThumbnailPath' style='margin:10px'></a>";
	}else{
		$picture=null;
	}
	
	$form=$boot->Compile();
	$language=$tpl->javascript_parse_text("{language}");
	$html="
	<div class=BodyContent>
		<table style='width:100%'>
		<tr>
		<td valign='top'>$picture</td>
		<td valign='top'>
		<H1>{myaccount}</H1>
		<p>{myaccount_text}</p>
		<div style='text-align:right'>
		<a href=\"javascript:blur();\" OnClick=\"YahooWin3(500,'$page?lang=yes','$language');\">
		$language</a>&nbsp;|&nbsp;
		<a href=\"javascript:blur();\" OnClick=\"YahooWin3(500,'$page?privileges=yes','{my_privileges}');\">
		{my_privileges}</a></div>
		</td>
		</tr>
		</table>
	</div>
	<div class=BodyContent>
		$form
		
	</div>

	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){	
		$ct=new user($_SESSION["uid"]);
		$ct->DisplayName=url_decode_special_tool($_POST["DisplayName"]);
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

function privileges(){
	$tpl=new templates();
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "<H1>".__FUNCTION__ ."(line ".__LINE__.")</H1>\n";}
	$EnableSambaVirtualsServers=0;
	include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
	$cr=new TranslateRights(null, null);
	$r=$cr->GetPrivsArray();
	$users=new usersMenus();
	
	$ldap=new clladp();
	$ht=array();
	$ht[]="<div style='width:98%' class=form>";
	
	$ht[]="<table style='width:99%'>";
	if($ldap->IsKerbAuth()){
		if($GLOBALS["VERBOSE"]){echo "<li><strong>IsKerbAuth = TRUE (line ".__LINE__.")</strong></li>\n";}
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ht[]="<div style='font-size:18px;font-weight:bold'>{my_microsoft_groups}</div>";
		$ad=new external_ad_search();
		$groups=$ad->GroupsOfMember($_SESSION["uid"]);
		
		while (list ($dn, $name) = each ($groups) ){
			
			
				$ht[]="<tr>
					<td width=1% valign='top'><img src='img/arrow-right-16.png'></td>
					<td><span style='font-size:14px;font-weight:bold'>$name</span>
						<br><span style='font-size:10px'>&nbsp;($dn)</span></td>
				</tr>";
			
		}	
		
	}
	
	
	
	
	
	while (list ($key, $val) = each ($r) ){
		if($GLOBALS["VERBOSE"]){echo "<li>$key = \"$val\"</li>\n";}
		if($users->$key){
			$ht[]="<tr><td width=1%><img src='img/arrow-right-16.png'></td><td><span style='font-size:14px'>{{$key}}</span></td></tr>";
		}
	}
	
	$users=new usersMenus();
	if($users->SAMBA_INSTALLED){
		$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
		if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
	}
	
	if($EnableSambaVirtualsServers==1){
		if(count($_SESSION["VIRTUALS_SERVERS"])>0){
			$ht[]="<tr><td colspan=2 style='font-size:16px;font-weight:bolder'>{virtual_servers}</td></tr>";
			while (list ($key, $val) = each ($_SESSION["VIRTUALS_SERVERS"]) ){
				$ht[]="<tr><td width=1%><img src='img/arrow-right-16.png'></td><td><span style='font-size:14px'>$key</span></td></tr>";
			}
		}
	}
	
	$ht[]="</table></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $ht));
}

function lang_popup(){
	$htmlT=new htmltools_inc();
	$page=CurrentPageName();
	$lang=$htmlT->LanguageArray();
	$tpl=new templates();
	$boot=new boostrap_form();
	$boot->set_list("lang", "{language}",$lang, $tpl->language,array("COOKIE"=>"artica-language"));
	$boot->set_AjaxFinal("window.location.href='$page';");
	$boot->set_button("{apply}");
	$boot->set_CloseYahoo("YahooWin3");
	echo $boot->Compile();
	
	
	
	
}

function lang_save(){
	if($_POST["lang"]==null){echo "No language set...\n";}
	$_SESSION["detected_lang"]=$_POST["lang"];
	$_SESSION["OU_LANG"]=$_POST["lang"];
	$_COOKIE["artica-language"]=$_POST["lang"];
	unset($_SESSION["translation"]);
	unset($_SESSION["MINIADM_TOP_MENU"]);
	while (list ($key, $val) = each ($_SESSION)){
		if(preg_match("#\.php$#", $key)){unset($_SESSION[$key]);}
	}
	
}