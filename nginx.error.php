<?php


$GLOBALS["DEBUG"]=true;
Debuglogs("$HTTP_X_REAL_IP: Auth: \"{$_SERVER['PHP_AUTH_USER']}\", uri:{$_GET['uri']}, rule:{$_GET["ruleid"]}",
__FUNCTION__,__LINE__);
session_start();

if($GLOBALS["DEBUG"]){echo "<li>Includes...</li>";}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");

$GLOBALS["ruleid"]=$_GET["ruleid"];
$SERVER_NAME=$_SERVER["SERVER_NAME"];
$HTTP_HOST=$_SERVER["HTTP_HOST"];
$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];


Debuglogs("$HTTP_X_REAL_IP: Auth: \"{$_SERVER['PHP_AUTH_USER']}\", uri:{$_GET['uri']}, rule:{$_GET["ruleid"]}",
__FUNCTION__,__LINE__);
$banner=base64_decode($_GET["banner"]);
Debuglogs("$HTTP_X_REAL_IP: -> INIT",__FUNCTION__,__LINE__);
$GLOBALS["Q"]=new mysql_squid_builder();


$content="<table class=\"w100 h100\">
<tr>
<td class=\"c m\">
<table style=\"margin:0 auto;border:solid 1px #560000\">
<tr>
<td class=\"l\" style=\"padding:1px\">
<div style=\"width:346px;background:#E33630\">
<div style=\"padding:3px\">
<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
<h1>ERROR: {$array["TITLE"]}</h1>
</div>
<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div>
<div style=\"background:#F7F7F7;padding:20px 28px 36px\">
<div id=\"titles\">
<h1>ERROR</h1> <h2>{$array["ERROR"]}</h2>
</div> <hr>
<div id=\"content\"> <p>{$array["EXPLAIN"]}</p>
<blockquote id=\"error\"> <p><b>{$array["REASON"]}</b></p> </blockquote>
<p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>";
$header=@file_get_contents(dirname(__FILE__)."/databases/squid.default.header.db");
$newheader=str_replace("{TITLE}", $array["TITLE"], $header);
$templateDatas="$newheader$content</body></html>";
echo $templateDatas;




function ErrorLogs($text=null,$function=null,$line=null){
	if($text==null){return;}
	$linetext=null;
	
	
	if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
	
	if(is_array($trace)){
		$filename=basename($trace[1]["file"]);
		$function=$trace[1]["function"];
		$line=$trace[1]["line"];
		$linetext="$function/$line $text";
	}else{
		$linetext=$text;
		if($function<>null){$linetext="$function/$line $linetext";}
	}
	
	if(function_exists("syslog")){
		$LOG_SEV=LOG_WARNING;
		openlog("error_page", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	
	}	
}


function Debuglogs($text=null,$function=null,$line=null){
	if(!$GLOBALS["DEBUG"]){return;}
	if($text==null){return;}
	$linetext=null;

	if($function==null){
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
		if(is_array($trace)){
			$filename=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];	
		}
	}
	
	$linetext=$text;
	if($function<>null){$linetext="$function/$line $linetext";}else{
		if($line<>null){
			$linetext="$line $linetext";
		}
	}
	
	
	if(function_exists("syslog")){
		$LOG_SEV=LOG_INFO;
		openlog("error_page", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $linetext);
		closelog();
		
	}
}