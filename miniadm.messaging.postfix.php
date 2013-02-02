<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-tabs"])){messaging_tabs();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
if(isset($_GET["postfix"])){section_postfix();exit;}
if(isset($_GET["security"])){section_security();exit;}
if(isset($_GET["queues"])){section_queues();exit;}
if(isset($_GET["wbl"])){section_wbl();exit;}
if(isset($_GET["postfwd2"])){section_postfwd2();exit;}


$users=new usersMenus();
if(!$users->AsPostfixAdministrator){header('location:miniadm.messaging.php');die();}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.messaging.php\">{mymessaging}</a></div>
		
		<H1>{MESSAGING_SERVICE}</H1>
		<p>{MESSAGING_SERVICE_TEXT}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='messaging-$t'></div>
	
	<script>
		LoadAjax('messaging-$t','$page?messaging-tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function messaging_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["postfix"]="{APP_POSTFIX}";
	$array["security"]="{security}";
	$array["queues"]="{queue_management}";
	$array["wbl"]="{white list}";
	$array["postfwd2"]="{APP_POSTFWD2}";
	$array["smtp_events"]="{POSTFIX_EVENTS}";
	$array["status"]="{status}";
	
	
	
	$fontsize='15';
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="status"){
			$tab[]="<li><a href=\"admin.index.services.status.php?status=yes&section=postfix_services&filterby=1;1;0;0\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="smtp_events"){
			$tab[]="<li><a href=\"postfix.events.new.php?quicklinks=yes&miniadm=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		
			
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_miniadmpostfix' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_miniadmpostfix').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function section_postfix(){
	$t=time();
	$html="<div id='$t'></div>
	<script>
		$('#BodyContent').remove();
		document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
		AnimateDiv('BodyContent');Loadjs('postfix.index.php?font-size=14');
	</script>
	";
	echo $html;
	
	
}
function section_security(){
	$t=time();
	$html="<div id='$t'></div>
	<script>
	LoadAjax('$t','postfix.security.php?tab=yes&font-size=14');
	</script>
	";
	echo $html;


}
function section_queues(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('postfix.queue.monitoring.php?inline-js=yes&font-size=14')
	</script>
	";
	echo $html;	
}
function section_wbl(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('whitelists.admin.php?js=yes&js-in-line=yes&font-size=14')
	</script>
	";
	echo $html;	
	
}
function section_postfwd2(){
	$t=time();
	$html="
	<div id='$t'></div>
	<script>
	$('#BodyContent').remove();
	document.getElementById('$t').innerHTML=\"<div id='BodyContent'></div>\";
	AnimateDiv('BodyContent');Loadjs('postfwd2.php?instance=master&newinterface=yes')
	</script>
	";
	echo $html;	
}

