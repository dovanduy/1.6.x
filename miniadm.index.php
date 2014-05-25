<?php
session_start();
setcookie("MINIADM", "YES", time()+1000);
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',"<p class=text-error style='color:red'>");
		ini_set('error_append_string',"</p>");
}
if(!isset($_SESSION["uid"])){
	writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);
	header("location:miniadm.logon.php");
	die();
}
BuildSessionAuth();

if(isset($_POST["SourceParams"])){SourceParams();exit;}

if(isset($_GET["top-menu"])){top_menu();exit;}
if(isset($_GET["left-menu"])){left_menu();exit;}
if(isset($_GET["upload-pic-js"])){upload_pic_js();exit;}
if(isset($_GET["upload-pic-popup"])){upload_pic_popup();exit;}
if( isset($_GET['TargetpathUploaded']) ){upload_form_perform();exit();}

if(isset($_GET["content"])){content_start();exit;}
if(isset($_GET["headNav"])){headNav();exit;}
if(isset($_GET["right-top-menus"])){right();exit;}
if(isset($_POST["GetMyTitle"])){GetMyTitle();exit;}
if(isset($_GET["left-content-id"])){left();exit;}
if(isset($_GET["aero"])){aero();exit;}
if(isset($_GET["dashboard"])){dashboard();exit;}
main_page();
exit;




if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	
	
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','miniadm.index.php?content=yes')</script>", $content);
	echo $content;
	
	
}
function SourceParams(){
	$SourceParams=unserialize(base64_decode($_POST["SourceParams"]));
	$SourceParams[$_POST["KeyParams"]]=$_POST["value"];
	$SourceParams["FIRST"]=$_POST["KeyParams"];
	echo base64_encode(serialize($SourceParams));
}

function top_menu(){
	if(isset($_SESSION["MINIADM_TOP_MENU"])){echo $_SESSION["MINIADM_TOP_MENU"];return;}
	$page=CurrentPageName();
	$mini=new miniadm();
	$_SESSION["MINIADM_TOP_MENU"]=$mini->NavBar()."
	<script>
		LoadAjax('left-menu','$page?left-menu=yes');

function x_ChangeHTMLTitle(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>0){
		document.title=tempvalue;
    }else{
    	document.title=\"!!! Error !!!\";
    }
}
function ChangeHTMLTitleEndUsersPerform(){
	var XHR = new XHRConnection();
	XHR.appendData('GetMyTitle','yes');
	XHR.sendAndLoad(\"$page\", 'POST',x_ChangeHTMLTitle);	
}	
	setTimeout('ChangeHTMLTitleEndUsersPerform()',500);		
		
	</script>
	";
	
	echo $_SESSION["MINIADM_TOP_MENU"];
}

function left_menu(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$miniadm=new miniadm();
	writelogs("->leftmenu()",__FUNCTION__,__FILE__,__LINE__);
	echo $miniadm->leftmenu();
}

function headNav(){
	if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	$page=CurrentPageName();
	$ct=new user($_SESSION["uid"]);
	if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}	
	$users=new usersMenus();
	$picture="/img/defaultFbProfileUser50x50.jpg";
	if(preg_match("#\/thumbnail-96-(.+?)$#", $ct->ThumbnailPath,$re)){
		if(is_file("ressources/profiles/icons/thumbnail-50-{$re[1]}")){
			$picture="ressources/profiles/icons/thumbnail-50-{$re[1]}";
		}
	}

	if($users->POSTFIX_INSTALLED){
	
	$messaging="<li class=\"navItem middleItem\">
				<a class=\"navLink bigPadding\" href=\"miniadm.messaging.php\" id=\"findFriendsNav\">{messaging}</a>
			</li>";
	}
	
	
$html="
	<div class=\"rfloat\">
		<ul id=\"pageNav\" class=\"clearfix\">
			<li class=\"navItem firstItem ThumbAccount\">
				<a href=\"miniadm.profile.php\" title=\"Profil\">
					<img class=\"ThumbAccountPhoto\" src=\"$picture\" />
						<span class=\"ThumbAccountName\">$ct->DisplayName</span></a>
				</li>
				$messaging
				<li class=\"navItem middleItem\" id=\"navHome\">
					<a class=\"navLink bigPadding\" href=\"miniadm.index.php\" accesskey=\"1\">{home}</a>
				</li>
		</ul>
	</div>
<script>
	
	
	
