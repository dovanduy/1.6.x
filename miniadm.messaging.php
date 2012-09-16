<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["messaging-left"])){messaging_left();exit;}
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
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{mymessaging}</H1>
		<p>{mymessaging_text}</p>
		
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?messaging-left=yes');
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function messaging_left(){
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
	if(!is_numeric($EnableFetchmail)){$EnableFetchmail=0;}
	
	if($users->AllowFetchMails){
		if($users->fetchmail_installed){
			if($EnableFetchmail==1){
				$t[]=Paragraphe("fetchmail-rule-64.png", "{myretreival_mailrules}", "{retreival_mailrules_text}","miniadm.fetchmail.php");
				
			}
			
		}
	}
	
	if($users->AllowEditAliases){
		
		$t[]=Paragraphe("rebuild-mailboxes-64.png", "{aliases}", "{enduser_aliases_text}","miniadm.aliases.php");
		
	}

	if($users->AllowUserMaillog){
		
		$t[]=Paragraphe("64-mailevents.png", "{messaging_events}", "{messaging_events_text}","miniadm.maillog.php");
		
	}	
	
	$html="<div class=BodyContent><center><div style='width:700px'>".CompileTr3($t,"none")."</div></center></div>";
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