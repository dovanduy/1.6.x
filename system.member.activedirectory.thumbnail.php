<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/charts.php');
	include_once('ressources/class.mimedefang.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.contacts.inc');
	writelogs("get requests",__FUNCTION__,__FILE__,__LINE__);
	
	if( isset($_POST['upload']) ){
		writelogs("Uploading photo....",__FUNCTION__,__FILE__,__LINE__);
		PhotoUploaded();
	}
	
	$usersmenus=new usersMenus();
	if($usersmenus->AllowAddGroup==false){
		if(isset($_GET["uid"])){
			if($_SESSION["uid"]<>$_GET["uid"]){die('No permissions');}
		}
	}
		
		
			
$page=CurrentPageName();		
$hidden=null;	
$html="<p>&nbsp;</p>
<div id='content' style='width:400px'>
<table style='width:100%'>
<tr>
<td valign='top'>
<h3 style='font-size:22px'>{edit_photo_title}</h3>
<p style='font-size:18px'>{edit_photo_text}</p>
<div style='color:#d32d2d'>{$GLOBALS["Photo_error"]}</div>
<form method=\"post\" enctype=\"multipart/form-data\" action=\"$page\">
<input type='hidden' name='DN' id='DN' value=\"{$_GET["DN"]}\">
<p>
<input type=\"file\" name=\"photo\" size=\"30\">
<input type='submit' name='upload' value='{upload file}&nbsp;&raquo;' style='width:90px'>
</p>
</form>
</td>
<td valign='top'><img src='$user->img_identity'>
</td>
</div>

";	
$GLOBALS["template_users_no_cache"]=true;
$tpl=new template_users('{edit_photo_title}',$html,0,1,1);
echo  $tpl->web_page;
	
	
function PhotoUploaded(){
	$tmp_file = $_FILES['photo']['tmp_name'];
	$content_dir=dirname(__FILE__)."/ressources/conf/upload";
	if(!is_dir($content_dir)){@mkdir($content_dir);}
	if( !@is_uploaded_file($tmp_file) ){
		writelogs("PHOTO: error_unable_to_upload_file",__FUNCTION__,__FILE__,__LINE__);
		$GLOBALS["Photo_error"]='{error_unable_to_upload_file} '.$tmp_file;
		return;
	}
	$name_file = $_FILES['photo']['name'];

if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
 if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){
 	$GLOBALS["Photo_error"]="{error_unable_to_move_file} : ". $content_dir . "/" .$name_file;
 	writelogs("PHOTO: {error_unable_to_move_file} : ". $content_dir . "/" .$name_file,__FUNCTION__,__FILE__,__LINE__);
 	return;
 }
     
    $file=$content_dir . "/" .$name_file;
    writelogs("PHOTO: $file",__FUNCTION__,__FILE__,__LINE__);
    $jpegPhoto_datas=file_get_contents($file); 
    
    $ad=new external_ad_search();
    
	if(!$ad->SaveUserPhoto($jpegPhoto_datas,$_POST["DN"])){
		$GLOBALS["Photo_error"]=$ad->ldap_error;
		return;
	}
	
}

?>	
	