function x_ChangeHTMLTitle(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>0){
		document.title=tempvalue;
    }else{
    	document.title=\"!!! Error !!!\";
    }
}
function ChangeHTMLTitleEndUsersPerform(){
	var XHR = new XHRConnection();
	XHR.appendData('GetMyTitle','yes');
	XHR.sendAndLoad(\"$page\", 'POST',x_ChangeHTMLTitle);	
}	
LoadAjax('right-top-menus','$page?right-top-menus=yes');
setTimeout('ChangeHTMLTitleEndUsersPerform()',500);
</script>	
";	

$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
$_SESSION[__FILE__][__FUNCTION__]=$html;
echo $html;
	
	
}

function aero(){
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return;}
	
	$q=new mysql();
	
		$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate, 
		MINUTE(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY `time` ,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d %H') ORDER BY `time`";
		
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){die();}
	if(mysql_num_rows($results)<2){die();}
	
	
	$t=time();
	header("content-type: application/x-javascript");
	echo "
	Loadjs('miniadm.about.php?graph1=yes&time=hour&container=herounit',true);
	document.getElementById('herounit').className ='';		
	";
	
	
	
	
}



function content_start(){
	$page=CurrentPageName();
	$uid=$_SESSION["uid"];
	$ct=new user($_SESSION["uid"]);
	$t=time();
	$error=null;
	$OUTEXT=$_SESSION["ou"];
	$base="ressources/profiles/icons";
	if($ct->DisplayName==null){$ct->DisplayName=$_SESSION["uid"];}	
	if($OUTEXT==null){
		$sock=new sockets();
		$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
		$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
		$OUTEXT=$LicenseInfos["COMPANY"];
		
	}
	$browser=browser_detection();
	if($browser=="ie"){
		$error="<p class=text-error>{ie_not_really_compatible}</p>";
	}
	$html="$error
	<div class='hero-unit' id='herounit'>
		<h1 style='text-transform:capitalize'>$OUTEXT</h1>
		<h2>{about_this_section}.</h2>
		<p>{enduser_explain_section}</p>
	</div>
	<div id='dashboard' style='min-height:220px'></div>	
	<div class=\"row-fluid\" id='$t'></div>
	
	
	<script>
		function Aero$t(){
			Loadjs('$page?aero=yes');
		
		}
	
		LoadAjax('$t','$page?right-top-menus=yes&t=$t');
		setTimeout('Aero$t()',800);
	</script>";
	
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	$html=str_replace("%ORGA", $OUTEXT, $html);
	echo $html;	
}










function choose_language(){
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$tpl=new templates();
	$htmltools=new htmltools_inc();
	$lang=$htmltools->LanguageArray();	
	$page=CurrentPageName();
	
	$lang[null]="{select}";
	$html="<table style='width:99%' class=form>
	<tr>
		<td valign='top'>{language}: ($tpl->language)</td>
		<td>". Field_array_Hash($lang,"miniconfig-select-lang",$tpl->language,"style:font-size:16px;")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}","ChangeLang()")."</td>
	</tr>
	</table>
	
	<script>
	
	var x_ChangeLang= function (obj) {
		var response=obj.responseText;
		location.reload();
	}	
	
	function ChangeLang(){
		var lang=document.getElementById('miniconfig-select-lang').value;
		Set_Cookie('artica-language', lang, '3600', '/', '', '');
		var XHR = new XHRConnection();
		XHR.appendData('miniconfig-POST-lang',lang);
		XHR.sendAndLoad('$page', 'POST',x_ChangeLang);		
		location.reload();
	}
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function choose_language_save(){
	session_start();
	$_SESSION["detected_lang"]=$_POST["miniconfig-POST-lang"];
	writelogs("Unset array of ".count($_SESSION["translation"]),__FUNCTION__,__FILE__,__LINE__);
	unset($_SESSION["translation"]);
	writelogs("-> remove cached",__FUNCTION__,__FILE__,__LINE__);
	REMOVE_CACHED(null);
	writelogs("-> setcookie",__FUNCTION__,__FILE__,__LINE__);
	setcookie("artica-language", $_POST["miniconfig-POST-lang"], time()+172800);
	
	$tpl=new templates();	
}

function is_admin_proxy($dump=false){
	$users=new usersMenus();
	$sock=new sockets();
	$isproxy=false;
	if($users->SQUID_INSTALLED){$isproxy=true;}
	if($users->WEBSTATS_APPLIANCE){$isproxy=true;}
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$isproxy=false;}
	
	if(!$isproxy){return false;}
	
	if($users->AsProxyMonitor){return true;}
	if($users->AsAnAdministratorGeneric){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}

}

