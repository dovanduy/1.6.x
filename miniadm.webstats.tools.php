<?php
session_start();
$_SESSION["MINIADM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}


if(isset($_GET["members-table-js"])){members_table_js();exit;}
if(isset($_POST["members-table-perform"])){members_table_perform();exit;}

if(isset($_GET["categorize-day-table-js"])){categorize_day_table_js();exit;}
if(isset($_POST["categorize-day-table-perform"])){categorize_day_table_perform();exit;}

if(isset($_GET["sumary-counters-table-js"])){sumary_counter_table_js();exit;}
if(isset($_POST["sumary-counters-table-perform"])){sumary_counter_table_perform();exit;}



function categorize_day_table_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$xtime=$_GET["categorize-day-table-js"];
	$day=time_to_date($xtime);
	$ask=$tpl->javascript_parse_text("{squidstats_gen_categorize_table} $day ?");
	$t=time();
	$html="
	var xf$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	}
	
	
	function f$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('categorize-day-table-perform','$xtime');
	XHR.sendAndLoad('$page', 'POST',xf$t);
	}
		
	f$t();
	";
	
	echo $html;	
}

function sumary_counter_table_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$xtime=$_GET["sumary-counters-table-js"];
	$day=time_to_date($xtime);
	$ask=$tpl->javascript_parse_text("{squidstats_gen_categorize_table} $day ?");
	$t=time();
	$html="
	var xf$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	}
	
	
	function f$t(){
	
	var XHR = new XHRConnection();
	XHR.appendData('sumary-counters-table-perform','$xtime');
	XHR.sendAndLoad('$page', 'POST',xf$t);
	}
	
	f$t();
	";
	
	echo $html;	
}

function members_table_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$xtime=$_GET["members-table-js"];
	$day=time_to_date($xtime);
	$ask=$tpl->javascript_parse_text("{squidstats_gen_members_table} $day ?");
	$t=time();
	$html="
	var xf$t= function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		 }		

	
		function f$t(){
			if(!confirm('$ask')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('members-table-perform','$xtime');
			XHR.sendAndLoad('$page', 'POST',xf$t);
		}
			
		f$t();	
	";
	
	echo $html;
}

function members_table_perform(){
	$sock=new sockets();
	$xtime=$_POST["members-table-perform"];
	$sock->getFrameWork("squidstats.php?table-members-time=yes&xtime=$xtime");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_restore_launched_explain}");	
}

function categorize_day_table_perform(){
	$sock=new sockets();
	$xtime=$_POST["categorize-day-table-perform"];
	$sock->getFrameWork("squidstats.php?categorize-day-table=yes&xtime=$xtime");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_restore_launched_explain}");
}
function sumary_counter_table_perform(){
	$sock=new sockets();
	$xtime=$_POST["sumary-counters-table-perform"];
	$sock->getFrameWork("squidstats.php?sumary-counters-table=yes&xtime=$xtime");
	$tpl=new templates();
	sleep(4);
	echo $tpl->javascript_parse_text("{task_restore_launched_explain}");	
}