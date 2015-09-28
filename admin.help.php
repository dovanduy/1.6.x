<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	if(isset($_GET["content"])){translate();exit;}
	if(isset($_GET["loadhelp"])){loadhelp();exit;}
	
	$title=$tpl->javascript_parse_text("{help}");
	$loadhelp=urlencode($_GET["text"]);
	header("content-type: application/x-javascript");
	echo "YahooWinT(600,'$page?loadhelp=$loadhelp','$title');";
	
	
function loadhelp(){
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body(base64_decode($_GET["loadhelp"]));
	
	echo "<div class=explain style='font-size:14px;width:90%'>$text</div>";
}

function translate(){
	
	
	$md5=md5($_GET["content"].$_GET["lang"]);
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/help")){
		@mkdir("/usr/share/artica-postfix/ressources/logs/web/help");
	}
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/help/$md5")){
			echo @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5");
			return;
		}
	$tpl=new templates();
	$content=base64_decode($_GET["content"]);
	$html=$tpl->_ENGINE_parse_body($content."<hr>");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/help/$md5", $html);
	echo $html;
}
