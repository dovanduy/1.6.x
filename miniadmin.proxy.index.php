<?php
session_start();
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["tabs"])){tabs();exit;}



main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&startup={$_GET["startup"]}&title={$_GET["title"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$users=new usersMenus();
	$title="{APP_PROXY}";
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	
	$start="LoadAjax('left-$t','$page?left=yes');";
	$head=null;
	if(!$users->AsSquidAdministrator){
		$start="LoadAjax('left-$t','$page?web-filtering=yes');";
	}
	$start="LoadAjax('left-$t','$page?tabs=yes');";
	$title="{proxy_main_settings}";
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>$head
	</div>
		
		
		<H1>$title</H1>
		<p>{APP_PROXY_TEXT}</p>
		
	</div>	
	<div id='left-$t' class=BodyContent></div>
	
	<script>
		$start
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function tabs(){
	$users=new usersMenus();
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($users->AsSquidAdministrator){
		if($users->SQUID_INSTALLED){
			if($SQUIDEnable==1){
				$array["{proxy_main_settings}"]="miniadmin.proxy.php?content=yes&startup=proxy-settings&title-none=yes";
			}
		}
	
	

		if($users->NGINX_INSTALLED){
			$array["{reverse_proxy_settings}"]="miniadmin.proxy.reverse.php?tabs=yes&subtitle=yes";
		}	
		
	
		if($users->APP_FTP_PROXY){
			$array["{proxy_ftp_main_settings}"]="miniadmin.ftp.proxy.php?content=yes";
		}
		
		if($users->RSYNC_INSTALLED){
			$array["{system_mirrors}"]="miniadmin.proxy.debian.mirrors.php";
		}
	}
	
	if($users->AsHotSpotManager){
		$array["{hotspot}"]="miniadmin.proxy.hotspot.manager.php";
		
	}
	
		
	$page=CurrentPageName();
	$mini=new boostrap_form();
	echo $mini->build_tab($array);	
}
