<?php

function microsoft_ufdb_template($title,$addhead=null,$SquidGuardIPWeb=null){
	$sock=new sockets();
	
	if(!isset($GLOBALS["UfdbGuardHTTP"])){$sock->BuildTemplatesConfig();}
	
	if($SquidGuardIPWeb<>null){
		$SquidGuardIPWeb=str_replace("/".basename(__FILE__), "", $SquidGuardIPWeb);
		
	}
	
	$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"];
	if(isset($_REQUEST["unlock"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["unlock-www"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	if(isset($_REQUEST["smtp-send-email"])){$Background=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"];}
	
	
	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"])){
		$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	}
	
	$BackgroundColorBLKBT=$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"];
	if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}

	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<UL class=\"tasks\" id=\"cantDisplayTasks\">
		<LI id=\"task1-1\">You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.</LI>
		<LI id=\"task1-1\">Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</LI>
		</UL>";

	}

	
	$title=str_replace("ERROR:", "",$title);
	$title=str_replace("ERREUR:", "",$title);
	
	$title=utf8_decode($title);
	
	$f[]="<!DOCTYPE HTML>";
	$f[]="<HTML>";
	$f[]="<HEAD>";
	$f[]="<META content=\"IE=11.0000\" http-equiv=\"X-UA-Compatible\">";
	$f[]="<title>$title</title>";
	$f[]="<style type=\"text/css\">     ";
	$f[]="body {";
			$f[]="    background-repeat: repeat-x;";
			$f[]="    background-color: white;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\", \"arial\";";
			$f[]="    margin: 0em;";
			$f[]="    color: #575757;";
			$f[]="}";
			$f[]="H1,H2,HR{
					display:none;
								
			}";
			$f[]="";
			$f[]=".mainContent {";
			$f[]="    margin-top: 80px;";
			$f[]="    width: 700px;";
			$f[]="    margin-left: 120px;";
			$f[]="    margin-right: 120px;";
			$f[]="}";
			$f[]="";
			$f[]="";
			$f[]="";
			$f[]=".title {";
			$f[]="    color: #2778ec;";
			$f[]="    font-size: 38pt;";
			$f[]="    font-weight: 300;";
			$f[]="    vertical-align: bottom;";
			$f[]="    margin-bottom: 20px;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    position: relative;";
			$f[]="}";
			$f[]="";
			$f[]=".taskSection {";
			$f[]="    margin-top: 20px;";
			$f[]="    margin-bottom: 40px;";
			$f[]="    position: relative;";
			$f[]="}";
			$f[]="";
			$f[]=".tasks {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
			$f[]="blockquote {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    margin-top: -10px;";
			$f[]="}";
			$f[]="";
			$f[]="P,pre {";
			$f[]="    color: #000000;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: 200;";
			$f[]="    font-size: 12pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
			$f[]="#footer p {";
			$f[]="    color: #CCCCCC;";
			$f[]="    font-family: \"Segoe UI\", \"verdana\";";
			$f[]="    font-weight: normal;";
			$f[]="    font-size: 10pt;";
			$f[]="    padding-top: 5px;";
			$f[]="}";
		
			$f[]="li {";
			$f[]="    margin-top: 8px;";
			$f[]="}";
			$f[]="";
			$f[]=".diagnoseButton {";
			$f[]="    outline: none;";
			$f[]="    font-size: 9pt;";
			$f[]="}";
			$f[]=".Button2014-lg {
				border-radius: 6px 6px 6px 6px;
				-moz-border-radius: 6px 6px 6px 6px;
				-khtml-border-radius: 6px 6px 6px 6px;
				-webkit-border-radius: 6px 6px 6px 6px;
				font-size: 18px;
				line-height: 1.33;
				padding: 10px 16px;
			}
			.Button2014-success {
				background-color: #2778ec;
				border-color: #000000;
				color: black;
			}
			.Button2014 {
				-moz-user-select: none;
				border: 1px solid transparent;
				border-radius: 4px 4px 4px 4px;
				cursor: pointer;
				display: inline-block;
				font-size: 22px;
				font-weight: normal;
				line-height: 1.42857;
				margin-bottom: 0;
				padding: 6px 22px;
				text-align: center;
				vertical-align: middle;
				white-space: nowrap;
				font-family:  \"Segoe UI\", \"verdana\";
			}";			
			$f[]="</style>";
			$f[]="";
			$f[]="";
			$f[]="";
			$f[]=$addhead;
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-1.8.3.js\"></script>";
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery-ui-1.8.22.custom.min.js\"></script>";
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/js/jquery.blockUI.js\"></script>";
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/mouse.js\"></script>";
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/default.js\"></script>";
			$f[]="<script type=\"text/javascript\" language=\"javascript\" src=\"$SquidGuardIPWeb/XHRConnection.js\"></script>";
			$f[]="<META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
			$f[]="<META name=\"GENERATOR\" content=\"MSHTML 11.00.9600.17801\">
			</HEAD>     ";
			$f[]="<BODY>";
			$f[]="<DIV class=\"mainContent\" id=\"contentContainer\">";
			$f[]="<DIV class=\"title\" id=\"mainTitle\">$title</DIV>";
			$f[]="<DIV class=\"taskSection\" id=\"taskSection\">";
			
			return @implode("\n", $f);
	


	}