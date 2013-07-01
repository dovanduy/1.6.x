<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["items"])){report_items();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}


main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function GetPrivs(){
		$users=new usersMenus();
		if($users->AsSystemAdministrator){return true;}
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
		
		
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"$page\">{events} &laquo;{system}&raquo;</a>
		</div>
		<H1>{events} &laquo;{system}&raquo;</H1>
		<p>{system_events_text}</p>
	</div>	
	<div id='webstats-middle-$ff' class=BodyContent></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function webstats_middle(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$boot=new boostrap_form();
	$sock=new sockets();
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	
	$array["{system_events}"]="miniadm.system.syslog-query.php?section=yes";
	$array["{artica_events}"]="miniadm.system.artica-events.php?section=yes";
	
	if($EnableSyslogDB==1){
		$array["{syslog}"]="miniadm.system.syslog.php";
		$array["{syslog_store}"]="miniadm.system.syslogstore.php";
	}
	echo $boot->build_tab($array);

}	
