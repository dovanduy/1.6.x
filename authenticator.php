<?php
session_start();
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");

$GLOBALS["ruleid"]=$_GET["ruleid"];
$SERVER_NAME=$_SERVER["SERVER_NAME"];
$HTTP_HOST=$_SERVER["HTTP_HOST"];
$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];
if(!isset($_GET["cachetime"])){$_GET["cachetime"]=15;}
if(!is_numeric($_GET["cachetime"])){$_GET["cachetime"]=15;}




if(isset($_GET["error-page"])){send_error_page();exit;}

$GLOBALS["DEBUG"]=true;
$sesskey=$_GET["sesskey"];
$time=sessiontime();
if($time<$_GET["cachetime"]+1){
	Debuglogs("{$_SESSION[$sesskey]["AUTHENTICATOR_UID"]} Cached {$time}mn < {$_GET["cachetime"]}Mn",__FUNCTION__,__LINE__);
	header("HTTP/1.0 200 OK");
	die();
}


while (list ($index, $alias) = each ($_GET) ){
	Debuglogs("GET: $index: -> $alias",__FUNCTION__,__LINE__);
	
}


Debuglogs("$HTTP_X_REAL_IP: Auth: \"{$_SERVER['PHP_AUTH_USER']}\", uri:{$_GET['uri']}, rule:{$_GET["ruleid"]}",
__FUNCTION__,__LINE__);
$banner=base64_decode($_GET["banner"]);
Debuglogs("$HTTP_X_REAL_IP: -> INIT",__FUNCTION__,__LINE__);
$GLOBALS["Q"]=new mysql_squid_builder();


if(!isset($_SERVER['PHP_AUTH_USER']) OR ($_SERVER['PHP_AUTH_USER']==null)){
	header('WWW-Authenticate: Basic realm="'.$banner.'"');
	header('HTTP/1.0 401 Unauthorized');
	die();
}





$auth=false;



if(CheckPassword_rule($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'],$_GET["ruleid"])){$auth=true;}

if(!$auth){
	header('WWW-Authenticate: Basic realm="'.$banner.'"');
	header('HTTP/1.0 401 Unauthorized');
	die();
}else{
	
	$_SESSION[$sesskey]["AUTHENTICATOR_TIME"]=time();
	$_SESSION[$sesskey]["AUTHENTICATOR_UID"]=$_SERVER['PHP_AUTH_USER'];
	Debuglogs("$HTTP_X_REAL_IP: -> OK Authenticated..",__FUNCTION__,__LINE__);
}

function sessiontime(){
	$sesskey=$_GET["sesskey"];
	if(!isset($_SESSION[$sesskey]["AUTHENTICATOR_TIME"])){return 9000000;}
	if(!isset($_SESSION[$sesskey]["AUTHENTICATOR_UID"])){return 9000000;}
	$last_modified = $_SESSION[$sesskey]["AUTHENTICATOR_TIME"];
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);	
	
}


function CheckPassword_rule($username,$password,$ruleid){
	if(!isMustAuth($ruleid)){return true;}
	$sql="	SELECT
	authenticator_authlnk.ID,
	authenticator_authlnk.zorder,
	authenticator_auth.groupname,
	authenticator_auth.enabled,
	authenticator_auth.params,
	authenticator_auth.group_type,
	authenticator_authlnk.groupid
	FROM authenticator_authlnk,authenticator_auth
	WHERE authenticator_authlnk.ruleid='$ruleid'
	AND authenticator_authlnk.groupid=authenticator_auth.ID";
	$results = $GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ErrorLogs($GLOBALS["Q"]->mysql_error,__FUNCTION__,__LINE__);return false;}
	
	$t=time();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$groupname=$ligne["groupname"];
		$group_type=$ligne["group_type"];
		$enabled=$ligne["enabled"];
		$params=unserialize(base64_decode($ligne["params"]));
		if($enabled==0){
			Debuglogs("Rule: $ruleid: $username : $groupname type( $group_type ) not enabled, SKIP",__FUNCTION__,__LINE__);
			continue;
		}
		
		Debuglogs("Rule: $ruleid: $username : $groupname type( $group_type ) Check ".count($params)." parameters",__FUNCTION__,__LINE__);
		if($group_type==0){
			if(local_ldap($username,$password)){
				return true;
			}
		}
		
		if($group_type==2){
			if(local_ad($username,$password,$params)){
				return true;
			}
		}		
	}	
	
	return false;
}

function local_ldap($username,$password){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	$users=new user($username);
	if(!$users->UserExists){
		Debuglogs("$username : UserExists->False",__FUNCTION__,__LINE__);		
		return false;
	}
	if($users->password==$password){
		Debuglogs("$username : Authenticated...",__FUNCTION__,__LINE__);
		return true;
	}
	Debuglogs("$username : FAILED...",__FUNCTION__,__LINE__);
}

