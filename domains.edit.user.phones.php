<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/charts.php');
include_once ('ressources/class.mimedefang.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}

//permissions	
$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();
$modify_user = 1;
if ($_SESSION ["uid"] != $_GET["userid"]) {$modify_user = 0;}
if ($change_aliases == 1) {$modify_user = 1;}
if ($modify_user == 0) {die ( "alert('No permissions:{$_SESSION ["uid"]}\\nchange_aliases=$usersprivs->AllowEditAliases')" );}

if(isset($_POST["phone"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	$uid=$_GET["uid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{phone_title}");
	$html="YahooWinBrowse('550','$page?popup=yes&uid=$uid','$title')";
	echo $html;
}

function popup(){
	$uid=$_GET["uid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$ct=new user($uid);
	$t=time();
	$html="
	<div id='animate-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td valign='middle' width=1%><img src='img/icon-mobile-phone.png'></td>
		<td class=legend style='font-size:16px'>{mobile}:</td>
		<td>". Field_text("mobile-$t",$ct->mobile,"font-size:16px;width:220px",null,null,null,false,"CheckPhones$t(event)")."</td>
	</tr>
	<tr>
		<td valign='middle' width=1%><img src='img/20-phone.png'></td>
		<td class=legend style='font-size:16px'>{phone}:</td>
		<td>". Field_text("phone-$t",$ct->telephoneNumber,"font-size:16px;width:220px",null,null,null,false,"CheckPhones$t(event)")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SavePhones$t()","18px")."</td>
	</tr>
	</table>
	
	<script>
var x_SavePhones$t= function (obj) {
	document.getElementById('animate-$t').innerHTML='';
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return false;}
	YahooWinBrowseHide();
	if(document.getElementById('container-users-tabs')){RefreshTab('container-users-tabs');}
	
}

function SavePhones$t(){
  	 var XHR = new XHRConnection();
     XHR.appendData('phone',document.getElementById('phone-$t').value);
	 XHR.appendData('mobile',document.getElementById('mobile-$t').value);
	 XHR.appendData('uid','$uid');
     AnimateDiv('animate-$t');   
                                		      	
     XHR.sendAndLoad('$page', 'POST',x_SavePhones$t);		  
}

function CheckPhones$t(e){
	if(checkEnter(e)){SavePhones$t();}
}

</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function Save(){
	$ct=new user($_POST["uid"]);
	if(!$ct->SaveUserPhones($_POST["phone"],$_POST["mobile"])){echo $ct->error;}
	
}

	
	

