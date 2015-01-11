<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
session_start();
include_once("ressources/class.templates.inc");
include_once("ressources/class.ldap.inc");
include_once("ressources/class.main_cf.inc");
include_once("ressources/class.maincf.multi.inc");
$user=new usersMenus();
if($user->AsPostfixAdministrator==false){
	$tpl=new templates();
	echo "alert('".$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}')."')";
	die();
	}
if(isset($_GET["otherpage"])){otherpage();exit;}	
if(isset($_POST["undisclosed_recipients_header"])){SaveForm();exit();}


js();

function js(){
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{other_settings}');
	$html="
	function PostFixOtherLoad(){
		YahooWin5(990,'$page?otherpage=yes&hostname={$_GET["hostname"]}','$title');
		
	}
	
	

	
	PostFixOtherLoad();";
	echo $html;
}



function otherpage(){
	$page=CurrentPageName();
	$main=new maincf_multi($_GET["hostname"]);
	$enable_original_recipient=$main->GET("enable_original_recipient");
	$smtpd_discard_ehlo_keywords=$main->GET("smtpd_discard_ehlo_keywords");
	if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
	$undisclosed_recipients_header=$main->GET("undisclosed_recipients_header");
	
	if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
	
	
	$t=time();

$html="
<div id='otherpagedvi' style='width:98%' class=form>
		
<table style='width:99%'>
<tr>
	<td class=legend nowrap style='font-size:22px'>{undisclosed_recipients_header}:</td>
	<td>" . Field_text("undisclosed_recipients_header-$t",$undisclosed_recipients_header,'width:90%;font-size:22px')."</td>
	<td>" . help_icon("{undisclosed_recipients_header_text}")."</td>
</tr>
<tr>
	<td class=legend nowrap style='font-size:22px'>{smtpd_discard_ehlo_keywords}:</td>
	<td>" . Field_text("smtpd_discard_ehlo_keywords-$t",$smtpd_discard_ehlo_keywords,'width:90%;font-size:22px')."</td>
	<td>" . help_icon("{smtpd_discard_ehlo_keywords_text}")."</td>
</tr>			
			
			
<tr>
	<td class=legend nowrap style='font-size:22px'>{enable_original_recipient}:</td>
	<td>" .Field_checkbox_design('enable_original_recipient',1,$enable_original_recipient)."</td>
	<td>" . help_icon("{enable_original_recipient_text}")."</td>
</tr>



<tr><td colspan=2 align='right'><hr>".button("{apply}", "SavePostfixOtherSection$t()","36")."</td></tr>
</table>
</div>
<script>
	var x_SavePostfixOtherSection= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		
	}	
	
	function SavePostfixOtherSection(){
		var undisclosed_recipients_header=document.getElementById('undisclosed_recipients_header').value;
		var enable_original_recipient=document.getElementById('enable_original_recipient').value;	
		var XHR = new XHRConnection();
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('enable_original_recipient',document.getElementById('enable_original_recipient-$t').value);
		XHR.appendData('undisclosed_recipients_header',document.getElementById('undisclosed_recipients_header-$t').value);
		XHR.appendData('smtpd_discard_ehlo_keywords',document.getElementById('smtpd_discard_ehlo_keywords-$t').value);
		
		XHR.sendAndLoad('$page', 'POST',x_SavePostfixOtherSection$t);		  
	}
</script>		
";


	
	
	

$tpl=new Templates();
echo $tpl->_ENGINE_parse_body($html);
}

function SaveForm(){
	$main=new maincf_multi($_POST["hostname"]);
	$main->SET_VALUE("enable_original_recipient", $_POST["enable_original_recipient"]);
	$main->SET_VALUE("undisclosed_recipients_header", $_POST["undisclosed_recipients_header"]);
	$main->SET_VALUE("smtpd_discard_ehlo_keywords", $_POST["smtpd_discard_ehlo_keywords"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-others-values=yes&hostname={$_POST["hostname"]}");
	
	
}