function dashboard_proxy(){
	if(!function_exists("dashboard_box")){return;}
	$tpl=new templates();
	$users=new usersMenus();
	$cpunum=intval($users->CPU_NUMBER);
	$array_load=sys_getloadavg();
	$org_load=$array_load[2];
	$load=intval($org_load);
	$max_vert_fonce=$cpunum;
	$max_vert_tfonce=$cpunum+1;
	$max_orange=$cpunum*0.75;
	$max_over=$cpunum*2;
	$purc1=$load/$cpunum;
	$sock=new sockets();
	$systemMaxOverloaded=$sock->GET_INFO("systemMaxOverloaded");
	if(!is_numeric($systemMaxOverloaded)){$systemMaxOverloaded=17;}
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	
	$text=$tpl->_ENGINE_parse_body("{max_load_explain}");
	$text=str_replace("%maxloadavg", $max_vert_tfonce, $text);
	$text=str_replace("%maxloadalert", $systemMaxOverloaded, $text);
	
	
	$a[]=dashboard_box("{computer_load}",$internal_load,null,"$internal_load / $max_vert_fonce max:$systemMaxOverloaded");
	
	exec("/usr/bin/free -m" ,$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Mem:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
			$MEM_TOTAL=$re[1];
			$MEM_USED=$re[2];
			$MEM_FREE=$re[3];
			$MEM_TOTAL_UNIT="MB";
			$MEM_USED_UNIT="MB";
			$MEM_FREE_UNIT="MB";
			$POURC=$MEM_USED/$MEM_TOTAL;
			$POURC=$POURC*100;
			
			if($MEM_TOTAL>1000){$MEM_TOTAL=$MEM_TOTAL/1000;$MEM_TOTAL_UNIT="GB";}
			if($MEM_USED>1000){$MEM_USED=$MEM_USED/1000;$MEM_USED_UNIT="GB";}
			if($MEM_FREE>1000){$MEM_FREE=$MEM_FREE/1000;$MEM_FREE_UNIT="GB";}
			
			
			if($POURC>0){
				$POURC=round($POURC,1);
				$MEM_TOTAL=round($MEM_TOTAL,1);
				$MEM_USED=round($MEM_USED,1);
				$a[]=dashboard_box("{memory_used}","{$POURC}%",null,"{$MEM_USED}{$MEM_USED_UNIT} / {$MEM_TOTAL}{$MEM_TOTAL_UNIT}    {free} {$MEM_FREE}{$MEM_FREE_UNIT} ");
			}
		}
	}

	$last=null;
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.counters.db";
	if(is_file($cacheFile)){
		$ARRAY=unserialize(@file_get_contents($cacheFile));
		if(is_array($ARRAY)){
			if(isset($ARRAY["SAVETIME"])){
				$time=$ARRAY["SAVETIME"];
				$last=distanceOfTimeInWords($time,time(),true);
			}
			if(isset($ARRAY["client_http.requests"])){
				if(preg_match("#([0-9\.]+)#", $ARRAY["client_http.requests"],$re)){$ARRAY["client_http.requests"]=$re[1];}
				$client_http_requests=round($ARRAY["client_http.requests"],1);
				$a[]=dashboard_box("{requests_second}","$client_http_requests",null,$last);
		}
		
		if(isset($ARRAY["client_http.requests"])){
			if(preg_match("#([0-9\.]+)#", $ARRAY["server.all.kbytes_in"],$re)){$ARRAY["server.all.kbytes_in"]=$re[1];}
			$kbytes_in=FormatBytes($ARRAY["server.all.kbytes_in"]);
			$kbytes_in_text=$kbytes_in;
			if(preg_match("#([0-9\.,]+)#", $kbytes_in,$re)){$kbytes_in_text=$re[1];}
			$a[]=dashboard_box("{bandwidth}",$kbytes_in_text,null,"$kbytes_in / {second} - $last");
		}
		if(isset($ARRAY["active_requests"])){
			if(preg_match("#([0-9\.]+)#", $ARRAY["active_requests"],$re)){$ARRAY["active_requests"]=$re[1];}
		
			$a[]=dashboard_box("{sessions}",$ARRAY["active_requests"],"{simultaneous_sessions}","$last");
		}
		
		
	}
	
	
	}
	$page=CurrentPageName();
	$t=time();
	echo "
		<div id='box-holder' style='display: block;overflow: hidden;position: relative; width:1220px !important'>
				".@implode("\n", $a)."
		</div>
		<script>
			function upd$t(){
				LoadAjaxTiny('dashboard','$page?dashboard=yes');
				
			}
			
			setTimeout('upd$t()',12000);
		</script>
			
	";
}
function dashboard(){
	if(is_admin_proxy()){
		dashboard_proxy();
		return;
	}	
}

