<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
$GLOBALS["POLICY_DEFAULT"]="Company retains the right, at its sole discretion, to refuse new service to any individual, group, or business.
Company also reserves the right to monitor Internet access to its services by authorized users and clients, as part of the normal course of its business practice. 
Should Company discover users engaged in any violation of the Acceptable Use Policy, which create denial of access or impediment of service, and which adversely affect Company’s ability to provide services, Company reserves the right to temporarily suspend user access to the its Servers and/or database.  
Company shall make written/electronic notification to user’s point of contact of any temporary suspension, and the cause thereof, as soon as reasonably possible. 
This temporary suspension will remain in effect until all violations have ceased.  
Company also retains the right to discontinue service with 30 days’ prior written notice for repeated violation of the acceptable use policy.
";		
	
	
	
	if(isset($_GET["checks"])){checks();exit;}
	if(isset($_GET["css-main"])){echo css();exit;}
	if(isset($_POST["username"])){check_auth();exit;}
page();	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	$array=unserialize(base64_decode($_GET["request"]));
	
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];
	$URI=$array["URI"];
	
	if(!isset($HotSpotConfig["USETERMSLABEL"])){$HotSpotConfig["USETERMSLABEL"]=null;}
	if(!isset($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}

	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	if($HotSpotConfig["USETERMSLABEL"]==null){$HotSpotConfig["USETERMSLABEL"]="I agree to terms";}
	
	
	$youmustaceptterms=$tpl->javascript_parse_text("{youmustaceptterms}: {$HotSpotConfig["USETERMSLABEL"]}");
	
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");
	
	if($URI==null){$URI="http://www.google.com";}
	
	$squid_splash_logon_explain=$tpl->_ENGINE_parse_body("{squid_splash_logon_explain}");
	
	
	if($HotSpotConfig["USETERMS"]==1){
		$useterms="
		<div style='text-align:right;margin-top:-15px;margin-bottom:10px'>
			<a href=\"javascript:blur();\" OnClick=\"javascript:Terms$t();\"
			style='color:#E50000;text-decoration:underline'>{$HotSpotConfig["USETERMSLABEL"]}</a>&nbsp;". 
			Field_checkbox("USETERMS$t", 1)
		."</div>";
		
	}
	
	
	
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
						<label for=\"username\">{username}:</label> <input type=\"text\" name=\"username\" id=\"username\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon$t(event)\">
		
					</div>
					<div class=\"field\">
						<label for=\"password\">{password}:</label> <input type=\"password\" name=\"password\" id=\"password\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon$t(event)\">
						<div id='lostpassworddiv'></div>
					</div>
					$useterms
					<div class=\"field button\">
						<span id='YouCanAnimateIt-$t'></span>".
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
 var x_SaveHotSpot$t=function(obj){
		if(document.getElementById('YouCanAnimateIt-$t')){document.getElementById('YouCanAnimateIt-$t').innerHTML='';}
     	var tempvalue=obj.responseText;
	 	if(tempvalue.length>1){alert(tempvalue);return;}
		 var url='$URI';
		 document.location.href=url;
		}	 
	 	
	function SendLogonStart$t(){
		if(document.getElementById('USETERMS$t')){
			if(!document.getElementById('USETERMS$t').checked){
				alert('$youmustaceptterms');
				return;
			}
		}
		var XHR = new XHRConnection();
 		var user=document.getElementById('username').value;
 		var password=MD5(document.getElementById('password').value);
 		XHR.appendData('username',user);
		XHR.appendData('password',password);
		XHR.appendData('md5key','$md5key');
		XHR.appendData('request','{$_GET["request"]}');
		if(document.getElementById('YouCanAnimateIt-$t')){
			document.getElementById('YouCanAnimateIt-$t').innerHTML='<img src=\"/img/preloader.gif\">';
		}
		XHR.sendAndLoad('$page', 'POST',x_SaveHotSpot$t);
 	 }
 	 
 	 function SendLogon$t(e){
 	 	if(!checkEnter(e)){return;}
 	 	SendLogonStart$t();
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
	$tpl=new templates();
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$username=$_POST["username"];
	$time=time();
	if($username==null){
		echo $tpl->javascript_parse_text("{wrong_password_or_username}");
		return;
	}
	
	include_once(dirname(__FILE__).'/ressources/class.user.inc');
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!isset($HotSpotConfig["FINAL_TIME"])){$HotSpotConfig["FINAL_TIME"]=0;}
	if(!isset($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!isset($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!isset($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!isset($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!isset($HotSpotConfig["USEAD"])){$HotSpotConfig["USEAD"]=0;}
	
	
	
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["FINAL_TIME"])){$HotSpotConfig["FINAL_TIME"]=0;}
	
	if($EnableKerbAuth==0){$HotSpotConfig["USEAD"]=0;}
	if(!$users->CORP_LICENSE){$HotSpotConfig["USEAD"]=0;}
	
	$CACHE_AUTH=$HotSpotConfig["CACHE_AUTH"];	
	$username=$_POST["username"];
	$password=$_POST["password"];
	$md5key=trim($_POST["md5key"]);
	if($md5key==null){echo "md5key is null...\n";return;}
	
	$array=unserialize(base64_decode($_POST["request"]));
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];	
	
	$auth=false;
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT md5,finaltime FROM hotspot_sessions WHERE md5='$md5key'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	$md5_session=$ligne["md5"];
	
	if($HotSpotConfig["USEAD"]==1){
		$creds["username"]=$username;
		$creds["password"]=$password;
		$results=trim(base64_decode($sock->GET_INFO("squid.php?pamlogon=".base64_encode(serialize($creds)))));
		if($results=="SUCCESS"){$auth=true;}
	}
	

	if($HotSpotConfig["USELDAP"]==1){
		$ct=new user($username);
		if(md5($ct->password)==$password){$auth=true;}
		
	}
	$ASUID=false;
	if($HotSpotConfig["USEMYSQL"]==1){
		if(!$q->TABLE_EXISTS("hotspot_members")){$q->CheckTables();}
		$sql="SELECT uid,password,ttl,sessiontime,enabled FROM hotspot_members WHERE uid='$username'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["uid"]<>null){
			$ASUID=true;
			if($ligne["password"]==$password){$auth=true;}
			if($ligne["sessiontime"]>0){$CACHE_AUTH=$ligne["sessiontime"];}
			if($ligne["enabled"]==0){echo $tpl->javascript_parse_text("{access_to_internet_disabled} ({disabled})");die();}
			if(intval($ligne["ttl"])>0){if($time>$ligne["ttl"]){echo $tpl->javascript_parse_text("{accesstime_to_internet_expired}");die();	}}
			
		}
	}
		
	
	
	
if(!$auth){
	echo $tpl->javascript_parse_text("{wrong_password_or_username}");
	return;
}

		$q=new mysql_squid_builder();
		
		if(!is_numeric($CACHE_AUTH)){$CACHE_AUTH=60;}
		
		$finaltime = strtotime("+$CACHE_AUTH minutes", $time);
		
		

	if($LOGIN<>null){$uid=$LOGIN;}else{	$uid=$username;}	
		
		if($md5_session<>null){
			$sql="UPDATE hotspot_sessions SET logintime=$time,maxtime=$CACHE_AUTH,
			username='$username',uid='$uid',MAC='$MAC',hostname='$HOST',
			finaltime=$finaltime
			WHERE md5='$md5key'";		
		}else{
			$sql="INSERT IGNORE INTO hotspot_sessions (md5,logintime, maxtime,finaltime,username,uid,MAC,hostname)
			VALUES('$md5key',$time,$finaltime,$CACHE_AUTH,'$username','$uid','$MAC','$HOST')";
			
		}			

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}		
		
		if($HotSpotConfig["USEMYSQL"]==1){
			if(!$ASUID){
				$sql="INSERT IGNORE INTO hotspot_members (uid,MAC,hostname,ipaddr,enabled) VALUES ('$uid','$MAC','$HOST','$IPADDR',1)";
			}else{
				$sql="UPDATE hotspot_members SET MAC='$MAC',hostname='$HOST',ipaddr='$IPADDR' WHERE uid='$uid'";
			}
			$q->QUERY_SQL($sql);
			
		}
		
		

	
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
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new sockets();
	
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	
	if(!isset($HotSpotConfig["FINAL_TIME"])){$HotSpotConfig["FINAL_TIME"]=0;}
	if(!isset($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!isset($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!isset($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!isset($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}	
	
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["FINAL_TIME"])){$HotSpotConfig["FINAL_TIME"]=0;}		
	
	$array=unserialize(base64_decode($_GET["checks"]));
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];
	$URI=$array["URI"];
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");
	$sql="SELECT uid,finaltime,logintime,maxtime FROM hotspot_sessions WHERE md5='$md5key'";
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mytime=time();
	echo "\nmd5key=$md5key\nuid={$ligne["uid"]}";
	
	
	if($ligne["uid"]==null){
        header("HTTP/1.0 401 Unauthorized");
        header("Status: 401 Unauthorized");
		die("401 Unauthorized $md5key $LOGIN $IPADDR $MAC$HOST".__LINE__);
	}	
	
	$uid=$ligne["uid"];
	$uid=$ligne["uid"];
	$finaltime=$ligne["finaltime"];
	$maxtime=$ligne["maxtime"];
	$logintime=$ligne["logintime"];
		
	
	
	if($HotSpotConfig["USEMYSQL"]==1){
		$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["uid"]<>null){
			if($ligne["enabled"]==0){
		        header("HTTP/1.0 401 Unauthorized");
		        header("Status: 401 Unauthorized");
				die("401 Unauthorized Account disabled".__LINE__);				
			}
		}
		
		if($ligne["ttl"]>0){
			if($mytime>$ligne["ttl"]){
		 		header("HTTP/1.0 401 Unauthorized");
		    	header("Status: 401 Unauthorized");
				die("401 Unauthorized Account expired".__LINE__);
			}				
		}
		
	}
	
	

	$maxtimeInSeconds=$maxtime*60;
	
	$distanceInSeconds = round(abs(time() - $logintime));	
	echo "\nCurrent time:$mytime;\nMax time: {$maxtimeInSeconds}s;Login time:$logintime\nDiff: {$distanceInSeconds}s (require $maxtimeInSeconds)";
	
	if(intval($distanceInSeconds)>intval($maxtimeInSeconds)){
		 echo "\nMyTime:$distanceInSeconds > MaxtimeInSeconds:$maxtimeInSeconds";
		 header("HTTP/1.0 401 Unauthorized");
         header("Status: 401 Unauthorized");
		 die("401 Unauthorized ".__LINE__);}
		 
		if($HotSpotConfig["USEMYSQL"]==1){$sql="UPDATE hotspot_members SET MAC='$MAC',hostname='$HOST',ipaddr='$IPADDR' WHERE uid='$uid'";$q->QUERY_SQL($sql);}		 
	 
		 
	echo "\n<OK>uid=$uid</OK>";

	
	
}

