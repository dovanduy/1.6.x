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
	if(isset($_POST["detect_8bit_encoding_header"])){save();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{MIME_OPTIONS}");
	$html="YahooWin5('550','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	
	$main=new maincf_multi($_GET["hostname"],$_GET["ou"]);

	$detect_8bit_encoding_header=$main->GET("detect_8bit_encoding_header");
	$disable_mime_input_processing=$main->GET("disable_mime_input_processing");
	$disable_mime_output_conversion=$main->GET("disable_mime_output_conversion");
	$mime_nesting_limit=$main->GET("mime_nesting_limit");
	
	if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
	if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
	if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
	if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}

	
	$html="
	<div id='$t'></div>
	
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:16px'>{detect_8bit_encoding_header}:</td>
		<td class=legend style='font-size:16px'>". Field_checkbox("detect_8bit_encoding_header-$t", 1,$detect_8bit_encoding_header)."</td>
		<td width=1%>".help_icon("{detect_8bit_encoding_header_text}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{disable_mime_output_conversion}:</td>
		<td class=legend style='font-size:16px'>". Field_checkbox("disable_mime_output_conversion-$t", 1,$disable_mime_output_conversion)."</td>
		<td width=1%>".help_icon("{disable_mime_output_conversion_text}")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{disable_mime_input_processing}:</td>
		<td class=legend style='font-size:16px'>". Field_checkbox("disable_mime_input_processing-$t", 1,$disable_mime_input_processing)."</td>
		<td width=1%>".help_icon("{disable_mime_input_processing_text}")."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:16px'>{mime_nesting_limit}</strong>:</td>
		<td>" . Field_text("mime_nesting_limit-$t",$mime_nesting_limit,'width:70px;font-size:16px;padding:3px;text-align:right')." </td>
		<td>". help_icon('{mime_nesting_limit_text}')."</td>
	</tr>				
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t').innerHTML='';
		}		
	
	function Save$t(){
		detect_8bit_encoding_header=0;
		disable_mime_output_conversion=0;
		disable_mime_input_processing=0;
		
		if(document.getElementById('detect_8bit_encoding_header-$t').checked){detect_8bit_encoding_header=1;}
		if(document.getElementById('disable_mime_output_conversion-$t').checked){disable_mime_output_conversion=1;}
		if(document.getElementById('disable_mime_input_processing-$t').checked){disable_mime_input_processing=1;}
		var XHR = new XHRConnection();
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('detect_8bit_encoding_header',detect_8bit_encoding_header);
		XHR.appendData('disable_mime_output_conversion',disable_mime_output_conversion);
		XHR.appendData('disable_mime_input_processing',disable_mime_input_processing);
		XHR.appendData('mime_nesting_limit',document.getElementById('mime_nesting_limit-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_Save$t);
	}
	
	
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$main=new maincf_multi($_POST["hostname"],$_POST["ou"]);
	$main->SET_VALUE("detect_8bit_encoding_header",$_POST["detect_8bit_encoding_header"]);
	$main->SET_VALUE("disable_mime_output_conversion",$_POST["disable_mime_output_conversion"]);
	$main->SET_VALUE("disable_mime_input_processing",$_POST["disable_mime_input_processing"]);
	$main->SET_VALUE("mime_nesting_limit",$_POST["mime_nesting_limit"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-others-values=yes&hostname={$_POST["hostname"]}");
}


