<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){
	writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);
	header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){
	writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);
	header("location:admin.index.php");
	die();
	}

if(isset($_GET["disk-tabs"])){disk_tabs();exit;}
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
if(isset($_GET["performances"])){performances();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["disk-usage"])){disk_usage();exit;}
main_page();
exit;


if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$page=CurrentPageName();
	
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function content_start(){
	$page=CurrentPageName();
	$uid=$_SESSION["uid"];
	$ct=new user($_SESSION["uid"]);
	$t=time();
	$base="ressources/profiles/icons";
	
	$users=new usersMenus();
	
	$html="
	<div class='hero-unit'>
		<h1 style='text-transform:capitalize'>{about2}</h1>
		<h2>Artica v$users->ARTICA_VERSION</h2>
		
	</div>
	
	<div class=\"row-fluid\" id='$t'></div>
	
	
	<script>LoadAjax('$t','$page?right-top-menus=yes');</script>";
	
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	$html=str_replace("%ORGA ", $_SESSION["ou"], $html);
	echo $html;	
}




function is_admin(){
	
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsAnAdministratorGeneric){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
	
}

function performances(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='graph1-$t' style='width:990px;height:450px'></div>
	<div id='graph2-$t' style='width:990px;height:450px'></div>
	
	<script>
		AnimateDiv('graph1-$t');
		AnimateDiv('graph2-$t');
		Loadjs('$page?graph1=yes&container=graph1-$t&time={$_GET["time"]}');
		Loadjs('$page?graph2=yes&container=graph2-$t&time={$_GET["time"]}');
	</script>
	
	
	";
	
	echo $html;
	
}

function graph1(){
	$tpl=new templates();
	if($_GET["time"]=="hour"){
		$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate, 
		MINUTE(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY `time` ,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d %H') ORDER BY `time`";
		
		$title="{server_load_this_hour}";
		$timetext="{minutes}";
		
	}
	
	if($_GET["time"]=="day"){
		$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate,
		HOUR(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY time,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY `time`";
	
		$title="{server_load_today}";
		$timetext="{hours}";
	
	}

	if($_GET["time"]=="week"){
		$sql="SELECT WEEK(zDate) as tdate,
		DAY(zDate) as `time`,AVG(loadavg) as value FROM `sys_loadvg` GROUP BY time,tdate
		HAVING tdate=WEEK(NOW()) ORDER BY `time`";
	
		$title="{server_load_this_week}";
		$timetext="{days}";
	
	}	
	
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	if(mysql_num_rows($results)<2){$tpl->javascript_senderror("{this_request_contains_no_data}<hr>$sql",$_GET["container"]);}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["time"];
		$ydata[]=round($ligne["value"],2);
	
	
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{load}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{load}"=>$ydata);
	echo $highcharts->BuildChart();
	
}
function graph2(){
	$tpl=new templates();
	if($_GET["time"]=="hour"){
		$sql="SELECT DATE_FORMAT( zDate, '%Y-%m-%d %H' ) AS tdate, MINUTE( zDate ) AS time, AVG( memory_used ) AS value
				FROM `sys_mem`
				GROUP BY `time` , tdate
				HAVING tdate = DATE_FORMAT( NOW( ) , '%Y-%m-%d %H' )
				ORDER BY `time`";

		$title="{memory_consumption_this_hour}";
		$timetext="{minutes}";

	}
	if($_GET["time"]=="day"){
		$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate,
		HOUR(zDate) as time,AVG(memory_used) as value FROM `sys_mem` GROUP BY `time`,tdate
		HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY `time`";
		
		$title="{memory_consumption_today}";
		$timetext="{hours}";		
		
	}
	
	if($_GET["time"]=="week"){
		$sql="SELECT WEEK(zDate) as tdate,
		DAY(zDate) as `time`,AVG(memory_used) as value FROM `sys_mem` GROUP BY time,tdate
		HAVING tdate=WEEK(NOW()) ORDER BY `time`";
	
		$title="{memory_consumption_this_week}";
		$timetext="{days}";
	
	}	
	
	
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);}
	
	if(mysql_num_rows($results)<2){
		$tpl->javascript_senderror("{this_request_contains_no_data}: (". mysql_num_rows($results).")",$_GET["container"]);
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["time"];
		$ligne["value"]=$ligne["value"]/1024;
		$ydata[]=round($ligne["value"],2);


	}

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{memory} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{memory}"=>$ydata);
	echo $highcharts->BuildChart();

}
function tabs_graphs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{performance}:{this_hour}"]="$page?performances=yes&time=hour";
	$array["{performance}:{today}"]="$page?performances=yes&time=day";
	$array["{performance}:{week}"]="$page?performances=yes&time=week";
	$array["{disk_usage}"]="$page?disk-tabs=yes";
	$array["{maintenance}"]="miniadm.maintenance.php";
	
	
	echo $boot->build_tab($array);	
	
}

