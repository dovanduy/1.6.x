<?php
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["lang"])){saveLang();exit;}
	
js();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{language}");
	$html="YahooWin4('500','$page?popup=yes','$title')";
	echo $html;
	
}

function popup(){
		$tpl=new templates();
		$page=CurrentPageName();	
		$html=new htmltools_inc();
		$lang=$html->LanguageArray();
		$sock=new sockets();
		$FixedLanguage=$sock->GET_INFO("FixedLanguage");
		$enabled=0;
		if($FixedLanguage<>null){$_COOKIE["artica-language"]=$FixedLanguage;$enabled=1;}
		$field_lang=Field_array_Hash($lang,'lang',$_COOKIE["artica-language"],"style:font-size:22px");
		
		$html="
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{language}:</td>
			<td>$field_lang</td>
			
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{fix_value}:</td>
			<td>". Field_checkbox("Fixit", 1,$enabled)."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>".button("{apply}", "ChangeAdminLang()",34)."</td></td>
		</tr>
		</table>		
<script>
	var x_ChangeAdminLang= function (obj) {
	 var results=obj.responseText;
	 if(results.length>1){alert(results);}
	 CacheOff();
	 YahooWin4Hide();
	}

function ChangeAdminLang(){
	var XHR = new XHRConnection();
	var lang=document.getElementById('lang').value;
	if(document.getElementById('Fixit').checked){XHR.appendData('Fixit',lang);}else{XHR.appendData('Fixit','');}
	Set_Cookie('artica-language', lang, '3600', '/', '', '');
	XHR.appendData('lang',lang);
	XHR.sendAndLoad('$page', 'POST',x_ChangeAdminLang);		
	
}
</script>
";
		echo $tpl->_ENGINE_parse_body($html);
	
}

function saveLang(){
	$sock=new sockets();
	$sock->SET_INFO("FixedLanguage", $_POST["Fixit"]);
	$_SESSION["detected_lang"]=$_POST["lang"];
	$FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
	$sock->SET_INFO($FileCookyKey, $_POST["Changelang"]);
}
