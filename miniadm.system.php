<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}



main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function tabs(){
	
	$users=new usersMenus();
	$Privileges_members_admins=Privileges_members_admins();
	$AS_PROXY_SECTION=false;
	if($users->SQUID_INSTALLED){$AS_PROXY_SECTION=true;}
	if($users->APP_FTP_PROXY){$AS_PROXY_SECTION=true;}
	if($users->NGINX_INSTALLED){$AS_PROXY_SECTION=true;}	
	
	if(isNetSessions() ) {
		$array["{network_services}"]="miniadm.network.php?webstats-middle=yes&title=yes";
	}
	
	$array["{tasks}"]="miniadm.system.schedules.php";
	
	$boot=new boostrap_form();
	if( ($users->AsSquidAdministrator) OR ($users->AsWebMaster) ){
		if($users->NGINX_INSTALLED){
			if($AS_PROXY_SECTION){$array["{certificates_center}"]="miniadmin.certificates.php?tabs=yes&title=yes";}
				
		}
	}
	if($Privileges_members_admins){
		$array["{users_and_groups}"]="miniadmin.system.members.php?tabs=yes&title=yes";
	}
	echo $boot->build_tab($array);
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$users=new usersMenus();
	$start="LoadAjax('tabs-$t','$page?tabs=yes');";
	
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{manage_system}</H1>
		<p>{manage_system_text}</p>
	</div>	
	<div id='tabs-$t' class=BodyContent></div>
	
	<script>
		$start
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