function disk_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{partitions}"]="miniadm.partitions.php";
	$array["{mysql_databases}"]="miniadm.mysql.status.php";
	$array["{disk_usage}"]="philesight.php?popup=yes&no-form=yes";
	echo $boot->build_tab($array);
}



function right(){
	if(is_admin()){tabs_graphs();return;}
	
	
	if(!$GLOBALS["VERBOSE"]){
		if(isset($_SESSION[__FILE__][__FUNCTION__])){echo $_SESSION[__FILE__][__FUNCTION__];return;}
	}
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$u=new user($_SESSION["uid"]);
	
	$mydn=base64_encode($u->dn);
	
	$p1=Paragraphe32("myaccount", "myaccount_text", "MyHref('miniadm.profile.php')", "identity-32.png");
	$p2=Paragraphe32("logoff", "logoff_text", "MyHref('miniadm.logoff.php')", "shutdown-computer-32.png");
	
	if($users->AsOrgAdmin){
		$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("new_member","new_member_explain_in_ou",
		"javascript:Loadjs('create-user.php')","plus-24.png");
		
	}
	
	$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("myaccount","myaccount_text","miniadm.profile.php");
	$tt[]=SIMPLE_PARAGRAPHE_BLUE_ARROWTR("logoff","logoff_text","miniadm.logoff.php","shutdown-computer-24.png");
			
			
		
	
	
	$html="
	<H1>{what_to_do}</H1>
	<table style='width:100%'>
	".@implode("", $tt)."
	</table>
	<script>LoadAjax('left-content-id','$page?left-content-id=yes');</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	return;
	
	
	$info_right[]="	
	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/webservices-128.png'></td>
		<td valign='top'><H3 style='font-weight:bold'>{myWebServices}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:Loadjs('miniadm.www.services.php');\" 
				style='font-size:13px;font-weight:normal'>{myWebServices_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>";
	
	$info_left[]="	<table style='width:98%' class=form>
	<tr>
		<td valign='top' width=1%><img src='img/identity-128.png'></td>
		<td valign='top'><H3 style='font-weight:bold'>{myaccount}</H3>
			<ul>
			<li><a href=\"javascript:blur()\" 
				OnClick=\"javascript:LoadAjax('BodyContent','domains.edit.user.php?userid={$_SESSION["uid"]}&ajaxmode=yes&dn=$mydn');\" 
				style='font-size:13px;font-weight:normal'>{myaccount_text}</a>
			</li>
			</ul>	
			
		
		</td>
	</tr>
	</table>";	
	
	if($users->AllowAddUsers){
		$info_left[]=info_organization();
	}
	if($users->AllowChangeDomains){
		$info_right[]=info_messaging();
	}
	
	if(($users->AsDansGuardianAdministrator) OR ($users->AsWebFilterRepository)){
		$info_left[]=info_Dansguardian();
		
	}
	
	
	
	
	//www-128.png
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=50%>".@implode("<br>",$info_left)."</td>
		<td valign='top' width=50%>".@implode("<br>",$info_right)."</td>
	</tr>
	</table>
	<script>
	LoadAjax('tool-map','miniadm.toolbox.php?script=center-panel');
	
	</script>
	
	";
	$html=$tpl->_ENGINE_parse_body($html);
	$_SESSION[__FILE__][__FUNCTION__]=$html;
	echo $html;
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

function disk_usage(){
	
	
}



?>