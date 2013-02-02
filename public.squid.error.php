<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.mysql.inc');
	
	
	
	$username=$_GET["a"];
	$www=$_GET["www"].$_GET["url"];
	
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	$sql="SELECT template_body,template_title,template_header FROM squidtpls WHERE `zmd5`='{$_GET["T"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$newheader=trim(stripslashes($ligne["template_header"]));
	$ligne["template_body"]=stripslashes($ligne["template_body"]);
	if($newheader==null){$newheader=@file_get_contents("ressources/databases/squid.default.header.db");}	
	
	$ligne["template_body"]=trim($ligne["template_body"]);
	if($ligne["template_body"]==null){
		$ligne["template_body"]="<table class=\"w100 h100\">
				<tr><td class=\"c m\"><table style=\"margin:0 auto;border:solid 1px #560000\">
				<tr><td class=\"l\" style=\"padding:1px\"><div style=\"width:346px;background:#E33630\">
				<div style=\"padding:3px\"><div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
				<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
				<h1>ERROR: The requested URL could not be retrieved</h1>
				</div><div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div>
				<div style=\"background:#F7F7F7;padding:20px 28px 36px\"> <div id=\"titles\"> 
				<h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> 
				<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  
				<blockquote id=\"error\"> <p><b>Access Denied.</b></p> </blockquote>  
				<p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is 
				<a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div>
				</div></div></td></tr></table></td></tr></table>";
	}

	$ligne["template_body"]=str_replace("%U", $www, $ligne["template_body"]);
	$ligne["template_body"]=str_replace("%W","",$ligne["template_body"]);
	$ligne["template_body"]=str_replace("%w","postmaster@{$_SERVER["SERVER_NAME"]}",$ligne["template_body"]);
	$ligne["template_body"]=str_replace("%T","Artica",$ligne["template_body"]);
	$ligne["template_body"]=str_replace("%h","{$_SERVER["SERVER_NAME"]}",$ligne["template_body"]);
	$ligne["template_body"]=str_replace("%s",date("Y-m-d H:i:s"),$ligne["template_body"]);
	$ligne["template_body"]=str_replace("%c",$_GET["ee"],$ligne["template_body"]);
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $newheader);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
	echo $templateDatas;	