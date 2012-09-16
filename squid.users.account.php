<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["new-password"])){SaveAccount();exit;}
page();




function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	
	<table style='width:100%' class=form>
	<tr>
		<td colspan=2 style='font-size:22px'>{myaccount}</td>
	</tr>
	<tr>
	<td width=1% valign='top'><img src='img/user-server-128.png'></td>
	<td width=100%' valign='top'><span id='$t'></span>
	</tr>
	</table>
	<script>
		LoadAjax('$t','$page?popup=yes&t=$t');
	</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{username2}:</td>
		<td style='font-size:16px'>". Field_text("email-$t",$_SESSION["email"],"font-size:16px;padding:3px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;text-align:right'>{password}:</td>
		<td>". Field_password("register-password",null,"font-size:18px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;text-align:right'>{password_confirm}:</td>
		<td>". Field_password("register-password2",null,"font-size:18px;width:250px")."</td>
	</tr>	
	<tr>	
	<td colspan=2 align='right'><hr>". button("{apply}","SaveMyAccount()",18)."</td>
	</tr>
	</table>
	<script>
	
	var X_SaveMyAccount=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
	}  

	function SaveMyAccount(userid,md,email){
		var XHR = new XHRConnection();
		 var p1=document.getElementById('register-password').value;
		 var p2=document.getElementById('register-password2').value;
		 if(p1!==p2){alert('password mismatch!');return;}
		XHR.appendData('email',document.getElementById('email-$t').value);
	    XHR.appendData('new-password',MD5(trim(document.getElementById('register-password').value)));
		XHR.sendAndLoad('$page', 'POST',X_SaveMyAccount);    		
	}		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SaveAccount(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE usersisp SET user_password='{$_POST["new-password"]}',email='{$_POST["email"]}' WHERE userid={$_SESSION["uid"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
	
}
