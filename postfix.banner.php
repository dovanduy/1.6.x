<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SMTP_BANNER"])){save();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{SMTP_BANNER}");
	$html="YahooWin5('890','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);
	$smtpd_banner=$main->GET('smtpd_banner');
	if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}
	

	
	$html="
	<div id='$t'></div>
	<div class=explain style='font-size:18px'>{SMTP_BANNER_TEXT}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:22px'>{SMTP_BANNER}:</td>
		<td class=legend style='font-size:22px'>". Field_text("SMTP_BANNER-$t",$smtpd_banner,"font-size:22;width:95%x;font-weight:bold")."</td>
	</tr>
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}","Save$t()","30px")."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		Loadjs('postfix.othervalues.progress.php?hostname={$_POST["hostname"]}');	
	}		
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('SMTP_BANNER',encodeURIComponent(document.getElementById('SMTP_BANNER-$t').value));
		XHR.sendAndLoad('$page', 'POST',X_Save$t);
	}
	
	
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);
	$main->SET_VALUE("smtpd_banner",url_decode_special_tool($_POST["SMTP_BANNER"]));
	
}


