<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.tcpip.inc');
include_once('ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');



if(isset($_GET["css"])){css();exit;}

if(isset($_GET["endusers"])){endusers_load();exit;}
if(isset($_GET["jsload"])){js_load();exit;}
if(isset($_GET["imgload"])){imgload();exit;}
if(isset($_GET["register-form"])){register_form();exit;}
session_start();
if(isset($_POST["register-password"])){register_save();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["username"])){checkCreds();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tweaks"])){tweaks();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
page();

function page(){
	header('Content-Type: text/html; charset=iso-8859-1');
	$ipClass=new IP();
	$sock=new sockets();
	build_session();
	$NetBuilder=new system_nic();
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="Artica HotSpot system";}
	$tpl=new templates();
	$username=$tpl->_ENGINE_parse_body("{username}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="Artica HotSpot system";}
	
	
	
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$ArticaHotSpotInterface=$NetBuilder->NicToOther($ArticaHotSpotInterface);
	if($ArticaHotSpotInterface==null){
		echo "<H1>Error 500 Hostpot Interface error</H1>";die();
	}
	
	
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	
	$URL_REDIRECT="$ArticaHotSpotInterface?popup=yes&redirecturi=".urlencode($redirecturi);
	
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}");
	if($EnableArticaHotSpotCAS==1){ $URL_REDIRECT=CAS_URI(); }
	$redirect_text=$tpl->_ENGINE_parse_body("{redirect_uri_text}");
	
	$html="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html lang=\"en\">
	<head>
	<meta charset=\"utf-8\">
	<title>$ArticaSplashHotSpotTitle</title>
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
	<meta name=\"description\" content=\"\">
	<meta name=\"author\" content=\"\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=". urlencode("bootstrap/css/bootstrap.css")."\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=". urlencode("bootstrap/css/bootstrap-responsive.css")."\">
	<meta http-equiv=\"refresh\" content=\"0;url=$URL_REDIRECT\">
	
	<style type=\"text/css\">
	body {
	padding-top: 40px;
	padding-bottom: 40px;
	background-color: #f5f5f5;
	}
	
	.form-signin {
	max-width: 300px;
	padding: 19px 29px 29px;
	margin: 0 auto 20px;
	background-color: #fff;
	border: 1px solid #e5e5e5;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
	-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
	box-shadow: 0 1px 2px rgba(0,0,0,.05);
	}
	.form-signin .form-signin-heading,
	.form-signin .checkbox {
	margin-bottom: 10px;
	}
	.form-signin input[type=\"text\"],
	.form-signin input[type=\"password\"] {
	font-size: 16px;
	height: auto;
	margin-bottom: 15px;
	padding: 7px 9px;
	}
	</style>
	<!--[if IE]>
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/bootstrap/css/ie-only.css\" />
	<![endif]-->
	</head>
	<body>
	<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"/ressources/templates/endusers/ajax-loader-eu.gif\">

	
	<div class=\"container\">
	<H2><center>$ArticaSplashHotSpotTitle<br>$please_wait...</center></H2>
	<form class=\"form-signin\">
	<center>$redirect_text
		<img src='img/wait_verybig_mini_red.gif' style='margin:50px'>
	</center>
	</form>
	<center><a href='http://www.artica.fr'>&laquo;Artica HotSpot system&raquo;</a></center>
	</div>

	
	</body>
	</html>";
	echo $html;
	
	
}



function GET_IP_SOURCE(){
	$ipClass=new IP();
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		if($ipClass->isIPAddress($_SERVER["HTTP_X_FORWARDED_FOR"])){
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
	}
	
	if(function_exists("apache_request_headers")){
		$headers = apache_request_headers();
		if(isset($headers["X-Forwarded-For"])){
			if($ipClass->isIPAddress($headers["X-Forwarded-For"])){
				return $headers["X-Forwarded-For"];
			}
		}
	}
	$REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
	if($ipClass->isIPAddress($REMOTE_ADDR)){return $REMOTE_ADDR;}
	
	
}

function build_session(){
	if(is_array($_SESSION["HOTSPOT"])){return;}
	$REMOTE_ADDR=GET_IP_SOURCE();
	$SERVER_NAME=$_SERVER["SERVER_NAME"];
	$REDIRECT_URL=$_SERVER["REDIRECT_URL"];	
	$redirecturi="http://$SERVER_NAME$REDIRECT_URL";
	$IPADDR=GET_IP_SOURCE();
	$sock=new sockets();
	$ARP=$sock->getFrameWork("system.php?arp-resolve=$IPADDR");
	$t=time();
	$HOST=gethostbyaddr($REMOTE_ADDR);
	if(isset($_GET["redirecturi"])){ if($_GET["redirecturi"]<>null){$redirecturi=$_GET["redirecturi"];} }
	
	$array=array(
			"IPADDR"=>$IPADDR,
			"REMOTE_ADDR"=>$IPADDR,
			"SERVER_NAME"=>$SERVER_NAME,
			"HOST"=> $HOST,
			"redirecturi"=>$redirecturi,
			"ARP"=>$ARP,"t"=>$t
	);
	
	$_SESSION["HOTSPOT"]=$array;
}


function popup(){
	header('Content-Type: text/html; charset=iso-8859-1');
	$ipClass=new IP();
	$sock=new sockets();
	build_session();
	$array=$_SESSION["HOTSPOT"];
	$page=CurrentPageName();
		
	$ArticaSplashHotSpotTitle=$sock->GET_INFO("ArticaSplashHotSpotTitle");
	if($ArticaSplashHotSpotTitle==null){$ArticaSplashHotSpotTitle="Artica HotSpot system";}
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$HotSpotAutoRegisterWebMail=intval($sock->GET_INFO("HotSpotAutoRegisterWebMail"));
	$tpl=new templates();
	$username=$tpl->_ENGINE_parse_body("{username}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$ARP=$array["ARP"];
	$t=$array["t"];
	$token=$_GET["popup"];
	$redirect_header=null;
	$text_error=null;
	$GLOBALS["NO_JS"]=false;
	
	if($HotSpotAutoRegisterWebMail==1){
		$register=$tpl->_ENGINE_parse_body("{register}");
		$button_register="
				 <button class=\"btn btn-large btn-primary\" 
				type=\"button\" id=\"signin\"
				OnClick=\"Loadjs('$page?register-js=yes');\"
				>$register</button>";
				
				
	}

	
	$text_form="
		<h2 class=\"form-signin-heading\">$ArticaSplashHotSpotTitle</h2>
		<div id='main-form'>
	        <input type=\"text\" class=\"input-block-level\" placeholder=\"$username\" id=\"artica_username-$t\">
	        <input type=\"password\" class=\"input-block-level\" placeholder=\"$password\" id=\"artica_password-$t\">
	        <button class=\"btn btn-large btn-primary\" type=\"button\" id=\"signin\"
			>$please_sign_in</button>
			$button_register
		</div>";
	
	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($ARP)){
		$text_form=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{hostspot_network_incompatible}"));
	}
	
	if($EnableArticaHotSpotCAS==1){
		$text_form=null;
		$GLOBALS["NO_JS"]=true;
		$username=CAS_VALIDATE();
		if($username<>null){
			$array["LOGIN"]=$username;
			$text_error="<center><h2 style='color:red'>&laquo;$username&raquo;<br>CAS Authentification Success</h2></center>";
			$redirect_header="<meta http-equiv=\"refresh\" content=\"1;url=$redirecturi\">";
			if(!UnLock($array)){
				$redirect_header=null;
				$text_error=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("
				{failed}{$GLOBALS["CASLOGS"]}
				<center style='margin-top:20px'>
				<a href=\"$redirecturi\" style='font-size:22px'>&laquo;&nbsp;{retry_authentication}&nbsp;&raquo;</a>
				</center>
				"));
			}
		}else{
			unset($_SESSION["HOTSPOT"]);
			$text_error=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("
			{CAS_Authentification_failed}{$GLOBALS["CASLOGS"]}
			<center style='margin-top:20px'>
				<a href=\"$redirecturi\" style='font-size:22px'>&laquo;&nbsp;{retry_authentication}&nbsp;&raquo;</a>		
			</center>
			"));
			$redirect_header=null;
		}
		
		
		
		
	}
	
	
	if($text_error<>null){$text_error="<center><div style='background-color: #FFFFFF;
    border: 1px solid #E5E5E5;
    border-radius: 5px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    margin: 0 auto 20px;
    max-width: 500px;
    padding: 19px 29px 29px;margin:50px;width:60%'>$text_error</div></center>";
	}
	
	if($text_form<>null){$text_form= "<form class=\"form-signin\" id='formsignin'>$text_form</form>";}
	
	

	
	
$html="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html lang=\"en\">
 <head>
	<meta charset=\"utf-8\">
    <title>$ArticaSplashHotSpotTitle</title>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <meta name=\"description\" content=\"\">
    <meta name=\"author\" content=\"\">
   	<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=". urlencode("bootstrap/css/bootstrap.css")."\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=". urlencode("bootstrap/css/bootstrap-responsive.css")."\">
	$redirect_header
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=default.js\"></script>    
  	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
  	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
  	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/jquery.uilock.min.js\"></script>    
  	<script type=\"text/javascript\" language=\"javascript\" src=\"$page?jsload=js/jquery.blockUI.js\"></script>
   <style type=\"text/css\">
     body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      .form-signin {
        max-width: 300px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type=\"text\"],
      .form-signin input[type=\"password\"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
    </style>    
    <!--[if IE]>
		<link rel=\"stylesheet\" type=\"text/css\" href=\"$page?css=". urlencode("bootstrap/css/ie-only.css")."\" />
	<![endif]-->    
</head>
<body id='body-$t'>
<input type='hidden' id='LoadAjaxPicture' name=\"LoadAjaxPicture\" value=\"/ressources/templates/endusers/ajax-loader-eu.gif\">
<div id=\"SetupControl\" style='width:0;height:0'></div>
<div id=\"dialogS\" style='width:0;height:0'></div> 
<div id=\"dialogT\" style='width:0;height:0'></div> 
<div id=\"dialog0\" style='width:0;height:0'></div> 
<div id=\"dialog1\" style='width:0;height:0'></div>
<div id=\"dialog2\" style='width:0;height:0'></div> 
<div id=\"dialog3\" style='width:0;height:0'></div>
<div id=\"dialog4\" style='width:0;height:0'></div>
<div id=\"dialog5\" style='width:0;height:0'></div>
<div id=\"dialog6\" style='width:0;height:0'></div>
<div id=\"YahooUser\" style='width:0;height:0'></div>
<div id=\"logsWatcher\" style='width:0;height:0'></div>
<div id=\"WinORG\" style='width:0;height:0'></div>
<div id=\"WinORG2\" style='width:0;height:0'></div>
<div id=\"RTMMail\" style='width:0;height:0'></div>
<div id=\"Browse\" style='width:0;height:0'></div>
<div id=\"SearchUser\" style='width:0;height:0'></div>
<div id=\"UnityDiv\" style='width:0;height:0'></div>
<div id='PopUpInfos' style='position:absolute'></div>
<div id='find' style='position:absolute'></div>
<div class=\"info message\" id='AcaNotifyMessInfo'></div>
<div class=\"error message\" id='AcaNotifyMessError'></div>
<div class=\"warning message\" id='AcaNotifyMessWarn'></div>
<div class=\"success message\" id='AcaNotifyMessSuccess'></div>
$text_error
    <div class=\"container\">

$text_form
	
	 <center><a href='http://www.artica.fr'>&laquo;Artica HotSpot system&raquo;</a></center>
    </div> 

 
 <script type=\"text/javascript\">
 
 \$('#signin').on('click', function (e) {
	 //if(!checkEnter(e)){return;}
		\$.getScript('$page?js=yes&token=$token',true);

});
 
 
 \$('.input-block-level').keypress(function (e) {
	
	 if (e.which == 13) {
		 \$.getScript('$page?js=yes&token=$token');
	 }

});
 
 
 
 function SendLogon(event){
	 if(!checkEnter(e)){return;}
	 \$.getScript('$page?js=yes');
	 
 }
 
 Loadjs('$page?tweaks=true&t=$t');
 
 </script>

</body>
</html>";
	echo $html;
	
	
}

function tweaks(){
	$t=$_GET["t"];
	$sock=new sockets();
	$EnableArticaHostPotBackground=$sock->GET_INFO("EnableArticaHostPotBackground");
	
	$ArticaHostPotBackgroundPositionH=$sock->GET_INFO("ArticaHostPotBackgroundPositionH");
	$ArticaHostPotBackgroundPositionV=$sock->GET_INFO("ArticaHostPotBackgroundPositionV");
	
	if(!is_numeric($EnableArticaHostPotBackground)){$EnableArticaHostPotBackground=1;}
	if(!is_numeric($ArticaHostPotBackgroundPositionH)){$ArticaHostPotBackgroundPositionH=5;}
	if(!is_numeric($ArticaHostPotBackgroundPositionV)){$ArticaHostPotBackgroundPositionV=5;}
	
	if($EnableArticaHostPotBackground==0){return;}
	header("content-type: application/x-javascript");
	$ArticaHostPotBackgroundPath=$sock->GET_INFO("ArticaHostPotBackgroundPath");
	if($ArticaHostPotBackgroundPath==null){$ArticaHostPotBackgroundPath="logo-artica.png";}
	$style="background-image:url(img/$ArticaHostPotBackgroundPath);background-repeat : no-repeat;background-position:{$ArticaHostPotBackgroundPositionV}% {$ArticaHostPotBackgroundPositionH}%";
	
	echo "document.getElementById('body-$t').style.cssText='$style';";
}


function js(){
	header("content-type: application/x-javascript");
	$array=$_SESSION["HOTSPOT"];
	$NextArray=$_GET["token"];
	$page=CurrentPageName();
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$ARP=$array["ARP"];
	$t=$array["t"];
	
	while (list ($num, $ligne) = each ($array) ){
		$js[]="XHR.appendData('$num','$ligne');";
		
	}
	
	
	$ldap=new clladp();
	$password="var password=MD5(document.getElementById('artica_password-$t').value);";
	$xhradd="XHR.appendData('asMD5','1');";
	
	if($GLOBALS["VERBOSE"]){echo __LINE__."::IsKerbAuth ??...()<br>\n";}
	if($ldap->IsKerbAuth()){
		$xhradd=null;
	}
$html="
var x_SendLogonButton$t = function (obj) {
	var response=obj.responseText;
	if(response.length>3){alert(response);return;}
	document.location.href='$redirecturi';
}

function SendLogonButton$t(){
	var username=document.getElementById('artica_username-$t').value;
	var password=encodeURIComponent(document.getElementById('artica_password-$t').value);
	var XHR = new XHRConnection();
	XHR.appendData('username',username);
	XHR.appendData('password',password);
	XHR.appendData('token','$NextArray');
	".@implode("\n", $js)."
	$xhradd
	XHR.sendAndLoad('$page', 'POST',x_SendLogonButton$t);

}
SendLogonButton$t();
";
echo $html;
}

function checkcreds_AD(){
	$ldap=new clladp();
	if(!$ldap->IsKerbAuth()){return false;}
	$username=$_POST["username"];
	$password=url_decode_special_tool(trim($_POST["password"]));
	$external_ad_search=new external_ad_search();
	if($external_ad_search->CheckUserAuth($username,$password)){return false;}
	return true;
}

function checkcreds_ldap(){
	$username=$_POST["username"];
	$password=url_decode_special_tool(trim($_POST["password"]));
	$DEBUG[]="username: $username";
	
	
	
	$DEBUG[]="_POST[password]: $password";
	
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	
	$DEBUG[]="userPassword: $userPassword";
	
	$userPasswordMD=md5(trim($userPassword));
	$passwordMD=md5($password);
	if(trim($u->uidNumber)==null){return false;}
		
	
	
	if($passwordMD<>$userPasswordMD){ 
		$DEBUG[]="userPasswordMD: $userPasswordMD";
		$DEBUG[]="passwordMD: $passwordMD";
		echo $tpl->javascript_parse_text("{bad_password}\n".@implode("\n", $DEBUG),1);
		return false;}
		
		return true;
}

function checkcreds_mysql($array,$noauthent=false){
	$q=new mysql_squid_builder();
	
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];
	$MAC=$array["MAC"];
	$username=$_POST["username"];
	$HOST=$array["HOST"];
	$CACHE_AUTH=$GLOBALS["CACHE_AUTH"];
	$IPADDR=$array["IPADDR"];
	$md5key=md5(strtolower("$username$IPADDR$MAC$HOST"));
	
	
	
	// sessiontime -> reauth
	
	
	
	
	$tpl=new templates();
	$time=time();
	$password=url_decode_special_tool(trim($_POST["password"]));
	if(!$q->TABLE_EXISTS("hotspot_members")){$q->CheckTables();}
	$sql="SELECT * FROM hotspot_members WHERE uid='$username'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	if($ligne["uid"]<>null){
		$GLOBALS["CACHE_AUTH"]=$ligne["sessiontime"];
		$GLOBALS["MAX_TIME"]=$ligne["ttl"];
		
	}
	if($noauthent){return;}
	
	if($ligne["enabled"]==0){echo $tpl->javascript_parse_text("{access_to_internet_disabled} ({disabled})");return false;}
	
	if($ligne["uid"]==null){
		$GLOBALS["LOGS"][]=__FUNCTION__.":: uid is null";
		return false;
	}

	if($ligne["password"]<>$password){
		if($ligne["password"]<>md5($password)){
			$GLOBALS["LOGS"][]=__FUNCTION__.":: $username `password` mismatch expected `{$ligne["password"]}`";
			
			return false;
		}
	}
	
	
	return true;
}

function CheckCurrentSessionTTL($md5key){
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$md5key'"));
	if($md5key==null){return true;}
	
	$finaltime=$ligne["finaltime"];
	if($finaltime==0){return true;}
	$time=time();
	if($finaltime>$time){return true;}
	
	
	$since=distanceOfTimeInWords($finaltime,time());
	$currentTime=date("Y-m-d H:i:s");
	$since=$since." ".date("Y-m-d H:i:s",$finaltime)."/$currentTime";
	
	
	$tpl=new templates();
	$error=$tpl->javascript_parse_text("{accesstime_to_internet_expired}: {since} $since - $results");
	$GLOBALS["CASLOGS"]="<br><strong>$error</strong>";
	if(!$GLOBALS["NO_JS"]){	echo $error; }
	return false;
	
	
}


function checkcreds(){
	while (list ($num, $ligne) = each ($_POST) ){
		$array[$num]=$ligne;	
	}
	$sock=new sockets();
	$GLOBALS["CACHE_AUTH"]=$sock->GET_INFO("ArticaSplashHotSpotCacheAuth");
	$GLOBALS["MAX_TIME"]=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	
	
	if(!is_numeric($GLOBALS["CACHE_AUTH"])){$GLOBALS["CACHE_AUTH"]=60;}
	if(!is_numeric($GLOBALS["MAX_TIME"])){$GLOBALS["MAX_TIME"]=0;}
	$LOGIN=$array["username"];
	$IPADDR=$array["REMOTE_ADDR"];
	$MAC=$array["ARP"];
	$HOST=gethostbyaddr($IPADDR);
	$URI=$array["redirecturi"];
	
	$array["LOGIN"]=$LOGIN;
	$array["IPADDR"]=$IPADDR;
	$array["MAC"]=$MAC;
	$array["HOST"]=$HOST;
	
	
	
	
	$auth=false;
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	
	if(!$auth){
		$auth=checkcreds_AD();
		if($auth){
			checkcreds_mysql($array,true);
			UnLock($array);
			return;
		}
		
	}
	
	if(!$auth){
		$auth=checkcreds_ldap();
		if($auth){
			checkcreds_mysql($array,true);
			UnLock($array);
			return;
		}
	}	
	
	if(!$auth){
		$auth=checkcreds_mysql($array);
		if($auth){
			UnLock($array);
			return;
		}
	}
	
	
	events(1,"Login failed for $LOGIN/$IPADDR","MAC:$MAC\nHost:$HOST\n".@implode("\n", $GLOBALS["LOGS"]));
	$tpl=new templates();
	$connection_failed=$tpl->javascript_parse_text("{failed}");
	echo $connection_failed;
}

function events($severity,$subject,$content){
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			}
		}
			
	
	$zdate=date("Y-m-d H:i:s");
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	$q->QUERY_SQL("INSERT IGNORE IGNORE INTO `hotspot_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
}

function UnLock($array){
	$REMOTE_ADDR=$array["REMOTE_ADDR"];
	$IPADDR=$array["REMOTE_ADDR"];
	$SERVER_NAME=$array["SERVER_NAME"];
	$redirecturi=$array["redirecturi"];
	$LOGIN=$array["LOGIN"];
	$uid=$array["LOGIN"];
	$ARP=$array["ARP"];	
	$MAC=$ARP;
	$username=$uid;
	$HOST=$array["HOST"];
	$CACHE_AUTH=$GLOBALS["CACHE_AUTH"];
	
	$time=time();
	$sock=new sockets();
	
	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($ARP)){
		$tpl=new templates();
		$fa=$tpl->javascript_parse_text("{hostspot_network_incompatible}");
		events(0,$fa,"MAC:$MAC\nHost:$HOST Unable to find any MAC address for this item");
		unset($_SESSION["HOTSPOT"]);
		echo $fa;
		return;
	}
	
	$md5key=md5(strtolower("$LOGIN$IPADDR$MAC$HOST"));
	if(!CheckCurrentSessionTTL($md5key)){return false;}
	
	// maxtime -> Paramètres de réauthentification
	// nextcheck -> time du prochain check
	// endtime -> Lock de la session.
	

	$q=new mysql_squid_builder();
	$NextCheck = strtotime("+525600 minutes", $time);
	$finaltime = strtotime("+525600 minutes", $time);
	$logintime=time();
	
	if(!is_numeric($CACHE_AUTH)){$CACHE_AUTH=60;}
	if(!is_numeric($GLOBALS["MAX_TIME"])){$GLOBALS["MAX_TIME"]=0;}
	
	
	if($CACHE_AUTH>0){ $NextCheck = strtotime("+$CACHE_AUTH minutes", $time); }
	if($GLOBALS["MAX_TIME"]>0){ $finaltime = strtotime("+{$GLOBALS["MAX_TIME"]} minutes", $time); }
	$datelogs=date("Y-m-d H:i:s",$NextCheck);
	$finaltimeDate=date("Y-m-d H:i:s",$finaltime);
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_sessions WHERE `md5`='$md5key'"));
	if($ligne["md5"]<>null){
		$finaltime=$ligne["finaltime"];
		$logintime=$ligne["logintime"];
	}
	
	$MAC=trim(strtolower($MAC));
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`='$md5key'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE ipaddr='$IPADDR'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE MAC='$MAC'");
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE uid='$uid'");
	
	
	if(!$q->FIELD_EXISTS("hotspot_sessions", "nextcheck")){$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `nextcheck` BIGINT UNSIGNED ,ADD INDEX ( `nextcheck` )");}
	
	$sql="INSERT IGNORE INTO hotspot_sessions (`md5`,logintime, maxtime,finaltime,nextcheck,username,uid,MAC,hostname,ipaddr)
	VALUES('$md5key',$logintime,$CACHE_AUTH,$finaltime,$NextCheck,'$username','$uid','$MAC','$HOST','$IPADDR')";
	
	events(2,"Create session for $LOGIN","MAC:$MAC\nHost:$HOST +{$CACHE_AUTH}mn\nNext check will be at $datelogs ($NextCheck),\n session will expire at $finaltimeDate\n$sql");
	
	unset($_SESSION["HOTSPOT"]);
	$md5key_enc=urlencode($md5key);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("hotspot.php?release-mac=".urlencode($ARP)."&ip=".urlencode($REMOTE_ADDR)."&md5key=$md5key");
	return true;
}

function CAS_SERVICE(){
	if(!isset($_SESSION["HOTSPOT"])){build_session();}
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$redirecturi=urlencode(trim($_SESSION["HOTSPOT"]["redirecturi"]));
	$MAC=trim(strtolower($_SESSION["HOTSPOT"]["ARP"]));
	return urlencode("$ArticaHotSpotInterface?popup=yes&MAC=$MAC&redirecturi=$redirecturi");
}



function CAS_URI(){
	
	$sock=new sockets();
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	
	
	
	
	$service=CAS_SERVICE();
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$URL_REDIRECT="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/login?service=$service";
	return $URL_REDIRECT;
	
}



function CAS_VALIDATE(){
	$TGT=$_GET["ticket"];
	if($TGT==null){
		$GLOBALS["CASLOGS"]="<br><strong>No ticket returned</strong><br>";
		return null;}
	$sock=new sockets();
	$token=$_GET["popup"];
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$EnableArticaHotSpotCAS=$sock->GET_INFO("EnableArticaHotSpotCAS");
	if(!is_numeric($EnableArticaHotSpotCAS)){$EnableArticaHotSpotCAS=0;}
	$ArticaHotSpotCASHost=$sock->GET_INFO("ArticaHotSpotCASHost");
	$ArticaHotSpotCASPort=intval($sock->GET_INFO("ArticaHotSpotCASPort"));
	$ArticaHotSpotCASContext=$sock->GET_INFO("ArticaHotSpotCASContext");
	if(!is_numeric($ArticaHotSpotCASPort)){$ArticaHotSpotCASPort=443;}
	$proto="http";
	if($ArticaHotSpotCASPort==80){$proto="http";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort==443){$proto="https";$ArticaHotSpotCASPort=null;}
	if($ArticaHotSpotCASPort<>null){$ArticaHotSpotCASPort=":$ArticaHotSpotCASPort";}
	$REMOTE_ADDR=$_SESSION["HOTSPOT"]["REMOTE_ADDR"];
	$SERVER_NAME=$_SESSION["HOTSPOT"]["SERVER_NAME"];
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	$ARP=$_SESSION["HOTSPOT"]["ARP"];
	

	
	$cas_service=CAS_SERVICE();
	
	if($ArticaHotSpotCASContext<>null){$ArticaHotSpotCASContext="/$ArticaHotSpotCASContext";}
	

	$uri_check="$proto://$ArticaHotSpotCASHost$ArticaHotSpotCASPort$ArticaHotSpotCASContext/serviceValidate?ticket=$TGT&service=$cas_service";
	
	$curl = curl_init("$uri_check");
	
	if($GLOBALS["VERBOSE"]){echo "<li>$uri_check</li>";}
	
	$t=time();
	curl_setopt($curl,  CURLOPT_HEADER, true);
	curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,  CURLOPT_FAILONERROR, true);
	curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl,  CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($curl,  CURLOPT_POST, 0);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	
	$data = curl_exec($curl);
	$length=strlen($data);
	$CURLINFO_HTTP_CODE=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	
	if($GLOBALS["VERBOSE"]) {
		echo "<strong>CURLINFO_HTTP_CODE:$CURLINFO_HTTP_CODE</strong><br>";
		print_r(curl_getinfo($curl));
	}
	
	curl_close($curl);
	$distanceInSeconds = round(abs(time() - $t));
	if($GLOBALS["VERBOSE"]) {echo "<strong>data: ". strlen($data)."</strong><hr>DATA: $data\n\n</hr>";}
	
	
	
	if($CURLINFO_HTTP_CODE<>200){
		$GLOBALS["CASLOGS"]="<br><strong>Unknown error: HTTP Error: $CURLINFO_HTTP_CODE</strong>";
		return null;}
	

	if($GLOBALS["VERBOSE"]){print_r($_SESSION["HOTSPOT"]);}
	
	if($GLOBALS["VERBOSE"]) {echo "<strong>".htmlentities($data)."</strong>";}
	
	if(preg_match("#INVALID_SERVICE#is", $data)){
		$GLOBALS["CASLOGS"]="<br><strong>Invalid service</strong>";
		events(1,"CAS session failed ticket $TGT INVALID_SERVICE","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi\service: $cas_service");
		return null;
	}
	
	if(preg_match("#<cas:user>(.+?)</cas:user>#is", $data,$re)){
		events(2,"CAS session success ticket $TGT with user {$re[1]}","REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP\nredirect:$redirecturi");
		if($GLOBALS["VERBOSE"]){echo "<li>TGT:$TGT - $re[1]</li>";}
		$GLOBALS["CASLOGS"]="<br><strong>$TGT Cas user: {$re[1]}<br>";
		return $re[1];
	}	
	
	$GLOBALS["CASLOGS"]="<br><strong>Unknown error: REMOTE_ADDR:$REMOTE_ADDR\nARP:$ARP</strong>";
	$GLOBALS["CASLOGS"]=$data;
	if($GLOBALS["VERBOSE"]){echo "<li>failed</li>";}
	
}

function js_load(){
	$js=$_GET["jsload"];
	$file=basename($js);
	header('Content-type: application/x-javascript');
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($js);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($js);
		
	
}

function css(){
	$css=$_GET["css"];
	$file=basename($css);
	header('Content-type: text/css');
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($css);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($css);
	
}

function endusers_load(){
	$path="ressources/templates/endusers/".$_GET["endusers"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);	
}

function imgload(){
	$path="img/".$_GET["imgload"];
	$CONTENT["gif"]="image/gif";
	$CONTENT["jpeg"]="image/jpeg";
	$CONTENT["jpg"]="image/jpeg";
	$CONTENT["png"]="image/png";
	$file=basename($path);
	$ext=Get_extension($path);
	
	$Content_type=$CONTENT[$ext];
	//error_log("$path - $ext - $file - $Content_type");
	
	header('Content-type: '.$CONTENT[$ext]);
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	
}

function register_js(){
	$page=CurrentPageName();
	header('Content-type: application/x-javascript');
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	echo "LoadAjax('main-form','$page?register-form=yes')";
	
}

function register_form(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{register}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	$html="
	<hr>
	<div style='font-size:22px;font-weight:bold;margin-bottom:15px'>$register</div>		
	
	<div style='font-size:18px;margin-bottom:10px'>$email:</div>		
	<input style='font-size:18px' type=\"text\" class=\"input-block-level\" placeholder=\"$email\" id=\"email-$t\">
	

	<div style='font-size:18px;margin-bottom:10px'>$password:</div>	
		<input style='font-size:18px' type=\"password\" class=\"input-block-level\" placeholder=\"$password\" id=\"password-$t\">
			        
	<div style='font-size:18px;margin-bottom:10px'>$password ($confirm):</div>	
		<input style='font-size:18px' type=\"password\" class=\"input-block-level\" placeholder=\"$password ($confirm)\" id=\"password2-$t\">
			        
	        
	        <div style='width:100%;text-align:right'>
	        <button class=\"btn btn-large btn-primary\" type=\"button\" id=\"signin\"
	        OnClick=\"javascript:Save$t();\"
			>$submit</button></div>
	
<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	document.location.href='$redirecturi';
}

function Save$t(){
	var XHR = new XHRConnection();
	
	
	if(document.getElementById('password-$t').value !== document.getElementById('password2-$t').value){
		alert('$password_mismatch');
		return;
	}
	
	
	XHR.appendData('email',document.getElementById('email-$t').value);
	XHR.appendData('register-password',encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
	
	
	";
	echo $html;
}

function register_save(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$uid=$_POST["email"];
	$sock=new sockets();
	$sock=new sockets();
	$tpl=new templates();
	$NetBuilder=new system_nic();
	$t=time();
	$HotSpotAutoRegisterWebMail=intval($sock->GET_INFO("HotSpotAutoRegisterWebMail"));
	$HotSpotAutoRegisterSMTPSrv=$sock->GET_INFO("HotSpotAutoRegisterSMTPSrv");
	$HotSpotAutoRegisterSMTPSrvPort=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPSrvPort"));
	$HotSpotAutoRegisterSMTPSender=$sock->GET_INFO("HotSpotAutoRegisterSMTPSender");
	$HotSpotAutoRegisterSMTPUser=$sock->GET_INFO("HotSpotAutoRegisterSMTPUser");
	$HotSpotAutoRegisterSMTPPass=$sock->GET_INFO("HotSpotAutoRegisterSMTPPass");
	$HotSpotAutoRegisterSMTPTls=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPTls"));
	$HotSpotAutoRegisterSMTPSSL=intval($sock->GET_INFO("HotSpotAutoRegisterSMTPSSL"));
	$HotSpotAutoRegisterSubject=$sock->GET_INFO("HotSpotAutoRegisterSubject");
	$HotSpotAutoRegisterContent=$sock->GET_INFO("HotSpotAutoRegisterContent");
	$HotSpotAutoRegisterConfirmTxt=$sock->GET_INFO("HotSpotAutoRegisterConfirmTxt");
	$HotSpotAutoRegisterMaxTime=intval($sock->GET_INFO("HotSpotAutoRegisterMaxTime"));
	$instance=trim($sock->getFrameWork('cmd.php?full-hostname=yes'));
	$_POST["register-password"]=url_decode_special_tool($_POST["register-password"]);
	$_POST["register-password"]=md5($_POST["register-password"]);
	
	
	
	
	if($HotSpotAutoRegisterSMTPSrvPort==0){$HotSpotAutoRegisterSMTPSrvPort=25;}
	if($HotSpotAutoRegisterMaxTime==0){$HotSpotAutoRegisterMaxTime=3;}
	
	if($HotSpotAutoRegisterSubject==null){
		$HotSpotAutoRegisterSubject="Access to Internet";
	}
	
	if($HotSpotAutoRegisterContent==null){
		$HotSpotAutoRegisterContent="In order to complete your registration and activate your account %email with %pass password\r\nClick on the link bellow:\r\n";
	}
	
	if($HotSpotAutoRegisterConfirmTxt==null){
		$HotSpotAutoRegisterConfirmTxt="Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration";
	}
	
	$smtp=new smtp();
	if($HotSpotAutoRegisterSMTPUser<>null){
		$params["auth"]=true;
		$params["user"]=$HotSpotAutoRegisterSMTPUser;
		$params["pass"]=$HotSpotAutoRegisterSMTPPass;
	}
	$params["host"]=$HotSpotAutoRegisterSMTPSrv;
	$params["port"]=$HotSpotAutoRegisterSMTPSrvPort;
	
	
	if(!$smtp->connect($params)){
		echo "Error $smtp->error_number: Could not connect to `$HotSpotAutoRegisterSMTPSrv` $smtp->error_text\n";
		return;
	}
	
	$HotSpotAutoRegisterContent=str_replace("\n", "\r\n", $HotSpotAutoRegisterContent);
	$HotSpotAutoRegisterContent=str_replace("%email", "$uid", $HotSpotAutoRegisterContent);
	$HotSpotAutoRegisterContent=str_replace("%pass", $_POST["register-password"], $HotSpotAutoRegisterContent);
	
	$REMOTE_ADDR=$_SESSION["HOTSPOT"]["REMOTE_ADDR"];
	$SERVER_NAME=$_SESSION["HOTSPOT"]["SERVER_NAME"];
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	$ARP=$_SESSION["HOTSPOT"]["ARP"];
	
	$sessionkey=md5("$ARP$uid{$_POST["register-password"]}");
	
	
	$ArticaHotSpotInterface=$sock->getFrameWork("hotspot.php?ArticaHotSpotInterface=yes");
	$ArticaHotSpotInterface=$NetBuilder->NicToOther($ArticaHotSpotInterface);
	$redirecturi=$_SESSION["HOTSPOT"]["redirecturi"];
	
	
	$URL_REDIRECT="$ArticaHotSpotInterface?hotspot.php?confirm=$sessionkey";
	
	
	
	$random_hash = md5(date('r', time()));
	$boundary="$random_hash/$instance";
	$body[]="Return-Path: <$HotSpotAutoRegisterSMTPSender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $HotSpotAutoRegisterSMTPSender";
	$body[]="Subject: $HotSpotAutoRegisterSubject";
	$body[]="To: $uid";
	$body[]="Auto-Submitted: auto-replied";
	$body[]="MIME-Version: 1.0";
	$body[]="Content-Type: multipart/mixed;";
	$body[]="	boundary=\"$boundary\"";
	$body[]="Content-Transfer-Encoding: 8bit";
	$body[]="Message-Id: <$random_hash@$instance>";
	$body[]="--$boundary";
	$body[]="Content-Description: Notification";
	$body[]="Content-Type: text/plain; charset=us-ascii";
	$body[]="";
	$body[]=$HotSpotAutoRegisterContent;
	$body[]=$URL_REDIRECT;
	$body[]="";
	$body[]="";
	$finalbody=@implode("\r\n", $body);
	
	if(!$smtp->send(array("from"=>$HotSpotAutoRegisterSMTPSender,"recipients"=>$uid,"body"=>$finalbody,"headers"=>null))){
		echo "Error $smtp->error_number: Could not send to `$uid`\n$smtp->error_text\n";
		$smtp->quit();
		return;
	}
	$smtp->quit();
	
	
	
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("hotspot_members", "sessionkey")){$q->QUERY_SQL("ALTER TABLE `hotspot_members` ADD `sessionkey` VARCHAR( 90 ) ,ADD INDEX ( `sessionkey` )");}
	
	$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["uid"]==null){

		$sql="INSERT IGNORE INTO hotspot_members (uid,ttl,sessiontime,password,enabled,sessionkey) VALUES
		('$uid','$HotSpotAutoRegisterMaxTime','$HotSpotAutoRegisterMaxTime','{$_POST["register-password"]}',0,'$sessionkey')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "MySQL error\n$q->mysql_error\n";}
		return;
	}
	
	
	
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?release-mac-period=$HotSpotAutoRegisterMaxTime&MAC=$ARP&ipaddr=$REMOTE_ADDR");
	echo $HotSpotAutoRegisterConfirmTxt;
}

?>