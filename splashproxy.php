<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/class.mysql.squid.builder.php');
	include_once(dirname(__FILE__).'/class.user.php');
	
	
	
	if(isset($_GET["checks"])){checks();exit;}
	if(isset($_GET["css-main"])){echo css();exit;}
	if(isset($_POST["username"])){check_auth();}
page();	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array=unserialize(base64_decode($_GET["request"]));
	
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];
	$URI=$array["URI"];
	
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");
	
	if($URI==null){$URI="http://www.google.com";}
	
	$squid_splash_logon_explain=$tpl->_ENGINE_parse_body("{squid_splash_logon_explain}");
	
	$t=time();
	$html="<!DOCTYPE html>
<html lang=\"en\">
<head>
  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">
  <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"/ressources/templates/Squid/css/s.css\" charset=\"utf-8\"  />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/artica-theme/jquery-ui.custom.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/thickbox.css\" media=\"screen\"/>
		<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.qtip.css\" />
		<link href=\"$page?css-main=yes\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" >
  <title>$request</title>
<!-- HEAD TITLE: ressources/templates/Squid/TITLE -->
<link rel=\"icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />
<link rel=\"shortcut icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />



		<!-- Prepend:  -->
		<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/md5.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/float-barr.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/TimersLogs.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/artica_confapply.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/edit.user.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-1.8.0.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-1.8.22.custom.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jqueryFileTree.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.easing.1.3.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/thickbox-compressed.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.simplemodal-1.3.3.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.jgrowl_minimized.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cluetip.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.async.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.tools.min.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.qtip.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.kwicks-1.5.1.pack.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/flexigrid.pack.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-timepicker-addon.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/ui.selectmenu.js\"></script>
		<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cookie.js\"></script>


	
</head>

<body>
  <div id=\"sum\">
    <div id=\"header\">
      <h1>&nbsp;</h1>
    </div>




    <div id=\"content\">

			<form action=\"#\">
				<div class=\"f\">
					<div class=\"field\">
						<label for=\"flogin\">{username}:</label> <input type=\"text\" name=\"username\" id=\"artica_username\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon(event)\">
		
					</div>
					<div class=\"field\">
						<label for=\"fpassword\">{password}:</label> <input type=\"password\" name=\"password\" id=\"artica_password\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon(event)\">
						<div id='lostpassworddiv'></div>
					</div>
					<div class=\"field button\">
						<span id='YouCanAnimateIt-$t'></span>
						".
						button("{logon}", "SendLogonStart$t()",18)."
					</div>
				</div>
		
			</form>			
    </div><!-- /#content -->

    <div class=\"footer\">
    	<center style='font-size:13px;font-weight:bold;color:white'>$squid_splash_logon_explain</center>
    </div><!-- /#footer -->
  </div>
  
 <script>
 var x_SendLogonStart=function(obj){
		if(document.getElementById('YouCanAnimateIt')){document.getElementById('YouCanAnimateIt').innerHTML='';}
     	var tempvalue=obj.responseText;
	 	if(tempvalue.length)>0){alert(tempvalue);return;}
		 var url='$URI';
		 document.location.href=url;
		}	 
	 	
	function SendLogonStart$t(){
 		var user=document.getElementById('username').value;
 		var password=MD5(document.getElementById('password').value);
 		XHR.appendData('username',user);
		XHR.appendData('password',password);
		XHR.appendData('md5key','$md5key');
		XHR.appendData('request','{$_GET["request"]}');
		
		AnimateDiv('YouCanAnimateIt-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
 	 }
 	
 </script>
 
  
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

</body>
</html>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function check_auth(){
	$sock=new sockets();
	$tpl=new templates();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["FINAL_TIME"])){$HotSpotConfig["FINAL_TIME"]=0;}	
	$username=$_POST["username"];
	$password=$_POST["password"];
	$md5key=$_POST["md5key"];
	$array=unserialize(base64_decode($_POST["request"]));
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];	
	
	$auth=false;
	
	$sql="SELECT md5,finaltime FROM hostspot_sessions WHERE md5='$md5key'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
		if($ligne["finaltime"]>0){
			if($ligne["finaltime"]>time()){
				echo $tpl->javascript_parse_text("{accesstime_to_internet_expired}");
				return;	
			}
		}	
	
	if($HotSpotConfig["USELDAP"]==1){
		$ct=new user($username);
		if(md5($ct->password)==$password){$auth=true;}
		
	}
	if(!$auth){
		
		
	}
	
	
if(!$auth){
	echo $tpl->javascript_parse_text("{wrong_password_or_username}");
	return;
}

		$q=new mysql_squid_builder();
		$CACHE_AUTH=$HotSpotConfig["CACHE_AUTH"]*60;
		$time=time();
		$nexttime=$time+$CACHE_AUTH;
		
		$finaltime=0;
		if($ligne["finaltime"]==0){
			if($HotSpotConfig["FINAL_TIME"]>0){
				$FINAL_TIME=$HotSpotConfig["FINAL_TIME"]*60;
				$finaltime=$time+$FINAL_TIME;
			}
		}

	if($LOGIN<>null){$uid=$LOGIN;}else{	$uid=$username;}	
		
		if($ligne["md5"]<>null){
			$sql="UPDATE hostspot_sessions SET logintime=$time,maxtime=$nexttime,
			username='$username',uid='$uid',MAC='$MAC',hostname='$HOST' WHERE md5='$md5key'";		
		}else{
			$sql="INSERT IGNORE INTO hostspot_sessions (logintime, maxtime,finaltime,username,uid,MAC,hostname)
			VALUES($time,$nexttime,$finaltime,'$username','$uid','$MAC','$HOST')";
			
		}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function css(){
	$WORK_IMAGES="/ressources/templates/Squid/i";
$css="
body{
	font: 10pt Arial, Helvetica, sans-serif;
	background: #263849 url('$WORK_IMAGES/pattern.png');
}
#sum{
	width: 485px;
	height: 221px;
	margin: 80px auto;
	
}
h1{
	width: 401px;
	height: 127px;
	background: transparent url('$WORK_IMAGES/logo-captive.png') no-repeat;
	margin: 0 27px 21px;
} 
h1 span{
	display: none;
}
#content{
	width: 507px;
	height: 221px;
	background: url('$WORK_IMAGES/form.png') no-repeat;	
}
.f{
	padding: 45px 50px 45px 38px;	
	overflow: hidden;
}
.field{
	clear:both;
	text-align: right;
	margin-bottom: 15px;
}
.field label{
	float:left;
	font-weight: bold;
	line-height: 42px;
}
.field input{
	background: #fff url('$WORK_IMAGES/input.png') no-repeat;
	outline: none;
	border: none;
	font-size: 10pt;
	padding: 7px 9px 8px;
	width: 279px;
	height: 25px;
	font-size: 18px;
	font-weight:bolder;
	color:#444444;
}
.field input.active{
	background: url('$WORK_IMAGES/input_act.png') no-repeat;
}
.button{
	width: 297px;
	float: right;
}
.button input{
	width: 69px;
	background: url('$WORK_IMAGES/btn_bg.png') no-repeat;
	border: 0;
	font-weight: bold;
	height: 27px;
	float: left;
	padding: 0;
}
";	
header("Content-type: text/css");	
echo $css;
	
}

function checks(){
	$array=unserialize(base64_decode($_GET["checks"]));
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];
	$URI=$array["URI"];
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");
	$sql="SELECT uid,finaltime,logintime,maxtime FROM hostspot_sessions WHERE md5='$md5key'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["uid"]==null){die();}	
	
}