function local_ad($username,$password,$params){
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$array["LDAP_SERVER"]=$params["LDAP_SERVER"];
	$array["LDAP_PORT"]=$params["LDAP_PORT"];
	$array["WINDOWS_DNS_SUFFIX"]=$params["WINDOWS_DNS_SUFFIX"];
	$array["DEBUG"]=$GLOBALS["DEBUG"];
	
	Debuglogs("Active Directory : {$params["LDAP_SERVER"]}:{$params["LDAP_PORT"]} Check",__FUNCTION__,__LINE__);
	
	$external_ad_search=new external_ad_search(base64_encode(serialize($array)));
	if($external_ad_search->CheckUserAuth($username,$password)){
		Debuglogs("$username : Authenticated...",__FUNCTION__,__LINE__);
		return true;
	}
	Debuglogs("$username : FAILED...",__FUNCTION__,__LINE__);
	
}


function isMustAuth($ruleid){
	
	
	$sql="
	SELECT
	authenticator_sourceslnk.ID,
	authenticator_sourceslnk.zorder,
	authenticator_sourceslnk.groupid,
	authenticator_groups.groupname,
	authenticator_groups.group_type,
	authenticator_groups.enabled
	FROM authenticator_sourceslnk,authenticator_groups
	WHERE authenticator_sourceslnk.ruleid='$ruleid'
	AND authenticator_sourceslnk.groupid=authenticator_groups.ID
	ORDER BY zorder
	";
	
	
	
	$results = $GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ErrorLogs($GLOBALS["Q"]->mysql_error,__FUNCTION__,__LINE__);return true;}
	
	Debuglogs("rule:{$_GET["ruleid"]} -> ". mysql_num_rows($results)." sources groups",__FUNCTION__,__LINE__);
	
	$t=time();
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$enabled=$ligne["enabled"];
		if($enabled==0){continue;}
		Debuglogs("rule:{$_GET["ruleid"]} Group:{$ligne["groupname"]} (enabled=$enabled): type:{$ligne["group_type"]}",__FUNCTION__,__LINE__);
		if($ligne["group_type"]==0){Debuglogs("rule:{$_GET["ruleid"]} Group:{$ligne["groupname"]}: -> in All cases...",__FUNCTION__,__LINE__);return true;}
	}
	
	
}


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
		openlog("authenticator", LOG_PID , LOG_SYSLOG);
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
		openlog("authenticator", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $linetext);
		closelog();
		
	}
}