function right(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$u=new user($_SESSION["uid"]);
	$t=$_GET["t"];
	$mydn=base64_encode($u->dn);
	
	$p1=Paragraphe32("myaccount", "myaccount_text", "MyHref('miniadm.profile.php')", "identity-32.png");
	$p2=Paragraphe32("logoff", "logoff_text", "MyHref('miniadm.logoff.php')", "shutdown-computer-32.png");
	
	if($users->AsOrgAdmin){
		$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("new_member","new_member_explain_in_ou",
		"javascript:Loadjs('create-user.php')","plus-24.png");
		
	}
	
	if(!$_SESSION["VirtAclUser"]){
		$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("myaccount","myaccount_text","miniadm.profile.php");
	}
	$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("logoff","logoff_text","miniadm.logoff.php","shutdown-computer-24.png");
			
			
		
	$t=time();
	
	$html="
	
		
	<H1>{what_to_do}</H1>
	<table style='width:100%'>
	".@implode("", $tt)."
	</table>
	<script>
		
		LoadAjaxTiny('dashboard','$page?dashboard=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function info_messaging(){
	$ldap=new clladp();
	$users=new usersMenus();
	$usersNB=$ldap->CountDeDomainsOU($_SESSION["ou"]);
	$ouencoded=base64_encode($_SESSION["ou"]);
return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/128-catch-all.png' OnClick=\"javascript:Loadjs('domains.edit.domains.php?js=yes&ou=$ouencoded&encoded=yes&in-front-ajax=yes');></td>
		<td valign='top'><H3 style='font-weight:bold'>{messaging}: $usersNB {domains}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('domains.edit.domains.php?js=yes&ou=$ouencoded&encoded=yes&in-front-ajax=yes');\" 
				style='font-size:13px;font-weight:normal'>{localdomains_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";	
	
	
}

function info_Dansguardian(){

return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/www-web-secure-128.png' OnClick=\"javascript:Loadjs('miniadm.webfiltering.index.php');\" ></td>
		<td valign='top'><H3 style='font-weight:bold'>{WEB_FILTERING}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('miniadm.webfiltering.index.php');\" 
				style='font-size:13px;font-weight:normal'>{miniadm_web_filtering_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";		
	
}

function info_organization(){
	$ldap=new clladp();
	$usersNB=$ldap->CountDeUSerOu($_SESSION["ou"]);
	
	return "
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/users-info-128.png' OnClick=\"javascript:Loadjs('domains.add.user.php?ou={$_SESSION["ou"]}');\" ></td>
		<td valign='top'><H3 style='font-weight:bold'>{$_SESSION["ou"]}: $usersNB {members}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('domains.add.user.php?ou={$_SESSION["ou"]}');\" 
				style='font-size:13px;font-weight:normal'>{add_user}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>
	";
	
	
	
}

function GetMyTitle(){
	
	echo $_SERVER["SERVER_NAME"]." >> {$_SESSION["ou"]} >> {$_SESSION["uid"]}";
	
}

function upload_pic_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{change_background_image}");
	$html="YahooWin2('550','$page?upload-pic-popup=yes','$title')";
	echo $html;
}
function upload_pic_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_your_picture} 851x315");
	$allowedExtensions="'jpg','jpeg','png'"; 
	
	
	
	
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
  	include_once(dirname(__FILE__).'/ressources/class.images.inc');
	$uid=$_SESSION["uid"];
	$base="ressources/profiles/icons";
	$user=new user($_SESSION["uid"]);
	$jpeg_filename="$content_dir$fileName";
	$jpegPhoto_datas=file_get_contents("$content_dir$fileName"); 
	$image=new images($jpeg_filename);
	$jpeg_dimensions=@getimagesize($jpeg_filename);
	$img_type=array(1=>"gif",2=>'jpg',3=>'png',4=>'swf',5=>'psd',6=>'bmp',7=>'tiff',8=>'tiff',9=>'jpc',10=>'jp2',11=>'jpx');
	$extension="{$img_type[$jpeg_dimensions[2]]}";
	$thumbnail_path="$base/background-{$uid}.$extension";
	if(!$image->thumbnail(851,315,$thumbnail_path)){
		die ("{error: \"Create image error\"}");
	}
	
	@file_put_contents("$base/background-{$uid}.loc", $thumbnail_path);
	
	
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;
		
}





?>