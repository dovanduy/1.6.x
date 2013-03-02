<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["left"])){left();exit;}
if(isset($_GET["proxy-settings"])){section_architecture_tabs();exit;}
if(isset($_GET["web-filtering"])){web_filtering();exit;}
if(isset($_GET["tasks"])){tasks();exit;}
if(isset($_GET["monitor"])){monitor();exit;}



main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function section_architecture_tabs(){
	
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
	LoadAjax('section-$t','squid.main.quicklinks.php?architecture-tabs=yes');
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
	
	$start="LoadAjax('left-$t','$page?left=yes');";
	
	if(!$users->AsSquidAdministrator){
		$start="LoadAjax('left-$t','$page?web-filtering=yes');";
	}
	
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
	";
	if($users->AsSquidAdministrator){$html=$html."
		&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:LoadAjax('left-$t','$page?left=yes');\"><strong>{APP_PROXY}</strong></a>
		&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:LoadAjax('left-$t','$page?proxy-settings=yes');\"><strong>{proxy_main_settings}</strong></a>
		";
	}
	$html=$html."&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:LoadAjax('left-$t','$page?web-filtering=yes');\"><strong>{WEB_FILTERING}</strong></a>";	
		
	if($users->AsSquidAdministrator){$html=$html."
		&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:LoadAjax('left-$t','$page?tasks=yes');\"><strong>{tasks}</strong></a>";
		}			
	$html=$html."&nbsp;|&nbsp;<a href=\"javascript:blur();\" 
		OnClick=\"javascript:LoadAjax('left-$t','$page?monitor=yes');\"><strong>{monitor}</strong></a>			
		$dynamic_acls_newbee
		
		</div>
		
		
		<H1>{APP_PROXY}</H1>
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
	LoadAjax('section-$t','prxy.monitor.php?tabs=yes&byenduser-interface=yes');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}
function left(){
	
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
		LoadAjax('section-$t','squid.main.quicklinks.php?function=section_status');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tasks(){
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
		LoadAjax('section-$t','squid.statistics.tasks.php');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function web_filtering(){
	$t=time();
	
	$html="<div id='section-$t'></div>
	<script>
	LoadAjax('section-$t','dansguardian2.php');
	</script>
	";
	
	
	$tpl=new templates();
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function messaging_right(){
	$sock=new sockets();
	$users=new usersMenus();

	if(count($t)==0){return;}
	$tpl=new templates();
	$html="<div class=BodyContent>".CompileTr2($t,"none")."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}