function send_error_page(){
	$SERVER_NAME=$_SERVER["SERVER_NAME"];
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
	$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
	$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];	
	$REQUESTED_URI=$_GET["uri"];
	$uid=$_SERVER['PHP_AUTH_USER'];
	$error_id=$_GET["error-ID"];
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	
	
	$error["400"]["TITLE"]="Bad Request";
	$error["400"]["EXPLAIN"]="The user's request contains incorrect syntax";

	$error["401"]["TITLE"]="Unauthorized";
	$error["401"]["EXPLAIN"]="The requested file requires authentication (a username and password).";
	
	$error["403"]["TITLE"]="Forbidden";
	$error["403"]["EXPLAIN"]="The server will not allow you to access the requested file.";
	
	
	$error["404"]["TITLE"]="Not Found";
	$error["404"]["EXPLAIN"]="The server could not find the file that you requested.<br>This error commonly occurs when a URL is mistyped.";
	
	
	$error["500"]["TITLE"]="Internal Server Error";
	$error["500"]["EXPLAIN"]="This error signifies that the server has encountered 
	an unexpected condition.<br>It is a &laquo;catch-all&raquo; error that will be displayed when 
	no specific information can be gathered by the server regarding the condition.<br>
	This error often occurs when an application request cannot be fulfilled due 
	to the application being misconfigured.";
	
	
	 
	$error["501"]["TITLE"]="Not Implemented";
	$error["501"]["EXPLAIN"]="This signifies that the HTTP method sent by the client is not supported 
	by the server.<br>
	It is most often caused by the server being out of date. This error is very rare and generally requires that the web server be updated.";
	
	
	
	$error["502"]["TITLE"]="Bad Gateway";
	$error["502"]["EXPLAIN"]="This error is usually due to improperly configured proxy servers.<br>
	However, the problem may also arise when there is poor IP communication amongst back-end computers, 
	when the client’s ISP is overloaded, or when a firewall is functioning improperly.<br>
	The first step in resolving the issue is to clear the client’s cache.<br>
	This action should result in the a different proxy being used to resolve the web server’s content.";	
	
	$error["503"]["TITLE"]="Service Unavailable";
	$error["503"]["EXPLAIN"]="This error occurs when the server is unable to handle requests due to a 
	temporary overload or due to the server being temporarily closed for maintenance.<br>
	The error signifies that the server will only temporarily be down.<br>
	It is possible to receive other errors in place of 503.<br>	
	Contact the server administrator if this problem persists.";
	
	$error["504"]["TITLE"]="Gateway Timeout";
	$error["504"]["EXPLAIN"]="This occurs when this server somewhere along the chain does not receive 
	a timely response from a server further up the chain.<br>
	The problem is caused entirely by slow communication between upstream computers.<br>	
	To resolve this issue, contact the system administrator.";
	
	$error["505"]["TITLE"]="HTTP Version Not Supported";
	$error["505"]["EXPLAIN"]="This error occurs when the server refuses to support the HTTP protocol 
	that has been specified by the client computer.<br>
	It can be caused by the protocol not being specified properly by the client computer;
	for example, if an invalid version number has been specified.";
	
	
	$error["507"]["TITLE"]="Insufficient Storage";
	$error["507"]["EXPLAIN"]="This error indicates that the server is out of free memory.<br>
	It is most likely to occur when an application being requested cannot allocate the necessary 
	system resources for it to run.<br>
	To resolve the issue, the server’s hard disk may need to be cleaned of any unnecessary documents 
	to free up more hard disk space, its memory may need to be expanded, 
	or it may simply need to be restarted.<br>
	Please contact the system administrator for more information regarding this error message.";
	
	$error["509"]["TITLE"]="Bandwidth Limit Exceeded";
	$error["509"]["EXPLAIN"]="This error occurs when the bandwidth limit imposed by 
	the system administrator has been reached.<br>
	The only fix for this issue is to wait until the limit is reset in the following cycle.<br>	
	Consult the system administrator for information about acquiring more bandwidth.";
	
	
	$error["510"]["TITLE"]="Not Extended";
	$error["510"]["EXPLAIN"]="This error occurs when an extension attached to the HTTP request 
	is not supported by the web server.<br>	
	To resolve the issue, you may need to update the server.<br>
	Please consult the system administrator for more information.";
		
	$sock=new sockets();
	$ARTICAV=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$title="Error: {$_GET["error-page"]}: ".$error[$_GET["error-page"]]["TITLE"];
	
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
	<h1>$title</h1>
	</div>
	<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">
		{$error[$_GET["error-page"]]["TITLE"]}
	</div>
	<div style=\"background:#F7F7F7;padding:20px 28px 36px\">
	<div id=\"titles\">
	<h1>Request not allowed</h1> <h2>$uid</h2>
	</div> <hr>
	<div id=\"content\">
	<blockquote id=\"error\"> <p><b>{$error[$_GET["error-page"]]["EXPLAIN"]}</b></p> </blockquote>
	<p>The request:<a href=\"$REQUESTED_URI\">$REQUESTED_URI</a> cannot be displayed<br> 
	Please contact your service provider if you feel this is incorrect.
	</p>  <p>Generated by Artica Reverse Proxy <a href=\"http://www.artica.fr\">artica.fr</a></p>
	 <br> </div>  <hr> <div id=\"footer\"> <p>Artica version: $ARTICAV</p> <!-- %c --> </div> </div></div>
	</div>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>";
	$header=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");
	
	if($error_id>0){
		$users=new usersMenus();
		if($users->CORP_LICENSE){
			$sql="SELECT `title`,`headers`,`body` FROM nginx_error_pages WHERE ID='$error_id'";
			$q=new mysql_squid_builder();
			$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
			if(strlen($ligne["headers"])>10){$header=$ligne["headers"];}
			if(strlen($ligne["body"])>10){$content=$ligne["body"];}
			$title=$ligne["title"];
		}
	}
	
	$newheader=str_replace("{TITLE}", $title, $header);
	$newheader=str_replace("{ARTICA_VERSION}", $ARTICAV, $header);
	$newheader=str_replace("{uid}", $uid, $header);
	$newheader=str_replace("{error_code}", $_GET["error-page"], $header);
	$newheader=str_replace("{error_desc}", $error[$_GET["error-page"]]["EXPLAIN"], $header);
	$newheader=str_replace("{uri}", $REQUESTED_URI, $header);
	
	$content=str_replace("{ARTICA_VERSION}", $content, $content);
	$content=str_replace("{uid}", $uid, $content);
	$content=str_replace("{TITLE}", $title, $content);
	$content=str_replace("{error_code}", $_GET["error-page"], $content);
	$content=str_replace("{error_desc}", $error[$_GET["error-page"]]["EXPLAIN"], $content);
	$content=str_replace("{uri}", $REQUESTED_URI, $content);
	
	
	
	$templateDatas="$newheader$content</body></html>";
	
	
	
	
	echo $templateDatas;
	
	
}
