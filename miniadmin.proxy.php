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
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["left"])){left();exit;}
if(isset($_GET["proxy-settings"])){section_architecture_tabs();exit;}
if(isset($_GET["web-filtering"])){web_filtering();exit;}
if(isset($_GET["tasks"])){tasks();exit;}
if(isset($_GET["monitor"])){monitor();exit;}
if(isset($_GET["architecture-behavior"])){proxy_behavior();exit;}
if(isset($_POST["exclusive_reverse_proxy"])){proxy_behavior_save();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&startup={$_GET["startup"]}&title={$_GET["title"]}')</script>", $content);
	echo $content;	
}

function section_architecture_tabs(){
	
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
	LoadAjax('section-$t','squid.main.quicklinks.php?architecture-tabs=yes&byminiadm=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}




function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$users=new usersMenus();
	
	if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
		$dynamic_acls_newbee="&nbsp;|&nbsp;<a href=\"miniadmin.proxy.dynamic.acls.php\"><strong>{dynamic_acls_newbee}</strong></a>";
		
			
			
	}	
	$title="{APP_PROXY}";
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	
	$start="LoadAjax('left-$t','$page?left=yes');";
	$head=null;
	if(!$users->AsSquidAdministrator){
		$start="LoadAjax('left-$t','$page?web-filtering=yes');";
	}
	
	if($_GET["startup"]==null){
		if($users->AsSquidAdministrator){$head."
		&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"javascript:LoadAjax('left-$t','$page?left=yes');\"><strong>{APP_PROXY}</strong></a>
		&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"javascript:LoadAjax('left-$t','$page?proxy-settings=yes');\"><strong>{proxy_main_settings}</strong></a>
		";
		}
		$head=$head."&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"javascript:LoadAjax('left-$t','$page?web-filtering=yes');\"><strong>{WEB_FILTERING}</strong></a>";
		
		if($users->AsSquidAdministrator){$head=$head."
		&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"javascript:LoadAjax('left-$t','$page?tasks=yes');\"><strong>{tasks}</strong></a>";
		}
		$head=$head."&nbsp;|&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"javascript:LoadAjax('left-$t','$page?monitor=yes');\"><strong>{monitor}</strong></a>
		$dynamic_acls_newbee";
		
	}else{
		$start="LoadAjax('left-$t','$page?{$_GET["startup"]}=yes');";
		$title="{proxy_main_settings}";
		
	}
	if($_GET["title"]<>null){$title="{{$_GET["title"]}}";}
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>$head
	</div>
		
		
		<H1>$title</H1>
		<p>{APP_PROXY_TEXT}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='left-$t' class=BodyContent></div>
	
	<script>
		$start
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function monitor(){
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
	LoadAjax('section-$t','prxy.monitor.php?tabs=yes&byenduser-interface=yes&byminiadm=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}
function left(){
	
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
		LoadAjax('section-$t','squid.main.quicklinks.php?function=section_status&byminiadm=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tasks(){
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
		LoadAjax('section-$t','squid.statistics.tasks.php&byminiadm=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function web_filtering(){
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
	LoadAjax('section-$t','dansguardian2.php?without-acl=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function proxy_behavior(){
	$boot=new boostrap_form();
	$boot->set_formtitle("{proxy_behavior}");
	$boot->set_formdescription("{proxy_behavior_explain}");
	$sock=new sockets();
	
	$exclusive_internet_proxy=1;
	$exclusive_reverse_proxy=0;
	$mixed_mode=0;
	
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$SquidActHasReverseOnly=$sock->GET_INFO("SquidActHasReverseOnly");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($SquidActHasReverseOnly)){$SquidActHasReverseOnly=0;}	
	
	if($SquidActHasReverseOnly==1){
		$exclusive_internet_proxy=0;
		$exclusive_reverse_proxy=1;
		$mixed_mode=0;		
	}
	
	if($SquidActHasReverseOnly==0){
		if($SquidActHasReverse==1){
			$exclusive_internet_proxy=0;
			$exclusive_reverse_proxy=0;
			$mixed_mode=1;
		}
	}
	
	$boot->set_checkbox("exclusive_reverse_proxy", "{exclusive_reverse_proxy}", $exclusive_reverse_proxy);
	$boot->set_checkbox("exclusive_internet_proxy", "{exclusive_internet_proxy}", $exclusive_internet_proxy);
	$boot->set_checkbox("mixed_mode", "{mixed_mode}", $mixed_mode);
	$boot->set_button("{apply}");
	echo $boot->Compile();
}

function proxy_behavior_save(){
	$squid=new squidbee();
	$sock=new sockets();
	$exclusive_reverse_proxy=$_POST["exclusive_reverse_proxy"];
	$exclusive_internet_proxy=$_POST["exclusive_internet_proxy"];
	$mixed_mode=$_POST["mixed_mode"];
	if($exclusive_reverse_proxy==1){
		$sock->SET_INFO("SquidActHasReverse", 1);
		$sock->SET_INFO("SquidActHasReverseOnly", 1);
		$sock->SET_INFO("SquidOldHTTPPort",$squid->listen_port);
		$sock->SET_INFO("SquidOldHTTPPort2",$squid->second_listen_port);
		$sock->SET_INFO("SquidOldSSLPort",$squid->ssl_port);
		$squid->listen_port=80;
		$squid->second_listen_port=0;
		$squid->ICP_PORT=0;
		$squid->HTCP_PORT=0;
		$squid->ssl_port=443;
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			return;
		}
		
		return;
	}
	
	if($exclusive_internet_proxy==1){
		$SquidOldHTTPPort=$sock->GET_INFO("SquidOldHTTPPort");
		$SquidOldSSLPort=$sock->GET_INFO("SquidOldSSLPort");
		$SquidOldHTTPPort2=$sock->GET_INFO("SquidOldHTTPPort2");
		$sock->SET_INFO("SquidActHasReverse", 0);
		$sock->SET_INFO("SquidActHasReverseOnly", 0);
		if(is_numeric($SquidOldHTTPPort)){
			if($SquidOldHTTPPort>0){
				$squid->listen_port=$SquidOldHTTPPort;
			}
		}
		if(is_numeric($SquidOldHTTPPort2)){
			if($SquidOldHTTPPort2>0){
				$squid->second_listen_port=$SquidOldHTTPPort2;
			}
		}		
		if(is_numeric($SquidOldSSLPort)){
			if($SquidOldSSLPort>0){
				$squid->ssl_port=$SquidOldSSLPort;
			}
		}		
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			return;
		}
	
		return;
	}	
	
	if($mixed_mode==1){
		$sock->SET_INFO("SquidActHasReverse", 1);
		if($squid->listen_port<>80){
			$sock->SET_INFO("SquidOldHTTPPort",$squid->listen_port);
			$sock->SET_INFO("SquidOldHTTPPort2",$squid->second_listen_port);
			$squid->second_listen_port=$squid->listen_port;
			$squid->listen_port=80;
			
		}
		
		if($squid->ssl_port<>443){
			$sock->SET_INFO("SquidOldSSLPort",$squid->listen_port);
			$squid->listen_port=443;
		}
		
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			return;
		}
		
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-apache-src=yes");
	
	
}



function messaging_right(){
	$sock=new sockets();
	$users=new usersMenus();

	if(count($t)==0){return;}
	$tpl=new templates();
	$html="<div class=BodyContent>".CompileTr2($t,"none")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}