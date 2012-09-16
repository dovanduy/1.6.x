<?php
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.browser.detection.inc');

if(isset($_POST["artica_username"])){checkLogin();exit;}

page();


function page(){
	$template="android";
	$page=CurrentPageName();
	$users=new usersMenus();
	$tplE=new templates();
	$title=$users->hostname." For Android/Tablets Login.";
	include_once(dirname(__FILE__)."/ressources/class.page.builder.inc");
	$p=new pagebuilder();
	$jsArtica=$p->jsArtica();
	$tpl=@file_get_contents("ressources/templates/android/logon.html");
	
	$tpl=str_replace("{TEMPLATE_LANG_LINK}", "", $tpl);
	$tpl=str_replace("{TEMPLATE_BODY_YAHOO}",$p->YahooBody(),$tpl);
	$tpl=str_replace("{TEMPLATE_HEAD}","<!-- HEAD TITLE: $TITLE_RESSOURCE -->\n$favicon\n$jquery\n$jsArtica\n". @implode("\n", $js)."\n$jslogon\n".@implode("\n", $css)."\n".@implode("\n", $log), $tpl);
	$sock=new sockets();
	$TITLE_RESSOURCE="ressources/templates/$template/TITLE";
	$favicon=$p->favicon($template);
	if(is_file($TITLE_RESSOURCE)){$title=@file_get_contents($TITLE_RESSOURCE);$title=str_replace("%server", $users->hostname, $title);}else{$title=$users->hostname;}
	$tpl=str_replace("{COPYRIGHT}","Copyright 2006 - ". date('Y').$lang2Link,$tpl);
	$tpl=str_replace("{copy-right}","Copyright 2006 - ". date('Y').$lang2Link,$tpl);
	$tpl=str_replace("{TEMPLATE_HEAD}","<!-- HEAD TITLE: $TITLE_RESSOURCE -->\n$favicon\n$jquery\n$jsArtica\n". @implode("\n", $js)."\n$jslogon\n".@implode("\n", $css)."\n".@implode("\n", $log), $tpl);
	$tpl=str_replace("{ARTICA_VERSION}",@file_get_contents("VERSION"),$tpl);
	$tpl=str_replace("{SQUID_VERSION}",$users->SQUID_VERSION,$tpl);
	$tpl=str_replace("{POSTFIX_VERSION}",$users->POSTFIX_VERSION,$tpl);
	$tpl=str_replace("{SAMBA_VERSION}",$users->SAMBA_VERSION,$tpl);
	$tpl=str_replace("{CROSSROADS_VERSION}",$users->CROSSROADS_VERSION,$tpl);
	$tpl=str_replace("{APACHE_VERSION}",$users->APACHE_VERSION,$tpl);
	$tpl=str_replace("{TEMPLATE_TITLE_HEAD}",$title,$tpl);
	$tpl=str_replace("{MEM_ACCOUNT}",$_COOKIE["mem-logon-user"],$tpl);

	
	$button=$tplE->_ENGINE_parse_body(button("{login}","SendLogonStart()","22px"));
	
	
	$tpl=str_replace("{LOGON_BUTTON}",$button,$tpl);
	$tpl=$tplE->_ENGINE_parse_body($tpl);
	
	$scripts="
	<script>
	function SendLogon(e){
		if(checkEnter(e)){SendLogonStart();}
	}
	
var x_AndroidLogon=function(obj){
	 if(document.getElementById('anim')){document.getElementById('anim').innerHTML='';}
	 if(document.getElementById('YouCanAnimateIt')){document.getElementById('YouCanAnimateIt').innerHTML='';}
     var tempvalue=obj.responseText;
	 var re = new RegExp(/location:(.+)/);
	 m=re.exec(tempvalue);
	  if(m){
		var url=m[1]; 
		 document.location.href=url;
		 return ;
      }	 
	 alert(tempvalue);
	 document.location.href='$page';
	}	
	
	
	function SendLogonStart(){
		if(document.getElementById('YouCanAnimateIt')){document.getElementById('YouCanAnimateIt').innerHTML='<img src=\"/img/preloader.gif\">';}
		var XHR = new XHRConnection();
		if(!document.getElementById('artica_username')){alert('missing tag `artica_username`');}
		if(!document.getElementById('artica_password')){alert('missing tag `artica_password`');}
		XHR.appendData('artica_username',document.getElementById('artica_username').value);
		XHR.appendData('artica_password',MD5(document.getElementById('artica_password').value));
		Set_Cookie('mem-logon-user', document.getElementById('artica_username').value, '3600', '/', '', '');
		XHR.sendAndLoad('$page', 'POST',x_AndroidLogon);
		
	}	
</script>	
	
	";
	
	$tpl=str_replace("{SCRIPTS}",$scripts,$tpl);
	
	
	
	

echo $tpl;


}


function checkLogin(){
	include("ressources/settings.inc");
	$sock=new sockets();
	
	writelogs("Testing logon....{$_POST["artica_username"]}",__FUNCTION__,__FILE__,__LINE__);
	writelogs("Testing logon.... password:{$_POST["artica_password"]}",__FUNCTION__,__FILE__,__LINE__);	
	$FixedLanguage=$sock->GET_INFO("FixedLanguage");
	
	if($_SESSION["uid"]<>null){echo "location:android.index.php";return;}
	
	while (list ($index, $value) = each ($_SERVER) ){
		$notice[]="$index:$value";
	}
	
	if($_GLOBAL["ldap_admin"]==null){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{ldap_username_corrupt_text}");
		return null;
	}
	
	$md5submitted=$_POST["artica_password"];
	$md5Manager=md5(trim($_GLOBAL["ldap_password"]));
	if(trim($FixedLanguage)<>null){$_POST["lang"]=$FixedLanguage;}

	if(trim(strtolower($_POST["artica_username"]))==trim(strtolower($_GLOBAL["ldap_admin"]))){
		if($md5Manager<>$md5submitted){
			writelogs("Testing logon.... password:{$_POST["artica_password"]}!==\"{$_GLOBAL["ldap_password"]}\"",__FUNCTION__,__FILE__,__LINE__);	
			artica_mysql_events("Failed to logon on the Artica Web console from {$_SERVER["REMOTE_HOST"]}",@implode("\n",$notice),"security","security");
			echo "Bad password";
			return null;
		}else{
			
			artica_mysql_events("Success to logon on the Artica Web console from {$_SERVER["REMOTE_HOST"]} as SuperAdmin",@implode("\n",$notice),"security","security");
			//session_start();
			$_SESSION["uid"]='-100';
			$_SESSION["groupid"]='-100';
			$_SESSION["passwd"]=$_GLOBAL["ldap_password"];
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			setcookie("artica-language", $_POST["lang"], time()+172800);
			$_SESSION["detected_lang"]=$_POST["lang"];
			$_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"
			';
		$tpl=new templates();
		$sock->getFrameWork("squid.php?clean-catz-cache=yes");
		echo("location:android.index.php");
		exit;
		}
	}
	
	
	echo("location:android.logon.php");
	

	
}

	